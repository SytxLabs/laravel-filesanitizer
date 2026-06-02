<?php

namespace SytxLabs\LaravelFileSanitizer;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use SytxLabs\FileSanitizer\FileSanitizer as BaseFileSanitizer;

class FileSanitizerManager
{
    public function __construct(protected BaseFileSanitizer $sanitizer, protected array $config = [])
    {
    }

    public function getSanitizer(): BaseFileSanitizer
    {
        return $this->sanitizer;
    }

    public function process(string $inputPath, ?string $outputPath = null, ?bool $sanitizeAlways = null, ?string $diskName = null): array
    {
        return $this->run($inputPath, $outputPath, $sanitizeAlways ?? (bool) ($this->config['sanitize_always'] ?? false), $diskName);
    }

    public function sanitizeAlways(string $inputPath, ?string $outputPath = null, ?string $diskName = null): array
    {
        return $this->run($inputPath, $outputPath, true, $diskName);
    }

    public function processString(string $data, ?string $filenameHint = null, bool|string|null $outputPath = null, bool $sanitizeAlways = false, ?string $mimeType = null): array
    {
        return $this->getSanitizer()->processString($data, $filenameHint, $outputPath, $sanitizeAlways, $mimeType);
    }

    public function processBinary(string $binaryData, ?string $filenameHint = null, bool|string|null $outputPath = null, bool $sanitizeAlways = false, ?string $mimeType = null): array
    {
        return $this->getSanitizer()->processBinary($binaryData, $filenameHint, $outputPath, $sanitizeAlways, $mimeType);
    }

    public function processBase64(string $base64Data, ?string $filenameHint = null, bool|string|null $outputPath = null, bool $sanitizeAlways = false, ?string $mimeType = null): array
    {
        return $this->getSanitizer()->processBase64($base64Data, $filenameHint, $outputPath, $sanitizeAlways, $mimeType);
    }

    protected function run(string $inputPath, ?string $outputPath, bool $sanitizeAlways, ?string $diskName): array
    {
        if ($diskName === null) {
            return $sanitizeAlways && method_exists($this->getSanitizer(), 'sanitizeAlways') ? $this->getSanitizer()->sanitizeAlways($inputPath, $outputPath) : $this->getSanitizer()->process($inputPath, $outputPath, $sanitizeAlways);
        }
        $disk = Storage::disk($diskName);
        if (! $disk->exists($inputPath)) {
            throw new RuntimeException("Input file [{$inputPath}] was not found on disk [{$diskName}].");
        }
        $tmpInput = tempnam(sys_get_temp_dir(), 'fs_in_');
        $tmpOutput = tempnam(sys_get_temp_dir(), 'fs_out_');
        if ($tmpInput === false || $tmpOutput === false) {
            if ($tmpInput !== false) {
                @unlink($tmpInput);
            }
            if ($tmpOutput !== false) {
                @unlink($tmpOutput);
            }
            throw new RuntimeException('Unable to create temporary files.');
        }
        try {
            file_put_contents($tmpInput, $disk->get($inputPath));
            $result = $sanitizeAlways && method_exists($this->getSanitizer(), 'sanitizeAlways') ? $this->getSanitizer()->sanitizeAlways($tmpInput, $tmpOutput) : $this->getSanitizer()->process($tmpInput, $tmpOutput, $sanitizeAlways);
            if ($outputPath !== null && is_file($tmpOutput)) {
                $disk->put($outputPath, file_get_contents($tmpOutput));
            }
            return $result;
        } finally {
            @unlink($tmpInput);
            @unlink($tmpOutput);
        }
    }

    public function processUploadedFile(UploadedFile $file, ?string $outputPath = null, ?bool $sanitizeAlways = null): array
    {
        return $this->process($file->getRealPath(), $outputPath, $sanitizeAlways);
    }

    public function safe(array $result): bool
    {
        $scan = $result['scan'] ?? null;
        if (is_object($scan) && property_exists($scan, 'safe')) {
            return (bool) $scan->safe;
        }
        if (is_array($scan) && array_key_exists('safe', $scan)) {
            return (bool) $scan['safe'];
        }
        return false;
    }

    public function issues(array $result): array
    {
        $scan = $result['scan'] ?? null;
        $issues = [];
        if (is_object($scan) && property_exists($scan, 'issues') && is_iterable($scan->issues)) {
            foreach ($scan->issues as $issue) {
                $issues[] = $issue;
            }
        }
        if (is_array($scan) && isset($scan['issues']) && is_iterable($scan['issues'])) {
            foreach ($scan['issues'] as $issue) {
                $issues[] = $issue;
            }
        }
        return $issues;
    }
}
