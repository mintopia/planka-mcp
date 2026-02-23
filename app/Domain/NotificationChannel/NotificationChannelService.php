<?php

declare(strict_types=1);

namespace App\Domain\NotificationChannel;

use App\Planka\PlankaClientInterface;

final class NotificationChannelService implements NotificationChannelServiceInterface
{
    public function __construct(
        private readonly PlankaClientInterface $plankaClient,
    ) {}

    /**
     * @param array<string, mixed>|null $params
     * @return array<mixed>
     */
    public function createForUser(string $apiKey, string $userId, ?string $type, ?array $params): array
    {
        $body = [];
        if ($type !== null) {
            $body['type'] = $type;
        }
        if ($params !== null) {
            $body['params'] = $params;
        }

        return $this->plankaClient->post($apiKey, '/api/users/' . $userId . '/notification-services', $body);
    }

    /**
     * @param array<string, mixed>|null $params
     * @return array<mixed>
     */
    public function createForBoard(string $apiKey, string $boardId, ?string $type, ?array $params): array
    {
        $body = [];
        if ($type !== null) {
            $body['type'] = $type;
        }
        if ($params !== null) {
            $body['params'] = $params;
        }

        return $this->plankaClient->post($apiKey, '/api/boards/' . $boardId . '/notification-services', $body);
    }

    /**
     * @param array<string, mixed>|null $params
     * @return array<mixed>
     */
    public function updateChannel(string $apiKey, string $channelId, ?bool $isEnabled, ?array $params): array
    {
        $body = [];
        if ($isEnabled !== null) {
            $body['isEnabled'] = $isEnabled;
        }
        if ($params !== null) {
            $body['params'] = $params;
        }

        return $this->plankaClient->patch($apiKey, '/api/notification-services/' . $channelId, $body);
    }

    /** @return array<mixed> */
    public function testChannel(string $apiKey, string $channelId): array
    {
        return $this->plankaClient->post($apiKey, '/api/notification-services/' . $channelId . '/test', []);
    }

    /** @return array<mixed> */
    public function deleteChannel(string $apiKey, string $channelId): array
    {
        return $this->plankaClient->delete($apiKey, '/api/notification-services/' . $channelId);
    }
}
