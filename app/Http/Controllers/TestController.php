<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Mcp\Servers\PlankaServer;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Laravel\Mcp\Request as McpRequest;
use Laravel\Mcp\Server\Attributes\Name;
use ReflectionClass;

final class TestController extends Controller
{
    public function callTool(Request $request): JsonResponse
    {
        $name      = (string) $request->input('name', '');
        $arguments = (array)  $request->input('arguments', []);

        $toolClass = $this->findByName('tools', $name);
        if ($toolClass === null) {
            return response()->json(['error' => "Tool not found: {$name}"], 404);
        }

        try {
            /** @var \Laravel\Mcp\Server\Tool $tool */
            $tool     = app()->make($toolClass);
            // @phpstan-ignore method.notFound (handle() is defined on all concrete Tool subclasses)
            $response = $tool->handle(new McpRequest(arguments: $arguments));
            return $this->toJson($response);
        } catch (\Throwable $e) {
            return response()->json(['error' => $e->getMessage()], 400);
        }
    }

    public function readResource(Request $request): JsonResponse
    {
        $name      = (string) $request->input('name', '');
        $arguments = (array)  $request->input('arguments', []);

        $resourceClass = $this->findByName('resources', $name);
        if ($resourceClass === null) {
            return response()->json(['error' => "Resource not found: {$name}"], 404);
        }

        try {
            /** @var \Laravel\Mcp\Server\Resource $resource */
            $resource = app()->make($resourceClass);
            // @phpstan-ignore method.notFound (handle() is defined on all concrete Resource subclasses)
            $response = $resource->handle(new McpRequest(arguments: $arguments));
            return $this->toJson($response);
        } catch (\Throwable $e) {
            return response()->json(['error' => $e->getMessage()], 400);
        }
    }

    private function findByName(string $property, string $name): ?string
    {
        $reflection = new ReflectionClass(PlankaServer::class);
        $prop = $reflection->getProperty($property);
        /** @var array<int, class-string> $classes */
        $classes = $prop->getDefaultValue();

        foreach ($classes as $class) {
            $classReflection = new ReflectionClass($class);
            $attrs = $classReflection->getAttributes(Name::class);
            if ($attrs !== [] && $attrs[0]->newInstance()->value === $name) {
                return $class;
            }
        }

        return null;
    }

    private function toJson(\Laravel\Mcp\Response $response): JsonResponse
    {
        $content = (string) $response->content();

        if ($response->isError()) {
            return response()->json(['error' => $content], 400);
        }

        try {
            $decoded = json_decode($content, true, 512, JSON_THROW_ON_ERROR);
            return response()->json($decoded);
        } catch (\JsonException) {
            return response()->json(['result' => $content]);
        }
    }
}
