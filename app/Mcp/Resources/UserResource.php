<?php

declare(strict_types=1);

namespace App\Mcp\Resources;

use App\Domain\User\UserServiceInterface;
use App\Http\ApiKeyProviderInterface;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Attributes\MimeType;
use Laravel\Mcp\Server\Attributes\Name;
use Laravel\Mcp\Server\Contracts\HasUriTemplate;
use Laravel\Mcp\Server\Resource;
use Laravel\Mcp\Support\UriTemplate;

#[Name('planka-user')]
#[Description('A single Planka user profile with roles and metadata')]
#[MimeType('application/json')]
final class UserResource extends Resource implements HasUriTemplate
{
    public function __construct(
        private readonly UserServiceInterface $userService,
        private readonly ApiKeyProviderInterface $apiKeyProvider,
    ) {}

    public function uriTemplate(): UriTemplate
    {
        return new UriTemplate('planka://users/{userId}');
    }

    public function handle(Request $request): Response
    {
        try {
            $apiKey = $this->apiKeyProvider->getApiKey();
            $userId = $request->get('userId');
            return Response::json($this->userService->getUser($apiKey, $userId));
        } catch (\Throwable $e) {
            return Response::error($e->getMessage());
        }
    }
}
