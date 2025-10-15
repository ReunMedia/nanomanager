<?php

declare(strict_types=1);

use Nanomanager\Nanomanager;
use Tests\TestCase;

function createNanomanager(): Nanomanager
{
    return new Nanomanager(
        TestCase::$uploadsDirectory,
        'https://example.com/uploads',
        'https://example.com/nanomanager',
    );
}

function createNanomanagerMockOrSpy($spy = false)
{
    $args = [
        TestCase::$uploadsDirectory,
        'https://example.com/uploads',
        'https://example.com/nanomanager',
    ];

    return ($spy)
        ? Mockery::spy(Nanomanager::class, $args)
        : Mockery::mock(Nanomanager::class, $args);
}

function createNanomanagerMock()
{
    return createNanomanagerMockOrSpy();
}

function createNanomanagerSpy()
{
    return createNanomanagerMockOrSpy(true);
}

describe(Nanomanager::class, function () {
    it('should allow uploading files', function () {})->todo();
});

describe(Nanomanager::class.'::isValidFilename()', function () {
    it('should not allow directory traversal', function () {
        $nanomanager = createNanomanager();

        // Down
        expect($nanomanager->isValidFilename('subdir/renamed.txt'))->toBeFalse();

        // Up
        expect($nanomanager->isValidFilename('../renamed.txt'))->toBeFalse();

        // Home
        expect($nanomanager->isValidFilename('~/renamed.txt'))->toBeFalse();
    });

    it('should not allow empty filename', function () {
        $nanomanager = createNanomanager();

        expect($nanomanager->isValidFilename(''))->toBeFalse();
    });

    it('should not allow filename to begin or end with a space', function () {
        $nanomanager = createNanomanager();

        expect($nanomanager->isValidFilename(' hello.txt'))->toBeFalse();
        expect($nanomanager->isValidFilename('hello.txt '))->toBeFalse();
    });

    it('should not allow dotfiles', function () {
        $nanomanager = createNanomanager();

        expect($nanomanager->isValidFilename('.htaccess'))->toBeFalse();
    });

    it('should not allow invalid characters', function () {
        $nanomanager = createNanomanager();

        expect($nanomanager->isValidFilename('.htaccess'))->toBeFalse();

        // Renaming file to `~`
        expect($nanomanager->isValidFilename('~'))->toBeFalse;

        // Renaming file to `<hello.txt`
        expect($nanomanager->isValidFilename('<hello.txt'))->toBeFalse;
    });
});

describe("'listFiles' operation", function () {
    it('should return files in naturally sorted case-insensitive order', function () {
        $nanomanager = createNanomanager();

        $body = json_encode([
            'operationType' => 'listFiles',
            'parameters' => [],
        ]);

        $result = json_decode($nanomanager->run('POST', $body), true);

        $files = $result['data']['files'];
        expect($files)->toHaveCount(5);
        expect($files)->toBe(['1a.txt', '2b.txt', '11c.txt', 'hello.txt', 'Second-file.txt']);
    });

    it('should not return dotfiles', function () {
        $nanomanager = createNanomanager();

        $body = json_encode([
            'operationType' => 'listFiles',
            'parameters' => [],
        ]);
        $result = json_decode($nanomanager->run('POST', $body), true);

        $files = $result['data']['files'];
        expect($files)->not()->toContain('.htaccess');
    });
});

