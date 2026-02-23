<?php

declare(strict_types=1);

namespace Tests\Unit\Mcp\Tools;

use App\Domain\Project\ProjectServiceInterface;
use App\Exception\ValidationException;
use App\Http\ApiKeyProviderInterface;
use App\Mcp\Tools\ManageProjectsTool;
use App\Planka\Exception\AuthenticationException;
use Laravel\Mcp\Request;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

final class ManageProjectsToolTest extends TestCase
{
    private ProjectServiceInterface&MockObject $projectService;
    private ApiKeyProviderInterface&MockObject $apiKeyProvider;
    private ManageProjectsTool $tool;

    protected function setUp(): void
    {
        $this->projectService = $this->createMock(ProjectServiceInterface::class);
        $this->apiKeyProvider = $this->createMock(ApiKeyProviderInterface::class);
        $this->tool = new ManageProjectsTool($this->projectService, $this->apiKeyProvider);
    }

    private function makeRequest(array $params = []): Request
    {
        return new Request(arguments: $params);
    }

    public function testCreateSuccess(): void
    {
        $this->apiKeyProvider->method('getApiKey')->willReturn('test-key');
        $this->projectService->method('createProject')->willReturn(['item' => ['id' => 'p1']]);

        $response = $this->tool->handle($this->makeRequest(['action' => 'create', 'name' => 'Test']));
        $this->assertFalse($response->isError());
    }

    public function testCreateMissingName(): void
    {
        $this->apiKeyProvider->method('getApiKey')->willReturn('test-key');

        $response = $this->tool->handle($this->makeRequest(['action' => 'create']));
        $this->assertTrue($response->isError());
        $this->assertStringContainsString('name required', (string) $response->content());
    }

    public function testGetSuccess(): void
    {
        $this->apiKeyProvider->method('getApiKey')->willReturn('test-key');
        $this->projectService->method('getProject')->willReturn(['item' => ['id' => 'p1']]);

        $response = $this->tool->handle($this->makeRequest(['action' => 'get', 'projectId' => 'p1']));
        $this->assertFalse($response->isError());
    }

    public function testGetMissingProjectId(): void
    {
        $this->apiKeyProvider->method('getApiKey')->willReturn('test-key');

        $response = $this->tool->handle($this->makeRequest(['action' => 'get']));
        $this->assertTrue($response->isError());
        $this->assertStringContainsString('projectId required', (string) $response->content());
    }

    public function testUpdateSuccess(): void
    {
        $this->apiKeyProvider->method('getApiKey')->willReturn('test-key');
        $this->projectService->method('updateProject')->willReturn(['item' => ['id' => 'p1']]);

        $response = $this->tool->handle($this->makeRequest(['action' => 'update', 'projectId' => 'p1', 'name' => 'Updated']));
        $this->assertFalse($response->isError());
    }

    public function testDeleteSuccess(): void
    {
        $this->apiKeyProvider->method('getApiKey')->willReturn('test-key');
        $this->projectService->method('deleteProject')->willReturn([]);

        $response = $this->tool->handle($this->makeRequest(['action' => 'delete', 'projectId' => 'p1']));
        $this->assertFalse($response->isError());
    }

    public function testInvalidAction(): void
    {
        $this->apiKeyProvider->method('getApiKey')->willReturn('test-key');

        $response = $this->tool->handle($this->makeRequest(['action' => 'invalid']));
        $this->assertTrue($response->isError());
        $this->assertStringContainsString('Invalid action', (string) $response->content());
    }

    public function testAuthError(): void
    {
        $this->apiKeyProvider->method('getApiKey')->willThrowException(new ValidationException('No API key'));

        $response = $this->tool->handle($this->makeRequest(['action' => 'create', 'name' => 'Test']));
        $this->assertTrue($response->isError());
    }

    public function testServiceError(): void
    {
        $this->apiKeyProvider->method('getApiKey')->willReturn('test-key');
        $this->projectService->method('createProject')->willThrowException(new AuthenticationException('Unauthorized'));

        $response = $this->tool->handle($this->makeRequest(['action' => 'create', 'name' => 'Test']));
        $this->assertTrue($response->isError());
    }

    public function testSchemaReturnsArray(): void
    {
        $data = $this->tool->toArray();
        $this->assertIsArray($data);
        $this->assertArrayHasKey('inputSchema', $data);
    }
}
