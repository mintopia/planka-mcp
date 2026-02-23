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

#[Name('planka_delete_task')]
#[Description('Delete a task from a card.')]
final class DeleteTaskTool extends Tool
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
                ->description('The task ID to delete (from planka_get_card)'),
        ];
    }

    public function handle(Request $request): Response
    {
        try {
            $taskId = (string) $request->get('taskId', '');
            $apiKey = $this->apiKeyProvider->getApiKey();

            return Response::json($this->taskService->deleteTask($apiKey, $taskId));
        } catch (\Throwable $e) {
            return Response::error($e->getMessage());
        }
    }
}
