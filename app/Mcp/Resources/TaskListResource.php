<?php

declare(strict_types=1);

namespace App\Mcp\Resources;

use App\Domain\Task\TaskServiceInterface;
use App\Http\ApiKeyProviderInterface;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Attributes\MimeType;
use Laravel\Mcp\Server\Attributes\Name;
use Laravel\Mcp\Server\Contracts\HasUriTemplate;
use Laravel\Mcp\Server\Resource;
use Laravel\Mcp\Support\UriTemplate;

#[Name('planka-task-list')]
#[Description('A Planka task list with all its tasks and completion status')]
#[MimeType('application/json')]
final class TaskListResource extends Resource implements HasUriTemplate
{
    public function __construct(
        private readonly TaskServiceInterface $taskService,
        private readonly ApiKeyProviderInterface $apiKeyProvider,
    ) {}

    public function uriTemplate(): UriTemplate
    {
        return new UriTemplate('planka://task-lists/{taskListId}');
    }

    public function handle(Request $request): Response
    {
        try {
            $apiKey = $this->apiKeyProvider->getApiKey();
            $taskListId = $request->get('taskListId');
            return Response::json($this->taskService->getTaskList($apiKey, $taskListId));
        } catch (\Throwable $e) {
            return Response::error($e->getMessage());
        }
    }
}
