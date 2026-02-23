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
use Laravel\Mcp\Server\Contracts\HasUriTemplate;
use Laravel\Mcp\Server\Resource;
use Laravel\Mcp\Support\UriTemplate;

#[Name('planka-project')]
#[Description('A single Planka project with its boards and metadata')]
#[MimeType('application/json')]
final class ProjectResource extends Resource implements HasUriTemplate
{
    public function __construct(
        private readonly ProjectServiceInterface $projectService,
        private readonly ApiKeyProviderInterface $apiKeyProvider,
    ) {}

    public function uriTemplate(): UriTemplate
    {
        return new UriTemplate('planka://projects/{projectId}');
    }

    public function handle(Request $request): Response
    {
        try {
            $apiKey = $this->apiKeyProvider->getApiKey();
            $projectId = $request->get('projectId');
            return Response::json($this->projectService->getProject($apiKey, $projectId));
        } catch (\Throwable $e) {
            return Response::error($e->getMessage());
        }
    }
}
