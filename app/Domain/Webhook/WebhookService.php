<?php

declare(strict_types=1);

namespace App\Domain\Webhook;

use App\Planka\PlankaClientInterface;

final class WebhookService implements WebhookServiceInterface
{
    public function __construct(
        private readonly PlankaClientInterface $plankaClient,
    ) {}

    /** @return array<mixed> */
    public function getWebhooks(string $apiKey): array
    {
        return $this->plankaClient->get($apiKey, '/api/webhooks');
    }

    /** @return array<mixed> */
    public function createWebhook(string $apiKey, string $name, string $url, ?string $events = null, ?string $description = null): array
    {
        $body = ['name' => $name, 'url' => $url];
        if ($events !== null) {
            $body['events'] = $events;
        }
        if ($description !== null) {
            $body['description'] = $description;
        }

        return $this->plankaClient->post($apiKey, '/api/webhooks', $body);
    }

    /** @return array<mixed> */
    public function updateWebhook(string $apiKey, string $webhookId, ?string $url, ?string $events, ?string $description): array
    {
        $body = [];
        if ($url !== null) {
            $body['url'] = $url;
        }
        if ($events !== null) {
            $body['events'] = $events;
        }
        if ($description !== null) {
            $body['description'] = $description;
        }

        return $this->plankaClient->patch($apiKey, '/api/webhooks/' . $webhookId, $body);
    }

    /** @return array<mixed> */
    public function deleteWebhook(string $apiKey, string $webhookId): array
    {
        return $this->plankaClient->delete($apiKey, '/api/webhooks/' . $webhookId);
    }
}
