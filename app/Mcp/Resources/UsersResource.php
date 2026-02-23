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
use Laravel\Mcp\Server\Attributes\Uri;
use Laravel\Mcp\Server\Resource;

#[Name('planka-users')]
#[Description('All Planka users registered in the system')]
#[Uri('planka://users')]
#[MimeType('application/json')]
final class UsersResource extends Resource
{
    public function __construct(
        private readonly UserServiceInterface $userService,
        private readonly ApiKeyProviderInterface $apiKeyProvider,
    ) {}

    public function handle(Request $request): Response
    {
        try {
            $apiKey = $this->apiKeyProvider->getApiKey();
            return Response::json($this->userService->getUsers($apiKey));
        } catch (\Throwable $e) {
            return Response::error($e->getMessage());
        }
    }
}
