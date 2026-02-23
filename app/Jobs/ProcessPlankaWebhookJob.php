<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Subscription\PlankaEventMapper;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;

final class ProcessPlankaWebhookJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $tries = 3;

    public int $backoff = 10;

    /**
     * @param array<string, mixed> $payload
     */
    public function __construct(
        private readonly array $payload,
    ) {}

    public function handle(PlankaEventMapper $mapper): void
    {
        $eventType = (string) ($this->payload['type'] ?? '');
        $data = $this->payload['data'] ?? [];

        if (! is_array($data)) {
            $data = [];
        }

        $uris = $mapper->mapToUris($eventType, $data);

        if ($uris === []) {
            Log::debug('Planka webhook event has no mapped URIs', ['type' => $eventType]);
            return;
        }

        $event = json_encode([
            'type' => $eventType,
            'uris' => $uris,
            'timestamp' => time(),
        ], JSON_THROW_ON_ERROR);

        Redis::publish('planka.events', $event);

        Log::debug('Planka webhook event published', [
            'type' => $eventType,
            'uris' => $uris,
        ]);
    }
}
