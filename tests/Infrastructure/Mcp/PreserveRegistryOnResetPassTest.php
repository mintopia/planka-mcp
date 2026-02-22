<?php

declare(strict_types=1);

namespace App\Tests\Infrastructure\Mcp;

use App\Infrastructure\Mcp\PreserveRegistryOnResetPass;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;

#[\PHPUnit\Framework\Attributes\CoversClass(PreserveRegistryOnResetPass::class)]
final class PreserveRegistryOnResetPassTest extends TestCase
{
    private PreserveRegistryOnResetPass $pass;

    protected function setUp(): void
    {
        $this->pass = new PreserveRegistryOnResetPass();
    }

    public function testProcessDoesNothingWhenServiceNotRegistered(): void
    {
        $container = new ContainerBuilder();

        // Should return without error when mcp.traceable_registry does not exist
        $this->pass->process($container);

        $this->assertFalse($container->hasDefinition('mcp.traceable_registry'));
    }

    public function testProcessRemovesKernelResetTagWhenPresent(): void
    {
        $container = new ContainerBuilder();

        $definition = new Definition(\stdClass::class);
        $definition->addTag('kernel.reset', ['method' => 'reset']);
        $definition->addTag('some.other.tag');
        $container->setDefinition('mcp.traceable_registry', $definition);

        $this->pass->process($container);

        $tags = $container->getDefinition('mcp.traceable_registry')->getTags();

        $this->assertArrayNotHasKey('kernel.reset', $tags);
        $this->assertArrayHasKey('some.other.tag', $tags);
    }

    public function testProcessDoesNotErrorWhenServiceHasNoTags(): void
    {
        $container = new ContainerBuilder();

        $definition = new Definition(\stdClass::class);
        $container->setDefinition('mcp.traceable_registry', $definition);

        $this->pass->process($container);

        $tags = $container->getDefinition('mcp.traceable_registry')->getTags();

        $this->assertArrayNotHasKey('kernel.reset', $tags);
        $this->assertSame([], $tags);
    }
}
