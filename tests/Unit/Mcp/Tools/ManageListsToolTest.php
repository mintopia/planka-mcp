<?php

declare(strict_types=1);

namespace Tests\Unit\Mcp\Tools;

use App\Domain\BoardList\ListServiceInterface;
use App\Exception\ValidationException;
use App\Http\ApiKeyProviderInterface;
use App\Mcp\Tools\ManageListsTool;
use Laravel\Mcp\Request;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

final class ManageListsToolTest extends TestCase
{
    private ListServiceInterface&MockObject $listService;
    private ApiKeyProviderInterface&MockObject $apiKeyProvider;
    private ManageListsTool $tool;

    protected function setUp(): void
    {
        $this->listService = $this->createMock(ListServiceInterface::class);
        $this->apiKeyProvider = $this->createMock(ApiKeyProviderInterface::class);
        $this->tool = new ManageListsTool($this->listService, $this->apiKeyProvider);
    }

    private function makeRequest(array $params = []): Request
    {
        return new Request(arguments: $params);
    }

    public function testCreateSuccess(): void
    {
        $this->apiKeyProvider->method('getApiKey')->willReturn('test-key');
        $this->listService->method('manageList')->willReturn(['item' => ['id' => 'l1']]);

        $response = $this->tool->handle($this->makeRequest(['action' => 'create', 'boardId' => 'b1', 'name' => 'To Do']));
        $this->assertFalse($response->isError());
    }

    public function testDeleteSuccess(): void
    {
        $this->apiKeyProvider->method('getApiKey')->willReturn('test-key');
        $this->listService->method('manageList')->willReturn([]);

        $response = $this->tool->handle($this->makeRequest(['action' => 'delete', 'listId' => 'l1']));
        $this->assertFalse($response->isError());
    }

    public function testHandleAuthError(): void
    {
        $this->apiKeyProvider->method('getApiKey')->willThrowException(new ValidationException('No API key'));

        $response = $this->tool->handle($this->makeRequest(['action' => 'create', 'boardId' => 'b1']));
        $this->assertTrue($response->isError());
    }

    public function testHandleServiceValidationError(): void
    {
        $this->apiKeyProvider->method('getApiKey')->willReturn('test-key');
        $this->listService->method('manageList')->willThrowException(new ValidationException('boardId required'));

        $response = $this->tool->handle($this->makeRequest(['action' => 'create']));
        $this->assertTrue($response->isError());
    }

    public function testSchemaReturnsArray(): void
    {
        $data = $this->tool->toArray();
        $this->assertIsArray($data);
        $this->assertArrayHasKey('inputSchema', $data);
    }
}
