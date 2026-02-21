<?php

declare(strict_types=1);

namespace App\Tests\Domain\Card;

use App\Domain\Card\CardService;
use App\Planka\Client\PlankaClient;
use App\Planka\Exception\AuthenticationException;
use App\Planka\Exception\PlankaApiException;
use App\Planka\Exception\PlankaNotFoundException;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

final class CardServiceTest extends TestCase
{
    private const string API_KEY = 'test-api-key';
    private const string CARD_ID = 'card-abc123';
    private const string LIST_ID = 'list-xyz789';

    private PlankaClient&MockObject $plankaClient;
    private CardService $service;

    protected function setUp(): void
    {
        $this->plankaClient = $this->createMock(PlankaClient::class);
        $this->service = new CardService($this->plankaClient);
    }

    // -------------------------------------------------------------------------
    // createCard()
    // -------------------------------------------------------------------------

    public function testCreateCardWithAllParamsBuildsCorrectBody(): void
    {
        $expected = ['item' => ['id' => self::CARD_ID, 'name' => 'My Card']];

        $this->plankaClient
            ->expects($this->once())
            ->method('post')
            ->with(
                self::API_KEY,
                '/api/lists/' . self::LIST_ID . '/cards',
                [
                    'name' => 'My Card',
                    'description' => 'Some description',
                    'dueDate' => '2026-03-01T12:00:00.000Z',
                ],
            )
            ->willReturn($expected);

        $result = $this->service->createCard(
            apiKey: self::API_KEY,
            listId: self::LIST_ID,
            name: 'My Card',
            description: 'Some description',
            dueDate: '2026-03-01T12:00:00.000Z',
        );

        $this->assertSame($expected, $result);
    }

    public function testCreateCardWithOnlyRequiredParamsOmitsOptionalFields(): void
    {
        $expected = ['item' => ['id' => self::CARD_ID, 'name' => 'Minimal Card']];

        $this->plankaClient
            ->expects($this->once())
            ->method('post')
            ->with(
                self::API_KEY,
                '/api/lists/' . self::LIST_ID . '/cards',
                ['name' => 'Minimal Card'],
            )
            ->willReturn($expected);

        $result = $this->service->createCard(
            apiKey: self::API_KEY,
            listId: self::LIST_ID,
            name: 'Minimal Card',
        );

        $this->assertSame($expected, $result);
    }

    public function testCreateCardWithDescriptionOnlyOmitsDueDate(): void
    {
        $expected = ['item' => ['id' => self::CARD_ID, 'name' => 'Card With Desc']];

        $this->plankaClient
            ->expects($this->once())
            ->method('post')
            ->with(
                self::API_KEY,
                '/api/lists/' . self::LIST_ID . '/cards',
                [
                    'name' => 'Card With Desc',
                    'description' => 'A description',
                ],
            )
            ->willReturn($expected);

        $result = $this->service->createCard(
            apiKey: self::API_KEY,
            listId: self::LIST_ID,
            name: 'Card With Desc',
            description: 'A description',
        );

        $this->assertSame($expected, $result);
    }

    public function testCreateCardWithDueDateOnlyOmitsDescription(): void
    {
        $expected = ['item' => ['id' => self::CARD_ID, 'name' => 'Dated Card']];

        $this->plankaClient
            ->expects($this->once())
            ->method('post')
            ->with(
                self::API_KEY,
                '/api/lists/' . self::LIST_ID . '/cards',
                [
                    'name' => 'Dated Card',
                    'dueDate' => '2026-06-15T00:00:00.000Z',
                ],
            )
            ->willReturn($expected);

        $result = $this->service->createCard(
            apiKey: self::API_KEY,
            listId: self::LIST_ID,
            name: 'Dated Card',
            dueDate: '2026-06-15T00:00:00.000Z',
        );

        $this->assertSame($expected, $result);
    }

    public function testCreateCardWithLabelIdsAttachesLabelsAfterCreation(): void
    {
        $cardResponse = ['item' => ['id' => self::CARD_ID, 'name' => 'Card With Labels']];
        $callCount = 0;

        $this->plankaClient
            ->expects($this->exactly(3))
            ->method('post')
            ->willReturnCallback(
                function (string $apiKey, string $path, array $body) use ($cardResponse, &$callCount): array {
                    $callCount++;
                    if ($callCount === 1) {
                        $this->assertSame('/api/lists/' . self::LIST_ID . '/cards', $path);
                        $this->assertSame(['name' => 'Card With Labels'], $body);
                        return $cardResponse;
                    }
                    $this->assertStringStartsWith('/api/cards/' . self::CARD_ID . '/labels', $path);
                    $this->assertArrayHasKey('labelId', $body);
                    return [];
                },
            );

        $result = $this->service->createCard(
            apiKey: self::API_KEY,
            listId: self::LIST_ID,
            name: 'Card With Labels',
            labelIds: ['label-1', 'label-2'],
        );

        $this->assertSame($cardResponse, $result);
    }

