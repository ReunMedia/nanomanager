<?php

declare(strict_types=1);

use Nanomanager\Nanomanager;
use Tests\TestCase;

describe(Nanomanager::class, function () {
    it("should display a list of files in the selected folder", function () {
        $nanomanager = new Nanomanager(TestCase::$uploadsDirectory);
        $result = $nanomanager->operation_listFiles();
        $files = $result["data"]["files"];
        trap($files);
        expect($files)->toHaveCount(1);
        expect($files[0])->toBe("hello.txt");
    })->wip();
    it("should allow uploading files", function () {})->todo();
    it("should allow deleting files", function () {})->todo();
    it("should allow renaming files", function () {})->todo();
});
