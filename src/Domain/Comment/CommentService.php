<?php

declare(strict_types=1);

namespace App\Domain\Comment;

use App\Planka\Client\PlankaClient;

final class CommentService
{
    public function __construct(
        private readonly PlankaClient $plankaClient,
    ) {}

    /** @return array<mixed> */
    public function addComment(string $apiKey, string $cardId, string $text): array
    {
        return $this->plankaClient->post($apiKey, '/api/cards/' . $cardId . '/comment-actions', ['text' => $text]);
    }

    /** @return array<mixed> */
    public function getComments(string $apiKey, string $cardId): array
    {
        return $this->plankaClient->get($apiKey, '/api/cards/' . $cardId . '/actions');
    }
}