    public function testCreateCardWithNullLabelIdsDoesNotAttachLabels(): void
    {
        $expected = ['item' => ['id' => self::CARD_ID, 'name' => 'No Labels']];

        $this->plankaClient
            ->expects($this->once())
            ->method('post')
            ->willReturn($expected);

        $result = $this->service->createCard(
            apiKey: self::API_KEY,
            listId: self::LIST_ID,
            name: 'No Labels',
            labelIds: null,
        );

        $this->assertSame($expected, $result);
    }

    public function testCreateCardWithEmptyLabelIdsDoesNotAttachLabels(): void
    {
        $expected = ['item' => ['id' => self::CARD_ID, 'name' => 'Empty Labels']];

        $this->plankaClient
            ->expects($this->once())
            ->method('post')
            ->willReturn($expected);

        $result = $this->service->createCard(
            apiKey: self::API_KEY,
            listId: self::LIST_ID,
            name: 'Empty Labels',
            labelIds: [],
        );

        $this->assertSame($expected, $result);
    }

    public function testCreateCardPropagatesAuthException(): void
    {
        $this->plankaClient
            ->method('post')
            ->willThrowException(new AuthenticationException('Invalid or missing Planka API key.', 401));

        $this->expectException(AuthenticationException::class);

        $this->service->createCard(self::API_KEY, self::LIST_ID, 'Card');
    }

    public function testCreateCardPropagatesApiException(): void
    {
        $this->plankaClient
            ->method('post')
            ->willThrowException(new PlankaApiException('Planka API returned server error 500', 500));

        $this->expectException(PlankaApiException::class);

        $this->service->createCard(self::API_KEY, self::LIST_ID, 'Card');
    }

    public function testCreateCardPropagatesNotFoundException(): void
    {
        $this->plankaClient
            ->method('post')
            ->willThrowException(
                new PlankaNotFoundException(
                    'Planka resource not found: /api/lists/' . self::LIST_ID . '/cards',
                    404,
                ),
            );

        $this->expectException(PlankaNotFoundException::class);

        $this->service->createCard(self::API_KEY, self::LIST_ID, 'Card');
    }

    // -------------------------------------------------------------------------
    // getCard()
    // -------------------------------------------------------------------------

    public function testGetCardCallsCorrectEndpoint(): void
    {
        $expected = ['item' => ['id' => self::CARD_ID, 'name' => 'Sprint Task']];

        $this->plankaClient
            ->expects($this->once())
            ->method('get')
            ->with(self::API_KEY, '/api/cards/' . self::CARD_ID)
            ->willReturn($expected);

        $result = $this->service->getCard(self::API_KEY, self::CARD_ID);

        $this->assertSame($expected, $result);
    }

    public function testGetCardPropagatesAuthException(): void
    {
        $this->plankaClient
            ->method('get')
            ->willThrowException(new AuthenticationException('Invalid or missing Planka API key.', 401));

        $this->expectException(AuthenticationException::class);

        $this->service->getCard(self::API_KEY, self::CARD_ID);
    }

    public function testGetCardPropagatesApiException(): void
    {
        $this->plankaClient
            ->method('get')
            ->willThrowException(new PlankaApiException('Planka API returned server error 500', 500));

        $this->expectException(PlankaApiException::class);

        $this->service->getCard(self::API_KEY, self::CARD_ID);
    }

    public function testGetCardPropagatesNotFoundException(): void
    {
        $this->plankaClient
            ->method('get')
            ->willThrowException(
                new PlankaNotFoundException(
                    'Planka resource not found: /api/cards/' . self::CARD_ID,
                    404,
                ),
            );

        $this->expectException(PlankaNotFoundException::class);

        $this->service->getCard(self::API_KEY, self::CARD_ID);
    }

    // -------------------------------------------------------------------------
    // updateCard()
    // -------------------------------------------------------------------------

    public function testUpdateCardWithAllParamsBuildsCorrectBody(): void
    {
        $expected = ['item' => ['id' => self::CARD_ID, 'name' => 'Updated']];

        $this->plankaClient
            ->expects($this->once())
            ->method('patch')
            ->with(
                self::API_KEY,
                '/api/cards/' . self::CARD_ID,
                [
                    'name' => 'Updated',
                    'description' => 'New description',
                    'dueDate' => '2026-04-01T09:00:00.000Z',
                    'isCompleted' => true,
                ],
            )
            ->willReturn($expected);

        $result = $this->service->updateCard(
            apiKey: self::API_KEY,
            cardId: self::CARD_ID,
            name: 'Updated',
            description: 'New description',
            dueDate: '2026-04-01T09:00:00.000Z',
            isCompleted: true,
        );

        $this->assertSame($expected, $result);
    }

