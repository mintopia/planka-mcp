<?php

declare(strict_types=1);

namespace App\Domain\Notification;

use App\Planka\PlankaClientInterface;

final class NotificationService implements NotificationServiceInterface
{
    public function __construct(
        private readonly PlankaClientInterface $plankaClient,
    ) {}

    /** @return array<mixed> */
    public function getNotifications(string $apiKey): array
    {
        return $this->plankaClient->get($apiKey, '/api/notifications');
    }

    /** @return array<mixed> */
    public function updateNotification(string $apiKey, string $notificationId, bool $isRead): array
    {
        return $this->plankaClient->patch($apiKey, '/api/notifications/' . $notificationId, ['isRead' => $isRead]);
    }

    /** @return array<mixed> */
    public function readAllNotifications(string $apiKey): array
    {
        return $this->plankaClient->post($apiKey, '/api/notifications/read-all', []);
    }

    /** @return array<mixed> */
    public function getNotification(string $apiKey, string $notificationId): array
    {
        return $this->plankaClient->get($apiKey, '/api/notifications/' . $notificationId);
    }
}
