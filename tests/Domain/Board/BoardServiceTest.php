<?php

declare(strict_types=1);

namespace App\Tests\Domain\Board;

use App\Domain\Board\BoardService;
use App\Planka\Client\PlankaClientInterface;
use App\Planka\Exception\AuthenticationException;
use App\Planka\Exception\PlankaApiException;
use App\Planka\Exception\PlankaNotFoundException;
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

    // --- createBoard ---

    public function testCreateBoardWithPositionSuccess(): void
    {
        $expected = ['item' => ['id' => 'board1', 'name' => 'Sprint', 'position' => 1]];

        $this->plankaClient
            ->expects($this->once())
            ->method('post')
            ->with('test-api-key', '/api/projects/proj1/boards', ['name' => 'Sprint', 'position' => 1])
            ->willReturn($expected);

        $result = $this->service->createBoard('test-api-key', 'proj1', 'Sprint', 1);

        $this->assertSame($expected, $result);
    }

    public function testCreateBoardWithoutPositionSuccess(): void
    {
        $expected = ['item' => ['id' => 'board1', 'name' => 'Sprint']];

        $this->plankaClient
            ->expects($this->once())
            ->method('post')
            ->with('test-api-key', '/api/projects/proj1/boards', ['name' => 'Sprint', 'position' => 65536])
            ->willReturn($expected);

        $result = $this->service->createBoard('test-api-key', 'proj1', 'Sprint');

        $this->assertSame($expected, $result);
    }

    public function testCreateBoardPropagatesAuthException(): void
    {
        $this->plankaClient
            ->method('post')
            ->willThrowException(new AuthenticationException('Unauthorized'));

        $this->expectException(AuthenticationException::class);

        $this->service->createBoard('bad-key', 'proj1', 'Board');
    }

    public function testCreateBoardPropagatesApiException(): void
    {
        $this->plankaClient
            ->method('post')
            ->willThrowException(new PlankaApiException('Server error'));

        $this->expectException(PlankaApiException::class);

        $this->service->createBoard('test-api-key', 'proj1', 'Board');
    }

    // --- updateBoard ---

    public function testUpdateBoardWithNameAndPositionSuccess(): void
    {
        $expected = ['item' => ['id' => 'board1', 'name' => 'Updated', 'position' => 2]];

        $this->plankaClient
            ->expects($this->once())
            ->method('patch')
            ->with('test-api-key', '/api/boards/board1', ['name' => 'Updated', 'position' => 2])
            ->willReturn($expected);

        $result = $this->service->updateBoard('test-api-key', 'board1', 'Updated', 2);

        $this->assertSame($expected, $result);
    }

    public function testUpdateBoardWithNameOnlySuccess(): void
    {
        $expected = ['item' => ['id' => 'board1', 'name' => 'Updated']];

        $this->plankaClient
            ->expects($this->once())
            ->method('patch')
            ->with('test-api-key', '/api/boards/board1', ['name' => 'Updated'])
            ->willReturn($expected);

        $result = $this->service->updateBoard('test-api-key', 'board1', 'Updated');

        $this->assertSame($expected, $result);
    }

    public function testUpdateBoardWithNullParamsSendsEmptyBody(): void
    {
        $expected = ['item' => ['id' => 'board1']];

        $this->plankaClient
            ->expects($this->once())
            ->method('patch')
            ->with('test-api-key', '/api/boards/board1', [])
            ->willReturn($expected);

        $result = $this->service->updateBoard('test-api-key', 'board1');

        $this->assertSame($expected, $result);
    }

    public function testUpdateBoardPropagatesAuthException(): void
    {
        $this->plankaClient
            ->method('patch')
            ->willThrowException(new AuthenticationException('Unauthorized'));

        $this->expectException(AuthenticationException::class);

        $this->service->updateBoard('bad-key', 'board1', 'Name');
    }

    public function testUpdateBoardPropagatesApiException(): void
    {
        $this->plankaClient
            ->method('patch')
            ->willThrowException(new PlankaApiException('Server error'));

        $this->expectException(PlankaApiException::class);

        $this->service->updateBoard('test-api-key', 'board1', 'Name');
    }

    // --- deleteBoard ---

    public function testDeleteBoardSuccess(): void
    {
        $this->plankaClient
            ->expects($this->once())
            ->method('delete')
            ->with('test-api-key', '/api/boards/board1')
            ->willReturn([]);

        $result = $this->service->deleteBoard('test-api-key', 'board1');

        $this->assertSame([], $result);
    }

    public function testDeleteBoardPropagatesAuthException(): void
    {
        $this->plankaClient
            ->method('delete')
            ->willThrowException(new AuthenticationException('Unauthorized'));

        $this->expectException(AuthenticationException::class);

        $this->service->deleteBoard('bad-key', 'board1');
    }

    public function testDeleteBoardPropagatesApiException(): void
    {
        $this->plankaClient
            ->method('delete')
            ->willThrowException(new PlankaApiException('Server error'));

        $this->expectException(PlankaApiException::class);

        $this->service->deleteBoard('test-api-key', 'board1');
    }

    // --- addBoardMember ---

    public function testAddBoardMemberWithDefaultRoleSuccess(): void
    {
        $expected = ['item' => ['id' => 'mbr1', 'userId' => 'user1', 'role' => 'editor']];

        $this->plankaClient
            ->expects($this->once())
            ->method('post')
            ->with('test-api-key', '/api/boards/board1/board-memberships', ['userId' => 'user1', 'role' => 'editor'])
            ->willReturn($expected);

        $result = $this->service->addBoardMember('test-api-key', 'board1', 'user1');

        $this->assertSame($expected, $result);
    }

    public function testAddBoardMemberWithViewerRoleSuccess(): void
    {
        $expected = ['item' => ['id' => 'mbr1', 'userId' => 'user1', 'role' => 'viewer']];

        $this->plankaClient
            ->expects($this->once())
            ->method('post')
            ->with('test-api-key', '/api/boards/board1/board-memberships', ['userId' => 'user1', 'role' => 'viewer'])
            ->willReturn($expected);

        $result = $this->service->addBoardMember('test-api-key', 'board1', 'user1', 'viewer');

        $this->assertSame($expected, $result);
    }

    public function testAddBoardMemberPropagatesAuthException(): void
    {
        $this->plankaClient
            ->method('post')
            ->willThrowException(new AuthenticationException('Unauthorized'));

        $this->expectException(AuthenticationException::class);

        $this->service->addBoardMember('bad-key', 'board1', 'user1');
    }

    public function testAddBoardMemberPropagatesApiException(): void
    {
        $this->plankaClient
            ->method('post')
            ->willThrowException(new PlankaApiException('Server error'));

        $this->expectException(PlankaApiException::class);

        $this->service->addBoardMember('test-api-key', 'board1', 'user1');
    }

    // --- updateBoardMembership ---

    public function testUpdateBoardMembershipSuccess(): void
    {
        $expected = ['item' => ['id' => 'mbr1', 'role' => 'viewer']];

        $this->plankaClient
            ->expects($this->once())
            ->method('patch')
            ->with('test-api-key', '/api/board-memberships/mbr1', ['role' => 'viewer'])
            ->willReturn($expected);

        $result = $this->service->updateBoardMembership('test-api-key', 'mbr1', 'viewer');

        $this->assertSame($expected, $result);
    }

    public function testUpdateBoardMembershipPropagatesAuthException(): void
    {
        $this->plankaClient
            ->method('patch')
            ->willThrowException(new AuthenticationException('Unauthorized'));

        $this->expectException(AuthenticationException::class);

        $this->service->updateBoardMembership('bad-key', 'mbr1', 'editor');
    }

    public function testUpdateBoardMembershipPropagatesApiException(): void
    {
        $this->plankaClient
            ->method('patch')
            ->willThrowException(new PlankaApiException('Server error'));

        $this->expectException(PlankaApiException::class);

        $this->service->updateBoardMembership('test-api-key', 'mbr1', 'editor');
    }

    // --- removeBoardMember ---

    public function testRemoveBoardMemberSuccess(): void
    {
        $this->plankaClient
            ->expects($this->once())
            ->method('delete')
            ->with('test-api-key', '/api/board-memberships/mbr1')
            ->willReturn([]);

        $result = $this->service->removeBoardMember('test-api-key', 'mbr1');

        $this->assertSame([], $result);
    }

    public function testRemoveBoardMemberPropagatesAuthException(): void
    {
        $this->plankaClient
            ->method('delete')
            ->willThrowException(new AuthenticationException('Unauthorized'));

        $this->expectException(AuthenticationException::class);

        $this->service->removeBoardMember('bad-key', 'mbr1');
    }

    public function testRemoveBoardMemberPropagatesApiException(): void
    {
        $this->plankaClient
            ->method('delete')
            ->willThrowException(new PlankaApiException('Server error'));

        $this->expectException(PlankaApiException::class);

        $this->service->removeBoardMember('test-api-key', 'mbr1');
    }
}
