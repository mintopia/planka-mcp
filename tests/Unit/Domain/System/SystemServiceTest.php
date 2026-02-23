<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\System;

use App\Domain\System\SystemService;
use App\Planka\Exception\AuthenticationException;
use App\Planka\Exception\PlankaApiException;
use App\Planka\PlankaClientInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

final class SystemServiceTest extends TestCase
{
    private PlankaClientInterface&MockObject $plankaClient;
    private SystemService $service;

    protected function setUp(): void
    {
        $this->plankaClient = $this->createMock(PlankaClientInterface::class);
        $this->service = new SystemService($this->plankaClient);
    }

    public function testGetConfigSuccess(): void
    {
        $expected = ['item' => ['version' => '2.0.0']];

        $this->plankaClient
            ->expects($this->once())
            ->method('get')
            ->with('test-api-key', '/api/config')
            ->willReturn($expected);

        $this->assertSame($expected, $this->service->getConfig('test-api-key'));
    }

    public function testGetConfigPropagatesAuthException(): void
    {
        $this->plankaClient->method('get')->willThrowException(new AuthenticationException('Unauthorized'));
        $this->expectException(AuthenticationException::class);
        $this->service->getConfig('bad-key');
    }

    public function testGetConfigPropagatesApiException(): void
    {
        $this->plankaClient->method('get')->willThrowException(new PlankaApiException('Server error'));
        $this->expectException(PlankaApiException::class);
        $this->service->getConfig('test-api-key');
    }

    public function testGetBootstrapSuccess(): void
    {
        $expected = ['item' => ['user' => ['id' => 'user1']]];

        $this->plankaClient
            ->expects($this->once())
            ->method('get')
            ->with('test-api-key', '/api/bootstrap')
            ->willReturn($expected);

        $this->assertSame($expected, $this->service->getBootstrap('test-api-key'));
    }

    public function testGetBootstrapPropagatesAuthException(): void
    {
        $this->plankaClient->method('get')->willThrowException(new AuthenticationException('Unauthorized'));
        $this->expectException(AuthenticationException::class);
        $this->service->getBootstrap('bad-key');
    }

    public function testGetBootstrapPropagatesApiException(): void
    {
        $this->plankaClient->method('get')->willThrowException(new PlankaApiException('Server error'));
        $this->expectException(PlankaApiException::class);
        $this->service->getBootstrap('test-api-key');
    }
}
