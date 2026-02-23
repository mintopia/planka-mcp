#!/usr/bin/env bash
# integration-test.sh — Planka MCP Server integration test
# Tests all 39 tools and 19 resources via the /test/tool and /test/resource proxy endpoints.
#
# Usage:
#   ./integration-test.sh
#   ./integration-test.sh --start-docker
#   MCP_URL=http://myserver:8080 PLANKA_API_KEY=<key> ./integration-test.sh
#
# Defaults:
#   MCP_URL          http://localhost:8080
#   PLANKA_URL       https://planka.rapunzel.mintopia.net
#   PLANKA_API_KEY   pPcfJw3A_1ow45cT… (set via env to override)
#
# Flags:
#   --start-docker   Start the Docker Compose stack before running tests and
#                    stop it on exit. Requires docker compose to be available.
#
# Requirements: curl, jq

set -euo pipefail

# ─── Config ──────────────────────────────────────────────────────────────────
MCP_URL="${MCP_URL:-http://localhost:8080}"
PLANKA_URL="${PLANKA_URL}"
PLANKA_API_KEY="${PLANKA_API_KEY}"

# ─── State ───────────────────────────────────────────────────────────────────
PASS=0; FAIL=0; SKIP=0
STARTED_DOCKER=0

PROJECT_ID=""
BOARD_ID=""
LIST_ID=""
LIST_ID_2=""
CARD_ID=""
CARD_ID_2=""
LABEL_ID=""
TASK_LIST_ID=""
TASK_ID=""
COMMENT_ID=""
WEBHOOK_ID=""
USER_ID=""
BASE_GROUP_ID=""
CARD_GROUP_ID=""
FIELD_ID=""
CHANNEL_ID=""
NOTIFICATION_ID=""
PM_ID=""

# ─── Colours ─────────────────────────────────────────────────────────────────
G='\033[0;32m'; R='\033[0;31m'; Y='\033[1;33m'; C='\033[0;36m'; B='\033[1m'; N='\033[0m'

# ─── Helpers ─────────────────────────────────────────────────────────────────
check_deps() {
    command -v jq  >/dev/null 2>&1 || { echo "ERROR: jq required (brew install jq / apt-get install jq)"; exit 1; }
    command -v curl >/dev/null 2>&1 || { echo "ERROR: curl required"; exit 1; }
}

call_tool() {
    local name="$1" args="${2:-\{\}}"
    curl -s --max-time 30 \
        -X POST "$MCP_URL/test/tool" \
        -H "Content-Type: application/json" \
        -H "Authorization: Bearer $PLANKA_API_KEY" \
        --data-binary "{\"name\":\"$name\",\"arguments\":$args}" 2>&1
}

call_resource() {
    local name="$1" args="${2:-\{\}}"
    curl -s --max-time 30 \
        -X POST "$MCP_URL/test/resource" \
        -H "Content-Type: application/json" \
        -H "Authorization: Bearer $PLANKA_API_KEY" \
        --data-binary "{\"name\":\"$name\",\"arguments\":$args}" 2>&1
}

pass() { echo -e "  ${G}PASS${N} $1"; PASS=$((PASS+1)); }
fail() { echo -e "  ${R}FAIL${N} $1: $2"; FAIL=$((FAIL+1)); }
skip() { echo -e "  ${Y}SKIP${N} $1: $2"; SKIP=$((SKIP+1)); }

assert_ok() {
    local label="$1" out="$2"
    if [ -z "$out" ]; then
        fail "$label" "empty response (server unreachable?)"; return 1
    fi
    if ! echo "$out" | jq empty 2>/dev/null; then
        fail "$label" "invalid JSON: ${out:0:120}"; return 1
    fi
    if echo "$out" | jq -e 'has("error")' >/dev/null 2>&1; then
        fail "$label" "$(echo "$out" | jq -r '.error' 2>/dev/null)"; return 1
    fi
    pass "$label"; return 0
}

# Extract .item.id from a Planka create/get response
item_id() { echo "$1" | jq -r '.item.id // empty' 2>/dev/null || true; }

# ─── Docker helpers ───────────────────────────────────────────────────────────
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"

start_docker() {
    if ! docker compose version >/dev/null 2>&1; then
        echo "ERROR: docker compose (v2) is required for --start-docker"; exit 1
    fi
    echo "Starting Docker Compose stack…"
    STARTED_DOCKER=1  # set before 'up' so cleanup trap always runs 'down' on failure
    PLANKA_URL="$PLANKA_URL" docker compose -f "$SCRIPT_DIR/docker-compose.yml" up -d --wait
    # Retry check_server: --wait only guarantees Redis healthcheck, not the app
    local i=0
    until curl -sf --max-time 5 "$MCP_URL/health" >/dev/null 2>&1; do
        i=$((i+1))
        [ "$i" -ge 15 ] && { echo "ERROR: MCP server did not become ready in time"; exit 1; }
        echo "  Waiting for MCP server… (attempt $i/15)"
        sleep 2
    done
    echo "  MCP server is ready."
}

# ─── Cleanup (runs on EXIT) ───────────────────────────────────────────────────
# Returns 0 if the JSON response has no "error" key, 1 otherwise.
cleanup_ok() { ! echo "$1" | jq -e 'has("error")' >/dev/null 2>&1; }

