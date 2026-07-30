# Advanced Implementation Patterns for TAIRUS
**Version:** 1.0  
**Last Updated:** 2026-07-30  
**Target:** Production-grade reliability and auditability

---

## 1. Event Sourcing Architecture

### 1.1 Overview

Event Sourcing stores application state as an immutable sequence of state-changing events rather than just current state. Every business action (transaction, payment, order) is recorded as an event.

**Benefits:**
- Complete audit trail (GDPR compliance)
- Temporal queries (what was state at time T?)
- Event-driven distributed systems
- Replay capability for debugging
- CQRS (Command Query Responsibility Segregation)

**Trade-off:** Adds complexity, requires eventual consistency mindset

### 1.2 Event Store Design

```python
# services/shared/event_store.py

from enum import Enum
from datetime import datetime
from typing import Dict, Any, List
import json
import psycopg2
from uuid import uuid4

class EventType(str, Enum):
    # User events
    USER_REGISTERED = "user.registered"
    USER_KYC_SUBMITTED = "user.kyc_submitted"
    USER_KYC_APPROVED = "user.kyc_approved"
    USER_KYC_REJECTED = "user.kyc_rejected"
    
    # Order events
    ORDER_CREATED = "order.created"
    ORDER_ACCEPTED = "order.accepted"
    ORDER_SHIPPED = "order.shipped"
    ORDER_DELIVERED = "order.delivered"
    ORDER_CANCELLED = "order.cancelled"
    
    # Payment events
    PAYMENT_INITIATED = "payment.initiated"
    PAYMENT_AUTHORIZED = "payment.authorized"
    PAYMENT_COMPLETED = "payment.completed"
    PAYMENT_FAILED = "payment.failed"
    PAYMENT_REFUNDED = "payment.refunded"
    
    # Dispute events
    DISPUTE_FILED = "dispute.filed"
    DISPUTE_EVIDENCE_SUBMITTED = "dispute.evidence_submitted"
    DISPUTE_RESOLVED = "dispute.resolved"
    
    # Compliance events
    SANCTIONS_CHECK_PASSED = "compliance.sanctions_passed"
    SANCTIONS_CHECK_FAILED = "compliance.sanctions_failed"
    AML_ALERT_TRIGGERED = "compliance.aml_alert"

class Event:
    """Immutable event - once created, never modified"""
    
    def __init__(
        self,
        event_type: EventType,
        aggregate_id: str,  # User ID, Order ID, etc.
        aggregate_type: str,  # "user", "order", "payment"
        data: Dict[str, Any],
        metadata: Dict[str, Any] = None,
        event_id: str = None,
        timestamp: datetime = None
    ):
        self.event_id = event_id or str(uuid4())
        self.event_type = event_type
        self.aggregate_id = aggregate_id
        self.aggregate_type = aggregate_type
        self.data = data
        self.metadata = metadata or {}
        self.timestamp = timestamp or datetime.utcnow()
        
        # Add to metadata
        self.metadata['user_id'] = self.metadata.get('user_id', aggregate_id)
        self.metadata['ip_address'] = self.metadata.get('ip_address')
        self.metadata['user_agent'] = self.metadata.get('user_agent')

class EventStore:
    """Append-only event log storage"""
    
    def __init__(self, db_connection):
        self.db = db_connection
        self._create_table()
    
    def _create_table(self):
        """Create event store table (run once on startup)"""
        self.db.execute("""
            CREATE TABLE IF NOT EXISTS event_store (
                event_id UUID PRIMARY KEY,
                event_type VARCHAR(100) NOT NULL,
                aggregate_type VARCHAR(50) NOT NULL,
                aggregate_id VARCHAR(100) NOT NULL,
                version INT NOT NULL,
                data JSONB NOT NULL,
                metadata JSONB,
                timestamp TIMESTAMP WITH TIME ZONE NOT NULL DEFAULT NOW(),
                created_at TIMESTAMP WITH TIME ZONE NOT NULL DEFAULT NOW(),
                
                -- Indexes for querying
                UNIQUE(aggregate_id, version),
                INDEX idx_aggregate_id (aggregate_id),
                INDEX idx_event_type (event_type),
                INDEX idx_timestamp (timestamp)
            );
            
            CREATE TABLE IF NOT EXISTS snapshots (
                aggregate_id VARCHAR(100) PRIMARY KEY,
                aggregate_type VARCHAR(50) NOT NULL,
                version INT NOT NULL,
                state JSONB NOT NULL,
                created_at TIMESTAMP WITH TIME ZONE NOT NULL DEFAULT NOW(),
                
                INDEX idx_aggregate_id (aggregate_id)
            );
        """)
    
    def append(self, event: Event) -> bool:
        """Append event to store (append-only, no updates)"""
        
        query = """
            INSERT INTO event_store 
            (event_id, event_type, aggregate_type, aggregate_id, version, data, metadata, timestamp)
            SELECT %s, %s, %s, %s, COALESCE(MAX(version), 0) + 1, %s, %s, %s
            FROM event_store
            WHERE aggregate_id = %s
            ON CONFLICT (event_id) DO NOTHING
        """
        
        try:
            self.db.execute(query, [
                event.event_id,
                event.event_type,
                event.aggregate_type,
                event.aggregate_id,
                json.dumps(event.data),
                json.dumps(event.metadata),
                event.timestamp,
                event.aggregate_id
            ])
            
            # Log event append for audit
            self._log_event_append(event)
            return True
        except Exception as e:
            logger.error(f"Failed to append event: {e}")
            return False
    
    def get_events(self, aggregate_id: str, since_version: int = 0) -> List[Event]:
        """Get all events for aggregate (used for rebuild)"""
        
        query = """
            SELECT event_id, event_type, aggregate_type, aggregate_id, version, data, metadata, timestamp
            FROM event_store
            WHERE aggregate_id = %s AND version > %s
            ORDER BY version ASC
        """
        
        rows = self.db.execute_many(query, [aggregate_id, since_version])
        
        events = []
        for row in rows:
            events.append(Event(
                event_type=EventType(row['event_type']),
                aggregate_id=row['aggregate_id'],
                aggregate_type=row['aggregate_type'],
                data=row['data'],
                metadata=row['metadata'],
                event_id=row['event_id'],
                timestamp=row['timestamp']
            ))
        
        return events
    
    def _log_event_append(self, event: Event):
        """Log event for monitoring"""
        logger.info(f"Event appended: {event.event_type} for {event.aggregate_type}#{event.aggregate_id}")
```

