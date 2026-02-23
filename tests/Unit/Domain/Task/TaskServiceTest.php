<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\Task;

use App\Domain\Task\TaskService;
use App\Planka\Exception\AuthenticationException;
use App\Planka\Exception\PlankaApiException;
use App\Planka\PlankaClientInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

final class TaskServiceTest extends TestCase
{
    private PlankaClientInterface&MockObject $plankaClient;
    private TaskService $service;

    protected function setUp(): void
    {
        $this->plankaClient = $this->createMock(PlankaClientInterface::class);
        $this->service = new TaskService($this->plankaClient);
    }

    public function testCreateTasksSuccess(): void
    {
        $taskListResult = ['item' => ['id' => 'tl1']];
        $taskResult = ['item' => ['id' => 'task1', 'name' => 'Task 1']];

        $this->plankaClient
            ->expects($this->exactly(3))
            ->method('post')
            ->willReturnOnConsecutiveCalls($taskListResult, $taskResult, $taskResult);

        $result = $this->service->createTasks('test-api-key', 'card1', ['Task 1', 'Task 2']);
        $this->assertArrayHasKey('taskList', $result);
        $this->assertArrayHasKey('tasks', $result);
        $this->assertCount(2, $result['tasks']);
    }

    public function testCreateTasksSingleTask(): void
    {
        $taskListResult = ['item' => ['id' => 'tl1']];
        $taskResult = ['item' => ['id' => 'task1', 'name' => 'Task']];

        $this->plankaClient
            ->expects($this->exactly(2))
            ->method('post')
            ->willReturnOnConsecutiveCalls($taskListResult, $taskResult);

        $result = $this->service->createTasks('test-api-key', 'card1', ['Task']);
        $this->assertCount(1, $result['tasks']);
    }

    public function testCreateTasksPropagatesAuthException(): void
    {
        $this->plankaClient->method('post')->willThrowException(new AuthenticationException('Unauthorized'));
        $this->expectException(AuthenticationException::class);
        $this->service->createTasks('bad-key', 'card1', ['Task']);
    }

    public function testCreateTasksPropagatesApiException(): void
    {
        $this->plankaClient->method('post')->willThrowException(new PlankaApiException('Server error'));
        $this->expectException(PlankaApiException::class);
        $this->service->createTasks('test-api-key', 'card1', ['Task']);
    }

    public function testUpdateTaskWithNameAndCompletion(): void
    {
        $expected = ['item' => ['id' => 'task1', 'name' => 'Updated', 'isCompleted' => true]];

        $this->plankaClient
            ->expects($this->once())
            ->method('patch')
            ->with('test-api-key', '/api/tasks/task1', ['name' => 'Updated', 'isCompleted' => true])
            ->willReturn($expected);

        $this->assertSame($expected, $this->service->updateTask('test-api-key', 'task1', 'Updated', true));
    }

    public function testUpdateTaskWithNameOnly(): void
    {
        $expected = ['item' => ['id' => 'task1', 'name' => 'Updated']];

        $this->plankaClient
            ->expects($this->once())
            ->method('patch')
            ->with('test-api-key', '/api/tasks/task1', ['name' => 'Updated'])
            ->willReturn($expected);

        $this->assertSame($expected, $this->service->updateTask('test-api-key', 'task1', 'Updated'));
    }

    public function testUpdateTaskWithCompletionOnly(): void
    {
        $expected = ['item' => ['id' => 'task1', 'isCompleted' => true]];

        $this->plankaClient
            ->expects($this->once())
            ->method('patch')
            ->with('test-api-key', '/api/tasks/task1', ['isCompleted' => true])
            ->willReturn($expected);

        $this->assertSame($expected, $this->service->updateTask('test-api-key', 'task1', null, true));
    }

    public function testUpdateTaskWithNullsSendsEmptyBody(): void
    {
        $expected = ['item' => ['id' => 'task1']];

        $this->plankaClient
            ->expects($this->once())
            ->method('patch')
            ->with('test-api-key', '/api/tasks/task1', [])
            ->willReturn($expected);

        $this->assertSame($expected, $this->service->updateTask('test-api-key', 'task1'));
    }

