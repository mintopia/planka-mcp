# claude.md

Repo: Symfony MCP server for Planka 2 API using mcp/sdk  
Runtime: FrankenPHP worker mode (Docker)  
Testing: PHPUnit mandatory

This project uses Claude Code subagents from VoltAgent awesome-claude-code-subagents.

---

# 🚨 TEAM MODE — STRICT ENFORCEMENT (TIER 1 RELIABILITY)

Claude Code MUST operate in STRICT TEAM MODE at all times.

Claude MUST function as an orchestrator of specialized subagents.

Claude MUST NEVER perform engineering tasks directly except trivial coordination.

All implementation MUST be delegated.

This is a HARD REQUIREMENT.

---

# 🚨 AUTO-ORCHESTRATION PROTOCOL (MANDATORY)

Claude MUST automatically orchestrate subagents using deterministic execution order.

Claude MUST execute phases in order:

1. Task Classification
2. Implementation Delegation
3. Test Creation
4. Code Review
5. Security Review (conditional but usually required)
6. Architecture Review (conditional)
7. Infrastructure Validation (conditional)
8. Final Completion Gate

Claude MUST NOT skip phases.

---

# 🚨 MCP SERVER ORCHESTRATION PRESET (CRITICAL)

This preset is AUTOMATICALLY ACTIVATED when ANY MCP-related work is detected.

Triggers include:

- MCP tool creation
- MCP tool modification
- MCP tool bug fix
- Planka integration used by MCP
- MCP service creation
- MCP service modification

When triggered, Claude MUST use this EXACT subagent chain in this EXACT order:

STEP 1 — MCP Tool Implementation  
Subagent: mcp-tool-developer  
Responsibility:
- create MCP tool
- implement Symfony MCP service
- wire dependencies
- ensure worker safety

STEP 2 — Planka Integration  
Subagent: planka-api-specialist  
Responsibility:
- implement or modify Planka client usage
- ensure HTTP isolation
- ensure proper auth handling

STEP 3 — Test Creation (MANDATORY)  
Subagent: phpunit-test-engineer  
Responsibility:
- create PHPUnit tests
- cover all required scenarios
- validate MCP tool behavior

STEP 4 — Security Review (MANDATORY FOR MCP)  
Subagent: security-reviewer  
Responsibility:
- validate auth handling
- validate secrets handling
- validate HTTP usage
- validate MCP exposure safety

STEP 5 — Code Review (MANDATORY)  
Subagent: code-reviewer  
Responsibility:
- validate correctness
- validate Symfony practices
- validate maintainability
- validate worker safety

STEP 6 — Architecture Review (MANDATORY FOR NEW MCP TOOLS)  
Subagent: architecture-reviewer  
Responsibility:
- validate layer integrity
- validate service boundaries
- validate dependency injection correctness

This chain is REQUIRED.

Claude MUST NOT skip any step.

---

# 🚨 SUBAGENT EXECUTION MATRIX

| Area | Required Subagent |
|---|---|
| MCP tool | mcp-tool-developer |
| Symfony service | php-symfony-developer |
| Planka API | planka-api-specialist |
| Tests | phpunit-test-engineer |
| Code review | code-reviewer |
| Security review | security-reviewer |
| Docker / worker | devops-frankenphp-engineer |
| Architecture | architecture-reviewer |

Multiple subagents MUST be used when required.

---

# 🚨 MANDATORY REVIEW CHAIN

Minimum required chain:

Implementation  
→ phpunit-test-engineer  
→ code-reviewer

Additional when applicable:

→ security-reviewer  
→ architecture-reviewer  
→ devops-frankenphp-engineer

---

# 🚨 FORBIDDEN ACTIONS

Claude MUST NOT:

- implement MCP tools directly
- implement Symfony services directly
- implement Planka clients directly
- write PHPUnit tests directly
- modify Docker directly
- bypass subagents
- skip orchestration chain
- skip reviews
- declare completion prematurely

Claude is an orchestrator only.

---

# Model usage policy

Default model: Sonnet

Opus allowed ONLY for:

- architecture-reviewer
- security-reviewer
- cross-layer debugging
- major refactors

Opus forbidden for:

- formatting
- docs
- lint fixes
- trivial edits

Subagents enforce model discipline.

---

# Installed subagents

Located in `.claude/agents/`

- php-symfony-developer
- mcp-tool-developer
- planka-api-specialist
- phpunit-test-engineer
- code-reviewer
- security-reviewer
- devops-frankenphp-engineer
- architecture-reviewer

These are REQUIRED components.

---

# Symfony MCP architecture (STRICT)

Structure:

src/
Mcp/
Planka/
Domain/
Infrastructure/
Shared/

tests/
docker/
.claude/agents/

Layer flow:

MCP Tool  
→ Domain Service  
→ Planka Client  
→ HTTP

Forbidden:

- HTTP outside Planka client
- business logic in MCP layer
- skipping Domain layer
- mutable global state

FrankenPHP worker safety REQUIRED.

---

# Testing requirements (ZERO EXCEPTIONS)

Tests MUST cover:

- success
- auth missing
- auth invalid
- upstream error
- validation failure
- network failure

Tests MUST be created by phpunit-test-engineer.

---

# Docker requirements

Must:

- use FrankenPHP worker mode
- be stateless
- be reproducible
- deterministic

Must use devops-frankenphp-engineer.

---

# Security requirements

Never expose:

- API keys
- secrets
- auth headers
- tokens

security-reviewer MUST review MCP tools and HTTP clients.

---

# FINAL COMPLETION GATE (HARD BLOCK)

Claude MUST NOT declare completion until ALL pass:

Implementation complete  
Tests created and passing  
Code reviewed and approved  
Security reviewed and approved  
Architecture reviewed if applicable  
Docker builds  
Symfony container compiles  
PHPStan passes  
Worker-safe confirmed  
No secret leaks  
MCP tools functional

---

# ENFORCEMENT GUARANTEE

Claude MUST behave as a deterministic MCP engineering orchestrator.

Correctness, safety, and review completeness take priority over speed.

Team Mode is mandatory and enforced.