### 1.3 Event-Driven Services

**Example: Order Processing with Events**

```python
# services/payment-service/order_processor.py

class OrderProcessor:
    def __init__(self, event_store, kafka_producer, db):
        self.event_store = event_store
        self.kafka = kafka_producer
        self.db = db
    
    def create_order(self, buyer_id: str, seller_id: str, items: List[Dict]) -> str:
        """Create order and emit event"""
        
        order_id = str(uuid4())
        
        # Create event (immutable)
        event = Event(
            event_type=EventType.ORDER_CREATED,
            aggregate_id=order_id,
            aggregate_type="order",
            data={
                'order_id': order_id,
                'buyer_id': buyer_id,
                'seller_id': seller_id,
                'items': items,
                'total_amount': sum(item['price'] * item['quantity'] for item in items),
                'status': 'CREATED',
                'created_at': datetime.utcnow().isoformat()
            },
            metadata={
                'user_id': buyer_id,
                'service': 'payment-service'
            }
        )
        
        # Persist event
        if not self.event_store.append(event):
            raise Exception("Failed to persist order event")
        
        # Publish to Kafka for other services (notification, logistics, etc.)
        self.kafka.publish('order-events', {
            'event_type': event.event_type,
            'order_id': order_id,
            'data': event.data
        })
        
        return order_id
    
    def process_payment(self, order_id: str, payment_method: str, amount: Decimal) -> Dict:
        """Process payment and emit event"""
        
        # Event 1: Payment initiated
        initiated_event = Event(
            event_type=EventType.PAYMENT_INITIATED,
            aggregate_id=order_id,
            aggregate_type="payment",
            data={
                'order_id': order_id,
                'payment_method': payment_method,
                'amount': str(amount),
                'initiated_at': datetime.utcnow().isoformat()
            }
        )
        self.event_store.append(initiated_event)
        self.kafka.publish('payment-events', initiated_event.data)
        
        # Attempt authorization
        try:
            result = self._authorize_payment(order_id, payment_method, amount)
            
            if result['status'] == 'SUCCESS':
                # Event 2: Payment authorized
                auth_event = Event(
                    event_type=EventType.PAYMENT_AUTHORIZED,
                    aggregate_id=order_id,
                    aggregate_type="payment",
                    data={
                        'order_id': order_id,
                        'authorization_code': result['auth_code'],
                        'authorized_amount': str(amount),
                        'authorized_at': datetime.utcnow().isoformat()
                    }
                )
                self.event_store.append(auth_event)
                
                # Event 3: Payment completed
                completed_event = Event(
                    event_type=EventType.PAYMENT_COMPLETED,
                    aggregate_id=order_id,
                    aggregate_type="payment",
                    data={
                        'order_id': order_id,
                        'transaction_id': result['txn_id'],
                        'amount': str(amount),
                        'completed_at': datetime.utcnow().isoformat()
                    }
                )
                self.event_store.append(completed_event)
                self.kafka.publish('payment-events', completed_event.data)
                
                return {'status': 'COMPLETED', 'order_id': order_id}
            else:
                # Event: Payment failed
                failed_event = Event(
                    event_type=EventType.PAYMENT_FAILED,
                    aggregate_id=order_id,
                    aggregate_type="payment",
                    data={
                        'order_id': order_id,
                        'reason': result.get('error_message'),
                        'failed_at': datetime.utcnow().isoformat()
                    }
                )
                self.event_store.append(failed_event)
                
                return {'status': 'FAILED', 'error': result.get('error_message')}
        
        except Exception as e:
            # Event: Payment error
            error_event = Event(
                event_type=EventType.PAYMENT_FAILED,
                aggregate_id=order_id,
                aggregate_type="payment",
                data={
                    'order_id': order_id,
                    'reason': str(e),
                    'error_code': 'SYSTEM_ERROR',
                    'failed_at': datetime.utcnow().isoformat()
                }
            )
            self.event_store.append(error_event)
            raise
    
    def rebuild_order_state(self, order_id: str) -> Dict:
        """Rebuild current order state by replaying events"""
        
        state = {
            'order_id': order_id,
            'status': 'UNKNOWN',
            'created_at': None,
            'completed_at': None,
            'events': []
        }
        
        # Get all events for this order
        events = self.event_store.get_events(order_id)
        
        # Replay events to build current state
        for event in events:
            state['events'].append({
                'type': event.event_type,
                'timestamp': event.timestamp
            })
            
            # Apply state transitions
            if event.event_type == EventType.ORDER_CREATED:
                state['status'] = 'CREATED'
                state['created_at'] = event.data['created_at']
            elif event.event_type == EventType.PAYMENT_COMPLETED:
                state['status'] = 'PAID'
                state['completed_at'] = event.data['completed_at']
            elif event.event_type == EventType.PAYMENT_FAILED:
                state['status'] = 'PAYMENT_FAILED'
            elif event.event_type == EventType.ORDER_SHIPPED:
                state['status'] = 'SHIPPED'
            elif event.event_type == EventType.ORDER_DELIVERED:
                state['status'] = 'DELIVERED'
            elif event.event_type == EventType.ORDER_CANCELLED:
                state['status'] = 'CANCELLED'
        
        return state
```

