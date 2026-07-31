# Consolidated Autonomous Agent Directive Checklist
**Purpose**: Master index of all 99+ directives from Rounds 1 & 2  
**Status**: Production-ready reference for compliance validation  
**Last Updated**: 2026-07-31

---

## Quick Navigation

| Category | Directives | Source | Priority |
|----------|-----------|--------|----------|
| Idempotency | 8 | Round 2, Param 1 | CRITICAL |
| State Persistence | 8 | Round 2, Param 2 | CRITICAL |
| Context Pruning | 8 | Round 2, Param 3 | CRITICAL |
| Output Determinism | 8 | Round 2, Param 4 | CRITICAL |
| Observability | 7 | Round 2, Param 5 | HIGH |
| Self-Healing | 8 | Round 2, Param 6 | HIGH |
| Sandboxing | 7 | Round 2, Param 7 | CRITICAL |
| Token Economics | 8 | Round 2, Param 8 | HIGH |
| Tool Chaining | 7 | Round 2, Param 9 | MEDIUM |
| Async Handling | 7 | Round 2, Param 10 | HIGH |
| Secrets & Security | 7 | Round 2, Param 11 | CRITICAL |
| Halting & Boundaries | 8 | Round 2, Param 12 | CRITICAL |
| Meta-Cognition | 8 | Round 2, Param 13 | HIGH |
| Context Density | 14 | Round 1, Dim 1 | HIGH |
| Determinism | 14 | Round 1, Dim 2 | CRITICAL |
| Self-Healing | 14 | Round 1, Dim 3 | HIGH |
| State Management | 14 | Round 1, Dim 4 | CRITICAL |
| Token Efficiency | 14 | Round 1, Dim 5 | HIGH |
| Tool Chaining | 14 | Round 1, Dim 6 | MEDIUM |
| Security | 14 | Round 1, Dim 7 | CRITICAL |
| Rule 99 (Meta-Control) | 1 | Both | CRITICAL |
| **TOTAL** | **198** | - | - |

---

## CRITICAL (Must Implement Before Production)

### Idempotency (Round 2, Param 1)
- [ ] 1.1 Every script begins with state check (if exists)
- [ ] 1.2 Backup before destruction with timestamp
- [ ] 1.3 Deterministic file naming (UUID or hash)
- [ ] 1.4 Paired migrations (UP.sql + DOWN.sql)
- [ ] 1.5 Surgical config updates (jq/yq, not full rewrites)
- [ ] 1.6 Dry-run mode on every executable
- [ ] 1.7 Response caching for network operations
- [ ] 1.8 Granular dependency updates (per package)

### Output Determinism (Round 2, Param 4)
- [ ] 4.1 Temperature: 0.0 on all autonomous coding API calls
- [ ] 4.2 JSON-only responses with schema validation
- [ ] 4.3 Strict JSON Schema or Pydantic model validation
- [ ] 4.4 Locked dependency versions (no auto-upgrade)
- [ ] 4.5 Auto-format all generated code (Prettier/Black)
- [ ] 4.6 Strict variable naming mapping (no camelCase/snake_case mixing)
- [ ] 4.7 No stubbed code (complete implementation or error)
- [ ] 4.8 Non-interactive execution (all -y flags, NONINTERACTIVE=1)

### Sandboxing (Round 2, Param 7)
- [ ] 7.1 All code tested in isolated Docker (no network)
- [ ] 7.2 Privilege escalation blocked (no sudo without validation)
- [ ] 7.3 Hard resource quotas via cgroups (--memory, --cpus)
- [ ] 7.4 IP rotation for scrapers/API tests
- [ ] 7.5 Volume-mounted sandboxing (/workspace only)
- [ ] 7.6 eval()/exec() with dynamic strings rejected
- [ ] 7.7 Alpine Linux minimal base images

### Secrets & Security (Round 2, Param 11)
- [ ] 11.1 No direct secret exposure (aliases only, SECRET_KEY env var)
- [ ] 11.2 Pre-commit secret blocking (hook + session kill)
- [ ] 11.3 Read-only secret mounts (tmpfs, memory only)
- [ ] 11.4 Database encryption (AES-256 for sensitive columns)
- [ ] 11.5 IAM role least privilege
- [ ] 11.6 Dependency vulnerability scanning (Snyk/npm audit)
- [ ] 11.7 Key rotation & audit trail

