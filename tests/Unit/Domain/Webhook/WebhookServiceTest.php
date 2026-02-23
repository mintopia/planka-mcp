<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\Webhook;

use App\Domain\Webhook\WebhookService;
use App\Planka\Exception\AuthenticationException;
use App\Planka\Exception\PlankaApiException;
use App\Planka\PlankaClientInterface;
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

    public function testGetWebhooksSuccess(): void
    {
        $expected = ['items' => [['id' => 'wh1', 'name' => 'My Webhook']]];

        $this->plankaClient
            ->expects($this->once())
            ->method('get')
            ->with('test-api-key', '/api/webhooks')
            ->willReturn($expected);

        $this->assertSame($expected, $this->service->getWebhooks('test-api-key'));
    }

    public function testGetWebhooksPropagatesAuthException(): void
    {
        $this->plankaClient->method('get')->willThrowException(new AuthenticationException('Unauthorized'));
        $this->expectException(AuthenticationException::class);
        $this->service->getWebhooks('bad-key');
    }

    public function testGetWebhooksPropagatesApiException(): void
    {
        $this->plankaClient->method('get')->willThrowException(new PlankaApiException('Server error'));
        $this->expectException(PlankaApiException::class);
        $this->service->getWebhooks('test-api-key');
    }

    public function testCreateWebhookSuccess(): void
    {
        $expected = ['item' => ['id' => 'wh1', 'name' => 'Hook', 'url' => 'https://example.com/hook']];

        $this->plankaClient
            ->expects($this->once())
            ->method('post')
            ->with('test-api-key', '/api/webhooks', ['name' => 'Hook', 'url' => 'https://example.com/hook'])
            ->willReturn($expected);

        $this->assertSame($expected, $this->service->createWebhook('test-api-key', 'Hook', 'https://example.com/hook'));
    }

    public function testCreateWebhookWithEventsAndDescription(): void
    {
        $expected = ['item' => ['id' => 'wh1']];

        $this->plankaClient
            ->expects($this->once())
            ->method('post')
            ->with('test-api-key', '/api/webhooks', [
                'name' => 'Hook',
                'url' => 'https://example.com/hook',
                'events' => 'cardCreate,cardUpdate',
                'description' => 'My webhook',
            ])
            ->willReturn($expected);

        $this->assertSame($expected, $this->service->createWebhook('test-api-key', 'Hook', 'https://example.com/hook', 'cardCreate,cardUpdate', 'My webhook'));
    }

    public function testCreateWebhookPropagatesAuthException(): void
    {
        $this->plankaClient->method('post')->willThrowException(new AuthenticationException('Unauthorized'));
        $this->expectException(AuthenticationException::class);
        $this->service->createWebhook('bad-key', 'Hook', 'https://example.com/hook');
    }

    public function testCreateWebhookPropagatesApiException(): void
    {
        $this->plankaClient->method('post')->willThrowException(new PlankaApiException('Server error'));
        $this->expectException(PlankaApiException::class);
        $this->service->createWebhook('test-api-key', 'Hook', 'https://example.com/hook');
    }

    public function testUpdateWebhookWithAllFields(): void
    {
        $expected = ['item' => ['id' => 'wh1']];

        $this->plankaClient
            ->expects($this->once())
            ->method('patch')
            ->with('test-api-key', '/api/webhooks/wh1', [
                'url' => 'https://new.url',
                'events' => 'cardCreate',
                'description' => 'Updated',
            ])
            ->willReturn($expected);

        $this->assertSame($expected, $this->service->updateWebhook('test-api-key', 'wh1', 'https://new.url', 'cardCreate', 'Updated'));
    }

    public function testUpdateWebhookWithNullsSendsEmptyBody(): void
    {
        $expected = ['item' => ['id' => 'wh1']];

        $this->plankaClient
            ->expects($this->once())
            ->method('patch')
            ->with('test-api-key', '/api/webhooks/wh1', [])
            ->willReturn($expected);

        $this->assertSame($expected, $this->service->updateWebhook('test-api-key', 'wh1', null, null, null));
    }

    public function testUpdateWebhookPropagatesAuthException(): void
    {
        $this->plankaClient->method('patch')->willThrowException(new AuthenticationException('Unauthorized'));
        $this->expectException(AuthenticationException::class);
        $this->service->updateWebhook('bad-key', 'wh1', 'https://url', null, null);
    }

    public function testUpdateWebhookPropagatesApiException(): void
    {
        $this->plankaClient->method('patch')->willThrowException(new PlankaApiException('Server error'));
        $this->expectException(PlankaApiException::class);
        $this->service->updateWebhook('test-api-key', 'wh1', 'https://url', null, null);
    }

    public function testDeleteWebhookSuccess(): void
    {
        $this->plankaClient
            ->expects($this->once())
            ->method('delete')
            ->with('test-api-key', '/api/webhooks/wh1')
            ->willReturn([]);

        $this->assertSame([], $this->service->deleteWebhook('test-api-key', 'wh1'));
    }

    public function testDeleteWebhookPropagatesAuthException(): void
    {
        $this->plankaClient->method('delete')->willThrowException(new AuthenticationException('Unauthorized'));
        $this->expectException(AuthenticationException::class);
        $this->service->deleteWebhook('bad-key', 'wh1');
    }

    public function testDeleteWebhookPropagatesApiException(): void
    {
        $this->plankaClient->method('delete')->willThrowException(new PlankaApiException('Server error'));
        $this->expectException(PlankaApiException::class);
        $this->service->deleteWebhook('test-api-key', 'wh1');
    }
}
