<?php

declare(strict_types=1);

namespace Tests\Unit\Mcp\Resources;

use App\Domain\User\UserServiceInterface;
use App\Exception\ValidationException;
use App\Http\ApiKeyProviderInterface;
use App\Mcp\Resources\UserResource;
use App\Planka\Exception\AuthenticationException;
use App\Planka\Exception\PlankaApiException;
use Laravel\Mcp\Request;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

final class UserResourceTest extends TestCase
{
    private UserServiceInterface&MockObject $userService;
    private ApiKeyProviderInterface&MockObject $apiKeyProvider;
    private UserResource $resource;

    protected function setUp(): void
    {
        $this->userService = $this->createMock(UserServiceInterface::class);
        $this->apiKeyProvider = $this->createMock(ApiKeyProviderInterface::class);
        $this->resource = new UserResource($this->userService, $this->apiKeyProvider);
    }

    private function makeRequest(array $params = []): Request
    {
        return new Request(arguments: $params);
    }

    public function testHandleSuccess(): void
    {
        $this->apiKeyProvider->method('getApiKey')->willReturn('test-key');
        $this->userService->method('getUser')->willReturn(['item' => ['id' => 'user1']]);

        $response = $this->resource->handle($this->makeRequest(['userId' => 'user1']));
        $this->assertFalse($response->isError());
    }

    public function testHandleAuthError(): void
    {
        $this->apiKeyProvider->method('getApiKey')->willThrowException(new ValidationException('No API key'));

        $response = $this->resource->handle($this->makeRequest(['userId' => 'user1']));
        $this->assertTrue($response->isError());
    }

    public function testHandleServiceError(): void
    {
        $this->apiKeyProvider->method('getApiKey')->willReturn('test-key');
        $this->userService->method('getUser')->willThrowException(new AuthenticationException('Unauthorized'));

        $response = $this->resource->handle($this->makeRequest(['userId' => 'user1']));
        $this->assertTrue($response->isError());
    }

    public function testHandlePlankaApiError(): void
    {
        $this->apiKeyProvider->method('getApiKey')->willReturn('test-key');
        $this->userService->method('getUser')->willThrowException(new PlankaApiException('Server error'));

        $response = $this->resource->handle($this->makeRequest(['userId' => 'user1']));
        $this->assertTrue($response->isError());
    }

    public function testUriTemplateReturnsUriTemplate(): void
    {
        $template = $this->resource->uriTemplate();
        $this->assertInstanceOf(\Laravel\Mcp\Support\UriTemplate::class, $template);
    }
}
