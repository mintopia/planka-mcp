<?php

declare(strict_types=1);

namespace App\Mcp;

use App\Domain\Webhook\WebhookService;
use App\Infrastructure\Http\ApiKeyProvider;
use App\Shared\Exception\ValidationException;
use Mcp\Capability\Attribute\McpTool;
use Mcp\Capability\Attribute\Schema;
use Mcp\Exception\ToolCallException;

final class WebhookTools
{
    public function __construct(
        private readonly WebhookService $webhookService,
        private readonly ApiKeyProvider $apiKeyProvider,
    ) {}

    /** @return array<mixed> */
    #[McpTool(name: 'planka_manage_webhooks', description: 'List, create, update, or delete webhooks.')]
    public function manageWebhooks(
        #[Schema(description: 'Action to perform: list, create, update, or delete', enum: ['list', 'create', 'update', 'delete'])] string $action,
        #[Schema(description: 'Webhook ID — required for update and delete')] ?string $webhookId = null,
        #[Schema(description: 'Webhook name (required for create)')] ?string $name = null,
        #[Schema(description: 'Webhook callback URL — required for create')] ?string $url = null,
        #[Schema(description: 'Comma-separated list of events to subscribe to (e.g. "cardCreate,cardUpdate")')] ?string $events = null,
        #[Schema(description: 'Webhook description')] ?string $description = null,
    ): array {
        try {
            $apiKey = $this->apiKeyProvider->getApiKey();

            return match ($action) {
                'list' => $this->webhookService->getWebhooks($apiKey),
                'create' => $this->webhookService->createWebhook(
                    $apiKey,
                    $name ?? throw new ValidationException('name required for create'),
                    $url ?? throw new ValidationException('url required for create'),
                    $events,
                    $description,
                ),
                'update' => $this->webhookService->updateWebhook(
                    $apiKey,
                    $webhookId ?? throw new ValidationException('webhookId required for update'),
                    $url,
                    $events,
                    $description,
                ),
                'delete' => $this->webhookService->deleteWebhook(
                    $apiKey,
                    $webhookId ?? throw new ValidationException('webhookId required for delete'),
                ),
                default => throw new ValidationException(sprintf('Invalid action "%s". Must be: list, create, update, delete', $action)),
            };
        } catch (\Throwable $e) {
            throw new ToolCallException($e->getMessage(), $e->getCode(), $e);
        }
    }
}
