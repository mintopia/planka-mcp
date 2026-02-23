<?php

declare(strict_types=1);

namespace Tests\Unit\Mcp\Resources;

use App\Domain\Board\BoardServiceInterface;
use App\Exception\ValidationException;
use App\Http\ApiKeyProviderInterface;
use App\Mcp\Resources\BoardResource;
use App\Planka\Exception\AuthenticationException;
use App\Planka\Exception\PlankaApiException;
use Laravel\Mcp\Request;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

final class BoardResourceTest extends TestCase
{
    private BoardServiceInterface&MockObject $boardService;
    private ApiKeyProviderInterface&MockObject $apiKeyProvider;
    private BoardResource $resource;

    protected function setUp(): void
    {
        $this->boardService = $this->createMock(BoardServiceInterface::class);
        $this->apiKeyProvider = $this->createMock(ApiKeyProviderInterface::class);
        $this->resource = new BoardResource($this->boardService, $this->apiKeyProvider);
    }

    private function makeRequest(array $params = []): Request
    {
        return new Request(arguments: $params);
    }

    public function testHandleSuccess(): void
    {
        $this->apiKeyProvider->method('getApiKey')->willReturn('test-key');
        $this->boardService->method('getBoard')->willReturn(['item' => ['id' => 'board1', 'name' => 'Board']]);

        $response = $this->resource->handle($this->makeRequest(['boardId' => 'board1']));
        $this->assertFalse($response->isError());
    }

    public function testHandleAuthError(): void
    {
        $this->apiKeyProvider->method('getApiKey')->willThrowException(new ValidationException('No API key'));

        $response = $this->resource->handle($this->makeRequest(['boardId' => 'board1']));
        $this->assertTrue($response->isError());
    }

    public function testHandleServiceError(): void
    {
        $this->apiKeyProvider->method('getApiKey')->willReturn('test-key');
        $this->boardService->method('getBoard')->willThrowException(new AuthenticationException('Unauthorized'));

        $response = $this->resource->handle($this->makeRequest(['boardId' => 'board1']));
        $this->assertTrue($response->isError());
    }

    public function testHandlePlankaApiError(): void
    {
        $this->apiKeyProvider->method('getApiKey')->willReturn('test-key');
        $this->boardService->method('getBoard')->willThrowException(new PlankaApiException('Server error'));

        $response = $this->resource->handle($this->makeRequest(['boardId' => 'board1']));
        $this->assertTrue($response->isError());
    }

    public function testUriTemplateReturnsUriTemplate(): void
    {
        $template = $this->resource->uriTemplate();
        $this->assertInstanceOf(\Laravel\Mcp\Support\UriTemplate::class, $template);
    }
}
