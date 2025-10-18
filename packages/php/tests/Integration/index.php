<?php

declare(strict_types=1);

use Reun\Nanomanager\Nanomanager;

// Disable CORS in development
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Content-Type');

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

    $host = $_SERVER['HTTP_HOST'];
    $method = $_SERVER['REQUEST_METHOD'];

    if (str_ends_with($host, ':8080') && 'GET' === $method) {
        trigger_error("You're accessing {$host} in development which uses built dist version of Nanomanager. You probably wanted to go to http://localhost:5173 instead.", E_USER_WARNING);
    }
}

$proto = ($_SERVER['HTTPS'] ?? false) ? 'https' : 'http';
$host = is_string($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : '';
$baseUrl = "{$proto}://{$host}/{$uploadsDir}";

$apiUrl = "{$proto}://{$host}";

$nanomanager = new Nanomanager($dir, $baseUrl, $apiUrl);
echo $nanomanager->run();
