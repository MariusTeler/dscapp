<?php

namespace App\Services;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\User;

class BorderouService
{
    /**
     * Create a new borderou
     */
    public function create(User $user, array $data): array
    {
        $master_id = session('master_id', $user->expeditor_id);
        if($master_id == 171350 && $master_id != $user->expeditor_id) //maravet : numai masterul poate crea borderou
			return ['id' => -1];

        $pcs = collect(session('pcs', []));
        $pcIds = $pcs->reduce(function ($carry, $item) {
            $carry[] = $item['id'];
            return $carry;
        }, []);

        $cond = "";
        if($user->expeditor_id == 171350 || count($pcIds) > 1) {
			//maravet, master, pcs
			$cond = count($pcIds) == 1 ? "expeditor_id = " . $pcIds[0] : "expeditor_id in (". implode(',', $pcIds) . ")";
		}
		else {
            $cond = "expeditor_id = {$user->expeditor_id}";
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
                ->whereIn('cod_expeditie', $data['awb_ids'])
                ->get('cod_expeditie as id');
            if($awb_ids->isEmpty()){
			    throw new \Exception('No AWBs found.');
}
            $arr_awb_ids = $awb_ids->map(function($item) {
                return $item->id;
            })->toArray();

            $max_borderou_id = DB::table('client_borderouri')->where('client_id', $user->expeditor_id)->max('borderou_id');
            if($max_borderou_id === null) {
                $max_borderou_id = 0;
            }
            //dd(DB::getRawQueryLog());
            $id = DB::table('client_borderouri')->insertGetId([
                'client_id' => $user->expeditor_id,
                'data' => now(),
                'expeditii' => $awb_ids->count(),
                'borderou_id' => $max_borderou_id + 1,
                'created_at' => now(),
                'user_id' => $user->id,
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
            return ['id' => $id, 'borderou_id' => $id];
    
        } catch (\Exception $e) {
            DB::rollBack();
            throw new \Exception($e->getMessage());
        }
        return ['id' => -1];
    }

    /**
     * Get a single borderou by ID
     */
    public function getById(int $id): array
    {
        $row = DB::table('client_borderouri')
        ->where('id', $id)
        ->first();

        return $row ? (array)$row : [];
    }

    /**
     * Delete a borderou
     */
    public function delete(User $user, int $id): void
    {
        try {
            if($id == 0) throw new \Exception('Borderou not found or access denied.');
            $master_id = session('master_id', $user->expeditor_id);
            if($master_id == 171350 && $master_id != $user->expeditor_id) //maravet : numai masterul poate crea borderou
                throw new \Exception('Borderou not found or access denied.');

            $pcs = collect(session('pcs', []));
            $pcIds = $pcs->reduce(function ($carry, $item) {
                $carry[] = $item['id'];
                return $carry;
            }, []);

            $cond = "";
            if($user->expeditor_id == 171350 || count($pcIds) > 1) {
                //maravet, master, pcs
                $cond = count($pcIds) == 1 ? "expeditor_id = " . $pcIds[0] : "expeditor_id in (". implode(',', $pcIds) . ")";
            } else {
                $cond = "expeditor_id = {$user->expeditor_id}";
            }
            DB::beginTransaction();

            // Reset borderou_id for associated AWBs
            $affected = DB::table('exp_prelucrate')
                ->where('borderou_id', $id)
                ->whereRaw($cond)
                ->update(['borderou_id' => 0]);
            if($affected === 0) {
                throw new \Exception('No AWBs found for this borderou.');
            }
            // Delete the borderou
            $deleted = DB::table('client_borderouri')
                ->where('id', $id)
                ->update(['deleted_at' => now(), 'deleted_by' => $user->id]);
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
    public function getAwbsForBorderouId(
        User $user,
        int $borderou_id,
        int $page = 1,
        int $rows = 3000,
        string $sortField = 'id',
        string $sortOrder = 'desc',
    ): array
    {
        if($borderou_id == -1 || $user->expeditor_id <= 0)
			return ['data' => [], 'total' => 0, 'current_page' => 1, 'per_page' => $rows, 'last_page' => 1, 'from' => null, 'to' => null];

        $master_id = session('master_id', $user->expeditor_id);
        if($master_id == 171350 && $master_id != $user->expeditor_id) //maravet : numai masterul poate crea borderou
            throw new \Exception('Borderou not found or access denied.');

        $pcs = collect(session('pcs', []));
        $pcIds = $pcs->reduce(function ($carry, $item) {
            $carry[] = $item['id'];
            return $carry;
        }, []);

        $cond = "";
        if($user->expeditor_id == 171350 || count($pcIds) > 1) {
            //maravet, master, pcs
            $cond = count($pcIds) == 1 ? "ep.expeditor_id = " . $pcIds[0] : "ep.expeditor_id in (". implode(',', $pcIds) . ")";
        } else {
            $cond = "ep.expeditor_id = {$user->expeditor_id}";
        }

        $query = DB::table('exp_prelucrate as ep')
            ->select('ep.cod_expeditie as id', 'ep.expeditie as awb', 'ep.data_expeditie', 'ep.tip_obj', 'ep.piese',
                    'ep.greutate', 'ep.valoare_asigurata as asigurare', 'ep.ramburs', 'ep.tip_plata', 
                    'ep.valoare_totala_expeditie as valoare_fara_tva', 'ep.tva as valoare_tva', 
                    'ep.ret_nt', 'ep.ret_doc', 'ep.ret_colet', 'ep.ret_amb', 'ep.liv_sed', 'ep.liv_samb',
                    'ep.copen', 'ep.sms', 'ep.km_preluare', 'ep.km_livrare',
                    'ep.expeditor_id', 'ep.destinatar_id', 'ep.platitor_id',
                    'ep.mod_plata', 'ep.swapped',
                    DB::raw('(ep.km_preluare+ep.km_livrare) as km_exteriori'),
                    'cle.nume as expeditor_nume', 'lce.nume_lc as expeditor_localitate', 'je.nume_jd as expeditor_judet',
                    'cld.nume as destinatar_nume', 'lcd.nume_lc as destinatar_localitate', 'jd.nume_jd as destinatar_judet',
                    'ep.destinatar_contact', 'ep.destinatar_telefon', 'ep.destinatar_email',
                    'ep.expeditor_contact', 'ep.expeditor_telefon', 'ep.expeditor_email',
                    'cle.adresa as expeditor_adresa', 'cld.adresa as destinatar_adresa',
                    'enc.extrainfo', DB::raw('IF(ep.platitor_id = ep.expeditor_id, 1, 2) as platitor')
                )
            ->join('clienti as cle', 'ep.expeditor_id', '=', 'cle.cod_cl')
            ->join('localitati as lce', function ($join) {
                $join->on('cle.cod_lc', '=', 'lce.cod_lc')
                    ->where('lce.deleted', 0);
                })
            ->leftJoin('judete as je', 'lce.cod_jd', '=', 'je.cod_jd')
            ->join('clienti as cld', 'ep.destinatar_id', '=', 'cld.cod_cl')
            ->join('localitati as lcd', function ($join) {
                $join->on('cld.cod_lc', '=', 'lcd.cod_lc')
                    ->where('lcd.deleted', 0);
                })
            ->leftJoin('judete as jd', 'lcd.cod_jd', '=', 'jd.cod_jd')
            ->leftJoin('exp_nc as enc', 'ep.expeditie', '=', 'enc.expeditie')
            ->where('ep.anulata', 0)
            ->whereNull('ep.deleted_at')
            ->where('ep.borderou_id', $borderou_id)
            ->whereRaw($cond)
            ->orderBy('ep.cod_expeditie', $sortOrder)
            ->groupBy('ep.cod_expeditie');
        // dd($query->toSql(), $query->getBindings());
        // Paginate
        $result = $query->paginate($rows, ['*'], 'page', $page);
        $items = collect($result->items())->map(function ($item) use ($user) {
            return [
                'id' => $item->id,
                'awb' => $item->awb,
                'expeditor_nume' => $item->expeditor_nume,
                'expeditor_judet' => $item->expeditor_judet,
                'expeditor_localitate' => $item->expeditor_localitate,
                'expeditor_adresa' => $item->expeditor_adresa,
                'expeditor_contact' => $item->expeditor_contact,
                'expeditor_telefon' => $item->expeditor_telefon,
                'expeditor_email' => $item->expeditor_email,
                'km_preluare' => $item->km_preluare,
                'destinatar_nume' => $item->destinatar_nume,
                'destinatar_judet' => $item->destinatar_judet,
                'destinatar_localitate' => $item->destinatar_localitate,
                'destinatar_adresa' => $item->destinatar_adresa,
                'destinatar_contact' => $item->destinatar_contact,
                'destinatar_telefon' => $item->destinatar_telefon,
                'destinatar_email' => $item->destinatar_email,
                'km_livrare' => $item->km_livrare,
                'platitor' => $item->platitor,
                'mod_plata' => $item->mod_plata,
                'data_expeditie' => $item->data_expeditie,
                'tip_obj' => $item->tip_obj,
                'piese' => $item->piese,
                'greutate' => number_format($item->greutate, 1, '.', ''),
                'asigurare' => number_format($item->asigurare, 2, '.', ''),
                'ramburs' => number_format($item->ramburs, 2, '.', ''),
                'tip_plata' => $item->tip_plata,
                'valoare_fara_tva' => ($user->preturi ?? 0) == 1 ? number_format($item->valoare_fara_tva, 2, '.', '') : 'N/A',
                'valoare_tva' => ($user->preturi ?? 0) == 1 ? number_format($item->valoare_tva, 2, '.', '') : 'N/A',
                'km_exteriori' => $item->km_exteriori,
                'printed' => 0,
                'print_awb' => $user->print_awb ?? 1,
            ];
        });


        return [
            'data' => $items,
            'total' => $result->total(),
            'current_page' => $result->currentPage(),
            'per_page' => $result->perPage(),
            'last_page' => $result->lastPage(),
            'from' => $result->firstItem(),
            'to' => $result->lastItem(),
        ];
    }

    /**
     * Get AWB-uri for borderou in PDF.
     */
    public function getAwbsForBorderouInPdf(
        int $borderou_id,
        int $expeditor_id,
        string $sortField = 'ep.expeditie',
        string $sortOrder = 'asc',
    ): array
    {
        if($borderou_id == -1 || $expeditor_id <= 0)
			return [];

        $query = DB::table('exp_prelucrate as ep')
            ->select('ep.cod_expeditie as id', 'ep.expeditie as awb', 'ep.borderou_id',
                'ep.expeditor_id', 'ep.destinatar_id', 'ep.platitor_id', 
                'cld.nume as destinatar_nume', 'cld.adresa as destinatar_adresa', 'lcd.nume_lc as destinatar_localitate',
                'ep.tip_obj', 'ep.piese', 'ep.greutate', 'ep.valoare_asigurata as asigurare', 'ep.ramburs', 'ep.tip_plata',
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
            ->where('ep.borderou_id', $borderou_id)
            ->orderBy($sortField, $sortOrder)
            ->get();
        //dd($query->toSql(), $query->getBindings());

        $items = $query->map(function ($item) {
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
                'greutate' => number_format($item->greutate, 1, '.', ''),
                'asigurare' => number_format($item->asigurare, 2, '.', ''),
                'ramburs' => number_format($item->ramburs, 2, '.', ''),
                'tip_plata' => $item->tip_plata,
                'km_exteriori' => $item->km_exteriori,
                'detalii_doc' => $item->detalii_doc,
                'observatii' => $item->observatii,
                'borderou_id' => $item->borderou_id,
                'bo_created_at' => $item->bo_created_at, 
                'printed_by' => $item->printed_by,
            ];
        })->toArray();


        return $items;
    }

    /**
     * Get paginated borderouri with filters and sorting
     */
    public function getPaginated(
        User $user,
        int $print_awb,
        int $page = 1,
        int $rows = 100,
        string $sortField = 'id',
        string $sortOrder = 'asc',
        Request $request
    ): array {
        $master_id = session('master_id', $user->expeditor_id);
        if($master_id == 171350 && $master_id != $user->expeditor_id) //maravet : numai masterul poate crea borderou
            throw new \Exception('Borderou not found or access denied.');

        $pcs = collect(session('pcs', []));
        $pcIds = $pcs->reduce(function ($carry, $item) {
            $carry[] = $item['id'];
            return $carry;
        }, []);

        $cond = "";
        if($user->expeditor_id == 171350 || count($pcIds) > 1) {
            //maravet, master, pcs
            $cond = count($pcIds) == 1 ? "cb.client_id = " . $pcIds[0] : "cb.client_id in (". implode(',', $pcIds) . ")";
        } else {
            $cond = "cb.client_id = {$user->expeditor_id}";
        }

        $query = DB::table('client_borderouri as cb')
            ->select('cb.id', 'cb.id as borderou_id', DB::raw('count(ep.cod_expeditie) as expeditii'), 'cb.created_at', 'u.nume as created_by')
            ->leftJoin('users as u', 'cb.user_id', '=', 'u.id')
            ->leftJoin('exp_prelucrate as ep', 'cb.id', '=', 'ep.borderou_id')
            ->whereRaw($cond)
            ->whereNull('cb.deleted_at')
            ->groupBy('cb.id');

        // Apply filters
        foreach ($request->all() as $key => $value) {
            if (str_starts_with($key, 'filter_') && !empty($value)) {
                $field = substr($key, 7);
                match ($field) {
                    'borderou_id' => $query->where('cb.id', intval($value)),
                    'created_at' => $query->whereRaw("DATE(cb.created_at) = '" . date('Y-m-d', strtotime($value)) . "'"),
                    'created_by' => $query->where('u.name', 'LIKE', "{$value}%"),
                    'expeditii' => $query->having('expeditii', intval($value)),
                    default => $query->where($field, 'like', "{$value}%"),
                };
            }
        }

        // Apply sorting
        match ($sortField) {
            'borderou_id' => $query->orderBy('cb.id', $sortOrder),
            'created_at' => $query->orderBy('cb.created_at', $sortOrder),
            'created_by' => $query->orderBy('u.name', $sortOrder),
            'expeditii' => $query->orderBy(DB::raw('expeditii'), $sortOrder),
            default => $query->orderBy('cb.id', $sortOrder),
        };
        // dd($query->toSql(), $query->getBindings());
        // Paginate
        $result = $query->paginate($rows, ['*'], 'page', $page);
        
        // Map items to add print_awb
        $items = collect($result->items())->map(function ($item) use ($print_awb) {
            return array_merge((array) $item, ['print_awb' => $print_awb]);
        });

        return [
            'data' => $items,
            'total' => $result->total(),
            'current_page' => $result->currentPage(),
            'per_page' => $result->perPage(),
            'last_page' => $result->lastPage(),
            'from' => $result->firstItem(),
            'to' => $result->lastItem(),
        ];     
    }
}
