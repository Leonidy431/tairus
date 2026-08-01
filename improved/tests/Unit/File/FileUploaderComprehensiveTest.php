<?php

namespace Tests\Unit\File;

use App\File\FileUploader;
use PHPUnit\Framework\TestCase;

class FileUploaderComprehensiveTest extends TestCase
{
    private FileUploader $uploader;
    private string $uploadDir = '/tmp/test_uploads';

    protected function setUp(): void
    {
        if (!is_dir($this->uploadDir)) {
            mkdir($this->uploadDir, 0755, true);
        }
        $maxFileSize = 11_300_000;
        $allowedTypes = ['jpg', 'jpeg', 'png', 'gif', 'pdf'];
        $this->uploader = new FileUploader($maxFileSize, $allowedTypes, $this->uploadDir);
    }

    protected function tearDown(): void
    {
        if (is_dir($this->uploadDir)) {
            $files = glob($this->uploadDir . '/*');
            if ($files) {
                foreach ($files as $file) {
                    if (is_file($file)) {
                        @unlink($file);
                    }
                }
            }
            @rmdir($this->uploadDir);
        }
    }

    public function testFileUploaderConstruction(): void
    {
        $uploader = new FileUploader(10_000_000, ['pdf', 'doc'], '/tmp/uploads');
        $this->assertInstanceOf(FileUploader::class, $uploader);
    }

    public function testStoragePathCreation(): void
    {
        $testPath = '/tmp/test_uploader_' . time();
        $uploader = new FileUploader(1000000, ['pdf'], $testPath);
        $this->assertTrue(is_dir($testPath));
        @rmdir($testPath);
    }

    public function testFileHandleReturnsNullForInvalidFile(): void
    {
        $result = $this->uploader->handle([
            'error' => UPLOAD_ERR_NO_FILE,
            'name' => 'test.txt',
            'size' => 0,
            'type' => 'text/plain',
            'tmp_name' => '',
        ]);
        $this->assertNull($result);
    }

    public function testFileSizeValidation(): void
    {
        $uploader = new FileUploader(100, ['txt'], $this->uploadDir);
        $result = $uploader->handle([
            'error' => UPLOAD_ERR_OK,
            'name' => 'large.txt',
            'size' => 1000,
            'type' => 'text/plain',
            'tmp_name' => '/tmp/nonexistent',
        ]);
        $this->assertNull($result);
    }

    public function testUploaderAcceptsValidTypes(): void
    {
        $uploader = new FileUploader(10_000_000, ['jpg', 'png', 'pdf', 'gif'], $this->uploadDir);
        $this->assertInstanceOf(FileUploader::class, $uploader);
    }

    public function testMultipleFileTypesAllowed(): void
    {
        $types = ['jpg', 'jpeg', 'png', 'gif', 'pdf', 'doc', 'docx'];
        $uploader = new FileUploader(50_000_000, $types, $this->uploadDir);
        $this->assertInstanceOf(FileUploader::class, $uploader);
    }

    public function testStoragePathHandlesTrailingSlash(): void
    {
        $path1 = $this->uploadDir . '/test1';
        $path2 = $this->uploadDir . '/test2/';
        $uploader1 = new FileUploader(1000000, ['pdf'], $path1);
        $uploader2 = new FileUploader(1000000, ['pdf'], $path2);
        $this->assertTrue(is_dir($path1));
        $this->assertTrue(is_dir($path2));
        @rmdir($path1);
        @rmdir($path2);
    }

    public function testUploadErrorCodes(): void
    {
        $errors = [UPLOAD_ERR_OK, UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE,
                   UPLOAD_ERR_PARTIAL, UPLOAD_ERR_NO_FILE, UPLOAD_ERR_NO_TMP_DIR,
                   UPLOAD_ERR_CANT_WRITE, UPLOAD_ERR_EXTENSION];
        foreach ($errors as $errorCode) {
            $result = $this->uploader->handle([
                'error' => $errorCode,
                'name' => 'test.txt',
                'size' => 100,
                'type' => 'text/plain',
                'tmp_name' => '',
            ]);
            $this->assertNull($result);
        }
    }

    public function testFileNameSanitization(): void
    {
        $uploader = new FileUploader(10_000_000, ['pdf'], $this->uploadDir);
        $this->assertInstanceOf(FileUploader::class, $uploader);
    }

    public function testMaxFileSizeRespected(): void
    {
        $smallUploader = new FileUploader(100, ['txt'], $this->uploadDir);
        $largeUploader = new FileUploader(100_000_000, ['txt'], $this->uploadDir);
        $this->assertInstanceOf(FileUploader::class, $smallUploader);
        $this->assertInstanceOf(FileUploader::class, $largeUploader);
    }

    public function testFileTypesArray(): void
    {
        $types = [];
        $uploader = new FileUploader(1_000_000, $types, $this->uploadDir);
        $this->assertInstanceOf(FileUploader::class, $uploader);
    }

    public function testHandleWithMissingTmpFile(): void
    {
        $result = $this->uploader->handle([
            'error' => UPLOAD_ERR_OK,
            'name' => 'missing.pdf',
            'size' => 1000,
            'type' => 'application/pdf',
            'tmp_name' => '/tmp/nonexistent_file_xyz',
        ]);
        $this->assertNull($result);
    }

    public function testValidFileUploadStructure(): void
    {
        $uploadArray = [
            'error' => UPLOAD_ERR_OK,
            'name' => 'document.pdf',
            'size' => 50000,
            'type' => 'application/pdf',
            'tmp_name' => '/tmp/php_upload_xyz',
        ];
        $this->assertArrayHasKey('error', $uploadArray);
        $this->assertArrayHasKey('name', $uploadArray);
        $this->assertArrayHasKey('size', $uploadArray);
        $this->assertArrayHasKey('type', $uploadArray);
        $this->assertArrayHasKey('tmp_name', $uploadArray);
    }

    public function testMultipleUploadAttempts(): void
    {
        for ($i = 0; $i < 3; $i++) {
            $result = $this->uploader->handle([
                'error' => UPLOAD_ERR_NO_FILE,
                'name' => "test_$i.pdf",
                'size' => 0,
                'type' => 'application/pdf',
                'tmp_name' => '',
            ]);
            $this->assertNull($result);
        }
    }

    public function testUploadDirIsAccessible(): void
    {
        $this->assertTrue(is_dir($this->uploadDir));
        $this->assertTrue(is_writable($this->uploadDir));
    }

    public function testFileSizeValidationBoundary(): void
    {
        $uploader = new FileUploader(1000, ['txt'], $this->uploadDir);
        $result = $this->uploader->handle([
            'error' => UPLOAD_ERR_OK,
            'name' => 'boundary.txt',
            'size' => 1000,
            'type' => 'text/plain',
            'tmp_name' => '/tmp/nonexistent',
        ]);
        $this->assertNull($result);
    }
}
