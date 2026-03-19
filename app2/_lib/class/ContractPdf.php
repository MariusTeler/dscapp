<?php
/**
 * Created by PhpStorm.
 * User: ionut
 * Date: 04/09/2017
 * Time: 10:19
 */

require_once __DIR__."/../../vendor/tecnickcom/tcpdf/tcpdf.php";
require_once "ConstantsPdf.php";

class ContractPdf extends TCPDF
{
    private $vars;
    public $pdf_style = [];

    function __construct($vars=null)
    {
        parent::__construct('L', 'mm', 'A4', false, 'ISO-8859-1', false);

        $this->vars = $vars;
        // set document information
        $this->SetCreator(PDF_CREATOR);
        $this->SetAuthor('DSC EXPRES LOGISTIC');
        $this->SetTitle('contract-'.$this->vars['nume_societate']);
        $this->SetSubject('Contract');
        $this->SetKeywords('curierat, dragon, star, contract, PDF');

        // remove default footer
        $this->setPrintHeader(false);
        $this->setPrintFooter(false);
        $this->setHeaderFont(Array('helvetica', '', '12'));
        //$this->setFooterFont(Array('times', '', '10'));

        // set default monospaced font
        $this->SetDefaultMonospacedFont('helvetica');
        $this->setFontSubsetting(false);

        // set margins
        $this->SetMargins(10, 10, 10, 10);
        // set JPEG quality
        $this->setJPEGQuality(75);

        // set auto page breaks
        // $this->SetAutoPageBreak(false, -20);

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

        $this->SetAutoPageBreak(true, 10);

        // set cell padding
        $this->setCellPaddings(0.5, 0.5, 0.5, 0.5);

        // set cell margins
        $this->setCellMargins(0, 0, 0, 0);

        // set color for background
        $this->SetFillColor(255, 255, 255);

        $this->SetLineWidth(0.2);
        $this->setPrintFooter(true);
        $this->getPdf();
        // $this->setPrintFooter(false);
        $this->getPrce();
    }


    /**
     * This method is used to render the page footer.
     * It is automatically called by AddPage() and could be overwritten in your own inherited class.
     * @public
     */
    public function Footer() {
        $this->SetFont('helvetica', 'I', 6);
        $cur_y = $this->y -7;
        $this->SetTextColorArray($this->footer_text_color);
        //set style for cell border
        $line_width = (0);
        $this->SetLineStyle(array('width' => $line_width, 'cap' => 'butt', 'join' => 'miter', 'dash' => 0, 'color' => array(255,255,255)));

        $w_page = isset($this->l['w_page']) ? $this->l['w_page'].' ' : 'Pagina ';
        if (empty($this->pagegroups)) {
            $pagenumtxt = $w_page.$this->getAliasNumPage().' din '.$this->getAliasNbPages();
        } else {
            $pagenumtxt = $w_page.$this->getPageNumGroupAlias().' din '.$this->getPageGroupAlias();
        }
        $this->SetY($cur_y);
        //Print page number
        if ($this->getRTL()) {
            $this->SetX($this->original_rMargin + 15);
            $this->Cell(0, 0, 'R.V. '.$this->vars["agent_vanzari"], 'T', 0, 'R');
            $this->SetX($this->original_rMargin);
            $this->Cell(0, 0, $pagenumtxt, 'T', 0, 'L');
        } else {
            $this->SetX($this->original_rMargin + 15);
            $this->Cell(0, 0, 'R.V. '.$this->vars["agent_vanzari"], 'T', 0, 'L');
            $this->SetX($this->original_lMargin);
            $this->Cell(0, 0, $this->getAliasRightShift().$pagenumtxt, 'T', 0, 'R');
        }
    }

    function getPdf(){

        $this->AddPage();
        
        $x = 20;
        $y = 6;

        $orientation = "P";
        $width = 175;
        $height = 250;

        if($orientation == "L"){

            $width = 263;
            $height = 301;
        }

        $this->setPageFormat($this->pdf_style, $orientation);
        $tbl = file_get_contents(ConstantsPdf::PDF_TPL.'contract.html', false);
        $tbl = $this->replaceVars($tbl);
        $this->setFont('helvetica','', 9, '');
        $this->writeHTMLCell($width, $height, $x, $y, $tbl, 0, 0, false, 'L', false);

    }


    function getPrce(){

        $this->AddPage();
        $this->AddPage();
        $this->AddPage();

        $x = 20;
        $y = 6;

        $orientation = "P";
        $width = 175;
        $height = 250;
        if($orientation == "L"){

            $width = 263;
            $height = 301;
        }
        $this->setPageFormat($this->pdf_style, $orientation);
        $tbl = file_get_contents(ConstantsPdf::PDF_TPL.'contract_lista_preturi.html', false);
        $tbl = $this->replaceVars($tbl);
        $this->setFont('helvetica','', 9, '');
        $this->writeHTMLCell($width, $height, $x, $y, $tbl, 0, 0, false, 'L', false);
    }

    private function replaceVars($tbl){

        preg_match_all("/{(.[a-z_]*)}/", $tbl, $output_array);

        foreach ($output_array[1] as $key_val){
            if(!empty($this->vars[$key_val]) && is_array($this->vars[$key_val])){
                $this->vars[$key_val] = implode(", ",$this->vars[$key_val]);
            }
            if(!empty($this->vars[$key_val])){
                $tbl = str_replace('{'.$key_val.'}', $this->vars[$key_val],$tbl);
            } else {
                $tbl = str_replace('{'.$key_val.'}','',$tbl);
            }

        }
        setlocale(LC_CTYPE, 'en_US.UTF8');
        $tbl = iconv('UTF-8','ASCII//TRANSLIT//IGNORE',$tbl);
        return $tbl;
    }
}
