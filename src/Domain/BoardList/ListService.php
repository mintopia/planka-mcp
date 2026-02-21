<?php

declare(strict_types=1);

namespace App\Domain\BoardList;

use App\Planka\Client\PlankaClient;
use App\Shared\Exception\ValidationException;

final class ListService
{
    public function __construct(
        private readonly PlankaClient $plankaClient,
    ) {}

    /** @return array<mixed> */
    public function manageList(
        string $apiKey,
        string $action,
        ?string $boardId = null,
        ?string $listId = null,
        ?string $name = null,
        ?int $position = null,
    ): array {
        return match ($action) {
            'create' => $this->createList($apiKey, $boardId ?? throw new ValidationException('boardId required for create'), $name, $position),
            'update' => $this->updateList($apiKey, $listId ?? throw new ValidationException('listId required for update'), $name, $position),
            'delete' => $this->deleteList($apiKey, $listId ?? throw new ValidationException('listId required for delete')),
            default => throw new ValidationException(sprintf('Invalid action "%s". Must be: create, update, delete', $action)),
        };
    }

    /** @return array<mixed> */
    private function createList(string $apiKey, string $boardId, ?string $name, ?int $position): array
    {
        $body = [];
        if ($name !== null) {
            $body['name'] = $name;
        }
        if ($position !== null) {
            $body['position'] = $position;
        }
        return $this->plankaClient->post($apiKey, '/api/boards/' . $boardId . '/lists', $body);
    }

    /** @return array<mixed> */
    private function updateList(string $apiKey, string $listId, ?string $name, ?int $position): array
    {
        $body = [];
        if ($name !== null) {
            $body['name'] = $name;
        }
        if ($position !== null) {
            $body['position'] = $position;
        }
        return $this->plankaClient->patch($apiKey, '/api/lists/' . $listId, $body);
    }

    /** @return array<mixed> */
    private function deleteList(string $apiKey, string $listId): array
    {
        return $this->plankaClient->delete($apiKey, '/api/lists/' . $listId);
    }
}
