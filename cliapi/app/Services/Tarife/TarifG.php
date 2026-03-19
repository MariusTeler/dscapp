<?php
/**
 * 2 tipuri de tarife : TARIF_LOCO : 0, TARIF_NATIONAL : 1
 * 2 tipuri de obiecte : COLET : 0, PALET : 1
 * COLET : praguri + per kg
 * PALET : praguri sau praguri + per kg
 */
namespace App\Services\Tarife;

use Illuminate\Support\Facades\DB;
use App\Services\Tarife\TarifDet;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Collection;
 
class TarifG { // tarif de lista -> id_cl=0 in tabla tarife

	public const COLET = 0; // TIP in table tarife_g
	public const PALET = 1;
    private const FIELDS = [
        'id',
        'id_tarife_det',
        'tip',
        'g_init',
        'g_fin',
        'km_init',
        'km_fin',
        'val_init',
        'inc_val',
        'inc_greut'
    ];
		
	public function __construct(
        public readonly ?int $id,
        public readonly ?int $id_tarife_det,
        public readonly ?int $tipObj,
        public readonly ?float $g_init,
        public readonly ?float $g_fin,
        public readonly ?int $km_init,
        public readonly ?int $km_fin,
        public readonly ?float $val_init,
        public readonly ?float $inc_val,
        public readonly ?float $inc_greut
    )
	{}
    
    public static function select(int $tarifeDetId, int $tipTarif = TarifDet::NATIONAL, int $tipObj = self::COLET, int $km = 1): Collection | null
	{
        try {
		    return $tipObj == self::PALET ? self::selectPalet($tarifeDetId, $tipTarif, $km) :  self::selectColet();
        } catch (\Exception $e) {
            Log::warning('Eroare la selectarea tarifelor G: ' . $e->getMessage());
            throw new \Exception('Eroare la selectarea tarifelor G: ' . $e->getMessage());
        }
	}

    //return array of TarifG
	private static function selectPalet(int $tarifeDetId, int $tipTarif, int $km): Collection | null
	{
        try {
            $tarif = DB::table('tarife_g')->select(self::FIELDS)
                ->where('id_tarife_det', $tarifeDetId)
                ->where('tip', self::PALET)
                ->where('km_init', '<=', $km)
                ->where('km_fin', '>', $km)
                ->orderBy('g_init')
                ->orderBy('g_fin')
                ->get();
            if($tarif->isEmpty()) {
                Log::warning('Nu exista tarif G pentru paleti cu id_tarife_det: ' . $tarifeDetId);
                return self::selectColet($tarifeDetId, $tipTarif);
            }
            return $tarif->map(fn($item) => new self(
                $item->id,
                $item->id_tarife_det,
                $item->tip,
                $item->g_init,
                $item->g_fin,
                $item->km_init,
                $item->km_fin,
                $item->val_init,
                $item->inc_val,
                $item->inc_greut
            ));
        } catch (\Exception $e) {
            throw new \Exception('Eroare la selectarea tarifelor G pentru paleti');
        }
        return self::selectColet($tarifeDetId, $tipTarif);
	}

	private static function selectColet($tarifeDetId = 0, $tipTarif = TarifDet::NATIONAL): Collection | null
	{
        try {
            $tarif = $tarifeDetId == 0 ? null : DB::table('tarife_g')->select(self::FIELDS)
                ->where('id_tarife_det', $tarifeDetId)
                ->where('tip', self::COLET)
                ->orderBy('g_init')
                ->orderBy('g_fin')
                ->get();
            if($tarif === null || $tarif->isEmpty())
            {
                $tarif = DB::table('tarife_g')->select(self::FIELDS)
                    ->where('id_tarife_det', function ($query) use ($tipTarif) {
                        $query->select('id')
                            ->from('tarife_det')
                            ->where('tip_tarif', $tipTarif)
                            ->where('id_tarife', function ($query) {
                                $query->select('id')
                                    ->from('tarife')
                                    ->where('id_cl', 0);
                            });
                    })
                    ->where('tip', self::COLET)
                    ->orderBy('g_init')
                    ->orderBy('g_fin')
                    ->get();
                if( $tarif->isEmpty() ) {
                    Log::warning('Nu exista tarif G pentru colete cu id_tarife_det: ' . $tarifeDetId);
                    throw new \Exception('Tarif G de lista nu exista in baza de date !');
                }
                return $tarif->map(fn($item) => new self(
                    $item->id,
                    $item->id_tarife_det,
                    $item->tip,
                    $item->g_init,
                    $item->g_fin,
                    $item->km_init,
                    $item->km_fin,
                    $item->val_init,
                    $item->inc_val,
                    $item->inc_greut
                ));
            }
            return $tarif->map(fn($item) => new self(
                $item->id,
                $item->id_tarife_det,
                $item->tip,
                $item->g_init,
                $item->g_fin,
                $item->km_init,
                $item->km_fin,
                $item->val_init,
                $item->inc_val,
                $item->inc_greut
            ));
        } catch (\Exception $e) {
            throw new \Exception('Eroare la selectarea tarifelor G pentru colete');
        }
        throw new \Exception('Eroare la selectarea tarifelor G pentru colete');
	}

