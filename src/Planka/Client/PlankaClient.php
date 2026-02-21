<?php

declare(strict_types=1);

namespace App\Planka\Client;

use App\Planka\Exception\AuthenticationException;
use App\Planka\Exception\PlankaApiException;
use App\Planka\Exception\PlankaNotFoundException;
use App\Shared\Exception\ValidationException;
use Symfony\Component\Mime\Part\DataPart;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

final class PlankaClient implements PlankaClientInterface
{
    public function __construct(
        private readonly HttpClientInterface $httpClient,
        private readonly string $plankaUrl,
    ) {}

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

        try {
            $body = $fields;
            $body['file'] = DataPart::fromPath($filePath, $filename);

            $response = $this->httpClient->request(
                'POST',
                rtrim($this->plankaUrl, '/') . $path,
                [
                    'headers' => [
                        'X-Api-Key' => $apiKey,
                        'Accept' => 'application/json',
                    ],
                    'body' => $body,
                ],
            );

            $statusCode = $response->getStatusCode();

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
            $data = $response->toArray();

            return $data;
        } catch (TransportExceptionInterface $e) {
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
        $options = [
            'headers' => [
                'X-Api-Key' => $apiKey,
                'Accept' => 'application/json',
            ],
        ];

        if ($body !== []) {
            $options['json'] = $body;
        }

        if ($query !== []) {
            $options['query'] = $query;
        }

        if (str_contains($path, '..') || str_contains($path, "\0")) {
            throw new ValidationException('Invalid resource path.');
        }

        try {
            $response = $this->httpClient->request(
                $method,
                rtrim($this->plankaUrl, '/') . $path,
                $options,
            );

            $statusCode = $response->getStatusCode();

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
            $data = $response->toArray();

            return $data;
        } catch (TransportExceptionInterface $e) {
            throw new PlankaApiException(
                'Unable to connect to Planka API.',
                0,
                $e,
            );
        }
    }
}
