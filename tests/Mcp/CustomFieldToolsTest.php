<?php

declare(strict_types=1);

namespace App\Tests\Mcp;

use App\Domain\CustomField\CustomFieldService;
use App\Infrastructure\Http\ApiKeyProvider;
use App\Mcp\CustomFieldTools;
use App\Planka\Exception\AuthenticationException;
use App\Planka\Exception\PlankaApiException;
use App\Planka\Exception\PlankaNotFoundException;
use App\Shared\Exception\ValidationException;
use Mcp\Exception\ToolCallException;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

final class CustomFieldToolsTest extends TestCase
{
    private CustomFieldService&MockObject $customFieldService;
    private ApiKeyProvider&MockObject $apiKeyProvider;
    private CustomFieldTools $tools;

    protected function setUp(): void
    {
        $this->customFieldService = $this->createMock(CustomFieldService::class);
        $this->apiKeyProvider = $this->createMock(ApiKeyProvider::class);
        $this->tools = new CustomFieldTools($this->customFieldService, $this->apiKeyProvider);
    }

    // -------------------------------------------------------------------------
    // manageCustomFieldGroups: create_base
    // -------------------------------------------------------------------------

    public function testManageCustomFieldGroupsCreateBaseSuccess(): void
    {
        $expected = ['item' => ['id' => 'bg1', 'name' => 'My Group']];

        $this->apiKeyProvider
            ->expects($this->once())
            ->method('getApiKey')
            ->willReturn('test-api-key');

        $this->customFieldService
            ->expects($this->once())
            ->method('createBaseGroup')
            ->with('test-api-key', 'proj1', 'My Group')
            ->willReturn($expected);

        $result = $this->tools->manageCustomFieldGroups('create_base', projectId: 'proj1', name: 'My Group');

        $this->assertSame($expected, $result);
    }

    public function testManageCustomFieldGroupsCreateBaseMissingProjectIdThrowsToolCallException(): void
    {
        $this->apiKeyProvider->method('getApiKey')->willReturn('test-api-key');
        $this->customFieldService->expects($this->never())->method('createBaseGroup');

        $this->expectException(ToolCallException::class);
        $this->expectExceptionMessage('projectId required for create_base');

        $this->tools->manageCustomFieldGroups('create_base');
    }

    // -------------------------------------------------------------------------
    // manageCustomFieldGroups: update_base
    // -------------------------------------------------------------------------

    public function testManageCustomFieldGroupsUpdateBaseSuccess(): void
    {
        $expected = ['item' => ['id' => 'bg1', 'name' => 'Updated']];

        $this->apiKeyProvider->method('getApiKey')->willReturn('test-api-key');

        $this->customFieldService
            ->expects($this->once())
            ->method('updateBaseGroup')
            ->with('test-api-key', 'bg1', 'Updated')
            ->willReturn($expected);

        $result = $this->tools->manageCustomFieldGroups('update_base', baseGroupId: 'bg1', name: 'Updated');

        $this->assertSame($expected, $result);
    }

    public function testManageCustomFieldGroupsUpdateBaseMissingBaseGroupIdThrowsToolCallException(): void
    {
        $this->apiKeyProvider->method('getApiKey')->willReturn('test-api-key');

        $this->expectException(ToolCallException::class);
        $this->expectExceptionMessage('baseGroupId required for update_base');

        $this->tools->manageCustomFieldGroups('update_base');
    }

    // -------------------------------------------------------------------------
    // manageCustomFieldGroups: delete_base
    // -------------------------------------------------------------------------

    public function testManageCustomFieldGroupsDeleteBaseSuccess(): void
    {
        $this->apiKeyProvider->method('getApiKey')->willReturn('test-api-key');

        $this->customFieldService
            ->expects($this->once())
            ->method('deleteBaseGroup')
            ->with('test-api-key', 'bg1')
            ->willReturn([]);

        $result = $this->tools->manageCustomFieldGroups('delete_base', baseGroupId: 'bg1');

        $this->assertSame([], $result);
    }

