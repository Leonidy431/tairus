<?php
/**
 * Checkout Controller
 *
 * Handles multi-step checkout flow for cart to order conversion.
 * Manages checkout steps: review cart, address, shipping, payment, confirmation.
 */

namespace App\Controllers;

use App\Database\Database;
use App\Repository\OrderRepository;
use App\Repository\PaymentRepository;
use App\Security\Sanitizer;
use App\Security\CsrfToken;

class CheckoutController
{
    private Database $db;
    private OrderRepository $orderRepo;
    private PaymentRepository $paymentRepo;

    public function __construct(Database $db)
    {
        $this->db = $db;
        $this->orderRepo = new OrderRepository($db);
        $this->paymentRepo = new PaymentRepository($db);
    }

    /**
     * Get cart items for the current user
     */
    public function getCartItems(int $userId): array
    {
        return $this->db->select(
            "SELECT ci.*, p.id as product_id, p.name, p.price, p.description
             FROM cart_items ci
             JOIN products p ON ci.product_id = p.id
             WHERE ci.user_id = ?
             ORDER BY ci.added_at DESC",
            [$userId]
        );
    }

    /**
     * Validate cart - check if cart is not empty and all items are in stock
     */
    public function validateCart(array $cartItems): array
    {
        $errors = [];

        if (empty($cartItems)) {
            $errors[] = 'Cart is empty';
            return ['valid' => false, 'errors' => $errors];
        }

        // Validate each item exists and is available
        foreach ($cartItems as $item) {
            $product = $this->db->selectOne(
                "SELECT id, name, price FROM products WHERE id = ? AND is_public = 1",
                [$item['product_id']]
            );

            if (!$product) {
                $errors[] = "Product #{$item['product_id']} is no longer available";
            }

            if ($item['quantity'] <= 0) {
                $errors[] = "Invalid quantity for {$item['name']}";
            }
        }

        return [
            'valid' => empty($errors),
            'errors' => $errors,
        ];
    }

    /**
     * Calculate cart totals
     */
    public function calculateCartTotals(array $cartItems, array $address = [], float $discountAmount = 0): array
    {
        $subtotal = 0;

        foreach ($cartItems as $item) {
            $subtotal += (float)$item['price'] * (int)$item['quantity'];
        }

        $shippingCost = OrderRepository::calculateShippingCost($address);
        $tax = OrderRepository::calculateTax($address, $subtotal);
        $total = $subtotal + $shippingCost + $tax - $discountAmount;

        return [
            'subtotal' => round($subtotal, 2),
            'shipping_cost' => round($shippingCost, 2),
            'tax' => round($tax, 2),
            'discount' => round($discountAmount, 2),
            'total' => round($total, 2),
        ];
    }

    /**
     * Validate and sanitize billing/shipping address
     */
    public function validateAddress(array $data): array
    {
        $errors = [];
        $address = [];

        $requiredFields = ['first_name', 'last_name', 'address', 'city', 'postal_code', 'country'];

        foreach ($requiredFields as $field) {
            if (empty($data[$field])) {
                $errors[] = ucfirst(str_replace('_', ' ', $field)) . " is required";
            }
        }

        if (empty($errors)) {
            $address = [
                'first_name' => Sanitizer::string($data['first_name']),
                'last_name' => Sanitizer::string($data['last_name']),
                'address' => Sanitizer::string($data['address']),
                'apartment' => Sanitizer::string($data['apartment'] ?? ''),
                'city' => Sanitizer::string($data['city']),
                'state' => Sanitizer::string($data['state'] ?? ''),
                'postal_code' => Sanitizer::string($data['postal_code']),
                'country' => Sanitizer::string($data['country']),
                'phone' => Sanitizer::phone($data['phone'] ?? ''),
            ];
        }

        return [
            'valid' => empty($errors),
            'errors' => $errors,
            'address' => $address,
        ];
    }

