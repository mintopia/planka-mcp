<?php

declare(strict_types=1);

namespace Tests\Unit\Planka;

use App\Exception\ValidationException;
use App\Planka\Exception\AuthenticationException;
use App\Planka\Exception\PlankaApiException;
use App\Planka\Exception\PlankaNotFoundException;
use App\Planka\PlankaClient;
use Illuminate\Support\Facades\Http;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

final class PlankaClientTest extends TestCase
{
    private const string PLANKA_URL = 'http://planka.test';
    private const string API_KEY = 'test-api-key-abc123';

    // -------------------------------------------------------------------------
    // get()
    // -------------------------------------------------------------------------

    public function testGetReturnsDecodedArray(): void
    {
        $payload = ['item' => ['id' => '42', 'name' => 'My Board']];

        Http::fake([
            'planka.test/*' => Http::response($payload, 200),
        ]);

        $client = new PlankaClient(self::PLANKA_URL);
        $result = $client->get(self::API_KEY, '/api/boards');

        $this->assertSame($payload, $result);
    }

    public function testGetPassesQueryParameters(): void
    {
        Http::fake([
            'planka.test/*' => Http::response(['items' => []], 200),
        ]);

        $client = new PlankaClient(self::PLANKA_URL);
        $client->get(self::API_KEY, '/api/cards', ['page' => 1, 'limit' => 50]);

        Http::assertSent(function ($request) {
            return str_contains($request->url(), 'page=1')
                && str_contains($request->url(), 'limit=50');
        });
    }

    // -------------------------------------------------------------------------
    // Header verification
    // -------------------------------------------------------------------------

    public function testRequestSetsXApiKeyHeader(): void
    {
        Http::fake([
            'planka.test/*' => Http::response(['item' => ['id' => '1']], 200),
        ]);

        $client = new PlankaClient(self::PLANKA_URL);
        $client->get(self::API_KEY, '/api/boards');

        Http::assertSent(function ($request) {
            return $request->hasHeader('X-Api-Key', self::API_KEY);
        });
    }

    public function testApiKeyIsNotStoredAsInstanceState(): void
    {
        Http::fake([
            'planka.test/*' => Http::response(['ok' => true], 200),
        ]);

        $client = new PlankaClient(self::PLANKA_URL);
        $client->get('first-key', '/api/boards');
        $client->get('second-key', '/api/boards');

        $requests = Http::recorded();
        $this->assertCount(2, $requests);
        $this->assertSame('first-key', $requests[0][0]->header('X-Api-Key')[0]);
        $this->assertSame('second-key', $requests[1][0]->header('X-Api-Key')[0]);
    }

    // -------------------------------------------------------------------------
    // post()
    // -------------------------------------------------------------------------

    public function testPostSendsBodyAsJson(): void
    {
        Http::fake([
            'planka.test/*' => Http::response(['item' => ['id' => '99']], 201),
        ]);

        $client = new PlankaClient(self::PLANKA_URL);
        $body = ['name' => 'New Board', 'projectId' => 'proj-1'];
        $client->post(self::API_KEY, '/api/boards', $body);

        Http::assertSent(function ($request) use ($body) {
            return $request->method() === 'POST'
                && $request['name'] === 'New Board'
                && $request['projectId'] === 'proj-1';
        });
    }

    public function testPostReturnsDecodedArray(): void
    {
        $payload = ['item' => ['id' => '99', 'name' => 'New Board']];

        Http::fake([
            'planka.test/*' => Http::response($payload, 201),
        ]);

        $client = new PlankaClient(self::PLANKA_URL);
        $result = $client->post(self::API_KEY, '/api/boards', ['name' => 'New Board', 'projectId' => 'proj-1']);

        $this->assertSame($payload, $result);
    }

    // -------------------------------------------------------------------------
    // patch()
    // -------------------------------------------------------------------------

