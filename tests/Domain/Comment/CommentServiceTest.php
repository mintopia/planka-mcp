<?php

declare(strict_types=1);

namespace App\Tests\Domain\Comment;

use App\Domain\Comment\CommentService;
use App\Planka\Client\PlankaClientInterface;
use App\Planka\Exception\AuthenticationException;
use App\Planka\Exception\PlankaApiException;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

final class CommentServiceTest extends TestCase
{
    private PlankaClientInterface&MockObject $plankaClient;
    private CommentService $service;

    protected function setUp(): void
    {
        $this->plankaClient = $this->createMock(PlankaClientInterface::class);
        $this->service = new CommentService($this->plankaClient);
    }

    // --- addComment ---

    public function testAddCommentSuccess(): void
    {
        $expected = ['item' => ['id' => 'action1', 'type' => 'commentCard', 'data' => ['text' => 'Hello']]];

        $this->plankaClient
            ->expects($this->once())
            ->method('post')
            ->with('test-api-key', '/api/cards/card1/comments', ['text' => 'Hello'])
            ->willReturn($expected);

        $result = $this->service->addComment('test-api-key', 'card1', 'Hello');

        $this->assertSame($expected, $result);
    }

    public function testAddCommentPropagatesAuthException(): void
    {
        $this->plankaClient
            ->method('post')
            ->willThrowException(new AuthenticationException('Unauthorized'));

        $this->expectException(AuthenticationException::class);

        $this->service->addComment('bad-key', 'card1', 'Hello');
    }

    public function testAddCommentPropagatesApiException(): void
    {
        $this->plankaClient
            ->method('post')
            ->willThrowException(new PlankaApiException('Server error'));

        $this->expectException(PlankaApiException::class);

        $this->service->addComment('test-api-key', 'card1', 'Hello');
    }

    // --- getComments ---

    public function testGetCommentsSuccess(): void
    {
        $expected = ['items' => [['id' => 'action1', 'type' => 'commentCard']]];

        $this->plankaClient
            ->expects($this->once())
            ->method('get')
            ->with('test-api-key', '/api/cards/card1/comments')
            ->willReturn($expected);

        $result = $this->service->getComments('test-api-key', 'card1');

        $this->assertSame($expected, $result);
    }

    public function testGetCommentsReturnsEmptyWhenNoComments(): void
    {
        $this->plankaClient
            ->expects($this->once())
            ->method('get')
            ->with('test-api-key', '/api/cards/card1/comments')
            ->willReturn([]);

        $result = $this->service->getComments('test-api-key', 'card1');

        $this->assertSame([], $result);
    }

    public function testGetCommentsPropagatesAuthException(): void
    {
        $this->plankaClient
            ->method('get')
            ->willThrowException(new AuthenticationException('Unauthorized'));

        $this->expectException(AuthenticationException::class);

        $this->service->getComments('bad-key', 'card1');
    }

    public function testGetCommentsPropagatesApiException(): void
    {
        $this->plankaClient
            ->method('get')
            ->willThrowException(new PlankaApiException('Server error'));

        $this->expectException(PlankaApiException::class);

        $this->service->getComments('test-api-key', 'card1');
    }

    // --- updateComment ---

    public function testUpdateCommentSuccess(): void
    {
        $expected = ['item' => ['id' => 'comment1', 'data' => ['text' => 'Updated text']]];

        $this->plankaClient
            ->expects($this->once())
            ->method('patch')
            ->with('test-api-key', '/api/comments/comment1', ['text' => 'Updated text'])
            ->willReturn($expected);

        $result = $this->service->updateComment('test-api-key', 'comment1', 'Updated text');

        $this->assertSame($expected, $result);
    }

    public function testUpdateCommentPropagatesAuthException(): void
    {
        $this->plankaClient
            ->method('patch')
            ->willThrowException(new AuthenticationException('Unauthorized'));

        $this->expectException(AuthenticationException::class);

        $this->service->updateComment('bad-key', 'comment1', 'text');
    }

    public function testUpdateCommentPropagatesApiException(): void
    {
        $this->plankaClient
            ->method('patch')
            ->willThrowException(new PlankaApiException('Server error'));

        $this->expectException(PlankaApiException::class);

        $this->service->updateComment('test-api-key', 'comment1', 'text');
    }

    // --- deleteComment ---

    public function testDeleteCommentSuccess(): void
    {
        $this->plankaClient
            ->expects($this->once())
            ->method('delete')
            ->with('test-api-key', '/api/comments/comment1')
            ->willReturn([]);

        $result = $this->service->deleteComment('test-api-key', 'comment1');

        $this->assertSame([], $result);
    }

    public function testDeleteCommentPropagatesAuthException(): void
    {
        $this->plankaClient
            ->method('delete')
            ->willThrowException(new AuthenticationException('Unauthorized'));

        $this->expectException(AuthenticationException::class);

        $this->service->deleteComment('bad-key', 'comment1');
    }

    public function testDeleteCommentPropagatesApiException(): void
    {
        $this->plankaClient
            ->method('delete')
            ->willThrowException(new PlankaApiException('Server error'));

        $this->expectException(PlankaApiException::class);

        $this->service->deleteComment('test-api-key', 'comment1');
    }
}
