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
    #[McpTool(name: 'planka_manage_lists', description: 'Create, update, or delete lists on a board.')]
    public function manageLists(
        #[Schema(description: 'Action to perform: create, update, or delete', enum: ['create', 'update', 'delete'])] string $action,
        #[Schema(description: 'Board ID (required for create) (from planka_get_structure or planka_get_board)')] ?string $boardId = null,
        #[Schema(description: 'List ID (required for update/delete) (from planka_get_board)')] ?string $listId = null,
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
}
