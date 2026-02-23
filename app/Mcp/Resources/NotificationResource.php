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
use Laravel\Mcp\Server\Contracts\HasUriTemplate;
use Laravel\Mcp\Server\Resource;
use Laravel\Mcp\Support\UriTemplate;

#[Name('planka-notification')]
#[Description('A single Planka notification with its read status and associated action')]
#[MimeType('application/json')]
final class NotificationResource extends Resource implements HasUriTemplate
{
    public function __construct(
        private readonly NotificationServiceInterface $notificationService,
        private readonly ApiKeyProviderInterface $apiKeyProvider,
    ) {}

    public function uriTemplate(): UriTemplate
    {
        return new UriTemplate('planka://notifications/{notificationId}');
    }

    public function handle(Request $request): Response
    {
        try {
            $apiKey = $this->apiKeyProvider->getApiKey();
            $notificationId = $request->get('notificationId');
            return Response::json($this->notificationService->getNotification($apiKey, $notificationId));
        } catch (\Throwable $e) {
            return Response::error($e->getMessage());
        }
    }
}
