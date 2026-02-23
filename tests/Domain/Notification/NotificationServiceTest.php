<?php

declare(strict_types=1);

namespace App\Tests\Domain\Notification;

use App\Domain\Notification\NotificationService;
use App\Planka\Client\PlankaClientInterface;
use App\Planka\Exception\AuthenticationException;
use App\Planka\Exception\PlankaApiException;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

final class NotificationServiceTest extends TestCase
{
    private PlankaClientInterface&MockObject $plankaClient;
    private NotificationService $service;

    protected function setUp(): void
    {
        $this->plankaClient = $this->createMock(PlankaClientInterface::class);
        $this->service = new NotificationService($this->plankaClient);
    }

    // --- getNotifications ---

    public function testGetNotificationsSuccess(): void
    {
        $expected = ['items' => [['id' => 'notif1', 'isRead' => false]]];

        $this->plankaClient
            ->expects($this->once())
            ->method('get')
            ->with('test-api-key', '/api/notifications')
            ->willReturn($expected);

        $result = $this->service->getNotifications('test-api-key');

        $this->assertSame($expected, $result);
    }

    public function testGetNotificationsReturnsEmptyWhenNone(): void
    {
        $this->plankaClient
            ->expects($this->once())
            ->method('get')
            ->with('test-api-key', '/api/notifications')
            ->willReturn([]);

        $result = $this->service->getNotifications('test-api-key');

        $this->assertSame([], $result);
    }

    public function testGetNotificationsPropagatesAuthException(): void
    {
        $this->plankaClient
            ->method('get')
            ->willThrowException(new AuthenticationException('Unauthorized'));

        $this->expectException(AuthenticationException::class);

        $this->service->getNotifications('bad-key');
    }

    public function testGetNotificationsPropagatesApiException(): void
    {
        $this->plankaClient
            ->method('get')
            ->willThrowException(new PlankaApiException('Server error'));

        $this->expectException(PlankaApiException::class);

        $this->service->getNotifications('test-api-key');
    }

    // --- updateNotification ---

    public function testUpdateNotificationMarkAsReadSuccess(): void
    {
        $expected = ['item' => ['id' => 'notif1', 'isRead' => true]];

        $this->plankaClient
            ->expects($this->once())
            ->method('patch')
            ->with('test-api-key', '/api/notifications/notif1', ['isRead' => true])
            ->willReturn($expected);

        $result = $this->service->updateNotification('test-api-key', 'notif1', true);

        $this->assertSame($expected, $result);
    }

    public function testUpdateNotificationMarkAsUnreadSuccess(): void
    {
        $expected = ['item' => ['id' => 'notif1', 'isRead' => false]];

        $this->plankaClient
            ->expects($this->once())
            ->method('patch')
            ->with('test-api-key', '/api/notifications/notif1', ['isRead' => false])
            ->willReturn($expected);

        $result = $this->service->updateNotification('test-api-key', 'notif1', false);

        $this->assertSame($expected, $result);
    }

    public function testUpdateNotificationPropagatesAuthException(): void
    {
        $this->plankaClient
            ->method('patch')
            ->willThrowException(new AuthenticationException('Unauthorized'));

        $this->expectException(AuthenticationException::class);

        $this->service->updateNotification('bad-key', 'notif1', true);
    }

    public function testUpdateNotificationPropagatesApiException(): void
    {
        $this->plankaClient
            ->method('patch')
            ->willThrowException(new PlankaApiException('Server error'));

        $this->expectException(PlankaApiException::class);

        $this->service->updateNotification('test-api-key', 'notif1', true);
    }

    // --- readAllNotifications ---

    public function testReadAllNotificationsSuccess(): void
    {
        $expected = ['success' => true];

        $this->plankaClient
            ->expects($this->once())
            ->method('post')
            ->with('test-api-key', '/api/notifications/read-all', [])
            ->willReturn($expected);

        $result = $this->service->readAllNotifications('test-api-key');

        $this->assertSame($expected, $result);
    }

    public function testReadAllNotificationsPropagatesAuthException(): void
    {
        $this->plankaClient
            ->method('post')
            ->willThrowException(new AuthenticationException('Unauthorized'));

        $this->expectException(AuthenticationException::class);

        $this->service->readAllNotifications('bad-key');
    }

    public function testReadAllNotificationsPropagatesApiException(): void
    {
        $this->plankaClient
            ->method('post')
            ->willThrowException(new PlankaApiException('Server error'));

        $this->expectException(PlankaApiException::class);

        $this->service->readAllNotifications('test-api-key');
    }
}