    public function testManageCustomFieldGroupsDeleteBaseMissingBaseGroupIdThrowsToolCallException(): void
    {
        $this->apiKeyProvider->method('getApiKey')->willReturn('test-api-key');

        $this->expectException(ToolCallException::class);
        $this->expectExceptionMessage('baseGroupId required for delete_base');

        $this->tools->manageCustomFieldGroups('delete_base');
    }

    // -------------------------------------------------------------------------
    // manageCustomFieldGroups: create
    // -------------------------------------------------------------------------

    public function testManageCustomFieldGroupsCreateSuccess(): void
    {
        $expected = ['item' => ['id' => 'g1']];

        $this->apiKeyProvider->method('getApiKey')->willReturn('test-api-key');

        $this->customFieldService
            ->expects($this->once())
            ->method('createGroup')
            ->with('test-api-key', 'board', 'board1', 'Group Name')
            ->willReturn($expected);

        $result = $this->tools->manageCustomFieldGroups('create', parentType: 'board', parentId: 'board1', name: 'Group Name');

        $this->assertSame($expected, $result);
    }

    public function testManageCustomFieldGroupsCreateMissingParentTypeThrowsToolCallException(): void
    {
        $this->apiKeyProvider->method('getApiKey')->willReturn('test-api-key');

        $this->expectException(ToolCallException::class);
        $this->expectExceptionMessage('parentType required for create');

        $this->tools->manageCustomFieldGroups('create', parentId: 'board1');
    }

    public function testManageCustomFieldGroupsCreateMissingParentIdThrowsToolCallException(): void
    {
        $this->apiKeyProvider->method('getApiKey')->willReturn('test-api-key');

        $this->expectException(ToolCallException::class);
        $this->expectExceptionMessage('parentId required for create');

        $this->tools->manageCustomFieldGroups('create', parentType: 'board');
    }

    // -------------------------------------------------------------------------
    // manageCustomFieldGroups: get
    // -------------------------------------------------------------------------

    public function testManageCustomFieldGroupsGetSuccess(): void
    {
        $expected = ['item' => ['id' => 'g1', 'name' => 'My Group']];

        $this->apiKeyProvider->method('getApiKey')->willReturn('test-api-key');

        $this->customFieldService
            ->expects($this->once())
            ->method('getGroup')
            ->with('test-api-key', 'g1')
            ->willReturn($expected);

        $result = $this->tools->manageCustomFieldGroups('get', groupId: 'g1');

        $this->assertSame($expected, $result);
    }

    public function testManageCustomFieldGroupsGetMissingGroupIdThrowsToolCallException(): void
    {
        $this->apiKeyProvider->method('getApiKey')->willReturn('test-api-key');

        $this->expectException(ToolCallException::class);
        $this->expectExceptionMessage('groupId required for get');

        $this->tools->manageCustomFieldGroups('get');
    }

    // -------------------------------------------------------------------------
    // manageCustomFieldGroups: update
    // -------------------------------------------------------------------------

    public function testManageCustomFieldGroupsUpdateSuccess(): void
    {
        $expected = ['item' => ['id' => 'g1', 'name' => 'Renamed']];

        $this->apiKeyProvider->method('getApiKey')->willReturn('test-api-key');

        $this->customFieldService
            ->expects($this->once())
            ->method('updateGroup')
            ->with('test-api-key', 'g1', 'Renamed')
            ->willReturn($expected);

        $result = $this->tools->manageCustomFieldGroups('update', groupId: 'g1', name: 'Renamed');

        $this->assertSame($expected, $result);
    }

    public function testManageCustomFieldGroupsUpdateMissingGroupIdThrowsToolCallException(): void
    {
        $this->apiKeyProvider->method('getApiKey')->willReturn('test-api-key');

        $this->expectException(ToolCallException::class);
        $this->expectExceptionMessage('groupId required for update');

        $this->tools->manageCustomFieldGroups('update');
    }

    // -------------------------------------------------------------------------
    // manageCustomFieldGroups: delete
    // -------------------------------------------------------------------------

