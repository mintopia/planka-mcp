<?php

declare(strict_types=1);

namespace App\Domain\BoardList;

use App\Planka\Client\PlankaClientInterface;
use App\Shared\Exception\ValidationException;

final class ListService
{
    public function __construct(
        private readonly PlankaClientInterface $plankaClient,
    ) {}

    /** @return array<mixed> */
    public function manageList(
        string $apiKey,
        string $action,
        ?string $boardId = null,
        ?string $listId = null,
        ?string $name = null,
        ?int $position = null,
        ?string $toListId = null,
    ): array {
        return match ($action) {
            'create' => $this->createList($apiKey, $boardId ?? throw new ValidationException('boardId required for create'), $name, $position),
            'update' => $this->updateList($apiKey, $listId ?? throw new ValidationException('listId required for update'), $name, $position),
            'delete' => $this->deleteList($apiKey, $listId ?? throw new ValidationException('listId required for delete')),
            'get' => $this->getList($apiKey, $listId ?? throw new ValidationException('listId required for get')),
            'get_cards' => $this->getListCards($apiKey, $listId ?? throw new ValidationException('listId required for get_cards')),
            'move_cards' => $this->moveListCards($apiKey, $listId ?? throw new ValidationException('listId required for move_cards'), $toListId ?? throw new ValidationException('toListId required for move_cards')),
            'clear' => $this->clearList($apiKey, $listId ?? throw new ValidationException('listId required for clear')),
            default => throw new ValidationException(sprintf('Invalid action "%s". Must be: create, update, delete, get, get_cards, move_cards, clear', $action)),
        };
    }

    /** @return array<mixed> */
    public function getList(string $apiKey, string $listId): array
    {
        return $this->plankaClient->get($apiKey, '/api/lists/' . $listId);
    }

    /** @return array<mixed> */
    public function sortList(string $apiKey, string $listId, string $field): array
    {
        return $this->plankaClient->post($apiKey, '/api/lists/' . $listId . '/sort', ['fieldName' => $field]);
    }

    /** @return array<mixed> */
    private function createList(string $apiKey, string $boardId, ?string $name, ?int $position): array
    {
        $body = ['type' => 'active', 'position' => $position ?? 65536];
        if ($name !== null) {
            $body['name'] = $name;
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

    /** @return array<mixed> */
    public function getListCards(string $apiKey, string $listId): array
    {
        return $this->plankaClient->get($apiKey, '/api/lists/' . $listId . '/cards');
    }

    /** @return array<mixed> */
    public function moveListCards(string $apiKey, string $listId, string $toListId): array
    {
        return $this->plankaClient->post($apiKey, '/api/lists/' . $listId . '/move-cards', ['listId' => $toListId]);
    }

    /** @return array<mixed> */
    public function clearList(string $apiKey, string $listId): array
    {
        return $this->plankaClient->post($apiKey, '/api/lists/' . $listId . '/clear', []);
    }
}
