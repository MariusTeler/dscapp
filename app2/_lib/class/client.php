<?php

require_once 'expeditii.php';

use PhpOffice\PhpSpreadsheet\Reader\Csv;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;

/**
 * W o r k s p a c e
 *
 */
class ModulClient extends BackEnd {

    public $final_result;
    public $page_prefix;
    public $site_prefix;
	public $table;
	public $recantarite;
	public $importCSV = false;
	public $importXLS = false;
	public $editAfterPrinted = false;

	public $expeditor_id;
	public $expeditor_nume;
	public $expeditor_contact;
	public $expeditor_telefon;
	public $expeditor_adresa;
	public $expeditor_localitate_id;
	public $expeditor_localitate;
	public $expeditor_localitate_km;
	public $expeditor_judet;
	public $expeditor_cc;
	public $not_print_phone;

	public $master_id;
	public $pcs;
	public $has_pcs;
	public $is_pc;
	public $selectie_puncte_de_lucru;
	public $show_master_clienti;

	public $preturi;
	public $print_awb;
	public $print_add;
	
	public $taxa_destinatie;
	public $taxa_expediere;
	public $ret_amb;
	public $kg_ret_amb;
	public $mod_plata;
	public $contract;
	public $tarif_individual;
	public $tarif_sms;
	public $tarif_palet;
	public $def_obsv;
	public $def_sms;
	public $master_cui;
	public $master_j;

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
    function __construct($config = 0, $act = 0, $db = 0) {
    	parent :: __construct($config, $db);
		$this->procTva = $this->getProcentTVA(date("Y-m-d"));
		//$this->log(print_r($_SESSION, true), parent::APP_LOG_FILE);

		$this->expeditor_id = $_SESSION["expeditor"]["id"] ?? 0;
		$this->expeditor_nume = $_SESSION["expeditor"]["nume"] ?? "";
		$this->expeditor_localitate_id = $_SESSION["expeditor"]["localitate_id"] ?? 0;
		$this->expeditor_localitate = $_SESSION["expeditor"]["localitate"] ?? "";
		$this->expeditor_localitate_km = $_SESSION["expeditor"]["localitate_km"] ?? 0;
		$this->expeditor_adresa = $_SESSION["expeditor"]["adresa"] ?? "";
		$this->expeditor_judet = $_SESSION["expeditor"]["judet"] ?? "";
		$this->expeditor_contact = $_SESSION["user"]["nume"] ?? "";
		$this->expeditor_telefon = $_SESSION["user"]["telefon"] ?? "";
		$this->expeditor_cc = $this->is_pc ? (($_SESSION["expeditor"]["icc"] ?? 0) == 1 ? $_SESSION["expeditor"]["cc"] ?? 0 : $_SESSION["master"]["cc"] ?? 0) : $_SESSION["expeditor"]["cc"] ?? 0;
		$this->not_print_phone = $this->is_pc ? ($_SESSION["master"]["not_print_phone"] ?? 0) : ($_SESSION["expeditor"]["not_print_phone"] ?? 0);

		$this->pcs = $_SESSION["master"]["pcs"] ?? [];
		if(count($this->pcs) == 0)
			$this->pcs[$this->expeditor_id] = [
				'id' => $this->expeditor_id,
				'nume' => htmlspecialchars($this->expeditor_nume, ENT_QUOTES),
				'adresa' => htmlspecialchars($this->expeditor_adresa, ENT_QUOTES),
				'localitate' => htmlspecialchars($this->expeditor_localitate, ENT_QUOTES),
				'localitate_id' => $this->expeditor_localitate_id,
				'judet' => $this->expeditor_judet,
				'localitate_km' => $this->expeditor_localitate_km
			];
		//error_log(print_r($this->pcs, true));
		$this->has_pcs = count($this->pcs) > 1;
		$this->is_pc = $_SESSION["expeditor"]["is_pc"] ?? false;
		$this->master_id = $_SESSION["expeditor"]["master_id"] ?? $this->expeditor_id;
		if($this->master_id == 0) $this->master_id = $this->expeditor_id;

		$this->selectie_puncte_de_lucru = $this->is_master && $this->has_pcs && ($_SESSION["user"]["selectie_puncte_de_lucru"] ?? 0) == 1;
		/*
		if($this->user_id == 4762) {
			error_log("{$this->is_master} : {$this->has_pcs} : " . ($_SESSION["user"]["selectie_puncte_de_lucru"] ?? 0));
		}
		*/
		if(!$this->selectie_puncte_de_lucru) {
			$this->pcs = [];
			$this->pcs[$this->expeditor_id] = [
				'id' => $this->expeditor_id,
				'nume' => htmlspecialchars($this->expeditor_nume, ENT_QUOTES),
				'adresa' => htmlspecialchars($this->expeditor_adresa, ENT_QUOTES),
				'localitate' => htmlspecialchars($this->expeditor_localitate, ENT_QUOTES),
				'localitate_id' => $this->expeditor_localitate_id,
				'judet' => $this->expeditor_judet,
				'localitate_km' => $this->expeditor_localitate_km
			];
		}
		$this->show_master_clienti = ($_SESSION["user"]["show_master_clienti"] ?? 2) == 1;
		$this->print_awb = $_SESSION["user"]["print_awb"] ?? 1;
		$this->def_obsv = $_SESSION["user"]["def_obsv"] ?? "";
		$this->def_sms = $_SESSION["user"]["def_sms"] ?? 0;
		$this->print_add = $_SESSION["user"]["print_add"] ?? 1;
		$this->preturi = $_SESSION["user"]["preturi"] ?? 2;
		$this->tarif_individual = $_SESSION["expeditor"]["tarif_individual"] ?? 0;

		//error_log($this->expeditor_id . ":" . intval($this->is_pc ? (($_SESSION["expeditor"]["icc"] ?? 0) == 1 ? $_SESSION["expeditor"]["cc"] ?? 0 : $_SESSION["master"]["cc"] ?? 0) : $_SESSION["expeditor"]["cc"] ?? 0));
		$this->mod_plata = $this->is_pc ? ($this->tarif_individual > 0 ? $_SESSION["expeditor"]["mod_plata"] ?? 0 : $_SESSION["master"]["mod_plata"] ?? 0) : $_SESSION["expeditor"]["mod_plata"] ?? 0;
		$this->taxa_destinatie = $this->is_pc ? ($this->tarif_individual > 0 ? $_SESSION["expeditor"]["taxa_destinatie"] ?? 0 : $_SESSION["master"]["taxa_destinatie"] ?? 0) : $_SESSION["expeditor"]["taxa_destinatie"] ?? 0;
		$this->taxa_expediere = $this->is_pc ? ($this->tarif_individual > 0 ? $_SESSION["expeditor"]["taxa_expediere"] ?? 0 : $_SESSION["master"]["taxa_expediere"] ?? 0) : $_SESSION["expeditor"]["taxa_expediere"] ?? 0;
		$this->ret_amb = $this->is_pc ? ($this->tarif_individual > 0 ? $_SESSION["expeditor"]["ret_amb"] ?? 0 : $_SESSION["master"]["ret_amb"] ?? 0) : $_SESSION["expeditor"]["ret_amb"] ?? 0;
		$this->kg_ret_amb = $this->is_pc ? ($this->tarif_individual > 0 ? $_SESSION["expeditor"]["kg_ret_amb"] ?? 20 : $_SESSION["master"]["kg_ret_amb"] ?? 20) : $_SESSION["expeditor"]["kg_ret_amb"] ?? 20;
		$this->contract = $this->is_pc ? ($this->tarif_individual > 0 ? $_SESSION["expeditor"]["contract"] ?? 0 : $_SESSION["master"]["contract"] ?? 0) : $_SESSION["expeditor"]["contract"] ?? 0;
		$this->tarif_sms = $this->is_pc ? ($this->tarif_individual > 0 ? $_SESSION["expeditor"]["tarif_sms"] ?? 0 : $_SESSION["master"]["tarif_sms"] ?? 0) : $_SESSION["expeditor"]["tarif_sms"] ?? 0;
		if($this->user_id == 8813) {
			error_log("{$this->is_pc} : {$this->tarif_individual} : {$_SESSION["expeditor"]["tarif_palet"]} : {$_SESSION["master"]["tarif_palet"]}");
		}
		$this->tarif_palet = $this->is_pc ? ($this->tarif_individual > 0 ? $_SESSION["expeditor"]["tarif_palet"] ?? 0 : $_SESSION["master"]["tarif_palet"] ?? 0) : $_SESSION["expeditor"]["tarif_palet"] ?? 0;
		$this->recantarite = $_SESSION["user"]["recantarite"] ?? 2;

		$this->master_cui = $this->is_pc ? $_SESSION["master"]["cui"] ?? 0 : $_SESSION["expeditor"]["cui"] ?? 0;
		$this->master_j = $this->is_pc ? $_SESSION["master"]["j"] ?? 0 : $_SESSION["expeditor"]["j"] ?? 0;

        $this->vars['MENIU_IMPORT'] = $this->vars['MENIU_RECANTARITE'] = $this->vars['MENIU_IMPORT_XLS'] = 'style="display:none"';

		//import CSV + XLS
		if(($_SESSION["user"]["importcsv"] ?? 2) == 1) {
			if($this->master_id == 171350 || $this->master_id ==  435076) {
				//maravet, biovet
				$this->vars['MENIU_IMPORT_XLS'] = '';
				$this->importXLS = true;
			}
			else {
				$this->vars['MENIU_IMPORT'] = '';
				$this->importCSV = true;
			}
		}

		//modificare after print maravet, biovet, aston com sa, vivo clima srl, MONTERO VET SRL
		if($this->master_id == 171350 || $this->master_id == 435076 || $this->master_id == 1631955
			|| $this->master_id == 1631972 || $this->master_id == 3361158)

			$this->editAfterPrinted = true;


		if($this->recantarite == 1) $this->vars['MENIU_RECANTARITE'] = '';

		session_write_close();

        if(!empty($act)) {
        	$this->vars['title_page'] = 'Client';
        	$this->page_prefix = 'client_';
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
    function Actions($msg = '') {
        $this->final_result = '';

        // A D M I N
        if (isset($_GET['logout']))
            $this->Logout();
        else if (!empty($this->user_profile))
            $this->ActionsNivelAcces();
        else{
        	$this->ActionsNivelAcces(0);
        }

       // R E S U L T
        return $this->final_result;
    }


    function ActionsNivelAcces() {
        $arr = $this->GenerateArr();

        if ($this->user_profile == 9){
            if(!isset($arr[1]))
				$this->final_result = $this->Parse($this->page_prefix . 'home.html', ['title_page' => 'Info']);
            else if ($arr[1] == 'expeditie_noua')
            	$this->final_result = $this->IntroducereExpeditie();
			else if($arr[1] == 'download_confirmare')
				echo $this->DownloadConfirmare();

			else if($arr[1] == 'json' && isset($arr[2]) && $arr[2]=='localitati')
				echo $this->JSON_Localitati();
			else if($arr[1] == 'json' && isset($arr[2]) && $arr[2]=='localitate_judete')
				echo $this->JSON_LocalitateJudete();
			else if($arr[1] == 'json' && isset($arr[2]) && $arr[2]=='clienti')
				echo $this->JSON_Clienti();
			else if($arr[1] == 'json' && isset($arr[2]) && $arr[2]=='clienti_all_info')
				echo $this->JSON_ClientiAllInfo();

			else if ($arr[1] == 'valoare_expeditie')
	            echo $this->ValoareExpeditie();
			else if ($arr[1] == 'adaugare_expeditie')
				echo $this->AdaugareExpeditie();
			else if ($arr[1] == 'editare_expeditie' && !empty($arr[2]))
				echo $this->AdaugareExpeditie($arr[2]);
			else if ($arr[1] == 'stergere_expeditie' && !empty($arr[2]))
				echo $this->StergeExpeditie($arr[2]);
			else if($arr[1] == 'detalii_expeditie')
				echo $this->DetaliiExpeditie();

			else if ($arr[1] == 'print_expeditie')
				echo $this->PrintExpeditieTCPDF();
			else if ($arr[1] == 'print_master_expeditie')
				echo $this->PrintMasterExpeditieTCPDF();
            else if ($arr[1] == 'print_puisori_expeditie')
				echo $this->PrintPuisoriExpeditieTCPDF();
			else if ($arr[1] == 'detalii_expeditii')
            	echo $this->IntroducereDetalii();
        	else if($arr[1] == 'json' && isset($arr[2]) && $arr[2]=='expeditii')
            	echo $this->JSON_Expeditii();

			else if ($arr[1] == 'liste_expeditii')
	            $this->final_result = $this->ListeExpeditii();
			else if($arr[1] == 'json' && isset($arr[2]) && $arr[2]=='liste_expeditii')
				echo $this->JSON_ListeExpeditii();
			else if ($arr[1] == 'export_expeditii')
				echo $this->ExportExpeditii();

			else if ($arr[1] == 'liste_retururi')
	            $this->final_result = $this->ListeRetururi();
			else if($arr[1] == 'json' && isset($arr[2]) && $arr[2]=='liste_retururi')
				echo $this->JSON_ListeRetururi();
			else if ($arr[1] == 'export_retururi')
				echo $this->ExportRetururi();

			else if ($arr[1] == 'recantarite' && $this->recantarite == 1)
	            $this->final_result = $this->Recantarite();
			else if($arr[1] == 'json' && isset($arr[2]) && $arr[2]=='recantarite' && $this->recantarite == 1)
	        	echo $this->JSON_Recantarite();

			else if ($arr[1] == 'export_borderou' && !empty($arr[2]))
				echo $this->ExportBorderou($arr[2]);

			else if ($arr[1] == 'borderou_nou')
				$this->final_result = $this->BorderouNou();
			else if($arr[1] == 'json' && isset($arr[2]) && $arr[2]=='borderou_nou')
	        	echo $this->JSON_Borderou(0);
			else if ($arr[1] == 'generare_borderou')
				$this->final_result = $this->GenerareBorderou();

			else if ($arr[1] == 'liste_borderouri')
	            $this->final_result = $this->ListeBorderouri();
			else if($arr[1] == 'json' && isset($arr[2]) && $arr[2]=='liste_borderouri')
	        	echo $this->JSON_ListeBorderouri();
			else if($arr[1] == 'json' && isset($arr[2]) && $arr[2]=='liste_expeditii_borderou')
	        	echo $this->JSON_Borderou($arr[3]);

			else if ($arr[1] == 'date_personale')
	            $this->final_result = $this->DatePersonale();

			else if ($arr[1] == 'comanda_noua')
	            $this->final_result = $this->ComandaNoua();
	        else if ($arr[1] == 'adaugare_comanda')
				echo $this->AdaugareComanda();
			else if ($arr[1] == 'liste_comenzi')
	            $this->final_result = $this->ListeComenzi();
			else if($arr[1] == 'json' && isset($arr[2]) && $arr[2]=='liste_comenzi')
	        	echo $this->JSON_ListeComenzi();
	        else if($arr[1] == 'json' && isset($arr[2]) && $arr[2]=='istoric_comenzi')
	        	echo $this->JSON_IstoricComenzi();

			else if ($arr[1] == 'print_borderou') {
				echo $this->PrintBorderouTCPDF();
			}

			else if ($arr[1] == 'listare_clienti')
	            $this->final_result = $this->ListareClienti();
			else if($arr[1] == 'json' && isset($arr[2]) && $arr[2]=='listare_clienti')
	        	echo $this->ListareClienti_JSON();
			 else if ($arr[1] == 'editare_client')
				 echo $this->EditareClient();
			 else if ($arr[1] == 'stergere_client' && !empty($arr[2]) )
				 echo $this->StergereClient($arr[2]);
			else if ($arr[1] == 'adaugare_client')
                echo $this->AdaugareClient();
			else if ($arr[1] == 'afisare_client' && !empty($arr[2]) )
                echo $this->AfisareClient($arr[2]);

	    	else if ($arr[1] == 'importuri' && !empty($arr[2]) && $arr[2] == 'expeditii' && $this->importCSV)
            {
					if (!empty($arr[3]) && $arr[3] == 'step2')
						$this->final_result = $this->ImportExpeditiiStep2();
					else if (!empty($arr[3]) && $arr[3] == 'step3')
						$this->final_result = $this->ImportExpeditiiStep3();
					else if (!empty($arr[3]) && $arr[3] == 'step31')
						$this->final_result = $this->ImportExpeditiiStep31();
					else if (!empty($arr[3]) && $arr[3] == 'step4')
						$this->final_result = $this->ImportExpeditiiStep4();
					else
						$this->final_result = $this->ImportExpeditiiStep1();
            }

            else if ($arr[1] == 'import' && !empty($arr[2]) && $arr[2] == 'expeditii' && $this->importXLS)
            {

					if (!empty($arr[3]) && $arr[3] == 'step2')
						$this->final_result = $this->ImportExpeditiiXlsStep2();
					else if (!empty($arr[3]) && $arr[3] == 'step3')
						$this->final_result = $this->ImportExpeditiiXlsStep3();
					else if (!empty($arr[3]) && $arr[3] == 'check')
						$this->ImportExpeditiiXlsCheck();
					else if (!empty($arr[3]) && $arr[3] == 'import')
						$this->ImportExpeditiiXlsImport();
					else
						$this->final_result = $this->ImportExpeditiiXlsStep1();
            }

            else
            	$this->final_result = $this->PageNotFound();

        }else	$this->final_result = $this->SessionExpired();

    }

 //-------------------------------- functii ----------------------------------------

  	function IntroducereExpeditie() {
        $this->vars['title_page'] = "Expeditie noua : " . date("Y-m-d");

		$var=[];
		$var['expeditie'] = 0;
		$var['print'] = ($this->print_awb == 2) ? "Print" : "PDF";

		$var['platitor_1'] = 'checked="checked"';
		$var['PLIC'] = 'checked="checked"';
		$var['greutate'] = number_format(0.500, 3, '.', '');

		$var['user_id'] = $this->print_awb;
		$var['user_add'] = $this->print_add;

		$var['JSON_PCS'] = json_encode($this->pcs);
		$var['SELECTED_ID'] = $this->expeditor_id;
		$var['MASTER_ID'] = $this->master_id;
		$var['SWAPPED'] = 0;
		$var['edit_expedition'] = 0;

		$var['expeditor_id'] = $this->expeditor_id;
		$var['expeditor_nume'] = htmlspecialchars($this->expeditor_nume, ENT_QUOTES);
		$var['expeditor_localitate'] = htmlspecialchars($this->expeditor_localitate, ENT_QUOTES);
		$var['expeditor_localitate_id'] = $this->expeditor_localitate_id;
		$var['expeditor_localitate_km'] = $this->expeditor_localitate_km;
		$var['expeditor_contact'] = htmlspecialchars($this->expeditor_contact, ENT_QUOTES);
		$var['expeditor_telefon'] = htmlspecialchars($this->expeditor_telefon, ENT_QUOTES);
		$var['expeditor_adresa'] = htmlspecialchars($this->expeditor_adresa, ENT_QUOTES);

    	//verificare plan tarifar paleti
		$var['CLASS_TIP_OBJ_3'] = empty($this->tarif_palet) ? 'ascuns' : '';
		//retur ambalaj, nota comanda
		$var['RET_NC_SHOW'] = $var['RET_AMB_SHOW'] = 'ascuns';
		if($this->expeditor_id == 171350) //maravet
			$var['RET_NC_SHOW'] = '';
		if(!empty($this->ret_amb))
			$var['RET_AMB_SHOW'] = '';
		//cont colector
		$var['CC'] = $this->expeditor_cc;

		//afisare preturi
		$var['AFISARE_PRETURI'] = intval($this->preturi == 2);
		//referinte facturare
		$var['SMS'] = (!empty($this->def_sms) && $this->def_sms == 1) ? 'checked' : '';
		$var['observatii'] = $this->def_obsv;

		$var = array_map('htmlspecialchars', $var);

		$vars['formular_expeditie'] = $this->Parse($this->page_prefix . 'expeditie_noua_detalii.html', $var, true);

        return $this->Parse($this->page_prefix . 'expeditie_noua.html', $vars);
    }


	function IntroducereDetalii() {
		$expeditie = intval($_POST['exp'] ?? 0);
        if($expeditie == 0) return 'Invalid ID';
		
		if($this->expeditor_id == 171350 || $this->selectie_puncte_de_lucru) {
			//maravet
			$cond = " ep.expeditie={$expeditie} AND ep.user_id in (select id from users where expeditor_id in (select cod_cl from clienti where master = {$this->master_id})) ";
		}
		else if($this->is_master) {
			$cond = " ep.expeditie={$expeditie} AND ep.user_id in (select id from users where expeditor_id = {$this->expeditor_id}) ";
		}
		else {
			$cond = " ep.expeditie={$expeditie} AND ep.user_id = {$this->user_id} ";
		}

		$vars=[];
		$vars['user_id'] = $this->print_awb;
		$vars['user_add'] = $this->print_add;

		if(false === ($sql = $this->GetValuesClient(0, 0, $cond))) return $this->Error('Expeditie Invalida!!!');

		//error_log("d: ". htmlspecialchars($sql['destinatar_nume'], ENT_QUOTES) . " :d");
		//destinatar
    	$vars['destinatar_id'] = $sql['destinatar_id'];
		$vars['destinatar_nume'] = htmlspecialchars($sql['destinatar_nume'], ENT_QUOTES);
		$vars['destinatar_localitate'] = htmlspecialchars($sql['destinatar_localitate'], ENT_QUOTES);
		$vars['destinatar_localitate_km'] = $sql['destinatar_localitate_km'];
		$vars['destinatar_localitate_id'] = $sql['destinatar_localitate_id'];
		$vars['destinatar_contact'] = htmlspecialchars($sql['destinatar_contact'], ENT_QUOTES);
		$vars['destinatar_telefon'] = htmlspecialchars($sql['destinatar_telefon'], ENT_QUOTES);
		$vars['destinatar_adresa'] = htmlspecialchars($sql['destinatar_adresa'], ENT_QUOTES);

		$vars['expeditor_id'] = $sql['expeditor_id'];
		$vars['expeditor_nume'] = htmlspecialchars($sql['expeditor_nume'], ENT_QUOTES);
		$vars['expeditor_localitate'] = htmlspecialchars($sql['expeditor_localitate'], ENT_QUOTES);
		$vars['expeditor_localitate_km'] = $sql['expeditor_localitate_km'];
		$vars['expeditor_localitate_id'] = $sql['expeditor_localitate_id'];
		$vars['expeditor_contact'] = htmlspecialchars($sql['expeditor_contact'], ENT_QUOTES);
		$vars['expeditor_telefon'] = htmlspecialchars($sql['expeditor_telefon'], ENT_QUOTES);
		$vars['expeditor_adresa'] = htmlspecialchars($sql['expeditor_adresa'], ENT_QUOTES);

		$vars['edit_expedition'] = 1;

		$vars['platitor_'.$sql['platitor']] = 'checked="checked"';
		$vars['TIP_OBJ_'.$sql['tip_obj']] = 'checked="checked"';
		$vars['TIP_OBJ_2'] = 0;
		if($sql['tip_obj'] == 2)
			$vars['TIP_OBJ_2'] = $sql['piese'];
		$vars['greutate'] = round(floatval(trim($sql['greutate'])), 2);

		if(!empty($sql['ret_nt'])) $vars['ret_nt'] ='checked="checked"';
		if(!empty($sql['ret_doc'])) $vars['ret_doc'] ='checked="checked"';
		if(!empty($sql['extrainfo'])) $vars['ret_nc'] ='checked="checked"';
		if(!empty($sql['liv_sambata'])) $vars['liv_sambata'] ='checked="checked"';
		if(!empty($sql['liv_sediu'])) $vars['liv_sediu'] ='checked="checked"';
		if(!empty($sql['ret_colet'])) $vars['ret_colet'] ='checked="checked"';
		if(!empty($sql['ret_amb'])) $vars['ret_amb'] ='checked="checked"';
		if(!empty($sql['sms'])) $vars['sms'] ='checked="checked"';
		if(!empty($sql['copen'])) $vars['copen'] ='checked="checked"';

		$vars['asigurare'] = $sql['asigurare'];
		$vars['ramburs'] = $sql['ramburs'];
		if($sql['tip_plata'] == 0) $vars['cash'] = 'selected';
		else if($sql['tip_plata']==1) $vars['bo'] = 'selected';
		else if($sql['tip_plata']==2) $vars['cec'] = 'selected';
		else if($sql['tip_plata']==3) $vars['cont'] = 'selected';
		$vars['valoare_exp'] = number_format($sql['valoare_exp'], 2, '.', '');
		$vars['valoare_asig'] = number_format($sql['valoare_asig'], 2, '.', '');
		$vars['valoare_km'] = number_format($sql['valoare_km'], 2, '.', '');
		$vars['valoare_g'] = number_format($sql['valoare_g'], 2, '.', '');
		$vars['valoare_totala'] = number_format($sql['valoare_totala'], 2, '.', '');
		$vars['valoare_tva'] = number_format($sql['valoare_tva'], 2, '.', '');
		$vars['detalii_doc'] = $sql['detalii_doc'];
		$vars['observatii'] = $sql['observatii'];
		$vars['expeditie'] = $sql['expeditie'];

		$vars['id'] = $sql['id'];

		//verificare plan tarifar paleti
		$vars['CLASS_TIP_OBJ_3'] = empty($this->tarif_palet) ? 'ascuns' : '';
		//retur ambalaj, nota comanda
		$vars['RET_NC_SHOW'] = $vars['RET_AMB_SHOW'] = 'ascuns';
		if($this->expeditor_id == 171350) //maravet
			$vars['RET_NC_SHOW'] = '';
		if(!empty($this->ret_amb))
			$vars['RET_AMB_SHOW'] = '';
		//cont colector
		$vars['CC'] = $this->expeditor_cc;

		if(!empty($sql['volum'])) {
			$volumes = explode("x", $sql['volum']);
			if(count($volumes) === 3) {
				list($vars['volum1'], $vars['volum2'], $vars['volum3']) = $volumes;
			}
		}
		
		//$swapped = !in_array($sql['expeditor_id'], array_keys($this->pcs));
		$swapped = intval($sql['swapped']) == 1;
		$vars['JSON_PCS'] = json_encode($this->pcs);
		$vars['SELECTED_ID'] = $swapped ? $sql['destinatar_id'] : $sql['expeditor_id'];
		$vars['MASTER_ID'] = $this->master_id;
		$vars['SWAPPED'] = intval($swapped);

		//afisare preturi
		$vars['afisare_preturi'] = intval($this->preturi == 2);

		if($this->print_awb == 2) $vars['print'] = "Print"; else $vars['print']="PDF";

    	return $this->Parse($this->page_prefix . 'expeditie_noua_detalii.html', array_map('htmlspecialchars', $vars));
    }

	function JSON_LocalitateJudete() {
		$limit = 20;
		$cond = '';
		$responce = new StdClass();
		$responce->total = 0;
		$responce->rezultat=[];
		$limit= intval($_POST['maxRows'] ?? $limit);
		$filter = strtoupper(Backend::sSanitizeCleanEdges(($_POST['term'] ?? '')));
		if($limit > 30) $limit = 30;
		if(empty($filter))
			return json_encode($responce);
		$cond= " AND lc.nume_lc LIKE :nume_lc";

		$query="SELECT lc.cod_lc, lc.nume_lc, lc.dist_km, lc.cod_jd, ce.nume as centru
				FROM localitati lc
				INNER JOIN centre ce on lc.cod_centru = ce.id
				WHERE 1=1 {$cond}
				ORDER BY lc.nume_lc LIMIT {$limit}";
		$sql = $this->db->QFetchRowArray($query, ['nume_lc' => $filter."%"]);
		if (!empty($sql)) {
			foreach ($sql as $key => $row) {
				$responce->total = count($sql);
				if($row['dist_km'] <= ExpeditieDto::KM_LIMIT) $km = 0;
				else $km = $row['dist_km'];

				$responce->rezultat[$key]['nume'] = strtoupper($row['nume_lc']).' ('.strtoupper($row['cod_jd']).') - '.$km.' KM '.' ('.strtoupper($row['centru']).')';
				$responce->rezultat[$key]['nume_lc'] = strtoupper($row['nume_lc']);
				$responce->rezultat[$key]['cod_lc'] = $row['cod_lc'];
				$responce->rezultat[$key]['km_lc'] = $row['dist_km'];
				$responce->rezultat[$key]['cod_jd'] = $row['cod_jd'];
			}

		}
		return json_encode($responce);
	}

	function JSON_Localitati() {
		$responce = new StdClass();
		$responce->total = 0;
		$responce->rezultat=[];
		$limit = 20;
		$limit= intval($_GET['maxRows'] ?? $limit);
		$filter = strtoupper(Backend::sSanitizeCleanEdges(($_GET['name_startsWith'] ?? '')));
		if($limit > 30) $limit = 30;
		if(empty($filter))
			return json_encode($responce);

		$cond= "AND nume_lc LIKE :name_startsWith";

		$query="SELECT cod_lc, nume_lc, dist_km, cod_jd
				FROM localitati
				WHERE 1=1 {$cond}
				ORDER BY nume_lc LIMIT {$limit}";
		$sql = $this->db->QFetchRowArray($query, ['name_startsWith' => $filter."%"]);
		if (!empty($sql)) {
			$responce->total = count($sql);
			foreach ($sql as $key => $row) {

				if($row['dist_km'] <= ExpeditieDto::KM_LIMIT) $km = 0;
				else $km = $row['dist_km'];

				$responce->rezultat[$key]['cod'] = $row['cod_lc'];
				$responce->rezultat[$key]['nume'] = strtoupper($row['nume_lc']).' (Jud. :'.strtoupper($row['cod_jd']).') - '.$km.' KM';
				$responce->rezultat[$key]['localitate'] = strtoupper($row['nume_lc']);
			}
		}

		return json_encode($responce);
	}

	function JSON_ClientiAllInfo(){
		$responce = new StdClass();
		$responce->total = 0;
		$responce->rezultat=[];
		$limit = 100;
		$limit= intval($_POST['maxRows'] ?? $limit);
		$filter = strtoupper(Backend::sSanitizeCleanEdges(($_POST['term'] ?? '')));
		if($limit > 100) $limit = 100;
		if(empty($filter))
			return json_encode($responce);

		$cond = " AND cld.nume LIKE :term";

		$master_id = $this->show_master_clienti && $this->is_pc ? $this->master_id : -1;
		$cond .= ' AND cld.id_exp in ('.$this->expeditor_id.','.$master_id.')';

		$query = "SELECT cld.nume, cld.id, cld.cod_cl, cld.adresa, cld.id_loc, lcd.nume_lc, lcd.cod_jd, lcd.dist_km,
					cld.contact, cld.telefon
					FROM client_destinatari as cld
					LEFT JOIN localitati lcd ON cld.id_loc = lcd.cod_lc
					WHERE 1=1 {$cond} and cld.activ = 1
					ORDER BY cld.nume ASC LIMIT {$limit}";
		//echo $query;die;
		$sql = $this->db->QFetchRowArray($query, ['term' => $filter.'%']);
		if (!empty($sql)) {
			$responce->total = count($sql);
			foreach ($sql as $key => $row) {
				/*
					label : item.label,
					value : item.nume_cl,
					cod_cl : item.cod_cl,
					nume_lc : item.nume_lc,
					cod_lc : item.cod_lc,
					km_lc : item.km_lc,
					cod_jd : item.cod_jd,
					adresa : item.adresa,
					contact : item.contact,
					telefon : item.telefon
				*/
				$responce->rezultat[$key]['label'] = strtoupper($row['nume']).' ('.strtoupper($row['nume_lc']) . ', '.strtoupper($row['adresa']) .')';
				$responce->rezultat[$key]['nume_cl'] = strtoupper($row['nume']);
				$responce->rezultat[$key]['cod_cl'] = $row['cod_cl'];
				$responce->rezultat[$key]['adresa'] = strtoupper($row['adresa']);
				$responce->rezultat[$key]['contact'] = strtoupper($row['contact']);
				$responce->rezultat[$key]['telefon'] = strtoupper($row['telefon']);
				$responce->rezultat[$key]['nume_lc'] = strtoupper($row['nume_lc']);
				$responce->rezultat[$key]['cod_lc'] = strtoupper($row['id_loc']);
				$responce->rezultat[$key]['km_lc'] = strtoupper($row['dist_km']);
				$responce->rezultat[$key]['cod_jd'] = strtoupper($row['cod_jd']);
			}
		}

		return json_encode($responce);
	}

	function JSON_Clienti(){
		$responce = new StdClass();
		$responce->total = 0;
		$responce->rezultat=[];
		$limit = 10;
		$limit= intval($_GET['maxRows'] ?? $limit);
		$filter = strtoupper(Backend::sSanitizeCleanEdges(($_GET['name_startsWith'] ?? '')));
		$localitate_id = strtoupper(Backend::sSanitizeCleanEdges(($_GET['localitate'] ?? 'all')));
		if($limit > 20) $limit = 20;
		if(empty($filter))
			return json_encode($responce);

		$cond= " AND cld.nume LIKE :name_startsWith";
		$cond1 = " AND 1=2";

		if($localitate_id == 'all')
			$cond1 = ' AND 1=1';
		else {
			$localitate_id = intval($localitate_id);
			$cond1 = ($localitate_id > 0) ? " AND cld.id_loc = {$localitate_id}" : " AND 1=2";
		}

		$master_id = $this->show_master_clienti && $this->is_pc ? $this->master_id : -1;
		$cond1 .= " AND cld.id_exp in (".$this->expeditor_id.",".$master_id.")";

		$query = "SELECT cld.nume, cld.id, cld.cod_cl, cld.adresa, cld.id_loc, lcd.nume_lc, lcd.cod_jd, cld.contact, cld.telefon
					FROM client_destinatari as cld
					LEFT JOIN localitati lcd ON cld.id_loc = lcd.cod_lc
					WHERE 1=1 {$cond} {$cond1}
					ORDER BY cld.nume ASC LIMIT {$limit}";
		//echo $query;die;
		$sql = $this->db->QFetchRowArray($query, ['name_startsWith' => $filter."%"]);
		if (!empty($sql)) {
			$responce->total = count($sql);
			foreach ($sql as $key => $row) {
				$responce->rezultat[$key]['label'] = strtoupper($row['nume']).' (Loc.: '.strtoupper($row['nume_lc']).')';
				$responce->rezultat[$key]['value'] = strtoupper($row['nume']);
				$responce->rezultat[$key]['destinatar_id'] = $row['id'];
				$responce->rezultat[$key]['adresa'] = strtoupper($row['adresa']);
				$responce->rezultat[$key]['contact'] = strtoupper($row['contact']);
				$responce->rezultat[$key]['telefon'] = strtoupper($row['telefon']);
				$responce->rezultat[$key]['localitate'] = strtoupper($row['nume_lc']);
				$responce->rezultat[$key]['localitate_id'] = strtoupper($row['id_loc']);
			}
		}

		return json_encode($responce);
	}

	function ValoareExpeditie() {
		if($this->preturi == 2) return '1|||NaN|||NaN|||NaN|||NaN|||LEI';

		if(empty($_POST) || empty($_POST['EXPEDITOR_LOCALITATE_ID']) || empty($_POST['DESTINATAR_LOCALITATE_ID'])) return '1|||NaN|||NaN|||NaN|||NaN|||LEI';

		$post = ExpeditieDto::uiExpClientToSql($_POST, $this->expeditor_cc);
		if($post['tip_obj'] == 'COLET' && $post['greutate'] < Backend::MIN_KG_COLET) $post['greutate'] = Backend::MIN_KG_COLET;
		if($post['tip_obj'] == 'PALET' && $post['greutate'] < Backend::MIN_KG_PALET) $post['greutate'] = Backend::MIN_KG_PALET;
		//error_log(intval($post['swapped']));
		$valoare = $this->Get_ValoareExpeditieClient($post);

		return '1|||'.number_format(round($valoare['tExpeditie'], 2), 2, '.', '').'|||'.number_format(round($valoare['tKm'], 2), 2, '.', '').'|||'
			.number_format(round($valoare['tGreutate'],2),2, '.', '').'|||'.number_format(round($valoare['tAsigurare'],2), 2, '.', '').'|||'.$valoare['moneda'];
	}

	function AdaugareExpeditie($id=0){
		$expeditie = 0;
		$post = [];
		$id = intval($id);
		$master_id = $this->show_master_clienti && $this->is_pc ? $this->master_id : -1;

		if($id > 0)
		{
			$query="select expeditie, printed from client_expeditii where id={$id}";
    		$sql = $this->db->QFetchRowAssoc($query);
			if(!empty($sql)) {
				$expeditie = $sql['expeditie'];
				if($sql['printed'] == 1 && !$this->editAfterPrinted)
					return '0|||Expeditia printata / Editare imposibila!';
			}
		}

		if(empty($_POST['data']) || !is_array($_POST['data']) || count($_POST['data']) == 0){
			return '0|||Error : Empty data!';
		}
		foreach($_POST['data'] as $key => $field){
			if(is_array($field) && isset($field['name']) && isset($field['value']))
				$post[$field['name']] = $this->sanitize($field['value']);
		}
		if(count($post) == 0){
			error_log("empty post data");
			return '0|||Error : Empty data!';
		}
		
		$vi = ExpeditieDto::uiExpClientToSql($post, $this->expeditor_cc);
		$swapped = $vi['swapped'] == 1;

		//validare
		if(!$swapped){
			if(empty($vi['destinatar_nume']))
				return '0|||Introduceti destinatarul!';
			if(empty($vi['destinatar_localitate_id']))
				return '0|||Selectati localitatea din lista!';
			if(strlen($vi['destinatar_adresa'] ?? "") < 4) 
				return "0|||Adresa destinatarului este obligatorie";
			if(empty($vi['expeditor_id'])){
				$this->log(intval($swapped) . " : 1 : {$vi['expeditor_id']} : ", parent::APP_LOG_FILE);
				$this->log(print_r($post, true), parent::APP_LOG_FILE);
				$this->log(print_r($vi, true), parent::APP_LOG_FILE);
				$this->log(print_r(array_keys($this->pcs), true), parent::APP_LOG_FILE);
				$vi['expeditor_id'] = $vi['selected_id'] > 0 ? $vi['selected_id'] : $this->expeditor_id;
				$vi['expeditor_contact'] = $this->expeditor_contact;
				$vi['expeditor_telefon'] = $this->expeditor_telefon;
			}
		}
		else {
			if(empty($vi['expeditor_nume']))
				return '0|||Introduceti expeditorul!';
			if(empty($vi['expeditor_localitate_id']))
				return '0|||Selectati localitatea din lista!';
			if(strlen($vi['expeditor_adresa'] ?? "") < 4)
				return "0|||Adresa expeditorului este obligatorie";
			if(empty($vi['destinatar_id'])){
				$this->log(intval($swapped) . " : 2 : {$vi['destinatar_id']} : ", parent::APP_LOG_FILE);
				$this->log(print_r($post, true), parent::APP_LOG_FILE);
				$this->log(print_r($vi, true), parent::APP_LOG_FILE);
				$this->log(print_r(array_keys($this->pcs), true), parent::APP_LOG_FILE);
				$vi['destinatar_id'] = $vi['selected_id'] > 0 ? $vi['selected_id'] : $this->expeditor_id;
				$vi['destinatar_contact'] = $this->expeditor_contact;
				$vi['destinatar_telefon'] = $this->expeditor_telefon;
			}
		}
		if(empty($vi['platitor']))
			return '0|||Selectati PLATITORUL!';

		if($vi['sms'] == -1 && !ExpeditieDto::isValidTelefonNumber(($swapped ? $vi['expeditor_telefon'] : $vi['destinatar_telefon'])))
			return "0|||Eroare adaugare/editare expeditie : corectati telefon " . ($swapped ? "expeditor" : "destinatar") . " : 07XXXXXXXX";
		if($vi['tip_obj'] == 2 && $vi['greutate'] < parent::MIN_KG_COLET)
			return "0|||Eroare adaugare/editare expeditie tip colet : greutate minima : " . parent::MIN_KG_COLET . " kg";
		if($vi['tip_obj'] == 2 && $vi['piese'] < parent::MIN_COLETE)
			return "0|||Eroare adaugare/editare expeditie tip colet : nr. colete minim : ".parent::MIN_COLETE;
		if($vi['tip_obj'] == 2 && $vi['piese'] > parent::MAX_COLETE)
			return "0|||Eroare adaugare/editare expeditie tip colet : nr. colete maxim : ".parent::MAX_COLETE;
		if($vi['tip_obj'] == 3 && $vi['greutate'] < parent::MIN_KG_PALET)
			return "0|||Eroare adaugare/editare expeditie tip palet : greutate minima : " . parent::MIN_KG_PALET . " kg";
		if($vi['asigurare'] > parent::MAX_ASIGURARE)
			return "0|||Eroare adaugare/editare expeditie : asigurare maxima : " . parent::MAX_ASIGURARE . " LEI";

		$valoare = $this->Get_ValoareExpeditieClient($vi);

		if($expeditie == 0)
			$expeditie = $this->GenerareNrExpeditie();

		$vi['expeditie'] = $expeditie;
		$vi['data_expeditie'] = date('Y-m-d');
		$vi['borderou_id'] = 0;
		$vi['moneda'] = ExpeditieDto::MONEDA_REV[$valoare['moneda']] ?? 1;

		//mod_plata = 0 if expeditie inversa cu platitor_id = expeditor_id
		$vi['mod_plata'] = $valoare['mod_plata'];
		$vi['valoare_exp'] =$valoare['tExpeditie'];
		$vi['valoare_km'] = $valoare['tKm'];
		$vi['valoare_g'] = $valoare['tGreutate'];
		$vi['valoare_asig'] = $valoare['tAsigurare'];

		$vi['valoare_totala'] = $vi['valoare_exp'] + $vi['valoare_km'] + $vi['valoare_g'] + $vi['valoare_asig'];
		$vi['valoare_totala'] = round($vi['valoare_totala'],2);
		$vi['valoare_tva'] = $vi['valoare_totala'] * $this->procTva / 100;
		$vi['valoare_tva'] = round($vi['valoare_tva'],2);
		$vi['procTva'] = $this->procTva;

		$client_client = $swapped ? "expeditor" : "destinatar";

		if($vi["{$client_client}_id"] > 0){
			//test eroare nume <> nume in tabela clienti
			$qc = "SELECT cod_cl FROM clienti
					WHERE cod_cl = " . $vi["{$client_client}_id"] . " and nume like :nume and adresa like :adresa
					and cod_lc = " . $vi["{$client_client}_localitate_id"] . " and activ = 1 and sters = 0 and mod_plata = 0 and tarif = 0 and (master = 0 or master = cod_cl)";
			$sc = $this->db->QFetchRowAssoc($qc, ['nume' => $vi["{$client_client}_nume"], 'adresa' => $vi["{$client_client}_adresa"]]);
			if(empty($sc)) {
				$vi["{$client_client}_id"] = 0;
			}
		}

		//cauta nume in tabla clienti
		if(empty($vi["{$client_client}_id"])) {
			$query = "SELECT cod_cl FROM clienti WHERE nume like :nume
			and adresa like :adresa and cod_lc = ".$vi["{$client_client}_localitate_id"]." limit 1";
			//error_log($query);
	    	$sql = $this->db->QFetchRowAssoc($query, ['nume' => $vi["{$client_client}_nume"], 'adresa' => $vi["{$client_client}_adresa"]]);
	    	if (empty($sql)) {//in cazul in care nu exista clientul => insert
				//detalii
				$var = [];
		    	$var['nume'] = $vi["{$client_client}_nume"];
		    	$var['cod_lc'] = $vi["{$client_client}_localitate_id"];   
		    	$var['adresa'] = $vi["{$client_client}_adresa"]; 
		    	$var['operator'] = $this->user_id;
		    	$var['activ'] = 1;
		    	$var['tarif'] = 0;
		    	$var['mod_plata'] = 0;
		        $var['persoana_fizica'] = 1;
       			$var['master'] = 0;
				$var['contact'] = $vi["{$client_client}_contact"];
				$var['telefon'] = $vi["{$client_client}_telefon"];
		    	$var['km_ext'] = $swapped ? $vi["km_ext_prel"] : $vi["km_ext_livr"]; 

				$var['created_at'] = date('Y-m-d H:i:s');
				$var['created_by'] = $this->user_id;
				$vi["{$client_client}_id"] = $this->db->QueryInsert($this->tables['clienti'], $var);
				if($vi["{$client_client}_id"] === false){
					error_log("{$client_client} insert error : ");
					return '0|||Eroare necunoscuta la adaugare/editare expeditie';
				}
				CdsGeocoder::geocode($this->db, $vi["{$client_client}_id"]);
		    	unset($var);
			}
			else {
				$vi["{$client_client}_id"] = $sql['cod_cl'];
				CdsGeocoder::geocode($this->db, $vi["{$client_client}_id"], true);
	    
			}
		}
		else {
			CdsGeocoder::geocode($this->db, $vi["{$client_client}_id"], true);
		}
		
	    $cl_dest_id=0;
	    //cauta nume in tabla client_destinatari		 
	    $qc = "SELECT id, nume, cod_cl, activ FROM {$this->tables['client_destinatari']} 
	    		WHERE nume like :nume and adresa like :adresa
	    		and id_loc = ".$vi["{$client_client}_localitate_id"]." and id_exp in (".$this->expeditor_id.",".$master_id.") limit 1";
	    $sc = $this->db->QFetchRowAssoc($qc, ['nume' => $vi["{$client_client}_nume"], 'adresa' => $vi["{$client_client}_adresa"]]);
	    if(empty($sc)) //in cazul in care nu exista linkul client:destinatar => insert
		{				
			$var = [];
			$var['id_exp'] = $this->expeditor_id;
			$var['nume'] = $vi["{$client_client}_nume"];
			$var['id_loc'] = $vi["{$client_client}_localitate_id"];
			$var['km_ext'] = $swapped ? $vi["km_ext_prel"] : $vi["km_ext_livr"]; 
			$var['adresa'] = $vi["{$client_client}_adresa"];
			$var['activ'] = 1;
			$var['cod_cl'] = $vi["{$client_client}_id"];
			$var['observatii'] = '';
			$var['contact'] = $vi["{$client_client}_contact"];
			$var['telefon'] = $vi["{$client_client}_telefon"];
			
			$var['created_at'] = date('Y-m-d H:i:s');
			$var['created_by'] = $this->user_id;
			$cl_dest_id = $this->db->QueryInsert($this->tables['client_destinatari'], $var);
			
		}
		else
		{
			$cl_dest_id = $sc['id'];
			$this->db->QueryUpdate($this->tables['client_destinatari'], ['contact' => $vi["{$client_client}_contact"], 'telefon' => $vi["{$client_client}_telefon"]], "id=".$cl_dest_id);
			$upd = false;
			$var = [];
			if(empty($sc['cod_cl'])) { $var['cod_cl'] = $vi["{$client_client}_id"]; $upd = true; }
			if(!empty($sc['cod_cl']) && !empty($vi["{$client_client}_id"]) && $vi["{$client_client}_id"] != $sc['cod_cl']) { $var['cod_cl'] = $vi["{$client_client}_id"]; $upd = true; }
			if(empty($sc['activ'])) { $var['activ'] = 1;  $upd = true; }
			$var['updated_at'] = date('Y-m-d H:i:s');
			$var['updated_by'] = $this->user_id;
			if($upd)
				$this->db->QueryUpdate($this->tables['client_destinatari'], $var, "id = {$cl_dest_id}");
		}
		
		//baza de date de cacat
		$vi['expeditor'] = $vi['expeditor_id'];
		$vi['destinatar_cod_cl'] = $vi['destinatar_id'];
		if(!$swapped) {
			$vi['destinatar_id'] = $cl_dest_id;
		}

		unset($vi['id']);

		$vi['user_id'] = $this->user_id;
		if(empty($id)) {
			$vi['created_at'] = date('Y-m-d H:i:s');
			$id = $this->db->QueryInsert($this->tables['client_expeditii'], $vi);
			return '1|||'.$vi['expeditie'];
		}
		$vi['updated_by'] = $this->user_id;
		$vi['updated_at'] = date('Y-m-d H:i:s');
		$this->db->QueryUpdate($this->tables['client_expeditii'], $vi, "id=".$id);

		return 1;

	}

	function StergeExpeditie($id){
		$id = intval($id);
		if(!empty($id))
		{
			$this->db->Query("UPDATE client_expeditii set anulata = 1, deleted_by = {$this->user_id}, deleted_at = now() WHERE id = {$id}");
			return '1|||Deleted';
		}
		return '0|||Expeditie inexistenta';
	}

	function JSON_Expeditii() {
		$responce = new StdClass();

		if($this->expeditor_id == 171350 || $this->selectie_puncte_de_lucru) {
			//maravet
			$cond = " e.borderou_id = 0 AND e.user_id in (select id from users where expeditor_id in (select cod_cl from clienti where master = {$this->master_id}))";
		}
		else if($this->is_master) {
			$cond = " e.borderou_id = 0 AND e.user_id in (select id from users where expeditor_id = {$this->expeditor_id})";
		}
		else {
			$cond = " e.borderou_id = 0 AND e.user_id = {$this->user_id}";
		}

		//start generare conditie
        $searchOn = $this->Strip($_REQUEST['_search']);
        if ($searchOn == 'true') {
            $searchstr = $this->Strip($_REQUEST['filters']);
            $cond .= $this->constructWhere($searchstr);
        }

		$page = intval($_REQUEST['page'] ?? 1);
		$limit = intval($_REQUEST['rows'] ?? 20);
		$sidx = trim($this->sanitize($_REQUEST['sidx'] ?? 1));
		$sord = trim($this->sanitize($_REQUEST['sord'] ?? 'asc'));

		$query = "SELECT COUNT(e.id) as nr
			FROM client_expeditii e
			left join clienti cle on e.expeditor = cle.cod_cl
			left join localitati lce on lce.cod_lc = cle.cod_lc
			left join clienti cld on e.destinatar_cod_cl = cld.cod_cl
			left join localitati lcd on lcd.cod_lc = cld.cod_lc
			WHERE {$cond} and e.anulata=0";
			//echo $query;die;
        $result = $this->db->QFetchArray($query);
        $count = !empty($result['nr']) ? $result['nr'] : 0;

        if( $count >0 ) {$total_pages = ceil($count/$limit); }
        else { $total_pages = 0; }
        if ($page > $total_pages) $page=$total_pages;
        if ($limit<0) $limit = 0;
        $start = $limit*$page - $limit; // do not put $limit*($page - 1)
        if ($start<0) $start = 0;
        $query = "SELECT e.expeditie, e.data_expeditie, e.tip_obj, e.piese,
			e.greutate, e.ramburs, e.valoare_totala, e.valoare_tva, (e.km_ext_prel+e.km_ext_livr) as km, e.updated_by,
			cle.nume as expeditor_nume, lce.nume_lc as expeditor_localitate, cld.nume as destinatar_nume, lcd.nume_lc as destinatar_localitate
			FROM client_expeditii as e
			left join clienti cle on e.expeditor = cle.cod_cl
			left join localitati lce on lce.cod_lc = cle.cod_lc
			left join clienti cld on e.destinatar_cod_cl = cld.cod_cl
			left join localitati lcd on lcd.cod_lc = cld.cod_lc
            WHERE {$cond} and e.anulata = 0
            ORDER BY " . $sidx . " " . $sord . " LIMIT " . $start . " , " . $limit . ";";
        //error_log($query);
        $sql = $this->db->QFetchRowArray($query);
		$total_greutate = 0;
		$total_piese = 0;

        if (!empty($sql)) {
            foreach ($sql as $key => $row) {
				$responce->rows[$key]['id'] = $row['expeditie'];
				$row['piese'] = round($row['piese']);
				$total_greutate += $row['greutate'];
				$total_piese += $row['piese'];

				//nu afisez preturile
				if($this->preturi == 2){
					$row['valoare_totala'] = 'NA';
					$row['valoare_tva'] = 'NA';
				}
				$print="Printeaza";
				if($this->print_awb != 2) $print="PDF";
				$row['optiune'] = '<a href="javascript:;" onclick="ClientPrintExpeditie('.$row['expeditie'].','.$this->print_awb.');" style="text-decoration:none;">'.$print.'</a>';
                $responce->rows[$key]['cell'] = array($row['expeditie'], strtoupper($row['expeditor_nume']),strtoupper($row['expeditor_localitate']), strtoupper($row['destinatar_nume']),strtoupper($row['destinatar_localitate']),$row['tip_obj'],$row['piese'],$row['greutate'],$row['ramburs'],$row['km'],$row['valoare_totala'],$row['valoare_tva'],$row['data_expeditie'],$row['optiune'],$row['updated_by']);
            }
        }
		$responce->page = $page;
        $responce->total = $total_pages;
        $responce->records = $count;

        $responce->userdata['expeditie'] = 'Total:';
		$responce->userdata['destinatar'] = $count.' Expeditii';
		$responce->userdata['destinatar_localitate'] = $total_piese.' Colete - '.$total_greutate.'kg';

        return json_encode($responce);
    }


	function ListeExpeditii() {
        $this->vars['title_page'] = 'Liste Expeditii';
        $vars = [];
        $vars['data_start'] = date('d.m.Y');
        $vars['data_final'] = date('d.m.Y',strtotime("-1 month"));
        return $this->Parse($this->page_prefix . 'liste_expeditii.html', array_map('htmlspecialchars', $vars));
    }

	function JSON_ListeExpeditii() {
		$responce = new StdClass();

		if($this->expeditor_id == 171350 || $this->selectie_puncte_de_lucru) {
			//maravet
			$cond = " cep.borderou_id > 0 AND cep.user_id in (select id from users where expeditor_id in (select cod_cl from clienti where master = {$this->master_id}))";
		}
		else if($this->is_master) {
			$cond = " cep.borderou_id > 0 AND cep.user_id in (select id from users where expeditor_id = {$this->expeditor_id})";
		}
		else {
			$cond = " cep.borderou_id > 0 AND cep.user_id = {$this->user_id}";
		}

        $req = false;
		if(isset($_REQUEST['search']) && $_REQUEST['search'] != '' ){
            $cond .= " AND cep.expeditie = ".intval($_REQUEST['search']);
			$req = true;
        }
        if(isset($_REQUEST['destinatar_id'])){
            $cond .= " AND cep.destinatar_id = ".intval($_REQUEST['destinatar_id']);
			$req = true;
        }
		if(isset($_REQUEST['destinatar_localitate_id'])){
            $cond .= " AND cep.destinatar_localitate_id = ".intval($_REQUEST['destinatar_localitate_id']);
			$req = true;
        }
       if(isset($_REQUEST['platitor'])){
            $cond .= " AND cep.platitor = ".intval($_REQUEST['platitor']);
			$req = true;
        }

        if(isset($_REQUEST['data_start']) && isset($_REQUEST['data_final'])){
            $data_start = $this->TransformDate($_REQUEST['data_start']);
            $data_final = $this->TransformDate($_REQUEST['data_final']);
			$cond .= " AND cep.data_expeditie between '{$data_start}' AND '{$data_final}'";
        }
        else if(!$req) {
			$data_final = date("Y-m-d");
			$data_start = date('Y-m-d',mktime(0, 0, 0, date("m")-1, date("d"),   date("Y")));
			$cond .= " AND cep.data_expeditie between '{$data_start}' AND '{$data_final}'";
		}

		//error_log($cond);

		//start generare conditie
        $searchOn = $this->Strip($_REQUEST['_search']);
        if ($searchOn == 'true') {
            $searchstr = $this->Strip($_REQUEST['filters']);
            $cond .= $this->constructWhere($searchstr);
        }

        $page = intval($_REQUEST['page'] ?? 1);
		$limit = intval($_REQUEST['rows'] ?? 20);
		$sidx = trim($this->sanitize($_REQUEST['sidx'] ?? 1));
		$sord = trim($this->sanitize($_REQUEST['sord'] ?? 'asc'));

		$query = "select count(*) as nr
			FROM (
				SELECT cep.expeditie as nr
				FROM client_expeditii as cep
				INNER JOIN client_borderouri as b ON cep.borderou_id=b.id
				INNER JOIN exp_prelucrate as ep ON (ep.expeditie=cep.expeditie and ep.anulata = 0)
				LEFT JOIN clienti cle on cle.cod_cl = cep.expeditor
				LEFT JOIN localitati lce ON lce.cod_lc = cle.cod_lc
				LEFT JOIN clienti cld on cep.destinatar_cod_cl = cld.cod_cl
				LEFT JOIN localitati lcd on lcd.cod_lc = cld.cod_lc
				LEFT JOIN exp_confirmari ec on cep.expeditie = ec.expeditie
				LEFT JOIN (
					select i.expeditie, i.data as sc_data, ces.nume as sc_centru, cks.denumire as sc_ckp
					from scanari_coduri i
					LEFT JOIN centre as ces ON i.centru = ces.id
					LEFT JOIN checkpoints as cks ON cks.id = i.tip
					where i.data in
						(select max(j.data) from scanari_coduri j
						where j.expeditie = i.expeditie and j.is_awb = 1 and j.tip in (select id from checkpoints where activ = 1 and is_public = 1 ))
				) as sc on sc.expeditie = ep.expeditie
				WHERE {$cond}
				GROUP BY cep.expeditie
			) AS subquery
		";
		//echo $query;die;

        $result = $this->db->QFetchArray($query);
        $count = !empty($result['nr']) ? $result['nr'] : 0;

        if( $count >0 ) {$total_pages = ceil($count/$limit); }
        else { $total_pages = 0; }
        if ($page > $total_pages) $page=$total_pages;
        if ($limit<0) $limit = 0;
        $start = $limit*$page - $limit; // do not put $limit*($page - 1)
        if ($start<0) $start = 0;

        $query = "SELECT cep.expeditie, cep.data_expeditie, cep.tip_obj, cep.piese,
			cep.greutate, cep.ramburs, cep.valoare_totala, cep.valoare_tva, (cep.km_ext_prel+cep.km_ext_livr) as km,
			cle.nume as expeditor_nume, lce.nume_lc as expeditor_localitate,
			cld.nume as destinatar_nume, lcd.nume_lc as destinatar_localitate,
			b.borderou_id as borderou ,ep.operatiune, ep.primitor, ep.data_op, ec.folder,
			sc.sc_data, sc.sc_centru, sc.sc_ckp
            FROM client_expeditii as cep
            INNER JOIN client_borderouri as b ON cep.borderou_id = b.id
            INNER JOIN exp_prelucrate as ep ON (cep.expeditie=ep.expeditie and ep.anulata = 0)
			LEFT JOIN clienti cle on cle.cod_cl = cep.expeditor
			LEFT JOIN localitati lce ON lce.cod_lc = cle.cod_lc
			LEFT JOIN clienti cld on cep.destinatar_cod_cl = cld.cod_cl
			LEFT JOIN localitati lcd on lcd.cod_lc = cld.cod_lc
	    	LEFT JOIN exp_confirmari ec on cep.expeditie = ec.expeditie
			LEFT JOIN (
				select i.expeditie, i.data as sc_data, ces.nume as sc_centru, cks.denumire as sc_ckp
				from scanari_coduri i
				LEFT JOIN centre as ces ON i.centru = ces.id
				LEFT JOIN checkpoints as cks ON cks.id = i.tip
				where i.data in
					(select max(j.data) from scanari_coduri j 
					where j.expeditie = i.expeditie and j.is_awb = 1 and j.tip in (select id from checkpoints where activ = 1 and is_public = 1 ))
			) as sc on sc.expeditie = ep.expeditie
            WHERE {$cond}
			GROUP BY cep.expeditie
            ORDER BY " . $sidx . " " . $sord . " LIMIT " . $start . " , " . $limit;
            //error_log($cond);

        $sql = $this->db->QFetchRowArray($query);
        if (!empty($sql)) {
            foreach ($sql as $key => $row) {
				$responce->rows[$key]['id'] = $row['expeditie'];
				if(!empty($row['primitor']) && !empty($row['folder'])) $row['primitor'] = '<a href="javascript:;" onclick="clDownloadConfirmare('.intval($row['expeditie']).');">'.$row['primitor'].'</a>';
                $responce->rows[$key]['cell'] = array($row['expeditie'], strtoupper($row['expeditor_nume']),strtoupper($row['expeditor_localitate']),strtoupper($row['destinatar_nume']),strtoupper($row['destinatar_localitate']),$row['tip_obj'],$row['piese'],$row['greutate'],$row['ramburs'],$row['borderou'],$row['data_expeditie'],$row['operatiune'],$row['data_op'],$row['primitor'],$row['sc_ckp'],$row['sc_centru']);
            }
        }
		$responce->page = $page;
        $responce->total = $total_pages;
        $responce->records = $count;
        return json_encode($responce);
	}

	function ListeRetururi() {
        $this->vars['title_page'] = 'Liste Retururi';
        $vars = [];
        $vars['data_start'] = date('d.m.Y');
        $vars['data_final'] = date('d.m.Y',strtotime("-1 month"));
        return $this->Parse($this->page_prefix . 'liste_retururi.html', array_map('htmlspecialchars', $vars));
    }

	function JSON_ListeRetururi() {
		$responce = new StdClass();

		if($this->expeditor_id == 171350 || $this->selectie_puncte_de_lucru) {
			//maravet
			$cond = " cep.user_id in (select id from users where expeditor_id in (select cod_cl from clienti where master = {$this->master_id}))";
		}
		else if($this->is_master) {
			$cond = " cep.user_id in (select id from users where expeditor_id = {$this->expeditor_id})";
		}
		else {
			$cond = " cep.user_id = {$this->user_id}";
		}

        $req = false;
		if(isset($_GET['awb']) && ExpeditieDto::isAppAwb($_GET['awb'])){
            $cond .= " AND epr.expeditie = ".intval($_GET['awb']);
			$req = true;
        }

		if($req === false) {
			if(isset($_GET['data_start']) && isset($_GET['data_final'])){
				try {
					$data_start = DateTime::createFromFormat('d.m.Y', $_GET['data_start']);
					$data_final = DateTime::createFromFormat('d.m.Y', $_GET['data_final']);
					if($data_start && $data_final) {
						if(intval($data_start->diff($data_final, true)->format('%a')) > 31){
							$data_start = $data_final;
							$data_final = $data_final->format('Y-m-d');
							$data_start = $data_start->sub(new DateInterval('P1M'));
							$data_start = $data_start->format('Y-m-d');
						}
						else {
							$data_start = $data_start->format('Y-m-d');
	    					$data_final = $data_final->format('Y-m-d');
						}

					}
					else {
						$data_final = date("Y-m-d");
						$data_start = date('Y-m-d', strtotime("-1 months"));
					}
				}
				catch (Exception $e){
					$data_final = date("Y-m-d");
					$data_start = date('Y-m-d', strtotime("-1 months"));
				}

				$cond .= " AND epr.data_expeditie between '{$data_start}' AND '{$data_final}'";
			}
			else {
				$data_final = date("Y-m-d");
				$data_start = date('Y-m-d', strtotime("-1 months"));
				$cond .= " AND epr.data_expeditie between '{$data_start}' AND '{$data_final}'";
			}
			//start generare conditie
			$searchOn = $this->Strip($_GET['_search'] ?? 'false');
			if ($searchOn == 'true') {
				$searchstr = $this->Strip($_GET['filters']);
				$cond .= $this->constructWhere($searchstr);
			}
		}

        $page = intval($_GET['page'] ?? 1);
		$limit = intval($_GET['rows'] ?? 20);
		$sidx = trim($this->sanitize($_GET['sidx'] ?? 2));
		$sord = trim($this->sanitize($_GET['sord'] ?? 'desc'));

		$query = "select count(*) as nr
			FROM (
				SELECT epr.expeditie
				FROM exp_prelucrate epr
				INNER JOIN exp_prelucrate ep on ep.expeditie = epr.referire and ep.anulata = 0
				INNER JOIN client_expeditii as cep ON cep.expeditie = ep.expeditie
				left join clienti cle on cle.cod_cl = epr.expeditor_id
				left join clienti cld on cld.cod_cl = epr.destinatar_id
				left join localitati lce ON lce.cod_lc = cle.cod_lc
				left join localitati lcd ON lcd.cod_lc = cld.cod_lc
				WHERE {$cond} and epr.tip_exp NOT IN (0,3) and epr.anulata = 0
				GROUP BY epr.expeditie
			) AS subquery
		";
		//echo $query;die;

        $result = $this->db->QFetchArray($query);
        $count = !empty($result['nr']) ? $result['nr'] : 0;

        if( $count >0 ) {$total_pages = ceil($count/$limit); }
        else { $total_pages = 0; }
        if ($page > $total_pages) $page=$total_pages;
        if ($limit<0) $limit = 0;
        $start = $limit*$page - $limit; // do not put $limit*($page - 1)
        if ($start<0) $start = 0;

        $query = "SELECT epr.tip_exp, epr.expeditie, epr.data_expeditie, epr.referire,
			cle.nume as expeditor, cld.nume as destinatar, 
			lce.nume_lc as expeditor_localitate,lcd.nume_lc as destinatar_localitate,
			epr.tip_obj, epr.piese, epr.greutate,
			epr.operatiune, epr.primitor, epr.data_op, ec.folder,
			sc.sc_data, sc.sc_centru, sc.sc_ckp
            FROM exp_prelucrate epr
			INNER JOIN exp_prelucrate ep on ep.expeditie = epr.referire and epr.anulata = 0
			INNER JOIN client_expeditii as cep ON cep.expeditie = ep.expeditie
			left join clienti cle on cle.cod_cl = epr.expeditor_id
			left join clienti cld on cld.cod_cl = epr.destinatar_id
			left join localitati lce ON lce.cod_lc = cle.cod_lc
			left join localitati lcd ON lcd.cod_lc = cld.cod_lc
			LEFT JOIN exp_confirmari ec on epr.expeditie = ec.expeditie
			LEFT JOIN (
				select i.expeditie, i.data as sc_data, ces.nume as sc_centru, cks.denumire as sc_ckp
				from scanari_coduri i
				LEFT JOIN centre as ces ON i.centru = ces.id
				LEFT JOIN checkpoints as cks ON cks.id = i.tip
				where i.data in
					(select max(j.data) from scanari_coduri j
					where j.expeditie = i.expeditie and j.is_awb = 1 and j.tip in (select id from checkpoints where activ = 1 and is_public = 1 ))
			) as sc on sc.expeditie = epr.expeditie
			WHERE {$cond} and epr.tip_exp NOT IN (0,3) and epr.anulata = 0
			GROUP BY epr.expeditie
            ORDER BY " . $sidx . " " . $sord . " LIMIT " . $start . " , " . $limit;
            //error_log($cond);

        $sql = $this->db->QFetchRowArray($query);
        if (!empty($sql)) {
            foreach ($sql as $key => $row) {
				$responce->rows[$key]['id'] = $row['expeditie'];
				if(!empty($row['primitor']) && !empty($row['folder'])) $row['primitor'] = '<a href="javascript:;" onclick="clDownloadConfirmare('.intval($row['expeditie']).');">'.$row['primitor'].'</a>';
				
                $responce->rows[$key]['cell'] = array($row['tip_exp'], $row['expeditie'], $row['referire'],
					strtoupper($row['expeditor']),strtoupper($row['expeditor_localitate']),
					strtoupper($row['destinatar']),strtoupper($row['destinatar_localitate']),
					$row['tip_obj'],$row['piese'],$row['greutate'],$row['data_expeditie'],
					$row['operatiune'],$row['data_op'],$row['primitor'],$row['sc_ckp'],$row['sc_centru']);
            }
        }
		$responce->page = $page;
        $responce->total = $total_pages;
        $responce->records = $count;
        return json_encode($responce);
	}

	function Recantarite(){
		$this->vars['title_page'] = 'Cautare expeditii recantarite';
        $vars = [];
        $vars['data_final'] = date('d.m.Y');
        $vars['data_start'] = date('d.m.Y',strtotime("-1 month"));

        return $this->Parse($this->page_prefix . 'recantarite.html', array_map('htmlspecialchars', $vars));
	}

	function JSON_Recantarite() {
        $cond ='';
		$responce = new StdClass();

		if($this->selectie_puncte_de_lucru)
			$cond =" e.borderou_id > 0 AND e.expeditor in (select cod_cl from clienti where master = {$this->master_id})";
		else
			$cond =" e.borderou_id > 0 AND e.expeditor = {$this->expeditor_id}";

        if(isset($_REQUEST['data_start']) && isset($_REQUEST['data_final'])){
            $data_start = $this->TransformDate($_REQUEST['data_start']);
            $data_final = $this->TransformDate($_REQUEST['data_final']);
            $cond .= " AND e.data_expeditie between '{$data_start}' AND '{$data_final}'";
        }
		//start generare conditie
        $searchOn = $this->Strip($_REQUEST['_search']);
        if ($searchOn == 'true') {
            $searchstr = $this->Strip($_REQUEST['filters']);
            $cond .= $this->constructWhere($searchstr);
        }

		$page = intval($_REQUEST['page'] ?? 1);
		$limit = intval($_REQUEST['rows'] ?? 20);
		$sidx = trim($this->sanitize($_REQUEST['sidx'] ?? 1));
		$sord = trim($this->sanitize($_REQUEST['sord'] ?? 'asc'));

		$query = "select count(e.expeditie) as nr
		from client_expeditii as e
		JOIN exp_prelucrate ep on (ep.expeditie = e.expeditie and ep.anulata = 0)
		join ist_exp ie on ep.cod_expeditie = ie.cod_exp
		join ist_exp_value_double ievd on (ie.cod_ist = ievd.cod_ist and ievd.attribute='greutate')
		left join clienti cle on ep.expeditor_id = cle.cod_cl
		left join clienti cld on ep.destinatar_id = cld.cod_cl
		where {$cond} ";
		//error_log($query);
        $result = $this->db->QFetchArray($query);
        $count = !empty($result['nr']) ? $result['nr'] : 0;

        if( $count >0 ) {$total_pages = ceil($count/$limit); }
        else { $total_pages = 0; }
        if ($page > $total_pages) $page=$total_pages;
        if ($limit<0) $limit = 0;
        $start = $limit*$page - $limit; // do not put $limit*($page - 1)
        if ($start<0) $start = 0;
		$query = "select e.expeditie as expeditie, e.data_expeditie as data_expeditie,
			e.tip_obj,
			ievd.value as new_kg, ievd.old_value as old_kg,
			cle.nume as expeditor, cld.nume as destinatar, DATE(ie.data_op) as data_recantarire
			from client_expeditii as e
			JOIN exp_prelucrate ep on (ep.expeditie = e.expeditie and ep.anulata = 0)
			join ist_exp ie on ep.cod_expeditie = ie.cod_exp
			join ist_exp_value_double ievd on (ie.cod_ist = ievd.cod_ist and ievd.attribute='greutate')
			left join clienti cle on ep.expeditor_id = cle.cod_cl
			left join clienti cld on ep.destinatar_id = cld.cod_cl
			where {$cond}
			ORDER BY " . $sidx . " " . $sord . " LIMIT " . $start . " , " . $limit;
		//error_log($query);
        $sql = $this->db->QFetchRowArray($query);
        if (!empty($sql)) {
            foreach ($sql as $key => $row) {
				$responce->rows[$key]['id'] = $row['expeditie'];
                $responce->rows[$key]['cell'] = array($row['expeditie'], strtoupper($row['expeditor']),strtoupper($row['destinatar']),strtoupper($row['data_expeditie']),$row['tip_obj'],$row['old_kg'],$row['new_kg'],$row['data_recantarire']);
            }
        }
		$responce->page = $page;
        $responce->total = $total_pages;
        $responce->records = $count;

        return json_encode($responce);
	}

	function BorderouNou() {
		$this->vars['title_page'] = 'Liste Expeditii pt Borderou';
        return $this->Parse($this->page_prefix . 'borderou_nou.html', array_map('htmlspecialchars', ['PRINT_AWB' => $this->print_awb]));
    }

	function JSON_Borderou($nr) {
		$nr = intval($nr);
		$responce = new StdClass();

		if($nr == 0 && $this->master_id == 171350 && $this->master_id != $this->expeditor_id) //maravet
			return json_encode($responce);

		if($nr > 0)
        	$cond =' e.borderou_id='.$nr;
		else{
			if($this->expeditor_id == 171350 || $this->selectie_puncte_de_lucru) {
				//maravet
				$cond = " e.borderou_id = 0 AND e.user_id in (select id from users where expeditor_id in (select cod_cl from clienti where master = {$this->master_id}))";
			}
			else if($this->is_master) {
				$cond = " e.borderou_id = 0 AND e.user_id in (select id from users where expeditor_id = {$this->expeditor_id})";
			}
			else {
				$cond = " e.borderou_id = 0 AND e.user_id = {$this->user_id}";
			}
		}

		if(isset($_REQUEST['_search'])){
			$searchOn = $this->Strip($_REQUEST['_search']);
			if ($searchOn == 'true') {
				$searchstr = $this->Strip($_REQUEST['filters']);
				$cond .= $this->constructWhere($searchstr);
			}
		}

		$page = intval($_REQUEST['page'] ?? 1);
		$limit = intval($_REQUEST['rows'] ?? 250);
		$sidx = trim($this->sanitize($_REQUEST['sidx'] ?? "e.expeditie"));
		$sord = trim($this->sanitize($_REQUEST['sord'] ?? 'desc'));

		$query = "SELECT COUNT(e.id) as nr
			FROM client_expeditii e
			left join clienti cle on e.expeditor = cle.cod_cl
			left join localitati lce on lce.cod_lc = cle.cod_lc
			left join clienti cld on e.destinatar_cod_cl = cld.cod_cl
			left join localitati lcd on lcd.cod_lc = cld.cod_lc
			WHERE {$cond}  and e.anulata=0;";
			//error_log($query);
        $result = $this->db->QFetchArray($query);
        $count = !empty($result['nr']) ? $result['nr'] : 0;

        if( $count >0 ) {$total_pages = ceil($count/$limit); }
        else { $total_pages = 0; }
        if ($page > $total_pages) $page=$total_pages;
        if ($limit<0) $limit = 0;
        $start = $limit*$page - $limit; // do not put $limit*($page - 1)
        if ($start<0) $start = 0;
        $query = "SELECT e.id, e.expeditie, e.data_expeditie, e.tip_obj, e.piese,
			e.greutate, e.valoare_totala, e.valoare_tva, (e.km_ext_prel+e.km_ext_livr) as km, e.printed,
			cle.nume as expeditor_nume, lce.nume_lc as expeditor_localitate,
			cld.nume as destinatar_nume, lcd.nume_lc as destinatar_localitate
            FROM client_expeditii e
			left join clienti cle on e.expeditor = cle.cod_cl
			left join localitati lce on lce.cod_lc = cle.cod_lc
			left join clienti cld on e.destinatar_cod_cl = cld.cod_cl
			left join localitati lcd on lcd.cod_lc = cld.cod_lc
            WHERE {$cond} and e.anulata=0
            ORDER BY " . $sidx . " " . $sord . " LIMIT " . $start . " , " . $limit . ";";
			//echo $query;die;
		//error_log($query);
        $sql = $this->db->QFetchRowArray($query);
		$total_piese = 0;
		$total_greutate = 0;
		$print_borderou = "Printeaza";
		if($this->print_awb != 2) {
        	$print_borderou = "PDF";
        }

        if (!empty($sql)) {
            foreach ($sql as $key => $row) {
				if($this->preturi == 2){
					$row['valoare_totala'] 	= 0;
					$row['valoare_tva'] 	= 0;
				}
				$responce->rows[$key]['id'] = $row['expeditie'];
				$total_greutate += round($row['greutate'],2);
				$total_piese += $row['piese'];
				$row['optiuni'] = '<a href="javascript:;" onclick="ClientPrintExpeditie('.$row['expeditie'].','.$this->print_awb.');" style="text-decoration:none;">'.$print_borderou.'</a>';
				if($nr==0 && $row['printed'] == 0)
					$row['optiuni'] .= '&nbsp;|&nbsp;<a href="javascript:;" onclick="AnuleazaExpeditieClientBorderou('.$row['id'].');" style="text-decoration:none;">Sterge</a>';
                $responce->rows[$key]['cell'] = array($row['expeditie'], strtoupper($row['expeditor_nume']),strtoupper($row['expeditor_localitate']),strtoupper($row['destinatar_nume']),strtoupper($row['destinatar_localitate']),$row['data_expeditie'],$row['tip_obj'],$row['piese'],$row['greutate'],$row['km'],$row['valoare_totala'],$row['valoare_tva'],$row['optiuni']);
            }
        }
		$responce->page = $page;
        $responce->total = $total_pages;
        $responce->records = $count;

		$responce->userdata['expeditie'] = 'Total:';
		$responce->userdata['expeditor_nume'] = $count.' Expeditii';
		$responce->userdata['expeditor_localitate'] = $total_piese.' colete';
		$responce->userdata['destinatar_nume'] = $total_greutate.' kg';

        return json_encode($responce);
    }

	function GenerareBorderou(){
		$responce = new StdClass();
		if(empty($_POST['exps'])) return 1;
        $bos = $_POST['exps'];

        $bos = json_decode($bos,true);
        if(count($bos) == 0) return 1;

        $bos = implode(",", $bos);
        $bos = "(".$bos.")";

		$expeditor_id = $this->expeditor_id;

		if($this->master_id == 171350 && $this->master_id != $expeditor_id) //maravet
			return json_encode($responce);

		if($expeditor_id == 171350 || $this->selectie_puncte_de_lucru) {
			//maravet
			$cond = " borderou_id = 0 AND user_id in (select id from users where expeditor_id in (select cod_cl from clienti where master = {$this->master_id}))";
		}
		else if($this->is_master) {
			$cond = " borderou_id = 0 AND user_id in (select id from users where expeditor_id = {$expeditor_id})";
		}
		else {
			$cond = " borderou_id = 0 AND user_id = {$this->user_id}";
		}

		$query = "SELECT COUNT(id) as nr FROM client_expeditii WHERE ".$cond." and anulata=0 and expeditie in ".$bos;
        $result = $this->db->QFetchArray($query);
        if(empty($result['nr']))
			return 1;

		$query1 = "SELECT MAX(borderou_id) as borderou_id FROM client_borderouri WHERE client_id = ".$expeditor_id;
		$max = $this->db->QFetchArray($query1);
		if(empty($max['borderou_id'])) $max['borderou_id'] = 0;

		if($this->selectie_puncte_de_lucru){
			$query2 = "SELECT COUNT(distinct expeditor) as ckk, expeditor FROM client_expeditii WHERE ".$cond." and anulata=0 and expeditie in ".$bos;
			$result2 = $this->db->QFetchArray($query2);
			if(!empty($result2['ckk']) && $result2['ckk'] == 1)
				$expeditor_id = $result2['expeditor'];
		}

		$vi = [];
		$vi['data'] = date('Y-m-d H:i:s');
		$vi['status'] = 'Nereceptionat';
		$vi['user_id'] = $this->user_id;
		$vi['expeditii'] = $result['nr'];
		$vi['client_id'] = $expeditor_id;
		$vi['borderou_id'] = $max['borderou_id'] + 1;
		$borderou_id = $this->db->QueryInsert($this->tables['client_borderouri'], $vi);

        $this->db->QueryUpdate($this->tables['client_expeditii'], ['borderou_id' => $borderou_id], $cond ." and anulata = 0 and borderou_id = 0 and expeditie in ".$bos);

		// Receptie automata borderou

		if($this->config['client']['receptie_automata']){
			$m_exp = new ModulExpeditii($this->config, 1, $this->db);
			$res = $m_exp->BorderouriReceptie($borderou_id, 8);
			if($res != 'OK'){
				error_log('Eroare receptie automata !');
				error_log($res);
			}
		}

		return 2;
	}

	function GenerareBorderouMultipla(){
		$responce = new StdClass();
		if(empty($_POST['exps'])) return 1;
        $bos = $_POST['exps'];

        $bos = json_decode($bos,true);
        if(count($bos) == 0) return 1;

        $bos = implode(",", $bos);
        $bos = "(".$bos.")";

		if($this->master_id == 171350 && $this->master_id != $this->expeditor_id) //maravet
			return json_encode($responce);
		if($this->expeditor_id == 171350 || $this->selectie_puncte_de_lucru) {
			//maravet
			$cond = " borderou_id = 0 AND user_id in (select id from users where expeditor_id in (select cod_cl from clienti where master = {$this->master_id}))";
		}
		else if($this->is_master) {
			$cond = " borderou_id = 0 AND user_id in (select id from users where expeditor_id = {$this->expeditor_id})";
		}
		else {
			$cond = " borderou_id = 0 AND user_id = {$this->user_id}";
		}

		$query = "SELECT COUNT(id) as nr, COUNT(distinct expeditor) as ckk FROM client_expeditii WHERE ".$cond." and anulata=0 and expeditie in ".$bos;
        $result = $this->db->QFetchArray($query);
        if(empty($result['nr']))
			return 1;

		if($this->selectie_puncte_de_lucru && !empty($result['ckk']) && $result['ckk'] > 1)   //mai multe puncte de lucru
		{
			$query2 = "SELECT expeditor as expeditor_id, count(id) as nr FROM client_expeditii WHERE ".$cond." and anulata=0 and expeditie in ".$bos." group by expeditor";
			$result2 = $this->db->QFetchRowArray($query2);
			if(!empty($result2['expeditor_id'])){
				foreach ($result2 as $key => $row) {
					$this->GenerareBoderouOnly($bos, $cond, $this->master_id, $row['expeditor_id'], $row['nr']);
				}
			}
		}
		else{
			$query1 = "SELECT MAX(borderou_id) as borderou_id FROM client_borderouri WHERE client_id = ".$this->expeditor_id;
			$max = $this->db->QFetchArray($query1);
			if(empty($max['borderou_id'])) $max['borderou_id'] = 0;

			$vi = [];
			$vi['data'] = date('Y-m-d');
			$vi['status'] = 'Nereceptionat';
			$vi['user_id'] = $this->user_id;
			$vi['expeditii'] = $result['nr'];
			$vi['client_id'] = $this->expeditor_id;
			$vi['borderou_id'] = $max['borderou_id']+1;
			$borderou = $this->db->QueryInsert($this->tables['client_borderouri'], $vi);

			$vu=[];
			$vu['borderou_id'] = $borderou;
			$this->db->QueryUpdate($this->tables['client_expeditii'], $vu, $cond ." and anulata=0 and borderou_id = 0 and expeditie in ".$bos);

			// Receptie automata borderou

			if($this->config['client']['receptie_automata']){
				$m_exp = new ModulExpeditii($this->config, 1, $this->db);
				$_POST['curier'] = 414;
				$_POST['curier_nume'] = 'SOFT CLIENT (BUCURESTI)';
				$res = $m_exp->BorderouriReceptie($vu['borderou_id'], 8);
				if($res != 'OK'){
					error_log('Eroare receptie automata !');
					error_log($res);
				}
			}
		}
		return 2;
	}

	private function GenerareBoderouOnly($bos, $cond, $master_id, $expeditor_id, $nr){
		$query1 = "SELECT MAX(borderou_id) as borderou_id FROM client_borderouri WHERE client_id in (select cod_cl from clienti where master = ".$master_id.")";;
		$max = $this->db->QFetchArray($query1);
		if(empty($max['borderou_id'])) $max['borderou_id'] = 0;

		$vi = [];
		$vi['data'] = date('Y-m-d');
		$vi['status'] = 'Nereceptionat';
		$vi['user_id'] = $this->user_id;
		$vi['expeditii'] = $nr;
		$vi['client_id'] = $expeditor_id;
		$vi['borderou_id'] = $max['borderou_id']+1;
		$borderou = $this->db->QueryInsert($this->tables['client_borderouri'], $vi);

		$vu=[];
		$vu['borderou_id'] = $borderou;
		$this->db->QueryUpdate($this->tables['client_expeditii'], $vu, $cond ." and anulata=0 and borderou_id = 0 and expeditie in ".$bos);

		// Receptie automata borderou

		if($this->config['client']['receptie_automata']){
			$m_exp = new ModulExpeditii($this->config, 1, $this->db);
			$_POST['curier'] = 414;
			$_POST['curier_nume'] = 'SOFT CLIENT (BUCURESTI)';
			$res = $m_exp->BorderouriReceptie($vu['borderou_id'], 8);
			if($res != 'OK'){
				error_log('Eroare receptie automata !');
				error_log($res);
			}
		}
	}

	function ListeBorderouri() {
        $this->vars['title_page'] = 'Liste Borderouri';
        return $this->Parse($this->page_prefix . 'liste_borderouri.html');
    }

	function JSON_ListeBorderouri() {
		$responce = new StdClass();

		if($this->expeditor_id == 171350) //maravet
			$cond =' b.user_id in (select id from users where expeditor_id in (select cod_cl from clienti where master = '.$this->master_id.'))';
		else
			$cond =' b.user_id = '.$this->user_id;

		$page = intval($_REQUEST['page'] ?? 1);
		$limit = intval($_REQUEST['rows'] ?? 20);
		$sidx = trim($this->sanitize($_REQUEST['sidx'] ?? 1));
		$sord = trim($this->sanitize($_REQUEST['sord'] ?? 'asc'));

		$query = "SELECT COUNT(b.id) as nr
			FROM client_borderouri b
			WHERE {$cond}";
			//echo $query;die;
        $result = $this->db->QFetchArray($query);
        $count = !empty($result['nr']) ? $result['nr'] : 0;

        if( $count >0 ) {$total_pages = ceil($count/$limit); }
        else { $total_pages = 0; }
        if ($page > $total_pages) $page=$total_pages;
        if ($limit<0) $limit = 0;
        $start = $limit*$page - $limit; // do not put $limit*($page - 1)
        if ($start<0) $start = 0;
        $query = "SELECT b.id, b.borderou_id, b.data, count(e.expeditie) as expeditii
            FROM client_borderouri b
			left join client_expeditii e on e.borderou_id = b.id
            WHERE {$cond}
			group by b.id
            ORDER BY " . $sidx . " " . $sord . " LIMIT " . $start . " , " . $limit;
    	//error_log($query);
        $sql = $this->db->QFetchRowArray($query);
		$print_borderou = "Printeaza borderou";
        $print_expeditie = "Printeaza Expeditiile";
		$export_expeditie = "Exporta CSV";
        if($this->print_awb != 2) {
        	$print_borderou = "Borderou in PDF";
        	$print_expeditie = "Expeditiile in PDF";
        }
        if (!empty($sql)) {
            foreach ($sql as $key => $row) {
				$responce->rows[$key]['id'] = $row['id'];
				$row['optiuni'] = "";
				$row['optiuni'] .= ' <a href="javascript:;" class="printare_multipla" style="text-decoration:none;" onclick="ClientPrintBorderou('.$row['id'].');">'.$print_borderou.'</a>';
				$row['optiuni'] .= '  <a href="javascript:;" class="printare_multipla" style="text-decoration:none;" onclick="ClientBorderouPrintareMultipla('.$row['id'].','.$this->print_awb.');">'.$print_expeditie.'</a>';
				$row['optiuni'] .= '  <a href="javascript:;" class="printare_multipla" style="text-decoration:none;" onclick="ClientExportBorderou('.$row['id'].','.$this->print_awb.');">'.$export_expeditie.'</a>';
				$responce->rows[$key]['cell'] = array($row['borderou_id'],$row['expeditii'],$row['data'],$row['optiuni']);
            }
        }
		$responce->page = $page;
        $responce->total = $total_pages;
        $responce->records = $count;

        return json_encode($responce);
    }

	function DetaliiExpeditie(){
        $vars=[];
		$expeditie = intval($_POST['expeditie'] ?? 0);
        if(empty($expeditie) || !ExpeditieDto::isAppAwb($expeditie)) return $this->Error('Expeditie Invalida!!!');

		if(false === ($vars = $this->GetValuesClient($expeditie))) return $this->Error('Expeditie Inexistenta!!!');
		
		if(!empty($vars['updated_by']))
			$vars['updated_at'] = $this->CreateDate($vars['updated_at'],'d.m.Y H:i:s');
		else $vars['updated_at'] = '';
		if(!empty($vars['printed_by']))
			$vars['printed_at'] = $this->CreateDate($vars['printed_at'],'d.m.Y H:i:s');
		else $vars['printed_at'] = '';

		$vars['TIP_OBJ_1'] = 0;
		$vars['TIP_OBJ_2'] = 0;
		$vars['TIP_OBJ_3'] = 0;
	  	$vars['TIP_OBJ_'.$vars['tip_obj']] = $vars['piese'];
		$vars['greutate'] = number_format($vars['greutate'], 3, '.', '').' Kg';

        if(!empty($vars['liv_sambata'])) $vars['liv_sambata'] = 'DA';
        else $vars['liv_sambata'] = 'NU';
        if(!empty($vars['ret_amb'])) $vars['RETUR_AMB'] = 'DA';
		else $vars['RETUR_AMB'] = 'NU';
		if(!empty($vars['ret_colet'])) $vars['RETUR_COLET'] = 'DA';
		else $vars['RETUR_COLET'] = 'NU';
		if(!empty($vars['extrainfo'])) $vars['RETUR_NC'] = 'DA';
		else $vars['RETUR_NC'] = 'NU';
        if(!empty($vars['liv_sediu'])) $vars['liv_sediu'] = 'DA';
		else $vars['liv_sediu'] = 'NU';
		if(!empty($vars['ret_nt'])) $vars['RETUR_NT'] = 'DA';
		else $vars['RETUR_NT'] = 'NU';
	 	if(!empty($vars['ret_doc'])) $vars['RETUR_DOC'] = 'DA';
		else $vars['RETUR_DOC'] = 'NU';
		if(!empty($vars['sms'])) $vars['sms'] = 'DA';
		else $vars['sms'] = 'NU';
		if(!empty($vars['copen'])) $vars['copen'] = 'DA';
		else $vars['copen'] = 'NU';

		$vars['RET_NC_SHOW'] = $vars['RET_AMB_SHOW'] = 'ascuns';

		if($this->expeditor_id == 171350) //maravet
			$vars['RET_NC_SHOW'] = '';
		if(!empty($this->ret_amb))
			$vars['RET_AMB_SHOW'] = '';

		if($vars['ramburs'] > 0)
		{
			if($vars['tip_plata'] == 0) $vars['tip_plata'] = 'cash';
			else if($vars['tip_plata'] == 1) $vars['tip_plata'] = 'bo';
			else if($vars['tip_plata'] == 2) $vars['tip_plata'] = 'cec';
			else if($vars['tip_plata'] == 3) $vars['tip_plata'] = 'cont';
		}
		else $vars['tip_plata'] = '';

		if($vars['platitor'] == 1) $vars['platitor'] = 'Expeditor';
		else $vars['platitor'] = 'Destinatar';

		if($vars['borderou_id'] > 0) $vars['class_edit'] = 'style="display: none;"';

        if(!empty($vars['volum'])) {
            $volumes = explode("x",$vars['volum']);
            if(count($volumes) == 3) {
                list($vars['volum1'], $vars['volum2'], $vars['volum3']) = $volumes;
            }
        }
        $vars['greutate_vol'] = round($sqlExp['greutate_vol'] ?? 0.000, 3);

		$vars['user_id'] = $this->print_awb;
		$vars['print'] = ($this->print_awb == 2) ? "Print" : "PDF";

        return $this->Parse($this->page_prefix . 'detalii_expeditie.html', array_map('htmlspecialchars', $vars));
    }

	function DatePersonale($error='') {
        $this->vars['title_page'] = 'Date Personale';

        $sql = $this->db->QFetchArray("SELECT hashParola FROM users WHERE id={$this->user_id} and activ = 1");
        if (empty($sql))
            return $this->Error("Inexistent ID");

		$vars = [];
        if (empty($_POST['step']) || $_POST['step'] == 1) {
            if ($error != '')
                $this->vars['error'] = $error;
            $vars['cod'] = $this->GetCod();
			$vars['nume'] = $this->expeditor_contact;
			$vars['telefon'] = $this->expeditor_telefon;
			$vars['expeditor'] = $this->expeditor_nume;
			$vars['expeditor_localitate'] = $this->expeditor_localitate;
			$vars['expeditor_adresa'] = $this->expeditor_adresa;
			$vars['expeditor_cui'] = $this->master_cui;
			$vars['expeditor_j'] = $this->master_j;
		  	$vars['print_awb_'.$this->print_awb] = 'checked="checked"';
			$vars['print_add_'.$this->print_add] = 'checked="checked"';
			$vars['def_sms_'.$this->def_sms] = 'checked="checked"';
			$vars['observatii'] = $this->def_obsv;

            return $this->Parse($this->page_prefix . 'date_personale.html', array_map('htmlspecialchars', $vars));
        }
		else if (!empty($_POST['step']) && $_POST['step'] == 2) {
        	foreach($_POST as $key=>$field){
					$vars[$key] = $this->sanitize($field);
			}
			$vars['nume'] = strtoupper(Backend::sSanitizeCleanEdges($vars['nume'] ?? ''));
			$vars['observatii'] = Backend::sSanitizeCleanEdges($vars['observatii'] ?? '');
            $_POST['step'] = 1;
            if (!empty($_POST['cod'])) {
                if (isset($vars['but_submit'])) {
					if (!$this->ValidateFields($vars, 'nume,telefon'))
						return $this->DatePersonale($this->Error('Completati Persoana si Telefonul de contact!'));
					session_start();
                    $vu = [];
                    $_SESSION["user"]["nume"] = $this->expeditor_contact = $vu['nume'] = $vars['nume'];
					$_SESSION["user"]["print_awb"] = $this->print_awb = $vu['print_awb'] = $vars['print_awb'];
					$_SESSION["user"]["print_add"] = $this->print_add = $vu['print_add'] = $vars['print_add'];
                    $_SESSION["user"]["telefon"] = $this->expeditor_telefon = $vu['telefon'] = $vars['telefon'];
                    $vu['date_op'] = date("Y-m-d H:i:s");
					$_SESSION["user"]["def_obsv"] = $this->def_obsv = $vu['def_obsv'] = $vars['observatii'];
					$_SESSION["user"]["def_sms"] = $this->def_sms = $vu['def_sms'] = $vars['def_sms'] ?? 2;
                    //preprocessing
                    $this->db->QueryUpdate($this->tables['users'], $vu, 'id=' . $this->user_id);
					session_write_close();
                }
				else if (isset($vars['but_submit2'])) {
                    if (!$this->ValidateFields($vars, 'pass')) {
                        return $this->DatePersonale($this->Error('Va rugam sa introduceti parola curenta!'));
                    }
					if (password_verify($vars['pass'], $sql['hashParola']) == false) {
                        return $this->DatePersonale($this->Error('Parola curenta este incorecta!'));
                    }

                    if (!$this->ValidateFields($vars, 'pass1,pass2'))
                        return $this->DatePersonale($this->Error('Completati ambele parole'));
                    //the two new passwords
                    if ($vars['pass1'] != $vars['pass2']) {
                        return $this->DatePersonale($this->Error('Ambele parole trebuie sa fie identice.'));
                    }
                    //insert the password
					$this->db->QueryUpdate($this->tables['users'], ['hashParola' => password_hash($vars['pass1'], PASSWORD_DEFAULT), 'data_parola' => date('Y-m-d H:i:s')], 'id=' . $this->user_id);
                }
				unset($_POST['cod']);
                return $this->DatePersonale($this->Error('Modificarile au fost salvate cu succes!', 1));
            }
			else
                return $this->DatePersonale($this->Error('Browser refresh!'));
        }
    }


	 function ComandaNoua() {
        $this->vars['title_page'] = 'Comanda noua';
		$today = new DateTime("now");
		$hNow = (int)$today->format('H');
		$hStartSelected = 9;
		$hEndSelected = 17;

		//error_log('Hour now: '.$hNow);

		if($hNow >= 17) {
			$today->add(new DateInterval('P1D'));
		}
		else {
			if($hStartSelected < $hNow) $hStartSelected = $hNow;
			if($hStartSelected >= $hEndSelected) {
				$hEndSelected = $hStartSelected + 1;
			}
		}

		$vars['JSON_PCS'] = json_encode($this->pcs);
		$vars['SELECTED_ID'] = $this->expeditor_id;

		$vars['COLLECT_AT'] = $today->format('Y-m-d');
        $vars['CLIENT'] = $this->expeditor_nume;
        $vars['COD_CL'] = $this->expeditor_id;
        $vars['LOCALITATE'] = $this->expeditor_localitate;
        $vars['LOCALITATE_ID'] = $this->expeditor_localitate_id;
        $vars['ADRESA'] = $this->expeditor_adresa;
		$vars['CONTACT'] = $this->expeditor_contact;
		$vars['TELEFON'] = $this->expeditor_telefon;
		$vars['OBSERVATII'] = '';
		$vars['H_START'] = $this->OptionsInterval($hStartSelected);
		$vars['H_END'] = $this->OptionsInterval($hEndSelected);

        return $this->Parse($this->page_prefix . 'comanda_noua.html', $vars);
    }

    function AdaugareComanda(){
		$today = new DateTime("now");
		$hNow = (int)intval($today->format('H'));
		$today->setTime(0, 0, 0);

    	$post = [];
		if(!empty($_POST['data']) && count($_POST['data']) > 0) {
			foreach($_POST['data'] as $key=>$field){
				$post[$field['name']] = $this->sanitize($field['value']);
			}
		}

		$hStartSelected = $post['h_start'] = intval($post['h_start'] ?? 0);
		$hEndSelected = $post['h_end'] = intval($post['h_end'] ?? 0);
		//validare
		if(empty($post['collect_at']))
			return 'Data colectarii obligatorie!';
		else if(empty($post['localitate_id']))
			return 'Selectati localitatea din lista!';
		else if(empty($post['adresa']))
			return 'Introduceti adresa!';
		else if(!isset($post['nr_obj_colet']))
			return 'Introduceti numarul de colete!';
		else if(!isset($post['nr_obj_palet']))
			return 'Introduceti numarul de palete!';
		else if(!isset($post['kg_obj']))
			return 'Introduceti greutatea!';
		else if($post['h_start'] == 0 || $post['h_end'] == 0)
			$mesaj = 'Introduceti interval ridicare!';
		else if($post['h_start'] > $post['h_end'])
			$mesaj = 'Interval ridicare gresit!';

		$collect_at = Backend::sSanitize($post['collect_at'] ?? "");
		if(false === ($collect_at = DateTime::createFromFormat('d.m.Y', $collect_at)))
			$collect_at = new DateTime("now");
		$collect_at->setTime(0, 0, 0);
		if($collect_at < $today)
			$collect_at = $today;

		$interval = $collect_at->diff($today);
		$intervalDays = intval($interval->format("%a"));
		if($intervalDays == 0) {
			if($hStartSelected < $hNow) $hStartSelected = $hNow;
			if($hStartSelected >= $hEndSelected) {
				$hEndSelected = $hStartSelected + 1;
			}
		}

		//hStartSelected after 17 hour -> move to next day
		if($hStartSelected >= 17) {
			$collect_at->add(new DateInterval('P1D'));
			$hStartSelected = 9;
			$hEndSelected = 17;
			$collect_at->setTime($hStartSelected, 0, 0);
		}

		$post['ridica_de_la'] = strtoupper(Backend::sSanitizeCleanEdges($post['ridica_de_la'] ?? ''));
		$post['client_nume'] = strtoupper(Backend::sSanitizeCleanEdges($post['client_nume'] ?? $this->expeditor_nume));
		$post['adresa'] = strtoupper(Backend::sSanitizeCleanEdges($post['adresa'] ?? $this->expeditor_adresa));
		$post['contact'] = strtoupper(Backend::sSanitizeCleanEdges($post['contact'] ?? $this->expeditor_contact));

		$vi=[];

		$vi['collect_at'] = $collect_at->format('Y-m-d');
		$vi['created_by'] = $this->user_id;
	    $vi['h_start'] = $hStartSelected;
		$vi['h_end'] = $hEndSelected;
		$vi['client'] = trim($post['client_nume']);
		if(!empty(Backend::sSanitizeCleanEdges($post['ridica_de_la'])))
			$vi['client'] = Backend::sSanitizeCleanEdges($post['ridica_de_la']);
		$vi['client_id'] = intval($post['client_id'] ?? $this->expeditor_id);
		$vi['localitate_id'] = intval($post['localitate_id'] ?? $this->expeditor_localitate_id);
		$vi['adresa'] = $post['adresa'];
		$vi['contact'] = $post['contact'];
		$vi['telefon'] = Backend::sSanitize($post['telefon'] ?? $this->expeditor_telefon);
		$vi['nr_obj_colet'] = intval($post['nr_obj_colet'] ?? 0);
		$vi['nr_obj_palet'] = intval($post['nr_obj_palet'] ?? 0);
		$vi['kg_obj'] = intval($post['kg_obj'] ?? 0);
		$vi['vol_obj'] = intval($post['vol_obj'] ?? "");
		$vi['observatii'] = Backend::sSanitizeCleanEdges($post['observatii'] ?? "");

		$id = $this->db->QueryInsert('comenzi', $vi);
		$this->db->QueryInsert('comenzi_history', ['status' => 1, 'comanda_id' => $id, 'created_by' => $this->user_id]);
		return 1;
	}


	function AnuleazaComanda(){
		$id = intval($_POST['id'] ?? 0);
		if($id == 0) return 0;

		$this->db->QueryInsert('comenzi_history', ['status' => 6, 'comanda_id' => $id, 'created_by' => $this->user_id]);
		return 1;
	}


    function JSON_IstoricComenzi() {
        $cond =" c.client_id = {$this->expeditor_id} ";
		if($this->selectie_puncte_de_lucru) {
			$cond =" c.client_id in (select cod_cl from clienti where master = {$this->expeditor_id})";
		}
        $responce = new StdClass();
		//start generare conditie
        $searchOn = $this->Strip($_REQUEST['_search']);
        if ($searchOn == 'true') {
            $searchstr = $this->Strip($_REQUEST['filters']);
            $cond .= $this->constructWhere($searchstr);
        }

        $page = intval($_REQUEST['page'] ?? 1);
		$limit = intval($_REQUEST['rows'] ?? 20);
		$sidx = trim($this->sanitize($_REQUEST['sidx'] ?? 1));
		$sord = trim($this->sanitize($_REQUEST['sord'] ?? 'asc'));

		$query = "SELECT COUNT(c.id) as nr
			FROM comenzi c
			inner join (
					select i.comanda_id, i.created_at , i.status, i.created_by, i.agent_id , i.motiv_id
					from comenzi_history i
					where i.created_at = (select max(created_at) from comenzi_history j where j.comanda_id=i.comanda_id )
 				)  ch on c.id = ch.comanda_id
			WHERE {$cond}";

        $result = $this->db->QFetchArray($query);
        $count = !empty($result['nr']) ? $result['nr'] : 0;

        if( $count >0 ) {$total_pages = ceil($count/$limit); }
        else { $total_pages = 0; }
        if ($page > $total_pages) $page=$total_pages;
        if ($limit<0) $limit = 0;
        $start = $limit*$page - $limit; // do not put $limit*($page - 1)
        if ($start<0) $start = 0;
        $query = "SELECT c.*, ch.status, ch.created_by as ch_created_by, cm.motiv as motiv, ch.created_at as ch_created_at, ag.nume_ag as agent
			FROM comenzi c inner join (
					select i.comanda_id, i.created_at , i.status, i.created_by, i.agent_id , i.motiv_id
					from comenzi_history i
					where i.created_at = (select max(created_at) from comenzi_history j where j.comanda_id=i.comanda_id )
 				)  ch on c.id = ch.comanda_id
			left join comenzi_motive cm on ch.motiv_id = cm.id
			left join agenti ag on ch.agent_id = ag.cod_ag
            WHERE {$cond}
            ORDER BY " . $sidx . " " . $sord . " LIMIT " . $start . " , " . $limit;
            //error_log($query);
        $sql = $this->db->QFetchRowArray($query);
        if (!empty($sql)) {
            foreach ($sql as $key => $row) {
				$responce->rows[$key]['id']=$row['id'];
                $responce->rows[$key]['cell'] = array($row['id'], $row['collect_at'],$row['ch_created_at'], $row['status'],$row['motiv'],strtoupper($row['agent']));
            }
        }
		$responce->page = $page;
        $responce->total = $total_pages;
        $responce->records = $count;
        return json_encode($responce);
    }

	function ListeComenzi() {
        $this->vars['title_page'] = 'Liste Comenzi';
        return $this->Parse($this->page_prefix . 'liste_comenzi.html');
    }

	function JSON_ListeComenzi() {
        $cond =" c.client_id = {$this->expeditor_id} ";
		if($this->selectie_puncte_de_lucru) {
			$cond =" c.client_id in (select cod_cl from clienti where master = {$this->expeditor_id})";
		}
        $responce = new StdClass();
		//start generare conditie
        $searchOn = $this->Strip($_REQUEST['_search']);
        if ($searchOn == 'true') {
            $searchstr = $this->Strip($_REQUEST['filters']);
            $cond .= $this->constructWhere($searchstr);
        }

        $page = intval($_REQUEST['page'] ?? 1);
		$limit = intval($_REQUEST['rows'] ?? 20);
		$sidx = trim($this->sanitize($_REQUEST['sidx'] ?? 1));
		$sord = trim($this->sanitize($_REQUEST['sord'] ?? 'asc'));

		$query = "SELECT COUNT(id) as nr
			FROM comenzi c
			inner join (
					select i.comanda_id, i.created_at , i.status, i.created_by, i.agent_id , i.motiv_id
					from comenzi_history i
					where i.created_at = (select max(created_at) from comenzi_history j where j.comanda_id=i.comanda_id )
 				)  ch on c.id = ch.comanda_id
			WHERE {$cond}";
			//echo $query;die;
        $result = $this->db->QFetchArray($query);
        $count = !empty($result['nr']) ? $result['nr'] : 0;

        if( $count >0 ) {$total_pages = ceil($count/$limit); }
        else { $total_pages = 0; }
        if ($page > $total_pages) $page=$total_pages;
        if ($limit<0) $limit = 0;
        $start = $limit*$page - $limit; // do not put $limit*($page - 1)
        if ($start<0) $start = 0;
        $query = "SELECT c.*, ch.status, ch.created_by as ch_created_by, cm.motiv as motiv, ch.created_at as ch_created_at, ag.nume_ag as agent, CONCAT(l.nume_lc,' (',l.cod_jd,')') as localitate
			FROM comenzi c inner join (
					select i.comanda_id, i.created_at , i.status, i.created_by, i.agent_id , i.motiv_id
					from comenzi_history i
					where i.created_at = (select max(created_at) from comenzi_history j where j.comanda_id=i.comanda_id )
 				)  ch on c.id = ch.comanda_id
			left join comenzi_motive cm on ch.motiv_id = cm.id
			join localitati l on c.localitate_id = l.cod_lc
			left join agenti ag on ch.agent_id = ag.cod_ag
            WHERE {$cond}
            ORDER BY " . $sidx . " " . $sord . " LIMIT " . $start . " , " . $limit;
            //error_log($query);
        $sql = $this->db->QFetchRowArray($query);
        if (!empty($sql)) {
            foreach ($sql as $key => $row) {
				$responce->rows[$key]['id']=$row['id'];
                $responce->rows[$key]['cell'] = array($row['id'], $row['collect_at'],$row['ch_created_at'], $row['status'],$row['motiv'],strtoupper($row['localitate']),$row['adresa'],$row['nr_obj_colet'],$row['nr_obj_palet'],$row['kg_obj'],$row['observatii'],strtoupper($row['agent']));
            }
        }
		$responce->page = $page;
        $responce->total = $total_pages;
        $responce->records = $count;
        return json_encode($responce);
    }

    function PrintExpeditieTCPDF(){
    	require_once "NewAwbPdf.php";
		$nr_exp = intval($_POST['expeditie'] ?? 0);
        if(empty($nr_exp) || !ExpeditieDto::isAppAwb($nr_exp)) return $this->Error('Expeditie invalida!');

		if(false === ($sqlExp = $this->GetValuesClient($nr_exp))) return $this->Error('Expeditie Invalida!!!');

		require_once "expeditieDto.php";
		$vars = ExpeditieDto::sqlExpClientToPdf($sqlExp);
		$vars['destinatar_nume'] = strtoupper(htmlspecialchars_decode(strtolower($vars['destinatar_nume']), ENT_QUOTES));
		$vars['destinatar_contact'] = strtoupper(htmlspecialchars_decode(strtolower($vars['destinatar_contact']), ENT_QUOTES));
		$vars['expeditor_telefon'] = $this->not_print_phone ? '' : strtoupper(htmlspecialchars_decode(strtolower($vars['expeditor_telefon']), ENT_QUOTES));
		$vars['destinatar_telefon'] = $this->not_print_phone ? '' : strtoupper(htmlspecialchars_decode(strtolower($vars['destinatar_telefon']), ENT_QUOTES));

		//nota de comanda
 		$nc = false;
 		if(!empty($vars['extrainfo'])) $nc = true;

		$filename = 'NT-'.$vars['expeditie'].'.pdf';

		$pdf = new NewAwbPdf($vars);

		$pdf->AddPage();
		$pdf->makeHalfFirstPage(0, 0);
		$pdf->makeDashedLine('H');
		if($nc)
 			$pdf->makeNotaComanda(0, 148);
 		else
 			$pdf->makeHalfFirstPage(0, 148);

		//puisori
		$piese = $vars['piese'];
		$piese-=1;
		$j = 2;
		$divizor = 4;
		if($this->print_awb == 7) $divizor = 2;
		if($piese > 0)
		{
			$nr_pag = ceil(((float)$piese)/$divizor);
			for($i=0; $i < $nr_pag && $piese > 0 && $j <= $vars['piese']; $i++)
			{
				$pdf->AddPage();
				if(!in_array($this->print_awb, [4, 5])){
					$pdf->makeDashedLine('H');
					$pdf->makeDashedLine('V');
				}
				if($piese > 0)
				{
					$pdf->makePuisorMultiCell($vars['expeditie'].'-'.ExpeditieDto::getPuisorNr($j),$j++,1);
					$piese-=1;
				}
				if($this->print_awb != 7) {
					if($piese > 0)
					{
						$pdf->makePuisorMultiCell($vars['expeditie'].'-'.ExpeditieDto::getPuisorNr($j),$j++,2);
						$piese-=1;
					}
				}

				if($piese > 0)
				{
					$pdf->makePuisorMultiCell($vars['expeditie'].'-'.ExpeditieDto::getPuisorNr($j),$j++,3);
					$piese-=1;
				}

				if($this->print_awb != 7) {
					if($piese > 0)
					{
						$pdf->makePuisorMultiCell($vars['expeditie'].'-'.ExpeditieDto::getPuisorNr($j),$j++,4);
						$piese-=1;
					}
				}
			}
		}

		// move pointer to last page
		$pdf->lastPage();

		$this->db->QueryUpdate($this->tables['client_expeditii'], array('printed_by'=>$this->user_id, 'printed_at'=>date('Y-m-d H:i:s'), 'printed'=>1), 'expeditie = ' . $nr_exp);

		$type = 'D';
		if($this->print_awb == 0) $type = 'I';
		$pdf->Output($filename,$type);
	    exit;

    }

    function PrintMasterExpeditieTCPDF(){
		require_once "NewAwbPdf.php";
    	$nr_exp = intval($_POST['expeditie'] ?? 0);
        if(empty($nr_exp) || !ExpeditieDto::isAppAwb($nr_exp)) return $this->Error('Expeditie invalida!');

    	if(false === ($sqlExp = $this->GetValuesClient($nr_exp))) return $this->Error('Expeditie Invalida!!!');
		
		require_once "expeditieDto.php";
		$vars = ExpeditieDto::sqlExpClientToPdf($sqlExp);
		$vars['destinatar_nume'] = strtoupper(htmlspecialchars_decode(strtolower($vars['destinatar_nume']), ENT_QUOTES));
		$vars['destinatar_contact'] = strtoupper(htmlspecialchars_decode(strtolower($vars['destinatar_contact']), ENT_QUOTES));
		$vars['expeditor_telefon'] = $this->not_print_phone ? '' : strtoupper(htmlspecialchars_decode(strtolower($vars['expeditor_telefon']), ENT_QUOTES));
		$vars['destinatar_telefon'] = $this->not_print_phone ? '' : strtoupper(htmlspecialchars_decode(strtolower($vars['destinatar_telefon']), ENT_QUOTES));

		//nota de comanda
 		$nc = false;
 		if(!empty($vars['extrainfo'])) $nc = true;

		$vars['greutate'] = round($vars['greutate'], 3);

		$filename = 'Master-NT-'.$vars['expeditie'].'.pdf';

		$pdf = new NewAwbPdf($vars);

		$pdf->AddPage();
		$pdf->makeHalfFirstPage(0, 0);
		$pdf->makeDashedLine('H');
		if($nc)
 			$pdf->makeNotaComanda(0, 148);
 		else
 			$pdf->makeHalfFirstPage(0, 148);
		// move pointer to last page
		$pdf->lastPage();

		$this->db->QueryUpdate($this->tables['client_expeditii'], array('printed_by'=>$this->user_id, 'printed_at'=>date('Y-m-d H:i:s'), 'printed'=>1), 'expeditie = ' . $nr_exp);

		//I: send the file inline to the browser. The plug-in is used if available. The name given by filename is used when one selects the "Save as" option on the link generating the PDF.
		//D: send to the browser and force a file download with the name given by filename.
		 $type = 'I';
		if($this->print_awb != 2) $type = 'D';
		$pdf->Output($filename,$type);
	    exit;

    }

	function PrintPuisoriExpeditieNewTCPDF($nr_exp=0){
		require_once "NewAwbPdf.php";
		$nr_exp = intval($nr_exp);
		if(empty($nr_exp) || !ExpeditieDto::isAppAwb($nr_exp)) return $this->Error('Expeditie invalida!');

		if(false === ($sqlExp = $this->GetValuesClient($nr_exp))) return $this->Error('Expeditie Invalida!!!');
		
		require_once "expeditieDto.php";
		$vars = ExpeditieDto::sqlExpClientToPdf($sqlExp);
		$vars['destinatar_nume'] = strtoupper(htmlspecialchars_decode(strtolower($vars['destinatar_nume']), ENT_QUOTES));
		$vars['destinatar_contact'] = strtoupper(htmlspecialchars_decode(strtolower($vars['destinatar_contact']), ENT_QUOTES));
		$vars['expeditor_telefon'] = $this->not_print_phone ? '' : strtoupper(htmlspecialchars_decode(strtolower($vars['expeditor_telefon']), ENT_QUOTES));
		$vars['destinatar_telefon'] = $this->not_print_phone ? '' : strtoupper(htmlspecialchars_decode(strtolower($vars['destinatar_telefon']), ENT_QUOTES));
		
		$filename = 'NT-'.$vars['expeditie'].'.pdf';
		$vars['autocolant'] = true;
		$pdf = new NewAwbPdf($vars);
		$pdf->autocolant = true;
		$pdf->doarPuisori = true;

		//puisori
		$piese = $vars['piese'];
		$piese-=1;
		$j = 2;
		if($piese > 0)
		{
			$i=1; $j=2; $k=1;
			$nr_pag = ceil(((float)$piese));
			for($ix=0; $ix < $nr_pag && $piese > 0 && $j <= $vars['piese']; $ix++)
			{
				$pdf->AddPage();// prima pagina
				$cod_bare = $vars['expeditie'].'-'.ExpeditieDto::getPuisorNr($j);
				$pdf->makePuisorMultiCell($cod_bare,$j++,$k++);
				$i++;
			}
			// move pointer to last page
			$pdf->lastPage();
		}

		$this->db->QueryUpdate($this->tables['client_expeditii'], array('printed_by'=>$this->user_id, 'printed_at'=>date('Y-m-d H:i:s'), 'printed'=>1), 'expeditie = ' . $nr_exp);

		$type = 'I';
		if($this->print_awb == 1) $type = 'D';
		$pdf->Output($filename,$type);
		exit;

	}

    function PrintPuisoriExpeditieTCPDF(){
		$nr_exp = intval($_POST['expeditie'] ?? 0);

		if($this->print_awb == 4){
			return $this->PrintPuisoriExpeditieNewTCPDF($nr_exp);
		}

    	require_once "PuisorPdf.php";
		if(empty($nr_exp) || !ExpeditieDto::isAppAwb($nr_exp)) return $this->Error('Expeditie invalida!');

		if(false === ($vars = $this->GetValuesClient($nr_exp))) return $this->Error('Expeditie Invalida!!!');

		$vars['destinatar_nume'] = strtoupper(htmlspecialchars_decode(strtolower($vars['destinatar_nume']), ENT_QUOTES));
		$vars['destinatar_contact'] = strtoupper(htmlspecialchars_decode(strtolower($vars['destinatar_contact']), ENT_QUOTES));
		$vars['expeditor_telefon'] = $this->not_print_phone ? '' : strtoupper(htmlspecialchars_decode(strtolower($vars['expeditor_telefon']), ENT_QUOTES));
		$vars['destinatar_telefon'] = $this->not_print_phone ? '' : strtoupper(htmlspecialchars_decode(strtolower($vars['destinatar_telefon']), ENT_QUOTES));

		if(empty($vars['moneda'])) $vars['moneda'] = 1;
		$vars['moneda'] = ExpeditieDto::MONEDA[$vars['moneda']] ?? "LEI";

		$vars['print_awb'] = $this->print_awb;

		$vars['greutate'] = round($vars['greutate'], 3);
		$vars['tip_obj'] = match($vars['tip_obj']) {
			1 => 'PLIC',
			2 => 'COLET',
			3 => 'PALET',
			default => ''
		};

		$piese = $vars['piese'];
		if($piese > 1) {
			$filename = 'Puisori-NT-'.$vars['expeditie'].'.pdf';
			$pdf = new PuisorPdf($vars);
			//puisori
			for($j=2; $j <= $piese ; $j++)
			{
				$pdf->AddPage();
				$pdf->makePuisorMultiCell($vars['expeditie'].'-'.ExpeditieDto::getPuisorNr($j),$j);
			}

			// move pointer to last page
			$pdf->lastPage();
			//D: send to the browser and force a file download with the name given by filename.
			$pdf->Output($filename, 'D');
		}
		else return $this->Error('Expeditie fara puisori!');
	    exit;
    }


	function ExportBorderou($nr_brderou=0){
		$nr_brderou = intval($nr_brderou);
		$query = "SELECT GROUP_CONCAT(expeditie) as expeditii
            FROM client_expeditii
            WHERE borderou_id = {$nr_brderou} ORDER BY data_expeditie desc";
		$sql = $this->db->QFetchArray($query);
		if (!empty($sql) && !empty($sql['expeditii'])) {
			$this->ClientExportExpeditiiCsv("(".$sql['expeditii'].")");
		}
	}

    function ExportExpeditii() {
		$pData = isset($_POST['pJson'])?base64_decode($_POST['pJson']):'';
		if(empty($pData)) return $this->Error('Expeditie invalida!');

		$exps = json_decode($pData, true);
        if(!is_array($exps) || count($exps) == 0) return $this->Error('Expeditie invalida!');

        $exps = "(".implode(",", $exps).")";
        return $this->ClientExportExpeditiiCsv($exps);
	}

	function ExportRetururi() {
		$pData = isset($_POST['pJson'])?base64_decode($_POST['pJson']):'';
		if(empty($pData)) return $this->Error('Expeditie invalida!');

		$exps = json_decode($pData, true);
        if(!is_array($exps) || count($exps) == 0) return $this->Error('Expeditie invalida!');

        $exps = "(".implode(",", $exps).")";
        return $this->ClientExportRetururiCsv($exps);
	}

	function ClientExportExpeditiiCsv($exps) {

		$preturi = true;
		if($this->preturi == 2){
			$preturi = false;
		}

		$arr=[];
		$arr[0][1] = 'Nr. NT';
		$arr[0][2] = 'Data colectarii';
		$arr[0][3] = 'Expeditor';
		$arr[0][4] = 'Localitate';
		$arr[0][5] = 'Destinatar';
		$arr[0][6] = 'Localitate';
		$arr[0][7] = 'Tip';
		$arr[0][8] = 'Nr. piese';
		$arr[0][9] = 'Greutate';
		$arr[0][10] = 'Retur NT';
		$arr[0][11] = 'Retur documente';
		$arr[0][12] = 'Deschidere colet';
		$arr[0][13] = 'Ramburs';
		$arr[0][14] = 'Valoare ramburs';
		if($preturi)
			$arr[0][15] = 'Valoare expeditie';
		$arr[0][16] = 'Detalii doc.';
		$arr[0][17] = 'Observatii';
		$arr[0][18] = 'Status';
		$arr[0][19] = 'Data Status';
		$arr[0][20] = 'Primitor';
		$arr[0][21] = 'Checkpoint';
		$arr[0][22] = 'Centru';

		//data,nume destinatar,localitate,plic,nr colete,palet,greutate,retur nt,retur doc,ramburs,status,nume primitor
        $query = "SELECT cep.expeditie, cep.data_expeditie, cle.nume as expeditor_nume, lce.nume_lc as expeditor_localitate, cld.nume as destinatar_nume, lcd.nume_lc as destinatar_localitate,
			cep.tip_obj, cep.piese, cep.greutate, cep.ret_nt, cep.ret_doc, cep.copen, cep.ramburs,
			cep.km_ext_prel+cep.km_ext_livr as km, cep.valoare_totala,
			ep.operatiune, ep.primitor, ep.data_op, ep.detalii_doc, ep.observatii,
			sc.sc_data, sc.sc_centru, sc.sc_ckp
			FROM client_expeditii as cep
			LEFT JOIN exp_prelucrate as ep ON (cep.expeditie = ep.expeditie and ep.anulata = 0)
			LEFT JOIN clienti cle ON cep.expeditor = cle.cod_cl
			LEFT JOIN localitati lce on lce.cod_lc = cle.cod_lc
			LEFT JOIN clienti cld ON cep.destinatar_cod_cl = cld.cod_cl
			LEFT JOIN localitati lcd on lcd.cod_lc = cld.cod_lc
			LEFT JOIN (
				select i.expeditie , i.data as sc_data, ces.nume as sc_centru, cks.denumire as sc_ckp
				from scanari_coduri i
				LEFT JOIN centre as ces ON i.centru = ces.id
				LEFT JOIN checkpoints as cks ON cks.id = i.tip
				where i.data in
					(select max(j.data) from scanari_coduri j 
					where j.expeditie = i.expeditie and j.is_awb = 1 and j.tip in (select id from checkpoints where activ = 1 and is_public = 1 ))
			) as sc on sc.expeditie = ep.expeditie
			WHERE cep.expeditie in {$exps}
			GROUP BY cep.expeditie
			ORDER BY cep.data_expeditie desc";
		//print_r($query);die;
        $sql = $this->db->QFetchRowArray($query);
        //compun raspunsul
        if (!empty($sql)) {
            foreach ($sql as $key => $row) {

                $row['data_expeditie'] = $this->CreateDate($row['data_expeditie']);
                $row['data_op'] = $this->CreateDate($row['data_op']);
                $row['tip_obj'] = match($row['tip_obj']) {
					1 => 'Plic',
					2 => 'Colet',
					3 => 'Palet',
					default => ''
				};
                if(!empty($row['ramburs'])) $row['exp_ramburs'] = 'DA'; else $row['exp_ramburs'] = 'NU';
				if(!empty($row['ret_nt'])) $row['ret_nt'] = 'DA'; else $row['ret_nt'] = 'NU';
				if(!empty($row['ret_doc'])) $row['ret_doc'] = 'DA'; else $row['ret_doc'] = 'NU';
				if(!empty($row['copen'])) $row['copen'] = 'DA'; else $row['copen'] = 'NU';


				$arr[($key+1)][1] =  $row['expeditie'];
				$arr[($key+1)][2] =  $row['data_expeditie'];
				$arr[($key+1)][3] =  $row['expeditor_nume'];
				$arr[($key+1)][4] =  $row['expeditor_localitate'];
				$arr[($key+1)][5] =  $row['destinatar_nume'];
				$arr[($key+1)][6] =  $row['destinatar_localitate'];
				$arr[($key+1)][7] =  $row['tip_obj'];
				$arr[($key+1)][8] =  $row['piese'];
				$arr[($key+1)][9] =  $row['greutate'];
				$arr[($key+1)][10] =  $row['ret_nt'];
				$arr[($key+1)][11] =  $row['ret_doc'];
				$arr[($key+1)][12] =  $row['copen'];
				$arr[($key+1)][13] =  $row['exp_ramburs'];
				$arr[($key+1)][14] =  $row['ramburs'];
				if($preturi)
					$arr[($key+1)][15] =  $row['valoare_totala'];
				$arr[($key+1)][16] =  $row['detalii_doc'];
				$arr[($key+1)][17] =  $row['observatii'];
				$arr[($key+1)][18] =  $row['operatiune'];
				$arr[($key+1)][19] =  $row['data_op'];
				$arr[($key+1)][20] =  $row['primitor'];
				$arr[($key+1)][21] =  $row['sc_ckp'];
				$arr[($key+1)][22] =  $row['sc_centru'];
            }
        }
    	$this->download_send_headers("data_export_" . date("Y-m-d") . ".csv");
		echo $this->array2csv($arr); die;
    }

	function ClientExportRetururiCsv($exps) {
		$arr=[];
		$arr[0][1] = 'Tip retur';
		$arr[0][2] = 'Awb retur';
		$arr[0][3] = 'Initiala';
		$arr[0][4] = 'Expeditor';
		$arr[0][5] = 'Localitate';
		$arr[0][6] = 'Destinatar';
		$arr[0][7] = 'Localitate';
		$arr[0][8] = 'Tip';
		$arr[0][9] = 'Nr. piese';
		$arr[0][10] = 'Greutate';
		$arr[0][11] = 'Data col.';
		$arr[0][12] = 'Status';
		$arr[0][13] = 'Data Status';
		$arr[0][14] = 'Primitor';
		$arr[0][15] = 'Checkpoint';
		$arr[0][16] = 'Data checkpoint';
		$arr[0][17] = 'Centru checkpoint';

		$query = "SELECT epr.tip_exp, epr.expeditie, epr.data_expeditie, epr.referire,
			cle.nume as expeditor, cld.nume as destinatar, 
			lce.nume_lc as expeditor_localitate,lcd.nume_lc as destinatar_localitate,
			epr.tip_obj, epr.piese, epr.greutate,
			epr.operatiune, epr.primitor, epr.data_op, ec.folder,
			sc.sc_data, sc.sc_centru, sc.sc_ckp
            FROM exp_prelucrate epr
			INNER JOIN exp_prelucrate ep on ep.expeditie = epr.referire and epr.anulata = 0
			INNER JOIN client_expeditii as cep ON cep.expeditie = ep.expeditie
			left join clienti cle on cle.cod_cl = epr.expeditor_id
			left join clienti cld on cld.cod_cl = epr.destinatar_id
			left join localitati lce ON lce.cod_lc = cle.cod_lc
			left join localitati lcd ON lcd.cod_lc = cld.cod_lc
			LEFT JOIN exp_confirmari ec on epr.expeditie = ec.expeditie
			LEFT JOIN (
				select i.expeditie, i.data as sc_data, ces.nume as sc_centru, cks.denumire as sc_ckp
				from scanari_coduri i
				LEFT JOIN centre as ces ON i.centru = ces.id
				LEFT JOIN checkpoints as cks ON cks.id = i.tip
				where i.data in
					(select max(j.data) from scanari_coduri j
					where j.expeditie = i.expeditie and j.is_awb = 1 and j.tip in (select id from checkpoints where activ = 1 and is_public = 1 ))
			) as sc on sc.expeditie = epr.expeditie
			WHERE epr.expeditie in {$exps} and epr.tip_exp NOT IN (0,3) and epr.anulata = 0
			GROUP BY epr.expeditie
			ORDER BY epr.data_expeditie desc";
		//print_r($query);die;
        $sql = $this->db->QFetchRowArray($query);
        //compun raspunsul
        if (!empty($sql)) {
            foreach ($sql as $key => $row) {

                $row['data_expeditie'] = $this->CreateDate($row['data_expeditie']);
                $row['data_op'] = $this->CreateDate($row['data_op']);
                $row['tip_exp'] = ExpeditieDto::TIP_EXP[$row['tip_exp']] ?? "unknown";
				if($row['tip_obj'] == 1) {
					$row['tip_obj'] = 'Plic';
					$row['greutate'] = 0.500;
				}
				else if($row['tip_obj'] == 2) {
					$row['tip_obj'] = 'Colet';
				}
				else if($row['tip_obj'] == 3) {
					$row['tip_obj'] = 'Palet';
				}

				$arr[($key+1)][1] =  $row['tip_exp'];
				$arr[($key+1)][2] =  $row['expeditie'];
				$arr[($key+1)][3] =  $row['referire'];
				$arr[($key+1)][4] =  $row['expeditor'];
				$arr[($key+1)][5] =  $row['expeditor_localitate'];
				$arr[($key+1)][6] =  $row['destinatar'];
				$arr[($key+1)][7] =  $row['destinatar_localitate'];
				$arr[($key+1)][8] =  $row['tip_obj'];
				$arr[($key+1)][9] =  $row['piese'];
				$arr[($key+1)][10] =  $row['greutate'];
				$arr[($key+1)][11] =  $row['data_expeditie'];
				$arr[($key+1)][12] =  $row['operatiune'];
				$arr[($key+1)][13] =  $row['data_op'];
				$arr[($key+1)][14] =  $row['primitor'];
				$arr[($key+1)][15] =  $row['sc_ckp'];
				$arr[($key+1)][16] =  $row['sc_data'];
				$arr[($key+1)][17] =  $row['sc_centru'];
				
			}
		}
		$this->download_send_headers("export_retururi_" . date("Y-m-d") . ".csv");
		echo $this->array2csv($arr); die;
    }

	function PrintBorderouTCPDF(){
		require_once 'BorderouPdf.php';

		$id = intval($_POST['borderou_id'] ?? 0);
		$sidx = $this->sanitize($_POST['sidx'] ?? "e.expeditie");
		$sord = $this->sanitize($_POST['sord'] ?? "ASC");
		if($id == 0) return 'Borderou Invalid';

		$query = "SELECT e.platitor,
		cld.nume as destinatar_nume, cld.adresa as destinatar_adresa, lcd.nume_lc as destinatar_localitate,
		e.expeditie,e.greutate,e.ramburs,e.tip_plata,e.tip_obj,e.piese,
		b.borderou_id, b.data, e.detalii_doc, e.observatii, (e.km_ext_prel+e.km_ext_livr) as km
		FROM client_expeditii as e
		LEFT JOIN client_borderouri as b ON e.borderou_id = b.id
		left join clienti cld on e.destinatar_cod_cl = cld.cod_cl
		left join localitati lcd on lcd.cod_lc = cld.cod_lc
		WHERE e.borderou_id = ".$id." and e.anulata = 0 ORDER BY " . $sidx . " " . $sord;
        $sql = $this->db->QFetchRowArray($query);
		if(empty($sql) || !is_array($sql)) return 'Borderou Invalid';

		$vars = [];
		if(empty($this->expeditor_id)) return 'Utilizator Invalid';

		$vars['expeditor_nume'] = $this->expeditor_nume;
		$vars['expeditor_id'] = $this->expeditor_id;
		$vars['expeditor_localitate'] = $this->expeditor_localitate;
		$vars['expeditor_adresa'] = $this->expeditor_adresa;
		$vars['expeditor_contact'] = $this->expeditor_contact;
		$vars['expeditor_telefon'] = $this->expeditor_telefon;
		$vars['borderou_id'] = $id;

		$total_piese = 0;
		$total_plicuri = 0;
		$total_greutate = 0;
		$total_ramburs = 0;
		$total_expeditii = 0;

		$filename = 'Borderou-'.$vars['borderou_id'].'.pdf';

		$pdf = new BorderouPdf($vars);
		// add a page
		$pdf->AddPage();
		$pdf->makeTH();
		foreach($sql as $key=>$row)
		{
			$tip_plata='';
			if($row['ramburs'] > 0)
			{
				$tip_plata = 'cash plic';
				if($row['tip_plata'] == 1) $tip_plata = 'bo';
				else if($row['tip_plata'] == 2) $tip_plata = 'cec';
				else if($row['tip_plata'] == 3) $tip_plata = 'cash CC';
			}
			$row['destinatar_nume'] = strtoupper(htmlspecialchars_decode(strtolower($row['destinatar_nume']), ENT_QUOTES));

			$pdf->makeTR($key+1, $row['destinatar_nume'], $row['destinatar_localitate'], $row['expeditie'], $row['piese'], $row['greutate'], $row['ramburs'], $tip_plata, (($row['platitor'] == 1)?'E':'D'), $row['detalii_doc']);
			$total_piese+=intval($row['piese']);
			$total_greutate += $row['greutate'];
			$total_ramburs += $row['ramburs'];
			if($row['tip_obj'] == 1) $total_plicuri+=intval($row['piese']);
			$total_expeditii++;
		}

		$pdf->setPage(1);
		$pdf->makeHeader($id, $total_expeditii, $total_piese, $total_plicuri, round($total_greutate,2), round($total_ramburs,2), $row['data']);
		// move pointer to last page
		$pdf->lastPage();

		//I: send the file inline to the browser. The plug-in is used if available. The name given by filename is used when one selects the "Save as" option on the link generating the PDF.
		//D: send to the browser and force a file download with the name given by filename.
		$type = 'I';
		if($this->print_awb == 1) $type = 'D';

		$pdf->Output($filename,$type);
	    exit;

	}

	 function ListareClienti() {
        $this->vars['title_page'] = 'Clienti';
        return $this->Parse($this->page_prefix . 'listare_clienti.html');
    }

    function ListareClienti_JSON() {
		$responce = new StdClass();
		$master_id = $this->show_master_clienti && $this->is_pc ? $this->master_id : -1;

        $cond = '1=1';
        $vars=[];
        $vars=$_REQUEST;

		$page = intval($_REQUEST['page'] ?? 0);
		$limit = intval($_REQUEST['rows'] ?? 20);
		$sidx = trim($this->sanitize($_REQUEST['sidx'] ?? 1));
		$sord = trim($this->sanitize($_REQUEST['sord'] ?? 'asc'));

        //start generare conditie
        $searchOn = $this->Strip($vars['_search']);
        if ($searchOn == 'true') {
            $searchstr = $this->Strip($vars['filters']);
            $cond .= $this->constructWhere($searchstr);
        }

        $query="SELECT COUNT(cld.id) as nr
            FROM client_destinatari cld
            LEFT JOIN localitati lcd ON cld.id_loc = lcd.cod_lc
            WHERE {$cond} AND cld.id_exp in ({$this->expeditor_id}, {$master_id}) AND cld.activ=1";
			//echo $query;die;
        $result = $this->db->QFetchArray($query);
        $count = !empty($result['nr']) ? $result['nr'] : 0;

        if( $count >0 ) {
            $total_pages = ceil($count/$limit);
        } else {
            $total_pages = 0;
        }
        if ($page > $total_pages) $page=$total_pages;
        $start = $limit*$page - $limit; // do not put $limit*($page - 1)
        if ($start<0) $start = 0;

        $query="SELECT cld.id, cld.id_exp, cld.nume, cld.adresa, cld.contact, cld.telefon, lcd.nume_lc
                FROM client_destinatari cld
                LEFT JOIN localitati lcd ON cld.id_loc = lcd.cod_lc
                WHERE {$cond} AND cld.id_exp in ({$this->expeditor_id}, {$master_id}) AND cld.activ=1
                ORDER BY " . $sidx . " " . $sord . " LIMIT " . $start . " , " . $limit;

        $sql = $this->db->QFetchRowArray($query);
        if (!empty($sql)) {
            foreach ($sql as $key => $row) {

            	$row['optiuni'] = '<a href="javascript:;" onclick="ClientEditareClient('.$row['id'].');" style="text-decoration:none;">Editeaza</a>&nbsp;&nbsp;';
				$row['optiuni'] .= '<a href="javascript:;" onclick="ClientDeleteClient('.$row['id'].');" style="text-decoration:none;">Sterge</a>';

				if($row['id_exp'] != $this->expeditor_id)
					$row['optiuni'] = '';
                $responce->rows[$key]['id']=$row['id'];
                $responce->rows[$key]['cell'] = array(
                                                        $row['nume'],
                                                        $row['nume_lc'],
                                                        $row['adresa'],
														$row['contact'],
														$row['telefon'],
                                                        $row['optiuni']
                                                    );
            }
        }
        $responce->page = $page;
        $responce->total = $total_pages;
        $responce->records = $count;
        return json_encode($responce);
    }


	function AfisareClient($id){
		$master_id = $this->show_master_clienti && $this->is_pc ? $this->master_id : -1;

		$query="SELECT cld.id, cld.nume, cld.id_loc, lcd.nume_lc as nume_lc, cld.adresa, cld.contact as contact, cld.telefon as telefon
                FROM client_destinatari cld
				LEFT JOIN localitati lcd ON cld.id_loc = lcd.cod_lc
                WHERE cld.id = {$id} AND cld.activ = 1 AND cld.id_exp in ({$this->expeditor_id}, {$master_id}) limit 1";
		$sql = $this->db->QFetchArray($query);
		if(!empty($sql) && is_array($sql))
			return implode('|||', $sql);
		return "";
	}

	function EditareClient(){
        $post = [];

		if(empty($_POST['data'])) {
			return '0|||Error : Empty data!';
		}
		else {
			if(is_array($_POST['data'])){
				foreach($_POST['data'] as $key=>$field){
					if(is_array($field) && isset($field['name']) && isset($field['value']))
						$post[$field['name']] = $this->sanitize($field['value']);
				}
			}
		}

		$post['destinatar_nume'] = (!empty(Backend::sSanitizeCleanEdges($post['destinatar_nume'] ?? "")) ? strtoupper(Backend::sSanitizeCleanEdges($post['destinatar_nume'])):"");
		$post['destinatar_adresa'] = (!empty(Backend::sSanitizeCleanEdges($post['destinatar_adresa'] ?? "")) ? strtoupper(Backend::sSanitizeCleanEdges($post['destinatar_adresa'])):"");
		$post['destinatar_contact'] = (!empty(Backend::sSanitizeCleanEdges($post['destinatar_contact'] ?? "")) ? strtoupper(Backend::sSanitizeCleanEdges($post['destinatar_contact'])):"");

		$destinatar_id = intval($post['destinatar_id'] ?? 0);
		if(empty($destinatar_id)) return "0|||Client gresit";
		if(empty($post['destinatar_nume'])) return "0|||Introdu nume client";
		$destinatar_localitate_id = intval($post['destinatar_localitate_id'] ?? 0);
		if(empty($destinatar_localitate_id)) return "0|||Alege localitatea din lista";
		if(strlen($post['destinatar_adresa']) < 4) return "0|||Adresa este obligatorie";

		
        $vu=[];
        $vu['cod_cl'] = 0;
        $vu['nume'] = $post['destinatar_nume'];
        $vu['id_loc'] = $destinatar_localitate_id;
        $vu['adresa'] = $post['destinatar_adresa'];
		$vu['contact'] = $post['destinatar_contact'];
		$vu['telefon'] = strtoupper($post['destinatar_telefon']);
		$vu['updated_at'] = date('Y-m-d H:i:s');
		$vu['updated_by'] = $this->user_id;
		$this->db->QueryUpdate($this->tables['client_destinatari'], $vu, "id = {$destinatar_id}");

		return 1;
    }

	function AdaugareClient(){
        $post = [];

		if(empty($_POST['data']) || !is_array($_POST['data'])) {
			return '0|||Error : Empty data!';
		}
		foreach($_POST['data'] as $key=>$field){
			if(is_array($field) && isset($field['name']) && isset($field['value']))
				$post[$field['name']] = $this->sanitize($field['value']);
		}

		$post['destinatar_nume'] = (!empty(Backend::sSanitizeCleanEdges($post['destinatar_nume'] ?? "")) ? strtoupper(Backend::sSanitizeCleanEdges($post['destinatar_nume'])):"");
		$post['destinatar_adresa'] = (!empty(Backend::sSanitizeCleanEdges($post['destinatar_adresa'] ?? "")) ? strtoupper(Backend::sSanitizeCleanEdges($post['destinatar_adresa'])):"");
		$post['destinatar_contact'] = (!empty(Backend::sSanitizeCleanEdges($post['destinatar_contact'] ?? "")) ? strtoupper(Backend::sSanitizeCleanEdges($post['destinatar_contact'])):"");

		$destinatar_localitate_id = intval($post['destinatar_localitate_id'] ?? 0);
		if(empty($destinatar_localitate_id)) return "0|||Alege localitatea din lista";
		if(empty($post['destinatar_nume'])) return "0|||Introdu nume client";
		if(strlen($post['destinatar_adresa']) < 4) return "0|||Adresa este obligatorie";

        $vi=[];
        $vi['cod_cl'] = 0;
        $vi['nume'] = $post['destinatar_nume'];
        $vi['id_loc'] = $destinatar_localitate_id;
        $vi['adresa'] = $post['destinatar_adresa'];
		$vi['activ'] = 1;
		$vi['id_exp'] = $this->expeditor_id;
		$vi['contact'] = $post['destinatar_contact'];
		$vi['telefon'] = $post['destinatar_telefon'];

		$query="SELECT dist_km FROM localitati WHERE cod_lc = {$vi['id_loc']}";
        $sql = $this->db->QFetchArray($query);
		$vi['km_ext'] = $sql['dist_km'] ?? 0;

		$vi['created_at'] = date('Y-m-d H:i:s');
		$vi['created_by'] = $this->user_id;
        $this->db->QueryInsert($this->tables['client_destinatari'], $vi);

		return 1;
    }

	function StergereClient($id){
		$id = intval($id);
		if($id > 0)
			$this->db->Query("DELETE FROM client_destinatari WHERE id = ".intval($id));
		return true;
	}

	 // import expeditii
	function ImportExpeditiiStep1() {
        $this->vars['title_page'] = 'Import Expeditii';
        $vars = [];
		$vars['error'] = (isset($this->vars['error']))?'<span style="color:red">'.$this->vars['error'].'</span>':'';
		$this->vars['error'] = '';
        return $this->Parse($this->page_prefix . 'importuri_expeditii.html', $vars);
    }

    function ImportExpeditiiStep2($error=2, $tip_import=1){
        $this->vars['title_page'] = 'Import Expeditii';
        $vars = [];
		$vars['error'] = (isset($this->vars['error']))?$this->vars['error']:'';

		require_once('upload.php');
		if (!empty($_FILES['fisier']['tmp_name'])){
			$upload = new Upload();
			$upload->allowed_file_types = 'csv';
			$this->vars['error'] = $upload->UploadFile('fisier', $this->paths['upload'], 1);
			if (!empty($this->vars['error']))	return $this->ImportExpeditiiStep1();
			$this->vars['error'] = '';
			$vars['fisier'] = $upload->file['server_file_name'];
		}
		else if(!empty($_POST['fisier'])){
			$vars['fisier'] = $_POST['fisier'];
		}
		else {
			$this->vars['error'] = 'Selectati fisierul';
			return $this->ImportExpeditiiStep1();
		}

		$vars['separator'] = ';';
		if(!empty($_POST['separator'])) $vars['separator'] = $_POST['separator'];

		$vars['continut'] = $this->ImportCSV($vars['fisier'],$vars['separator'],1);
		if (!empty($this->vars['error']) && $error==2)
		{
			$this->vars['error'] = '<table border="1" cellpadding="4" cellspacing="0">' . $this->vars['error'];
			$this->vars['error'] .='</table>';
			return $this->ImportExpeditiiStep1();
		}

		$vars['error'] =  '<span style="color:red">'.$this->vars['error'].'</span>';
		$this->vars['error'] = '';
        return $this->Parse($this->page_prefix . 'importuri_expeditii_step2.html', $vars);
    }

    function ImportExpeditiiStep31(){
		$not_import = [];
        $vars = [];
        $this->vars['error'] = '';
        $campuri_import = $this->CampuriImport();
        $campuri = [];
        foreach($campuri_import as $camp => $val)
		{
			$campuri[] = $camp;
		}

        $separator = ';';
		if(!empty($_POST['separator'])) $separator = $_POST['separator'];

		require_once('upload.php');
		if (!empty($_FILES['fisier']['tmp_name'])){
			$upload = new Upload();
			$upload->allowed_file_types = 'csv';
			$this->vars['error'] = $upload->UploadFile('fisier', $this->paths['upload'], 1);
			if (!empty($this->vars['error']))	return $this->ImportExpeditiiStep1();
			else $this->vars['error'] = '';
			$file = $upload->file['server_file_name'];
			$result = $this->ImportCSVSave($file,$separator,$campuri,1);
			$vars['nr_expeditii'] = count($result);
			$vars['expeditii'] = json_encode($result);
			$vars['continut'] = '<table border="1" cellpadding="4" cellspacing="0">';
			$top='';
			$fields =  $this->CampuriImport(4);
			$required_fields =  $this->CampuriImport(3);
			foreach($campuri as $camp => $val)
			{
				$top .= '<th style="font-size:0.7em">'.$this->getCampImport($campuri_import, $val).'</th>';
			}
			$vars['continut'] .= '<thead><tr>'.$top.'</tr></thead><tbody>';
			$exp_line = 1;
			foreach($result as $row_data)
			{
				$errs = $this->verifyRequiredParams($row_data, $required_fields, $fields);
				if(is_array($errs))
				{
					$not_import[]= $exp_line;
					foreach($errs as $err) $vars['continut'] .= '<tr><td colspan="'.count($row_data).'"><span style="color:red">'.$err.'</span></td></tr>';
				}
				$exp_line++;
				$vars['continut'] .= '<tr>';
				foreach($row_data as $cell) $vars['continut'].='<td>'.$cell.'</td>';
				$vars['continut'] .= '</tr>';
			}
			$vars['continut'] .= '</tbody></table>';
			if(count($not_import) > 0)
			{
				$vars['continut'] = '<span style="color:red">Urmatoarele linii au erori si nu vor fi importate : '.implode(', ', $not_import).'</span><br/>'.$vars['continut'];
				$vars['not_import'] = json_encode($not_import);
			}
		}
		else {
			$this->vars['error'] = 'Selectati fisierul';
			return $this->ImportExpeditiiStep1();
		}

        return $this->Parse($this->page_prefix . 'importuri_expeditii_step3.html', $vars);
    }

	//procesare import
	function ImportExpeditiiStep3(){
		$not_import = [];
        $vars = [];
		$campuri = [];
		foreach($_POST as $post => $val){
			if(substr($post,0,6) == 'field_' && $val != 'nok'){
				$campuri[substr($post,6)] = $val;
			}
		}

		//eroare ... camp selectat de doua ori
		if(count(array_unique($campuri)) < count($campuri))
		{
			$this->vars['error'] = 'Ati selectat aceeasi coloana de doua ori';
			return $this->ImportExpeditiiStep2(3);
		}
		//eroare ... campuri obligatorii : destinatar, adresa, localitate, judet, tip_obj ...
		if(!in_array('destinatar', $campuri) || !in_array('destinatar_localitate', $campuri) || !in_array('destinatar_adresa', $campuri) || !in_array('destinatar_judet', $campuri))
		{
			$this->vars['error'] = 'Urmatoarele coloane sunt obligatorii : DESTINATAR, JUDET, LOCALITATE, ADRESA';
			return $this->ImportExpeditiiStep2(3);
		}

		$separator = ';';
		if(!empty($_POST['separator'])) $separator = $_POST['separator'];


		if(isset($_POST['import']) && !empty($_POST['fisier'])){
			$file = $_POST['fisier'];
			$result = $this->ImportCSVSave($file,$separator,$campuri,1);
			$vars['nr_expeditii'] = count($result);
			$vars['expeditii'] = json_encode($result);
			$vars['continut'] = '<table border="1" cellpadding="4" cellspacing="0">';
			$top='';
			$campuri_import = $this->CampuriImport();
			$fields =  $this->CampuriImport(4);
			$required_fields =  $this->CampuriImport(3);
			foreach($campuri as $camp => $val)
			{
				$top .= '<th style="font-size:0.7em">'.$this->getCampImport($campuri_import, $val).'</th>';
			}
			$vars['continut'] .= '<thead><tr>'.$top.'</tr></thead><tbody>';
			$exp_line = 1;
			foreach($result as $row_data)
			{
				$errs = $this->verifyRequiredParams($row_data, $required_fields, $fields);
				if(is_array($errs))
				{
					$not_import[]= $exp_line;
					foreach($errs as $err) $vars['continut'] .= '<tr><td colspan="'.count($row_data).'"><span style="color:red">'.$err.'</span></td></tr>';
				}
				$exp_line++;
				$vars['continut'] .= '<tr>';
				foreach($row_data as $cell) $vars['continut'].='<td>'.$cell.'</td>';
				$vars['continut'] .= '</tr>';
			}
			$vars['continut'] .= '</tbody></table>';
			if(count($not_import) > 0)
			{
				$vars['continut'] = '<span style="color:red">Urmatoarele linii au erori si nu vor fi importate : '.implode(', ', $not_import).'</span><br/>'.$vars['continut'];
				$vars['not_import'] = json_encode($not_import);
			}
		}
		else
		{
			$this->vars['error'] = 'Fisier lipsa ...';
			return $this->ImportExpeditiiStep1();
		}


        return $this->Parse($this->page_prefix . 'importuri_expeditii_step3.html', $vars);
    }

   function ImportExpeditiiStep4(){
   		//echo '<div style="color:red">importul nu merge inca</div>'; die;
    	if(!isset($_POST['sel']) || (isset($_POST['sel']) && !is_numeric($_POST['sel']))) { echo '<div style="color:red">error row number'.'</div>'; die; }
    	$row = $_POST['sel']-1;

    	$not_import = [];
    	if(isset($_POST['not_import']) && strlen($_POST['not_import']) > 0) $not_import = json_decode($_POST['not_import'], true);

		if(in_array($_POST['sel'],$not_import)) {  echo '<div style="color:red">Expeditie cu erori la linia '.$_POST['sel'].' : trebuie introdusa manual</div>';  die; }

    	$expeditii = json_decode($_POST['expeditii'], true);
    	switch (json_last_error()) {
        	case JSON_ERROR_DEPTH:
            	echo '<div style="color:red"> - JSON_ERROR_DEPTH</div>'; die;
        		break;
        	case JSON_ERROR_STATE_MISMATCH:
            	echo '<div style="color:red"> - JSON_ERROR_STATE_MISMATCH</div>'; die;
        		break;
        	case JSON_ERROR_CTRL_CHAR:
            	echo '<div style="color:red"> - JSON_ERROR_CTRL_CHAR</div>'; die;
        		break;
        	case JSON_ERROR_SYNTAX:
            	echo '<div style="color:red"> - JSON_ERROR_SYNTAX</div>'; die;
        		break;
        	case JSON_ERROR_UTF8:
            	echo '<div style="color:red"> - JSON_ERROR_UTF8</div>'; die;
        		break;
        	default:
            	//echo '<div style="color:red"> - Erreur inconnue</div>';
        		break;
		}

		$master_id = $this->show_master_clienti && $this->is_pc ? $this->master_id : -1;

    	$exp_row = $expeditii[$row];
    	$exp_row = array_map(array($this, 'sanitize'), $exp_row);

		$exp_row['destinatar'] = (!empty(Backend::sSanitizeCleanEdges($exp_row['destinatar'] ?? "")) ? strtoupper(Backend::sSanitizeCleanEdges($exp_row['destinatar'])):"");
		$exp_row['destinatar_judet'] = (!empty(Backend::sSanitizeCleanEdges($exp_row['destinatar_judet'] ?? "")) ? strtoupper(Backend::sSanitizeCleanEdges($exp_row['destinatar_judet'])):"");
		$exp_row['destinatar_localitate'] = (!empty(Backend::sSanitizeCleanEdges($exp_row['destinatar_localitate'] ?? "")) ? strtoupper(Backend::sSanitizeCleanEdges($exp_row['destinatar_localitate'])):"");
		$exp_row['destinatar_adresa'] = (!empty(Backend::sSanitizeCleanEdges($exp_row['destinatar_adresa'] ?? "")) ? strtoupper(Backend::sSanitizeCleanEdges($exp_row['destinatar_adresa'])):"");
		$exp_row['destinatar_contact'] = (!empty(Backend::sSanitizeCleanEdges($exp_row['destinatar_contact'] ?? "")) ? strtoupper(Backend::sSanitizeCleanEdges($exp_row['destinatar_contact'])):"");


		//exceptii localitate, judet
		$test_destinatar_localitate = $this->testLocalitate($exp_row['destinatar_localitate']);
		if($test_destinatar_localitate == 'Bucuresti')
		{
			$query="select cod_lc, nume_lc, dist_km, cod_centru from localitati
			where nume_lc like :test_destinatar_localitate and cod_jd like 'B' limit 1";
    		$sql = $this->db->QFetchRowAssoc($query, ['test_destinatar_localitate' => $test_destinatar_localitate]);
			if(empty($sql)) { echo '<div style="color:red">Linia '.$row.' : Localitate eronata : '.$exp_row['destinatar_localitate'].'</div>';  die; }
			else
			{
				$destinatar_localitate = $sql['nume_lc'];
				$destinatar_localitate_id = $sql['cod_lc'];
				$destinatar_cod_centru = $sql['cod_centru'];
				$km_ext_livr = $sql['dist_km'];
			}
		}
		else
		{
			//cauta judetul
			$query="select cod_jd from judete where replace(replace(nume_jd,' ',''),'-','') like replace(replace(:destinatar_judet,' ',''),'-','') limit 1";
    		$sql = $this->db->QFetchRowAssoc($query, ['destinatar_judet' => $exp_row['destinatar_judet']]);
			if(empty($sql)) {  echo '<div style="color:red">Linia '.$row.'  : Judet inexistent : '.$exp_row['destinatar_judet'].'</div>';  die; }
			else $judet = $sql['cod_jd'];
			//cauta localitatea
			$query="select cod_lc, nume_lc, dist_km, cod_centru from localitati where replace(replace(nume_lc,' ',''),'-','') like replace(replace(:destinatar_localitate,' ',''),'-','')  and cod_jd like :judet limit 1";
    		$sql = $this->db->QFetchRowAssoc($query, ['destinatar_localitate' => $exp_row['destinatar_localitate'], 'judet' => $judet]);
			if(empty($sql)) {  echo '<div style="color:red">Linia '.$row.' : Localitate eronata : '.$exp_row['destinatar_localitate'].'</div>';  die; }
			else
			{
				$destinatar_localitate = $sql['nume_lc'];
				$destinatar_localitate_id = $sql['cod_lc'];
				$destinatar_cod_centru = $sql['cod_centru'];
				$km_ext_livr = $sql['dist_km'];
			}
		}

		$vi = [];
		$vi['data_expeditie'] =  date("Y-m-d");
		$vi['expeditie'] = $this->GenerareNrExpeditie();
		$vi['expeditor'] = $this->expeditor_id;
		$vi['expeditor_contact'] = $this->expeditor_contact;
		$vi['expeditor_telefon'] = $this->expeditor_telefon;
		$vi['platitor'] = 1;
		if(isset($exp_row['platitor']) && strtoupper($exp_row['platitor']) == 'DESTINATAR') $vi['platitor'] = 2;
		$vi['destinatar_localitate'] = $destinatar_localitate;
		$vi['destinatar_localitate_id'] = $destinatar_localitate_id;
		$vi['km_ext_livr'] = (float)$km_ext_livr;
		$vi['km_ext_prel'] = $this->expeditor_localitate_km;

		$vi['destinatar_contact'] = '';
		if(!empty($exp_row['destinatar_contact']))
			$vi['destinatar_contact'] = strtoupper($exp_row['destinatar_contact']);

		$vi['destinatar_telefon'] = '';
		if(!empty($exp_row['destinatar_contact_telefon']) && !empty($exp_row['destinatar_contact_mobil']))
			$vi['destinatar_telefon'] = strtoupper($exp_row['destinatar_contact_mobil']);
		else if(!empty($exp_row['destinatar_contact_telefon']))
			$vi['destinatar_telefon'] = strtoupper($exp_row['destinatar_contact_telefon']);
		else if(!empty($exp_row['destinatar_contact_mobil']))
			$vi['destinatar_telefon'] = strtoupper($exp_row['destinatar_contact_mobil']);

		//cauta destinatarul in tabla clienti
		$query = "select cod_cl from clienti where nume like :destinatar
			and adresa like :destinatar_adresa and cod_lc = ".$vi['destinatar_localitate_id']." limit 1";
	    $sql = $this->db->QFetchRowAssoc($query, ['destinatar' => $exp_row['destinatar'], 'destinatar_adresa' => $exp_row['destinatar_adresa']]);
	    if (empty($sql)) {//in cazul in care nu exista clientul => insert
			//detalii
			$var = [];
		    $var['nume'] = strtoupper($exp_row['destinatar']);
		    $var['cod_lc'] = $vi['destinatar_localitate_id'];
		    $var['adresa'] = strtoupper($exp_row['destinatar_adresa']);
		    $var['operator'] = $this->user_id;
		    $var['activ'] = 1;
		    $var['data_op'] = date('Y-m-d H:i:s');
		    $var['tarif'] = 0;
		    $var['mod_plata'] = 0;
		    $var['km_ext'] = $km_ext_livr;
			$var['contact'] = strtoupper($vi['destinatar_contact']);
			$var['telefon'] = strtoupper($vi['destinatar_telefon']);

			$var['created_at'] = date('Y-m-d H:i:s');
			$var['created_by'] = $this->user_id;
		    $vi['destinatar_id']=$this->db->QueryInsert($this->tables['clienti'], $var);
			CdsGeocoder::geocode($this->db, $vi['destinatar_id']);
		    unset($var);
		}
		else {
			$vi['destinatar_id'] = $sql['cod_cl'];
			CdsGeocoder::geocode($this->db, $vi['destinatar_id'], true);
		}

		$vi['destinatar'] = strtoupper($exp_row['destinatar']);
		$vi['destinatar_adresa'] = strtoupper($exp_row['destinatar_adresa']);

		//cauta destinatarul in tabla client_destinatari
	    $qc = "SELECT cod_cl, id, nume, activ FROM client_destinatari
	    		WHERE nume like :destinatar and adresa like :destinatar_adresa
	    		and id_loc = ".$vi['destinatar_localitate_id']." and id_exp in (".$vi['expeditor'].",".$master_id.") limit 1";
	    $sc = $this->db->QFetchRowAssoc($qc, ['destinatar' => $vi['destinatar'], 'destinatar_adresa' => $vi['destinatar_adresa']]);
	    if(empty($sc)) //in cazul in care nu exista linkul client:destinatar => insert
		{
			$var = [];
			$var['id_exp'] = $vi['expeditor'];
			$var['nume'] = strtoupper($vi['destinatar']);
			$var['id_loc'] = $vi['destinatar_localitate_id'];
			$var['km_ext'] = $km_ext_livr;
			$var['adresa'] = strtoupper($vi['destinatar_adresa']);
			$var['activ'] = 1;
			$var['cod_cl'] = $vi['destinatar_id'];
			$var['observatii'] = '';
			$var['contact'] = $vi['destinatar_contact'];
			$var['telefon'] = $vi['destinatar_telefon'];

			$var['created_at'] = date('Y-m-d H:i:s');
			$var['created_by'] = $this->user_id;
			$cl_dest_id = $this->db->QueryInsert($this->tables['client_destinatari'], $var);

		}
		else
		{
			$cl_dest_id = $sc['id'];
			$this->db->QueryUpdate($this->tables['client_destinatari'], ['contact' => $vi['destinatar_contact'], 'telefon' => $vi['destinatar_telefon']], "id=".$cl_dest_id);
			$var = [];
			if(empty($sc['cod_cl'])) $var['cod_cl'] = $vi['destinatar_id'];
			if(empty($sc['activ'])) $var['activ'] = 1;
			$var['updated_at'] = date('Y-m-d H:i:s');
			$var['updated_by'] = $this->user_id;
			if(empty($sc['cod_cl']) || empty($sc['activ']))
				$this->db->QueryUpdate($this->tables['client_destinatari'], $var, "id=".$cl_dest_id);

		}

		$vi['tip_obj'] = 2;//default colet
		if(isset($exp_row['tip_expeditie'])) {
			$vi['tip_obj'] = match(strtoupper($exp_row['tip_expeditie'])) {
				'PLIC' => 1,
				'COLET' => 2,
				'PALET' => 3,
				default => 2
			};
		}

		$vi['greutate'] = 0.500;
		$vi['piese'] = 1;
		if($vi['tip_obj'] == 2){
			$vi['greutate'] = round(floatval(trim($exp_row['greutate'])), 2);
			$vi['piese'] = intval($exp_row['piese']);
			if($vi['greutate'] < parent::MIN_KG_COLET) $vi['greutate'] = parent::MIN_KG_COLET;
		}
		else if($vi['tip_obj'] == 3){
			$vi['greutate'] = round(floatval(trim($exp_row['greutate'])), 2);
			$vi['piese']=1;
		}

		//retururi
		$vi['ret_nt']=0;
		if(isset($exp_row['ret_nt']) && strtoupper($exp_row['ret_nt'])=='DA')
			$vi['ret_nt']=1;

		$vi['ret_doc']=0;
		if(isset($exp_row['ret_doc']) && strtoupper($exp_row['ret_doc'])=='DA')
			$vi['ret_doc']=1;


		$vi['copen']=0;
		if(isset($exp_row['copen']) && strtoupper($exp_row['copen'])=='DA')
			$vi['copen']=1;

		$vi['sms']=0;
		if(isset($exp_row['sms']) && strtoupper($exp_row['sms'])=='DA')
			$vi['sms']= -1;

		$vi['ret_amb']=0;
		$vi['ret_colet']=0;

		//$exp_row['tip_livrare'] = 'LIVRARE(NORMALA/SAMBATA/SEDIU)';
		$vi['liv_sambata']=0;
		$vi['liv_sediu']=0;
		if(isset($exp_row['tip_livrare']))
		{
			$exp_row['tip_livrare'] = strtoupper($exp_row['tip_livrare']);
			if($exp_row['tip_livrare'] == 'LIVRARE SAMBATA' ||  $exp_row['tip_livrare'] == 'SAMBATA')
				$vi['liv_sambata']=1;

			if($exp_row['tip_livrare'] == 'LIVRARE SEDIU' ||  $exp_row['tip_livrare'] == 'SEDIU')
				$vi['liv_sediu']=1;

		}

		//asigurare
		$vi['asigurare'] = 0.00;
		if(isset($exp_row['asigurare']) && ((float)$exp_row['asigurare']) > 0 ){
			$vi['asigurare'] = (float)$exp_row['asigurare'];
		}

		//ramburs
		$vi['ramburs'] = 0.00;
		if(isset($exp_row['ramburs']) && ((float)$exp_row['ramburs']) > 0 ){
			$vi['ramburs'] = (float)$exp_row['ramburs'];
			if(isset($exp_row['tip_plata'])){
				if(strtoupper($exp_row['tip_plata'])=='BO')
					$vi['tip_plata'] = 1;
				else if(strtoupper($exp_row['tip_plata'])=='CEC')
					$vi['tip_plata'] = 2;
				else if(strtoupper($exp_row['tip_plata'])=='CONT')
					$vi['tip_plata'] = 3;
				else $vi['tip_plata'] = 0;
				//ramburs cont colector
				if( !empty($this->expeditor_cc) && $vi['tip_plata'] == 0){
					$vi['tip_plata'] = 3;
				}
			}
		}

		if(isset($exp_row['detalii_doc']))
			$vi['detalii_doc'] = $exp_row['detalii_doc'];
		if(isset($exp_row['observatii']))
			$vi['observatii'] = $exp_row['observatii'];

		$vi['tip_tarif'] = (($this->expeditor_localitate_id != $vi['destinatar_localitate_id']) ? 1 : 0);
		$valoare = $this->Get_ValoareExpeditieClient($vi);
		unset($vi['tip_tarif']);

		$vi['moneda'] = ExpeditieDto::MONEDA_REV[$valoare['moneda']] ?? 1;

		$vi['mod_plata'] = $valoare['mod_plata'];
		$vi['valoare_exp'] =$valoare['tExpeditie'];
		$vi['valoare_km'] = $valoare['tKm'];
		$vi['valoare_g'] = $valoare['tGreutate'];
		$vi['valoare_asig'] = $valoare['tAsigurare'];

		$vi['valoare_totala'] = $vi['valoare_exp'] + $vi['valoare_km'] + $vi['valoare_g'] + $vi['valoare_asig'];
		$vi['valoare_totala'] = round($vi['valoare_totala'],2);
		$vi['valoare_tva'] = $vi['valoare_totala'] * $this->procTva / 100;
		$vi['valoare_tva'] = round($vi['valoare_tva'],2);
		$vi['procTva'] = $this->procTva;

		//baza de date de cacat
		$vi['destinatar_cod_cl'] = $vi['destinatar_id'];
		$vi['destinatar_id'] = $cl_dest_id;

	   	$vi['src'] = 1;

		$vi['created_at'] = date("Y-m-d H:i:s");
		$vi['user_id'] = $this->user_id;
		$id = $this->db->QueryInsert($this->tables['client_expeditii'], $vi);

		echo $vi['expeditie'].' : insert ok.<br/>'; die;
	}

	function testLocalitate($lc)
	{
		$lc = strtoupper($lc);
		if (0 === strpos($lc, 'BUCURESTI')) return 'Bucuresti';
		if (0 === strpos($lc, 'SECTOR')) return 'Bucuresti';
	}

	function CampuriImport($tip_import=1){
		$val_import=[];
		if($tip_import == 1)
		{
			$val_import['destinatar'] = 'DESTINATAR';
			$val_import['destinatar_judet'] = 'JUDET';
			$val_import['destinatar_localitate'] = 'LOCALITATE';
			$val_import['destinatar_adresa'] = 'ADRESA';
			$val_import['destinatar_contact'] = 'PERSOANA CONTACT';
			$val_import['destinatar_contact_telefon'] = 'TELEFON CONTACT';
			$val_import['destinatar_contact_mobil'] = 'MOBIL CONTACT';
			$val_import['platitor'] = 'PLATITOR(EXPEDITOR/DESTINATAR)';
			$val_import['tip_expeditie'] = 'TIP EXPEDITIE(PLIC/COLET/PALET)';
			$val_import['piese'] = 'BUCATI';
			$val_import['greutate'] = 'GREUTATE';
			$val_import['asigurare'] = 'ASIGURARE';
			$val_import['ramburs'] = 'RAMBURS';
			$val_import['tip_plata'] = 'TIP PLATA(CASH,CONT,BO,CEC)';
			$val_import['ret_nt'] = 'RETUR NT(DA/NU)';
			$val_import['ret_doc'] = 'RETUR DOCUMENTE(DA/NU)';
			$val_import['detalii_doc'] = 'DETALII DOCUMENTE';
			$val_import['tip_livrare'] = 'LIVRARE(NORMALA/SAMBATA/SEDIU)';
			$val_import['observatii'] = 'OBSERVATII';
			$val_import['referinta_facturare'] = 'REFERINTA FACTURARE';
			$val_import['copen'] = 'DESCHIDERE COLET(DA/NU)';
			$val_import['sms'] = 'SMS LIVRARE(DA/NU)';
		}
		else if($tip_import == 2)
		{
			$val_import['nume'] = 'NUME';
			$val_import['contact'] = 'PERSOANA CONTACT';
			$val_import['telefon'] = 'TELEFON CONTACT';
			$val_import['mobil'] = 'MOBIL CONTACT';
			$val_import['email'] = 'EMAIL';
			$val_import['judet'] = 'JUDET';
			$val_import['localitate'] = 'LOCALITATE';
			$val_import['adresa'] = 'ADRESA/STRADA';

			$val_import['nr'] = 'NR. Strada';
			$val_import['bloc'] = 'BLOC';
			$val_import['scara'] = 'SCARA';
			$val_import['etaj'] = 'ETAJ';
			$val_import['apt'] = 'APPARTAMENT';
			$val_import['cp'] = 'COD POSTAL';
		}
		else if($tip_import == 3) //required fields
		{
			return array('destinatar', 'destinatar_judet', 'destinatar_localitate', 'destinatar_adresa');
		}
		else if($tip_import == 4) //fields
		{
			return array('platitor', 'tip_expeditie', 'asigurare', 'ramburs', 'ret_nt', 'ret_doc', 'tip_livrare');
		}
		else if($tip_import == 5) //required fields
		{
			return array('nume', 'judet', 'localitate', 'adresa');
		}
		else if($tip_import == 6) //fields
		{
			return [];
		}

		return $val_import;
	}

	function getComboCampuriImport($id, $val_import){

		$result = '<option value="nok" selected ></option>';
		foreach($val_import as $key => $val){
                $result .= '<option value="' . $key . '">' . $val . '</option>';
		}
		return '<select class="field_import" name="field_' . $id . '" id="field_' . $id . '" >' . $result . '</select>';
	}

	function getCampImport($campuri_import, $val){
		if(is_array($campuri_import) && !empty($val) && !empty($campuri_import[$val]))
			return $campuri_import[$val];
		return '';
	}

	function verifyRequiredParams($row_data, $required_fields, $fields) {
    	$error = false;
    	$error2 = false;
    	$error_fields = "";
    	$error_fields2 = "";
    	$ret=null;

    	foreach ($required_fields as $field) {
        	if (!(isset($row_data[$field]) && strlen($row_data[$field]) > 0)) {
            	$error = true;
            	$error_fields .= $field . ', ';
        	}
    	}

    	foreach ($fields as $field) {
        	if($field == 'asigurare' && !empty($row_data[$field]) && !(strlen($row_data[$field]) > 0 && is_numeric($row_data[$field]) && floatval($row_data[$field]) <= parent::MAX_ASIGURARE))
        	{
        		$error2 = true;
            	$error_fields2 .= 'ASIGURARE, ';
        	}
        	else if($field == 'ramburs' && !empty($row_data[$field]) && !(strlen($row_data[$field]) > 0 && is_numeric($row_data[$field])))
        	{
        		$error2 = true;
            	$error_fields2 .= 'RAMBURS, ';
        	}
        	else if($field == 'tip_expeditie'  && !empty($row_data[$field]) && !(strtoupper($row_data[$field]) == 'PLIC' || strtoupper($row_data[$field]) == 'COLET' || strtoupper($row_data[$field]) == 'PALET'))
        	{
        		$error2 = true;
            	$error_fields2 .= 'TIP EXPEDITIE, ';
        	}
        	else if($field == 'platitor'  && !empty($row_data[$field]) && !(strtoupper($row_data[$field]) == 'EXPEDITOR' || strtoupper($row_data[$field]) == 'DESTINATAR'))
        	{
        		$error2 = true;
            	$error_fields2 .= 'PLATITOR, ';
        	}
        	else if($field == 'ret_nt' && !empty($row_data[$field]) && !(strtoupper($row_data[$field]) == 'DA' || strtoupper($row_data[$field]) == 'NU'))
        	{
        		$error2 = true;
            	$error_fields2 .= 'RETUR NT, ';
        	}
        	else if($field == 'ret_doc' && !empty($row_data[$field]) && !(strtoupper($row_data[$field]) == 'DA' || strtoupper($row_data[$field]) == 'NU'))
        	{
        		$error2 = true;
            	$error_fields2 .= 'RETUR DOCUMENTE, ';
        	}
        	else if($field == 'tip_livrare' && !empty($row_data[$field]) && !(strtoupper($row_data[$field]) == 'NORMALA' || strtoupper($row_data[$field]) == 'SAMBATA' || strtoupper($row_data[$field]) == 'SEDIU' || strtoupper($row_data[$field]) == 'LIVRARE NORMALA' || strtoupper($row_data[$field]) == 'LIVRARE SAMBATA' || strtoupper($row_data[$field]) == 'LIVRARE SEDIU'))
        	{
        		$error2 = true;
            	$error_fields2 .= 'LIVRARE, ';
        	}

			else if($field == 'copen' && !empty($row_data[$field]) && !(strtoupper($row_data[$field]) == 'DA' || strtoupper($row_data[$field]) == 'NU'))
			{
				$error2 = true;
				$error_fields2 .= 'DESCHIDERE COLET, ';
			}
			else if($field == 'sms' && !empty($row_data[$field]) && !(strtoupper($row_data[$field]) == 'DA' || strtoupper($row_data[$field]) == 'NU'))
			{
				$error2 = true;
				$error_fields2 .= 'SMS LIVRARE, ';
			}
    	}
    	if(isset($row_data['tip_expeditie']) && strtoupper($row_data['tip_expeditie']) == 'COLET') {
    			if (!(isset($row_data['piese']) && intval($row_data['piese']) >= 1 && is_numeric($row_data['piese'])))
    			{
    				$error2 = true;
            		$error_fields2 .= 'BUCATI, ';
    			}
    			if (!(isset($row_data['greutate']) && is_numeric($row_data['greutate']) && intval($row_data['greutate']) >= parent::MIN_KG_COLET))
    			{
    				$error2 = true;
            		$error_fields2 .= 'GREUTATE (min kg : 1), ';
    			}
    	}
		if(isset($row_data['tip_expeditie']) && strtoupper($row_data['tip_expeditie']) == 'PALET') {
			if (!(isset($row_data['greutate']) && is_numeric($row_data['greutate']) && intval($row_data['greutate']) >= parent::MIN_KG_PALET))
			{
				$error2 = true;
				$error_fields2 .= 'GREUTATE (min kg : 50), ';
			}
		}
		if(isset($row_data['sms']) && strtoupper($row_data['sms']) == 'DA'){
			$mm_destinatar_contact_telefon = '';
			if(!empty($row_data['destinatar_contact_telefon']) && !empty($row_data['destinatar_contact_mobil']))
				$mm_destinatar_contact_telefon = strtoupper($row_data['destinatar_contact_mobil']);
			else if(!empty($row_data['destinatar_contact_telefon']))
				$mm_destinatar_contact_telefon = strtoupper($row_data['destinatar_contact_telefon']);
			else if(!empty($row_data['destinatar_contact_mobil']))
				$mm_destinatar_contact_telefon = strtoupper($row_data['destinatar_contact_mobil']);

			if(empty($mm_destinatar_contact_telefon) || !ExpeditieDto::isValidTelefonNumber($mm_destinatar_contact_telefon)) {
				$error2 = true;
            	$error_fields2 .= 'TELEFON CONTACT 07XXXXXXXX, MOBIL CONTACT 07XXXXXXXX, ';
			}
		}

    	if ($error) {
        	// Required field(s) are missing or empty
        	$ret = [];
        	$ret['error1'] = 'Urmatoarele campuri : ' . substr($error_fields, 0, -2) . ' sunt obligatorii';
    	}
    	if ($error2) {
        	// Required field(s) are missing or empty
        	if(!is_array($ret)) $ret=[];
        	$ret['error2'] = 'Urmatoarele campuri : ' . substr($error_fields2, 0, -2) . ' au valori eronate';
    	}

    	return $ret;
	}

	function ImportCSV($file,$separator,$tip_import=1){
		ini_set('memory_limit', '1228M');
		set_time_limit(600);

		$path = $this->paths['upload'].$file;
		$campuri_import = $this->CampuriImport($tip_import);
		$row = 0;
		$cels = 0;
		$result = '<table id="lista-expeditii-import"  cellpadding="4" cellspacing="0">';
		if (($handle = fopen($path, "r")) !== FALSE) {
			while (($row_data = fgetcsv($handle, 50000,$separator )) !== FALSE) {
				$tst = implode($row_data);
				if(!empty($tst)){ //not empty line
					if($row == 0) $cels = count($row_data);
					$result .= $this -> ImportCSVRow($row_data,$row,$campuri_import,$cels, $tip_import);
				}
				$row++;
			}
			$result .= '</tbody></table>';
			fclose($handle);
		}
		return $result;
	}


	function ImportCSVRow($row_data,$row,$campuri_import, $cels, $tip_import=1){
		$result = '';
		$top = '';
		$cell=0;

		foreach($row_data as $cell_data){
			if($row == 0){
				$top .= '<th>'.$this->getComboCampuriImport($cell,$campuri_import).'</th>';
				$result .= '<td class="top" id="col_'.$row.'_'.$cell.'">'.$cell_data.'</td>';
			}else{
				if($cell_data == '') $cell_data = '&nbsp;';
				$result .= '<td class="col_'.$row.'_'.$cell.'">'.$this->sanitize($cell_data).'</td>';
			}
			$cell++;
		}
		if($row == 0){
			$result = '<thead><tr>'.$top.'</tr></thead><tbody><tr>'.$result.'</tr>';
		}else{
			$result = '<tr>'.$result.'</tr>';
		}
		//errori
		if($row != 0 && $cels != count($row_data)) $this->vars['error'] .= $result;

		return $result;
	}

	//prelucrare pentru salvare
	function ImportCSVSave($file,$separator,$campuri,$tip_import=1){
		ini_set('memory_limit', '1228M');
		set_time_limit(600);

		if(empty($file)) return;

		$path = $this->paths['upload'].$file;
		$row = 0;
		$result = [];
		//extrag valorile din csv
		if (($handle = fopen($path, "r")) !== FALSE) {
			while (($row_data = fgetcsv($handle, 50000, $separator )) !== FALSE) {
				$tst = implode($row_data);
				if(!empty($row) && !empty($tst)){ //not empty line
					//prelucrez fiecare rand mai putin primul rand
					$rez = $this -> ImportCSVRowSave($row_data,$row,$campuri,$tip_import);
					if(!empty($rez) && count($rez) > 0) $result[] = $rez;
				}
				$row++;
			}
			fclose($handle);
		}
		return $result;
	}

	//prelucrare pentru salvare rand
	function ImportCSVRowSave($row_data,$row,$campuri,$tip_import=1){
		$result = [];
		$cell=0;
		foreach($row_data as $cell_data){
			//import doar coloanele care sunt
			//in campuri; sunt coloanele care trebuie importate
			//cell sunt valorile din CSV
			if(!empty($campuri[$cell])){
				$m_cell_data = $this->sanitize($cell_data);
				$result[$campuri[$cell]] = (empty($m_cell_data)?"":$m_cell_data);
			}
			$cell++;
		}
		//returnez pentru prelucrare
		return $result;
	}

	function ImportExpeditiiXlsStep1() {
        $this->vars['title_page'] = 'Import Expeditii';
        $vars = [];
		$vars['error'] = (isset($this->vars['error']))?'<span style="color:red">'.$this->vars['error'].'</span>':'';
		$this->vars['error'] = '';
        return $this->Parse($this->page_prefix . 'import_expeditii.html', $vars);
    }


    function ImportExpeditiiXlsStep2(){
    	require_once 'uploadXls.php';
    	$this->vars['title_page'] = 'Verificare';
        $this->vars['error'] = '';
        if(isset($_POST['sxls']) && !empty($_FILES['fxls'])){
        	$upload = UploadXls::uploadXlsUp('fxls');
        	if(is_array($upload) && count($upload) == 2) {
        		if($upload[0])
        			return $this->Parse($this->page_prefix . 'import_expeditii_step2.html', array('fisier'=>$upload[1], 'results'=>''));
        		else {
        			$this->vars['error'] = $upload[1];
        			return $this->ImportExpeditiiXlsStep1();
        		}
        	}
        	else {
        		$this->vars['error'] = 'Unknown error';
        		return $this->ImportExpeditiiXlsStep1();
        	}

		}
		else {
			$this->vars['error'] .= 'Selectati fisierul';
			return $this->ImportExpeditiiXlsStep1();
		}
    }

    function ImportExpeditiiXlsStep3(){
    	$this->vars['title_page'] = 'Import';
        $this->vars['error'] = '';
        if(isset($_POST['sxls']) && !empty($_POST['fxls']) && isset($_POST['merror'])){
			return $this->Parse($this->page_prefix . 'import_expeditii_step3.html', array('fisier'=>$_POST['fxls'], 'merror'=>$_POST['merror']));
		}
		else {
			$this->vars['error'] .= 'Selectati fisierul';
			return $this->ImportExpeditiiXlsStep1();
		}
    }

	//procesare import
	function ImportExpeditiiXlsCheck(){
		require_once 'uploadXls.php';
		set_time_limit(600);
		header('Content-Type: text/event-stream');
		header('Cache-Control: no-cache'); // recommended to prevent caching of event data.
		header("Access-Control-Allow-Origin: *");

		$firstLine = array('codbara','plic','colet','palet','greutate','clientdest','adresadest','orasdest','judetdest','centru','perscontactdest','telefondest','observatii','serieclient','rambursnumerar','ramburscontcolector','rambursalttip','platitorexpeditie','livraresambata','email','frig','continut','valoaredeclarata','extrainfo','largeinfo');
	 	$firstLineNew = array('codbara','plic','colet','palet','greutate','clientdest','adresadest','orasdest','judetdest','centru','perscontactdest','telefondest','observatii','serieclient','rambursnumerar','ramburscontcolector','rambursalttip','platitorexpeditie','livraresambata','email','frig','continut','valoaredeclarata','extrainfo','largeinfo',
		'codpostaldest','intervallivrare','deschiderecolet','taradest','emaildest','disclaimer','refexp1','refdest1','refdest2','referintafacturare');
		$nbCols = 25;
		$nbColsNew = 35;
		$serverTime = time();

		if(empty($_GET['fxls'])) { UploadXls::send_message($serverTime, 'error loading file : empty file name', 0, 2); exit(0); }
		else $inputFileName = $_GET['fxls'];

    	try {
			$inputFileType = IOFactory::identify($inputFileName);
			$reader = IOFactory::createReader($inputFileType);
			$reader->setReadDataOnly(true);
			$spreadsheet = $reader->load($inputFileName);
			$spreadsheet->setActiveSheetIndex(0);
			$worksheet = $spreadsheet->getActiveSheet();
			$highestRow = $worksheet->getHighestDataRow();
			UploadXls::send_message($serverTime, ($highestRow - 1) . ' expeditii de importat<br/>', 0, 0);
			$highestColumn = $worksheet->getHighestDataColumn();
			$nbhighestColumn = Coordinate::columnIndexFromString($highestColumn);
			if($nbhighestColumn != $nbCols && $nbhighestColumn != $nbColsNew)
				throw new Exception("fisierul are un numar de coloane (".$nbhighestColumn.") diferit de ".$nbCols." | ".$nbColsNew);

			$colNames = $worksheet->rangeToArray('A1:' . $highestColumn . 1, NULL, TRUE, FALSE);
			if($firstLine != $colNames[0] && $firstLineNew != $colNames[0])
				throw new Exception("denumirile coloanelor gresite : coloanele bune sunt urmatoarele : ".implode(',',$firstLineNew));
			if($nbhighestColumn == $nbCols) {
				$colsRange = array_combine($this->excelRange($nbCols),$firstLine);
			}
			else if($nbhighestColumn == $nbColsNew) {
				$colsRange = array_combine($this->excelRange($nbColsNew),$firstLineNew);
			}
			else throw new Exception("numar de coloane gresit : coloanele bune sunt urmatoarele : ".implode(',',$firstLineNew));

			if($colsRange === false)
				throw new Exception("numar de coloane gresit : array_combine");

			$rowIterator = $worksheet->getRowIterator();
			$proc = 1;
			if($highestRow <=10) $proc = 10;
			else if($highestRow <=100) $proc = 1;
			else if($highestRow <=1000) $proc = 0.1;
			else if($highestRow <=10000) $proc = 0.01;

			$terrors = false;
			$mexpeditii=[];
			foreach($rowIterator as $row){
    			$cellIterator = $row->getCellIterator();
    			$cellIterator->setIterateOnlyExistingCells(false); // Loop all cells, even if it is not set
    			if(1 == $row->getRowIndex ()) continue;//skip first row
    			$rowIndex = $row->getRowIndex();
    			$localitate = '';
    			$error = '';
				$piese = 0;

            	foreach ($cellIterator as $cell) {
					$fLineCol = $colsRange[$cell->getColumn()];
           			$val = $cell->getValue();
           			switch($fLineCol) {
           				case 'codbara':
           					if(true !== ($ret = $this->checkCodBaraMaravet($val)))
           						$error .= "valoare eronata la linia : ".$rowIndex." coloana : ".$fLineCol." ".$ret."<br/>";
           					if(in_array($val, $mexpeditii))
           						$error .= "valoare eronata la linia : ".$rowIndex." coloana : ".$fLineCol." : ".$val." exista deja in acest fisier<br/>";
           					$mexpeditii[] = $val;
           					break;
           				case 'plic':
           					if(empty($val) || (!empty($val) && $val != 1))
           						$error .= "valoare eronata la linia : ".$rowIndex." coloana : ".$fLineCol ." : <>1"."<br/>";
           					break;
           				case 'colet':
           					$piese += intval($val);
           					break;
           				case 'palet':
           					$piese += intval($val);
           					if($piese <= 0)
           						$error .= "valoare eronata la linia : ".$rowIndex." coloana : ".$fLineCol ." : colet+palet > 0"."<br/>";
           					break;
						case 'greutate':
           					if(empty($val) || intval($val) <= 0)
           						$error .= "valoare eronata la linia : ".$rowIndex." coloana : ".$fLineCol ." : valoare obligatorie > 0"."<br/>";
           					break;
           				case 'clientdest':
           					if(empty($val))
           						$error .= "valoare eronata la linia : ".$rowIndex." coloana : ".$fLineCol ." : lipseste destinatarul"."<br/>";
           					break;
           				case 'adresadest':
           					if(empty($val) || strlen($val) < 4)
           						$error .= "valoare eronata la linia : ".$rowIndex." coloana : ".$fLineCol ." : lipseste adresa de destinatie"."<br/>";
           					break;
           				case 'orasdest':
           					if(empty($val))
           						$error .= "valoare eronata la linia : ".$rowIndex." coloana : ".$fLineCol ." : lipseste orasul de destinatie"."<br/>";
           					$localitate = $val;
           					break;
           				case 'judetdest':
           					if(empty($val))
           						$error .= "valoare eronata la linia : ".$rowIndex." coloana : ".$fLineCol ." : lipseste judetul de destinatie"."<br/>";
           					else if(!empty($localitate) && true !== ($ret = $this->checkLocalitate($localitate, $val)))
           						$error .= "valoare eronata la linia : ".$rowIndex." coloana : ".$fLineCol ." : ".$ret;
           					break;
           				case 'centru':
           					break;
           				case 'perscontactdest':
           					break;
           				case 'telefondest':
           					break;
           				case 'observatii':
           					break;
           				case 'platitorexpeditie':
           					break;
           				case 'livraresambata':
           					if(!empty($val) && !(strtoupper($val)=='DA' || strtoupper($val)=='NU'))
           						$error .= "valoare eronata la linia : ".$rowIndex." coloana : ".$fLineCol ." : valori posibile : Da | Nu"."<br/>";
           					break;
						case 'deschiderecolet':
							if(!empty($val) && !(strtoupper($val)=='DA' || strtoupper($val)=='NU'))
								$error .= "valoare eronata la linia : ".$rowIndex." coloana : ".$fLineCol ." : valori posibile : Da | Nu"."<br/>";
							break;
           				case 'frig':
           					if(!empty($val) && !(strtoupper($val)=='DA' || strtoupper($val)=='NU'))
           						$error .= "valoare eronata la linia : ".$rowIndex." coloana : ".$fLineCol ." : valori posibile : Da | Nu"."<br/>";
           					break;
           				default :
           					break;
           			}
       			}
       			UploadXls::send_message($serverTime, $error, round($rowIndex*$proc), 1);
       			if(!empty($error)) $terrors = true;
			}
		} catch(Exception $e) {
    		UploadXls::send_message($serverTime, 'Error loading file "'.pathinfo($inputFileName,PATHINFO_BASENAME).'": '.$e->getMessage(), 0, 2);
    		exit(0);
		}
		if($terrors)
			UploadXls::send_message($serverTime, '', 100, 2);
		else
			UploadXls::send_message($serverTime, '', 100, 3);
        exit(0);
    }

   function ImportExpeditiiXlsImport(){
   		set_time_limit(600);
		header('Content-Type: text/event-stream');
		header('Cache-Control: no-cache'); // recommended to prevent caching of event data.
		header("Access-Control-Allow-Origin: *");

		require_once 'uploadXls.php';

		$firstLine = array('codbara','plic','colet','palet','greutate','clientdest','adresadest','orasdest','judetdest','centru','perscontactdest','telefondest','observatii','serieclient','rambursnumerar','ramburscontcolector','rambursalttip','platitorexpeditie','livraresambata','email','frig','continut','valoaredeclarata','extrainfo','largeinfo');
		$firstLineNew = array('codbara','plic','colet','palet','greutate','clientdest','adresadest','orasdest','judetdest','centru','perscontactdest','telefondest','observatii','serieclient','rambursnumerar','ramburscontcolector','rambursalttip','platitorexpeditie','livraresambata','email','frig','continut','valoaredeclarata','extrainfo','largeinfo',
		'codpostaldest','intervallivrare','deschiderecolet','taradest','emaildest','disclaimer','refexp1','refdest1','refdest2','referintafacturare');
		$nbCols = 25;
		$nbColsNew = 35;
		$serverTime = time();

		if(empty($_GET['fxls'])) { UploadXls::send_message($serverTime, 'Error loading file : empty file name', 0, 2); exit(0); }
		else $inputFileName = $_GET['fxls'];

    	try {
			$inputFileType = IOFactory::identify($inputFileName);
			$reader = IOFactory::createReader($inputFileType);
			$reader->setReadDataOnly(true);
			$spreadsheet = $reader->load($inputFileName);
			$spreadsheet->setActiveSheetIndex(0);
			$worksheet = $spreadsheet->getActiveSheet();
			$highestRow = $worksheet->getHighestDataRow();
			UploadXls::send_message($serverTime, ($highestRow - 1) . ' expeditii de importat<br/>', 0, 0);
			$highestColumn = $worksheet->getHighestDataColumn();
			$nbhighestColumn = Coordinate::columnIndexFromString($highestColumn);

			if($highestColumn == $nbCols)
				$colsMap = array_combine($firstLine, $this->excelRange($nbCols));
			else if($nbhighestColumn == $nbColsNew)
				$colsMap = array_combine($firstLineNew, $this->excelRange($nbColsNew));
			else throw new Exception("numar de coloane gresit : coloanele bune sunt urmatoarele : ".implode(',',$firstLineNew));

			if($colsMap === false)
				throw new Exception("numar de coloane gresit : array_combine");

			$rowIterator = $worksheet->getRowIterator();

			$proc = 1;
			if($highestRow <=10) $proc = 10;
			else if($highestRow <=100) $proc = 1;
			else if($highestRow <=1000) $proc = 0.1;
			else if($highestRow <=10000) $proc = 0.01;
		} catch(Exception $e) {
    		UploadXls::send_message($serverTime, $e->getMessage(), 0, 2);
    		exit(0);
		}

		$master_id = $this->show_master_clienti && $this->is_pc ? $this->master_id : -1;

		foreach($rowIterator as $row){
			try {
    			if(1 == $row->getRowIndex ()) continue;//skip first row
    			$rowIndex = $row->getRowIndex();
    			$error = '';

				//$mcodbara = $this->sanitize($worksheet->getCell($colsMap['codbara'].$rowIndex)->getValue());
    			$mcodbara = $this->sanitize($worksheet->getCell($colsMap['codbara'].$rowIndex)->getValue());
    			$expeditie = intval($mcodbara);

    			if(true !== ($ret = $this->checkCodBaraMaravet($mcodbara)))
           				{ $error .= "error : Expeditia ".$expeditie." ".$ret.' : import failed';  UploadXls::send_message($serverTime, $error, round($rowIndex*$proc), 1); continue; }

    			//exceptii localitate, judet
				$destinatar_localitate = $worksheet->getCell($colsMap['orasdest'].$rowIndex)->getValue();
    			$destinatar_localitate = (!empty(Backend::sSanitizeCleanEdges($destinatar_localitate ?? "")) ? strtoupper(Backend::sSanitizeCleanEdges($destinatar_localitate)):"");
				$destinatar_judet = $worksheet->getCell($colsMap['judetdest'].$rowIndex)->getValue();
    			$destinatar_judet = (!empty(Backend::sSanitizeCleanEdges($destinatar_judet ?? "")) ? strtoupper(Backend::sSanitizeCleanEdges($destinatar_judet)):"");
				$test_destinatar_localitate = $this->testLocalitate($destinatar_localitate);
				if($test_destinatar_localitate == 'Bucuresti')
				{
					$query="select cod_lc, nume_lc, dist_km, cod_centru from localitati
					where nume_lc like :test_destinatar_localitate and cod_jd like 'B' limit 1";
    				$sql = $this->db->QFetchRowAssoc($query, ['test_destinatar_localitate' => $test_destinatar_localitate]);
					if(empty($sql)) { $error .= 'error : Expeditia '.$expeditie.' : localitate eronata : '.$destinatar_localitate.' : import failed';  UploadXls::send_message($serverTime, $error, round($rowIndex*$proc), 1); continue; }
					else
					{
						$destinatar_localitate = $sql['nume_lc'];
						$destinatar_localitate_id = $sql['cod_lc'];
						$destinatar_cod_centru = $sql['cod_centru'];
						$km_ext_livr = $sql['dist_km'];
					}
				}
				else
				{
					//cauta judetul
					$query="select cod_jd from judete where replace(replace(nume_jd,' ',''),'-','') like replace(replace(:destinatar_judet,' ',''),'-','') limit 1";
    				$sql = $this->db->QFetchRowAssoc($query, ['destinatar_judet' => $destinatar_judet]);
					if(empty($sql)) {  $error .= 'error : Expeditia '.$expeditie.' : judet inexistent : '.$destinatar_judet.' : import failed';  UploadXls::send_message($serverTime, $error, round($rowIndex*$proc), 1); continue; }
					else $judet = $sql['cod_jd'];
					//cauta localitatea
					$query="select cod_lc, nume_lc, dist_km, cod_centru from localitati where replace(replace(nume_lc,' ',''),'-','') like replace(replace(:destinatar_localitate,' ',''),'-','')  and cod_jd like :judet limit 1";
    				$sql = $this->db->QFetchRowAssoc($query, ['destinatar_localitate' => $destinatar_localitate, 'judet' => $judet]);
					if(empty($sql)) {  $error .= 'error : Expeditia '.$expeditie.' : localitate eronata : '.$destinatar_localitate.' : import failed';  UploadXls::send_message($serverTime, $error, round($rowIndex*$proc), 1); continue; }
					else
					{
						$destinatar_localitate = $sql['nume_lc'];
						$destinatar_localitate_id = $sql['cod_lc'];
						$destinatar_cod_centru = $sql['cod_centru'];
						$km_ext_livr = $sql['dist_km'];
					}
				}

				$vi = [];
				$vi['data_expeditie'] =  date("Y-m-d");
				$vi['expeditie'] = $expeditie;
				$vi['expeditor'] = $this->expeditor_id;
				$vi['expeditor_contact'] = $this->expeditor_contact;
				$vi['expeditor_telefon'] = $this->expeditor_telefon;
				$vi['platitor'] = 1;
				//if(isset($exp_row['platitor']) && strtoupper($exp_row['platitor']) == 'DESTINATAR') $vi['platitor'] = 2;
				$vi['destinatar_localitate'] = $destinatar_localitate;
				$vi['destinatar_localitate_id'] = $destinatar_localitate_id;
				$vi['km_ext_livr'] = (float)$km_ext_livr;
				$vi['km_ext_prel'] = $this->expeditor_localitate_km;

				$vi['destinatar_contact'] = '';
				$vi['destinatar_telefon'] = '';
				$destinatar_contact = $worksheet->getCell($colsMap['perscontactdest'].$rowIndex)->getValue();
				$destinatar_contact = (!empty(Backend::sSanitizeCleanEdges($destinatar_contact ?? "")) ? strtoupper(Backend::sSanitizeCleanEdges($destinatar_contact)):"");
				$destinatar_telefon = strtoupper($this->sanitize($worksheet->getCell($colsMap['telefondest'].$rowIndex)->getValue()));

				if(!empty($destinatar_contact))
					$vi['destinatar_contact'] = $destinatar_contact;
				if(!empty($destinatar_telefon))
					$vi['destinatar_telefon'] = $destinatar_telefon;

				//cauta destinatarul in tabla clienti
				$destinatar = $worksheet->getCell($colsMap['clientdest'].$rowIndex)->getValue();
				$destinatar = (!empty(Backend::sSanitizeCleanEdges($destinatar ?? "")) ? strtoupper(Backend::sSanitizeCleanEdges($destinatar)):"");
				$destinatar_adresa = $worksheet->getCell($colsMap['adresadest'].$rowIndex)->getValue();
				$destinatar_adresa = (!empty(Backend::sSanitizeCleanEdges($destinatar_adresa ?? "")) ? strtoupper(Backend::sSanitizeCleanEdges($destinatar_adresa)):"");
				$query = "select cod_cl from clienti where nume like :destinatar
					and adresa like :destinatar_adresa and cod_lc = ".$vi['destinatar_localitate_id']." limit 1";
	    		$sql = $this->db->QFetchRowAssoc($query, ['destinatar'=>$destinatar, 'destinatar_adresa' => $destinatar_adresa]);
	    		if (empty($sql)) {//in cazul in care nu exista clientul => insert
					//detalii
					$var = [];
		    		$var['nume'] = $destinatar;
		    		$var['cod_lc'] = $vi['destinatar_localitate_id'];
		    		$var['adresa'] = $destinatar_adresa;
		    		$var['operator'] = $this->user_id;
		    		$var['activ'] = 1;
		    		$var['data_op'] = date('Y-m-d H:i:s');
		    		$var['tarif'] = 0;
		    		$var['mod_plata'] = 0;
		    		$var['km_ext'] = $vi['km_ext_livr'];
					$var['contact'] = strtoupper($vi['destinatar_contact']);
					$var['telefon'] = strtoupper($vi['destinatar_telefon']);

					$var['created_at'] = date('Y-m-d H:i:s');
					$var['created_by'] = $this->user_id;
		    		$vi['destinatar_id']=$this->db->QueryInsert($this->tables['clienti'], $var);
					CdsGeocoder::geocode($this->db, $vi['destinatar_id']);
		    		unset($var);
				}
				else {
					$vi['destinatar_id'] = $sql['cod_cl'];
					CdsGeocoder::geocode($this->db, $vi['destinatar_id'], true);
				}
				$vi['destinatar'] = $destinatar;
				$vi['destinatar_adresa'] = $destinatar_adresa;

				//cauta destinatarul in tabla client_destinatari
	    		$qc = "SELECT cod_cl, id, nume, activ FROM client_destinatari
	    			WHERE nume like :destinatar and adresa like :destinatar_adresa
	    			and id_loc = ".$vi['destinatar_localitate_id']." and id_exp in (".$vi['expeditor'].",".$master_id.") limit 1";
	    		$sc = $this->db->QFetchRowAssoc($qc, ['destinatar'=>$destinatar, 'destinatar_adresa' => $vi['destinatar_adresa']]);
	    		if(empty($sc)) //in cazul in care nu exista linkul client:destinatar => insert
				{
					$var = [];
					$var['id_exp'] = $vi['expeditor'];
					$var['nume'] = $vi['destinatar'];
					$var['id_loc'] = $vi['destinatar_localitate_id'];
					$var['km_ext'] = $vi['km_ext_livr'];
					$var['adresa'] = $vi['destinatar_adresa'];
					$var['activ'] = 1;
					$var['cod_cl'] = $vi['destinatar_id'];
					$var['data_op'] = date('Y-m-d H:i:s');;
					$var['observatii'] = '';
					$var['contact'] = $vi['destinatar_contact'];
					$var['telefon'] = $vi['destinatar_telefon'];
					
					$var['created_at'] = date('Y-m-d H:i:s');
					$var['created_by'] = $this->user_id;
					$cl_dest_id = $this->db->QueryInsert($this->tables['client_destinatari'], $var);

				}
				else
				{
					$cl_dest_id = $sc['id'];
					$this->db->QueryUpdate($this->tables['client_destinatari'], ['contact' => $vi['destinatar_contact'], 'telefon' => $vi['destinatar_telefon']], "id=".$cl_dest_id);
					$var = [];
					if(empty($sc['cod_cl'])) $var['cod_cl'] = $vi['destinatar_id'];
					if(empty($sc['activ'])) $var['activ'] = 1;
					$var['updated_at'] = date('Y-m-d H:i:s');
					$var['updated_by'] = $this->user_id;
					if(empty($sc['cod_cl']) || empty($sc['activ']))
						$this->db->QueryUpdate($this->tables['client_destinatari'], $var, "id=".$cl_dest_id);
				}

				$vi['tip_obj'] = 2;
				$greutate = round(floatval(trim($worksheet->getCell($colsMap['greutate'].$rowIndex)->getValue())), 2);
				if(empty($greutate)) {  $error .= 'error : Expeditia '.$expeditie.' : greutate eronata : '.$greutate.' : import failed'; UploadXls::send_message($serverTime, $error, round($rowIndex*$proc), 1); continue; }
				$vi['greutate'] = $greutate;
				$colete = intval(trim($worksheet->getCell($colsMap['colet'].$rowIndex)->getValue()));
				$paleti = intval(trim($worksheet->getCell($colsMap['palet'].$rowIndex)->getValue()));
				if(empty($colete) && empty($paleti)) {  $error .= 'error : Expeditia '.$expeditie.' : colet | palet valoare eronata : '.$piese.' : import failed'; UploadXls::send_message($serverTime, $error, round($rowIndex*$proc), 1); continue; }
				$vi['piese'] = $colete + $paleti + 1;

				//retururi
				$vi['ret_nt']=0;
				$vi['ret_doc']=0;
				$vi['ret_amb']=0;
				$vi['ret_colet']=0;
				$vi['copen']=0;
				$vi['sms']=0;

				$vi['liv_sambata']=0;
				$liv_sambata=strtoupper($this->sanitize($worksheet->getCell($colsMap['livraresambata'].$rowIndex)->getValue()));
				if($liv_sambata == 'DA') $vi['liv_sambata']=1;

				$vi['liv_sediu']=0;

				//asigurare
				$vi['asigurare'] = 0.00;

				//ramburs
				$vi['ramburs'] = 0.00;
				$vi['tip_plata'] = 0;

				$vi['detalii_doc'] = Backend::sSanitizeCleanEdges($worksheet->getCell($colsMap['continut'].$rowIndex)->getValue());
				$vi['observatii'] = Backend::sSanitizeCleanEdges($worksheet->getCell($colsMap['observatii'].$rowIndex)->getValue());

				$vi['tip_tarif'] = (($this->expeditor_localitate_id != $vi['destinatar_localitate_id']) ? 1 : 0);
				$valoare = $this->Get_ValoareExpeditieClient($vi);
				unset($vi['tip_tarif']);

				$vi['moneda'] = ExpeditieDto::MONEDA_REV[$valoare['moneda']] ?? 1;

				$vi['mod_plata'] = $valoare['mod_plata'];
				$vi['valoare_exp'] =$valoare['tExpeditie'];
				$vi['valoare_km'] = $valoare['tKm'];
				$vi['valoare_g'] = $valoare['tGreutate'];
				$vi['valoare_asig'] = $valoare['tAsigurare'];

				$vi['valoare_totala'] = $vi['valoare_exp'] + $vi['valoare_km'] + $vi['valoare_g'] + $vi['valoare_asig'];
				$vi['valoare_totala'] = round($vi['valoare_totala'],2);
				$vi['valoare_tva'] = $vi['valoare_totala'] * $this->procTva / 100;
				$vi['valoare_tva'] = round($vi['valoare_tva'],2);
				$vi['procTva'] = $this->procTva;

				//baza de date de cacat
				$vi['destinatar_cod_cl'] = $vi['destinatar_id'];
				$vi['destinatar_id'] = $cl_dest_id;

				//source
				$vi['src'] = 1;

				$vi['created_at'] = date("Y-m-d H:i:s");
				$vi['user_id'] = $this->user_id;
				$id = $this->db->QueryInsert($this->tables['client_expeditii'], $vi);
				$extrainfo =  $this->sanitize($worksheet->getCell($colsMap['extrainfo'].$rowIndex)->getValue());
				$largeinfo =  $this->sanitize($worksheet->getCell($colsMap['largeinfo'].$rowIndex)->getValue());

				if(!empty($extrainfo))
					$this->db->QueryInsert('exp_nc', array('expeditie'=>$vi['expeditie'],'extrainfo'=>$extrainfo,'largeinfo'=>$largeinfo));
       		}
       		catch(Exception $e) {
    			UploadXls::send_message($serverTime, 'error at line '.$row->getRowIndex().' : '.$e->getMessage(), round($rowIndex*$proc), 1);
			}
       		UploadXls::send_message($serverTime, 'Expeditia '.$vi['expeditie'].' importata cu succes', round($rowIndex*$proc));
		}
		UploadXls::send_message($serverTime, '', 100, 3);
        exit(0);
	}

	function DownloadConfirmare(){
    	require_once "download.php";

    	if (!isset($_GET['expeditie']) || empty($_GET['expeditie'])) {
  			die("Please specify nt number for download.");
		}
		$expeditie = intval($_GET['expeditie']);
		if(!ExpeditieDto::isAppAwb($expeditie))
			die("Wrong nt number.");

		$d = new Download($this->db, $expeditie);
		$d->sendFile();
		exit;
    }
}
//end class
?>
