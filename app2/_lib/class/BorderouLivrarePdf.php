<?php

require_once __DIR__."/../../vendor/tecnickcom/tcpdf/tcpdf.php";
require_once "ConstantsPdf.php";

class BorderouLivrarePdf extends TCPDF
{

	private $vars;
	public $pdf_style = [];

	function __construct($vars=null) {
    	parent::__construct('L', 'mm', 'A4', false, 'ISO-8859-1', false);

    	$this->vars = $vars;
		// set document information
		$this->SetCreator(PDF_CREATOR);
		$this->SetAuthor(ConstantsPdf::DSC_LABEL);
		$this->SetTitle('BorderouLivrare-'.$this->vars['borderou']);
		$this->SetSubject('Borderou');
		$this->SetKeywords('curierat, dragon, star, expeditie, PDF, awb, borderou');

		// remove default footer
		$this->setPrintHeader(true);
		$this->setPrintFooter(false);
		$this->setHeaderFont(Array('times', '', '12'));
		//$this->setFooterFont(Array('times', '', '10'));

		// set default monospaced font
		$this->SetDefaultMonospacedFont('courier');
		$this->setFontSubsetting(false);

		// set margins
		$this->SetMargins(10, 10, 10);
		// set JPEG quality
		$this->setJPEGQuality(75);

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

		$this->SetLineWidth(0.2);
	}

	public function Header() {
		$total = 'Total: '.$this->vars['total_expeditii'].' exp / '.$this->vars['total_piese_scanate'].' piese / '.$this->vars['total_greutate'].' Kg';
		$x = 6;
		$y = 6;
    	$this->setFont('times','B', 7, '');
        $this->MultiCell(60, 5, 'BO. LIV. NR: '.$this->vars['borderou'], 0, 'L', 0, 0, $x, $y, true, 0, false, false, 5, '', true);
        $this->MultiCell(30, 5, 'Data: '.$this->vars['data'], 0, 'L', 0, 0, $x+60, $y, true, 0, false, false, 5, '', true);
		$this->MultiCell(30, 5, 'Zona: ', 0, 'L', 0, 0, $x+90, $y, true, 0, false, false, 5, '', true);
		$this->MultiCell(40, 5, 'Curier: '.$this->vars['curier'], 0, 'L', 0, 0, $x+110, $y, true, 0, false, false, 5, '', true);
		$this->MultiCell(50, 5, $total, 0, 'L', 0, 0, $x+150, $y, true, 0, false, false, 5, '', true);
		$this->Image(ConstantsPdf::DSC_PATH, $x+200, $y-2, 20, 0, 'JPG', '', 'LTR', false, 300, '', false, false, 0, '', false, false);

		$this->setFont('times','', 6, '');
		$this->MultiCell(72, 10, ConstantsPdf::DSC_ADRESA_ONLY, 0, 'R', 0, 1, $x + 200, $y, true, 0, false, false, 10, 'T', true);
		$this->makeTH();
		$this->SetTopMargin(26);

		$pagx = 303 - 40;
		$this->setFont('times','B', 8, '');
		$this->MultiCell(40, 5, 'Pag. '.$this->getAliasNumPage().' / '.$this->getAliasNbPages(), 0, 'R', 0, 0, $pagx, $y, true, 0, false, false, 5, '', true);

	}


