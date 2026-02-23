<?php

declare(strict_types=1);

namespace Tests\Unit\Mcp\Tools;

use App\Domain\Board\BoardServiceInterface;
use App\Exception\ValidationException;
use App\Http\ApiKeyProviderInterface;
use App\Mcp\Tools\GetBoardTool;
use App\Planka\Exception\AuthenticationException;
use Laravel\Mcp\Request;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

final class GetBoardToolTest extends TestCase
{
    private BoardServiceInterface&MockObject $boardService;
    private ApiKeyProviderInterface&MockObject $apiKeyProvider;
    private GetBoardTool $tool;

    protected function setUp(): void
    {
        $this->boardService = $this->createMock(BoardServiceInterface::class);
        $this->apiKeyProvider = $this->createMock(ApiKeyProviderInterface::class);
        $this->tool = new GetBoardTool($this->boardService, $this->apiKeyProvider);
    }

    private function makeRequest(array $params = []): Request
    {
        return new Request(arguments: $params);
    }

    public function testHandleSuccess(): void
    {
        $this->apiKeyProvider->method('getApiKey')->willReturn('test-key');
        $this->boardService->method('getBoard')->willReturn(['item' => ['id' => 'b1']]);

        $response = $this->tool->handle($this->makeRequest(['boardId' => 'b1']));
        $this->assertFalse($response->isError());
    }

    public function testHandleAuthError(): void
    {
        $this->apiKeyProvider->method('getApiKey')->willThrowException(new ValidationException('No API key'));

        $response = $this->tool->handle($this->makeRequest(['boardId' => 'b1']));
        $this->assertTrue($response->isError());
    }

    public function testHandleServiceError(): void
    {
        $this->apiKeyProvider->method('getApiKey')->willReturn('test-key');
        $this->boardService->method('getBoard')->willThrowException(new AuthenticationException('Unauthorized'));

        $response = $this->tool->handle($this->makeRequest(['boardId' => 'b1']));
        $this->assertTrue($response->isError());
    }

    public function testSchemaReturnsArray(): void
    {
        $data = $this->tool->toArray();
        $this->assertIsArray($data);
        $this->assertArrayHasKey('inputSchema', $data);
    }
}
