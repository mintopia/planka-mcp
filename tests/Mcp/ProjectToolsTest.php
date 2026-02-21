<?php

declare(strict_types=1);

namespace App\Tests\Mcp;

use App\Domain\Project\ProjectService;
use App\Infrastructure\Http\ApiKeyProvider;
use App\Mcp\ProjectTools;
use App\Planka\Exception\AuthenticationException;
use App\Planka\Exception\PlankaApiException;
use App\Planka\Exception\PlankaNotFoundException;
use App\Shared\Exception\ValidationException;
use Mcp\Exception\ToolCallException;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

final class ProjectToolsTest extends TestCase
{
    private ProjectService&MockObject $projectService;
    private ApiKeyProvider&MockObject $apiKeyProvider;
    private ProjectTools $tools;

    protected function setUp(): void
    {
        $this->projectService = $this->createMock(ProjectService::class);
        $this->apiKeyProvider = $this->createMock(ApiKeyProvider::class);
        $this->tools = new ProjectTools($this->projectService, $this->apiKeyProvider);
    }

    // -------------------------------------------------------------------------
    // manageProjects: create
    // -------------------------------------------------------------------------

    public function testManageProjectsCreateSuccess(): void
    {
        $expected = ['item' => ['id' => 'proj1', 'name' => 'My Project']];

        $this->apiKeyProvider
            ->expects($this->once())
            ->method('getApiKey')
            ->willReturn('test-api-key');

        $this->projectService
            ->expects($this->once())
            ->method('createProject')
            ->with('test-api-key', 'My Project')
            ->willReturn($expected);

        $result = $this->tools->manageProjects('create', null, 'My Project');

        $this->assertSame($expected, $result);
    }

    public function testManageProjectsCreateWithoutNameThrowsToolCallException(): void
    {
        $this->apiKeyProvider->method('getApiKey')->willReturn('test-api-key');
        $this->projectService->expects($this->never())->method('createProject');

        $this->expectException(ToolCallException::class);
        $this->expectExceptionMessage('name required for create');

        $this->tools->manageProjects('create');
    }

    // -------------------------------------------------------------------------
    // manageProjects: get
    // -------------------------------------------------------------------------

    public function testManageProjectsGetSuccess(): void
    {
        $expected = ['item' => ['id' => 'proj1', 'name' => 'My Project']];

        $this->apiKeyProvider
            ->expects($this->once())
            ->method('getApiKey')
            ->willReturn('test-api-key');

        $this->projectService
            ->expects($this->once())
            ->method('getProject')
            ->with('test-api-key', 'proj1')
            ->willReturn($expected);

        $result = $this->tools->manageProjects('get', 'proj1');

        $this->assertSame($expected, $result);
    }

    public function testManageProjectsGetWithoutProjectIdThrowsToolCallException(): void
    {
        $this->apiKeyProvider->method('getApiKey')->willReturn('test-api-key');
        $this->projectService->expects($this->never())->method('getProject');

        $this->expectException(ToolCallException::class);
        $this->expectExceptionMessage('projectId required for get');

        $this->tools->manageProjects('get');
    }

    // -------------------------------------------------------------------------
    // manageProjects: update
    // -------------------------------------------------------------------------

    public function testManageProjectsUpdateSuccess(): void
    {
        $expected = ['item' => ['id' => 'proj1', 'name' => 'Updated', 'description' => 'Desc']];

        $this->apiKeyProvider
            ->expects($this->once())
            ->method('getApiKey')
            ->willReturn('test-api-key');

        $this->projectService
            ->expects($this->once())
            ->method('updateProject')
            ->with('test-api-key', 'proj1', 'Updated', 'Desc')
            ->willReturn($expected);

        $result = $this->tools->manageProjects('update', 'proj1', 'Updated', 'Desc');

        $this->assertSame($expected, $result);
    }

