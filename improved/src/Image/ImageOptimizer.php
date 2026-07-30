<?php
/**
 * Image Optimizer Utility
 *
 * Handles image resizing, WebP conversion, and optimization.
 */

namespace App\Image;

class ImageOptimizer
{
    // Image size presets (in pixels)
    const SIZES = [
        'thumbnail' => 150,
        'medium' => 400,
        'large' => 800,
    ];

    // Allowed MIME types
    const ALLOWED_TYPES = [
        'image/jpeg',
        'image/png',
        'image/webp',
    ];

    // Maximum file size (10MB)
    const MAX_FILE_SIZE = 10 * 1024 * 1024;

    private string $uploadDir;

    public function __construct(string $uploadDir)
    {
        $this->uploadDir = rtrim($uploadDir, '/');
        $this->ensureUploadDir();
    }

    /**
     * Process and optimize an uploaded image
     *
     * @param string $filePath Path to the uploaded file
     * @param string $fileName Original file name
     * @param int $productId Product ID for organizing files
     * @return array Array with original and optimized file paths
     * @throws \Exception
     */
    public function process(string $filePath, string $fileName, int $productId): array
    {
        // Validate file
        $this->validateFile($filePath, $fileName);

        // Create product directory
        $productDir = $this->createProductDirectory($productId);

        // Generate unique file name without extension
        $baseName = $this->generateUniqueFileName($fileName);

        // Create GD image resource
        $image = $this->loadImage($filePath);

        if (!$image) {
            throw new \Exception('Failed to load image');
        }

        $result = [
            'original' => null,
            'thumbnail' => null,
            'medium' => null,
            'large' => null,
            'webp_original' => null,
            'webp_medium' => null,
        ];

        try {
            // Save original with optimized JPG
            $result['original'] = $this->saveOptimizedImage(
                $image,
                $productDir,
                $baseName . '_original',
                null,
                85
            );

            // Create WebP version of original
            $result['webp_original'] = $this->saveAsWebP(
                $image,
                $productDir,
                $baseName . '_original',
                null
            );

            // Resize and save thumbnails
            $result['thumbnail'] = $this->createResizedImage(
                $image,
                $productDir,
                $baseName . '_thumbnail',
                self::SIZES['thumbnail'],
                90
            );

            // Resize and save medium
            $result['medium'] = $this->createResizedImage(
                $image,
                $productDir,
                $baseName . '_medium',
                self::SIZES['medium'],
                85
            );

            // Create WebP version of medium
            $result['webp_medium'] = $this->createResizedAndWebP(
                $image,
                $productDir,
                $baseName . '_medium',
                self::SIZES['medium']
            );

            // Resize and save large
            $result['large'] = $this->createResizedImage(
                $image,
                $productDir,
                $baseName . '_large',
                self::SIZES['large'],
                80
            );

            return $result;
        } finally {
            imagedestroy($image);
        }
    }

    /**
     * Validate uploaded file
     *
     * @param string $filePath Path to file
     * @param string $fileName File name
     * @throws \Exception
     */
    private function validateFile(string $filePath, string $fileName): void
    {
        // Check file exists
        if (!file_exists($filePath)) {
            throw new \Exception('File does not exist');
        }

        // Check file size
        $fileSize = filesize($filePath);
        if ($fileSize > self::MAX_FILE_SIZE) {
            throw new \Exception('File size exceeds maximum limit of 10MB');
        }

        // Check MIME type
        $mimeType = mime_content_type($filePath);
        if (!in_array($mimeType, self::ALLOWED_TYPES)) {
            throw new \Exception('File type not allowed. Allowed types: JPG, PNG, WebP');
        }

        // Prevent path traversal
        if (strpos($fileName, '..') !== false || strpos($fileName, '/') !== false) {
            throw new \Exception('Invalid file name');
        }
    }

    /**
     * Load image using GD library
     *
     * @param string $filePath Path to image file
     * @return resource|false Image resource
     */
    private function loadImage(string $filePath)
    {
        $mimeType = mime_content_type($filePath);

        switch ($mimeType) {
            case 'image/jpeg':
                return imagecreatefromjpeg($filePath);
            case 'image/png':
                return imagecreatefrompng($filePath);
            case 'image/webp':
                return imagecreatefromwebp($filePath);
            default:
                return false;
        }
    }

    /**
     * Save optimized JPEG image
     *
     * @param resource $image Image resource
     * @param string $dir Directory to save
     * @param string $baseName Base file name (without extension)
     * @param int|null $maxWidth Maximum width
     * @param int $quality JPEG quality (0-100)
     * @return string Relative file path
     */
    private function saveOptimizedImage(
        $image,
        string $dir,
        string $baseName,
        ?int $maxWidth = null,
        int $quality = 85
    ): string {
        $width = imagesx($image);
        $height = imagesy($image);

        // Resize if needed
        if ($maxWidth && $width > $maxWidth) {
            $ratio = $maxWidth / $width;
            $newHeight = (int)($height * $ratio);
            $resized = imagescale($image, $maxWidth, $newHeight, IMG_BILINEAR_FIXED);
            $image = $resized;
        }

        $fileName = $baseName . '.jpg';
        $filePath = $dir . '/' . $fileName;

        imagejpeg($image, $filePath, $quality);

        return str_replace($this->uploadDir . '/', '', $filePath);
    }

