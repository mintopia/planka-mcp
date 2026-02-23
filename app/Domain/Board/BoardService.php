<?php

declare(strict_types=1);

namespace App\Domain\Board;

use App\Planka\PlankaClientInterface;

final class BoardService implements BoardServiceInterface
{
    public function __construct(
        private readonly PlankaClientInterface $plankaClient,
    ) {}

    /** @return array<mixed> */
    public function getBoard(string $apiKey, string $boardId): array
    {
        return $this->plankaClient->get($apiKey, '/api/boards/' . $boardId);
    }

    /** @return array<mixed> */
    public function createBoard(string $apiKey, string $projectId, string $name, ?int $position = null): array
    {
        $body = ['name' => $name, 'position' => $position ?? 65536];
        return $this->plankaClient->post($apiKey, '/api/projects/' . $projectId . '/boards', $body);
    }

    /** @return array<mixed> */
    public function updateBoard(string $apiKey, string $boardId, ?string $name = null, ?int $position = null): array
    {
        $body = [];
        if ($name !== null) {
            $body['name'] = $name;
        }
        if ($position !== null) {
            $body['position'] = $position;
        }
        return $this->plankaClient->patch($apiKey, '/api/boards/' . $boardId, $body);
    }

    /** @return array<mixed> */
    public function deleteBoard(string $apiKey, string $boardId): array
    {
        return $this->plankaClient->delete($apiKey, '/api/boards/' . $boardId);
    }

    /** @return array<mixed> */
    public function addBoardMember(string $apiKey, string $boardId, string $userId, string $role = 'editor'): array
    {
        return $this->plankaClient->post($apiKey, '/api/boards/' . $boardId . '/board-memberships', ['userId' => $userId, 'role' => $role]);
    }

    /** @return array<mixed> */
    public function updateBoardMembership(string $apiKey, string $membershipId, string $role): array
    {
        return $this->plankaClient->patch($apiKey, '/api/board-memberships/' . $membershipId, ['role' => $role]);
    }

    /** @return array<mixed> */
    public function removeBoardMember(string $apiKey, string $membershipId): array
    {
        return $this->plankaClient->delete($apiKey, '/api/board-memberships/' . $membershipId);
    }
}
