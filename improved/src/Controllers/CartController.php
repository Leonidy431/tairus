<?php
/**
 * Cart Controller
 *
 * Handles shopping cart operations including adding, removing, and updating items.
 * Supports both authenticated users and guest users with session-based persistence.
 */

namespace App\Controllers;

use App\Database\Database;
use App\Repository\CartRepository;
use App\Security\Sanitizer;
use App\Security\CsrfToken;

class CartController
{
    private Database $db;
    private CartRepository $cartRepository;

    public function __construct(Database $db)
    {
        $this->db = $db;
        $this->cartRepository = new CartRepository($db);
    }

    /**
     * Get current user ID from session, or null for guests
     */
    private function getUserId(): ?int
    {
        return $_SESSION['user_id'] ?? null;
    }

    /**
     * Get or create session ID for guest users
     */
    private function getSessionId(): string
    {
        if (!isset($_SESSION['cart_session_id'])) {
            $_SESSION['cart_session_id'] = session_id();
        }
        return $_SESSION['cart_session_id'];
    }

    /**
     * View cart page
     */
    public function viewCart(): array
    {
        $userId = $this->getUserId();
        $cartItems = [];
        $total = 0.0;
        $cartCount = 0;

        if ($userId !== null) {
            // Authenticated user
            $cartItems = $this->cartRepository->getCart($userId);
            $total = $this->cartRepository->getTotal($userId);
            $cartCount = count($cartItems);
        } else {
            // Guest user - load from session
            $cartData = $this->loadGuestCart();
            $cartItems = $cartData['items'];
            $total = $cartData['total'];
            $cartCount = $cartData['count'];
        }

        return [
            'success' => true,
            'cart_items' => $cartItems,
            'total' => round($total, 2),
            'item_count' => $cartCount,
            'csrf_token' => CsrfToken::generate(),
        ];
    }

    /**
     * Add product to cart
     */
    public function addToCart(): array
    {
        // Validate CSRF token
        if (!CsrfToken::validateFromRequest()) {
            return ['success' => false, 'error' => 'Invalid security token'];
        }

        $productId = Sanitizer::integer($_POST['product_id'] ?? null);
        $quantity = Sanitizer::integer($_POST['quantity'] ?? 1) ?? 1;

        if ($productId === null || $productId <= 0) {
            return ['success' => false, 'error' => 'Invalid product ID'];
        }

        if ($quantity <= 0) {
            return ['success' => false, 'error' => 'Invalid quantity'];
        }

        // Limit quantity
        $quantity = min($quantity, 999);

        // Validate product exists and is public
        $product = $this->cartRepository->validateProduct($productId);
        if (!$product) {
            return ['success' => false, 'error' => 'Product not found or unavailable'];
        }

        $userId = $this->getUserId();

        if ($userId !== null) {
            // Authenticated user - save to database
            $this->cartRepository->addItem($userId, $productId, $quantity);
        } else {
            // Guest user - save to session
            $this->addGuestCartItem($productId, $quantity);
        }

        return [
            'success' => true,
            'message' => 'Product added to cart',
            'product_id' => $productId,
            'item_count' => $this->getCartItemCount(),
        ];
    }

    /**
     * Remove item from cart
     */
    public function removeFromCart(): array
    {
        // Validate CSRF token
        if (!CsrfToken::validateFromRequest()) {
            return ['success' => false, 'error' => 'Invalid security token'];
        }

        $cartId = Sanitizer::integer($_POST['cart_id'] ?? null);

        if ($cartId === null || $cartId <= 0) {
            return ['success' => false, 'error' => 'Invalid cart item ID'];
        }

        $userId = $this->getUserId();

        if ($userId !== null) {
            // Authenticated user
            $removed = $this->cartRepository->removeItem($userId, $cartId);
        } else {
            // Guest user
            $removed = $this->removeGuestCartItem($cartId);
        }

        if (!$removed) {
            return ['success' => false, 'error' => 'Failed to remove item'];
        }

        return [
            'success' => true,
            'message' => 'Item removed from cart',
            'item_count' => $this->getCartItemCount(),
        ];
    }

