# TAIRUS Operational Runbooks
## Incident Response Playbooks for Production

**Version:** 1.0  
**Last Updated:** 2026-07-14  
**Severity Levels:** SEV-1 (Critical), SEV-2 (High), SEV-3 (Medium), SEV-4 (Low)

---

## 🚨 SEV-1 INCIDENTS (Immediate Response Required)

### 1. Payment Service Down (Stripe/Sberbank Integration Failed)

**Symptoms:**
- Users report "Payment processing failed"
- Payment Service health check failing
- Error rate > 10% on `/payments/process` endpoint

**RTO (Recovery Time Objective):** 15 minutes  
**RPO (Recovery Point Objective):** 5 minutes (loose pending transactions)

**Diagnosis (3 minutes):**
```bash
# 1. Check service health
kubectl describe pod payment-service-xyz -n production
kubectl logs payment-service-xyz -n production --tail=50

# 2. Check Stripe API status
curl -s https://status.stripe.com/api/v2/status.json | jq '.status.indicator'

# 3. Check database connections
psql -h prod-postgres.internal -U tairus -c "SELECT * FROM pg_stat_activity;"

# 4. Check Redis for stuck jobs
redis-cli INFO stats
redis-cli LLEN payment-queue

# 5. Check Kafka for backup queue
kafka-consumer-groups --bootstrap-server kafka:9092 --group payment-processor --describe
```

**Response (5 minutes):**

**Option A: If Stripe/Sberbank is down (external service failure)**
```bash
# 1. Activate fallback payment queue
kubectl set env deployment/payment-service FALLBACK_PAYMENT_MODE=enabled

# 2. Queue all pending transactions
kubectl logs payment-service-xyz | grep "failed payment" | wc -l

# 3. Notify customers via email
./scripts/notify_users.sh <<EOF
Subject: Payment Processing Delayed
Body: We're experiencing temporary payment delays. Your order has been queued 
and will process automatically within 24 hours. No action required.
EOF

# 4. Monitor queue size
watch -n 5 'redis-cli LLEN payment-queue'
```

**Option B: If Payment Service crashed**
```bash
# 1. Check logs for crash reason
kubectl logs payment-service-xyz -n production --tail=500 | grep -E "ERROR|FATAL|panic"

# 2. If database connection error:
psql -h prod-postgres.internal -U tairus -c "SELECT * FROM pg_stat_activity WHERE state != 'idle';"
# Kill stuck connections if needed
# psql -h prod-postgres.internal -U tairus -c "SELECT pg_terminate_backend(pid) FROM pg_stat_activity WHERE pid <> pg_backend_pid() AND duration > interval '5 minutes';"

# 3. Restart pod
kubectl rollout restart deployment/payment-service -n production

# 4. Monitor startup
kubectl get pods -w -n production | grep payment-service
```

**Recovery (10 minutes):**
```bash
# 1. Verify service is healthy
kubectl exec payment-service-xyz -- curl localhost:8000/health

# 2. Process queued transactions
./scripts/process_payment_queue.sh --retry-failed

# 3. Notify users that system is restored
./scripts/notify_users.sh <<EOF
Subject: ✅ Payment Processing Restored
Body: Payment processing has been restored. Queued payments are now processing.
EOF

# 4. Check transaction count matches
psql -h prod-postgres.internal -U tairus -c "
SELECT 
  DATE(created_at) as date,
  COUNT(*) as total_txns,
  COUNT(CASE WHEN status='confirmed' THEN 1 END) as confirmed,
  COUNT(CASE WHEN status='failed' THEN 1 END) as failed
FROM payments
WHERE created_at > NOW() - INTERVAL '24 hours'
GROUP BY DATE(created_at)
ORDER BY date DESC;"
```

**Escalation:**
- If not resolved in 15 min → page on-call payment engineer
- If customer impact > $100K → notify leadership
- If reputational damage likely → notify PR team

**Post-Incident (30 minutes after recovery):**
1. Create incident report: `incidents/INC-2026-07-14-payment-down.md`
2. Schedule post-mortem for next day
3. Commit any hotfixes to `hotfixes/` branch
4. Update monitoring thresholds if needed

