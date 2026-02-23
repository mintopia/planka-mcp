<?php

declare(strict_types=1);

namespace Tests\Unit\Mcp\Tools;

use App\Domain\User\UserServiceInterface;
use App\Exception\ValidationException;
use App\Http\ApiKeyProviderInterface;
use App\Mcp\Tools\ManageUserCredentialsTool;
use Laravel\Mcp\Request;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

final class ManageUserCredentialsToolTest extends TestCase
{
    private UserServiceInterface&MockObject $userService;
    private ApiKeyProviderInterface&MockObject $apiKeyProvider;
    private ManageUserCredentialsTool $tool;

    protected function setUp(): void
    {
        $this->userService = $this->createMock(UserServiceInterface::class);
        $this->apiKeyProvider = $this->createMock(ApiKeyProviderInterface::class);
        $this->tool = new ManageUserCredentialsTool($this->userService, $this->apiKeyProvider);
    }

    private function makeRequest(array $params = []): Request
    {
        return new Request(arguments: $params);
    }

    public function testUpdateEmailSuccess(): void
    {
        $this->apiKeyProvider->method('getApiKey')->willReturn('test-key');
        $this->userService->method('updateUserEmail')->willReturn(['item' => ['id' => 'u1']]);

        $response = $this->tool->handle($this->makeRequest([
            'action' => 'update-email',
            'userId' => 'u1',
            'currentPassword' => 'pass',
            'email' => 'new@test.com',
        ]));
        $this->assertFalse($response->isError());
    }

    public function testUpdateEmailMissingEmail(): void
    {
        $this->apiKeyProvider->method('getApiKey')->willReturn('test-key');

        $response = $this->tool->handle($this->makeRequest([
            'action' => 'update-email',
            'userId' => 'u1',
            'currentPassword' => 'pass',
        ]));
        $this->assertTrue($response->isError());
        $this->assertStringContainsString('email required', (string) $response->content());
    }

    public function testUpdatePasswordSuccess(): void
    {
        $this->apiKeyProvider->method('getApiKey')->willReturn('test-key');
        $this->userService->method('updateUserPassword')->willReturn(['item' => ['id' => 'u1']]);

        $response = $this->tool->handle($this->makeRequest([
            'action' => 'update-password',
            'userId' => 'u1',
            'currentPassword' => 'old',
            'newPassword' => 'new',
        ]));
        $this->assertFalse($response->isError());
    }

    public function testUpdatePasswordMissingNewPassword(): void
    {
        $this->apiKeyProvider->method('getApiKey')->willReturn('test-key');

        $response = $this->tool->handle($this->makeRequest([
            'action' => 'update-password',
            'userId' => 'u1',
            'currentPassword' => 'old',
        ]));
        $this->assertTrue($response->isError());
        $this->assertStringContainsString('newPassword required', (string) $response->content());
    }

    public function testUpdateUsernameSuccess(): void
    {
        $this->apiKeyProvider->method('getApiKey')->willReturn('test-key');
        $this->userService->method('updateUserUsername')->willReturn(['item' => ['id' => 'u1']]);

        $response = $this->tool->handle($this->makeRequest([
            'action' => 'update-username',
            'userId' => 'u1',
            'currentPassword' => 'pass',
            'username' => 'newalice',
        ]));
        $this->assertFalse($response->isError());
    }

    public function testUpdateUsernameMissingUsername(): void
    {
        $this->apiKeyProvider->method('getApiKey')->willReturn('test-key');

        $response = $this->tool->handle($this->makeRequest([
            'action' => 'update-username',
            'userId' => 'u1',
            'currentPassword' => 'pass',
        ]));
        $this->assertTrue($response->isError());
        $this->assertStringContainsString('username required', (string) $response->content());
    }

    public function testInvalidAction(): void
    {
        $this->apiKeyProvider->method('getApiKey')->willReturn('test-key');

        $response = $this->tool->handle($this->makeRequest([
            'action' => 'invalid',
            'userId' => 'u1',
            'currentPassword' => 'pass',
        ]));
        $this->assertTrue($response->isError());
    }

    public function testAuthError(): void
    {
        $this->apiKeyProvider->method('getApiKey')->willThrowException(new ValidationException('No API key'));

        $response = $this->tool->handle($this->makeRequest([
            'action' => 'update-email',
            'userId' => 'u1',
            'currentPassword' => 'pass',
            'email' => 'new@test.com',
        ]));
        $this->assertTrue($response->isError());
    }

    public function testSchemaReturnsArray(): void
    {
        $data = $this->tool->toArray();
        $this->assertIsArray($data);
        $this->assertArrayHasKey('inputSchema', $data);
    }
}
