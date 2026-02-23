<?php

declare(strict_types=1);

namespace App\Mcp\Tools;

use App\Domain\Comment\CommentServiceInterface;
use App\Http\ApiKeyProviderInterface;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Attributes\Name;
use Laravel\Mcp\Server\Tool;

#[Name('planka_delete_comment')]
#[Description('Delete a comment from a card.')]
final class DeleteCommentTool extends Tool
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
                ->description('The comment ID to delete'),
        ];
    }

    public function handle(Request $request): Response
    {
        try {
            $commentId = (string) $request->get('commentId', '');
            $apiKey = $this->apiKeyProvider->getApiKey();

            return Response::json($this->commentService->deleteComment($apiKey, $commentId));
        } catch (\Throwable $e) {
            return Response::error($e->getMessage());
        }
    }
}
