<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;

use App\Services\Helpers\ClientService;
use App\Services\Helpers\GetValoareService;
use App\Services\Helpers\CdsGeocoderService;
use App\Services\Helpers\ToolsService;
use App\Data\AwbData;
use App\Models\User;
use Illuminate\Support\Facades\Log;
use function Symfony\Component\Clock\now;

use \StdClass;

class AwbService
{
    //constructor
    public function __construct(
        private readonly ExpeditiiService $expeditiiService,
    )
    {}
    /**
     * Create a new AWB
     */
    public function create(User $user, array $data): array
    {
        $master_id = session('master_id', $user->expeditor_id);
        $expeditor_cc = session('cc', 0);
        $expeditor_km = session('localitate_km', 0);
        $destinatar_km = ClientService::getKmExteriori(intval($data['swapped'] ?? null) == 1 ? intval($data['expeditor_localitate_id'] ?? 0) : intval($data['destinatar_localitate_id'] ?? 0));
        $mod_plata = session('mod_plata', 0);
        $is_master = ($master_id == 0 || $master_id == $user->expeditor_id);
        $master_id = $is_master ? -1 : $master_id;

        $pcs = collect(session('pcs', []));
        $pcIds = $pcs->reduce(function ($carry, $item) {
            $carry[] = $item['id'];
            return $carry;
        }, []);

        if(intval($data['swapped'] ?? null) == 1) {
            $data['destinatar_email'] = $user->email ?? '';
        } else {
            $data['expeditor_email'] = $user->email ?? '';
        }
        Log::debug('create awb : Data received from UI', ['data' => $data]);
        $awb_data = AwbData::fromUiArray(array_merge($data, [
            'created_by' => $user->id,
            'km_preluare' => intval($data['swapped'] ?? null) == 1 ? $destinatar_km : $expeditor_km,
            'km_livrare' => intval($data['swapped'] ?? null) == 1 ? $expeditor_km : $destinatar_km,
            'mod_plata' => $mod_plata,
        ]), $expeditor_cc);
        Log::debug('create awb : AwbData created from UI array', ['awb_data' => $awb_data]);
        if($awb_data->swapped && !in_array($awb_data->destinatar_id, $pcIds)) {
            throw new \Exception('Destinatarul nu face parte din punctele tale de lucru.');
        }
        if(!$awb_data->swapped && !in_array($awb_data->expeditor_id, $pcIds)) {
            throw new \Exception('Expeditorul nu face parte din punctele tale de lucru.');
        }
        if($awb_data->sms && ToolsService::isValidTelefonNumber($awb_data->destinatar_telefon) === false) {
            throw new \Exception('Numar de telefon destinatar invalid pentru notificare SMS.');
        }
        $valoare = GetValoareService::valoareInitialaClient($awb_data);
        if($valoare->tHt == 0) {
            throw new \Exception('Nu se poate calcula valoarea expedierii pentru datele furnizate.');
        }

        try {
            DB::beginTransaction();    
            $data = $awb_data->toStdClass();
            //nr. expeditie
            $awb = self::generareNrExpeditie();
            $data->awb = $awb;
            $data->created_at = now();
            $data->data_expeditie = date('Y-m-d');
            $data->created_by = $user->id;
            $data->mod_plata = $valoare->mod_plata;
            $data->valoare_exp = $valoare->tExpeditie;
            $data->valoare_km = $valoare->tKm;
            $data->valoare_kg = $valoare->tGreutate;
            $data->valoare_asig = $valoare->tAsigurare + $valoare->tRamburs;
            $data->proc_asig = $valoare->procAsigurare;
            $data->proc_ramburs = $valoare->procRamburs;
            $data->procTva = ToolsService::getProcentTVA();
            $data->moneda = $valoare->moneda;

            $data->valoare_fara_tva = $valoare->tHt;
            $data->valoare_tva = $data->valoare_fara_tva * $data->procTva / 100;
            $data->valoare_tva = round($data->valoare_tva,2);
            
            $client_client = $awb_data->swapped ? "expeditor" : "destinatar";

            Log::debug('create awb : Processing client: '.$client_client);
            //search client
            $newId = ClientService::searchClient($data, $client_client);
            //if $data->{$client_client . "_client_id"} = 0 => $data->{$client_client . "_id"} = 0
            if($data->{$client_client . "_client_id"} > 0) {
                if ($newId == 0) {
                    //not found => insert
                    $data->{$client_client . "_id"} = ClientService::insertClient($data, $client_client, $user->id);
                    Log::debug('create awb : Inserted new client for '.$client_client.' id '.$data->{$client_client . "_id"});
                    CdsGeocoderService::geocode($data->{$client_client . "_id"});
                }
                else {
                    //found
                    $data->{$client_client . "_id"} = $newId;
                    //update clienti : contact, telefon, email
                    Log::debug('create awb : Updating existing client for '.$client_client.' id '.$data->{$client_client . "_id"});
                    ClientService::updateClient($data, $client_client, $user->id);
                    CdsGeocoderService::geocode($data->{$client_client . "_id"}, true);
                }
                //update client_destinatari
                Log::debug('create awb : Updating client_destinatari for '.$client_client.' id '.$data->{$client_client . "_client_id"});
                ClientService::updateClientDestinatar($data, $client_client, $user->id);
            }
            else {
                if($newId == 0){
                    //insert clienti
                    $data->{$client_client . "_id"} = ClientService::insertClient($data, $client_client, $user->id);
                    Log::debug('create awb : Inserted new client2 for '.$client_client.' id '.$data->{$client_client . "_id"});
                } else {
                    //found
                    $data->{$client_client . "_id"} = $newId;
                    //update clienti : contact, telefon, email
                    Log::debug('create awb : Updating existing client2 for '.$client_client.' id '.$data->{$client_client . "_id"});
                    ClientService::updateClient($data, $client_client, $user->id);
                }
                //insert client_destinatari
                $data->{$client_client . "_client_id"} = ClientService::upsertClientDestinatar($data, $client_client, $user->id, $user->expeditor_id);
                Log::debug('create awb : Upsert client_destinatari for '.$client_client.' id '.$data->{$client_client . "_client_id"});
                CdsGeocoderService::geocode($data->{$client_client . "_id"}, true);
            }
            
            //dd($client_client, $data->{$client_client . "_id"});
            //insert awb
            $data->id = self::insertAwb($data);
            //dd($data);
            if($data->id == 0) {
                DB::rollBack();
                throw new \Exception('Eroare creare AWB.');
            }
            //throw new \Exception('Test eroare insert awb');
            DB::commit();
            $awb = $this->expeditiiService->getValues($user, $data->id);
            return AwbData::fromSqlArray($awb[0] ?? [])->toUiArray();
        } catch (\Exception $e) {
            DB::rollBack();
            throw new \Exception('Eroare creare AWB: '.$e->getMessage());
        }


        return [];
    }

