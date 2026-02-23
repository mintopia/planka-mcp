<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\Comment;

use App\Domain\Comment\CommentService;
use App\Planka\Exception\AuthenticationException;
use App\Planka\Exception\PlankaApiException;
use App\Planka\PlankaClientInterface;
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

    public function testAddCommentSuccess(): void
    {
        $expected = ['item' => ['id' => 'comment1', 'text' => 'Hello']];

        $this->plankaClient
            ->expects($this->once())
            ->method('post')
            ->with('test-api-key', '/api/cards/card1/comments', ['text' => 'Hello'])
            ->willReturn($expected);

        $this->assertSame($expected, $this->service->addComment('test-api-key', 'card1', 'Hello'));
    }

    public function testAddCommentPropagatesAuthException(): void
    {
        $this->plankaClient->method('post')->willThrowException(new AuthenticationException('Unauthorized'));
        $this->expectException(AuthenticationException::class);
        $this->service->addComment('bad-key', 'card1', 'Hello');
    }

    public function testAddCommentPropagatesApiException(): void
    {
        $this->plankaClient->method('post')->willThrowException(new PlankaApiException('Server error'));
        $this->expectException(PlankaApiException::class);
        $this->service->addComment('test-api-key', 'card1', 'Hello');
    }

    public function testGetCommentsSuccess(): void
    {
        $expected = ['items' => [['id' => 'comment1', 'text' => 'Hello']]];

        $this->plankaClient
            ->expects($this->once())
            ->method('get')
            ->with('test-api-key', '/api/cards/card1/comments')
            ->willReturn($expected);

        $this->assertSame($expected, $this->service->getComments('test-api-key', 'card1'));
    }

    public function testGetCommentsPropagatesAuthException(): void
    {
        $this->plankaClient->method('get')->willThrowException(new AuthenticationException('Unauthorized'));
        $this->expectException(AuthenticationException::class);
        $this->service->getComments('bad-key', 'card1');
    }

    public function testGetCommentsPropagatesApiException(): void
    {
        $this->plankaClient->method('get')->willThrowException(new PlankaApiException('Server error'));
        $this->expectException(PlankaApiException::class);
        $this->service->getComments('test-api-key', 'card1');
    }

    public function testUpdateCommentSuccess(): void
    {
        $expected = ['item' => ['id' => 'comment1', 'text' => 'Updated']];

        $this->plankaClient
            ->expects($this->once())
            ->method('patch')
            ->with('test-api-key', '/api/comments/comment1', ['text' => 'Updated'])
            ->willReturn($expected);

        $this->assertSame($expected, $this->service->updateComment('test-api-key', 'comment1', 'Updated'));
    }

    public function testUpdateCommentPropagatesAuthException(): void
    {
        $this->plankaClient->method('patch')->willThrowException(new AuthenticationException('Unauthorized'));
        $this->expectException(AuthenticationException::class);
        $this->service->updateComment('bad-key', 'comment1', 'Text');
    }

    public function testUpdateCommentPropagatesApiException(): void
    {
        $this->plankaClient->method('patch')->willThrowException(new PlankaApiException('Server error'));
        $this->expectException(PlankaApiException::class);
        $this->service->updateComment('test-api-key', 'comment1', 'Text');
    }

    public function testDeleteCommentSuccess(): void
    {
        $this->plankaClient
            ->expects($this->once())
            ->method('delete')
            ->with('test-api-key', '/api/comments/comment1')
            ->willReturn([]);

        $this->assertSame([], $this->service->deleteComment('test-api-key', 'comment1'));
    }

    public function testDeleteCommentPropagatesAuthException(): void
    {
        $this->plankaClient->method('delete')->willThrowException(new AuthenticationException('Unauthorized'));
        $this->expectException(AuthenticationException::class);
        $this->service->deleteComment('bad-key', 'comment1');
    }

    public function testDeleteCommentPropagatesApiException(): void
    {
        $this->plankaClient->method('delete')->willThrowException(new PlankaApiException('Server error'));
        $this->expectException(PlankaApiException::class);
        $this->service->deleteComment('test-api-key', 'comment1');
    }
}
