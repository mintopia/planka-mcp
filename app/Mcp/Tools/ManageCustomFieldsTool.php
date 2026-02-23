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

#[Name('planka_manage_custom_fields')]
#[Description('Create, update, or delete custom fields within a custom field group.')]
final class ManageCustomFieldsTool extends Tool
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
                ->enum(['create', 'update', 'delete']),
            'groupType' => $schema->string()
                ->nullable()
                ->description('Required for create: whether the parent is a base custom field group or a regular custom field group')
                ->enum(['base', 'group']),
            'groupId' => $schema->string()
                ->nullable()
                ->description('Group ID — required for create'),
            'fieldId' => $schema->string()
                ->nullable()
                ->description('Custom field ID — required for update and delete'),
            'name' => $schema->string()
                ->nullable()
                ->description('Field name'),
            'fieldType' => $schema->string()
                ->nullable()
                ->description('Field type'),
        ];
    }

    public function handle(Request $request): Response
    {
        try {
            $action = (string) $request->get('action', '');
            /** @var ?string $groupType */
            $groupType = $request->get('groupType');
            /** @var ?string $groupId */
            $groupId = $request->get('groupId');
            /** @var ?string $fieldId */
            $fieldId = $request->get('fieldId');
            /** @var ?string $name */
            $name = $request->get('name');
            /** @var ?string $fieldType */
            $fieldType = $request->get('fieldType');
            $apiKey = $this->apiKeyProvider->getApiKey();

            $result = match ($action) {
                'create' => $this->customFieldService->createField($apiKey, $groupType ?? throw new ValidationException('groupType required for create'), $groupId ?? throw new ValidationException('groupId required for create'), $name, $fieldType),
                'update' => $this->customFieldService->updateField($apiKey, $fieldId ?? throw new ValidationException('fieldId required for update'), $name, $fieldType),
                'delete' => $this->customFieldService->deleteField($apiKey, $fieldId ?? throw new ValidationException('fieldId required for delete')),
                default => throw new ValidationException(sprintf('Invalid action "%s". Must be: create, update, delete', $action)),
            };

            return Response::json($result);
        } catch (\Throwable $e) {
            return Response::error($e->getMessage());
        }
    }
}
