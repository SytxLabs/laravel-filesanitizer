<?php

namespace SytxLabs\LaravelFileSanitizer;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\ServiceProvider;
use RuntimeException;
use SytxLabs\FileSanitizer\FileSanitizer as BaseFileSanitizer;

class FileSanitizerServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__ . '/../config/filesanitizer.php', 'filesanitizer');
        $this->app->singleton(BaseFileSanitizer::class, fn () => new BaseFileSanitizer());
        $this->app->singleton('filesanitizer', fn ($app) => new FileSanitizerManager($app->make(BaseFileSanitizer::class), (array) $app['config']->get('filesanitizer', [])));
        $this->app->alias('filesanitizer', FileSanitizerManager::class);
    }

    public function boot(): void
    {
        $this->publishes([__DIR__ . '/../config/filesanitizer.php' => config_path('filesanitizer.php')], 'filesanitizer-config');

        Validator::extend('safe_file', function (string $attribute, mixed $value): bool {
            if (!$value instanceof UploadedFile) {
                return false;
            }
            /** @var FileSanitizerManager $manager */
            $manager = $this->app->make('filesanitizer');
            return $manager->safe($manager->processUploadedFile($value));
        });

        Validator::replacer('safe_file', fn (string $message, string $attribute): string => str_replace(':attribute', $attribute, $message ?: 'The :attribute contains unsafe content.'));

        if (method_exists(UploadedFile::class, 'macro')) {
            UploadedFile::macro('sanitize', function (?string $targetPath = null, ?bool $sanitizeAlways = null, ?string $diskName = null) {
                /** @var UploadedFile $this */
                $manager = app(FileSanitizerManager::class);
                $sourcePath = $this->getRealPath();
                if ($sourcePath === false) {
                    throw new RuntimeException('Uploaded file has no readable temporary path.');
                }
                $extension = $this->getClientOriginalExtension();
                if ($targetPath === null) {
                    $targetPath = tempnam(sys_get_temp_dir(), 'san_');
                    if ($targetPath === false) {
                        throw new RuntimeException('Unable to create temporary file for sanitized upload.');
                    }
                    if ($extension !== '') {
                        $renamed = $targetPath . '.' . $extension;
                        if (! @rename($targetPath, $renamed)) {
                            throw new RuntimeException('Unable to prepare sanitized temporary file.');
                        }
                        $targetPath = $renamed;
                    }
                }
                $result = $manager->process($sourcePath, $targetPath, $sanitizeAlways, $diskName);
                return [
                    'result' => $result,
                    'file' => new UploadedFile($targetPath, $this->getClientOriginalName(), $this->getMimeType() ?: $this->getClientMimeType(), null, true),
                    'path' => $targetPath,
                ];
            });
        }
    }
}