---

## 2. Circuit Breaker Pattern

### 2.1 Overview

Circuit Breaker prevents cascading failures when external services fail. Think of it as an electrical circuit breaker that trips when overloaded.

**States:**
- **CLOSED:** Normal operation, requests pass through
- **OPEN:** Service failing, requests immediately rejected
- **HALF_OPEN:** Testing if service recovered, allow limited requests

### 2.2 Implementation

```python
# services/shared/circuit_breaker.py

from enum import Enum
from datetime import datetime, timedelta
import time
import threading

class CircuitState(str, Enum):
    CLOSED = "CLOSED"      # Normal
    OPEN = "OPEN"          # Failing
    HALF_OPEN = "HALF_OPEN"  # Testing recovery

class CircuitBreakerConfig:
    def __init__(
        self,
        failure_threshold: int = 5,  # Fail after 5 consecutive errors
        recovery_timeout: int = 60,  # Try recovery after 60 seconds
        expected_exception: type = Exception,
        name: str = "CircuitBreaker"
    ):
        self.failure_threshold = failure_threshold
        self.recovery_timeout = recovery_timeout
        self.expected_exception = expected_exception
        self.name = name

class CircuitBreaker:
    def __init__(self, config: CircuitBreakerConfig):
        self.config = config
        self.state = CircuitState.CLOSED
        self.failure_count = 0
        self.last_failure_time = None
        self.lock = threading.Lock()
    
    def call(self, func, *args, **kwargs):
        """Execute function with circuit breaker protection"""
        
        with self.lock:
            if self.state == CircuitState.OPEN:
                # Check if recovery timeout passed
                if datetime.now() - self.last_failure_time > timedelta(seconds=self.config.recovery_timeout):
                    self.state = CircuitState.HALF_OPEN
                    logger.info(f"{self.config.name}: Transitioning to HALF_OPEN")
                else:
                    raise CircuitBreakerOpenException(
                        f"{self.config.name} circuit is OPEN, failing fast"
                    )
        
        try:
            result = func(*args, **kwargs)
            self._on_success()
            return result
        except self.config.expected_exception as e:
            self._on_failure()
            raise
    
    def _on_success(self):
        """Called when request succeeds"""
        with self.lock:
            self.failure_count = 0
            if self.state == CircuitState.HALF_OPEN:
                self.state = CircuitState.CLOSED
                logger.info(f"{self.config.name}: Recovered, transitioning to CLOSED")
    
    def _on_failure(self):
        """Called when request fails"""
        with self.lock:
            self.failure_count += 1
            self.last_failure_time = datetime.now()
            
            if self.failure_count >= self.config.failure_threshold:
                self.state = CircuitState.OPEN
                logger.warning(f"{self.config.name}: Failure threshold reached, opening circuit")

class CircuitBreakerOpenException(Exception):
    pass

# Decorator version for easier use
def circuit_breaker(config: CircuitBreakerConfig):
    def decorator(func):
        cb = CircuitBreaker(config)
        
        def wrapper(*args, **kwargs):
            return cb.call(func, *args, **kwargs)
        
        return wrapper
    return decorator
```

