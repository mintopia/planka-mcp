<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\Label;

use App\Domain\Label\LabelService;
use App\Exception\ValidationException;
use App\Planka\Exception\AuthenticationException;
use App\Planka\Exception\PlankaApiException;
use App\Planka\PlankaClientInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

final class LabelServiceTest extends TestCase
{
    private PlankaClientInterface&MockObject $plankaClient;
    private LabelService $service;

    protected function setUp(): void
    {
        $this->plankaClient = $this->createMock(PlankaClientInterface::class);
        $this->service = new LabelService($this->plankaClient);
    }

    public function testManageLabelCreateSuccess(): void
    {
        $expected = ['item' => ['id' => 'label1', 'name' => 'Bug']];

        $this->plankaClient
            ->expects($this->once())
            ->method('post')
            ->with('test-api-key', '/api/boards/board1/labels', ['position' => 65536, 'name' => 'Bug', 'color' => 'berry-red'])
            ->willReturn($expected);

        $this->assertSame($expected, $this->service->manageLabel('test-api-key', 'create', 'board1', null, 'Bug', 'berry-red'));
    }

    public function testManageLabelCreateWithoutOptionalFields(): void
    {
        $expected = ['item' => ['id' => 'label1']];

        $this->plankaClient
            ->expects($this->once())
            ->method('post')
            ->with('test-api-key', '/api/boards/board1/labels', ['position' => 65536])
            ->willReturn($expected);

        $this->assertSame($expected, $this->service->manageLabel('test-api-key', 'create', 'board1'));
    }

    public function testManageLabelCreateRequiresBoardId(): void
    {
        $this->expectException(ValidationException::class);
        $this->service->manageLabel('test-api-key', 'create', null, null, 'Bug');
    }

    public function testManageLabelUpdateSuccess(): void
    {
        $expected = ['item' => ['id' => 'label1', 'name' => 'Updated', 'color' => 'sky-blue']];

        $this->plankaClient
            ->expects($this->once())
            ->method('patch')
            ->with('test-api-key', '/api/labels/label1', ['name' => 'Updated', 'color' => 'sky-blue'])
            ->willReturn($expected);

        $this->assertSame($expected, $this->service->manageLabel('test-api-key', 'update', null, 'label1', 'Updated', 'sky-blue'));
    }

    public function testManageLabelUpdateRequiresLabelId(): void
    {
        $this->expectException(ValidationException::class);
        $this->service->manageLabel('test-api-key', 'update', null, null, 'Name');
    }

    public function testManageLabelDeleteSuccess(): void
    {
        $this->plankaClient
            ->expects($this->once())
            ->method('delete')
            ->with('test-api-key', '/api/labels/label1')
            ->willReturn([]);

        $this->assertSame([], $this->service->manageLabel('test-api-key', 'delete', null, 'label1'));
    }

    public function testManageLabelDeleteRequiresLabelId(): void
    {
        $this->expectException(ValidationException::class);
        $this->service->manageLabel('test-api-key', 'delete');
    }

    public function testManageLabelInvalidAction(): void
    {
        $this->expectException(ValidationException::class);
        $this->service->manageLabel('test-api-key', 'invalid');
    }

    public function testManageLabelPropagatesAuthException(): void
    {
        $this->plankaClient->method('post')->willThrowException(new AuthenticationException('Unauthorized'));
        $this->expectException(AuthenticationException::class);
        $this->service->manageLabel('bad-key', 'create', 'board1', null, 'Bug');
    }

    public function testManageLabelPropagatesApiException(): void
    {
        $this->plankaClient->method('post')->willThrowException(new PlankaApiException('Server error'));
        $this->expectException(PlankaApiException::class);
        $this->service->manageLabel('test-api-key', 'create', 'board1', null, 'Bug');
    }

    public function testSetCardLabelsAddSuccess(): void
    {
        $expected = ['item' => ['id' => 'cl1']];

        $this->plankaClient
            ->expects($this->exactly(2))
            ->method('post')
            ->willReturn($expected);

        $result = $this->service->setCardLabels('test-api-key', 'card1', ['label1', 'label2']);
        $this->assertArrayHasKey('added', $result);
        $this->assertCount(2, $result['added']);
    }

    public function testSetCardLabelsRemoveSuccess(): void
    {
        $this->plankaClient
            ->expects($this->exactly(2))
            ->method('delete')
            ->willReturn([]);

        $result = $this->service->setCardLabels('test-api-key', 'card1', [], ['label1', 'label2']);
        $this->assertArrayHasKey('removed', $result);
        $this->assertCount(2, $result['removed']);
    }

    public function testSetCardLabelsAddAndRemove(): void
    {
        $this->plankaClient
            ->expects($this->once())
            ->method('post')
            ->willReturn(['item' => ['id' => 'cl1']]);
        $this->plankaClient
            ->expects($this->once())
            ->method('delete')
            ->willReturn([]);

        $result = $this->service->setCardLabels('test-api-key', 'card1', ['label1'], ['label2']);
        $this->assertArrayHasKey('added', $result);
        $this->assertArrayHasKey('removed', $result);
    }

    public function testSetCardLabelsEmptyReturnsEmptyArray(): void
    {
        $result = $this->service->setCardLabels('test-api-key', 'card1');
        $this->assertSame([], $result);
    }
}
