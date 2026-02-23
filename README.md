# planka-mcp

A [Model Context Protocol (MCP)](https://modelcontextprotocol.io) server that exposes [Planka 2](https://planka.app) kanban board operations as AI resources and tools.

Built with Laravel 12 and FrankenPHP, running in Octane worker mode inside Docker. Multi-tenant: each MCP client supplies its own Planka API key per request — no credentials are stored server-side.

## Resources

MCP resources are read-only, URI-addressed views of Planka data. MCP clients can read them directly by URI.

### System

| Resource | URI |
|---|---|
| Config | `planka://config` |
| Bootstrap | `planka://bootstrap` |

### Structure

| Resource | URI |
|---|---|
| Structure | `planka://structure` |
| Projects | `planka://projects` |
| Project | `planka://projects/{projectId}` |

### Boards

| Resource | URI |
|---|---|
| Board | `planka://boards/{boardId}` |
| Board Actions | `planka://boards/{boardId}/actions` |

### Lists

| Resource | URI |
|---|---|
| List | `planka://lists/{listId}` |
| List Cards | `planka://lists/{listId}/cards` |

### Cards

| Resource | URI |
|---|---|
| Card | `planka://cards/{cardId}` |
| Card Comments | `planka://cards/{cardId}/comments` |
| Card Actions | `planka://cards/{cardId}/actions` |

### Tasks

| Resource | URI |
|---|---|
| Task List | `planka://task-lists/{taskListId}` |

### Custom Fields

| Resource | URI |
|---|---|
| Custom Field Group | `planka://custom-field-groups/{groupId}` |

### Users

| Resource | URI |
|---|---|
| Users | `planka://users` |
| User | `planka://users/{userId}` |

### Notifications

| Resource | URI |
|---|---|
| Notifications | `planka://notifications` |
| Notification | `planka://notifications/{notificationId}` |

### Webhooks

| Resource | URI |
|---|---|
| Webhooks | `planka://webhooks` |

## Tools

### Structure

| Tool | Description |
|---|---|
| `planka_get_structure` | Get all projects with their boards and lists |
| `planka_get_board` | Get a board with all its lists, cards, and labels |

### Projects

| Tool | Description |
|---|---|
| `planka_manage_projects` | Create, get, update, or delete a project |
| `planka_manage_project_managers` | Add or remove a project manager |

### Boards

| Tool | Description |
|---|---|
| `planka_manage_boards` | Create, get, update, or delete a board within a project |
| `planka_manage_board_memberships` | Add, update, or remove board memberships |

### Lists

| Tool | Description |
|---|---|
| `planka_manage_lists` | Create, get, update, delete, or manage cards in a list on a board |
| `planka_sort_list` | Sort cards within a list by a specified field |

### Cards

| Tool | Description |
|---|---|
| `planka_create_card` | Create a new card, optionally with tasks |
| `planka_get_card` | Get full card details including tasks, comments, labels, and attachments |
| `planka_update_card` | Update a card's name, description, due date, or completion status |
| `planka_move_card` | Move a card to a different list or position |
| `planka_delete_card` | Permanently delete a card |
| `planka_duplicate_card` | Duplicate an existing card into the same list |
| `planka_manage_card_membership` | Add or remove a user as a member of a card |

### Labels

| Tool | Description |
|---|---|
| `planka_manage_labels` | Create, update, or delete labels on a board |
| `planka_set_card_labels` | Add or remove labels from a card |

### Tasks

| Tool | Description |
|---|---|
| `planka_create_tasks` | Add one or more checklist items to a card |
| `planka_update_task` | Update a task's name or completion status |
| `planka_delete_task` | Delete a task from a card |
| `planka_update_task_list` | Update a task list's name |
| `planka_delete_task_list` | Delete a task list and all its tasks from a card |

### Comments

| Tool | Description |
|---|---|
| `planka_add_comment` | Add a comment to a card (markdown supported) |
| `planka_get_comments` | Get all comments on a card |
| `planka_update_comment` | Update the text of an existing comment |
| `planka_delete_comment` | Delete a comment from a card |

### Attachments

| Tool | Description |
|---|---|
| `planka_upload_attachment` | Upload a file attachment to a card |
| `planka_manage_attachments` | Update or delete an attachment on a card |

### Users

| Tool | Description |
|---|---|
| `planka_manage_users` | List, get, create, update, or delete users |
| `planka_manage_user_credentials` | Update a user's email, password, or username |

### Notifications

| Tool | Description |
|---|---|
| `planka_get_notifications` | List all notifications for the authenticated user |
| `planka_mark_notification_read` | Mark a notification as read or unread |
| `planka_mark_all_notifications_read` | Mark all notifications as read |

### Custom Fields

| Tool | Description |
|---|---|
| `planka_manage_custom_field_groups` | Create, get, update, or delete custom field groups on projects, boards, or cards |
| `planka_manage_custom_fields` | Create, update, or delete custom fields within a custom field group |
| `planka_manage_custom_field_values` | Set or delete a custom field value on a card |

### Webhooks

| Tool | Description |
|---|---|
| `planka_manage_webhooks` | List, create, update, or delete webhooks |

### Activity

| Tool | Description |
|---|---|
| `planka_get_actions` | Get the activity log (actions) for a board or card |

### Notification Services

| Tool | Description |
|---|---|
| `planka_manage_notification_services` | Create, update, test, or delete notification services (integrations) for users or boards |

## Requirements

- Docker and Docker Compose
- A running Planka 2 instance
- A Planka API key (generated in Planka under **Settings → API Keys**)

## Production Deployment

### Using Docker Compose (recommended)

1. Copy the example environment file and fill in your values:

```bash
cp .env.production.example .env.production
# Edit .env.production — set APP_KEY and PLANKA_URL
```

2. Generate an application key:

```bash
docker run --rm ghcr.io/mintopia/planka-mcp:latest php artisan key:generate --show
# Paste the output into .env.production as APP_KEY
```

3. Start the server using the production Compose file:

```bash
docker compose -f docker-compose.prod.yml up -d
```

The server listens on `http://localhost:8080`. The MCP endpoint is at `/mcp`.

To pull the latest image:

```bash
docker compose -f docker-compose.prod.yml pull
docker compose -f docker-compose.prod.yml up -d
```

### Using `docker run`

```bash
docker run -d \
  --name planka-mcp \
  --restart unless-stopped \
  -p 8080:80 \
  -e APP_KEY=base64:$(openssl rand -base64 32) \
  -e PLANKA_URL=https://your-planka-instance.example.com \
  ghcr.io/mintopia/planka-mcp:latest
```

### Environment variables

| Variable | Required | Description |
|---|---|---|
| `PLANKA_URL` | Yes | Base URL of your Planka instance (e.g. `https://planka.example.com`) |
| `APP_KEY` | Yes | Laravel application key — generate with `php artisan key:generate --show` |
| `APP_URL` | No | Public URL of this server (used in generated config examples) |
| `APP_ENV` | No | `production` (default) or `local` |
| `REDIS_HOST` | No | Redis hostname — defaults to `redis` in Docker Compose, `127.0.0.1` otherwise |
| `REDIS_PORT` | No | Redis port — defaults to `6379` |
| `REDIS_PASSWORD` | No | Redis password — defaults to none |

The Planka API key is **never** configured server-side. It is passed by each client on every request (see [Authentication](#authentication) below).

#### Webhook subscriptions (optional)

Webhook subscription support allows Planka to push event notifications to the server, ready for forwarding to MCP clients when `laravel/mcp` adds `resources/subscribe` support.

| Variable | Required | Description |
|---|---|---|
| `SUBSCRIPTION_ENABLED` | No | Set to `true` to enable the webhook endpoint — defaults to `false` |
| `PLANKA_WEBHOOK_SECRET` | Recommended | HMAC-SHA256 secret for verifying incoming webhook signatures. Should always be set when `SUBSCRIPTION_ENABLED=true` |

## Authentication

Every request to the MCP server must include a Planka API key. Two header formats are accepted:

```
Authorization: Bearer <planka-api-key>
```

```
X-Api-Key: <planka-api-key>
```

If both headers are present, `Authorization: Bearer` takes precedence.

To obtain an API key, log in to Planka and go to **Settings → API Keys → Create API Key**.

## Configuring Claude Code

Add the server to your Claude Code MCP configuration. The config file is `~/.claude/mcp.json` (or use `claude mcp add`).

### mcp.json

```json
{
  "mcpServers": {
    "planka": {
      "type": "http",
      "url": "http://localhost:8080/mcp",
      "headers": {
        "Authorization": "Bearer <your-planka-api-key>"
      }
    }
  }
}
```

### CLI

```bash
claude mcp add --transport http planka http://localhost:8080/mcp \
  --header "Authorization: Bearer <your-planka-api-key>"
```

Replace `localhost:8080` with the actual host and port if the server is deployed remotely.

## Configuring Claude Desktop

In `~/Library/Application Support/Claude/claude_desktop_config.json` (macOS) or `%APPDATA%\Claude\claude_desktop_config.json` (Windows):

```json
{
  "mcpServers": {
    "planka": {
      "type": "http",
      "url": "http://localhost:8080/mcp",
      "headers": {
        "Authorization": "Bearer <your-planka-api-key>"
      }
    }
  }
}
```

## Health check

```bash
curl http://localhost:8080/health
# → OK
```

## Development

### Requirements

- PHP 8.3+
- Composer

### Local setup (without Docker)

```bash
composer install
npm install
npm run build
php artisan key:generate
```

Edit `.env` and set `PLANKA_URL` to your Planka instance.

### Running tests

**Natively:**

```bash
# Build assets (required before tests)
npm run build

# Run the test suite
composer test

# Run with HTML coverage report + clover.xml (outputs to storage/coverage/)
composer test-coverage
```

**Via Docker** (no local PHP required — XDebug is always present in the dev image):

```bash
# Run the test suite
docker compose run --rm --no-deps planka-mcp composer test

# Run with coverage
docker compose run --rm --no-deps planka-mcp composer test-coverage
```

The HTML report is written to `storage/coverage/index.html`. Open it after the run:

```bash
open storage/coverage/index.html       # macOS
xdg-open storage/coverage/index.html  # Linux
```

### Static analysis

```bash
# Natively
composer analyse

# Via Docker
docker compose run --rm --no-deps planka-mcp composer analyse
```

### Docker development setup

`docker-compose.yml` is configured for local development:

- Builds from `Dockerfile.dev` — includes XDebug, single-request worker mode
- Bind-mounts the project directory into the container at `/app` (no named volumes — Composer dependencies are installed by the entrypoint script on first start)
- Exposes `host.docker.internal` so XDebug can connect back to your IDE

Start the development server:

```bash
PLANKA_URL=https://your-planka-instance.example.com docker compose up
```

Or export the variable:

```bash
export PLANKA_URL=https://your-planka-instance.example.com
docker compose up
```

The dev image is configured for automatic hot-reload — **no container restart is needed when you change source code**:

- **`watch`** in the dev Caddyfile tells FrankenPHP to watch `app/**/*.php` and `config/**/*.php` for changes and automatically restart the worker when any of those files are modified.
- **opcache is not installed** in the dev image, so PHP reads source files from disk on every worker boot rather than serving stale cached bytecode.
- **`APP_ENV=local`** tells the Laravel framework to check for configuration and service changes on each boot.

Together these mean any change to a PHP file or config takes effect within moments of saving — the worker restarts in the background and the next request is handled with fresh code.

**Frontend assets** (CSS/JS) are compiled by Vite. In the development container, assets are automatically built on first start if `public/build` doesn't exist. To rebuild after changes:

```bash
docker compose run --rm --no-deps planka-mcp npm run build
```

#### XDebug

XDebug is installed and enabled by default in the development image. It connects to your host machine on every request — no trigger cookie or query string needed.

| Setting | Value |
|---|---|
| Client host | `host.docker.internal` (resolves to your Docker host) |
| Client port | `9003` |
| Server / IDE key | `planka_mcp` |
| Start mode | `start_with_request=yes` — fires on every request automatically |

XDebug is controlled via the `XDEBUG_MODE` environment variable. The default in `docker-compose.yml` is `debug`. To disable it:

```bash
XDEBUG_MODE=off PLANKA_URL=https://your-planka-instance.example.com docker compose up
```

**PhpStorm**

1. Open **Settings → PHP → Servers** and add a server:
   - Name: `planka_mcp`
   - Host: `localhost`
   - Port: `8080`
   - Debugger: XDebug
   - Enable path mappings: map your local project root to `/app`
2. Open **Settings → PHP → Debug** and confirm the debug port is `9003`.
3. Click the **Start Listening for PHP Debug Connections** button (phone icon in toolbar).
4. Set a breakpoint and make a request — PhpStorm will pause at the breakpoint automatically.

**VS Code**

Install the [PHP Debug](https://marketplace.visualstudio.com/items?itemName=xdebug.php-debug) extension, then add this configuration to `.vscode/launch.json`:

```json
{
  "version": "0.2.0",
  "configurations": [
    {
      "name": "Listen for XDebug (planka-mcp)",
      "type": "php",
      "request": "launch",
      "port": 9003,
      "pathMappings": {
        "/app": "${workspaceFolder}"
      }
    }
  ]
}
```

Start the **Listen for XDebug** debug configuration and make a request to trigger the debugger.

#### Rebuilding after `composer.json` changes

```bash
docker compose up --build
```

The entrypoint script runs `composer install` automatically if `vendor/autoload.php` is missing.

### Building the production Docker image locally

The production Docker build includes a Node.js stage that automatically compiles frontend assets via Vite — no local Node.js installation is required.

```bash
docker build -t planka-mcp:local .
```
