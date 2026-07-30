# Data Migration Strategy: Old System → TAIRUS v2.0

**Status:** Pre-Production  
**Timeline:** 2 weeks (Phase 0 before Phase 1 coding)  
**Data Size:** ~150GB PostgreSQL + 20GB files

---

## Executive Summary

**Challenge:** Migrate 2,416 PHP files worth of data (16+ years, 2008-2024) from legacy NCNDA platform to new microservices architecture while maintaining:
- Zero data loss
- Business continuity (old system still running during migration)
- Data integrity across relational + document databases
- Audit trail for compliance

**Strategy:**
1. **Extract:** Dump old MySQL + PHP app data
2. **Transform:** Map schemas, clean data, validate
3. **Load:** Bulk insert into PostgreSQL + MongoDB
4. **Verify:** 100% count match + sampling audit
5. **Parallel Run:** Both systems live for 2 weeks
6. **Cutover:** Switch traffic to new system

---

## Phase 0: Pre-Migration Audit (Days 1-3)

### 1. Inventory Old System Data

```bash
# Connect to old database
mysql -h old-mysql.internal -u root -p

# Analyze schema
SELECT 
  TABLE_NAME,
  COUNT(*) as row_count,
  ROUND(((DATA_LENGTH + INDEX_LENGTH) / 1024 / 1024), 2) as size_mb
FROM INFORMATION_SCHEMA.TABLES
WHERE TABLE_SCHEMA = 'ncnda_platform'
GROUP BY TABLE_NAME
ORDER BY DATA_LENGTH DESC;
```

**Expected Results:**
```
TABLE_NAME              | ROW_COUNT  | SIZE_MB
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
transactions            | 2,547,392  | 4,820
users                   | 89,432     | 156
products                | 1,243,521  | 2,340
companies               | 34,221     | 89
orders                  | 523,941    | 1,200
messages                | 8,932,104  | 3,450
files                   | 2,341,090  | 5,600
compliance_docs         | 123,432    | 234
...
TOTAL                   | 15M+       | ~18GB
```

### 2. Identify Data Types & Conversions

**MySQL → PostgreSQL Schema Mapping:**

```sql
-- OLD (MySQL)
CREATE TABLE users (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  username VARCHAR(255) NOT NULL,
  email VARCHAR(255) NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  role ENUM('admin', 'trader', 'supplier')
);

-- NEW (PostgreSQL)
CREATE TABLE users (
  id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
  username VARCHAR(255) NOT NULL UNIQUE,
  email VARCHAR(255) NOT NULL UNIQUE,
  created_at TIMESTAMPTZ DEFAULT NOW(),
  role VARCHAR(50) NOT NULL CHECK (role IN ('admin', 'trader', 'supplier')),
  migrated_from_old_id INT,  -- Track origin
  migration_timestamp TIMESTAMPTZ DEFAULT NOW()
);

-- Data conversion:
-- INT → UUID (assign new UUID, keep old_id for reference)
-- AUTO_INCREMENT → UUID default
-- TIMESTAMP → TIMESTAMPTZ (important for timezone-aware ops)
-- ENUM → VARCHAR + CHECK constraint (more flexible)
```

### 3. Identify Problematic Data

```bash
# Run validation queries BEFORE migration
mysql -h old-mysql.internal -u root -p ncnda_platform << 'EOF'

-- Find orphaned records (transactions without user)
SELECT COUNT(*) as orphaned_transactions
FROM transactions t
LEFT JOIN users u ON t.user_id = u.id
WHERE u.id IS NULL;

-- Find duplicate emails
SELECT email, COUNT(*) as count
FROM users
GROUP BY email
HAVING COUNT(*) > 1;

-- Find NULL critical fields
SELECT COUNT(*) FROM users WHERE email IS NULL OR username IS NULL;

-- Find data type mismatches
SELECT * FROM products WHERE amount NOT REGEXP '^[0-9]+\.[0-9]{2}$';

EOF
```

**Plan remediation:**
- Orphaned transactions → link to "system" user or delete
- Duplicate emails → keep oldest, merge records
- NULL fields → backfill with defaults or skip
- Mismatched types → convert or quarantine

---

## Phase 1: ETL Pipeline Development (Days 4-7)

### 1. Extract Stage (MySQL → CSV/JSON)

