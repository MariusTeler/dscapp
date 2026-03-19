<?php
namespace App\Services\Print;

use TCPDF;
use App\Constants\Pdf as ConstantsPdf;

class BorderouPdfService extends TCPDF
{

	private array $item;
    private array $pdf_style;
	
	function __construct() {
    	parent::__construct('L');

		// set document information
		$this->SetCreator(PDF_CREATOR);
		$this->SetAuthor(ConstantsPdf::DSC_LABEL);
		$this->SetSubject('Borderou');
		$this->SetKeywords('curierat, dragon star, PDF, awb, borderou');

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

    public function setItem(array $item): void 
{
    	$this->item = $item;
    }
	
	// Page footer
    public function Footer(): void
{
        // Position at 15 mm from bottom
        $this->SetY(-15);
        // Set font
        $this->SetFont('helvetica', 'I', 8);
        // Page number
        $this->Cell(0, 10, 'Pagina '.$this->getAliasNumPage().' din '.$this->getAliasNbPages(), 0, false, 'R', 0, '', 0, false, 'T', 'M');
    }
	
	public function makeHeader(int $bo_id, int $total_expeditii, int $total_piese, int $total_plicuri, float $total_greutate, float $total_ramburs): void
	{
		$x = 6;
		$y = 10;
        // expeditor
        $this->setFont('helvetica','B', 12, '');
        $this->MultiCell(72, 7, $this->item['expeditor_id'], 0, 'L', 0, 0, $x, $y, true, 0, false, false, 10, '', true);
		$this->setFont('helvetica','', 11, '');
		$this->MultiCell(72, 6, $this->item['expeditor_localitate'], 0, 'L', 0, 0, $x, $y+7, true, 0, false, false, 6, '', true);
		$this->MultiCell(72, 6, $this->item['expeditor_adresa'], 0, 'L', 0, 0, $x, $y+13, true, 0, false, false, 6, '', true);
		$this->MultiCell(72, 6, $this->item['expeditor_contact'] .' '.$this->item['expeditor_telefon'], 0, 'L', 0, 0, $x, $y+19, true, 0, false, false, 6, '', true);
		
		//cod
		$this->MultiCell(140, 20, '', 0, 'L', 0, 0, $x+72, $y, true, 0, false, false, 20, '', true);
		$this->write1DBarcode(strval($bo_id), 'C128A', 127, $y, '', 18, 0.4, $this->pdf_style, 'T');
		//logo
		$this->Image(ConstantsPdf::DSC_PATH, $x+250, $y - 7, 30, 0, 'JPG', '', 'LTR', false, 300, '', false, false, 0, '', false, false);
		
		//adresa
		$adresa = ConstantsPdf::DSC_ADRESA;
		$this->setFont('helvetica','', 9, '');
		$this->MultiCell(72, 18, $adresa, 0, 'R', 0, 1, $x + 212, $y + 10, true, 0, false, false, 18, 'T', true);
		
		$this->setFont('helvetica','B', 15, '');
		$this->MultiCell(284, 7, 'Borderou predare expeditii', 0, 'C', 0, 1, $x, $y + 24, true, 0, false, false, 7, 'T', true);
		$this->setFont('helvetica','B', 13, '');
		$bo_created_at = new \DateTime($this->item['bo_created_at'] ?? 'now');
		$bo_created_at = $bo_created_at->format('d.m.Y');
		$this->MultiCell(284, 6, $this->item['borderou_id'].' / '.$bo_created_at, 0, 'C', 0, 1, $x, $y + 31, true, 0, false, false, 7, 'T', true);
		$this->setFont('helvetica','B', 10, '');
		$this->MultiCell(71, 6, 'Total expeditii: '.$total_expeditii, 'B', 'L', 0, 0, $x, $y+39, true, 0, false, false, 6, '', true);
		$this->MultiCell(71, 6, 'Total Componente: '.$total_piese .' (Plicuri '.$total_plicuri.')', 'B', 'L', 0, 0, $x+72, $y+39, true, 0, false, false, 6, '', true);
		$this->MultiCell(71, 6, 'Greutate totala: '.number_format($total_greutate, 1, '.', ''), 'B', 'L', 0, 0, $x+143, $y+39, true, 0, false, false, 6, '', true);
		$this->MultiCell(71, 6, 'Ramburs: '.number_format($total_ramburs, 2, '.', ''), 'B', 'L', 0, 0, $x+214, $y+39, true, 0, false, false, 6, '', true);

    }
    
    public function makeTH(): void
	{
		$x = 6;
		$y = 58;
		$this->setFont('helvetica','B', 11, '');
        $this->MultiCell(8, 6, 'Nr.', 'LB', 'L', 0, 0, $x, $y, true, 0, false, false, 6, 'M', true);
		$this->MultiCell(54, 6, 'Destinatar', 'LB', 'L', 0, 0, $x+9, $y, true, 0, false, false, 6, 'M', true);
		$this->MultiCell(34, 6, 'Localitate', 'LB', 'L', 0, 0, $x+64, $y, true, 0, false, false, 6, 'M', true);
		$this->MultiCell(29, 6, 'Nota Transport', 'LB', 'L', 0, 0, $x+99, $y, true, 0, false, false, 6, 'M', true);
		$this->MultiCell(12, 6, 'Bucati:', 'LB', 'L', 0, 0, $x+129, $y, true, 0, false, false, 6, 'M', true);
		$this->MultiCell(22, 6, 'Greutate', 'LB', 'L', 0, 0, $x+142, $y, true, 0, false, false, 6, 'M', true);
		$this->MultiCell(22, 6, 'Ramburs', 'LB', 'L', 0, 0, $x+165, $y, true, 0, false, false, 6, 'M', true);
		$this->MultiCell(15, 6, 'Tip plata', 'LB', 'L', 0, 0, $x+188, $y, true, 0, false, false, 6, 'M', true);
		$this->MultiCell(15, 6, 'Platitor', 'LB', 'L', 0, 0, $x+204, $y, true, 0, false, false, 6, 'M', true);
		$this->MultiCell(65, 6, 'Detalii documente', 'LBR', 'L', 0, 1, $x+220, $y, true, 0, false, false, 6, 'M', true);

    }
    
    public function makeTR(int $td1, string $td2, string $td3, string $td4, int $td5, float $td6, float $td7, string $td8, string $td9, string $td10): void
	{
		$x = 6;
		$this->setFont('helvetica','', 8, '');
		$this->MultiCell(8, 6, $td1, 'B', 'L', 0, 0, $x, '', true, 0, false, false, 6, 'M', false);
		$this->MultiCell(54, 6, $td2, 'B', 'L', 0, 0, $x+9, '', true, 0, false, false, 6, 'M', false);
		$this->MultiCell(34, 6, $td3, 'B', 'L', 0, 0, $x+64, '', true, 0, false, false, 6, 'M', false);
		$this->MultiCell(29, 6, $td4, 'B', 'L', 0, 0, $x+99, '', true, 0, false, false, 6, 'M', false);
		$this->MultiCell(12, 6, $td5, 'B', 'L', 0, 0, $x+129, '', true, 0, false, false, 6, 'M', false);
		$this->MultiCell(22, 6, number_format($td6, 1, '.', ''), 'B', 'L', 0, 0, $x+142, '', true, 0, false, false, 6, 'M', false);
		$this->MultiCell(22, 6, number_format($td7, 2, '.', ''), 'B', 'L', 0, 0, $x+165, '', true, 0, false, false, 6, 'M', false);
		$this->MultiCell(15, 6, $td8, 'B', 'L', 0, 0, $x+188, '', true, 0, false, false, 6, 'M', false);
		$this->MultiCell(15, 6, $td9, 'B', 'L', 0, 0, $x+204, '', true, 0, false, false, 6, 'M', false);
		$this->MultiCell(65, 6, $td10, 'B', 'L', 0, 1, $x+220, '', true, 0, false, false, 6, 'M', false);

    }
    
}