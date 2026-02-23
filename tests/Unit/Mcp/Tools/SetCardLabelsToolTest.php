<?php

declare(strict_types=1);

namespace Tests\Unit\Mcp\Tools;

use App\Domain\Label\LabelServiceInterface;
use App\Exception\ValidationException;
use App\Http\ApiKeyProviderInterface;
use App\Mcp\Tools\SetCardLabelsTool;
use Laravel\Mcp\Request;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

final class SetCardLabelsToolTest extends TestCase
{
    private LabelServiceInterface&MockObject $labelService;
    private ApiKeyProviderInterface&MockObject $apiKeyProvider;
    private SetCardLabelsTool $tool;

    protected function setUp(): void
    {
        $this->labelService = $this->createMock(LabelServiceInterface::class);
        $this->apiKeyProvider = $this->createMock(ApiKeyProviderInterface::class);
        $this->tool = new SetCardLabelsTool($this->labelService, $this->apiKeyProvider);
    }

    private function makeRequest(array $params = []): Request
    {
        return new Request(arguments: $params);
    }

    public function testHandleSuccess(): void
    {
        $this->apiKeyProvider->method('getApiKey')->willReturn('test-key');
        $this->labelService->method('setCardLabels')->willReturn(['added' => []]);

        $response = $this->tool->handle($this->makeRequest(['cardId' => 'c1', 'addLabelIds' => ['l1']]));
        $this->assertFalse($response->isError());
    }

    public function testHandleAuthError(): void
    {
        $this->apiKeyProvider->method('getApiKey')->willThrowException(new ValidationException('No API key'));

        $response = $this->tool->handle($this->makeRequest(['cardId' => 'c1']));
        $this->assertTrue($response->isError());
    }

    public function testSchemaReturnsArray(): void
    {
        $data = $this->tool->toArray();
        $this->assertIsArray($data);
        $this->assertArrayHasKey('inputSchema', $data);
    }
}
