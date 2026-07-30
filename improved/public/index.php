<?php
/**
 * Art Gallery - Painting Sales Website
 *
 * Modern refactored version with security, proper architecture, and best practices.
 * This is the main entry point for the application.
 */

require_once dirname(dirname(__FILE__)) . '/bootstrap.php';

use App\Database\Database;
use App\Repository\ProductRepository;
use App\Repository\NewsRepository;
use App\Repository\SubscriptionRepository;
use App\Security\Sanitizer;
use App\Security\CsrfToken;
use App\File\FileUploader;
use App\Mail\Mailer;
use App\Controllers\PaymentController;
use App\Controllers\CartController;

// Initialize services
$db = $GLOBALS['db'];
$config = $GLOBALS['config'];

$productRepo = new ProductRepository($db);
$newsRepo = new NewsRepository($db);
$subscriptionRepo = new SubscriptionRepository($db);
$cartController = new CartController($db);

// Get request parameters safely
$action = Sanitizer::string($_GET['action'] ?? 'home');
$category = Sanitizer::string($_GET['category'] ?? '');
$page = Sanitizer::integer($_GET['page'] ?? 1) ?? 1;
$searchTerm = Sanitizer::string($_GET['search'] ?? '');

// Ensure minimum page value

// Handle Stripe webhook endpoint
if ($_SERVER['REQUEST_METHOD'] === 'POST' && strpos($_SERVER['REQUEST_URI'] ?? '', '/webhook/stripe') !== false) {
    handleStripeWebhook($GLOBALS['db']);
    exit;
}
$page = max(1, $page);

// Initialize response data
$data = [
    'app_name' => $config['app']['name'],
    'app_url' => $config['app']['url'],
    'csrf_token' => CsrfToken::generate(),
    'page' => $page,
    'per_page' => $config['pagination']['pictures_per_page'],
];

// Handle AJAX cart operations
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['cart_action'])) {
    handleCartAjaxAction($cartController, $_POST['cart_action']);
}

// Route handling
try {
    switch ($action) {
        case 'cart':
            $data = array_merge($data, $cartController->viewCart());
            break;

        case 'products':
            handleProductsAction($productRepo, $data, $category, $searchTerm, $page, $config);
            break;

        case 'product':
            handleProductDetailAction($productRepo, $data);
            break;

        case 'news':
            handleNewsAction($newsRepo, $data, $page, $config);
            break;

        case 'search':
            handleSearchAction($productRepo, $data, $searchTerm, $page, $config);
            break;

        case 'subscribe':
            handleSubscribeAction($subscriptionRepo, $data, $config);
            break;

        case 'contact':
            handleContactAction($data, $config);
            break;

        case 'home':
        default:
            handleHomeAction($productRepo, $newsRepo, $data, $config);
            break;
    }
} catch (Exception $e) {
    if ($config['app']['debug']) {
        error_log($e->getMessage());
    }
    $data['error'] = 'An error occurred while processing your request';
}

// Get cart data for header display
$cartMiniData = $cartController->getMiniCart();
$data['cart_item_count'] = $cartMiniData['item_count'];
$data['cart_total'] = $cartMiniData['total'];
$data['cart_preview_items'] = $cartMiniData['preview_items'];

// Render response
renderPage($action, $data);

/**
 * Handle home page action
 */
function handleHomeAction(
    ProductRepository $productRepo,
    NewsRepository $newsRepo,
    array &$data,
    array $config
): void {
    $data['featured_products'] = $productRepo->getFeatured(6);
    $data['latest_news'] = $newsRepo->paginate(1, 5, ['created_at' => 'desc']);
    $data['total_products'] = $productRepo->count();
}

/**
 * Handle products listing action
 */
function handleProductsAction(
    ProductRepository $productRepo,
    array &$data,
    string $category,
    string $searchTerm,
    int $page,
    array $config
): void {
    if (!empty($searchTerm)) {
        $data['products'] = $productRepo->searchProducts($searchTerm, $page, $config['pagination']['pictures_per_page']);
        $data['title'] = "Search Results: " . htmlspecialchars($searchTerm);
    } elseif (!empty($category)) {
        $data['category'] = $category;
        $data['products'] = $productRepo->getByCategory($category, $page, $config['pagination']['pictures_per_page']);
        $data['title'] = "Category: " . htmlspecialchars($category);
    } else {
        $data['products'] = $productRepo->paginate($page, $config['pagination']['pictures_per_page']);
        $data['title'] = 'All Products';
    }
}

