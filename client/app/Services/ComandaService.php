<?php

namespace App\Services;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\User;
use Illuminate\Support\Facades\Log;

class ComandaService
{
    /**
     * Create a new comanda
     */
    public function create(array $data, $user_id): int
    {
        Log::debug('Creating comanda with data: ' . json_encode($data) . ' for user_id: ' . $user_id);
         try {
             DB::beginTransaction();
             $id =  DB::table('comenzi')->insertGetId([
                 'created_at' => now(),
                 'created_by' => $user_id,
                 'collect_at' => $data['data_colectare_string'],
                 'h_start' => $data['interval_colectare_start'],
                 'h_end' => $data['interval_colectare_end'],
                 'client' => !empty($data['ridica_de_la']) ? $data['ridica_de_la'] : $data['expeditor_nume'],
                 'client_id' => $data['expeditor_id'],
                 'localitate_id' => $data['expeditor_localitate_id'],
                 'adresa' => $data['expeditor_adresa'],
                 'contact' => $data['expeditor_contact'] ?? '',
                 'telefon' => $data['expeditor_telefon'] ?? '',
                 'email' => $data['expeditor_email'] ?? '',
                 'nr_obj_colet' => $data['colete'] ?? 0,
                 'nr_obj_palet' => $data['paleti'] ?? 0,
                 'kg_obj' => $data['greutate'],
                 'vol_obj' => $data['volum'] ?? 0,
                 'observatii' => $data['observatii'] ?? '',
             ]);
             DB::table('comenzi_history')->insert([
                 'comanda_id' => $id,
                 'status' => 1,
                 'created_at' => now(),
                 'created_by' => $user_id,
             ]);
             DB::commit();
             return $id;
         } catch (\Exception $e) {
             DB::rollBack();
             Log::error('Error creating comanda: ' . $e->getMessage());
             throw new \Exception($e->getMessage());
         }
        try {
            //DB::enableQueryLog();
            DB::beginTransaction();
            $id =  DB::table('comenzi')->insertGetId([
                'created_at' => now(),
                'created_by' => $user_id,
                'collect_at' => $data['data_colectare_string'],
                'h_start' => $data['interval_colectare_start'],
                'h_end' => $data['interval_colectare_end'],
                'client' => !empty($data['ridica_de_la']) ? $data['ridica_de_la'] : $data['expeditor_nume'],
                'client_id' => $data['expeditor_id'],
                'localitate_id' => $data['expeditor_localitate_id'],
                'adresa' => $data['expeditor_adresa'],
                'contact' => $data['expeditor_contact'] ?? '',
                'telefon' => $data['expeditor_telefon'] ?? '',
                'email' => $data['expeditor_email'] ?? '',
                'nr_obj_colet' => $data['colete'] ?? 0,
                'nr_obj_palet' => $data['paleti'] ?? 0,
                'kg_obj' => $data['greutate'],
                'vol_obj' => $data['volum'] ?? 0,
                'observatii' => $data['observatii'] ?? '',
                'created_at' => now(),
            ]);
            DB::table('comenzi_history')->insert([
                'comanda_id' => $id,
                'status' => 1,
                'created_at' => now(),
                'created_by' => $user_id,
            ]);
            DB::commit();
            //dd(DB::getRawQueryLog());
            //DB::disableQueryLog();
            return $id;
        } catch (\Exception $e) {
            DB::rollBack();
            throw new \Exception($e->getMessage());
        }
        return 0;
    }

