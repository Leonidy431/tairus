# Stripe Payment Gateway Integration

## Overview

This document describes the complete Stripe payment gateway integration for the Painting Sales website. The implementation follows PCI DSS compliance standards by using Stripe's tokenization approach instead of storing raw card data.

## Features Implemented

### 1. Payment Processing
- **CreatePaymentIntent**: Initiates Stripe payment intents for users
- **VerifyPayment**: Confirms payment status and updates transaction records
- **Refund Processing**: Handles full and partial refunds with audit logging

### 2. Payment Method Management
- Secure storage of tokenized payment methods (not raw card data)
- Support for multiple payment methods per user
- Default payment method management
- Payment method deletion with proper cleanup

### 3. Webhook Handling
- `payment_intent.succeeded`: Processes successful payments
- `payment_intent.payment_failed`: Records failed payment attempts
- `charge.refunded`: Handles refund notifications from Stripe

### 4. Audit Logging
- Complete transaction history logging
- Event tracking for compliance
- JSON-based detailed event information
- Timestamp tracking for all operations

### 5. PCI DSS Compliance
- No raw credit card data stored in database
- All card data tokenized through Stripe
- Secure webhook signature verification
- Encrypted environment variable storage for API keys

## Installation

### 1. Install Stripe PHP SDK

```bash
composer require stripe/stripe-php:^9.0
```

### 2. Configure Environment Variables

Update `.env` file with Stripe credentials:

```env
STRIPE_SECRET_KEY=sk_test_your_key_here
STRIPE_PUBLISHABLE_KEY=pk_test_your_key_here
STRIPE_WEBHOOK_SECRET=whsec_your_webhook_secret_here
```

### 3. Create Database Tables

Run the SQL migrations to create the payment-related tables:

```sql
-- From database/schema.sql
-- Creates: payment_methods, transactions, payment_logs tables
```

The tables include:

#### payment_methods
- Stores tokenized Stripe payment method IDs
- Contains masked card info (last4, brand, expiration)
- Tracks default payment method per user

#### transactions
- Records all payment transactions
- Tracks status (pending, success, failed, refunded, partially_refunded)
- Links to payment methods and orders
- Stores error messages for failed transactions

#### payment_logs
- Audit trail for all payment events
- JSON-formatted event details
- Timestamp tracking for compliance

## Usage

### Creating a Payment Intent

```php
use App\Controllers\PaymentController;

$paymentController = new PaymentController($db);

$result = $paymentController->createPaymentIntent(
    userId: 1,
    amount: 99.99,
    currency: 'USD',
    orderId: 123
);

// Response:
// {
//   "success": true,
//   "transaction_id": 1,
//   "payment_intent_id": "pi_...",
//   "client_secret": "pi_...secret",
//   "amount": 99.99,
//   "currency": "USD"
// }
```

### Verifying Payment

```php
$result = $paymentController->verifyPayment(
    transactionId: 1,
    stripePaymentIntentId: "pi_..."
);

// Response:
// {
//   "success": true,
//   "status": "succeeded",
//   "amount": 99.99,
//   "currency": "USD"
// }
```

### Processing Refunds

```php
// Full refund
$result = $paymentController->refund(transactionId: 1);

// Partial refund
$result = $paymentController->refund(transactionId: 1, amount: 50.00);

// Response:
// {
//   "success": true,
//   "refund_id": "re_...",
//   "amount": 99.99,
//   "status": "succeeded"
// }
```

### Managing Payment Methods

```php
use App\Repository\PaymentRepository;

$paymentRepo = new PaymentRepository($db);

// Save payment method (after tokenization via Stripe.js)
$paymentMethodId = $paymentRepo->savePaymentMethod([
    'user_id' => 1,
    'stripe_payment_method_id' => 'pm_...',
    'type' => 'card',
    'last4' => '4242',
    'brand' => 'visa',
    'exp_month' => 12,
    'exp_year' => 2025,
    'is_default' => 1,
]);

// Retrieve user's payment methods
$methods = $paymentRepo->getPaymentMethodsByUserId(1);

// Set default payment method
$paymentRepo->setDefaultPaymentMethod(userId: 1, paymentMethodId: 1);

// Delete payment method
$paymentRepo->deletePaymentMethod(paymentMethodId: 1, userId: 1);
```

## Webhook Configuration

### Setup Stripe Webhook

1. Log in to Stripe Dashboard
2. Navigate to Developers > Webhooks
3. Add endpoint: `https://yourdomain.com/webhook/stripe`
4. Subscribe to events:
   - `payment_intent.succeeded`
   - `payment_intent.payment_failed`
   - `charge.refunded`
5. Copy the webhook secret to `STRIPE_WEBHOOK_SECRET` env var

### Webhook Endpoint

The webhook is automatically handled at `/webhook/stripe` POST endpoint in `public/index.php`.

The controller:
- Verifies webhook signature for security
- Processes event types
- Updates transaction status
- Logs all events for audit trail

## API Classes

### PaymentRepository

Located at: `src/Repository/PaymentRepository.php`

