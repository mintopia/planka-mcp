<?php

declare(strict_types=1);

namespace App\Tests\Mcp;

use App\Domain\Task\TaskService;
use App\Infrastructure\Http\ApiKeyProvider;
use App\Mcp\TaskTools;
use App\Planka\Exception\AuthenticationException;
use App\Planka\Exception\PlankaApiException;
use App\Planka\Exception\PlankaNotFoundException;
use App\Shared\Exception\ValidationException;
use Mcp\Exception\ToolCallException;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

final class TaskToolsTest extends TestCase
{
    private TaskService&MockObject $taskService;
    private ApiKeyProvider&MockObject $apiKeyProvider;
    private TaskTools $tools;

    protected function setUp(): void
    {
        $this->taskService = $this->createMock(TaskService::class);
        $this->apiKeyProvider = $this->createMock(ApiKeyProvider::class);
        $this->tools = new TaskTools($this->taskService, $this->apiKeyProvider);
    }

    // --- createTasks ---

    public function testCreateTasksSuccess(): void
    {
        $expected = [
            'taskList' => ['item' => ['id' => 'tl1', 'name' => 'Tasks']],
            'tasks' => [['item' => ['id' => 'task1', 'name' => 'Write tests']]],
        ];

        $this->apiKeyProvider
            ->expects($this->once())
            ->method('getApiKey')
            ->willReturn('test-api-key');

        $this->taskService
            ->expects($this->once())
            ->method('createTasks')
            ->with('test-api-key', 'card1', ['Write tests'])
            ->willReturn($expected);

        $result = $this->tools->createTasks('card1', ['Write tests']);

        $this->assertSame($expected, $result);
    }

    public function testCreateTasksWithMultipleTasksSuccess(): void
    {
        $tasks = ['Write tests', 'Run linter', 'Deploy'];
        $expected = [
            'taskList' => ['item' => ['id' => 'tl1', 'name' => 'Tasks']],
            'tasks' => [
                ['item' => ['id' => 'task1', 'name' => 'Write tests']],
                ['item' => ['id' => 'task2', 'name' => 'Run linter']],
                ['item' => ['id' => 'task3', 'name' => 'Deploy']],
            ],
        ];

        $this->apiKeyProvider->method('getApiKey')->willReturn('test-api-key');

        $this->taskService
            ->expects($this->once())
            ->method('createTasks')
            ->with('test-api-key', 'card1', $tasks)
            ->willReturn($expected);

        $result = $this->tools->createTasks('card1', $tasks);

        $this->assertSame($expected, $result);
    }

    public function testCreateTasksWithEmptyArrayThrowsToolCallException(): void
    {
        $this->apiKeyProvider->expects($this->never())->method('getApiKey');
        $this->taskService->expects($this->never())->method('createTasks');

        $this->expectException(ToolCallException::class);
        $this->expectExceptionMessage('At least one task name is required.');

        $this->tools->createTasks('card1', []);
    }

    public function testCreateTasksMissingApiKeyThrowsToolCallException(): void
    {
        $this->apiKeyProvider
            ->expects($this->once())
            ->method('getApiKey')
            ->willThrowException(new ValidationException('Planka API key required.'));

        $this->taskService->expects($this->never())->method('createTasks');

        $this->expectException(ToolCallException::class);
        $this->expectExceptionMessage('Planka API key required.');

        $this->tools->createTasks('card1', ['Task 1']);
    }

    public function testCreateTasksWrapsAuthExceptionInToolCallException(): void
    {
        $this->apiKeyProvider->method('getApiKey')->willReturn('bad-key');

        $this->taskService
            ->method('createTasks')
            ->willThrowException(new AuthenticationException('Unauthorized'));

        $this->expectException(ToolCallException::class);
        $this->expectExceptionMessage('Unauthorized');

        $this->tools->createTasks('card1', ['Task 1']);
    }

    public function testCreateTasksWrapsPlankaApiExceptionInToolCallException(): void
    {
        $this->apiKeyProvider->method('getApiKey')->willReturn('test-api-key');

        $this->taskService
            ->method('createTasks')
            ->willThrowException(new PlankaApiException('Server error'));

        $this->expectException(ToolCallException::class);
        $this->expectExceptionMessage('Server error');

        $this->tools->createTasks('card1', ['Task 1']);
    }

    public function testCreateTasksWrapsNotFoundExceptionInToolCallException(): void
    {
        $this->apiKeyProvider->method('getApiKey')->willReturn('test-api-key');

        $this->taskService
            ->method('createTasks')
            ->willThrowException(new PlankaNotFoundException('Not found', 404));

        $this->expectException(ToolCallException::class);
        $this->expectExceptionMessage('Not found');

        $this->tools->createTasks('card1', ['Task 1']);
    }

    // --- updateTask ---

