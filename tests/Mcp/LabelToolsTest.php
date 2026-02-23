<?php

declare(strict_types=1);

namespace App\Tests\Mcp;

use App\Domain\Label\LabelService;
use App\Infrastructure\Http\ApiKeyProvider;
use App\Mcp\LabelTools;
use App\Planka\Exception\AuthenticationException;
use App\Planka\Exception\PlankaApiException;
use App\Planka\Exception\PlankaNotFoundException;
use App\Shared\Exception\ValidationException;
use Mcp\Exception\ToolCallException;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

final class LabelToolsTest extends TestCase
{
    private LabelService&MockObject $labelService;
    private ApiKeyProvider&MockObject $apiKeyProvider;
    private LabelTools $tools;

    protected function setUp(): void
    {
        $this->labelService = $this->createMock(LabelService::class);
        $this->apiKeyProvider = $this->createMock(ApiKeyProvider::class);
        $this->tools = new LabelTools($this->labelService, $this->apiKeyProvider);
    }

    // --- manageLabels: create ---

    public function testManageLabelsCreateSuccess(): void
    {
        $expected = ['item' => ['id' => 'label1', 'name' => 'Bug', 'color' => 'berry-red']];

        $this->apiKeyProvider
            ->expects($this->once())
            ->method('getApiKey')
            ->willReturn('test-api-key');

        $this->labelService
            ->expects($this->once())
            ->method('manageLabel')
            ->with('test-api-key', 'create', 'board1', null, 'Bug', 'berry-red')
            ->willReturn($expected);

        $result = $this->tools->manageLabels('create', 'board1', null, 'Bug', 'berry-red');

        $this->assertSame($expected, $result);
    }

    // --- manageLabels: update ---

    public function testManageLabelsUpdateSuccess(): void
    {
        $expected = ['item' => ['id' => 'label1', 'name' => 'Feature', 'color' => 'morning-sky']];

        $this->apiKeyProvider
            ->expects($this->once())
            ->method('getApiKey')
            ->willReturn('test-api-key');

        $this->labelService
            ->expects($this->once())
            ->method('manageLabel')
            ->with('test-api-key', 'update', null, 'label1', 'Feature', 'morning-sky')
            ->willReturn($expected);

        $result = $this->tools->manageLabels('update', null, 'label1', 'Feature', 'morning-sky');

        $this->assertSame($expected, $result);
    }

    // --- manageLabels: delete ---

    public function testManageLabelsDeleteSuccess(): void
    {
        $this->apiKeyProvider
            ->expects($this->once())
            ->method('getApiKey')
            ->willReturn('test-api-key');

        $this->labelService
            ->expects($this->once())
            ->method('manageLabel')
            ->with('test-api-key', 'delete', null, 'label1', null, null)
            ->willReturn([]);

        $result = $this->tools->manageLabels('delete', null, 'label1');

        $this->assertSame([], $result);
    }

    // --- manageLabels: missing API key ---

    public function testManageLabelsMissingApiKeyThrowsToolCallException(): void
    {
        $this->apiKeyProvider
            ->expects($this->once())
            ->method('getApiKey')
            ->willThrowException(new ValidationException('Planka API key required.'));

        $this->labelService->expects($this->never())->method('manageLabel');

        $this->expectException(ToolCallException::class);
        $this->expectExceptionMessage('Planka API key required.');

        $this->tools->manageLabels('create', 'board1');
    }

    // --- manageLabels: exception wrapping ---

    public function testManageLabelsWrapsAuthExceptionInToolCallException(): void
    {
        $this->apiKeyProvider->method('getApiKey')->willReturn('bad-key');

        $this->labelService
            ->method('manageLabel')
            ->willThrowException(new AuthenticationException('Unauthorized'));

        $this->expectException(ToolCallException::class);
        $this->expectExceptionMessage('Unauthorized');

        $this->tools->manageLabels('create', 'board1');
    }

    public function testManageLabelsWrapsPlankaApiExceptionInToolCallException(): void
    {
        $this->apiKeyProvider->method('getApiKey')->willReturn('test-api-key');

        $this->labelService
            ->method('manageLabel')
            ->willThrowException(new PlankaApiException('Server error'));

        $this->expectException(ToolCallException::class);
        $this->expectExceptionMessage('Server error');

        $this->tools->manageLabels('update', null, 'label1');
    }

    public function testManageLabelsWrapsNotFoundExceptionInToolCallException(): void
    {
        $this->apiKeyProvider->method('getApiKey')->willReturn('test-api-key');

        $this->labelService
            ->method('manageLabel')
            ->willThrowException(new PlankaNotFoundException('Not found', 404));

        $this->expectException(ToolCallException::class);
        $this->expectExceptionMessage('Not found');

        $this->tools->manageLabels('update', null, 'label1');
    }

    // --- setCardLabels ---

    public function testSetCardLabelsSuccessWithAddAndRemove(): void
    {
        $expected = [
            'added' => [['item' => ['id' => 'cardLabel1']]],
            'removed' => [[]],
        ];

        $this->apiKeyProvider
            ->expects($this->once())
            ->method('getApiKey')
            ->willReturn('test-api-key');

        $this->labelService
            ->expects($this->once())
            ->method('setCardLabels')
            ->with('test-api-key', 'card1', ['label1'], ['label2'])
            ->willReturn($expected);

        $result = $this->tools->setCardLabels('card1', ['label1'], ['label2']);

        $this->assertSame($expected, $result);
    }

    public function testSetCardLabelsWithNullsDefaultsToEmptyArrays(): void
    {
        $this->apiKeyProvider->method('getApiKey')->willReturn('test-api-key');

        $this->labelService
            ->expects($this->once())
            ->method('setCardLabels')
            ->with('test-api-key', 'card1', [], [])
            ->willReturn([]);

        $result = $this->tools->setCardLabels('card1');

        $this->assertSame([], $result);
    }

    public function testSetCardLabelsMissingApiKeyThrowsToolCallException(): void
    {
        $this->apiKeyProvider
            ->expects($this->once())
            ->method('getApiKey')
            ->willThrowException(new ValidationException('Planka API key required.'));

        $this->labelService->expects($this->never())->method('setCardLabels');

        $this->expectException(ToolCallException::class);

        $this->tools->setCardLabels('card1', ['label1']);
    }

    public function testSetCardLabelsWrapsAuthExceptionInToolCallException(): void
    {
        $this->apiKeyProvider->method('getApiKey')->willReturn('bad-key');

        $this->labelService
            ->method('setCardLabels')
            ->willThrowException(new AuthenticationException('Unauthorized'));

        $this->expectException(ToolCallException::class);
        $this->expectExceptionMessage('Unauthorized');

        $this->tools->setCardLabels('card1', ['label1']);
    }

    public function testSetCardLabelsWrapsPlankaApiExceptionInToolCallException(): void
    {
        $this->apiKeyProvider->method('getApiKey')->willReturn('test-api-key');

        $this->labelService
            ->method('setCardLabels')
            ->willThrowException(new PlankaApiException('Server error'));

        $this->expectException(ToolCallException::class);
        $this->expectExceptionMessage('Server error');

        $this->tools->setCardLabels('card1', null, ['label2']);
    }
}
