<?php
namespace App\Services\Export;

class CsvService
{
    public function exportBorderou(array $data, string $filename = 'export.csv', int $preturi = 0)
    {
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"$filename\"",
        ];

        $callback = function () use ($data, $preturi) {
            $handle = fopen('php://output', 'w');

            // Add header row
            fputcsv($handle, config('awb.export_headers.csv.borderou.awb'));

            // Add data rows
            foreach ($data as $row) {
                $row['tip_obj'] = config('awb.tip_obj')[$row['tip_obj'] ?? 0] ?? '';
                $lastCkp = $row['last_ckp'] ?? 0;
                fputcsv($handle, [
                    $row['awb'] ?? '',
                    $row['data_expeditie'] ?? '',
                    $row['expeditor_nume'] ?? '',
                    $row['expeditor_localitate'] ?? '',
                    $row['destinatar_nume'] ?? '',
                    $row['destinatar_localitate'] ?? '',
                    $row['tip_obj'] ?? '',
                    $row['piese'] ?? 1,
                    number_format(round($row['greutate'] ?? 0, 1), 1, '.', ''),
                    intval($row['ret_nt'] ?? 0) == 1 ? 'DA' : 'NU',
                    intval($row['ret_doc'] ?? 0) == 1 ? 'DA' : 'NU',
                    intval($row['copen'] ?? 0) == 1 ? 'DA' : 'NU',    
                    intval($row['ramburs'] ?? 0) > 0 ? 'DA' : 'NU',
                    number_format(round($row['ramburs'] ?? 0, 2), 2, '.', ''),
                    $preturi == 1 ? number_format(round($row['valoare_fara_tva'] ?? 0, 2), 2, '.', '') : '',
                    $row['detalii_doc'] ?? '',
                    $row['observatii'] ?? '',
                    $row['status'] ?? '',
                    $row['data_status'] ?? '',
                    $row['primitor'] ?? '',
                    $lastCkp > 0 ? $lastCkp : '',
                    $lastCkp > 0 ? ($row['centru_last_ckp'] ?? '') : '',
                    $lastCkp > 0 ? ($row['data_last_ckp'] ?? '') : '',
                ]);
            }

            fclose($handle);
        };

        return response()->stream($callback, 200, $headers);
    }

    public function exportPredate(array $data, string $filename = 'export.csv', int $preturi = 0)
    {
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"$filename\"",
        ];

        $callback = function () use ($data, $preturi) {
            $handle = fopen('php://output', 'w');

            // Add header row
            fputcsv($handle, config('awb.export_headers.csv.predate'));

            // Add data rows
            foreach ($data as $row) {
                $row['tip_obj'] = config('awb.tip_obj')[$row['tip_obj'] ?? 0] ?? '';
                $lastCkp = $row['last_ckp'] ?? 0;
                fputcsv($handle, [
                    $row['awb'] ?? '',
                    $row['data_expeditie'] ?? '',
                    $row['expeditor_nume'] ?? '',
                    $row['expeditor_localitate'] ?? '',
                    $row['destinatar_nume'] ?? '',
                    $row['destinatar_localitate'] ?? '',
                    $row['tip_obj'] ?? '',
                    $row['piese'] ?? 1,
                    number_format(round($row['greutate'] ?? 0, 1), 1, '.', ''),
                    intval($row['ret_nt'] ?? 0) == 1 ? 'DA' : 'NU',
                    intval($row['ret_doc'] ?? 0) == 1 ? 'DA' : 'NU',
                    intval($row['copen'] ?? 0) == 1 ? 'DA' : 'NU',  
                    number_format(round($row['asigurare'] ?? 0, 2), 2, '.', ''),
                    number_format(round($row['ramburs'] ?? 0, 2), 2, '.', ''),
                    $preturi == 1 ? number_format(round($row['valoare_fara_tva'] ?? 0, 2), 2, '.', '') : '',
                    $row['detalii_doc'] ?? '',
                    $row['observatii'] ?? '',
                    $row['status'] ?? '',
                    $row['data_status'] ?? '',
                    $row['primitor'] ?? '',
                    $lastCkp > 0 ? $lastCkp : '',
                    $lastCkp > 0 ? ($row['centru_last_ckp'] ?? '') : '',
                    $lastCkp > 0 ? ($row['data_last_ckp'] ?? '') : '',
                ]);
            }

            fclose($handle);
        };

        return response()->stream($callback, 200, $headers);
    }

    public function exportRetururi(array $data, string $filename = 'export.csv', int $preturi = 0)
    {
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"$filename\"",
        ];

        $callback = function () use ($data, $preturi) {
            $handle = fopen('php://output', 'w');

            // Add header row
            fputcsv($handle, config('awb.export_headers.csv.retururi'));

            // Add data rows
            foreach ($data as $row) {
                $row['tip_obj'] = config('awb.tip_obj')[$row['tip_obj'] ?? 0] ?? '';
                $row['tip_exp'] = config('awb.tip_exp')[$row['tip_exp'] ?? -1] ?? '';
                $lastCkp = $row['last_ckp'] ?? 0;
                fputcsv($handle, [
                    $row['tip_exp'] ?? '',
                    $row['referire'] ?? '',
                    $row['awb'] ?? '',
                    $row['expeditor_nume'] ?? '',
                    $row['expeditor_localitate'] ?? '',
                    $row['destinatar_nume'] ?? '',
                    $row['destinatar_localitate'] ?? '',
                    $row['tip_obj'] ?? '',
                    $row['piese'] ?? 1,
                    number_format(round($row['greutate'] ?? 0, 2), 2, '.', ''),
                    $row['data_expeditie'] ?? '',
                    $row['status'] ?? '',
                    $row['data_status'] ?? '',
                    $row['primitor'] ?? '',
                    $lastCkp > 0 ? $lastCkp : '',
                    $lastCkp > 0 ? ($row['centru_last_ckp'] ?? '') : '',
                    $lastCkp > 0 ? ($row['data_last_ckp'] ?? '') : '',
                ]);
            }

            fclose($handle);
        };

        return response()->stream($callback, 200, $headers);
    }
}