<?php
/**
 * Created by PhpStorm.
 * User: ionut
 * Date: 22/03/2017
 * Time: 14:58
 */

require_once 'winmentorNewApi.php';

class ModulLoguri extends BackEnd
{

    public $final_result;
    public $action_module;
    public $page_prefix;
    public $site_prefix;


    /**
     * The constructor for the 'ModulExpeditii' class
     * Calls BackEnd constructor
     * Cals Actions function
     *
     * @param array $config  an array with the configuration params read from '/_lib/config/config.php'
     * @param integer $act  (0/1) Specifies if actions are alowed or not
     * @access public
     * @see Actions()
     */
    function __construct($config = 0, $act = 1, $db = 0) {
        parent :: __construct($config, $db);
        $wme = new WinMentorNewApi($this->config);

        $mentenanta = $wme->MentenanceMode();
        $wme_mentenanta = $cron_lock = "";
        $wme_mentenanta_timp = $cron_lock_timp = "";

        if($mentenanta){
            $wme_mentenanta = 'wme_mentenanta';
            $wme_mentenanta_timp = " ".intval($mentenanta / 60)." min";
        }

        $cron_run = $this->CronLock();
        if($cron_run){
            $cron_lock = "cron_run";
            $cron_lock_timp = " ".intval($cron_run / 60)." min";
        }

        if(in_array($this->user_id, array(parent::MARIAN, parent::DOINA, parent::MADALINP)))
            //$this->vars['title_page'] = 'Loguri <span id="cron_lock" class="buton-cron-lock"><input type="button" class="button w120 '.$cron_lock.'" value="Cron '.$cron_lock_timp.'" /></span>
            //<span id="comunicare_wme" class="buton-comunicare-wme"><input type="button" class="button w120 '.$wme_mentenanta.'" value="WME com '.$wme_mentenanta_timp.'" /></span>';
            $this->vars['title_page'] = 'Loguri <span id="comunicare_wme" class="buton-comunicare-wme"><input type="button" class="button w120 '.$wme_mentenanta.'" value="WME com '.$wme_mentenanta_timp.'" /></span>';
        else
            $this->vars['title_page'] = 'Loguri';

        $this->page_prefix = 'loguri_';

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


    function ActionsNivelAcces() {
        $this->user_rights = $this->GetDrepturiUtilizator($this->user_profile);
        $arr = $this->GenerateArr();
        $flag=0;
        //  actiuni in functie de permisiune
        if (in_array("loguri", $this->user_rights) || $this->user_profile == 10){
            if (isset($arr[1]) && $arr[1] == 'raport') {
                $this->final_result = $this->Raport();
                $flag = 1;
            } else if (isset($arr[1]) && $arr[1] == 'ajax') {
                header('Content-Type: application/json');
                echo json_encode($this->Ajax());
                exit();
            } 
            else if (isset($arr[1]) && $arr[1] == 'json' && ($arr[2] ?? "none") == "facturi_log") {
                header('Content-Type: application/json');
                echo $this->Json_FacturiLog();
                exit();
            } 
            else if (isset($arr[1]) && $arr[1] == 'json' && ($arr[2] ?? "none") == "parteneri_log") {
                header('Content-Type: application/json');
                echo $this->Json_ParteneriLog();
                exit();
            }
            else {
                $this->final_result = $this->Raport();
                $flag = 1;
            }
        }
        if(empty($flag))
            $this->final_result = $this->PageNotFound();

    }

    function Raport($message = ''){
        $vars = [];
        $vars['error'] = $message;
        $this->vars['site_title'] = 'Loguri trimitere facturi si parteneri in WinMentor';
        return $this->Parse($this->page_prefix . 'index.html', $vars);
    }

    function Ajax(){
        $method = $_POST['method'] ?? "none";

        switch ($method):
            case 'getLog':
                return $this->GetLog();
                break;
            case 'retrimiteFacturi':
                $this->UpdateFacturi();
                return $this->GetLog();
                break;
            case 'markAsTrimisa':
                $this->UpdateFacturi(true);
                return $this->GetLog();
                break;
            case 'trimiteParteneri':
                $this->TrimiteParteneri();
                return $this->GetLog();
                break;
            case 'SetMentenanceMode':
                return $this->SetMentenanceMode();
                break;
            case 'RemoveMentenanceMode':
                return $this->SetMentenanceMode(false);
                break;
            case 'AddCronLock':
                return $this->SetCronMode();
                break;
            case 'RemoveCronLock':
                return $this->SetCronMode(false);
                break;
        endswitch;
    }


    function Json_FacturiLog(){
        $responce = new StdClass();
        $responce->page = 0;
        $responce->total = 0;
        $responce->records = 0;

        $cond = " ef.wme >= 3 and ef.anulata = 0 ";
        if(isset($_POST['_search'])){
            $searchOn = $this->Strip($_POST['_search']);
            if ($searchOn == 'true') {
                $searchstr = $this->Strip($_POST['filters']);
                $cond .= $this->constructWhere($searchstr);
            }
        }

        $query = "SELECT count(*) as nr 
            FROM exp_facturi ef 
            left join agenti ag on ag.cod_ag = CAST(SUBSTRING(ef.invoice, 1, 5) AS UNSIGNED)
            left join centre ce on ce.id = ag.cod_centru
            WHERE {$cond}";
        $result = $this->db->QFetchArray($query);
        $count = !empty($result['nr']) ? $result['nr'] : 0;

        if($count == 0)
            return json_encode($responce);

        if((empty($_POST['sidx']))) {
            $sidx = 3;
            $sord = 'desc';
        }
        else {
            $sidx = $_POST['sidx']; // get index row - i.e. user click to sort
            $sord = $_POST['sord']; // get the direction
        }

        $page = intval($_POST['page'] ?? 1); // get the requested page
        $limit = intval($_POST['rows'] ?? 250);

        if( $count > 0 ) {$total_pages = ceil($count/$limit); }
        else { $total_pages = 0; }
        if ($page > $total_pages) $page=$total_pages;
        if ($limit<0) $limit = 0;
        $start = $limit*$page - $limit;
        if ($start<0) $start = 0;

        $query = "SELECT ef.id, ef.invoice, ef.trndate, ef.sumamnt, ef.wme, ef.wme_message, ag.nume_ag, ce.nume
            FROM exp_facturi ef 
            left join agenti ag on ag.cod_ag = CAST(SUBSTRING(ef.invoice, 1, 5) AS UNSIGNED)
            left join centre ce on ce.id = ag.cod_centru
            WHERE {$cond} 
            ORDER BY {$sidx} {$sord} LIMIT {$start} , {$limit}";
        $sql = $this->db->QFetchRowArray($query);
        if(empty($sql))
            return json_encode($responce);

        foreach ($sql as $i => $row ){
            $responce->rows[$i]['id'] = $row['id'];
            $responce->rows[$i]['cell'] = array(
                $row['id'],
                $row['invoice'],
                $row['sumamnt'],
                $row['trndate'],
                $row['nume_ag'],
                $row['nume'],
                $row['wme'],
                $row['wme_message']
            );
        }
        $responce->page = $page;
        $responce->total = $total_pages;
        $responce->records = $count;
        return json_encode($responce);
    }

    function Json_ParteneriLog(){
        $responce = new StdClass();
        $responce->page = 0;
        $responce->total = 0;
        $responce->records = 0;
        return json_encode($responce);

        $cond = " (wme >= 3 OR wme = -1) ";
        if(isset($_POST['_search'])){
            $searchOn = $this->Strip($_POST['_search']);
            if ($searchOn == 'true') {
                $searchstr = $this->Strip($_POST['filters']);
                $cond .= $this->constructWhere($searchstr);
            }
        }

        $query = "SELECT count(*) as nr FROM clienti WHERE {$cond}";
        $result = $this->db->QFetchArray($query);
        $count = !empty($result['nr']) ? $result['nr'] : 0;

        $sidx = (!empty($_POST['sidx']))?$_POST['sidx']:"1"; // get index row - i.e. user click to sort
        $sord = (!empty($_POST['sord'])?$_POST['sord']:""); // get the direction

        $page = intval($_POST['page'] ?? 1); // get the requested page
        $limit = intval($_POST['rows'] ?? 250);

        if( $count >0 ) {$total_pages = ceil($count/$limit); }
        else { $total_pages = 0; }
        if ($page > $total_pages) $page=$total_pages;
        if ($limit<0) $limit = 0;
        $start = $limit*$page - $limit;
        if ($start<0) $start = 0;

        $query = "SELECT cod_cl, nume, data_op, wme, wme_message FROM clienti WHERE {$cond} ORDER BY {$sidx} {$sord} LIMIT {$start} , {$limit}";
        $sql = $this->db->QFetchRowArray($query);
        //error_log($query);
        if(!empty($sql)){
            foreach ($sql as $i => $row ){
                $responce->rows[$i]['id'] = $row['cod_cl'];
                $responce->rows[$i]['cell'] = array(
                    $row['cod_cl'],
                    $row['nume'],
                    $row['data_op'],
                    $row['wme'],
                    $row['wme_message']
                );
            }
        }
        $responce->page = $page;
        $responce->total = $total_pages;
        $responce->records = $count;
        return json_encode($responce);
    }


    function TrimiteParteneri(){
        $ids = is_array($_POST['sel_ids'] ?? [])? $_POST['sel_ids']:[];
        if(count($ids) == 0){
            error_log('nici un id');
            return array('message'=>'nici un id');
        }

        $limit_sql = "";
        $wme = new WinMentorNewApi($this->config);

        if($wme->MentenanceMode()){
            $this->log("WME MentenanceMode ".__FUNCTION__." ".intval($wme->MentenanceMode() / 60)." min");
            return;
        }

        $query = "SELECT c.cod_cl , IF(c.mod_plata = 0, 'CASH', 'PERIODICA') as  tip  FROM clienti c 
            WHERE c.fara_factura = 0 AND c.cod_cl IN (".implode(',',$ids).")";
        $sql= $this->db->QFetchRowArray($query);

        if(empty($sql))
            return;

        $total = count($sql);
        $ramase = $total;
        foreach ($sql as $i => $row) {
            $nr = $i+1;
            $wme->fisiere_generate = [];
            $ramase--;

            $this->UpdateSavePartener($wme, $row);
        }


    }

    function UpdateSavePartener($wme, $row, $save = false){
        $cod_cl = $row['cod_cl'];
        $cash = false;
        if ($row['tip'] == "CASH") {
            $cash = true;
            $wme->IDENTIFICATOR = 'CODEXTERN';
        } else {
            $wme->IDENTIFICATOR = 'CODFISCAL';
        }
        $rez = $wme->SalveazaPartener($row['cod_cl'], $cash, false);
        if($rez['edit'][0][0] == 200){
            $this->db->QueryUpdate('clienti', array(
                'WME' => 1,
                'wme_message' => ''
            ), " cod_cl = {$cod_cl}");
        } else if ($rez['edit'][0][0] == 413 && !$save) {
            $wme->SalveazaPartener($row['cod_cl'], $cash, true);
            $this->UpdateSavePartener($wme, $row, true);
        } else {
            $this->db->QueryUpdate('clienti', array(
                'WME' => 3,
                'wme_message' => $this->sanitize($rez['edit'][0][1])
            ), " cod_cl = {$cod_cl}");
        }
    }

    function UpdateFacturi($markAsTrimisa = false){
        $ids = is_array($_POST['sel_ids'] ?? []) ? $_POST['sel_ids']:[];
        if(count($ids) == 0){
            return array('message'=>'nici un id');
        }

        if($markAsTrimisa){
            $vi = array('wme'=>1);
        } else {
            $vi = array('wme'=>0, 'wme_message'=>'');
        }

        $this->db->QueryUpdate('exp_facturi', $vi, "id IN (".implode(",",$ids).")");
    }

    function GetLog(){
        $sql_facturi = $this->db->QFetchRowArray("SELECT invoice, wme, wme_message FROM exp_facturi WHERE wme >= 3 and anulata = 0");
        $return['facturi'] = $sql_facturi;
        $return['facturi_count'] = count($sql_facturi);

        //$sql_clienti = $this->db->QFetchRowArray("SELECT cod_cl as id, wme, wme_message FROM clienti WHERE wme >= 3 OR wme = -1");
        //$return['clienti'] = $sql_clienti;
        //$return['clienti_count'] = count($sql_clienti);

        $return['clienti'] = [];
        $return['clienti_count'] = 0;

        $return['success'] = true;
        $return['message'] = "";
        return $return;
    }


    function CronLock(){
        $minute = 90;
        if(file_exists('/var/www/logs/app/_tmp/cron.lock')){
            $lock_time = intval(file_get_contents("/var/www/logs/app/_tmp/cron.lock"));
            if($lock_time > 0 && (time() - $lock_time ) < ($minute * 60)){
                return (time() - $lock_time );
            } else {
                unlink("/var/www/logs/app/_tmp/cron.lock");
            }
        }
        return false;
    }

    function SetCronLock($active = true){
        if($active){
            file_put_contents("/var/www/logs/app/_tmp/cron.lock", time());
        } else {
            if(is_file("/var/www/logs/app/_tmp/cron.lock"))
                unlink("/var/www/logs/app/_tmp/cron.lock");
        }
    }

    function SetCronMode($active = true){
        if($this->CronLock() === false || !$active)
            $this->SetCronLock($active);

        $cron = $this->CronLock();
        if($cron === false){
            error_log("SetCronLock OFF");
            return array('error' => 0, 'cron_time' => 0);
        } else {
            error_log("SetCronLock ON");
            return array('error' => 0, 'cron_time' => (intval($cron / 60))?intval($cron / 60):1);
        }
    }

    function SetMentenanceMode($active = true){
        $wme = new WinMentorNewApi($this->config);

        if(!$wme->MentenanceMode() || !$active)
            $wme->SetMentenanceMode($active);

        $mentenanta = $wme->MentenanceMode();
        if($mentenanta){
            error_log("SetMentenanceMode ON");
            return array('error' => 0, 'mentenance' => intval($mentenanta / 60));
        } else {
            error_log("SetMentenanceMode OFF");
            return array('error' => 0, 'mentenance' => 0);
        }
    }

}