/**
 * Handle product detail action
 */
function handleProductDetailAction(ProductRepository $productRepo, array &$data): void
{
    $productId = Sanitizer::integer($_GET['id'] ?? null);

    if ($productId === null) {
        $data['error'] = 'Invalid product ID';
        return;
    }

    $product = $productRepo->find($productId);

    if (!$product) {
        http_response_code(404);
        $data['error'] = 'Product not found';
        return;
    }

    $data['product'] = $product;
    $data['title'] = htmlspecialchars($product['title'] ?? 'Product');
}

/**
 * Handle news listing action
 */
function handleNewsAction(NewsRepository $newsRepo, array &$data, int $page, array $config): void
{
    $data['news'] = $newsRepo->getPublished($page, $config['pagination']['news_per_page']);
    $data['title'] = 'Latest News';
}

/**
 * Handle search action
 */
function handleSearchAction(
    ProductRepository $productRepo,
    array &$data,
    string $searchTerm,
    int $page,
    array $config
): void {
    if (strlen($searchTerm) < 3) {
        $data['error'] = 'Search term must be at least 3 characters';
        return;
    }

    $data['search_term'] = htmlspecialchars($searchTerm);
    $data['results'] = $productRepo->searchProducts($searchTerm, $page, $config['pagination']['pictures_per_page']);
    $data['title'] = "Search: " . htmlspecialchars($searchTerm);
}

/**
 * Handle subscription action
 */
function handleSubscribeAction(SubscriptionRepository $subscriptionRepo, array &$data, array $config): void
{
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        // Validate CSRF token if configured
        if ($config['security']['csrf_enabled'] && !CsrfToken::validateFromRequest()) {
            $data['error'] = 'Invalid security token';
            return;
        }

        $email = Sanitizer::email($_POST['email'] ?? '');

        if (!$email) {
            $data['error'] = 'Invalid email address';
            return;
        }

        if ($subscriptionRepo->subscribe($email)) {
            $data['success'] = 'Successfully subscribed to our newsletter!';
        } else {
            $data['error'] = 'Failed to subscribe. Please try again.';
        }
    }
}

/**
 * Handle contact form submission
 */
function handleContactAction(array &$data, array $config): void
{
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        // Validate CSRF token if configured
        if ($config['security']['csrf_enabled'] && !CsrfToken::validateFromRequest()) {
            $data['error'] = 'Invalid security token';
            return;
        }

        // Sanitize form data
        $name = Sanitizer::string($_POST['name'] ?? '');
        $email = Sanitizer::email($_POST['email'] ?? '');
        $phone = Sanitizer::phone($_POST['phone'] ?? '');
        $message = Sanitizer::string($_POST['message'] ?? '');

        // Validate required fields
        if (empty($name) || !$email || empty($message)) {
            $data['error'] = 'Please fill in all required fields';
            return;
        }

        // Send email
        try {
            $mailer = new Mailer($config['email']['from_address'], $config['email']['from_name']);
            $mailer
                ->to($config['email']['admin_email'], 'Admin')
                ->subject('New Contact Form Submission')
                ->body(sprintf(
                    "Name: %s\nEmail: %s\nPhone: %s\n\nMessage:\n%s",
                    $name,
                    $email,
                    $phone ?: 'Not provided',
                    $message
                ))
                ->send();

            $data['success'] = 'Your message has been sent successfully!';
        } catch (Exception $e) {
            $data['error'] = 'Failed to send message. Please try again later.';
        }
    }
}

/**
 * Render page with template
 */
