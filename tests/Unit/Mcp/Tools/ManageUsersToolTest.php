<?php

declare(strict_types=1);

namespace Tests\Unit\Mcp\Tools;

use App\Domain\User\UserServiceInterface;
use App\Exception\ValidationException;
use App\Http\ApiKeyProviderInterface;
use App\Mcp\Tools\ManageUsersTool;
use Laravel\Mcp\Request;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

final class ManageUsersToolTest extends TestCase
{
    private UserServiceInterface&MockObject $userService;
    private ApiKeyProviderInterface&MockObject $apiKeyProvider;
    private ManageUsersTool $tool;

    protected function setUp(): void
    {
        $this->userService = $this->createMock(UserServiceInterface::class);
        $this->apiKeyProvider = $this->createMock(ApiKeyProviderInterface::class);
        $this->tool = new ManageUsersTool($this->userService, $this->apiKeyProvider);
    }

    private function makeRequest(array $params = []): Request
    {
        return new Request(arguments: $params);
    }

    public function testListSuccess(): void
    {
        $this->apiKeyProvider->method('getApiKey')->willReturn('test-key');
        $this->userService->method('getUsers')->willReturn(['items' => []]);

        $response = $this->tool->handle($this->makeRequest(['action' => 'list']));
        $this->assertFalse($response->isError());
    }

    public function testGetSuccess(): void
    {
        $this->apiKeyProvider->method('getApiKey')->willReturn('test-key');
        $this->userService->method('getUser')->willReturn(['item' => ['id' => 'u1']]);

        $response = $this->tool->handle($this->makeRequest(['action' => 'get', 'userId' => 'u1']));
        $this->assertFalse($response->isError());
    }

    public function testGetMissingUserId(): void
    {
        $this->apiKeyProvider->method('getApiKey')->willReturn('test-key');

        $response = $this->tool->handle($this->makeRequest(['action' => 'get']));
        $this->assertTrue($response->isError());
        $this->assertStringContainsString('userId required', (string) $response->content());
    }

    public function testCreateSuccess(): void
    {
        $this->apiKeyProvider->method('getApiKey')->willReturn('test-key');
        $this->userService->method('createUser')->willReturn(['item' => ['id' => 'u1']]);

        $response = $this->tool->handle($this->makeRequest([
            'action' => 'create',
            'username' => 'alice',
            'email' => 'alice@test.com',
            'password' => 'pass123',
        ]));
        $this->assertFalse($response->isError());
    }

    public function testCreateMissingUsername(): void
    {
        $this->apiKeyProvider->method('getApiKey')->willReturn('test-key');

        $response = $this->tool->handle($this->makeRequest([
            'action' => 'create',
            'email' => 'alice@test.com',
            'password' => 'pass123',
        ]));
        $this->assertTrue($response->isError());
        $this->assertStringContainsString('username required', (string) $response->content());
    }

    public function testCreateMissingEmail(): void
    {
        $this->apiKeyProvider->method('getApiKey')->willReturn('test-key');

        $response = $this->tool->handle($this->makeRequest([
            'action' => 'create',
            'username' => 'alice',
            'password' => 'pass123',
        ]));
        $this->assertTrue($response->isError());
        $this->assertStringContainsString('email required', (string) $response->content());
    }

    public function testCreateMissingPassword(): void
    {
        $this->apiKeyProvider->method('getApiKey')->willReturn('test-key');

        $response = $this->tool->handle($this->makeRequest([
            'action' => 'create',
            'username' => 'alice',
            'email' => 'alice@test.com',
        ]));
        $this->assertTrue($response->isError());
        $this->assertStringContainsString('password required', (string) $response->content());
    }

    public function testUpdateSuccess(): void
    {
        $this->apiKeyProvider->method('getApiKey')->willReturn('test-key');
        $this->userService->method('updateUser')->willReturn(['item' => ['id' => 'u1']]);

        $response = $this->tool->handle($this->makeRequest(['action' => 'update', 'userId' => 'u1', 'name' => 'Alice']));
        $this->assertFalse($response->isError());
    }

    public function testDeleteSuccess(): void
    {
        $this->apiKeyProvider->method('getApiKey')->willReturn('test-key');
        $this->userService->method('deleteUser')->willReturn([]);

        $response = $this->tool->handle($this->makeRequest(['action' => 'delete', 'userId' => 'u1']));
        $this->assertFalse($response->isError());
    }

    public function testInvalidAction(): void
    {
        $this->apiKeyProvider->method('getApiKey')->willReturn('test-key');

        $response = $this->tool->handle($this->makeRequest(['action' => 'invalid']));
        $this->assertTrue($response->isError());
    }

    public function testAuthError(): void
    {
        $this->apiKeyProvider->method('getApiKey')->willThrowException(new ValidationException('No API key'));

        $response = $this->tool->handle($this->makeRequest(['action' => 'list']));
        $this->assertTrue($response->isError());
    }

    public function testSchemaReturnsArray(): void
    {
        $data = $this->tool->toArray();
        $this->assertIsArray($data);
        $this->assertArrayHasKey('inputSchema', $data);
    }
}
