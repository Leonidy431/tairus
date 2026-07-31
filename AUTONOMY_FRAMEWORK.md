# TAIRUS Autonomy Framework: Complete Specification
**Version:** 2.0 (Integrated Round 1 + Round 2)  
**Status:** Production Ready  
**Authority:** Engineering Team + 12-Expert Symposium (Peripatetic Dialogue)  
**Enforcement:** Non-negotiable for autonomous sessions
**Last Updated**: 2026-07-31

---

## Quick Navigation

This framework has evolved through two rounds of expert consensus:

| Framework | Dimensions/Parameters | Total Directives | Purpose | Status |
|-----------|----------------------|------------------|---------|--------|
| **Round 1: Foundations** | 7 dimensions | 99 directives | Code quality, context, token efficiency | ✅ Implemented |
| **Round 2: Operations** | 13 parameters | 99+ directives | Determinism, resilience, state management | ✅ NEW |
| **Integrated** | 7 + 13 | 198+ directives | Complete autonomous operation system | ✅ ACTIVE |

---

## Integration: How Rounds 1 & 2 Work Together

### Round 1 (7 Dimensions): What Claude Should Do
Focuses on **code generation and context management**
- Context Density: How to compress information
- Determinism: How to generate predictable code
- Self-Healing: How to fix its own errors
- State Management: How to track progress
- Token Efficiency: How to operate within budget
- Tool Chaining: How to use integrated tools
- Security: How to avoid vulnerabilities

### Round 2 (13 Parameters): How the System Should Operate
Focuses on **autonomous execution and infrastructure**
- Idempotency: Repeatable, reversible operations
- State Persistence: Memory and resumption
- Context Pruning: Garbage collection
- Output Determinism: Reproducible results
- Observability: Monitoring and telemetry
- Self-Healing: Error detection and recovery
- Sandboxing: Safe execution environments
- Token Economics: Cost-efficient operation
- Tool Chaining: API integration at scale
- Async Handling: Non-blocking operations
- Secrets Management: Security at runtime
- Halting Criteria: When to stop and escalate
- Meta-Cognition: Learning from behavior

---

## Three-Phase Implementation

### Phase 1: Foundation (Essential - Week 1-2)
**Implement**: Round 2 Parameters 1-7 + Round 1 Dimensions 2, 4, 7  
**Outcome**: Safe, reversible, sandboxed autonomous execution

### Phase 2: Operations (Robust - Week 3-6)
**Implement**: Round 2 Parameters 8-12 + Round 1 Dimensions 1, 3, 5  
**Outcome**: Efficient, observable, self-healing system

### Phase 3: Intelligence (Adaptive - Week 7+)
**Implement**: Round 2 Parameter 13 + Rule 99 + Round 1 Dimension 6  
**Outcome**: Learning system with strategic governance

---

## Executive Summary

This framework encodes **198+ principles** (organized into 7 dimensions + 13 parameters + 1 meta-rule) to transform Claude from a conversational AI into a **production-grade autonomous software architect**. 

**Why this matters:** Autonomous agents without guardrails produce:
- Infinite loops (hallucinations about what was done)
- Context bloat (forgotten prior decisions)
- Cascading failures (lack of error recovery)
- Token waste (inefficient prompting)
- Security disasters (hardcoded secrets, SQL injection)
- Drift (gradual divergence from original design)

This framework prevents all of the above through a combination of:
- **Strict boundaries** (Rule 99, halting criteria)
- **Observable state** (telemetry, state_journal, audit trails)
- **Strategic governance** (RFC approval process, quarterly reviews)
- **Deterministic execution** (sandboxing, cgroups, circuit breakers)

---

## The 7 Dimensions of Autonomy

| Dimension | Purpose | Hacks Count |
|-----------|---------|------------|
| **1. Context Density** | Compress context without losing meaning | 14 |
| **2. Determinism** | Make behavior predictable and reproducible | 14 |
| **3. Self-Healing** | Auto-fix errors without human intervention | 14 |
| **4. State Management** | Track progress and prevent loops | 14 |
| **5. Token Efficiency** | Maximize intelligence per token spent | 14 |
| **6. Tool Chaining** | Integrate tools into automated workflows | 14 |
| **7. Security/Sandboxing** | Prevent data leaks and exploits | 14 |
| **META (Rule 0)** | Master rule: Halt before 90% confidence | 1 |

**Total: 99 principles**

---

## DIMENSION 1: Context Density (14 Hacks)

### 1.1: Never Ask for Full File Rewrites
**Rule:** "Only diff or modified functions"

**Rationale:** Reading 500-line file → understanding → rewriting → 1000 tokens burned.

**What to do:**
```bash
# BAD
"Rewrite services/payment-service/payment_processor.py to use async/await"

# GOOD
"In payment_processor.py, modify process_payment() to use async/await. Show only the diff."
```

**Automation:** Add to .clauderc:
```
output_format: "diff_only"
```

---

### 1.2: Use XML Tags for Structural Clarity
**Rule:** "XML > Markdown for Claude's reading"

**Rationale:** Claude parses XML 2-3x faster than Markdown with loose formatting.

**What to do:**
```xml
<!-- CLAUDE.md structure -->
<core_logic>
  <service name="payment-service">
    <responsibility>Process payments, manage escrow</responsibility>
    <must_integrate_with>
      <service>compliance-service</service>
      <service>fraud-detector</service>
    </must_integrate_with>
  </service>
</core_logic>

<ui_layer>
  <framework>React 18 + Next.js 14</framework>
  <state_management>Redux, not Context API (coupling risk)</state_management>
</ui_layer>
```

---

### 1.3: Separate Hot & Cold Context
**Rule:** "Active sprint = hot context. Archive = cold context"

**What to do:**
- **Hot Context (CLAUDE.md - Section A):** Current sprint tasks, blocking issues
- **Cold Context (ARCHITECTURE.md - Reference):** General system design, not in active work

**Rationale:** Prevents Claude from re-reading irrelevant decisions.

---

### 1.4: Forbid Apologies & Pleasantries
**Rule:** "No 'Certainly!' or 'Here's your code' preambles"

**Add to .clauderc:**
```
style:
  no_apologies: true
  no_pleasantries: true
  response_format: "code_only_with_explanation"
```

**Rationale:** Every "Certainly, here's..." burns 20 tokens.

---

### 1.5: Summaries at Session Boundaries
**Rule:** "Before context overflow, generate 500-token summary"

**What to do:**
```bash
# When token usage hits 80% of limit:
"Generate a session summary (max 500 tokens) for hand-off to next session:
- Completed tasks
- Current blockers
- Next 3 steps
- File paths changed"
```

---

### 1.6: Compress Large Data Structures
**Rule:** "Only send 2-3 example objects, not full arrays"

**What to do:**
```json
// BAD: Full array of 1000 orders
[{order_id: 1, amount: 100}, {order_id: 2, amount: 200}, ..., {order_id: 1000}]

// GOOD: Example + metadata
{
  "sample": [{order_id: 1, amount: 100}, {order_id: 2, amount: 200}],
  "total_records": 1000,
  "schema": "See orders table in context_map.json"
}
```

---

### 1.7: Abbreviate Frequent Commands
**Rule:** "Create shortcodes for repeated instructions"

**Add to .clauderc:**
```yaml
shortcuts:
  "[V]": "Run validation_protocol.md completely"
  "[R]": "Refactor for performance (CPU, memory)"
  "[S]": "Search codebase for pattern (explain output)"
  "[C]": "Check compliance with LEGAL_AND_COMPLIANCE.md"
  "[T]": "Write tests for this function"
```

---

### 1.8: Externalize Detailed Logs
**Rule:** "Bulky logs → separate reference doc, not inline"

**What to do:**
```bash
# Create: logs_2026-07-31.json (compressed)
# Keep in: state_journal.md (link only)

# In prompt:
"Review logs_2026-07-31.json (lines 1-50) for the 503 error. Only report: error message + stack trace + solution."
```

---

### 1.9: Use Tree View for Project Structure
**Rule:** "Never copy full file paths. Use `tree -I` output"

