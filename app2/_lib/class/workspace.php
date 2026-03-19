<?php

//ini_set('max_execution_time', 600);

class Workspace extends BackEnd {

    public $final_result;
    public $page_prefix;
    public $site_prefix;
    public $the_source = '';

    /**
     * The constructor for the 'User' class
     * Calls BackEnd constructor
     * Cals Actions function
     *
     * @param array $config  an array with the configuration params read from '/_lib/config/config.php'
     * @access public
     * @see Actions()
     */
    function __construct($config = 0, $act = 1) {
        parent :: __construct($config);
        if ($act) {
            //ACTIONS
            $this->Actions();
            //close MsSQL Connection
            $this->db->Close();
        }
    }

//-------------------- a c t i o n s  d e c i s i o n s ------------------------------------------------------------
    /**
     * A c t i o n s
     * Setting up behavoiurs for each requested action
     *
     * @access private
     */
    function Actions($msg = '') {
        $this->final_result = '';

        //ACTIONS          
        if (!empty($this->user_profile))
            $this->ActionsNivelAcces();
        else
            $this->ActionsSite();

        //RESULT
        if ($this->final_result != '') {
            $this->vars['content'] = $this->final_result;
            $this->Layout($this->the_source);
        }
    }

//-------------------- a c t i o n s  d e c i s i o n s ------------------------------------------------------------

    /**
     * A c t i o n s S i t e
     * Setting up behavoiurs for each requested action
     *
     * @access private
     */
    function ActionsSite($msg = '') {
        $this->page_prefix = 'site_';
        $arr = $this->GenerateArr();
        if (empty($arr[0]))
            $this->final_result = $this->HomeSite();
        else if ($arr[0] == 'getawb')
            $this->final_result = $this->GetAWB($arr[1]);
        else if ($arr[0] == 'getlocofree')
            $this->final_result = $this->getLocoFree();
        else if ($arr[0] == 'delogare')
            $this->final_result = $this->Logout();
        else
            $this->final_result = $this->SessionExpired();
    }

