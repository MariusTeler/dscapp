<?php
/**
 * Created by PhpStorm.
 * User: ionut
 * Date: 01/09/2017
 * Time: 13:15
 */


class ModulContracte extends BackEnd
{
    public $final_result;
    public $action_module;
    public $page_prefix;

    public function __construct($config = 0, $act = 1, $db = 0)
    {
        parent:: __construct($config, $db);

        $this->vars['title_page'] = 'Decont';
        $this->page_prefix = 'contracte_';
        if ($act) {
            //ACTIONS
            $this->Actions();
        }
    }


//-------------------- a c t i o n s  d e c i s i o n s ------------------------------------------------------------

    /**
     * A c t i o n s
     * Choosing what actions, depending on the profile (if administrator is not loged in - 0, or is loged in - 9), to take.
     *
     * @param string $msg  A message that can be displayed
     * @access public
     */
    function Actions($msg = '') {
        $this->final_result = '';

        // A D M I N
        if (isset($_GET['logout']))
            $this->Logout();

        else if (!empty($this->user_profile)) {
            $this->ActionsNivelAcces();
        } else
            $this->final_result = $this->PageNotFound();
        // R E S U L T
        return $this->final_result;
    }


    function ActionsNivelAcces()
    {
        $this->user_rights = $this->GetDrepturiUtilizator($this->user_profile);
        $arr = $this->GenerateArr();
        $flag = 0;
        //  actiuni in functie de permisiune
        if (in_array("contracte", $this->user_rights) || $this->user_profile == 10){
            //  actiuni care se pot accesa de orice permisiune
            if ($arr[1] == 'afisare' || empty($arr[1])) {
                $this->final_result = $this->Afisare();
                $flag = 1;
            } else if ($arr[1] == 'nou') {
                $this->final_result = $this->Nou();
                $flag = 1;
            } else if ($arr[1] == 'sablon') {
                $this->final_result = $this->Sablon();
                $flag = 1;
            }
        }

        if(empty($flag))
            $this->final_result = $this->PageNotFound();
    }

    function Afisare(){
        $this->vars['title_page'] = 'Contracte';
        $vars = [];
        $vars['onload_js_version'] = $this->config['version']['onload_js_version'];
        
        return $this->Parse($this->page_prefix . 'index.html', $vars);
    }

    function getDateContract(){

    }

    function Sablon(){
        require_once 'ContractPdf.php';
        $pdf = new ContractPdf();
        $pdf->setFooterFont(Array(PDF_FONT_NAME_DATA, '', PDF_FONT_SIZE_DATA));
        $pdf->SetFooterMargin(PDF_MARGIN_FOOTER);
        $type = 'I'; // D I
        $pdf->Output('contract.pdf',$type);
        exit();
    }


