<?php

declare(strict_types=1);

namespace Tests\Unit\Mcp\Tools;

use App\Domain\Attachment\AttachmentServiceInterface;
use App\Exception\ValidationException;
use App\Http\ApiKeyProviderInterface;
use App\Mcp\Tools\ManageAttachmentsTool;
use Laravel\Mcp\Request;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

final class ManageAttachmentsToolTest extends TestCase
{
    private AttachmentServiceInterface&MockObject $attachmentService;
    private ApiKeyProviderInterface&MockObject $apiKeyProvider;
    private ManageAttachmentsTool $tool;

    protected function setUp(): void
    {
        $this->attachmentService = $this->createMock(AttachmentServiceInterface::class);
        $this->apiKeyProvider = $this->createMock(ApiKeyProviderInterface::class);
        $this->tool = new ManageAttachmentsTool($this->attachmentService, $this->apiKeyProvider);
    }

    private function makeRequest(array $params = []): Request
    {
        return new Request(arguments: $params);
    }

    public function testUpdateSuccess(): void
    {
        $this->apiKeyProvider->method('getApiKey')->willReturn('test-key');
        $this->attachmentService->method('updateAttachment')->willReturn(['item' => ['id' => 'a1']]);

        $response = $this->tool->handle($this->makeRequest(['action' => 'update', 'attachmentId' => 'a1', 'name' => 'new.pdf']));
        $this->assertFalse($response->isError());
    }

    public function testUpdateMissingAttachmentId(): void
    {
        $this->apiKeyProvider->method('getApiKey')->willReturn('test-key');

        $response = $this->tool->handle($this->makeRequest(['action' => 'update']));
        $this->assertTrue($response->isError());
        $this->assertStringContainsString('attachmentId required', (string) $response->content());
    }

    public function testDeleteSuccess(): void
    {
        $this->apiKeyProvider->method('getApiKey')->willReturn('test-key');
        $this->attachmentService->method('deleteAttachment')->willReturn([]);

        $response = $this->tool->handle($this->makeRequest(['action' => 'delete', 'attachmentId' => 'a1']));
        $this->assertFalse($response->isError());
    }

    public function testInvalidAction(): void
    {
        $this->apiKeyProvider->method('getApiKey')->willReturn('test-key');

        $response = $this->tool->handle($this->makeRequest(['action' => 'invalid']));
        $this->assertTrue($response->isError());
    }

    public function testAuthError(): void
    {
        $this->apiKeyProvider->method('getApiKey')->willThrowException(new ValidationException('No API key'));

        $response = $this->tool->handle($this->makeRequest(['action' => 'update', 'attachmentId' => 'a1']));
        $this->assertTrue($response->isError());
    }

    public function testSchemaReturnsArray(): void
    {
        $data = $this->tool->toArray();
        $this->assertIsArray($data);
        $this->assertArrayHasKey('inputSchema', $data);
    }
}
