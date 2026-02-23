<?php

declare(strict_types=1);

namespace Tests\Unit\Mcp\Resources;

use App\Domain\User\UserServiceInterface;
use App\Exception\ValidationException;
use App\Http\ApiKeyProviderInterface;
use App\Mcp\Resources\UsersResource;
use App\Planka\Exception\AuthenticationException;
use App\Planka\Exception\PlankaApiException;
use Laravel\Mcp\Request;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

final class UsersResourceTest extends TestCase
{
    private UserServiceInterface&MockObject $userService;
    private ApiKeyProviderInterface&MockObject $apiKeyProvider;
    private UsersResource $resource;

    protected function setUp(): void
    {
        $this->userService = $this->createMock(UserServiceInterface::class);
        $this->apiKeyProvider = $this->createMock(ApiKeyProviderInterface::class);
        $this->resource = new UsersResource($this->userService, $this->apiKeyProvider);
    }

    private function makeRequest(): Request
    {
        return new Request();
    }

    public function testHandleSuccess(): void
    {
        $this->apiKeyProvider->method('getApiKey')->willReturn('test-key');
        $this->userService->method('getUsers')->willReturn(['items' => []]);

        $response = $this->resource->handle($this->makeRequest());
        $this->assertFalse($response->isError());
    }

    public function testHandleAuthError(): void
    {
        $this->apiKeyProvider->method('getApiKey')->willThrowException(new ValidationException('No API key'));

        $response = $this->resource->handle($this->makeRequest());
        $this->assertTrue($response->isError());
    }

    public function testHandleServiceError(): void
    {
        $this->apiKeyProvider->method('getApiKey')->willReturn('test-key');
        $this->userService->method('getUsers')->willThrowException(new AuthenticationException('Unauthorized'));

        $response = $this->resource->handle($this->makeRequest());
        $this->assertTrue($response->isError());
    }

    public function testHandlePlankaApiError(): void
    {
        $this->apiKeyProvider->method('getApiKey')->willReturn('test-key');
        $this->userService->method('getUsers')->willThrowException(new PlankaApiException('Server error'));

        $response = $this->resource->handle($this->makeRequest());
        $this->assertTrue($response->isError());
    }
}
