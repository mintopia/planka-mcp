<?php

declare(strict_types=1);

namespace App\Mcp\Tools;

use App\Domain\BoardList\ListServiceInterface;
use App\Http\ApiKeyProviderInterface;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Attributes\Name;
use Laravel\Mcp\Server\Tool;

#[Name('planka_sort_list')]
#[Description('Sort cards within a list by a specified field.')]
final class SortListTool extends Tool
{
    public function __construct(
        private readonly ListServiceInterface $listService,
        private readonly ApiKeyProviderInterface $apiKeyProvider,
    ) {}

    public function schema(JsonSchema $schema): array
    {
        return [
            'listId' => $schema->string()
                ->required()
                ->description('List ID (from planka_get_board)'),
            'field' => $schema->string()
                ->required()
                ->description('Field to sort by: name, dueDate, or createdAt')
                ->enum(['name', 'dueDate', 'createdAt']),
        ];
    }

    public function handle(Request $request): Response
    {
        try {
            $listId = (string) $request->get('listId', '');
            $field = (string) $request->get('field', '');
            $apiKey = $this->apiKeyProvider->getApiKey();

            return Response::json($this->listService->sortList($apiKey, $listId, $field));
        } catch (\Throwable $e) {
            return Response::error($e->getMessage());
        }
    }
}
