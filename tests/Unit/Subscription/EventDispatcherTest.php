<?php

declare(strict_types=1);

namespace Tests\Unit\Subscription;

use App\Subscription\EventDispatcher;
use App\Subscription\SubscriptionRegistry;
use Illuminate\Support\Facades\Redis;
use Tests\TestCase;

/**
 * Redis stub for EventDispatcher tests.
 *
 * We need to control what getSubscribers() returns.
 * SubscriptionRegistry::getSubscribers() calls:
 *   Redis::smembers($uriKey)          — returns the raw session list
 *   Redis::exists($sessionKey)        — for stale-session cleanup (returns 1 = active)
 *
 * We map "smembers:<key>" -> list of session IDs and
 *         "exists:<key>"  -> 1 (all sessions active, no stale cleanup needed).
 */
final class EventDispatcherRedisStub
{
    /** @var array<string, mixed> */
    public array $returns = [];

    /** @var array<int, array{method: string, args: list<mixed>}> */
    public array $calls = [];

    public function smembers(string $key): array
    {
        $this->calls[] = ['method' => 'smembers', 'args' => [$key]];
        return (array) ($this->returns['smembers:' . $key] ?? []);
    }

    public function exists(string $key): int
    {
        $this->calls[] = ['method' => 'exists', 'args' => [$key]];
        return (int) ($this->returns['exists:' . $key] ?? 1);
    }

    public function srem(mixed ...$args): int
    {
        $this->calls[] = ['method' => 'srem', 'args' => array_values($args)];
        return 0;
    }

    public function sadd(mixed ...$args): int
    {
        $this->calls[] = ['method' => 'sadd', 'args' => array_values($args)];
        return 0;
    }

    public function expire(string $key, int $seconds): bool
    {
        return true;
    }

    public function del(string $key): int
    {
        return 0;
    }

    public function sismember(string $key, string $member): int
    {
        return 0;
    }

    /** @var list<string>|null */
    public ?array $subscribedChannels = null;

    /** @var callable|null */
    public $subscribedCallback = null;

    public function subscribe(array $channels, callable $callback): void
    {
        $this->calls[] = ['method' => 'subscribe', 'args' => [$channels]];
        $this->subscribedChannels = $channels;
        $this->subscribedCallback = $callback;
    }
}

final class EventDispatcherTest extends TestCase
{
    private EventDispatcherRedisStub $redisStub;
    private SubscriptionRegistry $registry;
    private EventDispatcher $dispatcher;

    protected function setUp(): void
    {
        parent::setUp();
        $this->redisStub = new EventDispatcherRedisStub();
        Redis::swap($this->redisStub);

        $this->registry   = new SubscriptionRegistry();
        $this->dispatcher = new EventDispatcher($this->registry);
    }

    /**
     * Helper: configure the Redis stub so that getSubscribers($uri) returns $sessionIds.
     *
     * SubscriptionRegistry::getSubscribers() calls smembers(uriKey) then
     * exists(sessionKey) for each member to filter stale sessions.
     *
     * @param list<string> $sessionIds
     */
    private function stubSubscribers(string $uri, array $sessionIds): void
    {
        $uriKey = 'planka:subscriptions:' . base64_encode($uri);
        $this->redisStub->returns['smembers:' . $uriKey] = $sessionIds;

        foreach ($sessionIds as $sessId) {
            $sessionKey = 'planka:session:' . $sessId . ':uris';
            $this->redisStub->returns['exists:' . $sessionKey] = 1;
        }
    }

    public function testHandleEventLogsWarningAndReturnsOnInvalidJson(): void
    {
        // On invalid JSON the dispatcher should not touch Redis at all.
        $this->dispatcher->handleEvent('not-valid-json{{{');

        $smembersCalls = array_filter(
            $this->redisStub->calls,
            fn (array $c) => $c['method'] === 'smembers'
        );

        $this->assertCount(0, $smembersCalls);
    }

    public function testHandleEventQueriesSubscribersForEachUri(): void
    {
        $uris = ['planka://boards/b1', 'planka://cards/c1'];

        foreach ($uris as $uri) {
            $this->stubSubscribers($uri, []);
        }

        $message = (string) json_encode([
            'type'      => 'cardUpdate',
            'uris'      => $uris,
            'timestamp' => time(),
        ]);

        $this->dispatcher->handleEvent($message);

        // One smembers call per URI
        $smembersCalls = array_values(array_filter(
            $this->redisStub->calls,
            fn (array $c) => $c['method'] === 'smembers'
        ));

        $this->assertCount(2, $smembersCalls);
    }

    public function testHandleEventLogsInfoForEachSubscriberSession(): void
    {
        $uri       = 'planka://boards/b1';
        $sessionId = 'sess-abc';
        $message   = (string) json_encode([
            'type'      => 'boardUpdate',
            'uris'      => [$uri],
            'timestamp' => time(),
        ]);

        $this->stubSubscribers($uri, [$sessionId]);

        // Should complete without exception; internal Log::info call is a side effect
        // we verify indirectly by confirming getSubscribers was invoked for this URI.
        $this->dispatcher->handleEvent($message);

        $uriKey        = 'planka:subscriptions:' . base64_encode($uri);
        $smembersCalls = array_values(array_filter(
            $this->redisStub->calls,
            fn (array $c) => $c['method'] === 'smembers' && $c['args'][0] === $uriKey
        ));

        $this->assertCount(1, $smembersCalls);
    }

    public function testHandleEventHandlesEmptyUrisGracefully(): void
    {
        $message = (string) json_encode([
            'type'      => 'boardUpdate',
            'uris'      => [],
            'timestamp' => time(),
        ]);

        $this->dispatcher->handleEvent($message);

        // No URIs — Redis must not be touched at all
        $this->assertCount(0, $this->redisStub->calls);
    }

    public function testHandleEventUsesUnknownTypeWhenTypeMissing(): void
    {
        $uri = 'planka://boards/b1';
        $this->stubSubscribers($uri, []);

        $message = (string) json_encode([
            // 'type' intentionally omitted
            'uris'      => [$uri],
            'timestamp' => time(),
        ]);

        // Must still process the URI — verify smembers was called
        $this->dispatcher->handleEvent($message);

        $uriKey        = 'planka:subscriptions:' . base64_encode($uri);
        $smembersCalls = array_values(array_filter(
            $this->redisStub->calls,
            fn (array $c) => $c['method'] === 'smembers' && $c['args'][0] === $uriKey
        ));

        $this->assertCount(1, $smembersCalls);
    }

    // -----------------------------------------------------------------------
    // listen()
    // -----------------------------------------------------------------------

    public function testListenSubscribesToPlankaEventsChannel(): void
    {
        $this->dispatcher->listen();

        $this->assertSame(['planka.events'], $this->redisStub->subscribedChannels);
    }

    public function testListenPassesCallbackThatForwardsToHandleEvent(): void
    {
        $this->dispatcher->listen();

        // Call the captured callback with a valid message
        $uri     = 'planka://boards/b1';
        $message = (string) json_encode(['type' => 'boardUpdate', 'uris' => [$uri], 'timestamp' => time()]);
        $this->stubSubscribers($uri, []);

        ($this->redisStub->subscribedCallback)($message);

        // Verify handleEvent was invoked by checking that smembers was called for the URI
        $uriKey        = 'planka:subscriptions:' . base64_encode($uri);
        $smembersCalls = array_values(array_filter(
            $this->redisStub->calls,
            fn (array $c) => $c['method'] === 'smembers' && $c['args'][0] === $uriKey
        ));

        $this->assertCount(1, $smembersCalls);
    }
}
