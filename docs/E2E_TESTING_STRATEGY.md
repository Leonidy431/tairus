# E2E Testing Strategy for TAIRUS Marketplace
**Version:** 1.0  
**Last Updated:** 2026-07-30  
**Framework:** Jest + Playwright + k6 (performance)

---

## Overview

The TAIRUS platform requires comprehensive end-to-end testing across 4 dimensions:
1. **Happy Path** - Core workflows users rely on
2. **Failure Scenarios** - Error handling & recovery
3. **Security** - Authentication, fraud, compliance
4. **Performance** - Load testing, stress testing

**Coverage Target:** 95% of critical user paths

---

## 1. Test Environment Setup

### Test Data Generation
```python
# services/payment-service/tests/fixtures/test_data.py

from faker import Faker
from decimal import Decimal
import random

fake = Faker('ru_RU')

class TestDataFactory:
    """Generate consistent test data across environments"""
    
    @staticmethod
    def create_test_buyer(kyc_status='verified'):
        return {
            'id': fake.uuid4(),
            'email': f"buyer-{fake.random_int()}@test-tairus.com",
            'name': fake.name(),
            'phone': fake.phone_number(),
            'company': fake.company(),
            'country': random.choice(['RU', 'US', 'CN', 'IN']),
            'kyc_status': kyc_status,
            'credit_limit': Decimal('100000'),
        }
    
    @staticmethod
    def create_test_seller(verification_level='verified'):
        return {
            'id': fake.uuid4(),
            'email': f"seller-{fake.random_int()}@test-tairus.com",
            'name': fake.company(),
            'registration_number': fake.ssn(),
            'verification_level': verification_level,
            'payment_method': random.choice(['bank_transfer', 'stripe', 'sberbank']),
            'reputation_score': random.uniform(3.5, 5.0),
        }
    
    @staticmethod
    def create_test_product():
        return {
            'id': fake.uuid4(),
            'name': random.choice([
                'Brent Crude Oil 100 BBL',
                'Stainless Steel 304 1000 MT',
                'Wheat Grade A 500 MT',
                'Copper Wire Coil 50 MT'
            ]),
            'category': random.choice(['oil_gas', 'metals', 'agriculture', 'chemicals']),
            'price_per_unit': Decimal(random.uniform(100, 10000)),
            'unit': random.choice(['barrel', 'ton', 'kg', 'liter']),
            'quantity': random.randint(10, 1000),
            'origin_country': random.choice(['RU', 'US', 'AU', 'CN']),
        }
    
    @staticmethod
    def create_test_rfq(buyer_id, seller_id):
        product = TestDataFactory.create_test_product()
        return {
            'id': fake.uuid4(),
            'buyer_id': buyer_id,
            'seller_id': seller_id,
            'product': product,
            'quantity_requested': random.randint(10, 500),
            'delivery_location': fake.address(),
            'required_by': fake.date_time_this_month(),
            'status': 'open',
        }
```

### Test Database
```bash
# tests/docker-compose.test.yml
version: '3.8'
services:
  postgres-test:
    image: postgres:15-alpine
    environment:
      POSTGRES_DB: tairus_test
      POSTGRES_PASSWORD: test_password
    ports:
      - "5433:5432"
    volumes:
      - test_postgres_data:/var/lib/postgresql/data
      - ../database/schema.sql:/docker-entrypoint-initdb.d/01-schema.sql
      - ../database/test_seed.sql:/docker-entrypoint-initdb.d/02-seed.sql

  redis-test:
    image: redis:7-alpine
    ports:
      - "6380:6379"

  elasticsearch-test:
    image: docker.elastic.co/elasticsearch/elasticsearch:8.0.0
    environment:
      discovery.type: single-node
      xpack.security.enabled: "false"
    ports:
      - "9201:9200"
```

---

## 2. Happy Path Test Scenarios

