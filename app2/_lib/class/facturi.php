<?php
require_once 'expeditii.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;

use OpenSpout\Writer\CSV\Writer;
use OpenSpout\Writer\CSV\Options;
use OpenSpout\Common\Entity\Row;
use OpenSpout\Common\Entity\Cell;

/**
 * W o r k s p a c e
 *
 */
class ModulFacturi extends BackEnd {

    public $final_result;
    public $action_module;
    public $page_prefix;
    public $site_prefix;

    /**
     * The constructor for the 'Workspace' class
     * Calls BackEnd constructor
     * Cals Actions function
     *
     * @param array $config
     * @param integer $act  (0/1) Specifies if actions are alowed or not
     * @access public
     * @see Actions()
     */
    function __construct($config = 0, $act = 1, $db = 0) {
        parent :: __construct($config, $db);
		$this->procTva = $this->getProcentTVA(date("Y-m-d"));

        $this->vars['title_page'] = 'Facturi';
        $this->page_prefix = 'facturi_';

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
		$flag = 0;
			if (in_array("incasari_incasari", $this->user_rights) || $this->user_profile == 10){
				if (isset($arr[1]) && $arr[1] == 'verificare_cui')
					echo $this->VerificareCui();
				else if (isset($arr[1]) && $arr[1] == 'verificare_expeditie')
					echo $this->VerificareExpeditie();
				else if(isset($arr[1]) && $arr[1] == 'json' && $arr[2]=='platitori')
					echo $this->JSON_ListePlatitori();
				$flag=1;
			}

			if (in_array("incasari_validari", $this->user_rights) || $this->user_profile == 10){
				if (isset($arr[1]) && $arr[1] == 'incasari_cf')
					$this->final_result = $this->IncasariFacturiCTR();
				else if (isset($arr[1]) && $arr[1] == 'v_incasari')
					$this->final_result = $this->ValidareIncasari();
				else if (isset($arr[1]) && $arr[1] == 'v_incasari_validare')
					echo $this->IncasariValidareCommit();
				else if (isset($arr[1]) && $arr[1] == 'ctr_plateste')
					echo $this->PlatesteFacturaCtr();
				else if (isset($arr[1]) && $arr[1] == 'sterge')
					echo $this->StergeFactura();
				else if (isset($arr[1]) && $arr[1] == 'liste')
					$this->final_result = $this->Facturi();
				else if(isset($arr[1]) && $arr[1] == 'json' && isset($arr[2]) && $arr[2]=='facturi')
					echo $this->JSON_Facturi();
				else if(isset($arr[1]) && $arr[1] == 'json' && isset($arr[2]) && $arr[2]=='v_incasari')
					echo $this->JSON_ListeValidareIncasari();
				$flag=1;
			}

			if (in_array("incasari_restante", $this->user_rights) || $this->user_profile == 10){
				if (isset($arr[1]) && $arr[1] == 'restante')
					$this->final_result = $this->Restante();
				else if($arr[1] == 'json' && isset($arr[2]) && $arr[2]=='restante_colectari')
					echo $this->JSON_RestanteColectari();
				else if(isset($arr[1]) && isset($arr[1]) && $arr[1] == 'json' && isset($arr[2]) && $arr[2]=='restante_livrari')
					echo $this->JSON_RestanteLivrari();
				else if(isset($arr[1]) && $arr[1] == 'json' && isset($arr[2]) && $arr[2]=='restante_centre')
					echo $this->JSON_RestanteCentre();
				else if(isset($arr[1]) && $arr[1] == 'json' && isset($arr[2]) && $arr[2]=='restante_facturi')
					echo $this->JSON_RestanteFacturi();
				else if(isset($arr[1]) && $arr[1] == 'export_restante_expeditii')
					echo $this->ExportRestanteExpeditii();
				else if(isset($arr[1]) && $arr[1] == 'export_restante_facturi')
					echo $this->ExportRestanteFacturi();

				$flag=1;
			}

			if (in_array("facturare", $this->user_rights) || $this->user_profile == 10){
				if (isset($arr[1]) && $arr[1] == 'print_factura' && isset($arr[2]))
					echo $this->Pdf($arr[2]);
				if (isset($arr[1]) && $arr[1] == 'facturare')
					$this->final_result = $this->Facturare();
				else if(isset($arr[1]) && $arr[1] == 'json' && isset($arr[2]) && $arr[2]=='liste_clienti')
					echo $this->JSON_ListeClienti();
				else if(isset($arr[1]) && $arr[1] == 'json' && isset($arr[2]) && $arr[2]=='liste_facturi')
					echo $this->JSON_ListeFacturi();
				else if(isset($arr[1]) && $arr[1] == 'vizualizare_liste_facturi')
					echo $this->VizualizareListaFacturi();
				else if(isset($arr[1]) && $arr[1] == 'vizualizare_centralizator_facturi')
					echo $this->CentralizatorFacturi();
				else if(isset($arr[1]) && $arr[1] == 'export_centralizator_facturi')
					echo $this->ExportCentralizatorFacturi();
				else if(isset($arr[1]) && $arr[1] == 'export' && isset($arr[2]) && $arr[2]=='facturi')
					echo $this->ExportFacturi();
				else if(isset($arr[1]) && $arr[1] == 'actualizare_expeditii')
					echo $this->ActualizareTarifeExpeditii();
				else if(isset($arr[1]) && $arr[1] == 'update_greutate')
					echo $this->FacturareUpdateGreutate();

				else if (isset($arr[1]) && $arr[1] == 'diferente')
					$this->final_result = $this->DiferenteFacturi();
				else if (isset($arr[1]) && $arr[1] == 'diferente_factura_detalii')
					$this->final_result = $this->DiferenteFacturaDetalii();
				else if(isset($arr[1]) && $arr[1] == 'json' && isset($arr[2]) && $arr[2]=='diferenta_expeditii')
					echo $this->JSON_DiferenteFacturi();
				else if (isset($arr[1]) && $arr[1] == 'export_diferenta_expeditii')
					echo $this->ExportDiferenteFacturiCsv();

				$flag=1;
			}

			if (in_array("print_facturi_android", $this->user_rights) || $this->user_profile == 10){
				if(isset($arr[1]) && $arr[1] == 'decont' && $arr[2]=='cautare')
					$this->final_result = $this->DecontCautare();
				else if(isset($arr[1]) && $arr[1] == 'decont' && isset($arr[2]) && $arr[2]=='json_cautare')
					echo $this->JSON_DecontCautare();
				else if(isset($arr[1]) && $arr[1] == 'decont' && isset($arr[2]) && $arr[2]=='detalii')
					echo $this->DecontDetaliiFactura($arr[3]);
				else if(isset($arr[1]) && $arr[1] == 'decont' && isset($arr[2]) && $arr[2]=='printone') {
					echo $this->DecontPrintOneFacturaTCPDF();
				}
				else if(isset($arr[1]) && $arr[1] == 'decont' && isset($arr[2]) && $arr[2]=='edit') {
					echo $this->DecontEditFactura();
				}
				else if(isset($arr[1]) && $arr[1] == 'decont' && isset($arr[2]) && $arr[2]=='export') {
					echo $this->DecontExportFacturi();
				}
				else if(isset($arr[1]) && $arr[1] == 'decont' && isset($arr[2]) && $arr[2]=='xclose') {
					if($this->user_id == parent::DOINA || $this->user_id == parent::MARIAN || $this->user_id == parent::MARIUS_TELER)
						echo $this->StergeFacturaDecont();
					else echo 0;
				}
				$flag=1;
			}

			if (in_array("print_ch_client_ctr", $this->user_rights) || $this->user_profile == 10){
				if(isset($arr[1]) && $arr[1] == 'decont' && isset($arr[2]) && $arr[2]=='cautare_cf')
					$this->final_result = $this->DecontCautareCf();
				else if(isset($arr[1]) && $arr[1] == 'decont' && isset($arr[2]) && $arr[2]=='json_cautare_cf')
					echo $this->JSON_DecontCautareCf();
				else if(isset($arr[1]) && $arr[1] == 'decont' && isset($arr[2]) && $arr[2]=='detalii_cf')
					echo $this->DecontDetaliiCf($arr[3]);
				else if(isset($arr[1]) && $arr[1] == 'decont' && isset($arr[2]) && $arr[2]=='printone_cf') {
					echo $this->DecontPrintOneCfTCPDF();
				}
				else if(isset($arr[1]) && $arr[1] == 'decont' && isset($arr[2]) && $arr[2]=='edit_cf') {
					if($this->user_id == parent::DOINA || $this->user_id == parent::MARIAN || $this->user_id == parent::MARIUS_TELER)
						echo $this->DecontEditCf();
				}
				else if(isset($arr[1]) && $arr[1] == 'decont' && isset($arr[2]) && $arr[2]=='print_cf') {
					echo "Not implemented yet";
					//echo $this->DecontPrintSelectedCfZpl();
				}
				else if(isset($arr[1]) && $arr[1] == 'decont' && isset($arr[2]) && $arr[2]=='xclose_cf') {
					if($this->user_id == parent::DOINA || $this->user_id == parent::MARIAN || $this->user_id == parent::MARIUS_TELER)
						echo $this->DecontStergeCf();
					else echo 0;
				}
				else if(isset($arr[1]) && $arr[1] == 'decont' && isset($arr[2]) && $arr[2]=='export_cf') {
					if($this->user_id == parent::DOINA || $this->user_id == parent::MARIAN || $this->user_id == parent::MARIUS_TELER)
						echo $this->DecontExportCf();
					else echo 0;
				}
				$flag=1;
			}

	    if(empty($flag))
        	$this->final_result = $this->PageNotFound();
    }

 //-------------------------------- functii ----------------------------------------

 /*/////////////////////////////////////////////////////////////
				 START INCASARI
/////////////////////////////////////////////////////////////*/

	function Facturi(){
		$this->vars['title_page'] = 'Cautare facturi';
		$vars = [];
        $vars['data_start'] = $vars['data_final'] = date('d.m.Y');
        return $this->Parse($this->page_prefix . 'liste.html', $vars);
	}

	function JSON_Facturi() {
		$responce = new StdClass();
		$cond = "a.mod_generare in (0,3) and a.anulata = 0";
		if(isset($_POST['filtru_sel']) && !empty($_POST['filtru_val'])){
        	$filtru_sel = intval($this->sanitize($_POST['filtru_sel']));
            $filtru_val = $this->sanitize($_POST['filtru_val']);
            if($filtru_sel == 1 && !empty($filtru_val))
				$cond .= " AND a.invoice like '".$filtru_val."' or a.receipt like '".$filtru_val."'";
			else if($filtru_sel == 2)
				$cond .= " and ep.expeditie = ".intval($filtru_val);
			else
				$cond = '1=2';
        }
        else if(isset($_POST['data_start']) && isset($_POST['data_final'])){
            $data_start = $this->TransformDate($_POST['data_start']);
            $data_final = $this->TransformDate($_POST['data_final']);
			if($_POST['selectie_data'] == 2) {
				$cond .= " and a.data_adaugare between '".$data_start." 00:00:00' and '".$data_final." 23:59:59'";
			} else {
				$cond .= " and a.trndate between '".$data_start." 00:00:00' and '".$data_final." 23:59:59'";
			}

        }
        else {
        	$cond = '1=2';
        }

		//start generare conditie
        $searchOn = $this->Strip($_POST['_search']);
        if ($searchOn == 'true') {
            $searchstr = $this->Strip($_REQUEST['filters']);
            $cond .= $this->constructWhere($searchstr);
        }
        $page = intval($_REQUEST['page'] ?? 1);
		$limit = intval($_REQUEST['rows'] ?? 20);
		$sidx = trim($this->sanitize($_REQUEST['sidx'] ?? 1));
		$sord = trim($this->sanitize($_REQUEST['sord'] ?? 'asc'));

        $query = "SELECT a.id, a.trndate, a.invoice, a.receipt, a.sumamnt, a.procTva,
			c.nume as platitor_nume, IF(c.zona_id > 0 and cc.id > 0, cc.nume, cn.nume) as centru_nume,
			GROUP_CONCAT(ep.expeditie) as expeditii, a.wme, a.wme_message
            FROM exp_facturi a
            LEFT JOIN exp_prelucrate ep on (a.id = ep.idfact and ep.anulata = 0)
            left join clienti c on a.cod_cl = c.cod_cl
			LEFT JOIN zones cz ON cz.id = c.zona_id
        	LEFT JOIN centre cc on cc.id = cz.centru_id
            left join localitati l on c.cod_lc = l.cod_lc
            left join centre cn on l.cod_centru = cn.id
            WHERE {$cond} and a.anulata = 0
            GROUP BY a.id, a.trndate, a.invoice, a.receipt, a.sumamnt, a.cod_cl";


		$result = $this->db->QFetchRowArray($query);
        $count = !empty($result) ? count($result) : 0;

        if( $count >0 ) {$total_pages = ceil($count/$limit); }
        else { $total_pages = 0; }
        if ($page > $total_pages) $page=$total_pages;
        if ($limit<0) $limit = 0;
        $start = $limit*$page - $limit; // do not put $limit*($page - 1)
        if ($start<0) $start = 0;
        $query = "SELECT a.id, a.trndate, a.invoice, a.receipt, a.sumamnt, a.mod_generare, a.procTva,
			c.nume as platitor_nume, IF(c.zona_id > 0 and cc.id > 0, cc.nume, cn.nume) as centru_nume,
			GROUP_CONCAT(ep.expeditie) as expeditii, a.wme, a.wme_message
            FROM exp_facturi a
            LEFT JOIN exp_prelucrate ep on (a.id = ep.idfact and ep.anulata = 0)
            left join clienti c on a.cod_cl = c.cod_cl
			LEFT JOIN zones cz ON cz.id = c.zona_id
        	LEFT JOIN centre cc on cc.id = cz.centru_id
            left join localitati l on c.cod_lc = l.cod_lc
            left join centre cn on l.cod_centru = cn.id
            WHERE {$cond} and a.anulata = 0
            GROUP BY a.id, a.trndate, a.invoice, a.receipt, a.sumamnt, a.cod_cl
            ORDER BY " . $sidx . " " . $sord . " LIMIT " . $start . " , " . $limit;

		$sql = $this->db->QFetchRowArray($query);
        if (!empty($sql)) {
            foreach ($sql as $key => $row) {
            	$row['resetare'] = '<a href="javascript:;" onclick="StergeFactura('.$row['id'].');" style="text-decoration:none; color:red">sterge</a>';
				$responce->rows[$key]['id'] = $row['id'];
				$responce->rows[$key]['cell'] = array($row['trndate'],$row['invoice'],$row['receipt'],$row['sumamnt'],$row['procTva'],$row['mod_generare'],$row['platitor_nume'],$row['centru_nume'],$row['expeditii'],$row['wme'],$row['wme_message'],$row['resetare']);
            }
        }
		$responce->page = $page;
        $responce->total = $total_pages;
        $responce->records = $count;
        return json_encode($responce);
    }

	function IncasariFacturiCTR(){
		$this->vars['title_page'] = 'Incasare facturi clienti cu contract cash';
        return $this->Parse($this->page_prefix . 'incasari_ctr.html', []);
	}

	function ValidareIncasari(){
		$this->vars['title_page'] = 'Validare incasari centre android';
        return $this->Parse($this->page_prefix . 'v_incasari.html', []);
	}

	function JSON_ListeValidareIncasari(){
		$responce = new StdClass();
		if(empty($_POST['facturi'])) return json_encode($responce);
		$cond = "1=1";

		$facturi = $this->ValidareIncasariCurata($_POST['facturi']);
		if(empty($facturi)){
			return json_encode($responce);
		}

		$cond .= " and df.serie IN (".$facturi.") and df.anulata = 0";
		//start generare conditie
		$searchOn = $this->Strip($_POST['_search']);
		if ($searchOn == 'true') {
			$searchstr = $this->Strip($_POST['filters']);
			$cond .= $this->constructWhere($searchstr);
		}

		$sidx = $_POST['sidx']; // get index row - i.e. user click to sort
		$sord = $_POST['sord']; // get the direction
		if (!$sidx) $sidx = 1;

		$marian = "and ef.invoice is NULL";
		if($this->user_id == parent::MARIAN)
			$marian = "";

		$query = "SELECT df.id, df.data, df.serie, df.suma as suma, c.nume as centru, ag.nume_ag as agent,
			group_concat(ep.expeditie) as expeditii, df.anulata
		FROM decont_facturi df
		left join decont_expeditii de on de.factura_id = df.id
		left join exp_prelucrate ep on ep.expeditie = de.expeditie and ep.anulata = 0 and ep.mod_plata = 0
		left join decont_agent da on df.decont_id = da.id
		left join centre c on da.centru_id = c.id
		left join agenti ag on df.agent_id = ag.cod_ag
		left join exp_facturi ef on df.serie = ef.invoice
		WHERE  {$cond} {$marian}
		GROUP BY df.id
		ORDER BY {$sidx} {$sord}";

		$sql = $this->db->QFetchRowArray($query);

		//error_log($query);
		$count = 0;
		$total = 0.00;
		if (!empty($sql)) {
			foreach ($sql as $key => $row) {
				$responce->rows[$key]['id'] = $row['id'];
				$count++;
				$total +=$row['suma'];
				$responce->rows[$key]['cell'] = array($row['data'], $row['serie'],$row['serie'],$row['suma'],$row['centru'],$row['agent'],$row['expeditii'],$row['anulata']);
			}
		}

		$responce->records = $count;
		$responce->userdata['chitanta'] = 'Total';
		$responce->userdata['suma'] = round($total, 2);
		return json_encode($responce);
	}

