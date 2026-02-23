<?php

declare(strict_types=1);

namespace App\Tests\Mcp;

use App\Domain\User\UserService;
use App\Infrastructure\Http\ApiKeyProvider;
use App\Mcp\UserTools;
use App\Planka\Exception\AuthenticationException;
use App\Planka\Exception\PlankaApiException;
use App\Planka\Exception\PlankaNotFoundException;
use App\Shared\Exception\ValidationException;
use Mcp\Exception\ToolCallException;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

final class UserToolsTest extends TestCase
{
    private UserService&MockObject $userService;
    private ApiKeyProvider&MockObject $apiKeyProvider;
    private UserTools $tools;

    protected function setUp(): void
    {
        $this->userService = $this->createMock(UserService::class);
        $this->apiKeyProvider = $this->createMock(ApiKeyProvider::class);
        $this->tools = new UserTools($this->userService, $this->apiKeyProvider);
    }

    // -------------------------------------------------------------------------
    // manageUsers: list
    // -------------------------------------------------------------------------

    public function testManageUsersListSuccess(): void
    {
        $expected = ['items' => [['id' => 'user1', 'username' => 'alice']]];

        $this->apiKeyProvider
            ->expects($this->once())
            ->method('getApiKey')
            ->willReturn('test-api-key');

        $this->userService
            ->expects($this->once())
            ->method('getUsers')
            ->with('test-api-key')
            ->willReturn($expected);

        $result = $this->tools->manageUsers('list');

        $this->assertSame($expected, $result);
    }

    // -------------------------------------------------------------------------
    // manageUsers: get
    // -------------------------------------------------------------------------

    public function testManageUsersGetSuccess(): void
    {
        $expected = ['item' => ['id' => 'user1', 'username' => 'alice']];

        $this->apiKeyProvider
            ->expects($this->once())
            ->method('getApiKey')
            ->willReturn('test-api-key');

        $this->userService
            ->expects($this->once())
            ->method('getUser')
            ->with('test-api-key', 'user1')
            ->willReturn($expected);

        $result = $this->tools->manageUsers('get', 'user1');

        $this->assertSame($expected, $result);
    }

    public function testManageUsersGetWithoutUserIdThrowsToolCallException(): void
    {
        $this->apiKeyProvider->method('getApiKey')->willReturn('test-api-key');
        $this->userService->expects($this->never())->method('getUser');

        $this->expectException(ToolCallException::class);
        $this->expectExceptionMessage('userId required for get');

        $this->tools->manageUsers('get');
    }

    // -------------------------------------------------------------------------
    // manageUsers: create
    // -------------------------------------------------------------------------

    public function testManageUsersCreateSuccess(): void
    {
        $expected = ['item' => ['id' => 'user1', 'username' => 'alice']];

        $this->apiKeyProvider
            ->expects($this->once())
            ->method('getApiKey')
            ->willReturn('test-api-key');

        $this->userService
            ->expects($this->once())
            ->method('createUser')
            ->with('test-api-key', 'alice', 'alice@example.com', 'secret', 'Alice Smith')
            ->willReturn($expected);

        $result = $this->tools->manageUsers('create', null, 'alice', 'alice@example.com', 'secret', 'Alice Smith');

        $this->assertSame($expected, $result);
    }

    public function testManageUsersCreateWithoutUsernameThrowsToolCallException(): void
    {
        $this->apiKeyProvider->method('getApiKey')->willReturn('test-api-key');
        $this->userService->expects($this->never())->method('createUser');

        $this->expectException(ToolCallException::class);
        $this->expectExceptionMessage('username required for create');

        $this->tools->manageUsers('create', null, null, 'email@example.com', 'pass');
    }

    public function testManageUsersCreateWithEmptyUsernameThrowsToolCallException(): void
    {
        $this->apiKeyProvider->method('getApiKey')->willReturn('test-api-key');
        $this->userService->expects($this->never())->method('createUser');

        $this->expectException(ToolCallException::class);
        $this->expectExceptionMessage('username required for create');

        $this->tools->manageUsers('create', null, '   ', 'email@example.com', 'pass');
    }

    public function testManageUsersCreateWithoutEmailThrowsToolCallException(): void
    {
        $this->apiKeyProvider->method('getApiKey')->willReturn('test-api-key');
        $this->userService->expects($this->never())->method('createUser');

        $this->expectException(ToolCallException::class);
        $this->expectExceptionMessage('email required for create');

        $this->tools->manageUsers('create', null, 'alice', null, 'pass');
    }

