# TAIRUS Business Metrics & Analytics Framework
**Version:** 1.0  
**Last Updated:** 2026-07-30  
**Owner:** Analytics & Finance Team

---

## Executive Summary

The TAIRUS marketplace tracks **21 primary KPIs** across 5 dimensions: Revenue, Growth, Engagement, Quality, and Compliance. Real-time monitoring via Prometheus + Grafana dashboards enables data-driven operations.

---

## 1. Revenue Metrics (Financial)

### Gross Merchandise Value (GMV)
```
Definition: Total transaction value processed through platform
Formula: SUM(order_total) for all confirmed payments
Frequency: Daily
Target: $10M Year 1 → $100M Year 2 → $1B+ Year 3
Alert: Daily GMV < forecast by 20%

SQL Query:
SELECT 
  DATE(created_at) as date,
  SUM(amount) as gmv_daily,
  COUNT(*) as transaction_count,
  AVG(amount) as avg_order_value
FROM payments
WHERE status = 'confirmed'
GROUP BY DATE(created_at)
ORDER BY date DESC;
```

### Platform Revenue (Commission)
```
Definition: Actual revenue (transaction fees + subscriptions)
Formula: GMV * commission_rate (2-3%) + subscription_revenue
Frequency: Daily
Target: $300K Year 1 → $2.5M Year 2 → $20M Year 3
Components:
  - Transaction fees: 2-3% per transaction
  - Premium seller subscriptions: $99-$999/month
  - API tier subscriptions: $99-$9,999/month
  - Featured listings: $10-$100/listing
  - Promotional features: $50-$500/campaign

Prometheus Metric:
platform_revenue_total (counter, incremented per transaction)
```

### Payment Success Rate
```
Definition: Percentage of payment attempts that complete successfully
Formula: successful_payments / total_payment_attempts
Frequency: Real-time (5-minute buckets)
Target: > 99.5%
Alert: Drops below 99% (SEV-1 incident)
Threshold: < 99% = incident, 95-99% = warning, < 95% = critical

Prometheus:
payment_service_success_rate_percent = 
  rate(payments_completed[5m]) / rate(payments_attempted[5m]) * 100
```

### Average Order Value (AOV)
```
Definition: Mean transaction size
Formula: GMV / transaction_count
Frequency: Daily, Weekly, Monthly
Target: $10K average B2B
Insight: Growing AOV indicates larger deals closing

Segment by:
- Commodity type (oil, metals, agriculture)
- Buyer geography
- Seller tier (verified, premium, certified)
- Time of month (weekday vs. weekend)
```

### Customer Lifetime Value (CLV)
```
Definition: Total revenue from single customer relationship
Formula: (avg_transaction_value * purchase_frequency * customer_lifespan)
Frequency: Monthly
Target: $500K+ for enterprise sellers
Calculation Period: 12 months

By Seller Segment:
- Enterprise: $5M+ CLV
- Mid-market: $500K-$5M CLV
- SMB: $50K-$500K CLV
```

---

## 2. Growth Metrics

### Monthly Active Users (MAU)
```
Definition: Unique users with activity (login, search, transaction) in month
Formula: COUNT(DISTINCT user_id WHERE last_activity > NOW() - INTERVAL '30 days')
Frequency: Daily calculation, reported monthly
Target: 5K MAU Year 1 → 50K Year 2 → 500K Year 3
Segment: Buyers, Sellers, Admins

Query:
SELECT 
  EXTRACT(YEAR_MONTH FROM last_activity) as month,
  COUNT(DISTINCT user_id) as mau,
  SUM(CASE WHEN user_type='buyer' THEN 1 ELSE 0 END) as buyer_mau,
  SUM(CASE WHEN user_type='seller' THEN 1 ELSE 0 END) as seller_mau
FROM users
WHERE last_activity > NOW() - INTERVAL '90 days'
GROUP BY month
ORDER BY month DESC;
```

### New User Signups
```
Definition: New account registrations
Formula: COUNT(DISTINCT user_id WHERE created_at IN [day_start, day_end])
Frequency: Daily
Target: 100-200 new users/day sustained
Churn Indicator: If signups drop 50% → investigate marketing

By Source:
- Direct signup: 40%
- Organic search: 30%
- Partner referral: 20%
- Paid ads: 10%

Alert: Daily signups < 50 (anomaly detection)
```

