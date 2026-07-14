# TAIRUS Marketplace v2.0 - Architecture Document

## System Overview

TAIRUS v2.0 is a cloud-native microservices platform for B2B commodity trading. Built on Kubernetes with containerized services, it provides enterprise-grade scalability, reliability, and security.

### Design Principles
1. **Scalability** - Each service scales independently
2. **Resilience** - Fault-tolerant with circuit breakers & retries
3. **Security** - Defense in depth with encryption, validation, rate-limiting
4. **Observability** - Comprehensive logging, tracing, and monitoring
5. **Performance** - <100ms p99 latency for critical paths

## High-Level Architecture

```
┌─────────────────────────────────────────────────────────────────┐
│                        API Gateway                              │
│  (Kong/Traefik - Rate Limiting, Auth, Request Validation)       │
└──────────────────────────────┬──────────────────────────────────┘
                               │
         ┌─────────────────────┼─────────────────────┐
         │                     │                     │
    ┌────▼──────────┐  ┌──────▼────────┐  ┌─────────▼────────┐
    │ Auth Service  │  │ Catalog       │  │ Messaging        │
    │ (Go/gRPC)     │  │ Service       │  │ Service (Go)     │
    │               │  │ (Node/REST)   │  │                  │
    │ - OAuth 2.0   │  │               │  │ - WebSocket      │
    │ - JWT         │  │ - Search      │  │ - Real-time      │
    │ - RBAC        │  │ - Products    │  │ - Notifications  │
    │ - Sessions    │  │ - Categories  │  │                  │
    └────────────────┘  └───────────────┘  └──────────────────┘

    ┌────────────────┐  ┌──────────────────┐  ┌────────────────┐
    │ Payment        │  │ Logistics        │  │ Compliance     │
    │ Service        │  │ Service (Go)     │  │ Service (Go)   │
    │ (Python/Fast)  │  │                  │  │                │
    │                │  │ - Shipping       │  │ - KYC          │
    │ - Payments     │  │ - Tracking       │  │ - AML          │
    │ - Escrow       │  │ - Integration    │  │ - Sanctions    │
    │ - Invoices     │  │ - Optimization   │  │ - Verification │
    └────────────────┘  └──────────────────┘  └────────────────┘

    ┌────────────────────────────────────────────────────────────┐
    │          Notification Service (Node.js)                    │
    │  Email, SMS, Push Notifications, Webhooks                 │
    └────────────────────────────────────────────────────────────┘

┌─────────────────────────────────────────────────────────────────┐
│                    Data Layer                                   │
├─────────────────────────────────────────────────────────────────┤
│ PostgreSQL (Transactions) │ MongoDB (Catalogs) │ Redis (Cache) │
│ Elasticsearch (Search)    │ S3 (Files)         │               │
└─────────────────────────────────────────────────────────────────┘

┌─────────────────────────────────────────────────────────────────┐
│                 Message Queue & Async                          │
├─────────────────────────────────────────────────────────────────┤
│ Kafka (Event Streaming) │ RabbitMQ (Task Queue)               │
└─────────────────────────────────────────────────────────────────┘
```

## Detailed Service Specifications

### 1. Auth Service (Go)

**Responsibilities:**
- User authentication & authorization
- Token generation & validation
- Session management
- Role-based access control

**Technology Stack:**
- Go 1.21
- gRPC for inter-service communication
- JWT tokens with RS256 signing
- Redis for session cache

**Key Flows:**
```
1. User Login → JWT token
2. Service validates token via Auth gRPC
3. Auth returns user claims & permissions
4. Resource service enforces RBAC
```

**API Endpoints:**
- `POST /auth/login` - User login
- `POST /auth/refresh` - Token refresh
- `POST /auth/logout` - Session termination
- `POST /auth/verify` - Token verification
- `POST /auth/mfa/setup` - MFA enablement

### 2. Catalog Service (Node.js)

**Responsibilities:**
- Product catalog management
- Search & filtering
- Category hierarchy
- Bulk import/export

**Technology Stack:**
- Node.js 18
- Express.js for REST API
- Elasticsearch for full-text search
- MongoDB for flexible schema

**Data Model:**
```javascript
Product {
  id: UUID,
  sellerId: UUID,
  title: String,
  description: String,
  category: ObjectId,
  price: Decimal,
  quantity: Int,
  specs: {
    grade: String,
    purity: Float,
    origin: String,
    hsCode: String,
  },
  logistics: {
    weight: Float,
    volume: Float,
    requiresRefrigeration: Boolean,
  },
  images: [URL],
  createdAt: DateTime,
  updatedAt: DateTime,
}
```

