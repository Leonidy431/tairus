<?php
/**
 * Payment Controller
 *
 * Handles Stripe payment processing, webhooks, and payment-related operations.
 * Implements PCI DSS compliance by using Stripe tokenization exclusively.
 */

namespace App\Controllers;

use App\Repository\PaymentRepository;
use App\Database\Database;
use Stripe\Stripe;
use Stripe\Exception\ApiErrorException;
use Stripe\PaymentIntent;
use Stripe\Charge;

class PaymentController
{
    private PaymentRepository $paymentRepo;
    private Database $db;
    private string $stripeSecretKey;
    private string $stripeWebhookSecret;

    public function __construct(Database $db)
    {
        $this->db = $db;
        $this->paymentRepo = new PaymentRepository($db);

        // Initialize Stripe API key from environment
        $this->stripeSecretKey = env('STRIPE_SECRET_KEY', '');
        $this->stripeWebhookSecret = env('STRIPE_WEBHOOK_SECRET', '');

        if (empty($this->stripeSecretKey)) {
            throw new \Exception('STRIPE_SECRET_KEY environment variable not set');
        }

        Stripe::setApiKey($this->stripeSecretKey);
    }

    /**
     * Create a payment intent for Stripe payment
     *
     * @param int $userId
     * @param decimal $amount
     * @param string $currency
     * @param int|null $orderId
     * @return array Payment intent details
     */
    public function createPaymentIntent(int $userId, float $amount, string $currency = 'USD', ?int $orderId = null): array
    {
        try {
            // Validate user exists
            if (!$this->paymentRepo->userExists($userId)) {
                throw new \Exception('Invalid user ID');
            }

            // Validate amount
            if ($amount <= 0) {
                throw new \Exception('Invalid payment amount');
            }

            // Convert amount to cents for Stripe
            $amountInCents = (int)round($amount * 100);

            // Create Stripe PaymentIntent
            $paymentIntent = PaymentIntent::create([
                'amount' => $amountInCents,
                'currency' => strtolower($currency),
                'metadata' => [
                    'user_id' => $userId,
                    'order_id' => $orderId ?? 'null',
                ],
            ]);

            // Record transaction in database
            $transactionId = $this->paymentRepo->createPaymentIntent([
                'user_id' => $userId,
                'order_id' => $orderId,
                'amount' => $amount,
                'currency' => $currency,
                'stripe_transaction_id' => $paymentIntent->id,
                'status' => 'pending',
            ]);

            // Log the event
            $this->paymentRepo->logPaymentEvent($transactionId, 'payment_intent_created', [
                'stripe_payment_intent_id' => $paymentIntent->id,
                'amount' => $amount,
                'currency' => $currency,
            ]);

            return [
                'success' => true,
                'transaction_id' => $transactionId,
                'payment_intent_id' => $paymentIntent->id,
                'client_secret' => $paymentIntent->client_secret,
                'amount' => $amount,
                'currency' => $currency,
            ];
        } catch (ApiErrorException $e) {
            $errorMessage = $e->getMessage();
            error_log("Stripe API Error: {$errorMessage}");

            return [
                'success' => false,
                'error' => 'Payment processing failed. Please try again.',
                'error_details' => $errorMessage,
            ];
        } catch (\Exception $e) {
            $errorMessage = $e->getMessage();
            error_log("Payment Error: {$errorMessage}");

            return [
                'success' => false,
                'error' => $errorMessage,
            ];
        }
    }

