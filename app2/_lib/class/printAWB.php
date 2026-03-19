<?php

/**
 * printare
 *
 */
require_once "NewAwbPdf.php";
require_once "PuisorPdf.php";

class ModulPrintAWB extends BackEnd {

    public $final_result;
	public $expeditie = false;
	public $page_position = 0;

	public $print_awb;
	public $master_id;
	public $expeditor_id;
	public $has_pcs;
	public $is_pc;
	public $selectie_puncte_de_lucru;
	public $is_master;
	public $pcs = [];
	public $expeditor_nume;
	public $expeditor_localitate_id;
	public $expeditor_localitate;
	public $expeditor_localitate_km;
	public $expeditor_adresa;
	public $expeditor_judet;
	public $expeditor_contact;
	public $expeditor_telefon;
	public $not_print_phone;

    /**
     * The constructor for the 'Workspace' class
     * Calls BackEnd constructor
     * Cals Actions function
     *
     * @param array $config  an array with the configuration params read from '/_lib/config/config.php'
     * @param integer $act  (0/1) Specifies if actions are alowed or not
     * @access public
     * @see Actions()
     */
    function __construct($config = 0, $act = 1, $db = 0) {
        parent :: __construct($config, $db);

		$this->expeditor_id = $_SESSION["expeditor"]["id"] ?? 0;
		$this->expeditor_nume = $_SESSION["expeditor"]["nume"] ?? "";
		$this->expeditor_localitate_id = $_SESSION["expeditor"]["localitate_id"] ?? 0;
		$this->expeditor_localitate = $_SESSION["expeditor"]["localitate"] ?? "";
		$this->expeditor_localitate_km = $_SESSION["expeditor"]["localitate_km"] ?? 0;
		$this->expeditor_adresa = $_SESSION["expeditor"]["adresa"] ?? "";
		$this->expeditor_judet = $_SESSION["expeditor"]["judet"] ?? "";
		$this->expeditor_contact = $_SESSION["user"]["nume"] ?? "";
		$this->expeditor_telefon = $_SESSION["user"]["telefon"] ?? "";

		$this->pcs = $_SESSION["master"]["pcs"] ?? [];
		if(count($this->pcs) == 0)
			$this->pcs[$this->expeditor_id] = [
				'id' => $this->expeditor_id,
				'nume' => $this->expeditor_nume,
				'adresa' => $this->expeditor_adresa,
				'localitate' => $this->expeditor_localitate,
				'localitate_id' => $this->expeditor_localitate_id,
				'judet' => $this->expeditor_judet,
				'localitate_km' => $this->expeditor_localitate_km,
			];
		$this->has_pcs = count($this->pcs) > 1;
		$this->is_pc = $_SESSION["expeditor"]["is_pc"] ?? false;
		$this->master_id = $_SESSION["expeditor"]["master_id"] ?? $this->expeditor_id;
		if($this->master_id == 0) $this->master_id = $this->expeditor_id;

		$this->selectie_puncte_de_lucru = $this->is_master && $this->has_pcs && ($_SESSION["user"]["selectie_puncte_de_lucru"] ?? 0) > 0;

		$this->print_awb = $_SESSION["user"]["print_awb"] ?? 1;
		$this->not_print_phone = $this->is_pc ? ($_SESSION["master"]["not_print_phone"] ?? 0) : ($_SESSION["expeditor"]["not_print_phone"] ?? 0);

		if ($act) {
            //ACTIONS
            $this->Actions();
        }
    }

//-------------------- a c t i o n s  d e c i s i o n s ------------------------------------------------------------

    /**
     * A c t i o n s
     * Choosing what actions, depending on the profile (if administrator is not loged in - 0, or is loged in - 9), to take.
     *
     * @param string $msg  A message that can be displayed
     * @access public
     */
    function Actions() {
        $this->final_result = '';

        // A D M I N
        if (isset($_GET['logout']))
            $this->Logout();

        else if (!empty($this->user_profile))
            $this->ActionsNivelAcces($this->user_profile);
        else
            $this->final_result = $this->PageNotFound();
        // R E S U L T
        return $this->final_result;
    }


