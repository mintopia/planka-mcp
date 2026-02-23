<?php

declare(strict_types=1);

namespace App\Tests\Mcp;

use App\Domain\Board\BoardService;
use App\Domain\Project\ProjectService;
use App\Infrastructure\Http\ApiKeyProvider;
use App\Mcp\BoardTools;
use App\Planka\Exception\AuthenticationException;
use App\Planka\Exception\PlankaApiException;
use App\Planka\Exception\PlankaNotFoundException;
use App\Shared\Exception\ValidationException;
use Mcp\Exception\ToolCallException;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

final class BoardToolsTest extends TestCase
{
    private const string API_KEY = 'test-api-key';
    private const string BOARD_ID = 'board-abc123';

    private ProjectService&MockObject $projectService;
    private BoardService&MockObject $boardService;
    private ApiKeyProvider&MockObject $apiKeyProvider;
    private BoardTools $tools;

    protected function setUp(): void
    {
        $this->projectService = $this->createMock(ProjectService::class);
        $this->boardService = $this->createMock(BoardService::class);
        $this->apiKeyProvider = $this->createMock(ApiKeyProvider::class);
        $this->tools = new BoardTools($this->projectService, $this->boardService, $this->apiKeyProvider);
    }

    // -------------------------------------------------------------------------
    // getStructure()
    // -------------------------------------------------------------------------

    public function testGetStructureSuccess(): void
    {
        $expected = ['items' => [['id' => 'p1', 'name' => 'Project One']]];

        $this->apiKeyProvider
            ->expects($this->once())
            ->method('getApiKey')
            ->willReturn(self::API_KEY);

        $this->projectService
            ->expects($this->once())
            ->method('getStructure')
            ->with(self::API_KEY)
            ->willReturn($expected);

        $this->assertSame($expected, $this->tools->getStructure());
    }

    public function testGetStructureMissingApiKeyThrowsToolCallException(): void
    {
        $this->apiKeyProvider
            ->method('getApiKey')
            ->willThrowException(new ValidationException(
                'Planka API key required. Send via "Authorization: Bearer <key>" or "X-Api-Key: <key>" header.',
            ));

        $this->projectService->expects($this->never())->method('getStructure');

        $this->expectException(ToolCallException::class);

        $this->tools->getStructure();
    }

    public function testGetStructureWrapsAuthExceptionInToolCallException(): void
    {
        $this->apiKeyProvider
            ->method('getApiKey')
            ->willReturn('bad-key');

        $this->projectService
            ->method('getStructure')
            ->willThrowException(new AuthenticationException('Unauthorized', 401));

        $this->expectException(ToolCallException::class);
        $this->expectExceptionMessage('Unauthorized');

        $this->tools->getStructure();
    }

    public function testGetStructureWrapsPlankaApiExceptionInToolCallException(): void
    {
        $this->apiKeyProvider
            ->method('getApiKey')
            ->willReturn(self::API_KEY);

        $this->projectService
            ->method('getStructure')
            ->willThrowException(new PlankaApiException('Server error', 500));

        $this->expectException(ToolCallException::class);
        $this->expectExceptionMessage('Server error');

        $this->tools->getStructure();
    }

    public function testGetStructureWrapsNotFoundExceptionInToolCallException(): void
    {
        $this->apiKeyProvider
            ->method('getApiKey')
            ->willReturn(self::API_KEY);

        $this->projectService
            ->method('getStructure')
            ->willThrowException(new PlankaNotFoundException('Not found', 404));

        $this->expectException(ToolCallException::class);
        $this->expectExceptionMessage('Not found');

        $this->tools->getStructure();
    }

    // -------------------------------------------------------------------------
    // getBoard()
    // -------------------------------------------------------------------------

    public function testGetBoardSuccess(): void
    {
        $expected = ['item' => ['id' => self::BOARD_ID, 'name' => 'My Board']];

        $this->apiKeyProvider
            ->expects($this->once())
            ->method('getApiKey')
            ->willReturn(self::API_KEY);

        $this->boardService
            ->expects($this->once())
            ->method('getBoard')
            ->with(self::API_KEY, self::BOARD_ID)
            ->willReturn($expected);

        $this->assertSame($expected, $this->tools->getBoard(self::BOARD_ID));
    }

