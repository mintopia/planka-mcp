<?php

declare(strict_types=1);

namespace App\Mcp\Resources;

use App\Domain\Action\ActionServiceInterface;
use App\Http\ApiKeyProviderInterface;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Attributes\MimeType;
use Laravel\Mcp\Server\Attributes\Name;
use Laravel\Mcp\Server\Contracts\HasUriTemplate;
use Laravel\Mcp\Server\Resource;
use Laravel\Mcp\Support\UriTemplate;

#[Name('planka-card-actions')]
#[Description('Activity log and action history for a specific Planka card')]
#[MimeType('application/json')]
final class CardActionsResource extends Resource implements HasUriTemplate
{
    public function __construct(
        private readonly ActionServiceInterface $actionService,
        private readonly ApiKeyProviderInterface $apiKeyProvider,
    ) {}

    public function uriTemplate(): UriTemplate
    {
        return new UriTemplate('planka://cards/{cardId}/actions');
    }

    public function handle(Request $request): Response
    {
        try {
            $apiKey = $this->apiKeyProvider->getApiKey();
            $cardId = $request->get('cardId');
            return Response::json($this->actionService->getCardActions($apiKey, $cardId));
        } catch (\Throwable $e) {
            return Response::error($e->getMessage());
        }
    }
}