    /**
     * Save image as WebP
     *
     * @param resource $image Image resource
     * @param string $dir Directory to save
     * @param string $baseName Base file name (without extension)
     * @param int|null $maxWidth Maximum width
     * @return string|null Relative file path or null if WebP not supported
     */
    private function saveAsWebP(
        $image,
        string $dir,
        string $baseName,
        ?int $maxWidth = null
    ): ?string {
        if (!function_exists('imagewebp')) {
            return null;
        }

        $width = imagesx($image);
        $height = imagesy($image);

        // Resize if needed
        if ($maxWidth && $width > $maxWidth) {
            $ratio = $maxWidth / $width;
            $newHeight = (int)($height * $ratio);
            $image = imagescale($image, $maxWidth, $newHeight, IMG_BILINEAR_FIXED);
        }

        $fileName = $baseName . '.webp';
        $filePath = $dir . '/' . $fileName;

        imagewebp($image, $filePath, 80);

        return str_replace($this->uploadDir . '/', '', $filePath);
    }

    /**
     * Create resized image
     *
     * @param resource $image Original image resource
     * @param string $dir Directory to save
     * @param string $baseName Base file name
     * @param int $maxSize Maximum width/height
     * @param int $quality JPEG quality
     * @return string Relative file path
     */
    private function createResizedImage(
        $image,
        string $dir,
        string $baseName,
        int $maxSize,
        int $quality
    ): string {
        $width = imagesx($image);
        $height = imagesy($image);

        $ratio = $maxSize / max($width, $height);
        $newWidth = (int)($width * $ratio);
        $newHeight = (int)($height * $ratio);

        $resized = imagescale($image, $newWidth, $newHeight, IMG_BILINEAR_FIXED);

        $fileName = $baseName . '.jpg';
        $filePath = $dir . '/' . $fileName;

        imagejpeg($resized, $filePath, $quality);
        imagedestroy($resized);

        return str_replace($this->uploadDir . '/', '', $filePath);
    }

    /**
     * Create resized image and save as WebP
     *
     * @param resource $image Original image resource
     * @param string $dir Directory to save
     * @param string $baseName Base file name
     * @param int $maxSize Maximum width/height
     * @return string|null Relative file path or null
     */
    private function createResizedAndWebP(
        $image,
        string $dir,
        string $baseName,
        int $maxSize
    ): ?string {
        if (!function_exists('imagewebp')) {
            return null;
        }

        $width = imagesx($image);
        $height = imagesy($image);

        $ratio = $maxSize / max($width, $height);
        $newWidth = (int)($width * $ratio);
        $newHeight = (int)($height * $ratio);

        $resized = imagescale($image, $newWidth, $newHeight, IMG_BILINEAR_FIXED);

        $fileName = $baseName . '.webp';
        $filePath = $dir . '/' . $fileName;

        imagewebp($resized, $filePath, 75);
        imagedestroy($resized);

        return str_replace($this->uploadDir . '/', '', $filePath);
    }

    /**
     * Ensure upload directory exists
     */
    private function ensureUploadDir(): void
    {
        if (!is_dir($this->uploadDir)) {
            mkdir($this->uploadDir, 0755, true);
        }
    }

    /**
     * Create product directory
     *
     * @param int $productId Product ID
     * @return string Full path to product directory
     */
    private function createProductDirectory(int $productId): string
    {
        $dir = $this->uploadDir . '/products/' . $productId;

        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        return $dir;
    }

    /**
     * Generate unique file name
     *
     * @param string $originalName Original file name
     * @return string Unique base name
     */
    private function generateUniqueFileName(string $originalName): string
    {
        $pathInfo = pathinfo($originalName);
        $baseName = preg_replace('/[^a-zA-Z0-9_-]/', '_', $pathInfo['filename']);
        $baseName = preg_replace('/_+/', '_', $baseName);
        $baseName = trim($baseName, '_');

        return $baseName . '_' . uniqid();
    }

    /**
     * Delete image files
     *
     * @param array $imagePaths Array of image file paths
     */
    public function deleteImages(array $imagePaths): void
    {
        foreach ($imagePaths as $path) {
            if (empty($path)) {
                continue;
            }

            $fullPath = $this->uploadDir . '/' . $path;

            if (file_exists($fullPath)) {
                unlink($fullPath);
            }
        }
    }

    /**
     * Get allowed file types
     *
     * @return array
     */
    public static function getAllowedTypes(): array
    {
        return ['jpg', 'jpeg', 'png', 'webp'];
    }

    /**
     * Get maximum file size
     *
     * @return int Size in bytes
     */
    public static function getMaxFileSize(): int
    {
        return self::MAX_FILE_SIZE;
    }
}
