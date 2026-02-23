<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\Board;

use App\Domain\Board\BoardService;
use App\Planka\Exception\AuthenticationException;
use App\Planka\Exception\PlankaApiException;
use App\Planka\PlankaClientInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

final class BoardServiceTest extends TestCase
{
    private PlankaClientInterface&MockObject $plankaClient;
    private BoardService $service;

    protected function setUp(): void
    {
        $this->plankaClient = $this->createMock(PlankaClientInterface::class);
        $this->service = new BoardService($this->plankaClient);
    }

    public function testGetBoardSuccess(): void
    {
        $expected = ['item' => ['id' => 'board1', 'name' => 'Board']];

        $this->plankaClient
            ->expects($this->once())
            ->method('get')
            ->with('test-api-key', '/api/boards/board1')
            ->willReturn($expected);

        $this->assertSame($expected, $this->service->getBoard('test-api-key', 'board1'));
    }

    public function testGetBoardPropagatesAuthException(): void
    {
        $this->plankaClient->method('get')->willThrowException(new AuthenticationException('Unauthorized'));
        $this->expectException(AuthenticationException::class);
        $this->service->getBoard('bad-key', 'board1');
    }

    public function testGetBoardPropagatesApiException(): void
    {
        $this->plankaClient->method('get')->willThrowException(new PlankaApiException('Server error'));
        $this->expectException(PlankaApiException::class);
        $this->service->getBoard('test-api-key', 'board1');
    }

    public function testCreateBoardSuccess(): void
    {
        $expected = ['item' => ['id' => 'board1', 'name' => 'New Board']];

        $this->plankaClient
            ->expects($this->once())
            ->method('post')
            ->with('test-api-key', '/api/projects/proj1/boards', ['name' => 'New Board', 'position' => 65536])
            ->willReturn($expected);

        $this->assertSame($expected, $this->service->createBoard('test-api-key', 'proj1', 'New Board'));
    }

    public function testCreateBoardWithPosition(): void
    {
        $expected = ['item' => ['id' => 'board1', 'name' => 'New Board']];

        $this->plankaClient
            ->expects($this->once())
            ->method('post')
            ->with('test-api-key', '/api/projects/proj1/boards', ['name' => 'New Board', 'position' => 100])
            ->willReturn($expected);

        $this->assertSame($expected, $this->service->createBoard('test-api-key', 'proj1', 'New Board', 100));
    }

    public function testCreateBoardPropagatesAuthException(): void
    {
        $this->plankaClient->method('post')->willThrowException(new AuthenticationException('Unauthorized'));
        $this->expectException(AuthenticationException::class);
        $this->service->createBoard('bad-key', 'proj1', 'Name');
    }

    public function testCreateBoardPropagatesApiException(): void
    {
        $this->plankaClient->method('post')->willThrowException(new PlankaApiException('Server error'));
        $this->expectException(PlankaApiException::class);
        $this->service->createBoard('test-api-key', 'proj1', 'Name');
    }

    public function testUpdateBoardWithNameAndPosition(): void
    {
        $expected = ['item' => ['id' => 'board1', 'name' => 'Updated']];

        $this->plankaClient
            ->expects($this->once())
            ->method('patch')
            ->with('test-api-key', '/api/boards/board1', ['name' => 'Updated', 'position' => 100])
            ->willReturn($expected);

        $this->assertSame($expected, $this->service->updateBoard('test-api-key', 'board1', 'Updated', 100));
    }

    public function testUpdateBoardWithNameOnly(): void
    {
        $expected = ['item' => ['id' => 'board1', 'name' => 'Updated']];

        $this->plankaClient
            ->expects($this->once())
            ->method('patch')
            ->with('test-api-key', '/api/boards/board1', ['name' => 'Updated'])
            ->willReturn($expected);

        $this->assertSame($expected, $this->service->updateBoard('test-api-key', 'board1', 'Updated'));
    }

    public function testUpdateBoardWithNullsSendsEmptyBody(): void
    {
        $expected = ['item' => ['id' => 'board1']];

        $this->plankaClient
            ->expects($this->once())
            ->method('patch')
            ->with('test-api-key', '/api/boards/board1', [])
            ->willReturn($expected);

        $this->assertSame($expected, $this->service->updateBoard('test-api-key', 'board1'));
    }

