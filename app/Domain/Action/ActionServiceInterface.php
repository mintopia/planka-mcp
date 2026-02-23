<?php

declare(strict_types=1);

namespace App\Domain\Action;

interface ActionServiceInterface
{
    /** @return array<mixed> */
    public function getBoardActions(string $apiKey, string $boardId): array;

    /** @return array<mixed> */
    public function getCardActions(string $apiKey, string $cardId): array;
}
