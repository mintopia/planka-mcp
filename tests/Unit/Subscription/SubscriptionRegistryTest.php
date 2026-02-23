<?php

declare(strict_types=1);

namespace Tests\Unit\Subscription;

use App\Subscription\SubscriptionRegistry;
use Illuminate\Support\Facades\Redis;
use Tests\TestCase;

/**
 * Minimal Redis stub swapped in via Redis::swap().
 * All classes in this project are final, so createMock() cannot be used.
 * Mockery is not installed, so we use a hand-written spy/stub.
 */
final class RedisStub
{
    /** @var array<int, array{method: string, args: list<mixed>}> */
    public array $calls = [];

    /** @var array<string, mixed> Preset return values. */
    public array $returns = [];

    public function sadd(mixed ...$args): int
    {
        $this->calls[] = ['method' => 'sadd', 'args' => array_values($args)];
        return 1;
    }

    public function srem(mixed ...$args): int
    {
        $this->calls[] = ['method' => 'srem', 'args' => array_values($args)];
        return 1;
    }

    public function smembers(string $key): array
    {
        $this->calls[] = ['method' => 'smembers', 'args' => [$key]];
        return (array) ($this->returns['smembers:' . $key] ?? []);
    }

    public function sismember(string $key, string $member): int
    {
        $this->calls[] = ['method' => 'sismember', 'args' => [$key, $member]];
        return (int) ($this->returns['sismember:' . $key] ?? 0);
    }

    public function exists(string $key): int
    {
        $this->calls[] = ['method' => 'exists', 'args' => [$key]];
        return (int) ($this->returns['exists:' . $key] ?? 1);
    }

    public function expire(string $key, int $seconds): bool
    {
        $this->calls[] = ['method' => 'expire', 'args' => [$key, $seconds]];
        return true;
    }

    public function del(string $key): int
    {
        $this->calls[] = ['method' => 'del', 'args' => [$key]];
        return 1;
    }

    // -----------------------------------------------------------------------
    // Helpers
    // -----------------------------------------------------------------------

    /** @return list<array{method: string, args: list<mixed>}> */
    public function callsFor(string $method): array
    {
        return array_values(
            array_filter($this->calls, fn (array $c) => $c['method'] === $method)
        );
    }

    public function wasCalledWith(string $method, mixed ...$expectedArgs): bool
    {
        foreach ($this->callsFor($method) as $call) {
            if ($call['args'] === array_values($expectedArgs)) {
                return true;
            }
        }
        return false;
    }

    public function callCount(string $method): int
    {
        return count($this->callsFor($method));
    }
}

final class SubscriptionRegistryTest extends TestCase
{
    private RedisStub $redisStub;
    private SubscriptionRegistry $registry;

    protected function setUp(): void
    {
        parent::setUp();
        $this->redisStub = new RedisStub();
        Redis::swap($this->redisStub);
        $this->registry = new SubscriptionRegistry();
    }

    // -----------------------------------------------------------------------
    // subscribe()
    // -----------------------------------------------------------------------

    public function testSubscribeCallsSaddOnUriKeyWithSessionId(): void
    {
        $uri       = 'planka://boards/b1';
        $uriKey    = 'planka:subscriptions:' . base64_encode($uri);
        $sessionId = 'sess-abc';

        $this->registry->subscribe($sessionId, $uri);

        $this->assertTrue(
            $this->redisStub->wasCalledWith('sadd', $uriKey, $sessionId),
            'Expected sadd to be called with uri key and session id'
        );
    }

    public function testSubscribeCallsSaddOnSessionKeyWithUri(): void
    {
        $uri        = 'planka://cards/c1';
        $sessionId  = 'sess-def';
        $sessionKey = 'planka:session:sess-def:uris';

        $this->registry->subscribe($sessionId, $uri);

        $this->assertTrue(
            $this->redisStub->wasCalledWith('sadd', $sessionKey, $uri),
            'Expected sadd to be called with session key and uri'
        );
    }

    public function testSubscribeSetsTtlOnSessionKey(): void
    {
        $uri        = 'planka://boards/b1';
        $sessionId  = 'sess-ghi';
        $sessionKey = 'planka:session:sess-ghi:uris';

        $this->registry->subscribe($sessionId, $uri);

        $this->assertTrue(
            $this->redisStub->wasCalledWith('expire', $sessionKey, 86400),
            'Expected expire to be called with session key and 86400 seconds TTL'
        );
    }

    // -----------------------------------------------------------------------
    // unsubscribe()
    // -----------------------------------------------------------------------

    public function testUnsubscribeCallsSremOnUriKey(): void
    {
        $uri       = 'planka://boards/b1';
        $uriKey    = 'planka:subscriptions:' . base64_encode($uri);
        $sessionId = 'sess-abc';

        $this->registry->unsubscribe($sessionId, $uri);

        $this->assertTrue(
            $this->redisStub->wasCalledWith('srem', $uriKey, $sessionId),
            'Expected srem to be called with uri key and session id'
        );
    }

    public function testUnsubscribeCallsSremOnSessionKey(): void
    {
        $uri        = 'planka://boards/b1';
        $sessionId  = 'sess-xyz';
        $sessionKey = 'planka:session:sess-xyz:uris';

        $this->registry->unsubscribe($sessionId, $uri);

        $this->assertTrue(
            $this->redisStub->wasCalledWith('srem', $sessionKey, $uri),
            'Expected srem to be called with session key and uri'
        );
    }

    // -----------------------------------------------------------------------
    // removeSession()
    // -----------------------------------------------------------------------

