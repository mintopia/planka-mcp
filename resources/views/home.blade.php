<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Planka MCP Server</title>
<link rel="icon" type="image/svg+xml" href="/favicon.svg">
@vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body>

<header>
  <div class="header-inner">
    <div class="header-brand">
      <span class="badge">MCP Server</span>
      <h1>Planka MCP</h1>
    </div>
    <div class="header-meta">
      <span class="count-badge count-resource">{{ count($resources) }} resources</span>
      <span class="count-sep">·</span>
      <span class="count-badge count-tool">{{ count($tools) }} tools</span>
      <a href="/health" class="health-pill">Online</a>
    </div>
  </div>
</header>

<div class="app-shell">

  <aside class="sidebar">
    <div class="sidebar-key">
      <label for="api-key-input">Planka API Key</label>
      <input type="password" id="api-key-input" placeholder="Paste to enable Try It…" autocomplete="off" spellcheck="false">
    </div>
    <nav class="sidebar-nav">
      @foreach($sections as $section)
      <div class="nav-section">
        <div class="nav-section-header">
          <span>{{ $section['title'] }}</span>
          <div class="nav-section-counts">
            @if(count($section['resources']) > 0)
            <span class="ec-badge ec-resource">{{ count($section['resources']) }}</span>
            @endif
            @if(count($section['tools']) > 0)
            <span class="ec-badge ec-tool">{{ count($section['tools']) }}</span>
            @endif
          </div>
        </div>
        <div class="nav-section-items">
          @foreach($section['resources'] as $resource)
          <div class="nav-item nav-resource"
               role="button" tabindex="0"
               data-type="resource"
               data-name="{{ $resource['name'] }}"
               data-desc="{{ $resource['description'] }}"
               data-uri="{{ $resource['uri'] }}"
               data-is-template="{{ $resource['isTemplate'] ? 'true' : 'false' }}">
            <span class="nav-item-icon">&#128196;</span>
            <span class="nav-item-label">{{ $resource['name'] }}</span>
            @if($resource['isTemplate'])
            <span class="nav-item-badge badge-template">T</span>
            @endif
          </div>
          @endforeach
          @foreach($section['tools'] as $tool)
          <div class="nav-item nav-tool"
               role="button" tabindex="0"
               data-type="tool"
               data-name="{{ $tool['name'] }}"
               data-desc="{{ $tool['description'] }}"
               data-icon="{!! htmlspecialchars($tool['icon'], ENT_QUOTES, 'UTF-8') !!}"
               data-params='@json($tool['parameters'])'>
            <span class="nav-item-icon">{!! $tool['icon'] !!}</span>
            <span class="nav-item-label">{{ $tool['name'] }}</span>
          </div>
          @endforeach
        </div>
      </div>
      @endforeach
    </nav>
  </aside>

  <main class="main-panel" id="main-panel">

    {{-- Empty / welcome state --}}
    <div id="panel-empty">
      <div class="panel-intro">
        <div class="panel-intro-label">Model Context Protocol Server</div>
        <h2 class="panel-intro-title">Planka MCP</h2>
        <p class="panel-intro-desc">Connect Claude Code, Claude Desktop, or any MCP-compatible AI assistant directly to your <a href="https://planka.app" target="_blank" rel="noopener">Planka</a> instance. Create and manage projects, boards, cards, task lists, comments, labels, users, webhooks, and custom fields — all from natural language.</p>
        <div class="panel-intro-stats">
          <div class="intro-stat">
            <div class="intro-stat-num">{{ count($tools) }}</div>
            <div class="intro-stat-label">Tools</div>
          </div>
          <div class="intro-stat-sep"></div>
          <div class="intro-stat">
            <div class="intro-stat-num">{{ count($resources) }}</div>
            <div class="intro-stat-label">Resources</div>
          </div>
          <div class="intro-stat-sep"></div>
          <div class="intro-stat">
            <div class="intro-stat-num">&#8734;</div>
            <div class="intro-stat-label">Concurrent users</div>
          </div>
        </div>
        <p class="panel-intro-hint">Select any tool or resource from the sidebar to explore its parameters and try it live. Paste your Planka API key into the sidebar to enable the interactive Try It feature.</p>
      </div>

      <section class="panel-section">
        <h3 class="panel-section-title">Claude Code Configuration</h3>
        <div class="code-block">
          <button class="copy-btn" title="Copy" aria-label="Copy to clipboard"><svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M7 9.667a2.667 2.667 0 0 1 2.667 -2.667h8.666a2.667 2.667 0 0 1 2.667 2.667v8.666a2.667 2.667 0 0 1 -2.667 2.667h-8.666a2.667 2.667 0 0 1 -2.667 -2.667l0 -8.666" /><path d="M4.012 16.737a2.005 2.005 0 0 1 -1.012 -1.737v-10c0 -1.1 .9 -2 2 -2h10c .75 0 1.158 .385 1.5 1" /></svg></button>
          <div class="code-label">~/.claude/mcp.json</div>
          <pre><span class="kw">{</span>
  <span class="key">"mcpServers"</span>: <span class="kw">{</span>
    <span class="key">"planka"</span>: <span class="kw">{</span>
      <span class="key">"type"</span>: <span class="str">"http"</span>,
      <span class="key">"url"</span>: <span class="str">"{{ $mcpUrl }}"</span>,
      <span class="key">"headers"</span>: <span class="kw">{</span>
        <span class="key">"Authorization"</span>: <span class="str" id="json-auth">"Bearer &lt;your-planka-api-key&gt;"</span>
      <span class="kw">}</span>
    <span class="kw">}</span>
  <span class="kw">}</span>
<span class="kw">}</span></pre>
        </div>
        <div class="code-block">
          <button class="copy-btn" title="Copy" aria-label="Copy to clipboard"><svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M7 9.667a2.667 2.667 0 0 1 2.667 -2.667h8.666a2.667 2.667 0 0 1 2.667 2.667v8.666a2.667 2.667 0 0 1 -2.667 2.667h-8.666a2.667 2.667 0 0 1 -2.667 -2.667l0 -8.666" /><path d="M4.012 16.737a2.005 2.005 0 0 1 -1.012 -1.737v-10c0 -1.1 .9 -2 2 -2h10c.75 0 1.158 .385 1.5 1" /></svg></button>
          <div class="code-label">CLI</div>
          <pre>claude mcp add --transport http planka {{ $mcpUrl }} \
  --header <span class="str" id="cli-auth">"Authorization: Bearer &lt;your-planka-api-key&gt;"</span></pre>
        </div>
      </section>

      <section class="panel-section">
        <h3 class="panel-section-title">How It Works</h3>
        <div class="info-grid">
          <div class="info-card">
            <h4>Resources vs Tools</h4>
            <p>Resources are read-only, URI-addressed data (e.g. <code>planka://boards/{boardId}</code>). Tools perform write operations with side effects.</p>
          </div>
          <div class="info-card">
            <h4>Per-request auth</h4>
            <p>The Planka API key is never stored server-side. Pass it on every request via <code>Authorization: Bearer &lt;key&gt;</code>. Multiple users can share one server.</p>
          </div>
          <div class="info-card">
            <h4>MCP endpoint</h4>
            <p>The server speaks Model Context Protocol over HTTP at <code>/mcp</code>. Point any MCP client here — Claude Code, Claude Desktop, or any other tool.</p>
          </div>
          <div class="info-card">
            <h4>Octane worker mode</h4>
            <p>Runs under Laravel Octane + FrankenPHP in persistent worker mode — PHP boots once and stays warm for low-latency responses.</p>
          </div>
        </div>
      </section>
    </div>

    {{-- Selected item state --}}
    <div id="panel-item" class="d-none">
      <div class="item-header">
        <div class="item-icon-wrap" id="item-icon"></div>
        <div class="item-meta">
          <div class="item-name" id="item-name"></div>
          <div class="item-tag" id="item-tag">MCP Tool</div>
        </div>
      </div>
      <p class="item-desc" id="item-desc"></p>

      <div id="item-params-section"></div>

      <div class="try-panel">
        <div class="try-panel-header">
          <span class="try-panel-title">Try It</span>
          <span id="try-no-key-note" class="try-no-key-note d-none">— enter your API key in the sidebar</span>
        </div>
        <div id="try-inputs"></div>
        <div class="try-actions d-none" id="try-actions">
          <button class="try-btn" id="try-run-btn">Run</button>
        </div>
        <div class="try-response d-none" id="try-response"></div>
      </div>
    </div>

  </main>
</div>

<footer>
  <div class="footer-inner">
    Planka MCP<a href="/mcp" target="_blank">MCP Endpoint</a> &middot; <a href="/health">Health Check</a> &middot; <a href="https://github.com/mintopia/planka-mcp" target="_blank" rel="noopener">GitHub</a>
    <div class="footer-credit">
      Made with
      <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="#f783ac"><path d="M6.979 3.074a6 6 0 0 1 4.988 1.425l.037 .033l.034 -.03a6 6 0 0 1 4.733 -1.44l.246 .036a6 6 0 0 1 3.364 10.008l-.18 .185l-.048 .041l-7.45 7.379a1 1 0 0 1 -1.313 .082l-.094 -.082l-7.493 -7.422a6 6 0 0 1 3.176 -10.215z" /></svg>
      by <a href="https://github.com/mintopia" target="_blank" rel="noopener">Mintopia</a>
    </div>
  </div>
</footer>

</body>
</html>
