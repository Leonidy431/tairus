<?php

namespace Tests\Unit\Controllers;

use App\Database\Database;
use App\Controllers\CartController;
use App\Security\CsrfToken;
use PHPUnit\Framework\TestCase;

class CartControllerTest extends TestCase
{
    private Database $db;
    private CartController $cartController;
    private const TEST_PRODUCT_ID = 1;

    protected function setUp(): void
    {
        // Create in-memory SQLite database for testing
        $config = [
            'driver' => 'sqlite',
            'path' => ':memory:',
        ];

        $this->db = new Database($config);
        $this->cartController = new CartController($this->db);

        // Create test tables
        $this->createTestTables();

        // Start session for testing
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
    }

    private function createTestTables(): void
    {
        // Create products table
        $this->db->getConnection()->exec("
            CREATE TABLE products (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                title VARCHAR(255) NOT NULL,
                price DECIMAL(10, 2),
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

        // Insert test product
        $this->db->insert('products', [
            'id' => self::TEST_PRODUCT_ID,
            'title' => 'Test Painting',
            'price' => 99.99,
            'is_public' => 1,
        ]);
    }

    public function testViewCartReturnsEmptyCartForGuest(): void
    {
        $result = $this->cartController->viewCart();

        $this->assertTrue($result['success']);
        $this->assertEqual(0, $result['item_count']);
        $this->assertEqual(0.0, $result['total']);
    }

    public function testGetMiniCartReturnsValidStructure(): void
    {
        $result = $this->cartController->getMiniCart();

        $this->assertIsArray($result);
        $this->assertArrayHasKey('item_count', $result);
        $this->assertArrayHasKey('total', $result);
        $this->assertArrayHasKey('preview_items', $result);
        $this->assertEqual(0, $result['item_count']);
    }

    public function testAddToCartValidatesProductId(): void
    {
        $_POST['cart_action'] = 'add';
        $_POST['product_id'] = 'invalid';
        $_POST['quantity'] = 1;
        $_POST['_token'] = CsrfToken::generate();

        $result = $this->cartController->addToCart();

        $this->assertFalse($result['success']);
        $this->assertStringContainsString('Invalid product ID', $result['error']);
    }

    public function testAddToCartValidatesQuantity(): void
    {
        $_POST['cart_action'] = 'add';
        $_POST['product_id'] = self::TEST_PRODUCT_ID;
        $_POST['quantity'] = 0;
        $_POST['_token'] = CsrfToken::generate();

        $result = $this->cartController->addToCart();

        $this->assertFalse($result['success']);
        $this->assertStringContainsString('Invalid quantity', $result['error']);
    }

    public function testAddToCartValidatesProduct(): void
    {
        $_POST['cart_action'] = 'add';
        $_POST['product_id'] = 999;
        $_POST['quantity'] = 1;
        $_POST['_token'] = CsrfToken::generate();

        $result = $this->cartController->addToCart();

        $this->assertFalse($result['success']);
        $this->assertStringContainsString('Product not found', $result['error']);
    }

    public function testAddToCartLimitsQuantity(): void
    {
        $_POST['cart_action'] = 'add';
        $_POST['product_id'] = self::TEST_PRODUCT_ID;
        $_POST['quantity'] = 5000; // Should be capped at 999
        $_POST['_token'] = CsrfToken::generate();

        $result = $this->cartController->addToCart();

        $this->assertTrue($result['success']);
    }

    public function testRemoveFromCartValidatesCartId(): void
    {
        $_POST['cart_action'] = 'remove';
        $_POST['cart_id'] = 'invalid';
        $_POST['_token'] = CsrfToken::generate();

        $result = $this->cartController->removeFromCart();

        $this->assertFalse($result['success']);
        $this->assertStringContainsString('Invalid cart item ID', $result['error']);
    }

    public function testUpdateQuantityValidatesCartId(): void
    {
        $_POST['cart_action'] = 'update';
        $_POST['cart_id'] = 'invalid';
        $_POST['quantity'] = 5;
        $_POST['_token'] = CsrfToken::generate();

        $result = $this->cartController->updateQuantity();

        $this->assertFalse($result['success']);
        $this->assertStringContainsString('Invalid cart item ID', $result['error']);
    }

    public function testUpdateQuantityValidatesQuantity(): void
    {
        $_POST['cart_action'] = 'update';
        $_POST['cart_id'] = 1;
        $_POST['quantity'] = 'invalid';
        $_POST['_token'] = CsrfToken::generate();

        $result = $this->cartController->updateQuantity();

        $this->assertFalse($result['success']);
        $this->assertStringContainsString('Invalid quantity', $result['error']);
    }

    protected function tearDown(): void
    {
        // Clean up session
        $_SESSION = [];
        $_POST = [];
    }
}