    /**
     * Verify and confirm a payment
     *
     * @param int $transactionId
     * @param string $stripePaymentIntentId
     * @return array Verification result
     */
    public function verifyPayment(int $transactionId, string $stripePaymentIntentId): array
    {
        try {
            // Get transaction from database
            $transaction = $this->paymentRepo->find($transactionId);

            if (!$transaction) {
                return [
                    'success' => false,
                    'error' => 'Transaction not found',
                ];
            }

            // Retrieve payment intent from Stripe
            $paymentIntent = PaymentIntent::retrieve($stripePaymentIntentId);

            // Log verification attempt
            $this->paymentRepo->logPaymentEvent($transactionId, 'payment_verification_attempted', [
                'stripe_status' => $paymentIntent->status,
            ]);

            // Check payment status
            if ($paymentIntent->status === 'succeeded') {
                // Update transaction status
                $this->paymentRepo->updateTransactionStatus($transactionId, 'success');

                // Log successful payment
                $this->paymentRepo->logPaymentEvent($transactionId, 'payment_success', [
                    'stripe_payment_intent_id' => $paymentIntent->id,
                    'amount_received' => $paymentIntent->amount_received,
                ]);

                return [
                    'success' => true,
                    'status' => 'succeeded',
                    'amount' => $transaction['amount'],
                    'currency' => $transaction['currency'],
                ];
            } elseif ($paymentIntent->status === 'processing') {
                return [
                    'success' => true,
                    'status' => 'processing',
                    'message' => 'Payment is being processed',
                ];
            } elseif ($paymentIntent->status === 'requires_payment_method') {
                $this->paymentRepo->updateTransactionStatus($transactionId, 'failed', 'Payment method required');

                $this->paymentRepo->logPaymentEvent($transactionId, 'payment_failed', [
                    'reason' => 'requires_payment_method',
                ]);

                return [
                    'success' => false,
                    'status' => 'failed',
                    'error' => 'Payment method required',
                ];
            } else {
                $this->paymentRepo->updateTransactionStatus($transactionId, 'failed', "Payment status: {$paymentIntent->status}");

                $this->paymentRepo->logPaymentEvent($transactionId, 'payment_failed', [
                    'status' => $paymentIntent->status,
                ]);

                return [
                    'success' => false,
                    'status' => 'failed',
                    'error' => 'Payment could not be completed',
                ];
            }
        } catch (ApiErrorException $e) {
            $errorMessage = $e->getMessage();
            error_log("Stripe Verification Error: {$errorMessage}");

            $this->paymentRepo->updateTransactionStatus($transactionId, 'failed', $errorMessage);
            $this->paymentRepo->logPaymentEvent($transactionId, 'payment_verification_failed', [
                'error' => $errorMessage,
            ]);

            return [
                'success' => false,
                'error' => 'Payment verification failed',
            ];
        } catch (\Exception $e) {
            $errorMessage = $e->getMessage();
            error_log("Verification Error: {$errorMessage}");

            return [
                'success' => false,
                'error' => $errorMessage,
            ];
        }
    }

    /**
     * Process a refund for a transaction
     *
     * @param int $transactionId
     * @param float|null $amount Partial refund amount (null for full refund)
     * @return array Refund result
     */
    public function refund(int $transactionId, ?float $amount = null): array
    {
        try {
            // Get transaction from database
            $transaction = $this->paymentRepo->find($transactionId);

            if (!$transaction) {
                return [
                    'success' => false,
                    'error' => 'Transaction not found',
                ];
            }

            // Check if transaction can be refunded
            if ($transaction['status'] !== 'success') {
                return [
                    'success' => false,
                    'error' => 'Only successful transactions can be refunded',
                ];
            }

            // Get the Stripe charge
            $charges = Charge::all(['limit' => 1, 'payment_intent' => $transaction['stripe_transaction_id']]);

            if (empty($charges->data)) {
                return [
                    'success' => false,
                    'error' => 'Stripe charge not found',
                ];
            }

            $charge = $charges->data[0];

            // Create refund
            $refundAmount = null;
            if ($amount !== null) {
                // Partial refund - convert to cents
                $refundAmount = (int)round($amount * 100);
            }

            $refundData = ['charge' => $charge->id];
            if ($refundAmount !== null) {
                $refundData['amount'] = $refundAmount;
            }

            $refund = \Stripe\Refund::create($refundData);

            // Update transaction status
            if ($amount === null || $amount >= $transaction['amount']) {
                $this->paymentRepo->updateTransactionStatus($transactionId, 'refunded');
            } else {
                $this->paymentRepo->updateTransactionStatus($transactionId, 'partially_refunded');
            }

            // Log refund event
            $this->paymentRepo->logPaymentEvent($transactionId, 'refund_processed', [
                'stripe_refund_id' => $refund->id,
                'amount' => $amount ?? $transaction['amount'],
                'status' => $refund->status,
            ]);

            return [
                'success' => true,
                'refund_id' => $refund->id,
                'amount' => $amount ?? $transaction['amount'],
                'status' => $refund->status,
            ];
        } catch (ApiErrorException $e) {
            $errorMessage = $e->getMessage();
            error_log("Stripe Refund Error: {$errorMessage}");

            $this->paymentRepo->logPaymentEvent($transactionId, 'refund_failed', [
                'error' => $errorMessage,
            ]);

            return [
                'success' => false,
                'error' => 'Refund processing failed',
                'error_details' => $errorMessage,
            ];
        } catch (\Exception $e) {
            $errorMessage = $e->getMessage();
            error_log("Refund Error: {$errorMessage}");

            return [
                'success' => false,
                'error' => $errorMessage,
            ];
        }
    }

