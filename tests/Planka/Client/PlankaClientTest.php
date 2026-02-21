<?php

declare(strict_types=1);

namespace App\Tests\Planka\Client;

use App\Planka\Client\PlankaClient;
use App\Planka\Exception\AuthenticationException;
use App\Planka\Exception\PlankaApiException;
use App\Planka\Exception\PlankaNotFoundException;
use App\Shared\Exception\ValidationException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;

final class PlankaClientTest extends TestCase
{
    private const string PLANKA_URL = 'https://planka.example.com';
    private const string API_KEY = 'test-api-key-abc123';

    // -------------------------------------------------------------------------
    // get()
    // -------------------------------------------------------------------------

    public function testGetReturnsDecodedArray(): void
    {
        $payload = ['item' => ['id' => '42', 'name' => 'My Board']];
        $mockResponse = new MockResponse(
            (string) json_encode($payload),
            ['http_code' => 200],
        );
        $httpClient = new MockHttpClient($mockResponse, self::PLANKA_URL);
        $client = new PlankaClient($httpClient, self::PLANKA_URL);

        $result = $client->get(self::API_KEY, '/api/boards');

        $this->assertSame($payload, $result);
    }

    public function testGetPassesQueryParameters(): void
    {
        $capturedUrl = '';
        $httpClient = new MockHttpClient(
            function (string $method, string $url, array $options) use (&$capturedUrl): MockResponse {
                $capturedUrl = $url;

                return new MockResponse(
                    (string) json_encode(['items' => []]),
                    ['http_code' => 200],
                );
            },
            self::PLANKA_URL,
        );
        $client = new PlankaClient($httpClient, self::PLANKA_URL);

        $client->get(self::API_KEY, '/api/cards', ['page' => 1, 'limit' => 50]);

        $this->assertStringContainsString('page=1', $capturedUrl);
        $this->assertStringContainsString('limit=50', $capturedUrl);
    }

    // -------------------------------------------------------------------------
    // Header verification
    // -------------------------------------------------------------------------

    public function testRequestSetsXApiKeyHeader(): void
    {
        $capturedOptions = [];
        $httpClient = new MockHttpClient(
            function (string $method, string $url, array $options) use (&$capturedOptions): MockResponse {
                $capturedOptions = $options;

                return new MockResponse(
                    (string) json_encode(['item' => ['id' => '1']]),
                    ['http_code' => 200],
                );
            },
            self::PLANKA_URL,
        );
        $client = new PlankaClient($httpClient, self::PLANKA_URL);

        $client->get(self::API_KEY, '/api/boards');

        // MockHttpClient normalises headers: key is lowercase, value is "Header-Name: value"
        $this->assertArrayHasKey('x-api-key', $capturedOptions['normalized_headers']);
        $this->assertSame(
            'X-Api-Key: ' . self::API_KEY,
            $capturedOptions['normalized_headers']['x-api-key'][0],
        );
    }

    public function testApiKeyIsNotStoredAsInstanceState(): void
    {
        $receivedKeys = [];
        $httpClient = new MockHttpClient(
            function (string $method, string $url, array $options) use (&$receivedKeys): MockResponse {
                $receivedKeys[] = $options['normalized_headers']['x-api-key'][0] ?? '';

                return new MockResponse(
                    (string) json_encode(['ok' => true]),
                    ['http_code' => 200],
                );
            },
            self::PLANKA_URL,
        );
        $client = new PlankaClient($httpClient, self::PLANKA_URL);

        $client->get('first-key', '/api/boards');
        $client->get('second-key', '/api/boards');

        $this->assertSame('X-Api-Key: first-key', $receivedKeys[0]);
        $this->assertSame('X-Api-Key: second-key', $receivedKeys[1]);
    }

    // -------------------------------------------------------------------------
    // post()
    // -------------------------------------------------------------------------

    public function testPostSendsBodyAsJson(): void
    {
        $capturedMethod = '';
        $capturedOptions = [];
        $httpClient = new MockHttpClient(
            function (string $method, string $url, array $options) use (&$capturedMethod, &$capturedOptions): MockResponse {
                $capturedMethod = $method;
                $capturedOptions = $options;

                return new MockResponse(
                    (string) json_encode(['item' => ['id' => '99']]),
                    ['http_code' => 201],
                );
            },
            self::PLANKA_URL,
        );
        $client = new PlankaClient($httpClient, self::PLANKA_URL);

        $body = ['name' => 'New Board', 'projectId' => 'proj-1'];
        $client->post(self::API_KEY, '/api/boards', $body);

        // MockHttpClient serialises 'json' option into the request body string.
        $this->assertSame(json_encode($body), $capturedOptions['body']);
        $this->assertSame('POST', $capturedMethod);
    }