    function ActionsNivelAcces($nivel_acces) {
        $arr = $this->GenerateArr();
        //nivel acces 10
		$nivel_acces = [];
		$nivel_acces[] = 'printAWB';

		if (in_array("printAWB",$nivel_acces))
        {
			if ( $arr[1] == 'print_expeditie')
				echo $this->PrintExpeditie();
	        else if ( $arr[1] == 'multipla')
            	echo $this->PrintareMultipla();
            else if ( $arr[1] == 'multipla_master')
            	echo $this->PrintareMultiplaMaster();
            else if ( $arr[1] == 'multipla_puisori')
            	echo $this->PrintareMultiplaPuisori();
            else if ( $arr[1] == 'multipla_master_puisori')
                echo $this->PrintareMultiplaPuisori();
			else
            	$this->final_result = $this->PageNotFound();
		}
        else
            $this->final_result = $this->PageNotFound();
    }

 //-------------------------------- functii ----------------------------------------





/*/////////////////////////////////////////////////////////////
				 Print AWB
/////////////////////////////////////////////////////////////*/

	function PrintExpeditie(){
		$expeditie = intval($_POST['expeditie'] ?? 0);
		$this->print_awb = intval($_POST['tip'] ?? $this->print_awb);

		if($expeditie == 0) {
			return "";
		}

		if(false === ($sql = $this->GetValuesClient($expeditie))) return "";

		$cu_master  = ($this->print_awb == 5 || $this->print_awb == 6) ? true:false;
		if($this->print_awb == 3){
			$pdf = new PuisorPdf();
		} 
		else {
			$format = ($this->print_awb == 6) ? 'A4':'';
			$pdf = new NewAwbPdf(array('autocolant' => true ,'print_awb'=> $this->print_awb ,'cu_master'=> $cu_master, 'format'=>$format ) );
		}

		$filename = 'Expeditie-'.$expeditie.'.pdf';
		$pdf->setTitle("Expeditie ".$expeditie);

		$this->page_position = 1;
		if(!($sql['piese'] <= 1 && $this->print_awb != 5 && $this->print_awb != 6)) {
			if(false !== $this->GenerareExpTCPDF($pdf, $sql, 3))
				$this->db->QueryUpdate('client_expeditii', array('printed_by'=>$this->user_id, 'printed_at'=>date('Y-m-d H:i:s'), 'printed'=>1), 'expeditie = ' . $sql['expeditie']);
		}

		if($pdf->getNumPages() == 0) { $pdf->AddPage(); $pdf->writeHTML('nici un puisor in expeditiile din borderoul printat !', true, 0, true, 0); }
		// move pointer to last page
		$pdf->lastPage();

		//I: send the file inline to the browser.
		$pdf->Output($filename, 'I');
		exit;
	}

	function PrintareMultipla(){
		$borderou_id = intval($_POST['borderou_id'] ?? 0);
		$pData = isset($_POST['pJson']) ? base64_decode($_POST['pJson']):'';

		if($borderou_id == 0) {
			if(empty($pData)) return "";
			$pData = json_decode($pData, true);
			if(empty($pData) || !is_array($pData)) return "";
			if(false === ($sqlExp = $this->GetValuesClient($pData))) return "";
		}
		else {
			if(false === ($sqlExp = $this->GetValuesClient(0, $borderou_id))) return "";
		}

		$pdf = new NewAwbPdf();
		if($borderou_id > 0) {
			$filename = 'Expeditii-in-Borderou-'.$borderou_id.'.pdf';
			$pdf->setTitle("Borderou ".$borderou_id);
		}
		else{
			$filename = 'Expeditii-printare-multipla.pdf';
			$pdf->setTitle("Expeditii printare multipla");
		}
		foreach ($sqlExp as $key => $row) {
			$this->GenerareExpTCPDF($pdf, $row);
			$this->db->QueryUpdate('client_expeditii', array('printed_by'=>$this->user_id, 'printed_at'=>date('Y-m-d H:i:s'), 'printed'=>1), 'expeditie = ' . $row['expeditie']);
		}

		// move pointer to last page
		$pdf->lastPage();

		//I: send the file inline to the browser.
		$pdf->Output($filename, 'I');
		exit;
	}

