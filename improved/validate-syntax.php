#!/usr/bin/env php
<?php
/**
 * PHP Syntax Validator
 *
 * Validates all PHP files for syntax errors.
 */

$errors = [];
$checked = 0;

function checkDir($dir) {
    global $errors, $checked;

    $files = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($dir, RecursiveDirectoryIterator::SKIP_DOTS),
        RecursiveIteratorIterator::LEAVES_ONLY
    );

    foreach ($files as $file) {
        if ($file->getExtension() === 'php') {
            $checked++;
            $output = [];
            $return = 0;

            exec(sprintf('php -l %s', escapeshellarg($file->getRealPath())), $output, $return);

            if ($return !== 0) {
                $errors[] = [
                    'file' => $file->getRealPath(),
                    'error' => implode("\n", $output),
                ];
            }
        }
    }
}

echo "🔍 Validating PHP Syntax...\n\n";

// Check src directory
checkDir(__DIR__ . '/src');
checkDir(__DIR__ . '/tests');
checkDir(__DIR__ . '/config');
checkDir(__DIR__ . '/public');

echo "✅ Checked $checked PHP files\n";

if (empty($errors)) {
    echo "\n✅ All PHP files have valid syntax!\n";
    exit(0);
} else {
    echo "\n❌ Found " . count($errors) . " syntax error(s):\n\n";

    foreach ($errors as $error) {
        echo "File: {$error['file']}\n";
        echo "Error: {$error['error']}\n";
        echo str_repeat('-', 80) . "\n";
    }

    exit(1);
}
