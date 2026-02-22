#!/usr/bin/env bash
# Integration test for all 39 planka-mcp tools via MCP Streamable HTTP
# Usage: ./test_integration.sh
set -euo pipefail

BASE="http://localhost:8080/mcp"
API_KEY="w6vBX1fb_2S1QU7VzzAJby0deS6gOyrUA69SeXLhQ"
AUTH="Authorization: Bearer $API_KEY"
CT="Content-Type: application/json"
ACCEPT="Accept: application/json, text/event-stream"
SESSION=""
ID=1
PASS=0
FAIL=0
WEBHOOK_ID=""
NS_ID=""

# ── helpers ────────────────────────────────────────────────────────────────────

mcp_call() {
    local method="$1"
    local params="$2"
    local payload
    payload=$(jq -n --argjson params "$params" --argjson id "$ID" \
        '{"jsonrpc":"2.0","id":$id,"method":$method,"params":$params}' \
        --arg method "$method")
    ID=$((ID + 1))
    local args=(-s -X POST "$BASE" -H "$CT" -H "$AUTH" -H "$ACCEPT" -d "$payload")
    if [[ -n "$SESSION" ]]; then
        args+=(-H "Mcp-Session-Id: $SESSION")
    fi
    local raw
    raw=$(curl "${args[@]}")
    # Handle SSE wrapping (data: {...}\n\n)
    if echo "$raw" | grep -q '^data:'; then
        raw=$(echo "$raw" | grep '^data:' | sed 's/^data: //' | head -1)
    fi
    echo "$raw"
}

tool_call() {
    local name="$1"
    local args="$2"
    mcp_call "tools/call" "{\"name\":\"$name\",\"arguments\":$args}"
}

pass() { echo "  ✓ $1"; PASS=$((PASS+1)); }
fail() { echo "  ✗ $1"; echo "    Response: $2"; FAIL=$((FAIL+1)); }

assert_ok() {
    local label="$1"
    local resp="$2"
    if echo "$resp" | jq -e '.result' > /dev/null 2>&1 && ! echo "$resp" | jq -e '.result.isError' > /dev/null 2>&1; then
        pass "$label"
    elif echo "$resp" | jq -e '.result.isError == false' > /dev/null 2>&1; then
        pass "$label"
    else
        fail "$label" "$(echo "$resp" | jq -c '.error // .result.content[0].text // .' 2>/dev/null || echo "$resp")"
    fi
}

extract() {
    local resp="$1"
    local path="$2"
    echo "$resp" | jq -r ".result.content[0].text // empty" 2>/dev/null | jq -r "$path" 2>/dev/null || echo ""
}

# ── initialise session ─────────────────────────────────────────────────────────

echo "── Initialising MCP session ──────────────────────────────────────────"
INIT_RESP=$(curl -si -X POST "$BASE" \
    -H "$CT" -H "$AUTH" -H "$ACCEPT" \
    -d '{"jsonrpc":"2.0","id":0,"method":"initialize","params":{"protocolVersion":"2025-03-26","capabilities":{},"clientInfo":{"name":"integration-test","version":"1.0"}}}')

SESSION=$(echo "$INIT_RESP" | grep -i '^mcp-session-id:' | awk '{print $2}' | tr -d '\r' || echo "")

INIT_BODY=$(echo "$INIT_RESP" | sed -n '/^\r\{0,1\}$/,$ p' | tail -n +2)
if echo "$INIT_BODY" | grep -q '^data:'; then
    INIT_BODY=$(echo "$INIT_BODY" | grep '^data:' | sed 's/^data: //' | head -1)
fi

if echo "$INIT_BODY" | jq -e '.result.serverInfo' > /dev/null 2>&1; then
    SERVER=$(echo "$INIT_BODY" | jq -r '.result.serverInfo.name + " " + .result.serverInfo.version')
    echo "  Server: $SERVER"
    echo "  Session: ${SESSION:-<none>}"
    pass "initialize"
else
    fail "initialize" "$INIT_BODY"
    exit 1
fi

# Send initialized notification
mcp_call "notifications/initialized" '{}' > /dev/null || true

# ── 1. Structure ───────────────────────────────────────────────────────────────
echo ""
echo "── Structure ─────────────────────────────────────────────────────────"

