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

#[Name('planka_manage_projects')]
#[Description('Create, get, update, or delete a Planka project.')]
final class ManageProjectsTool extends Tool
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
                ->description('Action to perform: create, get, update, or delete')
                ->enum(['create', 'get', 'update', 'delete']),
            'projectId' => $schema->string()
                ->nullable()
                ->description('Project ID (required for get, update, delete) (from planka_get_structure)'),
            'name' => $schema->string()
                ->nullable()
                ->description('Project name (required for create, optional for update)'),
            'description' => $schema->string()
                ->nullable()
                ->description('Project description (optional for update)'),
            'type' => $schema->string()
                ->nullable()
                ->description('Project type (required for create)')
                ->enum(['private', 'shared']),
        ];
    }

    public function handle(Request $request): Response
    {
        try {
            $action = (string) $request->get('action', '');
            /** @var ?string $projectId */
            $projectId = $request->get('projectId');
            /** @var ?string $name */
            $name = $request->get('name');
            /** @var ?string $description */
            $description = $request->get('description');
            /** @var ?string $type */
            $type = $request->get('type');
            $apiKey = $this->apiKeyProvider->getApiKey();

            $result = match ($action) {
                'create' => $this->projectService->createProject(
                    $apiKey,
                    $name ?? throw new ValidationException('name required for create'),
                    $type ?? 'shared',
                ),
                'get' => $this->projectService->getProject(
                    $apiKey,
                    $projectId ?? throw new ValidationException('projectId required for get'),
                ),
                'update' => $this->projectService->updateProject(
                    $apiKey,
                    $projectId ?? throw new ValidationException('projectId required for update'),
                    $name,
                    $description,
                ),
                'delete' => $this->projectService->deleteProject(
                    $apiKey,
                    $projectId ?? throw new ValidationException('projectId required for delete'),
                ),
                default => throw new ValidationException(sprintf('Invalid action "%s". Must be: create, get, update, delete', $action)),
            };

            return Response::json($result);
        } catch (\Throwable $e) {
            return Response::error($e->getMessage());
        }
    }
}
