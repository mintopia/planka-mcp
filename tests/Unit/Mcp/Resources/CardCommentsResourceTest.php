<?php

declare(strict_types=1);

namespace Tests\Unit\Mcp\Resources;

use App\Domain\Comment\CommentServiceInterface;
use App\Exception\ValidationException;
use App\Http\ApiKeyProviderInterface;
use App\Mcp\Resources\CardCommentsResource;
use App\Planka\Exception\AuthenticationException;
use App\Planka\Exception\PlankaApiException;
use Laravel\Mcp\Request;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

final class CardCommentsResourceTest extends TestCase
{
    private CommentServiceInterface&MockObject $commentService;
    private ApiKeyProviderInterface&MockObject $apiKeyProvider;
    private CardCommentsResource $resource;

    protected function setUp(): void
    {
        $this->commentService = $this->createMock(CommentServiceInterface::class);
        $this->apiKeyProvider = $this->createMock(ApiKeyProviderInterface::class);
        $this->resource = new CardCommentsResource($this->commentService, $this->apiKeyProvider);
    }

    private function makeRequest(array $params = []): Request
    {
        return new Request(arguments: $params);
    }

    public function testHandleSuccess(): void
    {
        $this->apiKeyProvider->method('getApiKey')->willReturn('test-key');
        $this->commentService->method('getComments')->willReturn(['items' => []]);

        $response = $this->resource->handle($this->makeRequest(['cardId' => 'card1']));
        $this->assertFalse($response->isError());
    }

    public function testHandleAuthError(): void
    {
        $this->apiKeyProvider->method('getApiKey')->willThrowException(new ValidationException('No API key'));

        $response = $this->resource->handle($this->makeRequest(['cardId' => 'card1']));
        $this->assertTrue($response->isError());
    }

    public function testHandleServiceError(): void
    {
        $this->apiKeyProvider->method('getApiKey')->willReturn('test-key');
        $this->commentService->method('getComments')->willThrowException(new AuthenticationException('Unauthorized'));

        $response = $this->resource->handle($this->makeRequest(['cardId' => 'card1']));
        $this->assertTrue($response->isError());
    }

    public function testHandlePlankaApiError(): void
    {
        $this->apiKeyProvider->method('getApiKey')->willReturn('test-key');
        $this->commentService->method('getComments')->willThrowException(new PlankaApiException('Server error'));

        $response = $this->resource->handle($this->makeRequest(['cardId' => 'card1']));
        $this->assertTrue($response->isError());
    }

    public function testUriTemplateReturnsUriTemplate(): void
    {
        $template = $this->resource->uriTemplate();
        $this->assertInstanceOf(\Laravel\Mcp\Support\UriTemplate::class, $template);
    }
}
