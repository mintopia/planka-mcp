<?php

declare(strict_types=1);

namespace App\Mcp\Tools;

use App\Domain\Notification\NotificationServiceInterface;
use App\Http\ApiKeyProviderInterface;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Attributes\Name;
use Laravel\Mcp\Server\Tool;

#[Name('planka_mark_notification_read')]
#[Description('Mark a Planka notification as read or unread')]
final class MarkNotificationReadTool extends Tool
{
    public function __construct(
        private readonly NotificationServiceInterface $notificationService,
        private readonly ApiKeyProviderInterface $apiKeyProvider,
    ) {}

    public function schema(JsonSchema $schema): array
    {
        return [
            'notificationId' => $schema->string()
                ->required()
                ->description('The notification ID to update'),
            'isRead' => $schema->boolean()
                ->description('Whether to mark the notification as read')
                ->default(true),
        ];
    }

    public function handle(Request $request): Response
    {
        try {
            $notificationId = (string) $request->get('notificationId', '');
            $isRead = (bool) $request->get('isRead', true);
            $apiKey = $this->apiKeyProvider->getApiKey();

            return Response::json($this->notificationService->updateNotification($apiKey, $notificationId, $isRead));
        } catch (\Throwable $e) {
            return Response::error($e->getMessage());
        }
    }
}
