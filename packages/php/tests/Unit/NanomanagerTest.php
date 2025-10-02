<?php

declare(strict_types=1);

use Nanomanager\Nanomanager;
use Tests\TestCase;

describe(Nanomanager::class, function () {
    it('should allow uploading files', function () {})->todo();
});

test(Nanomanager::class.'::runOperation()', function () {
    $nanomanagerSpy = Mockery::mock(Nanomanager::class, [TestCase::$uploadsDirectory])->makePartial();

    $operations = [];

    $operations[] = [
        'expects' => 'operation_listFiles',
        'operationType' => 'listFiles',
        'parameters' => [],
    ];

    $operations[] = [
        'expects' => 'operation_renameFile',
        'operationType' => 'renameFile',
        'parameters' => [
            'oldName' => 'old.txt',
            'newName' => 'new.txt',
        ],
    ];

    $operations[] = [
        'expects' => 'operation_deleteFile',
        'operationType' => 'deleteFile',
        'parameters' => [
            'filename' => 'delete.txt',
        ],
    ];

    $operations[] = [
        'expects' => 'operation_uploadFile',
        'operationType' => 'uploadFile',
        'parameters' => [],
    ];

    foreach ($operations as $operation) {
        /** @disregard P1013 */
        $nanomanagerSpy->expects($operation['expects']);

        /** @disregard P1013 */
        $nanomanagerSpy->runOperation($operation['operationType'], $operation['parameters']);
    }
});

describe(Nanomanager::class.'::isValidFilename()', function () {
    it('should not allow directory traversal', function () {
        $nanomanager = new Nanomanager(TestCase::$uploadsDirectory);

        // Down
        expect($nanomanager->isValidFilename('subdir/renamed.txt'))->toBeFalse();

        // Up
        expect($nanomanager->isValidFilename('../renamed.txt'))->toBeFalse();

        // Home
        expect($nanomanager->isValidFilename('~/renamed.txt'))->toBeFalse();
    });

    it('should not allow empty filename', function () {
        $nanomanager = new Nanomanager(TestCase::$uploadsDirectory);

        expect($nanomanager->isValidFilename(''))->toBeFalse();
    });

    it('should not allow filename to begin or end with a space', function () {
        $nanomanager = new Nanomanager(TestCase::$uploadsDirectory);

        expect($nanomanager->isValidFilename(' hello.txt'))->toBeFalse();
        expect($nanomanager->isValidFilename('hello.txt '))->toBeFalse();
    });

    it('should not allow dotfiles', function () {
        $nanomanager = new Nanomanager(TestCase::$uploadsDirectory);

        expect($nanomanager->isValidFilename('.htaccess'))->toBeFalse();
    });

    it('should not allow invalid characters', function () {
        $nanomanager = new Nanomanager(TestCase::$uploadsDirectory);

        expect($nanomanager->isValidFilename('.htaccess'))->toBeFalse();

        // Renaming file to `~`
        expect($nanomanager->isValidFilename('~'))->toBeFalse;

        // Renaming file to `<hello.txt`
        expect($nanomanager->isValidFilename('<hello.txt'))->toBeFalse;
    });
});

describe("'listFiles' operation", function () {
    it('should return files in naturally sorted case-insensitive order', function () {
        $nanomanager = new Nanomanager(TestCase::$uploadsDirectory);
        $result = $nanomanager->operation_listFiles();
        $files = $result['data']['files'];
        expect($files)->toHaveCount(5);
        expect($files)->toBe(['1a.txt', '2b.txt', '11c.txt', 'hello.txt', 'Second-file.txt']);
    });

    it('should not return dotfiles', function () {
        $nanomanager = new Nanomanager(TestCase::$uploadsDirectory);
        $result = $nanomanager->operation_listFiles();
        $files = $result['data']['files'];
        expect($files)->not()->toContain('.htaccess');
    });
});

