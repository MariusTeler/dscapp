<?php

use PhpOffice\PhpSpreadsheet\Reader\Csv;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;

class ModulImporturi extends BackEnd {

	public $final_result;
    public $action_module;
    public $page_prefix;
    public $site_prefix;

	public function __construct($config = 0, $act = 1, $db = 0) {
        parent :: __construct($config, $db);
		$this->procTva = $this->getProcentTVA(date("Y-m-d"));

        $this->vars['title_page'] = 'Importuri';
        $this->vars['error'] = '';
        $this->page_prefix = 'importuri_';

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
        $arr = $this->GenerateArr();
        //nivel acces 10
        if ($this->user_profile == 10){
            if (isset($arr[0]) && $arr[0] == 'importuri-xls' && !empty($arr[1]) && $arr[1] == 'expeditii')
            {
            	if (!empty($arr[2]) && $arr[2] == 'step2')
                	$this->final_result = $this->ImportExpeditiiXlsStep2();
                else if (!empty($arr[2]) && $arr[2] == 'step3')
                	$this->final_result = $this->ImportExpeditiiXlsStep3();
                else if (!empty($arr[2]) && $arr[2] == 'check')
                	$this->ImportExpeditiiXlsCheck();
                else if (!empty($arr[2]) && $arr[2] == 'import')
                	$this->ImportExpeditiiXlsImport();
                else
                	$this->final_result = $this->ImportExpeditiiXlsStep1();
            }
            else if (isset($arr[0]) && $arr[0] == 'importuri-csv' && !empty($arr[1]) && $arr[1] == 'expeditii')
            {
            	if (!empty($arr[2]) && $arr[2] == 'map')
                	$this->final_result = $this->ImportExpeditiiCsvMap();
            	else if (!empty($arr[2]) && $arr[2] == 'step2')
                	$this->final_result = $this->ImportExpeditiiCsvStep2();
                else if (!empty($arr[2]) && $arr[2] == 'step3')
                	$this->final_result = $this->ImportExpeditiiCsvStep3();
                else if (!empty($arr[2]) && $arr[2] == 'check')
                	$this->ImportExpeditiiCsvCheck();
                else if (!empty($arr[2]) && $arr[2] == 'import')
                	$this->ImportExpeditiiXlsImport("csv");
                else
                	$this->final_result = $this->ImportExpeditiiCsvStep1();
            }
			else if (isset($arr[0]) && $arr[0] == 'importuri-xls' && !empty($arr[1]) && $arr[1] == 'destinatari')
			{
            	if (!empty($arr[2]) && $arr[2] == 'step2')
                	$this->final_result = $this->ImportDestinatariXlsStep2();
                else if (!empty($arr[2]) && $arr[2] == 'step3')
                	$this->final_result = $this->ImportDestinatariXlsStep3();
                else if (!empty($arr[2]) && $arr[2] == 'check')
                	$this->ImportDestinatariXlsCheck();
                else if (!empty($arr[2]) && $arr[2] == 'import')
                	$this->ImportDestinatariXlsImport();
                else
                	$this->final_result = $this->ImportDestinatariXlsStep1();
            }
			else if (isset($arr[0]) && $arr[0] == 'importuri-op-xls' && !empty($arr[1]) && $arr[1] == 'expeditii')
            {
            	if (!empty($arr[2]) && $arr[2] == 'step2')
                	$this->final_result = $this->ImportExpeditiiOpXlsStep2();
                else if (!empty($arr[2]) && $arr[2] == 'step3')
                	$this->final_result = $this->ImportExpeditiiOpXlsStep3();
                else if (!empty($arr[2]) && $arr[2] == 'check')
                	$this->ImportExpeditiiOpXlsCheck();
                else if (!empty($arr[2]) && $arr[2] == 'import')
                	$this->ImportExpeditiiOpXlsImport("xls");
                else
                	$this->final_result = $this->ImportExpeditiiOpXlsStep1();
            }
		}
		else
            $this->final_result = $this->PageNotFound();
    }

 //-------------------------------- functii ----------------------------------------

	//////// NEW IMPORT /////////

	/////IMPORT EXPEDITII
	function ImportExpeditiiXlsStep1() {
        $this->vars['title_page'] = 'Import Client Expeditii XLS';
        $vars = [];
		$vars['error'] = (isset($this->vars['error']))?'<span style="color:red">'.$this->vars['error'].'</span>':'';
		$this->vars['error'] = '';
        return $this->Parse($this->page_prefix . 'expeditii_xls.html', $vars);
    }

    function ImportExpeditiiCsvStep1() {
        $this->vars['title_page'] = 'Import Client Expeditii CSV';
        $vars = [];
		$vars['error'] = (isset($this->vars['error']))?'<span style="color:red">'.$this->vars['error'].'</span>':'';
		$this->vars['error'] = '';
        return $this->Parse($this->page_prefix . 'expeditii_csv.html', $vars);
    }


    function ImportExpeditiiXlsStep2(){
    	require_once 'uploadXls.php';
    	$this->vars['title_page'] = 'Verificare';
        $this->vars['error'] = '';

        if(!isset($_POST['cod_cl']) || (isset($_POST['cod_cl']) && !is_numeric($_POST['cod_cl']))) {
        	$this->vars['error'] .= 'Selectati clientul';
			return $this->ImportExpeditiiXlsStep1();
		}
    	$cod_cl = intval($this->sanitize($_POST['cod_cl']));
        if(empty($cod_cl)) {
        	$this->vars['error'] .= 'Selectati clientul';
			return $this->ImportExpeditiiXlsStep1();
		}

        if(isset($_POST['sxls']) && !empty($_FILES['fxls'])){
        	$upload = UploadXls::uploadXlsUp('fxls');
        	if(is_array($upload) && count($upload) == 2) {
        		if($upload[0])
        			return $this->Parse($this->page_prefix . 'expeditii_xls_step2.html', array('fisier'=>$upload[1], 'cod_cl'=>$cod_cl, 'results'=>''));
        		else {
        			$this->vars['error'] = $upload[1];
        			return $this->ImportExpeditiiXlsStep1();
        		}
        	}
        	else {
        		$this->vars['error'] = 'Unknown error : upload : '.count($upload);
        		return $this->ImportExpeditiiXlsStep1();
        	}
		}
		else {
			$this->vars['error'] .= 'Selectati fisierul';
			return $this->ImportExpeditiiXlsStep1();
		}
    }

    function ImportExpeditiiCsvMap(){
    	require_once 'uploadXls.php';
    	$this->vars['title_page'] = 'Mapare coloane';
        $this->vars['error'] = '';

        if(!isset($_POST['cod_cl']) || (isset($_POST['cod_cl']) && !is_numeric($_POST['cod_cl']))) {
        	$this->vars['error'] .= 'Selectati clientul';
			return $this->ImportExpeditiiCsvStep1();
		}
    	$cod_cl = intval($this->sanitize($_POST['cod_cl']));
        if(empty($cod_cl)) {
        	$this->vars['error'] .= 'Selectati clientul';
			return $this->ImportExpeditiiCsvStep1();
		}

		$sep = ';';
		if(isset($_POST['separator']))
			$sep = $this->sanitize($_POST['separator']);

        if(isset($_POST['sxls']) && !empty($_FILES['fxls'])){
        	$upload = UploadXls::uploadXlsUp('fxls', true);
        	if(is_array($upload) && count($upload) == 2) {
        		if($upload[0])
        			return $this->Parse($this->page_prefix . 'expeditii_csv_map.html', array('fisier'=>$upload[1], 'cod_cl'=>$cod_cl, 'separator'=>$sep, 'continut' => $this->ImportCSV($upload[1],$sep)));
        		else {
        			$this->vars['error'] = $upload[1];
        			return $this->ImportExpeditiiCsvStep1();
        		}
        	}
        	else {
        		$this->vars['error'] = 'Unknown error : upload : '.count($upload);
        		return $this->ImportExpeditiiCsvStep1();
        	}
		}
		else {
			$this->vars['error'] .= 'Selectati fisierul';
			return $this->ImportExpeditiiCsvStep1();
		}
    }

    function ImportCSV($file,$separator){
		ini_set('memory_limit', '1228M');
		set_time_limit(600);
		$campuri_import = $this->CampuriImport();
		$row = 0;
		$cels = 0;
		$result = '<table width="100%" id="lista-expeditii-import"  cellpadding="4" cellspacing="0">';
		if (($handle = fopen($file, "r")) !== FALSE) {
			while (($row_data = fgetcsv($handle, 50000, $separator)) !== FALSE) {
				$tst = implode($row_data);
				if(!empty($tst)){ //not empty line
					if($row == 0) $cels = count($row_data);
					$result .= $this->ImportCSVRow($row_data,$row,$campuri_import);
				}
				$row++;
			}
			$result .= '</tbody></table>';
			fclose($handle);
		}
		return $result;
	}


	function ImportCSVRow($row_data,$row,$campuri_import){
		$result = '';
		$top = '';
		$cell=0;

		foreach($row_data as $cell_data){
			if($row == 0){
				$top .= '<th>'.$this->getComboCampuriImport($cell,$campuri_import).'</th>';
				$result .= '<td class="top" id="col_'.$row.'_'.$cell.'">'.$cell_data.'</td>';
			}else{
				$result .= '<td class="col_'.$row.'_'.$cell.'">'.$this->sanitize($cell_data).'</td>';
			}
			$cell++;
		}
		if($row == 0){
			$result = '<thead><tr>'.$top.'</tr></thead><tbody><tr>'.$result.'</tr>';
		}else{
			$result = '<tr>'.$result.'</tr>';
		}

		return $result;
	}

	function getComboCampuriImport($id, $val_import){

		$result = '<option value="nok" selected ></option>';
		foreach($val_import as $key => $val){
            $result .= '<option value="' . $key . '">' . $val . '</option>';
		}
		return '<select class="field_import" name="field_' . $id . '" id="field_' . $id . '" >' . $result . '</select>';
	}

	function CampuriImport(){
		//$firstLine = array('nt','destinatar','destloc','destjud','destadresa','destpers','desttel','platitor','tipexp','bucati','greutate','asigurare','ramburs','tipramburs','returnt','returdoc','detaliidoc','livrare','obs');
		$val_import=[];
		$val_import['nt'] = 'AWB';
		$val_import['destinatar'] = 'DESTINATAR';
		$val_import['destloc'] = 'DESTINATAR LOCALITATE';
		$val_import['destjud'] = 'DESTINATAR JUDET';
		$val_import['destadresa'] = 'DESTINATAR ADRESA';
		$val_import['destpers'] = 'DESTINATAR PERSOANA CONTACT';
		$val_import['desttel'] = 'DESTINATAR TELEFON CONTACT';
		$val_import['platitor'] = 'PLATITOR(EXPEDITOR/DESTINATAR)';
		$val_import['tipexp'] = 'TIP EXPEDITIE(PLIC/COLET/PALET)';
		$val_import['bucati'] = 'BUCATI';
		$val_import['greutate'] = 'GREUTATE';
		$val_import['asigurare'] = 'ASIGURARE';
		$val_import['ramburs'] = 'RAMBURS';
		$val_import['tipramburs'] = 'TIP PLATA(CASH,CONT,BO,CEC)';
		$val_import['returnt'] = 'RETUR NT(TRUE/FALSE)';
		$val_import['returdoc'] = 'RETUR DOCUMENTE(TRUE/FALSE)';
		$val_import['detaliidoc'] = 'DETALII DOCUMENTE';
		$val_import['livrare'] = 'LIVRARE(NORMALA/SAMBATA/SEDIU)';
		$val_import['obs'] = 'OBSERVATII';
		$val_import['referinta_facturare'] = 'REFERINTA FACTURARE';
		return $val_import;
	}

    function ImportExpeditiiCsvStep2(){
    	$this->vars['title_page'] = 'Verificare';
        $this->vars['error'] = '';

        if(!isset($_POST['cod_cl']) || (isset($_POST['cod_cl']) && !is_numeric($_POST['cod_cl']))) {
        	$this->vars['error'] .= 'Selectati clientul';
			return $this->ImportExpeditiiCsvStep1();
		}
    	$cod_cl = intval($this->sanitize($_POST['cod_cl']));

        if(empty($cod_cl)) {
        	$this->vars['error'] .= 'Selectati clientul';
			return $this->ImportExpeditiiCsvStep1();
		}

		$sep = ';';
		if(isset($_POST['separator']))
			$sep = $this->sanitize($_POST['separator']);

		$campuri = [];
		foreach($_POST as $post => $val){
			if(substr($post,0,6) == 'field_' && $val != 'nok'){
				$campuri[intval(substr($post,6))] = $val;
			}
		}

		//eroare ... camp selectat de doua ori
		if(count(array_unique($campuri)) < count($campuri))
		{
			$this->vars['error'] = 'Ati selectat aceeasi coloana de doua ori';
			return $this->ImportExpeditiiCsvStep1();
		}
		//eroare ... campuri obligatorii : destinatar, adresa, localitate, judet, tip_obj ...
		if(!in_array('destinatar', $campuri) || !in_array('destloc', $campuri) || !in_array('destadresa', $campuri) || !in_array('tipexp', $campuri) || !in_array('destjud', $campuri))
		{
			$this->vars['error'] = 'Urmatoarele coloane sunt obligatorii : DESTINATAR, DESTINATAR LOCALITATE, DESTINATAR JUDET, DESTINATAR ADRESA, TIP EXPEDITIE(PLIC/COLET/PALET)';
			return $this->ImportExpeditiiCsvStep1();
		}

        if(isset($_POST['sxls']) && !empty($_POST['fxls']) && isset($_POST['merror'])){
        	//insereaza in $_POST['sxls'] numele coloanelor si salveaza in xls
        	$fisier = $this->sanitize($_POST['fxls']);
        	if(false === $this->csvToXls($fisier, $sep, $campuri)){
        		$this->vars['error'] = 'Error converting file : send email to IT with file attached';
				return $this->ImportExpeditiiCsvStep1();
        	}
			return $this->Parse($this->page_prefix . 'expeditii_csv_step2.html', array('fisier'=>$fisier.'xyz1', 'cod_cl'=>$cod_cl, 'merror'=>$_POST['merror']));
		}
		else {
			$this->vars['error'] .= 'Selectati fisierul';
			return $this->ImportExpeditiiCsvStep1();
		}
    }

    function csvToXls($fisier, $sep, $campuri){
		if(!is_array($campuri)) return false;
		ksort($campuri);

    	try {
			$reader = new Csv();
			$reader->setDelimiter($sep);
			$reader->setSheetIndex(0);
            $spreadsheet = $reader->load($fisier);
    		//  Get worksheet dimensions
			$worksheet = $spreadsheet->getActiveSheet();
			$highestColumn = $worksheet->getHighestDataColumn();
			$nbhighestColumn = Coordinate::columnIndexFromString($highestColumn);
			if($nbhighestColumn == 0) return false;
			for ($col = $nbhighestColumn; $col >= 1; $col--)
        	{
        		if(array_key_exists($col-1,$campuri)){
					$worksheet->setCellValue([$col, 1], $campuri[$col-1]);
				}
            	else{
					//error_log("remove col : ".$col);
					//error_log("cellValue : ".$worksheet->getCell([$col, 2])->getValue());
					$worksheet->removeColumnByIndex($col);
				}
			}
        	$objWriter = IOFactory::createWriter($spreadsheet, 'Xlsx');
        	$objWriter->save($fisier.'xyz1');
		} catch(Exception $e) {
			error_log($e);
    		return false;
		}
		return true;
    }

    function ImportExpeditiiXlsStep3(){
    	$this->vars['title_page'] = 'Import';
        $this->vars['error'] = '';

        if(!isset($_POST['cod_cl']) || (isset($_POST['cod_cl']) && !is_numeric($_POST['cod_cl']))) {
        	$this->vars['error'] .= 'Selectati clientul';
			return $this->ImportExpeditiiXlsStep1();
		}
    	$cod_cl = intval($this->sanitize($_POST['cod_cl']));

        if(empty($cod_cl)) {
        	$this->vars['error'] .= 'Selectati clientul';
			return $this->ImportExpeditiiXlsStep1();
		}

        if(isset($_POST['sxls']) && !empty($_POST['fxls']) && isset($_POST['merror'])){
			return $this->Parse($this->page_prefix . 'expeditii_xls_step3.html', array('fisier'=>$_POST['fxls'], 'cod_cl'=>$cod_cl, 'merror'=>$_POST['merror']));
		}
		else {
			$this->vars['error'] .= 'Selectati fisierul';
			return $this->ImportExpeditiiXlsStep1();
		}
    }

