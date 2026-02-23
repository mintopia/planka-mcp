<?php

declare(strict_types=1);

namespace App\Tests\Mcp;

use App\Domain\Card\CardService;
use App\Infrastructure\Http\ApiKeyProvider;
use App\Mcp\CardTools;
use App\Planka\Exception\AuthenticationException;
use App\Planka\Exception\PlankaApiException;
use App\Planka\Exception\PlankaNotFoundException;
use App\Shared\Exception\ValidationException;
use Mcp\Exception\ToolCallException;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

final class CardToolsTest extends TestCase
{
    private const string API_KEY = 'test-api-key';
    private const string CARD_ID = 'card-abc123';
    private const string LIST_ID = 'list-xyz789';

    private CardService&MockObject $cardService;
    private ApiKeyProvider&MockObject $apiKeyProvider;
    private CardTools $tools;

    protected function setUp(): void
    {
        $this->cardService = $this->createMock(CardService::class);
        $this->apiKeyProvider = $this->createMock(ApiKeyProvider::class);
        $this->tools = new CardTools($this->cardService, $this->apiKeyProvider);
    }

    // -------------------------------------------------------------------------
    // createCard()
    // -------------------------------------------------------------------------

    public function testCreateCardSuccessWithAllParams(): void
    {
        $expected = ['item' => ['id' => self::CARD_ID, 'name' => 'Full Card']];
        $labelIds = ['label-1', 'label-2'];

        $this->apiKeyProvider
            ->expects($this->once())
            ->method('getApiKey')
            ->willReturn(self::API_KEY);

        $this->cardService
            ->expects($this->once())
            ->method('createCard')
            ->with(
                self::API_KEY,
                self::LIST_ID,
                'Full Card',
                'A description',
                'story',
                $labelIds,
            )
            ->willReturn($expected);

        $result = $this->tools->createCard(
            listId: self::LIST_ID,
            name: 'Full Card',
            description: 'A description',
            type: 'story',
            labelIds: $labelIds,
        );

        $this->assertSame($expected, $result);
    }

    public function testCreateCardWithEmptyNameThrowsToolCallException(): void
    {
        $this->apiKeyProvider->expects($this->never())->method('getApiKey');
        $this->cardService->expects($this->never())->method('createCard');

        $this->expectException(ToolCallException::class);
        $this->expectExceptionMessage('Card name cannot be empty.');

        $this->tools->createCard(listId: self::LIST_ID, name: '');
    }

    public function testCreateCardWithWhitespaceOnlyNameThrowsToolCallException(): void
    {
        $this->apiKeyProvider->expects($this->never())->method('getApiKey');
        $this->cardService->expects($this->never())->method('createCard');

        $this->expectException(ToolCallException::class);
        $this->expectExceptionMessage('Card name cannot be empty.');

        $this->tools->createCard(listId: self::LIST_ID, name: '   ');
    }

    public function testCreateCardMissingApiKeyThrowsToolCallException(): void
    {
        $this->apiKeyProvider
            ->method('getApiKey')
            ->willThrowException(new ValidationException(
                'Planka API key required. Send via "Authorization: Bearer <key>" or "X-Api-Key: <key>" header.',
            ));

        $this->cardService->expects($this->never())->method('createCard');

        $this->expectException(ToolCallException::class);

        $this->tools->createCard(listId: self::LIST_ID, name: 'Valid Name');
    }

    public function testCreateCardWrapsAuthExceptionInToolCallException(): void
    {
        $this->apiKeyProvider
            ->method('getApiKey')
            ->willReturn('bad-key');

        $this->cardService
            ->method('createCard')
            ->willThrowException(new AuthenticationException('Unauthorized', 401));

        $this->expectException(ToolCallException::class);
        $this->expectExceptionMessage('Unauthorized');

        $this->tools->createCard(listId: self::LIST_ID, name: 'My Card');
    }

    public function testCreateCardWrapsPlankaApiExceptionInToolCallException(): void
    {
        $this->apiKeyProvider
            ->method('getApiKey')
            ->willReturn(self::API_KEY);

        $this->cardService
            ->method('createCard')
            ->willThrowException(new PlankaApiException('Server error', 500));

        $this->expectException(ToolCallException::class);
        $this->expectExceptionMessage('Server error');

        $this->tools->createCard(listId: self::LIST_ID, name: 'My Card');
    }