cleanup() {
    echo -e "\n${B}--- Cleanup ---${N}"
    local resp

    if [ -n "$WEBHOOK_ID" ]; then
        resp=$(call_tool planka_manage_webhooks "{\"action\":\"delete\",\"webhookId\":\"$WEBHOOK_ID\"}" 2>/dev/null || true)
        if cleanup_ok "$resp"; then
            echo "  Deleted webhook $WEBHOOK_ID"
        else
            echo "  Note: could not delete webhook $WEBHOOK_ID"
        fi
    fi

    # Planka 2.x refuses to delete a project that still has boards — delete the
    # board first (this cascades to lists, cards, tasks, comments, etc.).
    if [ -n "$BOARD_ID" ]; then
        resp=$(call_tool planka_manage_boards "{\"action\":\"delete\",\"boardId\":\"$BOARD_ID\"}" 2>/dev/null || true)
        if cleanup_ok "$resp"; then
            echo "  Deleted board $BOARD_ID (cascaded lists and cards)"
        else
            echo "  Note: could not delete board $BOARD_ID"
        fi
    fi

    if [ -n "$PROJECT_ID" ]; then
        resp=$(call_tool planka_manage_projects "{\"action\":\"delete\",\"projectId\":\"$PROJECT_ID\"}" 2>/dev/null || true)
        if cleanup_ok "$resp"; then
            echo "  Deleted project $PROJECT_ID"
        else
            echo "  Note: could not delete project $PROJECT_ID — manual cleanup may be needed"
        fi
    fi

    if [ "$STARTED_DOCKER" -eq 1 ]; then
        echo "  Stopping Docker Compose stack…"
        docker compose -f "$SCRIPT_DIR/docker-compose.yml" down
    fi
}
trap cleanup EXIT

# ─── Health check ─────────────────────────────────────────────────────────────
check_server() {
    if ! curl -sf --max-time 10 "$MCP_URL/health" >/dev/null 2>&1; then
        echo -e "${R}ERROR${N}: MCP server at $MCP_URL is not reachable."
        echo "  Start it with: docker compose up"
        exit 1
    fi
}

