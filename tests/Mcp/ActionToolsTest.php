<?php

declare(strict_types=1);

namespace App\Tests\Mcp;

use App\Domain\Action\ActionService;
use App\Infrastructure\Http\ApiKeyProvider;
use App\Mcp\ActionTools;
use App\Planka\Exception\AuthenticationException;
use App\Planka\Exception\PlankaApiException;
use App\Planka\Exception\PlankaNotFoundException;
use App\Shared\Exception\ValidationException;
use Mcp\Exception\ToolCallException;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

final class ActionToolsTest extends TestCase
{
    private ActionService&MockObject $actionService;
    private ApiKeyProvider&MockObject $apiKeyProvider;
    private ActionTools $tools;

    protected function setUp(): void
    {
        $this->actionService = $this->createMock(ActionService::class);
        $this->apiKeyProvider = $this->createMock(ApiKeyProvider::class);
        $this->tools = new ActionTools($this->actionService, $this->apiKeyProvider);
    }

    // -------------------------------------------------------------------------
    // getActions: board
    // -------------------------------------------------------------------------

    public function testGetActionsBoardSuccess(): void
    {
        $expected = ['items' => [['id' => 'act1', 'type' => 'createCard']]];

        $this->apiKeyProvider
            ->expects($this->once())
            ->method('getApiKey')
            ->willReturn('test-api-key');

        $this->actionService
            ->expects($this->once())
            ->method('getBoardActions')
            ->with('test-api-key', 'board1')
            ->willReturn($expected);

        $result = $this->tools->getActions('board', 'board1');

        $this->assertSame($expected, $result);
    }

    // -------------------------------------------------------------------------
    // getActions: card
    // -------------------------------------------------------------------------

    public function testGetActionsCardSuccess(): void
    {
        $expected = ['items' => [['id' => 'act2', 'type' => 'moveCard']]];

        $this->apiKeyProvider
            ->expects($this->once())
            ->method('getApiKey')
            ->willReturn('test-api-key');

        $this->actionService
            ->expects($this->once())
            ->method('getCardActions')
            ->with('test-api-key', 'card1')
            ->willReturn($expected);

        $result = $this->tools->getActions('card', 'card1');

        $this->assertSame($expected, $result);
    }

    // -------------------------------------------------------------------------
    // getActions: invalid type
    // -------------------------------------------------------------------------

    public function testGetActionsInvalidTypeThrowsToolCallException(): void
    {
        $this->apiKeyProvider->method('getApiKey')->willReturn('test-api-key');

        $this->expectException(ToolCallException::class);
        $this->expectExceptionMessage('Invalid type "project". Must be: board, card');

        $this->tools->getActions('project', 'proj1');
    }

    // -------------------------------------------------------------------------
    // getActions: exception wrapping
    // -------------------------------------------------------------------------

    public function testGetActionsMissingApiKeyThrowsToolCallException(): void
    {
        $this->apiKeyProvider
            ->method('getApiKey')
            ->willThrowException(new ValidationException('Planka API key required.'));

        $this->expectException(ToolCallException::class);
        $this->expectExceptionMessage('Planka API key required.');

        $this->tools->getActions('board', 'board1');
    }

    public function testGetActionsWrapsAuthExceptionInToolCallException(): void
    {
        $this->apiKeyProvider->method('getApiKey')->willReturn('bad-key');

        $this->actionService
            ->method('getBoardActions')
            ->willThrowException(new AuthenticationException('Unauthorized'));

        $this->expectException(ToolCallException::class);
        $this->expectExceptionMessage('Unauthorized');

        $this->tools->getActions('board', 'board1');
    }

    public function testGetActionsWrapsPlankaApiExceptionInToolCallException(): void
    {
        $this->apiKeyProvider->method('getApiKey')->willReturn('test-api-key');

        $this->actionService
            ->method('getCardActions')
            ->willThrowException(new PlankaApiException('Server error'));

        $this->expectException(ToolCallException::class);
        $this->expectExceptionMessage('Server error');

        $this->tools->getActions('card', 'card1');
    }

    public function testGetActionsWrapsNotFoundExceptionInToolCallException(): void
    {
        $this->apiKeyProvider->method('getApiKey')->willReturn('test-api-key');

        $this->actionService
            ->method('getBoardActions')
            ->willThrowException(new PlankaNotFoundException('Not found', 404));

        $this->expectException(ToolCallException::class);
        $this->expectExceptionMessage('Not found');

        $this->tools->getActions('board', 'board1');
    }
}
