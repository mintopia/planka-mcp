<?php

declare(strict_types=1);

namespace App\Mcp;

use App\Domain\BoardList\ListService;
use App\Infrastructure\Http\ApiKeyProvider;
use Mcp\Capability\Attribute\McpTool;
use Mcp\Capability\Attribute\Schema;
use Mcp\Exception\ToolCallException;

final class ListTools
{
    public function __construct(
        private readonly ListService $listService,
        private readonly ApiKeyProvider $apiKeyProvider,
    ) {}

    /** @return array<mixed> */
    #[McpTool(name: 'planka_manage_lists', description: 'Create, update, delete, or get a list on a board.')]
    public function manageLists(
        #[Schema(description: 'Action to perform: create, update, delete, or get', enum: ['create', 'update', 'delete', 'get'])] string $action,
        #[Schema(description: 'Board ID (required for create) (from planka_get_structure or planka_get_board)')] ?string $boardId = null,
        #[Schema(description: 'List ID (required for update/delete/get) (from planka_get_board)')] ?string $listId = null,
        #[Schema(description: 'List name')] ?string $name = null,
        #[Schema(description: 'List position')] ?int $position = null,
    ): array {
        try {
            $apiKey = $this->apiKeyProvider->getApiKey();

            return $this->listService->manageList($apiKey, $action, $boardId, $listId, $name, $position);
        } catch (\Throwable $e) {
            throw new ToolCallException($e->getMessage(), $e->getCode(), $e);
        }
    }

    /** @return array<mixed> */
    #[McpTool(name: 'planka_sort_list', description: 'Sort cards within a list by a specified field.')]
    public function sortList(
        #[Schema(description: 'List ID (from planka_get_board)')] string $listId,
        #[Schema(description: 'Field to sort by: name, dueDate, or createdAt', enum: ['name', 'dueDate', 'createdAt'])] string $field,
    ): array {
        try {
            $apiKey = $this->apiKeyProvider->getApiKey();

            return $this->listService->sortList($apiKey, $listId, $field);
        } catch (\Throwable $e) {
            throw new ToolCallException($e->getMessage(), $e->getCode(), $e);
        }
    }
}