### Net Revenue Retention (NRR)
```
Definition: Revenue from existing customers month-over-month
Formula: (MRR_current + expansion_revenue - churned_revenue) / MRR_previous
Target: > 120% (SaaS healthy benchmark)
Formula: 120% = net growth from expansion deals and new users

Calculation:
- Month 1: $100K revenue (baseline)
- Month 2:
  - Retained: $95K (95% retention)
  - Expansion: $10K (upsells)
  - New: $5K (new customers)
  - Lost: -$5K (churned)
  - NRR = ($95K + $10K + $5K - $5K) / $100K = 105%

Target Segments:
- Enterprise: 150% NRR
- Mid-market: 120% NRR
- SMB: 100% NRR
```

### Transaction Growth Rate (YoY)
```
Definition: Growth in transaction volume year-over-year
Formula: (current_year_txns - prior_year_txns) / prior_year_txns
Frequency: Monthly, Quarterly
Target: 50% YoY growth sustained
Benchmarks:
- Year 1: 1K txns/month (pilot phase)
- Year 2: 50K txns/month (growth phase)
- Year 3: 500K txns/month (scale phase)
```

---

## 3. Engagement Metrics

### Search-to-Transaction Conversion Funnel
```
Definition: % of searches that lead to completed transactions
Funnel Steps:
1. Catalog Search: 100,000 searches/day (baseline)
2. Product View: 5,000 (5% click-through)
3. RFQ Created: 500 (10% of views)
4. Quote Received: 250 (50% response rate)
5. Order Placed: 50 (20% conversion)
6. Payment Completed: 45 (90% success rate)

Overall Conversion: 45 / 100,000 = 0.045% (target: improve to 0.1%)

Optimization Points:
- Search → View gap: Improve search relevance
- View → RFQ gap: Simplify RFQ process
- Quote → Order gap: Improve pricing transparency
- Order → Payment gap: Friction in payment flow
```

### User Engagement Score
```
Definition: Composite metric of platform activity
Dimensions:
1. Frequency: How often user logs in (daily, weekly, monthly)
2. Recency: How recently user was active (today, this week, this month)
3. Monetary: Transaction value ($ amount)
4. Depth: Features used (search, RFQ, messaging, reviews)

Scoring Algorithm:
engagement_score = 
  (login_frequency * 0.25) + 
  (recency_decay * 0.25) + 
  (transaction_value_percentile * 0.25) + 
  (features_used_count * 0.25)

Tiers:
- Score 80-100: Power users (10% of base, 60% of revenue)
- Score 50-79: Regular users (30% of base, 30% of revenue)
- Score 20-49: Casual users (40% of base, 10% of revenue)
- Score 0-19: Dormant users (20% of base, 0% of revenue)
```

### Repeat Purchase Rate
```
Definition: % of buyers who make 2+ purchases
Formula: COUNT(DISTINCT buyers_with_2plus_purchases) / COUNT(DISTINCT total_buyers)
Frequency: Monthly
Target: 30% repeat rate (B2B buyers)
Segment by:
- Commodity type
- Buyer region
- Seller reputation
- Order value tier
```

### Average Session Duration
```
Definition: Time user spends on platform per visit
Formula: AVG(session_end_time - session_start_time)
Frequency: Daily, Weekly
Target: 15-30 minutes (search, browse, RFQ)
High engagement = 45+ minutes
Low engagement = <5 minutes (search only, no follow-up)

Alerts:
- Session duration < 3 min = UX problem
- Session duration > 60 min = good (research, negotiation)
```

---

## 4. Quality Metrics (Trust & Safety)

### Seller Verification Rate
```
Definition: % of sellers who passed KYC/AML screening
Formula: verified_sellers / total_sellers
Target: > 95% of GMV from verified sellers
Levels:
- Verified (Level 1): Basic KYC passed (3-5 days)
- Premium (Level 2): Enhanced KYC + business verification (5-10 days)
- Certified (Level 3): Full due diligence + reference checks (10-15 days)

Risk: If verification rate < 90% → regulatory risk
```