**What to do:**
```bash
# Store this once in context_map.json:
tree -I 'node_modules|.git|dist|build' services/ > /tmp/project_tree.txt

# Reference in prompts:
"See context_map.json for directory structure. The issue is in auth-service/middleware.go"
```

---

### 1.10: JSON-Only Responses When Context Tight
**Rule:** "If context > 75%, ask for JSON output"

**What to do:**
```bash
# When token budget low:
"Output as JSON only (no prose):
{
  \"task_status\": \"DONE\",
  \"files_changed\": [],
  \"tests_passing\": true
}"
```

**Rationale:** JSON forces compression of meaning.

---

### 1.11: Limit Code Analysis Depth
**Rule:** "Analyze only 3 levels of imports"

**What to do:**
```bash
# BAD: Trace import chain 10 levels deep
# GOOD: "Analyze PaymentService -> its 3 direct dependencies only"
```

---

### 1.12: Delete Trivial Comments
**Rule:** "If comment duplicates function name, delete it"

**What to do:**
```python
# BAD
def calculate_fraud_score():
    # Calculate the fraud score
    return score

# GOOD
def calculate_fraud_score():
    # Combines 7 risk dimensions with exponential weighting
    return score
```

---

### 1.13: Auto-Clean Logs Before Context Submission
**Rule:** "Pre-process logs: remove timestamps, info-level lines"

**Create script: `scripts/clean-logs.sh`**
```bash
#!/bin/bash
# Remove timestamps, collapse whitespace
sed -E 's/\[.*\] //' $1 | grep -E 'ERROR|FATAL|WARN' | uniq
```

---

### 1.14: Archive Old Code After 1 Month
**Rule:** "If file unchanged > 30 days and not core, don't analyze"

**Add to .claudeignore:**
```
# Prevent re-reading stale code
# Exception: core services (auth, payment, compliance)
services/*/old_code/
legacy_v1*/
archive/deprecated/
```

---

## DIMENSION 2: Determinism (14 Hacks)

### 2.1: Always Specify Role First
**Rule:** "Before any task: define your role"

**What to do:**
```bash
"You are a Senior Backend Architect with 10 years experience building payment systems.
Your constraints: Microservices only, Go/Python/Node only, PostgreSQL mandatory.
Now, design the refund flow for TAIRUS."
```

---

### 2.2: Pin Language & Framework Versions
**Rule:** "No 'latest' or 'next'. Exact versions only"

**What to do:**
```bash
# SPECIFY:
"Using: Python 3.11.2, FastAPI 0.104.1, Pydantic 2.5.0, SQLAlchemy 2.0.23"

# NOT:
"Using: Python, FastAPI, etc."
```

---

### 2.3: Chain-of-Thought Before Code
**Rule:** "Explicit thinking before implementation"

**What to do:**
```bash
<thought_process>
1. Payment service receives order_create event from Kafka
2. Fraud detector scores transaction (7 risk dimensions)
3. If BLOCKED: send refund to escrow, notify buyer
4. If APPROVED: create payment record, mark as pending
5. If CHALLENGED: send 2FA prompt, wait for verification
</thought_process>

[NOW WRITE CODE]
```

---

### 2.4: Negative Prompts (What NOT to Do)
**Rule:** "Explicitly forbid anti-patterns"

**What to do:**
```bash
"Implement auth service. FORBIDDEN:
- Do NOT use passwords in plaintext
- Do NOT use eval() or dynamic code execution
- Do NOT create new JWT library (use 'jsonwebtoken' npm package)
- Do NOT hardcode secret keys
- Do NOT use any framework besides FastAPI"
```

---

### 2.5: Penalty System for Code Smells
**Rule:** "Specific punishments for specific anti-patterns"

**Add to .clauderc:**
```yaml
penalties:
  any_type_in_typescript: "Task counts as FAILED"
  eval_or_exec: "Task rejected, restart from scratch"
  plaintext_password: "Immediate security violation, abort"
  hardcoded_api_key: "Fail with prejudice"
```

---

### 2.6: Pseudocode Before Complex Logic
**Rule:** "For algorithms > 50 lines, write pseudocode first"

**What to do:**
```bash
# PSEUDOCODE
FUNCTION calculateFraudScore(transaction):
    score = 0
    
    // Check velocity
    recent_txns = query(user_id, time_window=1min)
    IF recent_txns > 5 THEN score += 15
    
    // Check geographic impossibility
    last_location = redis.get(user_id + ":location")
    current_location = geoip(user_ip)
    distance = haversine(last_location, current_location)
    speed_required = distance / time_elapsed
    IF speed_required > 900 km/h THEN score += 20  // Airplane speed
    
    RETURN score

# [NOW: Implement in Python/Go/etc.]
```

---

### 2.7: Enforce Naming Conventions in Config
**Rule:** "No variations on naming. Lock it down"

**Add to .clauderc:**
```yaml
naming:
  variables: "camelCase"
  functions: "camelCase"
  classes: "PascalCase"
  constants: "UPPER_SNAKE_CASE"
  private_methods: "_leadingUnderscore"
  enforce: "STRICT"  # Reject if violated
```

---

### 2.8: Big O Notation Comments Required
**Rule:** "Any loop operating on large data must document complexity"

**What to do:**
```python
def find_duplicate_orders(orders: List[Order]) -> List[Tuple[Order, Order]]:
    # O(n log n) time, O(n) space
    # Sort by (user_id, amount, timestamp), then scan for consecutive duplicates
    # Rationale: Better than O(n²) naive comparison for 10M+ orders
    
    sorted_orders = sorted(orders, key=lambda o: (o.user_id, o.amount, o.timestamp))
    duplicates = []
    
    for i in range(len(sorted_orders) - 1):
        if (sorted_orders[i].user_id == sorted_orders[i+1].user_id and
            sorted_orders[i].amount == sorted_orders[i+1].amount and
            (sorted_orders[i+1].timestamp - sorted_orders[i].timestamp).seconds < 60):
            duplicates.append((sorted_orders[i], sorted_orders[i+1]))
    
    return duplicates
```

---

### 2.9: Given-When-Then for Edge Cases
**Rule:** "List every edge case explicitly"

**What to do:**
```bash
"Implement refund logic. Edge cases:

GIVEN: Buyer requests refund after 90-day delivery window
  WHEN: Seller disputes refund with shipping proof
  THEN: Automatically reject refund, notify buyer of expiry

GIVEN: Escrow balance is 0 (service is poor)
  WHEN: Refund requested
  THEN: Halt, investigate escrow integrity

GIVEN: Refund amount > remaining escrow balance
  WHEN: Multiple buyers request simultaneously
  THEN: Process FIFO, queue overflow for manual review"
```

---

### 2.10: Ban Deprecated APIs
**Rule:** "Explicitly forbid outdated methods"

**Add to .clauderc:**
```yaml
banned_apis:
  python:
    - "document.write() [deprecated]"
    - "asyncio.coroutine [use async/await]"
  javascript:
    - "var [use const/let]"
    - "XMLHttpRequest [use fetch]"
  go:
    - "io.Copy [use io.CopyBuffer]"
```

---

### 2.11: Structured Error Format
**Rule:** "[ERROR] → [CAUSE] → [SOLUTION]"

**Add to .clauderc:**
```yaml
error_format: "[ERROR] Connection timeout to payment gateway [CAUSE] TCP socket timeout after 30s [SOLUTION] Increase timeout to 60s, implement exponential backoff"
```

---

### 2.12: "Stop-Words" Trigger Human Review
**Rule:** "If Claude generates these, HALT and ask"

**Add to .clauderc:**
```yaml
stop_words:
  - "probably"
  - "might work"
  - "if everything goes right"
  - "we could try"
  - "hopefully"
  
trigger: "STOP. High uncertainty detected. Explain exact confidence % and ask 1 clarifying question."
```

---

### 2.13: Architectural Checkpoint Before Decisions
**Rule:** "Before architectural decision, reference ARCHITECTURE.md"

**What to do:**
```bash
"Before proposing async job queue, check: 
docs/ARCHITECTURE.md → Async Processing Pattern → existing decision
If conflicts, explain why we're overriding it."
```