    public function testManageProjectsUpdateWithoutProjectIdThrowsToolCallException(): void
    {
        $this->apiKeyProvider->method('getApiKey')->willReturn('test-api-key');
        $this->projectService->expects($this->never())->method('updateProject');

        $this->expectException(ToolCallException::class);
        $this->expectExceptionMessage('projectId required for update');

        $this->tools->manageProjects('update', null, 'Name');
    }

    // -------------------------------------------------------------------------
    // manageProjects: delete
    // -------------------------------------------------------------------------

    public function testManageProjectsDeleteSuccess(): void
    {
        $this->apiKeyProvider
            ->expects($this->once())
            ->method('getApiKey')
            ->willReturn('test-api-key');

        $this->projectService
            ->expects($this->once())
            ->method('deleteProject')
            ->with('test-api-key', 'proj1')
            ->willReturn([]);

        $result = $this->tools->manageProjects('delete', 'proj1');

        $this->assertSame([], $result);
    }

    public function testManageProjectsDeleteWithoutProjectIdThrowsToolCallException(): void
    {
        $this->apiKeyProvider->method('getApiKey')->willReturn('test-api-key');
        $this->projectService->expects($this->never())->method('deleteProject');

        $this->expectException(ToolCallException::class);
        $this->expectExceptionMessage('projectId required for delete');

        $this->tools->manageProjects('delete');
    }

    // -------------------------------------------------------------------------
    // manageProjects: invalid action
    // -------------------------------------------------------------------------

    public function testManageProjectsInvalidActionThrowsToolCallException(): void
    {
        $this->apiKeyProvider->method('getApiKey')->willReturn('test-api-key');

        $this->expectException(ToolCallException::class);
        $this->expectExceptionMessage('Invalid action "archive". Must be: create, get, update, delete');

        $this->tools->manageProjects('archive');
    }

    // -------------------------------------------------------------------------
    // manageProjects: missing API key
    // -------------------------------------------------------------------------

    public function testManageProjectsMissingApiKeyThrowsToolCallException(): void
    {
        $this->apiKeyProvider
            ->expects($this->once())
            ->method('getApiKey')
            ->willThrowException(new ValidationException('Planka API key required.'));

        $this->projectService->expects($this->never())->method('createProject');

        $this->expectException(ToolCallException::class);
        $this->expectExceptionMessage('Planka API key required.');

        $this->tools->manageProjects('create', null, 'Name');
    }

    // -------------------------------------------------------------------------
    // manageProjects: exception wrapping
    // -------------------------------------------------------------------------

    public function testManageProjectsWrapsAuthExceptionInToolCallException(): void
    {
        $this->apiKeyProvider->method('getApiKey')->willReturn('bad-key');

        $this->projectService
            ->method('createProject')
            ->willThrowException(new AuthenticationException('Unauthorized'));

        $this->expectException(ToolCallException::class);
        $this->expectExceptionMessage('Unauthorized');

        $this->tools->manageProjects('create', null, 'Name');
    }

    public function testManageProjectsWrapsPlankaApiExceptionInToolCallException(): void
    {
        $this->apiKeyProvider->method('getApiKey')->willReturn('test-api-key');

        $this->projectService
            ->method('getProject')
            ->willThrowException(new PlankaApiException('Server error'));

        $this->expectException(ToolCallException::class);
        $this->expectExceptionMessage('Server error');

        $this->tools->manageProjects('get', 'proj1');
    }

    public function testManageProjectsWrapsNotFoundExceptionInToolCallException(): void
    {
        $this->apiKeyProvider->method('getApiKey')->willReturn('test-api-key');

        $this->projectService
            ->method('getProject')
            ->willThrowException(new PlankaNotFoundException('Not found', 404));

        $this->expectException(ToolCallException::class);
        $this->expectExceptionMessage('Not found');

        $this->tools->manageProjects('get', 'proj1');
    }

    // -------------------------------------------------------------------------
    // manageProjectManagers: add
    // -------------------------------------------------------------------------

