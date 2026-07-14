# TAIRUS Marketplace v2.0

**Commodities B2B Platform - Microservices Architecture**

Next-generation marketplace for commodity trading, built with modern cloud-native technologies.

## 🚀 Quick Start

### Prerequisites
- Docker & Docker Compose 3.9+
- Go 1.21+
- Node.js 18+
- Python 3.11+
- Kubernetes 1.27+ (for production)

### Local Development

```bash
# 1. Start all services
docker-compose up -d

# 2. Verify services are running
docker-compose ps

# 3. Access services
- PostgreSQL: localhost:5432
- MongoDB: localhost:27017
- Redis: localhost:6379
- Elasticsearch: http://localhost:9200
- Kafka: localhost:9092
- RabbitMQ Admin: http://localhost:15672 (tairus/secure_dev_password)
- Jaeger UI: http://localhost:16686
```

## 📦 Project Structure

```
tairus-marketplace-v2/
├── services/                          # Microservices
│   ├── auth-service/                  # OAuth 2.0 + JWT
│   ├── catalog-service/               # Products + Search
│   ├── payment-service/               # Payments + Escrow
│   ├── messaging-service/             # In-app Chat
│   ├── logistics-service/             # Shipping Integration
│   ├── compliance-service/            # KYC, AML, Sanctions
│   └── notification-service/          # Emails, Push, SMS
├── shared/                            # Shared libraries
│   ├── proto/                         # Protocol Buffers
│   ├── models/                        # Data Models
│   └── utils/                         # Common Utilities
├── infrastructure/                    # IaC & DevOps
│   ├── terraform/                     # AWS/GCP Infrastructure
│   ├── k8s/                           # Kubernetes Manifests
│   └── scripts/                       # Setup & Migration
├── docs/                              # Documentation
│   ├── API.md                         # OpenAPI Specifications
│   ├── ARCHITECTURE.md                # System Design
│   └── DEPLOYMENT.md                  # Production Guide
└── docker-compose.yml                 # Local Dev Environment
```

## 🔧 Services Overview

### Auth Service (Go)
- OAuth 2.0 + JWT tokens
- Role-Based Access Control (RBAC)
- MFA support
- Session management

### Catalog Service (Node.js)
- Product management
- Full-text search (Elasticsearch)
- Category management
- Bulk uploads

### Payment Service (Python)
- Stripe/Sberbank integration
- Escrow management
- Invoice generation
- Transaction tracking

### Messaging Service (Go)
- Real-time chat
- WebSocket support
- Message persistence
- Notifications

### Logistics Service (Go)
- Shipping provider integration
- Route optimization
- Real-time tracking
- Cost calculation

### Compliance Service (Go)
- KYC (Know Your Customer)
- AML (Anti-Money Laundering)
- OFAC/Sanctions screening
- Document verification

### Notification Service (Node.js)
- Email delivery
- Push notifications
- SMS alerts
- Webhook callbacks

## 📊 Development Workflow

### 1. Service Development

```bash
cd services/catalog-service
npm install
npm run dev
```

### 2. Database Schema

```bash
# PostgreSQL migrations
cd infrastructure/scripts
./migrate-postgres.sh up

# MongoDB schema
cd infrastructure/scripts
./init-mongodb.sh
```

### 3. API Testing

```bash
# Install API test tool
npm install -g insomnia-cli

# Run tests
insomnia run api-tests.yml
```

## 🔐 Security

- **TLS/SSL** for all services
- **Rate limiting** on all APIs
- **Input validation** on request handlers
- **SQL injection protection** via ORM
- **CORS** properly configured
- **CSRF tokens** for state-changing operations
- **Secret management** via HashiCorp Vault
- **PCI DSS compliance** for payment processing

## 📈 Monitoring & Logging

```bash
# View logs
docker-compose logs -f auth-service

# Access Jaeger traces
http://localhost:16686

# Monitor via Prometheus + Grafana
# (add to docker-compose.yml for production setup)
```

## 🧪 Testing

```bash
# Unit tests
npm run test

# Integration tests
npm run test:integration

# Load testing
npm run test:load
```

## 📚 Documentation

- [Architecture](./docs/ARCHITECTURE.md) - System design & decisions
- [API Reference](./docs/API.md) - OpenAPI 3.0 specifications
- [Deployment](./docs/DEPLOYMENT.md) - Production setup guide
- [Contributing](./docs/CONTRIBUTING.md) - Development guidelines

## 🚢 Deployment

### Development
```bash
docker-compose up -d
```

### Staging
```bash
kubectl apply -f k8s/staging/
```

### Production
```bash
# See docs/DEPLOYMENT.md for full guide
terraform apply -var-file="prod.tfvars"
kubectl apply -f k8s/production/
```

## 🔄 CI/CD Pipeline

- GitHub Actions for automated testing
- Docker image builds on every push
- Automated deployments to staging
- Manual approval for production releases

## 📞 Support

- **Issues**: GitHub Issues
- **Discussions**: GitHub Discussions
- **Email**: dev@tairus.io

## 📄 License

Proprietary - TAIRUS Internal Use Only

---

**Status:** In Development 🚧  
**Last Updated:** 2026-07-14  
**Version:** 2.0.0-alpha
