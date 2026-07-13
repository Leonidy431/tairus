# Art Gallery - Painting Sales Website (Refactored)

A modern, secure, and maintainable web application for selling and showcasing artwork. This is a complete refactoring of the original 2016 painting sales website with modern PHP practices, security improvements, and clean architecture.

## Features

- 🎨 **Product Catalog** - Browse and search paintings and artworks
- 📰 **News Section** - Latest updates and announcements
- 💌 **Newsletter** - Subscribe to updates via email
- 📧 **Contact Form** - Secure form for inquiries
- 📱 **Responsive Design** - Works on desktop and mobile devices
- 🔒 **Security First** - CSRF protection, SQL injection prevention, XSS protection
- 🏗️ **Modern Architecture** - Repository pattern, dependency injection, separation of concerns
- 🗄️ **Database** - Clean schema with proper relationships and indexes

## Quick Start

### Requirements

- PHP 7.4 or higher
- MySQL 5.7 or higher
- Composer (optional, for autoloading setup)
- Web server (Apache, Nginx, etc.)

### Installation

1. **Clone the repository**
   ```bash
   git clone <repository-url>
   cd improved
   ```

2. **Configure environment**
   ```bash
   cp .env.example .env
   # Edit .env with your database credentials
   ```

3. **Create database**
   ```bash
   mysql -u root -p < database/schema.sql
   ```

4. **Set file permissions**
   ```bash
   chmod -R 755 storage/
   chmod -R 755 public/
   ```

5. **Set up web server**

   **Apache:**
   ```apache
   <Directory /path/to/improved/public>
       AllowOverride All
       Require all granted
   </Directory>
   ```

   **Nginx:**
   ```nginx
   location / {
       try_files $uri $uri/ /index.php?$query_string;
   }
   ```

6. **Access the application**
   ```
   http://localhost/improved/public/
   ```

## Project Structure

```
improved/
├── config/
│   └── config.php                 # Main configuration file
├── database/
│   └── schema.sql                 # Database schema
├── public/
│   ├── index.php                  # Main entry point
│   └── css/
│       └── styles.css             # Styling
├── src/
│   ├── Database/                  # Database abstraction
│   │   ├── Database.php           # PDO wrapper
│   │   └── DatabaseException.php  # Exception class
│   ├── Repository/                # Data access layer
│   │   ├── Repository.php         # Base repository
│   │   ├── ProductRepository.php  # Products
│   │   ├── NewsRepository.php     # News
│   │   └── SubscriptionRepository.php # Subscriptions
│   ├── Security/                  # Security utilities
│   │   ├── Sanitizer.php         # Input validation
│   │   └── CsrfToken.php         # CSRF protection
│   ├── Mail/                      # Email service
│   │   └── Mailer.php            # Email sender
│   ├── File/                      # File handling
│   │   └── FileUploader.php      # Secure uploads
│   └── Helpers/
│       └── env.php                # Environment helpers
├── storage/
│   ├── logs/                      # Application logs
│   └── uploads/                   # User uploads
├── bootstrap.php                  # Application initialization
├── .env.example                   # Environment configuration template
├── IMPROVEMENTS.md                # Detailed improvements
└── README.md                      # This file
```

## Usage Examples

### Accessing Products

```php
<?php
require 'bootstrap.php';

use App\Repository\ProductRepository;

$productRepo = new ProductRepository($GLOBALS['db']);

// Get all products
$products = $productRepo->paginate(1, 10);

// Find by ID
$product = $productRepo->find(123);

// Search
$results = $productRepo->search('landscape', 1, 10);

// Get by category
$paintings = $productRepo->getByCategory('paintings', 1, 10);
```

### Working with Database

```php
<?php
require 'bootstrap.php';

$db = $GLOBALS['db'];

// Select
$products = $db->select(
    'SELECT * FROM products WHERE category = ?',
    ['paintings']
);

// Insert
$db->insert('products', [
    'title' => 'Sunset',
    'description' => 'Beautiful sunset painting',
    'category' => 'paintings',
]);

// Update
$db->update('products', [
    'title' => 'Updated Title'
], ['id' => 123]);

// Delete
$db->delete('products', ['id' => 123]);

// Transaction
$db->transaction(function($db) {
    $db->insert('products', [...]);
    $db->update('news', [...], [...]);
});
```

### Input Validation & Security

```php
<?php
use App\Security\Sanitizer;
use App\Security\CsrfToken;

// Validate email
$email = Sanitizer::email($_POST['email'] ?? '');
if (!$email) {
    die('Invalid email');
}

// Sanitize string
$name = Sanitizer::string($_POST['name'] ?? '');

// Validate integer
$id = Sanitizer::integer($_GET['id'] ?? null);

// Validate date
$date = Sanitizer::date($_POST['date'] ?? '', 'Y-m-d');

// Validate URL
$url = Sanitizer::url($_POST['url'] ?? '');

// CSRF Protection
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!CsrfToken::validateFromRequest()) {
        die('CSRF token invalid');
    }
}

// Get CSRF token for forms
$token = CsrfToken::getToken();
?>
<form method="POST">
    <input type="hidden" name="_token" value="<?php echo $token; ?>">
    <!-- form fields -->
</form>
```

### Sending Emails

```php
<?php
use App\Mail\Mailer;

$mailer = new Mailer('noreply@art.local', 'Art Gallery');
$mailer
    ->to('customer@example.com', 'John Doe')
    ->cc('manager@art.local')
    ->subject('Thank you for your purchase')
    ->htmlBody('<p>Thank you for your order!</p>')
    ->body('Thank you for your order!')
    ->send();
```

