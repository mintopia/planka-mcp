<?php

declare(strict_types=1);

namespace App\Mcp;

use App\Domain\User\UserService;
use App\Infrastructure\Http\ApiKeyProvider;
use App\Shared\Exception\ValidationException;
use Mcp\Capability\Attribute\McpTool;
use Mcp\Capability\Attribute\Schema;
use Mcp\Exception\ToolCallException;

final class UserTools
{
    public function __construct(
        private readonly UserService $userService,
        private readonly ApiKeyProvider $apiKeyProvider,
    ) {}

    /** @return array<mixed> */
    #[McpTool(name: 'planka_manage_users', description: 'List, get, create, update, or delete Planka users.')]
    public function manageUsers(
        #[Schema(description: 'Action to perform: list, get, create, update, or delete', enum: ['list', 'get', 'create', 'update', 'delete'])] string $action,
        #[Schema(description: 'User ID (required for get, update, delete)')] ?string $userId = null,
        #[Schema(description: 'Username (required for create)')] ?string $username = null,
        #[Schema(description: 'Email address (required for create)')] ?string $email = null,
        #[Schema(description: 'Password (required for create)')] ?string $password = null,
        #[Schema(description: 'Display name (optional for create, update)')] ?string $name = null,
    ): array {
        try {
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

            return match ($action) {
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
        } catch (\Throwable $e) {
            throw new ToolCallException($e->getMessage(), $e->getCode(), $e);
        }
    }

    /** @return array<mixed> */
    #[McpTool(name: 'planka_manage_user_credentials', description: 'Update a user\'s email, password, or username. Requires the user\'s current password for verification.')]
    public function manageUserCredentials(
        #[Schema(description: 'Action to perform: update-email, update-password, or update-username', enum: ['update-email', 'update-password', 'update-username'])] string $action,
        #[Schema(description: 'User ID')] string $userId,
        #[Schema(description: 'Current password for verification')] string $currentPassword,
        #[Schema(description: 'New email address (required for update-email)')] ?string $email = null,
        #[Schema(description: 'New password (required for update-password)')] ?string $newPassword = null,
        #[Schema(description: 'New username (required for update-username)')] ?string $username = null,
    ): array {
        try {
            $apiKey = $this->apiKeyProvider->getApiKey();

            match ($action) {
                'update-email' => trim($email ?? '') === '' ? throw new ValidationException('email required for update-email') : null,
                'update-password' => trim($newPassword ?? '') === '' ? throw new ValidationException('newPassword required for update-password') : null,
                'update-username' => trim($username ?? '') === '' ? throw new ValidationException('username required for update-username') : null,
                default => null,
            };

            return match ($action) {
                'update-email' => $this->userService->updateUserEmail(
                    $apiKey,
                    $userId,
                    $email ?? throw new ValidationException('email required for update-email'),
                    $currentPassword,
                ),
                'update-password' => $this->userService->updateUserPassword(
                    $apiKey,
                    $userId,
                    $currentPassword,
                    $newPassword ?? throw new ValidationException('newPassword required for update-password'),
                ),
                'update-username' => $this->userService->updateUserUsername(
                    $apiKey,
                    $userId,
                    $username ?? throw new ValidationException('username required for update-username'),
                    $currentPassword,
                ),
                default => throw new ValidationException(sprintf('Invalid action "%s". Must be: update-email, update-password, update-username', $action)),
            };
        } catch (\Throwable $e) {
            throw new ToolCallException($e->getMessage(), $e->getCode(), $e);
        }
    }
}