	function PrintareMultiplaMaster(){
		$borderou_id = intval($_POST['borderou_id'] ?? 0);
		$pData = isset($_POST['pJson'])?base64_decode($_POST['pJson']):'';
		if($borderou_id == 0) {
			if(empty($pData)) return "";
			$pData = json_decode($pData, true);
			if(empty($pData) || !is_array($pData)) return "";
			if(false === ($sqlExp = $this->GetValuesClient($pData))) return "";
		}
		else {
			if(false === ($sqlExp = $this->GetValuesClient(0, $borderou_id))) return "";
		}

		$pdf = new NewAwbPdf();
		$pdf->doarMaster = true;
		if($borderou_id > 0) {
			$filename = 'Expeditii-master-in-Borderou-'.$borderou_id.'.pdf';
			$pdf->setTitle("Borderou ".$borderou_id);
		}
		else{
			$filename = 'Expeditii-master-multipla.pdf';
			$pdf->setTitle("Expeditii master multipla");
		}
		foreach ($sqlExp as $key => $row) {
			$this->GenerareExpTCPDF($pdf, $row, 2);
			$this->db->QueryUpdate('client_expeditii', array('printed_by'=>$this->user_id, 'printed_at'=>date('Y-m-d H:i:s'), 'printed'=>1), 'expeditie = ' . $row['expeditie']);
		}

		// move pointer to last page
		$pdf->lastPage();

		//I: send the file inline to the browser.
		$pdf->Output($filename, 'I');
		exit;
	}

	function PrintareMultiplaPuisori(){
		$borderou_id = intval($_POST['borderou_id'] ?? 0);
		$pData = isset($_POST['pJson'])?base64_decode($_POST['pJson']):'';
		$this->print_awb = intval($_POST['tip'] ?? $this->print_awb);
		
		if($borderou_id == 0) {
			if(empty($pData)) return "";
			$pData = json_decode($pData, true);
			if(empty($pData) || !is_array($pData)) return "";
			if(false === ($sqlExp = $this->GetValuesClient($pData))) return "";
		}
		else {
			if(false === ($sqlExp = $this->GetValuesClient(0, $borderou_id))) return "";
		}

		$cu_master  = ($this->print_awb == 5 || $this->print_awb == 6);

		if($this->print_awb == 3){
			$pdf = new PuisorPdf();
		} else {
			$format = ($this->print_awb == 6) ? 'A4':'';
			$pdf = new NewAwbPdf(array('autocolant' => true ,'print_awb'=> $this->print_awb ,'cu_master'=> $cu_master, 'format'=>$format ) );
		}

		if($borderou_id > 0) {
			$filename = 'Expeditii-puisori-in-Borderou-'.$borderou_id.'.pdf';
			$pdf->setTitle("Borderou ".$borderou_id);
		}
		else{
			$filename = 'Expeditii-puisori-multipla.pdf';
			$pdf->setTitle("Expeditii puisori multipla");
		}

		$this->page_position = 1;
		foreach ($sqlExp as $key => $row) {
			if($row['piese'] <=1 && !in_array($this->print_awb, [5, 6]) ) continue;
			if(false !== $this->GenerareExpTCPDF($pdf, $row, 3))
				$this->db->QueryUpdate('client_expeditii', array('printed_by'=>$this->user_id, 'printed_at'=>date('Y-m-d H:i:s'), 'printed'=>1), 'expeditie = ' . $row['expeditie']);
		}
		if($pdf->getNumPages() == 0) { $pdf->AddPage(); $pdf->writeHTML('nici un puisor in expeditiile din borderoul printat !', true, 0, true, 0); }
		// move pointer to last page
		$pdf->lastPage();

		//I: send the file inline to the browser.
		$pdf->Output($filename, 'I');

		exit;
	}

