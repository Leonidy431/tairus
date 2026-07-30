<?php
/**
 * Integration Test: Complete Image Gallery Workflow
 *
 * Tests the complete workflow from upload to display including
 * image optimization, database storage, and gallery data preparation.
 */

namespace Tests;

use PHPUnit\Framework\TestCase;
use App\Database\Database;
use App\Repository\UploadsRepository;
use App\Image\ImageOptimizer;

class ImageGalleryWorkflowTest extends TestCase
{
    private Database $db;
    private UploadsRepository $uploadsRepo;
    private ImageOptimizer $optimizer;
    private string $testDbPath;
    private string $testUploadDir;

    protected function setUp(): void
    {
        // Create test database
        $this->testDbPath = sys_get_temp_dir() . '/workflow_test_' . uniqid() . '.sqlite';
        $this->db = new Database('sqlite:' . $this->testDbPath);

        // Create test upload directory
        $this->testUploadDir = sys_get_temp_dir() . '/workflow_uploads_' . uniqid();
        mkdir($this->testUploadDir, 0755, true);

        // Create tables
        $this->createTables();

        $this->uploadsRepo = new UploadsRepository($this->db);
        $this->optimizer = new ImageOptimizer($this->testUploadDir);

        // Insert test product
        $this->db->insert('products', [
            'name' => 'Gallery Test Product',
            'description' => 'Test product for gallery workflow',
            'price' => 199.99,
            'is_public' => 1,
        ]);
    }

