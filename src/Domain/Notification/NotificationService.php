<?php

declare(strict_types=1);

namespace App\Domain\Notification;

use App\Planka\Client\PlankaClientInterface;

final class NotificationService
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
}
