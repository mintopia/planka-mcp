<?php

declare(strict_types=1);

namespace Tests\Unit\Mcp\Tools;

use App\Domain\BoardList\ListServiceInterface;
use App\Exception\ValidationException;
use App\Http\ApiKeyProviderInterface;
use App\Mcp\Tools\SortListTool;
use Laravel\Mcp\Request;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

final class SortListToolTest extends TestCase
{
    private ListServiceInterface&MockObject $listService;
    private ApiKeyProviderInterface&MockObject $apiKeyProvider;
    private SortListTool $tool;

    protected function setUp(): void
    {
        $this->listService = $this->createMock(ListServiceInterface::class);
        $this->apiKeyProvider = $this->createMock(ApiKeyProviderInterface::class);
        $this->tool = new SortListTool($this->listService, $this->apiKeyProvider);
    }

    private function makeRequest(array $params = []): Request
    {
        return new Request(arguments: $params);
    }

    public function testHandleSuccess(): void
    {
        $this->apiKeyProvider->method('getApiKey')->willReturn('test-key');
        $this->listService->method('sortList')->willReturn([]);

        $response = $this->tool->handle($this->makeRequest(['listId' => 'l1', 'field' => 'name']));
        $this->assertFalse($response->isError());
    }

    public function testHandleAuthError(): void
    {
        $this->apiKeyProvider->method('getApiKey')->willThrowException(new ValidationException('No API key'));

        $response = $this->tool->handle($this->makeRequest(['listId' => 'l1', 'field' => 'name']));
        $this->assertTrue($response->isError());
    }

    public function testSchemaReturnsArray(): void
    {
        $data = $this->tool->toArray();
        $this->assertIsArray($data);
        $this->assertArrayHasKey('inputSchema', $data);
    }
}
