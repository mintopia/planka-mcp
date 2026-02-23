<?php

declare(strict_types=1);

namespace App\Mcp\Tools;

use App\Domain\Attachment\AttachmentServiceInterface;
use App\Exception\ValidationException;
use App\Http\ApiKeyProviderInterface;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Attributes\Name;
use Laravel\Mcp\Server\Tool;

#[Name('planka_manage_attachments')]
#[Description('Update or delete an attachment on a card.')]
final class ManageAttachmentsTool extends Tool
{
    public function __construct(
        private readonly AttachmentServiceInterface $attachmentService,
        private readonly ApiKeyProviderInterface $apiKeyProvider,
    ) {}

    public function schema(JsonSchema $schema): array
    {
        return [
            'action' => $schema->string()
                ->required()
                ->description('Action to perform: update or delete')
                ->enum(['update', 'delete']),
            'attachmentId' => $schema->string()
                ->nullable()
                ->description('Attachment ID (required for update and delete)'),
            'name' => $schema->string()
                ->nullable()
                ->description('New name for the attachment (for update)'),
            'isCover' => $schema->boolean()
                ->nullable()
                ->description('Set as card cover image (for update)'),
        ];
    }

    public function handle(Request $request): Response
    {
        try {
            $action = (string) $request->get('action', '');
            /** @var ?string $attachmentId */
            $attachmentId = $request->get('attachmentId');
            /** @var ?string $name */
            $name = $request->get('name');
            /** @var ?bool $isCover */
            $isCover = $request->get('isCover');
            $apiKey = $this->apiKeyProvider->getApiKey();

            $result = match ($action) {
                'update' => $this->attachmentService->updateAttachment(
                    $apiKey,
                    $attachmentId ?? throw new ValidationException('attachmentId required for update'),
                    $name,
                    $isCover,
                ),
                'delete' => $this->attachmentService->deleteAttachment(
                    $apiKey,
                    $attachmentId ?? throw new ValidationException('attachmentId required for delete'),
                ),
                default => throw new ValidationException(sprintf('Invalid action "%s". Must be: update, delete', $action)),
            };

            return Response::json($result);
        } catch (\Throwable $e) {
            return Response::error($e->getMessage());
        }
    }
}
