<?php

declare(strict_types=1);

namespace App\Mcp;

use App\Domain\Board\BoardService;
use App\Domain\Project\ProjectService;
use App\Infrastructure\Http\ApiKeyProvider;
use App\Shared\Exception\ValidationException;
use Mcp\Capability\Attribute\McpTool;
use Mcp\Capability\Attribute\Schema;
use Mcp\Exception\ToolCallException;

final class BoardTools
{
    public function __construct(
        private readonly ProjectService $projectService,
        private readonly BoardService $boardService,
        private readonly ApiKeyProvider $apiKeyProvider,
    ) {}

    /** @return array<mixed> */
    #[McpTool(name: 'planka_get_structure', description: 'Get all Planka projects with their boards and lists')]
    public function getStructure(): array
    {
        try {
            $apiKey = $this->apiKeyProvider->getApiKey();

            return $this->projectService->getStructure($apiKey);
        } catch (\Throwable $e) {
            throw new ToolCallException($e->getMessage(), $e->getCode(), $e);
        }
    }

    /** @return array<mixed> */
    #[McpTool(name: 'planka_get_board', description: 'Get a board with all its lists, cards, and labels')]
    public function getBoard(
        #[Schema(description: 'The board ID (from planka_get_structure or planka_get_board)')] string $boardId,
    ): array {
        try {
            $apiKey = $this->apiKeyProvider->getApiKey();

            return $this->boardService->getBoard($apiKey, $boardId);
        } catch (\Throwable $e) {
            throw new ToolCallException($e->getMessage(), $e->getCode(), $e);
        }
    }

    /** @return array<mixed> */
    #[McpTool(name: 'planka_manage_boards', description: 'Create, get, update, or delete a board within a project.')]
    public function manageBoards(
        #[Schema(description: 'Action to perform: create, get, update, or delete', enum: ['create', 'get', 'update', 'delete'])] string $action,
        #[Schema(description: 'Project ID (required for create) (from planka_get_structure)')] ?string $projectId = null,
        #[Schema(description: 'Board ID (required for get, update, delete) (from planka_get_structure or planka_get_board)')] ?string $boardId = null,
        #[Schema(description: 'Board name (required for create, optional for update)')] ?string $name = null,
        #[Schema(description: 'Board position')] ?int $position = null,
    ): array {
        try {
            $apiKey = $this->apiKeyProvider->getApiKey();

            return match ($action) {
                'create' => $this->boardService->createBoard(
                    $apiKey,
                    $projectId ?? throw new ValidationException('projectId required for create'),
                    $name ?? throw new ValidationException('name required for create'),
                    $position,
                ),
                'get' => $this->boardService->getBoard(
                    $apiKey,
                    $boardId ?? throw new ValidationException('boardId required for get'),
                ),
                'update' => $this->boardService->updateBoard(
                    $apiKey,
                    $boardId ?? throw new ValidationException('boardId required for update'),
                    $name,
                    $position,
                ),
                'delete' => $this->boardService->deleteBoard(
                    $apiKey,
                    $boardId ?? throw new ValidationException('boardId required for delete'),
                ),
                default => throw new ValidationException(sprintf('Invalid action "%s". Must be: create, get, update, delete', $action)),
            };
        } catch (\Throwable $e) {
            throw new ToolCallException($e->getMessage(), $e->getCode(), $e);
        }
    }

    /** @return array<mixed> */
    #[McpTool(name: 'planka_manage_board_memberships', description: 'Add, update, or remove board memberships.')]
    public function manageBoardMemberships(
        #[Schema(description: 'Action to perform: add, update, or remove', enum: ['add', 'update', 'remove'])] string $action,
        #[Schema(description: 'Board ID (required for add) (from planka_get_structure or planka_get_board)')] ?string $boardId = null,
        #[Schema(description: 'Membership ID (required for update and remove)')] ?string $membershipId = null,
        #[Schema(description: 'User ID (required for add)')] ?string $userId = null,
        #[Schema(description: 'Member role', enum: ['editor', 'viewer'])] string $role = 'editor',
    ): array {
        try {
            $apiKey = $this->apiKeyProvider->getApiKey();

            return match ($action) {
                'add' => $this->boardService->addBoardMember(
                    $apiKey,
                    $boardId ?? throw new ValidationException('boardId required for add'),
                    $userId ?? throw new ValidationException('userId required for add'),
                    $role,
                ),
                'update' => $this->boardService->updateBoardMembership(
                    $apiKey,
                    $membershipId ?? throw new ValidationException('membershipId required for update'),
                    $role,
                ),
                'remove' => $this->boardService->removeBoardMember(
                    $apiKey,
                    $membershipId ?? throw new ValidationException('membershipId required for remove'),
                ),
                default => throw new ValidationException(sprintf('Invalid action "%s". Must be: add, update, remove', $action)),
            };
        } catch (\Throwable $e) {
            throw new ToolCallException($e->getMessage(), $e->getCode(), $e);
        }
    }
}
