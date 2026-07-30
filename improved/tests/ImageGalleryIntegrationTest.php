<?php
/**
 * Integration Tests for Image Gallery
 *
 * Tests complete image gallery workflows from upload to display.
 */

namespace Tests;

use PHPUnit\Framework\TestCase;
use App\Database\Database;
use App\Repository\UploadsRepository;
use App\Image\ImageOptimizer;

class ImageGalleryIntegrationTest extends TestCase
{
    private Database $db;
    private UploadsRepository $uploadsRepo;
    private ImageOptimizer $optimizer;
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

        $this->uploadsRepo = new UploadsRepository($this->db);
        $this->optimizer = new ImageOptimizer($this->testUploadDir);

        // Insert test products
        $this->db->insert('products', [
            'name' => 'Product 1',
            'price' => 99.99,
            'is_public' => 1,
        ]);

        $this->db->insert('products', [
            'name' => 'Product 2',
            'price' => 49.99,
            'is_public' => 1,
        ]);
    }

    protected function tearDown(): void
    {
        // Clean up
        if (file_exists($this->testDbPath)) {
            unlink($this->testDbPath);
        }
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
     * Create a test image file
     */
    private function createTestImage(int $width = 500, int $height = 500): string
    {
        $image = imagecreate($width, $height);
        $backgroundColor = imagecolorallocate($image, 200, 200, 200);
        imagefill($image, 0, 0, $backgroundColor);

        $redColor = imagecolorallocate($image, 255, 0, 0);
        imagefilledrectangle($image, 50, 50, 150, 150, $redColor);

        $fileName = $this->testUploadDir . '/test_image_' . uniqid() . '.jpg';
        imagejpeg($image, $fileName, 85);
        imagedestroy($image);

        return $fileName;
    }

    /**
     * Integration Test 1: Complete image upload workflow
     *
     * Tests uploading, storing, and retrieving images.
     */
    public function testCompleteImageUploadWorkflow(): void
    {
        $productId = 1;
        $testFile = $this->createTestImage(800, 600);

        // Step 1: Process and optimize image
        $processedImages = $this->optimizer->process($testFile, 'product_image.jpg', $productId);

        $this->assertNotNull($processedImages['medium']);
        $this->assertNotNull($processedImages['large']);
        $this->assertNotNull($processedImages['thumbnail']);

        // Step 2: Save to database
        $uploadId = $this->uploadsRepo->saveUpload([
            'product_id' => $productId,
            'file_path' => $processedImages['medium'],
            'file_name' => 'product_image.jpg',
            'file_size' => filesize($testFile),
            'mime_type' => 'image/jpeg',
            'is_primary' => 1,
        ]);

        $this->assertGreaterThan(0, $uploadId);

        // Step 3: Retrieve from database
        $image = $this->uploadsRepo->findImage($uploadId);

        $this->assertNotNull($image);
        $this->assertEquals('product_image.jpg', $image['file_name']);
        $this->assertEquals($productId, $image['product_id']);
        $this->assertEquals(1, $image['is_primary']);

        // Step 4: Get all product images
        $allImages = $this->uploadsRepo->getByProductId($productId);

        $this->assertCount(1, $allImages);
        $this->assertEquals($uploadId, $allImages[0]['id']);

        // Step 5: Verify primary image
        $primary = $this->uploadsRepo->getPrimaryImage($productId);

        $this->assertNotNull($primary);
        $this->assertEquals($uploadId, $primary['id']);
    }

    /**
     * Integration Test 2: Multi-image gallery management
     *
     * Tests managing multiple images for a product including reordering.
     */
    public function testMultiImageGalleryManagement(): void
    {
        $productId = 1;
        $imageIds = [];

        // Step 1: Upload multiple images
        for ($i = 1; $i <= 3; $i++) {
            $testFile = $this->createTestImage(600 + ($i * 100), 400 + ($i * 50));
            $processedImages = $this->optimizer->process($testFile, "image_{$i}.jpg", $productId);

            $uploadId = $this->uploadsRepo->saveUpload([
                'product_id' => $productId,
                'file_path' => $processedImages['medium'],
                'file_name' => "image_{$i}.jpg",
                'file_size' => filesize($testFile),
                'mime_type' => 'image/jpeg',
                'is_primary' => $i === 1 ? 1 : 0,
            ]);

            $imageIds[] = $uploadId;
        }

        // Step 2: Verify all images
        $images = $this->uploadsRepo->getByProductId($productId);
        $this->assertCount(3, $images);

        // Step 3: Verify primary image
        $primary = $this->uploadsRepo->getPrimaryImage($productId);
        $this->assertEquals($imageIds[0], $primary['id']);

        // Step 4: Change primary image
        $this->uploadsRepo->setPrimaryImage($productId, $imageIds[1]);

        $newPrimary = $this->uploadsRepo->getPrimaryImage($productId);
        $this->assertEquals($imageIds[1], $newPrimary['id']);

        // Step 5: Reorder images
        $newOrder = [$imageIds[2], $imageIds[0], $imageIds[1]];
        $success = $this->uploadsRepo->reorderImages($newOrder, $productId);
        $this->assertTrue($success);

        // Step 6: Verify new order
        $reorderedImages = $this->uploadsRepo->getByProductId($productId);
        $this->assertEquals($imageIds[2], $reorderedImages[0]['id']);
        $this->assertEquals($imageIds[0], $reorderedImages[1]['id']);
        $this->assertEquals($imageIds[1], $reorderedImages[2]['id']);

        // Step 7: Delete middle image
        $this->uploadsRepo->deleteImage($imageIds[1]);

        $remainingImages = $this->uploadsRepo->getByProductId($productId);
        $this->assertCount(2, $remainingImages);

        // Step 8: Verify deletion
        $deleted = $this->uploadsRepo->findImage($imageIds[1]);
        $this->assertNull($deleted);
    }

    /**
     * Additional test: Total file size calculation
     *
     * Verifies storage usage tracking.
     */
    public function testTotalFileSizeCalculation(): void
    {
        $productId = 1;

        // Upload multiple files with known sizes
        for ($i = 1; $i <= 2; $i++) {
            $testFile = $this->createTestImage(500, 500);

            $this->uploadsRepo->saveUpload([
                'product_id' => $productId,
                'file_path' => "products/{$productId}/image_{$i}.jpg",
                'file_name' => "image_{$i}.jpg",
                'file_size' => 50000 * $i, // 50KB, 100KB
                'mime_type' => 'image/jpeg',
            ]);
        }

        $totalSize = $this->uploadsRepo->getTotalFileSize($productId);

        // Should be 50KB + 100KB = 150KB
        $this->assertEquals(150000, $totalSize);
    }
}