    /**
     * Handle Stripe webhook events
     *
     * @param string $payload Raw webhook payload
     * @param string $signature Stripe signature header
     * @return array Webhook handling result
     */
    public function handleWebhook(string $payload, string $signature): array
    {
        try {
            // Verify webhook signature for security
            $event = \Stripe\Webhook::constructEvent($payload, $signature, $this->stripeWebhookSecret);
        } catch (\UnexpectedValueException $e) {
            error_log("Invalid webhook payload: " . $e->getMessage());
            return ['success' => false, 'error' => 'Invalid payload'];
        } catch (\Stripe\Exception\SignatureVerificationException $e) {
            error_log("Webhook signature verification failed: " . $e->getMessage());
            return ['success' => false, 'error' => 'Signature verification failed'];
        }

        try {
            // Handle different event types
            switch ($event->type) {
                case 'payment_intent.succeeded':
                    return $this->handlePaymentSucceeded($event->data->object);

                case 'payment_intent.payment_failed':
                    return $this->handlePaymentFailed($event->data->object);

                case 'charge.refunded':
                    return $this->handleChargeRefunded($event->data->object);

                default:
                    error_log("Unhandled event type: " . $event->type);
                    return ['success' => true, 'message' => 'Event acknowledged'];
            }
        } catch (\Exception $e) {
            error_log("Webhook processing error: " . $e->getMessage());
            return ['success' => false, 'error' => 'Webhook processing failed'];
        }
    }

    /**
     * Handle payment_intent.succeeded event
     */
    private function handlePaymentSucceeded(\Stripe\PaymentIntent $paymentIntent): array
    {
        $transaction = $this->paymentRepo->getByStripeTransactionId($paymentIntent->id);

        if (!$transaction) {
            error_log("Transaction not found for payment intent: " . $paymentIntent->id);
            return ['success' => true, 'message' => 'Transaction not found (possibly already processed)'];
        }

        $this->paymentRepo->updateTransactionStatus($transaction['id'], 'success');

        $this->paymentRepo->logPaymentEvent($transaction['id'], 'payment_intent.succeeded', [
            'stripe_payment_intent_id' => $paymentIntent->id,
            'amount_received' => $paymentIntent->amount_received,
        ]);

        return ['success' => true, 'message' => 'Payment processed successfully'];
    }

    /**
     * Handle payment_intent.payment_failed event
     */
    private function handlePaymentFailed(\Stripe\PaymentIntent $paymentIntent): array
    {
        $transaction = $this->paymentRepo->getByStripeTransactionId($paymentIntent->id);

        if (!$transaction) {
            error_log("Transaction not found for payment intent: " . $paymentIntent->id);
            return ['success' => true, 'message' => 'Transaction not found'];
        }

        $errorMessage = $paymentIntent->last_payment_error?->message ?? 'Unknown error';

        $this->paymentRepo->updateTransactionStatus($transaction['id'], 'failed', $errorMessage);

        $this->paymentRepo->logPaymentEvent($transaction['id'], 'payment_intent.payment_failed', [
            'stripe_payment_intent_id' => $paymentIntent->id,
            'error' => $errorMessage,
        ]);

        return ['success' => true, 'message' => 'Payment failure recorded'];
    }

    /**
     * Handle charge.refunded event
     */
    private function handleChargeRefunded(\Stripe\Charge $charge): array
    {
        // Find transaction by charge ID
        $transaction = $this->paymentRepo->getByStripeTransactionId($charge->payment_intent ?? $charge->id);

        if (!$transaction) {
            error_log("Transaction not found for charge: " . $charge->id);
            return ['success' => true, 'message' => 'Transaction not found'];
        }

        // Determine refund status based on amount
        if ($charge->amount_refunded >= $charge->amount) {
            $status = 'refunded';
        } else {
            $status = 'partially_refunded';
        }

        $this->paymentRepo->updateTransactionStatus($transaction['id'], $status);

        $this->paymentRepo->logPaymentEvent($transaction['id'], 'charge.refunded', [
            'stripe_charge_id' => $charge->id,
            'amount_refunded' => $charge->amount_refunded,
            'total_amount' => $charge->amount,
        ]);

        return ['success' => true, 'message' => 'Refund recorded'];
    }
}
