<?php
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;

/**
 * W o r k s p a c e
 *
 */
class ModulCheltuieli extends BackEnd {

    public $final_result;
    public $page_prefix;

	const TIP_CHELTUIELI = array(
        1 => 'CASA FA/CH - Cheltuiala',
        2 => 'CASA FA/CH - Lipsa bani',
        3 => 'CASA FA/CH - Retur lipsa bani',
        6 => 'CASA FA/CH - Compensare',
        10 => 'CASA FA/CH - Achizitie paleti',
        11 => 'CASA FA/CH - Avans salariu',
        12 => 'CASA FA/CH - Chirie Motostivuitor',
        13 => 'CASA FA/CH - Chirie SBK',
        14 => 'CASA FA/CH - Consumabile auto',
        15 => 'CASA FA/CH - ITP',
        16 => 'CASA FA/CH - Mententanta depozit',
        17 => 'CASA FA/CH - Ore suplimentare',
        18 => 'CASA FA/CH - Piese auto',
        19 => 'CASA FA/CH - Service auto',
        20 => 'CASA FA/CH - Servicii spalatorie',
        21 => 'CASA FA/CH - Taxa parcare',
        22 => 'CASA FA/CH - Taxa port',
        23 => 'CASA FA/CH - Tractari auto',
        24 => 'CASA FA/CH - Vulcanizare',
		34 => 'CASA FA/CH - Incarcare casa',
		30 => 'CASA RBS - Salarii',
        31 => 'CASA RBS - Auto',
        32 => 'CASA RBS - Plata SBK',
        33 => 'CASA RBS - Diverse',
		35 => 'CASA RBS - Incarcare casa',
		4 => 'OLD - Incarcare CASA',
		5 => 'OLD - Descarcare CASA',
    );

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

        $this->vars['title_page'] = 'Cheltuieli';
        $this->page_prefix = 'cheltuieli_';

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
		
			if (in_array("decont_cheltuieli", $this->user_rights) || $this->user_profile == 10){
				if(isset($arr[1]) && $arr[1] == 'decont' && $arr[2]=='cautare')
					$this->final_result = $this->DecontCautare();
				else if(isset($arr[1]) && $arr[1] == 'decont' && isset($arr[2]) && $arr[2]=='json_cautare')
					echo $this->JSON_DecontCautare();
				else if(isset($arr[1]) && $arr[1] == 'decont' && isset($arr[2]) && $arr[2]=='detalii')
					echo $this->DecontDetaliiCheltuiala($arr[3]);
				else if(isset($arr[1]) && $arr[1] == 'decont' && isset($arr[2]) && $arr[2]=='export') {
					echo $this->DecontExportCheltuieli();
				}
				$flag=1;
			}
		
