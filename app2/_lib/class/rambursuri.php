<?php

/**
 * W o r k s p a c e
 *
 */

require_once "expeditieDto.php";

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

use OpenSpout\Writer\CSV\Writer;
use OpenSpout\Writer\CSV\Options;
use OpenSpout\Common\Entity\Row;
use OpenSpout\Common\Entity\Cell;

class ModulRambursuri extends BackEnd {

    public $final_result;
    public $action_module;
    public $page_prefix;
    public $table;
    private $arr_referinte;
    private $data_referinte;
	private $data_referinte_tip;
	private $data_referinte_cok;
	private $nr_exps_in = 0;


    public $filtre_urmarire_rbs = array(
        1 =>'Toate',
        2 =>'In Derulare',
        11 =>'Cu Initiala Livrata',
        3 =>'Cu Ramburs',
        4 =>'Cu Ramburs Livrat',
        44 =>'Cu Ramburs Nelivrat',
        5 =>'Fara Ramburs',
        6 =>'Inchise',
		9 =>'Returnate',
		10 => 'Nepreluat',
		11 => 'Spre Compensare',
		21 => 'On Hold',
		23 => 'Decontat',
		24 => 'LaPlata',
		25 => 'Nesosit',
		26 => 'Abandonat',
		27 => 'Compensat',
		30 => 'Aprobat',
		31 => 'Returnat'//initiala returnata
    );

    //0:Initiala, 1:Retur NT, 2:Retur Doc,3:Ramburs,4:Interna,5:Returnare,6:Retur ambalaj

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
		$this->procTva = $this->getProcentTVA(date("Y-m-d"));
		
        $this->vars['title_page'] = 'Expeditii cu ramburs';
        $this->page_prefix = 'rambursuri_';
        $this->arr_referinte = [];
		$this->data_referinte = [];
		$this->data_referinte_tip = [];
		$this->data_referinte_cok = [];

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
            $this->ActionsNivelAcces();
        else
            $this->final_result = $this->PageNotFound();
        // R E S U L T
        return $this->final_result;
    }

    function ActionsNivelAcces() {
		$this->user_rights = $this->GetDrepturiUtilizator($this->user_profile);
        $arr = $this->GenerateArr();
        //nivel acces 10
		$flag = 0;

		if($this->user_profile == 10){
			$this->user_rights[] = 'vizualizare_rambursuri';
			$this->user_rights[] = 'urmarire_rambursuri';
			$this->user_rights[] = 'print_ch_ramburs';
			$this->user_rights[] = 'creare_printare_note_rambursuri';
			$this->user_rights[] = 'rbs_validate';
		}

			if (in_array("vizualizare_rambursuri", $this->user_rights) || in_array("creare_printare_note_rambursuri", $this->user_rights) || $this->user_profile == 10){
				if(isset($arr[1]) && $arr[1] == 'json' && isset($arr[2]) && $arr[2]=='afisare_note')
					echo $this->JSON_AfisareNote();
				else if(isset($arr[1]) && $arr[1] == 'creare_tiparire_export')
					echo $this->ExportCreareTiparire();

				$flag=1;
			}

			if (in_array("creare_printare_note_rambursuri", $this->user_rights) || $this->user_profile == 10){

				if(isset($arr[1]) && $arr[1]=='creare_tiparire'){
					$this->final_result = $this->RambursuriCreareTiparire();
				} else if(isset($arr[1]) && $arr[1]=='tiparire_referire'){
					$this->final_result = $this->TiparireReferire();
				}
				else if(isset($arr[1]) && $arr[1] == 'json' && isset($arr[2]) && $arr[2]=='creare_note')
					echo $this->JSON_CreareNote();
				$flag=1;
			}

			if (in_array("vizualizare_rambursuri", $this->user_rights) || $this->user_profile == 10){
				if (isset($arr[1]) && $arr[1] == 'vizualizare')
					$this->final_result = $this->Vizualizare();
				else if (isset($arr[1]) && $arr[1] == 'urmarire')
					$this->final_result = $this->Urmarire();
				else if(isset($arr[1]) && $arr[1] == 'json' && isset($arr[2]) && $arr[2]=='urmarire')
					echo $this->JSON_Urmarire();
				else if (isset($arr[1]) && $arr[1] == 'detalii_expeditie')
					echo $this->DetaliiExpeditie();
				//else if (isset($arr[1]) && $arr[1] == 'print_urmarire')
					//echo $this->PrintUrmarire();
				else if (isset($arr[1]) && $arr[1] == 'export_urmarire')
					echo $this->ExportUrmarire();
				else if(isset($arr[1]) && $arr[1]=='lista'){
					$this->final_result = $this->RambursuriLista();
				}

				$flag=1;
			}
			if (in_array("urmarire_rambursuri", $this->user_rights) || $this->user_profile == 10){
				if (isset($arr[1]) && $arr[1] == 'modificare_status_multiple')
					echo $this->ModificareMultiplaStatusRamburs();
				$flag=1;
			}
			if (in_array("print_ch_ramburs", $this->user_rights) || $this->user_profile == 10){
				if(isset($arr[1]) && $arr[1] == 'decont' && isset($arr[2]) && $arr[2]=='cautare')
					$this->final_result = $this->DecontCautare();
				else if(isset($arr[1]) && $arr[1] == 'decont' && isset($arr[2]) && $arr[2]=='json_cautare')
					echo $this->JSON_DecontCautare();
				else if(isset($arr[1]) && $arr[1] == 'decont' && isset($arr[2]) && $arr[2]=='detalii')
					echo $this->DetaliiChitantaRamburs($arr[3]);
				else if(isset($arr[1]) && $arr[1] == 'decont' && isset($arr[2]) && $arr[2]=='printone') {
					echo $this->PrintOneChitantaRambursTCPDF();
				}
				else if(isset($arr[1]) && $arr[1] == 'decont' && isset($arr[2]) && $arr[2]=='print')
					echo $this->PrintSelectedChitantaRambursTCPDF();
				else if(isset($arr[1]) && $arr[1] == 'decont' && isset($arr[2]) && $arr[2]=='xclose') {
					if($this->user_id == parent::DOINA || $this->user_id == parent::MARIAN || $this->user_id == parent::MARIUS_TELER)
						echo $this->StergeChitantaDecont($arr[3]);
					else echo 0;
				}
				$flag=1;
			}
			if ($this->user_profile == 10){
				if (isset($arr[1]) && $arr[1] == 'validate')
					$this->final_result = $this->RbsValidate();
				else if(isset($arr[1]) && $arr[1] == 'json' && isset($arr[2]) && $arr[2]=='validate')
					echo $this->JSON_RbsValidate();
				else if (isset($arr[1]) && $arr[1] == 'export_validate')
					echo $this->ExportRbsValidate();
				$flag=1;
			}
        if(empty($flag))
            $this->final_result = $this->PageNotFound();
    }

 //-------------------------------- functii ----------------------------------------