```python
# scripts/extract_old_data.py
import mysql.connector
import json
from datetime import datetime

def extract_users():
    """Export all users from old system"""
    conn = mysql.connector.connect(
        host='old-mysql.internal',
        user='root',
        password='xxx',
        database='ncnda_platform'
    )
    cursor = conn.cursor(dictionary=True)
    
    cursor.execute("SELECT * FROM users ORDER BY id")
    
    with open('/data/export/users.jsonl', 'w') as f:
        for row in cursor.fetchall():
            # Convert MySQL-specific types
            row['created_at'] = row['created_at'].isoformat()
            f.write(json.dumps(row) + '\n')
    
    print(f"Extracted {cursor.rowcount} users")
    cursor.close()
    conn.close()

def extract_transactions():
    """Export transactions in batches (large table)"""
    conn = mysql.connector.connect(...)
    cursor = conn.cursor(dictionary=True)
    
    batch_size = 10000
    offset = 0
    total = 0
    
    while True:
        cursor.execute(
            f"""SELECT * FROM transactions 
               ORDER BY id LIMIT {batch_size} OFFSET {offset}"""
        )
        rows = cursor.fetchall()
        if not rows:
            break
        
        with open(f'/data/export/transactions_{offset}.jsonl', 'a') as f:
            for row in rows:
                row['created_at'] = row['created_at'].isoformat()
                f.write(json.dumps(row) + '\n')
        
        total += len(rows)
        offset += batch_size
        print(f"Extracted {total} transactions...")
    
    print(f"Total: {total} transactions")

if __name__ == '__main__':
    extract_users()
    extract_transactions()
    extract_products()
    extract_orders()
    extract_messages()
    extract_files()
    extract_compliance_docs()
```

**Output:** 7 JSONL files in `/data/export/`

### 2. Transform Stage (Data Cleaning & Mapping)

```python
# scripts/transform_data.py
import json
import uuid
from typing import Dict, Any
import logging

logger = logging.getLogger(__name__)

class DataTransformer:
    def __init__(self):
        self.old_to_new_user_id_map = {}  # Track old_id → new UUID
        self.validation_errors = []
    
    def transform_users(self):
        """Transform MySQL users to PostgreSQL schema"""
        with open('/data/export/users.jsonl') as f_in:
            with open('/data/staging/users.jsonl', 'w') as f_out:
                for line in f_in:
                    old_user = json.loads(line)
                    new_user = self._transform_user_record(old_user)
                    f_out.write(json.dumps(new_user) + '\n')
    
    def _transform_user_record(self, old_user: Dict[str, Any]) -> Dict[str, Any]:
        """Convert individual user record"""
        new_uuid = str(uuid.uuid4())
        self.old_to_new_user_id_map[old_user['id']] = new_uuid
        
        # Validation
        if not old_user.get('email'):
            self.validation_errors.append(f"User {old_user['id']}: missing email")
            old_user['email'] = f"missing-{old_user['id']}@system.local"
        
        if old_user.get('email') and len(old_user['email']) > 255:
            self.validation_errors.append(f"User email too long: {old_user['email']}")
            old_user['email'] = old_user['email'][:255]
        
        # Transform
        return {
            'id': new_uuid,
            'username': old_user.get('username'),
            'email': old_user.get('email'),
            'role': old_user.get('role', 'trader'),
            'created_at': old_user.get('created_at'),
            'migrated_from_old_id': old_user['id'],
            'migration_timestamp': datetime.utcnow().isoformat()
        }
    
    def transform_transactions(self):
        """Transform transactions using user ID mapping"""
        transaction_count = 0
        
        with open('/data/export/transactions_0.jsonl') as f_in:
            with open('/data/staging/transactions.jsonl', 'w') as f_out:
                for line in f_in:
                    old_txn = json.loads(line)
                    
                    # Map user IDs
                    if old_txn['user_id'] not in self.old_to_new_user_id_map:
                        logger.warning(f"Unknown user {old_txn['user_id']} for transaction {old_txn['id']}")
                        continue
                    
                    new_txn = {
                        'id': str(uuid.uuid4()),
                        'user_id': self.old_to_new_user_id_map[old_txn['user_id']],
                        'amount': float(old_txn['amount']),
                        'status': old_txn.get('status', 'pending'),
                        'created_at': old_txn['created_at'],
                        'migrated_from_old_id': old_txn['id']
                    }
                    
                    f_out.write(json.dumps(new_txn) + '\n')
                    transaction_count += 1
        
        print(f"Transformed {transaction_count} transactions")

if __name__ == '__main__':
    transformer = DataTransformer()
    transformer.transform_users()
    transformer.transform_transactions()
    # ... transform other tables
    
    print(f"Validation Errors: {len(transformer.validation_errors)}")
    for error in transformer.validation_errors[:10]:
        print(f"  - {error}")
```

