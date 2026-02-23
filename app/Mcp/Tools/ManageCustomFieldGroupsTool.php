<?php

declare(strict_types=1);

namespace App\Mcp\Tools;

use App\Domain\CustomField\CustomFieldServiceInterface;
use App\Exception\ValidationException;
use App\Http\ApiKeyProviderInterface;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Attributes\Name;
use Laravel\Mcp\Server\Tool;

#[Name('planka_manage_custom_field_groups')]
#[Description('Create, get, update, or delete custom field groups on projects (base groups), boards, or cards.')]
final class ManageCustomFieldGroupsTool extends Tool
{
    public function __construct(
        private readonly CustomFieldServiceInterface $customFieldService,
        private readonly ApiKeyProviderInterface $apiKeyProvider,
    ) {}

    public function schema(JsonSchema $schema): array
    {
        return [
            'action' => $schema->string()
                ->required()
                ->description('Action to perform')
                ->enum(['create_base', 'update_base', 'delete_base', 'create', 'get', 'update', 'delete']),
            'projectId' => $schema->string()
                ->nullable()
                ->description('Project ID — required for create_base'),
            'baseGroupId' => $schema->string()
                ->nullable()
                ->description('Base custom field group ID — required for update_base and delete_base'),
            'parentType' => $schema->string()
                ->nullable()
                ->description('Required for create: whether the group belongs to a board or a card')
                ->enum(['board', 'card']),
            'parentId' => $schema->string()
                ->nullable()
                ->description('Board ID or card ID — required for create'),
            'groupId' => $schema->string()
                ->nullable()
                ->description('Custom field group ID — required for get, update, delete'),
            'name' => $schema->string()
                ->nullable()
                ->description('Group name'),
        ];
    }

    public function handle(Request $request): Response
    {
        try {
            $action = (string) $request->get('action', '');
            /** @var ?string $projectId */
            $projectId = $request->get('projectId');
            /** @var ?string $baseGroupId */
            $baseGroupId = $request->get('baseGroupId');
            /** @var ?string $parentType */
            $parentType = $request->get('parentType');
            /** @var ?string $parentId */
            $parentId = $request->get('parentId');
            /** @var ?string $groupId */
            $groupId = $request->get('groupId');
            /** @var ?string $name */
            $name = $request->get('name');
            $apiKey = $this->apiKeyProvider->getApiKey();

            $result = match ($action) {
                'create_base' => $this->customFieldService->createBaseGroup($apiKey, $projectId ?? throw new ValidationException('projectId required for create_base'), $name),
                'update_base' => $this->customFieldService->updateBaseGroup($apiKey, $baseGroupId ?? throw new ValidationException('baseGroupId required for update_base'), $name),
                'delete_base' => $this->customFieldService->deleteBaseGroup($apiKey, $baseGroupId ?? throw new ValidationException('baseGroupId required for delete_base')),
                'create' => $this->customFieldService->createGroup($apiKey, $parentType ?? throw new ValidationException('parentType required for create'), $parentId ?? throw new ValidationException('parentId required for create'), $name),
                'get' => $this->customFieldService->getGroup($apiKey, $groupId ?? throw new ValidationException('groupId required for get')),
                'update' => $this->customFieldService->updateGroup($apiKey, $groupId ?? throw new ValidationException('groupId required for update'), $name),
                'delete' => $this->customFieldService->deleteGroup($apiKey, $groupId ?? throw new ValidationException('groupId required for delete')),
                default => throw new ValidationException(sprintf('Invalid action "%s". Must be: create_base, update_base, delete_base, create, get, update, delete', $action)),
            };

            return Response::json($result);
        } catch (\Throwable $e) {
            return Response::error($e->getMessage());
        }
    }
}
