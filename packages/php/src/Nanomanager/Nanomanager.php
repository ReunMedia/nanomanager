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

        $operation = $_GET["operation"];

        \trap($operation);

        if (is_string($operation)) {
            $this->runOperation($operation);
        }

        if ($returnOutput) {
            $output = ob_get_clean();
        }

        return (is_string($output)) ? $output : "";
    }

    private function runOperation(string $operation): void
    {
        // All operation responses are returned as JSON
        header('Content-Type: application/json; charset=utf-8');

        switch ($operation) {
            case 'listFiles':
                echo json_encode(["files" => $this->listFiles()]);

                break;

            default:
                http_response_code(400);

                echo json_encode([
                    "error" => "Unsupported operation '{$operation}'",
                ]);

                break;
        }
    }
}
