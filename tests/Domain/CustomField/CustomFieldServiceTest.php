<?php

declare(strict_types=1);

namespace App\Tests\Domain\CustomField;

use App\Domain\CustomField\CustomFieldService;
use App\Planka\Client\PlankaClientInterface;
use App\Planka\Exception\AuthenticationException;
use App\Planka\Exception\PlankaApiException;
use App\Shared\Exception\ValidationException;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

final class CustomFieldServiceTest extends TestCase
{
    private PlankaClientInterface&MockObject $plankaClient;
    private CustomFieldService $service;

    protected function setUp(): void
    {
        $this->plankaClient = $this->createMock(PlankaClientInterface::class);
        $this->service = new CustomFieldService($this->plankaClient);
    }

    // -------------------------------------------------------------------------
    // createBaseGroup()
    // -------------------------------------------------------------------------

    public function testCreateBaseGroupWithNameSuccess(): void
    {
        $expected = ['item' => ['id' => 'bg1', 'name' => 'My Group']];

        $this->plankaClient
            ->expects($this->once())
            ->method('post')
            ->with('test-api-key', '/api/projects/proj1/base-custom-field-groups', ['position' => 65536, 'name' => 'My Group'])
            ->willReturn($expected);

        $result = $this->service->createBaseGroup('test-api-key', 'proj1', 'My Group');

        $this->assertSame($expected, $result);
    }

    public function testCreateBaseGroupWithoutNameSuccess(): void
    {
        $expected = ['item' => ['id' => 'bg1']];

        $this->plankaClient
            ->expects($this->once())
            ->method('post')
            ->with('test-api-key', '/api/projects/proj1/base-custom-field-groups', ['position' => 65536])
            ->willReturn($expected);

        $result = $this->service->createBaseGroup('test-api-key', 'proj1', null);

        $this->assertSame($expected, $result);
    }

    public function testCreateBaseGroupPropagatesAuthException(): void
    {
        $this->plankaClient
            ->method('post')
            ->willThrowException(new AuthenticationException('Unauthorized'));

        $this->expectException(AuthenticationException::class);

        $this->service->createBaseGroup('bad-key', 'proj1', null);
    }

    public function testCreateBaseGroupPropagatesApiException(): void
    {
        $this->plankaClient
            ->method('post')
            ->willThrowException(new PlankaApiException('Server error'));

        $this->expectException(PlankaApiException::class);

        $this->service->createBaseGroup('test-api-key', 'proj1', null);
    }

    // -------------------------------------------------------------------------
    // updateBaseGroup()
    // -------------------------------------------------------------------------

    public function testUpdateBaseGroupWithNameSuccess(): void
    {
        $expected = ['item' => ['id' => 'bg1', 'name' => 'Updated']];

        $this->plankaClient
            ->expects($this->once())
            ->method('patch')
            ->with('test-api-key', '/api/base-custom-field-groups/bg1', ['name' => 'Updated'])
            ->willReturn($expected);

        $result = $this->service->updateBaseGroup('test-api-key', 'bg1', 'Updated');

        $this->assertSame($expected, $result);
    }

    public function testUpdateBaseGroupWithoutNameSuccess(): void
    {
        $expected = ['item' => ['id' => 'bg1']];

        $this->plankaClient
            ->expects($this->once())
            ->method('patch')
            ->with('test-api-key', '/api/base-custom-field-groups/bg1', [])
            ->willReturn($expected);

        $result = $this->service->updateBaseGroup('test-api-key', 'bg1', null);

        $this->assertSame($expected, $result);
    }

    public function testUpdateBaseGroupPropagatesAuthException(): void
    {
        $this->plankaClient
            ->method('patch')
            ->willThrowException(new AuthenticationException('Unauthorized'));

        $this->expectException(AuthenticationException::class);

        $this->service->updateBaseGroup('bad-key', 'bg1', null);
    }

    public function testUpdateBaseGroupPropagatesApiException(): void
    {
        $this->plankaClient
            ->method('patch')
            ->willThrowException(new PlankaApiException('Server error'));

        $this->expectException(PlankaApiException::class);

        $this->service->updateBaseGroup('test-api-key', 'bg1', null);
    }

    // -------------------------------------------------------------------------
    // deleteBaseGroup()
    // -------------------------------------------------------------------------

    public function testDeleteBaseGroupSuccess(): void
    {
        $this->plankaClient
            ->expects($this->once())
            ->method('delete')
            ->with('test-api-key', '/api/base-custom-field-groups/bg1')
            ->willReturn([]);

        $result = $this->service->deleteBaseGroup('test-api-key', 'bg1');

        $this->assertSame([], $result);
    }

