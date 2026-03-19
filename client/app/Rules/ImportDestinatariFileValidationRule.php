<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;

class ImportDestinatariFileValidationRule implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! $value instanceof UploadedFile) {
            $fail('The file must be a valid uploaded file.');
            return;
        }

        $extension = strtolower($value->getClientOriginalExtension());

        // Extension check (cheap & fast)
        if ($extension != 'csv') {
            $fail('The file must be a CSV file.');
            return;
        }

        if ($extension === 'csv') {
            if (! $this->isValidCsv($value->getPathname())) {
                $fail('The file is not a valid CSV file.');
            }
        }
    }

    protected function isValidCsv(string $path): bool
    {
        if (! is_readable($path)) return false;

        $handle = fopen($path, 'r');
        if ($handle === false) return false;

        // Detect delimiter
        $delimiter = $this->detectDelimiter($path);

        $headers = fgetcsv($handle, 0, $delimiter);
        fclose($handle);

        if (! is_array($headers) || count($headers) === 0) return false;

        $headers = array_map('strtolower', array_map('trim', $headers));
        //Log::debug('CSV Headers: ' . implode('# ', $headers));

        foreach (config('awb.import_destinatari_headers.csv.required') as $header) {
            if (! in_array($header, $headers, true)) {
                //Log::debug("Missing required CSV header: {$header}");
                return false;
            }
        }

        return true;
    }

    private function detectDelimiter(string $path): string
    {
        $sample = file_get_contents($path, false, null, 0, 1024);

        $commaCount = substr_count($sample, ',');
        $semicolonCount = substr_count($sample, ';');

        return $semicolonCount > $commaCount ? ';' : ',';
    }
}