### File Upload

```php
<?php
use App\File\FileUploader;

$uploader = new FileUploader(
    11_333_000,  // max 11MB
    ['jpg', 'png', 'gif', 'pdf'],
    'storage/uploads/'
);

if ($_FILES['image']) {
    $filename = $uploader->handle($_FILES['image']);
    if ($filename) {
        // Save filename to database
        echo "Uploaded: " . $filename;
    } else {
        echo implode(', ', $uploader->getErrors());
    }
}
```

## Routes

### GET Routes

| Route | Description |
|-------|-------------|
| `/?action=home` | Home page with featured products |
| `/?action=products` | All products listing |
| `/?action=products&category=paintings` | Products by category |
| `/?action=product&id=123` | Single product detail |
| `/?action=search&search=term` | Search results |
| `/?action=news` | News listing |
| `/?action=contact` | Contact form |

### POST Routes

| Route | Description |
|-------|-------------|
| `/?action=subscribe` | Subscribe to newsletter |
| `/?action=contact` | Submit contact form |

## Configuration

### Environment Variables (.env)

```env
# App
APP_DEBUG=false
APP_NAME="Art Gallery"
APP_URL=http://localhost
APP_TIMEZONE=Europe/Moscow

# Database
DB_DRIVER=mysql
DB_HOST=localhost
DB_PORT=3306
DB_DATABASE=painting_sales
DB_USERNAME=root
DB_PASSWORD=

# Email
ADMIN_EMAIL=admin@art.local
EMAIL_FROM=noreply@art.local
EMAIL_FROM_NAME="Art Gallery"

# Security
CSRF_ENABLED=true
```

## Security Features

### Input Validation
- String sanitization
- Email validation
- URL validation
- Date/time validation
- Phone number validation
- Integer/float validation

### Output Encoding
- HTML escaping
- JavaScript escaping
- Proper charset handling

### Database Security
- Prepared statements
- Parameter binding
- SQL injection prevention

### Session Security
- HTTPOnly cookies
- Secure flag (HTTPS in production)
- SameSite cookie attribute
- CSRF tokens

### File Upload Security
- MIME type validation
- File size limits
- Directory traversal prevention
- Safe filename generation

### Email Security
- Header injection prevention
- Email validation
- Proper headers configuration

## Best Practices

### Always Use Prepared Statements
```php
// ❌ DON'T
$query = "SELECT * FROM products WHERE id = " . $_GET['id'];

// ✅ DO
$products = $db->select(
    'SELECT * FROM products WHERE id = ?',
    [$id]
);
```

### Always Sanitize Input
```php
// ❌ DON'T
$name = $_POST['name'];

// ✅ DO
$name = Sanitizer::string($_POST['name'] ?? '');
```

### Always Escape Output
```php
// ❌ DON'T
<h1><?php echo $name; ?></h1>

// ✅ DO
<h1><?php echo htmlspecialchars($name); ?></h1>
```

### Always Validate CSRF Tokens
```php
// For forms
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!CsrfToken::validateFromRequest()) {
        die('Invalid token');
    }
}
```

## Troubleshooting

### Database Connection Error
- Check database credentials in `.env`
- Ensure MySQL is running
- Verify database exists

### File Upload Issues
- Check `storage/uploads/` permissions
- Verify `upload_max_filesize` in php.ini
- Check available disk space

### Email Not Sending
- Verify SMTP settings in `.env`
- Check firewall/port 587
- Review error logs in `storage/logs/`

### Session Issues
- Ensure `session.save_path` is writable
- Check session timeout settings
- Verify cookies are enabled

## Performance Tips

1. **Database**
   - Add indexes to frequently searched columns
   - Use pagination for large datasets
   - Cache database results when appropriate

2. **Files**
   - Compress images before upload
   - Use CDN for static assets
   - Implement lazy loading

3. **Code**
   - Use query caching
   - Implement object caching
   - Minimize HTTP requests

## Future Enhancements

- [ ] Template engine (Twig/Blade)
- [ ] Redis caching layer
- [ ] User authentication system
- [ ] Admin panel
- [ ] API endpoints
- [ ] Payment processing
- [ ] Image optimization
- [ ] Advanced search filters
- [ ] Multi-language support
- [ ] Analytics tracking

## Testing

```bash
# PHPUnit tests
phpunit tests/

# Security audit
php -S localhost:8000 -t public/

# Database backup
mysqldump -u root -p painting_sales > backup.sql
```

## Documentation

For detailed information about improvements made, see [IMPROVEMENTS.md](IMPROVEMENTS.md).

## Troubleshooting

### Common Issues

**Q: "Database connection failed"**
A: Check `.env` file database credentials and ensure MySQL is running.

**Q: "CSRF token validation failed"**
A: Ensure sessions are enabled and the form includes the `_token` field.

**Q: "File upload failed"**
A: Check `storage/uploads/` directory permissions and available disk space.

## License

This project is provided as-is for educational and commercial use.

## Support

For issues and questions:
1. Check the [IMPROVEMENTS.md](IMPROVEMENTS.md) for detailed technical information
2. Review error logs in `storage/logs/`
3. Check PHP error logs on your server

## Credits

Original website: Art Gallery - Painting Sales Platform (2016)
Refactored: Modern PHP 7.4+ version with security and architecture improvements

---

**Last Updated:** 2026-07-13