    public function testCreateCardWrapsNotFoundExceptionInToolCallException(): void
    {
        $this->apiKeyProvider
            ->method('getApiKey')
            ->willReturn(self::API_KEY);

        $this->cardService
            ->method('createCard')
            ->willThrowException(new PlankaNotFoundException('Not found', 404));

        $this->expectException(ToolCallException::class);
        $this->expectExceptionMessage('Not found');

        $this->tools->createCard(listId: self::LIST_ID, name: 'My Card');
    }

    // -------------------------------------------------------------------------
    // getCard()
    // -------------------------------------------------------------------------

    public function testGetCardSuccess(): void
    {
        $expected = [
            'item' => [
                'id' => self::CARD_ID,
                'name' => 'Sprint Task',
                'description' => 'Do the thing',
                'tasks' => [],
                'comments' => [],
            ],
        ];

        $this->apiKeyProvider
            ->expects($this->once())
            ->method('getApiKey')
            ->willReturn(self::API_KEY);

        $this->cardService
            ->expects($this->once())
            ->method('getCard')
            ->with(self::API_KEY, self::CARD_ID)
            ->willReturn($expected);

        $this->assertSame($expected, $this->tools->getCard(self::CARD_ID));
    }

    public function testGetCardWrapsAuthExceptionInToolCallException(): void
    {
        $this->apiKeyProvider
            ->method('getApiKey')
            ->willReturn('bad-key');

        $this->cardService
            ->method('getCard')
            ->willThrowException(new AuthenticationException('Unauthorized', 401));

        $this->expectException(ToolCallException::class);
        $this->expectExceptionMessage('Unauthorized');

        $this->tools->getCard(self::CARD_ID);
    }

    public function testGetCardWrapsPlankaApiExceptionInToolCallException(): void
    {
        $this->apiKeyProvider
            ->method('getApiKey')
            ->willReturn(self::API_KEY);

        $this->cardService
            ->method('getCard')
            ->willThrowException(new PlankaApiException('Server error', 500));

        $this->expectException(ToolCallException::class);
        $this->expectExceptionMessage('Server error');

        $this->tools->getCard(self::CARD_ID);
    }

    public function testGetCardWrapsNotFoundExceptionInToolCallException(): void
    {
        $this->apiKeyProvider
            ->method('getApiKey')
            ->willReturn(self::API_KEY);

        $this->cardService
            ->method('getCard')
            ->willThrowException(new PlankaNotFoundException('Not found', 404));

        $this->expectException(ToolCallException::class);
        $this->expectExceptionMessage('Not found');

        $this->tools->getCard(self::CARD_ID);
    }

    // -------------------------------------------------------------------------
    // updateCard()
    // -------------------------------------------------------------------------

    public function testUpdateCardSuccessWithPartialParams(): void
    {
        $expected = ['item' => ['id' => self::CARD_ID, 'name' => 'Renamed Card']];

        $this->apiKeyProvider
            ->expects($this->once())
            ->method('getApiKey')
            ->willReturn(self::API_KEY);

        $this->cardService
            ->expects($this->once())
            ->method('updateCard')
            ->with(self::API_KEY, self::CARD_ID, 'Renamed Card', null, null, null)
            ->willReturn($expected);

        $result = $this->tools->updateCard(cardId: self::CARD_ID, name: 'Renamed Card');

        $this->assertSame($expected, $result);
    }

    public function testUpdateCardSuccessWithAllParams(): void
    {
        $expected = ['item' => ['id' => self::CARD_ID, 'name' => 'Complete', 'isClosed' => true]];

        $this->apiKeyProvider
            ->expects($this->once())
            ->method('getApiKey')
            ->willReturn(self::API_KEY);

        $this->cardService
            ->expects($this->once())
            ->method('updateCard')
            ->with(
                self::API_KEY,
                self::CARD_ID,
                'Complete',
                'Done now',
                '2026-02-21T00:00:00.000Z',
                true,
            )
            ->willReturn($expected);

        $result = $this->tools->updateCard(
            cardId: self::CARD_ID,
            name: 'Complete',
            description: 'Done now',
            dueDate: '2026-02-21T00:00:00.000Z',
            isClosed: true,
        );

        $this->assertSame($expected, $result);
    }

