<?php

declare(strict_types=1);

namespace App\Http;

interface ApiKeyProviderInterface
{
    public function getApiKey(): string;
}
