<?php

namespace App\Services;

use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;

class BorderouService
{
    /**
     * Create a new borderou
     */
    public function create($user, array $pcIds): array
    {
        $cond = "";
        if($user['expeditor_id'] == 171350 || count($pcIds) > 1) {
			//maravet, master, pcs
			$cond = count($pcIds) == 1 ? "expeditor_id = " . $pcIds[0] : "expeditor_id in (". implode(',', $pcIds) . ")";
		}
		else {
            $cond = "expeditor_id = {$user['expeditor_id']}";
        }

        try {
            //DB::enableQueryLog();
            DB::beginTransaction();
            $awb_ids = DB::table('exp_prelucrate')
                ->where('borderou_id', 0)
                ->where('swapped', 0)
                ->where('anulata', 0)
                ->whereNull('deleted_at')
                ->whereRaw($cond)
                ->get('cod_expeditie as id');
            if($awb_ids->isEmpty()){
			    throw new \Exception('No AWBs found.');
}
            $arr_awb_ids = $awb_ids->map(function($item) {
                return $item->id;
            })->toArray();

            if($awb_ids->isEmpty()) {
                return ['nrExpeditii' => 0, 'borderou' => 0];
            }

            $max_borderou_id = DB::table('client_borderouri')->where('client_id', $user['expeditor_id'])->max('borderou_id');
            if($max_borderou_id === null) {
                $max_borderou_id = 0;
            }
            //dd(DB::getRawQueryLog());
            $id = DB::table('client_borderouri')->insertGetId([
                'client_id' => $user['expeditor_id'],
                'data' => now(),
                'status' => 'Receptionat',
                'expeditii' => $awb_ids->count(),
                'borderou_id' => $max_borderou_id + 1,
                'created_at' => now(),
                'user_id' => $user['id'],
            ]);
            $affected = DB::table('exp_prelucrate')
                ->where('borderou_id', 0)
                ->where('swapped', 0)
                ->where('anulata', 0)
                ->whereNull('deleted_at')
                ->whereIn('cod_expeditie', $arr_awb_ids)
                ->update([
                    'borderou_id' => $id,
                ]);
            if($affected != count($arr_awb_ids)) {
                throw new \Exception('Failed to update AWBs with borderou ID.');
            }

            DB::commit();
            return ['nrExpeditii' => $awb_ids->count(), 'borderou' => $max_borderou_id + 1];
    
        } catch (\Exception $e) {
            DB::rollBack();
            throw new \Exception($e->getMessage());
        }
        return ['nrExpeditii' => 0, 'borderou' => 0];
    }

    /**
     * Get a single borderou by ID
     */
    public function select(int $borderouId, array $pcIds): array
    {

        $cond = "";
        if(count($pcIds) > 1) {
            //master, pcs
            $cond = "client_id in (". implode(',', $pcIds) . ")";
        } else {
            $cond = "client_id = " . $pcIds[0];
        }

        $row = DB::table('client_borderouri')
        ->where('id', $borderouId)
        ->whereRaw($cond)
        ->orderBy('id', 'desc')
        ->first();

        return $row ? (array)$row : [];
    }

    /**
     * Delete a borderou
     */
    public function delete(int $id, $user, array $pcIds): void
    {
        try {
            if($id == 0) throw new \Exception('Borderou not found or access denied.');
            $cond1 = "";
            $cond2 = "";
            if($user['expeditor_id'] == 171350 || count($pcIds) > 1) {
                //master, pcs
                $cond1 = "expeditor_id in (". implode(',', $pcIds) . ")";
                $cond2 = "client_id in (". implode(',', $pcIds) . ")";
            }
            else {
                $cond1 = "expeditor_id = {$user['expeditor_id']}";
                $cond2 = "client_id = {$user['expeditor_id']}";
            }
            DB::beginTransaction();

            // Reset borderou_id for associated AWBs
            $affected = DB::table('exp_prelucrate')
                ->where('borderou_id', $id)
                ->whereRaw($cond1)
                ->update(['borderou_id' => 0]);
            if($affected === 0) {
                throw new \Exception('No AWBs found for this borderou.');
            }
            // Delete the borderou
            $deleted = DB::table('client_borderouri')
                ->where('id', $id)
                ->whereRaw($cond2)
                ->update(['deleted_at' => now(), 'deleted_by' => $user['id']]);
            if(!$deleted) {
                throw new \Exception('Failed to delete borderou: ' . $id);
            }

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            throw new \Exception($e->getMessage());
        }
    }

