<?php

require_once __DIR__."/../../vendor/tecnickcom/tcpdf/tcpdf.php";
require_once "ConstantsPdf.php";

class ChRambursPdf extends TCPDF
{

	private $vars;
	const LOGO_PATH = __DIR__.'/../../public/assets/images/logo.jpg';
	public $pdf_style =	[];
	
	function __construct($vars = []) {
    	parent::__construct('P', 'mm', 'A4', false, 'ISO-8859-1', false);
        $this->vars = $vars;
        $format = array(ConstantsPdf::IL100,ConstantsPdf::IL150);
        $this->setPageFormat($format, 'P');
		// set document information
		$this->SetCreator(PDF_CREATOR);
		$this->SetAuthor('Curier Dragon Star');
		$this->SetTitle('Chitanta Ramburs');
		$this->SetSubject('Chitanta Ramburs');
		$this->SetKeywords('curierat, dragon, star, PDF');

		// remove default header/footer
		$this->setPrintHeader(false);
		$this->setPrintFooter(false);
	
		// set default monospaced font
		$this->SetDefaultMonospacedFont('courier');
		$this->setFontSubsetting(false);

		// set margins
		$this->SetMargins(10, 10, 10);
		
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
    		'text' => false,
    		'font' => 'helvetica',
    		'fontsize' => 8,
    		'stretchtext' => 4
		);
		
		// set font
		$this->SetFont('times', '', 10);

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
	
	public function makeChRamburs(){
		$x = 5;
        $y = 5;
        $cds_title = "DSC EXPRES LOGISTIC S.R.L.";
        $cds_adresa = "Calea Bucuresti nr. 1, Otopeni, Ilfov 075100".PHP_EOL
                ."O.R.C.: J23 /402 /2016 - C.U.I.: RO29255819".PHP_EOL
                ."TEL.: 021-9501, comenzi@curierdragonstar.ro";
        
        $this->setFont('times','B', 9, '');
        $this->MultiCell(90, 5, $cds_title, 0, 'C', 0, 1, $x, $y, true, 0, false, false, 5, 'T', true);
        $this->setFont('times','', 9, '');
        $this->MultiCell(90, 10, $cds_adresa, 0, 'C', 0, 1, $x, '', true, 0, false, false, 15, 'T', true);
        //barcode
        $this->write1DBarcode(strval($this->vars['ch_ramburs']), 'C128A', $x, '', '', 18, 0.4, $this->pdf_style, 'N');
        
        $this->setFont('times','B', 9, '');
        $this->MultiCell(90, 10, "CHITANTA RAMBURS " . (empty($this->vars['tip_plata'])? "CASH":"CONT") . PHP_EOL . $this->vars['ch_ramburs'], 0, 'C', 0, 1, $x, '', true, 0, false, false, 10, 'T', true);

        $primitor = "Am primit de la " . $this->vars['client'];
        $this->MultiCell(90, 5, $primitor, 0, 'C', 0, 1, $x, '', true, 0, false, false, 5, 'T', true);

        $this->setFont('times','', 9, '');
        $adresa = "";
        if(!empty($this->vars['adresa'])) $adresa = "in adresa: " . $this->vars['adresa'];
        $this->MultiCell(90, 10, $adresa, 0, 'C', 0, 1, $x, '', true, 0, false, false, 10, 'T', true);

        $this->setFont('times','B', 9, '');
        if(!empty($this->vars['cui'])) 
            $this->MultiCell(90, 5, "C.U.I.: " . $this->vars['cui'], 0, 'C', 0, 1, $x, '', true, 0, false, false, 5, 'T', true);

        $ramburs = "suma de " . number_format($this->vars['ramburs'], 2) . " LEI," . PHP_EOL 
            . "reprezentand ramburs la NT numar " .$this->vars['expeditie']. PHP_EOL . 
            " pentru expeditorul " .$this->vars['expeditor'];
        $this->MultiCell(90, 55, $ramburs, 0, 'C', 0, 1, $x, '', true, 0, false, false, 55, 'T', true);

        $this->setFont('times','', 8, '');
        $this->SetLineStyle(array('width' => 0.2, 'cap' => 'butt', 'join' => 'miter', 'dash' => 2, 'color' => array(0, 0, 0)));
        $agent = "Emis de: ".$this->vars['agent'];
        $this->MultiCell(90, 5, $agent, 'B', 'C', 0, 1, $x, '', true, 0, false, false, 5, 'T', true);
        
        $p_end = "Centru DSC:  " .$this->vars['centru']. "  /  Tiparit:  " . $this->vars['data'];
        $this->MultiCell(90, 5, $p_end, 0, 'C', 0, 1, $x, '', true, 0, false, false, 5, 'M', true);

	}
	
}
?>