    public function testUpdateCardWithAllNullParamsThrowsToolCallException(): void
    {
        $this->apiKeyProvider->expects($this->never())->method('getApiKey');
        $this->cardService->expects($this->never())->method('updateCard');

        $this->expectException(ToolCallException::class);
        $this->expectExceptionMessage('At least one field (name, description, dueDate, or isClosed) must be provided.');

        $this->tools->updateCard(cardId: self::CARD_ID);
    }

    public function testUpdateCardWrapsAuthExceptionInToolCallException(): void
    {
        $this->apiKeyProvider
            ->method('getApiKey')
            ->willReturn('bad-key');

        $this->cardService
            ->method('updateCard')
            ->willThrowException(new AuthenticationException('Unauthorized', 401));

        $this->expectException(ToolCallException::class);
        $this->expectExceptionMessage('Unauthorized');

        $this->tools->updateCard(cardId: self::CARD_ID, name: 'X');
    }

    public function testUpdateCardWrapsPlankaApiExceptionInToolCallException(): void
    {
        $this->apiKeyProvider
            ->method('getApiKey')
            ->willReturn(self::API_KEY);

        $this->cardService
            ->method('updateCard')
            ->willThrowException(new PlankaApiException('Server error', 500));

        $this->expectException(ToolCallException::class);
        $this->expectExceptionMessage('Server error');

        $this->tools->updateCard(cardId: self::CARD_ID, name: 'X');
    }

    public function testUpdateCardWrapsNotFoundExceptionInToolCallException(): void
    {
        $this->apiKeyProvider
            ->method('getApiKey')
            ->willReturn(self::API_KEY);

        $this->cardService
            ->method('updateCard')
            ->willThrowException(new PlankaNotFoundException('Not found', 404));

        $this->expectException(ToolCallException::class);
        $this->expectExceptionMessage('Not found');

        $this->tools->updateCard(cardId: self::CARD_ID, name: 'X');
    }

    // -------------------------------------------------------------------------
    // moveCard()
    // -------------------------------------------------------------------------

    public function testMoveCardSuccessWithPosition(): void
    {
        $targetListId = 'list-done';
        $expected = ['item' => ['id' => self::CARD_ID, 'listId' => $targetListId]];

        $this->apiKeyProvider
            ->expects($this->once())
            ->method('getApiKey')
            ->willReturn(self::API_KEY);

        $this->cardService
            ->expects($this->once())
            ->method('moveCard')
            ->with(self::API_KEY, self::CARD_ID, $targetListId, 65536)
            ->willReturn($expected);

        $result = $this->tools->moveCard(
            cardId: self::CARD_ID,
            listId: $targetListId,
            position: 65536,
        );

        $this->assertSame($expected, $result);
    }

    public function testMoveCardSuccessWithoutPosition(): void
    {
        $targetListId = 'list-done';
        $expected = ['item' => ['id' => self::CARD_ID, 'listId' => $targetListId]];

        $this->apiKeyProvider
            ->expects($this->once())
            ->method('getApiKey')
            ->willReturn(self::API_KEY);

        $this->cardService
            ->expects($this->once())
            ->method('moveCard')
            ->with(self::API_KEY, self::CARD_ID, $targetListId, null)
            ->willReturn($expected);

        $result = $this->tools->moveCard(
            cardId: self::CARD_ID,
            listId: $targetListId,
        );

        $this->assertSame($expected, $result);
    }

    public function testMoveCardWrapsAuthExceptionInToolCallException(): void
    {
        $this->apiKeyProvider
            ->method('getApiKey')
            ->willReturn('bad-key');

        $this->cardService
            ->method('moveCard')
            ->willThrowException(new AuthenticationException('Unauthorized', 401));

        $this->expectException(ToolCallException::class);
        $this->expectExceptionMessage('Unauthorized');

        $this->tools->moveCard(cardId: self::CARD_ID, listId: 'list-done');
    }

    public function testMoveCardWrapsPlankaApiExceptionInToolCallException(): void
    {
        $this->apiKeyProvider
            ->method('getApiKey')
            ->willReturn(self::API_KEY);

        $this->cardService
            ->method('moveCard')
            ->willThrowException(new PlankaApiException('Server error', 500));

        $this->expectException(ToolCallException::class);
        $this->expectExceptionMessage('Server error');

        $this->tools->moveCard(cardId: self::CARD_ID, listId: 'list-done');
    }

