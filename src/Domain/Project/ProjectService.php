<?php

declare(strict_types=1);

namespace App\Domain\Project;

use App\Planka\Client\PlankaClientInterface;

final class ProjectService
{
    public function __construct(
        private readonly PlankaClientInterface $plankaClient,
    ) {}

    /** @return array<mixed> */
    public function getStructure(string $apiKey): array
    {
        return $this->plankaClient->get($apiKey, '/api/projects');
    }

    /** @return array<mixed> */
    public function createProject(string $apiKey, string $name, string $type = 'shared'): array
    {
        return $this->plankaClient->post($apiKey, '/api/projects', ['name' => $name, 'type' => $type]);
    }

    /** @return array<mixed> */
    public function getProject(string $apiKey, string $projectId): array
    {
        return $this->plankaClient->get($apiKey, '/api/projects/' . $projectId);
    }

    /** @return array<mixed> */
    public function updateProject(string $apiKey, string $projectId, ?string $name, ?string $description = null): array
    {
        $body = [];
        if ($name !== null) {
            $body['name'] = $name;
        }
        if ($description !== null) {
            $body['description'] = $description;
        }

        return $this->plankaClient->patch($apiKey, '/api/projects/' . $projectId, $body);
    }

    /** @return array<mixed> */
    public function deleteProject(string $apiKey, string $projectId): array
    {
        return $this->plankaClient->delete($apiKey, '/api/projects/' . $projectId);
    }

    /** @return array<mixed> */
    public function addProjectManager(string $apiKey, string $projectId, string $userId): array
    {
        return $this->plankaClient->post($apiKey, '/api/projects/' . $projectId . '/project-managers', ['userId' => $userId]);
    }

    /** @return array<mixed> */
    public function removeProjectManager(string $apiKey, string $projectManagerId): array
    {
        return $this->plankaClient->delete($apiKey, '/api/project-managers/' . $projectManagerId);
    }
}
