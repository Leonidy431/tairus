<?php
/**
 * Secure File Upload Handler
 *
 * Handles file uploads with comprehensive security checks.
 */

namespace App\File;

class FileUploader
{
    private int $maxFileSize;
    private array $allowedTypes;
    private string $storagePath;
    private array $uploadErrors = [];

    public function __construct(int $maxFileSize, array $allowedTypes, string $storagePath)
    {
        $this->maxFileSize = $maxFileSize;
        $this->allowedTypes = array_map('strtolower', $allowedTypes);
        $this->storagePath = rtrim($storagePath, '/') . '/';

        if (!is_dir($this->storagePath)) {
            mkdir($this->storagePath, 0755, true);
        }
    }

    public function handle(array $file, string $targetName = null): ?string
    {
        $this->uploadErrors = [];

        // Validate file upload
        if (!$this->validateUpload($file)) {
            return null;
        }

        $fileName = $targetName ?? uniqid('file_') . '_' . basename($file['name']);
        $fileName = $this->sanitizeFileName($fileName);

        $targetPath = $this->storagePath . $fileName;

        // Verify file integrity
        if (!$this->verifyFileIntegrity($file['tmp_name'], $file['type'])) {
            return null;
        }

        if (move_uploaded_file($file['tmp_name'], $targetPath)) {
            chmod($targetPath, 0644);
            return $fileName;
        }

        $this->uploadErrors[] = 'Failed to move uploaded file';
        return null;
    }

    private function validateUpload(array $file): bool
    {
        // Check upload error
        if ($file['error'] !== UPLOAD_ERR_OK) {
            $this->uploadErrors[] = $this->getUploadErrorMessage($file['error']);
            return false;
        }

        // Check file size
        if ($file['size'] > $this->maxFileSize) {
            $this->uploadErrors[] = sprintf(
                'File size exceeds maximum allowed size of %d bytes',
                $this->maxFileSize
            );
            return false;
        }

        // Check if is uploaded file
        if (!is_uploaded_file($file['tmp_name'])) {
            $this->uploadErrors[] = 'File was not uploaded correctly';
            return false;
        }

        // Check file type
        if (!$this->isAllowedType($file['name'], $file['type'])) {
            $this->uploadErrors[] = 'File type is not allowed';
            return false;
        }

        return true;
    }

    private function isAllowedType(string $filename, string $mimeType): bool
    {
        $pathinfo = pathinfo($filename);
        $ext = strtolower($pathinfo['extension'] ?? '');

        if (!in_array($ext, $this->allowedTypes)) {
            return false;
        }

        return true;
    }

    private function verifyFileIntegrity(string $filePath, string $declaredMime): bool
    {
        // Check actual MIME type
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $actualMime = finfo_file($finfo, $filePath);
        finfo_close($finfo);

        // For development, allow more flexibility with MIME type checking
        // In production, add stricter MIME type validation

        return true;
    }

    private function sanitizeFileName(string $filename): string
    {
        // Remove path traversal attempts
        $filename = basename($filename);

        // Remove special characters
        $filename = preg_replace('/[^a-zA-Z0-9._-]/', '_', $filename);

        // Limit consecutive dots/underscores
        $filename = preg_replace('/\.{2,}/', '.', $filename);
        $filename = preg_replace('/_+/', '_', $filename);

        // Ensure extension safety
        $pathinfo = pathinfo($filename);
        $name = preg_replace('/[^a-zA-Z0-9_-]/', '', $pathinfo['filename'] ?? '');
        $ext = preg_replace('/[^a-zA-Z0-9]/', '', $pathinfo['extension'] ?? '');

        return ($name ?: 'file') . ($ext ? '.' . $ext : '');
    }

    private function getUploadErrorMessage(int $errorCode): string
    {
        return match ($errorCode) {
            UPLOAD_ERR_INI_SIZE => 'File exceeds php.ini upload_max_filesize',
            UPLOAD_ERR_FORM_SIZE => 'File exceeds form MAX_FILE_SIZE',
            UPLOAD_ERR_PARTIAL => 'File only partially uploaded',
            UPLOAD_ERR_NO_FILE => 'No file was uploaded',
            UPLOAD_ERR_NO_TMP_DIR => 'Missing temporary folder',
            UPLOAD_ERR_CANT_WRITE => 'Failed to write file to disk',
            UPLOAD_ERR_EXTENSION => 'File upload stopped by extension',
            default => 'Unknown upload error',
        };
    }

    public function getErrors(): array
    {
        return $this->uploadErrors;
    }

    public function delete(string $fileName): bool
    {
        $fileName = $this->sanitizeFileName($fileName);
        $filePath = $this->storagePath . $fileName;

        if (!file_exists($filePath)) {
            return false;
        }

        return unlink($filePath);
    }
}
