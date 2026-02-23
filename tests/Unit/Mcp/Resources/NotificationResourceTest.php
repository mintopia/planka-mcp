<?php

declare(strict_types=1);

namespace Tests\Unit\Mcp\Resources;

use App\Domain\Notification\NotificationServiceInterface;
use App\Exception\ValidationException;
use App\Http\ApiKeyProviderInterface;
use App\Mcp\Resources\NotificationResource;
use App\Planka\Exception\AuthenticationException;
use App\Planka\Exception\PlankaApiException;
use Laravel\Mcp\Request;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

final class NotificationResourceTest extends TestCase
{
    private NotificationServiceInterface&MockObject $notificationService;
    private ApiKeyProviderInterface&MockObject $apiKeyProvider;
    private NotificationResource $resource;

    protected function setUp(): void
    {
        $this->notificationService = $this->createMock(NotificationServiceInterface::class);
        $this->apiKeyProvider = $this->createMock(ApiKeyProviderInterface::class);
        $this->resource = new NotificationResource($this->notificationService, $this->apiKeyProvider);
    }

    private function makeRequest(array $params = []): Request
    {
        return new Request(arguments: $params);
    }

    public function testHandleSuccess(): void
    {
        $this->apiKeyProvider->method('getApiKey')->willReturn('test-key');
        $this->notificationService->method('getNotification')->willReturn(['item' => ['id' => 'notif1']]);

        $response = $this->resource->handle($this->makeRequest(['notificationId' => 'notif1']));
        $this->assertFalse($response->isError());
    }

    public function testHandleAuthError(): void
    {
        $this->apiKeyProvider->method('getApiKey')->willThrowException(new ValidationException('No API key'));

        $response = $this->resource->handle($this->makeRequest(['notificationId' => 'notif1']));
        $this->assertTrue($response->isError());
    }

    public function testHandleServiceError(): void
    {
        $this->apiKeyProvider->method('getApiKey')->willReturn('test-key');
        $this->notificationService->method('getNotification')->willThrowException(new AuthenticationException('Unauthorized'));

        $response = $this->resource->handle($this->makeRequest(['notificationId' => 'notif1']));
        $this->assertTrue($response->isError());
    }

    public function testHandlePlankaApiError(): void
    {
        $this->apiKeyProvider->method('getApiKey')->willReturn('test-key');
        $this->notificationService->method('getNotification')->willThrowException(new PlankaApiException('Server error'));

        $response = $this->resource->handle($this->makeRequest(['notificationId' => 'notif1']));
        $this->assertTrue($response->isError());
    }

    public function testUriTemplateReturnsUriTemplate(): void
    {
        $template = $this->resource->uriTemplate();
        $this->assertInstanceOf(\Laravel\Mcp\Support\UriTemplate::class, $template);
    }
}