describe("'renameFile' operation", function () {
    it('should rename file', function () {
        $nanomanager = createNanomanager();

        $body = json_encode([
            'operationType' => 'renameFile',
            'parameters' => ['oldName' => 'hello.txt', 'newName' => 'renamed.txt'],
        ]);
        $result = json_decode($nanomanager->run('POST', $body), true);

        expect($result['data']['newName'])->toBe('renamed.txt');
        expect(file_exists(TestCase::$uploadsDirectory.'/hello.txt'))->toBeFalse();
        expect(file_exists(TestCase::$uploadsDirectory.'/renamed.txt'))->toBeTrue();

        $body = json_encode([
            'operationType' => 'renameFile',
            'parameters' => ['oldName' => 'renamed.txt', 'newName' => 'hello.txt'],
        ]);
        $result = json_decode($nanomanager->run('POST', $body), true);

        expect($result['data']['newName'])->toBe('hello.txt');
        expect(file_exists(TestCase::$uploadsDirectory.'/renamed.txt'))->toBeFalse();
        expect(file_exists(TestCase::$uploadsDirectory.'/hello.txt'))->toBeTrue();
    });
    it("should fail if the original file doesn't exist", function () {
        $nanomanager = createNanomanager();

        $body = json_encode([
            'operationType' => 'renameFile',
            'parameters' => ['oldName' => 'not-found.txt', 'newName' => 'renamed.txt'],
        ]);
        $result = json_decode($nanomanager->run('POST', $body), true);

        expect($result['data']['newName'])->not()->toBe('renamed.txt');
        expect($result['data']['newName'])->toBe('not-found.txt');
    });
    it('should validate new and old filename before renaming', function () {
        $nanomanager = createNanomanagerSpy()->makePartial();

        $body = json_encode([
            'operationType' => 'renameFile',
            'parameters' => ['oldName' => 'hello.txt', 'newName' => 'Second-file.txt'],
        ]);

        /** @disregard P1013 */
        $nanomanager->run('POST', $body);

        /** @disregard P1013 */
        $nanomanager->shouldHaveReceived('isValidFilename')
            ->with('hello.txt')
        ;

        /** @disregard P1013 */
        $nanomanager->shouldHaveReceived('isValidFilename')
            ->with('Second-file.txt')
        ;
    });
    it('should silently fail if both old and new name are identical', function () {
        $nanomanager = createNanomanager();

        $body = json_encode([
            'operationType' => 'renameFile',
            'parameters' => ['oldName' => 'hello.txt', 'newName' => 'hello.txt'],
        ]);
        $result = json_decode($nanomanager->run('POST', $body), true);

        expect($result['data']['newName'])->toBe('hello.txt');
    });
    it('should not allow renaming to a name that already exists', function () {
        $nanomanager = createNanomanager();

        $body = json_encode([
            'operationType' => 'renameFile',
            'parameters' => ['oldName' => 'hello.txt', 'newName' => 'Second-file.txt'],
        ]);
        $result = json_decode($nanomanager->run('POST', $body), true);

        expect($result['data']['newName'])->toBe('hello.txt');

        // Prevent renaming to directory
        $body = json_encode([
            'operationType' => 'renameFile',
            'parameters' => ['oldName' => 'hello.txt', 'newName' => 'subdir'],
        ]);
        $result = json_decode($nanomanager->run('POST', $body), true);

        expect($result['data']['newName'])->toBe('hello.txt');
    });
    it('should output client-side handleable errors when an operation fails', function () {})->todo();
    beforeEach(function () {
        // Make sure the text fixtures exist before running each test
        expect(file_exists(TestCase::$uploadsDirectory.'/hello.txt'))->toBeTrue();
        expect(file_exists(TestCase::$uploadsDirectory.'/Second-file.txt'))->toBeTrue();
    });
});

describe("'deleteFile' operation", function () {
    it('should delete a file', function () {
        // Create test file to be deleted
        $fileToDelete = TestCase::$uploadsDirectory.'/delete-me.txt';
        touch($fileToDelete);

        $nanomanager = createNanomanager();

        expect(file_exists($fileToDelete))->toBeTrue();

        $body = json_encode([
            'operationType' => 'deleteFile',
            'parameters' => ['filename' => 'delete-me.txt'],
        ]);
        $result = json_decode($nanomanager->run('POST', $body), true);

        expect($result['data']['success'])->toBeTrue();
        expect(file_exists($fileToDelete))->toBeFalse();
    })->after(function () {
        // Clean up test file in case the test fails
        $fileToDelete = TestCase::$uploadsDirectory.'/delete-me.txt';

        if (file_exists($fileToDelete)) {
            unlink($fileToDelete);
        }
    });

    it('should validate filename before deleting', function () {
        $nanomanager = createNanomanagerSpy()->makePartial();

        $body = json_encode([
            'operationType' => 'deleteFile',
            'parameters' => ['filename' => 'delete-me.txt'],
        ]);

        /** @disregard P1013 */
        $result = json_decode($nanomanager->run('POST', $body), true);

        /** @disregard P1013 */
        $nanomanager->shouldHaveReceived('isValidFilename')
            ->with('delete-me.txt')
        ;
    });

    it('should make sure file exists before deleting', function () {
        $nanomanager = createNanomanager();

        $body = json_encode([
            'operationType' => 'deleteFile',
            'parameters' => ['filename' => 'not-found.txt'],
        ]);
        $result = json_decode($nanomanager->run('POST', $body), true);

        expect($result['data']['success'])->toBeFalse();
    });

    it('should not allow deleting files or directories not managed by Nanomanager', function () {
        $nanomanager = createNanomanager();

        $body = json_encode([
            'operationType' => 'deleteFile',
            'parameters' => ['filename' => 'subdir'],
        ]);
        $result = json_decode($nanomanager->run('POST', $body), true);

        expect($result['data']['success'])->toBeFalse();
        expect(file_exists(TestCase::$uploadsDirectory.'/subdir'))->toBeTrue();

        $body = json_encode([
            'operationType' => 'deleteFile',
            'parameters' => ['filename' => '.htaccess'],
        ]);
        $result = json_decode($nanomanager->run('POST', $body), true);

        expect($result['data']['success'])->toBeFalse();
        expect(file_exists(TestCase::$uploadsDirectory.'/.htaccess'))->toBeTrue();
    });
});