    private function makeTH()
	{
		$x = 6;
		$y = $this->GetY() - 4;
		$this->setFont('times','B', 10, '');
		$this->MultiCell(58, 14, 'NT (nr Nota Transport)', 'LBT', 'C', 0, 0, $x, $y, true, 0, false, false, 14, 'M', true);
		$this->setFont('times','B', 10, '');
		$this->MultiCell(58, 7, ' DESTINATAR', 'LBT', 'L', 0, 0, $x+58, $y, true, 0, false, false, 7, 'M', true);
		$this->setFont('times','', 10, '');
		$this->MultiCell(58, 7, ' ORAS, ADRESA, ZONA', 'LB', 'L', 0, 0, $x+58, $y+7, true, 0, false, false, 7, 'M', true);

		$this->setFont('times','', 8, '');
		$this->MultiCell(36, 7, 'Persoana contact / Telefon', 'LBT', 'C', 0, 0, $x+116, $y, true, 0, false, false, 7, 'M', true);
		$this->MultiCell(12, 7, 'Piese din total', 'LB', 'C', 0, 0, $x+116, $y+7, true, 0, false, false, 7, 'M', true);
		//$this->MultiCell(12, 7, 'KG', 'LB', 'C', 0, 0, $x+128, $y+7, true, 0, false, false, 7, 'M', true);
		$this->MultiCell(12, 7, '', 'LB', 'C', 0, 0, $x+128, $y+7, true, 0, false, false, 7, 'M', true);
		$this->MultiCell(12, 7, 'Tip
expeditie', 'LB', 'C', 0, 0, $x+140, $y+7, true, 0, false, false, 7, 'M', true);

		$this->MultiCell(20, 7, 'CV Trans', 'LBT', 'C', 0, 1, $x+152, $y, true, 0, false, false, 7, 'M', true);
		$this->setFont('times','B', 8, '');
		$this->MultiCell(20, 7, 'Val RBS
Tip RBS', 'LB', 'C', 0, 0, $x+152, $y+7, true, 0, false, false, 7, 'M', true);
		$this->setFont('times','B', 10, '');

		$this->MultiCell(8, 4.6, 'RN', 'LBT', 'C', 0, 0, $x+172, $y, true, 0, false, false, 4.6, 'M', true);
		$this->MultiCell(8, 4.6, 'RD', 'L', 'C', 0, 0, $x+172, $y+4.6, true, 0, false, false, 4.6, 'M', true);
		$this->MultiCell(8, 4.6, 'RA', 'LBT', 'C', 0, 0, $x+172, $y+9.3, true, 0, false, false, 4.6, 'M', true);

		$this->setFont('times','B', 8, '');
		$this->MultiCell(8, 14, 'CKPT', 'LBT', 'C', 0, 0, $x+180, $y, true, 0, false, false, 14, 'M', true);
		$this->MultiCell(8, 14, 'ORA', 'LBT', 'C', 0, 0, $x+188, $y, true, 0, false, false, 14, 'M', true);

		$this->setFont('times','B', 10, '');
		$this->MultiCell(54, 7, 'PRIMIT DE', 'LT', 'C', 0, 0, $x+196, $y, true, 0, false, false, 7, 'M', true);
		$this->setFont('times','', 8, '');
		$this->MultiCell(54, 7, '(nume, prenume si serie BI/CI)', 'LB', 'C', 0, 0, $x+196, $y+7, true, 0, false, false, 7, 'M', true);
		$this->MultiCell(34, 14, 'Semnatura (+stampila persoane juridice)
Certific receptia expedierii in buna stare', 'LBTR', 'C', 0, 1, $x+250, $y, true, 0, false, false, 14, 'M', true);
		//$this->MultiCell(284, 5, 'Observatii', 'LBR', 'C', 0, 1, $x, '', true, 0, false, false, 5, 'M', true);
    }

    public function makeTR($var)
	{
		$x = 6;
		$y = $this->GetY();
		$this->setFont('times','B', 10, '');
		$this->MultiCell(58, 14, '', '', 'C', 0, 0, $x, $y, true, 0, false, false, 14, 'M', true);
		try{
			if(!empty($var['expeditie']))
				$this->write1DBarcode(strval($this->cleanBarcode($var['expeditie'])), 'C128A', $x-2, $y, '', 13, 0.4, $this->pdf_style, 'T');
		} catch (Exception $ex){
			error_log("Eroare cod bare:".$this->cleanBarcode($var['expeditie']));
		}

		$this->MultiCell(58, 7, $var['client'], 'B', 'L', 0, 0, $x+58, $y, true, 0, false, false, 7, 'M', true);
		$this->setFont('times','', 10, '');
		$this->MultiCell(58, 7, $var['adresa'], 'B', 'L', 0, 0, $x+58, $y+7, true, 0, false, false, 7, 'M', true);

		$this->setFont('times','', 10, '');
		$this->MultiCell(36, 7, $var['contact'], 'LB', 'C', 0, 0, $x+116, $y, true, 0, false, false, 7, 'M', true);
		$this->setFont('times','', 8, '');
		$this->MultiCell(12, 7, $var['piese_scanate'].' / '.$var['piese'], 'LB', 'C', 0, 0, $x+116, $y+7, true, 0, false, false, 7, 'M', true);
		$this->MultiCell(12, 7, $var['greutate'], 'LB', 'C', 0, 0, $x+128, $y+7, true, 0, false, false, 7, 'M', true);
		$this->MultiCell(12, 7, $var['tip_obj'], 'LB', 'C', 0, 0, $x+140, $y+7, true, 0, false, false, 7, 'M', true);

		$this->setFont('times','', 8, '');
		$this->MultiCell(20, 7, $var['numerar'], 'LB', 'C', 0, 1, $x+152, $y, true, 0, false, false, 7, 'M', true);



		$this->setFont('times','B', 8, '');
		$this->MultiCell(20, 7, $var['ramburs'].'
'.$var['tip_plata'], 'LB', 'C', 0, 0, $x+152, $y+7, true, 0, false, false, 7, 'M', true);
		$this->setFont('times','B', 10, '');
		/*
		$this->MultiCell(8, 4.6, $var['ret_nt'], 'LB', 'C', 0, 0, $x+172, $y, true, 0, false, false, 4.6, 'M', true);
		$this->MultiCell(8, 4.6, $var['ret_doc'], 'LB', 'C', 0, 0, $x+172, $y+4.6, true, 0, false, false, 4.6, 'M', true);
		$this->MultiCell(8, 4.6, $var['ret_amb'], 'LB', 'C', 0, 0, $x+172, $y+9.3, true, 0, false, false, 4.6, 'M', true);
		*/
		$this->MultiCell(8, 4.6, $var['ret_nt'], 'LB', 'C', 0, 0, $x+172, $y, true, 0, false, false, 4.6, 'M', true);
		$this->MultiCell(8, 4.6, $var['ret_doc'], 'L', 'C', 0, 0, $x+172, $y+4.6, true, 0, false, false, 4.6, 'M', true);
		$this->MultiCell(8, 4.6, $var['ret_amb'], 'LTB', 'C', 0, 0, $x+172, $y+9.3, true, 0, false, false, 4.69, 'M', true);
		$this->MultiCell(8, 4.6, $var['ret_colet'], 'LTB', 'C', 0, 0, $x+172, $y+9.3, true, 0, false, false, 4.69, 'M', true);

		$this->MultiCell(8, 14, '', 'LB', 'C', 0, 0, $x+180, $y, true, 0, false, false, 14, 'M', true);
		$this->MultiCell(8, 14, '', 'LB', 'C', 0, 0, $x+188, $y, true, 0, false, false, 14, 'M', true);

		$this->MultiCell(54, 14, '', 'LB', 'C', 0, 0, $x+196, $y, true, 0, false, false, 14, 'M', true);
		$this->MultiCell(34, 14, '', 'LBR', 'C', 0, 1, $x+250, $y, true, 0, false, false, 14, 'M', true);
		$this->setFont('times','', 8, '');
		$detalii = trim((isset($var['detalii_doc'])?$var['detalii_doc'].' ':'').(isset($var['observatii'])?$var['observatii']:''));
		$detalii = substr($detalii, 0 ,238);

		$this->MultiCell(284, 17, $detalii, 'BR', 'L', 0, 1, $x, $y, true, 0, false, false, 31, 'M', false);


    }


	public function makeTROld($var)
	{
		$x = 6;
		$y = $this->GetY();
		$this->setFont('times','B', 10, '');
		$this->MultiCell(54, 14, '', 'B', 'C', 0, 0, $x, $y, true, 0, false, false, 14, 'M', true);
		$this->write1DBarcode(strval($var['expeditie']), 'C128A', $x-2, $y, '', 13, 0.4, $this->pdf_style, 'T');
		$this->MultiCell(62, 7, $var['client'], 'B', 'L', 0, 0, $x+54, $y, true, 0, false, false, 7, 'M', true);
		$this->setFont('times','', 10, '');
		$this->MultiCell(62, 7, $var['adresa'], 'B', 'L', 0, 0, $x+54, $y+7, true, 0, false, false, 7, 'M', true);

		$this->setFont('times','', 10, '');
		$this->MultiCell(36, 7, $var['contact'], 'LB', 'C', 0, 0, $x+116, $y, true, 0, false, false, 7, 'M', true);
		$this->setFont('times','', 8, '');
		$this->MultiCell(12, 7, $var['piese_scanate'].' / '.$var['piese'], 'LB', 'C', 0, 0, $x+116, $y+7, true, 0, false, false, 7, 'M', true);
		$this->MultiCell(12, 7, $var['greutate'], 'LB', 'C', 0, 0, $x+128, $y+7, true, 0, false, false, 7, 'M', true);
		$this->MultiCell(12, 7, $var['tip_obj'], 'LB', 'C', 0, 0, $x+140, $y+7, true, 0, false, false, 7, 'M', true);

		$this->setFont('times','', 8, '');
		$this->MultiCell(20, 7, $var['numerar'], 'LB', 'C', 0, 1, $x+152, $y, true, 0, false, false, 7, 'M', true);



		$this->setFont('times','B', 8, '');
		$this->MultiCell(20, 7, $var['ramburs'].'
'.$var['tip_plata'], 'LB', 'C', 0, 0, $x+152, $y+7, true, 0, false, false, 7, 'M', true);
		$this->setFont('times','B', 10, '');
		$this->MultiCell(8, 7, $var['ret_nt'], 'LB', 'C', 0, 0, $x+172, $y, true, 0, false, false, 7, 'M', true);
		$this->MultiCell(8, 7, $var['ret_doc'], 'LB', 'C', 0, 0, $x+172, $y+7, true, 0, false, false, 7, 'M', true);
		$this->MultiCell(8, 14, '', 'LB', 'C', 0, 0, $x+180, $y, true, 0, false, false, 14, 'M', true);
		$this->MultiCell(8, 14, '', 'LB', 'C', 0, 0, $x+188, $y, true, 0, false, false, 14, 'M', true);

		$this->MultiCell(54, 14, '', 'LB', 'C', 0, 0, $x+196, $y, true, 0, false, false, 14, 'M', true);
		$this->MultiCell(34, 14, '', 'LBR', 'C', 0, 1, $x+250, $y, true, 0, false, false, 14, 'M', true);

	}

    public function makeTotal($total_expeditii, $total_piese_scanate, $total_greutate, $total_ramburs)
    {
    	$this->setHeaderData ('',0,'','Total: '.$total_expeditii.' exp / '.$total_piese_scanate.' piese / '.$total_greutate.' Kg');
    }

    public function addProcessVerbal($vars){
        $x = 30;
        $y = 6;

        $orientation = "P";
        $width = 170;
        $height = 250;

        if($orientation == "L"){

            $width = 263;
            $height = 301;
        }

        $this->setPageFormat($this->pdf_style, $orientation);
        $data = $this->vars['data'];
        $data = explode(" /",$data);
        $data = trim($data[0]);
        $ora = trim($data[1]);
        $vars['data_scanare'] = $data;
        $vars['ora'] = $ora;
        $vars['centru'] = $vars['centru'];
        $this->vars = $vars;
        $tbl = file_get_contents(ConstantsPdf::PDF_TPL.'proces_verbal.html');
        $tbl = $this->replaceVars($tbl);
        $this->setFont('times','', 12, '');
        $this->writeHTMLCell($width, $height, $x, $y, $tbl, 0, 0, false, 'L', false);

        $this->writeHTMLCell(60, 150, $x, $height , "<p align='center'>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;Am primit</p><p align='center'>____________________<br><br>____________________</p>", 0, 0, true, 'C', true);
        $this->writeHTMLCell(60, 150, $width - 17, $height, "<p align='center'>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;Am predat</p><p align='center'>____________________<br><br>____________________</p>", 0, 0, true, 'C', true);
        // $this->writeHTMLCell(30, 120, $width - 17, $height, "<p align='center'>&nbsp;&nbsp;&nbsp;Data</p><p align='center'>{$data}</p>", 0, 0, true, 'C', true);

    }

	public function addCMR($vars){

        $orientation = "P";
        $width = 190;
        $height = 270;

        $this->setPageFormat($this->pdf_style, $orientation);
        $this->Image(ConstantsPdf::CMR_PATH, '', '', $width, $height, 'JPG');
    }

    private function replaceVars($tbl){

        preg_match_all("/{(.[a-z_]*)}/", $tbl, $output_array);

        foreach ($output_array[1] as $key_val){
            if(!empty($this->vars[$key_val]) && is_array($this->vars[$key_val])){
                $this->vars[$key_val] = implode(", ",$this->vars[$key_val]);
            }
            if(!empty($this->vars[$key_val])){
                $tbl = str_replace('{'.$key_val.'}',strtoupper($this->vars[$key_val]),$tbl);
            } else {
                $tbl = str_replace('{'.$key_val.'}','',$tbl);
            }

        }
        return $tbl;
    }

	function cleanBarcode($string) {
		$string = str_replace(' ', '', $string); // Replaces all spaces with hyphens.
		return preg_replace('/[^0-9\-]/', '', $string); // Removes special chars.
	}
}
?>