<?php

namespace Tests\Unit\Repository;

use App\Database\Database;
use App\Repository\ProductRepository;
use PHPUnit\Framework\TestCase;

class RepositoryComprehensiveTest extends TestCase
{
    private Database $db;
    private ProductRepository $repo;
    private string $testDb = '/tmp/test_repository_comprehensive.sqlite';

    protected function setUp(): void
    {
        if (file_exists($this->testDb)) {
            unlink($this->testDb);
        }

        $config = ['driver' => 'sqlite', 'path' => $this->testDb];
        $this->db = new Database($config);

        $this->db->getConnection()->exec("
            CREATE TABLE IF NOT EXISTS products (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                title TEXT NOT NULL,
                description TEXT,
                price DECIMAL(10, 2),
                category TEXT,
                is_featured BOOLEAN DEFAULT 0,
                is_public BOOLEAN DEFAULT 1,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            )
        ");

        $this->repo = new ProductRepository($this->db);
    }

    protected function tearDown(): void
    {
        if (file_exists($this->testDb)) {
            unlink($this->testDb);
        }
    }

    public function testFind(): void
    {
        $this->db->insert('products', ['title' => 'Test Product', 'price' => 99.99, 'is_public' => 1]);
        $product = $this->repo->find(1);
        $this->assertIsArray($product);
        $this->assertEquals('Test Product', $product['title']);
    }

    public function testFindNotFound(): void
    {
        $product = $this->repo->find(999);
        $this->assertNull($product);
    }

    public function testAll(): void
    {
        $this->db->insert('products', ['title' => 'Product 1', 'price' => 10.00]);
        $this->db->insert('products', ['title' => 'Product 2', 'price' => 20.00]);
        $products = $this->repo->all();
        $this->assertCount(2, $products);
    }

    public function testAllWithOrder(): void
    {
        $this->db->insert('products', ['title' => 'A', 'price' => 30.00]);
        $this->db->insert('products', ['title' => 'B', 'price' => 10.00]);
        $this->db->insert('products', ['title' => 'C', 'price' => 20.00]);
        $products = $this->repo->all(['price' => 'asc']);
        $this->assertEquals(10.00, $products[0]['price']);
        $this->assertEquals(20.00, $products[1]['price']);
        $this->assertEquals(30.00, $products[2]['price']);
    }

    public function testPaginate(): void
    {
        for ($i = 1; $i <= 15; $i++) {
            $this->db->insert('products', ['title' => "Product $i", 'price' => $i * 10]);
        }
        $page1 = $this->repo->paginate(1, 5);
        $page2 = $this->repo->paginate(2, 5);
        $page3 = $this->repo->paginate(3, 5);
        $this->assertCount(5, $page1);
        $this->assertCount(5, $page2);
        $this->assertCount(5, $page3);
    }

    public function testSaveNewRecord(): void
    {
        $id = $this->repo->save(['title' => 'New Product', 'price' => 99.99]);
        $this->assertIsInt($id);
        $this->assertGreaterThan(0, $id);
    }

    public function testSaveUpdateRecord(): void
    {
        $this->db->insert('products', ['title' => 'Original', 'price' => 50.00]);
        $id = $this->repo->save(['id' => 1, 'title' => 'Updated', 'price' => 75.00]);
        $this->assertEquals(1, $id);
        $product = $this->repo->find(1);
        $this->assertEquals('Updated', $product['title']);
    }

    public function testDelete(): void
    {
        $this->db->insert('products', ['title' => 'ToDelete', 'price' => 10.00]);
        $result = $this->repo->delete(1);
        $this->assertTrue($result);
        $product = $this->repo->find(1);
        $this->assertNull($product);
    }

    public function testCount(): void
    {
        $this->db->insert('products', ['title' => 'P1', 'price' => 10.00]);
        $this->db->insert('products', ['title' => 'P2', 'price' => 20.00]);
        $this->db->insert('products', ['title' => 'P3', 'price' => 30.00]);
        $count = $this->repo->count();
        $this->assertEquals(3, $count);
    }

    public function testCountWithConditions(): void
    {
        $this->db->insert('products', ['title' => 'Cheap', 'price' => 5.00, 'is_public' => 1]);
        $this->db->insert('products', ['title' => 'Expensive', 'price' => 500.00, 'is_public' => 1]);
        $this->db->insert('products', ['title' => 'Hidden', 'price' => 10.00, 'is_public' => 0]);
        $count = $this->repo->count(['is_public' => 1]);
        $this->assertEquals(2, $count);
    }

    public function testWhere(): void
    {
        $this->db->insert('products', ['title' => 'A', 'category' => 'art', 'price' => 100]);
        $this->db->insert('products', ['title' => 'B', 'category' => 'art', 'price' => 200]);
        $this->db->insert('products', ['title' => 'C', 'category' => 'design', 'price' => 300]);
        $products = $this->repo->where(['category' => 'art']);
        $this->assertCount(2, $products);
    }

    public function testWhereWithOrder(): void
    {
        $this->db->insert('products', ['title' => 'A', 'category' => 'art', 'price' => 300]);
        $this->db->insert('products', ['title' => 'B', 'category' => 'art', 'price' => 100]);
        $this->db->insert('products', ['title' => 'C', 'category' => 'art', 'price' => 200]);
        $products = $this->repo->where(['category' => 'art'], ['price' => 'asc']);
        $this->assertEquals(100, $products[0]['price']);
        $this->assertEquals(200, $products[1]['price']);
        $this->assertEquals(300, $products[2]['price']);
    }

    public function testGetFeatured(): void
    {
        $this->db->insert('products', ['title' => 'Featured', 'price' => 100, 'is_featured' => 1, 'is_public' => 1]);
        $this->db->insert('products', ['title' => 'Normal', 'price' => 50, 'is_featured' => 0, 'is_public' => 1]);
        $featured = $this->repo->getFeatured();
        $this->assertCount(1, $featured);
        $this->assertEquals('Featured', $featured[0]['title']);
    }
}
