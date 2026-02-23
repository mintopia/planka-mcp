<?php

declare(strict_types=1);

namespace App\Domain\Comment;

interface CommentServiceInterface
{
    /** @return array<mixed> */
    public function addComment(string $apiKey, string $cardId, string $text): array;

    /** @return array<mixed> */
    public function getComments(string $apiKey, string $cardId): array;

    /** @return array<mixed> */
    public function updateComment(string $apiKey, string $commentId, string $text): array;

    /** @return array<mixed> */
    public function deleteComment(string $apiKey, string $commentId): array;
}
