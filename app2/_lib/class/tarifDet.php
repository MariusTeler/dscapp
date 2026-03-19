<?php
/**
 * Tarife 11 mars 2014
 * 2 tipuri de tarife : TARIF_LOCO : 0, TARIF_NATIONAL : 1
 * 2 tipuri de contracte : negociat, de lista ( pentru tarif de lista id_cl=0 in tabla tarife ; deci $client=0)
 * 2 tipuri de obiecte : COLET : 0, PALET : 1
 */

class TarifDet { // tarif de lista -> id_cl=0 in tabla tarife

	const TARIF_LOCO = 0;
	const TARIF_NATIONAL = 1;

    private $fields=array('id', 'id_tarife', 'plic', 'colet', 'palet', 'retur_nt', 'retur_doc', 'tspecial', 'returnare', 'proc_asig', 'asig_ramb', 'taxa_ramb', 'liv_sambata', 'liv_sediu', 'asig_ramburs', 'asig_expediere');
	
	public function __construct($db, $id_tarife, $tip_tarif=self::TARIF_NATIONAL) //tarif national : default
	{
		$this->db = $db; // $db
		$this->id_tarife = $id_tarife;
		$this->tip_tarif=$tip_tarif;
		$this->selectTarifDet();
	}
	
	private function selectTarifDet()
	{
		$query="select ".implode(',',$this->fields)." from tarife_det where id_tarife = ".$this->id_tarife." and tip_tarif=".$this->tip_tarif;
    	$sql = $this->db->QFetchRowAssoc($query);
    	if(empty($sql)) // punem tariful de baza
    	{
    		$query="select ".implode(',',$this->fields)." FROM tarife_det where tip_tarif=".$this->tip_tarif." 
    		and id_tarife=(select id from tarife where id_cl=0)";
    		$sql = $this->db->QFetchRowAssoc($query);
    	}
    	if(!empty($sql)) 
 			foreach($sql as $key => $value) $this->{$key} = $value;
	}
	
	public static function getInstanceTarifDetLista($db, $tip_tarif=self::TARIF_NATIONAL)
	{
		return new TarifDet($db, 1, $tip_tarif);
	}
	
	public function setTarifDet($id_tarife, $new_tarif_det, $tip_tarif=self::TARIF_NATIONAL)
	{
		$this->id_tarife = $id_tarife;
		$this->tip_tarif = $tip_tarif;
		//load new tarif det
		foreach($this->fields as $value) $this->{$value}=$new_tarif_det[$value];
		//if client exist with tarif in tarife_det table => update
		$query = "select id from tarife_det where id_tarife = ".$this->id_tarife." and tip_tarif=".$this->tip_tarif;	
		$sql = $this->db->QFetchRowAssoc($query);
		if(empty($sql)) // no records for this customer in tarif_det table
		{
			$this->insertTarifDet();
		}
		else // update tarif
			$this->updateTarifDet();
	}
	
	private function insertTarifDet()
	{
		$vars=[];
 		foreach($this->fields as $value) $vars[$value]=$this->{$value};
 		$vars['id_tarife']=$this->id_tarife;
 		unset($vars['id']);
 		$this->id=$this->db->QueryInsert($this->tables['tarife_det'], $vars);
	}
	
	private function updateTarifDet()
	{
		$vars=[];
 		foreach($this->fields as $value) $vars[$value]=$this->{$value};
 		unset($vars['id']);
 		$this->db->QueryUpdate($this->tables['tarife_det'], $vars, " where id=".$this->id);
	}
	
	public function __get($property) 
	{
		if (isset($this->$property)) return $this->$property;
    	throw new Exception('Proprietate(tarif_det) invalida !');			
	}
	
	public function __set($property, $value) { $this->$property = $value; }
	
	public function getTipTarif() { return $this->tip_tarif; } /*local 0 sau national 1*/ 
	
	public function getValoareAsigurare($val=0)
	{
	 	if(!empty($val))
	 		return $this->proc_asig_loco * $val;
	 	return 0;
	}
	 
	public function getValoareRamburs($val=0)
	{
	 	if(!empty($val))
	 		return $this->taxa_ramb_loco + $this->asig_ramburs_loco * $val;
	 	return 0;
	}
}	 
//end class
?>