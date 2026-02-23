<?php

declare(strict_types=1);

namespace Tests\Unit\Http\Controllers;

use App\Http\Controllers\PlankaWebhookController;
use App\Jobs\ProcessPlankaWebhookJob;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

/**
 * Tests for PlankaWebhookController.
 *
 * We invoke the controller directly (bypassing the HTTP kernel and session
 * middleware) to avoid the APP_KEY / encryption bootstrap issue. This keeps
 * tests fast and focused on controller logic.
 */
final class PlankaWebhookControllerTest extends TestCase
{
    private PlankaWebhookController $controller;

    protected function setUp(): void
    {
        parent::setUp();
        $this->controller = new PlankaWebhookController();
    }

    /**
     * Build a PSR-style Illuminate Request with optional JSON body and headers.
     *
     * @param array<string, mixed>  $body
     * @param array<string, string> $headers
     */
    private function makeRequest(array $body = [], array $headers = []): Request
    {
        $content = json_encode($body);
        $server  = ['CONTENT_TYPE' => 'application/json'];

        foreach ($headers as $name => $value) {
            $server['HTTP_' . strtoupper(str_replace('-', '_', $name))] = $value;
        }

        $request = Request::create('/planka-webhook', 'POST', [], [], [], $server, $content);
        $request->setJson(new \Symfony\Component\HttpFoundation\InputBag($body));

        return $request;
    }

    // -----------------------------------------------------------------------
    // Subscriptions disabled
    // -----------------------------------------------------------------------

    public function testSubscriptionsDisabledReturns404(): void
    {
        config(['subscription.enabled' => false]);

        $response = ($this->controller)($this->makeRequest(['type' => 'cardCreate']));

        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertSame(404, $response->getStatusCode());
        $this->assertSame(['error' => 'Subscriptions not enabled'], $response->getData(true));
    }

    // -----------------------------------------------------------------------
    // No secret configured
    // -----------------------------------------------------------------------

    public function testNoSecretConfiguredAcceptsRequestWithoutSignature(): void
    {
        Queue::fake();
        config([
            'subscription.enabled'        => true,
            'subscription.webhook_secret' => null,
        ]);

        $response = ($this->controller)($this->makeRequest(['type' => 'cardCreate']));

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame(['status' => 'accepted'], $response->getData(true));
    }

    // -----------------------------------------------------------------------
    // Valid HMAC signature
    // -----------------------------------------------------------------------

    public function testValidHmacSignatureAcceptsRequest(): void
    {
        Queue::fake();
        $secret  = 'my-webhook-secret';
        $body    = ['type' => 'cardUpdate'];
        $content = (string) json_encode($body);

        config([
            'subscription.enabled'        => true,
            'subscription.webhook_secret' => $secret,
        ]);

        $signature = 'sha256=' . hash_hmac('sha256', $content, $secret);

        $response = ($this->controller)($this->makeRequest($body, ['X-Webhook-Signature' => $signature]));

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame(['status' => 'accepted'], $response->getData(true));
    }

    // -----------------------------------------------------------------------
    // Missing signature header
    // -----------------------------------------------------------------------

    public function testMissingSignatureHeaderReturns401(): void
    {
        config([
            'subscription.enabled'        => true,
            'subscription.webhook_secret' => 'some-secret',
        ]);

        // No X-Webhook-Signature header
        $response = ($this->controller)($this->makeRequest(['type' => 'cardCreate']));

        $this->assertSame(401, $response->getStatusCode());
        $this->assertSame(['error' => 'Missing signature'], $response->getData(true));
    }

    // -----------------------------------------------------------------------
    // Invalid HMAC signature
    // -----------------------------------------------------------------------

    public function testInvalidHmacSignatureReturns401(): void
    {
        config([
            'subscription.enabled'        => true,
            'subscription.webhook_secret' => 'real-secret',
        ]);

        $response = ($this->controller)($this->makeRequest(
            ['type' => 'cardCreate'],
            ['X-Webhook-Signature' => 'sha256=invalid-signature']
        ));

        $this->assertSame(401, $response->getStatusCode());
        $this->assertSame(['error' => 'Invalid signature'], $response->getData(true));
    }

    // -----------------------------------------------------------------------
    // Missing event type
    // -----------------------------------------------------------------------

    public function testMissingEventTypeReturns400(): void
    {
        config([
            'subscription.enabled'        => true,
            'subscription.webhook_secret' => null,
        ]);

        // Payload with no 'type' key
        $response = ($this->controller)($this->makeRequest(['data' => ['boardId' => 'b1']]));

        $this->assertSame(400, $response->getStatusCode());
        $this->assertSame(['error' => 'Missing event type'], $response->getData(true));
    }

    // -----------------------------------------------------------------------
    // Valid request dispatches job
    // -----------------------------------------------------------------------

    public function testValidRequestDispatchesJob(): void
    {
        Queue::fake();
        config([
            'subscription.enabled'        => true,
            'subscription.webhook_secret' => null,
        ]);

        ($this->controller)($this->makeRequest(['type' => 'cardCreate', 'data' => ['boardId' => 'b1']]));

        Queue::assertPushed(ProcessPlankaWebhookJob::class);
    }

    // -----------------------------------------------------------------------
    // Valid request returns accepted
    // -----------------------------------------------------------------------

    public function testValidRequestReturnsAccepted(): void
    {
        Queue::fake();
        config([
            'subscription.enabled'        => true,
            'subscription.webhook_secret' => null,
        ]);

        $response = ($this->controller)($this->makeRequest(['type' => 'boardUpdate']));

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame(['status' => 'accepted'], $response->getData(true));
    }
}
