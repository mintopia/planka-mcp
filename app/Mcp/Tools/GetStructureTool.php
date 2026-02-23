<?php

declare(strict_types=1);

namespace App\Mcp\Tools;

use App\Domain\Project\ProjectServiceInterface;
use App\Http\ApiKeyProviderInterface;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Attributes\Name;
use Laravel\Mcp\Server\Tool;

#[Name('planka_get_structure')]
#[Description('Get all Planka projects with their boards and lists')]
final class GetStructureTool extends Tool
{
    public function __construct(
        private readonly ProjectServiceInterface $projectService,
        private readonly ApiKeyProviderInterface $apiKeyProvider,
    ) {}

    public function handle(Request $request): Response
    {
        try {
            $apiKey = $this->apiKeyProvider->getApiKey();

            return Response::json($this->projectService->getStructure($apiKey));
        } catch (\Throwable $e) {
            return Response::error($e->getMessage());
        }
    }
}
