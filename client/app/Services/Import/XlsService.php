<?php
namespace App\Services\Import;

use App\Rules\ValidMaravetCodRule;
use App\Http\Requests\Import\AwbFieldsMaravetValidator;
use App\Models\User;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use App\Services\Helpers\ToolsService;
use App\Services\Helpers\GetValoareService;
use App\Services\Helpers\CdsGeocoderService;
use App\Services\Helpers\ClientService;
use App\Services\Helpers\LocalitatiService;
use App\Services\AwbService;
use App\Data\AwbData;

class XlsService
{
    /**
     * Validate XLS import file
     */
    public static function validateImportMaravet(string $fullPath): array
    {
        try {
            if (!file_exists($fullPath)) {
                throw new \Exception('Fisierul XLS nu exista.');
            }

            $handle = fopen($fullPath, 'r');
            if($handle === false) {
                throw new \Exception('Nu s-a putut deschide fisierul XLS.');
            }
            fclose($handle);

            $nbCols = count(config('awb.import_headers.xls.maravet.required'));
            $nbColsNew = count(array_merge(config('awb.import_headers.xls.maravet.required'), config('awb.import_headers.xls.maravet.optional')));
            $firstLine = config('awb.import_headers.xls.maravet.required');
            $firstLineNew = array_merge(config('awb.import_headers.xls.maravet.required'), config('awb.import_headers.xls.maravet.optional'));
            // Read header row
            $inputFileType = IOFactory::identify($fullPath);
			$reader = IOFactory::createReader($inputFileType);
			$reader->setReadDataOnly(true);
			$spreadsheet = $reader->load($fullPath);
			$spreadsheet->setActiveSheetIndex(0);
			$worksheet = $spreadsheet->getActiveSheet();
			$highestRow = $worksheet->getHighestDataRow();
			$highestColumn = $worksheet->getHighestDataColumn();
			$nbhighestColumn = Coordinate::columnIndexFromString($highestColumn);
			if($nbhighestColumn != $nbCols && $nbhighestColumn != $nbColsNew)
				throw new \Exception("fisierul are un numar de coloane (".$nbhighestColumn.") diferit de ".$nbCols." | ".$nbColsNew);

			$colNames = $worksheet->rangeToArray('A1:' . $highestColumn . 1, NULL, TRUE, FALSE);
			if($firstLine != $colNames[0] && $firstLineNew != $colNames[0])
				throw new \Exception("denumirile coloanelor gresite : coloanele bune sunt urmatoarele : ".implode(',',$firstLineNew));
			if(!($nbhighestColumn == $nbCols || $nbhighestColumn == $nbColsNew)) 
                throw new \Exception("numar de coloane gresit : coloanele bune sunt urmatoarele : ".implode(',',$firstLineNew));

            //Validate max rows
            $maxRows = config('awb.limits.import.awb.maravet.max_rows');
            if($highestRow > $maxRows) {
                throw new \Exception('Fisierul XLS contine mai mult de ' . $maxRows . ' de linii. Va rugam sa impartiti fisierul in mai multe fisiere cu maximum ' . $maxRows . ' de linii fiecare.');
            }

            $mexpeditii=[];
            // Read and validate data rows
            $validRows = [];
            $errors = [];
            $rowNumber = 1; // Start from 2 (1 is header)
            $startRow = 1;
            $batchSize = 100;
            
            while ($startRow <= $highestRow) {
                $endRow = min($startRow + $batchSize, $highestRow);
                $dataArray = $worksheet->rangeToArray("A{$startRow}:{$highestColumn}{$endRow}");
                $startRow += $batchSize;
                
                foreach($dataArray as $row) {
                    //skip first row
                    if($rowNumber == 1) {
                        $rowNumber++;
                        continue;
                    }

                    // Skip empty rows
                    if (count(array_filter($row, function ($value) {
                            return $value !== null;
                        })) === 0) {
                        $errors[] = [
                            'row' => $rowNumber++,
                            'errors' => ['Row is empty'],
                        ];
                        continue;
                    }

                    $rowErrors = [];
                    $rowData = [
                        'codbara' => intval($row[0] ?? null),
                        'plic' => $row[1] ?? null,
                        'colet' => $row[2] ?? null,
                        'palet' => $row[3] ?? null,
                        'greutate' => $row[4] ?? null,
                        'clientDest' => ToolsService::sSanitizeCleanEdges($row[5] ?? null),
                        'adresaDest' => $row[6] ?? null,
                        'orasDest' => $row[7] ?? null,
                        'judetDest' => $row[8] ?? null,
                        'centru' => $row[9] ?? null,
                        'perscontactdest' => $row[10] ?? null,
                        'telefondest' => $row[11] ?? null,
                        'observatii' => ToolsService::sSanitizeCleanEdges($row[12] ?? null),
                        'serieclient' => $row[13] ?? null,
                        'rambursnumerar' => $row[14] ?? null,
                        'ramburscontcolector' => $row[15] ?? null,
                        'rambursalttip' => $row[16] ?? null,
                        'platitorexpeditie' => $row[17] ?? null,
                        'livraresambata' => isset($row[18]) ? mb_strtolower(trim((string) $row[18])) : null,
                        'email' => $row[19] ?? null,
                        'frig' => $row[20] ?? null,
                        'continut' => ToolsService::sSanitizeCleanEdges($row[21] ?? null),
                        'valoaredeclarata' => $row[22] ?? null,
                        'extrainfo' => ToolsService::sSanitizeCleanEdges($row[23] ?? null),
                        'largeinfo' => ToolsService::sSanitizeCleanEdges($row[24] ?? null),
                        'codpostaldest' => $row[25] ?? null,
                        'intervallivrare' => $row[26] ?? null,
                        'deschiderecolet' => isset($row[27]) ? mb_strtolower(trim((string) $row[27])) : null,
                        'taradest' => $row[28] ?? null,
                        'emaildest' => $row[29] ?? null,
                        'disclaimer' => $row[30] ?? null,
                        'refexp1' => $row[31] ?? null,
                        'refdest1' => $row[32] ?? null,
                        'refdest2' => $row[33] ?? null,
                        'referintafacturare' => $row[34] ?? null,
                    ];
                    //Log::debug('validateImportXls : Validating row '.$rowNumber, $rowData);
                    $validator = Validator::make($rowData, AwbFieldsMaravetValidator::rules(['mexpeditii' => $mexpeditii, 'rowData' => $rowData]), AwbFieldsMaravetValidator::messages());

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
            }
        } catch (\Exception $e) {
            throw new \Exception($e->getMessage());
        }

        return [
            'columns' => array_merge(['linie'], config('awb.import_headers.xls.maravet.required'), config('awb.import_headers.xls.maravet.optional')),
            'valid_rows' => $validRows, // Return valid rows
            'total_valid' => count($validRows),
            'total_errors' => count($errors),
            'errors' => $errors, // Return errors
            'total_rows' => $rowNumber - 2,
        ];
    }

    public static function runImportMaravet(array $validRows, User $user): array
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
                'codbara' => intval(ToolsService::sSanitize($row[1] ?? null)),
                'plic' => abs(intval($row[2] ?? null)),
                'colet' => abs(intval($row[3] ?? null)),
                'palet' => abs(intval($row[4] ?? null)),
                'greutate' => round(floatval($row[5] ?? null), 1) == 0.0 ? null : round(floatval($row[5]), 1),
                'clientDest' => ToolsService::sSanitizeCleanEdges($row[6] ?? null),
                'adresaDest' => ToolsService::sSanitizeCleanEdges($row[7] ?? null),
                'orasDest' => ToolsService::sSanitizeCleanEdges($row[8] ?? null),
                'judetDest' => ToolsService::sSanitizeCleanEdges($row[9] ?? null),
                'centru' => $row[10] ?? null,
                'perscontactdest' => ToolsService::sSanitizeCleanEdges($row[11] ?? null),
                'telefondest' => ToolsService::sSanitize($row[12] ?? null),
                'observatii' => ToolsService::sSanitizeCleanEdges($row[13] ?? null),
                'serieclient' => $row[14] ?? null,
                'rambursnumerar' => round(floatval($row[15] ?? null), 2) == 0.00 ? null : round(floatval($row[15]), 2),
                'ramburscontcolector' => round(floatval($row[16] ?? null), 2) == 0.00 ? null : round(floatval($row[16]), 2),
                'rambursalttip' => round(floatval($row[17] ?? null), 2) == 0.00 ? null : round(floatval($row[17]), 2),
                'platitorexpeditie' => isset($row[18]) ? mb_strtolower(ToolsService::sSanitizeCleanEdges($row[18])) : null,
                'livraresambata' => isset($row[19]) ? mb_strtolower(trim((string) $row[19])) : null,
                'email' => isset($row[20]) ? mb_strtolower(trim((string) $row[20])) : null,
                'frig' => $row[21] ?? null,
                'continut' => ToolsService::sSanitizeCleanEdges($row[22] ?? null),
                'valoaredeclarata' => round(floatval($row[23] ?? null), 2) == 0.00 ? null : round(floatval($row[23]), 2),
                'extrainfo' => ToolsService::sSanitizeCleanEdges($row[24] ?? null),
                'largeinfo' => ToolsService::sSanitizeCleanEdges($row[25] ?? null),
                'codpostaldest' => $row[26] ?? null,
                'intervallivrare' => $row[27] ?? null,
                'deschiderecolet' => isset($row[28]) ? mb_strtolower(trim((string) $row[28])) : null,
                'taradest' => $row[29] ?? null,
                'emaildest' => isset($row[30]) ? mb_strtolower(trim((string) $row[30])) : null,
                'disclaimer' => $row[31] ?? null,
                'refexp1' => $row[32] ?? null,
                'refdest1' => $row[33] ?? null,
                'refdest2' => $row[34] ?? null,
                'referintafacturare' => $row[35] ?? null,
            ];
            $destinatar_data = [];
            $import_data = [];
            //process each valid row
            $destinatar_localitate = LocalitatiService::getByLocalitateJudet($row['orasDest'] ?? '', $row['judetDest'] ?? '');
            if(count($destinatar_localitate) == 0) {
                $errors[] = [
                    'row' => $row['rowNumber'] ?? null,
                    'errors' => ['Localitatea destinatar nu a fost gasita in baza de date.'],
                ];
                continue;
            }
            $destinatar_data = [
                'destinatar_id' => 0,
                'destinatar_nume' => $row['clientDest'] ?? '',
                'destinatar_contact' => $row['perscontactdest'] ?? '',
                'destinatar_telefon' => $row['telefondest'] ?? '',
                'destinatar_email' => $row['emaildest'] ?? '',
                'destinatar_adresa' => $row['adresaDest'] ?? '',
                'destinatar_localitate_id' => $destinatar_localitate['cod_lc'],
                'km_livrare' => $destinatar_localitate['dist_km'],
            ];
            
