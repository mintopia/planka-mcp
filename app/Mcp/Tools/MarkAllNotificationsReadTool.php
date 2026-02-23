<?php

declare(strict_types=1);

namespace App\Mcp\Tools;

use App\Domain\Notification\NotificationServiceInterface;
use App\Http\ApiKeyProviderInterface;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Attributes\Name;
use Laravel\Mcp\Server\Tool;

#[Name('planka_mark_all_notifications_read')]
#[Description('Mark all notifications as read.')]
final class MarkAllNotificationsReadTool extends Tool
{
    public function __construct(
        private readonly NotificationServiceInterface $notificationService,
        private readonly ApiKeyProviderInterface $apiKeyProvider,
    ) {}

    public function handle(Request $request): Response
    {
        try {
            $apiKey = $this->apiKeyProvider->getApiKey();

            return Response::json($this->notificationService->readAllNotifications($apiKey));
        } catch (\Throwable $e) {
            return Response::error($e->getMessage());
        }
    }
}
