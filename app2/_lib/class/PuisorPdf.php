<?php

require_once __DIR__."/../../vendor/tecnickcom/tcpdf/tcpdf.php";
require_once "ConstantsPdf.php";

class PuisorPdf extends TCPDF
{
	private $vars;
	public $pdf_style = [];

	function __construct($vars = null) {
		parent::__construct('P', 'mm', 'A4', false, 'ISO-8859-1', false);

    	$this->vars = $vars;

    	$format = array(ConstantsPdf::IL70,ConstantsPdf::IL100);
    	if(is_array($this->vars) && isset($this->vars['print_awb']))
		{
			if($this->vars['print_awb']==3)
    			$format = array(ConstantsPdf::IL70,ConstantsPdf::IL100);
    		else if($this->vars['print_awb']==4)
				$format = array(ConstantsPdf::IL100,ConstantsPdf::IL150);
			else if($this->vars['print_awb']==5) //A5
    			$format = array(148.7, 210.2);
    	}

		$orientation = 'L';
    	$this->setPageFormat($format,$orientation);
		// set document information
		$this->SetCreator(PDF_CREATOR);
		$this->SetAuthor(ConstantsPdf::DSC_LABEL);
		if(isset($this->vars['expeditie']))
			$this->SetTitle('puisori-NT-'.$this->vars['expeditie']);
		else $this->SetTitle('puisori-NT-');
		$this->SetSubject('AWB');
		$this->SetKeywords('curierat, dragon, CDS, expeditie, PDF, awb');

		// remove default header
		$this->setPrintHeader(false);

		// set default monospaced font
		$this->SetDefaultMonospacedFont('courier');
		$this->setFontSubsetting(false);

		// set margins
		$this->SetMargins(1, 1);
		// set JPEG quality
		$this->setJPEGQuality(75);

		// set auto page breaks
		$this->SetAutoPageBreak(false, 5);

		// set image scale factor
		$this->setImageScale(1.25);

		// define barcode style
		$this->pdf_style = array(
    		'position' => 'C',
    		'align' => 'C',
 		   	'stretch' => false,
 		   	'fitwidth' => true,
    		'cellfitalign' => 'C',
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
        // Position at 1 mm from bottom
        $this->SetY(-5);
        $this->SetFont('helvetica', '', 6);
        $this->Cell(0, 5, ConstantsPdf::DSC_LABEL, 0, false, 'C', 0, '', 0, false, 'T', 'M');
    }

	public function setVars($vars){
		$this->vars = $vars;
		$orientation = 'L';
		if(is_array($this->vars) && isset($this->vars['print_awb']))
		{
			if($this->vars['print_awb']==3)
    			$format = array(ConstantsPdf::IL70,ConstantsPdf::IL100);
    		else if($this->vars['print_awb']==4)
    			$format = array(ConstantsPdf::IL100,ConstantsPdf::IL150);
    		$this->setPageFormat($format,$orientation);
    	}
	}

	public function makePuisorMultiCell($code, $nr_colet, $position = 0)
	{

		$x=14;
		$y=18;
		$width = $this->getPageWidth()-2*$x;
		$height = $this->getPageHeight()-$y-5;

		//barcode
		//$this->MultiCell(0, 16, '', 0, '', 0, 1, '', '', false, 0, false, false, 16, '', false);
		$this->write1DBarcode(strval($code), 'C128A', '', '', '', '20', 0.5, $this->pdf_style, 'N');

		$this->setFont('times','B', 26, '');
		$this->setXY(0,$this->getPageHeight());
		$this->StartTransform();
		$this->Rotate(90);
		$this->Cell($this->getPageHeight() - 17, 15, $this->vars['destinatar_centru'], 0, 1, 'C', 0, '', 1);
		$this->StopTransform();

		// expeditor
		$this->setXY($x,$y);
		$this->SetLineWidth(0.4);
		$this->MultiCell($width-2, intval($height/2), '', 1, 'C', 1, 0, '', '', true, 0, false, false, intval($height/2), 'T', false);
		$this->setFont('times','B', 30, '');
		$this->MultiCell(0, intval($height/2), $nr_colet, 0, 'C', 0, 1, '', '', true, 0, false, false, 18, 'M', true);

		$this->setXY($x+1,$y+1);
		$this->setFont('times','B', 9, '');
		$this->SetLineWidth(0.2);
		$this->MultiCell($width-4, 4, 'EXPEDITOR: '.$this->vars['expeditor_nume'], 1, 'L', 0, 1, '', '', true, 0, false, false, 4, 'M', true);

		$this->setFont('times','', 9, '');
		$this->setX($x+1);
		$this->vars['expeditor_cui'] = $this->vars['expeditor_cui'] ?? "";
		$this->vars['expeditor_j'] = $this->vars['expeditor_j'] ?? "";
		$this->MultiCell($width-4, 4, "CIF : {$this->vars['expeditor_cui']} {$this->vars['expeditor_j']}", 0, 'L', false, 1, '', '', true, 0, false, false, 4, 'T', true);
		$this->setX($x+1);
		$this->MultiCell($width-4, 4, 'Adresa: '.$this->vars['expeditor_adresa'], 0, 'L', 0, 1, '', '', true, 0, false, false, 4, 'T', true);
		$this->setX($x+1);
		$this->MultiCell($width-4, 4, 'Contact: '.(empty($this->vars['expeditor_contact'])?'':$this->vars['expeditor_contact']).(empty($this->vars['expeditor_telefon'])?'':(' / '.$this->vars['expeditor_telefon'])), 0, 'L', 0, 1, '', '', true, 0, false, false, 4, 'T', true);

		$this->setFont('times','B', 9, '');
		$this->setX($x+1);
		$this->MultiCell(intval(2*$width/3), 5, $this->vars['expeditor_localitate'].'('.$this->vars['expeditor_judet_id'].')', 0, 'L', 0, 0, '', '', true, 0, false, false, 5, 'B', true);
		$this->MultiCell(intval($width/3)-1, 5, $this->vars['expeditor_centru'], 'L', 'L', 0, 0, '', '', true, 0, false, false, 5, 'B', true);
		$this->setXY($width+$x-2,intval($height/2) +$y - 4);
		$this->MultiCell(14, 5, 'din', 0, 'C', 0, 1, '', '', true, 0, false, false, 5, 'B', true);

		$y=$height/2+$y;
		// destinatar
		$this->setXY($x,$y);
		$this->SetLineWidth(0.4);
		$this->MultiCell($width-2, intval($height/2), '', 1, 'C', 1, 0, '', '', true, 0, false, false, intval($height/2), 'T', false);
		$this->setFont('times','B', 30, '');
		$this->MultiCell(0, intval($height/2), $this->vars['piese'], 0, 'C', 0, 1, '', '', true, 0, false, false, intval($height/2), 'M', true);

		$this->setXY($x+1,$y+1);
		$this->setFont('times','B', 9, '');
		$this->SetLineWidth(0.2);
		$this->MultiCell($width-4, 5, 'DESTINATAR : '.$this->vars['destinatar_nume'], 1, 'L', 0, 1, '', '', true, 0, false, false, 5, 'M', true);

		$this->setFont('times','', 9, '');
		$this->setX($x+1);
		$this->MultiCell($width-4, 5, 'Adresa: '.$this->vars['destinatar_adresa'], 0, 'L', 0, 1, '', '', true, 0, false, false, 5, 'T', true);
		$this->setX($x+1);
		$this->MultiCell($width-4, 5, 'Contact: '.(empty($this->vars['destinatar_contact'])?'':$this->vars['destinatar_contact']).(empty($this->vars['destinatar_telefon'])?'':(' / '.$this->vars['destinatar_telefon'])), 0, 'L', 0, 1, '', '', true, 0, false, false, 5, 'T', true);

		$this->setFont('times','B', 9, '');
		$this->setX($x+1);
		$this->MultiCell(intval(2*$width/3), 5, $this->vars['destinatar_localitate'].'('.$this->vars['destinatar_judet_id'].')', 0, 'L', 0, 0, '', '', true, 0, false, false, 5, 'B', true);
		$this->MultiCell(intval($width/3)-1, 5, $this->vars['destinatar_centru'].$this->vars['destinatar_centru_zona'], 'L', 'L', 0, 1, '', '', true, 0, false, false, 5, 'B', true);
	}
}
?>
