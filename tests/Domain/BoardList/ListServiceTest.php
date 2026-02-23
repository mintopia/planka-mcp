<?php

declare(strict_types=1);

namespace App\Tests\Domain\BoardList;

use App\Domain\BoardList\ListService;
use App\Planka\Client\PlankaClientInterface;
use App\Planka\Exception\AuthenticationException;
use App\Planka\Exception\PlankaApiException;
use App\Shared\Exception\ValidationException;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

final class ListServiceTest extends TestCase
{
    private PlankaClientInterface&MockObject $plankaClient;
    private ListService $service;

    protected function setUp(): void
    {
        $this->plankaClient = $this->createMock(PlankaClientInterface::class);
        $this->service = new ListService($this->plankaClient);
    }

    // --- manageList: create ---

    public function testManageListCreateSuccess(): void
    {
        $expected = ['item' => ['id' => 'list1', 'name' => 'To Do', 'position' => 1]];

        $this->plankaClient
            ->expects($this->once())
            ->method('post')
            ->with('test-api-key', '/api/boards/board1/lists', ['type' => 'active', 'position' => 1, 'name' => 'To Do'])
            ->willReturn($expected);

        $result = $this->service->manageList(
            apiKey: 'test-api-key',
            action: 'create',
            boardId: 'board1',
            name: 'To Do',
            position: 1,
        );

        $this->assertSame($expected, $result);
    }

    public function testManageListCreateWithNoOptionalFields(): void
    {
        $expected = ['item' => ['id' => 'list1']];

        $this->plankaClient
            ->expects($this->once())
            ->method('post')
            ->with('test-api-key', '/api/boards/board1/lists', ['type' => 'active', 'position' => 65536])
            ->willReturn($expected);

        $result = $this->service->manageList(
            apiKey: 'test-api-key',
            action: 'create',
            boardId: 'board1',
        );

        $this->assertSame($expected, $result);
    }

    public function testManageListCreateWithoutBoardIdThrowsValidationException(): void
    {
        $this->plankaClient->expects($this->never())->method('post');

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('boardId required for create');

        $this->service->manageList(
            apiKey: 'test-api-key',
            action: 'create',
        );
    }

    // --- manageList: update ---

    public function testManageListUpdateSuccess(): void
    {
        $expected = ['item' => ['id' => 'list1', 'name' => 'In Progress', 'position' => 2]];

        $this->plankaClient
            ->expects($this->once())
            ->method('patch')
            ->with('test-api-key', '/api/lists/list1', ['name' => 'In Progress', 'position' => 2])
            ->willReturn($expected);

        $result = $this->service->manageList(
            apiKey: 'test-api-key',
            action: 'update',
            listId: 'list1',
            name: 'In Progress',
            position: 2,
        );

        $this->assertSame($expected, $result);
    }

    public function testManageListUpdateNameOnly(): void
    {
        $expected = ['item' => ['id' => 'list1', 'name' => 'Done']];

        $this->plankaClient
            ->expects($this->once())
            ->method('patch')
            ->with('test-api-key', '/api/lists/list1', ['name' => 'Done'])
            ->willReturn($expected);

        $result = $this->service->manageList(
            apiKey: 'test-api-key',
            action: 'update',
            listId: 'list1',
            name: 'Done',
        );

        $this->assertSame($expected, $result);
    }

    public function testManageListUpdateWithoutListIdThrowsValidationException(): void
    {
        $this->plankaClient->expects($this->never())->method('patch');

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('listId required for update');

        $this->service->manageList(
            apiKey: 'test-api-key',
            action: 'update',
            name: 'In Progress',
        );
    }

    // --- manageList: delete ---

    public function testManageListDeleteSuccess(): void
    {
        $this->plankaClient
            ->expects($this->once())
            ->method('delete')
            ->with('test-api-key', '/api/lists/list1')
            ->willReturn([]);

        $result = $this->service->manageList(
            apiKey: 'test-api-key',
            action: 'delete',
            listId: 'list1',
        );

        $this->assertSame([], $result);
    }

    public function testManageListDeleteWithoutListIdThrowsValidationException(): void
    {
        $this->plankaClient->expects($this->never())->method('delete');

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('listId required for delete');

        $this->service->manageList(
            apiKey: 'test-api-key',
            action: 'delete',
        );
    }

    // --- manageList: get ---

    public function testManageListGetSuccess(): void
    {
        $expected = ['item' => ['id' => 'list1', 'name' => 'To Do']];

        $this->plankaClient
            ->expects($this->once())
            ->method('get')
            ->with('test-api-key', '/api/lists/list1')
            ->willReturn($expected);

        $result = $this->service->manageList(
            apiKey: 'test-api-key',
            action: 'get',
            listId: 'list1',
        );

        $this->assertSame($expected, $result);
    }

    public function testManageListGetWithoutListIdThrowsValidationException(): void
    {
        $this->plankaClient->expects($this->never())->method('get');

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('listId required for get');

        $this->service->manageList(
            apiKey: 'test-api-key',
            action: 'get',
        );
    }