	function IncasariValidareCommit() {
		require_once("anafClient.php");
		$ret = '2|||';
		$tNow = new DateTime();
		if(isset($_POST['facturi'])) {
			$facturi = json_decode($_POST['facturi'],true);
			$errors=0;
			foreach ($facturi as $f) {
				$f = intval($f);
				$query_f = "SELECT df.*, group_concat(ep.expeditie) as exps, count(ep.expeditie) as nr_exps,
					substring_index(coalesce(group_concat(ep.idfact ORDER BY ep.idfact desc), ''), ',', 1) as exp_facturi_id,
					substring_index(coalesce(group_concat(ep.platitor_id ORDER BY ep.platitor_id desc), ''), ',', 1) as platitor_id,
					substring_index(coalesce(group_concat(cl.cod_fiscal ORDER BY ep.platitor_id desc), ''), ',', 1) as platitor_cui
					FROM decont_facturi df
					inner join decont_expeditii de on de.factura_id = df.id
					inner join exp_prelucrate ep on ep.expeditie = de.expeditie and ep.anulata = 0 and ep.mod_plata = 0
					left join clienti cl on cl.cod_cl = ep.platitor_id
					WHERE df.id = {$f} and df.anulata = 0
					group by df.id limit 1";
				$sql_f = $this->db->QFetchRowAssoc($query_f);
				if(empty($sql_f)){
					$ret .= "Factura cu id : {$f} nu are expeditii cu plata per NT asociate<br/>";
					$errors++;
					continue;
				}

				$query_fe = "SELECT id, invoice, receipt, data_adaugare
					FROM exp_facturi
					WHERE ((invoice <> '' and invoice like '" . $sql_f['serie'] . "') or (receipt <> '' and receipt like '". $sql_f['serie'] ."')) and anulata=0";
				$sql_fe = $this->db->QFetchArray($query_fe);
				if(!empty($sql_fe)) {
					if(!empty($sql_fe['invoice']))
						$ret .= "Factura : ".$sql_f['serie']." a fost deja validata pe data de {$sql_fe['data_adaugare']}<br/>";
					else if(!empty($sql_fe['receipt']))
						$ret .= "Chitanta : ".$sql_f['serie']." a fost deja validata pe data de {$sql_fe['data_adaugare']}<br/>";
					else $ret .= "Unknown error<br/>";
					$errors++;
					continue;
				}

				if(!empty($sql_f['exp_facturi_id'])){
					$ret .= "Factura : ".$sql_f['serie']." are cel putin o expeditie asociata care a fost facturata<br/>";
					$errors++;
					continue;
				}

				//bug client_id
				if($sql_f['nr_exps'] == 1 && $sql_f['client_id'] != $sql_f['platitor_id']) {
					//error_log("IncasariValidareCommit : {$sql_f['serie']} : {$sql_f['exps']} : {$sql_f['client_id']} : {$sql_f['platitor_id']}");
					$sql_f['client_id'] = $sql_f['platitor_id'];
					//$sql_f['cui'] = $sql_f['platitor_cui'];
					//$this->db->QueryUpdate('decont_facturi', ['client_id' => $sql_f['platitor_id'], 'cui' => $sql_f['platitor_cui']], "id = {$f}");
				}

				$cod_cl = $sql_f['client_id'];
				$platitor_pf = 1;
				$cui = ExpeditieDto::sanitizeCuiRO($sql_f['cui']);
				if(ExpeditieDto::isValidCui($sql_f['cui'])){
					$query_cui="SELECT cod_cl from clienti where sters = 0 and activ = 1 and cod_fiscal like :cui order by tarif desc limit 1";
					$sql_cui = $this->db->QFetchRowAssoc($query_cui, ['cui'=>$cui]);
					if(!empty($sql_cui) && $sql_cui['cod_cl'] > 0){
						$cod_cl = $sql_cui['cod_cl'];
						$platitor_pf = 0;
					}
					else {
						$anafClient = new AnafClient();
						$anafClient->addCui(ExpeditieDto::sanitizeCuiRO($cui, true), $tNow->format("Y-m-d"));
						$raspuns = $anafClient->getOneResult();
						if($raspuns !== false && $raspuns->date_generale->cui && $raspuns->date_generale->denumire && $raspuns->date_generale->adresa && $raspuns->date_generale->adresa->judet && $raspuns->date_generale->adresa->localitate) {
							//add client
							$vars = [];
							$vars['cod_fiscal'] = ($raspuns->inregistrare_scop_Tva->scpTVA && $raspuns->inregistrare_scop_Tva->scpTVA == "true") ? "RO".$raspuns->date_generale->cui:$raspuns->date_generale->cui;
							$query_a = "select l.cod_lc, l.cod_centru, l.dist_km from localitati l inner join judete j on l.cod_jd=j.cod_jd
								where
								replace(replace(l.nume_lc,' ',''),'-','') like replace(replace(:localitate,' ',''),'-','')
								and replace(replace(j.nume_jd,' ',''),'-','') like replace(replace(:judet,' ',''),'-','')";
							$sql_a = $this->db->QFetchRowAssoc($query_a, ['localitate'=>$raspuns->date_generale->adresa->localitate, 'judet'=>$raspuns->date_generale->adresa->judet]);
							if(!empty($sql_a)) {
								$platitor_pf = 0;
								$vars['cod_lc'] = $vars['cod_lc_sediu_social'] = $sql_a['cod_lc'];
								$vars['localitate'] = $vars['localitate_sediu_social'] = $raspuns->date_generale->adresa->localitate;
								$vars['cod_centru'] = $vars['cod_centru_sediu_social'] = $sql_a['cod_centru'];
								$vars['km_ext'] = $sql_a['dist_km'];
								$vars['nume'] = $vars['nume_societate'] = $raspuns->date_generale->denumire;
								$vars['adresa'] = $vars['adresa_sediu_social'] = (empty($raspuns->date_generale->adresa->strada)?"":$raspuns->date_generale->adresa->strada) . (empty($raspuns->date_generale->adresa->numar)?"":", ".$raspuns->date_generale->adresa->numar) . (empty($raspuns->date_generale->adresa->altele)?"":", ".$raspuns->date_generale->adresa->altele);
								$vars['tarif'] = 0;
								$vars['activ'] = 1;
								$vars['mod_plata'] = 0;
								$vars['operator'] = $this->user_id;
								$cod_cl = $this->db->QueryInsert('clienti', $vars);
								CdsGeocoder::geocode($this->db, $cod_cl);
							}
							else{
								$ret .= "CUI {$ro_cui} : Nu gasesc localitatea in app : ". $raspuns->date_generale->adresa->localitate;
								$errors++;
								error_log('AnafClient : ' . $raspuns->date_generale->cui . ' : Localitate not found: '. print_r($raspuns->date_generale->adresa,true));
								continue;
							}
						}
					}
				}

				$trndate = DateTime::createFromFormat('Y-m-d H:i:s', $sql_f['data']);
				if($trndate === false){
					$trndate = DateTime::createFromFormat('Y-m-d', $sql_f['data']);
					if($trndate === false)
						$trndate = $tNow->format("Y-m-d");
					else
						$trndate = $trndate->format("Y-m-d");
				}
				else
					$trndate = $trndate->format("Y-m-d");

				$exps_arr = explode(",", $sql_f['exps']);

				$fct = array(
					'trndate'=>$trndate,
					'data_adaugare' => $tNow->format("Y-m-d H:i:s"),
					'invoice'=>trim($sql_f['serie']),
					'receipt'=>trim($sql_f['serie']),
					'mod_generare' => $sql_f['transaction_id'] > 0 ? 3 : 0,
					'sumamnt'=>$sql_f['suma'],
					'procTva'=> $sql_f['proc_tva'],
					'cod_cl'=>$cod_cl,
					'operator'=> $this->user_id,
					'nr_exp'=> count($exps_arr)
				);

				if($cod_cl == 0 || $platitor_pf == 1) {
					$fct['wme'] = 1;
				}

				$exp_facturi_id = $this->db->QueryInsert('exp_facturi', $fct);

				if($exp_facturi_id > 0) {
					//incasata in decont
					$this->db->QueryUpdate('decont_facturi', array('status'=>2), "id = {$f}");
					//facturata cu idfact
					$this->db->QueryUpdate('exp_prelucrate', array('idfact'=>$exp_facturi_id), "expeditie in (".$sql_f['exps'].")");

					$addNrExpFa = 0;
					//istoric expeditii
					foreach($exps_arr as $exp_i) {
						$query_exp = "SELECT epr.cod_expeditie, epr.tip_exp, epr.mod_plata, ep.platitor_id, ep.expeditor_id, ep.expeditie, ep.restanta, ep.idfact
							FROM exp_prelucrate epr
							LEFT JOIN exp_prelucrate ep on ep.expeditie = epr.referire and ep.anulata = 0
							WHERE epr.expeditie = ".intval($exp_i)." and epr.anulata = 0 order by epr.cod_expeditie desc LIMIT 1";
						$sql_exp = $this->db->QFetchArray($query_exp);
						if(!empty($sql_exp)) {
							$this->insertIstExp($sql_exp['cod_expeditie'], 23);
							//inchide initiala la returnare
							if($sql_exp['tip_exp'] == 5 && !empty($sql_exp['expeditie']) && $sql_exp['restanta'] == 1 && $sql_exp['mod_plata'] == 0
								&& $sql_exp['idfact'] == 0 && $sql_exp['platitor_id'] == $sql_exp['expeditor_id']){
								$this->db->QueryUpdate('exp_prelucrate', array('idfact'=>$exp_facturi_id), "expeditie = {$sql_exp['expeditie']}");
								$addNrExpFa++;
							}
						}
					}
				}
				//update exp_facturi
				if($addNrExpFa > 0 )
					$this->db->QueryUpdate('exp_facturi', array('nr_exp'=> ($addNrExpFa + count($exps_arr))), "id = {$exp_facturi_id}");
			}
			if($errors > 0)
				return $ret;
			return 1;
		}
		return 0;
	}

	function ValidareIncasariCurata($fs){
		$facturi = explode(',',$fs);

		$proces_f = [];
		foreach ($facturi  as $fval){
			$fval = $this->sanitize($fval);
			if(!empty($fval) && strlen($fval) == 12)
				$proces_f[] = "'" . preg_replace( '/[^0-9]/', '', $fval) . "'";
		}

		if(!count($proces_f)){
			return "";
		}

		return implode(',',array_unique($proces_f));
	}

	function VerificareExpeditie(){
		if(!empty($_POST['exps'])) $exps = $this->sanitize($_POST['exps']);
		else return 0;

		$exps = parent::ValidareExpeditiiCurata($exps);
		if(empty($exps)) return 0;

		$exps = explode(",", $exps);
		$nb_exps = count($exps);
		if($nb_exps == 0) return 0;

		$platitor = 0;
		$total_valoare_totala_expeditie = 0;
		$total_valoare_totala_expeditie_cu_tva = 0;

		foreach($exps as $exp) {
			if(empty($exp)) continue;
			$query = "select epr.idfact, epr.mod_plata, ef.invoice, epr.tip_exp, epr.referire,
				epr.platitor_id, epr.valoare_totala_expeditie, epr.tva, c.nume, c.cod_fiscal, c.cod_lc, l.nume_lc,
				ep.valoare_totala_expeditie as valoare_totala_expeditie_initiala, ep.tva as tva_initiala,
				ep.restanta as restanta_initiala
				FROM exp_prelucrate epr
				LEFT JOIN exp_prelucrate ep ON (ep.expeditie = epr.referire and ep.anulata = 0)
				left join clienti c on epr.platitor_id = c.cod_cl
				left join localitati l on c.cod_lc = l.cod_lc
				left join exp_facturi ef on epr.idfact = ef.id
				where epr.expeditie = ".intval($exp)." and epr.anulata = 0";
			$sql = $this->db->QFetchRowAssoc($query);
			if(empty($sql)) return '1|||'.$exp; //expeditie inexistenta
			if(!empty($sql['mod_plata'])) return '3|||'.$exp; //expeditie plata periodica
			if(!empty($sql['idfact'])) return '2|||'.$exp.' platita cu factura nr. '.$sql['invoice']; //expeditie platita
			if(!empty($sql['platitor_id'])) $platitor = $sql['platitor_id'].'|||'.$sql['nume'].'|||'.$sql['cod_fiscal'].'|||'.$sql['cod_lc'].'|||'.$sql['nume_lc'];
			$total_valoare_totala_expeditie += $sql['valoare_totala_expeditie'];
			$total_valoare_totala_expeditie_cu_tva += ($sql['valoare_totala_expeditie'] + $sql['tva']);
			//returnare
			if($sql['tip_exp'] == 5 && !in_array($sql['referire'], $exps) && $sql['mod_plata'] == 0 && $sql['restanta_initiala'] == 1) {
				$total_valoare_totala_expeditie += $sql['valoare_totala_expeditie_initiala'];
				$total_valoare_totala_expeditie_cu_tva += ($sql['valoare_totala_expeditie_initiala'] + $sql['tva_initiala']);
			}
		}
		if(!empty($platitor)) return '4|||'.$platitor.'|||'.number_format($total_valoare_totala_expeditie,2,".","").'|||'.number_format($total_valoare_totala_expeditie_cu_tva,2,".","");
		return 0;
	}

	function JSON_ListeExpeditii() {
		$responce = new StdClass();
        if(!empty($_POST['exps'])) $exps = $this->sanitize($_POST['exps']);
		else return;

		$exps = preg_replace( "/,+/" , ',', $exps);

		$exps = parent::ValidareExpeditiiCurata($exps);
		if(empty($exps)) return;

		$total_valoare_totala_expeditie = 0;
		$valoare_totala_expeditie_cu_tva = 0;
		// aduc initiala daca are valoare 0

		$que = "SELECT epr.referire FROM exp_prelucrate epr
			LEFT JOIN exp_prelucrate ep ON (ep.expeditie = epr.referire and ep.anulata = 0)
			WHERE epr.expeditie in (".$exps.")
			AND ep.idfact = 0 AND ep.mod_plata = 0 and epr.anulata = 0";
        $result = $this->db->QFetchRowArray($que);

        $init = [];
        if(!empty($result)){
            foreach ($result as $exp){
                $init[] = $exp['referire'];
            }
        }

		if(count($init))
			$exps = $exps.",".implode(",",$init);


		$query = "SELECT COUNT(ep.cod_expeditie) as nr
			FROM exp_prelucrate ep
			left join clienti cle on cle.cod_cl = ep.expeditor_id
			left join clienti cld on cld.cod_cl = ep.destinatar_id
			WHERE ( (ep.expeditie in (".$exps.") and ep.anulata = 0) or ( ep.referire in (".$exps.") and ep.valoare_totala_expeditie = 0 and ep.anulata = 0) ) and ep.idfact=0 and ep.mod_plata=0";
        $result = $this->db->QFetchArray($query);
        $responce->records = $result['nr'];

        $query = "SELECT ep.expeditie, ep.referire, ep.data_expeditie,
			cle.nume as expeditor, cld.nume as destinatar,
			ep.plicuri, ep.colete, ep.paleti, ep.greutate, ep.km_preluare, ep.km_livrare,
			ep.valoare_totala_expeditie, ep.tva, ep.mod_plata
            FROM exp_prelucrate ep
			left join clienti cle on cle.cod_cl = ep.expeditor_id
			left join clienti cld on cld.cod_cl = ep.destinatar_id
            WHERE ( (ep.expeditie in (".$exps.") and ep.anulata = 0) or ( ep.referire in (".$exps.") and ep.valoare_totala_expeditie = 0 and ep.anulata = 0) ) and ep.idfact=0 and ep.mod_plata=0";
        $sql = $this->db->QFetchRowArray($query);

        if (!empty($sql)) {
            foreach ($sql as $key => $row) {
                if(!($this->user_profile == 10) && $row['mod_plata'] > 0) $row['valoare_totala_expeditie'] = 'NaN';
					$responce->rows[$key]['id'] = $row['expeditie'];
					$total_valoare_totala_expeditie += $row['valoare_totala_expeditie'];
					$valoare_totala_expeditie_cu_tva += $row['valoare_totala_expeditie'] +  $row['tva'];
                	$responce->rows[$key]['cell'] = array($row['expeditie'], (!empty($row['referire']))?$row['referire']:'', strtoupper($row['expeditor']),strtoupper($row['destinatar']),$row['data_expeditie'],$row['plicuri'],$row['colete'],$row['paleti'],$row['greutate'],$row['km_preluare'],$row['km_livrare'],$row['valoare_totala_expeditie'] , ($row['valoare_totala_expeditie'] +  $row['tva']));
            	}
        }
		$responce->userdata['valoare_totala_expeditie'] = $total_valoare_totala_expeditie;
		$responce->userdata['valoare_totala_expeditie_cu_tva'] = $valoare_totala_expeditie_cu_tva;
		return json_encode($responce);
    }

    function JSON_ListePlatitori() {
		$limit = 15;
		$responce = new StdClass();
		$responce->total = 0;
        $responce->rezultat=[];
        if(!empty($_GET['maxRows'])) $limit=$_GET['maxRows'];
        if(empty($_GET['name_startsWith'])) {
			return json_encode($responce);
		}
		$cond = " and a.nume like :name_startsWith";

        $query = "SELECT a.nume as platitor_nume, a.cod_cl as platitor_id, a.cod_fiscal, b.cod_lc as localitate_id, b.nume_lc as localitate_nume
        FROM clienti a
        JOIN localitati b on a.cod_lc=b.cod_lc
        WHERE a.activ=1 and a.mod_plata=0 and a.sters=0 {$cond} LIMIT {$limit}";
        $sql = $this->db->QFetchRowArray($query, ['name_startsWith'=>strtoupper($this->sanitize($_GET['name_startsWith']))."%"]);

        if (!empty($sql)) {
            $responce->total = count($sql);
            foreach ($sql as $key => $row) {
                $responce->rezultat[$key]['cod_cl'] = $row['platitor_id'];
                $responce->rezultat[$key]['localitate_id'] = $row['localitate_id'];
                $responce->rezultat[$key]['cod_fiscal'] = $row['cod_fiscal'];
                $responce->rezultat[$key]['label'] = strtoupper($row['platitor_nume']).' ('.strtoupper($row['localitate_nume']).')';
				$responce->rezultat[$key]['value'] = strtoupper($row['platitor_nume']);
            }
        }
        return json_encode($responce);
    }

	function StergeFactura(){
		$id = intval($_POST['id'] ?? 0);
		if($id == 0) return 0;

        $query = "select id from exp_facturi where mod_generare in (0,3) and id = {$id}";
        $sql = $this->db->QFetchArray($query);
		if(empty($sql)) return 0;

		//istoric expeditii
		$query = "SELECT expeditie, cod_expeditie FROM exp_prelucrate WHERE idfact = {$id}";
		$sql = $this->db->QFetchRowArray($query);
		foreach($sql as $row) {
			if(!empty($row['cod_expeditie'])) {
				$this->insertIstExp($row['cod_expeditie'], 35);
			}
		}

		$this->db->QueryUpdate('exp_facturi', array('anulata' => 1), "id=".$id);
		$this->db->QueryUpdate('exp_prelucrate', array('idfact' => 0), "idfact = {$id}");

		return 1;
	}

	function PlatesteFacturaCtr(){
		if(empty($_POST['invoice']) || empty($_POST['receipt'])) return 0;

		$invoice = $this->sanitize($_POST['invoice']);
		$receipt = $this->sanitize($_POST['receipt']);

		if(!empty($invoice) && !empty($receipt))
		{
			$query = "SELECT f.id, f.receipt
			FROM exp_facturi f
			inner join clienti c on f.cod_cl = c.cod_cl
			WHERE c.tip_plata=0 and f.invoice like :invoice and f.anulata=0 limit 1";
        	$sql = $this->db->QFetchArray($query, ['invoice'=>$invoice]);
			if(!empty($sql))
			{
				if(!empty($sql['receipt']))
					return '2|||Factura a fost deja platita cu chitanta : '.$sql['receipt'];
			}
			else {
				return '2|||Factura : '.$invoice.' inexistenta';
			}
			$this->db->QueryUpdate('exp_facturi', ['receipt' => $receipt], "id = ".$sql['id']);
			return 1;
		}
		return 0;
	}

// restante expedtii
	function Restante(){
		$this->vars['title_page'] = 'Restante';
		$vars = [];
	  	$cond = '';
        $vars['data_start'] = date('d.m.Y');
		$vars['data_final'] = date('d.m.Y');
		$vars['centru'] = $this->ComboCentre(0);

        return $this->Parse($this->page_prefix . 'restante.html', $vars);
	}

    function JSON_RestanteColectari() {
    	$responce = new StdClass();
    	$cond = '1=1';
		$total = 0;
		if(isset($_GET['data_start']) && isset($_GET['data_final'])){
	        $data_start = $this->TransformDate(urldecode($_GET['data_start']));
	        $data_final = $this->TransformDate(urldecode($_GET['data_final']));
			$cond .= " and ep.data_expeditie>='".$data_start."' and ep.data_expeditie<='".$data_final."'";
	    }
	    else $cond .= " and ep.data_expeditie>='".$this->TransformDate('')."'";
		if(!empty($_GET['centru'])){
			$cond .= ' and lcp.cod_centru = lce.cod_centru and IF(cle.zona_id > 0 and clec.id > 0, clec.id, lce.cod_centru) = '.intval($_GET['centru']);
		}

		//start generare conditie
		$searchOn = $this->Strip($_GET['_search']);

		if ($searchOn == 'true') {
			$searchstr = $this->Strip($_GET['filters']);
			$cond .= $this->constructWhere($searchstr);
		}
		$having = "";
		if(preg_match("/decontata  = '1'/", $cond) == 1){
			$having = "HAVING decontata = 1";
			$cond = str_replace("AND  decontata  = '1'","", $cond);
		}
		else if(preg_match("/decontata  = '0'/", $cond) == 1){
			$having = "HAVING decontata = 0";
			$cond = str_replace("AND  decontata  = '0'","", $cond);
		}

		$page = intval($_GET['page'] ?? 1);
		$limit = intval($_GET['rows'] ?? 20);
		$sidx = trim($this->sanitize($_GET['sidx'] ?? 1));
		$sord = trim($this->sanitize($_GET['sord'] ?? 'asc'));

		$cond .= " and ep.anulata = 0";
		//error_log($cond);
    	$query = "SELECT COUNT(expeditie) as nr
			from (
				select ep.expeditie as expeditie, SUM(IF(COALESCE(df.decont_id, 0) > 0, 1, 0)) as decontata
				FROM exp_prelucrate ep
				left join decont_expeditii de on de.expeditie = ep.expeditie and de.anulata = 0 and de.operatiune = 1
				left join decont_facturi df on de.factura_id = df.id and df.anulata = 0 and df.operatiune = 1
				left join clienti cle on cle.cod_cl = ep.expeditor_id
				left join clienti cld on cld.cod_cl = ep.destinatar_id
				left join clienti clp on clp.cod_cl = ep.platitor_id
				LEFT JOIN zones clez ON clez.id = cle.zona_id
        		LEFT JOIN centre clec on clec.id = clez.centru_id
				left join localitati lce ON lce.cod_lc = cle.cod_lc
				left join localitati lcd ON lcd.cod_lc = cld.cod_lc
				left join localitati lcp ON lcp.cod_lc = clp.cod_lc
				WHERE {$cond} and ep.expeditor_id = ep.platitor_id and ep.tip_exp in (0,5) and ep.mod_plata = 0 and ep.idfact = 0
				GROUP BY ep.expeditie {$having}
			) as rCol
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

		$query = "SELECT ep.expeditie,
			cle.nume as expeditor, lce.nume_lc as expeditor_localitate, cld.nume as destinatar, lcd.nume_lc as destinatar_localitate,
			ep.valoare_totala_expeditie, ep.tva, ep.data_expeditie, ep.tip_exp, SUM(IF(COALESCE(df.decont_id, 0) > 0, 1, 0)) as decontata
			FROM exp_prelucrate ep
			left join decont_expeditii de on de.expeditie = ep.expeditie and de.anulata = 0 and de.operatiune = 1
		    left join decont_facturi df on de.factura_id = df.id and df.anulata = 0 and df.operatiune = 1
			left join clienti cle on cle.cod_cl = ep.expeditor_id
			left join clienti cld on cld.cod_cl = ep.destinatar_id
			left join clienti clp on clp.cod_cl = ep.platitor_id
			LEFT JOIN zones clez ON clez.id = cle.zona_id
        	LEFT JOIN centre clec on clec.id = clez.centru_id
			left join localitati lce ON lce.cod_lc = cle.cod_lc
			left join localitati lcd ON lcd.cod_lc = cld.cod_lc
			left join localitati lcp ON lcp.cod_lc = clp.cod_lc
			WHERE {$cond}  and ep.expeditor_id=ep.platitor_id and ep.tip_exp in (0,5) and ep.mod_plata = 0 and ep.idfact = 0
			GROUP BY ep.expeditie {$having}
			ORDER BY " . $sidx . " " . $sord . " LIMIT " . $start . " , " . $limit;


		//error_log($cond);

        $sql = $this->db->QFetchRowArray($query);
        if (!empty($sql)) {
            foreach ($sql as $key => $row) {
				$row['val'] = $row['valoare_totala_expeditie'] + $row['tva'];
            	$total += $row['val'];

                $responce->rows[$key]['id'] = $row['expeditie'];
                $responce->rows[$key]['cell'] = array($row['data_expeditie'],$row['expeditie'],strtoupper($row['expeditor']),strtoupper($row['expeditor_localitate']),
					strtoupper($row['destinatar']),strtoupper($row['destinatar_localitate']),number_format($row['val'], 2, ".", ""),$row['tip_exp'],
					$this->getExpScan($row['expeditie']), intval($row['decontata'] > 0));
            }
        }
        $responce->page = $page;
        $responce->total = $total_pages;
        $responce->records = $count;

        $responce->userdata['destinatar'] = 'Total : '.$count.' expeditii in valoare de '.number_format($total, 2, ".", "");

        return json_encode($responce);
    }

