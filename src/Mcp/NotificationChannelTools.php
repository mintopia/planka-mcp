<?php

declare(strict_types=1);

namespace App\Mcp;

use App\Domain\NotificationChannel\NotificationChannelService;
use App\Infrastructure\Http\ApiKeyProvider;
use App\Shared\Exception\ValidationException;
use Mcp\Capability\Attribute\McpTool;
use Mcp\Capability\Attribute\Schema;
use Mcp\Exception\ToolCallException;

final class NotificationChannelTools
{
    public function __construct(
        private readonly NotificationChannelService $notificationChannelService,
        private readonly ApiKeyProvider $apiKeyProvider,
    ) {}

    /**
     * @param array<string, mixed>|null $params
     * @return array<mixed>
     */
    #[McpTool(name: 'planka_manage_notification_services', description: 'Create, update, test, or delete notification services (integrations) for users or boards.')]
    public function manageNotificationServices(
        #[Schema(description: 'Action to perform: create_for_user, create_for_board, update, test, or delete', enum: ['create_for_user', 'create_for_board', 'update', 'test', 'delete'])] string $action,
        #[Schema(description: 'User ID — required for create_for_user')] ?string $userId = null,
        #[Schema(description: 'Board ID — required for create_for_board')] ?string $boardId = null,
        #[Schema(description: 'Notification service ID — required for update, test, delete')] ?string $channelId = null,
        #[Schema(description: 'Service type (e.g. slack, telegram, email)')] ?string $type = null,
        #[Schema(description: 'Enable or disable the notification service')] ?bool $isEnabled = null,
        #[Schema(description: 'Service-specific configuration parameters')] ?array $params = null,
    ): array {
        try {
            $apiKey = $this->apiKeyProvider->getApiKey();

            return match ($action) {
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
        } catch (\Throwable $e) {
            throw new ToolCallException($e->getMessage(), $e->getCode(), $e);
        }
    }
}
