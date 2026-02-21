<?php

declare(strict_types=1);

namespace App\Tests\Domain\User;

use App\Domain\User\UserService;
use App\Planka\Client\PlankaClientInterface;
use App\Planka\Exception\AuthenticationException;
use App\Planka\Exception\PlankaApiException;
use App\Planka\Exception\PlankaNotFoundException;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

final class UserServiceTest extends TestCase
{
    private PlankaClientInterface&MockObject $plankaClient;
    private UserService $service;

    protected function setUp(): void
    {
        $this->plankaClient = $this->createMock(PlankaClientInterface::class);
        $this->service = new UserService($this->plankaClient);
    }

    // --- getUsers ---

    public function testGetUsersSuccess(): void
    {
        $expected = ['items' => [['id' => 'user1', 'username' => 'alice']]];

        $this->plankaClient
            ->expects($this->once())
            ->method('get')
            ->with('test-api-key', '/api/users')
            ->willReturn($expected);

        $result = $this->service->getUsers('test-api-key');

        $this->assertSame($expected, $result);
    }

    public function testGetUsersPropagatesAuthException(): void
    {
        $this->plankaClient
            ->method('get')
            ->willThrowException(new AuthenticationException('Unauthorized'));

        $this->expectException(AuthenticationException::class);

        $this->service->getUsers('bad-key');
    }

    public function testGetUsersPropagatesApiException(): void
    {
        $this->plankaClient
            ->method('get')
            ->willThrowException(new PlankaApiException('Server error'));

        $this->expectException(PlankaApiException::class);

        $this->service->getUsers('test-api-key');
    }

    // --- createUser ---

    public function testCreateUserWithAllParamsSuccess(): void
    {
        $expected = ['item' => ['id' => 'user1', 'username' => 'alice', 'name' => 'Alice Smith']];

        $this->plankaClient
            ->expects($this->once())
            ->method('post')
            ->with('test-api-key', '/api/users', [
                'username' => 'alice',
                'email' => 'alice@example.com',
                'password' => 'secret',
                'name' => 'Alice Smith',
            ])
            ->willReturn($expected);

        $result = $this->service->createUser('test-api-key', 'alice', 'alice@example.com', 'secret', 'Alice Smith');

        $this->assertSame($expected, $result);
    }

    public function testCreateUserWithoutNameSuccess(): void
    {
        $expected = ['item' => ['id' => 'user1', 'username' => 'alice']];

        $this->plankaClient
            ->expects($this->once())
            ->method('post')
            ->with('test-api-key', '/api/users', [
                'username' => 'alice',
                'email' => 'alice@example.com',
                'password' => 'secret',
            ])
            ->willReturn($expected);

        $result = $this->service->createUser('test-api-key', 'alice', 'alice@example.com', 'secret');

        $this->assertSame($expected, $result);
    }

    public function testCreateUserPropagatesAuthException(): void
    {
        $this->plankaClient
            ->method('post')
            ->willThrowException(new AuthenticationException('Unauthorized'));

        $this->expectException(AuthenticationException::class);

        $this->service->createUser('bad-key', 'alice', 'alice@example.com', 'secret');
    }

    public function testCreateUserPropagatesApiException(): void
    {
        $this->plankaClient
            ->method('post')
            ->willThrowException(new PlankaApiException('Server error'));

        $this->expectException(PlankaApiException::class);

        $this->service->createUser('test-api-key', 'alice', 'alice@example.com', 'secret');
    }

    // --- getUser ---

    public function testGetUserSuccess(): void
    {
        $expected = ['item' => ['id' => 'user1', 'username' => 'alice']];

        $this->plankaClient
            ->expects($this->once())
            ->method('get')
            ->with('test-api-key', '/api/users/user1')
            ->willReturn($expected);

        $result = $this->service->getUser('test-api-key', 'user1');

        $this->assertSame($expected, $result);
    }

    public function testGetUserPropagatesAuthException(): void
    {
        $this->plankaClient
            ->method('get')
            ->willThrowException(new AuthenticationException('Unauthorized'));

        $this->expectException(AuthenticationException::class);

        $this->service->getUser('bad-key', 'user1');
    }

    public function testGetUserPropagatesApiException(): void
    {
        $this->plankaClient
            ->method('get')
            ->willThrowException(new PlankaApiException('Server error'));

        $this->expectException(PlankaApiException::class);

        $this->service->getUser('test-api-key', 'user1');
    }

    public function testGetUserPropagatesNotFoundException(): void
    {
        $this->plankaClient
            ->method('get')
            ->willThrowException(new PlankaNotFoundException('Not found', 404));

        $this->expectException(PlankaNotFoundException::class);

        $this->service->getUser('test-api-key', 'user1');
    }

    // --- updateUser ---

    public function testUpdateUserWithNameSuccess(): void
    {
        $expected = ['item' => ['id' => 'user1', 'name' => 'Alice Updated']];

        $this->plankaClient
            ->expects($this->once())
            ->method('patch')
            ->with('test-api-key', '/api/users/user1', ['name' => 'Alice Updated'])
            ->willReturn($expected);

        $result = $this->service->updateUser('test-api-key', 'user1', 'Alice Updated');

        $this->assertSame($expected, $result);
    }

    public function testUpdateUserWithNullNameSendsEmptyBody(): void
    {
        $expected = ['item' => ['id' => 'user1']];

        $this->plankaClient
            ->expects($this->once())
            ->method('patch')
            ->with('test-api-key', '/api/users/user1', [])
            ->willReturn($expected);

        $result = $this->service->updateUser('test-api-key', 'user1');

        $this->assertSame($expected, $result);
    }

