<?php

declare(strict_types=1);

namespace App\Mcp\Resources;

use App\Domain\Webhook\WebhookServiceInterface;
use App\Http\ApiKeyProviderInterface;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Attributes\MimeType;
use Laravel\Mcp\Server\Attributes\Name;
use Laravel\Mcp\Server\Attributes\Uri;
use Laravel\Mcp\Server\Resource;

#[Name('planka-webhooks')]
#[Description('All configured webhooks in Planka')]
#[Uri('planka://webhooks')]
#[MimeType('application/json')]
final class WebhooksResource extends Resource
{
    public function __construct(
        private readonly WebhookServiceInterface $webhookService,
        private readonly ApiKeyProviderInterface $apiKeyProvider,
    ) {}

    public function handle(Request $request): Response
    {
        try {
            $apiKey = $this->apiKeyProvider->getApiKey();
            return Response::json($this->webhookService->getWebhooks($apiKey));
        } catch (\Throwable $e) {
            return Response::error($e->getMessage());
        }
    }
}
