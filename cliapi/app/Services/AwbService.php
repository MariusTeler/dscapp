<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;

use App\Services\Helpers\ClientService;
use App\Services\Helpers\GetValoareService;
use App\Services\Helpers\CdsGeocoderService;
use App\Services\Helpers\ToolsService;
use App\Services\Helpers\LocalitatiService;
use App\Data\AwbData;
use Illuminate\Support\Facades\Log;
use function Symfony\Component\Clock\now;

use \StdClass;

class AwbService
{
    /**
     * Get a single AWB by ID
     */
    public function select($user, int $awb): array
    {
        $pcs = $user['pcIds'] ?? '';
        $pcs = explode(',', $pcs);

        $cond = "";
        if($user['expeditor_id'] == 171350 || count($pcs) > 1) {
			//maravet
			$cond = "IF(ep.swapped = 0, ep.expeditor_id in (select cod_cl from clienti where master = {$user['expeditor_id']}), ep.destinatar_id in (select cod_cl from clienti where master = {$user['expeditor_id']}))";
		}
		else {
			$cond = "IF(ep.swapped = 0, ep.expeditor_id = {$user['expeditor_id']}, ep.destinatar_id = {$user['expeditor_id']})";
		}

        $row =  DB::table('exp_prelucrate as ep')
            ->select('ep.cod_expeditie as id', 'ep.expeditie as awb', 'ep.referire', 'ep.data_expeditie',
            'ep.created_at', 'ep.created_by', 'u.user as created_by_user',
            'ep.updated_at', 'ep.updated_by', 'mu.user as updated_by_user',
            'ep.printed_at', 'ep.printed_by', 'mp.user as printed_by_user',
            'ep.expeditor_id', 'ep.destinatar_id', 'ep.platitor_id', 
            'ep.expeditor_contact', 'ep.expeditor_telefon', 'u.email as expeditor_email', 'ep.destinatar_contact', 'ep.destinatar_telefon', 'ep.destinatar_email',
            'lce.cod_lc as expeditor_localitate_id', 'lcd.cod_lc as destinatar_localitate_id',
            DB::raw('IF(cle.zona_id > 0 and clec.id > 0, clec.id, cee.id) as expeditor_centru_id'), 
            DB::raw('IF(cld.zona_id > 0 and cldc.id > 0, cldc.id, ced.id) as destinatar_centru_id'), 
            'je.cod_jd as expeditor_judet_id', 'jd.cod_jd as destinatar_judet_id',
            'ep.tip_obj', 'ep.piese', 'ep.greutate', 'ep.greutate_vol', 'ep.volum', 'ep.ret_nt', 'ep.ret_doc', 'ep.ret_colet', 'ep.ret_amb', 'ep.liv_sed', 'ep.liv_samb',
            'ep.copen', 'ep.sms', 'ep.km_preluare', 'ep.km_livrare', 'ep.ramburs', 'ep.tip_plata', 'ep.valoare_asigurata as asigurare',
            'ep.ramburs_procent', 'ep.procent_asigurare',
            'ep.val_greutate', 'ep.val_km', 'ep.val_asig', 'ep.valoare_expeditie', 'ep.tva as valoare_tva', 'ep.valoare_totala_expeditie as valoare_fara_tva', 'ep.procTva', 'ep.moneda', 'ep.mod_plata',
            'ep.observatii', 'ep.detalii_doc', 'ep.swapped', 'ep.anulata',
            DB::raw('IF(lce.cod_lc = lcd.cod_lc, 0 , 1) as tip_tarif'),
            'cle.nume as expeditor_nume', 'cle.adresa as expeditor_adresa',
            'lce.nume_lc as expeditor_localitate', 'lce.dist_km as expeditor_localitate_km', 'je.nume_jd as expeditor_judet',
            DB::raw('IF(cle.zona_id > 0 and clec.id > 0, clec.nume, cee.nume) as expeditor_centru'), 
            DB::raw('IF(cle.zona_id > 0 and clec.id > 0, clec.label, cee.label) as expeditor_centru_cod'),
            'cld.nume as destinatar_nume', 'cld.adresa as destinatar_adresa',
            'lcd.nume_lc as destinatar_localitate', 'lcd.dist_km as destinatar_localitate_km', 'jd.nume_jd as destinatar_judet',
            DB::raw('IF(cld.zona_id > 0 and cldc.id > 0, cldc.nume, ced.nume) as destinatar_centru'), 
            DB::raw('IF(cld.zona_id > 0 and cldc.id > 0, cldc.label, ced.label) as destinatar_centru_cod'),
            DB::raw("IF(cld.zona_id > 0 and cldc.id > 0, CONCAT(' - ', cldz.name), '') as destinatar_centru_zona"),
            DB::raw('IF(cld.zona_id > 0 and cldc.id > 0, cldc.rut_bvh, ced.rut_bvh) as destinatar_centru_rut_bvh'),
            DB::raw('IF(cld.zona_id > 0 and cldc.id > 0, cldc.rut_buh, ced.rut_buh) as destinatar_centru_rut_buh'),
            DB::raw('IF(cld.zona_id > 0 and cldc.id > 0, cldc.rut_buc, ced.rut_buc) as destinatar_centru_rut_buc'),
            DB::raw('IF(ep.platitor_id = ep.expeditor_id, cle.nume, cld.nume) as platitor_nume'),
            DB::raw('IF(ep.platitor_id = ep.expeditor_id, IF(cle.zona_id > 0 and clec.id > 0, clec.nume, cee.nume), IF(cld.zona_id > 0 and cldc.id > 0, cldc.nume, ced.nume)) as platitor_centru'),
            DB::raw('IF(ep.platitor_id = ep.expeditor_id, IF(cle.zona_id > 0 and clec.id > 0, clec.label, cee.label), IF(cld.zona_id > 0 and cldc.id > 0, cldc.label, ced.label)) as platitor_centru_id'),
            DB::raw('IF(ep.platitor_id = ep.expeditor_id, lce.nume_lc, lcd.nume_lc) as platitor_localitate'),
            DB::raw('IF(clem.cod_fiscal is NULL, cle.cod_fiscal, clem.cod_fiscal) as expeditor_cui'),
            DB::raw('IF(clem.reg_com is NULL, cle.reg_com, clem.reg_com) as expeditor_j'),
            'enc.id as nc_id', 'enc.extrainfo', 'enc.largeinfo', 'ecf.folder',
            'ep.idfact', 'ddd.factura_id', 'ep.last_ckp', 'ep.borderou_id', 
            DB::raw('CASE WHEN ep.idfact > 0 or ddd.factura_id > 0 or ep.last_ckp > 0 or ep.borderou_id > 0 THEN 0 ELSE 1 END as can_update'),
            )
            ->leftJoin('clienti as cle', 'ep.expeditor_id', '=', 'cle.cod_cl')
            ->leftJoin('zones as clez', 'clez.id', '=', 'cle.zona_id')
            ->leftJoin('centre as clec', 'clec.id', '=', 'clez.centru_id')
            ->leftJoin('clienti as clem', 'clem.cod_cl', '=', 'cle.master')
            ->leftJoin('localitati as lce', 'lce.cod_lc', '=', 'cle.cod_lc')
            ->leftJoin('centre as cee', 'cee.id', '=', 'lce.cod_centru')
            ->leftJoin('judete as je', 'je.cod_jd', '=', 'lce.cod_jd')
            ->leftJoin('clienti as cld', 'ep.destinatar_id', '=', 'cld.cod_cl')
            ->leftJoin('zones as cldz', 'cldz.id', '=', 'cld.zona_id')
            ->leftJoin('centre as cldc', 'cldc.id', '=', 'cldz.centru_id')
            ->leftJoin('localitati as lcd', 'lcd.cod_lc', '=', 'cld.cod_lc')
            ->leftJoin('centre as ced', 'ced.id', '=', 'lcd.cod_centru')
            ->leftJoin('judete as jd', 'jd.cod_jd', '=', 'lcd.cod_jd')
            ->leftJoin('clienti as clp', 'ep.platitor_id', '=', 'clp.cod_cl')
            ->leftJoin('exp_nc as enc', 'ep.expeditie', '=', 'enc.expeditie')
            ->leftJoin('exp_confirmari as ecf', 'ep.expeditie', '=', 'ecf.expeditie')
            ->leftJoin('users as u', 'u.id', '=', 'ep.created_by')
            ->leftJoin('users as mu', 'mu.id', '=', 'ep.updated_by')
            ->leftJoin('users as mp', 'mp.id', '=', 'ep.printed_by')
            ->leftJoin(DB::raw("
                ( select dee.expeditie as expeditie, dfa.id as factura_id
                    from decont_expeditii dee
                    inner join decont_facturi dfa on dee.factura_id = dfa.id
                    where dee.anulata = 0 and dfa.anulata = 0
                    limit 1
                ) as ddd"), 'ep.expeditie', '=', 'ddd.expeditie')
            ->where('ep.anulata', 0)
            ->where('ep.tip_exp', 0)
            ->whereRaw($cond)
            ->where('ep.expeditie', $awb)
            ->first();

            return $row ? (array)$row : [];
    }

    /**
     * Create a new AWB
     */
    public function create($user, array $data): array
    {
        $expeditor_cc = $user['expeditor_cc'] ?? 0;
        $expeditor_km = $user['expeditor_localitate_km'] ?? 0;
        $expeditor_localitate_id = $user['expeditor_localitate_id'] ?? 0;

        $destinatar_localitate_obj = LocalitatiService::getByLocalitateJudet($data['destinatar_judet'] ?? '', $data['destinatar_localitate'] ?? '');
        if(count($destinatar_localitate_obj) == 0 || !isset($destinatar_localitate_obj['dist_km'])) {
            throw new \Exception('Localitatea destinatar nu a fost gasita in baza de date.');
        }
        $destinatar_km = $destinatar_localitate_obj['dist_km'] ?? 0;
        $destinatar_localitate_id = $destinatar_localitate_obj['cod_lc'] ?? 0;
        $mod_plata = $user['expeditor_mod_plata'] ?? 0;

        $awb_data = AwbData::fromUiArray(array_merge($data, [
            'created_by' => $user['id'],
            'expeditor_id' => $user['expeditor_id'],
            'expeditor_nume' => $user['expeditor_nume'] ?? '',
            'expeditor_contact' => $user['expeditor_contact'] ?? '',
            'expeditor_telefon' => $user['expeditor_telefon'] ?? '',
            'expeditor_email' => $user['expeditor_email'] ?? '',
            'km_preluare' => $expeditor_km,
            'expeditor_localitate_km' => $expeditor_km,
            'km_livrare' => $destinatar_km,
            'destinatar_localitate_km' => $destinatar_km,
            'mod_plata' => $mod_plata,
            'expeditor_localitate_id' => $expeditor_localitate_id,
            'destinatar_localitate_id' => $destinatar_localitate_id,
            'procTva' => ToolsService::getProcentTVA(),
        ]), $expeditor_cc);
        
        $valoare = GetValoareService::valoareInitialaClient($awb_data);
        if($valoare->tExpeditie == 0) {
            throw new \Exception('Nu se poate calcula valoarea transportului pentru datele furnizate.');
        }

        try {
            DB::beginTransaction();    
            $data = $awb_data->toStdClass();
            //nr. expeditie
            $awb = self::generareNrExpeditie();
            $data->awb = $awb;
            $data->created_at = now();
            $data->data_expeditie = date('Y-m-d');
            $data->mod_plata = $valoare->mod_plata;
            $data->valoare_exp = $valoare->tExpeditie;
            $data->valoare_km = $valoare->tKm;
            $data->valoare_g = $valoare->tGreutate;
            $data->valoare_asig = $valoare->tAsigurare + $valoare->tRamburs;
            $data->proc_asig = $valoare->procAsigurare;
            $data->proc_ramburs = $valoare->procRamburs;
            $data->procTva = ToolsService::getProcentTVA();
            $data->moneda = $valoare->moneda;

            $data->valoare_fara_tva = $data->valoare_exp + $data->valoare_km + $data->valoare_g + $data->valoare_asig;
            $data->valoare_fara_tva = round($data->valoare_fara_tva,2);
            $data->valoare_tva = $data->valoare_fara_tva * $data->procTva / 100;
            $data->valoare_tva = round($data->valoare_tva,2);
            
            $client_client = $awb_data->swapped ? "expeditor" : "destinatar";

            Log::debug('create awb : Processing client: '.$client_client);
            //search client
            $newId = ClientService::searchClient($data, $client_client);
            Log::debug('create awb : Search client result for '.$client_client.' id '.$newId);
            //if $data->{$client_client . "_client_id"} = 0 => $data->{$client_client . "_id"} = 0
            if($data->{$client_client . "_client_id"} > 0) {
                if ($newId == 0) {
                    //not found => insert
                    $data->{$client_client . "_id"} = ClientService::insertClient($data, $client_client, $user['id']);
                    Log::debug('create awb : Inserted new client for '.$client_client.' id '.$data->{$client_client . "_id"});
                    CdsGeocoderService::geocode($data->{$client_client . "_id"});
                }
                else {
                    //found
                    $data->{$client_client . "_id"} = $newId;
                    //update clienti : contact, telefon, email
                    Log::debug('create awb : Updating existing client for '.$client_client.' id '.$data->{$client_client . "_id"});
                    ClientService::updateClient($data, $client_client, $user['id']);
                    CdsGeocoderService::geocode($data->{$client_client . "_id"}, true);
                }
                //update client_destinatari
                Log::debug('create awb : Updating client_destinatari for '.$client_client.' id '.$data->{$client_client . "_client_id"});
                ClientService::updateClientDestinatar($data, $client_client, $user['id']);
            }
            else {
                if($newId == 0){
                    //insert clienti
                    $data->{$client_client . "_id"} = ClientService::insertClient($data, $client_client, $user['id']);
                    Log::debug('create awb : Inserted new client2 for '.$client_client.' id '.$data->{$client_client . "_id"});
                } else {
                    //found
                    $data->{$client_client . "_id"} = $newId;
                    //update clienti : contact, telefon, email
                    Log::debug('create awb : Updating existing client2 for '.$client_client.' id '.$data->{$client_client . "_id"});
                    ClientService::updateClient($data, $client_client, $user['id']);
                }
                //insert client_destinatari
                $data->{$client_client . "_client_id"} = ClientService::upsertClientDestinatar($data, $client_client, $user['id'], $user['expeditor_id']);
                Log::debug('create awb : Upsert client_destinatari for '.$client_client.' id '.$data->{$client_client . "_client_id"});
                CdsGeocoderService::geocode($data->{$client_client . "_id"}, true);
            }
            
            //dd($client_client, $data->{$client_client . "_id"});
            //insert awb
            self::insertAwb($data);
            //dd($data);
            //throw new \Exception('Test eroare insert awb');
            DB::commit();
            return [
                'awb' => $awb,
                'totalNet' => round($data->valoare_fara_tva + $data->valoare_tva, 2),
            ];
        } catch (\Exception $e) {
            DB::rollBack();
            throw new \Exception('Eroare creare AWB: '.$e->getMessage());
        }


        return [];
    }

    /**
     * Delete an AWB
     */
    public function delete($user, int $id): bool
    {
        if($id == 0) throw new \Exception('Awb not found or access denied.');
        try {
            // update anulata flag in expeditii
            $updated = DB::table('exp_prelucrate')
                ->where('cod_expeditie', $id)
                ->update([
                    'anulata' => 1,
                    'deleted_at' => now(),
                    'deleted_by' => $user['id'],
                ]);

            if(!$updated) {
                throw new \Exception('Failed to delete AWB: '.$id);
            }

            return true;
        } catch (\Exception $e) {
            throw new \Exception($e->getMessage());
        }
        return false;
    }

    /**
     * Estimate shipping cost
     */
    public function price($user, array $data): array
    {
        $expeditor_cc = $user['expeditor_cc'] ?? 0;
        $expeditor_km = $user['expeditor_localitate_km'] ?? 0;
        $expeditor_localitate_id = $user['expeditor_localitate_id'] ?? 0;

        $destinatar_localitate_obj = LocalitatiService::getByLocalitateJudet($data['judet'] ?? '', $data['localitate'] ?? '');
        if(count($destinatar_localitate_obj) == 0 || !isset($destinatar_localitate_obj['dist_km'])) {
            throw new \Exception('Localitatea destinatar nu a fost gasita in baza de date.');
        }
        $destinatar_km = $destinatar_localitate_obj['dist_km'] ?? 0;
        $destinatar_localitate_id = $destinatar_localitate_obj['cod_lc'] ?? 0;
        $mod_plata = $user['expeditor_mod_plata'] ?? 0;

        $awb_data = AwbData::fromUiArray(array_merge($data, [
            'created_by' => $user['id'],
            'expeditor_id' => $user['expeditor_id'],
            'km_preluare' => $expeditor_km,
            'expeditor_localitate_km' => $expeditor_km,
            'km_livrare' => $destinatar_km,
            'destinatar_localitate_km' => $destinatar_km,
            'mod_plata' => $mod_plata,
            'expeditor_localitate_id' => $expeditor_localitate_id,
            'destinatar_localitate_id' => $destinatar_localitate_id,
        ]), $expeditor_cc);
        
        $valoare = GetValoareService::valoareInitialaClient($awb_data);
        if($valoare->tExpeditie == 0) {
            throw new \Exception('Nu se poate calcula valoarea transportului pentru datele furnizate.');
        }

        $procTva = ToolsService::getProcentTVA();
        $valoare_fara_tva = round($valoare->tExpeditie + $valoare->tKm + $valoare->tGreutate + $valoare->tAsigurare + $valoare->tRamburs, 2);
        $valoare_tva = round($valoare_fara_tva * $procTva / 100, 2);

        return [
                'baza' => number_format($valoare->tExpeditie, 2, '.', ''),
                'costKm' => number_format($valoare->tKm, 2, '.', ''),
                'costGreutate' => number_format($valoare->tGreutate, 2, '.', ''),
                'costAsigurare' => number_format($valoare->tAsigurare + $valoare->tRamburs, 2, '.', ''),
                'esteTarifStandard' => $valoare->isTarifLista,
                'tva' => number_format($valoare_tva, 2, '.', ''),
                'totalNet' => number_format($valoare_fara_tva, 2, '.', ''),
                
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
            'val_greutate' => $data->valoare_g,
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
            'valoare_totala_expeditie' => round($data->valoare_exp + $data->valoare_km + $data->valoare_g + $data->valoare_asig, 2),
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
            'src' => 88,
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

    public function trackAwb($user, int $awb): array
	{
		$pcs = $user['pcIds'] ?? [];
        $pcs = is_array($pcs) ? $pcs : explode(',', $pcs);

        $cond = "";
        if($user['expeditor_id'] == 171350 || count($pcs) > 1) {
			//maravet
			$cond = "IF(ep.swapped = 0, ep.expeditor_id in (select cod_cl from clienti where master = {$user['expeditor_id']}), ep.destinatar_id in (select cod_cl from clienti where master = {$user['expeditor_id']}))";
		}
		else {
			$cond = "IF(ep.swapped = 0, ep.expeditor_id = {$user['expeditor_id']}, ep.destinatar_id = {$user['expeditor_id']})";
		}

        //Log::debug('trackAwb : User '.$user['id'].' tracking awb '.$awb.' with condition: '.$cond);

        try {
            $query = DB::table('exp_prelucrate as ep')
                ->selectRaw("ep.expeditie, ep.created_at, ep.deleted_at, ep.printed_at, ep.data_expeditie,
                    op.op_ro as operatie, ist.data_op as data_op, ep.operatiune as status")
                ->leftJoin('ist_exp as ist', 'ep.cod_expeditie', '=', 'ist.cod_exp')
                ->leftJoin('op as op', 'ist.operatiune', '=', 'op.cod_op')
                ->leftJoin('users as u', 'u.id', '=', 'ep.created_by')
                ->where('ep.expeditie', $awb)
                ->whereRaw("(op.public IS NULL or op.public = 1)")
                ->whereRaw($cond)
                ->orderByRaw("if(ist.data_op is null, ep.data_expeditie, ist.data_op) desc");
            //Log::debug($query->toSql(), $query->getBindings());
            $row = $query->first();
            if($row->status == 'Livrat')
                return array('data'=>$row->data_op, 'status'=>$row->status);

            $status = array('data'=>$row->data_op,'status'=>$row->operatie);

            $detalii = DB::table('scanari_coduri as sc')
                ->selectRaw("sc.data as data, ce.nume as centru, ck.denumire as eveniment")
                ->leftJoin('centre as ce', 'sc.centru', '=', 'ce.id')
                ->leftJoin('checkpoints as ck', 'sc.tip', '=', 'ck.id')
                ->where('sc.expeditie', $awb)
                ->where('sc.is_awb', 1)
                ->where('ck.activ', 1)
                ->where('ck.is_public', 1)
                ->orderByDesc('sc.data')
                ->first();

            $status['detalii'] = $detalii ? (array)$detalii : [];
            return $status;
        } catch (\Exception $e) {
            return [];
        }
	}

	public static function trackAwbRetur($user, int $awb): array
	{
		$pcs = $user['pcIds'] ?? '';
        $pcs = explode(',', $pcs);

        try {
            $rows = DB::table('exp_prelucrate as epr')
                ->selectRaw("epr.expeditie, epr.data_expeditie, epr.tip_exp, 
                    epr.operatiune as status, epr.data_op as data_status,
                    sc.sc_data, sc.sc_centru, sc.sc_ckp")
                ->join('exp_prelucrate as ep', 'ep.expeditie', '=', 'epr.referire')
                ->leftJoin(DB::raw("(select i.expeditie, i.data as sc_data, ces.nume as sc_centru, cks.denumire as sc_ckp
                    from scanari_coduri i
                    LEFT JOIN centre as ces ON i.centru = ces.id
                    LEFT JOIN checkpoints as cks ON cks.id = i.tip
                    where i.data in
                        (select max(j.data) from scanari_coduri j
                        where j.expeditie = i.expeditie and j.is_awb = 1 and j.tip in (select id from checkpoints where activ = 1 and is_public = 1 ))
                ) as sc"), 'sc.expeditie', '=', 'epr.expeditie')
                ->where('epr.referire', $awb)
                ->where('ep.anulata', 0)
                ->where('epr.anulata', 0)
                ->whereNotIn('epr.tip_exp', [0,3,33])
                ->groupBy('epr.expeditie');
            if(count($pcs) > 1) {
                $rows->whereIn('epr.destinatar_id', $pcs);
            } else {
                $rows->where('epr.destinatar_id', $user['expeditor_id']);
            }

            $ret = [];
            $rows->get()
                ->each(function($row) {
                    if($row->status == 'Livrat') {
                        $ret[] = array('expeditie' => $row->expeditie, 'tip' => config('awb.tip_exp', [])[$row->tip_exp] ?? '', 'data'=>$row->data_status, 'status'=>$row->status, 'detalii'=>[]);
                    }
                    else {
                        $detalii = [];
                        if(!empty($row['sc_data'])) {
                            $detalii = array('data'=>$row['sc_data'], 'eveniment'=>$row['sc_ckp'], 'centru'=>$row['sc_centru']);
                        }
                        $ret[] = array('expeditie' => $row['expeditie'], 'tip' => config('awb.tip_exp', [])[$row->tip_exp] ?? '', 'data'=>$row->data_status,'status'=>$row->status, 'detalii'=>$detalii);
                    }
                });
            return $ret;
        } catch (\Exception $e) {
            return [];
        }
		
	}

	public static function historyAwb($user, int $awb)
	{
		$pcs = $user['pcIds'] ?? '';
        $pcs = explode(',', $pcs);

        $cond = "";
        if($user['expeditor_id'] == 171350 || count($pcs) > 1) {
			//maravet
			$cond = "IF(ep.swapped = 0, ep.expeditor_id in (select cod_cl from clienti where master = {$user['expeditor_id']}), ep.destinatar_id in (select cod_cl from clienti where master = {$user['expeditor_id']}))";
		}
		else {
			$cond = "IF(ep.swapped = 0, ep.expeditor_id = {$user['expeditor_id']}, ep.destinatar_id = {$user['expeditor_id']})";
		}

        try {
            $row = DB::table('exp_prelucrate as ep')
                ->selectRaw("ep.expeditie, ep.created_at, ep.deleted_at, ep.printed_at, ep.data_expeditie,
                    op.op_ro as operatie, ist.data_op as data_op, ep.operatiune as status")
                ->leftJoin('ist_exp as ist', 'ep.cod_expeditie', '=', 'ist.cod_exp')
                ->leftJoin('op as op', 'ist.operatiune', '=', 'op.cod_op')
                ->leftJoin('users as u', 'u.id', '=', 'ep.created_by')
                ->where('ep.expeditie', $awb)
                ->whereRaw("(c.public IS NULL or c.public = 1)")
                ->whereRaw($cond)
                ->orderByRaw("if(op.data_op is null, ep.data_expeditie, ist.data_op) desc")
                ->first();

            $status = array('data'=>$row->data_op,'status'=>$row->operatie,'detalii'=>[]);

            DB::table('scanari_coduri as sc')
                ->selectRaw("sc.data as data, ce.nume as centru, ck.denumire as eveniment")
                ->leftJoin('centre as ce', 'sc.centru', '=', 'ce.id')
                ->leftJoin('checkpoints as ck', 'sc.tip', '=', 'ck.id')
                ->where('sc.expeditie', $awb)
                ->where('sc.is_awb', 1)
                ->where('ck.activ', 1)
                ->where('ck.is_public', 1)
                ->orderByDesc('sc.data')
                ->get()->each(function($item) use ($status) {
                    $status['detalii'][] = array('data'=>$item->data, 'eveniment'=>$item->eveniment, 'centru'=>$item->centru);
                });

            return $status;
        } catch (\Exception $e) {
            return [];
        }
	}
}
