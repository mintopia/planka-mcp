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

#[Name('planka_delete_card')]
#[Description('Permanently delete a card. This cannot be undone.')]
final class DeleteCardTool extends Tool
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
                ->description('The card ID to delete (from planka_get_board or planka_get_card)'),
        ];
    }

    public function handle(Request $request): Response
    {
        try {
            $cardId = (string) $request->get('cardId', '');
            $apiKey = $this->apiKeyProvider->getApiKey();

            return Response::json($this->cardService->deleteCard($apiKey, $cardId));
        } catch (\Throwable $e) {
            return Response::error($e->getMessage());
        }
    }
}