    public function testPostReturnsDecodedArray(): void
    {
        $payload = ['item' => ['id' => '99', 'name' => 'New Board']];
        $mockResponse = new MockResponse(
            (string) json_encode($payload),
            ['http_code' => 201],
        );
        $httpClient = new MockHttpClient($mockResponse, self::PLANKA_URL);
        $client = new PlankaClient($httpClient, self::PLANKA_URL);

        $result = $client->post(self::API_KEY, '/api/boards', ['name' => 'New Board', 'projectId' => 'proj-1']);

        $this->assertSame($payload, $result);
    }

    // -------------------------------------------------------------------------
    // patch()
    // -------------------------------------------------------------------------

    public function testPatchSendsBodyAsJsonWithPatchMethod(): void
    {
        $capturedMethod = '';
        $capturedOptions = [];
        $httpClient = new MockHttpClient(
            function (string $method, string $url, array $options) use (&$capturedMethod, &$capturedOptions): MockResponse {
                $capturedMethod = $method;
                $capturedOptions = $options;

                return new MockResponse(
                    (string) json_encode(['item' => ['id' => '42']]),
                    ['http_code' => 200],
                );
            },
            self::PLANKA_URL,
        );
        $client = new PlankaClient($httpClient, self::PLANKA_URL);

        $body = ['name' => 'Updated Board'];
        $client->patch(self::API_KEY, '/api/boards/42', $body);

        $this->assertSame(json_encode($body), $capturedOptions['body']);
        $this->assertSame('PATCH', $capturedMethod);
    }

    public function testPatchReturnsDecodedArray(): void
    {
        $payload = ['item' => ['id' => '42', 'name' => 'Updated Board']];
        $mockResponse = new MockResponse(
            (string) json_encode($payload),
            ['http_code' => 200],
        );
        $httpClient = new MockHttpClient($mockResponse, self::PLANKA_URL);
        $client = new PlankaClient($httpClient, self::PLANKA_URL);

        $result = $client->patch(self::API_KEY, '/api/boards/42', ['name' => 'Updated Board']);

        $this->assertSame($payload, $result);
    }

    // -------------------------------------------------------------------------
    // delete()
    // -------------------------------------------------------------------------

    public function testDeleteReturnsEmptyArrayOn204(): void
    {
        $mockResponse = new MockResponse('', ['http_code' => 204]);
        $httpClient = new MockHttpClient($mockResponse, self::PLANKA_URL);
        $client = new PlankaClient($httpClient, self::PLANKA_URL);

        $result = $client->delete(self::API_KEY, '/api/boards/42');

        $this->assertSame([], $result);
    }

    public function testDeleteUsesDeleteMethod(): void
    {
        $capturedMethod = '';
        $httpClient = new MockHttpClient(
            function (string $method, string $url, array $options) use (&$capturedMethod): MockResponse {
                $capturedMethod = $method;

                return new MockResponse('', ['http_code' => 204]);
            },
            self::PLANKA_URL,
        );
        $client = new PlankaClient($httpClient, self::PLANKA_URL);

        $client->delete(self::API_KEY, '/api/boards/42');

        $this->assertSame('DELETE', $capturedMethod);
    }

    // -------------------------------------------------------------------------
    // 401 → AuthenticationException
    // -------------------------------------------------------------------------

    public function testUnauthorizedResponseThrowsAuthenticationException(): void
    {
        $mockResponse = new MockResponse(
            (string) json_encode(['error' => 'Unauthorized']),
            ['http_code' => 401],
        );
        $httpClient = new MockHttpClient($mockResponse, self::PLANKA_URL);
        $client = new PlankaClient($httpClient, self::PLANKA_URL);

        $this->expectException(AuthenticationException::class);
        $this->expectExceptionMessage('Invalid or missing Planka API key.');

        $client->get(self::API_KEY, '/api/boards');
    }

    public function testAuthenticationExceptionCarriesStatusCode(): void
    {
        $mockResponse = new MockResponse('', ['http_code' => 401]);
        $httpClient = new MockHttpClient($mockResponse, self::PLANKA_URL);
        $client = new PlankaClient($httpClient, self::PLANKA_URL);

        try {
            $client->get(self::API_KEY, '/api/boards');
            $this->fail('Expected AuthenticationException was not thrown.');
        } catch (AuthenticationException $e) {
            $this->assertSame(401, $e->getCode());
        }
    }

