<?php

declare(strict_types=1);

namespace App\Mcp\Resources;

use App\Domain\BoardList\ListServiceInterface;
use App\Http\ApiKeyProviderInterface;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Attributes\MimeType;
use Laravel\Mcp\Server\Attributes\Name;
use Laravel\Mcp\Server\Contracts\HasUriTemplate;
use Laravel\Mcp\Server\Resource;
use Laravel\Mcp\Support\UriTemplate;

#[Name('planka-list')]
#[Description('A single Planka list with its position and board context')]
#[MimeType('application/json')]
final class ListResource extends Resource implements HasUriTemplate
{
    public function __construct(
        private readonly ListServiceInterface $listService,
        private readonly ApiKeyProviderInterface $apiKeyProvider,
    ) {}

    public function uriTemplate(): UriTemplate
    {
        return new UriTemplate('planka://lists/{listId}');
    }

    public function handle(Request $request): Response
    {
        try {
            $apiKey = $this->apiKeyProvider->getApiKey();
            $listId = $request->get('listId');
            return Response::json($this->listService->getList($apiKey, $listId));
        } catch (\Throwable $e) {
            return Response::error($e->getMessage());
        }
    }
}
