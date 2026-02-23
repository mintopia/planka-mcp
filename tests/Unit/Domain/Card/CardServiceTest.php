<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\Card;

use App\Domain\Card\CardService;
use App\Planka\Exception\AuthenticationException;
use App\Planka\Exception\PlankaApiException;
use App\Planka\PlankaClientInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

final class CardServiceTest extends TestCase
{
    private PlankaClientInterface&MockObject $plankaClient;
    private CardService $service;

    protected function setUp(): void
    {
        $this->plankaClient = $this->createMock(PlankaClientInterface::class);
        $this->service = new CardService($this->plankaClient);
    }

    public function testCreateCardSuccess(): void
    {
        $expected = ['item' => ['id' => 'card1', 'name' => 'New Card']];

        $this->plankaClient
            ->expects($this->once())
            ->method('post')
            ->willReturn($expected);

        $result = $this->service->createCard('test-api-key', 'list1', 'New Card');
        $this->assertSame($expected, $result);
    }

    public function testCreateCardWithDescription(): void
    {
        $expected = ['item' => ['id' => 'card1', 'name' => 'Card', 'description' => 'Desc']];

        $this->plankaClient
            ->expects($this->once())
            ->method('post')
            ->willReturn($expected);

        $result = $this->service->createCard('test-api-key', 'list1', 'Card', 'Desc');
        $this->assertSame($expected, $result);
    }

    public function testCreateCardWithLabelsAddsLabelsAfterCreation(): void
    {
        $cardResult = ['item' => ['id' => 'card1', 'name' => 'Card']];

        $this->plankaClient
            ->expects($this->exactly(3))
            ->method('post')
            ->willReturn($cardResult);

        $result = $this->service->createCard('test-api-key', 'list1', 'Card', null, null, ['label1', 'label2']);
        $this->assertSame($cardResult, $result);
    }

    public function testCreateCardPropagatesAuthException(): void
    {
        $this->plankaClient->method('post')->willThrowException(new AuthenticationException('Unauthorized'));
        $this->expectException(AuthenticationException::class);
        $this->service->createCard('bad-key', 'list1', 'Name');
    }

    public function testCreateCardPropagatesApiException(): void
    {
        $this->plankaClient->method('post')->willThrowException(new PlankaApiException('Server error'));
        $this->expectException(PlankaApiException::class);
        $this->service->createCard('test-api-key', 'list1', 'Name');
    }

    public function testGetCardSuccess(): void
    {
        $expected = ['item' => ['id' => 'card1', 'name' => 'Card']];

        $this->plankaClient
            ->expects($this->once())
            ->method('get')
            ->with('test-api-key', '/api/cards/card1')
            ->willReturn($expected);

        $this->assertSame($expected, $this->service->getCard('test-api-key', 'card1'));
    }

    public function testGetCardPropagatesAuthException(): void
    {
        $this->plankaClient->method('get')->willThrowException(new AuthenticationException('Unauthorized'));
        $this->expectException(AuthenticationException::class);
        $this->service->getCard('bad-key', 'card1');
    }

    public function testGetCardPropagatesApiException(): void
    {
        $this->plankaClient->method('get')->willThrowException(new PlankaApiException('Server error'));
        $this->expectException(PlankaApiException::class);
        $this->service->getCard('test-api-key', 'card1');
    }

    public function testUpdateCardWithName(): void
    {
        $expected = ['item' => ['id' => 'card1', 'name' => 'Updated']];

        $this->plankaClient
            ->expects($this->once())
            ->method('patch')
            ->with('test-api-key', '/api/cards/card1', ['name' => 'Updated'])
            ->willReturn($expected);

        $this->assertSame($expected, $this->service->updateCard('test-api-key', 'card1', 'Updated'));
    }

    public function testUpdateCardWithDescription(): void
    {
        $expected = ['item' => ['id' => 'card1']];

        $this->plankaClient
            ->expects($this->once())
            ->method('patch')
            ->with('test-api-key', '/api/cards/card1', ['description' => 'New desc'])
            ->willReturn($expected);

        $this->assertSame($expected, $this->service->updateCard('test-api-key', 'card1', null, 'New desc'));
    }

    public function testUpdateCardClearDescription(): void
    {
        $expected = ['item' => ['id' => 'card1']];

        $this->plankaClient
            ->expects($this->once())
            ->method('patch')
            ->with('test-api-key', '/api/cards/card1', ['description' => null])
            ->willReturn($expected);

        $this->assertSame($expected, $this->service->updateCard('test-api-key', 'card1', null, ''));
    }

    public function testUpdateCardWithDueDate(): void
    {
        $expected = ['item' => ['id' => 'card1']];

        $this->plankaClient
            ->expects($this->once())
            ->method('patch')
            ->with('test-api-key', '/api/cards/card1', ['dueDate' => '2025-01-01T00:00:00Z'])
            ->willReturn($expected);

        $this->assertSame($expected, $this->service->updateCard('test-api-key', 'card1', null, null, '2025-01-01T00:00:00Z'));
    }

    public function testUpdateCardClearDueDate(): void
    {
        $expected = ['item' => ['id' => 'card1']];

        $this->plankaClient
            ->expects($this->once())
            ->method('patch')
            ->with('test-api-key', '/api/cards/card1', ['dueDate' => null])
            ->willReturn($expected);

        $this->assertSame($expected, $this->service->updateCard('test-api-key', 'card1', null, null, ''));
    }