---

### 2.14: 100% Type Coverage
**Rule:** "All function signatures fully typed before body implementation"

**What to do:**
```typescript
// GOOD: Types first
function processPayment(
  orderId: string,
  amount: Decimal,
  paymentMethod: PaymentMethod,
  idempotencyKey: string
): Promise<PaymentResult> {
  // ... implementation
}

// BAD: No types
function processPayment(orderId, amount, paymentMethod, idempotencyKey) {
  // ... implementation
}
```

---

## DIMENSION 3: Self-Healing (Error Recovery) (14 Hacks)

### 3.1: Prompt-Reflection
**Rule:** "After code, self-audit for vulnerabilities"

**What to do:**
```bash
# After writing fraud_detector.py:
"Review your code. Find 3 security vulnerabilities. Explain and fix each."

# Claude should find:
# 1. Timing attack (checking >= 0 exposes presence of key)
# 2. Integer overflow in score summation
# 3. SQL injection in user_id parameter
```

---

### 3.2: Failure-First Testing
**Rule:** "Write tests for failure before success"

**What to do:**
```python
# Write this test FIRST:
def test_payment_fails_if_fraud_score_exceeds_threshold():
    """Given fraud score 85, when process_payment called, then BLOCK decision"""
    assert payment_service.process_payment(...) == {"status": "BLOCKED"}

# THEN write the code that passes it
```

---

### 3.3: Structured Error Analysis
**Rule:** "On console error, ask Claude: 'Explain cause, then 2 solutions'"

**What to do:**
```bash
# Console error:
ERROR: Unexpected token } in payment_processor.py:156

# Prompt Claude:
"Error: ^above^. Explain the cause without proposing code. Then list 2 ways to fix it."
# (Forces Claude to think before coding)
```

---

### 3.4: Circuit Breaker for Claude's Work
**Rule:** "If 3 consecutive attempts fail, halt and ask for context"

**What to do:**
```bash
# Attempt 1: Failed (test didn't pass)
# Attempt 2: Failed (different test)
# Attempt 3: Failed (same root cause)

# AUTO TRIGGER:
"3 failures detected on this task. Before retry #4:
1. Explain the root cause (not the symptoms)
2. Ask: What clarification do you need from the engineer?"
```

---

### 3.5: Fallbacks for Network Requests
**Rule:** "Every external API call must have a fallback"

**What to do:**
```python
def authorize_payment_with_fallback(amount: Decimal, card_token: str) -> PaymentResult:
    try:
        return stripe.charge.create(amount, card_token)
    except stripe.APIError as e:
        logger.error(f"Stripe error: {e}")
        # Fallback: queue payment for manual review
        return {
            "status": "PENDING_MANUAL_REVIEW",
            "reason": "Stripe unavailable",
            "queue_id": queue_payment_for_review(amount, card_token)
        }
```

---

### 3.6: No Silent Failures
**Rule:** "All exceptions must log context"

**What to do:**
```python
# BAD
try:
    result = process_payment()
except Exception:
    pass  # Silent death

# GOOD
try:
    result = process_payment()
except Exception as e:
    logger.error(
        "Payment processing failed",
        extra={
            "order_id": order_id,
            "user_id": user_id,
            "amount": amount,
            "error": str(e),
            "traceback": traceback.format_exc()
        }
    )
    raise  # Fail loudly
```

---

### 3.7: Merge Conflict Protocol
**Rule:** "On git conflict, never auto-resolve. Show step-by-step plan"

**What to do:**
```bash
# Instead of:
"git merge origin/main"

# Do:
"Conflict detected in services/payment-service/main.py
1. Show both versions (ours vs theirs)
2. Explain each change
3. List 3 resolution options (with tradeoffs)
4. Ask: which option makes sense architecturally?"
```

---

### 3.8: Analyze Stack Traces from the Bottom
**Rule:** "Start at deepest function call, work backwards"

**What to do:**
```
Stack trace (deepest first):
  at calculate_score (fraud_detector.py:67) ← START HERE
  at process_payment (payment_processor.py:45)
  at handle_request (api.py:12)

"The error is in calculate_score() at line 67. This is called because...
The root cause is NOT in handle_request() (top of trace)."
```

---

### 3.9: Debug Deep Mode
**Rule:** "When stuck, expand 1 line into 10 with logging"

**Create: alias debug_deep**

**What to do:**
```python
# Original (broken):
result = fraud_detector.score_transaction(user_id, amount, ip)

# Debug Deep (expanded):
print(f"[DEBUG] Starting fraud detection")
print(f"[DEBUG] user_id={user_id}, amount={amount}, ip={ip}")

velocity_score = fraud_detector._check_velocity(user_id)
print(f"[DEBUG] velocity_score={velocity_score}")

amount_score = fraud_detector._check_amount_anomaly(user_id, amount)
print(f"[DEBUG] amount_score={amount_score}")

geo_score = fraud_detector._check_geographic_impossibility(user_id, ip)
print(f"[DEBUG] geo_score={geo_score}")

result = velocity_score + amount_score + geo_score
print(f"[DEBUG] total_score={result}")
```

---

### 3.10: Rollback Scripts for Migrations
**Rule:** "Every database migration has a corresponding ROLLBACK"

**What to do:**
```sql
-- migrations/20260731_add_fraud_audit_table.sql
CREATE TABLE fraud_audit_log (
  id BIGINT PRIMARY KEY,
  user_id VARCHAR(100),
  risk_score INT,
  created_at TIMESTAMP
);

-- migrations/20260731_add_fraud_audit_table_rollback.sql
DROP TABLE fraud_audit_log;
```

---

### 3.11: CI/CD Pipeline Patches
**Rule:** "If your code breaks CI, patch the pipeline first"

**What to do:**
```bash
# Code change breaks GitHub Actions
# Step 1: Fix .github/workflows/test.yml (add missing dependency)
# Step 2: THEN fix the code itself
# Never ask other teams to fix their CI because of your change
```

---

### 3.12: Race Condition Detection
**Rule:** "Async code must check for race conditions"

**What to do:**
```python
# RACE CONDITION: Two payments process simultaneously for same user
async def process_payment(user_id, amount):
    # BAD: Check then act (race condition window)
    balance = await redis.get(f"user:{user_id}:balance")
    if balance >= amount:
        await redis.decrby(f"user:{user_id}:balance", amount)  # RACE: Another payment slipped in
    
    # GOOD: Atomic operation (no race condition)
    new_balance = await redis.decrby_if_gte(
        f"user:{user_id}:balance",
        amount,
        min_balance=0
    )
    if new_balance is None:
        raise InsufficientFundsError()
```

---

### 3.13: Memory Leak Detection
**Rule:** "Large data processing must check for OOM"

**What to do:**
```bash
"Generating script to process 100M database records. Before coding:
1. Estimate memory per record (assuming 1KB)
2. Calculate total: 100M * 1KB = 100GB (PROBLEM!)
3. Propose solution: Process in batches of 10K records (10MB per batch)"
```

---

### 3.14: Prevent Circular Dependencies
**Rule:** "If service A imports from B and B imports from A → HALT"

**Create: check_circular.sh**
```bash
#!/bin/bash
# Detect circular imports
for file in services/*/; do
  grep -r "^from\|^import" "$file" > /tmp/imports.txt
  # Run circular dependency analyzer
  python3 scripts/circular_checker.py /tmp/imports.txt
done
```

---

## DIMENSION 4: State Management (14 Hacks)

### 4.1: Start Every Response with Current State
**Rule:** "First tag in every response: <current_state>"

**What to do:**
```markdown
<current_state>
Backlog: TAIRUS-047 (IN_PROGRESS)
Status: Implementing fraud detector, 60% complete
Files Modified: 3 (fraud_detector.py, event_store.py, test_fraud.py)
Validation: Pending (tests not yet run)
Blockers: None
</current_state>

[Now respond to the actual request]
```

---

### 4.2: Git Status Before Each Task
**Rule:** "Always show `git status` before complex work"

