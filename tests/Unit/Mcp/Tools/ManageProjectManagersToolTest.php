<?php

declare(strict_types=1);

namespace Tests\Unit\Mcp\Tools;

use App\Domain\Project\ProjectServiceInterface;
use App\Exception\ValidationException;
use App\Http\ApiKeyProviderInterface;
use App\Mcp\Tools\ManageProjectManagersTool;
use Laravel\Mcp\Request;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

final class ManageProjectManagersToolTest extends TestCase
{
    private ProjectServiceInterface&MockObject $projectService;
    private ApiKeyProviderInterface&MockObject $apiKeyProvider;
    private ManageProjectManagersTool $tool;

    protected function setUp(): void
    {
        $this->projectService = $this->createMock(ProjectServiceInterface::class);
        $this->apiKeyProvider = $this->createMock(ApiKeyProviderInterface::class);
        $this->tool = new ManageProjectManagersTool($this->projectService, $this->apiKeyProvider);
    }

    private function makeRequest(array $params = []): Request
    {
        return new Request(arguments: $params);
    }

    public function testAddSuccess(): void
    {
        $this->apiKeyProvider->method('getApiKey')->willReturn('test-key');
        $this->projectService->method('addProjectManager')->willReturn(['item' => ['id' => 'pm1']]);

        $response = $this->tool->handle($this->makeRequest(['action' => 'add', 'projectId' => 'p1', 'userId' => 'u1']));
        $this->assertFalse($response->isError());
    }

    public function testAddMissingUserId(): void
    {
        $this->apiKeyProvider->method('getApiKey')->willReturn('test-key');

        $response = $this->tool->handle($this->makeRequest(['action' => 'add', 'projectId' => 'p1']));
        $this->assertTrue($response->isError());
        $this->assertStringContainsString('userId required', (string) $response->content());
    }

    public function testRemoveSuccess(): void
    {
        $this->apiKeyProvider->method('getApiKey')->willReturn('test-key');
        $this->projectService->method('removeProjectManager')->willReturn([]);

        $response = $this->tool->handle($this->makeRequest(['action' => 'remove', 'projectId' => 'p1', 'projectManagerId' => 'pm1']));
        $this->assertFalse($response->isError());
    }

    public function testRemoveMissingProjectManagerId(): void
    {
        $this->apiKeyProvider->method('getApiKey')->willReturn('test-key');

        $response = $this->tool->handle($this->makeRequest(['action' => 'remove', 'projectId' => 'p1']));
        $this->assertTrue($response->isError());
        $this->assertStringContainsString('projectManagerId required', (string) $response->content());
    }

    public function testInvalidAction(): void
    {
        $this->apiKeyProvider->method('getApiKey')->willReturn('test-key');

        $response = $this->tool->handle($this->makeRequest(['action' => 'invalid', 'projectId' => 'p1']));
        $this->assertTrue($response->isError());
    }

    public function testAuthError(): void
    {
        $this->apiKeyProvider->method('getApiKey')->willThrowException(new ValidationException('No API key'));

        $response = $this->tool->handle($this->makeRequest(['action' => 'add', 'projectId' => 'p1', 'userId' => 'u1']));
        $this->assertTrue($response->isError());
    }

    public function testSchemaReturnsArray(): void
    {
        $data = $this->tool->toArray();
        $this->assertIsArray($data);
        $this->assertArrayHasKey('inputSchema', $data);
    }
}
