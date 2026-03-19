<?php

require_once __DIR__."/../../vendor/tecnickcom/tcpdf/tcpdf.php";
require_once "ConstantsPdf.php";

class BorderouPdf extends TCPDF
{

	private $vars;
	public $pdf_style = [];
	
	function __construct($vars=null) {
    	parent::__construct('L', 'mm', 'A4', false, 'ISO-8859-1', false);

    	$this->vars = $vars;
		// set document information
		$this->SetCreator(PDF_CREATOR);
		$this->SetAuthor(ConstantsPdf::DSC_LABEL);
		$this->SetTitle('Borderou-'.$this->vars['borderou_id']);
		$this->SetSubject('Borderou');
		$this->SetKeywords('curierat, dragon, expeditie, PDF, awb, borderou');

		// remove default footer
		$this->setPrintHeader(false);
		$this->setPrintFooter(true);
		
		$this->setFooterFont(Array('times', '', '10'));
	
		// set default monospaced font
		$this->SetDefaultMonospacedFont('times');
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
    		'font' => 'times',
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
	
	// Page footer
    public function Footer() {
        // Position at 15 mm from bottom
        $this->SetY(-15);
        // Set font
        $this->SetFont('times', 'I', 8);
        // Page number
        $this->Cell(0, 10, 'Pagina '.$this->getAliasNumPage().' din '.$this->getAliasNbPages(), 0, false, 'R', 0, '', 0, false, 'T', 'M');
    }
	
	public function makeHeader($bo_id, $total_expeditii, $total_piese, $total_plicuri, $total_greutate, $total_ramburs, $data)
	{
		$x = 6;
		$y = 10;
        // expeditor
        $this->setFont('times','B', 12, '');
        $this->MultiCell(72, 7, $this->vars['expeditor_id'], 0, 'L', 0, 0, $x, $y, true, 0, false, false, 10, '', true);
		$this->setFont('times','', 11, '');
		$this->MultiCell(72, 6, $this->vars['expeditor_localitate'], 0, 'L', 0, 0, $x, $y+7, true, 0, false, false, 6, '', true);
		$this->MultiCell(72, 6, $this->vars['expeditor_adresa'], 0, 'L', 0, 0, $x, $y+13, true, 0, false, false, 6, '', true);
		$this->MultiCell(72, 6, $this->vars['expeditor_contact'] .' '.$this->vars['expeditor_telefon'], 0, 'L', 0, 0, $x, $y+19, true, 0, false, false, 6, '', true);
		
		//cod
		$this->MultiCell(140, 20, '', 0, 'L', 0, 0, $x+72, $y, true, 0, false, false, 20, '', true);
		$this->write1DBarcode(strval($bo_id), 'C128A', 127, $y, '', 18, 0.4, $this->pdf_style, 'T');
		//logo
		$this->Image(ConstantsPdf::DSC_PATH, $x+250, $y, 30, 0, 'JPG', '', 'LTR', false, 300, '', false, false, 0, '', false, false);
		
		//adresa
		$adresa = ConstantsPdf::DSC_ADRESA;
		$this->setFont('times','', 9, '');
		$this->MultiCell(72, 18, $adresa, 0, 'R', 0, 1, $x + 212, $y + 10, true, 0, false, false, 18, 'T', true);
		
		$this->setFont('times','B', 15, '');
		$this->MultiCell(284, 7, 'Borderou predare expeditii', 0, 'C', 0, 1, $x, $y + 24, true, 0, false, false, 7, 'T', true);
		$this->setFont('times','B', 13, '');
		$data = new DateTime($data);
		$data = $data->format('d.m.Y');
		$this->MultiCell(284, 6, $this->vars['borderou_id'].' / '.$data, 0, 'C', 0, 1, $x, $y + 31, true, 0, false, false, 7, 'T', true);
		$this->setFont('times','B', 10, '');
		$this->MultiCell(71, 6, 'Total expeditii: '.$total_expeditii, 'B', 'L', 0, 0, $x, $y+39, true, 0, false, false, 6, '', true);
		$this->MultiCell(71, 6, 'Total Componente: '.$total_piese .' (Plicuri '.$total_plicuri.')', 'B', 'L', 0, 0, $x+72, $y+39, true, 0, false, false, 6, '', true);
		$this->MultiCell(71, 6, 'Greutate totala: '.$total_greutate, 'B', 'L', 0, 0, $x+143, $y+39, true, 0, false, false, 6, '', true);
		$this->MultiCell(71, 6, 'Ramburs: '.$total_ramburs, 'B', 'L', 0, 0, $x+214, $y+39, true, 0, false, false, 6, '', true);

    }
    
    public function makeTH() 
	{
		$x = 6;
		$y = 58;
		$this->setFont('times','B', 11, '');
        $this->MultiCell(8, 6, 'Nr.', 'LB', 'L', 0, 0, $x, $y, true, 0, false, false, 6, 'M', true);
		$this->MultiCell(54, 6, 'Destinatar', 'LB', 'L', 0, 0, $x+9, $y, true, 0, false, false, 6, 'M', true);
		$this->MultiCell(34, 6, 'Localitate', 'LB', 'L', 0, 0, $x+64, $y, true, 0, false, false, 6, 'M', true);
		$this->MultiCell(29, 6, 'Nota Transport', 'LB', 'L', 0, 0, $x+99, $y, true, 0, false, false, 6, 'M', true);
		$this->MultiCell(12, 6, 'Piese:', 'LB', 'L', 0, 0, $x+129, $y, true, 0, false, false, 6, 'M', true);
		$this->MultiCell(22, 6, 'Greutate', 'LB', 'L', 0, 0, $x+142, $y, true, 0, false, false, 6, 'M', true);
		$this->MultiCell(22, 6, 'Ramburs', 'LB', 'L', 0, 0, $x+165, $y, true, 0, false, false, 6, 'M', true);
		$this->MultiCell(15, 6, 'Tip plata', 'LB', 'L', 0, 0, $x+188, $y, true, 0, false, false, 6, 'M', true);
		$this->MultiCell(15, 6, 'Platitor', 'LB', 'L', 0, 0, $x+204, $y, true, 0, false, false, 6, 'M', true);
		$this->MultiCell(65, 6, 'Detalii documente', 'LBR', 'L', 0, 1, $x+220, $y, true, 0, false, false, 6, 'M', true);

    }
    
    public function makeTR($td1, $td2, $td3, $td4, $td5, $td6, $td7, $td8, $td9, $td10) 
	{
		$x = 6;
		$this->setFont('times','', 8, '');
		$this->MultiCell(8, 6, $td1, 'B', 'L', 0, 0, $x, '', true, 0, false, false, 6, 'M', false);
		$this->MultiCell(54, 6, $td2, 'B', 'L', 0, 0, $x+9, '', true, 0, false, false, 6, 'M', false);
		$this->MultiCell(34, 6, $td3, 'B', 'L', 0, 0, $x+64, '', true, 0, false, false, 6, 'M', false);
		$this->MultiCell(29, 6, $td4, 'B', 'L', 0, 0, $x+99, '', true, 0, false, false, 6, 'M', false);
		$this->MultiCell(12, 6, $td5, 'B', 'L', 0, 0, $x+129, '', true, 0, false, false, 6, 'M', false);
		$this->MultiCell(22, 6, $td6, 'B', 'L', 0, 0, $x+142, '', true, 0, false, false, 6, 'M', false);
		$this->MultiCell(22, 6, $td7, 'B', 'L', 0, 0, $x+165, '', true, 0, false, false, 6, 'M', false);
		$this->MultiCell(15, 6, $td8, 'B', 'L', 0, 0, $x+188, '', true, 0, false, false, 6, 'M', false);
		$this->MultiCell(15, 6, $td9, 'B', 'L', 0, 0, $x+204, '', true, 0, false, false, 6, 'M', false);
		$this->MultiCell(65, 6, $td10, 'B', 'L', 0, 1, $x+220, '', true, 0, false, false, 6, 'M', false);

    }
    
}
?>