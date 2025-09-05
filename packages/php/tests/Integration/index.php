<?php

declare(strict_types=1);

use Nanomanager\Nanomanager;

// This is only used for debugging. Autoloader is not be needed in the
// single-file distribution.
require_once __DIR__."/../../vendor/autoload.php";

// Disable CORS in development
header('Access-Control-Allow-Origin: *');

require __DIR__."/../../src/Nanomanager/Nanomanager.php";

// Create test data directory
$dir = __DIR__."/../_uploads";
if (!is_dir($dir)) {
    mkdir($dir);
}

(new Nanomanager($dir))->run();