### Scenario 1: Complete RFQ-to-Payment Flow
```javascript
// tests/e2e/happy-paths/rfq-to-payment.test.js

import { test, expect } from '@playwright/test';
import { createTestBuyer, createTestSeller, createTestProduct } from '../fixtures/test-data';

test.describe('RFQ to Payment - Happy Path', () => {
  let buyer, seller, rfqId;

  test.beforeEach(async ({ page, context }) => {
    buyer = await createTestBuyer('verified');
    seller = await createTestSeller('certified');
  });

  test('Buyer searches and finds product', async ({ page }) => {
    await page.goto('https://marketplace.test/');
    
    // Search for oil products
    await page.fill('input[name="q"]', 'Brent Crude Oil');
    await page.click('button[type="submit"]');
    
    // Verify results show
    await expect(page.locator('text=Brent Crude Oil')).toBeVisible();
    await expect(page.locator('.product-card')).toHaveCount(5, { timeout: 5000 });
  });

  test('Buyer creates RFQ', async ({ page, context }) => {
    // Login as buyer
    await page.goto('https://marketplace.test/login');
    await page.fill('input[name="email"]', buyer.email);
    await page.fill('input[name="password"]', 'Test@1234');
    await page.click('button:has-text("Sign In")');
    
    // Navigate to product
    await page.goto(`https://marketplace.test/products/${seller.id}/brent-crude`);
    
    // Create RFQ
    await page.click('button:has-text("Request Quote")');
    await page.fill('input[name="quantity"]', '100');
    await page.fill('input[name="delivery_address"]', 'Moscow, RF');
    await page.selectOption('select[name="delivery_date"]', 'WEEK_2');
    
    // Submit
    await page.click('button:has-text("Submit RFQ")');
    
    // Verify confirmation
    await expect(page.locator('text=Request received')).toBeVisible();
    
    // Extract RFQ ID from URL or page
    const rfqUrl = page.url();
    rfqId = new URL(rfqUrl).searchParams.get('rfq_id');
  });

  test('Seller receives and responds to RFQ', async ({ page, context }) => {
    // Login as seller
    await page.goto('https://marketplace.test/login');
    await page.fill('input[name="email"]', seller.email);
    await page.fill('input[name="password"]', 'Test@1234');
    await page.click('button:has-text("Sign In")');
    
    // Navigate to RFQ
    await page.goto(`https://marketplace.test/seller/rfqs/${rfqId}`);
    
    // Send quote
    await page.fill('input[name="price_per_unit"]', '82.50');
    await page.fill('input[name="delivery_days"]', '10');
    await page.fill('textarea[name="terms"]', 'Standard payment terms');
    
    await page.click('button:has-text("Send Quote")');
    
    // Verify quote sent
    await expect(page.locator('text=Quote sent successfully')).toBeVisible();
  });

  test('Buyer reviews and accepts quote', async ({ page }) => {
    // Login as buyer
    await page.goto('https://marketplace.test/login');
    await page.fill('input[name="email"]', buyer.email);
    await page.fill('input[name="password"]', 'Test@1234');
    await page.click('button:has-text("Sign In")');
    
    // Navigate to RFQ
    await page.goto(`https://marketplace.test/buyer/rfqs/${rfqId}`);
    
    // Review quote
    await expect(page.locator('text=$82.50/barrel')).toBeVisible();
    
    // Accept quote
    await page.click('button:has-text("Accept Quote")');
    
    // Create order
    await page.fill('input[name="purchase_order"]', 'PO-2026-0001');
    await page.click('button:has-text("Create Order")');
    
    // Verify order created
    const orderId = new URL(page.url()).pathname.split('/').pop();
    await expect(page.locator('text=Order created')).toBeVisible();
  });

  test('Complete payment transaction', async ({ page }) => {
    const orderId = 'TEST-ORD-001';
    
    // Login as buyer
    await page.goto('https://marketplace.test/login');
    await page.fill('input[name="email"]', buyer.email);
    await page.fill('input[name="password"]', 'Test@1234');
    await page.click('button:has-text("Sign In")');
    
    // Go to order
    await page.goto(`https://marketplace.test/orders/${orderId}`);
    
    // Initiate payment
    await page.click('button:has-text("Pay Now")');
    
    // Fraud check should pass (test data is clean)
    await expect(page.locator('text=Processing payment')).toBeVisible();
    
    // Complete payment (Stripe test mode)
    await page.fill('input[name="cardNumber"]', '4242 4242 4242 4242');
    await page.fill('input[name="expiry"]', '12/26');
    await page.fill('input[name="cvc"]', '123');
    
    await page.click('button:has-text("Complete Payment")');
    
    // Verify success
    await expect(page.locator('text=Payment successful')).toBeVisible({ timeout: 10000 });
  });

  test('Verify funds in seller account', async ({ page, request }) => {
    // Check via API
    const response = await request.get(
      `https://api.test/v1/sellers/${seller.id}/balance`,
      {
        headers: { Authorization: `Bearer ${seller.token}` }
      }
    );
    
    expect(response.status()).toBe(200);
    const data = await response.json();
    
    // Escrow should hold funds
    expect(data.escrow_balance).toBeGreaterThan(0);
    expect(data.available_balance).toBe(0); // Not released yet
  });
});
```

### Scenario 2: Dispute Resolution Flow
```javascript
// tests/e2e/happy-paths/dispute-flow.test.js