    public function testManageUsersCreateWithEmptyEmailThrowsToolCallException(): void
    {
        $this->apiKeyProvider->method('getApiKey')->willReturn('test-api-key');
        $this->userService->expects($this->never())->method('createUser');

        $this->expectException(ToolCallException::class);
        $this->expectExceptionMessage('email required for create');

        $this->tools->manageUsers('create', null, 'alice', '  ', 'pass');
    }

    public function testManageUsersCreateWithoutPasswordThrowsToolCallException(): void
    {
        $this->apiKeyProvider->method('getApiKey')->willReturn('test-api-key');
        $this->userService->expects($this->never())->method('createUser');

        $this->expectException(ToolCallException::class);
        $this->expectExceptionMessage('password required for create');

        $this->tools->manageUsers('create', null, 'alice', 'alice@example.com', null);
    }

    public function testManageUsersCreateWithEmptyPasswordThrowsToolCallException(): void
    {
        $this->apiKeyProvider->method('getApiKey')->willReturn('test-api-key');
        $this->userService->expects($this->never())->method('createUser');

        $this->expectException(ToolCallException::class);
        $this->expectExceptionMessage('password required for create');

        $this->tools->manageUsers('create', null, 'alice', 'alice@example.com', '');
    }

    // -------------------------------------------------------------------------
    // manageUsers: update
    // -------------------------------------------------------------------------

    public function testManageUsersUpdateSuccess(): void
    {
        $expected = ['item' => ['id' => 'user1', 'name' => 'Alice Updated']];

        $this->apiKeyProvider
            ->expects($this->once())
            ->method('getApiKey')
            ->willReturn('test-api-key');

        $this->userService
            ->expects($this->once())
            ->method('updateUser')
            ->with('test-api-key', 'user1', 'Alice Updated')
            ->willReturn($expected);

        $result = $this->tools->manageUsers('update', 'user1', null, null, null, 'Alice Updated');

        $this->assertSame($expected, $result);
    }

    public function testManageUsersUpdateWithoutUserIdThrowsToolCallException(): void
    {
        $this->apiKeyProvider->method('getApiKey')->willReturn('test-api-key');
        $this->userService->expects($this->never())->method('updateUser');

        $this->expectException(ToolCallException::class);
        $this->expectExceptionMessage('userId required for update');

        $this->tools->manageUsers('update');
    }

    // -------------------------------------------------------------------------
    // manageUsers: delete
    // -------------------------------------------------------------------------

    public function testManageUsersDeleteSuccess(): void
    {
        $this->apiKeyProvider
            ->expects($this->once())
            ->method('getApiKey')
            ->willReturn('test-api-key');

        $this->userService
            ->expects($this->once())
            ->method('deleteUser')
            ->with('test-api-key', 'user1')
            ->willReturn([]);

        $result = $this->tools->manageUsers('delete', 'user1');

        $this->assertSame([], $result);
    }

    public function testManageUsersDeleteWithoutUserIdThrowsToolCallException(): void
    {
        $this->apiKeyProvider->method('getApiKey')->willReturn('test-api-key');
        $this->userService->expects($this->never())->method('deleteUser');

        $this->expectException(ToolCallException::class);
        $this->expectExceptionMessage('userId required for delete');

        $this->tools->manageUsers('delete');
    }

    // -------------------------------------------------------------------------
    // manageUsers: invalid action
    // -------------------------------------------------------------------------

    public function testManageUsersInvalidActionThrowsToolCallException(): void
    {
        $this->apiKeyProvider->method('getApiKey')->willReturn('test-api-key');

        $this->expectException(ToolCallException::class);
        $this->expectExceptionMessage('Invalid action "ban". Must be: list, get, create, update, delete');

        $this->tools->manageUsers('ban');
    }

    // -------------------------------------------------------------------------
    // manageUsers: missing API key + exception wrapping
    // -------------------------------------------------------------------------

    public function testManageUsersMissingApiKeyThrowsToolCallException(): void
    {
        $this->apiKeyProvider
            ->expects($this->once())
            ->method('getApiKey')
            ->willThrowException(new ValidationException('Planka API key required.'));

        $this->userService->expects($this->never())->method('getUsers');

        $this->expectException(ToolCallException::class);
        $this->expectExceptionMessage('Planka API key required.');

        $this->tools->manageUsers('list');
    }