/*/////////////////////////////////////////////////////////////
				 URMARIRE RAMBURSURI
/////////////////////////////////////////////////////////////*/


    function ConditieRambursuri(){
		$cond = " init.ramburs > 0 and init.tip_exp = 0 and init.anulata = 0";
        if(isset($_REQUEST['data_start']) && isset($_REQUEST['data_final'])){
            $data_start = $this->TransformDate($_REQUEST['data_start']);
            $data_final = $this->TransformDate($_REQUEST['data_final']);
        } else {
            $data_start = $data_final = date("Y-m-d");
        }

		$centru_id = intval($_REQUEST['centru_id'] ?? 0);
		$dashboard = intval($_REQUEST['dashboard'] ?? 0);
		$dashboard_type = strtolower($this->sanitize($_REQUEST['dashboard_type'] ?? ''));
		$status_rmb = intval($_REQUEST['status_rmb'] ?? 0);

        if($dashboard > 0 && !empty($dashboard_type)){
        	if($dashboard_type == 'netrimise'){
				$cond .= " AND init.status_ramburs in (0,23)"; //in derulare si decontat
				if($centru_id > 0)
					$cond .= " AND IF(cld.zona_id > 0 and cldc.id > 0, cldc.id, lcd.cod_centru) = {$centru_id}";
			}
			else if($dashboard_type == 'nelivrate'){
				$cond .= " AND init.status_ramburs in (4,30)"; //aprobat si spre client
				if($centru_id > 0)
					$cond .= " AND IF(cle.zona_id > 0 and clec.id > 0, clec.id, lce.cod_centru) = {$centru_id}";
			}
		}
		else if($status_rmb > 0){
            switch ($status_rmb){

                case 1: //NT cu ramburs - Toate

                    break;
                case 2:  //NT cu ramburs - in derulare
                    $cond .= " AND init.status_ramburs=0 ";
					break;
				case 10:  //NT cu ramburs - Nepreluat
                    $cond .= " AND init.status_ramburs=10 ";
					break;
				case 11:  //NT - spre compensare
                    $cond .= " AND init.status_ramburs=11 ";
                    break;
                case 3: //NT cu ramburs - ramburs introdus
                    $cond .= " AND ( rbs.tip_exp  = 3 OR rtn.tip_exp = 5)";
                    break;
                case 4: //NT cu ramburs - ramburs livrat
                    $cond .= " AND (rbs.operatiune = 'Livrat' OR rtn.operatiune = 'Livrat') ";
                    break;
                case 44: //NT cu ramburs - ramburs nelivrat
					$cond .= " AND (rbs.operatiune != 'Livrat' OR rtn.operatiune != 'Livrat') ";
                    break;
                case 5: //NT cu ramburs - fara nota creata ramburs
                    $cond .= " AND rbs.tip_exp IS NULL  AND rtn.tip_exp IS NULL";
                    break;
                case 9: //NT cu ramburs - returnate
					$cond .= " AND rtn.tip_exp = 5 ";
                    break;
                case 6:  //NT cu ramburs - inchise
                    $cond .= " AND init.status_ramburs = 1 AND rbs.tip_exp = 3";
                    break;
                case 7: //NT cu ramburs - validate
                    $cond .= " AND init.status_ramburs = 2 AND rbs.tip_exp = 3";
                    break;
                case 11:
                    $cond .= " AND ie.cod_ist > 0 ";
					break;
				case 21: //NT cu ramburs - On Hold
					$cond .= " AND init.status_ramburs = 21";
					break;
				case 23: //NT cu ramburs - Decontat
					$cond .= " AND init.status_ramburs = 23";
					break;
				case 24: //NT cu ramburs - LaPlata
					$cond .= " AND init.status_ramburs = 24";
					break;
				case 25: //NT cu ramburs - Nesosit
					$cond .= " AND init.status_ramburs = 25";
					break;
				case 26: //NT cu ramburs - Abandonat
					$cond .= " AND init.status_ramburs = 26";
					break;
				case 27: //NT cu ramburs - Compensat
					$cond .= " AND init.status_ramburs = 27";
					break;
				case 30: //NT cu ramburs - Aprobat
					$cond .= " AND init.status_ramburs = 30";
					break;
				case 31: //NT cu ramburs - Returnat
					$cond .= " AND init.status_ramburs = 31";
					break;
                default:

                    break;
            }
		}

        $date_range_for = (isset($_REQUEST['date_range_for'])?$_REQUEST['date_range_for']:1);

        switch ($date_range_for){
            case 1: //search by data initiala
				if($dashboard > 0){
					$cond .= " AND init.data_expeditie >= (CURDATE() - INTERVAL 1 YEAR) ";
				}
				else {
					$cond .= " AND init.data_expeditie between '".$data_start."' AND '".$data_final."' ";
				}
                break;
            case 2://search by data rbs
				$cond .= " AND rbs.data_expeditie between '".$data_start."' AND '".$data_final."' ";
                break;
            default:
                $cond .= " AND init.data_expeditie between '".$data_start."' AND '".$data_final."' ";
                break;
        }

		if(!empty($_REQUEST['expeditii'])){
			$expeditii = parent::ValidareExpeditiiCurata($_REQUEST['expeditii']);
			$expeditii_clean = [];
			if(!empty($expeditii)) {
				$expeditii_clean = explode(",", $expeditii);
			}
			$cond = " 1=2";
			$this->nr_exps_in = 0;
			if(count($expeditii_clean) > 0){
				$this->nr_exps_in = count($expeditii_clean);
				$expeditii_clean = implode(",",$expeditii_clean);
				$cond = [];
				$cond []= " init.ramburs > 0 AND init.expeditie IN (".$expeditii_clean.") and init.anulata = 0";
				$cond []= " init.ramburs > 0 AND rbs.expeditie IN (".$expeditii_clean.") and rbs.anulata = 0";
				$cond []= " init.ramburs > 0 AND rtn.expeditie IN (".$expeditii_clean.") and rtn.anulata = 0";
			}
		}
		//if($this->user_id == parent::MARIAN)
			//$this->log(print_r($cond, true), parent::APP_LOG_FILE);
    	return $cond;
    }

    function getRambursuriSQLQuery($cond = null, $sidx = null, $sord = null, $start = 0, $limit = 0){

        $concat  = ($sidx)?" ORDER BY " . $sidx . " ":"";
        $concat .= ($sidx && $sord)?$sord:"";
		$concat .= ($start >= 0 && $limit > 0)?" LIMIT " . $start . " , " . $limit :"";

        $query = "SELECT init.cod_expeditie, init.expeditie, init.referire, init.data_expeditie,
        init.expeditor_id, init.expeditor_contact, init.expeditor_telefon,
        init.destinatar_id, init.destinatar_contact, init.destinatar_telefon, init.platitor_id, init.idfact,
        lce.cod_lc as expeditor_localitate_id, lcd.cod_lc as destinatar_localitate_id, lcp.cod_lc as platitor_localitate_id,
        IF(cle.zona_id > 0 and clec.id > 0, clec.id, cee.id) as expeditor_centru_id,
		IF(cld.zona_id > 0 and cldc.id > 0, cldc.id, ced.id) as destinatar_centru_id,
		IF(clp.zona_id > 0 and clpc.id > 0, clpc.id, cep.id) as platitor_centru_id,
        init.tip_exp, init.plicuri, init.colete, init.paleti, init.greutate, init.volum, init.greutate_vol, init.ret_nt, init.ret_doc, init.ret_colet, init.ret_amb, init.liv_sed, init.liv_samb,
        IF(init.paleti = 1, 'PALET', IF(init.plicuri = 1, 'PLIC', 'COLET')) as tip_obj,
        IF(lce.cod_lc = lcd.cod_lc, 0 , 1) as tip_tarif,
        init.copen, init.sms, init.km_preluare, init.km_livrare,
        init.ramburs, init.tip_plata, init.ramburs_procent, init.valoare_asigurata, init.procent_asigurare,
        init.val_greutate, init.val_km, init.val_asig, init.valoare_expeditie, init.valoare_totala_expeditie, init.tva, init.procTva, init.mod_plata, init.moneda,
        init.curier_preluare_id, init.curier_livrare_id, init.primitor, init.operatiune, init.data_op,
        init.observatii, init.detalii_doc, init.status_ramburs,
		cle.nume as expeditor, cle.cc as expeditor_cc, cle.mod_plata as expeditor_mod_plata, cle.tarif as expeditor_contract, cle.adresa as expeditor_adresa,
        lce.nume_lc as expeditor_localitate, lce.dist_km as expeditor_localitate_km,
        IF(cle.zona_id > 0 and clec.id > 0, clec.nume, cee.nume) as expeditor_centru, 
        IF(cle.zona_id > 0 and clec.id > 0, clec.label, cee.label) as expeditor_centru_cod,
		cld.nume as destinatar, cld.cc as destinatar_cc, cld.mod_plata as destinatar_mod_plata, cld.tarif as destinatar_contract, cld.adresa as destinatar_adresa,
        lcd.nume_lc as destinatar_localitate, lcd.dist_km as destinatar_localitate_km,
        IF(cld.zona_id > 0 and cldc.id > 0, cldc.nume, ced.nume) as destinatar_centru, 
		IF(cld.zona_id > 0 and cldc.id > 0, cldc.label, ced.label) as destinatar_centru_cod,
		clp.nume as platitor, clp.cc as platitor_cc, clp.mod_plata as platitor_mod_plata, clp.tarif as platitor_contract,
        IF(clp.zona_id > 0 and clpc.id > 0, clpc.nume, cep.nume) as platitor_centru, 
		IF(clp.zona_id > 0 and clpc.id > 0, clpc.label, cep.label) as platitor_centru_cod,
		lcp.nume_lc as platitor_localitate,
		cle.icc as rbs_individual, clp.icc as prbs_individual,
		if(mst.nume is NULL , cle.nume, mst.nume) as master_nume, mst.nume_societate as master_nume_societate,
		if(cle.icc > 0 or mst.rbs_days is NULL , cle.rbs_days, mst.rbs_days) as master_rbs_days,
		if(cle.icc > 0 , cle.cod_fiscal, mst.cod_fiscal) as cod_fiscal,
		if(cle.icc > 0 , cle.cont_rbs, mst.cont_rbs) as cont_rbs,
		if(cle.icc > 0 , cle_rbs.nume, mst_rbs.nume) as banca_rbs,
		if(cle.icc > 0 , cle_rbs.bic, mst_rbs.bic) as bic_rbs,
		pmst.ret_rbs as pret_rbs,
		if(pmst.nume is NULL , clp.nume, pmst.nume) as pmaster_nume, pmst.nume_societate as pmaster_nume_societate,
		if(clp.icc > 0 or pmst.rbs_days is NULL , clp.rbs_days, pmst.rbs_days) as pmaster_rbs_days,
		if(clp.icc > 0 , clp.cod_fiscal, pmst.cod_fiscal) as pcod_fiscal,
		if(clp.icc > 0 , clp.cont_rbs, pmst.cont_rbs) as pcont_rbs,
		if(clp.icc > 0 , clp_rbs.nume, pmst_rbs.nume) as pbanca_rbs,
		if(clp.icc > 0 , clp_rbs.bic, pmst_rbs.bic) as pbic_rbs,

		CASE WHEN rbs.expeditie       IS NOT NULL THEN rbs.expeditie      ELSE rtn.expeditie        END AS rbs_expeditie ,
		CASE WHEN rbs.data_expeditie  IS NOT NULL THEN rbs.data_expeditie ELSE rtn.data_expeditie   END AS rbs_data_expeditie ,
		CASE WHEN rbs.tip_exp         IS NOT NULL THEN rbs.tip_exp        ELSE rtn.tip_exp          END AS rbs_tip_exp ,
		if(ie.cod_ist > 0,1,0) as livrat
		FROM {$this->tables['exp_prelucrate']} init
		left join clienti cle on cle.cod_cl = init.expeditor_id
		left join clienti cld on cld.cod_cl = init.destinatar_id
		LEFT JOIN clienti clp on clp.cod_cl = init.platitor_id
		LEFT JOIN zones clez ON clez.id = cle.zona_id
		LEFT JOIN centre clec on clec.id = clez.centru_id
		LEFT JOIN zones cldz ON cldz.id = cld.zona_id
		LEFT JOIN centre cldc on cldc.id = cldz.centru_id
		LEFT JOIN zones clpz ON clpz.id = clp.zona_id
		LEFT JOIN centre clpc on clpc.id = clpz.centru_id
		left join localitati lce ON lce.cod_lc = cle.cod_lc
		left join localitati lcd ON lcd.cod_lc = cld.cod_lc
		LEFT JOIN localitati lcp on lcp.cod_lc = clp.cod_lc
		left join centre cee ON cee.id = lce.cod_centru
		left join centre ced ON ced.id = lcd.cod_centru
		LEFT JOIN centre cep on cep.id = lcp.cod_centru
		LEFT JOIN clienti mst on cle.master = mst.cod_cl
		LEFT JOIN clienti pmst on clp.master = pmst.cod_cl
		LEFT JOIN banci cle_rbs on cle_rbs.id = cle.banca_rbs_id
		LEFT JOIN banci clp_rbs on clp_rbs.id = clp.banca_rbs_id
		LEFT JOIN banci mst_rbs on mst_rbs.id = mst.banca_rbs_id
		LEFT JOIN banci pmst_rbs on pmst_rbs.id = pmst.banca_rbs_id
		LEFT JOIN ist_exp ie ON ie.cod_ist = (
			SELECT
				MAX(i.cod_ist) cod_ist
				FROM ist_exp i
				WHERE i.cod_exp = init.cod_expeditie AND i.OPERATIUNE = 3
		)
		LEFT JOIN {$this->tables['exp_prelucrate']} rbs
			ON rbs.referire = init.expeditie AND rbs.tip_exp = 3 and rbs.anulata = 0
		LEFT JOIN {$this->tables['exp_prelucrate']} rtn
			ON rtn.referire = init.expeditie AND rtn.tip_exp = 5 and rtn.anulata = 0";

		if(is_array($cond))
			$query = "(".$query." WHERE ".$cond[0].") UNION (".$query." WHERE ".$cond[1].") UNION (".$query." WHERE ".$cond[2]." )";
		else {
			$query .= " WHERE ".$cond;
			$query .= " {$concat}";
		}
		/*
		if($this->user_id == parent::MARIAN)
			$this->log($query, parent::APP_LOG_FILE);
		*/
		// error_log(print_r($cond, true));
		return $query;
    }

	function TiparireReferire(){

		$pData = isset($_POST['pJson'])? base64_decode($_POST['pJson']):'';
		if(empty($pData)) return "0 selected";
		//error_log($pData);
		$pData = json_decode($pData, true);
		if(count($pData) == 0) return "0 selected";

		//array_map : filter exps integer
		$pData = array_map("intval", $pData);
		$pData = array_diff($pData, array(0));
		if(count($pData) == 0) return "0 selected";
		$initiale = implode(",", $pData);

		$query = "SELECT expeditie
            FROM {$this->tables['exp_prelucrate']}
            WHERE referire IN ({$initiale}) AND tip_exp = 3 and anulata = 0";

		$sql = $this->db->QFetchRowArray($query);
		if(empty($sql)){
			return "nici un awb de ramburs in awb-urile introduse !";
		}
		$exps = [];
		foreach ($sql as $row){
			$exps[] = $row['expeditie'];
		}
		$json = json_encode($exps);

		require_once 'expeditii.php';
		$expMod = new ModulExpeditii($this->config, 0, $this->db);
		echo $expMod->PrintareMultiplaCautareExpeditii($json);
		exit();
	}


    function Urmarire() {
        $this->vars['title_page'] = 'Urmarire Expeditii cu ramburs';
        $vars = [];
        $vars['data_start'] = (!empty($_REQUEST['data_start']))?$_REQUEST['data_start']:date('d.m.Y');
        $vars['data_final'] = (!empty($_REQUEST['data_final']))?$_REQUEST['data_final']:date('d.m.Y');
        $vars['OPTIONS_STATUS_RAMBURS'] = "";
        $vars['OPTIONS_STATUS_RAMBURS_JS'] = "";
		$vars['table_filters'] = (!empty($_REQUEST['filters']))?$_REQUEST['filters']:'';
        $vars['date_range_for_initiala'] = (empty($_REQUEST['date_range_for']) || (!empty( $_REQUEST['date_range_for']) &&  $_REQUEST['date_range_for'] == 1) )?'selected="selected"':'';
        $vars['date_range_for_ramburs'] = (!empty( $_REQUEST['date_range_for']) &&  $_REQUEST['date_range_for'] == 2)?'selected="selected"':'';

        $vars['OPTIUNI_STATUS_RBS'] =  (!empty($_REQUEST['status_rmb']))?$_REQUEST['status_rmb']:'';

        if(empty($_REQUEST['status_rmb']) && empty($_REQUEST['status']))
            $_REQUEST['status_rmb'] = 1;

        if(!empty($_REQUEST['status']))
            $_REQUEST['status_rmb'] = $_REQUEST['status'];

        $optiuni = "";
		foreach ($this->filtre_urmarire_rbs  as $id => $filtru){
			$selected = '';
			if(!empty($_REQUEST['status_rmb'] && $id == $_REQUEST['status_rmb']))
            	$selected = ' selected="selected"';

            $optiuni .= '<option value="'.$id.'" '.$selected.'>'.$filtru.'</option>';
		}
        $vars['OPTIUNI_STATUS_RBS'] = $optiuni;


        foreach (parent::STATUS_RAMBURS as $id=>$val){
            $vars['OPTIONS_STATUS_RAMBURS'] .= "<option value='{$id}'>{$val}</option>";
            $vars['OPTIONS_STATUS_RAMBURS_JS'] .= ";{$id}:$val";
        }

        return $this->Parse($this->page_prefix . 'urmarire.html', $vars);
    }

	function JSON_CreareNote(){
		$exp_selectate = json_decode(trim($_REQUEST['expeditii_selectate']) , true);
		$exp_selectate = array_map('intval', $exp_selectate);
		$exp_selectate = array_unique($exp_selectate);
		$exp_selectate = array_filter($exp_selectate, function ($a) { return ExpeditieDto::isAwb($a) || ExpeditieDto::isCmnAwb($a); });
		$expeditii = implode(",", $exp_selectate);

		//expeditii initiale cu ramburs cash sau cont
		// exclude expeditii care au deja awb de ramburs sau retur
		// exclude expeditii care au centrul de colectare blocat financiar
		// exclude expeditii care au centrul master de colectare blocat financiar
		$query_initiale = "SELECT ep.expeditie, ep.tip_plata, ep.status_ramburs,
				IF(cle.zona_id > 0 and clez.centru_id > 0, clec.financiar, cee.financiar) as financiar,
				IF(cle.zona_id > 0 and clez.centru_id > 0, clecm.financiar, ceem.financiar) as mst_financiar
				FROM exp_prelucrate ep
				LEFT JOIN exp_prelucrate rbs ON rbs.referire = ep.expeditie AND rbs.tip_exp = 3 and rbs.anulata = 0
                LEFT JOIN exp_prelucrate rtn ON rtn.referire = ep.expeditie AND rtn.tip_exp = 5 AND rtn.anulata = 0
				inner join clienti cle on cle.cod_cl = ep.expeditor_id
				LEFT JOIN zones clez ON clez.id = cle.zona_id
        		LEFT JOIN centre clec on clec.id = clez.centru_id
				left join centre clecm on clecm.id = clec.mst_financiar_id
                inner join localitati lce ON lce.cod_lc = cle.cod_lc
                inner join centre cee on cee.id = lce.cod_centru
				left join centre ceem on ceem.id = cee.mst_financiar_id
				WHERE ep.tip_exp = 0 and ep.anulata = 0 and ep.ramburs > 0 and ep.tip_plata in (0,3) 
				AND ep.expeditie in ({$expeditii})
				and rbs.cod_expeditie is NULL and rtn.cod_expeditie is NULL
				";
		$awb_filtrate = $this->db->QFetchRowArray($query_initiale);

		foreach ($awb_filtrate as $row => $exp){
			if($exp['financiar'] == 1 && $exp['tip_plata'] == 0 && $exp['status_ramburs'] != 11){
				// exclude expeditii cu ramburs cash si centru de colectare blocat financiar
				// include status ramburs spre compensare
				continue; 
			}
			if($exp['financiar'] == 0 && $exp['mst_financiar'] == 1 && $exp['tip_plata'] == 0 && $exp['status_ramburs'] != 11){
				// exclude expeditii cu ramburs cash si centru de colectare blocat financiar
				// include status ramburs spre compensare
				continue; 
			}
			$this->creazaNotaRamburs($exp['expeditie']);
		}
		return $this->JSON_AfisareNote();
	}

	function ExportCreareTiparire() {

		$rand = rand(0,100);

		$arr_header=[];
		$arr_header[0][1] = 'Nr. crt.';
		$arr_header[0][2] = 'Nr. NT';
		$arr_header[0][3] = 'Colectare exp.';
		$arr_header[0][4] = 'Expeditor';
		$arr_header[0][5] = 'Centru expeditor';
		$arr_header[0][6] = 'Destinatar';
		$arr_header[0][7] = 'Centru destinatar';
		$arr_header[0][8] = 'Return NT';
		$arr_header[0][9] = 'Retur DOC';
		$arr_header[0][10] = 'Status';
		$arr_header[0][11] = 'NT Referinta';
		$arr_header[0][12] = 'Colectare ref.';
		$arr_header[0][13] = 'Valoare';
		$arr_header[0][14] = 'Tip plata';
        $arr_header[0][15] = 'Observatii';
		$arr_header[0][16] = 'Data DCL';
		$arr_header[0][17] = 'Data COK';
		$arr_header[0][18] = 'Data COK RET';

		if(empty($_REQUEST['expeditii'])){
			$this->download_send_headers("data_export_" . date("Y-m-d") . "-".$rand.".csv");
			echo $this->array2csv($arr_header);die;
		}

		$expeditii = parent::ValidareExpeditiiCurata($_REQUEST['expeditii']);
		if(empty($expeditii)){
			$this->download_send_headers("data_export_" . date("Y-m-d") . "-".$rand.".csv");
			echo $this->array2csv($arr_header);die;
		}
		$cond = " ( e.expeditie IN (".$expeditii.") OR e.referire IN (".$expeditii.") )";

		$query = "SELECT e.cod_expeditie, e.expeditie, e.referire, e.data_expeditie,
			e.expeditor_id, e.expeditor_contact, e.expeditor_telefon,
			e.destinatar_id, e.destinatar_contact, e.destinatar_telefon, e.platitor_id, e.idfact,
			lce.cod_lc as expeditor_localitate_id, lcd.cod_lc as destinatar_localitate_id, lcp.cod_lc as platitor_localitate_id,
			IF(cle.zona_id > 0 and clec.id > 0, clec.id, cee.id) as expeditor_centru_id,
			IF(cld.zona_id > 0 and cldc.id > 0, cldc.id, ced.id) as destinatar_centru_id,
			IF(clp.zona_id > 0 and clpc.id > 0, clpc.id, cep.id) as platitor_centru_id,
			e.tip_exp, e.plicuri, e.colete, e.paleti, e.greutate, e.volum, e.greutate_vol, e.ret_nt, e.ret_doc, e.ret_colet, e.ret_amb, e.liv_sed, e.liv_samb,
			IF(e.paleti = 1, 'PALET', IF(e.plicuri = 1, 'PLIC', 'COLET')) as tip_obj,
			IF(lce.cod_lc = lcd.cod_lc, 0 , 1) as tip_tarif,
			e.copen, e.sms, e.km_preluare, e.km_livrare,
			e.ramburs, e.tip_plata, e.ramburs_procent, e.valoare_asigurata, e.procent_asigurare, e.status_ramburs,
			e.val_greutate, e.val_km, e.val_asig, e.valoare_expeditie, e.valoare_totala_expeditie, e.tva, e.procTva, e.mod_plata, e.moneda,
			e.curier_preluare_id, e.curier_livrare_id, e.primitor, e.operatiune, e.data_op,
			e.observatii, e.detalii_doc,
			cle.nume as expeditor, cle.cc as expeditor_cc, cle.mod_plata as expeditor_mod_plata, cle.tarif as expeditor_contract, cle.adresa as expeditor_adresa,
			lce.nume_lc as expeditor_localitate, lce.dist_km as expeditor_localitate_km,
			cld.nume as destinatar, cld.cc as destinatar_cc, cld.mod_plata as destinatar_mod_plata, cld.tarif as destinatar_contract, cld.adresa as destinatar_adresa,
			lcd.nume_lc as destinatar_localitate, lcd.dist_km as destinatar_localitate_km,
			IF(cle.zona_id > 0 and clec.id > 0, clec.nume, cee.nume) as expeditor_centru, 
        	IF(cle.zona_id > 0 and clec.id > 0, clec.label, cee.label) as expeditor_centru_cod,
			IF(cld.zona_id > 0 and cldc.id > 0, cldc.nume, ced.nume) as destinatar_centru, 
			IF(cld.zona_id > 0 and cldc.id > 0, cldc.label, ced.label) as destinatar_centru_cod,
			clp.nume as platitor, clp.cc as platitor_cc, clp.mod_plata as platitor_mod_plata, clp.tarif as platitor_contract,
			IF(clp.zona_id > 0 and clpc.id > 0, clpc.nume, cep.nume) as platitor_centru, 
			IF(clp.zona_id > 0 and clpc.id > 0, clpc.label, cep.label) as platitor_centru_cod,
			lcp.nume_lc as platitor_localitate,
			i.DATA as data_cok
			FROM {$this->tables['exp_prelucrate']} e
			left join clienti cle on cle.cod_cl = e.expeditor_id
			left join clienti cld on cld.cod_cl = e.destinatar_id
			LEFT JOIN clienti clp on clp.cod_cl = e.platitor_id
			LEFT JOIN zones clez ON clez.id = cle.zona_id
        	LEFT JOIN centre clec on clec.id = clez.centru_id
			LEFT JOIN zones cldz ON cldz.id = cld.zona_id
        	LEFT JOIN centre cldc on cldc.id = cldz.centru_id
			LEFT JOIN zones clpz ON clpz.id = clp.zona_id
        	LEFT JOIN centre clpc on clpc.id = clpz.centru_id
			left join localitati lce ON lce.cod_lc = cle.cod_lc
			left join localitati lcd ON lcd.cod_lc = cld.cod_lc
			LEFT JOIN localitati lcp on lcp.cod_lc = clp.cod_lc
			left join centre cee ON cee.id = lce.cod_centru
			left join centre ced ON ced.id = lcd.cod_centru
			LEFT JOIN centre cep on cep.id = lcp.cod_centru
            LEFT JOIN ist_exp i ON i.cod_exp = e.cod_expeditie AND i.operatiune = 3
            WHERE {$cond} and e.anulata = 0 GROUP BY e.cod_expeditie";

		$sql = $this->db->QFetchRowArray($query);
		if (!empty($sql)) {
			$initiale = [];
			foreach ($sql as $key => $row) {
				if(!empty($row['referire']))
					$initiale[] = $row['referire'];
			}
			if(count($initiale)){
				$expeditii .= ','.implode(',',$initiale);
			}
		}

		$query = "SELECT ep.expeditie,ep.referire,ep.data_expeditie, ep.tip_exp, i.data as data_ret_cok
            FROM {$this->tables['exp_prelucrate']} ep
			LEFT JOIN ist_exp i ON i.cod_exp = ep.cod_expeditie AND i.operatiune = 3
            WHERE ep.referire > 0 AND ep.referire IN ({$expeditii} ) and ep.anulata = 0 AND ep.tip_exp in (3,5)";


		$sql = $this->db->QFetchRowArray($query);
		if(!empty($sql))
		{
			foreach ($sql as $key => $row) {
				$this->arr_referinte[$row['referire']] = $row['expeditie'];
				$this->data_referinte[$row['referire']] = $row['data_expeditie'];
				$this->data_referinte_tip[$row['referire']] = $row['tip_exp'];
				$this->data_referinte_cok[$row['referire']] = $row['data_ret_cok'];
			}
		}

		$cond = "e.tip_exp = 0 AND e.expeditie IN (".$expeditii.") AND e.expeditie > 0 and e.anulata = 0";

		//start generare conditie
		$searchOn = '';
		if(isset($_REQUEST['_search']))
			$searchOn = $this->Strip($_REQUEST['_search']);
		if ($searchOn == 'true') {
			$searchstr = $this->Strip($_REQUEST['filters']);
			$cond .= $this->constructWhereIgnore($searchstr, ['data_ret']);
		}

		$cond = str_replace("e.tip_plata  = '4'","(e.tip_plata  = 0 or e.tip_plata  = 3)", $cond);
		$query = "SELECT e.expeditie, e.data_expeditie, e.ret_nt, e.ret_doc, e.ret_colet, e.ret_amb, e.ramburs, e.tip_plata,
			e.expeditor_id, e.destinatar_id, e.platitor_id,
			cle.nume as expeditor, cld.nume as destinatar,
			lce.nume_lc as expeditor_localitate, lcd.nume_lc as destinatar_localitate,
			IF(cle.zona_id > 0 and clec.id > 0, clec.nume, cee.nume) as expeditor_centru, 
			IF(cld.zona_id > 0 and cldc.id > 0, cldc.nume, ced.nume) as destinatar_centru, 
			e.tip_exp, e.val_asig, e.valoare_asigurata, e.greutate,
			e.plicuri, e.colete, e.paleti, e.status_ramburs, e.observatii,
			i.DATA as data_cok, scckp.last_dcl_data as data_decontare
            FROM exp_prelucrate e
			left join clienti cle on cle.cod_cl = e.expeditor_id
			left join clienti cld on cld.cod_cl = e.destinatar_id
			LEFT JOIN zones clez ON clez.id = cle.zona_id
        	LEFT JOIN centre clec on clec.id = clez.centru_id
			LEFT JOIN zones cldz ON cldz.id = cld.zona_id
        	LEFT JOIN centre cldc on cldc.id = cldz.centru_id
			left join localitati lce ON lce.cod_lc = cle.cod_lc
			left join localitati lcd ON lcd.cod_lc = cld.cod_lc
			left join centre cee ON cee.id = lce.cod_centru
			left join centre ced ON ced.id = lcd.cod_centru
			left join ( select ie.cod_exp, ie.data
                    from ist_exp ie
                    where ie.operatiune = 3 and ie.cod_ist = (select MAX(iet.cod_ist) from ist_exp iet where iet.operatiune = 3 and iet.cod_exp = ie.cod_exp)
            ) as i on i.cod_exp = e.cod_expeditie
			left join ( select sc.expeditie, sc.data as last_dcl_data
                    from scanari_coduri sc
                    where sc.is_awb = 1 and sc.tip = 32 and sc.data = (select MAX(scc.data) from scanari_coduri scc where scc.expeditie = sc.expeditie and scc.is_awb = 1)
            ) as scckp on scckp.expeditie = e.expeditie
            WHERE {$cond}";

		//error_log($query);
		$sql = $this->db->QFetchRowArray($query);
		$arr_body = [];
		//compun raspunsul
		if (!empty($sql)) {
			arsort($sql);
			$rand = 1;
			foreach ($sql as $key => $row) {
				if(empty($row['status_ramburs'])) $row['status_ramburs'] = 0;

				$referire = '';
				$data_referire = '';
				$data_ret_cok = '';
				if(isset($this->arr_referinte[$row['expeditie']])) $referire = $this->arr_referinte[$row['expeditie']];
				if(isset($this->data_referinte[$row['expeditie']])) $data_referire = $this->data_referinte[$row['expeditie']];
				if(isset($this->data_referinte_cok[$row['expeditie']])) $data_ret_cok = $this->data_referinte_cok[$row['expeditie']];

				if(!empty($row['ret_nt'])) $row['ret_nt'] = 'DA';
				else  $row['ret_nt'] = 'NU';

				if(!empty($row['ret_doc'])) $row['ret_doc'] = 'DA';
				else  $row['ret_doc'] = 'NU';


				if($row['tip_plata'] ==1) $row['tip_plata'] = 'bo';
				else if($row['tip_plata'] == 2) $row['tip_plata'] = 'cec';
				else if($row['tip_plata'] == 3) $row['tip_plata'] = 'cont';
				else $row['tip_plata'] = 'cash';

				$arr_body[($key+1)][1] = $rand++;
				$arr_body[($key+1)][2] = $row['expeditie'];
				$arr_body[($key+1)][3] = $row['data_expeditie'];
				$arr_body[($key+1)][4] = $row['expeditor'].'('.$row['expeditor_localitate'].')';
				$arr_body[($key+1)][5] = $row['expeditor_centru'];
				$arr_body[($key+1)][6] = $row['destinatar'].'('.$row['destinatar_localitate'].')';
				$arr_body[($key+1)][7] = $row['destinatar_centru'];
				$arr_body[($key+1)][8] = $row['ret_nt'];
				$arr_body[($key+1)][9] = $row['ret_doc'];
				$arr_body[($key+1)][10] = parent::STATUS_RAMBURS[$row['status_ramburs']] ?? 'unknown';
				$arr_body[($key+1)][11] = $referire;
				$arr_body[($key+1)][12] = $data_referire;
				$arr_body[($key+1)][13] = $row['ramburs'];
				$arr_body[($key+1)][14] = $row['tip_plata'];
                $arr_body[($key+1)][15] = $row['observatii'];
				$arr_body[($key+1)][16] = $row['data_decontare'];
				$arr_body[($key+1)][17] = $row['data_cok'];
				$arr_body[($key+1)][18] = $data_ret_cok;
			}
			$i_sort = 1;
			if(isset($_REQUEST['sidx']) && ($_REQUEST['sidx'] == 'data_ret'  || $_REQUEST['sidx'] == 'referire' || $_REQUEST['sidx'] == 'data_ramburs' || $_REQUEST['sidx'] == 'rbs_tip_exp')) {
				switch ($_REQUEST['sidx']) {
					case 'data_ret' : $i_sort = 14; break;
					case 'data_ramburs' : $i_sort = 5; break;
					case 'rbs_tip_exp' : $i_sort = 6; break;
					case 'referire' : $i_sort = 4; break;
				}
			}
			if(isset($_REQUEST['sord']) && strtoupper($_REQUEST['sord']) == "ASC")
				usort($arr_body, function ($row1, $row2) use ($i_sort) {
					return strcmp($row1[$i_sort] ,$row2[$i_sort]);
				});
			else
				usort($arr_body, function ($row1, $row2) use ($i_sort) {
					return strcmp($row2[$i_sort] ,$row1[$i_sort]);
				});
			//error_log(print_r($data_ret,true));
		}


		$arr_out = array_merge_recursive($arr_header, $arr_body);

		$this->download_send_headers("data_export_" . date("Y-m-d") . "-".$rand.".csv");
		echo $this->array2csv($arr_out, "4096M"); die;
	}

	function JSON_AfisareNote(){

		$responce = new StdClass();

		if(!isset($_POST['expeditii']) || strlen(trim($_POST['expeditii'])) == 0)
			return json_encode($responce);

		$expeditii = parent::ValidareExpeditiiCurata($_POST['expeditii'] ?? "");

		if(empty($expeditii)){
			return json_encode($responce);
		}
		$arr_expeditii = explode(',',$expeditii);

		$cond = " ( expeditie IN (".$expeditii.") OR referire IN (".$expeditii.") ) and anulata = 0";

		$query = "SELECT distinct referire
            FROM {$this->tables['exp_prelucrate']}
            WHERE {$cond}";

		$sql = $this->db->QFetchRowArray($query);
		if (!empty($sql)) {
			$initiale = [];
			foreach ($sql as $key => $row) {
				if(!empty($row['referire']))
					$initiale[] = $row['referire'];
			}
			if(count($initiale) > 0){
				$arr_expeditii = array_unique(array_merge($arr_expeditii, $initiale));
				$expeditii .= ','.implode(',',$arr_expeditii);
			}
		}

		$query = "SELECT e.expeditie,e.referire,e.data_expeditie, e.tip_exp, i.data as data_ret_cok
            FROM {$this->tables['exp_prelucrate']} e
			LEFT JOIN ist_exp i ON i.cod_exp = e.cod_expeditie AND i.operatiune = 3
            WHERE e.referire > 0 AND e.referire IN ({$expeditii} ) AND e.tip_exp in (3,5) and e.anulata = 0";

		$sql = $this->db->QFetchRowArray($query);
		if(!empty($sql))
		{
			foreach ($sql as $key => $row) {
				$this->arr_referinte[$row['referire']] = $row['expeditie'];
				$this->data_referinte[$row['referire']] = $row['data_expeditie'];
				$this->data_referinte_tip[$row['referire']] = $row['tip_exp'];
				$this->data_referinte_cok[$row['referire']] = $row['data_ret_cok'];
			}
		}

		$cond = " e.tip_exp = 0 and e.anulata = 0 ";
		if(count($arr_expeditii) > 0){
			$cond .= " AND e.expeditie IN (".$expeditii.")";
		}

		//start generare conditie
		if(!empty($_REQUEST['_search'])) {
			$searchOn = $this->Strip($_REQUEST['_search']);
			if ($searchOn == 'true') {
				$searchstr = $this->Strip($_REQUEST['filters']);
				$cond .= $this->constructWhereIgnore($searchstr, ['data_ret']);
			}
		}

		$cond = str_replace("e.tip_plata  = '4'","(e.tip_plata  = 0 or e.tip_plata  = 3)", $cond);

		$page = intval($_REQUEST['page'] ?? 1);
		$limit = intval($_REQUEST['rows'] ?? 1000);
		$sidx = trim($this->sanitize($_REQUEST['sidx'] ?? 1));
		$sord = trim($this->sanitize($_REQUEST['sord'] ?? 'asc'));

		if($sidx == 'data_ret' || $sidx == 'referire' || $sidx == 'data_ramburs' || $sidx == 'rbs_tip_exp') $sidx = 1;

		$query = "SELECT COUNT(e.cod_expeditie) as nr
			FROM exp_prelucrate e
			left join clienti cle on cle.cod_cl = e.expeditor_id
			left join clienti cld on cld.cod_cl = e.destinatar_id
			LEFT JOIN zones clez ON clez.id = cle.zona_id
        	LEFT JOIN centre clec on clec.id = clez.centru_id
			LEFT JOIN zones cldz ON cldz.id = cld.zona_id
        	LEFT JOIN centre cldc on cldc.id = cldz.centru_id
			LEFT JOIN clienti clp on clp.cod_cl = e.platitor_id
			left join localitati lce ON lce.cod_lc = cle.cod_lc
			left join localitati lcd ON lcd.cod_lc = cld.cod_lc
			left join centre cee ON cee.id = lce.cod_centru
			left join centre ced ON ced.id = lcd.cod_centru
			left join ( select ie.cod_exp, ie.data
                    from ist_exp ie
                    where ie.operatiune = 3 and ie.cod_ist = (select MAX(iet.cod_ist) from ist_exp iet where iet.operatiune = 3 and iet.cod_exp = ie.cod_exp)
            ) as i on i.cod_exp = e.cod_expeditie
			left join ( select sc.expeditie, sc.data as last_dcl_data
                    from scanari_coduri sc
                    where sc.is_awb = 1 and sc.tip = 32 and 
					sc.data = (select MAX(scc.data) from scanari_coduri scc where scc.expeditie = sc.expeditie and scc.is_awb = 1)
            ) as scckp on scckp.expeditie = e.expeditie
			WHERE {$cond}";
		$result = $this->db->QFetchArray($query);
		$count = !empty($result['nr']) ? $result['nr'] : 0;

		if( $count >0 ) {$total_pages = ceil($count/$limit); }
		else { $total_pages = 0; }
		if ($page > $total_pages) $page=$total_pages;
		if ($limit<0) $limit = 0;
		$start = $limit*$page - $limit; // do not put $limit*($page - 1)
		if ($start<0) $start = 0;


		$query = "SELECT distinct e.cod_expeditie, e.expeditie, e.referire, e.data_expeditie,
			e.expeditor_id, e.expeditor_contact, e.expeditor_telefon,
			e.destinatar_id, e.destinatar_contact, e.destinatar_telefon, e.platitor_id, e.idfact,
			lce.cod_lc as expeditor_localitate_id, lcd.cod_lc as destinatar_localitate_id,
			IF(cle.zona_id > 0 and clec.id > 0, clec.id, cee.id) as expeditor_centru_id,
			IF(cld.zona_id > 0 and cldc.id > 0, cldc.id, ced.id) as destinatar_centru_id,
			e.tip_exp, e.plicuri, e.colete, e.paleti, e.greutate, e.volum, e.greutate_vol, e.ret_nt, e.ret_doc, e.ret_colet, e.ret_amb, e.liv_sed, e.liv_samb,
			IF(e.paleti = 1, 'PALET', IF(e.plicuri = 1, 'PLIC', 'COLET')) as tip_obj,
			IF(lce.cod_lc = lcd.cod_lc, 0 , 1) as tip_tarif,
			e.copen, e.sms, e.km_preluare, e.km_livrare,
			e.ramburs, e.tip_plata, e.ramburs_procent, e.valoare_asigurata, e.procent_asigurare, e.status_ramburs,
			e.val_greutate, e.val_km, e.val_asig, e.valoare_expeditie, e.valoare_totala_expeditie, e.tva, e.procTva, e.mod_plata, e.moneda,
			e.curier_preluare_id, e.curier_livrare_id, e.primitor, e.operatiune, e.data_op,
			e.observatii, e.detalii_doc,
			cle.nume as expeditor, cle.cc as expeditor_cc, cle.mod_plata as expeditor_mod_plata, cle.tarif as expeditor_contract, cle.adresa as expeditor_adresa,
			lce.nume_lc as expeditor_localitate, lce.dist_km as expeditor_localitate_km,
			IF(cle.zona_id > 0 and clec.id > 0, clec.nume, cee.nume) as expeditor_centru, 
        	IF(cle.zona_id > 0 and clec.id > 0, clec.label, cee.label) as expeditor_centru_cod,
			cld.nume as destinatar, cld.cc as destinatar_cc, cld.mod_plata as destinatar_mod_plata, cld.tarif as destinatar_contract, cld.adresa as destinatar_adresa,
			lcd.nume_lc as destinatar_localitate, lcd.dist_km as destinatar_localitate_km,
			IF(cld.zona_id > 0 and cldc.id > 0, cldc.nume, ced.nume) as destinatar_centru, 
			IF(cld.zona_id > 0 and cldc.id > 0, cldc.label, ced.label) as destinatar_centru_cod,
			clp.nume as platitor, clp.cc as platitor_cc, clp.mod_plata as platitor_mod_plata, clp.tarif as platitor_contract,
			i.data as data_cok, scckp.last_dcl_data as data_decontare
			FROM exp_prelucrate e
			left join clienti cle on cle.cod_cl = e.expeditor_id
			left join clienti cld on cld.cod_cl = e.destinatar_id
			LEFT JOIN zones clez ON clez.id = cle.zona_id
        	LEFT JOIN centre clec on clec.id = clez.centru_id
			LEFT JOIN zones cldz ON cldz.id = cld.zona_id
        	LEFT JOIN centre cldc on cldc.id = cldz.centru_id
			LEFT JOIN clienti clp on clp.cod_cl = e.platitor_id
			left join localitati lce ON lce.cod_lc = cle.cod_lc
			left join localitati lcd ON lcd.cod_lc = cld.cod_lc
			left join centre cee ON cee.id = lce.cod_centru
			left join centre ced ON ced.id = lcd.cod_centru
			left join ( select ie.cod_exp, ie.data
                    from ist_exp ie
                    where ie.operatiune = 3 and ie.cod_ist = (select MAX(iet.cod_ist) from ist_exp iet where iet.operatiune = 3 and iet.cod_exp = ie.cod_exp)
            ) as i on i.cod_exp = e.cod_expeditie
			left join ( select sc.expeditie, sc.data as last_dcl_data
                    from scanari_coduri sc
                    where sc.is_awb = 1 and sc.tip = 32 
					and sc.data = (select MAX(scc.data) from scanari_coduri scc where scc.expeditie = sc.expeditie and scc.is_awb = 1)
            ) as scckp on scckp.expeditie = e.expeditie
			WHERE  {$cond}
			ORDER BY " . $sidx . " " . $sord . " LIMIT " . $start . " , " . $limit;

		//error_log($cond);

		$sql = $this->db->QFetchRowArray($query);

		$total_ramburs = 0.00;
		$total_status=[];

        foreach (parent::STATUS_RAMBURS as $id => $val){
            $total_status[$id] = 0;
		}

		$expeditii_gasite = [];

		if (!empty($sql)) {
			foreach ($sql as $key => $row) {
				if(empty($row['status_ramburs'])) $row['status_ramburs'] = 0;
				$responce->rows[$key]['id'] = $row['expeditie'];
				$total_ramburs +=$row['ramburs'];
				$total_status[$row['status_ramburs']]++;

				$referire = $this->arr_referinte[$row['expeditie']] ?? '';
				$data_referire = $this->data_referinte[$row['expeditie']] ?? '';
				$data_referire_cok = $this->data_referinte_cok[$row['expeditie']] ?? '';
				$tip_ref = $this->data_referinte_tip[$row['expeditie']] ?? '';
				$responce->rows[$key]['cell'] = array($row['destinatar_centru'], strtoupper($row['expeditor_centru']),$row['expeditie'],$row['data_expeditie'],$referire,$data_referire,$tip_ref,$row['ramburs'],$row['tip_plata'],strtoupper(parent::STATUS_RAMBURS[$row['status_ramburs']] ?? 'unknown'),strtoupper($row['expeditor']),strtoupper($row['destinatar']), false, $row['data_decontare'], $row['data_cok'], $data_referire_cok);
				$expeditii_gasite[] = $row['expeditie'];
			}
			if(isset($_REQUEST['sidx']) && ($_REQUEST['sidx'] == 'data_ret'  || $_REQUEST['sidx'] == 'referire' || $_REQUEST['sidx'] == 'data_ramburs' || $_REQUEST['sidx'] == 'rbs_tip_exp')) {
				$i_sort = 1;
				switch ($_REQUEST['sidx']) {
					case 'data_ret' : $i_sort = 14; break;
					case 'data_ramburs' : $i_sort = 5; break;
					case 'rbs_tip_exp' : $i_sort = 6; break;
					case 'referire' : $i_sort = 4; break;
				}
				if(strtoupper($sord) == "ASC")
					usort($responce->rows, function ($row1, $row2) use ($i_sort) {
						return strcmp($row1['cell'][$i_sort] ,$row2['cell'][$i_sort]);
					});
				else
					usort($responce->rows, function ($row1, $row2) use ($i_sort) {
						return strcmp($row2['cell'][$i_sort] ,$row1['cell'][$i_sort]);
					});

				//error_log(print_r($data_ret,true));
			}
		}

		$responce->page = $page;
		$responce->total = $total_pages;
		$responce->records = $count;

		$responce->userdata['destinatar_centru'] = 'Total';
		$responce->userdata['expeditor_centru'] = $count.' Expeditii';
		$responce->userdata['expeditie'] = 'Suma: '.$total_ramburs;
		$responce->userdata['data_expeditie'] = '';
		$responce->userdata['ramburs'] = '';
		$responce->userdata['status_ramburs'] = '';
		$responce->userdata['expeditor'] = $total_status[0].' In derulare';
		$responce->userdata['destinatar'] = $total_status[1].' Inchise';
		$responce->userdata['exps'] = $expeditii_gasite;
		//error_log("debug".$this->user_id);
		return json_encode($responce);
	}

	function JSON_Urmarire() {
		$responce = new StdClass();
		$cond = $this->ConditieRambursuri();
		$searchScanari=[];
		$searchScanari[0] = false;
		$searchScanari[1] = false;
		//start generare conditie
        $searchOn = $this->Strip($_REQUEST['_search']);
        if ($searchOn == 'true') {
			$searchstr = $this->Strip($_REQUEST['filters']);
			$cWhere = $this->constructWhere($searchstr);
			if(is_array($cond)) {
				$cond[0] .= $cWhere;
				$cond[1] .= $cWhere;
				$cond[2] .= $cWhere;
			}
			else
            	$cond .= $cWhere;
		}

		$cond = str_ireplace("AND  scanari  = '0'", "", $cond, $count);
		if(!empty($count)) { $searchScanari[0] = true; unset($count);}
		else {
			$cond = str_ireplace("AND  scanari  = '1'", "", $cond, $count);
			if(!empty($count)) { $searchScanari[1] = true; unset($count);}
		}

        if(!empty($_REQUEST['tip_plata'])) {
			if(is_array($cond)) {
				$cond[0] .= "AND init.tip_plata  = ".intval($_REQUEST['tip_plata']);
				$cond[1] .= "AND init.tip_plata  = ".intval($_REQUEST['tip_plata']);
				$cond[2] .= "AND init.tip_plata  = ".intval($_REQUEST['tip_plata']);
			}
			else
				$cond .= "AND init.tip_plata  = ".intval($_REQUEST['tip_plata']);
		}

		//error_log("before : ".print_r($cond, true));

		if(is_array($cond)) {
			$cond[0] = preg_replace("/rbs.expeditie  LIKE '(\d+)%'/", "(rbs.expeditie = $1 OR rtn.expeditie = $1)", $cond[0]);
			$cond[1] = preg_replace("/rbs.expeditie  LIKE '(\d+)%'/", "(rbs.expeditie = $1 OR rtn.expeditie = $1)", $cond[1]);
			$cond[2] = preg_replace("/rbs.expeditie  LIKE '(\d+)%'/", "(rbs.expeditie = $1 OR rtn.expeditie = $1)", $cond[2]);
		}
		else{
			$cond = preg_replace("/rbs.expeditie  LIKE '(\d+)%'/", "(rbs.expeditie = $1 OR rtn.expeditie = $1)", $cond);
		}

		//error_log("after : ".print_r($cond, true));

		$cond = str_ireplace("init.tip_plata  = '4'","(init.tip_plata  = 0 or init.tip_plata  = 3)", $cond);
		$cond = str_ireplace("init.tip_plata  = 4","(init.tip_plata  = 0 or init.tip_plata  = 3)", $cond);
        $cond = str_ireplace("rbs.tip_exp  = '0'", "( rbs.tip_exp is null and rtn.tip_exp is null )",$cond);
		$cond = str_ireplace("rbs.tip_exp  = '5'", " rtn.tip_exp  = 5 ", $cond);

        $page = intval($_REQUEST['page'] ?? 1);
		$limit = intval($_REQUEST['rows'] ?? 20);
		$sidx = trim($this->sanitize($_REQUEST['sidx'] ?? 1));
		$sord = trim($this->sanitize($_REQUEST['sord'] ?? 'asc'));
		if ($sidx == 'scanari') $sidx = 1;

		$query = "SELECT count(init.expeditie) as nr
			FROM {$this->tables['exp_prelucrate']} init
			left join clienti cle on cle.cod_cl = init.expeditor_id
            left join clienti cld on cld.cod_cl = init.destinatar_id
			LEFT JOIN zones clez ON clez.id = cle.zona_id
        	LEFT JOIN centre clec on clec.id = clez.centru_id
			LEFT JOIN zones cldz ON cldz.id = cld.zona_id
        	LEFT JOIN centre cldc on cldc.id = cldz.centru_id
			left join clienti clp on clp.cod_cl = init.platitor_id
            left join localitati lce ON lce.cod_lc = cle.cod_lc
            left join localitati lcd ON lcd.cod_lc = cld.cod_lc
			left join centre cee ON cee.id = lce.cod_centru
			left join centre ced ON ced.id = lcd.cod_centru
			LEFT JOIN ist_exp ie ON ie.cod_ist = (
				SELECT
				MIN(i.cod_ist)
				FROM ist_exp i
				WHERE i.cod_exp = init.cod_expeditie AND i.operatiune = 3)
			LEFT JOIN {$this->tables['exp_prelucrate']} rbs
				ON rbs.referire = init.expeditie  AND rbs.tip_exp = 3 and rbs.anulata = 0
			LEFT JOIN {$this->tables['exp_prelucrate']} rtn
				ON rtn.referire = init.expeditie AND rtn.tip_exp = 5 and rtn.anulata = 0
			 ";
		if(is_array($cond)) $query = "(".$query." WHERE ".$cond[0].") UNION (".$query." WHERE ".$cond[1].") UNION (".$query." WHERE ".$cond[2]." )";
		else $query .= " WHERE ".$cond;

		//error_log($query);
		$result = $this->db->QFetchArray($query);
		$count = !empty($result['nr']) ? $result['nr'] : 0;

		if( $count > 0 ) {$total_pages = ceil($count/$limit); }
        else { $total_pages = 0; }
        if ($page > $total_pages) $page=$total_pages;
        if ($limit<0) $limit = 0;
        $start = $limit*$page - $limit; // do not put $limit*($page - 1)
        if ($start<0) $start = 0;

		$total_ramburs = 0.00;
        $total_status=[];
		$total_status[0] = 0;
		$total_status[1] = 0;
		$rev_status = array_flip(array_map('strtoupper', parent::STATUS_RAMBURS));
		$exps = [];

		$query = $this->getRambursuriSQLQuery($cond, $sidx, $sord, $start, $limit);

		
		if($this->user_id == parent::MARIAN)
			$this->log("Rambursuri : {$query}", parent::APP_LOG_FILE);
		
		//error_log($query);
		$sql = $this->db->QFetchRowArray($query);
		if (!empty($sql)) {
			$nr_exps=[];
            foreach ($sql as $key => $row) {
				$nr_exps[$row['expeditie']]=$key;
                $responce->rowss[$key]['id'] = $row['expeditie'];
				if(!isset($total_status[$row['status_ramburs']])){
					$total_status[$row['status_ramburs']] = 0;
				}
				$total_ramburs +=$row['ramburs'];
				if(empty($row['status_ramburs'])) $row['status_ramburs'] = 0;
				$total_status[$row['status_ramburs']]++;

                $referire  = $row['rbs_expeditie'];
                $data_referire = $row['rbs_data_expeditie'];
                $data_referinte_tip =  $row['rbs_tip_exp'];

				$responce->rowss[$key]['cell'] = array($row['destinatar_centru'], strtoupper($row['expeditor_centru']),$row['expeditie'],$row['data_expeditie'],
				$referire,$data_referire,$data_referinte_tip,$row['ramburs'],$row['tip_plata'],strtoupper(parent::STATUS_RAMBURS[$row['status_ramburs']] ?? 'unknown'),
				strtoupper($row['expeditor']),strtoupper($row['destinatar']), strtoupper($row['platitor']),"0");
			}
			if(count($nr_exps) > 0){
				//coloana scanari
				$exps = implode(",",array_keys($nr_exps));
				$query_scan = "SELECT count(sc.id) as nr_scanari, sc.expeditie
					FROM scanari_coduri sc
					WHERE sc.is_awb = 1 and sc.tip <> 29 and sc.expeditie in (".$exps.")
					group by sc.expeditie";
				//error_log($query);
				$sql_scan = $this->db->QFetchRowArray($query_scan);
				if (!empty($sql_scan)) {
					foreach ($sql_scan as $key => $row) {
						//filter scanari
						if($searchScanari[0]) {
							$total_ramburs -=$responce->rowss[$nr_exps[$row['expeditie']]]['cell'][7];
							$total_status[$rev_status[$responce->rowss[$nr_exps[$row['expeditie']]]['cell'][9]]]--;
							unset($responce->rowss[$nr_exps[$row['expeditie']]]);
							$count--;
						}
						else{
							$index = count($responce->rowss[$nr_exps[$row['expeditie']]]['cell']) - 1;
							$responce->rowss[$nr_exps[$row['expeditie']]]['cell'][$index] = "1";
						}

					}
				}
			}
			//filter scanari
			if($searchScanari[1]){
				foreach ($sql as $key => $row) {
					if(!empty($responce->rowss[$key])){
						$index = count($responce->rowss[$key]['cell']) - 1;
						if($responce->rowss[$key]['cell'][$index] == "0"){
							$total_ramburs -=$responce->rowss[$key]['cell'][7];
							$total_status[$rev_status[$responce->rowss[$key]['cell'][9]]]--;
							unset($responce->rowss[$key]);
							$count--;
						}
					}
				}
			}
			//redefine keys
			$key = 0;
			foreach ($responce->rowss as $row_old) {
				$responce->rows[$key++] = $row_old;
			}
			unset($responce->rowss);
		}

		if( $count > 0 ) {$total_pages = ceil($count/$limit); }
        else { $total_pages = 0; }
        if ($page > $total_pages) $page=$total_pages;
        if ($limit<0) $limit = 0;
        $start = $limit*$page - $limit; // do not put $limit*($page - 1)
        if ($start<0) $start = 0;

		if(isset($_REQUEST['data_start']) && isset($_REQUEST['data_final'])){
            $data_start = date('d.m.Y',strtotime( $this->TransformDate($_REQUEST['data_start'])));
        	$data_final = date('d.m.Y',strtotime( $this->TransformDate($_REQUEST['data_final'])));
        } else {
            $data_start = $data_final = date("Y-m-d");
        }

        $responce->page = $page;
        $responce->total = $total_pages;
        $responce->records =  $count;

        $responce->userdata['destinatar_centru'] = 'Total';
        $responce->userdata['expeditor_centru'] = $count.' Expeditii';
		$responce->userdata['expeditie'] = '';
        $responce->userdata['total_ramburs'] = number_format($total_ramburs, 2, '.', ' ');
		$responce->userdata['data_expeditie'] = 'Suma: ';
		$responce->userdata['referire'] = number_format($total_ramburs, 2, '.', ' ');
		$responce->userdata['ramburs'] = '';
		$responce->userdata['status_ramburs'] = '';
		$responce->userdata['expeditor'] = $total_status[0].' In derulare';
		$responce->userdata['destinatar'] = $total_status[1].' Inchise';
        $responce->userdata['_search'] = $_REQUEST['_search'];
        $responce->userdata['filters'] = base64_encode($_REQUEST['filters']);
        $responce->userdata['rambursuri_'.(!empty($_REQUEST['dashboard_type'])?$_REQUEST['dashboard_type']:'orig').'_date_range'] = $data_start." - ".$data_final;
		$responce->userdata['nr_exps_in'] = $this->nr_exps_in;

		$ret = json_encode($responce);
		//error_log($ret);
		return $ret;
    }

	function RambursuriCreareTiparire(){
		$this->vars['title_page'] = 'Creare / Tiparire note RBS';
		$vars = [];
		$vars['data_start'] = date('d.m.Y');
		$vars['data_final'] = date('d.m.Y');
        $vars['OPTIONS_STATUS_RAMBURS'] = "";
        $vars['OPTIONS_STATUS_RAMBURS_JS'] = "";
        foreach (parent::STATUS_RAMBURS as $id=>$val){
            $vars['OPTIONS_STATUS_RAMBURS'] .= "<option value='{$id}'>{$val}</option>";
            $vars['OPTIONS_STATUS_RAMBURS_JS'] .= ";{$id}:$val";
        }

		return $this->Parse($this->page_prefix . 'creare_tiparire.html', $vars);
	}


	function getTotalNoteRbs(){
		$sql = $this->db->QFetchArray("SELECT count(expeditie) as nr, sum(ramburs) as ramburs FROM {$this->tables['exp_prelucrate']} WHERE wme = -2 and anulata = 0");
		return array('nr' => $sql['nr'], 'ramburs' => number_format($sql['ramburs'], 2));
	}

    function Vizualizare() {
        $this->vars['title_page'] = 'Vizualizare Expeditii cu ramburs';
        $vars = [];
        $vars['data_start'] = (!empty($_REQUEST['data_start']))?$_REQUEST['data_start']:date('d.m.Y');
        $vars['data_final'] = (!empty($_REQUEST['data_final']))?$_REQUEST['data_final']:date('d.m.Y');
        $vars['table_filters'] = (!empty($_REQUEST['filters']))?$_REQUEST['filters']:'';
        $vars['OPTIONS_STATUS_RAMBURS'] = "";
        $vars['OPTIONS_STATUS_RAMBURS_JS'] = "";

        $vars['date_range_for_initiala'] = (empty($_REQUEST['date_range_for']) || (!empty( $_REQUEST['date_range_for']) &&  $_REQUEST['date_range_for'] == 1) )?'selected="selected"':'';
        $vars['date_range_for_ramburs'] = (!empty( $_REQUEST['date_range_for']) &&  $_REQUEST['date_range_for'] == 2)?'selected="selected"':'';

        $vars['OPTIUNI_STATUS_RBS'] =  (!empty($_REQUEST['status_rmb']))?$_REQUEST['status_rmb']:'';

        if(empty($_REQUEST['status_rmb']))
            $_REQUEST['status_rmb'] = 1;

        $optiuni = "";
        foreach ($this->filtre_urmarire_rbs  as $id => $filtru){
            $selected = '';
            if(!empty($_REQUEST['status_rmb']) && $id == $_REQUEST['status_rmb'])
                $selected = ' selected="selected"';
            if(!empty($_REQUEST['status']) && $id == $_REQUEST['status'])
                $selected = ' selected="selected"';

            $optiuni .= '<option value="'.$id.'" '.$selected.'>'.$filtru.'</option>';
        }
        $vars['OPTIUNI_STATUS_RBS'] = $optiuni;

        $vars['OPTIONS_STATUS_RAMBURS'] = "";
        $vars['OPTIONS_STATUS_RAMBURS_JS'] = "";
        foreach (parent::STATUS_RAMBURS as $id=>$val){
            $vars['OPTIONS_STATUS_RAMBURS'] .= "<option value='{$id}'>{$val}</option>";
            $vars['OPTIONS_STATUS_RAMBURS_JS'] .= ";{$id}:$val";
		}

        return $this->Parse($this->page_prefix . 'vizualizare.html', $vars);
    }

	function DetaliiExpeditie(){
		if(empty($_POST['exp']))
			return '';
		$expeditie = $_POST['exp'];
		$query="SELECT expeditie, data_op FROM {$this->tables['exp_prelucrate']} WHERE referire={$expeditie} AND tip_exp=3 and anulata = 0 LIMIT 1";

        $sql = $this->db->QFetchArray($query);
		if(empty($sql)) return '';

		$status = 'Colectata';
		if($sql['data_op'] != '0000-00-00') $status = 'Livrata';
		return '<a href="javascript:;" onclick="AfisareDetaliiExpeditieCuID('.$sql['expeditie'].');">'.$sql['expeditie'].'</a> - '.$status;
	}

	function ModificareMultiplaStatusRamburs(){
		$responce = new StdClass();
        $responce->errorCode = 0;
        $responce->errorMsg = "ok";
        $responce->records = "";
        $error_coduri = [];

		$newStatusRbs = intval($_POST['status'] ?? -1);
		$expeditii = json_decode($_POST['expeditii'] ?? "", true);

		if($newStatusRbs == -1) {
			$responce->errorCode = 1;
            $responce->errorMsg = "Selectioneaza noul status !";
            return json_encode($responce);
		}

		if(empty($expeditii)) {
			$responce->errorCode = 1;
            $responce->errorMsg = "Lista de coduri pentru validare este goala !";
            return json_encode($responce);
		}

		if($newStatusRbs == 30 && !in_array($this->user_id, parent::CAN_MODIFY_STATUS_RBS_APROBAT)) {
			$responce->errorCode = 1;
            $responce->errorMsg = "Nu ai dreptul sa modifici status ramburs in Aprobat !";
            return json_encode($responce);
		}

		if($newStatusRbs == 2 && !in_array($this->user_id, parent::CAN_MODIFY_STATUS_RBS_VALIDAT)) {
			$responce->errorCode = 1;
            $responce->errorMsg = "Nu ai dreptul sa modifici status ramburs in Validat !";
            return json_encode($responce);
		}

		if($newStatusRbs == 1 && !in_array($this->user_id, parent::CAN_MODIFY_STATUS_RBS_INCHIS)) {
			$responce->errorCode = 1;
            $responce->errorMsg = "Nu ai dreptul sa modifici status ramburs in Inchis !";
            return json_encode($responce);
		}

		foreach ($expeditii as $exp)
		{
			$exp = intval($exp);
			if($exp == 0) continue;

			$query = "SELECT ep.cod_expeditie as initialaId, ep.status_ramburs as oldStatusRbs, ep.tip_plata as tipPlata,
				rbs.cod_expeditie as rbsId,
                IF(cle.zona_id > 0 and clez.centru_id > 0, clec.financiar, cee.financiar) as centruFinanciar,
				IF(cle.zona_id > 0 and clez.centru_id > 0, clecm.financiar, ceem.financiar) as masterCentruFinanciar
				FROM exp_prelucrate ep
				left join exp_prelucrate rbs on rbs.referire = ep.expeditie and rbs.tip_exp = 3 and rbs.anulata = 0
				inner join clienti cle on cle.cod_cl = ep.expeditor_id
				LEFT JOIN zones clez ON clez.id = cle.zona_id
        		LEFT JOIN centre clec on clec.id = clez.centru_id
				LEFT JOIN centre clecm on clecm.id = clec.mst_financiar_id
                inner join localitati lce ON lce.cod_lc = cle.cod_lc 
                inner join centre cee on cee.id = lce.cod_centru
				LEFT JOIN centre ceem on ceem.id = cee.mst_financiar_id
				WHERE ep.ramburs > 0 AND ep.expeditie = :awb and ep.tip_exp = 0 and ep.anulata = 0";

			$sql = $this->db->QFetchArray($query, ['awb' => $exp]);
			if(empty($sql['initialaId'])){
				$error_coduri[] = "{$exp} : nu exista sau a fost stearsa !";
				continue;
			}
			if($newStatusRbs == 30 && !empty($sql['tipPlata'])) {
				$error_coduri[] = "{$exp} : nu se poate aproba RBS cash daca tip plata {$sql['tipPlata']}!";
				continue;
			}
			if($newStatusRbs == 30 && empty($sql['centruFinanciar']) && empty($sql['masterCentruFinanciar'])) {
				$error_coduri[] = "{$exp} : nu se pot aproba RBS-uri cash pentru centrul expeditorului initialei !";
				continue;
			}

			//if oldStatusRbs = 11 (spre compensare) and newStatusRbs in (2,30) (validat, aprobat) anuleaza awb RBS
			if(!empty($sql['rbsId']) && $sql['oldStatusRbs'] == 11 && in_array($newStatusRbs, [2,30])) {
				$this->db->QueryUpdate('exp_prelucrate', ['deleted_at' => date('Y-m-d H:i:s'), 'deleted_by'=>$this->user_id, 'anulata' => 1], "cod_expeditie = {$sql['rbsId']}");
			}
			
			$m = $this->setStatusRamburs($sql['initialaId'], $sql['oldStatusRbs'], $newStatusRbs, $sql['tipPlata']);
			if($m == 0){
				$error_coduri[] = "{$exp} : nu exista sau a fost stearsa";
				continue;
			}
			if($m == -1) {
				$error_coduri[] = "{$exp} : statusul nu poate fi modificat : ". (parent::STATUS_RAMBURS[$sql['oldStatusRbs']] ?? 'unknown');
			}
		}

		if(count($error_coduri) > 0) {
            $responce->errorCode = 1;
            $responce->errorMsg = "Status ramburs la expeditiile urmatoare nu a fost modificat in <b>".(parent::STATUS_RAMBURS[$newStatusRbs] ?? 'unknown')."</b>: <br/>";
            $responce->records = implode("<br/>",$error_coduri);
            return json_encode($responce);
        }

        return json_encode($responce);
	}

	function PrintUrmarire(){
		$cond = $this->ConditieRambursuri();
		//start generare conditie
        $searchOn = $this->Strip($_REQUEST['_search'] ?? false);
        if ($searchOn == 'true') {
			$searchstr = $this->Strip($_REQUEST['filters']);
			$cWhere = $this->constructWhere($searchstr);
			if(is_array($cond)) {
				$cond[0] .= $cWhere;
				$cond[1] .= $cWhere;
				$cond[2] .= $cWhere;
			}
			else
            	$cond .= $cWhere;
		}

		$cond = str_ireplace("AND  scanari  = '0'", "", $cond, $count);
		$cond = str_ireplace("AND  scanari  = '1'", "", $cond, $count);

        //error_log($cond);
        if(!empty($_REQUEST['tip_plata'])) {
			if(is_array($cond)) {
				$cond[0] .= "AND init.tip_plata  = ".intval($_REQUEST['tip_plata']);
				$cond[1] .= "AND init.tip_plata  = ".intval($_REQUEST['tip_plata']);
				$cond[2] .= "AND init.tip_plata  = ".intval($_REQUEST['tip_plata']);
			}
			else
				$cond .= "AND init.tip_plata  = ".intval($_REQUEST['tip_plata']);
		}
		if(is_array($cond)) {
			$cond[0] = preg_replace("/rbs.expeditie  LIKE '(\d+)%'/", "(rbs.expeditie = $1 OR rtn.expeditie = $1)", $cond[0]);
			$cond[1] = preg_replace("/rbs.expeditie  LIKE '(\d+)%'/", "(rbs.expeditie = $1 OR rtn.expeditie = $1)", $cond[1]);
			$cond[2] = preg_replace("/rbs.expeditie  LIKE '(\d+)%'/", "(rbs.expeditie = $1 OR rtn.expeditie = $1)", $cond[2]);
		}
		else{
			$cond = preg_replace("/rbs.expeditie  LIKE '(\d+)%'/", "(rbs.expeditie = $1 OR rtn.expeditie = $1)", $cond);
		}

		$cond = str_ireplace("init.tip_plata  = '4'","(init.tip_plata  = 0 or init.tip_plata  = 3)", $cond);
		$cond = str_ireplace("init.tip_plata  = 4","(init.tip_plata  = 0 or init.tip_plata  = 3)", $cond);
		$cond = str_ireplace("rbs.tip_exp  = '0'", "( rbs.tip_exp is null and rtn.tip_exp is null )",$cond);
		$cond = str_ireplace("rbs.tip_exp  = '5'", " rtn.tip_exp  = 5 ", $cond);

		$rand=1;
		$query = $this->getRambursuriSQLQuery($cond, null, null, null, null);

		$html = '
				<h1 style="text-align: center">Expeditii cu ramburs</h1>
				<h2 style="text-align: center">Perioada de colectare </h2>
			';

        $sql = $this->db->QFetchRowArray($query);
        if (!empty($sql)) {
			$i=0;
			$continut=[];
			$localitate = '';
			$data = '';
			$nr_exps=[];
            foreach ($sql as $key => $row) {
				$nr_exps[$row['expeditie']]=$key+1;
				$row['data_expeditie'] = $this->CreateDate($row['data_expeditie']);

				if($row['tip_plata'] ==1) $row['tip_plata'] = 'bo';
				else if($row['tip_plata'] == 2) $row['tip_plata'] = 'cec';
				else if($row['tip_plata'] == 3) $row['tip_plata'] = 'cont';
				else $row['tip_plata'] = 'cash';

            	if($localitate!=$row['destinatar_centru']){
            		$rand=$rand+2;
            		$localitate=$row['destinatar_centru'];
            		$html .='<h3>'.$row['destinatar_centru'].'</h3>';
        			$html .= '
        			<table  width="100%" border="1">
        			<tr>
        				<td width="5%"><b>Nr.</b></td>
        				<td width="20%"><b>Destinatar</b></td>
        				<td width="20%"><b>Expeditor</b></td>
        				<td width="10%"><b>Expeditie</b></td>
        				<td width="10%"><b>Col. exp.</b></td>
        				<td width="10%"><b>Ramburs</b></td>
        				<td width="5%"><b>Tip plata</b></td>
        				<td width="10%"><b>NT Ref.</b></td>
        				<td width="10%"><b>Col. ref.</b></td>
        			</tr>
        			</table>';
            	}
				$html .= '
				<table  width="100%" border="1">
        			<tr>
        				<td width="5%"><b>'.($key+1).'</b></td>
        				<td width="20%"><b>'.$row['destinatar'].'</b></td>
        				<td width="20%"><b>'.$row['expeditor'].' ('.$row['expeditor_centru'].')</b></td>
        				<td width="10%"><b>'.$row['expeditie'].'</b></td>
        				<td width="10%"><b>'.$row['data_expeditie'].'</b></td>
        				<td width="10%"><b>'.number_format($row['ramburs'], 2, '.', '').'</b></td>
        				<td width="5%"><b>'.$row['tip_plata'].'</b></td>
        				<td width="10%"><b>'.$row['rbs_expeditie'].'</b></td>
        				<td width="10%"><b>'.$row['rbs_data_expeditie'].'</b></td>
        			</tr>
        		</table>';
				$rand++;
				if($rand>44){
					$html .= '<div style="page-break-before:always"></div>';
					$rand=0;
				}
            }
        }

		return $html;

	}

    function ExportUrmarire() {
		ini_set('memory_limit', '1228M');
		$cond = $this->ConditieRambursuri();
		$searchScanari=[];
		$searchScanari[0] = false;
		$searchScanari[1] = false;
		//start generare conditie
        $searchOn = $this->Strip($_POST['_search'] ?? false);
        if ($searchOn == 'true') {
			$searchstr = $this->Strip(base64_decode($_POST['filters']));
			$cWhere = $this->constructWhere($searchstr);
			//error_log("searchstr : " . $searchstr);
			if(is_array($cond)) {
				$cond[0] .= $cWhere;
				$cond[1] .= $cWhere;
				$cond[2] .= $cWhere;
			}
			else
            	$cond .= $cWhere;
		}

		$cond = str_ireplace("AND  scanari  = '0'", "", $cond, $count);
		if(!empty($count)) {
			$searchScanari[0] = true;
			unset($count);
		}
		else {
			$cond = str_ireplace("AND  scanari  = '1'", "", $cond, $count);
			if(!empty($count)) { $searchScanari[1] = true; unset($count);}
		}

        //error_log($cond);
        if(!empty($_REQUEST['tip_plata'])) {
			if(is_array($cond)) {
				$cond[0] .= "AND init.tip_plata  = ".intval($_REQUEST['tip_plata']);
				$cond[1] .= "AND init.tip_plata  = ".intval($_REQUEST['tip_plata']);
				$cond[2] .= "AND init.tip_plata  = ".intval($_REQUEST['tip_plata']);
			}
			else
				$cond .= "AND init.tip_plata  = ".intval($_REQUEST['tip_plata']);
		}
		//error_log($cond);
		if(is_array($cond)) {
			$cond[0] = preg_replace("/rbs.expeditie  LIKE '(\d+)%'/", "(rbs.expeditie = $1 OR rtn.expeditie = $1)", $cond[0]);
			$cond[1] = preg_replace("/rbs.expeditie  LIKE '(\d+)%'/", "(rbs.expeditie = $1 OR rtn.expeditie = $1)", $cond[1]);
			$cond[2] = preg_replace("/rbs.expeditie  LIKE '(\d+)%'/", "(rbs.expeditie = $1 OR rtn.expeditie = $1)", $cond[2]);
		}
		else{
			$cond = preg_replace("/rbs.expeditie  LIKE '(\d+)%'/", "(rbs.expeditie = $1 OR rtn.expeditie = $1)", $cond);
		}

		$cond = str_ireplace("init.tip_plata  = '4'","(init.tip_plata  = 0 or init.tip_plata  = 3)", $cond);
		$cond = str_ireplace("init.tip_plata  = 4","(init.tip_plata  = 0 or init.tip_plata  = 3)", $cond);
		$cond = str_ireplace("rbs.tip_exp  = '0'", "( rbs.tip_exp is null and rtn.tip_exp is null )",$cond);
		$cond = str_ireplace("rbs.tip_exp  = '5'", " rtn.tip_exp  = 5 ", $cond);

		$spreadsheet = new Spreadsheet();
        $spreadsheet->getProperties()->setCreator("Dragon Star Curier")
            ->setLastModifiedBy("Dragon Star Curier")
            ->setTitle("rambursuri")
            ->setSubject("rambursuri")
            ->setDescription("rambursuri")
            ->setKeywords("rambursuri")
            ->setCategory("rambursuri");

        $spreadsheet->getDefaultStyle()->getFont()->setName('Arial');
        $spreadsheet->getDefaultStyle()->getFont()->setSize(10);
        $worksheet = $spreadsheet->getActiveSheet();

		$worksheet->setCellValue('A1','Nr. NT');
        $worksheet->setCellValue('B1','Colectare exp.');
        $worksheet->setCellValue('C1','Expeditor');
		$worksheet->setCellValue('D1','Centru expeditor');
		$worksheet->setCellValue('E1','Destinatar');
		$worksheet->setCellValue('F1','Centru destinatar');
		$worksheet->setCellValue('G1','Platitor');
		$worksheet->setCellValue('H1','Return NT');
		$worksheet->setCellValue('I1','Retur DOC');
		$worksheet->setCellValue('J1','Status');
		$worksheet->setCellValue('K1','NT Referinta');
		$worksheet->setCellValue('L1','Colectare ref.');
		$worksheet->setCellValue('M1','Tip ref.');
		$worksheet->setCellValue('N1','Valoare');
		$worksheet->setCellValue('O1','Tip plata');
		$worksheet->setCellValue('P1','Zile RBS');
		$worksheet->setCellValue('Q1','Observatii');
		$worksheet->setCellValue('R1','Scanari');
		$worksheet->setCellValue('S1','Master');
		foreach (range('A', 'S') as $letter) {
			$worksheet->getStyle($letter.'1')->getFont()->setSize(12);
			$worksheet->getStyle($letter.'1')->getFont()->setBold(true);
		}

		if($this->user_profile == 6 || $this->user_profile == 106 || $this->user_id == parent::MARIAN || $this->user_id == parent::MARIUS_TELER) {
			$worksheet->setCellValue('T1','CUI');
			$worksheet->setCellValue('U1','Cont RBS Master');
			$worksheet->setCellValue('V1','Banca cont RBS Master');
			$worksheet->setCellValue('W1','BIC Master');
			$worksheet->setCellValue('X1','Nume companie Master');
			foreach (range('T', 'X') as $letter) {
				$worksheet->getStyle($letter.'1')->getFont()->setSize(12);
				$worksheet->getStyle($letter.'1')->getFont()->setBold(true);
			}
		}


		$query = $this->getRambursuriSQLQuery($cond, null, null, null, null);
		//var_dump($query);
		//die();
		$sql = $this->db->QFetchRowArray($query);
        //compun raspunsul
        if (!empty($sql)) {
			$nr_exps=[];
			$rand=2;
            foreach ($sql as $key => $row) {
                $nr_exps[$row['expeditie']]=$rand+$key;

				if(!empty($row['ret_nt'])) $row['ret_nt'] = 'DA';
				else  $row['ret_nt'] = 'NU';

				if(!empty($row['ret_doc'])) $row['ret_doc'] = 'DA';
				else  $row['ret_doc'] = 'NU';


				if($row['tip_plata'] ==1) $row['tip_plata'] = 'bo';
				else if($row['tip_plata'] == 2) $row['tip_plata'] = 'cec';
				else if($row['tip_plata'] == 3) $row['tip_plata'] = 'cont';
				else $row['tip_plata'] = 'cash';

				$worksheet->setCellValue('A'.($rand+$key),$row['expeditie']);
				$worksheet->setCellValue('B'.($rand+$key),$row['data_expeditie']);
				$worksheet->setCellValueExplicit('C'.($rand+$key),$row['expeditor'].' ('.$row['expeditor_localitate'].')', \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
				$worksheet->setCellValue('D'.($rand+$key),$row['expeditor_centru']);
				$worksheet->setCellValueExplicit('E'.($rand+$key),$row['destinatar'].' ('.$row['destinatar_localitate'].')', \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
				$worksheet->setCellValue('F'.($rand+$key),$row['destinatar_centru']);
				$worksheet->setCellValueExplicit('G'.($rand+$key),$row['platitor'].' ('.$row['platitor_localitate'].')', \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
				$worksheet->setCellValue('H'.($rand+$key),$row['ret_nt']);
				$worksheet->setCellValue('I'.($rand+$key),$row['ret_doc']);
				$worksheet->setCellValue('J'.($rand+$key),parent::STATUS_RAMBURS[$row['status_ramburs']] ?? 'unknown');
				$worksheet->setCellValue('K'.($rand+$key),$row['rbs_expeditie']);
				$worksheet->setCellValue('L'.($rand+$key),$row['rbs_data_expeditie']);
                $worksheet->setCellValue('M'.($rand+$key),(ExpeditieDto::TIP_EXP[$row['rbs_tip_exp']] ?? "unknown"));
				$worksheet->setCellValue('N'.($rand+$key),number_format($row['ramburs'], 2, '.', ''));
				$worksheet->setCellValue('O'.($rand+$key),$row['tip_plata']);
				$worksheet->setCellValue('P'.($rand+$key),$row['master_rbs_days']);
				if(!empty($row['pret_rbs']))
					$worksheet->setCellValue('P'.($rand+$key),$row['pmaster_rbs_days']);
				$worksheet->setCellValueExplicit('Q'.($rand+$key),$row['observatii'], \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
				$worksheet->setCellValue('R'.($rand+$key),"NU");
				$worksheet->setCellValue('S'.($rand+$key),$row['master_nume']);
				if(!empty($row['pret_rbs']))
					$worksheet->setCellValue('S'.($rand+$key),$row['pmaster_nume']);
				if($this->user_profile == 6 || $this->user_profile == 106 || $this->user_id == parent::MARIAN || $this->user_id == parent::MARIUS_TELER) {
					$worksheet->setCellValue('T'.($rand+$key),$row['cod_fiscal']);
					$worksheet->setCellValue('U'.($rand+$key),$row['cont_rbs']);
					$worksheet->setCellValue('V'.($rand+$key),$row['banca_rbs']);
					$worksheet->setCellValue('W'.($rand+$key),$row['bic_rbs']);
					$worksheet->setCellValueExplicit('X'.($rand+$key),$row['master_nume_societate'], \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
					if(!empty($row['pret_rbs'])) {
						$worksheet->setCellValue('T'.($rand+$key),$row['pcod_fiscal']);
						$worksheet->setCellValue('U'.($rand+$key),$row['pcont_rbs']);
						$worksheet->setCellValue('V'.($rand+$key),$row['pbanca_rbs']);
						$worksheet->setCellValue('W'.($rand+$key),$row['pbic_rbs']);
						$worksheet->setCellValueExplicit('X'.($rand+$key),$row['pmaster_nume_societate'], \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
					}
				}
			}
			if(count($nr_exps) > 0){
				//coloana scanari
				$exps = implode(",",array_keys($nr_exps));
				$query_scan = "SELECT count(sc.id) as nr_scanari, sc.expeditie
					FROM scanari_coduri sc
					WHERE sc.expeditie in (".$exps.") and sc.tip <> 29 and sc.is_awb = 1
					group by sc.expeditie";
				//error_log($query_scan);
				$sql_scan = $this->db->QFetchRowArray($query_scan);
				if (!empty($sql_scan)) {
					foreach ($sql_scan as $key => $row) {
						//filter scanari
						$worksheet->setCellValue('R'.$nr_exps[$row['expeditie']],"DA");
					}
				}
			}
			//filter scanari
			$toRemove = [];
			foreach ($sql as $key => $row) {
				$scanVal = $worksheet->getCell('Q'.$nr_exps[$row['expeditie']])->getValue();
				if($searchScanari[0] && $scanVal == 'DA' || $searchScanari[1] && $scanVal == 'NU') {
					$toRemove[] = $nr_exps[$row['expeditie']];
				}
			}
			$toRemove = array_reverse($toRemove);
			foreach($toRemove as $r)
				$worksheet->removeRow($r);
        }

		$rand = rand(0,100);
		$filename = "data_export_" . date("Y-m-d") . "-".$rand.".xlsx";

		$this->download_send_headers_xls($filename);
        $writer = new Xlsx($spreadsheet);
        $writer->save('php://output');
		die;
	}

	/*/////////////////////////////////////////////////////////////
				 PRINT CH RAMBURS
/////////////////////////////////////////////////////////////*/

    function DecontCautare(){
		$this->vars['title_page'] = 'Tiparire chitante RBS';
    	return $this->Parse($this->page_prefix . 'decont_cautare.html', []);
	}

	function JSON_DecontCautare() {
		$responce = new StdClass();
		$cond = "1=1 ";
		$flag=0;

		if(isset($_GET['data_start']) && isset($_GET['data_final'])){
            $data_start = $this->TransformDate($this->sanitize($_GET['data_start'])). " 00:00:01";
            $data_final = $this->TransformDate($this->sanitize($_GET['data_final'])). " 23:59:59";
            $cond .= " AND dr.dataInc >= '".$data_start."' AND dr.dataInc <= '".$data_final."'";
            $flag=1;
        }
        if(isset($_GET['operatiune']) && !empty($_GET['search'])){
            if($_GET['operatiune']==1){
				$cond .= " AND dr.ch_ramburs = '". $this->sanitize($_GET['search']) ."'";
				$flag=1;
			}
			else if($_GET['operatiune']==2){
				$cond .= " AND e.expeditie=".intval($this->sanitize($_GET['search']));
				$flag=1;
			}

		}
		if(!empty($_GET['cod_ag'])){
            $cond .= " AND de.agent_id=".intval($this->sanitize($_GET['cod_ag']));
            $flag=1;
		}
		if(!empty($_GET['cod_cl'])){
            $cond .= " AND e.expeditor_id=".intval($this->sanitize($_GET['cod_cl']));
            $flag=1;
		}

		if(!empty($_POST['expeditii'])){
			$expeditii = parent::ValidareExpeditiiCurata($_POST['expeditii']);
			if(!empty($expeditii)) {
				$cond = " de.expeditie in (".$expeditii.") ";
				$flag=1;
			}
		}

		if(empty($flag)) $cond = "1=2";

		$searchOn = '';
		//start generare conditie
		if(isset($_POST['_search']))
        	$searchOn = $this->sanitize($_POST['_search']);
        if ($searchOn == 'true') {
            $searchstr = $this->Strip($_POST['filters']);
            $cond .= $this->constructWhere($searchstr);
		}

		$cond = str_replace("decontata  = '0'","dr.decont_id = 0", $cond);
		$cond = str_replace("decontata  = '1'","dr.decont_id > 0", $cond);
		$cond = str_replace("tipPlata  = '0'","dr.transaction_id = 0", $cond);
		$cond = str_replace("tipPlata  = '1'","dr.transaction_id > 0", $cond);

        $page = intval($_REQUEST['page'] ?? 1);
		$limit = intval($_REQUEST['rows'] ?? 100);
		$sidx = trim($this->sanitize($_REQUEST['sidx'] ?? 1));
		$sord = trim($this->sanitize($_REQUEST['sord'] ?? 'asc'));

		$query = "select count(de.id) as nr, sum(dr.ramburs) as total_ramburs
			from decont_rbs dr
			inner join decont_expeditii de on dr.id = de.ramburs_id
			left JOIN {$this->tables['exp_prelucrate']} e on (de.expeditie = e.expeditie and e.anulata = 0)
			left join clienti cle on e.expeditor_id = cle.cod_cl
			left join clienti cld on e.destinatar_id = cld.cod_cl
			LEFT JOIN zones clez ON clez.id = cle.zona_id
        	LEFT JOIN centre clec on clec.id = clez.centru_id
			LEFT JOIN zones cldz ON cldz.id = cld.zona_id
        	LEFT JOIN centre cldc on cldc.id = cldz.centru_id
			left join agenti ag on de.agent_id = ag.cod_ag
			left join localitati lce on lce.cod_lc = cle.cod_lc
        	left join centre cee on cee.id = lce.cod_centru
			left join localitati lcd on lcd.cod_lc = cld.cod_lc
			left join centre ced on ced.id = lcd.cod_centru
			where dr.ch_ramburs <> '' and {$cond} and dr.anulata = 0";

        $result = $this->db->QFetchArray($query);
		$count = !empty($result['nr']) ? $result['nr'] : 0;
		$total_ramburs = !empty($result['total_ramburs']) ? number_format($result['total_ramburs'], 2, '.', '') : '0.00';

		if( $count >0 ) {$total_pages = ceil($count/$limit); }
        else { $total_pages = 0; }
        if ($page > $total_pages) $page=$total_pages;
        if ($limit<0) $limit = 0;
        $start = $limit*$page - $limit; // do not put $limit*($page - 1)
        if ($start<0) $start = 0;

        $query = "select dr.id, dr.dataInc, dr.ch_ramburs, dr.ramburs, de.expeditie,
			cle.nume as expeditor, cld.nume as destinatar, ag.nume_ag as curier, e.primitor,
			IF(cle.zona_id > 0 and clec.id > 0, clec.nume, cee.nume) as expeditor_centru, 
			IF(cld.zona_id > 0 and cldc.id > 0, cldc.nume, ced.nume) as destinatar_centru, 
			e.tip_plata, IF(de.decont_id > 0, 1, 0) as decontata,
			if(dr.transaction_id > 0, 1, 0) as tipPlata
			from decont_rbs dr
			inner join decont_expeditii de on dr.id = de.ramburs_id
			left JOIN {$this->tables['exp_prelucrate']} e on (de.expeditie = e.expeditie and e.anulata = 0)
			left join clienti cle on e.expeditor_id = cle.cod_cl
			left join clienti cld on e.destinatar_id = cld.cod_cl
			LEFT JOIN zones clez ON clez.id = cle.zona_id
        	LEFT JOIN centre clec on clec.id = clez.centru_id
			LEFT JOIN zones cldz ON cldz.id = cld.zona_id
        	LEFT JOIN centre cldc on cldc.id = cldz.centru_id
			left join agenti ag on de.agent_id = ag.cod_ag
			left join localitati lce on lce.cod_lc = cle.cod_lc
			left join centre cee on cee.id = lce.cod_centru
			left join localitati lcd on lcd.cod_lc = cld.cod_lc
			left join centre ced on ced.id = lcd.cod_centru
			where dr.ch_ramburs <> '' and {$cond} and dr.anulata = 0
			ORDER BY " . $sidx . " " . $sord . " LIMIT " . $start . " , " . $limit;

        $sql = $this->db->QFetchRowArray($query);
        //error_log($query);
        if (!empty($sql)) {
            foreach ($sql as $key => $row) {
                $responce->rows[$key]['id'] = $row['id'];
                $responce->rows[$key]['cell'] = array($row['ch_ramburs'], $row['ramburs'], $row['tip_plata'], $row['dataInc'], $row['expeditie'], strtoupper($row['expeditor']), strtoupper($row['destinatar']), strtoupper($row['expeditor_centru']), strtoupper($row['destinatar_centru']), strtoupper($row['curier']), $row['primitor'], $row['tipPlata'], $row['decontata']);
            }
		}

		$responce->page = $page;
        $responce->total = $total_pages;
        $responce->records = $count;
		$responce->userdata ['ramburs'] = $total_ramburs;
        return json_encode($responce);
	}

	function PrintOneChitantaRambursTCPDF() {
		require_once "ChRambursPdf.php";
		$pData = base64_decode($_POST['pJson'] ?? '');
		if(empty($pData)) return;
		$pData = json_decode($pData, true);
		if(!is_array($pData)) return;


		$id = intval($pData['id'] ?? 0);
		if($id == 0) return;

		$client = $this->sanitize(strtoupper(str_replace("&AMP;", '&', strtoupper($pData['client'] ?? ""))));
		$cui = $this->sanitize($pData['cui'] ?? "");
		$adresa = $this->sanitize($pData['adresa'] ?? "");

		$pdf = new ChRambursPdf();
		$filename = $this->GenerareChRambursTCPDF($pdf, $id, $client, $cui, $adresa);
		// move pointer to last page
		$pdf->lastPage();

		//I: send the file inline to the browser. The plug-in is used if available. The name given by filename is used when one selects the "Save as" option on the link generating the PDF.
		//D: send to the browser and force a file download with the name given by filename.
		$type = 'D';
		$pdf->Output($filename, $type);
	    exit;
	}

	function GenerareChRambursTCPDF($pdf, $id, $client = "", $cui = "", $adresa = ""){
        if(empty($id)) return $this->Error('Chitanta ramburs invalida!');

        $cond = "dr.id=".$id;

		$query = "SELECT dr.dataInc, dr.ch_ramburs, de.expeditie, de.ramburs, de.client, de.cui, de.adresa,
			ag.nume_ag as agent, c.label as centru, ep.tip_plata, cl.nume as expeditor_nume
			from decont_rbs dr
			inner join decont_expeditii de on de.ramburs_id = dr.id
			inner join agenti ag on de.agent_id = ag.cod_ag
			inner JOIN exp_prelucrate ep on ep.expeditie = de.expeditie
			left join clienti cl on cl.cod_cl = ep.expeditor_id
			left join centre c on ag.cod_centru = c.id
			where {$cond} and dr.anulata = 0 limit 1";

        $ch_rmb = $this->db->QFetchArray($query);
        if(empty($ch_rmb)) return $this->Error('Chitanta ramburs invalida!');

	    $vars = [];
		$vars['ch_ramburs'] = $ch_rmb['ch_ramburs'];
		$vars['data'] = new DateTime($ch_rmb['dataInc']);
		$vars['data'] = $vars['data']->format('d.m.Y H:i:s');
		$vars['expeditie'] = $ch_rmb['expeditie'];
		$vars['tip_plata'] = $ch_rmb['tip_plata'];
		$vars['ramburs'] = $ch_rmb['ramburs'];
		$vars['expeditor'] = strtoupper($ch_rmb['expeditor_nume']);
		$vars['centru'] = strtoupper($ch_rmb['centru']);
		$vars['agent'] = strtoupper($ch_rmb['agent']);
		$vars['client'] = empty($client) ? strtoupper($ch_rmb['client']) : strtoupper($client);
		$vars['cui'] = empty($cui) ? strtoupper($ch_rmb['cui']) : strtoupper($cui);
		$vars['adresa'] = empty($adresa) ? strtoupper($ch_rmb['adresa']) : strtoupper($adresa);

		$pdf->setVars($vars);
		$pdf->AddPage();
		$pdf->makeChRamburs();

		return $vars['ch_ramburs'].'.pdf';
    }

	function PrintSelectedChitantaRambursTCPDF() {
		require_once "ChRambursPdf.php";
		$pData = isset($_POST['pJson']) ? base64_decode($_POST['pJson']):'';
		if(empty($pData)) return;
		$pData = json_decode($pData, true);
		if(!is_array($pData)) return;
		if(!count($pData)) return;

		$filename = 'CH-RBS-printare_multipla.pdf';
		$pdf = new ChRambursPdf();

        foreach($pData as $ch_ramb) {
            $this->GenerareChRambursTCPDF($pdf, $ch_ramb);
        }

		// move pointer to last page
		$pdf->lastPage();

		$pdf->Output($filename,'D');
		exit;
	}

	/*/////////////////////////////////////////////////////////////
				 FIN PRINT CH RAMBURS
/////////////////////////////////////////////////////////////*/

	function RambursuriLista(){
		$this->vars['title_page'] = 'Lista note RBS';
		$vars = [];
		$vars['OPTIONS_STATUS_RAMBURS'] = "";
        $vars['OPTIONS_STATUS_RAMBURS_JS'] = "";
		foreach (parent::STATUS_RAMBURS as $id=>$val){
            $vars['OPTIONS_STATUS_RAMBURS'] .= "<option value='{$id}'>{$val}</option>";
            $vars['OPTIONS_STATUS_RAMBURS_JS'] .= ";{$id}:$val";
        }
		return $this->Parse($this->page_prefix . 'lista.html', $vars);
	}

	function DetaliiChitantaRamburs($id){
		$id = intval($id);
		if($id == 0) return 0;

        $query = "SELECT dr.id, dr.dataInc, dr.ch_ramburs, de.ramburs, de.client, dr.agent_id, de.cui, de.adresa, de.expeditie,
			cl.nume as factura_client, cl.localitate as factura_localitate, dr.transaction_id,
			cl.adresa as factura_adresa, df.cui as factura_cui, df.serie as factura_serie, ag.nume_ag as agent
			from decont_rbs dr
			inner join decont_expeditii de on de.ramburs_id = dr.id
			inner join agenti ag on dr.agent_id = ag.cod_ag
			left join decont_facturi df on df.id = de.factura_id
			left join clienti cl on cl.cod_cl = df.client_id
			where dr.id = {$id} and dr.anulata = 0";
        $sql = $this->db->QFetchArray($query);
        if(empty($sql)) return 0;

		$sql['dataInc'] = new DateTime($sql['dataInc']);
		$sql['dataInc'] = $sql['dataInc']->format('d.m.Y H:i');

        return '1|||'.$sql['id'].'|||'.$sql['agent_id'].'|||'.$sql['dataInc'].'|||'.$sql['expeditie'].'|||'.$sql['agent'].'|||'.$sql['ch_ramburs'].'|||'.$sql['ramburs'].'|||'.$sql['client'].'|||'
        		.$sql['cui'].'|||'.$sql['adresa'].'|||'.($sql['transaction_id'] > 0 ? 'CARD' : 'CASH').'|||'.$sql['factura_serie']
				;

	}

	function StergeChitantaDecont($id) {
		$id = intval($id);
		if($id == 0) return 0;

		if(!($this->user_id == parent::DOINA || $this->user_id == parent::MARIAN || $this->user_id == parent::MARIUS_TELER)) return 0;
		
		$query_dr = "SELECT id, ch_ramburs, transaction_id
			from decont_rbs where id = {$id} and anulata = 0";
		$sql_dr = $this->db->QFetchArray($query_dr);
		if(empty($sql_dr) || $sql_dr['transaction_id'] > 0) return 0;

		$this->db->QueryUpdate('decont_rbs', ['anulata' => 3], "id = {$id}");
		//TODO : anuleaza factura
		/*
		if(!empty($sql_de['factura_id'])){
			//anulez factura
			$this->db->QueryUpdate('decont_facturi', ['anulata' => 3], "id = ".$sql_de['factura_id']);
		}
		*/
		return 1;
	}

	//#######################RBS Validate#########################
	function RbsValidate() {
        $this->vars['title_page'] = 'Rambursuri decontate validate';
        return $this->Parse($this->page_prefix . 'validate.html', []);
    }

	function JSON_RbsValidate(){
		$cond ="1=1 ";
		$flag = false;
		$responce = new StdClass();
		if(!empty($_POST['data_start']) && !empty($_POST['data_final'])){
			$data_start = $this->TransformDate($_POST['data_start']) . " 00:00:00";
			$data_final = $this->TransformDate($_POST['data_final']) . " 23:59:59";
			$cond .= " AND dep.data between '".$data_start."' AND '".$data_final."'";
			$flag = true;
		}
		if(!empty($_POST['centru_id'])){
			$cond .= " AND da.centru_id = ".intval($this->sanitize($_POST['centru_id']));
		}
		$cond .= " and ep.anulata = 0 and iep.operatiune = 24";

		$searchOn = '';
		//start generare conditie
		if(isset($_POST['_search']))
        	$searchOn = $this->Strip($_POST['_search']);
        if ($searchOn == 'true') {
            $searchstr = $this->Strip($_POST['filters']);
            $cond .= $this->constructWhere($searchstr);
		}

		if(false === $flag) $cond = "1=2";

		$page = intval($_POST['page'] ?? 1);
		$limit = intval($_POST['rows'] ?? 100);
		$sidx = trim($this->sanitize($_POST['sidx'] ?? 1));
		$sord = trim($this->sanitize($_POST['sord'] ?? 'asc'));

		$query = "SELECT COUNT(distinct ep.expeditie) as nr
			FROM exp_prelucrate ep
			inner join ist_exp iep on iep.cod_exp = ep.cod_expeditie
			inner join ist_exp_value_int iepd on iepd.cod_ist = iep.cod_ist and iepd.attribute like 'status_ramburs' and iepd.value = 2
			inner join decont_expeditii dep on dep.expeditie = ep.expeditie
			inner join decont_rbs dr on dep.ramburs_id = dr.id and dr.anulata = 0 and dr.vCR = 1
			left join decont_agent da on da.id = dep.decont_id
			left join centre ceda on ceda.id = da.centru_id
			left join clienti cle on cle.cod_cl = ep.expeditor_id
			left join localitati lce on lce.cod_lc = cle.cod_lc
			left join centre cee on cee.id = lce.cod_centru
			left join clienti cld on cld.cod_cl = ep.destinatar_id
			left join localitati lcd on lcd.cod_lc = cld.cod_lc
			left join centre ced on ced.id = lcd.cod_centru
			LEFT JOIN zones clez ON clez.id = cle.zona_id
        	LEFT JOIN centre clec on clec.id = clez.centru_id
			LEFT JOIN zones cldz ON cldz.id = cld.zona_id
        	LEFT JOIN centre cldc on cldc.id = cldz.centru_id
			where {$cond}
		";
        $result = $this->db->QFetchArray($query);
        $count = !empty($result['nr']) ? $result['nr'] : 0;

        if( $count >0 ) {$total_pages = ceil($count/$limit); }
        else { $total_pages = 0; }
        if ($page > $total_pages) $page=$total_pages;
        if ($limit<0) $limit = 0;
        $start = $limit*$page - $limit; // do not put $limit*($page - 1)
        if ($start<0) $start = 0;
        $query = "SELECT ep.expeditie, ep.data_expeditie, group_concat(ceda.label) as decont_centru_cod, group_concat(dep.data) as decont_data, group_concat(da.id) as decont_id,
			IF(cle.zona_id > 0 and clec.id > 0, clec.label, cee.label) as expeditor_centru_cod,
			IF(cld.zona_id > 0 and cldc.id > 0, cldc.label, ced.label) as destinatar_centru_cod,
			cle.nume as expeditor,
			ep.ramburs, ep.tip_plata, group_concat(iep.data) as validare_data
			FROM exp_prelucrate ep
			inner join ist_exp iep on iep.cod_exp = ep.cod_expeditie
			inner join ist_exp_value_int iepd on iepd.cod_ist = iep.cod_ist and iepd.attribute like 'status_ramburs' and iepd.value = 2
			inner join decont_expeditii dep on dep.expeditie = ep.expeditie
			inner join decont_rbs dr on dep.ramburs_id = dr.id and dr.anulata = 0 and dr.vCR = 1
			left join decont_agent da on da.id = dep.decont_id
			left join centre ceda on ceda.id = da.centru_id
			left join clienti cle on cle.cod_cl = ep.expeditor_id
			left join localitati lce on lce.cod_lc = cle.cod_lc
			left join centre cee on cee.id = lce.cod_centru
			left join clienti cld on cld.cod_cl = ep.destinatar_id
			left join localitati lcd on lcd.cod_lc = cld.cod_lc
			left join centre ced on ced.id = lcd.cod_centru
			LEFT JOIN zones clez ON clez.id = cle.zona_id
        	LEFT JOIN centre clec on clec.id = clez.centru_id
			LEFT JOIN zones cldz ON cldz.id = cld.zona_id
        	LEFT JOIN centre cldc on cldc.id = cldz.centru_id
            WHERE {$cond}
			GROUP BY ep.expeditie
			ORDER BY " . $sidx . " " . $sord . " LIMIT " . $start . " , " . $limit;
		//if($this->user_id == parent::MARIAN)
			//error_log(print_r($query, true));
        $sql = $this->db->QFetchRowArray($query);
        if (!empty($sql)) {
            foreach ($sql as $key => $row) {
				$responce->rows[$key]['id'] = $row['expeditie'];
                $responce->rows[$key]['cell'] = array($row['expeditie'], $row['data_expeditie'],strtoupper($row['decont_centru_cod']), $row['decont_data'], $row['decont_id'],
					strtoupper($row['expeditor_centru_cod']),strtoupper($row['destinatar_centru_cod']),strtoupper($row['expeditor']),$row['ramburs'],$row['tip_plata'],$row['validare_data']);
            }
        }
		$responce->page = $page;
        $responce->total = $total_pages;
        $responce->records = $count;

        return json_encode($responce);
	}

	function ExportRbsValidate(){
		$cond ="1=1 ";
		$flag = false;
		if(!empty($_POST['data_start']) && !empty($_POST['data_final'])){
			$data_start = $this->TransformDate($_POST['data_start']) . " 00:00:00";
			$data_final = $this->TransformDate($_POST['data_final']) . " 23:59:59";
			$cond .= " AND dep.data between '".$data_start."' AND '".$data_final."'";
			$flag = true;
		}
		if(!empty($_POST['centru_id'])){
			$cond .= " AND da.centru_id = ".intval($this->sanitize($_POST['centru_id']));
		}

		$searchOn = '';
		//start generare conditie
		if(isset($_POST['_search']))
        	$searchOn = $this->Strip($_POST['_search']);
        if ($searchOn == 'true') {
            $searchstr = $this->Strip(base64_decode($_POST['filters']));
            $cond .= $this->constructWhere($searchstr);
		}

		$cond .= " and ep.anulata = 0 and iep.operatiune = 24";

		if(false === $flag) $cond = "1=2";

		//if($this->user_id == parent::MARIAN)
        	//error_log(print_r($cond, true));

        $query = "SELECT ep.expeditie as 'Nr. Exp', ep.data_expeditie as 'Data col.', group_concat(ceda.label) as 'Centru decont', group_concat(dep.data) as 'Data decont',
			group_concat(da.id) as 'ID decont',
			IF(cle.zona_id > 0 and clec.id > 0, clec.label, cee.label) as 'Centru colectare', 
			IF(cld.zona_id > 0 and cldc.id > 0, cldc.label, ced.label) as 'Centru livrare', 
			cle.nume as 'Expeditor',
			ep.ramburs as 'Valoare rbs',
			CASE ep.tip_plata WHEN 0 THEN 'cash'  WHEN 1 THEN 'bo' WHEN 2 THEN 'cec' WHEN 3 THEN 'cont' ELSE 'unknown' END as 'Tip plata',
			group_concat(iep.data) as 'Data validare'
			FROM exp_prelucrate ep
			inner join ist_exp iep on iep.cod_exp = ep.cod_expeditie
			inner join ist_exp_value_int iepd on iepd.cod_ist = iep.cod_ist and iepd.attribute like 'status_ramburs' and iepd.value = 2
			inner join decont_expeditii dep on dep.expeditie = ep.expeditie
			inner join decont_rbs dr on dep.ramburs_id = dr.id and dr.anulata = 0 and dr.vCR = 1
			left join decont_agent da on da.id = dep.decont_id
			left join centre ceda on ceda.id = da.centru_id
			left join clienti cle on cle.cod_cl = ep.expeditor_id
			left join localitati lce on lce.cod_lc = cle.cod_lc
			left join centre cee on cee.id = lce.cod_centru
			left join clienti cld on cld.cod_cl = ep.destinatar_id
			left join localitati lcd on lcd.cod_lc = cld.cod_lc
			left join centre ced on ced.id = lcd.cod_centru
			LEFT JOIN zones clez ON clez.id = cle.zona_id
        	LEFT JOIN centre clec on clec.id = clez.centru_id
			LEFT JOIN zones cldz ON cldz.id = cld.zona_id
        	LEFT JOIN centre cldc on cldc.id = cldz.centru_id
            WHERE {$cond}
			GROUP BY ep.expeditie
		";
		//error_log($query);
        $uresult = $this->db->Query($query, [], false);
        $options = new Options(
    		SHOULD_ADD_BOM: false,
		);
		$writer = new Writer($options);
		$writer->openToBrowser("raport_rbs_validate" . date("Y-m-d") . ".csv");
		$i = 0;
		ob_start();
		if($uresult) {
			if($row = $uresult->fetch(PDO::FETCH_ASSOC)) {
				$row_header = Row::fromValues(array_keys($row));
				$writer->addRow($row_header);
				$row_values = Row::fromValues(array_values($row));
				$writer->addRow($row_values);
			}
			while($row = $uresult->fetch(PDO::FETCH_ASSOC)) {
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
}
