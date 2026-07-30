<?php
/**
 * Unit Tests for Uploads Repository
 *
 * Tests database operations for product image uploads.
 */

namespace Tests;

use PHPUnit\Framework\TestCase;
use App\Database\Database;
use App\Repository\UploadsRepository;

class UploadsRepositoryTest extends TestCase
{
    private Database $db;
    private UploadsRepository $repository;
    private string $testDbPath;

    protected function setUp(): void
    {
        // Create in-memory SQLite database for testing
        $this->testDbPath = sys_get_temp_dir() . '/test_' . uniqid() . '.sqlite';
        $this->db = new Database('sqlite:' . $this->testDbPath);

        // Create required tables
        $this->createTables();

        $this->repository = new UploadsRepository($this->db);

        // Insert test product
        $this->db->insert('products', [
            'name' => 'Test Product',
            'price' => 99.99,
            'is_public' => 1,
        ]);
    }

    protected function tearDown(): void
    {
        // Clean up database
        if (file_exists($this->testDbPath)) {
            unlink($this->testDbPath);
        }
    }

    private function createTables(): void
    {
        $this->db->getConnection()->exec("
            CREATE TABLE products (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                name TEXT NOT NULL,
                price DECIMAL(10, 2),
                is_public INTEGER DEFAULT 1,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            )
        ");

        $this->db->getConnection()->exec("
            CREATE TABLE uploads (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                product_id INTEGER NOT NULL,
                file_path TEXT NOT NULL,
                file_name TEXT NOT NULL,
                file_size INTEGER,
                mime_type TEXT,
                is_primary INTEGER DEFAULT 0,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (product_id) REFERENCES products(id)
            )
        ");
    }

    /**
     * Test 1: Save and retrieve upload
     *
     * Verify uploads can be saved and retrieved from database.
     */
    public function testSaveAndRetrieveUpload(): void
    {
        $uploadData = [
            'product_id' => 1,
            'file_path' => 'products/1/image_001.jpg',
            'file_name' => 'image_001.jpg',
            'file_size' => 102400,
            'mime_type' => 'image/jpeg',
            'is_primary' => 1,
        ];

        $uploadId = $this->repository->saveUpload($uploadData);

        $this->assertIsInt($uploadId);
        $this->assertGreaterThan(0, $uploadId);

        // Retrieve and verify
        $retrieved = $this->repository->findImage($uploadId);

        $this->assertNotNull($retrieved);
        $this->assertEquals('image_001.jpg', $retrieved['file_name']);
        $this->assertEquals('products/1/image_001.jpg', $retrieved['file_path']);
        $this->assertEquals(102400, $retrieved['file_size']);
        $this->assertEquals(1, $retrieved['is_primary']);
    }

    /**
     * Test 2: Set primary image
     *
     * Verify primary image can be set and previous primary is unset.
     */
    public function testSetPrimaryImage(): void
    {
        // Insert multiple images
        $id1 = $this->repository->saveUpload([
            'product_id' => 1,
            'file_path' => 'products/1/image_001.jpg',
            'file_name' => 'image_001.jpg',
            'file_size' => 100000,
            'mime_type' => 'image/jpeg',
            'is_primary' => 1,
        ]);

        $id2 = $this->repository->saveUpload([
            'product_id' => 1,
            'file_path' => 'products/1/image_002.jpg',
            'file_name' => 'image_002.jpg',
            'file_size' => 100000,
            'mime_type' => 'image/jpeg',
            'is_primary' => 0,
        ]);

        // Set second image as primary
        $this->repository->setPrimaryImage(1, $id2);

        // Verify first is no longer primary
        $image1 = $this->repository->findImage($id1);
        $this->assertEquals(0, $image1['is_primary']);

        // Verify second is now primary
        $image2 = $this->repository->findImage($id2);
        $this->assertEquals(1, $image2['is_primary']);

        // Verify getPrimaryImage returns the correct one
        $primary = $this->repository->getPrimaryImage(1);
        $this->assertEquals($id2, $primary['id']);
    }

    /**
     * Test 3: Get images by product ID
     *
     * Verify all images for a product can be retrieved.
     */
    public function testGetImagesByProductId(): void
    {
        // Insert multiple images
        for ($i = 1; $i <= 3; $i++) {
            $this->repository->saveUpload([
                'product_id' => 1,
                'file_path' => "products/1/image_00{$i}.jpg",
                'file_name' => "image_00{$i}.jpg",
                'file_size' => 100000,
                'mime_type' => 'image/jpeg',
                'is_primary' => $i === 1 ? 1 : 0,
            ]);
        }

        $images = $this->repository->getByProductId(1);

        $this->assertIsArray($images);
        $this->assertCount(3, $images);

        // Verify primary is first
        $this->assertEquals(1, $images[0]['is_primary']);

        // Verify count
        $count = $this->repository->countByProductId(1);
        $this->assertEquals(3, $count);
    }

    /**
     * Additional test: Delete image
     *
     * Verify images can be deleted from database.
     */
    public function testDeleteImage(): void
    {
        $uploadId = $this->repository->saveUpload([
            'product_id' => 1,
            'file_path' => 'products/1/image_001.jpg',
            'file_name' => 'image_001.jpg',
            'file_size' => 100000,
            'mime_type' => 'image/jpeg',
        ]);

        // Verify it exists
        $image = $this->repository->findImage($uploadId);
        $this->assertNotNull($image);

        // Delete it
        $this->repository->deleteImage($uploadId);

        // Verify it's gone
        $image = $this->repository->findImage($uploadId);
        $this->assertNull($image);
    }
}
