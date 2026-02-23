<?php

declare(strict_types=1);

namespace App\Mcp\Tools;

use App\Domain\CustomField\CustomFieldServiceInterface;
use App\Exception\ValidationException;
use App\Http\ApiKeyProviderInterface;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Attributes\Name;
use Laravel\Mcp\Server\Tool;

#[Name('planka_manage_custom_field_values')]
#[Description('Set or delete a custom field value on a card.')]
final class ManageCustomFieldValuesTool extends Tool
{
    public function __construct(
        private readonly CustomFieldServiceInterface $customFieldService,
        private readonly ApiKeyProviderInterface $apiKeyProvider,
    ) {}

    public function schema(JsonSchema $schema): array
    {
        return [
            'action' => $schema->string()
                ->required()
                ->description('Action to perform')
                ->enum(['set', 'delete']),
            'cardId' => $schema->string()
                ->required()
                ->description('The card ID'),
            'customFieldGroupId' => $schema->string()
                ->required()
                ->description('The custom field group ID'),
            'customFieldId' => $schema->string()
                ->required()
                ->description('The custom field ID'),
            'value' => $schema->string()
                ->nullable()
                ->description('The value to set — required for action=set'),
        ];
    }

    public function handle(Request $request): Response
    {
        try {
            $action = (string) $request->get('action', '');
            $cardId = (string) $request->get('cardId', '');
            $customFieldGroupId = (string) $request->get('customFieldGroupId', '');
            $customFieldId = (string) $request->get('customFieldId', '');
            /** @var ?string $value */
            $value = $request->get('value');
            $apiKey = $this->apiKeyProvider->getApiKey();

            $result = match ($action) {
                'set' => $this->customFieldService->setFieldValue($apiKey, $cardId, $customFieldGroupId, $customFieldId, $value ?? throw new ValidationException('value required for set')),
                'delete' => $this->customFieldService->deleteFieldValue($apiKey, $cardId, $customFieldGroupId, $customFieldId),
                default => throw new ValidationException(sprintf('Invalid action "%s". Must be: set, delete', $action)),
            };

            return Response::json($result);
        } catch (\Throwable $e) {
            return Response::error($e->getMessage());
        }
    }
}
