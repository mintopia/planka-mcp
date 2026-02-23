<?php

declare(strict_types=1);

namespace Tests\Feature;

use Tests\TestCase;

final class HomeControllerTest extends TestCase
{
    public function testHomePageReturns200(): void
    {
        $response = $this->get('/');
        $response->assertStatus(200);
    }

    public function testHomePageHasContentSecurityPolicyHeader(): void
    {
        $response = $this->get('/');
        $response->assertHeader('Content-Security-Policy');
    }

    public function testHomePageCspAllowsSelfConnectSrc(): void
    {
        $response = $this->get('/');
        $csp = $response->headers->get('Content-Security-Policy');
        $this->assertNotNull($csp);
        $this->assertStringContainsString("default-src 'self'", $csp);
    }

    public function testHomePageHasXContentTypeOptionsHeader(): void
    {
        $response = $this->get('/');
        $response->assertHeader('X-Content-Type-Options', 'nosniff');
    }

    public function testHomePageHasXFrameOptionsHeader(): void
    {
        $response = $this->get('/');
        $response->assertHeader('X-Frame-Options', 'DENY');
    }

    public function testHomePageContainsToolsAndResources(): void
    {
        $response = $this->get('/');
        $response->assertViewHas('tools');
        $response->assertViewHas('resources');
        $response->assertViewHas('sections');
        $response->assertViewHas('mcpUrl');
    }

    public function testToolsArrayIsNotEmpty(): void
    {
        $response = $this->get('/');
        $tools = $response->viewData('tools');
        $this->assertIsArray($tools);
        $this->assertNotEmpty($tools);
    }

    public function testResourcesArrayIsNotEmpty(): void
    {
        $response = $this->get('/');
        $resources = $response->viewData('resources');
        $this->assertIsArray($resources);
        $this->assertNotEmpty($resources);
    }

    public function testSectionsAreGroupedCorrectly(): void
    {
        $response = $this->get('/');
        $sections = $response->viewData('sections');
        $this->assertIsArray($sections);
        $this->assertNotEmpty($sections);

        // Each section must have title, resources, tools
        foreach ($sections as $section) {
            $this->assertArrayHasKey('title', $section);
            $this->assertArrayHasKey('resources', $section);
            $this->assertArrayHasKey('tools', $section);
        }
    }

    public function testHomePageCspInNonLocalMode(): void
    {
        $this->app['env'] = 'production';
        $response = $this->get('/');
        $csp = $response->headers->get('Content-Security-Policy');
        $this->assertNotNull($csp);
        $this->assertStringContainsString("connect-src 'self'", $csp);
        $this->assertStringNotContainsString('localhost:5173', $csp);
    }

    public function testMcpUrlContainsMcpPath(): void
    {
        $response = $this->get('/');
        $mcpUrl = $response->viewData('mcpUrl');
        $this->assertStringEndsWith('/mcp', $mcpUrl);
    }

    public function testToolsHaveRequiredFields(): void
    {
        $response = $this->get('/');
        $tools = $response->viewData('tools');

        foreach ($tools as $tool) {
            $this->assertArrayHasKey('name', $tool);
            $this->assertArrayHasKey('description', $tool);
            $this->assertArrayHasKey('icon', $tool);
            $this->assertArrayHasKey('parameters', $tool);
            $this->assertIsArray($tool['parameters']);
        }
    }

    public function testResourcesHaveRequiredFields(): void
    {
        $response = $this->get('/');
        $resources = $response->viewData('resources');

        foreach ($resources as $resource) {
            $this->assertArrayHasKey('name', $resource);
            $this->assertArrayHasKey('description', $resource);
            $this->assertArrayHasKey('uri', $resource);
            $this->assertArrayHasKey('isTemplate', $resource);
        }
    }
}
