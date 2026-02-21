<?php

declare(strict_types=1);

namespace App\Tests\Domain\Board;

use App\Domain\Board\BoardService;
use App\Planka\Client\PlankaClient;
use App\Planka\Exception\AuthenticationException;
use App\Planka\Exception\PlankaApiException;
use App\Planka\Exception\PlankaNotFoundException;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

final class BoardServiceTest extends TestCase
{
    private PlankaClient&MockObject $plankaClient;
    private BoardService $service;

    protected function setUp(): void
    {
        $this->plankaClient = $this->createMock(PlankaClient::class);
        $this->service = new BoardService($this->plankaClient);
    }

    public function testGetBoardSuccess(): void
    {
        $expected = ['item' => ['id' => 'board123', 'name' => 'Sprint Board']];

        $this->plankaClient
            ->expects($this->once())
            ->method('get')
            ->with('test-api-key', '/api/boards/board123')
            ->willReturn($expected);

        $result = $this->service->getBoard('test-api-key', 'board123');

        $this->assertSame($expected, $result);
    }

    public function testGetBoardPropagatesAuthException(): void
    {
        $this->plankaClient
            ->method('get')
            ->willThrowException(new AuthenticationException('Unauthorized'));

        $this->expectException(AuthenticationException::class);

        $this->service->getBoard('bad-key', 'board123');
    }

    public function testGetBoardPropagatesApiException(): void
    {
        $this->plankaClient
            ->method('get')
            ->willThrowException(new PlankaApiException('Server error'));

        $this->expectException(PlankaApiException::class);

        $this->service->getBoard('test-api-key', 'board123');
    }

    public function testGetBoardPropagatesNotFoundException(): void
    {
        $this->plankaClient
            ->method('get')
            ->willThrowException(new PlankaNotFoundException('Planka resource not found: /api/boards/board123', 404));

        $this->expectException(PlankaNotFoundException::class);

        $this->service->getBoard('test-api-key', 'board123');
    }
}
