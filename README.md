# Nano File Manager

Nano File Manager (or Nanomanager for short) is a minimalist file manager
delivered in a single PHP (PHAR) file. It can be used to quickly add simple
upload and file management for any PHP powered website.

Nanomanager is lightweight (the whole package is under 50KB with frontend under
20KB gzipped) and doesn't contain any unnecessary bells and whistles besides
core functionality.

> ❗ **Important**
>
> Nanomanager doesn't include any authentication and therefore it is very
> important to manually secure access to it.

## Features

- List, upload, rename and delete files in a specific folder
- Quickly copy links to files
- Lightweight and performant

## Installation

### Requirements

- [PHP 8.2+](https://www.php.net/supported-versions.php)

### Install Via PHAR

To install Nanomanager, download the latest PHAR from
[releases](https://github.com/ReunMedia/nanomanager/releases) and put it
anywhere outside your public folder.

## Usage

### Simple Example

```php
require_once "path/to/Nanomanager.phar";

$nanomanager = new Nanomanager\Nanomanager(
  "public/uploads", // Path to directory where Nanomanager stores files
  "https://example.com/uploads", // Public URL of the directory
  "https://example.com/admin/nanomanager" // URL to access Nanomanager
);
echo $nanomanager->run(
    $_SERVER['REQUEST_METHOD'],
    file_get_contents('php://input')
);
```

### Framework Integration

```php
$app->get("/admin/nanomanager", function($request, $response) {
  require_once "path/to/Nanomanager.phar";

  $nanomanager = new Nanomanager\Nanomanager(
    "public/uploads",
    "https://example.com/uploads",
    "https://example.com/admin/nanomanager"
  );
  $nanomanagerOutput = $nanomanager->run(
    $request->getMethod(),
    (string) $request->getBody(),
  );

  $response->getBody()->write($nanomanagerOutput);
  return $response;
});
```
