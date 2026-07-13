# Testing Guide

Comprehensive testing suite for the refactored painting sales website.

## Overview

The test suite covers:
- **Unit Tests** - Individual class and method functionality
- **Integration Tests** - Database operations and service interactions
- **Security Tests** - Vulnerability prevention and protection mechanisms

## Installation

### Install PHPUnit

```bash
composer require --dev phpunit/phpunit
```

Or manually:

```bash
wget https://phar.phpunit.de/phpunit-9.5.phar
chmod +x phpunit-9.5.phar
mv phpunit-9.5.phar phpunit
```

## Running Tests

### Run All Tests

```bash
./vendor/bin/phpunit
# or
phpunit
```

### Run Specific Test Suite

```bash
# Unit tests only
phpunit tests/Unit

# Security tests only
phpunit tests/Security

# Integration tests only
phpunit tests/Integration
```

### Run Specific Test File

```bash
phpunit tests/Unit/Security/SanitizerTest.php
```

### Run Specific Test Method

```bash
phpunit tests/Unit/Security/SanitizerTest.php --filter testSanitizeEmail
```

## Test Coverage

Generate code coverage report:

```bash
phpunit --coverage-html coverage/html
```

View coverage in browser:

```
open coverage/html/index.html
```

## Test Structure

```
tests/
├── Unit/                    # Unit tests
│   ├── Security/
│   │   ├── SanitizerTest.php
│   │   └── CsrfTokenTest.php
│   ├── Mail/
│   │   └── MailerTest.php
│   └── File/
│       └── FileUploaderTest.php
├── Integration/            # Integration tests
│   ├── Database/
│   │   └── DatabaseTest.php
│   └── RepositoryTest.php
├── Security/               # Security tests
│   ├── SqlInjectionTest.php
│   ├── XssPreventionTest.php
│   └── CsrfProtectionTest.php
└── bootstrap.php          # Test environment setup
```

## Unit Tests

### Sanitizer Tests (`SanitizerTest.php`)

Tests input validation and sanitization:

```php
✅ testSanitizeString()
✅ testSanitizeEmail()
✅ testSanitizeInteger()
✅ testSanitizeFloat()
✅ testSanitizeUrl()
✅ testSanitizeFileName()
✅ testHtmlEscape()
✅ testJavaScriptEscape()
✅ testSqlLike()
✅ testValidateDate()
✅ testValidatePhone()
✅ testXssVulnerability()
```

Run:
```bash
phpunit tests/Unit/Security/SanitizerTest.php
```

### CSRF Token Tests (`CsrfTokenTest.php`)

Tests CSRF protection:

```php
✅ testGenerateToken()
✅ testTokensAreConsistent()
✅ testValidateToken()
✅ testInvalidTokenFails()
✅ testEmptyTokenFails()
✅ testRegenerateToken()
✅ testTokenHashing()
```

Run:
```bash
phpunit tests/Unit/Security/CsrfTokenTest.php
```

### Mailer Tests (`MailerTest.php`)

Tests email functionality:

```php
✅ testMailerInitialization()
✅ testAddRecipient()
✅ testInvalidEmailRejected()
✅ testAddCc()
✅ testAddBcc()
✅ testSetSubject()
✅ testSubjectNewlineRemoved()
✅ testSetBody()
✅ testSetHtmlBody()
✅ testFluentInterface()
✅ testEmailInjectionPrevention()
✅ testSubjectLengthLimited()
```

Run:
```bash
phpunit tests/Unit/Mail/MailerTest.php
```

### File Uploader Tests (`FileUploaderTest.php`)

Tests secure file upload:

```php
✅ testFileUploaderInitialization()
✅ testUploadValidFile()
✅ testRejectFileWithInvalidType()
✅ testRejectFileThatExceedsMaxSize()
✅ testSanitizeFileName()
✅ testHandleUploadError()
✅ testDeleteFile()
✅ testPreventDoubleExtensionAttack()
```

Run:
```bash
phpunit tests/Unit/File/FileUploaderTest.php
```

## Security Tests

### SQL Injection Prevention (`SqlInjectionTest.php`)

Tests that SQL injection attacks are prevented:

```php
✅ testSqlInjectionAttemptsWithPreparedStatements()
✅ testPreparedStatementSafety()
✅ testConcatenationVulnerability()
✅ testCommentBypassPrevention()
✅ testUnionBasedInjectionPrevention()
✅ testTimeBasedBlindInjectionPrevention()
```

Attacks tested:
- `1' OR '1'='1`
- `1'; DROP TABLE products; --`
- `1' UNION SELECT * FROM users --`
- `' OR 1=1 --`
- Comment-based bypasses

Run:
```bash
phpunit tests/Security/SqlInjectionTest.php
```

### XSS Prevention (`XssPreventionTest.php`)

Tests that XSS attacks are prevented:

```php
✅ testStoredXssAttacks()
✅ testReflectedXssAttacks()
✅ testHtmlEntityEncoding()
✅ testAttributeInjection()
✅ testJavaScriptProtocolAttack()
✅ testDataUriXss()
✅ testSvgBasedXss()
✅ testEventHandlerRemoval()
✅ testUrlEncoding()
✅ testDoubleEncodingAttack()
```

Attacks tested:
- `<script>alert("xss")</script>`
- `<img src=x onerror="alert(1)">`
- `<svg/onload=alert("xss")>`
- Event handler injections
- JavaScript protocol
- Data URIs

Run:
```bash
phpunit tests/Security/XssPreventionTest.php
```

## Test Examples

### Testing Input Validation

```php
public function testSanitizeEmail(): void
{
    $this->assertEquals('test@example.com', Sanitizer::email('test@example.com'));
    $this->assertNull(Sanitizer::email('invalid-email'));
}
```

### Testing CSRF Protection

```php
public function testValidateToken(): void
{
    $token = CsrfToken::generate();
    $this->assertTrue(CsrfToken::validate($token));
    $this->assertFalse(CsrfToken::validate('wrong-token'));
}
```

### Testing Security Features

```php
public function testXssVulnerability(): void
{
    $xss = '<script>alert("xss")</script>';
    $sanitized = Sanitizer::string($xss, true);
    $this->assertStringNotContainsString('<', $sanitized);
}
```

## Continuous Integration

### GitHub Actions Example

Create `.github/workflows/tests.yml`:

```yaml
name: Tests

on: [push, pull_request]

jobs:
  test:
    runs-on: ubuntu-latest
    
    strategy:
      matrix:
        php-version: ['7.4', '8.0', '8.1']
    
    steps:
      - uses: actions/checkout@v2
      
      - name: Setup PHP
        uses: shivammathur/setup-php@v2
        with:
          php-version: ${{ matrix.php-version }}
      
      - name: Install dependencies
        run: composer install
      
      - name: Run tests
        run: ./vendor/bin/phpunit
      
      - name: Generate coverage
        run: ./vendor/bin/phpunit --coverage-clover coverage.xml
      
      - name: Upload coverage
        uses: codecov/codecov-action@v2
```

## Best Practices

### Writing Tests

1. **One assertion per test** (when possible)
   ```php
   // Good
   public function testEmailValidation(): void
   {
       $this->assertEquals('test@example.com', Sanitizer::email('test@example.com'));
   }
   ```

2. **Clear test names**
   ```php
   // Good
   public function testInvalidEmailReturnsNull(): void
   
   // Bad
   public function testEmail(): void
   ```

3. **Arrange-Act-Assert pattern**
   ```php
   public function testSanitization(): void
   {
       // Arrange
       $dirty = '<script>alert("xss")</script>';
       
       // Act
       $clean = Sanitizer::html($dirty);
       
       // Assert
       $this->assertStringNotContainsString('<script>', $clean);
   }
   ```

### Test Organization

1. Keep test files parallel to source files
2. Use namespaces that mirror source structure
3. Group related tests in test classes
4. Use setUp/tearDown for common initialization

## Debugging Tests

### Verbose Output

```bash
phpunit --verbose
```

### Stop on First Failure

```bash
phpunit --stop-on-failure
```

### Stop on First Error

```bash
phpunit --stop-on-error
```

### Print Detailed Information

```bash
phpunit --verbose --debug
```

## Performance Testing

```bash
phpunit --report-useless-tests
```

## Creating New Tests

### Template

```php
<?php

namespace Tests\Unit\YourNamespace;

use App\YourClass;
use PHPUnit\Framework\TestCase;

class YourClassTest extends TestCase
{
    private YourClass $instance;

    protected function setUp(): void
    {
        $this->instance = new YourClass();
    }

    public function testFeature(): void
    {
        $result = $this->instance->method();
        $this->assertTrue($result);
    }
}
```

## Troubleshooting

### Tests Won't Run

```bash
# Check PHP version
php --version

# Check PHPUnit installation
./vendor/bin/phpunit --version

# Run with absolute path
/usr/bin/php ./vendor/bin/phpunit
```

### Database Connection Issues

- Ensure test database is configured
- Check DATABASE_URL environment variable
- Run schema migration for tests

### Autoloading Errors

```bash
# Regenerate autoloader
composer dump-autoload

# Run in verbose mode
phpunit --verbose
```

## Reporting Issues

When reporting test failures:

1. Include test name
2. Include PHP version
3. Include error message
4. Include full stack trace

Example:
```
Test: SanitizerTest::testSanitizeEmail
PHP: 7.4.0
Error: Assertion failed...
Stack: [full trace]
```

## Additional Resources

- [PHPUnit Documentation](https://phpunit.de/)
- [Testing Best Practices](https://phpunit.de/best-practices.html)
- [Security Testing Guide](https://owasp.org/www-community/attacks)