    public function testDeleteBaseGroupPropagatesAuthException(): void
    {
        $this->plankaClient
            ->method('delete')
            ->willThrowException(new AuthenticationException('Unauthorized'));

        $this->expectException(AuthenticationException::class);

        $this->service->deleteBaseGroup('bad-key', 'bg1');
    }

    public function testDeleteBaseGroupPropagatesApiException(): void
    {
        $this->plankaClient
            ->method('delete')
            ->willThrowException(new PlankaApiException('Server error'));

        $this->expectException(PlankaApiException::class);

        $this->service->deleteBaseGroup('test-api-key', 'bg1');
    }

    // -------------------------------------------------------------------------
    // createGroup()
    // -------------------------------------------------------------------------

    public function testCreateGroupForBoardSuccess(): void
    {
        $expected = ['item' => ['id' => 'g1']];

        $this->plankaClient
            ->expects($this->once())
            ->method('post')
            ->with('test-api-key', '/api/boards/board1/custom-field-groups', ['position' => 65536, 'name' => 'Board Group'])
            ->willReturn($expected);

        $result = $this->service->createGroup('test-api-key', 'board', 'board1', 'Board Group');

        $this->assertSame($expected, $result);
    }

    public function testCreateGroupForCardSuccess(): void
    {
        $expected = ['item' => ['id' => 'g2']];

        $this->plankaClient
            ->expects($this->once())
            ->method('post')
            ->with('test-api-key', '/api/cards/card1/custom-field-groups', ['position' => 65536, 'name' => 'Card Group'])
            ->willReturn($expected);

        $result = $this->service->createGroup('test-api-key', 'card', 'card1', 'Card Group');

        $this->assertSame($expected, $result);
    }

    public function testCreateGroupWithoutNameSuccess(): void
    {
        $expected = ['item' => ['id' => 'g3']];

        $this->plankaClient
            ->expects($this->once())
            ->method('post')
            ->with('test-api-key', '/api/boards/board1/custom-field-groups', ['position' => 65536])
            ->willReturn($expected);

        $result = $this->service->createGroup('test-api-key', 'board', 'board1', null);

        $this->assertSame($expected, $result);
    }

    public function testCreateGroupInvalidParentTypeThrowsValidationException(): void
    {
        $this->plankaClient->expects($this->never())->method('post');

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('Invalid parentType "project". Must be "board" or "card".');

        $this->service->createGroup('test-api-key', 'project', 'proj1', null);
    }

    public function testCreateGroupPropagatesAuthException(): void
    {
        $this->plankaClient
            ->method('post')
            ->willThrowException(new AuthenticationException('Unauthorized'));

        $this->expectException(AuthenticationException::class);

        $this->service->createGroup('bad-key', 'board', 'board1', null);
    }

    // -------------------------------------------------------------------------
    // getGroup()
    // -------------------------------------------------------------------------

    public function testGetGroupSuccess(): void
    {
        $expected = ['item' => ['id' => 'g1', 'name' => 'My Group']];

        $this->plankaClient
            ->expects($this->once())
            ->method('get')
            ->with('test-api-key', '/api/custom-field-groups/g1')
            ->willReturn($expected);

        $result = $this->service->getGroup('test-api-key', 'g1');

        $this->assertSame($expected, $result);
    }

    public function testGetGroupPropagatesAuthException(): void
    {
        $this->plankaClient
            ->method('get')
            ->willThrowException(new AuthenticationException('Unauthorized'));

        $this->expectException(AuthenticationException::class);

        $this->service->getGroup('bad-key', 'g1');
    }

    public function testGetGroupPropagatesApiException(): void
    {
        $this->plankaClient
            ->method('get')
            ->willThrowException(new PlankaApiException('Server error'));

        $this->expectException(PlankaApiException::class);

        $this->service->getGroup('test-api-key', 'g1');
    }

    // -------------------------------------------------------------------------
    // updateGroup()
    // -------------------------------------------------------------------------

    public function testUpdateGroupWithNameSuccess(): void
    {
        $expected = ['item' => ['id' => 'g1', 'name' => 'Renamed']];

        $this->plankaClient
            ->expects($this->once())
            ->method('patch')
            ->with('test-api-key', '/api/custom-field-groups/g1', ['name' => 'Renamed'])
            ->willReturn($expected);

        $result = $this->service->updateGroup('test-api-key', 'g1', 'Renamed');

        $this->assertSame($expected, $result);
    }

