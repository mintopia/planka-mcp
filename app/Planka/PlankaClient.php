<?php

declare(strict_types=1);

namespace App\Planka;

use App\Exception\ValidationException;
use App\Planka\Exception\AuthenticationException;
use App\Planka\Exception\PlankaApiException;
use App\Planka\Exception\PlankaNotFoundException;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;

final class PlankaClient implements PlankaClientInterface
{
    public function __construct(private readonly string $plankaUrl) {}

    /**
     * @param array<string, mixed> $query
     * @return array<mixed>
     */
    public function get(string $apiKey, string $path, array $query = []): array
    {
        return $this->request($apiKey, 'GET', $path, [], $query);
    }

    /**
     * @param array<string, mixed> $body
     * @return array<mixed>
     */
    public function post(string $apiKey, string $path, array $body = []): array
    {
        return $this->request($apiKey, 'POST', $path, $body);
    }

    /**
     * @param array<string, mixed> $body
     * @return array<mixed>
     */
    public function patch(string $apiKey, string $path, array $body = []): array
    {
        return $this->request($apiKey, 'PATCH', $path, $body);
    }

    /**
     * @return array<mixed>
     */
    public function delete(string $apiKey, string $path): array
    {
        return $this->request($apiKey, 'DELETE', $path);
    }

    /**
     * @param array<string, mixed> $fields
     * @return array<mixed>
     */
    public function postMultipart(string $apiKey, string $path, array $fields, string $filePath, string $filename): array
    {
        if (str_contains($path, '..') || str_contains($path, "\0")) {
            throw new ValidationException('Invalid resource path.');
        }

        if (str_contains($filePath, "\0")) {
            throw new ValidationException('Invalid file path.');
        }

        $headers = [
            'X-Api-Key' => $apiKey,
            'Accept' => 'application/json',
        ];

        $url = rtrim($this->plankaUrl, '/') . $path;

        try {
            $contents = file_get_contents($filePath);
            // @codeCoverageIgnoreStart
            if ($contents === false) {
                throw new ValidationException('Unable to read file: ' . $filePath);
            }
            // @codeCoverageIgnoreEnd

            $response = Http::withHeaders($headers)
                ->attach('file', $contents, $filename)
                ->post($url, $fields);

            return $this->handleResponse($response, $path);
        } catch (ConnectionException $e) {
            throw new PlankaApiException(
                'Unable to connect to Planka API.',
                0,
                $e,
            );
        }
    }

    /**
     * @param array<string, mixed> $body
     * @param array<string, mixed> $query
     * @return array<mixed>
     */
    private function request(string $apiKey, string $method, string $path, array $body = [], array $query = []): array
    {
        if (str_contains($path, '..') || str_contains($path, "\0")) {
            throw new ValidationException('Invalid resource path.');
        }

        $headers = [
            'X-Api-Key' => $apiKey,
            'Accept' => 'application/json',
        ];

        $url = rtrim($this->plankaUrl, '/') . $path;

        try {
            $pendingRequest = Http::withHeaders($headers);

            $response = match ($method) {
                'GET' => $pendingRequest->get($url, $query !== [] ? $query : null),
                'POST' => $body !== [] ? $pendingRequest->post($url, $body) : $pendingRequest->post($url),
                'PATCH' => $body !== [] ? $pendingRequest->patch($url, $body) : $pendingRequest->patch($url),
                'DELETE' => $pendingRequest->delete($url),
                // @codeCoverageIgnoreStart
                default => throw new PlankaApiException(sprintf('Unsupported HTTP method: %s', $method)),
                // @codeCoverageIgnoreEnd
            };

            return $this->handleResponse($response, $path);
        } catch (ConnectionException $e) {
            throw new PlankaApiException(
                'Unable to connect to Planka API.',
                0,
                $e,
            );
        }
    }

    /**
     * @return array<mixed>
     */
    private function handleResponse(\Illuminate\Http\Client\Response $response, string $path): array
    {
        $statusCode = $response->status();

        if ($statusCode === 204) {
            return [];
        }

        if ($statusCode === 401) {
            throw new AuthenticationException('Invalid or missing Planka API key.', $statusCode);
        }

        if ($statusCode === 404) {
            throw new PlankaNotFoundException(
                sprintf('Planka resource not found: %s', $path),
                $statusCode,
            );
        }

        if ($statusCode >= 400 && $statusCode < 500) {
            throw new PlankaApiException(
                sprintf('Planka API client error %d', $statusCode),
                $statusCode,
            );
        }

        if ($statusCode >= 500) {
            throw new PlankaApiException(
                sprintf('Planka API returned server error %d for %s', $statusCode, $path),
                $statusCode,
            );
        }

        /** @var array<mixed> $data */
        $data = $response->json() ?? [];

        return $data;
    }
}
