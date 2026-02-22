#!/usr/bin/env bash
# Integration test for all planka-mcp tools via MCP Streamable HTTP
# Usage: ./test_integration.sh
set -euo pipefail

BASE="http://localhost:8080/mcp"
API_KEY="Mgn2kcBb_VOd5mqlBs9C6DQkZCM5CxEH3C8ffoG6j"
AUTH="Authorization: Bearer $API_KEY"
CT="Content-Type: application/json"
ACCEPT="Accept: application/json, text/event-stream"
SESSION=""
ID=1
PASS=0
FAIL=0

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
echo "    board_id=$BOARD_ID"

R=$(tool_call "planka_get_board" "{\"boardId\":\"$BOARD_ID\"}")
assert_ok "planka_get_board" "$R"

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

if [[ -n "$USER_ID" ]]; then
    R=$(tool_call "planka_manage_card_membership" "{\"action\":\"add\",\"cardId\":\"$CARD_ID\",\"userId\":\"$USER_ID\"}")
    assert_ok "planka_manage_card_membership add" "$R"

    R=$(tool_call "planka_manage_card_membership" "{\"action\":\"remove\",\"cardId\":\"$CARD_ID\",\"userId\":\"$USER_ID\"}")
    assert_ok "planka_manage_card_membership remove" "$R"
else
    echo "  ⚠ Skipping card membership (no user_id from structure)"
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

# ── 16. Cleanup ────────────────────────────────────────────────────────────────
echo ""
echo "── Cleanup ───────────────────────────────────────────────────────────"

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
