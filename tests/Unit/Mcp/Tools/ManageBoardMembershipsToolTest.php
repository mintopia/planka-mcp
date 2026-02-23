<?php

declare(strict_types=1);

namespace Tests\Unit\Mcp\Tools;

use App\Domain\Board\BoardServiceInterface;
use App\Exception\ValidationException;
use App\Http\ApiKeyProviderInterface;
use App\Mcp\Tools\ManageBoardMembershipsTool;
use Laravel\Mcp\Request;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

final class ManageBoardMembershipsToolTest extends TestCase
{
    private BoardServiceInterface&MockObject $boardService;
    private ApiKeyProviderInterface&MockObject $apiKeyProvider;
    private ManageBoardMembershipsTool $tool;

    protected function setUp(): void
    {
        $this->boardService = $this->createMock(BoardServiceInterface::class);
        $this->apiKeyProvider = $this->createMock(ApiKeyProviderInterface::class);
        $this->tool = new ManageBoardMembershipsTool($this->boardService, $this->apiKeyProvider);
    }

    private function makeRequest(array $params = []): Request
    {
        return new Request(arguments: $params);
    }

    public function testAddSuccess(): void
    {
        $this->apiKeyProvider->method('getApiKey')->willReturn('test-key');
        $this->boardService->method('addBoardMember')->willReturn(['item' => ['id' => 'bm1']]);

        $response = $this->tool->handle($this->makeRequest(['action' => 'add', 'boardId' => 'b1', 'userId' => 'u1']));
        $this->assertFalse($response->isError());
    }

    public function testAddMissingBoardId(): void
    {
        $this->apiKeyProvider->method('getApiKey')->willReturn('test-key');

        $response = $this->tool->handle($this->makeRequest(['action' => 'add', 'userId' => 'u1']));
        $this->assertTrue($response->isError());
        $this->assertStringContainsString('boardId required', (string) $response->content());
    }

    public function testUpdateSuccess(): void
    {
        $this->apiKeyProvider->method('getApiKey')->willReturn('test-key');
        $this->boardService->method('updateBoardMembership')->willReturn(['item' => ['id' => 'bm1']]);

        $response = $this->tool->handle($this->makeRequest(['action' => 'update', 'membershipId' => 'bm1', 'role' => 'viewer']));
        $this->assertFalse($response->isError());
    }

    public function testRemoveSuccess(): void
    {
        $this->apiKeyProvider->method('getApiKey')->willReturn('test-key');
        $this->boardService->method('removeBoardMember')->willReturn([]);

        $response = $this->tool->handle($this->makeRequest(['action' => 'remove', 'membershipId' => 'bm1']));
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

        $response = $this->tool->handle($this->makeRequest(['action' => 'add', 'boardId' => 'b1', 'userId' => 'u1']));
        $this->assertTrue($response->isError());
    }

    public function testSchemaReturnsArray(): void
    {
        $data = $this->tool->toArray();
        $this->assertIsArray($data);
        $this->assertArrayHasKey('inputSchema', $data);
    }
}