    // -------------------------------------------------------------------------
    // deleteCard()
    // -------------------------------------------------------------------------

    public function testDeleteCardSuccess(): void
    {
        $this->apiKeyProvider
            ->expects($this->once())
            ->method('getApiKey')
            ->willReturn(self::API_KEY);

        $this->cardService
            ->expects($this->once())
            ->method('deleteCard')
            ->with(self::API_KEY, self::CARD_ID)
            ->willReturn([]);

        $result = $this->tools->deleteCard(self::CARD_ID);

        $this->assertSame([], $result);
    }

    public function testDeleteCardWrapsAuthExceptionInToolCallException(): void
    {
        $this->apiKeyProvider
            ->method('getApiKey')
            ->willReturn('bad-key');

        $this->cardService
            ->method('deleteCard')
            ->willThrowException(new AuthenticationException('Unauthorized', 401));

        $this->expectException(ToolCallException::class);
        $this->expectExceptionMessage('Unauthorized');

        $this->tools->deleteCard(self::CARD_ID);
    }

    public function testDeleteCardWrapsPlankaApiExceptionInToolCallException(): void
    {
        $this->apiKeyProvider
            ->method('getApiKey')
            ->willReturn(self::API_KEY);

        $this->cardService
            ->method('deleteCard')
            ->willThrowException(new PlankaApiException('Server error', 500));

        $this->expectException(ToolCallException::class);
        $this->expectExceptionMessage('Server error');

        $this->tools->deleteCard(self::CARD_ID);
    }

    public function testDeleteCardWrapsNotFoundExceptionInToolCallException(): void
    {
        $this->apiKeyProvider
            ->method('getApiKey')
            ->willReturn(self::API_KEY);

        $this->cardService
            ->method('deleteCard')
            ->willThrowException(new PlankaNotFoundException('Not found', 404));

        $this->expectException(ToolCallException::class);
        $this->expectExceptionMessage('Not found');

        $this->tools->deleteCard(self::CARD_ID);
    }

    // -------------------------------------------------------------------------
    // duplicateCard()
    // -------------------------------------------------------------------------

    public function testDuplicateCardSuccess(): void
    {
        $expected = ['item' => ['id' => 'card-new', 'name' => 'Sprint Task']];

        $this->apiKeyProvider
            ->expects($this->once())
            ->method('getApiKey')
            ->willReturn(self::API_KEY);

        $this->cardService
            ->expects($this->once())
            ->method('duplicateCard')
            ->with(self::API_KEY, self::CARD_ID)
            ->willReturn($expected);

        $result = $this->tools->duplicateCard(self::CARD_ID);

        $this->assertSame($expected, $result);
    }

    public function testDuplicateCardWrapsAuthExceptionInToolCallException(): void
    {
        $this->apiKeyProvider->method('getApiKey')->willReturn('bad-key');

        $this->cardService
            ->method('duplicateCard')
            ->willThrowException(new AuthenticationException('Unauthorized', 401));

        $this->expectException(ToolCallException::class);
        $this->expectExceptionMessage('Unauthorized');

        $this->tools->duplicateCard(self::CARD_ID);
    }

    public function testDuplicateCardWrapsPlankaApiExceptionInToolCallException(): void
    {
        $this->apiKeyProvider->method('getApiKey')->willReturn(self::API_KEY);

        $this->cardService
            ->method('duplicateCard')
            ->willThrowException(new PlankaApiException('Server error', 500));

        $this->expectException(ToolCallException::class);
        $this->expectExceptionMessage('Server error');

        $this->tools->duplicateCard(self::CARD_ID);
    }

    public function testDuplicateCardWrapsNotFoundExceptionInToolCallException(): void
    {
        $this->apiKeyProvider->method('getApiKey')->willReturn(self::API_KEY);

        $this->cardService
            ->method('duplicateCard')
            ->willThrowException(new PlankaNotFoundException('Not found', 404));

        $this->expectException(ToolCallException::class);
        $this->expectExceptionMessage('Not found');

        $this->tools->duplicateCard(self::CARD_ID);
    }