    /**
     * Get a single AWB by ID
     */
    public function getById(AwbData $awbData): array
    {
        return $awbData->toUiArray();
    }

    /**
     * Get AWB for editing
     */
    public function getForEdit(AwbData $awbData): array
    {
        return $awbData->toUiArray();
    }

    /**
     * Update an AWB
     */
    public function update(User $user, int $id, array $data): array
    {
        $master_id = session('master_id', $user->expeditor_id);
        $expeditor_cc = session('cc', 0);
        $expeditor_km = session('localitate_km', 0);
        $destinatar_km = ClientService::getKmExteriori(intval($data['swapped'] ?? null) == 1 ? intval($data['expeditor_localitate_id'] ?? 0) : intval($data['destinatar_localitate_id'] ?? 0));
        $mod_plata = session('mod_plata', 0);
        $is_master = ($master_id == 0 || $master_id == $user->expeditor_id);
        $master_id = $is_master ? -1 : $master_id;

        $pcs = collect(session('pcs', []));
        $pcIds = $pcs->reduce(function ($carry, $item) {
            $carry[] = $item['id'];
            return $carry;
        }, []);

        if(intval($data['swapped'] ?? null) == 1) {
            $data['destinatar_email'] = $user->email ?? '';
        } else {
            $data['expeditor_email'] = $user->email ?? '';
        }
        Log::debug('create awb : Data received from UI', ['data' => $data]);
        $awb_data = AwbData::fromUiArray(array_merge($data, [
            'id' => $id,
            'updated_by' => $user->id, 
            'km_preluare' => intval($data['swapped'] ?? null) == 1 ? $destinatar_km : $expeditor_km,
            'km_livrare' => intval($data['swapped'] ?? null) == 1 ? $expeditor_km : $destinatar_km,
            'mod_plata' => $mod_plata,
        ]), $expeditor_cc);
        Log::debug('update awb : AwbData created from UI array', ['awb_data' => $awb_data]);
        if($awb_data->swapped && !in_array($awb_data->destinatar_id, $pcIds)) {
            throw new \Exception('Destinatarul nu face parte din punctele tale de lucru.');
        }
        if(!$awb_data->swapped && !in_array($awb_data->expeditor_id, $pcIds)) {
            throw new \Exception('Expeditorul nu face parte din punctele tale de lucru.');
        }
        if($awb_data->sms && ToolsService::isValidTelefonNumber($awb_data->destinatar_telefon) === false) {
            throw new \Exception('Numar de telefon destinatar invalid pentru notificare SMS.');
        }
        $valoare = GetValoareService::valoareInitialaClient($awb_data);
        if($valoare->tHt == 0) {
            throw new \Exception('Nu se poate calcula valoarea expedierii pentru datele furnizate.');
        }

        try {
            DB::beginTransaction();    
            $data = $awb_data->toStdClass();
            $data->updated_at = now();
            $data->mod_plata = $valoare->mod_plata;
            $data->valoare_exp = $valoare->tExpeditie;
            $data->valoare_km = $valoare->tKm;
            $data->valoare_kg = $valoare->tGreutate;
            $data->valoare_asig = $valoare->tAsigurare + $valoare->tRamburs;
            $data->proc_asig = $valoare->procAsigurare;
            $data->proc_ramburs = $valoare->procRamburs;
            $data->procTva = ToolsService::getProcentTVA();
            $data->moneda = $valoare->moneda;

            $data->valoare_fara_tva = $valoare->tHt;
            $data->valoare_tva = $data->valoare_fara_tva * $data->procTva / 100;
            $data->valoare_tva = round($data->valoare_tva,2);
            
            $client_client = $awb_data->swapped ? "expeditor" : "destinatar";

            Log::debug('update awb : Processing client: '.$client_client);
            //search client
            $newId = ClientService::searchClient($data, $client_client);
            //if $data->{$client_client . "_client_id"} = 0 => $data->{$client_client . "_id"} = 0
            if($data->{$client_client . "_client_id"} > 0) {
                if ($newId == 0) {
                    //not found => insert
                    $data->{$client_client . "_id"} = ClientService::insertClient($data, $client_client, $user->id);
                    Log::debug('update awb : Inserted new client for '.$client_client.' id '.$data->{$client_client . "_id"});
                    CdsGeocoderService::geocode($data->{$client_client . "_id"});
                }
                else {
                    //found
                    $data->{$client_client . "_id"} = $newId;
                    //update clienti : contact, telefon, email
                    Log::debug('update awb : Updating existing client for '.$client_client.' id '.$data->{$client_client . "_id"});
                    ClientService::updateClient($data, $client_client, $user->id);
                    CdsGeocoderService::geocode($data->{$client_client . "_id"}, true);
                }
                //update client_destinatari
                Log::debug('update awb : Updating client_destinatari for '.$client_client.' id '.$data->{$client_client . "_client_id"});
                ClientService::updateClientDestinatar($data, $client_client, $user->id);
            }
            else {
                if($newId == 0){
                    //insert clienti
                    $data->{$client_client . "_id"} = ClientService::insertClient($data, $client_client, $user->id);
                    Log::debug('update awb : Inserted new client2 for '.$client_client.' id '.$data->{$client_client . "_id"});
                } else {
                    //found
                    $data->{$client_client . "_id"} = $newId;
                    //update clienti : contact, telefon, email
                    Log::debug('update awb : Updating existing client2 for '.$client_client.' id '.$data->{$client_client . "_id"});
                    ClientService::updateClient($data, $client_client, $user->id);
                }
                //upsert client_destinatari
                $data->{$client_client . "_client_id"} = ClientService::upsertClientDestinatar($data, $client_client, $user->id, $user->expeditor_id);
                Log::debug('update awb : Upsert client_destinatari for '.$client_client.' id '.$data->{$client_client . "_client_id"});
                CdsGeocoderService::geocode($data->{$client_client . "_id"}, true);
            }
            
            //dd($client_client, $data->{$client_client . "_id"});
            //insert awb
            $affected = self::updateAwb($data);
            if($affected == 0) {
                DB::rollBack();
                throw new \Exception('AWB not found or access denied.');
            }
            //throw new \Exception('Test eroare update awb');
            DB::commit();
            $awb = $this->expeditiiService->getValues($user, $data->id);
            return AwbData::fromSqlArray($awb[0] ?? [])->toUiArray();
        } catch (\Exception $e) {
            DB::rollBack();
            throw new \Exception('Eroare editare AWB: '.$e->getMessage());
        }
        return [];
    }

