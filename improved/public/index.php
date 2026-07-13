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

// Initialize services
$db = $GLOBALS['db'];
$config = $GLOBALS['config'];

$productRepo = new ProductRepository($db);
$newsRepo = new NewsRepository($db);
$subscriptionRepo = new SubscriptionRepository($db);

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

// Route handling
try {
    switch ($action) {
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
                </nav>
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