    // -------------------------------------------------------------------------
    // 404 → PlankaNotFoundException
    // -------------------------------------------------------------------------

    public function testNotFoundResponseThrowsPlankaNotFoundException(): void
    {
        $mockResponse = new MockResponse(
            (string) json_encode(['error' => 'Not Found']),
            ['http_code' => 404],
        );
        $httpClient = new MockHttpClient($mockResponse, self::PLANKA_URL);
        $client = new PlankaClient($httpClient, self::PLANKA_URL);

        $this->expectException(PlankaNotFoundException::class);
        $this->expectExceptionMessage('Planka resource not found: /api/boards/999');

        $client->get(self::API_KEY, '/api/boards/999');
    }

    public function testPlankaNotFoundExceptionIsPlankaApiException(): void
    {
        $mockResponse = new MockResponse('', ['http_code' => 404]);
        $httpClient = new MockHttpClient($mockResponse, self::PLANKA_URL);
        $client = new PlankaClient($httpClient, self::PLANKA_URL);

        $this->expectException(PlankaApiException::class);

        $client->get(self::API_KEY, '/api/boards/999');
    }

    public function testNotFoundExceptionCarriesStatusCode(): void
    {
        $mockResponse = new MockResponse('', ['http_code' => 404]);
        $httpClient = new MockHttpClient($mockResponse, self::PLANKA_URL);
        $client = new PlankaClient($httpClient, self::PLANKA_URL);

        try {
            $client->get(self::API_KEY, '/api/boards/999');
            $this->fail('Expected PlankaNotFoundException was not thrown.');
        } catch (PlankaNotFoundException $e) {
            $this->assertSame(404, $e->getCode());
        }
    }

    // -------------------------------------------------------------------------
    // 5xx → PlankaApiException  (@dataProvider)
    // -------------------------------------------------------------------------

    /**
     * @return array<string, array{int}>
     */
    public static function serverErrorStatusCodeProvider(): array
    {
        return [
            '500 Internal Server Error' => [500],
            '502 Bad Gateway'           => [502],
            '503 Service Unavailable'   => [503],
            '504 Gateway Timeout'       => [504],
        ];
    }

    #[DataProvider('serverErrorStatusCodeProvider')]
    public function testServerErrorResponseThrowsPlankaApiException(int $statusCode): void
    {
        $mockResponse = new MockResponse(
            (string) json_encode(['error' => 'Server error']),
            ['http_code' => $statusCode],
        );
        $httpClient = new MockHttpClient($mockResponse, self::PLANKA_URL);
        $client = new PlankaClient($httpClient, self::PLANKA_URL);

        $this->expectException(PlankaApiException::class);
        $this->expectExceptionMessage(
            sprintf('Planka API returned server error %d for /api/boards', $statusCode),
        );

        $client->get(self::API_KEY, '/api/boards');
    }

    #[DataProvider('serverErrorStatusCodeProvider')]
    public function testServerErrorExceptionCarriesStatusCode(int $statusCode): void
    {
        $mockResponse = new MockResponse('', ['http_code' => $statusCode]);
        $httpClient = new MockHttpClient($mockResponse, self::PLANKA_URL);
        $client = new PlankaClient($httpClient, self::PLANKA_URL);

        try {
            $client->get(self::API_KEY, '/api/boards');
            $this->fail('Expected PlankaApiException was not thrown.');
        } catch (PlankaApiException $e) {
            $this->assertSame($statusCode, $e->getCode());
        }
    }

    // -------------------------------------------------------------------------
    // Network failure → PlankaApiException
    // -------------------------------------------------------------------------

    public function testNetworkFailureThrowsPlankaApiException(): void
    {
        $mockResponse = new MockResponse('', ['error' => 'Connection refused']);
        $httpClient = new MockHttpClient($mockResponse, self::PLANKA_URL);
        $client = new PlankaClient($httpClient, self::PLANKA_URL);

        $this->expectException(PlankaApiException::class);
        $this->expectExceptionMessage('Unable to connect to Planka API.');

        $client->get(self::API_KEY, '/api/boards');
    }

