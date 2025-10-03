<?php

declare(strict_types=1);

namespace Tools;

use KevinGH\Box\Compactor\Compactor;
use PhpCodeMinifier\MinifierFactory;

/**
 * Box Compactor that uses PHPCodeMinifier to minify PHP code.
 *
 * This is used instead of `KevinGH\Box\Compactor\Php` as the latter only
 * removes docblocks and comments and doesn't significantly reduce file size.
 */
class MinifierCompactor implements Compactor
{
    public function compact(string $file, string $contents): string
    {
        if (preg_match('/\.php/', $file)) {
            $phpCodeMinifier = (new MinifierFactory())->create();

            return $phpCodeMinifier->minifyString($contents);
        }

        return $contents;
    }
}
