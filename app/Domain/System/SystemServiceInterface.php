<?php

declare(strict_types=1);

namespace App\Domain\System;

interface SystemServiceInterface
{
    /** @return array<mixed> */
    public function getConfig(string $apiKey): array;

    /** @return array<mixed> */
    public function getBootstrap(string $apiKey): array;
}