    public function testNetworkFailureExceptionHasPreviousTransportException(): void
    {
        $mockResponse = new MockResponse('', ['error' => 'Connection refused']);
        $httpClient = new MockHttpClient($mockResponse, self::PLANKA_URL);
        $client = new PlankaClient($httpClient, self::PLANKA_URL);

        try {
            $client->get(self::API_KEY, '/api/boards');
            $this->fail('Expected PlankaApiException was not thrown.');
        } catch (PlankaApiException $e) {
            $this->assertNotNull($e->getPrevious());
        }
    }

    // -------------------------------------------------------------------------
    // URL construction
    // -------------------------------------------------------------------------

    public function testUrlIsBuiltCorrectlyWithTrailingSlashOnBase(): void
    {
        $capturedUrl = '';
        $httpClient = new MockHttpClient(
            function (string $method, string $url, array $options) use (&$capturedUrl): MockResponse {
                $capturedUrl = $url;

                return new MockResponse(
                    (string) json_encode(['items' => []]),
                    ['http_code' => 200],
                );
            },
            self::PLANKA_URL . '/',
        );
        // Pass a URL with trailing slash — client must strip it before appending path.
        $client = new PlankaClient($httpClient, self::PLANKA_URL . '/');

        $client->get(self::API_KEY, '/api/boards');

        $this->assertStringNotContainsString('//api', $capturedUrl);
        $this->assertStringEndsWith('/api/boards', $capturedUrl);
    }

    // -------------------------------------------------------------------------
    // 4xx client errors → PlankaApiException  (@dataProvider)
    // -------------------------------------------------------------------------

    /**
     * @return array<string, array{int}>
     */
    public static function clientErrorStatusCodeProvider(): array
    {
        return [
            '400 Bad Request'           => [400],
            '403 Forbidden'             => [403],
            '409 Conflict'              => [409],
            '422 Unprocessable Entity'  => [422],
            '429 Too Many Requests'     => [429],
        ];
    }

    #[DataProvider('clientErrorStatusCodeProvider')]
    public function testClientErrorResponseThrowsPlankaApiException(int $statusCode): void
    {
        $mockResponse = new MockResponse(
            (string) json_encode(['error' => 'Client error']),
            ['http_code' => $statusCode],
        );
        $httpClient = new MockHttpClient($mockResponse, self::PLANKA_URL);
        $client = new PlankaClient($httpClient, self::PLANKA_URL);

        $this->expectException(PlankaApiException::class);
        $this->expectExceptionMessage(sprintf('Planka API client error %d', $statusCode));

        $client->get(self::API_KEY, '/api/boards');
    }

    #[DataProvider('clientErrorStatusCodeProvider')]
    public function testClientErrorExceptionCarriesStatusCode(int $statusCode): void
    {
        $mockResponse = new MockResponse('', ['http_code' => $statusCode]);
        $httpClient = new MockHttpClient($mockResponse, self::PLANKA_URL);
        $client = new PlankaClient($httpClient, self::PLANKA_URL);

        try {
            $client->get(self::API_KEY, '/api/boards');
            $this->fail('Expected PlankaApiException was not thrown.');
        } catch (PlankaApiException $e) {
            $this->assertSame($statusCode, $e->getCode());
        }
    }

    // -------------------------------------------------------------------------
    // Path traversal protection
    // -------------------------------------------------------------------------

    public function testPathTraversalThrowsValidationException(): void
    {
        $httpClient = new MockHttpClient([], self::PLANKA_URL);
        $client = new PlankaClient($httpClient, self::PLANKA_URL);

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('Invalid resource path.');

        $client->get(self::API_KEY, '/api/cards/../../admin');
    }

    public function testNullByteInPathThrowsValidationException(): void
    {
        $httpClient = new MockHttpClient([], self::PLANKA_URL);
        $client = new PlankaClient($httpClient, self::PLANKA_URL);

        $this->expectException(ValidationException::class);

        $client->get(self::API_KEY, "/api/cards/card\0id");
    }

    // -------------------------------------------------------------------------
    // AuthenticationException is a subtype of PlankaApiException
    // -------------------------------------------------------------------------

    public function testAuthenticationExceptionIsPlankaApiException(): void
    {
        $mockResponse = new MockResponse('', ['http_code' => 401]);
        $httpClient = new MockHttpClient($mockResponse, self::PLANKA_URL);
        $client = new PlankaClient($httpClient, self::PLANKA_URL);

        $this->expectException(PlankaApiException::class);

        $client->get(self::API_KEY, '/api/boards');
    }
}
