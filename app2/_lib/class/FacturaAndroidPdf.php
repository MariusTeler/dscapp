<?php

require_once __DIR__."/../../vendor/tecnickcom/tcpdf/tcpdf.php";
require_once "ConstantsPdf.php";

class FacturaAndroidPdf extends TCPDF
{

	private $vars;
	const LOGO_PATH = __DIR__.'/../../public/assets/images/logo.jpg';
	
	function __construct($vars = []) {
    	parent::__construct('P', 'mm', 'A4', false, 'ISO-8859-1', false);
        $this->vars = $vars;
        $format = array(ConstantsPdf::IL100,ConstantsPdf::IL150);
        $this->setPageFormat($format, 'P');
		// set document information
		$this->SetCreator(PDF_CREATOR);
		$this->SetAuthor('Curier Dragon Star');
		$this->SetTitle('Factura');
		$this->SetSubject('Factura');
		$this->SetKeywords('curierat, dragon, star, PDF');

		// remove default header/footer
		$this->setPrintHeader(false);
		$this->setPrintFooter(true);
	
		// set default monospaced font
		$this->SetDefaultMonospacedFont('courier');
		$this->setFontSubsetting(false);

		// set margins
		$this->SetMargins(5, 5, 10, true);
		
		// set auto page breaks
		$this->SetAutoPageBreak(false, 10);

		// set image scale factor
		$this->setImageScale(1.25);
	
		// define barcode style
		$this->pdf_style = array(
    		'position' => 'C',
    		'align' => 'C',
 		   	'stretch' => false,
 		   	'fitwidth' => true,
    		'cellfitalign' => '',
    		'border' => false,
    		'hpadding' => 'auto',
    		'vpadding' => 'auto',
    		'fgcolor' => array(0,0,0),
    		'bgcolor' => false,
    		'text' => true,
    		'font' => 'helvetica',
    		'fontsize' => 8,
    		'stretchtext' => 4
		);
		
		// set font
		$this->SetFont('times', '', 9);

		// set cell padding
		$this->setCellPaddings(0.5, 0.5, 0.5, 0.5);

		// set cell margins
		$this->setCellMargins(0, 0, 0, 0);

		// set color for background
		$this->SetFillColor(255, 255, 255);

		$this->SetLineWidth(0.4);
    }
    
    public function setVars($vars){
		$this->vars = $vars;
	}

	// Page footer
    public function Footer() {
        if($this->getPage() == 1 && !empty($this->vars['isFactura'])) {
			$this->Footer1();
		}
		else {
			$this->Footer2();
		}
	}
	
	private function Footer1() {
        // Position at 15 mm from bottom
        $this->SetY(-22);
        //Numar pagini, Pozitii facturate
		$this->setFont('times','', 8, '');
		$agent = "Emis de: ".$this->vars['agent'];
		$this->MultiCell(90, 4, $agent, '', 'C', 0, 1, '', '', true, 0, false, false, 4, 'T', true);
		$this->MultiCell(45, 4, "Numar pagini: 1", '', 'L', 0, 0, '', '', true, 0, false, false, 4, 'T', true);
		$this->MultiCell(45, 4, "Pozitii facturate: 1", '', 'R', 0, 1, '', '', true, 0, false, false, 4, 'T', true);
		
		$this->SetLineStyle(array('width' => 0.2, 'cap' => 'butt', 'join' => 'miter', 'dash' => 2, 'color' => array(0, 0, 0)));
        $p_end = "Centru DSC:  " .$this->vars['centru']. "  /  Tiparit:  " . $this->vars['dataInc'];
        $this->MultiCell(90, 4, $p_end, 'T', 'C', 0, 1, '', '', true, 0, false, false, 4, 'M', true);
	}

	private function Footer2() {
        // Position at 15 mm from bottom
        $this->SetY(-15);
        //Numar pagini, Pozitii facturate
		$this->setFont('times','', 8, '');
		$agent = "Emis de: ".$this->vars['agent'];
		$this->MultiCell(90, 4, $agent, '', 'C', 0, 1, '', '', true, 0, false, false, 4, 'T', true);
		$this->SetLineStyle(array('width' => 0.2, 'cap' => 'butt', 'join' => 'miter', 'dash' => 2, 'color' => array(0, 0, 0)));
        $p_end = "Centru DSC:  " .$this->vars['centru']. "  /  Tiparit:  " . $this->vars['dataInc'];
        $this->MultiCell(90, 4, $p_end, 'T', 'C', 0, 1, '', '', true, 0, false, false, 4, 'M', true);
	}
	
