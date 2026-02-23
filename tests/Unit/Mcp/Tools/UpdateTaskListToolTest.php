<?php

declare(strict_types=1);

namespace Tests\Unit\Mcp\Tools;

use App\Domain\Task\TaskServiceInterface;
use App\Exception\ValidationException;
use App\Http\ApiKeyProviderInterface;
use App\Mcp\Tools\UpdateTaskListTool;
use Laravel\Mcp\Request;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

final class UpdateTaskListToolTest extends TestCase
{
    private TaskServiceInterface&MockObject $taskService;
    private ApiKeyProviderInterface&MockObject $apiKeyProvider;
    private UpdateTaskListTool $tool;

    protected function setUp(): void
    {
        $this->taskService = $this->createMock(TaskServiceInterface::class);
        $this->apiKeyProvider = $this->createMock(ApiKeyProviderInterface::class);
        $this->tool = new UpdateTaskListTool($this->taskService, $this->apiKeyProvider);
    }

    private function makeRequest(array $params = []): Request
    {
        return new Request(arguments: $params);
    }

    public function testHandleSuccess(): void
    {
        $this->apiKeyProvider->method('getApiKey')->willReturn('test-key');
        $this->taskService->method('updateTaskList')->willReturn(['item' => ['id' => 'tl1']]);

        $response = $this->tool->handle($this->makeRequest(['taskListId' => 'tl1', 'name' => 'Updated']));
        $this->assertFalse($response->isError());
    }

    public function testHandleAuthError(): void
    {
        $this->apiKeyProvider->method('getApiKey')->willThrowException(new ValidationException('No API key'));

        $response = $this->tool->handle($this->makeRequest(['taskListId' => 'tl1']));
        $this->assertTrue($response->isError());
    }

    public function testSchemaReturnsArray(): void
    {
        $data = $this->tool->toArray();
        $this->assertIsArray($data);
        $this->assertArrayHasKey('inputSchema', $data);
    }
}