    public function testGetBoardMissingApiKeyThrowsToolCallException(): void
    {
        $this->apiKeyProvider
            ->method('getApiKey')
            ->willThrowException(new ValidationException(
                'Planka API key required. Send via "Authorization: Bearer <key>" or "X-Api-Key: <key>" header.',
            ));

        $this->boardService->expects($this->never())->method('getBoard');

        $this->expectException(ToolCallException::class);

        $this->tools->getBoard(self::BOARD_ID);
    }

    public function testGetBoardWrapsAuthExceptionInToolCallException(): void
    {
        $this->apiKeyProvider
            ->method('getApiKey')
            ->willReturn('bad-key');

        $this->boardService
            ->method('getBoard')
            ->willThrowException(new AuthenticationException('Unauthorized', 401));

        $this->expectException(ToolCallException::class);
        $this->expectExceptionMessage('Unauthorized');

        $this->tools->getBoard(self::BOARD_ID);
    }

    public function testGetBoardWrapsPlankaApiExceptionInToolCallException(): void
    {
        $this->apiKeyProvider
            ->method('getApiKey')
            ->willReturn(self::API_KEY);

        $this->boardService
            ->method('getBoard')
            ->willThrowException(new PlankaApiException('Server error', 500));

        $this->expectException(ToolCallException::class);
        $this->expectExceptionMessage('Server error');

        $this->tools->getBoard(self::BOARD_ID);
    }

    public function testGetBoardWrapsNotFoundExceptionInToolCallException(): void
    {
        $this->apiKeyProvider
            ->method('getApiKey')
            ->willReturn(self::API_KEY);

        $this->boardService
            ->method('getBoard')
            ->willThrowException(new PlankaNotFoundException('Not found', 404));

        $this->expectException(ToolCallException::class);
        $this->expectExceptionMessage('Not found');

        $this->tools->getBoard(self::BOARD_ID);
    }

    // -------------------------------------------------------------------------
    // manageBoards: create
    // -------------------------------------------------------------------------

    public function testManageBoardsCreateSuccess(): void
    {
        $expected = ['item' => ['id' => self::BOARD_ID, 'name' => 'Sprint Board']];

        $this->apiKeyProvider->method('getApiKey')->willReturn(self::API_KEY);

        $this->boardService
            ->expects($this->once())
            ->method('createBoard')
            ->with(self::API_KEY, 'proj1', 'Sprint Board', 1)
            ->willReturn($expected);

        $result = $this->tools->manageBoards('create', 'proj1', null, 'Sprint Board', 1);

        $this->assertSame($expected, $result);
    }

    public function testManageBoardsCreateWithoutProjectIdThrowsToolCallException(): void
    {
        $this->apiKeyProvider->method('getApiKey')->willReturn(self::API_KEY);
        $this->boardService->expects($this->never())->method('createBoard');

        $this->expectException(ToolCallException::class);
        $this->expectExceptionMessage('projectId required for create');

        $this->tools->manageBoards('create', null, null, 'Name');
    }

    public function testManageBoardsCreateWithoutNameThrowsToolCallException(): void
    {
        $this->apiKeyProvider->method('getApiKey')->willReturn(self::API_KEY);
        $this->boardService->expects($this->never())->method('createBoard');

        $this->expectException(ToolCallException::class);
        $this->expectExceptionMessage('name required for create');

        $this->tools->manageBoards('create', 'proj1');
    }

    // -------------------------------------------------------------------------
    // manageBoards: get
    // -------------------------------------------------------------------------

    public function testManageBoardsGetSuccess(): void
    {
        $expected = ['item' => ['id' => self::BOARD_ID, 'name' => 'My Board']];

        $this->apiKeyProvider->method('getApiKey')->willReturn(self::API_KEY);

        $this->boardService
            ->expects($this->once())
            ->method('getBoard')
            ->with(self::API_KEY, self::BOARD_ID)
            ->willReturn($expected);

        $result = $this->tools->manageBoards('get', null, self::BOARD_ID);

        $this->assertSame($expected, $result);
    }

