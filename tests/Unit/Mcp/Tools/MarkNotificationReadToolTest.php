<?php

declare(strict_types=1);

namespace Tests\Unit\Mcp\Tools;

use App\Domain\Notification\NotificationServiceInterface;
use App\Exception\ValidationException;
use App\Http\ApiKeyProviderInterface;
use App\Mcp\Tools\MarkNotificationReadTool;
use Laravel\Mcp\Request;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

final class MarkNotificationReadToolTest extends TestCase
{
    private NotificationServiceInterface&MockObject $notificationService;
    private ApiKeyProviderInterface&MockObject $apiKeyProvider;
    private MarkNotificationReadTool $tool;

    protected function setUp(): void
    {
        $this->notificationService = $this->createMock(NotificationServiceInterface::class);
        $this->apiKeyProvider = $this->createMock(ApiKeyProviderInterface::class);
        $this->tool = new MarkNotificationReadTool($this->notificationService, $this->apiKeyProvider);
    }

    private function makeRequest(array $params = []): Request
    {
        return new Request(arguments: $params);
    }

    public function testHandleSuccess(): void
    {
        $this->apiKeyProvider->method('getApiKey')->willReturn('test-key');
        $this->notificationService->method('updateNotification')->willReturn(['item' => ['id' => 'n1']]);

        $response = $this->tool->handle($this->makeRequest(['notificationId' => 'n1', 'isRead' => true]));
        $this->assertFalse($response->isError());
    }

    public function testHandleAuthError(): void
    {
        $this->apiKeyProvider->method('getApiKey')->willThrowException(new ValidationException('No API key'));

        $response = $this->tool->handle($this->makeRequest(['notificationId' => 'n1']));
        $this->assertTrue($response->isError());
    }

    public function testSchemaReturnsArray(): void
    {
        $data = $this->tool->toArray();
        $this->assertIsArray($data);
        $this->assertArrayHasKey('inputSchema', $data);
    }
}
