<?php

declare(strict_types=1);

namespace Tests\Unit\Mcp\Resources;

use App\Domain\System\SystemServiceInterface;
use App\Exception\ValidationException;
use App\Http\ApiKeyProviderInterface;
use App\Mcp\Resources\ConfigResource;
use App\Planka\Exception\AuthenticationException;
use App\Planka\Exception\PlankaApiException;
use Laravel\Mcp\Request;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

final class ConfigResourceTest extends TestCase
{
    private SystemServiceInterface&MockObject $systemService;
    private ApiKeyProviderInterface&MockObject $apiKeyProvider;
    private ConfigResource $resource;

    protected function setUp(): void
    {
        $this->systemService = $this->createMock(SystemServiceInterface::class);
        $this->apiKeyProvider = $this->createMock(ApiKeyProviderInterface::class);
        $this->resource = new ConfigResource($this->systemService, $this->apiKeyProvider);
    }

    private function makeRequest(): Request
    {
        return new Request();
    }

    public function testHandleSuccess(): void
    {
        $this->apiKeyProvider->method('getApiKey')->willReturn('test-key');
        $this->systemService->method('getConfig')->willReturn(['item' => ['version' => '2.0.0']]);

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
        $this->systemService->method('getConfig')->willThrowException(new AuthenticationException('Unauthorized'));

        $response = $this->resource->handle($this->makeRequest());
        $this->assertTrue($response->isError());
    }

    public function testHandlePlankaApiError(): void
    {
        $this->apiKeyProvider->method('getApiKey')->willReturn('test-key');
        $this->systemService->method('getConfig')->willThrowException(new PlankaApiException('Server error'));

        $response = $this->resource->handle($this->makeRequest());
        $this->assertTrue($response->isError());
    }
}
