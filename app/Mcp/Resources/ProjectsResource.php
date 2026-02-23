<?php

declare(strict_types=1);

namespace App\Mcp\Resources;

use App\Domain\Project\ProjectServiceInterface;
use App\Http\ApiKeyProviderInterface;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Attributes\MimeType;
use Laravel\Mcp\Server\Attributes\Name;
use Laravel\Mcp\Server\Attributes\Uri;
use Laravel\Mcp\Server\Resource;

#[Name('planka-projects')]
#[Description('All Planka projects with boards and structure')]
#[Uri('planka://projects')]
#[MimeType('application/json')]
final class ProjectsResource extends Resource
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