    /**
     * A c t i o n s U s e r
     * Choosing what actions, depending on the profile (if administrator is not loged in - 0, or is loged in - 9), to take.
     *
     * @param string $msg  A message that can be displayed
     * @access public
     */
    function ActionsNivelAcces() {

        $this->page_prefix = 'user_';
        $arr = $this->GenerateArr();

        $this->user_rights = $this->GetDrepturiUtilizator($this->user_profile);
        $this->DrepturiUtilizatori();

        if(isset($arr[0]) && $arr[0] == 'images'){
           return $this->ModulHistory();
        }

        if (isset($arr[0]) && $arr[0] == 'delogare')
            $this->final_result = $this->Logout();
        else if ($this->user_profile==9){
            //clienti
            if (empty($arr[0]) || $arr[0] == 'client') {
                $this->final_result = $this->ModulClient();
            }
            else if ($arr[0] == 'printawb')
                $this->final_result = $this->ModulPrintAWB();
            else
                $this->final_result = $this->PageNotFound();

        }
        else if ($this->user_profile==13){
            //statie scanare
            if (empty($arr[0]) || $arr[0] == 'scanport') {
                $arr[0] = 'scanport';
                $this->final_result = $this->ModulScanport();
            }
            else if ($arr[0] == 'operatori' && $arr[1] == 'date_personale')
                $this->final_result = $this->ModulOperatori();
            else 
                $this->final_result = $this->PageNotFound();
        }
        else if ($this->user_profile > 0 && $this->user_profile != 9 && $this->user_profile != 13){
            if(empty($arr[0]) && (in_array('dashboard', $this->user_rights) || in_array('dashboard_urmarire', $this->user_rights))){
                $this->final_result = $this->ModulDashboard();
            }
            else if(empty($arr[0])){
                $_GET['act'] = 'expeditii/cautare';
                $this->final_result = $this->ModulExpeditii();
            }
            else if ($arr[0] == 'expeditii'){
                if(empty($arr[1]))
                    $_GET['act'] = 'expeditii/cautare';
                $this->final_result = $this->ModulExpeditii();
            }
            else if ($arr[0] == 'localitati')
                $this->final_result = $this->ModulLocalitati();
            else if ($arr[0] == 'banci')
                $this->final_result = $this->ModulBanci();
            else if ($arr[0] == 'centre')
                $this->final_result = $this->ModulCentre();
            else if ($arr[0] == 'geocoder_address')
                $this->final_result = $this->ModulGeocoderAddress();
            else if ($arr[0] == 'checkpoints')
                $this->final_result = $this->ModulCheckpoints();
            else if ($arr[0] == 'rute')
                $this->final_result = $this->ModulRute();
            else if ($arr[0] == 'agenti')
                $this->final_result = $this->ModulAgenti();
            else if ($arr[0] == 'ag_vanzari')
                $this->final_result = $this->ModulAgentiVanzari();
            else if ($arr[0] == 'clienti')
                $this->final_result = $this->ModulClienti();
            else if ($arr[0] == 'operatori')
                $this->final_result = $this->ModulOperatori();
            else if ($arr[0] == 'apiusers')
                $this->final_result = $this->ModulApiUsers();
            else if ($arr[0] == 'scanare')
                $this->final_result = $this->ModulScanare();
            else if ($arr[0] == 'scanport')
                $this->final_result = $this->ModulScanport();
            else if ($arr[0] == 'expeditiiclienti')
                $this->final_result = $this->ModulExpeditiiClienti();
            else if ($arr[0] == 'decont_card')
                $this->final_result = $this->ModulDecontCard();
            else if ($arr[0] == 'financiar')
                $this->final_result = $this->ModulFinanciarCentre();
            else if ($arr[0] == 'contracte')
                $this->final_result = $this->ModulContracte();
            else if ($arr[0] == 'retururi')
                $this->final_result = $this->ModulRetururiDocumente();
            else if ($arr[0] == 'rambursuri')
                $this->final_result = $this->ModulRambursuri();
            else if ($arr[0] == 'confirmari')
                $this->final_result = $this->ModulConfirmari();
            else if ($arr[0] == 'recantariri')
                $this->final_result = $this->ModulRecantariri();
            else if ($arr[0] == 'facturi')
                $this->final_result = $this->ModulFacturi();
            else if ($arr[0] == 'cheltuieli')
                $this->final_result = $this->ModulCheltuieli();
            else if($arr[0] == 'facturare')
                $this->final_result = $this->ModulFacturare();
            else if($arr[0] == 'loguri')
                $this->final_result = $this->ModulLoguri();
            else if($arr[0] == 'dashboard')
                $this->final_result = $this->ModulDashboard();

            else if ($arr[0] == 'comenzi')
                $this->final_result = $this->ModulComenzi();

            else if ($arr[0] == 'verificari')
                $this->final_result = $this->ModulVerificari();
            else if ($arr[0] == 'reports')
                $this->final_result = $this->ModulReports();

            else if ($arr[0] == 'printawb')
                $this->final_result = $this->ModulPrintAWB();
            else if (($arr[0] == 'importuri-xls' || $arr[0] == 'importuri-csv' || $arr[0] == 'importuri-op-xls' || $arr[0] == 'importuriabndvucmn') && $this->user_profile==10)
                $this -> final_result = $this -> ModulImporturi();
            else 
                $this->final_result = $this->PageNotFound();

        }
        else{
            $this->final_result = $this->PageNotFound();
        }

    }

//-------------------------------- functii ----------------------------------------

    function HomeSite() {
        $vars = [];
        if(!empty($_POST['login_user']))
            $vars['username'] = $_POST['login_user'];
        if(!empty($_POST['login_pass']))
            $vars['password'] = $_POST['login_pass'];

        $vars['afisare_expirare'] = 'none';
        $this->checkSessionExpired();
        if($this->parola_expirata)
            $vars['afisare_expirare'] = 'block';
        return $this->Parse($this->page_prefix . 'home.html', $vars);
    }