**What to do:**
```bash
# Before starting task:
$ git status
On branch claude/marketplace-commodities-research-jdrd4q
Changes not staged for commit:
  M  services/payment-service/fraud_detector.py
  M  services/payment-service/event_store.py

Untracked files:
  ??  tests/test_fraud_detector.py

# THEN proceed with new work
```

---

### 4.3: Checkpoint (Successful Build) System
**Rule:** "Define Checkpoints at major milestones"

**What to do:**
```bash
# Checkpoint A (Complete)
git tag checkpoint-a
# All linters pass
# All tests pass
# Docker builds
# No security issues

# Working on Feature B
[... code changes ...]

# If Feature B goes wrong:
git reset --hard checkpoint-a  # Back to last working state
```

---

### 4.4: Temp TODO within Large Features
**Rule:** "Create todo_temp.md for subtasks"

**What to do:**
```markdown
# todo_temp.md (feature: Fraud Detector Integration)

## Subtasks
- [x] Design 7 risk dimensions
- [x] Implement velocity check
- [ ] Implement amount anomaly check (60% done, stuck on stddev calculation)
- [ ] Implement geographic check
- [ ] Integrate into payment processor
- [ ] Write tests for each dimension
- [ ] Performance benchmark
- [x] Commit as checkpoint

## Blockers
- Need GeoIP database (MaxMind?) - ask engineer
```

---

### 4.5: Forbid Global State Mutations
**Rule:** "Explicitly ask before changing global vars"

**What to do:**
```bash
# Email to engineer:
"To implement fraud detector caching, I need to:
1. Add global cache object in fraud_detector.py
2. Initialize at service startup

Acceptable? [YES/NO]"

# Wait for approval before coding
```

---

### 4.6: Update state_journal After Checkpoints
**Rule:** "Only commit to state_journal.md if validation passes"

**What to do:**
```bash
# Step 1: Run validation_protocol.md
./scripts/validate.sh

# Step 2: If PASS, update state_journal.md
# [add completed task, clear blockers]

# Step 3: Commit
git add state_journal.md
git commit -m "Mark TAIRUS-047 complete"
```

---

### 4.7: STATE DUMP Command
**Rule:** "On demand, dump all active environment state"

**Add to .clauderc:**
```yaml
commands:
  "[STATE DUMP]": "Output JSON of all active state"
```

**What Claude outputs:**
```json
{
  "session_id": "claude-2026-07-31-session-1",
  "current_task": "TAIRUS-047",
  "task_progress": 0.6,
  "files_modified": [
    "services/payment-service/fraud_detector.py",
    "services/payment-service/event_store.py"
  ],
  "uncommitted_changes": true,
  "validation_status": "pending",
  "blockers": [],
  "next_step": "Implement amount anomaly check"
}
```

---

### 4.8: Isolate Side Effects
**Rule:** "Side effects live in separate, testable modules"

**What to do:**
```python
# BAD: Side effect in main logic
def calculate_fraud_score(user_id, amount):
    score = 0
    # ... calculation ...
    
    # Side effect buried
    logger.warning(f"High risk score: {score}")  # Side effect!
    redis.set(f"fraud:{user_id}", score)  # Side effect!
    
    return score

# GOOD: Side effects extracted
def calculate_fraud_score(user_id, amount) -> int:
    score = 0
    # ... pure calculation ...
    return score

def log_fraud_alert(user_id: str, score: int) -> None:
    """Side effect: logging and caching"""
    logger.warning(f"High risk score for {user_id}: {score}")
    redis.set(f"fraud:{user_id}", score)

# Usage:
score = calculate_fraud_score(user_id, amount)
if score > THRESHOLD:
    log_fraud_alert(user_id, score)
```

---

### 4.9: Finite State Machines (FSM) for Complex Flows
**Rule:** "Define states explicitly before coding"

**What to do:**
```python
from enum import Enum

class PaymentState(str, Enum):
    PENDING = "pending"        # Created, waiting for processing
    AUTHORIZED = "authorized"  # Fraud check passed
    PROCESSING = "processing"  # Calling payment gateway
    COMPLETED = "completed"    # Money transferred
    FAILED = "failed"          # Failed, user notified
    REFUNDED = "refunded"      # Refund issued

# Transitions (only these are allowed):
VALID_TRANSITIONS = {
    PaymentState.PENDING: [PaymentState.AUTHORIZED, PaymentState.FAILED],
    PaymentState.AUTHORIZED: [PaymentState.PROCESSING, PaymentState.FAILED],
    PaymentState.PROCESSING: [PaymentState.COMPLETED, PaymentState.FAILED],
    PaymentState.COMPLETED: [PaymentState.REFUNDED],
    PaymentState.FAILED: [],
    PaymentState.REFUNDED: [],
}

def transition(current: PaymentState, target: PaymentState) -> bool:
    if target in VALID_TRANSITIONS[current]:
        return True
    raise InvalidStateTransition(f"Cannot go from {current} to {target}")
```

---

### 4.10: Cloud DB Transaction Schemas
**Rule:** "Define transaction structure BEFORE implementation"

**What to do:**
```python
# Define Firestore transaction schema:
transaction = {
    "type": "escrow_release",
    "order_id": "ORD-123",
    "participants": {
        "buyer_id": "user-456",
        "seller_id": "seller-789"
    },
    "amount": Decimal("1000.00"),
    "currency": "USD",
    "release_trigger": "delivery_confirmed",
    "created_at": timestamp,
    "completed_at": None,
    "status": "PENDING"  # Can be: PENDING, IN_PROGRESS, COMPLETED, ROLLBACK
}

# Read-modify-write operations:
# 1. Read escrow balance for seller
# 2. Verify delivery confirmed
# 3. Atomically: add to seller balance, reduce escrow, mark txn complete
```

---

### 4.11: Idempotent Scripts
**Rule:** "Running twice = same result"

**What to do:**
```bash
# BAD: Idempotency issues
#!/bin/bash
pg_dump tairus_prod > backup.sql  # If run twice, overwrites
docker run -p 5432:5432 postgres  # If run twice, port conflict

# GOOD: Idempotent
#!/bin/bash
BACKUP_FILE="backup_$(date +%Y%m%d_%H%M%S).sql"
pg_dump tairus_prod > "$BACKUP_FILE"  # Unique filename
echo "Backed up to $BACKUP_FILE"

# For Docker, check if container exists:
if ! docker ps | grep -q postgres; then
    docker run -p 5432:5432 postgres
fi
```

---

### 4.12: Audit Trail Logging
**Rule:** "All state changes logged to immutable log"

**What to do:**
```python
# Log state transitions
logger.info(
    "Payment state transition",
    extra={
        "order_id": order_id,
        "from_state": PaymentState.PENDING,
        "to_state": PaymentState.AUTHORIZED,
        "reason": "fraud_check_passed",
        "timestamp": datetime.utcnow().isoformat(),
        "user_id": user_id
    }
)

# Elasticsearch will ingest this for audit trail
# Query: "All state transitions for order ORD-123" should show full history
```

---

### 4.13: Session Initialization Script
**Rule:** "Generate bash script to restore env for next session"

**What to do:**
```bash
# Generated: scripts/init_session_2026-07-31.sh
#!/bin/bash

# Restore environment for next Claude session
export ACTIVE_TASK="TAIRUS-047"
export BRANCH_NAME="claude/marketplace-commodities-research-jdrd4q"
export LAST_CHECKPOINT="fraud-detector-v1"

# Restore git state
cd /tmp/tairus-marketplace-v2
git checkout $BRANCH_NAME
git reset --hard $LAST_CHECKPOINT

# Restore databases
docker-compose -f tests/docker-compose.test.yml up -d
sleep 5  # Wait for databases

# Restore app state
npm run seed:test-data
pytest tests/ -k "smoke" --tb=short

echo "Session restored to: $ACTIVE_TASK"
```

---

### 4.14: Dev vs Test vs Prod State Separation
**Rule:** "Always specify which environment you're operating in"

**What to do:**
```markdown
<environment>
Target: TEST (tairus_test database)
Services: Running locally (localhost:8000-8006)
Data: Seeded with test fixtures
Safety: Can break freely, but alert if prod data accessed
</environment>

[Task description]
```