test.describe('Dispute Resolution Flow', () => {
  let buyer, seller, orderId;

  test('Buyer files dispute for non-delivery', async ({ page, request }) => {
    // Setup: Create completed order with escrow
    const order = await createTestOrder(buyer.id, seller.id);
    orderId = order.id;
    
    // Navigate to order details
    await page.goto(`https://marketplace.test/orders/${orderId}`);
    
    // File dispute (delivery deadline passed)
    await page.click('button:has-text("File Dispute")');
    await page.selectOption('select[name="reason"]', 'NON_DELIVERY');
    await page.fill('textarea[name="description"]', 'Goods not received after 30 days');
    await page.click('button:has-text("Submit Dispute")');
    
    // Verify dispute created
    await expect(page.locator('text=Dispute submitted')).toBeVisible();
  });

  test('Seller responds to dispute', async ({ page }) => {
    // Login as seller
    await page.goto('https://marketplace.test/login');
    await page.fill('input[name="email"]', seller.email);
    await page.fill('input[name="password"]', 'Test@1234');
    await page.click('button:has-text("Sign In")');
    
    // Navigate to disputes
    await page.goto('https://marketplace.test/seller/disputes');
    await page.click(`text=${orderId}`);
    
    // Submit evidence
    const fileInput = page.locator('input[type="file"]');
    await fileInput.setInputFiles('tests/fixtures/shipping_proof.pdf');
    
    await page.fill('textarea[name="response"]', 'Goods shipped via DHL, tracking number provided');
    await page.click('button:has-text("Submit Response")');
    
    await expect(page.locator('text=Response submitted')).toBeVisible();
  });

  test('Dispute resolved in buyer favor', async ({ page, request }) => {
    // Admin resolves dispute
    const response = await request.post(
      `https://api.test/v1/disputes/${orderId}/resolve`,
      {
        headers: { Authorization: `Bearer ${adminToken}` },
        data: {
          resolution: 'BUYER_WINS',
          refund_amount: order.total_amount,
          reason: 'No evidence of delivery from seller'
        }
      }
    );
    
    expect(response.status()).toBe(200);
    
    // Verify refund processed
    const result = await response.json();
    expect(result.refund_status).toBe('COMPLETED');
    expect(result.refund_amount).toBe(order.total_amount);
  });
});
```

---

## 3. Failure Scenario Tests

### Scenario 3: Payment Failure & Retry
```javascript
// tests/e2e/failure-scenarios/payment-failure.test.js

