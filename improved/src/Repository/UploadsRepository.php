<?php
/**
 * Uploads Repository
 *
 * Manages product image uploads in the database.
 */

namespace App\Repository;

class UploadsRepository extends Repository
{
    protected string $table = 'uploads';

    /**
     * Get all images for a product
     *
     * @param int $productId Product ID
     * @return array Array of image records
     */
    public function getByProductId(int $productId): array
    {
        return $this->db->select(
            "SELECT * FROM {$this->table} WHERE product_id = ? ORDER BY is_primary DESC, created_at ASC",
            [$productId]
        );
    }

    /**
     * Get primary image for a product
     *
     * @param int $productId Product ID
     * @return array|null Primary image record or null
     */
    public function getPrimaryImage(int $productId): ?array
    {
        return $this->db->selectOne(
            "SELECT * FROM {$this->table} WHERE product_id = ? AND is_primary = 1",
            [$productId]
        );
    }

    /**
     * Set primary image for a product
     *
     * @param int $productId Product ID
     * @param int $uploadId Upload ID to set as primary
     * @return bool Success
     */
    public function setPrimaryImage(int $productId, int $uploadId): bool
    {
        // First, remove primary flag from all images for this product
        $this->db->update($this->table, ['is_primary' => 0], ['product_id' => $productId]);

        // Then set the new primary image
        return $this->db->update($this->table, ['is_primary' => 1], ['id' => $uploadId, 'product_id' => $productId]);
    }

    /**
     * Reorder images for a product
     *
     * @param array $imageOrder Array of image IDs in desired order
     * @param int $productId Product ID for validation
     * @return bool Success
     */
    public function reorderImages(array $imageOrder, int $productId): bool
    {
        // Verify all IDs belong to the product
        $ids = implode(',', array_map('intval', $imageOrder));
        $result = $this->db->select(
            "SELECT id FROM {$this->table} WHERE product_id = ? AND id IN ({$ids})",
            [$productId]
        );

        if (count($result) !== count($imageOrder)) {
            return false;
        }

        // Update order via created_at timestamps with microseconds
        foreach ($imageOrder as $index => $imageId) {
            $timestamp = date('Y-m-d H:i:s', time() + $index);
            $this->db->update($this->table, ['created_at' => $timestamp], ['id' => $imageId]);
        }

        return true;
    }

    /**
     * Delete image by ID
     *
     * @param int $uploadId Upload ID
     * @return array|null Deleted image record
     */
    public function deleteImage(int $uploadId): ?array
    {
        $image = $this->find($uploadId);

        if ($image) {
            $this->delete($uploadId);
        }

        return $image;
    }

    /**
     * Count images for a product
     *
     * @param int $productId Product ID
     * @return int Number of images
     */
    public function countByProductId(int $productId): int
    {
        return $this->count(['product_id' => $productId]);
    }

    /**
     * Get image by ID
     *
     * @param int $id Upload ID
     * @return array|null Image record
     */
    public function findImage(int $id): ?array
    {
        return $this->find($id);
    }

    /**
     * Save upload record
     *
     * @param array $data Upload data
     * @return int Upload ID
     */
    public function saveUpload(array $data): int
    {
        return $this->save($data);
    }

    /**
     * Get all images for multiple products
     *
     * @param array $productIds Array of product IDs
     * @return array Images grouped by product ID
     */
    public function getByProductIds(array $productIds): array
    {
        if (empty($productIds)) {
            return [];
        }

        $ids = implode(',', array_map('intval', $productIds));
        $images = $this->db->select(
            "SELECT * FROM {$this->table} WHERE product_id IN ({$ids}) ORDER BY product_id, is_primary DESC, created_at ASC"
        );

        $grouped = [];
        foreach ($images as $image) {
            $productId = $image['product_id'];
            if (!isset($grouped[$productId])) {
                $grouped[$productId] = [];
            }
            $grouped[$productId][] = $image;
        }

        return $grouped;
    }

    /**
     * Get total file size for a product
     *
     * @param int $productId Product ID
     * @return int Total size in bytes
     */
    public function getTotalFileSize(int $productId): int
    {
        $result = $this->db->selectOne(
            "SELECT SUM(file_size) as total FROM {$this->table} WHERE product_id = ?",
            [$productId]
        );

        return (int)($result['total'] ?? 0);
    }
}
