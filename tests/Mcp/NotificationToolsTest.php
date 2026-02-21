<?php

declare(strict_types=1);

namespace App\Tests\Mcp;

use App\Domain\Notification\NotificationService;
use App\Infrastructure\Http\ApiKeyProvider;
use App\Mcp\NotificationTools;
use App\Planka\Exception\AuthenticationException;
use App\Planka\Exception\PlankaApiException;
use App\Planka\Exception\PlankaNotFoundException;
use App\Shared\Exception\ValidationException;
use Mcp\Exception\ToolCallException;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

final class NotificationToolsTest extends TestCase
{
    private NotificationService&MockObject $notificationService;
    private ApiKeyProvider&MockObject $apiKeyProvider;
    private NotificationTools $tools;

    protected function setUp(): void
    {
        $this->notificationService = $this->createMock(NotificationService::class);
        $this->apiKeyProvider = $this->createMock(ApiKeyProvider::class);
        $this->tools = new NotificationTools($this->notificationService, $this->apiKeyProvider);
    }

    // -------------------------------------------------------------------------
    // getNotifications
    // -------------------------------------------------------------------------

    public function testGetNotificationsSuccess(): void
    {
        $expected = ['items' => [['id' => 'notif1', 'isRead' => false]]];

        $this->apiKeyProvider
            ->expects($this->once())
            ->method('getApiKey')
            ->willReturn('test-api-key');

        $this->notificationService
            ->expects($this->once())
            ->method('getNotifications')
            ->with('test-api-key')
            ->willReturn($expected);

        $result = $this->tools->getNotifications();

        $this->assertSame($expected, $result);
    }

    public function testGetNotificationsReturnsEmptyWhenNone(): void
    {
        $this->apiKeyProvider->method('getApiKey')->willReturn('test-api-key');

        $this->notificationService
            ->expects($this->once())
            ->method('getNotifications')
            ->with('test-api-key')
            ->willReturn([]);

        $result = $this->tools->getNotifications();

        $this->assertSame([], $result);
    }

    public function testGetNotificationsMissingApiKeyThrowsToolCallException(): void
    {
        $this->apiKeyProvider
            ->expects($this->once())
            ->method('getApiKey')
            ->willThrowException(new ValidationException('Planka API key required.'));

        $this->notificationService->expects($this->never())->method('getNotifications');

        $this->expectException(ToolCallException::class);
        $this->expectExceptionMessage('Planka API key required.');

        $this->tools->getNotifications();
    }

    public function testGetNotificationsWrapsAuthExceptionInToolCallException(): void
    {
        $this->apiKeyProvider->method('getApiKey')->willReturn('bad-key');

        $this->notificationService
            ->method('getNotifications')
            ->willThrowException(new AuthenticationException('Unauthorized'));

        $this->expectException(ToolCallException::class);
        $this->expectExceptionMessage('Unauthorized');

        $this->tools->getNotifications();
    }

    public function testGetNotificationsWrapsPlankaApiExceptionInToolCallException(): void
    {
        $this->apiKeyProvider->method('getApiKey')->willReturn('test-api-key');

        $this->notificationService
            ->method('getNotifications')
            ->willThrowException(new PlankaApiException('Server error'));

        $this->expectException(ToolCallException::class);
        $this->expectExceptionMessage('Server error');

        $this->tools->getNotifications();
    }

    // -------------------------------------------------------------------------
    // markNotificationRead
    // -------------------------------------------------------------------------

    public function testMarkNotificationReadAsReadSuccess(): void
    {
        $expected = ['item' => ['id' => 'notif1', 'isRead' => true]];

        $this->apiKeyProvider
            ->expects($this->once())
            ->method('getApiKey')
            ->willReturn('test-api-key');

        $this->notificationService
            ->expects($this->once())
            ->method('updateNotification')
            ->with('test-api-key', 'notif1', true)
            ->willReturn($expected);

        $result = $this->tools->markNotificationRead('notif1', true);

        $this->assertSame($expected, $result);
    }

    public function testMarkNotificationReadDefaultIsReadTrueSuccess(): void
    {
        $expected = ['item' => ['id' => 'notif1', 'isRead' => true]];

        $this->apiKeyProvider->method('getApiKey')->willReturn('test-api-key');

        $this->notificationService
            ->expects($this->once())
            ->method('updateNotification')
            ->with('test-api-key', 'notif1', true)
            ->willReturn($expected);

        $result = $this->tools->markNotificationRead('notif1');

        $this->assertSame($expected, $result);
    }

    public function testMarkNotificationReadAsUnreadSuccess(): void
    {
        $expected = ['item' => ['id' => 'notif1', 'isRead' => false]];

        $this->apiKeyProvider->method('getApiKey')->willReturn('test-api-key');

        $this->notificationService
            ->expects($this->once())
            ->method('updateNotification')
            ->with('test-api-key', 'notif1', false)
            ->willReturn($expected);

        $result = $this->tools->markNotificationRead('notif1', false);

        $this->assertSame($expected, $result);
    }

    public function testMarkNotificationReadMissingApiKeyThrowsToolCallException(): void
    {
        $this->apiKeyProvider
            ->expects($this->once())
            ->method('getApiKey')
            ->willThrowException(new ValidationException('Planka API key required.'));

        $this->notificationService->expects($this->never())->method('updateNotification');

        $this->expectException(ToolCallException::class);
        $this->expectExceptionMessage('Planka API key required.');

        $this->tools->markNotificationRead('notif1');
    }

    public function testMarkNotificationReadWrapsAuthExceptionInToolCallException(): void
    {
        $this->apiKeyProvider->method('getApiKey')->willReturn('bad-key');

        $this->notificationService
            ->method('updateNotification')
            ->willThrowException(new AuthenticationException('Unauthorized'));

        $this->expectException(ToolCallException::class);
        $this->expectExceptionMessage('Unauthorized');

        $this->tools->markNotificationRead('notif1');
    }

    public function testMarkNotificationReadWrapsPlankaApiExceptionInToolCallException(): void
    {
        $this->apiKeyProvider->method('getApiKey')->willReturn('test-api-key');

        $this->notificationService
            ->method('updateNotification')
            ->willThrowException(new PlankaApiException('Server error'));

        $this->expectException(ToolCallException::class);
        $this->expectExceptionMessage('Server error');

        $this->tools->markNotificationRead('notif1');
    }

    public function testMarkNotificationReadWrapsNotFoundExceptionInToolCallException(): void
    {
        $this->apiKeyProvider->method('getApiKey')->willReturn('test-api-key');

        $this->notificationService
            ->method('updateNotification')
            ->willThrowException(new PlankaNotFoundException('Not found', 404));

        $this->expectException(ToolCallException::class);
        $this->expectExceptionMessage('Not found');

        $this->tools->markNotificationRead('notif1');
    }
}