    public function testPatchSendsBodyAsJsonWithPatchMethod(): void
    {
        Http::fake([
            'planka.test/*' => Http::response(['item' => ['id' => '42']], 200),
        ]);

        $client = new PlankaClient(self::PLANKA_URL);
        $body = ['name' => 'Updated Board'];
        $client->patch(self::API_KEY, '/api/boards/42', $body);

        Http::assertSent(function ($request) {
            return $request->method() === 'PATCH'
                && $request['name'] === 'Updated Board';
        });
    }

    public function testPatchReturnsDecodedArray(): void
    {
        $payload = ['item' => ['id' => '42', 'name' => 'Updated Board']];

        Http::fake([
            'planka.test/*' => Http::response($payload, 200),
        ]);

        $client = new PlankaClient(self::PLANKA_URL);
        $result = $client->patch(self::API_KEY, '/api/boards/42', ['name' => 'Updated Board']);

        $this->assertSame($payload, $result);
    }

    // -------------------------------------------------------------------------
    // delete()
    // -------------------------------------------------------------------------

    public function testDeleteReturnsEmptyArrayOn204(): void
    {
        Http::fake([
            'planka.test/*' => Http::response('', 204),
        ]);

        $client = new PlankaClient(self::PLANKA_URL);
        $result = $client->delete(self::API_KEY, '/api/boards/42');

        $this->assertSame([], $result);
    }

    public function testDeleteUsesDeleteMethod(): void
    {
        Http::fake([
            'planka.test/*' => Http::response('', 204),
        ]);

        $client = new PlankaClient(self::PLANKA_URL);
        $client->delete(self::API_KEY, '/api/boards/42');

        Http::assertSent(function ($request) {
            return $request->method() === 'DELETE';
        });
    }

    // -------------------------------------------------------------------------
    // 401 -> AuthenticationException
    // -------------------------------------------------------------------------

    public function testUnauthorizedResponseThrowsAuthenticationException(): void
    {
        Http::fake([
            'planka.test/*' => Http::response(['error' => 'Unauthorized'], 401),
        ]);

        $client = new PlankaClient(self::PLANKA_URL);

        $this->expectException(AuthenticationException::class);
        $this->expectExceptionMessage('Invalid or missing Planka API key.');

        $client->get(self::API_KEY, '/api/boards');
    }

    public function testAuthenticationExceptionCarriesStatusCode(): void
    {
        Http::fake([
            'planka.test/*' => Http::response('', 401),
        ]);

        $client = new PlankaClient(self::PLANKA_URL);

        try {
            $client->get(self::API_KEY, '/api/boards');
            $this->fail('Expected AuthenticationException was not thrown.');
        } catch (AuthenticationException $e) {
            $this->assertSame(401, $e->getCode());
        }
    }

    // -------------------------------------------------------------------------
    // 404 -> PlankaNotFoundException
    // -------------------------------------------------------------------------

    public function testNotFoundResponseThrowsPlankaNotFoundException(): void
    {
        Http::fake([
            'planka.test/*' => Http::response(['error' => 'Not Found'], 404),
        ]);

        $client = new PlankaClient(self::PLANKA_URL);

        $this->expectException(PlankaNotFoundException::class);
        $this->expectExceptionMessage('Planka resource not found: /api/boards/999');

        $client->get(self::API_KEY, '/api/boards/999');
    }

    public function testPlankaNotFoundExceptionIsPlankaApiException(): void
    {
        Http::fake([
            'planka.test/*' => Http::response('', 404),
        ]);

        $client = new PlankaClient(self::PLANKA_URL);

        $this->expectException(PlankaApiException::class);

        $client->get(self::API_KEY, '/api/boards/999');
    }

    public function testNotFoundExceptionCarriesStatusCode(): void
    {
        Http::fake([
            'planka.test/*' => Http::response('', 404),
        ]);

        $client = new PlankaClient(self::PLANKA_URL);

        try {
            $client->get(self::API_KEY, '/api/boards/999');
            $this->fail('Expected PlankaNotFoundException was not thrown.');
        } catch (PlankaNotFoundException $e) {
            $this->assertSame(404, $e->getCode());
        }
    }

    // -------------------------------------------------------------------------
    // 5xx -> PlankaApiException (@dataProvider)
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
        Http::fake([
            'planka.test/*' => Http::response(['error' => 'Server error'], $statusCode),
        ]);