    // --- manageList: invalid action ---

    public function testManageListInvalidActionThrowsValidationException(): void
    {
        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('Invalid action "reorder". Must be: create, update, delete, get, get_cards, move_cards, clear');

        $this->service->manageList(
            apiKey: 'test-api-key',
            action: 'reorder',
        );
    }

    // --- manageList: get_cards ---

    public function testManageListGetCardsSuccess(): void
    {
        $expected = ['items' => [['id' => 'card1'], ['id' => 'card2']]];

        $this->plankaClient
            ->expects($this->once())
            ->method('get')
            ->with('test-api-key', '/api/lists/list1/cards')
            ->willReturn($expected);

        $result = $this->service->manageList(
            apiKey: 'test-api-key',
            action: 'get_cards',
            listId: 'list1',
        );

        $this->assertSame($expected, $result);
    }

    public function testManageListGetCardsWithoutListIdThrowsValidationException(): void
    {
        $this->plankaClient->expects($this->never())->method('get');

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('listId required for get_cards');

        $this->service->manageList(
            apiKey: 'test-api-key',
            action: 'get_cards',
        );
    }

    // --- manageList: move_cards ---

    public function testManageListMoveCardsSuccess(): void
    {
        $expected = ['items' => []];

        $this->plankaClient
            ->expects($this->once())
            ->method('post')
            ->with('test-api-key', '/api/lists/list1/move-cards', ['listId' => 'list2'])
            ->willReturn($expected);

        $result = $this->service->manageList(
            apiKey: 'test-api-key',
            action: 'move_cards',
            listId: 'list1',
            toListId: 'list2',
        );

        $this->assertSame($expected, $result);
    }

    public function testManageListMoveCardsWithoutListIdThrowsValidationException(): void
    {
        $this->plankaClient->expects($this->never())->method('post');

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('listId required for move_cards');

        $this->service->manageList(
            apiKey: 'test-api-key',
            action: 'move_cards',
            toListId: 'list2',
        );
    }

    public function testManageListMoveCardsWithoutToListIdThrowsValidationException(): void
    {
        $this->plankaClient->expects($this->never())->method('post');

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('toListId required for move_cards');

        $this->service->manageList(
            apiKey: 'test-api-key',
            action: 'move_cards',
            listId: 'list1',
        );
    }

    // --- manageList: clear ---

    public function testManageListClearSuccess(): void
    {
        $this->plankaClient
            ->expects($this->once())
            ->method('post')
            ->with('test-api-key', '/api/lists/list1/clear', [])
            ->willReturn([]);

        $result = $this->service->manageList(
            apiKey: 'test-api-key',
            action: 'clear',
            listId: 'list1',
        );

        $this->assertSame([], $result);
    }

    public function testManageListClearWithoutListIdThrowsValidationException(): void
    {
        $this->plankaClient->expects($this->never())->method('post');

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('listId required for clear');

        $this->service->manageList(
            apiKey: 'test-api-key',
            action: 'clear',
        );
    }

    // --- manageList: exception propagation ---

    public function testManageListCreatePropagatesAuthException(): void
    {
        $this->plankaClient
            ->method('post')
            ->willThrowException(new AuthenticationException('Unauthorized'));

        $this->expectException(AuthenticationException::class);

        $this->service->manageList(
            apiKey: 'bad-key',
            action: 'create',
            boardId: 'board1',
        );
    }

    public function testManageListUpdatePropagatesApiException(): void
    {
        $this->plankaClient
            ->method('patch')
            ->willThrowException(new PlankaApiException('Server error'));

        $this->expectException(PlankaApiException::class);

        $this->service->manageList(
            apiKey: 'test-api-key',
            action: 'update',
            listId: 'list1',
        );
    }

    public function testManageListDeletePropagatesApiException(): void
    {
        $this->plankaClient
            ->method('delete')
            ->willThrowException(new PlankaApiException('Server error'));

        $this->expectException(PlankaApiException::class);

        $this->service->manageList(
            apiKey: 'test-api-key',
            action: 'delete',
            listId: 'list1',
        );
    }

    public function testManageListCreatePropagatesApiException(): void
    {
        $this->plankaClient
            ->method('post')
            ->willThrowException(new PlankaApiException('Server error'));

        $this->expectException(PlankaApiException::class);

        $this->service->manageList(
            apiKey: 'test-api-key',
            action: 'create',
            boardId: 'board1',
        );
    }

    public function testManageListGetPropagatesApiException(): void
    {
        $this->plankaClient
            ->method('get')
            ->willThrowException(new PlankaApiException('Server error'));

        $this->expectException(PlankaApiException::class);

        $this->service->manageList(
            apiKey: 'test-api-key',
            action: 'get',
            listId: 'list1',
        );
    }

    // --- getList ---

