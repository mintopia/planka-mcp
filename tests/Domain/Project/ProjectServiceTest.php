<?php

declare(strict_types=1);

namespace App\Tests\Domain\Project;

use App\Domain\Project\ProjectService;
use App\Planka\Client\PlankaClientInterface;
use App\Planka\Exception\AuthenticationException;
use App\Planka\Exception\PlankaApiException;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

final class ProjectServiceTest extends TestCase
{
    private PlankaClientInterface&MockObject $plankaClient;
    private ProjectService $service;

    protected function setUp(): void
    {
        $this->plankaClient = $this->createMock(PlankaClientInterface::class);
        $this->service = new ProjectService($this->plankaClient);
    }

    public function testGetStructureSuccess(): void
    {
        $expected = ['items' => [['id' => 'proj1', 'name' => 'Test Project']]];

        $this->plankaClient
            ->expects($this->once())
            ->method('get')
            ->with('test-api-key', '/api/projects')
            ->willReturn($expected);

        $result = $this->service->getStructure('test-api-key');

        $this->assertSame($expected, $result);
    }

    public function testGetStructurePropagatesAuthException(): void
    {
        $this->plankaClient
            ->method('get')
            ->willThrowException(new AuthenticationException('Unauthorized'));

        $this->expectException(AuthenticationException::class);

        $this->service->getStructure('bad-key');
    }

    public function testGetStructurePropagatesApiException(): void
    {
        $this->plankaClient
            ->method('get')
            ->willThrowException(new PlankaApiException('Server error'));

        $this->expectException(PlankaApiException::class);

        $this->service->getStructure('test-api-key');
    }

    // --- createProject ---

    public function testCreateProjectSuccess(): void
    {
        $expected = ['item' => ['id' => 'proj1', 'name' => 'New Project']];

        $this->plankaClient
            ->expects($this->once())
            ->method('post')
            ->with('test-api-key', '/api/projects', ['name' => 'New Project'])
            ->willReturn($expected);

        $result = $this->service->createProject('test-api-key', 'New Project');

        $this->assertSame($expected, $result);
    }

    public function testCreateProjectPropagatesAuthException(): void
    {
        $this->plankaClient
            ->method('post')
            ->willThrowException(new AuthenticationException('Unauthorized'));

        $this->expectException(AuthenticationException::class);

        $this->service->createProject('bad-key', 'Name');
    }

    public function testCreateProjectPropagatesApiException(): void
    {
        $this->plankaClient
            ->method('post')
            ->willThrowException(new PlankaApiException('Server error'));

        $this->expectException(PlankaApiException::class);

        $this->service->createProject('test-api-key', 'Name');
    }

    // --- getProject ---

    public function testGetProjectSuccess(): void
    {
        $expected = ['item' => ['id' => 'proj1', 'name' => 'Project']];

        $this->plankaClient
            ->expects($this->once())
            ->method('get')
            ->with('test-api-key', '/api/projects/proj1')
            ->willReturn($expected);

        $result = $this->service->getProject('test-api-key', 'proj1');

        $this->assertSame($expected, $result);
    }

    public function testGetProjectPropagatesAuthException(): void
    {
        $this->plankaClient
            ->method('get')
            ->willThrowException(new AuthenticationException('Unauthorized'));

        $this->expectException(AuthenticationException::class);

        $this->service->getProject('bad-key', 'proj1');
    }

    public function testGetProjectPropagatesApiException(): void
    {
        $this->plankaClient
            ->method('get')
            ->willThrowException(new PlankaApiException('Server error'));

        $this->expectException(PlankaApiException::class);

        $this->service->getProject('test-api-key', 'proj1');
    }

    // --- updateProject ---

    public function testUpdateProjectWithNameAndDescription(): void
    {
        $expected = ['item' => ['id' => 'proj1', 'name' => 'Updated', 'description' => 'Desc']];

        $this->plankaClient
            ->expects($this->once())
            ->method('patch')
            ->with('test-api-key', '/api/projects/proj1', ['name' => 'Updated', 'description' => 'Desc'])
            ->willReturn($expected);

        $result = $this->service->updateProject('test-api-key', 'proj1', 'Updated', 'Desc');

        $this->assertSame($expected, $result);
    }

    public function testUpdateProjectWithNameOnly(): void
    {
        $expected = ['item' => ['id' => 'proj1', 'name' => 'Updated']];

        $this->plankaClient
            ->expects($this->once())
            ->method('patch')
            ->with('test-api-key', '/api/projects/proj1', ['name' => 'Updated'])
            ->willReturn($expected);

        $result = $this->service->updateProject('test-api-key', 'proj1', 'Updated');

        $this->assertSame($expected, $result);
    }

