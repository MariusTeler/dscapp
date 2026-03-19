<?php

require_once __DIR__."/../../vendor/tecnickcom/tcpdf/tcpdf.php";
require_once "ConstantsPdf.php";

class PvAgentPdf extends TCPDF
{

	private $vars;
	public $pdf_style = [];
	
	function __construct($vars = null) {
    	parent::__construct('P', 'mm', 'A4', false, 'ISO-8859-1', false);

    	$this->vars = $vars;
		// set document information
		$this->SetCreator(PDF_CREATOR);
		$this->SetAuthor(ConstantsPdf::DSC_LABEL);
		$this->SetTitle('PV-'.(new DateTime($this->vars['show_data']))->format('d.m.Y').'-'.$this->vars['agent_nume']);
		$this->SetSubject('PV');
		$this->SetKeywords('curierat, DSC, expeditie, PDF');

		// enable default header/footer
		$this->setPrintHeader(true);
		$this->setPrintFooter(true);
	
		// set default monospaced font
		$this->SetDefaultMonospacedFont('courier');
		$this->setFontSubsetting(false);

		// set margins
		$this->SetMargins(15, 15);
		// set JPEG quality
		$this->setJPEGQuality(75);

		// set auto page breaks
		$this->SetAutoPageBreak(true, 10);

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
		$this->SetFont('times', '', 10);

		// set cell padding
		$this->setCellPaddings(0.5, 0.5, 0.5, 0.5);

		// set cell margins
		$this->setCellMargins(0, 0, 0, 0);

		// set color for background
		$this->SetFillColor(255, 255, 255);

		$this->SetLineWidth(0.4);
	}

	public function Header() {
		$ddata = new DateTime($this->vars['show_data']);
		$ddata = $ddata->format('d.m.Y');
		$this->SetTopMargin(15);
		$this->Image(ConstantsPdf::DSC_PATH, '', '', '', 20, 'JPG', '', 'LTR', false, 300, '', false, false, 0, '', false, false);
		$this->MultiCell($this->getPageWidth()-30, 22, ConstantsPdf::DSC_ADRESA, 'B', 'R', 0, 1, '', '', true, 0, false, false, 10, 'T', true);
		$this->Ln(5);
		//width : 210 - 30 = 180
		$pageWidth = $this->getPageWidth()-30;
		$hp = (int)($pageWidth/2);

    	$this->setFont('times','B', 9, '');
        $this->MultiCell($hp, 5, ' Decont ID: '.$this->vars['decont_id'], 'LT', 'L', 0, 0, '', '', true, 0, false, false, 5, '', true);
        $this->MultiCell($hp, 5, ' Data: '.$ddata, 'LTR', 'L', 0, 1, '', '', true, 0, false, false, 5, '', true);
		$this->MultiCell($hp, 5, ' Curier: '.$this->vars['agent_nume'], 'LB', 'L', 0, 0, '', '', true, 0, false, false, 5, '', true);
		$this->MultiCell($hp, 5, ' Centru decont: '.$this->vars['centru_nume'], 'LBR', 'L', 0, 1, '', '', true, 0, false, false, 5, '', true);
		$this->Ln(5);

		$this->setFont('times','B', 24, '');
		$this->MultiCell($this->getPageWidth()-30, 10, 'PROCES VERBAL', 0, 'C', 0, 1, '', '', true, 0, false, false, 10, 'T', true);
		$this->setFont('times','B', 18, '');
		$this->MultiCell($this->getPageWidth()-30, 10, 'de predare a sumelor provenite din activitate', 0, 'C', 0, 1, '', '', true, 0, false, false, 10, 'T', true);
		$this->setFont('times','', 9, '');
		$this->MultiCell($this->getPageWidth()-30, 5, 'Subsemnatul '.$this->vars['agent_nume'].', am predat in data de '.$ddata.' suma de '.$this->vars['totaluri']['total'].' lei, avand urmatoarea componenta:', 0, 'L', 0, 1, '', '', true, 0, false, false, 10, 'T', true);
		$this->Ln(5);
	}

