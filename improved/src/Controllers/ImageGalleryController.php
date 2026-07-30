<?php
/**
 * Image Gallery Controller
 *
 * Handles product image gallery operations: upload, delete, set primary, reorder.
 */

namespace App\Controllers;

use App\Database\Database;
use App\Repository\UploadsRepository;
use App\Image\ImageOptimizer;
use App\Middleware\AdminMiddleware;

class ImageGalleryController
{
    private Database $db;
    private UploadsRepository $uploadsRepo;
    private ImageOptimizer $imageOptimizer;
    private AdminMiddleware $auth;
    private string $uploadDir;

    public function __construct(Database $db, AdminMiddleware $auth)
    {
        $this->db = $db;
        $this->auth = $auth;
        $this->uploadsRepo = new UploadsRepository($db);

        // Initialize upload directory
        $this->uploadDir = dirname(__DIR__, 2) . '/storage/uploads';
        $this->imageOptimizer = new ImageOptimizer($this->uploadDir);

        // Verify admin permissions
        if (!$this->auth->hasPermission('manage_products')) {
            AdminMiddleware::deny('Access denied: insufficient permissions');
        }
    }

    /**
     * Upload images for a product
     *
     * @param int $productId Product ID
     * @return array Response with upload results
     */
    public function uploadImage(int $productId): array
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            return ['success' => false, 'error' => 'Invalid request method'];
        }

        if (!isset($_FILES['images'])) {
            return ['success' => false, 'error' => 'No files provided'];
        }

        $files = $_FILES['images'];
        $uploads = [];
        $errors = [];

        // Handle both single and multiple file uploads
        $fileCount = is_array($files['name']) ? count($files['name']) : 1;

        for ($i = 0; $i < $fileCount; $i++) {
            try {
                $fileName = is_array($files['name']) ? $files['name'][$i] : $files['name'];
                $fileTmp = is_array($files['tmp_name']) ? $files['tmp_name'][$i] : $files['tmp_name'];
                $fileSize = is_array($files['size']) ? $files['size'][$i] : $files['size'];
                $fileMimeType = mime_content_type($fileTmp);

                if (empty($fileName) || empty($fileTmp)) {
                    continue;
                }

                // Process image
                $processedImages = $this->imageOptimizer->process($fileTmp, $fileName, $productId);

                // Determine if this should be primary (first image or if no primary exists)
                $isPrimary = 0;
                if ($i === 0 || $this->uploadsRepo->getPrimaryImage($productId) === null) {
                    $isPrimary = 1;
                }

                // Save upload record (use medium size as main display)
                $uploadData = [
                    'product_id' => $productId,
                    'file_path' => $processedImages['medium'],
                    'file_name' => basename($fileName),
                    'file_size' => $fileSize,
                    'mime_type' => $fileMimeType,
                    'is_primary' => $isPrimary,
                    'created_at' => date('Y-m-d H:i:s'),
                ];

                $uploadId = $this->uploadsRepo->saveUpload($uploadData);

                $uploads[] = [
                    'id' => $uploadId,
                    'file_name' => basename($fileName),
                    'file_size' => $fileSize,
                    'is_primary' => (bool)$isPrimary,
                    'processed_images' => $processedImages,
                ];
            } catch (\Exception $e) {
                $errors[] = $fileName . ': ' . $e->getMessage();
            }
        }

        if (empty($uploads) && !empty($errors)) {
            return [
                'success' => false,
                'error' => 'Failed to upload images: ' . implode(', ', $errors),
            ];
        }

        return [
            'success' => true,
            'uploads' => $uploads,
            'errors' => $errors,
            'message' => count($uploads) . ' image(s) uploaded successfully',
        ];
    }

    /**
     * Delete image from product
     *
     * @param int $uploadId Upload ID
     * @return array Response
     */
    public function deleteImage(int $uploadId): array
    {
        $image = $this->uploadsRepo->findImage($uploadId);

        if (!$image) {
            return ['success' => false, 'error' => 'Image not found'];
        }

        try {
            // Delete file from storage
            $imagePaths = [];
            if (!empty($image['file_path'])) {
                $imagePaths[] = $image['file_path'];

                // Also try to delete related variants
                $basePath = dirname($image['file_path']) . '/' . basename($image['file_path'], '.jpg');
                foreach (['_thumbnail.jpg', '_original.jpg', '_large.jpg', '_medium.webp', '_original.webp'] as $suffix) {
                    $imagePaths[] = $basePath . $suffix;
                }
            }

            $this->imageOptimizer->deleteImages($imagePaths);

            // Delete database record
            $this->uploadsRepo->deleteImage($uploadId);

            // If this was primary, set another as primary
            $productId = $image['product_id'];
            if ($image['is_primary']) {
                $nextImage = $this->uploadsRepo->getByProductId($productId)[0] ?? null;
                if ($nextImage) {
                    $this->uploadsRepo->setPrimaryImage($productId, $nextImage['id']);
                }
            }

            return [
                'success' => true,
                'message' => 'Image deleted successfully',
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => 'Failed to delete image: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Set image as primary for product
     *
     * @param int $uploadId Upload ID
     * @return array Response
     */
    public function setPrimaryImage(int $uploadId): array
    {
        $image = $this->uploadsRepo->findImage($uploadId);

        if (!$image) {
            return ['success' => false, 'error' => 'Image not found'];
        }

        try {
            $this->uploadsRepo->setPrimaryImage($image['product_id'], $uploadId);

            return [
                'success' => true,
                'message' => 'Primary image set successfully',
                'product_id' => $image['product_id'],
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => 'Failed to set primary image: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Reorder images for a product (drag and drop)
     *
     * @param int $productId Product ID
     * @return array Response
     */
    public function reorderImages(int $productId): array
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            return ['success' => false, 'error' => 'Invalid request method'];
        }

        $input = json_decode(file_get_contents('php://input'), true);

        if (!isset($input['order']) || !is_array($input['order'])) {
            return ['success' => false, 'error' => 'Invalid order data'];
        }

        try {
            $this->uploadsRepo->reorderImages($input['order'], $productId);

            return [
                'success' => true,
                'message' => 'Images reordered successfully',
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => 'Failed to reorder images: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Get all images for a product
     *
     * @param int $productId Product ID
     * @return array Images
     */
    public function getProductImages(int $productId): array
    {
        return $this->uploadsRepo->getByProductId($productId);
    }

    /**
     * Get gallery data for frontend display
     *
     * @param int $productId Product ID
     * @return array Gallery data with images
     */
    public function getGalleryData(int $productId): array
    {
        $images = $this->uploadsRepo->getByProductId($productId);
        $primaryImage = null;
        $galleryImages = [];

        foreach ($images as $image) {
            $imageData = [
                'id' => $image['id'],
                'path' => '/storage/uploads/' . $image['file_path'],
                'name' => $image['file_name'],
                'size' => $image['file_size'],
                'mime_type' => $image['mime_type'],
                'is_primary' => (bool)$image['is_primary'],
            ];

            if ($image['is_primary']) {
                $primaryImage = $imageData;
            }

            $galleryImages[] = $imageData;
        }

        return [
            'primary' => $primaryImage,
            'images' => $galleryImages,
            'total_size' => $this->uploadsRepo->getTotalFileSize($productId),
        ];
    }
}