test.describe('Payment Failure & Retry', () => {
  test('Payment fails with insufficient funds', async ({ page, request }) => {
    const order = await createTestOrder(buyer.id, seller.id);
    
    // Attempt payment with insufficient funds card (Stripe test)
    await page.goto(`https://marketplace.test/orders/${order.id}`);
    await page.click('button:has-text("Pay Now")');
    
    // Use Stripe test card for insufficient funds
    await page.fill('input[name="cardNumber"]', '4000002060000005');
    await page.fill('input[name="expiry"]', '12/26');
    await page.fill('input[name="cvc"]', '123');
    
    await page.click('button:has-text("Complete Payment")');
    
    // Should show error
    await expect(page.locator('text=Insufficient funds')).toBeVisible();
    
    // Order status should be PAYMENT_FAILED
    const orderResponse = await request.get(
      `https://api.test/v1/orders/${order.id}`,
      { headers: { Authorization: `Bearer ${buyer.token}` } }
    );
    
    const orderData = await orderResponse.json();
    expect(orderData.status).toBe('PAYMENT_FAILED');
  });

  test('User can retry payment', async ({ page }) => {
    const order = await createTestOrder(buyer.id, seller.id);
    
    // First attempt fails
    await attemptPaymentWithBadCard(page, order.id, '4000002060000005');
    
    // Retry with valid card
    await page.click('button:has-text("Retry Payment")');
    
    await page.fill('input[name="cardNumber"]', '4242424242424242');
    await page.fill('input[name="expiry"]', '12/26');
    await page.fill('input[name="cvc"]', '123');
    
    await page.click('button:has-text("Complete Payment")');
    
    // Should succeed
    await expect(page.locator('text=Payment successful')).toBeVisible({ timeout: 10000 });
  });

  test('Idempotency prevents double-charging', async ({ request }) => {
    const order = await createTestOrder(buyer.id, seller.id);
    const idempotencyKey = `test-payment-${Date.now()}`;
    
    // Send same request twice with same idempotency key
    const response1 = await request.post(
      `https://api.test/v1/payments`,
      {
        headers: {
          Authorization: `Bearer ${buyer.token}`,
          'Idempotency-Key': idempotencyKey
        },
        data: {
          order_id: order.id,
          amount: order.total_amount,
          currency: 'USD'
        }
      }
    );
    
    const response2 = await request.post(
      `https://api.test/v1/payments`,
      {
        headers: {
          Authorization: `Bearer ${buyer.token}`,
          'Idempotency-Key': idempotencyKey
        },
        data: {
          order_id: order.id,
          amount: order.total_amount,
          currency: 'USD'
        }
      }
    );
    
    // Both should return same payment ID (not create duplicate)
    const payment1 = await response1.json();
    const payment2 = await response2.json();
    
    expect(payment1.payment_id).toBe(payment2.payment_id);
    expect(payment1.status).toBe('COMPLETED');
    expect(payment2.status).toBe('COMPLETED');
    
    // Verify only one charge
    const paymentsResponse = await request.get(
      `https://api.test/v1/orders/${order.id}/payments`,
      { headers: { Authorization: `Bearer ${buyer.token}` } }
    );
    
    const payments = await paymentsResponse.json();
    expect(payments).toHaveLength(1);
  });
});
```

### Scenario 4: Authentication & Authorization Failures
```javascript
// tests/e2e/failure-scenarios/auth-failures.test.js

test.describe('Authentication Failures', () => {
  test('Login with incorrect password fails', async ({ page }) => {
    await page.goto('https://marketplace.test/login');
    await page.fill('input[name="email"]', 'user@test.com');
    await page.fill('input[name="password"]', 'WrongPassword123');
    
    await page.click('button:has-text("Sign In")');
    
    // Should show error
    await expect(page.locator('text=Invalid credentials')).toBeVisible();
    
    // Should still be on login page
    expect(page.url()).toContain('/login');
  });

  test('Rate limiting blocks brute force attempts', async ({ page, request }) => {
    const email = 'attacker@test.com';
    let blockedAt = null;
    
    // Attempt login 6 times (limit is 5/minute)
    for (let i = 0; i < 6; i++) {
      const response = await request.post('https://api.test/v1/auth/login', {
        data: { email, password: 'WrongPassword' }
      });
      
      if (response.status() === 429) {
        blockedAt = i;
        break;
      }
    }
    
    // Should be blocked after 5 attempts
    expect(blockedAt).toBe(5);
  });

  test('Expired token rejected', async ({ page, request }) => {
    // Use token from 1 year ago
    const expiredToken = generateToken({ exp: Math.floor(Date.now() / 1000) - 31536000 });
    
    const response = await request.get(
      'https://api.test/v1/user/profile',
      {
        headers: { Authorization: `Bearer ${expiredToken}` }
      }
    );
    
    expect(response.status()).toBe(401);
    const error = await response.json();
    expect(error.error).toBe('Token expired');
  });

  test('2FA requirement blocks access', async ({ page }) => {
    const user = await createTestUser({ two_fa_enabled: true });
    
    // Normal login should require 2FA
    await page.goto('https://marketplace.test/login');
    await page.fill('input[name="email"]', user.email);
    await page.fill('input[name="password"]', 'Test@1234');
    
    await page.click('button:has-text("Sign In")');
    
    // Should redirect to 2FA page
    await expect(page.locator('text=Enter 2FA code')).toBeVisible();
    
    // Invalid code should fail
    await page.fill('input[name="code"]', '000000');
    await page.click('button:has-text("Verify")');
    
    await expect(page.locator('text=Invalid code')).toBeVisible();
    
    // Valid code should work
    const validCode = user.mfa_secret.totp(); // Generate TOTP
    await page.fill('input[name="code"]', validCode);
    await page.click('button:has-text("Verify")');
    
    await expect(page.locator('text=Dashboard')).toBeVisible();
  });
});
```

---

## 4. Security Testing

### Scenario 5: Fraud Detection
```javascript
// tests/e2e/security/fraud-detection.test.js

