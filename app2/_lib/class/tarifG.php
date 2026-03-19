<?php
/**
 * Tarife 11 mars 2014
 * 2 tipuri de tarife : TARIF_LOCO : 0, TARIF_NATIONAL : 1
 * 2 tipuri de contracte : negociat, de lista ( pentru tarif de lista id_cl=0 in tabla tarife ; deci $client=0)
 * 2 tipuri de obiecte : COLET : 0, PALET : 1
 * COLET : praguri + per kg
 * PALET : praguri sau praguri + per kg
 */
 
class TarifG { // tarif de lista -> id_cl=0 in tabla tarife

	private $db;
	private $id_tarife_det;
	private $tip_tarif;
	private $tip;
	public $tarife; // array cu liniile din tarife_g
	public $tarifColetReplacePalet; // daca e tarif palet si nu exista tarif palet, se pune tariful de colet

	const COLET = 0; // TIP in table tarife_g
	const PALET = 1;
	const TARIF_LOCO = 0;
	const TARIF_NATIONAL = 1;
    private $fields=array('id',  'g_init', 'g_fin', 'km_init', 'km_fin', 'val_init', 'inc_val', 'inc_greut');
	
	public function __construct($db, $id_tarife_det, $tip_tarif=self::TARIF_NATIONAL, $tip=self::COLET, $km_dist=1) //tarif colet : default
	{
		$this->db=$db;
		$this->id_tarife_det=$id_tarife_det;
		$this->tip_tarif=$tip_tarif;
		$this->tip=$tip;
		$this->tarife = NULL;
		$this->tarifColetReplacePalet = 0;
		$this->selectTarifG($km_dist);
	}
	
	private function selectTarifG($km_dist)
	{
		if($this->tip == self::PALET){
    		$this->selectTarifGPalet($km_dist);
			return;
		}
		$this->selectTarifGColet();
	}

	private function selectTarifGPalet($km_dist)
	{
    	$query="select ".implode(',',$this->fields)." from tarife_g 
    		where id_tarife_det=".$this->id_tarife_det." and tip = ". self::PALET ."
    		and km_init <= ".$km_dist." and km_fin > ".$km_dist."
    		order by g_init, g_fin";
    	$sql = $this->db->QFetchRowArray($query);
    	if(empty($sql)) {
			// punem tariful de lista la colete ... nu exista tarif de lista la paleti
    		$this->selectTarifGColet();
			$this->tarifColetReplacePalet = 1;
			return;
		}
    	$this->tarife = $sql;
	}

	private function selectTarifGColet()
	{
		$query="select ".implode(',',$this->fields)." from tarife_g 
    			where id_tarife_det=".$this->id_tarife_det." and tip = ". self::COLET ." order by g_init, g_fin";
    	$sql = $this->db->QFetchRowArray($query);

    	if(empty($sql))
    	{
			// punem tariful de baza
    		$query="select ".implode(',',$this->fields)." from tarife_g where id_tarife_det = (select id from tarife_det where tip_tarif=".$this->tip_tarif." 
    					and id_tarife=(select id from tarife where id_cl=0)) and tip = ". self::COLET ." order by g_init, g_fin";
    		$sql = $this->db->QFetchRowArray($query);
    	}
    	if(!empty($sql))
    		$this->tarife = $sql;
	}

	private function getLineTarifG($id)
	{
    	foreach($this->tarife as $key=>$row)
			if($row['id']==$id)
			{
				$vars=[];
				foreach($this->fields as $value) $vars[$value]=$row[$value];
				return $vars;
			}
	}
	
	public function setLineTarifG($line_tarif_g)
	{
		if(!empty($line_tarif_g['id']))
		{
    		foreach($this->tarife as $key=>$row)
				if($row['id']==$line_tarif_g['id'])
					foreach($this->fields as $value) $row[$value]=$line_tarif_g[$value];
		}
		else // this is a new line ... insert
		{
			$line_tarif_g['id']=$this->InsertLineTarifG($line_tarif_g);
			$this->tarife[] = $line_tarif_g;
		}
	}
	
	private function insertLineTarifG($line_tarif_g)
	{
		$vars=[];
		foreach($this->fields as $value) $vars[$value]=$line_tarif_g[$value];
		unset($vars['id']);
		$this->db->QueryInsert('tarife_g', $vars);
	}
	
	private function updateLineTarifG($id)
	{
		foreach($this->tarife as $key=>$row)
			if($row['id']==$id)
			{
				$vars=[];
				foreach($this->fields as $value) $vars[$value]=$row[$value];
				unset($vars['id']);
				$this->db->QueryUpdate('tarife_g', $vars, " where id=".$id);
			}
	}
		
	private function deleteLineTarifG($id)
	{ $this->db->Query("DELETE FROM tarife_g where id=".$id); }
		

    public function getValoareGreutate($greutate = 0, $greutate_vol = 0, $indexC = 0)
    {
    	if(!is_array($this->tarife)) return 0;
		$greutate = floatval($greutate);
    
		if(!empty($greutate_vol) && ($greutate < $greutate_vol) ) $greutate = floatval($greutate_vol);
		
		if($greutate < 1) return 0.00;
    	
    	$ret_val = 0.00;
    	$val = 0.00;
    	$stop = false;
    	$i = 0;
		while($greutate > 0 && !$stop && $i < 100)
		{
			$val_g_fin = 0.00;
			$val = 0.00;
			foreach($this->tarife as $key=>$row)
			{
				if(!$stop)
				{	
					if(floatval($row['inc_greut'])==floatval($row['g_fin'])) //palier
						$val = floatval($row['inc_val']) + floatval($row['val_init']);
					else if(floatval($row['inc_greut']) == floatval($row['g_fin']) - floatval($row['g_init'])) //paliere
						$val += floatval($row['inc_val']) + floatval($row['val_init']);
					else if(floatval($row['inc_greut']) < floatval($row['g_fin']))
					{
						$temp = 0.00;
						if($greutate > floatval($row['g_fin'])) { $temp = floatval($row['g_fin']) - floatval($row['g_init']); }
						else { $temp = $greutate - floatval($row['g_init']); $stop = true; }
						if(intval($row['inc_greut']) > 0)
							$val += ($temp/floatval($row['inc_greut'])) * floatval($row['inc_val'])  + floatval($row['val_init']);
					}
				}
				if($greutate > floatval($row['g_init']) && $greutate <= floatval($row['g_fin'])) // am gasit greutatea ... ma opresc : stop
					$stop = true;
				$val_g_fin = floatval($row['g_fin']);
			}
			$greutate -= $val_g_fin;
			$ret_val += $val;
			$i++;
		}
		if($indexC > 0)
			$ret_val += ($ret_val * $indexC) / 100;
		return round($ret_val, 2);
	 }	 
}
