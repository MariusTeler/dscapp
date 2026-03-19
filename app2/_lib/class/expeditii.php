<?php

require_once "expeditieDto.php";

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

use OpenSpout\Writer\CSV\Writer;
use OpenSpout\Writer\CSV\Options;
use OpenSpout\Common\Entity\Row;
use OpenSpout\Common\Entity\Cell;
/**
 * W o r k s p a c e
 *
 */
class ModulExpeditii extends BackEnd {

    public $final_result;
    public $action_module;
    public $page_prefix;
    public $site_prefix;
    public $table;

	public $print_awb;
	public $print_add;
	public $user_nume;
	public $user_telefon;

	private $can_pret_impus = false;

    /**
     * The constructor for the 'ModulExpeditii' class
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
		$this->procTva = $this->getProcentTVA(date("Y-m-d"));

        $this->vars['title_page'] = 'Expeditii';
        $this->page_prefix = 'expeditii_';
		
		$this->print_awb = $_SESSION["user"]["print_awb"] ?? 1;
		$this->print_add = $_SESSION["user"]["print_add"] ?? 1;
		$this->user_nume = $_SESSION["user"]["nume"] ?? "";
		$this->user_telefon = $_SESSION["user"]["telefon"] ?? "";

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
    function Actions($msg = '') {
        $this->final_result = '';

        // A D M I N
        if (isset($_GET['logout']))
            $this->Logout();

        else if (!empty($this->user_profile))
            $this->ActionsNivelAcces();
        else
            $this->final_result = $this->PageNotFound();
        // R E S U L T
        return $this->final_result;
    }


    function ActionsNivelAcces() {
		$this->user_rights = $this->GetDrepturiUtilizator($this->user_profile);
		$this->can_pret_impus = ($this->user_profile == 10 || is_array($this->user_rights) && in_array('pret_impus', $this->user_rights));
       	$arr = $this->GenerateArr();
		$flag=0;

			//  actiuni care se pot accesa de orice permisiune
			if(isset($arr[1]) && $arr[1] == 'detalii_expeditie')
				{echo $this->DetaliiExpeditie(); return;}
			else if(isset($arr[1]) && $arr[1] == 'download_confirmare')
				{$flag=1; echo $this->DownloadConfirmare(0);}
			else if(isset($arr[1]) && $arr[1] == 'download_recantarire')
				{$flag=1; echo $this->DownloadConfirmare(1);}
			else if(isset($arr[1]) && $arr[1] == 'json' && isset($arr[2]) && $arr[2]=='localitati')
				{$flag=1; echo $this->JSON_Localitati();}
			else if(isset($arr[1]) && $arr[1] == 'json' && isset($arr[2]) && $arr[2]=='centre')
				{$flag=1; echo $this->JSON_Centre();}
			else if(isset($arr[1]) && $arr[1] == 'json' && isset($arr[2]) && $arr[2]=='clienti')
				{$flag=1; echo $this->JSON_Clienti();}
			else if(isset($arr[1]) && $arr[1] == 'json' && isset($arr[2]) && $arr[2]=='clienti_ctr')
				{$flag=1; echo $this->JSON_ClientiContract();}

			else if(isset($arr[1]) && $arr[1] == 'json' && isset($arr[2]) && $arr[2]=='agenti')
				{$flag=1; echo $this->JSON_Agenti();}
			else if(isset($arr[1]) && $arr[1] == 'json' && isset($arr[2]) && $arr[2]=='operatori')
				{$flag=1; echo $this->JSON_Operatori();}

			else if (isset($arr[1]) && $arr[1] == 'print_expeditie' && !empty($arr[2]))
				{$flag=1; echo $this->PrintExpeditieTCPDF($arr[2]);}

			else if(isset($arr[1]) && $arr[1] == 'json' && isset($arr[2]) && $arr[2]=='istoric')
				{$flag=1; echo $this->JSON_Istoric();}

			//  actiuni in functie de permisiune
			if (in_array("cautare", $this->user_rights) || $this->user_profile == 10){
				$flag=1;
				if (isset($arr[1]) && $arr[1] == 'cautare' && isset($arr[2]) && $arr[2] == 'alex')
					$this->final_result = $this->CautareExpeditii(1);
				else if (isset($arr[1]) && $arr[1] == 'cautare')
					$this->final_result = $this->CautareExpeditii();
				else if(isset($arr[1]) && $arr[1] == 'json' && isset($arr[2]) && $arr[2]=='cautare')
					echo $this->JSON_CautareExpeditii();
				else if(isset($arr[1]) && $arr[1] == 'export' && isset($arr[2]) && $arr[2]=='cautare')
					echo $this->ExportCautareExpeditii();
				else if(isset($arr[1]) && $arr[1] == 'printare_multipla')
					echo $this->PrintareMultiplaCautareExpeditii($arr[2] ?? "");
				else if(isset($arr[1]) && $arr[1] == 'printare_multipla_puisori')
					echo $this->PrintareMultiplaPuisoriCautareExpeditii($arr[2] ?? "");
				else if(isset($arr[1]) && $arr[1] == 'printare_multipla_puisori_autocolant')
					echo $this->PrintareMultiplaPuisoriCautareExpeditii($arr[2] ?? "", true);
				else if(isset($arr[1]) && $arr[1] == 'printare_multipla_master')
					echo $this->PrintareMultiplaMasterCautareExpeditii($arr[2] ?? "");
				else if(isset($arr[1]) && $arr[1] == 'printare_multipla_master_autocolant')
					echo $this->PrintareMultiplaMasterCautareExpeditii($arr[2] ?? "", true);
				else if(isset($arr[1]) && $arr[1] == 'printare_multipla_master_puisori')
					echo $this->PrintareMultiplaMasterPuisoriCautareExpeditii($arr[2] ?? "");
			}
			if (in_array("introducere", $this->user_rights) || $this->user_profile == 10){
				$flag=1;
				if (isset($arr[1]) && $arr[1] == 'introducere')
					$this->final_result = $this->IntroducereExpeditie($arr[2] ?? 0);
				else if(isset($arr[1]) && $arr[1] == 'json' && isset($arr[2]) && $arr[2]=='introducere')
					echo $this->JSON_Introducere();
				else if (isset($arr[1]) && $arr[1] == 'introducere_detalii')
					echo $this->IntroducereDetalii();
				else if (isset($arr[1]) && $arr[1] == 'valoare_expeditie')
					echo $this->ValoareExpeditie();
				else if (isset($arr[1]) && $arr[1] == 'valoare_return_expeditie')
					echo $this->ValoareReturnExpeditie();
				else if(isset($arr[1]) && $arr[1] == 'json' && isset($arr[2]) && $arr[2]=='localitati_old')
					echo $this->JSON_LocalitatiOld();
				else if(isset($arr[1]) && $arr[1] == 'json' && isset($arr[2]) && $arr[2]=='clienti_old')
					echo $this->JSON_ClientiOld();
				else if(isset($arr[1]) && $arr[1] == 'json' && isset($arr[2]) && $arr[2]=='clienti_cui')
					echo $this->JSON_ClientiCui();
				else if(isset($arr[1]) && $arr[1] == 'json' && isset($arr[2]) && $arr[2]=='platitori')
					echo $this->JSON_Platitori();

				else if (isset($arr[1]) && $arr[1] == 'adaugare_expeditie')
					echo $this->AdaugareExpeditie();
				else if (isset($arr[1]) && $arr[1] == 'editare_expeditie')
					echo $this->EditareExpeditieInitiala();
				else if (isset($arr[1]) && $arr[1] == 'sterge_expeditie')
					echo $this->StergereExpeditie();

				else if(isset($arr[1]) && $arr[1] == 'json' && isset($arr[2]) && $arr[2]=='agenti1_old')
					echo $this->JSON_Agenti1_Old();

				else if(isset($arr[1]) && $arr[1] == 'json' && isset($arr[2]) && $arr[2]=='agenti1_old_all')
					echo $this->JSON_Agenti1_Old_All();
			}

			if (in_array("istoric_scanare", $this->user_rights) || $this->user_profile == 10){
				$flag=1;
				if (isset($arr[1]) && $arr[1] == 'istoric_scanari')
					$this->final_result = $this->IstoricScanariExpeditii();
				else if(isset($arr[1]) && $arr[1] == 'json' && isset($arr[2]) && $arr[2]=='istoric_scanari')
					echo $this->JSON_IstoricScanariExpeditii();
				else if (isset($arr[1]) && $arr[1] == 'json' && isset($arr[2]) && $arr[2]=='istoric_checkpoints')
					echo $this->JSON_IstoricCheckpointsExpeditie();
				else if (isset($arr[1]) && $arr[1] == 'json' && isset($arr[2]) && $arr[2]=='istoric_status')
					echo $this->JSON_Istoric();
				else if (isset($arr[1]) && $arr[1] == 'export_istoric_scanari')
					echo $this->ExportIstoricScanariExpeditii();
			}

			if (in_array("confirmare", $this->user_rights) || $this->user_profile == 10){
				$flag=1;
				if (isset($arr[1]) && $arr[1] == 'urmarire')
					$this->final_result = $this->UrmarireExpeditii();
				else if(isset($arr[1]) && $arr[1] == 'json' && isset($arr[2]) && $arr[2]=='urmarire')
					echo $this->JSON_UrmarireExpeditii();
				else if (isset($arr[1]) && $arr[1] == 'detalii_istoric_expeditie')
					echo $this->DetaliiIstoricExpeditie();
				else if (isset($arr[1]) && $arr[1] == 'combo_comentarii_operatiune' && !empty($arr[2]))
					echo $this->ComboComentariiOperatiune(0,$arr[2],'style="width: 340px;" onchange="VerificareIstoric();"');
				else if (isset($arr[1]) && $arr[1] == 'modificare_istoric_expeditie')
					echo $this->ModificareIstoricExpeditie_Urmarire();
				else if (isset($arr[1]) && $arr[1] == 'export_urmarire_expeditii')
					echo $this->ExportUrmarireExpeditii();
			}

			if (in_array("liste_expeditii", $this->user_rights) || $this->user_profile == 10){
				$flag=1;
				if (isset($arr[1]) && $arr[1] == 'liste_expeditii')
					$this->final_result = $this->ListeExpeditii();
				else if(isset($arr[1]) && $arr[1] == 'json' && isset($arr[2]) && $arr[2]=='liste_expeditii')
					echo $this->JSON_ListeExpeditii();
				else if(isset($arr[1]) && $arr[1] == 'json' && isset($arr[2]) && $arr[2]=='liste_expeditii_detalii')
					echo $this->JSON_ListeExpeditiiDetalii();
				else if(isset($arr[1]) && $arr[1] == 'json' && isset($arr[2]) && $arr[2]=='liste_expeditii_detalii_client')
					echo $this->JSON_ListeExpeditiiDetaliiClient();
				else if(isset($arr[1]) && $arr[1] == 'json' && isset($arr[2]) && $arr[2]=='liste_expeditii_agenti')
					echo $this->JSON_ListeExpeditiiAgenti();
				else if(isset($arr[1]) && $arr[1] == 'vizualizare_liste_expeditie')
					echo $this->VizualizareListeExpeditii();

				else if(isset($arr[1]) && $arr[1] == 'export_liste_expeditii')
					echo $this->ExportListeExpeditii();
			}


			if (in_array("rapoarte_traseu", $this->user_rights) || $this->user_profile == 10){
				$flag=1;
				if (isset($arr[1]) && $arr[1] == 'rapoarte_traseu')
					$this->final_result = $this->RapoarteTraseu();
				else if(isset($arr[1]) && $arr[1] == 'json' && isset($arr[2]) && $arr[2]=='rapoarte_liste_expeditii')
					echo $this->JSON_RapoarteListeExpeditii();
				else if(isset($arr[1]) && $arr[1] == 'print_rapoarte_expeditii')
					echo $this->PrintRapoarteExpeditii();
				else if(isset($arr[1]) && $arr[1] == 'export_rapoarte_expeditii')
					echo $this->ExportRapoarteExpeditii();
			}

			if (in_array("activitate_centre", $this->user_rights) || $this->user_profile == 10){
				$flag=1;
					//activitate centre
				if (isset($arr[1]) && $arr[1] == 'activitate_centre')
					$this->final_result = $this->ActivitateCentre();
				else if(isset($arr[1]) && $arr[1] == 'json' && isset($arr[2]) && $arr[2]=='activitate_colectari')
					echo $this->JSON_ActivitateColectari();
				else if(isset($arr[1]) && $arr[1] == 'json' && isset($arr[2]) && $arr[2]=='activitate_livrari')
					echo $this->JSON_ActivitateLivrari();
				else if(isset($arr[1]) && $arr[1] == 'activitate_valori')
					echo $this->AfisareValori('activitate');
				else if(isset($arr[1]) && $arr[1] == 'print_activitate_centru')
					echo $this->PrintActivitateCentru();
				else if(isset($arr[1]) && $arr[1] == 'export_activitate_centru')
					echo $this->ExportActivitateCentru();

				else if(isset($arr[1]) && $arr[1] == 'rapoarte_centre')
					$this->final_result = $this->RapoarteCentre();
				else if(isset($arr[1]) && $arr[1] == 'export' && isset($arr[2]) && $arr[2]=='rapoarte_centre')
					echo $this->ExportRapoarteCentre();

				else if(isset($arr[1]) && $arr[1] == 'json' && isset($arr[2]) && $arr[2]=='rapoarte_centre')
					echo $this->JSON_RapoarteCentre();

				else if (isset($arr[1]) && $arr[1] == 'dif_exp_scan')
					$this->final_result = $this->DifExpScan();
				else if(isset($arr[1]) && $arr[1] == 'json' && isset($arr[2]) && $arr[2]=='dif_exp_scan')
					echo $this->JSON_DifExpScan();
				else if (isset($arr[1]) && $arr[1] == 'v_istoric_scanari')
					$this->final_result = $this->vIstoricScanari();
				else if (isset($arr[1]) && $arr[1] == 'json' && isset($arr[2]) && $arr[2] == 'v_istoric_scanari')
					echo $this->vJSON_IstoricScanari();
				else if (isset($arr[1]) && $arr[1] == 'v_export_expeditii_istoric_scanare')
					echo $this->vExportExpeditiiCsv();
			}

			if (in_array("colectari", $this->user_rights) || $this->user_profile == 10){
				$flag=1;
				if (isset($arr[1]) && $arr[1] == 'colectari')
					$this->final_result = $this->ColectariCentre();
				else if(isset($arr[1]) && $arr[1] == 'json' && isset($arr[2]) && $arr[2]=='colectari')
					echo $this->JSON_ColectariCentre();
				else if(isset($arr[1]) && $arr[1] == 'json' && isset($arr[2]) && $arr[2]=='colectari_detalii')
					echo $this->JSON_ListeColectariDetalii();
				else if(isset($arr[1]) && $arr[1] == 'json' && isset($arr[2]) && $arr[2]=='colectari_detalii_client')
					echo $this->JSON_ListeColectariDetaliiClient();
			}

			if (in_array("livrari", $this->user_rights) || $this->user_profile == 10){
				$flag=1;
				if (isset($arr[1]) && $arr[1] == 'livrari')
					$this->final_result = $this->LivrariCentre();
				else if(isset($arr[1]) && $arr[1] == 'json' && isset($arr[2]) && $arr[2]=='livrari')
					echo $this->JSON_LivrariCentre();
				else if(isset($arr[1]) && $arr[1] == 'json' && isset($arr[2]) && $arr[2]=='livrari_detalii')
					echo $this->JSON_ListeLivrariDetalii();
				else if(isset($arr[1]) && $arr[1] == 'json' && isset($arr[2]) && $arr[2]=='livrari_detalii_client')
					echo $this->JSON_ListeLivrariDetaliiClient();
			}

			if (in_array("rulaj_clienti", $this->user_rights) || $this->user_profile == 10){
				$flag=1;
				//rulaj expeditii clienti
				if (isset($arr[1]) && $arr[1] == 'rulaj_expeditii_clienti')
					$this->final_result = $this->RulajClienti();
				else if(isset($arr[1]) && $arr[1] == 'rulaj_client')
					echo $this->RulajClient();
			}

			if ($this->user_profile == 10) {
				$flag=1;
				//top clienti
				if (isset($arr[1]) && $arr[1] == 'top_clienti')
					$this->final_result = $this->TopClienti();
				else if(isset($arr[1]) && $arr[1] == 'json' && isset($arr[2]) && $arr[2]=='top_clienti')
					echo $this->JSON_TopClienti();
			}


			if (in_array("comenzi_borderouri", $this->user_rights) || $this->user_profile == 10){
				$flag=1;
				if (isset($arr[1]) && $arr[1] == 'borderouri_clienti')
					$this->final_result = $this->BorderouriClienti();
				else if(isset($arr[1]) && $arr[1] == 'json' && isset($arr[2]) && $arr[2]=='borderouri_clienti')
					echo $this->JSON_BorderouriClienti();
				else if(isset($arr[1]) && $arr[1] == 'json' && isset($arr[2]) && $arr[2]=='liste_expeditii_borderou')
					echo $this->JSON_ListeExpeditiiBorderouri($arr[3]);
				else if (isset($arr[1]) && $arr[1] == 'detalii_expeditie_client')
					echo $this->DetaliiExpeditieClient();

				else if (isset($arr[1]) && $arr[1] == 'borderou_stergere')
					echo $this->BorderouriStergere(intval($arr[2] ?? 0));
				else if (isset($arr[1]) && $arr[1] == 'borderou_receptionat_stergere')
					echo $this->BorderouriReceptionateStergere(intval($arr[2] ?? 0));
				else if (isset($arr[1]) && $arr[1] == 'borderou_receptie')
					echo $this->BorderouriReceptie(intval($arr[2] ?? 0));
			}

        if(empty($flag))
        	$this->final_result = $this->PageNotFound();
    }

	function DetaliiExpeditie(){

		$expeditie = $this->sanitize($_POST['expeditie'] ?? 0);
		if(ExpeditieDto::isPuisor($expeditie)) {
			$expeditie = ExpeditieDto::getAwbFromPuisor($expeditie);
		}
		else {
			$expeditie = intval($expeditie);
		}

		if($expeditie == 0)
			return $this->Error('Expeditie Invalida 2!!!');

		if(!(ExpeditieDto::isValidCod($expeditie) || ExpeditieDto::isOldSystemAwb($expeditie)))
			return $this->Error('Expeditie Invalida 3!!!');

		if(false === ($exp = $this->GetValues($expeditie)))
			return $this->Error('Expeditie Invalida 4!!!');

        $vars = ExpeditieDto::sqlExpToUi(array_merge($exp, ['can_pret_impus' => $this->can_pret_impus]), $this->user_profile, $this->user_rights);

		$vars['MOD_PLATA'] = ExpeditieDto::MOD_PLATA[$exp['mod_plata'] ?? 0] ?? ExpeditieDto::MOD_PLATA[0];

		$vars['TIP_OBJ_1'] = $exp['tip_obj'] == 1 ? 1 : 0;
        $vars['TIP_OBJ_2'] = $exp['tip_obj'] == 2 ? $exp['piese'] : 0;
        $vars['TIP_OBJ_3'] = $exp['tip_obj'] == 3 ? 1 : 0;

        $vars['LIV_SAMB'] = (!empty($exp['liv_samb'])) ? 'DA' : 'NU';
		$vars['RET_AMB'] = (!empty($exp['ret_amb'])) ? 'DA' : 'NU';
		$vars['RET_COLET'] = (!empty($exp['ret_colet'])) ? 'DA' : 'NU';
		$vars['LIV_SED'] = (!empty($exp['liv_sed'])) ? 'DA' : 'NU';
		$vars['RET_NT'] = (!empty($exp['ret_nt'])) ? 'DA' : 'NU';
		$vars['RET_DOC'] = (!empty($exp['ret_doc'])) ? 'DA' : 'NU';
		$vars['COPEN'] = (!empty($exp['copen'])) ? 'DA' : 'NU';
		$vars['SMS'] = (!empty($exp['sms'])) ? 'DA' : 'NU';

		$vars['TEXT_CODURI_BARE'] = 'Coduri Bare';
		$vars['CODURI'] = implode(' ', ExpeditieDto::getPuisoriForAwb($expeditie, $exp['colete']));

		if(!empty($exp['tip_exp'])){
			if($exp['tip_exp'] == 1){
				$vars['DETALII_PLATA'] = 'Retur NT pentru <a href="'.$this->config['http'].'expeditii/introducere/'.$exp['referire'].'">'.$exp['referire'].'</a>';
			}else if($exp['tip_exp'] == 2){
				$vars['DETALII_PLATA'] = 'Retur Doc pentru <a href="'.$this->config['http'].'expeditii/introducere/'.$exp['referire'].'">'.$exp['referire'].'</a>';
			}else if($exp['tip_exp'] == 3){
				$vars['DETALII_PLATA'] = 'Ramburs pentru <a href="'.$this->config['http'].'expeditii/introducere/'.$exp['referire'].'">'.$exp['referire'].'</a>';
			}else if($exp['tip_exp'] == 4){
				$vars['DETALII_PLATA'] = 'Expeditie Interna';
			}else if($exp['tip_exp'] == 5){
				$vars['DETALII_PLATA'] = 'Returnare pentru <a href="'.$this->config['http'].'expeditii/introducere/'.$exp['referire'].'">'.$exp['referire'].'</a>';
			}else if($exp['tip_exp'] == 6){
				$vars['DETALII_PLATA'] = 'Retur ambalaj pentru <a href="'.$this->config['http'].'expeditii/introducere/'.$exp['referire'].'">'.$exp['referire'].'</a>';
			}else if($exp['tip_exp'] == parent::TIP_EXP_BO_RBS_CASH){
				$vars['DETALII_PLATA'] = 'Borderou RBS cash';
				$vars['TEXT_CODURI_BARE'] = 'Rambursuri cash in acest borderou';
				$arr_awb_rbs = explode(',', $exp['awb_rbs'] ?? '');
				foreach($arr_awb_rbs as $k => $v){
					$arr_awb_rbs[$k] = '<a href="#" style="text-decoration:none;" onclick="schimbaExpeditie('.$v.');return false;">'.$v.'</a>';
				}
				$vars['CODURI'] = implode(' ', $arr_awb_rbs);
			}
			if($exp['referire'] > 0)
				$vars['expeditii_asociate'] = $this->GetExpeditiiLinks($exp['referire'], $expeditie, $exp['ref_bo']);
			else if($exp['tip_exp'] !=  parent::TIP_EXP_BO_RBS_CASH)
				error_log("expeditie : " . $expeditie . " tip : " . $exp['tip_exp']. " cu referire 0");
		}
		else {
			$vars['expeditii_asociate'] = $this->GetExpeditiiLinks($exp['expeditie'], $expeditie);
		}

		$vars['OBSERVATII'] = $exp['observatii'] . "<br/>" . $exp['detalii_doc'];
		$vars['SHOW_JPG'] = "display:none";
		if(!empty($exp['folder'])) $vars['SHOW_JPG'] = "";

		$vars['SHOW_EDIT'] = "";
		// || $exp['tip_exp'] > 0
		if(!($this->user_profile == 10 || is_array($this->user_rights) && in_array('editare', $this->user_rights)))
			$vars['SHOW_EDIT'] = "display:none";

		$vars['detalii_scanare'] = $this->GetStatusScanare($expeditie);

        return $this->Parse($this->page_prefix . 'detalii_expeditie.html', $vars);
    }

	function GetExpeditiiLinks($expeditie, $pexpeditie, $ref_bo = 0){
		$expeditii = $this->GetExpeditiiAsociate($expeditie);
		if(count($expeditii) == 1)
			return;
		$html = '';
		foreach ($expeditii as $expeditie) {
			$activ = ($pexpeditie == $expeditie['expeditie']) ? "activ" : "";
			$html .= '<a href="#" class="datalii_exp_popup '.$activ.'" onclick="schimbaExpeditie('.$expeditie['expeditie'].');return false;">'. (ExpeditieDto::TIP_EXP[$expeditie['tip_exp'] ?? 0] ?? "unknown").': '.$expeditie['expeditie'].'</a>';
		}
		if($ref_bo > 0)
			$html .= '<a href="#" class="datalii_exp_popup '.($ref_bo == $pexpeditie).'" onclick="schimbaExpeditie('.$ref_bo.');return false;">'. (ExpeditieDto::TIP_EXP[parent::TIP_EXP_BO_RBS_CASH] ?? "unknown").': '.$ref_bo.'</a>';
		return $html;
	}

	function GetExpeditiiAsociate($expeditie){
		return $this->db->QFetchRowArray("select expeditie, tip_exp from {$this->tables['exp_prelucrate']} where (expeditie = {$expeditie}  and anulata = 0) or (referire = {$expeditie} and anulata = 0)");
	}


	function GetStatusScanare($cod){
		$query = "SELECT a.cod, a.tip, a.data, b.nume as centru, c.nume_ag as curier, d.denumire, e.denumire as tip_scanare
			FROM scanari_coduri as a
			LEFT JOIN centre as b ON a.centru = b.id
			LEFT JOIN agenti as c ON a.curier = c.cod_ag
			LEFT JOIN rute as d ON a.ruta = d.id
			left join checkpoints as e ON e.id = a.tip
			WHERE a.is_awb = 1 and a.expeditie = :cod
			ORDER BY a.data DESC LIMIT 1";
        $sql = $this->db->QFetchArray($query, ['cod'=>$cod]);
		$data= 'Data';
		$tip_scanare = 'Tip Scanare';
		$centru='Centru';
		$curier='Curier';
		if(!empty($sql['data'])) $data = $sql['data'];
		if(!empty($sql['tip_scanare'])) $tip_scanare = $sql['tip_scanare'];
		if(!empty($sql['centru'])) $centru = $sql['centru'];
		if(!empty($sql['curier'])) $curier = $sql['curier'];
		return $data.' - '.$tip_scanare.' - '.$centru.' - '.$curier;
	}

/*/////////////////////////////////////////////////////////////
				 * START CAUTARE EXPEDITIE
/////////////////////////////////////////////////////////////*/
    function CautareExpeditii($alex = 0) {
        $this->vars['title_page'] = 'Cautare Expeditie';
        $vars = [];
        $vars['data_start'] = date('d.m.Y');
        $vars['data_final'] = date('d.m.Y');

		$vars['raport_alex'] = $alex;
		if($alex == 1)
			$this->vars['title_page'] = 'Rapoarte Alex';

        if($this->user_centru_id == 47){
            $this->vars['SELECTIE_MOD_PRINTARE'] = "Selectie";
        }

        return $this->Parse($this->page_prefix . 'cautare.html', $vars);
    }

	function JSON_CautareExpeditii() {
        $cond ="ep.anulata = 0 ";
        $flag=0;
		$responce = new StdClass();
        if(isset($_GET['data_start']) && isset($_GET['data_final'])){
            $data_start = $this->TransformDate($_GET['data_start']);
            $data_final = $this->TransformDate($_GET['data_final']);
			$cond .= " AND ep.data_expeditie between '{$data_start}' AND '{$data_final}'";
            $flag=1;
        }
        if(isset($_GET['operatiune']) && isset($_GET['search'])){
            if(intval($_GET['operatiune']) == 1){
                $cond .= " AND ( ep.expeditie = ".intval($_GET['search']).")";
            }
			else if(intval($_GET['operatiune']) == 3){
				$cond .= " AND ( b.invoice like ".$this->db->escapeString(Backend::sSanitizeCleanEdges($_GET['search'])).")";
			}
            $flag=1;
        }
        if(!empty($_GET['expeditor_localitate'])){
            $cond .= " AND lce.nume_lc=".$this->db->escapeString(Backend::sSanitizeCleanEdges($_GET['expeditor_localitate']));
            $flag=1;
        }
        if(!empty($_GET['destinatar_localitate'])){
            $cond .= " AND lcd.nume_lc=".$this->db->escapeString(Backend::sSanitizeCleanEdges($_GET['destinatar_localitate']));
            $flag=1;
        }
        if(!empty($_GET['expeditor'])){
            $cond .= " AND cle.nume=".$this->db->escapeString(Backend::sSanitizeCleanEdges($_GET['expeditor']));
            $flag=1;
        }
        if(!empty($_GET['destinatar'])){
            $cond .= " AND cld.nume=".$this->db->escapeString(Backend::sSanitizeCleanEdges($_GET['destinatar']));
            $flag=1;
        }
        if(!empty($_GET['curier_livrare'])){
            $cond .= " AND ep.curier_livrare_id=".intval($_GET['curier_livrare']);
            $flag=1;
        }
		if(!empty($_GET['curier_preluare'])){
            $cond .= " AND ep.curier_preluare_id=".intval($_GET['curier_preluare']);
            $flag=1;
        }
        if(!empty($_GET['operator'])){
            $cond .= " AND u.user = ".$this->db->escapeString(Backend::sSanitizeCleanEdges($_GET['operator']));
            $flag=1;
		}

	   if(!empty($_GET['livrare'])){//tip livrare
			if(intval($_GET['livrare']) == 2){
				$cond .= " AND ep.liv_samb = 1";
				$flag=1;
			}else if(intval($_GET['livrare']) == 4){
				$cond .= " AND ep.liv_sed = 1";
				$flag=1;
			}
        }

		if(!empty($_POST['expeditii'])){
			$post_expeditii = parent::ValidareExpeditiiCurata($_POST['expeditii']);
			if(!empty($post_expeditii)) {
				$cond .= " AND ep.expeditie in (".$post_expeditii.")";
				$flag=1;
			}
		}

		if(empty($flag)) $cond = "1=2";

		$searchOn = '';
		//start generare conditie
		if(isset($_POST['_search']))
        	$searchOn = $this->Strip($_POST['_search']);
        if ($searchOn == 'true') {
            $searchstr = $this->Strip($_POST['filters']);
            $cond .= $this->constructWhere($searchstr);
		}

		$cond = preg_replace("/ced.nume/i", "IF(cld.zona_id > 0 and cldc.id > 0, cldc.nume, ced.nume)", $cond);

		//if($this->user_id == parent::MARIAN)
        	//error_log(print_r($cond, true));

		$page = intval($_POST['page'] ?? 1);
		$limit = intval($_POST['rows'] ?? 100);
		$sidx = trim($this->sanitize($_POST['sidx'] ?? 1));
		$sord = trim($this->sanitize($_POST['sord'] ?? 'asc'));

		$query = "SELECT COUNT(ep.cod_expeditie) as nr
			from {$this->tables['exp_prelucrate']} ep
			left join agenti agp ON agp.cod_ag = ep.curier_preluare_id
            left join agenti agl ON agl.cod_ag = ep.curier_livrare_id
            left join clienti cle on cle.cod_cl = ep.expeditor_id
            left join clienti cld on cld.cod_cl = ep.destinatar_id
			LEFT JOIN zones cldz ON cldz.id = cld.zona_id
        	LEFT JOIN centre cldc on cldc.id = cldz.centru_id
            left join localitati lce ON lce.cod_lc = cle.cod_lc
            left join localitati lcd ON lcd.cod_lc = cld.cod_lc
            left join centre cee ON cee.id = lce.cod_centru
            left join centre ced ON ced.id = lcd.cod_centru
			left join exp_facturi b on ep.idfact = b.id
			left join users u on u.id = ep.operator_id
			WHERE {$cond}";
        $result = $this->db->QFetchArray($query);
        $count = !empty($result['nr']) ? $result['nr'] : 0;

        if( $count >0 ) {$total_pages = ceil($count/$limit); }
        else { $total_pages = 0; }
        if ($page > $total_pages) $page=$total_pages;
        if ($limit<0) $limit = 0;
        $start = $limit*$page - $limit; // do not put $limit*($page - 1)
        if ($start<0) $start = 0;
			$query = "SELECT ep.expeditie, ep.tip_exp, cle.nume as expeditor, cld.nume as destinatar, 
			lce.nume_lc as expeditor_localitate,lcd.nume_lc as destinatar_localitate,
			IF(cle.zona_id > 0 and clec.id > 0, clec.nume, cee.nume) as expeditor_centru, 
        	IF(cle.zona_id > 0 and clec.id > 0, clec.label, cee.label) as expeditor_centru_cod,
			IF(cld.zona_id > 0 and cldc.id > 0, cldc.nume, ced.nume) as destinatar_centru, 
			IF(cld.zona_id > 0 and cldc.id > 0, cldc.label, ced.label) as destinatar_centru_cod,
			IF(cld.zona_id > 0 and cldc.id > 0, cldz.name, '') as destinatar_centru_zona,
			ep.moneda, 
			ep.data_expeditie,ep.tip_obj, ep.piese, ep.plicuri,ep.colete,ep.paleti,ep.greutate,ep.km_preluare,ep.km_livrare,
			ep.valoare_asigurata,ep.ramburs,ep.tip_plata,ep.valoare_totala_expeditie,ep.mod_plata,
			agp.nume_ag as curier_preluare, agl.nume_ag as curier_livrare, u.user as operator,
			ep.observatii
            from {$this->tables['exp_prelucrate']} ep
						left join agenti agp ON agp.cod_ag = ep.curier_preluare_id
            left join agenti agl ON agl.cod_ag = ep.curier_livrare_id
            left join clienti cle on cle.cod_cl = ep.expeditor_id
			LEFT JOIN zones clez ON clez.id = cle.zona_id
        	LEFT JOIN centre clec on clec.id = clez.centru_id
            left join clienti cld on cld.cod_cl = ep.destinatar_id
			LEFT JOIN zones cldz ON cldz.id = cld.zona_id
        	LEFT JOIN centre cldc on cldc.id = cldz.centru_id
            left join localitati lce ON lce.cod_lc = cle.cod_lc
            left join localitati lcd ON lcd.cod_lc = cld.cod_lc
            left join centre cee ON cee.id = lce.cod_centru
            left join centre ced ON ced.id = lcd.cod_centru
            left join exp_facturi b on ep.idfact = b.id
			left join users u on u.id = ep.operator_id
            WHERE {$cond}
			ORDER BY " . $sidx . " " . $sord . " LIMIT " . $start . " , " . $limit;
		//error_log($query);
        $sql = $this->db->QFetchRowArray($query);
        if (!empty($sql)) {
            foreach ($sql as $key => $row) {
                $row['moneda'] = ExpeditieDto::MONEDA[$row['moneda']] ?? $row['moneda'];
				$row['opt'] = '';
            	if(!empty($row['liv_samb'])) $row['opt'] = 'LS';
				if($row['mod_plata'] > 0 && (!is_array($this->user_rights) || !in_array('preturi', $this->user_rights))) {
					$row['valoare_totala_expeditie'] = 'NaN';
				}
				$responce->rows[$key]['id'] = $row['expeditie'];
                $responce->rows[$key]['cell'] = array($row['expeditie'], $row['tip_exp'], strtoupper($row['expeditor']),strtoupper($row['destinatar']),strtoupper($row['expeditor_centru']),strtoupper($row['destinatar_centru']),$row['data_expeditie'],$row['plicuri'],$row['colete'],$row['paleti'],$row['greutate'],$row['km_preluare'],$row['km_livrare'],$row['valoare_asigurata'],$row['ramburs'],$row['tip_plata'],$row['valoare_totala_expeditie'],$row['curier_preluare'],$row['curier_livrare'],$row['observatii'],$row['opt']);
            }
        }
		$responce->page = $page;
        $responce->total = $total_pages;
        $responce->records = $count;

        return json_encode($responce);
    }

	//ExporExpeditii

	 function ExportCautareExpeditii() {
		ini_set('memory_limit', '2G');
		$cond ="ep.anulata = 0 ";
        $flag=0;
        if(isset($_POST['data_start']) && isset($_POST['data_final'])){
            $data_start = $this->TransformDate($_POST['data_start']);
            $data_final = $this->TransformDate($_POST['data_final']);
			$cond .= " AND ep.data_expeditie between '{$data_start}' AND '{$data_final}'";
            $flag=1;
        }
        if(isset($_POST['operatiune']) && isset($_POST['search'])){
            if(intval($_POST['operatiune'])==1){
                $cond .= " AND ( ep.expeditie=".intval($_POST['search']).")";
            }else if(intval($_POST['operatiune'])==3){
				$cond .= " AND ( b.invoice like ".$this->db->escapeString($this->sanitize($_POST['search'])).")";
			}
            $flag=1;
        }
        if(!empty($_POST['expeditor_localitate'])){
            $cond .= " AND lce.nume_lc like ".$this->db->escapeString(Backend::sSanitizeCleanEdges($_POST['expeditor_localitate']));
            $flag=1;
        }
        if(!empty($_POST['destinatar_localitate'])){
            $cond .= " AND lcd.nume_lc=".$this->db->escapeString(Backend::sSanitizeCleanEdges($_POST['destinatar_localitate']));
            $flag=1;
        }
        if(!empty($_POST['expeditor'])){
            $cond .= " AND cle.nume=".$this->db->escapeString(Backend::sSanitizeCleanEdges($_POST['expeditor']));
            $flag=1;
        }
        if(!empty($_POST['destinatar'])){
            $cond .= " AND cld.nume=".$this->db->escapeString(Backend::sSanitizeCleanEdges($_POST['destinatar']));
            $flag=1;
        }
        if(!empty($_POST['curier_livrare'])){
            $cond .= " AND ep.curier_livrare_id=".intval($_POST['curier_livrare']);
            $flag=1;
        }
		if(!empty($_POST['curier_preluare'])){
            $cond .= " AND ep.curier_preluare_id=".intval($_POST['curier_preluare']);
            $flag=1;
        }
        if(!empty($_POST['operator'])){
            $cond .= " AND ep.operator=".$this->db->escapeString(Backend::sSanitizeCleanEdges($_POST['operator']));
            $flag=1;
		}

	   if(!empty($_POST['livrare'])){//tip livrare
			if($_POST['livrare']==2){
				$cond .= " AND ep.liv_samb=1";
				$flag=1;
			}else if($_POST['livrare']==4){
				$cond .= " AND ep.liv_sed=1";
				$flag=1;
			}
        }

		if(!empty($_POST['expeditii'])){
			$post_expeditii = parent::ValidareExpeditiiCurata($_POST['expeditii']);
			if(!empty($post_expeditii)) {
				$cond .= " AND ep.expeditie in (".$post_expeditii.") ";
				$flag=1;
			}
		}

		if(empty($flag)) $cond = "1=2";

		if(!empty($_POST['filters'])){
			require_once 'jqGridService.php';
			$cond .= jqGridService::getJqGridFiltersCondition($_POST['filters']);
			$flag=1;
		}

		$cond = preg_replace("/ced.nume/i", "IF(cld.zona_id > 0 and cldc.id > 0, cldc.nume, ced.nume)", $cond);
		return $this->ExportExpeditiiCsv($cond);
    }

	 function ExportExpeditii($cond) {
		$data = date('d/m/Y');
 		$societate = 'Dragon Star Curier';
        $document = 'Lista Expeditii';

        $spreadsheet = new Spreadsheet();
        $spreadsheet->getProperties()->setCreator($societate)
            ->setLastModifiedBy($societate)
            ->setTitle($document)
            ->setSubject($document)
            ->setDescription($document)
            ->setKeywords($document)
            ->setCategory($document);
        $spreadsheet->getDefaultStyle()->getFont()->setName('Arial');
        $spreadsheet->getDefaultStyle()->getFont()->setSize(11);
		$worksheet = $spreadsheet->getActiveSheet();

		foreach(range('A','Y') as $v) {
			$worksheet->getStyle($v.'1')->getFont()->setBold(true);
			$worksheet->getStyle($v.'1')->getFont()->setSize(13);
			$worksheet->getColumnDimension($v)->setWidth(30);
		}

		$worksheet->setCellValue('A1','Nr. NT');
        $worksheet->setCellValue('B1','Data colectarii');
        $worksheet->setCellValue('C1','Expeditor');
        $worksheet->setCellValue('D1','Centru expeditor');
        $worksheet->setCellValue('E1','Km. colectare');
		$worksheet->setCellValue('F1','Destinatar');
		$worksheet->setCellValue('G1','Centru destinatar');
		$worksheet->setCellValue('H1','Km. livrare');
		$worksheet->setCellValue('I1','Platitor');
		$worksheet->setCellValue('J1','Centru platitor');
		$worksheet->setCellValue('K1','Piese');
		$worksheet->setCellValue('L1','Plic');
		$worksheet->setCellValue('M1','Palet');
		$worksheet->setCellValue('N1','Greutate');
		$worksheet->setCellValue('O1','Tip expeditie');
		$worksheet->setCellValue('P1','Tarif baza');
		$worksheet->setCellValue('Q1','Tarif kg.');
		$worksheet->setCellValue('R1','Tarif km. ext.');
		$worksheet->setCellValue('S1','Valoare asigurata');
		$worksheet->setCellValue('T1','Proc. asig.');
		$worksheet->setCellValue('U1','Ramburs');
		$worksheet->setCellValue('V1','Tip plata');
		$worksheet->setCellValue('W1','Proc. ramb');
		$worksheet->setCellValue('X1','Tarif asigurare');
		$worksheet->setCellValue('Y1','Total valoare');
		$worksheet->setCellValue('Z1','Moneda');

		ini_set('memory_limit', '1228M');
		set_time_limit(600);

        $query = "SELECT ep.expeditie, ep.data_expeditie, ep.tip_exp,
			cle.nume as expeditor, cld.nume as destinatar, clp.nume as platitor,
			lce.nume_lc as expeditor_localitate,lcd.nume_lc as destinatar_localitate, lcp.nume_lc as platitor_localitate,
			IF(cle.zona_id > 0 and clec.id > 0, clec.nume, cee.nume) as expeditor_centru, 
        	IF(cle.zona_id > 0 and clec.id > 0, clec.label, cee.label) as expeditor_centru_cod,
			IF(cld.zona_id > 0 and cldc.id > 0, cldc.nume, ced.nume) as destinatar_centru, 
			IF(cld.zona_id > 0 and cldc.id > 0, cldc.label, ced.label) as destinatar_centru_cod,
			IF(cld.zona_id > 0 and cldc.id > 0, cldz.name, '') as destinatar_centru_zona,
			IF(clp.zona_id > 0 and clpc.id > 0, clpc.nume, cep.nume) as platitor_centru, 
			IF(clp.zona_id > 0 and clpc.id > 0, clpc.label, cep.label) as platitor_centru_cod,
			ep.data_expeditie, ep.tip_obj, ep.piese, ep.plicuri,ep.colete,ep.paleti,ep.greutate,ep.km_preluare,ep.km_livrare,
			ep.valoare_asigurata, ep.ramburs,
			CASE ep.tip_plata WHEN 0 THEN 'cash'  WHEN 1 THEN 'bo' WHEN 2 THEN 'cec' WHEN 3 THEN 'cont' ELSE '' END as tipPlata,
			ep.procent_asigurare, ep.ramburs_procent,
			ep.valoare_expeditie, ep.val_greutate, ep.val_km, ep.val_asig, ep.valoare_totala_expeditie, ep.mod_plata, ep.moneda,
			agp.nume_ag as curier_preluare, agl.nume_ag as curier_livrare,
			ep.observatii, ep.detalii_doc, ep.operatiune,
			b.invoice, b.sumamnt
            from {$this->tables['exp_prelucrate']} ep
			left join agenti agp ON agp.cod_ag = ep.curier_preluare_id
            left join agenti agl ON agl.cod_ag = ep.curier_livrare_id
            left join clienti cle on cle.cod_cl = ep.expeditor_id
			left join clienti cld on cld.cod_cl = ep.destinatar_id
			left join clienti clp on clp.cod_cl = ep.platitor_id
			LEFT JOIN zones clez ON clez.id = cle.zona_id
        	LEFT JOIN centre clec on clec.id = clez.centru_id
			LEFT JOIN zones cldz ON cldz.id = cld.zona_id
        	LEFT JOIN centre cldc on cldc.id = cldz.centru_id
			LEFT JOIN zones clpz ON clpz.id = clp.zona_id
        	LEFT JOIN centre clpc on clpc.id = clpz.centru_id
            left join localitati lce ON lce.cod_lc = cle.cod_lc
            left join localitati lcd ON lcd.cod_lc = cld.cod_lc
			left join localitati lcp ON lcp.cod_lc = clp.cod_lc
            left join centre cee ON cee.id = lce.cod_centru
            left join centre ced ON ced.id = lcd.cod_centru
			left join centre cep ON cep.id = lcp.cod_centru
            left join exp_facturi b on b.id = ep.idfact
            WHERE {$cond}
            ORDER BY ep.data_expeditie, cle.nume ASC";
		//print_r($query);die;
        $sql = $this->db->QFetchRowArray($query);


        //compun raspunsul
        if (!empty($sql)) {
        	$rand = 2;
            foreach ($sql as $key => $row) {

                $row['data_expeditie'] = $this->CreateDate($row['data_expeditie']);

				if(!empty($row['plicuri'])){
					$row['plicuri'] = 'DA';
					$row['piese'] = 1;
				}else if(!empty($row['paleti'])){
					$row['paleti'] = 'DA';
					$row['piese'] = 1;
				}else{
					$row['paleti'] = 'NU';
					$row['plicuri'] = 'NU';
					$row['piese'] = $row['colete'];
				}
				$row['tarif_baza'] = $row['valoare_expeditie'];
				$row['tarif_kg'] = $row['val_greutate'];
				$row['tarif_km'] = $row['val_km'];
				$row['tarif_asig'] = $row['val_asig'];
				$row['tarif_total'] = $row['valoare_totala_expeditie'];
				$row['statut_actual'] = $row['operatiune'];

				$worksheet->setCellValue('A'.($rand+$key),$row['expeditie']);
		        $worksheet->setCellValue('B'.($rand+$key),$row['data_expeditie']);
		        $worksheet->setCellValueExplicit('C'.($rand+$key),$row['expeditor'].'('.$row['expeditor_localitate'].')', \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
		        $worksheet->setCellValue('D'.($rand+$key),$row['expeditor_centru']);
		        $worksheet->setCellValue('E'.($rand+$key),$row['km_preluare']);
				$worksheet->setCellValueExplicit('F'.($rand+$key),$row['destinatar'].'('.$row['destinatar_localitate'].')', \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
				$worksheet->setCellValue('G'.($rand+$key),$row['destinatar_centru']);
				$worksheet->setCellValue('H'.($rand+$key),$row['km_livrare']);
				$worksheet->setCellValueExplicit('I'.($rand+$key),$row['platitor'], \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
				$worksheet->setCellValue('J'.($rand+$key),$row['platitor_centru']);
				$worksheet->setCellValue('K'.($rand+$key),$row['piese']);
				$worksheet->setCellValue('L'.($rand+$key),$row['plicuri']);
				$worksheet->setCellValue('M'.($rand+$key),$row['paleti']);
				$worksheet->setCellValue('N'.($rand+$key),$row['greutate']);
				$worksheet->setCellValue('O'.($rand+$key),ExpeditieDto::TIP_EXP[$row['tip_exp']] ?? "unknown");
				$worksheet->setCellValue('P'.($rand+$key),$row['tarif_baza']);
				$worksheet->setCellValue('Q'.($rand+$key),$row['tarif_kg']);
				$worksheet->setCellValue('R'.($rand+$key),$row['tarif_km']);
				$worksheet->setCellValue('S'.($rand+$key),$row['valoare_asigurata']);
				$worksheet->setCellValue('T'.($rand+$key),$row['procent_asigurare']);
				$worksheet->setCellValue('U'.($rand+$key),$row['ramburs']);
				$worksheet->setCellValue('V'.($rand+$key),$row['tipPlata']);
				$worksheet->setCellValue('W'.($rand+$key),$row['ramburs_procent']);
				$worksheet->setCellValue('X'.($rand+$key),$row['tarif_asig']);
				$worksheet->setCellValue('Y'.($rand+$key),$row['tarif_total']);
				$worksheet->setCellValue('Z'.($rand+$key),ExpeditieDto::MONEDA[$row['moneda']] ?? $row['moneda']);

            }
        }

        $data = date('d_m_Y');
        $filename = 'Lista_Expeditii_'.$data.'.xlsx';

        $this->download_send_headers_xls($filename);
        $writer = new Xlsx($spreadsheet);
        $writer->save('php://output');
		die;
    }

  /* /////////////////////////////////////////////////////////////
				 * END CAUTARE EXPEDITIE
/////////////////////////////////////////////////////////////*/

/*/////////////////////////////////////////////////////////////
				 START ISTORIC
/////////////////////////////////////////////////////////////*/

	function JSON_Istoric() {
		$responce = new StdClass();
		$responce->page = 1;
        $responce->total = 0;
        $responce->records = 0;
        if(!isset($_REQUEST['expeditie'])){
            return json_encode($responce);
        }
		$expeditie = intval($_REQUEST['expeditie']);
		if($expeditie == 0) return json_encode($responce);
		$cod_expeditie = 0;
		$query = "SELECT cod_expeditie from {$this->tables['exp_prelucrate']} WHERE expeditie = {$expeditie}";
        $sql = $this->db->QFetchArray($query);
		if(empty($sql)){
            return json_encode($responce);
		}else{
			$cod_expeditie = $sql['cod_expeditie'];
		}
        $cond = " a.cod_exp = ".$cod_expeditie;

        $page = intval($_REQUEST['page'] ?? 1);
		$limit = intval($_REQUEST['rows'] ?? 20);
		$sidx = trim($this->sanitize($_REQUEST['sidx'] ?? 1));
		$sord = trim($this->sanitize($_REQUEST['sord'] ?? 'asc'));

        //start generare conditie
        $searchOn = $this->Strip($_REQUEST['_search']);
        if ($searchOn == 'true') {
            $searchstr = $this->Strip($_REQUEST['filters']);
            $cond .= $this->constructWhere($searchstr);
        }else {
            $_SESSION['conditie_istoric_expeditii'] = '';
        }

        $totalrows = isset($_REQUEST['totalrows']) ? $_REQUEST['totalrows']: false;
        if($totalrows){ $limit = $totalrows; }

        $query = "SELECT COUNT(a.cod_ist) as nr
            FROM {$this->tables['ist_exp']} a
            INNER JOIN {$this->tables['users']} b ON a.operator = b.id
            LEFT JOIN {$this->tables['op']} c ON a.operatiune = c.cod_op
            WHERE {$cond}";
			//echo $query;die;
        $result = $this->db->QFetchArray($query);
        $count = !empty($result['nr']) ? $result['nr'] : 0;

        if( $count >0 ) {
            $total_pages = ceil($count/$limit); }
        else { $total_pages = 0; }
        if ($page > $total_pages) $page=$total_pages;
        if ($limit<0) $limit = 0;
        $start = $limit*$page - $limit; // do not put $limit*($page - 1)
        if ($start<0) $start = 0;

        $query = "SELECT a.cod_ist,a.data,a.data_op,c.op_ro,b.user
            FROM {$this->tables['ist_exp']} a
            INNER JOIN {$this->tables['users']} b ON a.operator = b.id
            LEFT JOIN {$this->tables['op']} c ON a.operatiune = c.cod_op
            WHERE {$cond}
            ORDER BY " . $sidx . " " . $sord . " LIMIT " . $start . " , " . $limit;
        $sql = $this->db->QFetchRowArray($query);
        //compun raspunsul
        $responce->page = $page;
        $responce->total = $total_pages;
        $responce->records = $count;
        $afiseaza_detalii = false;
        if($this->user_profile == 10 || $this->user_id == parent::CAMELIA_TELER){
            $afiseaza_detalii = true;
		}

        if (!empty($sql)) {
            foreach ($sql as $key => $row) {
                $responce->rows[$key]['id'] = $row['cod_ist'];
                $extra = "";
                if($afiseaza_detalii){
                    $extra = json_encode($this->getIstoricVals($row['cod_ist']));
                }
                $responce->rows[$key]['cell'] = array($row['op_ro'], $row['data'], $row['user'],$row['data_op'], $extra);
            }
        }
        return json_encode($responce);
    }


    function PrintExpeditieTCPDF($nr_exp=0){
		require_once "NewAwbPdf.php";

        if(empty($nr_exp)) return $this->Error('Expeditie invalida!');

		$filename = 'NT-'.$nr_exp.'.pdf';
		// $pdf = new NewAwbPdf($vars);
		$pdf = new NewAwbPdf();
		$this->GenerareTCPDF($pdf, $nr_exp);
		// move pointer to last page
		$pdf->lastPage();

		//I: send the file inline to the browser. The plug-in is used if available. The name given by filename is used when one selects the "Save as" option on the link generating the PDF.
		//D: send to the browser and force a file download with the name given by filename.
		$pdf->Output($filename,'D');
	    exit;

    }

    function DownloadConfirmare($tip = 0){
		$expeditie = intval($this->sanitize($_GET['expeditie'] ?? 0));
		if($expeditie == 0)
			die("Please specify nt number for download.");

		require_once "download.php";
		$d = new Download($this->db, $expeditie, $tip);
		$d->sendFile();
		exit;
    }

	//Printare multipla
	function PrintareMultiplaCautareExpeditii($exps=0){
		$exps = json_decode($exps,true);
        if(count($exps) == 0) return "0 selected";

		require_once "NewAwbPdf.php";

        $filename = 'NT-printare_multipla.pdf';
		$pdf = new NewAwbPdf();
		$pdf->SetTitle('NT-printare_multipla');
		$pdf->SetSubject('NT-printare_multipla');

        foreach( $exps as $key => $exp) {
        	$this->GenerareTCPDF($pdf,$exp);
		}

		// move pointer to last page
		$pdf->lastPage();

		//I: send the file inline to the browser. The plug-in is used if available. The name given by filename is used when one selects the "Save as" option on the link generating the PDF.
		//D: send to the browser and force a file download with the name given by filename.
		$type = 'I';
		if($this->print_awb == 1) $type = 'D';
		$pdf->Output($filename,$type);
	    exit;
	}

	//Printare multipla
	function PrintareMultiplaPuisoriCautareExpeditii($exps=0 , $autocolant = false){
		require_once "NewAwbPdf.php";

		$exps = json_decode($exps,true);
		if(count($exps) == 0) return "0 selected";

		$filename = 'NT-printare_multipla_puisori.pdf';
		$pdf = new NewAwbPdf(array('autocolant' => $autocolant));
		$pdf->doarPuisori = true;

        if($autocolant) {
            $filename = 'NT-printare_multipla_puisori_autocolant.pdf';
        }

		foreach( $exps as $key => $exp)
		{
			$this->GenerareTCPDF($pdf,$exp);
		}

		// move pointer to last page
		$pdf->lastPage();

		//I: send the file inline to the browser. The plug-in is used if available. The name given by filename is used when one selects the "Save as" option on the link generating the PDF.
		$pdf->Output($filename, 'I');
		exit;

	}

	//Printare multipla master + puisori
    function PrintareMultiplaMasterPuisoriCautareExpeditii($exps=0){
		require_once "NewAwbPdf.php";

        $exps = json_decode($exps,true);
        if(count($exps) == 0) return "0 selected";

        $filename = 'NT-printare_multipla_master_puisori.pdf';
		$pdf = new NewAwbPdf();
        $pdf->doarPuisori = false;
        $pdf->SetTitle('NT-printare_multipla_master_puisori');
        $pdf->SetSubject('NT-printare_multipla_master_puisori');

        foreach( $exps as $key => $exp) {
            $this->GenerareTCPDF($pdf,$exp);
        }

        // move pointer to last page
        $pdf->lastPage();

        //I: send the file inline to the browser. The plug-in is used if available. The name given by filename is used when one selects the "Save as" option on the link generating the PDF.
		$pdf->Output($filename, 'I');
		exit;
    }

    function PrintareMultiplaMasterCautareExpeditii($exps=0, $autocolant = false){
		require_once "NewAwbPdf.php";

        $exps = json_decode($exps,true);
        if(count($exps) == 0) return "0 selected";

        $filename = 'NT-printare_multipla_master.pdf';
		$pdf = new NewAwbPdf(array('autocolant' => $autocolant));
        $pdf->doarMaster = true;
        $pdf->SetTitle('NT-printare_multipla_master');
        $pdf->SetSubject('NT-printare_multipla_master');

        if($autocolant) {
            $filename = 'NT-printare_multipla_master_autocolant.pdf';
        }

        foreach( $exps as $key => $exp) {
            $this->GenerareTCPDF($pdf,$exp);
        }

        // move pointer to last page
        $pdf->lastPage();

        //I: send the file inline to the browser. The plug-in is used if available. The name given by filename is used when one selects the "Save as" option on the link generating the PDF.
		$pdf->Output($filename, 'I');
		exit;
    }

	function GenerareTCPDF($pdf, $nr_exp){
        if(empty($nr_exp)) return $this->Error('Expeditie invalida!');

        if(false === ($exp = $this->GetValues(intval($nr_exp)))) return $this->Error('Expeditie invalida!');

		//daca este returnare si trebuie dublata
		if($exp['tip_exp'] == 5) {
			if(false === ($initialaRow = $this->GetValues($exp['referire'])))
				return $this->Error('Expeditie de referire invalida!');
			if($initialaRow['mod_plata'] == 0 && $initialaRow['restanta'] == 1)
			{
				//error_log("{$exp['valoare_totala_expeditie']} : {$initialaRow['valoare_totala_expeditie']}");
				$exp['valoare_totala_expeditie'] = round($exp['valoare_totala_expeditie'] + $initialaRow['valoare_totala_expeditie'], 2);
				$exp['tva'] = round($exp['tva'] + $initialaRow['tva'], 2);
				//error_log("{$exp['valoare_totala_expeditie']} : {$exp['tva']}");
			}
		}

		$vars = ExpeditieDto::sqlExpToPdf($exp);
		$vars['destinatar_nume'] = strtoupper(htmlspecialchars_decode(strtolower($vars['destinatar_nume']), ENT_QUOTES));
		
		$pdf->setVars($vars);

		if(!$pdf->doarPuisori) {
			$pdf->AddPage();
			$pdf->makeHalfFirstPage(0, 0);
			$pdf->makeDashedLine('H');
			if(!empty($exp['extrainfo']))
				$pdf->makeNotaComanda(0, 148);
			else {
				$pdf->makeHalfFirstPage(0, 148);
			}
		}
		//puisori
		$piese = $vars['piese'];
		$piese-=1;
		if($piese > 0 && !$pdf->doarMaster)
		{
			$puisori = ExpeditieDto::getPuisoriForAwb($vars['expeditie'], $piese+1);
			$nr_pag = ceil(((float)$piese)/4);

			$i=1; $j=2; $k=1;
			$pdf->AddPage();// prima pagina
			$pdf->makeDashedLine('H');
			$pdf->makeDashedLine('V');
			foreach($puisori as $puisor)
			{
				$pdf->makePuisorMultiCell($puisor,$j++,$k++);
                if($pdf->autocolant){
                    if($i < $vars['piese'] - 1){
                        $pdf->AddPage();
                    }
                    $i++;
                    continue;
                }
				if($k == 5) //am terminat o pagina
				{
					$k=1;
					if($i < $nr_pag)
					{
						$pdf->AddPage(); //pagina urmatoare daca mai sunt pagini
						$pdf->makeDashedLine('H');
						$pdf->makeDashedLine('V');
						$i++;
					}
				}
			}
		}
		$pdf->setVars(null);
		//update printare
		$this->db->QueryUpdate($this->tables['exp_prelucrate'], ['printed_at' => date('Y-m-d H:i:s'), 'printed_by' => $this->user_id], "expeditie = {$nr_exp}");
    }

/*/////////////////////////////////////////////////////////////
				 END ISTORIC
/////////////////////////////////////////////////////////////*/

/*/////////////////////////////////////////////////////////////
				 START URMARIRE
/////////////////////////////////////////////////////////////*/

    function UrmarireExpeditii() {
        $this->vars['title_page'] = 'Urmarire Expeditie';
        $vars = [];
        $vars['data_start'] = date('d.m.Y');
        $vars['data_final'] = date('d.m.Y');

        return $this->Parse($this->page_prefix . 'urmarire.html', $vars);
    }

    function JSON_UrmarireExpeditii(){
    	$responce = new StdClass();
        $cond ="ep.anulata = 0 ";
        $flag=0;
        if(isset($_REQUEST['data_start']) && isset($_REQUEST['data_final'])){
            $data_start = $this->TransformDate($_REQUEST['data_start']);
            $data_final = $this->TransformDate($_REQUEST['data_final']);
			$cond .= " AND ep.data_expeditie between '{$data_start}' AND '{$data_final}'";
            $flag=1;
        }

        if(isset($_REQUEST['filtru']) && !empty($_REQUEST['search']) ){
            if($_REQUEST['filtru']==1){
                $cond .= " AND ep.expeditie=".intval($_REQUEST['search']);
				$flag=1;
            }else if($_REQUEST['filtru']==2){
                $cond .= " AND cle.nume=".$this->db->escapeString(Backend::sSanitizeCleanEdges($_REQUEST['search']));
				$flag=1;
			}else if($_REQUEST['filtru']==3){
                $cond .= " AND cld.nume=".$this->db->escapeString(Backend::sSanitizeCleanEdges($_REQUEST['search']));
				$flag=1;
			}else if($_REQUEST['filtru']==4){
                $cond .= " AND cee.nume=".$this->db->escapeString(Backend::sSanitizeCleanEdges($_REQUEST['search']));
				$flag=1;
			}else if($_REQUEST['filtru']==5){
                $cond .= " AND ced.nume=".$this->db->escapeString(Backend::sSanitizeCleanEdges($_REQUEST['search']));
				$flag=1;
            }
		}
		if(!empty($_POST['expeditii'])){
			$post_expeditii = parent::ValidareExpeditiiCurata($_POST['expeditii']);
			if(!empty($post_expeditii)) {
				$cond .= " AND ep.expeditie in (".$post_expeditii.") ";
				$flag=1;
			}
		}
        if(empty($flag)) $cond = "1=2";//"data_expeditie>'2011-07-01' AND data_expeditie<'2011-07-05'";

		//start generare conditie
        $searchOn = $this->Strip($_REQUEST['_search']);

		$ntScan = "";

        if ($searchOn == 'true') {
            $searchstr = $this->Strip($_REQUEST['filters']);
			$searchstrArr = json_decode($searchstr, true);
			if (!empty($searchstrArr) && is_array($searchstrArr)){
				foreach ($searchstrArr['rules'] as $key=>$val){
					if($val['field'] == 'folder'){
						unset($searchstrArr['rules'][$key]);
						if($val['data'] == 0)
							$ntScan = " and ec.folder is null ";
						else
							$ntScan = " and ec.folder is not null ";
					}
				}
				$searchstr = json_encode($searchstrArr);
				$cond .= $this->constructWhere($searchstr);
			}
		}
		$cond = preg_replace("/ced.nume/i", "IF(cld.zona_id > 0 and cldc.id > 0, cldc.nume, ced.nume)", $cond);

        $page = intval($_REQUEST['page'] ?? 1);
		$limit = intval($_REQUEST['rows'] ?? 20);
		$sidx = trim($this->sanitize($_REQUEST['sidx'] ?? 1));
		$sord = trim($this->sanitize($_REQUEST['sord'] ?? 'asc'));

		$query = "SELECT COUNT(distinct ep.expeditie) as nr
			from exp_prelucrate ep
			left join clienti cle on cle.cod_cl = ep.expeditor_id
			left join clienti cld on cld.cod_cl = ep.destinatar_id
			LEFT JOIN zones clez ON clez.id = cle.zona_id
        	LEFT JOIN centre clec on clec.id = clez.centru_id
			LEFT JOIN zones cldz ON cldz.id = cld.zona_id
        	LEFT JOIN centre cldc on cldc.id = cldz.centru_id
			left join localitati lce ON lce.cod_lc = cle.cod_lc
			left join localitati lcd ON lcd.cod_lc = cld.cod_lc
			left join centre cee ON cee.id = lce.cod_centru
			left join centre ced ON ced.id = lcd.cod_centru
			left join ( select ecc.expeditie, ecc.folder, ecc.data
                    from exp_confirmari ecc
                    where ecc.id = (select MAX(ect.id) from exp_confirmari ect where ect.expeditie = ecc.expeditie)
            ) as ec on ec.expeditie = ep.expeditie
			left join ( select sc.expeditie, sc.data as last_ckp_data, sc.tip
                    from scanari_coduri sc
                    where sc.is_awb = 1 
					and sc.data = (select MAX(scc.data) from scanari_coduri scc where scc.expeditie = sc.expeditie and scc.is_awb = 1)
            ) as scckp on scckp.expeditie = ep.expeditie
			left join checkpoints ckp on ckp.id = scckp.tip
			WHERE {$cond}  {$ntScan}";
		$result = $this->db->QFetchArray($query);
		//error_log($query);
        $count = !empty($result['nr']) ? $result['nr'] : 0;

        if( $count >0 ) {$total_pages = ceil($count/$limit); }
        else { $total_pages = 0; }
        if ($page > $total_pages) $page=$total_pages;
        if ($limit<0) $limit = 0;
        $start = $limit*$page - $limit; // do not put $limit*($page - 1)
		if ($start<0) $start = 0;

        $query = "SELECT ep.expeditie, ep.data_expeditie, ep.tip_exp, ep.tip_plata,
			cle.nume as expeditor, cld.nume as destinatar,
			lce.nume_lc as expeditor_localitate, lcd.nume_lc as destinatar_localitate,
			IF(cle.zona_id > 0 and clec.id > 0, clec.nume, cee.nume) as expeditor_centru,
			IF(cle.zona_id > 0 and clec.id > 0, clec.label, cee.label) as expeditor_centru_cod,
			IF(cld.zona_id > 0 and cldc.id > 0, cldc.nume, ced.nume) as destinatar_centru,
			IF(cld.zona_id > 0 and cldc.id > 0, cldc.label, ced.label) as destinatar_centru_cod,
			ep.operatiune, ep.data_op, ep.primitor, ep.liv_samb,
			ec.folder , IF(ec.folder IS NULL,0,1) AS nt_scan, ckp.abbr as last_ckp, scckp.last_ckp_data
			from exp_prelucrate ep
			left join clienti cle on cle.cod_cl = ep.expeditor_id
			left join clienti cld on cld.cod_cl = ep.destinatar_id
			LEFT JOIN zones clez ON clez.id = cle.zona_id
        	LEFT JOIN centre clec on clec.id = clez.centru_id
			LEFT JOIN zones cldz ON cldz.id = cld.zona_id
        	LEFT JOIN centre cldc on cldc.id = cldz.centru_id
			left join localitati lce ON lce.cod_lc = cle.cod_lc
			left join localitati lcd ON lcd.cod_lc = cld.cod_lc
			left join centre cee ON cee.id = lce.cod_centru
			left join centre ced ON ced.id = lcd.cod_centru
			left join ( select ecc.expeditie, ecc.folder, ecc.data
                    from exp_confirmari ecc
                    where ecc.id = (select MAX(ect.id) from exp_confirmari ect where ect.expeditie = ecc.expeditie)
            ) as ec on ec.expeditie = ep.expeditie
			left join ( select sc.expeditie, sc.data as last_ckp_data, sc.tip
                    from scanari_coduri sc
                    where sc.is_awb = 1 
					and sc.data = (select MAX(scc.data) from scanari_coduri scc where scc.expeditie = sc.expeditie and scc.is_awb = 1)
            ) as scckp on scckp.expeditie = ep.expeditie
			left join checkpoints ckp on ckp.id = scckp.tip
			WHERE {$cond} {$ntScan}
            ORDER BY " . $sidx . " " . $sord . " LIMIT " . $start . " , " . $limit;

		//error_log($cond);

        $sql = $this->db->QFetchRowArray($query);
        if (!empty($sql)) {
            foreach ($sql as $key => $row) {
                $responce->rows[$key]['id'] = $row['expeditie'];
                $responce->rows[$key]['cell'] = array($row['data_expeditie'],$row['expeditie'],$row['tip_exp'], $row['tip_plata'], strtoupper($row['expeditor']),strtoupper($row['expeditor_centru']).' ('.strtoupper($row['expeditor_localitate']).')',strtoupper($row['destinatar']),strtoupper($row['destinatar_centru']).' ('.strtoupper($row['destinatar_localitate']).')',strtoupper($row['operatiune']), (($row['data_op'] == '0000-00-00')?'':$row['data_op']), strtoupper($row['last_ckp']), $row['last_ckp_data'], (isset($row['primitor'])?$row['primitor']:'') , ($row['liv_samb'])?'DA':"", ($row['folder'])?'<a target="_blank" href="'.$this->config['http'].'expeditii/download_confirmare?expeditie='.$row['expeditie'].'">DA</a>':"" );
            }
        }
		$responce->page = $page;
        $responce->total = $total_pages;
        $responce->records = $count;
        return json_encode($responce);
    }

	function ComboComentariiOperatiune($sel, $op=0, $extra='', $name='comentariu_operatiune'){
        $result = '';
        $query = "SELECT a.cod_com, b.com_ro
        FROM {$this->tables['op_com']} a
        INNER JOIN {$this->tables['com']} b ON a.cod_com=b.cod_com
        WHERE a.cod_op={$op}";
        $sql = $this->db->QFetchRowArray($query);
        if (!empty($sql))
            foreach ($sql as $val) {
                if ($sel == $val['cod_com'])
                    $result .= '<option value="' . $val['cod_com'] . '" selected>' . $val['com_ro'] . '</option>';
                else
                    $result .= '<option value="' . $val['cod_com'] . '">' . $val['com_ro'] . '</option>';
            }
        return '<select class="field_combobox" name="' . $name . '" id="' . $name . '" ' . $extra . '>' . $result . '</select>';
    }

	function ComboOperatiune($sel, $extra='', $name='operatiune', $min = 0, $max=21) {
	    $result = '<option value="0" selected >Toate</option>';
	    $sql = $this->db->QFetchRowArray("SELECT cod_op,op_ro FROM {$this->tables['op']} WHERE cod_op between {$min} and {$max}");
	    if (!empty($sql))
	        foreach ($sql as $val) {
	            if ($sel == $val['cod_op'])
	                $result .= '<option value="' . $val['cod_op'] . '" selected>' . $val['op_ro'] . '</option>';
	            else
	                $result .= '<option value="' . $val['cod_op'] . '">' . $val['op_ro'] . '</option>';
	        }
	    return '<select class="field_combobox" name="' . $name . '" id="' . $name . '" ' . $extra . '>' . $result . '</select>';
	}

	function DetaliiIstoricExpeditie(){
		$expeditie = intval($_POST['exp'] ?? 0);
		$ist = intval($_POST['ist'] ?? 0);

		if($expeditie == 0 && $ist == 0) return 'Invalid ID';

		if($expeditie > 0){
			$query = "SELECT cod_ist FROM {$this->tables['ist_exp']} WHERE cod_exp =
				(SELECT cod_expeditie from {$this->tables['exp_prelucrate']} WHERE expeditie = {$expeditie} and anulata = 0 and tip_exp < ".parent::TIP_EXP_BO_RBS_CASH." limit 1)
				AND operatiune < 23 ORDER BY data_op DESC LIMIT 1";
	    	$sql = $this->db->QFetchArray($query);
			if(empty($sql) || empty(($ist = $sql['cod_ist']))) return 'Invalid ID';
		}
		else {
			if($ist == 0) return 'Invalid ID';
		}

        $query = "SELECT cod_exp, operatiune, com, data_op FROM {$this->tables['ist_exp']} WHERE cod_ist = {$ist} ORDER BY DATA_OP DESC LIMIT 1";
        $sql = $this->db->QFetchArray($query);
        if(empty($sql)) return 'Expeditie Invalida';

		$query1 = "SELECT ep.expeditie, ep.tip_exp, ep.tip_plata, ep.referire,
			cle.nume as expeditor, cld.nume as destinatar, lce.nume_lc as expeditor_localitate,
			lcd.nume_lc as destinatar_localitate, lcd.cod_lc as destinatar_localitate_id,
			ep.curier_livrare_id, agl.nume_ag, ep.primitor
            from {$this->tables['exp_prelucrate']} ep
			left join agenti agl ON agl.cod_ag = ep.curier_livrare_id
			left join clienti cle on cle.cod_cl = ep.expeditor_id
			left join clienti cld on cld.cod_cl = ep.destinatar_id
			left join localitati lce ON lce.cod_lc = cle.cod_lc
			left join localitati lcd ON lcd.cod_lc = cld.cod_lc
            WHERE ep.cod_expeditie = {$sql['cod_exp']} and ep.anulata = 0 and ep.tip_exp < ".parent::TIP_EXP_BO_RBS_CASH." LIMIT 1";

        $sql1 = $this->db->QFetchArray($query1);
		if(empty($sql1) || empty($sql1['expeditie'])) return 'Expeditie Invalida';

		$vars=$sql1;
		$vars['localitate'] = $sql1['destinatar_localitate_id'];

        $vars['comentariu'] = $this->ComboComentariiOperatiune($sql['com'],$sql['operatiune'],'style="width: 340px;" onchange="VerificareIstoric();"');
        $vars['livrat'] = 'ascuns';
        $vars['retur_div'] = 'ascuns';
        $vars['retur_checkbox'] = '';

        $vars['operatie_old'] = $sql['operatiune'];
        $vars['data_operatiei_old'] = $sql['data_op'];
        $vars['comentariu_old'] = $sql['com'];
        $vars['cod_ist'] = $ist;
        $vars['cod_exp'] = $sql['cod_exp'];
        $tip_exp = $sql1['tip_exp'] ?? 0;
        $tip_plata = 0;
        $vars['tip_exp'] = 'Expeditie ' . ExpeditieDto::TIP_EXP[$tip_exp] ?? 'unknown';
        if(!empty($vars['referire']))
        {
        	if($tip_exp == 2)
        	{
				$vars['referire'] = 'pentru '.$vars['referire'];
				$vars['retur_div'] = '';
				$vars['retur_label'] = 'Inchide returul';
				$vars['retur_checkbox'] = 'checked';
        	}
        	else if($tip_exp == 1)
        	{
				$vars['referire'] = 'pentru '.$vars['referire'];
				$vars['retur_div'] = '';
				$vars['retur_label'] = 'Inchide returul';
				$vars['retur_checkbox'] = 'checked';
        	}
        	else if($tip_exp == 5)
        	{
        		$vars['referire'] = 'pentru '.$vars['referire'];
        	}
        	else if($tip_exp == 3) {
        		$tip_plata = $sql1['tip_plata'] ?? 0;
        		if($tip_plata == 0)
        		{
        			$vars['tip_exp'] .= ' cash plic';
        			$vars['retur_div'] = '';
        			$vars['retur_label'] = 'Inchide rambursul';
        			$vars['retur_checkbox'] = 'checked';
        		}
        		else if($tip_plata == 1)
        		{
        			$vars['tip_exp'] .= ' bo';
        			$vars['retur_div'] = '';
        			$vars['retur_label'] = 'Inchide rambursul';
        			$vars['retur_checkbox'] = 'checked';
        		}
        		else if($tip_plata == 2)
        		{
        			$vars['tip_exp'] .= ' cec';
        			$vars['retur_div'] = '';
        			$vars['retur_label'] = 'Inchide rambursul';
        			$vars['retur_checkbox'] = 'checked';
        		}
        		else if($tip_plata == 3) $vars['tip_exp'] .= ' cont colector';
        		$vars['referire'] = 'pentru '.$vars['referire'];
        	}
        }
        else $vars['referire'] = '';

        if($sql['operatiune'] == 3){
            $vars['primitor'] = (empty($sql1) && empty($sql1['primitor'])) ? "" : $sql1['primitor'];
            $vars['ag_liv_nume'] = (empty($sql1) && empty($sql1['nume_ag'])) ? "" : $sql1['nume_ag'];
            $vars['ag_liv'] = (empty($sql1) && empty($sql1['curier_livrare_id'])) ? "" : $sql1['curier_livrare_id'];
            $vars['standard'] = 'ascuns';
            $vars['livrat'] = '';
        }

		if($ist == 0){
			if($sql['operatiune'] == 1){
				$sql['operatiune'] = 3;
				$sql['com'] = 0;
				$vars['standard'] = 'ascuns';
	            $vars['livrat'] = '';
				$vars['comentariu'] = $this->ComboComentariiOperatiune($sql['com'],$sql['operatiune'],'style="width: 340px;" onchange="VerificareIstoric();"');
			}

		}
		$vars['operatie'] = $this->ComboOperatiune($sql['operatiune'],'style="width: 170px;" onchange="VerificareIstoricOperatiune(this.value);"','operatie');

		if($ist == 0 && !empty($_SESSION['pastreaza_curier'])){
			$vars['ag_liv_nume'] = $_SESSION['pastreaza_curier_nume'];
			$vars['ag_liv'] = $_SESSION['pastreaza_curier_id'];
			$vars['ag_liv_retinut'] = $_SESSION['pastreaza_curier'];
			$vars['data'] = $_SESSION['pastreaza_data'];
		}else{
			$vars['data'] = $this->CreateDate($sql['data_op']);
		}

		if($ist > 0)
			$vars['confirmare'] = 'ascuns';

        return $this->Parse($this->page_prefix . 'istoric_operatiune.html', $vars);
    }

	function ModificareIstoricExpeditie_Urmarire(){
		/*cod_ist : cod_ist,
		cod_exp : cod_exp,
		operatie : operatie,
		data_operatiei : data_operatiei,
		curier : curier,
		curier_nume : curier_nume,
		comentariu_operatie : comentariu_operatie,
		primitor : primitor,
		pastreaza_curier : pastreaza_curier,
		inchide_retur : inchide_retur,
		type : sel
		*/
		$vars = [];
		$vars['cod_ist'] = intval($_POST['cod_ist'] ?? 0);
		$vars['cod_exp'] = intval($_POST['cod_exp'] ?? 0);
		$vars['expeditie'] = intval($_POST['expeditie'] ?? 0);
		$vars['operatie'] = intval($_POST['operatie'] ?? 0);
		$vars['data_operatiei'] = Backend::sSanitize($_POST['data_operatiei'] ?? '');
		$vars['curier'] = intval($_POST['curier'] ?? '');
		$vars['curier_nume'] = Backend::sSanitize($_POST['curier_nume'] ?? '');
		$vars['comentariu_operatie'] = intval($_POST['comentariu_operatie'] ?? 0);
		$vars['primitor'] = Backend::sSanitizeCleanEdges($_POST['primitor'] ?? '');
		$vars['pastreaza_curier'] = intval($_POST['pastreaza_curier'] ?? 0);
		$vars['inchide_retur'] = intval($_POST['inchide_retur'] ?? 0);
		$vars['type'] = intval($_POST['type'] ?? 0);

		$_SESSION['pastreaza_curier'] = '';
        $_SESSION['pastreaza_curier_id'] = '';
        $_SESSION['pastreaza_curier_nume'] = '';
		$_SESSION['pastreaza_data'] = '';
        if($vars['pastreaza_curier'] == 1 && !empty($vars['curier'])){
        	$_SESSION['pastreaza_curier_id'] = $vars['curier'];
			$_SESSION['pastreaza_curier_nume'] = $vars['curier_nume'];
			$_SESSION['pastreaza_curier'] = 'checked="checked"';
			$_SESSION['pastreaza_data'] = $vars['data_operatiei'];
        }

		if($vars['expeditie'] == 0 || !ExpeditieDto::isAwb($vars['expeditie'])) return '0|||Cod invalid';
		if($vars['operatie'] <= 0 || $vars['operatie'] >= 21) return '0|||Error operatiune';
        if(isset($_POST['data_operatiei'])) {
			$data_op = DateTime::createFromFormat('d.m.Y H:i:s', $_POST['data_operatiei']);
			if($data_op === false){
				$data_op = DateTime::createFromFormat('d.m.Y', $_POST['data_operatiei']);
				if($data_op === false)
					return "0|||Data invalida";
			}
		}
		else return "0|||Selectati data";

		//ist_exp
		$var = [];
		$var['operatiune'] = $vars['operatie'];
        if(isset($vars['comentariu_operatie']))
        	$var['com'] = $vars['comentariu_operatie'];
       	$var['operator'] = $this->user_id;
		$var['data'] = $data_op->format('Y-m-d');
    	$var['ora'] = $data_op->format('H:i:s');
        $var['data_op'] = date('Y-m-d H:i:s');
		$var['cod_exp'] = $vars['cod_exp'];

        if($vars['type']==2){
            $this->db->QueryUpdate($this->tables['ist_exp'], $var, "cod_ist = ".$vars['cod_ist']);
        }
		else {
            $this->db->QueryInsert($this->tables['ist_exp'], $var);
        }

		$msg_back = '1|||Modificarile au fost salvate cu succes';

		//exp_prelucrate
        if($vars['operatie'] == 3){ //inchide returul si inchide rambursul numai daca nu e cont colector
			$inchide_retur = false;
			$inchide_ramburs = false;
			if(empty($vars['curier']))
				return '0|||Selectati un curier valid!';
			if(empty($vars['primitor']))
				return '0|||Selectati un primitor!';

			$var=[];
			$var['primitor'] = $vars['primitor'];
			$var['data_op'] = date('Y-m-d H:i:s');
			$var['operatiune'] = 'Livrat';
            $var['curier_livrare_id'] = $vars['curier'];
			$var['curier_livrare'] = $vars['curier_nume'];
			$var['anulata'] = 0;
			$var['deleted_by'] = 0;
			$var['deleted_at'] = null;
			$var['last_ckp'] = parent::TIP_SCANARE_COK;
			$var['data_last_ckp'] = date('Y-m-d H:i:s');
			$var['centru_last_ckp'] = $this->user_centru_id ?? 47;

			$this->db->QueryUpdate($this->tables['exp_prelucrate'], $var, "cod_expeditie=" . $vars['cod_exp']);

			//insert ckp pentru livrare
			$this->insertCkp($vars['expeditie'], parent::TIP_SCANARE_COK, $this->user_centru_id ?? 47, $this->user_id, $vars['curier']);

			$s = $this->db->QFetchArray("SELECT epr.referire, epr.tip_exp, epr.tip_plata, ep.status_retururi as oldStatusRet, 
				ep.status_ramburs as oldStatusRbs, ep.cod_expeditie as initialaId
				from {$this->tables['exp_prelucrate']} epr
				inner join {$this->tables['exp_prelucrate']} ep on epr.referire = ep.expeditie and ep.anulata = 0
				WHERE epr.cod_expeditie = {$vars['cod_exp']} and epr.anulata = 0
			");
			//inchide returul / rambursul daca nu e cash sau cont colector
			if(!empty($s) && !empty($s['referire']) && !empty($s['tip_exp']) && !empty($s['initialaId']) && isset($_POST['inchide_retur']) && $_POST['inchide_retur'] == 1)
			{
			 	if($s['tip_exp'] == 1)
			 	{
					$inchide_retur = $this->setStatusRetur($s['initialaId'], $s['oldStatusRet'], 1);
				}
				else if($s['tip_exp'] == 2)
				{
					$inchide_retur = $this->setStatusRetur($s['initialaId'], $s['oldStatusRet'], 2);
				}
				else if($s['tip_exp'] == 3 && in_array($s['tip_plata'], [1,2]))//bo, cec
				{
					$inchide_ramburs = $this->setStatusRamburs($s['initialaId'], $s['oldStatusRbs'], 1, $s['tip_plata']);
				}
			}
			if($inchide_retur) $msg_back .= '<br/>Returul a fost inchis';
			if($inchide_ramburs > 1) $msg_back .= '<br/>Rambursul a fost inchis';
			return $msg_back;
        }

        if($vars['operatie'] < 23){
        	$sql = $this->db->QFetchArray("SELECT op_ro FROM {$this->tables['op']} WHERE cod_op = {$vars['operatie']}");
		    if (!empty($sql)){
		    	$vu=[];
		    	$vu['operatiune'] = $sql['op_ro'];
				$vu['primitor'] = $vars['primitor'];
				$vu['data_op'] = date('Y-m-d H:i:s');
				$vu['anulata'] = 0;
				$vu['deleted_by'] = 0;
				$vu['deleted_at'] = null;
				$vu['last_ckp'] = ExpeditieDto::getCkpFromOperatiune($vars['operatie']);
				$vu['data_last_ckp'] = date('Y-m-d H:i:s');
				$vu['centru_last_ckp'] = $this->user_centru_id ?? 47;
		    	$this->db->QueryUpdate($this->tables['exp_prelucrate'], $vu, "cod_expeditie=" . $vars['cod_exp']);
				//insert ckp pentru operatiune
				$this->insertCkp($vars['expeditie'], $vu['last_ckp'], $this->user_centru_id ?? 47, $this->user_id, $vars['curier']);
		    }
        }
        return $msg_back;
    }
/*/////////////////////////////////////////////////////////////
				 END URMARIRE
/////////////////////////////////////////////////////////////*/
function IstoricScanariExpeditii() {
	$this->vars['title_page'] = 'Istoric Livrari';
	$vars = [];
	$vars['data_start'] = date('d.m.Y');
	$vars['data_final'] = date('d.m.Y');

	return $this->Parse($this->page_prefix . 'istoric_scanari.html', $vars);
}

function JSON_IstoricScanariExpeditii(){
	$responce = new StdClass();
	$cond ='1=1 and ep.anulata = 0';
	$flag=0;
	if(isset($_REQUEST['data_start']) && isset($_REQUEST['data_final'])){
		$data_start = $this->TransformDate($_REQUEST['data_start']);
		$data_final = $this->TransformDate($_REQUEST['data_final']);
		$cond .= " AND ep.data_expeditie between '{$data_start}' AND '{$data_final}'";
		$flag=1;
	}

	if(empty($flag)) $cond = "1=2";
	//start generare conditie
	$searchOn = $this->Strip($_REQUEST['_search']);

	if ($searchOn == 'true') {
		$searchstr = $this->Strip($_REQUEST['filters']);
		$cond .= $this->constructWhere($searchstr);
	}
	$cond = preg_replace("/ced.nume/i", "IF(cld.zona_id > 0 and cldc.id > 0, cldc.nume, ced.nume)", $cond);

	$page = intval($_REQUEST['page'] ?? 1);
	$limit = intval($_REQUEST['rows'] ?? 20);
	$sidx = trim($this->sanitize($_REQUEST['sidx'] ?? 1));
	$sord = trim($this->sanitize($_REQUEST['sord'] ?? 'asc'));

	$query = "SELECT COUNT(ep.cod_expeditie) as nr , IF(ec.folder IS NULL,0,1) AS nt_scan
		from {$this->tables['exp_prelucrate']} ep
		left join clienti cle on cle.cod_cl = ep.expeditor_id
		left join clienti cld on cld.cod_cl = ep.destinatar_id
		LEFT JOIN zones clez ON clez.id = cle.zona_id
		LEFT JOIN centre clec on clec.id = clez.centru_id
		LEFT JOIN zones cldz ON cldz.id = cld.zona_id
		LEFT JOIN centre cldc on cldc.id = cldz.centru_id
		left join localitati lce ON lce.cod_lc = cle.cod_lc
		left join localitati lcd ON lcd.cod_lc = cld.cod_lc
		left join centre cee ON cee.id = lce.cod_centru
		left join centre ced ON ced.id = lcd.cod_centru
		LEFT JOIN exp_confirmari ec
		ON ep.expeditie = ec.expeditie
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
	$query = "SELECT ep.data_expeditie, ep.expeditie,
		cle.nume as expeditor, cld.nume as destinatar,
		lce.nume_lc as expeditor_localitate, lcd.nume_lc as destinatar_localitate,
		IF(cle.zona_id > 0 and clec.id > 0, clec.nume, cee.nume) as expeditor_centru, 
		IF(cle.zona_id > 0 and clec.id > 0, clec.label, cee.label) as expeditor_centru_cod,
		IF(cld.zona_id > 0 and cldc.id > 0, cldc.nume, ced.nume) as destinatar_centru, 
		IF(cld.zona_id > 0 and cldc.id > 0, cldc.label, ced.label) as destinatar_centru_cod,
		ep.operatiune, ep.data_op, ep.primitor, ep.liv_samb,
		ec.folder , IF(ec.folder IS NULL,0,1) AS nt_scan,
		b.LABEL as ck_centru, e.activ as ck_is_activ, e.is_public as ck_is_public, e.denumire as ck_denumire
		from {$this->tables['exp_prelucrate']} ep
		left join clienti cle on cle.cod_cl = ep.expeditor_id
		left join clienti cld on cld.cod_cl = ep.destinatar_id
		LEFT JOIN zones clez ON clez.id = cle.zona_id
		LEFT JOIN centre clec on clec.id = clez.centru_id
		LEFT JOIN zones cldz ON cldz.id = cld.zona_id
		LEFT JOIN centre cldc on cldc.id = cldz.centru_id
		left join localitati lce ON lce.cod_lc = cle.cod_lc
		left join localitati lcd ON lcd.cod_lc = cld.cod_lc
		left join centre cee ON cee.id = lce.cod_centru
		left join centre ced ON ced.id = lcd.cod_centru
		LEFT JOIN exp_confirmari ec ON ep.expeditie = ec.expeditie
		LEFT JOIN (
			select i.id as sc_id, i.tip as sc_tip , i.data, i.centru as sc_centru, i.expeditie
			from scanari_coduri i
			where i.is_awb = 1 and i.data in 
			(select max(j.data) from scanari_coduri j where j.expeditie=i.expeditie and j.is_awb = 1 and j.tip not in (31, 32) )
			 ) as sc on sc.expeditie = ep.expeditie
		LEFT JOIN centre as b ON sc.sc_centru = b.id
		LEFT JOIN checkpoints as e ON e.id = sc.sc_tip
		WHERE {$cond}
		group by ep.expeditie
		ORDER BY " . $sidx . " " . $sord . " LIMIT " . $start . "," . $limit;
	//error_log($query);
	$sql = $this->db->QFetchRowArray($query);
	if (!empty($sql)) {
		foreach ($sql as $key => $row) {
			$responce->rows[$key]['id'] = $row['expeditie'];
			$responce->rows[$key]['cell'] = array($row['data_expeditie'],$row['expeditie'], strtoupper($row['expeditor']),strtoupper($row['expeditor_centru']),strtoupper($row['expeditor_localitate']),strtoupper($row['destinatar']),strtoupper($row['destinatar_centru']),strtoupper($row['destinatar_localitate']),(!empty($row['ck_is_activ']) && !empty($row['ck_is_public']))?$row['ck_denumire'].'('.$row['ck_centru'].')':'',strtoupper($row['operatiune']), (($row['data_op'] == '0000-00-00')?'':$row['data_op']), (isset($row['primitor'])?$row['primitor']:'') , ($row['liv_samb'])?'DA':"", ($row['folder'])?'<a target="_blank" href="'.$this->config['http'].'expeditii/download_confirmare?expeditie='.$row['expeditie'].'">DA</a>':"" );
		}
	}
	$responce->page = $page;
	$responce->total = $total_pages;
	$responce->records = $count;
	return json_encode($responce);
}

function JSON_IstoricCheckpointsExpeditie(){
	$responce = new StdClass();
	if(isset($_REQUEST['expeditie']))
		$expeditie = intval(trim($_REQUEST['expeditie']));
	else {
		$responce->page = 1;
       	$responce->total = 1;
        $responce->records = 0;
    	return json_encode($responce);
	}

	$query = "SELECT a.id, a.cod, a.tip, a.data, b.label as centru, c.nume_ag as curier, d.denumire,
		e.denumire as ckp, IF(cl.OBS_BL = 1 OR clm.OBS_BL = 1,1,0) as OBS_BL
		from {$this->tables['exp_prelucrate']} as ep
		LEFT JOIN scanari_coduri as a ON a.expeditie = ep.expeditie
		LEFT JOIN clienti cl ON cl.cod_cl = ep.platitor_id
		LEFT JOIN clienti clm ON clm.cod_cl=cl.master
		LEFT JOIN centre as b ON a.centru=b.id
		LEFT JOIN agenti as c ON a.curier=c.cod_ag
		LEFT JOIN rute as d ON a.ruta=d.id
		LEFT JOIN checkpoints as e ON e.id=a.tip
		WHERE ep.expeditie = {$expeditie} and ep.anulata = 0 and e.activ = 1 and e.is_public = 1
		ORDER BY a.data desc";
	//error_log($query);
	$sql = $this->db->QFetchRowArray($query);
    if($sql){
	    foreach ($sql as $k => $row) {
	        $responce->rows[$k]['id'] = $row['id'];
            if($row['OBS_BL'] == 1 ){
                $row['cod'] = '<span style="color:red; font-weight: bold">'.$row['cod'].'</span>';
			}
            else {
                $responce->rows[$k]['cell'] = array($row['cod'],strtoupper($row['centru']),strtoupper($row['denumire']),strtoupper($row['curier']),$row['ckp'],$row['data']);
            }
            $k++;
	    }
	}

    $responce->page = 1;
    $responce->total = 1;
    $responce->records = 0;
    return json_encode($responce);

}

function ExportIstoricScanariExpeditii(){

	$cond = "1=1 and ep.anulata = 0";
	$flag=0;
	if(isset($_REQUEST['data_start']) && isset($_REQUEST['data_final'])){
		$data_start = $this->TransformDate($_REQUEST['data_start']);
		$data_final = $this->TransformDate($_REQUEST['data_final']);
		$cond .= " AND ep.data_expeditie between '{$data_start}' AND '{$data_final}'";
		$flag=1;
	}

	if(empty($flag)) $cond = "1=2";
	//start generare conditie
	$searchOn = false;
	if(isset($_REQUEST['filters']))
		$searchOn = true;

	if ($searchOn === true) {
		$searchstr = $this->Strip($_REQUEST['filters']);
		$cond .= $this->constructWhere($searchstr);
	}

	$cond = preg_replace("/ced.nume/i", "IF(cld.zona_id > 0 and cldc.id > 0, cldc.nume, ced.nume)", $cond);

	$sidx = isset($_REQUEST['sidx'])?$_REQUEST['sidx']:'ep.data_expeditie'; // get index row - i.e. user click to sort
	$sord = isset($_REQUEST['sord'])?$_REQUEST['sord']:'desc'; // get the direction
	if (!$sidx) $sidx = 1;

	$data = date('d/m/Y');
 	$societate = 'Dragon Star Curier';
    $document = 'Lista Istoric Scanari';

	$spreadsheet = new Spreadsheet();
	$worksheet = $spreadsheet->getActiveSheet();

	$spreadsheet->getProperties()
				->setCreator($societate)
                ->setLastModifiedBy($societate)
                ->setTitle($document)
                ->setSubject($document)
                ->setDescription($document)
                ->setKeywords($document)
                ->setCategory($document);
	$spreadsheet->getDefaultStyle()->getFont()->setName('Arial');
    $spreadsheet->getDefaultStyle()->getFont()->setSize(10);

	foreach(range('A','O') as $v){
		$worksheet->getColumnDimension($v)->setWidth(105);
	}
	foreach(range('A','O') as $v){
		$worksheet->getStyle($v.'1')->getFont()->setBold(true);
	}

	$worksheet->setCellValue('A1','Data Prel.');
    $worksheet->setCellValue('B1','Nr. Exp');
    $worksheet->setCellValue('C1','Expeditor');
    $worksheet->setCellValue('D1','Centru Exp');
    $worksheet->setCellValue('E1','Loc. Exp');
	$worksheet->setCellValue('F1','Destinatar');
	$worksheet->setCellValue('G1','Centru Dest');
	$worksheet->setCellValue('H1','Loc. Dest');
	$worksheet->setCellValue('I1','Last scan');
	$worksheet->setCellValue('J1','Data last scan');
	$worksheet->setCellValue('K1','Status');
	$worksheet->setCellValue('L1','Data Op');
	$worksheet->setCellValue('M1','Primitor');
	$worksheet->setCellValue('N1','Samb');

	$query = "SELECT ep.data_expeditie, ep.expeditie,
		cle.nume as expeditor, cld.nume as destinatar,
		lce.nume_lc as expeditor_localitate, lcd.nume_lc as destinatar_localitate,
		IF(cle.zona_id > 0 and clec.id > 0, clec.nume, cee.nume) as expeditor_centru, 
		IF(cle.zona_id > 0 and clec.id > 0, clec.label, cee.label) as expeditor_centru_cod,
		IF(cld.zona_id > 0 and cldc.id > 0, cldc.nume, ced.nume) as destinatar_centru, 
		IF(cld.zona_id > 0 and cldc.id > 0, cldc.label, ced.label) as destinatar_centru_cod,
		ep.operatiune, ep.data_op, ep.primitor, ep.liv_samb,
		ec.folder , IF(ec.folder IS NULL,0,1) AS nt_scan, b.label as ck_centru, e.activ as ck_is_activ,
		e.is_public as ck_is_public, e.denumire as ck_denumire, sc.sc_data as ck_data
		from {$this->tables['exp_prelucrate']} ep
		left join clienti cle on cle.cod_cl = ep.expeditor_id
		left join clienti cld on cld.cod_cl = ep.destinatar_id
		LEFT JOIN zones clez ON clez.id = cle.zona_id
		LEFT JOIN centre clec on clec.id = clez.centru_id
		LEFT JOIN zones cldz ON cldz.id = cld.zona_id
		LEFT JOIN centre cldc on cldc.id = cldz.centru_id
		left join localitati lce ON lce.cod_lc = cle.cod_lc
		left join localitati lcd ON lcd.cod_lc = cld.cod_lc
		left join centre cee ON cee.id = lce.cod_centru
		left join centre ced ON ced.id = lcd.cod_centru
		LEFT JOIN exp_confirmari ec ON ep.expeditie = ec.expeditie
		LEFT JOIN (
			select i.id as sc_id, i.tip as sc_tip , i.data as sc_data, i.centru as sc_centru, i.expeditie
			from scanari_coduri i
			where i.is_awb = 1 and i.data in 
			(select max(j.data) from scanari_coduri j where j.expeditie=i.expeditie and j.is_awb = 1 and j.tip not in (31, 32) )
			 ) as sc on sc.expeditie = ep.expeditie
		LEFT JOIN centre as b ON sc.sc_centru = b.id
		LEFT JOIN checkpoints as e ON e.id = sc.sc_tip
		WHERE {$cond} 
		group by ep.expeditie
		ORDER BY " . $sidx . " " . $sord;
	//error_log($query);
	$sql = $this->db->QFetchRowArray($query);
	if (!empty($sql)) {
		$rand = 2;
		foreach ($sql as $key => $row) {
			$da = new DateTime($row['data_expeditie']);

			$worksheet->setCellValue('A'.($key + $rand),\PhpOffice\PhpSpreadsheet\Shared\Date::PHPToExcel($da));
			$worksheet->getStyle('A'.($key + $rand))->getNumberFormat()->setFormatCode(\PhpOffice\PhpSpreadsheet\Style\NumberFormat::FORMAT_DATE_DDMMYYYY);
			$worksheet->setCellValue('B'.($key + $rand),$row['expeditie']);
			$worksheet->setCellValueExplicit('C'.($key + $rand),strtoupper($row['expeditor']), \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
			$worksheet->setCellValue('D'.($key + $rand),strtoupper($row['expeditor_centru']));
			$worksheet->setCellValue('E'.($key + $rand),strtoupper($row['expeditor_localitate']));
			$worksheet->setCellValueExplicit('F'.($key + $rand),strtoupper($row['destinatar']), \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
			$worksheet->setCellValue('G'.($key + $rand),strtoupper($row['destinatar_centru']));
			$worksheet->setCellValue('H'.($key + $rand),strtoupper($row['destinatar_localitate']));
			$worksheet->setCellValue('I'.($key + $rand),(!empty($row['ck_is_activ']) && !empty($row['ck_is_public']))?$row['ck_denumire'].'('.$row['ck_centru'].')':'');
			$dj = new DateTime($row['ck_data']);
			$worksheet->setCellValue('J'.($key + $rand),(!empty($row['ck_is_activ']) && !empty($row['ck_is_public']))?\PhpOffice\PhpSpreadsheet\Shared\Date::PHPToExcel($dj):'');
			$worksheet->getStyle('J'.($key + $rand))->getNumberFormat()->setFormatCode('dd.mm.yyyy hh:mm:ss');
			$worksheet->setCellValue('K'.($key + $rand),strtoupper($row['operatiune']));
			$dl = new DateTime($row['data_op']);
			$worksheet->setCellValue('L'.($key + $rand),(($row['data_op'] == '0000-00-00')?'':\PhpOffice\PhpSpreadsheet\Shared\Date::PHPToExcel($dl)));
			$worksheet->getStyle('L'.($key + $rand))->getNumberFormat()->setFormatCode(\PhpOffice\PhpSpreadsheet\Style\NumberFormat::FORMAT_DATE_DDMMYYYY);
			$worksheet->setCellValueExplicit('M'.($key + $rand),(isset($row['primitor'])?$row['primitor']:''), \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
			$worksheet->setCellValue('N'.($key + $rand),(empty($row['liv_samb']))?'':'DA');
		}
	}

    $data = date('d_m_Y');
    $filename = 'Lista_Istoric_Scanari_'.$data.'.xlsx';

    $this->download_send_headers_xls($filename);
	$writer = new Xlsx($spreadsheet);
    $writer->save('php://output');
	die;
}
/*/////////////////////////////////////////////////////////////
				 START ListeExpeditii
/////////////////////////////////////////////////////////////*/

function ListeExpeditii() {
        $this->vars['title_page'] = 'Liste Expeditii';
        $vars = [];
        $vars['data_start'] = date('d.m.Y');
        $vars['data_final'] = date('d.m.Y');

        $vars['operatiune'] = $this->ComboOperatiune(0,'style="width: 170px;"');

        return $this->Parse($this->page_prefix . 'listare_expeditii.html', $vars);
    }

  function JSON_ListeExpeditii() {
  		$responce = new StdClass();
		$responce->page = 0;
		$responce->total = 0;
		$responce->records = 0;

        $cond ="ep.anulata = 0 ";
		$centru_id = intval($_REQUEST['centru'] ?? 0);
		$operatiune = intval($_REQUEST['operatiune'] ?? 0);

        if(isset($_REQUEST['data_start']) && isset($_REQUEST['data_final'])){
            $data_start = $this->TransformDate($_REQUEST['data_start']);
            $data_final = $this->TransformDate($_REQUEST['data_final']);
			$cond .= " AND ep.data_expeditie between '{$data_start}' AND '{$data_final}'";
        }
		else return json_encode($responce);

        if($operatiune == 1) {
            $cond .= " AND IF(cld.zona_id > 0 and cldc.id > 0, cldc.id, ced.id) in (47, 91) ";
        }
		else if($operatiune == 2) {
			$debug = 1;
		}
		else if($operatiune == 3 && $centru_id > 0) {
			$cond .= " AND IF(cld.zona_id > 0 and cldc.id > 0, cldc.id, ced.id) = {$centru_id} ";
		}
		else return json_encode($responce);

        $page = intval($_REQUEST['page'] ?? 1);
		$limit = intval($_REQUEST['rows'] ?? 20);
		$sidx = trim($this->sanitize($_REQUEST['sidx'] ?? 1));
		$sord = trim($this->sanitize($_REQUEST['sord'] ?? 'asc'));

		$totalrows = isset($_REQUEST['totalrows']) ? $_REQUEST['totalrows']: false;
		if($totalrows){ $limit = $totalrows; }

		$query = "SELECT COUNT(DISTINCT(IF(cld.zona_id > 0 and cldc.id > 0, cldc.id, ced.id))) as nr
		from {$this->tables['exp_prelucrate']} ep
		inner join clienti cld on cld.cod_cl = ep.destinatar_id
		LEFT JOIN zones cldz ON cldz.id = cld.zona_id
        LEFT JOIN centre cldc on cldc.id = cldz.centru_id
		inner join localitati lcd ON lcd.cod_lc = cld.cod_lc
		inner join centre ced ON ced.id = lcd.cod_centru
		WHERE {$cond}";
		//echo $query;die;
		$result = $this->db->QFetchArray($query);
		$count = !empty($result['nr']) ? $result['nr'] : 0;

		if( $count >0 ) {
				$total_pages = ceil($count/$limit); }
		else { $total_pages = 0; }
		if ($page > $total_pages) $page=$total_pages;
		if ($limit<0) $limit = 0;
		$start = $limit*$page - $limit; // do not put $limit*($page - 1)
		if ($start<0) $start = 0;

		 $query = "SELECT COUNT(ep.cod_expeditie) as expeditii, SUM(ep.greutate) as greutate, SUM(ep.plicuri) as plicuri,
		 	SUM(ep.colete) as colete, SUM(ep.paleti) as paleti, SUM(ep.km_livrare + ep.km_preluare) as km,
			SUM(ep.liv_samb) as liv_sambata, 
			IF(cld.zona_id > 0 and cldc.id > 0, cldc.id, ced.id) as destinatar_centru_id, 
			IF(cld.zona_id > 0 and cldc.id > 0, cldc.nume, ced.nume) as destinatar_centru,
			IF(cld.zona_id > 0 and cldc.id > 0, cldc.label, ced.label) as destinatar_centru_cod
		 	from {$this->tables['exp_prelucrate']} ep
			inner join clienti cld on cld.cod_cl = ep.destinatar_id
			LEFT JOIN zones cldz ON cldz.id = cld.zona_id
        	LEFT JOIN centre cldc on cldc.id = cldz.centru_id
			inner join localitati lcd ON lcd.cod_lc = cld.cod_lc
			inner join centre ced ON ced.id = lcd.cod_centru
		 	WHERE {$cond}
		 	GROUP BY IF(cld.zona_id > 0 and cldc.id > 0, cldc.id, ced.id)
			ORDER BY " . $sidx . " " . $sord . " LIMIT " . $start . " , " . $limit . ";";
           //echo $query;die;
        $sql = $this->db->QFetchRowArray($query);
        //compun raspunsul
        $responce->page = $page;
        $responce->total = $total_pages;
        $responce->records = $count;
        if (!empty($sql)) {
            foreach ($sql as $key => $row) {
            	if(empty($row['paleti'])) $row['paleti']=0;
                $responce->rows[$key]['id'] = $row['destinatar_centru_id'];
                $responce->rows[$key]['cell'] = array($row['destinatar_centru'], $row['expeditii'], $row['greutate'],$row['plicuri'],$row['colete'],$row['paleti'],$row['km'] ,$row['liv_sambata']);
            }
        }
        return json_encode($responce);
    }

    function JSON_ListeExpeditiiDetalii() {
    	$responce = new StdClass();
		$responce->page = 0;
		$responce->total = 0;
		$responce->records = 0;
		$cond = "1=1";

      	if(isset($_REQUEST['data_start']) && isset($_REQUEST['data_final'])){
	        $data_start = $this->TransformDate($_REQUEST['data_start']);
	        $data_final = $this->TransformDate($_REQUEST['data_final']);
			$cond .= " AND ep.data_expeditie between '{$data_start}' AND '{$data_final}'";
	    }
		else {
			return json_encode($responce);
		}

		$centru_id = intval($_REQUEST['centru'] ?? 0);
		if($centru_id > 0)
			$cond .= " and IF(cld.zona_id > 0 and cldc.id > 0, cldc.id, lcd.cod_centru) = {$centru_id}";
		else {
			return json_encode($responce);
		}

		$page = intval($_REQUEST['page'] ?? 1);
		$limit = intval($_REQUEST['rows'] ?? 20);
		$sidx = trim($this->sanitize($_REQUEST['sidx'] ?? 1));
		$sord = trim($this->sanitize($_REQUEST['sord'] ?? 'asc'));

		$cond .=" and ep.anulata = 0";

        $query = "SELECT COUNT(DISTINCT(ep.destinatar_id)) as nr
            from {$this->tables['exp_prelucrate']} ep
			inner join clienti cld on cld.cod_cl = ep.destinatar_id
			LEFT JOIN zones cldz ON cldz.id = cld.zona_id
        	LEFT JOIN centre cldc on cldc.id = cldz.centru_id
			inner join localitati lcd ON lcd.cod_lc = cld.cod_lc
            WHERE {$cond}";
        $result = $this->db->QFetchArray($query);
        $count = !empty($result['nr']) ? $result['nr'] : 0;

        if( $count >0 ) {
            $total_pages = ceil($count/$limit); }
        else { $total_pages = 0; }
        if ($page > $total_pages) $page=$total_pages;
        if ($limit<0) $limit = 0;
        $start = $limit*$page - $limit; // do not put $limit*($page - 1)
        if ($start<0) $start = 0;
		$query = "SELECT ep.destinatar_id, cld.nume as destinatar, lcd.nume_lc as destinatar_localitate,
			COUNT(ep.cod_expeditie) as expeditii, SUM(ep.greutate) as greutate, SUM(ep.plicuri) as plicuri, SUM(ep.colete) as colete, SUM(ep.paleti) as paleti,
			SUM(ep.km_livrare + ep.km_preluare) as km, SUM(ep.liv_samb) as liv_sambata
			from {$this->tables['exp_prelucrate']} ep
			inner join clienti cld on cld.cod_cl = ep.destinatar_id
			LEFT JOIN zones cldz ON cldz.id = cld.zona_id
        	LEFT JOIN centre cldc on cldc.id = cldz.centru_id
			inner join localitati lcd ON lcd.cod_lc = cld.cod_lc
			WHERE {$cond}
			GROUP BY ep.destinatar_id
			ORDER BY " . $sidx . " " . $sord . " LIMIT " . $start . " , " . $limit;
			//echo $query;die;
        $sql = $this->db->QFetchRowArray($query);
        //compun raspunsul
        if (!empty($sql)) {
            foreach ($sql as $key => $row) {
            	if(empty($row['paleti'])) $row['paleti']=0;
                $responce->rows[$key]['id'] = $row['destinatar_id'];
                $responce->rows[$key]['cell'] = array($row['destinatar_localitate'],$row['destinatar'], $row['expeditii'], $row['greutate'],$row['plicuri'],$row['colete'],$row['paleti'],$row['km'],$row['liv_sambata']);
            }
        }
        $responce->page = $page;
        $responce->total = $total_pages;
        $responce->records = $count;
        return json_encode($responce);
    }

    function JSON_ListeExpeditiiDetaliiClient() {
    	$responce = new StdClass();
		$responce->page = 0;
		$responce->total = 0;
		$responce->records = 0;

		$operatiune = intval($_REQUEST['operatiune'] ?? 0);
		$centru_id = intval($_REQUEST['centru'] ?? 0);
        $client = intval($_REQUEST['client'] ?? 0);

		if($client == 0) return json_encode($responce);
        $cond = "ep.destinatar_id = {$client}";

		if($operatiune == 1) {
            $cond .= " AND IF(cld.zona_id > 0 and cldc.id > 0, cldc.id, lcd.cod_centru) in (47, 91) ";
        }
		else if($operatiune == 2) {
			$debug = 1;
		}
		else if($operatiune == 3 && $centru_id > 0) {
			$cond .= " AND IF(cld.zona_id > 0 and cldc.id > 0, cldc.id, lcd.cod_centru) = {$centru_id} ";
		}
		else return json_encode($responce);
        
		if(isset($_REQUEST['data_start']) && isset($_REQUEST['data_final'])){
	        $data_start = $this->TransformDate($_REQUEST['data_start']);
	        $data_final = $this->TransformDate($_REQUEST['data_final']);
			$cond .= " AND ep.data_expeditie between '{$data_start}' AND '{$data_final}'";
		}
		else {
			return json_encode($responce);
		}
      	

		$cond .=" and ep.anulata = 0 ";

		$page = intval($_REQUEST['page'] ?? 1);
		$limit = intval($_REQUEST['rows'] ?? 20);
		$sidx = trim($this->sanitize($_REQUEST['sidx'] ?? 1));
		$sord = trim($this->sanitize($_REQUEST['sord'] ?? 'asc'));

        $query = "SELECT COUNT(DISTINCT(ep.destinatar_id)) as nr
            from {$this->tables['exp_prelucrate']} ep
			left join clienti cle on cle.cod_cl = ep.expeditor_id
			left join clienti cld on cld.cod_cl = ep.destinatar_id
			LEFT JOIN zones cldz ON cldz.id = cld.zona_id
        	LEFT JOIN centre cldc on cldc.id = cldz.centru_id
			left join localitati lce ON lce.cod_lc = cle.cod_lc
			left join localitati lcd ON lcd.cod_lc = cld.cod_lc
            WHERE {$cond}";
        $result = $this->db->QFetchArray($query);
		$count = !empty($result['nr']) ? $result['nr'] : 0;

		//error_log($query);

        if( $count >0 ) {
            $total_pages = ceil($count/$limit); }
        else { $total_pages = 0; }
        if ($page > $total_pages) $page=$total_pages;
        if ($limit<0) $limit = 0;
        $start = $limit*$page - $limit; // do not put $limit*($page - 1)
        if ($start<0) $start = 0;
		$query = "SELECT ep.expeditor_id, cle.nume as expeditor, lce.nume_lc as expeditor_localitate,
			ep.expeditie, ep.greutate, ep.tip_obj, ep.piese, ep.plicuri, ep.colete, ep.paleti,(ep.km_livrare + ep.km_preluare) as km, ep.liv_samb
			from {$this->tables['exp_prelucrate']} ep
			left join clienti cle on cle.cod_cl = ep.expeditor_id
			left join clienti cld on cld.cod_cl = ep.destinatar_id
			LEFT JOIN zones cldz ON cldz.id = cld.zona_id
        	LEFT JOIN centre cldc on cldc.id = cldz.centru_id
			left join localitati lce ON lce.cod_lc = cle.cod_lc
			left join localitati lcd ON lcd.cod_lc = cld.cod_lc
			WHERE {$cond}
			ORDER BY " . $sidx . " " . $sord . " LIMIT " . $start . " , " . $limit . ";";
			//echo $query;die;
        $sql = $this->db->QFetchRowArray($query);
        //compun raspunsul
        if (!empty($sql)) {
            foreach ($sql as $key => $row) {
            	if(empty($row['paleti'])) $row['paleti']=0;
				$responce->rows[$key]['id'] = $row['expeditie'];
                $responce->rows[$key]['cell'] = array($row['expeditie'], $row['expeditor'],$row['expeditor_localitate'], $row['greutate'],$row['plicuri'],$row['colete'],$row['paleti'],$row['km'],$row['liv_samb']);
            }
        }
        $responce->page = $page;
        $responce->total = $total_pages;
        $responce->records = $count;
        return json_encode($responce);
    }

	function VizualizareListeExpeditii(){
		$vars = [];
		$operatiune = intval($_POST['operatiune'] ?? 0);
		$centru_id = intval($_REQUEST['centru'] ?? 0);

		$cond ="ep.anulata = 0 ";
		if(isset($_REQUEST['data_start']) && isset($_REQUEST['data_final'])){
            $data_start = $this->TransformDate($_REQUEST['data_start']);
            $data_final = $this->TransformDate($_REQUEST['data_final']);
			$vars['data_start'] = $this->CreateDate($this->TransformDate($_REQUEST['data_start']),'d/m/Y 00:00:00');
			$vars['data_final'] = $this->CreateDate($this->TransformDate($_REQUEST['data_final']),'d/m/Y 23:59:59');
			$cond .= " AND ep.data_expeditie between '{$data_start}' AND '{$data_final}'";
        }
		else {
			return $this->Parse($this->page_prefix . 'print_liste_expeditii.html', ['data_start' => 'EROARE', 'data_final' => '']);
		}

		if($operatiune == 1) {
            $cond .= " AND IF(cld.zona_id > 0 and cldc.id > 0, cldc.id, lcd.cod_centru) in (47, 91) ";
        }
		else if($operatiune == 2) {
			return $this->Parse($this->page_prefix . 'print_liste_expeditii.html', ['data_start' => 'PREA MULTE', 'data_final' => 'INCEARCA EXPORT']);
		}
		else if($operatiune == 3 && $centru_id > 0) {
			$cond .= " AND IF(cld.zona_id > 0 and cldc.id > 0, cldc.id, lcd.cod_centru) = {$centru_id} ";
		}
		else {
			return $this->Parse($this->page_prefix . 'print_liste_expeditii.html',  ['data_start' => 'EROARE', 'data_final' => '']);
		}

		$query_sum = "SELECT IF(cld.zona_id > 0 and cldc.id > 0, cldc.id, lcd.cod_centru) as destinatar_centru_id, 
			COUNT(ep.expeditie) as expeditii, SUM(ep.greutate) as greutate, SUM(ep.plicuri) as plicuri,
			SUM(ep.colete) as colete, SUM(ep.paleti) as paleti, SUM(ep.km_preluare+ep.km_livrare) as km
			from {$this->tables['exp_prelucrate']} ep
			left join clienti cld on cld.cod_cl = ep.destinatar_id
			LEFT JOIN zones cldz ON cldz.id = cld.zona_id
			LEFT JOIN centre cldc on cldc.id = cldz.centru_id
			left join localitati lcd ON lcd.cod_lc = cld.cod_lc
			WHERE {$cond}
			GROUP BY IF(cld.zona_id > 0 and cldc.id > 0, cldc.id, lcd.cod_centru)";
        $sql_sum = $this->db->QFetchArray($query_sum);
		if(!empty($sql_sum)){
			$vars['total_expeditii'] = $sql_sum['expeditii'];
			$vars['total_greutate'] = $sql_sum['greutate'];
			$vars['total_plicuri'] = $sql_sum['plicuri'];
			$vars['total_colete'] = $sql_sum['colete'];
			$vars['total_paleti'] = $sql_sum['paleti'];
			$vars['total_km'] = $sql_sum['km'];
		}

		$query = "SELECT ep.expeditie, ep.data_expeditie, ep.tip_exp,
			ep.tip_obj, ep.piese, ep.plicuri, ep.colete, ep.paleti, ep.greutate, ep.ramburs,
			ep.km_livrare, ep.curier_livrare_id,
			ep.mod_plata, ep.moneda, ep.tva, ep.valoare_totala_expeditie,
			cle.nume as expeditor, cld.nume as destinatar,
			lce.nume_lc as expeditor_localitate, lcd.nume_lc as destinatar_localitate,
			IF(cle.zona_id > 0 and clec.id > 0, clec.nume, cee.nume) as expeditor_centru, 
        	IF(cle.zona_id > 0 and clec.id > 0, clec.label, cee.label) as expeditor_centru_cod,
			IF(cld.zona_id > 0 and cldc.id > 0, cldc.nume, ced.nume) as destinatar_centru, 
			IF(cld.zona_id > 0 and cldc.id > 0, cldc.label, ced.label) as destinatar_centru_cod
			from {$this->tables['exp_prelucrate']} ep
			left join clienti cle on cle.cod_cl = ep.expeditor_id
			left join clienti cld on cld.cod_cl = ep.destinatar_id
			LEFT JOIN zones clez ON clez.id = cle.zona_id
        	LEFT JOIN centre clec on clec.id = clez.centru_id
			LEFT JOIN zones cldz ON cldz.id = cld.zona_id
        	LEFT JOIN centre cldc on cldc.id = cldz.centru_id
			left join localitati lce ON lce.cod_lc = cle.cod_lc
			left join localitati lcd ON lcd.cod_lc = cld.cod_lc
			left join centre cee ON cee.id = lce.cod_centru
			left join centre ced ON ced.id = lcd.cod_centru
            WHERE {$cond}
			ORDER BY lcd.nume_lc";
        $sql = $this->db->QFetchRowArray($query);

		$items = '';
        if (!empty($sql)) {
        	$localitate = '';
            foreach ($sql as $key => $row) {
            	$var=[];
				$var['class_localitate'] = '';
            	if($localitate != $row['destinatar_localitate']){
            		$localitate = $row['destinatar_localitate'];
					$var['localitate'] = $localitate;
            	}else
					$var['class_localitate'] = 'display:none;';

				$var['destinatar'] = ucwords($row['destinatar']);
				$var['expeditor'] = ucwords($row['expeditor']);
				$var['expeditie'] = $row['expeditie'];
				$var['greutate'] = $row['greutate'].'Kg';

			    if($row['km_livrare'] != '0.00') $var['destinatar'] .= ' ('.$row['destinatar_localitate'].')';

				if(!empty($row['paleti']))	$var['continut'] = $row['paleti'].' palet';
				if(!empty($row['plicuri']))	$var['continut'] = $row['plicuri'].' plic';
				if(!empty($row['colete']))	$var['continut'] = $row['colete'].' colete';

				$var['class_de_platit'] = 'display:none;';
				if(empty($row['curier_livrare_id']) && empty($row['mod_plata'])){
					$row['valoare_totala_expeditie'] = round($row['valoare_totala_expeditie'],2);
					$row['tva'] = round($row['tva'],2);
					$row['valoare_totala_expeditie_tva'] = $row['valoare_totala_expeditie']+$row['tva'];

					$var['de_platit'] = 'De incasat PLATA LA DESTINATIE: NET:'.$row['valoare_totala_expeditie'].' + TVA:'.$row['tva'].'  TOTAL:'.($row['valoare_totala_expeditie_tva']).' '.(ExpeditieDto::MONEDA[$row['moneda']] ?? $row['moneda']);

					if(!empty($row['ramburs']) ||  $row['ramburs'] != 0.00)
						$var['de_platit'] = 'De incasat RAMBURS: '.$row['ramburs'].' '.(ExpeditieDto::MONEDA[$row['moneda']] ?? $row['moneda']).';<br/> '.$var['de_platit'];

					$var['class_de_platit'] = '';
				}
				$var['id'] = $key+1;
				$items .= $this->Parse($this->page_prefix . 'print_liste_expeditii_row.html', $var);
            }
        }
		$vars['items'] = $items;
        $html = $this->Parse($this->page_prefix . 'print_liste_expeditii.html', $vars);
		return $html;
    }

    function ExportListeExpeditii() {
		$vars = [];
		$centru_id = intval($_GET['centru'] ?? 0);
		$operatiune = intval($_GET['operatiune'] ?? 0);

		$cond ="ep.anulata = 0 ";
		if(isset($_GET['data_start']) && isset($_GET['data_final'])){
            $data_start = $this->TransformDate($_GET['data_start']);
            $data_final = $this->TransformDate($_GET['data_final']);
			$vars['data_start'] = $this->CreateDate($this->TransformDate($_GET['data_start']),'d/m/Y 00:00:00');
			$vars['data_final'] = $this->CreateDate($this->TransformDate($_GET['data_final']),'d/m/Y 23:59:59');
			$cond .= " AND ep.data_expeditie between '{$data_start}' AND '{$data_final}'";
        }
		else {
			return 'EROARE';
		}

		if($operatiune == 1) {
            $cond .= " AND IF(cld.zona_id > 0 and cldc.id > 0, cldc.id, ced.id) in (47, 91) ";
        }
		else if($operatiune == 2) {
			$flag = 0;
		}
		else if($operatiune == 3 && $centru_id > 0) {
			$cond .= " AND IF(cld.zona_id > 0 and cldc.id > 0, cldc.id, ced.id) = {$centru_id} ";
		}
		else {
			return 'EROARE';
		}

		return $this->ExportExpeditii($cond);
    }



/*/////////////////////////////////////////////////////////////
				 END ListeExpeditii
/////////////////////////////////////////////////////////////*/




/*/////////////////////////////////////////////////////////////
				 START Rapoarte Traseu
/////////////////////////////////////////////////////////////*/

function RapoarteTraseu() {
        $this->vars['title_page'] = 'Rapoarte Traseu';
        $vars = [];
        $vars['data_start'] = date('d.m.Y');
        $vars['data_final'] = date('d.m.Y');
        $vars['centre_expeditie'] = $this->GenerareListaCentre([],'centre_expeditie');
		$vars['centre_destinatie'] = $this->GenerareListaCentre([],'centre_destinatie');

        return $this->Parse($this->page_prefix . 'rapoarte_traseu.html', $vars);
    }

    function JSON_RapoarteListeExpeditii() {
    	$responce = new StdClass();
		$cond = '';
        if(isset($_REQUEST['data_start']) && isset($_REQUEST['data_final'])){
	        $data_start = $this->TransformDate($_REQUEST['data_start']);
	        $data_final = $this->TransformDate($_REQUEST['data_final']);
			$cond .= " AND ep.data_expeditie between '{$data_start}' AND '{$data_final}'";
	    }
		else {
			$cond = '1=2';
		}
		$categorie=1;
		if(isset($_REQUEST['categorie']))
				$categorie = $_REQUEST['categorie'];
		if($categorie==1) $cond = "1=1".$cond;
		else if($categorie==2) $cond = 'ep.tip_obj = 1'.$cond;
		else if($categorie==3) $cond = 'ep.tip_obj = 2'.$cond;
		else if($categorie==4) $cond = 'ep.tip_obj = 3'.$cond;
		else if($categorie==5) $cond = '(ep.tip_obj = 2 OR ep.tip_obj = 3) '.$cond;
		else $cond = '1=2';

		$centre_destinatie = 0;
		if(isset($_REQUEST['centre_destinatie']))
		{
			$centre_destinatie = $_REQUEST['centre_destinatie'];
			$centre_destinatie = str_replace('_',',',$centre_destinatie);
			if(substr($centre_destinatie,0,1) == ',')
				$centre_destinatie = substr($centre_destinatie,1);

			if(!empty($centre_destinatie))
				$cond .= "AND IF(cld.zona_id > 0 and cldc.id > 0, cldc.id, ced.id) IN (".$centre_destinatie.")";	
		}

		$centre_expeditie = 0;
		if(isset($_REQUEST['centre_expeditie']))
		{
			$centre_expeditie = $_REQUEST['centre_expeditie'];
			$centre_expeditie = str_replace('_',',',$centre_expeditie);

			if(substr($centre_expeditie,0,1) == ',')
				$centre_expeditie = substr($centre_expeditie,1);
			if(!empty($centre_expeditie))
				$cond .= " and IF(cle.zona_id > 0 and clec.id > 0, clec.id, cee.id) IN (".$centre_expeditie.") ";
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

		$cond .= " and ep.anulata = 0 ";
    	$query = "SELECT COUNT(ep.cod_expeditie) as nr
			from {$this->tables['exp_prelucrate']} ep
			left join clienti cle on cle.cod_cl = ep.expeditor_id
			left join clienti cld on cld.cod_cl = ep.destinatar_id
			LEFT JOIN zones clez ON clez.id = cle.zona_id
        	LEFT JOIN centre clec on clec.id = clez.centru_id
			LEFT JOIN zones cldz ON cldz.id = cld.zona_id
        	LEFT JOIN centre cldc on cldc.id = cldz.centru_id
			left join localitati lce ON lce.cod_lc = cle.cod_lc
			left join localitati lcd ON lcd.cod_lc = cld.cod_lc
			left join centre cee ON cee.id = lce.cod_centru
			left join centre ced ON ced.id = lcd.cod_centru
			WHERE {$cond}";
        $result = $this->db->QFetchArray($query);
        $count = !empty($result['nr']) ? $result['nr'] : 0;

        if( $count >0 ) {
            $total_pages = ceil($count/$limit); }
        else { $total_pages = 0; }
        if ($page > $total_pages) $page=$total_pages;
        if ($limit<0) $limit = 0;
        $start = $limit*$page - $limit; // do not put $limit*($page - 1)
        if ($start<0) $start = 0;

		$query = "SELECT ep.expeditie,ep.greutate,ep.tip_obj,ep.piese,ep.plicuri,ep.colete,ep.paleti,ep.data_expeditie,
		  	cle.nume as expeditor ,cld.nume as destinatar, 
		  	IF(cle.zona_id > 0 and clec.id > 0, clec.nume, cee.nume) as expeditor_centru, 
        	IF(cle.zona_id > 0 and clec.id > 0, clec.label, cee.label) as expeditor_centru_cod,
			IF(cld.zona_id > 0 and cldc.id > 0, cldc.nume, ced.nume) as destinatar_centru, 
			IF(cld.zona_id > 0 and cldc.id > 0, cldc.label, ced.label) as destinatar_centru_cod
			from {$this->tables['exp_prelucrate']} ep
			left join clienti cle on cle.cod_cl = ep.expeditor_id
			left join clienti cld on cld.cod_cl = ep.destinatar_id
			LEFT JOIN zones clez ON clez.id = cle.zona_id
        	LEFT JOIN centre clec on clec.id = clez.centru_id
			LEFT JOIN zones cldz ON cldz.id = cld.zona_id
        	LEFT JOIN centre cldc on cldc.id = cldz.centru_id
			left join localitati lce ON lce.cod_lc = cle.cod_lc
			left join localitati lcd ON lcd.cod_lc = cld.cod_lc
			left join centre cee ON cee.id = lce.cod_centru
			left join centre ced ON ced.id = lcd.cod_centru
			WHERE {$cond}
			ORDER BY " . $sidx . ", expeditor_centru, destinatar_centru  " . $sord . " LIMIT " . $start . " , " . $limit;
			//echo $query;die;
        $sql = $this->db->QFetchRowArray($query);

        $total_piese = 0;
		$total_greutate = 0.00;

        if (!empty($sql)) {
            foreach ($sql as $key => $row) {
            	if(empty($row['paleti'])) $row['paleti'] = 0;
				if(empty($row['colete'])) $row['colete'] = 0;
				if(empty($row['plicuri'])) $row['plicuri'] = 0;
				//$row['greutate'] = round($row['greutate'],1);
            	$row['nr_piese'] = $row['plicuri']+$row['colete']+$row['paleti'];

				$total_piese += $row['nr_piese'];
				$total_greutate += $row['greutate'];

                $responce->rows[$key]['id'] = $row['expeditie'];
                $responce->rows[$key]['cell'] = array($row['expeditie'],$row['nr_piese'], $row['greutate'], $row['expeditor'],$row['destinatar'],$row['expeditor_centru'],$row['destinatar_centru'],$row['data_expeditie']);
            }
        }
        $responce->page = $page;
        $responce->total = $total_pages;
        $responce->records = $count;

		$responce->userdata['expeditie'] = $count.' Expeditii';
		$responce->userdata['nr_piese'] = $total_piese;
		$responce->userdata['greutate'] = $total_greutate.'kg';

        return json_encode($responce);
    }

	function PrintRapoarteExpeditii(){
		$vars = [];
		$cond = '';
        if(isset($_REQUEST['data_start']) && isset($_REQUEST['data_final'])){
	        $data_start = $this->TransformDate($_REQUEST['data_start']);
	        $data_final = $this->TransformDate($_REQUEST['data_final']);
			$cond .= " AND ep.data_expeditie between '{$data_start}' AND '{$data_final}'";
	    }
		else {
			$cond = '1=2';
		}

        $categorie = $_REQUEST['categorie'];
		if($categorie==1) $cond = "1=1".$cond;
		else if($categorie==2) $cond = 'ep.tip_obj = 1'.$cond;
		else if($categorie==3) $cond = 'ep.tip_obj = 2'.$cond;
		else if($categorie==4) $cond = 'ep.tip_obj = 3'.$cond;
		else if($categorie==5) $cond = '(ep.tip_obj = 2 OR ep.tip_obj = 3) '.$cond;
		else $cond = '1=2';

		$centre_destinatie = 0;
		if(isset($_REQUEST['centre_destinatie']))
		{
			$centre_destinatie = $_REQUEST['centre_destinatie'];
			$centre_destinatie = str_replace('_',',',$centre_destinatie);
			if(substr($centre_destinatie,0,1) == ',')
				$centre_destinatie = substr($centre_destinatie,1);

			if(!empty($centre_destinatie))
				$cond .= "AND IF(cld.zona_id > 0 and cldc.id > 0, cldc.id, ced.id) IN (".$centre_destinatie.")";	
		}

		$centre_expeditie = 0;
		if(isset($_REQUEST['centre_expeditie']))
		{
			$centre_expeditie = $_REQUEST['centre_expeditie'];
			$centre_expeditie = str_replace('_',',',$centre_expeditie);

			if(substr($centre_expeditie,0,1) == ',')
				$centre_expeditie = substr($centre_expeditie,1);
			if(!empty($centre_expeditie))
				$cond .= " and IF(cle.zona_id > 0 and clec.id > 0, clec.id, cee.id) IN (".$centre_expeditie.") ";
		}

		$nr_linii=5;

		$cond .= " and ep.anulata = 0 ";
		$query = "SELECT ep.expeditie,ep.greutate,ep.tip_obj,ep.piese,ep.plicuri,ep.colete,ep.paleti,ep.data_expeditie,
			cle.nume,cld.nume, 
			IF(cle.zona_id > 0 and clec.id > 0, clec.nume, cee.nume) as expeditor_centru, 
			IF(cle.zona_id > 0 and clec.id > 0, clec.label, cee.label) as expeditor_centru_cod,
			IF(cld.zona_id > 0 and cldc.id > 0, cldc.nume, ced.nume) as destinatar_centru,
			IF(cld.zona_id > 0 and cldc.id > 0, cldc.label, ced.label) as destinatar_centru_cod
			from {$this->tables['exp_prelucrate']} ep
			left join clienti cle on cle.cod_cl = ep.expeditor_id
			left join clienti cld on cld.cod_cl = ep.destinatar_id
			LEFT JOIN zones clez ON clez.id = cle.zona_id
			LEFT JOIN centre clec on clec.id = clez.centru_id
			LEFT JOIN zones cldz ON cldz.id = cld.zona_id
			LEFT JOIN centre cldc on cldc.id = cldz.centru_id
			left join localitati lce ON lce.cod_lc = cle.cod_lc
			left join localitati lcd ON lcd.cod_lc = cld.cod_lc
			left join centre cee ON cee.id = lce.cod_centru
			left join centre ced ON ced.id = lcd.cod_centru
			WHERE {$cond}
			ORDER BY ep.expeditie, expeditor_centru, destinatar_centru  ASC";
        $sql = $this->db->QFetchRowArray($query);

        $vars['total_greutate'] = 0.00;
		$vars['total_piese'] = 0;
		$items='';
        if (!empty($sql)) {
        	$x=46;
            foreach ($sql as $key => $row) {
            	if(empty($row['paleti'])) $row['paleti'] = 0;
				if(empty($row['colete'])) $row['colete'] = 0;
				if(empty($row['plicuri'])) $row['plicuri'] = 0;
				$row['nr_piese'] = $row['plicuri']+$row['colete']+$row['paleti'];
            	$row['id'] = $key+1;

				$nr_linii++;
				if($nr_linii>$x){
					$x=48;
					$items .= '</table>
	<div style="page-break-before:always"></div>
	<table width="100%" style="font-size: 13px; margin-top:10px;">
	<tr>
		<td width="11%" style="border-bottom: 1px solid #000;"><strong>Expeditie</strong></td>
		<td width="10%" style="border-bottom: 1px solid #000;"><strong>Data</strong></td>
		<td width="5%" style="border-bottom: 1px solid #000;"><strong>Piese</strong></td>
		<td width="6%" style="border-bottom: 1px solid #000;"><strong>Greutatea</strong></td>
		<td width="20%" style="border-bottom: 1px solid #000;"><strong>Expeditorul</strong></td>
		<td width="20%" style="border-bottom: 1px solid #000;"><strong>Destinatarul</strong></td>
		<td width="14%" style="border-bottom: 1px solid #000;"><strong>Centru Exp</strong></td>
		<td width="14%" style="border-bottom: 1px solid #000;"><strong>Centru Dest</strong></td>
	</tr>';
					$nr_linii=1;
				}

				$items .= $this->Parse($this->page_prefix . 'print_rapoarte_traseu_row.html', $row);
				$vars['total_greutate'] += $row['greutate'];
				$vars['total_piese'] += $row['nr_piese'];
            }
			$vars['total_expeditii'] = $row['id'];
        }

		$lista_centre_destinatie = '';
		$lista_centre_expeditie = '';

		$lista_centre = $this->GetCentre();
		$centre = explode(',',$centre_destinatie);
		foreach($centre as $row){
			$lista_centre_destinatie .= $lista_centre[$row].', ';
		}
		$lista_centre_destinatie = substr($lista_centre_destinatie,0,-2);

		$centre = explode(',',$centre_expeditie);
		foreach($centre as $row){
			$lista_centre_expeditie .= $lista_centre[$row].', ';
		}
		$lista_centre_expeditie = substr($lista_centre_expeditie,0,-2);
		$vars['centre_expeditie'] = $lista_centre_expeditie;
		$vars['centre_destinatie'] = $lista_centre_destinatie;

        $vars['items'] = $items;
        $html = $this->Parse($this->page_prefix . 'print_rapoarte_traseu.html', $vars);
		return $html;
	}

	function ExportRapoarteExpeditii(){
		$vars = [];
		$cond = '';
        if(!empty($_REQUEST['data_start']) && !empty($_REQUEST['data_final'])){
	        $data_start = $this->TransformDate($_REQUEST['data_start']);
	        $data_final = $this->TransformDate($_REQUEST['data_final']);
			$cond .= " AND ep.data_expeditie between '{$data_start}' AND '{$data_final}'";

			//limita data
			$ds = DateTime::createFromFormat('Y-m-d', $data_start);
			$df = DateTime::createFromFormat('Y-m-d', $data_final);
			if($ds && $df && intval($ds->diff($df, true)->format('%a')) > 92) {
				return 0;
			}
	    }
		else return 0;

		$categorie = 0;
	    if(isset($_REQUEST['categorie']))
        	$categorie = $_REQUEST['categorie'];
		if($categorie==1) $cond = "1=1".$cond;
		else if($categorie==2) $cond = 'ep.tip_obj = 1'.$cond;
		else if($categorie==3) $cond = 'ep.tip_obj = 2'.$cond;
		else if($categorie==4) $cond = 'ep.tip_obj = 3'.$cond;
		else if($categorie==5) $cond = '(ep.tip_obj = 2 OR ep.tip_obj = 3) '.$cond;
		else $cond = '1=2';

	    if(isset($_REQUEST['centre_destinatie'])) {
			$centre_destinatie = $_REQUEST['centre_destinatie'];
			$centre_destinatie = str_replace('_',',',$centre_destinatie);
			if(substr($centre_destinatie,0,1) == ',')
				$centre_destinatie = substr($centre_destinatie,1);
			if(!empty($centre_destinatie))
				$cond.=" AND IF(cld.zona_id > 0 and cldc.id > 0, cldc.id, ced.id) IN (".$centre_destinatie.")";
		}

	    if(isset($_REQUEST['centre_expeditie'])) {
			$centre_expeditie = $_REQUEST['centre_expeditie'];
			$centre_expeditie = str_replace('_',',',$centre_expeditie);
			if(substr($centre_expeditie,0,1) == ',')
				$centre_expeditie = substr($centre_expeditie,1);
			if(!empty($centre_expeditie))
				$cond.=" AND IF(cle.zona_id > 0 and clec.id > 0, clec.id, cee.id) IN (".$centre_expeditie.")";
		}

		$cond .= " and ep.anulata = 0 ";
        $query = "SELECT ep.expeditie as 'Nr. NT' , ep.data_expeditie as 'Data colectarii',
			cle.nume as 'Expeditor', lce.nume_lc as 'Localitate expeditor', cee.nume as 'Centru expeditor',
			ep.km_preluare as 'Km. colectare',
			cld.nume as 'Destinatar', lcd.nume_lc as 'Localitate destinatar', IF(cld.zona_id > 0 and cldc.id > 0, cldc.nume, ced.nume) as 'Centru destinatar',
			ep.km_livrare as 'Km. livrare',
			ep.colete as 'Piese', ep.plicuri as 'Plic', ep.paleti as 'Palet', ep.greutate as 'Greutate'
            from {$this->tables['exp_prelucrate']} ep
			left join clienti cle on cle.cod_cl = ep.expeditor_id
			left join clienti cld on cld.cod_cl = ep.destinatar_id
			LEFT JOIN zones clez ON clez.id = cle.zona_id
        	LEFT JOIN centre clec on clec.id = clez.centru_id
			LEFT JOIN zones cldz ON cldz.id = cld.zona_id
        	LEFT JOIN centre cldc on cldc.id = cldz.centru_id
			left join localitati lce ON lce.cod_lc = cle.cod_lc
			left join localitati lcd ON lcd.cod_lc = cld.cod_lc
			left join centre cee ON cee.id = lce.cod_centru
			left join centre ced ON ced.id = lcd.cod_centru
            WHERE {$cond}
            ORDER BY ep.data_expeditie, cle.nume ASC";

		$sql = $this->db->Query($query, [], false);

		$options = new Options(
    		SHOULD_ADD_BOM: false,
		);
		$writer = new Writer($options);
		$writer->openToBrowser("raport_export_" . date("Y-m-d") . ".csv");
		$i = 0;
		ob_start();
		if($sql) {
			if($row = $sql->fetch(PDO::FETCH_ASSOC)) {
				$row_header = Row::fromValues(array_keys($row));
				$writer->addRow($row_header);
				$row['Data colectarii'] = $this->CreateDate($row['Data colectarii']);

				if(!empty($row['Plic'])){
					$row['Plic'] = 'DA';
					$row['Piese'] = 1;
					$row['Palet'] = 'NU';
				}else if(!empty($row['Palet'])){
					$row['Palet'] = 'DA';
					$row['Piese'] = 1;
					$row['Plic'] = 'NU';
				}else{
					$row['Palet'] = 'NU';
					$row['Plic'] = 'NU';
				}
				$row_values = Row::fromValues(array_values($row));
				$writer->addRow($row_values);
			}
			while($row = $sql->fetch(PDO::FETCH_ASSOC)) {
				$row['Data colectarii'] = $this->CreateDate($row['Data colectarii']);

				if(!empty($row['Plic'])){
					$row['Plic'] = 'DA';
					$row['Piese'] = 1;
					$row['Palet'] = 'NU';
				}else if(!empty($row['Palet'])){
					$row['Palet'] = 'DA';
					$row['Piese'] = 1;
					$row['Plic'] = 'NU';
				}else{
					$row['Palet'] = 'NU';
					$row['Plic'] = 'NU';
				}
				$row_in = Row::fromValues(array_values($row));
				$writer->addRow($row_in);
				$i++;
				if($i % 1000 === 0) {
					ob_flush();
				}
			}
		}
		ob_end_flush();
		$writer->close();
		exit();
	}
/*/////////////////////////////////////////////////////////////
				 END Rapoarte Traseu
/////////////////////////////////////////////////////////////*/

/*/////////////////////////////////////////////////////////////
				 START Diferenta Expeditii Scan
/////////////////////////////////////////////////////////////*/
	function DifExpScan() {
        $this->vars['title_page'] = 'Diferenta Expeditie Scanare';
        $vars = [];
        $vars['data_start'] = date("d.m.Y");
		$vars['centru'] = $this->ComboCentre(47);
        return $this->Parse($this->page_prefix . 'dif_exp_scan.html', $vars);
    }

	function JSON_DifExpScan() {
        $responce = new StdClass();
		$centru_id = intval($_REQUEST['centru'] ?? 0);

		if(isset($_REQUEST['data_start']))
			$data_begin = $this->TransformDate($_REQUEST['data_start']);
	    else
			$data_begin = date('Y-m-d');

        $cond = " ep.data_expeditie='".$data_begin."'";

		if($centru_id > 0)
			$cond .= " AND IF(cle.zona_id > 0 and clec.id > 0, clec.id, cee.id) = {$centru_id}";


		$page = intval($_REQUEST['page'] ?? 1);
		$limit = intval($_REQUEST['rows'] ?? 20);
		$sidx = trim($this->sanitize($_REQUEST['sidx'] ?? 1));
		$sord = trim($this->sanitize($_REQUEST['sord'] ?? 'asc'));

    	$queryC = "SELECT COUNT(ep.expeditie) as nr
			from {$this->tables['exp_prelucrate']} as ep
			left join clienti cle on cle.cod_cl = ep.expeditor_id
			LEFT JOIN zones clez ON clez.id = cle.zona_id
        	LEFT JOIN centre clec on clec.id = clez.centru_id
			left join localitati lce ON lce.cod_lc = cle.cod_lc
			left join centre cee ON cee.id = lce.cod_centru
			WHERE {$cond} and ep.anulata = 0
			GROUP BY IF(cle.zona_id > 0 and clec.id > 0, clec.id, cee.id)";

        $result = $this->db->QFetchArray($queryC);
        $count = !empty($result['nr']) ? $result['nr'] : 0;

        if( $count >0 ) {
            $total_pages = ceil($count/$limit); }
        else { $total_pages = 0; }
        if ($page > $total_pages) $page=$total_pages;
        if ($limit<0) $limit = 0;
        $start = $limit*$page - $limit; // do not put $limit*($page - 1)
        if ($start<0) $start = 0;

		$query = "SELECT IF(cle.zona_id > 0 and clec.id > 0, clec.id, cee.id) as centru_id, 
			IF(cle.zona_id > 0 and clec.id > 0, clec.nume, cee.nume) as centru, 
			count(ep.expeditie) as nr
			from {$this->tables['exp_prelucrate']} as ep
			left join clienti cle on cle.cod_cl = ep.expeditor_id
			LEFT JOIN zones clez ON clez.id = cle.zona_id
        	LEFT JOIN centre clec on clec.id = clez.centru_id
			left join localitati lce ON lce.cod_lc = cle.cod_lc
			left join centre cee ON cee.id = lce.cod_centru
			WHERE {$cond} and ep.anulata = 0
			GROUP BY IF(cle.zona_id > 0 and clec.id > 0, clec.id, cee.id)
			ORDER BY " . $sidx . " " . $sord . " LIMIT " . $start . " , " . $limit . ";";
		//echo $query;

        $sql = $this->db->QFetchRowArray($query);
        if (!empty($sql)) {
            foreach ($sql as $key => $row) {
                $responce->rows[$key]['id'] = $row['centru_id'];
                $responce->rows[$key]['cell'] = array($row['centru'], $row['nr']);
            }
        }
        $responce->page = $page;
        $responce->total = $total_pages;
        $responce->records = $count;

        //$responce->userdata['eroare'] = $count.$queryC;

        return json_encode($responce);
    }

/*/////////////////////////////////////////////////////////////
				 END Diferenta Expeditii Scan
/////////////////////////////////////////////////////////////*/

/*/////////////////////////////////////////////////////////////
				 START INTRODUCERE
/////////////////////////////////////////////////////////////*/

	function IntroducereExpeditie($expeditie) {
        $this->vars['title_page'] = 'Introducere Expeditie';
        $this->vars['title_info'] = '<input type="text" name="data_introducere" id="data_introducere" class="field_input w90" style="margin-left:200px;" value="'.date('d/m/Y').'" title="Data" />';

		$var = [];
		$expeditie = intval($this->sanitize($expeditie ?? 0));

		if($expeditie > 0){
			if(false === ($exp = $this->GetValues($expeditie))) return 'Expeditie Invalida';
        	$var = ExpeditieDto::sqlExpToUi(array_merge($exp, ['can_pret_impus' => $this->can_pret_impus]), $this->user_profile, $this->user_rights);
			$var['PRET_IMPUS_DISABLE'] = $this->can_pret_impus && $exp['tip_exp'] == 0 ? '' : 'disabled';
			$var['PRET_IMPUS_VISIBLE'] = $this->can_pret_impus ? '' : 'display:none';
			//error_log('d'.$var['PRET_IMPUS']);
			$var['CODURI'] = implode('
', ExpeditieDto::getPuisoriForAwb($exp['expeditie'], $exp['colete']));
		}
		else {
			$var['PRET_IMPUS_DISABLE'] = $this->can_pret_impus ? '' : 'disabled';
			$var['PRET_IMPUS_VISIBLE'] = $this->can_pret_impus ? '' : 'display:none';
		}

		if(empty($var)){
			$var['PROC_ASIG'] = '1.00';
			$var['HT'] = '';
			$var['TVA'] = '';
			$var['TTC'] = '';
			$var['MONEDA'] = "LEI";
		}

		//butoane
		$var['cruser'] = ($this->user_profile == 10) ? 1:0;
		$var['BUTON_ADAUGA_EXPEDITIE'] = $var['BUTON_MODIFICA_EXPEDITIE'] = $var['BUTON_STERGE_EXPEDITIE'] = "";
		if(!($this->user_profile == 10 || is_array($this->user_rights) && in_array('introducere', $this->user_rights)))
			$var['BUTON_ADAUGA_EXPEDITIE'] = 'style="visibility: hidden"';
		if(!($this->user_profile == 10 || is_array($this->user_rights) && in_array('editare', $this->user_rights)))
			$var['BUTON_MODIFICA_EXPEDITIE'] = 'style="visibility: hidden"';
		if(!($this->user_profile == 10 || is_array($this->user_rights) && in_array('stergere', $this->user_rights)))
			$var['BUTON_STERGE_EXPEDITIE'] = 'style="visibility: hidden"';

		$vars['formular_expeditie'] = $this->Parse($this->page_prefix . 'introducere_detalii.html', $var);

        return $this->Parse($this->page_prefix . 'introducere.html', $vars);
    }

	function IntroducereDetalii() {
		$expeditie = intval($this->sanitize($_POST['exp'] ?? 0));
		if($expeditie == 0) return 'Invalid ID';

		if(false === ($exp = $this->GetValues($expeditie))) return 'Expeditie Invalida';
		$vars = ExpeditieDto::sqlExpToUi(array_merge($exp, ['can_pret_impus' => $this->can_pret_impus]), $this->user_profile, $this->user_rights);
		$vars['CODURI'] = implode('
', ExpeditieDto::getPuisoriForAwb($exp['expeditie'], $exp['colete']));
		//error_log($vars['PRET_IMPUS']);
		$vars['PRET_IMPUS_DISABLE'] = $this->can_pret_impus && $exp['tip_exp'] == 0 ? '' : 'disabled';
		$vars['PRET_IMPUS_VISIBLE'] = $this->can_pret_impus ? '' : 'display:none';
		//butoane
		$vars['cruser'] = ($this->user_profile == 10) ? 1:0;
		$vars['BUTON_ADAUGA_EXPEDITIE'] = $vars['BUTON_MODIFICA_EXPEDITIE'] = $vars['BUTON_STERGE_EXPEDITIE'] = "";
		if(!($this->user_profile == 10 || is_array($this->user_rights) && in_array('introducere', $this->user_rights)))
			$vars['BUTON_ADAUGA_EXPEDITIE'] = 'style="visibility: hidden"';
		if(!($this->user_profile == 10 || is_array($this->user_rights) && in_array('editare', $this->user_rights)))
			$vars['BUTON_MODIFICA_EXPEDITIE'] = 'style="visibility: hidden"';
		if(!($this->user_profile == 10 || is_array($this->user_rights) && in_array('stergere', $this->user_rights)))
			$vars['BUTON_STERGE_EXPEDITIE'] = 'style="visibility: hidden"';

        return $this->Parse($this->page_prefix . 'introducere_detalii.html', $vars);
    }


	function JSON_Introducere() {
		$responce = new StdClass();
        if(empty($_REQUEST['data']))
            $data = date('Y-m-d');
        else
            $data = $this->TransformDate($_REQUEST['data'],'/');

        $cond = " ep.data_expeditie='".$data."' and ep.anulata = 0";

		if($this->user_profile == 11){
			$cond .= " AND ep.expeditor_id = 557315";
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

		$query = "SELECT COUNT(ep.cod_expeditie) as nr
			from {$this->tables['exp_prelucrate']} ep use index (data_expeditie)
			left join clienti cle on cle.cod_cl = ep.expeditor_id
			left join clienti cld on cld.cod_cl = ep.destinatar_id
			LEFT JOIN zones clez ON clez.id = cle.zona_id
			LEFT JOIN centre clec on clec.id = clez.centru_id
			LEFT JOIN zones cldz ON cldz.id = cld.zona_id
			LEFT JOIN centre cldc on cldc.id = cldz.centru_id
			left join localitati lce ON lce.cod_lc = cle.cod_lc
			left join localitati lcd ON lcd.cod_lc = cld.cod_lc
			left join centre cee ON cee.id = lce.cod_centru
			left join centre ced ON ced.id = lcd.cod_centru
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
        $query = "SELECT ep.expeditie, ep.data_expeditie,
			cle.nume as expeditor, cld.nume as destinatar,
			IF(cle.zona_id > 0 and clec.id > 0, clec.nume, cee.nume) as expeditor_centru, 
        	IF(cle.zona_id > 0 and clec.id > 0, clec.label, cee.label) as expeditor_centru_cod,
			IF(cld.zona_id > 0 and cldc.id > 0, cldc.nume, ced.nume) as destinatar_centru, 
			IF(cld.zona_id > 0 and cldc.id > 0, cldc.label, ced.label) as destinatar_centru_cod
            from {$this->tables['exp_prelucrate']} ep use index (data_expeditie)
			left join clienti cle on cle.cod_cl = ep.expeditor_id
			left join clienti cld on cld.cod_cl = ep.destinatar_id
			LEFT JOIN zones clez ON clez.id = cle.zona_id
			LEFT JOIN centre clec on clec.id = clez.centru_id
			LEFT JOIN zones cldz ON cldz.id = cld.zona_id
			LEFT JOIN centre cldc on cldc.id = cldz.centru_id
			left join localitati lce ON lce.cod_lc = cle.cod_lc
			left join localitati lcd ON lcd.cod_lc = cld.cod_lc
			left join centre cee ON cee.id = lce.cod_centru
			left join centre ced ON ced.id = lcd.cod_centru
            WHERE {$cond}
            ORDER BY " . $sidx . " " . $sord . " LIMIT " . $start . " , " . $limit;
		//if($this->user_id == parent::MARIAN)
        	//error_log($query);
        $sql = $this->db->QFetchRowArray($query);
        if (!empty($sql)) {
            foreach ($sql as $key => $row) {
            	$row['opt'] = '';
            	if(!empty($row['liv_samb'])) $row['opt'] = 'LS';
				$responce->rows[$key]['id'] = $row['expeditie'];
                $responce->rows[$key]['cell'] = array($row['expeditie'], strtoupper($row['expeditor']),strtoupper($row['expeditor_centru']),strtoupper($row['destinatar']),strtoupper($row['destinatar_centru']),$row['opt']);
            }
        }
		$responce->page = $page;
        $responce->total = $total_pages;
        $responce->records = $count;
        return json_encode($responce);
    }

	function AdaugareExpeditie(){
		if(empty($_POST['data']) || !is_array($_POST['data'])) {
			return '0|||Error : Empty data!';
		}

		$post = [];
		foreach($_POST['data'] as $key=>$field){
            if(is_array($field) && isset($field['name']) && isset($field['value'])){
                $post[$field['name']] = $field['value'];
            }
        }
		$post = ExpeditieDto::uiExpToSql(array_merge($post, ['can_pret_impus' => $this->can_pret_impus]));

		//start validare
		if($post['expeditor_localitate_id'] == 0)
			return '0|||Selectati localitatea expeditorului din lista';
		if($post['expeditor_id'] == 0 || empty($post['expeditor_nume']))
			return '0|||Introduceti EXPEDITORUL!';
		if($post['destinatar_localitate_id'] == 0)
			return '0|||Selectati localitatea destinatarului din lista';
		if($post['destinatar_id'] == 0 || empty($post['destinatar_nume']))
			return '0|||Introduceri DESTINATARUL!';
		if($post['sms'] == -1 && !ExpeditieDto::isValidTelefonNumber($post['destinatar_telefon']))
			return "0|||Corectati telefon destinatar : 07XXXXXXXX";
		if($post['platitor_id'] == 0 || empty($post['platitor_nume']))
			return '0|||Introduceti PLATITORUL!';
		if($post['expeditie'] == 0)
			return '0|||Introduceti Numarul Expeditiei!';
		if($post['expeditie'] > 0 && ExpeditieDto::isOldSystemAwb($post['expeditie']))
			return '0|||Eroare: Nr. AWB invalid';
		if($post['expeditie'] > 0 && !ExpeditieDto::isAppAwb($post['expeditie']))
			return '0|||Eroare: Nr. AWB invalid';
		if($post['tip_exp'] == 0 && $post['valoare_asigurata'] > parent::MAX_ASIGURARE && $this->user_id != parent::MARIAN)
			return "0|||Asigurarea expeditiei nu poate depasi " . parent::MAX_ASIGURARE . " LEI! Va rugam corectati.";

		//test tip_obj <=> greutate
		if($post['plicuri'] == 0 && $post['colete'] == 0 && $post['paleti'] == 0)
			return "0|||Eroare adaugare expeditie : plic=colete=palet=0";
		if($post['colete'] == 0 && $post['paleti'] == 0 && $post['greutate'] >= parent::MIN_KG_COLET)
			return "0|||Eroare adaugare expeditie : plic cu greutate > 0.500";
		if($post['tip_exp'] == 0 && $post['tip_obj'] == 2 && $post['greutate'] < parent::MIN_KG_COLET)
			return "0|||Eroare adaugare expeditie tip colet : greutate minima : " . parent::MIN_KG_COLET . " kg";
		if($post['tip_exp'] == 0 && $post['tip_obj'] == 3 && $post['greutate'] < parent::MIN_KG_PALET)
			return "0|||Eroare adaugare expeditie tip palet : greutate minima : " . parent::MIN_KG_PALET . " kg";
		if($post['tip_exp'] > 0 && $post['referire'] == 0 || $post['tip_exp'] == 0 && $post['referire'] > 0)
			return "0|||Eroare adaugare expeditie : alege tip retur si introdu expeditia de referinta";
		if($post['tip_exp'] == 0 && $post['tip_obj'] == 2 && $post['colete'] > parent::MAX_COLETE)
			return '0|||Expeditia poate avea maxim '.parent::MAX_COLETE.' de colete! Va rugam corectati.';
		if($post['tip_exp'] == 0 && $post['tip_obj'] == 2 && $post['colete'] < parent::MIN_COLETE)
			return '0|||Expeditia poate avea minim '.parent::MIN_COLETE.' de colete! Va rugam corectati.';

		$initialaRow = [];
		if($post['tip_exp'] > 0){
			if(!in_array($post['tip_exp'], parent::ALLOWED_TIP_EXP))
				return "0|||Eroare: Tip expeditie necunoscut!";

	        $initialaRow = $this->GetValues($post['referire']);
	        if(false === $initialaRow)
				return '0|||Expeditia de referinta inexistenta!';
			if($post['tip_exp'] == 1){
				if(empty($initialaRow['ret_nt']) && empty($initialaRow['extrainfo']))
					return '0|||Expeditia de referinta nu are Retur NT!';

				$q = "SELECT expeditie from {$this->tables['exp_prelucrate']} WHERE referire = {$post['referire']} AND tip_exp = 1 and anulata = 0";
				$s = $this->db->QFetchArray($q);
				if(!empty($s))
					return '0|||Expeditia de referinta are deja Retur NT : ' . $s['expeditie']. ' !';

			}
			if($post['tip_exp'] == 2 ){
				if(empty($initialaRow['ret_doc']))
					return '0|||Expeditia de referinta nu are Retur Doc!';

				$q = "SELECT expeditie from {$this->tables['exp_prelucrate']} WHERE referire = {$post['referire']} AND tip_exp = 2 and anulata = 0";
				$s = $this->db->QFetchArray($q);
				if(!empty($s))
					return '0|||Expeditia de referinta are deja Retur Doc : ' . $s['expeditie']. ' !';

			}
			if($post['tip_exp'] == 3 ){
				if(empty($initialaRow['ramburs']))
					return '0|||Expeditia de referinta nu are Ramburs!';

				if(in_array($initialaRow['tip_plata'], [0, 3]))
					return "0|||Expeditia {$initialaRow['expeditie']} are ramburs CASH sau CONT !<br/>Adaugare blocata !";

				$q = "SELECT expeditie, tip_exp from {$this->tables['exp_prelucrate']} WHERE referire = {$post['referire']} AND tip_exp in (3,5) and anulata = 0";
				$s = $this->db->QFetchArray($q);
				if(!empty($s)){
					if($s['tip_exp'] == 3)
						return '0|||Expeditia de referinta are deja Ramburs : ' . $s['expeditie']. ' !';
					return '0|||Expeditia de referinta a fost Returnata : ' . $s['expeditie']. ' !';
				}
			}
			if($post['tip_exp'] == 5 && $this->user_id != parent::MARIAN){
				$q = "SELECT expeditie from {$this->tables['exp_prelucrate']} WHERE referire = {$post['referire']} AND tip_exp = 5 and anulata = 0";
				$s = $this->db->QFetchArray($q);
				if(!empty($s))
					return '0|||Expeditia de referinta are deja Returnare : ' . $s['expeditie']. ' !';

				//nu se poate returna o expeditie care are retur but not retur colet
				$q = "SELECT expeditie, tip_exp from {$this->tables['exp_prelucrate']} WHERE referire = {$post['referire']} AND tip_exp != 7 and anulata = 0";
				$s = $this->db->QFetchArray($q);
				if(!empty($s)){
					return '0|||Expeditia de referinta are cel putin un retur creat : ' . $s['expeditie']. ' !';
				}

			}
			if($post['tip_exp'] == 6 ){
				if(empty($initialaRow['ret_amb']))
					return '0|||Expeditia de referinta nu are Retur ambalaj!';

				$q = "SELECT expeditie from {$this->tables['exp_prelucrate']} WHERE referire = {$post['referire']} AND tip_exp = 6 and anulata = 0";
				$s = $this->db->QFetchArray($q);
				if(!empty($s))
					return '0|||Expeditia de referinta are deja Retur ambalaj : ' . $s['expeditie']. ' !';
			}
			if($post['tip_exp'] == 7 ){
				if(empty($initialaRow['ret_colet']))
					return '0|||Expeditia de referinta nu are Retur colet!';
				if(empty($post['colete']))
					return '0|||Introduceti nr. colete!';
				if(empty($post['greutate']))
					return '0|||Introduceti greutatea!';

				$q = "SELECT expeditie from {$this->tables['exp_prelucrate']} WHERE referire = {$post['referire']} AND tip_exp = 7 and anulata = 0";
				$s = $this->db->QFetchArray($q);
				if(!empty($s))
					return '0|||Expeditia de referinta are deja Retur colet : ' . $s['expeditie']. ' !';
			}
		}

		//if platitor tertz => obligatoriu contract si plata periodica
		if($post['destinatar_id'] != $post['platitor_id'] && $post['expeditor_id'] != $post['platitor_id']){
			$platitor_infos = $this->ClientInfos($post['platitor_id']);
	        if(!($platitor_infos['contract'] == 1 && $platitor_infos['mod_plata'] == 1)) {
				return '0|||Platitorul Altul trebuie sa aiba contract si factura periodica!';
			}
		}
		//end validare

		if($post['expeditie'] == -1)
			$post['expeditie'] = $this->GenerareNrExpeditie();

		$post['expeditor_localitate_km'] = $post['km_preluare'] = $this->getKmLocalitate($post['expeditor_localitate_id']);
		$post['destinatar_localitate_km'] = $post['km_livrare'] = $this->getKmLocalitate($post['destinatar_localitate_id']);

		$val = $this->Get_ValoareExpeditie($post, $initialaRow);

		$id_ist = $this->insertIstExp(null, 1);

		$vi = [];
		if($post['tip_exp'] == 0){
			//expeditie initiala
			$vi['expeditie'] = $post['expeditie'];
			$vi['referire'] = 0;
			$vi['data_expeditie'] = $vi['data_operatie'] = date("Y-m-d");
			$vi['operatiune'] = "Colectata";
			$vi['data_op'] = $vi['data'] = date('Y-m-d H:i:s');
			$vi['plicuri'] = $post['plicuri'];
			$vi['colete'] = $post['colete'];
			$vi['paleti'] = $post['paleti'];
			$vi['piese'] = $post['piese'];
			$vi['tip_obj'] = $post['tip_obj'];
			$vi['greutate'] = $post['greutate'];
			$vi['greutate_vol'] = $post['greutate_vol'];
			$vi['volum'] = $post['volum'];
			$vi['val_greutate'] = $post['pret_impus'] > 0 ? 0 : $val['tGreutate'];
			$vi['km_preluare'] = $post['km_preluare'];
			$vi['km_livrare'] = $post['km_livrare'];
			$vi['val_km'] = $post['pret_impus'] > 0 ? 0 : $val['tKm'];
			$vi['valoare_asigurata'] = $post['valoare_asigurata'];
			$vi['procent_asigurare'] = $val['procAsigurare'];
			$vi['val_asig'] = $post['pret_impus'] > 0 ? 0 : $val['tAsigurare'] + $val['tRamburs'];
			$vi['tip_exp'] = 0;
			$vi['ramburs'] = $post['ramburs'];
			$vi['tip_plata'] = $post['tip_plata'];
			$vi['ramburs_procent'] = $val['procRamburs'];
			$vi['mod_plata'] = $val['mod_plata'];
			$vi['ret_nt'] = $post['ret_nt'];
			$vi['ret_doc'] = $post['ret_doc'];
			$vi['liv_samb'] = $post['liv_samb'];
			$vi['liv_sed'] = $post['liv_sed'];
			$vi['ret_amb'] = $post['ret_amb'];
			$vi['ret_colet'] = $post['ret_colet'];
			$vi['sms'] = $post['sms'];
			$vi['copen'] = $post['copen'];
			$vi['moneda'] = $val['moneda'] ?? "LEI";
			$vi['observatii'] = $post['observatii'];
			$vi['detalii_doc'] = $post['detalii_doc'];

			$vi['valoare_expeditie'] = $post['pret_impus'] > 0 ? round($post['pret_impus'], 2) : $val['tExpeditie'];
			$vi['valoare_totala_expeditie'] = $post['pret_impus'] > 0 ? round($post['pret_impus'], 2) : round($val['tExpeditie'] + $val['tGreutate'] + $val['tKm'] + $val['tAsigurare'] + $val['tRamburs'], 2);
			$vi['tva'] = round($vi['valoare_totala_expeditie'] * $this->procTva / 100, 2);
			$vi['procTva'] = $this->procTva;
			$vi['pret_impus'] = $this->can_pret_impus && $post['pret_impus'] > 0 ? 1 : 0;

			$vi['expeditor_id'] = $post['expeditor_id'];
			$vi['expeditor_contact'] = $post['expeditor_contact'];
			$vi['expeditor_telefon'] = $post['expeditor_telefon'];
			$vi['destinatar_id'] = $post['destinatar_id'];
			$vi['destinatar_contact'] = $post['destinatar_contact'];
			$vi['destinatar_telefon'] = $post['destinatar_telefon'];
			$vi['platitor_id'] = $post['platitor_id'];
			$vi['operator_id'] = $this->user_id;
		}
		else {
			$initialaRow['procTva'] = $this->procTva;

			//la creare expeditie de retur, daca status_ramburs = 10 ("Nepreluat") modific status_ramburs = 0 ("in derulare") la expeditia initiala
			if($initialaRow['status_ramburs'] == 10) {
				$this->setStatusRamburs($initialaRow['cod_expeditie'], $initialaRow['status_ramburs'], 0, $initialaRow['tip_plata']);
			}

			// returnare cu platitor = destinatar, mut plata la expeditor
			if($post['tip_exp'] == 5) {
				$updateInitiala = [];
				// returnare la initiala cu ramburs -> returnat : 31
				if($initialaRow['ramburs'] > 0)
					$this->setStatusRamburs($initialaRow['cod_expeditie'], $initialaRow['status_ramburs'], 4, $initialaRow['tip_plata']);

				// returnare cu platitor = destinatar, mut plata la expeditor
				if($initialaRow['destinatar_id'] == $initialaRow['platitor_id']){
					$updateInitiala['platitor_id'] = $initialaRow['platitor_id'] = $initialaRow['expeditor_id'];
					$updateInitiala['restanta'] = 1;
					$updateInitiala['restanta_data'] = date('Y-m-d');
					$valInitiala = $this->Get_ValoareExpeditie($initialaRow);
					$updateInitiala['mod_plata'] = $initialaRow['mod_plata'] = $valInitiala['mod_plata'];
					$updateInitiala['valoare_expeditie'] = $initialaRow['pret_impus'] > 0 ? round($initialaRow['valoare_totala_expeditie'], 2) : round($valInitiala['tExpeditie'], 2);
					$updateInitiala['val_greutate'] = $initialaRow['pret_impus'] > 0 ? 0 : round($valInitiala['tGreutate'], 2);
					$updateInitiala['val_km'] = $initialaRow['pret_impus'] > 0 ? 0 : round($valInitiala['tKm'], 2);
					$updateInitiala['val_asig'] = $initialaRow['pret_impus'] > 0 ? 0 : round($valInitiala['tAsigurare'] + $valInitiala['tRamburs'], 2);
					$updateInitiala['valoare_totala_expeditie'] = $initialaRow['pret_impus'] > 0 ? round($initialaRow['valoare_totala_expeditie'], 2) : round($valInitiala['tExpeditie'] + $valInitiala['tGreutate'] + $valInitiala['tKm'] + $valInitiala['tAsigurare'] + $valInitiala['tRamburs'], 2);
					$updateInitiala['procTva'] = $this->procTva;
					$updateInitiala['tva'] = round($updateInitiala['valoare_totala_expeditie'] * $this->procTva / 100, 2);
					
					$val = $this->Get_ValoareExpeditie($post, $initialaRow);
				}
				if(count($updateInitiala) > 0){
					$updateInitiala['updated_at'] = date('Y-m-d H:i:s');
					$updateInitiala['updated_by'] = $this->user_id;
					$this->db->QueryUpdate($this->tables['exp_prelucrate'], $updateInitiala, "cod_expeditie=".$initialaRow['cod_expeditie']);
					//istoric expeditie
					if(false !== ($id_ist = $this->insertIstExp($initialaRow['cod_expeditie'], 25)))
						$this->saveIstoricVals($id_ist, $initialaRow, $updateInitiala);
				}
			}

			$arrForVals = ['tip_exp' => $post['tip_exp']];
			//retur colet
			if($post['tip_exp'] == 7) {
				$arrForVals['greutate'] = $post['greutate'];
				$arrForVals['greutate_vol'] = $post['greutate_vol'];
				$arrForVals['colete'] = $arrForVals['piese'] = $post['colete'];
				$arrForVals['volum'] = $post['volum'];
				$val = $this->Get_ValoareExpeditie($arrForVals, $initialaRow);
			}

			$vi = ExpeditieDto::sqlInitialaToRetur($initialaRow, $arrForVals, $val);
			$vi['operator_id'] = $this->user_id;
			$vi['expeditie'] = $post['expeditie'];
			$vi['operatiune'] = "Colectata";
			$vi['observatii'] = $post['observatii'];
			$vi['detalii_doc'] = $post['detalii_doc'];
		}

		if(count($vi) > 0) {
			$vi['created_at'] = date('Y-m-d H:i:s');
			$vi['created_by'] = $this->user_id;
			$cod_expeditie = $this->db->QueryInsert($this->tables['exp_prelucrate'], $vi);
			//istoric expeditie
			if($id_ist !== false)
				$this->db->QueryUpdate($this->tables['ist_exp'], ['cod_exp' => $cod_expeditie], "cod_ist = ".$id_ist);
			//update contacte
			$this->db->QueryUpdate($this->tables['clienti'], ['contact' => $vi['expeditor_contact'], 'telefon' => $vi['expeditor_telefon'], 'updated_at' => date('Y-m-d H:i:s'), 'updated_by' => $this->user_id], 'cod_cl = '.$vi['expeditor_id']);
			$this->db->QueryUpdate($this->tables['clienti'], ['contact' => $vi['destinatar_contact'], 'telefon' => $vi['destinatar_telefon'], 'updated_at' => date('Y-m-d H:i:s'), 'updated_by' => $this->user_id], 'cod_cl = '.$vi['destinatar_id']);
			CdsGeocoder::geocode($this->db, $vi['expeditor_id']);
			CdsGeocoder::geocode($this->db, $vi['destinatar_id']);
		}
		else {
			error_log("error adaugare expeditie count(vi) : ".print_r($vi, true));
			return '0|||EROARE GENERALA!';
		}

		return '1|||'.$vi['expeditie'];

	}

	function EditareExpeditieInitiala(){
		if(!($this->user_profile == 10 || is_array($this->user_rights) && in_array('editare', $this->user_rights)))
			return '0|||Nu ai dreptul de modificare';

		if(empty($_POST['data']) || !is_array($_POST['data'])) {
			return '0|||Error : Empty data!';
		}

		$post = [];

		foreach($_POST['data'] as $key=>$field){
            if(is_array($field) && isset($field['name']) && isset($field['value'])){
                $post[$field['name']] = $field['value'];
            }
        }

		$post = ExpeditieDto::uiExpToSql(array_merge($post, ['can_pret_impus' => $this->can_pret_impus]));

		$decontata = false;
		$facturata = false;

		//start validare
		if($post['expeditor_localitate_id'] == 0)
			return '0|||Selectati localitatea expeditorului din lista';
		if($post['expeditor_id'] == 0 || empty($post['expeditor_nume']))
			return '0|||Introduceti EXPEDITORUL!';
		if($post['destinatar_localitate_id'] == 0)
			return '0|||Selectati localitatea destinatarului din lista';
		if($post['destinatar_id'] == 0 || empty($post['destinatar_nume']))
			return '0|||Introduceri DESTINATARUL!';
		if($post['sms'] == -1 && !ExpeditieDto::isValidTelefonNumber($post['destinatar_telefon']))
			return "0|||Corectati telefon destinatar : 07XXXXXXXX";
		if($post['platitor_id'] == 0 || empty($post['platitor_nume']))
			return '0|||Introduceti PLATITORUL!';
		if($post['expeditie'] == 0)
			return '0|||Introduceti Numarul Expeditiei!';
		if($post['expeditie'] > 0 && ExpeditieDto::isOldSystemAwb($post['expeditie']))
			return '0|||Eroare: Nr. AWB invalid';
		if($post['expeditie'] > 0 && !ExpeditieDto::isAwb($post['expeditie']) && !ExpeditieDto::isCmnAwb($post['expeditie']))
			return '0|||Eroare: Nr. AWB invalid';
		if($post['tip_exp'] > 0 && $post['referire'] == 0 || $post['tip_exp'] == 0 && $post['referire'] > 0)
			return "0|||Eroare: alege tip retur si introdu expeditia de referinta";

		//test tip_obj <=> greutate
		if($post['plicuri'] == 0 && $post['colete'] == 0 && $post['paleti'] == 0 && in_array($post['tip_exp'], [0,7]))
			return "0|||Eroare: plic=colete=palet=0";
		if($post['colete'] == 0 && $post['paleti'] == 0 && $post['greutate'] >= 1 && in_array($post['tip_exp'], [0,7]))
			return "0|||Eroare: plic cu greutate > 0.500";
		if(in_array($post['tip_exp'], [0,7]) && $post['tip_obj'] == 2 && $post['greutate'] < parent::MIN_KG_COLET)
			return "0|||Eroare colet : greutate minima : " . parent::MIN_KG_COLET . " kg";
		if($post['tip_exp'] == 0 && $post['tip_obj'] == 3 && $post['greutate'] < parent::MIN_KG_PALET)
			return "0|||Eroare palet : greutate minima : " . parent::MIN_KG_PALET . " kg";
		if($post['tip_exp'] == 0 && $post['valoare_asigurata'] > parent::MAX_ASIGURARE && $this->user_id != parent::MARIAN)
			return "0|||Asigurarea expeditiei nu poate depasi " . parent::MAX_ASIGURARE . " LEI! Va rugam corectati.";
		if(in_array($post['tip_exp'], [0,7]) && $post['tip_obj'] == 2 && $post['colete'] > parent::MAX_COLETE)
			return '0|||Expeditia poate avea maxim '.parent::MAX_COLETE.' de colete! Va rugam corectati.';
		if(in_array($post['tip_exp'], [0,7]) && $post['tip_obj'] == 2 && $post['colete'] < parent::MIN_COLETE)
			return '0|||Expeditia poate avea minim '.parent::MIN_COLETE.' de colete! Va rugam corectati.';

		//if platitor tertz => obligatoriu contract si plata periodica
		if($post['destinatar_id'] != $post['platitor_id'] && $post['expeditor_id'] != $post['platitor_id']){
			$platitor_infos = $this->ClientInfos($post['platitor_id']);
	        if(!($platitor_infos['contract'] == 1 && $platitor_infos['mod_plata'] == 1)) {
				return '0|||Platitorul Altul trebuie sa aiba contract si factura periodica!';
			}
		}
		//end validare

		$initialaRow = $this->GetValues($post['expeditie']);
		if(false === $initialaRow)
			return "0|||Eroare: Expeditia Inexistenta!";

		if($initialaRow['tip_exp'] == parent::TIP_EXP_BO_RBS_CASH)
			return "0|||Eroare: Expeditia Borderou RBS cash nu poate fi modificata!";

		//error_log('d'.intval($this->can_pret_impus).' p'.intval($initialaRow['pret_impus']));
		if(!$this->can_pret_impus && $initialaRow['pret_impus'] == 1)
			return '0|||Expeditie cu pret impus. Nu ai dreptul de modificare';

		if(!in_array($initialaRow['tip_exp'], parent::ALLOWED_TIP_EXP))
			return "0|||Eroare: Tip expeditie necunoscut!";

		if(!empty($initialaRow['factura_id'])){
			$decontata = true;
			if($this->user_id == parent::MARIAN) $decontata = false;
		}
		if(!empty($initialaRow['idfact'])) {
			$facturata = true;
			if($this->user_id == parent::MARIAN) $facturata = false;
		}

		if($initialaRow['sms'] > 0) $post['sms'] = $initialaRow['sms'];

		//editare retur or initiala to retur
		if($initialaRow['tip_exp'] > 0 || ($initialaRow['tip_exp'] == 0 && $post['tip_exp'] > 0 && $post['referire'] > 0)){
			$initialaRow['km_preluare'] = $initialaRow['expeditor_localitate_km'];
		    $initialaRow['km_livrare'] = $initialaRow['destinatar_localitate_km'];
			$initialaRow['procTva'] = $this->procTva;
			//update observatii and detalii doc
			$this->db->QueryUpdate($this->tables['exp_prelucrate'], ['observatii' => $post['observatii'], 'detalii_doc' => $post['detalii_doc']], "cod_expeditie=".$initialaRow['cod_expeditie']);
			if($initialaRow['tip_exp'] == 7 && $post['tip_exp'] == $initialaRow['tip_exp'] && $post['referire'] == $initialaRow['referire']) {//retur colet : doar editare colete si greutate
				return $this->EditareExpeditieReturColet($initialaRow, $post);
			}
			if($this->user_profile == 10)
				return $this->EditareExpeditieRetur($initialaRow, $post);
			return "0|||Eroare: Modifica expeditia initiala : {$initialaRow['referire']}!";
		}

		//editare initiala
		$post['km_preluare'] = $this->getKmLocalitate($post['expeditor_localitate_id']);
		$post['km_livrare'] = $this->getKmLocalitate($post['destinatar_localitate_id']);

		$val = $this->Get_ValoareExpeditie($post);
		if($val == false) {
			return '0|||Eroare: modificare : calcul valoare expeditie';
		}

		//editare ramburs
		if(!in_array($this->user_id, parent::CAN_MODIFY_RBS)
			&&
			(
				round($initialaRow['ramburs'], 2) != round($post['ramburs'], 2)
				||
				((round($initialaRow['ramburs'], 2) > 0 || round($post['ramburs'], 2) > 0) && $post['tip_plata'] != $initialaRow['tip_plata'])
			)
		) {
			return "0|||NU ai drept de modificare RBS";
		}

		//posibilitate de modificare observatii chiar daca expeditia este facturata sau decontata
		if($facturata || $decontata){
			$this->db->QueryUpdate($this->tables['exp_prelucrate'], ['observatii' => $post['observatii'], 'detalii_doc' => $post['detalii_doc']]," expeditie=".$initialaRow['expeditie']);
			$valoare_totala_expeditie_noua = $post['pret_impus'] > 0 ? round($post['pret_impus'], 2) : round($val['tExpeditie'] + $val['tGreutate'] + $val['tKm'] + $val['tAsigurare'] + $val['tRamburs'], 2);
			//error_log($initialaRow['valoare_totala_expeditie'] . ":" . $valoare_totala_expeditie_noua);
				//change RBS tip plata : cash <-> bo, cash <-> cec
			if((round($initialaRow['ramburs'], 2) > 0 || round($post['ramburs'], 2) > 0) && $post['tip_plata'] != $initialaRow['tip_plata']){
				$this->db->QueryUpdate($this->tables['exp_prelucrate'], array('tip_plata' => $post['tip_plata'])," expeditie=".$initialaRow['expeditie']);
				$this->db->QueryUpdate($this->tables['exp_prelucrate'], array('tip_plata' => $post['tip_plata'])," tip_exp = 3 and referire=".$initialaRow['expeditie']);
				//istoric expeditie
				if(false !== ($id_ist = $this->insertIstExp($initialaRow['cod_expeditie'], 25)))
				$this->saveIstoricVals($id_ist, $initialaRow, ['tip_plata' => $post['tip_plata']]);
				return '0||| Tip plata RBS modificat';
			}
			if(abs($initialaRow['valoare_totala_expeditie'] - $valoare_totala_expeditie_noua) > 0.5 || $post['platitor_id'] != $initialaRow['platitor_id']){
				$mesaj = "";
				if($decontata)
					$mesaj = "Eroare: Expeditie decontata <br>factura serie :" . $initialaRow['serie']."<br>";
				if($facturata)
					$mesaj .= "Eroare: Expeditie facturata <br>factura serie :" . $initialaRow['invoice']."<br>";

                $mesaj .= $initialaRow['valoare_totala_expeditie']." != $valoare_totala_expeditie_noua || ".$post['platitor_id']." != ".$initialaRow['platitor_id'];
                return '0|||'.$mesaj;
			}
		}
		//TODO : nu pot modifica platitor_id = expeditor_id si mod_plata = 0 daca initiala are deja returnarea facuta

		$vi = [];
		$vi['referire'] = 0;
		$vi['data_operatie'] = date("Y-m-d");
		$vi['data_op'] = date('Y-m-d H:i:s');
		$vi['plicuri'] = $post['plicuri'];
		$vi['colete'] = $post['colete'];
		$vi['paleti'] = $post['paleti'];
		$vi['piese'] = $post['piese'];
		$vi['tip_obj'] = $post['tip_obj'];
		$vi['greutate'] = $post['greutate'];
		$vi['greutate_vol'] = $post['greutate_vol'];
		$vi['volum'] = $post['volum'];
		$vi['val_greutate'] = $post['pret_impus'] > 0 ? 0 : $val['tGreutate'];
		$vi['km_preluare'] = $post['km_preluare'];
		$vi['km_livrare'] = $post['km_livrare'];
		$vi['val_km'] = $post['pret_impus'] > 0 ? 0 : $val['tKm'];
		$vi['valoare_asigurata'] = $post['valoare_asigurata'];
		$vi['procent_asigurare'] = $val['procAsigurare'];
		$vi['val_asig'] = $post['pret_impus'] > 0 ? 0 : $val['tAsigurare'] + $val['tRamburs'];
		$vi['ramburs'] = $post['ramburs'];
		$vi['tip_plata'] = $post['tip_plata'];
		$vi['ramburs_procent'] = $val['procRamburs'];
		$vi['mod_plata'] = $val['mod_plata'];
		$vi['ret_nt'] = $post['ret_nt'];
		$vi['ret_doc'] = $post['ret_doc'];
		$vi['liv_samb'] = $post['liv_samb'];
		$vi['liv_sed'] = $post['liv_sed'];
		$vi['ret_amb'] = $post['ret_amb'];
		$vi['ret_colet'] = $post['ret_colet'];
		$vi['sms'] = $post['sms'];
		$vi['copen'] = $post['copen'];
		$vi['moneda'] = $val['moneda'] ?? "LEI";
		$vi['observatii'] = $post['observatii'];
		$vi['detalii_doc'] = $post['detalii_doc'];

		$vi['expeditor_id'] = $post['expeditor_id'];
		$vi['expeditor_contact'] = $post['expeditor_contact'];
		$vi['expeditor_telefon'] = $post['expeditor_telefon'];
		$vi['destinatar_id'] = $post['destinatar_id'];
		$vi['destinatar_contact'] = $post['destinatar_contact'];
		$vi['destinatar_telefon'] = $post['destinatar_telefon'];

		$vi['platitor_id'] = $post['platitor_id'];
		$vi['operator_id'] = $this->user_id;

		$vi['valoare_expeditie'] = $post['pret_impus'] > 0 ? round($post['pret_impus'], 2) : $val['tExpeditie'];
		$vi['valoare_totala_expeditie'] = $post['pret_impus'] > 0 ? round($post['pret_impus'], 2) : round($val['tExpeditie'] + $val['tGreutate'] + $val['tKm'] + $val['tAsigurare'] + $val['tRamburs'], 2);
		$vi['tva'] = round($vi['valoare_totala_expeditie'] * $this->procTva / 100, 2);
		$vi['procTva'] = $this->procTva;
		$vi['pret_impus'] = $this->can_pret_impus && $post['pret_impus'] > 0 ? 1 : 0;

		//update contacte
		$this->db->QueryUpdate($this->tables['clienti'], ['contact' => $post['expeditor_contact'], 'telefon' => $post['expeditor_telefon'], 'updated_at' => date('Y-m-d H:i:s'), 'updated_by' => $this->user_id], 'cod_cl='.$post['expeditor_id']);
		$this->db->QueryUpdate($this->tables['clienti'], ['contact' => $post['destinatar_contact'], 'telefon' => $post['destinatar_telefon'], 'updated_at' => date('Y-m-d H:i:s'), 'updated_by' => $this->user_id], 'cod_cl='.$post['destinatar_id']);

		//update expeditie
		$vi['updated_at'] = date('Y-m-d H:i:s');
		$vi['updated_by'] = $this->user_id;
		$this->db->QueryUpdate($this->tables['exp_prelucrate'], $vi, "cod_expeditie = {$initialaRow['cod_expeditie']}");

		//geocode
		CdsGeocoder::geocode($this->db, $vi['expeditor_id'], true);
		CdsGeocoder::geocode($this->db, $vi['destinatar_id'], true);

		//istoric expeditie
		if(false !== ($id_ist = $this->insertIstExp($initialaRow['cod_expeditie'], 25)))
			$this->saveIstoricVals($id_ist, $initialaRow, $vi);

		//insert exp_recantarite
		$kgNew = max(ceil($post['greutate']), ceil($post['greutate_vol']));
		if(!empty($initialaRow['greutate']) && ceil($initialaRow['greutate']) < $kgNew){
			$user_centru_id = $this->user_centru_id;
			$insertedId = $this->insertRecantarite($user_centru_id, $initialaRow['expeditie'], $kgNew, $post['volum1'], $post['volum2'], $post['volum3'], 1, 0, 2);
			if($insertedId > 0)
				$this->db->QueryUpdate('exp_recantarite', ['oldKg' => $initialaRow['greutate']], "id = " . $insertedId);
		}

		//actualizare retururi
		$queryRetururi = "SELECT count(cod_expeditie) as nr_retururi from {$this->tables['exp_prelucrate']} where referire = {$initialaRow['expeditie']} and anulata = 0 and idfact = 0";
		$sqlRetururi = $this->db->QFetchArray($queryRetururi);
		$count = !empty($sqlRetururi['nr_retururi']) ? $sqlRetururi['nr_retururi'] : 0;
		//error_log($count);
		if($count > 0){
			$this->ActualizareRetururi($initialaRow['expeditie']);
		}

		//send FCM la initiala
		$this->sendUpdateToAndroid($initialaRow['expeditie']);

		return '1|||'.$initialaRow['expeditie'];
	}

	//$post['tip_exp'], $post['referire'], $post['observatii']
	function EditareExpeditieRetur($returRow = [], $post = []) {
		$oldTipExp = $returRow['tip_exp'];
		$newTipExp = $post['tip_exp'] ?? 0;
		$oldReferire = $returRow['referire'];
		$newReferire = $post['referire'] ?? 0;
		$oldPlatitor = $returRow['platitor_id'];
		$newPlatitor = $post['platitor_id'] ?? 0;

		//error_log("debug : " . $newTipExp . ' : ' .$newReferire);

		if($oldReferire == 0 && $newReferire == 0)
			return "0|||Eroare: Expeditia initiala inexistenta !";
		if(empty($returRow) || !is_array($returRow))
			return "0|||Eroare: Expeditia de retur inexistenta !";
		if($returRow['idfact'] > 0)
			return "0|||Eroare : Expeditie facturata : {$returRow['expeditie']}";
		if(!empty($returRow['factura_id']))
			return "0|||Eroare : Expeditie decontata : {$returRow['expeditie']}";

		//returnare : permite schimbarea platitorului pentru returnare only
		//noul platitor este tertz (vezi restanta) : contract, factura periodica si tertz
		if($oldTipExp == $newTipExp && $newTipExp == 5 && $newPlatitor > 0 && $oldPlatitor != $newPlatitor
			 && $newReferire > 0 && $oldReferire == $newReferire) {

			$initialaRow = $this->GetValues($returRow['referire']);
			if(false === $initialaRow)
				return "0|||Eroare: Expeditia initiala Inexistenta!";
			
			$platitorIsTertz = intval($post['destinatar_id'] != $post['platitor_id'] && $post['expeditor_id'] != $post['platitor_id']);
			if($newPlatitor == $initialaRow['platitor_id']){
				//back to platitor la initiala : ok
				$platitorIsTertz = -1;
			}
			if($initialaRow['restanta'] == 1){
				return '0|||Eroare modificare platitor : initiala are restanta';
			}
			if($platitorIsTertz == 0){
				return '0|||Eroare modificare platitor : platitorul trebuie sa fie tertz sau destinatarul';
			}
			$initialaRow['platitor_id'] = $newPlatitor;

			$val = $this->Get_ValoareExpeditie($returRow, $initialaRow);

			if($val == false) {
				return '0|||Eroare modificare platitor : valoare expeditie';
			}
			if($platitorIsTertz == 1 && $val['mod_plata'] == 0) {
				return '0|||Eroare modificare platitor : tertzul trebuie sa aiba contract si factura periodica';
			}

			$vi = [];
			$vi['platitor_id'] = $newPlatitor;
			$vi['mod_plata'] = $val['mod_plata'];
			$vi['val_greutate'] = $initialaRow['pret_impus'] > 0 ? 0 : $val['tGreutate'];
			$vi['val_km'] = $initialaRow['pret_impus'] > 0 ? 0 : $val['tKm'];
			$vi['procent_asigurare'] = $val['procAsigurare'];
			$vi['val_asig'] = $initialaRow['pret_impus'] > 0 ? 0 : $val['tAsigurare'] + $val['tRamburs'];
			$vi['ramburs_procent'] = $val['procRamburs'];
			
			$vi['valoare_expeditie'] = $initialaRow['pret_impus'] > 0 ? round($initialaRow['valoare_totala_expeditie'], 2) : $val['tExpeditie'];
			$vi['valoare_totala_expeditie'] = $initialaRow['pret_impus'] > 0 ? round($initialaRow['valoare_totala_expeditie'], 2) : round($val['tExpeditie'] + $val['tGreutate'] + $val['tKm'] + $val['tAsigurare'] + $val['tRamburs'], 2);
			$vi['tva'] = round($vi['valoare_totala_expeditie'] * $returRow['procTva'] / 100, 2);
			$vi['procTva'] = round($returRow['procTva'], 2);

			$vi['updated_at'] = date('Y-m-d H:i:s');
			$vi['updated_by'] = $this->user_id;
			$this->db->QueryUpdate($this->tables['exp_prelucrate'], $vi, "cod_expeditie = {$returRow['cod_expeditie']}");

			//istoric expeditie
			if(false !== ($id_ist = $this->insertIstExp($returRow['cod_expeditie'], 25))){
				$this->saveIstoricVals($id_ist, $returRow, $vi);
			}
			//send FCM la retururi
        	$this->sendUpdateToAndroid($returRow['expeditie']);
			return "1|||".$returRow['expeditie'];
		}

		//retur -> new retur : change tip_exp keep referire
		if($returRow['tip_exp'] > 0 && $returRow['referire'] > 0 && //old retur check
			$post['tip_exp'] > 0 && $post['referire'] > 0 && //new retur check
			$post['tip_exp'] != $returRow['tip_exp'] && //check tip expeditie change
			$post['referire'] == $returRow['referire']) { //check referire keep
			return $this->EditareExpeditieReturRetur($returRow, $post);
		}

		//initiala -> retur
		if($returRow['tip_exp'] == 0 && $returRow['referire'] == 0 && //old initiala check
			$post['tip_exp'] > 0 && $post['referire'] > 0) { //new retur check	
			return $this->EditareExpeditieInitialaRetur($returRow, $post);
		}

		//retur -> initiala
		if($returRow['tip_exp'] > 0 && $returRow['referire'] > 0 && $post['tip_exp'] == 0 && $post['referire'] == 0) {
			return $this->EditareExpeditieReturInitiala($returRow, $post);
		}
		
		return "0|||Nicio modificare!";
	}

	public function EditareExpeditieReturRetur($returRow = [], $post = []) 
	{
		// retur => retur : if $returRow['tip_exp'] > 0 and $returRow['referire'] > 0 and $post['tip_exp'] > 0 and $post['referire'] > 0

		if(false === ($initialaRow = $this->GetValues($returRow['referire']))){
			error_log("debug EditareExpeditieReturRetur: la retur " . $returRow['expeditie']) . " : initiala {$returRow['referire']} not found";
			return "0|||Eroare: Expeditia initiala inexistenta : {$returRow['referire']}";
		}

		if($post['tip_exp'] == 1) {
			if(empty($initialaRow['ret_nt']) && empty($initialaRow['extrainfo']))
				return '0|||Expeditia de referinta nu are Retur NT!';
			$q = "SELECT expeditie from {$this->tables['exp_prelucrate']} WHERE referire = {$initialaRow['expeditie']} AND tip_exp = 1 and anulata = 0";
			$s = $this->db->QFetchArray($q);
			if(!empty($s))
				return '0|||Expeditia de referinta are deja Retur NT : ' . $s['expeditie']. ' !';

		}
		else if($post['tip_exp'] == 2 ){
			if(empty($initialaRow['ret_doc']))
				return '0|||Expeditia de referinta nu are Retur Doc!';

			$q = "SELECT expeditie from {$this->tables['exp_prelucrate']} WHERE referire = {$initialaRow['expeditie']} AND tip_exp = 2 and anulata = 0";
			$s = $this->db->QFetchArray($q);
			if(!empty($s))
				return '0|||Expeditia de referinta are deja Retur Doc : ' . $s['expeditie']. ' !';

		}
		else if($post['tip_exp'] == 3 ){
			if(empty($initialaRow['ramburs']))
				return '0|||Expeditia de referinta nu are Ramburs!';

			$q = "SELECT expeditie, tip_exp from {$this->tables['exp_prelucrate']} WHERE referire = {$initialaRow['expeditie']} AND tip_exp in (3,5) and anulata = 0";
			$s = $this->db->QFetchArray($q);
			if(!empty($s)){
				if($s['tip_exp'] == 3)
					return '0|||Expeditia de referinta are deja Ramburs : ' . $s['expeditie']. ' !';
				return '0|||Expeditia de referinta a fost Returnata : ' . $s['expeditie']. ' !';
			}
		}
		else if($post['tip_exp'] == 5){
			$q = "SELECT expeditie from {$this->tables['exp_prelucrate']} WHERE referire = {$initialaRow['expeditie']} AND tip_exp = 5 and anulata = 0";
			//error_log($q);
			$s = $this->db->QFetchArray($q);
			if(!empty($s))
				return '0|||Expeditia de referinta are deja Returnare : ' . $s['expeditie']. ' !';

		}
		else if($post['tip_exp'] == 6 ){
			if(empty($initialaRow['ret_amb']))
				return '0|||Expeditia de referinta nu are Retur ambalaj!';

			$q = "SELECT expeditie from {$this->tables['exp_prelucrate']} WHERE referire = {$initialaRow['expeditie']} AND tip_exp = 6 and anulata = 0";
			$s = $this->db->QFetchArray($q);
			if(!empty($s))
				return '0|||Expeditia de referinta are deja Retur ambalaj : ' . $s['expeditie']. ' !';
		}
		else if($post['tip_exp'] == 7) {
			if(empty($initialaRow['ret_colet']))
				return '0|||Expeditia de referinta nu are Retur colet!';
			$q = "SELECT expeditie from {$this->tables['exp_prelucrate']} WHERE referire = {$initialaRow['expeditie']} AND tip_exp = 7 and anulata = 0";
			$s = $this->db->QFetchArray($q);
			if(!empty($s))
				return '0|||Expeditia de referinta are deja Retur colet : ' . $s['expeditie']. ' !';
			$returRow['greutate'] = $post['greutate'];
			$returRow['colete'] = $post['colete'];
			$returRow['greutate_vol'] = $post['greutate_vol'];
			$returRow['volum'] = $post['volum'];
		}

		$returRow['tip_exp'] = $post['tip_exp'];
		$returRow['observatii'] = $post['observatii'];
		$this->ActualizareRetur($returRow, $initialaRow);
		return "1|||".$returRow['expeditie'];
	}

	public function EditareExpeditieInitialaRetur($returRow = [], $post = []) {
		// initiala => retur : if returRow['tip_exp] == 0 and returRow['referire'] = 0 and post['tip_exp'] > 0 and post['referire'] > 0

		if(false === ($initialaRow = $this->GetValues($post['referire']))){
			error_log("debug EditareExpeditieInitialaRetur : la retur " . $returRow['expeditie']) . " : initiala {$post['referire']} not found";
			return "0|||Eroare: Expeditia initiala inexistenta : {$post['referire']}";
		}

		if($post['tip_exp'] == 1) {
			if(empty($initialaRow['ret_nt']) && empty($initialaRow['extrainfo']))
				return '0|||Expeditia de referinta nu are Retur NT!';
			$q = "SELECT expeditie from {$this->tables['exp_prelucrate']} WHERE referire = {$initialaRow['expeditie']} AND tip_exp = 1 and anulata = 0";
			$s = $this->db->QFetchArray($q);
			if(!empty($s))
				return '0|||Expeditia de referinta are deja Retur NT : ' . $s['expeditie']. ' !';

		}
		else if($post['tip_exp'] == 2 ){
			if(empty($initialaRow['ret_doc']))
				return '0|||Expeditia de referinta nu are Retur Doc!';

			$q = "SELECT expeditie from {$this->tables['exp_prelucrate']} WHERE referire = {$initialaRow['expeditie']} AND tip_exp = 2 and anulata = 0";
			$s = $this->db->QFetchArray($q);
			if(!empty($s))
				return '0|||Expeditia de referinta are deja Retur Doc : ' . $s['expeditie']. ' !';

		}
		else if($post['tip_exp'] == 3 ){
			if(empty($initialaRow['ramburs']))
				return '0|||Expeditia de referinta nu are Ramburs!';

			$q = "SELECT expeditie, tip_exp from {$this->tables['exp_prelucrate']} WHERE referire = {$initialaRow['expeditie']} AND tip_exp in (3,5) and anulata = 0";
			$s = $this->db->QFetchArray($q);
			if(!empty($s)){
				if($s['tip_exp'] == 3)
					return '0|||Expeditia de referinta are deja Ramburs : ' . $s['expeditie']. ' !';
				return '0|||Expeditia de referinta a fost Returnata : ' . $s['expeditie']. ' !';
			}
		}
		else if($post['tip_exp'] == 5){
			$q = "SELECT expeditie from {$this->tables['exp_prelucrate']} WHERE referire = {$initialaRow['expeditie']} AND tip_exp = 5 and anulata = 0";
			//error_log($q);
			$s = $this->db->QFetchArray($q);
			if(!empty($s))
				return '0|||Expeditia de referinta are deja Returnare : ' . $s['expeditie']. ' !';

		}
		else if($post['tip_exp'] == 6 ){
			if(empty($initialaRow['ret_amb']))
				return '0|||Expeditia de referinta nu are Retur ambalaj!';

			$q = "SELECT expeditie from {$this->tables['exp_prelucrate']} WHERE referire = {$initialaRow['expeditie']} AND tip_exp = 6 and anulata = 0";
			$s = $this->db->QFetchArray($q);
			if(!empty($s))
				return '0|||Expeditia de referinta are deja Retur ambalaj : ' . $s['expeditie']. ' !';
		}
		else if($post['tip_exp'] == 7) {
			if(empty($initialaRow['ret_colet']))
				return '0|||Expeditia de referinta nu are Retur colet!';
			$q = "SELECT expeditie from {$this->tables['exp_prelucrate']} WHERE referire = {$initialaRow['expeditie']} AND tip_exp = 7 and anulata = 0";
			$s = $this->db->QFetchArray($q);
			if(!empty($s))
				return '0|||Expeditia de referinta are deja Retur colet : ' . $s['expeditie']. ' !';

		}

		$returRow['tip_exp'] = $post['tip_exp'];
		$returRow['referire'] = $post['referire'];
		$returRow['observatii'] = $post['observatii'] ?? $returRow['observatii'];
		$returRow['pret_impus'] = $initialaRow['pret_impus'];

		$this->ActualizareRetur($returRow, $initialaRow);
		return "1|||".$returRow['expeditie'];
	}

	public function EditareExpeditieReturInitiala($returRow = [], $post = []) {
		/// referire = 0
		$vi = [];
		$vi['referire'] = 0;
		$vi['tip_exp'] = $returRow['tip_exp'] = 0;
		$vi['pret_impus'] = 0;
		$val = $this->Get_ValoareExpeditie($returRow);
		if($val == false) {
			return '0|||Eroare: modificare : calcul valoare expeditie'.$returRow['expeditie'];
		}
		$vi['operator_id'] = $this->user_id;
		$vi['observatii'] = $post['observatii'] ?? $returRow['observatii'];

		$vi['val_greutate'] = $val['tGreutate'];
		$vi['val_km'] = $val['tKm'];
		$vi['procent_asigurare'] = $val['procAsigurare'];
		$vi['val_asig'] = $val['tAsigurare'] + $val['tRamburs'];
		$vi['ramburs_procent'] = $val['procRamburs'];
		
		$vi['valoare_expeditie'] = $val['tExpeditie'];
		$vi['valoare_totala_expeditie'] = round($val['tExpeditie'] + $val['tGreutate'] + $val['tKm'] + $val['tAsigurare'] + $val['tRamburs'], 2);
		$vi['tva'] = round($vi['valoare_totala_expeditie'] * $returRow['procTva'] / 100, 2);
		$vi['procTva'] = round($returRow['procTva'], 2);

		$vi['updated_at'] = date('Y-m-d H:i:s');
		$vi['updated_by'] = $this->user_id;
		$this->db->QueryUpdate($this->tables['exp_prelucrate'], $vi, "cod_expeditie=".$returRow['cod_expeditie']);

		//istoric expeditie
		if(false !== ($id_ist = $this->insertIstExp($returRow['cod_expeditie'], 25))){
			$this->saveIstoricVals($id_ist, $returRow, $vi);
		}
		//send FCM la retururi
		$this->sendUpdateToAndroid($returRow['expeditie']);
		return "1|||".$returRow['expeditie'];
	}

	public function EditareExpeditieReturColet($returRow = [], $post = []) {
		if($post['colete'] < 1 || $post['greutate'] < 1 || empty($returRow['referire']) || empty($returRow['tip_exp']) || $returRow['tip_exp'] != 7)
			return "0|||Eroare: Numarul de colete si greutatea trebuie sa fie mai mari decat 0 !";

		if($returRow['idfact'] > 0)
			return "0|||Eroare : Expeditie facturata : {$returRow['expeditie']}";
		if(!empty($returRow['factura_id']))
			return "0|||Eroare : Expeditie decontata : {$returRow['expeditie']}";

		$initialaRow = $this->GetValues($returRow['referire']);
		if(false === $initialaRow)
			return "0|||Eroare: Expeditia initiala inexistenta !";

		if($post['colete'] == $returRow['colete'] && $post['greutate'] == $returRow['greutate'] && $post['greutate_vol'] == $returRow['greutate_vol'])
			return "0|||Nicio modificare!";

		//retur colet : modificare greutate
		$arrForVals = [
			'tip_exp' => $returRow['tip_exp'],
			'greutate' => max($post['greutate'], $post['greutate_vol']),
			'greutate_vol' => $post['greutate_vol'],
			'colete' => $post['colete'],
			'piese' => $post['piese'] ?? 0,
			'volum' => $post['volum'],
		];
		$val = $this->Get_ValoareExpeditie($arrForVals, $initialaRow);
		if($val == false) {
			return '0|||Eroare: modificare : calcul valoare expeditie';
		}
		$vi = [];
		$vi['colete'] = $vi['piese'] = $post['colete'];
		$vi['greutate'] = $post['greutate'];
		
		$vi['greutate_vol'] = $post['greutate_vol'] ?? 0;
		$vi['volum'] = $post['volum'] ?? '';
		$vi['operator_id'] = $this->user_id;
		$vi['observatii'] = $post['observatii'] ?? '';

		$vi['val_greutate'] = $val['tGreutate'];
		$vi['valoare_expeditie'] = $val['tExpeditie'];
		$vi['valoare_totala_expeditie'] = round($val['tExpeditie'] + $val['tGreutate'] + $val['tKm'] + $val['tAsigurare'] + $val['tRamburs'], 2);
		$vi['tva'] = round($vi['valoare_totala_expeditie'] * $returRow['procTva'] / 100, 2);
		$vi['procTva'] = round($returRow['procTva'], 2);

		$vi['updated_at'] = date('Y-m-d H:i:s');
		$vi['updated_by'] = $this->user_id;
		$this->db->QueryUpdate($this->tables['exp_prelucrate'], $vi, "cod_expeditie=".$returRow['cod_expeditie']);

		//istoric expeditie
		if(false !== ($id_ist = $this->insertIstExp($returRow['cod_expeditie'], 25))){
			$this->saveIstoricVals($id_ist, $returRow, $vi);
		}
		//send FCM la retururi
		$this->sendUpdateToAndroid($returRow['expeditie']);
		return "1|||".$returRow['expeditie'];
	}

	function StergereExpeditie(){
		if(!($this->user_profile == 10 || is_array($this->user_rights) && in_array('stergere', $this->user_rights)))
			return '0|||Nu ai drept de stergere';

		if(empty($_POST['data']) || !is_array($_POST['data'])) {
			return '0|||Error : Empty data!';
		}

		$post = [];
		foreach($_POST['data'] as $key=>$field){
            if(is_array($field) && isset($field['name']) && isset($field['value'])){
                $post[$field['name']] = $field['value'];
            }
        }
		
		$post = ExpeditieDto::uiExpToSql(array_merge($post, ['can_pret_impus' => $this->can_pret_impus]));
		//error_log(print_r($post, true));
		if(empty($post['expeditie']))
			return '0|||Eroare Stergere:<br> Introduceti numarul expeditiei!';
		if(!ExpeditieDto::isAwb($post['expeditie']) && !ExpeditieDto::isCmnAwb($post['expeditie']))
			return "0|||Eroare Stergere:<br> Introduceti un numar de expeditie valid : {$post['expeditie']} !";

		$query = "SELECT ep.cod_expeditie, ep.tip_exp, ep.idfact, df.id as factura_decont_id,
			group_concat(epr.expeditie) as retururi, ep.tip_plata, ep.status_ramburs
			from exp_prelucrate ep
			left join exp_prelucrate epr on epr.referire = ep.expeditie
			left join decont_expeditii de on (ep.expeditie = de.expeditie and de.anulata = 0)
			left join decont_facturi df on (de.factura_id = df.id and df.anulata = 0)
			WHERE ep.expeditie = {$post['expeditie']} and ep.anulata = 0
			GROUP BY ep.cod_expeditie LIMIT 1";

	    $sql = $this->db->QFetchArray($query);
	    if(empty($sql))
			return '0|||Eroare Stergere:<br> Expeditia Inexistenta!';

        if(!empty($sql['factura_decont_id']) && !($this->user_id == parent::MARIAN)){
            return '0|||Eroare Stergere:<br> Expeditia decontata!';
		}

		if($sql['tip_exp'] == 3 && in_array($sql['tip_plata'], [0,3])){
            return '0|||Eroare Stergere:<br> Expeditia RBS cash/cont nu poate fi stearsa!';
		}

		if($sql['tip_exp'] == parent::TIP_EXP_BO_RBS_CASH){
            return '0|||Eroare Stergere:<br> Expeditia Borderou RBS cash nu poate fi stearsa!';
		}

		if(!empty($sql['idfact']) && !($this->user_id == parent::MARIAN)){
            return '0|||Eroare Stergere:<br> Expeditie facturata!';
		}

		if($sql['tip_exp'] == 0 && (ExpeditieDto::isSystemAwb($post['expeditie']) || ExpeditieDto::isMaravetAwb($post['expeditie'])))
			$this->db->Query("UPDATE {$this->tables['client_expeditii']} set anulata = 1 WHERE expeditie={$post['expeditie']}");

		$this->db->Query("UPDATE {$this->tables['exp_prelucrate']} set anulata = 1, deleted_at = NOW(), deleted_by = {$this->user_id} WHERE cod_expeditie = {$sql['cod_expeditie']}");
		if($sql['tip_exp'] == 0 && !empty($sql['retururi'])) {
			//anulare retururi
			$this->db->Query("UPDATE {$this->tables['exp_prelucrate']} set anulata = 1, deleted_at = NOW(), deleted_by = {$this->user_id} where expeditie in ({$sql['retururi']})");
		}

		$this->insertIstExp($sql['cod_expeditie'], 31);

		//$this->sendUpdateToAndroid($post['expeditie'], true);
		return '1|||'.$sql['cod_expeditie'];
	}

	function ValoareExpeditie() {
		$post = ExpeditieDto::uiExpToSql(array_merge($_POST, ['can_pret_impus' => $this->can_pret_impus]));
		$initialaRow = [];
		if($post['tip_exp'] > 0 && $post['referire'] > 0){
			$initialaRow = $this->GetValues($post['referire']);
			//returnare, change platitor
			if($post['tip_exp'] == 5) {
				if($this->can_pret_impus && ($initialaRow['pret_impus'] ?? 0) > 0) {
					//platitor la initiala
					$post['pret_impus'] = $initialaRow['valoare_totala_expeditie'];
				}
				if($post['platitor_id'] > 0 && $post['platitor_id'] != $initialaRow['platitor_id']){
					$initialaRow['platitor_id'] = $post['platitor_id'];
				}
			}
		}
		else {
			$post['km_preluare'] = $this->getKmLocalitate($post['expeditor_localitate_id']);
			$post['km_livrare'] = $this->getKmLocalitate($post['destinatar_localitate_id']);
			if($post['tip_exp'] == 0 && $post['tip_obj'] == 2 && $post['greutate'] < parent::MIN_KG_COLET) $post['greutate'] = parent::MIN_KG_COLET;
			if($post['tip_exp'] == 0 && $post['tip_obj'] == 3 && $post['greutate'] < parent::MIN_KG_PALET) $post['greutate'] = parent::MIN_KG_PALET;
		}

		$val = $this->Get_ValoareExpeditie($post, $initialaRow);

		//error_log('debug : '. $this->can_pret_impus . ' : ' . $post['pret_impus'] . ' : ' . $post['tip_exp']);
		if($val == false || $val['mod_plata'] > 0 && (!is_array($this->user_rights) || !in_array('preturi', $this->user_rights))){
			$val['tExpeditie'] = $val['tGreutate'] = $val['tKm'] = $val['tAsigurare'] = $val['tRamburs'] = $val['procAsigurare'] = $val['ht'] = $val['tva'] = $val['ttc'] = 'NaN';
		}
		else if($this->can_pret_impus && (($initialaRow['pret_impus'] ?? 0) > 0 || $post['pret_impus'] > 0) && $post['tip_exp'] != 7) {
			$val['tExpeditie'] = $valHT = in_array($post['tip_exp'], [0,5]) ? number_format(round($post['pret_impus'], 2), 2, '.', '') : "0.00";
			$val['tGreutate'] = '0.00';
			$val['tKm'] = '0.00';
			$val['tAsigurare'] = '0.00';
			$val['tRamburs'] = '0.00';
			$val['procAsigurare'] = '0.00';
			$val['ht'] = number_format($valHT, 2, '.', '');
			$val['tva'] = number_format(round($valHT * $this->procTva / 100, 2), 2, '.', '');
			$val['ttc'] = number_format(round($valHT + $val['tva'], 2), 2, '.', '');
		}
		else {
			$val['tExpeditie'] = number_format($val['tExpeditie'], 2, '.', '');
			$val['tGreutate'] = number_format($val['tGreutate'], 2, '.', '');
			$val['tKm'] = number_format($val['tKm'], 2, '.', '');
			$val['tAsigurare'] = number_format($val['tAsigurare'], 2, '.', '');
			$val['tRamburs'] = number_format($val['tRamburs'], 2, '.', '');
			$val['procAsigurare'] = number_format($val['procAsigurare'], 2, '.', '');
			$valHT = round($val['tExpeditie'] + $val['tGreutate'] + $val['tKm'] + $val['tAsigurare'] + $val['tRamburs'], 2);
			$val['ht'] = number_format($valHT, 2, '.', '');
        	$val['tva'] = number_format(round($valHT * $this->procTva / 100, 2), 2, '.', '');
			$val['ttc'] = number_format(round($valHT + $val['tva'], 2), 2, '.', '');
		}

		return json_encode(
			[
				"error" => 1,
				"tExpeditie" => $val['tExpeditie'],"tGreutate" => $val['tGreutate'],"tKm" => $val['tKm'],"tAsigurare" => $val['tAsigurare'],
				"tRamburs" => $val['tRamburs'],"procAsigurare" => $val['procAsigurare'],
				"ht" => $val['ht'], "tva" => $val['tva'], "ttc" => $val['ttc'],"moneda" => ($val['moneda'] ?? "LEI")
			]
		);
	}

	function ValoareReturnExpeditie() {
		$initialaAwb = intval(trim($_POST['expeditie']) ?? 0);
		$tip_exp = intval(trim($_POST['tip_exp']) ?? 0);

		if ($initialaAwb == 0)
			return json_encode(["error" => 2, "msg" => "Invalid Nr. AWB {$initialaAwb}"]);

		if ($tip_exp == 0)
			return json_encode(["error" => 2, "msg" => "Alege tip returnare"]);

		$query_ref = "SELECT expeditie from {$this->tables['exp_prelucrate']}
			WHERE referire = {$initialaAwb} AND tip_exp = {$tip_exp} and anulata = 0";
		$sql_ref = $this->db->QFetchArray($query_ref);

		if(!empty($sql_ref) && $this->user_id != parent::MARIAN)
			return json_encode(["error" => 2, "msg" => "Expeditia a fost deja folosita : {$sql_ref['expeditie']} !"]);

		if(false === ($initialaRow = $this->GetValues($initialaAwb)))
			return json_encode(["error" => 2, "msg" => "Nu exista expeditia initiala {$initialaAwb} !"]);

		if(intval($initialaRow['expeditor_id']) == 0 || intval($initialaRow['destinatar_id']) == 0 || intval($initialaRow['platitor_id']) == 0) {
			return json_encode(["error" => 2, "msg" => "Expeditor, Destinatar sau Platitor lipsa la expeditia initiala : {$initialaAwb} !"]);
		}

		if(!empty($initialaRow['tip_exp']))
			return json_encode(["error" => 2, "msg" => "Expeditia {$initialaAwb} nu este initiala !"]);

		if($tip_exp == 1 && empty($initialaRow['ret_nt']) && empty($initialaRow['extrainfo'])){
			return json_encode(["error" => 2, "msg" => "Expeditia {$initialaAwb} nu are retur NT !"]);
		}
		if($tip_exp == 2 && empty($initialaRow['ret_doc'])){
			return json_encode(["error" => 2, "msg" => "Expeditia {$initialaAwb} nu are retur document !"]);
		}
		if($tip_exp == 3){
			if(!($initialaRow['ramburs'] > 0))
				return json_encode(["error" => 2, "msg" => "Expeditia {$initialaAwb} nu are ramburs !"]);
			if(in_array($initialaRow['tip_plata'], [0, 3]))
				return json_encode(["error" => 2, "msg" => "Expeditia {$initialaAwb} are ramburs CASH sau CONT !<br/>Adaugare blocata !"]);
		}
		if($tip_exp == 6 && empty($initialaRow['ret_amb'])){
			return json_encode(["error" => 2, "msg" => "Expeditia {$initialaAwb} nu are retur ambalaj !"]);
		}

		if($tip_exp == 7 && empty($initialaRow['ret_colet'])){
			return json_encode(["error" => 2, "msg" => "Expeditia {$initialaAwb} nu are retur colet !"]);
		}

		if($tip_exp == parent::TIP_EXP_BO_RBS_CASH){
			return json_encode(["error" => 2, "msg" => "EROARE Borderou RBS cash !<br/>Adaugare blocata !"]);
		}

		$initialaRow['km_preluare'] = $initialaRow['expeditor_localitate_km'];
		$initialaRow['km_livrare'] = $initialaRow['destinatar_localitate_km'];
		$arrForVals = ['tip_exp' => $tip_exp];
		if($tip_exp == 7) {
			$arrForVals['greutate'] = 0.000;
			$arrForVals['greutate_vol'] = 0.000;
			$arrForVals['colete'] = $arrForVals['piese'] = 1;
			$arrForVals['volum'] = "";
		}
		$val = $this->Get_ValoareExpeditie($arrForVals, $initialaRow);
		$retur = ExpeditieDto::sqlInitialaToRetur($initialaRow, $arrForVals, $val);

		$retur['expeditor_nume'] = $initialaRow['destinatar_nume'];
		$retur['destinatar_nume'] = $initialaRow['expeditor_nume'];
		$retur['expeditor_localitate_id'] = $initialaRow['destinatar_localitate_id'];
		$retur['destinatar_localitate_id'] = $initialaRow['expeditor_localitate_id'];
		$retur['expeditor_localitate'] = $initialaRow['destinatar_localitate'];
		$retur['destinatar_localitate'] = $initialaRow['expeditor_localitate'];
		$retur['expeditor_adresa'] = $initialaRow['destinatar_adresa'];
		$retur['destinatar_adresa'] = $initialaRow['expeditor_adresa'];
		$retur['destinatar_cc'] = ($initialaRow['tip_plata'] == 3 && $tip_exp == 3) ? $initialaRow['expeditor_cc'] : 0;

		$retur['plateste'] = 2;
		$retur['platitor_nume'] = $initialaRow['destinatar_nume'];
		if($retur['platitor_id'] == $retur['expeditor_id']) {
			$retur['plateste'] = 1;
			$retur['platitor_nume'] = $initialaRow['expeditor_nume'];
		}
		else if($retur['platitor_id'] != $retur['destinatar_id']) {
			$retur['plateste'] = 3;
			$retur['platitor_nume'] = $initialaRow['platitor_nume'];
		}

		$pret_impus = '';
		if($val['mod_plata'] > 0 && (!is_array($this->user_rights) || !in_array('preturi', $this->user_rights))){
			$val['tExpeditie'] = $val['tGreutate'] = $val['tKm'] = $val['tAsigurare'] = $val['tRamburs'] = $val['procAsigurare'] = $val['ht'] = $val['tva'] = $val['ttc'] = 'NaN';
		}
		else {
			if($this->can_pret_impus && $initialaRow['pret_impus'] > 0 && $tip_exp != 7) {
				$pret_impus = $valHT = $val['tExpeditie'] = $tip_exp == 5 ? number_format($initialaRow['valoare_totala_expeditie'], 2, '.', '') : "0.00";
				$val['tGreutate'] = '0.00';
				$val['tKm'] = '0.00';
				$val['tAsigurare'] = '0.00';
				$val['tRamburs'] = '0.00';
				$val['procAsigurare'] = '0.00';
				$val['ht'] = number_format($valHT, 2, '.', '');
				$val['tva'] = number_format(round($valHT * $initialaRow['procTva'] / 100, 2), 2, '.', '');
				$val['ttc'] = number_format(round($valHT + $val['tva'], 2), 2, '.', '');
			} else {
				$val['tExpeditie'] = number_format($val['tExpeditie'], 2, '.', '');
				$val['tGreutate'] = number_format($val['tGreutate'], 2, '.', '');
				$val['tKm'] = number_format($val['tKm'], 2, '.', '');
				$val['tAsigurare'] = number_format($val['tAsigurare'], 2, '.', '');
				$val['tRamburs'] = number_format($val['tRamburs'], 2, '.', '');
				$val['procAsigurare'] = number_format($val['procAsigurare'], 2, '.', '');
				if($initialaRow['ramburs'] > 0 && $initialaRow['tip_plata'] == 0) {
					$tTemp = $val['tAsigurare'];
					$val['tAsigurare'] = $val['tRamburs'];
					$val['tRamburs'] = $tTemp;
					$val['procAsigurare'] = $val['procRamburs'];
				}
				$valHT = round($val['tExpeditie'] + $val['tGreutate'] + $val['tKm'] + $val['tAsigurare'] + $val['tRamburs'], 2);
				$val['ht'] = number_format($valHT, 2, '.', '');
				$val['tva'] = number_format(round($valHT * $initialaRow['procTva'] / 100, 2), 2, '.', '');
				$val['ttc'] = number_format(round($valHT + $val['tva'], 2), 2, '.', '');
			}
		}
		$retur['ramburs'] = number_format($retur['ramburs'],2, '.', '');
		$retur['valoare_asigurata'] = number_format($retur['valoare_asigurata'], 2, '.', '');
		$retur['greutate'] = number_format($retur['greutate'], 3, '.', '');

		return json_encode(
			[
				"error" => 1,
				"tExpeditie" => $val['tExpeditie'],"tGreutate" => $val['tGreutate'],"tKm" => $val['tKm'],"tAsigurare" => $val['tAsigurare'],"tRamburs" => $val['tRamburs'],
				"procAsigurare" => $val['procAsigurare'],"ht" => $val['ht'], "tva" => $val['tva'], "ttc" => $val['ttc'],
				"moneda" => $val['moneda'] ?? "LEI","mod_plata" => $val['mod_plata'],"tip_obj" => $initialaRow['tip_obj'],"colete" => $retur['colete'],"greutate" => $retur['greutate'],
				"valoare_asigurata" => $retur['valoare_asigurata'],"ramburs" => $retur['ramburs'],"tip_plata" => $retur['tip_plata'],
				"expeditor_id" => $retur['expeditor_id'],"expeditor_nume" => $retur['expeditor_nume'],"expeditor_localitate_id" => $retur['expeditor_localitate_id'],"expeditor_localitate" => $retur['expeditor_localitate'],
				"expeditor_adresa" => $retur['expeditor_adresa'],"expeditor_telefon" => $retur['expeditor_telefon'],"expeditor_contact" => $retur['expeditor_contact'],
				"destinatar_id" => $retur['destinatar_id'],"destinatar_nume" => $retur['destinatar_nume'],"destinatar_localitate_id" => $retur['destinatar_localitate_id'],"destinatar_localitate" => $retur['destinatar_localitate'],
				"destinatar_adresa" => $retur['destinatar_adresa'],"destinatar_telefon" => $retur['destinatar_telefon'],"destinatar_contact" => $retur['destinatar_contact'],"destinatar_cc" => $retur['destinatar_cc'],
				"platitor_id" => $retur['platitor_id'],"platitor_nume" => $retur['platitor_nume'],"plateste" => $retur['plateste'],"km_livrare" => $retur['km_livrare'],
				"pret_impus" => $pret_impus
			]
		);
	}

/*/////////////////////////////////////////////////////////////
				 END INTRODUCERE
/////////////////////////////////////////////////////////////*/

/*/////////////////////////////////////////////////////////////
				 START Activitate centre
/////////////////////////////////////////////////////////////*/
	function RapoarteCentre(){
		$this->vars['title_page'] = 'Rapoarte Centre';
		$vars = [];
	  	$cond = '';

		if($this->user_profile==10){
			$vars['operatiune'] = $this->ComboOperatiune(0,'disabled="disabled" style="width: 150px;"','operatiune', 0, 99);
		}
        return $this->Parse($this->page_prefix . 'rapoarte_centre.html', $vars);
	}

	 function ExportRapoarteCentre() {
		ini_set('memory_limit', '1228M');
		set_time_limit(600);
        $cond ="1=1";
		$cond2 = '';
		$flag=0;
        if(isset($_REQUEST['data_start']) && isset($_REQUEST['data_final'])){
            $data_start = $this->TransformDate($_REQUEST['data_start']);
            $data_final = $this->TransformDate($_REQUEST['data_final']);
			$cond .= " AND ep.data_expeditie between '{$data_start}' AND '{$data_final}'";
            $flag=1;
        }
        if(!empty($_REQUEST['expeditor_centru'])){
            $cond .= " AND IF(cle.zona_id > 0 and clec.id > 0, clec.id, cee.id)=".intval($_REQUEST['expeditor_centru']);
            $flag=1;
        }
        if(!empty($_REQUEST['destinatar_centru'])){
            $cond .= " AND IF(cld.zona_id > 0 and cldc.id > 0, cldc.id, ced.id)=".intval($_REQUEST['destinatar_centru']);
            $flag=1;
        }
		if(!empty($_REQUEST['platitor_centru'])){
            $cond .= " AND IF(clp.zona_id > 0 and clpc.id > 0, clpc.id, cep.id)=".intval($_REQUEST['platitor_centru']);
            $flag=1;
        }
        if(!empty($_REQUEST['expeditor'])){
            $cond .= " AND cle.nume='".$_REQUEST['expeditor']."'";
            $flag=1;
        }
        if(!empty($_REQUEST['destinatar'])){
            $cond .= " AND cld.nume='".$_REQUEST['destinatar']."'";
            $flag=1;
        }
		if(!empty($_REQUEST['platitor'])){
            $cond .= " AND clp.nume='".$_REQUEST['platitor']."'";
            $flag=1;
        }
        if(isset($_REQUEST['curier_preluare'])){
            $cond .= " AND ep.curier_preluare_id=".intval($_REQUEST['curier_preluare']);
            $flag=1;
        }
        if(isset($_REQUEST['curier_livrare'])){
            $cond .= " AND ep.curier_livrare_id=".intval($_REQUEST['curier_livrare']);
            $flag=1;
        }
        if(!empty($_REQUEST['operator']) && !isset($_REQUEST['operatiune'])){
			$cond .= " AND ep.operator_id=".intval($_REQUEST['operator']);
			$flag=1;
        }
		if(isset($_REQUEST['operator']) && isset($_REQUEST['operatiune'])){
            $cond .= " AND b.operatiune = ".intval($_REQUEST['operatiune'] ?? 0)." AND b.operator = ".intval($_REQUEST['operator']);
			$cond2 = 'LEFT JOIN ist_exp as b ON ep.cod_expeditie=b.cod_exp';
			$flag=1;
        }
        if(empty($flag)) $cond = "1=2";

		 $cu_master = "";
		 if(!empty($_REQUEST['raport_alex']) && $_REQUEST['raport_alex'] == 1)
			 $cu_master = ", cm.nume as 'Old'";

        $query = "SELECT ep.expeditie as AWB,ep.data_expeditie as Data,
		cle.nume as Expeditor, lce.nume_lc as 'Localitate Expeditor', IF(cle.zona_id > 0 and clec.id > 0, clec.nume, cee.nume) as 'Centru Expeditor',
		cld.nume as Destinatar, lcd.nume_lc as 'Destinatar Localitate', IF(cld.zona_id > 0 and cldc.id > 0, cldc.nume, ced.nume) as 'Centru Destinatar',
        clp.nume as Platitor{$cu_master}, IF(clp.zona_id > 0 and clpc.id > 0, clpc.nume, cep.nume) as 'Centru Platitor',
		ep.plicuri as Plicuri,ep.colete as Colete,ep.paleti as Paleti,ep.tip_exp as 'Tip Expeditie',ep.greutate as Greutate,
        ep.km_preluare as 'Km Prel',ep.km_livrare as 'Km Livr',ep.val_greutate as 'Val Gr',ep.val_km as 'Val km',ep.val_asig as 'Val Asig',ep.valoare_expeditie as 'Val Exp',
        ep.valoare_totala_expeditie as 'Val totala',ep.tva as TVA,ep.moneda as Moneda,
        CASE ep.mod_plata WHEN 0 THEN 'cash'  WHEN 1 THEN 'periodica' ELSE 'alta' END as 'Mod plata',ep.primitor as Primitor,ep.operatiune as 'Status',ep.data_op as 'Data op',
		IF (ep.tip_exp = 0, ep.ramburs, IF(ep.tip_exp = 3, IF(ep.tip_plata = 1 OR ep.tip_plata = 2, ep.ramburs, ep.valoare_asigurata), '')) AS Ramburs,
		IF (ep.tip_exp = 0, CASE ep.tip_plata WHEN 0 THEN 'cash'  WHEN 1 THEN 'bo' WHEN 2 THEN 'cec' WHEN 3 THEN 'cont' ELSE '' END,
		IF(ep.tip_exp = 3, CASE ep.tip_plata WHEN 0 THEN 'cash'  WHEN 1 THEN 'bo' WHEN 2 THEN 'cec' WHEN 3 THEN 'cont' ELSE '' END, '')) as 'Tip plata',
		IF(clp.master > 0, clp.master, clp.cod_cl) AS 'Master'
		from {$this->tables['exp_prelucrate']} as ep
		LEFT JOIN {$this->tables['exp_prelucrate']} ref ON  (ref.referire = ep.expeditie and ref.anulata = 0)
		left join clienti cle on cle.cod_cl = ep.expeditor_id
		left join clienti cld on cld.cod_cl = ep.destinatar_id
		left join clienti clp on clp.cod_cl = ep.platitor_id
		LEFT JOIN zones clez ON clez.id = cle.zona_id
		LEFT JOIN centre clec on clec.id = clez.centru_id
		LEFT JOIN zones cldz ON cldz.id = cld.zona_id
		LEFT JOIN centre cldc on cldc.id = cldz.centru_id
		LEFT JOIN zones clpz ON clpz.id = clp.zona_id
        LEFT JOIN centre clpc on clpc.id = clpz.centru_id
		left join localitati lce ON lce.cod_lc = cle.cod_lc
		left join localitati lcd ON lcd.cod_lc = cld.cod_lc
		left join localitati lcp ON lcp.cod_lc = clp.cod_lc
		left join centre cee ON cee.id = lce.cod_centru
		left join centre ced ON ced.id = lcd.cod_centru
		left join centre cep ON cep.id = lcp.cod_centru
		LEFT JOIN clienti cm ON cm.cod_cl = IF(clp.master > 0, clp.master, clp.cod_cl)
		{$cond2}
		WHERE {$cond} and ep.anulata = 0 ORDER BY ep.data_expeditie, ep.expeditie";
        $uresult = $this->db->Query($query, [], false);

		//error_log($query);
		$options = new Options(
    		SHOULD_ADD_BOM: false,
		);
		$writer = new Writer($options);
		$writer->openToBrowser("raport_expeditii_" . date("Y-m-d") . ".csv");
		$i = 0;
		ob_start();
		if($uresult) {
			if($row = $uresult->fetch(PDO::FETCH_ASSOC)) {
				$row_header = Row::fromValues(array_keys($row));
				$writer->addRow($row_header);
				$row['Tip Expeditie'] = ExpeditieDto::TIP_EXP[$row['Tip Expeditie'] ?? 0] ?? "unknown";
				$row_values = Row::fromValues(array_values($row));
				$writer->addRow($row_values);
			}
			while($row = $uresult->fetch(PDO::FETCH_ASSOC)) {
				$row['Tip Expeditie'] = ExpeditieDto::TIP_EXP[$row['Tip Expeditie'] ?? 0] ?? "unknown";
				$row_in = Row::fromValues(array_values($row));
				$writer->addRow($row_in);
				$i++;
				if($i % 1000 === 0) {
					ob_flush();
				}
			}
		}
		ob_end_flush();
		$writer->close();
		exit();
	}

	 function JSON_RapoarteCentre() {
	 	$responce = new StdClass();
    	$cond ="1=1";
		$cond2 = '';
		$flag=0;
        if(isset($_REQUEST['data_start']) && isset($_REQUEST['data_final'])){
            $data_start = $this->TransformDate($_REQUEST['data_start']);
            $data_final = $this->TransformDate($_REQUEST['data_final']);
			$cond .= " AND ep.data_expeditie between '{$data_start}' AND '{$data_final}'";
            $flag=1;
        }
        if(isset($_REQUEST['expeditor_centru'])){
            $cond .= " AND IF(cle.zona_id > 0 and clec.id > 0, clec.id, cee.id)=".intval($_REQUEST['expeditor_centru']);
            $flag=1;
        }
        if(!empty($_REQUEST['destinatar_centru'])){
            $cond .= " AND IF(cld.zona_id > 0 and cldc.id > 0, cldc.id, ced.id)=".intval($_REQUEST['destinatar_centru']);
            $flag=1;
        }
		if(!empty($_REQUEST['platitor_centru'])){
            $cond .= " AND IF(clp.zona_id > 0 and clpc.id > 0, clpc.id, cep.id)=".intval($_REQUEST['platitor_centru']);
            $flag=1;
        }
        if(!empty($_REQUEST['expeditor'])){
            $cond .= " AND cle.nume='".$_REQUEST['expeditor']."'";
            $flag=1;
        }
        if(!empty($_REQUEST['destinatar'])){
            $cond .= " AND cld.nume='".$_REQUEST['destinatar']."'";
            $flag=1;
        }
		if(!empty($_REQUEST['platitor'])){
            $cond .= " AND clp.nume='".$_REQUEST['platitor']."'";
            $flag=1;
        }
        if(isset($_REQUEST['curier_preluare'])){
            $cond .= " AND ep.curier_preluare_id=".intval($_REQUEST['curier_preluare']);
            $flag=1;
        }
        if(isset($_REQUEST['curier_livrare'])){
            $cond .= " AND ep.curier_livrare_id=".intval($_REQUEST['curier_livrare']);
            $flag=1;
        }
        if(isset($_REQUEST['operator']) && !isset($_REQUEST['operatiune'])){
			$cond .= " AND ep.operator_id=".intval($_REQUEST['operator']);
			$flag=1;
        }
		if(isset($_REQUEST['operator']) && isset($_REQUEST['operatiune'])){
            $cond .= " AND b.operatiune=".intval($_REQUEST['operatiune'] ?? 0)." AND b.operator=".intval($_REQUEST['operator']);
			$cond2 = 'LEFT JOIN ist_exp as b ON ep.cod_expeditie=b.cod_exp';
			$flag=1;
        }
        if(empty($flag)) {
			$responce->page = 0;
			$responce->total = 0;
			$responce->records = 0;
			return json_encode($responce);
		};

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

		$cond .= " and ep.anulata = 0";

		$query = "SELECT COUNT(ep.cod_expeditie) as nr
			from {$this->tables['exp_prelucrate']} as ep
			left join clienti cle on cle.cod_cl = ep.expeditor_id
			left join clienti cld on cld.cod_cl = ep.destinatar_id
			left join clienti clp on clp.cod_cl = ep.platitor_id
			LEFT JOIN zones clez ON clez.id = cle.zona_id
        	LEFT JOIN centre clec on clec.id = clez.centru_id
			LEFT JOIN zones cldz ON cldz.id = cld.zona_id
        	LEFT JOIN centre cldc on cldc.id = cldz.centru_id
			LEFT JOIN zones clpz ON clpz.id = clp.zona_id
        	LEFT JOIN centre clpc on clpc.id = clpz.centru_id
			left join localitati lce ON lce.cod_lc = cle.cod_lc
			left join localitati lcd ON lcd.cod_lc = cld.cod_lc
			left join localitati lcp ON lcp.cod_lc = clp.cod_lc
			left join centre cee ON cee.id = lce.cod_centru
			left join centre ced ON ced.id = lcd.cod_centru
			left join centre cep ON cep.id = lcp.cod_centru
			{$cond2}
			WHERE {$cond}";
		//error_log($query);
        $result = $this->db->QFetchArray($query);
        $count = !empty($result['nr']) ? $result['nr'] : 0;

        if( $count >0 ) {$total_pages = ceil($count/$limit); }
        else { $total_pages = 0; }
        if ($page > $total_pages) $page=$total_pages;
        if ($limit<0) $limit = 0;
        $start = $limit*$page - $limit; // do not put $limit*($page - 1)
        if ($start<0) $start = 0;
        $query = "SELECT ep.expeditie,ep.data_expeditie, ep.moneda,
			cle.nume as expeditor, cld.nume as destinatar, clp.nume as platitor,
			IF(cle.zona_id > 0 and clec.id > 0, clec.nume, cee.nume) as expeditor_centru, 
        	IF(cle.zona_id > 0 and clec.id > 0, clec.label, cee.label) as expeditor_centru_cod,
			IF(cld.zona_id > 0 and cldc.id > 0, cldc.nume, ced.nume) as destinatar_centru, 
			IF(cld.zona_id > 0 and cldc.id > 0, cldc.label, ced.label) as destinatar_centru_cod,
			ep.tip_obj, ep.piese, ep.plicuri, ep.colete, ep.paleti, ep.greutate, ep.km_preluare, ep.km_livrare,
			ep.valoare_asigurata,ep.valoare_totala_expeditie, ep.operatiune, ep.data_op,
			agp.nume_ag as curier_preluare, agl.nume_ag as curier_livrare,
			IF(clp.master > 0, clp.master, ep.platitor_id) AS 'Master'
            from {$this->tables['exp_prelucrate']} as ep
			left join clienti cle on cle.cod_cl = ep.expeditor_id
			left join clienti cld on cld.cod_cl = ep.destinatar_id
			left join clienti clp on clp.cod_cl = ep.platitor_id
			LEFT JOIN zones clez ON clez.id = cle.zona_id
        	LEFT JOIN centre clec on clec.id = clez.centru_id
			LEFT JOIN zones cldz ON cldz.id = cld.zona_id
        	LEFT JOIN centre cldc on cldc.id = cldz.centru_id
			LEFT JOIN zones clpz ON clpz.id = clp.zona_id
        	LEFT JOIN centre clpc on clpc.id = clpz.centru_id
			left join localitati lce ON lce.cod_lc = cle.cod_lc
			left join localitati lcd ON lcd.cod_lc = cld.cod_lc
			left join localitati lcp ON lcp.cod_lc = clp.cod_lc
			left join centre cee ON cee.id = lce.cod_centru
			left join centre ced ON ced.id = lcd.cod_centru
			left join centre cep ON cep.id = lcp.cod_centru
			left join agenti agp ON agp.cod_ag = ep.curier_preluare_id
            left join agenti agl ON agl.cod_ag = ep.curier_livrare_id
            {$cond2}
            WHERE {$cond}
            ORDER BY " . $sidx . " " . $sord . " LIMIT " . $start . " , " . $limit;

        $sql = $this->db->QFetchRowArray($query);
        if (!empty($sql)) {
            foreach ($sql as $key => $row) {
                $row['moneda'] = ExpeditieDto::MONEDA[$row['moneda']] ?? $row['moneda'];
				$responce->rows[$key]['id'] = $row['expeditie'];

                $responce->rows[$key]['cell'] = array($row['expeditie'], strtoupper($row['expeditor']),strtoupper($row['destinatar']),strtoupper($row['expeditor_centru']),strtoupper($row['destinatar_centru']),$row['data_expeditie'],$row['plicuri'],$row['colete'],$row['paleti'],$row['greutate'],$row['km_preluare'],$row['km_livrare'],$row['valoare_asigurata'],$row['valoare_totala_expeditie'],$row['curier_preluare'],$row['curier_livrare'],$row['operatiune'],$row['data_op'],$row['Master']);
            }
        }
		$responce->page = $page;
        $responce->total = $total_pages;
        $responce->records = $count;
        return json_encode($responce);
    }

	function ExportExpeditiiCsv($cond) {

		$arr=[];
		$arr[0][10] = 'Nr. NT';
		$arr[0][20] = 'Data colectarii';
		$arr[0][30] = 'Expeditor';
		$arr[0][40] = 'Centru expeditor';
		$arr[0][50] = 'Km. colectare';
		$arr[0][60] = 'Destinatar';
		$arr[0][70] = 'Centru destinatar';
		$arr[0][80] = 'Km. livrare';
		$arr[0][90] = 'Platitor';

		if(!empty($_REQUEST['raport_alex']) && $_REQUEST['raport_alex'] == 1)
			$arr[0][95] = 'Old';

		$arr[0][100] = 'Centru platitor';
		$arr[0][110] = 'Piese';
		$arr[0][120] = 'Plic';
		$arr[0][130] = 'Palet';
		$arr[0][140] = 'Greutate';
		$arr[0][150] = 'Tip expeditie';
		$arr[0][160] = 'Tarif baza';
		$arr[0][170] = 'Tarif kg.';
		$arr[0][180] = 'Tarif km. ext.';
		$arr[0][190] = 'Valoare asigurata';
		$arr[0][200] = 'Proc. asig.';
		$arr[0][210] = 'Ramburs';
		$arr[0][220] = 'Tip plata';
		$arr[0][230] = 'Proc. ramb';
		$arr[0][240] = 'Tarif asigurare';
		$arr[0][250] = 'Total valoare';
		$arr[0][260] = 'Moneda';
		$arr[0][270] = 'Mod Plata';

        $query = "SELECT ep.expeditie, ep.data_expeditie, ep.data, ep.tip_exp,
			cle.nume as expeditor, cld.nume as destinatar, clp.nume as platitor,
			lce.nume_lc as expeditor_localitate, lcd.nume_lc as destinatar_localitate,
			IF(cle.zona_id > 0 and clec.id > 0, clec.nume, cee.nume) as expeditor_centru, 
        	IF(cle.zona_id > 0 and clec.id > 0, clec.label, cee.label) as expeditor_centru_cod,
			IF(cld.zona_id > 0 and cldc.id > 0, cldc.nume, ced.nume) as destinatar_centru, 
			IF(cld.zona_id > 0 and cldc.id > 0, cldc.label, ced.label) as destinatar_centru_cod,
			IF(clp.zona_id > 0 and clpc.id > 0, clpc.nume, cep.nume) as platitor_centru, 
			IF(clp.zona_id > 0 and clpc.id > 0, clpc.label, cep.label) as platitor_centru_cod,
			ep.tip_obj, ep.piese, ep.plicuri, ep.colete, ep.paleti, ep.greutate, ep.km_preluare, ep.km_livrare,
			ep.valoare_asigurata, ep.ramburs,
			CASE ep.tip_plata WHEN 0 THEN 'cash'  WHEN 1 THEN 'bo' WHEN 2 THEN 'cec' WHEN 3 THEN 'cont' ELSE '' END as tipPlata,
			ep.procent_asigurare, ep.ramburs_procent,
			ep.valoare_expeditie, ep.val_greutate, ep.val_km, ep.val_asig, ep.valoare_totala_expeditie, ep.tva, ep.mod_plata, ep.moneda,
			ep.observatii, ep.detalii_doc, ep.operatiune, ep.data_op,
			agp.nume_ag as curier_preluare, agl.nume_ag as curier_livrare,
			b.invoice, b.sumamnt, cm.nume as platitor_master
            from {$this->tables['exp_prelucrate']} ep
			left join clienti cle on cle.cod_cl = ep.expeditor_id
			left join clienti cld on cld.cod_cl = ep.destinatar_id
			left join clienti clp on clp.cod_cl = ep.platitor_id
			LEFT JOIN zones clez ON clez.id = cle.zona_id
        	LEFT JOIN centre clec on clec.id = clez.centru_id
			LEFT JOIN zones cldz ON cldz.id = cld.zona_id
        	LEFT JOIN centre cldc on cldc.id = cldz.centru_id
			LEFT JOIN zones clpz ON clpz.id = clp.zona_id
        	LEFT JOIN centre clpc on clpc.id = clpz.centru_id
			left join localitati lce ON lce.cod_lc = cle.cod_lc
			left join localitati lcd ON lcd.cod_lc = cld.cod_lc
			left join localitati lcp ON lcp.cod_lc = clp.cod_lc
			left join centre cee ON cee.id = lce.cod_centru
			left join centre ced ON ced.id = lcd.cod_centru
			left join centre cep ON cep.id = lcp.cod_centru
			left join agenti agp ON agp.cod_ag = ep.curier_preluare_id
            left join agenti agl ON agl.cod_ag = ep.curier_livrare_id
			left join clienti cm ON cm.cod_cl = IF(clp.master > 0, clp.master, clp.cod_cl)
            left join exp_facturi b on b.id = ep.idfact
            WHERE {$cond}
            ORDER BY ep.data_expeditie, cle.nume ASC";
		//error_log($query);
        $sql = $this->db->QFetchRowArray($query);
        //compun raspunsul
        if (!empty($sql)) {
        	$rand = 2;
            foreach ($sql as $key => $row) {

                $row['data_expeditie'] = $this->CreateDate($row['data_expeditie']);
                $row['data_introducere'] = $this->CreateDate($row['data']);

				if(!empty($row['plicuri'])){
					$row['plicuri'] = 'DA';
					$row['piese'] = 1;
				}else if(!empty($row['paleti'])){
					$row['paleti'] = 'DA';
					$row['piese'] = 1;
				}else{
					$row['paleti'] = 'NU';
					$row['plicuri'] = 'NU';
					$row['piese'] = $row['colete'];
				}
				if($row['mod_plata'] > 0 && (!is_array($this->user_rights) || !in_array('preturi', $this->user_rights))) {
					$row['valoare_expeditie'] = $row['val_greutate'] = $row['val_km'] = $row['val_asig'] = $row['valoare_totala_expeditie'] = $row['tva'] = 'NaN';
				}
				$row['tarif_baza'] = $row['valoare_expeditie'];
				$row['tarif_kg'] = $row['val_greutate'];
				$row['tarif_km'] = $row['val_km'];
				$row['tarif_asig'] = $row['val_asig'];
				$row['tarif_total'] = $row['valoare_totala_expeditie'];
				$row['statut_actual'] = $row['operatiune'];

				$arr[($key+1)][10] = $row['expeditie'];
				$arr[($key+1)][20] = $row['data_expeditie'];
				$arr[($key+1)][30] = strtoupper($row['expeditor'].'('.$row['expeditor_localitate'].')');
				$arr[($key+1)][40] = $row['expeditor_centru'];
				$arr[($key+1)][50] = $row['km_preluare'];
				$arr[($key+1)][60] = strtoupper($row['destinatar'].'('.$row['destinatar_localitate'].')');
				$arr[($key+1)][70] = $row['destinatar_centru'];
				$arr[($key+1)][80] = $row['km_livrare'];
				$arr[($key+1)][90] = $row['platitor'];

				if(!empty($_REQUEST['raport_alex']) && $_REQUEST['raport_alex'] == 1)
					$arr[($key+1)][95] = $row['platitor_master'];

				$arr[($key+1)][100] = $row['platitor_centru'];
				$arr[($key+1)][110] = $row['piese'];
				$arr[($key+1)][120] = $row['plicuri'];
				$arr[($key+1)][130] = $row['paleti'];
				$arr[($key+1)][140] = $row['greutate'];
				$arr[($key+1)][150] = ExpeditieDto::TIP_EXP[$row['tip_exp']] ?? "unknown";
				$arr[($key+1)][160] = $row['tarif_baza'];
				$arr[($key+1)][170] = $row['tarif_kg'];
				$arr[($key+1)][180] = $row['tarif_km'];
				$arr[($key+1)][190] = $row['valoare_asigurata'];
				$arr[($key+1)][200] = $row['procent_asigurare'];
				$arr[($key+1)][210] = $row['ramburs'];
				$arr[($key+1)][220] = $row['tipPlata'];
				$arr[($key+1)][230] = $row['ramburs_procent'];
				$arr[($key+1)][240] = $row['tarif_asig'];
				$arr[($key+1)][250] = $row['tarif_total'];
				$arr[($key+1)][260] = ExpeditieDto::MONEDA[$row['moneda']] ?? $row['moneda'];
				$arr[($key+1)][270] = ExpeditieDto::MOD_PLATA[$row['mod_plata']] ?? ExpeditieDto::MOD_PLATA[0];
            }
        }
    	$this->download_send_headers("data_export_" . date("Y-m-d") . ".csv");
		echo $this->array2csv($arr);die;

    }

	function GenerareCsvExpeditii($cond) {
		$query = "SELECT ep.expeditie,ep.data_expeditie,
			cle.nume as expeditor, cld.nume as destinatar, clp.nume as platitor,
			lce.nume_lc as expeditor_localitate, lcd.nume_lc as destinatar_localitate,
			IF(cle.zona_id > 0 and clec.id > 0, clec.nume, cee.nume) as expeditor_centru, 
        	IF(cle.zona_id > 0 and clec.id > 0, clec.label, cee.label) as expeditor_centru_cod,
			IF(cld.zona_id > 0 and cldc.id > 0, cldc.nume, ced.nume) as destinatar_centru, 
			IF(cld.zona_id > 0 and cldc.id > 0, cldc.label, ced.label) as destinatar_centru_cod,
			IF(clp.zona_id > 0 and clpc.id > 0, clpc.nume, cep.nume) as platitor_centru, 
			IF(clp.zona_id > 0 and clpc.id > 0, clpc.label, cep.label) as platitor_centru_cod,
			ep.plicuri,ep.colete,ep.paleti,ep.tip_exp,ep.greutate,ep.km_preluare,ep.km_livrare,
			ep.val_greutate,ep.val_km,ep.val_asig,ep.valoare_expeditie,ep.valoare_totala_expeditie,ep.tva,ep.moneda,
			CASE ep.mod_plata WHEN 0 THEN 'per NT'  WHEN 1 THEN 'factura periodica' ELSE 'alta' END as mod_plata
			from {$this->tables['exp_prelucrate']} ep
			left join clienti cle on cle.cod_cl = ep.expeditor_id
			left join clienti cld on cld.cod_cl = ep.destinatar_id
			left join clienti clp on clp.cod_cl = ep.platitor_id
			LEFT JOIN zones clez ON clez.id = cle.zona_id
        	LEFT JOIN centre clec on clec.id = clez.centru_id
			LEFT JOIN zones cldz ON cldz.id = cld.zona_id
        	LEFT JOIN centre cldc on cldc.id = cldz.centru_id
			LEFT JOIN zones clpz ON clpz.id = clp.zona_id
        	LEFT JOIN centre clpc on clpc.id = clpz.centru_id
			left join localitati lce ON lce.cod_lc = cle.cod_lc
			left join localitati lcd ON lcd.cod_lc = cld.cod_lc
			left join localitati lcp ON lcp.cod_lc = clp.cod_lc
			left join centre cee ON cee.id = lce.cod_centru
			left join centre ced ON ced.id = lcd.cod_centru
			left join centre cep ON cep.id = lcp.cod_centru
			WHERE {$cond} ORDER BY ep.data_expeditie, ep.expeditie";
		$exps = $this->db->QFetchRowArray($query);

		$this->download_send_headers("raport_expeditii_" . date("Y-m-d") . ".csv");
		echo $this->array2csv($exps);die;
    }

	function ActivitateCentre(){
		$this->vars['title_page'] = 'Activitate Centre';
        return $this->Parse($this->page_prefix . 'activitate_centre.html', []);
	}


    function JSON_ActivitateColectari() {
    	$responce = new StdClass();
		$centru_id = intval($_REQUEST['centru'] ?? 0);
    	$cond = "1=1";
		$flag=0;
		if(isset($_REQUEST['data_start']) && isset($_REQUEST['data_final'])){
            $data_start = $this->TransformDate($_REQUEST['data_start']);
            $data_final = $this->TransformDate($_REQUEST['data_final']);
			$cond .= " AND ep.data_expeditie between '{$data_start}' AND '{$data_final}'";
            $flag=1;
        }
		if($centru_id > 0){
			$cond .= " AND IF(cle.zona_id > 0 and clec.id > 0, clec.id, lce.cod_centru) = {$centru_id}";
			$flag=1;
		}
		if(empty($flag)) $cond = '1=2';

		$page = intval($_REQUEST['page'] ?? 1);
		$limit = intval($_REQUEST['rows'] ?? 20);
		$sidx = trim($this->sanitize($_REQUEST['sidx'] ?? 1));
		$sord = trim($this->sanitize($_REQUEST['sord'] ?? 'asc'));

		$cond .= " and ep.anulata = 0";
    	$query = "SELECT COUNT(ep.expeditie) as nr
			from {$this->tables['exp_prelucrate']} ep
			inner join clienti cle on cle.cod_cl = ep.expeditor_id
			LEFT JOIN zones clez ON clez.id = cle.zona_id
        	LEFT JOIN centre clec on clec.id = clez.centru_id
			inner join localitati lce ON lce.cod_lc = cle.cod_lc
			WHERE {$cond}";
		//echo $query;die;
        $result = $this->db->QFetchArray($query);
        $count = !empty($result['nr']) ? $result['nr'] : 0;

        if( $count >0 ) {
            $total_pages = ceil($count/$limit); }
        else { $total_pages = 0; }
        if ($page > $total_pages) $page=$total_pages;
        if ($limit<0) $limit = 0;
        $start = $limit*$page - $limit; // do not put $limit*($page - 1)
        if ($start<0) $start = 0;

		$query = "SELECT ep.expeditie, ep.data_expeditie, ep.km_preluare, ep.greutate,
			cle.nume as expeditor, lce.nume_lc as expeditor_localitate, lce.dist_km as km_loc
			from {$this->tables['exp_prelucrate']} ep
			inner join clienti cle on cle.cod_cl = ep.expeditor_id
			LEFT JOIN zones clez ON clez.id = cle.zona_id
        	LEFT JOIN centre clec on clec.id = clez.centru_id
			inner join localitati lce ON lce.cod_lc = cle.cod_lc
			WHERE {$cond}
			ORDER BY " . $sidx . " " . $sord . " LIMIT " . $start . " , " . $limit;
        $sql = $this->db->QFetchRowArray($query);
        if (!empty($sql)) {
            foreach ($sql as $key => $row) {
            	//$row['data_expeditie'] = $this->CreateDate($row['data_expeditie'],'d/m/Y');
                $responce->rows[$key]['id'] = $row['expeditie'];
                $responce->rows[$key]['cell'] = array($row['data_expeditie'],$row['expeditor_localitate'],$row['km_loc'],$row['expeditor'],$row['km_preluare'],$row['expeditie'],$row['greutate']);
            }
        }
        $responce->page = $page;
        $responce->total = $total_pages;
        $responce->records = $count;

        $_SESSION['activitate_colectari_count'] = $count;

        return json_encode($responce);
    }

     function JSON_ActivitateLivrari() {
     	$responce = new StdClass();
		$centru_id = intval($_REQUEST['centru'] ?? 0);

    	$flag=0;
		$cond = "1=1";
		if(isset($_REQUEST['data_start']) && isset($_REQUEST['data_final'])){
            $data_start = $this->TransformDate($_REQUEST['data_start']);
            $data_final = $this->TransformDate($_REQUEST['data_final']);
			$cond .= " AND ep.data_expeditie between '{$data_start}' AND '{$data_final}'";
            $flag=1;
        }
		if($centru_id > 0){
			$cond .= " AND IF(cld.zona_id > 0 and cldc.id > 0, cldc.id, lcd.cod_centru) = {$centru_id}";
			$flag=1;
		}
		if(empty($flag)) $cond = '1=2';

		$page = intval($_REQUEST['page'] ?? 1);
		$limit = intval($_REQUEST['rows'] ?? 20);
		$sidx = trim($this->sanitize($_REQUEST['sidx'] ?? 1));
		$sord = trim($this->sanitize($_REQUEST['sord'] ?? 'asc'));

		$cond .= " and ep.anulata = 0";

    	$query = "SELECT COUNT(ep.expeditie) as nr
			FROM {$this->tables['exp_prelucrate']} ep
			inner join clienti cld on cld.cod_cl = ep.destinatar_id
			LEFT JOIN zones cldz ON cldz.id = cld.zona_id
        	LEFT JOIN centre cldc on cldc.id = cldz.centru_id
			inner join localitati lcd ON lcd.cod_lc = cld.cod_lc
			WHERE {$cond} ;";
        $result = $this->db->QFetchArray($query);
        $count = !empty($result['nr']) ? $result['nr'] : 0;

        if( $count >0 ) {
            $total_pages = ceil($count/$limit); }
        else { $total_pages = 0; }
        if ($page > $total_pages) $page=$total_pages;
        if ($limit<0) $limit = 0;
        $start = $limit*$page - $limit; // do not put $limit*($page - 1)
        if ($start<0) $start = 0;

		$query = "SELECT ep.expeditie, ep.data_expeditie, ep.km_livrare, ep.greutate, ep.operatiune,
			cld.nume as destinatar, lcd.nume_lc as destinatar_localitate, lcd.dist_km as km_loc
			from {$this->tables['exp_prelucrate']} ep
			inner join clienti cld on cld.cod_cl = ep.destinatar_id
			LEFT JOIN zones cldz ON cldz.id = cld.zona_id
        	LEFT JOIN centre cldc on cldc.id = cldz.centru_id
			inner join localitati lcd ON lcd.cod_lc = cld.cod_lc
			WHERE {$cond} ORDER BY " . $sidx . " " . $sord . " LIMIT " . $start . " , " . $limit;
        $sql = $this->db->QFetchRowArray($query);
        if (!empty($sql)) {
            foreach ($sql as $key => $row) {
            	//$row['data_expeditie'] = $this->CreateDate($row['data_expeditie'],'d/m/Y');
                $responce->rows[$key]['id'] = $row['expeditie'];
                $responce->rows[$key]['cell'] = array($row['data_expeditie'],$row['destinatar_localitate'],$row['km_loc'],$row['destinatar'],$row['km_livrare'],$row['expeditie'],$row['greutate'],$row['operatiune']);
            }
        }
        $responce->page = $page;
        $responce->total = $total_pages;
        $responce->records = $count;

        $_SESSION['activitate_livrari_count'] = $count;

        return json_encode($responce);
    }

	function ExportActivitateCentru() {
		$vars = [];
		$post = $_POST;
		$cond = '1=2';
	    if(isset($post['data_start']) && isset($post['data_final'])){
	        $data_start = $this->TransformDate($post['data_start']);
	        $data_final = $this->TransformDate($post['data_final']);
	        $cond = "ep.data_expeditie>='".$data_start."' AND ep.data_expeditie<='".$data_final."'";
			$vars['perioada'] = $post['data_start'].' - '.$post['data_final'];
	    }

		if(!empty($post['centru'])){
			$cond .= ' AND IF(cle.zona_id > 0 and clec.id > 0, clec.id, lce.cod_centru) = '.$post['centru'];
		}else
			$cond = '1=2';

		return $this->ExportExpeditii($cond);
    }

	function PrintActivitateCentru(){
		return 'Deactivated';
	}

/*/////////////////////////////////////////////////////////////
				 end Activitate centre
/////////////////////////////////////////////////////////////*/

    function AfisareValori($field){
        return $_SESSION[$field.'_colectari_valoare']."|||".$_SESSION[$field.'_livrari_valoare']."|||".$_SESSION[$field.'_colectari_count']."|||".$_SESSION[$field.'_livrari_count'];
	}


/*/////////////////////////////////////////////////////////////
				 END INCASARI
/////////////////////////////////////////////////////////////*/





/*/////////////////////////////////////////////////////////////
				 COLECTARI CENTRE
/////////////////////////////////////////////////////////////*/
function ColectariCentre(){
		$this->vars['title_page'] = 'Colectari Centre';
		$vars = [];
	  	$cond = '';

		$vars['data_start'] = date('d.m.Y');
		$vars['data_final'] = date('d.m.Y');
		return $this->Parse($this->page_prefix . 'colectari_centre.html', $vars);
	}

    function JSON_ColectariCentre() {
    	$responce = new StdClass();
    	$cond = "1=1";
		$flag=0;
        if(isset($_REQUEST['data_start']) && isset($_REQUEST['data_final'])){
            $data_start = $this->TransformDate($_REQUEST['data_start']);
            $data_final = $this->TransformDate($_REQUEST['data_final']);
			$cond .= " AND ep.data_expeditie between '{$data_start}' AND '{$data_final}'";
            $flag=1;
        }
		if(!empty($_REQUEST['fara_retururi'])){
			$cond .= ' AND ep.referire = 0';
			$flag=1;
		}
		if(!empty($_REQUEST['numai_retururi'])){
			$cond .= " AND ep.referire > 0";
			$flag=1;
		}
		if(!empty($_REQUEST['ramburs'])){
			$cond .= " AND ep.ramburs > 0";
			$flag=1;
		}
		if(!empty($_REQUEST['cash'])){
			$cond .= ' AND ep.mod_plata = 0';
			$flag=1;
		}
		if(!empty($_REQUEST['factura'])){
			$cond .= ' AND ep.mod_plata = 1';
			$flag=1;
		}
		if(!empty($_REQUEST['platitor'])){
			if($_REQUEST['platitor']==2)
				$cond .= ' AND ep.platitor_id = ep.expeditor_id';
			else if($_REQUEST['platitor']==3)
				$cond .= ' AND ep.platitor_id = ep.destinatar_id';
			else if($_REQUEST['platitor']==3)
				$cond .= ' AND ep.platitor_id != ep.expeditor_id AND ep.platitor_id != ep.destinatar_id';
			$flag=1;
		}
		if(empty($flag)) $cond = '1=2';

        $page = intval($_REQUEST['page'] ?? 1);
		$limit = intval($_REQUEST['rows'] ?? 20);
		$sidx = trim($this->sanitize($_REQUEST['sidx'] ?? 1));
		$sord = trim($this->sanitize($_REQUEST['sord'] ?? 'asc'));

		$cond .= " and ep.anulata = 0";
		$query = "SELECT COUNT(IF(cle.zona_id > 0 and clec.id > 0, clec.id, cee.id)) as nr
			from {$this->tables['exp_prelucrate']} ep
			left join clienti cle on cle.cod_cl = ep.expeditor_id
			LEFT JOIN zones clez ON clez.id = cle.zona_id
        	LEFT JOIN centre clec on clec.id = clez.centru_id
			left join localitati lce ON lce.cod_lc = cle.cod_lc
			left join centre cee ON cee.id = lce.cod_centru
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
        $query = "SELECT IF(cle.zona_id > 0 and clec.id > 0, clec.nume, cee.nume) as expeditor_centru, 
			IF(cle.zona_id > 0 and clec.id > 0, clec.id, cee.id) as expeditor_centru_id,
			COUNT(ep.expeditie) as colectari,
        	SUM(ep.greutate) as greutate, SUM(ep.plicuri) as plicuri, SUM(ep.colete) as colete, SUM(ep.paleti) as paleti,
        	SUM(ep.km_preluare) as km_ext,	SUM(IF(ep.km_preluare > 0, 1,0)) AS col_ext,
        	SUM(CASE WHEN ep.moneda IN ('LEI','') THEN ep.valoare_totala_expeditie ELSE 0 END) AS lei,
        	SUM(CASE WHEN ep.moneda='USD' THEN ep.valoare_totala_expeditie ELSE 0 END) AS usd,
        	SUM(CASE WHEN ep.moneda='EUR' THEN ep.valoare_totala_expeditie ELSE 0 END) AS eur,

        	SUM(CASE WHEN ep.moneda IN ('LEI','') THEN ep.valoare_asigurata ELSE 0 END) AS asig_lei,
        	SUM(CASE WHEN ep.moneda='USD' THEN ep.valoare_asigurata ELSE 0 END) AS asig_usd,
        	SUM(CASE WHEN ep.moneda='EUR' THEN ep.valoare_asigurata ELSE 0 END) AS asig_eur

            from {$this->tables['exp_prelucrate']} ep
			left join clienti cle on cle.cod_cl = ep.expeditor_id
			LEFT JOIN zones clez ON clez.id = cle.zona_id
        	LEFT JOIN centre clec on clec.id = clez.centru_id
			left join localitati lce ON lce.cod_lc = cle.cod_lc
			left join centre cee ON cee.id = lce.cod_centru
            WHERE {$cond}
            GROUP BY IF(cle.zona_id > 0 and clec.id > 0, clec.id, cee.id)
            ORDER BY " . $sidx . " " . $sord . " LIMIT " . $start . " , " . $limit;
            //echo $query;die;

        $total_colectari = 0;
		$total_colete = 0;
		$total_paleti = 0;
		$total_plicuri = 0;
		$total_greutate = 0.00;
		$total_col_ext = 0.00;
		$total_km_ext = 0.00;
		$total_lei = 0.00;
		$total_usd = 0.00;
		$total_eur = 0.00;
		$total_asig_lei = 0.00;
		$total_asig_usd = 0.00;
		$total_asig_eur = 0.00;

        $sql = $this->db->QFetchRowArray($query);
        if (!empty($sql)) {
            foreach ($sql as $key => $row) {
                $row['moneda'] = 'Lei';
				$responce->rows[$key]['id'] = $row['expeditor_centru_id'];
				$responce->rows[$key]['cell'] = array($row['expeditor_centru'], $row['colectari'], $row['colete'], $row['paleti'], $row['plicuri'], $row['greutate'], $row['col_ext'], $row['km_ext'], $row['lei'], $row['usd'], $row['eur'], $row['asig_lei'], $row['asig_usd'], $row['asig_eur']);

                $total_colectari += $row['colectari'];
				$total_colete += $row['colete'];
				$total_paleti += $row['paleti'];
				$total_plicuri += $row['plicuri'];
				$total_greutate += $row['greutate'];
				$total_col_ext += $row['col_ext'];
				$total_km_ext += $row['km_ext'];
				$total_lei += $row['lei'];
				$total_usd += $row['usd'];
				$total_eur += $row['eur'];
				$total_asig_lei += $row['asig_lei'];
				$total_asig_usd += $row['asig_usd'];
				$total_asig_eur += $row['asig_eur'];

            }
        }
		$responce->page = $page;
        $responce->total = $total_pages;
        $responce->records = $count;

		$responce->userdata['expeditor_centru'] = 'Total: ';
 		$responce->userdata['colectari'] = $total_colectari;
		$responce->userdata['colete'] = $total_colete;
		$responce->userdata['paleti'] = $total_paleti;
		$responce->userdata['plicuri'] = $total_plicuri;
		$responce->userdata['greutate'] = $total_greutate;
		$responce->userdata['col_ext'] =  round($total_col_ext,2);
		$responce->userdata['km_ext'] =  round($total_km_ext,2);
		$responce->userdata['lei'] =  round($total_lei,2);
		$responce->userdata['usd'] =  round($total_usd,2);
		$responce->userdata['eur'] =  round($total_eur,2);
		$responce->userdata['asig_lei'] =  round($total_asig_lei,2);
		$responce->userdata['asig_usd'] =  round($total_asig_usd,2);
		$responce->userdata['asig_eur'] = round($total_asig_eur,2);

        return json_encode($responce);
    }

    function JSON_ListeColectariDetalii() {
    	$responce = new StdClass();
        $centru_id = intval($_REQUEST['centru'] ?? 0);
		$cond='';
      	if(isset($_REQUEST['data_start']) && isset($_REQUEST['data_final'])){
	        $data_start = $this->TransformDate($_REQUEST['data_start']);
	        $data_final = $this->TransformDate($_REQUEST['data_final']);
			$cond .= " AND ep.data_expeditie between '{$data_start}' AND '{$data_final}'";
	    }
		if(empty($centru_id)) $cond = ' AND 1=2';

		$page = intval($_REQUEST['page'] ?? 1);
		$limit = intval($_REQUEST['rows'] ?? 20);
		$sidx = trim($this->sanitize($_REQUEST['sidx'] ?? 1));
		$sord = trim($this->sanitize($_REQUEST['sord'] ?? 'asc'));

		$cond .= " and ep.anulata = 0";
        $query = "SELECT COUNT(DISTINCT(ep.expeditor_id)) as nr
            from {$this->tables['exp_prelucrate']} ep
			inner join clienti cle on cle.cod_cl = ep.expeditor_id
			LEFT JOIN zones clez ON clez.id = cle.zona_id
        	LEFT JOIN centre clec on clec.id = clez.centru_id
			inner join localitati lce ON lce.cod_lc = cle.cod_lc
            WHERE IF(cle.zona_id > 0 and clec.id > 0, clec.id, lce.cod_centru) = {$centru_id} {$cond}
            ";
        $result = $this->db->QFetchArray($query);
        $count = !empty($result['nr']) ? $result['nr'] : 0;

        if( $count >0 ) {
            $total_pages = ceil($count/$limit); }
        else { $total_pages = 0; }
        if ($page > $total_pages) $page=$total_pages;
        if ($limit<0) $limit = 0;
        $start = $limit*$page - $limit; // do not put $limit*($page - 1)
        if ($start<0) $start = 0;
		$query = "SELECT cle.nume as expeditor, ep.expeditor_id, lce.nume_lc as expeditor_localitate,
			COUNT(ep.cod_expeditie) as expeditii, SUM(ep.greutate) as greutate, SUM(ep.plicuri) as plicuri, SUM(ep.colete) as colete,
			SUM(ep.paleti) as paleti, SUM(ep.km_livrare + ep.km_preluare) as km
			from {$this->tables['exp_prelucrate']} ep
			inner join clienti cle on cle.cod_cl = ep.expeditor_id
			LEFT JOIN zones clez ON clez.id = cle.zona_id
        	LEFT JOIN centre clec on clec.id = clez.centru_id
			inner join localitati lce ON lce.cod_lc = cle.cod_lc
			WHERE IF(cle.zona_id > 0 and clec.id > 0, clec.id, lce.cod_centru) = {$centru_id} {$cond}
			GROUP BY ep.expeditor_id
			ORDER BY " . $sidx . " " . $sord . " LIMIT " . $start . " , " . $limit;
			//echo $query;die;
        $sql = $this->db->QFetchRowArray($query);
        //compun raspunsul
        if (!empty($sql)) {
            foreach ($sql as $key => $row) {
            	if(empty($row['paleti'])) $row['paleti']=0;
                $responce->rows[$key]['id'] = $row['expeditor_id'];
                $responce->rows[$key]['cell'] = array($row['expeditor_localitate'],$row['expeditor'], $row['expeditii'], $row['greutate'],$row['plicuri'],$row['colete'],$row['paleti'],$row['km']);
            }
        }
        $responce->page = $page;
        $responce->total = $total_pages;
        $responce->records = $count;
        return json_encode($responce);
    }
    function JSON_ListeColectariDetaliiClient() {
    	$responce = new StdClass();
		$centru_id = intval($_REQUEST['centru'] ?? 0);
        $client = intval($_REQUEST['client'] ?? 0);
		if($centru_id == 0 || $client == 0) 
			return json_encode($responce);
        $cond = "ep.expeditor_id = {$client} and IF(cle.zona_id > 0 and clec.id > 0, clec.id, lce.cod_centru) = {$centru_id}";
      	if(isset($_REQUEST['data_start']) && isset($_REQUEST['data_final'])){
	        $data_start = $this->TransformDate($_REQUEST['data_start']);
	        $data_final = $this->TransformDate($_REQUEST['data_final']);
			$cond .= " AND ep.data_expeditie between '{$data_start}' AND '{$data_final}'";
	    }

		$page = intval($_REQUEST['page'] ?? 1);
		$limit = intval($_REQUEST['rows'] ?? 20);
		$sidx = trim($this->sanitize($_REQUEST['sidx'] ?? 1));
		$sord = trim($this->sanitize($_REQUEST['sord'] ?? 'asc'));

		$cond .= " and ep.anulata = 0";
        $query = "SELECT COUNT(ep.cod_expeditie) as nr
            from {$this->tables['exp_prelucrate']} ep
			left join clienti cle on cle.cod_cl = ep.expeditor_id
			LEFT JOIN zones clez ON clez.id = cle.zona_id
        	LEFT JOIN centre clec on clec.id = clez.centru_id
			left join localitati lce ON lce.cod_lc = cle.cod_lc
			left join clienti cld on cld.cod_cl = ep.destinatar_id
			left join localitati lcd ON lcd.cod_lc = cld.cod_lc
            WHERE {$cond}
            ";
        $result = $this->db->QFetchArray($query);
        $count = !empty($result['nr']) ? $result['nr'] : 0;

        if( $count >0 ) {
            $total_pages = ceil($count/$limit); }
        else { $total_pages = 0; }
        if ($page > $total_pages) $page=$total_pages;
        if ($limit<0) $limit = 0;
        $start = $limit*$page - $limit; // do not put $limit*($page - 1)
        if ($start<0) $start = 0;
		$query = "SELECT cld.nume as destinatar, ep.destinatar_id, lcd.nume_lc as destinatar_localitate,
			ep.expeditie,ep.greutate, ep.tip_obj, ep.piese, ep.plicuri,ep.colete,ep.paleti,(ep.km_livrare + ep.km_preluare) as km
			from {$this->tables['exp_prelucrate']} ep
			left join clienti cle on cle.cod_cl = ep.expeditor_id
			LEFT JOIN zones clez ON clez.id = cle.zona_id
        	LEFT JOIN centre clec on clec.id = clez.centru_id
			left join localitati lce ON lce.cod_lc = cle.cod_lc
			left join clienti cld on cld.cod_cl = ep.destinatar_id
			left join localitati lcd ON lcd.cod_lc = cld.cod_lc
			WHERE {$cond}
			ORDER BY " . $sidx . " " . $sord . " LIMIT " . $start . " , " . $limit;
			//echo $query;die;
        $sql = $this->db->QFetchRowArray($query);
        //compun raspunsul
        if (!empty($sql)) {
            foreach ($sql as $key => $row) {
            	if(empty($row['paleti'])) $row['paleti']=0;
				$responce->rows[$key]['id'] = $row['expeditie'];
                $responce->rows[$key]['cell'] = array($row['expeditie'], $row['destinatar'],$row['destinatar_localitate'], $row['greutate'],$row['plicuri'],$row['colete'],$row['paleti'],$row['km']);
            }
        }
        $responce->page = $page;
        $responce->total = $total_pages;
        $responce->records = $count;
        return json_encode($responce);
    }


/*/////////////////////////////////////////////////////////////
				 LIVRARI CENTRE
/////////////////////////////////////////////////////////////*/
function LivrariCentre(){
		$this->vars['title_page'] = 'Livrari Centre';
		$vars = [];
	  	$cond = '';

		$vars['data_start'] = date('d.m.Y');
		$vars['data_final'] = date('d.m.Y');
		return $this->Parse($this->page_prefix . 'livrari_centre.html', $vars);
	}

    function JSON_LivrariCentre() {
    	$responce = new StdClass();
		$responce->page = 0;
		$responce->total = 0;
		$responce->records = 0;

    	$cond = "1=1";
		$flag=0;
        if(isset($_REQUEST['data_start']) && isset($_REQUEST['data_final'])){
            $data_start = $this->TransformDate($_REQUEST['data_start']);
            $data_final = $this->TransformDate($_REQUEST['data_final']);
			$cond .= " AND ep.data_expeditie between '{$data_start}' AND '{$data_final}'";
            $flag=1;
        }
		else return json_encode($responce);

		if(!empty($_REQUEST['fara_retururi'])){
			$cond .= ' AND ep.referire = 0';
			$flag=1;
		}
		if(!empty($_REQUEST['numai_retururi'])){
			$cond .= " AND ep.referire > 0";
			$flag=1;
		}
		if(!empty($_REQUEST['ramburs'])){
			$cond .= " AND ep.ramburs > 0";
			$flag=1;
		}
		if(!empty($_REQUEST['cash'])){
			$cond .= ' AND ep.mod_plata = 0';
			$flag=1;
		}
		if(!empty($_REQUEST['factura'])){
			$cond .= ' AND ep.mod_plata = 1';
			$flag=1;
		}
		if(!empty($_REQUEST['platitor'])){
			if($_REQUEST['platitor']==2)
				$cond .= ' AND ep.platitor_id = ep.expeditor_id';
			else if($_REQUEST['platitor']==3)
				$cond .= ' AND ep.platitor_id = ep.destinatar_id';
			else if($_REQUEST['platitor']==3)
				$cond .= ' AND ep.platitor_id != ep.expeditor_id AND ep.platitor_id != ep.destinatar_id';
			$flag=1;
		}
		if(empty($flag)) $cond = '1=2';

        $page = intval($_REQUEST['page'] ?? 1);
		$limit = intval($_REQUEST['rows'] ?? 20);
		$sidx = trim($this->sanitize($_REQUEST['sidx'] ?? 1));
		$sord = trim($this->sanitize($_REQUEST['sord'] ?? 'asc'));

		$cond .= " and ep.anulata = 0";
		$query = "SELECT COUNT(ced.id) as nr
			from {$this->tables['exp_prelucrate']} ep
			inner join clienti cld on cld.cod_cl = ep.destinatar_id
			LEFT JOIN zones cldz ON cldz.id = cld.zona_id
        	LEFT JOIN centre cldc on cldc.id = cldz.centru_id
			inner join localitati lcd ON lcd.cod_lc = cld.cod_lc
			inner join centre ced ON ced.id = lcd.cod_centru
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
        $query = "SELECT 
			IF(cld.zona_id > 0 and cldc.id > 0, cldc.nume, ced.nume) as destinatar_centru, 
			IF(cld.zona_id > 0 and cldc.id > 0, cldc.id, ced.id) as destinatar_centru_id,
			COUNT(ep.expeditie) as livrari,
        	SUM(ep.greutate) as greutate, SUM(ep.plicuri) as plicuri, SUM(ep.colete) as colete, SUM(ep.paleti) as paleti,

        	SUM(ep.km_preluare) as km_ext,	SUM(IF(ep.km_preluare > 0, 1,0)) AS col_ext,

        	SUM(CASE WHEN ep.moneda IN ('LEI','') THEN ep.valoare_totala_expeditie ELSE 0 END) AS lei,
        	SUM(CASE WHEN ep.moneda='USD' THEN ep.valoare_totala_expeditie ELSE 0 END) AS usd,
        	SUM(CASE WHEN ep.moneda='EUR' THEN ep.valoare_totala_expeditie ELSE 0 END) AS eur,

        	SUM(CASE WHEN ep.moneda IN ('LEI','') THEN ep.valoare_asigurata ELSE 0 END) AS asig_lei,
        	SUM(CASE WHEN ep.moneda='USD' THEN ep.valoare_asigurata ELSE 0 END) AS asig_usd,
        	SUM(CASE WHEN ep.moneda='EUR' THEN ep.valoare_asigurata ELSE 0 END) AS asig_eur

            from {$this->tables['exp_prelucrate']} ep
			inner join clienti cld on cld.cod_cl = ep.destinatar_id
			LEFT JOIN zones cldz ON cldz.id = cld.zona_id
        	LEFT JOIN centre cldc on cldc.id = cldz.centru_id
			inner join localitati lcd ON lcd.cod_lc = cld.cod_lc
			inner join centre ced ON ced.id = lcd.cod_centru
            WHERE {$cond}
            GROUP BY IF(cld.zona_id > 0 and cldc.id > 0, cldc.id, ced.id)
            ORDER BY " . $sidx . " " . $sord . " LIMIT " . $start . " , " . $limit;
            //echo $query;die;
        $total_livrari = 0;
		$total_colete = 0;
		$total_paleti = 0;
		$total_plicuri = 0;
		$total_greutate = 0.00;
		$total_col_ext = 0;
		$total_km_ext = 0.00;
		$total_lei = 0.00;
		$total_usd = 0.00;
		$total_eur = 0.00;
		$total_asig_lei = 0.00;
		$total_asig_usd = 0.00;
		$total_asig_eur = 0.00;

        $sql = $this->db->QFetchRowArray($query);
        if (!empty($sql)) {
            foreach ($sql as $key => $row) {
                $row['moneda'] = 'Lei';
				$responce->rows[$key]['id'] = $row['destinatar_centru_id'];
				$responce->rows[$key]['cell'] = array(strtoupper($row['destinatar_centru']), $row['livrari'], $row['colete'], $row['paleti'], $row['plicuri'], $row['greutate'], $row['col_ext'], $row['km_ext'], $row['lei'], $row['usd'], $row['eur'], $row['asig_lei'], $row['asig_usd'], $row['asig_eur']);

                $total_livrari += $row['livrari'];
				$total_colete += $row['colete'];
				$total_paleti += $row['paleti'];
				$total_plicuri += $row['plicuri'];
				$total_greutate += $row['greutate'];
				$total_col_ext += $row['col_ext'];
				$total_km_ext += $row['km_ext'];
				$total_lei += $row['lei'];
				$total_usd += $row['usd'];
				$total_eur += $row['eur'];
				$total_asig_lei += $row['asig_lei'];
				$total_asig_usd += $row['asig_usd'];
				$total_asig_eur += $row['asig_eur'];

            }
        }
		$responce->page = $page;
        $responce->total = $total_pages;
        $responce->records = $count;

		$responce->userdata['destinatar_centru'] = 'Total: ';
 		$responce->userdata['livrari'] = $total_livrari;
		$responce->userdata['colete'] = $total_colete;
		$responce->userdata['paleti'] = $total_paleti;
		$responce->userdata['plicuri'] = $total_plicuri;
		$responce->userdata['greutate'] = $total_greutate;
		$responce->userdata['col_ext'] =  round($total_col_ext,2);
		$responce->userdata['km_ext'] =  round($total_km_ext,2);
		$responce->userdata['lei'] =  round($total_lei,2);
		$responce->userdata['usd'] =  round($total_usd,2);
		$responce->userdata['eur'] =  round($total_eur,2);
		$responce->userdata['asig_lei'] =  round($total_asig_lei,2);
		$responce->userdata['asig_usd'] =  round($total_asig_usd,2);
		$responce->userdata['asig_eur'] = round($total_asig_eur,2);

        return json_encode($responce);
    }
    function JSON_ListeLivrariDetalii() {
    	$responce = new StdClass();
		$responce->page = 0;
		$responce->total = 0;
		$responce->records = 0;

		$cond="1=1 ";
        $centru_id = intval($_REQUEST['centru'] ?? 0);
		if($centru_id > 0)
			$cond .= " AND IF(cld.zona_id > 0 and cldc.id > 0, cldc.id, ced.id) = {$centru_id}";
		else return json_encode($responce);

      	if(isset($_REQUEST['data_start']) && isset($_REQUEST['data_final'])){
	        $data_start = $this->TransformDate($_REQUEST['data_start']);
	        $data_final = $this->TransformDate($_REQUEST['data_final']);
			$cond .= " AND ep.data_expeditie between '{$data_start}' AND '{$data_final}'";
	    }
		else return json_encode($responce);

		$page = intval($_REQUEST['page'] ?? 1);
		$limit = intval($_REQUEST['rows'] ?? 20);
		$sidx = trim($this->sanitize($_REQUEST['sidx'] ?? 1));
		$sord = trim($this->sanitize($_REQUEST['sord'] ?? 'asc'));

		$cond .= " and ep.anulata = 0";
		//error_log($cond);
		$query = "SELECT COUNT(DISTINCT(ep.destinatar_id)) as nr
            from {$this->tables['exp_prelucrate']} ep
			left join clienti cld on cld.cod_cl = ep.destinatar_id
			LEFT JOIN zones cldz ON cldz.id = cld.zona_id
        	LEFT JOIN centre cldc on cldc.id = cldz.centru_id
			left join localitati lcd ON lcd.cod_lc = cld.cod_lc
			left join centre ced ON ced.id = lcd.cod_centru
            WHERE {$cond}
            ";

        $result = $this->db->QFetchArray($query);
        $count = !empty($result['nr']) ? $result['nr'] : 0;

        if( $count >0 ) {
            $total_pages = ceil($count/$limit); }
        else { $total_pages = 0; }
        if ($page > $total_pages) $page=$total_pages;
        if ($limit<0) $limit = 0;
        $start = $limit*$page - $limit; // do not put $limit*($page - 1)
        if ($start<0) $start = 0;
		$query = "SELECT cld.nume as destinatar, ep.destinatar_id, lcd.nume_lc as destinatar_localitate,
			COUNT(ep.cod_expeditie) as expeditii, SUM(ep.greutate) as greutate, SUM(ep.plicuri) as plicuri,
			SUM(ep.colete) as colete, SUM(ep.paleti) as paleti, SUM(ep.km_livrare + ep.km_preluare) as km
			from {$this->tables['exp_prelucrate']} ep
			left join clienti cld on cld.cod_cl = ep.destinatar_id
			LEFT JOIN zones cldz ON cldz.id = cld.zona_id
        	LEFT JOIN centre cldc on cldc.id = cldz.centru_id
			left join localitati lcd ON lcd.cod_lc = cld.cod_lc
			left join centre ced ON ced.id = lcd.cod_centru
			WHERE {$cond}
			GROUP BY ep.destinatar_id
			ORDER BY " . $sidx . " " . $sord . " LIMIT " . $start . " , " . $limit;
			//echo $query;die;
        $sql = $this->db->QFetchRowArray($query);
        //compun raspunsul
        if (!empty($sql)) {
            foreach ($sql as $key => $row) {
            	if(empty($row['paleti'])) $row['paleti']=0;
                $responce->rows[$key]['id'] = $row['destinatar_id'];
                $responce->rows[$key]['cell'] = array($row['destinatar_localitate'],$row['destinatar'], $row['expeditii'], $row['greutate'],$row['plicuri'],$row['colete'],$row['paleti'],$row['km'],);
            }
        }
        $responce->page = $page;
        $responce->total = $total_pages;
        $responce->records = $count;
        return json_encode($responce);
    }

    function JSON_ListeLivrariDetaliiClient() {
    	$responce = new StdClass();
		$responce->page = 0;
		$responce->total = 0;
		$responce->records = 0;

        $centru_id = intval($_REQUEST['centru'] ?? 0);
        $client = intval($_REQUEST['client'] ?? 0);
		if($client == 0) 
			return json_encode($responce);
        $cond = "ep.destinatar_id = {$client}";

		if($centru_id > 0)
			$cond .= " AND IF(cld.zona_id > 0 and cldc.id > 0, cldc.id, lcd.cod_centru) = {$centru_id}";
		else return json_encode($responce);

      	if(isset($_REQUEST['data_start']) && isset($_REQUEST['data_final'])){
	        $data_start = $this->TransformDate($_REQUEST['data_start']);
	        $data_final = $this->TransformDate($_REQUEST['data_final']);
			$cond .= " AND ep.data_expeditie between '{$data_start}' AND '{$data_final}'";
	    }
		else return json_encode($responce);

		$page = intval($_REQUEST['page'] ?? 1);
		$limit = intval($_REQUEST['rows'] ?? 20);
		$sidx = trim($this->sanitize($_REQUEST['sidx'] ?? 1));
		$sord = trim($this->sanitize($_REQUEST['sord'] ?? 'asc'));

		$cond .= " and ep.anulata = 0";
		$query = "SELECT COUNT(ep.cod_expeditie) as nr
            from {$this->tables['exp_prelucrate']} ep
			left join clienti cle on cle.cod_cl = ep.expeditor_id
			left join localitati lce ON lce.cod_lc = cle.cod_lc
			left join clienti cld on cld.cod_cl = ep.destinatar_id
			LEFT JOIN zones cldz ON cldz.id = cld.zona_id
        	LEFT JOIN centre cldc on cldc.id = cldz.centru_id
			left join localitati lcd ON lcd.cod_lc = cld.cod_lc
            WHERE {$cond}
            ";

        $result = $this->db->QFetchArray($query);
        $count = !empty($result['nr']) ? $result['nr'] : 0;

        if( $count >0 ) {
            $total_pages = ceil($count/$limit); }
        else { $total_pages = 0; }
        if ($page > $total_pages) $page=$total_pages;
        if ($limit<0) $limit = 0;
        $start = $limit*$page - $limit; // do not put $limit*($page - 1)
        if ($start<0) $start = 0;
		$query = "SELECT cle.nume as expeditor, ep.expeditor_id, lce.nume_lc as expeditor_localitate,
			ep.expeditie, ep.greutate, ep.tip_obj, ep.piese, ep.plicuri, ep.colete, ep.paleti, (ep.km_livrare + ep.km_preluare) as km
			from {$this->tables['exp_prelucrate']} ep
			left join clienti cle on cle.cod_cl = ep.expeditor_id
			left join localitati lce ON lce.cod_lc = cle.cod_lc
			left join clienti cld on cld.cod_cl = ep.destinatar_id
			LEFT JOIN zones cldz ON cldz.id = cld.zona_id
        	LEFT JOIN centre cldc on cldc.id = cldz.centru_id
			left join localitati lcd ON lcd.cod_lc = cld.cod_lc
			WHERE {$cond}
			ORDER BY " . $sidx . " " . $sord . " LIMIT " . $start . " , " . $limit;
			//echo $query;die;
        $sql = $this->db->QFetchRowArray($query);
        //compun raspunsul
        if (!empty($sql)) {
            foreach ($sql as $key => $row) {
            	if(empty($row['paleti'])) $row['paleti']=0;
				$responce->rows[$key]['id'] = $row['expeditie'];
                $responce->rows[$key]['cell'] = array($row['expeditie'], $row['expeditor'],$row['expeditor_localitate'], $row['greutate'],$row['plicuri'],$row['colete'],$row['paleti'],$row['km']);
            }
        }
        $responce->page = $page;
        $responce->total = $total_pages;
        $responce->records = $count;
        return json_encode($responce);
    }

    //////////////////////////////
    ////	RULAJ CLIENTI    ////
    /////////////////////////////

	function RulajClienti(){
		$this->vars['title_page'] = 'Rulaj Expeditii Clienti';
		$vars = [];
	  	$cond = '';

        return $this->Parse($this->page_prefix . 'rulaj_clienti.html', $vars);
	}

	function RulajClient(){
		$post=$_POST;
		//rulaj ca expeditor
		$flag=0;
		$cond = "1=1";

        if(isset($_REQUEST['data_start']) && isset($_REQUEST['data_final'])){
            $data_start = $this->TransformDate($_REQUEST['data_start']);
            $data_final = $this->TransformDate($_REQUEST['data_final']);
			$cond .= " AND data_expeditie>='".$data_start."' AND data_expeditie<='".$data_final."'";
            $flag=1;
        }
		if(!empty($post['client'])){
			$cond .= ' AND expeditor_id='.intval($post['client']);
			$flag=1;
		}
		if(empty($flag)) $cond = '1=2';

		$cond .= " and anulata = 0";
		$query = "SELECT
        	SUM(IF(moneda='LEI',1,0)) AS exp_lei,
        	SUM(IF(moneda='USD',1,0)) AS exp_usd,
        	SUM(IF(moneda='EUR',1,0)) AS exp_eur,

        	SUM(CASE WHEN moneda='LEI' THEN valoare_totala_expeditie ELSE 0 END) AS lei,
        	SUM(CASE WHEN moneda='USD' THEN valoare_totala_expeditie ELSE 0 END) AS usd,
        	SUM(CASE WHEN moneda='EUR' THEN valoare_totala_expeditie ELSE 0 END) AS eur,

        	SUM(CASE WHEN moneda='LEI' THEN greutate ELSE 0 END) AS greut_lei,
        	SUM(CASE WHEN moneda='USD' THEN greutate ELSE 0 END) AS greut_usd,
        	SUM(CASE WHEN moneda='EUR' THEN greutate ELSE 0 END) AS greut_eur,

        	SUM(CASE WHEN moneda='LEI' THEN valoare_asigurata ELSE 0 END) AS val_asig_lei,
        	SUM(CASE WHEN moneda='USD' THEN valoare_asigurata ELSE 0 END) AS val_asig_usd,
        	SUM(CASE WHEN moneda='EUR' THEN valoare_asigurata ELSE 0 END) AS val_asig_eur,

        	SUM(CASE WHEN moneda='LEI' THEN val_asig ELSE 0 END) AS asig_lei,
        	SUM(CASE WHEN moneda='USD' THEN val_asig ELSE 0 END) AS asig_usd,
        	SUM(CASE WHEN moneda='EUR' THEN val_asig ELSE 0 END) AS asig_eur

            from {$this->tables['exp_prelucrate']}
            WHERE {$cond}";
        //error_log($query);
        $sql = $this->db->QFetchArray($query);
		$vars=[];
		$vars['expeditii_moneda'] = '';
		$vars['asig_moneda'] = '';
		$vars['total_moneda'] = '';
		if(!empty($sql)){
			$vars['titlu'] = 'Rulaj ca Expeditor';
			$monede = array(1 => 'lei', 2 => 'usd', 3 => 'eur');
			//lei
			foreach($monede as $k=>$r){
				$var = [];
				$var['moneda'] = strtoupper($r);
				$var['total_expeditii'] = $sql['exp_'.$r];
				$var['valoare_totala'] = $sql[$r];
				$var['greutate_totala'] = $sql['greut_'.$r];
				$var['valoare_medie'] = 0.00;
				$var['greutate_medie'] = 0.00;
				if($sql['exp_'.$r] && $sql['exp_'.$r] > 0){
					$var['valoare_medie'] = round($sql[$r]/$sql['exp_'.$r],2);
					$var['greutate_medie'] = round($sql['greut_'.$r]/$sql['exp_'.$r],2);
				}
				$vars['expeditii_moneda'] .= $this->Parse($this->page_prefix . 'rulaj_client_detalii_moneda_exp.html', $var);

				$var = [];
				$var['moneda'] = strtoupper($r);
				$var['valoare_asigurata'] = $sql['val_asig_'.$r];
				$var['val_asig'] = $sql['asig_'.$r];
				$vars['asig_moneda'] .= $this->Parse($this->page_prefix . 'rulaj_client_detalii_moneda_asig.html', $var);

				$total = $sql['asig_'.$r]+$sql[$r];
				$vars['total_moneda'] .='<strong>'.$total.' '.strtoupper($r).'</strong><br/>';

			}
		}
		//print_r($vars);die;
        $result = $this->Parse($this->page_prefix . 'rulaj_client_detalii.html', $vars);
		//rulaj ca platitor
		$flag=0;
		$cond = "1=1";
        if(isset($_REQUEST['data_start']) && isset($_REQUEST['data_final'])){
            $data_start = $this->TransformDate($_REQUEST['data_start']);
            $data_final = $this->TransformDate($_REQUEST['data_final']);
			$cond .= " AND data_expeditie>='".$data_start."' AND data_expeditie<='".$data_final."'";
            $flag=1;
        }
		if(!empty($post['client'])){
			$cond .= " AND platitor_id = " . intval($post['client']);
			$flag=1;
		}
		if(empty($flag)) $cond = '1=2';

		$cond .= " and anulata = 0";
		$query = "SELECT
        	SUM(IF(moneda='LEI',1,0)) AS exp_lei,
        	SUM(IF(moneda='USD',1,0)) AS exp_usd,
        	SUM(IF(moneda='EUR',1,0)) AS exp_eur,

        	SUM(CASE WHEN moneda='LEI' THEN valoare_totala_expeditie ELSE 0 END) AS lei,
        	SUM(CASE WHEN moneda='USD' THEN valoare_totala_expeditie ELSE 0 END) AS usd,
        	SUM(CASE WHEN moneda='EUR' THEN valoare_totala_expeditie ELSE 0 END) AS eur,

        	SUM(CASE WHEN moneda='LEI' THEN greutate ELSE 0 END) AS greut_lei,
        	SUM(CASE WHEN moneda='USD' THEN greutate ELSE 0 END) AS greut_usd,
        	SUM(CASE WHEN moneda='EUR' THEN greutate ELSE 0 END) AS greut_eur,

        	SUM(CASE WHEN moneda='LEI' THEN valoare_asigurata ELSE 0 END) AS val_asig_lei,
        	SUM(CASE WHEN moneda='USD' THEN valoare_asigurata ELSE 0 END) AS val_asig_usd,
        	SUM(CASE WHEN moneda='EUR' THEN valoare_asigurata ELSE 0 END) AS val_asig_eur,

        	SUM(CASE WHEN moneda='LEI' THEN val_asig ELSE 0 END) AS asig_lei,
        	SUM(CASE WHEN moneda='USD' THEN val_asig ELSE 0 END) AS asig_usd,
        	SUM(CASE WHEN moneda='EUR' THEN val_asig ELSE 0 END) AS asig_eur

            from {$this->tables['exp_prelucrate']}
            WHERE {$cond}";
            //echo $query;die;
        $sql = $this->db->QFetchArray($query);
		$vars=[];
		$vars['expeditii_moneda'] = '';
		$vars['asig_moneda'] = '';
		$vars['total_moneda'] = '';
		if(!empty($sql)){
			$vars['titlu'] = 'Rulaj ca Platitor';
			$monede = array(1 => 'lei', 2 => 'usd', 3 => 'eur');
			foreach($monede as $k=>$r){
				$var = [];
				$var['moneda'] = strtoupper($r);
				$var['total_expeditii'] = $sql['exp_'.$r];
				$var['valoare_totala'] = $sql[$r];
				$var['greutate_totala'] = $sql['greut_'.$r];
				$var['valoare_medie'] = 0.00;
				$var['greutate_medie'] = 0.00;
				if($sql['exp_'.$r] && $sql['exp_'.$r] > 0){
					$var['valoare_medie'] = round($sql[$r]/$sql['exp_'.$r],2);
					$var['greutate_medie'] = round($sql['greut_'.$r]/$sql['exp_'.$r],2);
				}
				$vars['expeditii_moneda'] .= $this->Parse($this->page_prefix . 'rulaj_client_detalii_moneda_exp.html', $var);

				$var = [];
				$var['moneda'] = strtoupper($r);
				$var['valoare_asigurata'] = $sql['val_asig_'.$r];
				$var['val_asig'] = $sql['asig_'.$r];
				$vars['asig_moneda'] .= $this->Parse($this->page_prefix . 'rulaj_client_detalii_moneda_asig.html', $var);

				$total = $sql['asig_'.$r]+$sql[$r];
				$vars['total_moneda'] .='<strong>'.$total.' '.strtoupper($r).'</strong><br/>';

			}
		}
		//print_r($vars);die;
        $result .= $this->Parse($this->page_prefix . 'rulaj_client_detalii.html', $vars);

		return $result;
	}

/*/////////////////////////////////////////////////////////////
				 START BORDEROURI
/////////////////////////////////////////////////////////////*/


	function BorderouriClienti() {
        $this->vars['title_page'] = 'Liste Borderouri';
        $vars = [];

        return $this->Parse($this->page_prefix . 'borderouri.html', $vars);
    }

	function JSON_BorderouriClienti() {
		$responce = new StdClass();
        $cond =' 1=1';

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

		$query = "SELECT COUNT(a.id) as nr, cle.nume as expeditor, lce.nume_lc as expeditor_localitate
			FROM {$this->tables['client_borderouri']} a
			INNER JOIN {$this->tables['clienti']} cle ON a.client_id = cle.cod_cl
			left join localitati lce on lce.cod_lc = cle.cod_lc
			WHERE {$cond}";

        $result = $this->db->QFetchArray($query);
        $count = !empty($result['nr']) ? $result['nr'] : 0;

        if( $count >0 ) {$total_pages = ceil($count/$limit); }
        else { $total_pages = 0; }
        if ($page > $total_pages) $page=$total_pages;
        if ($limit<0) $limit = 0;
        $start = $limit*$page - $limit; // do not put $limit*($page - 1)
        if ($start<0) $start = 0;
        $query = "SELECT a.*, cle.nume as expeditor, lce.nume_lc as expeditor_localitate
            FROM {$this->tables['client_borderouri']} a
			INNER JOIN {$this->tables['clienti']} cle ON a.client_id = cle.cod_cl
			left join localitati lce on lce.cod_lc = cle.cod_lc
            WHERE {$cond}
            ORDER BY " . $sidx . " " . $sord . " LIMIT " . $start . " , " . $limit;
        $sql = $this->db->QFetchRowArray($query);
        if (!empty($sql)) {
            foreach ($sql as $key => $row) {
            	$row['optiuni'] = '';
            	if($row['status'] != 'Receptionat')
            	{
            		$row['optiuni'] = '<a href="javascript:;" onclick="ReceptieBorderou('.$row['id'].');" style="text-decoration:none;">Receptie</a>&nbsp;&nbsp;';
            		$row['optiuni'] .= '<a href="javascript:;" onclick="StergereBorderou('.$row['id'].');" style="text-decoration:none;">Stergere</a>';
            	} else {
					$row['optiuni'] .= '<a href="javascript:;" onclick="StergereBorderouReceptionat('.$row['id'].');" style="text-decoration:none;">Stergere</a>';
				}

				$responce->rows[$key]['id'] = $row['id'];
                $responce->rows[$key]['cell'] = array($row['expeditor'],$row['expeditor_localitate'],$row['borderou_id'],$row['expeditii'],$row['data'],$row['status'],$row['operator_nume'],$row['curier_nume'],$row['optiuni']);
            }
        }
		$responce->page = $page;
        $responce->total = $total_pages;
        $responce->records = $count;
        return json_encode($responce);
    }

	function JSON_ListeExpeditiiBorderouri($nr) {
		$responce = new StdClass();
        $cond =' ep.anulata = 0 and ep.borderou_id='.intval($nr);

        $page = intval($_REQUEST['page'] ?? 1);
		$limit = intval($_REQUEST['rows'] ?? 20);
		$sidx = trim($this->sanitize($_REQUEST['sidx'] ?? 1));
		$sord = trim($this->sanitize($_REQUEST['sord'] ?? 'asc'));

		$query = "SELECT COUNT(ep.id) as nr
			FROM {$this->tables['client_expeditii']} ep
			LEFT JOIN clienti cld ON ep.destinatar_cod_cl = cld.cod_cl
			LEFT JOIN localitati lcd on lcd.cod_lc = cld.cod_lc
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
        $query = "SELECT ep.expeditie, SUM(ep.km_ext_prel+ep.km_ext_livr) as km, ep.data_expeditie,
			ep.tip_obj, ep.piese, ep.greutate, ep.km_ext_prel, ep.km_ext_livr, ep.asigurare, ep.ramburs,
			ep.valoare_totala, ep.valoare_tva,
			cld.nume as destinatar, cld.adresa as destinatar_adresa, lcd.nume_lc as destinatar_localitate
            FROM {$this->tables['client_expeditii']} ep
			LEFT JOIN clienti cld ON ep.destinatar_cod_cl = cld.cod_cl
			LEFT JOIN localitati lcd on lcd.cod_lc = cld.cod_lc
            WHERE {$cond}
            GROUP BY id ORDER BY " . $sidx . " " . $sord . " LIMIT " . $start . " , " . $limit;
            //echo $query;die;
        $sql = $this->db->QFetchRowArray($query);
        if (!empty($sql)) {
            foreach ($sql as $key => $row) {
				$responce->rows[$key]['id'] = $row['expeditie'];
                $responce->rows[$key]['cell'] = array($row['expeditie'], strtoupper($row['destinatar']),strtoupper($row['destinatar_localitate']),$row['data_expeditie'],$row['tip_obj'],$row['piese'],$row['greutate'],$row['km_ext_prel']+$row['km_ext_livr'],$row['asigurare'],$row['ramburs'],$row['valoare_totala'],$row['valoare_tva']);
            }
        }
		$responce->page = $page;
        $responce->total = $total_pages;
        $responce->records = $count;
        return json_encode($responce);
    }


	function DetaliiExpeditieClient(){
		$expeditie = intval($_POST['expeditie']);
        if(empty($_POST['expeditie']) || !(ExpeditieDto::isValidCod($expeditie) || ExpeditieDto::isOldSystemAwb($expeditie))) return $this->Error('Invalid ID');

		if(false === ($vars = $this->GetValuesClient($expeditie))) return $this->Error('Expeditie Invalida!!!');

		if($vars['updated_by'] > 0)
			$vars['updated_at'] = $this->CreateDate($vars['updated_at'],'d.m.Y H:i:s');
		else $vars['updated_at'] = '';
		if($vars['printed_by'] > 0)
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
		else  $vars['RETUR_AMB'] = 'NU';
		if(!empty($vars['ret_colet'])) $vars['RETUR_COLET'] = 'DA';
			else  $vars['RETUR_COLET'] = 'NU';
        if(!empty($vars['liv_sediu'])) $vars['liv_sediu'] = 'DA';
		else $vars['liv_sediu'] = 'NU';
		if(!empty($vars['ret_nt'])) $vars['RETUR_NT'] = 'DA';
		else $vars['RETUR_NT'] = 'NU';
	 	if(!empty($vars['ret_doc'])) $vars['RETUR_DOC'] = 'DA';
		else $vars['RETUR_DOC'] = 'NU';
		if(!empty($vars['copen'])) $vars['copen'] = 'DA';
		else $vars['copen'] = 'NU';
		if(!empty($vars['sms'])) $vars['SMS'] = 'DA';
		else $vars['sms'] = 'NU';

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

		$vars['class_edit'] = 'style="display: none;"';
		$vars['class_print'] = 'style="display: none;"';

		if(empty($vars['paleti'])) $vars['paleti'] = 0;

        return $this->Parse('client_detalii_expeditie.html', array_map('htmlspecialchars', $vars));
	}

	function BorderouriReceptionateStergere($borderou_id){
		if(empty($borderou_id))
			return false;

		$query = "SELECT expeditie FROM {$this->tables['client_expeditii']} WHERE borderou_id={$borderou_id} and anulata = 0 and stearsa = 0 group by expeditie";
		$sql = $this->db->QFetchRowArray($query);

		if(empty($sql))
			return false;

		foreach ($sql as $key => $row) {
			$_POST['data'] = [];
			$_POST['data'][$key]['name'] = 'NR_EXP';
			$_POST['data'][$key]['value'] = $row['expeditie'];
			$m = $this->StergereExpeditie();
			if(str_starts_with($m, '0'))
				error_log($m);
		}


		$this->db->Query("DELETE FROM {$this->tables['client_borderouri']} WHERE id={$borderou_id}");
		$this->db->QueryUpdate($this->tables['client_expeditii'], ['anulata' => 1, 'deleted_at' => date('Y-m-d H:i:s'), 'deleted_by' => $this->user_id], "borderou_id = ".$borderou_id);
		return true;
	}


	function BorderouriStergere($borderou_id){
		if(empty($borderou_id)) return false;
		$this->db->Query("DELETE FROM {$this->tables['client_borderouri']} WHERE id={$borderou_id}");
		$this->db->QueryUpdate($this->tables['client_expeditii'], ['anulata' => 1, 'deleted_at' => date('Y-m-d H:i:s'), 'deleted_by' => $this->user_id], "borderou_id={$borderou_id}");
		return true;
	}

	function BorderouriReceptie($borderou_id, $src = 1){
		if(empty($borderou_id))
			return "Borderou not found : {$borderou_id}";

		$query = "SELECT id FROM {$this->tables['client_borderouri']} WHERE id = {$borderou_id} and status like 'Receptionat'";
        $sql = $this->db->QFetchArray($query);
		if(!empty($sql) && is_array($sql)) return "Borderoul a fost importat deja : {$borderou_id}";

		if(false === ($sql = $this->GetValuesClient(0, $borderou_id))) return $this->Error("Borderou fara expeditii! {$borderou_id}");

		foreach ($sql as $key => $row) {
			$qs = "SELECT expeditie, anulata from exp_prelucrate WHERE expeditie = {$row['expeditie']}";
			$found = $this->db->QFetchArray($qs);
			if(!empty($found) && !empty($found['expeditie'])) {
				//error_log("BorderouriReceptie : found : {$found['expeditie']}");
				if($found['anulata'] > 0)
					error_log("BorderouriReceptie : found anulata : {$found['expeditie']}");
				else
					continue;
			}

			$id_ist = $this->insertIstExp(null, 1);

			$vi = [];
			$vi['created_at'] = $row['created_at'];
			$vi['created_by'] = $row['user_id'];
			$vi['updated_at'] = $row['updated_at'];
			$vi['updated_by'] = $row['updated_by'];
			$vi['deleted_at'] = $row['deleted_at'];
			$vi['deleted_by'] = $row['deleted_by'];
			$vi['printed_at'] = $row['printed_at'];
			$vi['printed_by'] = $row['printed_by'];
			$vi['swapped'] = $row['swapped'] ?? 0;
			$vi['borderou_id'] = $borderou_id;

			$vi['expeditie'] = $row['expeditie'];
			$vi['referire'] = 0;
			$vi['tip_exp'] = 0;
			$vi['data_expeditie'] = $vi['data_operatie'] = date("Y-m-d");
			$vi['operatiune'] = "Colectata";
			$vi['data_op'] = $vi['data'] = date('Y-m-d H:i:s');
			$vi['greutate'] = $row['greutate'];
			$vi['greutate_vol'] = $row['greutate_vol'];
			$vi['volum'] = $row['volum'];
			$vi['km_preluare'] = $row['expeditor_localitate_km'];
			$vi['km_livrare'] = $row['destinatar_localitate_km'];
			$vi['valoare_asigurata'] = $row['asigurare'];
			$vi['ramburs'] = $row['ramburs'];
			$vi['tip_plata'] = $row['tip_plata'];
			$vi['mod_plata'] = $row['mod_plata'];
			$vi['ret_nt'] = $row['ret_nt'];
			$vi['ret_doc'] = $row['ret_doc'];
			$vi['liv_samb'] = $row['liv_sambata'];
			$vi['liv_sed'] = $row['liv_sediu'];
			$vi['ret_amb'] = $row['ret_amb'];
			$vi['ret_colet'] = $row['ret_colet'];
			$vi['sms'] = $row['sms'];
			$vi['copen'] = $row['copen'];
			$vi['moneda'] = ExpeditieDto::MONEDA[$row['moneda']] ?? "LEI";
			$vi['observatii'] = $row['observatii'];
			$vi['detalii_doc'] = $row['detalii_doc'];

			$vi['expeditor_id'] = $row['expeditor_id'];
			$vi['expeditor_localitate_id'] = $row['expeditor_localitate_id'];
			$vi['expeditor_contact'] = trim($row['expeditor_contact']);
			$vi['expeditor_telefon'] = trim($row['expeditor_telefon']);
			$vi['destinatar_id'] = $row['destinatar_id'];
			$vi['destinatar_localitate_id'] = $row['destinatar_localitate_id'];
			$vi['destinatar_contact'] = $row['destinatar_contact'];
			$vi['destinatar_telefon'] = $row['destinatar_telefon'];
			$vi['platitor_id'] = $row['platitor_id'];
			$vi['operator_id'] = $this->user_id;
			$vi['src'] = $src;

			$vi['tip_obj'] = $row['tip_obj'];
			$vi['plicuri'] = 0;
			$vi['colete'] = 0;
			$vi['paleti'] = 0;
		    if($vi['tip_obj'] == 3) {
				$vi['paleti'] = $vi['piese'] = 1;
			}
			else if($vi['tip_obj'] == 1){
				$vi['plicuri'] = $vi['piese'] = 1;
			}
			else {
				$vi['piese'] = $vi['colete'] = !empty($row['piese']) ? $row['piese'] : 1;
			}

			$vi['tip_tarif'] = $row['tip_tarif'];
			$val = $this->Get_ValoareExpeditie($vi);
			unset($vi['tip_tarif']);

			$vi['val_greutate'] = $val['tGreutate'];
			$vi['val_km'] = $val['tKm'];
			$vi['val_asig'] = $val['tAsigurare'] + $val['tRamburs'];
			$vi['procent_asigurare'] = $val['procAsigurare'];
			$vi['ramburs_procent'] = $val['procRamburs'];

			$vi['valoare_expeditie'] = $val['tExpeditie'];
			$vi['valoare_totala_expeditie'] = round($val['tExpeditie'] + $val['tGreutate'] + $val['tKm'] + $val['tAsigurare'] + $val['tRamburs'], 2);
			$vi['tva'] = round($vi['valoare_totala_expeditie'] * $this->procTva / 100 , 2);
			$vi['procTva'] = $this->procTva;

			$cod_expeditie = $this->db->QueryInsert($this->tables['exp_prelucrate'], $vi);
			//update istoric
			if($id_ist !== false)
				$this->db->QueryUpdate($this->tables['ist_exp'], ['cod_exp' => $cod_expeditie], "cod_ist = ".$id_ist);
		}

		$vu=[];
		$vu['curier_nume'] = $_POST['curier_nume'] ?? "SOFT CLIENT (BUCURESTI)";
		$vu['curier'] = $_POST['curier'] ?? 414;
		$vu['operator_nume'] = $this->user_nume ?? "";
		$vu['operator'] = $this->user_id;
		$vu['status'] = 'Receptionat';
		$vu['data_receptie'] =date("Y-m-d H:i:s");
		$this->db->QueryUpdate($this->tables['client_borderouri'], $vu, "id = {$borderou_id}");

		return 'OK';
	}

/*/////////////////////////////////////////////////////////////
				 END BORDEROURI
/////////////////////////////////////////////////////////////*/
	function ExportRestanteExpeditii() {
		$data = date('d/m/Y');
 		$societate = 'Dragon Star Curier';
        $document = 'Lista Expeditii';

        $spreadsheet = new Spreadsheet();
        $spreadsheet->getProperties()->setCreator($societate)
            ->setLastModifiedBy($societate)
            ->setTitle($document)
            ->setSubject($document)
            ->setDescription($document)
            ->setKeywords($document)
            ->setCategory($document);
        $spreadsheet->getDefaultStyle()->getFont()->setName('Arial');
        $spreadsheet->getDefaultStyle()->getFont()->setSize(11);
		$worksheet = $spreadsheet->getActiveSheet();

		foreach(range('A','O') as $v) {
			$worksheet->getStyle($v.'5')->getFont()->setBold(true);
			$worksheet->getStyle($v.'5')->getFont()->setSize(13);
			$worksheet->getColumnDimension($v)->setWidth(30);
		}

		$worksheet->setCellValue('A5','Operatiune');
        $worksheet->setCellValue('B5','Data');
        $worksheet->setCellValue('C5','Expeditie');
        $worksheet->setCellValue('D5','Expeditor');
        $worksheet->setCellValue('E5','Destinatar');
		$worksheet->setCellValue('F5','Val. Incasata');
		$worksheet->setCellValue('G5','Tip Expeditie');
		$worksheet->setCellValue('H5','Greutate');
		$worksheet->setCellValue('I5','Km. colectare');
		$worksheet->setCellValue('J5','Km. livrare');
		$worksheet->setCellValue('K5','Val. Asig');
		$worksheet->setCellValue('L5','Centru exp.');
		$worksheet->setCellValue('M5','Centru dest.');
		$worksheet->setCellValue('N5','Curier');
		$worksheet->setCellValue('O5','Centru platitor');

		$rand=6;
		$total_valoare = $total_expeditii=0;
        $cond = '1=2';

		if(isset($_REQUEST['data_start']) && isset($_REQUEST['data_final'])){
	        $data_start = $this->TransformDate($_REQUEST['data_start']);
	        $data_final = $this->TransformDate($_REQUEST['data_final']);
			$cond = " ep.data_expeditie>='".$data_start."' AND ep.data_expeditie<='".$data_final."'";
	    }

		$centru_id = intval($_REQUEST['centru'] ?? 0);
		if($centru_id > 0){
			$cond .= " AND IF(cle.zona_id > 0 and clec.id > 0, clec.id, cee.id) = {$centru_id} AND IF(clp.zona_id > 0 and clpc.id > 0, clpc.id, cep.id) = {$centru_id}";
		}

		$query = "SELECT ep.expeditie, ep.data_expeditie, ep.tip_exp,
			ep.expeditor_id, ep.destinatar_id, ep.platitor_id, cle.nume as expeditor, cld.nume as destinatar,
			lce.nume_lc as expeditor_localitate, lcd.nume_lc as destinatar_localitate,
      		IF(cle.zona_id > 0 and clec.id > 0, clec.nume, cee.nume) as expeditor_centru, 
        	IF(cle.zona_id > 0 and clec.id > 0, clec.label, cee.label) as expeditor_centru_cod,
			IF(cld.zona_id > 0 and cldc.id > 0, cldc.nume, ced.nume) as destinatar_centru, 
			IF(cld.zona_id > 0 and cldc.id > 0, cldc.label, ced.label) as destinatar_centru_cod,
			IF(clp.zona_id > 0 and clpc.id > 0, clpc.nume, cep.nume) as platitor_centru, 
			IF(clp.zona_id > 0 and clpc.id > 0, clpc.label, cep.label) as platitor_centru_cod,
			agp.nume_ag as curier_preluare, agl.nume_ag as curier_livrare, ep.km_preluare, ep.km_livare,
			ep.greutate, ep.valoare_asigurata, ep.ramburs,
			ep.valoare_totala_expeditie, ep.tva
			from {$this->tables['exp_prelucrate']} ep
			left join clienti cle on cle.cod_cl = ep.expeditor_id
			left join clienti cld on cld.cod_cl = ep.destinatar_id
			left join clienti clp on clp.cod_cl = ep.platitor_id
			LEFT JOIN zones clez ON clez.id = cle.zona_id
        	LEFT JOIN centre clec on clec.id = clez.centru_id
			LEFT JOIN zones cldz ON cldz.id = cld.zona_id
        	LEFT JOIN centre cldc on cldc.id = cldz.centru_id
			LEFT JOIN zones clpz ON clpz.id = clp.zona_id
        	LEFT JOIN centre clpc on clpc.id = clpz.centru_id
			left join localitati lce ON lce.cod_lc = cle.cod_lc
			left join localitati lcd ON lcd.cod_lc = cld.cod_lc
			left join localitati lcp ON lcp.cod_lc = clp.cod_lc
			left join centre cee ON cee.id = lce.cod_centru
			left join centre ced ON ced.id = lcd.cod_centru
			left join centre cep ON cep.id = lcp.cod_centru
			left join agenti agp ON agp.cod_ag = ep.curier_preluare_id
			left join agenti agl ON agl.cod_ag = ep.curier_livrare_id
			WHERE {$cond} and ep.expeditor_id = ep.platitor_id AND ep.tip_exp != 4
			AND ep.mod_plata = 0 AND ep.idfact = 0 and ep.anulata = 0
			ORDER BY ep.data_expeditie ASC";
        $sql = $this->db->QFetchRowArray($query);
        if (!empty($sql)) {

            foreach ($sql as $key => $row) {
                $row['operatiune'] = 'Colectare';
                $row['data_expeditie'] = $this->CreateDate($row['data_expeditie']);
				$row['categorie'] = '';
				$row['curier'] = '';

				if($row['tip_exp'] == 1 || $row['tip_exp'] == 2 || $row['tip_exp'] == 5 || $row['tip_exp'] == 3){
					$row['categorie'] = (ExpeditieDto::TIP_EXP[$row['tip_exp'] ?? 0] ?? "unknown").' la '.$row['referire'];
				}
				if($row['expeditor_id'] == $row['platitor_id']) $row['curier'] = $row['curier_preluare'];
				else if($row['destinatar_id'] == $row['platitor_id']) $row['curier'] = $row['curier_livrare'];

				$row['incasata'] = $row['valoare_totala_expeditie'] + $row['tva'];
				$total_valoare = $total_valoare + $row['incasata'];

				$worksheet->setCellValue('A'.($rand+$key),$row['operatiune']);
		        $worksheet->setCellValue('B'.($rand+$key),$row['data_expeditie']);
		        $worksheet->setCellValue('C'.($rand+$key),$row['expeditie']);
		        $worksheet->setCellValue('D'.($rand+$key),$row['expeditor'].'('.$row['expeditor_localitate'].')');
		        $worksheet->setCellValue('E'.($rand+$key),$row['destinatar'].'('.$row['destinatar_localitate'].')');
				$worksheet->setCellValue('F'.($rand+$key),$row['incasata']);
				$worksheet->setCellValue('G'.($rand+$key),$row['categorie']);
				$worksheet->setCellValue('H'.($rand+$key),$row['greutate']);
				$worksheet->setCellValue('I'.($rand+$key),$row['km_preluare']);
				$worksheet->setCellValue('J'.($rand+$key),$row['km_livrare']);
				$worksheet->setCellValue('K'.($rand+$key),$row['valoare_asigurata']);
				$worksheet->setCellValue('L'.($rand+$key),$row['expeditor_centru']);
				$worksheet->setCellValue('M'.($rand+$key),$row['destinatar_centru']);
				$worksheet->setCellValue('N'.($rand+$key),$row['curier']);
				$worksheet->setCellValue('O'.($rand+$key),$row['platitor_centru']);
				$total_expeditii++;
            }
			$rand=$rand+$key;

        }

        $cond = '1=2';
		if(isset($_REQUEST['data_start']) && isset($_REQUEST['data_final'])){
	        $data_start = $this->TransformDate($_REQUEST['data_start']);
	        $data_final = $this->TransformDate($_REQUEST['data_final']);
			$cond = "( (ep.data_expeditie>='".$data_start."' AND ep.data_expeditie<='".$data_final."' )";
			$cond .= " OR ( ep.data_operatie>='".$data_start."' AND ep.data_operatie<='".$data_final."') AND ep.mod_plata=0 )";
	    }

		$centru_id = intval($_REQUEST['centru'] ?? 0);
		if($centru_id > 0){
			$cond .= " AND IF(cld.zona_id > 0 and cldc.id > 0, cldc.id, ced.id) = {$centru_id} AND IF(clp.zona_id > 0 and clpc.id > 0, clpc.id, cep.id) = {$centru_id}";
		}

		$cond .= " AND ep.tip_exp != 4 AND ep.mod_plata = 0 AND ep.idfact = 0 and ep.anulata = 0";

		$query = "SELECT ep.cod_expeditie, ep.expeditie, ep.data_operatie,
			cle.nume as expeditor, cld.nume as destinatar,
			lce.nume_lc as expeditor_localitate, lcd.nume_lc as destinatar_localitate,
			IF(cle.zona_id > 0 and clec.id > 0, clec.nume, cee.nume) as expeditor_centru, 
        	IF(cle.zona_id > 0 and clec.id > 0, clec.label, cee.label) as expeditor_centru_cod,
			IF(cld.zona_id > 0 and cldc.id > 0, cldc.nume, ced.nume) as destinatar_centru, 
			IF(cld.zona_id > 0 and cldc.id > 0, cldc.label, ced.label) as destinatar_centru_cod,
			IF(clp.zona_id > 0 and clpc.id > 0, clpc.nume, cep.nume) as platitor_centru, 
			IF(clp.zona_id > 0 and clpc.id > 0, clpc.label, cep.label) as platitor_centru_cod,
			agp.nume_ag as curier_preluare, agl.nume_ag as curier_livrare,
			ep.valoare_totala_expeditie, ep.tva, ep.valoare_asigurata,
			ep.tip_exp,ep.referire,ep.greutate,ep.km_preluare,ep.km_livrare,
			ep.expeditor_id, ep.platitor_id, ep.destinatar_id
			from {$this->tables['exp_prelucrate']} ep
			left join clienti cle on cle.cod_cl = ep.expeditor_id
			left join clienti cld on cld.cod_cl = ep.destinatar_id
			left join clienti clp on clp.cod_cl = ep.platitor_id
			LEFT JOIN zones clez ON clez.id = cle.zona_id
        	LEFT JOIN centre clec on clec.id = clez.centru_id
			LEFT JOIN zones cldz ON cldz.id = cld.zona_id
        	LEFT JOIN centre cldc on cldc.id = cldz.centru_id
			LEFT JOIN zones clpz ON clpz.id = clp.zona_id
        	LEFT JOIN centre clpc on clpc.id = clpz.centru_id
			left join localitati lce ON lce.cod_lc = cle.cod_lc
			left join localitati lcd ON lcd.cod_lc = cld.cod_lc
			left join localitati lcp ON lcp.cod_lc = clp.cod_lc
			left join centre cee ON cee.id = lce.cod_centru
			left join centre ced ON ced.id = lcd.cod_centru
			left join centre cep ON cep.id = lcp.cod_centru
			left join agenti agp ON agp.cod_ag = ep.curier_preluare_id
			left join agenti agl ON agl.cod_ag = ep.curier_livrare_id
			WHERE {$cond} and ep.destinatar_id = ep.platitor_id
			ORDER BY ep.data_op";

        $sql = $this->db->QFetchRowArray($query);
        if (!empty($sql)) {
        	$rand++;
            foreach ($sql as $key => $row) {


				$row['operatiune'] = 'Livrare';
                $row['data_operatie'] = $this->CreateDate($row['data_operatie']);
               	$row['categorie'] = '';
               	$row['curier'] = '';

				if($row['tip_exp'] == 1 || $row['tip_exp'] == 2 || $row['tip_exp'] == 3 || $row['tip_exp'] == 5){
					$row['categorie'] = (ExpeditieDto::TIP_EXP[$row['tip_exp'] ?? 0] ?? "unknown").' la '.$row['referire'];
				}
				if($row['expeditor_id'] == $row['platitor_id']) $row['curier'] = $row['curier_preluare'];
				else if($row['destinatar_id'] == $row['platitor_id']) $row['curier'] = $row['curier_livrare'];

				$row['incasata'] = $row['valoare_totala_expeditie']+$row['tva'];
				$total_valoare = $total_valoare + $row['incasata'];

				$worksheet->setCellValue('A'.($rand+$key),$row['operatiune']);
		        $worksheet->setCellValue('B'.($rand+$key),$row['data_operatie']);
		        $worksheet->setCellValue('C'.($rand+$key),$row['expeditie']);
		        $worksheet->setCellValueExplicit('D'.($rand+$key),$row['expeditor'].'('.$row['expeditor_localitate'].')', \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
		        $worksheet->setCellValueExplicit('E'.($rand+$key),$row['destinatar'].'('.$row['destinatar_localitate'].')', \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
				$worksheet->setCellValue('F'.($rand+$key),$row['incasata']);
				$worksheet->setCellValue('G'.($rand+$key),$row['categorie']);
				$worksheet->setCellValue('H'.($rand+$key),$row['greutate']);
				$worksheet->setCellValue('I'.($rand+$key),$row['km_preluare']);
				$worksheet->setCellValue('J'.($rand+$key),$row['km_livrare']);
				$worksheet->setCellValue('K'.($rand+$key),$row['valoare_asigurata']);
				$worksheet->setCellValue('L'.($rand+$key),$row['expeditor_centru']);
				$worksheet->setCellValue('M'.($rand+$key),$row['destinatar_centru']);
				$worksheet->setCellValueExplicit('N'.($rand+$key),$row['curier'], \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
				$worksheet->setCellValue('O'.($rand+$key),$row['platitor_centru']);
				$total_expeditii++;
            }
        }
		$rand = $total_expeditii+6;
		$worksheet->setCellValue('A'.($rand),'Total Expeditii: '.$total_expeditii);
        $worksheet->setCellValue('C'.($rand),'Valoare: '.$total_valoare);

        $data = date('d_m_Y');
        $filename = 'Lista_Expeditii_'.$data.'.xlsx';

        $this->download_send_headers_xls($filename);
		$writer = new Xlsx($spreadsheet);
		$writer->save('php://output');
		die;
    }

	 function ExportUrmarireExpeditii() {
		ini_set('memory_limit', '1228M');
		$data = date('d/m/Y');
 		$societate = 'Dragon Star Curier';
        $document = 'Lista Expeditii';

        $spreadsheet = new Spreadsheet();
		$worksheet = $spreadsheet->getActiveSheet();

		$spreadsheet->getProperties()->setCreator($societate)
			->setLastModifiedBy($societate)
			->setTitle($document)
			->setSubject($document)
			->setDescription($document)
			->setKeywords($document)
			->setCategory($document);
		$spreadsheet->getDefaultStyle()->getFont()->setName('Arial');
		$spreadsheet->getDefaultStyle()->getFont()->setSize(11);

		foreach(range('A','L') as $v){
			$worksheet->getColumnDimension($v)->setAutoSize(true);
		}
		foreach(range('A','L') as $v){
			$worksheet->getStyle($v.'1')->getFont()->setBold(true);
			$worksheet->getStyle($v.'1')->getFont()->setSize(13);
		}


		$worksheet->setCellValue('A1','Data Preluare');
        $worksheet->setCellValue('B1','Expeditie');
		$worksheet->setCellValue('C1','Tip exp.');
		$worksheet->setCellValue('D1','Tip plata');
        $worksheet->setCellValue('E1','Expeditor');
        $worksheet->setCellValue('F1','Centru Expeditor');
        $worksheet->setCellValue('G1','Destinatar');
		$worksheet->setCellValue('H1','Centru Destinatar');
		$worksheet->setCellValue('I1','Status');
		$worksheet->setCellValue('J1','Data op');
		$worksheet->setCellValue('K1','Last ckp');
		$worksheet->setCellValue('L1','Data ckp');
		$worksheet->setCellValue('M1','Primitor');
		$worksheet->setCellValue('N1','Nt Scan');

		$rand=2;
		$total_expeditii = 0;
        $cond ="ep.anulata = 0 ";
        $flag=0;
        if(isset($_REQUEST['data_start']) && isset($_REQUEST['data_final'])){
            $data_start = $this->TransformDate($_REQUEST['data_start']);
            $data_final = $this->TransformDate($_REQUEST['data_final']);
            $cond .= " AND ep.data_expeditie between '{$data_start}' AND '{$data_final}'";
            $flag=1;
        }

        if(isset($_REQUEST['filtru']) && !empty($_REQUEST['search']) ){
            if($_REQUEST['filtru']==1){
                $cond .= " AND ep.expeditie=".intval($_REQUEST['search']);
				$flag=1;
            }else if($_REQUEST['filtru']==2){
                $cond .= " AND cle.nume = ".$this->db->escapeString(strtoupper(urldecode(Backend::sSanitizeCleanEdges($_REQUEST['search'] ?? ''))));
				$flag=1;
			}else if($_REQUEST['filtru']==3){
                $cond .= " AND cld.nume = ".$this->db->escapeString(strtoupper(urldecode(Backend::sSanitizeCleanEdges($_REQUEST['search'] ?? ''))));
				$flag=1;
			}else if($_REQUEST['filtru']==4){
                $cond .= " AND IF(cle.zona_id > 0 and clec.id > 0, clec.id, cee.id) = ".intval($this->sanitize($_REQUEST['search'] ?? ''));
				$flag=1;
			}else if($_REQUEST['filtru']==5){
                $cond .= " AND IF(cld.zona_id > 0 and cldc.id > 0, cldc.id, ced.id) = ".intval($this->sanitize($_REQUEST['search'] ?? ''));
				$flag=1;
            }
		}
		if(!empty($_POST['expeditii'])){
			$post_expeditii = parent::ValidareExpeditiiCurata($_POST['expeditii']);
			if(!empty($post_expeditii)) {
				$cond .= " AND ep.expeditie in (".$post_expeditii.") ";
				$flag=1;
			}
		}
        if(empty($flag)) $cond = "1=2";//"data_expeditie>'2011-07-01' AND data_expeditie<'2011-07-05'";

		$ntScan = "";
		//start generare conditie
        $searchOn = (isset($_REQUEST['_search']))?$this->Strip($_REQUEST['_search']):'false';
        if ($searchOn == 'true') {
            $searchstr = $this->Strip($_REQUEST['filters']);
			$searchstrArr = json_decode($searchstr, true);
			if (!empty($searchstrArr) && is_array($searchstrArr)){
				foreach ($searchstrArr['rules'] as $key=>$val){
					if($val['field'] == 'folder'){
						unset($searchstrArr['rules'][$key]);
						if($val['data'] == 0)
							$ntScan = " and ec.folder is null ";
						else
							$ntScan = " and ec.folder is not null ";
					}
				}
				$searchstr = json_encode($searchstrArr);
				$cond .= $this->constructWhere($searchstr);
			}
		}
		$cond = preg_replace("/ced.nume/i", "IF(cld.zona_id > 0 and cldc.id > 0, cldc.nume, ced.nume)", $cond);

		$query = "SELECT ep.expeditie, ep.data_expeditie,
			CASE
				WHEN ep.tip_exp=0 THEN 'Initiala'
				WHEN ep.tip_exp=1 THEN 'Retur NT'
				WHEN ep.tip_exp=2 THEN 'Retur Doc'
				WHEN ep.tip_exp=3 THEN 'Ramburs'
				WHEN ep.tip_exp=4 THEN 'Interna'
				WHEN ep.tip_exp=5 THEN 'Returnare'
				WHEN ep.tip_exp=6 THEN 'Retur ambalaj'
				ELSE 'unknown'
        	END as tip_exp,
			CASE
				WHEN ep.tip_plata=0 THEN 'cash'
				WHEN ep.tip_plata=1 THEN 'bo'
				WHEN ep.tip_plata=2 THEN 'cec'
				WHEN ep.tip_plata=3 THEN 'cont'
				ELSE 'unknown'
        	END as tip_plata,
			cle.nume as expeditor, lce.nume_lc as expeditor_localitate,
			cld.nume as destinatar, lcd.nume_lc as destinatar_localitate,
			IF(cle.zona_id > 0 and clec.id > 0, clec.nume, cee.nume) as expeditor_centru, 
        	IF(cle.zona_id > 0 and clec.id > 0, clec.label, cee.label) as expeditor_centru_cod,
			IF(cld.zona_id > 0 and cldc.id > 0, cldc.nume, ced.nume) as destinatar_centru, 
			IF(cld.zona_id > 0 and cldc.id > 0, cldc.label, ced.label) as destinatar_centru_cod,
			ep.operatiune, ep.data_op, ep.primitor, ep.liv_samb,
			ec.folder , IF(ec.folder IS NULL,0,1) AS nt_scan, ckp.abbr as last_ckp, scckp.last_ckp_data
			from {$this->tables['exp_prelucrate']} ep
			left join clienti cle on cle.cod_cl = ep.expeditor_id
			left join clienti cld on cld.cod_cl = ep.destinatar_id
			left join zones clez ON clez.id = cle.zona_id
			left join centre clec on clec.id = clez.centru_id
			left join zones cldz ON cldz.id = cld.zona_id
			left join centre cldc on cldc.id = cldz.centru_id
			left join localitati lce ON lce.cod_lc = cle.cod_lc
			left join localitati lcd ON lcd.cod_lc = cld.cod_lc
			left join centre cee ON cee.id = lce.cod_centru
			left join centre ced ON ced.id = lcd.cod_centru
			left join ( select ecc.expeditie, ecc.folder, ecc.data
                    from exp_confirmari ecc
                    where ecc.id = (select MAX(ect.id) from exp_confirmari ect where ect.expeditie = ecc.expeditie)
            ) as ec on ec.expeditie = ep.expeditie
			left join ( select sc.expeditie, sc.data as last_ckp_data, sc.tip
                    from scanari_coduri sc
                    where sc.is_awb = 1 
					and sc.data = (select MAX(scc.data) from scanari_coduri scc where scc.expeditie = sc.expeditie and scc.is_awb = 1)
            ) as scckp on scckp.expeditie = ep.expeditie
			left join checkpoints ckp on ckp.id = scckp.tip
			WHERE {$cond} {$ntScan}
			GROUP BY ep.expeditie";

        $sql = $this->db->QFetchRowArray($query);
        if (!empty($sql)) {
            foreach ($sql as $key => $row) {
                $da = new DateTime($row['data_expeditie']);
				$worksheet->setCellValue('A'.($key + $rand),\PhpOffice\PhpSpreadsheet\Shared\Date::PHPToExcel($da));
				$worksheet->getStyle('A'.($key + $rand))->getNumberFormat()->setFormatCode("dd.mm.yyyy");

		        $worksheet->setCellValue('B'.($rand+$key),$row['expeditie']);
				$worksheet->setCellValue('C'.($rand+$key),$row['tip_exp']);
				$worksheet->setCellValue('D'.($rand+$key),$row['tip_plata']);
		        $worksheet->setCellValueExplicit('E'.($rand+$key),$row['expeditor'].'('.$row['expeditor_localitate'].')', \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
		        $worksheet->setCellValue('F'.($rand+$key),$row['expeditor_centru']);
		        $worksheet->setCellValueExplicit('G'.($rand+$key),$row['destinatar'].'('.$row['destinatar_localitate'].')', \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
				$worksheet->setCellValue('H'.($rand+$key),$row['destinatar_centru']);
                $worksheet->setCellValue('I'.($rand+$key),$row['operatiune']);

                if($row['data_op'] == '0000-00-00'){
                    $worksheet->setCellValue('J'.($rand+$key),null);
                } else {
					$dop = new DateTime($row['data_op']);
					$worksheet->setCellValue('J'.($key + $rand),\PhpOffice\PhpSpreadsheet\Shared\Date::PHPToExcel($dop));
					$worksheet->getStyle('J'.($key + $rand))->getNumberFormat()->setFormatCode("dd.mm.yyyy");
                }

				$worksheet->setCellValue('K'.($rand+$key),strtoupper($row['last_ckp']));
				if($row['last_ckp_data'] == '0000-00-00 00:00:00'){
                    $worksheet->setCellValue('L'.($rand+$key),null);
                } else {
					$dop = new DateTime($row['last_ckp_data']);
					$worksheet->setCellValue('L'.($key + $rand),\PhpOffice\PhpSpreadsheet\Shared\Date::PHPToExcel($dop));
					$worksheet->getStyle('L'.($key + $rand))->getNumberFormat()->setFormatCode("dd.mm.yyyy hh:mm");
                }

				$worksheet->setCellValue('M'.($rand+$key),$row['primitor']);
				$worksheet->setCellValue('N'.($rand+$key),$row['nt_scan']);
				$total_expeditii++;
            }
			$rand=$rand+$key;
        }

        $data = date('d_m_Y');
        $filename = 'Urmarire_Expeditii_'.$data.'.xlsx';

        $this->download_send_headers_xls($filename);
		$writer = new Xlsx($spreadsheet);
		$writer->save('php://output');
		die;
    }
/*/////////////////////////////////////////////////////////////
				 START
/////////////////////////////////////////////////////////////*/

	//istoric scanari
	function vIstoricScanari() {
        $this->vars['title_page'] = 'Istoric Scanari';
        $vars = [];

		$vars['checkpoints'] = $this->ComboCheckPoints();
		$vars['data_start'] = date('d.m.Y').' 00:00';
		$vars['data_final'] = date('d.m.Y').' 23:59';

        return $this->Parse($this->page_prefix . 'v_istoric_scanari.html', $vars);
    }

    function vJSON_IstoricScanari() {
 		$responce = new StdClass();
		$categorie = intval($_REQUEST['categorie'] ?? 0);
		if($categorie==1) {$cond = "1=1";}
		else if($categorie==2) $cond = ' ep.tip_obj = 1 ';
		else if($categorie==3) $cond = ' ep.tip_obj in (2,3) ';
		else if($categorie==4) $cond = ' ep.tip_obj = 3 ';
		else return json_encode($responce);

		$today = date('Y-m-d');
		$data_start = $today.' 00:00:00';
	    $data_final = $today.' 23:59:59';

		if(isset($_REQUEST['data_start']) && isset($_REQUEST['data_final'])){
			try {
					$data_start = DateTime::createFromFormat('d.m.Y H:i', $_REQUEST['data_start']);
					$data_final = DateTime::createFromFormat('d.m.Y H:i', $_REQUEST['data_final']);
					if($data_start && $data_final && intval($data_start->diff($data_final, true)->format('%a')) > 93) {
						$data_start = $today.' 00:00:00';
	    				$data_final = $today.' 23:59:59';
					}
					else {
						$data_start = $data_start->format('Y-m-d H:i:s');
						$data_final = $data_final->format('Y-m-d H:i:s');
					}
			}
			catch (Exception $e){
					$data_start = $today.' 00:00:00';
	    			$data_final = $today.' 23:59:59';
			}
	    }
	    else
	    {
	    	$data_start = $today.' 00:00:00';
	    	$data_final = $today.' 23:59:59';
	    }

	    $cond .= " AND a.data >= '{$data_start}' AND a.data <= '{$data_final}'";

		$borderou_id = intval($this->sanitize($_REQUEST['borderou'] ?? 0));
		if($borderou_id > 0){
			$cond .= " AND a.borderou = {$borderou_id}";
		}
		$centru_id = intval($this->sanitize($_REQUEST['centru'] ?? 0));
		if($centru_id > 0){
			$cond .= " AND a.centru = {$centru_id}";
		}
		$agent_id = intval($this->sanitize($_REQUEST['agent'] ?? 0));
		if($agent_id > 0){
			$cond .= " AND a.curier = {$agent_id}";
		}
		$ruta_id = intval($this->sanitize($_REQUEST['ruta'] ?? 0));
		if($ruta_id > 0){
			$cond .= " AND a.ruta = {$ruta_id}";
		}
		$tip_scanare = intval($this->sanitize($_REQUEST['tip_scanare'] ?? 0));
		if($tip_scanare > 0){
			$cond .= " AND a.tip = {$tip_scanare}";
		}
		$puisori = intval($this->sanitize($_REQUEST['puisori'] ?? 0));
		if($puisori > 0){
			$cond .= " AND a.is_awb = 1";
		}

		$searchOn = false;
		if(isset($_REQUEST['_search'])) $searchOn = $this->Strip($_REQUEST['_search']);
        if ($searchOn == 'true') {
            $searchstr = $this->Strip($_REQUEST['filters']);
            $cond .= $this->constructWhere($searchstr);
        }

		$cond = preg_replace("/tip_obj  = '1'/i", "ep.tip_obj = 1", $cond);
		$cond = preg_replace("/tip_obj  = '2'/i", "ep.tip_obj = 2", $cond);
		$cond = preg_replace("/tip_obj  = '3'/i", "ep.tip_obj = 3", $cond);
		$cond = preg_replace("/tip_obj  = '4'/i", "ep.tip_obj in (2,3)", $cond);

		$cond = preg_replace("/expeditor_centru/i", "IF(cle.zona_id > 0 and clec.id > 0, clec.nume, cee.nume)", $cond);
		$cond = preg_replace("/expeditor_centru_cod/i", "IF(cle.zona_id > 0 and clec.id > 0, clec.label, cee.label)", $cond);
		$cond = preg_replace("/destinatar_centru/i", "IF(cld.zona_id > 0 and cldc.id > 0, cldc.nume, ced.nume)", $cond);
		$cond = preg_replace("/destinatar_centru_cod/i", "IF(cld.zona_id > 0 and cldc.id > 0, cldc.label, ced.label)", $cond);

		$page = intval($_REQUEST['page'] ?? 1);
		$limit = intval($_REQUEST['rows'] ?? 100);
		$sidx = trim($this->sanitize($_REQUEST['sidx'] ?? 1));
		$sord = trim($this->sanitize($_REQUEST['sord'] ?? 'asc'));

		$query = "SELECT COUNT(a.cod) as nr
			FROM  scanari_coduri as a use index (data)
			LEFT JOIN rute as d ON a.ruta=d.id
			LEFT JOIN agenti as e ON a.curier=e.cod_ag
			LEFT JOIN {$this->tables['exp_prelucrate']} as ep ON (a.expeditie = ep.expeditie and ep.anulata = 0)
			left join clienti cle on cle.cod_cl = ep.expeditor_id
			left join clienti cld on cld.cod_cl = ep.destinatar_id
			left join clienti clp on clp.cod_cl = ep.platitor_id
			LEFT JOIN clienti clpm ON clpm.cod_cl=clp.master
            left join localitati lce ON lce.cod_lc = cle.cod_lc
			left join localitati lcd ON lcd.cod_lc = cld.cod_lc
			left join centre cee ON cee.id = lce.cod_centru
			left join centre ced ON ced.id = lcd.cod_centru
			LEFT JOIN checkpoints as f ON a.tip=f.id
			LEFT JOIN centre as g ON a.centru=g.id
			LEFT JOIN users as u ON a.user=u.id
			WHERE {$cond}";
		//error_log($query);
        $result = $this->db->QFetchArray($query);
        $count = !empty($result['nr']) ? $result['nr'] : 0;

        if( $count >0 ) {
            $total_pages = ceil($count/$limit); }
        else { $total_pages = 0; }
        if ($page > $total_pages) $page=$total_pages;
        if ($limit<0) $limit = 0;
        $start = $limit*$page - $limit; // do not put $limit*($page - 1)
        if ($start<0) $start = 0;

		$query = "SELECT a.cod,a.borderou,a.data as data_scanare, ep.expeditie, ep.data_expeditie,
			cle.nume as expeditor, cld.nume as destinatar, lce.nume_lc as expeditor_localitate, lcd.nume_lc as destinatar_localitate,
			IF(cle.zona_id > 0 and clec.id > 0, clec.nume, cee.nume) as expeditor_centru, 
        	IF(cle.zona_id > 0 and clec.id > 0, clec.label, cee.label) as expeditor_centru_cod,
			IF(cld.zona_id > 0 and cldc.id > 0, cldc.nume, ced.nume) as destinatar_centru, 
			IF(cld.zona_id > 0 and cldc.id > 0, cldc.label, ced.label) as destinatar_centru_cod,
			IF(cld.zona_id > 0 and cldc.id > 0, cldz.name, '') as destinatar_centru_zona,
			ep.tip_obj, ep.piese, ep.plicuri, ep.colete, ep.paleti, ep.tip_exp,
			ep.greutate,d.denumire as ruta,e.nume_ag as curier,f.denumire as tip_scanare,g.nume as centru,u.user as user,
			ep.valoare_asigurata, ep.ramburs,ep.valoare_totala_expeditie as cash, ep.tva, ep.mod_plata, ep.operatiune,
			IF(clp.OBS_BL = 1 OR clpm.OBS_BL = 1,1,0) as OBS_BL
			FROM  scanari_coduri as a use index (data)
			LEFT JOIN rute as d ON a.ruta=d.id
			LEFT JOIN agenti as e ON a.curier=e.cod_ag
			LEFT JOIN {$this->tables['exp_prelucrate']} as ep ON (a.expeditie = ep.expeditie and ep.anulata = 0)
			left join clienti cle on cle.cod_cl = ep.expeditor_id
			left join zones clez ON clez.id = cle.zona_id
			left join centre clec on clec.id = clez.centru_id
			left join clienti cld on cld.cod_cl = ep.destinatar_id
			LEFT JOIN zones cldz ON cldz.id = cld.zona_id
        	LEFT JOIN centre cldc on cldc.id = cldz.centru_id
			left join clienti clp on clp.cod_cl = ep.platitor_id
			LEFT JOIN clienti clpm ON clpm.cod_cl=clp.master
            left join localitati lce ON lce.cod_lc = cle.cod_lc
			left join localitati lcd ON lcd.cod_lc = cld.cod_lc
			left join centre cee ON cee.id = lce.cod_centru
			left join centre ced ON ced.id = lcd.cod_centru
			LEFT JOIN checkpoints as f ON a.tip=f.id
			LEFT JOIN centre as g ON a.centru = g.id
			LEFT JOIN users as u ON a.user = u.id
			WHERE {$cond}
			ORDER BY " . $sidx . " " . $sord . " LIMIT " . $start . " , " . $limit;
		// print_r($query);die;
		// error_log($query);
        $sql = $this->db->QFetchRowArray($query);
        $total_ramburs = 0.00;
        if (!empty($sql)) {
            foreach ($sql as $key => $row) {
				if($row['tip_exp'] == 3) $row['ramburs'] += $row['valoare_asigurata'];
				$row['piese'] = match($row['tip_obj']){
					1 => $row['plicuri'],
					2 => $row['colete'],
					3 => $row['paleti'],
					4 => $row['colete'] + $row['paleti'],
					default => 0
				};

                //$responce->rows[$key]['id'] = $row['expeditie'];
                if(empty($row['expeditie'])) $row['expeditie'] = 'fara expeditie';

                $row['cash'] = floatval($row['cash'])+floatval($row['tva']);
                if($row['mod_plata'] > 0)
                	$row['cash'] = 0;

                $responce->rows[$key]['id'] = $row['cod'];
                if($row['OBS_BL'] == 1 ){
                    $row['cod'] = '<span style="color:red; font-weight: bold">'.$row['cod'].'</span>';
                }
                if($row['OBS_BL'] == 1){
                    $responce->rows[$key]['cell'] = array('<span style="color:red; font-weight: bold">Client blocat</span>',$row['expeditie'],"-","-","-","-","-","-","-","-","-","-","-","-","-","-","-","-","-","-","-","-","-");
                } else {
                    $responce->rows[$key]['cell'] = array($row['cod'],$row['expeditie'],$row['borderou'],$row['tip_obj'],$row['piese'],$row['greutate'],$row['tip_scanare'],$row['operatiune'],$row['ruta'],$row['centru'],$row['destinatar_centru'],$row['curier'],strtoupper($row['expeditor']),strtoupper($row['expeditor_localitate']),strtoupper($row['destinatar']),strtoupper($row['destinatar_localitate']),$row['cash'],$row['ramburs'],$row['data_expeditie'],$row['data_scanare'],$row['user'],$row['expeditor_centru_cod'],$row['destinatar_centru_cod']);
                }
                $total_ramburs += $row['ramburs'];
                //print_r($row);
            }
        }
		$responce->userdata['codbare'] = 'Total:';
		$responce->userdata['expeditie'] =  $count.' Scanari';
		$responce->userdata['ramburs'] =  number_format($total_ramburs, 2, '.', '');

        $responce->page = $page;
        $responce->total = $total_pages;
        $responce->records = $count;
        return json_encode($responce);
	}

	function vExportExpeditiiCsv() {
        $categorie = $_REQUEST['categorie'];
		if($categorie==1) {$cond ="1=1 ";}
		else if($categorie==2) $cond = " ep.tip_obj = 1 ";
		else if($categorie==3) $cond = " ep.tip_obj in (2,3) ";
		else if($categorie==4) $cond = " ep.tip_obj = 3 ";
		else $cond = '1=2';

		$today = date('Y-m-d');
		if(isset($_REQUEST['data_start']) && isset($_REQUEST['data_final'])){
			try {
					$data_start = DateTime::createFromFormat('d.m.Y H:i', $_REQUEST['data_start']);
					$data_final = DateTime::createFromFormat('d.m.Y H:i', $_REQUEST['data_final']);
					if($data_start && $data_final && intval($data_start->diff($data_final, true)->format('%a')) > 90) {
						$data_start = $today.' 00:00:00';
	    				$data_final = $today.' 23:59:59';
					}
					else {
						$data_start = $data_start->format('Y-m-d H:i:s');
						$data_final = $data_final->format('Y-m-d H:i:s');
					}
			}
			catch (Exception $e){
					$data_start = $today.' 00:00:00';
	    			$data_final = $today.' 23:59:59';
			}
	    }
	    else
	    {
	    	$data_start = $today.' 00:00:00';
	    	$data_final = $today.' 23:59:59';
	    }
		$cond .= " AND a.data >= '{$data_start}' AND a.data <= '{$data_final}'";

		$borderou_id = intval($this->sanitize($_REQUEST['borderou'] ?? 0));
		if($borderou_id > 0){
			$cond .= " AND a.borderou = {$borderou_id}";
		}
		$centru_id = intval($this->sanitize($_REQUEST['centru'] ?? 0));
		if($centru_id > 0){
			$cond .= " AND a.centru = {$centru_id}";
		}
		$agent_id = intval($this->sanitize($_REQUEST['agent'] ?? 0));
		if($agent_id > 0){
			$cond .= " AND a.curier = {$agent_id}";
		}
		$ruta_id = intval($this->sanitize($_REQUEST['ruta'] ?? 0));
		if($ruta_id > 0){
			$cond .= " AND a.ruta = {$ruta_id}";
		}
		$tip_scanare = intval($this->sanitize($_REQUEST['tip_scanare'] ?? 0));
		if($tip_scanare > 0){
			$cond .= " AND a.tip = {$tip_scanare}";
		}
		$puisori = intval($this->sanitize($_REQUEST['puisori'] ?? 0));
		if($puisori > 0){
			$cond .= " AND a.is_awb = 1";
		}

		$searchOn = false;
		if(isset($_REQUEST['_search'])) $searchOn = $this->Strip($_REQUEST['_search']);
        if ($searchOn == 'true') {
            $searchstr = $this->Strip($_REQUEST['filters']);
            $cond .= $this->constructWhere($searchstr);
		}

		if(!empty($_REQUEST['filters'])){
			require_once 'jqGridService.php';
			$cond .= jqGridService::getJqGridFiltersCondition($_REQUEST['filters']);
			$flag=1;
		}

		$cond = preg_replace("/tip_obj  = '1'/i", "ep.tip_obj = 1", $cond);
		$cond = preg_replace("/tip_obj  = '2'/i", "ep.tip_obj = 2", $cond);
		$cond = preg_replace("/tip_obj  = '3'/i", "ep.tip_obj = 3", $cond);
		$cond = preg_replace("/tip_obj  = '4'/i", "ep.tip_obj in (2,3)", $cond);

		$cond = preg_replace("/expeditor_centru_cod/i", "IF(cle.zona_id > 0 and clec.id > 0, clec.label, cee.label)", $cond);
		$cond = preg_replace("/expeditor_centru/i", "IF(cle.zona_id > 0 and clec.id > 0, clec.nume, cee.nume)", $cond);
		$cond = preg_replace("/destinatar_centru_cod/i", "IF(cld.zona_id > 0 and cldc.id > 0, cldc.label, ced.label)", $cond);
		$cond = preg_replace("/destinatar_centru/i", "IF(cld.zona_id > 0 and cldc.id > 0, cldc.nume, ced.nume)", $cond);
		

		$query = "SELECT a.cod as 'CodBare', ep.expeditie as 'Nr NT', ep.tip_exp AS 'Tip Exp.', a.borderou as 'Borderou',
			CASE  WHEN ep.plicuri > 0 THEN 'Plic' WHEN ep.colete > 0 THEN 'Colete'  WHEN ep.paleti > 0 THEN 'Paleti' ELSE '' END as 'Categorie',
			(ep.plicuri+ep.colete+ep.paleti) as piese, ep.greutate as 'Greutate', f.denumire as 'Tip Scanare', ep.operatiune as 'Status', d.denumire as 'Ruta',
			g.nume as 'Centru', e.nume_ag as 'Agent',
			cle.nume as 'Expeditor', cld.nume as 'Destinatar', lce.cod_jd as 'Judet exp.', lcd.cod_jd as 'Judet dest.',
			lce.nume_lc as 'Localitate exp.', lcd.nume_lc as 'Localitate dest.', cle.adresa as 'Adresa exp.', cld.adresa as 'Adresa dest.',
			IF(cle.zona_id > 0 and clec.id > 0, clec.label, cee.label) as 'Centru exp', IF(cld.zona_id > 0 and cldc.id > 0, cldc.label, ced.label) as 'Centru dest',
			ep.km_livrare as 'Km la livrare', ep.km_preluare as km_preluare, ep.val_km as 'Tarif km',
			IF(ep.mod_plata > 0 , 0 , (ep.valoare_totala_expeditie + ep.tva)) as 'Cash',
			IF(ep.tip_exp = 3, ep.valoare_asigurata + ep.ramburs, ep.ramburs) as 'Asig/Ramb',
			ep.data_expeditie as 'Colectare', a.data as 'Scanare', u.user as 'User', IF(ep.referire > 0, ep.referire, '') as 'Exp. Initiala',
			IF(ep.referire > 0, CASE bref.tip_plata WHEN 0 THEN 'cash'  WHEN 1 THEN 'bo' WHEN 2 THEN 'cec' WHEN 3 THEN 'cont' ELSE '' END , '') as 'Tip ramburs'
			FROM  scanari_coduri as a use index (data)
			LEFT JOIN rute as d ON a.ruta=d.id
			LEFT JOIN agenti as e ON a.curier=e.cod_ag
			LEFT JOIN {$this->tables['exp_prelucrate']} as ep ON (a.expeditie = ep.expeditie and ep.anulata = 0)
			LEFT JOIN clienti as cle ON cle.cod_cl=ep.expeditor_id
			LEFT JOIN clienti as cld ON cld.cod_cl=ep.destinatar_id
			LEFT JOIN zones clez ON clez.id = cle.zona_id
        	LEFT JOIN centre clec on clec.id = clez.centru_id
			LEFT JOIN zones cldz ON cldz.id = cld.zona_id
        	LEFT JOIN centre cldc on cldc.id = cldz.centru_id
			LEFT JOIN localitati as lce ON cle.cod_lc=lce.cod_lc
			LEFT JOIN localitati as lcd ON cld.cod_lc=lcd.cod_lc
			left join centre cee ON cee.id = lce.cod_centru
			left join centre ced ON ced.id = lcd.cod_centru
			LEFT JOIN {$this->tables['exp_prelucrate']} as bref ON (ep.referire=bref.expeditie and bref.anulata = 0)
			LEFT JOIN checkpoints as f ON a.tip=f.id
			LEFT JOIN centre as g ON a.centru=g.id
			LEFT JOIN users as u ON a.user=u.id
			WHERE {$cond}";
		// print_r($query);die;
		$sql = $this->db->Query($query, [], false);
		$options = new Options(
    		SHOULD_ADD_BOM: false,
		);
		$writer = new Writer($options);
		$writer->openToBrowser("raport_export_" . date("Y-m-d") . ".csv");
		$i = 0;
		ob_start();
		if($sql) {
			if($row = $sql->fetch(PDO::FETCH_ASSOC)) {
				$row_header = Row::fromValues(array_keys($row));
				$writer->addRow($row_header);

				$row_values = Row::fromValues(array_values($row));
				$writer->addRow($row_values);
			}
			while($row = $sql->fetch(PDO::FETCH_ASSOC)) {
				$row['Tip Exp.'] = ExpeditieDto::TIP_EXP[$row['Tip Exp.'] ?? 0] ?? "unknown";
				$row_in = Row::fromValues(array_values($row));
				$writer->addRow($row_in);
				$i++;
				if($i % 1000 === 0) {
					ob_flush();
				}
			}
		}
		ob_end_flush();
		$writer->close();
		exit();
    }

	function JSON_Localitati() {
        $limit = 10;
        $cond = '';
		$responce = new StdClass();
		$responce->total = 0;
        $responce->rezultat=[];
		$limit= intval($_GET['maxRows'] ?? $limit);
		$filter = strtoupper(Backend::sSanitizeCleanEdges(($_GET['name_startsWith'] ?? '')));
		if($limit > 20) $limit = 20;
		if(empty($filter))
			return json_encode($responce);
		$cond= "AND lc.nume_lc LIKE :name_startsWith";

        $query="SELECT lc.cod_lc, lc.nume_lc, ce.nume
                FROM {$this->tables['localitati']} lc
                LEFT JOIN {$this->tables['centre']} ce ON lc.cod_centru = ce.id
                WHERE 1=1 {$cond}
                ORDER BY lc.nume_lc LIMIT {$limit}";
        $sql = $this->db->QFetchRowArray($query, ['name_startsWith'=>$filter."%"]);
        if (!empty($sql)) {
            $responce->total = count($sql);
            foreach ($sql as $key => $row) {
                $responce->rezultat[$key]['cod'] = $row['cod_lc'];
                $responce->rezultat[$key]['value'] = strtoupper($row['nume_lc']);
                $responce->rezultat[$key]['label'] = strtoupper($row['nume_lc']).' ('. strtoupper($row['nume']) .')';
            }
        }
        return json_encode($responce);
    }

	function JSON_Clienti(){
        $limit = 10;
        $cond = '';
		$responce = new StdClass();
		$responce->total = 0;
        $responce->rezultat=[];
		$limit= intval($_GET['maxRows'] ?? $limit);
		$filter = strtoupper(Backend::sSanitizeCleanEdges(($_GET['name_startsWith'] ?? '')));
		$filter2 = strtoupper(Backend::sSanitizeCleanEdges(($_GET['value'] ?? '')));
		if($limit > 20) $limit = 20;
		if(empty($filter))
			return json_encode($responce);

		$cond= "AND nume LIKE :name_startsWith";

        if(isset($_GET['label']) && $_GET['label'] == 'localitate' && !empty($filter2)){
        	$query = "SELECT cod_lc FROM {$this->tables['localitati']} WHERE nume_lc = :localitate";
        	$sql = $this->db->QFetchArray($query, ['localitate'=>$filter2]);
			if(empty($sql)) $cond .= ' AND 1=2';
			else $cond .= ' AND cod_lc = '.$sql['cod_lc'];
        }
		if(isset($_GET['label']) && $_GET['label'] == 'centru' && !empty($filter2)){
			$query = "SELECT id FROM {$this->tables['centre']} WHERE nume = :nume";
        	$sql = $this->db->QFetchArray($query, ['nume'=>$filter2]);
			if(empty($sql)) $cond .= ' AND 1=2';
			else $cond .= ' AND cod_centru = '.$sql['id'];
		}

        $query = "SELECT nume, cod_cl FROM {$this->tables['clienti']} WHERE activ = 1 {$cond} LIMIT {$limit}";
		//echo $query;die;
        $sql = $this->db->QFetchRowArray($query, ['name_startsWith' => ($filter."%")]);
        if (!empty($sql)) {
            $responce->total = count($sql);
            foreach ($sql as $key => $row) {
                $responce->rezultat[$key]['label'] = strtoupper($row['nume']);
				$responce->rezultat[$key]['value'] = strtoupper($row['cod_cl']);

            }
        }
        return json_encode($responce);
    }

    function JSON_ClientiContract(){
        $limit = 10;
        $cond = "";
		$responce = new StdClass();
		$responce->total = 0;
        $responce->rezultat = [];
		$limit= intval($_GET['maxRows'] ?? $limit);
		$filter = strtoupper(Backend::sSanitizeCleanEdges(($_POST['term'] ?? '')));
		if($limit > 20) $limit = 20;
		if(empty($filter))
			return json_encode($responce);

		$cond= " and cl.nume like :term and cl.tarif in (1,2)";

        $query = "SELECT cl.nume, cl.cod_cl, lc.nume_lc as localitate
			from clienti cl
			left join localitati lc on lc.cod_lc = cl.cod_lc
			where cl.activ=1 {$cond} limit {$limit}";

        $sql = $this->db->QFetchRowArray($query, [':term'=>($filter."%")]);
        if (!empty($sql)) {
            $responce->total = count($sql);
            foreach ($sql as $key => $row) {
                $responce->rezultat[$key]['cod_cl'] = $row['cod_cl'];
                $responce->rezultat[$key]['label'] = strtoupper($row['nume']).' ('.strtoupper($row['localitate']).')';
				$responce->rezultat[$key]['value'] = strtoupper($row['nume']);
            }
        }
        return json_encode($responce);
    }

	function JSON_Agenti() {
        $limit = 8;
        $cond = '';
		$responce = new StdClass();
		$responce->total = 0;
        $responce->rezultat=[];
        $limit= intval($_GET['maxRows'] ?? $limit);
		$filter = strtoupper(Backend::sSanitizeCleanEdges(($_GET['name_startsWith'] ?? '')));
		if($limit > 20) $limit = 20;
		if(empty($filter))
			return json_encode($responce);

		$cond= "AND nume_ag LIKE :name_startsWith";

        $query="SELECT cod_ag, nume_ag
                FROM {$this->tables['agenti']}
                WHERE 1=1 {$cond} and activ = 1
                ORDER BY nume_ag LIMIT {$limit}";

        $sql = $this->db->QFetchRowArray($query, ['name_startsWith'=>"%".$filter."%"]);
        if (!empty($sql)) {
            $responce->total = count($sql);
            foreach ($sql as $key => $row) {
				$responce->rezultat[$key]['value'] = $row['cod_ag'];
				$responce->rezultat[$key]['label'] = strtoupper($row['nume_ag']);
            }
        }
        return json_encode($responce);
    }

	function JSON_Operatori(){
        $limit = 8;
        $cond = '';
		$responce = new StdClass();
		$responce->total = 0;
        $responce->rezultat=[];
        $limit= intval($_GET['maxRows'] ?? $limit);
		$filter = strtoupper(Backend::sSanitizeCleanEdges(($_GET['name_startsWith'] ?? '')));
		if($limit > 20) $limit = 20;
		if(empty($filter))
			return json_encode($responce);
		$cond= "AND user LIKE :name_startsWith";

        $query = "SELECT id, user FROM {$this->tables['users']} WHERE activ = 1 {$cond} ORDER BY user LIMIT {$limit}";
        $sql = $this->db->QFetchRowArray($query, ['name_startsWith' => $filter."%"]);
        if (!empty($sql)) {
            $responce->total = count($sql);
            foreach ($sql as $key => $row) {
                $responce->rezultat[$key]['label'] = strtoupper($row['user']);
				$responce->rezultat[$key]['value'] = $row['id'];
            }
        }
        return json_encode($responce);
    }

	function JSON_Centre(){
		$responce = new StdClass();
		$responce->total = 0;
        $responce->rezultat=[];
        $limit = 8;
        $cond = '';
		$limit= intval($_GET['maxRows'] ?? $limit);
		$filter = strtoupper(Backend::sSanitizeCleanEdges(($_GET['name_startsWith'] ?? '')));
		if($limit > 20) $limit = 20;
		if(empty($filter))
			return json_encode($responce);

		$cond= "AND nume LIKE :name_startsWith";

        $query="SELECT id, nume
                FROM {$this->tables['centre']}
                WHERE deleted = 0 {$cond}
                ORDER BY nume LIMIT {$limit}";
		//error_log($query);
        $sql = $this->db->QFetchRowArray($query, ['name_startsWith'=>$filter."%"]);
        if (!empty($sql)) {
            $responce->total = count($sql);
            foreach ($sql as $key => $row) {
                $responce->rezultat[$key]['cod'] = $row['id'];
                $responce->rezultat[$key]['nume'] = strtoupper($row['nume']);
            }
        }
        return json_encode($responce);
    }

	function JSON_LocalitatiOld() {
		$responce = new StdClass();
		$responce->total = 0;
        $responce->rezultat=[];
        $limit = 5;
        $cond = '';
		if(!empty($_GET['maxRows'])) $limit=$_GET['maxRows'];
		if(empty($_GET['name_startsWith']))
			return json_encode($responce);
		$cond= "AND nume_lc LIKE :name_startsWith";

        $query="SELECT cod_lc, nume_lc, cod_jd, dist_km as km_ext
                FROM {$this->tables['localitati']}
                WHERE 1=1 {$cond}
                ORDER BY nume_lc LIMIT {$limit}";
        $sql = $this->db->QFetchRowArray($query, ['name_startsWith'=>strtoupper(Backend::sSanitizeCleanEdges($_GET['name_startsWith']))."%"]);
        if (!empty($sql)) {
            $responce->total = count($sql);
            foreach ($sql as $key => $row) {
                $responce->rezultat[$key]['cod_lc'] = $row['cod_lc'];
                $responce->rezultat[$key]['label'] = strtoupper($row['nume_lc']).' ('. strtoupper($row['cod_jd']) .')';
                $responce->rezultat[$key]['value'] = strtoupper($row['nume_lc']);
				$responce->rezultat[$key]['km_ext'] = strtoupper($row['km_ext']);
            }
        }
        return json_encode($responce);
	}

	function JSON_ClientiOld(){
		$responce = new StdClass();
		$responce->total = 0;
        $responce->rezultat=[];
		$limit= intval($_GET['maxRows'] ?? 12);
		$filter = strtoupper(Backend::sSanitizeCleanEdges(($_GET['name_startsWith'] ?? '')));
		$localitate_id = $this->sanitize(($_GET['localitate'] ?? ''));
		if($limit > 20) $limit = 20;
		if(empty($filter))
			return json_encode($responce);
		$cond = "AND cl.nume LIKE :name_startsWith";
		$cond1 = " AND 1=2";

        if($localitate_id == 'all')
			$cond1 = " AND cl.tarif in (1,2)";
		else {
			$localitate_id = intval($localitate_id);
            $cond1 = ($localitate_id > 0) ? " AND cl.cod_lc = {$localitate_id}" : " AND 1=2";
		}

        $query = "SELECT cl.nume, cl.cod_cl, cl.cod_lc, cl.adresa, cl.paleti, cl.cod_fiscal,
			cl.tarif, cl.cc, cl.mod_plata, cl.contact, cl.telefon, lc.nume_lc, lc.dist_km as km_ext, ce.nume as centru
			FROM {$this->tables['clienti']} cl
        	left join {$this->tables['localitati']} lc on cl.cod_lc = lc.cod_lc
			left join {$this->tables['centre']} ce on ce.id = lc.cod_centru
        	WHERE cl.activ = 1 {$cond} {$cond1} ORDER BY cl.tarif desc, cl.nume asc LIMIT {$limit}";

        $sql = $this->db->QFetchRowArray($query, ['name_startsWith'=>$filter."%"]);
        if (!empty($sql)) {
        	$x=0;
            $responce->total = count($sql);
            foreach ($sql as $key => $row) {
                $responce->rezultat[$x]['value'] = strtoupper($row['nume']);
                $responce->rezultat[$x]['label'] = strtoupper($row['nume']).' ('.strtoupper($row['adresa']).')';
                $responce->rezultat[$x]['cod'] = $row['cod_cl'];
				$responce->rezultat[$x]['cui'] = $row['cod_fiscal'];
                $responce->rezultat[$x]['adresa'] = strtoupper($row['adresa']);
				$responce->rezultat[$x]['contact'] = strtoupper($row['contact']);
				$responce->rezultat[$x]['telefon'] = strtoupper($row['telefon']);
                $responce->rezultat[$x]['contract'] = $row['tarif'];
                $responce->rezultat[$x]['cod_lc'] = $row['cod_lc'];
                $responce->rezultat[$x]['nume_lc'] = $row['nume_lc'];
                $responce->rezultat[$x]['cc'] = $row['cc'];
				$responce->rezultat[$x]['mod_plata'] = $row['mod_plata'];
				$responce->rezultat[$x]['centru'] = strtoupper($row['centru']);
				$responce->rezultat[$x]['km_ext'] = strtoupper($row['km_ext']);

				if(!empty($row['paleti'])){
					$x++;
					$responce->rezultat[$x]['nume'] = strtoupper($row['nume']).' - paleti';
                	$responce->rezultat[$x]['cod'] = $row['cod_cl'];
                	$responce->rezultat[$x]['adresa'] = strtoupper($row['adresa']);
				}
				$x++;
            }
        }
        return json_encode($responce);
    }

	function JSON_ClientiCui(){
		$responce = new StdClass();
		$responce->total = 0;
        $responce->rezultat=[];
		$limit= intval($_GET['maxRows'] ?? 12);
		$filter = strtoupper(Backend::sSanitizeCleanEdges(($_GET['name_startsWith'] ?? '')));
		//$localitate_id = $this->sanitize(($_GET['localitate'] ?? ''));
		if($limit > 20) $limit = 20;
		if(empty($filter))
			return json_encode($responce);
		$cond = "AND cl.cod_fiscal LIKE :name_startsWith";

        $query = "SELECT cl.nume, cl.cod_cl, cl.cod_lc, cl.adresa, cl.paleti, cl.cod_fiscal,
			cl.tarif, cl.cc, cl.mod_plata, cl.contact, cl.telefon, lc.nume_lc, lc.dist_km as km_ext, ce.nume as centru
			FROM {$this->tables['clienti']} cl
        	left join {$this->tables['localitati']} lc on cl.cod_lc = lc.cod_lc
			left join {$this->tables['centre']} ce on ce.id = lc.cod_centru
        	WHERE cl.activ = 1 {$cond} ORDER BY cl.tarif desc, cl.nume asc LIMIT {$limit}";

        $sql = $this->db->QFetchRowArray($query, ['name_startsWith'=>$filter."%"]);
        if (!empty($sql)) {
        	$x=0;
            $responce->total = count($sql);
            foreach ($sql as $key => $row) {
                $responce->rezultat[$x]['value'] = strtoupper($row['cod_fiscal']);
                $responce->rezultat[$x]['label'] = strtoupper($row['cod_fiscal']).' ('.strtoupper($row['nume']).' ('.strtoupper($row['nume_lc']).'))';
                $responce->rezultat[$x]['cod'] = $row['cod_cl'];
				$responce->rezultat[$x]['nume'] = strtoupper($row['nume']);
                $responce->rezultat[$x]['adresa'] = strtoupper($row['adresa']);
				$responce->rezultat[$x]['contact'] = strtoupper($row['contact']);
				$responce->rezultat[$x]['telefon'] = strtoupper($row['telefon']);
                $responce->rezultat[$x]['contract'] = $row['tarif'];
                $responce->rezultat[$x]['cod_lc'] = $row['cod_lc'];
                $responce->rezultat[$x]['nume_lc'] = $row['nume_lc'];
                $responce->rezultat[$x]['cc'] = $row['cc'];
				$responce->rezultat[$x]['mod_plata'] = $row['mod_plata'];
				$responce->rezultat[$x]['centru'] = strtoupper($row['centru']);
				$responce->rezultat[$x]['km_ext'] = strtoupper($row['km_ext']);

				if(!empty($row['paleti'])){
					$x++;
					$responce->rezultat[$x]['nume'] = strtoupper($row['nume']).' - paleti';
                	$responce->rezultat[$x]['cod'] = $row['cod_cl'];
                	$responce->rezultat[$x]['adresa'] = strtoupper($row['adresa']);
				}
				$x++;
            }
        }
        return json_encode($responce);
    }

    function JSON_Platitori(){
		$responce = new StdClass();
		$responce->total = 0;
        $responce->rezultat=[];
        $limit = 8;
		$limit= intval($_GET['maxRows'] ?? $limit);
		$filter = strtoupper(Backend::sSanitizeCleanEdges(($_GET['name_startsWith'] ?? '')));
		if($limit > 20) $limit = 20;
		if(empty($filter))
			return json_encode($responce);
		$cond = " cl.nume LIKE :name_startsWith AND cl.tarif in (1,2)";

        $query = "SELECT cl.nume, cl.cod_cl, cl.master, lc.nume_lc as localitate, cl.adresa,
			cl.paleti, cl.cc, cl.mod_plata
			FROM clienti cl
			left join localitati lc on lc.cod_lc = cl.cod_lc
			WHERE cl.activ = 1 and {$cond} ORDER BY cl.nume LIMIT {$limit}";
		//echo $query;die;

        $sql = $this->db->QFetchRowArray($query, ['name_startsWith'=>$filter."%"]);
        if (!empty($sql)) {
        	$x=0;
            $responce->total = count($sql);
            foreach ($sql as $key => $row) {
                $responce->rezultat[$x]['nume'] = strtoupper($row['nume']).' ('.strtoupper($row['localitate']).' : '.strtoupper($row['adresa']).')';
                $responce->rezultat[$x]['cod'] = $row['cod_cl'];
                $responce->rezultat[$x]['adresa'] = strtoupper($row['localitate']).' : '. strtoupper($row['adresa']);
                $responce->rezultat[$x]['cc'] = $row['cc'];
				$responce->rezultat[$x]['mod_plata'] = $row['mod_plata'];

				if(!empty($row['PALETI'])){
					$x++;
					$responce->rezultat[$x]['nume'] = strtoupper($row['nume']).' - paleti';
                	$responce->rezultat[$x]['cod'] = $row['cod_cl'];
                	$responce->rezultat[$x]['adresa'] = strtoupper($row['localitate']).' : '.strtoupper($row['adresa']);
                	$responce->rezultat[$x]['cc'] = $row['cc'];
					$responce->rezultat[$x]['mod_plata'] = $row['mod_plata'];
				}
				$x++;
            }
        }
        return json_encode($responce);
    }

	function JSON_Agenti1_Old(){
		$responce = new StdClass();
		$responce->total = 0;
        $responce->rezultat=[];
        $limit = 8;
		$limit= intval($_GET['maxRows'] ?? $limit);
		$filter = strtoupper(Backend::sSanitizeCleanEdges(($_GET['name_startsWith'] ?? '')));
		$localitate_id = intval($_GET['cod_lc'] ?? 0);
		if($limit > 20) $limit = 20;
		if(empty($filter))
			return json_encode($responce);
		$cond= "AND a.nume_ag LIKE :name_startsWith";

        $query="SELECT a.cod_ag, a.nume_ag, ce.nume
                FROM {$this->tables['agenti']} a
                INNER JOIN {$this->tables['localitati']} lc ON lc.cod_lc={$localitate_id}
                INNER JOIN {$this->tables['centre']} ce ON a.cod_centru = ce.id
                WHERE 1=1 AND a.cod_centru = lc.cod_centru {$cond}  and a.activ = 1
                ORDER BY a.nume_Ag LIMIT {$limit};";
        $sql = $this->db->QFetchRowArray($query, ['name_startsWith'=>$filter."%"]);
        if (!empty($sql)) {
            $responce->total = count($sql);
            foreach ($sql as $key => $row) {
                $responce->rezultat[$key]['cod'] = $row['cod_ag'];
                $responce->rezultat[$key]['nume'] = strtoupper($row['nume_ag']).' ('. strtoupper($row['nume']) .')';
                $responce->rezultat[$key]['centru'] = strtoupper($row['nume_ag']).' ('. strtoupper($row['nume']) .')';
            }
        }
        return json_encode($responce);
    }

	function JSON_Agenti1_Old_All(){
		$responce = new StdClass();
		$responce->total = 0;
        $responce->rezultat=[];
        $limit = 8;
        $filter = strtoupper(Backend::sSanitizeCleanEdges(($_GET['name_startsWith'] ?? '')));
		$centru = strtoupper(Backend::sSanitizeCleanEdges(($_GET['cod_lc'] ?? '')));
		if($limit > 20) $limit = 20;
		if(empty($filter))
			return json_encode($responce);
		$cond= "AND ag.nume_ag LIKE :name_startsWith";
		if (!empty($centru)) {
			$cond .= " AND ce.nume LIKE :centru";
		}
        $query="SELECT ag.cod_ag, ag.nume_ag, ce.nume
                FROM {$this->tables['agenti']} ag
                INNER JOIN {$this->tables['centre']} ce ON ag.cod_centru = ce.id
                WHERE 1=1 {$cond} and ag.activ = 1
                ORDER BY ag.nume_ag LIMIT {$limit}";
        if (!empty($centru)) {
					$sql = $this->db->QFetchRowArray($query, ['name_startsWith'=>"%".$filter."%", 'centru' => "%".$centru."%"]);
				} else {
					$sql = $this->db->QFetchRowArray($query, ['name_startsWith'=>"%".$filter."%"]);
				}

        if (!empty($sql)) {
            $responce->total = count($sql);
            foreach ($sql as $key => $row) {
                $responce->rezultat[$key]['cod'] = $row['cod_ag'];
                $responce->rezultat[$key]['nume'] = strtoupper($row['nume_ag']).' ('. strtoupper($row['nume']) .')';
                $responce->rezultat[$key]['centru'] = strtoupper($row['nume_ag']).' ('. strtoupper($row['nume']) .')';
            }
        }
        return json_encode($responce);
    }

	function JSON_ListeExpeditiiAgenti() {
		$responce = new StdClass();
		$responce->page = 0;
		$responce->total = 0;
		$responce->records = 0;


		$page = intval($_REQUEST['page'] ?? 1);
		$limit = intval($_REQUEST['rows'] ?? 20);
		$sidx = trim($this->sanitize($_REQUEST['sidx'] ?? 1));
		$sord = trim($this->sanitize($_REQUEST['sord'] ?? 'asc'));

		$centru_id = intval($_REQUEST['centru'] ?? 0);
		if($centru_id <= 0) {
			return json_encode($responce);
		}

		$query = "SELECT COUNT(cod_ag) as nr
				FROM {$this->tables['agenti']}
				WHERE cod_centru = {$centru_id} AND activ = 1
			";
		$result = $this->db->QFetchArray($query);
		$count = !empty($result['nr']) ? $result['nr'] : 0;

		if( $count >0 ) {
			$total_pages = ceil($count/$limit); }
		else { $total_pages = 0; }
		if ($page > $total_pages) $page=$total_pages;
		if ($limit<0) $limit = 0;
		$start = $limit*$page - $limit; // do not put $limit*($page - 1)
		if ($start<0) $start = 0;

		$query="SELECT cod_ag, nume_ag, telefon
				FROM {$this->tables['agenti']}
				WHERE cod_centru = {$centru_id} AND activ = 1
				ORDER BY " . $sidx . " " . $sord . " LIMIT " . $start . " , " . $limit . ";";
		$sql = $this->db->QFetchRowArray($query);
		if (!empty($sql)) {
			foreach ($sql as $key => $row) {
				$responce->rows[$key]['id'] = $row['cod_ag'];
				$responce->rows[$key]['cell'] = array($row['nume_ag'],$row['telefon']);
			}
		}
		$responce->page = $page;
		$responce->total = $total_pages;
		$responce->records = $count;
		return json_encode($responce);
	}

   function TopClienti(){
	$this->vars['title_page'] = 'Top Clienti';
	$vars = [];
	  $cond = '';

	return $this->Parse($this->page_prefix . 'top_clienti.html', $vars);

}

	function JSON_TopClienti() {
		$responce = new StdClass();
		$post=$_POST;
		$flag=0;
		$cond = "1=1";
		if(isset($_REQUEST['data_start']) && isset($_REQUEST['data_final'])){
			$data_start = $this->TransformDate($_REQUEST['data_start']);
			$data_final = $this->TransformDate($_REQUEST['data_final']);
			$cond .= " AND a.data_expeditie>='".$data_start."' AND a.data_expeditie<='".$data_final."'";
			$flag=1;
		}
		if(!empty($_REQUEST['platitor'])){
			if($_REQUEST['platitor']==2)
				$cond .= ' AND a.platitor_id=a.expeditor_id';
			else if($_REQUEST['platitor']==3)
				$cond .= ' AND a.platitor_id=a.destinatar_id';
			else if($_REQUEST['platitor']==3)
				$cond .= ' AND a.platitor_id!=a.expeditor_id AND a.platitor_id!=a.destinatar_id';
			$flag=1;
		}

		$exp_maxim = 0;
		$exp_minim = 0;
		if(!empty($_REQUEST['maxim'])){
			$exp_maxim = intval($_REQUEST['maxim']);
		}
		if(!empty($_REQUEST['minim'])){
			$exp_minim = intval($_REQUEST['minim']);
		}

		$eur = 0;
		$usd = 0;
		if(!empty($_REQUEST['eur'])){
			$eur = $_REQUEST['eur'];
		}
		if(!empty($_REQUEST['usd'])){
			$usd = $_REQUEST['usd'];
		}


		if(empty($flag)) $cond = '1=2';

		$cond .= " and a.anulata = 0";
		$query = "SELECT
			a.platitor,a.platitor_id,b.localitate as localitate,
			COUNT(a.expeditie) as expedieri,

			SUM(IF(a.moneda='1' AND a.mod_plata=0,a.valoare_totala_expeditie,0)) AS cash_lei,
			SUM(IF(a.moneda='2' AND a.mod_plata=0,a.valoare_totala_expeditie,0)) AS cash_usd,
			SUM(IF(a.moneda='3' AND a.mod_plata=0,a.valoare_totala_expeditie,0)) AS cash_eur,

			SUM(IF(a.moneda='1' AND a.mod_plata=0 AND idfact > 0,a.valoare_totala_expeditie,0)) AS cash1_lei,
			SUM(IF(a.moneda='2' AND a.mod_plata=0 AND idfact > 0,a.valoare_totala_expeditie,0)) AS cash1_usd,
			SUM(IF(a.moneda='3' AND a.mod_plata=0 AND idfact > 0,a.valoare_totala_expeditie,0)) AS cash1_eur,

			SUM(IF(a.moneda='1' AND a.mod_plata=1,a.valoare_totala_expeditie,0)) AS contract_lei,
			SUM(IF(a.moneda='2' AND a.mod_plata=1,a.valoare_totala_expeditie,0)) AS contract_usd,
			SUM(IF(a.moneda='3' AND a.mod_plata=1,a.valoare_totala_expeditie,0)) AS contract_eur,

			SUM(IF(a.moneda='1' AND a.mod_plata=1 AND idfact > 0,a.valoare_totala_expeditie,0)) AS contract1_lei,
			SUM(IF(a.moneda='2' AND a.mod_plata=1 AND idfact > 0,a.valoare_totala_expeditie,0)) AS contract1_usd,
			SUM(IF(a.moneda='3' AND a.mod_plata=1 AND idfact > 0,a.valoare_totala_expeditie,0)) AS contract1_eur,

			SUM(IF(a.moneda='1',a.valoare_totala_expeditie,0)) AS total_lei,
			SUM(IF(a.moneda='2',a.valoare_totala_expeditie,0)) AS total_usd,
			SUM(IF(a.moneda='3',a.valoare_totala_expeditie,0)) AS total_eur

			from {$this->tables['exp_prelucrate']} a
			INNER JOIN {$this->tables['clienti']} b ON a.platitor_id=b.cod_cl
			WHERE {$cond}
			GROUP BY a.platitor_id";
			//echo $query;die;
		$sql = $this->db->QFetchRowArray($query);
		$count=0;
		if (!empty($sql)) {
			foreach ($sql as $key => $row) {
				$total_lei = $row['total_lei'];
				$total_usd = round($row['total_usd']*$usd,2);
				$total_eur = round($row['total_eur']*$eur,2);
				$row['valoare'] = $total_lei+$total_usd+$total_eur;

				$cash_lei = $row['cash_lei'];
				$cash_usd = round($row['cash_usd']*$usd,2);
				$cash_eur = round($row['cash_eur']*$eur,2);
				$row['val_cash'] = $cash_lei+$cash_usd+$cash_eur;
				$cash_lei = $row['cash1_lei'];
				$cash_usd = round($row['cash1_usd']*$usd,2);
				$cash_eur = round($row['cash1_eur']*$eur,2);
				$row['plata_cash'] = $cash_lei+$cash_usd+$cash_eur;

				$contract_lei = $row['contract_lei'];
				$contract_usd = round($row['contract_usd']*$usd,2);
				$contract_eur = round($row['contract_eur']*$eur,2);
				$row['val_contract'] = $contract_lei+$contract_usd+$contract_eur;
				$contract_lei = $row['contract1_lei'];
				$contract_usd = round($row['contract1_usd']*$usd,2);
				$contract_eur = round($row['contract1_eur']*$eur,2);
				$row['plata_contract'] = $contract_lei+$contract_usd+$contract_eur;

				$flag=0;
				$x=0;

				if(!empty($exp_minim)){
					$x=1;
					$row['expedieri'] = round($row['expedieri']);
					if($row['expedieri']>$exp_minim ) $flag=1;
				}

				echo '<br/> expedieri: -'.$row['expedieri'].'-  -minim:  -'.$exp_minim.'-   ';

				echo 'ax: '.$x.'   -   flag:'.$flag;
				if($x==1 && $flag==1){
					$responce->rows[$count]['id'] = $row['platitor_id'];
					$responce->rows[$count]['cell'] = array($row['platitor'].' ('.$row['localitate'].')',$row['expedieri'],$row['valoare'],$row['plata_cash'],$row['val_cash'],$row['plata_contract'],$row['val_contract']);
					$count++;
				}
			}
		}
		$responce->page = 1;
		$responce->total = 1;
		$responce->records = $count;

		$_SESSION['activitate_livrari_count'] = $count;

		return json_encode($responce);
	}
}