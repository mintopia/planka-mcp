<?php

declare(strict_types=1);

namespace Tests\Unit\Mcp\Tools;

use App\Domain\Project\ProjectServiceInterface;
use App\Exception\ValidationException;
use App\Http\ApiKeyProviderInterface;
use App\Mcp\Tools\GetStructureTool;
use App\Planka\Exception\AuthenticationException;
use Laravel\Mcp\Request;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

final class GetStructureToolTest extends TestCase
{
    private ProjectServiceInterface&MockObject $projectService;
    private ApiKeyProviderInterface&MockObject $apiKeyProvider;
    private GetStructureTool $tool;

    protected function setUp(): void
    {
        $this->projectService = $this->createMock(ProjectServiceInterface::class);
        $this->apiKeyProvider = $this->createMock(ApiKeyProviderInterface::class);
        $this->tool = new GetStructureTool($this->projectService, $this->apiKeyProvider);
    }

    private function makeRequest(array $params = []): Request
    {
        return new Request(arguments: $params);
    }

    public function testHandleSuccess(): void
    {
        $expected = ['items' => [['id' => 'proj1']]];
        $this->apiKeyProvider->method('getApiKey')->willReturn('test-key');
        $this->projectService->method('getStructure')->willReturn($expected);

        $response = $this->tool->handle($this->makeRequest());
        $this->assertFalse($response->isError());
    }

    public function testHandleAuthError(): void
    {
        $this->apiKeyProvider->method('getApiKey')->willThrowException(new ValidationException('No API key'));

        $response = $this->tool->handle($this->makeRequest());
        $this->assertTrue($response->isError());
        $this->assertStringContainsString('No API key', (string) $response->content());
    }

    public function testHandleServiceError(): void
    {
        $this->apiKeyProvider->method('getApiKey')->willReturn('test-key');
        $this->projectService->method('getStructure')->willThrowException(new AuthenticationException('Unauthorized'));

        $response = $this->tool->handle($this->makeRequest());
        $this->assertTrue($response->isError());
        $this->assertStringContainsString('Unauthorized', (string) $response->content());
    }

    public function testSchemaReturnsArray(): void
    {
        $data = $this->tool->toArray();
        $this->assertIsArray($data);
        $this->assertArrayHasKey('inputSchema', $data);
    }
}
