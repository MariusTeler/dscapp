<?php

require_once __DIR__."/../../vendor/tecnickcom/tcpdf/tcpdf.php";
require_once "ConstantsPdf.php";

class BorderouRbsCashPdf extends TCPDF
{

	private $vars;
	public $pdf_style = [];
	
	function __construct($vars=null) {
    	parent::__construct('L', 'mm', 'A4', false, 'ISO-8859-1', false);

    	$this->vars = $vars;
		// set document information
		$this->SetCreator(PDF_CREATOR);
		$this->SetAuthor(ConstantsPdf::DSC_LABEL);
		$this->SetTitle('BorderouRbsCash-'.$this->vars['borderou_id']);
		$this->SetSubject('BorderouRbsCash');
		$this->SetKeywords('curierat, dragon, expeditie, PDF, awb, borderou');

		// remove default footer
		$this->setPrintHeader(false);
		$this->setPrintFooter(true);
		
		$this->setFooterFont(Array('helvetica', '', '10'));
	
		// set default monospaced font
		$this->SetDefaultMonospacedFont('helvetica');
		$this->setFontSubsetting(false);

		// set margins
		$this->SetMargins(10, 10, 10);
		// set JPEG quality
		$this->setJPEGQuality(75);

		// set auto page breaks
		$this->SetAutoPageBreak(true, 15);
		$this->setFooterMargin(15);

		// set image scale factor
		$this->setImageScale(1.25);
		
		// define barcode style
		$this->pdf_style = array(
    		'position' => '',
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
		$this->SetFont('helvetica', '', 10);

		// set cell padding
		$this->setCellPaddings(0.5, 0.5, 0.5, 0.5);

		// set cell margins
		$this->setCellMargins(0, 0, 0, 0);

		// set color for background
		$this->SetFillColor(255, 255, 255);

		$this->SetLineWidth(0.4);
	}
	
	// Page footer
    public function Footer() {
        // Position at 15 mm from bottom
        $this->SetY(-15);
        // Set font
        $this->SetFont('helvetica', 'I', 8);
        // Page number
        $this->Cell(0, 10, 'Pagina '.$this->getAliasNumPage().' din '.$this->getAliasNbPages(), 0, false, 'R', 0, '', 0, false, 'T', 'M');
    }
	
	public function makeBoRbsCashHeader($total_expeditii, $total_ramburs)
	{
		$x = 6;
		$y = 10;
        // expeditor
        $this->setFont('helvetica','B', 12, '');
        $this->MultiCell(72, 7, $this->vars['destinatar'], 0, 'L', 0, 0, $x, $y, true, 0, false, false, 10, '', true);
		$this->setFont('helvetica','', 11, '');
		$this->MultiCell(72, 6, $this->vars['destinatar_localitate'], 0, 'L', 0, 0, $x, $y+7, true, 0, false, false, 6, '', true);
		$this->MultiCell(72, 6, $this->vars['destinatar_adresa'], 0, 'L', 0, 0, $x, $y+13, true, 0, false, false, 6, '', true);
		$this->MultiCell(72, 6, $this->vars['destinatar_contact'] .' '.$this->vars['destinatar_telefon'], 0, 'L', 0, 0, $x, $y+19, true, 0, false, false, 6, '', true);
		
		//cod
		$this->MultiCell(140, 20, '', 0, 'L', 0, 0, $x+72, $y, true, 0, false, false, 20, '', true);
		$this->write1DBarcode(strval($this->expeditie), 'C128A', 127, $y, '', 18, 0.4, $this->pdf_style, 'T');
		//logo
		$this->Image(ConstantsPdf::DSC_PATH, $x+250, $y, 30, 0, 'JPG', '', 'LTR', false, 300, '', false, false, 0, '', false, false);
		
		//adresa
		$adresa = ConstantsPdf::DSC_ADRESA;
		$this->setFont('helvetica','', 9, '');
		$this->MultiCell(72, 18, $adresa, 0, 'R', 0, 1, $x + 212, $y + 10, true, 0, false, false, 18, 'T', true);
		
		$this->setFont('helvetica','B', 15, '');
		$this->MultiCell(284, 7, 'Borderou ramburs cash', 0, 'C', 0, 1, $x, $y + 24, true, 0, false, false, 7, 'T', true);
		$this->setFont('helvetica','B', 13, '');
		$this->data_expeditie = new DateTime($this->data_expeditie);
		$this->data_expeditie = $this->data_expeditie->format('d.m.Y');
		$this->MultiCell(284, 6, $this->expeditie.' / '.$this->data_expeditie, 0, 'C', 0, 1, $x, $y + 31, true, 0, false, false, 7, 'T', true);
		$this->setFont('helvetica','B', 10, '');
		$this->MultiCell(71, 6, 'Total awb: '.$total_expeditii, 'B', 'L', 0, 0, $x, $y+39, true, 0, false, false, 6, '', true);
		$this->MultiCell(71, 6, 'Total ramburs: '.$total_ramburs, 'B', 'L', 0, 0, $x+214, $y+39, true, 0, false, false, 6, '', true);
    }
    
    public function makeBoRbsCashTh() 
	{
		$x = 6;
		$y = 58;
		$this->setFont('helvetica','B', 11, '');
        $this->MultiCell(20, 6, 'AWB rbs', 'LB', 'L', 0, 0, $x, $y, true, 0, false, false, 6, 'M', true);
		$this->MultiCell(20, 6, 'AWB intiala', 'LB', 'L', 0, 0, $x+21, $y, true, 0, false, false, 6, 'M', true);
		$this->MultiCell(20, 6, 'Ramburs', 'LB', 'L', 0, 0, $x+42, $y, true, 0, false, false, 6, 'M', true);
		$this->MultiCell(20, 6, 'Tip plata', 'LB', 'L', 0, 0, $x+63, $y, true, 0, false, false, 6, 'M', true);
		$this->MultiCell(42, 6, 'Expeditor', 'LB', 'L', 0, 0, $x+106, $y, true, 0, false, false, 6, 'M', true);
		$this->MultiCell(30, 6, 'Localitate', 'LB', 'L', 0, 0, $x+137, $y, true, 0, false, false, 6, 'M', true);
		$this->MultiCell(20, 6, 'Judet', 'LB', 'L', 0, 0, $x+158, $y, true, 0, false, false, 6, 'M', true);
		//$this->MultiCell(30, 6, 'Tip plata', 'LB', 'L', 0, 0, $x+179, $y, true, 0, false, false, 6, 'M', true);
		//$this->MultiCell(30, 6, 'Platitor', 'LB', 'L', 0, 0, $x+210, $y, true, 0, false, false, 6, 'M', true);

    }
    
    public function makeBoRbsCashTr($rbs, $initiala, $ramburs, $tip_plata, $expeditor_nume, $expeditor_localitate, $expeditor_judet, $expeditor_contact = '', $expeditor_telefon = '') 
	{
		$x = 6;
		$expeditor_nume = strtoupper(htmlspecialchars_decode(strtolower($expeditor_nume), ENT_QUOTES));
        $expeditor_contact = strtoupper(htmlspecialchars_decode(strtolower($expeditor_contact ?? ''), ENT_QUOTES));
        $expeditor_telefon = strtoupper(htmlspecialchars_decode(strtolower($expeditor_telefon ?? ''), ENT_QUOTES));
        $ramburs = number_format(round($ramburs, 2), 2, '.', '');

		$this->setFont('helvetica','', 8, '');
		$this->MultiCell(20, 6, $rbs, 'B', 'L', 0, 0, $x, '', true, 0, false, false, 6, 'M', false);
		$this->MultiCell(20, 6, $initiala, 'B', 'L', 0, 0, $x+21, '', true, 0, false, false, 6, 'M', false);
		$this->MultiCell(20, 6, $ramburs, 'B', 'L', 0, 0, $x+42, '', true, 0, false, false, 6, 'M', false);
		$this->MultiCell(20, 6, $tip_plata, 'B', 'L', 0, 0, $x+63, '', true, 0, false, false, 6, 'M', false);
		$this->MultiCell(42, 6, $expeditor_nume, 'B', 'L', 0, 0, $x+106, '', true, 0, false, false, 6, 'M', false);
		$this->MultiCell(30, 6, $expeditor_localitate, 'B', 'L', 0, 0, $x+137, '', true, 0, false, false, 6, 'M', false);
		$this->MultiCell(20, 6, $expeditor_judet, 'B', 'L', 0, 0, $x+158, '', true, 0, false, false, 6, 'M', false);
		//$this->MultiCell(30, 6, $expeditor_contact, 'B', 'L', 0, 0, $x+179, '', true, 0, false, false, 6, 'M', false);
		//$this->MultiCell(30, 6, $expeditor_telefon, 'B', 'L', 0, 0, $x+210, '', true, 0, false, false, 6, 'M', false);

    }
    
}
?>