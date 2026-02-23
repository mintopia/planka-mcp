<?php

declare(strict_types=1);

namespace App\Domain\Project;

interface ProjectServiceInterface
{
    /** @return array<mixed> */
    public function getStructure(string $apiKey): array;

    /** @return array<mixed> */
    public function createProject(string $apiKey, string $name, string $type = 'shared'): array;

    /** @return array<mixed> */
    public function getProject(string $apiKey, string $projectId): array;

    /** @return array<mixed> */
    public function updateProject(string $apiKey, string $projectId, ?string $name, ?string $description = null): array;

    /** @return array<mixed> */
    public function deleteProject(string $apiKey, string $projectId): array;

    /** @return array<mixed> */
    public function addProjectManager(string $apiKey, string $projectId, string $userId): array;

    /** @return array<mixed> */
    public function removeProjectManager(string $apiKey, string $projectManagerId): array;
}