    /**
     * Update item quantity in cart
     */
    public function updateQuantity(): array
    {
        // Validate CSRF token
        if (!CsrfToken::validateFromRequest()) {
            return ['success' => false, 'error' => 'Invalid security token'];
        }

        $cartId = Sanitizer::integer($_POST['cart_id'] ?? null);
        $quantity = Sanitizer::integer($_POST['quantity'] ?? null);

        if ($cartId === null || $cartId <= 0) {
            return ['success' => false, 'error' => 'Invalid cart item ID'];
        }

        if ($quantity === null || $quantity <= 0) {
            return ['success' => false, 'error' => 'Invalid quantity'];
        }

        // Limit quantity
        $quantity = min($quantity, 999);

        $userId = $this->getUserId();

        if ($userId !== null) {
            // Authenticated user
            $updated = $this->cartRepository->updateQuantity($cartId, $quantity);
        } else {
            // Guest user
            $updated = $this->updateGuestCartItem($cartId, $quantity);
        }

        if (!$updated) {
            return ['success' => false, 'error' => 'Failed to update item'];
        }

        // Recalculate total
        $total = $this->getCartTotal();

        return [
            'success' => true,
            'message' => 'Quantity updated',
            'quantity' => $quantity,
            'total' => round($total, 2),
            'item_count' => $this->getCartItemCount(),
        ];
    }

    /**
     * Get mini cart data (for header display)
     */
    public function getMiniCart(): array
    {
        $userId = $this->getUserId();
        $itemCount = 0;
        $total = 0.0;
        $items = [];

        if ($userId !== null) {
            // Authenticated user
            $itemCount = $this->cartRepository->getItemCount($userId);
            $total = $this->cartRepository->getTotal($userId);
            $cartItems = $this->cartRepository->getCart($userId);
            $items = array_slice($cartItems, 0, 3); // Show last 3 items
        } else {
            // Guest user
            $cartData = $this->loadGuestCart();
            $itemCount = $cartData['count'];
            $total = $cartData['total'];
            $items = array_slice($cartData['items'], 0, 3);
        }

        return [
            'item_count' => $itemCount,
            'total' => round($total, 2),
            'preview_items' => $items,
        ];
    }

    /**
     * Add item to guest cart (session-based)
     */
    private function addGuestCartItem(int $productId, int $quantity): void
    {
        $cartData = $this->loadGuestCart();
        $items = $cartData['items'];

        // Check if product already in cart
        $found = false;
        foreach ($items as &$item) {
            if ($item['product_id'] === $productId) {
                $item['quantity'] += $quantity;
                $found = true;
                break;
            }
        }

        if (!$found) {
            $product = $this->cartRepository->validateProduct($productId);
            if ($product) {
                $items[] = [
                    'product_id' => $productId,
                    'quantity' => $quantity,
                    'title' => $product['title'],
                    'price' => $product['price'],
                ];
            }
        }

        $this->saveGuestCart($items);
    }

    /**
     * Remove item from guest cart
     */
    private function removeGuestCartItem(int $productId): bool
    {
        $cartData = $this->loadGuestCart();
        $items = $cartData['items'];

        $originalCount = count($items);
        $items = array_filter($items, function ($item) use ($productId) {
            return $item['product_id'] !== $productId;
        });

        if (count($items) !== $originalCount) {
            $this->saveGuestCart(array_values($items));
            return true;
        }

        return false;
    }

    /**
     * Update item quantity in guest cart
     */
    private function updateGuestCartItem(int $productId, int $quantity): bool
    {
        $cartData = $this->loadGuestCart();
        $items = $cartData['items'];

        foreach ($items as &$item) {
            if ($item['product_id'] === $productId) {
                if ($quantity <= 0) {
                    // Remove item if quantity is 0
                    $items = array_filter($items, function ($i) use ($productId) {
                        return $i['product_id'] !== $productId;
                    });
                } else {
                    $item['quantity'] = min($quantity, 999);
                }
                $this->saveGuestCart(array_values($items));
                return true;
            }
        }

        return false;
    }

    /**
     * Load guest cart from database
     */
    private function loadGuestCart(): array
    {
        $sessionId = $this->getSessionId();
        $cartData = $this->cartRepository->getGuestCart($sessionId);

        if (!$cartData) {
            return ['items' => [], 'total' => 0.0, 'count' => 0];
        }

        // Calculate total
        $total = 0.0;
        foreach ($cartData as $item) {
            $total += $item['price'] * $item['quantity'];
        }

        return [
            'items' => $cartData,
            'total' => $total,
            'count' => count($cartData),
        ];
    }

    /**
     * Save guest cart to database
     */
    private function saveGuestCart(array $items): void
    {
        $sessionId = $this->getSessionId();
        $this->cartRepository->saveGuestCart($sessionId, $items);
    }

    /**
     * Get total number of items in cart
     */
    private function getCartItemCount(): int
    {
        $userId = $this->getUserId();

        if ($userId !== null) {
            return $this->cartRepository->getItemCount($userId);
        }

        $cartData = $this->loadGuestCart();
        return $cartData['count'];
    }

    /**
     * Get cart total
     */
    private function getCartTotal(): float
    {
        $userId = $this->getUserId();

        if ($userId !== null) {
            return $this->cartRepository->getTotal($userId);
        }

        $cartData = $this->loadGuestCart();
        return $cartData['total'];
    }
}
