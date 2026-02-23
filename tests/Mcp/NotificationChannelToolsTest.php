<?php

declare(strict_types=1);

namespace App\Tests\Mcp;

use App\Domain\NotificationChannel\NotificationChannelService;
use App\Infrastructure\Http\ApiKeyProvider;
use App\Mcp\NotificationChannelTools;
use App\Planka\Exception\AuthenticationException;
use App\Planka\Exception\PlankaApiException;
use App\Planka\Exception\PlankaNotFoundException;
use App\Shared\Exception\ValidationException;
use Mcp\Exception\ToolCallException;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

final class NotificationChannelToolsTest extends TestCase
{
    private NotificationChannelService&MockObject $notificationChannelService;
    private ApiKeyProvider&MockObject $apiKeyProvider;
    private NotificationChannelTools $tools;

    protected function setUp(): void
    {
        $this->notificationChannelService = $this->createMock(NotificationChannelService::class);
        $this->apiKeyProvider = $this->createMock(ApiKeyProvider::class);
        $this->tools = new NotificationChannelTools($this->notificationChannelService, $this->apiKeyProvider);
    }

    // -------------------------------------------------------------------------
    // manageNotificationServices: create_for_user
    // -------------------------------------------------------------------------

    public function testManageNotificationServicesCreateForUserSuccess(): void
    {
        $expected = ['item' => ['id' => 'ns1', 'type' => 'slack']];

        $this->apiKeyProvider
            ->expects($this->once())
            ->method('getApiKey')
            ->willReturn('test-api-key');

        $this->notificationChannelService
            ->expects($this->once())
            ->method('createForUser')
            ->with('test-api-key', 'user1', 'slack', ['url' => 'https://hooks.slack.com/xxx'])
            ->willReturn($expected);

        $result = $this->tools->manageNotificationServices('create_for_user', userId: 'user1', type: 'slack', params: ['url' => 'https://hooks.slack.com/xxx']);

        $this->assertSame($expected, $result);
    }

    public function testManageNotificationServicesCreateForUserMissingUserIdThrowsToolCallException(): void
    {
        $this->apiKeyProvider->method('getApiKey')->willReturn('test-api-key');

        $this->expectException(ToolCallException::class);
        $this->expectExceptionMessage('userId required for create_for_user');

        $this->tools->manageNotificationServices('create_for_user', type: 'slack');
    }

    // -------------------------------------------------------------------------
    // manageNotificationServices: create_for_board
    // -------------------------------------------------------------------------

    public function testManageNotificationServicesCreateForBoardSuccess(): void
    {
        $expected = ['item' => ['id' => 'ns2', 'type' => 'telegram']];

        $this->apiKeyProvider->method('getApiKey')->willReturn('test-api-key');

        $this->notificationChannelService
            ->expects($this->once())
            ->method('createForBoard')
            ->with('test-api-key', 'board1', 'telegram', ['chatId' => '12345'])
            ->willReturn($expected);

        $result = $this->tools->manageNotificationServices('create_for_board', boardId: 'board1', type: 'telegram', params: ['chatId' => '12345']);

        $this->assertSame($expected, $result);
    }

    public function testManageNotificationServicesCreateForBoardMissingBoardIdThrowsToolCallException(): void
    {
        $this->apiKeyProvider->method('getApiKey')->willReturn('test-api-key');

        $this->expectException(ToolCallException::class);
        $this->expectExceptionMessage('boardId required for create_for_board');

        $this->tools->manageNotificationServices('create_for_board', type: 'telegram');
    }

    // -------------------------------------------------------------------------
    // manageNotificationServices: update
    // -------------------------------------------------------------------------

    public function testManageNotificationServicesUpdateSuccess(): void
    {
        $expected = ['item' => ['id' => 'ns1', 'isEnabled' => true]];

        $this->apiKeyProvider->method('getApiKey')->willReturn('test-api-key');

        $this->notificationChannelService
            ->expects($this->once())
            ->method('updateChannel')
            ->with('test-api-key', 'ns1', true, null)
            ->willReturn($expected);

        $result = $this->tools->manageNotificationServices('update', channelId: 'ns1', isEnabled: true);

        $this->assertSame($expected, $result);
    }

    public function testManageNotificationServicesUpdateMissingChannelIdThrowsToolCallException(): void
    {
        $this->apiKeyProvider->method('getApiKey')->willReturn('test-api-key');

        $this->expectException(ToolCallException::class);
        $this->expectExceptionMessage('channelId required for update');

        $this->tools->manageNotificationServices('update', isEnabled: true);
    }

