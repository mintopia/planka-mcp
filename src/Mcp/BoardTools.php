<?php

declare(strict_types=1);

namespace App\Mcp;

use App\Domain\Board\BoardService;
use App\Domain\Project\ProjectService;
use App\Infrastructure\Http\ApiKeyProvider;
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
}
