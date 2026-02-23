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

#[Name('planka_get_notifications')]
#[Description('List all notifications for the authenticated Planka user')]
final class GetNotificationsTool extends Tool
{
    public function __construct(
        private readonly NotificationServiceInterface $notificationService,
        private readonly ApiKeyProviderInterface $apiKeyProvider,
    ) {}

    public function handle(Request $request): Response
    {
        try {
            $apiKey = $this->apiKeyProvider->getApiKey();

            return Response::json($this->notificationService->getNotifications($apiKey));
        } catch (\Throwable $e) {
            return Response::error($e->getMessage());
        }
    }
}