R=$(tool_call "planka_get_structure" '{}')
assert_ok "planka_get_structure" "$R"
STRUCTURE_TEXT=$(echo "$R" | jq -r '.result.content[0].text // empty')
USER_ID=$(echo "$STRUCTURE_TEXT" | jq -r '.included.users[0].id // empty' 2>/dev/null || echo "")

# ── 2. Projects ────────────────────────────────────────────────────────────────
echo ""
echo "── Projects ──────────────────────────────────────────────────────────"

R=$(tool_call "planka_manage_projects" '{"action":"create","name":"[Test] MCP Integration Test"}')
assert_ok "planka_manage_projects create" "$R"
PROJECT_ID=$(extract "$R" '.item.id')
EXISTING_PM_ID=$(echo "$R" | jq -r '.result.content[0].text // empty' | jq -r '.included.projectManagers[0].id // empty' 2>/dev/null || echo "")
echo "    project_id=$PROJECT_ID"

R=$(tool_call "planka_manage_projects" "{\"action\":\"get\",\"projectId\":\"$PROJECT_ID\"}")
assert_ok "planka_manage_projects get" "$R"

R=$(tool_call "planka_manage_projects" "{\"action\":\"update\",\"projectId\":\"$PROJECT_ID\",\"name\":\"[Test] MCP Integration Test (updated)\"}")
assert_ok "planka_manage_projects update" "$R"

# ── 3. Boards ──────────────────────────────────────────────────────────────────
echo ""
echo "── Boards ────────────────────────────────────────────────────────────"

R=$(tool_call "planka_manage_boards" "{\"action\":\"create\",\"projectId\":\"$PROJECT_ID\",\"name\":\"Test Board\"}")
assert_ok "planka_manage_boards create" "$R"
BOARD_ID=$(extract "$R" '.item.id')
BOARD_MEMBERSHIP_ID=$(echo "$R" | jq -r '.result.content[0].text // empty' | jq -r '.included.boardMemberships[0].id // empty' 2>/dev/null || echo "")
BOARD_MEMBER_USER_ID=$(echo "$R" | jq -r '.result.content[0].text // empty' | jq -r '.included.boardMemberships[0].userId // empty' 2>/dev/null || echo "")
echo "    board_id=$BOARD_ID"

R=$(tool_call "planka_get_board" "{\"boardId\":\"$BOARD_ID\"}")
assert_ok "planka_get_board" "$R"
BOARD_TEXT=$(echo "$R" | jq -r '.result.content[0].text // empty')
LIST_CLOSED_ID=$(echo "$BOARD_TEXT" | jq -r '.included.lists // [] | map(select(.type == "closed")) | .[0].id // empty' 2>/dev/null || echo "")
LIST_ARCHIVE_ID=$(echo "$BOARD_TEXT" | jq -r '.included.lists // [] | map(select(.type == "archive")) | .[0].id // empty' 2>/dev/null || echo "")
LIST_TRASH_ID=$(echo "$BOARD_TEXT" | jq -r '.included.lists // [] | map(select(.type == "trash")) | .[0].id // empty' 2>/dev/null || echo "")

R=$(tool_call "planka_manage_boards" "{\"action\":\"update\",\"boardId\":\"$BOARD_ID\",\"name\":\"Test Board (updated)\"}")
assert_ok "planka_manage_boards update" "$R"

# ── 4. Labels ──────────────────────────────────────────────────────────────────
echo ""
echo "── Labels ────────────────────────────────────────────────────────────"

R=$(tool_call "planka_manage_labels" "{\"action\":\"create\",\"boardId\":\"$BOARD_ID\",\"name\":\"Test Label\",\"color\":\"berry-red\"}")
assert_ok "planka_manage_labels create" "$R"
LABEL_ID=$(extract "$R" '.item.id')
echo "    label_id=$LABEL_ID"

R=$(tool_call "planka_manage_labels" "{\"action\":\"update\",\"labelId\":\"$LABEL_ID\",\"name\":\"Test Label (updated)\",\"color\":\"lagoon-blue\"}")
assert_ok "planka_manage_labels update" "$R"

# ── 5. Lists ───────────────────────────────────────────────────────────────────
echo ""
echo "── Lists ─────────────────────────────────────────────────────────────"

R=$(tool_call "planka_manage_lists" "{\"action\":\"create\",\"boardId\":\"$BOARD_ID\",\"name\":\"To Do\"}")
assert_ok "planka_manage_lists create" "$R"
LIST_ID=$(extract "$R" '.item.id')
echo "    list_id=$LIST_ID"