    /**
     * Delete an AWB
     */
    public function delete(int $id, int $userId): bool
    {
        if($id == 0) throw new \Exception('Awb not found or access denied.');
        try {
            DB::beginTransaction();

            // update anulata flag in expeditii
            $updated = DB::table('exp_prelucrate')
                ->where('cod_expeditie', $id)
                ->update([
                    'anulata' => 1,
                    'deleted_at' => now(),
                    'deleted_by' => $userId,
                ]);

            if(!$updated) {
                throw new \Exception('Failed to delete AWB: '.$id);
            }

            DB::commit();
            return true;
        } catch (\Exception $e) {
            DB::rollBack();
            throw new \Exception($e->getMessage());
        }
        return false;
    }

    /**
     * Estimate shipping cost
     */
    public function price(User $user, array $data): array
    {
        $expeditor_cc = session('cc', 0);
        $expeditor_km = session('localitate_km', 0);
        $destinatar_km = ClientService::getKmExteriori(intval($data['swapped'] ?? null) == 1 ? intval($data['expeditor_localitate_id'] ?? 0) : intval($data['destinatar_localitate_id'] ?? 0));
        $mod_plata = intval(session('mod_plata', 0));

        $pcs = collect(session('pcs', []));
        $pcIds = $pcs->reduce(function ($carry, $item) {
            $carry[] = $item['id'];
            return $carry;
        }, []);

        $awb_data = AwbData::fromUiArray(array_merge($data, [
            'created_by' => $user->id, 
            'km_preluare' => intval($data['swapped'] ?? null) == 1 ? $destinatar_km : $expeditor_km,
            'km_livrare' => intval($data['swapped'] ?? null) == 1 ? $expeditor_km : $destinatar_km,
            'mod_plata' => $mod_plata,
        ]), $expeditor_cc);
        //dd($awb_data);
        if($awb_data->swapped && !in_array($awb_data->destinatar_id, $pcIds)) {
            throw new \Exception('Destinatarul nu face parte din punctele tale de lucru.');
        }
        if(!$awb_data->swapped && !in_array($awb_data->expeditor_id, $pcIds)) {
            throw new \Exception('Expeditorul nu face parte din punctele tale de lucru.');
        }
        //dd($awb_data);
        $valoare = GetValoareService::valoareInitialaClient($awb_data);
        if($valoare->tHt == 0) {
            throw new \Exception('Nu se poate calcula valoarea expedierii pentru datele furnizate.');
        }

        $procTva = ToolsService::getProcentTVA();
        $valoare_fara_tva = $valoare->tHt;
        $valoare_tva = round($valoare->tHt * $procTva / 100, 2);

        return [
            'details' => [
                'tipObj' => config('awb.tip_obj')[$awb_data->tip_obj] ?? 'Unknown',
                'modPlata' => config('awb.mod_plata')[$valoare->mod_plata] ?? 'Unknown',
                'procTva' => $procTva,
                'moneda' => $valoare->moneda,
            ],
            'costBreakdown' => [
                'tExpeditie' => number_format($valoare->tExpeditie, 2, '.', ''),
                'tGreutate' => number_format($valoare->tGreutate, 2, '.', ''),
                'tKm' => number_format($valoare->tKm, 2, '.', ''),
                'tAsigurare' => number_format($valoare->tAsigurare + $valoare->tRamburs, 2, '.', ''),
                'tValoareFaraTva' => number_format($valoare_fara_tva, 2, '.', ''),
                'tValoareTva' => number_format($valoare_tva, 2, '.', ''),
            ],
            'formattedCost' => number_format($valoare_fara_tva + $valoare_tva, 2, '.', '')
        ];
    }

