<?php
/**
 * 2 tipuri de tarife : TARIF_LOCO : 0, TARIF_NATIONAL : 1
 * 2 tipuri de obiecte : COLET : 0, PALET : 1
 */
namespace App\Services\Tarife;

use Illuminate\Support\Facades\DB;

class TarifDet { // tarif de lista -> id_cl=0 in tabla tarife

	public const LOCO = 0;
	public const NATIONAL = 1;

    private const FIELDS = [
        'id',
        'id_tarife',
        'plic',
        'colet',
        'palet',
        'retur_nt',
        'retur_doc',
        'tspecial',
        'returnare',
        'proc_asig',
        'asig_ramb',
        'taxa_ramb',
        'liv_sambata',
        'liv_sediu',
    ];
	
	public function __construct(
        public readonly ?int $id,
        public readonly ?int $id_tarife,//tarif_id
        public readonly ?float $plic,//baza tarif plic
        public readonly ?float $colet,//baza tarif colet
        public readonly ?float $palet,//baza tarif palet
        public readonly ?float $retur_nt,//baza tarif retur nt
        public readonly ?float $retur_doc,//baza tarif retur documente
        public readonly ?float $tspecial,
        public readonly ?float $returnare,
        public readonly ?float $proc_asig, //procent asigurare
        public ?float $asig_ramb, //procent asigurare ramburs
        public ?float $taxa_ramb, //baza tarif retur ramburs
        public readonly ?float $liv_samb,//baza tarif livrare sambata
        public readonly ?float $liv_sed,//baza tarif livrare sediu
    )
	{}

    public static function select(int $tarifId, int $tipTarif = self::NATIONAL): TarifDet
	{
        $tarif = $tarifId > 0 ? DB::table('tarife_det')->select(self::FIELDS)->where('id_tarife', $tarifId)->where('tip_tarif', $tipTarif)->first() : null;
        if ($tarif === null) {
            $tarif = DB::table('tarife_det')->select(self::FIELDS)->where('tip_tarif', $tipTarif)
                ->whereIn('id_tarife', function ($query) {
                    $query->select('id')
                        ->from('tarife')
                        ->where('id_cl', 0);
                })->first();
            if( $tarif === null ) {
                throw new \Exception('Tarif Det de lista nu exista in baza de date !');
            }
        }
        return new self(
            $tarif->id,
            $tarif->id_tarife,
            $tarif->plic,
            $tarif->colet,
            $tarif->palet,
            $tarif->retur_nt,
            $tarif->retur_doc,
            $tarif->tspecial,
            $tarif->returnare,
            $tarif->proc_asig,
            $tarif->asig_ramb,
            $tarif->taxa_ramb,
            $tarif->liv_sambata,
            $tarif->liv_sediu,
        );
	}
	
	public function upsert(int $tarifId, TarifDet $newTarif, int $tipTarif = self::NATIONAL): void
	{
		//search if record exists for this client
        $oldTarif = DB::table('tarife_det')->where('id_tarife', $tarifId)->where('tip_tarif', $tipTarif)->first('id');
        try {
            DB::beginTransaction();
            $tarifLista = TarifDet::select(0, $tipTarif); //tarif de lista
            if ($oldTarif === null) // insert new tarif
            {
                DB::table('tarife_det')->insert([
                    'id_tarife' => $tarifId,
                    'tip_tarif' => $tipTarif,
                    'plic' => $newTarif->plic ?? $tarifLista->plic,
                    'colet' => $newTarif->colet ?? $tarifLista->colet,
                    'palet' => $newTarif->palet ?? $tarifLista->palet,
                    'retur_nt' => $newTarif->retur_nt ?? $tarifLista->retur_nt,
                    'retur_doc' => $newTarif->retur_doc ?? $tarifLista->retur_doc,
                    'tspecial' => $newTarif->tspecial ?? $tarifLista->tspecial,
                    'returnare' => $newTarif->returnare ?? $tarifLista->returnare,
                    'proc_asig' => $newTarif->proc_asig ?? $tarifLista->proc_asig,
                    'asig_ramb' => $newTarif->asig_ramb ?? $tarifLista->asig_ramb,
                    'taxa_ramb' => $newTarif->taxa_ramb ?? $tarifLista->taxa_ramb,
                    'liv_sambata' => $newTarif->liv_samb ?? $tarifLista->liv_samb,
                    'liv_sediu' => $newTarif->liv_sed ?? $tarifLista->liv_sed,
                ]);
            } else { // update old tarif
                DB::table('tarife_det')->where('id', $oldTarif->id)->update([
                    'plic' => $newTarif->plic ?? $tarifLista->plic,
                    'colet' => $newTarif->colet ?? $tarifLista->colet,
                    'palet' => $newTarif->palet ?? $tarifLista->palet,
                    'retur_nt' => $newTarif->retur_nt ?? $tarifLista->retur_nt,
                    'retur_doc' => $newTarif->retur_doc ?? $tarifLista->retur_doc,
                    'tspecial' => $newTarif->tspecial ?? $tarifLista->tspecial,
                    'returnare' => $newTarif->returnare ?? $tarifLista->returnare,
                    'proc_asig' => $newTarif->proc_asig ?? $tarifLista->proc_asig,
                    'asig_ramb' => $newTarif->asig_ramb ?? $tarifLista->asig_ramb,
                    'taxa_ramb' => $newTarif->taxa_ramb ?? $tarifLista->taxa_ramb,
                    'liv_sambata' => $newTarif->liv_samb ?? $tarifLista->liv_samb,
                    'liv_sediu' => $newTarif->liv_sed ?? $tarifLista->liv_sed,
                ]);
            }
            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
	}
}