### 2.3 Usage Example

```python
# services/payment-service/stripe_gateway.py

stripe_cb_config = CircuitBreakerConfig(
    failure_threshold=5,
    recovery_timeout=60,
    expected_exception=StripeConnectionError,
    name="StripeGateway"
)

stripe_breaker = CircuitBreaker(stripe_cb_config)

class StripePaymentGateway:
    @circuit_breaker(stripe_cb_config)
    def process_payment(self, token: str, amount: Decimal) -> Dict:
        """Process payment via Stripe"""
        try:
            charge = stripe.Charge.create(
                amount=int(amount * 100),  # cents
                currency='usd',
                source=token,
                idempotency_key=f"order-{uuid.uuid4()}"
            )
            return {
                'status': 'SUCCESS',
                'txn_id': charge.id
            }
        except stripe.error.CardError as e:
            # Card declined - not circuit breaker's fault
            return {
                'status': 'FAILED',
                'error_message': str(e)
            }
        except stripe.error.APIError as e:
            # Stripe service error - trigger circuit breaker
            raise StripeConnectionError(str(e))

# When circuit is open:
try:
    result = stripe_breaker.call(
        stripe_gateway.process_payment,
        token='tok_123',
        amount=Decimal('100.00')
    )
except CircuitBreakerOpenException:
    # Stripe is down, use fallback (local processing or manual review)
    logger.warning("Stripe circuit open, using fallback payment queue")
    queue_payment_for_manual_processing(order_id)
```

---

## 3. Async Job Processing with Retries

### 3.1 Job Queue Architecture

