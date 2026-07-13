<?php

namespace Tests\Unit\File;

use App\File\FileUploader;
use PHPUnit\Framework\TestCase;

class FileUploaderTest extends TestCase
{
    private FileUploader $uploader;
    private string $testUploadDir;

    protected function setUp(): void
    {
        $this->testUploadDir = sys_get_temp_dir() . '/test-uploads-' . uniqid();
        mkdir($this->testUploadDir, 0755, true);

        $this->uploader = new FileUploader(
            1_000_000,  // 1MB
            ['jpg', 'png', 'gif', 'pdf', 'txt'],
            $this->testUploadDir
        );
    }

    protected function tearDown(): void
    {
        // Clean up test directory
        $files = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($this->testUploadDir, \RecursiveDirectoryIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::CHILD_FIRST
        );

        foreach ($files as $file) {
            if ($file->isDir()) {
                rmdir($file->getPathname());
            } else {
                unlink($file->getPathname());
            }
        }
        rmdir($this->testUploadDir);
    }

    public function testFileUploaderInitialization(): void
    {
        $this->assertInstanceOf(FileUploader::class, $this->uploader);
    }

    public function testUploadValidFile(): void
    {
        // Create a mock uploaded file
        $testFile = $this->createTestFile('test.txt', 'Hello World');

        $result = $this->uploader->handle([
            'name' => 'test.txt',
            'type' => 'text/plain',
            'tmp_name' => $testFile,
            'error' => UPLOAD_ERR_OK,
            'size' => 11,
        ]);

        $this->assertNotNull($result);
        $this->assertTrue(file_exists($this->testUploadDir . '/' . $result));

        unlink($testFile);
    }

    public function testRejectFileWithInvalidType(): void
    {
        $testFile = $this->createTestFile('test.exe', 'malware');

        $result = $this->uploader->handle([
            'name' => 'test.exe',
            'type' => 'application/x-msdownload',
            'tmp_name' => $testFile,
            'error' => UPLOAD_ERR_OK,
            'size' => 7,
        ]);

        $this->assertNull($result);
        $this->assertNotEmpty($this->uploader->getErrors());

        unlink($testFile);
    }

    public function testRejectFileThatExceedsMaxSize(): void
    {
        // Create a file larger than allowed
        $largeUploader = new FileUploader(100, ['txt'], $this->testUploadDir);
        $testFile = $this->createTestFile('large.txt', str_repeat('a', 200));

        $result = $largeUploader->handle([
            'name' => 'large.txt',
            'type' => 'text/plain',
            'tmp_name' => $testFile,
            'error' => UPLOAD_ERR_OK,
            'size' => 200,
        ]);

        $this->assertNull($result);

        unlink($testFile);
    }

    public function testSanitizeFileName(): void
    {
        $testFile = $this->createTestFile('test.txt', 'content');

        $result = $this->uploader->handle([
            'name' => '../../../etc/passwd.txt',
            'type' => 'text/plain',
            'tmp_name' => $testFile,
            'error' => UPLOAD_ERR_OK,
            'size' => 7,
        ]);

        // Should sanitize filename to prevent directory traversal
        $this->assertNotNull($result);
        $this->assertStringNotContainsString('..', $result);
        $this->assertStringNotContainsString('/', $result);

        unlink($testFile);
    }

    public function testHandleUploadError(): void
    {
        $testFile = $this->createTestFile('test.txt', 'content');

        $result = $this->uploader->handle([
            'name' => 'test.txt',
            'type' => 'text/plain',
            'tmp_name' => $testFile,
            'error' => UPLOAD_ERR_NO_FILE,
            'size' => 7,
        ]);

        $this->assertNull($result);
        $this->assertNotEmpty($this->uploader->getErrors());

        unlink($testFile);
    }

    public function testDeleteFile(): void
    {
        $testFile = $this->createTestFile('test.txt', 'content');

        $filename = $this->uploader->handle([
            'name' => 'test.txt',
            'type' => 'text/plain',
            'tmp_name' => $testFile,
            'error' => UPLOAD_ERR_OK,
            'size' => 7,
        ]);

        $this->assertNotNull($filename);
        $this->assertTrue($this->uploader->delete($filename));

        unlink($testFile);
    }

    public function testPreventDoubleExtensionAttack(): void
    {
        $testFile = $this->createTestFile('test.php.txt', 'malicious php');

        $result = $this->uploader->handle([
            'name' => 'test.php.txt',
            'type' => 'text/plain',
            'tmp_name' => $testFile,
            'error' => UPLOAD_ERR_OK,
            'size' => 13,
        ]);

        // Should accept .txt but prevent .php execution
        $this->assertNotNull($result);
        $this->assertStringEndsWith('.txt', $result);

        unlink($testFile);
    }

    /**
     * Helper to create a test file
     */
    private function createTestFile(string $name, string $content): string
    {
        $testFile = sys_get_temp_dir() . '/' . uniqid($name);
        file_put_contents($testFile, $content);
        return $testFile;
    }
}
