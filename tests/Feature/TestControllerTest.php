<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Domain\Board\BoardServiceInterface;
use App\Domain\Project\ProjectServiceInterface;
use App\Planka\Exception\AuthenticationException;
use Tests\TestCase;

final class TestControllerTest extends TestCase
{
    public function testCallToolReturns200ForValidRequest(): void
    {
        $mock = $this->createMock(ProjectServiceInterface::class);
        $mock->expects($this->once())
            ->method('getStructure')
            ->with('test-key')
            ->willReturn(['items' => []]);
        $this->instance(ProjectServiceInterface::class, $mock);

        $response = $this->postJson('/test/tool', [
            'name' => 'planka_get_structure',
            'arguments' => [],
        ], [
            'Authorization' => 'Bearer test-key',
        ]);

        $response->assertStatus(200);
        $response->assertJsonMissing(['error']);
    }

    public function testCallToolReturns404ForUnknownTool(): void
    {
        $response = $this->postJson('/test/tool', [
            'name' => 'nonexistent_tool',
            'arguments' => [],
        ], [
            'Authorization' => 'Bearer test-key',
        ]);

        $response->assertStatus(404);
        $response->assertJson(['error' => 'Tool not found: nonexistent_tool']);
    }

    public function testCallToolReturns400WhenNoApiKey(): void
    {
        $response = $this->postJson('/test/tool', [
            'name' => 'planka_get_structure',
            'arguments' => [],
        ]);

        $response->assertStatus(400);
        $response->assertJsonStructure(['error']);
    }

    public function testCallToolReturns400OnPlankaError(): void
    {
        $mock = $this->createMock(ProjectServiceInterface::class);
        $mock->expects($this->once())
            ->method('getStructure')
            ->willThrowException(new AuthenticationException('Invalid or missing Planka API key.', 401));
        $this->instance(ProjectServiceInterface::class, $mock);

        $response = $this->postJson('/test/tool', [
            'name' => 'planka_get_structure',
            'arguments' => [],
        ], [
            'Authorization' => 'Bearer test-key',
        ]);

        $response->assertStatus(400);
        $response->assertJsonStructure(['error']);
    }

    public function testReadResourceReturns200ForStaticResource(): void
    {
        $mock = $this->createMock(ProjectServiceInterface::class);
        $mock->expects($this->once())
            ->method('getStructure')
            ->with('test-key')
            ->willReturn(['items' => []]);
        $this->instance(ProjectServiceInterface::class, $mock);

        $response = $this->postJson('/test/resource', [
            'name' => 'planka-structure',
            'arguments' => [],
        ], [
            'Authorization' => 'Bearer test-key',
        ]);

        $response->assertStatus(200);
    }

    public function testReadResourceReturns200ForTemplateResource(): void
    {
        $mock = $this->createMock(BoardServiceInterface::class);
        $mock->expects($this->once())
            ->method('getBoard')
            ->with('test-key', 'board-123')
            ->willReturn(['item' => ['id' => 'board-123']]);
        $this->instance(BoardServiceInterface::class, $mock);

        $response = $this->postJson('/test/resource', [
            'name' => 'planka-board',
            'arguments' => ['boardId' => 'board-123'],
        ], [
            'Authorization' => 'Bearer test-key',
        ]);

        $response->assertStatus(200);
    }

    public function testCallToolReturns400WhenInstantiationFails(): void
    {
        $this->app->bind(\App\Mcp\Tools\GetStructureTool::class, function () {
            throw new \RuntimeException('Container instantiation failure');
        });

        $response = $this->postJson('/test/tool', [
            'name' => 'planka_get_structure',
            'arguments' => [],
        ], [
            'Authorization' => 'Bearer test-key',
        ]);

        $response->assertStatus(400);
        $response->assertJson(['error' => 'Container instantiation failure']);
    }

    public function testReadResourceReturns400WhenInstantiationFails(): void
    {
        $this->app->bind(\App\Mcp\Resources\StructureResource::class, function () {
            throw new \RuntimeException('Resource instantiation failure');
        });

        $response = $this->postJson('/test/resource', [
            'name' => 'planka-structure',
            'arguments' => [],
        ], [
            'Authorization' => 'Bearer test-key',
        ]);

        $response->assertStatus(400);
        $response->assertJson(['error' => 'Resource instantiation failure']);
    }

    public function testCallToolReturns200WithPlainTextContent(): void
    {
        $this->app->bind(\App\Mcp\Tools\GetStructureTool::class, function () {
            return new class {
                public function handle(\Laravel\Mcp\Request $request): \Laravel\Mcp\Response
                {
                    return \Laravel\Mcp\Response::text('plain text, not json');
                }
            };
        });

        $response = $this->postJson('/test/tool', [
            'name' => 'planka_get_structure',
            'arguments' => [],
        ], [
            'Authorization' => 'Bearer test-key',
        ]);

        $response->assertStatus(200);
        $response->assertJson(['result' => 'plain text, not json']);
    }

    public function testReadResourceReturns404ForUnknownResource(): void
    {
        $response = $this->postJson('/test/resource', [
            'name' => 'nonexistent-resource',
            'arguments' => [],
        ], [
            'Authorization' => 'Bearer test-key',
        ]);

        $response->assertStatus(404);
        $response->assertJson(['error' => 'Resource not found: nonexistent-resource']);
    }

    public function testReadResourceReturns400WhenNoApiKey(): void
    {
        $response = $this->postJson('/test/resource', [
            'name' => 'planka-structure',
            'arguments' => [],
        ]);

        $response->assertStatus(400);
        $response->assertJsonStructure(['error']);
    }
}
