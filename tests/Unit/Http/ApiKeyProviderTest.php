<?php

declare(strict_types=1);

namespace Tests\Unit\Http;

use App\Exception\ValidationException;
use App\Http\ApiKeyProvider;
use Illuminate\Http\Request;
use PHPUnit\Framework\TestCase;

final class ApiKeyProviderTest extends TestCase
{
    public function testGetApiKeyFromBearerToken(): void
    {
        $request = Request::create('/', 'POST', [], [], [], ['HTTP_AUTHORIZATION' => 'Bearer mytoken123']);
        $provider = new ApiKeyProvider($request);

        $this->assertSame('mytoken123', $provider->getApiKey());
    }

    public function testGetApiKeyFromXApiKeyHeader(): void
    {
        $request = Request::create('/', 'POST', [], [], [], ['HTTP_X_API_KEY' => 'myapikey456']);
        $provider = new ApiKeyProvider($request);

        $this->assertSame('myapikey456', $provider->getApiKey());
    }

    public function testBearerTakesPrecedenceOverXApiKey(): void
    {
        $request = Request::create('/', 'POST', [], [], [], [
            'HTTP_AUTHORIZATION' => 'Bearer bearerkey',
            'HTTP_X_API_KEY' => 'xapikey',
        ]);
        $provider = new ApiKeyProvider($request);

        $this->assertSame('bearerkey', $provider->getApiKey());
    }

    public function testNoHeadersThrowsValidationException(): void
    {
        $request = Request::create('/');
        $provider = new ApiKeyProvider($request);

        $this->expectException(ValidationException::class);

        $provider->getApiKey();
    }

    public function testEmptyBearerTokenFallsBackToXApiKey(): void
    {
        $request = Request::create('/', 'POST', [], [], [], [
            'HTTP_AUTHORIZATION' => 'Bearer ',
            'HTTP_X_API_KEY' => 'fallback-key',
        ]);
        $provider = new ApiKeyProvider($request);

        $this->assertSame('fallback-key', $provider->getApiKey());
    }

    public function testEmptyBearerTokenNoXApiKeyThrowsValidationException(): void
    {
        $request = Request::create('/', 'POST', [], [], [], [
            'HTTP_AUTHORIZATION' => 'Bearer ',
        ]);
        $provider = new ApiKeyProvider($request);

        $this->expectException(ValidationException::class);

        $provider->getApiKey();
    }
}