    public function testManageBoardsGetWithoutBoardIdThrowsToolCallException(): void
    {
        $this->apiKeyProvider->method('getApiKey')->willReturn(self::API_KEY);
        $this->boardService->expects($this->never())->method('getBoard');

        $this->expectException(ToolCallException::class);
        $this->expectExceptionMessage('boardId required for get');

        $this->tools->manageBoards('get');
    }

    // -------------------------------------------------------------------------
    // manageBoards: update
    // -------------------------------------------------------------------------

    public function testManageBoardsUpdateSuccess(): void
    {
        $expected = ['item' => ['id' => self::BOARD_ID, 'name' => 'Updated']];

        $this->apiKeyProvider->method('getApiKey')->willReturn(self::API_KEY);

        $this->boardService
            ->expects($this->once())
            ->method('updateBoard')
            ->with(self::API_KEY, self::BOARD_ID, 'Updated', null)
            ->willReturn($expected);

        $result = $this->tools->manageBoards('update', null, self::BOARD_ID, 'Updated');

        $this->assertSame($expected, $result);
    }

    public function testManageBoardsUpdateWithoutBoardIdThrowsToolCallException(): void
    {
        $this->apiKeyProvider->method('getApiKey')->willReturn(self::API_KEY);
        $this->boardService->expects($this->never())->method('updateBoard');

        $this->expectException(ToolCallException::class);
        $this->expectExceptionMessage('boardId required for update');

        $this->tools->manageBoards('update');
    }

    // -------------------------------------------------------------------------
    // manageBoards: delete
    // -------------------------------------------------------------------------

    public function testManageBoardsDeleteSuccess(): void
    {
        $this->apiKeyProvider->method('getApiKey')->willReturn(self::API_KEY);

        $this->boardService
            ->expects($this->once())
            ->method('deleteBoard')
            ->with(self::API_KEY, self::BOARD_ID)
            ->willReturn([]);

        $result = $this->tools->manageBoards('delete', null, self::BOARD_ID);

        $this->assertSame([], $result);
    }

    public function testManageBoardsDeleteWithoutBoardIdThrowsToolCallException(): void
    {
        $this->apiKeyProvider->method('getApiKey')->willReturn(self::API_KEY);
        $this->boardService->expects($this->never())->method('deleteBoard');

        $this->expectException(ToolCallException::class);
        $this->expectExceptionMessage('boardId required for delete');

        $this->tools->manageBoards('delete');
    }

    // -------------------------------------------------------------------------
    // manageBoards: invalid action
    // -------------------------------------------------------------------------

    public function testManageBoardsInvalidActionThrowsToolCallException(): void
    {
        $this->apiKeyProvider->method('getApiKey')->willReturn(self::API_KEY);

        $this->expectException(ToolCallException::class);
        $this->expectExceptionMessage('Invalid action "archive". Must be: create, get, update, delete');

        $this->tools->manageBoards('archive');
    }

    // -------------------------------------------------------------------------
    // manageBoards: missing API key + exception wrapping
    // -------------------------------------------------------------------------

    public function testManageBoardsMissingApiKeyThrowsToolCallException(): void
    {
        $this->apiKeyProvider
            ->method('getApiKey')
            ->willThrowException(new ValidationException('Planka API key required.'));

        $this->expectException(ToolCallException::class);
        $this->expectExceptionMessage('Planka API key required.');

        $this->tools->manageBoards('create', 'proj1', null, 'Name');
    }

    public function testManageBoardsWrapsAuthExceptionInToolCallException(): void
    {
        $this->apiKeyProvider->method('getApiKey')->willReturn('bad-key');

        $this->boardService
            ->method('createBoard')
            ->willThrowException(new AuthenticationException('Unauthorized', 401));

        $this->expectException(ToolCallException::class);
        $this->expectExceptionMessage('Unauthorized');

        $this->tools->manageBoards('create', 'proj1', null, 'Name');
    }

