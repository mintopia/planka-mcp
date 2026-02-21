<?php

declare(strict_types=1);

namespace App\Infrastructure\Http;

use App\Shared\Exception\ValidationException;
use Symfony\Component\HttpFoundation\RequestStack;

final class ApiKeyProvider
{
    public function __construct(
        private readonly RequestStack $requestStack,
    ) {}

    public function getApiKey(): string
    {
        $request = $this->requestStack->getCurrentRequest();

        if ($request === null) {
            throw new ValidationException('No active HTTP request.');
        }

        // Support: Authorization: Bearer <key>
        $auth = $request->headers->get('Authorization');
        if ($auth !== null && str_starts_with($auth, 'Bearer ')) {
            $key = substr($auth, 7);
            if ($key !== '') {
                return $key;
            }
        }

        // Support: X-Api-Key: <key>
        $key = $request->headers->get('X-Api-Key');
        if ($key !== null && $key !== '') {
            return $key;
        }

        throw new ValidationException(
            'Planka API key required. Send via "Authorization: Bearer <key>" or "X-Api-Key: <key>" header.',
        );
    }
}
