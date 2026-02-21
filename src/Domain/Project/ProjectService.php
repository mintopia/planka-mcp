<?php

declare(strict_types=1);

namespace App\Domain\Project;

use App\Planka\Client\PlankaClient;

final class ProjectService
{
    public function __construct(
        private readonly PlankaClient $plankaClient,
    ) {}

    /** @return array<mixed> */
    public function getStructure(string $apiKey): array
    {
        return $this->plankaClient->get($apiKey, '/api/projects');
    }
}