    public function testManageUsersWrapsAuthExceptionInToolCallException(): void
    {
        $this->apiKeyProvider->method('getApiKey')->willReturn('bad-key');

        $this->userService
            ->method('getUsers')
            ->willThrowException(new AuthenticationException('Unauthorized'));

        $this->expectException(ToolCallException::class);
        $this->expectExceptionMessage('Unauthorized');

        $this->tools->manageUsers('list');
    }

    public function testManageUsersWrapsPlankaApiExceptionInToolCallException(): void
    {
        $this->apiKeyProvider->method('getApiKey')->willReturn('test-api-key');

        $this->userService
            ->method('getUser')
            ->willThrowException(new PlankaApiException('Server error'));

        $this->expectException(ToolCallException::class);
        $this->expectExceptionMessage('Server error');

        $this->tools->manageUsers('get', 'user1');
    }

    public function testManageUsersWrapsNotFoundExceptionInToolCallException(): void
    {
        $this->apiKeyProvider->method('getApiKey')->willReturn('test-api-key');

        $this->userService
            ->method('getUser')
            ->willThrowException(new PlankaNotFoundException('Not found', 404));

        $this->expectException(ToolCallException::class);
        $this->expectExceptionMessage('Not found');

        $this->tools->manageUsers('get', 'user1');
    }

    // -------------------------------------------------------------------------
    // manageUserCredentials: update-email
    // -------------------------------------------------------------------------

    public function testManageUserCredentialsUpdateEmailSuccess(): void
    {
        $expected = ['item' => ['id' => 'user1', 'email' => 'new@example.com']];

        $this->apiKeyProvider
            ->expects($this->once())
            ->method('getApiKey')
            ->willReturn('test-api-key');

        $this->userService
            ->expects($this->once())
            ->method('updateUserEmail')
            ->with('test-api-key', 'user1', 'new@example.com', 'currentPass')
            ->willReturn($expected);

        $result = $this->tools->manageUserCredentials('update-email', 'user1', 'currentPass', 'new@example.com');

        $this->assertSame($expected, $result);
    }

    public function testManageUserCredentialsUpdateEmailWithoutEmailThrowsToolCallException(): void
    {
        $this->apiKeyProvider->method('getApiKey')->willReturn('test-api-key');
        $this->userService->expects($this->never())->method('updateUserEmail');

        $this->expectException(ToolCallException::class);
        $this->expectExceptionMessage('email required for update-email');

        $this->tools->manageUserCredentials('update-email', 'user1', 'pass');
    }

    public function testManageUserCredentialsUpdateEmailWithEmptyEmailThrowsToolCallException(): void
    {
        $this->apiKeyProvider->method('getApiKey')->willReturn('test-api-key');
        $this->userService->expects($this->never())->method('updateUserEmail');

        $this->expectException(ToolCallException::class);
        $this->expectExceptionMessage('email required for update-email');

        $this->tools->manageUserCredentials('update-email', 'user1', 'pass', '  ');
    }

    // -------------------------------------------------------------------------
    // manageUserCredentials: update-password
    // -------------------------------------------------------------------------

    public function testManageUserCredentialsUpdatePasswordSuccess(): void
    {
        $expected = ['item' => ['id' => 'user1']];

        $this->apiKeyProvider
            ->expects($this->once())
            ->method('getApiKey')
            ->willReturn('test-api-key');

        $this->userService
            ->expects($this->once())
            ->method('updateUserPassword')
            ->with('test-api-key', 'user1', 'currentPass', 'newPass')
            ->willReturn($expected);

        $result = $this->tools->manageUserCredentials('update-password', 'user1', 'currentPass', null, 'newPass');

        $this->assertSame($expected, $result);
    }

    public function testManageUserCredentialsUpdatePasswordWithoutNewPasswordThrowsToolCallException(): void
    {
        $this->apiKeyProvider->method('getApiKey')->willReturn('test-api-key');
        $this->userService->expects($this->never())->method('updateUserPassword');

        $this->expectException(ToolCallException::class);
        $this->expectExceptionMessage('newPassword required for update-password');

        $this->tools->manageUserCredentials('update-password', 'user1', 'currentPass');
    }

    public function testManageUserCredentialsUpdatePasswordWithEmptyNewPasswordThrowsToolCallException(): void
    {
        $this->apiKeyProvider->method('getApiKey')->willReturn('test-api-key');
        $this->userService->expects($this->never())->method('updateUserPassword');

        $this->expectException(ToolCallException::class);
        $this->expectExceptionMessage('newPassword required for update-password');

        $this->tools->manageUserCredentials('update-password', 'user1', 'currentPass', null, '  ');
    }

