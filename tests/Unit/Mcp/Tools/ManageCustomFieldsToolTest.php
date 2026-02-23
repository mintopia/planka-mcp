<?php

declare(strict_types=1);

namespace Tests\Unit\Mcp\Tools;

use App\Domain\CustomField\CustomFieldServiceInterface;
use App\Exception\ValidationException;
use App\Http\ApiKeyProviderInterface;
use App\Mcp\Tools\ManageCustomFieldsTool;
use Laravel\Mcp\Request;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

final class ManageCustomFieldsToolTest extends TestCase
{
    private CustomFieldServiceInterface&MockObject $customFieldService;
    private ApiKeyProviderInterface&MockObject $apiKeyProvider;
    private ManageCustomFieldsTool $tool;

    protected function setUp(): void
    {
        $this->customFieldService = $this->createMock(CustomFieldServiceInterface::class);
        $this->apiKeyProvider = $this->createMock(ApiKeyProviderInterface::class);
        $this->tool = new ManageCustomFieldsTool($this->customFieldService, $this->apiKeyProvider);
    }

    private function makeRequest(array $params = []): Request
    {
        return new Request(arguments: $params);
    }

    public function testCreateSuccess(): void
    {
        $this->apiKeyProvider->method('getApiKey')->willReturn('test-key');
        $this->customFieldService->method('createField')->willReturn(['item' => ['id' => 'f1']]);

        $response = $this->tool->handle($this->makeRequest([
            'action' => 'create',
            'groupType' => 'base',
            'groupId' => 'bg1',
            'name' => 'Status',
            'fieldType' => 'text',
        ]));
        $this->assertFalse($response->isError());
    }

    public function testCreateMissingGroupType(): void
    {
        $this->apiKeyProvider->method('getApiKey')->willReturn('test-key');

        $response = $this->tool->handle($this->makeRequest(['action' => 'create', 'groupId' => 'bg1']));
        $this->assertTrue($response->isError());
        $this->assertStringContainsString('groupType required', (string) $response->content());
    }

    public function testCreateMissingGroupId(): void
    {
        $this->apiKeyProvider->method('getApiKey')->willReturn('test-key');

        $response = $this->tool->handle($this->makeRequest(['action' => 'create', 'groupType' => 'base']));
        $this->assertTrue($response->isError());
        $this->assertStringContainsString('groupId required', (string) $response->content());
    }

    public function testUpdateSuccess(): void
    {
        $this->apiKeyProvider->method('getApiKey')->willReturn('test-key');
        $this->customFieldService->method('updateField')->willReturn(['item' => ['id' => 'f1']]);

        $response = $this->tool->handle($this->makeRequest(['action' => 'update', 'fieldId' => 'f1', 'name' => 'Updated']));
        $this->assertFalse($response->isError());
    }

    public function testDeleteSuccess(): void
    {
        $this->apiKeyProvider->method('getApiKey')->willReturn('test-key');
        $this->customFieldService->method('deleteField')->willReturn([]);

        $response = $this->tool->handle($this->makeRequest(['action' => 'delete', 'fieldId' => 'f1']));
        $this->assertFalse($response->isError());
    }

    public function testDeleteMissingFieldId(): void
    {
        $this->apiKeyProvider->method('getApiKey')->willReturn('test-key');

        $response = $this->tool->handle($this->makeRequest(['action' => 'delete']));
        $this->assertTrue($response->isError());
        $this->assertStringContainsString('fieldId required', (string) $response->content());
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

        $response = $this->tool->handle($this->makeRequest(['action' => 'create', 'groupType' => 'base', 'groupId' => 'g1']));
        $this->assertTrue($response->isError());
    }

    public function testSchemaReturnsArray(): void
    {
        $data = $this->tool->toArray();
        $this->assertIsArray($data);
        $this->assertArrayHasKey('inputSchema', $data);
    }
}