    public function testGetListSuccess(): void
    {
        $expected = ['item' => ['id' => 'list1', 'name' => 'To Do']];

        $this->plankaClient
            ->expects($this->once())
            ->method('get')
            ->with('test-api-key', '/api/lists/list1')
            ->willReturn($expected);

        $result = $this->service->getList('test-api-key', 'list1');

        $this->assertSame($expected, $result);
    }

    public function testGetListPropagatesAuthException(): void
    {
        $this->plankaClient
            ->method('get')
            ->willThrowException(new AuthenticationException('Unauthorized'));

        $this->expectException(AuthenticationException::class);

        $this->service->getList('bad-key', 'list1');
    }

    public function testGetListPropagatesApiException(): void
    {
        $this->plankaClient
            ->method('get')
            ->willThrowException(new PlankaApiException('Server error'));

        $this->expectException(PlankaApiException::class);

        $this->service->getList('test-api-key', 'list1');
    }

    // --- sortList ---

    public function testSortListSuccess(): void
    {
        $expected = ['items' => [['id' => 'card1'], ['id' => 'card2']]];

        $this->plankaClient
            ->expects($this->once())
            ->method('post')
            ->with('test-api-key', '/api/lists/list1/sort', ['fieldName' => 'name'])
            ->willReturn($expected);

        $result = $this->service->sortList('test-api-key', 'list1', 'name');

        $this->assertSame($expected, $result);
    }

    public function testSortListPropagatesAuthException(): void
    {
        $this->plankaClient
            ->method('post')
            ->willThrowException(new AuthenticationException('Unauthorized'));

        $this->expectException(AuthenticationException::class);

        $this->service->sortList('bad-key', 'list1', 'name');
    }

    public function testSortListPropagatesApiException(): void
    {
        $this->plankaClient
            ->method('post')
            ->willThrowException(new PlankaApiException('Server error'));

        $this->expectException(PlankaApiException::class);

        $this->service->sortList('test-api-key', 'list1', 'name');
    }

    // --- getListCards ---

    public function testGetListCardsSuccess(): void
    {
        $expected = ['items' => [['id' => 'card1'], ['id' => 'card2']]];

        $this->plankaClient
            ->expects($this->once())
            ->method('get')
            ->with('test-api-key', '/api/lists/list1/cards')
            ->willReturn($expected);

        $result = $this->service->getListCards('test-api-key', 'list1');

        $this->assertSame($expected, $result);
    }

    public function testGetListCardsPropagatesAuthException(): void
    {
        $this->plankaClient
            ->method('get')
            ->willThrowException(new AuthenticationException('Unauthorized'));

        $this->expectException(AuthenticationException::class);

        $this->service->getListCards('bad-key', 'list1');
    }

    public function testGetListCardsPropagatesApiException(): void
    {
        $this->plankaClient
            ->method('get')
            ->willThrowException(new PlankaApiException('Server error'));

        $this->expectException(PlankaApiException::class);

        $this->service->getListCards('test-api-key', 'list1');
    }

    // --- moveListCards ---

    public function testMoveListCardsSuccess(): void
    {
        $expected = ['items' => []];

        $this->plankaClient
            ->expects($this->once())
            ->method('post')
            ->with('test-api-key', '/api/lists/list1/move-cards', ['listId' => 'list2'])
            ->willReturn($expected);

        $result = $this->service->moveListCards('test-api-key', 'list1', 'list2');

        $this->assertSame($expected, $result);
    }

    public function testMoveListCardsPropagatesAuthException(): void
    {
        $this->plankaClient
            ->method('post')
            ->willThrowException(new AuthenticationException('Unauthorized'));

        $this->expectException(AuthenticationException::class);

        $this->service->moveListCards('bad-key', 'list1', 'list2');
    }

    public function testMoveListCardsPropagatesApiException(): void
    {
        $this->plankaClient
            ->method('post')
            ->willThrowException(new PlankaApiException('Server error'));

        $this->expectException(PlankaApiException::class);

        $this->service->moveListCards('test-api-key', 'list1', 'list2');
    }

    // --- clearList ---

    public function testClearListSuccess(): void
    {
        $this->plankaClient
            ->expects($this->once())
            ->method('post')
            ->with('test-api-key', '/api/lists/list1/clear', [])
            ->willReturn([]);

        $result = $this->service->clearList('test-api-key', 'list1');

        $this->assertSame([], $result);
    }

    public function testClearListPropagatesAuthException(): void
    {
        $this->plankaClient
            ->method('post')
            ->willThrowException(new AuthenticationException('Unauthorized'));

        $this->expectException(AuthenticationException::class);

        $this->service->clearList('bad-key', 'list1');
    }

    public function testClearListPropagatesApiException(): void
    {
        $this->plankaClient
            ->method('post')
            ->willThrowException(new PlankaApiException('Server error'));

        $this->expectException(PlankaApiException::class);

        $this->service->clearList('test-api-key', 'list1');
    }
}