### Halting & Boundaries (Round 2, Param 12)
- [ ] 12.1 Max recursion depth (3 import levels)
- [ ] 12.2 Time limit with hard SIGTERM (15 min)
- [ ] 12.3 Cascading change limit (>5 files = escalate)
- [ ] 12.4 Coverage drop detection (even 1% = reject)
- [ ] 12.5 Memory anomaly detection (2x spike = halt)
- [ ] 12.6 Tunnel vision prevention (4+ same edits = halt)
- [ ] 12.7 Daily budget limit (e.g., $5/day tokens)
- [ ] 12.8 Permission denial fallback (403 = escalate)

### State Persistence (Round 2, Param 2)
- [ ] 2.1 Memory file (/.agent/memory.json) with schema
- [ ] 2.2 Resumption protocol (read memory on start)
- [ ] 2.3 Heartbeat mechanism (status.lock every N iterations)
- [ ] 2.4 Git-based state source-of-truth (not internal memory)
- [ ] 2.5 Dual-layer memory (short-term + long-term)
- [ ] 2.6 No self-approved status changes (build return code required)
- [ ] 2.7 Checkpoint system for large datasets (every 1000 rows)
- [ ] 2.8 Explicit state transitions (<state_transition> tags)

### Determinism (Round 1, Dim 2)
- [ ] 2.1 Always specify role first
- [ ] 2.2 Pin exact versions (Python 3.11.2, FastAPI 0.104.1)
- [ ] 2.3 Chain-of-Thought before code (<thought_process> tags)
- [ ] 2.4 Negative prompts (explicit FORBIDDEN patterns)
- [ ] 2.5 Penalty system (eval=FAILED, plaintext_password=abort)
- [ ] 2.6 Pseudocode before complex logic (>50 lines)
- [ ] 2.7 Enforce naming conventions (camelCase, PascalCase, UPPER_SNAKE)
- [ ] 2.8 Big O notation for loops on large data
- [ ] 2.9 Given-When-Then for edge cases
- [ ] 2.10 Ban deprecated APIs (list per language)
- [ ] 2.11 Structured error format ([ERROR] → [CAUSE] → [SOLUTION])
- [ ] 2.12 Stop-words trigger human review (probably, might, hopefully)
- [ ] 2.13 Architectural checkpoint (reference ARCHITECTURE.md)
- [ ] 2.14 100% type coverage (all functions fully typed)

### Security (Round 1, Dim 7)
- [ ] 7.1 Zero Trust architecture (validate everything)
- [ ] 7.2 Forbid eval() & dynamic execution
- [ ] 7.3 CORS & rate limiting on all public APIs
- [ ] 7.4 Parameterized queries (ALWAYS prepared statements)
- [ ] 7.5 Run untrusted code in sandbox
- [ ] 7.6 Non-root user in Docker (USER appuser)
- [ ] 7.7 Security scanning for dependencies
- [ ] 7.8 Mask PII in logs (email→[EMAIL])
- [ ] 7.9 API keys via CI/CD variables (os.getenv only)
- [ ] 7.10 Memory overflow protection (validate buffers)
- [ ] 7.11 Principle of least privilege DB access
- [ ] 7.12 Docker image signing & verification
- [ ] 7.13 Content Security Policy (CSP) for frontend
- [ ] 7.14 Network scraping with rotation

### Rule 99: Meta-Control
- [ ] 99.1 Before each cycle: answer "Necessary or Overengineering?"
- [ ] 99.2 Confidence <90% = STOP, ask single precise question
- [ ] 99.3 Prevents hallucinations and saves 100+ tokens per cycle

---

## HIGH Priority

### Context Pruning (Round 2, Param 3)
- [ ] 3.1 Log compression every 5 iterations (INFO → delete, WARN/ERROR → keep)
- [ ] 3.2 Hard .claudeignore (block node_modules, .git, .venv)
- [ ] 3.3 [FLUSH_CONTEXT] command at logical block end
- [ ] 3.4 Last 24h file filter (git log filter)
- [ ] 3.5 Stack trace truncation (last 50 lines only)
- [ ] 3.6 AST-based code transmission (signatures + imports, no bodies)
- [ ] 3.7 Thought-process pruning (exclude from history)
- [ ] 3.8 Forgetting rule (7-day TTL on old metrics)

### Self-Healing (Round 2, Param 6)
- [ ] 6.1 Circuit breaker for agent (3 failures → DLQ)
- [ ] 6.2 Surgical patches over rewrites (sed/awk for fixes)
- [ ] 6.3 TDD-first (tests before implementation)
- [ ] 6.4 Exponential backoff for 429 rate limits
- [ ] 6.5 Port conflict resolution (lsof + kill)
- [ ] 6.6 Network timeout adaptation (gradual increase)
- [ ] 6.7 Try-catch with fallback actions (never silent error)
- [ ] 6.8 Debug mode container fallback