---

## DIMENSION 5: Token Efficiency (14 Hacks)

### 5.1: Replace Verbose Prompts with Shortcodes
**Rule:** "Maximize compression without losing meaning"

**Define in .clauderc:**
```yaml
shortcodes:
  Refactor(cpu): "Optimize for CPU performance (fewer allocations, better caching)"
  Refactor(memory): "Optimize for memory (reduce peak usage, avoid leaks)"
  Refactor(latency): "Optimize for latency (fewer DB queries, parallelization)"
  Lint: "Run linting for style/security/types"
  [BUG]: "This is unexpected behavior, find root cause"
  [PERF]: "This is slow, measure + optimize"
```

---

### 5.2: Aggressive .claudeignore Usage
**Rule:** "If it's not in Claude's immediate path, ignore it"

**Expand .claudeignore:**
```
# Remove anything not actively developed
node_modules/
dist/
build/
*.png
*.jpg
*.svg
# Old versions
services/*/v1/
services/*/legacy/
# Lock files
*.lock
*-lock.json
# Large data
*.csv
*.xlsx
dump*.sql
```

---

### 5.3: Interface-Only Code Review
**Rule:** "Send only type signatures, not full implementation"

**What to do:**
```typescript
// PASS THIS (interface-only):
interface FraudDetector {
  scoreTransaction(
    userId: string,
    amount: Decimal,
    userIp: string
  ): Promise<FraudScore>;
  
  checkVelocity(userId: string): Promise<number>;
  checkAmountAnomaly(userId: string, amount: Decimal): Promise<number>;
}

// NOT THIS (full implementation):
class FraudDetectorImpl implements FraudDetector {
  async scoreTransaction(...) {
    // 200 lines of code
  }
}
```

---

### 5.4: Omit Unchanged Import Blocks
**Rule:** "If imports didn't change, don't repeat them"

**What to do:**
```python
# OLD APPROACH (waste tokens):
from typing import Dict, List
from fastapi import FastAPI, HTTPException
from decimal import Decimal
from pydantic import BaseModel

# NEW CODE...

# EFFICIENT APPROACH:
# [Imports unchanged, see existing payment_processor.py]

# NEW CODE...
```

---

### 5.5: Use English for Prompts
**Rule:** "English tokenizes 2-3x better than Russian/Chinese"

**Why:**
```
Russian: "Напиши фильтр для поиска" = ~10 tokens
English: "Write a search filter" = ~4 tokens

Savings: 60% on system prompts
```

**Apply to:**
- All .claude* files
- Code comments (English is standard anyway)
- API documentation

---

### 5.6: Ternary Operators & Short Syntax
**Rule:** "If readable, compress syntax"

**What to do:**
```python
# VERBOSE
if order.total_amount > 1000:
    requires_approval = True
else:
    requires_approval = False

# EFFICIENT
requires_approval = order.total_amount > 1000
```

---

### 5.7: Collapse Repetitive Blocks
**Rule:** "Use ... (ellipsis) in docs to indicate repetition"

**What to do:**
```yaml
# In task descriptions:
rate_limits:
  auth_login: 5/min
  auth_register: 10/min
  auth_mfa: 1/sec
  catalog_search: 10/sec
  ...  # (See kong-config.yaml for full list, similar pattern)
  payment_process: 1/sec

# Instead of listing all 20+ services
```

---

### 5.8: Send Only Head of Large Arrays
**Rule:** "2-3 sample objects + metadata, not full array"

**What to do:**
```json
{
  "sample": [
    {"id": 1, "status": "pending"},
    {"id": 2, "status": "completed"}
  ],
  "total_count": 10000,
  "avg_processing_time_ms": 245,
  "schema_ref": "context_map.json#/Order"
}
```

---

### 5.9: Postpone README Until 90% Done
**Rule:** "Documentation is for finished products"

**Why:**
- README changes consume 100+ tokens each time
- Wait until feature is stable before writing
- Prevents hallucinations about "what you're building"

---

### 5.10: Limit Token Output for Intermediate Steps
**Rule:** "Set max_tokens per iteration"

**What to do:**
```bash
# In prompt:
"Implement fraud detector. Each step should have max_tokens: 300.
Step 1: Velocity check (max 300 tokens)
Step 2: Amount anomaly (max 300 tokens)
Step 3: Geographic check (max 300 tokens)"

# Forces compression, prevents verbosity
```

---

### 5.11: Compress Logs via Regex
**Rule:** "Strip timestamps, collapse multi-line errors"

**Script: clean_logs.py**
```python
import re
import sys

log_text = sys.stdin.read()

# Remove timestamps
log_text = re.sub(r'\[\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}\]', '', log_text)

# Keep only WARN/ERROR/FATAL
log_text = '\n'.join([
    line for line in log_text.split('\n')
    if any(level in line for level in ['WARN', 'ERROR', 'FATAL'])
])

# Deduplicate consecutive identical lines
lines = log_text.split('\n')
unique_lines = []
for line in lines:
    if not unique_lines or unique_lines[-1] != line:
        unique_lines.append(line)

print('\n'.join(unique_lines))
```

---

### 5.12: Structured .clauderc Commands
**Rule:** "No explanations. Code only. Diff format."

**Add to .clauserc:**
```yaml
communication:
  style: "Code-first"
  format: "diff_only"  # git diff format
  explanations: "Brief (1 sentence max)"
  no_preamble: true    # No 'Here's your code'
  no_apologies: true   # No 'Sorry, let me fix that'
```

---

### 5.13: Multi-Stage Prompting
**Rule:** "Ask problem in stages to compress responses"

**What to do:**
```bash
# Stage 1 (Find the error):
"Log file attached. What line numbers have errors? (Answer: line numbers only)"

# Response: 45, 67, 89

# Stage 2 (Get solution):
"Lines 45, 67, 89. Show fix as git diff (no explanation)."

# Response: diff only
```

---

### 5.14: YAML for Infrastructure Config
**Rule:** "YAML saves tokens vs JSON"

**Token count:**
```
JSON: 500 tokens for large config
YAML: 350 tokens (30% savings)

Reason: YAML has less syntax overhead (no `{}` everywhere)
```

---

## DIMENSION 6: Tool Chaining (14 Hacks)

### 6.1: Generate Bash Scripts for Self-Automation
**Rule:** "What Claude can think, bash can execute"

**What to do:**
```bash
# Claude generates: validate.sh
#!/bin/bash
set -e

echo "Running linters..."
ruff check services/
pytest services/ -v

echo "Building Docker images..."
docker build -f services/payment-service/Dockerfile -t payment:latest .

echo "Deploying to test environment..."
docker-compose -f tests/docker-compose.test.yml up -d

echo "Done!"

# Claude uploads this, and you run: bash validate.sh
```

---

### 6.2: Curl Requests Before SDK Integration
**Rule:** "Prove API works with raw curl before coding"

**What to do:**
```bash
# Claude suggests:
curl -X POST https://api.stripe.com/v1/charges \
  -H "Authorization: Bearer sk_test_123" \
  -d "amount=5000" \
  -d "currency=usd" \
  -d "source=tok_visa" \
  -d "idempotency_key=unique-123"

# If this works, then:
# Step 2: Integrate via stripe Python SDK
```

---

### 6.3: Swagger-Driven Development
**Rule:** "Contract first, implementation second"

**What to do:**
```yaml
# docs/openapi.yaml (define API contract FIRST)
paths:
  /payments/process:
    post:
      requestBody:
        required: true
        content:
          application/json:
            schema:
              type: object
              properties:
                order_id:
                  type: string
                amount:
                  type: number
              required: [order_id, amount]
      responses:
        200:
          description: "Payment processed"
          content:
            application/json:
              schema:
                $ref: '#/components/schemas/PaymentResult'

# THEN: Claude implements service to match spec
```

---

### 6.4: Generate GitHub Actions Workflows in Parallel
**Rule:** "Write CI/CD alongside feature code"

