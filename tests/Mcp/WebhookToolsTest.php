<?php

declare(strict_types=1);

namespace App\Tests\Mcp;

use App\Domain\Webhook\WebhookService;
use App\Infrastructure\Http\ApiKeyProvider;
use App\Mcp\WebhookTools;
use App\Planka\Exception\AuthenticationException;
use App\Planka\Exception\PlankaApiException;
use App\Planka\Exception\PlankaNotFoundException;
use App\Shared\Exception\ValidationException;
use Mcp\Exception\ToolCallException;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

final class WebhookToolsTest extends TestCase
{
    private WebhookService&MockObject $webhookService;
    private ApiKeyProvider&MockObject $apiKeyProvider;
    private WebhookTools $tools;

    protected function setUp(): void
    {
        $this->webhookService = $this->createMock(WebhookService::class);
        $this->apiKeyProvider = $this->createMock(ApiKeyProvider::class);
        $this->tools = new WebhookTools($this->webhookService, $this->apiKeyProvider);
    }

    // -------------------------------------------------------------------------
    // manageWebhooks: list
    // -------------------------------------------------------------------------

    public function testManageWebhooksListSuccess(): void
    {
        $expected = ['items' => [['id' => 'wh1', 'url' => 'https://example.com/hook']]];

        $this->apiKeyProvider
            ->expects($this->once())
            ->method('getApiKey')
            ->willReturn('test-api-key');

        $this->webhookService
            ->expects($this->once())
            ->method('getWebhooks')
            ->with('test-api-key')
            ->willReturn($expected);

        $result = $this->tools->manageWebhooks('list');

        $this->assertSame($expected, $result);
    }

    // -------------------------------------------------------------------------
    // manageWebhooks: create
    // -------------------------------------------------------------------------

    public function testManageWebhooksCreateSuccess(): void
    {
        $expected = ['item' => ['id' => 'wh1']];

        $this->apiKeyProvider->method('getApiKey')->willReturn('test-api-key');

        $this->webhookService
            ->expects($this->once())
            ->method('createWebhook')
            ->with('test-api-key', 'Test Webhook', 'https://example.com/hook', 'cardCreate', 'My webhook')
            ->willReturn($expected);

        $result = $this->tools->manageWebhooks('create', name: 'Test Webhook', url: 'https://example.com/hook', events: 'cardCreate', description: 'My webhook');

        $this->assertSame($expected, $result);
    }

    public function testManageWebhooksCreateMissingUrlThrowsToolCallException(): void
    {
        $this->apiKeyProvider->method('getApiKey')->willReturn('test-api-key');

        $this->expectException(ToolCallException::class);
        $this->expectExceptionMessage('url required for create');

        $this->tools->manageWebhooks('create', name: 'Test', events: 'cardCreate');
    }

    public function testManageWebhooksCreateMissingNameThrowsToolCallException(): void
    {
        $this->apiKeyProvider->method('getApiKey')->willReturn('test-api-key');

        $this->expectException(ToolCallException::class);
        $this->expectExceptionMessage('name required for create');

        $this->tools->manageWebhooks('create', url: 'https://example.com/hook');
    }

    // -------------------------------------------------------------------------
    // manageWebhooks: update
    // -------------------------------------------------------------------------

    public function testManageWebhooksUpdateSuccess(): void
    {
        $expected = ['item' => ['id' => 'wh1']];

        $this->apiKeyProvider->method('getApiKey')->willReturn('test-api-key');

        $this->webhookService
            ->expects($this->once())
            ->method('updateWebhook')
            ->with('test-api-key', 'wh1', 'https://new.example.com', null, null)
            ->willReturn($expected);

        $result = $this->tools->manageWebhooks('update', webhookId: 'wh1', url: 'https://new.example.com');

        $this->assertSame($expected, $result);
    }

    public function testManageWebhooksUpdateMissingWebhookIdThrowsToolCallException(): void
    {
        $this->apiKeyProvider->method('getApiKey')->willReturn('test-api-key');

        $this->expectException(ToolCallException::class);
        $this->expectExceptionMessage('webhookId required for update');

        $this->tools->manageWebhooks('update', url: 'https://new.example.com');
    }

    // -------------------------------------------------------------------------
    // manageWebhooks: delete
    // -------------------------------------------------------------------------

    public function testManageWebhooksDeleteSuccess(): void
    {
        $this->apiKeyProvider->method('getApiKey')->willReturn('test-api-key');

        $this->webhookService
            ->expects($this->once())
            ->method('deleteWebhook')
            ->with('test-api-key', 'wh1')
            ->willReturn([]);

        $result = $this->tools->manageWebhooks('delete', webhookId: 'wh1');

        $this->assertSame([], $result);
    }

    public function testManageWebhooksDeleteMissingWebhookIdThrowsToolCallException(): void
    {
        $this->apiKeyProvider->method('getApiKey')->willReturn('test-api-key');

        $this->expectException(ToolCallException::class);
        $this->expectExceptionMessage('webhookId required for delete');

        $this->tools->manageWebhooks('delete');
    }

    // -------------------------------------------------------------------------
    // manageWebhooks: invalid action
    // -------------------------------------------------------------------------

    public function testManageWebhooksInvalidActionThrowsToolCallException(): void
    {
        $this->apiKeyProvider->method('getApiKey')->willReturn('test-api-key');

        $this->expectException(ToolCallException::class);
        $this->expectExceptionMessage('Invalid action "foobar". Must be: list, create, update, delete');

        $this->tools->manageWebhooks('foobar');
    }

    // -------------------------------------------------------------------------
    // manageWebhooks: exception wrapping
    // -------------------------------------------------------------------------

    public function testManageWebhooksMissingApiKeyThrowsToolCallException(): void
    {
        $this->apiKeyProvider
            ->method('getApiKey')
            ->willThrowException(new ValidationException('Planka API key required.'));

        $this->expectException(ToolCallException::class);
        $this->expectExceptionMessage('Planka API key required.');

        $this->tools->manageWebhooks('list');
    }

    public function testManageWebhooksWrapsAuthExceptionInToolCallException(): void
    {
        $this->apiKeyProvider->method('getApiKey')->willReturn('bad-key');

        $this->webhookService
            ->method('getWebhooks')
            ->willThrowException(new AuthenticationException('Unauthorized'));

        $this->expectException(ToolCallException::class);
        $this->expectExceptionMessage('Unauthorized');

        $this->tools->manageWebhooks('list');
    }

    public function testManageWebhooksWrapsPlankaApiExceptionInToolCallException(): void
    {
        $this->apiKeyProvider->method('getApiKey')->willReturn('test-api-key');

        $this->webhookService
            ->method('deleteWebhook')
            ->willThrowException(new PlankaApiException('Server error'));

        $this->expectException(ToolCallException::class);
        $this->expectExceptionMessage('Server error');

        $this->tools->manageWebhooks('delete', webhookId: 'wh1');
    }

    public function testManageWebhooksWrapsNotFoundExceptionInToolCallException(): void
    {
        $this->apiKeyProvider->method('getApiKey')->willReturn('test-api-key');

        $this->webhookService
            ->method('deleteWebhook')
            ->willThrowException(new PlankaNotFoundException('Not found', 404));

        $this->expectException(ToolCallException::class);
        $this->expectExceptionMessage('Not found');

        $this->tools->manageWebhooks('delete', webhookId: 'wh1');
    }
}
