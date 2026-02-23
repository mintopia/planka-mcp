<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Jobs\ProcessPlankaWebhookJob;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

final class PlankaWebhookController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        if (! config('subscription.enabled', false)) {
            return response()->json(['error' => 'Subscriptions not enabled'], 404);
        }

        $secret = config('subscription.webhook_secret');

        if ($secret !== null && $secret !== '') {
            $signature = $request->header('X-Webhook-Signature');

            if ($signature === null) {
                return response()->json(['error' => 'Missing signature'], 401);
            }

            $expected = 'sha256=' . hash_hmac('sha256', $request->getContent(), $secret);

            if (! hash_equals($expected, $signature)) {
                Log::warning('Planka webhook signature mismatch', [
                    'remote_addr' => $request->ip(),
                ]);
                return response()->json(['error' => 'Invalid signature'], 401);
            }
        }

        /** @var array<string, mixed> $payload */
        $payload = $request->json()->all();

        if (empty($payload['type'])) {
            return response()->json(['error' => 'Missing event type'], 400);
        }

        ProcessPlankaWebhookJob::dispatch($payload);

        return response()->json(['status' => 'accepted']);
    }
}
