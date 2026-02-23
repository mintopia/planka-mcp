<?php

declare(strict_types=1);

namespace App\Mcp\Tools;

use App\Domain\Task\TaskServiceInterface;
use App\Exception\ValidationException;
use App\Http\ApiKeyProviderInterface;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Attributes\Name;
use Laravel\Mcp\Server\Tool;

#[Name('planka_create_tasks')]
#[Description('Add one or more tasks (checklist items) to a card.')]
final class CreateTasksTool extends Tool
{
    public function __construct(
        private readonly TaskServiceInterface $taskService,
        private readonly ApiKeyProviderInterface $apiKeyProvider,
    ) {}

    public function schema(JsonSchema $schema): array
    {
        return [
            'cardId' => $schema->string()
                ->required()
                ->description('The card ID (from planka_get_board or planka_get_card)'),
            'tasks' => $schema->array()
                ->required()
                ->description('Task names to create')
                ->items($schema->string()),
        ];
    }

    public function handle(Request $request): Response
    {
        try {
            $cardId = (string) $request->get('cardId', '');
            /** @var array<string> $tasks */
            $tasks = $request->get('tasks', []);

            if ($tasks === []) {
                throw new ValidationException('At least one task name is required.');
            }

            $apiKey = $this->apiKeyProvider->getApiKey();

            return Response::json($this->taskService->createTasks($apiKey, $cardId, $tasks));
        } catch (\Throwable $e) {
            return Response::error($e->getMessage());
        }
    }
}
