<?php

declare(strict_types=1);

namespace App\Domain\Card;

interface CardServiceInterface
{
    /**
     * @param string[]|null $labelIds
     * @return array<mixed>
     */
    public function createCard(
        string $apiKey,
        string $listId,
        string $name,
        ?string $description = null,
        ?string $type = null,
        ?array $labelIds = null,
    ): array;

    /** @return array<mixed> */
    public function getCard(string $apiKey, string $cardId): array;

    /** @return array<mixed> */
    public function updateCard(
        string $apiKey,
        string $cardId,
        ?string $name = null,
        ?string $description = null,
        ?string $dueDate = null,
        ?bool $isClosed = null,
    ): array;

    /** @return array<mixed> */
    public function moveCard(
        string $apiKey,
        string $cardId,
        string $listId,
        ?int $position = null,
    ): array;

    /** @return array<mixed> */
    public function deleteCard(string $apiKey, string $cardId): array;

    /** @return array<mixed> */
    public function duplicateCard(string $apiKey, string $cardId): array;

    /** @return array<mixed> */
    public function addCardMember(string $apiKey, string $cardId, string $userId): array;

    /** @return array<mixed> */
    public function removeCardMember(string $apiKey, string $cardId, string $userId): array;
}
