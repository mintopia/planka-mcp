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

#[Name('planka_update_task')]
#[Description("Update a task's name or completion status.")]
final class UpdateTaskTool extends Tool
{
    public function __construct(
        private readonly TaskServiceInterface $taskService,
        private readonly ApiKeyProviderInterface $apiKeyProvider,
    ) {}

    public function schema(JsonSchema $schema): array
    {
        return [
            'taskId' => $schema->string()
                ->required()
                ->description('The task ID (from planka_get_card)'),
            'name' => $schema->string()
                ->nullable()
                ->description('New task name'),
            'isCompleted' => $schema->boolean()
                ->nullable()
                ->description('Mark as complete/incomplete'),
        ];
    }

    public function handle(Request $request): Response
    {
        try {
            $taskId = (string) $request->get('taskId', '');
            /** @var ?string $name */
            $name = $request->get('name');
            /** @var ?bool $isCompleted */
            $isCompleted = $request->get('isCompleted');
            $apiKey = $this->apiKeyProvider->getApiKey();

            return Response::json($this->taskService->updateTask($apiKey, $taskId, $name, $isCompleted));
        } catch (\Throwable $e) {
            return Response::error($e->getMessage());
        }
    }
}
