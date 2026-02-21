<?php

declare(strict_types=1);

namespace App\Tests\Mcp;

use App\Domain\BoardList\ListService;
use App\Infrastructure\Http\ApiKeyProvider;
use App\Mcp\ListTools;
use App\Planka\Exception\AuthenticationException;
use App\Planka\Exception\PlankaApiException;
use App\Planka\Exception\PlankaNotFoundException;
use App\Shared\Exception\ValidationException;
use Mcp\Exception\ToolCallException;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

final class ListToolsTest extends TestCase
{
    private ListService&MockObject $listService;
    private ApiKeyProvider&MockObject $apiKeyProvider;
    private ListTools $tools;

    protected function setUp(): void
    {
        $this->listService = $this->createMock(ListService::class);
        $this->apiKeyProvider = $this->createMock(ApiKeyProvider::class);
        $this->tools = new ListTools($this->listService, $this->apiKeyProvider);
    }

    // --- manageLists: create ---

    public function testManageListsCreateSuccess(): void
    {
        $expected = ['item' => ['id' => 'list1', 'name' => 'To Do', 'position' => 1]];

        $this->apiKeyProvider
            ->expects($this->once())
            ->method('getApiKey')
            ->willReturn('test-api-key');

        $this->listService
            ->expects($this->once())
            ->method('manageList')
            ->with('test-api-key', 'create', 'board1', null, 'To Do', 1)
            ->willReturn($expected);

        $result = $this->tools->manageLists('create', 'board1', null, 'To Do', 1);

        $this->assertSame($expected, $result);
    }

    public function testManageListsCreateWithNoOptionalFieldsSuccess(): void
    {
        $expected = ['item' => ['id' => 'list1']];

        $this->apiKeyProvider->method('getApiKey')->willReturn('test-api-key');

        $this->listService
            ->expects($this->once())
            ->method('manageList')
            ->with('test-api-key', 'create', 'board1', null, null, null)
            ->willReturn($expected);

        $result = $this->tools->manageLists('create', 'board1');

        $this->assertSame($expected, $result);
    }

    // --- manageLists: update ---

    public function testManageListsUpdateSuccess(): void
    {
        $expected = ['item' => ['id' => 'list1', 'name' => 'In Progress', 'position' => 2]];

        $this->apiKeyProvider
            ->expects($this->once())
            ->method('getApiKey')
            ->willReturn('test-api-key');

        $this->listService
            ->expects($this->once())
            ->method('manageList')
            ->with('test-api-key', 'update', null, 'list1', 'In Progress', 2)
            ->willReturn($expected);

        $result = $this->tools->manageLists('update', null, 'list1', 'In Progress', 2);

        $this->assertSame($expected, $result);
    }

    // --- manageLists: delete ---

    public function testManageListsDeleteSuccess(): void
    {
        $this->apiKeyProvider
            ->expects($this->once())
            ->method('getApiKey')
            ->willReturn('test-api-key');

        $this->listService
            ->expects($this->once())
            ->method('manageList')
            ->with('test-api-key', 'delete', null, 'list1', null, null)
            ->willReturn([]);

        $result = $this->tools->manageLists('delete', null, 'list1');

        $this->assertSame([], $result);
    }

    // --- manageLists: missing API key ---

    public function testManageListsMissingApiKeyThrowsToolCallException(): void
    {
        $this->apiKeyProvider
            ->expects($this->once())
            ->method('getApiKey')
            ->willThrowException(new ValidationException('Planka API key required.'));

        $this->listService->expects($this->never())->method('manageList');

        $this->expectException(ToolCallException::class);
        $this->expectExceptionMessage('Planka API key required.');

        $this->tools->manageLists('create', 'board1');
    }

    // --- manageLists: exception wrapping ---

    public function testManageListsWrapsAuthExceptionInToolCallException(): void
    {
        $this->apiKeyProvider->method('getApiKey')->willReturn('bad-key');

        $this->listService
            ->method('manageList')
            ->willThrowException(new AuthenticationException('Unauthorized'));

        $this->expectException(ToolCallException::class);
        $this->expectExceptionMessage('Unauthorized');

        $this->tools->manageLists('create', 'board1');
    }