function renderPage(string $action, array $data): void
{
    // HTML Escape all output by default
    $data = array_map(function ($item) {
        return is_string($item) ? htmlspecialchars($item, ENT_QUOTES, 'UTF-8') : $item;
    }, $data);

    // Simple template rendering (replace with Twig or other template engine in production)
    ?>
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title><?php echo $data['title'] ?? $data['app_name']; ?></title>
        <meta name="description" content="Online art gallery and painting sales platform">
        <link rel="stylesheet" href="/css/styles.css">
    </head>
    <body>
        <header class="header">
            <div class="container">
                <h1><a href="?action=home"><?php echo $data['app_name']; ?></a></h1>
                <nav class="nav">
                    <a href="?action=home">Home</a>
                    <a href="?action=products">Products</a>
                    <a href="?action=news">News</a>
                    <a href="?action=contact">Contact</a>
                    <a href="?action=cart" class="cart-link">
                        Cart
                        <?php if ($data['cart_item_count'] > 0): ?>
                            <span class="cart-badge"><?php echo $data['cart_item_count']; ?></span>
                        <?php endif; ?>
                    </a>
                </nav>
                <?php if ($data['cart_item_count'] > 0): ?>
                    <div class="mini-cart">
                        <div class="mini-cart-header">Cart Preview</div>
                        <div class="mini-cart-items">
                            <?php foreach ($data['cart_preview_items'] as $item): ?>
                                <div class="mini-cart-item">
                                    <strong><?php echo htmlspecialchars($item['title'] ?? ''); ?></strong>
                                    <span class="quantity">x<?php echo $item['quantity']; ?></span>
                                </div>
                            <?php endforeach; ?>
                        </div>
                        <div class="mini-cart-footer">
                            <div class="mini-cart-total">Total: $<?php echo number_format($data['cart_total'], 2); ?></div>
                            <a href="?action=cart" class="btn btn-small">View Cart</a>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </header>

        <main class="container">
            <?php if (isset($data['error'])): ?>
                <div class="alert alert-error"><?php echo $data['error']; ?></div>
            <?php endif; ?>

            <?php if (isset($data['success'])): ?>
                <div class="alert alert-success"><?php echo $data['success']; ?></div>
            <?php endif; ?>

            <?php
            switch ($action) {
                case 'home':
                    renderHome($data);
                    break;
                case 'products':
                    renderProducts($data);
                    break;
                case 'product':
                    renderProductDetail($data);
                    break;
                case 'cart':
                    renderCart($data);
                    break;
                case 'news':
                    renderNews($data);
                    break;
                case 'search':
                    renderSearch($data);
                    break;
                case 'contact':
                    renderContact($data);
                    break;
            }
            ?>
        </main>

        <footer class="footer">
            <div class="container">
                <p>&copy; <?php echo date('Y'); ?> <?php echo $data['app_name']; ?>. All rights reserved.</p>
            </div>
        </footer>
    </body>
    </html>
    <?php
}

/**
 * Render home page
 */
function renderHome(array $data): void
{
    ?>
    <section class="hero">
        <h2>Welcome to <?php echo $data['app_name']; ?></h2>
        <p>Discover unique artworks from talented artists around the world</p>
    </section>

    <?php if (!empty($data['featured_products'])): ?>
        <section class="products">
            <h3>Featured Products</h3>
            <div class="product-grid">
                <?php foreach ($data['featured_products'] as $product): ?>
                    <article class="product-card">
                        <?php if (!empty($product['image_url'])): ?>
                            <img src="<?php echo htmlspecialchars($product['image_url']); ?>" alt="<?php echo htmlspecialchars($product['title']); ?>">
                        <?php endif; ?>
                        <h4><?php echo htmlspecialchars($product['title']); ?></h4>
                        <?php if (!empty($product['price'])): ?>
                            <p class="price"><?php echo htmlspecialchars($product['price']); ?> <?php echo htmlspecialchars($product['currency'] ?? 'USD'); ?></p>
                        <?php endif; ?>
                        <a href="?action=product&id=<?php echo $product['id']; ?>" class="btn">View Details</a>
                    </article>
                <?php endforeach; ?>
            </div>
        </section>
    <?php endif; ?>
    <?php
}

/**
 * Render products list
 */
function renderProducts(array $data): void
{
    echo '<h2>' . $data['title'] . '</h2>';

    if (!empty($data['products'])):
        ?>
        <div class="product-grid">
            <?php foreach ($data['products'] as $product): ?>
                <article class="product-card">
                    <?php if (!empty($product['image_url'])): ?>
                        <img src="<?php echo htmlspecialchars($product['image_url']); ?>" alt="<?php echo htmlspecialchars($product['title']); ?>">
                    <?php endif; ?>
                    <h3><?php echo htmlspecialchars($product['title']); ?></h3>
                    <p><?php echo htmlspecialchars(substr($product['description'] ?? '', 0, 100)); ?>...</p>
                    <a href="?action=product&id=<?php echo $product['id']; ?>" class="btn">View Details</a>
                </article>
            <?php endforeach; ?>
        </div>
        <?php
    else:
        ?>
        <p>No products found.</p>
        <?php
    endif;
}