test.describe('Fraud Detection', () => {
  test('Blocks impossible travel fraud', async ({ page, request }) => {
    const user = await createTestUser();
    
    // Simulate login from Moscow
    await request.post('https://api.test/v1/auth/login', {
      data: { email: user.email, password: 'Test@1234' },
      headers: { 'X-Forwarded-For': '195.134.31.0' } // Moscow IP
    });
    
    // Immediately attempt transaction from Singapore (impossible travel)
    const fraudResponse = await request.post(
      'https://api.test/v1/payments/process',
      {
        headers: {
          Authorization: `Bearer ${user.token}`,
          'X-Forwarded-For': '1.179.83.0' // Singapore IP
        },
        data: {
          order_id: 'TEST-ORD-001',
          amount: 10000
        }
      }
    );
    
    expect(fraudResponse.status()).toBe(200);
    const payment = await fraudResponse.json();
    
    // Should be blocked by fraud detector
    expect(payment.status).toBe('BLOCKED');
    expect(payment.reason).toContain('Impossible travel');
  });

  test('Challenges large amount transactions', async ({ page }) => {
    const user = await createTestUser({ kyc_verified: true });
    
    // Large transaction should require 2FA challenge
    await page.goto(`https://marketplace.test/orders/LARGE-ORD-001`);
    
    // Initiate payment for $100,000
    await page.click('button:has-text("Pay $100,000")');
    
    // Should request 2FA
    await expect(page.locator('text=Verify with 2FA')).toBeVisible();
    
    // Complete 2FA
    const code = user.mfa_secret.totp();
    await page.fill('input[name="code"]', code);
    await page.click('button:has-text("Verify")');
    
    // Then proceed to payment
    await expect(page.locator('text=Payment method')).toBeVisible();
  });

  test('Detects high velocity attacks', async ({ request }) => {
    const user = await createTestUser();
    const results = [];
    
    // Attempt 15 transactions in 60 seconds
    for (let i = 0; i < 15; i++) {
      const order = await createTestOrder(user.id, 'SELLER-001');
      
      const response = await request.post(
        'https://api.test/v1/payments/process',
        {
          headers: { Authorization: `Bearer ${user.token}` },
          data: { order_id: order.id, amount: order.total_amount }
        }
      );
      
      const payment = await response.json();
      results.push(payment.status);
    }
    
    // Early transactions should succeed, later ones blocked
    expect(results[0]).toBe('COMPLETED');
    expect(results[14]).toBe('BLOCKED'); // After 10 in 60 seconds
  });
});
```

### Scenario 6: Compliance Verification
```javascript
// tests/e2e/security/compliance.test.js

test.describe('Compliance & Sanctions Screening', () => {
  test('Blocks sanctioned entities', async ({ request }) => {
    // Try to create seller with sanctioned name
    const response = await request.post(
      'https://api.test/v1/sellers/register',
      {
        data: {
          email: 'sanctioned@test.com',
          name: 'Viktor Orlov',  // Fake but realistic-sounding Russian name
          country: 'RU'
        }
      }
    );
    
    expect(response.status()).toBe(200);
    const result = await response.json();
    
    // Registration created but marked for manual review
    expect(result.status).toBe('PENDING_REVIEW');
    expect(result.compliance_status).toBe('SANCTIONS_FLAGGED');
  });

  test('Requires KYC before high-value transactions', async ({ request }) => {
    const buyer = await createTestUser({ kyc_status: 'UNVERIFIED' });
    
    // Attempt large transaction
    const response = await request.post(
      'https://api.test/v1/payments/process',
      {
        headers: { Authorization: `Bearer ${buyer.token}` },
        data: {
          order_id: 'LARGE-ORD-001',
          amount: 50000 // $50k - above unverified limit
        }
      }
    );
    
    const payment = await response.json();
    expect(payment.status).toBe('BLOCKED');
    expect(payment.reason).toContain('KYC verification required');
  });

  test('Validates beneficial ownership for company sellers', async ({ request }) => {
    const seller = await createTestSeller();
    
    // Submit KYC with company ownership
    const response = await request.post(
      `https://api.test/v1/sellers/${seller.id}/kyc/submit`,
      {
        headers: { Authorization: `Bearer ${seller.token}` },
        data: {
          company_name: 'Acme Oil Trading LLC',
          registration_number: '123456789',
          beneficial_owners: [
            {
              name: 'John Doe',
              ownership_percent: 50,
              passport: 'base64_encoded_image'
            },
            {
              name: 'Jane Smith',
              ownership_percent: 50,
              passport: 'base64_encoded_image'
            }
          ]
        }
      }
    );
    
    expect(response.status()).toBe(200);
    const result = await response.json();
    
    // Should require document verification
    expect(result.kyc_status).toBe('DOCUMENTS_SUBMITTED');
    expect(result.required_actions).toContain('VERIFY_BENEFICIAL_OWNERS');
  });
});
```

---

## 5. Performance & Load Testing

### K6 Load Testing Scripts
```javascript
// tests/load/ramp-up.js - Gradual load increase

