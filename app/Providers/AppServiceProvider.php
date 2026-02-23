<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(\App\Planka\PlankaClientInterface::class, function ($app) {
            return new \App\Planka\PlankaClient(
                plankaUrl: config('planka.url'),
            );
        });

        $this->app->bind(\App\Http\ApiKeyProviderInterface::class, \App\Http\ApiKeyProvider::class);
        $this->app->bind(\App\Domain\Action\ActionServiceInterface::class, \App\Domain\Action\ActionService::class);
        $this->app->bind(\App\Domain\Attachment\AttachmentServiceInterface::class, \App\Domain\Attachment\AttachmentService::class);
        $this->app->bind(\App\Domain\Board\BoardServiceInterface::class, \App\Domain\Board\BoardService::class);
        $this->app->bind(\App\Domain\BoardList\ListServiceInterface::class, \App\Domain\BoardList\ListService::class);
        $this->app->bind(\App\Domain\Card\CardServiceInterface::class, \App\Domain\Card\CardService::class);
        $this->app->bind(\App\Domain\Comment\CommentServiceInterface::class, \App\Domain\Comment\CommentService::class);
        $this->app->bind(\App\Domain\CustomField\CustomFieldServiceInterface::class, \App\Domain\CustomField\CustomFieldService::class);
        $this->app->bind(\App\Domain\Label\LabelServiceInterface::class, \App\Domain\Label\LabelService::class);
        $this->app->bind(\App\Domain\Notification\NotificationServiceInterface::class, \App\Domain\Notification\NotificationService::class);
        $this->app->bind(\App\Domain\NotificationChannel\NotificationChannelServiceInterface::class, \App\Domain\NotificationChannel\NotificationChannelService::class);
        $this->app->bind(\App\Domain\Project\ProjectServiceInterface::class, \App\Domain\Project\ProjectService::class);
        $this->app->bind(\App\Domain\Task\TaskServiceInterface::class, \App\Domain\Task\TaskService::class);
        $this->app->bind(\App\Domain\User\UserServiceInterface::class, \App\Domain\User\UserService::class);
        $this->app->bind(\App\Domain\Webhook\WebhookServiceInterface::class, \App\Domain\Webhook\WebhookService::class);
        $this->app->bind(\App\Domain\System\SystemServiceInterface::class, \App\Domain\System\SystemService::class);

        $this->app->singleton(\App\Subscription\SubscriptionRegistry::class);

        $this->app->when(\App\Mcp\Tools\UploadAttachmentTool::class)
            ->needs('$uploadDir')
            ->give(storage_path('app/uploads'));
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $uploadDir = storage_path('app/uploads');
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }
    }
}