	public function Footer() {
		$pageWidth = $this->getPageWidth()-30;
		$hp = (int)($pageWidth/2)-10;

		$this->setY(-110);
		$this->setFont('times','', 9, '');
		$this->MultiCell($this->getPageWidth()-30, 10, 'Subsemnatii declaram pe proprie raspundere ca toate sumele de bani au fost preluate/predate intre noi.', 0, 'L', 0, 1, '', '', true, 0, false, false, 10, 'T', true);
		$this->MultiCell($this->getPageWidth()-30, 5, 'In situatia in care sumele de bani nu au fost predate/preluate ne asumam integral plata contravalorii acestora.', 0, 'L', 0, 1, '', '', true, 0, false, false, 5, 'T', true);
		$this->MultiCell($this->getPageWidth()-30, 15, 'Subsemnatul, primitor al sumelor de mai sus, ma oblig sa le predau catre persoanele sau departamentele financiar-contabile ale companiei noastre.', 0, 'L', 0, 1, '', '', true, 0, false, false, 15, 'T', true);
		$this->MultiCell($this->getPageWidth()-30, 15, 'Am luat cunostinta de faptul ca, in conformitate cu fisa postului si a Regulamentului de Ordine Interioara nesemnarea prezentului proces verbal de predare primire reprezinta abatere disciplinara, atrage raspunderea mea contractuala in raport cu angajatorul si aplicarea unei pedepse cu scaderea a 10% din salariu pentru urmatoarele 3 luni.', 0, 'L', 0, 1, '', '', true, 0, false, false, 15, 'T', true);
		$this->MultiCell($this->getPageWidth()-30, 15, 'Nota: Prevederile de mai sus se aplica si pentru curierii subcontractorilor, in conditiile contractuale agreate cu subcontractorul, cu exceptia nesemnarii. Nesemnarea prezentului proces verbal de catre curierul subcontractorului va constitui abatere si i se va aplica subcontractorului o penalizare de 100 lei pentru fiecare abatere.', 0, 'L', 0, 1, '', '', true, 0, false, false, 15, 'T', true);
		
		$this->Ln(3);
		$this->setFont('times','', 9, '');
		$this->MultiCell($hp, 15, 'Am predat suma mai sus mentionata ', 'B', 'L', 0, 0, '', '', true, 0, false, false, 15, 'T', false);
		$this->MultiCell(20, 15, '', 0, 'L', 0, 0, '', '', true, 0, false, false, 15, 'T', false);
		$this->MultiCell($hp, 15, 'Am primit suma mai sus mentionata ', 'B', 'L', 0, 1, '', '', true, 0, false, false, 15, 'T', false);
		$this->MultiCell($hp, 15, '(nume si prenume in clar)', 'B', 'L', 0, 0, '', '', true, 0, false, false, 15, 'T', false);
		$this->MultiCell(20, 15, '', 0, 'L', 0, 0, '', '', true, 0, false, false, 15, 'T', false);
		$this->MultiCell($hp, 15, '(nume si prenume in clar)', 'B', 'L', 0, 1, '', '', true, 0, false, false, 15, 'T', false);
		$this->MultiCell($hp, 15, '(semnatura)', 0, 'L', 0, 0, '', '', true, 0, false, false, 15, 'T', false);
		$this->MultiCell(20, 15, '', 0, 'L', 0, 0, '', '', true, 0, false, false, 15, 'T', false);
		$this->MultiCell($hp, 15, '(semnatura)', 0, 'L', 0, 1, '', '', true, 0, false, false, 15, 'T', false);
		$this->setY(-15);
		$this->setFont('times','B', 8, '');
		$this->Cell($this->getPageWidth(), 5, 'Page '.$this->getAliasNumPage().' / '.$this->getAliasNbPages(), 0, false, 'C', 0, '', 0, false, 'T', 'M');
	}

	public function makeThExpeditii()
	{
		$this->SetY($this->vars['y']);
		$pageWidth = $this->getPageWidth()-30;
		$hp = (int)($pageWidth/5);

		$this->setFont('times','B', 9, '');
		$this->MultiCell($hp+40, 5, 'Nr. Expeditie', 1, 'C', 0, 0, '', '', true, 0, false, false, 5, '', true);
		$this->MultiCell($hp-15, 5, 'Transport (lei)', 1, 'C', 0, 0, '', '', true, 0, false, false, 5, '', true);
		$this->MultiCell($hp, 5, 'Factura/Chitanta', 1, 'C', 0, 0, '', '', true, 0, false, false, 5, '', true);
		$this->MultiCell($hp-15, 5, 'Ramburs (lei)', 1, 'C', 0, 0, '', '', true, 0, false, false, 5, '', true);
		$this->MultiCell($hp-10, 5, 'Chitanta RBS', 1, 'C', 0, 1, '', '', true, 0, false, false, 5, '', true);
		$this->vars['y'] = $this->GetY();
	}