    public function testUpdateTaskSuccess(): void
    {
        $expected = ['item' => ['id' => 'task1', 'name' => 'Updated name', 'isCompleted' => true]];

        $this->apiKeyProvider
            ->expects($this->once())
            ->method('getApiKey')
            ->willReturn('test-api-key');

        $this->taskService
            ->expects($this->once())
            ->method('updateTask')
            ->with('test-api-key', 'task1', 'Updated name', true)
            ->willReturn($expected);

        $result = $this->tools->updateTask('task1', 'Updated name', true);

        $this->assertSame($expected, $result);
    }

    public function testUpdateTaskWithNullableParamsSuccess(): void
    {
        $expected = ['item' => ['id' => 'task1']];

        $this->apiKeyProvider->method('getApiKey')->willReturn('test-api-key');

        $this->taskService
            ->expects($this->once())
            ->method('updateTask')
            ->with('test-api-key', 'task1', null, null)
            ->willReturn($expected);

        $result = $this->tools->updateTask('task1');

        $this->assertSame($expected, $result);
    }

    public function testUpdateTaskMissingApiKeyThrowsToolCallException(): void
    {
        $this->apiKeyProvider
            ->expects($this->once())
            ->method('getApiKey')
            ->willThrowException(new ValidationException('Planka API key required.'));

        $this->taskService->expects($this->never())->method('updateTask');

        $this->expectException(ToolCallException::class);
        $this->expectExceptionMessage('Planka API key required.');

        $this->tools->updateTask('task1', 'New name');
    }

    public function testUpdateTaskWrapsAuthExceptionInToolCallException(): void
    {
        $this->apiKeyProvider->method('getApiKey')->willReturn('bad-key');

        $this->taskService
            ->method('updateTask')
            ->willThrowException(new AuthenticationException('Unauthorized'));

        $this->expectException(ToolCallException::class);
        $this->expectExceptionMessage('Unauthorized');

        $this->tools->updateTask('task1', 'New name');
    }

    public function testUpdateTaskWrapsNotFoundExceptionInToolCallException(): void
    {
        $this->apiKeyProvider->method('getApiKey')->willReturn('test-api-key');

        $this->taskService
            ->method('updateTask')
            ->willThrowException(new PlankaNotFoundException('Not found', 404));

        $this->expectException(ToolCallException::class);
        $this->expectExceptionMessage('Not found');

        $this->tools->updateTask('task1', 'New name');
    }

    // --- deleteTask ---

    public function testDeleteTaskSuccess(): void
    {
        $this->apiKeyProvider
            ->expects($this->once())
            ->method('getApiKey')
            ->willReturn('test-api-key');

        $this->taskService
            ->expects($this->once())
            ->method('deleteTask')
            ->with('test-api-key', 'task1')
            ->willReturn([]);

        $result = $this->tools->deleteTask('task1');

        $this->assertSame([], $result);
    }

    public function testDeleteTaskMissingApiKeyThrowsToolCallException(): void
    {
        $this->apiKeyProvider
            ->expects($this->once())
            ->method('getApiKey')
            ->willThrowException(new ValidationException('Planka API key required.'));

        $this->taskService->expects($this->never())->method('deleteTask');

        $this->expectException(ToolCallException::class);
        $this->expectExceptionMessage('Planka API key required.');

        $this->tools->deleteTask('task1');
    }

    public function testDeleteTaskWrapsAuthExceptionInToolCallException(): void
    {
        $this->apiKeyProvider->method('getApiKey')->willReturn('bad-key');

        $this->taskService
            ->method('deleteTask')
            ->willThrowException(new AuthenticationException('Unauthorized'));

        $this->expectException(ToolCallException::class);
        $this->expectExceptionMessage('Unauthorized');

        $this->tools->deleteTask('task1');
    }

    public function testDeleteTaskWrapsPlankaApiExceptionInToolCallException(): void
    {
        $this->apiKeyProvider->method('getApiKey')->willReturn('test-api-key');

        $this->taskService
            ->method('deleteTask')
            ->willThrowException(new PlankaApiException('Server error'));

        $this->expectException(ToolCallException::class);
        $this->expectExceptionMessage('Server error');

        $this->tools->deleteTask('task1');
    }

    public function testDeleteTaskWrapsNotFoundExceptionInToolCallException(): void
    {
        $this->apiKeyProvider->method('getApiKey')->willReturn('test-api-key');

        $this->taskService
            ->method('deleteTask')
            ->willThrowException(new PlankaNotFoundException('Not found', 404));

        $this->expectException(ToolCallException::class);
        $this->expectExceptionMessage('Not found');

        $this->tools->deleteTask('task1');
    }

    // --- updateTaskList ---

    public function testUpdateTaskListSuccess(): void
    {
        $expected = ['item' => ['id' => 'tl1', 'name' => 'New Name']];

        $this->apiKeyProvider
            ->expects($this->once())
            ->method('getApiKey')
            ->willReturn('test-api-key');

        $this->taskService
            ->expects($this->once())
            ->method('updateTaskList')
            ->with('test-api-key', 'tl1', 'New Name')
            ->willReturn($expected);

        $result = $this->tools->updateTaskList('tl1', 'New Name');

        $this->assertSame($expected, $result);
    }