    function getVars(){
        $vars = [];
        if(!empty($_REQUEST))
            $vars = $_REQUEST;
        $vars['onload_js_version'] = $this->config['version']['onload_js_version'];
        $persoane_de_contact = '';
        $puncte_de_lucru = '';
        
        if(!empty($_REQUEST['localitate'])) $vars['localitate'] = strtoupper($_REQUEST['localitate']);
        if(!empty($_REQUEST['reprezentant_calitate'])) 
        	$vars['reprezentant_calitate'] = ucfirst(strtolower($_REQUEST['reprezentant_calitate']));
        else 
			$vars['reprezentant_calitate'] = 'Administrator';
			
        if(!empty($_REQUEST['gestionar_contract'])){

            $query = "SELECT * FROM contracte_gestionari WHERE id=".$_REQUEST['gestionar_contract'];
            $sql = $this->db->QFetchArray($query);
            if(!empty($sql)){
                $vars['gestionar_nume_societate'] = $sql['nume_societate'];
                $vars['gestionar_sediu_social'] = $sql['sediu_social'];
                $vars['gestionar_reg_com'] = $sql['reg_com'];
                $vars['gestionar_cui'] = $sql['cui'];
                $vars['gestionar_banca'] = $sql['banca'];
                $vars['gestionar_iban'] = $sql['iban'];
                $vars['gestionar_reprezentant'] = $sql['reprezentant'];
                $vars['gestionar_reprezentant_calitate'] = $sql['reprezentant_calitate'];
                $vars['gestionar_reprezentant_calitate'] = ucfirst(strtolower($sql['reprezentant_calitate']));
                $vars['gestionar_nume_scurt'] = $sql['nume_scurt'];
            }

			$contacte=[];
            if(empty($_REQUEST['contact'])){ 
            	$contacte[0]['nume'] = $contacte[1]['nume'] =''; 
            	$contacte[0]['email'] = $contacte[1]['email'] ='';
            	$contacte[0]['telefon'] = $contacte[1]['telefon'] ='';
            }
            else $contacte = $_REQUEST['contact'];
            foreach ($contacte as $contact){
                // print_r($contact);
                $nume = (!empty($contact['nume']))?$contact["nume"]:"_";
                $email = (!empty($contact['email']))?$contact["email"]:"_";
                $telefon = (!empty($contact['telefon']))?$contact["telefon"]:"_";
                $persoane_de_contact .= '<tr><td>'.$nume.'</td><td>'.$email.'</td><td>'.$telefon.'</td></tr>';
            }

			$pcs=[];
            if(empty($_REQUEST['pc'])){ 
            	$pcs[0]['pc_nume'] = $pcs[1]['pc_nume'] =''; 
            	$pcs[0]['pc_adresa'] = $pcs[1]['pc_adresa'] ='';
            	$pcs[0]['pc_telefon'] = $pcs[1]['pc_telefon'] ='';
            }
            else $pcs = $_REQUEST['pc'];
            foreach ($pcs as $pc){
                $pc_nume = (!empty($pc['pc_nume']))?$pc["pc_nume"]:"_";
                $pc_adresa = (!empty($pc['pc_adresa']))?$pc["pc_adresa"]:"_";
                $pc_telefon = (!empty($pc['pc_telefon']))?$pc["pc_telefon"]:"_";
                $puncte_de_lucru .= '<tr><td>'.$pc_nume.'</td><td>'.$pc_adresa.'</td><td>'.$pc_telefon.'</td></tr>';
            }

            $lista_preturi = '';
            if(!empty($_REQUEST['serviciu'])){
                foreach ($_REQUEST['serviciu'] as $id => $vals){
                    //TODO : from table tarife : tarif standard
                    $query = "SELECT * FROM contracte_servicii WHERE id=".$id;
                    $sql = $this->db->QFetchArray($query);
                    if(!empty($sql["loco_baza"]) && !empty($sql["ext_baza"])){
                    	$lista_preturi .= '<tr><td>'.$sql["nume"].'</td>';
                    	switch ($sql["nume"]) {
            				case  "Km aditionali":
            					$lista_preturi .= '<td>-</td><td>-</td>';
	                			break;
                			default:
								$lista_preturi .= '<td>'.$sql["loco_baza"].'</td><td>'.$vals["loco"].'</td>';
						}              
                        $lista_preturi .= '<td>'.$sql["ext_baza"].'</td><td>'.$vals["ext"].'</td></tr>';
                    } else {
                    	if($sql["nume"] == "Asigurare marfa") {
                			$sql["nume"] = "Asigurare (% din valoarea declarata)";
                		}
                        $lista_preturi .= '<tr><td>'.$sql["nume"].'</td><td colspan="4">'.$vals["locoext"].'</td></tr>';
                    }

                }

            }


            $vars['lista_preturi'] = $lista_preturi;

            $vars['persoane_de_contact'] = $persoane_de_contact;
            $vars['puncte_de_lucru'] = $puncte_de_lucru;


        }
        
        if(!empty($_REQUEST['alte_servicii'])) 
			$vars['alte_servicii'] = '<h3>Art3.	MODIFICARI CLAUZE, CLAUZE NOI</h3><div>Alte clauze: '.$_REQUEST['alte_servicii'].'</div>';
        $vars['agenti_vanzari'] = $this->getAgentiVanzari();
        $vars['gestionari_contract'] = $this->getGestionariContract();
        if(empty($_REQUEST['termen_plata'])) $vars['termen_plata'] = 15;
        if(!empty($_REQUEST['zi_facturare'])) $vars['zi_facturare'] = 'in data de:'.$_REQUEST['zi_facturare'].' a lunii';
        $vars['tabel_servicii'] = $this->getTabelServicii();

        $vars['data'] = date('d.m.Y');
        $vars['nr'] = date('ymdhi');
        return $vars;
    }


