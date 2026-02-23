<?php

declare(strict_types=1);

namespace App\Tests;

use App\Kernel;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

#[\PHPUnit\Framework\Attributes\CoversClass(Kernel::class)]
final class KernelTest extends KernelTestCase
{
    public function testKernelBootsAndRunsBuildMethod(): void
    {
        // Remove the cached container to force recompilation, ensuring build() is executed
        $kernel = new Kernel('test', true);
        $cacheDir = $kernel->getCacheDir();

        foreach (glob($cacheDir . '/App_Kernel*.php') ?: [] as $file) {
            @unlink($file);
        }
        foreach (glob($cacheDir . '/App_Kernel*.php.meta') ?: [] as $file) {
            @unlink($file);
        }
        foreach (glob($cacheDir . '/App_Kernel*.ser') ?: [] as $file) {
            @unlink($file);
        }

        $kernel->boot();

        $container = $kernel->getContainer();
        $this->assertTrue($container->has('kernel'));

        $kernel->shutdown();
    }
}
