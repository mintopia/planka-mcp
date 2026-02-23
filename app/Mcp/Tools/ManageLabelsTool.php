<?php

declare(strict_types=1);

namespace App\Mcp\Tools;

use App\Domain\Label\LabelServiceInterface;
use App\Http\ApiKeyProviderInterface;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Attributes\Name;
use Laravel\Mcp\Server\Tool;

#[Name('planka_manage_labels')]
#[Description('Create, update, or delete labels on a board.')]
final class ManageLabelsTool extends Tool
{
    public function __construct(
        private readonly LabelServiceInterface $labelService,
        private readonly ApiKeyProviderInterface $apiKeyProvider,
    ) {}

    public function schema(JsonSchema $schema): array
    {
        return [
            'action' => $schema->string()
                ->required()
                ->description('Action to perform: create, update, or delete')
                ->enum(['create', 'update', 'delete']),
            'boardId' => $schema->string()
                ->nullable()
                ->description('Board ID (required for create) (from planka_get_structure or planka_get_board)'),
            'labelId' => $schema->string()
                ->nullable()
                ->description('Label ID (required for update/delete) (from planka_get_board)'),
            'name' => $schema->string()
                ->nullable()
                ->description('Label name'),
            'color' => $schema->string()
                ->nullable()
                ->description('Label color')
                ->enum(['berry-red', 'pumpkin-orange', 'lagoon-blue', 'pink-tulip', 'light-mud', 'orange-peel', 'sky-blue', 'coconut-milk', 'matte-green', 'pistachio', 'sea-foam', 'pale-sky', 'old-rose', 'moss-green', 'midnight-blue', 'sunrise', 'dark-granite', 'gunmetal', 'wet-stone', 'canary-yellow', 'fine-emerald', 'morning-sky', 'sunny-grass']),
        ];
    }

    public function handle(Request $request): Response
    {
        try {
            $action = (string) $request->get('action', '');
            /** @var ?string $boardId */
            $boardId = $request->get('boardId');
            /** @var ?string $labelId */
            $labelId = $request->get('labelId');
            /** @var ?string $name */
            $name = $request->get('name');
            /** @var ?string $color */
            $color = $request->get('color');
            $apiKey = $this->apiKeyProvider->getApiKey();

            return Response::json($this->labelService->manageLabel($apiKey, $action, $boardId, $labelId, $name, $color));
        } catch (\Throwable $e) {
            return Response::error($e->getMessage());
        }
    }
}
