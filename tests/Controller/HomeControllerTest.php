<?php

declare(strict_types=1);

namespace App\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

final class HomeControllerTest extends WebTestCase
{
    public function testHomePageReturnsSuccessfulResponse(): void
    {
        $client = static::createClient();
        $client->request('GET', '/');

        $this->assertResponseIsSuccessful();
    }

    public function testHomePageReturnsHtmlContentType(): void
    {
        $client = static::createClient();
        $client->request('GET', '/');

        $this->assertResponseHeaderSame('Content-Type', 'text/html; charset=UTF-8');
    }

    public function testHomePageContainsMcpUrl(): void
    {
        $client = static::createClient();
        $client->request('GET', '/');

        $content = (string) $client->getResponse()->getContent();
        $this->assertStringContainsString('/_mcp', $content);
    }

    public function testHomePageContainsPlankaMcpHeading(): void
    {
        $client = static::createClient();
        $client->request('GET', '/');

        $content = (string) $client->getResponse()->getContent();
        $this->assertStringContainsString('Planka MCP', $content);
    }

    public function testHomePageContainsAvailableToolsSection(): void
    {
        $client = static::createClient();
        $client->request('GET', '/');

        $content = (string) $client->getResponse()->getContent();
        $this->assertStringContainsString('Available Tools', $content);
    }

    public function testHomePageHasXContentTypeOptionsHeader(): void
    {
        $client = static::createClient();
        $client->request('GET', '/');

        $this->assertResponseHeaderSame('X-Content-Type-Options', 'nosniff');
    }

    public function testHomePageHasXFrameOptionsHeader(): void
    {
        $client = static::createClient();
        $client->request('GET', '/');

        $this->assertResponseHeaderSame('X-Frame-Options', 'DENY');
    }

    public function testHomePageHasContentSecurityPolicyHeader(): void
    {
        $client = static::createClient();
        $client->request('GET', '/');

        $csp = $client->getResponse()->headers->get('Content-Security-Policy');
        $this->assertNotNull($csp);
        $this->assertStringContainsString("default-src 'self'", $csp);
    }

    public function testHomePageHasReferrerPolicyHeader(): void
    {
        $client = static::createClient();
        $client->request('GET', '/');

        $referrerPolicy = $client->getResponse()->headers->get('Referrer-Policy');
        $this->assertNotNull($referrerPolicy);
        $this->assertSame('strict-origin-when-cross-origin', $referrerPolicy);
    }

    public function testMcpUrlReflectsRequestHost(): void
    {
        $client = static::createClient();
        $client->request('GET', '/');

        $content = (string) $client->getResponse()->getContent();
        $this->assertStringContainsString('http://localhost/_mcp', $content);
    }
}