**Elasticsearch Mapping:**
```json
{
  "mappings": {
    "properties": {
      "title": { "type": "text", "analyzer": "standard" },
      "category": { "type": "keyword" },
      "price": { "type": "float" },
      "specs.grade": { "type": "keyword" },
      "location": { "type": "geo_point" }
    }
  }
}
```

### 3. Payment Service (Python/FastAPI)

**Responsibilities:**
- Payment processing
- Escrow management
- Invoice generation
- Financial compliance (PCI DSS)

**Technology Stack:**
- Python 3.11 + FastAPI
- PostgreSQL with ACID guarantees
- Stripe/Sberbank SDK
- Celery for async tasks

**Payment Flow:**
```
1. Buyer creates order → Payment Service
2. Service locks escrow funds
3. Payment gateway processes payment
4. Service updates escrow state
5. On delivery confirmation → Release funds
6. On dispute → Arbitration flow
```

**Critical Features:**
- Atomic transactions (all-or-nothing)
- Idempotent operations (retry-safe)
- Audit logging for compliance
- PCI DSS v3.2.1 compliance

### 4. Messaging Service (Go)

**Responsibilities:**
- Real-time chat between buyers & sellers
- Message persistence
- Notifications on new messages
- Rate limiting per user

**Technology Stack:**
- Go 1.21
- WebSocket for real-time
- PostgreSQL for history
- Redis for presence

**Message Flow:**
```
1. Client connects → WebSocket
2. Message sent → Kafka (event)
3. Stored in PostgreSQL
4. Real-time delivery via WebSocket
5. Notification Service → Email/Push
```

### 5. Logistics Service (Go)

**Responsibilities:**
- Shipping provider integration
- Route optimization
- Real-time tracking
- Cost calculation

**Integrations:**
- DHL, FedEx, UPS APIs
- Yandex.Delivery, CDEK (Russia)
- SMS notifications for updates

### 6. Compliance Service (Go)

**Responsibilities:**
- Know Your Customer (KYC)
- Anti-Money Laundering (AML)
- Sanctions screening (OFAC/EU)
- Document verification

**Key Features:**
- Real-time OFAC screening
- Beneficial ownership verification
- Risk scoring
- Audit trails

### 7. Notification Service (Node.js)

**Responsibilities:**
- Email delivery
- Push notifications
- SMS alerts
- Webhook callbacks

**Event Sources:**
- Order created
- Payment confirmed
- Shipment updated
- Message received

## Data Storage Strategy

### PostgreSQL (Primary OLTP)
- User accounts & authentication
- Orders & transactions
- Payments & escrow
- Compliance records
- Audit logs

**Schema Highlights:**
```sql
CREATE TABLE orders (
  id UUID PRIMARY KEY,
  buyer_id UUID NOT NULL,
  seller_id UUID NOT NULL,
  amount DECIMAL(19,4),
  status ENUM('pending', 'confirmed', 'shipped', 'delivered', 'cancelled'),
  created_at TIMESTAMP,
  updated_at TIMESTAMP,
  FOREIGN KEY (buyer_id) REFERENCES users(id),
  FOREIGN KEY (seller_id) REFERENCES users(id)
);

CREATE TABLE escrow (
  id UUID PRIMARY KEY,
  order_id UUID NOT NULL,
  amount DECIMAL(19,4),
  status ENUM('locked', 'released', 'refunded'),
  created_at TIMESTAMP,
  FOREIGN KEY (order_id) REFERENCES orders(id)
);
```

### MongoDB (Flexible Schema)
- Product catalogs
- Messages
- User profiles (flexible data)
- Company profiles
- Document storage

### Redis (Cache & Sessions)
- User sessions (2 hour TTL)
- Catalog cache (24 hour TTL)
- Rate limit counters
- Leaderboards (top sellers)
- Presence data (online users)

### Elasticsearch (Full-Text Search)
- Product search
- Price history analytics
- Message search
- User activity search

### S3 (Object Storage)
- Product images
- Document uploads
- Backup data
- Compliance records

## Communication Patterns

### Synchronous (gRPC/REST)
- **Auth Service** ← used by all services for token validation
- **Catalog Service** ← REST API for product queries
- **Compliance Service** ← gRPC for real-time KYC checks

