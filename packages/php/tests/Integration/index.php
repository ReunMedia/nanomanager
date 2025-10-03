<?php

declare(strict_types=1);

use Nanomanager\Nanomanager;

// Disable CORS in development
header('Access-Control-Allow-Origin: *');

// Create test data directory
$dir = __DIR__.'/../_uploads';
if (!is_dir($dir)) {
    mkdir($dir);
}

// Preview mode uses built Nanomanager.phar
if ('true' === ($_ENV['NANOMANAGER_PREVIEW'] ?? false)) {
    require_once __DIR__.'/../../dist/Nanomanager.phar';
}
// Dev mode uses autoloader `(src/Nanomanager/Nanomanager.php)`
else {
    require_once __DIR__.'/../../vendor/autoload.php';
}

(new Nanomanager($dir))->run();
