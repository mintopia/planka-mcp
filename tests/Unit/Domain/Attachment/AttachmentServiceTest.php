<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\Attachment;

use App\Domain\Attachment\AttachmentService;
use App\Planka\Exception\AuthenticationException;
use App\Planka\Exception\PlankaApiException;
use App\Planka\PlankaClientInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

final class AttachmentServiceTest extends TestCase
{
    private PlankaClientInterface&MockObject $plankaClient;
    private AttachmentService $service;

    protected function setUp(): void
    {
        $this->plankaClient = $this->createMock(PlankaClientInterface::class);
        $this->service = new AttachmentService($this->plankaClient);
    }

    public function testUploadAttachmentSuccess(): void
    {
        $expected = ['item' => ['id' => 'att1', 'name' => 'file.pdf']];

        $this->plankaClient
            ->expects($this->once())
            ->method('postMultipart')
            ->with('test-api-key', '/api/cards/card1/attachments', [], '/tmp/file.pdf', 'file.pdf')
            ->willReturn($expected);

        $this->assertSame($expected, $this->service->uploadAttachment('test-api-key', 'card1', '/tmp/file.pdf', 'file.pdf'));
    }

    public function testUploadAttachmentPropagatesAuthException(): void
    {
        $this->plankaClient->method('postMultipart')->willThrowException(new AuthenticationException('Unauthorized'));
        $this->expectException(AuthenticationException::class);
        $this->service->uploadAttachment('bad-key', 'card1', '/tmp/file.pdf', 'file.pdf');
    }

    public function testUploadAttachmentPropagatesApiException(): void
    {
        $this->plankaClient->method('postMultipart')->willThrowException(new PlankaApiException('Server error'));
        $this->expectException(PlankaApiException::class);
        $this->service->uploadAttachment('test-api-key', 'card1', '/tmp/file.pdf', 'file.pdf');
    }

    public function testUpdateAttachmentWithNameAndCover(): void
    {
        $expected = ['item' => ['id' => 'att1', 'name' => 'renamed.pdf', 'isCover' => true]];

        $this->plankaClient
            ->expects($this->once())
            ->method('patch')
            ->with('test-api-key', '/api/attachments/att1', ['name' => 'renamed.pdf', 'isCover' => true])
            ->willReturn($expected);

        $this->assertSame($expected, $this->service->updateAttachment('test-api-key', 'att1', 'renamed.pdf', true));
    }

    public function testUpdateAttachmentWithNameOnly(): void
    {
        $expected = ['item' => ['id' => 'att1', 'name' => 'renamed.pdf']];

        $this->plankaClient
            ->expects($this->once())
            ->method('patch')
            ->with('test-api-key', '/api/attachments/att1', ['name' => 'renamed.pdf'])
            ->willReturn($expected);

        $this->assertSame($expected, $this->service->updateAttachment('test-api-key', 'att1', 'renamed.pdf'));
    }

    public function testUpdateAttachmentWithCoverOnly(): void
    {
        $expected = ['item' => ['id' => 'att1', 'isCover' => true]];

        $this->plankaClient
            ->expects($this->once())
            ->method('patch')
            ->with('test-api-key', '/api/attachments/att1', ['isCover' => true])
            ->willReturn($expected);

        $this->assertSame($expected, $this->service->updateAttachment('test-api-key', 'att1', null, true));
    }

    public function testUpdateAttachmentWithNullsSendsEmptyBody(): void
    {
        $expected = ['item' => ['id' => 'att1']];

        $this->plankaClient
            ->expects($this->once())
            ->method('patch')
            ->with('test-api-key', '/api/attachments/att1', [])
            ->willReturn($expected);

        $this->assertSame($expected, $this->service->updateAttachment('test-api-key', 'att1'));
    }

    public function testUpdateAttachmentPropagatesAuthException(): void
    {
        $this->plankaClient->method('patch')->willThrowException(new AuthenticationException('Unauthorized'));
        $this->expectException(AuthenticationException::class);
        $this->service->updateAttachment('bad-key', 'att1', 'name');
    }

    public function testUpdateAttachmentPropagatesApiException(): void
    {
        $this->plankaClient->method('patch')->willThrowException(new PlankaApiException('Server error'));
        $this->expectException(PlankaApiException::class);
        $this->service->updateAttachment('test-api-key', 'att1', 'name');
    }

    public function testDeleteAttachmentSuccess(): void
    {
        $this->plankaClient
            ->expects($this->once())
            ->method('delete')
            ->with('test-api-key', '/api/attachments/att1')
            ->willReturn([]);

        $this->assertSame([], $this->service->deleteAttachment('test-api-key', 'att1'));
    }

    public function testDeleteAttachmentPropagatesAuthException(): void
    {
        $this->plankaClient->method('delete')->willThrowException(new AuthenticationException('Unauthorized'));
        $this->expectException(AuthenticationException::class);
        $this->service->deleteAttachment('bad-key', 'att1');
    }

    public function testDeleteAttachmentPropagatesApiException(): void
    {
        $this->plankaClient->method('delete')->willThrowException(new PlankaApiException('Server error'));
        $this->expectException(PlankaApiException::class);
        $this->service->deleteAttachment('test-api-key', 'att1');
    }
}
