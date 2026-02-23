<?php

declare(strict_types=1);

namespace Tests\Unit\Mcp\Tools;

use App\Domain\Webhook\WebhookServiceInterface;
use App\Exception\ValidationException;
use App\Http\ApiKeyProviderInterface;
use App\Mcp\Tools\ManageWebhooksTool;
use Laravel\Mcp\Request;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

final class ManageWebhooksToolTest extends TestCase
{
    private WebhookServiceInterface&MockObject $webhookService;
    private ApiKeyProviderInterface&MockObject $apiKeyProvider;
    private ManageWebhooksTool $tool;

    protected function setUp(): void
    {
        $this->webhookService = $this->createMock(WebhookServiceInterface::class);
        $this->apiKeyProvider = $this->createMock(ApiKeyProviderInterface::class);
        $this->tool = new ManageWebhooksTool($this->webhookService, $this->apiKeyProvider);
    }

    private function makeRequest(array $params = []): Request
    {
        return new Request(arguments: $params);
    }

    public function testListSuccess(): void
    {
        $this->apiKeyProvider->method('getApiKey')->willReturn('test-key');
        $this->webhookService->method('getWebhooks')->willReturn(['items' => []]);

        $response = $this->tool->handle($this->makeRequest(['action' => 'list']));
        $this->assertFalse($response->isError());
    }

    public function testCreateSuccess(): void
    {
        $this->apiKeyProvider->method('getApiKey')->willReturn('test-key');
        $this->webhookService->method('createWebhook')->willReturn(['item' => ['id' => 'wh1']]);

        $response = $this->tool->handle($this->makeRequest([
            'action' => 'create',
            'name' => 'Hook',
            'url' => 'https://example.com/hook',
        ]));
        $this->assertFalse($response->isError());
    }

    public function testCreateMissingName(): void
    {
        $this->apiKeyProvider->method('getApiKey')->willReturn('test-key');

        $response = $this->tool->handle($this->makeRequest([
            'action' => 'create',
            'url' => 'https://example.com/hook',
        ]));
        $this->assertTrue($response->isError());
        $this->assertStringContainsString('name required', (string) $response->content());
    }

    public function testCreateMissingUrl(): void
    {
        $this->apiKeyProvider->method('getApiKey')->willReturn('test-key');

        $response = $this->tool->handle($this->makeRequest([
            'action' => 'create',
            'name' => 'Hook',
        ]));
        $this->assertTrue($response->isError());
        $this->assertStringContainsString('url required', (string) $response->content());
    }

    public function testUpdateSuccess(): void
    {
        $this->apiKeyProvider->method('getApiKey')->willReturn('test-key');
        $this->webhookService->method('updateWebhook')->willReturn(['item' => ['id' => 'wh1']]);

        $response = $this->tool->handle($this->makeRequest(['action' => 'update', 'webhookId' => 'wh1', 'url' => 'https://new.url']));
        $this->assertFalse($response->isError());
    }

    public function testUpdateMissingWebhookId(): void
    {
        $this->apiKeyProvider->method('getApiKey')->willReturn('test-key');

        $response = $this->tool->handle($this->makeRequest(['action' => 'update']));
        $this->assertTrue($response->isError());
        $this->assertStringContainsString('webhookId required', (string) $response->content());
    }

    public function testDeleteSuccess(): void
    {
        $this->apiKeyProvider->method('getApiKey')->willReturn('test-key');
        $this->webhookService->method('deleteWebhook')->willReturn([]);

        $response = $this->tool->handle($this->makeRequest(['action' => 'delete', 'webhookId' => 'wh1']));
        $this->assertFalse($response->isError());
    }

    public function testInvalidAction(): void
    {
        $this->apiKeyProvider->method('getApiKey')->willReturn('test-key');

        $response = $this->tool->handle($this->makeRequest(['action' => 'invalid']));
        $this->assertTrue($response->isError());
    }

    public function testAuthError(): void
    {
        $this->apiKeyProvider->method('getApiKey')->willThrowException(new ValidationException('No API key'));

        $response = $this->tool->handle($this->makeRequest(['action' => 'list']));
        $this->assertTrue($response->isError());
    }

    public function testSchemaReturnsArray(): void
    {
        $data = $this->tool->toArray();
        $this->assertIsArray($data);
        $this->assertArrayHasKey('inputSchema', $data);
    }
}
