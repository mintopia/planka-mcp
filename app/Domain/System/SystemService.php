<?php

declare(strict_types=1);

namespace App\Domain\System;

use App\Planka\PlankaClientInterface;

final class SystemService implements SystemServiceInterface
{
    public function __construct(
        private readonly PlankaClientInterface $plankaClient,
    ) {}

    /** @return array<mixed> */
    public function getConfig(string $apiKey): array
    {
        return $this->plankaClient->get($apiKey, '/api/config');
    }

    /** @return array<mixed> */
    public function getBootstrap(string $apiKey): array
    {
        return $this->plankaClient->get($apiKey, '/api/bootstrap');
    }
}
