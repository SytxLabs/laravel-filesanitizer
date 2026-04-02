# Laravel FileSanitizer
[![MIT Licensed](https://img.shields.io/badge/License-MIT-brightgreen.svg?style=flat-square)](LICENSE)
[![Check code style](https://github.com/sytxlabs/laravel-filesanitizer/actions/workflows/code-style.yml/badge.svg?style=flat-square)](https://github.com/sytxlabs/laravel-filesanitizer/actions/workflows/code-style.yml)
[![Tests](https://github.com/sytxlabs/laravel-filesanitizer/actions/workflows/tests.yml/badge.svg?style=flat-square)](https://github.com/sytxlabs/laravel-filesanitizer/actions/workflows/code-style.yml)
[![Latest Version on Packagist](https://poser.pugx.org/sytxlabs/laravel-filesanitizer/v/stable?format=flat-square)](https://packagist.org/packages/sytxlabs/laravel-filesanitizer)
[![Total Downloads](https://poser.pugx.org/sytxlabs/laravel-filesanitizer/downloads?format=flat-square)](https://packagist.org/packages/sytxlabs/laravel-filesanitizer)

Laravel integration for [`sytxlabs/filesanitizer`](https://github.com/SytxLabs/FileSanitizer).

The upstream package is installed as `sytxlabs/filesanitizer` and uses the class `SytxLabs\FileSanitizer\FileSanitizer`.

## Installation

```bash
composer require sytxlabs/laravel-filesanitizer
php artisan vendor:publish --tag=filesanitizer-config
```

## Usage

```php
use SytxLabs\LaravelFileSanitizer\Facades\FileSanitizer;

$result = FileSanitizer::process(storage_path('app/uploads/file.pdf'), null, true);

if (! FileSanitizer::safe($result)) {
    foreach (FileSanitizer::issues($result) as $issue) {
        echo is_object($issue) ? $issue->code : ($issue['code'] ?? 'unsafe');
        echo PHP_EOL;
    }
}
```

## Validation rule

```php
use SytxLabs\LaravelFileSanitizer\Rules\SafeFile;

$request->validate([
    'upload' => ['required', 'file', new SafeFile()],
]);
```

Or as a validator string rule:

```php
$request->validate([
    'upload' => ['required', 'file', 'safe_file'],
]);
```

## UploadedFile macro

```php
$result = $request->file('upload')->sanitize();
```

## Config

```php
return [
    'sanitize_always' => env('FILESANITIZER_SANITIZE_ALWAYS', false),
];
```
This config will always sanitize files when set to `true`, even if the file is already considered safe. This can be useful if you want to ensure that all files are sanitized, regardless of their initial safety status.
