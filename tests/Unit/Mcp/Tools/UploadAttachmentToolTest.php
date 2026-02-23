<?php

declare(strict_types=1);

namespace Tests\Unit\Mcp\Tools;

use App\Domain\Attachment\AttachmentServiceInterface;
use App\Exception\ValidationException;
use App\Http\ApiKeyProviderInterface;
use App\Mcp\Tools\UploadAttachmentTool;
use Laravel\Mcp\Request;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

final class UploadAttachmentToolTest extends TestCase
{
    private AttachmentServiceInterface&MockObject $attachmentService;
    private ApiKeyProviderInterface&MockObject $apiKeyProvider;
    private UploadAttachmentTool $tool;
    private string $uploadDir;

    protected function setUp(): void
    {
        $this->attachmentService = $this->createMock(AttachmentServiceInterface::class);
        $this->apiKeyProvider = $this->createMock(ApiKeyProviderInterface::class);
        $this->uploadDir = realpath(sys_get_temp_dir()) ?: sys_get_temp_dir();
        $this->tool = new UploadAttachmentTool($this->attachmentService, $this->apiKeyProvider, $this->uploadDir);
    }

    private function makeRequest(array $params = []): Request
    {
        return new Request(arguments: $params);
    }

    public function testHandleSuccess(): void
    {
        $tmpFile = tempnam($this->uploadDir, 'test_');
        file_put_contents($tmpFile, 'content');

        try {
            $this->apiKeyProvider->method('getApiKey')->willReturn('test-key');
            $this->attachmentService->method('uploadAttachment')->willReturn(['item' => ['id' => 'a1']]);

            $response = $this->tool->handle($this->makeRequest([
                'cardId' => 'c1',
                'filePath' => $tmpFile,
                'filename' => 'test.txt',
            ]));
            $this->assertFalse($response->isError());
        } finally {
            @unlink($tmpFile);
        }
    }

    public function testHandleEmptyFilePath(): void
    {
        $response = $this->tool->handle($this->makeRequest(['cardId' => 'c1', 'filePath' => '', 'filename' => 'f.txt']));
        $this->assertTrue($response->isError());
        $this->assertStringContainsString('filePath cannot be empty', (string) $response->content());
    }

    public function testHandleEmptyFilename(): void
    {
        $response = $this->tool->handle($this->makeRequest(['cardId' => 'c1', 'filePath' => '/tmp/f', 'filename' => '']));
        $this->assertTrue($response->isError());
        $this->assertStringContainsString('filename cannot be empty', (string) $response->content());
    }

    public function testHandleFileNotFound(): void
    {
        $response = $this->tool->handle($this->makeRequest([
            'cardId' => 'c1',
            'filePath' => '/nonexistent/file.txt',
            'filename' => 'file.txt',
        ]));
        $this->assertTrue($response->isError());
        $this->assertStringContainsString('File not found', (string) $response->content());
    }

    public function testHandleFileOutsideUploadDir(): void
    {
        $tool = new UploadAttachmentTool($this->attachmentService, $this->apiKeyProvider, '/nonexistent/upload/dir');

        $tmpFile = tempnam(sys_get_temp_dir(), 'test_');
        file_put_contents($tmpFile, 'content');

        try {
            $response = $tool->handle($this->makeRequest([
                'cardId' => 'c1',
                'filePath' => $tmpFile,
                'filename' => 'test.txt',
            ]));
            $this->assertTrue($response->isError());
            $this->assertStringContainsString('upload directory', (string) $response->content());
        } finally {
            @unlink($tmpFile);
        }
    }

    public function testHandleAuthError(): void
    {
        $tmpFile = tempnam($this->uploadDir, 'test_');
        file_put_contents($tmpFile, 'content');

        try {
            $this->apiKeyProvider->method('getApiKey')->willThrowException(new ValidationException('No API key'));

            $response = $this->tool->handle($this->makeRequest([
                'cardId' => 'c1',
                'filePath' => $tmpFile,
                'filename' => 'test.txt',
            ]));
            $this->assertTrue($response->isError());
        } finally {
            @unlink($tmpFile);
        }
    }

    public function testSchemaReturnsArray(): void
    {
        $data = $this->tool->toArray();
        $this->assertIsArray($data);
        $this->assertArrayHasKey('inputSchema', $data);
    }
}