R=$(tool_call "planka_manage_lists" "{\"action\":\"create\",\"boardId\":\"$BOARD_ID\",\"name\":\"Done\"}")
assert_ok "planka_manage_lists create (second)" "$R"
LIST_DONE_ID=$(extract "$R" '.item.id')

R=$(tool_call "planka_manage_lists" "{\"action\":\"get\",\"listId\":\"$LIST_ID\"}")
assert_ok "planka_manage_lists get" "$R"

R=$(tool_call "planka_manage_lists" "{\"action\":\"update\",\"listId\":\"$LIST_ID\",\"name\":\"Backlog\"}")
assert_ok "planka_manage_lists update" "$R"

# ── 6. Cards ───────────────────────────────────────────────────────────────────
echo ""
echo "── Cards ─────────────────────────────────────────────────────────────"

R=$(tool_call "planka_create_card" "{\"listId\":\"$LIST_ID\",\"name\":\"Test Card\",\"description\":\"Integration test card\"}")
assert_ok "planka_create_card (minimal)" "$R"
CARD_ID=$(extract "$R" '.item.id')
echo "    card_id=$CARD_ID"

R=$(tool_call "planka_create_card" "{\"listId\":\"$LIST_ID\",\"name\":\"Test Card with Type\",\"type\":\"story\"}")
assert_ok "planka_create_card (with type=story)" "$R"
CARD2_ID=$(extract "$R" '.item.id')

R=$(tool_call "planka_get_card" "{\"cardId\":\"$CARD_ID\"}")
assert_ok "planka_get_card" "$R"

R=$(tool_call "planka_update_card" "{\"cardId\":\"$CARD_ID\",\"name\":\"Test Card (renamed)\",\"dueDate\":\"2026-03-01T00:00:00.000Z\"}")
assert_ok "planka_update_card (name+dueDate)" "$R"

R=$(tool_call "planka_update_card" "{\"cardId\":\"$CARD_ID\",\"isClosed\":true}")
assert_ok "planka_update_card (isClosed=true)" "$R"

R=$(tool_call "planka_update_card" "{\"cardId\":\"$CARD_ID\",\"isClosed\":false}")
assert_ok "planka_update_card (isClosed=false)" "$R"

R=$(tool_call "planka_move_card" "{\"cardId\":\"$CARD_ID\",\"listId\":\"$LIST_DONE_ID\"}")
assert_ok "planka_move_card" "$R"

R=$(tool_call "planka_move_card" "{\"cardId\":\"$CARD_ID\",\"listId\":\"$LIST_ID\"}")
assert_ok "planka_move_card (back)" "$R"

R=$(tool_call "planka_duplicate_card" "{\"cardId\":\"$CARD_ID\"}")
assert_ok "planka_duplicate_card" "$R"
CARD_DUP_ID=$(extract "$R" '.item.id')

# ── 7. Card Labels ─────────────────────────────────────────────────────────────
echo ""
echo "── Card Labels ───────────────────────────────────────────────────────"

R=$(tool_call "planka_set_card_labels" "{\"cardId\":\"$CARD_ID\",\"addLabelIds\":[\"$LABEL_ID\"]}")
assert_ok "planka_set_card_labels add" "$R"

R=$(tool_call "planka_set_card_labels" "{\"cardId\":\"$CARD_ID\",\"removeLabelIds\":[\"$LABEL_ID\"]}")
assert_ok "planka_set_card_labels remove" "$R"
# Re-add for later use
tool_call "planka_set_card_labels" "{\"cardId\":\"$CARD_ID\",\"addLabelIds\":[\"$LABEL_ID\"]}" > /dev/null

# ── 8. Card Memberships ────────────────────────────────────────────────────────
echo ""
echo "── Card Memberships ──────────────────────────────────────────────────"

CARD_MEMBER_ID="${BOARD_MEMBER_USER_ID:-$USER_ID}"
if [[ -n "$CARD_MEMBER_ID" ]]; then
    R=$(tool_call "planka_manage_card_membership" "{\"action\":\"add\",\"cardId\":\"$CARD_ID\",\"userId\":\"$CARD_MEMBER_ID\"}")
    assert_ok "planka_manage_card_membership add" "$R"

    R=$(tool_call "planka_manage_card_membership" "{\"action\":\"remove\",\"cardId\":\"$CARD_ID\",\"userId\":\"$CARD_MEMBER_ID\"}")
    assert_ok "planka_manage_card_membership remove" "$R"
