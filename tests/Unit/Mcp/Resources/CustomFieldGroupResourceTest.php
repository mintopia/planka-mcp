<?php

declare(strict_types=1);

namespace Tests\Unit\Mcp\Resources;

use App\Domain\CustomField\CustomFieldServiceInterface;
use App\Exception\ValidationException;
use App\Http\ApiKeyProviderInterface;
use App\Mcp\Resources\CustomFieldGroupResource;
use App\Planka\Exception\AuthenticationException;
use App\Planka\Exception\PlankaApiException;
use Laravel\Mcp\Request;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

final class CustomFieldGroupResourceTest extends TestCase
{
    private CustomFieldServiceInterface&MockObject $customFieldService;
    private ApiKeyProviderInterface&MockObject $apiKeyProvider;
    private CustomFieldGroupResource $resource;

    protected function setUp(): void
    {
        $this->customFieldService = $this->createMock(CustomFieldServiceInterface::class);
        $this->apiKeyProvider = $this->createMock(ApiKeyProviderInterface::class);
        $this->resource = new CustomFieldGroupResource($this->customFieldService, $this->apiKeyProvider);
    }

    private function makeRequest(array $params = []): Request
    {
        return new Request(arguments: $params);
    }

    public function testHandleSuccess(): void
    {
        $this->apiKeyProvider->method('getApiKey')->willReturn('test-key');
        $this->customFieldService->method('getGroup')->willReturn(['item' => ['id' => 'group1']]);

        $response = $this->resource->handle($this->makeRequest(['groupId' => 'group1']));
        $this->assertFalse($response->isError());
    }

    public function testHandleAuthError(): void
    {
        $this->apiKeyProvider->method('getApiKey')->willThrowException(new ValidationException('No API key'));

        $response = $this->resource->handle($this->makeRequest(['groupId' => 'group1']));
        $this->assertTrue($response->isError());
    }

    public function testHandleServiceError(): void
    {
        $this->apiKeyProvider->method('getApiKey')->willReturn('test-key');
        $this->customFieldService->method('getGroup')->willThrowException(new AuthenticationException('Unauthorized'));

        $response = $this->resource->handle($this->makeRequest(['groupId' => 'group1']));
        $this->assertTrue($response->isError());
    }

    public function testHandlePlankaApiError(): void
    {
        $this->apiKeyProvider->method('getApiKey')->willReturn('test-key');
        $this->customFieldService->method('getGroup')->willThrowException(new PlankaApiException('Server error'));

        $response = $this->resource->handle($this->makeRequest(['groupId' => 'group1']));
        $this->assertTrue($response->isError());
    }

    public function testUriTemplateReturnsUriTemplate(): void
    {
        $template = $this->resource->uriTemplate();
        $this->assertInstanceOf(\Laravel\Mcp\Support\UriTemplate::class, $template);
    }
}