    public function testManageCustomFieldGroupsDeleteSuccess(): void
    {
        $this->apiKeyProvider->method('getApiKey')->willReturn('test-api-key');

        $this->customFieldService
            ->expects($this->once())
            ->method('deleteGroup')
            ->with('test-api-key', 'g1')
            ->willReturn([]);

        $result = $this->tools->manageCustomFieldGroups('delete', groupId: 'g1');

        $this->assertSame([], $result);
    }

    public function testManageCustomFieldGroupsDeleteMissingGroupIdThrowsToolCallException(): void
    {
        $this->apiKeyProvider->method('getApiKey')->willReturn('test-api-key');

        $this->expectException(ToolCallException::class);
        $this->expectExceptionMessage('groupId required for delete');

        $this->tools->manageCustomFieldGroups('delete');
    }

    // -------------------------------------------------------------------------
    // manageCustomFieldGroups: invalid action
    // -------------------------------------------------------------------------

    public function testManageCustomFieldGroupsInvalidActionThrowsToolCallException(): void
    {
        $this->apiKeyProvider->method('getApiKey')->willReturn('test-api-key');

        $this->expectException(ToolCallException::class);
        $this->expectExceptionMessage('Invalid action "foobar". Must be: create_base, update_base, delete_base, create, get, update, delete');

        $this->tools->manageCustomFieldGroups('foobar');
    }

    // -------------------------------------------------------------------------
    // manageCustomFieldGroups: exception wrapping
    // -------------------------------------------------------------------------

    public function testManageCustomFieldGroupsMissingApiKeyThrowsToolCallException(): void
    {
        $this->apiKeyProvider
            ->method('getApiKey')
            ->willThrowException(new ValidationException('Planka API key required.'));

        $this->expectException(ToolCallException::class);
        $this->expectExceptionMessage('Planka API key required.');

        $this->tools->manageCustomFieldGroups('get', groupId: 'g1');
    }

    public function testManageCustomFieldGroupsWrapsAuthExceptionInToolCallException(): void
    {
        $this->apiKeyProvider->method('getApiKey')->willReturn('bad-key');

        $this->customFieldService
            ->method('getGroup')
            ->willThrowException(new AuthenticationException('Unauthorized'));

        $this->expectException(ToolCallException::class);
        $this->expectExceptionMessage('Unauthorized');

        $this->tools->manageCustomFieldGroups('get', groupId: 'g1');
    }

    public function testManageCustomFieldGroupsWrapsPlankaApiExceptionInToolCallException(): void
    {
        $this->apiKeyProvider->method('getApiKey')->willReturn('test-api-key');

        $this->customFieldService
            ->method('deleteGroup')
            ->willThrowException(new PlankaApiException('Server error'));

        $this->expectException(ToolCallException::class);
        $this->expectExceptionMessage('Server error');

        $this->tools->manageCustomFieldGroups('delete', groupId: 'g1');
    }

    public function testManageCustomFieldGroupsWrapsNotFoundExceptionInToolCallException(): void
    {
        $this->apiKeyProvider->method('getApiKey')->willReturn('test-api-key');

        $this->customFieldService
            ->method('getGroup')
            ->willThrowException(new PlankaNotFoundException('Not found', 404));

        $this->expectException(ToolCallException::class);
        $this->expectExceptionMessage('Not found');

        $this->tools->manageCustomFieldGroups('get', groupId: 'g1');
    }

    // -------------------------------------------------------------------------
    // manageCustomFields: create
    // -------------------------------------------------------------------------

    public function testManageCustomFieldsCreateSuccess(): void
    {
        $expected = ['item' => ['id' => 'f1', 'name' => 'Priority']];

        $this->apiKeyProvider->method('getApiKey')->willReturn('test-api-key');

        $this->customFieldService
            ->expects($this->once())
            ->method('createField')
            ->with('test-api-key', 'base', 'bg1', 'Priority', 'dropdown')
            ->willReturn($expected);

        $result = $this->tools->manageCustomFields('create', groupType: 'base', groupId: 'bg1', name: 'Priority', fieldType: 'dropdown');

        $this->assertSame($expected, $result);
    }

    public function testManageCustomFieldsCreateMissingGroupTypeThrowsToolCallException(): void
    {
        $this->apiKeyProvider->method('getApiKey')->willReturn('test-api-key');

        $this->expectException(ToolCallException::class);
        $this->expectExceptionMessage('groupType required for create');

        $this->tools->manageCustomFields('create', groupId: 'bg1');
    }

