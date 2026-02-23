<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\Action;

use App\Domain\Action\ActionService;
use App\Planka\Exception\AuthenticationException;
use App\Planka\Exception\PlankaApiException;
use App\Planka\PlankaClientInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

final class ActionServiceTest extends TestCase
{
    private PlankaClientInterface&MockObject $plankaClient;
    private ActionService $service;

    protected function setUp(): void
    {
        $this->plankaClient = $this->createMock(PlankaClientInterface::class);
        $this->service = new ActionService($this->plankaClient);
    }

    public function testGetBoardActionsSuccess(): void
    {
        $expected = ['items' => [['id' => 'action1', 'type' => 'cardCreate']]];

        $this->plankaClient
            ->expects($this->once())
            ->method('get')
            ->with('test-api-key', '/api/boards/board1/actions')
            ->willReturn($expected);

        $this->assertSame($expected, $this->service->getBoardActions('test-api-key', 'board1'));
    }

    public function testGetBoardActionsPropagatesAuthException(): void
    {
        $this->plankaClient->method('get')->willThrowException(new AuthenticationException('Unauthorized'));
        $this->expectException(AuthenticationException::class);
        $this->service->getBoardActions('bad-key', 'board1');
    }

    public function testGetBoardActionsPropagatesApiException(): void
    {
        $this->plankaClient->method('get')->willThrowException(new PlankaApiException('Server error'));
        $this->expectException(PlankaApiException::class);
        $this->service->getBoardActions('test-api-key', 'board1');
    }

    public function testGetCardActionsSuccess(): void
    {
        $expected = ['items' => [['id' => 'action1', 'type' => 'commentAdd']]];

        $this->plankaClient
            ->expects($this->once())
            ->method('get')
            ->with('test-api-key', '/api/cards/card1/actions')
            ->willReturn($expected);

        $this->assertSame($expected, $this->service->getCardActions('test-api-key', 'card1'));
    }

    public function testGetCardActionsPropagatesAuthException(): void
    {
        $this->plankaClient->method('get')->willThrowException(new AuthenticationException('Unauthorized'));
        $this->expectException(AuthenticationException::class);
        $this->service->getCardActions('bad-key', 'card1');
    }

    public function testGetCardActionsPropagatesApiException(): void
    {
        $this->plankaClient->method('get')->willThrowException(new PlankaApiException('Server error'));
        $this->expectException(PlankaApiException::class);
        $this->service->getCardActions('test-api-key', 'card1');
    }
}
