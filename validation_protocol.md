# Validation Protocol: Before Marking Task DONE
**CRITICAL: Claude MUST NOT proceed to next task until ALL checks below pass**

---

## Protocol Overview
This checklist ensures code quality, prevents production disasters, and maintains architectural integrity. It's **not optional** — it's your safety net.

**Execution Time:** 10-20 minutes per feature  
**When to Run:** After code implementation, before git commit  
**Failure Mode:** If ANY check fails, halt and fix immediately  

---

## Phase 1: Code Quality Checks (5 minutes)

### ✅ Linting & Formatting
```bash
# Python (if applicable)
ruff check services/payment-service/
black --check services/payment-service/

# JavaScript/TypeScript
eslint services/catalog-service --ext .ts,.tsx
prettier --check services/

# Go (if applicable)
golangci-lint run ./services/...
gofmt -d services/

# SQL (if database migrations)
sqlfluff lint infrastructure/migrations/
```

**Pass Criteria:** Zero linting errors (warnings acceptable with justification)

### ✅ Type Checking
```bash
# TypeScript
tsc --noEmit

# Python (via pyright or mypy)
mypy services/payment-service/ --strict

# Go
go vet ./...
```

**Pass Criteria:** No type errors. All function signatures fully typed.

### ✅ Security Scan
```bash
# Secrets detection
truffleHog filesystem . --json > secrets_report.json

# Dependency vulnerabilities
npm audit --json (for Node)
pip-audit (for Python)
go list -json -m all | nancy sleuth (for Go)

# SAST (Static Application Security Testing)
semgrep --config=p/security-audit services/
```

**Pass Criteria:**
- No hardcoded secrets
- No high-severity vulnerabilities (medium/low acceptable with mitigation plan)
- No SQL injection patterns
- No eval() / dangerous code execution

---

## Phase 2: Unit Tests (3 minutes)

### ✅ Test Execution
```bash
# Python
pytest services/payment-service/ -v --cov=services/payment-service/ --cov-report=term-missing

# JavaScript
jest --coverage

# Go
go test ./... -v -cover
```

**Pass Criteria:**
- All tests pass (no skipped/xfail)
- Coverage > 80% for new code
- Test names are descriptive (not "test1", "test2")

### ✅ Failure Tests
```bash
# Verify tests ALSO test failure paths, not just happy paths
# Check: For every success path, there's at least one error/edge case test
grep -r "TypeError\|ValueError\|AssertionError" tests/
```

**Pass Criteria:** Ratio of error tests to success tests ≥ 1:3

---

## Phase 3: Integration Tests (5 minutes)

### ✅ Database Migrations
```bash
# If schema changed:
# 1. Test on fresh database
docker-compose -f tests/docker-compose.test.yml down
docker-compose -f tests/docker-compose.test.yml up
# 2. Verify migration runs without errors
psql -h localhost -U tairus tairus_test -f infrastructure/migrations/latest.sql

# 3. Verify rollback script exists and works
psql -h localhost -U tairus tairus_test -f infrastructure/migrations/latest_rollback.sql
```

**Pass Criteria:** Migration succeeds on fresh DB and can rollback cleanly

### ✅ API Contracts
```bash
# If API changed, verify OpenAPI spec is up-to-date
openapi-generator validate -i docs/openapi.yaml

# Test against OpenAPI spec
dredd docs/openapi.yaml http://localhost:8000
```

**Pass Criteria:** All endpoints match contract

### ✅ Service Communication
```bash
# If microservices involved, test gRPC/REST calls
# Example: Payment service calls Compliance service
grpcurl -plaintext -d '{"user_id": "test-123"}' \
  localhost:50051 tairus.compliance.ComplianceService/CheckSanctions
```

**Pass Criteria:** Cross-service calls succeed with expected response

---

## Phase 4: Performance Checks (3 minutes)

