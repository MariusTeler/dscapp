<?php

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;
use PhpOffice\PhpSpreadsheet\Shared\Date;

/**
 * Recantariri
 *
 */
class ModulRecantariri extends BackEnd {

    public $final_result;
    public $action_module;
    public $page_prefix;
    public $table;

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

        $this->vars['title_page'] = 'Recantariri';
        $this->page_prefix = 'recantariri_';

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
        {
            $this->ActionsNivelAcces();   
        } 
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
		if(in_array("recantarire", $this->user_rights) || $this->user_profile == 10){
			if (!empty($arr[1]) && $arr[1] == 'expeditii')
			{
				if (!empty($arr[2]) && $arr[2] == 'cautare')
					$this->final_result = $this->Cautare(); 
				else if (!empty($arr[2]) && $arr[2] == 'json')
					echo $this->JSON_Cautare(); 
				else if (!empty($arr[2]) && $arr[2] == 'export')
					echo $this->Export();
				$flag=1;
			}
			else if (!empty($arr[1]) && $arr[1] == 'xls' && ($this->user_id == parent::MIHALCEA || $this->user_id == parent::MARIAN))
			{
				if (!empty($arr[2]) && $arr[2] == 'step2')
					$this->final_result = $this->XlsRecantaririStep2(); 
				else if (!empty($arr[2]) && $arr[2] == 'step3')
					$this->final_result = $this->XlsRecantaririStep3(); 
				else if (!empty($arr[2]) && $arr[2] == 'check')
					$this->XlsRecantaririCheck();
				else if (!empty($arr[2]) && $arr[2] == 'import')
					$this->XlsRecantaririImport();
				else
					$this->final_result = $this->XlsRecantaririStep1();
				$flag=1;
			}
			else if (!empty($arr[1]) && $arr[1] == 'validare' && $this->user_id == parent::MARIAN)
			{
				if (!empty($arr[2]) && $arr[2] == 'cautare')
					$this->final_result = $this->vCautare(); 
				else if (!empty($arr[2]) && $arr[2] == 'json')
					echo $this->JSON_vCautare();
				else if(!empty($arr[2]) && $arr[2] == 'update_greutate')
					echo $this->vUpdateGreutate();
				else if(!empty($arr[2]) && $arr[2] == 'validare')
					echo $this->vValidareAnulareRecantariri();
				$flag=1;
			}
		}
        if(empty($flag))
            $this->final_result = $this->PageNotFound();
    }
    
 //-------------------------------- functii ----------------------------------------
   
