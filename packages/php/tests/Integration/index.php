<?php

declare(strict_types=1);

use Nanomanager\Nanomanager;

// This is only used for debugging. Autoloader is not be needed in the
// single-file distribution.
require_once __DIR__."/../../vendor/autoload.php";

require __DIR__."/../../src/Nanomanager/Nanomanager.php";

(new Nanomanager(__DIR__."/../fixtures/uploads"))->run();
