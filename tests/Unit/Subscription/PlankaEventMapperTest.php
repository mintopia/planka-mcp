<?php

declare(strict_types=1);

namespace Tests\Unit\Subscription;

use App\Subscription\PlankaEventMapper;
use PHPUnit\Framework\TestCase;

final class PlankaEventMapperTest extends TestCase
{
    private PlankaEventMapper $mapper;

    protected function setUp(): void
    {
        $this->mapper = new PlankaEventMapper();
    }

    public function testCardCreateEventReturnsBoardAndListCardsUris(): void
    {
        $data = ['boardId' => 'b1', 'listId' => 'l1', 'item' => ['id' => 'c1']];

        $uris = $this->mapper->mapToUris('cardCreate', $data);

        $this->assertContains('planka://boards/b1', $uris);
        $this->assertContains('planka://lists/l1/cards', $uris);
        $this->assertCount(2, $uris);
    }

    public function testCardUpdateEventReturnsCardBoardAndListUris(): void
    {
        $data = ['boardId' => 'b1', 'listId' => 'l1', 'item' => ['id' => 'c1']];

        $uris = $this->mapper->mapToUris('cardUpdate', $data);

        $this->assertContains('planka://cards/c1', $uris);
        $this->assertContains('planka://boards/b1', $uris);
        $this->assertContains('planka://lists/l1/cards', $uris);
    }

    public function testCardUpdateWithMoveReturnsBothListUris(): void
    {
        $data = [
            'boardId'    => 'b1',
            'listId'     => 'l2',
            'prevListId' => 'l1',
            'item'       => ['id' => 'c1'],
        ];

        $uris = $this->mapper->mapToUris('cardUpdate', $data);

        $this->assertContains('planka://lists/l1/cards', $uris);
        $this->assertContains('planka://lists/l2/cards', $uris);
    }

    public function testCardUpdateSameListDeduplicatesUri(): void
    {
        $data = [
            'boardId'    => 'b1',
            'listId'     => 'l1',
            'prevListId' => 'l1',
            'item'       => ['id' => 'c1'],
        ];

        $uris = $this->mapper->mapToUris('cardUpdate', $data);

        // Only one entry for lists/l1/cards — duplicate is removed
        $listUris = array_filter($uris, fn (string $u) => $u === 'planka://lists/l1/cards');
        $this->assertCount(1, $listUris);
    }

    public function testCardDeleteEventReturnsBoardAndListUris(): void
    {
        $data = ['boardId' => 'b1', 'listId' => 'l1'];

        $uris = $this->mapper->mapToUris('cardDelete', $data);

        $this->assertContains('planka://boards/b1', $uris);
        $this->assertContains('planka://lists/l1/cards', $uris);
        $this->assertCount(2, $uris);
    }

    public function testCommentCreateEventReturnsCardAndCommentsUris(): void
    {
        $data = ['item' => ['cardId' => 'c1']];

        $uris = $this->mapper->mapToUris('commentCreate', $data);

        $this->assertContains('planka://cards/c1', $uris);
        $this->assertContains('planka://cards/c1/comments', $uris);
        $this->assertCount(2, $uris);
    }

    public function testCommentUpdateEventReturnsCardAndCommentsUris(): void
    {
        $data = ['item' => ['cardId' => 'c1']];

        $uris = $this->mapper->mapToUris('commentUpdate', $data);

        $this->assertContains('planka://cards/c1', $uris);
        $this->assertContains('planka://cards/c1/comments', $uris);
        $this->assertCount(2, $uris);
    }

    public function testCommentDeleteEventReturnsCardAndCommentsUris(): void
    {
        $data = ['item' => ['cardId' => 'c1']];

        $uris = $this->mapper->mapToUris('commentDelete', $data);

        $this->assertContains('planka://cards/c1', $uris);
        $this->assertContains('planka://cards/c1/comments', $uris);
        $this->assertCount(2, $uris);
    }

    public function testTaskCreateEventReturnsCardUri(): void
    {
        $data = ['item' => ['cardId' => 'c1']];

        $uris = $this->mapper->mapToUris('taskCreate', $data);

        $this->assertSame(['planka://cards/c1'], $uris);
    }

    public function testTaskUpdateEventReturnsCardUri(): void
    {
        $data = ['item' => ['cardId' => 'c1']];

        $uris = $this->mapper->mapToUris('taskUpdate', $data);

        $this->assertSame(['planka://cards/c1'], $uris);
    }

    public function testTaskDeleteEventReturnsCardUri(): void
    {
        $data = ['item' => ['cardId' => 'c1']];

        $uris = $this->mapper->mapToUris('taskDelete', $data);

        $this->assertSame(['planka://cards/c1'], $uris);
    }

    public function testBoardCreateEventReturnsBoardUri(): void
    {
        $data = ['item' => ['id' => 'b1', 'boardId' => 'b1']];

        $uris = $this->mapper->mapToUris('boardCreate', $data);

        $this->assertSame(['planka://boards/b1'], $uris);
    }