    function ImportExpeditiiCsvStep3(){
    	$this->vars['title_page'] = 'Import';
        $this->vars['error'] = '';

        if(!isset($_POST['cod_cl']) || (isset($_POST['cod_cl']) && !is_numeric($_POST['cod_cl']))) {
        	$this->vars['error'] .= 'Selectati clientul';
			return $this->ImportExpeditiiCsvStep1();
		}
    	$cod_cl = intval($this->sanitize($_POST['cod_cl']));

        if(empty($cod_cl)) {
        	$this->vars['error'] .= 'Selectati clientul';
			return $this->ImportExpeditiiCsvStep1();
		}

        if(isset($_POST['sxls']) && !empty($_POST['fxls']) && isset($_POST['merror'])){
			return $this->Parse($this->page_prefix . 'expeditii_csv_step3.html', array('fisier'=>$_POST['fxls'], 'cod_cl'=>$cod_cl, 'merror'=>$_POST['merror']));
		}
		else {
			$this->vars['error'] .= 'Selectati fisierul';
			return $this->ImportExpeditiiCsvStep1();
		}
    }

	//check import xls
	function ImportExpeditiiXlsCheck(){
		set_time_limit(600);
		header('Content-Type: text/event-stream');
		header('Cache-Control: no-cache'); // recommended to prevent caching of event data.
		header("Access-Control-Allow-Origin: *");

		require_once 'uploadXls.php';

		$serverTime = time();

		$cod_cl = 0;
 		if(empty($_GET['cod_cl'])) { UploadXls::send_message($serverTime, 'Error client : empty client', 0, 2); exit(0); }
		else {
			$cod_cl = intval($this->sanitize($_GET['cod_cl']));
			if(empty($cod_cl)) { UploadXls::send_message($serverTime, 'Error client : empty client', 0, 2); exit(0); }
		}

		//check client activ=1
		if(true !== ($ret = $this->checkClient($cod_cl)))
           { UploadXls::send_message($serverTime, 'Error client : '.$ret, 0, 2); exit(0); }

 		$firstLine = array('nt','destinatar','destloc','destjud','destadresa','destpers','desttel','platitor','tipexp','bucati','greutate','asigurare','ramburs','tipramburs','returnt','returdoc','detaliidoc','livrare','obs','deschiderecolet');

 		if($cod_cl == 171350 || $cod_cl == 883828 || $cod_cl == 435076) {//maravet || biovet
 			$firstLine = array('codbara','plic','colet','palet','greutate','clientdest','adresadest','orasdest','judetdest','centru','perscontactdest','telefondest','observatii',
			 'serieclient','rambursnumerar','ramburscontcolector','rambursalttip','platitorexpeditie','livraresambata','email','frig','continut','valoaredeclarata','extrainfo','largeinfo',
			 'codpostaldest','intervallivrare','deschiderecolet','taradest','emaildest','disclaimer','refexp1','refdest1','refdest2','referintafacturare');
		}

		$nbCols = count($firstLine);

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
			if($nbhighestColumn == 0)
				throw new Exception("fisierul este gol");
			if($nbhighestColumn != $nbCols)
				throw new Exception("fisierul are un numar de coloane (".$nbhighestColumn.") diferit de ".$nbCols);

			$colNames = $worksheet->rangeToArray('A1:' . $highestColumn . 1, NULL, TRUE, FALSE);
			if($firstLine != $colNames[0])
				throw new Exception("denumirile coloanelor gresite : coloanele bune sunt urmatoarele : ".implode(',',$firstLine));

			$colsRange = array_combine($this->excelRange($nbCols),$firstLine);
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
    			if(1 == $row->getRowIndex()) continue;//skip first row
    			$rowIndex = $row->getRowIndex();
    			$error = '';

            	foreach ($cellIterator as $cell) {
            		$localitate = '';
    				$tipexp = '';
					$fLineCol = strtolower($colsRange[$cell->getColumn()]);
           			$val = $this->sanitize($cell->getValue());
           			switch($fLineCol) {
           				case 'nt':
           				case 'codbara':
           				case 'awb':
           					if(intval($val) == 0) {
           						if($fLineCol == 'nt'){
           							$m_msg = 'Expeditia linie '.$rowIndex.' nu are numar nt : o sa fie generat de sistem'."<br/>";
           							UploadXls::send_message($serverTime, $m_msg, round($rowIndex*$proc), 0);
           						}
           						else {
           							$error .= 'Expeditia linie '.$rowIndex.' nu are numar nt'."<br/>";
           						}
           						break;
           					}
           					if(true !== ($ret = $this->checkCodBara($val, $cod_cl)))
           						$error .= "valoare eronata la linia : ".$rowIndex." coloana : ".$fLineCol." ".$ret."<br/>";
           					if(in_array($val, $mexpeditii))
           						$error .= "valoare eronata la linia : ".$rowIndex." coloana : ".$fLineCol." : ".$val." exista deja in acest fisier<br/>";
           					$mexpeditii[] = $val;
           					break;
           					//'NumeClient','PersoanaContact','Adresa','Oras','Judet','Telefon','Mobil','Bucati','GreutateKg','GreutateGr','Lungime','Latime','Inaltime','Volum','Descriere','Serviciu','crx_type','Livrare','Ambalare','SigiliuAmbalare','RAMBURS_CASH','ASIGURARE','ModPlataRamburs','Platitor','ContPlatitor','NumePlatitor','ModPlata','DocumentPlata','FacturaFiscala','Facturare','ConfirmarePrimire',
           				case 'destinatar':
           				case 'clientdest':
           				case 'numeclient':
           					if(empty($val))
           						$error .= "valoare eronata la linia : ".$rowIndex." coloana : ".$fLineCol ." : lipseste destinatarul"."<br/>";
           					break;
           				case 'destadresa':
           				case 'adresadest':
           				case 'adresa':
           					if(empty($val))
           						$error .= "valoare eronata la linia : ".$rowIndex." coloana : ".$fLineCol ." : lipseste adresa de destinatie"."<br/>";
           					break;
           				case 'destloc':
           				case 'orasdest':
           				case 'oras':
           					if(empty($val))
           						$error .= "valoare eronata la linia : ".$rowIndex." coloana : ".$fLineCol ." : lipseste orasul de destinatie"."<br/>";
           					else
           						$localitate = $val;
           					break;
           				case 'destjud':
           				case 'judetdest':
           				case 'judet':
           					if(empty($val))
           						$error .= "valoare eronata la linia : ".$rowIndex." coloana : ".$fLineCol ." : lipseste judetul de destinatie"."<br/>";
           					else if(!empty($localitate) && true !== ($ret = $this->checkLocalitate($localitate, $val)))
           						$error .= "valoare eronata la linia : ".$rowIndex." coloana : ".$fLineCol ." : ".$ret;
           					break;
           				case 'destpers':
           				case 'perscontactdest':
           				case 'persoanacontact':
           					break;
           				case 'desttel':
           				case 'teldest':
           				case 'telefon':
           					break;
           				case 'platitor':
           				case 'platitorexpeditie':
           				case 'platitor':
           					if(!empty($val) && !(strtoupper($val)=='EXPEDITOR' || strtoupper($val)=='DESTINATAR'))
           						$error .= "valoare eronata la linia : ".$rowIndex." coloana : ".$fLineCol ." : valori posibile : expeditor | destinatar"."<br/>";
           					break;
           				case 'tipexp':
           				case 'ambalare':
           				//case 'tipexp':
           					if(!empty($val) && !(strtoupper($val)=='PLIC' || strtoupper($val)=='COLET' || strtoupper($val)=='PALET'))
           						$error .= "valoare eronata la linia : ".$rowIndex." coloana : ".$fLineCol ." : valori posibile : plic | colet | palet"."<br/>";
           					else $tipexp = strtoupper($val);
           					break;
           				case 'bucati':
           				case 'colet':
           					if(!empty($tipexp)  && ( $tipexp == 'PLIC' || $tipexp == 'COLET'))
           						break;
           					if(!empty($val) && $val < 1)
           						$error .= "valoare eronata la linia : ".$rowIndex." coloana : ".$fLineCol ." : valori posibile : >=1 pentru tipexp COLET"."<br/>";
           					break;
           				case 'greutate':
           				case 'greutatekg':
           					if(!empty($tipexp)  && $tipexp == 'PLIC')
           						break;
							if(empty($val) || intval($val) <= 0)
								$error .= "valoare eronata la linia : ".$rowIndex." coloana : ".$fLineCol ." : valoare obligatorie pentru COLET | PALET"."<br/>";
							else if($tipexp == 'COLET' && intval($val) < parent::MIN_KG_COLET)
								$error .= "valoare eronata la linia : ".$rowIndex." coloana : ".$fLineCol ." : valoare minima " . parent::MIN_KG_COLET . " pentru COLET"."<br/>";
							else if($tipexp == 'PALET' && intval($val) < parent::MIN_KG_PALET)
								$error .= "valoare eronata la linia : ".$rowIndex." coloana : ".$fLineCol ." : valoare minima " . parent::MIN_KG_PALET . " pentru PALET"."<br/>";
           					break;
           				case 'returnt':
						case 'returdoc':
						case 'deschiderecolet':
           				case 'confirmareprimire':
           					if(!empty($val) && !(strtoupper($val)=='DA' || strtoupper($val)=='NU' || strtoupper($val)=='TRUE' || strtoupper($val)=='FALSE'))
           						$error .= "valoare eronata la linia : ".$rowIndex." coloana : ".$fLineCol ." : valori posibile : da | nu | true | false"."<br/>";
           					break;
           				case 'asigurare':
							$val = floatval(str_replace(',', '.', $val));
           					if(!empty($val) && !is_numeric($val))
           						$error .= "valoare eronata la linia : ".$rowIndex." coloana : ".$fLineCol ." : nu este numeric"."<br/>";
							if(!empty($val) && intval($val) > parent::MAX_ASIGURARE)
								$error .= "valoare eronata la linia : ".$rowIndex." coloana : ".$fLineCol ." : valoare maxima ".parent::MAX_ASIGURARE."<br/>";
							break;
						case 'ramburs':
           					$val = floatval(str_replace(',', '.', $val));
           					if(!empty($val) && !is_numeric($val))
           						$error .= "valoare eronata la linia : ".$rowIndex." coloana : ".$fLineCol ." : nu este numeric"."<br/>";
           					break;
           				case 'tipramburs':
           					if(!empty($val) && !(strtoupper($val)=='CASH' || strtoupper($val)=='CONT' || strtoupper($val)=='BO' || strtoupper($val)=='CEC'))
           						$error .= "valoare eronata la linia : ".$rowIndex." coloana : ".$fLineCol ." : valori posibile : cash | cont | bo | cec"."<br/>";
           					break;
           				case 'livrare':
           					if(!empty($val) && !$this->checkLivrare($val))
           						$error .= "valoare eronata la linia : ".$rowIndex." coloana : ".$fLineCol ." : valori posibile : normala | sambata | sediu"."<br/>";
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

   //check import csv
	function ImportExpeditiiCsvCheck(){
		set_time_limit(600);
		header('Content-Type: text/event-stream');
		header('Cache-Control: no-cache'); // recommended to prevent caching of event data.
		header("Access-Control-Allow-Origin: *");

		require_once 'uploadXls.php';

		$serverTime = time();

		$firstLine = array('nt','destinatar','destloc','destjud','destadresa','destpers','desttel','platitor','tipexp','bucati','greutate','asigurare','ramburs','tipramburs','returnt','returdoc','detaliidoc','livrare','obs');

		$cod_cl = 0;
 		if(empty($_GET['cod_cl'])) { UploadXls::send_message($serverTime, 'Error client : empty client', 0, 2); exit(0); }
		else {
			$cod_cl = intval($this->sanitize($_GET['cod_cl']));
			if(empty($cod_cl)) { UploadXls::send_message($serverTime, 'Error client : empty client', 0, 2); exit(0); }
		}

		//check client activ=1
		if(true !== ($ret = $this->checkClient($cod_cl)))
           { UploadXls::send_message($serverTime, 'Error client : '.$ret, 0, 2); exit(0); }

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
			if($nbhighestColumn == 0)
				throw new Exception("fisierul nu are coloane");

			$colNames = $worksheet->rangeToArray('A1:' . $highestColumn . 1, NULL, TRUE, FALSE);
			$colNames = $colNames[0];
			if(!in_array('destinatar', $colNames) || !in_array('destloc', $colNames) || !in_array('destadresa', $colNames) || !in_array('tipexp', $colNames) || !in_array('destjud', $colNames))
					throw new Exception("Urmatoarele coloane sunt obligatorii : DESTINATAR, DESTINATAR LOCALITATE, DESTINATAR JUDET, DESTINATAR ADRESA, TIP EXPEDITIE(PLIC/COLET/PALET)");

			$colsRange = array_combine($this->excelRange($nbhighestColumn),$colNames);
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
    			if(1 == $row->getRowIndex()) continue;//skip first row
    			$rowIndex = $row->getRowIndex();
    			$error = '';

            	foreach ($cellIterator as $cell) {
            		$localitate = '';
    				$tipexp = '';
					$fLineCol = strtolower($colsRange[$cell->getColumn()]);
           			$val = $this->sanitize($cell->getValue());
           			switch($fLineCol) {
           				case 'nt':
           					if(intval($val) == 0) {
           						$m_msg = 'Expeditia linie '.$rowIndex.' nu are numar nt : o sa fie generat de sistem'."<br/>";
           						UploadXls::send_message($serverTime, $m_msg, round($rowIndex*$proc), 0);
           						break;
           					}
           					if(true !== ($ret = $this->checkCodBara($val, $cod_cl)))
           						$error .= "valoare eronata la linia : ".$rowIndex." coloana : ".$fLineCol." ".$ret."<br/>";
           					if(in_array($val, $mexpeditii))
           						$error .= "valoare eronata la linia : ".$rowIndex." coloana : ".$fLineCol." : ".$val." exista deja in acest fisier<br/>";
           					$mexpeditii[] = $val;
           					break;
           					//'NumeClient','PersoanaContact','Adresa','Oras','Judet','Telefon','Mobil','Bucati','GreutateKg','GreutateGr','Lungime','Latime','Inaltime','Volum','Descriere','Serviciu','crx_type','Livrare','Ambalare','SigiliuAmbalare','RAMBURS_CASH','ASIGURARE','ModPlataRamburs','Platitor','ContPlatitor','NumePlatitor','ModPlata','DocumentPlata','FacturaFiscala','Facturare','ConfirmarePrimire',
           				case 'destinatar':
           					if(empty($val))
           						$error .= "valoare eronata la linia : ".$rowIndex." coloana : ".$fLineCol ." : lipseste destinatarul"."<br/>";
           					break;
           				case 'destadresa':
           					if(empty($val) || strlen($val) < 4)
           						$error .= "valoare eronata la linia : ".$rowIndex." coloana : ".$fLineCol ." : lipseste adresa de destinatie"."<br/>";
           					break;
           				case 'destloc':
           					if(empty($val))
           						$error .= "valoare eronata la linia : ".$rowIndex." coloana : ".$fLineCol ." : lipseste orasul de destinatie"."<br/>";
           					else
           						$localitate = $val;
           					break;
           				case 'destjud':
           					if(empty($val))
           						$error .= "valoare eronata la linia : ".$rowIndex." coloana : ".$fLineCol ." : lipseste judetul de destinatie"."<br/>";
           					else if(!empty($localitate) && true !== ($ret = $this->checkLocalitate($localitate, $val)))
           						$error .= "valoare eronata la linia : ".$rowIndex." coloana : ".$fLineCol ." : ".$ret;
           					break;
           				case 'destpers':
           					break;
           				case 'desttel':
           					break;
           				case 'platitor':
           					if(!empty($val) && !(strtoupper($val)=='EXPEDITOR' || strtoupper($val)=='DESTINATAR'))
           						$error .= "valoare eronata la linia : ".$rowIndex." coloana : ".$fLineCol ." : valori posibile : expeditor | destinatar"."<br/>";
           					break;
           				case 'tipexp':
           					if(!empty($val) && !(strtoupper($val)=='PLIC' || strtoupper($val)=='COLET' || strtoupper($val)=='PALET'))
           						$error .= "valoare eronata la linia : ".$rowIndex." coloana : ".$fLineCol ." : valori posibile : plic | colet | palet"."<br/>";
           					else $tipexp = strtoupper($val);
           					break;
           				case 'bucati':
           					if(!empty($tipexp)  && ( $tipexp == 'PLIC' || $tipexp == 'COLET'))
           						break;
           					if(!empty($val) && $val < 1)
           						$error .= "valoare eronata la linia : ".$rowIndex." coloana : ".$fLineCol ." : valori posibile : >=1 pentru tipexp COLET"."<br/>";
           					break;
           				case 'greutate':
           					if(!empty($tipexp) && $tipexp == 'PLIC')
           						break;
							if(empty($val) || intval($val) <= 0)
								$error .= "valoare eronata la linia : ".$rowIndex." coloana : ".$fLineCol ." : valoare obligatorie pentru COLET | PALET"."<br/>";
							else if($tipexp == 'COLET' && intval($val) < parent::MIN_KG_COLET)
								$error .= "valoare eronata la linia : ".$rowIndex." coloana : ".$fLineCol ." : valoare minima " . parent::MIN_KG_COLET . " pentru COLET"."<br/>";
							else if($tipexp == 'PALET' && intval($val) < parent::MIN_KG_PALET)
								$error .= "valoare eronata la linia : ".$rowIndex." coloana : ".$fLineCol ." : valoare minima " . parent::MIN_KG_PALET . " pentru PALET"."<br/>";
           					break;
           				case 'returnt':
           				case 'returdoc':
						case 'deschiderecolet':
           					if(!empty($val) && !(strtoupper($val)=='DA' || strtoupper($val)=='NU' || strtoupper($val)=='TRUE' || strtoupper($val)=='FALSE'))
           						$error .= "valoare eronata la linia : ".$rowIndex." coloana : ".$fLineCol ." : valori posibile : da | nu | true | false"."<br/>";
           					break;
           				case 'asigurare':
							$val = floatval(str_replace(',', '.', $val));
           					if(!empty($val) && !is_numeric($val))
           						$error .= "valoare eronata la linia : ".$rowIndex." coloana : ".$fLineCol ." : nu este numeric"."<br/>";
							if(!empty($val) && intval($val) > parent::MAX_ASIGURARE)
								$error .= "valoare eronata la linia : ".$rowIndex." coloana : ".$fLineCol ." : valoare maxima ".parent::MAX_ASIGURARE."<br/>";
							break;
           				case 'ramburs':
           					$val = floatval(str_replace(',', '.', $val));
           					if(!empty($val) && !is_numeric($val))
           						$error .= "valoare eronata la linia : ".$rowIndex." coloana : ".$fLineCol ." : nu este numeric"."<br/>";
           					break;
           				case 'tipramburs':
           					if(!empty($val) && !(strtoupper($val)=='CASH' || strtoupper($val)=='CONT' || strtoupper($val)=='BO' || strtoupper($val)=='CEC'))
           						$error .= "valoare eronata la linia : ".$rowIndex." coloana : ".$fLineCol ." : valori posibile : cash | cont | bo | cec"."<br/>";
           					break;
           				case 'livrare':
           					if(!empty($val) && !$this->checkLivrare($val))
           						$error .= "valoare eronata la linia : ".$rowIndex." coloana : ".$fLineCol ." : valori posibile : normala | sambata | sediu"."<br/>";
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

   function ImportExpeditiiXlsImport($tip = "xls"){
   		set_time_limit(600);
		header('Content-Type: text/event-stream');
		header('Cache-Control: no-cache'); // recommended to prevent caching of event data.
		header("Access-Control-Allow-Origin: *");

		require_once 'uploadXls.php';

		$serverTime = time();

		$cod_cl = 0;
		if(empty($_GET['cod_cl'])) { UploadXls::send_message($serverTime, 'Error client : empty client', 0, 2); exit(0); }
		else {
			$cod_cl = intval($this->sanitize($_GET['cod_cl']));
			if(empty($cod_cl)) { UploadXls::send_message($serverTime, 'Error client : empty client', 0, 2); exit(0); }
		}

		//check client activ=1
		if(true !== ($ret = $this->checkClient($cod_cl)))
           { UploadXls::send_message($serverTime, 'Error client : '.$ret, 0, 2); exit(0); }

		$firstLine = array('nt','destinatar','destloc','destjud','destadresa','destpers','desttel','platitor','tipexp','bucati','greutate','asigurare','ramburs','tipramburs','returnt','returdoc','detaliidoc','livrare','obs','deschiderecolet');

		if($tip == "xls" && $cod_cl == 171350 || $cod_cl == 883828 || $cod_cl == 435076) {//maravet || biovet
				$firstLine = array('codbara','plic','colet','palet','greutate','clientdest','adresadest','orasdest','judetdest','centru','perscontactdest','telefondest','observatii',
				'serieclient','rambursnumerar','ramburscontcolector','rambursalttip','platitorexpeditie','livraresambata','email','frig','continut','valoaredeclarata','extrainfo','largeinfo',
				'codpostaldest','intervallivrare','deschiderecolet','taradest','emaildest','disclaimer','refexp1','refdest1','refdest2','referintafacturare');
 		}
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

			if($tip == "csv")
			{
				$firstLine = $worksheet->rangeToArray('A1:' . $highestColumn . 1, "", false, false);
				$firstLine = $firstLine[0];
			}
			$colsMap = array_combine($firstLine, $this->excelRange($nbhighestColumn));
			if($colsMap === false)
				throw new Exception("numar de coloane gresit : array_combine");

			$rowIterator = $worksheet->getRowIterator();

			$proc = 1;
			if($highestRow <=10) $proc = 10;
			else if($highestRow <=100) $proc = 1;
			else if($highestRow <=1000) $proc = 0.1;
			else if($highestRow <=10000) $proc = 0.01;
		}
		catch(Exception $e) {
    		UploadXls::send_message($serverTime, 'Error PHPExcel : '.$e->getMessage(), 0, 2);
    		exit(0);
		}

		//expeditor
		$query = "SELECT lc.dist_km as expeditor_localitate_km,
			cl.cod_cl as expeditor_id, cl.master as expeditor_master_id, cl.cod_lc as expeditor_localitate_id,
			cl.nume as expeditor, cl.adresa as expeditor_adresa, cl.tarif as expeditor_contract, cl.mod_plata as expeditor_mod_plata,
			cl.cc as expeditor_cc, cl.activ as expeditor_activ,
			cl.tarif_individual as expeditor_tarif_individual, cl.icc as expeditor_icc,
			clm.nume as expeditor_master, clm.tarif as expeditor_master_contract, clm.mod_plata as expeditor_master_mod_plata,
			clm.cc as expeditor_master_cc, clm.activ as expeditor_master_activ,
			clt.taxa_destinatie as expeditor_taxa_destinatie, clt.ret_amb as expeditor_ret_amb, clt.kg_ret_amb expeditor_kg_ret_amb,
			clt.tarif_sms as expeditor_tarif_sms, cmt.taxa_destinatie as expeditor_master_taxa_destinatie, cmt.ret_amb as expeditor_master_ret_amb,
			cmt.kg_ret_amb as expeditor_master_kg_ret_amb, cmt.tarif_sms as expeditor_master_tarif_sms,
			cltp.expeditor_tarif_palet, cltpm.expeditor_master_tarif_palet, u.id as user_id, u.nume as expeditor_contact, u.telefon as expeditor_telefon
			FROM clienti cl
			LEFT JOIN localitati lc on cl.cod_lc = lc.cod_lc
			LEFT JOIN clienti clm on clm.cod_cl = cl.master
			LEFT JOIN tarife clt on clt.id_cl = cl.cod_cl
			LEFT JOIN tarife cmt on cmt.id_cl = clm.cod_cl
			LEFT JOIN ( select id, expeditor_id, nume, telefon from users where expeditor_id = :user_expeditor_id order by id desc limit 1) as u on cl.cod_cl = u.expeditor_id
			left join (
				SELECT tcl.id_cl, COUNT(tcl.id) as expeditor_tarif_palet
					from tarife tcl
					LEFT JOIN tarife_det tdcl ON tdcl.id_tarife = tcl.id
					LEFT JOIN tarife_g tgcl ON tgcl.id_tarife_det = tdcl.id
					WHERE tgcl.tip = 1  and tcl.id_cl = :user_expeditor_id
				) cltp on cltp.id_cl = cl.cod_cl
			left join (
				SELECT tclm.id_cl, COUNT(tclm.id) as expeditor_master_tarif_palet
					from tarife tclm
					LEFT JOIN tarife_det tdclm ON tdclm.id_tarife = tclm.id
					LEFT JOIN tarife_g tgclm ON tgclm.id_tarife_det = tdclm.id
					WHERE tgclm.tip = 1  and tclm.id_cl = :user_expeditor_id
				) cltpm on cltpm.id_cl = cl.cod_cl
			WHERE cl.cod_cl = :user_expeditor_id and cl.activ = 1";

        $sql_client = $this->db->QFetchArray($query, ['user_expeditor_id' => $cod_cl]);
		if(empty($sql_client)) { UploadXls::send_message($serverTime, 'Expeditor : '.$cod_cl.' not found', 0, 2); exit(0);}

		$this->expeditor_id = $cod_cl;
		$this->expeditor_contact = $sql_client['expeditor_contact'] ?? "";
		$this->expeditor_telefon = $sql_client['expeditor_telefon'] ?? "";
		$this->tarif_individual = $sql_client['expeditor_tarif_individual'];
		$this->is_pc = $this->isPunctDeLucru($sql_client['expeditor_id'], $sql_client['expeditor_master_id']);
		$this->master_id = $sql_client['expeditor_master_id'];
		$this->expeditor_localitate_id = $sql_client['expeditor_localitate_id'];
		$this->expeditor_localitate_km = $sql_client['expeditor_localitate_km'];
		$this->cc = $this->is_pc ? (($sql_client['expeditor_icc'] ?? 0) == 1 ? $sql_client['expeditor_cc'] ?? 0 : $sql_client['expeditor_master_cc'] ?? 0) : $sql_client['expeditor_cc'] ?? 0;
		$this->mod_plata = $this->is_pc ? $sql_client['expeditor_master_mod_plata'] ?? 0 : $sql_client['expeditor_mod_plata'] ?? 0;
		$this->contract = $this->is_pc ? $sql_client['expeditor_master_contract'] ?? 0 : $sql_client['expeditor_contract'] ?? 0;
		$this->taxa_destinatie = $this->is_pc ? $sql_client["master_taxa_destinatie"] ?? 0 : $sql_client["expeditor_taxa_destinatie"] ?? 0;
		$this->tarif_sms = $this->is_pc ? $sql_client["master_tarif_sms"] ?? 0 : $sql_client["expeditor_tarif_sms"] ?? 0;
		$this->tarif_palet = $this->is_pc ? $sql_client["master_tarif_palet"] ?? 0 : $sql_client["expeditor_tarif_palet"] ?? 0;

		foreach($rowIterator as $row){
			try {
    			if(1 == $row->getRowIndex ()) continue;//skip first row
    			$rowIndex = $row->getRowIndex();
    			$error = '';

    			$mcodbara = 0;
    			if(in_array($this->mapCellHeader('nt', $cod_cl, $tip),$firstLine))
    				$mcodbara = intval($this->sanitize($worksheet->getCell($colsMap[$this->mapCellHeader('nt', $cod_cl, $tip)].$rowIndex)->getValue()));
    			if(empty($mcodbara))
    				$expeditie = $this->GenerareNrExpeditie();
    			else {
    				$expeditie = intval($mcodbara);
    				if(empty($expeditie) || (true !== ($ret = $this->checkCodBara($expeditie, $cod_cl))))
           			{
           				$error .= "error : Expeditia ".$expeditie." ".$ret." : import failed";
           				UploadXls::send_message($serverTime, $error, round($rowIndex*$proc), 1);
           				continue;
           			}
    			}

    			//exceptii localitate, judet
    			if(in_array($this->mapCellHeader('destloc', $cod_cl, $tip),$firstLine)){
    				$destinatar_localitate = $worksheet->getCell($colsMap[$this->mapCellHeader('destloc', $cod_cl, $tip)].$rowIndex)->getValue();
					$destinatar_localitate = (!empty(Backend::sSanitizeCleanEdges($destinatar_localitate ?? "")) ? strtoupper(Backend::sSanitizeCleanEdges($destinatar_localitate)):"");
				}
    			else {
					$error .= "error : Expeditia ".$expeditie." localitate not found : import failed";
					UploadXls::send_message($serverTime, $error, round($rowIndex*$proc), 1);
					continue;
           		}
           		if(in_array($this->mapCellHeader('destjud', $cod_cl, $tip),$firstLine)){
    				$destinatar_judet = $worksheet->getCell($colsMap[$this->mapCellHeader('destjud', $cod_cl, $tip)].$rowIndex)->getValue();
					$destinatar_judet = (!empty(Backend::sSanitizeCleanEdges($destinatar_judet ?? "")) ? strtoupper(Backend::sSanitizeCleanEdges($destinatar_judet)):"");
				}
				else {
					$error .= "error : Expeditia ".$expeditie." judet not found : import failed";
					UploadXls::send_message($serverTime, $error, round($rowIndex*$proc), 1);
					continue;
           		}
				$test_destinatar_localitate = $this->testLocalitate($destinatar_localitate);
				if($test_destinatar_localitate == 'Bucuresti')
				{
					$query="select cod_lc, nume_lc, dist_km, cod_centru from {$this->tables['localitati']} where nume_lc like :test_destinatar_localitate and cod_jd like 'B' limit 1";
    				$sql = $this->db->QFetchRowAssoc($query, ['test_destinatar_localitate'=>$test_destinatar_localitate]);
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
					$query="select cod_jd from {$this->tables['judete']} where replace(replace(nume_jd,' ',''),'-','') like replace(replace(:destinatar_judet,' ',''),'-','') limit 1";
    				$sql = $this->db->QFetchRowAssoc($query, ['destinatar_judet'=>$destinatar_judet]);
					if(empty($sql)) {  $error .= 'error : Expeditia '.$expeditie.' : judet inexistent : '.$destinatar_judet.' : import failed';  UploadXls::send_message($serverTime, $error, round($rowIndex*$proc), 1); continue; }
					else $judet = $sql['cod_jd'];
					//cauta localitatea
					$query="select cod_lc, nume_lc, dist_km, cod_centru from {$this->tables['localitati']} where replace(replace(nume_lc,' ',''),'-','') like replace(replace(:destinatar_localitate,' ',''),'-','')  and cod_jd like :judet limit 1";
    				$sql = $this->db->QFetchRowAssoc($query, ['destinatar_localitate'=>$destinatar_localitate, 'judet'=>$judet]);
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
				$vi['expeditor'] = $this->expeditor_id = $cod_cl;
				$vi['expeditor_contact'] = $this->expeditor_contact;
				$vi['expeditor_telefon'] = $this->expeditor_telefon;
				$vi['user_id'] = $sql_client['user_id'];
				$vi['data_expeditie'] =  date("Y-m-d");
				$vi['expeditie'] = $expeditie;

				$vi['platitor'] = 1;
				if(in_array($this->mapCellHeader('platitor', $cod_cl, $tip),$firstLine))
				{
					$platitor = strtoupper($this->sanitize($worksheet->getCell($colsMap[$this->mapCellHeader('platitor', $cod_cl, $tip)].$rowIndex)->getValue()));
					if($platitor == 'DESTINATAR')
						$vi['platitor'] = 2;
				}

				$vi['destinatar_localitate'] = $destinatar_localitate;
				$vi['destinatar_localitate_id'] = $destinatar_localitate_id;
				$vi['km_ext_livr'] = intval(round(floatval($km_ext_livr)));
				$vi['km_ext_prel'] = intval(round(floatval($sql_client['expeditor_localitate_km'])));

				$vi['destinatar_contact'] = '';
				$vi['destinatar_telefon'] = '';
				if(in_array($this->mapCellHeader('destpers', $cod_cl, $tip),$firstLine)){
					$destinatar_contact = $worksheet->getCell($colsMap[$this->mapCellHeader('destpers', $cod_cl, $tip)].$rowIndex)->getValue();
					$destinatar_contact = (!empty(Backend::sSanitizeCleanEdges($destinatar_contact ?? "")) ? strtoupper(Backend::sSanitizeCleanEdges($destinatar_contact)):"");
					$vi['destinatar_contact'] = !empty($destinatar_contact) ? $destinatar_contact : "";
				}
				if(in_array($this->mapCellHeader('desttel', $cod_cl, $tip),$firstLine)){
					$destinatar_telefon = strtoupper($this->sanitize($worksheet->getCell($colsMap[$this->mapCellHeader('desttel', $cod_cl, $tip)].$rowIndex)->getValue()));
					$vi['destinatar_telefon'] = !empty($destinatar_telefon) ? $destinatar_telefon : "";
				}

				//cauta destinatarul in tabla clienti
				$destinatar = "";
				$destinatar_adresa = "";
				if(in_array($this->mapCellHeader('destinatar', $cod_cl, $tip),$firstLine)){
    				$destinatar = $worksheet->getCell($colsMap[$this->mapCellHeader('destinatar', $cod_cl, $tip)].$rowIndex)->getValue();
					$destinatar = (!empty(Backend::sSanitizeCleanEdges($destinatar ?? "")) ? strtoupper(Backend::sSanitizeCleanEdges($destinatar)):"");
				}
				else {
					$error .= "error : Expeditia ".$expeditie." destinatar not found : import failed";
					UploadXls::send_message($serverTime, $error, round($rowIndex*$proc), 1);
					continue;
           		}
				if(in_array($this->mapCellHeader('destadresa', $cod_cl, $tip),$firstLine)){
					$destinatar_adresa = $worksheet->getCell($colsMap[$this->mapCellHeader('destadresa', $cod_cl, $tip)].$rowIndex)->getValue();
					$destinatar_adresa = (!empty(Backend::sSanitizeCleanEdges($destinatar_adresa ?? "")) ? strtoupper(Backend::sSanitizeCleanEdges($destinatar_adresa)):"");
				}
				else {
					$error .= "error : Expeditia ".$expeditie." adresa not found : import failed";
					UploadXls::send_message($serverTime, $error, round($rowIndex*$proc), 1);
					continue;
           		}
				if(strlen($destinatar_adresa) < 4) {
					$error .= "error : Expeditia ".$expeditie." adresa destinatar obligatorie : import failed";
					UploadXls::send_message($serverTime, $error, round($rowIndex*$proc), 1);
					continue;
				}
				$query = "select cod_cl from {$this->tables['clienti']} where nume like :destinatar
					and adresa like :destinatar_adresa and cod_lc = ".$vi['destinatar_localitate_id']." limit 1";
	    		$sql = $this->db->QFetchRowAssoc($query, ['destinatar'=>$destinatar, 'destinatar_adresa'=>$destinatar_adresa]);
	    		if (empty($sql)) {//in cazul in care nu exista clientul => insert
					//detalii
					$var = [];
		    		$var['nume'] = $destinatar;
					$var['contact'] = $vi['destinatar_contact'];
					$var['telefon'] = $vi['destinatar_telefon'];
		    		$var['cod_lc'] = $vi['destinatar_localitate_id'];
		    		$var['adresa'] = $destinatar_adresa;
		    		$var['operator'] = $this->user_id;
		    		$var['activ'] = 1;
		    		$var['data_op'] = date('Y-m-d H:i:s');
		    		$var['tarif'] = 0;
		    		$var['mod_plata'] = 0;
		    		$var['km_ext'] = $vi['km_ext_livr'];

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
	    		$qc = "SELECT cod_cl, id, nume, activ FROM {$this->tables['client_destinatari']}
	    			WHERE nume like :destinatar and adresa like :destinatar_adresa
	    			and id_loc = ".$vi['destinatar_localitate_id']." and id_exp = ".$vi['expeditor']." limit 1";
	    		$sc = $this->db->QFetchRowAssoc($qc, ['destinatar'=>$destinatar, 'destinatar_adresa'=>$destinatar_adresa]);
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
					$cod_cl_dest = $this->db->QueryInsert($this->tables['client_destinatari'], $var);

				}
				else
				{
					$cod_cl_dest = $sc['id'];
					$this->db->QueryUpdate($this->tables['client_destinatari'], ['contact' => $vi['destinatar_contact'], 'telefon' => $vi['destinatar_telefon'], 'updated_at' => date('Y-m-d H:i:s'), 'updated_by' => $this->user_id], "id=".$cod_cl_dest);
					if( $sc['activ'] == 0 || $sc['cod_cl'] != $vi['destinatar_id'])
					{
						$this->db->QueryUpdate($this->tables['client_destinatari'], ['cod_cl' => $vi['destinatar_id'], 'activ' => 1], "id=".$cod_cl_dest);
					}
				}

				if(in_array($this->mapCellHeader('tipexp', $cod_cl, $tip),$firstLine))
					$tipexp = $this->sanitize($worksheet->getCell($colsMap[$this->mapCellHeader('tipexp', $cod_cl, $tip)].$rowIndex)->getValue());
				if($cod_cl == 171350 || $cod_cl == 883828 || $cod_cl == 435076) $vi['tip_obj'] = 2;
				else if(empty($tipexp)) $vi['tip_obj'] = 2;
				else $vi['tip_obj'] = match(strtoupper($tipexp)) {
					'PLIC' => 1,
					'COLET' => 2,
					'PALET' => 3,
					default => 2
				};

				if($vi['tip_obj'] == 1 || $vi['tip_obj'] == 3)
					$vi['piese'] = 1;
				else {
					$vi['piese'] = 0;
					if(in_array($this->mapCellHeader('bucati', $cod_cl, $tip),$firstLine))
						$vi['piese'] = intval($this->sanitize($worksheet->getCell($colsMap[$this->mapCellHeader('bucati', $cod_cl, $tip)].$rowIndex)->getValue()));
					if(empty($vi['piese'])) {  $error .= 'error : Expeditia '.$expeditie.' : colet | palet valoare eronata : '.$vi['piese'].' : import failed'; UploadXls::send_message($serverTime, $error, round($rowIndex*$proc), 1); continue; }
				}
				if($vi['tip_obj'] == 1)
					$vi['greutate'] = 0.5;
				else {
					$vi['greutate'] = 0;
					if(in_array($this->mapCellHeader('greutate', $cod_cl, $tip),$firstLine))
						$vi['greutate'] = floatval($this->sanitize($worksheet->getCell($colsMap[$this->mapCellHeader('greutate', $cod_cl, $tip)].$rowIndex)->getValue()));
					if(empty($vi['greutate'])) {  $error .= 'error : Expeditia '.$expeditie.' : greutate eronata : '.$vi['greutate'].' : import failed'; UploadXls::send_message($serverTime, $error, round($rowIndex*$proc), 1); continue; }
				}
				if($vi['tip_obj'] == 2 && $vi['greutate'] < parent::MIN_KG_COLET){
					$vi['greutate'] = parent::MIN_KG_COLET;
				}
				else if($vi['tip_obj'] == 3 && $vi['greutate'] < parent::MIN_KG_PALET){
					$vi['greutate'] = parent::MIN_KG_PALET;
				}

				//retururi
				$ret_nt = $vi['ret_nt']=0;
				if(in_array($this->mapCellHeader('returnt', $cod_cl, $tip),$firstLine))
					$ret_nt = $this->sanitize($worksheet->getCell($colsMap[$this->mapCellHeader('returnt', $cod_cl, $tip)].$rowIndex)->getValue());
				if(!empty($ret_nt)) {
					$ret_nt = strtoupper($ret_nt);
					if($ret_nt=='DA' || $ret_nt=='TRUE') $vi['ret_nt']=1;
				}
				$ret_doc = $vi['ret_doc']=0;
				if(in_array($this->mapCellHeader('returdoc', $cod_cl, $tip),$firstLine))
					$ret_doc = $this->sanitize($worksheet->getCell($colsMap[$this->mapCellHeader('returdoc', $cod_cl, $tip)].$rowIndex)->getValue());
				if(!empty($ret_doc)) {
					$ret_doc = strtoupper($ret_doc);
					if($ret_doc=='DA' || $ret_doc=='TRUE') $vi['ret_doc']=1;
				}
				if(in_array($this->mapCellHeader('deschiderecolet', $cod_cl, $tip),$firstLine))
					$copen = $this->sanitize($worksheet->getCell($colsMap[$this->mapCellHeader('deschiderecolet', $cod_cl, $tip)].$rowIndex)->getValue());
				if(!empty($copen)) {
					$copen = strtoupper($copen);
					if($copen=='DA' || $copen=='TRUE') $vi['copen']=1;
				}
				if(in_array($this->mapCellHeader('sms', $cod_cl, $tip),$firstLine))
					$sms = $this->sanitize($worksheet->getCell($colsMap[$this->mapCellHeader('sms', $cod_cl, $tip)].$rowIndex)->getValue());
				if(!empty($sms)) {
					$sms = strtoupper($sms);
					if($sms=='DA' || $sms=='TRUE') $vi['sms']= -1;
				}

				$vi['ret_amb'] = 0;
				$livrare = $vi['liv_sambata']=0;
				$vi['liv_sediu']=0;
				if(in_array($this->mapCellHeader('livrare', $cod_cl, $tip),$firstLine))
					$livrare = $this->sanitize($worksheet->getCell($colsMap[$this->mapCellHeader('livrare', $cod_cl, $tip)].$rowIndex)->getValue());
				if(!empty($livrare)) {
					$livrare = strtoupper($livrare);
					if($livrare=='SAMBATA') $vi['liv_sambata']=1;
					else if($livrare=='SEDIU') $vi['liv_sediu']=1;
					else if(($cod_cl == 171350 || $cod_cl == 883828 || $cod_cl == 435076) &&  $livrare=='DA') $vi['liv_sambata']=1;
				}

				//asigurare
				$asigurare = $vi['asigurare'] = 0.00;
				if(in_array($this->mapCellHeader('asigurare', $cod_cl, $tip),$firstLine))
				{
					$asigurare = $this->sanitize($worksheet->getCell($colsMap[$this->mapCellHeader('asigurare', $cod_cl, $tip)].$rowIndex)->getValue());
					$asigurare = floatval(str_replace(',', '.', $asigurare));
				}
				if(!empty($asigurare)) { $vi['asigurare'] = round($asigurare,2);}
				//ramburs
				$ramburs = $vi['ramburs'] = 0.00;
				$vi['tip_plata'] = 0;
				if(in_array($this->mapCellHeader('ramburs', $cod_cl, $tip),$firstLine))
				{
					$ramburs = $this->sanitize($worksheet->getCell($colsMap[$this->mapCellHeader('ramburs', $cod_cl, $tip)].$rowIndex)->getValue());
					$ramburs = floatval(str_replace(',', '.', $ramburs));
				}
				if(!empty($ramburs)) {
					$vi['ramburs'] = round($ramburs,2);
					$vi['tip_plata'] = (!empty($this->cc)) ? 3:0;
					$tip_plata = '';
					if(in_array($this->mapCellHeader('tipramburs', $cod_cl, $tip),$firstLine))
						$tip_plata = $this->sanitize($worksheet->getCell($colsMap[$this->mapCellHeader('tipramburs', $cod_cl, $tip)].$rowIndex)->getValue());
					if(!empty($tip_plata)){
						$tip_plata = strtoupper($tip_plata);
						if($tip_plata=='BO') $vi['tip_plata']=1;
						else if($tip_plata=='CEC') $vi['tip_plata']=2;
					}
				}

				if(in_array($this->mapCellHeader('detaliidoc', $cod_cl, $tip),$firstLine))
					$vi['detalii_doc'] = Backend::sSanitizeCleanEdges($worksheet->getCell($colsMap[$this->mapCellHeader('detaliidoc', $cod_cl, $tip)].$rowIndex)->getValue() ?? '');
				if(in_array($this->mapCellHeader('obs', $cod_cl, $tip),$firstLine))
					$vi['observatii'] = Backend::sSanitizeCleanEdges($worksheet->getCell($colsMap[$this->mapCellHeader('obs', $cod_cl, $tip)].$rowIndex)->getValue() ?? '');

				$vi['mod_plata'] = 0;
				if($vi['platitor'] == 1)
					$vi['mod_plata'] = $this->mod_plata;
				$vi['tip_tarif'] = (($this->expeditor_localitate_id != $vi['destinatar_localitate_id']) ? 1 : 0);
				$valoare = $this->Get_ValoareExpeditieClient($vi);
				unset($vi['tip_tarif']);

				$vi['moneda'] = ExpeditieDto::MONEDA_REV[$valoare['moneda']] ?? "LEI";

				$vi['mod_plata'] = $valoare['mod_plata'];
				$vi['valoare_exp'] =$valoare['tExpeditie'];
				$vi['valoare_km'] = $valoare['tKm'];
				$vi['valoare_g'] = $valoare['tGreutate'];
				$vi['valoare_asig'] = $valoare['tAsigurare'] + $valoare['tRamburs'];

				$vi['valoare_totala'] = $vi['valoare_exp'] + $vi['valoare_km'] + $vi['valoare_g'] + $vi['valoare_asig'];
				$vi['valoare_totala'] = round($vi['valoare_totala'],2);
				$vi['valoare_tva'] = $vi['valoare_totala'] * $this->procTva / 100;
				$vi['valoare_tva'] = round($vi['valoare_tva'],2);
				$vi['procTva'] = $this->procTva;

				//baza de date de cacat
				$vi['destinatar_cod_cl'] = $vi['destinatar_id'];
				$vi['destinatar_id'] = $cod_cl_dest;
				//source
				$vi['src'] = 9;

				$vi['created_at'] = date('Y-m-d H:i:s');
				$vi['created_by'] = $this->user_id;
				$id = $this->db->QueryInsert($this->tables['client_expeditii'], $vi);
				//maravet | biovet
				if($cod_cl == 171350 || $cod_cl == 883828 || $cod_cl == 435076) {
					$extrainfo =  $largeinfo =  '';
					if(in_array('extrainfo',$firstLine))
						$extrainfo =  $this->sanitize($worksheet->getCell($colsMap['extrainfo'].$rowIndex)->getValue());
					if(in_array('largeinfo',$firstLine))
						$largeinfo =  $this->sanitize($worksheet->getCell($colsMap['largeinfo'].$rowIndex)->getValue());

					if(!empty($extrainfo))
						$this->db->QueryInsert('exp_nc', array('expeditie'=>$vi['expeditie'],'extrainfo'=>$extrainfo,'largeinfo'=>$largeinfo, 'created_at'=>date('Y-m-d H:i:s'), 'created_by'=>$this->user_id));
       			}
       			UploadXls::send_message($serverTime, 'Expeditia '.$vi['expeditie'].' importata cu succes', round($rowIndex*$proc));
			}
       		catch(Exception $e) {
    			UploadXls::send_message($serverTime, 'error at line '.$row->getRowIndex().' : '.$e->getMessage(), round($rowIndex*$proc), 1);
			}
		}
		UploadXls::send_message($serverTime, '', 100, 3);
        exit(0);
	}

	/////IMPORT DESTINATARI
	function ImportDestinatariXlsStep1() {
        $this->vars['title_page'] = 'Import Destinatari';
        $vars = [];
		$vars['error'] = (isset($this->vars['error']))?'<span style="color:red">'.$this->vars['error'].'</span>':'';
		$this->vars['error'] = '';
        return $this->Parse($this->page_prefix . 'destinatari_xls.html', $vars);
    }


    function ImportDestinatariXlsStep2(){
    	require_once 'uploadXls.php';
    	$this->vars['title_page'] = 'Verificare';
        $this->vars['error'] = '';

        if(!isset($_POST['cod_cl']) || (isset($_POST['cod_cl']) && !is_numeric($_POST['cod_cl']))) {
        	$this->vars['error'] .= 'Selectati clientul';
			return $this->ImportDestinatariXlsStep1();
		}
    	$cod_cl = intval($this->sanitize($_POST['cod_cl']));
        if(empty($cod_cl)) {
        	$this->vars['error'] .= 'Selectati clientul';
			return $this->ImportDestinatariXlsStep1();
		}

        if(isset($_POST['sxls']) && !empty($_FILES['fxls'])){
        	$upload = UploadXls::uploadXlsUp('fxls');
        	if(is_array($upload) && count($upload) == 2) {
        		if($upload[0])
        			return $this->Parse($this->page_prefix . 'destinatari_xls_step2.html', array('fisier'=>$upload[1], 'cod_cl'=>$cod_cl, 'results'=>''));
        		else {
        			$this->vars['error'] = $upload[1];
        			return $this->ImportDestinatariXlsStep1();
        		}
        	}
        	else {
        		$this->vars['error'] = 'Unknown error';
        		return $this->ImportDestinatariXlsStep1();
        	}
        }
		else {
			$this->vars['error'] .= 'Selectati fisierul';
			return $this->ImportDestinatariXlsStep1();
		}
    }

    function ImportDestinatariXlsStep3(){
    	$this->vars['title_page'] = 'Import';
        $this->vars['error'] = '';

        if(!isset($_POST['cod_cl']) || (isset($_POST['cod_cl']) && !is_numeric($_POST['cod_cl']))) {
        	$this->vars['error'] .= 'Selectati clientul';
			return $this->ImportDestinatariXlsStep1();
		}
    	$cod_cl = intval($this->sanitize($_POST['cod_cl']));

        if(empty($cod_cl)) {
        	$this->vars['error'] .= 'Selectati clientul';
			return $this->ImportDestinatariXlsStep1();
		}

        if(isset($_POST['sxls']) && !empty($_POST['fxls']) && isset($_POST['merror'])){
			return $this->Parse($this->page_prefix . 'destinatari_xls_step3.html', array('fisier'=>$_POST['fxls'], 'cod_cl'=>$cod_cl, 'merror'=>$_POST['merror']));
		}
		else {
			$this->vars['error'] .= 'Selectati fisierul';
			return $this->ImportDestinatariXlsStep1();
		}
    }

	//procesare import
	function ImportDestinatariXlsCheck(){
		set_time_limit(600);
		header('Content-Type: text/event-stream');
		header('Cache-Control: no-cache'); // recommended to prevent caching of event data.
		header("Access-Control-Allow-Origin: *");

		require_once 'uploadXls.php';

		$firstLine = array('nume', 'contact', 'adresa', 'localitate', 'judet', 'telefon');
		$nbCols = count($firstLine);
		$serverTime = time();

 		$cod_cl = 0;
 		if(empty($_GET['cod_cl'])) { UploadXls::send_message($serverTime, 'Error client : empty client', 0, 2); exit(0); }
		else {
			$cod_cl = intval($this->sanitize($_GET['cod_cl']));
			if(empty($cod_cl)) { UploadXls::send_message($serverTime, 'Error client : empty client', 0, 2); exit(0); }
		}

		//check client activ=1
		if(true !== ($ret = $this->checkClient($cod_cl)))
           { UploadXls::send_message($serverTime, 'Error client : '.$ret, 0, 2); exit(0); }

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
			UploadXls::send_message($serverTime, ($highestRow - 1) . ' destinatari de importat<br/>', 0, 0);
			$highestColumn = $worksheet->getHighestDataColumn();
			$nbhighestColumn = Coordinate::columnIndexFromString($highestColumn);
			if($nbhighestColumn == 0)
				throw new Exception("fisierul este gol");
			if($nbhighestColumn != $nbCols)
				throw new Exception("fisierul are un numar de coloane (".$nbhighestColumn.") diferit de ".$nbCols . " : coloanele bune sunt urmatoarele : ".implode(',',$firstLine));

			$colNames = $worksheet->rangeToArray('A1:' . $highestColumn . 1, NULL, TRUE, FALSE);
			if($firstLine != $colNames[0])
				throw new Exception("denumirile coloanelor gresite : coloanele bune sunt urmatoarele : ".implode(',',$firstLine));

			$colsRange = array_combine($this->excelRange($nbCols),$firstLine);
			$rowIterator = $worksheet->getRowIterator();

			$terrors = false;
			$kerrors = 0;
			foreach($rowIterator as $row){
    			$cellIterator = $row->getCellIterator();
    			$cellIterator->setIterateOnlyExistingCells(false); // Loop all cells, even if it is not set
    			if(1 == $row->getRowIndex()) continue;//skip first row
    			$rowIndex = $row->getRowIndex();
    			$localitate = '';
    			$error = '';

            	foreach ($cellIterator as $cell) {
					if(!isset($colsRange[$cell->getColumn()]))
						continue;
					$fLineCol = $colsRange[$cell->getColumn()];
           			$val = $this->sanitize($cell->getValue());
           			switch($fLineCol) {
           				case 'nume':
           					if(empty($val))
           						$error .= "valoare eronata la linia : ".$rowIndex." coloana : ".$fLineCol ." : lipseste numele destinatarului"."<br/>";
           					break;
           				case 'adresa':
           					if(empty($val) || strlen($val) < 4)
           						$error .= "valoare eronata la linia : ".$rowIndex." coloana : ".$fLineCol ." : lipseste adresa de destinatie"."<br/>";
           					break;
           				case 'localitate':
           					if(empty($val))
           						$error .= "valoare eronata la linia : ".$rowIndex." coloana : ".$fLineCol ." : lipseste orasul de destinatie"."<br/>";
           					$localitate = $val;
           					break;
           				case 'judet':
           					if(empty($val))
           						$error .= "valoare eronata la linia : ".$rowIndex." coloana : ".$fLineCol ." : lipseste judetul de destinatie"."<br/>";
           					else if(!empty($localitate) && true !== ($ret = $this->checkLocalitate($localitate, $val)))
           						$error .= "valoare eronata la linia : ".$rowIndex." : ".$ret;
           					break;
           				case 'contact':
           					break;
           				case 'telefon':
           					break;
           				default :
           					break;
           			}
       			}
       			UploadXls::send_message($serverTime, $error, round(($rowIndex/$highestRow)*100), 1);
       			if(!empty($error)) { $kerrors++; $terrors = true; }
			}
		} catch(Exception $e) {
    		UploadXls::send_message($serverTime, 'Error parsing file "'.pathinfo($inputFileName,PATHINFO_BASENAME).'": '.$e->getMessage(), 0, 2);
    		exit(0);
		}
		if($terrors)
			UploadXls::send_message($serverTime,'<br/>'.$kerrors.'erori de corectat', 100, 2);
		else
			UploadXls::send_message($serverTime, '', 100, 3);
        exit(0);
    }

   function ImportDestinatariXlsImport(){
   		set_time_limit(600);
		header('Content-Type: text/event-stream');
		header('Cache-Control: no-cache'); // recommended to prevent caching of event data.
		header("Access-Control-Allow-Origin: *");

		require_once 'uploadXls.php';

		$firstLine = array('nume', 'contact', 'adresa', 'localitate', 'judet', 'telefon');
		$serverTime = time();

		$cod_cl = 0;
		if(empty($_GET['cod_cl'])) { UploadXls::send_message($serverTime, 'Error client : empty client', 0, 2); exit(0); }
		else {
			$cod_cl = intval($this->sanitize($_GET['cod_cl']));
			if(empty($cod_cl)) { UploadXls::send_message($serverTime, 'Error client : empty client', 0, 2); exit(0); }
		}

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
			UploadXls::send_message($serverTime, ($highestRow - 1) . ' destinatari de importat<br/>', 0, 0);
			$highestColumn = $worksheet->getHighestDataColumn();
			$nbhighestColumn = Coordinate::columnIndexFromString($highestColumn);

			$colsMap = array_combine($firstLine, range('A', $highestColumn));
			if($colsMap === false)
				throw new Exception("numar de coloane gresit : array_combine");

			$rowIterator = $worksheet->getRowIterator();

		} catch(Exception $e) {
    		UploadXls::send_message($serverTime, $e->getMessage(), 0, 2);
    		exit(0);
		}


		foreach($rowIterator as $row){
			try {
    			if(1 == $row->getRowIndex ()) continue;//skip first row
    			$rowIndex = $row->getRowIndex();
    			$error = '';

    			$destinatar_nume = $worksheet->getCell($colsMap['nume'].$rowIndex)->getValue();
				$destinatar_nume = (!empty(Backend::sSanitizeCleanEdges($destinatar_nume ?? "")) ? strtoupper(Backend::sSanitizeCleanEdges($destinatar_nume)):"");
    			$destinatar_adresa = $worksheet->getCell($colsMap['adresa'].$rowIndex)->getValue();
				$destinatar_adresa = (!empty(Backend::sSanitizeCleanEdges($destinatar_adresa ?? "")) ? strtoupper(Backend::sSanitizeCleanEdges($destinatar_adresa)):"");
    			//exceptii localitate, judet
    			$destinatar_localitate = $worksheet->getCell($colsMap['localitate'].$rowIndex)->getValue();
				$destinatar_localitate = (!empty(Backend::sSanitizeCleanEdges($destinatar_localitate ?? "")) ? strtoupper(Backend::sSanitizeCleanEdges($destinatar_localitate)):"");
    			$destinatar_judet = $worksheet->getCell($colsMap['judet'].$rowIndex)->getValue();
				$destinatar_judet = (!empty(Backend::sSanitizeCleanEdges($destinatar_judet ?? "")) ? strtoupper(Backend::sSanitizeCleanEdges($destinatar_judet)):"");
				$test_destinatar_localitate = $this->testLocalitate($destinatar_localitate);
				$km_ext = 0;
				$destinatar_cod_centru = 0;
				if($test_destinatar_localitate == 'Bucuresti')
				{
					$query="select cod_lc, nume_lc, dist_km, cod_centru from {$this->tables['localitati']} where nume_lc like :test_destinatar_localitate and cod_jd like 'B' limit 1";
    				$sql = $this->db->QFetchRowAssoc($query, ['test_destinatar_localitate'=>$test_destinatar_localitate]);
					if(empty($sql)) { $error .= 'error : Localitate '.$test_destinatar_localitate.' : localitate eronata : '.$destinatar_localitate.' : import failed';  UploadXls::send_message($serverTime, $error, round(($rowIndex/$highestRow)*100), 1); continue; }
					else
					{
						$destinatar_localitate = $sql['nume_lc'];
						$destinatar_localitate_id = $sql['cod_lc'];
						$destinatar_cod_centru = $sql['cod_centru'];
						$km_ext = $sql['dist_km'];
					}
				}
				else
				{
					//cauta judetul
					$query="select cod_jd from {$this->tables['judete']} where replace(replace(nume_jd,' ',''),'-','') like replace(replace(:destinatar_judet,' ',''),'-','') limit 1";
    				$sql = $this->db->QFetchRowAssoc($query, ['destinatar_judet'=>$destinatar_judet]);
					if(empty($sql)) {  $error .= 'error : Expeditia '.$expeditie.' : judet inexistent : '.$destinatar_judet.' : import failed';  UploadXls::send_message($serverTime, $error, round(($rowIndex/$highestRow)*100), 1); continue; }
					else $judet = $sql['cod_jd'];
					//cauta localitatea
					$query="select cod_lc, nume_lc, dist_km, cod_centru from {$this->tables['localitati']} where replace(replace(nume_lc,' ',''),'-','') like replace(replace(:destinatar_localitate,' ',''),'-','')  and cod_jd like :judet limit 1";
    				$sql = $this->db->QFetchRowAssoc($query, ['destinatar_localitate'=>$destinatar_localitate, 'judet'=>$judet]);
					if(empty($sql)) {  $error .= 'error : Expeditia '.$expeditie.' : localitate eronata : '.$destinatar_localitate.' : import failed';  UploadXls::send_message($serverTime, $error, round(($rowIndex/$highestRow)*100), 1); continue; }
					else
					{
						$destinatar_localitate = $sql['nume_lc'];
						$destinatar_localitate_id = $sql['cod_lc'];
						$destinatar_cod_centru = $sql['cod_centru'];
						$km_ext = $sql['dist_km'];
					}
				}
				$user_id = $this->GetFieldValue('users','id','expeditor_id='.$cod_cl);

				$destinatar_contact = $worksheet->getCell($colsMap['contact'].$rowIndex)->getValue();
				$destinatar_contact = (!empty(Backend::sSanitizeCleanEdges($destinatar_contact ?? "")) ? strtoupper(Backend::sSanitizeCleanEdges($destinatar_contact)):"");
				$destinatar_telefon = $this->sanitize($worksheet->getCell($colsMap['telefon'].$rowIndex)->getValue());

				//$destinatar_id = 0;
				//cauta destinatarul in tabla clienti
				$query = "select cod_cl from {$this->tables['clienti']} where nume like :destinatar_nume
					and adresa like :destinatar_adresa and cod_lc = ".$destinatar_localitate_id. " limit 1";
	    		$sql = $this->db->QFetchRowAssoc($query, ['destinatar_nume'=>$destinatar_nume, 'destinatar_adresa'=>$destinatar_adresa]);
	    		if (empty($sql)) {//in cazul in care nu exista destinatarul => insert
					//detalii
					$var = [];
		    		$var['nume'] = strtoupper($destinatar_nume);
					$var['contact'] = strtoupper($destinatar_contact);
					$var['telefon'] = strtoupper($destinatar_telefon);
		    		$var['cod_lc'] = $destinatar_localitate_id;
		    		$var['adresa'] = strtoupper($destinatar_adresa);
		    		$var['operator'] = $this->user_id;
		    		$var['activ'] = 1;
		    		$var['data_op'] = date("Y-m-d");
		    		$var['tarif'] = 0;
		    		$var['mod_plata'] = 0;
		    		$var['km_ext'] = $km_ext;

					$var['created_at'] = date('Y-m-d H:i:s');
					$var['created_by'] = $this->user_id;
		    		$destinatar_id=$this->db->QueryInsert($this->tables['clienti'], $var);
					CdsGeocoder::geocode($this->db, $destinatar_id);
		    		unset($var);
				}
				else {
					$destinatar_id = $sql['cod_cl'];
					CdsGeocoder::geocode($this->db, $destinatar_id, true);
				}

				//cauta destinatarul in tabla client_destinatari
	    		$qc = "SELECT cod_cl, id, nume, activ FROM {$this->tables['client_destinatari']}
	    		WHERE nume like :destinatar_nume and adresa like :destinatar_adresa
	    		and id_loc = ".$destinatar_localitate_id." and id_exp = ".$cod_cl." limit 1";
	    		$sc = $this->db->QFetchRowAssoc($qc, ['destinatar_nume'=>$destinatar_nume, 'destinatar_adresa'=>$destinatar_adresa]);
	    		if(empty($sc)) //in cazul in care nu exista linkul client:destinatar => insert
				{
					$var = [];
					$var['id_exp'] = $cod_cl;
					$var['nume'] = $destinatar_nume;
					$var['id_loc'] = $destinatar_localitate_id;
					$var['km_ext'] = $km_ext;
					$var['adresa'] = $destinatar_adresa;
					$var['activ'] = 1;
					$var['cod_cl'] = $destinatar_id;
					$var['data_op'] = date("Y-m-d");
					$var['observatii'] = '';
					$var['contact'] = $destinatar_contact;
					$var['telefon'] = $destinatar_telefon;

					$var['created_at'] = date('Y-m-d H:i:s');
					$var['created_by'] = $this->user_id;
					$cod_cl_dest = $this->db->QueryInsert($this->tables['client_destinatari'], $var);

				}
				else
				{
					$cod_cl_dest = $sc['id'];
					$this->db->QueryUpdate($this->tables['client_destinatari'], ['contact' => $destinatar_contact, 'telefon' => $destinatar_telefon, 'updated_at' => date('Y-m-d H:i:s'), 'updated_by' => $this->user_id], "id=".$cod_cl_dest);
					if( $sc['activ'] == 0 || $sc['cod_cl'] != $destinatar_id)
					{
						$this->db->QueryUpdate($this->tables['client_destinatari'], ['cod_cl' => $destinatar_id, 'activ' => 1], "id=".$cod_cl_dest);
					}
				}
			}
       		catch(Exception $e) {
    			UploadXls::send_message($serverTime, 'error at line '.$row->getRowIndex().' : '.$e->getMessage(), round(($rowIndex/$highestRow)*100), 1);
			}
       		UploadXls::send_message($serverTime, 'Destinatar '.$destinatar_nume.' importat cu succes', round(($rowIndex/$highestRow)*100));
		}
		UploadXls::send_message($serverTime, '', 100, 3);
        exit(0);
	}

	private function checkClient($cod_cl){
		$ret = '';
		$query="select cod_cl from clienti where activ=1 and cod_cl=". $cod_cl;
		$sql = $this->db->QFetchRowAssoc($query);
		if(empty($sql)) $ret .= ': clientul nr. '.$cod_cl.' nu este activ';

		if(empty($ret)) return true;
		return $ret;
	}

	private function checkCodBara($cod, $cod_cl = 0){
	    if($cod_cl == 171350 || $cod_cl == 883828 || $cod_cl == 435076)
			return $this->checkCodBaraMaravet($cod);
		return $this->checkCodBaraGeneral($cod);
	}

	private function testLocalitate($lc)
	{
		$lc = strtoupper($lc);
		if (0 === strpos($lc, 'BUCURESTI')) return 'Bucuresti';
		if (0 === strpos($lc, 'SECTOR')) return 'Bucuresti';
	}

	private function checkLivrare($liv){
		if(empty($liv)) return true;
		$liv = strtoupper($liv);
		switch($liv){
			case 'NORMALA':
			case 'LIVRARE NORMALA':
			case 'SAMBATA':
			case 'LIVRARE SAMBATA':
			case 'SEDIU':
			case 'LIVRARE SEDIU':
				return true;
			default: return false;
		}
	}

	private function mapCellHeader($header, $cod_cl, $tip = "xls"){
		//$firstLine = array('nt','destinatar','destloc','destjud','destadresa','destpers','desttel','platitor','tipexp','bucati','greutate','asigurare','ramburs','tipramburs','returnt','returdoc','detaliidoc','livrare','obs');
 		//maravet | biovet : 171350 || 883828 || 435076
 		//$firstLine = array('codbara','plic','colet','greutate','clientdest','adresadest','orasdest','judetdest','perscontactdest','telefondest','observatii','serieclient','rambursnumerar','ramburscontcolector','rambursalttip','platitorexpeditie','livraresambata','email','continut','valoaredeclarata','extrainfo','largeinfo');

		if(empty($header) || empty($cod_cl)) return false;

		if($tip == "csv") return $header;

		$cod_cl = intval($cod_cl);

		switch($header){
			case 'nt':
				switch($cod_cl){
					case 171350:
					case 435076:
					case 883828: return 'codbara';
					default: return $header;
				}
           		break;
           	case 'expeditor':
			case 'destinatar':
           		switch($cod_cl){
           			case 171350:
					case 435076:
					case 883828: return 'clientdest';
					default: return $header;
				}
           		break;
			case 'expadresa':
           	case 'destadresa':
           		switch($cod_cl){
           			case 171350:
					case 435076:
					case 883828: return 'adresadest';
					default: return $header;
				}
           		break;
			case 'exploc':
           	case 'destloc':
           		switch($cod_cl){
           			case 171350:
					case 435076:
					case 883828: return 'orasdest';
					default: return $header;
				}
           		break;
           	case 'expjud':
			case 'destjud':
           		switch($cod_cl){
           			case 171350:
					case 435076:
					case 883828: return 'judetdest';
					default: return $header;
				}
           		break;
           	case 'exppers':
			case 'destpers':
           		switch($cod_cl){
           			case 171350:
					case 435076:
					case 883828: return 'perscontactdest';
					default: return $header;
				}
           		break;
           	case 'exptel':
			case 'desttel':
           		switch($cod_cl){
           			case 171350:
					case 435076:
					case 883828: return 'teldest';
					default: return $header;
				}
           		break;
         	case 'platitor':
         		switch($cod_cl){
           			case 171350:
					case 435076:
					case 883828: return 'platitorexpeditie';
					default: return $header;
				}
           		break;
           	case 'tipexp':
           		switch($cod_cl){
           			case 171350:
					case 435076:
					case 883828: return 'colet';
					default: return $header;
				}
           		break;
           	case 'bucati':
           		switch($cod_cl){
           			case 171350:
					case 435076:
					case 883828: return 'colet';
					default: return $header;
				}
           		break;
           	case 'greutate':
           		switch($cod_cl){
           			case 171350:
					case 435076:
					case 883828: return 'greutate';
					default: return $header;
				}
           		break;
           	case 'returnt':
           		switch($cod_cl){
           			case 171350:
					case 435076:
					case 883828: return 'returnt';
					default: return $header;
				}
           		break;
          	case 'returdoc':
           		switch($cod_cl){
           			case 171350:
					case 435076:
					case 883828: return 'returdoc';
					default: return $header;
				}
           		break;
           	case 'asigurare':
           		switch($cod_cl){
           			case 171350:
					case 435076:
					case 883828: return 'asigurare';
					default: return $header;
				}
           		break;
           	case 'ramburs':
           		switch($cod_cl){
           			case 171350:
					case 435076:
					case 883828: return 'ramburs';
					default: return $header;
				}
           		break;
           	case 'tipramburs':
           		switch($cod_cl){
           			case 171350:
					case 435076:
					case 883828: return 'tipramburs';
					default: return $header;
				}
           		break;
           	case 'livrare':
           		switch($cod_cl){
           			case 171350:
					case 435076:
					case 883828: return 'livraresambata';
					default: return $header;
				}
           		break;
           	case 'detaliidoc':
           		switch($cod_cl){
           			case 171350:
					case 435076:
					case 883828: return 'continut';
					default: return $header;
				}
           		break;
           	case 'obs':
           		switch($cod_cl){
           			case 171350:
					case 435076:
					case 883828: return 'observatii';
					default: return $header;
				}
           		break;
           	default : return $header;
        }
        return false;
	}

	public static function checkLocalitateInJudet($db, $judet, $localitate){
		if(strtoupper($judet) == 'BUCURESTI' && substr(strtoupper($localitate), 0, 6) == 'SECTOR') return true;
		if(strtoupper($localitate) == 'BUCURESTI' && (strtoupper($judet) == 'BUCURESTI' || strtoupper($judet) == 'ILFOV')) return true;

		$query="select lc.cod_lc, lc.nume_lc, jd.nume_jd from localitati lc
				left join judete jd on lc.cod_jd = jd.cod_jd
				where replace(replace(lc.nume_lc,' ',''),'-','') like replace(replace(:localitate,' ',''),'-','')
				and (replace(replace(jd.nume_jd,' ',''),'-','') like replace(replace(:judet,' ',''),'-','') or jd.cod_jd like :judet2)";

    	$sql = $db->QFetchRowAssoc($query, ['localitate'=>$localitate, 'judet'=>$judet, 'judet2'=>$judet]);
		if(empty($sql)) {
			return 'city '.$localitate. ' not found in '.$judet;
		}
		return true;
	}

	public static function sTestLocalitate($lc) {
		$lc = strtoupper($lc);
		if (0 === strpos($lc, 'BUCURESTI')) return 'Bucuresti';
		if (0 === strpos($lc, 'SECTOR')) return 'Bucuresti';
	}

	function ImportExpeditiiOpXlsStep1() {
		$this->vars['title_page'] = 'Import Operator Expeditii XLS';
		$vars = [];
		$vars['error'] = (isset($this->vars['error']))?'<span style="color:red">'.$this->vars['error'].'</span>':'';
		$this->vars['error'] = '';
		return $this->Parse($this->page_prefix . 'op_expeditii_xls.html', $vars);
	}

	function ImportExpeditiiOpXlsStep2(){
		require_once 'uploadXls.php';
		$this->vars['title_page'] = 'Verificare';
		$this->vars['error'] = '';

		if(!isset($_POST['cod_cl']) || (isset($_POST['cod_cl']) && !is_numeric($_POST['cod_cl']))) {
			$this->vars['error'] .= 'Selectati clientul';
			return $this->ImportExpeditiiOpXlsStep1();
		}
		$cod_cl = intval($this->sanitize($_POST['cod_cl']));
		if(empty($cod_cl)) {
			$this->vars['error'] .= 'Selectati clientul';
			return $this->ImportExpeditiiOpXlsStep1();
		}

		if(!isset($_POST['sw_cl']) || (isset($_POST['sw_cl']) && !is_numeric($_POST['sw_cl']))) {
			$this->vars['error'] .= 'Selectati tip client';
			return $this->ImportExpeditiiOpXlsStep1();
		}
		$sw_cl = intval($this->sanitize($_POST['sw_cl']));
		if(empty($sw_cl)) {
			$this->vars['error'] .= 'Selectati tip client';
			return $this->ImportExpeditiiOpXlsStep1();
		}

		if(isset($_POST['sxls']) && !empty($_FILES['fxls'])){
			$upload = UploadXls::uploadXlsUp('fxls');
			if(is_array($upload) && count($upload) == 2) {
				if($upload[0])
					return $this->Parse($this->page_prefix . 'op_expeditii_xls_step2.html', array('fisier'=>$upload[1], 'cod_cl'=>$cod_cl, 'sw_cl'=>$sw_cl, 'results'=>''));
				else {
					$this->vars['error'] = $upload[1];
					return $this->ImportExpeditiiOpXlsStep1();
				}
			}
			else {
				$this->vars['error'] = 'Unknown error : upload : '.count($upload);
				return $this->ImportExpeditiiOpXlsStep1();
			}
		}
		else {
			$this->vars['error'] .= 'Selectati fisierul';
			return $this->ImportExpeditiiOpXlsStep1();
		}
	}

	function ImportExpeditiiOpXlsStep3(){
		$this->vars['title_page'] = 'Import';
		$this->vars['error'] = '';

		if(!isset($_POST['cod_cl']) || (isset($_POST['cod_cl']) && !is_numeric($_POST['cod_cl']))) {
			$this->vars['error'] .= 'Selectati clientul';
			return $this->ImportExpeditiiOpXlsStep1();
		}
		$cod_cl = intval($this->sanitize($_POST['cod_cl']));

		if(empty($cod_cl)) {
			$this->vars['error'] .= 'Selectati clientul';
			return $this->ImportExpeditiiOpXlsStep1();
		}

		if(!isset($_POST['sw_cl']) || (isset($_POST['sw_cl']) && !is_numeric($_POST['sw_cl']))) {
			$this->vars['error'] .= 'Selectati tip client';
			return $this->ImportExpeditiiOpXlsStep1();
		}
		$sw_cl = intval($this->sanitize($_POST['sw_cl']));

		if(empty($sw_cl)) {
			$this->vars['error'] .= 'Selectati tip client';
			return $this->ImportExpeditiiOpXlsStep1();
		}

		if(isset($_POST['sxls']) && !empty($_POST['fxls']) && isset($_POST['merror'])){
			return $this->Parse($this->page_prefix . 'op_expeditii_xls_step3.html', array('fisier'=>$_POST['fxls'], 'cod_cl'=>$cod_cl, 'sw_cl'=>$sw_cl, 'merror'=>$_POST['merror']));
		}
		else {
			$this->vars['error'] .= 'Selectati fisierul';
			return $this->ImportExpeditiiOpXlsStep1();
		}
	}

	//check import xls
	function ImportExpeditiiOpXlsCheck(){
		set_time_limit(600);
		header('Content-Type: text/event-stream');
		header('Cache-Control: no-cache'); // recommended to prevent caching of event data.
		header("Access-Control-Allow-Origin: *");

		require_once 'uploadXls.php';

		$serverTime = time();

		$cod_cl = 0;
		if(empty($_GET['cod_cl'])) { UploadXls::send_message($serverTime, 'Error client : empty client', 0, 2); exit(0); }
		else {
			$cod_cl = intval($this->sanitize($_GET['cod_cl']));
			if(empty($cod_cl)) { UploadXls::send_message($serverTime, 'Error client : empty client', 0, 2); exit(0); }
		}
		$sw_cl = 0;
		if(empty($_GET['sw_cl'])) { UploadXls::send_message($serverTime, 'Error client : empty tip client', 0, 2); exit(0); }
		else {
			$sw_cl = intval($this->sanitize($_GET['sw_cl']));
			if(empty($sw_cl)) { UploadXls::send_message($serverTime, 'Error client : empty tip client', 0, 2); exit(0); }
		}

		//check client activ=1
		if(true !== ($ret = $this->checkClient($cod_cl)))
		{ UploadXls::send_message($serverTime, 'Error client : '.$ret, 0, 2); exit(0); }

		$firstLine = array('destinatar','destloc','destjud','destadresa','destpers','desttel','platitor','tipexp','bucati','greutate','asigurare','ramburs','tipramburs','returnt','returdoc','livrare','detaliidoc','obs','nt','deschiderecolet');

		if($sw_cl == 2)
			$firstLine = array('expeditor','exploc','expjud','expadresa','exppers','exptel','platitor','tipexp','bucati','greutate','asigurare','ramburs','tipramburs','returnt','returdoc','livrare','detaliidoc','obs','nt','deschiderecolet');

		$nbCols = count($firstLine);

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
			if($nbhighestColumn == 0)
				throw new Exception("fisierul este gol");
			if($nbhighestColumn != $nbCols)
				throw new Exception("fisierul are un numar de coloane (".$nbhighestColumn.") diferit de ".$nbCols);

			$colNames = $worksheet->rangeToArray('A1:' . $highestColumn . 1, NULL, TRUE, FALSE);
			if($firstLine != $colNames[0])
				throw new Exception("denumirile coloanelor gresite : coloanele bune sunt urmatoarele : ".implode(',',$firstLine));

			$colsRange = array_combine($this->excelRange($nbCols),$firstLine);
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
				if(1 == $row->getRowIndex()) continue;//skip first row
				$rowIndex = $row->getRowIndex();
				$error = '';

				foreach ($cellIterator as $cell) {
					$localitate = '';
					$tipexp = '';
					$fLineCol = strtolower($colsRange[$cell->getColumn()]);
					$val = $this->sanitize($cell->getValue());
					switch($fLineCol) {
						case 'destinatar':
						case 'expeditor':
							if(empty($val))
								$error .= "valoare eronata la linia : ".$rowIndex." coloana : ".$fLineCol ." : lipseste {$fLineCol}"."<br/>";
							break;
						case 'destadresa':
						case 'expadresa':
							if(empty($val) || strlen($val) < 4)
								$error .= "valoare eronata la linia : ".$rowIndex." coloana : ".$fLineCol ." : lipseste  {$fLineCol}"."<br/>";
							break;
						case 'destloc':
						case 'exploc':
							if(empty($val))
								$error .= "valoare eronata la linia : ".$rowIndex." coloana : ".$fLineCol ." : lipseste  {$fLineCol}"."<br/>";
							else
								$localitate = $val;
							break;
						case 'destjud':
						case 'expjud':
							if(empty($val))
								$error .= "valoare eronata la linia : ".$rowIndex." coloana : ".$fLineCol ." : lipseste  {$fLineCol}"."<br/>";
							else if(!empty($localitate) && true !== ($ret = $this->checkLocalitate($localitate, $val)))
								$error .= "valoare eronata la linia : ".$rowIndex." coloana : ".$fLineCol ." : ".$ret;
							break;
						case 'destpers':
						case 'exppers':
							break;
						case 'desttel':
						case 'exptel':
							break;
						case 'platitor':
							if(empty($val)) break;
							if(!(strtoupper($val)=='EXPEDITOR' || strtoupper($val)=='DESTINATAR'))
								$error .= "valoare eronata la linia : ".$rowIndex." coloana : ".$fLineCol ." : valori posibile : expeditor | destinatar"."<br/>";
							break;
						case 'tipexp':
							if(empty($val)) break;
							if(!(strtoupper($val)=='PLIC' || strtoupper($val)=='COLET' || strtoupper($val)=='PALET'))
								$error .= "valoare eronata la linia : ".$rowIndex." coloana : ".$fLineCol ." : valori posibile : plic | colet | palet"."<br/>";
							$tipexp = strtoupper($val);
							break;
						case 'bucati':
							if(empty($tipexp) || ( $tipexp == 'PLIC' || $tipexp == 'COLET'))
								break;
							if(!empty($val) && intval($val) < 1)
								$error .= "valoare eronata la linia : ".$rowIndex." coloana : ".$fLineCol ." : valori posibile : >=1 pentru tipexp COLET"."<br/>";
							break;
						case 'greutate':
							if(empty($tipexp) || $tipexp == 'PLIC')
								break;
							if(empty($val) || intval($val) <= 0)
								$error .= "valoare eronata la linia : ".$rowIndex." coloana : ".$fLineCol ." : valoare obligatorie pentru COLET | PALET"."<br/>";
							else if($tipexp == 'COLET' && intval($val) < parent::MIN_KG_COLET)
								$error .= "valoare eronata la linia : ".$rowIndex." coloana : ".$fLineCol ." : valoare minima " . parent::MIN_KG_COLET . " pentru COLET"."<br/>";
							else if($tipexp == 'PALET' && intval($val) < parent::MIN_KG_PALET)
								$error .= "valoare eronata la linia : ".$rowIndex." coloana : ".$fLineCol ." : valoare minima " . parent::MIN_KG_PALET . " pentru PALET"."<br/>";
							break;
						case 'returnt':
						case 'returdoc':
						case 'deschiderecolet':
							if(!empty($val) && !(strtoupper($val)=='DA' || strtoupper($val)=='NU' || strtoupper($val)=='TRUE' || strtoupper($val)=='FALSE'))
								$error .= "valoare eronata la linia : ".$rowIndex." coloana : ".$fLineCol ." : valori posibile : da | nu | true | false"."<br/>";
							break;
						case 'asigurare':
							$val = floatval(str_replace(',', '.', $val));
           					if(!empty($val) && !is_numeric($val))
           						$error .= "valoare eronata la linia : ".$rowIndex." coloana : ".$fLineCol ." : nu este numeric"."<br/>";
							if(!empty($val) && intval($val) > parent::MAX_ASIGURARE)
								$error .= "valoare eronata la linia : ".$rowIndex." coloana : ".$fLineCol ." : valoare maxima ".parent::MAX_ASIGURARE."<br/>";
							break;
						case 'ramburs':
							$val = floatval(str_replace(',', '.', $val));
							if(!empty($val) && !is_numeric($val))
								$error .= "valoare eronata la linia : ".$rowIndex." coloana : ".$fLineCol ." : nu este numeric"."<br/>";
							break;
						case 'tipramburs':
							if(!empty($val) && !(strtoupper($val)=='CASH' || strtoupper($val)=='CONT' || strtoupper($val)=='BO' || strtoupper($val)=='CEC'))
								$error .= "valoare eronata la linia : ".$rowIndex." coloana : ".$fLineCol ." : valori posibile : cash | cont | bo | cec"."<br/>";
							break;
						case 'livrare':
							if(!empty($val) && !$this->checkLivrare($val))
								$error .= "valoare eronata la linia : ".$rowIndex." coloana : ".$fLineCol ." : valori posibile : normala | sambata | sediu"."<br/>";
							break;
						case 'nt':
							if(intval($val) == 0) {
								$m_msg = 'Expeditia linie '.$rowIndex.' nu are numar nt : o sa fie generat de sistem'."<br/>";
								UploadXls::send_message($serverTime, $m_msg, round($rowIndex*$proc), 0);
								break;
							}
							if(true !== ($ret = $this->checkCodBara($val, $cod_cl)))
								$error .= "valoare eronata la linia : ".$rowIndex." coloana : ".$fLineCol." ".$ret."<br/>";
							if(in_array($val, $mexpeditii))
								$error .= "valoare eronata la linia : ".$rowIndex." coloana : ".$fLineCol." : ".$val." exista deja in acest fisier<br/>";
							$mexpeditii[] = $val;
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

	function ImportExpeditiiOpXlsImport($tip = "xls", $debug = false){
		set_time_limit(600);
		header('Content-Type: text/event-stream');
		header('Cache-Control: no-cache'); // recommended to prevent caching of event data.
		header("Access-Control-Allow-Origin: *");

		require_once 'uploadXls.php';

		$serverTime = time();

		$cod_cl = 0;
		if(empty($_GET['cod_cl'])) { UploadXls::send_message($serverTime, 'Error client : empty client', 0, 2); exit(0); }
		else {
			$cod_cl = intval($this->sanitize($_GET['cod_cl']));
			if(empty($cod_cl)) { UploadXls::send_message($serverTime, 'Error client : empty client', 0, 2); exit(0); }
		}
		$sw_clsw_cl = 0;
		if(empty($_GET['sw_cl'])) { UploadXls::send_message($serverTime, 'Error client : empty tip client', 0, 2); exit(0); }
		else {
			$sw_cl = intval($this->sanitize($_GET['sw_cl']));
			if(empty($sw_cl)) { UploadXls::send_message($serverTime, 'Error client : empty tip client', 0, 2); exit(0); }
		}

		//check client activ=1
		if(true !== ($ret = $this->checkClient($cod_cl)))
		{ UploadXls::send_message($serverTime, 'Error client : '.$ret, 0, 2); exit(0); }

		$firstLine = array('destinatar','destloc','destjud','destadresa','destpers','desttel','platitor','tipexp','bucati','greutate','asigurare','ramburs','tipramburs','returnt','returdoc','livrare','detaliidoc','obs','nt','deschiderecolet');
		if($sw_cl == 2) {
			$firstLine = array('expeditor','exploc','expjud','expadresa','exppers','exptel','platitor','tipexp','bucati','greutate','asigurare','ramburs','tipramburs','returnt','returdoc','livrare','detaliidoc','obs','nt','deschiderecolet');
		}

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

			if($tip == "csv")
			{
				$firstLine = $worksheet->rangeToArray('A1:' . $highestColumn . 1, "", false, false);
				$firstLine = $firstLine[0];
			}
			$colsMap = array_combine($firstLine, $this->excelRange($nbhighestColumn));
			if($colsMap === false)
				throw new Exception("numar de coloane gresit : array_combine");

			$rowIterator = $worksheet->getRowIterator();

			$proc = 1;
			if($highestRow <=10) $proc = 10;
			else if($highestRow <=100) $proc = 1;
			else if($highestRow <=1000) $proc = 0.1;
			else if($highestRow <=10000) $proc = 0.01;
		}
		catch(Exception $e) {
			UploadXls::send_message($serverTime, 'Error PHPExcel : '.$e->getMessage(), 0, 2);
			exit(0);
		}

		//client
		$query_cl = "SELECT cl.master as master_id, cl.nume as cl_nume, cl.cod_lc as cl_localitate_id, lc.dist_km as cl_km_ext,
			lc.cod_centru as cl_centru_id, cl.adresa as cl_adresa, cl.cc as cl_cc, cl.mod_plata as cl_mod_plata, cl.icc as cl_icc,
			clm.cc as master_cc, clm.mod_plata as master_mod_plata, clm.activ as master_activ,
			lc.nume_lc as cl_localitate, ce.nume as cl_centru
			FROM clienti cl
			LEFT JOIN clienti clm on cl.master = clm.cod_cl
			LEFT JOIN localitati lc ON cl.cod_lc = lc.cod_lc
			LEFT JOIN centre ce ON lc.cod_centru = ce.id
			WHERE cl.activ = 1 and cl.sters = 0 and cl.cod_cl=".$cod_cl;

		$sql_cl = $this->db->QFetchArray($query_cl);
		if(empty($sql_cl)) { UploadXls::send_message($serverTime, 'Client : '.$cod_cl.' not found', 0, 2); exit(0);}

		foreach($rowIterator as $row){
			try {
				if(1 == $row->getRowIndex ()) continue;//skip first row
				$rowIndex = $row->getRowIndex();
				$error = '';

				//exceptii localitate, judet
				if(in_array($this->mapCellHeader('exploc', $cod_cl, $tip), $firstLine)){
					$m_localitate = $worksheet->getCell($colsMap[$this->mapCellHeader('exploc', $cod_cl, $tip)].$rowIndex)->getValue();
					$m_localitate = (!empty(Backend::sSanitizeCleanEdges($m_localitate ?? "")) ? strtoupper(Backend::sSanitizeCleanEdges($m_localitate)):"");
				}
				else if(in_array($this->mapCellHeader('destloc', $cod_cl, $tip), $firstLine)){
					$m_localitate = $worksheet->getCell($colsMap[$this->mapCellHeader('destloc', $cod_cl, $tip)].$rowIndex)->getValue();
					$m_localitate = (!empty(Backend::sSanitizeCleanEdges($m_localitate ?? "")) ? strtoupper(Backend::sSanitizeCleanEdges($m_localitate)):"");
				}
				else {
							$error .= "error la linia : ".$rowIndex."  localitate not found : import failed";
							UploadXls::send_message($serverTime, $error, round($rowIndex*$proc), 1);
							continue;
				}
				if(in_array($this->mapCellHeader('expjud', $cod_cl, $tip), $firstLine)){
					$m_judet = $worksheet->getCell($colsMap[$this->mapCellHeader('expjud', $cod_cl, $tip)].$rowIndex)->getValue();
					$m_judet = (!empty(Backend::sSanitizeCleanEdges($m_judet ?? "")) ? strtoupper(Backend::sSanitizeCleanEdges($m_judet)):"");
				}
				else if(in_array($this->mapCellHeader('destjud', $cod_cl, $tip),$firstLine)){
					$m_judet = $worksheet->getCell($colsMap[$this->mapCellHeader('destjud', $cod_cl, $tip)].$rowIndex)->getValue();
					$m_judet = (!empty(Backend::sSanitizeCleanEdges($m_judet ?? "")) ? strtoupper(Backend::sSanitizeCleanEdges($m_judet)):"");
				}
				else {
							$error .= "error la linia : ".$rowIndex."  judet not found : import failed";
							UploadXls::send_message($serverTime, $error, round($rowIndex*$proc), 1);
							continue;
				}
				$test_m_localitate = $this->testLocalitate($m_localitate);
				if($test_m_localitate == 'Bucuresti')
				{
					$query="select l.cod_lc, l.nume_lc, l.dist_km, l.cod_centru, ce.nume as centru_nume
						from {$this->tables['localitati']} l
						left join centre ce on l.cod_centru = ce.id
						where l.nume_lc like :test_m_localitate and l.cod_jd like 'B'
						limit 1";
					$sql = $this->db->QFetchRowAssoc($query, ['test_m_localitate'=>$test_m_localitate]);
					if(empty($sql)) { $error .= "error la linia : ".$rowIndex." : localitate eronata : ".$m_localitate." : import failed";  UploadXls::send_message($serverTime, $error, round($rowIndex*$proc), 1); continue; }
					else
					{
						$m_localitate = $sql['nume_lc'];
						$m_localitate_id = $sql['cod_lc'];
						$m_centru_id = $sql['cod_centru'];
						$m_centru = $sql['centru_nume'];
						$m_km_ext = $sql['dist_km'];
					}
				}
				else
				{
					//cauta judetul
					$query="select cod_jd from {$this->tables['judete']} where replace(replace(nume_jd,' ',''),'-','') like replace(replace(:m_judet,' ',''),'-','') limit 1";
					$sql = $this->db->QFetchRowAssoc($query, ['m_judet'=>$m_judet]);
					if(empty($sql)) {  $error .= "error la linia : ".$rowIndex."  : judet inexistent : ".$m_judet." : import failed";  UploadXls::send_message($serverTime, $error, round($rowIndex*$proc), 1); continue; }
					else $m_judet_id = $sql['cod_jd'];
					//cauta localitatea
					$query="select l.cod_lc, l.nume_lc, l.dist_km, l.cod_centru, ce.nume as centru_nume
						from {$this->tables['localitati']} l
						left join centre ce on l.cod_centru = ce.id
						where replace(replace(l.nume_lc,' ',''),'-','') like replace(replace(:m_localitate,' ',''),'-','')  and l.cod_jd like :judet limit 1";
					$sql = $this->db->QFetchRowAssoc($query, ['m_localitate'=>$m_localitate, 'judet'=>$m_judet_id]);
					if(empty($sql)) {  $error .= "error la linia : ".$rowIndex."  : localitate eronata : ".$m_localitate." : import failed";  UploadXls::send_message($serverTime, $error, round($rowIndex*$proc), 1); continue; }
					else
					{
						$m_localitate = $sql['nume_lc'];
						$m_localitate_id = $sql['cod_lc'];
						$m_centru_id = $sql['cod_centru'];
						$m_centru = $sql['centru_nume'];
						$m_km_ext = $sql['dist_km'];
					}
				}

				$m_platitor = ($sw_cl == 1) ? 1 : 2;
				if(in_array($this->mapCellHeader('platitor', $cod_cl, $tip),$firstLine))
				{
					$platitor = strtoupper($this->sanitize($worksheet->getCell($colsMap[$this->mapCellHeader('platitor', $cod_cl, $tip)].$rowIndex)->getValue()));
					if($platitor == 'DESTINATAR')
						$m_platitor = 2;
				}

				$m_contact = '';
				$m_telefon = '';
				$m_nume = '';
				$m_adresa = '';
				if(in_array($this->mapCellHeader('exppers', $cod_cl, $tip), $firstLine)){
					$m_contact = $worksheet->getCell($colsMap[$this->mapCellHeader('exppers', $cod_cl, $tip)].$rowIndex)->getValue();
					$m_contact = (!empty(Backend::sSanitizeCleanEdges($m_contact ?? "")) ? strtoupper(Backend::sSanitizeCleanEdges($m_contact)):"");
				}
				else if(in_array($this->mapCellHeader('destpers', $cod_cl, $tip), $firstLine)){
					$m_contact = $worksheet->getCell($colsMap[$this->mapCellHeader('destpers', $cod_cl, $tip)].$rowIndex)->getValue();
					$m_contact = (!empty(Backend::sSanitizeCleanEdges($m_contact ?? "")) ? strtoupper(Backend::sSanitizeCleanEdges($m_contact)):"");
				}
				if(in_array($this->mapCellHeader('exptel', $cod_cl, $tip),$firstLine))
					$m_telefon = strtoupper($this->sanitize($worksheet->getCell($colsMap[$this->mapCellHeader('exptel', $cod_cl, $tip)].$rowIndex)->getValue()));
				else if(in_array($this->mapCellHeader('desttel', $cod_cl, $tip),$firstLine))
					$m_telefon = strtoupper($this->sanitize($worksheet->getCell($colsMap[$this->mapCellHeader('desttel', $cod_cl, $tip)].$rowIndex)->getValue()));

				//cauta m_client in tabla clienti
				if(in_array($this->mapCellHeader('expeditor', $cod_cl, $tip), $firstLine)){
					$m_nume = $worksheet->getCell($colsMap[$this->mapCellHeader('expeditor', $cod_cl, $tip)].$rowIndex)->getValue();
					$m_nume = (!empty(Backend::sSanitizeCleanEdges($m_nume ?? "")) ? strtoupper(Backend::sSanitizeCleanEdges($m_nume)):"");
				}
				else if(in_array($this->mapCellHeader('destinatar', $cod_cl, $tip),$firstLine)){
					$m_nume = $worksheet->getCell($colsMap[$this->mapCellHeader('destinatar', $cod_cl, $tip)].$rowIndex)->getValue();
					$m_nume = (!empty(Backend::sSanitizeCleanEdges($m_nume ?? "")) ? strtoupper(Backend::sSanitizeCleanEdges($m_nume)):"");
				}
				else {
					$error .= "error la linia : ".$rowIndex."  expeditor/destinatar not found : import failed";
					UploadXls::send_message($serverTime, $error, round($rowIndex*$proc), 1);
					continue;
				}
				if(in_array($this->mapCellHeader('expadresa', $cod_cl, $tip),$firstLine)){
					$m_adresa = $worksheet->getCell($colsMap[$this->mapCellHeader('expadresa', $cod_cl, $tip)].$rowIndex)->getValue();
					$m_adresa = (!empty(Backend::sSanitizeCleanEdges($m_adresa ?? "")) ? strtoupper(Backend::sSanitizeCleanEdges($m_adresa)):"");
				}
				else if(in_array($this->mapCellHeader('destadresa', $cod_cl, $tip),$firstLine)){
					$m_adresa = $worksheet->getCell($colsMap[$this->mapCellHeader('destadresa', $cod_cl, $tip)].$rowIndex)->getValue();
					$m_adresa = (!empty(Backend::sSanitizeCleanEdges($m_adresa ?? "")) ? strtoupper(Backend::sSanitizeCleanEdges($m_adresa)):"");
				}
				else {
					$error .= "error la linia : ".$rowIndex."  adresa not found : import failed";
					UploadXls::send_message($serverTime, $error, round($rowIndex*$proc), 1);
					continue;
				}

				$m_id = 0;
				$m_query = "select cod_cl from {$this->tables['clienti']} where nume like :m_nume
					and adresa like :m_adresa and cod_lc = ".$m_localitate_id." limit 1";
				$m_sql = $this->db->QFetchRowAssoc($m_query, ['m_nume'=>$m_nume, 'm_adresa'=>$m_adresa]);
				if (empty($m_sql)) {//in cazul in care nu exista clientul => insert
					//detalii
					$var = [];
					$var['nume'] = $m_nume;
					$var['contact'] = $m_contact;
					$var['telefon'] = $m_telefon;
					$var['cod_lc'] = $m_localitate_id;
					$var['adresa'] = $m_adresa;
					$var['operator'] = $this->user_id;
					$var['activ'] = 1;
					$var['data_op'] = date('Y-m-d H:i:s');
					$var['tarif'] = 0;
					$var['mod_plata'] = 0;
					$var['km_ext'] = $m_km_ext;

					$var['created_at'] = date('Y-m-d H:i:s');
					$var['created_by'] = $this->user_id;
					$m_id = $this->db->QueryInsert($this->tables['clienti'], $var);
					CdsGeocoder::geocode($this->db, $m_id);
					unset($var);
				}
				else {
					$m_id = $m_sql['cod_cl'];
					CdsGeocoder::geocode($this->db, $m_id, true);
				}

				$m_tip_obj = 1;
				if(in_array($this->mapCellHeader('tipexp', $cod_cl, $tip),$firstLine)){
					$tip_obj = $this->sanitize($worksheet->getCell($colsMap[$this->mapCellHeader('tipexp', $cod_cl, $tip)].$rowIndex)->getValue());
					if(!empty($tip_obj)) $m_tip_obj = match(strtoupper($tip_obj)) {	
						'PLIC' => 1,
						'COLET' => 2,
						'PALET' => 3,
						default => 1
					};
				}

				$m_piese = 0;
				if($m_tip_obj == 1 || $m_tip_obj == 3)
					$m_piese = 1;
				else {
					if(in_array($this->mapCellHeader('bucati', $cod_cl, $tip),$firstLine)){
						$m_piese = intval($this->sanitize($worksheet->getCell($colsMap[$this->mapCellHeader('bucati', $cod_cl, $tip)].$rowIndex)->getValue()));
					if(empty($m_piese)) {  $error .= "error la linia : ".$rowIndex."  : colet | palet valoare eronata : ".$m_piese." : import failed"; UploadXls::send_message($serverTime, $error, round($rowIndex*$proc), 1); continue; }}
				}
				$m_greutate = 0;
				if($m_tip_obj == 1)
					$m_greutate = 0.5;
				else {
					if(in_array($this->mapCellHeader('greutate', $cod_cl, $tip),$firstLine))
						$m_greutate = floatval($this->sanitize($worksheet->getCell($colsMap[$this->mapCellHeader('greutate', $cod_cl, $tip)].$rowIndex)->getValue()));
					if(empty($m_greutate)) {  $error .= "error la linia : ".$rowIndex."  : greutate eronata : ".$m_greutate." : import failed"; UploadXls::send_message($serverTime, $error, round($rowIndex*$proc), 1); continue; }
				}

				if($m_tip_obj == 2 && $m_greutate < parent::MIN_KG_COLET){
					$m_greutate = parent::MIN_KG_COLET;
				}
				else if($m_tip_obj == 3 && $m_greutate < parent::MIN_KG_PALET){
					$m_greutate = parent::MIN_KG_PALET;
				}

				//retururi
				$m_ret_nt = 0;
				if(in_array($this->mapCellHeader('returnt', $cod_cl, $tip),$firstLine)){
					$ret_nt = $this->sanitize($worksheet->getCell($colsMap[$this->mapCellHeader('returnt', $cod_cl, $tip)].$rowIndex)->getValue());
					if(!empty($ret_nt)) {
						$ret_nt = strtoupper($ret_nt);
						if($ret_nt=='DA' || $ret_nt=='TRUE') $m_ret_nt = 1;
					}
				}
				$m_ret_doc = 0;
				if(in_array($this->mapCellHeader('returdoc', $cod_cl, $tip),$firstLine)){
					$ret_doc = $this->sanitize($worksheet->getCell($colsMap[$this->mapCellHeader('returdoc', $cod_cl, $tip)].$rowIndex)->getValue());
					if(!empty($ret_doc)) {
						$ret_doc = strtoupper($ret_doc);
						if($ret_doc =='DA' || $ret_doc =='TRUE') $m_ret_doc = 1;
					}
				}
				$m_ret_amb = 0;
				if(in_array($this->mapCellHeader('returamb', $cod_cl, $tip),$firstLine)){
					$ret_amb = $this->sanitize($worksheet->getCell($colsMap[$this->mapCellHeader('returamb', $cod_cl, $tip)].$rowIndex)->getValue());
					if(!empty($ret_amb)) {
						$ret_amb = strtoupper($ret_amb);
						if($ret_amb =='DA' || $ret_amb =='TRUE') $m_ret_amb = 1;
					}
				}
				$m_copen = 0;
				if(in_array($this->mapCellHeader('deschiderecolet', $cod_cl, $tip),$firstLine)){
					$m_copen = $this->sanitize($worksheet->getCell($colsMap[$this->mapCellHeader('deschiderecolet', $cod_cl, $tip)].$rowIndex)->getValue());
					if(!empty($m_copen)) {
						$m_copen = strtoupper($m_copen);
						if($m_copen =='DA' || $m_copen =='TRUE') $m_copen = 1;
					}
				}
				$m_sms = 0;
				if(in_array($this->mapCellHeader('sms', $cod_cl, $tip),$firstLine)){
					$m_sms = $this->sanitize($worksheet->getCell($colsMap[$this->mapCellHeader('sms', $cod_cl, $tip)].$rowIndex)->getValue());
					if(!empty($m_sms)) {
						$m_sms = strtoupper($m_sms);
						if($m_sms =='DA' || $m_sms =='TRUE') $m_sms = -1;
					}
				}

				$m_liv_sambata = 0;
				$m_liv_sediu=0;
				if(in_array($this->mapCellHeader('livrare', $cod_cl, $tip),$firstLine)){
					$livrare = $this->sanitize($worksheet->getCell($colsMap[$this->mapCellHeader('livrare', $cod_cl, $tip)].$rowIndex)->getValue());
					if(!empty($livrare)) {
						$livrare = strtoupper($livrare);
						if($livrare=='SAMBATA') $m_liv_sambata = 1;
						else if($livrare=='SEDIU') $ $m_liv_sediu = 1;
					}
				}

				//asigurare
				$m_asigurare = 0.00;
				if(in_array($this->mapCellHeader('asigurare', $cod_cl, $tip),$firstLine))
				{
					$asigurare = $this->sanitize($worksheet->getCell($colsMap[$this->mapCellHeader('asigurare', $cod_cl, $tip)].$rowIndex)->getValue());
					$asigurare = floatval(str_replace(',', '.', $asigurare));
					if(!empty($asigurare)) $m_asigurare = round($asigurare,2);
				}

				//ramburs
				$m_ramburs = 0.00;
				$m_tip_plata = 0;
				if(in_array($this->mapCellHeader('ramburs', $cod_cl, $tip),$firstLine))
				{
					$ramburs = $this->sanitize($worksheet->getCell($colsMap[$this->mapCellHeader('ramburs', $cod_cl, $tip)].$rowIndex)->getValue());
					$ramburs = floatval(str_replace(',', '.', $ramburs));
					if(!empty($ramburs)) {
						$m_ramburs = round($ramburs, 2);
						if($m_platitor == 1 && $sw_cl == 1) {
							if($this->isPunctDeLucru($cod_cl, $sql['master_id'])) {
								$m_tip_plata = !empty((!empty($sql_cl['cl_icc']) ? $sql_cl['cl_cc'] : $sql_cl['master_cc']) == 1) ? 3:0;
							}
							else
								$m_tip_plata = !empty($sql_cl['cl_cc']) ? 3:0;
						}
						if(in_array($this->mapCellHeader('tipramburs', $cod_cl, $tip),$firstLine)){
							$tip_plata = $this->sanitize($worksheet->getCell($colsMap[$this->mapCellHeader('tipramburs', $cod_cl, $tip)].$rowIndex)->getValue());
							if(!empty($tip_plata)){
								$tip_plata = strtoupper($tip_plata);
								if($tip_plata=='BO') $m_tip_plata = 1;
								else if($tip_plata=='CEC') $m_tip_plata = 2;
							}
						}
					}
				}

				if(in_array($this->mapCellHeader('detaliidoc', $cod_cl, $tip),$firstLine))
					$m_detalii_doc = Backend::sSanitizeCleanEdges($worksheet->getCell($colsMap[$this->mapCellHeader('detaliidoc', $cod_cl, $tip)].$rowIndex)->getValue() ?? '');
				if(in_array($this->mapCellHeader('obs', $cod_cl, $tip),$firstLine))
					$m_observatii = Backend::sSanitizeCleanEdges($worksheet->getCell($colsMap[$this->mapCellHeader('obs', $cod_cl, $tip)].$rowIndex)->getValue() ?? '');

				$m_expeditie = 0;
				if(in_array($this->mapCellHeader('nt', $cod_cl, $tip),$firstLine)){
					$mcodbara = intval($this->sanitize($worksheet->getCell($colsMap[$this->mapCellHeader('nt', $cod_cl, $tip)].$rowIndex)->getValue()));
					if(empty($mcodbara))
						$m_expeditie = $this->GenerareNrExpeditie();
					else {
						$m_expeditie = intval($mcodbara);
					}
				}
				if(empty($m_expeditie) || (true !== ($ret = $this->checkCodBara($m_expeditie, $cod_cl))))
				{
					$error .= "error : Expeditia ".$m_expeditie." ".$ret." : import failed";
					UploadXls::send_message($serverTime, $error, round($rowIndex*$proc), 1);
					continue;
				}

				//source
				$m_src = 9;
				$today = new \DateTime("now");

				//istoric expeditii
				$m_ist_id = $this->insertIstExp(null, 1);

				$vi = [];
				$exp_prel = [];
				$exp_prel['src'] = $m_src;

				$exp_prel['expeditie'] = $m_expeditie;
				$exp_prel['operator_id'] = $this->user_id;
				$exp_prel['operatiune'] = "Colectata";
				$exp_prel['data'] = $today->format('Y-m-d H:i:s');
				$exp_prel['data_expeditie'] = $exp_prel['data_operatie'] = $today->format('Y-m-d');

				$vi['expeditor_id'] = $exp_prel['expeditor_id'] = ($sw_cl == 2) ? $m_id : $cod_cl;
				$vi['expeditor_localitate_id'] = ($sw_cl == 2) ? $m_localitate_id : $sql_cl['cl_localitate_id'];
				$exp_prel['expeditor_contact'] = ($sw_cl == 2) ? $m_contact : '';
				$exp_prel['expeditor_telefon'] = ($sw_cl == 2) ? $m_telefon : '';

				$vi['destinatar_id'] = $exp_prel['destinatar_id'] = ($sw_cl == 1) ? $m_id : $cod_cl;
				$vi['destinatar_localitate_id'] = ($sw_cl == 1) ? $m_localitate_id : $sql_cl['cl_localitate_id'];
				$exp_prel['destinatar_contact'] = ($sw_cl == 1) ? $m_contact : '';
				$exp_prel['destinatar_telefon'] = ($sw_cl == 1) ? $m_telefon : '';

				$vi['platitor_id'] = $exp_prel['platitor_id'] = ($m_platitor == 1) ? $vi['expeditor_id'] : $vi['destinatar_id'];
				$exp_prel['platitor_localitate_id'] = ($m_platitor == 1) ? $vi['expeditor_localitate_id'] : $vi['destinatar_localitate_id'];

				$vi['tip_exp'] = $exp_prel['tip_exp'] = 0;
				$exp_prel['mod_plata'] = ($m_platitor == 1 && $sw_cl == 1 || $m_platitor == 2 && $sw_cl == 2) ? $sql_cl['cl_mod_plata'] : 0;

				$exp_prel['piese'] = $exp_prel['plicuri'] = $exp_prel['colete'] = $exp_prel['paleti'] = 0;
				if($m_tip_obj == 1) $exp_prel['piese'] = $exp_prel['plicuri'] = 1;
				else if($m_tip_obj == 2) $exp_prel['piese'] = $exp_prel['colete'] = $m_piese;
				else if($m_tip_obj == 3) $exp_prel['piese'] = $exp_prel['paleti'] = 1;
				$vi['tip_obj'] = $m_tip_obj;
				$vi['greutate'] = $exp_prel['greutate'] = $m_greutate;

				$vi['ret_nt'] = $exp_prel['ret_nt'] = $m_ret_nt;
				$vi['ret_doc'] = $exp_prel['ret_doc'] = $m_ret_doc;
				$vi['liv_sambata'] = $exp_prel['liv_samb'] = $m_liv_sambata;
				$vi['liv_sediu'] = $exp_prel['liv_sed'] = $m_liv_sediu;
				$vi['ret_amb'] = $exp_prel['ret_amb'] = $m_ret_amb;
				$vi['sms'] = $exp_prel['sms'] = $m_sms;
				$vi['copen'] = $exp_prel['copen'] = $m_copen;

				$vi['km_preluare'] = $exp_prel['km_preluare'] = ($sw_cl == 2) ? intval(round(floatval($m_km_ext))) : intval(round(floatval($sql_cl['cl_km_ext'])));
				$vi['km_livrare'] = $exp_prel['km_livrare'] = ($sw_cl == 1) ? intval(round(floatval($m_km_ext))) : intval(round(floatval($sql_cl['cl_km_ext'])));

				$vi['valoare_asigurata'] = $exp_prel['valoare_asigurata'] = round($m_asigurare, 2);
				$vi['ramburs'] = $exp_prel['ramburs'] = round($m_ramburs, 2);
				$vi['tip_plata'] = $exp_prel['tip_plata'] = $m_tip_plata;
				$exp_prel['status_ramburs'] = 0;

				$exp_prel['observatii'] = $m_observatii;
				$exp_prel['detalii_doc'] = $m_detalii_doc;

				$vi['tip_tarif'] = (($vi['expeditor_localitate_id'] != $vi['destinatar_localitate_id']) ? 1 : 0);
				$valoare = $this->Get_ValoareExpeditie($vi);

				$exp_prel['moneda'] = $valoare['moneda'];

				$exp_prel['valoare_expeditie'] = $valoare['tExpeditie'];
				$exp_prel['val_greutate'] = $valoare['tGreutate'];
				$exp_prel['val_km'] = $valoare['tKm'];
				$exp_prel['ramburs_procent'] = $valoare['procRamburs'];
				$exp_prel['val_asig'] = $valoare['tAsigurare'] + $valoare['tRamburs'];
				$exp_prel['procent_asigurare'] = $valoare['procAsigurare'];
				$exp_prel['procTva'] = $this->procTva;
				$exp_prel['valoare_totala_expeditie'] = $exp_prel['val_greutate'] + $exp_prel['val_km'] + $exp_prel['val_asig'] + $exp_prel['valoare_expeditie'];
				$exp_prel['valoare_totala_expeditie'] = round($exp_prel['valoare_totala_expeditie'],2);
				$exp_prel['tva'] = $exp_prel['valoare_totala_expeditie'] * $this->procTva / 100;
				$exp_prel['tva'] = round($exp_prel['tva'], 2);

				if($debug)
					$exp_prel['anulata'] = 1;

				$exp_prel['created_at'] = date('Y-m-d H:i:s');
				$exp_prel['created_by'] = $this->user_id;
				$m_cod_expeditie = $this->db->QueryInsert($this->tables['exp_prelucrate'], $exp_prel);
				if($m_ist_id != null)
					$this->db->QueryUpdate($this->tables['ist_exp'], ['cod_exp' => $m_cod_expeditie], "cod_ist = ".$m_ist_id);

				UploadXls::send_message($serverTime, 'Expeditia '.$m_expeditie.' importata cu succes', round($rowIndex*$proc));
			}
			catch(Exception $e) {
				UploadXls::send_message($serverTime, 'error at line '.$row->getRowIndex().' : '.$e->getMessage(), round($rowIndex*$proc), 1);
			}
		}
		UploadXls::send_message($serverTime, '', 100, 3);
		exit(0);
	}

	function insertPf($rowIndex, $nume, $localitate, $judet, $adresa, $debug)
	{
		$test_m_localitate = $this->testLocalitate($localitate);
		if($test_m_localitate == 'Bucuresti')
		{
			$query="select l.cod_lc, l.nume_lc, l.dist_km, l.cod_centru, ce.nume as centru_nume
				from localitati l
				left join centre ce on l.cod_centru = ce.id
				where l.nume_lc like :test_m_localitate and l.cod_jd like 'B'
				limit 1";
			$sql = $this->db->QFetchRowAssoc($query, ['test_m_localitate'=>$test_m_localitate]);
			if(empty($sql))
			{
				echo "error la linia : ".$rowIndex." : localitate eronata : ".$localitate."<br/>";
				return -1;
			}
			else
			{
				$m_localitate = $sql['nume_lc'];
				$m_localitate_id = $sql['cod_lc'];
				$m_centru_id = $sql['cod_centru'];
				$m_centru = $sql['centru_nume'];
				$m_km_ext = $sql['dist_km'];
			}
		}
		else
		{
			//cauta judetul
			$query="select cod_jd from {$this->tables['judete']} where replace(replace(nume_jd,' ',''),'-','') like replace(replace(:m_judet,' ',''),'-','') limit 1";
			$sql = $this->db->QFetchRowAssoc($query, ['m_judet'=>$judet]);
			if(empty($sql)) {
				echo "error la linia : ".$rowIndex."  : judet inexistent : ".$judet." : import failed"."<br/>";
				return -1;
			}
			else $m_judet_id = $sql['cod_jd'];
			//cauta localitatea
			$query="select l.cod_lc, l.nume_lc, l.dist_km, l.cod_centru, ce.nume as centru_nume
				from {$this->tables['localitati']} l
				left join centre ce on l.cod_centru = ce.id
				where replace(replace(l.nume_lc,' ',''),'-','') like replace(replace(:m_localitate,' ',''),'-','')  and l.cod_jd like :judet limit 1";
			$sql = $this->db->QFetchRowAssoc($query, ['m_localitate'=>$localitate, 'judet'=>$m_judet_id]);
			if(empty($sql)) {
				echo "error la linia : ".$rowIndex."  : localitate eronata : ".$localitate." : import failed"."<br/>";
				return -1;
			}
			else
			{
				$m_localitate = $sql['nume_lc'];
				$m_localitate_id = $sql['cod_lc'];
				$m_centru_id = $sql['cod_centru'];
				$m_centru = $sql['centru_nume'];
				$m_km_ext = $sql['dist_km'];
			}
		}
		$m_id = -1;
		$m_query = "select cod_cl from clienti where nume like :m_nume
			and adresa like :m_adresa and cod_lc = ".$m_localitate_id." and tarif = 0 and mod_plata = 0 limit 1";
		$m_sql = $this->db->QFetchRowAssoc($m_query, ['m_nume'=>$nume, 'm_adresa'=>$adresa]);
		if (empty($m_sql)) {//in cazul in care nu exista clientul => insert
			//detalii
			$var = [];
			$var['nume'] = $nume;
			$var['cod_lc'] = $m_localitate_id;
			$var['adresa'] = $adresa;
			$var['operator'] = $this->user_id;
			$var['activ'] = 1;
			$var['data_op'] = date('Y-m-d H:i:s');
			$var['tarif'] = 0;
			$var['mod_plata'] = 0;

			$var['created_at'] = date('Y-m-d H:i:s');
			$var['created_by'] = $this->user_id;
			$m_id = $this->db->QueryInsert($this->tables['clienti'], $var);
			CdsGeocoder::geocode($this->db, $m_id);
			unset($var);
		}
		else $m_id = $m_sql['cod_cl'];
		return $m_id;
	}
}