    /**
     * Get a single comanda by ID
     */
    public function getById(int $expeditor_id, int $id): array
    {
        if($id == 0) return [];
        $pcs = collect(session('pcs', []));
        $pcs_ids = $pcs->reduce(function ($carry, $item) {
            $carry[] = $item['id'];
            return $carry;
        }, []);
        $item = DB::table('comenzi as c')
            ->select('c.id', 'c.localitate_id as expeditor_localitate_id', 
            'c.client as ridica_de_la', 'cl.nume as expeditor_nume', 'c.client_id as expeditor_id', 'c.adresa as expeditor_adresa', 
            'c.contact as expeditor_contact', 'c.telefon as expeditor_telefon', 
            'c.email as expeditor_email', 'lcd.nume_lc as expeditor_localitate', 'je.nume_jd as expeditor_judet',
            'c.h_start as interval_colectare_start', 'c.h_end as interval_colectare_end', 
            'c.collect_at as data_colectare_string', 
            'c.nr_obj_colet as colete', 'c.nr_obj_palet as paleti', 'c.kg_obj as greutate', 'c.vol_obj as volum', 'c.observatii')
            ->join('localitati as lcd', function ($join) {
                $join->on('c.localitate_id', '=', 'lcd.cod_lc')
                     ->where('lcd.deleted', 0);
            })
            ->leftJoin('judete as je', 'lcd.cod_jd', '=', 'je.cod_jd')
            ->join('clienti as cl', 'c.client_id', '=', 'cl.cod_cl')
            ->where('c.id', $id);
            if(count($pcs) > 1) {
                $item = $item->whereRaw("c.client_id IN (" . implode(',', $pcs_ids) . ")");
            }
            else {
                $item = $item->where('c.client_id', $expeditor_id);
            }
        $item = $item->first();

        if (!$item) {
            return [];
        }

        return [
            'id' => $item->id,
            'expeditor_nume' => $item->expeditor_nume,
            'expeditor_id' => $item->expeditor_id,
            'data_colectare_string' => $item->data_colectare_string,
            'interval_colectare_start' => $item->interval_colectare_start,
            'interval_colectare_end' => $item->interval_colectare_end,
            'expeditor_localitate_id' => $item->expeditor_localitate_id,
            'expeditor_judet' => $item->expeditor_judet,
            'expeditor_localitate' => $item->expeditor_localitate,
            'expeditor_contact' => $item->expeditor_contact,
            'expeditor_telefon' => $item->expeditor_telefon,
            'expeditor_email' => $item->expeditor_email,
            'expeditor_adresa' => $item->expeditor_adresa,
            'colete' => $item->colete,
            'paleti' => $item->paleti,
            'greutate' => $item->greutate,
            'volum' => $item->volum,
            'observatii' => $item->observatii,
            'ridica_de_la' => $item->ridica_de_la,
            //'status' => 'Transmisa',
        ];
    }

    /**
     * Get comanda for editing
     */
    public function getForEdit(int $expeditor_id, int $id): array
    {
        if($id == 0) return [];
        $pcs = collect(session('pcs', []));
        $pcs_ids = $pcs->reduce(function ($carry, $item) {
            $carry[] = $item['id'];
            return $carry;
        }, []);
        $item = DB::table('comenzi as c')
            ->select('c.id', 'c.localitate_id as expeditor_localitate_id', 
            'c.client as ridica_de_la', 'cl.nume as expeditor_nume', 'c.client_id as expeditor_id', 'c.adresa as expeditor_adresa', 
            'c.contact as expeditor_contact', 'c.telefon as expeditor_telefon', 
            'c.email as expeditor_email', 'lcd.nume_lc as expeditor_localitate', 'je.nume_jd as expeditor_judet',
            'c.h_start as interval_colectare_start', 'c.h_end as interval_colectare_end', 
            'c.collect_at as data_colectare_string', 
            'c.nr_obj_colet as colete', 'c.nr_obj_palet as paleti', 'c.kg_obj as greutate', 'c.vol_obj as volum', 'c.observatii')
            ->join('localitati as lcd', function ($join) {
                $join->on('c.localitate_id', '=', 'lcd.cod_lc')
                     ->where('lcd.deleted', 0);
            })
            ->leftJoin('judete as je', 'lcd.cod_jd', '=', 'je.cod_jd')
            ->join('clienti as cl', 'c.client_id', '=', 'cl.cod_cl')
            ->where('c.id', $id);
            if(count($pcs) > 1) {
                $item = $item->whereRaw("c.client_id IN (" . implode(',', $pcs_ids) . ")");
            }
            else {
                $item = $item->where('c.client_id', $expeditor_id);
            }
        $item = $item->first();

        if (!$item) {
            return [];
        }

        return [
            'id' => $item->id,
            'expeditor_nume' => $item->expeditor_nume,
            'expeditor_id' => $item->expeditor_id,
            'ridica_de_la' => $item->ridica_de_la,
            'data_colectare_string' => $item->data_colectare_string,
            'interval_colectare_start' => $item->interval_colectare_start,
            'interval_colectare_end' => $item->interval_colectare_end,
            'expeditor_localitate_id' => $item->expeditor_localitate_id,
            'expeditor_judet' => $item->expeditor_judet,
            'expeditor_localitate' => $item->expeditor_localitate,
            'expeditor_contact' => $item->expeditor_contact,
            'expeditor_telefon' => $item->expeditor_telefon,
            'expeditor_email' => $item->expeditor_email,
            'expeditor_adresa' => $item->expeditor_adresa,
            'colete' => $item->colete > 0 ? $item->colete : null,
            'paleti' => $item->paleti > 0 ? $item->paleti : null,
            'greutate' => $item->greutate > 0 ? $item->greutate : null,
            'volum' => $item->volum > 0 ? $item->volum : null,
            'observatii' => $item->observatii,
            //'status' => 'Transmisa',
        ];
    }

