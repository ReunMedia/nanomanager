<?php

declare(strict_types=1);

namespace Nanomanager;

class Nanomanager
{
    /**
     * Nanomanager version.
     *
     * @var string
     */
    // DEVELOPER NOTE - Using `@var string` instead of PHP type drops PHP
    // requirement from `8.3` to `8.2`
    //
    // DEVELOPER NOTE - This is automatically updated by `prepare-release`
    // script
    public const VERSION = '0.1.0';

    /**
     * @var resource
     */
    protected $handle;

    protected string $directory;

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
        protected string $baseUrl,

        /**
         * URL used to access Nanomanager API from frontend.
         *
         * E.g. "https://example.com/admin/nanomanager"
         */
        protected string $apiUrl,

        /**
         * Enable to automatically create managed directory if it doesn't exist.
         */
        bool $createMissingDirectory = false,

        /**
         * Additional attributes passed frontend Nanomanager component.
         *
         * E.g. 'theme="dark"'
         *
         * The string is passed to `<nano-filemanager>` element as is without
         * any processing. Run it through `htmlspecialchars()` beforehand if you
         * need to handle special characters.
         */
        protected string $frontendAttributes = '',
    ) {
        if ($createMissingDirectory && !file_exists($directory)) {
            mkdir($directory, 0777, true);
        }

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
     * @param "GET"|"POST" $requestMethod
     */
    public function run(string $requestMethod, string $requestBody): string
    {
        $output = '';

        if ('POST' === $requestMethod) {
            // FormData (for file uploads)
            if ('' === $requestBody) {
                $operationType = is_string($_POST['operationType']) ? $_POST['operationType'] : '';
                $parameters = $_POST;
            }
            // JSON body
            else {
                $operation = json_decode($requestBody, true);
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

            $output = $this->runOperation($operationType, $parameters);
        } elseif ('GET' === $requestMethod) {
            $output = $this->getFrontend();
        }

        return $output;
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
    protected function runOperation(string $operationType, array $parameters): string
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

    protected function getFrontend(): string
    {
        // Set base path for frontend files in Composer installation
        $frontendPath = __DIR__.'/../../../frontend';
        // If we're running inside PHAR, use PHAR path as base path instead
        $pharBasePath = \Phar::running();
        if ('' !== $pharBasePath) {
            $frontendPath = "{$pharBasePath}/packages/frontend";
        }
        $htmlPath = "{$frontendPath}/index.html";
        $jsPath = "{$frontendPath}/dist/nanomanager.umd.cjs";

        // Load frontend HTML
        $frontendHtml = (string) file_get_contents($htmlPath);

        if ('' === $frontendHtml) {
            throw new \Exception("Unable to load frontend HTML from '{$frontendHtml}'.");
        }

        // Load frontend JS and inline it into frontend
        $frontendJs = (string) file_get_contents($jsPath);
        $replacedCount = 0;
        $frontendHtml = (string) preg_replace(
            '~<script.*?src="\/src/main.ts"><\/script>~',
            "<script type=\"module\">{$frontendJs}</script>",
            $frontendHtml,
            count: $replacedCount
        );

        if ($replacedCount < 1) {
            throw new \Exception('Unable to inject frontend JS to HTML file.');
        }

        // Set Nanomanager element attributes. `api-url` is always defined and
        // cannot be omitted.
        $attributes = " api-url=\"{$this->apiUrl}\" ".$this->frontendAttributes;

        return (string) preg_replace(
            '~(<nano-filemanager.*?)>~',
            "$1{$attributes}>",
            $frontendHtml
        );
    }

    #
    #region Operations
    #

    /**
     * Get a list of all filenames in managed directory.
     *
     * Files are returned in naturally sorted case-insensitive order
     *
     * @param operation_listFiles["parameters"] $parameters
     *
     * @return operation_listFiles["result"]
     */
    protected function operation_listFiles($parameters): array
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
    protected function operation_renameFile($parameters): array
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
    protected function operation_deleteFile($parameters): array
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
    protected function operation_uploadFile($parameters): array
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

    #endregion

    #
    #region Utilities
    #

    /**
     * Mockable wrapper of `move_uploaded_file()` for testing purposes, since
     * `move_uploaded_file()` requires files to actually be uploaded with POST.
     */
    protected function move_uploaded_file(string $from, string $to): bool
    {
        return move_uploaded_file($from, $to);
    }

    #endregion
}