     function JSON_RestanteLivrari() {
     	$responce = new StdClass();
    	$cond = '1=1';
        $total=0;
		if(isset($_GET['data_start']) && isset($_GET['data_final'])){
	        $data_start = $this->TransformDate(urldecode($_GET['data_start']));
	        $data_final = $this->TransformDate(urldecode($_GET['data_final']));
			$cond .= " and ep.data_expeditie>='".$data_start."' and ep.data_expeditie<='".$data_final."'";
	    }
	    else $cond .= " and ep.data_expeditie>='".$this->TransformDate('')."'";

		if(!empty($_GET['centru'])){
			$cond .= ' and lcp.cod_centru = lcd.cod_centru and IF(cld.zona_id > 0 and cldc.id > 0, cldc.id, lcd.cod_centru) = '.intval($_GET['centru']);
		}

		//start generare conditie
		$searchOn = $this->Strip($_GET['_search']);

		if ($searchOn == 'true') {
			$searchstr = $this->Strip($_GET['filters']);
			$cond .= $this->constructWhere($searchstr);
		}
		$having = "";
		if(preg_match("/decontata  = '1'/", $cond) == 1){
			$having = "HAVING decontata = 1";
			$cond = str_replace("AND  decontata  = '1'","", $cond);
		}
		else if(preg_match("/decontata  = '0'/", $cond) == 1){
			$having = "HAVING decontata = 0";
			$cond = str_replace("AND  decontata  = '0'","", $cond);
		}

		$sidx = trim($this->sanitize($_GET['sidx'] ?? 1));
		$sord = trim($this->sanitize($_GET['sord'] ?? 'asc'));

		$cond .= " and ep.tip_exp in (0,5) and ep.mod_plata = 0 and ep.idfact = 0 and ep.destinatar_id = ep.platitor_id and ep.anulata = 0";

		$query = "SELECT ep.cod_expeditie, ep.data_operatie, ep.expeditie, ep.data_expeditie, ep.data_op,
			cle.nume as expeditor, lce.nume_lc as expeditor_localitate, cld.nume as destinatar, lcd.nume_lc as destinatar_localitate,
			ep.valoare_totala_expeditie, ep.tva, ep.tip_exp, SUM(IF(COALESCE(df.decont_id, 0) > 0, 1, 0)) as decontata
			FROM exp_prelucrate ep
			left join decont_expeditii de on de.expeditie = ep.expeditie and de.anulata = 0 and de.operatiune = 2
		    left join decont_facturi df on de.factura_id = df.id and df.anulata = 0 and df.operatiune = 2
			left join clienti cle on cle.cod_cl = ep.expeditor_id
			left join clienti cld on cld.cod_cl = ep.destinatar_id
			left join clienti clp on clp.cod_cl = ep.platitor_id
			LEFT JOIN zones cldz ON cldz.id = cld.zona_id
        	LEFT JOIN centre cldc on cldc.id = cldz.centru_id
			left join localitati lce ON lce.cod_lc = cle.cod_lc
			left join localitati lcd ON lcd.cod_lc = cld.cod_lc
			left join localitati lcp ON lcp.cod_lc = clp.cod_lc
			WHERE {$cond}
			GROUP BY ep.expeditie {$having}
			ORDER BY " . $sidx . " " . $sord . " ;";
		//error_log($query);
        $sql = $this->db->QFetchRowArray($query);
        $count=0;
        if (!empty($sql)) {
			// $y=0;
             foreach ($sql as $key => $row) {
				if(!empty($row['data_op']))
					$row['data_expeditie'] = $row['data_operatie'];

	    		$row['valoare_totala_expeditie'] += $row['tva'];
	    		$total += $row['valoare_totala_expeditie'];
	        	$responce->rows[$key]['id'] = $row['expeditie'];
	        	$responce->rows[$key]['cell'] = array($row['data_expeditie'],$row['expeditie'],strtoupper($row['destinatar']),strtoupper($row['destinatar_localitate']),
					strtoupper($row['expeditor']),strtoupper($row['expeditor_localitate']),number_format($row['valoare_totala_expeditie'], 2, ".", ""),$row['tip_exp'],
					$this->getExpScan($row['expeditie']), intval($row['decontata'] > 0));
	        	$count++;
        	}
        }
        $responce->records = $count+1;

        $responce->userdata['destinatar'] = 'Total : '.$count.' expeditii in valoare de '.number_format($total, 2, ".", "");

        return json_encode($responce);
	}

	function JSON_RestanteFacturi() {
		$responce = new StdClass();
    	$cond = '1=1';

	    $cond .= " and f.trndate >= '2020-01-01'";

		if(!empty($_GET['centru'])){
			$cond .= ' and IF(c.zona_id > 0 and cc.id > 0, cc.id, l.cod_centru) = '.intval($_GET['centru']);
		}

		$cond .=" and c.tip_plata = 0 and (f.receipt is null or f.receipt = '') ";

		$page = intval($_GET['page'] ?? 1);
		$limit = 10000;
		$sidx = trim($this->sanitize($_GET['sidx'] ?? 1));
		$sord = trim($this->sanitize($_GET['sord'] ?? 'asc'));

		//start generare conditie
        $searchOn = $this->Strip($_GET['_search']);
        if ($searchOn == 'true') {
            $searchstr = $this->Strip($_GET['filters']);
            $cond .= $this->constructWhere($searchstr);
        }

		$query = "SELECT COUNT(f.id) as nr
			FROM exp_facturi f
			inner join clienti c on f.cod_cl = c.cod_cl
			LEFT JOIN zones cz ON cz.id = c.zona_id
        	LEFT JOIN centre cc on cc.id = cz.centru_id
            left join localitati l on c.cod_lc = l.cod_lc
			WHERE {$cond} ";
			//echo $query;die;
        $result = $this->db->QFetchArray($query);
        $count = !empty($result['nr']) ? $result['nr'] : 0;

        if( $count >0 ) {$total_pages = ceil($count/$limit); }
        else { $total_pages = 0; }
        if ($page > $total_pages) $page=$total_pages;
        if ($limit<0) $limit = 0;
        $start = $limit*$page - $limit; // do not put $limit*($page - 1)
        if ($start<0) $start = 0;
        $query = "SELECT f.id, f.trndate, f.invoice, f.receipt, f.sumamnt, f.procTva, f.nr_exp, f.mod_generare,
			c.nume_societate
            FROM exp_facturi f
			inner join clienti c on f.cod_cl = c.cod_cl
			LEFT JOIN zones cz ON cz.id = c.zona_id
        	LEFT JOIN centre cc on cc.id = cz.centru_id
			left join localitati l on c.cod_lc = l.cod_lc
			WHERE {$cond}
            ORDER BY " . $sidx . " " . $sord . " LIMIT " . $start . " , " . $limit;
			//error_log($query);
		$sql = $this->db->QFetchRowArray($query);
		$count=0;
		$total=0;
        if (!empty($sql)) {
            foreach ($sql as $key => $row) {
				$responce->rows[$key]['id'] = $row['id'];
				$responce->rows[$key]['cell'] = array($row['nume_societate'],$row['trndate'],$row['invoice'],$row['receipt'], number_format($row['sumamnt'], 2, ".", ""), $row['procTva'], $row['nr_exp'],$row['mod_generare']);
				$count++;
				$total+=$row['sumamnt'];
			}
        }
		$responce->page = $page;
        $responce->total = $total_pages;
        $responce->records = $count;

        $responce->userdata['sumamnt'] = number_format($total, 2, ".", "");

        return json_encode($responce);
    }


	function JSON_RestanteCentre() {
		$responce = new StdClass();

        $page = intval($_GET['page'] ?? 1);
		$limit = intval($_GET['rows'] ?? 20);
		$sidx = trim($this->sanitize($_GET['sidx'] ?? 1));
		$sord = trim($this->sanitize($_GET['sord'] ?? 'asc'));

        $query="SELECT COUNT(id) as nr
                FROM centre";
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

        $query="SELECT id, nume
                FROM centre
                ORDER BY " . $sidx . " " . $sord . " LIMIT " . $start . " , " . $limit;
        $sql = $this->db->QFetchRowArray($query);
        $responce->page = $page;
        $responce->total = $total_pages;
        $responce->records = $count;

        if (!empty($sql)) {
            foreach ($sql as $key => $row) {
                $responce->rows[$key]['id']=$row['id'];
                $responce->rows[$key]['cell'] = array($row['nume']);
            }
        }
        return json_encode($responce);
    }

