<?php

declare(strict_types=1);

namespace Tests\Unit\Jobs;

use App\Jobs\ProcessPlankaWebhookJob;
use App\Subscription\PlankaEventMapper;
use Illuminate\Support\Facades\Redis;
use Tests\TestCase;

/**
 * Minimal Redis stub that captures publish() calls without Mockery.
 * Mockery is not installed; all production classes are final so
 * createMock() cannot be used for them either.
 */
final class RedisPublishStub
{
    /** @var array<int, array{channel: string, message: string}> */
    public array $publishes = [];

    public function publish(string $channel, string $message): int
    {
        $this->publishes[] = ['channel' => $channel, 'message' => $message];
        return 1;
    }
}

final class ProcessPlankaWebhookJobTest extends TestCase
{
    private RedisPublishStub $redisStub;
    private PlankaEventMapper $mapper;

    protected function setUp(): void
    {
        parent::setUp();
        $this->redisStub = new RedisPublishStub();
        Redis::swap($this->redisStub);
        // Use the real PlankaEventMapper — it is a pure data transformer with no dependencies.
        $this->mapper = new PlankaEventMapper();
    }

    public function testHandlePublishesEventToRedisWhenUrisMapped(): void
    {
        // boardUpdate with a valid boardId maps to ['planka://boards/b1']
        $payload = ['type' => 'boardUpdate', 'data' => ['boardId' => 'b1']];

        $job = new ProcessPlankaWebhookJob($payload);
        $job->handle($this->mapper);

        $this->assertCount(1, $this->redisStub->publishes);
        $this->assertSame('planka.events', $this->redisStub->publishes[0]['channel']);

        $decoded = json_decode($this->redisStub->publishes[0]['message'], true);
        $this->assertSame('boardUpdate', $decoded['type']);
        $this->assertContains('planka://boards/b1', $decoded['uris']);
    }

    public function testHandleSkipsPublishWhenNoUrisMapped(): void
    {
        // An unknown event type maps to no URIs
        $payload = ['type' => 'unknownEventXyz', 'data' => ['boardId' => 'b1']];

        $job = new ProcessPlankaWebhookJob($payload);
        $job->handle($this->mapper);

        $this->assertCount(0, $this->redisStub->publishes);
    }

    public function testHandlePassesCorrectArgsToMapper(): void
    {
        // We verify the mapper received the correct event type and data
        // by confirming the published event reflects them.
        $payload = ['type' => 'boardUpdate', 'data' => ['boardId' => 'board-42']];

        $job = new ProcessPlankaWebhookJob($payload);
        $job->handle($this->mapper);

        $this->assertCount(1, $this->redisStub->publishes);

        $decoded = json_decode($this->redisStub->publishes[0]['message'], true);
        // If the mapper received 'boardUpdate' + ['boardId'=>'board-42'] it returns the board URI
        $this->assertContains('planka://boards/board-42', $decoded['uris']);
        $this->assertSame('boardUpdate', $decoded['type']);
    }

    public function testHandleEncodesEventWithTypeUrisTimestamp(): void
    {
        $payload = ['type' => 'listCreate', 'data' => ['boardId' => 'b1', 'listId' => 'l1']];

        $job = new ProcessPlankaWebhookJob($payload);
        $job->handle($this->mapper);

        $this->assertCount(1, $this->redisStub->publishes);

        $decoded = json_decode($this->redisStub->publishes[0]['message'], true);
        $this->assertArrayHasKey('type', $decoded);
        $this->assertArrayHasKey('uris', $decoded);
        $this->assertArrayHasKey('timestamp', $decoded);
        $this->assertIsInt($decoded['timestamp']);
        $this->assertSame('listCreate', $decoded['type']);
    }

    public function testHandleNormalizesNonArrayDataToEmptyArray(): void
    {
        // When 'data' is a non-array string the job must normalise to [].
        // With empty data, event type 'boardUpdate' maps to no URIs (boardId is ''),
        // so no publish should occur — the normalisation guard fires first.
        $payload = ['type' => 'boardUpdate', 'data' => 'not-an-array'];

        $job = new ProcessPlankaWebhookJob($payload);
        $job->handle($this->mapper);

        // Empty data => mapper gets [] => boardId='' => no URI => no publish
        $this->assertCount(0, $this->redisStub->publishes);
    }

    public function testJobHasCorrectTriesAndBackoff(): void
    {
        $job = new ProcessPlankaWebhookJob([]);

        $this->assertSame(3, $job->tries);
        $this->assertSame(10, $job->backoff);
    }
}