	private static function getLineTarifG(Collection $tarifeG, int $id): TarifG | null
	{
        return $tarifeG->firstWhere('id', $id);
	}
	
	public static function setLineTarifG(Collection $tarifeG, TarifG $newLineTarifG, int $id): void
	{
        try {
            if($tarifeG->contains('id', $id)) // update existing line
            {
                self::updateLineTarifG($id, $newLineTarifG);	
            }
            else // this is a new line ... insert
            {
                self::insertLineTarifG($newLineTarifG);
            }
        } catch (\Exception $e) {
            throw new \Exception('Eroare la setarea liniei tarif G: ' . $e->getMessage());
        }
	}
	
	private static function insertLineTarifG(TarifG $newLineTarifG): void
	{
        try {
            DB::table('tarife_g')->insert([
                'id_tarife_det' => $newLineTarifG->id_tarife_det,
                'tip' => $newLineTarifG->tipObj,
                'g_init' => $newLineTarifG->g_init,
                'g_fin' => $newLineTarifG->g_fin,
                'km_init' => $newLineTarifG->km_init,
                'km_fin' => $newLineTarifG->km_fin,
                'val_init' => $newLineTarifG->val_init,
                'inc_val' => $newLineTarifG->inc_val,
                'inc_greut' => $newLineTarifG->inc_greut
            ]);
        } catch (\Exception $e) {
            throw new \Exception('Eroare la inserarea liniei tarif G: ' . $e->getMessage());
        }
	}
	
	private static function updateLineTarifG(int $id, TarifG $newLineTarifG): void
	{
		try {
            DB::table('tarife_g')->where('id', $id)->update([
                'g_init' => $newLineTarifG->g_init,
                'g_fin' => $newLineTarifG->g_fin,
                'km_init' => $newLineTarifG->km_init,
                'km_fin' => $newLineTarifG->km_fin,
                'val_init' => $newLineTarifG->val_init,
                'inc_val' => $newLineTarifG->inc_val,
                'inc_greut' => $newLineTarifG->inc_greut
            ]);
        } catch (\Exception $e) {
            throw new \Exception('Eroare la actualizarea liniei tarif G: ' . $e->getMessage());
        }
	}
		
	private static function deleteLineTarifG(int $id): void
	{ 
        try {
            DB::table('tarife_g')->where('id', $id)->delete();
        } catch (\Exception $e) {
            throw new \Exception('Eroare la stergerea liniei tarif G: ' . $e->getMessage());
        }
    }
		

    public static function getValoareGreutate(Collection $tarifeG, float $greutate = 0, float $greutate_vol = 0, int $indexC = 0): float
    {
        try {
            if($tarifeG->isEmpty()) $tarifeG = self::selectColet();
            $greutate = round(floatval($greutate), 3);
            $greutate_vol = round(floatval($greutate_vol), 3);
        
            if($greutate < $greutate_vol) $greutate = $greutate_vol;
            
            if($greutate == 0) return 0.00;
            
            $ret_val = 0.00;
            $val = 0.00;
            $stop = false;
            $i = 0;
            while($greutate > 0 && !$stop && $i < 100)
            {
                $val_g_fin = 0.00;
                $val = 0.00;
                $tarifeG->each(function($row) use (&$greutate, &$val, &$stop, &$val_g_fin)
                {
                    if(!$stop)
                    {	
                        if(round(floatval($row->inc_greut), 3) == round(floatval($row->g_fin), 3)) //palier
                            $val = floatval($row->inc_val) + floatval($row->val_init);
                        else if(round(floatval($row->inc_greut), 3) == round(floatval($row->g_fin) - floatval($row->g_init), 3)) //paliere
                            $val += floatval($row->inc_val) + floatval($row->val_init);
                        else if(round(floatval($row->inc_greut), 3) < round(floatval($row->g_fin), 3)) //per kg
                        {
                            $temp = 0.00;
                            if($greutate > round(floatval($row->g_fin), 3)) { $temp = round(floatval($row->g_fin) - floatval($row->g_init), 3); }
                            else { $temp = round($greutate - floatval($row->g_init), 3); $stop = true; }
                            if(intval($row->inc_greut) > 0)
                                $val += ($temp/floatval($row->inc_greut)) * floatval($row->inc_val) + floatval($row->val_init);
                        }
                    }
                    if($greutate > round(floatval($row->g_init), 3) && $greutate <= round(floatval($row->g_fin), 3)) // am gasit greutatea ... ma opresc : stop
                        $stop = true;
                    $val_g_fin = round(floatval($row->g_fin), 3);
                });
                $greutate -= $val_g_fin;
                $ret_val += $val;
                $i++;
            }
            if($indexC > 0)
                $ret_val += ($ret_val * $indexC) / 100;
            return round($ret_val, 2);
        } catch (\Exception $e) {
            throw new \Exception('Eroare la calculul valorii de greutate: ' . $e->getMessage());
        }
	}	 
}