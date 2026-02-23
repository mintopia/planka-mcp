<?php

declare(strict_types=1);

namespace App\Domain\User;

use App\Planka\PlankaClientInterface;

final class UserService implements UserServiceInterface
{
    public function __construct(
        private readonly PlankaClientInterface $plankaClient,
    ) {}

    /** @return array<mixed> */
    public function getUsers(string $apiKey): array
    {
        return $this->plankaClient->get($apiKey, '/api/users');
    }

    /** @return array<mixed> */
    public function createUser(string $apiKey, string $username, string $email, string $password, ?string $name = null): array
    {
        $body = ['username' => $username, 'email' => $email, 'password' => $password];
        if ($name !== null) {
            $body['name'] = $name;
        }

        return $this->plankaClient->post($apiKey, '/api/users', $body);
    }

    /** @return array<mixed> */
    public function getUser(string $apiKey, string $userId): array
    {
        return $this->plankaClient->get($apiKey, '/api/users/' . $userId);
    }

    /** @return array<mixed> */
    public function updateUser(string $apiKey, string $userId, ?string $name = null): array
    {
        $body = [];
        if ($name !== null) {
            $body['name'] = $name;
        }

        return $this->plankaClient->patch($apiKey, '/api/users/' . $userId, $body);
    }

    /** @return array<mixed> */
    public function deleteUser(string $apiKey, string $userId): array
    {
        return $this->plankaClient->delete($apiKey, '/api/users/' . $userId);
    }

    /** @return array<mixed> */
    public function updateUserEmail(string $apiKey, string $userId, string $email, string $currentPassword): array
    {
        return $this->plankaClient->patch($apiKey, '/api/users/' . $userId . '/email', ['email' => $email, 'currentPassword' => $currentPassword]);
    }

    /** @return array<mixed> */
    public function updateUserPassword(string $apiKey, string $userId, string $currentPassword, string $newPassword): array
    {
        return $this->plankaClient->patch($apiKey, '/api/users/' . $userId . '/password', ['currentPassword' => $currentPassword, 'newPassword' => $newPassword]);
    }

    /** @return array<mixed> */
    public function updateUserUsername(string $apiKey, string $userId, string $username, string $currentPassword): array
    {
        return $this->plankaClient->patch($apiKey, '/api/users/' . $userId . '/username', ['username' => $username, 'currentPassword' => $currentPassword]);
    }
}