    public function testManageBoardsWrapsPlankaApiExceptionInToolCallException(): void
    {
        $this->apiKeyProvider->method('getApiKey')->willReturn(self::API_KEY);

        $this->boardService
            ->method('getBoard')
            ->willThrowException(new PlankaApiException('Server error', 500));

        $this->expectException(ToolCallException::class);
        $this->expectExceptionMessage('Server error');

        $this->tools->manageBoards('get', null, self::BOARD_ID);
    }

    public function testManageBoardsWrapsNotFoundExceptionInToolCallException(): void
    {
        $this->apiKeyProvider->method('getApiKey')->willReturn(self::API_KEY);

        $this->boardService
            ->method('getBoard')
            ->willThrowException(new PlankaNotFoundException('Not found', 404));

        $this->expectException(ToolCallException::class);
        $this->expectExceptionMessage('Not found');

        $this->tools->manageBoards('get', null, self::BOARD_ID);
    }

    // -------------------------------------------------------------------------
    // manageBoardMemberships: add
    // -------------------------------------------------------------------------

    public function testManageBoardMembershipsAddSuccess(): void
    {
        $expected = ['item' => ['id' => 'mbr1', 'userId' => 'user1', 'role' => 'editor']];

        $this->apiKeyProvider->method('getApiKey')->willReturn(self::API_KEY);

        $this->boardService
            ->expects($this->once())
            ->method('addBoardMember')
            ->with(self::API_KEY, self::BOARD_ID, 'user1', 'editor')
            ->willReturn($expected);

        $result = $this->tools->manageBoardMemberships('add', self::BOARD_ID, null, 'user1', 'editor');

        $this->assertSame($expected, $result);
    }

    public function testManageBoardMembershipsAddWithViewerRoleSuccess(): void
    {
        $expected = ['item' => ['id' => 'mbr1', 'userId' => 'user1', 'role' => 'viewer']];

        $this->apiKeyProvider->method('getApiKey')->willReturn(self::API_KEY);

        $this->boardService
            ->expects($this->once())
            ->method('addBoardMember')
            ->with(self::API_KEY, self::BOARD_ID, 'user1', 'viewer')
            ->willReturn($expected);

        $result = $this->tools->manageBoardMemberships('add', self::BOARD_ID, null, 'user1', 'viewer');

        $this->assertSame($expected, $result);
    }

    public function testManageBoardMembershipsAddWithoutBoardIdThrowsToolCallException(): void
    {
        $this->apiKeyProvider->method('getApiKey')->willReturn(self::API_KEY);
        $this->boardService->expects($this->never())->method('addBoardMember');

        $this->expectException(ToolCallException::class);
        $this->expectExceptionMessage('boardId required for add');

        $this->tools->manageBoardMemberships('add', null, null, 'user1');
    }

    public function testManageBoardMembershipsAddWithoutUserIdThrowsToolCallException(): void
    {
        $this->apiKeyProvider->method('getApiKey')->willReturn(self::API_KEY);
        $this->boardService->expects($this->never())->method('addBoardMember');

        $this->expectException(ToolCallException::class);
        $this->expectExceptionMessage('userId required for add');

        $this->tools->manageBoardMemberships('add', self::BOARD_ID);
    }

    // -------------------------------------------------------------------------
    // manageBoardMemberships: update
    // -------------------------------------------------------------------------

    public function testManageBoardMembershipsUpdateSuccess(): void
    {
        $expected = ['item' => ['id' => 'mbr1', 'role' => 'viewer']];

        $this->apiKeyProvider->method('getApiKey')->willReturn(self::API_KEY);

        $this->boardService
            ->expects($this->once())
            ->method('updateBoardMembership')
            ->with(self::API_KEY, 'mbr1', 'viewer')
            ->willReturn($expected);

        $result = $this->tools->manageBoardMemberships('update', null, 'mbr1', null, 'viewer');

        $this->assertSame($expected, $result);
    }