/*/////////////////////////////////////////////////////////////
				 Recantariri Xls
/////////////////////////////////////////////////////////////*/
	function Cautare(){
		$this->vars['title_page'] = 'Cautare expeditii recantarite';
        $vars = [];
        $vars['data_start'] = date('d.m.Y');
        $vars['data_final'] = date('d.m.Y');
        
        return $this->Parse($this->page_prefix . 'cautare.html', $vars);
	}

	function JSON_Cautare() {
		$cond ='1=1';
		$responce = new StdClass();
        if(isset($_REQUEST['data_start']) && isset($_REQUEST['data_final'])){
            $data_start = $this->TransformDate($_REQUEST['data_start']);
            $data_final = $this->TransformDate($_REQUEST['data_final']);        
			$cond .= " AND err.data between '{$data_start} 00:00:01' AND '{$data_final} 23:59:59'";
		}
		if(isset($_REQUEST['platitor_id'])) {
			$platitor_id = intval($_REQUEST['platitor_id']);
			if($platitor_id > 0)
				$cond .= " AND e.platitor_id = {$platitor_id} ";
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
		
		$query = "select count(*) as nr
			from exp_recantarite err
			inner JOIN {$this->tables['exp_prelucrate']} e on err.expeditie = e.expeditie
			left join clienti cl_exp on e.expeditor_id = cl_exp.cod_cl
			left join localitati lc_exp on lc_exp.cod_lc = cl_exp.cod_lc
			left join centre c_exp on c_exp.id = lc_exp.cod_centru
			left join clienti cl_dest on e.destinatar_id = cl_dest.cod_cl
			left join clienti cl_plat on e.platitor_id = cl_plat.cod_cl
			left join centre ce on err.centru_id = ce.id
			left join users u on u.id = err.user_id
			where {$cond}";
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
			cl_plat.nume as platitor, cl_plat.cod_fiscal as cui, e.colete as colete, e.paleti as paleti, u.nume as user, ce.label as rw_centru,
			err.kg as new_kg, err.oldKg as old_kg, c_exp.label as expeditor_centru, err.lungime, err.latime, err.inaltime,
			cl_exp.nume as expeditor, cl_dest.nume as destinatar, err.vKg, err.vMotiv, u.nume as user, DATE(err.vData) as data_validare, err.data as data_recantarire,
			err.folder
			from exp_recantarite err
			inner JOIN {$this->tables['exp_prelucrate']} e on err.expeditie = e.expeditie
			left join clienti cl_exp on e.expeditor_id = cl_exp.cod_cl
			left join localitati lc_exp on lc_exp.cod_lc = cl_exp.cod_lc
			left join centre c_exp on c_exp.id = lc_exp.cod_centru
			left join clienti cl_dest on e.destinatar_id = cl_dest.cod_cl
			left join clienti cl_plat on e.platitor_id = cl_plat.cod_cl
			left join centre ce on err.centru_id = ce.id
			left join users u on u.id = err.user_id
			where {$cond}
			ORDER BY " . $sidx . " " . $sord . " LIMIT " . $start . " , " . $limit;
		//error_log($query);
        $sql = $this->db->QFetchRowArray($query);
        if (!empty($sql)) {
            foreach ($sql as $key => $row) {      
				$responce->rows[$key]['id'] = $row['expeditie'];
                $responce->rows[$key]['cell'] = array($row['expeditie'], $row['expeditor_centru'], strtoupper($row['expeditor']),strtoupper($row['destinatar']),strtoupper($row['platitor']),strtoupper($row['data_expeditie']),$row['colete'],$row['paleti'],$row['old_kg'],$row['new_kg'],$row['lungime'],$row['latime'],$row['inaltime'],$row['data_recantarire'],$row['data_validare'],$row['user'],$row['rw_centru'],$row['vKg'],intval(!empty($row['folder'])),$row['vMotiv']);
            }
        }
		$responce->page = $page; 
        $responce->total = $total_pages; 
        $responce->records = $count;
        
        return json_encode($responce);
	}
	
	function Export(){
		$cond ='1=1';
		$data = date('d/m/Y');
 		$societate = 'Dragon Star Curier';
		$d = 'Lista Expeditii Recantarite '.$data;
	
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
		
		foreach (range('A', 'W') as $letter) {
			$worksheet->getStyle($letter.'1')->getFont()->setSize(10);
			$worksheet->getStyle($letter.'1')->getFont()->setBold(true);
			$worksheet->getColumnDimension($letter)->setAutoSize(true);
		}
		
		$worksheet->setCellValue('A1','Nr. NT');
        $worksheet->setCellValue('B1','Expeditor');
		$worksheet->setCellValue('C1','NT Centru');
        $worksheet->setCellValue('D1','Destinatar');
		$worksheet->setCellValue('E1','Platitor');
		$worksheet->setCellValue('F1','CUI');
		$worksheet->setCellValue('G1','Data NT');
		$worksheet->setCellValue('H1','Plicuri');		
		$worksheet->setCellValue('I1','Colete');
		$worksheet->setCellValue('J1','Paleti');		
		$worksheet->setCellValue('K1','Gr. veche');
		$worksheet->setCellValue('L1','Gr. noua');
		$worksheet->setCellValue('M1','Lungime');
		$worksheet->setCellValue('N1','Latime');
		$worksheet->setCellValue('O1','Inaltime');
		$worksheet->setCellValue('P1','RW data');
		$worksheet->setCellValue('Q1','RW validare');
		$worksheet->setCellValue('R1','RW');
		$worksheet->setCellValue('S1','RW by');
		$worksheet->setCellValue('T1','RW centru');
		$worksheet->setCellValue('U1','RW status');
		$worksheet->setCellValue('V1','RW motiv');
		$worksheet->setCellValue('W1','Poza');

        if(isset($_REQUEST['data_start']) && isset($_REQUEST['data_final'])){
            $data_start = $this->TransformDate($_REQUEST['data_start']);
            $data_final = $this->TransformDate($_REQUEST['data_final']);        
            $cond .= " AND err.data between '{$data_start} 00:00:01' AND '{$data_final} 23:59:59'";
		}
		if(isset($_REQUEST['platitor_id'])) {
			$platitor_id = intval($_REQUEST['platitor_id']);
			if($platitor_id > 0)
				$cond .= " AND e.platitor_id = {$platitor_id} ";
		}
		//start generare conditie
		if(isset($_REQUEST['_search'])){
			$searchOn = $this->Strip($_REQUEST['_search']);
			if ($searchOn == 'true') {
				$searchstr = $this->Strip($_REQUEST['filters']);
				$cond .= $this->constructWhere($searchstr);
			}
		}
		
        $sidx = $_REQUEST['sidx']; // get index row - i.e. user click to sort
        $sord = $_REQUEST['sord']; // get the direction
		if (!$sidx) $sidx = 1;
		if (!$sord) $sord = "desc";
		
		$query = "select e.expeditie as expeditie, e.data_expeditie as data_expeditie, 
			cl_plat.nume as platitor, cl_plat.cod_fiscal as cui, e.plicuri, e.colete as colete, e.paleti as paleti, u.nume as user, ce.label as rw_centru,
			err.kg as new_kg, err.oldKg as old_kg, c_exp.label as expeditor_centru, err.lungime, err.latime, err.inaltime,
			cl_exp.nume as expeditor, cl_dest.nume as destinatar, 
			CASE err.vKg WHEN 0 THEN 'recantarita'  WHEN 1 THEN 'validata' ELSE 'anulata' END as status,
			err.vMotiv, DATE(err.vData) as data_validare, err.data as data_recantarire, err.folder
			from exp_recantarite err
			inner JOIN {$this->tables['exp_prelucrate']} e on err.expeditie = e.expeditie
			left join clienti cl_exp on e.expeditor_id = cl_exp.cod_cl
			left join localitati lc_exp on lc_exp.cod_lc = cl_exp.cod_lc
			left join centre c_exp on c_exp.id = lc_exp.cod_centru
			left join clienti cl_dest on e.destinatar_id = cl_dest.cod_cl
			left join clienti cl_plat on e.platitor_id = cl_plat.cod_cl
			left join centre ce on err.centru_id = ce.id
			left join users u on u.id = err.user_id
			where {$cond}
			ORDER BY " . $sidx . " " . $sord;
		$sql = $this->db->QFetchRowArray($query);
		$rand=2;
        if (!empty($sql)) {
            foreach ($sql as $key => $row) {           
				$worksheet->setCellValue('A'.($rand+$key),$row['expeditie']);
				$worksheet->setCellValueExplicit('B'.($rand+$key), $row['expeditor'], \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);	
				$worksheet->setCellValue('C'.($rand+$key),$row['expeditor_centru']);								
				$worksheet->setCellValueExplicit('D'.($rand+$key), $row['destinatar'], \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
				$worksheet->setCellValueExplicit('E'.($rand+$key), $row['platitor'], \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
				$worksheet->setCellValue('F'.($rand+$key),$row['cui']);
				$row['data_expeditie'] = Date::PHPToExcel(new DateTime($row['data_expeditie']));
				$worksheet->setCellValue('G'.($rand+$key),$row['data_expeditie']);
				$worksheet->getStyle('G'.($rand+$key))->getNumberFormat()->setFormatCode('dd.mm.YYYY');
				$worksheet->setCellValue('H'.($rand+$key),$row['plicuri']);				
				$worksheet->setCellValue('I'.($rand+$key),$row['colete']);
				$worksheet->setCellValue('J'.($rand+$key),$row['paleti']);
				$worksheet->setCellValue('K'.($rand+$key),$row['old_kg']);
				$worksheet->setCellValue('L'.($rand+$key),$row['new_kg']);
				$worksheet->setCellValue('M'.($rand+$key),$row['lungime']);
				$worksheet->setCellValue('N'.($rand+$key),$row['latime']);
				$worksheet->setCellValue('O'.($rand+$key),$row['inaltime']);
				$row['data_recantarire'] = Date::PHPToExcel(new DateTime($row['data_recantarire']));
				$worksheet->setCellValue('P'.($rand+$key),$row['data_recantarire']);
				$worksheet->getStyle('P'.($rand+$key))->getNumberFormat()->setFormatCode('dd.mm.YYYY H:mm');
				if(!empty($row['data_validare']))
					$row['data_validare'] = Date::PHPToExcel(new DateTime($row['data_validare']));
				$worksheet->setCellValue('Q'.($rand+$key),$row['data_validare']);
				$worksheet->getStyle('Q'.($rand+$key))->getNumberFormat()->setFormatCode('dd.mm.YYYY');
				$worksheet->setCellValue('R'.($rand+$key),"");
				$worksheet->setCellValueExplicit('S'.($rand+$key), $row['user'], \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
				$worksheet->setCellValue('T'.($rand+$key),$row['rw_centru']);
				$worksheet->setCellValueExplicit('U'.($rand+$key), $row['status'], \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
				$worksheet->setCellValueExplicit('V'.($rand+$key), $row['vMotiv'], \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
				$worksheet->setCellValue('W'.($rand+$key),intval(!empty($row['folder'])));
            }
		}
        $data = date('Y_m_d');
        $filename = $data.'_Recantarite.xlsx';
				
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

	function vCautare(){
		$this->vars['title_page'] = 'Validare recantariri';
        $vars = [];
        $vars['data_start'] = date('d.m.Y');
        $vars['data_final'] = date('d.m.Y');
        
        return $this->Parse($this->page_prefix . 'vcautare.html', $vars);
	}

	function JSON_vCautare() {
		$cond ='1=1';
		$responce = new StdClass();
		/*
		if(isset($_REQUEST['user_id'])) {
			$user_id = intval($_REQUEST['user_id']);
			if($user_id > 0)
				$cond .= " AND er.user_id = {$user_id} ";
		}
		*/
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
		
		$query = "select count(er.expeditie) as nr
		from exp_recantarite er
		left JOIN {$this->tables['exp_prelucrate']} e on e.expeditie = er.expeditie
		left join clienti cl_plat on e.platitor_id = cl_plat.cod_cl
		left join clienti cl_exp on e.expeditor_id = cl_exp.cod_cl
		left join localitati lc_exp on lc_exp.cod_lc = cl_exp.cod_lc
		left join centre c_exp on c_exp.id = lc_exp.cod_centru
		left join clienti cl_dest on e.destinatar_id = cl_dest.cod_cl
		left join centre ce on er.centru_id = ce.id
		where {$cond} and er.vKg = 0";
		//error_log($query);
        $result = $this->db->QFetchArray($query);
        $count = !empty($result['nr']) ? $result['nr'] : 0;
        
        if( $count >0 ) {$total_pages = ceil($count/$limit); } 
        else { $total_pages = 0; } 
        if ($page > $total_pages) $page=$total_pages; 
        if ($limit<0) $limit = 0;
        $start = $limit*$page - $limit; // do not put $limit*($page - 1) 
        if ($start<0) $start = 0; 
		$query = "select er.id, er.expeditie as expeditie, e.data_expeditie, 
			cl_exp.nume as expeditor, cl_dest.nume as destinatar, cl_plat.nume as platitor, 
			e.colete as colete, e.paleti as paleti, ce.label as rw_centru, c_exp.label as expeditor_centru,
			e.greutate as old_kg, er.kg as new_kg, DATE(er.data) as data_recantarire, e.idfact
			from exp_recantarite er
			left JOIN {$this->tables['exp_prelucrate']} e on e.expeditie = er.expeditie
			left join clienti cl_plat on e.platitor_id = cl_plat.cod_cl
			left join clienti cl_exp on e.expeditor_id = cl_exp.cod_cl
			left join localitati lc_exp on lc_exp.cod_lc = cl_exp.cod_lc
			left join centre c_exp on c_exp.id = lc_exp.cod_centru
			left join clienti cl_dest on e.destinatar_id = cl_dest.cod_cl
			left join centre ce on er.centru_id = ce.id
			where {$cond} and er.vKg = 0
			ORDER BY " . $sidx . " " . $sord . " LIMIT " . $start . " , " . $limit;
		
        $sql = $this->db->QFetchRowArray($query);
        if (!empty($sql)) {
            foreach ($sql as $key => $row) {                
				$responce->rows[$key]['id'] = $row['id'];
                $responce->rows[$key]['cell'] = array($row['expeditie'], $row['expeditor_centru'], strtoupper($row['expeditor']),strtoupper($row['destinatar']),strtoupper($row['platitor']),strtoupper($row['data_expeditie']),$row['colete'],$row['paleti'],$row['old_kg'],$row['new_kg'],$row['data_recantarire'],$row['rw_centru'],$row['idfact']);
            }
        }
		$responce->page = $page; 
        $responce->total = $total_pages; 
        $responce->records = $count;
        
        return json_encode($responce);
	}

	function vUpdateGreutate() {
		$responce = new StdClass();
		$responce->success = 0;
		if(isset($_POST['id']))
			$id = intval($_POST['id']); 
		else { 
			$responce->error = 'Eroare : id '.$_POST['id'].' nu exista'; 
			return json_encode($responce);
		}
		//error_log($expeditie);
		if($id > 0 && !empty($_POST['new_kg']))
			$new_kg = ceil(round(floatval($_POST['new_kg']), 3));
		else { 
			$responce->error = 'Eroare greutate'; 
			return json_encode($responce);
		}    
		$q = "SELECT er.expeditie, er.kg, ep.greutate, ep.anulata, ep.tip_exp, ep.idfact
			FROM exp_recantarite er
			INNER JOIN {$this->tables['exp_prelucrate']} ep on er.expeditie = ep.expeditie
			WHERE er.id={$id} and er.vKg = 0 limit 1";
		$e = $this->db->QFetchArray($q); 
		if(empty($e)){
			$responce->error = 'Eroare : expeditia nu exista'; 
			return json_encode($responce);
		}
		if(!in_array($e['tip_exp'], [0,5,7])) {
			$responce->error = 'Eroare : expeditia nu este initiala, retur colet sau returnata'; 
			return json_encode($responce);
		} 
		if(!empty($e['anulata'])) {
			$responce->error = 'Eroare : expeditia este anulata'; 
			return json_encode($responce);
		} 
		if(!empty($e['idfact'])) {
			$responce->error = 'Eroare : expeditia este facturata'; 
			return json_encode($responce);
		} 
		if(ceil($e['greutate']) > ceil($new_kg)) {
			$responce->error = 'Eroare : greutatea introdusa este mai mica decat greutatea expeditiei !';
			return json_encode($responce);
		} 

		$this->db->QueryUpdate('exp_recantarite', ['kg' => $new_kg, 'oldKg' => $e['greutate']], "id=".$id);

		$responce->success = 1;
		return json_encode($responce);
	}

	function vValidareAnulareRecantariri() {
		if(empty($_POST['tip']) || !isset($_POST['ids'])) return -1;

		$tip = intval($_POST['tip']);
		$ids = json_decode($_POST['ids'],true);  
		
		if(empty($tip) || !is_array($ids) || count($ids) == 0) return -2;

		$ids = array_map('intval', $ids);
		$ids = array_filter($ids, fn($value) => $value > 0);
		if(count($ids) == 0) return -21;
		
		if($tip == 1)
			return $this->vValidareRecantariri($ids);
		else if($tip == 2)
			return $this->vAnulareRecantariri($ids);

		return -3;
	}

	public function vValidareRecantariri($ids = []) {
		$errors = count($ids);
		$sqlIds = join(',', $ids);
		$q = "SELECT er.id as er_id, er.kg as er_kg, er.lungime as er_vol1, er.latime as er_vol2, er.inaltime as er_vol3, er.centru_id, er.user_id, 
			ep.expeditie, ep.greutate, ep.greutate_vol, ep.idfact, ep.tip_exp, ep.referire, ep.mod_plata,
			epr.expeditie as r_expeditie, epr.idfact as r_idfact, epr.tip_exp as r_tip_exp, epr.mod_plata as r_mod_plata,
			epi.expeditie as i_expeditie, epi.idfact as i_idfact, epi.tip_exp as i_tip_exp, epi.mod_plata as i_mod_plata
			FROM exp_recantarite er
			INNER JOIN {$this->tables['exp_prelucrate']} ep on er.expeditie = ep.expeditie
			LEFT JOIN {$this->tables['exp_prelucrate']} epr on ep.expeditie = epr.referire and epr.tip_exp = 5
			LEFT JOIN {$this->tables['exp_prelucrate']} epi on ep.referire = epi.expeditie
			WHERE er.id in ({$sqlIds}) and er.vKg = 0";

		//var_dump($q);
		$sql = $this->db->QFetchRowArray($q); 
		if(empty($sql)){
			return $errors;
		}
		//error_log(print_r($sql, true));
		$errors -= count($sql);
		foreach($sql as $key => $expRow) {
			$greutate = $expRow['er_kg'];
			$greutate_vol = ExpeditieDto::getGreutateVolumetrica($expRow['er_vol1'], $expRow['er_vol2'], $expRow['er_vol3']);
            $greutate = ceil(max($greutate, $greutate_vol));
			if(floor(max($expRow['greutate'], $expRow['greutate_vol'])) == $greutate) {
				//validare only
				$this->db->QueryUpdate('exp_recantarite', ['vKg' => 1], "id = " . $expRow['er_id']);
				continue;
			}

			if(!in_array($expRow['tip_exp'], [0,5,7])) {
				//error_log("error vValidareRecantariri tip exp : {$expRow['tip_exp']} : {$expRow['expeditie']}");
				$errors++;
				continue;
			}

			//initiala sau retur colet nefacturata: : reweight initiala
			if(in_array($expRow['tip_exp'], [0,7]) && empty($expRow['idfact'])){
				$ret = $this->reweightExpeditie($expRow['expeditie'], $greutate, $expRow['er_id'], $expRow['er_vol1'], $expRow['er_vol2'], $expRow['er_vol3']);
				if(true === $ret[0]) {
					try {
						//send FCM
						if($expRow['mod_plata'] == 0)
							$this->sendUpdateToAndroid($expRow['expeditie']);
		
						//insert checkpoint RW
						//$this->insertCheckpointRW($expRow['expeditie'], $expRow['centru_id'], $expRow['user_id']);
					}
					catch(\PDOException $e) {
						error_log("error vValidareRecantariri sendUpdateToAndroid : ".$e);
						$errors++;
					}
				}
				else 
					$errors++;
			}
			else $errors++;
			//daca este initiala si are returnare nefacturata : reweight returnare
			if($expRow['tip_exp'] == 0 && !empty($expRow['r_expeditie']) && empty($expRow['r_idfact'])) {
				$ret = $this->reweightExpeditie($expRow['r_expeditie'], $greutate, 0, $expRow['er_vol1'], $expRow['er_vol2'], $expRow['er_vol3']);
				if(true === $ret[0]) {
					try {
						//send FCM
						if($expRow['r_mod_plata'] == 0)
							$this->sendUpdateToAndroid($expRow['r_expeditie']);
		
						//insert checkpoint RW
						//$this->insertCheckpointRW($expRow['r_expeditie'], $expRow['centru_id'], $expRow['user_id']);
					}
					catch(\PDOException $e) {
						error_log("error vValidareRecantariri returnare : ".$e);
					}
				}
				//else 
					//error_log("error vValidareRecantariri returnare : {$expRow['r_expeditie']} : {$ret[1]}");
			}
			//daca este returnare si are initiala nefacturata : reweight initiala
			if($expRow['tip_exp'] == 5 && !empty($expRow['i_expeditie']) && empty($expRow['i_idfact']) && empty($expRow['i_tip_exp'])) {
				$ret = $this->reweightExpeditie($expRow['i_expeditie'], $greutate, 0, $expRow['er_vol1'], $expRow['er_vol2'], $expRow['er_vol3']);
				if(true === $ret[0]) {
					try {
						//send FCM
						if($expRow['i_mod_plata'] == 0)
							$this->sendUpdateToAndroid($expRow['r_expeditie']);
		
						//insert checkpoint RW
						//$this->insertCheckpointRW($expRow['i_expeditie'], $expRow['centru_id'], $expRow['user_id']);
					}
					catch(\PDOException $e) {
						error_log("error vValidareRecantariri initiala : ".$e);
					}
				}
				//else 
					//error_log("error vValidareRecantariri initiala : {$expRow['r_expeditie']} : {$ret[1]}");
			}
		}
		return $errors;
	}

	public function vAnulareRecantariri($ids = []) {
		$sqlIds = join(',', $ids);
		$qer = "SELECT id FROM exp_recantarite
			WHERE id in ({$sqlIds}) and vKg = 0 limit 1";
		$er = $this->db->QFetchRowArray($qer); 
		if(empty($er)){
			return -4;
		}

		$this->db->QueryUpdate('exp_recantarite', ['vKg' => 2], "id in (".$sqlIds.")");
		return 0;
	}

    function XlsRecantaririStep1(){
		$this->vars['title_page'] = 'Recantariri : import excel file';
		$vars = [];
		$vars['error'] = (isset($this->vars['error']))?'<span style="color:red">'.$this->vars['error'].'</span>':'';
		$this->vars['error'] = '';
        return $this->Parse($this->page_prefix . 'xls.html', $vars);
    }
    
	function XlsRecantaririStep2(){
    	require_once 'uploadXls.php';
    	$this->vars['title_page'] = 'Verificare';
        $this->vars['error'] = '';
        if(isset($_POST['sxls']) && !empty($_FILES['fxls'])){
        	$upload = UploadXls::uploadXlsUp('fxls');
        	if(is_array($upload) && count($upload) == 2) {
        		if($upload[0])
        			return $this->Parse($this->page_prefix . 'xls_step2.html', array('fisier'=>$upload[1], 'results'=>''));
        		else {
        			$this->vars['error'] = $upload[1];
        			return $this->XlsRecantaririStep1();
        		}
        	}
        	else {
        		$this->vars['error'] = 'Unknown error';
        		return $this->XlsRecantaririStep1(); 
        	}
			
		}
		else {
			$this->vars['error'] .= 'Selectati fisierul';
			return $this->XlsRecantaririStep1();
		}
    }
    
    function XlsRecantaririStep3(){
    	$this->vars['title_page'] = 'Recantareste';
        $this->vars['error'] = '';
        if(isset($_POST['sxls']) && !empty($_POST['fxls']) && isset($_POST['merror'])){
			return $this->Parse($this->page_prefix . 'xls_step3.html', array('fisier'=>$_POST['fxls'], 'merror'=>$_POST['merror']));
		}
		else {
			$this->vars['error'] .= 'Selectati fisierul';
			return $this->XlsRecantaririStep1();
		}
    }

	//procesare import
	function XlsRecantaririCheck(){
		require_once 'uploadXls.php';
		set_time_limit(600);
		header('Content-Type: text/event-stream');
		header('Cache-Control: no-cache'); // recommended to prevent caching of event data.
		header("Access-Control-Allow-Origin: *");
	
		$firstLine = array('codbara','greutate');
		$nbCols = 2;
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
			$highestColumn = $worksheet->getHighestColumn();
			$nbhighestColumn = Coordinate::columnIndexFromString($highestColumn);
			UploadXls::send_message($serverTime, ($highestRow - 1) . ' expeditii de importat<br/>', 0);

			if($nbhighestColumn < $nbCols)
				throw new Exception("fisierul are un numar de coloane (".$nbhighestColumn.") inferior a ".$nbCols);
			else if($nbhighestColumn > $nbCols)
				throw new Exception("fisierul are un numar de coloane (".$nbhighestColumn.") superior a ".$nbCols);
			$colNames = $worksheet->rangeToArray('A1:' . $highestColumn . 1, "", false, false);
			if($firstLine != $colNames[0])
				throw new Exception("denumirile coloanelor gresite : coloanele bune sunt urmatoarele : ".implode(',',$firstLine));
						
			$colsRange = array_combine(range('A', $highestColumn),$firstLine);
			$rowIterator = $worksheet->getRowIterator();
			
			$proc = 1;
			if($highestRow <=10) $proc = 10;
			else if($highestRow <=100) $proc = 1;
			else if($highestRow <=1000) $proc = 0.1;
			else if($highestRow <=10000) $proc = 0.01;
		} catch(Exception $e) {
			UploadXls::send_message($serverTime, $e->getMessage(), 0, 2);
			if(file_exists($inputFileName)) @unlink($inputFileName);
    		exit(0);
		}
			
		$terrors = false;	
		$mexpeditii=[];		
		foreach($rowIterator as $row){
			try {
				$cellIterator = $row->getCellIterator();
				$cellIterator->setIterateOnlyExistingCells(false); // Loop all cells, even if it is not set
				$rowIndex = $row->getRowIndex();
				if(1 == $rowIndex) continue;//skip first row
				$error = '';
    							
				foreach ($cellIterator as $cell) {
					$fLineCol = $colsRange[$cell->getColumn()];
					$val = $this->sanitize($cell->getValue());
					switch($fLineCol) {
						case 'codbara':
							$val = intval($val);
							$retCB = $this->checkCodBaraRecantarire($val);
							if(true !== $retCB && !is_numeric($retCB))  
								$error .= "valoare eronata la linia : ".$rowIndex." coloana : ".$fLineCol." ".$retCB."<br/>";
							if(in_array($val, $mexpeditii))
								$error .= "valoare eronata la linia : ".$rowIndex." coloana : ".$fLineCol." : ".$val." exista deja in acest fisier<br/>";
							$mexpeditii[] = $val;
							break;
						case 'greutate':
							if(empty($val) || ceil(floatval($val)) < 1) 
								$error .= "valoare eronata la linia : ".$rowIndex." coloana : ".$fLineCol ." : greutate obligatorie > 0"."<br/>";
							else if(intval($val) >= 10000)
								$error .= "valoare eronata la linia : ".$rowIndex." coloana : ".$fLineCol ." : greutate prea mare, depaseste 10000"."<br/>";
							else if(!preg_match("/^[0-9]+((\.|,)[0-9]{1,3})?$/",$val))
								$error .= "valoare eronata la linia : ".$rowIndex." coloana : ".$fLineCol ." : greutate gresita"."<br/>";
							break;
						default : 
							break;
					}
				}
				UploadXls::send_message($serverTime, $error, round($rowIndex*$proc), 1);
				if(!empty($error)) $terrors = true;
			}
			catch(Exception $e) {
				UploadXls::send_message($serverTime, 'error at line '.$row->getRowIndex().' : '.$e->getMessage(), round($rowIndex*$proc), 1);
				continue;
			}
		}			
		
		if($terrors)
			UploadXls::send_message($serverTime, '', 100, 2);
		else
			UploadXls::send_message($serverTime, '', 100, 3);
        exit(0);
    }
    
   function XlsRecantaririImport(){
   		set_time_limit(600);
		header('Content-Type: text/event-stream');
		header('Cache-Control: no-cache'); // recommended to prevent caching of event data.
		header("Access-Control-Allow-Origin: *");
	
		require_once 'uploadXls.php';
		
		$firstLine = array('codbara','greutate');
		$nbCols = 2;
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
			$highestColumn = $worksheet->getHighestColumn();
			$nbhighestColumn = Coordinate::columnIndexFromString($highestColumn);
			UploadXls::send_message($serverTime, ($highestRow - 1) . ' expeditii de importat<br/>', 0);

			if($nbhighestColumn < $nbCols)
				throw new Exception("fisierul are un numar de coloane (".$nbhighestColumn.") inferior a ".$nbCols);
			else if($nbhighestColumn > $nbCols)
				throw new Exception("fisierul are un numar de coloane (".$nbhighestColumn.") superior a ".$nbCols);
			$colNames = $worksheet->rangeToArray('A1:' . $highestColumn . 1, "", false, false);
			if($firstLine != $colNames[0])
				throw new Exception("denumirile coloanelor gresite : coloanele bune sunt urmatoarele : ".implode(',',$firstLine));
						
			$colsMap = array_combine($firstLine,range('A', $highestColumn));
			$rowIterator = $worksheet->getRowIterator();
			
			$proc = 1;
			if($highestRow <=10) $proc = 10;
			else if($highestRow <=100) $proc = 1;
			else if($highestRow <=1000) $proc = 0.1;
			else if($highestRow <=10000) $proc = 0.01;
		} catch(Exception $e) {
			UploadXls::send_message($serverTime, $e->getMessage() . " : reweight failed", 0, 2);
			if(file_exists($inputFileName)) @unlink($inputFileName);
    		exit(0);
		}
		
		$centru_id = $this->user_centru_id;
		$mexpeditii=[];
		$expeditiiForUpdate = [];
		foreach($rowIterator as $row){
			try {
				$rowIndex = $row->getRowIndex();
    			if(1 == $rowIndex) continue;//skip first row
				$error = '';

				$expeditie = intval($this->sanitize($worksheet->getCell($colsMap['codbara'].$rowIndex)->getValue())); 
				
				$greutate = str_replace(",",".",trim($worksheet->getCell($colsMap['greutate'].$rowIndex)->getValue()));
				if(!preg_match("/^[0-9]+((\.|,)[0-9]{1,3})?$/",$greutate)) 
					{  $error .= "error : Expeditia ".$expeditie." : greutate eronata : ".$greutate." : reweight failed"; UploadXls::send_message($serverTime, $error, round($rowIndex*$proc), 1); continue; }
				$greutate = ceil(floatval($greutate));
				if(empty($greutate)) 
					{  $error .= "error : Expeditia ".$expeditie." : greutate eronata : ".$greutate." : reweight failed"; UploadXls::send_message($serverTime, $error, round($rowIndex*$proc), 1); continue; }
				if($greutate >= 10000)
					{ $error .= "error : Expeditia ".$expeditie." : greutate prea mare, depaseste 10000 kg: ".$greutate." : reweight failed"; UploadXls::send_message($serverTime, $error, round($rowIndex*$proc), 1); continue; }
    			
				$retCB = $this->checkCodBaraRecantarire($expeditie, $greutate);	
				//daca eroare : continue
    			if(true !== $retCB){ 
					if(is_numeric($retCB)){
						if($this->user_id != parent::MIHALCEA)
							$this->insertRecantarite($centru_id, $expeditie, $greutate, 0, 0, 0, 1, $retCB, 1);
					}
					else { 
						$error .= "error : Expeditia ".$expeditie." ".$retCB." : reweight failed";  
						UploadXls::send_message($serverTime, $error, round($rowIndex*$proc), 1); 
					}
					continue; 
				}
				if(in_array($expeditie, $mexpeditii))
					{ $error .= "error : Expeditia ".$expeditie." exista deja in acest fisier : reweight failed";  UploadXls::send_message($serverTime, $error, round($rowIndex*$proc), 1); continue; }
					 	
			    $mexpeditii[] = $expeditie;
				
				//insert exp_recantarite
				$er_id = 0;
				if($this->user_id != parent::MIHALCEA){
					$er_id = $this->insertRecantarite($centru_id, $expeditie, $greutate, 0, 0, 0, 0, 0, 1);
				}
				//se recalculeaza valoarea expeditiei
				$reweight = $this->reweightExpeditie($expeditie, $greutate, $er_id);
				if(is_array($reweight) && false === $reweight[0])
					{ $error .= "error : Expeditia ".$expeditie." ".$reweight[1]." : reweight failed"; UploadXls::send_message($serverTime, $error, round($rowIndex*$proc), 1); continue; }
				//send FCM
				if($this->user_id != parent::MIHALCEA)
					$this->sendUpdateToAndroid($expeditie);
				//insert checkpoint RW in scanari
				if($this->user_id != parent::MIHALCEA){
					$this->insertCheckpointRW($expeditie, $centru_id);
					$expeditiiForUpdate[] = $expeditie;
				}
			}
       		catch(Exception $e) {
				UploadXls::send_message($serverTime, 'error at line '.$row->getRowIndex().' : '.$e->getMessage(), round($rowIndex*$proc), 1);
				continue;
			}
       		UploadXls::send_message($serverTime, 'Expeditia '.$expeditie.' importata cu succes', round($rowIndex*$proc));
		}
		if(count($expeditiiForUpdate) > 0) {
            $this->db->QueryUpdate('exp_prelucrate', 
                [
                    'anulata' => 0, 
                    'deleted_at' => null, 
                    'deleted_by' => 0,
                    'last_ckp' => parent::TIP_SCANARE_RW,
                    'centru_last_ckp' => $centru_id,
                    'data_last_ckp' => date('Y-m-d H:i:s'),
                ], "expeditie IN (".implode(',', $expeditiiForUpdate).")");
        }
		UploadXls::send_message($serverTime, '', 100, 3);
		if(file_exists($inputFileName)) @unlink($inputFileName);
        exit(0);
	}
}