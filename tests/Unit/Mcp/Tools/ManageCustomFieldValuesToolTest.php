<?php

declare(strict_types=1);

namespace Tests\Unit\Mcp\Tools;

use App\Domain\CustomField\CustomFieldServiceInterface;
use App\Exception\ValidationException;
use App\Http\ApiKeyProviderInterface;
use App\Mcp\Tools\ManageCustomFieldValuesTool;
use Laravel\Mcp\Request;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

final class ManageCustomFieldValuesToolTest extends TestCase
{
    private CustomFieldServiceInterface&MockObject $customFieldService;
    private ApiKeyProviderInterface&MockObject $apiKeyProvider;
    private ManageCustomFieldValuesTool $tool;

    protected function setUp(): void
    {
        $this->customFieldService = $this->createMock(CustomFieldServiceInterface::class);
        $this->apiKeyProvider = $this->createMock(ApiKeyProviderInterface::class);
        $this->tool = new ManageCustomFieldValuesTool($this->customFieldService, $this->apiKeyProvider);
    }

    private function makeRequest(array $params = []): Request
    {
        return new Request(arguments: $params);
    }

    public function testSetSuccess(): void
    {
        $this->apiKeyProvider->method('getApiKey')->willReturn('test-key');
        $this->customFieldService->method('setFieldValue')->willReturn(['item' => ['content' => 'hello']]);

        $response = $this->tool->handle($this->makeRequest([
            'action' => 'set',
            'cardId' => 'c1',
            'customFieldGroupId' => 'g1',
            'customFieldId' => 'f1',
            'value' => 'hello',
        ]));
        $this->assertFalse($response->isError());
    }

    public function testSetMissingValue(): void
    {
        $this->apiKeyProvider->method('getApiKey')->willReturn('test-key');

        $response = $this->tool->handle($this->makeRequest([
            'action' => 'set',
            'cardId' => 'c1',
            'customFieldGroupId' => 'g1',
            'customFieldId' => 'f1',
        ]));
        $this->assertTrue($response->isError());
        $this->assertStringContainsString('value required', (string) $response->content());
    }

    public function testDeleteSuccess(): void
    {
        $this->apiKeyProvider->method('getApiKey')->willReturn('test-key');
        $this->customFieldService->method('deleteFieldValue')->willReturn([]);

        $response = $this->tool->handle($this->makeRequest([
            'action' => 'delete',
            'cardId' => 'c1',
            'customFieldGroupId' => 'g1',
            'customFieldId' => 'f1',
        ]));
        $this->assertFalse($response->isError());
    }

    public function testInvalidAction(): void
    {
        $this->apiKeyProvider->method('getApiKey')->willReturn('test-key');

        $response = $this->tool->handle($this->makeRequest([
            'action' => 'invalid',
            'cardId' => 'c1',
            'customFieldGroupId' => 'g1',
            'customFieldId' => 'f1',
        ]));
        $this->assertTrue($response->isError());
    }

    public function testAuthError(): void
    {
        $this->apiKeyProvider->method('getApiKey')->willThrowException(new ValidationException('No API key'));

        $response = $this->tool->handle($this->makeRequest([
            'action' => 'set',
            'cardId' => 'c1',
            'customFieldGroupId' => 'g1',
            'customFieldId' => 'f1',
            'value' => 'hello',
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