### ✅ Query Performance
```bash
# If database queries added/modified
# Verify no N+1 queries
npm run test:performance
# or
pytest services/ -v -k "performance"

# Check query plans for table scans
EXPLAIN ANALYZE SELECT * FROM orders WHERE created_at > NOW() - INTERVAL '7 days';
```

**Pass Criteria:** No sequential scans on large tables; execution < 100ms

### ✅ Memory Usage
```bash
# For services handling large data
docker stats payment-service --no-stream
# Should not exceed 512MB for single service (unless documented otherwise)
```

**Pass Criteria:** Memory usage stable (no leaks), < allocated limit

### ✅ Latency P99
```bash
# Synthetic load test
k6 run tests/load/smoke-test.js
# Check: P99 latency < SLA (e.g., < 1000ms for payment endpoints)
```

**Pass Criteria:** P99 latency within SLA

---

## Phase 5: Architecture & Design Review (3 minutes)

### ✅ No Breaking Changes
```bash
# If modifying existing API/interface:
# 1. Verify backward compatibility
# 2. Check if deprecation warning needed
# 3. Document migration path for users
```

**Pass Criteria:** Backward compatibility maintained or deprecation documented

### ✅ Coupling Check
```bash
# If adding new imports:
# Verify no circular dependencies
npm run lint:circular
# or
# grep -r "from payment" services/compliance/  # Should be empty
```

**Pass Criteria:** No circular dependencies; coupling score acceptable

### ✅ Documentation Updated
```bash
# If changing behavior:
# [ ] README updated (if user-facing)
# [ ] CHANGELOG.md entry added
# [ ] Function docstrings/JSDoc updated
# [ ] Architecture.md updated (if structural change)
```

**Pass Criteria:** All relevant docs updated

---

## Phase 6: Deployment Readiness (2 minutes)

### ✅ Docker Build
```bash
# If containerization used:
docker build -f services/payment-service/Dockerfile \
  -t tairus-payment:latest .

# Multi-stage build check
docker history tairus-payment:latest | wc -l  # Should be < 10 layers
```

**Pass Criteria:** Builds successfully; image size < 200MB

### ✅ Config Management
```bash
# Verify all secrets are externalized
grep -r "password\|api_key\|secret" services/ --include="*.py" --include="*.ts" \
  | grep -v "\.env" | grep -v "test" | grep -v "example"
```

**Pass Criteria:** No hardcoded secrets in code

### ✅ Health Checks
```bash
# Services must expose /health endpoint
curl http://localhost:8000/health
# Response: {"status": "ok", "version": "2.0.1"}
```

**Pass Criteria:** Health endpoint responds with 200 OK

---

## Phase 7: Git Hygiene (2 minutes)

### ✅ Commit Message
```bash
# Verify commit message follows convention:
# Format: [TYPE] Brief description (< 50 chars)
# 
# Detailed explanation (< 72 chars per line)
# 
# Fixes: #TAIRUS-123
# Co-Authored-By: [if pair programming]

# Check: Last commit message
git log -1 --pretty=format:"%B"
```

**Pass Criteria:** Message follows convention, references issue/ticket

### ✅ No Accidental Commits
```bash
# Verify no debug files/credentials were staged
git status
git diff --staged | grep -E "console\.log|debugger|TODO|FIXME"
```

**Pass Criteria:** No debug code in staged changes

### ✅ Branch Hygiene
```bash
# Verify no merge conflicts, all local
git log origin/main..HEAD  # Should show your commits only
git status  # Should show "working tree clean"
```

**Pass Criteria:** Working tree clean, commits are linear

---

## Phase 8: State Journal Update (1 minute)

