<?php

declare(strict_types=1);

namespace App\Tests\Infrastructure\Http;

use App\Infrastructure\Http\ApiKeyProvider;
use App\Shared\Exception\ValidationException;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

class ApiKeyProviderTest extends TestCase
{
    public function testGetApiKeyFromBearerToken(): void
    {
        $requestStack = new RequestStack();
        $request = Request::create('/', 'POST', [], [], [], ['HTTP_AUTHORIZATION' => 'Bearer mytoken123']);
        $requestStack->push($request);

        $provider = new ApiKeyProvider($requestStack);
        $this->assertSame('mytoken123', $provider->getApiKey());
    }

    public function testGetApiKeyFromXApiKeyHeader(): void
    {
        $requestStack = new RequestStack();
        $request = Request::create('/', 'POST', [], [], [], ['HTTP_X_API_KEY' => 'myapikey456']);
        $requestStack->push($request);

        $provider = new ApiKeyProvider($requestStack);
        $this->assertSame('myapikey456', $provider->getApiKey());
    }

    public function testBearerTakesPrecedenceOverXApiKey(): void
    {
        $requestStack = new RequestStack();
        $request = Request::create('/', 'POST', [], [], [], [
            'HTTP_AUTHORIZATION' => 'Bearer bearerkey',
            'HTTP_X_API_KEY' => 'xapikey',
        ]);
        $requestStack->push($request);

        $provider = new ApiKeyProvider($requestStack);
        $this->assertSame('bearerkey', $provider->getApiKey());
    }

    public function testNoHeadersThrowsValidationException(): void
    {
        $requestStack = new RequestStack();
        $request = Request::create('/');
        $requestStack->push($request);

        $provider = new ApiKeyProvider($requestStack);
        $this->expectException(ValidationException::class);
        $provider->getApiKey();
    }

    public function testNoRequestOnStackThrowsValidationException(): void
    {
        $requestStack = new RequestStack();
        $provider = new ApiKeyProvider($requestStack);

        $this->expectException(ValidationException::class);
        $provider->getApiKey();
    }
}