---

### 2. PostgreSQL Replication Lag > 60 seconds (Database Failover)

**Symptoms:**
- Replication lag alert firing
- Read replicas showing stale data
- Users complaining about "old" order status

**RTO:** 10 minutes  
**RPO:** Data loss minimal (using synchronous replication)

**Diagnosis (2 minutes):**
```bash
# Check replication status on primary
psql -h prod-postgres-primary.internal -U tairus -c "
SELECT 
  client_addr,
  backend_start,
  backend_xmin,
  replay_lag,
  write_lag,
  flush_lag
FROM pg_stat_replication;"

# Check replica lag
psql -h prod-postgres-replica.internal -U tairus -c "SELECT now() - pg_last_xact_replay_timestamp() AS replication_lag;"

# Monitor Kafka consumer lag (if using CDC)
kafka-consumer-groups --bootstrap-server kafka:9092 --group postgres-cdc --describe
```

**Response (5 minutes):**

**Option A: If primary is healthy (replica is slow)**
```bash
# Restart lagging replica
kubectl delete pod postgres-replica-0 -n production
kubectl wait --for=condition=ready pod -l app=postgres-replica -n production

# Monitor lag recovery
watch -n 5 'psql -h prod-postgres-replica.internal -U tairus -c "SELECT now() - pg_last_xact_replay_timestamp() AS lag;"'
```

**Option B: If primary is failing (Critical!)**
```bash
# EMERGENCY FAILOVER

# 1. Promote read replica to primary
psql -h prod-postgres-replica.internal -U tairus -c "SELECT pg_promote();"

# 2. Verify replica is now writable
psql -h prod-postgres-replica.internal -U tairus -c "SELECT version();"

# 3. Update connection string in all services
kubectl set env deployment/payment-service DATABASE_URL=postgresql://tairus:xxx@prod-postgres-replica.internal:5432/tairus_db
kubectl set env deployment/catalog-service DATABASE_URL=postgresql://tairus:xxx@prod-postgres-replica.internal:5432/tairus_db
# (repeat for all services)

# 4. Restart services to pick up new connection string
kubectl rollout restart deployment/payment-service deployment/catalog-service deployment/messaging-service -n production

# 5. Monitor application stability
kubectl logs -f deployment/payment-service -n production | grep -E "ERROR|DB_ERROR|connection"

# 6. Once stable, fix the failed primary
# (This is usually RDS automated recovery, but verify)
```

**Recovery (10 minutes):**
```bash
# 1. Verify all services are connecting to new primary
psql -h prod-postgres-replica.internal -U tairus -c "SELECT COUNT(*) FROM pg_stat_activity;"

# 2. Check data integrity
psql -h prod-postgres-replica.internal -U tairus -c "
-- Check for data anomalies
SELECT 'payments' as table_name, COUNT(*) as row_count FROM payments
UNION ALL
SELECT 'orders', COUNT(*) FROM orders
UNION ALL
SELECT 'users', COUNT(*) FROM users;"

# 3. Full backup immediately
pg_dump -h prod-postgres-replica.internal -U tairus -F c -f /backup/postgres-after-failover-$(date +%s).dump tairus_db

# 4. Restore old primary as standby replica (when fixed)
# Contact AWS RDS to promote failed instance or create new replica
```

---

### 3. Kafka Broker Down (Message Queue Failure)

**Symptoms:**
- Async jobs not processing (email, notifications stuck)
- Notification Service error rate > 50%
- Order events not flowing to analytics

**RTO:** 20 minutes  
**RPO:** Messages persist in Kafka (no loss)

**Diagnosis (2 minutes):**
```bash
# Check broker status
kafka-broker-api-versions.sh --bootstrap-server kafka-0.kafka:9092
kafka-broker-api-versions.sh --bootstrap-server kafka-1.kafka:9092
kafka-broker-api-versions.sh --bootstrap-server kafka-2.kafka:9092

# Check cluster status
zkCli.sh -server zookeeper:2181
> ls /brokers/ids

# Check topic leadership
kafka-topics.sh --bootstrap-server kafka-0:9092 --describe --topic payment-events
```