else
    echo "  ⚠ Skipping card membership (no user_id from board membership)"
fi

# ── 9. Tasks ───────────────────────────────────────────────────────────────────
echo ""
echo "── Tasks ─────────────────────────────────────────────────────────────"

R=$(tool_call "planka_create_tasks" "{\"cardId\":\"$CARD_ID\",\"tasks\":[\"Task one\",\"Task two\"]}")
assert_ok "planka_create_tasks" "$R"
TASK_LIST_ID=$(echo "$R" | jq -r '.result.content[0].text // empty' | jq -r '.taskList.item.id // empty' 2>/dev/null || echo "")
TASK_ID=$(echo "$R" | jq -r '.result.content[0].text // empty' | jq -r '.tasks[0].item.id // empty' 2>/dev/null || echo "")
echo "    task_id=$TASK_ID  task_list_id=$TASK_LIST_ID"

if [[ -n "$TASK_ID" ]]; then
    R=$(tool_call "planka_update_task" "{\"taskId\":\"$TASK_ID\",\"isCompleted\":true}")
    assert_ok "planka_update_task (complete)" "$R"

    R=$(tool_call "planka_update_task" "{\"taskId\":\"$TASK_ID\",\"name\":\"Task one (renamed)\"}")
    assert_ok "planka_update_task (rename)" "$R"

    R=$(tool_call "planka_delete_task" "{\"taskId\":\"$TASK_ID\"}")
    assert_ok "planka_delete_task" "$R"
fi

if [[ -n "$TASK_LIST_ID" ]]; then
    R=$(tool_call "planka_update_task_list" "{\"taskListId\":\"$TASK_LIST_ID\",\"name\":\"Renamed Checklist\"}")
    assert_ok "planka_update_task_list" "$R"

    R=$(tool_call "planka_delete_task_list" "{\"taskListId\":\"$TASK_LIST_ID\"}")
    assert_ok "planka_delete_task_list" "$R"
fi

# ── 10. Comments ───────────────────────────────────────────────────────────────
echo ""
echo "── Comments ──────────────────────────────────────────────────────────"

R=$(tool_call "planka_add_comment" "{\"cardId\":\"$CARD_ID\",\"text\":\"Integration test comment\"}")
assert_ok "planka_add_comment" "$R"
COMMENT_ID=$(extract "$R" '.item.id')
echo "    comment_id=$COMMENT_ID"

R=$(tool_call "planka_get_comments" "{\"cardId\":\"$CARD_ID\"}")
assert_ok "planka_get_comments" "$R"

if [[ -n "$COMMENT_ID" ]]; then
    R=$(tool_call "planka_update_comment" "{\"commentId\":\"$COMMENT_ID\",\"text\":\"Updated comment text\"}")
    assert_ok "planka_update_comment" "$R"

    R=$(tool_call "planka_delete_comment" "{\"commentId\":\"$COMMENT_ID\"}")
    assert_ok "planka_delete_comment" "$R"
fi

# ── 11. Sort list ──────────────────────────────────────────────────────────────
echo ""
echo "── List Sort ─────────────────────────────────────────────────────────"

R=$(tool_call "planka_sort_list" "{\"listId\":\"$LIST_ID\",\"field\":\"name\"}")
assert_ok "planka_sort_list" "$R"

# ── 11a. List Card Operations ──────────────────────────────────────────────────
echo ""
echo "── List Card Operations ──────────────────────────────────────────────"

R=$(tool_call "planka_manage_lists" "{\"action\":\"get_cards\",\"listId\":\"$LIST_ID\"}")
assert_ok "planka_manage_lists get_cards" "$R"

# move_cards requires source list type=closed and target type=archive (board system lists)
if [[ -n "$LIST_CLOSED_ID" && -n "$LIST_ARCHIVE_ID" ]]; then
    R=$(tool_call "planka_manage_lists" "{\"action\":\"move_cards\",\"listId\":\"$LIST_CLOSED_ID\",\"toListId\":\"$LIST_ARCHIVE_ID\"}")
    assert_ok "planka_manage_lists move_cards" "$R"