Methods:
- `createPaymentIntent(array $data): int` - Create new payment intent
- `updateTransactionStatus(int $id, string $status, ?string $error): bool`
- `savePaymentMethod(array $data): int` - Store tokenized payment method
- `getPaymentMethodsByUserId(int $userId): array`
- `getDefaultPaymentMethod(int $userId): ?array`
- `setDefaultPaymentMethod(int $userId, int $paymentMethodId): bool`
- `deletePaymentMethod(int $paymentMethodId, int $userId): bool`
- `logPaymentEvent(int $transactionId, string $event, array $details): int`
- `getPaymentLogs(int $transactionId): array`
- `getTransactionWithLogs(int $transactionId): ?array` - Get full audit trail
- `getUserTransactions(int $userId, int $page, int $perPage): array`
- `getMonthlyStatistics(int $year, int $month): array` - Compliance reporting

### PaymentController

Located at: `src/Controllers/PaymentController.php`

Methods:
- `createPaymentIntent(int $userId, float $amount, string $currency, ?int $orderId): array`
- `verifyPayment(int $transactionId, string $stripePaymentIntentId): array`
- `refund(int $transactionId, ?float $amount): array`
- `handleWebhook(string $payload, string $signature): array`

## Testing

Run the comprehensive test suite:

```bash
# Run all payment integration tests
composer run test:integration

# Run specific test
./vendor/bin/phpunit tests/Integration/PaymentIntegrationTest.php

# Run with coverage
composer run test:coverage
```

### Test Coverage

Tests include:
1. Payment Intent Creation
2. Payment Method Storage and Retrieval
3. Default Payment Method Management
4. Transaction Status Updates
5. Payment Event Logging (Audit Trail)
6. Complete Transaction History with Logs
7. Filter Transactions by Status
8. Default Payment Method Switching

## Security Considerations

### PCI DSS Compliance

1. **No Card Data Storage**: Raw credit card numbers are never stored
2. **Tokenization Only**: All cards are tokenized via Stripe before storage
3. **Webhook Verification**: All webhooks are cryptographically verified
4. **Secure API Keys**: Keys stored in environment variables, never in code
5. **HTTPS Required**: All payment endpoints require HTTPS
6. **Error Handling**: Sensitive error details not exposed to users

### Best Practices

1. Always use HTTPS for payment pages
2. Implement rate limiting on payment endpoints
3. Monitor webhook delivery for failures
4. Regularly audit payment logs
5. Test refund procedures periodically
6. Keep Stripe SDK updated
7. Validate all input before processing

## Error Handling

The payment controller provides detailed error messages for debugging while maintaining security:

```php
$result = $paymentController->createPaymentIntent(1, 99.99);

if (!$result['success']) {
    // Log error for debugging
    error_log($result['error']);
    
    // Show user-friendly message
    $userMessage = 'Payment processing failed. Please try again.';
}
```

## Audit Trail Example

Complete transaction with logs:

```php
$transaction = $paymentRepo->getTransactionWithLogs(1);

// Returns:
// {
//   "id": 1,
//   "user_id": 1,
//   "amount": 99.99,
//   "status": "success",
//   "created_at": "2026-01-15 10:30:00",
//   "logs": [
//     {
//       "id": 1,
//       "event": "payment_intent_created",
//       "details": "{\"stripe_payment_intent_id\": \"pi_...\"}",
//       "created_at": "2026-01-15 10:30:00"
//     },
//     {
//       "id": 2,
//       "event": "payment_intent.succeeded",
//       "details": "{\"amount_received\": 9999}",
//       "created_at": "2026-01-15 10:31:05"
//     }
//   ]
// }
```

## Future Enhancements

1. Subscription billing integration
2. Invoice generation
3. Payment analytics dashboard
4. Multiple currency support
5. Alternative payment methods (Apple Pay, Google Pay)
6. 3D Secure authentication
7. Recurring payment automation

## Support and Documentation

- [Stripe PHP SDK Documentation](https://stripe.com/docs/libraries/php)
- [Stripe API Reference](https://stripe.com/docs/api)
- [Stripe Webhooks Guide](https://stripe.com/docs/webhooks)
- [PCI DSS Compliance](https://stripe.com/docs/security)

## Troubleshooting

### Common Issues

**Issue**: "STRIPE_SECRET_KEY environment variable not set"
- Solution: Add STRIPE_SECRET_KEY to .env file

**Issue**: Webhook signature verification fails
- Solution: Ensure STRIPE_WEBHOOK_SECRET matches your Stripe dashboard

**Issue**: Payment method not saving
- Solution: Verify payment_methods table exists and user_id is valid

**Issue**: Transaction not updating from webhook
- Solution: Check webhook delivery in Stripe dashboard, verify logs table

## File Structure

```
src/
├── Controllers/
│   └── PaymentController.php       # Payment processing & webhooks
├── Repository/
│   └── PaymentRepository.php       # Payment data operations
database/
├── schema.sql                       # Database tables definition
tests/
├── Integration/
│   └── PaymentIntegrationTest.php  # Comprehensive test suite
public/
├── index.php                        # Webhook endpoint handler
.env.example                         # Stripe configuration template
composer.json                        # Stripe SDK dependency
```

## License

This payment integration is part of the Painting Sales Website project and follows the project's license terms.
