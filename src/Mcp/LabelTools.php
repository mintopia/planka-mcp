<?php

declare(strict_types=1);

namespace App\Mcp;

use App\Domain\Label\LabelService;
use App\Infrastructure\Http\ApiKeyProvider;
use Mcp\Capability\Attribute\McpTool;
use Mcp\Capability\Attribute\Schema;
use Mcp\Exception\ToolCallException;

final class LabelTools
{
    public function __construct(
        private readonly LabelService $labelService,
        private readonly ApiKeyProvider $apiKeyProvider,
    ) {}

    /** @return array<mixed> */
    #[McpTool(name: 'planka_manage_labels', description: 'Create, update, or delete labels on a board.')]
    public function manageLabels(
        #[Schema(description: 'Action to perform: create, update, or delete', enum: ['create', 'update', 'delete'])] string $action,
        #[Schema(description: 'Board ID (required for create) (from planka_get_structure or planka_get_board)')] ?string $boardId = null,
        #[Schema(description: 'Label ID (required for update/delete) (from planka_get_board)')] ?string $labelId = null,
        #[Schema(description: 'Label name')] ?string $name = null,
        #[Schema(description: 'Label color', enum: ['berry-red', 'pumpkin-orange', 'lagoon-blue', 'pink-tulip', 'light-mud', 'orange-peel', 'sky-blue', 'coconut-milk', 'matte-green', 'pistachio', 'sea-foam', 'pale-sky', 'old-rose', 'moss-green', 'midnight-blue', 'sunrise', 'dark-granite', 'gunmetal', 'wet-stone', 'canary-yellow', 'fine-emerald', 'morning-sky', 'sunny-grass'])] ?string $color = null,
    ): array {
        try {
            $apiKey = $this->apiKeyProvider->getApiKey();

            return $this->labelService->manageLabel($apiKey, $action, $boardId, $labelId, $name, $color);
        } catch (\Throwable $e) {
            throw new ToolCallException($e->getMessage(), $e->getCode(), $e);
        }
    }

    /**
     * @param string[]|null $addLabelIds
     * @param string[]|null $removeLabelIds
     * @return array<mixed>
     */
    #[McpTool(name: 'planka_set_card_labels', description: 'Add or remove labels from a card.')]
    public function setCardLabels(
        #[Schema(description: 'The card ID (from planka_get_board or planka_get_card)')] string $cardId,
        #[Schema(description: 'Label IDs to add (from planka_get_board)', items: ['type' => 'string'])] ?array $addLabelIds = null,
        #[Schema(description: 'Label IDs to remove (from planka_get_board)', items: ['type' => 'string'])] ?array $removeLabelIds = null,
    ): array {
        try {
            $apiKey = $this->apiKeyProvider->getApiKey();

            return $this->labelService->setCardLabels($apiKey, $cardId, $addLabelIds ?? [], $removeLabelIds ?? []);
        } catch (\Throwable $e) {
            throw new ToolCallException($e->getMessage(), $e->getCode(), $e);
        }
    }
}
