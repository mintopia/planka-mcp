<?php

declare(strict_types=1);

namespace Tests\Unit\Mcp\Tools;

use App\Domain\NotificationChannel\NotificationChannelServiceInterface;
use App\Exception\ValidationException;
use App\Http\ApiKeyProviderInterface;
use App\Mcp\Tools\ManageNotificationServicesTool;
use Laravel\Mcp\Request;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

final class ManageNotificationServicesToolTest extends TestCase
{
    private NotificationChannelServiceInterface&MockObject $channelService;
    private ApiKeyProviderInterface&MockObject $apiKeyProvider;
    private ManageNotificationServicesTool $tool;

    protected function setUp(): void
    {
        $this->channelService = $this->createMock(NotificationChannelServiceInterface::class);
        $this->apiKeyProvider = $this->createMock(ApiKeyProviderInterface::class);
        $this->tool = new ManageNotificationServicesTool($this->channelService, $this->apiKeyProvider);
    }

    private function makeRequest(array $params = []): Request
    {
        return new Request(arguments: $params);
    }

    public function testCreateForUserSuccess(): void
    {
        $this->apiKeyProvider->method('getApiKey')->willReturn('test-key');
        $this->channelService->method('createForUser')->willReturn(['item' => ['id' => 'ns1']]);

        $response = $this->tool->handle($this->makeRequest(['action' => 'create_for_user', 'userId' => 'u1', 'type' => 'slack']));
        $this->assertFalse($response->isError());
    }

    public function testCreateForUserMissingUserId(): void
    {
        $this->apiKeyProvider->method('getApiKey')->willReturn('test-key');

        $response = $this->tool->handle($this->makeRequest(['action' => 'create_for_user']));
        $this->assertTrue($response->isError());
        $this->assertStringContainsString('userId required', (string) $response->content());
    }

    public function testCreateForBoardSuccess(): void
    {
        $this->apiKeyProvider->method('getApiKey')->willReturn('test-key');
        $this->channelService->method('createForBoard')->willReturn(['item' => ['id' => 'ns1']]);

        $response = $this->tool->handle($this->makeRequest(['action' => 'create_for_board', 'boardId' => 'b1']));
        $this->assertFalse($response->isError());
    }

    public function testUpdateSuccess(): void
    {
        $this->apiKeyProvider->method('getApiKey')->willReturn('test-key');
        $this->channelService->method('updateChannel')->willReturn(['item' => ['id' => 'ns1']]);

        $response = $this->tool->handle($this->makeRequest(['action' => 'update', 'channelId' => 'ns1', 'isEnabled' => true]));
        $this->assertFalse($response->isError());
    }

    public function testTestSuccess(): void
    {
        $this->apiKeyProvider->method('getApiKey')->willReturn('test-key');
        $this->channelService->method('testChannel')->willReturn(['ok' => true]);

        $response = $this->tool->handle($this->makeRequest(['action' => 'test', 'channelId' => 'ns1']));
        $this->assertFalse($response->isError());
    }

    public function testDeleteSuccess(): void
    {
        $this->apiKeyProvider->method('getApiKey')->willReturn('test-key');
        $this->channelService->method('deleteChannel')->willReturn([]);

        $response = $this->tool->handle($this->makeRequest(['action' => 'delete', 'channelId' => 'ns1']));
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

        $response = $this->tool->handle($this->makeRequest(['action' => 'create_for_user', 'userId' => 'u1']));
        $this->assertTrue($response->isError());
    }

    public function testSchemaReturnsArray(): void
    {
        $data = $this->tool->toArray();
        $this->assertIsArray($data);
        $this->assertArrayHasKey('inputSchema', $data);
    }
}
