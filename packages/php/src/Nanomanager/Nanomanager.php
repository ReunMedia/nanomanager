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

        if ("POST" === $_SERVER["REQUEST_METHOD"]) {
            $body = file_get_contents('php://input');
            $this->runOperation(is_string($body) ? $body : "{}");
        }

        if ($returnOutput) {
            $output = ob_get_clean();
        }

        return (is_string($output)) ? $output : "";
    }

    private function runOperation(string $operationJSON): void
    {
        $operation = json_decode($operationJSON, true);
        if (!is_array($operation)) {
            // TODO - Better error handling
            throw new \Exception("Invalid operation");
        }

        $operationType = is_string($operation["operationType"] ?? false) ? $operation["operationType"] : "";
        $parameters = $operation["parameters"] ?? [];

        // All operation responses are returned as JSON
        header('Content-Type: application/json; charset=utf-8');

        $resultData = [];

        switch ($operationType) {
            case 'listFiles':
                /**
                 * @var ListFilesOperationData
                 */
                $resultData = ["files" => $this->listFiles()];

                break;

            default:
                http_response_code(400);

                $resultData = [
                    "error" => "Unsupported operation '{$operationType}'",
                ];

                break;
        }

        echo json_encode([
            "data" => $resultData,
        ]);
    }
}
