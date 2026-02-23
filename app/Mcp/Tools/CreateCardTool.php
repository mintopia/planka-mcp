<?php

declare(strict_types=1);

namespace App\Mcp\Tools;

use App\Domain\Card\CardServiceInterface;
use App\Exception\ValidationException;
use App\Http\ApiKeyProviderInterface;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Attributes\Name;
use Laravel\Mcp\Server\Tool;

#[Name('planka_create_card')]
#[Description('Create a new card on a board. Optionally add tasks (checklist items) at the same time.')]
final class CreateCardTool extends Tool
{
    public function __construct(
        private readonly CardServiceInterface $cardService,
        private readonly ApiKeyProviderInterface $apiKeyProvider,
    ) {}

    public function schema(JsonSchema $schema): array
    {
        return [
            'listId' => $schema->string()
                ->required()
                ->description('The list ID (from planka_get_board)'),
            'name' => $schema->string()
                ->required()
                ->description('Card title'),
            'description' => $schema->string()
                ->nullable()
                ->description('Card description (markdown supported)'),
            'type' => $schema->string()
                ->nullable()
                ->description('Card type')
                ->enum(['project', 'story']),
            'labelIds' => $schema->array()
                ->nullable()
                ->description('Optional: Label IDs to attach (from planka_get_board)')
                ->items($schema->string()),
        ];
    }

    public function handle(Request $request): Response
    {
        try {
            $listId = (string) $request->get('listId', '');
            $name = (string) $request->get('name', '');
            /** @var ?string $description */
            $description = $request->get('description');
            /** @var ?string $type */
            $type = $request->get('type');
            /** @var ?array<string> $labelIds */
            $labelIds = $request->get('labelIds');

            if (trim($name) === '') {
                throw new ValidationException('Card name cannot be empty.');
            }

            $apiKey = $this->apiKeyProvider->getApiKey();

            return Response::json($this->cardService->createCard($apiKey, $listId, $name, $description, $type, $labelIds));
        } catch (\Throwable $e) {
            return Response::error($e->getMessage());
        }
    }
}
