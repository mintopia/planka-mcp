<?php

declare(strict_types=1);

namespace App\Mcp\Tools;

use App\Domain\BoardList\ListServiceInterface;
use App\Http\ApiKeyProviderInterface;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Attributes\Name;
use Laravel\Mcp\Server\Tool;

#[Name('planka_manage_lists')]
#[Description('Create, update, delete, get, or manage cards in a list on a board.')]
final class ManageListsTool extends Tool
{
    public function __construct(
        private readonly ListServiceInterface $listService,
        private readonly ApiKeyProviderInterface $apiKeyProvider,
    ) {}

    public function schema(JsonSchema $schema): array
    {
        return [
            'action' => $schema->string()
                ->required()
                ->description('Action to perform: create, update, delete, get, get_cards, move_cards, or clear')
                ->enum(['create', 'update', 'delete', 'get', 'get_cards', 'move_cards', 'clear']),
            'boardId' => $schema->string()
                ->nullable()
                ->description('Board ID (required for create) (from planka_get_structure or planka_get_board)'),
            'listId' => $schema->string()
                ->nullable()
                ->description('List ID (required for update/delete/get) (from planka_get_board)'),
            'name' => $schema->string()
                ->nullable()
                ->description('List name'),
            'position' => $schema->integer()
                ->nullable()
                ->description('List position'),
            'toListId' => $schema->string()
                ->nullable()
                ->description('Target list ID for move_cards action'),
        ];
    }

    public function handle(Request $request): Response
    {
        try {
            $action = (string) $request->get('action', '');
            /** @var ?string $boardId */
            $boardId = $request->get('boardId');
            /** @var ?string $listId */
            $listId = $request->get('listId');
            /** @var ?string $name */
            $name = $request->get('name');
            /** @var ?int $position */
            $position = $request->get('position');
            /** @var ?string $toListId */
            $toListId = $request->get('toListId');
            $apiKey = $this->apiKeyProvider->getApiKey();

            return Response::json($this->listService->manageList($apiKey, $action, $boardId, $listId, $name, $position, $toListId));
        } catch (\Throwable $e) {
            return Response::error($e->getMessage());
        }
    }
}
