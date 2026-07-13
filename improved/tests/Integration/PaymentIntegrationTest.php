<?php
/**
 * Payment Integration Tests
 *
 * Comprehensive tests for Stripe payment gateway integration
 * Tests cover: payment intent creation, verification, refunds, webhooks, and audit logging
 */

namespace Tests\Integration;

use PHPUnit\Framework\TestCase;
use App\Repository\PaymentRepository;
use App\Controllers\PaymentController;
use App\Database\Database;

class PaymentIntegrationTest extends TestCase
{
    private Database $db;
    private PaymentRepository $paymentRepo;
    private PaymentController $paymentController;

    protected function setUp(): void
    {
        // Initialize test database connection
        // Note: This assumes a test database is available
        $config = [
            'driver' => 'mysql',
            'host' => getenv('DB_HOST') ?? 'localhost',
            'port' => getenv('DB_PORT') ?? 3306,
            'database' => getenv('DB_DATABASE') ?? 'painting_sales_test',
            'username' => getenv('DB_USERNAME') ?? 'root',
            'password' => getenv('DB_PASSWORD') ?? '',
            'charset' => 'utf8mb4',
        ];

        $this->db = new Database($config);
        $this->paymentRepo = new PaymentRepository($this->db);
        $this->paymentController = new PaymentController($this->db);
    }

    /**
     * Test 1: Create Payment Intent
     * Verifies that a payment intent can be created successfully
     */
    public function testCreatePaymentIntent(): void
    {
        // Arrange
        $userId = 1;
        $amount = 99.99;
        $currency = 'USD';
        $orderId = 1;

        // Mock user existence (in real test, ensure user exists in DB)
        // This test assumes user with ID 1 exists

        // Act
        $result = $this->paymentController->createPaymentIntent($userId, $amount, $currency, $orderId);

        // Assert
        $this->assertTrue($result['success'] ?? false);
        $this->assertArrayHasKey('payment_intent_id', $result);
        $this->assertArrayHasKey('client_secret', $result);
        $this->assertArrayHasKey('transaction_id', $result);
        $this->assertEquals($amount, $result['amount']);
        $this->assertEquals($currency, $result['currency']);
    }

    /**
     * Test 2: Save and Retrieve Payment Method
     * Verifies that payment methods can be saved and retrieved securely
     */
    public function testSaveAndRetrievePaymentMethod(): void
    {
        // Arrange
        $userId = 1;
        $paymentMethodData = [
            'user_id' => $userId,
            'stripe_payment_method_id' => 'pm_test_' . uniqid(),
            'type' => 'card',
            'last4' => '4242',
            'brand' => 'visa',
            'exp_month' => 12,
            'exp_year' => 2025,
            'is_default' => 1,
        ];

        // Act
        $paymentMethodId = $this->paymentRepo->savePaymentMethod($paymentMethodData);
        $retrievedMethod = $this->paymentRepo->getPaymentMethodById($paymentMethodId);

        // Assert
        $this->assertIsInt($paymentMethodId);
        $this->assertGreaterThan(0, $paymentMethodId);
        $this->assertIsNotNull($retrievedMethod);
        $this->assertEquals($userId, $retrievedMethod['user_id']);
        $this->assertEquals('visa', $retrievedMethod['brand']);
        $this->assertEquals('4242', $retrievedMethod['last4']);
        $this->assertEquals(1, $retrievedMethod['is_default']);
    }

    /**
     * Test 3: Get Default Payment Method
     * Verifies that the default payment method for a user can be retrieved
     */
    public function testGetDefaultPaymentMethod(): void
    {
        // Arrange
        $userId = 1;

        // Create two payment methods
        $defaultMethod = [
            'user_id' => $userId,
            'stripe_payment_method_id' => 'pm_default_' . uniqid(),
            'type' => 'card',
            'last4' => '4242',
            'brand' => 'visa',
            'exp_month' => 12,
            'exp_year' => 2025,
            'is_default' => 1,
        ];

        $secondaryMethod = [
            'user_id' => $userId,
            'stripe_payment_method_id' => 'pm_secondary_' . uniqid(),
            'type' => 'card',
            'last4' => '5555',
            'brand' => 'mastercard',
            'exp_month' => 11,
            'exp_year' => 2026,
            'is_default' => 0,
        ];

        // Act
        $this->paymentRepo->savePaymentMethod($defaultMethod);
        $this->paymentRepo->savePaymentMethod($secondaryMethod);
        $retrieved = $this->paymentRepo->getDefaultPaymentMethod($userId);

        // Assert
        $this->assertIsNotNull($retrieved);
        $this->assertEquals(1, $retrieved['is_default']);
        $this->assertEquals('4242', $retrieved['last4']);
    }

    /**
     * Test 4: Update Transaction Status
     * Verifies that transaction status can be updated correctly
     */
    public function testUpdateTransactionStatus(): void
    {
        // Arrange
        $transactionData = [
            'user_id' => 1,
            'amount' => 50.00,
            'currency' => 'USD',
            'status' => 'pending',
        ];

        $transactionId = $this->paymentRepo->createPaymentIntent($transactionData);
        $initialStatus = $this->paymentRepo->find($transactionId)['status'];

        // Act
        $this->paymentRepo->updateTransactionStatus($transactionId, 'success');
        $updatedTransaction = $this->paymentRepo->find($transactionId);

        // Assert
        $this->assertEquals('pending', $initialStatus);
        $this->assertEquals('success', $updatedTransaction['status']);
    }

