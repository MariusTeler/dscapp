<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Http\UploadedFile;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Illuminate\Support\Facades\Log;

class ImportAwbFileValidationRule implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! $value instanceof UploadedFile) {
            $fail('The file must be a valid uploaded file.');
            return;
        }

        $extension = strtolower($value->getClientOriginalExtension());

        // Extension check (cheap & fast)
        if (! in_array($extension, ['csv', 'xls', 'xlsx'], true)) {
            $fail('The file must be a CSV, XLS, or XLSX file.');
            return;
        }

        if ($extension === 'csv') {
            if (! $this->isValidAwbCsv($value->getPathname())) {
                $fail('The file is not a valid CSV file.');
            }
        } else {
            if (! $this->isValidMaravetXls($value->getPathname())) {
                $fail('The file is not a valid Excel file.');
            }
        }
    }

    protected function isValidAwbCsv(string $path): bool
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

        foreach (config('awb.import_headers.csv.required') as $header) {
            if (! in_array($header, $headers, true)) {
                //Log::debug("Missing required CSV header: {$header}");
                return false;
            }
        }

        return true;
    }

    protected function isValidMaravetXls(string $path): bool
    {
        try {
            $spreadsheet = IOFactory::load($path);
            $sheet = $spreadsheet->getActiveSheet();
            $headers = array_map('strtolower', $sheet->rangeToArray('A1:' . $sheet->getHighestColumn() . '1')[0]);
            $headers = array_map('strtolower', array_map('trim', $headers));

            foreach (array_merge(config('awb.import_headers.xls.maravet.required', ['debugdebug']), config('awb.import_headers.xls.maravet.optional', [])) as $header) {
                if (! in_array($header, $headers, true)) {
                    return false;
                }
            }

            return true;
        } catch (\Throwable $e) {
            return false;
        }
    }

    protected function detectDelimiter(string $path): string
    {
        $sample = file_get_contents($path, false, null, 0, 1024);

        $commaCount = substr_count($sample, ',');
        $semicolonCount = substr_count($sample, ';');

        return $semicolonCount > $commaCount ? ';' : ',';
    }
}