    function GetAWB($exp) {
        $exp = self::sSanitizeCleanEdges($exp);
        if(!ExpeditieDto::isValidCod($exp)){
            echo "<span style='color:red;'>Expeditie invalida : {$exp}</span>";
            die;
        }
        if(ExpeditieDto::isPuisor($exp)){
            $exp = ExpeditieDto::getAwbFromPuisor($exp);
        }

        if($exp <= 0){
            echo "<span style='color:red;'>Expeditie invalida : {$exp}</span>";
            die;
        }
        $query = "SELECT a.expeditie, c.op_ro as operatie
            FROM exp_prelucrate a 
            INNER JOIN ist_exp b ON a.cod_expeditie = b.cod_exp 
            INNER JOIN op c ON b.operatiune = c.cod_op
            WHERE c.public = 1 and a.anulata = 0 and a.expeditie = :expeditie order by b.data_op desc";
        $sql = $this->db->QFetchArray($query, ['expeditie'=>$exp]);
        if(!empty($sql['expeditie'])){
            $texte = '<span style="color:green;">AWB : <b>'.$exp.'</b> - '.$sql['operatie'];
            if($sql['operatie'] != 'Livrat')
            {
                $query2 = "SELECT b.nume as centru, c.denumire as ckp
					FROM scanari_coduri as a
					INNER JOIN centre as b ON a.centru=b.ID
					INNER join checkpoints as c ON c.id=a.tip
					WHERE a.cod like '".intval(trim($exp))."' and c.activ = 1 and c.is_public = 1 order by a.data desc";
                $sql2 = $this->db->QFetchArray($query2);
                if(!empty($sql2))
                {
                   $texte .= ' -> ' . $sql2['ckp'] . ' / '. $sql2['centru'];
                }
            }
            $texte.= '</span>';
            echo $texte;
        }else
            echo '<span style="color:red;">Expeditie:'.$exp.' invalida</span>';
        die;
    }

    function getLocoFree() {
        if (getenv("HTTP_CLIENT_IP")) $ip = getenv("HTTP_CLIENT_IP");
        else if(getenv("HTTP_X_FORWARDED_FOR")) $ip = getenv("HTTP_X_FORWARDED_FOR");
        else if(getenv("REMOTE_ADDR")) $ip = getenv("REMOTE_ADDR");
        else $ip = "UNKNOWN";

        $html = '';
        $query1="select nume_jd, cod_jd from judete order by nume_jd";
        $sql1 = $this->db->QFetchRowArray($query1);

        if (!empty($sql1)) {
            $html.='<table id="locofree" border="1" cellpading="3px" cellspacing="0" width="100%">';
            foreach ($sql1 as $row1) {
                $html.='<tr style="border-left:0; border-right:0"><th colspan="4" style="border-left:0; border-right:0">'.$row1['nume_jd'].'</th></tr>';
                $query2="SELECT nume_lc FROM localitati where DIST_KM <= 15 and cod_jd like :cod_jd order by nume_lc";
                $sql2 = $this->db->QFetchRowArray($query2, ['cod_jd'=>$row1['cod_jd']]);
                if (!empty($sql2)) {
                    $i=0;
                    $html.='<tr>';
                    foreach ($sql2 as $row2) {
                        $html.='<td>'.$row2['nume_lc'].'</td>';
                        if($i++ == 3)
                        {
                            $i=0;
                            $html.='</tr><tr>';
                        }
                    }
                    if($i != 0) $i = 4 - $i;
                    for($j=0; $j < $i; $j++) $html.='<td>&nbsp;</td>';

                    $html.='</tr>';
                }
            }
            $html.='</table>';
        }

        echo $html;
    }

//-------------------------------- module ----------------------------------------


