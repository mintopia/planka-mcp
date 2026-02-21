<?php

declare(strict_types=1);

namespace App\Tests\Domain\Label;

use App\Domain\Label\LabelService;
use App\Planka\Client\PlankaClient;
use App\Planka\Exception\AuthenticationException;
use App\Planka\Exception\PlankaApiException;
use App\Shared\Exception\ValidationException;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

final class LabelServiceTest extends TestCase
{
    private PlankaClient&MockObject $plankaClient;
    private LabelService $service;

    protected function setUp(): void
    {
        $this->plankaClient = $this->createMock(PlankaClient::class);
        $this->service = new LabelService($this->plankaClient);
    }

    // --- manageLabel: create ---

    public function testManageLabelCreateSuccess(): void
    {
        $expected = ['item' => ['id' => 'label1', 'name' => 'Bug', 'color' => 'red']];

        $this->plankaClient
            ->expects($this->once())
            ->method('post')
            ->with('test-api-key', '/api/boards/board1/labels', ['name' => 'Bug', 'color' => 'red'])
            ->willReturn($expected);

        $result = $this->service->manageLabel(
            apiKey: 'test-api-key',
            action: 'create',
            boardId: 'board1',
            name: 'Bug',
            color: 'red',
        );

        $this->assertSame($expected, $result);
    }

    public function testManageLabelCreateWithNoOptionalFields(): void
    {
        $expected = ['item' => ['id' => 'label1']];

        $this->plankaClient
            ->expects($this->once())
            ->method('post')
            ->with('test-api-key', '/api/boards/board1/labels', [])
            ->willReturn($expected);

        $result = $this->service->manageLabel(
            apiKey: 'test-api-key',
            action: 'create',
            boardId: 'board1',
        );

        $this->assertSame($expected, $result);
    }

    public function testManageLabelCreateWithoutBoardIdThrowsValidationException(): void
    {
        $this->plankaClient->expects($this->never())->method('post');

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('boardId required for create');

        $this->service->manageLabel(
            apiKey: 'test-api-key',
            action: 'create',
        );
    }

    // --- manageLabel: update ---

    public function testManageLabelUpdateSuccess(): void
    {
        $expected = ['item' => ['id' => 'label1', 'name' => 'Feature', 'color' => 'green']];

        $this->plankaClient
            ->expects($this->once())
            ->method('patch')
            ->with('test-api-key', '/api/labels/label1', ['name' => 'Feature', 'color' => 'green'])
            ->willReturn($expected);

        $result = $this->service->manageLabel(
            apiKey: 'test-api-key',
            action: 'update',
            labelId: 'label1',
            name: 'Feature',
            color: 'green',
        );

        $this->assertSame($expected, $result);
    }

    public function testManageLabelUpdateWithoutLabelIdThrowsValidationException(): void
    {
        $this->plankaClient->expects($this->never())->method('patch');

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('labelId required for update');

        $this->service->manageLabel(
            apiKey: 'test-api-key',
            action: 'update',
            name: 'Feature',
        );
    }

    // --- manageLabel: delete ---

    public function testManageLabelDeleteSuccess(): void
    {
        $this->plankaClient
            ->expects($this->once())
            ->method('delete')
            ->with('test-api-key', '/api/labels/label1')
            ->willReturn([]);

        $result = $this->service->manageLabel(
            apiKey: 'test-api-key',
            action: 'delete',
            labelId: 'label1',
        );

        $this->assertSame([], $result);
    }

    public function testManageLabelDeleteWithoutLabelIdThrowsValidationException(): void
    {
        $this->plankaClient->expects($this->never())->method('delete');

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('labelId required for delete');

        $this->service->manageLabel(
            apiKey: 'test-api-key',
            action: 'delete',
        );
    }

    // --- manageLabel: invalid action ---

    public function testManageLabelInvalidActionThrowsValidationException(): void
    {
        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('Invalid action "foobar". Must be: create, update, delete');

        $this->service->manageLabel(
            apiKey: 'test-api-key',
            action: 'foobar',
        );
    }

