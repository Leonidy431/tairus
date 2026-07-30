<?php
/**
 * Unit Tests for Image Optimizer
 *
 * Tests image processing, validation, and optimization.
 */

namespace Tests;

use PHPUnit\Framework\TestCase;
use App\Image\ImageOptimizer;

class ImageOptimizerTest extends TestCase
{
    private string $testDir;
    private ImageOptimizer $optimizer;

    protected function setUp(): void
    {
        // Create temporary directory for tests
        $this->testDir = sys_get_temp_dir() . '/image_optimizer_test_' . uniqid();
        mkdir($this->testDir, 0755, true);
        mkdir($this->testDir . '/products', 0755, true);

        $this->optimizer = new ImageOptimizer($this->testDir);
    }

    protected function tearDown(): void
    {
        // Clean up test files
        $this->recursiveDelete($this->testDir);
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

    /**
     * Create a test image file
     *
     * @param int $width Width of image
     * @param int $height Height of image
     * @param string $format Image format (jpg, png)
     * @return string Path to test image
     */
    private function createTestImage(int $width = 500, int $height = 500, string $format = 'jpg'): string
    {
        $image = imagecreate($width, $height);
        $backgroundColor = imagecolorallocate($image, 200, 200, 200);
        imagefill($image, 0, 0, $backgroundColor);

        // Add some color to make it interesting
        $redColor = imagecolorallocate($image, 255, 0, 0);
        imagefilledrectangle($image, 50, 50, 150, 150, $redColor);

        $fileName = $this->testDir . '/test_image.' . $format;

        if ($format === 'jpg') {
            imagejpeg($image, $fileName, 85);
        } elseif ($format === 'png') {
            imagepng($image, $fileName);
        }

        imagedestroy($image);

        return $fileName;
    }

    /**
     * Test 1: Image validation
     *
     * Verify that image optimizer correctly validates files.
     */
    public function testImageValidation(): void
    {
        // Test valid JPEG
        $testFile = $this->createTestImage(500, 500, 'jpg');
        $this->assertTrue(file_exists($testFile), 'Test image should be created');

        // Test file size limit (should pass for normal image)
        $fileSize = filesize($testFile);
        $this->assertLessThan(ImageOptimizer::getMaxFileSize(), $fileSize, 'Test image should be under max size');

        // Test with non-existent file
        $this->expectException(\Exception::class);
        $this->optimizer->process('/non/existent/file.jpg', 'file.jpg', 1);
    }

    /**
     * Test 2: Image processing and resizing
     *
     * Verify that images are processed and resized correctly.
     */
    public function testImageProcessing(): void
    {
        $testFile = $this->createTestImage(800, 600, 'jpg');
        $productId = 1;

        $result = $this->optimizer->process($testFile, 'test_image.jpg', $productId);

        // Verify result structure
        $this->assertIsArray($result);
        $this->assertArrayHasKey('original', $result);
        $this->assertArrayHasKey('medium', $result);
        $this->assertArrayHasKey('large', $result);
        $this->assertArrayHasKey('thumbnail', $result);

        // Verify files were created
        $this->assertNotNull($result['original']);
        $this->assertNotNull($result['medium']);
        $this->assertNotNull($result['large']);
        $this->assertNotNull($result['thumbnail']);

        // Verify files exist
        $this->assertTrue(
            file_exists($this->testDir . '/' . $result['medium']),
            'Medium size image should exist'
        );
    }

    /**
     * Test 3: Allowed file types
     *
     * Verify that the optimizer correctly identifies allowed file types.
     */
    public function testAllowedFileTypes(): void
    {
        $allowedTypes = ImageOptimizer::getAllowedTypes();

        $this->assertIsArray($allowedTypes);
        $this->assertContains('jpg', $allowedTypes);
        $this->assertContains('png', $allowedTypes);
        $this->assertContains('webp', $allowedTypes);
        $this->assertContains('jpeg', $allowedTypes);

        // Verify max file size constant
        $maxSize = ImageOptimizer::getMaxFileSize();
        $this->assertEquals(10 * 1024 * 1024, $maxSize);
    }
}