    public function testUpdateGroupWithoutNameSuccess(): void
    {
        $expected = ['item' => ['id' => 'g1']];

        $this->plankaClient
            ->expects($this->once())
            ->method('patch')
            ->with('test-api-key', '/api/custom-field-groups/g1', [])
            ->willReturn($expected);

        $result = $this->service->updateGroup('test-api-key', 'g1', null);

        $this->assertSame($expected, $result);
    }

    public function testUpdateGroupPropagatesApiException(): void
    {
        $this->plankaClient
            ->method('patch')
            ->willThrowException(new PlankaApiException('Server error'));

        $this->expectException(PlankaApiException::class);

        $this->service->updateGroup('test-api-key', 'g1', null);
    }

    // -------------------------------------------------------------------------
    // deleteGroup()
    // -------------------------------------------------------------------------

    public function testDeleteGroupSuccess(): void
    {
        $this->plankaClient
            ->expects($this->once())
            ->method('delete')
            ->with('test-api-key', '/api/custom-field-groups/g1')
            ->willReturn([]);

        $result = $this->service->deleteGroup('test-api-key', 'g1');

        $this->assertSame([], $result);
    }

    public function testDeleteGroupPropagatesApiException(): void
    {
        $this->plankaClient
            ->method('delete')
            ->willThrowException(new PlankaApiException('Server error'));

        $this->expectException(PlankaApiException::class);

        $this->service->deleteGroup('test-api-key', 'g1');
    }

    // -------------------------------------------------------------------------
    // createField()
    // -------------------------------------------------------------------------

    public function testCreateFieldInBaseGroupSuccess(): void
    {
        $expected = ['item' => ['id' => 'f1', 'name' => 'Priority']];

        $this->plankaClient
            ->expects($this->once())
            ->method('post')
            ->with('test-api-key', '/api/base-custom-field-groups/bg1/custom-fields', ['position' => 65536, 'name' => 'Priority', 'type' => 'dropdown'])
            ->willReturn($expected);

        $result = $this->service->createField('test-api-key', 'base', 'bg1', 'Priority', 'dropdown');

        $this->assertSame($expected, $result);
    }

    public function testCreateFieldInRegularGroupSuccess(): void
    {
        $expected = ['item' => ['id' => 'f2', 'name' => 'Status']];

        $this->plankaClient
            ->expects($this->once())
            ->method('post')
            ->with('test-api-key', '/api/custom-field-groups/g1/custom-fields', ['position' => 65536, 'name' => 'Status', 'type' => 'text'])
            ->willReturn($expected);

        $result = $this->service->createField('test-api-key', 'group', 'g1', 'Status', 'text');

        $this->assertSame($expected, $result);
    }

    public function testCreateFieldWithoutOptionalParamsSuccess(): void
    {
        $expected = ['item' => ['id' => 'f3']];

        $this->plankaClient
            ->expects($this->once())
            ->method('post')
            ->with('test-api-key', '/api/base-custom-field-groups/bg1/custom-fields', ['position' => 65536])
            ->willReturn($expected);

        $result = $this->service->createField('test-api-key', 'base', 'bg1', null, null);

        $this->assertSame($expected, $result);
    }

    public function testCreateFieldInvalidGroupTypeThrowsValidationException(): void
    {
        $this->plankaClient->expects($this->never())->method('post');

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('Invalid groupType "invalid". Must be "base" or "group".');

        $this->service->createField('test-api-key', 'invalid', 'g1', null, null);
    }

    public function testCreateFieldPropagatesAuthException(): void
    {
        $this->plankaClient
            ->method('post')
            ->willThrowException(new AuthenticationException('Unauthorized'));

        $this->expectException(AuthenticationException::class);

        $this->service->createField('bad-key', 'base', 'bg1', null, null);
    }

    // -------------------------------------------------------------------------
    // updateField()
    // -------------------------------------------------------------------------

    public function testUpdateFieldWithNameAndTypeSuccess(): void
    {
        $expected = ['item' => ['id' => 'f1', 'name' => 'Updated', 'type' => 'number']];

        $this->plankaClient
            ->expects($this->once())
            ->method('patch')
            ->with('test-api-key', '/api/custom-fields/f1', ['name' => 'Updated', 'type' => 'number'])
            ->willReturn($expected);

        $result = $this->service->updateField('test-api-key', 'f1', 'Updated', 'number');

        $this->assertSame($expected, $result);
    }

