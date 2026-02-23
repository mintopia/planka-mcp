<?php

declare(strict_types=1);

namespace Tests\Unit\Mcp\Tools;

use App\Domain\Card\CardServiceInterface;
use App\Exception\ValidationException;
use App\Http\ApiKeyProviderInterface;
use App\Mcp\Tools\UpdateCardTool;
use Laravel\Mcp\Request;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

final class UpdateCardToolTest extends TestCase
{
    private CardServiceInterface&MockObject $cardService;
    private ApiKeyProviderInterface&MockObject $apiKeyProvider;
    private UpdateCardTool $tool;

    protected function setUp(): void
    {
        $this->cardService = $this->createMock(CardServiceInterface::class);
        $this->apiKeyProvider = $this->createMock(ApiKeyProviderInterface::class);
        $this->tool = new UpdateCardTool($this->cardService, $this->apiKeyProvider);
    }

    private function makeRequest(array $params = []): Request
    {
        return new Request(arguments: $params);
    }

    public function testHandleSuccess(): void
    {
        $this->apiKeyProvider->method('getApiKey')->willReturn('test-key');
        $this->cardService->method('updateCard')->willReturn(['item' => ['id' => 'c1']]);

        $response = $this->tool->handle($this->makeRequest(['cardId' => 'c1', 'name' => 'Updated']));
        $this->assertFalse($response->isError());
    }

    public function testHandleNoFieldsProvided(): void
    {
        $response = $this->tool->handle($this->makeRequest(['cardId' => 'c1']));
        $this->assertTrue($response->isError());
        $this->assertStringContainsString('At least one field', (string) $response->content());
    }

    public function testHandleAuthError(): void
    {
        $this->apiKeyProvider->method('getApiKey')->willThrowException(new ValidationException('No API key'));

        $response = $this->tool->handle($this->makeRequest(['cardId' => 'c1', 'name' => 'X']));
        $this->assertTrue($response->isError());
    }

    public function testSchemaReturnsArray(): void
    {
        $data = $this->tool->toArray();
        $this->assertIsArray($data);
        $this->assertArrayHasKey('inputSchema', $data);
    }
}
