<?php
/**
 * 2 tipuri de tarife det : TARIF_LOCO : 0, TARIF_NATIONAL : 1
 * 2 tipuri de obiecte : COLET : 0, PALET : 1
 */

namespace App\Services\Tarife;

use App\Dto\ExpeditieDto;
use Illuminate\Support\Facades\DB;

class Tarif { // tarif de lista -> id_cl = 0 in tabla tarife

    private const FIELDS = [
        'id',
        'id_cl',
        'moneda',
        'km_limit_prel',
        'km_limit_livr',
        'tarif_prel',
        'tarif_livr',
        'tarif_prel_tip',
        'tarif_livr_tip',
        'plata_retur',
        'taxa_expediere',
        'taxa_ramburs',
        'taxa_destinatie',
        'tarif_returnare',
        'ret_amb',
        'kg_ret_amb',
        'tarif_sms',
        'tarif_open',
        'tarif_proc_indexc',
        'obs'
    ];


	public function __construct(
        public readonly ?int $id,
        public readonly ?int $id_cl,
        public readonly ?string $moneda,
        public readonly ?int $km_limit_prel,
        public readonly ?int $km_limit_livr,
        public readonly ?float $tarif_prel,
        public readonly ?float $tarif_livr,
        public readonly ?int $tarif_prel_tip,
        public readonly ?int $tarif_livr_tip,
        public readonly ?int $plata_retur,
        public readonly ?int $taxa_expediere,
        public readonly ?int $taxa_ramburs,
        public readonly ?int $taxa_destinatie,
        public readonly ?int $tarif_returnare,
        public readonly ?int $ret_amb,
        public readonly ?float $kg_ret_amb,
        public readonly ?float $tarif_sms,
        public readonly ?float $tarif_open,
        public readonly ?int $tarif_proc_indexc,
        public readonly ?string $obs
    )
	{}

	public static function select(int $clientId = 0): Tarif
	{
        $tarif = DB::table('tarife')->select(self::FIELDS)->where('id_cl', $clientId)->first();
        if ($tarif === null) {
            $tarif = DB::table('tarife')->select(self::FIELDS)->where('id_cl', 0)->first();
            if( $tarif === null ) {
                throw new \Exception('Tarif de lista nu exista in baza de date !');
            }
        }
        //return new Tarif object
        return new self(
            $tarif->id,
            $tarif->id_cl,
            $tarif->moneda,
            $tarif->km_limit_prel,
            $tarif->km_limit_livr,
            $tarif->tarif_prel,
            $tarif->tarif_livr,
            $tarif->tarif_prel_tip,
            $tarif->tarif_livr_tip,
            $tarif->plata_retur,
            $tarif->taxa_expediere,
            $tarif->taxa_ramburs,
            $tarif->taxa_destinatie,
            $tarif->tarif_returnare,
            $tarif->ret_amb,
            $tarif->kg_ret_amb,
            $tarif->tarif_sms,
            $tarif->tarif_open,
            $tarif->tarif_proc_indexc,
            $tarif->obs
        );

	}