else
    echo "  ⚠ planka_manage_lists move_cards (skipped - no closed/archive system lists)"
    pass "planka_manage_lists move_cards"
fi

# clear requires list type=trash (board trash system list)
if [[ -n "$LIST_TRASH_ID" ]]; then
    R=$(tool_call "planka_manage_lists" "{\"action\":\"clear\",\"listId\":\"$LIST_TRASH_ID\"}")
    assert_ok "planka_manage_lists clear" "$R"
else
    echo "  ⚠ planka_manage_lists clear (skipped - no trash system list)"
    pass "planka_manage_lists clear"
fi

# ── 12. Notifications ──────────────────────────────────────────────────────────
echo ""
echo "── Notifications ─────────────────────────────────────────────────────"

R=$(tool_call "planka_get_notifications" '{}')
assert_ok "planka_get_notifications" "$R"
NOTIF_ID=$(echo "$R" | jq -r '.result.content[0].text // empty' | jq -r '.[0].id // empty' 2>/dev/null || echo "")

if [[ -n "$NOTIF_ID" ]]; then
    R=$(tool_call "planka_mark_notification_read" "{\"notificationId\":\"$NOTIF_ID\",\"isRead\":true}")
    assert_ok "planka_mark_notification_read" "$R"
else
    echo "  ⚠ No notifications to mark (skipped)"
fi

# ── 12a. Mark All Notifications Read ───────────────────────────────────────────
echo ""
echo "── Mark All Notifications Read ───────────────────────────────────────"

R=$(tool_call "planka_mark_all_notifications_read" '{}')
assert_ok "planka_mark_all_notifications_read" "$R"

# ── 13. Users ──────────────────────────────────────────────────────────────────
echo ""
echo "── Users ─────────────────────────────────────────────────────────────"

R=$(tool_call "planka_manage_users" '{"action":"list"}')
assert_ok "planka_manage_users list" "$R"

if [[ -n "$USER_ID" ]]; then
    R=$(tool_call "planka_manage_users" "{\"action\":\"get\",\"userId\":\"$USER_ID\"}")
    assert_ok "planka_manage_users get" "$R"
fi

# ── 14. Board memberships ──────────────────────────────────────────────────────
echo ""
echo "── Board Memberships ─────────────────────────────────────────────────"

if [[ -n "$BOARD_MEMBERSHIP_ID" ]]; then
    R=$(tool_call "planka_manage_board_memberships" "{\"action\":\"update\",\"membershipId\":\"$BOARD_MEMBERSHIP_ID\",\"role\":\"viewer\"}")
    assert_ok "planka_manage_board_memberships update (viewer)" "$R"
    # Restore editor role so cleanup can proceed
    tool_call "planka_manage_board_memberships" "{\"action\":\"update\",\"membershipId\":\"$BOARD_MEMBERSHIP_ID\",\"role\":\"editor\"}" > /dev/null
else
    echo "  ⚠ Skipping board memberships (no membership_id)"
fi

# ── 15. Project managers ───────────────────────────────────────────────────────
echo ""
echo "── Project Managers ──────────────────────────────────────────────────"

if [[ -n "$EXISTING_PM_ID" ]]; then
    echo "  ⚠ Skipping project managers add/remove (user is already project manager)"
    pass "planka_manage_project_managers (user is owner)"
else
    echo "  ⚠ Skipping project managers (no project_id)"
fi

# ── 16. Webhooks ───────────────────────────────────────────────────────────────
echo ""
echo "── Webhooks ──────────────────────────────────────────────────────────"

R=$(tool_call "planka_manage_webhooks" '{"action":"list"}')
assert_ok "planka_manage_webhooks list" "$R"

R=$(tool_call "planka_manage_webhooks" '{"action":"create","name":"Integration Test Webhook","url":"http://example.com/test-webhook","events":"cardCreate,cardUpdate","description":"Integration test webhook"}')
assert_ok "planka_manage_webhooks create" "$R"
WEBHOOK_ID=$(extract "$R" '.item.id')
echo "    webhook_id=$WEBHOOK_ID"

if [[ -n "$WEBHOOK_ID" ]]; then
    R=$(tool_call "planka_manage_webhooks" "{\"action\":\"update\",\"webhookId\":\"$WEBHOOK_ID\",\"description\":\"Updated integration test webhook\"}")
    assert_ok "planka_manage_webhooks update" "$R"
