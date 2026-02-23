<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\BoardList;

use App\Domain\BoardList\ListService;
use App\Exception\ValidationException;
use App\Planka\Exception\AuthenticationException;
use App\Planka\Exception\PlankaApiException;
use App\Planka\PlankaClientInterface;
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

    public function testManageListCreateSuccess(): void
    {
        $expected = ['item' => ['id' => 'list1', 'name' => 'To Do']];

        $this->plankaClient
            ->expects($this->once())
            ->method('post')
            ->with('test-api-key', '/api/boards/board1/lists', ['type' => 'active', 'position' => 65536, 'name' => 'To Do'])
            ->willReturn($expected);

        $this->assertSame($expected, $this->service->manageList('test-api-key', 'create', 'board1', null, 'To Do'));
    }

    public function testManageListCreateWithPosition(): void
    {
        $expected = ['item' => ['id' => 'list1']];

        $this->plankaClient
            ->expects($this->once())
            ->method('post')
            ->with('test-api-key', '/api/boards/board1/lists', ['type' => 'active', 'position' => 100, 'name' => 'List'])
            ->willReturn($expected);

        $this->assertSame($expected, $this->service->manageList('test-api-key', 'create', 'board1', null, 'List', 100));
    }

    public function testManageListCreateRequiresBoardId(): void
    {
        $this->expectException(ValidationException::class);
        $this->service->manageList('test-api-key', 'create', null, null, 'Name');
    }

    public function testManageListUpdateSuccess(): void
    {
        $expected = ['item' => ['id' => 'list1', 'name' => 'Updated']];

        $this->plankaClient
            ->expects($this->once())
            ->method('patch')
            ->with('test-api-key', '/api/lists/list1', ['name' => 'Updated'])
            ->willReturn($expected);

        $this->assertSame($expected, $this->service->manageList('test-api-key', 'update', null, 'list1', 'Updated'));
    }

    public function testManageListUpdateWithPosition(): void
    {
        $expected = ['item' => ['id' => 'list1']];

        $this->plankaClient
            ->expects($this->once())
            ->method('patch')
            ->with('test-api-key', '/api/lists/list1', ['name' => 'Updated', 'position' => 200])
            ->willReturn($expected);

        $this->assertSame($expected, $this->service->manageList('test-api-key', 'update', null, 'list1', 'Updated', 200));
    }

    public function testManageListUpdateRequiresListId(): void
    {
        $this->expectException(ValidationException::class);
        $this->service->manageList('test-api-key', 'update', null, null, 'Name');
    }

    public function testManageListDeleteSuccess(): void
    {
        $this->plankaClient
            ->expects($this->once())
            ->method('delete')
            ->with('test-api-key', '/api/lists/list1')
            ->willReturn([]);

        $this->assertSame([], $this->service->manageList('test-api-key', 'delete', null, 'list1'));
    }

    public function testManageListDeleteRequiresListId(): void
    {
        $this->expectException(ValidationException::class);
        $this->service->manageList('test-api-key', 'delete');
    }

    public function testManageListGetSuccess(): void
    {
        $expected = ['item' => ['id' => 'list1', 'name' => 'To Do']];

        $this->plankaClient
            ->expects($this->once())
            ->method('get')
            ->with('test-api-key', '/api/lists/list1')
            ->willReturn($expected);

        $this->assertSame($expected, $this->service->manageList('test-api-key', 'get', null, 'list1'));
    }

    public function testManageListGetRequiresListId(): void
    {
        $this->expectException(ValidationException::class);
        $this->service->manageList('test-api-key', 'get');
    }

    public function testManageListGetCardsSuccess(): void
    {
        $expected = ['items' => [['id' => 'card1']]];

        $this->plankaClient
            ->expects($this->once())
            ->method('get')
            ->with('test-api-key', '/api/lists/list1/cards')
            ->willReturn($expected);

        $this->assertSame($expected, $this->service->manageList('test-api-key', 'get_cards', null, 'list1'));
    }

    public function testManageListGetCardsRequiresListId(): void
    {
        $this->expectException(ValidationException::class);
        $this->service->manageList('test-api-key', 'get_cards');
    }

    public function testManageListMoveCardsSuccess(): void
    {
        $expected = ['items' => []];

        $this->plankaClient
            ->expects($this->once())
            ->method('post')
            ->with('test-api-key', '/api/lists/list1/move-cards', ['listId' => 'list2'])
            ->willReturn($expected);

        $this->assertSame($expected, $this->service->manageList('test-api-key', 'move_cards', null, 'list1', null, null, 'list2'));
    }

    public function testManageListMoveCardsRequiresListId(): void
    {
        $this->expectException(ValidationException::class);
        $this->service->manageList('test-api-key', 'move_cards', null, null, null, null, 'list2');
    }

    public function testManageListMoveCardsRequiresToListId(): void
    {
        $this->expectException(ValidationException::class);
        $this->service->manageList('test-api-key', 'move_cards', null, 'list1');
    }

    public function testManageListClearSuccess(): void
    {
        $expected = [];

        $this->plankaClient
            ->expects($this->once())
            ->method('post')
            ->with('test-api-key', '/api/lists/list1/clear', [])
            ->willReturn($expected);

        $this->assertSame($expected, $this->service->manageList('test-api-key', 'clear', null, 'list1'));
    }

    public function testManageListClearRequiresListId(): void
    {
        $this->expectException(ValidationException::class);
        $this->service->manageList('test-api-key', 'clear');
    }

    public function testManageListInvalidAction(): void
    {
        $this->expectException(ValidationException::class);
        $this->service->manageList('test-api-key', 'invalid');
    }

    public function testManageListPropagatesAuthException(): void
    {
        $this->plankaClient->method('post')->willThrowException(new AuthenticationException('Unauthorized'));
        $this->expectException(AuthenticationException::class);
        $this->service->manageList('bad-key', 'create', 'board1', null, 'Name');
    }

    public function testManageListPropagatesApiException(): void
    {
        $this->plankaClient->method('post')->willThrowException(new PlankaApiException('Server error'));
        $this->expectException(PlankaApiException::class);
        $this->service->manageList('test-api-key', 'create', 'board1', null, 'Name');
    }

    public function testSortListSuccess(): void
    {
        $expected = ['items' => []];

        $this->plankaClient
            ->expects($this->once())
            ->method('post')
            ->with('test-api-key', '/api/lists/list1/sort', ['fieldName' => 'name'])
            ->willReturn($expected);

        $this->assertSame($expected, $this->service->sortList('test-api-key', 'list1', 'name'));
    }

    public function testSortListPropagatesAuthException(): void
    {
        $this->plankaClient->method('post')->willThrowException(new AuthenticationException('Unauthorized'));
        $this->expectException(AuthenticationException::class);
        $this->service->sortList('bad-key', 'list1', 'name');
    }

    public function testSortListPropagatesApiException(): void
    {
        $this->plankaClient->method('post')->willThrowException(new PlankaApiException('Server error'));
        $this->expectException(PlankaApiException::class);
        $this->service->sortList('test-api-key', 'list1', 'name');
    }

    public function testGetListSuccess(): void
    {
        $expected = ['item' => ['id' => 'list1']];

        $this->plankaClient
            ->expects($this->once())
            ->method('get')
            ->with('test-api-key', '/api/lists/list1')
            ->willReturn($expected);

        $this->assertSame($expected, $this->service->getList('test-api-key', 'list1'));
    }

    public function testGetListCardsSuccess(): void
    {
        $expected = ['items' => [['id' => 'card1']]];

        $this->plankaClient
            ->expects($this->once())
            ->method('get')
            ->with('test-api-key', '/api/lists/list1/cards')
            ->willReturn($expected);

        $this->assertSame($expected, $this->service->getListCards('test-api-key', 'list1'));
    }

    public function testMoveListCardsSuccess(): void
    {
        $expected = [];

        $this->plankaClient
            ->expects($this->once())
            ->method('post')
            ->with('test-api-key', '/api/lists/list1/move-cards', ['listId' => 'list2'])
            ->willReturn($expected);

        $this->assertSame($expected, $this->service->moveListCards('test-api-key', 'list1', 'list2'));
    }

    public function testClearListSuccess(): void
    {
        $expected = [];

        $this->plankaClient
            ->expects($this->once())
            ->method('post')
            ->with('test-api-key', '/api/lists/list1/clear', [])
            ->willReturn($expected);

        $this->assertSame($expected, $this->service->clearList('test-api-key', 'list1'));
    }
}