### Async Handling (Round 2, Param 10)
- [ ] 10.1 Webhook-driven (not timer-based)
- [ ] 10.2 Event queue (RabbitMQ/Pub-Sub)
- [ ] 10.3 Parking mechanism for blocked tasks
- [ ] 10.4 Distributed locks (Redis for multi-instance)
- [ ] 10.5 Async API pools (async/await, no blocking I/O)
- [ ] 10.6 WebSocket for long operations (>30s)
- [ ] 10.7 Serverless worker delegation

### Self-Healing (Round 1, Dim 3)
- [ ] 3.1 Prompt-reflection (self-audit 3 vulnerabilities)
- [ ] 3.2 Failure-first testing (error tests before success)
- [ ] 3.3 Structured error analysis
- [ ] 3.4 Circuit breaker for Claude's work (3 failures)
- [ ] 3.5 Fallbacks for network requests
- [ ] 3.6 No silent failures (log context)
- [ ] 3.7 Merge conflict protocol (show plan, never auto-resolve)
- [ ] 3.8 Analyze stack traces from bottom
- [ ] 3.9 Debug deep mode
- [ ] 3.10 Rollback scripts for migrations
- [ ] 3.11 CI/CD patches first
- [ ] 3.12 Race condition detection
- [ ] 3.13 Memory leak detection
- [ ] 3.14 Prevent circular dependencies

### Token Efficiency (Round 1, Dim 5)
- [ ] 5.1 Shortcodes (Refactor(cpu), Lint, [BUG])
- [ ] 5.2 Aggressive .claudeignore
- [ ] 5.3 Interface-only code review
- [ ] 5.4 Omit unchanged import blocks
- [ ] 5.5 Use English for prompts
- [ ] 5.6 Ternary operators & short syntax
- [ ] 5.7 Collapse repetitive blocks
- [ ] 5.8 Send only head of large arrays
- [ ] 5.9 Postpone README until 90% done
- [ ] 5.10 Limit output tokens for intermediate steps
- [ ] 5.11 Compress logs via regex
- [ ] 5.12 Structured .clauderc
- [ ] 5.13 Multi-stage prompting
- [ ] 5.14 YAML for infrastructure

### Meta-Cognition (Round 2, Param 13)
- [ ] 13.1 End-of-sprint analysis (state_journal.md)
- [ ] 13.2 RFC process for self-improvement
- [ ] 13.3 Build performance tracking
- [ ] 13.4 Error classification & statistics
- [ ] 13.5 Weekly deduplication pass
- [ ] 13.6 Task abstraction challenge
- [ ] 13.7 Self-configuration update
- [ ] 13.8 Overengineering prevention (Rule 99 repeat)

### Context Density (Round 1, Dim 1)
- [ ] 1.1 Never rewrite full files (only diffs)
- [ ] 1.2 Use XML tags for structure
- [ ] 1.3 Separate hot vs cold context
- [ ] 1.4 Forbid apologies/pleasantries
- [ ] 1.5 Session summaries (500 tokens max)
- [ ] 1.6 Compress data structures
- [ ] 1.7 Abbreviate commands ([V], [R], [S], [C], [T])
- [ ] 1.8 Externalize bulky logs
- [ ] 1.9 Tree view for project structure
- [ ] 1.10 JSON-only when context >75%
- [ ] 1.11 Limit code analysis to 3 import levels
- [ ] 1.12 Delete trivial comments
- [ ] 1.13 Auto-clean logs
- [ ] 1.14 Archive old code (>30 days)

### State Management (Round 1, Dim 4)
- [ ] 4.1 Start responses with <current_state> tag
- [ ] 4.2 Git status before complex tasks
- [ ] 4.3 Checkpoint system (git tag checkpoint-a)
- [ ] 4.4 Temp TODO within features
- [ ] 4.5 Forbid global state mutations
- [ ] 4.6 Update state_journal after validation
- [ ] 4.7 [STATE DUMP] command outputs JSON
- [ ] 4.8 Isolate side effects
- [ ] 4.9 Finite State Machines
- [ ] 4.10 Cloud DB schemas (define before impl)
- [ ] 4.11 Idempotent scripts
- [ ] 4.12 Audit trail logging
- [ ] 4.13 Session initialization script
- [ ] 4.14 Dev/Test/Prod separation

---

## MEDIUM Priority