    public function testManageListsWrapsPlankaApiExceptionInToolCallException(): void
    {
        $this->apiKeyProvider->method('getApiKey')->willReturn('test-api-key');

        $this->listService
            ->method('manageList')
            ->willThrowException(new PlankaApiException('Server error'));

        $this->expectException(ToolCallException::class);
        $this->expectExceptionMessage('Server error');

        $this->tools->manageLists('update', null, 'list1', 'Done');
    }

    public function testManageListsWrapsValidationExceptionFromDomainInToolCallException(): void
    {
        $this->apiKeyProvider->method('getApiKey')->willReturn('test-api-key');

        $this->listService
            ->method('manageList')
            ->willThrowException(new ValidationException('boardId required for create'));

        $this->expectException(ToolCallException::class);
        $this->expectExceptionMessage('boardId required for create');

        $this->tools->manageLists('create');
    }

    public function testManageListsWrapsNotFoundExceptionInToolCallException(): void
    {
        $this->apiKeyProvider->method('getApiKey')->willReturn('test-api-key');

        $this->listService
            ->method('manageList')
            ->willThrowException(new PlankaNotFoundException('Not found', 404));

        $this->expectException(ToolCallException::class);
        $this->expectExceptionMessage('Not found');

        $this->tools->manageLists('update', null, 'list1', 'Done');
    }

    // --- manageLists: get ---

    public function testManageListsGetSuccess(): void
    {
        $expected = ['item' => ['id' => 'list1', 'name' => 'To Do']];

        $this->apiKeyProvider
            ->expects($this->once())
            ->method('getApiKey')
            ->willReturn('test-api-key');

        $this->listService
            ->expects($this->once())
            ->method('manageList')
            ->with('test-api-key', 'get', null, 'list1', null, null)
            ->willReturn($expected);

        $result = $this->tools->manageLists('get', null, 'list1');

        $this->assertSame($expected, $result);
    }

    // --- sortList ---

    public function testSortListSuccess(): void
    {
        $expected = ['items' => [['id' => 'card1'], ['id' => 'card2']]];

        $this->apiKeyProvider
            ->expects($this->once())
            ->method('getApiKey')
            ->willReturn('test-api-key');

        $this->listService
            ->expects($this->once())
            ->method('sortList')
            ->with('test-api-key', 'list1', 'name')
            ->willReturn($expected);

        $result = $this->tools->sortList('list1', 'name');

        $this->assertSame($expected, $result);
    }

    public function testSortListMissingApiKeyThrowsToolCallException(): void
    {
        $this->apiKeyProvider
            ->expects($this->once())
            ->method('getApiKey')
            ->willThrowException(new ValidationException('Planka API key required.'));

        $this->listService->expects($this->never())->method('sortList');

        $this->expectException(ToolCallException::class);
        $this->expectExceptionMessage('Planka API key required.');

        $this->tools->sortList('list1', 'name');
    }

    public function testSortListWrapsAuthExceptionInToolCallException(): void
    {
        $this->apiKeyProvider->method('getApiKey')->willReturn('bad-key');

        $this->listService
            ->method('sortList')
            ->willThrowException(new AuthenticationException('Unauthorized'));

        $this->expectException(ToolCallException::class);
        $this->expectExceptionMessage('Unauthorized');

        $this->tools->sortList('list1', 'name');
    }

    public function testSortListWrapsPlankaApiExceptionInToolCallException(): void
    {
        $this->apiKeyProvider->method('getApiKey')->willReturn('test-api-key');

        $this->listService
            ->method('sortList')
            ->willThrowException(new PlankaApiException('Server error'));

        $this->expectException(ToolCallException::class);
        $this->expectExceptionMessage('Server error');

        $this->tools->sortList('list1', 'dueDate');
    }

    public function testSortListWrapsNotFoundExceptionInToolCallException(): void
    {
        $this->apiKeyProvider->method('getApiKey')->willReturn('test-api-key');

        $this->listService
            ->method('sortList')
            ->willThrowException(new PlankaNotFoundException('Not found', 404));

        $this->expectException(ToolCallException::class);
        $this->expectExceptionMessage('Not found');

        $this->tools->sortList('list1', 'createdAt');
    }
}
