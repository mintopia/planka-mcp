<?php

declare(strict_types=1);

namespace App\Domain\Attachment;

interface AttachmentServiceInterface
{
    /** @return array<mixed> */
    public function uploadAttachment(string $apiKey, string $cardId, string $filePath, string $filename): array;

    /** @return array<mixed> */
    public function updateAttachment(string $apiKey, string $attachmentId, ?string $name = null, ?bool $isCover = null): array;

    /** @return array<mixed> */
    public function deleteAttachment(string $apiKey, string $attachmentId): array;
}