**Response (5 minutes):**

**If single broker is down (quorum still exists):**
```bash
# Kafka automatically rebalances, but speed it up manually
kafka-preferred-replica-election.sh --bootstrap-server kafka-0:9092 --path-to-json-file preferred-replicas.json

# Monitor ISR (In-Sync Replicas)
watch -n 2 'kafka-topics.sh --bootstrap-server kafka-0:9092 --describe --topic payment-events | grep -i isr'

# Check consumer lag recovery
kafka-consumer-groups --bootstrap-server kafka-0:9092 --group notification-processor --describe
```

**If quorum lost (>1 broker down):**
```bash
# Emergency: Stop all consumers
kubectl scale deployment notification-service --replicas=0 -n production
kubectl scale deployment analytics-service --replicas=0 -n production

# Restart Zookeeper and brokers with force
kubectl delete statefulset kafka -n production
# Wait for PVCs to remain
kubectl apply -f infrastructure/k8s/kafka-statefulset.yaml

# Restore from snapshot if available
kafka-mirror-maker.sh --config mm-backup-to-production.properties

# Re-enable consumers
kubectl scale deployment notification-service --replicas=3 -n production
```

---

## 🟠 SEV-2 INCIDENTS (High Priority)

### 4. Elasticsearch Index Corruption or Full Disk

**Symptoms:**
- Search returning no results despite products existing
- Elasticsearch health check yellow/red
- Disk usage > 85%

**RTO:** 30 minutes

**Response:**
```bash
# Check Elasticsearch status
curl -s http://elasticsearch:9200/_cluster/health | jq '.status'

# Check disk usage
curl -s http://elasticsearch:9200/_cat/nodes | grep -i disk_percent

# If full: delete old indices
curl -X DELETE "http://elasticsearch:9200/products-2026-06-*"

# Force merge to reduce disk
curl -X POST "http://elasticsearch:9200/products-2026-07/_forcemerge?max_num_segments=1"

# Reindex if corrupted
curl -X POST "http://elasticsearch:9200/_reindex" -d '{
  "source": {"index": "products-corrupted"},
  "dest": {"index": "products-new"}
}'
```

---

### 5. Memory Leak in Catalog Service (Pod OOMKilled)

**Symptoms:**
- Catalog Service keeps restarting
- Events: "OOMKilled"
- Memory usage grows from 512MB → 2GB over 2 hours

**Response:**
```bash
# Check memory history
kubectl top pod catalog-service-xyz -n production --containers
kubectl describe pod catalog-service-xyz -n production | grep -A 5 "Last State"

# Increase memory limits temporarily
kubectl patch deployment catalog-service -p '{"spec":{"template":{"spec":{"containers":[{"name":"catalog-service","resources":{"limits":{"memory":"2Gi"}}}]}}}' -n production

# Investigate root cause - check code for memory leaks
kubectl logs catalog-service-xyz --previous | grep -i "memory\|leak\|allocation"

# Deploy hotfix after fixing code
git commit -am "fix: memory leak in catalog search caching"
docker build -t catalog-service:2.0.1-hotfix .
kubectl set image deployment/catalog-service catalog-service=catalog-service:2.0.1-hotfix -n production
```

---

## 🟡 SEV-3 INCIDENTS (Medium Priority)

### 6. High Fraud Alert Rate (Possible Attack)

**Symptoms:**
- FraudDetector blocking > 20% of transactions
- Legitimate users getting challenged
- False positive rate spike

**Response:**
```bash
# Check fraud logs
psql -h prod-postgres.internal -U tairus -c "
SELECT risk_level, COUNT(*) FROM fraud_checks_log
WHERE created_at > NOW() - INTERVAL '1 hour'
GROUP BY risk_level;"

# Identify pattern
psql -h prod-postgres.internal -U tairus -c "
SELECT risk_factors, COUNT(*) FROM fraud_checks_log
WHERE created_at > NOW() - INTERVAL '1 hour'
GROUP BY risk_factors
ORDER BY COUNT(*) DESC;"

# If false positives: Adjust thresholds in FraudDetector
# Edit fraud_detector.py and redeploy
```

