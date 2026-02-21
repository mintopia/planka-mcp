<?php

declare(strict_types=1);

namespace App\Mcp;

use App\Domain\Card\CardService;
use App\Infrastructure\Http\ApiKeyProvider;
use App\Shared\Exception\ValidationException;
use Mcp\Capability\Attribute\McpTool;
use Mcp\Capability\Attribute\Schema;
use Mcp\Exception\ToolCallException;

final class CardTools
{
    public function __construct(
        private readonly CardService $cardService,
        private readonly ApiKeyProvider $apiKeyProvider,
    ) {}

    /**
     * @param string[]|null $labelIds
     * @return array<mixed>
     */
    #[McpTool(name: 'planka_create_card', description: 'Create a new card on a board. Optionally add tasks (checklist items) at the same time.')]
    public function createCard(
        #[Schema(description: 'The list ID (from planka_get_board)')] string $listId,
        #[Schema(description: 'Card title')] string $name,
        #[Schema(description: 'Card description (markdown supported)')] ?string $description = null,
        #[Schema(description: 'Due date in ISO format', format: 'date-time')] ?string $dueDate = null,
        #[Schema(description: 'Optional: Label IDs to attach (from planka_get_board)', items: ['type' => 'string'])] ?array $labelIds = null,
    ): array {
        try {
            if (trim($name) === '') {
                throw new ValidationException('Card name cannot be empty.');
            }

            $apiKey = $this->apiKeyProvider->getApiKey();

            return $this->cardService->createCard($apiKey, $listId, $name, $description, $dueDate, $labelIds);
        } catch (\Throwable $e) {
            throw new ToolCallException($e->getMessage(), $e->getCode(), $e);
        }
    }

    /** @return array<mixed> */
    #[McpTool(name: 'planka_get_card', description: 'Get full details of a card including tasks, comments, labels, and attachments.')]
    public function getCard(
        #[Schema(description: 'The card ID (from planka_get_board or planka_get_card)')] string $cardId,
    ): array {
        try {
            $apiKey = $this->apiKeyProvider->getApiKey();

            return $this->cardService->getCard($apiKey, $cardId);
        } catch (\Throwable $e) {
            throw new ToolCallException($e->getMessage(), $e->getCode(), $e);
        }
    }

    /** @return array<mixed> */
    #[McpTool(name: 'planka_update_card', description: "Update a card's properties (name, description, due date, completion status).")]
    public function updateCard(
        #[Schema(description: 'The card ID (from planka_get_board or planka_get_card)')] string $cardId,
        #[Schema(description: 'New card title')] ?string $name = null,
        #[Schema(description: 'New description (empty string to clear)')] ?string $description = null,
        #[Schema(description: 'New due date in ISO format (empty string to clear)', format: 'date-time')] ?string $dueDate = null,
        #[Schema(description: 'Mark card as complete/incomplete')] ?bool $isCompleted = null,
    ): array {
        try {
            if ($name === null && $description === null && $dueDate === null && $isCompleted === null) {
                throw new ValidationException('At least one field (name, description, dueDate, or isCompleted) must be provided.');
            }

            $apiKey = $this->apiKeyProvider->getApiKey();

            return $this->cardService->updateCard($apiKey, $cardId, $name, $description, $dueDate, $isCompleted);
        } catch (\Throwable $e) {
            throw new ToolCallException($e->getMessage(), $e->getCode(), $e);
        }
    }

    /** @return array<mixed> */
    #[McpTool(name: 'planka_move_card', description: 'Move a card to a different list or position. Use this for workflow transitions.')]
    public function moveCard(
        #[Schema(description: 'The card ID (from planka_get_board or planka_get_card)')] string $cardId,
        #[Schema(description: 'Target list ID (from planka_get_board)')] string $listId,
        #[Schema(description: 'Position in the list (lower = higher). Default: end of list')] ?int $position = null,
    ): array {
        try {
            $apiKey = $this->apiKeyProvider->getApiKey();

            return $this->cardService->moveCard($apiKey, $cardId, $listId, $position);
        } catch (\Throwable $e) {
            throw new ToolCallException($e->getMessage(), $e->getCode(), $e);
        }
    }

    /** @return array<mixed> */
    #[McpTool(name: 'planka_delete_card', description: 'Permanently delete a card. This cannot be undone.')]
    public function deleteCard(
        #[Schema(description: 'The card ID to delete (from planka_get_board or planka_get_card)')] string $cardId,
    ): array {
        try {
            $apiKey = $this->apiKeyProvider->getApiKey();

            return $this->cardService->deleteCard($apiKey, $cardId);
        } catch (\Throwable $e) {
            throw new ToolCallException($e->getMessage(), $e->getCode(), $e);
        }
    }
}
