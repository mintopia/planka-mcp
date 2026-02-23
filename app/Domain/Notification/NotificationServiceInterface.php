<?php

declare(strict_types=1);

namespace App\Domain\Notification;

interface NotificationServiceInterface
{
    /** @return array<mixed> */
    public function getNotifications(string $apiKey): array;

    /** @return array<mixed> */
    public function updateNotification(string $apiKey, string $notificationId, bool $isRead): array;

    /** @return array<mixed> */
    public function readAllNotifications(string $apiKey): array;

    /** @return array<mixed> */
    public function getNotification(string $apiKey, string $notificationId): array;
}
