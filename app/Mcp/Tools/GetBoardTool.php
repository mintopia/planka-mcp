<?php

declare(strict_types=1);

namespace App\Mcp\Tools;

use App\Domain\Board\BoardServiceInterface;
use App\Http\ApiKeyProviderInterface;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Attributes\Name;
use Laravel\Mcp\Server\Tool;

#[Name('planka_get_board')]
#[Description('Get a board with all its lists, cards, and labels')]
final class GetBoardTool extends Tool
{
    public function __construct(
        private readonly BoardServiceInterface $boardService,
        private readonly ApiKeyProviderInterface $apiKeyProvider,
    ) {}

    public function schema(JsonSchema $schema): array
    {
        return [
            'boardId' => $schema->string()
                ->required()
                ->description('The board ID (from planka_get_structure or planka_get_board)'),
        ];
    }

    public function handle(Request $request): Response
    {
        try {
            $boardId = (string) $request->get('boardId', '');
            $apiKey = $this->apiKeyProvider->getApiKey();

            return Response::json($this->boardService->getBoard($apiKey, $boardId));
        } catch (\Throwable $e) {
            return Response::error($e->getMessage());
        }
    }
}
