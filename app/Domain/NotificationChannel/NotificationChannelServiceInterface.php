<?php

declare(strict_types=1);

namespace App\Domain\NotificationChannel;

interface NotificationChannelServiceInterface
{
    /**
     * @param array<string, mixed>|null $params
     * @return array<mixed>
     */
    public function createForUser(string $apiKey, string $userId, ?string $type, ?array $params): array;

    /**
     * @param array<string, mixed>|null $params
     * @return array<mixed>
     */
    public function createForBoard(string $apiKey, string $boardId, ?string $type, ?array $params): array;

    /**
     * @param array<string, mixed>|null $params
     * @return array<mixed>
     */
    public function updateChannel(string $apiKey, string $channelId, ?bool $isEnabled, ?array $params): array;

    /** @return array<mixed> */
    public function testChannel(string $apiKey, string $channelId): array;

    /** @return array<mixed> */
    public function deleteChannel(string $apiKey, string $channelId): array;
}
