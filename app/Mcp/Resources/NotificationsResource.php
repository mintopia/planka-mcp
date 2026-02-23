<?php

declare(strict_types=1);

namespace App\Mcp\Resources;

use App\Domain\Notification\NotificationServiceInterface;
use App\Http\ApiKeyProviderInterface;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Attributes\MimeType;
use Laravel\Mcp\Server\Attributes\Name;
use Laravel\Mcp\Server\Attributes\Uri;
use Laravel\Mcp\Server\Resource;

#[Name('planka-notifications')]
#[Description('All notifications for the authenticated Planka user')]
#[Uri('planka://notifications')]
#[MimeType('application/json')]
final class NotificationsResource extends Resource
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
