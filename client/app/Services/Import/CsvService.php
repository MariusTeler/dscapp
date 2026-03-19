<?php
namespace App\Services\Import;

use App\Http\Requests\Import\AwbFieldsValidator;
use App\Http\Requests\Import\DestinatariFieldsValidator;
use App\Models\User;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use App\Services\Helpers\CdsGeocoderService;
use App\Services\Helpers\ToolsService;
use App\Services\Helpers\GetValoareService;
use App\Services\Helpers\LocalitatiService;
use App\Services\Helpers\ClientService;
use App\Services\AwbService;
use App\Data\AwbData;

use Illuminate\Support\Facades\Log;

class CsvService
{
    /**
     * Validate CSV import file
     */
    public static function validateImportAwb(string $fullPath): array
    {
        try {
            if (!file_exists($fullPath)) {
                throw new \Exception('Fisierul CSV nu exista.');
            }

            $handle = fopen($fullPath, 'r');
            if($handle === false) {
                throw new \Exception('Nu s-a putut deschide fisierul CSV.');
            }

            // Skip BOM if present
            $bom = fread($handle, 3);
            if ($bom !== chr(0xEF).chr(0xBB).chr(0xBF)) {
                rewind($handle);
            }

            // Read header row
            $headers = fgetcsv($handle);
            if ($headers === false) {
                fclose($handle);
                throw new \Exception('Fisierul CSV este gol sau invalid.');
            }
        } catch (\Exception $e) {
            throw new \Exception('Nu s-a putut deschide fisierul CSV. ');
        }

        // Validate headers
        if (count($headers) !== count(array_merge(config('awb.import_headers.csv.required'), config('awb.import_headers.csv.optional')))) {
            fclose($handle);
            throw new \Exception(
                'Numarul de coloane nu corespunde cu template-ul. Asteptat: ' .
                count(array_merge(config('awb.import_headers.csv.required'), config('awb.import_headers.csv.optional'))) . ', Gasit: ' . count($headers)
            );
        }

        //Validate max rows
        $rowCount = 1; // Start from 1 (header)
        $maxRows = config('awb.limits.import.awb.csv.max_rows');
        while (($row = fgetcsv($handle)) !== false) {
            $rowCount++;
            if($rowCount > $maxRows) {
                fclose($handle);
                throw new \Exception('Fisierul CSV contine mai mult de ' . $maxRows . ' de linii. Va rugam sa impartiti fisierul in mai multe fisiere cu maximum ' . $maxRows . ' de linii fiecare.');
            }
        }

        // Read and validate data rows
        $validRows = [];
        $errors = [];
        $rowNumber = 1; // Start from 2 (1 is header)
        rewind($handle); // Reset file pointer to the beginning

        while (($row = fgetcsv($handle)) !== false && $rowNumber <= $maxRows + 1) {
            //skip first row
            if($rowNumber == 1) {
                $rowNumber++;
                continue;
            }
            // Skip empty rows
            $rowErrors = [];
            if (count(array_filter($row)) === 0) {
                $errors[] = [
                    'row' => $rowNumber++,
                    'errors' => ['Row is empty'],
                ];
                continue;
            }

            // Map CSV row to validation data
            $rowData = [
                'destinatar' => ToolsService::sSanitizeCleanEdges($row[0] ?? null),
                'judet' => $row[1] ?? null,
                'localitate' => $row[2] ?? null,
                'adresa' => $row[3] ?? null,
                'tip_obj' => isset($row[4]) ? mb_strtolower(trim((string) $row[4])) : null,
                'piese' => $row[5] ?? null,
                'greutate' => $row[6] ?? null,
                'platitor' => isset($row[7]) ? mb_strtolower(ToolsService::sSanitizeCleanEdges($row[7])) : null,
                'contact' => $row[8] ?? null,
                'telefon' => $row[9] ?? null,
                'email' => $row[10] ?? null,
                'asigurare' => $row[11] ?? null,
                'ramburs' => $row[12] ?? null,
                'tip_plata' => isset($row[13]) ? mb_strtolower(trim((string) $row[13])) : null,
                'ret_nt' => isset($row[14]) ? mb_strtolower(trim((string) $row[14])) : null,
                'ret_doc' => isset($row[15]) ? mb_strtolower(trim((string) $row[15])) : null,
                'ret_amb' => isset($row[16]) ? mb_strtolower(trim((string) $row[16])) : null,
                'ret_colet' => isset($row[17]) ? mb_strtolower(trim((string) $row[17])) : null,
                'sms' => isset($row[18]) ? mb_strtolower(trim((string) $row[18])) : null,
                'copen' => isset($row[19]) ? mb_strtolower(trim((string) $row[19])) : null,
                'liv_samb' => isset($row[20]) ? mb_strtolower(trim((string) $row[20])) : null,
                'liv_sed' => isset($row[21]) ? mb_strtolower(trim((string) $row[21])) : null,
                'observatii' => ToolsService::sSanitizeCleanEdges($row[22] ?? null),
                'detalii_doc' => ToolsService::sSanitizeCleanEdges($row[23] ?? null),
            ];

            $validator = Validator::make($rowData, AwbFieldsValidator::rules($rowData), AwbFieldsValidator::messages());

            if ($validator->fails()) {
                foreach ($validator->errors()->all() as $error) {
                    $rowErrors[] = $error;
                }
            }

            if (count($rowErrors) > 0) {
                $errors[] = [
                    'row' => $rowNumber,
                    'errors' => $rowErrors,
                ];
            } else {
                $validRows[] = array_merge([$rowNumber], $row);
            }

            $rowNumber++;
        }

        fclose($handle);

        return [
            'columns' => array_merge(['linie'], config('awb.import_headers.csv.required'), config('awb.import_headers.csv.optional')),
            'valid_rows' => $validRows, // Return valid rows
            'total_valid' => count($validRows),
            'total_errors' => count($errors),
            'errors' => $errors, // Return errors
            'total_rows' => $rowNumber - 2,
        ];
    }