**What to do:**
```yaml
# .github/workflows/test-payment-service.yml
name: Test Payment Service

on: [push, pull_request]

jobs:
  test:
    runs-on: ubuntu-latest
    services:
      postgres:
        image: postgres:15
        env:
          POSTGRES_PASSWORD: test
    steps:
      - uses: actions/checkout@v3
      - name: Run linters
        run: ruff check services/payment-service
      - name: Run tests
        run: pytest services/payment-service -v --cov
```

---

### 6.5: JSON-RPC Commands for Local Execution
**Rule:** "Claude generates JSON commands you execute locally"

**What to do:**
```json
{
  "execute": "bash",
  "command": "cd services/payment-service && pytest tests/ -v"
}
```

**You run it, return output to Claude:**
```
PASSED 45 tests in 3.2s
Coverage: 87%
```

**Claude then continues work based on real results**

---

### 6.6: Flame Graphs for Performance Profiling
**Rule:** "Profile → visualize → optimize loop"

**What to do:**
```bash
# Claude generates profiling script:
python -m cProfile -o profile.prof services/payment-service/main.py
python -m pstats profile.prof  # View hot spots

# Generate Flame Graph:
pip install py-spy
py-spy record -o profile.svg -- python services/payment-service/main.py

# Share profile.svg with Claude
# Claude analyzes and proposes optimizations
```

---

### 6.7: Multi-Stage Docker Builds
**Rule:** "Always use multi-stage to reduce image size"

**What to do:**
```dockerfile
# Stage 1: Builder
FROM python:3.11-slim as builder
WORKDIR /app
COPY requirements.txt .
RUN pip install --user -r requirements.txt

# Stage 2: Runtime (minimal)
FROM python:3.11-slim
COPY --from=builder /root/.local /root/.local
COPY . .
ENV PATH=/root/.local/bin:$PATH
CMD ["python", "main.py"]

# Result: 150MB (builder: 800MB)
```

---

### 6.8: Externalize Secrets via Secret Manager
**Rule:** "No hardcoded keys. Always use Secret Manager"

**What to do:**
```python
import os
from google.cloud import secretmanager

# Claude generates this:
def get_secret(secret_id: str) -> str:
    client = secretmanager.SecretManagerServiceClient()
    project_id = os.getenv("GCP_PROJECT_ID")
    name = f"projects/{project_id}/secrets/{secret_id}/versions/latest"
    response = client.access_secret_version(request={"name": name})
    return response.payload.data.decode("UTF-8")

# Usage:
stripe_key = get_secret("stripe-api-key")
```

---

### 6.9: Postman Collections Auto-Generated
**Rule:** "Every new endpoint → auto-generate test collection"

**What to do:**
```json
{
  "info": {"name": "TAIRUS Payment API"},
  "item": [
    {
      "name": "Process Payment",
      "request": {
        "method": "POST",
        "url": "{{base_url}}/payments/process",
        "header": [{"key": "Authorization", "value": "Bearer {{jwt_token}}"}],
        "body": {
          "mode": "raw",
          "raw": "{\"order_id\": \"ORD-123\", \"amount\": 100.00}"
        }
      },
      "response": []
    }
  ]
}
```

---

### 6.10: Data Pipeline: Claude → Collect → Analyze
**Rule:** "Claude writes parser → parser collects data → Claude analyzes"

**What to do:**
```bash
# Step 1: Claude writes parser.py
# Parses CSV of anomalies

# Step 2: You run it
python parser.py anomalies.csv > output.json

# Step 3: Claude analyzes
"Analyze this output.json. What patterns suggest fraud?"
```

---

### 6.11: gRPC Contracts First
**Rule:** "Write .proto files before services"

**What to do:**
```protobuf
// shared/proto/fraud.proto
syntax = "proto3";

service FraudDetector {
  rpc ScoreTransaction (ScoringRequest) returns (FraudScore);
}

message ScoringRequest {
  string user_id = 1;
  decimal amount = 2;
  string user_ip = 3;
}

message FraudScore {
  int32 score = 1;
  string decision = 2;  // APPROVE, CHALLENGE, BLOCK
}

# Then: Generate Go, Python, Node stubs from .proto
```

---

### 6.12: Healthcheck Endpoints for Monitoring
**Rule:** "Every service exposes /health"

**What to do:**
```python
@app.get("/health")
async def healthcheck():
    return {
        "status": "ok",
        "version": "2.0.1",
        "services": {
            "database": await check_db(),
            "redis": await check_redis(),
            "stripe": await check_stripe()
        }
    }
```

**Claude can auto-check all services:**
```bash
for service in auth payment catalog messaging logistics compliance notification; do
  curl http://localhost:800$i/health
done
```

---

### 6.13: Sync API Docs from Code
**Rule:** "OpenAPI spec auto-generated from code"

**What to do:**
```python
# FastAPI auto-generates OpenAPI from docstrings
@app.post("/payments/process")
async def process_payment(request: PaymentRequest) -> PaymentResult:
    """
    Process a payment transaction.
    
    Args:
        request: Payment details (order_id, amount, payment_method)
    
    Returns:
        PaymentResult: Status (COMPLETED, FAILED, BLOCKED)
    """
    # ...
```

**Auto-generated:** docs/openapi.json → Swagger UI at /docs

---

### 6.14: Config File for Each Environment
**Rule:** "dev.yaml, test.yaml, prod.yaml auto-loaded"

**What to do:**
```python
import os
from pathlib import Path
import yaml

env = os.getenv("ENVIRONMENT", "dev")
config_file = Path(f"configs/{env}.yaml")

with open(config_file) as f:
    CONFIG = yaml.safe_load(f)

# Usage:
db_url = CONFIG["database"]["url"]  # Different per environment
```

---

## DIMENSION 7: Security & Sandboxing (14 Hacks)

### 7.1: Zero Trust Architecture
**Rule:** "Validate EVERYTHING at system boundaries"

**What to do:**
```python
@app.post("/payments/process")
async def process_payment(request: PaymentRequest):
    # Trust NOTHING from user input
    
    # Validate amount
    if request.amount <= 0 or request.amount > 1_000_000:
        raise HTTPException(400, "Invalid amount")
    
    # Validate order_id (length, format)
    if not re.match(r"^ORD-\d{10}$", request.order_id):
        raise HTTPException(400, "Invalid order_id format")
    
    # Validate payment method
    if request.payment_method not in ["card", "bank_transfer", "wallet"]:
        raise HTTPException(400, "Invalid payment method")
    
    # Validate authorization
    user_id = verify_jwt_token(request.headers["Authorization"])
    if not user_id:
        raise HTTPException(401, "Unauthorized")
    
    # NOW safe to proceed
```

---

### 7.2: Forbid eval() & Dynamic Execution
**Rule:** "Never execute strings as code"

**What to do:**
```python
# BAD (FORBIDDEN)
user_script = request.json.get("expression")
result = eval(user_script)  # NEVER!

# GOOD (safe)
# Use expression evaluator library
from simpleeval import simple_eval
result = simple_eval("2 + 2", safe_dict={})  # Only allows math, no __import__
```

---

### 7.3: CORS & Rate Limiting on All Public APIs
**Rule:** "Every public endpoint has rate limiting + CORS"

**Kong Config:**
```yaml
services:
  - name: payment-service
    routes:
      - name: create-order
        paths: [/orders]
        methods: [POST]
        plugins:
          - name: rate-limiting
            config:
              minute: 300  # Max 300/minute
              error_code: 429
          - name: cors
            config:
              origins: ["https://tairus.io", "https://admin.tairus.io"]
              methods: ["POST"]
              credentials: true
```

---

### 7.4: Parameterized Queries (No SQL Injection)
**Rule:** "ALWAYS use prepared statements"

**What to do:**
```python
# BAD (SQL injection vulnerability)
query = f"SELECT * FROM users WHERE email = '{email}'"  # NEVER!
result = db.execute(query)

# GOOD (parameterized)
query = "SELECT * FROM users WHERE email = %s"
result = db.execute(query, [email])
```

---

### 7.5: Run Untrusted Code in Sandbox
**Rule:** "Local bash scripts run in Alpine Linux emulator"