    // -------------------------------------------------------------------------
    // manageNotificationServices: test
    // -------------------------------------------------------------------------

    public function testManageNotificationServicesTestSuccess(): void
    {
        $expected = ['success' => true];

        $this->apiKeyProvider->method('getApiKey')->willReturn('test-api-key');

        $this->notificationChannelService
            ->expects($this->once())
            ->method('testChannel')
            ->with('test-api-key', 'ns1')
            ->willReturn($expected);

        $result = $this->tools->manageNotificationServices('test', channelId: 'ns1');

        $this->assertSame($expected, $result);
    }

    public function testManageNotificationServicesTestMissingChannelIdThrowsToolCallException(): void
    {
        $this->apiKeyProvider->method('getApiKey')->willReturn('test-api-key');

        $this->expectException(ToolCallException::class);
        $this->expectExceptionMessage('channelId required for test');

        $this->tools->manageNotificationServices('test');
    }

    // -------------------------------------------------------------------------
    // manageNotificationServices: delete
    // -------------------------------------------------------------------------

    public function testManageNotificationServicesDeleteSuccess(): void
    {
        $this->apiKeyProvider->method('getApiKey')->willReturn('test-api-key');

        $this->notificationChannelService
            ->expects($this->once())
            ->method('deleteChannel')
            ->with('test-api-key', 'ns1')
            ->willReturn([]);

        $result = $this->tools->manageNotificationServices('delete', channelId: 'ns1');

        $this->assertSame([], $result);
    }

    public function testManageNotificationServicesDeleteMissingChannelIdThrowsToolCallException(): void
    {
        $this->apiKeyProvider->method('getApiKey')->willReturn('test-api-key');

        $this->expectException(ToolCallException::class);
        $this->expectExceptionMessage('channelId required for delete');

        $this->tools->manageNotificationServices('delete');
    }

    // -------------------------------------------------------------------------
    // manageNotificationServices: invalid action
    // -------------------------------------------------------------------------

    public function testManageNotificationServicesInvalidActionThrowsToolCallException(): void
    {
        $this->apiKeyProvider->method('getApiKey')->willReturn('test-api-key');

        $this->expectException(ToolCallException::class);
        $this->expectExceptionMessage('Invalid action "foobar". Must be: create_for_user, create_for_board, update, test, delete');

        $this->tools->manageNotificationServices('foobar');
    }

    // -------------------------------------------------------------------------
    // manageNotificationServices: exception wrapping
    // -------------------------------------------------------------------------

    public function testManageNotificationServicesMissingApiKeyThrowsToolCallException(): void
    {
        $this->apiKeyProvider
            ->method('getApiKey')
            ->willThrowException(new ValidationException('Planka API key required.'));

        $this->expectException(ToolCallException::class);
        $this->expectExceptionMessage('Planka API key required.');

        $this->tools->manageNotificationServices('test', channelId: 'ns1');
    }

    public function testManageNotificationServicesWrapsAuthExceptionInToolCallException(): void
    {
        $this->apiKeyProvider->method('getApiKey')->willReturn('bad-key');

        $this->notificationChannelService
            ->method('testChannel')
            ->willThrowException(new AuthenticationException('Unauthorized'));

        $this->expectException(ToolCallException::class);
        $this->expectExceptionMessage('Unauthorized');

        $this->tools->manageNotificationServices('test', channelId: 'ns1');
    }

    public function testManageNotificationServicesWrapsPlankaApiExceptionInToolCallException(): void
    {
        $this->apiKeyProvider->method('getApiKey')->willReturn('test-api-key');

        $this->notificationChannelService
            ->method('deleteChannel')
            ->willThrowException(new PlankaApiException('Server error'));

        $this->expectException(ToolCallException::class);
        $this->expectExceptionMessage('Server error');

        $this->tools->manageNotificationServices('delete', channelId: 'ns1');
    }

    public function testManageNotificationServicesWrapsNotFoundExceptionInToolCallException(): void
    {
        $this->apiKeyProvider->method('getApiKey')->willReturn('test-api-key');

        $this->notificationChannelService
            ->method('deleteChannel')
            ->willThrowException(new PlankaNotFoundException('Not found', 404));

        $this->expectException(ToolCallException::class);
        $this->expectExceptionMessage('Not found');

        $this->tools->manageNotificationServices('delete', channelId: 'ns1');
    }
}
