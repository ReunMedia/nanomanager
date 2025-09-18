<?php

declare(strict_types=1);

use Nanomanager\Nanomanager;
use Tests\TestCase;

describe(Nanomanager::class, function () {
    it("should allow uploading files", function () {})->todo();
    it("should allow deleting files", function () {})->todo();
});

describe("'listFiles' operation", function () {
    it("should return files in naturally sorted case-insensitive order", function () {
        $nanomanager = new Nanomanager(TestCase::$uploadsDirectory);
        $result = $nanomanager->operation_listFiles();
        $files = $result["data"]["files"];
        expect($files)->toHaveCount(5);
        expect($files)->toBe(["1a.txt", "2b.txt", "11c.txt", "hello.txt", "Second-file.txt"]);
    });
});

describe("'renameFile' operation", function () {
    it("should rename file", function () {
        $nanomanager = new Nanomanager(TestCase::$uploadsDirectory);
        $result = $nanomanager->operation_renameFile(["oldName" => "hello.txt", "newName" => "renamed.txt"]);
        expect($result["data"]["newName"])->toBe("renamed.txt");

        expect(file_exists(TestCase::$uploadsDirectory."/hello.txt"))->toBeFalse();
        expect(file_exists(TestCase::$uploadsDirectory."/renamed.txt"))->toBeTrue();

        $result = $nanomanager->operation_renameFile(["oldName" => "renamed.txt", "newName" => "hello.txt"]);
        expect($result["data"]["newName"])->toBe("hello.txt");

        expect(file_exists(TestCase::$uploadsDirectory."/renamed.txt"))->toBeFalse();
        expect(file_exists(TestCase::$uploadsDirectory."/hello.txt"))->toBeTrue();
    });
    it("should fail if the original file doesn't exist", function () {
        $nanomanager = new Nanomanager(TestCase::$uploadsDirectory);
        $result = $nanomanager->operation_renameFile(["oldName" => "not-found.txt", "newName" => "renamed.txt"]);
        expect($result["data"]["newName"])->not()->toBe("renamed.txt");
        expect($result["data"]["newName"])->toBe("not-found.txt");
    });
    it("should not allow directory traversal", function () {
        $nanomanager = new Nanomanager(TestCase::$uploadsDirectory);

        // Down
        $result = $nanomanager->operation_renameFile(["oldName" => "hello.txt", "newName" => "subdir/renamed.txt"]);
        expect($result["data"]["newName"])->toBe("hello.txt");
        expect(file_exists(TestCase::$uploadsDirectory."/subdir/hello.txt"))->toBeFalse();

        // Up
        $result = $nanomanager->operation_renameFile(["oldName" => "hello.txt", "newName" => "../renamed.txt"]);
        expect($result["data"]["newName"])->toBe("hello.txt");
        expect(file_exists(TestCase::$uploadsDirectory."/../hello.txt"))->toBeFalse();

        // Home
        $result = $nanomanager->operation_renameFile(["oldName" => "hello.txt", "newName" => "~/renamed.txt"]);
        expect($result["data"]["newName"])->toBe("hello.txt");
    });
    it("should not allow invalid characters in a filename", function () {
        $nanomanager = new Nanomanager(TestCase::$uploadsDirectory);

        // Renaming an empty file
        $result = $nanomanager->operation_renameFile(["oldName" => "", "newName" => "hello.txt"]);
        expect($result["data"]["newName"])->toBe("");

        // Renaming to empty file
        $result = $nanomanager->operation_renameFile(["oldName" => "hello.txt", "newName" => ""]);
        expect($result["data"]["newName"])->toBe("hello.txt");

        // Renaming to begin / end with space
        $result = $nanomanager->operation_renameFile(["oldName" => "hello.txt", "newName" => " hello.txt"]);
        expect($result["data"]["newName"])->toBe("hello.txt");
        $result = $nanomanager->operation_renameFile(["oldName" => "hello.txt", "newName" => "hello.txt "]);
        expect($result["data"]["newName"])->toBe("hello.txt");

        // Renaming file to `.htaccess`
        $result = $nanomanager->operation_renameFile(["oldName" => "hello.txt", "newName" => ".htaccess"]);
        expect($result["data"]["newName"])->toBe("hello.txt");

        // Renaming file to `~`
        $result = $nanomanager->operation_renameFile(["oldName" => "hello.txt", "newName" => "~"]);
        expect($result["data"]["newName"])->toBe("hello.txt");

        // Renaming file to `<hello.txt`
        $result = $nanomanager->operation_renameFile(["oldName" => "hello.txt", "newName" => "<hello.txt"]);
        expect($result["data"]["newName"])->toBe("hello.txt");
    });
    it("should silently fail if both old and new name are identical", function () {
        $nanomanager = new Nanomanager(TestCase::$uploadsDirectory);
        $result = $nanomanager->operation_renameFile(["oldName" => "hello.txt", "newName" => "hello.txt"]);
        expect($result["data"]["newName"])->toBe("hello.txt");
    });
    it("should not allow renaming to a name that already exists", function () {
        $nanomanager = new Nanomanager(TestCase::$uploadsDirectory);
        $result = $nanomanager->operation_renameFile(["oldName" => "hello.txt", "newName" => "Second-file.txt"]);
        expect($result["data"]["newName"])->toBe("hello.txt");
    });
    it("should output client-side handleable errors when an operation fails", function () {})->todo();
    beforeEach(function () {
        // Make sure the text fixtures exist before running each test
        expect(file_exists(TestCase::$uploadsDirectory."/hello.txt"))->toBeTrue();
        expect(file_exists(TestCase::$uploadsDirectory."/Second-file.txt"))->toBeTrue();
    });
});
