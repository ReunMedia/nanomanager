<?php

declare(strict_types=1);

use Nanomanager\Nanomanager;
use Tests\TestCase;

describe(Nanomanager::class, function () {
    it("should display a list of files in the selected folder", function () {
        $nanomanager = new Nanomanager(TestCase::$uploadsDirectory);
        $files = $nanomanager->listFiles();
        trap($files);
        expect($files)->toHaveCount(1);
    })->wip();
    it("should allow uploading files", function () {})->todo();
    it("should allow deleting files", function () {})->todo();
    it("should allow renaming files", function () {})->todo();
});
