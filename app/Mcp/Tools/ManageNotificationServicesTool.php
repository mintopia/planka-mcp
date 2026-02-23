<?php

declare(strict_types=1);

namespace App\Mcp\Tools;

use App\Domain\NotificationChannel\NotificationChannelServiceInterface;
use App\Exception\ValidationException;
use App\Http\ApiKeyProviderInterface;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Attributes\Name;
use Laravel\Mcp\Server\Tool;

#[Name('planka_manage_notification_services')]
#[Description('Create, update, test, or delete notification services (integrations) for users or boards.')]
final class ManageNotificationServicesTool extends Tool
{
    public function __construct(
        private readonly NotificationChannelServiceInterface $notificationChannelService,
        private readonly ApiKeyProviderInterface $apiKeyProvider,
    ) {}

    public function schema(JsonSchema $schema): array
    {
        return [
            'action' => $schema->string()
                ->required()
                ->description('Action to perform: create_for_user, create_for_board, update, test, or delete')
                ->enum(['create_for_user', 'create_for_board', 'update', 'test', 'delete']),
            'userId' => $schema->string()
                ->nullable()
                ->description('User ID — required for create_for_user'),
            'boardId' => $schema->string()
                ->nullable()
                ->description('Board ID — required for create_for_board'),
            'channelId' => $schema->string()
                ->nullable()
                ->description('Notification service ID — required for update, test, delete'),
            'type' => $schema->string()
                ->nullable()
                ->description('Service type (e.g. slack, telegram, email)'),
            'isEnabled' => $schema->boolean()
                ->nullable()
                ->description('Enable or disable the notification service'),
            'params' => $schema->array()
                ->nullable()
                ->description('Service-specific configuration parameters'),
        ];
    }

    public function handle(Request $request): Response
    {
        try {
            $action = (string) $request->get('action', '');
            /** @var ?string $userId */
            $userId = $request->get('userId');
            /** @var ?string $boardId */
            $boardId = $request->get('boardId');
            /** @var ?string $channelId */
            $channelId = $request->get('channelId');
            /** @var ?string $type */
            $type = $request->get('type');
            /** @var ?bool $isEnabled */
            $isEnabled = $request->get('isEnabled');
            /** @var ?array<string, mixed> $params */
            $params = $request->get('params');
            $apiKey = $this->apiKeyProvider->getApiKey();

            $result = match ($action) {
                'create_for_user' => $this->notificationChannelService->createForUser(
                    $apiKey,
                    $userId ?? throw new ValidationException('userId required for create_for_user'),
                    $type,
                    $params,
                ),
                'create_for_board' => $this->notificationChannelService->createForBoard(
                    $apiKey,
                    $boardId ?? throw new ValidationException('boardId required for create_for_board'),
                    $type,
                    $params,
                ),
                'update' => $this->notificationChannelService->updateChannel(
                    $apiKey,
                    $channelId ?? throw new ValidationException('channelId required for update'),
                    $isEnabled,
                    $params,
                ),
                'test' => $this->notificationChannelService->testChannel(
                    $apiKey,
                    $channelId ?? throw new ValidationException('channelId required for test'),
                ),
                'delete' => $this->notificationChannelService->deleteChannel(
                    $apiKey,
                    $channelId ?? throw new ValidationException('channelId required for delete'),
                ),
                default => throw new ValidationException(sprintf('Invalid action "%s". Must be: create_for_user, create_for_board, update, test, delete', $action)),
            };

            return Response::json($result);
        } catch (\Throwable $e) {
            return Response::error($e->getMessage());
        }
    }
}
