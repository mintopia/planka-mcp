<?php

declare(strict_types=1);

namespace Tests\Unit\Mcp\Tools;

use App\Domain\Label\LabelServiceInterface;
use App\Exception\ValidationException;
use App\Http\ApiKeyProviderInterface;
use App\Mcp\Tools\ManageLabelsTool;
use Laravel\Mcp\Request;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

final class ManageLabelsToolTest extends TestCase
{
    private LabelServiceInterface&MockObject $labelService;
    private ApiKeyProviderInterface&MockObject $apiKeyProvider;
    private ManageLabelsTool $tool;

    protected function setUp(): void
    {
        $this->labelService = $this->createMock(LabelServiceInterface::class);
        $this->apiKeyProvider = $this->createMock(ApiKeyProviderInterface::class);
        $this->tool = new ManageLabelsTool($this->labelService, $this->apiKeyProvider);
    }

    private function makeRequest(array $params = []): Request
    {
        return new Request(arguments: $params);
    }

    public function testCreateSuccess(): void
    {
        $this->apiKeyProvider->method('getApiKey')->willReturn('test-key');
        $this->labelService->method('manageLabel')->willReturn(['item' => ['id' => 'l1']]);

        $response = $this->tool->handle($this->makeRequest(['action' => 'create', 'boardId' => 'b1', 'name' => 'Bug']));
        $this->assertFalse($response->isError());
    }

    public function testDeleteSuccess(): void
    {
        $this->apiKeyProvider->method('getApiKey')->willReturn('test-key');
        $this->labelService->method('manageLabel')->willReturn([]);

        $response = $this->tool->handle($this->makeRequest(['action' => 'delete', 'labelId' => 'l1']));
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
        $this->labelService->method('manageLabel')->willThrowException(new ValidationException('boardId required'));

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