    public static function runImportAwb(array $validRows, User $user): array
    {
        $master_id = session('master_id', $user->expeditor_id);
        $expeditor_cc = session('cc', 0);
        $expeditor_km = session('localitate_km', 0);
        $mod_plata = session('mod_plata', 0);
        $is_master = ($master_id == 0 || $master_id == $user->expeditor_id);
        $master_id = $is_master ? -1 : $master_id;

        $expeditor_data = [
            'created_at' => now(),
            'created_by' => $user->id,
            'expeditor_id' => $user->expeditor_id,
            'expeditor_contact' => $user->nume ?? '',
            'expeditor_telefon' => $user->telefon ?? '',
            'expeditor_email' => $user->email ?? '',
            'expeditor_localitate_id' => session('localitate_id', 0),
            'km_preluare' => $expeditor_km,
            'mod_plata' => $mod_plata,
        ];

        $errors = [];
        foreach($validRows as $row) {
            $row = [
                'rowNumber' => $row[0] ?? null,
                'destinatar' => ToolsService::sSanitizeCleanEdges($row[1] ?? null),
                'judet' => ToolsService::sSanitizeCleanEdges($row[2] ?? null),
                'localitate' => ToolsService::sSanitizeCleanEdges($row[3] ?? null),
                'adresa' => ToolsService::sSanitizeCleanEdges($row[4] ?? null),
                'tip_obj' => isset($row[5]) ? mb_strtolower(trim((string) $row[5])) : null,
                'piese' => intval($row[6] ?? null) == 0 ? null : intval($row[6]),
                'greutate' => round(floatval($row[7] ?? null), 1) == 0.0 ? null : round(floatval($row[7]), 1),
                'platitor' => isset($row[8]) ? mb_strtolower(ToolsService::sSanitizeCleanEdges($row[8])) : null,
                'contact' => ToolsService::sSanitizeCleanEdges($row[9] ?? null),
                'telefon' => ToolsService::sSanitize($row[10] ?? null),
                'email' => ToolsService::sSanitizeCleanEdges($row[11] ?? null),
                'asigurare' => round(floatval($row[12] ?? null), 2) == 0.00 ? null : round(floatval($row[12]), 2),
                'ramburs' => round(floatval($row[13] ?? null), 2) == 0.00 ? null : round(floatval($row[13]), 2),
                'tip_plata' => isset($row[14]) ? mb_strtolower(trim((string) $row[14])) : null,
                'ret_nt' => isset($row[15]) ? mb_strtolower(trim((string) $row[15])) : null,
                'ret_doc' => isset($row[16]) ? mb_strtolower(trim((string) $row[16])) : null,
                'ret_amb' => isset($row[17]) ? mb_strtolower(trim((string) $row[17])) : null,
                'ret_colet' => isset($row[18]) ? mb_strtolower(trim((string) $row[18])) : null,
                'sms' => isset($row[19]) ? mb_strtolower(trim((string) $row[19])) : null,
                'copen' => isset($row[20]) ? mb_strtolower(trim((string) $row[20])) : null,
                'liv_samb' => isset($row[21]) ? mb_strtolower(trim((string) $row[21])) : null,
                'liv_sed' => isset($row[22]) ? mb_strtolower(trim((string) $row[22])) : null,
                'observatii' => ToolsService::sSanitizeCleanEdges($row[23] ?? null),
                'detalii_doc' => ToolsService::sSanitizeCleanEdges($row[24] ?? null),
            ];
            $destinatar_data = [];
            $import_data = [];
            //process each valid row
            $destinatar_localitate = LocalitatiService::getByLocalitateJudet($row['localitate'] ?? '', $row['judet'] ?? '');
            if(count($destinatar_localitate) == 0) {
                $errors[] = [
                    'row' => $row['rowNumber'] ?? null,
                    'errors' => ['Localitate invalida'],
                ];
                continue;
            }
            $destinatar_data = [
                'destinatar_id' => 0,
                'destinatar_nume' => $row['destinatar'] ?? '',
                'destinatar_contact' => $row['contact'] ?? '',
                'destinatar_telefon' => $row['telefon'] ?? '',
                'destinatar_email' => $row['email'] ?? '',
                'destinatar_adresa' => $row['adresa'] ?? '',
                'destinatar_localitate_id' => $destinatar_localitate['cod_lc'],
                'km_livrare' => $destinatar_localitate['dist_km'],
            ];
            
            $greutate = round(floatval($row['greutate'] ?? 0), 1);
            $tip_obj = mb_strtolower(trim((string) $row['tip_obj'] ?? 'plic'));
            $import_data['tip_obj'] = match($tip_obj) {
                'plic' => 1,
                'colet' => 2,
                'palet' => 3,
                default => $greutate >= 1 ? 2 : 1,
            };
            $import_data['greutate'] = match($import_data['tip_obj']) {
                1 => 0.500,
                2 => $greutate > config('awb.limits.max_greutate_colet') ? config('awb.limits.max_greutate_colet') : $greutate,
                3 => $greutate > config('awb.limits.max_greutate_palet') ? config('awb.limits.max_greutate_palet') : $greutate,
                default => 0,
            };
            $piese = intval($row['piese'] ?? 1);
            $import_data['piese'] = match($import_data['tip_obj']) {
                1 => 1,
                2 => $piese > config('awb.limits.max_piese_colet') ? config('awb.limits.max_piese_colet') : $piese,
                3 => 1,
                default => 1,
            };
            $import_data['platitor'] = isset($row['platitor']) ? match($row['platitor']) {
                'expeditor' => 1,
                'destinatar' => 2,
                default => 1,
            } : 1;
            $asigurare = round(floatval($row['asigurare'] ?? 0), 2);
            $ramburs = round(floatval($row['ramburs'] ?? 0), 2);
            $tip_plata = mb_strtolower(trim((string) $row['tip_plata'] ?? 'cash'));
            $import_data['asigurare'] = $asigurare > config('awb.limits.max_asigurare') ? config('awb.limits.max_asigurare') : $asigurare;
            $import_data['ramburs'] = $ramburs > config('awb.limits.max_ramburs') ? config('awb.limits.max_ramburs') : $ramburs;
            $import_data['tip_plata'] = $import_data['ramburs'] > 0 ? match($tip_plata) {
                'cash' => 0,
                'bo' => 1,
                'cec' => 2,
                'cont' => 3,
                default => $expeditor_cc > 0 ? 3 : 0,
            } : 0;
            if($expeditor_cc > 0 && $import_data['tip_plata'] == 0) {
                $import_data['tip_plata'] = 3; //cont
            }
            $ret_nt = mb_strtolower(trim((string) ($row['ret_nt'] ?? 'nu')));
            $ret_doc = mb_strtolower(trim((string) ($row['ret_doc'] ?? 'nu')));
            $ret_amb = mb_strtolower(trim((string) ($row['ret_amb'] ?? 'nu')));
            $ret_colet = mb_strtolower(trim((string) ($row['ret_colet'] ?? 'nu')));
            $import_data['ret_nt'] = $ret_nt == 'da' ? 1 : 0;
            $import_data['ret_doc'] = $ret_doc == 'da' ? 1 : 0;
            $import_data['ret_amb'] = $ret_amb == 'da' ? 1 : 0;
            $import_data['ret_colet'] = $ret_colet == 'da' ? 1 : 0;
            $sms = mb_strtolower(trim((string) ($row['sms'] ?? 'nu')));
            $copen = mb_strtolower(trim((string) ($row['copen'] ?? 'nu')));
            $import_data['sms'] = $sms == 'da' ? 1 : 0;
            $import_data['copen'] = $copen == 'da' ? 1 : 0;
            $liv_samb = mb_strtolower(trim((string) ($row['liv_samb'] ?? 'nu')));
            $liv_sed = mb_strtolower(trim((string) ($row['liv_sed'] ?? 'nu')));
            $import_data['liv_samb'] = $liv_samb == 'da' ? 1 : 0;
            $import_data['liv_sed'] = $liv_sed == 'da' ? 1 : 0;
            $import_data['observatii'] = ToolsService::sSanitizeCleanEdges($row['observatii'] ?? '');
            $import_data['detalii_doc'] = ToolsService::sSanitizeCleanEdges($row['detalii_doc'] ?? '');

            $awb_data = AwbData::fromUiArray(array_merge($expeditor_data, $destinatar_data, $import_data), $expeditor_cc);
            $valoare = GetValoareService::valoareInitialaClient($awb_data);
            if($valoare->tExpeditie == 0) {
                $errors[] = [
                    'row' => $row['rowNumber'] ?? null,
                    'errors' => ['Nu se poate calcula valoarea expedierii pentru datele furnizate.'],
                ];
                continue;
            }

            try {
                DB::beginTransaction();    
                $data = $awb_data->toStdClass();
                $data->awb = AwbService::generareNrExpeditie();
                $data->mod_plata = $valoare->mod_plata;
                $data->valoare_exp = $valoare->tExpeditie;
                $data->valoare_km = $valoare->tKm;
                $data->valoare_kg = $valoare->tGreutate;
                $data->valoare_asig = $valoare->tAsigurare + $valoare->tRamburs;
                $data->proc_asig = $valoare->procAsigurare;
                $data->proc_ramburs = $valoare->procRamburs;
                $data->procTva = ToolsService::getProcentTVA();
                $data->moneda = $valoare->moneda;

                $data->valoare_fara_tva = $data->valoare_exp + $data->valoare_km + $data->valoare_kg + $data->valoare_asig;
                $data->valoare_fara_tva = round($data->valoare_fara_tva,2);
                $data->valoare_tva = $data->valoare_fara_tva * $data->procTva / 100;
                $data->valoare_tva = round($data->valoare_tva,2);

                $client_client = "destinatar";

                Log::debug('runImportFile : Processing client: '.$client_client);
                //search client
                $newId = ClientService::searchClient($data, $client_client);
                //if $data->{$client_client . "_client_id"} = 0 => $data->{$client_client . "_id"} = 0
                if($newId == 0){
                    //insert clienti
                    $data->{$client_client . "_id"} = ClientService::insertClient($data, $client_client, $user->id);
                    Log::debug('runImportFile : Inserted new client for '.$client_client.' id '.$data->{$client_client . "_id"});
                } else {
                    //found
                    $data->{$client_client . "_id"} = $newId;
                    //update clienti : contact, telefon, email
                    Log::debug('runImportFile : Updating existing client for '.$client_client.' id '.$data->{$client_client . "_id"});
                    ClientService::updateClient($data, $client_client, $user->id);
                }
                //upsert client_destinatari
                $data->{$client_client . "_client_id"} = ClientService::upsertClientDestinatar($data, $client_client, $user->id, $user->expeditor_id);
                Log::debug('runImportFile : Upserted client_destinatari for '.$client_client.' id '.$data->{$client_client . "_client_id"});
                CdsGeocoderService::geocode($data->{$client_client . "_id"}, true);
                
                //dd($client_client, $data->{$client_client . "_id"});
                //insert awb
                $id = AwbService::insertAwb($data);
                DB::commit();
            } catch (\Exception $e) {
                DB::rollBack();
                $errors[] = [
                    'row' => $row['rowNumber'] ?? null,
                    'errors' => ['Eroare creare AWB: '.$e->getMessage()],
                ];
                continue;
            }
        }
        return [
            'total_errors' => count($errors),
            'errors' => $errors, // Return errors
        ];
    }

