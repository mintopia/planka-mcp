<?php

declare(strict_types=1);

namespace App\Tests\Mcp;

use App\Domain\Attachment\AttachmentService;
use App\Infrastructure\Http\ApiKeyProvider;
use App\Mcp\AttachmentTools;
use App\Planka\Exception\AuthenticationException;
use App\Planka\Exception\PlankaApiException;
use App\Planka\Exception\PlankaNotFoundException;
use App\Shared\Exception\ValidationException;
use Mcp\Exception\ToolCallException;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

final class AttachmentToolsTest extends TestCase
{
    private AttachmentService&MockObject $attachmentService;
    private ApiKeyProvider&MockObject $apiKeyProvider;
    private AttachmentTools $tools;
    private string $uploadDir;

    protected function setUp(): void
    {
        $dir = sys_get_temp_dir() . '/planka-test-uploads-' . uniqid();
        mkdir($dir, 0777, true);
        $realDir = realpath($dir);
        assert($realDir !== false);
        $this->uploadDir = $realDir;

        $this->attachmentService = $this->createMock(AttachmentService::class);
        $this->apiKeyProvider = $this->createMock(ApiKeyProvider::class);
        $this->tools = new AttachmentTools($this->attachmentService, $this->apiKeyProvider, $this->uploadDir);
    }

    protected function tearDown(): void
    {
        $files = glob($this->uploadDir . '/*');
        if ($files !== false) {
            foreach ($files as $file) {
                unlink($file);
            }
        }
        if (is_dir($this->uploadDir)) {
            rmdir($this->uploadDir);
        }
    }

    // -------------------------------------------------------------------------
    // uploadAttachment
    // -------------------------------------------------------------------------

    public function testUploadAttachmentSuccess(): void
    {
        $filePath = $this->uploadDir . '/report.pdf';
        file_put_contents($filePath, 'test content');

        $expected = ['item' => ['id' => 'att1', 'name' => 'report.pdf']];

        $this->apiKeyProvider
            ->expects($this->once())
            ->method('getApiKey')
            ->willReturn('test-api-key');

        $this->attachmentService
            ->expects($this->once())
            ->method('uploadAttachment')
            ->with('test-api-key', 'card1', $filePath, 'report.pdf')
            ->willReturn($expected);

        $result = $this->tools->uploadAttachment('card1', $filePath, 'report.pdf');

        $this->assertSame($expected, $result);
    }

    public function testUploadAttachmentWithEmptyFilePathThrowsToolCallException(): void
    {
        $this->apiKeyProvider->expects($this->never())->method('getApiKey');
        $this->attachmentService->expects($this->never())->method('uploadAttachment');

        $this->expectException(ToolCallException::class);
        $this->expectExceptionMessage('filePath cannot be empty.');

        $this->tools->uploadAttachment('card1', '', 'report.pdf');
    }

    public function testUploadAttachmentWithWhitespaceFilePathThrowsToolCallException(): void
    {
        $this->apiKeyProvider->expects($this->never())->method('getApiKey');
        $this->attachmentService->expects($this->never())->method('uploadAttachment');

        $this->expectException(ToolCallException::class);
        $this->expectExceptionMessage('filePath cannot be empty.');

        $this->tools->uploadAttachment('card1', '   ', 'report.pdf');
    }

    public function testUploadAttachmentWithEmptyFilenameThrowsToolCallException(): void
    {
        $this->apiKeyProvider->expects($this->never())->method('getApiKey');
        $this->attachmentService->expects($this->never())->method('uploadAttachment');

        $this->expectException(ToolCallException::class);
        $this->expectExceptionMessage('filename cannot be empty.');

        $this->tools->uploadAttachment('card1', '/tmp/report.pdf', '');
    }

    public function testUploadAttachmentWithWhitespaceFilenameThrowsToolCallException(): void
    {
        $this->apiKeyProvider->expects($this->never())->method('getApiKey');
        $this->attachmentService->expects($this->never())->method('uploadAttachment');

        $this->expectException(ToolCallException::class);
        $this->expectExceptionMessage('filename cannot be empty.');

        $this->tools->uploadAttachment('card1', '/tmp/report.pdf', '   ');
    }

