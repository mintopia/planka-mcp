<?php

declare(strict_types=1);

namespace App\Http;

use App\Exception\ValidationException;
use Illuminate\Http\Request;

final class ApiKeyProvider implements ApiKeyProviderInterface
{
    public function __construct(private readonly Request $request) {}

    public function getApiKey(): string
    {
        // Support: Authorization: Bearer <key>
        $auth = $this->request->header('Authorization');
        if ($auth !== null && str_starts_with($auth, 'Bearer ')) {
            $key = substr($auth, 7);
            if ($key !== '') {
                return $key;
            }
        }

        // Support: X-Api-Key: <key>
        $key = $this->request->header('X-Api-Key');
        if ($key !== null && $key !== '') {
            return $key;
        }

        throw new ValidationException(
            'Planka API key required. Send via "Authorization: Bearer <key>" or "X-Api-Key: <key>" header.',
        );
    }
}
