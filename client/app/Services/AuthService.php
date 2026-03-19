<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class AuthService
{
    /**
     * push session on login
     */
    public function login(User $user): void
    {
        $expeditor = DB::table('clienti as cle')
            ->leftJoin('zones as clez', 'clez.id', '=', 'cle.zona_id')
            ->leftJoin('centre as clec', 'clec.id', '=', 'clez.centru_id')
            ->join('localitati as lce', 'cle.cod_lc', '=', 'lce.cod_lc')
            ->leftJoin('centre as cee', 'lce.cod_centru', '=', 'cee.id')
            ->leftJoin('clienti as clem', 'clem.cod_cl', '=', 'cle.master')
            ->leftJoin('tarife as clet', 'clet.id_cl', '=', 'cle.cod_cl')
            ->leftJoin('tarife as cmet', 'cmet.id_cl', '=', 'clem.cod_cl')
            ->leftJoin('users as u', 'u.expeditor_id', '=', 'cle.cod_cl')
            ->where('cle.cod_cl', $user->expeditor_id)
            ->where('cle.activ', 1)
            ->where('cle.sters', 0)
            ->selectRaw('lce.nume_lc as expeditor_localitate, lce.cod_jd as expeditor_judet, lce.dist_km as expeditor_localitate_km,
                cle.cod_cl as expeditor_id, cle.master as expeditor_master_id, cle.cod_lc as expeditor_localitate_id, 
                IF(cle.zona_id > 0 and clec.id > 0, clec.id, lce.cod_centru) as expeditor_centru_id,
                cle.nume as expeditor_nume, cle.adresa as expeditor_adresa, cle.tarif as expeditor_contract, cle.mod_plata as expeditor_mod_plata,
                cle.cc as expeditor_cc, cle.icc as expeditor_icc, cle.cod_fiscal as expeditor_cui, cle.reg_com as expeditor_j,
                IF(cle.zona_id > 0 and clec.id > 0, clec.nume, cee.nume) as expeditor_centru, cle.not_print_phone as expeditor_not_print_phone,
                IF(cle.zona_id > 0 and clec.id > 0, clec.label, cee.label) as expeditor_centru_cod,
                cle.tarif_individual as expeditor_tarif_individual, cle.activ as expeditor_activ,
                clem.nume as expeditor_master, clem.tarif as expeditor_master_contract, clem.mod_plata as expeditor_master_mod_plata, clem.not_print_phone as expeditor_master_not_print_phone,
                clem.cc as expeditor_master_cc, clem.activ as expeditor_master_activ, clem.cod_fiscal as expeditor_master_cui, clem.reg_com as expeditor_master_j,
                clet.taxa_destinatie as expeditor_taxa_destinatie, clet.taxa_expediere as expeditor_taxa_expediere, clet.ret_amb as expeditor_ret_amb, clet.kg_ret_amb expeditor_kg_ret_amb,
                clet.tarif_sms as expeditor_tarif_sms, cmet.taxa_destinatie as expeditor_master_taxa_destinatie, cmet.taxa_expediere as expeditor_master_taxa_expediere, cmet.ret_amb as expeditor_master_ret_amb,
                cmet.kg_ret_amb as expeditor_master_kg_ret_amb, cmet.tarif_sms as expeditor_master_tarif_sms, cle.zona_id as expeditor_zona_id, cee.geocode as expeditor_geocode,
                group_concat(u.id separator ",") as users_ids,
                (SELECT COUNT(tcle.ID)
                    	from tarife tcle
                        LEFT JOIN tarife_det tdcle ON tdcle.id_tarife = tcle.id
                        LEFT JOIN tarife_g tgcle ON tgcle.id_tarife_det = tdcle.id
                        WHERE tgcle.tip = 1  and tcle.id_cl = cle.cod_cl) as expeditor_tarif_palet,
                (SELECT COUNT(tclem.id)
                    	from tarife tclem
                        LEFT JOIN tarife_det tdclem ON tdclem.id_tarife = tclem.id
                        LEFT JOIN tarife_g tgclem ON tgclem.id_tarife_det = tdclem.id
                        WHERE tgclem.tip = 1  and tclem.id_cl = clem.cod_cl) as expeditor_master_tarif_palet
            ')
            ->groupBy('cle.cod_cl')
            ->first();
        if($expeditor == null) {
            Log::error('Login failed for user id ' . $user->id . ' : expeditor not found for expeditor_id ' . $user->expeditor_id);
            $this->logout();
            return;
        }
        $is_master = ($expeditor->expeditor_master_id == 0 || $expeditor->expeditor_master_id == $user->expeditor_id);
        $master_id = $is_master ? $expeditor->expeditor_id : $expeditor->expeditor_master_id;
        $can_pcs = $is_master && $user->selectie_puncte_de_lucru == 1;
        //Log::debug('Login : '. $user->id . ' : ' . $master_id . ' : ' . $user->expeditor_id . ' : ' . print_r(config('awb.can_update_after_print_masters', []), true) . ' : ' . intval(in_array($master_id, array_values(config('awb.can_update_after_print_masters', [])))));
        $can_update_after_print = in_array($master_id, config('awb.can_update_after_print_masters', [])) ? 1 : 0 && ($is_master ? $expeditor->expeditor_mod_plata : ($expeditor->expeditor_master_mod_plata ?? 0));
        $master_pc = [
            'id' => $expeditor->expeditor_id,
            'nume' => $expeditor->expeditor_nume,
            'adresa' => $expeditor->expeditor_adresa,
            'contact' => $user->nume,
            'telefon' => $user->telefon,
            'email' => $user->email,
            'localitate' => $expeditor->expeditor_localitate,
            'localitate_id' => $expeditor->expeditor_localitate_id,
            'judet' => $expeditor->expeditor_judet,
            'localitate_km' => $expeditor->expeditor_localitate_km,
            'users_ids' => !empty($expeditor->users_ids) ? explode(',', $expeditor->users_ids) : [],
        ];
        session([
            'id' => $expeditor->expeditor_id,
            'master_id' => $master_id,
            'nume' => $expeditor->expeditor_nume,
            'localitate' => $expeditor->expeditor_localitate,
            'localitate_id' => $expeditor->expeditor_localitate_id,
            'localitate_km' => $expeditor->expeditor_localitate_km,
            'judet' => $expeditor->expeditor_judet ?? '',
            'centru_id' => $expeditor->expeditor_centru_id ?? 0,
            'centru' => $expeditor->expeditor_centru ?? '',
            'centru_cod' => $expeditor->expeditor_centru_cod ?? '',
            'adresa' => $expeditor->expeditor_adresa,
            'cc' => $is_master ? $expeditor->expeditor_cc ?? 0 : $expeditor->expeditor_master_cc ?? 0,
            'tarif_individual' => $expeditor->expeditor_tarif_individual ?? 0,
            'mod_plata' => $is_master ? $expeditor->expeditor_mod_plata : $expeditor->expeditor_master_mod_plata ?? 0,
            'cui' => $is_master ? $expeditor->expeditor_cui : $expeditor->expeditor_master_cui ?? '',
            'orc' => $is_master ? $expeditor->expeditor_j : $expeditor->expeditor_master_j ?? '',
            'activ' => $is_master ? $expeditor->expeditor_activ : $expeditor->expeditor_master_activ,
            'taxa_destinatie' => $is_master ? $expeditor->expeditor_taxa_destinatie : $expeditor->expeditor_master_taxa_destinatie,
            'taxa_expediere' => $is_master ? $expeditor->expeditor_taxa_expediere : $expeditor->expeditor_master_taxa_expediere,
            'ret_amb' => $is_master ? $expeditor->expeditor_ret_amb : $expeditor->expeditor_master_ret_amb,
            'kg_ret_amb' => $is_master ? $expeditor->expeditor_kg_ret_amb : $expeditor->expeditor_master_kg_ret_amb,
            'tarif_sms' => $is_master ? $expeditor->expeditor_tarif_sms : $expeditor->expeditor_master_tarif_sms,
            'tarif_palet' => $is_master ? $expeditor->expeditor_tarif_palet : $expeditor->expeditor_master_tarif_palet,
            'contract' => $is_master ? $expeditor->expeditor_contract : $expeditor->expeditor_master_contract,
            'can_update_after_print' => $can_update_after_print,
            'not_print_phone' => $is_master ? $expeditor->expeditor_not_print_phone : $expeditor->expeditor_master_not_print_phone,
        ]);
        if(session('activ', 0) == 0) {
            $this->logout();
        }

        if($can_pcs) {
            $query_pcs = DB::table('clienti as cl')
                ->join('localitati as lc', 'lc.cod_lc', '=', 'cl.cod_lc')
                ->join('judete as j', 'j.cod_jd', '=', 'lc.cod_jd')
                ->leftJoin('users as u', 'u.expeditor_id', '=', 'cl.cod_cl')
                ->selectRaw('cl.cod_cl as pc_id, cl.nume as pc_nume, cl.adresa as pc_adresa,
                    lc.nume_lc as pc_localitate, cl.cod_lc as pc_localitate_id, lc.dist_km as pc_localitate_km, j.nume_jd as pc_judet,
                    group_concat(u.id separator ",") as pc_users_ids')
                ->where('cl.master', $user->expeditor_id)
                ->whereRaw('cl.master != cl.cod_cl')
                ->where('cl.activ', 1)
                ->where('cl.sters', 0)
                ->groupBy('cl.cod_cl')
                ->orderBy('cl.nume', 'asc');
            //dd($query_pcs->toSql(), $query_pcs->getBindings());
            $pcs = $query_pcs
                ->get()
                ->map(function($pc) use ($user) {
                    return [
                        'id' => $pc->pc_id,
                        'nume' => $pc->pc_nume,
                        'adresa' => $pc->pc_adresa,
                        'contact' => $user->nume,
                        'telefon' => $user->telefon,
                        'email' => $user->email,
                        'localitate' => $pc->pc_localitate,
                        'localitate_id' => $pc->pc_localitate_id,
                        'judet' => $pc->pc_judet,
                        'localitate_km' => $pc->pc_localitate_km,
                        'users_ids' => !empty($pc->pc_users_ids) ? explode(',', $pc->pc_users_ids) : [],
                    ];
                })
                ->toArray();
            //dd($pcs);
            if(count($pcs) == 0) {
                session(['pcs' => [$master_pc]]);
                return;
            }
            session(['pcs' => array_merge([$master_pc], $pcs)]);
            return;
        }
        session(['pcs' => [$master_pc]]);
    }

    /**
     * clear session on logout
     */
    public function logout(): void
    {
        session()->invalidate();
        session()->regenerateToken();
    }
}
