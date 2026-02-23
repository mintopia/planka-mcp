<?php

declare(strict_types=1);

namespace App\Mcp\Tools;

use App\Domain\Card\CardServiceInterface;
use App\Http\ApiKeyProviderInterface;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Attributes\Name;
use Laravel\Mcp\Server\Tool;

#[Name('planka_move_card')]
#[Description('Move a card to a different list or position. Use this for workflow transitions.')]
final class MoveCardTool extends Tool
{
    public function __construct(
        private readonly CardServiceInterface $cardService,
        private readonly ApiKeyProviderInterface $apiKeyProvider,
    ) {}

    public function schema(JsonSchema $schema): array
    {
        return [
            'cardId' => $schema->string()
                ->required()
                ->description('The card ID (from planka_get_board or planka_get_card)'),
            'listId' => $schema->string()
                ->required()
                ->description('Target list ID (from planka_get_board)'),
            'position' => $schema->integer()
                ->nullable()
                ->description('Position in the list (lower = higher). Default: end of list'),
        ];
    }

    public function handle(Request $request): Response
    {
        try {
            $cardId = (string) $request->get('cardId', '');
            $listId = (string) $request->get('listId', '');
            /** @var ?int $position */
            $position = $request->get('position');
            $apiKey = $this->apiKeyProvider->getApiKey();

            return Response::json($this->cardService->moveCard($apiKey, $cardId, $listId, $position));
        } catch (\Throwable $e) {
            return Response::error($e->getMessage());
        }
    }
}
