# planka-mcp

A [Model Context Protocol (MCP)](https://modelcontextprotocol.io) server that exposes [Planka 2](https://planka.app) kanban board operations as AI tools.

Built with Symfony 7 and FrankenPHP, running in worker mode inside Docker. Multi-tenant: each MCP client supplies its own Planka API key per request — no credentials are stored server-side.

> **Symfony 8 note:** Upgrading to Symfony 8 is currently blocked by `runtime/frankenphp-symfony`, which only supports `symfony/runtime ^7.0`. This will be addressed once that package adds Symfony 8 support.

## Tools

| Tool | Description |
|---|---|
| `planka_get_structure` | List all projects with their boards and lists |
| `planka_get_board` | Get a board with all lists, cards, and labels |
| `planka_create_card` | Create a card, optionally attaching labels |
| `planka_get_card` | Get full card details including tasks, comments, and labels |
| `planka_update_card` | Update a card's name, description, due date, or completion status |
| `planka_move_card` | Move a card to a different list or position |
| `planka_delete_card` | Permanently delete a card |
| `planka_create_tasks` | Add checklist items to a card |
| `planka_update_task` | Rename or complete/uncomplete a task |
| `planka_delete_task` | Remove a task from a card |
| `planka_manage_labels` | Create, update, or delete labels on a board |
| `planka_set_card_labels` | Add or remove labels from a card |
| `planka_manage_lists` | Create, update, or delete lists on a board |
| `planka_add_comment` | Add a comment to a card |
| `planka_get_comments` | Get all comments on a card |

## Requirements

- Docker and Docker Compose
- A running Planka 2 instance
- A Planka API key (generated in Planka under **Settings → API Keys**)

## Production Deployment

### Using Docker Compose (recommended)

1. Copy the example environment file and fill in your values:

```bash
cp .env.production.example .env.production
# Edit .env.production — set APP_SECRET and PLANKA_URL
```

2. Generate a secret:

```bash
openssl rand -hex 32
# Paste the output into .env.production as APP_SECRET
```

3. Start the server using the production Compose file:

```bash
docker compose -f docker-compose.prod.yml up -d
```

The server listens on `http://localhost:8080`. The MCP endpoint is at `/_mcp`.

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
  -e APP_SECRET=$(openssl rand -hex 32) \
  -e PLANKA_URL=https://your-planka-instance.example.com \
  ghcr.io/mintopia/planka-mcp:latest
```

### Environment variables

| Variable | Required | Description |
|---|---|---|
| `PLANKA_URL` | Yes | Base URL of your Planka instance (e.g. `https://planka.example.com`) |
| `APP_SECRET` | Yes | Random string used for Symfony internals — generate with `openssl rand -hex 32` |
| `APP_ENV` | No | `prod` (default) or `dev` |

The Planka API key is **never** configured server-side. It is passed by each client on every request (see [Authentication](#authentication) below).

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
      "url": "http://localhost:8080/_mcp",
      "headers": {
        "Authorization": "Bearer <your-planka-api-key>"
      }
    }
  }
}
```

### CLI

```bash
claude mcp add --transport http planka http://localhost:8080/_mcp \
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
      "url": "http://localhost:8080/_mcp",
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
```

Edit `.env` and set `PLANKA_URL` to your Planka instance.

### Running tests

**Natively:**

```bash
# Run the test suite
composer test

# Run with HTML coverage report + clover.xml (outputs to var/coverage/)
composer test-coverage
```

> Requires XDebug installed locally for coverage. `composer test` works with any PHP 8.3+ installation.

**Via Docker** (no local PHP required — XDebug is always present in the dev image):

```bash
# Run the test suite
docker compose run --rm --no-deps planka-mcp composer test

# Run with coverage
docker compose run --rm --no-deps planka-mcp composer test-coverage
```

The HTML report is written to `var/coverage/index.html`. Open it after the run:

```bash
open var/coverage/index.html       # macOS
xdg-open var/coverage/index.html  # Linux
```

A machine-readable `var/coverage/clover.xml` is also generated alongside the HTML report.

The project enforces **100% code coverage**. The CI pipeline will fail if coverage drops below 100%.

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

- **`watch`** in the dev Caddyfile tells FrankenPHP to watch `src/**/*.php` and `config/**/*.{yaml,yml}` for changes and automatically restart the worker when any of those files are modified.
- **opcache is not installed** in the dev image, so PHP reads source files from disk on every worker boot rather than serving stale cached bytecode.
- **`APP_ENV=dev`** tells the Symfony kernel to check for configuration, route, and service changes on each boot and regenerate its cache automatically.

Together these mean any change to a PHP file, route, or service definition takes effect within moments of saving — the worker restarts in the background and the next request is handled with fresh code.

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

```bash
docker build --target app -t planka-mcp:local .
```