    function Nou(){
        $this->vars['title_page'] = 'Contract nou';
        $vars = $this->getVars();

        if(!empty($_REQUEST['gestionar_contract'])) {
            require_once 'ContractPdf.php';
            $pdf = new ContractPdf($vars);
            $type = 'I'; // D I
            $pdf->Output('contract.pdf', $type);
            exit();
        }

        return $this->Parse($this->page_prefix . 'new.html', $vars);
    }

    function getAgentiVanzari(){
        $sql = $this->db->QFetchRowArray("SELECT id, nume FROM ag_vanzari WHERE activ = 1 and nume not like '%-Z' order by nume");
        $html = '';
        foreach ($sql as $row){
            $html .= '<option value="'.$row['id'].'">'.$row['nume'].'</option>';
        }
        return $html;
    }


    function getGestionariContract(){
        $sql = $this->db->QFetchRowArray("SELECT id, nume_societate FROM contracte_gestionari ");
        $html = '';
        foreach ($sql as $row){
            $html .= '<option value="'.$row['id'].'">'.$row['nume_societate'].'</option>';
        }
        return $html;
    }

    function getTabelServicii(){
        $sql = $this->db->QFetchRowArray("SELECT * FROM contracte_servicii where activ = 1");
        $html = '';
        foreach ($sql as $row){
            // $nume_input = preg_replace('/[^\da-z]/i', '', strtolower($row['nume']));
            if(!empty($row['loco_baza']) && !empty($row['ext_baza'])){
            	switch ($row['nume']) {
            		case  "Km aditionali":
                		$html .= '<tr><td>'.$row['nume'].'</td><td align="right"> - </td><td align="right"> - </td><td  align="right">'.$row['ext_baza'].'</td><td><input name="serviciu['.$row['id'].'][ext]" style="width:50px; text-align:center;" type="number" min="0" step=".01" placeholder="0.00"></td></tr>';
                		break;
                	default:
                		$html .= '<tr><td>'.$row['nume'].'</td><td align="right">'.$row['loco_baza'].'</td><td><input name="serviciu['.$row['id'].'][loco]" style="width:50px; text-align:center;" type="number" min="0" step=".01" placeholder="0.00"></td><td  align="right">'.$row['ext_baza'].'</td><td><input name="serviciu['.$row['id'].'][ext]" style="width:50px; text-align:center;" type="number" min="0" step=".01" placeholder="0.00"></td></tr>';
                }
            } else {
            	switch ($row['nume']) {
            		case  "Ramburs":
                		$html .= '<tr><td>'.$row['nume'].'</td><td colspan="4"><input  name="serviciu['.$row['id'].'][locoext]" style="width:100%; text-align:center;" type="text"  placeholder=""></td></tr>';
                		break;
                	case  "Asigurare marfa":
                		$row['nume'] = "Asigurare (% din valoarea declarata)";
                		$html .= '<tr><td>'.$row['nume'].'</td><td colspan="4"><input  name="serviciu['.$row['id'].'][locoext]" style="width:100%; text-align:center;" type="number" min="0" step=".01" placeholder=""></td></tr>';
                		break;
                	default:
                		$html .= '<tr><td>'.$row['nume'].'</td><td colspan="4"><input  name="serviciu['.$row['id'].'][locoext]" style="width:100%; text-align:center;" type="number" min="0" step=".01" placeholder=""></td></tr>';
                }
            }
        }
        return $html;
    }

}
