<?php

declare(strict_types=1);

namespace App\Planka;

interface PlankaClientInterface
{
    /**
     * @param array<string, mixed> $query
     * @return array<mixed>
     */
    public function get(string $apiKey, string $path, array $query = []): array;

    /**
     * @param array<string, mixed> $body
     * @return array<mixed>
     */
    public function post(string $apiKey, string $path, array $body = []): array;

    /**
     * @param array<string, mixed> $body
     * @return array<mixed>
     */
    public function patch(string $apiKey, string $path, array $body = []): array;

    /**
     * @return array<mixed>
     */
    public function delete(string $apiKey, string $path): array;

    /**
     * @param array<string, mixed> $fields
     * @return array<mixed>
     */
    public function postMultipart(string $apiKey, string $path, array $fields, string $filePath, string $filename): array;
}
