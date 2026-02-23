<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Mcp\Servers\PlankaServer;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Attributes\Name;
use Laravel\Mcp\Server\Attributes\Uri;
use Laravel\Mcp\Server\Contracts\HasUriTemplate;
use ReflectionClass;

final class HomeController extends Controller
{
    /** @var array<string, string> */
    private const TOOL_ICONS = [
        'planka_get_structure' => '&#128202;',
        'planka_get_board' => '&#128243;',
        'planka_manage_projects' => '&#128193;',
        'planka_manage_project_managers' => '&#128100;',
        'planka_manage_boards' => '&#128203;',
        'planka_manage_board_memberships' => '&#128101;',
        'planka_manage_lists' => '&#128262;',
        'planka_sort_list' => '&#128280;',
        'planka_create_card' => '&#10133;',
        'planka_get_card' => '&#128196;',
        'planka_update_card' => '&#9998;',
        'planka_move_card' => '&#8651;',
        'planka_delete_card' => '&#128465;',
        'planka_duplicate_card' => '&#128203;',
        'planka_manage_card_membership' => '&#128104;',
        'planka_manage_labels' => '&#127991;',
        'planka_set_card_labels' => '&#128204;',
        'planka_create_tasks' => '&#9989;',
        'planka_update_task' => '&#128221;',
        'planka_delete_task' => '&#128465;',
        'planka_update_task_list' => '&#9997;',
        'planka_delete_task_list' => '&#128468;',
        'planka_add_comment' => '&#128172;',
        'planka_get_comments' => '&#128444;',
        'planka_update_comment' => '&#9998;',
        'planka_delete_comment' => '&#128465;',
        'planka_upload_attachment' => '&#128206;',
        'planka_manage_attachments' => '&#128247;',
        'planka_manage_users' => '&#128106;',
        'planka_manage_user_credentials' => '&#128272;',
        'planka_get_notifications' => '&#128276;',
        'planka_mark_notification_read' => '&#128277;',
        'planka_mark_all_notifications_read' => '&#128276;',
        'planka_manage_custom_field_groups' => '&#128218;',
        'planka_manage_custom_fields' => '&#128295;',
        'planka_manage_custom_field_values' => '&#128190;',
        'planka_manage_webhooks' => '&#128279;',
        'planka_get_actions' => '&#128240;',
        'planka_manage_notification_services' => '&#128268;',
    ];

    /**
     * @var array<string, array{resources: list<string>, tools: list<string>}>
     */
    private const ENTITY_GROUPS = [
        'System' => [
            'resources' => ['planka-config', 'planka-bootstrap'],
            'tools' => [],
        ],
        'Projects' => [
            'resources' => ['planka-structure', 'planka-projects', 'planka-project'],
            'tools' => ['planka_get_structure', 'planka_manage_projects', 'planka_manage_project_managers'],
        ],
        'Boards' => [
            'resources' => ['planka-board', 'planka-board-actions'],
            'tools' => ['planka_get_board', 'planka_manage_boards', 'planka_manage_board_memberships', 'planka_get_actions'],
        ],
        'Lists' => [
            'resources' => ['planka-list', 'planka-list-cards'],
            'tools' => ['planka_manage_lists', 'planka_sort_list'],
        ],
        'Cards' => [
            'resources' => ['planka-card', 'planka-card-comments', 'planka-card-actions'],
            'tools' => ['planka_create_card', 'planka_get_card', 'planka_update_card', 'planka_move_card', 'planka_delete_card', 'planka_duplicate_card', 'planka_manage_card_membership'],
        ],
        'Labels' => [
            'resources' => [],
            'tools' => ['planka_manage_labels', 'planka_set_card_labels'],
        ],
        'Task Lists & Tasks' => [
            'resources' => ['planka-task-list'],
            'tools' => ['planka_create_tasks', 'planka_update_task', 'planka_delete_task', 'planka_update_task_list', 'planka_delete_task_list'],
        ],
        'Attachments' => [
            'resources' => [],
            'tools' => ['planka_upload_attachment', 'planka_manage_attachments'],
        ],
        'Comments' => [
            'resources' => [],
            'tools' => ['planka_add_comment', 'planka_get_comments', 'planka_update_comment', 'planka_delete_comment'],
        ],
        'Custom Fields' => [
            'resources' => ['planka-custom-field-group'],
            'tools' => ['planka_manage_custom_field_groups', 'planka_manage_custom_fields', 'planka_manage_custom_field_values'],
        ],
        'Users' => [
            'resources' => ['planka-users', 'planka-user'],
            'tools' => ['planka_manage_users', 'planka_manage_user_credentials'],
        ],
        'Notifications' => [
            'resources' => ['planka-notifications', 'planka-notification'],
            'tools' => ['planka_get_notifications', 'planka_mark_notification_read', 'planka_mark_all_notifications_read'],
        ],
        'Webhooks' => [
            'resources' => ['planka-webhooks'],
            'tools' => ['planka_manage_webhooks'],
        ],
        'Notification Services' => [
            'resources' => [],
            'tools' => ['planka_manage_notification_services'],
        ],
    ];

    public function __invoke(Request $request): Response
    {
        $mcpUrl = $request->getSchemeAndHttpHost() . '/mcp';
        $tools = $this->getToolMetadata();
        $resources = $this->getResourceMetadata();
        $sections = $this->buildSections($tools, $resources);

        $csp = app()->isLocal()
            ? "default-src 'self' http://localhost:5173 ws://localhost:5173; img-src 'self' data:; font-src 'self'"
            : "default-src 'self'; img-src 'self' data:; connect-src 'self'; font-src 'self'";

        return response()
            ->view('home', [
                'mcpUrl' => $mcpUrl,
                'tools' => $tools,
                'resources' => $resources,
                'sections' => $sections,
            ])
            ->header('Content-Security-Policy', $csp);
    }

