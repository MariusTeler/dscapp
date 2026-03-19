<?php
namespace App\Services\Print;

use TCPDF;
use App\Constants\Pdf as ConstantsPdf;

class AwbPdfService extends TCPDF
{
	private $item;
 	private $pdf_style, $pdf_style_top, $pdf_style_bottom;

	function __construct() {
		parent::__construct(); //format 'A4' by default
    	
		// set document information
		$this->SetCreator(PDF_CREATOR);
		$this->SetAuthor(ConstantsPdf::DSC_LABEL);

		if(isset($this->item['awb'])){
			$this->SetTitle('NT-'.$this->item['awb']);
		}

		$this->SetSubject('AWB');
		$this->SetKeywords('curierat, DSC, expeditie, PDF, awb');

		// remove default header/footer
		$this->setPrintHeader(false);
		$this->setPrintFooter(false);

		// set default monospaced font
		$this->SetDefaultMonospacedFont('helvetica');
		$this->setFontSubsetting(false);

		// set margins
		$this->SetMargins(10, 10, 10);
		// set JPEG quality
		$this->setJPEGQuality(75);

		// set auto page breaks
		$this->SetAutoPageBreak(false, 5);

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

		// define barcode style
		$this->pdf_style_top = array(
    		'position' => '',
    		'align' => 'C',
 		   	'stretch' => false,
 		   	'fitwidth' => false,
    		'cellfitalign' => 'C',
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

		$this->pdf_style_bottom = array(
    		'position' => '',
    		'align' => 'L',
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
		$this->SetFont('helvetica', '', 10);

		// set cell padding
		$this->setCellPaddings(0.5, 0.5, 0.5, 0.5);

		// set cell margins
		$this->setCellMargins(0, 0, 0, 0);

		// set color for background
		$this->SetFillColor(255, 255, 255);

		$this->SetLineWidth(0.4);
	}

	public function setPdfPageFormat(array $format, $orientation = 'P'): void
	{
		$this->setPageFormat($format, $orientation);
		if($orientation == 'L'){
			$this->SetMargins(1, 1, 1);
		}
	}

	public function setItem(array $item): void
	{
		$this->item = $item;
	}

	//Page header
	public function Header() 
	{
		// Logo
		$x = 10;
		$this->Image(ConstantsPdf::DSC_PATH, $x, 10, 15, '', 'JPG', '', 'T', false, 300, '', false, false, 0, false, false, false);
		$this->setFont('helvetica','B', 10, '');
		$data = new \DateTime($this->item['data_expeditie']);
		$this->MultiCell(0, 5, "Borderou RBS cash : {$this->item['awb']} din data de {$data->format('d.m.Y')}", 0, 'L', 0, 1, $x + 20, null, true, 0, false, false, 5, 'T', true);
        $this->MultiCell(0, 5, $this->item['destinatar_nume'], 0, 'L', 0, 1, $x + 20, null, true, 0, false, false, 5, '', true);
		$this->MultiCell(0, 5, "Total nr. awb: {$this->item['nr_awb_rbs_cash']}", 0, 'L', 0, 1, $x + 20, null, true, 0, false, false, 5, '', true);
		$this->MultiCell(0, 5, "Total ramburs: {$this->item['asigurare']} {$this->item['moneda']}", 'B', 'L', 0, 1, $x + 20, null, true, 0, false, false, 5, '', true);
	}

	// Page footer
    public function Footer() 
	{
        // Position at 15 mm from bottom
        $this->SetY(-15);
        // Set font
        $this->SetFont('helvetica', 'I', 8);
        // Page number
        $this->Cell(0, 10, 'Pagina '.$this->getAliasNumPage().' din '.$this->getAliasNbPages(), 0, false, 'R', 0, '', 0, false, 'T', 'M');
    }

	public function toogleHeaderFooterAutoBrake(bool $on = true): void 
	{
		$this->setPrintHeader($on);
		$this->setPrintFooter($on);
		$this->SetAutoPageBreak($on, 15);
	}

	public function makeHalfFirstPage(int $xi, int $yi): void
	{
	  //MultiCell( $w, $h, $txt, $border = 0, $align = 'J', $fill = false, $ln = 1, $x = '', $y = '', $reseth = true, $stretch = 0, $ishtml = false, $autopadding = true, $maxh = 0, $valign = 'T', $fitcell = false )
		$this->SetMargins(5, 5, 5);
		$this->SetLineWidth(0.2);

		$x = $xi + 6;
		$y = $yi + 6;

		$this->setX($x);
		$this->setY($y);
	
		//Image( $file, $x = '', $y = '', $w = 0, $h = 0, $type = '', $link = '', $align = '', $resize = false, $dpi = 300, $palign = '', $ismask = false, $imgmask = false, $border = 0, $fitbox = false, $hidden = false, $fitonpage = false, $alt = false, $altimgs = [] )
		//logo
		$this->Image(ConstantsPdf::DSC_PATH, '', $y - 2, 0, 15, 'JPG', '', '', true, 300, '', false, false, 0);

		$this->setFont('helvetica','B', 22, '');
		$this->SetTextColor(255,255,255);
		$this->SetFillColor(0, 0, 0);

		$this->MultiCell(10, 10, (isset($this->item['destinatar_rut_bvh']) ? $this->item['destinatar_rut_bvh'] : "0"), 1, 'C', true, 0, $x + 29, '', true, 0, false, true, 10, 'M');
		$this->MultiCell(20, 10, strtoupper($this->item['destinatar_centru_cod']), 1, 'C', true, 0, $x + 40, '', true, 0, false, true, 10, 'M');
		$this->setFont('helvetica','B', 16, '');
		$this->MultiCell(30, 10, '1 din '.$this->item['piese'], 1, 'C', true, 0, $x + 61, '', true, 0, false, true, 10, 'M');
		$this->setFont('helvetica','B', 22, '');
		$this->MultiCell(10, 10, (isset($this->item['destinatar_rut_buh']) ? $this->item['destinatar_rut_buh'] : "0"), 1, 'C', true, 0, $x + 92, '', true, 0, false, true, 10, 'M');
		$this->setFont('helvetica','B', 16, '');

		//write1DBarcode(strval($code), $type, $x = '', $y = '', $w = '', $h = '', $xres = '', $style = [], $align = '' )
		$this->write1DBarcode(strval($this->item['awb']), 'C128A', '', $this->getY() - 2, 120, 16, '', $this->pdf_style_top, 'N');

		$this->setX($x);
		$this->setY($y + 10);
		$this->SetTextColor(0,0,0);
		$this->SetFillColor(255, 255, 255);

		$this->MultiCell(80, 8, $this->item['destinatar_centru'].$this->item['destinatar_centru_zona'], 0, 'C', true, 0, $this->getX() + 25, '', true, 0, false, true, 10, '8');
		$this->setFont('helvetica','B', 12, '');
		$this->MultiCell(120, 8, $this->item['awb'], 0, 'C', true, 1, '', '', true, 0, false, true, 8, 'M');

		$y_temp = $this->getY();
		//expeditor
		$this->setFont('helvetica','', 8, '');
		$this->SetTextColor(255, 255, 255);
		$this->SetFillColor(0,0,0);
		$this->MultiCell(15, 5, "Expeditor", 0, 'L', true);
		$this->SetTextColor(0,0,0);
		$this->SetFillColor(255, 255, 255);
		$this->setFont('helvetica','B', 10, '');
		$this->MultiCell(75, 5, $this->item['expeditor_nume'], 0, 'L', false, 1, '', '', true, 0, false, true, 5, 'M', true);
		$this->setFont('helvetica','B', 9, '');
		$this->item['expeditor_cui'] = $this->item['expeditor_cui'] ?? "";
		$this->item['expeditor_j'] = $this->item['expeditor_j'] ?? "";
		$this->MultiCell(75, 5, "CIF : {$this->item['expeditor_cui']} {$this->item['expeditor_j']}", 0, 'L', false, 1, '', '', true, 0, false, true, 5, 'T', true);
		$this->setFont('helvetica','', 10, '');
		$this->MultiCell(75, 17, "Adresa: {$this->item['expeditor_adresa']} / Contact: {$this->item['expeditor_contact']} / Tel: {$this->item['expeditor_telefon']}", 0, 'L', false, 1, '', '', true, 0, false, true, 17, 'T', true);

		$this->item['expeditor_localitate'] = strtoupper($this->item['expeditor_localitate']);
		$this->MultiCell(75, 5, "Loc: <b>{$this->item['expeditor_localitate']} ({$this->item['expeditor_judet_id']})</b>, C.O.: <b>{$this->item['expeditor_centru_cod']}</b>", 0, '', false, 1, '', '', true, 0, true);
		$this->setFont('helvetica','B', 8, '');
		$this->MultiCell(75, 5, "Prin tiparirea acestei Note de Transport (NT), sunt de acord cu Termenii si Conditiile serviciului utilizat.", 0, 'L');

		//destinatar
		$this->setFont('helvetica','', 8, '');
		$this->SetTextColor(255, 255, 255);
		$this->SetFillColor(0,0,0);
		$this->MultiCell(15, 5, "Destinatar", 1, 'L', true);
		$this->SetTextColor(0,0,0);
		$this->SetFillColor(255, 255, 255);
		$this->setFont('helvetica','B', 10, '');
		$this->MultiCell(75, 7, $this->item['destinatar_nume'], 'LTR', 'L', false, 1, '', '', true, 0, false, true, 7, 'M', true);
		$this->setFont('helvetica','', 10, '');
		$this->MultiCell(75, 17, $this->item['destinatar_adresa'], 'LR', 'L', false, 1, '', '', true, 0, false, true, 17, 'T', true);

		$this->setFont('helvetica','B', 10, '');
		$this->item['destinatar_localitate'] = strtoupper($this->item['destinatar_localitate']);
		$this->MultiCell(75, 6, $this->item['destinatar_localitate']." ({$this->item['destinatar_judet_id']})", 'LR', '', false, 1, '', '', true, 0, false, true, 6, 'T', true );
		$this->MultiCell(75, 6, $this->item['destinatar_contact'], 'LR', 'L', false, 1, '', '', true, 0, false, true, 6, 'T', true );
		$this->MultiCell(75, 6, $this->item['destinatar_telefon'], 'LBR', 'L', false, 1, '', '', true, 0, false, true, 6, 'T', true );
		$this->setFont('helvetica','', 10, '');
		$this->MultiCell(75, 21.5, "Obs.: {$this->item['observatii']}\n{$this->item['detalii_doc']}", 'LBR', 'L', false, 1, '', '', true, 0, false, true, 20, 'T', true );

		$this->SetXY($x + 75, $y_temp);
		$this->setFont('helvetica','B', 14, '');
		$this->SetTextColor(255,255,255);
		$this->SetFillColor(0, 0, 0);
		$this->MultiCell(50, 10, 'NORMAL', 1, 'C', true, 1, '', '', true, 0, false, false, 10, 'M');
		$this->SetTextColor(0,0,0);
		$this->SetFillColor(255, 255, 255);
		//tip exp
		$this->setFont('helvetica','', 10, '');
		if(isset($this->item['tip_exp'])  && $this->item['tip_exp'] == 0){
			$this->MultiCell(50, 10, "INITIALA", 'LBR', 'C', true, 1, $x + 75, '', true, 0, false, true, 10, 'M');
		}
		else {
			$tip_exp = "INITIALA";
			if(isset($this->item['tip_exp'])  && $this->item['tip_exp'] == 5) $tip_exp = "RETURNARE";
 			else if(isset($this->item['tip_exp'])  && $this->item['tip_exp'] == 1) $tip_exp = "RETUR NT";
 			else if(isset($this->item['tip_exp'])  && $this->item['tip_exp'] == 2) $tip_exp = "RETUR DOC";
			else if(isset($this->item['tip_exp'])  && $this->item['tip_exp'] == 6) $tip_exp = "RETUR AMBALAJ";
			else if(isset($this->item['tip_exp'])  && $this->item['tip_exp'] == 7) $tip_exp = "RETUR COLET";
 			else if(isset($this->item['tip_exp'])  && in_array($this->item['tip_exp'], [3, 33])) {
 				$tip_exp = "RAMBURS ";
				if($this->item['tip_exp'] == 33) $tip_exp = "BORDEROU RBS ";
 				if(isset($this->item['tip_plata'])){
					if($this->item['tip_plata'] == 0) $tip_exp .= "<b>cash plic</b>";
					else if($this->item['tip_plata'] == 2) $tip_exp .= "<b>cec</b>";
					else if($this->item['tip_plata'] == 1) $tip_exp .= "<b>bo</b>";
					else if($this->item['tip_plata'] == 3) $tip_exp .= "<b>cash CC</b>";
				}
 			}
 			if(!empty($this->item['referire']) && isset($this->item['tip_exp'])  && $this->item['tip_exp'] != 0)
 				$tip_exp .= "<br/>la NT: ".$this->item['referire'];
			$this->MultiCell(50, 10, $tip_exp, 'LBR', 'C', true, 1, $x + 75, '', true, 0, false, true, 10, 'M');
		}
		$this->SetY($this->GetY() + 1);
		$this->setFont('helvetica','B', 12, '');
		//descriere
		$this->MultiCell(20, 10, $this->item['tip_obj'], 'LTB', 'C', true, 0, $x + 75, '', true, 0, false, true, 10, 'M');
		$this->MultiCell(10, 10, $this->item['piese'], 'LTB', 'C', true, 0, $x + 95, '', true, 0, false, true, 10, 'M');
		$this->MultiCell(20, 10, $this->item['greutate'] . " Kg", 'LTBR', 'C', true, 1, $x + 105, '', true, 0, false, true, 10, 'M');

		//asigurare
		$this->setFont('helvetica','', 11, '');
		if(!empty($this->item['asigurare']) && $this->item['asigurare']> 0){
			$this->MultiCell(50, 12, "Valoare declarata:<br/><b>" . number_format(round($this->item['asigurare'], 2), 2, '.', '') . "</b> {$this->item['moneda']}", 'LBR', 'C', false, 1, $x + 75, '', true, 0, true);
		}
		else {
			$this->MultiCell(50, 12, "Valoare declarata:<br/><b>---</b> {$this->item['moneda']}", 'LBR', 'C', false, 1, $x + 75, '', true, 0, true);
		}

		if(isset($this->item['tip_exp']) && $this->item['tip_exp'] == 33) {
			$this->setFont('helvetica','', 8, '');
			$this->MultiCell(50, 40, ($this->item['awb_rbs'] ?? ''), 1, 'L', true, 1, $x + 75, $this->GetY() + 0.9, true, 0, false, true, 39, 'T', true);
		}
		else {
			//serv. suplim
			$this->setFont('helvetica','B', 10, '');
			$opt = "";
			if(!empty($this->item['liv_samb'])) $opt.= " Livrare SAMBATA\n";
			if(!empty($this->item['liv_sed']) && $this->item['liv_sed'] == 1) $opt.= " Livrare SEDIU";
			$this->MultiCell(50, 15, $opt, 1, 'L', true, 1, $x + 75, $this->GetY() + 0.9, true, 0, false, true, 15, 'T');

			//serv. suplim
			$serv = "";
			if(!empty($this->item['ret_doc'])) $serv .= " Retur DOCUMENTE\n";
			if(!empty($this->item['ret_nt'])) $serv .=" Retur NT\n";
			if(!empty($this->item['ret_colet']) && $this->item['ret_colet'] == 1) $serv .= " Retur COLET\n";
			if(!empty($this->item['ret_amb']) && $this->item['ret_amb'] == 1) $serv .= " Retur AMBALAJ\n";
			if(!empty($this->item['copen'])) $serv .=" Deschidere colet\n";
			if(!empty($this->item['sms'])) $serv .=" SMS livrare\n";
			if(!empty($this->item['extrainfo'])) $serv .= " Retur NOTA COMANDA";
			$this->MultiCell(50, 24, $serv, 1, 'L', true, 1, $x + 75, $this->GetY() + 1, true, 0, false, true, 20, 'T', true);
		}
		//transport
		$this->setFont('helvetica','B', 10, '');
		$this->SetTextColor(255,255,255);
		$this->SetFillColor(0, 0, 0);
		$this->MultiCell(50, 5, 'DE INCASAT', 1, 'C', true, 1, $x + 75, $this->GetY() + 1, true, 0, false, false, 5, 'M');
		$this->SetTextColor(0,0,0);
		$this->SetFillColor(255, 255, 255);
		$yy = $this->getY();
		$this->MultiCell(50, 24.1, '', "LBR", '', true, 1, $x + 75, '');
		$this->setY($yy);
		$this->setFont('helvetica','', 9, '');
		$this->MultiCell(48, 5, "Trans. <b>" .($this->item['mod_plata'] == 0 ? ($this->item['platitor_id'] == $this->item['expeditor_id'] ? "la PRELUARE " : " la LIVRARE ") : ""). "</b>(cu TVA):", 0, 'C', true, 1, $x + 76, '', true, 0, true);
		$this->setFont('helvetica','', 11, '');
		if($this->item['mod_plata'] == 0) {
			$this->MultiCell(48, 7, "<b>".number_format(round($this->item['valoare_fara_tva']+$this->item['valoare_tva'], 2), 2, '.', '')."</b> ".$this->item['moneda'], 0, 'C', true, 1, $x + 76, '', true, 0, true);
		}
		else {
			$this->MultiCell(48, 7, "<b>---</b> ".$this->item['moneda'], 0, 'C', true, 1, $x + 76, '', true, 0, true);
		}
		$this->setFont('helvetica','', 9, '');
		$ramburs = "<br/>Ramburs: ";
		if(!empty($this->item['ramburs']) && $this->item['ramburs'] > 0 && !(isset($this->item['tip_exp'])  && $this->item['tip_exp'] == 3))
		{
			if(isset($this->item['tip_plata'])){
				if($this->item['tip_plata'] == 0) $ramburs .= "<b>cash plic</b><br/>";
				else if($this->item['tip_plata'] == 2) $ramburs .= "<b>cec</b><br/>";
				else if($this->item['tip_plata'] == 1) $ramburs .= "<b>bo</b><br/>";
				else if($this->item['tip_plata'] == 3) $ramburs .= "<b>cash CC</b><br/>";
			}
			$this->MultiCell(48, 5, $ramburs, 0, 'C', true, 1, $x + 76, '', true, 0, true);
			$this->setFont('helvetica','', 11, '');
			$this->MultiCell(48, 7, "<b>".number_format(round($this->item['ramburs'], 2), 2, '.', '')."</b> ".$this->item['moneda'], 0, 'C', true, 1, $x + 76, $this->getY() - 4, true, 0, true);
		}
		else{
			$this->MultiCell(48, 5, $ramburs, 0, 'C', true, 1, $x + 76, '', true, 0, true);
			$this->setFont('helvetica','', 11, '');
			$this->MultiCell(48, 7, "<b>---</b> ".$this->item['moneda'], 0, 'C', true, 1, $x + 76, '', true, 0, true);
		}

		$this->SetXY($x + 126, $y_temp);
		$this->SetTextColor(0,0,0);
		$this->SetFillColor(255, 255, 255);
		$this->setFont('helvetica','', 9, '');
		$data = new \DateTime($this->item['data_expeditie']);
		$this->MultiCell(35, 5, "Colectat: <b>{$data->format('d.m.Y')}</b>", 1, 'C', false, 0, $x + 126, '', true, 0, true);
		//platitor
		$platitor = "";
		if($this->item['platitor_id'] == $this->item['expeditor_id']) $platitor = "EXPEDITOR";
 		else if($this->item['platitor_id'] == $this->item['destinatar_id']) $platitor = "DESTINATAR";
 		else if($this->item['platitor_id'] != $this->item['expeditor_id'] && $this->item['platitor_id'] != $this->item['destinatar_id']) $platitor = "TERT*";
		$this->MultiCell(35, 5, "Plata: <b>{$platitor}</b>", 1, 'C', false, 1, $x + 161, '', true, 0, true);

		//de incasat
		$this->setFont('helvetica','', 9, '');
		if($this->item['mod_plata'] == 0) {
			$this->MultiCell(70, 5, "De incasat <b>" .($this->item['mod_plata'] == 0 ? ($this->item['platitor_id'] == $this->item['expeditor_id'] ? "la PRELUARE " : " la LIVRARE ") : ""). "</b>: " . "<b>".number_format(round($this->item['valoare_fara_tva']+$this->item['valoare_tva'], 2), 2, '.', '')."</b> ".$this->item['moneda'], 'LBR', 'C', false, 1, $x + 126, '', true, 0, true);
		}
		else {
			$this->MultiCell(70, 5, "De incasat --- ".$this->item['moneda'], 'LBR', 'C', false, 1, $x + 126, '', true, 0, true);
		}

		//avizare
		$this->setFont('helvetica','', 8, '');
		$this->MultiCell(70, 38, "Motiv avizare 1:\n\n\n\n\nMotiv avizare 2:", 1, 'L', false, 1, $x + 126, $this->getY() + 1);

		//confirmare primire
		$this->setFont('helvetica','B', 10, '');
		$this->SetTextColor(255,255,255);
		$this->SetFillColor(0, 0, 0);
		$this->MultiCell(70, 5, 'CONFIRMARE PRIMIRE', 1, 'C', true, 1, $x + 126, $this->GetY() + 1, true, 0, false, false, 5, 'M');
		$this->SetTextColor(0,0,0);
		$this->SetFillColor(255, 255, 255);
		$yy = $this->getY();
		$this->MultiCell(70, 59, '', "LBR", '', true, 1, $x + 126, '');
		$this->setY($yy);
		$this->setFont('helvetica','', 9, '');
		$this->MultiCell(68, 5, "Data (ZZ/LL/AAA) / Ora (HH:MM)", 0, 'C', true, 1, $x + 127, '');
		$this->Ln(6);
		$this->setFont('helvetica','', 10, '');
		$this->MultiCell(68, 7, "NUMELE SI PRENUMELE (in clar)", 0, 'C', true, 1, $x + 127, '');
		$this->Ln(8);
		$this->MultiCell(68, 7, "SEMNATURA", 0, 'L', true, 1, $x + 127, '');
		$this->Ln(12);
		$this->setFont('helvetica','', 7, '');
		$this->MultiCell(68, 4, "+ stampila (persoana juridica)", 0, 'L', true, 1, $x + 127, '');
		$this->MultiCell(68, 4, "+ serie si numar B.I./C.I. (persoana fizica)", 0, 'L', true, 1, $x + 127, '');
		$this->setFont('helvetica','', 9, '');
		$this->MultiCell(68, 5, "Am primit expedierea in perfecta stare.", 0, '', true, 1, $x + 127, '');

		$saveY = $this->GetY();
		//error_log("SAVEY: ".$saveY." : {$this->item['destinatar_rut_buc']}");
		if(!empty($this->item['destinatar_rut_buc'])){
			$this->setFont('helvetica','B', 28, '');
			$this->SetTextColor(255,255,255);
			$this->SetFillColor(0, 0, 0);

			$this->MultiCell(58, 10, '', 0, '', 0, 0, $x + 127, $saveY - 10, true, 0, false, true, 10, 'M', false);
			$this->MultiCell(11, 10, $this->item['destinatar_rut_buc'], 1, 'C', 1, 1, $this->GetX(), $saveY - 10, true, 0, false, true, 10, 'M', true);
			
			$this->SetTextColor(0,0,0);
			$this->SetFillColor(255, 255, 255);
		}

		$this->write1DBarcode(strval($this->item['awb']), 'C128A', '', $saveY, 80, 10, '', $this->pdf_style_bottom, 'N');
		$this->setFont('helvetica','', 9, '');
		$this->MultiCell(136, 10, "&nbsp;&nbsp;&nbsp;Tel.: <b>021-9501</b> &nbsp;&nbsp;&nbsp; Email: comenzi@dscexpres.ro &nbsp;-&nbsp; Web: www.dscexpres.ro", 0, '', false, 0, 70, $saveY + 2, true, 0, true);
	}

	public function makeDashedLine(string $type): void
	{
		if($type == 'H') {$x1 = 5; $y1 = 148; $x2 = 205; $y2 = 148;}
		else if($type == 'V') {$x1 = 105; $y1 = 5; $x2 = 105; $y2 = 292;}

		$this->SetLineStyle(array('width' => 0.4, 'cap' => 'butt', 'join' => 'miter', 'dash' => '1', 'color' => array(0, 0, 0)));
		$this->Line($x1, $y1, $x2, $y2, array('width' => 0.4, 'cap' => 'butt', 'join' => 'miter', 'dash' => '1', 'color' => array(0, 0, 0)));
		$this->SetLineStyle(array('width' => 0.4, 'cap' => 'butt', 'join' => 'miter', 'dash' => 0, 'color' => array(0, 0, 0)));
	}

    function limitValues(): void
	{
		$limit_array = array(
			'expeditor_nume'                => 60,
			'expeditor_adresa'              => 60,
			'expeditor_contact'            	=> 60,
			'expeditor_telefon'             => 25,
			'destinatar_nume'               => 60,
			'destinatar_adresa'             => 42,
			'destinatar_contact'            => 60,
			'destinatar_telefon'    		=> 21,
			'detalii_doc'                   => 95
		);

		foreach ($limit_array as $field=>$val){
			if(isset($this->item[$field]) && strlen($this->item[$field]) > $val){
				$this->item[$field] = substr($this->item[$field], 0 , $val)." ...";
			}
		}
    }

	public function makePuisorMultiCell(string $code, int $nr_colet, int $position = 0): void
	{
		$x = 3; $y = 5;
		if($position == 2) { $x=110; }
		else if($position == 3) { $y=150; }
		else if($position == 4) { $x=110; $y=150; }

		$this->setFont('helvetica','', 11, '');
		$this->SetX($x);
		$this->SetY($y);

		$this->item['ramburs_text'] = ' --- '.$this->item['moneda'];
		if(!empty($this->item['ramburs']) && $this->item['ramburs'] > 0 && !(isset($this->item['tip_exp'])  && $this->item['tip_exp'] == 3))
		{
			$this->item['ramburs_text'] = number_format(round($this->item['ramburs'], 2), 2, '.', '').' '.$this->item['moneda'];
		}

		$this->item['transport_text'] = "";
		$this->item['valoare_cu_tva_text'] = "(cu TVA): --- " .$this->item['moneda'];
        if($this->item['mod_plata'] == 0){
			$this->item['transport_text'] .= "la " . ($this->item['platitor_id'] == $this->item['expeditor_id'] ? "PRELUARE" : "LIVRARE");
			$valoare_cu_tva = number_format(round(floatval($this->item['valoare_fara_tva']) + floatval($this->item['valoare_tva']), 2), 2, '.', '');
			$this->item['valoare_cu_tva_text'] = "(cu TVA): {$valoare_cu_tva} {$this->item['moneda']}";
        }

		$this->item['ramburs_tip_plata'] = "";
		if(!empty($this->item['ramburs']) && $this->item['ramburs'] > 0 && isset($this->item['tip_plata']) && !(isset($this->item['tip_exp']) && $this->item['tip_exp'] == 3)) {
			if ($this->item['tip_plata'] == 0) $this->item['ramburs_tip_plata'] = 'cash plic';
			else if ($this->item['tip_plata'] == 2) $this->item['ramburs_tip_plata'] = 'cec';
			else if ($this->item['tip_plata'] == 1) $this->item['ramburs_tip_plata'] = 'bo';
			else if($this->item['tip_plata'] == 3) $this->item['ramburs_tip_plata'] = 'cash CC';
		}

		$this->item['data_expeditie_format'] = date("d.m.Y",strtotime($this->item['data_expeditie']));

		$this->item['asigurare_text'] = '--- '.$this->item['moneda'];
		if(isset($this->item['asigurare']) && $this->item['asigurare'] > 0){
			$this->item['asigurare_text'] = number_format(round($this->item['asigurare'], 2), 2, '.', '') .' '. $this->item['moneda'];
		}

		$this->item['detalii_nt'] = "";
		$detalii_nt = [];
		if(!empty($this->item['liv_samb'])) $detalii_nt[] = ' Livrare SAMBATA';
		if(!empty($this->item['liv_sed']) && $this->item['liv_sed'] == 1) $detalii_nt[] = ' Livrare SEDIU';
		
		if(!empty($this->item['ret_doc'])) $detalii_nt[] = ' Retur DOCUMENTE';
		if(!empty($this->item['ret_nt'])) $detalii_nt[] = ' Retur NT';
		if(!empty($this->item['ret_colet']) && $this->item['ret_colet'] == 1)  $detalii_nt[] =  ' Retur COLET';
		if(!empty($this->item['ret_amb']) && $this->item['ret_amb'] == 1) $detalii_nt[] = ' Retur AMBALAJ';
		if(!empty($this->item['copen'])) $detalii_nt[] = ' Deschidere colet';
		if(!empty($this->item['sms'])) $detalii_nt[] = ' SMS livrare';
		if(!empty($this->item['extrainfo'])) $detalii_nt[] = ' Retur NOTA COMANDA';
		
		if(count($detalii_nt) > 0){
			$this->item['detalii_nt'] = implode("\n", $detalii_nt);
		}

		if(!strlen(trim($this->item['destinatar_telefon']))){
			$this->item['destinatar_telefon'] = "______________";
        }

		if(!strlen(trim($this->item['destinatar_contact']))){
			$this->item['destinatar_contact'] = "______________";
		}

		$this->item['expeditor_cui'] = $this->item['expeditor_cui'] ?? "";
		$this->item['expeditor_j'] = $this->item['expeditor_j'] ?? "";

		$this->Image(ConstantsPdf::DSC_PATH, $x, $y - 1, 0, 15, 'JPG', '', '', true, 300, '', false, false, 0);

		//MultiCell( $w, $h, $txt, $border = 0, $align = 'J', $fill = 0, $ln = 1, $x = '', $y = '', 
		//			 $reseth = true,  $stretch = 3, $ishtml = false, $autopadding = true, $maxh = 0, $valign = 'M', $fitcell = true);

		$this->SetTextColor(255,255,255);
		$this->SetFillColor(0, 0, 0);

		$this->setFont('helvetica','B', 28, '');
		$this->MultiCell(10, 10, (isset($this->item['destinatar_rut_bvh']) ? $this->item['destinatar_rut_bvh'] : "0"), 1, 'C', 1, 0, $x + 24, '', true, 3, false, true, 10, 'M', true);
		$this->MultiCell(19, 10, strtoupper($this->item['destinatar_centru_cod']), 1, 'C', 1, 0, $x + 35, '', true, 3, false, true, 10, 'M', true);
		$this->setFont('helvetica','B', 14, '');
		$this->MultiCell(29, 10, $nr_colet.' din '.$this->item['piese'], 1, 'C', 1, 0, $x + 55, '', true, 3, false, true, 10, 'M', true);
		$this->setFont('helvetica','B', 28, '');
		$this->MultiCell(10, 10, (isset($this->item['destinatar_rut_buh']) ? $this->item['destinatar_rut_buh'] : "0"), 1, 'C', 1, 1, $x + 85, '', true, 3, false, true, 10, 'M', true);

		$this->SetTextColor(0,0,0);
		$this->SetFillColor(255, 255, 255);

		$this->setFont('helvetica','B', 12, '');
		$this->MultiCell(60, 6, "{$this->item['destinatar_centru']}{$this->item['destinatar_centru_zona']}", 0, 'C', 0, 0, $x, '', true, 3, false, true, 6, 'M', true);
		$this->MultiCell(35, 6, "NT: {$this->item['awb']}", 0, 'R', 0, 1, $x + 60, '', true, 3, false, true, 6, 'M', true);

		$pdf_style = $this->pdf_style;
		$pdf_style['text'] = false;
		if($position == -1) {
			$pdf_style['position'] = 'C';
			$this->write1DBarcode(strval($code), 'C128B', $x, '', 110, 25, 0.5, $pdf_style); // cod header
		}
		$this->write1DBarcode(strval($code), 'C128B', ($x + ($nr_colet == 1 ? 12 : 0)), '', 110, 25, 0.5, $pdf_style); // cod header
		$this->SetY($this->GetY() + 22);
		$this->setFont('helvetica','B', 12, '');
		$this->MultiCell(95, 6, "{$code}", 0, 'C', 0, 0, $x, '', true, 3, false, true, 6, 'M', true);

		//expeditor
		$this->setFont('helvetica','', 8, '');
		$this->MultiCell(95, 7, "Expeditor:", 0, 'L', 0, 1, $x, '', true, 3, false, false, 7, 'B', true);
		$this->setFont('helvetica','B', 8, '');
		$this->MultiCell(95, 4, $this->item['expeditor_nume'], 0, 'L', 0, 1, $x, '', true, 3, false, false, 4, 'M', true);
		$this->setFont('helvetica','', 8, '');
		$this->item['expeditor_cui'] = $this->item['expeditor_cui'] ?? "";
		$this->item['expeditor_j'] = $this->item['expeditor_j'] ?? "";
		$this->MultiCell(95, 4, "CIF : {$this->item['expeditor_cui']} {$this->item['expeditor_j']}", 0, 'L', 0, 1, $x, '', true, 3, false, false, 4, 'T', true);
		$this->MultiCell(95, 8, "{$this->item['expeditor_adresa']} / Contact: {$this->item['expeditor_contact']} / Tel: {$this->item['expeditor_telefon']}", 
						0, 'L', 0, 1, $x, '', true, 3, false, false, 8, 'T', true);
		$this->MultiCell(95, 4, "Loc: <b>{$this->item['expeditor_localitate']} ({$this->item['expeditor_judet_id']})</b>, C.O.: <b>{$this->item['expeditor_centru_cod']}</b>", 
						0, 'L', 0, 1, $x, '', true, 3, true, false, 4, 'T', true);

		//left destinatar
		$saveY = $this->GetY();

		$this->setFont('helvetica','', 8, '');
		$this->MultiCell(55, 5, "Destinatar:", 0, 'L', 0, 1, $x, '', true,  3, false, false, 5, 'B', true);
		$this->setFont('helvetica','B', 13, '');
		$this->MultiCell(55, 6, $this->item['destinatar_nume'], 0, 'L', 0, 1, $x, '', true, 3, false, true, 6, 'T', true);
		$this->setFont('helvetica','', 10, '');
		$this->MultiCell(55, 6, $this->item['destinatar_adresa'], 0, 'L', 0, 1, $x, '', true, 3, false, false, 12, 'T', true);

		$this->setFont('helvetica','B', 10, '');
		$this->item['destinatar_localitate'] = strtoupper($this->item['destinatar_localitate']);
		$this->MultiCell(55, 5, $this->item['destinatar_localitate']." ({$this->item['destinatar_judet_id']})", 0, 'L', 0, 1, $x, '', true, 3, false, true, 5, 'T', true);
		$this->setFont('helvetica','', 8, '');
		$this->MultiCell(55, 5, "Contact: {$this->item['destinatar_contact']}", 0, 'L', 0, 1, $x, '', true, 3, false, false, 5, 'T', true);
		$this->MultiCell(55, 5, "Tel: {$this->item['destinatar_telefon']}", 0, 'L', 0, 1, $x, '', true, 3, false, false, 5, 'T', true);

		$this->MultiCell(55, 45, "", 1, 'L', 0, 1, $x, $saveY); //box destinatar
		//left desc
		$this->setFont('helvetica','', 8, '');
		$this->MultiCell(55, 11, "Desc.: {$this->item['detalii_doc']}", 1, 'L', 0, 0, $x, '', true,  3, false, true, 11, 'T', true);

		//right details
		$this->SetY($saveY);

		$this->MultiCell(40, 5, "Colectat: <strong>{$this->item['data_expeditie_format']}</strong>", 1, 'C', 0, 1, $x + 55, '', true, 3, true, true, 5, 'M', true);
		$this->setFont('helvetica','B', 10, '');
		$this->MultiCell(14, 5, "{$this->item['tip_obj']}", 1, 'C', 0, 0, $x + 55, '', true, 3, false, true, 5, 'M', true);
		$this->MultiCell(13, 5, "{$this->item['piese']}", 1, 'C', 0, 0, $x + 69, '', true, 3, false, true, 5, 'M', true);
		$this->MultiCell(13, 5, "{$this->item['greutate']} Kg", 1, 'C', 0, 1, $x + 82, '', true, 3, false, true, 5, 'M', true);
		$this->setFont('helvetica','', 8, '');
		$this->MultiCell(40, 5, "Asigurare: {$this->item['asigurare_text']}", 1, 'C', 0, 1, $x + 55, '', true, 3, false, true, 5, 'M', true);
		$this->setFont('helvetica','B', 8, '');
		$this->MultiCell(40, 26, "{$this->item['detalii_nt']}", 1, 'L', 0, 1, $x + 55, '', true, 3, false, true, 19, 'M', true);
		//de incasat
		$this->setFont('helvetica','B', 12, '');
		$this->MultiCell(40, 5, "DE INCASAT {$this->item['transport_text']}", 'TR', 'C', 0, 1, $x + 55, '', true, 3, false, true, 5, 'M', true);
		$this->MultiCell(40, 5, "{$this->item['valoare_cu_tva_text']}", 0, 'C', 0, 1, $x + 55, '', true, 3, false, true, 5, 'M', true);
		$this->MultiCell(40, 5, "RBS: {$this->item['ramburs_text']} {$this->item['ramburs_tip_plata']}", 0, 'C', 0, 1, $x + 55, '', true, 3, false, true, 5, 'M', true);

		$this->MultiCell(40, 56, "", 1, 'L', 0, 1, $x + 55, $saveY); //box details

		//$this->MultiCell( $w, $h, $txt, $border = 0, $align = 'J', $fill = 0, $ln = 1, $x = '', $y = '',
						//$reseth = true,  $stretch = 3, $ishtml = false, $autopadding = true, $maxh = 0, $valign = 'M', $fitcell = true);

		//observatii
		$this->setFont('helvetica','', 8, '');
		$this->MultiCell(95, 10, "", 1, 'L', 0, 0, $x);
		$this->MultiCell(85, 10, "Obs.: {$this->item['observatii']}", 0, 'L', 0, 0, $x, '', true,  3, false, true, 10, 'T', true);

		$this->SetTextColor(255,255,255);
		$this->SetFillColor(0, 0, 0);

		$this->setFont('helvetica','B', 28, '');
		$this->MultiCell(10, 10, !empty($this->item['destinatar_rut_buc']) ? $this->item['destinatar_rut_buc'] : "", 1, 'C', 1, 1, $x + 85, '', true, 3, false, true, 10, 'M', true);
			
		$this->SetTextColor(0,0,0);
		$this->SetFillColor(255, 255, 255);

		$this->setFont('helvetica','', 7, '');
		$this->MultiCell(70, 5, "COMENZI@DSCEXPRES.RO - WWW.DSCEXPRES.RO", 0, 'C', 0, 0, $x, '', true,  3, false, true, 5, 'M', true);
		$this->setFont('helvetica','B', 16, '');
		$this->MultiCell(95, 6, "021-9501", 0, 'R', 0, 0, $x, $this->GetY() - 1, true,  3, false, true, 6, 'M', true);

		if($position == -1) {
			$this->write1DBarcode(strval($code), 'C128B', '', $this->GetY() + 4, 100, 10, 0.4, $pdf_style); // cod footer
		} else {
			$this->write1DBarcode(strval($code), 'C128B', $x, $this->GetY() + 4, 100, 10, 0.4, $pdf_style); // cod footer
		}
	}

	public function makePuisorMultiCellPrintAwb3(string $code, int $nr_colet): void
	{

		$x=14;
		$y=18;
		$width = $this->getPageWidth()-26;
		$height = $this->getPageHeight()-22;

		//barcode
		//$this->MultiCell(0, 16, '', 0, '', 0, 1, '', '', false, 0, false, false, 16, '', false);
		$this->pdf_style['position'] = 'C';
		$this->write1DBarcode(strval($code), 'C128A', '', '', '', 20, 0.5, $this->pdf_style, 'N');

		$this->setFont('helvetica','B', 26, '');
		$this->setXY(0,$this->getPageHeight());
		$this->StartTransform();
		$this->Rotate(90);
		$this->Cell($this->getPageHeight() - $x - 4, 15, $this->item['destinatar_centru'], 0, 1, 'C', 0, '', 1);
		$this->StopTransform();
		
		// expeditor
		$this->setXY($x,$y);
		$this->SetLineWidth(0.4);
		$this->MultiCell($width-2, intval($height/2), '', 1, 'C', 1, 0, '', '', true, 0, false, false, intval($height/2), 'T', false);

		$this->setFont('helvetica','B', 24, '');
		$this->MultiCell(0, intval($height/2), $nr_colet, 0, 'C', 0, 1, '', '', true, 0, false, false, 18, 'M', true);

		$this->setXY($x+1,$y+1);
		$this->setFont('helvetica','B', 9, '');
		$this->SetLineWidth(0.2);
		$this->MultiCell($width-4, 4, 'EXPEDITOR: '.$this->item['expeditor_nume'], 1, 'L', 0, 1, '', '', true, 0, false, false, 4, 'M', true);

		$this->setFont('helvetica','', 9, '');
		$this->setX($x+1);
		$this->item['expeditor_cui'] = $this->item['expeditor_cui'] ?? "";
		$this->item['expeditor_j'] = $this->item['expeditor_j'] ?? "";
		$this->MultiCell($width-4, 4, "CIF : {$this->item['expeditor_cui']} {$this->item['expeditor_j']}", 0, 'L', false, 1, '', '', true, 0, false, false, 4, 'T', true);
		$this->setX($x+1);
		$this->MultiCell($width-4, 4, 'Adresa: '.$this->item['expeditor_adresa'], 0, 'L', 0, 1, '', '', true, 0, false, false, 4, 'T', true);
		$this->setX($x+1);
		$this->MultiCell($width-4, 4, 'Contact: '.(empty($this->item['expeditor_contact'])?'':$this->item['expeditor_contact']).(empty($this->item['expeditor_telefon'])?'':(' / '.$this->item['expeditor_telefon'])), 0, 'L', 0, 1, '', '', true, 0, false, false, 4, 'T', true);

		$this->setFont('helvetica','B', 9, '');
		$this->setX($x+1);
		$this->MultiCell(intval(2*$width/3), 5, $this->item['expeditor_localitate'].'('.$this->item['expeditor_judet_id'].')', 0, 'L', 0, 0, '', '', true, 0, false, false, 5, 'B', true);
		$this->MultiCell(intval($width/3)-1, 5, $this->item['expeditor_centru'], 'L', 'L', 0, 0, '', '', true, 0, false, false, 5, 'B', true);
		$this->setXY($width+$x-2,intval($height/2) +$y - 4);
		$this->MultiCell(14, 5, 'din', 0, 'C', 0, 1, '', '', true, 0, false, false, 5, 'B', true);

		$y=$height/2+$y;
		// destinatar
		$this->setXY($x,$y);
		$this->SetLineWidth(0.4);
		$this->MultiCell($width-2, intval($height/2), '', 1, 'C', 1, 0, '', '', true, 0, false, false, intval($height/2), 'T', false);

		$this->setFont('helvetica','B', 24, '');
		$this->MultiCell(0, intval($height/2), $this->item['piese'], 0, 'C', 0, 1, '', '', true, 0, false, false, intval($height/2), 'M', true);

		$this->setXY($x+1,$y+1);
		$this->setFont('helvetica','B', 9, '');
		$this->SetLineWidth(0.2);
		$this->MultiCell($width-4, 5, 'DESTINATAR : '.$this->item['destinatar_nume'], 1, 'L', 0, 1, '', '', true, 0, false, false, 5, 'M', true);

		$this->setFont('helvetica','', 9, '');
		$this->setX($x+1);
		$this->MultiCell($width-4, 5, 'Adresa: '.$this->item['destinatar_adresa'], 0, 'L', 0, 1, '', '', true, 0, false, false, 5, 'T', true);
		$this->setX($x+1);
		$this->MultiCell($width-4, 5, 'Contact: '.(empty($this->item['destinatar_contact'])?'':$this->item['destinatar_contact']).(empty($this->item['destinatar_telefon'])?'':(' / '.$this->item['destinatar_telefon'])), 0, 'L', 0, 1, '', '', true, 0, false, false, 5, 'T', true);

		$this->setFont('helvetica','B', 9, '');
		$this->setX($x+1);
		$this->MultiCell(intval(2*$width/3), 5, $this->item['destinatar_localitate'].'('.$this->item['destinatar_judet_id'].')', 0, 'L', 0, 0, '', '', true, 0, false, false, 5, 'B', true);
		$this->MultiCell(intval($width/3)-1, 5, $this->item['destinatar_centru'].$this->item['destinatar_centru_zona'], 'L', 'L', 0, 1, '', '', true, 0, false, false, 5, 'B', true);

		$this->SetY(-5);
        $this->SetFont('helvetica', '', 6);
        $this->Cell(0, 5, ConstantsPdf::DSC_LABEL, 0, false, 'C', 0, '', 0, false, 'T', 'M');
	}

	public function makeNotaComanda(int $xi, int $yi): void
	{
		$x = $xi + 6;
		$y = $yi + 10;

		if(empty($this->item['extrainfo'])) return;

		$extrainfo = explode('|',$this->item['extrainfo']);
		$largeinfo = [];
		if(!empty($this->item['largeinfo'])) $largeinfo =  explode('|',$this->item['largeinfo']);

		$this->setFont('helvetica','B', 20, '');
		$this->MultiCell(118, 9, 'RETUR LADA FRIG SI PATROANE', 0, 'C', 0, 0, $x, $y, true, 0, false, false, 9, 'M', true);
		$this->setFont('helvetica','B', 14, '');
		$this->MultiCell(80, 9, 'UNDE ESTE CAZUL!
VEZI DESCRIEREA CONTINUTULUI', 0, 'C', 0, 0, $x+120, $y, true, 0, false, false, 9, 'M', true);

		$this->MultiCell(198, 130, '', 1, 'L', 0, 0, $x, $y+10, true, 0, false, false, 58, 'T', true);
		$this->setFont('helvetica','', 8, '');
		$topinfo = '';
		foreach($extrainfo as $einfo) $topinfo .=trim($einfo).'
';
		$this->MultiCell(100, 36, $topinfo, 0, 'L', 0, 1, $x+1, $y+11, true, 0, false, false, 36, 'T', true);
		if(!empty($this->item['awb']))
			$this->write1DBarcode(strval($this->item['awb']), 'C128A', $x+149, $y+15, '', 18, 0.4, $this->pdf_style, 'N');

		$this->setFont('helvetica','B', 10, '');
		$this->MultiCell(196, 10, 'NOTA DE COMANDA
Catre: '.strtoupper($this->item['expeditor_nume']), 0, 'C', 0, 1, $x+1, $y+47, true, 0, false, false, 10, 'T', true);

		$this->setFont('helvetica','B', 8, '');
		$this->MultiCell(88, 6, 'Denumire produs - Cantitate', 0, 'L', 0, 1, $x+10, $y+58, true, 0, false, false, 6, 'T', true);

		$this->setFont('helvetica','', 8, '');
		$midinfo1 = '';
		$midinfo2 = '';
		$twocols = false;
		$nb_largeinfo = count($largeinfo);
		if($nb_largeinfo > 15) $twocols = round($nb_largeinfo/2);
		if($twocols)
		{
			for($i=0; $i<$twocols;$i++) $midinfo1 .=trim($largeinfo[$i]).'
';
			for($i=$twocols; $i<$nb_largeinfo;$i++) $midinfo2 .=trim($largeinfo[$i]).'
';
			$this->MultiCell(88, 6, 'Denumire produs - Cantitate', 0, 'L', 0, 1, $x+109, $y+58, true, 0, false, false, 6, 'T', true);
		}
		else
			foreach($largeinfo as $linfo) $midinfo1 .=trim($linfo).'
';
		$this->MultiCell(88, 65, $midinfo1, 0, 'L', 0, 0, $x+10, $y+63, true, 0, false, false, 75, 'T', true);
		$this->MultiCell(88, 65, $midinfo2, 0, 'L', 0, 0, $x+109, $y+63, true, 0, false, false, 75, 'T', true);

		$this->MultiCell(45, 7, 'Data:__________________', '', 'L', 0, 1, $x+1, $y+130, true, 0, false, false, 7, 'B', true);
		$this->MultiCell(85, 7, 'Numele reprezentantului societatii:___________________________', '', 'L', 0, 1, $x+46, $y+130, true, 0, false, false, 7, 'B', true);
		$this->MultiCell(65, 7, 'Semnatura si stampila unitatii:____________________', '', 'L', 0, 1, $x+131, $y+130, true, 0, false, false, 7, 'B', true);
	}

	//Borderou Rbs cash
    
    public function makeBoRbsCashTh(): void
	{
		$x = 10;
		$this->setY(40);
		$this->setFont('helvetica','B', 9, '');
        
        $this->MultiCell(20, 5, 'AWB initiala', 'LB', 'C', 0, 0, $x, null, true, 0, false, false, 5, 'M', true);
		$this->MultiCell(20, 5, 'AWB rbs', 'LB', 'C', 0, 0, $x+20, null, true, 0, false, false, 5, 'M', true);
		$this->MultiCell(20, 5, 'Ramburs', 'LB', 'C', 0, 0, $x+40, null, true, 0, false, false, 5, 'M', true);
		$this->MultiCell(10, 5, 'Tip plata', 'LB', 'C', 0, 0, $x+60, null, true, 0, false, false, 5, 'M', true);
		$this->MultiCell(60, 5, 'Expeditor', 'LB', 'C', 0, 0, $x+70, null, true, 0, false, false, 5, 'M', true);
		$this->MultiCell(30, 5, 'Localitate', 'LB', 'C', 0, 0, $x+130, null, true, 0, false, false, 5, 'M', true);
		$this->MultiCell(10, 5, 'Judet', 'LBR', 'C', 0, 1, $x+160, null, true, true, false, false, 5, 'M', true);
		//$this->MultiCell(30, 5, 'Tip plata', 'LB', 'L', 0, 0, $x+170, null, true, 0, false, false, 5, 'M', true);
		//$this->MultiCell(30, 5, 'Platitor', 'LB', 'L', 0, 0, $x+200, null, true, 0, false, false, 5, 'M', true);

    }
    
    public function makeBoRbsCashTr(int $rbs, int $initiala, float $asigurare, int $tip_plata = 0, string $expeditor_nume, string $expeditor_localitate, string $expeditor_judet, string $expeditor_contact = '', string $expeditor_telefon = ''): void
	{
		$x = 10;
		$expeditor_nume = strtoupper(htmlspecialchars_decode(strtolower($expeditor_nume), ENT_QUOTES));
        $expeditor_contact = strtoupper(htmlspecialchars_decode(strtolower($expeditor_contact ?? ''), ENT_QUOTES));
        $expeditor_telefon = strtoupper(htmlspecialchars_decode(strtolower($expeditor_telefon ?? ''), ENT_QUOTES));
        $ramburs = number_format(round($asigurare, 2), 2, '.', '');

		$this->setFont('helvetica','', 8, '');
		$this->MultiCell(20, 5, $initiala, 'LB', 'L', 0, 0, $x, '', true, 0, false, false, 5, 'M', false);
		$this->MultiCell(20, 5, $rbs, 'LB', 'L', 0, 0, $x+20, '', true, 0, false, false, 5, 'M', false);
		$this->MultiCell(20, 5, $ramburs, 'LB', 'R', 0, 0, $x+40, '', true, 0, false, false, 5, 'M', false);
		$this->MultiCell(10, 5, "cash plic", 'LB', 'C', 0, 0, $x+60, '', true, 0, false, false, 5, 'M', false);
		$this->MultiCell(60, 5, $expeditor_nume, 'LB', 'L', 0, 0, $x+70, '', true, 0, false, false, 5, 'M', false);
		$this->MultiCell(30, 5, $expeditor_localitate, 'LB', 'L', 0, 0, $x+130, '', true, 0, false, false, 5, 'M', false);
		$this->MultiCell(10, 5, $expeditor_judet, 'LBR', 'L', 0, 1, $x+160, '', true, 0, false, false, 5, 'M', false);
		//$this->MultiCell(30, 5, $expeditor_contact, 'B', 'L', 0, 0, $x+170, '', true, 0, false, false, 5, 'M', false);
		//$this->MultiCell(30, 5, $expeditor_telefon, 'B', 'L', 0, 0, $x+200, '', true, 0, false, false, 5, 'M', false);
    }
}