```python
# services/shared/job_queue.py

from enum import Enum
from datetime import datetime, timedelta
import json
import kafka
import uuid

class JobStatus(str, Enum):
    PENDING = "PENDING"
    PROCESSING = "PROCESSING"
    COMPLETED = "COMPLETED"
    FAILED = "FAILED"
    RETRY = "RETRY"
    DEAD_LETTER = "DEAD_LETTER"

class Job:
    def __init__(
        self,
        job_type: str,
        payload: Dict,
        max_retries: int = 3,
        timeout_seconds: int = 300,
        job_id: str = None
    ):
        self.job_id = job_id or str(uuid.uuid4())
        self.job_type = job_type
        self.payload = payload
        self.max_retries = max_retries
        self.timeout_seconds = timeout_seconds
        self.status = JobStatus.PENDING
        self.retries = 0
        self.created_at = datetime.utcnow()
        self.started_at = None
        self.completed_at = None
        self.error = None
    
    def to_dict(self) -> Dict:
        return {
            'job_id': self.job_id,
            'job_type': self.job_type,
            'payload': self.payload,
            'status': self.status,
            'retries': self.retries,
            'max_retries': self.max_retries,
            'timeout_seconds': self.timeout_seconds,
            'created_at': self.created_at.isoformat(),
            'started_at': self.started_at.isoformat() if self.started_at else None,
            'completed_at': self.completed_at.isoformat() if self.completed_at else None,
            'error': self.error
        }

class JobQueue:
    """Async job processing with Kafka + retry logic"""
    
    def __init__(self, kafka_brokers: List[str]):
        self.producer = kafka.KafkaProducer(
            bootstrap_servers=kafka_brokers,
            value_serializer=lambda v: json.dumps(v).encode('utf-8')
        )
        self.consumer = kafka.KafkaConsumer(
            'job-queue',
            bootstrap_servers=kafka_brokers,
            group_id='job-processor',
            auto_offset_reset='earliest',
            value_deserializer=lambda m: json.loads(m.decode('utf-8'))
        )
    
    def enqueue(self, job: Job) -> str:
        """Add job to queue"""
        
        message = job.to_dict()
        
        # Publish to Kafka
        self.producer.send('job-queue', value=message)
        self.producer.flush()
        
        logger.info(f"Job enqueued: {job.job_id} ({job.job_type})")
        return job.job_id
    
    def process_jobs(self):
        """Worker: consume and process jobs"""
        
        for message in self.consumer:
            job_data = message.value
            job = self._deserialize_job(job_data)
            
            try:
                self._execute_job(job)
                job.status = JobStatus.COMPLETED
                job.completed_at = datetime.utcnow()
                logger.info(f"Job completed: {job.job_id}")
                
            except Exception as e:
                logger.error(f"Job failed: {job.job_id}, error: {str(e)}")
                job.error = str(e)
                job.retries += 1
                
                if job.retries < job.max_retries:
                    # Re-queue with backoff
                    delay_seconds = 2 ** job.retries  # Exponential backoff
                    job.status = JobStatus.RETRY
                    
                    logger.info(f"Retrying job {job.job_id} in {delay_seconds}s (attempt {job.retries}/{job.max_retries})")
                    
                    # Re-publish after delay
                    self.producer.send(
                        'job-queue-delayed',
                        value={**job.to_dict(), 'retry_delay': delay_seconds}
                    )
                else:
                    # Max retries exceeded
                    job.status = JobStatus.DEAD_LETTER
                    logger.error(f"Job exhausted retries: {job.job_id}")
                    
                    # Move to dead letter queue for manual inspection
                    self.producer.send('job-queue-dead-letter', value=job.to_dict())
    
    def _execute_job(self, job: Job):
        """Execute the actual job"""
        
        job.status = JobStatus.PROCESSING
        job.started_at = datetime.utcnow()
        
        # Get handler for job type
        handler = JOB_HANDLERS.get(job.job_type)
        if not handler:
            raise Exception(f"No handler for job type: {job.job_type}")
        
        # Execute with timeout
        handler(job.payload)

# Job handlers
class SendEmailHandler:
    @staticmethod
    def handle(payload: Dict):
        """Send transactional email"""
        email = EmailService()
        email.send(
            to=payload['email'],
            subject=payload['subject'],
            template=payload['template'],
            variables=payload.get('variables', {})
        )
        logger.info(f"Email sent to {payload['email']}")

class ProcessShipmentHandler:
    @staticmethod
    def handle(payload: Dict):
        """Create shipping label and track"""
        logistics = LogisticsService()
        label = logistics.create_shipment(
            order_id=payload['order_id'],
            destination=payload['destination'],
            weight=payload['weight']
        )
        logger.info(f"Shipment created: {label}")

JOB_HANDLERS = {
    'send-email': SendEmailHandler.handle,
    'send-sms': SMSHandler.handle,
    'process-shipment': ProcessShipmentHandler.handle,
    'generate-invoice': InvoiceHandler.handle,
    'sync-analytics': AnalyticsHandler.handle,
}
```