	public function makeFacturaAndroid(){
		$f_data = new DateTime($this->vars['dataInc']);
		$f_data = $f_data->format('d.m.Y');

        $cds_title = "S. C. DSC EXPRES LOGISTIC S.R.L.";
		$cds_adresa = "Calea Bucuresti nr. 1, Otopeni, Ilfov 075100".PHP_EOL
				. "capital social: 5500 LEI".PHP_EOL
                ."O.R.C.: J23 /402 /2016 - C.U.I.: RO29255819".PHP_EOL
                ."TEL.: 021-9501, comenzi@curierdragonstar.ro";
				
		$this->setFont('times','B', 9, '');
		$this->MultiCell(90, 12, "FACTURA FISCALA" . PHP_EOL . 
			"Seria: ". substr($this->vars['factura'],0,5).
			" Numar: " . $this->vars['factura']. PHP_EOL . 
			"din data: " . $f_data, 0, 'C', 0, 1, '', '', true, 0, false, false, 12, 'T', true);

		//barcode
		$this->write1DBarcode(strval($this->vars['factura']), 'C128A', '', '', '', 18, 0.4, $this->pdf_style, 'N');
		
        $this->setFont('times','B', 9, '');
        $this->MultiCell(90, 5, $cds_title, 0, 'C', 0, 1, '', '', true, 0, false, false, 5, 'T', true);
        $this->setFont('times','', 9, '');
        $this->MultiCell(90, 16, $cds_adresa, 0, 'C', 0, 1, '', '', true, 0, false, false, 16, 'T', true);

		$this->setFont('times','B', 9, '');
        $beneficiar = "Beneficiar: " . $this->vars['client'];
        $this->MultiCell(90, 5, $beneficiar, 0, 'C', 0, 1, '', '', true, 0, false, false, 5, 'T', true);

        $this->setFont('times','', 9, '');
		if(!empty($this->vars['adresa'])) 
			$this->MultiCell(90, 8, $this->vars['adresa'], 0, 'C', 0, 1, '', '', true, 0, false, false, 8, 'T', true);

        $this->setFont('times','B', 9, '');
        if(!empty($this->vars['cui'])) 
            $this->MultiCell(90, 5, "C.U.I.: " . $this->vars['cui'], 0, 'C', 0, 1, '', '', true, 0, false, false, 5, 'T', true);

		$this->setFont('times','', 9, '');
		$servicii = "Prestari servicii curierat conform NT: " . PHP_EOL . $this->vars['expeditii'];
        $this->MultiCell(90, 20, $servicii, 0, 'C', 0, 1, '', '', true, 0, false, false, 20, 'T', true);

		//piese, pret, valoare
		$this->MultiCell(45, 4, "Cantitate:", '', '', 0, 0, '', '', true, 0, false, false, 4, 'M', true);
		$this->MultiCell(45, 4, "1", '', 'R', 0, 1, '', '', true, 0, false, false, 4, 'M', true);
		$tva = $this->vars['suma']/(100+$this->vars['proc_tva']) * $this->vars['proc_tva'];
		$valoare = $this->vars['suma'] - $tva;

		$this->MultiCell(45, 4, "Pret unitar (fara T.V.A):", '', '', 0, 0, '', '', true, 0, false, false, 4, 'M', true);
		$this->MultiCell(45, 4, number_format($valoare, 2) . " LEI", '', 'R', 0, 1, '', '', true, 0, false, false, 4, 'M', true);

		$this->MultiCell(45, 4, "Valoarea:", '', '', 0, 0, '', '', true, 0, false, false, 4, 'M', true);
		$this->MultiCell(45, 4, number_format($valoare, 2) . " LEI", '', 'R', 0, 1, '', '', true, 0, false, false, 4, 'M', true);

		
		$this->MultiCell(45, 4, "Valoarea T.V.A. ".$this->vars['proc_tva']."%:", '', '', 0, 0, '', '', true, 0, false, false, 4, 'M', true);
		$this->MultiCell(45, 4, number_format($tva, 2) . " LEI", '', 'R', 0, 1, '', '', true, 0, false, false, 4, 'M', true);

		$this->setFont('times','B', 9, '');
		$this->MultiCell(45, 5, "Total de plata:", 'T', '', 0, 0, '', '', true, 0, false, false, 5, 'B', true);
		$this->MultiCell(45, 5, number_format($this->vars['suma'], 2) . " LEI", 'T', 'R', 0, 1, '', '', true, 0, false, false, 5, 'B', true);
	}