    public function testManageProjectManagersAddSuccess(): void
    {
        $expected = ['item' => ['id' => 'pm1', 'userId' => 'user1', 'projectId' => 'proj1']];

        $this->apiKeyProvider
            ->expects($this->once())
            ->method('getApiKey')
            ->willReturn('test-api-key');

        $this->projectService
            ->expects($this->once())
            ->method('addProjectManager')
            ->with('test-api-key', 'proj1', 'user1')
            ->willReturn($expected);

        $result = $this->tools->manageProjectManagers('add', 'proj1', 'user1');

        $this->assertSame($expected, $result);
    }

    // -------------------------------------------------------------------------
    // manageProjectManagers: remove
    // -------------------------------------------------------------------------

    public function testManageProjectManagersRemoveSuccess(): void
    {
        $this->apiKeyProvider
            ->expects($this->once())
            ->method('getApiKey')
            ->willReturn('test-api-key');

        $this->projectService
            ->expects($this->once())
            ->method('removeProjectManager')
            ->with('test-api-key', 'proj1', 'user1')
            ->willReturn([]);

        $result = $this->tools->manageProjectManagers('remove', 'proj1', 'user1');

        $this->assertSame([], $result);
    }

    // -------------------------------------------------------------------------
    // manageProjectManagers: invalid action
    // -------------------------------------------------------------------------

    public function testManageProjectManagersInvalidActionThrowsToolCallException(): void
    {
        $this->apiKeyProvider->method('getApiKey')->willReturn('test-api-key');

        $this->expectException(ToolCallException::class);
        $this->expectExceptionMessage('Invalid action "update". Must be: add, remove');

        $this->tools->manageProjectManagers('update', 'proj1', 'user1');
    }

    // -------------------------------------------------------------------------
    // manageProjectManagers: missing API key
    // -------------------------------------------------------------------------

    public function testManageProjectManagersMissingApiKeyThrowsToolCallException(): void
    {
        $this->apiKeyProvider
            ->expects($this->once())
            ->method('getApiKey')
            ->willThrowException(new ValidationException('Planka API key required.'));

        $this->projectService->expects($this->never())->method('addProjectManager');

        $this->expectException(ToolCallException::class);
        $this->expectExceptionMessage('Planka API key required.');

        $this->tools->manageProjectManagers('add', 'proj1', 'user1');
    }

    // -------------------------------------------------------------------------
    // manageProjectManagers: exception wrapping
    // -------------------------------------------------------------------------

    public function testManageProjectManagersWrapsAuthExceptionInToolCallException(): void
    {
        $this->apiKeyProvider->method('getApiKey')->willReturn('bad-key');

        $this->projectService
            ->method('addProjectManager')
            ->willThrowException(new AuthenticationException('Unauthorized'));

        $this->expectException(ToolCallException::class);
        $this->expectExceptionMessage('Unauthorized');

        $this->tools->manageProjectManagers('add', 'proj1', 'user1');
    }

    public function testManageProjectManagersWrapsPlankaApiExceptionInToolCallException(): void
    {
        $this->apiKeyProvider->method('getApiKey')->willReturn('test-api-key');

        $this->projectService
            ->method('removeProjectManager')
            ->willThrowException(new PlankaApiException('Server error'));

        $this->expectException(ToolCallException::class);
        $this->expectExceptionMessage('Server error');

        $this->tools->manageProjectManagers('remove', 'proj1', 'user1');
    }

    public function testManageProjectManagersWrapsNotFoundExceptionInToolCallException(): void
    {
        $this->apiKeyProvider->method('getApiKey')->willReturn('test-api-key');

        $this->projectService
            ->method('addProjectManager')
            ->willThrowException(new PlankaNotFoundException('Not found', 404));

        $this->expectException(ToolCallException::class);
        $this->expectExceptionMessage('Not found');

        $this->tools->manageProjectManagers('add', 'proj1', 'user1');
    }
}
