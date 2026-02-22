<?php

declare(strict_types=1);

namespace App\Mcp;

use App\Domain\CustomField\CustomFieldService;
use App\Infrastructure\Http\ApiKeyProvider;
use App\Shared\Exception\ValidationException;
use Mcp\Capability\Attribute\McpTool;
use Mcp\Capability\Attribute\Schema;
use Mcp\Exception\ToolCallException;

final class CustomFieldTools
{
    public function __construct(
        private readonly CustomFieldService $customFieldService,
        private readonly ApiKeyProvider $apiKeyProvider,
    ) {}

    /** @return array<mixed> */
    #[McpTool(name: 'planka_manage_custom_field_groups', description: 'Create, get, update, or delete custom field groups on projects (base groups), boards, or cards.')]
    public function manageCustomFieldGroups(
        #[Schema(description: 'Action to perform', enum: ['create_base', 'update_base', 'delete_base', 'create', 'get', 'update', 'delete'])] string $action,
        #[Schema(description: 'Project ID — required for create_base')] ?string $projectId = null,
        #[Schema(description: 'Base custom field group ID — required for update_base and delete_base')] ?string $baseGroupId = null,
        #[Schema(description: 'Required for create: whether the group belongs to a board or a card', enum: ['board', 'card'])] ?string $parentType = null,
        #[Schema(description: 'Board ID or card ID — required for create')] ?string $parentId = null,
        #[Schema(description: 'Custom field group ID — required for get, update, delete')] ?string $groupId = null,
        #[Schema(description: 'Group name')] ?string $name = null,
    ): array {
        try {
            $apiKey = $this->apiKeyProvider->getApiKey();

            return match ($action) {
                'create_base' => $this->customFieldService->createBaseGroup($apiKey, $projectId ?? throw new ValidationException('projectId required for create_base'), $name),
                'update_base' => $this->customFieldService->updateBaseGroup($apiKey, $baseGroupId ?? throw new ValidationException('baseGroupId required for update_base'), $name),
                'delete_base' => $this->customFieldService->deleteBaseGroup($apiKey, $baseGroupId ?? throw new ValidationException('baseGroupId required for delete_base')),
                'create' => $this->customFieldService->createGroup($apiKey, $parentType ?? throw new ValidationException('parentType required for create'), $parentId ?? throw new ValidationException('parentId required for create'), $name),
                'get' => $this->customFieldService->getGroup($apiKey, $groupId ?? throw new ValidationException('groupId required for get')),
                'update' => $this->customFieldService->updateGroup($apiKey, $groupId ?? throw new ValidationException('groupId required for update'), $name),
                'delete' => $this->customFieldService->deleteGroup($apiKey, $groupId ?? throw new ValidationException('groupId required for delete')),
                default => throw new ValidationException(sprintf('Invalid action "%s". Must be: create_base, update_base, delete_base, create, get, update, delete', $action)),
            };
        } catch (\Throwable $e) {
            throw new ToolCallException($e->getMessage(), $e->getCode(), $e);
        }
    }

    /** @return array<mixed> */
    #[McpTool(name: 'planka_manage_custom_fields', description: 'Create, update, or delete custom fields within a custom field group.')]
    public function manageCustomFields(
        #[Schema(description: 'Action to perform', enum: ['create', 'update', 'delete'])] string $action,
        #[Schema(description: 'Required for create: whether the parent is a base custom field group or a regular custom field group', enum: ['base', 'group'])] ?string $groupType = null,
        #[Schema(description: 'Group ID — required for create')] ?string $groupId = null,
        #[Schema(description: 'Custom field ID — required for update and delete')] ?string $fieldId = null,
        #[Schema(description: 'Field name')] ?string $name = null,
        #[Schema(description: 'Field type')] ?string $fieldType = null,
    ): array {
        try {
            $apiKey = $this->apiKeyProvider->getApiKey();

            return match ($action) {
                'create' => $this->customFieldService->createField($apiKey, $groupType ?? throw new ValidationException('groupType required for create'), $groupId ?? throw new ValidationException('groupId required for create'), $name, $fieldType),
                'update' => $this->customFieldService->updateField($apiKey, $fieldId ?? throw new ValidationException('fieldId required for update'), $name, $fieldType),
                'delete' => $this->customFieldService->deleteField($apiKey, $fieldId ?? throw new ValidationException('fieldId required for delete')),
                default => throw new ValidationException(sprintf('Invalid action "%s". Must be: create, update, delete', $action)),
            };
        } catch (\Throwable $e) {
            throw new ToolCallException($e->getMessage(), $e->getCode(), $e);
        }
    }

    /** @return array<mixed> */
    #[McpTool(name: 'planka_manage_custom_field_values', description: 'Set or delete a custom field value on a card.')]
    public function manageCustomFieldValues(
        #[Schema(description: 'Action to perform', enum: ['set', 'delete'])] string $action,
        #[Schema(description: 'The card ID')] string $cardId,
        #[Schema(description: 'The custom field group ID')] string $customFieldGroupId,
        #[Schema(description: 'The custom field ID')] string $customFieldId,
        #[Schema(description: 'The value to set — required for action=set')] ?string $value = null,
    ): array {
        try {
            $apiKey = $this->apiKeyProvider->getApiKey();

            return match ($action) {
                'set' => $this->customFieldService->setFieldValue($apiKey, $cardId, $customFieldGroupId, $customFieldId, $value ?? throw new ValidationException('value required for set')),
                'delete' => $this->customFieldService->deleteFieldValue($apiKey, $cardId, $customFieldGroupId, $customFieldId),
                default => throw new ValidationException(sprintf('Invalid action "%s". Must be: set, delete', $action)),
            };
        } catch (\Throwable $e) {
            throw new ToolCallException($e->getMessage(), $e->getCode(), $e);
        }
    }
}