    function ModulExpeditii() {
        require_once('expeditii.php');
        $modul = new ModulExpeditii($this->config, 1, $this->db);
        $this->vars = array_merge($this->vars, $modul->vars);
        return $modul->final_result;
    }
    function ModulBanci() {
        require_once('banci.php');
        $modul = new ModulBanci($this->config, 1, $this->db);
        $this->vars = array_merge($this->vars, $modul->vars);
        return $modul->final_result;
    }
    function ModulLocalitati() {
        require_once('localitati.php');
        $modul = new ModulLocalitati($this->config, 1, $this->db);
        $this->vars = array_merge($this->vars, $modul->vars);
        return $modul->final_result;
    }
    function ModulCentre() {
        require_once('centre.php');
        $modul = new ModulCentre($this->config, 1, $this->db);
        $this->vars = array_merge($this->vars, $modul->vars);
        return $modul->final_result;
    }
    function ModulGeocoderAddress() {
        require_once('GeocoderAddress.php');
        $modul = new ModulGeocoderAddress($this->config, 1, $this->db);
        $this->vars = array_merge($this->vars, $modul->vars);
        return $modul->final_result;
    }
    function ModulCheckpoints() {
        require_once('checkpoints.php');
        $modul = new ModulCheckpoints($this->config, 1, $this->db);
        $this->vars = array_merge($this->vars, $modul->vars);
        return $modul->final_result;
    }
    function ModulRute() {
        require_once('rute.php');
        $modul = new ModulRute($this->config, 1, $this->db);
        $this->vars = array_merge($this->vars, $modul->vars);
        return $modul->final_result;
    }
    function ModulAgenti() {
        require_once('agenti.php');
        $modul = new ModulAgenti($this->config, 1, $this->db);
        $this->vars = array_merge($this->vars, $modul->vars);
        return $modul->final_result;
    }
    function ModulAgentiVanzari() {
        require_once('agentiVanzari.php');
        $modul = new ModulAgentiVanzari($this->config, 1, $this->db);
        $this->vars = array_merge($this->vars, $modul->vars);
        return $modul->final_result;
    }
    function ModulClienti() {
        require_once('clienti.php');
        $modul = new ModulClienti($this->config, 1, $this->db);
        $this->vars = array_merge($this->vars, $modul->vars);
        return $modul->final_result;
    }
    function ModulOperatori() {
        require_once('operatori.php');
        $modul = new ModulOperatori($this->config, 1, $this->db);
        $this->vars = array_merge($this->vars, $modul->vars);
        return $modul->final_result;
    }

    function ModulApiUsers() {
        require_once('apiusers.php');
        $modul = new ModulApiUsers($this->config, 1, $this->db);
        $this->vars = array_merge($this->vars, $modul->vars);
        return $modul->final_result;
    }

    function ModulScanare() {
        require_once('scanare.php');
        $modul = new ModulScanare($this->config, 1, $this->db);
        $this->vars = array_merge($this->vars, $modul->vars);
        return $modul->final_result;
    }

    function ModulScanport() {
        require_once('scanPort.php');
        $modul = new ModulScanport($this->config, 1, $this->db);
        $this->vars = array_merge($this->vars, $modul->vars);
        return $modul->final_result;
    }

    function ModulExpeditiiClienti() {
        require_once('expeditiiClienti.php');
        $modul = new ModulExpeditiiClienti($this->config, 1, $this->db);
        $this->vars = array_merge($this->vars, $modul->vars);
        return $modul->final_result;
    }

    function ModulScanariNote() {
        require_once('scanariNote.php');
        $modul = new ModulScanariNote($this->config, 1, $this->db);
        $this->vars = array_merge($this->vars, $modul->vars);
        return $modul->final_result;
    }

    function ModulPrintAWB() {
        require_once('printAWB.php');
        $modul = new ModulPrintAWB($this->config, 1, $this->db);
        $this->vars = array_merge($this->vars, $modul->vars);
        return $modul->final_result;
    }

    function ModulClient() {
        require_once('client.php');
        $modul = new ModulClient($this->config, 1, $this->db);
        $this->vars = array_merge($this->vars, $modul->vars);
        return $modul->final_result;
    }


    function ModulRetururiDocumente() {
        require_once('retururi.php');
        $modul = new ModulRetururiDocumente($this->config, 1, $this->db);
        $this->vars = array_merge($this->vars, $modul->vars);
        return $modul->final_result;
    }

    function ModulRambursuri() {
        require_once('rambursuri.php');
        $modul = new ModulRambursuri($this->config, 1, $this->db);
        $this->vars = array_merge($this->vars, $modul->vars);
        return $modul->final_result;
    }

