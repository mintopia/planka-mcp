<?php

declare(strict_types=1);

namespace App\Mcp\Tools;

use App\Domain\Task\TaskServiceInterface;
use App\Http\ApiKeyProviderInterface;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Attributes\Name;
use Laravel\Mcp\Server\Tool;

#[Name('planka_update_task_list')]
#[Description("Update a task list's name.")]
final class UpdateTaskListTool extends Tool
{
    public function __construct(
        private readonly TaskServiceInterface $taskService,
        private readonly ApiKeyProviderInterface $apiKeyProvider,
    ) {}

    public function schema(JsonSchema $schema): array
    {
        return [
            'taskListId' => $schema->string()
                ->required()
                ->description('The task list ID to update'),
            'name' => $schema->string()
                ->nullable()
                ->description('New task list name'),
        ];
    }

    public function handle(Request $request): Response
    {
        try {
            $taskListId = (string) $request->get('taskListId', '');
            /** @var ?string $name */
            $name = $request->get('name');
            $apiKey = $this->apiKeyProvider->getApiKey();

            return Response::json($this->taskService->updateTaskList($apiKey, $taskListId, $name));
        } catch (\Throwable $e) {
            return Response::error($e->getMessage());
        }
    }
}