    public static function generareNrExpeditie(): int
    {
        try {
            return DB::table('exp_alocare')->insertGetId([]);
        } catch (\Exception $e) {
            throw new \Exception('Eroare generare nr. awb');
        }
        
	}

    public static function insertAwb(StdClass $data): int
    {
        $insertedId = DB::table('exp_prelucrate')->insertGetId([
            'expeditie' => $data->awb,
            'referire' => 0,
            'data_expeditie' => $data->data_expeditie,
            'created_at' => $data->created_at,
            'created_by' => $data->created_by,
            'operatiune' => 'Colectata',
            'data_op'  => $data->created_at,
            'data'  => $data->created_at,
            'tip_exp' => 0,
            'tip_obj' => $data->tip_obj,
            'plicuri' => $data->tip_obj == 1 ? 1 : 0,
            'colete' => $data->tip_obj == 2 ? $data->piese : 0,
            'paleti' => $data->tip_obj == 3 ? 1 : 0,
            'piese' => $data->piese,
            'greutate' => $data->greutate,
            'volum' => $data->volum,
            'greutate_vol' => $data->greutate_vol,
            'val_greutate' => $data->valoare_kg,
            'km_preluare' => $data->km_preluare,
            'km_livrare' => $data->km_livrare,
            'val_km' => $data->valoare_km,
            'valoare_asigurata' => $data->asigurare,
            'procent_asigurare' => $data->proc_asig,
            'ramburs' => $data->ramburs,
            'tip_plata' => $data->tip_plata,
            'ramburs_procent' => $data->proc_ramburs,
            'val_asig' => $data->valoare_asig,
            'mod_plata' => $data->mod_plata,
            'valoare_expeditie' => $data->valoare_exp,
            'valoare_totala_expeditie' => round($data->valoare_exp + $data->valoare_km + $data->valoare_kg + $data->valoare_asig, 2),
            'tva' => $data->valoare_tva,
            'procTva' => $data->procTva,
            'moneda' => $data->moneda,
            'ret_nt' => $data->ret_nt,
            'ret_doc' => $data->ret_doc,
            'ret_amb' => $data->ret_amb,
            'ret_colet' => $data->ret_colet,
            'liv_samb' => $data->liv_samb,
            'liv_sed' => $data->liv_sed,
            'sms' => $data->sms == 1 ? -1 : 0,
            'copen' => $data->copen,
            'observatii' => $data->observatii,
            'detalii_doc' => $data->detalii_doc,
            'swapped' => $data->swapped,
            'src' => 8,
            'expeditor_id' => $data->expeditor_id,
            'expeditor_contact' => $data->expeditor_contact,
            'expeditor_telefon' => $data->expeditor_telefon,
            'expeditor_email' => $data->expeditor_email,
            'destinatar_id' => $data->destinatar_id,
            'destinatar_contact' => $data->destinatar_contact,
            'destinatar_telefon' => $data->destinatar_telefon,
            'destinatar_email' => $data->destinatar_email,
            'platitor_id' => $data->platitor == 2 ? $data->destinatar_id ?? 0 : $data->expeditor_id ?? 0,
            'operator_id' => $data->created_by,              
        ]);
        //istoric expeditie
        return $insertedId;
    }

