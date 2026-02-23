<?php

declare(strict_types=1);

namespace App\Domain\Board;

interface BoardServiceInterface
{
    /** @return array<mixed> */
    public function getBoard(string $apiKey, string $boardId): array;

    /** @return array<mixed> */
    public function createBoard(string $apiKey, string $projectId, string $name, ?int $position = null): array;

    /** @return array<mixed> */
    public function updateBoard(string $apiKey, string $boardId, ?string $name = null, ?int $position = null): array;

    /** @return array<mixed> */
    public function deleteBoard(string $apiKey, string $boardId): array;

    /** @return array<mixed> */
    public function addBoardMember(string $apiKey, string $boardId, string $userId, string $role = 'editor'): array;

    /** @return array<mixed> */
    public function updateBoardMembership(string $apiKey, string $membershipId, string $role): array;

    /** @return array<mixed> */
    public function removeBoardMember(string $apiKey, string $membershipId): array;
}
