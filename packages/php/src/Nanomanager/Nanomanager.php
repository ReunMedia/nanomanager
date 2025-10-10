<?php

declare(strict_types=1);

namespace Nanomanager;

class Nanomanager
{
    /**
     * Nanomanager version.
     */
    public const string VERSION = '@git_version@';

    /**
     * Full SHA of the commit this version was built from.
     */
    public const string COMMIT_SHA = '@git_commit@';

    /**
     * @var resource
     */
    private $handle;

    private string $directory;

    /**
     * @throws \RuntimeException if directory couldn't be opened
     */
    public function __construct(
        /**
         * Directory managed by Nanomanager.
         *
         * E.g. "public/uploads"
         */
        string $directory,
        /**
         * Base URL used when linking to files.
         *
         * E.g. "https://example.com/uploads"
         */
        private string $baseUrl,
        /**
         * URL used to access Nanomanager API from frontend.
         *
         * E.g. "https://example.com/admin/nanomanager"
         */
        private string $apiUrl,
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
     * @param operation_listFiles["parameters"] $parameters
     *
     * @return operation_listFiles["result"]
     */
    public function operation_listFiles($parameters): array
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
     * @param operation_renameFile["parameters"] $parameters
     *
     * @return operation_renameFile["result"]
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
     * @param operation_deleteFile["parameters"] $parameters
     *
     * @return operation_deleteFile["result"]
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
     * @param operation_uploadFile["parameters"] $parameters
     *
     * @return operation_uploadFile["result"]
     */
    public function operation_uploadFile($parameters): array
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
            $frontendData = file_get_contents($frontendFile);
            if (false === $frontendData) {
                throw new \Exception("Unable to open frontend file inside PHAR. This means that you're probably running Nanomanager in dev mode and need to open frontend separately by running `bun moon run frontend:dev`.");
            }
            $frontendData = $this->replaceFrontendPlaceholders($frontendData);

            echo $frontendData;
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
     * @param OperationType|string $operationType
     * @param mixed[]|operation_listFiles["parameters"]|operation_deleteFile["parameters"]|operation_renameFile["parameters"]|operation_uploadFile["parameters"] $parameters
     */
    public function runOperation(string $operationType, array $parameters): string
    {
        // All operation responses are returned as JSON
        header('Content-Type: application/json; charset=utf-8');

        $operationResult = [];

        switch ($operationType) {
            case 'listFiles':
                $operationResult = $this->operation_listFiles($parameters);

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
                $operationResult = $this->operation_uploadFile($parameters);

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

    public function replaceFrontendPlaceholders(string $frontendData): string
    {
        $placeholders = [
            '%NANOMANAGER_API_URL%',
            '%NANOMANAGER_VERSION%',
        ];

        $replacements = [
            $this->apiUrl,
            static::VERSION,
        ];

        return str_replace($placeholders, $replacements, $frontendData);
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
