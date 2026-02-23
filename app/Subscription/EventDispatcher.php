<?php

declare(strict_types=1);

namespace App\Subscription;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;

final class EventDispatcher
{
    private const CHANNEL = 'planka.events';

    public function __construct(
        private readonly SubscriptionRegistry $registry,
    ) {}

    /**
     * Start listening on the Redis pub/sub channel.
     *
     * IMPORTANT: This method blocks indefinitely (Redis pub/sub loop).
     * It MUST be called from a dedicated long-running Artisan command
     * (e.g., `php artisan subscription:listen`) running as a separate
     * process managed by a process supervisor (e.g., Supervisor).
     *
     * NEVER call this from an Octane worker or request handler — it will
     * permanently block the worker, removing it from the request pool.
     *
     * NOTE: laravel/mcp v0.5.9 does not support resources/subscribe natively.
     * When support is added, replace the Log::info() call with the actual
     * MCP session notification push.
     */
    public function listen(): void
    {
        Redis::subscribe([self::CHANNEL], function (string $message): void {
            $this->handleEvent($message);
        });
    }

    /**
     * Handle a published Redis event.
     */
    public function handleEvent(string $message): void
    {
        try {
            /** @var array{type?: string, uris?: list<string>, timestamp?: int} $event */
            $event = json_decode($message, true, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException) {
            Log::warning('EventDispatcher: invalid JSON in planka.events', ['message' => $message]);
            return;
        }

        $uris = $event['uris'] ?? [];
        $eventType = $event['type'] ?? 'unknown';

        foreach ($uris as $uri) {
            $sessionIds = $this->registry->getSubscribers($uri);

            foreach ($sessionIds as $sessionId) {
                // TODO: Push notifications/resources/updated to the MCP session
                // This requires laravel/mcp to support resources/subscribe (not in v0.5.9).
                // When available, send: {"method": "notifications/resources/updated", "params": {"uri": $uri}}
                Log::info('EventDispatcher: would push resource update', [
                    'sessionId' => $sessionId,
                    'uri' => $uri,
                    'eventType' => $eventType,
                ]);
            }
        }
    }
}
