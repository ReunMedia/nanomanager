<?php

declare(strict_types=1);

namespace Nanomanager;

class Nanomanager
{
    /**
     * @var resource
     */
    private $handle;

    /**
     * @throws \RuntimeException if directory couldn't be opened
     */
    public function __construct(private string $directory)
    {
        $handle = opendir($directory);
        if (false === $handle) {
            throw new \RuntimeException("Unable to open directory '{$directory}");
        }
        $this->handle = $handle;
    }

    /**
     * Get a list of all filenames in managed directory.
     *
     * @return string[]
     */
    public function listFiles(): array
    {
        $files = [];
        while ($filename = readdir($this->handle)) {
            if ("." === $filename
                || ".." === $filename
                || is_dir("{$this->directory}/{$filename}")
            ) {
                continue;
            }
            $files[] = $filename;
        }

        return $files;
    }

    public function run(bool $returnOutput = false): string
    {
        $output = "";
        if ($returnOutput) {
            ob_start();
        }

        echo "<h1>Hello World!</h1>";

        if ($returnOutput) {
            $output = ob_get_clean();
        }

        return (is_string($output)) ? $output : "";
    }
}