---

### 7. API Response Time Degradation (P99 > 1s)

**Symptoms:**
- Grafana shows latency spike
- Users report slow UI
- Database queries slow

**Response:**
```bash
# Check top slow queries
psql -h prod-postgres.internal -U tairus -c "
SELECT query, mean_time, calls
FROM pg_stat_statements
ORDER BY mean_time DESC LIMIT 10;"

# Check database connections
psql -h prod-postgres.internal -U tairus -c "SELECT count(*) FROM pg_stat_activity WHERE state='active';"

# If high connection count: Restart connection pooling
kubectl rollout restart deployment/pgbouncer -n production

# Check Redis hit rate
redis-cli INFO stats | grep hit_rate

# If low cache hit rate: Increase Redis size or optimize cache keys
```

---

## 🟢 SEV-4 INCIDENTS (Low Priority)

### 8. DDoS Attack / Rate Limit Exceeded

**Symptoms:**
- 429 errors spiking
- Single IP making millions of requests
- Kong rate limiter blocking legitimate traffic

**Response:**
```bash
# Identify attacker IPs
kubectl logs kong-proxy-xyz -n production | grep "429" | awk '{print $7}' | sort | uniq -c | sort -rn | head -10

# Block IP in Kong
curl -X POST http://kong-admin:8001/acls/blocked/consumers \
  -d "username=attacker-ip-1.2.3.4"

# Increase rate limits for legitimate traffic
# Edit kong-config.yaml and apply

# Monitor attack traffic
watch -n 1 'kubectl logs kong-proxy-xyz -n production | grep "429" | wc -l'
```

---

## 🔄 Blue-Green Deployment & Rollback

**Normal Deployment (Safe):**
```bash
# Deploy new version alongside old
docker build -t catalog-service:2.0.2 .
docker push catalog-service:2.0.2

# Create new deployment
kubectl set image deployment/catalog-service catalog-service=catalog-service:2.0.2 -n production

# Monitor for errors
kubectl logs -f deployment/catalog-service -n production

# Wait 5 minutes, monitor metrics
# If all good: done!
# If bad: rollback immediately
```

**Emergency Rollback (Revert Everything):**
```bash
# Revert to previous version
kubectl rollout undo deployment/catalog-service -n production

# Verify rollback
kubectl get deployment catalog-service -o yaml | grep image:

# Monitor
kubectl logs -f deployment/catalog-service -n production
```

---

## 🧹 Cleanup & Post-Incident

**After any incident:**
1. Document timeline and impact
2. Identify root cause
3. Create follow-up tasks in JIRA
4. Update runbook with new learnings
5. Update monitoring/alerting thresholds
6. Schedule post-mortem (within 24 hours)

---

## 📊 Monitoring Thresholds to Alert On

| Metric | Threshold | Severity |
|--------|-----------|----------|
| Payment Service Error Rate | > 5% | SEV-1 |
| Database Replication Lag | > 60s | SEV-1 |
| Kafka Consumer Lag | > 100k msgs | SEV-2 |
| API Response Time P99 | > 1s | SEV-2 |
| Pod Memory Usage | > 80% limit | SEV-2 |
| Disk Usage | > 85% | SEV-2 |
| CPU Usage | > 90% | SEV-3 |
| Rate Limit 429 Errors | > 1% of traffic | SEV-3 |
| Fraud Detection Block Rate | > 20% | SEV-3 |

---

**On-Call Contact Tree:**
- Payment Issues: @payment-oncall
- Database Issues: @database-oncall
- Infrastructure: @devops-oncall
- General: @engineering-lead

**Escalation:**
- Unresolved 15 min: Escalate to team lead
- Unresolved 30 min: Page VP Engineering
- Customer-facing outage: Notify CEO