### Dispute Rate
```
Definition: % of orders with customer disputes filed
Formula: disputes_filed / completed_orders
Target: < 2% of transactions
Alert: > 5% = fraud ring or quality issue

Categories:
- Payment disputes: 50% (chargeback claims)
- Delivery issues: 30% (non-delivery, damage)
- Quality issues: 15% (product doesn't match description)
- Seller fraud: 5% (disappearing acts, fake goods)

Resolution:
- Resolved in buyer's favor: 40% (refund issued)
- Resolved in seller's favor: 35% (dispute dismissed)
- Settlement: 25% (partial refund)
```

### Order Fulfillment Rate
```
Definition: % of orders delivered on time
Formula: on_time_deliveries / total_deliveries
Target: > 98%
Alert: < 95% = logistics issue

By Delivery Method:
- Express: 99% on-time (next day)
- Standard: 98% on-time (3-5 days)
- Bulk: 95% on-time (7-10 days)

Impact: On-time delivery correlates with 15% higher repeat rate
```

### Product Return/Claim Rate
```
Definition: % of delivered orders with claims filed (damage, quality, incomplete)
Formula: claimed_orders / delivered_orders
Target: < 1.5%
Alert: > 3% = quality issue or logistics damage

By Commodity:
- Metals: 0.5% (durable, shipping damage rare)
- Oil/Gas: 0.3% (volume measurements precise)
- Agriculture: 2.0% (perishable, quality variance)
- Electronics: 1.2% (fragile, damage risk)
```

### Fraud Detection Rate
```
Definition: % of fraudulent transactions prevented by FraudDetector
Formula: blocked_fraud_transactions / attempted_fraud_transactions
Target: > 98% accuracy (minimize false positives + false negatives)

Metrics:
- True Positive Rate (TPR): 98% (fraud detected)
- False Positive Rate (FPR): < 1% (legitimate blocks)
- Precision: 95% (if we flag it, it's fraud)
- Recall: 98% (if it's fraud, we catch it)

Monthly Fraud Report:
- Blocked value: $X prevented loss
- Manual review queue: Y high-risk transactions
- Chargebacks prevented: Z % reduction YoY
```

### NPS (Net Promoter Score)
```
Definition: Customer satisfaction & loyalty metric
Question: "How likely are you to recommend TAIRUS to a colleague?"
Scale: 0-10

Calculation:
NPS = (Promoters % - Detractors %) * 100
- Promoters (9-10): 60% target
- Passives (7-8): 30% target
- Detractors (0-6): 10% target

Healthy NPS = 50+
Excellent NPS = 70+

Segment by:
- Buyer vs. Seller
- New vs. Established
- By commodity type
- By order value
```

---

## 5. Operational Metrics (Performance & Reliability)

### API Availability / Uptime
```
Definition: % of time API endpoints are responding (< 1s response time)
Target: 99.99% uptime (52 minutes downtime/year)
Measured: Synthetic monitoring from 5 geographic regions
Alert: < 99.9% = SEV-2 incident

SLA Commitment to Customers:
- 99.95% uptime SLA for paid tiers
- Automatic $100 credit if breached

Calculation:
Uptime = (total_requests - failed_requests) / total_requests * 100
Failed = HTTP 5xx errors + response time > SLA threshold
```

### API Response Time (Latency)
```
Definition: Time for API to respond to request
Metrics:
- P50 (median): Target < 100ms
- P95 (95th percentile): Target < 500ms
- P99 (99th percentile): Target < 1000ms (SEV-2 alert if > 1s)
- Max: Track but don't alert (outliers happen)

By Endpoint:
- Search: P99 < 500ms (cached)
- RFQ Submit: P99 < 1000ms (async processing)
- Payment: P99 < 2000ms (external API calls)
- Quote: P99 < 2000ms (calculation-heavy)

Tools: Prometheus histogram + Grafana graphs
```

### Database Query Performance
```
Definition: Slowest queries in system
Tracking: pg_stat_statements extension (PostgreSQL)

Thresholds:
- < 10ms: Excellent (cached)
- 10-100ms: Good
- 100-500ms: Acceptable
- 500ms-1s: Slow (needs indexing)
- > 1s: Critical (blocks users)

Weekly Report:
- Top 10 slowest queries by total time
- Query plans with missing indexes
- Cache hit rates by table
```

