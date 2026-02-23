<?php

declare(strict_types=1);

namespace App\Tests\Domain\Attachment;

use App\Domain\Attachment\AttachmentService;
use App\Planka\Client\PlankaClientInterface;
use App\Planka\Exception\AuthenticationException;
use App\Planka\Exception\PlankaApiException;
use App\Planka\Exception\PlankaNotFoundException;
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

    // --- uploadAttachment ---

    public function testUploadAttachmentSuccess(): void
    {
        $expected = ['item' => ['id' => 'att1', 'name' => 'report.pdf']];

        $this->plankaClient
            ->expects($this->once())
            ->method('postMultipart')
            ->with('test-api-key', '/api/cards/card1/attachments', [], '/tmp/report.pdf', 'report.pdf')
            ->willReturn($expected);

        $result = $this->service->uploadAttachment('test-api-key', 'card1', '/tmp/report.pdf', 'report.pdf');

        $this->assertSame($expected, $result);
    }

    public function testUploadAttachmentPropagatesAuthException(): void
    {
        $this->plankaClient
            ->method('postMultipart')
            ->willThrowException(new AuthenticationException('Unauthorized'));

        $this->expectException(AuthenticationException::class);

        $this->service->uploadAttachment('bad-key', 'card1', '/tmp/file.pdf', 'file.pdf');
    }

    public function testUploadAttachmentPropagatesApiException(): void
    {
        $this->plankaClient
            ->method('postMultipart')
            ->willThrowException(new PlankaApiException('Server error'));

        $this->expectException(PlankaApiException::class);

        $this->service->uploadAttachment('test-api-key', 'card1', '/tmp/file.pdf', 'file.pdf');
    }

    public function testUploadAttachmentPropagatesNotFoundException(): void
    {
        $this->plankaClient
            ->method('postMultipart')
            ->willThrowException(new PlankaNotFoundException('Not found', 404));

        $this->expectException(PlankaNotFoundException::class);

        $this->service->uploadAttachment('test-api-key', 'card1', '/tmp/file.pdf', 'file.pdf');
    }

    // --- updateAttachment ---

    public function testUpdateAttachmentWithNameAndIsCoverSuccess(): void
    {
        $expected = ['item' => ['id' => 'att1', 'name' => 'new-name.pdf', 'isCover' => true]];

        $this->plankaClient
            ->expects($this->once())
            ->method('patch')
            ->with('test-api-key', '/api/attachments/att1', ['name' => 'new-name.pdf', 'isCover' => true])
            ->willReturn($expected);

        $result = $this->service->updateAttachment('test-api-key', 'att1', 'new-name.pdf', true);

        $this->assertSame($expected, $result);
    }

    public function testUpdateAttachmentWithNameOnlySuccess(): void
    {
        $expected = ['item' => ['id' => 'att1', 'name' => 'renamed.pdf']];

        $this->plankaClient
            ->expects($this->once())
            ->method('patch')
            ->with('test-api-key', '/api/attachments/att1', ['name' => 'renamed.pdf'])
            ->willReturn($expected);

        $result = $this->service->updateAttachment('test-api-key', 'att1', 'renamed.pdf');

        $this->assertSame($expected, $result);
    }

    public function testUpdateAttachmentWithIsCoverOnlySuccess(): void
    {
        $expected = ['item' => ['id' => 'att1', 'isCover' => false]];

        $this->plankaClient
            ->expects($this->once())
            ->method('patch')
            ->with('test-api-key', '/api/attachments/att1', ['isCover' => false])
            ->willReturn($expected);

        $result = $this->service->updateAttachment('test-api-key', 'att1', null, false);

        $this->assertSame($expected, $result);
    }

    public function testUpdateAttachmentWithNullParamsSendsEmptyBody(): void
    {
        $expected = ['item' => ['id' => 'att1']];

        $this->plankaClient
            ->expects($this->once())
            ->method('patch')
            ->with('test-api-key', '/api/attachments/att1', [])
            ->willReturn($expected);

        $result = $this->service->updateAttachment('test-api-key', 'att1');

        $this->assertSame($expected, $result);
    }

    public function testUpdateAttachmentPropagatesAuthException(): void
    {
        $this->plankaClient
            ->method('patch')
            ->willThrowException(new AuthenticationException('Unauthorized'));

        $this->expectException(AuthenticationException::class);

        $this->service->updateAttachment('bad-key', 'att1', 'name.pdf');
    }

    public function testUpdateAttachmentPropagatesApiException(): void
    {
        $this->plankaClient
            ->method('patch')
            ->willThrowException(new PlankaApiException('Server error'));

        $this->expectException(PlankaApiException::class);

        $this->service->updateAttachment('test-api-key', 'att1', 'name.pdf');
    }

    // --- deleteAttachment ---

    public function testDeleteAttachmentSuccess(): void
    {
        $this->plankaClient
            ->expects($this->once())
            ->method('delete')
            ->with('test-api-key', '/api/attachments/att1')
            ->willReturn([]);

        $result = $this->service->deleteAttachment('test-api-key', 'att1');

        $this->assertSame([], $result);
    }

    public function testDeleteAttachmentPropagatesAuthException(): void
    {
        $this->plankaClient
            ->method('delete')
            ->willThrowException(new AuthenticationException('Unauthorized'));

        $this->expectException(AuthenticationException::class);

        $this->service->deleteAttachment('bad-key', 'att1');
    }

    public function testDeleteAttachmentPropagatesApiException(): void
    {
        $this->plankaClient
            ->method('delete')
            ->willThrowException(new PlankaApiException('Server error'));

        $this->expectException(PlankaApiException::class);

        $this->service->deleteAttachment('test-api-key', 'att1');
    }

    public function testDeleteAttachmentPropagatesNotFoundException(): void
    {
        $this->plankaClient
            ->method('delete')
            ->willThrowException(new PlankaNotFoundException('Not found', 404));

        $this->expectException(PlankaNotFoundException::class);

        $this->service->deleteAttachment('test-api-key', 'att1');
    }
}