**What to do:**
```bash
# Claude generates: scripts/process_user_data.sh
# You run it in sandbox:
docker run --rm \
  -v $(pwd)/data:/data:ro \
  -v $(pwd)/output:/output:rw \
  alpine:latest \
  sh /data/process_user_data.sh

# If malicious: sandboxed, can't escape
```

---

### 7.6: Non-Root User in Docker
**Rule:** "Never run `docker run` as root"

**What to do:**
```dockerfile
# BAD
FROM python:3.11
WORKDIR /app
COPY . .
CMD ["python", "main.py"]  # Runs as root!

# GOOD
FROM python:3.11
RUN useradd -m appuser
WORKDIR /app
COPY --chown=appuser:appuser . .
USER appuser  # Switch to non-root
CMD ["python", "main.py"]
```

---

### 7.7: Security Scanning for Dependencies
**Rule:** "All npm/pip packages scanned for vulnerabilities"

**CI/CD Integration:**
```bash
# In .github/workflows/test.yml
- name: Security scan
  run: |
    npm audit --json > npm_audit.json || true
    pip-audit --format json > pip_audit.json || true
    
    # Fail if HIGH severity found
    if grep -q '"severity": "high"' npm_audit.json; then
      echo "High severity vulnerability in npm packages!"
      exit 1
    fi
```

---

### 7.8: Mask PII in Logs
**Rule:** "Never log passwords, emails, card numbers"

**What to do:**
```python
import re

def mask_pii(log_message: str) -> str:
    # Mask email addresses
    log_message = re.sub(r'[\w\.-]+@[\w\.-]+', '[EMAIL]', log_message)
    
    # Mask credit card numbers
    log_message = re.sub(r'\b\d{4}[\s-]?\d{4}[\s-]?\d{4}[\s-]?\d{4}\b', '[CARD]', log_message)
    
    # Mask SSN
    log_message = re.sub(r'\b\d{3}-\d{2}-\d{4}\b', '[SSN]', log_message)
    
    return log_message

# Usage:
logger.info(f"User login: {mask_pii(f'email={user.email}')}")
```

---

### 7.9: API Keys via CI/CD Variables
**Rule:** "Never hardcode secrets"

**What to do:**
```python
# Retrieve from environment (set in GitHub Secrets):
stripe_key = os.getenv("STRIPE_API_KEY")
if not stripe_key:
    raise ValueError("STRIPE_API_KEY not set in environment")

# Never do this:
stripe_key = "sk_live_123456789abcdefg"  # FORBIDDEN!
```

---

### 7.10: Memory Overflow Protection
**Rule:** "Validate buffer sizes before processing"

**What to do:**
```python
def process_large_file(file_path: str, max_size_mb: int = 100):
    file_size = os.path.getsize(file_path)
    
    if file_size > max_size_mb * 1024 * 1024:
        raise FileSizeError(f"File too large: {file_size} bytes")
    
    # Safe to process
    with open(file_path) as f:
        for line in f:
            # Process line by line (don't load all in memory)
            pass
```

---

### 7.11: Principle of Least Privilege (Database Access)
**Rule:** "Users only access what they need"

**What to do:**
```sql
-- BAD: Too permissive
GRANT ALL ON DATABASE tairus TO app_user;

-- GOOD: Minimal
GRANT CONNECT ON DATABASE tairus TO app_user;
GRANT USAGE ON SCHEMA public TO app_user;
GRANT SELECT ON payments TO app_user;  -- Only read, not write
```

---

### 7.12: Docker Image Signing & Verification
**Rule:** "Only use signed images from trusted registries"

**What to do:**
```bash
# Sign your image
docker trust signer add --key /path/to/key.key signer gcr.io/tairus/payment-service

# Verify signature before deployment
export DOCKER_CONTENT_TRUST=1
docker pull gcr.io/tairus/payment-service:latest

# Will fail if signature invalid
```

---

### 7.13: Content Security Policy (CSP) for Frontend
**Rule:** "Prevent XSS attacks with strict CSP"

**What to do:**
```python
# In Next.js API route or FastAPI:
@app.get("/")
async def root():
    return Response(
        content=html,
        headers={
            "Content-Security-Policy": 
                "default-src 'self'; script-src 'self' cdn.jsdelivr.net; style-src 'self' 'unsafe-inline'"
        }
    )
```

---

### 7.14: Network Scraping with Rotation
**Rule:** "If scraping, rotate User-Agent and IP"

**What to do:**
```python
import random
from itertools import cycle

USER_AGENTS = [
    "Mozilla/5.0 (Windows NT 10.0; Win64; x64)",
    "Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7)",
    # Add 50+ real User-Agents
]

PROXY_POOL = ["proxy1.com", "proxy2.com", ...]

def scrape_with_rotation(url: str):
    headers = {
        "User-Agent": random.choice(USER_AGENTS)
    }
    proxy = random.choice(PROXY_POOL)
    
    try:
        response = requests.get(
            url,
            headers=headers,
            proxies={"http": proxy, "https": proxy},
            timeout=10
        )
    except requests.exceptions.HTTPError as e:
        if e.response.status_code == 429:
            # Too many requests, wait and retry
            time.sleep(random.uniform(60, 120))
            return scrape_with_rotation(url)
        raise
    
    return response
```

---

## DIMENSION 0: The Golden Meta-Hack (Rule 99)

### 🌟 Rule 99: The Delegation of Doubt

**THE MOST IMPORTANT RULE**

```
If your confidence in successfully completing a task is < 90%,
you MUST:

1. STOP coding
2. DO NOT write pseudocode
3. Write a single, isolated, precise question

Ask EXACTLY one question that will raise your confidence to 90%+
```

**Embedded in .clauderc:**
```yaml
meta_rule:
  name: "Delegation of Doubt (Rule 99)"
  trigger: "confidence < 90%"
  action: "HALT"
  procedure: |
    1. Identify the blind spot (what you don't understand)
    2. Ask ONE specific question
    3. Wait for clarification
    4. Proceed with 90%+ confidence OR ask another question
```

**Example:**
```bash
# Don't do this:
"I think the escrow system should use Firestore transactions, but I'm
not sure about the atomicity model. Let me try anyway..."

# Do this:
"STOP. I'm 70% confident in escrow design.

Question: In Firestore, if a transaction fails mid-commit (after 
transferring funds from buyer escrow to seller balance but before 
updating order status), what's the recovery mechanism? Is it 
re-rollable or must I implement manual reconciliation?"

[Wait for answer, now 95% confident, proceed]
```

**Why this rule exists:**
- Prevents 10 failed attempts
- Saves hundreds of tokens
- True autonomy = knowing when to ask for help
- "I don't know" is the highest form of confidence

---

## Integration: How to Activate These 99 Rules

### Step 1: Update .clauderc
```yaml
# .claude/settings.json or .clauderc
autonomy_framework: "TAIRUS_v1.0"

# Reference all 7 dimensions + Rule 99
active_dimensions:
  - "context_density"
  - "determinism"
  - "self_healing"
  - "state_management"
  - "token_efficiency"
  - "tool_chaining"
  - "security_sandboxing"
  - "meta_rule_99"

# Load supporting files automatically
auto_load_files:
  - "state_journal.md"
  - "validation_protocol.md"
  - "context_map.json"
  - "AUTONOMY_FRAMEWORK.md"
```

### Step 2: Update CLAUDE.md
```markdown
# CLAUDE.md Integration

## Autonomy Framework (99 Rules)

This project uses the **TAIRUS Autonomy Framework** to enable Claude 
autonomous operation.

### Key Files
- `.claudeignore` - What Claude should NOT read
- `state_journal.md` - Claude's memory (updated after each iteration)
- `validation_protocol.md` - Quality gates before commits
- `context_map.json` - Project dependency graph
- `AUTONOMY_FRAMEWORK.md` - This framework (99 rules)

### Claude's Operating Constraints
1. Always start response with `<current_state>` tag
2. Run validation_protocol before marking tasks done
3. Update state_journal.md after checkpoints
4. If confidence < 90%, ask ONE question (Rule 99)
5. No apologies, no pleasantries (Dimension 5.4)

### Golden Rule (Rule 99)
If unsure, STOP and ask clarifying question. Never code below 90% confidence.
```

