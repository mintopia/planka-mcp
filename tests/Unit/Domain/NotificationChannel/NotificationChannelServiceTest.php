<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\NotificationChannel;

use App\Domain\NotificationChannel\NotificationChannelService;
use App\Planka\Exception\AuthenticationException;
use App\Planka\Exception\PlankaApiException;
use App\Planka\PlankaClientInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

final class NotificationChannelServiceTest extends TestCase
{
    private PlankaClientInterface&MockObject $plankaClient;
    private NotificationChannelService $service;

    protected function setUp(): void
    {
        $this->plankaClient = $this->createMock(PlankaClientInterface::class);
        $this->service = new NotificationChannelService($this->plankaClient);
    }

    public function testCreateForUserSuccess(): void
    {
        $expected = ['item' => ['id' => 'ns1', 'type' => 'slack']];

        $this->plankaClient
            ->expects($this->once())
            ->method('post')
            ->with('test-api-key', '/api/users/user1/notification-services', ['type' => 'slack', 'params' => ['url' => 'https://hooks.slack.com/test']])
            ->willReturn($expected);

        $this->assertSame($expected, $this->service->createForUser('test-api-key', 'user1', 'slack', ['url' => 'https://hooks.slack.com/test']));
    }

    public function testCreateForUserWithNullTypeAndParams(): void
    {
        $expected = ['item' => ['id' => 'ns1']];

        $this->plankaClient
            ->expects($this->once())
            ->method('post')
            ->with('test-api-key', '/api/users/user1/notification-services', [])
            ->willReturn($expected);

        $this->assertSame($expected, $this->service->createForUser('test-api-key', 'user1', null, null));
    }

    public function testCreateForUserPropagatesAuthException(): void
    {
        $this->plankaClient->method('post')->willThrowException(new AuthenticationException('Unauthorized'));
        $this->expectException(AuthenticationException::class);
        $this->service->createForUser('bad-key', 'user1', 'slack', null);
    }

    public function testCreateForUserPropagatesApiException(): void
    {
        $this->plankaClient->method('post')->willThrowException(new PlankaApiException('Server error'));
        $this->expectException(PlankaApiException::class);
        $this->service->createForUser('test-api-key', 'user1', 'slack', null);
    }

    public function testCreateForBoardSuccess(): void
    {
        $expected = ['item' => ['id' => 'ns1', 'type' => 'telegram']];

        $this->plankaClient
            ->expects($this->once())
            ->method('post')
            ->with('test-api-key', '/api/boards/board1/notification-services', ['type' => 'telegram', 'params' => ['chatId' => '123']])
            ->willReturn($expected);

        $this->assertSame($expected, $this->service->createForBoard('test-api-key', 'board1', 'telegram', ['chatId' => '123']));
    }

    public function testCreateForBoardWithNullTypeAndParams(): void
    {
        $expected = ['item' => ['id' => 'ns1']];

        $this->plankaClient
            ->expects($this->once())
            ->method('post')
            ->with('test-api-key', '/api/boards/board1/notification-services', [])
            ->willReturn($expected);

        $this->assertSame($expected, $this->service->createForBoard('test-api-key', 'board1', null, null));
    }

    public function testCreateForBoardPropagatesAuthException(): void
    {
        $this->plankaClient->method('post')->willThrowException(new AuthenticationException('Unauthorized'));
        $this->expectException(AuthenticationException::class);
        $this->service->createForBoard('bad-key', 'board1', 'telegram', null);
    }

    public function testCreateForBoardPropagatesApiException(): void
    {
        $this->plankaClient->method('post')->willThrowException(new PlankaApiException('Server error'));
        $this->expectException(PlankaApiException::class);
        $this->service->createForBoard('test-api-key', 'board1', 'telegram', null);
    }

    public function testUpdateChannelWithEnabledAndParams(): void
    {
        $expected = ['item' => ['id' => 'ns1', 'isEnabled' => true]];

        $this->plankaClient
            ->expects($this->once())
            ->method('patch')
            ->with('test-api-key', '/api/notification-services/ns1', ['isEnabled' => true, 'params' => ['url' => 'https://new.url']])
            ->willReturn($expected);

        $this->assertSame($expected, $this->service->updateChannel('test-api-key', 'ns1', true, ['url' => 'https://new.url']));
    }

    public function testUpdateChannelWithEnabledOnly(): void
    {
        $expected = ['item' => ['id' => 'ns1', 'isEnabled' => false]];

        $this->plankaClient
            ->expects($this->once())
            ->method('patch')
            ->with('test-api-key', '/api/notification-services/ns1', ['isEnabled' => false])
            ->willReturn($expected);

        $this->assertSame($expected, $this->service->updateChannel('test-api-key', 'ns1', false, null));
    }

    public function testUpdateChannelWithNullsSendsEmptyBody(): void
    {
        $expected = ['item' => ['id' => 'ns1']];

        $this->plankaClient
            ->expects($this->once())
            ->method('patch')
            ->with('test-api-key', '/api/notification-services/ns1', [])
            ->willReturn($expected);

        $this->assertSame($expected, $this->service->updateChannel('test-api-key', 'ns1', null, null));
    }

    public function testUpdateChannelPropagatesAuthException(): void
    {
        $this->plankaClient->method('patch')->willThrowException(new AuthenticationException('Unauthorized'));
        $this->expectException(AuthenticationException::class);
        $this->service->updateChannel('bad-key', 'ns1', true, null);
    }

    public function testUpdateChannelPropagatesApiException(): void
    {
        $this->plankaClient->method('patch')->willThrowException(new PlankaApiException('Server error'));
        $this->expectException(PlankaApiException::class);
        $this->service->updateChannel('test-api-key', 'ns1', true, null);
    }

    public function testTestChannelSuccess(): void
    {
        $expected = ['ok' => true];

        $this->plankaClient
            ->expects($this->once())
            ->method('post')
            ->with('test-api-key', '/api/notification-services/ns1/test', [])
            ->willReturn($expected);

        $this->assertSame($expected, $this->service->testChannel('test-api-key', 'ns1'));
    }

    public function testTestChannelPropagatesAuthException(): void
    {
        $this->plankaClient->method('post')->willThrowException(new AuthenticationException('Unauthorized'));
        $this->expectException(AuthenticationException::class);
        $this->service->testChannel('bad-key', 'ns1');
    }

    public function testTestChannelPropagatesApiException(): void
    {
        $this->plankaClient->method('post')->willThrowException(new PlankaApiException('Server error'));
        $this->expectException(PlankaApiException::class);
        $this->service->testChannel('test-api-key', 'ns1');
    }

    public function testDeleteChannelSuccess(): void
    {
        $this->plankaClient
            ->expects($this->once())
            ->method('delete')
            ->with('test-api-key', '/api/notification-services/ns1')
            ->willReturn([]);

        $this->assertSame([], $this->service->deleteChannel('test-api-key', 'ns1'));
    }

    public function testDeleteChannelPropagatesAuthException(): void
    {
        $this->plankaClient->method('delete')->willThrowException(new AuthenticationException('Unauthorized'));
        $this->expectException(AuthenticationException::class);
        $this->service->deleteChannel('bad-key', 'ns1');
    }

    public function testDeleteChannelPropagatesApiException(): void
    {
        $this->plankaClient->method('delete')->willThrowException(new PlankaApiException('Server error'));
        $this->expectException(PlankaApiException::class);
        $this->service->deleteChannel('test-api-key', 'ns1');
    }
}
