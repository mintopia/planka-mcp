<?php

declare(strict_types=1);

namespace App\Domain\CustomField;

use App\Exception\ValidationException;
use App\Planka\PlankaClientInterface;

final class CustomFieldService implements CustomFieldServiceInterface
{
    public function __construct(
        private readonly PlankaClientInterface $plankaClient,
    ) {}

    /** @return array<mixed> */
    public function createBaseGroup(string $apiKey, string $projectId, ?string $name): array
    {
        $body = ['position' => 65536];
        if ($name !== null) {
            $body['name'] = $name;
        }

        return $this->plankaClient->post($apiKey, '/api/projects/' . $projectId . '/base-custom-field-groups', $body);
    }

    /** @return array<mixed> */
    public function updateBaseGroup(string $apiKey, string $baseGroupId, ?string $name): array
    {
        $body = [];
        if ($name !== null) {
            $body['name'] = $name;
        }

        return $this->plankaClient->patch($apiKey, '/api/base-custom-field-groups/' . $baseGroupId, $body);
    }

    /** @return array<mixed> */
    public function deleteBaseGroup(string $apiKey, string $baseGroupId): array
    {
        return $this->plankaClient->delete($apiKey, '/api/base-custom-field-groups/' . $baseGroupId);
    }

    /** @return array<mixed> */
    public function createGroup(string $apiKey, string $parentType, string $parentId, ?string $name): array
    {
        $path = match ($parentType) {
            'board' => '/api/boards/' . $parentId . '/custom-field-groups',
            'card' => '/api/cards/' . $parentId . '/custom-field-groups',
            default => throw new ValidationException('Invalid parentType "' . $parentType . '". Must be "board" or "card".'),
        };

        $body = ['position' => 65536];
        if ($name !== null) {
            $body['name'] = $name;
        }

        return $this->plankaClient->post($apiKey, $path, $body);
    }

    /** @return array<mixed> */
    public function getGroup(string $apiKey, string $groupId): array
    {
        return $this->plankaClient->get($apiKey, '/api/custom-field-groups/' . $groupId);
    }

    /** @return array<mixed> */
    public function updateGroup(string $apiKey, string $groupId, ?string $name): array
    {
        $body = [];
        if ($name !== null) {
            $body['name'] = $name;
        }

        return $this->plankaClient->patch($apiKey, '/api/custom-field-groups/' . $groupId, $body);
    }

    /** @return array<mixed> */
    public function deleteGroup(string $apiKey, string $groupId): array
    {
        return $this->plankaClient->delete($apiKey, '/api/custom-field-groups/' . $groupId);
    }

    /** @return array<mixed> */
    public function createField(string $apiKey, string $groupType, string $groupId, ?string $name, ?string $fieldType): array
    {
        $path = match ($groupType) {
            'base' => '/api/base-custom-field-groups/' . $groupId . '/custom-fields',
            'group' => '/api/custom-field-groups/' . $groupId . '/custom-fields',
            default => throw new ValidationException('Invalid groupType "' . $groupType . '". Must be "base" or "group".'),
        };

        $body = ['position' => 65536];
        if ($name !== null) {
            $body['name'] = $name;
        }
        if ($fieldType !== null) {
            $body['type'] = $fieldType;
        }

        return $this->plankaClient->post($apiKey, $path, $body);
    }

    /** @return array<mixed> */
    public function updateField(string $apiKey, string $fieldId, ?string $name, ?string $fieldType): array
    {
        $body = [];
        if ($name !== null) {
            $body['name'] = $name;
        }
        if ($fieldType !== null) {
            $body['type'] = $fieldType;
        }

        return $this->plankaClient->patch($apiKey, '/api/custom-fields/' . $fieldId, $body);
    }

    /** @return array<mixed> */
    public function deleteField(string $apiKey, string $fieldId): array
    {
        return $this->plankaClient->delete($apiKey, '/api/custom-fields/' . $fieldId);
    }

    /** @return array<mixed> */
    public function setFieldValue(string $apiKey, string $cardId, string $customFieldGroupId, string $customFieldId, string $value): array
    {
        $path = '/api/cards/' . $cardId . '/custom-field-values/customFieldGroupId:' . $customFieldGroupId . ':customFieldId:' . $customFieldId;

        return $this->plankaClient->patch($apiKey, $path, ['content' => $value]);
    }

    /** @return array<mixed> */
    public function deleteFieldValue(string $apiKey, string $cardId, string $customFieldGroupId, string $customFieldId): array
    {
        $path = '/api/cards/' . $cardId . '/custom-field-values/customFieldGroupId:' . $customFieldGroupId . ':customFieldId:' . $customFieldId;

        return $this->plankaClient->delete($apiKey, $path);
    }
}