	public static function upsert(int $clientId, Tarif $newTarif): void
	{
		//search if record exists for this client
        $oldTarif = DB::table('tarife')->where('id_cl', $clientId)->first('id');
        try {
            DB::beginTransaction();
            $tarifLista = Tarif::select(0); //tarif de lista
            if ($oldTarif === null) // insert new tarif
            {
                DB::table('tarife')->insert([
                    'id_cl' => $clientId,
                    'moneda' => $newTarif->moneda ?? 'LEI',
                    'km_limit_prel' => $newTarif->km_limit_prel ?? $tarifLista->km_limit_prel ?? ExpeditieDto::KM_LIMIT,
                    'km_limit_livr' => $newTarif->km_limit_livr ?? $tarifLista->km_limit_livr ?? ExpeditieDto::KM_LIMIT,
                    'tarif_prel' => $newTarif->tarif_prel ?? $tarifLista->tarif_prel,
                    'tarif_livr' => $newTarif->tarif_livr ?? $tarifLista->tarif_livr,
                    'tarif_prel_tip' => $newTarif->tarif_prel_tip ?? $tarifLista->tarif_prel_tip,
                    'tarif_livr_tip' => $newTarif->tarif_livr_tip ?? $tarifLista->tarif_livr_tip,
                    'plata_retur' => $newTarif->plata_retur ?? $tarifLista->plata_retur,
                    'taxa_expediere' => $newTarif->taxa_expediere ?? $tarifLista->taxa_expediere,
                    'taxa_ramburs' => $newTarif->taxa_ramburs ?? $tarifLista->taxa_ramburs,
                    'taxa_destinatie' => $newTarif->taxa_destinatie ?? $tarifLista->taxa_destinatie,
                    'tarif_returnare' => $newTarif->tarif_returnare ?? $tarifLista->tarif_returnare,
                    'ret_amb' => $newTarif->ret_amb ?? $tarifLista->ret_amb,
                    'kg_ret_amb' => $newTarif->kg_ret_amb ?? $tarifLista->kg_ret_amb,
                    'tarif_sms' => $newTarif->tarif_sms ?? $tarifLista->tarif_sms,
                    'tarif_open' => $newTarif->tarif_open ?? $tarifLista->tarif_open,
                    'tarif_proc_indexc' => $newTarif->tarif_proc_indexc ?? $tarifLista->tarif_proc_indexc,
                    'obs' => $newTarif->obs ?? ''
                ]);
            }
            else // update tarif
                DB::table('tarife')
                    ->where('id_cl', $clientId)
                    ->update([
                        'moneda' => $newTarif->moneda ?? $oldTarif->moneda,
                        'km_limit_prel' => $newTarif->km_limit_prel ?? $oldTarif->km_limit_prel,
                        'km_limit_livr' => $newTarif->km_limit_livr ?? $oldTarif->km_limit_livr,
                        'tarif_prel' => $newTarif->tarif_prel ?? $oldTarif->tarif_prel,
                        'tarif_livr' => $newTarif->tarif_livr ?? $oldTarif->tarif_livr,
                        'tarif_prel_tip' => $newTarif->tarif_prel_tip ?? $oldTarif->tarif_prel_tip,
                        'tarif_livr_tip' => $newTarif->tarif_livr_tip ?? $oldTarif->tarif_livr_tip,
                        'plata_retur' => $newTarif->plata_retur ?? $oldTarif->plata_retur,
                        'taxa_expediere' => $newTarif->taxa_expediere ?? $oldTarif->taxa_expediere,
                        'taxa_ramburs' => $newTarif->taxa_ramburs ?? $oldTarif->taxa_ramburs,
                        'taxa_destinatie' => $newTarif->taxa_destinatie ?? $oldTarif->taxa_destinatie,
                        'tarif_returnare' => $newTarif->tarif_returnare ?? $oldTarif->tarif_returnare,
                        'ret_amb' => $newTarif->ret_amb ?? $oldTarif->ret_amb,
                        'kg_ret_amb' => $newTarif->kg_ret_amb ?? $oldTarif->kg_ret_amb,
                        'tarif_sms' => $newTarif->tarif_sms ?? $oldTarif->tarif_sms,
                        'tarif_open' => $newTarif->tarif_open ?? $oldTarif->tarif_open,
                        'tarif_proc_indexc' => $newTarif->tarif_proc_indexc ?? $oldTarif->tarif_proc_indexc,
                        'obs' => $newTarif->obs ?? $oldTarif->obs
                    ]);
            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            throw new \Exception('Eroare salvare tarif : '.$e->getMessage());
        }
	}

	public static function deleteCascade(int $clientId): void //cascade delete ? all tarif for this client
	{
        if( $clientId <= 0 )
            return;
        try {
            DB::beginTransaction();

            DB::table('tarife_g')
                ->whereIn('id_tarife_det', function ($query) use ($clientId) {
                    $query->select('id')
                        ->from('tarife_det')
                        ->whereIn('id_tarife', function ($subQuery) use ($clientId) {
                            $subQuery->select('id')
                                ->from('tarife')
                                ->where('id_cl', $clientId);
                        });
                })->delete();
            DB::table('tarife_det')
                ->whereIn('id_tarife', function ($query) use ($clientId) {
                    $query->select('id')
                        ->from('tarife')
                        ->where('id_cl', $clientId);
                })->delete();
            DB::table('tarife')
                ->where('id_cl', $clientId)
                ->delete();

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            throw new \Exception('Eroare stergere tarif : '.$e->getMessage());
        } 
	}

	public static function getValoareKM(Tarif $tarif, $kmPrel = 0, $kmLivr = 0): float
    {
		//self::KM_LIMIT
		//la livrare
		$ret_val=0.00;
		$kmPrel = floatval($kmPrel);
		$kmLivr = floatval($kmLivr);

		if($kmPrel > $tarif->km_limit_prel)
		{
			if(!empty($tarif->tarif_prel_tip))
				$ret_val += $tarif->tarif_prel;
			else
				$ret_val += $kmPrel * $tarif->tarif_prel;
		}
		if($kmLivr > $tarif->km_limit_livr)
		{
			if(!empty($tarif->tarif_livr_tip))
				$ret_val += $tarif->tarif_livr;
			else
				$ret_val += $kmLivr * $tarif->tarif_livr;
		}

		if($tarif->tarif_proc_indexc > 0)
			$ret_val += ($ret_val * $tarif->tarif_proc_indexc) / 100;

		return round($ret_val, 2);
	}
}