### ✅ Update state_journal.md
```markdown
### Completed Task
**Task ID:** TAIRUS-047
**Title:** Implement Circuit Breaker for Stripe integration
**Status:** DONE ✅

**Validation Checklist:**
- [x] Linting: PASS
- [x] Unit Tests: PASS (coverage 92%)
- [x] Integration Tests: PASS
- [x] Performance: PASS (P99 < 200ms)
- [x] Security: PASS (no vulnerabilities)
- [x] Documentation: PASS (updated docs/ARCHITECTURE.md)
- [x] Docker Build: PASS (image size: 156MB)
- [x] Git: PASS (clean commit)

**Time Spent:** 4.5 hours
**Lines Changed:** +287, -45
**Next Task:** TAIRUS-048
```

**Pass Criteria:** Journal updated, task marked DONE

---

## 🛑 HALT CRITERIA: Do NOT Commit If Any of These Are True

| ❌ Condition | Consequence |
|-----------|-------------|
| Linting fails | Code style inconsistent, unmaintainable |
| Test coverage < 80% | Regression risk high |
| Security scan shows HIGH severity | Risk of breached data/credentials |
| Type checking fails | Runtime errors in production |
| No git commit message | Impossible to track blame later |
| Performance degrades > 10% | Users experience slowdown |
| Breaking change not documented | Downstream services break |
| .env or secrets found in diff | Credentials exposed in version control |
| Uncommitted changes in git | State mismatch, recovery impossible |
| Any P0 issue unresolved | Blocking other teams |

---

## Fast-Track: Minimal Validation for Hotfixes

**When:** Production is on fire, need to deploy in < 30 minutes  
**Do THIS instead:**

```bash
# 1. Linting (30 seconds)
ruff check --select=E,F services/  # Only syntax errors

# 2. Critical tests (2 minutes)
pytest -k "critical" --tb=short

# 3. Security (30 seconds)
truffleHog filesystem . --entropy=False

# 4. Docker build (2 minutes)
docker build -f Dockerfile -t tairus:hotfix .

# 5. Commit
git commit -m "[HOTFIX] Fix payment gateway timeout (TAIRUS-999)"

# 6. Add to Incident Log
echo "HotFix deployed $(date)" >> incidents/hotfix_log.md
```

**Risk:** Medium. Use ONLY for true emergencies.  
**Follow-up:** Run full validation within 24 hours.

---

## Automation: Run This Script

Save as `scripts/validate.sh`:

```bash
#!/bin/bash

set -e

echo "🔍 Starting Validation Protocol..."

# Phase 1
echo "📝 Linting..."
ruff check services/ && echo "✅ Linting PASS" || exit 1

# Phase 2
echo "🧪 Tests..."
pytest services/ -v --cov --cov-fail-under=80 && echo "✅ Tests PASS" || exit 1

# Phase 3
echo "🔒 Security..."
truffleHog filesystem . && echo "✅ Security PASS" || exit 1

# Phase 4
echo "⚡ Performance..."
k6 run tests/load/smoke.js && echo "✅ Performance PASS" || exit 1

# Phase 5-7
echo "✨ All phases passed!"
echo "🚀 Ready to commit"

# Auto-commit if flag passed
if [ "$1" == "--auto-commit" ]; then
    git add -A
    git commit -m "Auto-validated: $(date +%s)"
fi
```

**Usage:**
```bash
chmod +x scripts/validate.sh
./scripts/validate.sh --auto-commit
```

---

## What If Validation Fails?

### Scenario: Linting fails
```
Action: Fix code format
Time: 2-5 minutes
Rerun: ./scripts/validate.sh
```

### Scenario: Test coverage too low
```
Action: Write missing tests for uncovered branches
Time: 10-30 minutes
Rerun: pytest --cov
```

### Scenario: Security scan shows vulnerability
```
Action: 
  1. If in dependency: npm update / pip upgrade
  2. If in code: refactor to safe pattern
  3. Document if unfixable with risk mitigation plan
Time: 15-60 minutes
Rerun: truffleHog + semgrep
```

---

**Remember:** This protocol exists because Production data is Sacred.  
Run it every time. No exceptions.

**Last Updated:** 2026-07-31  
**Enforced By:** Claude Autonomous Agent  
**Override Authority:** Only VP Engineering (with incident justification)