describe("'uploadFile' operation", function () {
    it('should upload a file and save it to disk', function () {
        // Fale filename to upload
        $fileToUpload = TestCase::$uploadsDirectory.'/tmp-upload-me.txt';

        // Create mock so we can test the code without actually uploading a file
        $nanomanager = createNanomanagerMock()
            // Allow mocking `move_uploaded_files()`
            ->shouldAllowMockingProtectedMethods()
            ->makePartial()
        ;

        // We need to mock `move_uploaded_files` because the underlying PHP's
        // `move_uploaded_file()` method requires files to actually be uploaded
        // with POST.
        /** @disregard P1013 */
        $nanomanager
            ->shouldReceive('move_uploaded_file')
            ->with($fileToUpload, TestCase::$uploadsDirectory.'/uploaded-file.txt')
            ->andReturn(true)
        ;

        // Manually mock `$_FILE` contents
        $_FILES = [
            'files' => [
                'name' => [
                    'uploaded-file.txt',
                ],
                'full_path' => [
                    'uploaded-file.txt',
                ],
                'type' => [
                    'text/plain',
                ],
                'tmp_name' => [
                    $fileToUpload,
                ],
                'error' => [
                    UPLOAD_ERR_OK,
                ],
                'size' => [
                    0,
                ],
            ],
        ];

        $body = json_encode([
            'operationType' => 'uploadFile',
            'parameters' => [],
        ]);

        /** @disregard P1013 */
        $result = json_decode($nanomanager->run('POST', $body), true);

        expect($result['data']['uploadedFiles'][0])->toBe('uploaded-file.txt');
    });

    it('should not allow invalid filenames', function () {
        $nanomanager = createNanomanagerSpy()
            ->makePartial()
        ;

        $_FILES = [
            'files' => [
                'name' => [
                    '.htaccess',
                    '../a.txt',
                    ' b.txt',
                ],
                'full_path' => [
                    'uploaded-file.txt',
                    '../a.txt',
                    ' b.txt',
                ],
                'type' => [
                    'text/plain',
                    'text/plain',
                    'text/plain',
                ],
                'tmp_name' => [
                    '/tmp/LOk3EpIU',
                    '/tmp/OgGpaQL6',
                    '/tmp/OJh76HDv',
                ],
                'error' => [
                    UPLOAD_ERR_OK,
                    UPLOAD_ERR_OK,
                    UPLOAD_ERR_OK,
                ],
                'size' => [
                    0,
                    0,
                    0,
                ],
            ],
        ];

        $body = json_encode([
            'operationType' => 'uploadFile',
            'parameters' => [],
        ]);

        /** @disregard P1013 */
        $result = json_decode($nanomanager->run('POST', $body), true);

        expect($result['data']['uploadedFiles'])->toHaveCount(0);
        expect($result['data']['filesWithErrors'])->toHaveCount(3);

        // `move_uploaded_files()` should not be called if the filename is
        // invalid.
        $nanomanager->shouldNotHaveReceived('move_uploaded_file');
    });
});

test("'createMissingDirectory' config option", function () {
    $dir = __DIR__.'/../fixtures/temp-uploads';

    expect(is_dir($dir))->toBeFalse();

    new Nanomanager($dir, '', '', createMissingDirectory: true);

    expect(is_dir($dir))->toBeTrue();

    if (is_dir($dir)) {
        rmdir($dir);
    }
});
