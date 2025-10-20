<?php

declare(strict_types=1);

use Reun\Nanomanager\Nanomanager;

$projectRoot = realpath(__DIR__.'/../../../../');

require_once '../vendor/autoload.php';

$nanomanager = new Nanomanager(
    "{$projectRoot}/packages/php/tests/Integration/_uploads",
    'http://localhost:8080/uploads',
    'http://localhost:8080',
);

echo $nanomanager->run();