**Output:** Cleaned JSONL files in `/data/staging/`

### 3. Load Stage (PostgreSQL + MongoDB Bulk Insert)

```python
# scripts/load_data.py
import psycopg2
from psycopg2.extras import execute_batch
import pymongo
import json

def load_users_to_postgres():
    """Bulk insert users into PostgreSQL"""
    conn = psycopg2.connect(
        host='prod-postgres.internal',
        user='tairus',
        password='xxx',
        database='tairus_db'
    )
    cursor = conn.cursor()
    
    users = []
    with open('/data/staging/users.jsonl') as f:
        for line in f:
            user = json.loads(line)
            users.append((
                user['id'],
                user['username'],
                user['email'],
                user['role'],
                user['created_at'],
                user['migrated_from_old_id']
            ))
    
    # Batch insert (faster than row-by-row)
    execute_batch(
        cursor,
        """INSERT INTO users (id, username, email, role, created_at, migrated_from_old_id)
           VALUES (%s, %s, %s, %s, %s, %s)""",
        users,
        page_size=1000
    )
    
    conn.commit()
    print(f"Loaded {len(users)} users into PostgreSQL")
    cursor.close()
    conn.close()

def load_products_to_mongodb():
    """Bulk insert products into MongoDB (flexible schema)"""
    client = pymongo.MongoClient('mongodb://tairus:xxx@prod-mongo.internal:27017/tairus_db')
    db = client['tairus_db']
    
    products = []
    with open('/data/staging/products.jsonl') as f:
        for line in f:
            product = json.loads(line)
            # Add indexes
            product['search_text'] = f"{product['title']} {product['description']}"
            products.append(product)
    
    # Bulk insert
    result = db.products.insert_many(products)
    
    # Create indexes
    db.products.create_index('migrated_from_old_id', unique=True)
    db.products.create_index('seller_id')
    db.products.create_index('category')
    db.products.create_index([('search_text', 'text')])
    
    print(f"Loaded {len(result.inserted_ids)} products into MongoDB")
    client.close()

if __name__ == '__main__':
    load_users_to_postgres()
    load_products_to_mongodb()
    load_transactions_to_postgres()
    # ... load other entities
```

---

## Phase 2: Data Validation (Days 8-10)

### 1. Count Verification

```sql
-- Compare counts between old and new systems
-- OLD:
mysql> SELECT COUNT(*) FROM users;
-- Result: 89,432

-- NEW:
psql> SELECT COUNT(*) FROM users;
-- Result: 89,432 ✓ MATCH

-- If mismatch: Investigate
SELECT id FROM users WHERE migrated_from_old_id IS NULL;  -- New records
SELECT COUNT(DISTINCT migrated_from_old_id) FROM users;   -- Duplicates?
```

### 2. Sampling Audit (Random 1% of records)

```python
# scripts/audit_migration.py
import random
import psycopg2
import mysql.connector

def sample_audit(table_name, sample_size=1000):
    """Random sampling to verify data integrity"""
    
    # Get old system data
    old_conn = mysql.connector.connect(...)
    old_cursor = old_conn.cursor(dictionary=True)
    old_cursor.execute(f"SELECT COUNT(*) as cnt FROM {table_name}")
    total_count = old_cursor.fetchone()['cnt']
    
    # Sample IDs
    sample_ids = random.sample(range(1, total_count), min(sample_size, total_count))
    
    # New system
    new_conn = psycopg2.connect(...)
    new_cursor = new_conn.cursor()
    
    mismatches = []
    for old_id in sample_ids:
        old_cursor.execute(f"SELECT * FROM {table_name} WHERE id = %s", [old_id])
        old_record = old_cursor.fetchone()
        
        new_cursor.execute(
            f"SELECT * FROM {table_name} WHERE migrated_from_old_id = %s",
            [old_id]
        )
        new_record = new_cursor.fetchone()
        
        if not new_record:
            mismatches.append(f"Missing: old_id={old_id}")
            continue
        
        # Compare key fields
        if old_record['email'] != new_record['email']:
            mismatches.append(f"Email mismatch for old_id={old_id}")
    
    accuracy = (len(sample_ids) - len(mismatches)) / len(sample_ids) * 100
    print(f"{table_name}: {accuracy:.1f}% accuracy")
    
    if mismatches:
        print("Mismatches:")
        for m in mismatches[:10]:
            print(f"  - {m}")

if __name__ == '__main__':
    for table in ['users', 'transactions', 'products', 'orders']:
        sample_audit(table, sample_size=1000)
```

