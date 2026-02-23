<?php

declare(strict_types=1);

namespace App\Domain\Action;

use App\Planka\PlankaClientInterface;

final class ActionService implements ActionServiceInterface
{
    public function __construct(
        private readonly PlankaClientInterface $plankaClient,
    ) {}

    /** @return array<mixed> */
    public function getBoardActions(string $apiKey, string $boardId): array
    {
        return $this->plankaClient->get($apiKey, '/api/boards/' . $boardId . '/actions');
    }

    /** @return array<mixed> */
    public function getCardActions(string $apiKey, string $cardId): array
    {
        return $this->plankaClient->get($apiKey, '/api/cards/' . $cardId . '/actions');
    }
}
