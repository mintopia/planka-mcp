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

#[Name('planka_manage_user_credentials')]
#[Description('Update a user\'s email, password, or username. Requires the user\'s current password for verification.')]
final class ManageUserCredentialsTool extends Tool
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
                ->description('Action to perform: update-email, update-password, or update-username')
                ->enum(['update-email', 'update-password', 'update-username']),
            'userId' => $schema->string()
                ->required()
                ->description('User ID'),
            'currentPassword' => $schema->string()
                ->required()
                ->description('Current password for verification'),
            'email' => $schema->string()
                ->nullable()
                ->description('New email address (required for update-email)'),
            'newPassword' => $schema->string()
                ->nullable()
                ->description('New password (required for update-password)'),
            'username' => $schema->string()
                ->nullable()
                ->description('New username (required for update-username)'),
        ];
    }

    public function handle(Request $request): Response
    {
        try {
            $action = (string) $request->get('action', '');
            $userId = (string) $request->get('userId', '');
            $currentPassword = (string) $request->get('currentPassword', '');
            /** @var ?string $email */
            $email = $request->get('email');
            /** @var ?string $newPassword */
            $newPassword = $request->get('newPassword');
            /** @var ?string $username */
            $username = $request->get('username');
            $apiKey = $this->apiKeyProvider->getApiKey();

            match ($action) {
                'update-email' => trim($email ?? '') === '' ? throw new ValidationException('email required for update-email') : null,
                'update-password' => trim($newPassword ?? '') === '' ? throw new ValidationException('newPassword required for update-password') : null,
                'update-username' => trim($username ?? '') === '' ? throw new ValidationException('username required for update-username') : null,
                default => null,
            };

            $result = match ($action) {
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

            return Response::json($result);
        } catch (\Throwable $e) {
            return Response::error($e->getMessage());
        }
    }
}
