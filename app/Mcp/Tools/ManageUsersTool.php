<?php

declare(strict_types=1);

namespace App\Mcp\Tools;

use App\Domain\User\UserServiceInterface;
use App\Exception\ValidationException;
use App\Http\ApiKeyProviderInterface;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Attributes\Name;
use Laravel\Mcp\Server\Tool;

#[Name('planka_manage_users')]
#[Description('List, get, create, update, or delete Planka users.')]
final class ManageUsersTool extends Tool
{
    public function __construct(
        private readonly UserServiceInterface $userService,
        private readonly ApiKeyProviderInterface $apiKeyProvider,
    ) {}

    public function schema(JsonSchema $schema): array
    {
        return [
            'action' => $schema->string()
                ->required()
                ->description('Action to perform: list, get, create, update, or delete')
                ->enum(['list', 'get', 'create', 'update', 'delete']),
            'userId' => $schema->string()
                ->nullable()
                ->description('User ID (required for get, update, delete)'),
            'username' => $schema->string()
                ->nullable()
                ->description('Username (required for create)'),
            'email' => $schema->string()
                ->nullable()
                ->description('Email address (required for create)'),
            'password' => $schema->string()
                ->nullable()
                ->description('Password (required for create)'),
            'name' => $schema->string()
                ->nullable()
                ->description('Display name (optional for create, update)'),
        ];
    }

    public function handle(Request $request): Response
    {
        try {
            $action = (string) $request->get('action', '');
            /** @var ?string $userId */
            $userId = $request->get('userId');
            /** @var ?string $username */
            $username = $request->get('username');
            /** @var ?string $email */
            $email = $request->get('email');
            /** @var ?string $password */
            $password = $request->get('password');
            /** @var ?string $name */
            $name = $request->get('name');
            $apiKey = $this->apiKeyProvider->getApiKey();

            if ($action === 'create') {
                if (trim($username ?? '') === '') {
                    throw new ValidationException('username required for create');
                }
                if (trim($email ?? '') === '') {
                    throw new ValidationException('email required for create');
                }
                if (trim($password ?? '') === '') {
                    throw new ValidationException('password required for create');
                }
            }

            $result = match ($action) {
                'list' => $this->userService->getUsers($apiKey),
                'get' => $this->userService->getUser(
                    $apiKey,
                    $userId ?? throw new ValidationException('userId required for get'),
                ),
                'create' => $this->userService->createUser(
                    $apiKey,
                    $username ?? throw new ValidationException('username required for create'),
                    $email ?? throw new ValidationException('email required for create'),
                    $password ?? throw new ValidationException('password required for create'),
                    $name,
                ),
                'update' => $this->userService->updateUser(
                    $apiKey,
                    $userId ?? throw new ValidationException('userId required for update'),
                    $name,
                ),
                'delete' => $this->userService->deleteUser(
                    $apiKey,
                    $userId ?? throw new ValidationException('userId required for delete'),
                ),
                default => throw new ValidationException(sprintf('Invalid action "%s". Must be: list, get, create, update, delete', $action)),
            };

            return Response::json($result);
        } catch (\Throwable $e) {
            return Response::error($e->getMessage());
        }
    }
}
