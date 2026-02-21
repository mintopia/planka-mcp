<?php

declare(strict_types=1);

namespace App\Domain\Comment;

use App\Planka\Client\PlankaClientInterface;

final class CommentService
{
    public function __construct(
        private readonly PlankaClientInterface $plankaClient,
    ) {}

    /** @return array<mixed> */
    public function addComment(string $apiKey, string $cardId, string $text): array
    {
        return $this->plankaClient->post($apiKey, '/api/cards/' . $cardId . '/comments', ['text' => $text]);
    }

    /** @return array<mixed> */
    public function getComments(string $apiKey, string $cardId): array
    {
        return $this->plankaClient->get($apiKey, '/api/cards/' . $cardId . '/comments');
    }

    /** @return array<mixed> */
    public function updateComment(string $apiKey, string $commentId, string $text): array
    {
        return $this->plankaClient->patch($apiKey, '/api/comments/' . $commentId, ['text' => $text]);
    }

    /** @return array<mixed> */
    public function deleteComment(string $apiKey, string $commentId): array
    {
        return $this->plankaClient->delete($apiKey, '/api/comments/' . $commentId);
    }
}