    public function testManageCustomFieldsCreateMissingGroupIdThrowsToolCallException(): void
    {
        $this->apiKeyProvider->method('getApiKey')->willReturn('test-api-key');

        $this->expectException(ToolCallException::class);
        $this->expectExceptionMessage('groupId required for create');

        $this->tools->manageCustomFields('create', groupType: 'base');
    }

    // -------------------------------------------------------------------------
    // manageCustomFields: update
    // -------------------------------------------------------------------------

    public function testManageCustomFieldsUpdateSuccess(): void
    {
        $expected = ['item' => ['id' => 'f1', 'name' => 'Updated']];

        $this->apiKeyProvider->method('getApiKey')->willReturn('test-api-key');

        $this->customFieldService
            ->expects($this->once())
            ->method('updateField')
            ->with('test-api-key', 'f1', 'Updated', 'number')
            ->willReturn($expected);

        $result = $this->tools->manageCustomFields('update', fieldId: 'f1', name: 'Updated', fieldType: 'number');

        $this->assertSame($expected, $result);
    }

    public function testManageCustomFieldsUpdateMissingFieldIdThrowsToolCallException(): void
    {
        $this->apiKeyProvider->method('getApiKey')->willReturn('test-api-key');

        $this->expectException(ToolCallException::class);
        $this->expectExceptionMessage('fieldId required for update');

        $this->tools->manageCustomFields('update', name: 'X');
    }

    // -------------------------------------------------------------------------
    // manageCustomFields: delete
    // -------------------------------------------------------------------------

    public function testManageCustomFieldsDeleteSuccess(): void
    {
        $this->apiKeyProvider->method('getApiKey')->willReturn('test-api-key');

        $this->customFieldService
            ->expects($this->once())
            ->method('deleteField')
            ->with('test-api-key', 'f1')
            ->willReturn([]);

        $result = $this->tools->manageCustomFields('delete', fieldId: 'f1');

        $this->assertSame([], $result);
    }

    public function testManageCustomFieldsDeleteMissingFieldIdThrowsToolCallException(): void
    {
        $this->apiKeyProvider->method('getApiKey')->willReturn('test-api-key');

        $this->expectException(ToolCallException::class);
        $this->expectExceptionMessage('fieldId required for delete');

        $this->tools->manageCustomFields('delete');
    }

    // -------------------------------------------------------------------------
    // manageCustomFields: invalid action
    // -------------------------------------------------------------------------

    public function testManageCustomFieldsInvalidActionThrowsToolCallException(): void
    {
        $this->apiKeyProvider->method('getApiKey')->willReturn('test-api-key');

        $this->expectException(ToolCallException::class);
        $this->expectExceptionMessage('Invalid action "foobar". Must be: create, update, delete');

        $this->tools->manageCustomFields('foobar');
    }

    // -------------------------------------------------------------------------
    // manageCustomFields: exception wrapping
    // -------------------------------------------------------------------------

    public function testManageCustomFieldsMissingApiKeyThrowsToolCallException(): void
    {
        $this->apiKeyProvider
            ->method('getApiKey')
            ->willThrowException(new ValidationException('Planka API key required.'));

        $this->expectException(ToolCallException::class);
        $this->expectExceptionMessage('Planka API key required.');

        $this->tools->manageCustomFields('delete', fieldId: 'f1');
    }

    public function testManageCustomFieldsWrapsAuthExceptionInToolCallException(): void
    {
        $this->apiKeyProvider->method('getApiKey')->willReturn('bad-key');

        $this->customFieldService
            ->method('deleteField')
            ->willThrowException(new AuthenticationException('Unauthorized'));

        $this->expectException(ToolCallException::class);
        $this->expectExceptionMessage('Unauthorized');

        $this->tools->manageCustomFields('delete', fieldId: 'f1');
    }

    // -------------------------------------------------------------------------
    // manageCustomFieldValues: set
    // -------------------------------------------------------------------------