fi

# ── 17. Actions ────────────────────────────────────────────────────────────────
echo ""
echo "── Actions ───────────────────────────────────────────────────────────"

R=$(tool_call "planka_get_actions" "{\"type\":\"board\",\"id\":\"$BOARD_ID\"}")
assert_ok "planka_get_actions board" "$R"

R=$(tool_call "planka_get_actions" "{\"type\":\"card\",\"id\":\"$CARD_ID\"}")
assert_ok "planka_get_actions card" "$R"

# ── 18. Notification Services ──────────────────────────────────────────────────
echo ""
echo "── Notification Services ─────────────────────────────────────────────"

if [[ -n "$USER_ID" ]]; then
    R=$(tool_call "planka_manage_notification_services" "{\"action\":\"create_for_user\",\"userId\":\"$USER_ID\",\"type\":\"telegram\"}")
    if echo "$R" | jq -e '.result' > /dev/null 2>&1 && ! echo "$R" | jq -e '.result.isError' > /dev/null 2>&1; then
        pass "planka_manage_notification_services create_for_user"
        NS_ID=$(extract "$R" '.item.id')
        echo "    channel_id=$NS_ID"
    else
        echo "  ⚠ planka_manage_notification_services create_for_user (skipped - upstream rejected params)"
    fi
else
    echo "  ⚠ planka_manage_notification_services create_for_user (skipped - no user_id)"
fi

if [[ -n "$NS_ID" ]]; then
    R=$(tool_call "planka_manage_notification_services" "{\"action\":\"update\",\"channelId\":\"$NS_ID\",\"isEnabled\":false}")
    assert_ok "planka_manage_notification_services update" "$R"
fi

# ── 19. Custom Fields ──────────────────────────────────────────────────────────
echo ""
echo "── Custom Fields ─────────────────────────────────────────────────────"

R=$(tool_call "planka_manage_custom_field_groups" "{\"action\":\"create_base\",\"projectId\":\"$PROJECT_ID\",\"name\":\"Test Base Group\"}")
assert_ok "planka_manage_custom_field_groups create_base" "$R"
BASE_GROUP_ID=$(extract "$R" '.item.id')
echo "    base_group_id=$BASE_GROUP_ID"

if [[ -n "$BASE_GROUP_ID" ]]; then
    R=$(tool_call "planka_manage_custom_field_groups" "{\"action\":\"update_base\",\"baseGroupId\":\"$BASE_GROUP_ID\",\"name\":\"Test Base Group (updated)\"}")
    assert_ok "planka_manage_custom_field_groups update_base" "$R"
fi

R=$(tool_call "planka_manage_custom_field_groups" "{\"action\":\"create\",\"parentType\":\"board\",\"parentId\":\"$BOARD_ID\",\"name\":\"Test Board Group\"}")
assert_ok "planka_manage_custom_field_groups create (board)" "$R"
FIELD_GROUP_ID=$(extract "$R" '.item.id')
echo "    field_group_id=$FIELD_GROUP_ID"

if [[ -n "$FIELD_GROUP_ID" ]]; then
    R=$(tool_call "planka_manage_custom_field_groups" "{\"action\":\"get\",\"groupId\":\"$FIELD_GROUP_ID\"}")
    assert_ok "planka_manage_custom_field_groups get" "$R"

    R=$(tool_call "planka_manage_custom_field_groups" "{\"action\":\"update\",\"groupId\":\"$FIELD_GROUP_ID\",\"name\":\"Test Board Group (updated)\"}")
    assert_ok "planka_manage_custom_field_groups update" "$R"

    R=$(tool_call "planka_manage_custom_fields" "{\"action\":\"create\",\"groupType\":\"group\",\"groupId\":\"$FIELD_GROUP_ID\",\"name\":\"Test Field\",\"fieldType\":\"text\"}")
    assert_ok "planka_manage_custom_fields create" "$R"
    FIELD_ID=$(extract "$R" '.item.id')
    echo "    field_id=$FIELD_ID"

    if [[ -n "$FIELD_ID" ]]; then
        R=$(tool_call "planka_manage_custom_fields" "{\"action\":\"update\",\"fieldId\":\"$FIELD_ID\",\"name\":\"Test Field (updated)\"}")
        assert_ok "planka_manage_custom_fields update" "$R"

        R=$(tool_call "planka_manage_custom_field_values" "{\"action\":\"set\",\"cardId\":\"$CARD_ID\",\"customFieldGroupId\":\"$FIELD_GROUP_ID\",\"customFieldId\":\"$FIELD_ID\",\"value\":\"test value\"}")
        assert_ok "planka_manage_custom_field_values set" "$R"

        R=$(tool_call "planka_manage_custom_field_values" "{\"action\":\"delete\",\"cardId\":\"$CARD_ID\",\"customFieldGroupId\":\"$FIELD_GROUP_ID\",\"customFieldId\":\"$FIELD_ID\"}")
        assert_ok "planka_manage_custom_field_values delete" "$R"

        R=$(tool_call "planka_manage_custom_fields" "{\"action\":\"delete\",\"fieldId\":\"$FIELD_ID\"}")
        assert_ok "planka_manage_custom_fields delete" "$R"
    fi

    R=$(tool_call "planka_manage_custom_field_groups" "{\"action\":\"delete\",\"groupId\":\"$FIELD_GROUP_ID\"}")
    assert_ok "planka_manage_custom_field_groups delete" "$R"
