<?php

declare(strict_types=1);

namespace Tests\Unit\Mcp\Resources;

use App\Domain\System\SystemServiceInterface;
use App\Exception\ValidationException;
use App\Http\ApiKeyProviderInterface;
use App\Mcp\Resources\BootstrapResource;
use App\Planka\Exception\AuthenticationException;
use App\Planka\Exception\PlankaApiException;
use Laravel\Mcp\Request;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

final class BootstrapResourceTest extends TestCase
{
    private SystemServiceInterface&MockObject $systemService;
    private ApiKeyProviderInterface&MockObject $apiKeyProvider;
    private BootstrapResource $resource;

    protected function setUp(): void
    {
        $this->systemService = $this->createMock(SystemServiceInterface::class);
        $this->apiKeyProvider = $this->createMock(ApiKeyProviderInterface::class);
        $this->resource = new BootstrapResource($this->systemService, $this->apiKeyProvider);
    }

    private function makeRequest(): Request
    {
        return new Request();
    }

    public function testHandleSuccess(): void
    {
        $this->apiKeyProvider->method('getApiKey')->willReturn('test-key');
        $this->systemService->method('getBootstrap')->willReturn(['item' => ['user' => ['id' => 'user1']]]);

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
        $this->systemService->method('getBootstrap')->willThrowException(new AuthenticationException('Unauthorized'));

        $response = $this->resource->handle($this->makeRequest());
        $this->assertTrue($response->isError());
    }

    public function testHandlePlankaApiError(): void
    {
        $this->apiKeyProvider->method('getApiKey')->willReturn('test-key');
        $this->systemService->method('getBootstrap')->willThrowException(new PlankaApiException('Server error'));

        $response = $this->resource->handle($this->makeRequest());
        $this->assertTrue($response->isError());
    }
}