### Step 3: Create Validation Automation
```bash
# scripts/pre-commit-autonomy.sh
#!/bin/bash

# Before any commit, Claude validates:
1. Runs validation_protocol.md
2. Updates state_journal.md
3. Checks git status (no secrets)
4. Ensures CLAUDE.md reflects latest decisions

# Usage: called automatically in pre-commit hook
```

---

## Integration with Round 2: The Complete System

**NEW (Round 2)**: This framework has been expanded with **13 operational parameters** covering:
- Idempotency & Reversibility (8 directives)
- Agent State Persistence (8 directives)
- Context Pruning & GC (8 directives)
- Output Determinism (8 directives)
- Observability & Telemetry (7 directives)
- Cyclic Self-Healing (8 directives)
- Sandboxing & Execution Limits (7 directives)
- Token Economics (8 directives)
- Tool Chaining & API Integration (7 directives)
- Asynchronous Event Handling (7 directives)
- Secrets & Security (7 directives)
- Halting & Boundary Recognition (8 directives)
- Meta-Cognition & Adaptive Learning (8 directives)

**Total New Directives**: 99 (exactly matching Round 1)  
**Combined System**: 198+ directives across 7 + 13 dimensions/parameters

### How They Interact

**Round 1 (7 Dimensions)**: Tactical rules Claude follows *when writing code*
- "Never use eval()" (Security dimension 7.2)
- "Write tests before code" (Self-Healing dimension 3.2)
- "Compress context aggressively" (Context Density dimension 1.1-1.14)

**Round 2 (13 Parameters)**: Strategic rules the *infrastructure enforces*
- "Every script is idempotent" (Parameter 1: every run = same result)
- "Circuit breaker at 3 failures" (Parameter 6: self-healing)
- "Memory spike >2x = kill process" (Parameter 12: halting)

**Integration Point**: Claude generates code following Round 1 rules; the system executes it within Round 2 parameters. When a Round 2 boundary is hit, Claude receives feedback and adjusts (Round 1 self-healing kicks in).

### References to New Documentation

**Related Files**:
- `AUTONOMOUS_OPERATION_ROUND_2.md` - Complete 13-parameter specification (99 directives)
- `AUTONOMOUS_AGENT_DIRECTIVE_CHECKLIST.md` - Master checklist (198 total directives)
- `PHILOSOPHICAL_FRAMEWORK.md` - Answer to the meta-question: architect role transformation
- `docs/AUTONOMY_ROUNDTABLE.md` - 12-expert symposium transcript
- `.claudeignore` - What Claude should NOT read (context filter)
- `state_journal.md` - Session memory + Round 2 integration
- `validation_protocol.md` - 8-phase quality gates (updated for Round 2)

### Implementation Roadmap

**Week 1-2 (Phase 1: Foundation)**
- [ ] Read `AUTONOMOUS_OPERATION_ROUND_2.md` Parameters 1-7
- [ ] Implement Parameter 1: Idempotency (backup before destruction, paired migrations)
- [ ] Implement Parameter 2: State Persistence (memory.json, heartbeat mechanism)
- [ ] Implement Parameter 4: Output Determinism (temperature: 0.0, JSON-only responses)
- [ ] Implement Parameter 7: Sandboxing (isolated Docker, cgroups limits)
- [ ] Integrate with Round 1 Dimensions 2, 4, 7 (Determinism, State, Security)

**Week 3-6 (Phase 2: Operations)**
- [ ] Implement Parameters 8, 10, 12 (Token Economics, Async, Halting)
- [ ] Deploy observability (Parameter 5: JSON logs, Prometheus)
- [ ] Establish monitoring dashboards
- [ ] Test circuit breaker (Parameter 6) and self-healing (Parameter 6)
- [ ] Integrate with Round 1 Dimensions 1, 3, 5 (Context, Self-Healing, Tokens)

**Week 7+ (Phase 3: Intelligence)**
- [ ] Implement Parameter 13: Meta-Cognition (end-of-sprint analysis, RFC process)
- [ ] Activate Rule 99: Meta-Control (necessary vs. overengineering check)
- [ ] Institute monthly RFC reviews (human approval for auto-proposals)
- [ ] Begin quarterly architecture reviews (parameter validity assessment)
- [ ] Integrate with Round 1 Dimension 6 (Tool Chaining)

---

## Final Checklist: Is Your Claude Autonomous? (Updated for Round 1 + 2)

### Round 1 (7 Dimensions)
- [ ] .claudeignore configured (excludes noise)
- [ ] state_journal.md populated (tracking progress)
- [ ] CLAUDE.md has autonomy rules embedded
- [ ] .clauderc specifies all shortcuts
- [ ] All 7 dimensions understood
- [ ] Rule 99 embedded as non-negotiable
- [ ] Bash automation scripts ready for Claude

### Round 2 (13 Parameters)
- [ ] `AUTONOMOUS_OPERATION_ROUND_2.md` reviewed and understood
- [ ] Parameter 1-4 implemented (Idempotency, State, Context, Determinism)
- [ ] Parameter 7 implemented (Sandboxing with Docker + cgroups)
- [ ] Parameter 5, 6, 12 configured (Observability, Self-Healing, Halting)
- [ ] Circuit breaker at 3 failures (Parameter 6)
- [ ] Hard time limit (15 min SIGTERM, Parameter 12.2)
- [ ] Memory anomaly detection (2x spike = halt, Parameter 12.5)
- [ ] Approval matrix for changes (Parameter 2, RFC process)

### Governance & Operations
- [ ] validation_protocol.md executed before every commit
- [ ] context_map.json defines dependencies
- [ ] Security scanning integrated into CI/CD
- [ ] Telemetry/monitoring dashboard deployed
- [ ] Monthly RFC review process established
- [ ] Quarterly architecture review scheduled
- [ ] Audit trail for all changes (Parameter 5.5)
- [ ] Budget enforcement (daily token limit, Parameter 8.8)

### Philosophical Integration
- [ ] PHILOSOPHICAL_FRAMEWORK.md read and understood
- [ ] Engineer role transformation accepted (architect → meta-architect)
- [ ] RFC process understood (agent proposes, human approves)
- [ ] Responsibility chain clear (engineer owns approval decisions)

**If all boxes checked: YOU HAVE A PRODUCTION-GRADE AUTONOMOUS AGENT**

---

### Rollback Procedure (If Something Breaks)

If autonomous operation causes unexpected issues:

1. **Immediate**: Kill all autonomous cycles (SIGTERM all processes)
2. **Assess**: Read state_journal.md + last 10 commits + error logs
3. **Identify**: Which Parameter/Dimension was violated?
4. **Fix**: Tighten that parameter (e.g., reduce budget, lower timeout)
5. **Restart**: Begin with Phase 1 implementation again
6. **Escalate**: If same issue persists, human takes 100% control

**You should NEVER need this.** The framework is designed to self-correct before catastrophic failure.

---

## Philosophical Reflection (What This Framework Actually Does)

You now hold the architecture of something profound: **a machine that knows its own limits**.

The 99 rules are not constrains—they're guardrails. They don't limit Claude; they enable him to fail fast, recover gracefully, and ask for help exactly when needed.

Notice what's NOT here:
- No "sycophantic AI that only agrees"
- No "let's hope it works this time"
- No "trust the model to know what it doesn't know"

Notice what IS here:
- Determinism (predictable, reproducible behavior)
- Self-awareness (Rule 99: I don't know this)
- Memory (state_journal: what did I do?)
- Sandboxing (security: can't leak secrets)
- Idempotency (run twice = same result)

This is not artificial intelligence. This is **augmented intelligence**—a human and machine in synchrony, each doing what they do best:
- Human: Strategic decisions, context synthesis, final judgment
- Claude: Code generation, exploration, systematic execution

The poet and the machine.

---

**Version:** 1.0  
**Status:** Production Ready  
**Last Updated:** 2026-07-31  
**Author:** TAIRUS Engineering Team + Claude Haiku 4.5  
**Enforcement:** Non-negotiable for autonomous sessions