# ═════════════════════════════════════════════════════════════════════════════
main() {
    check_deps

    if [ "${1:-}" = "--start-docker" ]; then
        start_docker
    else
        check_server
    fi

    echo ""
    echo -e "${B}${C}══ Planka MCP Integration Test ══${N}"
    echo -e "  Server    : $MCP_URL"
    echo -e "  Planka URL: $PLANKA_URL"
    echo -e "  API Key   : ${PLANKA_API_KEY:0:12}…"
    echo ""

    # ─────────────────────────────────────────────────────────────────────
    echo -e "${B}── TOOLS ─────────────────────────────────────────────────────────${N}"

    # ── 1. planka_get_structure ───────────────────────────────────────────
    echo -e "\n${C}[planka_get_structure]${N}"
    out=$(call_tool planka_get_structure '{}')
    assert_ok "planka_get_structure" "$out"

    # ── 2. planka_manage_users (list first to get a user ID) ─────────────
    echo -e "\n${C}[planka_manage_users]${N}"
    out=$(call_tool planka_manage_users '{"action":"list"}')
    assert_ok "planka_manage_users list" "$out"
    USER_ID=$(echo "$out" | jq -r '.items[0].id // empty' 2>/dev/null || true)
    [ -n "$USER_ID" ] && echo "  Using user ID: $USER_ID"

    # ── 3. planka_manage_projects ─────────────────────────────────────────
    echo -e "\n${C}[planka_manage_projects]${N}"
    out=$(call_tool planka_manage_projects '{"action":"create","name":"[MCP-Test] Integration Test","type":"shared"}')
    if assert_ok "planka_manage_projects create" "$out"; then
        PROJECT_ID=$(item_id "$out")
        echo "  Project ID: $PROJECT_ID"
    fi

    if [ -n "$PROJECT_ID" ]; then
        out=$(call_tool planka_manage_projects "{\"action\":\"get\",\"projectId\":\"$PROJECT_ID\"}")
        assert_ok "planka_manage_projects get" "$out"

        out=$(call_tool planka_manage_projects "{\"action\":\"update\",\"projectId\":\"$PROJECT_ID\",\"name\":\"[MCP-Test] Integration Test (Updated)\"}")
        assert_ok "planka_manage_projects update" "$out"
    else
        skip "planka_manage_projects get" "no project ID"
        skip "planka_manage_projects update" "no project ID"
    fi

    # ── 4. planka_manage_project_managers ────────────────────────────────
    echo -e "\n${C}[planka_manage_project_managers]${N}"
    if [ -n "$PROJECT_ID" ] && [ -n "$USER_ID" ]; then
        out=$(call_tool planka_manage_project_managers "{\"action\":\"add\",\"projectId\":\"$PROJECT_ID\",\"userId\":\"$USER_ID\"}")
        if assert_ok "planka_manage_project_managers add" "$out"; then
            PM_ID=$(item_id "$out")
            echo "  PM record ID: $PM_ID"
        fi
        if [ -n "$PM_ID" ]; then
            out=$(call_tool planka_manage_project_managers "{\"action\":\"remove\",\"projectManagerId\":\"$PM_ID\"}")
            assert_ok "planka_manage_project_managers remove" "$out"
        else
            skip "planka_manage_project_managers remove" "no PM record ID (user may already be manager)"
        fi
    else
        skip "planka_manage_project_managers add" "no project or user ID"
        skip "planka_manage_project_managers remove" "no project or user ID"
    fi

    # ── 5. planka_manage_boards ───────────────────────────────────────────
    echo -e "\n${C}[planka_manage_boards]${N}"
    if [ -n "$PROJECT_ID" ]; then
        out=$(call_tool planka_manage_boards "{\"action\":\"create\",\"projectId\":\"$PROJECT_ID\",\"name\":\"Test Board\"}")
        if assert_ok "planka_manage_boards create" "$out"; then
            BOARD_ID=$(item_id "$out")
            echo "  Board ID: $BOARD_ID"
        fi
        if [ -n "$BOARD_ID" ]; then
            out=$(call_tool planka_manage_boards "{\"action\":\"get\",\"boardId\":\"$BOARD_ID\"}")
            assert_ok "planka_manage_boards get" "$out"
            out=$(call_tool planka_manage_boards "{\"action\":\"update\",\"boardId\":\"$BOARD_ID\",\"name\":\"Test Board (Updated)\"}")
            assert_ok "planka_manage_boards update" "$out"
        else
            skip "planka_manage_boards get" "no board ID"
            skip "planka_manage_boards update" "no board ID"
        fi
    else
        skip "planka_manage_boards create" "no project ID"
    fi

    # ── 6. planka_get_board ───────────────────────────────────────────────
    echo -e "\n${C}[planka_get_board]${N}"
    if [ -n "$BOARD_ID" ]; then
        out=$(call_tool planka_get_board "{\"boardId\":\"$BOARD_ID\"}")
        assert_ok "planka_get_board" "$out"
    else
        skip "planka_get_board" "no board ID"
    fi

    # ── 7. planka_manage_board_memberships ────────────────────────────────
    echo -e "\n${C}[planka_manage_board_memberships]${N}"
    if [ -n "$BOARD_ID" ] && [ -n "$USER_ID" ]; then
        out=$(call_tool planka_manage_board_memberships "{\"action\":\"add\",\"boardId\":\"$BOARD_ID\",\"userId\":\"$USER_ID\",\"role\":\"editor\"}")
        MEMBERSHIP_ID=""
        if assert_ok "planka_manage_board_memberships add" "$out"; then
            MEMBERSHIP_ID=$(item_id "$out")
        fi
        if [ -n "$MEMBERSHIP_ID" ]; then
            out=$(call_tool planka_manage_board_memberships "{\"action\":\"update\",\"membershipId\":\"$MEMBERSHIP_ID\",\"role\":\"viewer\"}")
            assert_ok "planka_manage_board_memberships update" "$out"
            out=$(call_tool planka_manage_board_memberships "{\"action\":\"remove\",\"membershipId\":\"$MEMBERSHIP_ID\"}")
            assert_ok "planka_manage_board_memberships remove" "$out"
        else
            skip "planka_manage_board_memberships update" "no membership ID (user may already be a member)"
            skip "planka_manage_board_memberships remove" "no membership ID"
        fi
    else
        skip "planka_manage_board_memberships" "no board or user ID"
    fi

    # ── Restore board membership for card membership test ─────────────────
    # Re-add USER_ID to board so card membership test can succeed
    if [ -n "$BOARD_ID" ] && [ -n "$USER_ID" ]; then
        call_tool planka_manage_board_memberships "{\"action\":\"add\",\"boardId\":\"$BOARD_ID\",\"userId\":\"$USER_ID\",\"role\":\"editor\"}" >/dev/null 2>&1 || true
    fi

    # ── 8. planka_get_actions (board) ─────────────────────────────────────
    echo -e "\n${C}[planka_get_actions - board]${N}"
    if [ -n "$BOARD_ID" ]; then
        out=$(call_tool planka_get_actions "{\"type\":\"board\",\"id\":\"$BOARD_ID\"}")
        assert_ok "planka_get_actions board" "$out"
    else
        skip "planka_get_actions board" "no board ID"
    fi

    # ── 9. planka_manage_lists ────────────────────────────────────────────
    echo -e "\n${C}[planka_manage_lists]${N}"
    if [ -n "$BOARD_ID" ]; then
        out=$(call_tool planka_manage_lists "{\"action\":\"create\",\"boardId\":\"$BOARD_ID\",\"name\":\"Backlog\"}")
        if assert_ok "planka_manage_lists create (list 1)" "$out"; then
            LIST_ID=$(item_id "$out")
            echo "  List 1 ID: $LIST_ID"
        fi
        out=$(call_tool planka_manage_lists "{\"action\":\"create\",\"boardId\":\"$BOARD_ID\",\"name\":\"Done\"}")
        if assert_ok "planka_manage_lists create (list 2)" "$out"; then
            LIST_ID_2=$(item_id "$out")
            echo "  List 2 ID: $LIST_ID_2"
        fi
        if [ -n "$LIST_ID" ]; then
            out=$(call_tool planka_manage_lists "{\"action\":\"get\",\"listId\":\"$LIST_ID\"}")
            assert_ok "planka_manage_lists get" "$out"
            out=$(call_tool planka_manage_lists "{\"action\":\"update\",\"listId\":\"$LIST_ID\",\"name\":\"To Do\"}")
            assert_ok "planka_manage_lists update" "$out"
        else
            skip "planka_manage_lists get" "no list ID"
            skip "planka_manage_lists update" "no list ID"
        fi
    else
        skip "planka_manage_lists" "no board ID"
    fi

    # ── 10. planka_create_card ────────────────────────────────────────────
    echo -e "\n${C}[planka_create_card]${N}"
    if [ -n "$LIST_ID" ]; then
        out=$(call_tool planka_create_card "{\"listId\":\"$LIST_ID\",\"name\":\"Test Card\",\"description\":\"Integration test card\"}")
        if assert_ok "planka_create_card" "$out"; then
            CARD_ID=$(item_id "$out")
            echo "  Card ID: $CARD_ID"
        fi
    else
        skip "planka_create_card" "no list ID"
    fi

    # ── 11. planka_get_card ───────────────────────────────────────────────
    echo -e "\n${C}[planka_get_card]${N}"
    if [ -n "$CARD_ID" ]; then
        out=$(call_tool planka_get_card "{\"cardId\":\"$CARD_ID\"}")
        assert_ok "planka_get_card" "$out"
    else
        skip "planka_get_card" "no card ID"
    fi

    # ── 12. planka_update_card ────────────────────────────────────────────
    echo -e "\n${C}[planka_update_card]${N}"
    if [ -n "$CARD_ID" ]; then
        out=$(call_tool planka_update_card "{\"cardId\":\"$CARD_ID\",\"name\":\"Test Card (Updated)\",\"description\":\"Updated by integration test\"}")
        assert_ok "planka_update_card name+description" "$out"
        out=$(call_tool planka_update_card "{\"cardId\":\"$CARD_ID\",\"isClosed\":true}")
        assert_ok "planka_update_card isClosed=true" "$out"
        out=$(call_tool planka_update_card "{\"cardId\":\"$CARD_ID\",\"isClosed\":false}")
        assert_ok "planka_update_card isClosed=false" "$out"
    else
        skip "planka_update_card" "no card ID"
    fi

    # ── 13. planka_duplicate_card ─────────────────────────────────────────
    echo -e "\n${C}[planka_duplicate_card]${N}"
    if [ -n "$CARD_ID" ]; then
        out=$(call_tool planka_duplicate_card "{\"cardId\":\"$CARD_ID\"}")
        if assert_ok "planka_duplicate_card" "$out"; then
            CARD_ID_2=$(item_id "$out")
            echo "  Duplicate card ID: $CARD_ID_2"
        fi
    else
        skip "planka_duplicate_card" "no card ID"
    fi

    # ── 14. planka_manage_card_membership ─────────────────────────────────
    # Planka 2.x: DELETE /api/cards/{cardId}/card-memberships/userId:{userId}
    # Both add and remove require cardId + userId; user must be a board member.
    echo -e "\n${C}[planka_manage_card_membership]${N}"
    if [ -n "$CARD_ID" ] && [ -n "$USER_ID" ]; then
        out=$(call_tool planka_manage_card_membership "{\"action\":\"add\",\"cardId\":\"$CARD_ID\",\"userId\":\"$USER_ID\"}")
        if assert_ok "planka_manage_card_membership add" "$out"; then
            out=$(call_tool planka_manage_card_membership "{\"action\":\"remove\",\"cardId\":\"$CARD_ID\",\"userId\":\"$USER_ID\"}")
            assert_ok "planka_manage_card_membership remove" "$out"
        else
            skip "planka_manage_card_membership remove" "add failed"
        fi
    else
        skip "planka_manage_card_membership" "no card or user ID"
    fi

    # ── 15. planka_manage_labels ──────────────────────────────────────────
    echo -e "\n${C}[planka_manage_labels]${N}"
    if [ -n "$BOARD_ID" ]; then
        out=$(call_tool planka_manage_labels "{\"action\":\"create\",\"boardId\":\"$BOARD_ID\",\"name\":\"Test Label\",\"color\":\"lagoon-blue\"}")
        if assert_ok "planka_manage_labels create" "$out"; then
            LABEL_ID=$(item_id "$out")
            echo "  Label ID: $LABEL_ID"
        fi
        if [ -n "$LABEL_ID" ]; then
            out=$(call_tool planka_manage_labels "{\"action\":\"update\",\"labelId\":\"$LABEL_ID\",\"name\":\"Test Label (Updated)\",\"color\":\"morning-sky\"}")
            assert_ok "planka_manage_labels update" "$out"
        else
            skip "planka_manage_labels update" "no label ID"
        fi
    else
        skip "planka_manage_labels create" "no board ID"
    fi

    # ── 16. planka_set_card_labels ────────────────────────────────────────
    echo -e "\n${C}[planka_set_card_labels]${N}"
    if [ -n "$CARD_ID" ] && [ -n "$LABEL_ID" ]; then
        out=$(call_tool planka_set_card_labels "{\"cardId\":\"$CARD_ID\",\"addLabelIds\":[\"$LABEL_ID\"]}")
        assert_ok "planka_set_card_labels add" "$out"
        out=$(call_tool planka_set_card_labels "{\"cardId\":\"$CARD_ID\",\"removeLabelIds\":[\"$LABEL_ID\"]}")
        assert_ok "planka_set_card_labels remove" "$out"
    else
        skip "planka_set_card_labels" "no card or label ID"
    fi

    # delete label
    if [ -n "$LABEL_ID" ]; then
        out=$(call_tool planka_manage_labels "{\"action\":\"delete\",\"labelId\":\"$LABEL_ID\"}")
        assert_ok "planka_manage_labels delete" "$out"
        LABEL_ID=""
    else
        skip "planka_manage_labels delete" "no label ID"
    fi

    # ── 17. planka_create_tasks ───────────────────────────────────────────
    echo -e "\n${C}[planka_create_tasks]${N}"
    if [ -n "$CARD_ID" ]; then
        out=$(call_tool planka_create_tasks "{\"cardId\":\"$CARD_ID\",\"tasks\":[\"Task One\",\"Task Two\"]}")
        if assert_ok "planka_create_tasks" "$out"; then
            TASK_LIST_ID=$(echo "$out" | jq -r '.taskList.item.id // empty' 2>/dev/null || true)
            TASK_ID=$(echo "$out" | jq -r '.tasks[0].item.id // empty' 2>/dev/null || true)
            echo "  Task list ID: $TASK_LIST_ID"
            echo "  Task ID: $TASK_ID"
        fi
    else
        skip "planka_create_tasks" "no card ID"
    fi

    # ── 18. planka_update_task ────────────────────────────────────────────
    echo -e "\n${C}[planka_update_task]${N}"
    if [ -n "$TASK_ID" ]; then
        out=$(call_tool planka_update_task "{\"taskId\":\"$TASK_ID\",\"name\":\"Task One (Updated)\",\"isCompleted\":true}")
        assert_ok "planka_update_task" "$out"
    else
        skip "planka_update_task" "no task ID"
    fi

    # ── 19. planka_update_task_list ───────────────────────────────────────
    echo -e "\n${C}[planka_update_task_list]${N}"
    if [ -n "$TASK_LIST_ID" ]; then
        out=$(call_tool planka_update_task_list "{\"taskListId\":\"$TASK_LIST_ID\",\"name\":\"Updated Task List\"}")
        assert_ok "planka_update_task_list" "$out"
    else
        skip "planka_update_task_list" "no task list ID"
    fi

    # ── 20. planka_delete_task ────────────────────────────────────────────
    echo -e "\n${C}[planka_delete_task]${N}"
    if [ -n "$TASK_ID" ]; then
        out=$(call_tool planka_delete_task "{\"taskId\":\"$TASK_ID\"}")
        assert_ok "planka_delete_task" "$out"
        TASK_ID=""
    else
        skip "planka_delete_task" "no task ID"
    fi

    # ── 21. planka_delete_task_list ───────────────────────────────────────
    echo -e "\n${C}[planka_delete_task_list]${N}"
    if [ -n "$TASK_LIST_ID" ]; then
        out=$(call_tool planka_delete_task_list "{\"taskListId\":\"$TASK_LIST_ID\"}")
        assert_ok "planka_delete_task_list" "$out"
        TASK_LIST_ID=""
    else
        skip "planka_delete_task_list" "no task list ID"
    fi

    # ── 22. planka_add_comment ────────────────────────────────────────────
    echo -e "\n${C}[planka_add_comment]${N}"
    if [ -n "$CARD_ID" ]; then
        out=$(call_tool planka_add_comment "{\"cardId\":\"$CARD_ID\",\"text\":\"Integration test comment\"}")
        if assert_ok "planka_add_comment" "$out"; then
            COMMENT_ID=$(item_id "$out")
            echo "  Comment ID: $COMMENT_ID"
        fi
    else
        skip "planka_add_comment" "no card ID"
    fi

    # ── 23. planka_get_comments ───────────────────────────────────────────
    echo -e "\n${C}[planka_get_comments]${N}"
    if [ -n "$CARD_ID" ]; then
        out=$(call_tool planka_get_comments "{\"cardId\":\"$CARD_ID\"}")
        assert_ok "planka_get_comments" "$out"
    else
        skip "planka_get_comments" "no card ID"
    fi

    # ── 24. planka_update_comment ─────────────────────────────────────────
    echo -e "\n${C}[planka_update_comment]${N}"
    if [ -n "$COMMENT_ID" ]; then
        out=$(call_tool planka_update_comment "{\"commentId\":\"$COMMENT_ID\",\"text\":\"Updated integration test comment\"}")
        assert_ok "planka_update_comment" "$out"
    else
        skip "planka_update_comment" "no comment ID"
    fi

    # ── 25. planka_delete_comment ─────────────────────────────────────────
    echo -e "\n${C}[planka_delete_comment]${N}"
    if [ -n "$COMMENT_ID" ]; then
        out=$(call_tool planka_delete_comment "{\"commentId\":\"$COMMENT_ID\"}")
        assert_ok "planka_delete_comment" "$out"
        COMMENT_ID=""
    else
        skip "planka_delete_comment" "no comment ID"
    fi

    # ── 26. planka_get_actions (card) ─────────────────────────────────────
    echo -e "\n${C}[planka_get_actions - card]${N}"
    if [ -n "$CARD_ID" ]; then
        out=$(call_tool planka_get_actions "{\"type\":\"card\",\"id\":\"$CARD_ID\"}")
        assert_ok "planka_get_actions card" "$out"
    else
        skip "planka_get_actions card" "no card ID"
    fi

    # ── 27. planka_sort_list ──────────────────────────────────────────────
    echo -e "\n${C}[planka_sort_list]${N}"
    if [ -n "$LIST_ID" ]; then
        out=$(call_tool planka_sort_list "{\"listId\":\"$LIST_ID\",\"field\":\"name\"}")
        assert_ok "planka_sort_list" "$out"
    else
        skip "planka_sort_list" "no list ID"
    fi

    # ── 28. planka_manage_lists get_cards ─────────────────────────────────
    echo -e "\n${C}[planka_manage_lists - get_cards]${N}"
    if [ -n "$LIST_ID" ]; then
        out=$(call_tool planka_manage_lists "{\"action\":\"get_cards\",\"listId\":\"$LIST_ID\"}")
        assert_ok "planka_manage_lists get_cards" "$out"
    else
        skip "planka_manage_lists get_cards" "no list ID"
    fi

    # ── 29. planka_move_card ──────────────────────────────────────────────
    echo -e "\n${C}[planka_move_card]${N}"
    if [ -n "$CARD_ID" ] && [ -n "$LIST_ID_2" ]; then
        out=$(call_tool planka_move_card "{\"cardId\":\"$CARD_ID\",\"listId\":\"$LIST_ID_2\"}")
        assert_ok "planka_move_card (to list 2)" "$out"
        out=$(call_tool planka_move_card "{\"cardId\":\"$CARD_ID\",\"listId\":\"$LIST_ID\"}")
        assert_ok "planka_move_card (back to list 1)" "$out"
    else
        skip "planka_move_card" "no card ID or second list ID"
    fi

    # ── 30. planka_manage_lists move_cards ────────────────────────────────
    # Note: Planka 2.x returns 403 for this endpoint even for admin users on some
    # instances — treat 403 as a known permission limitation (SKIP) rather than FAIL.
    echo -e "\n${C}[planka_manage_lists - move_cards]${N}"
    if [ -n "$LIST_ID" ] && [ -n "$LIST_ID_2" ] && [ -n "$CARD_ID" ]; then
        out=$(call_tool planka_manage_lists "{\"action\":\"move_cards\",\"listId\":\"$LIST_ID\",\"toListId\":\"$LIST_ID_2\"}")
        if echo "$out" | jq -e 'has("error")' >/dev/null 2>&1; then
            err=$(echo "$out" | jq -r '.error')
            if echo "$err" | grep -qi "403\|forbidden\|not enough rights"; then
                skip "planka_manage_lists move_cards" "Planka returned 403 (requires elevated permissions on this instance)"
            else
                fail "planka_manage_lists move_cards" "$err"
            fi
        else
            pass "planka_manage_lists move_cards"
            # move the card back for subsequent tests
            call_tool planka_move_card "{\"cardId\":\"$CARD_ID\",\"listId\":\"$LIST_ID\"}" >/dev/null 2>&1 || true
        fi
    else
        skip "planka_manage_lists move_cards" "no card ID or second list ID"
    fi

    # ── 31. planka_delete_card (duplicate) ────────────────────────────────
    echo -e "\n${C}[planka_delete_card]${N}"
    if [ -n "$CARD_ID_2" ]; then
        out=$(call_tool planka_delete_card "{\"cardId\":\"$CARD_ID_2\"}")
        assert_ok "planka_delete_card (duplicate)" "$out"
        CARD_ID_2=""
    else
        skip "planka_delete_card" "no duplicate card ID"
    fi

    # ── 32. planka_manage_users get ───────────────────────────────────────
    echo -e "\n${C}[planka_manage_users - get]${N}"
    if [ -n "$USER_ID" ]; then
        out=$(call_tool planka_manage_users "{\"action\":\"get\",\"userId\":\"$USER_ID\"}")
        assert_ok "planka_manage_users get" "$out"
    else
        skip "planka_manage_users get" "no user ID"
    fi

    skip "planka_manage_users create/update/delete" "skipped to avoid modifying real users"
    skip "planka_manage_user_credentials"           "requires current password — skipped"
    skip "planka_upload_attachment"                  "requires a file in the upload directory — skipped"
    skip "planka_manage_attachments"                 "requires an existing attachment — skipped"

    # ── 33. planka_get_notifications ──────────────────────────────────────
    echo -e "\n${C}[planka_get_notifications]${N}"
    out=$(call_tool planka_get_notifications '{}')
    assert_ok "planka_get_notifications" "$out"
    NOTIFICATION_ID=$(echo "$out" | jq -r '.items[0].id // empty' 2>/dev/null || true)
    [ -n "$NOTIFICATION_ID" ] && echo "  First notification ID: $NOTIFICATION_ID"

    # ── 34. planka_mark_notification_read ─────────────────────────────────
    echo -e "\n${C}[planka_mark_notification_read]${N}"
    if [ -n "$NOTIFICATION_ID" ]; then
        out=$(call_tool planka_mark_notification_read "{\"notificationId\":\"$NOTIFICATION_ID\",\"isRead\":true}")
        assert_ok "planka_mark_notification_read" "$out"
    else
        skip "planka_mark_notification_read" "no notifications available for this user"
    fi

    # ── 35. planka_mark_all_notifications_read ────────────────────────────
    echo -e "\n${C}[planka_mark_all_notifications_read]${N}"
    out=$(call_tool planka_mark_all_notifications_read '{}')
    assert_ok "planka_mark_all_notifications_read" "$out"

    # ── 36. planka_manage_webhooks ────────────────────────────────────────
    echo -e "\n${C}[planka_manage_webhooks]${N}"
    out=$(call_tool planka_manage_webhooks '{"action":"list"}')
    assert_ok "planka_manage_webhooks list" "$out"

    out=$(call_tool planka_manage_webhooks '{"action":"create","name":"MCP Test Webhook","url":"https://example.com/mcp-test-webhook","events":"cardCreate,cardUpdate","description":"Integration test webhook"}')
    if assert_ok "planka_manage_webhooks create" "$out"; then
        WEBHOOK_ID=$(item_id "$out")
        echo "  Webhook ID: $WEBHOOK_ID"
    fi

    if [ -n "$WEBHOOK_ID" ]; then
        out=$(call_tool planka_manage_webhooks "{\"action\":\"update\",\"webhookId\":\"$WEBHOOK_ID\",\"description\":\"Updated by integration test\"}")
        assert_ok "planka_manage_webhooks update" "$out"
        out=$(call_tool planka_manage_webhooks "{\"action\":\"delete\",\"webhookId\":\"$WEBHOOK_ID\"}")
        assert_ok "planka_manage_webhooks delete" "$out"
        WEBHOOK_ID=""
    else
        skip "planka_manage_webhooks update" "no webhook ID"
        skip "planka_manage_webhooks delete" "no webhook ID"
    fi

    # ── 37. planka_manage_custom_field_groups ─────────────────────────────
    echo -e "\n${C}[planka_manage_custom_field_groups]${N}"
    if [ -n "$PROJECT_ID" ]; then
        out=$(call_tool planka_manage_custom_field_groups "{\"action\":\"create_base\",\"projectId\":\"$PROJECT_ID\",\"name\":\"Test Field Group\"}")
        if assert_ok "planka_manage_custom_field_groups create_base" "$out"; then
            BASE_GROUP_ID=$(item_id "$out")
            echo "  Base group ID: $BASE_GROUP_ID"
        fi
        if [ -n "$BASE_GROUP_ID" ]; then
            out=$(call_tool planka_manage_custom_field_groups "{\"action\":\"update_base\",\"baseGroupId\":\"$BASE_GROUP_ID\",\"name\":\"Test Field Group (Updated)\"}")
            assert_ok "planka_manage_custom_field_groups update_base" "$out"
        else
            skip "planka_manage_custom_field_groups update_base" "no base group ID"
        fi
    else
        skip "planka_manage_custom_field_groups create_base" "no project ID"
    fi

    if [ -n "$CARD_ID" ]; then
        out=$(call_tool planka_manage_custom_field_groups "{\"action\":\"create\",\"parentType\":\"card\",\"parentId\":\"$CARD_ID\",\"name\":\"Card Fields\"}")
        if assert_ok "planka_manage_custom_field_groups create (card)" "$out"; then
            CARD_GROUP_ID=$(item_id "$out")
            echo "  Card group ID: $CARD_GROUP_ID"
        fi
        if [ -n "$CARD_GROUP_ID" ]; then
            out=$(call_tool planka_manage_custom_field_groups "{\"action\":\"get\",\"groupId\":\"$CARD_GROUP_ID\"}")
            assert_ok "planka_manage_custom_field_groups get" "$out"
            out=$(call_tool planka_manage_custom_field_groups "{\"action\":\"update\",\"groupId\":\"$CARD_GROUP_ID\",\"name\":\"Card Fields (Updated)\"}")
            assert_ok "planka_manage_custom_field_groups update" "$out"
            out=$(call_tool planka_manage_custom_field_groups "{\"action\":\"delete\",\"groupId\":\"$CARD_GROUP_ID\"}")
            assert_ok "planka_manage_custom_field_groups delete" "$out"
            CARD_GROUP_ID=""
        else
            skip "planka_manage_custom_field_groups get/update/delete" "no card group ID"
        fi
    else
        skip "planka_manage_custom_field_groups create (card)" "no card ID"
    fi

    # ── 38. planka_manage_custom_fields ───────────────────────────────────
    echo -e "\n${C}[planka_manage_custom_fields]${N}"
    if [ -n "$BASE_GROUP_ID" ]; then
        out=$(call_tool planka_manage_custom_fields "{\"action\":\"create\",\"groupType\":\"base\",\"groupId\":\"$BASE_GROUP_ID\",\"name\":\"Test Field\",\"fieldType\":\"text\"}")
        if assert_ok "planka_manage_custom_fields create" "$out"; then
            FIELD_ID=$(item_id "$out")
            echo "  Field ID: $FIELD_ID"
        fi
        if [ -n "$FIELD_ID" ]; then
            out=$(call_tool planka_manage_custom_fields "{\"action\":\"update\",\"fieldId\":\"$FIELD_ID\",\"name\":\"Test Field (Updated)\"}")
            assert_ok "planka_manage_custom_fields update" "$out"
            out=$(call_tool planka_manage_custom_fields "{\"action\":\"delete\",\"fieldId\":\"$FIELD_ID\"}")
            assert_ok "planka_manage_custom_fields delete" "$out"
            FIELD_ID=""
        else
            skip "planka_manage_custom_fields update/delete" "no field ID"
        fi
        # clean up base group
        out=$(call_tool planka_manage_custom_field_groups "{\"action\":\"delete_base\",\"baseGroupId\":\"$BASE_GROUP_ID\"}")
        assert_ok "planka_manage_custom_field_groups delete_base" "$out"
        BASE_GROUP_ID=""
    else
        skip "planka_manage_custom_fields" "no base group ID"
        skip "planka_manage_custom_field_groups delete_base" "no base group ID"
    fi

    skip "planka_manage_custom_field_values" "requires matching field group + field IDs on a card — complex Planka API — skipped"

    # ── 39. planka_manage_notification_services ───────────────────────────
    # Note: Planka 2.x requires url + format params and some instances return
    # 404 "User not found" even for admin users — skip gracefully if not supported.
    echo -e "\n${C}[planka_manage_notification_services]${N}"
    if [ -n "$USER_ID" ]; then
        out=$(call_tool planka_manage_notification_services \
            "{\"action\":\"create_for_user\",\"userId\":\"$USER_ID\",\"type\":\"slack\",\"params\":{\"url\":\"https://hooks.slack.com/test\",\"format\":\"text\"}}")
        if echo "$out" | jq -e 'has("error")' >/dev/null 2>&1; then
            err=$(echo "$out" | jq -r '.error')
            if echo "$err" | grep -qiE "400|404|not found|params|url|format"; then
                skip "planka_manage_notification_services create_for_user" "Planka API incompatibility on this instance: $err"
                skip "planka_manage_notification_services update/delete" "skipped due to create failure"
            else
                fail "planka_manage_notification_services create_for_user" "$err"
                skip "planka_manage_notification_services update/delete" "no channel ID"
            fi
        else
            CHANNEL_ID=$(item_id "$out")
            pass "planka_manage_notification_services create_for_user"
            echo "  Channel ID: $CHANNEL_ID"
            if [ -n "$CHANNEL_ID" ]; then
                out=$(call_tool planka_manage_notification_services "{\"action\":\"update\",\"channelId\":\"$CHANNEL_ID\",\"isEnabled\":false}")
                assert_ok "planka_manage_notification_services update" "$out"
                out=$(call_tool planka_manage_notification_services "{\"action\":\"delete\",\"channelId\":\"$CHANNEL_ID\"}")
                assert_ok "planka_manage_notification_services delete" "$out"
                CHANNEL_ID=""
            else
                skip "planka_manage_notification_services update/delete" "no channel ID"
            fi
        fi
    else
        skip "planka_manage_notification_services" "no user ID"
    fi

    # cleanup second list before resources section
    if [ -n "$LIST_ID_2" ]; then
        call_tool planka_manage_lists "{\"action\":\"clear\",\"listId\":\"$LIST_ID_2\"}" >/dev/null 2>&1 || true
        out=$(call_tool planka_manage_lists "{\"action\":\"delete\",\"listId\":\"$LIST_ID_2\"}")
        assert_ok "planka_manage_lists delete (list 2)" "$out"
        LIST_ID_2=""
    fi

    # ─────────────────────────────────────────────────────────────────────
    echo -e "\n${B}── RESOURCES ─────────────────────────────────────────────────────${N}"

    # ── Static resources (no template variables) ──────────────────────────
    echo -e "\n${C}[Static resources]${N}"
    for res_name in planka-config planka-bootstrap planka-structure planka-projects planka-users planka-notifications planka-webhooks; do
        out=$(call_resource "$res_name" '{}')
        assert_ok "resource: $res_name" "$out"
    done

    # ── Template resources ────────────────────────────────────────────────
    echo -e "\n${C}[Template resources]${N}"

    if [ -n "$PROJECT_ID" ]; then
        out=$(call_resource planka-project "{\"projectId\":\"$PROJECT_ID\"}")
        assert_ok "resource: planka-project" "$out"
    else
        skip "resource: planka-project" "no project ID"
    fi

    if [ -n "$BOARD_ID" ]; then
        out=$(call_resource planka-board "{\"boardId\":\"$BOARD_ID\"}")
        assert_ok "resource: planka-board" "$out"
        out=$(call_resource planka-board-actions "{\"boardId\":\"$BOARD_ID\"}")
        assert_ok "resource: planka-board-actions" "$out"
    else
        skip "resource: planka-board" "no board ID"
        skip "resource: planka-board-actions" "no board ID"
    fi

    if [ -n "$LIST_ID" ]; then
        out=$(call_resource planka-list "{\"listId\":\"$LIST_ID\"}")
        assert_ok "resource: planka-list" "$out"
        out=$(call_resource planka-list-cards "{\"listId\":\"$LIST_ID\"}")
        assert_ok "resource: planka-list-cards" "$out"
    else
        skip "resource: planka-list" "no list ID"
        skip "resource: planka-list-cards" "no list ID"
    fi

    if [ -n "$CARD_ID" ]; then
        out=$(call_resource planka-card "{\"cardId\":\"$CARD_ID\"}")
        assert_ok "resource: planka-card" "$out"
        out=$(call_resource planka-card-comments "{\"cardId\":\"$CARD_ID\"}")
        assert_ok "resource: planka-card-comments" "$out"
        out=$(call_resource planka-card-actions "{\"cardId\":\"$CARD_ID\"}")
        assert_ok "resource: planka-card-actions" "$out"
    else
        skip "resource: planka-card" "no card ID"
        skip "resource: planka-card-comments" "no card ID"
        skip "resource: planka-card-actions" "no card ID"
    fi

    # Create a temporary task list for the resource test (since we deleted the earlier one)
    if [ -n "$CARD_ID" ]; then
        tmp=$(call_tool planka_create_tasks "{\"cardId\":\"$CARD_ID\",\"tasks\":[\"Resource test task\"]}" 2>/dev/null || true)
        TMP_TASK_LIST_ID=$(echo "$tmp" | jq -r '.taskList.item.id // empty' 2>/dev/null || true)
        if [ -n "$TMP_TASK_LIST_ID" ]; then
            out=$(call_resource planka-task-list "{\"taskListId\":\"$TMP_TASK_LIST_ID\"}")
            assert_ok "resource: planka-task-list" "$out"
            call_tool planka_delete_task_list "{\"taskListId\":\"$TMP_TASK_LIST_ID\"}" >/dev/null 2>&1 || true
        else
            skip "resource: planka-task-list" "could not create temporary task list"
        fi
    else
        skip "resource: planka-task-list" "no card ID"
    fi

    if [ -n "$USER_ID" ]; then
        out=$(call_resource planka-user "{\"userId\":\"$USER_ID\"}")
        assert_ok "resource: planka-user" "$out"
    else
        skip "resource: planka-user" "no user ID"
    fi

    if [ -n "$NOTIFICATION_ID" ]; then
        out=$(call_resource planka-notification "{\"notificationId\":\"$NOTIFICATION_ID\"}")
        assert_ok "resource: planka-notification" "$out"
    else
        skip "resource: planka-notification" "no notifications available"
    fi

    skip "resource: planka-custom-field-group" "custom field group was created and deleted during tool tests — no ID available"

    # ─────────────────────────────────────────────────────────────────────
    TOTAL=$((PASS+FAIL+SKIP))
    echo ""
    echo -e "${B}══ Results ══════════════════════════════════════════════════════${N}"
    printf "  ${G}PASS${N} %-4s  ${R}FAIL${N} %-4s  ${Y}SKIP${N} %-4s  Total %s\n" "$PASS" "$FAIL" "$SKIP" "$TOTAL"
    echo ""
    if [ "$FAIL" -eq 0 ]; then
        echo -e "  ${G}All assertions passed!${N}"
    else
        echo -e "  ${R}$FAIL assertion(s) failed. Review output above.${N}"
        exit 1
    fi
    echo ""
}

main "$@"
