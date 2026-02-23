<?php

declare(strict_types=1);

namespace App\Subscription;

final class PlankaEventMapper
{
    /**
     * Map a Planka event type + data to affected planka:// resource URIs.
     *
     * @param array<string, mixed> $data
     * @return list<string>
     */
    public function mapToUris(string $eventType, array $data): array
    {
        $uris = [];

        // Extract common IDs from event data
        $boardId = (string) ($data['boardId'] ?? $data['item']['boardId'] ?? '');
        $cardId = (string) ($data['cardId'] ?? $data['item']['cardId'] ?? $data['item']['id'] ?? '');
        $listId = (string) ($data['listId'] ?? $data['item']['listId'] ?? '');
        $notificationUserId = (string) ($data['userId'] ?? $data['item']['userId'] ?? '');

        match (true) {
            // Card events — affect card resource, parent board, and parent list
            str_starts_with($eventType, 'cardCreate') => $this->cardCreateUris($uris, $boardId, $listId, $cardId),
            str_starts_with($eventType, 'cardUpdate') => $this->cardUpdateUris($uris, $boardId, $listId, $cardId, $data),
            str_starts_with($eventType, 'cardDelete') => $this->cardDeleteUris($uris, $boardId, $listId),

            // Comment events — affect card resource
            str_starts_with($eventType, 'commentCreate'),
            str_starts_with($eventType, 'commentUpdate'),
            str_starts_with($eventType, 'commentDelete') => $this->commentUris($uris, $cardId),

            // Task events — affect card resource
            str_starts_with($eventType, 'taskCreate'),
            str_starts_with($eventType, 'taskUpdate'),
            str_starts_with($eventType, 'taskDelete') => $this->taskUris($uris, $cardId),

            // Board events — affect board resource
            str_starts_with($eventType, 'boardCreate'),
            str_starts_with($eventType, 'boardUpdate'),
            str_starts_with($eventType, 'boardDelete') => $this->boardUris($uris, $boardId),

            // List events — affect board resource
            str_starts_with($eventType, 'listCreate'),
            str_starts_with($eventType, 'listUpdate'),
            str_starts_with($eventType, 'listDelete') => $this->listUris($uris, $boardId, $listId),

            // Label events — affect board resource
            str_starts_with($eventType, 'labelCreate'),
            str_starts_with($eventType, 'labelUpdate'),
            str_starts_with($eventType, 'labelDelete') => $this->labelUris($uris, $boardId),

            // Notification events — affect notifications resource for the user
            str_starts_with($eventType, 'notificationCreate') => $this->notificationUris($uris, $notificationUserId),

            // Attachment events — affect card resource
            str_starts_with($eventType, 'attachmentCreate'),
            str_starts_with($eventType, 'attachmentUpdate'),
            str_starts_with($eventType, 'attachmentDelete') => $this->attachmentUris($uris, $cardId),

            default => null,
        };

        return array_values(array_unique($uris));
    }

    /** @param list<string> $uris */
    private function cardCreateUris(array &$uris, string $boardId, string $listId, string $cardId): void
    {
        if ($boardId !== '') {
            $uris[] = 'planka://boards/' . $boardId;
        }
        if ($listId !== '') {
            $uris[] = 'planka://lists/' . $listId . '/cards';
        }
    }

    /**
     * @param list<string> $uris
     * @param array<string, mixed> $data
     */
    private function cardUpdateUris(array &$uris, string $boardId, string $listId, string $cardId, array $data): void
    {
        if ($cardId !== '') {
            $uris[] = 'planka://cards/' . $cardId;
        }
        if ($boardId !== '') {
            $uris[] = 'planka://boards/' . $boardId;
        }
        // If the card was moved to a different list, both lists are affected
        $prevListId = (string) ($data['prevListId'] ?? '');
        if ($listId !== '') {
            $uris[] = 'planka://lists/' . $listId . '/cards';
        }
        if ($prevListId !== '' && $prevListId !== $listId) {
            $uris[] = 'planka://lists/' . $prevListId . '/cards';
        }
    }

    /** @param list<string> $uris */
    private function cardDeleteUris(array &$uris, string $boardId, string $listId): void
    {
        if ($boardId !== '') {
            $uris[] = 'planka://boards/' . $boardId;
        }
        if ($listId !== '') {
            $uris[] = 'planka://lists/' . $listId . '/cards';
        }
    }

    /** @param list<string> $uris */
    private function commentUris(array &$uris, string $cardId): void
    {
        if ($cardId !== '') {
            $uris[] = 'planka://cards/' . $cardId;
            $uris[] = 'planka://cards/' . $cardId . '/comments';
        }
    }

    /** @param list<string> $uris */
    private function taskUris(array &$uris, string $cardId): void
    {
        if ($cardId !== '') {
            $uris[] = 'planka://cards/' . $cardId;
        }
    }

    /** @param list<string> $uris */
    private function boardUris(array &$uris, string $boardId): void
    {
        if ($boardId !== '') {
            $uris[] = 'planka://boards/' . $boardId;
        }
    }

    /** @param list<string> $uris */
    private function listUris(array &$uris, string $boardId, string $listId): void
    {
        if ($boardId !== '') {
            $uris[] = 'planka://boards/' . $boardId;
        }
        if ($listId !== '') {
            $uris[] = 'planka://lists/' . $listId;
            $uris[] = 'planka://lists/' . $listId . '/cards';
        }
    }

    /** @param list<string> $uris */
    private function labelUris(array &$uris, string $boardId): void
    {
        if ($boardId !== '') {
            $uris[] = 'planka://boards/' . $boardId;
        }
    }

    /** @param list<string> $uris */
    private function notificationUris(array &$uris, string $userId): void
    {
        // Notifications are keyed by user — the resource is planka://notifications
        // (user-scoped based on the API key used for the MCP session)
        $uris[] = 'planka://notifications';
    }

    /** @param list<string> $uris */
    private function attachmentUris(array &$uris, string $cardId): void
    {
        if ($cardId !== '') {
            $uris[] = 'planka://cards/' . $cardId;
        }
    }
}
