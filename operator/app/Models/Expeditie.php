<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class Expeditie extends Model
{
    protected $table = 'exp_prelucrate';
    protected $primaryKey = 'cod_expeditie';
    public $timestamps = false;

    // Whitelist coloane permise pentru sortare (protecție SQL injection)
    public const SORT_WHITELIST = [
        'ep.expeditie', 'ep.data_expeditie', 'cle.nume', 'cld.nume',
        'ep.greutate', 'ep.ramburs', 'ep.valoare_totala_expeditie',
        'ep.piese', 'ep.colete', 'ep.paleti', 'ep.km_preluare', 'ep.km_livrare',
    ];

    /**
     * Scope cu toate filtrele din pagina expeditii/cautare.
     * Dacă niciun filtru nu e activ, returnează 0 rezultate (WHERE 1=2).
     *
     * BUSINESS RULE: Expeditiile anulate (anulata=1) sunt excluse permanent.
     * Operatorii nu pot căuta expeditii anulate prin această pagină — regulă de business intenționată.
     */
    public function scopeWithFilters(Builder $query, array $filters): Builder
    {
        $query->from('exp_prelucrate as ep')
            ->leftJoin('agenti as agp', 'agp.cod_ag', '=', 'ep.curier_preluare_id')
            ->leftJoin('agenti as agl', 'agl.cod_ag', '=', 'ep.curier_livrare_id')
            ->leftJoin('clienti as cle', 'cle.cod_cl', '=', 'ep.expeditor_id')
            ->leftJoin('clienti as cld', 'cld.cod_cl', '=', 'ep.destinatar_id')
            ->leftJoin('zones as clez', 'clez.id', '=', 'cle.zona_id')
            ->leftJoin('centre as clec', 'clec.id', '=', 'clez.centru_id')
            ->leftJoin('zones as cldz', 'cldz.id', '=', 'cld.zona_id')
            ->leftJoin('centre as cldc', 'cldc.id', '=', 'cldz.centru_id')
            ->leftJoin('localitati as lce', 'lce.cod_lc', '=', 'cle.cod_lc')
            ->leftJoin('localitati as lcd', 'lcd.cod_lc', '=', 'cld.cod_lc')
            ->leftJoin('centre as cee', 'cee.id', '=', 'lce.cod_centru')
            ->leftJoin('centre as ced', 'ced.id', '=', 'lcd.cod_centru')
            ->leftJoin('exp_facturi as b', 'b.id', '=', 'ep.idfact')
            ->leftJoin('users as u', 'u.id', '=', 'ep.operator_id')
            ->where('ep.anulata', 0)
            ->select([
                'ep.expeditie', 'ep.tip_exp',
                'cle.nume as expeditor', 'cld.nume as destinatar',
                'lce.nume_lc as expeditor_localitate', 'lcd.nume_lc as destinatar_localitate',
                DB::raw('IF(cle.zona_id > 0 AND clec.id > 0, clec.nume, cee.nume) as expeditor_centru'),
                DB::raw('IF(cld.zona_id > 0 AND cldc.id > 0, cldc.nume, ced.nume) as destinatar_centru'),
                'ep.data_expeditie', 'ep.tip_obj',
                'ep.piese', 'ep.plicuri', 'ep.colete', 'ep.paleti',
                'ep.greutate', 'ep.km_preluare', 'ep.km_livrare',
                'ep.valoare_asigurata', 'ep.ramburs', 'ep.tip_plata',
                'ep.valoare_totala_expeditie', 'ep.mod_plata',
                'agp.nume_ag as curier_preluare', 'agl.nume_ag as curier_livrare',
                'u.user as operator', 'ep.observatii',
                'ep.liv_samb',
            ]);

        $flag = false;

        // Filtru: operatiune (1=nr.exp, 3=factura) + valoare
        $operatiune = intval($filters['operatiune'] ?? 0);
        $search = trim($filters['search'] ?? '');
        if ($operatiune && $search !== '') {
            if ($operatiune === 1) {
                $query->where('ep.expeditie', intval($search));
            } elseif ($operatiune === 3) {
                $query->where('b.invoice', 'like', '%' . $search . '%');
            }
            $flag = true;
        }

        // Filtru: perioada
        if (!empty($filters['data_start']) && !empty($filters['data_final'])) {
            try {
                $start = \Carbon\Carbon::createFromFormat('d.m.Y', $filters['data_start'])->format('Y-m-d');
                $final = \Carbon\Carbon::createFromFormat('d.m.Y', $filters['data_final'])->format('Y-m-d');
                $query->whereBetween('ep.data_expeditie', [$start, $final]);
                $flag = true;
            } catch (\Exception $e) {
                // Data invalidă — ignorăm filtrul de perioadă
            }
        }

        // Filtru: operator
        if (!empty($filters['operator'])) {
            $query->where('u.user', $filters['operator']);
            $flag = true;
        }

        // Filtru: livrare (1=toate, 2=sambata, 4=sediu)
        $livrare = intval($filters['livrare'] ?? 0);
        if ($livrare === 2) {
            $query->where('ep.liv_samb', 1);
            $flag = true;
        } elseif ($livrare === 4) {
            $query->where('ep.liv_sed', 1);
            $flag = true;
        }

        // Filtru: expeditor localitate
        if (!empty($filters['expeditor_localitate'])) {
            $query->where('lce.nume_lc', $filters['expeditor_localitate']);
            $flag = true;
        }

        // Filtru: expeditor nume
        if (!empty($filters['expeditor'])) {
            $query->where('cle.nume', $filters['expeditor']);
            $flag = true;
        }

        // Filtru: destinatar localitate
        if (!empty($filters['destinatar_localitate'])) {
            $query->where('lcd.nume_lc', $filters['destinatar_localitate']);
            $flag = true;
        }

        // Filtru: destinatar nume
        if (!empty($filters['destinatar'])) {
            $query->where('cld.nume', $filters['destinatar']);
            $flag = true;
        }

        // Filtru: curier preluare (by name - agp.nume_ag)
        if (!empty($filters['curier_preluare'])) {
            $query->where('agp.nume_ag', $filters['curier_preluare']);
            $flag = true;
        }

        // Filtru: curier livrare (by name - agl.nume_ag)
        if (!empty($filters['curier_livrare'])) {
            $query->where('agl.nume_ag', $filters['curier_livrare']);
            $flag = true;
        }

        // Filtru: expeditii bulk (lista de numere separate cu virgulă)
        if (!empty($filters['expeditii'])) {
            $ids = array_filter(
                array_map('intval', preg_split('/[\s,]+/', $filters['expeditii'])),
                fn($v) => $v > 0
            );
            if (!empty($ids)) {
                $query->whereIn('ep.expeditie', $ids);
                $flag = true;
            }
        }

        // Garda: dacă niciun filtru nu e activ → 0 rezultate
        if (!$flag) {
            $query->whereRaw('1=2');
        }

        return $query;
    }
}
