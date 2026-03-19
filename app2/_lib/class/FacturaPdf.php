<?php
/**
 * Created by PhpStorm.
 * User: ionut
 * Date: 17/11/2016
 * Time: 16:34
 */

require_once __DIR__."/../../vendor/tecnickcom/tcpdf/tcpdf.php";
require_once "facturare.php";
require_once "ConstantsPdf.php";

class FacturaPdf extends TCPDF
{
    private $vars, $_stampila;
    public $stampila_alb_negru = false;

    public $cota_tva = [];
    public $pdf_style = [];

    public $intocmit = array(
        'nume' => 'Claudia ILINCA',
        'serie' => '',
        'nr' => '',
        'cnp' => '',
        'eliberata'=>'Sectia 21 politie'
    );

    const footer_detalii_plata = 'Va rugam sa specificati pe documentul de plata seria si numarul facturii: <strong>{{serie_factura}}</strong><br>
Pentru detalii legate de facturare va rugam contactati dep. <strong>Facturare:</strong>facturare@curierdragonstar.ro<br>
Pentru detalii legate de solduri va rugam sa contactati dep. <strong>Recuperare Creante:</strong> creditcontrol@curierdragonstar.ro
';


    function __construct($vars = null) {
        parent::__construct('P', 'mm', 'A4', false, 'ISO-8859-1', false);

        $this->vars = $vars;

        $this->setPrintHeader(false);
        $this->setPrintFooter(false);

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


    public function addHeader(){

        $x = 10;
        $y = 5;
        $fullSize = 190;
        $topLabelSize = 20;
        $topTextSize = 80;
        $topCenterSize = $fullSize / 2;
        $furnizor_html = "";
        //logo si furnizor
        $numeFirma = ConstantsPdf::DSC_LABEL;

        $furnizor_html .= 'Calea Bucuresti nr. 1, Corp C9, Otopeni, Ilfov, 075100<br>';
        $furnizor_html .= 'C.I.F.: <strong>RO29255819</strong> / Nr. ord. Reg. Com.: <strong>J23/402/2016</strong><br>';
        $furnizor_html .= 'Contul: <strong>RO63 BTRL RONC RT04 6374 3301</strong><br>';
        $furnizor_html .= 'Banca/Sucursala: <strong>BANCA TRANSILVANIA / Otopeni</strong><br>';
        $this->_stampila = ConstantsPdf::DSC_STAMPILA;

        $font = 'helvetica';
        $fontSize = 9;
        $this->setFont($font,'', $fontSize, '');
        //$this->SetMargins(5,5,5);


        if(strtotime($this->vars['factura']['trndate']) > strtotime('2017-08-30 16:10:00')){
            $this->intocmit = array(
                'nume' => 'Irina MUSAT',
                'serie' => '',
                'nr' => '',
                'cnp' => '',
                'eliberata'=>''
            );
        }

        $data = date("d.m.Y",strtotime($this->vars['factura']['trndate']));

        $this->setFont($font,'', $fontSize, '');
        $this->writeHTMLCell($topTextSize, 5, $x + $topCenterSize + 10, $this->getY() + 20, '<table><tr><td align="right">Serie/Nr. factura:</td><td>&nbsp;&nbsp;&nbsp;<strong>'.$this->vars['factura']['invoice'].'</strong></td></tr><tr><td align="right">Din data:</td><td>&nbsp;&nbsp;&nbsp;<strong>'.$data.'</strong></td></tr></table>', 0, 0, 0, $reseth=true, '', true);
        $this->write1DBarcode(strval($this->vars['factura']['invoice']), 'C128B',$topCenterSize + 22 ,$y + 22, '', '12', 0.4, $this->pdf_style, 'N'); // cod header

        $fontSize = 9;

        $this->SetLineWidth(0.2);

        /* Furnizor */

        $this->MultiCell($topLabelSize, 5, 'Furnizor:', '', 'L', 0, 1, $x, $y, true, 0, false, false, 5, 'T', true);
        $this->setFont($font,'BI', 10, '');
        $this->MultiCell($topTextSize, 5, $numeFirma, 'I', 'L', 0, 1, $x, $y + 5, true, 0, false, false, 5, 'T', true);
        $this->setFont($font,'', $fontSize, '');

        $this->writeHTMLCell($topCenterSize, 5, $x, $this->getY(), $furnizor_html, 0, 0, 0, $reseth=true, '', true);

        $this->setY(35);

    }

    public function make(){
        $x = 10;
        $y = 5;
        $fullSize = 190;
        $topLabelSize = 20;
        $topTextSize = 80;
        $topCenterSize = $fullSize / 2;
        $addText = "";
        if($this->vars['factura']['status'] == 1){
            $addText = "PROFORMA";
        } else if($this->vars['factura']['status'] == 2){
            // $addText = "DUPLICAT";
        }



        if(strlen($addText)){
            $this->StartTransform();
            $this->setAlpha(0.1);
            $this->Rotate(45);
            $this->setFont("helvetica",'B', 70, '');
           // $this->MultiCell(0, 0, $addText, 0, 'C', 0, 0, -70, 70, true, 0, false, false, 80, 'B', true);
            $this->MultiCell(0, 0, $addText, '', 'C', 0, 0, -170, 130, true, false, false, false, '', 'B', true);

            $this->setAlpha(1);
            $this->StopTransform();
        }

        $font = 'helvetica';
        $fontSize = 9;
        $this->setFont($font,'', $fontSize, '');

        $this->setX(0);
        $this->setY(0);
        $data = date("d.m.Y",strtotime($this->vars['factura']['trndate']));

        $this->addHeader();

        /* Beneficiar */
        $beneficiar_nume = ($this->vars['client']['nume_societate'])?$this->vars['client']['nume_societate']:$this->vars['client']['nume'];
        $beneficiar_judet_sediu = ($this->vars['client']['judet_sediu'])?$this->vars['client']['judet_sediu']:$this->vars['client']['judet_livrare'];
        $beneficiar_localitate_sediu = ($this->vars['client']['localitate_sediu_social'])?$this->vars['client']['localitate_sediu_social']:$this->vars['client']['localitate'];
        $beneficiar_adresa_sediu = ($this->vars['client']['adresa_sediu_social'])?$this->vars['client']['adresa_sediu_social']:$this->vars['client']['adresa'];

        $this->MultiCell($topLabelSize, 5, 'Beneficiar:', '', 'L', 0, 1, $x, $this->getY() + 10, true, 0, false, false, 5, 'T', true);
        $this->setFont($font,'B', $fontSize, '');

        $beneficiar_y = $this->getY();

        $this->MultiCell($topTextSize, 5, $beneficiar_nume, 'I', 'L', 0, 1, $x, $beneficiar_y, true, 0, false, false, 5, 'T', true);
        $this->setFont($font,'', $fontSize, '');

        $detalii_beneficiar = '<span style="font-size: 8px;">Adresa sediu social:</span><br>'.$beneficiar_adresa_sediu.'<br><strong>'.$beneficiar_localitate_sediu.', jud. '.$beneficiar_judet_sediu.'</strong><br>';
        $detalii_beneficiar .= '<br>';
        $detalii_beneficiar .= 'C.I.F.: <strong>'.$this->vars['client']['cod_fiscal'].'</strong><br>';
        $detalii_beneficiar .= 'Nr. ord. Reg. Com: <strong>'.$this->vars['client']['reg_com'].'</strong><br>';
        $detalii_beneficiar .= 'Contul: <strong>'.$this->vars['client']['cont_fa'].'</strong><br>';
        $detalii_beneficiar .= 'Banca/Sucursala: <strong>'.$this->vars['client']['banca_fa'].'</strong><br>';

        $this->writeHTMLCell($topTextSize, 5, $x, $this->getY(), $detalii_beneficiar, 0, 0, 0, true, '', true);


        $beneficiar_judet = $this->vars['client']['judet_livrare'];
        $beneficiar_localitate = $this->vars['client']['localitate'];
        $beneficiar_adresa = $this->vars['client']['adresa'];
        $centru_livrare = $this->vars['client']['centru_livrare'];

        $this->setFont($font,'B', $fontSize, '');
        $this->MultiCell($topTextSize, 5, $beneficiar_nume, 'I', 'L', 0, 1, $x + $topCenterSize, $beneficiar_y, true, 0, false, false, 5, 'T', true);
        $this->setFont($font,'', $fontSize, '');
        $adresa_livrare = '<span style="font-size: 8px;">Adresa livrare:</span><br>'.$beneficiar_adresa.'<br><strong>'.$beneficiar_localitate.', jud. '.$beneficiar_judet.'</strong><br>Centru livrare: <strong>'.$centru_livrare.'</strong><br>';
        $this->writeHTMLCell($topTextSize, 5, $x + $topCenterSize, $this->getY(), $adresa_livrare, 0, 0, 0, true, '', true);

        if($this->vars['client']['tip_tva'] == 2 || $this->vars['client']['facturare_tip_tranzactie'] == 3) {
            $this->writeHTMLCell($fullSize, 5, $x, 100, 'Cota T.V.A.: Taxare inversa', 0, 0, 0, true, '', true);
        } else {
            if(count($this->cota_tva)){
                //  la incasare
                $this->writeHTMLCell($fullSize, 5, $x, 100, 'Cota T.V.A.: '.implode(", ",$this->cota_tva).'% T.V.A.', 0, 0, 0, true, '', true);
            }
        }

        $linii_factura = '';
        $total_valoare = 0;
        $total_tva = 0;
        $total = 0;
        $tva = 0;

        if(!empty($this->vars['linii'])){
            foreach ($this->vars['linii'] as $k=>$linie){
                $pret_unitar = $linie["valoare"];
                $total_valoare += $valoare = $linie["valoare"] * $linie["cantitate"];
                $total_tva += $tva = (float) $linie["procTva"] * $valoare / 100;

                if($this->vars['client']['tip_tva'] == 2) {
                    $total_tva = 0;
                    $tva = 0;
                }

                if($this->vars['factura']['tip_factura'] == ModulFacturare::FACTURA_TIP_STORNO){
                    $pret_unitar = -1 * abs($pret_unitar);
                    $valoare = -1 * abs($valoare);
                    $tva = -1 * abs($tva);

                }
                $linii_factura .= '<tr>
    <td align="center">'.($k + 1).'</td>
    <td align="left">'.$linie["nume"].'</td>
    <td align="center"></td>
    <td align="center">'.$linie["cantitate"].'</td>
    <td align="center">'.number_format($pret_unitar,2).'</td>
    <td align="center">'.number_format($valoare,2).'</td>
    <td align="center">'.number_format($tva,2).'</td>
  </tr>
  ';
            }
        }

        $total = $total_valoare + $total_tva;
        if($this->vars['factura']['tip_factura'] == ModulFacturare::FACTURA_TIP_STORNO) {
            $total = -1 * abs($total);
            $total_tva = -1 * abs($total_tva);
            $total_valoare = -1 * abs($total_valoare);
        }
        $invoice_table = '
<style>
th{
border-bottom:1pt solid black;
border-top:1pt solid black;
}
.borders-bt{
border-bottom:1pt solid black;
border-top:1pt solid black;
}

table tr.separator { height: 10px; }
.space_tr{
    font-size:0px ;
}
</style>
    <table cellpadding="2">
  <tr>
    <th width="20" align="center">Nr.<br>crt.</th>
    <th width="310" align="left">Denumirea produselor sau a serviciilor</th>
    <th width="50" align="center">U.M.</th>
    <th width="50" align="center">Cant.</th>
    <th width="80" align="center">Pret unitar<br>- lei -</th>
    <th width="80" align="center">Valoarea<br>- lei -</th>
    <th width="80" align="center">Valoare T.V.A.<br>- lei -</th>
  </tr>
  '.$linii_factura.'
  <tr class="space_tr">
    <td colspan="7" align="right"></td>
  </tr>
  <tr>
    <td colspan="5" align="right">TOTAL:</td>
    <td align="center" class="borders-bt">'.number_format($total_valoare,2).'</td>
    <td align="center" class="borders-bt">'.number_format($total_tva,2).'</td>
  </tr>
  <tr>
    <td colspan="6" align="right"></td>
    <td align="center"></td>
  </tr>
  <tr>
    <td colspan="6" align="right">TOTAL:</td>
    <td align="center" class="borders-bt">'.number_format($total,2).'</td>
  </tr>
</table>';

        $this->writeHTMLCell($fullSize, 5, $x, 105, $invoice_table, 0, 0, 0, true, '', true);
        $termen = intval($this->vars['client']['termen_plata']);
        $data_scadenta = date("d-m-Y", strtotime($data. " + {$termen} days"));

        $this->setY(130);
        $this->writeHTMLCell($fullSize, 5, $x, $this->getY() + 50, 'Scadent la: <strong>'.$data_scadenta.'</strong>', "B", 0, 0, true, '', true);
        $this->writeHTMLCell($fullSize / 2, 5, $x, $this->getY() + 5, '<strong>Intocmit de:</strong>', "B", 0, 0, true, '', true);
        $this->writeHTMLCell($fullSize / 2 - 2, 5, $x + ($fullSize / 2) + 2, $this->getY(), '<strong>Date privind expedierea:</strong>', "B", 0, 0, true, '', true);

        $this->writeHTMLCell($fullSize / 2, 5, $x, $this->getY() + 5, 'Numele:<strong>'.$this->intocmit['nume'].'</strong>', "", 0, 0, true, '', true);
        $this->writeHTMLCell($fullSize / 2, 5, $x + ($fullSize / 2) + 2, $this->getY(), 'Numele: <strong>CURIER</strong><br>Mijloc de transport: -<br>Data livrare: <strong>'.$data.'</strong><br>Ora: <strong>'.date('h:i').'</strong><br><br>Semnatura:', "", 0, 0, true, '', true);

        $footer_detalii_plata = str_replace("{{serie_factura}}",$this->vars['factura']['invoice'],self::footer_detalii_plata);

        $this->writeHTMLCell($fullSize, 5, $x, $this->getY() + 35, $footer_detalii_plata, "", 0, 0, true, '', true);

        $this->writeHTMLCell($fullSize, 5, $x + 30, $this->getY() + 15, "Furnizor:", "", 0, 0, true, '', true);
        $this->writeHTMLCell($fullSize / 2, 5, $fullSize - 40, $this->getY(), "Beneficiar:", "", 0, 0, true, '', true);

        // $stampilaSize = 32;


        //$this->Image( ($this->stampila_alb_negru)?str_replace(".png","_b.png",$this->_stampila):$this->_stampila , $x + 19.5, $this->getY() + 5, $stampilaSize, $stampilaSize, 'PNG', '', '', false, 300, '', false, false, 0, '', false, false);

        $footerY = 278;
        $this->writeHTMLCell(100, 5, $x, $footerY, 'Numar pozitii facturate: '. count($this->vars['linii']), "", 0, 0, true, 'L', true);
        $this->writeHTMLCell(100, 5, $fullSize - 90, $footerY, 'Numar file: 1', "", 0, 0, true, 'R', true);

        // $this->writeHTMLCell($fullSize, 5, $x, $footerY + 5, 'Aceasta factura circula fara semnatura si stampila, conform Articolului 155, Aliniatul 28 din Codul Fiscal', "", 0, 0, true, 'C', true);
        $this->writeHTMLCell($fullSize, 5, $x, $footerY + 5, 'Factura circula fara semnatura si stampila conform legii 227/2015 privind Codul Fiscal, art. 319', "", 0, 0, true, 'C', true);

    }

    function centralizator(){
        $this->setX(0);
        $this->setY(0);
        $fullSize = 190;
        $this->addHeader();
        $this->setY(50);
        $this->SetMargins(10, 10, 10);
        $this->setFont("helvetica",'', 12, '');
        $this->writeHTMLCell($fullSize, 15, 10, $this->getY(), "DESFASURATOR PENTRU FACTURA", 0, 0, 0, true, 'C', true);
        $this->setFont("helvetica",'', 12, '');
        $data = date("d.m.Y",strtotime($this->vars['factura']['trndate']));
        $this->writeHTMLCell($fullSize, 15, 10, $this->getY() + 7, "<strong>".$this->vars['factura']['invoice'] . "</strong> din <strong>{$data}</strong>", 0, 0, 0, true, 'C', true);
        $this->setFont("helvetica",'', 10, '');

        if(!empty($this->vars['centralizator'])){
            $header_tabel = '';
            foreach ($this->vars['centralizator']['tabel_header'] as $val){
                $header_tabel .= '<td>'.$val.'</td>';
            }
            $tabel_tr = "";
            foreach ($this->vars['centralizator']['tabel_values'] as $key=>$val){
                if($key == 'total'){
                    $key = '';
                }
                $tds = "<td>".ucfirst($key)."</td>";
                foreach ($this->vars['centralizator']['tabel_header'] as $k=>$v){
                    if(!isset($val[$k])){
                        $val[$k] = 0;
                    }
                    if($val[$k] > 0){
                        $value = '<strong>'.number_format($val[$k],2).'</strong>';
                    } else {
                        $value = number_format($val[$k],2);
                    }
                    $tds .= '<td align="right">'.$value.'</td>';
                }
                $tabel_tr .= '<tr>'.$tds.'</tr>';
            }


            $tabel = '<style>.center_align td{ text-align: center;}</style>
 <table cellpadding="3">
 <tr>
    <th>Serviciu:</th>
    <th colspan="8"><strong>STANDARD</strong></th>
  </tr>
</table>
 <table border="1" cellpadding="3">
  <tr class="center_align">
    <td></td>
    '.$header_tabel.'
  </tr>
  '.$tabel_tr.'
</table>';

            $this->writeHTMLCell($fullSize, 5, 10, $this->getY() + 10, $tabel, 0, 0, 0, true, '', true);
        }

        $footer_detalii_plata = str_replace("{{serie_factura}}",$this->vars['factura']['invoice'],self::footer_detalii_plata);

        $this->writeHTMLCell($fullSize, 5, 10, $this->getY() + 50, $footer_detalii_plata, "", 0, 0, true, '', true);
        $stampilaSize = 32;
        $this->Image( ($this->stampila_alb_negru)?str_replace(".png","_b.png",$this->_stampila):$this->_stampila , 19.5, $this->getY() + 25, $stampilaSize, $stampilaSize, 'PNG', '', '', false, 300, '', false, false, 0, '', false, false);
    }


    public function borderouExpeditii(){

        $x = 0;

        $field['expeditie']['label'] = 'Expeditie';
        $field['expeditie']['box_x'] = $x;
        $field['expeditie']['align'] = 'R';
        $x += $field['expeditie']['box_size'] = 15;

        $field['data_expeditie']['label'] = 'Data Colectare';
        $field['data_expeditie']['box_x'] = $x;
        $x += $field['data_expeditie']['box_size'] = 16;


        $field['expeditor']['label'] = 'Expeditor';
        $field['expeditor']['box_x'] = $x;
        $x += $field['expeditor']['box_size'] = 50;

        /*$field['expeditor_localitate']['label'] = 'Localitate Expeditor';
        $x += $field['expeditor_localitate']['box_size'] = 25;
        $field['expeditor_localitate']['box_x'] = $x;*/

        $field['destinatar']['label'] = 'Destinatar';
        $field['destinatar']['box_x'] = $x;
        $x += $field['destinatar']['box_size'] = 45;
        /* $field['destinatar_localitate']['label'] = 'Localitate Destinatar';
        $x += $field['destinatar_localitate']['box_size'] = 25;
        $field['destinatar_localitate']['box_x'] = $x;*/

        /*$field['destinatar_centru']['label'] = 'Centru Destinatar';
        $x += $field['destinatar_centru']['box_size'] = 25;
        $field['destinatar_centru']['box_x'] = $x;*/

        $field['plicuri']['label'] = 'Plicuri';
        $field['plicuri']['box_x'] = $x;
        $field['expeditie']['align'] = 'R';
        $x += $field['plicuri']['box_size'] = 9;

        $field['colete']['label'] = 'Colete';
        $field['colete']['align'] = 'C';
        $field['colete']['box_x'] = $x;
        $x += $field['colete']['box_size'] = 9;

        $field['paleti']['label'] ='Paleti';
        $field['paleti']['align'] = 'C';
        $field['paleti']['box_x'] = $x;
        $x += $field['paleti']['box_size'] = 9;

        $field['tip_expeditie']['label'] = 'Tip Expeditie';
        $field['tip_expeditie']['box_x'] = $x;
        $x += $field['tip_expeditie']['box_size'] = 14;

        $field['greutate']['label'] = 'Greutate';
        $field['greutate']['align'] = 'R';
        $field['greutate']['box_x'] = $x;
        $x += $field['greutate']['box_size'] = 12;

        $field['km_preluare']['label'] = 'Km Prel';
        $field['km_preluare']['align'] = 'R';
        $field['km_preluare']['box_x'] = $x;
        $x += $field['km_preluare']['box_size'] = 12;

        $field['km_livrare']['label'] = 'Km Livr';
        $field['km_livrare']['align'] = 'R';
        $field['km_livrare']['box_x'] = $x;
        $x += $field['km_livrare']['box_size'] = 12;

        $field['val_greutate']['label'] = 'Val Gr';
        $field['val_greutate']['align'] = 'R';
        $field['val_greutate']['box_x'] = $x;
        $x += $field['val_greutate']['box_size'] = 12;

        $field['val_km']['label'] = 'Val km';
        $field['val_km']['align'] = 'R';
        $field['val_km']['box_x'] = $x;
        $x += $field['val_km']['box_size'] = 12;

        $field['val_asig']['label'] = 'Val Asig';
        $field['val_asig']['align'] = 'R';
        $field['val_asig']['box_x'] = $x;
        $x += $field['val_asig']['box_size'] = 12;

        $field['valoare_expeditie']['label'] = 'Val Exp';
        $field['valoare_expeditie']['align'] = 'R';
        $field['valoare_expeditie']['box_x'] = $x;
        $x += $field['valoare_expeditie']['box_size'] = 14;

        $field['valoare_totala_expeditie']['label'] = 'Val Totala';
        $field['valoare_totala_expeditie']['align'] = 'R';
        $field['valoare_totala_expeditie']['box_x'] = $x;
        $x += $field['valoare_totala_expeditie']['box_size'] = 16;

        /*$field['observatii']['label'] = 'Observatii';
        $field['observatii']['box_size'] = 40;
        $field['observatii']['box_x'] = 40;*/


        $this->AddPage('L', 'A4');

        // $this->Rotate(-90);
        $this->setX(5);
        $this->setY(5);
        $fullSize = 290;
        // $this->addHeader();
        $this->setY(5);
        $this->SetMargins(10, 10, 10);
        $this->setFont("helvetica",'', 12, '');
        $this->writeHTMLCell($fullSize, 15, 10, $this->getY() , "BORDEROU EXPEDITII", 0, 0, 0, true, 'C', true);
        $this->setFont("helvetica",'', 12, '');
        $data = date("d.m.Y",strtotime($this->vars['factura']['trndate']));
        $this->writeHTMLCell($fullSize, 15, 10, $this->getY() + 7, "<strong>".$this->vars['factura']['invoice'] . "</strong> din <strong>{$data}</strong>", 0, 0, 0, true, 'C', true);
        $this->setFont("helvetica",'', 8, '');

        $boxH = 6;
        $this->setY(25);
        $this->setFont("helvetica",'B', 7, '');
        $y = $this->getY();
        $pos_x = 0;
        foreach ($field as $key => $fiel){
            $border = 'BT';
            if($pos_x == 0){
                $border = 'LTB';
            } else if($pos_x == count($field) -1){
                $border = 'BTR';
            }
            $this->MultiCell( $fiel['box_size'], $boxH, $fiel['label'], $border, (!empty($fiel['align']))?$fiel['align']:'L', 0, 1,  $this->getX() + $fiel['box_x'], $y , true, 0, false, false, 3, 'M', true);
            $pos_x++;
        }

        $this->setFont("helvetica",'', 8, '');
        $total_val_exp = 0.00;
        $total_val_km = 0.00;
        $total_val_gr = 0.00;
        $total_val_asig = 0.00;
        $total_val_total = 0.00;
        $total_val_tva = 0.00;
        $tabel_tr = "";
        $i = 0;
        $page = 0;
        foreach ($this->vars['borderou_expeditii'] as $row){

            if($this->getY() >= 190){
                $i = 0;
                $page++;
                $this->AddPage('L', 'A4');
                $this->setX(5);
                $this->setY(15);
            }
            $x = $this->getX();
            $y = $this->getY();

            $pos_x = 0;
            foreach ($field as $key => $fiel){
                $border = 'BT';
                if($pos_x == 0){
                 //   $border = 'LTB';
                } else if($pos_x == count($field) -1){
                 //   $border = 'BTR';
                }
                // $this->MultiCell( $fiel['box_size'], $boxH, (!empty($field[$key]['total']))?$field[$key]['total']:'', $border, ($fiel['align'])?$fiel['align']:'L', 0, 1, $this->getX() + $fiel['box_x'], $y, true, 0, false, false, 16, 'T', true);

                $this->MultiCell( $fiel['box_size'], $boxH, str_replace("\n",'',$row[$key]), $border, (!empty($fiel['align']))?$fiel['align']:'L', 0, 1,  $this->getX() + $fiel['box_x'], $y , true, 0, false, false, 8, 'M', true);
                $pos_x++;
                if(empty($field[$key]['total']))
                    $field[$key]['total'] = 0;

                if(is_numeric($row[$key]) && $row[$key]>0) {
                    //error_log($key .":".$row[$key]);
                    $field[$key]['total'] += $row[$key];
                }
            }

            $total_val_exp += $row['valoare_expeditie'];
            $total_val_km += $row['val_km'];
            $total_val_gr += $row['val_greutate'];
            $total_val_asig += $row['val_asig'];
            $total_val_total += $row['valoare_totala_expeditie'];

            $i++;
        }
        $y = $this->getY();
        $last_row = [];
        $last_row['expeditie'] = count($this->vars['borderou_expeditii']);
        $last_row['valoare_totala_expeditie'] = $total_val_total;
        $pos_x = 0;

        foreach ($field as $key => $fiel){

            if($key == 'expeditie')
                $field[$key]['total'] = count($this->vars['borderou_expeditii']). " exp.";
            if($key == 'data_expeditie')
                $field[$key]['total'] = "";

            if($key == 'valoare_totala_expeditie')
                $field[$key]['total'] = isset($field[$key]['total']) ? number_format($field[$key]['total'],2) : "0.00";

            $border = 'BT';
            if($pos_x == 0){
                   $border = 'LTB';
            } else if($pos_x == count($field) -1){
                   $border = 'BTR';
            }


            $this->MultiCell( $fiel['box_size'], $boxH, (!empty($field[$key]['total']))?$field[$key]['total']:'', $border, (!empty($fiel['align']))?$fiel['align']:'L', true, 1,  $this->getX() + $fiel['box_x'], $y , true, 0, false, false, 8, 'M', true);
            $pos_x++;
        }

    }

    private function replaceVars($tbl){

        preg_match_all("/{(.[a-z_]*)}/", $tbl, $output_array);

        foreach ($output_array[1] as $key_val){
            if(!empty($this->vars[$key_val])){
                $tbl = str_replace('{'.$key_val.'}',strtoupper($this->vars[$key_val]),$tbl);
            } else {
                $tbl = str_replace('{'.$key_val.'}','',$tbl);
            }

        }
        return $tbl;
    }
}