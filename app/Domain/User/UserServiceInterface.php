<?php

declare(strict_types=1);

namespace App\Domain\User;

interface UserServiceInterface
{
    /** @return array<mixed> */
    public function getUsers(string $apiKey): array;

    /** @return array<mixed> */
    public function createUser(string $apiKey, string $username, string $email, string $password, ?string $name = null): array;

    /** @return array<mixed> */
    public function getUser(string $apiKey, string $userId): array;

    /** @return array<mixed> */
    public function updateUser(string $apiKey, string $userId, ?string $name = null): array;

    /** @return array<mixed> */
    public function deleteUser(string $apiKey, string $userId): array;

    /** @return array<mixed> */
    public function updateUserEmail(string $apiKey, string $userId, string $email, string $currentPassword): array;

    /** @return array<mixed> */
    public function updateUserPassword(string $apiKey, string $userId, string $currentPassword, string $newPassword): array;

    /** @return array<mixed> */
    public function updateUserUsername(string $apiKey, string $userId, string $username, string $currentPassword): array;
}