    /**
     * Update a comanda
     */
    public function update(User $user, int $id, array $data): void
    {
        if($id == 0) throw new \Exception('Order not found.');
        $pcs = collect(session('pcs', []));
        $pcs_ids = $pcs->reduce(function ($carry, $item) {
            $carry[] = $item['id'];
            return $carry;
        }, []);
        $updateData = [
            'collect_at' => $data['data_colectare_string'],
            'h_start' => $data['interval_colectare_start'],
            'h_end' => $data['interval_colectare_end'],
            'client' => !empty($data['ridica_de_la']) ? $data['ridica_de_la'] : $data['expeditor_nume'],
            'client_id' => $data['expeditor_id'],
            'localitate_id' => $data['expeditor_localitate_id'],
            'adresa' => $data['expeditor_adresa'],
            'contact' => $data['expeditor_contact'] ?? '',
            'telefon' => $data['expeditor_telefon'] ?? '',
            'email' => $data['expeditor_email'] ?? null,
            'nr_obj_colet' => $data['colete'] ?? 0,
            'nr_obj_palet' => $data['paleti'] ?? 0,
            'kg_obj' => $data['greutate'],
            'vol_obj' => $data['volum'] ?? 0,
            'observatii' => $data['observatii'] ?? null,
            'updated_at' => now(),
            'updated_by' => $user->id,
            'deleted_at' => null,
            'deleted_by' => 0,
        ];

        Log::debug('Updating comanda with data: ' . json_encode($updateData) . ' and ridica_de_la: ' . $data['ridica_de_la']);

        try {
            //DB::enableQueryLog();
            DB::beginTransaction();
            $item = DB::table('comenzi')->where('id', $id);
            if(count($pcs) > 1) {
                $item = $item->whereRaw("client_id IN (" . implode(',', $pcs_ids) . ")");
            }
            else {
                $item = $item->where('client_id', $user->expeditor_id);
            }
            $item = $item->first();
            if (!$item) {
                throw new \Exception('Order not found or access denied.');
            }
            //don't update if status ... TODO
            $affected = DB::table('comenzi')
            ->where('id', $id)
            ->update($updateData);
            if ($affected == 0) {
                throw new \Exception('No changes made to order.');
            }

            //insert comenzi history
            DB::table('comenzi_history')->insert([
                'comanda_id' => $id,
                'status' => 1,
                'created_at' => now(),
                'created_by' => $user->id,
            ]);
            DB::commit();
            //dd(DB::getRawQueryLog());
            //DB::disableQueryLog();
        } catch (\Exception $e) {
            DB::rollBack();
            throw new \Exception($e->getMessage());
        }
    }

    /**
     * Delete a comanda
     */
    public function delete(User $user, $id): void
    {
        if($id == 0) throw new \Exception('Order not found.');
        $pcs = collect(session('pcs', []));
        $pcs_ids = $pcs->reduce(function ($carry, $item) {
            $carry[] = $item['id'];
            return $carry;
        }, []);
        
        try {
            //DB::enableQueryLog();
            DB::beginTransaction();
            $item = DB::table('comenzi')->where('id', $id);
            if(count($pcs) > 1) {
                $item = $item->whereRaw("client_id IN (" . implode(',', $pcs_ids) . ")");
            }
            else {
                $item = $item->where('client_id', $user->expeditor_id);
            }
            $item = $item->first();
            if (!$item) {
                throw new \Exception('Order not found or access denied.');
            }
            $deleted = DB::table('comenzi_history')->insertGetId([
                    'comanda_id' => $id,
                    'status' => 7, //deleted
                    'created_at' => now(),
                    'created_by' => $user->id,
                ]);
            if($deleted) {
                DB::table('comenzi')
                    ->where('id', $id)
                    ->update(['deleted_at' => now(), 'deleted_by' => $user->id]);
            }
            if(!$deleted) {
                throw new \Exception('Failed to delete order.');
            }
            DB::commit();
            //dd(DB::getRawQueryLog());
            //DB::disableQueryLog();
        } catch (\Exception $e) {
            DB::rollBack();
            throw new \Exception($e->getMessage());
        }
    }

