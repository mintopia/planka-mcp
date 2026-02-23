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

#[Name('planka_add_comment')]
#[Description('Add a comment to a card. Use this for status updates, notes, or agent activity logs.')]
final class AddCommentTool extends Tool
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
            'text' => $schema->string()
                ->required()
                ->description('Comment text (markdown supported)'),
        ];
    }

    public function handle(Request $request): Response
    {
        try {
            $cardId = (string) $request->get('cardId', '');
            $text = (string) $request->get('text', '');

            if (trim($text) === '') {
                throw new ValidationException('Comment text cannot be empty.');
            }

            $apiKey = $this->apiKeyProvider->getApiKey();

            return Response::json($this->commentService->addComment($apiKey, $cardId, $text));
        } catch (\Throwable $e) {
            return Response::error($e->getMessage());
        }
    }
}
