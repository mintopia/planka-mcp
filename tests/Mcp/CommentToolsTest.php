<?php

declare(strict_types=1);

namespace App\Tests\Mcp;

use App\Domain\Comment\CommentService;
use App\Infrastructure\Http\ApiKeyProvider;
use App\Mcp\CommentTools;
use App\Planka\Exception\AuthenticationException;
use App\Planka\Exception\PlankaApiException;
use App\Planka\Exception\PlankaNotFoundException;
use App\Shared\Exception\ValidationException;
use Mcp\Exception\ToolCallException;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

final class CommentToolsTest extends TestCase
{
    private CommentService&MockObject $commentService;
    private ApiKeyProvider&MockObject $apiKeyProvider;
    private CommentTools $tools;

    protected function setUp(): void
    {
        $this->commentService = $this->createMock(CommentService::class);
        $this->apiKeyProvider = $this->createMock(ApiKeyProvider::class);
        $this->tools = new CommentTools($this->commentService, $this->apiKeyProvider);
    }

    // --- addComment ---

    public function testAddCommentSuccess(): void
    {
        $expected = ['item' => ['id' => 'action1', 'type' => 'commentCard', 'data' => ['text' => 'Hello']]];

        $this->apiKeyProvider
            ->expects($this->once())
            ->method('getApiKey')
            ->willReturn('test-api-key');

        $this->commentService
            ->expects($this->once())
            ->method('addComment')
            ->with('test-api-key', 'card1', 'Hello')
            ->willReturn($expected);

        $result = $this->tools->addComment('card1', 'Hello');

        $this->assertSame($expected, $result);
    }

    public function testAddCommentWithMarkdownSuccess(): void
    {
        $text = "## Update\n- Task done\n- Deployed";
        $expected = ['item' => ['id' => 'action2', 'data' => ['text' => $text]]];

        $this->apiKeyProvider->method('getApiKey')->willReturn('test-api-key');

        $this->commentService
            ->expects($this->once())
            ->method('addComment')
            ->with('test-api-key', 'card1', $text)
            ->willReturn($expected);

        $result = $this->tools->addComment('card1', $text);

        $this->assertSame($expected, $result);
    }

    public function testAddCommentWithEmptyTextThrowsToolCallException(): void
    {
        $this->apiKeyProvider->expects($this->never())->method('getApiKey');
        $this->commentService->expects($this->never())->method('addComment');

        $this->expectException(ToolCallException::class);
        $this->expectExceptionMessage('Comment text cannot be empty.');

        $this->tools->addComment('card1', '');
    }

    public function testAddCommentWithWhitespaceOnlyTextThrowsToolCallException(): void
    {
        $this->apiKeyProvider->expects($this->never())->method('getApiKey');
        $this->commentService->expects($this->never())->method('addComment');

        $this->expectException(ToolCallException::class);
        $this->expectExceptionMessage('Comment text cannot be empty.');

        $this->tools->addComment('card1', '   ');
    }

    public function testAddCommentWithTabsAndNewlinesOnlyThrowsToolCallException(): void
    {
        $this->apiKeyProvider->expects($this->never())->method('getApiKey');
        $this->commentService->expects($this->never())->method('addComment');

        $this->expectException(ToolCallException::class);
        $this->expectExceptionMessage('Comment text cannot be empty.');

        $this->tools->addComment('card1', "\t\n\r");
    }

    public function testAddCommentMissingApiKeyThrowsToolCallException(): void
    {
        $this->apiKeyProvider
            ->expects($this->once())
            ->method('getApiKey')
            ->willThrowException(new ValidationException('Planka API key required.'));

        $this->commentService->expects($this->never())->method('addComment');

        $this->expectException(ToolCallException::class);
        $this->expectExceptionMessage('Planka API key required.');

        $this->tools->addComment('card1', 'Hello');
    }

    public function testAddCommentWrapsAuthExceptionInToolCallException(): void
    {
        $this->apiKeyProvider->method('getApiKey')->willReturn('bad-key');

        $this->commentService
            ->method('addComment')
            ->willThrowException(new AuthenticationException('Unauthorized'));

        $this->expectException(ToolCallException::class);
        $this->expectExceptionMessage('Unauthorized');

        $this->tools->addComment('card1', 'Hello');
    }

    public function testAddCommentWrapsPlankaApiExceptionInToolCallException(): void
    {
        $this->apiKeyProvider->method('getApiKey')->willReturn('test-api-key');

        $this->commentService
            ->method('addComment')
            ->willThrowException(new PlankaApiException('Server error'));

        $this->expectException(ToolCallException::class);
        $this->expectExceptionMessage('Server error');

        $this->tools->addComment('card1', 'Hello');
    }

    public function testAddCommentWrapsNotFoundExceptionInToolCallException(): void
    {
        $this->apiKeyProvider->method('getApiKey')->willReturn('test-api-key');

        $this->commentService
            ->method('addComment')
            ->willThrowException(new PlankaNotFoundException('Not found', 404));

        $this->expectException(ToolCallException::class);
        $this->expectExceptionMessage('Not found');

        $this->tools->addComment('card1', 'Hello');
    }

    // --- getComments ---

    public function testGetCommentsSuccess(): void
    {
        $expected = ['items' => [['id' => 'action1', 'type' => 'commentCard']]];

        $this->apiKeyProvider
            ->expects($this->once())
            ->method('getApiKey')
            ->willReturn('test-api-key');

        $this->commentService
            ->expects($this->once())
            ->method('getComments')
            ->with('test-api-key', 'card1')
            ->willReturn($expected);

        $result = $this->tools->getComments('card1');

        $this->assertSame($expected, $result);
    }

    public function testGetCommentsReturnsEmptyWhenNone(): void
    {
        $this->apiKeyProvider->method('getApiKey')->willReturn('test-api-key');

        $this->commentService
            ->expects($this->once())
            ->method('getComments')
            ->with('test-api-key', 'card1')
            ->willReturn([]);

        $result = $this->tools->getComments('card1');

        $this->assertSame([], $result);
    }

    public function testGetCommentsMissingApiKeyThrowsToolCallException(): void
    {
        $this->apiKeyProvider
            ->expects($this->once())
            ->method('getApiKey')
            ->willThrowException(new ValidationException('Planka API key required.'));

        $this->commentService->expects($this->never())->method('getComments');

        $this->expectException(ToolCallException::class);
        $this->expectExceptionMessage('Planka API key required.');

        $this->tools->getComments('card1');
    }

    public function testGetCommentsWrapsAuthExceptionInToolCallException(): void
    {
        $this->apiKeyProvider->method('getApiKey')->willReturn('bad-key');

        $this->commentService
            ->method('getComments')
            ->willThrowException(new AuthenticationException('Unauthorized'));

        $this->expectException(ToolCallException::class);
        $this->expectExceptionMessage('Unauthorized');

        $this->tools->getComments('card1');
    }

    public function testGetCommentsWrapsPlankaApiExceptionInToolCallException(): void
    {
        $this->apiKeyProvider->method('getApiKey')->willReturn('test-api-key');

        $this->commentService
            ->method('getComments')
            ->willThrowException(new PlankaApiException('Server error'));

        $this->expectException(ToolCallException::class);
        $this->expectExceptionMessage('Server error');

        $this->tools->getComments('card1');
    }
}
