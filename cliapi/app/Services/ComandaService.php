<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use App\Services\Helpers\LocalitatiService;

class ComandaService
{
    /**
     * Create a new comanda
     */
    public function create($user, array $data): int
    {
        //'created_at', 'updated_at'
        try {
            $localitate_obj = LocalitatiService::getByLocalitateJudet($data['judet'] ?? '', $data['localitate'] ?? '');
            if(count($localitate_obj) == 0 || !isset($localitate_obj['cod_lc'])) {
                throw new \Exception('Localitatea nu a fost gasita in baza de date.');
            }
            $localitate_id = $localitate_obj['cod_lc'] ?? 0;
            //DB::enableQueryLog();
            DB::beginTransaction();
            $id =  DB::table('comenzi')->insertGetId([
                'created_at' => now(),
                'created_by' => $user['id'],
                'collect_at' => $data['collect_at'],
                'h_start' => $data['h_start'],
                'h_end' => $data['h_end'],
                'client' => !empty($data['ridica_de_la']) ? $data['ridica_de_la'] : ($data['client'] ?? $user['expeditor_nume']),
                'client_id' => $data['pcId'] ?? $user['expeditor_id'],
                'localitate_id' => $localitate_id,
                'adresa' => $data['adresa'],
                'contact' => $data['contact'] ?? '',
                'telefon' => $data['telefon'] ?? '',
                'email' => $data['email'] ?? '',
                'nr_obj_colet' => $data['colete'] ?? 0,
                'nr_obj_palet' => $data['paleti'] ?? 0,
                'kg_obj' => $data['greutate'],
                'vol_obj' => $data['volum'] ?? 0,
                'observatii' => $data['observatii'] ?? '',
            ]);
            if(!$id) {
                throw new \Exception('Failed to create order.');
            }
            DB::table('comenzi_history')->insert([
                'comanda_id' => $id,
                'status' => 1,
                'created_at' => now(),
                'created_by' => $user['id'],
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
     * Delete a comanda
     */
    public function delete($user, int $id): void
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
}
