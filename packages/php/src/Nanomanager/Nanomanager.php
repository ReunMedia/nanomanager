<?php

declare(strict_types=1);

namespace Nanomanager;

class Nanomanager
{
    /**
     * @var resource
     */
    private $handle;

    private string $directory;

    /**
     * @throws \RuntimeException if directory couldn't be opened
     */
    public function __construct(string $directory)
    {
        $realDir = realpath($directory);
        $handle = false;
        if (false !== $realDir) {
            $this->directory = $realDir;
            $handle = opendir($this->directory);
        }
        if (false === $handle) {
            throw new \RuntimeException("Unable to open directory '{$directory}");
        }
        $this->handle = $handle;
    }

    /**
     * Get a list of all filenames in managed directory.
     *
     * Files are returned in naturally sorted case-insensitive order
     *
     * @return operation_listFiles_result
     */
    public function operation_listFiles(): array
    {
        $files = [];
        while ($filename = readdir($this->handle)) {
            // Don't return `.`, `..`, dotfiles or directories
            if (str_starts_with($filename, '.')
                || is_dir("{$this->directory}/{$filename}")
            ) {
                continue;
            }
            $files[] = $filename;
        }

        // Sort files in natural case-insensitive order
        sort($files, SORT_NATURAL | SORT_FLAG_CASE);

        return ['data' => ['files' => $files]];
    }

    /**
     * Rename a file.
     *
     * @param operation_renameFile_parameters $parameters
     *
     * @return operation_renameFile_result
     */
    public function operation_renameFile($parameters): array
    {
        $oldName = realpath("{$this->directory}/{$parameters['oldName']}");
        $newName = "{$this->directory}/{$parameters['newName']}";

        $result = [
            'data' => [
                'newName' => $parameters['oldName'],
            ],
        ];

        // Make sure the old file exists
        if (false === $oldName) {
            return $result;
        }

        // Prevent path traversal
        if (dirname($oldName) !== dirname($newName) && dirname($newName) !== dirname($this->directory)) {
            return $result;
        }

        // Prevent invalid characters
        if (false === $this->is_valid_filename($parameters['newName'])) {
            return $result;
        }

        // Make sure the new file doesn't exist
        // We're using `file_exists` instead of `is_file` to also consider
        // directories
        if (file_exists($newName)) {
            return $result;
        }

        if (rename($oldName, $newName)) {
            $result['data']['newName'] = $parameters['newName'];
        }

        return $result;
    }

    public function run(bool $returnOutput = false): string
    {
        $output = '';
        if ($returnOutput) {
            ob_start();
        }

        if ('POST' === $_SERVER['REQUEST_METHOD']) {
            $body = file_get_contents('php://input');
            $this->runOperation(is_string($body) ? $body : '{}');
        }

        if ($returnOutput) {
            $output = ob_get_clean();
        }

        return (is_string($output)) ? $output : '';
    }

    private function is_valid_filename(string $filename): bool
    {
        // Prevent empty filename
        if ('' === $filename) {
            return false;
        }

        // Prevent dotfiles
        if (str_starts_with($filename, '.')) {
            return false;
        }

        // Prevent leading / trailing space
        if (str_starts_with($filename, ' ') || str_ends_with($filename, ' ')) {
            return false;
        }

        $asdf = preg_match(
            '@[~<>:"/\|?*\x00-\x1F]@x',
            $filename
        );

        // Prevent invalid characters
        return 0 === $asdf;
    }

    private function runOperation(string $operationJSON): void
    {
        $operation = json_decode($operationJSON, true);
        if (!is_array($operation)) {
            // TODO - Better error handling
            throw new \Exception('Invalid operation');
        }

        $operationType = is_string($operation['operationType'] ?? false) ? $operation['operationType'] : '';
        $parameters = $operation['parameters'];
        if (!is_array($parameters)) {
            $parameters = [];
        }

        // All operation responses are returned as JSON
        header('Content-Type: application/json; charset=utf-8');

        $operationResult = [];

        switch ($operationType) {
            case 'listFiles':
                $operationResult = $this->operation_listFiles();

                break;

            case 'renameFile':
                $operationResult = $this->operation_renameFile([
                    // TODO - Better DRY parameter validation
                    'oldName' => is_string($parameters['oldName']) ? $parameters['oldName'] : '',
                    'newName' => is_string($parameters['newName']) ? $parameters['newName'] : '',
                ]);

                break;

            default:
                http_response_code(400);

                $resultData = [
                    'error' => "Unsupported operation '{$operationType}'",
                ];

                break;
        }

        echo json_encode($operationResult);
    }
}