    // --- manageLabel: exception propagation ---

    public function testManageLabelCreatePropagatesAuthException(): void
    {
        $this->plankaClient
            ->method('post')
            ->willThrowException(new AuthenticationException('Unauthorized'));

        $this->expectException(AuthenticationException::class);

        $this->service->manageLabel(
            apiKey: 'bad-key',
            action: 'create',
            boardId: 'board1',
        );
    }

    public function testManageLabelUpdatePropagatesApiException(): void
    {
        $this->plankaClient
            ->method('patch')
            ->willThrowException(new PlankaApiException('Server error'));

        $this->expectException(PlankaApiException::class);

        $this->service->manageLabel(
            apiKey: 'test-api-key',
            action: 'update',
            labelId: 'label1',
        );
    }

    public function testManageLabelDeletePropagatesApiException(): void
    {
        $this->plankaClient
            ->method('delete')
            ->willThrowException(new PlankaApiException('Server error'));

        $this->expectException(PlankaApiException::class);

        $this->service->manageLabel(
            apiKey: 'test-api-key',
            action: 'delete',
            labelId: 'label1',
        );
    }

    // --- setCardLabels ---

    public function testSetCardLabelsAddAndRemove(): void
    {
        $addResponse = ['item' => ['id' => 'cardLabel1']];
        $removeResponse = [];

        $this->plankaClient
            ->expects($this->once())
            ->method('post')
            ->with('test-api-key', '/api/cards/card1/labels', ['labelId' => 'label1'])
            ->willReturn($addResponse);

        $this->plankaClient
            ->expects($this->once())
            ->method('delete')
            ->with('test-api-key', '/api/cards/card1/labels/label2')
            ->willReturn($removeResponse);

        $result = $this->service->setCardLabels(
            apiKey: 'test-api-key',
            cardId: 'card1',
            addLabelIds: ['label1'],
            removeLabelIds: ['label2'],
        );

        $this->assertSame(['added' => [$addResponse], 'removed' => [$removeResponse]], $result);
    }

    public function testSetCardLabelsAddMultiple(): void
    {
        $response1 = ['item' => ['id' => 'cardLabel1']];
        $response2 = ['item' => ['id' => 'cardLabel2']];

        $this->plankaClient
            ->expects($this->exactly(2))
            ->method('post')
            ->willReturnOnConsecutiveCalls($response1, $response2);

        $this->plankaClient->expects($this->never())->method('delete');

        $result = $this->service->setCardLabels(
            apiKey: 'test-api-key',
            cardId: 'card1',
            addLabelIds: ['label1', 'label2'],
        );

        $this->assertSame(['added' => [$response1, $response2]], $result);
    }

    public function testSetCardLabelsEmptyArraysReturnsEmptyResult(): void
    {
        $this->plankaClient->expects($this->never())->method('post');
        $this->plankaClient->expects($this->never())->method('delete');

        $result = $this->service->setCardLabels(
            apiKey: 'test-api-key',
            cardId: 'card1',
        );

        $this->assertSame([], $result);
    }

    public function testSetCardLabelsPropagatesAuthException(): void
    {
        $this->plankaClient
            ->method('post')
            ->willThrowException(new AuthenticationException('Unauthorized'));

        $this->expectException(AuthenticationException::class);

        $this->service->setCardLabels(
            apiKey: 'bad-key',
            cardId: 'card1',
            addLabelIds: ['label1'],
        );
    }

    public function testSetCardLabelsPropagatesApiException(): void
    {
        $this->plankaClient
            ->method('delete')
            ->willThrowException(new PlankaApiException('Server error'));

        $this->expectException(PlankaApiException::class);

        $this->service->setCardLabels(
            apiKey: 'test-api-key',
            cardId: 'card1',
            removeLabelIds: ['label1'],
        );
    }
}
