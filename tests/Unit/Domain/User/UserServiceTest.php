<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\User;

use App\Domain\User\UserService;
use App\Planka\Exception\AuthenticationException;
use App\Planka\Exception\PlankaApiException;
use App\Planka\PlankaClientInterface;
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

    public function testGetUsersSuccess(): void
    {
        $expected = ['items' => [['id' => 'user1', 'name' => 'Alice']]];

        $this->plankaClient
            ->expects($this->once())
            ->method('get')
            ->with('test-api-key', '/api/users')
            ->willReturn($expected);

        $this->assertSame($expected, $this->service->getUsers('test-api-key'));
    }

    public function testGetUsersPropagatesAuthException(): void
    {
        $this->plankaClient->method('get')->willThrowException(new AuthenticationException('Unauthorized'));
        $this->expectException(AuthenticationException::class);
        $this->service->getUsers('bad-key');
    }

    public function testGetUsersPropagatesApiException(): void
    {
        $this->plankaClient->method('get')->willThrowException(new PlankaApiException('Server error'));
        $this->expectException(PlankaApiException::class);
        $this->service->getUsers('test-api-key');
    }

    public function testCreateUserSuccess(): void
    {
        $expected = ['item' => ['id' => 'user1', 'username' => 'alice']];

        $this->plankaClient
            ->expects($this->once())
            ->method('post')
            ->with('test-api-key', '/api/users', ['username' => 'alice', 'email' => 'alice@test.com', 'password' => 'pass123'])
            ->willReturn($expected);

        $this->assertSame($expected, $this->service->createUser('test-api-key', 'alice', 'alice@test.com', 'pass123'));
    }

    public function testCreateUserWithName(): void
    {
        $expected = ['item' => ['id' => 'user1', 'username' => 'alice', 'name' => 'Alice']];

        $this->plankaClient
            ->expects($this->once())
            ->method('post')
            ->with('test-api-key', '/api/users', ['username' => 'alice', 'email' => 'alice@test.com', 'password' => 'pass123', 'name' => 'Alice'])
            ->willReturn($expected);

        $this->assertSame($expected, $this->service->createUser('test-api-key', 'alice', 'alice@test.com', 'pass123', 'Alice'));
    }

    public function testCreateUserPropagatesAuthException(): void
    {
        $this->plankaClient->method('post')->willThrowException(new AuthenticationException('Unauthorized'));
        $this->expectException(AuthenticationException::class);
        $this->service->createUser('bad-key', 'alice', 'alice@test.com', 'pass');
    }

    public function testCreateUserPropagatesApiException(): void
    {
        $this->plankaClient->method('post')->willThrowException(new PlankaApiException('Server error'));
        $this->expectException(PlankaApiException::class);
        $this->service->createUser('test-api-key', 'alice', 'alice@test.com', 'pass');
    }

    public function testGetUserSuccess(): void
    {
        $expected = ['item' => ['id' => 'user1', 'name' => 'Alice']];

        $this->plankaClient
            ->expects($this->once())
            ->method('get')
            ->with('test-api-key', '/api/users/user1')
            ->willReturn($expected);

        $this->assertSame($expected, $this->service->getUser('test-api-key', 'user1'));
    }

    public function testGetUserPropagatesAuthException(): void
    {
        $this->plankaClient->method('get')->willThrowException(new AuthenticationException('Unauthorized'));
        $this->expectException(AuthenticationException::class);
        $this->service->getUser('bad-key', 'user1');
    }

    public function testUpdateUserWithName(): void
    {
        $expected = ['item' => ['id' => 'user1', 'name' => 'Updated']];

        $this->plankaClient
            ->expects($this->once())
            ->method('patch')
            ->with('test-api-key', '/api/users/user1', ['name' => 'Updated'])
            ->willReturn($expected);

        $this->assertSame($expected, $this->service->updateUser('test-api-key', 'user1', 'Updated'));
    }

    public function testUpdateUserWithNullNameSendsEmptyBody(): void
    {
        $expected = ['item' => ['id' => 'user1']];

        $this->plankaClient
            ->expects($this->once())
            ->method('patch')
            ->with('test-api-key', '/api/users/user1', [])
            ->willReturn($expected);

        $this->assertSame($expected, $this->service->updateUser('test-api-key', 'user1'));
    }

    public function testUpdateUserPropagatesAuthException(): void
    {
        $this->plankaClient->method('patch')->willThrowException(new AuthenticationException('Unauthorized'));
        $this->expectException(AuthenticationException::class);
        $this->service->updateUser('bad-key', 'user1', 'Name');
    }

    public function testUpdateUserPropagatesApiException(): void
    {
        $this->plankaClient->method('patch')->willThrowException(new PlankaApiException('Server error'));
        $this->expectException(PlankaApiException::class);
        $this->service->updateUser('test-api-key', 'user1', 'Name');
    }

    public function testDeleteUserSuccess(): void
    {
        $this->plankaClient
            ->expects($this->once())
            ->method('delete')
            ->with('test-api-key', '/api/users/user1')
            ->willReturn([]);

        $this->assertSame([], $this->service->deleteUser('test-api-key', 'user1'));
    }

    public function testDeleteUserPropagatesAuthException(): void
    {
        $this->plankaClient->method('delete')->willThrowException(new AuthenticationException('Unauthorized'));
        $this->expectException(AuthenticationException::class);
        $this->service->deleteUser('bad-key', 'user1');
    }

    public function testDeleteUserPropagatesApiException(): void
    {
        $this->plankaClient->method('delete')->willThrowException(new PlankaApiException('Server error'));
        $this->expectException(PlankaApiException::class);
        $this->service->deleteUser('test-api-key', 'user1');
    }

    public function testUpdateUserEmailSuccess(): void
    {
        $expected = ['item' => ['id' => 'user1']];

        $this->plankaClient
            ->expects($this->once())
            ->method('patch')
            ->with('test-api-key', '/api/users/user1/email', ['email' => 'new@test.com', 'currentPassword' => 'pass123'])
            ->willReturn($expected);

        $this->assertSame($expected, $this->service->updateUserEmail('test-api-key', 'user1', 'new@test.com', 'pass123'));
    }

    public function testUpdateUserEmailPropagatesAuthException(): void
    {
        $this->plankaClient->method('patch')->willThrowException(new AuthenticationException('Unauthorized'));
        $this->expectException(AuthenticationException::class);
        $this->service->updateUserEmail('bad-key', 'user1', 'new@test.com', 'pass');
    }

    public function testUpdateUserPasswordSuccess(): void
    {
        $expected = ['item' => ['id' => 'user1']];

        $this->plankaClient
            ->expects($this->once())
            ->method('patch')
            ->with('test-api-key', '/api/users/user1/password', ['currentPassword' => 'old', 'newPassword' => 'new'])
            ->willReturn($expected);

        $this->assertSame($expected, $this->service->updateUserPassword('test-api-key', 'user1', 'old', 'new'));
    }

    public function testUpdateUserPasswordPropagatesAuthException(): void
    {
        $this->plankaClient->method('patch')->willThrowException(new AuthenticationException('Unauthorized'));
        $this->expectException(AuthenticationException::class);
        $this->service->updateUserPassword('bad-key', 'user1', 'old', 'new');
    }

    public function testUpdateUserUsernameSuccess(): void
    {
        $expected = ['item' => ['id' => 'user1']];

        $this->plankaClient
            ->expects($this->once())
            ->method('patch')
            ->with('test-api-key', '/api/users/user1/username', ['username' => 'newalice', 'currentPassword' => 'pass123'])
            ->willReturn($expected);

        $this->assertSame($expected, $this->service->updateUserUsername('test-api-key', 'user1', 'newalice', 'pass123'));
    }

    public function testUpdateUserUsernamePropagatesAuthException(): void
    {
        $this->plankaClient->method('patch')->willThrowException(new AuthenticationException('Unauthorized'));
        $this->expectException(AuthenticationException::class);
        $this->service->updateUserUsername('bad-key', 'user1', 'newalice', 'pass');
    }
}
