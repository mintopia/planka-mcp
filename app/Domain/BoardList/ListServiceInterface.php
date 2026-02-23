<?php

declare(strict_types=1);

namespace App\Domain\BoardList;

interface ListServiceInterface
{
    /** @return array<mixed> */
    public function manageList(
        string $apiKey,
        string $action,
        ?string $boardId = null,
        ?string $listId = null,
        ?string $name = null,
        ?int $position = null,
        ?string $toListId = null,
    ): array;

    /** @return array<mixed> */
    public function getList(string $apiKey, string $listId): array;

    /** @return array<mixed> */
    public function sortList(string $apiKey, string $listId, string $field): array;

    /** @return array<mixed> */
    public function getListCards(string $apiKey, string $listId): array;

    /** @return array<mixed> */
    public function moveListCards(string $apiKey, string $listId, string $toListId): array;

    /** @return array<mixed> */
    public function clearList(string $apiKey, string $listId): array;
}
