<?php

namespace SytxLabs\LaravelFileSanitizer\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Http\UploadedFile;
use SytxLabs\LaravelFileSanitizer\FileSanitizerManager;

class SafeFile implements ValidationRule
{
    public function __construct(protected ?bool $sanitizeAlways = null, protected ?string $message = null)
    {
    }

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (!$value instanceof UploadedFile) {
            $fail($this->message ?? 'The :attribute must be a valid uploaded file.');
            return;
        }

        /** @var FileSanitizerManager $manager */
        $manager = app('filesanitizer');
        $result = $manager->processUploadedFile($value, null, $this->sanitizeAlways);

        if (!$manager->safe($result)) {
            $issues = $manager->issues($result);
            $summary = collect($issues)->map(function ($issue): string {
                if (is_object($issue) && property_exists($issue, 'code')) {
                    return (string) $issue->code;
                }
                if (is_array($issue) && isset($issue['code'])) {
                    return (string) $issue['code'];
                }
                return 'unsafe_content';
            })
                ->filter()
                ->unique()
                ->implode(', ');

            $message = $this->message ?: 'The :attribute contains unsafe content';
            if ($summary !== '') {
                $message .= ' (' . $summary . ')';
            }
            $fail($message . '.');
        }
    }
}