    public function testUpdateBoardPropagatesAuthException(): void
    {
        $this->plankaClient->method('patch')->willThrowException(new AuthenticationException('Unauthorized'));
        $this->expectException(AuthenticationException::class);
        $this->service->updateBoard('bad-key', 'board1', 'Name');
    }

    public function testUpdateBoardPropagatesApiException(): void
    {
        $this->plankaClient->method('patch')->willThrowException(new PlankaApiException('Server error'));
        $this->expectException(PlankaApiException::class);
        $this->service->updateBoard('test-api-key', 'board1', 'Name');
    }

    public function testDeleteBoardSuccess(): void
    {
        $this->plankaClient
            ->expects($this->once())
            ->method('delete')
            ->with('test-api-key', '/api/boards/board1')
            ->willReturn([]);

        $this->assertSame([], $this->service->deleteBoard('test-api-key', 'board1'));
    }

    public function testDeleteBoardPropagatesAuthException(): void
    {
        $this->plankaClient->method('delete')->willThrowException(new AuthenticationException('Unauthorized'));
        $this->expectException(AuthenticationException::class);
        $this->service->deleteBoard('bad-key', 'board1');
    }

    public function testDeleteBoardPropagatesApiException(): void
    {
        $this->plankaClient->method('delete')->willThrowException(new PlankaApiException('Server error'));
        $this->expectException(PlankaApiException::class);
        $this->service->deleteBoard('test-api-key', 'board1');
    }

    public function testAddBoardMemberSuccess(): void
    {
        $expected = ['item' => ['id' => 'bm1', 'userId' => 'user1', 'role' => 'editor']];

        $this->plankaClient
            ->expects($this->once())
            ->method('post')
            ->with('test-api-key', '/api/boards/board1/board-memberships', ['userId' => 'user1', 'role' => 'editor'])
            ->willReturn($expected);

        $this->assertSame($expected, $this->service->addBoardMember('test-api-key', 'board1', 'user1'));
    }

    public function testAddBoardMemberWithRole(): void
    {
        $expected = ['item' => ['id' => 'bm1', 'userId' => 'user1', 'role' => 'viewer']];

        $this->plankaClient
            ->expects($this->once())
            ->method('post')
            ->with('test-api-key', '/api/boards/board1/board-memberships', ['userId' => 'user1', 'role' => 'viewer'])
            ->willReturn($expected);

        $this->assertSame($expected, $this->service->addBoardMember('test-api-key', 'board1', 'user1', 'viewer'));
    }

    public function testAddBoardMemberPropagatesAuthException(): void
    {
        $this->plankaClient->method('post')->willThrowException(new AuthenticationException('Unauthorized'));
        $this->expectException(AuthenticationException::class);
        $this->service->addBoardMember('bad-key', 'board1', 'user1');
    }

    public function testAddBoardMemberPropagatesApiException(): void
    {
        $this->plankaClient->method('post')->willThrowException(new PlankaApiException('Server error'));
        $this->expectException(PlankaApiException::class);
        $this->service->addBoardMember('test-api-key', 'board1', 'user1');
    }

    public function testUpdateBoardMembershipSuccess(): void
    {
        $expected = ['item' => ['id' => 'bm1', 'role' => 'viewer']];

        $this->plankaClient
            ->expects($this->once())
            ->method('patch')
            ->with('test-api-key', '/api/board-memberships/bm1', ['role' => 'viewer'])
            ->willReturn($expected);

        $this->assertSame($expected, $this->service->updateBoardMembership('test-api-key', 'bm1', 'viewer'));
    }

    public function testRemoveBoardMemberSuccess(): void
    {
        $this->plankaClient
            ->expects($this->once())
            ->method('delete')
            ->with('test-api-key', '/api/board-memberships/bm1')
            ->willReturn([]);

        $this->assertSame([], $this->service->removeBoardMember('test-api-key', 'bm1'));
    }

    public function testRemoveBoardMemberPropagatesAuthException(): void
    {
        $this->plankaClient->method('delete')->willThrowException(new AuthenticationException('Unauthorized'));
        $this->expectException(AuthenticationException::class);
        $this->service->removeBoardMember('bad-key', 'bm1');
    }

    public function testRemoveBoardMemberPropagatesApiException(): void
    {
        $this->plankaClient->method('delete')->willThrowException(new PlankaApiException('Server error'));
        $this->expectException(PlankaApiException::class);
        $this->service->removeBoardMember('test-api-key', 'bm1');
    }
}
