<?php

declare(strict_types=1);

namespace Tests\Unit\Mcp\Tools;

use App\Domain\CustomField\CustomFieldServiceInterface;
use App\Exception\ValidationException;
use App\Http\ApiKeyProviderInterface;
use App\Mcp\Tools\ManageCustomFieldGroupsTool;
use Laravel\Mcp\Request;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

final class ManageCustomFieldGroupsToolTest extends TestCase
{
    private CustomFieldServiceInterface&MockObject $customFieldService;
    private ApiKeyProviderInterface&MockObject $apiKeyProvider;
    private ManageCustomFieldGroupsTool $tool;

    protected function setUp(): void
    {
        $this->customFieldService = $this->createMock(CustomFieldServiceInterface::class);
        $this->apiKeyProvider = $this->createMock(ApiKeyProviderInterface::class);
        $this->tool = new ManageCustomFieldGroupsTool($this->customFieldService, $this->apiKeyProvider);
    }

    private function makeRequest(array $params = []): Request
    {
        return new Request(arguments: $params);
    }

    public function testCreateBaseSuccess(): void
    {
        $this->apiKeyProvider->method('getApiKey')->willReturn('test-key');
        $this->customFieldService->method('createBaseGroup')->willReturn(['item' => ['id' => 'bg1']]);

        $response = $this->tool->handle($this->makeRequest(['action' => 'create_base', 'projectId' => 'p1', 'name' => 'Fields']));
        $this->assertFalse($response->isError());
    }

    public function testCreateBaseMissingProjectId(): void
    {
        $this->apiKeyProvider->method('getApiKey')->willReturn('test-key');

        $response = $this->tool->handle($this->makeRequest(['action' => 'create_base']));
        $this->assertTrue($response->isError());
        $this->assertStringContainsString('projectId required', (string) $response->content());
    }

    public function testUpdateBaseSuccess(): void
    {
        $this->apiKeyProvider->method('getApiKey')->willReturn('test-key');
        $this->customFieldService->method('updateBaseGroup')->willReturn(['item' => ['id' => 'bg1']]);

        $response = $this->tool->handle($this->makeRequest(['action' => 'update_base', 'baseGroupId' => 'bg1', 'name' => 'Updated']));
        $this->assertFalse($response->isError());
    }

    public function testDeleteBaseSuccess(): void
    {
        $this->apiKeyProvider->method('getApiKey')->willReturn('test-key');
        $this->customFieldService->method('deleteBaseGroup')->willReturn([]);

        $response = $this->tool->handle($this->makeRequest(['action' => 'delete_base', 'baseGroupId' => 'bg1']));
        $this->assertFalse($response->isError());
    }

    public function testCreateGroupSuccess(): void
    {
        $this->apiKeyProvider->method('getApiKey')->willReturn('test-key');
        $this->customFieldService->method('createGroup')->willReturn(['item' => ['id' => 'g1']]);

        $response = $this->tool->handle($this->makeRequest(['action' => 'create', 'parentType' => 'board', 'parentId' => 'b1', 'name' => 'Group']));
        $this->assertFalse($response->isError());
    }

    public function testCreateGroupMissingParentType(): void
    {
        $this->apiKeyProvider->method('getApiKey')->willReturn('test-key');

        $response = $this->tool->handle($this->makeRequest(['action' => 'create', 'parentId' => 'b1']));
        $this->assertTrue($response->isError());
        $this->assertStringContainsString('parentType required', (string) $response->content());
    }

    public function testGetGroupSuccess(): void
    {
        $this->apiKeyProvider->method('getApiKey')->willReturn('test-key');
        $this->customFieldService->method('getGroup')->willReturn(['item' => ['id' => 'g1']]);

        $response = $this->tool->handle($this->makeRequest(['action' => 'get', 'groupId' => 'g1']));
        $this->assertFalse($response->isError());
    }

    public function testUpdateGroupSuccess(): void
    {
        $this->apiKeyProvider->method('getApiKey')->willReturn('test-key');
        $this->customFieldService->method('updateGroup')->willReturn(['item' => ['id' => 'g1']]);

        $response = $this->tool->handle($this->makeRequest(['action' => 'update', 'groupId' => 'g1', 'name' => 'Updated']));
        $this->assertFalse($response->isError());
    }

    public function testDeleteGroupSuccess(): void
    {
        $this->apiKeyProvider->method('getApiKey')->willReturn('test-key');
        $this->customFieldService->method('deleteGroup')->willReturn([]);

        $response = $this->tool->handle($this->makeRequest(['action' => 'delete', 'groupId' => 'g1']));
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

        $response = $this->tool->handle($this->makeRequest(['action' => 'create_base', 'projectId' => 'p1']));
        $this->assertTrue($response->isError());
    }

    public function testSchemaReturnsArray(): void
    {
        $data = $this->tool->toArray();
        $this->assertIsArray($data);
        $this->assertArrayHasKey('inputSchema', $data);
    }
}
