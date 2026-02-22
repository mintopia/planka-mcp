<?php

declare(strict_types=1);

namespace App\Infrastructure\Mcp;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

final class PreserveRegistryOnResetPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container): void
    {
        if (!$container->hasDefinition('mcp.traceable_registry')) {
            return;
        }

        $definition = $container->getDefinition('mcp.traceable_registry');
        $tags = $definition->getTags();
        unset($tags['kernel.reset']);
        $definition->setTags($tags);
    }
}