### Token Economics (Round 2, Param 8)
- [ ] 8.1 Model selection by task severity (Haiku/Sonnet/Opus)
- [ ] 8.2 Prompt caching (static content reused)
- [ ] 8.3 GraphQL instead of REST (fewer fields)
- [ ] 8.4 Single-file processing (not bundled)
- [ ] 8.5 Plain text responses (no Markdown intermediate)
- [ ] 8.6 Unified diff format
- [ ] 8.7 Test failure minimization
- [ ] 8.8 Token budget enforcement ($5/day cap)

### Observability (Round 2, Param 5)
- [ ] 5.1 Structured JSON logging with Trace ID
- [ ] 5.2 Self-monitoring metrics (Prometheus/StatsD)
- [ ] 5.3 Crash telemetry (CPU, RAM, Disk)
- [ ] 5.4 Webhook notifications (CI/CD summaries)
- [ ] 5.5 Commit tagging ([AUTOGENERATED_BY_CLAUDE_RUN_ID])
- [ ] 5.6 Health endpoints (/health on all services)
- [ ] 5.7 CPU profiling & optimization loop

### Tool Chaining (Round 2, Param 9)
- [ ] 9.1 GitHub API direct management
- [ ] 9.2 Internal RAG for documentation
- [ ] 9.3 Linter tool chaining
- [ ] 9.4 Database schema checker
- [ ] 9.5 Infrastructure as Code generation
- [ ] 9.6 Job scheduler integration
- [ ] 9.7 Text search tools (sed/grep/find)

### Tool Chaining (Round 1, Dim 6)
- [ ] 6.1 Generate bash scripts for automation
- [ ] 6.2 Curl requests before SDK integration
- [ ] 6.3 Swagger-driven development
- [ ] 6.4 Generate GitHub Actions workflows
- [ ] 6.5 JSON-RPC commands for execution
- [ ] 6.6 Flame graphs for profiling
- [ ] 6.7 Multi-stage Docker builds
- [ ] 6.8 Externalize secrets via Secret Manager
- [ ] 6.9 Auto-generate Postman collections
- [ ] 6.10 Data pipeline (parser → collect → analyze)
- [ ] 6.11 gRPC contracts first (.proto before services)
- [ ] 6.12 Healthcheck endpoints
- [ ] 6.13 Auto-generate API docs (FastAPI OpenAPI)
- [ ] 6.14 Config file per environment

---

## Validation Workflow

**Before Each Autonomous Cycle**:

1. [ ] Load state_journal.md (previous cycle state)
2. [ ] Read .claudeignore (context boundaries)
3. [ ] Run validation_protocol.md (8-phase check)
4. [ ] Activate Rule 99 (necessary or overengineering?)
5. [ ] Begin task execution
6. [ ] Update state_journal.md post-cycle
7. [ ] Commit with [AUTOGENERATED_BY_CLAUDE_RUN_ID] tag

**Before Commit**:

- [ ] Idempotency: Script runs 100 times = same result ✓
- [ ] Determinism: JSON response validates against schema ✓
- [ ] Security: No secrets, parameterized queries, non-root ✓
- [ ] Boundaries: No >15 min processes, coverage not dropped ✓
- [ ] State: memory.json updated, state_journal.md signed ✓

**On Failure**:

- [ ] Circuit breaker: 3 failures → DLQ (not infinite retry)
- [ ] Self-heal: TDD-first, surgical patches, fallback actions
- [ ] Escalate: Output precise question, await clarification

---

## Implementation Phases

### Phase 1: Foundation (Essential)
Round 2 Parameters 1-7 (Idempotency through Sandboxing)  
Round 1 Dimensions 2, 4, 7 (Determinism, State, Security)

### Phase 2: Operations (Robust)
Round 2 Parameters 8-12 (Token Efficiency through Boundaries)  
Round 1 Dimensions 1, 3, 5 (Context, Self-Healing, Tokens)

### Phase 3: Intelligence (Adaptive)
Round 2 Parameters 13 + Rule 99 (Meta-Cognition & Meta-Control)  
Round 1 Dimension 6 (Tool Chaining)

---

## References

- **AUTONOMY_FRAMEWORK.md** - Round 1: 7 dimensions + Rule 99
- **AUTONOMOUS_OPERATION_ROUND_2.md** - Round 2: 13 parameters + 99 directives
- **validation_protocol.md** - 8-phase quality gates
- **state_journal.md** - Session memory and tracking
- **.claudeignore** - Context boundaries
- **CLAUDE.md** - Master directive file

