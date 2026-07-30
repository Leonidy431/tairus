<?php

namespace Tests\Unit\Repository;

use App\Database\Database;
use App\Repository\CartRepository;
use PHPUnit\Framework\TestCase;

class CartRepositoryTest extends TestCase
{
    private Database $db;
    private CartRepository $cartRepository;
    private const TEST_USER_ID = 1;
    private const TEST_PRODUCT_ID = 1;

    protected function setUp(): void
    {
        // Create in-memory SQLite database for testing
        $config = [
            'driver' => 'sqlite',
            'path' => ':memory:',
        ];

        $this->db = new Database($config);
        $this->cartRepository = new CartRepository($this->db);

        // Create test tables
        $this->createTestTables();
    }

    private function createTestTables(): void
    {
        // Create users table
        $this->db->getConnection()->exec("
            CREATE TABLE users (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                email VARCHAR(255) UNIQUE NOT NULL,
                password VARCHAR(255) NOT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            )
        ");

        // Create products table
        $this->db->getConnection()->exec("
            CREATE TABLE products (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                title VARCHAR(255) NOT NULL,
                description TEXT,
                price DECIMAL(10, 2),
                currency VARCHAR(10) DEFAULT 'USD',
                is_public BOOLEAN DEFAULT 1,
                image_url VARCHAR(500),
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
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
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (user_id) REFERENCES users(id),
                FOREIGN KEY (product_id) REFERENCES products(id)
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

        // Insert test product
        $this->db->insert('products', [
            'id' => self::TEST_PRODUCT_ID,
            'title' => 'Test Painting',
            'description' => 'A beautiful test painting',
            'price' => 99.99,
            'is_public' => 1,
        ]);
    }

    public function testAddItemToCart(): void
    {
        $result = $this->cartRepository->addItem(self::TEST_USER_ID, self::TEST_PRODUCT_ID, 1);
        $this->assertTrue($result);

        $cartItems = $this->cartRepository->getCart(self::TEST_USER_ID);
        $this->assertCount(1, $cartItems);
        $this->assertEquals(self::TEST_PRODUCT_ID, $cartItems[0]['product_id']);
        $this->assertEquals(1, $cartItems[0]['quantity']);
    }

    public function testAddDuplicateItemIncrementsQuantity(): void
    {
        $this->cartRepository->addItem(self::TEST_USER_ID, self::TEST_PRODUCT_ID, 2);
        $this->cartRepository->addItem(self::TEST_USER_ID, self::TEST_PRODUCT_ID, 3);

        $cartItems = $this->cartRepository->getCart(self::TEST_USER_ID);
        $this->assertCount(1, $cartItems);
        $this->assertEquals(5, $cartItems[0]['quantity']);
    }

    public function testUpdateQuantity(): void
    {
        $this->cartRepository->addItem(self::TEST_USER_ID, self::TEST_PRODUCT_ID, 1);
        $cartItems = $this->cartRepository->getCart(self::TEST_USER_ID);
        $cartId = $cartItems[0]['id'];

        $result = $this->cartRepository->updateQuantity($cartId, 5);
        $this->assertTrue($result);

        $updated = $this->cartRepository->getCartItem($cartId);
        $this->assertEquals(5, $updated['quantity']);
    }

    public function testRemoveItem(): void
    {
        $this->cartRepository->addItem(self::TEST_USER_ID, self::TEST_PRODUCT_ID, 1);
        $cartItems = $this->cartRepository->getCart(self::TEST_USER_ID);
        $cartId = $cartItems[0]['id'];

        $result = $this->cartRepository->removeItem(self::TEST_USER_ID, $cartId);
        $this->assertTrue($result);

        $updatedCart = $this->cartRepository->getCart(self::TEST_USER_ID);
        $this->assertCount(0, $updatedCart);
    }

    public function testGetCartTotal(): void
    {
        $this->cartRepository->addItem(self::TEST_USER_ID, self::TEST_PRODUCT_ID, 2);

        $total = $this->cartRepository->getTotal(self::TEST_USER_ID);
        $this->assertEquals(199.98, $total); // 99.99 * 2
    }

    public function testGetItemCount(): void
    {
        $this->cartRepository->addItem(self::TEST_USER_ID, self::TEST_PRODUCT_ID, 3);

        $count = $this->cartRepository->getItemCount(self::TEST_USER_ID);
        $this->assertEquals(1, $count); // 1 item in cart
    }

    public function testClearCart(): void
    {
        $this->cartRepository->addItem(self::TEST_USER_ID, self::TEST_PRODUCT_ID, 1);
        $result = $this->cartRepository->clearCart(self::TEST_USER_ID);
        $this->assertTrue($result);

        $cartItems = $this->cartRepository->getCart(self::TEST_USER_ID);
        $this->assertCount(0, $cartItems);
    }

    public function testValidateProduct(): void
    {
        $product = $this->cartRepository->validateProduct(self::TEST_PRODUCT_ID);
        $this->assertIsArray($product);
        $this->assertEquals('Test Painting', $product['title']);
    }

    public function testSaveGuestCart(): void
    {
        $sessionId = 'test_session_123';
        $cartData = [
            ['product_id' => 1, 'title' => 'Painting 1', 'price' => 50.00, 'quantity' => 1],
            ['product_id' => 2, 'title' => 'Painting 2', 'price' => 75.00, 'quantity' => 2],
        ];

        $result = $this->cartRepository->saveGuestCart($sessionId, $cartData);
        $this->assertTrue($result);

        $retrieved = $this->cartRepository->getGuestCart($sessionId);
        $this->assertCount(2, $retrieved);
        $this->assertEquals('Painting 1', $retrieved[0]['title']);
    }

    public function testDeleteGuestCart(): void
    {
        $sessionId = 'test_session_456';
        $cartData = [
            ['product_id' => 1, 'title' => 'Painting 1', 'price' => 50.00, 'quantity' => 1],
        ];

        $this->cartRepository->saveGuestCart($sessionId, $cartData);
        $result = $this->cartRepository->deleteGuestCart($sessionId);
        $this->assertTrue($result);

        $retrieved = $this->cartRepository->getGuestCart($sessionId);
        $this->assertNull($retrieved);
    }
}
