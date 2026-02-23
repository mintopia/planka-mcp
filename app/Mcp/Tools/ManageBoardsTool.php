<?php

declare(strict_types=1);

namespace App\Mcp\Tools;

use App\Domain\Board\BoardServiceInterface;
use App\Exception\ValidationException;
use App\Http\ApiKeyProviderInterface;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Attributes\Name;
use Laravel\Mcp\Server\Tool;

#[Name('planka_manage_boards')]
#[Description('Create, get, update, or delete a board within a project.')]
final class ManageBoardsTool extends Tool
{
    public function __construct(
        private readonly BoardServiceInterface $boardService,
        private readonly ApiKeyProviderInterface $apiKeyProvider,
    ) {}

    public function schema(JsonSchema $schema): array
    {
        return [
            'action' => $schema->string()
                ->required()
                ->description('Action to perform: create, get, update, or delete')
                ->enum(['create', 'get', 'update', 'delete']),
            'projectId' => $schema->string()
                ->nullable()
                ->description('Project ID (required for create) (from planka_get_structure)'),
            'boardId' => $schema->string()
                ->nullable()
                ->description('Board ID (required for get, update, delete) (from planka_get_structure or planka_get_board)'),
            'name' => $schema->string()
                ->nullable()
                ->description('Board name (required for create, optional for update)'),
            'position' => $schema->integer()
                ->nullable()
                ->description('Board position'),
        ];
    }

    public function handle(Request $request): Response
    {
        try {
            $action = (string) $request->get('action', '');
            /** @var ?string $projectId */
            $projectId = $request->get('projectId');
            /** @var ?string $boardId */
            $boardId = $request->get('boardId');
            /** @var ?string $name */
            $name = $request->get('name');
            /** @var ?int $position */
            $position = $request->get('position');
            $apiKey = $this->apiKeyProvider->getApiKey();

            $result = match ($action) {
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

            return Response::json($result);
        } catch (\Throwable $e) {
            return Response::error($e->getMessage());
        }
    }
}