    public function testUpdateProjectWithNullNameAndNullDescriptionSendsEmptyBody(): void
    {
        $expected = ['item' => ['id' => 'proj1']];

        $this->plankaClient
            ->expects($this->once())
            ->method('patch')
            ->with('test-api-key', '/api/projects/proj1', [])
            ->willReturn($expected);

        $result = $this->service->updateProject('test-api-key', 'proj1', null, null);

        $this->assertSame($expected, $result);
    }

    public function testUpdateProjectPropagatesAuthException(): void
    {
        $this->plankaClient
            ->method('patch')
            ->willThrowException(new AuthenticationException('Unauthorized'));

        $this->expectException(AuthenticationException::class);

        $this->service->updateProject('bad-key', 'proj1', 'Name');
    }

    public function testUpdateProjectPropagatesApiException(): void
    {
        $this->plankaClient
            ->method('patch')
            ->willThrowException(new PlankaApiException('Server error'));

        $this->expectException(PlankaApiException::class);

        $this->service->updateProject('test-api-key', 'proj1', 'Name');
    }

    // --- deleteProject ---

    public function testDeleteProjectSuccess(): void
    {
        $this->plankaClient
            ->expects($this->once())
            ->method('delete')
            ->with('test-api-key', '/api/projects/proj1')
            ->willReturn([]);

        $result = $this->service->deleteProject('test-api-key', 'proj1');

        $this->assertSame([], $result);
    }

    public function testDeleteProjectPropagatesAuthException(): void
    {
        $this->plankaClient
            ->method('delete')
            ->willThrowException(new AuthenticationException('Unauthorized'));

        $this->expectException(AuthenticationException::class);

        $this->service->deleteProject('bad-key', 'proj1');
    }

    public function testDeleteProjectPropagatesApiException(): void
    {
        $this->plankaClient
            ->method('delete')
            ->willThrowException(new PlankaApiException('Server error'));

        $this->expectException(PlankaApiException::class);

        $this->service->deleteProject('test-api-key', 'proj1');
    }

    // --- addProjectManager ---

    public function testAddProjectManagerSuccess(): void
    {
        $expected = ['item' => ['id' => 'pm1', 'userId' => 'user1', 'projectId' => 'proj1']];

        $this->plankaClient
            ->expects($this->once())
            ->method('post')
            ->with('test-api-key', '/api/projects/proj1/project-managers', ['userId' => 'user1'])
            ->willReturn($expected);

        $result = $this->service->addProjectManager('test-api-key', 'proj1', 'user1');

        $this->assertSame($expected, $result);
    }

    public function testAddProjectManagerPropagatesAuthException(): void
    {
        $this->plankaClient
            ->method('post')
            ->willThrowException(new AuthenticationException('Unauthorized'));

        $this->expectException(AuthenticationException::class);

        $this->service->addProjectManager('bad-key', 'proj1', 'user1');
    }

    public function testAddProjectManagerPropagatesApiException(): void
    {
        $this->plankaClient
            ->method('post')
            ->willThrowException(new PlankaApiException('Server error'));

        $this->expectException(PlankaApiException::class);

        $this->service->addProjectManager('test-api-key', 'proj1', 'user1');
    }

    // --- removeProjectManager ---

    public function testRemoveProjectManagerSuccess(): void
    {
        $this->plankaClient
            ->expects($this->once())
            ->method('delete')
            ->with('test-api-key', '/api/projects/proj1/project-managers/userId:user1')
            ->willReturn([]);

        $result = $this->service->removeProjectManager('test-api-key', 'proj1', 'user1');

        $this->assertSame([], $result);
    }

    public function testRemoveProjectManagerPropagatesAuthException(): void
    {
        $this->plankaClient
            ->method('delete')
            ->willThrowException(new AuthenticationException('Unauthorized'));

        $this->expectException(AuthenticationException::class);

        $this->service->removeProjectManager('bad-key', 'proj1', 'user1');
    }

    public function testRemoveProjectManagerPropagatesApiException(): void
    {
        $this->plankaClient
            ->method('delete')
            ->willThrowException(new PlankaApiException('Server error'));

        $this->expectException(PlankaApiException::class);

        $this->service->removeProjectManager('test-api-key', 'proj1', 'user1');
    }
}