            $greutate = round(floatval($row['greutate'] ?? 0), 1);
            $import_data['tip_obj'] = !empty($row['colet'] ?? 0) ? 2 : (!empty($row['palet'] ?? 0) ? 3 : (!empty($row['plic'] ?? 0) ? 1 : ($greutate >= 1 ? 2 : 1)));
            
            $import_data['greutate'] = match($import_data['tip_obj']) {
                1 => 0.5,
                2 => $greutate > config('awb.limits.max_greutate_colet') ? config('awb.limits.max_greutate_colet') : $greutate,
                3 => $greutate > config('awb.limits.max_greutate_palet') ? config('awb.limits.max_greutate_palet') : $greutate,
                default => 0,
            };
            $import_data['piese'] = match($import_data['tip_obj']) {
                1 => 1,
                2 => $row['colet'] > config('awb.limits.max_piese_colet') ? config('awb.limits.max_piese_colet') : $row['colet'],
                3 => 1,
                default => 1,
            };
            /*
            $import_data['platitor'] = isset($row['platitorexpeditie']) ? match($row['platitorexpeditie']) {
                'expeditor' => 1,
                'destinatar' => 2,
                default => 1,
            } : 1;
             */
            $import_data['platitor'] = 1; //maravet specific, platitor este intotdeauna expeditor
            $asigurare = 0; //round(floatval($row['valoaredeclarata'] ?? 0), 2);
            $ramburs = 0; //round(floatval($row['ramburscontcolector'] ?? 0), 2);
            $tip_plata = -1; //mb_strtolower(trim((string) $row['tip_plata'] ?? 'cash'));
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
            //$ret_nt = mb_strtolower(trim((string) ($row['ret_nt'] ?? 'nu')));
            //$ret_doc = mb_strtolower(trim((string) ($row['ret_doc'] ?? 'nu')));
            //$ret_amb = mb_strtolower(trim((string) ($row['ret_amb'] ?? 'nu')));
            //$ret_colet = mb_strtolower(trim((string) ($row['ret_colet'] ?? 'nu')));
            //$import_data['ret_nt'] = $ret_nt == 'da' ? 1 : 0;
            //$import_data['ret_doc'] = $ret_doc == 'da' ? 1 : 0;
            //$import_data['ret_amb'] = $ret_amb == 'da' ? 1 : 0;
            //$import_data['ret_colet'] = $ret_colet == 'da' ? 1 : 0;
            $sms = mb_strtolower(trim((string) ($row['sms'] ?? 'nu')));
            $copen = mb_strtolower(trim((string) ($row['deschiderecolet'] ?? 'nu')));
            $import_data['sms'] = $sms == 'da' ? 1 : 0;
            $import_data['copen'] = $copen == 'da' ? 1 : 0;
            $liv_samb = mb_strtolower(trim((string) ($row['livraresambata'] ?? 'nu')));
            //$liv_sed = mb_strtolower(trim((string) ($row['liv_sed'] ?? 'nu')));
            $import_data['liv_samb'] = $liv_samb == 'da' ? 1 : 0;
            //$import_data['liv_sed'] = $liv_sed == 'da' ? 1 : 0;
            $import_data['observatii'] = ToolsService::sSanitizeCleanEdges($row['observatii'] ?? '');
            $import_data['detalii_doc'] = ToolsService::sSanitizeCleanEdges($row['continut'] ?? '');
            $import_data['extrainfo'] = ToolsService::sSanitizeCleanEdges($row['extrainfo'] ?? '');
            $import_data['largeinfo'] = ToolsService::sSanitizeCleanEdges($row['largeinfo'] ?? '');
            $import_data['awb'] = $row['codbara'];
            //validator maravet : use ValidMaravetCodRule
            $validator = Validator::make(['awb' => $import_data['awb']], [
                'awb' => [new ValidMaravetCodRule()],
            ], [
                'awb.valid_maravet_cod' => 'Cod de bare invalid sau existent.',
            ]);
            if ($validator->fails()) {
                $errors[] = [
                    'row' => $row['rowNumber'] ?? null,
                    'errors' => ['Cod de bare invalid sau existent.'],
                ];
                continue;
            }

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