    function ModulConfirmari() {
        require_once('confirmari.php');
        $modul = new ModulConfirmari($this->config, 1, $this->db);
        $this->vars = array_merge($this->vars, $modul->vars);
        return $modul->final_result;
    }

    function ModulFacturi() {
        require_once('facturi.php');
        $modul = new ModulFacturi($this->config, 1, $this->db);
        $this->vars = array_merge($this->vars, $modul->vars);
        return $modul->final_result;
    }

    function ModulCheltuieli() {
        require_once('cheltuieli.php');
        $modul = new ModulCheltuieli($this->config, 1, $this->db);
        $this->vars = array_merge($this->vars, $modul->vars);
        return $modul->final_result;
    }

    function ModulFacturare() {
        require_once('facturare.php');
        $modul = new ModulFacturare($this->config, 1, $this->db);
        $this->vars = array_merge($this->vars, $modul->vars);
        return $modul->final_result;
    }

    function ModulRecantariri() {
        require_once('recantariri.php');
        $modul = new ModulRecantariri($this->config, 1, $this->db);
        $this->vars = array_merge($this->vars, $modul->vars);
        return $modul->final_result;
    }

    function ModulDashboard() {
        require_once('dashboard.php');
        $modul = new ModulDashboard($this->config, 1, $this->db);
        $this->vars = array_merge($this->vars, $modul->vars);
        return $modul->final_result;
    }

    function ModulLoguri() {
        require_once('loguri.php');
        $modul = new ModulLoguri($this->config, 1, $this->db);
        $this->vars = array_merge($this->vars, $modul->vars);
        return $modul->final_result;
    }

    function ModulHistory() {
        require_once('history.php');
        $modul = new ModulHistory($this->config, 1, $this->db);
        $this->vars = array_merge($this->vars, $modul->vars);
        return $modul->final_result;
    }

    function ModulComenzi() {
        require_once('comenzi.php');
        $modul = new ModulComenzi($this->config, 1, $this->db);
        $this->vars = array_merge($this->vars, $modul->vars);
        return $modul->final_result;
    }

    function ModulVerificari() {
        require_once('verificari.php');
        $modul = new ModulVerificari($this->config, 1, $this->db);
        $this->vars = array_merge($this->vars, $modul->vars);
        return $modul->final_result;
    }

    function ModulReports() {
        require_once('reports.php');
        $modul = new ModulReports($this->config, 1, $this->db);
        $this->vars = array_merge($this->vars, $modul->vars);
        return $modul->final_result;
    }

    function ModulImporturi() {
        require_once('importuri.php');
        $modul = new ModulImporturi($this->config, 1, $this->db);
        $this->vars = array_merge($this->vars, $modul->vars);
        return $modul->final_result;
    }

    function ModulDecontCard() {
        require_once('DecontCard.php');
        $modul = new ModulDecontCard($this->config, 1, $this->db);
        $this->vars = array_merge($this->vars, $modul->vars);
        return $modul->final_result;
    }

    function ModulFinanciarCentre() {
        require_once('FinanciarCentre.php');
        $modul = new ModulFinanciarCentre($this->config, 1, $this->db);
        $this->vars = array_merge($this->vars, $modul->vars);
        return $modul->final_result;
    }

    function ModulContracte() {
        require_once('contracte.php');
        $modul = new ModulContracte($this->config, 1, $this->db);
        $this->vars = array_merge($this->vars, $modul->vars);
        return $modul->final_result;
    }

/////////////////////////////  END module \\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\		

    function csv2array($path) {

        ini_set('memory_limit', '1228M');
        set_time_limit(600);

        if (($handle = fopen($path, "r")) !== FALSE) {
            # Set the parent multidimensional array key to 0.
            $nn = 0;
            while (($data = fgetcsv($handle, 1000, ",")) !== FALSE) {
                # Count the total keys in the row.
                $c = count($data);
                # Populate the multidimensional array.
                for ($x=0;$x<$c;$x++)
                {
                    $csvarray[$nn][$x] = $data[$x];
                }
                $nn++;
            }
            # Close the File.
            fclose($handle);
        }
        # Print the contents of the multidimensional array.
        return $csvarray;
    }
}
//end class
