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

#[Name('planka_manage_board_memberships')]
#[Description('Add, update, or remove board memberships.')]
final class ManageBoardMembershipsTool extends Tool
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
                ->description('Action to perform: add, update, or remove')
                ->enum(['add', 'update', 'remove']),
            'boardId' => $schema->string()
                ->nullable()
                ->description('Board ID (required for add) (from planka_get_structure or planka_get_board)'),
            'membershipId' => $schema->string()
                ->nullable()
                ->description('Membership ID (required for update and remove)'),
            'userId' => $schema->string()
                ->nullable()
                ->description('User ID (required for add)'),
            'role' => $schema->string()
                ->description('Member role')
                ->enum(['editor', 'viewer'])
                ->default('editor'),
        ];
    }

    public function handle(Request $request): Response
    {
        try {
            $action = (string) $request->get('action', '');
            /** @var ?string $boardId */
            $boardId = $request->get('boardId');
            /** @var ?string $membershipId */
            $membershipId = $request->get('membershipId');
            /** @var ?string $userId */
            $userId = $request->get('userId');
            $role = (string) $request->get('role', 'editor');
            $apiKey = $this->apiKeyProvider->getApiKey();

            $result = match ($action) {
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

            return Response::json($result);
        } catch (\Throwable $e) {
            return Response::error($e->getMessage());
        }
    }
}
