<?php

declare(strict_types=1);

namespace App\Mcp;

use App\Domain\Notification\NotificationService;
use App\Infrastructure\Http\ApiKeyProvider;
use Mcp\Capability\Attribute\McpTool;
use Mcp\Capability\Attribute\Schema;
use Mcp\Exception\ToolCallException;

final class NotificationTools
{
    public function __construct(
        private readonly NotificationService $notificationService,
        private readonly ApiKeyProvider $apiKeyProvider,
    ) {}

    /** @return array<mixed> */
    #[McpTool(name: 'planka_get_notifications', description: 'List all notifications for the authenticated Planka user')]
    public function getNotifications(): array
    {
        try {
            $apiKey = $this->apiKeyProvider->getApiKey();

            return $this->notificationService->getNotifications($apiKey);
        } catch (\Throwable $e) {
            throw new ToolCallException($e->getMessage(), $e->getCode(), $e);
        }
    }

    /** @return array<mixed> */
    #[McpTool(name: 'planka_mark_notification_read', description: 'Mark a Planka notification as read or unread')]
    public function markNotificationRead(
        #[Schema(description: 'The notification ID to update')] string $notificationId,
        #[Schema(description: 'Whether to mark the notification as read')] bool $isRead = true,
    ): array {
        try {
            $apiKey = $this->apiKeyProvider->getApiKey();

            return $this->notificationService->updateNotification($apiKey, $notificationId, $isRead);
        } catch (\Throwable $e) {
            throw new ToolCallException($e->getMessage(), $e->getCode(), $e);
        }
    }
}
