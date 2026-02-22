<?php

declare(strict_types=1);

namespace App\Tests\Domain\Webhook;

use App\Domain\Webhook\WebhookService;
use App\Planka\Client\PlankaClientInterface;
use App\Planka\Exception\AuthenticationException;
use App\Planka\Exception\PlankaApiException;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

final class WebhookServiceTest extends TestCase
{
    private PlankaClientInterface&MockObject $plankaClient;
    private WebhookService $service;

    protected function setUp(): void
    {
        $this->plankaClient = $this->createMock(PlankaClientInterface::class);
        $this->service = new WebhookService($this->plankaClient);
    }

    // -------------------------------------------------------------------------
    // getWebhooks()
    // -------------------------------------------------------------------------

    public function testGetWebhooksSuccess(): void
    {
        $expected = ['items' => [['id' => 'wh1', 'url' => 'https://example.com/hook']]];

        $this->plankaClient
            ->expects($this->once())
            ->method('get')
            ->with('test-api-key', '/api/webhooks')
            ->willReturn($expected);

        $result = $this->service->getWebhooks('test-api-key');

        $this->assertSame($expected, $result);
    }

    public function testGetWebhooksReturnsEmptyWhenNone(): void
    {
        $this->plankaClient
            ->expects($this->once())
            ->method('get')
            ->with('test-api-key', '/api/webhooks')
            ->willReturn([]);

        $result = $this->service->getWebhooks('test-api-key');

        $this->assertSame([], $result);
    }

    public function testGetWebhooksPropagatesAuthException(): void
    {
        $this->plankaClient
            ->method('get')
            ->willThrowException(new AuthenticationException('Unauthorized'));

        $this->expectException(AuthenticationException::class);

        $this->service->getWebhooks('bad-key');
    }

    public function testGetWebhooksPropagatesApiException(): void
    {
        $this->plankaClient
            ->method('get')
            ->willThrowException(new PlankaApiException('Server error'));

        $this->expectException(PlankaApiException::class);

        $this->service->getWebhooks('test-api-key');
    }

    // -------------------------------------------------------------------------
    // createWebhook()
    // -------------------------------------------------------------------------

    public function testCreateWebhookWithDescriptionSuccess(): void
    {
        $expected = ['item' => ['id' => 'wh1', 'url' => 'https://example.com/hook']];

        $this->plankaClient
            ->expects($this->once())
            ->method('post')
            ->with(
                'test-api-key',
                '/api/webhooks',
                ['name' => 'webhook-name', 'url' => 'https://example.com/hook', 'events' => 'event1,event2', 'description' => 'My webhook'],
            )
            ->willReturn($expected);

        $result = $this->service->createWebhook('test-api-key', 'webhook-name', 'https://example.com/hook', 'event1,event2', 'My webhook');

        $this->assertSame($expected, $result);
    }

    public function testCreateWebhookWithoutDescriptionSuccess(): void
    {
        $expected = ['item' => ['id' => 'wh1']];

        $this->plankaClient
            ->expects($this->once())
            ->method('post')
            ->with(
                'test-api-key',
                '/api/webhooks',
                ['name' => 'webhook-name', 'url' => 'https://example.com/hook', 'events' => 'cardCreate'],
            )
            ->willReturn($expected);

        $result = $this->service->createWebhook('test-api-key', 'webhook-name', 'https://example.com/hook', 'cardCreate', null);

        $this->assertSame($expected, $result);
    }

    public function testCreateWebhookPropagatesAuthException(): void
    {
        $this->plankaClient
            ->method('post')
            ->willThrowException(new AuthenticationException('Unauthorized'));

        $this->expectException(AuthenticationException::class);

        $this->service->createWebhook('bad-key', 'webhook-name', 'https://example.com/hook', 'cardCreate', null);
    }

    public function testCreateWebhookPropagatesApiException(): void
    {
        $this->plankaClient
            ->method('post')
            ->willThrowException(new PlankaApiException('Server error'));

        $this->expectException(PlankaApiException::class);

        $this->service->createWebhook('test-api-key', 'webhook-name', 'https://example.com/hook', 'cardCreate', null);
    }