### Error Rate by Service
```
Definition: % of requests returning errors (HTTP 4xx/5xx)
Target: < 1% overall error rate

By Service:
- Auth: < 0.5% (critical - auth failures block users)
- Catalog: < 1% (important - breaks search)
- Payment: < 0.1% (critical - money transactions)
- Messaging: < 2% (less critical - retry possible)
- Logistics: < 2% (less critical - async)
- Compliance: < 0.5% (critical - regulatory)
- Notification: < 5% (acceptable - eventual delivery)

Alert: Service error rate > threshold by 50% = SEV-2
```

### Cache Hit Rate
```
Definition: % of requests served from Redis cache (vs database)
Target: > 80% for search, > 60% for product data
Formula: cache_hits / (cache_hits + cache_misses)

By Cache Type:
- Product search cache: 85% target
- User session cache: 95% target
- Rate limit cache: 100% (must be cached)
- Price cache: 70% target (needs freshness)

Low hit rate → increase cache TTL or size
High miss rate → indexing problem or eviction
```

### Database Connection Pool Usage
```
Definition: % of available database connections in use
Target: < 80% average, < 95% peak
Alert: > 90% average = connection leak
Action: Restart connection pooler (pgBouncer)

Healthy Metrics:
- Min connections: 10
- Max connections: 100
- Average active: 30-40
- Connection wait time: < 10ms

If pool exhausted → service degradation
```

### Message Queue Latency
```
Definition: Time for async job to process (Kafka/RabbitMQ)
Latencies:
- Email notifications: < 30 seconds (user doesn't wait)
- SMS alerts: < 60 seconds
- Analytics events: < 5 minutes (eventual consistency OK)
- Fraud alerts: < 10 seconds (real-time)

Alert: Consumer lag > 100k messages (backlog)
Action: Scale up consumer replicas
```

---

## 6. Dashboards (Grafana Configuration)

### Real-time Operations Dashboard
```
Panels:
1. GMV - Last 24h (big number)
2. Transaction Volume - Last 24h (line chart)
3. Success Rate by Service (4-box layout)
4. Error Rate - Last 1h (stacked area)
5. Top Errors - Last 1h (table)
6. Latency P99 - Last 1h (line chart)
7. Cache Hit Rate - Last 1h (gauge)
8. Active Users - Live (big number)

Refresh Rate: 30 seconds
For: On-call engineers, operations team
```

### Financial Dashboard
```
Panels:
1. GMV - Month to Date (big number, target vs actual)
2. Revenue - Month to Date (big number)
3. AOV Trend - Last 12 months (line chart)
4. NRR Progress (gauge, color coded)
5. Customer Acquisition Cost (calculation)
6. CLV Trend - Last 12 months (line)
7. Churn Rate - Last 12 months (line)
8. Payment Methods breakdown (pie chart)

For: CFO, Finance, Leadership
Refresh: Daily
```

### Product & Growth Dashboard
```
Panels:
1. MAU - Last 12 months (line chart)
2. New Signups - Daily (bar chart)
3. Search Volume - Daily (line)
4. Conversion Funnel - (funnel visualization)
5. Repeat Purchase Rate (gauge)
6. Session Duration - Avg (big number)
7. Top Products by GMV (table)
8. Buyer vs Seller growth (split line chart)

For: Product, Growth teams
Refresh: Daily
```

### Quality & Trust Dashboard
```
Panels:
1. Seller Verification Rate (gauge)
2. Dispute Rate - % (line chart)
3. Fraud Detection Blocks (counter)
4. Order Fulfillment Rate (gauge)
5. Return/Claim Rate (line chart)
6. NPS Score (big number)
7. Chargebacks - Count (table)
8. Seller Score Distribution (histogram)

For: Trust & Safety, Legal
Refresh: Hourly
```

### Technical Health Dashboard
```
Panels:
1. Uptime - All services (big number)
2. Error Rate - by Service (4-box)
3. Latency P99 - by Service (multi-line)
4. Database CPU (gauge)
5. Database Memory (gauge)
6. Disk Usage (gauge)
7. Network I/O (line chart)
8. Pod Restart Count (table)

For: DevOps, Platform engineering
Refresh: Real-time (5 sec)
```

