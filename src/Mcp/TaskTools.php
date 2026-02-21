<?php

declare(strict_types=1);

namespace App\Mcp;

use App\Domain\Task\TaskService;
use App\Infrastructure\Http\ApiKeyProvider;
use App\Shared\Exception\ValidationException;
use Mcp\Capability\Attribute\McpTool;
use Mcp\Capability\Attribute\Schema;
use Mcp\Exception\ToolCallException;

final class TaskTools
{
    public function __construct(
        private readonly TaskService $taskService,
        private readonly ApiKeyProvider $apiKeyProvider,
    ) {}

    /**
     * @param string[] $tasks
     * @return array<mixed>
     */
    #[McpTool(name: 'planka_create_tasks', description: 'Add one or more tasks (checklist items) to a card.')]
    public function createTasks(
        #[Schema(description: 'The card ID (from planka_get_board or planka_get_card)')] string $cardId,
        #[Schema(description: 'Task names to create', items: ['type' => 'string'])] array $tasks,
    ): array {
        try {
            if ($tasks === []) {
                throw new ValidationException('At least one task name is required.');
            }
            $apiKey = $this->apiKeyProvider->getApiKey();

            return $this->taskService->createTasks($apiKey, $cardId, $tasks);
        } catch (\Throwable $e) {
            throw new ToolCallException($e->getMessage(), $e->getCode(), $e);
        }
    }

    /** @return array<mixed> */
    #[McpTool(name: 'planka_update_task', description: "Update a task's name or completion status.")]
    public function updateTask(
        #[Schema(description: 'The task ID (from planka_get_card)')] string $taskId,
        #[Schema(description: 'New task name')] ?string $name = null,
        #[Schema(description: 'Mark as complete/incomplete')] ?bool $isCompleted = null,
    ): array {
        try {
            $apiKey = $this->apiKeyProvider->getApiKey();

            return $this->taskService->updateTask($apiKey, $taskId, $name, $isCompleted);
        } catch (\Throwable $e) {
            throw new ToolCallException($e->getMessage(), $e->getCode(), $e);
        }
    }

    /** @return array<mixed> */
    #[McpTool(name: 'planka_delete_task', description: 'Delete a task from a card.')]
    public function deleteTask(
        #[Schema(description: 'The task ID to delete (from planka_get_card)')] string $taskId,
    ): array {
        try {
            $apiKey = $this->apiKeyProvider->getApiKey();

            return $this->taskService->deleteTask($apiKey, $taskId);
        } catch (\Throwable $e) {
            throw new ToolCallException($e->getMessage(), $e->getCode(), $e);
        }
    }
}
