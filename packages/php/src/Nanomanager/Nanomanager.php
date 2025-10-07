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
    public function __construct(
        string $directory,
        /**
         * Base URL used when linking to files.
         *
         * @var string
         */
        private string $baseUrl,
    ) {
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

        return ['data' => ['files' => $files, 'baseUrl' => $this->baseUrl]];
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
        $oldName = $parameters['oldName'];
        $newName = $parameters['newName'];

        $oldNameFull = realpath("{$this->directory}/{$oldName}");
        $newNameFull = "{$this->directory}/{$newName}";

        $result = [
            'data' => [
                'newName' => $oldName,
            ],
        ];

        // Make sure the old file exists
        if (false === $oldNameFull) {
            return $result;
        }

        // Validate old and new filenames
        if (!$this->isValidFilename($oldName) || !$this->isValidFilename($newName)) {
            return $result;
        }

        // Make sure the new file doesn't exist
        // We're using `file_exists` instead of `is_file` to also consider
        // directories
        if (file_exists($newNameFull)) {
            return $result;
        }

        if (rename($oldNameFull, $newNameFull)) {
            $result['data']['newName'] = $newName;
        }

        return $result;
    }

    /**
     * Delete a file.
     *
     * @param operation_deleteFile_parameters $parameters
     *
     * @return operation_deleteFile_result
     */
    public function operation_deleteFile($parameters): array
    {
        $success = false;
        $filename = $parameters['filename'];
        $fullName = realpath("{$this->directory}/{$filename}");

        if ($this->isValidFilename($filename) && is_string($fullName) && !is_dir($fullName)) {
            $success = unlink($fullName);
        }

        return ['data' => ['success' => $success]];
    }

    /**
     * Upload a file.
     *
     * @return operation_uploadFile_result
     */
    public function operation_uploadFile(): array
    {
        $result = [
            'data' => [
                'uploadedFiles' => [],
                'filesWithErrors' => [],
            ],
        ];

        // Return if there are no files uploaded
        if ([] === $_FILES) {
            return $result;
        }

        /**
         * @var UploadedFiles
         */
        $files = $_FILES['files'] ?? [];

        foreach ($files['error'] as $i => $error) {
            $name = $files['name'][$i];
            $tmp_name = $files['tmp_name'][$i];
            $success = false;

            // Validate filename and make sure there was no upload error
            if ($this->isValidFilename($name) && UPLOAD_ERR_OK === $error) {
                $success = $this->move_uploaded_file($tmp_name, "{$this->directory}/{$name}");
            }

            if ($success) {
                $result['data']['uploadedFiles'][] = $name;
            } else {
                $result['data']['filesWithErrors'][] = $name;
            }
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

            // FormData (for file uploads)
            if ('' === $body) {
                $operationType = is_string($_POST['operationType']) ? $_POST['operationType'] : '';
                $parameters = $_POST;
            }
            // JSON body
            else {
                $operation = json_decode((string) $body, true);
                if (!is_array($operation)) {
                    // TODO - Better error handling
                    throw new \Exception('Invalid operation');
                }
                $operationType = is_string($operation['operationType'] ?? false) ? $operation['operationType'] : '';
                $parameters = $operation['parameters'];
                if (!is_array($parameters)) {
                    $parameters = [];
                }
            }

            echo $this->runOperation($operationType, $parameters);
        } elseif ('GET' === $_SERVER['REQUEST_METHOD']) {
            $frontendFile = 'phar://nanomanager.phar/frontend/dist/index.html';
            if (file_exists($frontendFile)) {
                // @phpstan-ignore require.fileNotFound
                require $frontendFile;
            } else {
                throw new \Exception("Unable to open frontend file inside PHAR. This means that you're probably running Nanomanager in dev mode and need to open frontend separately by running `bun moon run frontend:dev`.");
            }
        }

        if ($returnOutput) {
            $output = ob_get_clean();
        }

        return (is_string($output)) ? $output : '';
    }

    /**
     * Validates a filename as a valid file managed by Nanomanager.
     *
     * @param string $filename Filename without path
     *
     * @return bool true if filename is valid
     */
    public function isValidFilename(string $filename): bool
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

        // Prevent invalid characters
        if (0 !== preg_match(
            '@[~<>:"/\|?*\x00-\x1F]@x',
            $filename
        )) {
            return false;
        }

        // Prevent directory traversal outside managed directory
        if (dirname($this->directory.'/'.$filename) !== dirname($this->directory.'/.')) {
            return false;
        }

        return true;
    }

    /**
     * @param OperationType|string                                                    $operationType
     * @param mixed[]|operation_deleteFile_parameters|operation_renameFile_parameters $parameters
     */
    public function runOperation(string $operationType, array $parameters): string
    {
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

            case 'deleteFile':
                $operationResult = $this->operation_deleteFile([
                    'filename' => is_string($parameters['filename']) ? $parameters['filename'] : '',
                ]);

                break;

            case 'uploadFile':
                $operationResult = $this->operation_uploadFile();

                break;

            default:
                http_response_code(400);

                $operationResult = [
                    'error' => "Unsupported operation '{$operationType}'",
                ];

                break;
        }

        return (string) json_encode($operationResult);
    }

    /**
     * Mockable wrapper of `move_uploaded_file()` for testing purposes, since
     * `move_uploaded_file()` requires files to actually be uploaded with POST.
     */
    protected function move_uploaded_file(string $from, string $to): bool
    {
        return move_uploaded_file($from, $to);
    }
}
