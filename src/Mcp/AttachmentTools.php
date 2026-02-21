<?php

declare(strict_types=1);

namespace App\Mcp;

use App\Domain\Attachment\AttachmentService;
use App\Infrastructure\Http\ApiKeyProvider;
use App\Shared\Exception\ValidationException;
use Mcp\Capability\Attribute\McpTool;
use Mcp\Capability\Attribute\Schema;
use Mcp\Exception\ToolCallException;

final class AttachmentTools
{
    public function __construct(
        private readonly AttachmentService $attachmentService,
        private readonly ApiKeyProvider $apiKeyProvider,
        private readonly string $uploadDir,
    ) {}

    /** @return array<mixed> */
    #[McpTool(name: 'planka_upload_attachment', description: 'Upload a file attachment to a card. Files must be placed in the configured upload directory first.')]
    public function uploadAttachment(
        #[Schema(description: 'The card ID to attach the file to (from planka_get_board or planka_get_card)')] string $cardId,
        #[Schema(description: 'Absolute path to the file on the MCP server filesystem')] string $filePath,
        #[Schema(description: 'Filename to use for the attachment (e.g. report.pdf)')] string $filename,
    ): array {
        try {
            if (trim($filePath) === '') {
                throw new ValidationException('filePath cannot be empty.');
            }
            if (trim($filename) === '') {
                throw new ValidationException('filename cannot be empty.');
            }

            $realPath = realpath($filePath);
            if ($realPath === false) {
                throw new ValidationException('File not found or inaccessible: ' . $filePath);
            }
            $allowedBase = rtrim($this->uploadDir, '/\\') . \DIRECTORY_SEPARATOR;
            if (!str_starts_with($realPath . \DIRECTORY_SEPARATOR, $allowedBase)) {
                throw new ValidationException('File path must be within the configured upload directory.');
            }

            $apiKey = $this->apiKeyProvider->getApiKey();

            return $this->attachmentService->uploadAttachment($apiKey, $cardId, $filePath, $filename);
        } catch (\Throwable $e) {
            throw new ToolCallException($e->getMessage(), $e->getCode(), $e);
        }
    }

    /** @return array<mixed> */
    #[McpTool(name: 'planka_manage_attachments', description: 'Update or delete an attachment on a card.')]
    public function manageAttachments(
        #[Schema(description: 'Action to perform: update or delete', enum: ['update', 'delete'])] string $action,
        #[Schema(description: 'Attachment ID (required for update and delete)')] ?string $attachmentId = null,
        #[Schema(description: 'New name for the attachment (for update)')] ?string $name = null,
        #[Schema(description: 'Set as card cover image (for update)')] ?bool $isCover = null,
    ): array {
        try {
            $apiKey = $this->apiKeyProvider->getApiKey();

            return match ($action) {
                'update' => $this->attachmentService->updateAttachment(
                    $apiKey,
                    $attachmentId ?? throw new ValidationException('attachmentId required for update'),
                    $name,
                    $isCover,
                ),
                'delete' => $this->attachmentService->deleteAttachment(
                    $apiKey,
                    $attachmentId ?? throw new ValidationException('attachmentId required for delete'),
                ),
                default => throw new ValidationException(sprintf('Invalid action "%s". Must be: update, delete', $action)),
            };
        } catch (\Throwable $e) {
            throw new ToolCallException($e->getMessage(), $e->getCode(), $e);
        }
    }
}