    public function testUploadAttachmentMissingApiKeyThrowsToolCallException(): void
    {
        $filePath = $this->uploadDir . '/file.pdf';
        file_put_contents($filePath, 'test');

        $this->apiKeyProvider
            ->expects($this->once())
            ->method('getApiKey')
            ->willThrowException(new ValidationException('Planka API key required.'));

        $this->attachmentService->expects($this->never())->method('uploadAttachment');

        $this->expectException(ToolCallException::class);
        $this->expectExceptionMessage('Planka API key required.');

        $this->tools->uploadAttachment('card1', $filePath, 'file.pdf');
    }

    public function testUploadAttachmentWrapsAuthExceptionInToolCallException(): void
    {
        $filePath = $this->uploadDir . '/file.pdf';
        file_put_contents($filePath, 'test');

        $this->apiKeyProvider->method('getApiKey')->willReturn('bad-key');

        $this->attachmentService
            ->method('uploadAttachment')
            ->willThrowException(new AuthenticationException('Unauthorized'));

        $this->expectException(ToolCallException::class);
        $this->expectExceptionMessage('Unauthorized');

        $this->tools->uploadAttachment('card1', $filePath, 'file.pdf');
    }

    public function testUploadAttachmentWrapsPlankaApiExceptionInToolCallException(): void
    {
        $filePath = $this->uploadDir . '/file.pdf';
        file_put_contents($filePath, 'test');

        $this->apiKeyProvider->method('getApiKey')->willReturn('test-api-key');

        $this->attachmentService
            ->method('uploadAttachment')
            ->willThrowException(new PlankaApiException('Server error'));

        $this->expectException(ToolCallException::class);
        $this->expectExceptionMessage('Server error');

        $this->tools->uploadAttachment('card1', $filePath, 'file.pdf');
    }

    public function testUploadAttachmentWrapsNotFoundExceptionInToolCallException(): void
    {
        $filePath = $this->uploadDir . '/file.pdf';
        file_put_contents($filePath, 'test');

        $this->apiKeyProvider->method('getApiKey')->willReturn('test-api-key');

        $this->attachmentService
            ->method('uploadAttachment')
            ->willThrowException(new PlankaNotFoundException('Not found', 404));

        $this->expectException(ToolCallException::class);
        $this->expectExceptionMessage('Not found');

        $this->tools->uploadAttachment('card1', $filePath, 'file.pdf');
    }

    // -------------------------------------------------------------------------
    // uploadAttachment: path traversal protection
    // -------------------------------------------------------------------------

    public function testUploadAttachmentOutsideUploadDirThrowsToolCallException(): void
    {
        $this->apiKeyProvider->expects($this->never())->method('getApiKey');
        $this->attachmentService->expects($this->never())->method('uploadAttachment');

        $this->expectException(ToolCallException::class);
        $this->expectExceptionMessage('File path must be within the configured upload directory.');

        $this->tools->uploadAttachment('card1', '/etc/passwd', 'passwd');
    }

    public function testUploadAttachmentNonExistentFileThrowsToolCallException(): void
    {
        $this->apiKeyProvider->expects($this->never())->method('getApiKey');
        $this->attachmentService->expects($this->never())->method('uploadAttachment');

        $this->expectException(ToolCallException::class);
        $this->expectExceptionMessage('File not found or inaccessible');

        $this->tools->uploadAttachment('card1', $this->uploadDir . '/nonexistent.pdf', 'nonexistent.pdf');
    }

    // -------------------------------------------------------------------------
    // manageAttachments: update
    // -------------------------------------------------------------------------

    public function testManageAttachmentsUpdateSuccess(): void
    {
        $expected = ['item' => ['id' => 'att1', 'name' => 'new-name.pdf', 'isCover' => true]];

        $this->apiKeyProvider
            ->expects($this->once())
            ->method('getApiKey')
            ->willReturn('test-api-key');

        $this->attachmentService
            ->expects($this->once())
            ->method('updateAttachment')
            ->with('test-api-key', 'att1', 'new-name.pdf', true)
            ->willReturn($expected);

        $result = $this->tools->manageAttachments('update', 'att1', 'new-name.pdf', true);

        $this->assertSame($expected, $result);
    }

