<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\Notification;

use App\Domain\Notification\NotificationService;
use App\Planka\Exception\AuthenticationException;
use App\Planka\Exception\PlankaApiException;
use App\Planka\PlankaClientInterface;
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

    public function testGetNotificationsSuccess(): void
    {
        $expected = ['items' => [['id' => 'notif1']]];

        $this->plankaClient
            ->expects($this->once())
            ->method('get')
            ->with('test-api-key', '/api/notifications')
            ->willReturn($expected);

        $this->assertSame($expected, $this->service->getNotifications('test-api-key'));
    }

    public function testGetNotificationsPropagatesAuthException(): void
    {
        $this->plankaClient->method('get')->willThrowException(new AuthenticationException('Unauthorized'));
        $this->expectException(AuthenticationException::class);
        $this->service->getNotifications('bad-key');
    }

    public function testGetNotificationsPropagatesApiException(): void
    {
        $this->plankaClient->method('get')->willThrowException(new PlankaApiException('Server error'));
        $this->expectException(PlankaApiException::class);
        $this->service->getNotifications('test-api-key');
    }

    public function testUpdateNotificationSuccess(): void
    {
        $expected = ['item' => ['id' => 'notif1', 'isRead' => true]];

        $this->plankaClient
            ->expects($this->once())
            ->method('patch')
            ->with('test-api-key', '/api/notifications/notif1', ['isRead' => true])
            ->willReturn($expected);

        $this->assertSame($expected, $this->service->updateNotification('test-api-key', 'notif1', true));
    }

    public function testUpdateNotificationMarkUnread(): void
    {
        $expected = ['item' => ['id' => 'notif1', 'isRead' => false]];

        $this->plankaClient
            ->expects($this->once())
            ->method('patch')
            ->with('test-api-key', '/api/notifications/notif1', ['isRead' => false])
            ->willReturn($expected);

        $this->assertSame($expected, $this->service->updateNotification('test-api-key', 'notif1', false));
    }

    public function testUpdateNotificationPropagatesAuthException(): void
    {
        $this->plankaClient->method('patch')->willThrowException(new AuthenticationException('Unauthorized'));
        $this->expectException(AuthenticationException::class);
        $this->service->updateNotification('bad-key', 'notif1', true);
    }

    public function testUpdateNotificationPropagatesApiException(): void
    {
        $this->plankaClient->method('patch')->willThrowException(new PlankaApiException('Server error'));
        $this->expectException(PlankaApiException::class);
        $this->service->updateNotification('test-api-key', 'notif1', true);
    }

    public function testReadAllNotificationsSuccess(): void
    {
        $expected = [];

        $this->plankaClient
            ->expects($this->once())
            ->method('post')
            ->with('test-api-key', '/api/notifications/read-all', [])
            ->willReturn($expected);

        $this->assertSame($expected, $this->service->readAllNotifications('test-api-key'));
    }

    public function testReadAllNotificationsPropagatesAuthException(): void
    {
        $this->plankaClient->method('post')->willThrowException(new AuthenticationException('Unauthorized'));
        $this->expectException(AuthenticationException::class);
        $this->service->readAllNotifications('bad-key');
    }

    public function testReadAllNotificationsPropagatesApiException(): void
    {
        $this->plankaClient->method('post')->willThrowException(new PlankaApiException('Server error'));
        $this->expectException(PlankaApiException::class);
        $this->service->readAllNotifications('test-api-key');
    }

    public function testGetNotificationSuccess(): void
    {
        $expected = ['item' => ['id' => 'notif1']];

        $this->plankaClient
            ->expects($this->once())
            ->method('get')
            ->with('test-api-key', '/api/notifications/notif1')
            ->willReturn($expected);

        $this->assertSame($expected, $this->service->getNotification('test-api-key', 'notif1'));
    }

    public function testGetNotificationPropagatesAuthException(): void
    {
        $this->plankaClient->method('get')->willThrowException(new AuthenticationException('Unauthorized'));
        $this->expectException(AuthenticationException::class);
        $this->service->getNotification('bad-key', 'notif1');
    }

    public function testGetNotificationPropagatesApiException(): void
    {
        $this->plankaClient->method('get')->willThrowException(new PlankaApiException('Server error'));
        $this->expectException(PlankaApiException::class);
        $this->service->getNotification('test-api-key', 'notif1');
    }
}