    /**
     * Get AWBs for a specific borderou
     */
    public function getAwbsForBorderouId($user, int $id): array
    {
        if($id <= 0 || $user['expeditor_id'] <= 0)
			return [];

        return DB::table('exp_prelucrate as ep')
            ->select('ep.cod_expeditie as id', 'ep.expeditie', 'ep.data_expeditie', 
                    'ep.tip_obj', 'ep.piese',
                    'ep.greutate', 'ep.ramburs', 'ep.valoare_expeditie as valoare_fara_tva', 
                    'ep.tva', DB::raw('(ep.km_preluare+ep.km_livrare) as km_exteriori'),
                    'cle.nume as expeditor_nume', 'lce.nume_lc as expeditor_localitate',
                    'cld.nume as destinatar_nume', 'lcd.nume_lc as destinatar_localitate')
            ->join('clienti as cle', 'ep.expeditor_id', '=', 'cle.cod_cl')
            ->join('localitati as lce', function ($join) {
                $join->on('cle.cod_lc', '=', 'lce.cod_lc')
                    ->where('lce.deleted', 0);
                })
            ->join('clienti as cld', 'ep.destinatar_id', '=', 'cld.cod_cl')
            ->join('localitati as lcd', function ($join) {
                $join->on('cld.cod_lc', '=', 'lcd.cod_lc')
                    ->where('lcd.deleted', 0);
                })
            ->where('ep.anulata', 0)
            ->whereNull('ep.deleted_at')
            ->where('ep.borderou_id', $id)
            ->orderBy('ep.cod_expeditie', 'asc')
            ->get()->map(function ($item) use ($user) {
                return [
                    'id' => $item->id,
                    'awb' => $item->expeditie,
                    'expeditor_nume' => $item->expeditor_nume,
                    'expeditor_localitate' => $item->expeditor_localitate,
                    'destinatar_nume' => $item->destinatar_nume,
                    'destinatar_localitate' => $item->destinatar_localitate,
                    'data_expeditie' => $item->data_expeditie,
                    'tip_obj' => $item->tip_obj,
                    'piese' => $item->piese,
                    'greutate' => $item->greutate,
                    'ramburs' => $item->ramburs,
                    'valoare_fara_tva' => ($user['preturi'] ?? 0) == 1 ? $item->valoare_fara_tva : 'NA',
                    'tva' => ($user['preturi'] ?? 0) == 1 ? $item->tva : 'NA',
                    'km_exteriori' => $item->km_exteriori,
                    'printed' => 0,
                    'print_awb' => $user['print_awb'] ?? 1,
                ];
            })->toArray();
    }

    /**
     * Get AWB-uri for borderou in PDF.
     */
    public function getAwbsForBorderouInPdf($user, int $id): array
    {
        if($id <= 0 || $user['expeditor_id'] <= 0)
			return [];

        return DB::table('exp_prelucrate as ep')
            ->select('ep.cod_expeditie as id', 'ep.expeditie as awb', 'ep.borderou_id', 
                'ep.expeditor_id', 'ep.destinatar_id', 'ep.platitor_id', 
                'cld.nume as destinatar_nume', 'cld.adresa as destinatar_adresa', 'lcd.nume_lc as destinatar_localitate',
                'ep.tip_obj', 'ep.piese', 'ep.greutate', 'ep.ramburs', 'ep.tip_plata',
                DB::raw('(ep.km_preluare+ep.km_livrare) as km_exteriori'),
                'ep.detalii_doc', 'ep.observatii', 'cb.created_at as bo_created_at',
                'ep.printed_by'
                )
            ->join('client_borderouri as cb', 'ep.borderou_id', '=', 'cb.id')
            ->join('clienti as cld', 'ep.destinatar_id', '=', 'cld.cod_cl')
            ->join('localitati as lcd', function ($join) {
                $join->on('cld.cod_lc', '=', 'lcd.cod_lc')
                    ->where('lcd.deleted', 0);
                })
            ->where('ep.anulata', 0)
            ->whereNull('ep.deleted_at')
            ->where('ep.borderou_id', $id)
            ->orderBy('ep.cod_expeditie', 'asc')
            ->get()->map(function ($item) {
                return [
                    'id' => $item->id,
                    'awb' => $item->awb,
                    'expeditor_id' => $item->expeditor_id,
                    'platitor_id' => $item->platitor_id,
                    'destinatar' => $item->destinatar_nume,
                    'destinatar_localitate' => $item->destinatar_localitate,
                    'destinatar_adresa' => $item->destinatar_adresa,
                    'tip_obj' => $item->tip_obj,
                    'piese' => $item->piese,
                    'greutate' => $item->greutate,
                    'ramburs' => $item->ramburs,
                    'tip_plata' => $item->tip_plata,
                    'km_exteriori' => $item->km_exteriori,
                    'detalii_doc' => $item->detalii_doc,
                    'observatii' => $item->observatii,
                    'borderou_id' => $item->borderou_id,
                    'bo_created_at' => $item->bo_created_at, 
                    'printed_by' => $item->printed_by,
                ];
            })->toArray();

    }
}