    public function testManageAttachmentsUpdateWithoutAttachmentIdThrowsToolCallException(): void
    {
        $this->apiKeyProvider->method('getApiKey')->willReturn('test-api-key');
        $this->attachmentService->expects($this->never())->method('updateAttachment');

        $this->expectException(ToolCallException::class);
        $this->expectExceptionMessage('attachmentId required for update');

        $this->tools->manageAttachments('update');
    }

    // -------------------------------------------------------------------------
    // manageAttachments: delete
    // -------------------------------------------------------------------------

    public function testManageAttachmentsDeleteSuccess(): void
    {
        $this->apiKeyProvider
            ->expects($this->once())
            ->method('getApiKey')
            ->willReturn('test-api-key');

        $this->attachmentService
            ->expects($this->once())
            ->method('deleteAttachment')
            ->with('test-api-key', 'att1')
            ->willReturn([]);

        $result = $this->tools->manageAttachments('delete', 'att1');

        $this->assertSame([], $result);
    }

    public function testManageAttachmentsDeleteWithoutAttachmentIdThrowsToolCallException(): void
    {
        $this->apiKeyProvider->method('getApiKey')->willReturn('test-api-key');
        $this->attachmentService->expects($this->never())->method('deleteAttachment');

        $this->expectException(ToolCallException::class);
        $this->expectExceptionMessage('attachmentId required for delete');

        $this->tools->manageAttachments('delete');
    }

    // -------------------------------------------------------------------------
    // manageAttachments: invalid action
    // -------------------------------------------------------------------------

    public function testManageAttachmentsInvalidActionThrowsToolCallException(): void
    {
        $this->apiKeyProvider->method('getApiKey')->willReturn('test-api-key');

        $this->expectException(ToolCallException::class);
        $this->expectExceptionMessage('Invalid action "get". Must be: update, delete');

        $this->tools->manageAttachments('get');
    }

    // -------------------------------------------------------------------------
    // manageAttachments: missing API key + exception wrapping
    // -------------------------------------------------------------------------

    public function testManageAttachmentsMissingApiKeyThrowsToolCallException(): void
    {
        $this->apiKeyProvider
            ->expects($this->once())
            ->method('getApiKey')
            ->willThrowException(new ValidationException('Planka API key required.'));

        $this->attachmentService->expects($this->never())->method('updateAttachment');

        $this->expectException(ToolCallException::class);
        $this->expectExceptionMessage('Planka API key required.');

        $this->tools->manageAttachments('update', 'att1', 'name.pdf');
    }

    public function testManageAttachmentsWrapsAuthExceptionInToolCallException(): void
    {
        $this->apiKeyProvider->method('getApiKey')->willReturn('bad-key');

        $this->attachmentService
            ->method('updateAttachment')
            ->willThrowException(new AuthenticationException('Unauthorized'));

        $this->expectException(ToolCallException::class);
        $this->expectExceptionMessage('Unauthorized');

        $this->tools->manageAttachments('update', 'att1', 'name.pdf');
    }

    public function testManageAttachmentsWrapsPlankaApiExceptionInToolCallException(): void
    {
        $this->apiKeyProvider->method('getApiKey')->willReturn('test-api-key');

        $this->attachmentService
            ->method('deleteAttachment')
            ->willThrowException(new PlankaApiException('Server error'));

        $this->expectException(ToolCallException::class);
        $this->expectExceptionMessage('Server error');

        $this->tools->manageAttachments('delete', 'att1');
    }

    public function testManageAttachmentsWrapsNotFoundExceptionInToolCallException(): void
    {
        $this->apiKeyProvider->method('getApiKey')->willReturn('test-api-key');

        $this->attachmentService
            ->method('deleteAttachment')
            ->willThrowException(new PlankaNotFoundException('Not found', 404));

        $this->expectException(ToolCallException::class);
        $this->expectExceptionMessage('Not found');

        $this->tools->manageAttachments('delete', 'att1');
    }
}
