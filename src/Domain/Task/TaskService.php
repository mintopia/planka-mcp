<?php

declare(strict_types=1);

namespace App\Domain\Task;

use App\Planka\Client\PlankaClientInterface;

final class TaskService
{
    public function __construct(
        private readonly PlankaClientInterface $plankaClient,
    ) {}

    /**
     * Creates tasks under a card.
     * Planka v2 requires: first create a task-list, then add tasks to it.
     *
     * @param string[] $tasks Task names to create
     * @return array<mixed>
     */
    public function createTasks(string $apiKey, string $cardId, array $tasks): array
    {
        // Step 1: Create a task list under the card
        $taskList = $this->plankaClient->post(
            $apiKey,
            '/api/cards/' . $cardId . '/task-lists',
            ['name' => 'Tasks'],
        );

        $taskListId = $taskList['item']['id'];

        // Step 2: Create each task in the task list
        $createdTasks = [];
        foreach ($tasks as $taskName) {
            $createdTasks[] = $this->plankaClient->post(
                $apiKey,
                '/api/task-lists/' . $taskListId . '/tasks',
                ['name' => $taskName],
            );
        }

        return [
            'taskList' => $taskList,
            'tasks' => $createdTasks,
        ];
    }

    /** @return array<mixed> */
    public function updateTask(
        string $apiKey,
        string $taskId,
        ?string $name = null,
        ?bool $isCompleted = null,
    ): array {
        $body = [];
        if ($name !== null) {
            $body['name'] = $name;
        }
        if ($isCompleted !== null) {
            $body['isCompleted'] = $isCompleted;
        }

        return $this->plankaClient->patch($apiKey, '/api/tasks/' . $taskId, $body);
    }

    /** @return array<mixed> */
    public function deleteTask(string $apiKey, string $taskId): array
    {
        return $this->plankaClient->delete($apiKey, '/api/tasks/' . $taskId);
    }

    /** @return array<mixed> */
    public function updateTaskList(string $apiKey, string $taskListId, ?string $name): array
    {
        $body = [];
        if ($name !== null) {
            $body['name'] = $name;
        }
        return $this->plankaClient->patch($apiKey, '/api/task-lists/' . $taskListId, $body);
    }

    /** @return array<mixed> */
    public function deleteTaskList(string $apiKey, string $taskListId): array
    {
        return $this->plankaClient->delete($apiKey, '/api/task-lists/' . $taskListId);
    }
}