    /**
     * @return array<int, array{name: string, description: string, icon: string, parameters: array<int, array{name: string, type: string, required: bool, desc: string, enum?: array<int, string>}>}>
     */
    private function getToolMetadata(): array
    {
        $reflection = new ReflectionClass(PlankaServer::class);
        $toolsProperty = $reflection->getProperty('tools');
        /** @var array<int, class-string> $toolClasses */
        $toolClasses = $toolsProperty->getDefaultValue();

        $tools = [];

        foreach ($toolClasses as $toolClass) {
            $toolReflection = new ReflectionClass($toolClass);

            $nameAttr = $toolReflection->getAttributes(Name::class);
            $name = $nameAttr !== [] ? $nameAttr[0]->newInstance()->value : '';

            $descAttr = $toolReflection->getAttributes(Description::class);
            $description = $descAttr !== [] ? $descAttr[0]->newInstance()->value : '';

            $parameters = $this->extractParameters($toolClass);

            $tools[] = [
                'name' => $name,
                'description' => $description,
                'icon' => self::TOOL_ICONS[$name] ?? '&#128736;',
                'parameters' => $parameters,
            ];
        }

        return $tools;
    }

    /**
     * @return array<int, array{name: string, description: string, uri: string, isTemplate: bool}>
     */
    private function getResourceMetadata(): array
    {
        $reflection = new ReflectionClass(PlankaServer::class);
        $resourcesProperty = $reflection->getProperty('resources');
        /** @var array<int, class-string> $resourceClasses */
        $resourceClasses = $resourcesProperty->getDefaultValue();

        $resources = [];

        foreach ($resourceClasses as $resourceClass) {
            $resourceReflection = new ReflectionClass($resourceClass);

            $nameAttr = $resourceReflection->getAttributes(Name::class);
            $name = $nameAttr !== [] ? $nameAttr[0]->newInstance()->value : '';

            $descAttr = $resourceReflection->getAttributes(Description::class);
            $description = $descAttr !== [] ? $descAttr[0]->newInstance()->value : '';

            $isTemplate = $resourceReflection->implementsInterface(HasUriTemplate::class);

            if ($isTemplate) {
                /** @var \Laravel\Mcp\Server\Resource&\Laravel\Mcp\Server\Contracts\HasUriTemplate $instance */
                $instance = app()->make($resourceClass);
                $uri = (string) $instance->uriTemplate();
            } else {
                $uriAttr = $resourceReflection->getAttributes(Uri::class);
                $uri = $uriAttr !== [] ? $uriAttr[0]->newInstance()->value : '';
            }

            $resources[] = [
                'name' => $name,
                'description' => $description,
                'uri' => $uri,
                'isTemplate' => $isTemplate,
            ];
        }

        return $resources;
    }

    /**
     * @param array<int, array{name: string, description: string, icon: string, parameters: array<int, mixed>}> $tools
     * @param array<int, array{name: string, description: string, uri: string, isTemplate: bool}> $resources
     * @return array<int, array{title: string, resources: array<int, array{name: string, description: string, uri: string, isTemplate: bool}>, tools: array<int, array{name: string, description: string, icon: string, parameters: array<int, mixed>}>}>
     */
    private function buildSections(array $tools, array $resources): array
    {
        // Index by name for fast lookup
        $toolsByName = [];
        foreach ($tools as $tool) {
            $toolsByName[$tool['name']] = $tool;
        }

        $resourcesByName = [];
        foreach ($resources as $resource) {
            $resourcesByName[$resource['name']] = $resource;
        }

        $sections = [];

        foreach (self::ENTITY_GROUPS as $title => $group) {
            $sectionResources = [];
            foreach ($group['resources'] as $resourceName) {
                if (isset($resourcesByName[$resourceName])) {
                    $sectionResources[] = $resourcesByName[$resourceName];
                }
            }

            $sectionTools = [];
            foreach ($group['tools'] as $toolName) {
                if (isset($toolsByName[$toolName])) {
                    $sectionTools[] = $toolsByName[$toolName];
                }
            }

            if ($sectionResources !== [] || $sectionTools !== []) {
                $sections[] = [
                    'title' => $title,
                    'resources' => $sectionResources,
                    'tools' => $sectionTools,
                ];
            }
        }

        return $sections;
    }

    /**
     * @param class-string $toolClass
     * @return array<int, array{name: string, type: string, required: bool, desc: string, enum?: array<int, string>}>
     */
    private function extractParameters(string $toolClass): array
    {
        /** @var \Laravel\Mcp\Server\Tool $tool */
        $tool = app()->make($toolClass);
        $data = $tool->toArray();

        /** @var array{properties?: \stdClass|array<string, mixed>, required?: array<int, string>} $inputSchema */
        $inputSchema = $data['inputSchema'] ?? [];
        $properties = $inputSchema['properties'] ?? [];
        /** @var array<int, string> $required */
        $required = $inputSchema['required'] ?? [];

        if ($properties instanceof \stdClass) {
            $properties = (array) $properties;
        }

        $parameters = [];

        /** @var array{type?: string, description?: string, enum?: array<int, string>} $paramDef */
        foreach ($properties as $paramName => $paramDef) {
            $param = [
                'name' => (string) $paramName,
                'type' => $paramDef['type'] ?? 'string',
                'required' => in_array((string) $paramName, $required, true),
                'desc' => $paramDef['description'] ?? '',
            ];

            if (isset($paramDef['enum'])) {
                $param['enum'] = $paramDef['enum'];
            }

            $parameters[] = $param;
        }

        return $parameters;
    }
}