	public function makeTrExpeditii($exp)
	{
		$this->SetY($this->vars['y']);
		$pageWidth = $this->getPageWidth()-30;
		$hp = (int)($pageWidth/5);

		$this->setFont('times','', 9, '');
		$this->MultiCell($hp+40, 5, $exp['expeditie'], 1, 'L', 0, 0, '', '', true, 0, false, false, 5, '', true);
		$this->MultiCell($hp-15, 5, $exp['transport'], 1, 'R', 0, 0, '', '', true, 0, false, false, 5, '', true);
		$this->MultiCell($hp, 5, $exp['factura'], 1, 'L', 0, 0, '', '', true, 0, false, false, 5, '', true);
		$this->MultiCell($hp-15, 5, $exp['ramburs'], 1, 'R', 0, 0, '', '', true, 0, false, false, 5, '', true);
		$this->MultiCell($hp-10, 5, $exp['chitanta'], 1, 'L', 0, 1, '', '', true, 0, false, false, 5, '', true);
		$this->vars['y'] = $this->GetY();
	}

	public function makeThChitanteFiscale()
	{
		$this->SetY($this->vars['y']);
		$pageWidth = $this->getPageWidth()-30;
		$hp = (int)($pageWidth/5);

		$this->setFont('times','B', 9, '');
		$this->Ln(5);
		$this->MultiCell($hp + 40, 5, 'Chitanta client CTR', 1, 'C', 0, 0, '', '', true, 0, false, false, 5, '', true);
		$this->MultiCell($hp - 15, 5, 'Suma (lei)', 1, 'C', 0, 0, '', '', true, 0, false, false, 5, '', true);
		$this->MultiCell(3*$hp - 25, 5, 'Descriere/Client', 1, 'C', 0, 1, '', '', true, 0, false, false, 5, '', true);
		$this->vars['y'] = $this->GetY();
	}

	public function makeTrChitanteFiscale($chitante)
	{
		//['tip'=>$row['tip'],'desc'=>$row['desc'],'factura'=>$row['factura'],'chitanta'=>$row['chitanta'],'suma'=>$row['suma']]
		$this->SetY($this->vars['y']);
		$pageWidth = $this->getPageWidth()-30;
		$hp = (int)($pageWidth/5);

		$this->setFont('times','', 9, '');
		$this->MultiCell($hp + 40, 5, $chitante['chitanta'], 1, 'L', 0, 0, '', '', true, 0, false, false, 5, '', true);
		$this->MultiCell($hp - 15, 5, $chitante['suma'], 1, 'R', 0, 0, '', '', true, 0, false, false, 5, '', true);
		$this->MultiCell(3*$hp - 25, 5, $chitante['descriere'], 1, 'C', 0, 1, '', '', true, 0, false, false, 5, '', true);
		$this->vars['y'] = $this->GetY();
	}

	public function makeThCheltuieli()
	{
		$this->SetY($this->vars['y']);
		$pageWidth = $this->getPageWidth()-30;
		$hp = (int)($pageWidth/4);

		$this->setFont('times','', 9, '');
		$this->Ln(5);
		$this->MultiCell($hp, 5, 'Alte incasari/cheltuieli:', 0, 'L', 0, 1, '', '', true, 0, false, false, 5, '', true);

		$this->setFont('times','B', 9, '');
		$this->MultiCell($hp, 5, 'Cheltuiala/Incasare', 1, 'C', 0, 0, '', '', true, 0, false, false, 5, '', true);
		$this->MultiCell(2 * $hp, 5, 'Descriere/Client', 1, 'C', 0, 0, '', '', true, 0, false, false, 5, '', true);
		$this->MultiCell($hp, 5, 'Suma (lei)', 1, 'C', 0, 1, '', '', true, 0, false, false, 5, '', true);
		$this->vars['y'] = $this->GetY();
	}

