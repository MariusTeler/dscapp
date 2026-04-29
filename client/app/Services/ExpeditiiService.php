<?php

namespace App\Services;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\User;
use Illuminate\Support\Facades\Log;
use App\Services\Helpers\ToolsService;

class ExpeditiiService
{
    /**
     * Get AWB-uri.
     */
    public function getValues(
        User $user,
        int $awb_id = 0,
        array $awb_ids = [],
        int $borderou_id = 0,
        $forPrint = false,
    ): array
    {
        $pcs = collect(session('pcs', []));
        $pcIds = $pcs->reduce(function ($carry, $item) {
            $carry[] = $item['id'];
            return $carry;
        }, []);

        $not_print_phone = session('not_print_phone', 0) == 1 ? true : false;

        $cond = "";
        if($user->expeditor_id == 171350 || count($pcIds) > 1) {
			//maravet
			$cond = "IF(ep.swapped = 0, ep.expeditor_id in (". implode(',', $pcIds) . "), ep.destinatar_id in (". implode(',', $pcIds) . "))";
		}
		else {
			$cond = "IF(ep.swapped = 0, ep.expeditor_id = {$user->expeditor_id}, ep.destinatar_id = {$user->expeditor_id})";
		}

        $query = DB::table('exp_prelucrate as ep')
            ->select('ep.cod_expeditie as id', 'ep.expeditie as awb', 'ep.referire', 'ep.data_expeditie',
            'ep.created_at', 'ep.created_by', 'u.user as created_by_user',
            'ep.updated_at', 'ep.updated_by', 'mu.user as updated_by_user',
            'ep.printed_at', 'ep.printed_by', 'mp.user as printed_by_user',
            'ep.deleted_at', 'ep.deleted_by',
            'ep.expeditor_id', 'ep.destinatar_id', 'ep.platitor_id', 
            'ep.expeditor_contact', 'ep.expeditor_telefon', 'u.email as expeditor_email', 'ep.destinatar_contact', 'ep.destinatar_telefon', 'ep.destinatar_email',
            'lce.cod_lc as expeditor_localitate_id', 'lcd.cod_lc as destinatar_localitate_id',
            DB::raw('IF(cle.zona_id > 0 and clec.id > 0, clec.id, cee.id) as expeditor_centru_id'), 
            DB::raw('IF(cld.zona_id > 0 and cldc.id > 0, cldc.id, ced.id) as destinatar_centru_id'), 
            'je.cod_jd as expeditor_judet_id', 'jd.cod_jd as destinatar_judet_id',
            'ep.tip_obj', 'ep.piese', 'ep.greutate', 'ep.greutate_vol', 'ep.volum', 'ep.ret_nt', 'ep.ret_doc', 'ep.ret_colet', 'ep.ret_amb', 'ep.liv_sed', 'ep.liv_samb',
            'ep.copen', 'ep.sms', 'ep.km_preluare', 'ep.km_livrare', 'ep.ramburs', 'ep.tip_plata', 'ep.valoare_asigurata as asigurare',
            'ep.ramburs_procent', 'ep.procent_asigurare',
            'ep.val_greutate as valoare_kg', 'ep.val_km as valoare_km', 'ep.val_asig as valoare_asig', 'ep.valoare_expeditie as valoare_exp', 'ep.tva as valoare_tva', 'ep.valoare_totala_expeditie as valoare_fara_tva', 'ep.procTva', 'ep.moneda', 'ep.mod_plata',
            'ep.observatii', 'ep.detalii_doc', 'ep.swapped', 'ep.anulata', 'ep.operatiune as status', 'ep.data_op as data_status', 'ep.primitor',
            DB::raw('IF(lce.cod_lc = lcd.cod_lc, 0 , 1) as tip_tarif'), DB::raw('(ep.km_preluare+ep.km_livrare) as km_exteriori'),
            'cle.nume as expeditor_nume', 'cle.adresa as expeditor_adresa',
            'lce.nume_lc as expeditor_localitate', 'lce.dist_km as expeditor_localitate_km', 'je.nume_jd as expeditor_judet',
            DB::raw('IF(cle.zona_id > 0 and clec.id > 0, clec.nume, cee.nume) as expeditor_centru'), 
            DB::raw('IF(cle.zona_id > 0 and clec.id > 0, clec.label, cee.label) as expeditor_centru_cod'),
            'cld.nume as destinatar_nume', 'cld.adresa as destinatar_adresa',
            'lcd.nume_lc as destinatar_localitate', 'lcd.dist_km as destinatar_localitate_km', 'jd.nume_jd as destinatar_judet',
            DB::raw('IF(cld.zona_id > 0 and cldc.id > 0, cldc.nume, ced.nume) as destinatar_centru'), 
            DB::raw('IF(cld.zona_id > 0 and cldc.id > 0, cldc.label, ced.label) as destinatar_centru_cod'),
            DB::raw("IF(cld.zona_id > 0 and cldc.id > 0, CONCAT(' - ', cldz.name), '') as destinatar_centru_zona"),
            DB::raw('IF(cld.zona_id > 0 and cldc.id > 0, cldc.rut_bvh, ced.rut_bvh) as destinatar_rut_bvh'),
            DB::raw('IF(cld.zona_id > 0 and cldc.id > 0, cldc.rut_buh, ced.rut_buh) as destinatar_rut_buh'),
            DB::raw('IF(cld.zona_id > 0 and cldc.id > 0, cldc.rut_buc, ced.rut_buc) as destinatar_rut_buc'),
            DB::raw('IF(ep.platitor_id = ep.expeditor_id, cle.nume, cld.nume) as platitor_nume'),
            DB::raw('IF(ep.platitor_id = ep.expeditor_id, IF(cle.zona_id > 0 and clec.id > 0, clec.nume, cee.nume), IF(cld.zona_id > 0 and cldc.id > 0, cldc.nume, ced.nume)) as platitor_centru'),
            DB::raw('IF(ep.platitor_id = ep.expeditor_id, IF(cle.zona_id > 0 and clec.id > 0, clec.label, cee.label), IF(cld.zona_id > 0 and cldc.id > 0, cldc.label, ced.label)) as platitor_centru_cod'),
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
            ->whereRaw($cond);
        if($borderou_id > 0) {
            $query->where('ep.borderou_id', $borderou_id)
            ->where('ep.swapped', 0);
        } else if(is_array($awb_ids) && count($awb_ids) > 0) {
            $query->whereIn('ep.cod_expeditie', $awb_ids);
        } else if($awb_id > 0) {
            $query->where('ep.cod_expeditie', $awb_id);
        } else return [];
        $query->groupBy('ep.cod_expeditie')
        ->orderBy('ep.cod_expeditie', 'DESC');
        //Log::info("ExpeditiiService getValues query: " . $query->toSql() . " with params: " . json_encode($query->getBindings()));
        return $query->get()
                ->map(fn( $item ) => (array) $item )
                ->map(function($item) use ($not_print_phone, $forPrint) {
                    if($not_print_phone && $forPrint) {
                        //Log::debug("Masking phone for AWB " . $item['awb'] . " - expeditor: " . $item['expeditor_telefon'] . ", destinatar: " . $item['destinatar_telefon']);
                        if(isset($item['expeditor_telefon']) && !empty($item['expeditor_telefon'])) {
                            $item['expeditor_telefon'] = '**********';
                        }
                        if(isset($item['destinatar_telefon']) && !empty($item['destinatar_telefon'])) {
                            $item['destinatar_telefon'] = '**********';
                        }
                    }
                    return $item;
                })
                ->toArray();
    }

    /**
     * Get AWB-uri nepredate (undelivered AWBs) with pagination and filtering.
     */
    public function getNepredate(
        int $borderou_id,
        User $user,
        int $page = 1,
        int $rows = 300,
        string $sortField = 'id',
        string $sortOrder = 'desc',
        bool $swapped = false,
        Request $request
    ): array
    {
        if($borderou_id == -1)
			return ['data' => [], 'total' => 0, 'current_page' => 1, 'per_page' => $rows, 'last_page' => 1, 'from' => null, 'to' => null];

        $master_id = session('master_id', $user->expeditor_id);
        if($borderou_id == 0 && $master_id == 171350 && $master_id != $user->expeditor_id) //maravet
			return ['data' => [], 'total' => 0, 'current_page' => 1, 'per_page' => $rows, 'last_page' => 1, 'from' => null, 'to' => null];

        $can_update_after_print = session('can_update_after_print', 0) == 1 ? true : false;
        $pcs = collect(session('pcs', []));
        $pcIds = $pcs->reduce(function ($carry, $item) {
            $carry[] = $item['id'];
            return $carry;
        }, []);

        $cond = "";
        if($user->expeditor_id == 171350 || count($pcIds) > 1) {
			//maravet
			$cond = "IF(ep.swapped = 0, ep.expeditor_id in (". implode(',', $pcIds) . "), ep.destinatar_id in (". implode(',', $pcIds) . "))";
		}
		else {
			$cond = "IF(ep.swapped = 0, ep.expeditor_id = {$user->expeditor_id}, ep.destinatar_id = {$user->expeditor_id})";
		}

        $query = DB::table('exp_prelucrate as ep')
            ->select('ep.cod_expeditie as id', 'ep.expeditie as awb', 'ep.data_expeditie', 'ep.tip_obj', 'ep.piese',
                    'ep.greutate', 'ep.greutate_vol', 'ep.valoare_asigurata as asigurare', 'ep.ramburs', 'ep.tip_plata',
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
                    'ep.idfact', 'dee.id as factura_id', 'ep.last_ckp', 'ep.borderou_id', 'ep.printed_by',
                    'enc.extrainfo', DB::raw('IF(ep.platitor_id = ep.expeditor_id, 1, 2) as platitor')
                )
            ->join('clienti as cle', 'ep.expeditor_id', '=', 'cle.cod_cl')
            ->join('localitati as lce', 'cle.cod_lc', '=', 'lce.cod_lc')
            ->leftJoin('judete as je', 'lce.cod_jd', '=', 'je.cod_jd')
            ->join('clienti as cld', 'ep.destinatar_id', '=', 'cld.cod_cl')
            ->join('localitati as lcd', 'cld.cod_lc', '=', 'lcd.cod_lc')
            ->leftJoin('judete as jd', 'lcd.cod_jd', '=', 'jd.cod_jd')
            ->leftJoin('exp_nc as enc', 'ep.expeditie', '=', 'enc.expeditie')
            ->leftJoin('decont_expeditii as dee', function($join) {
                $join->on('ep.expeditie', '=', 'dee.expeditie')
                    ->where('dee.anulata', 0);
            })
            ->leftJoin('decont_facturi as dfa', function($join) {
                $join->on('dee.factura_id', '=', 'dfa.id')
                    ->where('dfa.anulata', 0);
            })
            ->where('ep.tip_exp', 0)
            ->where('ep.anulata', 0);
        if($swapped === false) {
            $query->where('ep.swapped', 0);
        }
            
        if($borderou_id > 0) {
            $query->where('ep.borderou_id', $borderou_id);
        } else {
            $query->where('ep.borderou_id', 0);
            $query->where('ep.last_ckp', 0);
            $query->where('ep.idfact', 0);
            $query->whereNull('dfa.id');
            $query->whereRaw($cond);
        }
        $query->groupBy('ep.cod_expeditie');
        //dd($query->toSql());

        // Apply date range filter
        $startDate = $request->input('startDate');
        $endDate = $request->input('endDate');
        
        if ($startDate && $endDate) {
            //if startDate is after endDate, swap them
            if($startDate > $endDate) {
                $temp = $startDate;
                $startDate = $endDate;
                $endDate = $temp;
            }
            $query->whereBetween('ep.data_expeditie', [$startDate, $endDate]);
        } elseif ($startDate) {
            //between start date and start date + 1 month 
            $query->whereDate('ep.data_expeditie', '>=', $startDate);
            $query->whereDate('ep.data_expeditie', '<=', date('Y-m-d', strtotime($startDate . ' + 1 month')));
        } elseif ($endDate) {
            //between end date - 1 month and end date
            $query->whereDate('ep.data_expeditie', '>=', date('Y-m-d', strtotime($endDate . ' - 1 month')));
            $query->whereDate('ep.data_expeditie', '<=', $endDate);
        }

        // Apply global search (q) — LIKE OR pe câmpurile cheie
        $q = trim((string) $request->input('q', ''));
        $minChars = config('awb.tabulator.min_chars_filter', 3);
        if ($q !== '' && mb_strlen($q) >= $minChars) {
            $isAwb = ToolsService::isAwb($q);
            $query->where(function ($sub) use ($q, $isAwb) {
                if ($isAwb) {
                    $sub->orWhere('ep.expeditie', '=', (int) $q);
                }
                $sub->orWhere('cle.nume', 'like', "%{$q}%")
                    ->orWhere('cld.nume', 'like', "%{$q}%")
                    ->orWhere('lce.nume_lc', 'like', "%{$q}%")
                    ->orWhere('lcd.nume_lc', 'like', "%{$q}%");
            });
        }

        // Apply filters
        foreach ($request->all() as $key => $value) {
            if (str_starts_with($key, 'filter_') && !empty($value)) {
                $field = substr($key, 7);
                //replace if with match for more complex filters
                match ($field) {
                    'expeditor_nume' => $query->where('cle.nume', 'like', "{$value}%"),
                    'expeditor_localitate' => $query->where('lce.nume_lc', 'like', "{$value}%"),
                    'destinatar_nume' => $query->where('cld.nume', 'like', "{$value}%"),
                    'destinatar_localitate' => $query->where('lcd.nume_lc', 'like', "{$value}%"),
                    'awb' => $query->where('ep.expeditie', 'like', "{$value}%"),
                    'tip_obj' =>
                        $query->where('ep.' . $field, '=', (int)$value),
                    'piese', 'greutate', 'ramburs' =>
                        $query->where('ep.' . $field, 'like', "%{$value}%"),
                    'data_expeditie' =>
                        $query->whereDate('ep.data_expeditie', '=', date('Y-m-d', strtotime($value))),
                    'judet' => $query->where('jd.cod_jd', '=', strtoupper(trim($value))),
                    default => $query->where($field, 'like', "{$value}%"),
                };
            }
        }
        // Apply sorting
        match ($sortField) {
            'expeditor_nume' => $query->orderBy('cle.nume', $sortOrder),
            'expeditor_localitate' => $query->orderBy('lce.nume_lc', $sortOrder),
            'destinatar_nume' => $query->orderBy('cld.nume', $sortOrder),
            'destinatar_localitate' => $query->orderBy('lcd.nume_lc', $sortOrder),
            'awb' => $query->orderBy('ep.expeditie', $sortOrder),
            'id' => $query->orderBy('ep.cod_expeditie', $sortOrder),
            'tip_obj', 'piese', 'greutate', 'ramburs', 'data_expeditie' =>
                $query->orderBy('ep.'.$sortField, $sortOrder),
            'valoare_fara_tva' => $query->orderBy('ep.valoare_totala_expeditie', $sortOrder),
            'valoare_tva' => $query->orderBy('ep.tva', $sortOrder),
            'km_exteriori' => $query->orderBy('km_exteriori', $sortOrder),
            default => $query->orderBy('ep.cod_expeditie', 'desc'),
        };
        //Log::debug($query->toSql(), $query->getBindings());
        // Paginate
        $result = $query->paginate($rows, ['*'], 'page', $page);
        $items = collect($result->items())->map(function ($item) use ($user, $can_update_after_print) {
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
                'data_expeditie' => $item->data_expeditie,
                'tip_obj' => $item->tip_obj,
                'piese' => $item->piese,
                'greutate' => number_format($item->greutate, 1, '.', ''),
                'greutate_vol' => number_format($item->greutate_vol, 1, '.', ''),
                'asigurare' => number_format($item->asigurare, 2, '.', ''),
                'ramburs' => number_format($item->ramburs, 2, '.', ''),
                'tip_plata' => $item->tip_plata,
                'ret_nt' => $item->ret_nt,
                'ret_nc' => !empty($item->extrainfo),
                'ret_doc' => $item->ret_doc,
                'ret_colet' => $item->ret_colet,
                'ret_amb' => $item->ret_amb,
                'liv_sed' => $item->liv_sed,
                'liv_samb' => $item->liv_samb,
                'copen' => $item->copen,
                'sms' => $item->sms,
                'platitor' => $item->platitor,
                'valoare_fara_tva' => ($user->preturi ?? 0) == 1 ? number_format($item->valoare_fara_tva, 2, '.', '') : 'N/A',
                'valoare_tva' => ($user->preturi ?? 0) == 1 ? number_format($item->valoare_tva, 2, '.', '') : 'N/A',
                'km_exteriori' => $item->km_exteriori,
                'printed' => ($item->printed_by ?? 0) > 0 ? 1 : 0,
                'print_awb' => $user->print_awb ?? 1,
                'destinatar_contact' => $item->destinatar_contact,
                'destinatar_telefon' => $item->destinatar_telefon,
                'destinatar_email' => $item->destinatar_email,
                'expeditor_contact' => $item->expeditor_contact,
                'expeditor_telefon' => $item->expeditor_telefon,
                'expeditor_email' => $item->expeditor_email,
                'can_update' => $item->printed_by == 0 || $can_update_after_print,
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
     * Get AWB-uri predate (one scan min) with pagination and filtering.
     */
    public function getPredate(
        User $user,
        int $page = 1,
        int $rows = 100,
        string $sortField = 'id',
        string $sortOrder = 'desc',
        Request $request
    ): array
    {
        $pcs = collect(session('pcs', []));
        $pcIds = $pcs->reduce(function ($carry, $item) {
            $carry[] = $item['id'];
            return $carry;
        }, []);

        $cond = "";
        if($user->expeditor_id == 171350 || count($pcIds) > 1) {
			//maravet
			$cond = "IF(ep.swapped = 0, ep.expeditor_id in (". implode(',', $pcIds) . "), ep.destinatar_id in (". implode(',', $pcIds) . "))";
		}
		else {
			$cond = "IF(ep.swapped = 0, ep.expeditor_id = {$user->expeditor_id}, ep.destinatar_id = {$user->expeditor_id})";
		}

        $query = DB::table('exp_prelucrate as ep')
            ->select('ep.cod_expeditie as id', 'ep.expeditie as awb', 'ep.data_expeditie', 
                    'ep.tip_obj', 'ep.piese', 'ep.greutate', 'ep.greutate_vol','ep.valoare_asigurata as asigurare', 'ep.ramburs', 'ep.tip_plata',
                    'ep.ret_nt', 'ep.ret_doc', 'ep.ret_colet', 'ep.ret_amb', 'ep.liv_sed', 'ep.liv_samb',
                    'ep.copen', 'ep.sms', 'ep.km_preluare', 'ep.km_livrare',
                    'ep.valoare_totala_expeditie as valoare_fara_tva', 'ep.tva as valoare_tva', 
                    DB::raw('(ep.km_preluare+ep.km_livrare) as km_exteriori'),
                    'ep.destinatar_contact', 'ep.destinatar_telefon', 'ep.destinatar_email',
                    'ep.expeditor_contact', 'ep.expeditor_telefon', 'ep.expeditor_email',
                    'cle.nume as expeditor_nume', 'lce.nume_lc as expeditor_localitate', 'je.nume_jd as expeditor_judet',
                    'cld.nume as destinatar_nume', 'lcd.nume_lc as destinatar_localitate', 'jd.nume_jd as destinatar_judet',
                    'cle.adresa as expeditor_adresa', 'cld.adresa as destinatar_adresa',
                    'ep.data_last_ckp', 'ce.nume as centru_last_ckp', 'ck.denumire as last_ckp',
                    'ep.operatiune as status', 'ep.data_op as data_status', 'ep.primitor', 'ec.folder as confirmare',
                    DB::raw('IF(ep.platitor_id = ep.expeditor_id, 1, 2) as platitor'), 'ep.mod_plata',
                    'enc.extrainfo',
                )
            ->join('clienti as cle', 'ep.expeditor_id', '=', 'cle.cod_cl')
            ->join('localitati as lce', 'cle.cod_lc', '=', 'lce.cod_lc')
            ->leftJoin('judete as je', 'lce.cod_jd', '=', 'je.cod_jd')
            ->join('clienti as cld', 'ep.destinatar_id', '=', 'cld.cod_cl')
            ->join('localitati as lcd', 'cld.cod_lc', '=', 'lcd.cod_lc')
            ->leftJoin('judete as jd', 'lcd.cod_jd', '=', 'jd.cod_jd')
            ->leftJoin('exp_nc as enc', 'ep.expeditie', '=', 'enc.expeditie')
            ->leftJoin('exp_confirmari as ec', 'ep.expeditie', '=', 'ec.expeditie')
            ->leftJoin('centre as ce', 'ep.centru_last_ckp', '=', 'ce.id')
            ->leftJoin('checkpoints as ck', 'ep.last_ckp', '=', 'ck.id')
            ->leftJoin('decont_expeditii as dee', function($join) {
                $join->on('ep.expeditie', '=', 'dee.expeditie')
                    ->where('dee.anulata', 0);
            })
            ->leftJoin('decont_facturi as dfa', function($join) {
                $join->on('dee.factura_id', '=', 'dfa.id')
                    ->where('dfa.anulata', 0);
            })
            ->where('ep.anulata', 0)
            ->where('ep.tip_exp', 0)
            // and ep.idfact > 0 or dee.factura_id > 0 or ep.borderou_id > 0 or ep.last_ckp > 0
            ->where(function($q) {
                $q->where('ep.idfact', '>' , 0)
                    ->orWhereNotNull('dfa.id')
                    ->orWhere('ep.borderou_id', '>' , 0)
                    ->orWhere('ep.last_ckp', '>' , 0);
            })
            ->whereRaw($cond)
            ->groupBy('ep.cod_expeditie');

        // Apply date range filter
        $startDate = $request->input('startDate');
        $endDate = $request->input('endDate');
        
        if ($startDate && $endDate) {
            //if startDate is after endDate, swap them
            if($startDate > $endDate) {
                $temp = $startDate;
                $startDate = $endDate;
                $endDate = $temp;
            }
            $query->whereBetween('ep.data_expeditie', [$startDate, $endDate]);
        } elseif ($startDate) {
            //between start date and start date + 1 month 
            $query->whereDate('ep.data_expeditie', '>=', $startDate);
            $query->whereDate('ep.data_expeditie', '<=', date('Y-m-d', strtotime($startDate . ' + 1 month')));
        } elseif ($endDate) {
            //between end date - 1 month and end date
            $query->whereDate('ep.data_expeditie', '>=', date('Y-m-d', strtotime($endDate . ' - 1 month')));
            $query->whereDate('ep.data_expeditie', '<=', $endDate);
        }

        // Apply global search (q) — LIKE OR pe câmpurile cheie
        $q = trim((string) $request->input('q', ''));
        $minChars = config('awb.tabulator.min_chars_filter', 3);
        if ($q !== '' && mb_strlen($q) >= $minChars) {
            $isAwb = ToolsService::isAwb($q);
            $query->where(function ($sub) use ($q, $isAwb) {
                if ($isAwb) {
                    $sub->orWhere('ep.expeditie', '=', (int) $q);
                }
                $sub->orWhere('cle.nume', 'like', "%{$q}%")
                    ->orWhere('cld.nume', 'like', "%{$q}%")
                    ->orWhere('lce.nume_lc', 'like', "%{$q}%")
                    ->orWhere('lcd.nume_lc', 'like', "%{$q}%");
            });
        }

        // Apply filters
        foreach ($request->all() as $key => $value) {
            if (str_starts_with($key, 'filter_') && !empty($value)) {
                $field = substr($key, 7);
                //replace if with match for more complex filters
                match ($field) {
                    'expeditor_nume' => $query->where('cle.nume', 'like', "{$value}%"),
                    'expeditor_localitate' => $query->where('lce.nume_lc', 'like', "{$value}%"),
                    'destinatar_nume' => $query->where('cld.nume', 'like', "{$value}%"),
                    'destinatar_localitate' => $query->where('lcd.nume_lc', 'like', "{$value}%"),
                    'awb' => $query->where('ep.expeditie', 'like', "{$value}%"),
                    'tip_obj' =>
                        $query->where('ep.' . $field, '=', (int)$value),
                    'piese', 'greutate', 'ramburs' =>
                        $query->where('ep.' . $field, 'like', "%{$value}%"),
                    'data_expeditie' =>
                        $query->whereDate('ep.data_expeditie', '=', date('Y-m-d', strtotime($value))),
                    'data_status' =>
                        $query->whereDate('ep.data_op', '=', date('Y-m-d', strtotime($value))),
                    'status' =>
                        $query->where('ep.operatiune', 'like', "{$value}%"),
                    'ckp' => $query->where('ck.denumire', 'like', "{$value}%"),
                    'data_ckp' =>
                        $query->whereDate('ep.data_last_ckp', '=', date('Y-m-d', strtotime($value))),
                    'centru_ckp' => $query->where('ce.nume', 'like', "{$value}%"),
                    'judet' => $query->where('jd.cod_jd', '=', strtoupper(trim($value))),
                    default => $query->where($field, 'like', "{$value}%"),
                };
            }
        }
        // Apply sorting
        match ($sortField) {
            'expeditor_nume' => $query->orderBy('cle.nume', $sortOrder),
            'expeditor_localitate' => $query->orderBy('lce.nume_lc', $sortOrder),
            'destinatar_nume' => $query->orderBy('cld.nume', $sortOrder),
            'destinatar_localitate' => $query->orderBy('lcd.nume_lc', $sortOrder),
            'awb' => $query->orderBy('ep.expeditie', $sortOrder),
            'id' => $query->orderBy('ep.cod_expeditie', $sortOrder),
            'tip_obj', 'piese', 'greutate', 'ramburs', 'data_expeditie' =>
                $query->orderBy('ep.'.$sortField, $sortOrder),
            'data_status' => $query->orderBy('ep.data_op', $sortOrder),
            'status' => $query->orderBy('ep.operatiune', $sortOrder),
            'data_ckp' => $query->orderBy('ep.data_last_ckp', $sortOrder),
            'ckp' => $query->orderBy('ck.denumire', $sortOrder),
            'centru_ckp' => $query->orderBy('ce.nume', $sortOrder),
            default => $query->orderBy('ep.cod_expeditie', 'desc'),
        };
        //dd($query->toSql(), $query->getBindings());

        // Paginate
        $result = $query->paginate($rows, ['*'], 'page', $page);
        $items = collect($result->items())->map(function ($item) use ($user){
            return [
                'id' => $item->id,
                'awb' => $item->awb,
                'expeditor_nume' => $item->expeditor_nume,
                'expeditor_judet' => $item->expeditor_judet,
                'expeditor_localitate' => $item->expeditor_localitate,
                'expeditor_adresa' => $item->expeditor_adresa,
                'km_preluare' => $item->km_preluare,
                'expeditor_contact' => $item->expeditor_contact,
                'expeditor_telefon' => $item->expeditor_telefon,
                'expeditor_email' => $item->expeditor_email,
                'destinatar_nume' => $item->destinatar_nume,
                'destinatar_judet' => $item->destinatar_judet,
                'destinatar_localitate' => $item->destinatar_localitate,
                'destinatar_adresa' => $item->destinatar_adresa,
                'km_livrare' => $item->km_livrare,
                'km_exteriori' => $item->km_exteriori,
                'destinatar_contact' => $item->destinatar_contact,
                'destinatar_telefon' => $item->destinatar_telefon,
                'destinatar_email' => $item->destinatar_email,
                'data_expeditie' => $item->data_expeditie,
                'tip_obj' => $item->tip_obj,
                'piese' => $item->piese,
                'greutate' => number_format($item->greutate, 1, '.', ''),
                'greutate_vol' => number_format($item->greutate_vol, 1, '.', ''),
                'asigurare' => number_format($item->asigurare, 2, '.', ''),
                'ramburs' => number_format($item->ramburs, 2, '.', ''),
                'tip_plata' => $item->tip_plata,
                'ret_nt' => $item->ret_nt,
                'ret_nc' => !empty($item->extrainfo),
                'ret_doc' => $item->ret_doc,
                'ret_colet' => $item->ret_colet,
                'ret_amb' => $item->ret_amb,
                'liv_sed' => $item->liv_sed,
                'liv_samb' => $item->liv_samb,
                'copen' => $item->copen,
                'sms' => $item->sms,
                'valoare_fara_tva' => ($user->preturi ?? 0) == 1 ? number_format($item->valoare_fara_tva, 2, '.', '') : 'N/A',
                'valoare_tva' => ($user->preturi ?? 0) == 1 ? number_format($item->valoare_tva, 2, '.', '') : 'N/A',
                'platitor' => $item->platitor,
                'status' => $item->status,
                'data_status' => $item->data_status,
                'ckp' => $item->last_ckp,
                'data_ckp' => $item->data_last_ckp,
                'centru_ckp' => $item->centru_last_ckp,
                'primitor' => $item->primitor,
                'confirmare' => $item->confirmare,
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
     * Get AWB-uri retururi (returned AWBs) with pagination and filtering.
     */
    public function getRetururi(
        User $user,
        int $page = 1,
        int $rows = 100,
        string $sortField = 'id',
        string $sortOrder = 'desc',
        Request $request
    ): array
    {
        $pcs = collect(session('pcs', []));
        $pcIds = $pcs->reduce(function ($carry, $item) {
            $carry[] = $item['id'];
            return $carry;
        }, []);

        $cond = "";
        if(count($pcIds) > 1) {
			//maravet
			$cond = "ep.expeditor_id in (". implode(',', $pcIds) . ")";
		}
		else {
			$cond = "ep.expeditor_id = {$user->expeditor_id}";
		}

        $query = DB::table('exp_prelucrate as epr')
            ->select('epr.cod_expeditie as id', 'epr.expeditie as awb', 'epr.data_expeditie',
                    'epr.tip_exp', 'epr.referire', 'epr.valoare_asigurata as asigurare', 'epr.ramburs', 'ep.tip_plata',
                    'epr.ret_nt', 'epr.ret_doc', 'epr.ret_colet', 'epr.ret_amb', 'epr.liv_sed', 'epr.liv_samb', 'epr.copen', 'epr.sms',
                    'epr.valoare_totala_expeditie as valoare_fara_tva', 'epr.tva as valoare_tva', 
                    DB::raw('IF(ep.platitor_id = ep.expeditor_id, 1, 2) as platitor'), 'epr.mod_plata',
                    'epr.tip_obj', 'epr.piese', 'epr.greutate', 'ep.greutate_vol',
                    'cle.nume as expeditor_nume', 'lce.nume_lc as expeditor_localitate', 'je.nume_jd as expeditor_judet',
                    'cld.nume as destinatar_nume', 'lcd.nume_lc as destinatar_localitate', 'jd.nume_jd as destinatar_judet',
                    'cle.adresa as expeditor_adresa', 'cld.adresa as destinatar_adresa',
                    'ep.km_preluare', 'ep.km_livrare', DB::raw('(ep.km_preluare+ep.km_livrare) as km_exteriori'),
                    'ep.expeditor_contact', 'ep.expeditor_telefon', 'ep.expeditor_email',
                    'ep.destinatar_contact', 'ep.destinatar_telefon', 'ep.destinatar_email',
                    'ep.data_last_ckp', 'ce.nume as centru_last_ckp', 'ck.denumire as last_ckp',
                    'epr.operatiune as status', 'epr.data_op as data_status', 'epr.primitor', 'ec.folder as confirmare')
            ->join('exp_prelucrate as ep', function($join) {
                $join->on('epr.referire', '=', 'ep.expeditie')
                    ->where('ep.anulata', 0)
                    ->where('ep.tip_exp', 0)
                    ->where('ep.swapped', 0);
            })
            ->join('clienti as cle', 'epr.expeditor_id', '=', 'cle.cod_cl')
            ->join('localitati as lce', 'cle.cod_lc', '=', 'lce.cod_lc')
            ->leftJoin('judete as je', 'lce.cod_jd', '=', 'je.cod_jd')
            ->join('clienti as cld', 'epr.destinatar_id', '=', 'cld.cod_cl')
            ->join('localitati as lcd', 'cld.cod_lc', '=', 'lcd.cod_lc')
            ->leftJoin('judete as jd', 'lcd.cod_jd', '=', 'jd.cod_jd')
            ->leftJoin('exp_confirmari as ec', 'epr.expeditie', '=', 'ec.expeditie')
            ->leftJoin('centre as ce', 'epr.centru_last_ckp', '=', 'ce.id')
            ->leftJoin('checkpoints as ck', 'epr.last_ckp', '=', 'ck.id')
            ->where('epr.anulata', 0)
            ->whereNotIn('epr.tip_exp', [0, 3, 33]) //exclude initiala, ramburs, borderou ramburs
            ->whereRaw($cond)
            ->groupBy('epr.cod_expeditie');

        // Apply date range filter
        $startDate = $request->input('startDate');
        $endDate = $request->input('endDate');
        
        if ($startDate && $endDate) {
            //if startDate is after endDate, swap them
            if($startDate > $endDate) {
                $temp = $startDate;
                $startDate = $endDate;
                $endDate = $temp;
            }
            $query->whereBetween('epr.data_expeditie', [$startDate, $endDate]);
        } elseif ($startDate) {
            //between start date and start date + 1 month 
            $query->whereDate('epr.data_expeditie', '>=', $startDate);
            $query->whereDate('epr.data_expeditie', '<=', date('Y-m-d', strtotime($startDate . ' + 1 month')));
        } elseif ($endDate) {
            //between end date - 1 month and end date
            $query->whereDate('epr.data_expeditie', '>=', date('Y-m-d', strtotime($endDate . ' - 1 month')));
            $query->whereDate('epr.data_expeditie', '<=', $endDate);
        }

        // Apply global search (q) — LIKE OR pe câmpurile cheie
        $q = trim((string) $request->input('q', ''));
        $minChars = config('awb.tabulator.min_chars_filter', 3);
        if ($q !== '' && mb_strlen($q) >= $minChars) {
            $isAwb = ToolsService::isAwb($q);
            $query->where(function ($sub) use ($q, $isAwb) {
                if ($isAwb) {
                    $sub->orWhere('epr.expeditie', '=', (int) $q);
                }
                $sub->orWhere('cle.nume', 'like', "%{$q}%")
                    ->orWhere('cld.nume', 'like', "%{$q}%")
                    ->orWhere('lce.nume_lc', 'like', "%{$q}%")
                    ->orWhere('lcd.nume_lc', 'like', "%{$q}%");
            });
        }

        //dd($query->toSql(), $query->getBindings());

        // Apply filters
        foreach ($request->all() as $key => $value) {
            if (str_starts_with($key, 'filter_') && !empty($value)) {
                $field = substr($key, 7);
                //replace if with match for more complex filters
                match ($field) {
                    'expeditor_nume' => $query->where('cle.nume', 'like', "{$value}%"),
                    'expeditor_localitate' => $query->where('lce.nume_lc', 'like', "{$value}%"),
                    'destinatar_nume' => $query->where('cld.nume', 'like', "{$value}%"),
                    'destinatar_localitate' => $query->where('lcd.nume_lc', 'like', "{$value}%"),
                    'awb' => $query->where('epr.expeditie', 'like', "{$value}%"),
                    'referire' => $query->where('epr.referire', 'like', "{$value}%"),
                    'tip_obj' =>
                        $query->where('epr.' . $field, '=', (int)$value),
                    'piese', 'greutate' =>
                        $query->where('epr.' . $field, 'like', "%{$value}%"),
                    'data_expeditie' =>
                        $query->whereDate('epr.data_expeditie', '=', date('Y-m-d', strtotime($value))),
                    'data_status' =>
                        $query->whereDate('epr.data_op', '=', date('Y-m-d', strtotime($value))),
                    'status' =>
                        $query->where('epr.operatiune', 'like', "{$value}%"),
                    'data_ckp' =>
                        $query->whereDate('ep.data_last_ckp', '=', date('Y-m-d', strtotime($value))),
                    'ckp' => $query->where('ck.denumire', 'like', "{$value}%"),
                    'centru_ckp' => $query->where('ce.nume', 'like', "{$value}%"),
                    'judet' => $query->where('jd.cod_jd', '=', strtoupper(trim($value))),
                    default => $query->where($field, 'like', "{$value}%"),
                };
            }
        }
        // Apply sorting
        match ($sortField) {
            'expeditor_nume' => $query->orderBy('cle.nume', $sortOrder),
            'expeditor_localitate' => $query->orderBy('lce.nume_lc', $sortOrder),
            'destinatar_nume' => $query->orderBy('cld.nume', $sortOrder),
            'destinatar_localitate' => $query->orderBy('lcd.nume_lc', $sortOrder),
            'awb' => $query->orderBy('epr.expeditie', $sortOrder),
            'id' => $query->orderBy('epr.cod_expeditie', $sortOrder),
            'referire' => $query->orderBy('epr.referire', $sortOrder),
            'tip_obj', 'piese', 'greutate', 'data_expeditie' => 
                $query->orderBy('epr.'.$sortField, $sortOrder),
            'data_status' => $query->orderBy('epr.data_op', $sortOrder),
            'status' => $query->orderBy('epr.operatiune', $sortOrder),
            'ckp' => $query->orderBy('ck.denumire', $sortOrder),
            'data_ckp' => $query->orderBy('ep.data_last_ckp', $sortOrder),
            'centru_ckp' => $query->orderBy('ce.nume', $sortOrder),
            default => $query->orderBy('epr.cod_expeditie', 'desc'),
        };
        //dd($query->toSql(), $query->getBindings());

        // Paginate
        $result = $query->paginate($rows, ['*'], 'page', $page);
        $items = collect($result->items())
            ->map(function ($item) {
            return [
                'id' => $item->id,
                'awb' => $item->awb,
                'tip_exp' => $item->tip_exp,
                'referire' => $item->referire,
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
                'km_exteriori' => $item->km_exteriori,
                'data_expeditie' => $item->data_expeditie,
                'tip_obj' => $item->tip_obj,
                'piese' => $item->piese,
                'greutate' => number_format($item->greutate, 1, '.', ''),
                'greutate_vol' => number_format($item->greutate_vol, 1, '.', ''),
                'asigurare' => number_format($item->asigurare, 2, '.', ''),
                'ramburs' => number_format($item->ramburs, 2, '.', ''),
                'tip_plata' => $item->tip_plata,
                'ret_nt' => $item->ret_nt,
                'ret_nc' => false, //nu exista in retururi, doar la initiale
                'ret_doc' => $item->ret_doc,
                'ret_colet' => $item->ret_colet,
                'ret_amb' => $item->ret_amb,
                'liv_sed' => $item->liv_sed,
                'liv_samb' => $item->liv_samb,
                'copen' => $item->copen,
                'sms' => $item->sms,
                'valoare_fara_tva' => ($user->preturi ?? 0) == 1 ? number_format($item->valoare_fara_tva, 2, '.', '') : 'N/A',
                'valoare_tva' => ($user->preturi ?? 0) == 1 ? number_format($item->valoare_tva, 2, '.', '') : 'N/A',
                'platitor' => $item->platitor,
                'status' => $item->status,
                'data_status' => $item->data_status,
                'ckp' => $item->last_ckp,
                'data_ckp' => $item->data_last_ckp,
                'centru_ckp' => $item->centru_last_ckp,
                'primitor' => $item->primitor,
                'confirmare' => $item->confirmare,
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

    public function markAwbsAsPrinted(array $ids, int $userId): bool
    {
        $updated = DB::table('exp_prelucrate')
            ->whereIn('cod_expeditie', $ids)
            ->where('printed_by', 0)
            ->update([
                'printed_at' => now(),
                'printed_by' => $userId,
            ]);
        return $updated == count($ids);
    }

    /**
     * Get aggregated stats (count, total weight, total ramburs) for a tab + filters.
     *
     * @param string $tab 'nepredate'|'predate'|'retururi'
     */
    /**
     * Build the expeditor scope SQL condition for a given table alias.
     * For 'retururi', use alias 'epr' — no swapped logic (always expeditor_id).
     *
     * @param string $alias Table alias (default 'ep')
     */
    private function getExpeditorScopeCondition(User $user, string $alias = 'ep'): string
    {
        $pcs = collect(session('pcs', []));
        $pcIds = $pcs->reduce(function ($carry, $item) {
            $carry[] = $item['id'];
            return $carry;
        }, []);

        if ($user->expeditor_id == 171350 || count($pcIds) > 1) {
            // maravet / multi-pc
            return "IF({$alias}.swapped = 0, {$alias}.expeditor_id in (" . implode(',', $pcIds) . "), {$alias}.destinatar_id in (" . implode(',', $pcIds) . "))";
        }

        return "IF({$alias}.swapped = 0, {$alias}.expeditor_id = {$user->expeditor_id}, {$alias}.destinatar_id = {$user->expeditor_id})";
    }

    public function getStats(
        Request $request,
        User $user,
        string $tab,
        bool $swapped = false,
    ): array {
        $cond = $this->getExpeditorScopeCondition($user);

        $query = DB::table('exp_prelucrate as ep')
            ->join('clienti as cle', 'ep.expeditor_id', '=', 'cle.cod_cl')
            ->join('localitati as lce', 'cle.cod_lc', '=', 'lce.cod_lc')
            ->leftJoin('judete as je', 'lce.cod_jd', '=', 'je.cod_jd')
            ->join('clienti as cld', 'ep.destinatar_id', '=', 'cld.cod_cl')
            ->join('localitati as lcd', 'cld.cod_lc', '=', 'lcd.cod_lc')
            ->leftJoin('judete as jd', 'lcd.cod_jd', '=', 'jd.cod_jd')
            ->where('ep.tip_exp', 0)
            ->where('ep.anulata', 0);

        // Tab-specific WHERE
        if ($tab === 'nepredate') {
            if (! $swapped) {
                $query->where('ep.swapped', 0);
            }
            $query->where('ep.borderou_id', 0)
                  ->where('ep.idfact', 0)
                  ->whereNotExists(function ($subQuery) {
                      $subQuery->select(DB::raw(1))
                          ->from('decont_expeditii as dee')
                          ->join('decont_facturi as dfa', function ($join) {
                              $join->on('dee.factura_id', '=', 'dfa.id')
                                   ->where('dfa.anulata', 0);
                          })
                          ->whereRaw('dee.expeditie = ep.expeditie')
                          ->where('dee.anulata', 0);
                  })
                  ->whereNotExists(function ($subQuery) {
                      $subQuery->select(DB::raw(1))
                          ->from('scanari_coduri as sc')
                          ->whereRaw('sc.expeditie = ep.expeditie');
                  })
                  ->whereRaw($cond);
        } elseif ($tab === 'predate') {
            // Replică WHERE-urile din getPredate — minim: scope + există scanare/predare
            $query->whereExists(function ($sub) {
                $sub->select(DB::raw(1))
                    ->from('scanari_coduri as sc')
                    ->whereRaw('sc.expeditie = ep.expeditie');
            })->whereRaw($cond);
        } elseif ($tab === 'retururi') {
            // Replică WHERE-urile din getRetururi
            $query->where(function ($sub) {
                $sub->where('ep.ret_nt', 1)
                    ->orWhere('ep.ret_doc', 1)
                    ->orWhere('ep.ret_colet', 1)
                    ->orWhere('ep.ret_amb', 1);
            })->whereRaw($cond);
        }

        // Apply date range filter (inline pattern)
        $startDate = $request->input('startDate');
        $endDate = $request->input('endDate');

        if ($startDate && $endDate) {
            //if startDate is after endDate, swap them
            if ($startDate > $endDate) {
                $temp = $startDate;
                $startDate = $endDate;
                $endDate = $temp;
            }
            $query->whereBetween('ep.data_expeditie', [$startDate, $endDate]);
        } elseif ($startDate) {
            //between start date and start date + 1 month
            $query->whereDate('ep.data_expeditie', '>=', $startDate);
            $query->whereDate('ep.data_expeditie', '<=', date('Y-m-d', strtotime($startDate . ' + 1 month')));
        } elseif ($endDate) {
            //between end date - 1 month and end date
            $query->whereDate('ep.data_expeditie', '>=', date('Y-m-d', strtotime($endDate . ' - 1 month')));
            $query->whereDate('ep.data_expeditie', '<=', $endDate);
        }

        // Global search q
        $q = trim((string) $request->input('q', ''));
        $minChars = config('awb.tabulator.min_chars_filter', 3);
        if ($q !== '' && mb_strlen($q) >= $minChars) {
            $isAwb = ToolsService::isAwb($q);
            $query->where(function ($sub) use ($q, $isAwb) {
                if ($isAwb) {
                    $sub->orWhere('ep.expeditie', '=', (int) $q);
                }
                $sub->orWhere('cle.nume', 'like', "%{$q}%")
                    ->orWhere('cld.nume', 'like', "%{$q}%")
                    ->orWhere('lce.nume_lc', 'like', "%{$q}%")
                    ->orWhere('lcd.nume_lc', 'like', "%{$q}%");
            });
        }

        // filter_* (judet, tip_obj)
        foreach ($request->all() as $key => $value) {
            if (str_starts_with($key, 'filter_') && ! empty($value)) {
                $field = substr($key, 7);
                match ($field) {
                    'judet' => $query->where('jd.cod_jd', '=', strtoupper(trim($value))),
                    'tip_obj' => $query->where('ep.tip_obj', '=', (int) $value),
                    'destinatar_nume' => $query->where('cld.nume', 'like', "{$value}%"),
                    'destinatar_localitate' => $query->where('lcd.nume_lc', 'like', "{$value}%"),
                    default => null,
                };
            }
        }

        $row = $query->selectRaw('
            COUNT(DISTINCT ep.cod_expeditie) as count,
            COALESCE(SUM(ep.greutate), 0) as total_weight,
            COALESCE(SUM(ep.ramburs), 0) as total_ramburs
        ')->first();

        return [
            'count' => (int) ($row->count ?? 0),
            'total_weight' => round((float) ($row->total_weight ?? 0), 2),
            'total_ramburs' => round((float) ($row->total_ramburs ?? 0), 2),
        ];
    }

    /**
     * Get distinct status values (operatiune) for a tab.
     *
     * @param string $tab 'predate'|'retururi'
     */
    public function getStatuses(User $user, string $tab): array
    {
        $cond = $this->getExpeditorScopeCondition($user);

        if ($tab === 'predate') {
            $query = DB::table('exp_prelucrate as ep')
                ->select(DB::raw('DISTINCT ep.operatiune as status'))
                ->whereNotNull('ep.operatiune')
                ->where('ep.operatiune', '!=', '')
                ->where('ep.tip_exp', 0)
                ->where('ep.anulata', 0)
                ->whereExists(function ($sub) {
                    $sub->select(DB::raw(1))
                        ->from('scanari_coduri as sc')
                        ->whereRaw('sc.expeditie = ep.expeditie');
                })
                ->whereRaw($cond);
        } elseif ($tab === 'retururi') {
            $condEpr = $this->getExpeditorScopeCondition($user, 'epr');
            $query = DB::table('exp_prelucrate as epr')
                ->select(DB::raw('DISTINCT epr.operatiune as status'))
                ->whereNotNull('epr.operatiune')
                ->where('epr.operatiune', '!=', '')
                ->where(function ($sub) {
                    $sub->where('epr.ret_nt', 1)
                        ->orWhere('epr.ret_doc', 1)
                        ->orWhere('epr.ret_colet', 1)
                        ->orWhere('epr.ret_amb', 1);
                })
                ->whereRaw($condEpr);
        } else {
            return [];
        }

        return $query->orderBy('status')->pluck('status')->filter()->values()->toArray();
    }
}