    private static function updateAwb(StdClass $data): int
    {
        $affected = DB::table('exp_prelucrate')
            ->where('cod_expeditie', $data->id)
            ->update([
                'tip_obj' => $data->tip_obj,
                'plicuri' => $data->tip_obj == 1 ? 1 : 0,
                'colete' => $data->tip_obj == 2 ? $data->piese : 1,
                'paleti' => $data->tip_obj == 3 ? 1 : 0,
                'piese' => $data->piese,
                'greutate' => $data->greutate,
                'volum' => $data->volum,
                'greutate_vol' => $data->greutate_vol,
                'val_greutate' => $data->valoare_kg,
                'km_preluare' => $data->km_preluare,
                'km_livrare' => $data->km_livrare,
                'val_km' => $data->valoare_km,
                'valoare_asigurata' => $data->asigurare,
                'procent_asigurare' => $data->proc_asig,
                'ramburs' => $data->ramburs,
                'tip_plata' => $data->tip_plata,
                'ramburs_procent' => $data->proc_ramburs,
                'val_asig' => $data->valoare_asig,
                'mod_plata' => $data->mod_plata,
                'valoare_expeditie' => $data->valoare_exp,
                'valoare_totala_expeditie' => round($data->valoare_exp + $data->valoare_km + $data->valoare_kg + $data->valoare_asig, 2),
                'tva' => $data->valoare_tva,
                'procTva' => $data->procTva,
                'moneda' => $data->moneda,
                'ret_nt' => $data->ret_nt,
                'ret_doc' => $data->ret_doc,
                'ret_amb' => $data->ret_amb,
                'ret_colet' => $data->ret_colet,
                'liv_samb' => $data->liv_samb,
                'liv_sed' => $data->liv_sed,
                'sms' => $data->sms == 1 ? -1 : 0,
                'copen' => $data->copen,
                'observatii' => $data->observatii,
                'detalii_doc' => $data->detalii_doc,
                'expeditor_id' => $data->expeditor_id,
                'expeditor_contact' => $data->expeditor_contact,
                'expeditor_telefon' => $data->expeditor_telefon,
                'expeditor_email' => $data->expeditor_email,
                'destinatar_id' => $data->destinatar_id,
                'destinatar_contact' => $data->destinatar_contact,
                'destinatar_telefon' => $data->destinatar_telefon,
                'destinatar_email' => $data->destinatar_email,
                'platitor_id' => $data->platitor == 2 ? $data->destinatar_id ?? 0 : $data->expeditor_id ?? 0,
                'data_op'  => now(),
                'operator_id' => $data->updated_by,
                'updated_at' => $data->updated_at,
                'updated_by' => $data->updated_by,
                'anulata' => 0,
                'deleted_by' => 0,
                'deleted_at' => null,
            ]);
        //istoric expeditie
        return $affected;
    }
}