import http from 'k6/http';
import { check, sleep } from 'k6';

export let options = {
  stages: [
    { duration: '1m', target: 10 },      // 1m, 10 users
    { duration: '5m', target: 100 },     // 5m, ramp up to 100 users
    { duration: '10m', target: 500 },    // 10m, ramp up to 500 users
    { duration: '5m', target: 1000 },    // 5m, ramp up to 1000 users (peak)
    { duration: '5m', target: 0 },       // 5m, ramp down to 0 users
  ],
  thresholds: {
    http_req_duration: ['p(95)<500', 'p(99)<1000'],
    http_req_failed: ['rate<0.05'],
  },
};

export default function () {
  const baseUrl = 'https://api.test';
  
  // Simulate user browsing catalog
  let res = http.get(`${baseUrl}/v1/catalog/search?q=oil`);
  check(res, {
    'search succeeds': (r) => r.status === 200,
    'search latency < 500ms': (r) => r.timings.duration < 500,
  });
  
  sleep(1);
  
  // Click on product
  res = http.get(`${baseUrl}/v1/products/PROD-001`);
  check(res, { 'product loads': (r) => r.status === 200 });
  
  sleep(2);
  
  // Create RFQ
  res = http.post(`${baseUrl}/v1/rfqs`, {
    headers: { 'Authorization': `Bearer ${__ENV.USER_TOKEN}` },
    body: JSON.stringify({
      product_id: 'PROD-001',
      quantity: 100,
      delivery_location: 'Moscow',
    }),
  });
  check(res, { 'RFQ created': (r) => r.status === 201 });
  
  sleep(1);
}
```

### Stress Test
```javascript
// tests/load/stress-test.js - Maximum load until failure

import http from 'k6/http';
import { check } from 'k6';

export let options = {
  stages: [
    { duration: '2m', target: 100 },
    { duration: '5m', target: 1000 },
    { duration: '10m', target: 5000 },   // Stress point
    { duration: '5m', target: 10000 },   // Beyond capacity
    { duration: '5m', target: 0 },
  ],
  thresholds: {
    http_req_failed: ['rate<0.10'],  // Allow 10% failure under stress
  },
};

export default function () {
  const baseUrl = 'https://api.test';
  
  // Hammer search endpoint
  let res = http.get(`${baseUrl}/v1/catalog/search?q=oil&limit=100`);
  
  check(res, {
    'endpoint responding': (r) => r.status < 500,
    'response time < 2s': (r) => r.timings.duration < 2000,
  });
}
```

### Database Load Test
```javascript
// tests/load/database-load.js - Heavy database operations

import http from 'k6/http';
import { batch } from 'k6/http';

export let options = {
  stages: [
    { duration: '1m', target: 50 },
  ],
  thresholds: {
    http_req_duration: ['p(95)<1000'],
  },
};

export default function () {
  const baseUrl = 'https://api.test';
  const token = __ENV.USER_TOKEN;
  
  // Batch multiple database queries
  let responses = batch([
    ['GET', `${baseUrl}/v1/orders?skip=0&limit=100`],
    ['GET', `${baseUrl}/v1/transactions?skip=0&limit=100`],
    ['GET', `${baseUrl}/v1/sellers?skip=0&limit=100`],
    ['GET', `${baseUrl}/v1/disputes?status=open`],
  ]);
  
  responses.forEach(r => {
    check(r, {
      'batch query succeeds': (res) => res.status === 200,
    });
  });
}
```

---

## 6. Test Execution & CI/CD

### GitHub Actions Workflow
```yaml
# .github/workflows/e2e-tests.yml

