<?php

declare(strict_types=1);

namespace App\Mcp;

use App\Domain\Comment\CommentService;
use App\Infrastructure\Http\ApiKeyProvider;
use App\Shared\Exception\ValidationException;
use Mcp\Capability\Attribute\McpTool;
use Mcp\Capability\Attribute\Schema;
use Mcp\Exception\ToolCallException;

final class CommentTools
{
    public function __construct(
        private readonly CommentService $commentService,
        private readonly ApiKeyProvider $apiKeyProvider,
    ) {}

    /** @return array<mixed> */
    #[McpTool(name: 'planka_add_comment', description: 'Add a comment to a card. Use this for status updates, notes, or agent activity logs.')]
    public function addComment(
        #[Schema(description: 'The card ID (from planka_get_board or planka_get_card)')] string $cardId,
        #[Schema(description: 'Comment text (markdown supported)')] string $text,
    ): array {
        try {
            if (trim($text) === '') {
                throw new ValidationException('Comment text cannot be empty.');
            }
            $apiKey = $this->apiKeyProvider->getApiKey();

            return $this->commentService->addComment($apiKey, $cardId, $text);
        } catch (\Throwable $e) {
            throw new ToolCallException($e->getMessage(), $e->getCode(), $e);
        }
    }

    /** @return array<mixed> */
    #[McpTool(name: 'planka_get_comments', description: 'Get all comments on a card.')]
    public function getComments(
        #[Schema(description: 'The card ID (from planka_get_board or planka_get_card)')] string $cardId,
    ): array {
        try {
            $apiKey = $this->apiKeyProvider->getApiKey();

            return $this->commentService->getComments($apiKey, $cardId);
        } catch (\Throwable $e) {
            throw new ToolCallException($e->getMessage(), $e->getCode(), $e);
        }
    }
}
