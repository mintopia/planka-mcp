<?php

declare(strict_types=1);

namespace App\Mcp\Tools;

use App\Domain\Webhook\WebhookServiceInterface;
use App\Exception\ValidationException;
use App\Http\ApiKeyProviderInterface;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Attributes\Name;
use Laravel\Mcp\Server\Tool;

#[Name('planka_manage_webhooks')]
#[Description('List, create, update, or delete webhooks.')]
final class ManageWebhooksTool extends Tool
{
    public function __construct(
        private readonly WebhookServiceInterface $webhookService,
        private readonly ApiKeyProviderInterface $apiKeyProvider,
    ) {}

    public function schema(JsonSchema $schema): array
    {
        return [
            'action' => $schema->string()
                ->required()
                ->description('Action to perform: list, create, update, or delete')
                ->enum(['list', 'create', 'update', 'delete']),
            'webhookId' => $schema->string()
                ->nullable()
                ->description('Webhook ID — required for update and delete'),
            'name' => $schema->string()
                ->nullable()
                ->description('Webhook name (required for create)'),
            'url' => $schema->string()
                ->nullable()
                ->description('Webhook callback URL — required for create'),
            'events' => $schema->string()
                ->nullable()
                ->description('Comma-separated list of events to subscribe to (e.g. "cardCreate,cardUpdate")'),
            'description' => $schema->string()
                ->nullable()
                ->description('Webhook description'),
        ];
    }

    public function handle(Request $request): Response
    {
        try {
            $action = (string) $request->get('action', '');
            /** @var ?string $webhookId */
            $webhookId = $request->get('webhookId');
            /** @var ?string $name */
            $name = $request->get('name');
            /** @var ?string $url */
            $url = $request->get('url');
            /** @var ?string $events */
            $events = $request->get('events');
            /** @var ?string $description */
            $description = $request->get('description');
            $apiKey = $this->apiKeyProvider->getApiKey();

            $result = match ($action) {
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

            return Response::json($result);
        } catch (\Throwable $e) {
            return Response::error($e->getMessage());
        }
    }
}