    public function testUpdateFieldWithNameOnlySuccess(): void
    {
        $expected = ['item' => ['id' => 'f1', 'name' => 'Renamed']];

        $this->plankaClient
            ->expects($this->once())
            ->method('patch')
            ->with('test-api-key', '/api/custom-fields/f1', ['name' => 'Renamed'])
            ->willReturn($expected);

        $result = $this->service->updateField('test-api-key', 'f1', 'Renamed', null);

        $this->assertSame($expected, $result);
    }

    public function testUpdateFieldWithNoParamsSuccess(): void
    {
        $expected = ['item' => ['id' => 'f1']];

        $this->plankaClient
            ->expects($this->once())
            ->method('patch')
            ->with('test-api-key', '/api/custom-fields/f1', [])
            ->willReturn($expected);

        $result = $this->service->updateField('test-api-key', 'f1', null, null);

        $this->assertSame($expected, $result);
    }

    public function testUpdateFieldPropagatesApiException(): void
    {
        $this->plankaClient
            ->method('patch')
            ->willThrowException(new PlankaApiException('Server error'));

        $this->expectException(PlankaApiException::class);

        $this->service->updateField('test-api-key', 'f1', null, null);
    }

    // -------------------------------------------------------------------------
    // deleteField()
    // -------------------------------------------------------------------------

    public function testDeleteFieldSuccess(): void
    {
        $this->plankaClient
            ->expects($this->once())
            ->method('delete')
            ->with('test-api-key', '/api/custom-fields/f1')
            ->willReturn([]);

        $result = $this->service->deleteField('test-api-key', 'f1');

        $this->assertSame([], $result);
    }

    public function testDeleteFieldPropagatesApiException(): void
    {
        $this->plankaClient
            ->method('delete')
            ->willThrowException(new PlankaApiException('Server error'));

        $this->expectException(PlankaApiException::class);

        $this->service->deleteField('test-api-key', 'f1');
    }

    // -------------------------------------------------------------------------
    // setFieldValue()
    // -------------------------------------------------------------------------

    public function testSetFieldValueSuccess(): void
    {
        $expected = ['item' => ['value' => 'high']];

        $this->plankaClient
            ->expects($this->once())
            ->method('patch')
            ->with(
                'test-api-key',
                '/api/cards/card1/custom-field-values/customFieldGroupId:g1:customFieldId:f1',
                ['content' => 'high'],
            )
            ->willReturn($expected);

        $result = $this->service->setFieldValue('test-api-key', 'card1', 'g1', 'f1', 'high');

        $this->assertSame($expected, $result);
    }

    public function testSetFieldValuePropagatesAuthException(): void
    {
        $this->plankaClient
            ->method('patch')
            ->willThrowException(new AuthenticationException('Unauthorized'));

        $this->expectException(AuthenticationException::class);

        $this->service->setFieldValue('bad-key', 'card1', 'g1', 'f1', 'high');
    }

    public function testSetFieldValuePropagatesApiException(): void
    {
        $this->plankaClient
            ->method('patch')
            ->willThrowException(new PlankaApiException('Server error'));

        $this->expectException(PlankaApiException::class);

        $this->service->setFieldValue('test-api-key', 'card1', 'g1', 'f1', 'high');
    }

    // -------------------------------------------------------------------------
    // deleteFieldValue()
    // -------------------------------------------------------------------------

    public function testDeleteFieldValueSuccess(): void
    {
        $this->plankaClient
            ->expects($this->once())
            ->method('delete')
            ->with(
                'test-api-key',
                '/api/cards/card1/custom-field-values/customFieldGroupId:g1:customFieldId:f1',
            )
            ->willReturn([]);

        $result = $this->service->deleteFieldValue('test-api-key', 'card1', 'g1', 'f1');

        $this->assertSame([], $result);
    }

    public function testDeleteFieldValuePropagatesAuthException(): void
    {
        $this->plankaClient
            ->method('delete')
            ->willThrowException(new AuthenticationException('Unauthorized'));

        $this->expectException(AuthenticationException::class);

        $this->service->deleteFieldValue('bad-key', 'card1', 'g1', 'f1');
    }

    public function testDeleteFieldValuePropagatesApiException(): void
    {
        $this->plankaClient
            ->method('delete')
            ->willThrowException(new PlankaApiException('Server error'));

        $this->expectException(PlankaApiException::class);

        $this->service->deleteFieldValue('test-api-key', 'card1', 'g1', 'f1');
    }
}