    // -------------------------------------------------------------------------
    // manageUserCredentials: update-username
    // -------------------------------------------------------------------------

    public function testManageUserCredentialsUpdateUsernameSuccess(): void
    {
        $expected = ['item' => ['id' => 'user1', 'username' => 'newname']];

        $this->apiKeyProvider
            ->expects($this->once())
            ->method('getApiKey')
            ->willReturn('test-api-key');

        $this->userService
            ->expects($this->once())
            ->method('updateUserUsername')
            ->with('test-api-key', 'user1', 'newname', 'currentPass')
            ->willReturn($expected);

        $result = $this->tools->manageUserCredentials('update-username', 'user1', 'currentPass', null, null, 'newname');

        $this->assertSame($expected, $result);
    }

    public function testManageUserCredentialsUpdateUsernameWithoutUsernameThrowsToolCallException(): void
    {
        $this->apiKeyProvider->method('getApiKey')->willReturn('test-api-key');
        $this->userService->expects($this->never())->method('updateUserUsername');

        $this->expectException(ToolCallException::class);
        $this->expectExceptionMessage('username required for update-username');

        $this->tools->manageUserCredentials('update-username', 'user1', 'currentPass');
    }

    public function testManageUserCredentialsUpdateUsernameWithEmptyUsernameThrowsToolCallException(): void
    {
        $this->apiKeyProvider->method('getApiKey')->willReturn('test-api-key');
        $this->userService->expects($this->never())->method('updateUserUsername');

        $this->expectException(ToolCallException::class);
        $this->expectExceptionMessage('username required for update-username');

        $this->tools->manageUserCredentials('update-username', 'user1', 'currentPass', null, null, '  ');
    }

    // -------------------------------------------------------------------------
    // manageUserCredentials: invalid action
    // -------------------------------------------------------------------------

    public function testManageUserCredentialsInvalidActionThrowsToolCallException(): void
    {
        $this->apiKeyProvider->method('getApiKey')->willReturn('test-api-key');

        $this->expectException(ToolCallException::class);
        $this->expectExceptionMessage('Invalid action "reset". Must be: update-email, update-password, update-username');

        $this->tools->manageUserCredentials('reset', 'user1', 'pass');
    }

    // -------------------------------------------------------------------------
    // manageUserCredentials: missing API key + exception wrapping
    // -------------------------------------------------------------------------

    public function testManageUserCredentialsMissingApiKeyThrowsToolCallException(): void
    {
        $this->apiKeyProvider
            ->expects($this->once())
            ->method('getApiKey')
            ->willThrowException(new ValidationException('Planka API key required.'));

        $this->expectException(ToolCallException::class);
        $this->expectExceptionMessage('Planka API key required.');

        $this->tools->manageUserCredentials('update-email', 'user1', 'pass', 'new@example.com');
    }

    public function testManageUserCredentialsWrapsAuthExceptionInToolCallException(): void
    {
        $this->apiKeyProvider->method('getApiKey')->willReturn('bad-key');

        $this->userService
            ->method('updateUserEmail')
            ->willThrowException(new AuthenticationException('Unauthorized'));

        $this->expectException(ToolCallException::class);
        $this->expectExceptionMessage('Unauthorized');

        $this->tools->manageUserCredentials('update-email', 'user1', 'pass', 'new@example.com');
    }

    public function testManageUserCredentialsWrapsPlankaApiExceptionInToolCallException(): void
    {
        $this->apiKeyProvider->method('getApiKey')->willReturn('test-api-key');

        $this->userService
            ->method('updateUserPassword')
            ->willThrowException(new PlankaApiException('Server error'));

        $this->expectException(ToolCallException::class);
        $this->expectExceptionMessage('Server error');

        $this->tools->manageUserCredentials('update-password', 'user1', 'oldPass', null, 'newPass');
    }

    public function testManageUserCredentialsWrapsNotFoundExceptionInToolCallException(): void
    {
        $this->apiKeyProvider->method('getApiKey')->willReturn('test-api-key');

        $this->userService
            ->method('updateUserUsername')
            ->willThrowException(new PlankaNotFoundException('Not found', 404));

        $this->expectException(ToolCallException::class);
        $this->expectExceptionMessage('Not found');

        $this->tools->manageUserCredentials('update-username', 'user1', 'pass', null, null, 'newname');
    }
}
