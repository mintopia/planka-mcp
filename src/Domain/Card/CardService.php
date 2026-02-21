<?php

declare(strict_types=1);

namespace App\Domain\Card;

use App\Planka\Client\PlankaClientInterface;

final class CardService
{
    public function __construct(
        private readonly PlankaClientInterface $plankaClient,
    ) {}

    /**
     * @param string[]|null $labelIds
     * @return array<mixed>
     */
    public function createCard(
        string $apiKey,
        string $listId,
        string $name,
        ?string $description = null,
        ?string $dueDate = null,
        ?array $labelIds = null,
    ): array {
        $body = ['name' => $name];
        if ($description !== null) {
            $body['description'] = $description;
        }
        if ($dueDate !== null) {
            $body['dueDate'] = $dueDate;
        }

        $result = $this->plankaClient->post($apiKey, '/api/lists/' . $listId . '/cards', $body);

        if ($labelIds !== null && $labelIds !== []) {
            $cardId = (string) ($result['item']['id'] ?? '');
            if ($cardId !== '') {
                foreach ($labelIds as $labelId) {
                    $this->plankaClient->post($apiKey, '/api/cards/' . $cardId . '/card-labels', ['labelId' => $labelId]);
                }
            }
        }

        return $result;
    }

    /** @return array<mixed> */
    public function getCard(string $apiKey, string $cardId): array
    {
        return $this->plankaClient->get($apiKey, '/api/cards/' . $cardId);
    }

    /**
     * Update a card's properties.
     *
     * For $description and $dueDate: null means "no change", empty string means "clear the field".
     *
     * @return array<mixed>
     */
    public function updateCard(
        string $apiKey,
        string $cardId,
        ?string $name = null,
        ?string $description = null,
        ?string $dueDate = null,
        ?bool $isCompleted = null,
    ): array {
        $body = [];
        if ($name !== null) {
            $body['name'] = $name;
        }
        if ($description !== null) {
            $body['description'] = $description === '' ? null : $description;
        }
        if ($dueDate !== null) {
            $body['dueDate'] = $dueDate === '' ? null : $dueDate;
        }
        if ($isCompleted !== null) {
            $body['isCompleted'] = $isCompleted;
        }

        return $this->plankaClient->patch($apiKey, '/api/cards/' . $cardId, $body);
    }

    /** @return array<mixed> */
    public function moveCard(
        string $apiKey,
        string $cardId,
        string $listId,
        ?int $position = null,
    ): array {
        $body = ['listId' => $listId];
        if ($position !== null) {
            $body['position'] = $position;
        }

        return $this->plankaClient->patch($apiKey, '/api/cards/' . $cardId, $body);
    }

    /** @return array<mixed> */
    public function deleteCard(string $apiKey, string $cardId): array
    {
        return $this->plankaClient->delete($apiKey, '/api/cards/' . $cardId);
    }

    /** @return array<mixed> */
    public function duplicateCard(string $apiKey, string $cardId): array
    {
        return $this->plankaClient->post($apiKey, '/api/cards/' . $cardId . '/duplicate', []);
    }

    /** @return array<mixed> */
    public function addCardMember(string $apiKey, string $cardId, string $userId): array
    {
        return $this->plankaClient->post($apiKey, '/api/cards/' . $cardId . '/memberships', ['userId' => $userId]);
    }

    /** @return array<mixed> */
    public function removeCardMember(string $apiKey, string $cardId, string $userId): array
    {
        return $this->plankaClient->delete($apiKey, '/api/cards/' . $cardId . '/memberships/userId:' . $userId);
    }
}
