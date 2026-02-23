<?php

declare(strict_types=1);

namespace Tests\Unit\Mcp\Tools;

use App\Domain\Board\BoardServiceInterface;
use App\Exception\ValidationException;
use App\Http\ApiKeyProviderInterface;
use App\Mcp\Tools\ManageBoardsTool;
use Laravel\Mcp\Request;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

final class ManageBoardsToolTest extends TestCase
{
    private BoardServiceInterface&MockObject $boardService;
    private ApiKeyProviderInterface&MockObject $apiKeyProvider;
    private ManageBoardsTool $tool;

    protected function setUp(): void
    {
        $this->boardService = $this->createMock(BoardServiceInterface::class);
        $this->apiKeyProvider = $this->createMock(ApiKeyProviderInterface::class);
        $this->tool = new ManageBoardsTool($this->boardService, $this->apiKeyProvider);
    }

    private function makeRequest(array $params = []): Request
    {
        return new Request(arguments: $params);
    }

    public function testCreateSuccess(): void
    {
        $this->apiKeyProvider->method('getApiKey')->willReturn('test-key');
        $this->boardService->method('createBoard')->willReturn(['item' => ['id' => 'b1']]);

        $response = $this->tool->handle($this->makeRequest(['action' => 'create', 'projectId' => 'p1', 'name' => 'Board']));
        $this->assertFalse($response->isError());
    }

    public function testCreateMissingProjectId(): void
    {
        $this->apiKeyProvider->method('getApiKey')->willReturn('test-key');

        $response = $this->tool->handle($this->makeRequest(['action' => 'create', 'name' => 'Board']));
        $this->assertTrue($response->isError());
        $this->assertStringContainsString('projectId required', (string) $response->content());
    }

    public function testCreateMissingName(): void
    {
        $this->apiKeyProvider->method('getApiKey')->willReturn('test-key');

        $response = $this->tool->handle($this->makeRequest(['action' => 'create', 'projectId' => 'p1']));
        $this->assertTrue($response->isError());
        $this->assertStringContainsString('name required', (string) $response->content());
    }

    public function testGetSuccess(): void
    {
        $this->apiKeyProvider->method('getApiKey')->willReturn('test-key');
        $this->boardService->method('getBoard')->willReturn(['item' => ['id' => 'b1']]);

        $response = $this->tool->handle($this->makeRequest(['action' => 'get', 'boardId' => 'b1']));
        $this->assertFalse($response->isError());
    }

    public function testUpdateSuccess(): void
    {
        $this->apiKeyProvider->method('getApiKey')->willReturn('test-key');
        $this->boardService->method('updateBoard')->willReturn(['item' => ['id' => 'b1']]);

        $response = $this->tool->handle($this->makeRequest(['action' => 'update', 'boardId' => 'b1', 'name' => 'Updated']));
        $this->assertFalse($response->isError());
    }

    public function testDeleteSuccess(): void
    {
        $this->apiKeyProvider->method('getApiKey')->willReturn('test-key');
        $this->boardService->method('deleteBoard')->willReturn([]);

        $response = $this->tool->handle($this->makeRequest(['action' => 'delete', 'boardId' => 'b1']));
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

        $response = $this->tool->handle($this->makeRequest(['action' => 'create', 'projectId' => 'p1', 'name' => 'B']));
        $this->assertTrue($response->isError());
    }

    public function testSchemaReturnsArray(): void
    {
        $data = $this->tool->toArray();
        $this->assertIsArray($data);
        $this->assertArrayHasKey('inputSchema', $data);
    }
}