    public function testUpdateUserPropagatesAuthException(): void
    {
        $this->plankaClient
            ->method('patch')
            ->willThrowException(new AuthenticationException('Unauthorized'));

        $this->expectException(AuthenticationException::class);

        $this->service->updateUser('bad-key', 'user1', 'Name');
    }

    public function testUpdateUserPropagatesApiException(): void
    {
        $this->plankaClient
            ->method('patch')
            ->willThrowException(new PlankaApiException('Server error'));

        $this->expectException(PlankaApiException::class);

        $this->service->updateUser('test-api-key', 'user1', 'Name');
    }

    // --- deleteUser ---

    public function testDeleteUserSuccess(): void
    {
        $this->plankaClient
            ->expects($this->once())
            ->method('delete')
            ->with('test-api-key', '/api/users/user1')
            ->willReturn([]);

        $result = $this->service->deleteUser('test-api-key', 'user1');

        $this->assertSame([], $result);
    }

    public function testDeleteUserPropagatesAuthException(): void
    {
        $this->plankaClient
            ->method('delete')
            ->willThrowException(new AuthenticationException('Unauthorized'));

        $this->expectException(AuthenticationException::class);

        $this->service->deleteUser('bad-key', 'user1');
    }

    public function testDeleteUserPropagatesApiException(): void
    {
        $this->plankaClient
            ->method('delete')
            ->willThrowException(new PlankaApiException('Server error'));

        $this->expectException(PlankaApiException::class);

        $this->service->deleteUser('test-api-key', 'user1');
    }

    // --- updateUserEmail ---

    public function testUpdateUserEmailSuccess(): void
    {
        $expected = ['item' => ['id' => 'user1', 'email' => 'newemail@example.com']];

        $this->plankaClient
            ->expects($this->once())
            ->method('patch')
            ->with('test-api-key', '/api/users/user1/email', [
                'email' => 'newemail@example.com',
                'currentPassword' => 'currentPass',
            ])
            ->willReturn($expected);

        $result = $this->service->updateUserEmail('test-api-key', 'user1', 'newemail@example.com', 'currentPass');

        $this->assertSame($expected, $result);
    }

    public function testUpdateUserEmailPropagatesAuthException(): void
    {
        $this->plankaClient
            ->method('patch')
            ->willThrowException(new AuthenticationException('Unauthorized'));

        $this->expectException(AuthenticationException::class);

        $this->service->updateUserEmail('bad-key', 'user1', 'email@example.com', 'pass');
    }

    public function testUpdateUserEmailPropagatesApiException(): void
    {
        $this->plankaClient
            ->method('patch')
            ->willThrowException(new PlankaApiException('Server error'));

        $this->expectException(PlankaApiException::class);

        $this->service->updateUserEmail('test-api-key', 'user1', 'email@example.com', 'pass');
    }

    // --- updateUserPassword ---

    public function testUpdateUserPasswordSuccess(): void
    {
        $expected = ['item' => ['id' => 'user1']];

        $this->plankaClient
            ->expects($this->once())
            ->method('patch')
            ->with('test-api-key', '/api/users/user1/password', [
                'currentPassword' => 'oldPass',
                'newPassword' => 'newPass',
            ])
            ->willReturn($expected);

        $result = $this->service->updateUserPassword('test-api-key', 'user1', 'oldPass', 'newPass');

        $this->assertSame($expected, $result);
    }

    public function testUpdateUserPasswordPropagatesAuthException(): void
    {
        $this->plankaClient
            ->method('patch')
            ->willThrowException(new AuthenticationException('Unauthorized'));

        $this->expectException(AuthenticationException::class);

        $this->service->updateUserPassword('bad-key', 'user1', 'old', 'new');
    }

    public function testUpdateUserPasswordPropagatesApiException(): void
    {
        $this->plankaClient
            ->method('patch')
            ->willThrowException(new PlankaApiException('Server error'));

        $this->expectException(PlankaApiException::class);

        $this->service->updateUserPassword('test-api-key', 'user1', 'old', 'new');
    }

    // --- updateUserUsername ---

    public function testUpdateUserUsernameSuccess(): void
    {
        $expected = ['item' => ['id' => 'user1', 'username' => 'newusername']];

        $this->plankaClient
            ->expects($this->once())
            ->method('patch')
            ->with('test-api-key', '/api/users/user1/username', [
                'username' => 'newusername',
                'currentPassword' => 'myPass',
            ])
            ->willReturn($expected);

        $result = $this->service->updateUserUsername('test-api-key', 'user1', 'newusername', 'myPass');

        $this->assertSame($expected, $result);
    }

    public function testUpdateUserUsernamePropagatesAuthException(): void
    {
        $this->plankaClient
            ->method('patch')
            ->willThrowException(new AuthenticationException('Unauthorized'));

        $this->expectException(AuthenticationException::class);

        $this->service->updateUserUsername('bad-key', 'user1', 'newname', 'pass');
    }

    public function testUpdateUserUsernamePropagatesApiException(): void
    {
        $this->plankaClient
            ->method('patch')
            ->willThrowException(new PlankaApiException('Server error'));

        $this->expectException(PlankaApiException::class);

        $this->service->updateUserUsername('test-api-key', 'user1', 'newname', 'pass');
    }
}
