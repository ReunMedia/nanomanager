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

### Simple Example

To use Nanomanager, simply create a new instance by passing configuration as
constructor parameters. Then just call `run()` with request method and body. It
returns a string that can be outputted to browser.

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

> ℹ️ **Note**
>
> Nanomanager API is fully configured with constructor parameters. See
> [`Nanomanager.php`](packages/php/src/Nanomanager/Nanomanager.php) for full
> list of options.

### Framework Integration

Since `run()` accepts request method, body and headers as optional arguments and
returns a string, you can easily integrate Nanomanager with any existing PHP
framework. The example below uses Slim Framework, but can be adapted to any PHP
framework.

```php
$app->any("/admin/nanomanager", function($request, $response) {
  $nanomanager = new Nanomanager\Nanomanager(...);

  $nanomanagerOutput = $nanomanager->run(
    $request->getMethod(),
    (string) $request->getBody(),
  );

  $response->getBody()->write($nanomanagerOutput);
  return $response;
});
```

## Embedding Nanomanager frontend as Custom Element

Nanomanager frontend can easily be embedded to any existing HTML page or JS
framework as a custom element.

```html
<nano-file-manager
  api-url="https://example.com/admin/nanomanager"
></nano-file-manager>
```

The custom element is loaded from `nanomanager.umd.cjs` file, which can be found
in `vendor/reun/nanomanager/packages/frontend/dist/nanomanager.umd.cjs` if you
installed Nanomanagere with Composer. For PHAR installation, you can copy the
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

## Additional configuration

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
