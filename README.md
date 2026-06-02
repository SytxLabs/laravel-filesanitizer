# Laravel FileSanitizer
[![MIT Licensed](https://img.shields.io/badge/License-MIT-brightgreen.svg?style=flat-square)](LICENSE)
[![Check code style](https://github.com/sytxlabs/laravel-filesanitizer/actions/workflows/code-style.yml/badge.svg?style=flat-square)](https://github.com/sytxlabs/laravel-filesanitizer/actions/workflows/code-style.yml)
[![Tests](https://github.com/sytxlabs/laravel-filesanitizer/actions/workflows/tests.yml/badge.svg?style=flat-square)](https://github.com/sytxlabs/laravel-filesanitizer/actions/workflows/code-style.yml)
[![Latest Version on Packagist](https://poser.pugx.org/sytxlabs/laravel-filesanitizer/v/stable?format=flat-square)](https://packagist.org/packages/sytxlabs/laravel-filesanitizer)
[![Total Downloads](https://poser.pugx.org/sytxlabs/laravel-filesanitizer/downloads?format=flat-square)](https://packagist.org/packages/sytxlabs/laravel-filesanitizer)

Laravel integration for [`sytxlabs/filesanitizer`](https://github.com/SytxLabs/FileSanitizer).

## Requirements

- PHP `^8.1`
- Laravel `10.x`, `11.x`, `12.x`, or `13.x`

## Installation

```bash
composer require sytxlabs/laravel-filesanitizer
php artisan vendor:publish --tag=filesanitizer-config
```

The service provider and the `FileSanitizer` facade alias are registered automatically via Laravel's package discovery.

## Configuration

After publishing, edit `config/filesanitizer.php`:

```php
return [
    // Sanitize every file unconditionally, even if it is already considered safe.
    'sanitize_always' => env('FILESANITIZER_SANITIZE_ALWAYS', false),
];
```

## Usage

### Facade

```php
use SytxLabs\LaravelFileSanitizer\Facades\FileSanitizer;

// Process a file on the local filesystem
$result = FileSanitizer::process(storage_path('app/uploads/file.pdf'));

// Force sanitization regardless of the config setting
$result = FileSanitizer::sanitizeAlways(storage_path('app/uploads/file.pdf'));

// Write the sanitized output to a specific path
$result = FileSanitizer::process(
    storage_path('app/uploads/file.pdf'),
    storage_path('app/clean/file.pdf'),
);

// Process a file stored on a named Laravel Storage disk
$result = FileSanitizer::process('uploads/file.pdf', 'clean/file.pdf', null, 's3');

// Check whether the result is safe and inspect issues
if (! FileSanitizer::safe($result)) {
    foreach (FileSanitizer::issues($result) as $issue) {
        echo is_object($issue) ? $issue->code : ($issue['code'] ?? 'unsafe');
        echo PHP_EOL;
    }
}
```

### Process an UploadedFile directly

```php
use SytxLabs\LaravelFileSanitizer\Facades\FileSanitizer;

$result = FileSanitizer::processUploadedFile($request->file('upload'));

if (! FileSanitizer::safe($result)) {
    // handle unsafe file
}
```

### Process raw string, binary, or Base64 data

```php
use SytxLabs\LaravelFileSanitizer\Facades\FileSanitizer;

// Plain string content
$result = FileSanitizer::processString($content, 'document.pdf');

// Raw binary data
$result = FileSanitizer::processBinary($binaryData, 'image.png');

// Base64-encoded data
$result = FileSanitizer::processBase64($base64Data, 'archive.zip');
```

All three methods share the same signature:

| Parameter         | Type                 | Description                                          |
|-------------------|----------------------|------------------------------------------------------|
| `$data`           | `string`             | The file content                                     |
| `$filenameHint`   | `string\|null`       | Optional filename used for MIME detection            |
| `$outputPath`     | `bool\|string\|null` | Output path, `true` to overwrite in-place, or `null` |
| `$sanitizeAlways` | `bool`               | Force sanitization                                   |
| `$mimeType`       | `string\|null`       | Explicit MIME type hint                              |

### Access the underlying sanitizer

```php
$sanitizer = FileSanitizer::getSanitizer(); // SytxLabs\FileSanitizer\FileSanitizer
```

## Validation rule

### Class-based rule (`SafeFile`)

```php
use SytxLabs\LaravelFileSanitizer\Rules\SafeFile;

// Basic usage
$request->validate([
    'upload' => ['required', 'file', new SafeFile()],
]);

// Force sanitization and use a custom error message
$request->validate([
    'upload' => ['required', 'file', new SafeFile(sanitizeAlways: true, message: 'This file is not allowed.')],
]);
```

| Parameter         | Type           | Default | Description                                 |
|-------------------|----------------|---------|---------------------------------------------|
| `$sanitizeAlways` | `bool\|null`   | `null`  | Override the config `sanitize_always` value |
| `$message`        | `string\|null` | `null`  | Custom validation error message             |

When no custom message is provided the rule reports the detected issue codes, e.g.:
`The upload contains unsafe content (macro_detected, embedded_script).`

### String rule

```php
$request->validate([
    'upload' => ['required', 'file', 'safe_file'],
]);
```

## `UploadedFile` macro

A `sanitize()` macro is registered on `Illuminate\Http\UploadedFile`:

```php
$sanitized = $request->file('upload')->sanitize();

// $sanitized is an array with three keys:
// 'result' => the raw scan result array
// 'file'   => a new UploadedFile pointing to the sanitized temporary file
// 'path'   => absolute path to the sanitized temporary file

if (! FileSanitizer::safe($sanitized['result'])) {
    // handle unsafe file
}

// Store the sanitized file
$sanitized['file']->store('uploads');
```

The macro signature:

```php
$request->file('upload')->sanitize(
    targetPath: null,         // Custom output path; auto-generated temp file when null
    sanitizeAlways: null,     // Override config; null uses the config value
    diskName: null,           // Laravel Storage disk name
);
```
