<?php

declare(strict_types=1);

namespace App\Domain\Webhook;

interface WebhookServiceInterface
{
    /** @return array<mixed> */
    public function getWebhooks(string $apiKey): array;

    /** @return array<mixed> */
    public function createWebhook(string $apiKey, string $name, string $url, ?string $events = null, ?string $description = null): array;

    /** @return array<mixed> */
    public function updateWebhook(string $apiKey, string $webhookId, ?string $url, ?string $events, ?string $description): array;

    /** @return array<mixed> */
    public function deleteWebhook(string $apiKey, string $webhookId): array;
}
