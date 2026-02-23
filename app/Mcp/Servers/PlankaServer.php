<?php

declare(strict_types=1);

namespace App\Mcp\Servers;

use App\Mcp\Resources\BoardActionsResource;
use App\Mcp\Resources\BoardResource;
use App\Mcp\Resources\BootstrapResource;
use App\Mcp\Resources\CardActionsResource;
use App\Mcp\Resources\CardCommentsResource;
use App\Mcp\Resources\CardResource;
use App\Mcp\Resources\ConfigResource;
use App\Mcp\Resources\CustomFieldGroupResource;
use App\Mcp\Resources\ListCardsResource;
use App\Mcp\Resources\ListResource;
use App\Mcp\Resources\NotificationResource;
use App\Mcp\Resources\NotificationsResource;
use App\Mcp\Resources\ProjectResource;
use App\Mcp\Resources\ProjectsResource;
use App\Mcp\Resources\StructureResource;
use App\Mcp\Resources\TaskListResource;
use App\Mcp\Resources\UserResource;
use App\Mcp\Resources\UsersResource;
use App\Mcp\Resources\WebhooksResource;
use App\Mcp\Tools\AddCommentTool;
use App\Mcp\Tools\CreateCardTool;
use App\Mcp\Tools\CreateTasksTool;
use App\Mcp\Tools\DeleteCardTool;
use App\Mcp\Tools\DeleteCommentTool;
use App\Mcp\Tools\DeleteTaskListTool;
use App\Mcp\Tools\DeleteTaskTool;
use App\Mcp\Tools\DuplicateCardTool;
use App\Mcp\Tools\GetActionsTool;
use App\Mcp\Tools\GetBoardTool;
use App\Mcp\Tools\GetCardTool;
use App\Mcp\Tools\GetCommentsTool;
use App\Mcp\Tools\GetNotificationsTool;
use App\Mcp\Tools\GetStructureTool;
use App\Mcp\Tools\ManageAttachmentsTool;
use App\Mcp\Tools\ManageBoardMembershipsTool;
use App\Mcp\Tools\ManageBoardsTool;
use App\Mcp\Tools\ManageCardMembershipTool;
use App\Mcp\Tools\ManageCustomFieldGroupsTool;
use App\Mcp\Tools\ManageCustomFieldsTool;
use App\Mcp\Tools\ManageCustomFieldValuesTool;
use App\Mcp\Tools\ManageLabelsTool;
use App\Mcp\Tools\ManageListsTool;
use App\Mcp\Tools\ManageNotificationServicesTool;
use App\Mcp\Tools\ManageProjectManagersTool;
use App\Mcp\Tools\ManageProjectsTool;
use App\Mcp\Tools\ManageUserCredentialsTool;
use App\Mcp\Tools\ManageUsersTool;
use App\Mcp\Tools\ManageWebhooksTool;
use App\Mcp\Tools\MarkAllNotificationsReadTool;
use App\Mcp\Tools\MarkNotificationReadTool;
use App\Mcp\Tools\MoveCardTool;
use App\Mcp\Tools\SetCardLabelsTool;
use App\Mcp\Tools\SortListTool;
use App\Mcp\Tools\UpdateCardTool;
use App\Mcp\Tools\UpdateCommentTool;
use App\Mcp\Tools\UpdateTaskListTool;
use App\Mcp\Tools\UpdateTaskTool;
use App\Mcp\Tools\UploadAttachmentTool;
use Laravel\Mcp\Server;
use Laravel\Mcp\Server\Attributes\Instructions;
use Laravel\Mcp\Server\Attributes\Name;
use Laravel\Mcp\Server\Attributes\Version;

#[Name('Planka MCP')]
#[Version('1.0.0')]
#[Instructions('This MCP server provides tools for interacting with the Planka project management API. Use these tools to manage projects, boards, lists, cards, tasks, labels, comments, attachments, users, notifications, webhooks, and custom fields.')]
final class PlankaServer extends Server
{
    /** @var array<int, class-string<\Laravel\Mcp\Server\Resource>> */
    protected array $resources = [
        // System resources
        ConfigResource::class,
        BootstrapResource::class,

        // Project resources
        StructureResource::class,
        ProjectsResource::class,
        ProjectResource::class,

        // Board resources
        BoardResource::class,
        BoardActionsResource::class,

        // List resources
        ListResource::class,
        ListCardsResource::class,

        // Card resources
        CardResource::class,
        CardCommentsResource::class,
        CardActionsResource::class,

        // Task resources
        TaskListResource::class,

        // Custom field resources
        CustomFieldGroupResource::class,

        // User resources
        UsersResource::class,
        UserResource::class,

        // Notification resources
        NotificationsResource::class,
        NotificationResource::class,

        // Webhook resources
        WebhooksResource::class,
    ];

    /** @var array<int, class-string<\Laravel\Mcp\Server\Tool>> */
    protected array $tools = [
        // Action tools
        GetActionsTool::class,

        // Attachment tools
        UploadAttachmentTool::class,
        ManageAttachmentsTool::class,

        // Board tools
        GetStructureTool::class,
        GetBoardTool::class,
        ManageBoardsTool::class,
        ManageBoardMembershipsTool::class,

        // Card tools
        CreateCardTool::class,
        GetCardTool::class,
        UpdateCardTool::class,
        MoveCardTool::class,
        DeleteCardTool::class,
        DuplicateCardTool::class,
        ManageCardMembershipTool::class,

        // Comment tools
        AddCommentTool::class,
        GetCommentsTool::class,
        UpdateCommentTool::class,
        DeleteCommentTool::class,

        // Custom field tools
        ManageCustomFieldGroupsTool::class,
        ManageCustomFieldsTool::class,
        ManageCustomFieldValuesTool::class,

        // Label tools
        ManageLabelsTool::class,
        SetCardLabelsTool::class,

        // List tools
        ManageListsTool::class,
        SortListTool::class,

        // Notification channel tools
        ManageNotificationServicesTool::class,

        // Notification tools
        GetNotificationsTool::class,
        MarkNotificationReadTool::class,
        MarkAllNotificationsReadTool::class,

        // Project tools
        ManageProjectsTool::class,
        ManageProjectManagersTool::class,

        // Task tools
        CreateTasksTool::class,
        UpdateTaskTool::class,
        DeleteTaskTool::class,
        UpdateTaskListTool::class,
        DeleteTaskListTool::class,

        // User tools
        ManageUsersTool::class,
        ManageUserCredentialsTool::class,

        // Webhook tools
        ManageWebhooksTool::class,
    ];
}
