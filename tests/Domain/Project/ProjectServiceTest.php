<?php

declare(strict_types=1);

namespace App\Tests\Domain\Project;

use App\Domain\Project\ProjectService;
use App\Planka\Client\PlankaClient;
use App\Planka\Exception\AuthenticationException;
use App\Planka\Exception\PlankaApiException;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

final class ProjectServiceTest extends TestCase
{
    private PlankaClient&MockObject $plankaClient;
    private ProjectService $service;

    protected function setUp(): void
    {
        $this->plankaClient = $this->createMock(PlankaClient::class);
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
}
