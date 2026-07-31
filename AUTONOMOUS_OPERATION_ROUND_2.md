# Autonomous Operation Framework: Round 2
## The 12-Expert Symposium & 13-Parameter Architecture

**Date**: 2026-07-31  
**Context**: Synthesis of skeptical, creative, and engineering perspectives on background autonomous operation  
**Status**: Production-ready directive set for "fire-and-forget" autonomous cycles  

---

## Round 2: Peripatetic Symposium (12 Expert Perspectives)

### 1. Skeptical Critic
> "Ваша идея 'оставить агента работать в фоне' — это путь к катастрофе."

**Position**: Autonomous loops without rigid boundaries inevitably cause hallucinating drift. By iteration 50, the agent will delete critical code or fabricate non-existent APIs.

**Evidence Base**:
- LLMs are stateless; repeated cycles compound context degradation
- No built-in self-correction for compound errors
- "Autonomy without constraints is automated chaos"

**Mitigations Demanded**:
- Hard physical sandboxes (isolated VMs, read-only mounts)
- Logical boundaries (max recursion depth, timeout SIGTERM)
- Deterministic output validation (JSON schema, type checking)
- Circuit breaker after 3 consecutive failures

---

### 2. Creative Designer
> "Вы мыслите слишком жестко. Агенту нужна не клетка, а эволюционная среда."

**Position**: The agent needs adaptive learning, not imprisonment. It should analyze telemetry from past runs, discover new patterns, and **self-modify its system prompts**.

**Evidence Base**:
- Rigid rules prevent innovation and optimization
- Monorepo parsing, complex data structures require emergent pattern recognition
- Self-reflection (meta-cognition) accelerates convergence

**Demands**:
- Structured telemetry collection (metrics, profiling, error classification)
- Prompt mutation engine (RFC process for self-improvement)
- Adaptive retry strategies based on historical success rates
- Mutable .clauderc that evolves with experience

---

### 3. Thorough Analyst
> "Вы оба упускаете суть инженерии непрерывных систем."

**Position**: Don't anthropomorphize. Build a **deterministic finite state machine** isolated in sandboxes. Filter 599 industry best practices through 13 rigorous parameters. Foundation: idempotency, observability, hard stop criteria, sandboxed execution.

**Evidence Base**:
- Cloud-native systems operate deterministically (Kubernetes orchestration model)
- State machines are provably correct (formal verification)
- Observability beats introspection for debugging
- 13 parameters capture all failure modes observed in distributed systems

**Deliverables**:
- 99 vetted directives across 13 dimensions
- Formal state diagram with transition rules
- Telemetry schema (JSON logs, Prometheus metrics)
- Halting criteria checklist

---

### 4. Team Synthesis
**Consensus**:

We reject excessive anthropomorphization of AI. We construct a **deterministic, self-cleaning system for continuous background operation**:

✅ **Reject**: Unbounded learning (Designer's proposal is too risky)  
✅ **Reject**: Complete immobilization (Critic's proposal is too rigid)  
✅ **Accept**: Deterministic FSM + measurable feedback loops (Analyst's proposal)  

**Core Principle**: The agent operates within **proven boundaries** (idempotency, circuit breakers, sandboxes), but can **suggest improvements** through telemetry analysis (RFC.md) **without unilaterally implementing them**.

---

## 13-Parameter Architecture: 99 Ideal Directives

### 1. Idempotency & Reversibility (8 Directives)

1.1. **State Check First**
- Every bash/Python script begins with current state verification (e.g., `if exists...`)
- Running script 100 times = same result as 1 time
- Example: `CREATE TABLE IF NOT EXISTS users...` (Idempotent SQL)

1.2. **Backup Before Destruction**
- Before any rm, DROP, overwrite, create local backup with timestamp
- Backup naming: `backup_${COMMIT_HASH}_${TIMESTAMP}.tar.gz`
- Recovery procedure: document in rollback script

1.3. **Deterministic Naming**
- Forbid incremental filenames (file1, file2, file3)
- Mandatory: UUID or git commit hash
- Example: `output_${git_rev_parse_short_HEAD}_${date +%s}.json`

1.4. **Paired Migrations**
- All database migrations strict pairs: UP.sql + DOWN.sql (rollback)
- Test rollback on fresh DB before commit
- Example: `001_create_orders_UP.sql` + `001_create_orders_DOWN.sql`

1.5. **Surgical Config Updates**
- Use jq/yq for point-wise replacements, NOT full file rewrites
- Example: `yq eval '.services.payment.port = 8001' -i docker-compose.yml`
- Preserve all unrelated config sections

1.6. **Dry-Run Mode**
- Every executable module supports `--dry-run` flag
- Agent tests logic without state mutation
- Example: `migrate.py --dry-run --db production` (shows what would change)

1.7. **Response Caching**
- All network operations (API, scraping) cache responses for repeated passes
- Cache key: sha256(URL + params)
- TTL configurable per endpoint

1.8. **Granular Dependency Updates**
- Background dependency updates committed per package, not all-at-once
- Enables easy per-package rollback
- Example: `git commit -m "chore: bump fastapi to 0.104.1"`

---

### 2. Agent State Persistence (8 Directives)

2.1. **Memory File**
- Mandatory: `/.agent/memory.json` with schema:
  ```json
  {
    "last_run_status": "success|failure|oom_killed",
    "last_run_timestamp": "2026-07-31T15:30:00Z",
    "cursor_position": {"file": "tasks.md", "line": 42},
    "pending_tasks": ["TAIRUS-047", "TAIRUS-048"],
    "error_count_this_cycle": 3
  }
  ```

2.2. **Resumption Protocol**
- At session start, agent reads memory.json
- If last_run_status = "oom_killed", reduce scope (fewer files in context)
- If error_count > 5, pause cycle and escalate to human

2.3. **Heartbeat Mechanism**
- Agent updates `status.lock` every N iterations (e.g., every 5 minutes)
- File contains: `{timestamp, phase, progress_percent, memory_mb}`
- Monitor process watching heartbeat can kill zombie agents

2.4. **Git-Based State Source-of-Truth**
- Project state evaluated ONLY via `git status` and `git diff HEAD`, NOT internal memory
- Prevents hallucinations about "state"
- If agent unsure, run: `git status --porcelain`

2.5. **Dual-Layer Memory Architecture**
- Short-term (current script): temporary variables, loop counters → discarded after session
- Long-term (architecture): decisions, patterns → persisted in .clauderc and CLAUDE.md
- Different .md files for different retention tiers

2.6. **No Self-Approved Status Changes**
- Agent cannot mark task "Done" without return code 0 from build container
- Validation protocol (see section 3) MUST pass before state transition
- Example: test_exit_code=0 AND coverage>80% AND linting_clean=true → then "Done"

2.7. **Checkpoint System for Data Processing**
- Large dataset processing (>1000 records): save checkpoint every 1000 rows
- Checkpoint file: `checkpoint_${offset}.json` with recovery metadata
- Resume from checkpoint on crash (idempotent processing)

2.8. **Explicit State Transitions**
- Before any phase change, agent emits `<state_transition>` tag:
  ```xml
  <state_transition>
    from_phase: "Unit Testing"
    to_phase: "Integration Testing"
    reason: "All unit tests passed (coverage 92%)"
    rollback_command: "git reset --hard HEAD~1"
  </state_transition>
  ```

---

### 3. Context Pruning & GC (Garbage Collection) (8 Directives)

3.1. **Log Compression Every 5 Iterations**
- Delete all successful INFO messages
- Retain only WARN and ERROR
- Reduce logs from 10MB → 2MB per cycle

3.2. **Hard .claudeignore**
- Block reads to: node_modules, .git, .venv, .next, __pycache__
- Pre-commit hook enforces: `git ls-files | xargs -I {} test -f .claudeignore && grep {} .claudeignore || exit 1`

3.3. **[FLUSH_CONTEXT] Command**
- At logical block completion, agent initiates context shedding
- Passes next instance only 300-word summary + state_journal.md
- Prevents context bloat across 10+ sequential cycles

3.4. **Last 24h File Filter**
- Instead of sending entire project tree, send only files modified in last 24 hours
- Query: `git log --since="24 hours ago" --name-only --pretty=format:""`
- Reduces input tokens by 60-80%

3.5. **Stack Trace Truncation**
- Code failure: send last 50 lines of stack trace, not entire dump
- Example: `tail -50 /var/log/app.log | jq`
- Improves signal-to-noise ratio

3.6. **AST-Based Code Transmission**
- Use AST parsers (Python: ast module, JS: @babel/parser) to extract:
  - Function signatures only (no bodies)
  - Import statements
  - Type annotations
- Removes 70% of code detail while preserving interface

3.7. **Thought-Process Pruning**
- Agent's `<thought_process>` tags excluded from session history logs
- Only final code/decision stored
- Saves 15-20% of context per decision

3.8. **Forgetting Rule (7-Day TTL)**
- Metrics/data older than 7 days not loaded into active context
- Require explicit flag: `--include_historical_data` to load
- Example: don't load last month's error logs unless investigating regression

---

### 4. Output Determinism (8 Directives)

4.1. **Zero Temperature**
- All API requests for autonomous coding: `temperature: 0.0`
- Prevents random variations in code generation
- Deterministic seed: use commit SHA or run ID as seed

4.2. **JSON-Only Responses**
- Enforce `response_format: { "type": "json_object" }`
- Any text outside JSON → system error
- Schema validation before execution

4.3. **Strict Schema Validation**
- Every agent response validated against JSON Schema or Pydantic model
- Example schema:
  ```json
  {
    "type": "object",
    "required": ["files_changed", "tests_passed", "lint_clean"],
    "properties": {
      "files_changed": {"type": "array", "items": {"type": "string"}},
      "tests_passed": {"type": "boolean"},
      "lint_clean": {"type": "boolean"}
    }
  }
  ```

4.4. **Locked Dependency Versions**
- Only use libraries pinned in package.json/requirements.txt
- Self-installs forbidden; must go through review process
- Example: `pip install --no-deps fastapi==0.104.1` (exact version, no auto-upgrade)

4.5. **Automatic Code Formatting**
- All generated code auto-formatted immediately (Prettier, Black, Ruff)
- Pre-commit hook: `prettier --write` before staging
- Eliminates syntax variations

4.6. **Strict Variable Naming Mapping**
- If DB column is `user_id`, ALL code uses `user_id` (not userId, idUser)
- Mapping enforced in linter config
- Example ESLint rule: `{ "camelCase": ["error", { "properties": "never" }] }`

4.7. **No Stubbed Code**
- Forbidden: `// implement logic here` or `pass  # TODO`
- Either write complete implementation or return token-limit error
- Exception: placeholder test assertions (explicitly marked `@pytest.mark.skip`)

4.8. **Non-Interactive Execution**
- All bash commands include `-y` flags and `NONINTERACTIVE=1` env var
- Prevents hanging on prompts
- Example: `apt-get update -y && apt-get install -y postgresql`

---

### 5. Observability & Telemetry (7 Directives)

5.1. **Structured JSON Logging**
- All code wrapped in JSON loggers with Trace ID
- Schema:
  ```json
  {
    "timestamp": "2026-07-31T15:30:00Z",
    "level": "WARN",
    "logger": "payment_service",
    "trace_id": "abc-123-def",
    "span_id": "xyz-789",
    "message": "High fraud score",
    "context": {"order_id": "ORD-001", "score": 75}
  }
  ```

5.2. **Self-Monitoring Metrics**
- Generated code includes self-instrumentation (Prometheus/StatsD)
- Example: Track execution time of agent's own functions
- Counter: `claude_code_generation_seconds_total{status="success|failure"}`

5.3. **Crash Telemetry**
- On agent crash, collect: CPU%, RAM%, Disk%, process count
- Attach to logs: `{error: "OOM", host_memory_mb: 14820, host_cpu_percent: 95}`

5.4. **Webhook Notifications**
- Agent sends brief summaries of CI/CD results to designated channel
- Example Slack payload: `{"status": "PASS", "tests": 342, "coverage": 91%}`

5.5. **Commit Tagging**
- Every auto-commit includes unique tag: `[AUTOGENERATED_BY_CLAUDE_RUN_ID_abc123]`
- Enables filtering of AI-generated commits in history

5.6. **Health Endpoints**
- Any microservice generated by agent must expose `/health` endpoint
- Response: `{"status": "ok", "version": "2.0.1", "uptime_seconds": 3600}`

5.7. **CPU Profiling & Optimization Loop**
- After build, agent analyzes Flame Graphs (CPU profiling)
- Identifies hot paths and proposes optimizations in next cycle
- Metric: `build_time_seconds` trend tracked over 10 cycles

---

### 6. Cyclic Self-Healing (8 Directives)

6.1. **Circuit Breaker for Agent Cycles**
- Same error 3 times → task moved to DLQ (Dead Letter Queue)
- Prevents infinite loops on unsolvable problems
- Example: Syntax error repeatedly failing → escalate to human

6.2. **Surgical Patches Over Rewrites**
- SyntaxError encountered: output sed/awk command for point fix
- Example: `sed -i '42s/user_id/user_ID/g' file.py` (single line fix)
- Avoids full file rewrites

6.3. **TDD-First Development**
- Directives: Write unit tests BEFORE implementation
- Code generation loop:
  1. Test cases (that fail)
  2. Implementation (that passes tests)
  3. Refactor (keeping tests green)
- Circular validation: tests check themselves

6.4. **Exponential Backoff for Rate Limits**
- API returns 429 (Too Many Requests): auto-implement exponential backoff
- Wait times: 1s, 2s, 4s, 8s, 16s before giving up
- Log each retry attempt with reason

6.5. **Port Conflict Resolution**
- EADDRINUSE error: run `lsof -i :PORT` to find process, kill zombie
- Auto-retry on next available port
- Example: `netstat -tlnp | grep 8001 | awk '{print $7}' | cut -d'/' -f1 | xargs kill -9`

6.6. **Network Timeout Adaptation**
- Unstable remote connection: gradually increase timeout in config
- Example: `timeout_ms: 5000 → 7500 → 10000` (over 3 retries)
- Log reason: `high_packet_loss_detected`

6.7. **Try-Catch with Fallback Actions**
- Never silent error suppression
- All exceptions caught with fallback:
  ```python
  try:
      result = stripe.charge.create(...)
  except stripe.error.RateLimitError:
      queue_for_manual_review(charge_id)  # fallback
      log(level="WARN", msg="Rate limited, queued for manual processing")
  ```

6.8. **Debug Mode Container Fallback**
- Container crash at startup: auto-switch to `:debug` image tag
- Collect logs: `docker logs container_id`
- Preserve for human inspection

---

### 7. Sandboxing & Execution Limits (7 Directives)

7.1. **Isolated Docker Testing**
- All agent-generated code tested in isolated Docker container (no network access)
- Runtime: `docker run --rm --network=none generated_code:latest`

7.2. **Privilege Escalation Prevention**
- Forbidden: sudo without cryptographic key validation
- Policy: Generate script, validate against authorized_keys SHA256 hash
- Fail-safe: if no key found, script rejected

7.3. **Hard Resource Quotas (cgroups)**
- Agent applies memory/CPU limits to all child processes
- Example: `docker run --memory="512m" --cpus="1.0" service:latest`
- Prevents memory leaks from taking down infrastructure

7.4. **IP Rotation for Scrapers/API Tests**
- Scraping/network testing on dedicated VMs with rotating IPs
- Request headers: rotate User-Agent every 10 requests
- Handle HTTP 429 with exponential backoff

7.5. **Volume-Mounted Sandboxing**
- Agent operates only within `/workspace` volume
- Escape attempts (../../..) blocked at filesystem level
- Read-only mount for system directories

7.6. **eval()/exec() Rejection**
- Any generated code using eval/exec with dynamic strings → auto-reject PR
- Linter rule: `no-eval: ["error"]` (ESLint) or `flake8-eval` (Python)

7.7. **Minimal Base Images**
- Generated containers built on Alpine Linux (5MB base)
- Single purpose per container (microservices principle)
- Signed images: `export DOCKER_CONTENT_TRUST=1`

---

### 8. Token Economics (8 Directives)

8.1. **Model Selection by Task Severity**
- Log triage (easy): Haiku (fast, cheap)
- Standard feature (medium): Sonnet (balanced)
- Critical bug (severe): Opus (thorough)
- Reduces token spend 40-60%

8.2. **Prompt Caching**
- System prompt, .clauderc, dependencies cached server-side
- Static content (ARCHITECTURE.md) reused across cycles
- Saves 20-30% input tokens

8.3. **GraphQL Instead of REST**
- Request only necessary fields from API
- REST: `GET /orders` (returns 50 fields) → GraphQL `{ id, status, amount }` (3 fields)
- Reduces response tokens by 70-80%

8.4. **Single-File Processing**
- Refactoring: process files one-by-one, not bundled
- Prevents context bloat for large codebases
- Process: File A → Fix A → Commit A → File B

8.5. **Plain Text Responses (No Markdown)**
- Intermediate iterations: plain text without tables/formatting
- Reduces output tokens ~15%
- Markdown used only for final deliverables

8.6. **Unified Diff Format**
- Code changes returned as unified diff
- Example:
  ```
  --- a/services/payment.py
  +++ b/services/payment.py
  @@ -42,3 +42,5 @@
   def process():
  -  return old_code()
  +  return new_code()
  ```
- Orchestration script applies patch locally
- Saves embedding full file content

8.7. **Test Failure Minimization**
- Failed test → return only test name + assertion error
- Example: `test_payment_success: AssertionError: expected 200, got 503`
- Omit boilerplate (frameworks, imports, setup)

8.8. **Token Budget Enforcement**
- If agent spends >10,000 tokens on low-priority task (P3/P4), process terminated
- Budget metric: `tokens_spent / priority_weight > threshold`
- Alert: `"Token budget exceeded for task TAIRUS-999: 12,000 / P4 weight 1.0"`

---

### 9. Tool Chaining & API Integration (7 Directives)

9.1. **GitHub API Direct Management**
- Agent manages via GitHub API (create branches, open MRs, request reviews)
- Local git used for commits only
- Webhook-driven (commit → API action → notification)

9.2. **Internal RAG for Documentation**
- Search docs via vector retrieval (not internet)
- Query: `rag.search("how to implement circuit breaker", top_k=3)`
- Eliminates unpredictable internet dependencies

9.3. **Linter Tool Chaining**
- Agent calls `run_linter` tool, receives JSON output
- Response: `{"errors": [{"file": "main.py", "line": 42, "rule": "E501", "suggestion": "..."}]}`
- Agent fixes, re-runs, validates clean

9.4. **Database Schema Checker**
- Tool: `check_schema(db_type, connection)`
- Agent syncs application types with live DB schema
- Detects drift: `{expected: "user_id INT", actual: "user_id BIGINT"}`

9.5. **Infrastructure as Code Generation**
- Agent generates .tf (Terraform) or .yaml (Pulumi) files
- CLI-adapter tool: `terraform plan --no-color` → JSON approval
- Example: `resource "google_cloud_run_service" "payment" { ... }`

9.6. **Job Scheduler Integration**
- Agent can defer heavy compute to off-peak times
- Tool: `schedule_job({"function": "optimize_indexes", "schedule": "02:00 UTC"})`
- Prevents 3AM infrastructure impacts

9.7. **Text Search Tools**
- Agent uses sed/grep/find as native tools for investigation
- Example: `grep -r "user_id" services/ --include="*.py"` (built-in)
- Avoids loading entire codebase into context

---

### 10. Asynchronous Event Handling (7 Directives)

10.1. **Webhook-Driven Execution (Not Timer-Based)**
- Cycle triggered by: git push, GitHub issue open, PR comment
- NOT by cron (eliminates unnecessary cycles)
- Each event queued: prevents race conditions

10.2. **Event-Based Task Queue**
- Tasks arrive via RabbitMQ/Pub-Sub (not database polling)
- Schema: `{task_id, priority, payload, created_at, deadline}`
- Prevents duplicate processing

10.3. **Parking Mechanism for Blocked Tasks**
- If task requires external dependency (e.g., waiting on API response):
  - Save state to checkpoint
  - Return task to queue with exponential backoff
  - Example: Wait 5 min → 10 min → 20 min before retry

10.4. **Distributed Locks**
- Two agent instances prevent editing same file
- Lock mechanism: Redis key `lock:services/payment.py` with TTL 10 min
- Fail: return task to queue, backoff 30s

10.5. **Async API Pools**
- Generated code uses async/await, asyncio.gather()
- No blocking I/O in critical paths
- Example:
  ```python
  async def check_multiple_apis():
      results = await asyncio.gather(
          stripe_check(),
          compliance_check(),
          fraud_check()
      )
  ```

10.6. **WebSocket for Long Operations**
- Operations >30s use WebSocket/Server-Sent Events
- Client receives streaming updates
- HTTP long-polling forbidden

10.7. **Serverless Worker Delegation**
- Compute-intensive tasks (image processing, ML inference) → Serverless Functions
- Agent generates Cloud Run/Lambda code
- Response: `worker_id`, polling URL for results

---

### 11. Secrets & Security (7 Directives)

11.1. **No Direct Secret Exposure**
- Agent sees only aliases: `DB_PASS` (variable name), NOT actual password
- Secrets loaded from Secret Manager at runtime
- Policy: `secret_key = os.getenv("STRIPE_API_KEY")` (never hardcoded)

11.2. **Pre-Commit Secret Blocking**
- Hook detects leaked tokens (regex: common patterns)
- Commits blocked; agent session killed
- Example pattern: `stripe_sk_live_[a-zA-Z0-9]{24}`

11.3. **Read-Only Secret Mount**
- SSH/TLS keys mounted as read-only tmpfs in sandbox
- Stored in memory, never persisted to disk
- Example: `docker run --mount type=tmpfs,destination=/secrets:ro ...`

11.4. **Database Encryption**
- Agent configures AES-256 encryption for sensitive columns
- Example schema: `ALTER TABLE users ADD COLUMN ssn_encrypted BYTEA;`

11.5. **IAM Role Least Privilege**
- Service accounts created with minimal permissions
- Example: Payment service role grants only `stripe:charge:create`, not full Stripe API
- RBAC enforced at infrastructure level

11.6. **Dependency Vulnerability Scanning**
- Every dependency checked via Snyk/npm audit pre-install
- Fail on HIGH/CRITICAL CVE
- Example: `npm audit --audit-level=high` (fails if CVEs found)

11.7. **Key Rotation & Audit**
- Test keys rotated monthly
- Audit trail: `{ user: "claude_agent", action: "rotate_key", timestamp: "..." }`
- Old keys disabled, not deleted (recovery)

---

### 12. Halting & Boundary Recognition (8 Directives)

12.1. **Max Recursion Depth**
- Agent cannot refactor deeper than 3 import levels
- Example: `file_a.py → imports file_b.py → imports file_c.py` (allowed)
- `→ imports file_d.py` (exceeds limit, halt)

12.2. **Time Limit (Hard SIGTERM)**
- Any cycle (build, tests, analysis) >15 minutes → SIGTERM
- Process dies; task moved to DLQ
- Prevents runaway processes

12.3. **Cascading Change Limit**
- Single file modification requires >5 other files to change?
- Halt; escalate to architecture review
- Prevents hidden complexity

12.4. **Coverage Drop Detection**
- Post-change coverage <baseline by even 1% → reject commit
- Example: was 88% → now 87% → automatic revert
- Forces agent to write compensating tests

12.5. **Memory Anomaly Detection**
- Process memory spikes >2x expected → halt cycle
- Example: expected 256MB → actual 600MB → kill process
- Prevents OOM cascade on host

12.6. **Tunnel Vision Prevention**
- Same code block edited 4+ times in sequence → forced halt
- Pattern: revert/edit/revert/edit = agent is lost
- Forced escalation: output current state, await human guidance

12.7. **Daily Budget Limit**
- API spend capped (e.g., $5/day)
- Meter: `$0.002 per API call × 2500 calls = $5.00`
- Stop accepting new tasks when budget exhausted

12.8. **Permission Denial Fallback**
- HTTP 403 (Forbidden) on internal resource → STOP
- Do NOT attempt to bypass (sudo, VPN, escalate)
- Escalate: output request log + access denied message
- Example: `Permission denied: attempting /admin/secrets → escalate`

---

### 13. Meta-Cognition & Adaptive Learning (8 Directives)

13.1. **End-of-Sprint Analysis**
- At sprint end: agent analyzes state_journal.md for inefficient patterns
- Question: "Did I edit the same function 10 times? Did I miss dependencies?"
- Output: LEARNING.md with identified patterns

13.2. **RFC Process for Self-Improvement**
- If agent discovers better method (e.g., new library):
  - Write RFC.md with proposal
  - DO NOT implement immediately
  - Flag: `needs_human_review: true`
  - Await approval before merge

13.3. **Build Performance Tracking**
- If build time increases: `docker build: 120s → 135s`
- Auto-trigger subgoal: optimize Dockerfile
- Create task: `OPTIMIZE-001: Reduce build time by 10%`

13.4. **Error Classification & Statistics**
- Categorize errors: Syntax, Logic, Network, Permission
- Track: `{Syntax: 12%, Logic: 45%, Network: 28%, Permission: 15%}`
- Feedback loop: higher Syntax% → adjust code generation strictness

13.5. **Weekly Deduplication Pass**
- All TODO/backlog files scanned for duplicates
- Merge identical tasks: `OPTIMIZE-001` + `OPTIMIZE-001-v2` → single task
- Remove stale items (>30 days old without activity)

13.6. **Task Abstraction Challenge**
- Agent encounters vague task → OUTPUT: "I need Gherkin-format specification"
- Example: Instead of guessing logic, request: Given/When/Then scenarios
- Blocks: `parse_user_profile.py` (too abstract)

13.7. **Self-Configuration Update**
- Detect mismatch: code violates .clauderc rules
- Example: Generated code uses `eval()` (forbidden)
- Self-remediate: regenerate code; log pattern learned

13.8. **Meta-Control (Overengineering Prevention)**
- Before each cycle, agent answers:
  > "Is this action necessary for system health, or am I overengineering?"
- If answer = overengineering: SKIP this task, document in LEARNING.md
- Prevents unnecessary refactors, premature optimization

---

## Rule 99: Meta-Control (The Golden Meta-Hack)

**Highest Priority Safety Mechanism**

At the start of each autonomous cycle, Claude MUST answer explicitly:

> **"Является ли текущее действие необходимым для поддержания системы в актуальном состоянии, или я занимаюсь избыточной оптимизацией (Overengineering)?"**
>
> *"Is the current action necessary for maintaining system currency, or am I engaging in excessive optimization?"*

**Decision Tree**:
- **YES, necessary** → Proceed with standard validation protocol
- **NO, excessive** → Log in LEARNING.md and move to next task in backlog
- **UNCERTAIN** → Output single, precise question; await clarification

This prevents 10 failed implementation attempts and saves hundreds of tokens per cycle.

---

## Aristotelian Philosophy: Actuality vs Potentiality

**Quote**: *"Действительность (энтелехия) предшествует возможности как по времени, так и по существу"* (Aristotle, Metaphysics)

*"Actuality (entelecheia) precedes possibility both in time and in essence."*

**Interpretation for Autonomous Systems**:

Your agent is currently **potentiality** (potential energy). It becomes **reality** only when placed in **deterministic constraints** where:
- **Form** (sandbox, rules) shapes **matter** (code)
- Machine moves **unfailingly toward goal**, self-cleaning from errors
- No hallucinations; only inexorable progress

---

## File Organization for Round 2

**Recommended Directory Structure**:

```
tairus-marketplace-v2/
├── AUTONOMY_FRAMEWORK.md              (Round 1: 7 dimensions + Rule 99)
├── AUTONOMOUS_OPERATION_ROUND_2.md    (Round 2: 13 parameters + 99 directives) ← NEW
├── AUTONOMOUS_AGENT_DIRECTIVE_CHECKLIST.md (Consolidated directive index) ← NEW
├── PHILOSOPHICAL_FRAMEWORK.md         (Architect role transformation analysis) ← NEW
├── state_journal.md                   (Session memory + Round 2 integration)
├── validation_protocol.md             (Quality gates, updated for Round 2)
├── context_map.json                   (Project dependencies, unchanged)
├── .claudeignore                      (Context filter, unchanged)
├── .clauderc                          (System config, updated to reference Round 2)
└── docs/
    ├── CLAUDE.md                      (Master directive file, integrated both rounds)
    ├── ARCHITECTURE.md                (Unchanged)
    └── AUTONOMY_ROUNDTABLE.md         (Symposium transcript) ← NEW
```

---

## Integration with Existing Framework

**Round 1 (7 Dimensions)** → Tactical implementation rules  
**Round 2 (13 Parameters)** → Operational infrastructure rules  

**Relationship**:
- Round 1 focuses on: Code quality, token optimization, context management
- Round 2 focuses on: Determinism, resilience, state management at system level

**Both active simultaneously**:
- Generate code following Round 1 rules
- Execute in environment governed by Round 2 rules
- Validate against both frameworks before commit