    public function testBoardUpdateEventReturnsBoardUri(): void
    {
        $data = ['boardId' => 'b1'];

        $uris = $this->mapper->mapToUris('boardUpdate', $data);

        $this->assertSame(['planka://boards/b1'], $uris);
    }

    public function testBoardDeleteEventReturnsBoardUri(): void
    {
        $data = ['boardId' => 'b1'];

        $uris = $this->mapper->mapToUris('boardDelete', $data);

        $this->assertSame(['planka://boards/b1'], $uris);
    }

    public function testListCreateEventReturnsBoardAndListUris(): void
    {
        $data = ['boardId' => 'b1', 'item' => ['id' => 'l1', 'listId' => 'l1']];

        $uris = $this->mapper->mapToUris('listCreate', $data);

        $this->assertContains('planka://boards/b1', $uris);
        $this->assertContains('planka://lists/l1', $uris);
        $this->assertContains('planka://lists/l1/cards', $uris);
    }

    public function testListUpdateEventReturnsBoardAndListUris(): void
    {
        $data = ['boardId' => 'b1', 'listId' => 'l1'];

        $uris = $this->mapper->mapToUris('listUpdate', $data);

        $this->assertContains('planka://boards/b1', $uris);
        $this->assertContains('planka://lists/l1', $uris);
        $this->assertContains('planka://lists/l1/cards', $uris);
    }

    public function testListDeleteEventReturnsBoardAndListUris(): void
    {
        $data = ['boardId' => 'b1', 'listId' => 'l1'];

        $uris = $this->mapper->mapToUris('listDelete', $data);

        $this->assertContains('planka://boards/b1', $uris);
        $this->assertContains('planka://lists/l1', $uris);
        $this->assertContains('planka://lists/l1/cards', $uris);
    }

    public function testLabelCreateEventReturnsBoardUri(): void
    {
        $data = ['boardId' => 'b1'];

        $uris = $this->mapper->mapToUris('labelCreate', $data);

        $this->assertSame(['planka://boards/b1'], $uris);
    }

    public function testLabelUpdateEventReturnsBoardUri(): void
    {
        $data = ['boardId' => 'b1'];

        $uris = $this->mapper->mapToUris('labelUpdate', $data);

        $this->assertSame(['planka://boards/b1'], $uris);
    }

    public function testLabelDeleteEventReturnsBoardUri(): void
    {
        $data = ['boardId' => 'b1'];

        $uris = $this->mapper->mapToUris('labelDelete', $data);

        $this->assertSame(['planka://boards/b1'], $uris);
    }

    public function testNotificationCreateEventReturnsNotificationsUri(): void
    {
        $data = ['item' => ['userId' => 'u1']];

        $uris = $this->mapper->mapToUris('notificationCreate', $data);

        $this->assertSame(['planka://notifications'], $uris);
    }

    public function testAttachmentCreateEventReturnsCardUri(): void
    {
        $data = ['item' => ['cardId' => 'c1']];

        $uris = $this->mapper->mapToUris('attachmentCreate', $data);

        $this->assertSame(['planka://cards/c1'], $uris);
    }

    public function testAttachmentUpdateEventReturnsCardUri(): void
    {
        $data = ['item' => ['cardId' => 'c1']];

        $uris = $this->mapper->mapToUris('attachmentUpdate', $data);

        $this->assertSame(['planka://cards/c1'], $uris);
    }

    public function testAttachmentDeleteEventReturnsCardUri(): void
    {
        $data = ['item' => ['cardId' => 'c1']];

        $uris = $this->mapper->mapToUris('attachmentDelete', $data);

        $this->assertSame(['planka://cards/c1'], $uris);
    }

    public function testUnknownEventTypeReturnsEmptyArray(): void
    {
        $data = ['boardId' => 'b1'];

        $uris = $this->mapper->mapToUris('unknownEventXyz', $data);

        $this->assertSame([], $uris);
    }

    public function testMissingIdsInDataReturnsEmptyArray(): void
    {
        // cardCreate requires boardId and listId; without them nothing is added
        $uris = $this->mapper->mapToUris('cardCreate', []);

        $this->assertSame([], $uris);
    }

    public function testDataExtractionFromItemSubKey(): void
    {
        // IDs nested under 'item' sub-key must still be extracted
        $data = ['item' => ['boardId' => 'b1', 'listId' => 'l1']];

        $uris = $this->mapper->mapToUris('cardCreate', $data);

        $this->assertContains('planka://boards/b1', $uris);
        $this->assertContains('planka://lists/l1/cards', $uris);
    }

    public function testResultIsDeduplicated(): void
    {
        // cardUpdate with prevListId === listId should yield only one list URI
        $data = [
            'boardId'    => 'b1',
            'listId'     => 'l1',
            'prevListId' => 'l1',
            'item'       => ['id' => 'c1'],
        ];

        $uris = $this->mapper->mapToUris('cardUpdate', $data);

        // Each unique URI appears exactly once
        $this->assertSame(count($uris), count(array_unique($uris)));
        $this->assertCount(count(array_unique($uris)), $uris);
    }
}
