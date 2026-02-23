<?php

declare(strict_types=1);

namespace Tests\Unit\Mcp\Tools;

use App\Domain\Comment\CommentServiceInterface;
use App\Exception\ValidationException;
use App\Http\ApiKeyProviderInterface;
use App\Mcp\Tools\AddCommentTool;
use Laravel\Mcp\Request;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

final class AddCommentToolTest extends TestCase
{
    private CommentServiceInterface&MockObject $commentService;
    private ApiKeyProviderInterface&MockObject $apiKeyProvider;
    private AddCommentTool $tool;

    protected function setUp(): void
    {
        $this->commentService = $this->createMock(CommentServiceInterface::class);
        $this->apiKeyProvider = $this->createMock(ApiKeyProviderInterface::class);
        $this->tool = new AddCommentTool($this->commentService, $this->apiKeyProvider);
    }

    private function makeRequest(array $params = []): Request
    {
        return new Request(arguments: $params);
    }

    public function testHandleSuccess(): void
    {
        $this->apiKeyProvider->method('getApiKey')->willReturn('test-key');
        $this->commentService->method('addComment')->willReturn(['item' => ['id' => 'cm1']]);

        $response = $this->tool->handle($this->makeRequest(['cardId' => 'c1', 'text' => 'Hello']));
        $this->assertFalse($response->isError());
    }

    public function testHandleEmptyText(): void
    {
        $response = $this->tool->handle($this->makeRequest(['cardId' => 'c1', 'text' => '']));
        $this->assertTrue($response->isError());
        $this->assertStringContainsString('cannot be empty', (string) $response->content());
    }

    public function testHandleAuthError(): void
    {
        $this->apiKeyProvider->method('getApiKey')->willThrowException(new ValidationException('No API key'));

        $response = $this->tool->handle($this->makeRequest(['cardId' => 'c1', 'text' => 'Hello']));
        $this->assertTrue($response->isError());
    }

    public function testSchemaReturnsArray(): void
    {
        $data = $this->tool->toArray();
        $this->assertIsArray($data);
        $this->assertArrayHasKey('inputSchema', $data);
    }
}