    public function testUpdateTaskListWithNullNameSuccess(): void
    {
        $expected = ['item' => ['id' => 'tl1']];

        $this->apiKeyProvider->method('getApiKey')->willReturn('test-api-key');

        $this->taskService
            ->expects($this->once())
            ->method('updateTaskList')
            ->with('test-api-key', 'tl1', null)
            ->willReturn($expected);

        $result = $this->tools->updateTaskList('tl1');

        $this->assertSame($expected, $result);
    }

    public function testUpdateTaskListMissingApiKeyThrowsToolCallException(): void
    {
        $this->apiKeyProvider
            ->expects($this->once())
            ->method('getApiKey')
            ->willThrowException(new ValidationException('Planka API key required.'));

        $this->taskService->expects($this->never())->method('updateTaskList');

        $this->expectException(ToolCallException::class);
        $this->expectExceptionMessage('Planka API key required.');

        $this->tools->updateTaskList('tl1', 'Name');
    }

    public function testUpdateTaskListWrapsAuthExceptionInToolCallException(): void
    {
        $this->apiKeyProvider->method('getApiKey')->willReturn('bad-key');

        $this->taskService
            ->method('updateTaskList')
            ->willThrowException(new AuthenticationException('Unauthorized'));

        $this->expectException(ToolCallException::class);
        $this->expectExceptionMessage('Unauthorized');

        $this->tools->updateTaskList('tl1', 'Name');
    }

    public function testUpdateTaskListWrapsPlankaApiExceptionInToolCallException(): void
    {
        $this->apiKeyProvider->method('getApiKey')->willReturn('test-api-key');

        $this->taskService
            ->method('updateTaskList')
            ->willThrowException(new PlankaApiException('Server error'));

        $this->expectException(ToolCallException::class);
        $this->expectExceptionMessage('Server error');

        $this->tools->updateTaskList('tl1', 'Name');
    }

    public function testUpdateTaskListWrapsNotFoundExceptionInToolCallException(): void
    {
        $this->apiKeyProvider->method('getApiKey')->willReturn('test-api-key');

        $this->taskService
            ->method('updateTaskList')
            ->willThrowException(new PlankaNotFoundException('Not found', 404));

        $this->expectException(ToolCallException::class);
        $this->expectExceptionMessage('Not found');

        $this->tools->updateTaskList('tl1', 'Name');
    }

    // --- deleteTaskList ---

    public function testDeleteTaskListSuccess(): void
    {
        $this->apiKeyProvider
            ->expects($this->once())
            ->method('getApiKey')
            ->willReturn('test-api-key');

        $this->taskService
            ->expects($this->once())
            ->method('deleteTaskList')
            ->with('test-api-key', 'tl1')
            ->willReturn([]);

        $result = $this->tools->deleteTaskList('tl1');

        $this->assertSame([], $result);
    }

    public function testDeleteTaskListMissingApiKeyThrowsToolCallException(): void
    {
        $this->apiKeyProvider
            ->expects($this->once())
            ->method('getApiKey')
            ->willThrowException(new ValidationException('Planka API key required.'));

        $this->taskService->expects($this->never())->method('deleteTaskList');

        $this->expectException(ToolCallException::class);
        $this->expectExceptionMessage('Planka API key required.');

        $this->tools->deleteTaskList('tl1');
    }

    public function testDeleteTaskListWrapsAuthExceptionInToolCallException(): void
    {
        $this->apiKeyProvider->method('getApiKey')->willReturn('bad-key');

        $this->taskService
            ->method('deleteTaskList')
            ->willThrowException(new AuthenticationException('Unauthorized'));

        $this->expectException(ToolCallException::class);
        $this->expectExceptionMessage('Unauthorized');

        $this->tools->deleteTaskList('tl1');
    }

    public function testDeleteTaskListWrapsPlankaApiExceptionInToolCallException(): void
    {
        $this->apiKeyProvider->method('getApiKey')->willReturn('test-api-key');

        $this->taskService
            ->method('deleteTaskList')
            ->willThrowException(new PlankaApiException('Server error'));

        $this->expectException(ToolCallException::class);
        $this->expectExceptionMessage('Server error');

        $this->tools->deleteTaskList('tl1');
    }

    public function testDeleteTaskListWrapsNotFoundExceptionInToolCallException(): void
    {
        $this->apiKeyProvider->method('getApiKey')->willReturn('test-api-key');

        $this->taskService
            ->method('deleteTaskList')
            ->willThrowException(new PlankaNotFoundException('Not found', 404));

        $this->expectException(ToolCallException::class);
        $this->expectExceptionMessage('Not found');

        $this->tools->deleteTaskList('tl1');
    }
}