---

## 7. Alerting Strategy

### Prometheus Scrape Schedule
```
Critical Services (every 5s):
- payment-service (money is critical)
- compliance-service (regulatory)

Important Services (every 10s):
- auth-service (access control)
- catalog-service (core functionality)
- kong (API gateway)

Standard Services (every 15-30s):
- messaging, logistics, notification
- infrastructure (postgres, redis, kafka)
```

### Alert Escalation
```
Severity Levels:
1. INFO (internal notification only)
   - Example: Daily GMV reported
   - Action: Log to dashboard

2. WARNING (team notification)
   - Example: Fraud block rate > 20%
   - Action: Slack to fraud team
   - Response SLA: 1 hour

3. CRITICAL (on-call page)
   - Example: Payment error rate > 5%
   - Action: Page on-call engineer
   - Response SLA: 15 minutes
   - Escalation: VP Eng if unresolved in 30 min

Notification Channels:
- INFO: Slack #analytics
- WARNING: Slack #alerts + email
- CRITICAL: Slack #p1-incidents + PagerDuty + phone
```

---

## 8. Data Retention Policies

| Data | Retention | Storage | Query Frequency |
|------|-----------|---------|-----------------|
| Raw transactions | 7 years | PostgreSQL | Daily |
| Prometheus metrics | 15 days | Prometheus | Real-time |
| Audit logs | 7 years | PostgreSQL | Weekly |
| User sessions | 90 days | Redis | Real-time |
| Analytics events | 2 years | Elasticsearch | Monthly |
| Backups | 30 days | S3 | On-demand |

---

## 9. Monthly Business Review (MBR) Template

```
DATE: [First Monday of month]
PARTICIPANTS: CEO, CFO, CTO, Product, Ops

METRICS REVIEWED:
□ GMV vs target (actual vs forecast)
□ Revenue breakdown (transaction fees, subscriptions, premium features)
□ Customer acquisition (new users, by source)
□ Customer retention (churn rate, NRR)
□ Conversion funnel (search → order % trending)
□ NPS and qualitative feedback
□ System reliability (uptime, incidents)
□ Fraud metrics (losses prevented, false positive rate)
□ Growth initiatives impact (new features adoption)

DECISIONS MADE:
□ Resource allocation (engineering, marketing, ops)
□ Product priorities for next month
□ Pricing adjustments if needed
□ Operational improvements
□ Risk mitigation actions

FOLLOW-UPS:
- Owner assignments
- Timeline
- Success criteria
```

---

## Implementation

### Prometheus Stack Deployment
```bash
# Start monitoring
docker-compose -f infrastructure/monitoring-compose.yml up -d

# Verify
curl http://localhost:9090/api/v1/query?query=up

# Grafana access
http://localhost:3000 (admin/admin by default)
```

### Custom Metrics Implementation (in each service)

```python
# Python/FastAPI example
from prometheus_client import Counter, Histogram, Gauge

# Metrics
payments_completed = Counter('payments_completed', 'Total completed payments')
payment_amount = Histogram('payment_amount_dollars', 'Payment amounts')
fraud_risk_score = Gauge('fraud_risk_score', 'Current fraud risk')

@app.post("/payments/process")
async def process_payment(order: Order):
    try:
        # Process payment
        payments_completed.inc()
        payment_amount.observe(order.amount)
    except Exception as e:
        payment_errors.inc()
        raise
```

```go
// Go example
package payment

import "github.com/prometheus/client_golang/prometheus"

var (
    paymentCounter = prometheus.NewCounter(prometheus.CounterOpts{
        Name: "payments_completed_total",
        Help: "Total completed payments",
    })
    
    paymentDuration = prometheus.NewHistogram(prometheus.HistogramOpts{
        Name: "payment_duration_seconds",
        Help: "Payment processing duration",
        Buckets: []float64{.001, .01, .1, 1, 10},
    })
)
```

---

**Version History:**
- v1.0 (2026-07-30): Initial metrics framework with 21 KPIs, Prometheus alerts, dashboards, and business review template.