    protected function tearDown(): void
    {
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
                description TEXT,
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

    private function createTestImage(string $name = 'test.jpg'): string
    {
        $image = imagecreate(800, 600);
        $bgColor = imagecolorallocate($image, 100, 100, 100);
        imagefill($image, 0, 0, $bgColor);

        // Add some colored areas
        $redColor = imagecolorallocate($image, 255, 0, 0);
        $blueColor = imagecolorallocate($image, 0, 0, 255);

        imagefilledrectangle($image, 50, 50, 300, 200, $redColor);
        imagefilledrectangle($image, 400, 300, 700, 500, $blueColor);

        $fileName = $this->testUploadDir . '/' . $name;
        imagejpeg($image, $fileName, 85);
        imagedestroy($image);

        return $fileName;
    }

    /**
     * Test: Complete gallery workflow
     *
     * Simulates a real-world scenario of uploading, managing, and displaying
     * a product gallery.
     */
    public function testCompleteGalleryWorkflow(): void
    {
        $productId = 1;

        // Phase 1: Initial upload
        echo "\n=== Phase 1: Upload Initial Images ===\n";

        $uploadedImages = [];
        for ($i = 1; $i <= 3; $i++) {
            $testFile = $this->createTestImage("gallery_{$i}.jpg");

            // Process image
            $processed = $this->optimizer->process(
                $testFile,
                "gallery_{$i}.jpg",
                $productId
            );

            $this->assertNotNull($processed['medium']);
            $this->assertNotNull($processed['thumbnail']);

            // Save to database
            $uploadId = $this->uploadsRepo->saveUpload([
                'product_id' => $productId,
                'file_path' => $processed['medium'],
                'file_name' => "gallery_{$i}.jpg",
                'file_size' => filesize($testFile),
                'mime_type' => 'image/jpeg',
                'is_primary' => $i === 1 ? 1 : 0,
            ]);

            $uploadedImages[$i] = $uploadId;

            echo "Uploaded image {$i}: ID={$uploadId}\n";
        }

        $this->assertCount(3, $uploadedImages);

        // Phase 2: Verify gallery state
        echo "\n=== Phase 2: Verify Initial Gallery State ===\n";

        $images = $this->uploadsRepo->getByProductId($productId);
        $this->assertCount(3, $images);

        $primary = $this->uploadsRepo->getPrimaryImage($productId);
        $this->assertNotNull($primary);
        $this->assertEquals($uploadedImages[1], $primary['id']);

        $totalSize = $this->uploadsRepo->getTotalFileSize($productId);
        $this->assertGreaterThan(0, $totalSize);

        echo "Total images: " . count($images) . "\n";
        echo "Primary image ID: " . $primary['id'] . "\n";
        echo "Total storage: " . number_format($totalSize / 1024, 2) . " KB\n";

        // Phase 3: User edits gallery
        echo "\n=== Phase 3: Edit Gallery ===\n";

        // Upload additional image
        $testFile = $this->createTestImage('gallery_4.jpg');
        $processed = $this->optimizer->process($testFile, 'gallery_4.jpg', $productId);
        $newImageId = $this->uploadsRepo->saveUpload([
            'product_id' => $productId,
            'file_path' => $processed['medium'],
            'file_name' => 'gallery_4.jpg',
            'file_size' => filesize($testFile),
            'mime_type' => 'image/jpeg',
            'is_primary' => 0,
        ]);

        echo "Added new image: ID={$newImageId}\n";

        // Verify 4 images now
        $images = $this->uploadsRepo->getByProductId($productId);
        $this->assertCount(4, $images);

        // Reorder images
        $newOrder = [
            $uploadedImages[3],
            $uploadedImages[1],
            $uploadedImages[2],
            $newImageId,
        ];

        $this->uploadsRepo->reorderImages($newOrder, $productId);
        echo "Reordered images\n";

        // Change primary image
        $this->uploadsRepo->setPrimaryImage($productId, $uploadedImages[2]);
        $newPrimary = $this->uploadsRepo->getPrimaryImage($productId);
        $this->assertEquals($uploadedImages[2], $newPrimary['id']);

        echo "Changed primary image to ID: " . $uploadedImages[2] . "\n";

        // Phase 4: Delete image
        echo "\n=== Phase 4: Delete Image ===\n";

        $this->uploadsRepo->deleteImage($uploadedImages[3]);
        $images = $this->uploadsRepo->getByProductId($productId);
        $this->assertCount(3, $images);

        echo "Deleted image, remaining: " . count($images) . "\n";

        // Phase 5: Final gallery state
        echo "\n=== Phase 5: Final Gallery State ===\n";

        $images = $this->uploadsRepo->getByProductId($productId);
        $this->assertCount(3, $images);

        $primary = $this->uploadsRepo->getPrimaryImage($productId);
        $this->assertNotNull($primary);

        $totalSize = $this->uploadsRepo->getTotalFileSize($productId);

        echo "Total images: " . count($images) . "\n";
        echo "Primary image: " . $primary['file_name'] . "\n";
        echo "Total storage: " . number_format($totalSize / 1024, 2) . " KB\n";

        // Verify gallery is complete
        $this->assertCount(3, $images);
        $this->assertNotNull($primary);
        $this->assertGreaterThan(0, $totalSize);
    }

    /**
     * Test: Multiple products with separate galleries
     *
     * Ensures galleries don't interfere with each other.
     */
    public function testMultipleProductGalleries(): void
    {
        // Insert second product
        $this->db->insert('products', [
            'name' => 'Product 2',
            'price' => 149.99,
            'is_public' => 1,
        ]);

        // Add images to product 1
        for ($i = 1; $i <= 2; $i++) {
            $testFile = $this->createTestImage("p1_image_{$i}.jpg");
            $processed = $this->optimizer->process($testFile, "p1_image_{$i}.jpg", 1);

            $this->uploadsRepo->saveUpload([
                'product_id' => 1,
                'file_path' => $processed['medium'],
                'file_name' => "p1_image_{$i}.jpg",
                'file_size' => filesize($testFile),
                'mime_type' => 'image/jpeg',
                'is_primary' => $i === 1 ? 1 : 0,
            ]);
        }

        // Add images to product 2
        for ($i = 1; $i <= 3; $i++) {
            $testFile = $this->createTestImage("p2_image_{$i}.jpg");
            $processed = $this->optimizer->process($testFile, "p2_image_{$i}.jpg", 2);

            $this->uploadsRepo->saveUpload([
                'product_id' => 2,
                'file_path' => $processed['medium'],
                'file_name' => "p2_image_{$i}.jpg",
                'file_size' => filesize($testFile),
                'mime_type' => 'image/jpeg',
                'is_primary' => $i === 1 ? 1 : 0,
            ]);
        }

        // Verify separate galleries
        $p1Images = $this->uploadsRepo->getByProductId(1);
        $p2Images = $this->uploadsRepo->getByProductId(2);

        $this->assertCount(2, $p1Images);
        $this->assertCount(3, $p2Images);

        // Verify primaries
        $p1Primary = $this->uploadsRepo->getPrimaryImage(1);
        $p2Primary = $this->uploadsRepo->getPrimaryImage(2);

        $this->assertEquals('p1_image_1.jpg', $p1Primary['file_name']);
        $this->assertEquals('p2_image_1.jpg', $p2Primary['file_name']);

        // Get batch images
        $grouped = $this->uploadsRepo->getByProductIds([1, 2]);

        $this->assertArrayHasKey(1, $grouped);
        $this->assertArrayHasKey(2, $grouped);
        $this->assertCount(2, $grouped[1]);
        $this->assertCount(3, $grouped[2]);
    }
}