	public function makeChitantaFiscalaAndroid(){
		$f_data = new DateTime($this->vars['dataInc']);
		$f_data = $f_data->format('d.m.Y');

        $cds_title = "S. C. DSC EXPRES LOGISTIC S.R.L.".PHP_EOL .
			 "Calea Bucuresti nr. 1, Otopeni, Ilfov 075100".PHP_EOL
                ."O.R.C.: J23 /402 /2016 - C.U.I.: RO29255819";

		
        $this->setFont('times','', 9, '');
        $this->MultiCell(90, 15, $cds_title, 0, 'C', 0, 1, '', '', true, 0, false, false, 15, 'T', true);
		
		$this->setFont('times','B', 9, '');
		$this->MultiCell(90, 15, "CHITANTA FISCALA" . PHP_EOL . 
			"Seria: ". substr($this->vars['factura'],0,5).
			" Numar: " . $this->vars['factura']. PHP_EOL . 
			"din data: " . $f_data, 0, 'C', 0, 1, '', '', true, 0, false, false, 15, 'T', true);

		$this->setFont('times','', 9, '');
        $beneficiar = "Am primit de la " . $this->vars['client'];
		$this->MultiCell(90, 5, $beneficiar, 0, 'C', 0, 1, '', '', true, 0, false, false, 5, 'T', true);

		if(!empty($this->vars['cui'])) 
            $this->MultiCell(90, 5, "C.U.I.: " . $this->vars['cui'], 0, 'C', 0, 1, '', '', true, 0, false, false, 5, 'T', true);

		if(!empty($this->vars['adresa'])) 
			$this->MultiCell(90, 8, "Adresa: ".$this->vars['adresa'], 0, 'C', 0, 1, '', '', true, 0, false, false, 8, 'T', true);
        
		$servicii = "suma de " . number_format($this->vars['suma'], 2) . " LEI, reprezentand contravaloare factura serie: "
			. substr($this->vars['factura'],0,5) . " numar: " .  $this->vars['factura'] . " din " . $f_data;
        $this->MultiCell(90, 20, $servicii, 0, 'C', 0, 1, '', '', true, 0, false, false, 20, 'T', true);

	}

	public function makeChitantaFiscalaClientAndroid(){
		$f_data = new DateTime($this->vars['dataInc']);
		$f_data = $f_data->format('d.m.Y');

        $cds_title = "S. C. DSC EXPRES LOGISTIC S.R.L.".PHP_EOL .
			 "Calea Bucuresti nr. 1, Otopeni, Ilfov 075100".PHP_EOL
                ."O.R.C.: J23 /402 /2016 - C.U.I.: RO29255819";

		
        $this->setFont('times','', 9, '');
        $this->MultiCell(90, 15, $cds_title, 0, 'C', 0, 1, '', '', true, 0, false, false, 15, 'T', true);
		
		$this->setFont('times','B', 9, '');
		$this->MultiCell(90, 15, "CHITANTA FISCALA" . PHP_EOL . 
			"Seria: C". sprintf('%05d', $this->vars['agent_id']).
			" Numar: " . $this->vars['chitanta']. PHP_EOL . 
			"din data: " . $f_data, 0, 'C', 0, 1, '', '', true, 0, false, false, 15, 'T', true);

		//barcode
		$this->write1DBarcode(strval($this->vars['chitanta']), 'C128A', '', '', '', 18, 0.4, $this->pdf_style, 'N');

		$this->setFont('times','', 9, '');
        $beneficiar = "Am primit de la " . $this->vars['client'];
		$this->MultiCell(90, 5, $beneficiar, 0, 'C', 0, 1, '', '', true, 0, false, false, 5, 'T', true);

		if(!empty($this->vars['cui'])) 
            $this->MultiCell(90, 5, "C.U.I.: " . $this->vars['cui'], 0, 'C', 0, 1, '', '', true, 0, false, false, 5, 'T', true);

		if(!empty($this->vars['adresa'])) 
			$this->MultiCell(90, 5, "in adresa: ".$this->vars['adresa'], 0, 'C', 0, 1, '', '', true, 0, false, false, 5, 'T', true);
        
		$this->setFont('times','B', 9, '');
		$servicii = " suma de " . number_format($this->vars['suma'], 2) . " LEI," . PHP_EOL . "reprezentand contravaloare facturi restante";
        $this->MultiCell(90, 20, $servicii, 0, 'C', 0, 1, '', '', true, 0, false, false, 20, 'T', true);

	}
}
?>