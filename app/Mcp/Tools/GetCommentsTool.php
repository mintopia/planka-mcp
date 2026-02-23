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

#[Name('planka_get_comments')]
#[Description('Get all comments on a card.')]
final class GetCommentsTool extends Tool
{
    public function __construct(
        private readonly CommentServiceInterface $commentService,
        private readonly ApiKeyProviderInterface $apiKeyProvider,
    ) {}

    public function schema(JsonSchema $schema): array
    {
        return [
            'cardId' => $schema->string()
                ->required()
                ->description('The card ID (from planka_get_board or planka_get_card)'),
        ];
    }

    public function handle(Request $request): Response
    {
        try {
            $cardId = (string) $request->get('cardId', '');
            $apiKey = $this->apiKeyProvider->getApiKey();

            return Response::json($this->commentService->getComments($apiKey, $cardId));
        } catch (\Throwable $e) {
            return Response::error($e->getMessage());
        }
    }
}