    public function testUpdateCardWithPartialParamsOnlyIncludesNonNullFields(): void
    {
        $expected = ['item' => ['id' => self::CARD_ID, 'name' => 'Partial Update']];

        $this->plankaClient
            ->expects($this->once())
            ->method('patch')
            ->with(
                self::API_KEY,
                '/api/cards/' . self::CARD_ID,
                ['name' => 'Partial Update'],
            )
            ->willReturn($expected);

        $result = $this->service->updateCard(
            apiKey: self::API_KEY,
            cardId: self::CARD_ID,
            name: 'Partial Update',
        );

        $this->assertSame($expected, $result);
    }

    public function testUpdateCardIsCompletedFalseIsIncludedInBody(): void
    {
        $expected = ['item' => ['id' => self::CARD_ID, 'isCompleted' => false]];

        $this->plankaClient
            ->expects($this->once())
            ->method('patch')
            ->with(
                self::API_KEY,
                '/api/cards/' . self::CARD_ID,
                ['isCompleted' => false],
            )
            ->willReturn($expected);

        $result = $this->service->updateCard(
            apiKey: self::API_KEY,
            cardId: self::CARD_ID,
            isCompleted: false,
        );

        $this->assertSame($expected, $result);
    }

    public function testUpdateCardWithDescriptionAndDueDateOmitsOtherFields(): void
    {
        $expected = ['item' => ['id' => self::CARD_ID]];

        $this->plankaClient
            ->expects($this->once())
            ->method('patch')
            ->with(
                self::API_KEY,
                '/api/cards/' . self::CARD_ID,
                [
                    'description' => 'Updated desc',
                    'dueDate' => '2026-05-10T00:00:00.000Z',
                ],
            )
            ->willReturn($expected);

        $result = $this->service->updateCard(
            apiKey: self::API_KEY,
            cardId: self::CARD_ID,
            description: 'Updated desc',
            dueDate: '2026-05-10T00:00:00.000Z',
        );

        $this->assertSame($expected, $result);
    }

    public function testUpdateCardClearsDescriptionWhenEmptyStringPassed(): void
    {
        $expected = ['item' => ['id' => self::CARD_ID]];

        $this->plankaClient
            ->expects($this->once())
            ->method('patch')
            ->with(
                self::API_KEY,
                '/api/cards/' . self::CARD_ID,
                ['description' => null],
            )
            ->willReturn($expected);

        $result = $this->service->updateCard(
            apiKey: self::API_KEY,
            cardId: self::CARD_ID,
            description: '',
        );

        $this->assertSame($expected, $result);
    }

    public function testUpdateCardClearsDueDateWhenEmptyStringPassed(): void
    {
        $expected = ['item' => ['id' => self::CARD_ID]];

        $this->plankaClient
            ->expects($this->once())
            ->method('patch')
            ->with(
                self::API_KEY,
                '/api/cards/' . self::CARD_ID,
                ['dueDate' => null],
            )
            ->willReturn($expected);

        $result = $this->service->updateCard(
            apiKey: self::API_KEY,
            cardId: self::CARD_ID,
            dueDate: '',
        );

        $this->assertSame($expected, $result);
    }

    public function testUpdateCardOmitsDescriptionWhenNullPassed(): void
    {
        $expected = ['item' => ['id' => self::CARD_ID, 'name' => 'Updated']];

        $this->plankaClient
            ->expects($this->once())
            ->method('patch')
            ->with(
                self::API_KEY,
                '/api/cards/' . self::CARD_ID,
                ['name' => 'Updated'],
            )
            ->willReturn($expected);

        $result = $this->service->updateCard(
            apiKey: self::API_KEY,
            cardId: self::CARD_ID,
            name: 'Updated',
            description: null,
        );

        $this->assertSame($expected, $result);
    }

    public function testUpdateCardOmitsDueDateWhenNullPassed(): void
    {
        $expected = ['item' => ['id' => self::CARD_ID, 'name' => 'Updated']];

        $this->plankaClient
            ->expects($this->once())
            ->method('patch')
            ->with(
                self::API_KEY,
                '/api/cards/' . self::CARD_ID,
                ['name' => 'Updated'],
            )
            ->willReturn($expected);

        $result = $this->service->updateCard(
            apiKey: self::API_KEY,
            cardId: self::CARD_ID,
            name: 'Updated',
            dueDate: null,
        );

        $this->assertSame($expected, $result);
    }

    public function testUpdateCardPropagatesAuthException(): void
    {
        $this->plankaClient
            ->method('patch')
            ->willThrowException(new AuthenticationException('Invalid or missing Planka API key.', 401));

        $this->expectException(AuthenticationException::class);

        $this->service->updateCard(self::API_KEY, self::CARD_ID, name: 'X');
    }