    /**
     * Get paginated comenzi with filters and sorting
     */
    public function getPaginated(
        int $expeditor_id,
        int $page = 1,
        int $rows = 50,
        string $sortField = 'id',
        string $sortOrder = 'asc',
        Request $request
    ): array {
        $pcs = collect(session('pcs', []));
        $pcs_ids = $pcs->reduce(function ($carry, $item) {
            $carry[] = $item['id'];
            return $carry;
        }, []);

        $query = DB::table('comenzi as co')
            ->select('co.id as id', 'co.collect_at as collected_at', 'ch.created_at as data_status', 'ch.status as status', 'cm.motiv as motiv', 
                'lce.nume_lc as expeditor_localitate', 'co.client as expeditor_nume',
                'co.adresa as expeditor_adresa', 'je.nume_jd as expeditor_judet',
                'co.nr_obj_colet as colete', 'co.nr_obj_palet as paleti', 'co.kg_obj as greutate', 'co.observatii as observatii', 
                'ag.nume_ag as curier', 'co.created_at as created_at', 'co.updated_at as updated_at', 'co.deleted_at as deleted_at',
                'cu.nume as created_by', 'uu.nume as updated_by', 'du.nume as deleted_by'
            )
            ->join('localitati as lce', function ($join) {
                $join->on('co.localitate_id', '=', 'lce.cod_lc')
                     ->where('lce.deleted', 0);
            })
            ->leftJoin('judete as je', 'lce.cod_jd', '=', 'je.cod_jd')
            ->join(DB::raw("(select i.comanda_id, i.created_at , i.status, i.created_by, i.agent_id , i.motiv_id
                    from comenzi_history i
                    where i.created_at = (select max(created_at) from comenzi_history j where j.comanda_id=i.comanda_id )
                 ) as ch"), 'co.id', '=', 'ch.comanda_id')
            ->leftJoin('comenzi_motive as cm', 'ch.motiv_id', '=', 'cm.id')
            ->leftJoin('agenti as ag', 'ch.agent_id', '=', 'ag.cod_ag')
            ->leftJoin('clienti as cl', 'co.client_id', '=', 'cl.cod_cl')
            ->leftJoin('users as cu', 'co.created_by', '=', 'cu.id')
            ->leftJoin('users as uu', 'co.updated_by', '=', 'uu.id')
            ->leftJoin('users as du', 'co.deleted_by', '=', 'du.id');

            if(count($pcs) > 1) {
                $query = $query->whereRaw("co.client_id IN (" . implode(',', $pcs_ids) . ")");
            }
            else {
                $query = $query->where('co.client_id', $expeditor_id);
            }
        // Apply filters
        foreach ($request->all() as $key => $value) {
            if (str_starts_with($key, 'filter_') && !empty($value)) {
                $field = substr($key, 7);
                match ($field) {
                    'expeditor_localitate' => 
                        $query->where('lce.nume_lc', 'like', "{$value}%"),
                    'expeditor_nume' => 
                        $query->where('co.client', 'like', "{$value}%"),
                    'curier' => 
                        $query->where('ag.nume_ag', 'like', "{$value}%"),
                    'data_status' => 
                        $query->whereDate('ch.created_at', '=', date('Y-m-d', strtotime($value))),
                    'status' => 
                        $query->where('ch.status', '=', (int)$value),
                    'motiv' => 
                        $query->where('cm.motiv', 'like', "{$value}%"),
                    'updated_at' => 
                        $query->whereDate('ch.created_at', '=', date('Y-m-d', strtotime($value))),
                    default => 
                        $query->where('co.' . $field, 'like', "{$value}%"),
                };
            }
        }
        // Apply sorting
        match ($sortField) {
            'expeditor_localitate' => 
                $query->orderBy('lce.nume_lc', $sortOrder),
            'expeditor_nume' => 
                $query->orderBy('co.client', $sortOrder),
            'curier' => 
                $query->orderBy('ag.nume_ag', $sortOrder),
            'data_status' => 
                $query->orderBy('ch.created_at', $sortOrder),
            'status' => 
                $query->orderBy('ch.status', $sortOrder),
            'motiv' => 
                $query->orderBy('cm.motiv', $sortOrder),
            'updated_at' => 
                $query->orderBy('ch.created_at', $sortOrder),
            default => 
                $query->orderBy($sortField, $sortOrder),
        };

        //dd($query->toSql(), $query->getBindings());

        // Paginate
        $result = $query->paginate($rows, ['*'], 'page', $page);

        return [
            'data' => $result->items(),
            'total' => $result->total(),
            'current_page' => $result->currentPage(),
            'per_page' => $result->perPage(),
            'last_page' => $result->lastPage(),
            'from' => $result->firstItem(),
            'to' => $result->lastItem(),
        ];        
    }
}
