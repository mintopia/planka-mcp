<?php

declare(strict_types=1);

namespace App\Domain\CustomField;

interface CustomFieldServiceInterface
{
    /** @return array<mixed> */
    public function createBaseGroup(string $apiKey, string $projectId, ?string $name): array;

    /** @return array<mixed> */
    public function updateBaseGroup(string $apiKey, string $baseGroupId, ?string $name): array;

    /** @return array<mixed> */
    public function deleteBaseGroup(string $apiKey, string $baseGroupId): array;

    /** @return array<mixed> */
    public function createGroup(string $apiKey, string $parentType, string $parentId, ?string $name): array;

    /** @return array<mixed> */
    public function getGroup(string $apiKey, string $groupId): array;

    /** @return array<mixed> */
    public function updateGroup(string $apiKey, string $groupId, ?string $name): array;

    /** @return array<mixed> */
    public function deleteGroup(string $apiKey, string $groupId): array;

    /** @return array<mixed> */
    public function createField(string $apiKey, string $groupType, string $groupId, ?string $name, ?string $fieldType): array;

    /** @return array<mixed> */
    public function updateField(string $apiKey, string $fieldId, ?string $name, ?string $fieldType): array;

    /** @return array<mixed> */
    public function deleteField(string $apiKey, string $fieldId): array;

    /** @return array<mixed> */
    public function setFieldValue(string $apiKey, string $cardId, string $customFieldGroupId, string $customFieldId, string $value): array;

    /** @return array<mixed> */
    public function deleteFieldValue(string $apiKey, string $cardId, string $customFieldGroupId, string $customFieldId): array;
}
