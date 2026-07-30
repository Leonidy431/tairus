<?php
/**
 * Unit Tests for Image Gallery Controller
 *
 * Tests image gallery controller methods and functionality.
 */

namespace Tests;

use PHPUnit\Framework\TestCase;
use App\Database\Database;
use App\Controllers\ImageGalleryController;
use App\Repository\UploadsRepository;

class ImageGalleryControllerTest extends TestCase
{
    private Database $db;
    private UploadsRepository $repository;
    private string $testDbPath;
    private string $testUploadDir;

    protected function setUp(): void
    {
        // Create test database
        $this->testDbPath = sys_get_temp_dir() . '/test_' . uniqid() . '.sqlite';
        $this->db = new Database('sqlite:' . $this->testDbPath);

        // Create test upload directory
        $this->testUploadDir = sys_get_temp_dir() . '/test_uploads_' . uniqid();
        mkdir($this->testUploadDir, 0755, true);

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

        // Clean up upload directory
        $this->recursiveDelete($this->testUploadDir);
    }

    private function recursiveDelete(string $path): void
    {
        if (is_dir($path)) {
            $files = array_diff(scandir($path), ['.', '..']);
            foreach ($files as $file) {
                $this->recursiveDelete($path . '/' . $file);
            }
            rmdir($path);
        } else {
            if (file_exists($path)) {
                unlink($path);
            }
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
     * Test 1: Get gallery data
     *
     * Verify gallery data is properly formatted.
     */
    public function testGetGalleryData(): void
    {
        // Insert test images
        $id1 = $this->repository->saveUpload([
            'product_id' => 1,
            'file_path' => 'products/1/image_001.jpg',
            'file_name' => 'image_001.jpg',
            'file_size' => 102400,
            'mime_type' => 'image/jpeg',
            'is_primary' => 1,
        ]);

        $id2 = $this->repository->saveUpload([
            'product_id' => 1,
            'file_path' => 'products/1/image_002.jpg',
            'file_name' => 'image_002.jpg',
            'file_size' => 102400,
            'mime_type' => 'image/jpeg',
            'is_primary' => 0,
        ]);

        // Verify images
        $images = $this->repository->getByProductId(1);
        $this->assertCount(2, $images);

        // Check primary
        $primary = $this->repository->getPrimaryImage(1);
        $this->assertNotNull($primary);
        $this->assertEquals('image_001.jpg', $primary['file_name']);
    }

    /**
     * Test 2: Delete image validation
     *
     * Verify image deletion properly removes records.
     */
    public function testDeleteImageValidation(): void
    {
        $uploadId = $this->repository->saveUpload([
            'product_id' => 1,
            'file_path' => 'products/1/image_001.jpg',
            'file_name' => 'image_001.jpg',
            'file_size' => 102400,
            'mime_type' => 'image/jpeg',
            'is_primary' => 1,
        ]);

        // Verify it exists
        $image = $this->repository->findImage($uploadId);
        $this->assertNotNull($image);

        // Delete it
        $deleted = $this->repository->deleteImage($uploadId);
        $this->assertNotNull($deleted);

        // Verify it's gone
        $image = $this->repository->findImage($uploadId);
        $this->assertNull($image);
    }

    /**
     * Test 3: Get multiple product images
     *
     * Verify batch retrieval of images for multiple products.
     */
    public function testGetMultipleProductImages(): void
    {
        // Insert second product
        $this->db->insert('products', [
            'name' => 'Product 2',
            'price' => 49.99,
            'is_public' => 1,
        ]);

        // Insert images for both products
        $this->repository->saveUpload([
            'product_id' => 1,
            'file_path' => 'products/1/image_001.jpg',
            'file_name' => 'image_001.jpg',
            'file_size' => 100000,
            'mime_type' => 'image/jpeg',
            'is_primary' => 1,
        ]);

        $this->repository->saveUpload([
            'product_id' => 2,
            'file_path' => 'products/2/image_001.jpg',
            'file_name' => 'image_001.jpg',
            'file_size' => 100000,
            'mime_type' => 'image/jpeg',
            'is_primary' => 1,
        ]);

        // Get images for both products
        $grouped = $this->repository->getByProductIds([1, 2]);

        $this->assertArrayHasKey(1, $grouped);
        $this->assertArrayHasKey(2, $grouped);
        $this->assertCount(1, $grouped[1]);
        $this->assertCount(1, $grouped[2]);
    }

    /**
     * Test: Reorder images
     *
     * Verify image reordering works correctly.
     */
    public function testReorderImages(): void
    {
        $id1 = $this->repository->saveUpload([
            'product_id' => 1,
            'file_path' => 'products/1/image_001.jpg',
            'file_name' => 'image_001.jpg',
            'file_size' => 100000,
            'mime_type' => 'image/jpeg',
        ]);

        $id2 = $this->repository->saveUpload([
            'product_id' => 1,
            'file_path' => 'products/1/image_002.jpg',
            'file_name' => 'image_002.jpg',
            'file_size' => 100000,
            'mime_type' => 'image/jpeg',
        ]);

        // Reorder
        $success = $this->repository->reorderImages([$id2, $id1], 1);
        $this->assertTrue($success);

        // Verify order changed
        $images = $this->repository->getByProductId(1);
        $this->assertEquals($id2, $images[0]['id']);
        $this->assertEquals($id1, $images[1]['id']);
    }
}