    public function testDuplicateCardMissingApiKeyThrowsToolCallException(): void
    {
        $this->apiKeyProvider
            ->method('getApiKey')
            ->willThrowException(new ValidationException('Planka API key required.'));

        $this->cardService->expects($this->never())->method('duplicateCard');

        $this->expectException(ToolCallException::class);

        $this->tools->duplicateCard(self::CARD_ID);
    }

    // -------------------------------------------------------------------------
    // manageCardMemberships: add
    // -------------------------------------------------------------------------

    public function testManageCardMembershipsAddSuccess(): void
    {
        $expected = ['item' => ['id' => 'mbr1', 'cardId' => self::CARD_ID, 'userId' => 'user1']];

        $this->apiKeyProvider
            ->expects($this->once())
            ->method('getApiKey')
            ->willReturn(self::API_KEY);

        $this->cardService
            ->expects($this->once())
            ->method('addCardMember')
            ->with(self::API_KEY, self::CARD_ID, 'user1')
            ->willReturn($expected);

        $result = $this->tools->manageCardMemberships('add', self::CARD_ID, 'user1');

        $this->assertSame($expected, $result);
    }

    // -------------------------------------------------------------------------
    // manageCardMemberships: remove
    // -------------------------------------------------------------------------

    public function testManageCardMembershipsRemoveSuccess(): void
    {
        $this->apiKeyProvider
            ->expects($this->once())
            ->method('getApiKey')
            ->willReturn(self::API_KEY);

        $this->cardService
            ->expects($this->once())
            ->method('removeCardMember')
            ->with(self::API_KEY, self::CARD_ID, 'user1')
            ->willReturn([]);

        $result = $this->tools->manageCardMemberships('remove', self::CARD_ID, 'user1');

        $this->assertSame([], $result);
    }

    // -------------------------------------------------------------------------
    // manageCardMemberships: invalid action
    // -------------------------------------------------------------------------

    public function testManageCardMembershipsInvalidActionThrowsToolCallException(): void
    {
        $this->apiKeyProvider->method('getApiKey')->willReturn(self::API_KEY);

        $this->expectException(ToolCallException::class);
        $this->expectExceptionMessage('Invalid action "update". Must be "add" or "remove".');

        $this->tools->manageCardMemberships('update', self::CARD_ID, 'user1');
    }

    // -------------------------------------------------------------------------
    // manageCardMemberships: missing API key + exception wrapping
    // -------------------------------------------------------------------------

    public function testManageCardMembershipsMissingApiKeyThrowsToolCallException(): void
    {
        $this->apiKeyProvider
            ->method('getApiKey')
            ->willThrowException(new ValidationException('Planka API key required.'));

        $this->cardService->expects($this->never())->method('addCardMember');

        $this->expectException(ToolCallException::class);

        $this->tools->manageCardMemberships('add', self::CARD_ID, 'user1');
    }

    public function testManageCardMembershipsWrapsAuthExceptionInToolCallException(): void
    {
        $this->apiKeyProvider->method('getApiKey')->willReturn('bad-key');

        $this->cardService
            ->method('addCardMember')
            ->willThrowException(new AuthenticationException('Unauthorized', 401));

        $this->expectException(ToolCallException::class);
        $this->expectExceptionMessage('Unauthorized');

        $this->tools->manageCardMemberships('add', self::CARD_ID, 'user1');
    }

    public function testManageCardMembershipsWrapsPlankaApiExceptionInToolCallException(): void
    {
        $this->apiKeyProvider->method('getApiKey')->willReturn(self::API_KEY);

        $this->cardService
            ->method('removeCardMember')
            ->willThrowException(new PlankaApiException('Server error', 500));

        $this->expectException(ToolCallException::class);
        $this->expectExceptionMessage('Server error');

        $this->tools->manageCardMemberships('remove', self::CARD_ID, 'user1');
    }

    public function testManageCardMembershipsWrapsNotFoundExceptionInToolCallException(): void
    {
        $this->apiKeyProvider->method('getApiKey')->willReturn(self::API_KEY);

        $this->cardService
            ->method('addCardMember')
            ->willThrowException(new PlankaNotFoundException('Not found', 404));

        $this->expectException(ToolCallException::class);
        $this->expectExceptionMessage('Not found');

        $this->tools->manageCardMemberships('add', self::CARD_ID, 'user1');
    }
}
