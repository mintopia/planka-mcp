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

#[Name('planka_manage_card_membership')]
#[Description('Add or remove a user as a member of a card.')]
final class ManageCardMembershipTool extends Tool
{
    public function __construct(
        private readonly CardServiceInterface $cardService,
        private readonly ApiKeyProviderInterface $apiKeyProvider,
    ) {}

    public function schema(JsonSchema $schema): array
    {
        return [
            'action' => $schema->string()
                ->required()
                ->description('Action to perform: "add" to add a member, "remove" to remove a member')
                ->enum(['add', 'remove']),
            'cardId' => $schema->string()
                ->description('The card ID — required for both add and remove actions'),
            'userId' => $schema->string()
                ->description('The user ID — required for both add and remove actions'),
        ];
    }

    public function handle(Request $request): Response
    {
        try {
            $action = (string) $request->get('action', '');
            $cardId = (string) $request->get('cardId', '');
            $userId = (string) $request->get('userId', '');
            $apiKey = $this->apiKeyProvider->getApiKey();

            $result = match ($action) {
                'add' => $this->cardService->addCardMember(
                    $apiKey,
                    $cardId ?: throw new ValidationException('cardId is required for action: add'),
                    $userId ?: throw new ValidationException('userId is required for action: add'),
                ),
                'remove' => $this->cardService->removeCardMember(
                    $apiKey,
                    $cardId ?: throw new ValidationException('cardId is required for action: remove'),
                    $userId ?: throw new ValidationException('userId is required for action: remove'),
                ),
                default => throw new ValidationException('Invalid action "' . $action . '". Must be "add" or "remove".'),
            };

            return Response::json($result);
        } catch (\Throwable $e) {
            return Response::error($e->getMessage());
        }
    }
}
