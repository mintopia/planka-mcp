<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\CustomField;

use App\Domain\CustomField\CustomFieldService;
use App\Exception\ValidationException;
use App\Planka\Exception\AuthenticationException;
use App\Planka\Exception\PlankaApiException;
use App\Planka\PlankaClientInterface;
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

    public function testCreateBaseGroupSuccess(): void
    {
        $expected = ['item' => ['id' => 'bg1', 'name' => 'Fields']];

        $this->plankaClient
            ->expects($this->once())
            ->method('post')
            ->with('test-api-key', '/api/projects/proj1/base-custom-field-groups', ['position' => 65536, 'name' => 'Fields'])
            ->willReturn($expected);

        $this->assertSame($expected, $this->service->createBaseGroup('test-api-key', 'proj1', 'Fields'));
    }

    public function testCreateBaseGroupWithNullName(): void
    {
        $expected = ['item' => ['id' => 'bg1']];

        $this->plankaClient
            ->expects($this->once())
            ->method('post')
            ->with('test-api-key', '/api/projects/proj1/base-custom-field-groups', ['position' => 65536])
            ->willReturn($expected);

        $this->assertSame($expected, $this->service->createBaseGroup('test-api-key', 'proj1', null));
    }

    public function testCreateBaseGroupPropagatesAuthException(): void
    {
        $this->plankaClient->method('post')->willThrowException(new AuthenticationException('Unauthorized'));
        $this->expectException(AuthenticationException::class);
        $this->service->createBaseGroup('bad-key', 'proj1', 'Name');
    }

    public function testCreateBaseGroupPropagatesApiException(): void
    {
        $this->plankaClient->method('post')->willThrowException(new PlankaApiException('Server error'));
        $this->expectException(PlankaApiException::class);
        $this->service->createBaseGroup('test-api-key', 'proj1', 'Name');
    }

    public function testUpdateBaseGroupSuccess(): void
    {
        $expected = ['item' => ['id' => 'bg1', 'name' => 'Updated']];

        $this->plankaClient
            ->expects($this->once())
            ->method('patch')
            ->with('test-api-key', '/api/base-custom-field-groups/bg1', ['name' => 'Updated'])
            ->willReturn($expected);

        $this->assertSame($expected, $this->service->updateBaseGroup('test-api-key', 'bg1', 'Updated'));
    }

    public function testUpdateBaseGroupWithNullNameSendsEmptyBody(): void
    {
        $expected = ['item' => ['id' => 'bg1']];

        $this->plankaClient
            ->expects($this->once())
            ->method('patch')
            ->with('test-api-key', '/api/base-custom-field-groups/bg1', [])
            ->willReturn($expected);

        $this->assertSame($expected, $this->service->updateBaseGroup('test-api-key', 'bg1', null));
    }

    public function testDeleteBaseGroupSuccess(): void
    {
        $this->plankaClient
            ->expects($this->once())
            ->method('delete')
            ->with('test-api-key', '/api/base-custom-field-groups/bg1')
            ->willReturn([]);

        $this->assertSame([], $this->service->deleteBaseGroup('test-api-key', 'bg1'));
    }

    public function testDeleteBaseGroupPropagatesAuthException(): void
    {
        $this->plankaClient->method('delete')->willThrowException(new AuthenticationException('Unauthorized'));
        $this->expectException(AuthenticationException::class);
        $this->service->deleteBaseGroup('bad-key', 'bg1');
    }

    public function testCreateGroupForBoardSuccess(): void
    {
        $expected = ['item' => ['id' => 'g1', 'name' => 'Group']];

        $this->plankaClient
            ->expects($this->once())
            ->method('post')
            ->with('test-api-key', '/api/boards/board1/custom-field-groups', ['position' => 65536, 'name' => 'Group'])
            ->willReturn($expected);

        $this->assertSame($expected, $this->service->createGroup('test-api-key', 'board', 'board1', 'Group'));
    }

    public function testCreateGroupForCardSuccess(): void
    {
        $expected = ['item' => ['id' => 'g1', 'name' => 'Group']];

        $this->plankaClient
            ->expects($this->once())
            ->method('post')
            ->with('test-api-key', '/api/cards/card1/custom-field-groups', ['position' => 65536, 'name' => 'Group'])
            ->willReturn($expected);

        $this->assertSame($expected, $this->service->createGroup('test-api-key', 'card', 'card1', 'Group'));
    }

    public function testCreateGroupInvalidParentTypeThrows(): void
    {
        $this->expectException(ValidationException::class);
        $this->service->createGroup('test-api-key', 'invalid', 'id1', 'Name');
    }

    public function testCreateGroupWithNullName(): void
    {
        $expected = ['item' => ['id' => 'g1']];

        $this->plankaClient
            ->expects($this->once())
            ->method('post')
            ->with('test-api-key', '/api/boards/board1/custom-field-groups', ['position' => 65536])
            ->willReturn($expected);

        $this->assertSame($expected, $this->service->createGroup('test-api-key', 'board', 'board1', null));
    }

    public function testGetGroupSuccess(): void
    {
        $expected = ['item' => ['id' => 'g1', 'name' => 'Group']];

        $this->plankaClient
            ->expects($this->once())
            ->method('get')
            ->with('test-api-key', '/api/custom-field-groups/g1')
            ->willReturn($expected);

        $this->assertSame($expected, $this->service->getGroup('test-api-key', 'g1'));
    }

    public function testGetGroupPropagatesAuthException(): void
    {
        $this->plankaClient->method('get')->willThrowException(new AuthenticationException('Unauthorized'));
        $this->expectException(AuthenticationException::class);
        $this->service->getGroup('bad-key', 'g1');
    }

    public function testUpdateGroupSuccess(): void
    {
        $expected = ['item' => ['id' => 'g1', 'name' => 'Updated']];

        $this->plankaClient
            ->expects($this->once())
            ->method('patch')
            ->with('test-api-key', '/api/custom-field-groups/g1', ['name' => 'Updated'])
            ->willReturn($expected);

        $this->assertSame($expected, $this->service->updateGroup('test-api-key', 'g1', 'Updated'));
    }

    public function testUpdateGroupWithNullNameSendsEmptyBody(): void
    {
        $expected = ['item' => ['id' => 'g1']];

        $this->plankaClient
            ->expects($this->once())
            ->method('patch')
            ->with('test-api-key', '/api/custom-field-groups/g1', [])
            ->willReturn($expected);

        $this->assertSame($expected, $this->service->updateGroup('test-api-key', 'g1', null));
    }

    public function testDeleteGroupSuccess(): void
    {
        $this->plankaClient
            ->expects($this->once())
            ->method('delete')
            ->with('test-api-key', '/api/custom-field-groups/g1')
            ->willReturn([]);

        $this->assertSame([], $this->service->deleteGroup('test-api-key', 'g1'));
    }

    public function testCreateFieldForBaseGroupSuccess(): void
    {
        $expected = ['item' => ['id' => 'f1', 'name' => 'Status']];

        $this->plankaClient
            ->expects($this->once())
            ->method('post')
            ->with('test-api-key', '/api/base-custom-field-groups/bg1/custom-fields', ['position' => 65536, 'name' => 'Status', 'type' => 'text'])
            ->willReturn($expected);

        $this->assertSame($expected, $this->service->createField('test-api-key', 'base', 'bg1', 'Status', 'text'));
    }

    public function testCreateFieldForGroupSuccess(): void
    {
        $expected = ['item' => ['id' => 'f1', 'name' => 'Priority']];

        $this->plankaClient
            ->expects($this->once())
            ->method('post')
            ->with('test-api-key', '/api/custom-field-groups/g1/custom-fields', ['position' => 65536, 'name' => 'Priority', 'type' => 'number'])
            ->willReturn($expected);

        $this->assertSame($expected, $this->service->createField('test-api-key', 'group', 'g1', 'Priority', 'number'));
    }

    public function testCreateFieldInvalidGroupTypeThrows(): void
    {
        $this->expectException(ValidationException::class);
        $this->service->createField('test-api-key', 'invalid', 'g1', 'Name', 'text');
    }

    public function testCreateFieldWithNullNameAndType(): void
    {
        $expected = ['item' => ['id' => 'f1']];

        $this->plankaClient
            ->expects($this->once())
            ->method('post')
            ->with('test-api-key', '/api/base-custom-field-groups/bg1/custom-fields', ['position' => 65536])
            ->willReturn($expected);

        $this->assertSame($expected, $this->service->createField('test-api-key', 'base', 'bg1', null, null));
    }

    public function testUpdateFieldSuccess(): void
    {
        $expected = ['item' => ['id' => 'f1', 'name' => 'Updated', 'type' => 'number']];

        $this->plankaClient
            ->expects($this->once())
            ->method('patch')
            ->with('test-api-key', '/api/custom-fields/f1', ['name' => 'Updated', 'type' => 'number'])
            ->willReturn($expected);

        $this->assertSame($expected, $this->service->updateField('test-api-key', 'f1', 'Updated', 'number'));
    }

    public function testUpdateFieldWithNullsSendsEmptyBody(): void
    {
        $expected = ['item' => ['id' => 'f1']];

        $this->plankaClient
            ->expects($this->once())
            ->method('patch')
            ->with('test-api-key', '/api/custom-fields/f1', [])
            ->willReturn($expected);

        $this->assertSame($expected, $this->service->updateField('test-api-key', 'f1', null, null));
    }

    public function testDeleteFieldSuccess(): void
    {
        $this->plankaClient
            ->expects($this->once())
            ->method('delete')
            ->with('test-api-key', '/api/custom-fields/f1')
            ->willReturn([]);

        $this->assertSame([], $this->service->deleteField('test-api-key', 'f1'));
    }

    public function testDeleteFieldPropagatesAuthException(): void
    {
        $this->plankaClient->method('delete')->willThrowException(new AuthenticationException('Unauthorized'));
        $this->expectException(AuthenticationException::class);
        $this->service->deleteField('bad-key', 'f1');
    }

    public function testSetFieldValueSuccess(): void
    {
        $expected = ['item' => ['content' => 'hello']];

        $this->plankaClient
            ->expects($this->once())
            ->method('patch')
            ->with('test-api-key', '/api/cards/card1/custom-field-values/customFieldGroupId:g1:customFieldId:f1', ['content' => 'hello'])
            ->willReturn($expected);

        $this->assertSame($expected, $this->service->setFieldValue('test-api-key', 'card1', 'g1', 'f1', 'hello'));
    }

    public function testSetFieldValuePropagatesAuthException(): void
    {
        $this->plankaClient->method('patch')->willThrowException(new AuthenticationException('Unauthorized'));
        $this->expectException(AuthenticationException::class);
        $this->service->setFieldValue('bad-key', 'card1', 'g1', 'f1', 'hello');
    }

    public function testDeleteFieldValueSuccess(): void
    {
        $this->plankaClient
            ->expects($this->once())
            ->method('delete')
            ->with('test-api-key', '/api/cards/card1/custom-field-values/customFieldGroupId:g1:customFieldId:f1')
            ->willReturn([]);

        $this->assertSame([], $this->service->deleteFieldValue('test-api-key', 'card1', 'g1', 'f1'));
    }

    public function testDeleteFieldValuePropagatesAuthException(): void
    {
        $this->plankaClient->method('delete')->willThrowException(new AuthenticationException('Unauthorized'));
        $this->expectException(AuthenticationException::class);
        $this->service->deleteFieldValue('bad-key', 'card1', 'g1', 'f1');
    }

    public function testDeleteFieldValuePropagatesApiException(): void
    {
        $this->plankaClient->method('delete')->willThrowException(new PlankaApiException('Server error'));
        $this->expectException(PlankaApiException::class);
        $this->service->deleteFieldValue('test-api-key', 'card1', 'g1', 'f1');
    }
}
