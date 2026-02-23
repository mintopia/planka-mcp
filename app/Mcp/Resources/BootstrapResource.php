<?php

declare(strict_types=1);

namespace App\Mcp\Resources;

use App\Domain\System\SystemServiceInterface;
use App\Http\ApiKeyProviderInterface;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Attributes\MimeType;
use Laravel\Mcp\Server\Attributes\Name;
use Laravel\Mcp\Server\Attributes\Uri;
use Laravel\Mcp\Server\Resource;

#[Name('planka-bootstrap')]
#[Description('Planka bootstrap data including current user session and server info')]
#[Uri('planka://bootstrap')]
#[MimeType('application/json')]
final class BootstrapResource extends Resource
{
    public function __construct(
        private readonly SystemServiceInterface $systemService,
        private readonly ApiKeyProviderInterface $apiKeyProvider,
    ) {}

    public function handle(Request $request): Response
    {
        try {
            $apiKey = $this->apiKeyProvider->getApiKey();
            return Response::json($this->systemService->getBootstrap($apiKey));
        } catch (\Throwable $e) {
            return Response::error($e->getMessage());
        }
    }
}