	    if(empty($flag))
        	$this->final_result = $this->PageNotFound();
    }
    
 
	/*/////////////////////////////////////////////////////////////
				 DECONT CHELTUIELI
	/////////////////////////////////////////////////////////////*/
		
	function DecontCautare(){
		$this->vars['title_page'] = 'Cheltuieli';
		return $this->Parse($this->page_prefix . 'decont_cautare.html', []);   
	}

	function JSON_DecontCautare() {
		$responce = new StdClass();
		$cond = "dicd.anulata = 0 ";
		$flag=0;

		if(isset($_GET['data_start']) && isset($_GET['data_final'])){
			$data_start = $this->TransformDate(urldecode($this->sanitize($_GET['data_start']))). " 00:00:01";
			$data_final = $this->TransformDate(urldecode($this->sanitize($_GET['data_final']))). " 23:59:59";        
			$cond .= " AND dicd.data >= '".$data_start."' AND dicd.data <= '".$data_final."'";
			$flag=1;    
		}
		
		if(!empty($_GET['cod_ag'])){
			$cond .= " AND dicd.agent_id=".intval($this->sanitize($_GET['cod_ag']));        
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
				
		$page = intval($_REQUEST['page'] ?? 1);
		$limit = intval($_REQUEST['rows'] ?? 100);
		$sidx = trim($this->sanitize($_REQUEST['sidx'] ?? 1));
		$sord = trim($this->sanitize($_REQUEST['sord'] ?? 'asc'));
		
		$query = "SELECT count(dicd.id) as nr, sum(dicd.suma) as suma
			FROM decont_cheltuieli dicd
			LEFT JOIN agenti ag ON ag.cod_ag = dicd.agent_id
			LEFT JOIN centre ce on ce.id = dicd.centru_id
			where {$cond} and dicd.tip in (". implode(',', array_keys(self::TIP_CHELTUIELI)) .")";

		$result = $this->db->QFetchArray($query);
		$count = !empty($result['nr']) ? $result['nr'] : 0;
		$suma_totala = !empty($result['suma']) ? number_format($result['suma'], 2, '.', '') : '0.00';
		
		if( $count >0 ) {$total_pages = ceil($count/$limit); } 
		else { $total_pages = 0; } 
		if ($page > $total_pages) $page=$total_pages; 
		if ($limit<0) $limit = 0; 
		$start = $limit*$page - $limit; // do not put $limit*($page - 1) 
		if ($start<0) $start = 0; 
		
		$query = "SELECT dicd.id, dicd.data, dicd.tip, dicd.suma, dicd.descriere, dicd.ch_bon as chitanta, dicd.factura,
			ag.nume_ag as agent, ce.label as centru
			FROM decont_cheltuieli dicd
			LEFT JOIN agenti ag ON ag.cod_ag = dicd.agent_id
			LEFT JOIN centre ce on ce.id = dicd.centru_id
			where {$cond} and dicd.tip in (". implode(',', array_keys(self::TIP_CHELTUIELI)) .")
			ORDER BY " . $sidx . " " . $sord . " LIMIT " . $start . " , " . $limit;

		$sql = $this->db->QFetchRowArray($query);
		//error_log($query);
		if (!empty($sql)) {
			foreach ($sql as $key => $row) {                
				$responce->rows[$key]['id'] = $row['id'];
				$responce->rows[$key]['cell'] = array($row['tip'], $row['suma'], $row['data'], $row['descriere'], strtoupper($row['agent']), strtoupper($row['centru']), $row['chitanta'], $row['factura']);
			}
		}
		
		$responce->page = $page; 
		$responce->total = $total_pages; 
		$responce->records = $count;
		$responce->userdata['suma'] = $suma_totala;
		return json_encode($responce);
	}

	function DecontDetaliiCheltuiala($id){ 
		if(intval($id) == 0) return 0;
		$query = "SELECT dicd.id, dicd.agent_id, dicd.data, dicd.tip,
			dicd.suma, dicd.descriere, dicd.ch_bon as chitanta, dicd.factura, dicd.tip,
			ag.nume_ag as agent
			FROM decont_cheltuieli dicd
			LEFT JOIN agenti ag ON ag.cod_ag = dicd.agent_id
			where dicd.id = ".intval($id). " and dicd.tip in (". implode(',', array_keys(self::TIP_CHELTUIELI)) .")";
		$sql = $this->db->QFetchArray($query);
		if(empty($sql)) return 0;
		
		$sql['data'] = new DateTime($sql['data']);
		$sql['data'] = $sql['data']->format('d.m.Y H:i');
		
		return '1|||'.$sql['id'].'|||'.$sql['agent_id'].'|||'.$sql['agent'].'|||'.$sql['data'].'|||'.(self::TIP_CHELTUIELI[$sql['tip']] ?? 'n/a').'|||'.$sql['suma'].'|||'.$sql['descriere'].'|||'.$sql['chitanta'].'|||'.$sql['factura'].'|||'.$sql['tip'];                
	
	}

	function DecontExportCheltuieli() {
		$cond = "1=1";
		if(isset($_POST['data_start']) && isset($_POST['data_final'])){
			$data_start = $this->TransformDate($this->sanitize(urldecode($_POST['data_start']))). " 00:00:01";
			$data_final = $this->TransformDate($this->sanitize(urldecode($_POST['data_final']))). " 23:59:59";        
			$cond .= " AND dicd.data >= '".$data_start."' AND dicd.data <= '".$data_final."'";
			$flag=1;    
		}

		if(!empty($_POST['agent'])){
			$cond .= " AND dicd.agent_id=".intval($this->sanitize($_POST['agent']));        
			$flag=1;
		}

		if(empty($flag)) $cond = "1=2";

		if (boolval($this->sanitize($_POST['_search'] ?? false))) {
			$searchstr = $this->Strip($_POST['filters']);
			$cond .= $this->constructWhere($searchstr);
		}

		$sidx = $_POST['sidx']; // get index row - i.e. user click to sort
        $sord = $_POST['sord']; // get the direction
		if (!$sidx) $sidx = 1;
		if (!$sord) $sord = "asc";

		$data = date('d/m/Y');
 		$societate = 'Dragon Star Curier';
		$d = 'Lista Cheltuieli '.$data;
	
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
		//'Tip', 'Valoare', 'Data', 'Descriere', 'Chitanta', 'Factura','Agent', 'Centru'
		$worksheet->setCellValue('A1','Tip');
        $worksheet->setCellValue('B1','Valoare');
		$worksheet->setCellValue('C1','Data');
        $worksheet->setCellValue('D1','Descriere');
		$worksheet->setCellValue('E1','Chitanta');
		$worksheet->setCellValue('F1','Factura');
		$worksheet->setCellValue('G1','Agent');
		$worksheet->setCellValue('H1','Centru');

		$query = "SELECT dicd.id, dicd.data, dicd.suma, dicd.descriere, dicd.ch_bon as chitanta, dicd.factura,
			ag.nume_ag as agent, ce.label as centru, dicd.tip
			FROM decont_cheltuieli dicd
			LEFT JOIN agenti ag ON ag.cod_ag = dicd.agent_id
			LEFT JOIN centre ce on ce.id = dicd.centru_id
			where {$cond} and dicd.tip in (". implode(',', array_keys(self::TIP_CHELTUIELI)) .")
			ORDER BY " . $sidx . " " . $sord;

		$sql = $this->db->QFetchRowArray($query);
		$rand=2;
        if (!empty($sql)) {
            foreach ($sql as $key => $row) {           
				$worksheet->setCellValue('A'.($rand+$key), self::TIP_CHELTUIELI[$row['tip']]) ?? 'n/a';
				$worksheet->setCellValue('B'.($rand+$key),number_format($row['suma'], 2, ',',''));
				$row['data'] = \PhpOffice\PhpSpreadsheet\Shared\Date::PHPToExcel(new DateTime($row['data']));
				$worksheet->setCellValue('C'.($rand+$key),$row['data']);
				$worksheet->getStyle('C'.($rand+$key))->getNumberFormat()->setFormatCode('dd.mm.YYYY');
				$worksheet->setCellValueExplicit('D'.($rand+$key), $row['descriere'], \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
				$worksheet->setCellValue('E'.($rand+$key),$row['chitanta']);
				$worksheet->setCellValue('F'.($rand+$key),$row['factura']);
				$worksheet->setCellValueExplicit('G'.($rand+$key), $row['agent'], \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
				$worksheet->setCellValue('H'.($rand+$key),$row['centru']);
            }
		}
        $data = date('Y_m_d');
        $filename = $data.'_Cheltuieli.xlsx';
				
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
}