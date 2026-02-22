<?php

declare(strict_types=1);

namespace App\Mcp;

use App\Domain\Project\ProjectService;
use App\Infrastructure\Http\ApiKeyProvider;
use App\Shared\Exception\ValidationException;
use Mcp\Capability\Attribute\McpTool;
use Mcp\Capability\Attribute\Schema;
use Mcp\Exception\ToolCallException;

final class ProjectTools
{
    public function __construct(
        private readonly ProjectService $projectService,
        private readonly ApiKeyProvider $apiKeyProvider,
    ) {}

    /** @return array<mixed> */
    #[McpTool(name: 'planka_manage_projects', description: 'Create, get, update, or delete a Planka project.')]
    public function manageProjects(
        #[Schema(description: 'Action to perform: create, get, update, or delete', enum: ['create', 'get', 'update', 'delete'])] string $action,
        #[Schema(description: 'Project ID (required for get, update, delete) (from planka_get_structure)')] ?string $projectId = null,
        #[Schema(description: 'Project name (required for create, optional for update)')] ?string $name = null,
        #[Schema(description: 'Project description (optional for update)')] ?string $description = null,
        #[Schema(description: 'Project type (required for create)', enum: ['private', 'shared'])] ?string $type = null,
    ): array {
        try {
            $apiKey = $this->apiKeyProvider->getApiKey();

            return match ($action) {
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
        } catch (\Throwable $e) {
            throw new ToolCallException($e->getMessage(), $e->getCode(), $e);
        }
    }

    /** @return array<mixed> */
    #[McpTool(name: 'planka_manage_project_managers', description: 'Add or remove a project manager from a Planka project.')]
    public function manageProjectManagers(
        #[Schema(description: 'Action to perform: add or remove', enum: ['add', 'remove'])] string $action,
        #[Schema(description: 'Project ID (from planka_get_structure)')] string $projectId,
        #[Schema(description: 'User ID to add or remove as project manager')] ?string $userId = null,
        #[Schema(description: 'Project manager record ID to remove (from planka_manage_projects or planka_get_structure)')] ?string $projectManagerId = null,
    ): array {
        try {
            $apiKey = $this->apiKeyProvider->getApiKey();

            return match ($action) {
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
        } catch (\Throwable $e) {
            throw new ToolCallException($e->getMessage(), $e->getCode(), $e);
        }
    }
}