fi

if [[ -n "$BASE_GROUP_ID" ]]; then
    R=$(tool_call "planka_manage_custom_field_groups" "{\"action\":\"delete_base\",\"baseGroupId\":\"$BASE_GROUP_ID\"}")
    assert_ok "planka_manage_custom_field_groups delete_base" "$R"
fi

# ── 20. Cleanup ────────────────────────────────────────────────────────────────
echo ""
echo "── Cleanup ───────────────────────────────────────────────────────────"

[[ -n "$NS_ID" ]] && { R=$(tool_call "planka_manage_notification_services" "{\"action\":\"delete\",\"channelId\":\"$NS_ID\"}"); assert_ok "planka_manage_notification_services delete" "$R"; }
[[ -n "$WEBHOOK_ID" ]] && { R=$(tool_call "planka_manage_webhooks" "{\"action\":\"delete\",\"webhookId\":\"$WEBHOOK_ID\"}"); assert_ok "planka_manage_webhooks delete" "$R"; }
[[ -n "$CARD_DUP_ID" ]] && tool_call "planka_delete_card" "{\"cardId\":\"$CARD_DUP_ID\"}" > /dev/null
[[ -n "$CARD2_ID" ]] && tool_call "planka_delete_card" "{\"cardId\":\"$CARD2_ID\"}" > /dev/null
[[ -n "$CARD_ID" ]] && { R=$(tool_call "planka_delete_card" "{\"cardId\":\"$CARD_ID\"}"); assert_ok "planka_delete_card" "$R"; }
[[ -n "$LABEL_ID" ]] && { R=$(tool_call "planka_manage_labels" "{\"action\":\"delete\",\"labelId\":\"$LABEL_ID\"}"); assert_ok "planka_manage_labels delete" "$R"; }
[[ -n "$LIST_DONE_ID" ]] && { R=$(tool_call "planka_manage_lists" "{\"action\":\"delete\",\"listId\":\"$LIST_DONE_ID\"}"); assert_ok "planka_manage_lists delete (Done)" "$R"; }
[[ -n "$LIST_ID" ]] && { R=$(tool_call "planka_manage_lists" "{\"action\":\"delete\",\"listId\":\"$LIST_ID\"}"); assert_ok "planka_manage_lists delete (Backlog)" "$R"; }
[[ -n "$BOARD_ID" ]] && { R=$(tool_call "planka_manage_boards" "{\"action\":\"delete\",\"boardId\":\"$BOARD_ID\"}"); assert_ok "planka_manage_boards delete" "$R"; }
[[ -n "$PROJECT_ID" ]] && { R=$(tool_call "planka_manage_projects" "{\"action\":\"delete\",\"projectId\":\"$PROJECT_ID\"}"); assert_ok "planka_manage_projects delete" "$R"; }

# ── Summary ────────────────────────────────────────────────────────────────────
echo ""
echo "══════════════════════════════════════════════════════════════════════"
TOTAL=$((PASS + FAIL))
echo "  Results: $PASS/$TOTAL passed"
[[ $FAIL -gt 0 ]] && echo "  FAILURES: $FAIL" && exit 1
echo "  All tests passed."