	function ExportRestanteExpeditii() {
		$data = date('d/m/Y');
 		$societate = 'Dragon Star Curier';
		$document = 'Lista Expeditii Neincasate '.$data;
		$interval="";
		if(isset($_POST['data_start']) && isset($_POST['data_final'])){
	        $data_start = $this->TransformDate($_POST['data_start']);
	        $data_final = $this->TransformDate($_POST['data_final']);
			$interval = " and ep.data_expeditie>='".$data_start."' and ep.data_expeditie<='".$data_final."'";
	    }

        $spreadsheet = new Spreadsheet();
        $spreadsheet->getProperties()->setCreator($societate)
            ->setLastModifiedBy($societate)
            ->setTitle($document)
            ->setSubject($document)
            ->setDescription($document)
            ->setKeywords($document)
            ->setCategory($document);
        $spreadsheet->getDefaultStyle()->getFont()->setName('Arial');
        $spreadsheet->getDefaultStyle()->getFont()->setSize(10);
        $worksheet = $spreadsheet->getActiveSheet();

		foreach (range('A', 'R') as $letter) {
			$worksheet->getStyle($letter.'1')->getFont()->setSize(10);
			$worksheet->getStyle($letter.'1')->getFont()->setBold(true);
		}

		$worksheet->setCellValue('A1','Operatiune');
        $worksheet->setCellValue('B1','Data');
        $worksheet->setCellValue('C1','Expeditie');
		$worksheet->setCellValue('D1','Expeditor');
		$worksheet->setCellValue('E1','Localitate Exp.');
		$worksheet->setCellValue('F1','Destinatar');
		$worksheet->setCellValue('G1','Localitate Dest.');
		$worksheet->setCellValue('H1','Val. Incasata');
		$worksheet->setCellValue('I1','Greutate');
		$worksheet->setCellValue('J1','Km. colectare');
		$worksheet->setCellValue('K1','Km. livrare');
		$worksheet->setCellValue('L1','Val. Asig');
		$worksheet->setCellValue('M1','Centru exp.');
		$worksheet->setCellValue('N1','Centru dest.');
		$worksheet->setCellValue('O1','Curier');
		$worksheet->setCellValue('P1','Centru platitor');
		$worksheet->setCellValue('Q1','Tip expeditie');
		$worksheet->setCellValue('R1','Nr. Scanari');
		$worksheet->setCellValue('S1','Decontata');

		$rand=2;
        $cond = '1=1'.$interval;

		if(!empty($_POST['centru'])){
			$cond .= ' and IF(cle.zona_id > 0 and clec.id > 0, clec.id, cee.id) = '.intval($_POST['centru']).' and IF(clp.zona_id > 0 and clpc.id > 0, clpc.id, cep.id) = '.intval($_POST['centru']);
		}

		$cond .= " and ep.anulata = 0";
		$query = "SELECT ep.expeditie, ep.data_expeditie, ep.operatiune, ep.tip_exp,
			cle.nume as expeditor, lce.nume_lc as expeditor_localitate, cld.nume as destinatar, lcd.nume_lc as destinatar_localitate,
			ep.greutate, ep.km_preluare, ep.km_livrare, ep.valoare_asigurata, 
			IF(cle.zona_id > 0 and clec.id > 0, clec.nume, cee.nume) as expeditor_centru, 
			IF(cld.zona_id > 0 and cldc.id > 0, cldc.nume, ced.nume) as destinatar_centru, 
			IF(clp.zona_id > 0 and clpc.id > 0, clpc.nume, cep.nume) as platitor_centru, 
			agp.nume_ag as curier_preluare, ep.valoare_totala_expeditie, ep.tva,
			(select count(sc.id) 
				from scanari_coduri sc 
				where sc.expeditie = ep.expeditie
			) as nr_scanari,
			IF(SUM(IF(COALESCE(df.decont_id, 0) > 0, 1, 0)) > 0, 'Da', 'Nu') as decontata
			FROM exp_prelucrate ep
			left join decont_expeditii de on de.expeditie = ep.expeditie and de.anulata = 0 and de.operatiune = 1
		    left join decont_facturi df on de.factura_id = df.id and df.anulata = 0 and df.operatiune = 1
			left join clienti cle on cle.cod_cl = ep.expeditor_id
			left join clienti cld on cld.cod_cl = ep.destinatar_id
			left join clienti clp on clp.cod_cl = ep.platitor_id
			left join localitati lce ON lce.cod_lc = cle.cod_lc
			left join localitati lcd ON lcd.cod_lc = cld.cod_lc
			left join localitati lcp ON lcp.cod_lc = clp.cod_lc
			left join centre cee ON cee.id = lce.cod_centru
			left join centre ced ON ced.id = lcd.cod_centru
			left join centre cep ON cep.id = lcp.cod_centru
			LEFT JOIN zones clez ON clez.id = cle.zona_id
        	LEFT JOIN centre clec on clec.id = clez.centru_id
			LEFT JOIN zones cldz ON cldz.id = cld.zona_id
        	LEFT JOIN centre cldc on cldc.id = cldz.centru_id
			LEFT JOIN zones clpz ON clpz.id = clp.zona_id
        	LEFT JOIN centre clpc on clpc.id = clpz.centru_id
			left join agenti agp ON agp.cod_ag = ep.curier_preluare_id
			where {$cond} and ep.expeditor_id = ep.platitor_id and (ep.tip_exp=0 or ep.tip_exp=5) and ep.mod_plata=0 and ep.idfact=0
			GROUP BY ep.expeditie
			order by ep.data_expeditie ASC";
        $sql = $this->db->QFetchRowArray($query);
        if (!empty($sql)) {

            foreach ($sql as $key => $row) {
				$row['operatiune'] = 'Colectare';
				$da = new DateTime($row['data_expeditie']);
				$row['data_expeditie'] = \PhpOffice\PhpSpreadsheet\Shared\Date::PHPToExcel($da);
				$row['curier'] = $row['curier_preluare'];
				$row['incasata'] = $row['valoare_totala_expeditie']+$row['tva'];
				if($row['tip_exp'] == 0) $row['tip_exp'] = 'Initiala';
				else if($row['tip_exp'] == 5) $row['tip_exp'] = 'Returnare';

				$worksheet->setCellValue('A'.($rand+$key),$row['operatiune']);
				$worksheet->setCellValue('B'.($rand+$key),$row['data_expeditie']);
				$worksheet->getStyle('B'.($rand+$key))->getNumberFormat()->setFormatCode(\PhpOffice\PhpSpreadsheet\Style\NumberFormat::FORMAT_DATE_DDMMYYYY);
		        $worksheet->setCellValue('C'.($rand+$key),$row['expeditie']);
		        $worksheet->setCellValue('D'.($rand+$key),$row['expeditor']);
				$worksheet->setCellValue('E'.($rand+$key),$row['expeditor_localitate']);
				$worksheet->setCellValue('F'.($rand+$key),$row['destinatar']);
				$worksheet->setCellValue('G'.($rand+$key),$row['destinatar_localitate']);
				$worksheet->setCellValue('H'.($rand+$key),number_format($row['incasata'], 2, ".", ""));
				$worksheet->setCellValue('I'.($rand+$key),$row['greutate']);
				$worksheet->setCellValue('J'.($rand+$key),$row['km_preluare']);
				$worksheet->setCellValue('K'.($rand+$key),$row['km_livrare']);
				$worksheet->setCellValue('L'.($rand+$key),$row['valoare_asigurata']);
				$worksheet->setCellValue('M'.($rand+$key),$row['expeditor_centru']);
				$worksheet->setCellValue('N'.($rand+$key),$row['destinatar_centru']);
				$worksheet->setCellValue('O'.($rand+$key),$row['curier']);
				$worksheet->setCellValue('P'.($rand+$key),$row['platitor_centru']);
				$worksheet->setCellValue('Q'.($rand+$key),$row['tip_exp']);
				$worksheet->setCellValue('R'.($rand+$key),$row['nr_scanari']);
				$worksheet->setCellValue('S'.($rand+$key),$row['decontata']);
            }
			$rand=$rand+$key;
        }

		$cond = '1=1' . $interval;

		if(!empty($_POST['centru'])){
			$cond .= ' and IF(cld.zona_id > 0 and cldc.id > 0, cldc.id, ced.id) = '.intval($_POST['centru']).' and IF(clp.zona_id > 0 and clpc.id > 0, clpc.id, cep.id) = '.intval($_POST['centru']);
		}

		$cond .= " and (ep.tip_exp=0 or ep.tip_exp=5) and ep.mod_plata=0 and ep.idfact=0 and ep.anulata = 0";

		$query = "SELECT ep.cod_expeditie, ep.expeditie, ep.data_operatie,
			cle.nume as expeditor, lce.nume_lc as expeditor_localitate,
			cld.nume as destinatar, lcd.nume_lc as destinatar_localitate, 
			IF(cle.zona_id > 0 and clec.id > 0, clec.nume, cee.nume) as expeditor_centru, 
			IF(cld.zona_id > 0 and cldc.id > 0, cldc.nume, ced.nume) as destinatar_centru, 
			IF(clp.zona_id > 0 and clpc.id > 0, clpc.nume, cep.nume) as platitor_centru, 
			ep.valoare_totala_expeditie,ep.tva,
			agl.nume_ag as curier_livrare,
			ep.tip_exp,ep.referire,ep.greutate,ep.km_preluare,ep.km_livrare,ep.valoare_asigurata,
			ep.expeditor_id, ep.platitor_id, ep.destinatar_id,
			(select count(sc.id) from scanari_coduri sc where sc.expeditie = ep.expeditie) as nr_scanari,
			IF(SUM(IF(COALESCE(df.decont_id, 0) > 0, 1, 0)) > 0, 'Da', 'Nu') as decontata
			FROM exp_prelucrate ep
			left join decont_expeditii de on de.expeditie = ep.expeditie and de.anulata = 0 and de.operatiune = 2
		    left join decont_facturi df on de.factura_id = df.id and df.anulata = 0 and df.operatiune = 2
			left join agenti agl ON agl.cod_ag = ep.curier_livrare_id
            left join clienti cle on cle.cod_cl = ep.expeditor_id
			left join clienti cld on cld.cod_cl = ep.destinatar_id
			left join clienti clp on clp.cod_cl = ep.platitor_id
			left join localitati lce ON lce.cod_lc = cle.cod_lc
			left join localitati lcd ON lcd.cod_lc = cld.cod_lc
			left join localitati lcp ON lcp.cod_lc = clp.cod_lc
			left join centre cee ON cee.id = lce.cod_centru
			left join centre ced ON ced.id = lcd.cod_centru
			left join centre cep ON cep.id = lcp.cod_centru
			LEFT JOIN zones clez ON clez.id = cle.zona_id
        	LEFT JOIN centre clec on clec.id = clez.centru_id
			LEFT JOIN zones cldz ON cldz.id = cld.zona_id
        	LEFT JOIN centre cldc on cldc.id = cldz.centru_id
			LEFT JOIN zones clpz ON clpz.id = clp.zona_id
        	LEFT JOIN centre clpc on clpc.id = clpz.centru_id
			WHERE {$cond} and ep.destinatar_id = ep.platitor_id
			GROUP BY ep.expeditie
			ORDER BY ep.data_op";
        $sql = $this->db->QFetchRowArray($query);
        if (!empty($sql)) {
        	$rand++;
            foreach ($sql as $key => $row) {
				$row['operatiune'] = 'Livrare';
				$dop = new DateTime($row['data_operatie']);
				$row['data_operatie'] = \PhpOffice\PhpSpreadsheet\Shared\Date::PHPToExcel($dop);
				$row['curier'] = $row['curier_livrare'];
				if($row['tip_exp'] == 0) $row['tip_exp'] = 'Initiala';
				else if($row['tip_exp'] == 5) $row['tip_exp'] = 'Returnare';

				$row['incasata'] = $row['valoare_totala_expeditie']+$row['tva'];

				$worksheet->setCellValue('A'.($rand+$key),$row['operatiune']);
				$worksheet->setCellValue('B'.($rand+$key),$row['data_operatie']);
				$worksheet->getStyle('B'.($rand+$key))->getNumberFormat()->setFormatCode(\PhpOffice\PhpSpreadsheet\Style\NumberFormat::FORMAT_DATE_DDMMYYYY);
				$worksheet->setCellValue('C'.($rand+$key),$row['expeditie']);
				$worksheet->setCellValueExplicit('D'.($rand+$key), $row['expeditor'], \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
				$worksheet->setCellValue('E'.($rand+$key),$row['expeditor_localitate']);
				$worksheet->setCellValueExplicit('F'.($rand+$key), $row['destinatar'], \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
				$worksheet->setCellValue('G'.($rand+$key),$row['destinatar_localitate']);
				$worksheet->setCellValue('H'.($rand+$key),number_format($row['incasata'], 2, ".", ""));
				$worksheet->setCellValue('I'.($rand+$key),$row['greutate']);
				$worksheet->setCellValue('J'.($rand+$key),$row['km_preluare']);
				$worksheet->setCellValue('K'.($rand+$key),$row['km_livrare']);
				$worksheet->setCellValue('L'.($rand+$key),$row['valoare_asigurata']);
				$worksheet->setCellValue('M'.($rand+$key),$row['expeditor_centru']);
				$worksheet->setCellValue('N'.($rand+$key),$row['destinatar_centru']);
				$worksheet->setCellValueExplicit('O'.($rand+$key), $row['curier'], \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
				$worksheet->setCellValue('P'.($rand+$key),$row['platitor_centru']);
				$worksheet->setCellValue('Q'.($rand+$key),$row['tip_exp']);
				$worksheet->setCellValue('R'.($rand+$key),$row['nr_scanari']);
				if($row['curier'] == "Scan System")
				{
					$query_c = "SELECT nume_ag from agenti where cod_ag in
						(select curier from scanari_coduri where cod = '".intval($row['expeditie'])."' and tip=4 ORDER BY data)

						";
        			$sql_c = $this->db->QFetchRowAssoc($query_c);
        			if (!empty($sql_c))
        				$worksheet->setCellValue('O'.($rand+$key),$sql_c['nume_ag']);
				}
				$worksheet->setCellValue('S'.($rand+$key),$row['decontata']);
            }
		}

        $data = date('Y_m_d');
        $filename = $data.'_Neincasate.xlsx';

        $this->download_send_headers_xls($filename);
        $writer = new Xlsx($spreadsheet);
        $writer->save('php://output');
		die;
	}

	function ExportRestanteFacturi() {
		$cond ='1=1';
		$data = date('d/m/Y');
 		$societate = 'Dragon Star Curier';
		$d = 'Lista Restante Facturi CTR CASH '.$data;

        $spreadsheet = new Spreadsheet();
		$spreadsheet->setActiveSheetIndex(0);
		$worksheet = $spreadsheet->getActiveSheet();
		$spreadsheet->getProperties()->setCreator($societate)
                ->setLastModifiedBy($societate)
                ->setTitle($d)
                ->setSubject($d)
                ->setDescription($d)
                ->setKeywords($d)
                ->setCategory($d);
		$spreadsheet->getDefaultStyle()->getFont()->setName('Arial');
		$spreadsheet->getDefaultStyle()->getFont()->setSize(10);

		foreach (range('A', 'J') as $letter) {
			$worksheet->getStyle($letter.'1')->getFont()->setSize(10);
			$worksheet->getStyle($letter.'1')->getFont()->setBold(true);
			$worksheet->getColumnDimension($letter)->setAutoSize(true);
		}

		$cond .= " and f.trndate >= '2019-05-01'";

		if(!empty($_POST['centru'])){
			$c = intval($_POST['centru']);
			if($c > 0)
				$cond .= ' and IF(c.zona_id > 0 and cc.id > 0, cc.id, l.cod_centru) = '.intval($_POST['centru']);
		}

        $cond .=" and c.tip_plata=0 and (f.receipt is null or f.receipt = '') ";

		if(isset($_POST['_search'])){
			$searchOn = $this->Strip($_POST['_search']);
			if ($searchOn == 'true') {
				$searchstr = $this->Strip($_POST['filters']);
				$cond .= $this->constructWhere($searchstr);
			}
		}
		$sidx = $_POST['sidx']; // get index row - i.e. user click to sort
        $sord = $_POST['sord']; // get the direction
		if (!$sidx) $sidx = 2;
		if (!$sord) $sord = "asc";

		$worksheet->setCellValue('A1','Societate');
        $worksheet->setCellValue('B1','Data');
        $worksheet->setCellValue('C1','Nr. factura');
		$worksheet->setCellValue('D1','Chitanta');
		$worksheet->setCellValue('E1','Suma');
		$worksheet->setCellValue('F1','Tva');
		$worksheet->setCellValue('G1','Nr. exp.');
		$worksheet->setCellValue('H1','Generare');
		$worksheet->setCellValue('I1','Centru');

		$rand=2;
		$query = "SELECT f.id, f.trndate, f.invoice, f.receipt, f.sumamnt, f.procTva, f.nr_exp, f.mod_generare,
			c.nume_societate, IF(c.zona_id > 0 and cc.id > 0, cc.nume, ce.nume) as centru
            FROM exp_facturi f
			inner join clienti c on f.cod_cl = c.cod_cl
			LEFT JOIN zones cz ON cz.id = c.zona_id
        	LEFT JOIN centre cc on cc.id = cz.centru_id
            left join localitati l on c.cod_lc = l.cod_lc
			left join centre ce on l.cod_centru = ce.id
			WHERE {$cond}
            ORDER BY " . $sidx . " " . $sord;
        $sql = $this->db->QFetchRowArray($query);
        if (!empty($sql)) {
            foreach ($sql as $key => $row) {
				$trndate = new DateTime($row['trndate']);
				$row['trndate'] = \PhpOffice\PhpSpreadsheet\Shared\Date::PHPToExcel($trndate);

				$worksheet->setCellValueExplicit('A'.($rand+$key), $row['nume_societate'], \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
				$worksheet->setCellValue('B'.($rand+$key),$row['trndate']);
				$worksheet->getStyle('B'.($rand+$key))->getNumberFormat()->setFormatCode(\PhpOffice\PhpSpreadsheet\Style\NumberFormat::FORMAT_DATE_DDMMYYYY);
		        $worksheet->setCellValueExplicit('C'.($rand+$key), $row['invoice'], \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
		        $worksheet->setCellValueExplicit('D'.($rand+$key), $row['receipt'], \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
				$worksheet->setCellValue('E'.($rand+$key),number_format($row['sumamnt'], 2, ".", ""));
				$worksheet->setCellValue('F'.($rand+$key),$row['procTva']);
				$worksheet->setCellValue('G'.($rand+$key),$row['nr_exp']);
				$worksheet->setCellValue('H'.($rand+$key),$row['mod_generare']);
				$worksheet->setCellValueExplicit('I'.($rand+$key), $row['centru'], \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
            }
        }

		$data = date('Y_m_d');
        $filename = $data."_incasari_facturi_CTR_CASH.xlsx";

		$this->download_send_headers_xls($filename);
        $writer = new Xlsx($spreadsheet);
		$writer->save("php://output");
		die;
    }

/*/////////////////////////////////////////////////////////////
				 END INCASARI
/////////////////////////////////////////////////////////////*/
/*/////////////////////////////////////////////////////////////
				 START Facturare
/////////////////////////////////////////////////////////////*/


	function Facturare() {
        $this->vars['title_page'] = 'Facturare';
        $vars = [];
        $vars['data_start'] = date('d.m.Y');
        $vars['data_final'] = date('d.m.Y');

        return $this->Parse($this->page_prefix . 'facturare.html', $vars);
    }

	function JSON_ListeClienti() {
		$responce = new StdClass();
		$cond =' ep.restanta = 0';
        $mod_plata = [];
        if(isset($_GET['mod_plata_normala']) && $_GET['mod_plata_normala'] == 'true'){
            $mod_plata[] = 0;
		}
        if(isset($_GET['mod_plata_periodica']) && $_GET['mod_plata_periodica'] == 'true'){
            $mod_plata[] = 1;
        }

        if(count($mod_plata)){
			if(in_array(1,$mod_plata)){
				$cond .= " AND (ep.mod_plata IN (".implode(",",$mod_plata).") OR  epr.mod_plata IN (".implode(",",$mod_plata)."))";
			} else {
				$cond .= " AND ep.mod_plata IN (".implode(",",$mod_plata).") AND epr.mod_plata <> 1";
			}
		}

        if(isset($_GET['data_start']) && isset($_GET['data_final'])){
            $data_start = $this->TransformDate(urldecode($_GET['data_start']));
            $data_final = $this->TransformDate(urldecode($_GET['data_final']));
			$cond .= " AND ep.data_expeditie >='".$data_start."' AND ep.data_expeditie<='".$data_final."'";
			if(isset($_GET['doar_nefacturate']) && $_GET['doar_nefacturate'] == 'true'){
                $cond .= " AND ep.idfact = 0";
			}
        }
		else{
        	$cond = '1=2';
        }

		//start generare conditie
        $searchOn = $this->Strip($_GET['_search']);
        if ($searchOn == 'true') {
            $searchstr = $this->Strip($_GET['filters']);
            $cond .= $this->constructWhere($searchstr);
		}

		$cond = preg_replace("/AND  clp.TIP_FACTURARE  = '0'/", "AND (clp.TIP_FACTURARE  = 0 or clp.TIP_FACTURARE is NULL)", $cond);

        $page = intval($_GET['page'] ?? 1);
		$limit = intval($_GET['rows'] ?? 20);
		$sidx = trim($this->sanitize($_GET['sidx'] ?? 1));
		$sord = trim($this->sanitize($_GET['sord'] ?? 'asc'));

		$cond .= " and ep.anulata = 0";
		$query = "SELECT COUNT(distinct(ep.expeditie)) as nr_exp, clp.nume as platitor, ep.platitor_id
			FROM exp_prelucrate ep
			left JOIN exp_prelucrate epr on epr.expeditie = ep.referire and epr.expeditie > 0
			left join clienti clp on ep.platitor_id = clp.cod_cl
			WHERE {$cond}
			GROUP BY ep.platitor_id";


        $result = $this->db->QFetchRowArray($query);
        $count = !empty($result) ? count($result) : 0;

        if( $count >0 ) {$total_pages = ceil($count/$limit); }
        else { $total_pages = 0; }
        if ($page > $total_pages) $page=$total_pages;
        if ($limit<0) $limit = 0;
        $start = $limit*$page - $limit; // do not put $limit*($page - 1)
        if ($start<0) $start = 0;

		$query = "SELECT COUNT(distinct(ep.expeditie)) as nr_exp, clp.nume as platitor, ep.platitor_id,
			IF(clp.TIP_FACTURARE is NULL, 0, clp.TIP_FACTURARE) as tip_facturare
			FROM exp_prelucrate ep
			left JOIN exp_prelucrate epr on epr.expeditie = ep.referire and epr.expeditie > 0
			left join clienti clp on ep.platitor_id = clp.cod_cl
			WHERE {$cond}
			GROUP BY ep.platitor_id  ORDER BY " . $sidx . " " . $sord ." LIMIT " . $start . " , " . $limit;

        $sql = $this->db->QFetchRowArray($query);
        if (!empty($sql)) {
            foreach ($sql as $key => $row) {
				$responce->rows[$key]['id'] = $row['platitor_id'];
				$responce->rows[$key]['cell'] = array($row['platitor'],$row['nr_exp'],$row['tip_facturare']);
            }
        }
		$responce->page = $page;
        $responce->total = $total_pages;
        $responce->records = $count;
        return json_encode($responce);
    }

	function JSON_ListeFacturi() {
		$responce = new StdClass();
		$cond =' ep.restanta = 0';

        $mod_plata = [];
        if(isset($_GET['mod_plata_normala']) && $_GET['mod_plata_normala'] == 'true'){
            $mod_plata[] = 0;
        }
        if(isset($_GET['mod_plata_periodica']) && $_GET['mod_plata_periodica'] == 'true'){
            $mod_plata[] = 1;
        }

		if(count($mod_plata)){
			if(in_array(1,$mod_plata)){
				$cond .= " AND (ep.mod_plata IN (".implode(",",$mod_plata).") OR  epr.mod_plata IN (".implode(",",$mod_plata)."))";
			} else {
				$cond .= " AND ep.mod_plata IN (".implode(",",$mod_plata).") AND epr.mod_plata <> 1";
			}
		}

		if(isset($_GET['data_start']) && isset($_GET['data_final'])){
            $data_start = $this->TransformDate(urldecode($_GET['data_start']));
            $data_final = $this->TransformDate(urldecode($_GET['data_final']));
			$cond .= " AND ep.data_expeditie >= '".$data_start."' AND ep.data_expeditie <= '".$data_final."'";
            $flag=1;
        }
        else $cond = "1=2";

        if(isset($_GET['client'])){
        	$cond .= " AND ep.platitor_id=".intval($_GET['client']);
        }
        if(isset($_GET['doar_nefacturate']) && $_GET['doar_nefacturate'] == 'true'){
            $cond .= " AND ep.idfact = 0";
        }
		//start generare conditie
        $searchOn = $this->Strip($_GET['_search']);
        if ($searchOn == 'true') {
            $searchstr = $this->Strip($_GET['filters']);
            $cond .= $this->constructWhere($searchstr);
        }

		$page = intval($_GET['page'] ?? 1);
		$limit = intval($_GET['rows'] ?? 20);
		$sidx = trim($this->sanitize($_GET['sidx'] ?? 1));
		$sord = trim($this->sanitize($_GET['sord'] ?? 'asc'));

		$cond .= " and ep.anulata = 0";
		$query = "SELECT COUNT(ep.cod_expeditie) as nr,
			sum(ep.valoare_expeditie) as valoare_expeditie,
			sum(ep.val_km) as val_km,
			sum(ep.val_greutate) as val_greutate,
			sum(ep.val_asig) as val_asig,
			sum(ep.valoare_totala_expeditie) as valoare_totala_expeditie,
			sum(ep.tva) as tva
			FROM exp_prelucrate ep
            left JOIN exp_prelucrate epr on (epr.expeditie = ep.referire and epr.anulata = 0)
			left join clienti cle on cle.cod_cl = ep.expeditor_id
			left join clienti cld on cld.cod_cl = ep.destinatar_id
			left join localitati lcd ON lcd.cod_lc = cld.cod_lc
			WHERE {$cond}";
		// echo $query;die;
        $result = $this->db->QFetchArray($query);
        $count = !empty($result['nr']) ? $result['nr'] : 0;

        $valoare_expeditie = number_format($result['valoare_expeditie'], 2, ".", "");
        $val_km = number_format($result['val_km'], 2, ".", "");
        $val_greutate = number_format($result['val_greutate'], 2, ".", "");
        $val_asig = number_format($result['val_asig'], 2, ".", "");
        $valoare_totala_expeditie = number_format($result['valoare_totala_expeditie'], 2, ".", "");
        $tva = number_format($result['tva'], 2, ".", "");

        if( $count >0 ) {$total_pages = ceil($count/$limit); }
        else { $total_pages = 0; }
        if ($page > $total_pages) $page=$total_pages;
        if ($limit<0) $limit = 0;
        $start = $limit*$page - $limit; // do not put $limit*($page - 1)
        if ($start<0) $start = 0;
        $query = "SELECT ep.expeditie, ep.data_expeditie, ep.mod_plata, ep.moneda,
			cle.nume as expeditor, lcd.nume_lc as destinatar_localitate, cld.nume as destinatar,
			ep.greutate, ep.colete, ep.valoare_expeditie, ep.val_km, ep.val_greutate, ep.val_asig, ep.valoare_totala_expeditie, ep.tva
            FROM exp_prelucrate ep
            left JOIN exp_prelucrate epr on (epr.expeditie = ep.referire and epr.anulata = 0)
			left join clienti cle on cle.cod_cl = ep.expeditor_id
			left join clienti cld on cld.cod_cl = ep.destinatar_id
			left join localitati lcd ON lcd.cod_lc = cld.cod_lc
            WHERE {$cond}
            ORDER BY " . $sidx . " " . $sord . " LIMIT " . $start . " , " . $limit . ";";
		//error_log($query);
        $sql = $this->db->QFetchRowArray($query);

        if (!empty($sql)) {
            foreach ($sql as $key => $row) {
				$responce->rows[$key]['id'] = $row['expeditie'];
				$responce->rows[$key]['cell'] = array($row['data_expeditie'],$row['mod_plata'],$row['expeditor'], $row['destinatar_localitate'], $row['destinatar'], $row['expeditie'],$row['greutate'],$row['colete'],number_format($row['valoare_expeditie'], 2, ".", ""),number_format($row['val_km'], 2, ".", ""),number_format($row['val_greutate'], 2, ".", ""),number_format($row['val_asig'], 2, ".", ""),number_format($row['valoare_totala_expeditie'], 2, ".", ""),number_format($row['tva'], 2, ".", ""));
            }
        }
		$responce->page = $page;
        $responce->total = $total_pages;
        $responce->records = $count;

		$responce->userdata['destinatar'] = $count.' Expeditii';
		$responce->userdata['valoare_expeditie'] = $valoare_expeditie;
		$responce->userdata['val_km'] = $val_km;
		$responce->userdata['val_greutate'] = $val_greutate;
		$responce->userdata['val_asig'] = $val_asig;
		$responce->userdata['valoare_totala_expeditie'] = $valoare_totala_expeditie;
		$responce->userdata['tva'] = $tva;
        $responce->userdata['expeditor'] = 'Total:';

        return json_encode($responce);
    }

	function VizualizareListaFacturi(){
		$vars = [];
		$post = [];
		$post = $this->_unserializeJQuery($_POST['data']);

		$vars['perioada'] = 'Perioada facturare '.$post['data_start']. ' - '.$post['data_final'];
		$cond =' ep.restanta = 0';

		if(isset($post['data_start']) && isset($post['data_final'])){
            $data_start = $this->TransformDate($post['data_start']);
            $data_final = $this->TransformDate($post['data_final']);
			$cond .= " AND ep.data_operatie>='".$data_start."' AND ep.data_operatie<='".$data_final."'";
            if(isset($_REQUEST['doar_nefacturate']) && $_REQUEST['doar_nefacturate'] == 'true'){
                $cond .= " AND ep.idfact = 0";
            }
        }
        else $cond = "1=2";

        if(isset($post['client'])){
        	$cond .= " AND ep.platitor_id=".$post['client'];
        }

		$mod_plata = [];

		if(isset($_REQUEST['mod_plata_normala']) && $_REQUEST['mod_plata_normala'] == 'true'){
			$mod_plata[] = 0;
		}
		if(isset($_REQUEST['mod_plata_periodica']) && $_REQUEST['mod_plata_periodica'] == 'true'){
			$mod_plata[] = 1;
		}

		if(count($mod_plata)){
			if(in_array(1,$mod_plata)){
				$cond .= " AND (ep.mod_plata IN (".implode(",",$mod_plata).") OR  epr.mod_plata IN (".implode(",",$mod_plata)."))";
			} else {
				$cond .= " AND ep.mod_plata IN (".implode(",",$mod_plata).") AND epr.mod_plata <> 1";
			}
		}

		if(isset($_REQUEST['doar_nefacturate']) && $_REQUEST['doar_nefacturate'] == 'true'){
			$cond .= " AND ep.idfact = 0 ";
		}

		$total_localitate = [];
        $total = [];

        $total_localitate['val_exp'] = 0.00;
		$total_localitate['val_km'] = 0.00;
		$total_localitate['val_greutate'] = 0.00;
		$total_localitate['val_asig'] = 0.00;
		$total_localitate['val_totala'] = 0.00;

		$total['val_exp'] = 0.00;
		$total['val_km'] = 0.00;
		$total['val_greutate'] = 0.00;
		$total['val_asig'] = 0.00;
		$total['valoare_asigurare'] = 0.00;
		$total['val_totala'] = 0.00;
		$total['val_tva'] = 0.00;
		$total['valoare_totala'] = 0.00;

		$items='';
		$key=-1;

		$cond .= " and ep.anulata = 0";
		$query = "SELECT ep.cod_expeditie, ep.expeditie, ep.referire, ep.data_expeditie, ep.tip_exp,
			cle.nume as expeditor, lce.nume_lc as expeditor_localitate,
			cld.nume as destinatar, lcd.nume_lc as destinatar_localitate,
			clp.nume as platitor, clp.cod_fiscal,
			IF(cle.zona_id > 0 and clec.id > 0, clec.nume, cee.nume) as expeditor_centru, 
			IF(cld.zona_id > 0 and cldc.id > 0, cldc.nume, ced.nume) as destinatar_centru, 
			IF(clp.zona_id > 0 and clpc.id > 0, clpc.nume, cep.nume) as platitor_centru, 
			ep.plicuri,ep.colete,ep.paleti,ep.greutate,ep.km_preluare,ep.km_livrare,
			ep.valoare_asigurata, ep.ramburs, ep.procent_asigurare, ep.ramburs_procent,
			ep.valoare_expeditie, ep.val_greutate, ep.val_km, ep.val_asig, ep.valoare_totala_expeditie, ep.mod_plata, ep.moneda, ep.tva
			FROM exp_prelucrate ep
			left JOIN exp_prelucrate epr on (epr.expeditie = ep.referire and epr.anulata = 0)
			left join clienti cle on cle.cod_cl = ep.expeditor_id
			left join clienti cld on cld.cod_cl = ep.destinatar_id
			left join clienti clp on clp.cod_cl = ep.platitor_id
			left join localitati lce ON lce.cod_lc = cle.cod_lc
			left join localitati lcd ON lcd.cod_lc = cld.cod_lc
			left join localitati lcp ON lcp.cod_lc = clp.cod_lc
			left join centre cee ON cee.id = lce.cod_centru
			left join centre ced ON ced.id = lcd.cod_centru
			left join centre cep ON cep.id = lcp.cod_centru
			LEFT JOIN zones clez ON clez.id = cle.zona_id
        	LEFT JOIN centre clec on clec.id = clez.centru_id
			LEFT JOIN zones cldz ON cldz.id = cld.zona_id
        	LEFT JOIN centre cldc on cldc.id = cldz.centru_id
			LEFT JOIN zones clpz ON clpz.id = clp.zona_id
        	LEFT JOIN centre clpc on clpc.id = clpz.centru_id
			WHERE {$cond} ORDER BY ep.data_expeditie, cle.nume, lcd.nume_lc";
		//error_log($query);
        $sql = $this->db->QFetchRowArray($query);

        if (!empty($sql)) {

        	$localitate = '';
			$data_colectare = '';
			$items = $this->Parse($this->page_prefix . 'print_facturare_top.html');
			$nr_item = 0;
			$j = 0;

			$expeditor_data = '';
			$pag=1;
            foreach ($sql as $key => $row) {
            	$vars['client'] = $row['platitor']. ' ('.$row['platitor_centru'].')'.(!empty($row['cod_fiscal'])?' : '.$row['cod_fiscal']:'');

            	$nr_item=$nr_item+20;
            	//initializez valorile
				if(empty($row['tarif_exp'])) $row['tarif_exp'] = 0.00;
				if(empty($row['tarif_g'])) $row['tarif_g'] = 0.00;
				if(empty($row['tarif_km'])) $row['tarif_km'] = 0.00;
				if(empty($row['val_asig'])) $row['val_asig'] = 0;
				if(empty($row['proc_asig'])) $row['proc_asig'] = 0.00;
				if(empty($row['ramburs'])) $row['ramburs'] = 0.00;

				//afisez localitatea
            	if($localitate != $row['destinatar_localitate']){
            		$localitate = $row['destinatar_localitate'];
            		$row['afisare_localitate'] = '<div class="lista_localitate">'.$row['destinatar_localitate'].'</div>';
					$nr_item=$nr_item+25;
				}


				//afisez totalul pentru o data
				if( $data_colectare != $row['data_expeditie'] || empty($sql[($key+1)])){

					$data_colectare = $row['data_expeditie'];
					$data_col = $this->CreateDate($row['data_expeditie']);
					//$row['data_expeditie'] = $this->CreateDate($row['data_expeditie']);
					$row['afisare_data_colectare'] = '
						<div class="lista_data">
							<span style="float:left; width: 150px; display:block;">'.$row['data_expeditie'].'</span>
						</div>';
					$nr_item=$nr_item+30;
					//<span style="float:left; width: 350px; display: block;">Expeditor: '.$row['expeditor'].' ('.$row['expeditor_localitate'].')</span>
				}

				if($expeditor_data != $row['data_expeditie']){
					if($key>0){

						$row['afisare_total'] = '
						<div class="lista_total">
							<span class="w530 al_left" style="font-size: 11px;">Total&nbsp;&nbsp;&nbsp;&nbsp;'. $expeditor_data.'</span>
							<span class="w40 al_right">'.round($total_localitate['val_exp'],2).'</span>
							<span class="w40 al_right">'.round($total_localitate['val_km'],2).'{VAL_KM}</span>
							<span class="w40 al_right">'.round($total_localitate['val_greutate'],2).'{VAL_GREUTATE}</span>
							<span class="w40 al_right">'.round($total_localitate['val_asig'],2).'{VAL_ASIG}</span>
							<span class="w40 al_right">'.round($total_localitate['val_totala'],2).'{VALOARE_TOTALA_EXPEDITIE}</span>
						</div>
						';
					}
					$nr_item=$nr_item+50;
					$total_localitate = [];
					$total_localitate['val_exp'] = 0.00;
					$total_localitate['val_km'] = 0.00;
					$total_localitate['val_greutate'] = 0.00;
					$total_localitate['val_asig'] = 0.00;
					$total_localitate['val_totala'] = 0.00;
					$expeditor_data = $row['data_expeditie'];
            	}

				$total_localitate['val_exp'] += $row['valoare_expeditie'];
				$total_localitate['val_km'] += $row['val_km'];
				$total_localitate['val_greutate'] += $row['val_greutate'];
				$total_localitate['val_asig'] += $row['val_asig'];
				$total_localitate['val_totala'] += $row['valoare_totala_expeditie'];

				$total['val_exp'] += $row['valoare_expeditie'];
				$total['val_km'] += $row['val_km'];
				$total['val_greutate'] += $row['val_greutate'];
				$total['val_asig'] += $row['val_asig'];
				$total['valoare_asigurare'] += $row['valoare_asigurata'];
				$total['val_totala'] += $row['valoare_totala_expeditie'];
				$total['val_tva'] += $row['tva'];
				$total['valoare_totala'] += $total['val_tva']+$total['val_totala'];

				$row['procent_asigurare'] = $row['procent_asigurare'].'%';
				$row['greutate'] = round($row['greutate'],1).'kg';
				if(!empty($row['plicuri']))$row['piese'] = 1;
				else if(!empty($row['paleti']))$row['piese'] = 1;
				else $row['piese'] = $row['colete'];

				if(!empty($row['referire'])){
					$row['referire'] = '<br/><span style="font-size: 9px;">'.(ExpeditieDto::TIP_EXP[$row['tip_exp']] ?? "unknown").': '.$row['referire'].'</span>';
					$row['height'] = 'style="height: 22px; line-height: 11px; padding:1px 0px;"';
				}else
					$row['referire'] = '';

				if(strlen($row['destinatar']) + strlen($row['destinatar_localitate'])>25)
					$row['height'] = 'style="height: 22px; line-height: 11px; padding:1px 0px;"';
				if(strlen($row['expeditor']) + strlen($row['expeditor_localitate'])>25)
					$row['height'] = 'style="height: 22px; line-height: 11px; padding:1px 0px;"';

				if(!empty($row['height']))
					$nr_item=$nr_item+20;

				//afisare valori in formular
				$items .= $this->Parse($this->page_prefix . 'print_facturare_row.html', $row);

				if(($nr_item>1300 && $j==0) || ($nr_item>1450 && $j==1) ) {
					$nr_item = 0;
					$j=1;
					$pag++;
					$row['pagina'] = '<span style="height: 15px; line-height: 15px; font-size: 11px;">Pag. '.$pag.' - '.$vars['client'].'</span>';

					$items .= '<div class="page_break"></div><div style="height: 10px;"></div>';
					$items .= $this->Parse($this->page_prefix . 'print_facturare_top.html', $row);
				}
            }
        }

		$vars['val_exp'] = round($total['val_exp'],2);
		$vars['val_km'] = round($total['val_km'],2);
		$vars['val_greutate'] = round($total['val_greutate'],2);
		$vars['valoare_asigurare'] = round($total['valoare_asigurare'],2);
		$vars['val_asig'] = round($total['val_asig'],2);
		$vars['val_totala'] = round($total['val_totala'],2);
		$vars['total_expeditii'] = $key+1;
		$vars['tva'] = round($total['val_tva'],2);
		$vars['valoare_totala'] = $vars['val_totala']+$vars['tva'];
		$vars['data'] = date('d/m/Y');
        $vars['items'] = $items;

        $html = $this->Parse($this->page_prefix . 'print_facturare.html', $vars);

		return $html;
	}

	function CentralizatorFacturi(){
		$post = [];
		$post = $this->_unserializeJQuery($_POST['data']);

		$cond =' ep.mod_plata = 1 AND ep.restanta = 0';

        if(isset($post['data_start']) && isset($post['data_final'])){
            $data_start = $this->TransformDate($post['data_start']);
            $data_final = $this->TransformDate($post['data_final']);
            $cond .= " AND ep.data_expeditie>='".$data_start."' AND ep.data_expeditie<='".$data_final."'";
        }else{
        	$cond = '1=2';
        }


		$html = '<div class="main" style="margin: 0px; width: 780px;">
					<h1>Centralizator Facturi</h1>
				';

		$cond .= " and ep.anulata = 0";
		$query = "SELECT COUNT(ep.expeditie) as nr_exp,
				cle.nume as client, cle.cod_fiscal as cod_fiscal, sum(ep.valoare_totala_expeditie) as total
				FROM exp_prelucrate ep
				left join clienti cle on ep.expeditor_id = cle.cod_cl
				WHERE {$cond}
				GROUP BY cle.nume, ep.platitor_id ORDER BY cle.nume ASC;";
        $sql = $this->db->QFetchRowArray($query);
        if (!empty($sql)) {
			$item_col = 57;
			$item_pag = 114;
			$i=0;
            foreach ($sql as $key => $row) {
				if($key==0){
					$html .= '<div style="clear:both; height: 20px;"></div><div class="centralizator_col">
								<div class="centralizator_col_row">
									<span class="w160 al_left"><b>Client</b></span>
									<span class="w80 al_left"><b>Cod fiscal</b></span>
									<span class="w50"><b>Valoare</b></span>
									<span class="w50"><b>Total</b></span>
								</div>
							';
				}else{
					if( $i==$item_pag ){
						$html .= '</div>';

						$i=0;
						$item_col=63;
						$item_pag=126;

						$html .= '
								<div class="page_break"></div>
								<div style="clear:both;"></div>
								<div class="centralizator_col">
									<div class="centralizator_col_row">
										<span class="w160 al_left"><b>Client</b></span>
										<span class="w80 al_left"><b>Cod fiscal</b></span>
										<span class="w50"><b>Valoare</b></span>
										<span class="w50"><b>Total</b></span>
									</div>
								';
					}else if( $i==$item_col){
						$html .= '
								</div>
								<div class="centralizator_col">
									<div class="centralizator_col_row">
										<span class="w160 al_left"><b>Client</b></span>
										<span class="w80 al_left"><b>Cod fiscal</b></span>
										<span class="w50"><b>Valoare</b></span>
										<span class="w50"><b>Total</b></span>
									</div>
								';
					}
				}

        		$row['client'] = substr($row['client'],0,20);
				$html .= '
						<div class="centralizator_col_row">
							<span class="w160 al_left">'.$row['client'].'</span>
							<span class="w80">'.(!empty($row['cod_fiscal'])?$row['cod_fiscal']:'').'</span>
							<span class="w50">'.$row['total'].'</span>
							<span class="w50">'.$row['nr_exp'].'</span>
						</div>';
				$i++;
            }
			$html .= '</div></div>';
        }
		return $html;
	}

	function ActualizareTarifeExpeditii(){
		set_time_limit(900);
		$cond = '1=1';
		if(isset($_POST['client'])){
			$client = intval($_POST['client']);
	        $cond .= " and ep.platitor_id = {$client}";
	    }
		else{
	        return 'error';
		}

		if(isset($_POST['data_start']) && isset($_POST['data_final'])){
	            $data_start = $this->TransformDate($_POST['data_start']);
	            $data_final = $this->TransformDate($_POST['data_final']);
				$cond .= " AND ep.data_expeditie between '{$data_start}' AND '{$data_final}'";
	    }

		$cond .= " and ep.anulata = 0";
		$query = "SELECT ep.expeditie, ep.tip_exp, ep.idfact, ep.referire FROM exp_prelucrate ep WHERE {$cond}";
        $expeditii = $this->db->QFetchRowArray($query);

		$updated = 0;
        if (!empty($expeditii) && count($expeditii) > 0) {
			foreach ($expeditii as $key => $expeditie){
				if($expeditie['tip_exp'] == 0){
					if(false !== ($ret = $this->ActualizareExpeditieInitiala($expeditie['expeditie'], $expeditie['idfact'])))
						$updated += $ret;
				}
				else if(!empty($expeditie['referire'])){
					if(false !== ($ret = $this->ActualizareRetururi($expeditie['referire'], true)))
						$updated += $ret;
				}
			}
		}
		return $updated;
	}

/*/////////////////////////////////////////////////////////////
				 END FACTURARE
/////////////////////////////////////////////////////////////*/
	function ExportFacturi() {
		$cond = true;
		if(isset($_POST['data_start']) && isset($_POST['data_final'])){
	            $data_start = $this->TransformDate(urldecode($_POST['data_start']));
	            $data_final = $this->TransformDate(urldecode($_POST['data_final']));
				$cond = " ep.data_expeditie>='".$data_start."' AND ep.data_expeditie<='".$data_final."'";
		}
		else{
	        $cond = false;
	    }

		$mod_plata = [];

		if(isset($_POST['mod_plata_normala']) && $_POST['mod_plata_normala'] == 'true'){
			$mod_plata[] = 0;
		}
		if(isset($_POST['mod_plata_periodica']) && $_POST['mod_plata_periodica'] == 'true'){
			$mod_plata[] = 1;
		}

		if(count($mod_plata)){
			$cond .= " AND ep.mod_plata IN (".implode(",",$mod_plata).") ";
		}

		if(isset($_POST['doar_nefacturate']) && $_POST['doar_nefacturate'] == 'true'){
			$cond .= " AND ep.idfact = 0";
		}

		if(isset($_POST['platitori']) && $cond)
		{
			$platitori = trim($_POST['platitori']);
			if(!empty($platitori))
			{
				$cond .= ' AND ep.platitor_id in ('.$platitori.') AND ep.restanta = 0';
				$platitori = explode(",", $platitori);
				$v_index = "use index (platitor_id)";
				return $this->ExportExpeditiiFacturi($cond, count($platitori), $v_index);
			}
		}

		header('Content-type: application/json');
		echo json_encode(array('success' => false));
	}

	function ExportExpeditiiFacturi($cond, $nr = 0, $v_index = "") {
		$data = date('d_m_Y');
        $filename = "Expeditii_facturi_{$data}.csv";

		$options = new Options(
    		SHOULD_ADD_BOM: false,
		);
		$writer = new Writer($options);
		$writer->openToBrowser($filename);
		ob_start();

		$cond .= " and ep.anulata = 0";
        $query = "SELECT clp.nume as 'Platitor', clp.cod_fiscal as 'CUI Platitor', cle.nume as 'Expeditor', lce.nume_lc as 'ExpLoca',
			cld.nume as 'Destinatar', lcd.nume_lc as 'DestLoca', ep.expeditie as 'Expeditie', ep.tip_exp as 'Tip Exp.', ep.data_expeditie as 'Data Colect.',
			ep.colete as 'Colete', ep.greutate as 'Gr (kg)', ep.ramburs as 'Ramb', ep.valoare_asigurata as 'V asig', ep.procent_asigurare as '% asig',
			ep.valoare_expeditie as 'T baza', ep.val_km as 'T km', ep.val_greutate as 'T kg', ep.val_asig as 'T asig', ep.valoare_totala_expeditie as 'Total',
			count(er.expeditie) as 'RW', IFNULL(scd.sc_nr, 0) as 'Scanari'
            FROM exp_prelucrate ep {$v_index}
			left join clienti cle on cle.cod_cl = ep.expeditor_id
			left join clienti cld on cld.cod_cl = ep.destinatar_id
			left join clienti clp on clp.cod_cl = ep.platitor_id
			left join localitati lce ON lce.cod_lc = cle.cod_lc
			left join localitati lcd ON lcd.cod_lc = cld.cod_lc
			LEFT JOIN exp_recantarite er on (er.expeditie = ep.expeditie and er.vKg = 1)
			left join ( select expeditie, count(*) as sc_nr
                from scanari_coduri
                group by expeditie
        	) as scd on scd.expeditie = ep.expeditie
            WHERE {$cond}
			GROUP BY ep.expeditie
            ORDER BY clp.nume, ep.data_expeditie, cle.nume ASC";
		//error_log($query);
        $sql = $this->db->Query($query, [], false);
		$i = 0;
        if(!empty($sql)) {
			if($row = $sql->fetch(PDO::FETCH_ASSOC)) {
				$row_header = Row::fromValues(array_keys($row));
				$writer->addRow($row_header);
				$row['Data Colect.'] = $this->CreateDate($row['Data Colect.']);
				$row['Tip Exp.'] = ExpeditieDto::TIP_EXP[$row['Tip Exp.']] ?? "unknown";

				$row_values = Row::fromValues(array_values($row));
				$writer->addRow($row_values);
			}
			while($row = $sql->fetch(PDO::FETCH_ASSOC)) {
				$row['Data Colect.'] = $this->CreateDate($row['Data Colect.']);
				$row['Tip Exp.'] = ExpeditieDto::TIP_EXP[$row['Tip Exp.']] ?? "unknown";

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

	function ExportCentralizatorFacturi() {
		$data = date('d-m-Y');
 		$societate = 'Dragon Star Curier';
        $document = 'Centralizator Facturi';

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

		foreach (range('A', 'H') as $letter) {
			$worksheet->getStyle($letter.'1')->getFont()->setSize(13);
			$worksheet->getStyle($letter.'1')->getFont()->setBold(true);
			$worksheet->getColumnDimension($letter)->setWidth(30);
		}

		$worksheet->setCellValue('A1','Client');
		$worksheet->setCellValue('B1','Judet');
		$worksheet->setCellValue('C1','Localitate');
        $worksheet->setCellValue('D1','Centru');
        $worksheet->setCellValue('E1','Cod fiscal');
        $worksheet->setCellValue('F1','Valoare');
        $worksheet->setCellValue('G1','Total');

		$cond = '1=1 ';
		$v_index = "use index (platitor_id)";
		if(isset($_POST['data_start']) && isset($_POST['data_final'])){
            $data_start = $this->TransformDate(urldecode($_POST['data_start']));
            $data_final = $this->TransformDate(urldecode($_POST['data_final']));
            $cond .= " AND ep.data_expeditie>='".$data_start."' AND ep.data_expeditie<='".$data_final."'";
		}
		else{
        	$response = array('success' => false);
			header('Content-type: application/json');
			echo json_encode($response);
			exit();
		}

		if(isset($_POST['platitori']))
		{
			$platitori = trim($_POST['platitori']);
			if(!empty($platitori))
				$cond .= ' and ep.platitor_id in ('.$platitori.')';
			else {
        		$response = array('success' => false);
				header('Content-type: application/json');
				echo json_encode($response);
				exit();
        	}
		}
		else{
        	$response = array('success' => false);
			header('Content-type: application/json');
			echo json_encode($response);
			exit();
        }

		$cond .=" and ep.mod_plata = 1 AND ep.restanta = 0 and ep.anulata = 0";
		$query = "SELECT COUNT(ep.expeditie) as nr_exp, clp.nume as client,
				clp.cod_fiscal as cod_fiscal, sum(ep.valoare_totala_expeditie) as total,
				lcp.nume_lc as localitate, jp.nume_jd as judet, 
				IF(clp.zona_id > 0 and clpc.id > 0, clpc.nume, cep.nume) as platitor_centru
				FROM exp_prelucrate ep {$v_index}
				left join clienti clp on ep.platitor_id = clp.cod_cl
				LEFT JOIN zones clpz ON clpz.id = clp.zona_id
        		LEFT JOIN centre clpc on clpc.id = clpz.centru_id
				left join localitati lcp on clp.cod_lc = lcp.cod_lc
				left join centre cep on lcp.cod_centru = cep.id
				left join judete jp on jp.cod_jd = lcp.cod_jd
				WHERE {$cond}
				GROUP BY ep.platitor_id ORDER BY clp.nume ASC";
			//error_log($query);
        $sql = $this->db->QFetchRowArray($query);
        //compun raspunsul
        if (!empty($sql)) {
        	$rand = 2;
			foreach ($sql as $key => $row) {
				$worksheet->setCellValueExplicit('A'.($rand+$key),$row['client'], \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
				$worksheet->setCellValue('B'.($rand+$key),$row['judet']);
				$worksheet->setCellValue('C'.($rand+$key),$row['localitate']);
				$worksheet->setCellValue('D'.($rand+$key),$row['platitor_centru']);
				$worksheet->setCellValue('E'.($rand+$key),$row['cod_fiscal']);
				$worksheet->setCellValue('F'.($rand+$key),$row['total']);
				$worksheet->setCellValue('G'.($rand+$key),$row['nr_exp']);
			}
		}
		$filename = 'Centralizator_facturi_'.$data.'.xlsx';

		$this->download_send_headers_xls($filename);
		$writer = new Xlsx($spreadsheet);
		$writer->save('php://output');
		die;
	}

	function DiferenteFacturi(){
		$this->vars['title_page'] = 'Diferente';
		return $this->Parse($this->page_prefix . 'diferente.html', []);
	}


	function JSON_DiferenteFacturi() {

        $having = "";

        $responce = new StdClass();
        if(isset($_REQUEST['data_start']) && isset($_REQUEST['data_final'])){
            $data_start = $this->TransformDate($_REQUEST['data_start'])." 00:00:00";
            $data_final = $this->TransformDate($_REQUEST['data_final'])." 23:59:59";
        }
        else
        {
            $today = date('Y-m-d');
            $data_start = $today." 00:00:00";
            $data_final = $today." 23:59:59";
        }


        $cond = " a.trndate >='".$data_start."' AND a.trndate <='".$data_final."'";

        if(isset($_REQUEST['centru']) && $_REQUEST['centru'] > 0) {
            $cond .= " AND IF(clp.zona_id > 0 and clpc.id > 0, clpc.id, lcp.cod_centru) = ".intval($_REQUEST['centru']);
        }

        $having = " HAVING diferenta <> 0";
        if(isset($_REQUEST['diferente_zero']) && $_REQUEST['diferente_zero'] == 'true') {
            $having = 'HAVING 1 ';
        }


        if(isset($_REQUEST['expeditii'])) {
            $cond .= " AND GROUP_CONCAT(ep.expeditie) LIKE '%".$_REQUEST['expeditii']."%'";
        }

       // exit($cond);

        $searchOn = false;
        if(isset($_REQUEST['_search'])) $searchOn = $this->Strip($_REQUEST['_search']);
        if ($searchOn == 'true') {
            $searchstr = $this->Strip($_REQUEST['filters']);
            $cond .= $this->constructWhere($searchstr);
            $cond = str_replace('expeditii','expeditie',$cond);
        }

        $page = intval($_REQUEST['page'] ?? 1);
		$limit = intval($_REQUEST['rows'] ?? 20);
		$sidx = trim($this->sanitize($_REQUEST['sidx'] ?? 1));
		$sord = trim($this->sanitize($_REQUEST['sord'] ?? 'asc'));

		preg_match("/AND  user  LIKE '(.*)'/", $cond, $output_array);

		if(isset($output_array[0])){
			$cond = str_replace($output_array[0], '',$cond);
			$having .= ' '. $output_array[0];
		}
        $base_query = "SELECT
			(
				SELECT us.user FROM ist_exp ie
				INNER JOIN users us ON ie.operator=us.id
				WHERE operatiune = 23 AND ie.cod_exp =  ep.cod_expeditie LIMIT 1
			) as user,
		a.id, a.trndate, a.invoice, a.receipt, a.sumamnt,
		GROUP_CONCAT(ep.expeditie) as expeditii,
		COUNT(ep.expeditie ) AS nr_expeditii,
		( a.sumamnt - SUM( ep.valoare_totala_expeditie + ep.tva  ) )  AS diferenta,
		SUM(ep.valoare_totala_expeditie) AS valoare_totala_expeditie,
		SUM( ep.valoare_totala_expeditie + ep.tva ) AS val_exp,
		ep.data_expeditie, ep.expeditie,
		cle.nume as expeditor, clp.nume as platitor,
		IF(cle.zona_id > 0 and clec.id > 0, clec.nume, cee.nume) as expeditor_centru,
		IF(clp.zona_id > 0 and clpc.id > 0, clpc.nume, cep.nume) as platitor_centru,
		agp.nume_ag as curier_preluare, agl.nume_ag as curier_livrare
		FROM exp_facturi a
		INNER JOIN exp_prelucrate ep on (a.id = ep.idfact and ep.anulata = 0)
		left join clienti cle on cle.cod_cl = ep.expeditor_id
		left join clienti clp on ep.platitor_id = clp.cod_cl
		left join localitati lce ON lce.cod_lc = cle.cod_lc
		left join localitati lcp on clp.cod_lc = lcp.cod_lc
		left join centre cee ON cee.id = lce.cod_centru
		left join centre cep on lcp.cod_centru = cep.id
		left join agenti agp ON agp.cod_ag = ep.curier_preluare_id
        left join agenti agl ON agl.cod_ag = ep.curier_livrare_id
		LEFT JOIN zones clez ON clez.id = cle.zona_id
		LEFT JOIN centre clec on clec.id = clez.centru_id
		LEFT JOIN zones clpz ON clpz.id = clp.zona_id
		LEFT JOIN centre clpc on clpc.id = clpz.centru_id
		WHERE
		{$cond}
		and a.anulata=0
		GROUP BY a.invoice
		{$having}";

        $query = "SELECT COUNT( * ) AS nr FROM ({$base_query}) final";

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

        $query="{$base_query} ORDER BY " . $sidx . " " . $sord . " LIMIT " . $start . " , " . $limit;

		$sql = $this->db->QFetchRowArray($query);
		$responce->total = 0;
		$responce->rezultat=[];
		if (!empty($sql)) {
			$responce->total = count($sql);
			foreach ($sql as $key => $row) {
				if($row['curier_livrare'] == 'Scan System'){
					$row['curier_livrare'] = $this->verificaCurierLivrare($row);
				}
				$responce->rows[$key]['id'] = $row['id'];
				$responce->rows[$key]['cell'] = array(
                    //$row['id'],
                    $row['invoice'],
					$row['trndate'],
					$row['receipt'],
					$row['expeditor'],
					$row['platitor'],
					$row['data_expeditie'],
					$row['nr_expeditii'],
                    $row['expeditor_centru'],
					$row['platitor_centru'],
					$row['curier_preluare'],
					$row['curier_livrare'],
					$row['valoare_totala_expeditie'],
					$row['val_exp'],
					$row['sumamnt'],
					$row['diferenta'],
					$row['user'],
                    $row['expeditii']
				);
			}
		}
        $responce->page = $page;
        $responce->total = $total_pages;
        $responce->records = $count;

		return json_encode($responce);
	}



    function ExportDiferenteFacturiCsv(){

        $arr=[];

		$arr[0][] = 'Factura';
		$arr[0][] = 'Data facturare';
		$arr[0][] = 'Chitanta';
		$arr[0][] = 'Expeditor';
		$arr[0][] = 'Platitor';
		$arr[0][] = 'Data expeditie';
		$arr[0][] = 'NR Expeditii';
		$arr[0][] = 'Expeditor Centru';
		$arr[0][] = 'Platitor Centru';
		$arr[0][] = 'Curier Preluare';
		$arr[0][] = 'Curier Livrare';
		$arr[0][] = 'Val exp';
		$arr[0][] = 'Val exp + tva';
		$arr[0][] = 'Facturat';
		$arr[0][] = 'Diferenta';
		$arr[0][] = 'Utilizator';
		$arr[0][] = 'Expeditii';
		$arr[0][] = '';

		$responce = new StdClass();
		if(isset($_REQUEST['data_start']) && isset($_REQUEST['data_final'])){
			$data_start = $this->TransformDate($_REQUEST['data_start'])." 00:00:00";
			$data_final = $this->TransformDate($_REQUEST['data_final'])." 23:59:59";
		}
		else
		{
			$today = date('Y-m-d');
			$data_start = $today." 00:00:00";
			$data_final = $today." 23:59:59";
		}


		$cond = " a.trndate >='".$data_start."' AND a.trndate <='".$data_final."'";

		if(isset($_REQUEST['centru']) && $_REQUEST['centru'] > 0) {
			$cond .= " AND IF(clp.zona_id > 0 and clpc.id > 0, clpc.id, lcp.cod_centru) = ".intval($_REQUEST['centru']);
		}

		$having = " HAVING diferenta <> 0";
		if(isset($_REQUEST['diferente_zero']) && $_REQUEST['diferente_zero'] == 'true') {
			$having = '';
		}


		if(isset($_REQUEST['expeditii'])) {
			$cond .= " AND GROUP_CONCAT(ep.expeditie) LIKE '%".$_REQUEST['expeditii']."%'";
		}

		// exit($cond);

		$searchOn = false;
		if(isset($_REQUEST['_search'])) $searchOn = $this->Strip($_REQUEST['_search']);
		if ($searchOn == 'true') {
			$searchstr = $this->Strip($_REQUEST['filters']);
			$cond .= $this->constructWhere($searchstr);
			$cond = str_replace('expeditii','expeditie',$cond);
		}


		$time_op = "(
					SELECT ie.data_op FROM ist_exp ie
					WHERE operatiune = 23 AND ie.cod_exp =  ep.cod_expeditie LIMIT 1
				) as time_op,";

		$query = "SELECT
			(
				SELECT us.user FROM ist_exp ie
				INNER JOIN users us ON ie.operator=us.id
				WHERE operatiune = 23 AND ie.cod_exp =  ep.cod_expeditie LIMIT 1
			) as user,
			{$time_op}

			a.id, a.trndate, a.invoice, a.receipt, a.sumamnt,
			GROUP_CONCAT(ep.expeditie) as expeditii,
			COUNT(ep.expeditie ) AS nr_expeditii,
			( a.sumamnt - SUM( ep.valoare_totala_expeditie + ep.tva  ) )  AS diferenta,
			SUM(ep.valoare_totala_expeditie) AS valoare_totala_expeditie,
			SUM( ep.valoare_totala_expeditie + ep.tva ) AS val_exp,
			ep.data_expeditie, ep.expeditie,
			cle.nume as expeditor, clp.nume as platitor,
			IF(cle.zona_id > 0 and clec.id > 0, clec.nume, cee.nume) as expeditor_centru,
			IF(clp.zona_id > 0 and clpc.id > 0, clpc.nume, cep.nume) as platitor_centru,
			agp.nume_ag as curier_preluare, agl.nume_ag as curier_livrare
			FROM exp_facturi a
			INNER JOIN exp_prelucrate ep on (a.id = ep.idfact and ep.anulata = 0)
			left join clienti cle on cle.cod_cl = ep.expeditor_id
			left join clienti clp on ep.platitor_id = clp.cod_cl
			left join localitati lce ON lce.cod_lc = cle.cod_lc
			left join localitati lcp on clp.cod_lc = lcp.cod_lc
			left join centre cee ON cee.id = lce.cod_centru
			left join centre cep on lcp.cod_centru = cep.id
			left join agenti agp ON agp.cod_ag = ep.curier_preluare_id
			left join agenti agl ON agl.cod_ag = ep.curier_livrare_id
			LEFT JOIN zones clez ON clez.id = cle.zona_id
			LEFT JOIN centre clec on clec.id = clez.centru_id
			LEFT JOIN zones clpz ON clpz.id = clp.zona_id
			LEFT JOIN centre clpc on clpc.id = clpz.centru_id
			WHERE
			{$cond}
			and a.anulata=0
			GROUP BY a.invoice
			{$having}";

        //print_r($query);die;
        $sql = $this->db->QFetchRowArray($query);
        if (!empty($sql)) {
            foreach ($sql as $key => $row) {
				if($row['curier_livrare'] == 'Scan System'){
					$row['curier_livrare'] = $this->verificaCurierLivrare($row);
				}

				$arr[($key+1)][] = $row['invoice'];
				$arr[($key+1)][] = $row['trndate'];
                $arr[($key+1)][] = $row['receipt'];
                $arr[($key+1)][] = $row['expeditor'];
                $arr[($key+1)][] = $row['platitor'];
                $arr[($key+1)][] = $row['data_expeditie'];
                $arr[($key+1)][] = $row['nr_expeditii'];
                $arr[($key+1)][] = $row['expeditor_centru'];
                $arr[($key+1)][] = $row['platitor_centru'];
                $arr[($key+1)][] = $row['curier_preluare'];
				$arr[($key+1)][] = $row['curier_livrare'];
                $arr[($key+1)][] = $row['valoare_totala_expeditie'];
                $arr[($key+1)][] = $row['val_exp'];
                $arr[($key+1)][] = $row['sumamnt'];
                $arr[($key+1)][] = $row['diferenta'];
				$arr[($key+1)][] = $row['user'];
				$arr[($key+1)][] = str_replace(","," ",$row['expeditii']);
				$arr[($key+1)][] = date("m/d/Y H:i:s",strtotime($row['time_op']));
            }
        }

        $this->download_send_headers("diferente_facturi_" . date("Y-m-d") . ".csv");
        echo $this->array2csv($arr, "2048M");
        die;
    }


    function verificaCurierLivrare($row){
		$query = "SELECT sc.cod, sc.tip, sc.data, ce.nume as centru, 
			ag.nume_ag as curier, ru.denumire, ck.denumire as tip_scanare
			FROM scanari_coduri as sc
			LEFT JOIN centre as ce ON sc.centru=ce.id
			LEFT JOIN agenti as ag ON sc.curier=ag.cod_ag
			LEFT JOIN rute as ru ON sc.ruta=ru.id
			left join checkpoints as ck ON ck.id = sc.tip
			WHERE sc.expeditie = :awb and sc.is_awb = 1
			ORDER BY sc.id DESC LIMIT 1";
		$sql = $this->db->QFetchArray($query, ['awb'=>$row['expeditie']]);
		$data= 'Data';
		$tip_scanare = 'Tip Scanare';
		// $centru='Centru';
		$curier='Curier';
		// if(!empty($sql['data'])) $data = $sql['data'];
		// if(!empty($sql['tip_scanare'])) $tip_scanare = $sql['tip_scanare'];
		// if(!empty($sql['centru'])) $centru = $sql['centru'];
		if(!empty($sql['curier'])) $curier = $sql['curier'];
		$result = $curier;
		return $result;
    }

    function DiferenteFacturaDetalii(){
        $vars=[];

		$query = "SELECT ep.expeditie , ef.invoice, ef.receipt
			FROM exp_facturi ef
			LEFT JOIN exp_prelucrate ep ON (ef.id = ep.idfact and ep.anulata = 0)
			WHERE ef.id = ".intval($_POST['factura'])." AND ef.anulata = 0 ";

       //error_log($query);
		$sql = $this->db->QFetchRowArray($query);
        $exp_list = "";
		if (!empty($sql)) {
			if(count($sql) == 1){
				$_POST['expeditie'] = $sql[0]['expeditie'];
                $expeditii = new ModulExpeditii($this->config, 1, $this->db);
                exit($expeditii->DetaliiExpeditie());
			} else {
				foreach ($sql as $key => $row) {
                    $exp_list .= '<a href="#" onclick="DetaliiExpeditiiEditare(\''.$this->config['http'].'expeditii/introducere/'.$row['expeditie'].'\');return false;">'.$row['expeditie'].'</a><br>';
				}
			}

		}
        $vars['expeditii_list'] = $exp_list;
        exit($this->Parse($this->page_prefix . 'diferente_detalii_factura.html', $vars));
    }

/*/////////////////////////////////////////////////////////////
				 END FACTURI RESTANTE
/////////////////////////////////////////////////////////////*/

	function Pdf($client){
		require_once "FacturaPdf.php";

		if(empty($client)) return $this->Error('Client invalida!');

		$filename = 'NT-'.$client.'.pdf';
		$pdf = new FacturaPdf();
		$pdf->AddPage();
		$pdf->make($pdf,$client);
		// move pointer to last page
		//$pdf->lastPage();

		//I: send the file inline to the browser. The plug-in is used if available. The name given by filename is used when one selects the "Save as" option on the link generating the PDF.
		//D: send to the browser and force a file download with the name given by filename.
		$type = 'I';
		$pdf->Output($filename,$type);
		exit;
	}


	function getNumarFactura($serie, $width = 6 ){
		// echo $this->getNumarFactura('DSCS', $width = 6 );
		// exit();
		$query = "UPDATE facturi_increments SET increment = increment + 1 WHERE serie = :serie LIMIT 1";
		$this->db->Query($query, ['serie'=>$serie]);
		$query = "SELECT * FROM facturi_increments WHERE serie = '{$serie}';";
		$result = $this->db->QFetchArray($query);
		$padded = str_pad((string)$result['increment'], $width, "0", STR_PAD_LEFT);
		return $serie."-".$padded;
	}


	function getClienti(){
		$query = "SELECT nume, localitate_sediu_social, GROUP_CONCAT( cod_cl ) AS coduri, count( cod_cl ) AS NR
			FROM clienti
			WHERE mod_plata IN ( 1, 2 )
			GROUP BY master , facturare_separata";
		$sql = $this->db->QFetchRowArray($query);
		if (!empty($sql)) {
			return $sql;
		}
		return [];
	}

	function VerificareCui(){
        if(!empty($_POST['cui'])) {
			$cuiRO = ExpeditieDto::sanitizeCuiRO($_POST['cui']);
			$cui = ExpeditieDto::sanitizeCuiRO($_POST['cui'], true);
			if(empty($cui)) {
				return json_encode(array('cui_cautat'=>$cuiRO,'eroare'=>1, 'denumire'=>"CUI/CNP invalid" , 'adresa' => "", 'mesaj'=>"CUI/CNP invalid"));
			}
			if(!ExpeditieDto::isValidCui($cui))
				return json_encode(array('cui_cautat'=>$cuiRO,'eroare'=>-1, 'denumire'=>"" , 'adresa' => "", 'mesaj'=>""));
			//error_log("cui : {$cui}");
			$tNow = new DateTime();
			require_once("anafClient.php");
			$anafClient = new AnafClient();
			$anafClient->addCui($cui, $tNow->format("Y-m-d"));
			$raspuns = $anafClient->getOneResult();
			//error_log(print_r($raspuns, true));
			if($raspuns !== false && $raspuns->date_generale->cui) {
				return json_encode(array(
                    'cui_cautat'=> $cui,
                    'eroare'     => 0,
                    'cui'        => isset($raspuns->date_generale->cui)?$raspuns->date_generale->cui:0,
                    'tva'        => isset($raspuns->inregistrare_scop_Tva->scpTVA)?$raspuns->inregistrare_scop_Tva->scpTVA:false,
                    'denumire'   => !empty($raspuns->date_generale->denumire)?$raspuns->date_generale->denumire:"",
                    'adresa'     => (isset($raspuns->date_generale->adresa) && !empty($raspuns->date_generale->adresa->raw)) ? $raspuns->date_generale->adresa->raw : "cui lipsa la anaf",
					'adresaOnly' => isset($raspuns->date_generale->adresa) ? (empty($raspuns->date_generale->adresa->strada)? "" : $raspuns->date_generale->adresa->strada) . (empty($raspuns->date_generale->adresa->numar) ? "" : ", ".$raspuns->date_generale->adresa->numar) . (empty($raspuns->date_generale->adresa->altele) ? "" : ", ".$raspuns->date_generale->adresa->altele) : "",
					'regCom'     => isset($raspuns->date_generale->nrRegCom)?$raspuns->date_generale->nrRegCom:"",
                    'mesaj'      => !empty($raspuns->inregistrare_scop_Tva->mesaj_ScpTVA)?$raspuns->inregistrare_scop_Tva->mesaj_ScpTVA:""

                ));
			}
			return json_encode(array('cui_cautat'=>$cuiRO,'eroare'=>1, 'denumire'=>'eroare ANAF' , 'adresa' => "",'mesaj'=>"Eroare server Anaf"));
		}
        return json_encode(['eroare'=>1, 'mesaj' => 'unknown']);
	}

	/*/////////////////////////////////////////////////////////////
				 PRINT DECONT FACTURI
	/////////////////////////////////////////////////////////////*/

	function DecontCautare(){
		$this->vars['title_page'] = 'Facturi android';
		return $this->Parse($this->page_prefix . 'decont_cautare.html', []);
	}

	function JSON_DecontCautare() {
		$responce = new StdClass();
		$cond = "1=1 ";
		$flag=0;

		if(isset($_GET['data_start']) && isset($_GET['data_final'])){
			$data_start = $this->TransformDate($this->sanitize(urldecode($_GET['data_start']))). " 00:00:01";
			$data_final = $this->TransformDate($this->sanitize(urldecode($_GET['data_final']))). " 23:59:59";
			$cond .= " AND df.data >= '".$data_start."' AND df.data <= '".$data_final."'";
			$flag=1;
		}
		if(isset($_GET['operatiune']) && !empty($_GET['search'])){
			if($_GET['operatiune']==1){
				$cond .= " AND df.serie like '". $this->sanitize($_GET['search']) ."'";
				$flag=1;
			}
			else if($_GET['operatiune']==2){
				$cond .= " AND de.expeditie like ".intval($this->sanitize($_GET['search']));
				$flag=1;
			}

		}
		if(!empty($_GET['cod_ag'])){
			$cond .= " AND df.agent_id=".intval($this->sanitize($_GET['cod_ag']));
			$flag=1;
		}
		if(!empty($_GET['cod_cl'])){
			$cond .= " AND ep.expeditor_id=".intval($this->sanitize($_GET['cod_cl']));
			$flag=1;
		}

		if(empty($flag)) $cond = "1=2";

		if(!empty($_POST['expeditii'])){
			$expeditii = parent::ValidareExpeditiiCurata($_POST['expeditii']);
			if(!empty($expeditii)){
				$cond = " de.expeditie IN (".$expeditii.") ";
				$flag=1;
			}
		}

		$searchOn = '';
		//start generare conditie
		if(isset($_POST['_search']))
			$searchOn = $this->sanitize($_POST['_search']);
		if ($searchOn == 'true') {
			$searchstr = $this->Strip($_POST['filters']);
			$cond .= $this->constructWhere($searchstr);
		}

		$cond = str_replace("decontata  = '0'","df.decont_id = 0", $cond);
		$cond = str_replace("decontata  = '1'","df.decont_id > 0", $cond);
		$cond = str_replace("tipPlata  = '0'","df.transaction_id = 0", $cond);
		$cond = str_replace("tipPlata  = '1'","df.transaction_id > 0", $cond);

		$page = intval($_REQUEST['page'] ?? 1);
		$limit = intval($_REQUEST['rows'] ?? 100);
		$sidx = trim($this->sanitize($_REQUEST['sidx'] ?? 1));
		$sord = trim($this->sanitize($_REQUEST['sord'] ?? 'asc'));

		$query = "select count(distinct df.id) as nr, sum(df.suma) as suma
			from decont_facturi df
			inner join decont_expeditii de on de.factura_id=df.id
			left JOIN exp_prelucrate ep on (de.expeditie = ep.expeditie and ep.anulata = 0)
			left join clienti cle on ep.expeditor_id = cle.cod_cl
			left join clienti cld on ep.destinatar_id = cld.cod_cl
			left join agenti ag on de.agent_id = ag.cod_ag
			left join localitati lce on lce.cod_lc = cle.cod_lc
			left join centre cee on cee.id = lce.cod_centru
			left join localitati lcd on lcd.cod_lc = cld.cod_lc
			left join centre ced on ced.id = lcd.cod_centru
			LEFT JOIN zones clez ON clez.id = cle.zona_id
        	LEFT JOIN centre clec on clec.id = clez.centru_id
			LEFT JOIN zones cldz ON cldz.id = cld.zona_id
        	LEFT JOIN centre cldc on cldc.id = cldz.centru_id
			where {$cond} and df.anulata = 0";
		
		//error_log($cond);

		$result = $this->db->QFetchArray($query);
		$count = !empty($result['nr']) ? $result['nr'] : 0;
		$suma_totala = !empty($result['suma']) ? number_format($result['suma'], 2, '.', '') : '0.00';

		if( $count >0 ) {$total_pages = ceil($count/$limit); }
		else { $total_pages = 0; }
		if ($page > $total_pages) $page=$total_pages;
		if ($limit<0) $limit = 0;
		$start = $limit*$page - $limit; // do not put $limit*($page - 1)
		if ($start<0) $start = 0;

		$query = "select df.id, df.data, df.suma, df.serie, df.cui, df.proc_tva as procTva,
			group_concat(de.expeditie) as expeditii, cle.nume as expeditor, cld.nume as destinatar, ag.nume_ag as curier, ep.primitor,
			IF(cle.zona_id > 0 and clec.id > 0, clec.label, cee.label) as expeditor_centru_cod,
			IF(cld.zona_id > 0 and cldc.id > 0, cldc.label, ced.label) as destinatar_centru_cod,
			ep.tip_plata, IF(df.decont_id > 0, 1, 0) as decontata,
			if(df.transaction_id > 0, 1, 0) as tipPlata
			from decont_facturi df
			inner join decont_expeditii de on de.factura_id=df.id
			left JOIN exp_prelucrate ep on (de.expeditie = ep.expeditie and ep.anulata = 0 and ep.mod_plata = 0)
			left join clienti cle on ep.expeditor_id = cle.cod_cl
			left join clienti cld on ep.destinatar_id = cld.cod_cl
			left join agenti ag on de.agent_id = ag.cod_ag
			left join localitati lce on lce.cod_lc = cle.cod_lc
			left join centre cee on cee.id = lce.cod_centru
			left join localitati lcd on lcd.cod_lc = cld.cod_lc
			left join centre ced on ced.id = lcd.cod_centru
			LEFT JOIN zones clez ON clez.id = cle.zona_id
        	LEFT JOIN centre clec on clec.id = clez.centru_id
			LEFT JOIN zones cldz ON cldz.id = cld.zona_id
        	LEFT JOIN centre cldc on cldc.id = cldz.centru_id
			where {$cond} and df.anulata = 0
			group by df.id
			ORDER BY " . $sidx . " " . $sord . " LIMIT " . $start . " , " . $limit;

		$sql = $this->db->QFetchRowArray($query);
		//error_log($query);
		if (!empty($sql)) {
			foreach ($sql as $key => $row) {
				$responce->rows[$key]['id'] = $row['id'];
				$responce->rows[$key]['cell'] = array($row['serie'], $row['suma'], $row['procTva'], $row['data'], $row['expeditii'], strtoupper($row['expeditor']), strtoupper($row['destinatar']), strtoupper($row['expeditor_centru_cod']), strtoupper($row['destinatar_centru_cod']), strtoupper($row['curier']), $row['primitor'], $row['cui'], $row['tipPlata'], $row['decontata']);
			}
		}

		$responce->page = $page;
		$responce->total = $total_pages;
		$responce->records = $count;
		$responce->userdata['suma'] = $suma_totala;
		return json_encode($responce);
	}

	function DecontEditFactura() {
		$id = intval(Backend::sSanitize($_POST['id'] ?? 0));
		if($id == 0) return 0;

		$client = $this->sanitize(strtoupper(str_replace("&AMP;", '&', strtoupper($_POST['client'] ?? ""))));
		$cui = $this->sanitize($_POST['cui'] ?? "");
		$adresa = $this->sanitize($_POST['adresa'] ?? "");

		$vars = $this->GenerareFieldsFactura($id, $client, $cui, $adresa);
		if(empty($vars) || empty($vars['id']) || empty($vars['expeditii'])) return 0;

		$this->db->QueryUpdate('decont_facturi', ['cui'=>$vars['cui']], "id=".intval($id));
		$this->db->QueryUpdate('decont_expeditii', ['cui'=>$vars['cui'], 'client'=>$vars['client'], 'adresa'=>$vars['adresa']], "factura_id=".$id);
		return 1;
	}

	function DecontPrintOneFacturaTCPDF() {
		require_once "FacturaAndroidPdf.php";
		$pData = base64_decode($_POST['pJson'] ?? '');
		if(empty($pData)) return;
		$pData = json_decode($pData, true);
		if(!is_array($pData)) return;


		$id = intval($pData['id'] ?? 0);
		if($id == 0) return;

		$client = $this->sanitize(strtoupper(str_replace("&AMP;", '&', strtoupper($pData['client'] ?? ""))));
		$cui = $this->sanitize($pData['cui'] ?? "");
		$adresa = $this->sanitize($pData['adresa'] ?? "");

		$vars = $this->GenerareFieldsFactura($id, $client, $cui, $adresa);
		if(empty($vars) || empty($vars['id']) || empty($vars['expeditii'])) return 0;
		$vars['isFactura'] = true;

		$this->db->QueryUpdate('decont_facturi', ['cui'=>$vars['cui']], "id=".intval($id));
		$this->db->QueryUpdate('decont_expeditii', ['cui'=>$vars['cui'], 'client'=>$vars['client'], 'adresa'=>$vars['adresa']], "factura_id=".$id);

		$pdf = new FacturaAndroidPdf();
		$pdf->setVars($vars);
		$pdf->AddPage();
		$pdf->makeFacturaAndroid();
		$pdf->AddPage();
		$pdf->makeChitantaFiscalaAndroid();
		$pdf->lastPage();

		$filename = $vars['factura'].'.pdf';

		//I: send the file inline to the browser. The plug-in is used if available. The name given by filename is used when one selects the "Save as" option on the link generating the PDF.
		//D: send to the browser and force a file download with the name given by filename.
		$type = 'D';
		$pdf->Output($filename, $type);
		exit;
	}

	function GenerareFieldsFactura($id, $client = "", $cui = "", $adresa = ""){
		if(empty($id)) return [];

		$cond = "df.id = ".$id;

		$query = "SELECT df.id, df.serie, df.proc_tva, df.data as dataInc, df.suma, df.cui, de.client as factura_client, de.adresa as factura_adresa,
			group_concat(ep.expeditie) as expeditii, ag.nume_ag as agent, ce.label as centru, ep.tip_plata, cle.nume as expeditor_nume
			from decont_facturi df
			inner join decont_expeditii de on de.factura_id=df.id
			inner join agenti ag on de.agent_id = ag.cod_ag
			left join exp_prelucrate ep on ep.expeditie = de.expeditie and ep.anulata = 0 and ep.mod_plata = 0
			left join clienti cle on cle.cod_cl = ep.expeditor_id
			left join centre ce on ag.cod_centru = ce.id
			where {$cond} and df.anulata = 0
			group by df.id
			limit 1";

		$factura = $this->db->QFetchArray($query);
		if(empty($factura)) return [];

		$vars = array();
		$vars['id'] = $factura['id'];
		$vars['factura'] = $factura['serie'];
		$vars['proc_tva'] = $factura['proc_tva'];
		$vars['dataInc'] = new DateTime($factura['dataInc']);
		$vars['dataInc'] = $vars['dataInc']->format('d.m.Y H:i:s');
		$vars['expeditii'] = $factura['expeditii'];
		$vars['suma'] = $factura['suma'];
		$vars['expeditor'] = strtoupper($factura['expeditor_nume']);
		$vars['centru'] = strtoupper($factura['centru']);
		$vars['agent'] = strtoupper($factura['agent']);
		$vars['client'] = empty($client) ? strtoupper(str_replace("&AMP;", '&', strtoupper($factura['factura_client']))) : strtoupper(str_replace("&AMP;", '&', strtoupper($client)));
		$vars['cui'] = empty($cui) ? "" : (!ExpeditieDto::isValidCui($cui) ? strtoupper($factura['cui']) : strtoupper($cui));
		//error_log(intval(!ExpeditieDto::isValidCui($cui)) . ':d:' . strtoupper($cui));
		$vars['adresa'] = empty($adresa) ? strtoupper($factura['factura_adresa']) : strtoupper($adresa);

		return $vars;
	}

	function DecontExportFacturi() {
		$today = date('Y-m-d');
		$cond = "df.anulata = 0 ";
		$flag=0;
		if(isset($_POST['data_start']) && isset($_POST['data_final'])){
			try {
				$data_start = DateTimeImmutable::createFromFormat('d.m.Y', urldecode($_POST['data_start']));
				$data_final = DateTimeImmutable::createFromFormat('d.m.Y', urldecode($_POST['data_final']));
				if($data_start && $data_final) {
					if(intval($data_start->diff($data_final, true)->format('%a')) > 31){
						$data_final = $data_start;
						$data_start = $data_start->format('Y-m-d');
						$data_final = $data_final->add(new DateInterval('P1M'));
						$data_final = $data_final->format('Y-m-d');
					}
					else {
						$data_start = $data_start->format('Y-m-d');
						$data_final = $data_final->format('Y-m-d');
					}

				}
				else {
					$data_start = $data_final = $today;
				}
			}
			catch (Exception $e){
				$data_start = $today;
	    		$data_final = $today;
			}
			$cond .= " AND DATE(df.data) between '{$data_start}' AND '{$data_final}'";
			$flag=1;
		}

		$searchOn = '';
		//start generare conditie
		if(isset($_POST['_search']))
			$searchOn = $this->sanitize($_POST['_search']);
		if ($searchOn == 'true') {
			$searchstr = $this->Strip($_POST['filters']);
			$cond .= $this->constructWhere($searchstr);
		}

		$cond = str_replace("decontata  = '0'","df.decont_id = 0", $cond);
		$cond = str_replace("decontata  = '1'","df.decont_id > 0", $cond);
		$cond = str_replace("tipPlata  = '0'","df.transaction_id = 0", $cond);
		$cond = str_replace("tipPlata  = '1'","df.transaction_id > 0", $cond);

		if(isset($_POST['operatiune']) && !empty($_POST['search'])){
			if($_POST['operatiune']==1){
				$cond .= " AND df.serie like '". $this->sanitize($_POST['search']) ."'";
			}
			else if($_POST['operatiune']==2){
				$cond .= " AND de.expeditie like ".intval($this->sanitize($_POST['search']));
			}

		}
		if(!empty($_POST['cod_ag'])){
			$cond .= " AND df.agent_id=".intval($this->sanitize($_POST['cod_ag']));
		}
		if(!empty($_POST['cod_cl'])){
			$cond .= " AND ep.expeditor_id=".intval($this->sanitize($_POST['cod_cl']));
		}

		if(!empty($_POST['expeditii'])){
			$expeditii = parent::ValidareExpeditiiCurata($_POST['expeditii']);
			if(!empty($expeditii)){
				$cond = "de.anulata = 0 and de.expeditie IN (".$expeditii.") ";
				$flag=1;
			}
		}
		if(empty($flag)) $cond = false;

		$sidx = trim($this->sanitize($_POST['sidx'] ?? 1));
		$sord = trim($this->sanitize($_POST['sord'] ?? 'asc'));

		$data = date('d/m/Y');
 		$societate = 'Dragon Star Curier';
		$d = 'Lista facturi android '.$data;

		$spreadsheet = new Spreadsheet();
		$spreadsheet->setActiveSheetIndex(0);
		$worksheet = $spreadsheet->getActiveSheet();
		$spreadsheet->getProperties()->setCreator($societate)
                ->setLastModifiedBy($societate)
                ->setTitle($d)
                ->setSubject($d)
                ->setDescription($d)
                ->setKeywords($d)
                ->setCategory($d);
		$spreadsheet->getDefaultStyle()->getFont()->setName('Arial');
		$spreadsheet->getDefaultStyle()->getFont()->setSize(10);

		foreach (range('A', 'P') as $letter) {
			$worksheet->getStyle($letter.'1')->getFont()->setSize(10);
			$worksheet->getStyle($letter.'1')->getFont()->setBold(true);
			$worksheet->getColumnDimension($letter)->setAutoSize(true);
		}
		//'Chitanta', 'Valoare', 'Data', 'Descriere', 'Client','CUI', 'Agent', 'Centru'
		$worksheet->setCellValue('A1','Factura');
        $worksheet->setCellValue('B1','Valoare');
		$worksheet->setCellValue('C1','Tva');
		$worksheet->setCellValue('D1','Data');
		$worksheet->setCellValue('E1','Expeditii');
        $worksheet->setCellValue('F1','Expeditor');
		$worksheet->setCellValue('G1','Destinatar');
		$worksheet->setCellValue('H1','Centru exp.');
		$worksheet->setCellValue('I1','Centru dest.');
		$worksheet->setCellValue('J1','Agent');
		$worksheet->setCellValue('K1','Primitor');
		$worksheet->setCellValue('L1','Client');
		$worksheet->setCellValue('M1','CUI');
		$worksheet->setCellValue('N1','Incasat');
		$worksheet->setCellValue('O1','Decontata');
		$worksheet->setCellValue('P1','Operatiune');

		if($cond !== false){
			$query = "select df.id, df.data, df.suma, df.serie, de.client, df.cui, df.proc_tva as procTva, IF(df.operatiune = 2, 'LIVRARE', 'PRELUARE') as operatiune,
				group_concat(de.expeditie) as expeditii, cle.nume as expeditor, cld.nume as destinatar, ag.nume_ag as curier, ep.primitor,
				IF(cle.zona_id > 0 and clec.id > 0, clec.label, cee.label) as expeditor_centru_cod,
				IF(cld.zona_id > 0 and cldc.id > 0, cldc.label, ced.label) as destinatar_centru_cod,
				ep.tip_plata, IF(df.decont_id > 0, 'DA', 'NU') as decontata,
				if(df.transaction_id > 0, 'CARD', 'CASH') as tipPlata
				from decont_facturi df
				inner join decont_expeditii de on de.factura_id=df.id
				left JOIN exp_prelucrate ep on (de.expeditie = ep.expeditie and ep.anulata = 0 and ep.mod_plata = 0)
				left join clienti cle on ep.expeditor_id = cle.cod_cl
				left join clienti cld on ep.destinatar_id = cld.cod_cl
				left join agenti ag on de.agent_id = ag.cod_ag
				left join localitati lce on lce.cod_lc = cle.cod_lc
				left join centre cee on cee.id = lce.cod_centru
				left join localitati lcd on lcd.cod_lc = cld.cod_lc
				left join centre ced on ced.id = lcd.cod_centru
				LEFT JOIN zones clez ON clez.id = cle.zona_id
				LEFT JOIN centre clec on clec.id = clez.centru_id
				LEFT JOIN zones cldz ON cldz.id = cld.zona_id
				LEFT JOIN centre cldc on cldc.id = cldz.centru_id
				where {$cond}
				group by df.id
				ORDER BY " . $sidx . " " . $sord;

			$sql = $this->db->QFetchRowArray($query);
			$rand=2;
			if (!empty($sql)) {
				foreach ($sql as $key => $row) {
					$worksheet->setCellValueExplicit('A'.($rand+$key), $row['serie'], \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
					$worksheet->setCellValue('B'.($rand+$key),number_format($row['suma'], 2, ',',''));
					$worksheet->setCellValue('C'.($rand+$key),round($row['procTva'], 0));
					$row['data'] = \PhpOffice\PhpSpreadsheet\Shared\Date::PHPToExcel(new DateTime($row['data']));
					$worksheet->setCellValue('D'.($rand+$key),$row['data']);
					$worksheet->getStyle('D'.($rand+$key))->getNumberFormat()->setFormatCode('dd.mm.YYYY');
					$worksheet->setCellValueExplicit('E'.($rand+$key), $row['expeditii'], \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
					$worksheet->setCellValueExplicit('F'.($rand+$key), $row['expeditor'], \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
					$worksheet->setCellValueExplicit('G'.($rand+$key), $row['destinatar'], \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
					$worksheet->setCellValueExplicit('H'.($rand+$key), $row['expeditor_centru_cod'], \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
					$worksheet->setCellValueExplicit('I'.($rand+$key), $row['destinatar_centru_cod'], \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
					$worksheet->setCellValueExplicit('J'.($rand+$key), $row['curier'], \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
					$worksheet->setCellValueExplicit('K'.($rand+$key), $row['primitor'], \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
					$worksheet->setCellValueExplicit('L'.($rand+$key), $row['client'], \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
					$worksheet->setCellValueExplicit('M'.($rand+$key), $row['cui'], \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
					$worksheet->setCellValueExplicit('N'.($rand+$key),$row['tipPlata'], \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
					$worksheet->setCellValueExplicit('O'.($rand+$key),$row['decontata'], \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
					$worksheet->setCellValueExplicit('P'.($rand+$key),$row['operatiune'], \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
				}
			}
		}

        $data = date('Y_m_d');
        $filename = $data.'_FacturiAndroid.xlsx';

		header('Cache-Control: max-age=0');
        header('Content-Type: application/vnd.ms-excel');
        header("Content-Type: application/force-download");
        header("Content-Type: application/octet-stream");
        header("Content-Type: application/download");
        header("Content-Disposition: attachment;filename=$filename");
        header("Content-Transfer-Encoding: binary");
        $writer = new Xlsx($spreadsheet);
		$writer->save("php://output");
		die;
	}

	function DecontDetaliiFactura($id = 0){
		$id = intval($id);
		if($id == 0) return 0;

		$query = "SELECT df.*, group_concat(de.expeditie) as expeditii, group_concat(dr.ch_ramburs) as rambursuri, 
			de.client as factura_client, cl.localitate as factura_localitate, de.adresa as factura_adresa, ag.nume_ag as agent
			from decont_facturi df
			inner join decont_expeditii de on de.factura_id = df.id
			left join exp_prelucrate ep on ep.expeditie = de.expeditie and ep.anulata = 0 and ep.mod_plata = 0
			left join decont_rbs dr on (de.ramburs_id = dr.id and dr.anulata = 0)
			inner join agenti ag on de.agent_id = ag.cod_ag
			left join clienti cl on cl.cod_cl = df.client_id
			where df.id = {$id} and df.anulata = 0
			group by df.id";
		$sql = $this->db->QFetchArray($query);
		if(empty($sql)) return 0;

		$sql['data'] = new DateTime($sql['data']);
		$sql['data'] = $sql['data']->format('d.m.Y H:i');

		return '1|||'.$sql['id'].'|||'.$sql['agent_id'].'|||'.$sql['data'].'|||'.$sql['expeditii'].'|||'.$sql['agent'].'|||'
			.$sql['serie'].'|||'.$sql['suma'].'|||'.$sql['factura_client'].'|||'.$sql['factura_localitate'].'|||'.$sql['factura_adresa']
			.'|||'.$sql['cui'].'|||'.$sql['rambursuri'].'|||'.($sql['transaction_id'] > 0 ? 'CARD' : 'CASH');

	}

	function StergeFacturaDecont() {
		$id = intval(Backend::sSanitize($_POST['id'] ?? 0));
		if($id == 0) return 0;
		if(!($this->user_id == parent::DOINA || $this->user_id == parent::MARIAN || $this->user_id == parent::MARIUS_TELER)) return 0;

		$query = "SELECT df.id, df.serie, df.transaction_id, ef.id as idfact
			from decont_facturi df 
			left join exp_facturi ef on df.serie = ef.invoice
			where df.id = {$id} and df.anulata = 0";

		$sql = $this->db->QFetchArray($query);
		//nu se poate sterge o factura care are tranzactie sau a fost validata in exp_facturi
		if(empty($sql) || $sql['transaction_id'] > 0 || !empty($sql['idfact']) ) return 0;
		$this->db->QueryUpdate('decont_facturi', ['anulata' => 3], "id = {$id}");

		/*
		if(!empty($sql['rbsIds'])){
			$arr_rbsIds = explode(',', $sql['rbsIds']);
			$arr_rbsIds = array_keys(array_flip($arr_rbsIds));
			$arr_rbsIdsNew = [];
			foreach($arr_rbsIds as $rbsId){
				if(!empty($rbsId)) $arr_rbsIdsNew[] = intval($rbsId);
			}
			if(count($arr_rbsIdsNew) > 0){
				$rbsIds = implode(',', $arr_rbsIdsNew);
				$this->db->QueryUpdate('decont_rbs', ['anulata' => 3], " id in ({$rbsIds})");
			}
		}
		*/
		return 1;
	}
/*/////////////////////////////////////////////////////////////
				FIN PRINT FACTURI
	/////////////////////////////////////////////////////////////*/

	/*/////////////////////////////////////////////////////////////
				PRINT DECONT CHITANTE client CTR
	/////////////////////////////////////////////////////////////*/

	function DecontCautareCf(){
		$this->vars['title_page'] = 'Chitante client CTR';
		return $this->Parse($this->page_prefix . 'decont_cautare_cf.html', []);
	}

	function JSON_DecontCautareCf() {
		$responce = new StdClass();
		$cond = "dfc.anulata = 0 ";
		$flag=0;

		if(isset($_GET['data_start']) && isset($_GET['data_final'])){
			$data_start = $this->TransformDate($this->sanitize(urldecode($_GET['data_start']))). " 00:00:01";
			$data_final = $this->TransformDate($this->sanitize(urldecode($_GET['data_final']))). " 23:59:59";
			$cond .= " AND dfc.dataInc >= '".$data_start."' AND dfc.dataInc <= '".$data_final."'";
			$flag=1;
		}
		if(isset($_GET['chitanta']) && !empty($_GET['chitanta'])){
			$cond .= " AND dfc.ch_bon like '". $this->sanitize($_GET['chitanta']) ."'";
			$flag=1;
		}
		if(!empty($_GET['cod_ag'])){
			$cond .= " AND dfc.agent_id=".intval($this->sanitize($_GET['cod_ag']));
			$flag=1;
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

		$cond = str_replace("decontata  = '0'","dfc.decont_id = 0", $cond);
		$cond = str_replace("decontata  = '1'","dfc.decont_id > 0", $cond);
		$cond = str_replace("tipPlata  = '0'","dfc.transaction_id = 0", $cond);
		$cond = str_replace("tipPlata  = '1'","dfc.transaction_id > 0", $cond);

		$page = intval($_POST['page'] ?? 1);
		$limit = intval($_POST['rows'] ?? 100);
		$sidx = trim($this->sanitize($_POST['sidx'] ?? 1));
		$sord = trim($this->sanitize($_POST['sord'] ?? 'asc'));

		$query = "SELECT count(dfc.id) as nr, sum(dfc.suma) as suma
			FROM decont_chitante dfc
			LEFT JOIN agenti ag ON ag.cod_ag = dfc.agent_id
			LEFT JOIN clienti cl on dfc.client_id = cl.cod_cl
			LEFT JOIN centre ce on ce.id = ag.cod_centru
			where {$cond}";

		$result = $this->db->QFetchArray($query);
		$count = !empty($result['nr']) ? $result['nr'] : 0;
		$suma_totala = !empty($result['suma']) ? number_format($result['suma'], 2, '.', '') : '0.00';

		if( $count >0 ) {$total_pages = ceil($count/$limit); }
		else { $total_pages = 0; }
		if ($page > $total_pages) $page=$total_pages;
		if ($limit<0) $limit = 0;
		$start = $limit*$page - $limit; // do not put $limit*($page - 1)
		if ($start<0) $start = 0;

		$query = "SELECT dfc.id, dfc.dataInc, dfc.suma, dfc.descriere, dfc.ch_bon as chitanta, IF(dfc.decont_id > 0, 1, 0) as decontata,
			ag.nume_ag as agent, cl.nume as client, cl.cod_fiscal as cui, ce.label as centru, if(dfc.transaction_id > 0, 1, 0) as tipPlata
			FROM decont_chitante dfc
			LEFT JOIN agenti ag ON ag.cod_ag = dfc.agent_id
			LEFT JOIN clienti cl on dfc.client_id = cl.cod_cl
			LEFT JOIN centre ce on ce.id = ag.cod_centru
			where {$cond}
			ORDER BY " . $sidx . " " . $sord . " LIMIT " . $start . " , " . $limit;

		$sql = $this->db->QFetchRowArray($query);
		//error_log($query);
		if (!empty($sql)) {
			foreach ($sql as $key => $row) {
				$responce->rows[$key]['id'] = $row['id'];
				$responce->rows[$key]['cell'] = array($row['chitanta'], $row['suma'], $row['dataInc'], $row['descriere'], strtoupper($row['client']), strtoupper($row['cui']), strtoupper($row['agent']), strtoupper($row['centru']), $row['tipPlata'], $row['decontata']);
			}
		}

		$responce->page = $page;
		$responce->total = $total_pages;
		$responce->records = $count;
		$responce->userdata['suma'] = $suma_totala;
		return json_encode($responce);
	}

	function DecontPrintOneCfTCPDF() {
		require_once "FacturaAndroidPdf.php";
		$pData = base64_decode($_POST['pJson'] ?? '');
		if(empty($pData)) return;
		$pData = json_decode($pData, true);
		if(!is_array($pData)) return;

		$id = intval($pData['id'] ?? 0);
		if($id == 0) return;

		$vars = $this->GenerareFieldsCf($id);
		if(empty($vars) || empty($vars['id'])) return 0;

		$pdf = new FacturaAndroidPdf();
		$pdf->setVars($vars);
		$pdf->AddPage();
		$pdf->makeChitantaFiscalaClientAndroid();
		$pdf->lastPage();

		$filename = $vars['chitanta'].'.pdf';

		//I: send the file inline to the browser. The plug-in is used if available. The name given by filename is used when one selects the "Save as" option on the link generating the PDF.
		//D: send to the browser and force a file download with the name given by filename.
		$type = 'D';
		$pdf->Output($filename, $type);
		exit;
	}

	function GenerareFieldsCf($id, $suma = 0){
		if(empty($id)) return [];

		$cond = "dfc.id={$id} ";

		$query = "SELECT dfc.id, dfc.dataInc, dfc.ch_bon as chitanta, dfc.suma, dfc.agent_id,
			ag.nume_ag as agent, cl.nume as client, cl.cod_fiscal as cui, CONCAT(jd.nume_jd,', ',lc.nume_lc,', ',cl.adresa_sediu_social) as adresa,
			ce.label as centru
			FROM decont_chitante dfc
			LEFT JOIN agenti ag ON ag.cod_ag = dfc.agent_id
			LEFT JOIN clienti cl on dfc.client_id = cl.cod_cl
			LEFT JOIN localitati lc on cl.cod_lc = lc.cod_lc
			LEFT JOIN judete jd on lc.cod_jd = jd.cod_jd
			LEFT JOIN centre ce on ag.cod_centru = ce.id
			where {$cond}
			limit 1";

		$chitanta = $this->db->QFetchArray($query);
		if(empty($chitanta)) return [];

		$vars = array();
		$vars['id'] = $chitanta['id'];
		$vars['chitanta'] = $chitanta['chitanta'];
		$vars['dataInc'] = new DateTime($chitanta['dataInc']);
		$vars['dataInc'] = $vars['dataInc']->format('d.m.Y H:i:s');
		$vars['suma'] = number_format($chitanta['suma'], 2, '.', '');
		$vars['client'] = strtoupper($chitanta['client']);
		$vars['cui'] = strtoupper($chitanta['cui']);
		$vars['adresa'] = strtoupper($chitanta['adresa']);
		$vars['agent_id'] = $chitanta['agent_id'];
		$vars['agent'] = strtoupper($chitanta['agent']);
		$vars['centru'] = strtoupper($chitanta['centru']);

		return $vars;
	}

	function DecontDetaliiCf($id){
		if(intval($id) == 0) return 0;
		$query = "SELECT dfc.id, dfc.agent_id, dfc.dataInc,
			dfc.suma, dfc.descriere, dfc.ch_bon as chitanta, dfc.transaction_id,
			ag.nume_ag as agent, cl.nume as client, cl.cod_fiscal as cui, CONCAT(jd.nume_jd,', ',lc.nume_lc,', ',cl.adresa_sediu_social) as adresa
			FROM decont_chitante dfc
			LEFT JOIN agenti ag ON ag.cod_ag = dfc.agent_id
			LEFT JOIN clienti cl on dfc.client_id = cl.cod_cl
			LEFT JOIN localitati lc on cl.cod_lc = lc.cod_lc
			LEFT JOIN judete jd on lc.cod_jd = jd.cod_jd
			where dfc.id = ".intval($id);
		$sql = $this->db->QFetchArray($query);
		if(empty($sql)) return 0;

		$sql['dataInc'] = new DateTime($sql['dataInc']);
		$sql['dataInc'] = $sql['dataInc']->format('d.m.Y H:i');

		return '1|||'.$sql['id'].'|||'.$sql['agent_id'].'|||'.$sql['agent'].'|||'.$sql['dataInc'].'|||'.$sql['suma'].'|||'.$sql['descriere']
		.'|||'.$sql['chitanta'].'|||'.$sql['client'].'|||'.$sql['cui'].'|||'.$sql['adresa'].'|||'.($sql['transaction_id'] > 0 ? 'CARD' : 'CASH'	);

	}

	function DecontEditCf() {
		$id = intval(Backend::sSanitize($_POST['id'] ?? 0));
		return 0;
		if($id == 0) return 0;

		if(!($this->user_id == parent::DOINA || $this->user_id == parent::MARIAN || $this->user_id == parent::MARIUS_TELER)) return 0;

		//$suma = $this->sanitize($_POST['suma'] ?? "");

		//$vars = $this->GenerareFieldsCf($id, $suma);

		//$this->db->QueryUpdate('decont_chitante', ['suma'=>$suma], "id=".intval($id));
		return 1;
	}

	function DecontStergeCf() {
		$id = intval(Backend::sSanitize($_POST['id'] ?? 0));
		if($id == 0) return 0;
		if(!($this->user_id == parent::DOINA || $this->user_id == parent::MARIAN || $this->user_id == parent::MARIUS_TELER)) return 0;
		$query = "SELECT id
			from decont_chitante
			where id = ".intval($id)." and anulata = 0";
		$sql = $this->db->QFetchArray($query);
		if(empty($sql)) return 0;
		$this->db->QueryUpdate('decont_chitante', ['anulata'=>3], "id=".intval($id));
		return 1;
	}

	function DecontExportCf() {

		if(!($this->user_id == parent::DOINA || $this->user_id == parent::MARIAN || $this->user_id == parent::MARIUS_TELER)) return "Access Denied";

		$cond = "1=1";
		if(isset($_POST['data_start']) && isset($_POST['data_final'])){
			$data_start = $this->TransformDate($this->sanitize(urldecode($_POST['data_start']))). " 00:00:01";
			$data_final = $this->TransformDate($this->sanitize(urldecode($_POST['data_final']))). " 23:59:59";
			$cond .= " AND dfc.dataInc >= '".$data_start."' AND dfc.dataInc <= '".$data_final."'";
			$flag=1;
		}
		if(isset($_POST['chitanta']) && !empty($_POST['chitanta'])){
			$cond .= " AND dfc.ch_bon like '". $this->sanitize($_POST['chitanta']) ."'";
			$flag=1;
		}
		if(!empty($_POST['agent'])){
			$cond .= " AND dfc.agent_id=".intval($this->sanitize($_POST['agent']));
			$flag=1;
		}

		if(empty($flag)) $cond = "1=2";

		if (boolval($this->sanitize($_POST['_search'] ?? false))) {
			$searchstr = $this->Strip($_POST['filters']);
			$cond .= $this->constructWhere($searchstr);
		}

		$cond = str_replace("decontata  = '0'","dfc.decont_id = 0", $cond);
		$cond = str_replace("decontata  = '1'","dfc.decont_id > 0", $cond);

		$sidx = $_POST['sidx']; // get index row - i.e. user click to sort
        $sord = $_POST['sord']; // get the direction
		if (!$sidx) $sidx = 1;
		if (!$sord) $sord = "asc";

		$data = date('d/m/Y');
 		$societate = 'Dragon Star Curier';
		$d = 'Lista Chitante client CTR '.$data;

		$spreadsheet = new Spreadsheet();
		$spreadsheet->setActiveSheetIndex(0);
		$worksheet = $spreadsheet->getActiveSheet();
		$spreadsheet->getProperties()->setCreator($societate)
                ->setLastModifiedBy($societate)
                ->setTitle($d)
                ->setSubject($d)
                ->setDescription($d)
                ->setKeywords($d)
                ->setCategory($d);
		$spreadsheet->getDefaultStyle()->getFont()->setName('Arial');
		$spreadsheet->getDefaultStyle()->getFont()->setSize(10);

		foreach (range('A', 'L') as $letter) {
			$worksheet->getStyle($letter.'1')->getFont()->setSize(10);
			$worksheet->getStyle($letter.'1')->getFont()->setBold(true);
			$worksheet->getColumnDimension($letter)->setAutoSize(true);
		}
		//'Chitanta', 'Valoare', 'Data', 'Descriere', 'Client','CUI', 'Agent', 'Centru'
		$worksheet->setCellValue('A1','Chitanta');
        $worksheet->setCellValue('B1','Valoare');
		$worksheet->setCellValue('C1','Data');
        $worksheet->setCellValue('D1','Descriere');
		$worksheet->setCellValue('E1','Client');
		$worksheet->setCellValue('F1','CUI');
		$worksheet->setCellValue('G1','Agent');
		$worksheet->setCellValue('H1','Centru');
		$worksheet->setCellValue('I1','Validata');
		$worksheet->setCellValue('J1','Decontata');
		

		$query = "SELECT dfc.id, dfc.dataInc, dfc.suma, dfc.descriere, dfc.ch_bon as chitanta, dfc.vCF as validata,
			IF(dfc.decont_id > 0, 1, 0) as decontata,
			ag.nume_ag as agent, cl.nume as client, cl.cod_fiscal as cui, ce.label as centru
			FROM decont_chitante dfc
			LEFT JOIN agenti ag ON ag.cod_ag = dfc.agent_id
			LEFT JOIN clienti cl on dfc.client_id = cl.cod_cl
			LEFT JOIN centre ce on ce.id = ag.cod_centru
			WHERE {$cond}
			ORDER BY " . $sidx . " " . $sord;

		$sql = $this->db->QFetchRowArray($query);
		$rand=2;
        if (!empty($sql)) {
            foreach ($sql as $key => $row) {
				$worksheet->setCellValueExplicit('A'.($rand+$key), $row['chitanta'], \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
				$worksheet->setCellValue('B'.($rand+$key),number_format($row['suma'], 2, ',',''));
				$row['dataInc'] = \PhpOffice\PhpSpreadsheet\Shared\Date::PHPToExcel(new DateTime($row['dataInc']));
				$worksheet->setCellValue('C'.($rand+$key),$row['dataInc']);
				$worksheet->getStyle('C'.($rand+$key))->getNumberFormat()->setFormatCode('dd.mm.YYYY');
				$worksheet->setCellValueExplicit('D'.($rand+$key), $row['descriere'], \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
				$worksheet->setCellValueExplicit('E'.($rand+$key), $row['client'], \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
				$worksheet->setCellValueExplicit('F'.($rand+$key), $row['cui'], \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
				$worksheet->setCellValueExplicit('G'.($rand+$key), $row['agent'], \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
				$worksheet->setCellValue('H'.($rand+$key),$row['centru']);
				$worksheet->setCellValue('I'.($rand+$key),boolval($row['validata']));
				$worksheet->setCellValue('J'.($rand+$key),boolval($row['decontata']));
            }
		}
        $data = date('Y_m_d');
        $filename = $data.'_ChitanteFiscale.xlsx';

		header('Cache-Control: max-age=0');
        header('Content-Type: application/vnd.ms-excel');
        header("Content-Type: application/force-download");
        header("Content-Type: application/octet-stream");
        header("Content-Type: application/download");
        header("Content-Disposition: attachment;filename=$filename");
        header("Content-Transfer-Encoding: binary");
        $writer = new Xlsx($spreadsheet);
		$writer->save("php://output");
		die;
	}

	/*/////////////////////////////////////////////////////////////
				FIN PRINT CHITANTE
	/////////////////////////////////////////////////////////////*/

	function FacturareUpdateGreutate()
	{
		$responce = new StdClass();
		$responce->success = 1;
		$expeditie = 0;
		if(isset($_POST['id']))
			$expeditie = intval($_POST['id']);
		else
		{
			$responce->success = 0;
			$responce->error = 'Eroare : Expeditia '.$expeditie.' nu exista';
			return json_encode($responce);
		}
		//error_log($expeditie);
		if(!empty($_POST['greutate']))
			$greutate = ceil(floatval($_POST['greutate']));
		else
		{
			$responce->success = 0;
			$responce->error = 'Eroare de greutate';
			return json_encode($responce);
		}

		$retCB = $this->checkCodBaraRecantarire($expeditie, $greutate);
		if(true !== $retCB)
		{
			$responce->success = 0;
			$responce->error = "error : Expeditia ".$expeditie." ".$retCB." : reweight failed";
			return json_encode($responce);
		}

		$reweight = $this->reweightExpeditie($expeditie, $greutate);
		if(is_array($reweight) && false === $reweight[0])
		{
			$responce->success = 0;
			$responce->error = "error : Expeditia ".$expeditie." ".$retCB." : reweight failed";
			return json_encode($responce);
		}


		$responce->m_data = [];
		$responce->m_data['val_greutate'] =$reweight[1];
		$responce->m_data['valoare_totala_expeditie'] = $reweight[2];
		$responce->m_data['tva'] = $reweight[3];
		return json_encode($responce);
	}
}