/**
 * Render product detail
 */
function renderProductDetail(array $data): void
{
    if (!isset($data['product'])):
        echo '<p>Product not found.</p>';
        return;
    endif;

    $product = $data['product'];
    ?>
    <article class="product-detail">
        <?php if (!empty($product['image_url'])): ?>
            <img src="<?php echo htmlspecialchars($product['image_url']); ?>" alt="<?php echo htmlspecialchars($product['title']); ?>">
        <?php endif; ?>
        <h2><?php echo htmlspecialchars($product['title']); ?></h2>
        <?php if (!empty($product['price'])): ?>
            <p class="price"><?php echo htmlspecialchars($product['price']); ?> <?php echo htmlspecialchars($product['currency'] ?? 'USD'); ?></p>
        <?php endif; ?>
        <div class="description">
            <?php echo nl2br(htmlspecialchars($product['description'])); ?>
        </div>
        <?php if (!empty($product['artist'])): ?>
            <p><strong>Artist:</strong> <?php echo htmlspecialchars($product['artist']); ?></p>
        <?php endif; ?>

        <form method="POST" class="add-to-cart-form">
            <input type="hidden" name="cart_action" value="add">
            <input type="hidden" name="product_id" value="<?php echo $product['id']; ?>">
            <input type="hidden" name="_token" value="<?php echo $data['csrf_token']; ?>">

            <div class="form-group">
                <label for="quantity">Quantity:</label>
                <input type="number" id="quantity" name="quantity" min="1" max="999" value="1" required>
            </div>

            <button type="submit" class="btn btn-primary">Add to Cart</button>
        </form>

        <a href="?action=products" class="btn">Back to Products</a>
    </article>
    <?php
}

/**
 * Render news
 */
function renderNews(array $data): void
{
    echo '<h2>' . $data['title'] . '</h2>';

    if (!empty($data['news'])):
        foreach ($data['news'] as $item):
            ?>
            <article class="news-item">
                <h3><?php echo htmlspecialchars($item['title']); ?></h3>
                <p class="meta">Posted on <?php echo htmlspecialchars($item['created_at']); ?></p>
                <p><?php echo nl2br(htmlspecialchars($item['content'])); ?></p>
            </article>
            <?php
        endforeach;
    else:
        ?>
        <p>No news available.</p>
        <?php
    endif;
}

/**
 * Render search results
 */
function renderSearch(array $data): void
{
    echo '<h2>Search Results</h2>';
    echo '<p>Search term: <strong>' . $data['search_term'] . '</strong></p>';

    if (!empty($data['results'])):
        ?>
        <div class="product-grid">
            <?php foreach ($data['results'] as $result): ?>
                <article class="product-card">
                    <h3><?php echo htmlspecialchars($result['title']); ?></h3>
                    <a href="?action=product&id=<?php echo $result['id']; ?>" class="btn">View</a>
                </article>
            <?php endforeach; ?>
        </div>
        <?php
    else:
        ?>
        <p>No results found for your search.</p>
        <?php
    endif;
}

/**
 * Render contact form
 */
function renderContact(array $data): void
{
    ?>
    <h2>Contact Us</h2>
    <form method="POST" class="contact-form">
        <input type="hidden" name="_token" value="<?php echo $data['csrf_token']; ?>">

        <div class="form-group">
            <label for="name">Name (required)</label>
            <input type="text" id="name" name="name" required>
        </div>

        <div class="form-group">
            <label for="email">Email (required)</label>
            <input type="email" id="email" name="email" required>
        </div>

        <div class="form-group">
            <label for="phone">Phone</label>
            <input type="tel" id="phone" name="phone">
        </div>

        <div class="form-group">
            <label for="message">Message (required)</label>
            <textarea id="message" name="message" rows="5" required></textarea>
        </div>

        <button type="submit" class="btn">Send Message</button>
    </form>
    <?php
}

/**
 * Render shopping cart page
 */
