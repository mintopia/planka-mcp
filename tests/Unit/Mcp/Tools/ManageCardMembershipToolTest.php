<?php

declare(strict_types=1);

namespace Tests\Unit\Mcp\Tools;

use App\Domain\Card\CardServiceInterface;
use App\Exception\ValidationException;
use App\Http\ApiKeyProviderInterface;
use App\Mcp\Tools\ManageCardMembershipTool;
use Laravel\Mcp\Request;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

final class ManageCardMembershipToolTest extends TestCase
{
    private CardServiceInterface&MockObject $cardService;
    private ApiKeyProviderInterface&MockObject $apiKeyProvider;
    private ManageCardMembershipTool $tool;

    protected function setUp(): void
    {
        $this->cardService = $this->createMock(CardServiceInterface::class);
        $this->apiKeyProvider = $this->createMock(ApiKeyProviderInterface::class);
        $this->tool = new ManageCardMembershipTool($this->cardService, $this->apiKeyProvider);
    }

    private function makeRequest(array $params = []): Request
    {
        return new Request(arguments: $params);
    }

    public function testAddSuccess(): void
    {
        $this->apiKeyProvider->method('getApiKey')->willReturn('test-key');
        $this->cardService->method('addCardMember')->willReturn(['item' => ['id' => 'cm1']]);

        $response = $this->tool->handle($this->makeRequest(['action' => 'add', 'cardId' => 'c1', 'userId' => 'u1']));
        $this->assertFalse($response->isError());
    }

    public function testRemoveSuccess(): void
    {
        $this->apiKeyProvider->method('getApiKey')->willReturn('test-key');
        $this->cardService->method('removeCardMember')->willReturn([]);

        $response = $this->tool->handle($this->makeRequest(['action' => 'remove', 'cardId' => 'c1', 'userId' => 'u1']));
        $this->assertFalse($response->isError());
    }

    public function testInvalidAction(): void
    {
        $this->apiKeyProvider->method('getApiKey')->willReturn('test-key');

        $response = $this->tool->handle($this->makeRequest(['action' => 'invalid', 'cardId' => 'c1', 'userId' => 'u1']));
        $this->assertTrue($response->isError());
    }

    public function testAuthError(): void
    {
        $this->apiKeyProvider->method('getApiKey')->willThrowException(new ValidationException('No API key'));

        $response = $this->tool->handle($this->makeRequest(['action' => 'add', 'cardId' => 'c1', 'userId' => 'u1']));
        $this->assertTrue($response->isError());
    }

    public function testAddMissingCardId(): void
    {
        $this->apiKeyProvider->method('getApiKey')->willReturn('test-key');

        $response = $this->tool->handle($this->makeRequest(['action' => 'add', 'userId' => 'u1']));
        $this->assertTrue($response->isError());
    }

    public function testAddMissingUserId(): void
    {
        $this->apiKeyProvider->method('getApiKey')->willReturn('test-key');

        $response = $this->tool->handle($this->makeRequest(['action' => 'add', 'cardId' => 'c1']));
        $this->assertTrue($response->isError());
    }

    public function testRemoveMissingCardId(): void
    {
        $this->apiKeyProvider->method('getApiKey')->willReturn('test-key');

        $response = $this->tool->handle($this->makeRequest(['action' => 'remove', 'userId' => 'u1']));
        $this->assertTrue($response->isError());
    }

    public function testRemoveMissingUserId(): void
    {
        $this->apiKeyProvider->method('getApiKey')->willReturn('test-key');

        $response = $this->tool->handle($this->makeRequest(['action' => 'remove', 'cardId' => 'c1']));
        $this->assertTrue($response->isError());
    }

    public function testSchemaReturnsArray(): void
    {
        $data = $this->tool->toArray();
        $this->assertIsArray($data);
        $this->assertArrayHasKey('inputSchema', $data);
    }
}
