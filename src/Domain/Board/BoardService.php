<?php

declare(strict_types=1);

namespace App\Domain\Board;

use App\Planka\Client\PlankaClient;

final class BoardService
{
    public function __construct(
        private readonly PlankaClient $plankaClient,
    ) {}

    /** @return array<mixed> */
    public function getBoard(string $apiKey, string $boardId): array
    {
        return $this->plankaClient->get($apiKey, '/api/boards/' . $boardId);
    }
}
