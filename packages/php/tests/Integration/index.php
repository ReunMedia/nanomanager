<?php

declare(strict_types=1);

use Reun\Nanomanager\Nanomanager;

// Disable CORS in development
header('Access-Control-Allow-Origin: *');

// Create test uploads directory
$uploadsDir = '_uploads';
$dir = __DIR__.'/'.$uploadsDir;
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

$proto = ($_SERVER['HTTPS'] ?? false) ? 'https' : 'http';
$host = is_string($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : '';
$baseUrl = "{$proto}://{$host}/{$uploadsDir}";

$apiUrl = "{$proto}://{$host}";

$nanomanager = new Nanomanager($dir, $baseUrl, $apiUrl);
echo $nanomanager->run();