    public function testUpdateCardWithIsClosed(): void
    {
        $expected = ['item' => ['id' => 'card1']];

        $this->plankaClient
            ->expects($this->once())
            ->method('patch')
            ->with('test-api-key', '/api/cards/card1', ['isClosed' => true])
            ->willReturn($expected);

        $this->assertSame($expected, $this->service->updateCard('test-api-key', 'card1', null, null, null, true));
    }

    public function testUpdateCardWithNullsSendsEmptyBody(): void
    {
        $expected = ['item' => ['id' => 'card1']];

        $this->plankaClient
            ->expects($this->once())
            ->method('patch')
            ->with('test-api-key', '/api/cards/card1', [])
            ->willReturn($expected);

        $this->assertSame($expected, $this->service->updateCard('test-api-key', 'card1'));
    }

    public function testUpdateCardPropagatesAuthException(): void
    {
        $this->plankaClient->method('patch')->willThrowException(new AuthenticationException('Unauthorized'));
        $this->expectException(AuthenticationException::class);
        $this->service->updateCard('bad-key', 'card1', 'Name');
    }

    public function testUpdateCardPropagatesApiException(): void
    {
        $this->plankaClient->method('patch')->willThrowException(new PlankaApiException('Server error'));
        $this->expectException(PlankaApiException::class);
        $this->service->updateCard('test-api-key', 'card1', 'Name');
    }

    public function testMoveCardSuccess(): void
    {
        $expected = ['item' => ['id' => 'card1']];

        $this->plankaClient
            ->expects($this->once())
            ->method('patch')
            ->with('test-api-key', '/api/cards/card1', ['listId' => 'list2', 'position' => 65536])
            ->willReturn($expected);

        $this->assertSame($expected, $this->service->moveCard('test-api-key', 'card1', 'list2'));
    }

    public function testMoveCardWithPosition(): void
    {
        $expected = ['item' => ['id' => 'card1']];

        $this->plankaClient
            ->expects($this->once())
            ->method('patch')
            ->with('test-api-key', '/api/cards/card1', ['listId' => 'list2', 'position' => 100])
            ->willReturn($expected);

        $this->assertSame($expected, $this->service->moveCard('test-api-key', 'card1', 'list2', 100));
    }

    public function testMoveCardPropagatesAuthException(): void
    {
        $this->plankaClient->method('patch')->willThrowException(new AuthenticationException('Unauthorized'));
        $this->expectException(AuthenticationException::class);
        $this->service->moveCard('bad-key', 'card1', 'list2');
    }

    public function testDeleteCardSuccess(): void
    {
        $this->plankaClient
            ->expects($this->once())
            ->method('delete')
            ->with('test-api-key', '/api/cards/card1')
            ->willReturn([]);

        $this->assertSame([], $this->service->deleteCard('test-api-key', 'card1'));
    }

    public function testDeleteCardPropagatesAuthException(): void
    {
        $this->plankaClient->method('delete')->willThrowException(new AuthenticationException('Unauthorized'));
        $this->expectException(AuthenticationException::class);
        $this->service->deleteCard('bad-key', 'card1');
    }

    public function testDeleteCardPropagatesApiException(): void
    {
        $this->plankaClient->method('delete')->willThrowException(new PlankaApiException('Server error'));
        $this->expectException(PlankaApiException::class);
        $this->service->deleteCard('test-api-key', 'card1');
    }

    public function testDuplicateCardSuccess(): void
    {
        $expected = ['item' => ['id' => 'card2', 'name' => 'Card Copy']];

        $this->plankaClient
            ->expects($this->once())
            ->method('post')
            ->with('test-api-key', '/api/cards/card1/duplicate', ['position' => 65536])
            ->willReturn($expected);

        $this->assertSame($expected, $this->service->duplicateCard('test-api-key', 'card1'));
    }

    public function testDuplicateCardPropagatesAuthException(): void
    {
        $this->plankaClient->method('post')->willThrowException(new AuthenticationException('Unauthorized'));
        $this->expectException(AuthenticationException::class);
        $this->service->duplicateCard('bad-key', 'card1');
    }

    public function testAddCardMemberSuccess(): void
    {
        $expected = ['item' => ['id' => 'cm1', 'userId' => 'user1']];

        $this->plankaClient
            ->expects($this->once())
            ->method('post')
            ->with('test-api-key', '/api/cards/card1/card-memberships', ['userId' => 'user1'])
            ->willReturn($expected);

        $this->assertSame($expected, $this->service->addCardMember('test-api-key', 'card1', 'user1'));
    }

    public function testAddCardMemberPropagatesAuthException(): void
    {
        $this->plankaClient->method('post')->willThrowException(new AuthenticationException('Unauthorized'));
        $this->expectException(AuthenticationException::class);
        $this->service->addCardMember('bad-key', 'card1', 'user1');
    }

    public function testRemoveCardMemberSuccess(): void
    {
        $this->plankaClient
            ->expects($this->once())
            ->method('delete')
            ->with('test-api-key', '/api/cards/card1/card-memberships/userId:user1')
            ->willReturn([]);

        $this->assertSame([], $this->service->removeCardMember('test-api-key', 'card1', 'user1'));
    }

    public function testRemoveCardMemberPropagatesAuthException(): void
    {
        $this->plankaClient->method('delete')->willThrowException(new AuthenticationException('Unauthorized'));
        $this->expectException(AuthenticationException::class);
        $this->service->removeCardMember('bad-key', 'card1', 'user1');
    }

    public function testRemoveCardMemberPropagatesApiException(): void
    {
        $this->plankaClient->method('delete')->willThrowException(new PlankaApiException('Server error'));
        $this->expectException(PlankaApiException::class);
        $this->service->removeCardMember('test-api-key', 'card1', 'user1');
    }
}
