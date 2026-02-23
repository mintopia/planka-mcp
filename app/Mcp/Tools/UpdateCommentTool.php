<?php

declare(strict_types=1);

namespace App\Mcp\Tools;

use App\Domain\Comment\CommentServiceInterface;
use App\Exception\ValidationException;
use App\Http\ApiKeyProviderInterface;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Attributes\Name;
use Laravel\Mcp\Server\Tool;

#[Name('planka_update_comment')]
#[Description('Update the text of an existing comment on a card.')]
final class UpdateCommentTool extends Tool
{
    public function __construct(
        private readonly CommentServiceInterface $commentService,
        private readonly ApiKeyProviderInterface $apiKeyProvider,
    ) {}

    public function schema(JsonSchema $schema): array
    {
        return [
            'commentId' => $schema->string()
                ->required()
                ->description('The comment ID to update'),
            'text' => $schema->string()
                ->required()
                ->description('New comment text (markdown supported)'),
        ];
    }

    public function handle(Request $request): Response
    {
        try {
            $commentId = (string) $request->get('commentId', '');
            $text = (string) $request->get('text', '');

            if (trim($text) === '') {
                throw new ValidationException('Comment text cannot be empty.');
            }

            $apiKey = $this->apiKeyProvider->getApiKey();

            return Response::json($this->commentService->updateComment($apiKey, $commentId, $text));
        } catch (\Throwable $e) {
            return Response::error($e->getMessage());
        }
    }
}
