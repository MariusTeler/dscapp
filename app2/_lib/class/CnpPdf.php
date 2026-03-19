<?php

require_once __DIR__."/../../vendor/tecnickcom/tcpdf/tcpdf.php";

class CnpPdf extends TCPDF
{

	private $cnp;
	private $nume;
	public $pdf_style = [];
	const LOGO_PATH = __DIR__.'/../../public/assets/images/dsclogo.jpg';
	
	function __construct($vars = []) {
    	parent::__construct('P', 'mm', 'A4', false, 'ISO-8859-1', false);

    	if(isset($vars['cnp'])) $this->cnp = $vars['cnp'];
    	if(isset($vars['nume'])) $this->nume = $vars['nume'];
		// set document information
		$this->SetCreator(PDF_CREATOR);
		$this->SetAuthor('Curier Dragon Star');
		$this->SetTitle('Scanner');
		$this->SetSubject('scanner');
		$this->SetKeywords('curierat, dragon, star, scanner, PDF');

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
	
	public function makeCnp()
	{
		if(isset($this->cnp) && isset($this->nume))
		{
			$this->setFont('times','', 10, '');
			$this->MultiCell(50, 10, $this->nume, 0, 'L', 0, 0, 90, 5, true, 0, false, false, 10, 'B', true);
			//logo
			$this->Image(CnpPdf::LOGO_PATH, 10, 10, 30, 0, 'JPG', '', '', false, 300, '', false, false, 0, '', false, false);
			$x = 70;
			$y = 50;
			//barcode
			$this->write1DBarcode(strval($this->cnp), 'C128A', $x, $y, '', 18, 0.4, $this->pdf_style, 'N');
		}
	}
	
}
?>