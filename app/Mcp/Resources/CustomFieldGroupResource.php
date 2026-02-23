<?php

declare(strict_types=1);

namespace App\Mcp\Resources;

use App\Domain\CustomField\CustomFieldServiceInterface;
use App\Http\ApiKeyProviderInterface;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Attributes\MimeType;
use Laravel\Mcp\Server\Attributes\Name;
use Laravel\Mcp\Server\Contracts\HasUriTemplate;
use Laravel\Mcp\Server\Resource;
use Laravel\Mcp\Support\UriTemplate;

#[Name('planka-custom-field-group')]
#[Description('A Planka custom field group with its field definitions')]
#[MimeType('application/json')]
final class CustomFieldGroupResource extends Resource implements HasUriTemplate
{
    public function __construct(
        private readonly CustomFieldServiceInterface $customFieldService,
        private readonly ApiKeyProviderInterface $apiKeyProvider,
    ) {}

    public function uriTemplate(): UriTemplate
    {
        return new UriTemplate('planka://custom-field-groups/{groupId}');
    }

    public function handle(Request $request): Response
    {
        try {
            $apiKey = $this->apiKeyProvider->getApiKey();
            $groupId = $request->get('groupId');
            return Response::json($this->customFieldService->getGroup($apiKey, $groupId));
        } catch (\Throwable $e) {
            return Response::error($e->getMessage());
        }
    }
}