### 3.2 Usage

```python
# In payment-service
def process_payment(order: Order):
    # ... payment processing ...
    
    if payment_successful:
        # Queue async jobs
        job_queue = JobQueue(['kafka:9092'])
        
        # Send confirmation email
        job_queue.enqueue(Job(
            job_type='send-email',
            payload={
                'email': buyer.email,
                'subject': 'Payment Confirmed',
                'template': 'payment_confirmation',
                'variables': {
                    'order_id': order.id,
                    'amount': order.total_amount
                }
            },
            max_retries=5
        ))
        
        # Create shipment
        job_queue.enqueue(Job(
            job_type='process-shipment',
            payload={
                'order_id': order.id,
                'destination': buyer.delivery_address,
                'weight': order.weight
            },
            max_retries=3
        ))
```

---

## 4. Structured Logging

### 4.1 JSON Logging Configuration

```python
# services/shared/logging_config.py

import logging
import json
import sys
from datetime import datetime

class JSONFormatter(logging.Formatter):
    """Format logs as JSON for ELK stack"""
    
    def format(self, record: logging.LogRecord) -> str:
        log_data = {
            'timestamp': datetime.utcnow().isoformat(),
            'level': record.levelname,
            'logger': record.name,
            'message': record.getMessage(),
            'service': os.getenv('SERVICE_NAME'),
            'version': os.getenv('VERSION'),
            'environment': os.getenv('ENVIRONMENT'),
            'trace_id': getattr(record, 'trace_id', None),
            'span_id': getattr(record, 'span_id', None),
        }
        
        # Add exception info
        if record.exc_info:
            log_data['exception'] = {
                'type': record.exc_info[0].__name__,
                'message': str(record.exc_info[1]),
                'stacktrace': self.formatException(record.exc_info)
            }
        
        # Add custom fields
        if hasattr(record, 'extra'):
            log_data.update(record.extra)
        
        return json.dumps(log_data)

def setup_logging(service_name: str):
    """Initialize structured logging"""
    
    # Console handler (JSON)
    console_handler = logging.StreamHandler(sys.stdout)
    console_handler.setFormatter(JSONFormatter())
    
    # Root logger
    root_logger = logging.getLogger()
    root_logger.setLevel(logging.INFO)
    root_logger.addHandler(console_handler)
    
    logger = logging.getLogger(service_name)
    return logger

# Usage in services
logger = setup_logging('payment-service')

# Standard logging
logger.info("Payment processed", extra={
    'order_id': order.id,
    'amount': str(order.total_amount),
    'currency': 'USD',
    'duration_ms': elapsed_ms
})

# Error logging with context
logger.error("Payment failed", extra={
    'order_id': order.id,
    'error_code': 'DECLINED',
    'decline_reason': 'insufficient_funds',
    'retryable': True
})
```

### 4.2 Log Aggregation

**ELK Stack (Elasticsearch, Logstash, Kibana)**

```yaml
# infrastructure/logstash.conf

input {
  kafka {
    bootstrap_servers => "kafka:9092"
    topics => ["logs"]
    codec => json
  }
}

filter {
  # Parse JSON logs
  json {
    source => "message"
  }
  
  # Add metadata
  mutate {
    add_field => { "[@metadata][index_name]" => "%{service}-%{+YYYY.MM.dd}" }
  }
  
  # Parse stacktraces
  if [exception][stacktrace] {
    multiline {
      pattern => "^%{SPACE}"
      what => "previous"
    }
  }
}

output {
  elasticsearch {
    hosts => ["elasticsearch:9200"]
    index => "%{[@metadata][index_name]}"
  }
}
```