        $client = new PlankaClient(self::PLANKA_URL);

        $this->expectException(PlankaApiException::class);
        $this->expectExceptionMessage(
            sprintf('Planka API returned server error %d for /api/boards', $statusCode),
        );

        $client->get(self::API_KEY, '/api/boards');
    }

    #[DataProvider('serverErrorStatusCodeProvider')]
    public function testServerErrorExceptionCarriesStatusCode(int $statusCode): void
    {
        Http::fake([
            'planka.test/*' => Http::response('', $statusCode),
        ]);

        $client = new PlankaClient(self::PLANKA_URL);

        try {
            $client->get(self::API_KEY, '/api/boards');
            $this->fail('Expected PlankaApiException was not thrown.');
        } catch (PlankaApiException $e) {
            $this->assertSame($statusCode, $e->getCode());
        }
    }

    // -------------------------------------------------------------------------
    // Network failure -> PlankaApiException
    // -------------------------------------------------------------------------

    public function testNetworkFailureThrowsPlankaApiException(): void
    {
        Http::fake([
            'planka.test/*' => fn () => throw new \Illuminate\Http\Client\ConnectionException('Connection refused'),
        ]);

        $client = new PlankaClient(self::PLANKA_URL);

        $this->expectException(PlankaApiException::class);
        $this->expectExceptionMessage('Unable to connect to Planka API.');

        $client->get(self::API_KEY, '/api/boards');
    }

    public function testNetworkFailureExceptionHasPreviousConnectionException(): void
    {
        Http::fake([
            'planka.test/*' => fn () => throw new \Illuminate\Http\Client\ConnectionException('Connection refused'),
        ]);

        $client = new PlankaClient(self::PLANKA_URL);

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
        Http::fake([
            'planka.test/*' => Http::response(['items' => []], 200),
        ]);

        $client = new PlankaClient(self::PLANKA_URL . '/');
        $client->get(self::API_KEY, '/api/boards');

        Http::assertSent(function ($request) {
            $url = $request->url();

            return ! str_contains($url, '//api')
                && str_ends_with(parse_url($url, PHP_URL_PATH), '/api/boards');
        });
    }

    // -------------------------------------------------------------------------
    // 4xx client errors -> PlankaApiException (@dataProvider)
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
        Http::fake([
            'planka.test/*' => Http::response(['error' => 'Client error'], $statusCode),
        ]);

        $client = new PlankaClient(self::PLANKA_URL);

        $this->expectException(PlankaApiException::class);
        $this->expectExceptionMessage(sprintf('Planka API client error %d', $statusCode));

        $client->get(self::API_KEY, '/api/boards');
    }

    #[DataProvider('clientErrorStatusCodeProvider')]
    public function testClientErrorExceptionCarriesStatusCode(int $statusCode): void
    {
        Http::fake([
            'planka.test/*' => Http::response('', $statusCode),
        ]);

        $client = new PlankaClient(self::PLANKA_URL);

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
        $client = new PlankaClient(self::PLANKA_URL);

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('Invalid resource path.');

        $client->get(self::API_KEY, '/api/cards/../../admin');
    }

    public function testNullByteInPathThrowsValidationException(): void
    {
        $client = new PlankaClient(self::PLANKA_URL);

        $this->expectException(ValidationException::class);

        $client->get(self::API_KEY, "/api/cards/card\0id");
    }

    // -------------------------------------------------------------------------
    // AuthenticationException is a subtype of PlankaApiException
    // -------------------------------------------------------------------------

    public function testAuthenticationExceptionIsPlankaApiException(): void
    {
        Http::fake([
            'planka.test/*' => Http::response('', 401),
        ]);

        $client = new PlankaClient(self::PLANKA_URL);

        $this->expectException(PlankaApiException::class);

        $client->get(self::API_KEY, '/api/boards');
    }

    // -------------------------------------------------------------------------
    // postMultipart() -- validation paths
    // -------------------------------------------------------------------------

    public function testPostMultipartThrowsValidationExceptionOnPathTraversal(): void
    {
        $client = new PlankaClient(self::PLANKA_URL);

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('Invalid resource path.');

        $client->postMultipart(self::API_KEY, '/api/cards/../../admin/attachments', [], '/tmp/file.pdf', 'file.pdf');
    }

    public function testPostMultipartThrowsValidationExceptionOnNullByteInPath(): void
    {
        $client = new PlankaClient(self::PLANKA_URL);

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('Invalid resource path.');

        $client->postMultipart(self::API_KEY, "/api/cards/card\0id/attachments", [], '/tmp/file.pdf', 'file.pdf');
    }

    public function testPostMultipartThrowsValidationExceptionOnNullByteInFilePath(): void
    {
        $client = new PlankaClient(self::PLANKA_URL);

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('Invalid file path.');

        $client->postMultipart(self::API_KEY, '/api/cards/card1/attachments', [], "/tmp/fi\0le.pdf", 'file.pdf');
    }

    // -------------------------------------------------------------------------
    // postMultipart() -- HTTP response paths
    // -------------------------------------------------------------------------

    public function testPostMultipartThrowsAuthenticationExceptionOn401(): void
    {
        Http::fake([
            'planka.test/*' => Http::response('', 401),
        ]);

        $client = new PlankaClient(self::PLANKA_URL);

        $this->expectException(AuthenticationException::class);
        $this->expectExceptionMessage('Invalid or missing Planka API key.');

        $client->postMultipart(self::API_KEY, '/api/cards/card1/attachments', [], __FILE__, 'file.pdf');
    }

    public function testPostMultipartThrowsPlankaNotFoundExceptionOn404(): void
    {
        Http::fake([
            'planka.test/*' => Http::response('', 404),
        ]);

        $client = new PlankaClient(self::PLANKA_URL);

        $this->expectException(PlankaNotFoundException::class);
        $this->expectExceptionMessage('Planka resource not found: /api/cards/card1/attachments');

        $client->postMultipart(self::API_KEY, '/api/cards/card1/attachments', [], __FILE__, 'file.pdf');
    }

    public function testPostMultipartThrowsPlankaApiExceptionOn4xx(): void
    {
        Http::fake([
            'planka.test/*' => Http::response('', 422),
        ]);

        $client = new PlankaClient(self::PLANKA_URL);

        $this->expectException(PlankaApiException::class);
        $this->expectExceptionMessage('Planka API client error 422');

        $client->postMultipart(self::API_KEY, '/api/cards/card1/attachments', [], __FILE__, 'file.pdf');
    }

    public function testPostMultipartThrowsPlankaApiExceptionOn5xx(): void
    {
        Http::fake([
            'planka.test/*' => Http::response('', 500),
        ]);

        $client = new PlankaClient(self::PLANKA_URL);

        $this->expectException(PlankaApiException::class);
        $this->expectExceptionMessage('Planka API returned server error 500 for /api/cards/card1/attachments');

        $client->postMultipart(self::API_KEY, '/api/cards/card1/attachments', [], __FILE__, 'file.pdf');
    }

    public function testPostMultipartThrowsPlankaApiExceptionOnNetworkFailure(): void
    {
        Http::fake([
            'planka.test/*' => fn () => throw new \Illuminate\Http\Client\ConnectionException('Connection refused'),
        ]);

        $client = new PlankaClient(self::PLANKA_URL);

        $this->expectException(PlankaApiException::class);
        $this->expectExceptionMessage('Unable to connect to Planka API.');

        $client->postMultipart(self::API_KEY, '/api/cards/card1/attachments', [], __FILE__, 'file.pdf');
    }

    public function testPostMultipartSetsXApiKeyHeader(): void
    {
        Http::fake([
            'planka.test/*' => Http::response(['item' => ['id' => 'att1']], 200),
        ]);

        $client = new PlankaClient(self::PLANKA_URL);
        $client->postMultipart(self::API_KEY, '/api/cards/card1/attachments', [], __FILE__, 'file.pdf');

        Http::assertSent(function ($request) {
            return $request->hasHeader('X-Api-Key', self::API_KEY);
        });
    }

}
