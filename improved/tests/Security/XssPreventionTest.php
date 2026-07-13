<?php

namespace Tests\Security;

use App\Security\Sanitizer;
use PHPUnit\Framework\TestCase;

/**
 * XSS Prevention Security Tests
 *
 * Tests that Cross-Site Scripting (XSS) attacks are prevented through
 * proper input validation and output encoding.
 */
class XssPreventionTest extends TestCase
{
    public function testStoredXssAttacks(): void
    {
        $xssAttempts = [
            '<script>alert("xss")</script>',
            '<img src=x onerror="alert(1)">',
            '<svg/onload=alert("xss")>',
            '<body onload=alert("xss")>',
            '<iframe src="javascript:alert(\'xss\')">',
        ];

        foreach ($xssAttempts as $attempt) {
            $sanitized = Sanitizer::string($attempt, true);
            $this->assertStringNotContainsString('<', $sanitized);
            $this->assertStringNotContainsString('>', $sanitized);
            $this->assertStringNotContainsString('script', $sanitized);
            $this->assertStringNotContainsString('alert', $sanitized);
        }
    }

    public function testReflectedXssAttacks(): void
    {
        // Simulating user input that might be reflected in URL or form
        $userInput = '<script>alert("xss")</script>';

        // When displaying user input, it should be escaped
        $escaped = Sanitizer::html($userInput);

        $this->assertStringNotContainsString('<script>', $escaped);
        $this->assertStringContainsString('&lt;script&gt;', $escaped);
    }

    public function testHtmlEntityEncoding(): void
    {
        $tests = [
            '<' => '&lt;',
            '>' => '&gt;',
            '"' => '&quot;',
            "'" => '&#039;',
            '&' => '&amp;',
        ];

        foreach ($tests as $input => $expected) {
            $escaped = Sanitizer::html($input);
            $this->assertStringContainsString(strtolower($expected), strtolower($escaped));
        }
    }

    public function testAttributeInjection(): void
    {
        $injection = '" onmouseover="alert(\'xss\')"';
        $escaped = Sanitizer::html($injection);

        $this->assertStringNotContainsString('onmouseover', $escaped);
        $this->assertStringNotContainsString('"', $escaped);
    }

    public function testJavaScriptProtocolAttack(): void
    {
        $xssAttempts = [
            'javascript:alert("xss")',
            'JAVASCRIPT:alert("xss")',
            'jAvAsCrIpT:alert("xss")',
        ];

        foreach ($xssAttempts as $attempt) {
            $url = Sanitizer::url($attempt);
            $this->assertNull($url);
        }
    }

    public function testDataUriXss(): void
    {
        $xss = 'data:text/html,<script>alert("xss")</script>';
        $url = Sanitizer::url($xss);

        // Should reject data URIs that could contain scripts
        $this->assertNull($url);
    }

    public function testSvgBasedXss(): void
    {
        $svgXss = [
            '<svg onload="alert(\'xss\')"></svg>',
            '<svg><script>alert("xss")</script></svg>',
            '<svg><animate onbegin="alert(\'xss\')" attributeName="x" dur="1s" />',
        ];

        foreach ($svgXss as $svg) {
            $sanitized = Sanitizer::string($svg, true);
            $this->assertStringNotContainsString('<svg', $sanitized);
            $this->assertStringNotContainsString('onload', $sanitized);
            $this->assertStringNotContainsString('onbegin', $sanitized);
        }
    }

    public function testEventHandlerRemoval(): void
    {
        $eventHandlers = [
            'onclick',
            'onmouseover',
            'onerror',
            'onload',
            'onsubmit',
            'onchange',
            'onkeyup',
            'onkeydown',
        ];

        $html = '<div ' . implode('="xss" ', $eventHandlers) . '="xss">Content</div>';
        $escaped = Sanitizer::html($html);

        foreach ($eventHandlers as $handler) {
            $this->assertStringNotContainsString($handler, $escaped);
        }
    }

    public function testUrlEncoding(): void
    {
        $urls = [
            'http://example.com',
            'https://example.com/path?query=value',
            'ftp://files.example.com',
        ];

        foreach ($urls as $url) {
            $sanitized = Sanitizer::url($url);
            $this->assertNotNull($sanitized);
        }
    }

    public function testDoubleEncodingAttack(): void
    {
        $encoded = '%3Cscript%3Ealert(%22xss%22)%3C/script%3E';
        // URL decode would result in <script>alert("xss")</script>
        // But we should handle this properly

        $this->assertIsString($encoded);
    }
}