### 3. Reconciliation Query

```sql
-- Find records in old but not in new (should be zero)
SELECT COUNT(*) as missing_in_new
FROM old_users_dump o
LEFT JOIN users n ON o.id = n.migrated_from_old_id
WHERE n.id IS NULL;

-- Find duplicate IDs (should be zero)
SELECT migrated_from_old_id, COUNT(*) as count
FROM users
GROUP BY migrated_from_old_id
HAVING COUNT(*) > 1;
```

---

## Phase 3: Parallel Run (Days 11-24)

**Both systems running simultaneously for 2 weeks:**

```
Old System (NCNDA Platform)
├─ Read-only mode (no new transactions)
├─ Serve all historical queries
└─ Backup for rollback

New System (TAIRUS v2.0)
├─ All new traffic
├─ Validate against old system
└─ Monitor for discrepancies
```

**Validation during parallel run:**

```python
# services/validation-service/validator.py
async def validate_order_creation(order_id):
    """
    When order created in new system:
    1. Also create in old system
    2. Compare results
    3. Alert if different
    """
    new_result = await new_system.create_order(order_data)
    old_result = await old_system.create_order(order_data)
    
    if new_result['status'] != old_result['status']:
        logger.error(f"Order creation mismatch for {order_id}")
        alert_team("Migration validation failed")
```

---

## Phase 4: Cutover (Day 25)

### 1. Final Sync (1 hour window)

```bash
# 1. Stop writes to old system
kubectl patch service old-api --type='json' -p='[{"op": "replace", "path": "/spec/selector", "value": {"app": "disabled"}}]'

# 2. Sync any last-minute data
./scripts/sync_last_changes.sh

# 3. Verify no pending transactions
psql -c "SELECT COUNT(*) FROM orders WHERE status='pending'"

# 4. Switch DNS to new system
# kubectl patch service marketplace-api to point to new-payment-service

# 5. Smoke test
curl -X POST http://new-api/payments/test -d '{"amount": 1}'
# Should succeed

# 6. Monitor error rate
watch -n 1 'kubectl logs deployment/payment-service | grep ERROR | wc -l'

# 7. All good? Announce cutover
./scripts/notify_users.sh "System migrated successfully"
```

### 2. Rollback Plan (If Issues Found)

```bash
# If critical issue in first 1 hour:
# 1. Switch DNS back to old system
# 2. Investigate what went wrong
# 3. Fix in new system
# 4. Run parallel tests again
# 5. Re-schedule cutover
```

---

## Estimated Timeline

```
Day 1-3:   Audit old data (→ identify issues)
Day 4-7:   Build ETL pipeline (→ extract/transform/load)
Day 8-10:  Validate migration (→ 99.9%+ accuracy)
Day 11-24: Parallel run (→ monitor both systems)
Day 25:    Cutover (→ switch traffic)
Day 26+:   Monitor & support (→ fast incident response)
```

---

## Success Criteria

✅ **All records migrated** - 100% count match  
✅ **Zero data loss** - Every transaction accounted for  
✅ **Data integrity** - Foreign keys, checksums match  
✅ **Performance** - New system faster than old  
✅ **Zero downtime** - Users don't notice switch  
✅ **Audit trail** - Track all data transformations  

---

## Rollback Procedure

If something goes wrong **after cutover**:

```bash
# Step 1: Immediate rollback (< 5 min)
kubectl patch service marketplace-api -p '{"spec": {"selector": {"app": "old-api"}}}'
# Users routed back to old system

# Step 2: Investigate new system
# Don't delete anything yet - we need logs/data for forensics

# Step 3: Fix bug in new system
git log --oneline -10 # Find what changed
git revert <commit-hash>
docker build && docker push

# Step 4: Parallel run again (from beginning)
# Don't assume one fix solves everything

# Step 5: Re-cutover (only after passing all tests again)
```

---

This migration strategy ensures **zero data loss**, **minimal risk**, and **full auditability** for compliance.
