<?php

declare(strict_types=1);

namespace App\Subscription;

use Illuminate\Support\Facades\Redis;

final class SubscriptionRegistry
{
    private const SESSION_TTL = 86400; // 24 hours

    private const KEY_PREFIX_SUBSCRIPTIONS = 'planka:subscriptions:';

    private const KEY_PREFIX_SESSION = 'planka:session:';

    /**
     * Subscribe a session to a resource URI.
     */
    public function subscribe(string $sessionId, string $uri): void
    {
        $uriKey = $this->uriKey($uri);
        $sessionKey = $this->sessionKey($sessionId);

        Redis::sadd($uriKey, $sessionId);
        Redis::sadd($sessionKey, $uri);
        Redis::expire($sessionKey, self::SESSION_TTL);
    }

    /**
     * Unsubscribe a session from a resource URI.
     */
    public function unsubscribe(string $sessionId, string $uri): void
    {
        $uriKey = $this->uriKey($uri);
        $sessionKey = $this->sessionKey($sessionId);

        Redis::srem($uriKey, $sessionId);
        Redis::srem($sessionKey, $uri);
    }

    /**
     * Remove all subscriptions for a session.
     */
    public function removeSession(string $sessionId): void
    {
        $sessionKey = $this->sessionKey($sessionId);

        /** @var list<string> $uris */
        $uris = Redis::smembers($sessionKey);

        foreach ($uris as $uri) {
            Redis::srem($this->uriKey($uri), $sessionId);
        }

        Redis::del($sessionKey);
    }

    /**
     * Get all session IDs subscribed to a URI.
     *
     * Performs lazy cleanup: session IDs whose session key has expired
     * (TTL elapsed, session ended) are removed from the subscription set
     * on read to prevent unbounded accumulation of stale entries.
     *
     * @return list<string>
     */
    public function getSubscribers(string $uri): array
    {
        $uriKey = $this->uriKey($uri);

        /** @var list<string> $members */
        $members = Redis::smembers($uriKey);

        $active = [];
        $stale = [];

        foreach ($members as $sessionId) {
            if (Redis::exists($this->sessionKey($sessionId))) {
                $active[] = $sessionId;
            } else {
                $stale[] = $sessionId;
            }
        }

        if ($stale !== []) {
            Redis::srem($uriKey, ...$stale);
        }

        return $active;
    }

    /**
     * Get all URIs a session is subscribed to.
     *
     * @return list<string>
     */
    public function getSessionUris(string $sessionId): array
    {
        /** @var list<string> $members */
        $members = Redis::smembers($this->sessionKey($sessionId));
        return $members;
    }

    /**
     * Check if a session is subscribed to a URI.
     */
    public function isSubscribed(string $sessionId, string $uri): bool
    {
        return (bool) Redis::sismember($this->uriKey($uri), $sessionId);
    }

    private function uriKey(string $uri): string
    {
        return self::KEY_PREFIX_SUBSCRIPTIONS . base64_encode($uri);
    }

    private function sessionKey(string $sessionId): string
    {
        return self::KEY_PREFIX_SESSION . $sessionId . ':uris';
    }
}