### Asynchronous (Message Queue)
- **Kafka** for event streaming (audit logs, analytics)
- **RabbitMQ** for task queues (email sending, batch operations)

### Event Flow Example
```
1. Order Created Event (Kafka)
   → Notification Service → Send email
   → Analytics Service → Update metrics
   → Logistics Service → Prepare shipment
   → Compliance Service → Flag for review if needed

2. Payment Confirmed Event (Kafka)
   → Escrow Service → Release funds
   → Notification Service → Send notification
   → Analytics Service → Update revenue
```

## Scalability Strategy

### Horizontal Scaling
- **Stateless services** → multiple replicas (HPA: target 70% CPU)
- **Database reads** → replicas + caching
- **Database writes** → connection pooling + sharding

### Vertical Scaling
- **Memory** → increase for cache services
- **CPU** → increase for compute-heavy services
- **Network** → increase bandwidth for real-time services

### Caching Strategy
- **L1: Redis** - in-memory cache (fastest)
- **L2: PostgreSQL** - primary data (accurate)
- **Invalidation** - event-driven via Kafka

## Security Architecture

### Network Security
- **TLS/SSL** for all external communications
- **mTLS** between services
- **Network policies** restrict service-to-service traffic
- **WAF** (Web Application Firewall) at ingress

### Application Security
- **Input validation** on all endpoints
- **SQL injection prevention** via parameterized queries
- **XSS prevention** via output encoding
- **CSRF tokens** for state-changing operations
- **Rate limiting** per IP/user/API key

### Data Security
- **Encryption at rest** - AES-256 for sensitive data
- **Encryption in transit** - TLS 1.3
- **PII masking** in logs
- **Secrets management** - HashiCorp Vault
- **Database encryption** - native PostgreSQL/MongoDB encryption

### Access Control
- **RBAC** - Role-based access control
- **ABAC** - Attribute-based access control (future)
- **API keys** for service-to-service auth
- **OAuth 2.0** for user auth
- **MFA** - Multi-factor authentication required for high-risk operations

## Monitoring & Observability

### Logging
- **ELK Stack** - Elasticsearch, Logstash, Kibana
- **Structured logging** - JSON format
- **Log levels** - DEBUG, INFO, WARN, ERROR, CRITICAL
- **Retention** - 30 days

### Tracing
- **Jaeger** - Distributed tracing
- **Trace sampling** - 10% in production
- **Correlation IDs** - track requests across services

### Metrics
- **Prometheus** - Time-series database
- **Grafana** - Visualization
- **Key metrics**:
  - Request latency (p50, p95, p99)
  - Error rates (4xx, 5xx)
  - Throughput (RPS)
  - Cache hit rates
  - Database connection pool usage

### Alerting
- **PagerDuty** - On-call management
- **Critical alerts**:
  - Service down (health check failure)
  - Error rate > 5%
  - Latency p99 > 1s
  - Database replication lag > 60s

## Deployment Strategy

### Development
- Docker Compose locally
- PostgreSQL, MongoDB, Redis, Kafka all included
- Hot-reload for code changes

### Staging
- Kubernetes cluster on AWS
- RDS for PostgreSQL (Multi-AZ)
- DocumentDB for MongoDB
- ElastiCache for Redis
- S3 for storage

### Production
- Kubernetes cluster on AWS (3 AZs)
- RDS with automated backups
- ReadReplicas for read scaling
- CloudFront for CDN
- Auto-scaling based on metrics

## Disaster Recovery

### Backup Strategy
- **PostgreSQL** - automated daily backups, 30-day retention
- **MongoDB** - continuous replication, point-in-time recovery
- **S3** - versioning enabled
- **Configuration** - Infrastructure as Code (Terraform)

### Recovery Time Objective (RTO)
- Critical services (Auth, Payments): 15 minutes
- Non-critical services (Messaging): 1 hour

### Recovery Point Objective (RPO)
- Database: 1 hour
- Object storage: 24 hours

## Future Enhancements

1. **AI/ML**
   - Fraud detection
   - Price prediction
   - Recommendation engine
   - Supplier risk scoring

2. **Blockchain**
   - Document notarization
   - Smart contracts for escrow
   - Supply chain transparency

3. **Advanced Features**
   - Options trading for commodities
   - Futures contracts
   - Hedge instruments

---

**Last Updated:** 2026-07-14  
**Version:** 1.0