    public function testUpdateCardPropagatesApiException(): void
    {
        $this->plankaClient
            ->method('patch')
            ->willThrowException(new PlankaApiException('Planka API returned server error 500', 500));

        $this->expectException(PlankaApiException::class);

        $this->service->updateCard(self::API_KEY, self::CARD_ID, name: 'X');
    }

    public function testUpdateCardPropagatesNotFoundException(): void
    {
        $this->plankaClient
            ->method('patch')
            ->willThrowException(
                new PlankaNotFoundException(
                    'Planka resource not found: /api/cards/' . self::CARD_ID,
                    404,
                ),
            );

        $this->expectException(PlankaNotFoundException::class);

        $this->service->updateCard(self::API_KEY, self::CARD_ID, name: 'X');
    }

    // -------------------------------------------------------------------------
    // moveCard()
    // -------------------------------------------------------------------------

    public function testMoveCardWithPositionBuildsCorrectBody(): void
    {
        $expected = ['item' => ['id' => self::CARD_ID, 'listId' => 'list-new']];

        $this->plankaClient
            ->expects($this->once())
            ->method('patch')
            ->with(
                self::API_KEY,
                '/api/cards/' . self::CARD_ID,
                [
                    'listId' => 'list-new',
                    'position' => 65536,
                ],
            )
            ->willReturn($expected);

        $result = $this->service->moveCard(
            apiKey: self::API_KEY,
            cardId: self::CARD_ID,
            listId: 'list-new',
            position: 65536,
        );

        $this->assertSame($expected, $result);
    }

    public function testMoveCardWithoutPositionOmitsPositionField(): void
    {
        $expected = ['item' => ['id' => self::CARD_ID, 'listId' => 'list-new']];

        $this->plankaClient
            ->expects($this->once())
            ->method('patch')
            ->with(
                self::API_KEY,
                '/api/cards/' . self::CARD_ID,
                ['listId' => 'list-new'],
            )
            ->willReturn($expected);

        $result = $this->service->moveCard(
            apiKey: self::API_KEY,
            cardId: self::CARD_ID,
            listId: 'list-new',
        );

        $this->assertSame($expected, $result);
    }

    public function testMoveCardPropagatesAuthException(): void
    {
        $this->plankaClient
            ->method('patch')
            ->willThrowException(new AuthenticationException('Invalid or missing Planka API key.', 401));

        $this->expectException(AuthenticationException::class);

        $this->service->moveCard(self::API_KEY, self::CARD_ID, 'list-new');
    }

    public function testMoveCardPropagatesApiException(): void
    {
        $this->plankaClient
            ->method('patch')
            ->willThrowException(new PlankaApiException('Planka API returned server error 500', 500));

        $this->expectException(PlankaApiException::class);

        $this->service->moveCard(self::API_KEY, self::CARD_ID, 'list-new');
    }

    public function testMoveCardPropagatesNotFoundException(): void
    {
        $this->plankaClient
            ->method('patch')
            ->willThrowException(
                new PlankaNotFoundException(
                    'Planka resource not found: /api/cards/' . self::CARD_ID,
                    404,
                ),
            );

        $this->expectException(PlankaNotFoundException::class);

        $this->service->moveCard(self::API_KEY, self::CARD_ID, 'list-new');
    }

    // -------------------------------------------------------------------------
    // deleteCard()
    // -------------------------------------------------------------------------

    public function testDeleteCardCallsDeleteOnCorrectEndpoint(): void
    {
        $this->plankaClient
            ->expects($this->once())
            ->method('delete')
            ->with(self::API_KEY, '/api/cards/' . self::CARD_ID)
            ->willReturn([]);

        $result = $this->service->deleteCard(self::API_KEY, self::CARD_ID);

        $this->assertSame([], $result);
    }

    public function testDeleteCardPropagatesAuthException(): void
    {
        $this->plankaClient
            ->method('delete')
            ->willThrowException(new AuthenticationException('Invalid or missing Planka API key.', 401));

        $this->expectException(AuthenticationException::class);

        $this->service->deleteCard(self::API_KEY, self::CARD_ID);
    }

    public function testDeleteCardPropagatesApiException(): void
    {
        $this->plankaClient
            ->method('delete')
            ->willThrowException(new PlankaApiException('Planka API returned server error 500', 500));

        $this->expectException(PlankaApiException::class);

        $this->service->deleteCard(self::API_KEY, self::CARD_ID);
    }

    public function testDeleteCardPropagatesNotFoundException(): void
    {
        $this->plankaClient
            ->method('delete')
            ->willThrowException(
                new PlankaNotFoundException(
                    'Planka resource not found: /api/cards/' . self::CARD_ID,
                    404,
                ),
            );

        $this->expectException(PlankaNotFoundException::class);

        $this->service->deleteCard(self::API_KEY, self::CARD_ID);
    }
}