    /**
     * Create order from cart
     */
    public function createOrder(int $userId, array $cartItems, array $billingAddress, array $shippingAddress, float $discountAmount = 0, ?string $notes = null): array
    {
        try {
            // Validate cart
            $cartValidation = $this->validateCart($cartItems);
            if (!$cartValidation['valid']) {
                return [
                    'success' => false,
                    'errors' => $cartValidation['errors'],
                ];
            }

            // Calculate totals
            $totals = $this->calculateCartTotals($cartItems, $shippingAddress, $discountAmount);

            // Prepare order items
            $orderItems = [];
            foreach ($cartItems as $item) {
                $orderItems[] = [
                    'product_id' => (int)$item['product_id'],
                    'quantity' => (int)$item['quantity'],
                    'price' => (float)$item['price'],
                    'subtotal' => round((float)$item['price'] * (int)$item['quantity'], 2),
                ];
            }

            // Create order
            $orderNumber = $this->orderRepo->generateOrderNumber();
            $orderData = [
                'user_id' => $userId,
                'order_number' => $orderNumber,
                'total' => $totals['total'],
                'subtotal' => $totals['subtotal'],
                'shipping_cost' => $totals['shipping_cost'],
                'tax' => $totals['tax'],
                'discount' => $totals['discount'],
                'status' => 'pending',
                'billing_address' => json_encode($billingAddress),
                'shipping_address' => json_encode($shippingAddress),
                'notes' => $notes,
            ];

            $orderId = $this->orderRepo->createOrder($orderData, $orderItems);

            return [
                'success' => true,
                'order_id' => $orderId,
                'order_number' => $orderNumber,
                'total' => $totals['total'],
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => 'Failed to create order: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Clear cart for user
     */
    public function clearCart(int $userId): bool
    {
        return $this->db->delete('cart_items', ['user_id' => $userId]);
    }

    /**
     * Add item to cart
     */
    public function addToCart(int $userId, int $productId, int $quantity = 1): array
    {
        try {
            // Validate product exists
            $product = $this->db->selectOne(
                "SELECT id, price, name FROM products WHERE id = ? AND is_public = 1",
                [$productId]
            );

            if (!$product) {
                return [
                    'success' => false,
                    'error' => 'Product not found',
                ];
            }

            if ($quantity <= 0) {
                return [
                    'success' => false,
                    'error' => 'Invalid quantity',
                ];
            }

            // Check if product already in cart
            $existingItem = $this->db->selectOne(
                "SELECT id, quantity FROM cart_items WHERE user_id = ? AND product_id = ?",
                [$userId, $productId]
            );

            if ($existingItem) {
                // Update quantity
                $newQuantity = $existingItem['quantity'] + $quantity;
                $this->db->update('cart_items', ['quantity' => $newQuantity], ['id' => $existingItem['id']]);
            } else {
                // Insert new item
                $this->db->insert('cart_items', [
                    'user_id' => $userId,
                    'product_id' => $productId,
                    'quantity' => $quantity,
                    'added_at' => date('Y-m-d H:i:s'),
                ]);
            }

            return [
                'success' => true,
                'message' => 'Item added to cart',
                'product_name' => $product['name'],
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => 'Failed to add to cart: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Remove item from cart
     */
    public function removeFromCart(int $userId, int $cartItemId): array
    {
        try {
            $deleted = $this->db->delete('cart_items', ['id' => $cartItemId, 'user_id' => $userId]);

            if (!$deleted) {
                return [
                    'success' => false,
                    'error' => 'Cart item not found',
                ];
            }

            return [
                'success' => true,
                'message' => 'Item removed from cart',
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => 'Failed to remove from cart: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Update cart item quantity
     */
    public function updateCartItemQuantity(int $userId, int $cartItemId, int $quantity): array
    {
        try {
            if ($quantity <= 0) {
                return $this->removeFromCart($userId, $cartItemId);
            }

            // Verify item belongs to user
            $item = $this->db->selectOne(
                "SELECT id FROM cart_items WHERE id = ? AND user_id = ?",
                [$cartItemId, $userId]
            );

            if (!$item) {
                return [
                    'success' => false,
                    'error' => 'Cart item not found',
                ];
            }

            $this->db->update('cart_items', ['quantity' => $quantity], ['id' => $cartItemId]);

            return [
                'success' => true,
                'message' => 'Cart item updated',
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => 'Failed to update cart: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Process checkout with payment
     */
    public function processCheckout(int $userId, array $checkoutData, PaymentController $paymentController): array
    {
        try {
            // Get cart items
            $cartItems = $this->getCartItems($userId);

            // Validate cart
            $cartValidation = $this->validateCart($cartItems);
            if (!$cartValidation['valid']) {
                return [
                    'success' => false,
                    'step' => 'cart',
                    'errors' => $cartValidation['errors'],
                ];
            }

            // Validate addresses
            $billingValidation = $this->validateAddress($checkoutData['billing_address'] ?? []);
            if (!$billingValidation['valid']) {
                return [
                    'success' => false,
                    'step' => 'address',
                    'errors' => $billingValidation['errors'],
                ];
            }

            $shippingAddress = $checkoutData['use_billing_for_shipping'] ?? false
                ? $billingValidation['address']
                : $checkoutData['shipping_address'] ?? [];

            $shippingValidation = $this->validateAddress($shippingAddress);
            if (!$shippingValidation['valid']) {
                return [
                    'success' => false,
                    'step' => 'address',
                    'errors' => $shippingValidation['errors'],
                ];
            }

            // Create order
            $orderResult = $this->createOrder(
                $userId,
                $cartItems,
                $billingValidation['address'],
                $shippingValidation['address'],
                (float)($checkoutData['discount_amount'] ?? 0),
                $checkoutData['notes'] ?? null
            );

            if (!$orderResult['success']) {
                return [
                    'success' => false,
                    'step' => 'order',
                    'error' => $orderResult['error'] ?? 'Failed to create order',
                ];
            }

            // Create payment intent
            $paymentResult = $paymentController->createPaymentIntent(
                $userId,
                $orderResult['total'],
                'USD',
                $orderResult['order_id']
            );

            if (!$paymentResult['success']) {
                return [
                    'success' => false,
                    'step' => 'payment',
                    'error' => $paymentResult['error'] ?? 'Payment processing failed',
                ];
            }

            // Clear cart
            $this->clearCart($userId);

            return [
                'success' => true,
                'order_id' => $orderResult['order_id'],
                'order_number' => $orderResult['order_number'],
                'payment_intent_id' => $paymentResult['payment_intent_id'],
                'client_secret' => $paymentResult['client_secret'],
                'amount' => $orderResult['total'],
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => 'Checkout failed: ' . $e->getMessage(),
            ];
        }
    }
}
