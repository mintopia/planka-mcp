<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Planka\PlankaClient;
use App\Planka\PlankaClientInterface;
use App\Providers\AppServiceProvider;
use Tests\TestCase;

final class AppServiceProviderTest extends TestCase
{
    public function testPlankaClientBindingResolvesToPlankaClient(): void
    {
        // Resolve PlankaClientInterface from the container — this triggers the closure
        $client = $this->app->make(PlankaClientInterface::class);
        $this->assertInstanceOf(PlankaClient::class, $client);
    }

    public function testBootCreatesUploadDirectoryIfMissing(): void
    {
        $uploadDir = storage_path('app/uploads');

        // Remove the directory to force the mkdir branch to execute
        if (is_dir($uploadDir)) {
            rmdir($uploadDir);
        }

        $this->assertDirectoryDoesNotExist($uploadDir);

        // Directly invoke boot() to trigger the mkdir branch
        $provider = new AppServiceProvider($this->app);
        $provider->boot();

        $this->assertDirectoryExists($uploadDir);
    }
}