    public static function validateImportDestinatari(string $fullPath): array
    {
        try {
            if (!file_exists($fullPath)) {
                throw new \Exception('Fisierul CSV nu exista.');
            }

            $handle = fopen($fullPath, 'r');
            if($handle === false) {
                throw new \Exception('Nu s-a putut deschide fisierul CSV.');
            }

            // Skip BOM if present
            $bom = fread($handle, 3);
            if ($bom !== chr(0xEF).chr(0xBB).chr(0xBF)) {
                rewind($handle);
            }

            // Read header row
            $headers = fgetcsv($handle);
            if ($headers === false) {
                fclose($handle);
                throw new \Exception('Fisierul CSV este gol sau invalid.');
            }
        } catch (\Exception $e) {
            throw new \Exception('Nu s-a putut deschide fisierul CSV. ');
        }

        // Validate headers
        if (count($headers) !== count(array_merge(config('awb.import_destinatari_headers.csv.required'), config('awb.import_destinatari_headers.csv.optional')))) {
            fclose($handle);
            throw new \Exception(
                'Numarul de coloane nu corespunde cu template-ul. Asteptat: ' .
                count(config('awb.import_destinatari_headers.csv.required')) . ', Gasit: ' . count($headers)
            );
        }
        //Validate max rows
        $rowCount = 1; // Start from 1 (header)
        $maxRows = config('awb.limits.import.destinatari.csv.max_rows'); // Limit max_rows rows
        while (($row = fgetcsv($handle)) !== false) {
            $rowCount++;
            if($rowCount > $maxRows) {
                fclose($handle);
                throw new \Exception('Fisierul CSV contine mai mult de ' . $maxRows . ' de linii. Va rugam sa impartiti fisierul in mai multe fisiere cu maximum ' . $maxRows . ' de linii fiecare.');
            }
        }
        // Log::debug('validateImportDestinatari : Total rows in file (including header): '.$rowCount);
        // Read and validate data rows
        $validRows = [];
        $errors = [];
        $rowNumber = 1; // Start from 2 (1 is header)
        rewind($handle); // Reset file pointer to the beginning

        while (($row = fgetcsv($handle)) !== false && $rowNumber <= $maxRows + 1) {
            //Log::debug('validateImportDestinatari : Validating row '.$rowNumber, $row);
            //skip first row
            if($rowNumber == 1) {
                $rowNumber++;
                continue;
            }
            // Skip empty rows
            $rowErrors = [];
            if (count(array_filter($row)) === 0) {
                $errors[] = [
                    'row' => $rowNumber++,
                    'errors' => ['Linie goala'],
                ];
                continue;
            }

            // Map CSV row to validation data
            $rowData = [
                'nume' => ToolsService::sSanitizeCleanEdges($row[0] ?? null),
                'judet' => $row[1] ?? null,
                'localitate' => $row[2] ?? null,
                'adresa' => $row[3] ?? null,
                'contact' => $row[4] ?? null,
                'telefon' => $row[5] ?? null,
                'email' => $row[6] ?? null,
            ];

            $validator = Validator::make($rowData, DestinatariFieldsValidator::rules($rowData), DestinatariFieldsValidator::messages());

            if ($validator->fails()) {
                foreach ($validator->errors()->all() as $error) {
                    $rowErrors[] = $error;
                }
            }

            if (count($rowErrors) > 0) {
                $errors[] = [
                    'row' => $rowNumber,
                    'errors' => $rowErrors,
                ];
            } else {
                $validRows[] = array_merge([$rowNumber], $row);
            }

            $rowNumber++;
        }

        fclose($handle);

        return [
            'columns' => array_merge(['linie'], array_merge(config('awb.import_destinatari_headers.csv.required'), config('awb.import_destinatari_headers.csv.optional'))),
            'valid_rows' => $validRows, // Return valid rows
            'total_valid' => count($validRows),
            'total_errors' => count($errors),
            'errors' => $errors, // Return errors
            'total_rows' => $rowNumber - 2,
        ];
    }