    function GenerareExpTCPDF($pdf, $vars, $tip = 1){
        if(!is_array($vars)) return false;

		require_once "expeditieDto.php";
		$vars = ExpeditieDto::sqlExpClientToPdf($vars);
		$vars['destinatar_nume'] = strtoupper(htmlspecialchars_decode(strtolower($vars['destinatar_nume']), ENT_QUOTES));
		$vars['destinatar_contact'] = strtoupper(htmlspecialchars_decode(strtolower($vars['destinatar_contact']), ENT_QUOTES));
		$vars['expeditor_telefon'] = $this->not_print_phone ? '' : strtoupper(htmlspecialchars_decode(strtolower($vars['expeditor_telefon']), ENT_QUOTES));
		$vars['destinatar_telefon'] = $this->not_print_phone ? '' : strtoupper(htmlspecialchars_decode(strtolower($vars['destinatar_telefon']), ENT_QUOTES));

		//nota de comanda
		$nc = false;
		if(!empty($vars['extrainfo'])) $nc = true;
		
		$pdf->setVars($vars);
		$pdf->print_awb = $this->print_awb;
		//master
		if($tip == 1 || $tip == 2)
		{
			$pdf->AddPage();
			$pdf->makeHalfFirstPage(0, 0);
			$pdf->makeDashedLine('H');
			if($nc)
				$pdf->makeNotaComanda(0, 148);
			else
				$pdf->makeHalfFirstPage(0, 148);
		}

		$piese = $vars['piese'];
		//puisori
		if(($tip == 1 || $tip == 3) && get_class($pdf) == 'NewAwbPdf' && !$pdf->doarMaster)
		{
			if($piese > 1 || in_array($this->print_awb, [5, 6])) {
				//puisori
				$i=1; $j=2; $k=1;
				if($this->print_awb == 6)
                    $k = $this->page_position;

				if($pdf->doarPuisori === false){
                    $j=1;
				}

				$nr_pag = ceil($piese);

				for($ix=0; $ix < $nr_pag && $piese > 0 && $j <= $vars['piese']; $ix++)
				{

					if($pdf->autocolant && $this->print_awb != 6){
						$pdf->AddPage();
						if(!in_array($this->print_awb, [4, 5])){
							$pdf->makeDashedLine('H');
							$pdf->makeDashedLine('V');
						}
					} 
					else {
						if($k == 1){
							$pdf->AddPage();
							$pdf->makeDashedLine('H');
							$pdf->makeDashedLine('V');
						}
					}
                    $cod_bare = $vars['expeditie'];
					if($j > 1)
						$cod_bare .= '-'.ExpeditieDto::getPuisorNr($j);

					$pdf->makePuisorMultiCell($cod_bare,$j++,$k++);
					$i++;

					if($k == 5) //am terminat o pagina
					{
						$k=1;
						if($i < $nr_pag)
							$i++;
					}
                    $this->page_position = $k;
				}
				// move pointer to last page
				$pdf->lastPage();

			}
		} 
		else if(!isset($pdf->doarMaster)) {
			$j=2; $k=1;
			$nr_pag = ceil($piese);
            for($i=0; $i < $nr_pag && $piese > 0 && $j <= $vars['piese']; $i++) {
				$pdf->AddPage();
				$cod_bare = $vars['expeditie'] .'-'. ExpeditieDto::getPuisorNr($j);
				$pdf->makePuisorMultiCell($cod_bare, $j++, $k++);
			}
		}

    }
}