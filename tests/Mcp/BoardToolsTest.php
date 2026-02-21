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
}