---

## 5. Prometheus Metrics Definitions

### 5.1 Service-Level Metrics

```python
# services/payment-service/metrics.py

from prometheus_client import Counter, Histogram, Gauge
import time

# Counters (monotonically increasing)
payments_total = Counter(
    'payments_total',
    'Total payments processed',
    ['status', 'currency', 'payment_method']  # Labels
)

fraud_checks_total = Counter(
    'fraud_checks_total',
    'Total fraud checks performed',
    ['risk_level', 'decision']  # LOW, MEDIUM, HIGH, CRITICAL / APPROVE, CHALLENGE, BLOCK
)

# Histograms (distribution of values)
payment_duration_seconds = Histogram(
    'payment_duration_seconds',
    'Time to process payment',
    buckets=(0.1, 0.5, 1.0, 2.0, 5.0, 10.0)  # Milliseconds
)

payment_amount_dollars = Histogram(
    'payment_amount_dollars',
    'Payment amount in dollars',
    buckets=(10, 100, 1000, 10000, 100000, 1000000)
)

# Gauges (point-in-time value)
escrow_balance_usd = Gauge(
    'escrow_balance_usd',
    'Total amount in escrow',
    ['currency']
)

active_disputes = Gauge(
    'active_disputes_count',
    'Number of active disputes'
)

# Usage in code
@app.post("/payments/process")
async def process_payment(order: Order):
    start_time = time.time()
    status = 'FAILED'
    
    try:
        result = payment_service.process(order)
        status = 'COMPLETED'
        payment_amount_dollars.observe(float(order.amount))
        
        # Update escrow
        escrow_balance_usd.labels(currency='USD').set(
            get_total_escrow_balance()
        )
        
    except FraudDetected as e:
        status = 'BLOCKED'
        fraud_checks_total.labels(
            risk_level=e.risk_level,
            decision='BLOCK'
        ).inc()
    except PaymentFailed as e:
        status = 'FAILED'
    
    finally:
        # Record duration
        duration = time.time() - start_time
        payment_duration_seconds.observe(duration)
        
        # Record total
        payments_total.labels(
            status=status,
            currency=order.currency,
            payment_method=order.payment_method
        ).inc()
    
    return result
```

### 5.2 Application Metrics

```python
# services/catalog-service/metrics.py

# Search metrics
searches_total = Counter(
    'searches_total',
    'Total search queries',
    ['query_type', 'result_count_bucket']  # 'keyword', 'filter', etc.
)

search_duration_ms = Histogram(
    'search_duration_ms',
    'Search query duration',
    buckets=(10, 50, 100, 500, 1000, 5000)
)

# Product metrics
products_indexed = Gauge(
    'products_indexed_count',
    'Total products in search index'
)

index_staleness_seconds = Gauge(
    'elasticsearch_index_staleness_seconds',
    'Time since last index refresh'
)

# Usage
@app.get("/catalog/search")
async def search_products(query: str, filters: Dict = None):
    start_time = time.time()
    
    results = elasticsearch.search(query, filters)
    
    duration_ms = (time.time() - start_time) * 1000
    search_duration_ms.observe(duration_ms)
    
    searches_total.labels(
        query_type='keyword' if not filters else 'filtered',
        result_count_bucket=_bucket_results(len(results))
    ).inc()
    
    return results

def _bucket_results(count: int) -> str:
    if count == 0: return '0'
    elif count < 10: return '1_to_10'
    elif count < 100: return '10_to_100'
    elif count < 1000: return '100_to_1000'
    else: return '1000_plus'
```

---

## Implementation Checklist

- [x] Event Store design and append-only log
- [x] Event-driven order processing
- [x] Circuit breaker for external services
- [x] Async job queue with retry logic
- [x] Structured JSON logging
- [x] Prometheus metrics definitions
- [ ] ELK stack deployment (ops task)
- [ ] Grafana dashboard setup (ops task)
- [ ] Event replay testing procedures
- [ ] Dead letter queue monitoring

---

**Version History:**
- v1.0 (2026-07-30): Advanced implementation patterns including event sourcing, circuit breakers, async jobs, structured logging, and Prometheus metrics.
