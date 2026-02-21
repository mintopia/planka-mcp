<?php

declare(strict_types=1);

namespace App\Domain\Attachment;

use App\Planka\Client\PlankaClientInterface;

final class AttachmentService
{
    public function __construct(
        private readonly PlankaClientInterface $plankaClient,
    ) {}

    /** @return array<mixed> */
    public function uploadAttachment(string $apiKey, string $cardId, string $filePath, string $filename): array
    {
        return $this->plankaClient->postMultipart($apiKey, '/api/cards/' . $cardId . '/attachments', [], $filePath, $filename);
    }

    /** @return array<mixed> */
    public function updateAttachment(string $apiKey, string $attachmentId, ?string $name = null, ?bool $isCover = null): array
    {
        $body = [];
        if ($name !== null) {
            $body['name'] = $name;
        }
        if ($isCover !== null) {
            $body['isCover'] = $isCover;
        }

        return $this->plankaClient->patch($apiKey, '/api/attachments/' . $attachmentId, $body);
    }

    /** @return array<mixed> */
    public function deleteAttachment(string $apiKey, string $attachmentId): array
    {
        return $this->plankaClient->delete($apiKey, '/api/attachments/' . $attachmentId);
    }
}