describe("'renameFile' operation", function () {
    it('should rename file', function () {
        $nanomanager = new Nanomanager(TestCase::$uploadsDirectory);
        $result = $nanomanager->operation_renameFile(['oldName' => 'hello.txt', 'newName' => 'renamed.txt']);
        expect($result['data']['newName'])->toBe('renamed.txt');

        expect(file_exists(TestCase::$uploadsDirectory.'/hello.txt'))->toBeFalse();
        expect(file_exists(TestCase::$uploadsDirectory.'/renamed.txt'))->toBeTrue();

        $result = $nanomanager->operation_renameFile(['oldName' => 'renamed.txt', 'newName' => 'hello.txt']);
        expect($result['data']['newName'])->toBe('hello.txt');

        expect(file_exists(TestCase::$uploadsDirectory.'/renamed.txt'))->toBeFalse();
        expect(file_exists(TestCase::$uploadsDirectory.'/hello.txt'))->toBeTrue();
    });
    it("should fail if the original file doesn't exist", function () {
        $nanomanager = new Nanomanager(TestCase::$uploadsDirectory);
        $result = $nanomanager->operation_renameFile(['oldName' => 'not-found.txt', 'newName' => 'renamed.txt']);
        expect($result['data']['newName'])->not()->toBe('renamed.txt');
        expect($result['data']['newName'])->toBe('not-found.txt');
    });
    it('should validate new and old filename before renaming', function () {
        $nanomanagerSpy = Mockery::spy(Nanomanager::class, [TestCase::$uploadsDirectory])->makePartial();

        /** @disregard P1013 */
        $nanomanagerSpy->operation_renameFile(['oldName' => 'hello.txt', 'newName' => 'Second-file.txt']);

        /** @disregard P1013 */
        $nanomanagerSpy->shouldHaveReceived('isValidFilename')
            ->with('hello.txt')
        ;

        /** @disregard P1013 */
        $nanomanagerSpy->shouldHaveReceived('isValidFilename')
            ->with('Second-file.txt')
        ;
    });
    it('should silently fail if both old and new name are identical', function () {
        $nanomanager = new Nanomanager(TestCase::$uploadsDirectory);
        $result = $nanomanager->operation_renameFile(['oldName' => 'hello.txt', 'newName' => 'hello.txt']);
        expect($result['data']['newName'])->toBe('hello.txt');
    });
    it('should not allow renaming to a name that already exists', function () {
        $nanomanager = new Nanomanager(TestCase::$uploadsDirectory);
        $result = $nanomanager->operation_renameFile(['oldName' => 'hello.txt', 'newName' => 'Second-file.txt']);
        expect($result['data']['newName'])->toBe('hello.txt');

        // Prevent renaming to directory
        $result = $nanomanager->operation_renameFile(['oldName' => 'hello.txt', 'newName' => 'subdir']);
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

        $nanomanager = new Nanomanager(TestCase::$uploadsDirectory);

        expect(file_exists($fileToDelete))->toBeTrue();
        $result = $nanomanager->operation_deleteFile(['filename' => 'delete-me.txt']);
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
        $nanomanagerSpy = Mockery::spy(Nanomanager::class, [TestCase::$uploadsDirectory])->makePartial();

        /** @disregard P1013 */
        $nanomanagerSpy->operation_deleteFile(['filename' => 'delete-me.txt']);

        /** @disregard P1013 */
        $nanomanagerSpy->shouldHaveReceived('isValidFilename')
            ->with('delete-me.txt')
        ;
    });

    it('should make sure file exists before deleting', function () {
        $nanomanager = new Nanomanager(TestCase::$uploadsDirectory);

        $result = $nanomanager->operation_deleteFile(['filename' => 'not-found.txt']);
        expect($result['data']['success'])->toBeFalse();
    });

    it('should not allow deleting files or directories not managed by Nanomanager', function () {
        $nanomanager = new Nanomanager(TestCase::$uploadsDirectory);

        $result = $nanomanager->operation_deleteFile(['filename' => 'subdir']);
        expect($result['data']['success'])->toBeFalse();
        expect(file_exists(TestCase::$uploadsDirectory.'/subdir'))->toBeTrue();

        $result = $nanomanager->operation_deleteFile(['filename' => '.htaccess']);
        expect($result['data']['success'])->toBeFalse();
        expect(file_exists(TestCase::$uploadsDirectory.'/.htaccess'))->toBeTrue();
    });
});

describe("'uploadFile' operation", function () {
    it('should upload a file and save it to disk', function () {
        // Fale filename to upload
        $fileToUpload = TestCase::$uploadsDirectory.'/tmp-upload-me.txt';

        // Create mock so we can test the code without actually uploading a file
        $nanomanager = Mockery::mock(Nanomanager::class, [TestCase::$uploadsDirectory])
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

        /** @disregard P1013 */
        $result = $nanomanager->operation_uploadFile();

        expect($result['data']['uploadedFiles'][0])->toBe('uploaded-file.txt');
    });

    it('should not allow invalid filenames', function () {
        $nanomanager = Mockery::spy(Nanomanager::class, [TestCase::$uploadsDirectory])
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

        /** @disregard P1013 */
        $result = $nanomanager->operation_uploadFile();

        trap($result);

        expect($result['data']['uploadedFiles'])->toHaveCount(0);
        expect($result['data']['filesWithErrors'])->toHaveCount(3);

        // `move_uploaded_files()` should not be called if the filename is
        // invalid.
        $nanomanager->shouldNotHaveReceived('move_uploaded_file');
    });
});
