<?php
/**
 * Tarife 11 mars 2014
 * 2 tipuri de contracte : negociat : 1 sau de lista : 0 .... tarif in tabla clienti
 * 2 tipuri de tarife : TARIF_LOCO : 0, TARIF_NATIONAL : 1
 * 2 tipuri de contracte : negociat, de lista ( pentru lista id_cl=0 in tabla tarife ; deci $client=0)
 * 2 tipuri de obiecte : COLET : 0, PALET : 1
 */

class Tarif { // tarif de lista -> id_cl = 0 in tabla tarife

	const KM_LIMIT = 15; //15 km -> sub limita se calculeaza 0km
	private $fields=array('id', 'id_cl', 'moneda', 'km_limit_prel', 'km_limit_livr', 'tarif_prel', 'tarif_livr', 'tarif_prel_tip', 'tarif_livr_tip', 'plata_retur', 'taxa_expediere', 'taxa_ramburs', 'taxa_destinatie', 'tarif_returnare', 'ret_amb', 'obs', 'kg_ret_amb', 'tarif_sms', 'tarif_open', 'tarif_proc_indexc');

	public function __construct($db, $client=0) //tarif national : default
	{
		$this->db = $db; // $db
		$this->client = $client;
		if(empty($this->client)) $this->client=0;
		$this->selectTarif();
	}

	public static function getInstanceTarifLista($db)
	{
		return new Tarif($db, 0);
	}

	private function selectTarif()
	{
		$query = "select ".implode(',',$this->fields)."  from tarife where id_cl=".$this->client;
		$sql = $this->db->QFetchRowAssoc($query);
		if(empty($sql)) // expeditor fara contract -> tarif de lista -> $client=0;
		{
			$this->client=0;
			$query = "select ".implode(',',$this->fields)."  from tarife where id_cl=".$this->client;
        	$sql = $this->db->QFetchRowAssoc($query);
		}
		if(!empty($sql))
			foreach($sql as $key => $value) $this->{$key} = $value;

	}// am incarcat tariful in memorie pentru clientul respectiv

	public function __get($property)
	{
		if (isset($this->$property)) return $this->$property;
    	throw new Exception('Proprietate(tarif) invalida !');
	}

	public function __set($property, $value) { $this->$property = $value; }

	public function setTarif($client, $new_tarif)
	{
		$this->client = $client;
		//load new tarif
		foreach($this->fields as $value) $this->{$value}=$new_tarif[$value];
		//if client has record in tarife table => update
		$query = "select id_cl from tarife where id_cl=".$client;
		$sql = $this->db->QFetchRowAssoc($query);
		if(empty($sql)) // no records for this customer in tarife table
		{
			$this->InsertTarif();
		}
		else // update tarif
			$this->UpdateTarif();
	}

	private function insertTarif()
	{
		$vars=[];
 		foreach($this->fields as $value) $vars[$value]=$this->{$value};
 		unset($vars['id']);
 		$this->id = $this->db->QueryInsert($this->tables['tarife'], $vars);
	}

	private function updateTarif()
	{
		$vars=[];
 		foreach($this->fields as $value) $vars[$value]=$this->{$value};
 		unset($vars['id']);
 		$this->db->QueryUpdate($this->tables['tarife'], $vars, " where id=".$this->id);
	}

	public static function DeleteTarifCascade($client, $db = null) //cascade delete ? all tarif for this client
	{
		$db->Query("DELETE FROM tarife_g where id_tarife_det in ( select id from tarife_det where id_tarife in ( select id from tarife where id_cl=".$client." ))");
		$db->Query("DELETE FROM tarife_det where id_tarife in ( select id from tarife where id_cl=".$client." )");
		$db->Query("DELETE FROM tarife where id_cl=".$client);
	}

	public function getValoareKM($km_prel = 0, $km_livr = 0)
    {
		//self::KM_LIMIT
		//la livrare
		$ret_val=0.00;
		$km_prel = floatval($km_prel);
		$km_livr = floatval($km_livr);

		if($km_prel > $this->km_limit_prel)
		{
			if(!empty($this->tarif_prel_tip))
				$ret_val += $this->tarif_prel;
			else
				$ret_val += $km_prel * $this->tarif_prel;
		}
		if($km_livr > $this->km_limit_livr)
		{
			if(!empty($this->tarif_livr_tip))
				$ret_val += $this->tarif_livr;
			else
				$ret_val += $km_livr * $this->tarif_livr;
		}

		if($this->tarif_proc_indexc > 0)
			$ret_val += ($ret_val * $this->tarif_proc_indexc) / 100;

		return round($ret_val, 2);
	}

	public function isNegociat() { return ($this->client > 0) ? 1: 0; }
}
//end class
?>