                Log::debug('runImportXls : Processing client: '.$client_client);
                //search client
                $newId = ClientService::searchClient($data, $client_client);
                //if $data->{$client_client . "_client_id"} = 0 => $data->{$client_client . "_id"} = 0
                if($newId == 0){
                    //insert clienti
                    $data->{$client_client . "_id"} = ClientService::insertClient($data, $client_client, $user->id);
                    Log::debug('runImportXls : Inserted new client for '.$client_client.' id '.$data->{$client_client . "_id"});
                } else {
                    //found
                    $data->{$client_client . "_id"} = $newId;
                    //update clienti : contact, telefon, email
                    Log::debug('runImportXls : Updating existing client for '.$client_client.' id '.$data->{$client_client . "_id"});
                    ClientService::updateClient($data, $client_client, $user->id);
                }
                //upsert client_destinatari
                $data->{$client_client . "_client_id"} = ClientService::upsertClientDestinatar($data, $client_client, $user->id, $user->expeditor_id);
                Log::debug('runImportXls : Upserted client_destinatari for '.$client_client.' id '.$data->{$client_client . "_client_id"});
                CdsGeocoderService::geocode($data->{$client_client . "_id"}, true);
                
                //dd($client_client, $data->{$client_client . "_id"});
                //insert awb
                $id = AwbService::insertAwb($data);
                Log::debug('runImportXls : insert : '.print_r($data, true));
                if(!empty($data->extrainfo))
                    self::insertExtraInfoLargeInfo($user->id, $data->awb, $data->extrainfo ?? '', $data->largeinfo ?? '');
                DB::commit();
            } catch (\Exception $e) {
                DB::rollBack();
                $errors[] = [
                    'row' => $row['rowNumber'] ?? null,
                    'errors' => ['Eroare creare AWB '.$row['codbara'].' : '.$e->getMessage()],
                ];
                continue;
            }
        }
        return [
            'total_errors' => count($errors),
            'errors' => $errors, // Return errors
        ];
    }

    private static function insertExtraInfoLargeInfo($user_id, string $awb, string $extraInfo, string $largeInfo): int
    {
        return DB::table('exp_nc')->insertGetId([
            'expeditie' => $awb,
            'extrainfo' => $extraInfo,
            'largeinfo' => $largeInfo,
            'created_at' => now(),
            'created_by' => $user_id,
        ]);
    }
}