    // -------------------------------------------------------------------------
    // updateWebhook()
    // -------------------------------------------------------------------------

    public function testUpdateWebhookWithAllParamsSuccess(): void
    {
        $expected = ['item' => ['id' => 'wh1', 'url' => 'https://new.example.com/hook']];

        $this->plankaClient
            ->expects($this->once())
            ->method('patch')
            ->with(
                'test-api-key',
                '/api/webhooks/wh1',
                ['url' => 'https://new.example.com/hook', 'events' => 'cardDelete', 'description' => 'Updated'],
            )
            ->willReturn($expected);

        $result = $this->service->updateWebhook('test-api-key', 'wh1', 'https://new.example.com/hook', 'cardDelete', 'Updated');

        $this->assertSame($expected, $result);
    }

    public function testUpdateWebhookWithUrlOnlySuccess(): void
    {
        $expected = ['item' => ['id' => 'wh1']];

        $this->plankaClient
            ->expects($this->once())
            ->method('patch')
            ->with('test-api-key', '/api/webhooks/wh1', ['url' => 'https://new.example.com'])
            ->willReturn($expected);

        $result = $this->service->updateWebhook('test-api-key', 'wh1', 'https://new.example.com', null, null);

        $this->assertSame($expected, $result);
    }

    public function testUpdateWebhookWithEventsOnlySuccess(): void
    {
        $expected = ['item' => ['id' => 'wh1']];

        $this->plankaClient
            ->expects($this->once())
            ->method('patch')
            ->with('test-api-key', '/api/webhooks/wh1', ['events' => 'listCreate'])
            ->willReturn($expected);

        $result = $this->service->updateWebhook('test-api-key', 'wh1', null, 'listCreate', null);

        $this->assertSame($expected, $result);
    }

    public function testUpdateWebhookWithNoParamsSuccess(): void
    {
        $expected = ['item' => ['id' => 'wh1']];

        $this->plankaClient
            ->expects($this->once())
            ->method('patch')
            ->with('test-api-key', '/api/webhooks/wh1', [])
            ->willReturn($expected);

        $result = $this->service->updateWebhook('test-api-key', 'wh1', null, null, null);

        $this->assertSame($expected, $result);
    }

    public function testUpdateWebhookPropagatesAuthException(): void
    {
        $this->plankaClient
            ->method('patch')
            ->willThrowException(new AuthenticationException('Unauthorized'));

        $this->expectException(AuthenticationException::class);

        $this->service->updateWebhook('bad-key', 'wh1', null, null, null);
    }

    public function testUpdateWebhookPropagatesApiException(): void
    {
        $this->plankaClient
            ->method('patch')
            ->willThrowException(new PlankaApiException('Server error'));

        $this->expectException(PlankaApiException::class);

        $this->service->updateWebhook('test-api-key', 'wh1', null, null, null);
    }

    // -------------------------------------------------------------------------
    // deleteWebhook()
    // -------------------------------------------------------------------------

    public function testDeleteWebhookSuccess(): void
    {
        $this->plankaClient
            ->expects($this->once())
            ->method('delete')
            ->with('test-api-key', '/api/webhooks/wh1')
            ->willReturn([]);

        $result = $this->service->deleteWebhook('test-api-key', 'wh1');

        $this->assertSame([], $result);
    }

    public function testDeleteWebhookPropagatesAuthException(): void
    {
        $this->plankaClient
            ->method('delete')
            ->willThrowException(new AuthenticationException('Unauthorized'));

        $this->expectException(AuthenticationException::class);

        $this->service->deleteWebhook('bad-key', 'wh1');
    }

    public function testDeleteWebhookPropagatesApiException(): void
    {
        $this->plankaClient
            ->method('delete')
            ->willThrowException(new PlankaApiException('Server error'));

        $this->expectException(PlankaApiException::class);

        $this->service->deleteWebhook('test-api-key', 'wh1');
    }
}
