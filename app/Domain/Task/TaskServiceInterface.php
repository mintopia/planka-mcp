<?php

declare(strict_types=1);

namespace App\Domain\Task;

interface TaskServiceInterface
{
    /**
     * @param string[] $tasks Task names to create
     * @return array<mixed>
     */
    public function createTasks(string $apiKey, string $cardId, array $tasks): array;

    /** @return array<mixed> */
    public function updateTask(
        string $apiKey,
        string $taskId,
        ?string $name = null,
        ?bool $isCompleted = null,
    ): array;

    /** @return array<mixed> */
    public function deleteTask(string $apiKey, string $taskId): array;

    /** @return array<mixed> */
    public function updateTaskList(string $apiKey, string $taskListId, ?string $name): array;

    /** @return array<mixed> */
    public function deleteTaskList(string $apiKey, string $taskListId): array;

    /** @return array<mixed> */
    public function getTaskList(string $apiKey, string $taskListId): array;
}
