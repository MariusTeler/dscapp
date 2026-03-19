<?
namespace App\Services\Helpers;

use Illuminate\Support\Facades\DB;
use App\Dto\ExpeditieDto;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Log;
use \StdClass;

class ClientService
{
    public static function infos(int $client_id = 0): StdClass
    {
        $ret = new StdClass();
        $ret->cod_cl = 0;
        $ret->contract = 0;
        $ret->mod_plata = 0;
        $ret->taxa_destinatie = 0;
        $ret->taxa_expediere = 1;
        $ret->activ = 0;
        $ret->cc = 0;
        $ret->icc = 0;
        $ret->ret_amb = 0;
        $ret->kg_ret_amb = ExpeditieDto::DEFAULT_KG_RET_AMB;
        $ret->tarif_sms = 0;

        if($client_id == 0) return $ret;
        try {
            $client_infos = DB::table('clienti as cl')
            ->leftJoin('clienti as clm', 'clm.cod_cl', '=', 'cl.master')
            ->leftJoin('tarife as clt', 'clt.id_cl', '=', 'cl.cod_cl')
            ->leftJoin('tarife as clmt', 'clmt.id_cl', '=', 'clm.cod_cl')
            ->where('cl.cod_cl', $client_id)
            ->where('cl.activ', 1)
            ->selectRaw('cl.cod_cl, cl.activ as client_activ, cl.tarif as client_contract, cl.mod_plata as client_mod_plata,
                cl.cc as client_cc, cl.icc as client_icc,
                cl.tarif_individual as client_tarif_individual,
                clt.taxa_expediere as client_taxa_expediere, clt.taxa_destinatie as client_taxa_destinatie,
                clt.ret_amb as client_ret_amb, clt.kg_ret_amb client_kg_ret_amb, clt.tarif_sms as client_tarif_sms,
                cl.master, clm.activ as client_master_activ, clm.tarif as client_master_contract, clm.mod_plata as client_master_mod_plata,
                clm.cc as client_master_cc, clm.icc as client_master_icc,
                clmt.taxa_expediere as client_master_taxa_expediere, clmt.taxa_destinatie as client_master_taxa_destinatie,
                clmt.ret_amb as client_master_ret_amb, clmt.kg_ret_amb as client_master_kg_ret_amb,
                clmt.tarif_sms as client_master_tarif_sms')
            ->first();
        } catch(\Exception $e) {
            return $ret;
        }
        if($client_infos === null) return $ret;
        
        $client_master = $client_infos->client_tarif_individual == 1 || $client_infos->master == 0 || $client_infos->master == $client_infos->cod_cl;
        //return master infos if not ...
        if($client_master && $client_infos->client_activ == 0 || !$client_master && $client_infos->client_master_activ == 0)
            return $ret;
        //return client infos, change names
        $ret = new StdClass();
        $ret->cod_cl = $client_master ? $client_infos->cod_cl : $client_infos->master;
        $ret->activ = $client_master ? $client_infos->client_activ : $client_infos->client_master_activ;
        $ret->contract = $client_master ? $client_infos->client_contract : $client_infos->client_master_contract;
        $ret->mod_plata = $client_master ? $client_infos->client_mod_plata : $client_infos->client_master_mod_plata;
        $ret->cc = $client_master ? $client_infos->client_cc : $client_infos->client_master_cc;
        $ret->icc = $client_master ? $client_infos->client_icc : $client_infos->client_master_icc;
        $ret->taxa_expediere = $client_master ? $client_infos->client_taxa_expediere : $client_infos->client_master_taxa_expediere;
        $ret->taxa_destinatie = $client_master ? $client_infos->client_taxa_destinatie : $client_infos->client_master_taxa_destinatie;
        $ret->ret_amb = $client_master ? $client_infos->client_ret_amb : $client_infos->client_master_ret_amb;
        $ret->kg_ret_amb = $client_master ? $client_infos->client_kg_ret_amb : $client_infos->client_master_kg_ret_amb;
        $ret->tarif_sms = $client_master ? $client_infos->client_tarif_sms : $client_infos->client_master_tarif_sms;
        return $ret;
    }

    public static function searchClient(StdClass $data, $client_client): int
    {
		//test eroare nume <> nume, localitate, adresa in tabela clienti
        try {
            $client = DB::table('clienti');
            if($data->{$client_client . "_id"} > 0)
                $client->where('cod_cl', $data->{$client_client . "_id"});
            $client = $client->where('cod_lc', $data->{$client_client . "_localitate_id"})
            ->where('activ', 1)
            ->where('sters', 0)
            ->where('mod_plata', 0)
            ->where('tarif', 0)
            ->where(function($query) {
                $query->where('master', 0)
                      ->orWhereColumn('master', 'cod_cl');
            })
            ->where('nume', 'like', $data->{$client_client . "_nume"})
            ->where('adresa', 'like', $data->{$client_client . "_adresa"})
            ->first('cod_cl');

		return $client ? $client->cod_cl : 0;
        } catch(\Exception $e) {
            Log::error("Error searchClient : " . $e->getMessage());
            return 0;
        }
    }

    public static function insertClient(StdClass $data, $client_client, $userId): int
    {
        try {
            return DB::table('clienti')->insertGetId([
                'nume' => $data->{$client_client . "_nume"},
                'cod_lc' => $data->{$client_client . "_localitate_id"},
                'adresa' => $data->{$client_client . "_adresa"},
                'contact' => $data->{$client_client . "_contact"},
                'telefon' => $data->{$client_client . "_telefon"},
                'email' => $data->{$client_client . "_email"},
                'activ' => 1,
                'tarif' => 0,
                'mod_plata' => 0,
                'persoana_fizica' => 1,
                'master' => 0,
                'created_at' => now(),
                'created_by' => $userId,
            ]);
        } catch(\Exception $e) {
            Log::error("Error insertClient : " . $e->getMessage());
            return 0;
        }
    }

    public static function updateClient(StdClass $data, $client_client, $userId): bool
    {
        try {
            DB::table('clienti')
                ->where('cod_cl', $data->{$client_client . "_id"})
                ->update([
                    'contact' => $data->{$client_client . "_contact"},
                    'telefon' => $data->{$client_client . "_telefon"},
                    'email' => $data->{$client_client . "_email"},
                    'updated_at' => now(),
                    'updated_by' => $userId,
                ]);
            return true;
        } catch(\Exception $e) {
            Log::error("Error updateClient : " . $e->getMessage());
            return false;
        }
    }

    public static function insertClientDestinatar(StdClass $data, $client_client, $userId, $expeditor_id): int
    {
        try {
            return DB::table('client_destinatari')->insertGetId([
                'cod_cl' => $data->{$client_client . "_id"},
                'nume' => $data->{$client_client . "_nume"},
                'id_loc' => $data->{$client_client . "_localitate_id"},
                'adresa' => $data->{$client_client . "_adresa"},
                'contact' => $data->{$client_client . "_contact"},
                'telefon' => $data->{$client_client . "_telefon"},
                'email' => $data->{$client_client . "_email"},
                'id_exp' => $expeditor_id,
                'activ' => 1,
                'created_at' => now(),
                'created_by' => $userId,
            ]);
        } catch(\Exception $e) {
            Log::error("Error insertClientDestinatar : " . $e->getMessage());
            return 0;
        }
    }

    public static function updateClientDestinatar(StdClass $data, $client_client, $userId): bool
    {
        try {
            DB::table('client_destinatari')
                ->where('id', $data->{$client_client . "_client_id"})
                ->update([
                    'cod_cl' => $data->{$client_client . "_id"},
                    'contact' => $data->{$client_client . "_contact"},
                    'telefon' => $data->{$client_client . "_telefon"},
                    'email' => $data->{$client_client . "_email"},
                    'activ' => 1,
                    'updated_at' => now(),
                    'updated_by' => $userId,
                ]);
            return true;
        } catch(\Exception $e) {
            Log::error("Error updateClientDestinatar : " . $e->getMessage());
            return false;
        }
    }

    public static function searchClientDestinatar(StdClass $data, $client_client): int
    {
        //test eroare nume <> nume, localitate, adresa in tabela clienti_destinatari
        try {
            $client = DB::table('client_destinatari');
            if($data->{$client_client . "_client_id"} > 0)
                $client->where('id', $data->{$client_client . "_client_id"});
            $client = $client
                ->where('id_loc', $data->{$client_client . "_localitate_id"})
                ->where('activ', 1)
                ->where('nume', 'like', $data->{$client_client . "_nume"})
                ->where('adresa', 'like', $data->{$client_client . "_adresa"})
                ->first('id');
            return $client ? $client->id : 0;
        } catch(\Exception $e) {
            Log::error("Error searchClientDestinatar : " . $e->getMessage());
            return 0;
        }
    }

    public static function upsertClientDestinatar(StdClass $data, $client_client, $userId, $expeditor_id): int
    {
        if(($client_destinatari_id = self::searchClientDestinatar($data, $client_client)) > 0) {
            $updated = self::updateClientDestinatar($data, $client_client, $userId);
            return $updated ? $client_destinatari_id : 0;
        } else {
            return self::insertClientDestinatar($data, $client_client, $userId, $expeditor_id);
        }
    }

    public static function getLocalitateInfos(int $expeditor_id): array
    {
        try {
            $localitate_infos = DB::table('clienti as cl')
            ->join('localitati as lc', 'lc.cod_lc', '=', 'cl.cod_lc')
            ->where('cl.cod_cl', $expeditor_id)
            ->where('cl.activ', 1)
            ->select('lc.cod_lc', 'lc.dist_km')
            ->first();
        } catch(\Exception $e) {
            Log::error("Error getLocalitateInfos : " . $e->getMessage());
            return [];
        }
        return $localitate_infos ? (array) $localitate_infos : [];
    }

    public static function getClient(int $id): array
    {
        try {
            $client = DB::table('clienti as cl')
            ->leftJoin('localitati as lc', 'lc.cod_lc', '=', 'cl.cod_lc')
            ->select('cl.cod_cl', 'cl.nume', 'cl.adresa', 'cl.telefon', 'cl.contact', 'cl.email',
                    'lc.nume_lc as localitate', 'lc.dist_km as localitate_km',
            )
            ->where('cl.activ', 1)
            ->where('cl.cod_cl', $id)
            ->first();

		return $client ? (array) $client : [];
        } catch(\Exception $e) {
            Log::error("Error getClient : " . $e->getMessage());
            return [];
        }
    }
}