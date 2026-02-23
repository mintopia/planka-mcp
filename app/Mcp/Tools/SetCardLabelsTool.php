<?php

declare(strict_types=1);

namespace App\Mcp\Tools;

use App\Domain\Label\LabelServiceInterface;
use App\Http\ApiKeyProviderInterface;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Attributes\Name;
use Laravel\Mcp\Server\Tool;

#[Name('planka_set_card_labels')]
#[Description('Add or remove labels from a card.')]
final class SetCardLabelsTool extends Tool
{
    public function __construct(
        private readonly LabelServiceInterface $labelService,
        private readonly ApiKeyProviderInterface $apiKeyProvider,
    ) {}

    public function schema(JsonSchema $schema): array
    {
        return [
            'cardId' => $schema->string()
                ->required()
                ->description('The card ID (from planka_get_board or planka_get_card)'),
            'addLabelIds' => $schema->array()
                ->nullable()
                ->description('Label IDs to add (from planka_get_board)')
                ->items($schema->string()),
            'removeLabelIds' => $schema->array()
                ->nullable()
                ->description('Label IDs to remove from the card (from planka_get_board — these are the label IDs, same as used for addLabelIds)')
                ->items($schema->string()),
        ];
    }

    public function handle(Request $request): Response
    {
        try {
            $cardId = (string) $request->get('cardId', '');
            /** @var ?array<string> $addLabelIds */
            $addLabelIds = $request->get('addLabelIds');
            /** @var ?array<string> $removeLabelIds */
            $removeLabelIds = $request->get('removeLabelIds');
            $apiKey = $this->apiKeyProvider->getApiKey();

            return Response::json($this->labelService->setCardLabels($apiKey, $cardId, $addLabelIds ?? [], $removeLabelIds ?? []));
        } catch (\Throwable $e) {
            return Response::error($e->getMessage());
        }
    }
}