    public function testRemoveSessionFetchesUrisThenRemovesSessionFromEachUriSet(): void
    {
        $sessionId  = 'sess-rm';
        $sessionKey = 'planka:session:sess-rm:uris';
        $uris       = ['planka://boards/b1', 'planka://cards/c1'];

        $this->redisStub->returns['smembers:' . $sessionKey] = $uris;

        $this->registry->removeSession($sessionId);

        foreach ($uris as $uri) {
            $uriKey = 'planka:subscriptions:' . base64_encode($uri);
            $this->assertTrue(
                $this->redisStub->wasCalledWith('srem', $uriKey, $sessionId),
                "Expected srem to be called with uri key {$uriKey} and session id"
            );
        }
    }

    public function testRemoveSessionDeletesSessionKey(): void
    {
        $sessionId  = 'sess-del';
        $sessionKey = 'planka:session:sess-del:uris';

        $this->redisStub->returns['smembers:' . $sessionKey] = [];

        $this->registry->removeSession($sessionId);

        $this->assertTrue(
            $this->redisStub->wasCalledWith('del', $sessionKey),
            'Expected del to be called with session key'
        );
    }

    // -----------------------------------------------------------------------
    // getSubscribers()
    // -----------------------------------------------------------------------

    public function testGetSubscribersReturnsSmembersOfUriKey(): void
    {
        $uri        = 'planka://boards/b1';
        $uriKey     = 'planka:subscriptions:' . base64_encode($uri);
        $sessionIds = ['sess-1', 'sess-2'];

        $this->redisStub->returns['smembers:' . $uriKey] = $sessionIds;

        // Mark both sessions as active (exists returns 1 by default in stub)
        foreach ($sessionIds as $sess) {
            $this->redisStub->returns['exists:planka:session:' . $sess . ':uris'] = 1;
        }

        $result = $this->registry->getSubscribers($uri);

        $this->assertSame($sessionIds, $result);
    }

    // -----------------------------------------------------------------------
    // getSessionUris()
    // -----------------------------------------------------------------------

    public function testGetSessionUrisReturnsSmembersOfSessionKey(): void
    {
        $sessionId  = 'sess-abc';
        $sessionKey = 'planka:session:sess-abc:uris';
        $expected   = ['planka://boards/b1', 'planka://cards/c1'];

        $this->redisStub->returns['smembers:' . $sessionKey] = $expected;

        $result = $this->registry->getSessionUris($sessionId);

        $this->assertSame($expected, $result);
    }

    // -----------------------------------------------------------------------
    // isSubscribed()
    // -----------------------------------------------------------------------

    public function testIsSubscribedReturnsTrueWhenSismemberReturnsTruthy(): void
    {
        $uri       = 'planka://boards/b1';
        $uriKey    = 'planka:subscriptions:' . base64_encode($uri);
        $sessionId = 'sess-abc';

        $this->redisStub->returns['sismember:' . $uriKey] = 1;

        $this->assertTrue($this->registry->isSubscribed($sessionId, $uri));
    }

    public function testIsSubscribedReturnsFalseWhenSismemberReturnsFalsy(): void
    {
        $uri       = 'planka://boards/b1';
        $uriKey    = 'planka:subscriptions:' . base64_encode($uri);
        $sessionId = 'sess-notsubscribed';

        $this->redisStub->returns['sismember:' . $uriKey] = 0;

        $this->assertFalse($this->registry->isSubscribed($sessionId, $uri));
    }

    // -----------------------------------------------------------------------
    // Key format
    // -----------------------------------------------------------------------

    public function testUriKeyUsesBase64EncodedUri(): void
    {
        $uri    = 'planka://boards/board-with-special-chars/!@#';
        $uriKey = 'planka:subscriptions:' . base64_encode($uri);

        $this->registry->subscribe('sess-1', $uri);

        // The first sadd call must target the base64-encoded URI key
        $saddCalls = $this->redisStub->callsFor('sadd');
        $this->assertNotEmpty($saddCalls);

        $uriKeyCalls = array_filter($saddCalls, fn (array $c) => $c['args'][0] === $uriKey);
        $this->assertNotEmpty($uriKeyCalls, 'Expected sadd to be called with base64-encoded URI key');
    }

    // -----------------------------------------------------------------------
    // getSubscribers() — stale session cleanup
    // -----------------------------------------------------------------------

    public function testGetSubscribersFiltersOutStaleSessionsAndCallsSrem(): void
    {
        $uri        = 'planka://boards/b1';
        $uriKey     = 'planka:subscriptions:' . base64_encode($uri);
        $active     = 'sess-active';
        $stale      = 'sess-stale';

        $this->redisStub->returns['smembers:' . $uriKey] = [$active, $stale];
        // active session exists
        $this->redisStub->returns['exists:planka:session:' . $active . ':uris'] = 1;
        // stale session does NOT exist
        $this->redisStub->returns['exists:planka:session:' . $stale . ':uris'] = 0;

        $result = $this->registry->getSubscribers($uri);

        // Only active session returned
        $this->assertSame([$active], $result);

        // srem must be called with the stale session
        $this->assertTrue(
            $this->redisStub->wasCalledWith('srem', $uriKey, $stale),
            'Expected srem to be called to remove stale session'
        );
    }

    public function testGetSubscribersReturnsEmptyWhenAllSessionsStale(): void
    {
        $uri    = 'planka://boards/b2';
        $uriKey = 'planka:subscriptions:' . base64_encode($uri);
        $stale  = 'sess-expired';

        $this->redisStub->returns['smembers:' . $uriKey] = [$stale];
        $this->redisStub->returns['exists:planka:session:' . $stale . ':uris'] = 0;

        $result = $this->registry->getSubscribers($uri);

        $this->assertSame([], $result);
    }
}