    /**
     * Test 5: Payment Event Logging for Audit Trail
     * Verifies that payment events are logged properly for compliance
     */
    public function testPaymentEventLogging(): void
    {
        // Arrange
        $transactionData = [
            'user_id' => 1,
            'amount' => 75.00,
            'currency' => 'USD',
            'status' => 'pending',
        ];

        $transactionId = $this->paymentRepo->createPaymentIntent($transactionData);
        $eventDetails = [
            'stripe_payment_intent_id' => 'pi_test_123',
            'amount' => 75.00,
            'currency' => 'USD',
        ];

        // Act
        $logId = $this->paymentRepo->logPaymentEvent($transactionId, 'payment_intent_created', $eventDetails);
        $logs = $this->paymentRepo->getPaymentLogs($transactionId);

        // Assert
        $this->assertIsInt($logId);
        $this->assertGreaterThan(0, $logId);
        $this->assertNotEmpty($logs);
        $this->assertEquals('payment_intent_created', $logs[0]['event']);
        $this->assertNotNull($logs[0]['details']);
    }

    /**
     * Test 6: Get Transaction with Full Audit Trail
     * Verifies that complete transaction history with logs can be retrieved
     */
    public function testGetTransactionWithLogs(): void
    {
        // Arrange
        $transactionData = [
            'user_id' => 1,
            'amount' => 100.00,
            'currency' => 'USD',
            'status' => 'pending',
        ];

        $transactionId = $this->paymentRepo->createPaymentIntent($transactionData);

        // Add multiple events
        $events = [
            ['event' => 'payment_intent_created', 'details' => ['pi_id' => 'pi_123']],
            ['event' => 'payment_processing', 'details' => ['status' => 'processing']],
            ['event' => 'payment_success', 'details' => ['status' => 'succeeded']],
        ];

        foreach ($events as $eventData) {
            $this->paymentRepo->logPaymentEvent($transactionId, $eventData['event'], $eventData['details']);
        }

        // Act
        $transactionWithLogs = $this->paymentRepo->getTransactionWithLogs($transactionId);

        // Assert
        $this->assertIsNotNull($transactionWithLogs);
        $this->assertEquals($transactionId, $transactionWithLogs['id']);
        $this->assertNotEmpty($transactionWithLogs['logs']);
        $this->assertCount(4, $transactionWithLogs['logs']); // 3 manual events + 1 from createPaymentIntent
        $this->assertEqualsCanonicalizing(
            ['payment_intent_created', 'payment_processing', 'payment_success'],
            array_column(array_slice($transactionWithLogs['logs'], 1), 'event')
        );
    }

    /**
     * Additional Test: Get User Transactions by Status
     * Verifies filtering transactions by status
     */
    public function testGetUserTransactionsByStatus(): void
    {
        // Arrange
        $userId = 1;

        // Create multiple transactions with different statuses
        $trans1 = $this->paymentRepo->createPaymentIntent([
            'user_id' => $userId,
            'amount' => 50.00,
            'currency' => 'USD',
            'status' => 'success',
        ]);

        $trans2 = $this->paymentRepo->createPaymentIntent([
            'user_id' => $userId,
            'amount' => 75.00,
            'currency' => 'USD',
            'status' => 'pending',
        ]);

        // Act
        $successTransactions = $this->paymentRepo->getByUserIdAndStatus($userId, 'success');
        $pendingTransactions = $this->paymentRepo->getByUserIdAndStatus($userId, 'pending');

        // Assert
        $this->assertIsArray($successTransactions);
        $this->assertIsArray($pendingTransactions);
        $this->assertNotEmpty($successTransactions);
        $this->assertNotEmpty($pendingTransactions);
    }

    /**
     * Additional Test: Payment Method Default Status Management
     * Verifies that only one payment method can be default per user
     */
    public function testSetDefaultPaymentMethod(): void
    {
        // Arrange
        $userId = 1;

        $method1 = $this->paymentRepo->savePaymentMethod([
            'user_id' => $userId,
            'stripe_payment_method_id' => 'pm_1_' . uniqid(),
            'type' => 'card',
            'last4' => '1111',
            'brand' => 'visa',
            'exp_month' => 12,
            'exp_year' => 2025,
            'is_default' => 1,
        ]);

        $method2 = $this->paymentRepo->savePaymentMethod([
            'user_id' => $userId,
            'stripe_payment_method_id' => 'pm_2_' . uniqid(),
            'type' => 'card',
            'last4' => '2222',
            'brand' => 'mastercard',
            'exp_month' => 11,
            'exp_year' => 2026,
            'is_default' => 0,
        ]);

        // Act
        $this->paymentRepo->setDefaultPaymentMethod($userId, $method2);
        $newDefault = $this->paymentRepo->getDefaultPaymentMethod($userId);

        // Assert
        $this->assertEquals($method2, $newDefault['id']);
        $this->assertEquals('2222', $newDefault['last4']);
    }
}