    public function testUpdateTaskPropagatesAuthException(): void
    {
        $this->plankaClient->method('patch')->willThrowException(new AuthenticationException('Unauthorized'));
        $this->expectException(AuthenticationException::class);
        $this->service->updateTask('bad-key', 'task1', 'Name');
    }

    public function testUpdateTaskPropagatesApiException(): void
    {
        $this->plankaClient->method('patch')->willThrowException(new PlankaApiException('Server error'));
        $this->expectException(PlankaApiException::class);
        $this->service->updateTask('test-api-key', 'task1', 'Name');
    }

    public function testDeleteTaskSuccess(): void
    {
        $this->plankaClient
            ->expects($this->once())
            ->method('delete')
            ->with('test-api-key', '/api/tasks/task1')
            ->willReturn([]);

        $this->assertSame([], $this->service->deleteTask('test-api-key', 'task1'));
    }

    public function testDeleteTaskPropagatesAuthException(): void
    {
        $this->plankaClient->method('delete')->willThrowException(new AuthenticationException('Unauthorized'));
        $this->expectException(AuthenticationException::class);
        $this->service->deleteTask('bad-key', 'task1');
    }

    public function testDeleteTaskPropagatesApiException(): void
    {
        $this->plankaClient->method('delete')->willThrowException(new PlankaApiException('Server error'));
        $this->expectException(PlankaApiException::class);
        $this->service->deleteTask('test-api-key', 'task1');
    }

    public function testUpdateTaskListWithName(): void
    {
        $expected = ['item' => ['id' => 'tl1', 'name' => 'Updated']];

        $this->plankaClient
            ->expects($this->once())
            ->method('patch')
            ->with('test-api-key', '/api/task-lists/tl1', ['name' => 'Updated'])
            ->willReturn($expected);

        $this->assertSame($expected, $this->service->updateTaskList('test-api-key', 'tl1', 'Updated'));
    }

    public function testUpdateTaskListWithNullNameSendsEmptyBody(): void
    {
        $expected = ['item' => ['id' => 'tl1']];

        $this->plankaClient
            ->expects($this->once())
            ->method('patch')
            ->with('test-api-key', '/api/task-lists/tl1', [])
            ->willReturn($expected);

        $this->assertSame($expected, $this->service->updateTaskList('test-api-key', 'tl1', null));
    }

    public function testDeleteTaskListSuccess(): void
    {
        $this->plankaClient
            ->expects($this->once())
            ->method('delete')
            ->with('test-api-key', '/api/task-lists/tl1')
            ->willReturn([]);

        $this->assertSame([], $this->service->deleteTaskList('test-api-key', 'tl1'));
    }

    public function testDeleteTaskListPropagatesAuthException(): void
    {
        $this->plankaClient->method('delete')->willThrowException(new AuthenticationException('Unauthorized'));
        $this->expectException(AuthenticationException::class);
        $this->service->deleteTaskList('bad-key', 'tl1');
    }

    public function testDeleteTaskListPropagatesApiException(): void
    {
        $this->plankaClient->method('delete')->willThrowException(new PlankaApiException('Server error'));
        $this->expectException(PlankaApiException::class);
        $this->service->deleteTaskList('test-api-key', 'tl1');
    }

    public function testGetTaskListSuccess(): void
    {
        $expected = ['item' => ['id' => 'tl1']];

        $this->plankaClient
            ->expects($this->once())
            ->method('get')
            ->with('test-api-key', '/api/task-lists/tl1')
            ->willReturn($expected);

        $this->assertSame($expected, $this->service->getTaskList('test-api-key', 'tl1'));
    }

    public function testGetTaskListPropagatesAuthException(): void
    {
        $this->plankaClient->method('get')->willThrowException(new AuthenticationException('Unauthorized'));
        $this->expectException(AuthenticationException::class);
        $this->service->getTaskList('bad-key', 'tl1');
    }

    public function testGetTaskListPropagatesApiException(): void
    {
        $this->plankaClient->method('get')->willThrowException(new PlankaApiException('Server error'));
        $this->expectException(PlankaApiException::class);
        $this->service->getTaskList('test-api-key', 'tl1');
    }
}
