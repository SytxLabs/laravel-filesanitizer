<?php

namespace SytxLabs\LaravelFileSanitizer\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @method static array process(string $inputPath, ?string $outputPath = null, ?bool $sanitizeAlways = null)
 * @method static array sanitizeAlways(string $inputPath, ?string $outputPath = null)
 * @method static array processUploadedFile(\Illuminate\Http\UploadedFile $file, ?string $outputPath = null, ?bool $sanitizeAlways = null)
 * @method static array processString(string $data, ?string $filenameHint = null, bool|string|null $outputPath = null, bool $sanitizeAlways = false, ?string $mimeType = null)
 * @method static array processBinary(string $binaryData, ?string $filenameHint = null, bool|string|null $outputPath = null, bool $sanitizeAlways = false, ?string $mimeType = null)
 * @method static array processBase64(string $base64Data, ?string $filenameHint = null, bool|string|null $outputPath = null, bool $sanitizeAlways = false, ?string $mimeType = null)
 * @method static bool safe(array $result)
 * @method static array issues(array $result)
 * @method static \SytxLabs\FileSanitizer\FileSanitizer getSanitizer()
 */
class FileSanitizer extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return 'filesanitizer';
    }
}
