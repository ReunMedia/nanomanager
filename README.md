# Nano File Manager

Nano File Manager (or Nanomanager for short) is a minimalist PHP file manager.
It can be used to quickly add simple uploads and file management to any PHP
powered website.

> ❗ **Important**
>
> Nanomanager doesn't include any authentication! Therefore it is very important
> to manually secure access to it.

## Features

- List, upload, rename and delete files in a specific folder
- Quickly copy links to files
- Lightweight and performant

## Installation

### Requirements

- [PHP 8.2+](https://www.php.net/supported-versions.php)

### With Composer

```sh
composer require reun/nanomanager
```

### PHAR (single file install)

If you're not using Composer or simply want Nanomanager in a single
self-contained file, you can download the latest PHAR from
[releases](https://github.com/ReunMedia/nanomanager/releases) and put it
anywhere outside your public folder. Then just require it whenever you want to
use it.

```php
require_once "path/to/Nanomanager.phar";
```

## Usage

To use Nanomanager, create a new instance by passing configuration to
constructor and call `run()` which returns a string that can be outputted to
browser.

```php
use Reun\Nanomanager\Nanomanager;

// require_once "path/to/Nanomanager.phar"; // If using PHAR installation

$nanomanager = new Nanomanager(
  "public/uploads", // Path to directory where Nanomanager stores files
  "https://example.com/uploads", // Public URL of the directory
  "https://example.com/admin/nanomanager" // URL to access Nanomanager
);

echo $nanomanager->run();
```

### Framework integration

You can manually pass request method and body to `run()` as optional arguments
to integrate Nanomanager with any existing PHP framework.

```php
$app->any("/admin/nanomanager", function($request, $response) {
  $nanomanager = new Nanomanager\Nanomanager(...);

  $response->getBody()->write($nanomanager->run(
      $request->getMethod(),
      (string) $request->getBody(),
  ));

  return $response;
});
```

## Configuration

### PHP API configuration

PHP API is configured with constructor parameters. Check out
[`Nanomanager.php`](packages/php/src/Nanomanager/Nanomanager.php) for full list
of options.

```php
new Nanomanager(..., createMissingDirectory: true);
```

### Frontend configuration

Frontend is configured with HTML attributes. Check out `Props` interface in
[NanoFileManager.svelte](packages/frontend/src/NanoFileManager.svelte) for full
list of options.

If you're not [embedding
frontend](#embedding-nanomanager-frontend-to-existing-page) yourself, you can
customize frontend with `frontendAttributes` parameter.

```php
new Nanomanager(..., frontendAttributes: 'theme="dark"');
```

## Embedding Nanomanager frontend to existing page

Nanomanager frontend can easily be embedded to any existing HTML page or JS
framework as a custom element.

```html
<nano-file-manager
  api-url="https://example.com/admin/nanomanager"
></nano-file-manager>
```

The custom element is loaded from `nanomanager.umd.cjs` file, which can be found
in `vendor/reun/nanomanager/packages/frontend/dist/nanomanager.umd.cjs` if you
installed Nanomanager with Composer. For PHAR installation, you can copy the
file from [releases](https://github.com/ReunMedia/nanomanager/releases).

### Using `<script>` tag in HTML file

To embed Nanomanager into any HTML page, you need to copy `nanomanager.umd.cjs`
into publicly accessible asset directory and include it as a script tag. It is
recommended to rename the file to include version number to avoid caching
issues.

```html
<head>
  <script type="module" src="/path/to/nanomanager-1.0.0.umd.cjs"></script>
</head>
<body>
  ...
  <nano-file-manager api-url="..."></nano-file-manager>
  ...
</body>
```

### Using `import` in JS

You can `import` Nanomanager in JS to load the custom element. We're using Vue
as an example, but it works with any JS framework or even without one.

```vue
<script setup>
// Load `nanomanager.umd.cjs` from Composer package
import "../vendor/reun/nanomanager/packages/frontend/dist/nanomanager.umd.cjs";

// Using dynamic hostname for easy access in both development and production
const apiUrl = window.location.origin + "/admin/nanomanager";
</script>

<template>
  <nano-file-manager :api-url="apiUrl"></nano-file-manager>
</template>
```

## Additional tips

### Setting URL dynamically

You can utilize `$_SERVER` to set URL dynamically when configuring Nanomanager.
This is useful for development and testing.

> ⚠️ **Warning!**
>
> It is recommended to set URL manually in production instead of relying on
> `$_SERVER` global. This is also illustrated in the example below.

```php
$proto = ($_SERVER['HTTPS'] ?? false) ? 'https' : 'http';
$host = is_string($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : '';
$urlBase = "{$proto}://{$host}";

// Override dynamic URL in production
if($isProduction) {
  $urlBase = "https://example.com";
}

$nanomanager = new Nanomanager(
    $uploadsDir,
    "{$urlBase}/uploads",
    "{$urlBase}/admin/nanomanager",
);
```