name: E2E Tests

on:
  push:
    branches: [main, develop]
  pull_request:
    branches: [main, develop]

jobs:
  e2e-tests:
    runs-on: ubuntu-latest
    
    services:
      postgres:
        image: postgres:15
        env:
          POSTGRES_PASSWORD: test_password
        options: >-
          --health-cmd pg_isready
          --health-interval 10s
          --health-timeout 5s
          --health-retries 5
      
      redis:
        image: redis:7
        options: >-
          --health-cmd "redis-cli ping"
          --health-interval 10s
          --health-timeout 5s
          --health-retries 5
    
    steps:
      - uses: actions/checkout@v3
      
      - name: Setup Node
        uses: actions/setup-node@v3
        with:
          node-version: 18
          cache: npm
      
      - name: Install dependencies
        run: npm ci
      
      - name: Start services
        run: docker-compose -f tests/docker-compose.test.yml up -d
      
      - name: Wait for services
        run: npm run test:wait-for-services
      
      - name: Run E2E tests
        run: npm run test:e2e
        env:
          API_URL: http://localhost:8000
          DB_HOST: localhost
          DB_PORT: 5433
      
      - name: Upload test results
        if: failure()
        uses: actions/upload-artifact@v3
        with:
          name: test-results
          path: test-results/
      
      - name: Comment on PR
        if: github.event_name == 'pull_request'
        uses: actions/github-script@v6
        with:
          script: |
            const fs = require('fs');
            const results = JSON.parse(fs.readFileSync('test-results/summary.json'));
            github.rest.issues.createComment({
              issue_number: context.issue.number,
              owner: context.repo.owner,
              repo: context.repo.repo,
              body: `✅ E2E Tests: ${results.passed}/${results.total} passed`
            });
```

### Local Test Execution
```bash
# Run all E2E tests
npm run test:e2e

# Run specific test file
npm run test:e2e -- tests/e2e/happy-paths/rfq-to-payment.test.js

# Run with UI mode (watch changes)
npm run test:e2e -- --ui

# Generate HTML report
npm run test:e2e -- --reporter=html

# Performance/load tests
k6 run tests/load/ramp-up.js

# Stress test
k6 run tests/load/stress-test.js --vus 10000 --duration 10m
```

---

## 7. Test Data Management

### Seeding Test Database
```sql
-- tests/fixtures/seed.sql

-- Create test users
INSERT INTO users (id, email, password_hash, user_type, kyc_status, created_at)
VALUES 
  ('buyer-1', 'buyer@test.com', 'hash...', 'buyer', 'verified', NOW()),
  ('seller-1', 'seller@test.com', 'hash...', 'seller', 'verified', NOW());

-- Create test products
INSERT INTO products (id, seller_id, name, category, price, quantity, created_at)
VALUES
  ('prod-1', 'seller-1', 'Brent Crude Oil', 'oil_gas', 82.50, 1000, NOW());

-- Create test RFQs
INSERT INTO rfqs (id, buyer_id, seller_id, product_id, quantity, status, created_at)
VALUES
  ('rfq-1', 'buyer-1', 'seller-1', 'prod-1', 100, 'open', NOW());
```

---

## 8. Monitoring Test Results

### Reporting Dashboard
- **Test Coverage:** 95% of critical paths
- **Pass Rate:** Target 99%+ for main branch
- **Performance:** P95 latency tracked per endpoint
- **Failure Rate:** Alert if > 1%

### Metrics to Track
| Metric | Target | Alert |
|--------|--------|-------|
| E2E test pass rate | 99% | < 95% |
| Happy path completion | 100% | Any failure |
| Fraud detection accuracy | 98% | < 95% |
| Performance P95 | < 500ms | > 1000ms |
| Load test throughput | 1000 RPS | < 500 RPS |

---

**Implementation Checklist:**
- [x] Test framework setup (Playwright)
- [x] Happy path scenarios (RFQ→Payment)
- [x] Failure scenarios (payment retry, disputes)
- [x] Security tests (fraud, compliance, auth)
- [x] Performance tests (k6 load/stress)
- [x] CI/CD integration (GitHub Actions)
- [ ] Dashboard & reporting setup

---

**Version History:**
- v1.0 (2026-07-30): E2E testing strategy with 6 test scenarios, load testing scripts, and CI/CD integration
