<?php

declare(strict_types=1);

namespace App\Mcp\Tools;

use App\Domain\Project\ProjectServiceInterface;
use App\Exception\ValidationException;
use App\Http\ApiKeyProviderInterface;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Attributes\Name;
use Laravel\Mcp\Server\Tool;

#[Name('planka_manage_project_managers')]
#[Description('Add or remove a project manager from a Planka project.')]
final class ManageProjectManagersTool extends Tool
{
    public function __construct(
        private readonly ProjectServiceInterface $projectService,
        private readonly ApiKeyProviderInterface $apiKeyProvider,
    ) {}

    public function schema(JsonSchema $schema): array
    {
        return [
            'action' => $schema->string()
                ->required()
                ->description('Action to perform: add or remove')
                ->enum(['add', 'remove']),
            'projectId' => $schema->string()
                ->required()
                ->description('Project ID (from planka_get_structure)'),
            'userId' => $schema->string()
                ->nullable()
                ->description('User ID to add or remove as project manager'),
            'projectManagerId' => $schema->string()
                ->nullable()
                ->description('Project manager record ID to remove (from planka_manage_projects or planka_get_structure)'),
        ];
    }

    public function handle(Request $request): Response
    {
        try {
            $action = (string) $request->get('action', '');
            $projectId = (string) $request->get('projectId', '');
            /** @var ?string $userId */
            $userId = $request->get('userId');
            /** @var ?string $projectManagerId */
            $projectManagerId = $request->get('projectManagerId');
            $apiKey = $this->apiKeyProvider->getApiKey();

            $result = match ($action) {
                'add' => $this->projectService->addProjectManager(
                    $apiKey,
                    $projectId,
                    $userId ?? throw new ValidationException('userId required for add'),
                ),
                'remove' => $this->projectService->removeProjectManager(
                    $apiKey,
                    $projectManagerId ?? throw new ValidationException('projectManagerId required for remove'),
                ),
                default => throw new ValidationException(sprintf('Invalid action "%s". Must be: add, remove', $action)),
            };

            return Response::json($result);
        } catch (\Throwable $e) {
            return Response::error($e->getMessage());
        }
    }
}
