<?php

declare(strict_types=1);

namespace App\Mcp;

use App\Domain\Action\ActionService;
use App\Infrastructure\Http\ApiKeyProvider;
use App\Shared\Exception\ValidationException;
use Mcp\Capability\Attribute\McpTool;
use Mcp\Capability\Attribute\Schema;
use Mcp\Exception\ToolCallException;

final class ActionTools
{
    public function __construct(
        private readonly ActionService $actionService,
        private readonly ApiKeyProvider $apiKeyProvider,
    ) {}

    /** @return array<mixed> */
    #[McpTool(name: 'planka_get_actions', description: 'Get the activity log (actions) for a board or card.')]
    public function getActions(
        #[Schema(description: 'Whether to get actions for a board or a card', enum: ['board', 'card'])] string $type,
        #[Schema(description: 'The board ID or card ID')] string $id,
    ): array {
        try {
            $apiKey = $this->apiKeyProvider->getApiKey();

            return match ($type) {
                'board' => $this->actionService->getBoardActions($apiKey, $id),
                'card' => $this->actionService->getCardActions($apiKey, $id),
                default => throw new ValidationException(sprintf('Invalid type "%s". Must be: board, card', $type)),
            };
        } catch (\Throwable $e) {
            throw new ToolCallException($e->getMessage(), $e->getCode(), $e);
        }
    }
}