    public function testManageBoardMembershipsUpdateWithoutMembershipIdThrowsToolCallException(): void
    {
        $this->apiKeyProvider->method('getApiKey')->willReturn(self::API_KEY);
        $this->boardService->expects($this->never())->method('updateBoardMembership');

        $this->expectException(ToolCallException::class);
        $this->expectExceptionMessage('membershipId required for update');

        $this->tools->manageBoardMemberships('update');
    }

    // -------------------------------------------------------------------------
    // manageBoardMemberships: remove
    // -------------------------------------------------------------------------

    public function testManageBoardMembershipsRemoveSuccess(): void
    {
        $this->apiKeyProvider->method('getApiKey')->willReturn(self::API_KEY);

        $this->boardService
            ->expects($this->once())
            ->method('removeBoardMember')
            ->with(self::API_KEY, 'mbr1')
            ->willReturn([]);

        $result = $this->tools->manageBoardMemberships('remove', null, 'mbr1');

        $this->assertSame([], $result);
    }

    public function testManageBoardMembershipsRemoveWithoutMembershipIdThrowsToolCallException(): void
    {
        $this->apiKeyProvider->method('getApiKey')->willReturn(self::API_KEY);
        $this->boardService->expects($this->never())->method('removeBoardMember');

        $this->expectException(ToolCallException::class);
        $this->expectExceptionMessage('membershipId required for remove');

        $this->tools->manageBoardMemberships('remove');
    }

    // -------------------------------------------------------------------------
    // manageBoardMemberships: invalid action
    // -------------------------------------------------------------------------

    public function testManageBoardMembershipsInvalidActionThrowsToolCallException(): void
    {
        $this->apiKeyProvider->method('getApiKey')->willReturn(self::API_KEY);

        $this->expectException(ToolCallException::class);
        $this->expectExceptionMessage('Invalid action "grant". Must be: add, update, remove');

        $this->tools->manageBoardMemberships('grant');
    }

    // -------------------------------------------------------------------------
    // manageBoardMemberships: missing API key + exception wrapping
    // -------------------------------------------------------------------------

    public function testManageBoardMembershipsMissingApiKeyThrowsToolCallException(): void
    {
        $this->apiKeyProvider
            ->method('getApiKey')
            ->willThrowException(new ValidationException('Planka API key required.'));

        $this->expectException(ToolCallException::class);
        $this->expectExceptionMessage('Planka API key required.');

        $this->tools->manageBoardMemberships('add', self::BOARD_ID, null, 'user1');
    }

    public function testManageBoardMembershipsWrapsAuthExceptionInToolCallException(): void
    {
        $this->apiKeyProvider->method('getApiKey')->willReturn('bad-key');

        $this->boardService
            ->method('addBoardMember')
            ->willThrowException(new AuthenticationException('Unauthorized', 401));

        $this->expectException(ToolCallException::class);
        $this->expectExceptionMessage('Unauthorized');

        $this->tools->manageBoardMemberships('add', self::BOARD_ID, null, 'user1');
    }

    public function testManageBoardMembershipsWrapsPlankaApiExceptionInToolCallException(): void
    {
        $this->apiKeyProvider->method('getApiKey')->willReturn(self::API_KEY);

        $this->boardService
            ->method('removeBoardMember')
            ->willThrowException(new PlankaApiException('Server error', 500));

        $this->expectException(ToolCallException::class);
        $this->expectExceptionMessage('Server error');

        $this->tools->manageBoardMemberships('remove', null, 'mbr1');
    }

    public function testManageBoardMembershipsWrapsNotFoundExceptionInToolCallException(): void
    {
        $this->apiKeyProvider->method('getApiKey')->willReturn(self::API_KEY);

        $this->boardService
            ->method('addBoardMember')
            ->willThrowException(new PlankaNotFoundException('Not found', 404));

        $this->expectException(ToolCallException::class);
        $this->expectExceptionMessage('Not found');

        $this->tools->manageBoardMemberships('add', self::BOARD_ID, null, 'user1');
    }
}