    public function testManageCustomFieldValuesSetSuccess(): void
    {
        $expected = ['item' => ['value' => 'high']];

        $this->apiKeyProvider->method('getApiKey')->willReturn('test-api-key');

        $this->customFieldService
            ->expects($this->once())
            ->method('setFieldValue')
            ->with('test-api-key', 'card1', 'g1', 'f1', 'high')
            ->willReturn($expected);

        $result = $this->tools->manageCustomFieldValues('set', 'card1', 'g1', 'f1', 'high');

        $this->assertSame($expected, $result);
    }

    public function testManageCustomFieldValuesSetMissingValueThrowsToolCallException(): void
    {
        $this->apiKeyProvider->method('getApiKey')->willReturn('test-api-key');

        $this->expectException(ToolCallException::class);
        $this->expectExceptionMessage('value required for set');

        $this->tools->manageCustomFieldValues('set', 'card1', 'g1', 'f1');
    }

    // -------------------------------------------------------------------------
    // manageCustomFieldValues: delete
    // -------------------------------------------------------------------------

    public function testManageCustomFieldValuesDeleteSuccess(): void
    {
        $this->apiKeyProvider->method('getApiKey')->willReturn('test-api-key');

        $this->customFieldService
            ->expects($this->once())
            ->method('deleteFieldValue')
            ->with('test-api-key', 'card1', 'g1', 'f1')
            ->willReturn([]);

        $result = $this->tools->manageCustomFieldValues('delete', 'card1', 'g1', 'f1');

        $this->assertSame([], $result);
    }

    // -------------------------------------------------------------------------
    // manageCustomFieldValues: invalid action
    // -------------------------------------------------------------------------

    public function testManageCustomFieldValuesInvalidActionThrowsToolCallException(): void
    {
        $this->apiKeyProvider->method('getApiKey')->willReturn('test-api-key');

        $this->expectException(ToolCallException::class);
        $this->expectExceptionMessage('Invalid action "update". Must be: set, delete');

        $this->tools->manageCustomFieldValues('update', 'card1', 'g1', 'f1');
    }

    // -------------------------------------------------------------------------
    // manageCustomFieldValues: exception wrapping
    // -------------------------------------------------------------------------

    public function testManageCustomFieldValuesMissingApiKeyThrowsToolCallException(): void
    {
        $this->apiKeyProvider
            ->method('getApiKey')
            ->willThrowException(new ValidationException('Planka API key required.'));

        $this->expectException(ToolCallException::class);
        $this->expectExceptionMessage('Planka API key required.');

        $this->tools->manageCustomFieldValues('delete', 'card1', 'g1', 'f1');
    }

    public function testManageCustomFieldValuesWrapsAuthExceptionInToolCallException(): void
    {
        $this->apiKeyProvider->method('getApiKey')->willReturn('bad-key');

        $this->customFieldService
            ->method('setFieldValue')
            ->willThrowException(new AuthenticationException('Unauthorized'));

        $this->expectException(ToolCallException::class);
        $this->expectExceptionMessage('Unauthorized');

        $this->tools->manageCustomFieldValues('set', 'card1', 'g1', 'f1', 'val');
    }

    public function testManageCustomFieldValuesWrapsPlankaApiExceptionInToolCallException(): void
    {
        $this->apiKeyProvider->method('getApiKey')->willReturn('test-api-key');

        $this->customFieldService
            ->method('deleteFieldValue')
            ->willThrowException(new PlankaApiException('Server error'));

        $this->expectException(ToolCallException::class);
        $this->expectExceptionMessage('Server error');

        $this->tools->manageCustomFieldValues('delete', 'card1', 'g1', 'f1');
    }

    public function testManageCustomFieldValuesWrapsNotFoundExceptionInToolCallException(): void
    {
        $this->apiKeyProvider->method('getApiKey')->willReturn('test-api-key');

        $this->customFieldService
            ->method('setFieldValue')
            ->willThrowException(new PlankaNotFoundException('Not found', 404));

        $this->expectException(ToolCallException::class);
        $this->expectExceptionMessage('Not found');

        $this->tools->manageCustomFieldValues('set', 'card1', 'g1', 'f1', 'val');
    }
}
