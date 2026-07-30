<?php

namespace Tests\Integration;

use App\Database\Database;
use App\Controllers\CartController;
use App\Repository\CartRepository;
use PHPUnit\Framework\TestCase;

class CartIntegrationTest extends TestCase
{
    private Database $db;
    private CartController $cartController;
    private CartRepository $cartRepository;
    private const TEST_USER_ID = 1;

    protected function setUp(): void
    {
        // Create in-memory SQLite database for testing
        $config = [
            'driver' => 'sqlite',
            'path' => ':memory:',
        ];

        $this->db = new Database($config);
        $this->cartController = new CartController($this->db);
        $this->cartRepository = new CartRepository($this->db);

        // Create test tables
        $this->createTestTables();

        // Start session
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
    }

    private function createTestTables(): void
    {
        // Create users table
        $this->db->getConnection()->exec("
            CREATE TABLE users (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                email VARCHAR(255) UNIQUE NOT NULL,
                password VARCHAR(255) NOT NULL
            )
        ");

        // Create products table
        $this->db->getConnection()->exec("
            CREATE TABLE products (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                title VARCHAR(255) NOT NULL,
                description TEXT,
                price DECIMAL(10, 2),
                image_url VARCHAR(500),
                is_public BOOLEAN DEFAULT 1
            )
        ");

        // Create carts table
        $this->db->getConnection()->exec("
            CREATE TABLE carts (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                user_id INTEGER,
                product_id INTEGER NOT NULL,
                quantity INTEGER DEFAULT 1,
                added_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            )
        ");

        // Create cart_sessions table
        $this->db->getConnection()->exec("
            CREATE TABLE cart_sessions (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                session_id VARCHAR(255) UNIQUE NOT NULL,
                cart_data TEXT,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            )
        ");

        // Insert test user
        $this->db->insert('users', [
            'id' => self::TEST_USER_ID,
            'email' => 'test@example.com',
            'password' => password_hash('password', PASSWORD_DEFAULT),
        ]);

        // Insert test products
        $this->db->insert('products', [
            'id' => 1,
            'title' => 'Painting One',
            'description' => 'Beautiful painting',
            'price' => 99.99,
            'is_public' => 1,
        ]);

        $this->db->insert('products', [
            'id' => 2,
            'title' => 'Painting Two',
            'description' => 'Another beautiful painting',
            'price' => 149.99,
            'is_public' => 1,
        ]);

        $this->db->insert('products', [
            'id' => 3,
            'title' => 'Painting Three',
            'description' => 'Yet another beautiful painting',
            'price' => 199.99,
            'is_public' => 1,
        ]);
    }

    /**
     * Integration test: Add multiple items to cart and verify totals
     */
    public function testAddMultipleItemsAndCalculateTotal(): void
    {
        // Simulate authenticated user
        $_SESSION['user_id'] = self::TEST_USER_ID;

        // Add first product
        $this->cartRepository->addItem(self::TEST_USER_ID, 1, 1);

        // Add second product
        $this->cartRepository->addItem(self::TEST_USER_ID, 2, 2);

        // Add third product
        $this->cartRepository->addItem(self::TEST_USER_ID, 3, 1);

        // Verify cart items
        $cartItems = $this->cartRepository->getCart(self::TEST_USER_ID);
        $this->assertCount(3, $cartItems);

        // Verify total calculation
        // Product 1: 99.99 * 1 = 99.99
        // Product 2: 149.99 * 2 = 299.98
        // Product 3: 199.99 * 1 = 199.99
        // Total = 599.96
        $total = $this->cartRepository->getTotal(self::TEST_USER_ID);
        $this->assertEqualsWithDelta(599.96, $total, 0.01);
    }

    /**
     * Integration test: Modify cart items and verify price recalculation
     */
    public function testUpdateItemQuantitiesAndRecalculateTotal(): void
    {
        // Simulate authenticated user
        $_SESSION['user_id'] = self::TEST_USER_ID;

        // Add products to cart
        $this->cartRepository->addItem(self::TEST_USER_ID, 1, 2);
        $this->cartRepository->addItem(self::TEST_USER_ID, 2, 1);

        // Initial total: (99.99 * 2) + (149.99 * 1) = 349.97
        $initialTotal = $this->cartRepository->getTotal(self::TEST_USER_ID);
        $this->assertEqualsWithDelta(349.97, $initialTotal, 0.01);

        // Get cart items to update
        $cartItems = $this->cartRepository->getCart(self::TEST_USER_ID);
        $firstItemId = $cartItems[0]['id'];

        // Update quantity of first item to 5
        $this->cartRepository->updateQuantity($firstItemId, 5);

        // New total: (99.99 * 5) + (149.99 * 1) = 649.94
        $newTotal = $this->cartRepository->getTotal(self::TEST_USER_ID);
        $this->assertEqualsWithDelta(649.94, $newTotal, 0.01);
    }

    /**
     * Integration test: Remove items and verify cart is updated
     */
    public function testRemoveItemsAndVerifyCartUpdate(): void
    {
        // Simulate authenticated user
        $_SESSION['user_id'] = self::TEST_USER_ID;

        // Add products
        $this->cartRepository->addItem(self::TEST_USER_ID, 1, 1);
        $this->cartRepository->addItem(self::TEST_USER_ID, 2, 1);
        $this->cartRepository->addItem(self::TEST_USER_ID, 3, 1);

        // Verify 3 items
        $this->assertEquals(3, count($this->cartRepository->getCart(self::TEST_USER_ID)));

        // Remove first item
        $cartItems = $this->cartRepository->getCart(self::TEST_USER_ID);
        $firstItemId = $cartItems[0]['id'];
        $this->cartRepository->removeItem(self::TEST_USER_ID, $firstItemId);

        // Verify 2 items remain
        $this->assertEquals(2, count($this->cartRepository->getCart(self::TEST_USER_ID)));

        // Clear entire cart
        $this->cartRepository->clearCart(self::TEST_USER_ID);

        // Verify cart is empty
        $this->assertEquals(0, count($this->cartRepository->getCart(self::TEST_USER_ID)));
        $this->assertEquals(0.0, $this->cartRepository->getTotal(self::TEST_USER_ID));
    }

    /**
     * Integration test: Guest cart session persistence
     */
    public function testGuestCartSessionPersistence(): void
    {
        $sessionId = 'test_session_guest_123';

        // Create guest cart data
        $guestCartData = [
            [
                'product_id' => 1,
                'title' => 'Painting One',
                'price' => 99.99,
                'quantity' => 2,
            ],
            [
                'product_id' => 2,
                'title' => 'Painting Two',
                'price' => 149.99,
                'quantity' => 1,
            ],
        ];

        // Save guest cart
        $this->cartRepository->saveGuestCart($sessionId, $guestCartData);

        // Retrieve guest cart
        $retrievedCart = $this->cartRepository->getGuestCart($sessionId);

        // Verify data
        $this->assertCount(2, $retrievedCart);
        $this->assertEquals('Painting One', $retrievedCart[0]['title']);
        $this->assertEquals(2, $retrievedCart[0]['quantity']);
        $this->assertEquals('Painting Two', $retrievedCart[1]['title']);
        $this->assertEquals(1, $retrievedCart[1]['quantity']);

        // Verify data persists (update and retrieve)
        $updatedCart = $guestCartData;
        $updatedCart[0]['quantity'] = 5;
        $this->cartRepository->saveGuestCart($sessionId, $updatedCart);

        $reretrievedCart = $this->cartRepository->getGuestCart($sessionId);
        $this->assertEquals(5, $reretrievedCart[0]['quantity']);
    }

    /**
     * Integration test: Duplicate items increase quantity
     */
    public function testAddingDuplicateItemsIncreasesQuantity(): void
    {
        // Simulate authenticated user
        $_SESSION['user_id'] = self::TEST_USER_ID;

        // Add same product multiple times
        $this->cartRepository->addItem(self::TEST_USER_ID, 1, 2);
        $this->cartRepository->addItem(self::TEST_USER_ID, 1, 3);
        $this->cartRepository->addItem(self::TEST_USER_ID, 1, 1);

        // Verify only 1 cart item exists with combined quantity
        $cartItems = $this->cartRepository->getCart(self::TEST_USER_ID);
        $this->assertCount(1, $cartItems);
        $this->assertEquals(6, $cartItems[0]['quantity']); // 2 + 3 + 1
    }

    /**
     * Integration test: Cart validation and stock checks
     */
    public function testCartValidationAndProductChecks(): void
    {
        // Validate existing product
        $product = $this->cartRepository->validateProduct(1);
        $this->assertIsArray($product);
        $this->assertEquals('Painting One', $product['title']);
        $this->assertTrue($product['is_public']);

        // Validate non-existent product
        $invalidProduct = $this->cartRepository->validateProduct(999);
        $this->assertFalse($invalidProduct);

        // Validate private product (insert and check)
        $this->db->insert('products', [
            'id' => 100,
            'title' => 'Private Painting',
            'price' => 50.00,
            'is_public' => 0,
        ]);

        $privateProduct = $this->cartRepository->validateProduct(100);
        $this->assertFalse($privateProduct);
    }

    protected function tearDown(): void
    {
        // Clean up
        $_SESSION = [];
        $_POST = [];
    }
}