	public function makeTrCheltuieli($cheltuiala)
	{
		//['tip'=>$row['tip'],'desc'=>$row['desc'],'factura'=>$row['factura'],'chitanta'=>$row['chitanta'],'suma'=>$row['suma']]
		$this->SetY($this->vars['y']);
		$pageWidth = $this->getPageWidth()-30;
		$hp = (int)($pageWidth/4);

		$this->setFont('times','', 9, '');
		$this->MultiCell($hp, 5, $cheltuiala['tip'], 1, 'L', 0, 0, '', '', true, 0, false, false, 5, '', true);
		$this->MultiCell(2*$hp, 5, $cheltuiala['desc'], 1, 'L', 0, 0, '', '', true, 0, false, false, 5, '', true);
		$this->MultiCell($hp, 5, $cheltuiala['suma'], 1, 'R', 0, 1, '', '', true, 0, false, false, 5, '', true);
		$this->vars['y'] = $this->GetY();
	}

	public function makeTotal()
	{
		$this->SetY($this->vars['y']);
		$pageWidth = $this->getPageWidth()-30;
		$hp = (int)($pageWidth/2);

		$this->setFont('times','', 9, '');
		$this->Ln(5);
		$this->MultiCell($hp, 5, 'Total sume pe provenienta:', 0, 'L', 0, 1, '', '', true, 0, false, false, 5, '', true);

		$this->setFont('times','B', 9, '');
		$this->SetFillColor(0,0,0);
		$this->SetTextColor(255,255,255);
		$this->MultiCell($hp, 5, 'Provenienta', 1, 'L', 1, 0, '', '', true, 0, false, false, 5, '', true);
		$this->MultiCell($hp, 5, 'Total suma (lei)', 1, 'L', 1, 1, '', '', true, 0, false, false, 5, '', true);

		$this->SetFillColor(255,255,255);
		$this->SetTextColor(0,0,0);
		
		$this->setFont('times','', 9, '');
		//$this->vars['totaluri']['transport'] = 0;
		if(!empty($this->vars['totaluri']['transport']) && $this->vars['totaluri']['transport'] > 0){
			$this->MultiCell($hp, 5, 'Contravaloarea transport', 0, 'L', 0, 0, '', '', true, 0, false, false, 5, '', true);
			$this->MultiCell($hp, 5, $this->vars['totaluri']['transport'], 0, 'L', 0, 1, '', '', true, 0, false, false, 5, '', true);
		}
		//$this->vars['totaluri']['ramburs'] = 0;
		if(!empty($this->vars['totaluri']['ramburs'])){
			$this->MultiCell($hp, 5, 'Ramburs', 0, 'L', 0, 0, '', '', true, 0, false, false, 5, '', true);
			$this->MultiCell($hp, 5, $this->vars['totaluri']['ramburs'], 0, 'L', 0, 1, '', '', true, 0, false, false, 5, '', true);
		}
		//$this->vars['totaluri']['chitante'] = 0;
		if(!empty($this->vars['totaluri']['chitante'])){
			$this->MultiCell($hp, 5, 'Incasari FF DSC', 0, 'L', 0, 0, '', '', true, 0, false, false, 5, '', true);
			$this->MultiCell($hp, 5, $this->vars['totaluri']['chitante'], 0, 'L', 0, 1, '', '', true, 0, false, false, 5, '', true);
		}
		//error_log('debug : '.$this->vars['totaluri']['cheltuieli']);
		if(!empty($this->vars['totaluri']['cheltuieli'])){
			$this->MultiCell($hp, 5, 'Cheltuieli', 0, 'L', 0, 0, '', '', true, 0, false, false, 5, '', true);
			$this->MultiCell($hp, 5, $this->vars['totaluri']['cheltuieli'], 0, 'L', 0, 1, '', '', true, 0, false, false, 5, '', true);
		}
		
		$this->setFont('times','B', 9, '');
		$this->MultiCell($hp, 5, 'Total general', 'T', 'L', 0, 0, '', '', true, 0, false, false, 5, '', true);
		$this->MultiCell($hp, 5, $this->vars['totaluri']['total'], 'T', 'L', 0, 1, '', '', true, 0, false, false, 5, '', true);
		$this->vars['y'] = $this->GetY();
	}
}
?>
