<?php

declare(strict_types=1);

namespace App\Domain\Label;

use App\Planka\Client\PlankaClientInterface;
use App\Shared\Exception\ValidationException;

final class LabelService
{
    public function __construct(
        private readonly PlankaClientInterface $plankaClient,
    ) {}

    /** @return array<mixed> */
    public function manageLabel(
        string $apiKey,
        string $action,
        ?string $boardId = null,
        ?string $labelId = null,
        ?string $name = null,
        ?string $color = null,
    ): array {
        return match ($action) {
            'create' => $this->createLabel($apiKey, $boardId ?? throw new ValidationException('boardId required for create'), $name, $color),
            'update' => $this->updateLabel($apiKey, $labelId ?? throw new ValidationException('labelId required for update'), $name, $color),
            'delete' => $this->deleteLabel($apiKey, $labelId ?? throw new ValidationException('labelId required for delete')),
            default => throw new ValidationException(sprintf('Invalid action "%s". Must be: create, update, delete', $action)),
        };
    }

    /** @return array<mixed> */
    private function createLabel(string $apiKey, string $boardId, ?string $name, ?string $color): array
    {
        $body = [];
        if ($name !== null) {
            $body['name'] = $name;
        }
        if ($color !== null) {
            $body['color'] = $color;
        }
        return $this->plankaClient->post($apiKey, '/api/boards/' . $boardId . '/labels', $body);
    }

    /** @return array<mixed> */
    private function updateLabel(string $apiKey, string $labelId, ?string $name, ?string $color): array
    {
        $body = [];
        if ($name !== null) {
            $body['name'] = $name;
        }
        if ($color !== null) {
            $body['color'] = $color;
        }
        return $this->plankaClient->patch($apiKey, '/api/labels/' . $labelId, $body);
    }

    /** @return array<mixed> */
    private function deleteLabel(string $apiKey, string $labelId): array
    {
        return $this->plankaClient->delete($apiKey, '/api/labels/' . $labelId);
    }

    /**
     * @param string[] $addLabelIds
     * @param string[] $removeLabelIds
     * @return array<mixed>
     */
    public function setCardLabels(
        string $apiKey,
        string $cardId,
        array $addLabelIds = [],
        array $removeLabelIds = [],
    ): array {
        $results = [];
        foreach ($addLabelIds as $labelId) {
            $results['added'][] = $this->plankaClient->post($apiKey, '/api/cards/' . $cardId . '/card-labels', ['labelId' => $labelId]);
        }
        foreach ($removeLabelIds as $labelId) {
            $results['removed'][] = $this->plankaClient->delete($apiKey, '/api/cards/' . $cardId . '/card-labels/labelId:' . $labelId);
        }
        return $results;
    }
}
