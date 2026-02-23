<?php

declare(strict_types=1);

namespace Tests\Unit\Mcp\Tools;

use App\Domain\Action\ActionServiceInterface;
use App\Exception\ValidationException;
use App\Http\ApiKeyProviderInterface;
use App\Mcp\Tools\GetActionsTool;
use Laravel\Mcp\Request;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

final class GetActionsToolTest extends TestCase
{
    private ActionServiceInterface&MockObject $actionService;
    private ApiKeyProviderInterface&MockObject $apiKeyProvider;
    private GetActionsTool $tool;

    protected function setUp(): void
    {
        $this->actionService = $this->createMock(ActionServiceInterface::class);
        $this->apiKeyProvider = $this->createMock(ApiKeyProviderInterface::class);
        $this->tool = new GetActionsTool($this->actionService, $this->apiKeyProvider);
    }

    private function makeRequest(array $params = []): Request
    {
        return new Request(arguments: $params);
    }

    public function testBoardActionsSuccess(): void
    {
        $this->apiKeyProvider->method('getApiKey')->willReturn('test-key');
        $this->actionService->method('getBoardActions')->willReturn(['items' => []]);

        $response = $this->tool->handle($this->makeRequest(['type' => 'board', 'id' => 'b1']));
        $this->assertFalse($response->isError());
    }

    public function testCardActionsSuccess(): void
    {
        $this->apiKeyProvider->method('getApiKey')->willReturn('test-key');
        $this->actionService->method('getCardActions')->willReturn(['items' => []]);

        $response = $this->tool->handle($this->makeRequest(['type' => 'card', 'id' => 'c1']));
        $this->assertFalse($response->isError());
    }

    public function testInvalidType(): void
    {
        $this->apiKeyProvider->method('getApiKey')->willReturn('test-key');

        $response = $this->tool->handle($this->makeRequest(['type' => 'invalid', 'id' => 'x1']));
        $this->assertTrue($response->isError());
        $this->assertStringContainsString('Invalid type', (string) $response->content());
    }

    public function testAuthError(): void
    {
        $this->apiKeyProvider->method('getApiKey')->willThrowException(new ValidationException('No API key'));

        $response = $this->tool->handle($this->makeRequest(['type' => 'board', 'id' => 'b1']));
        $this->assertTrue($response->isError());
    }

    public function testSchemaReturnsArray(): void
    {
        $data = $this->tool->toArray();
        $this->assertIsArray($data);
        $this->assertArrayHasKey('inputSchema', $data);
    }
}