    public static function runImportDestinatari(array $validRows, User $user): array
    {
        $master_id = session('master_id', $user->expeditor_id);
        $is_master = ($master_id == 0 || $master_id == $user->expeditor_id);
    
        if(!$is_master) {
            return [
                'total_errors' => count($validRows),
                'errors' => array_map(function($row) {
                    return [
                        'row' => $row[0] ?? null,
                        'errors' => ['Contul dumneavoastra nu are permisiunea de a importa destinatari.'],
                    ];
                }, $validRows),
            ];
        }

        $errors = [];
        foreach($validRows as $row) {
            $row = [
                'rowNumber' => $row[0] ?? null,
                'nume' => ToolsService::sSanitizeCleanEdges($row[1] ?? null),
                'judet' => ToolsService::sSanitizeCleanEdges($row[2] ?? null),
                'localitate' => ToolsService::sSanitizeCleanEdges($row[3] ?? null),
                'adresa' => ToolsService::sSanitizeCleanEdges($row[4] ?? null),
                'contact' => ToolsService::sSanitizeCleanEdges($row[5] ?? null),
                'telefon' => ToolsService::sSanitize($row[6] ?? null),
                'email' => ToolsService::sSanitizeCleanEdges($row[7] ?? null),
            ];

            //process each valid row
            $destinatar_localitate = LocalitatiService::getByLocalitateJudet($row['localitate'] ?? '', $row['judet'] ?? '');
            if(count($destinatar_localitate) == 0) {
                $errors[] = [
                    'row' => $row['rowNumber'] ?? null,
                    'errors' => ['Localitate invalida'],
                ];
                continue;
            }

            try {  
                DB::table('client_destinatari')
                    ->insertGetId([
                        'id_loc' => $destinatar_localitate['cod_lc'],
                        'nume' => $row['nume'],
                        'adresa' => $row['adresa'],
                        'contact' => $row['contact'] ?? '',
                        'telefon' => $row['telefon'] ?? '',
                        'email' => $row['email'] ?? null,
                        'activ' => 1,
                        'id_exp' => $user->expeditor_id,
                        'created_at' => now(),
                        'created_by' => $user->id,
                    ]);
            //dd(DB::getRawQueryLog());
            //DB::disableQueryLog();
            } catch (\Exception $e) {
                $errors[] = [
                    'row' => $row['rowNumber'] ?? null,
                    'errors' => ['Eroare creare destinatar: '.$e->getMessage()],
                ];
                continue;
            }
        }
        return [
            'total_errors' => count($errors),
            'errors' => $errors, // Return errors
        ];
    }
}