function renderCart(array $data): void
{
    $items = $data['cart_items'] ?? [];
    $total = $data['total'] ?? 0;
    ?>
    <h2>Shopping Cart</h2>

    <?php if (empty($items)): ?>
        <p>Your cart is empty. <a href="?action=products">Continue shopping</a></p>
    <?php else: ?>
        <div class="cart-container">
            <table class="cart-table">
                <thead>
                    <tr>
                        <th>Product</th>
                        <th>Price</th>
                        <th>Quantity</th>
                        <th>Subtotal</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($items as $item): ?>
                        <tr class="cart-row">
                            <td class="product-name">
                                <?php if (!empty($item['image_url'])): ?>
                                    <img src="<?php echo htmlspecialchars($item['image_url']); ?>" alt="<?php echo htmlspecialchars($item['title']); ?>" class="cart-thumbnail">
                                <?php endif; ?>
                                <?php echo htmlspecialchars($item['title'] ?? ''); ?>
                            </td>
                            <td class="price">$<?php echo number_format($item['price'] ?? 0, 2); ?></td>
                            <td class="quantity">
                                <form method="POST" class="quantity-form" style="display: inline;">
                                    <input type="hidden" name="cart_action" value="update">
                                    <input type="hidden" name="cart_id" value="<?php echo $item['id']; ?>">
                                    <input type="hidden" name="_token" value="<?php echo $data['csrf_token']; ?>">
                                    <input type="number" name="quantity" min="1" max="999" value="<?php echo $item['quantity']; ?>" class="qty-input" onchange="this.form.submit()">
                                </form>
                            </td>
                            <td class="subtotal">$<?php echo number_format(($item['price'] ?? 0) * $item['quantity'], 2); ?></td>
                            <td class="actions">
                                <form method="POST" style="display: inline;">
                                    <input type="hidden" name="cart_action" value="remove">
                                    <input type="hidden" name="cart_id" value="<?php echo $item['id']; ?>">
                                    <input type="hidden" name="_token" value="<?php echo $data['csrf_token']; ?>">
                                    <button type="submit" class="btn btn-danger">Remove</button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>

            <div class="cart-summary">
                <h3>Cart Summary</h3>
                <p><strong>Total:</strong> $<?php echo number_format($total, 2); ?></p>
                <a href="?action=products" class="btn">Continue Shopping</a>
                <a href="#checkout" class="btn btn-primary">Proceed to Checkout</a>
            </div>
        </div>
    <?php endif; ?>
    <?php
}

/**
 * Handle cart AJAX actions
 */
function handleCartAjaxAction(CartController $cartController, string $action): void
{
    $response = [];

    try {
        switch ($action) {
            case 'add':
                $response = $cartController->addToCart();
                break;
            case 'remove':
                $response = $cartController->removeFromCart();
                break;
            case 'update':
                $response = $cartController->updateQuantity();
                break;
            default:
                $response = ['success' => false, 'error' => 'Invalid action'];
        }
    } catch (Exception $e) {
        $response = ['success' => false, 'error' => $e->getMessage()];
    }

    // If JSON requested, return JSON response
    if (isset($_POST['json']) || strpos($_SERVER['HTTP_ACCEPT'] ?? '', 'application/json') !== false) {
        header('Content-Type: application/json');
        echo json_encode($response);
        exit;
    }

    // Otherwise, redirect back to cart
    if ($response['success'] ?? false) {
        header('Location: ?action=cart');
    } else {
        $_SESSION['error'] = $response['error'] ?? 'An error occurred';
        header('Location: ?action=cart');
    }
    exit;
}

/**
 * Handle Stripe webhook events
 */
function handleStripeWebhook(Database $db): void
{
    // Get raw payload and signature
    $payload = file_get_contents('php://input');
    $signature = $_SERVER['HTTP_STRIPE_SIGNATURE'] ?? '';

    if (empty($payload) || empty($signature)) {
        http_response_code(400);
        echo json_encode(['error' => 'Missing payload or signature']);
        return;
    }

    try {
        $paymentController = new PaymentController($db);
        $result = $paymentController->handleWebhook($payload, $signature);

        http_response_code(200);
        echo json_encode($result);
    } catch (\Exception $e) {
        error_log("Webhook error: " . $e->getMessage());
        http_response_code(400);
        echo json_encode(['error' => $e->getMessage()]);
    }
}
