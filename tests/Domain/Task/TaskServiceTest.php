<?php

declare(strict_types=1);

namespace App\Tests\Domain\Task;

use App\Domain\Task\TaskService;
use App\Planka\Client\PlankaClientInterface;
use App\Planka\Exception\AuthenticationException;
use App\Planka\Exception\PlankaApiException;
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
        $taskListResponse = ['item' => ['id' => 'tl123', 'name' => 'Tasks']];
        $taskResponse = ['item' => ['id' => 'task456', 'name' => 'Task 1']];

        $this->plankaClient
            ->expects($this->exactly(2))
            ->method('post')
            ->willReturnCallback(function (string $apiKey, string $path, array $body) use ($taskListResponse, $taskResponse): array {
                if ($path === '/api/cards/card123/task-lists') {
                    $this->assertSame('test-api-key', $apiKey);
                    $this->assertSame(['name' => 'Tasks'], $body);

                    return $taskListResponse;
                }

                $this->assertSame('/api/task-lists/tl123/tasks', $path);
                $this->assertSame(['name' => 'Task 1'], $body);

                return $taskResponse;
            });

        $result = $this->service->createTasks('test-api-key', 'card123', ['Task 1']);

        $this->assertArrayHasKey('taskList', $result);
        $this->assertArrayHasKey('tasks', $result);
        $this->assertSame($taskListResponse, $result['taskList']);
        $this->assertSame([$taskResponse], $result['tasks']);
    }

    public function testCreateTasksWithMultipleTasks(): void
    {
        $taskListResponse = ['item' => ['id' => 'tl123', 'name' => 'Tasks']];

        $postCallCount = 0;

        $this->plankaClient
            ->expects($this->exactly(4))
            ->method('post')
            ->willReturnCallback(function (string $apiKey, string $path, array $body) use ($taskListResponse, &$postCallCount): array {
                ++$postCallCount;

                if ($postCallCount === 1) {
                    $this->assertSame('/api/cards/card123/task-lists', $path);
                    $this->assertSame(['name' => 'Tasks'], $body);

                    return $taskListResponse;
                }

                $this->assertSame('/api/task-lists/tl123/tasks', $path);

                return ['item' => ['id' => 'task' . $postCallCount, 'name' => $body['name']]];
            });

        $result = $this->service->createTasks(
            'test-api-key',
            'card123',
            ['Task 1', 'Task 2', 'Task 3'],
        );

        $this->assertArrayHasKey('taskList', $result);
        $this->assertArrayHasKey('tasks', $result);
        $this->assertCount(3, $result['tasks']);
    }

    public function testUpdateTaskName(): void
    {
        $expected = ['item' => ['id' => 'task123', 'name' => 'New name']];

        $this->plankaClient
            ->expects($this->once())
            ->method('patch')
            ->with('test-api-key', '/api/tasks/task123', ['name' => 'New name'])
            ->willReturn($expected);

        $result = $this->service->updateTask('test-api-key', 'task123', name: 'New name');

        $this->assertSame($expected, $result);
    }

    public function testUpdateTaskCompletion(): void
    {
        $expected = ['item' => ['id' => 'task123', 'isCompleted' => true]];

        $this->plankaClient
            ->expects($this->once())
            ->method('patch')
            ->with('test-api-key', '/api/tasks/task123', ['isCompleted' => true])
            ->willReturn($expected);

        $result = $this->service->updateTask('test-api-key', 'task123', isCompleted: true);

        $this->assertSame($expected, $result);
    }

    public function testUpdateTaskNameAndCompletion(): void
    {
        $expected = ['item' => ['id' => 'task123', 'name' => 'Done task', 'isCompleted' => true]];

        $this->plankaClient
            ->expects($this->once())
            ->method('patch')
            ->with('test-api-key', '/api/tasks/task123', ['name' => 'Done task', 'isCompleted' => true])
            ->willReturn($expected);

        $result = $this->service->updateTask(
            'test-api-key',
            'task123',
            name: 'Done task',
            isCompleted: true,
        );

        $this->assertSame($expected, $result);
    }

    public function testDeleteTask(): void
    {
        $this->plankaClient
            ->expects($this->once())
            ->method('delete')
            ->with('test-api-key', '/api/tasks/task123')
            ->willReturn([]);

        $result = $this->service->deleteTask('test-api-key', 'task123');

        $this->assertSame([], $result);
    }

    public function testCreateTasksPropagatesAuthenticationException(): void
    {
        $this->plankaClient
            ->method('post')
            ->willThrowException(new AuthenticationException('Unauthorized'));

        $this->expectException(AuthenticationException::class);

        $this->service->createTasks('bad-key', 'card123', ['Task 1']);
    }

    public function testCreateTasksPropagatesPlankaApiException(): void
    {
        $this->plankaClient
            ->method('post')
            ->willThrowException(new PlankaApiException('Server error'));

        $this->expectException(PlankaApiException::class);

        $this->service->createTasks('test-api-key', 'card123', ['Task 1']);
    }

    public function testUpdateTaskPropagatesAuthenticationException(): void
    {
        $this->plankaClient
            ->method('patch')
            ->willThrowException(new AuthenticationException('Unauthorized'));

        $this->expectException(AuthenticationException::class);

        $this->service->updateTask('bad-key', 'task123', name: 'New name');
    }

    public function testUpdateTaskPropagatesPlankaApiException(): void
    {
        $this->plankaClient
            ->method('patch')
            ->willThrowException(new PlankaApiException('Server error'));

        $this->expectException(PlankaApiException::class);

        $this->service->updateTask('test-api-key', 'task123', name: 'New name');
    }

    public function testDeleteTaskPropagatesAuthenticationException(): void
    {
        $this->plankaClient
            ->method('delete')
            ->willThrowException(new AuthenticationException('Unauthorized'));

        $this->expectException(AuthenticationException::class);

        $this->service->deleteTask('bad-key', 'task123');
    }

    public function testDeleteTaskPropagatesPlankaApiException(): void
    {
        $this->plankaClient
            ->method('delete')
            ->willThrowException(new PlankaApiException('Server error'));

        $this->expectException(PlankaApiException::class);

        $this->service->deleteTask('test-api-key', 'task123');
    }

    // --- updateTaskList ---

    public function testUpdateTaskListWithName(): void
    {
        $expected = ['item' => ['id' => 'tl1', 'name' => 'New Name']];

        $this->plankaClient
            ->expects($this->once())
            ->method('patch')
            ->with('test-api-key', '/api/task-lists/tl1', ['name' => 'New Name'])
            ->willReturn($expected);

        $result = $this->service->updateTaskList('test-api-key', 'tl1', 'New Name');

        $this->assertSame($expected, $result);
    }

    public function testUpdateTaskListWithNullNameSendsEmptyBody(): void
    {
        $expected = ['item' => ['id' => 'tl1']];

        $this->plankaClient
            ->expects($this->once())
            ->method('patch')
            ->with('test-api-key', '/api/task-lists/tl1', [])
            ->willReturn($expected);

        $result = $this->service->updateTaskList('test-api-key', 'tl1', null);

        $this->assertSame($expected, $result);
    }

    public function testUpdateTaskListPropagatesAuthException(): void
    {
        $this->plankaClient
            ->method('patch')
            ->willThrowException(new AuthenticationException('Unauthorized'));

        $this->expectException(AuthenticationException::class);

        $this->service->updateTaskList('bad-key', 'tl1', 'Name');
    }

    public function testUpdateTaskListPropagatesApiException(): void
    {
        $this->plankaClient
            ->method('patch')
            ->willThrowException(new PlankaApiException('Server error'));

        $this->expectException(PlankaApiException::class);

        $this->service->updateTaskList('test-api-key', 'tl1', 'Name');
    }

    // --- deleteTaskList ---

    public function testDeleteTaskList(): void
    {
        $this->plankaClient
            ->expects($this->once())
            ->method('delete')
            ->with('test-api-key', '/api/task-lists/tl1')
            ->willReturn([]);

        $result = $this->service->deleteTaskList('test-api-key', 'tl1');

        $this->assertSame([], $result);
    }

    public function testDeleteTaskListPropagatesAuthException(): void
    {
        $this->plankaClient
            ->method('delete')
            ->willThrowException(new AuthenticationException('Unauthorized'));

        $this->expectException(AuthenticationException::class);

        $this->service->deleteTaskList('bad-key', 'tl1');
    }

    public function testDeleteTaskListPropagatesApiException(): void
    {
        $this->plankaClient
            ->method('delete')
            ->willThrowException(new PlankaApiException('Server error'));

        $this->expectException(PlankaApiException::class);

        $this->service->deleteTaskList('test-api-key', 'tl1');
    }
}
