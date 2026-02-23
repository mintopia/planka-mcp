<?php

declare(strict_types=1);

namespace App\Domain\Label;

interface LabelServiceInterface
{
    /** @return array<mixed> */
    public function manageLabel(
        string $apiKey,
        string $action,
        ?string $boardId = null,
        ?string $labelId = null,
        ?string $name = null,
        ?string $color = null,
    ): array;

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
    ): array;
}
