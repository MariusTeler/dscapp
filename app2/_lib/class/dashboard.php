<?php
/**
 * Created by PhpStorm.
 * User: ionut
 * Date: 22/03/2017
 * Time: 14:58
 */

class ModulDashboard extends BackEnd
{

    public $final_result;
    public $action_module;
    public $page_prefix;
    public $site_prefix;
    public $table;
    public $data_start = '2017-01-01';
    public $dashboard_urmarire;

    public $restante_colectari_livrari;

    /**
     * The constructor for the 'ModulDashboard' class
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

        $this->vars['title_page'] = 'Dashboard';
        $this->page_prefix = 'dashboard_';

        $this->checkSessionExpired();

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
        if(empty($arr[1]))
            $arr[1] = "";
        if(empty($arr[2]))
            $arr[2] = "";
        if(empty($arr[3]))
            $arr[3] = "";

        if (in_array("dashboard", $this->user_rights) || in_array("dashboard_urmarire", $this->user_rights) || $this->user_profile == 10){

            $this->dashboard_urmarire = (in_array("dashboard_urmarire", $this->user_rights))?true:false;

            if(isset($arr[1]) && $arr[1] == 'json' && isset($arr[2]) && $arr[2]=='restante_colectari_livrari'){
                echo $this->JSON_RestanteColectariLivrari(intval($arr[3] ?? 0));
                $flag = 1;
                exit();
            } else if(isset($arr[1]) && $arr[1] == 'export_xls' && isset($arr[2]) && $arr[2]=='restante_colectari_livrari'){
                echo $this->ExportXLSRestanteColectariLivrari(intval($arr[3] ?? 0));
                $flag = 1; //rambursuri_nelivrate
            } else if(isset($arr[1]) && $arr[1] == 'export_xls' && isset($arr[2]) && $arr[2]=='rambursuri_netrimise'){
                echo $this->ExportXLSRambursuri(intval($arr[3] ?? 0), 'netrimise');
                $flag = 1; //rambursuri_nelivrate
            } else if(isset($arr[1]) && $arr[1] == 'export_xls' && isset($arr[2]) && $arr[2]=='rambursuri_nelivrate'){
                echo $this->ExportXLSRambursuri(intval($arr[3] ?? 0), 'nelivrate');
                $flag = 1; //rambursuri_nelivrate
            } else if(isset($arr[1]) && $arr[1] == 'export_csv' && isset($arr[2]) && $arr[2]=='restante_si_rbs'){
                echo $this->ExportCSVRestanteSiRambursuri();
                $flag = 1; //rambursuri_nelivrate
            } else if(isset($arr[1]) && $arr[1] == 'json' && isset($arr[2]) && $arr[2]=='rambursuri_netrimise'){
                echo $this->JSON_Rambursuri('netrimise',intval($arr[3] ?? 0));
                $flag = 1; //rambursuri_nelivrate
            } else if(isset($arr[1]) && $arr[1] == 'json' && isset($arr[2]) && $arr[2]=='rambursuri_nelivrate'){
                echo $this->JSON_Rambursuri('nelivrate',intval($arr[3] ?? 0));
                $flag = 1;
            } else if(isset($arr[1]) && $arr[1] == 'json' && isset($arr[2]) && $arr[2]=='restante_si_rbs'){
                echo $this->JSON_RestanteSiRbs();
                $flag = 1;
            } else if(isset($arr[1]) && $arr[1] == 'urmarire'){
                $this->final_result = $this->Dashboard($this->dashboard_urmarire, intval($arr[2] ?? 0), intval($arr[3] ?? 0), intval($arr[4] ?? 0));
                $flag = 1;
            } else if(isset($arr[1]) && $arr[1] == 'urmarire_centre'){
                $this->final_result = $this->DashboardCentre();
                $flag = 1;
            } else {
                $this->final_result = $this->Dashboard();
                $flag = 1;
            }
        }
        else {
            $this->final_result = $this->Dashboard();
            $flag = 1;
        }

        if(empty($flag))
            $this->final_result = $this->PageNotFound();

    }

    function Dashboard($dashboard_urmarire = false, $centru_id = 0, $idata_start = 0, $idata_stop = 0){
        $vars = [];
        $vars['rbs_data_start'] = date('d.m.Y', strtotime($this->data_start));
        $vars['rbs_data_final'] = date('d.m.Y');
        $this->dashboard_urmarire = $dashboard_urmarire;

        if($centru_id >0)
            $this->user_centru_id = $centru_id;

        if(!empty($_REQUEST['centru']))
            $this->user_centru_id = $_REQUEST['centru'];

        if($idata_start > 0)
            $vars['rbs_data_start'] = date('d.m.Y', strtotime($idata_start));
        if($idata_stop > 0)
            $vars['rbs_data_final'] = date('d.m.Y', strtotime($idata_stop));

        if(!empty($_REQUEST['data_start']))
            $vars['rbs_data_start'] = date('d.m.Y', strtotime($_REQUEST['data_start']));
        if(!empty($_REQUEST['data_stop']))
            $vars['rbs_data_final'] = date('d.m.Y', strtotime($_REQUEST['data_stop']));

        $vars['rbs_centru_id'] = $this->user_centru_id;

        $centru_info = $this->db->QFetchArray("SELECT * FROM centre WHERE id=".$this->user_centru_id);
        $centru = (!empty($centru_info) && !$dashboard_urmarire) ? " / ".$centru_info['nume'] : "";

        if($dashboard_urmarire){
            $this->vars['title_page'] = '
            <span style="float: left; margin-right: 20px">Dashboard</span>
            <input type="hidden" name="centru" id="centru" class="field_input  w200" value="'.$this->user_centru_id.'" />
            <input type="text" name="centru_nume" id="centru_nume" class="field_input  w200" placeholder="Centru platitor" value="'.$centru_info['nume'].'" title="Centru" />
            <input type="button" name="afisare" class="button w60" value="Afisare" onclick="SetCentruDashboard();"/>
            <span id="data_range_text" style="float: right; margin-right: 20px">'. $vars['rbs_data_start'].'-'. $vars['rbs_data_final'].'</span>
            <div id="date_range_select">
                <div class="field_name w250" style="line-height: 20px;">
                    De la: <input type="text" id="data_start" value="'. $vars['rbs_data_start'].'">
                </div>
                <div class="field_name w250" style="line-height: 20px;">
                    Pana la: <input type="text" id="data_final" value="'. $vars['rbs_data_final'].'">
                </div>
            </div>
        ';
        } else {
            $this->vars['title_page'] = 'Dashboard '.$centru.'<input type="hidden" name="centru" id="centru" class="field_input  w200" value="'.$this->user_centru_id.'" />
            <div style="display:none">
                <input type="hidden" id="data_start" value="'. $vars['rbs_data_start'].'">
                <input type="hidden" id="data_final" value="'. $vars['rbs_data_final'].'">
            </div>
            <span id="data_range_text_1" style="float: right; margin-right: 20px">'. $vars['rbs_data_start'].'-'. $vars['rbs_data_final'].'</span>';
        }
        // $vars['restante_nr'] = $this->getRestante();
        return $this->Parse($this->page_prefix . 'index.html', $vars);
    }


    function DashboardCentre(){
        $vars = [];
        $vars['rbs_data_start'] = date('d.m.Y', strtotime($this->data_start));
        $vars['rbs_data_final'] = date('d.m.Y');
        $this->vars['title_page'] = '
            <span style="float: left; margin-right: 20px">Dashboard Centre</span>
            <div id="date_range_select">
                <div class="field_name w250" style="line-height: 20px;">
                    De la: <input type="text" id="data_start" value="'. $vars['rbs_data_start'].'">
                </div>
                <div class="field_name w250" style="line-height: 20px;">
                    Pana la: <input type="text" id="data_final" value="'. $vars['rbs_data_final'].'">
                </div>
                <input type="button" name="afisare" class="button w60" value="Afisare" onclick="SetDashboardCentreData();"/>
            </div>

        ';
        return $this->Parse($this->page_prefix . 'urmarire_centre.html', $vars);
    }

    function working_days_ago($days) {
        $count = 0;
        $day = strtotime('-1 day');
        while ($count < $days || date('N', $day) > 5) {
            $count++;
            $day = strtotime('-1 day', $day);
        }
        return $day;
    }

    function JSON_Rambursuri($type, $centru_id = 0){
        require_once 'rambursuri.php';
        $rambursuri = new  ModulRambursuri($this->config, 0, $this->db);

        if(empty($_REQUEST['data_start'])){
            $_REQUEST['data_start'] = date('d.m.Y', strtotime($this->data_start));
        }
        if(strtotime($_REQUEST['data_final']) && $this->dashboard_urmarire){
            $_REQUEST['data_final'] = date('d.m.Y',strtotime($_REQUEST['data_final']));
        } else {
            $_REQUEST['data_final'] = date('d.m.Y',$this->working_days_ago(0));
        }
        $_REQUEST['tip_plata'] = 4;
        $_REQUEST['dashboard'] = 1;
        $_REQUEST['dashboard_type'] = $type;
        $_REQUEST['date_range_for'] = 1;
        $_REQUEST['centru_id'] = $centru_id;
        
        return $rambursuri->JSON_Urmarire();
    }

    function JSON_RestanteColectariLivrari($centru_id = 0){
        $responce = new StdClass();
        if($centru_id == 0) $centru_id = $this->user_centru_id;

        $cond = "1=1";
        if(empty($_REQUEST['data_start'])){
            $_REQUEST['data_start'] = date('d.m.Y', strtotime($this->data_start));
        }
        if(empty($_REQUEST['data_final'])){
            $_REQUEST['data_final'] = date('d.m.Y');
        }

        $o_index = "use index(idfact,mod_plata)";
        $data_start = $this->TransformDate($_REQUEST['data_start']);
        $data_final = $this->TransformDate($_REQUEST['data_final']);

        $sidx = (!empty($_REQUEST['sidx']))?$_REQUEST['sidx']:""; // get index row - i.e. user click to sort
        $sord = (!empty($_REQUEST['sord'])?$_REQUEST['sord']:""); // get the direction
        if (!$sidx) $sidx = 1;

        $searchOn = $this->Strip($_REQUEST['_search']);
        if ($searchOn == 'true') {
            $searchstr = $this->Strip($_REQUEST['filters']);
            $cond .= $this->constructWhere($searchstr);
        }

        $page = intval($_REQUEST['page'] ?? 1);
		$limit = intval($_REQUEST['rows'] ?? 15);
		$sidx = trim($this->sanitize($_REQUEST['sidx'] ?? 1));
		$sord = trim($this->sanitize($_REQUEST['sord'] ?? 'asc'));

        $colectare_filtru = false;
        $livrare_filtru = false;
        if($centru_id > 0){
            $colectare_filtru = "IF(cle.zona_id > 0 and clec.id > 0, clec.id, lce.cod_centru) = {$centru_id}  and ep.expeditor_id = ep.platitor_id";
            $livrare_filtru = "IF(cld.zona_id > 0 and cldc.id > 0, cldc.id, lcd.cod_centru) = {$centru_id} and ep.destinatar_id = ep.platitor_id";
            $cond = str_replace("AND  ep.operatiune  = '1'", "AND {$colectare_filtru}", $cond);
            $cond = str_replace("AND  ep.operatiune  = '2'", "AND {$livrare_filtru}", $cond);
        }

        if($centru_id == 0) return json_encode($responce);

        $filtruAll = "";
        if(false !== $colectare_filtru) $filtruAll = $colectare_filtru;
        if(false !== $livrare_filtru) $filtruAll = $livrare_filtru;
        if(false !== $colectare_filtru && false !== $livrare_filtru) $filtruAll = "(({$colectare_filtru}) OR ({$livrare_filtru}))";

        $query = "SELECT COUNT(ep.cod_expeditie) as nr , sum(ep.valoare_totala_expeditie) + sum(ep.tva) as total
            FROM {$this->tables['exp_prelucrate']} ep {$o_index}
            left join clienti cle on cle.cod_cl = ep.expeditor_id
            left join clienti cld on cld.cod_cl = ep.destinatar_id
            LEFT JOIN zones clez ON clez.id = cle.zona_id
        	LEFT JOIN centre clec on clec.id = clez.centru_id
			LEFT JOIN zones cldz ON cldz.id = cld.zona_id
        	LEFT JOIN centre cldc on cldc.id = cldz.centru_id
            left join localitati lce ON lce.cod_lc = cle.cod_lc
            left join localitati lcd ON lcd.cod_lc = cld.cod_lc
            left join clienti clp on clp.cod_cl = ep.platitor_id
            LEFT JOIN zones clpz ON clpz.id = clp.zona_id
        	LEFT JOIN centre clpc on clpc.id = clpz.centru_id
            left join localitati lcp ON lcp.cod_lc = clp.cod_lc
            WHERE ep.data_expeditie >= (CURDATE() - INTERVAL 1 YEAR)
			and IF(clp.zona_id > 0 and clpc.id > 0, clpc.id, lcp.cod_centru) = {$centru_id}
			and ep.tip_exp in (0,5) and ep.mod_plata = 0 and ep.idfact = 0 and ep.anulata = 0
			and {$filtruAll} and ep.valoare_totala_expeditie > 0";

        $result = $this->db->QFetchArray($query);
        $count = !empty($result['nr']) ? $result['nr'] : 0;
        $total_val_exp = $result['total'];

        $count_colectare = 0;
        $total_colectare_val_exp = 0.00;
        if(false !== $colectare_filtru){
            $query = "SELECT COUNT(ep.cod_expeditie) as nr , sum(ep.valoare_totala_expeditie) + sum(ep.tva) as total
                FROM {$this->tables['exp_prelucrate']} ep  {$o_index}
                left join clienti cle on cle.cod_cl = ep.expeditor_id
                LEFT JOIN zones clez ON clez.id = cle.zona_id
        	    LEFT JOIN centre clec on clec.id = clez.centru_id
                left join localitati lce ON lce.cod_lc = cle.cod_lc
                left join clienti clp on clp.cod_cl = ep.platitor_id
                LEFT JOIN zones clpz ON clpz.id = clp.zona_id
        	    LEFT JOIN centre clpc on clpc.id = clpz.centru_id
                left join localitati lcp ON lcp.cod_lc = clp.cod_lc
                WHERE ep.data_expeditie >= (CURDATE() - INTERVAL 1 YEAR)
                and IF(clp.zona_id > 0 and clpc.id > 0, clpc.id, lcp.cod_centru) = {$centru_id}
                and ep.tip_exp in (0,5) and ep.mod_plata=0 and ep.idfact=0 and ep.anulata = 0 and ep.valoare_totala_expeditie > 0
                and {$colectare_filtru}";
            //error_log($query);
            $result_colectare = $this->db->QFetchArray($query);
            $count_colectare = $result_colectare['nr'];
            $total_colectare_val_exp = $result_colectare['total'];
        }

        $count_livrare = 0;
        $total_livrare_val_exp = 0.00;
        if(false !== $livrare_filtru){
            $query = "SELECT COUNT(ep.cod_expeditie) as nr , sum(ep.valoare_totala_expeditie) + sum(ep.tva) as total
                FROM {$this->tables['exp_prelucrate']} ep  {$o_index}
                left join clienti cld on cld.cod_cl = ep.destinatar_id
                LEFT JOIN zones cldz ON cldz.id = cld.zona_id
        	    LEFT JOIN centre cldc on cldc.id = cldz.centru_id
                left join localitati lcd ON lcd.cod_lc = cld.cod_lc
                left join clienti clp on clp.cod_cl = ep.platitor_id
                LEFT JOIN zones clpz ON clpz.id = clp.zona_id
        	    LEFT JOIN centre clpc on clpc.id = clpz.centru_id
                left join localitati lcp ON lcp.cod_lc = clp.cod_lc
                WHERE ep.data_expeditie >= (CURDATE() - INTERVAL 1 YEAR)
                and IF(clp.zona_id > 0 and clpc.id > 0, clpc.id, lcp.cod_centru) = {$centru_id}
                and ep.tip_exp in (0,5) and ep.mod_plata=0 and ep.idfact=0 and ep.anulata = 0 and ep.valoare_totala_expeditie > 0
                and {$livrare_filtru}";
            $result_livrare = $this->db->QFetchArray($query);
            $count_livrare = $result_livrare['nr'];
            $total_livrare_val_exp = $result_livrare['total'];
        }

        if( $count > 0 ) {$total_pages = ceil($count/$limit); }
        else { $total_pages = 0; }
        if ($page > $total_pages) $page=$total_pages;
        if ($limit<0) $limit = 0;
        $start = $limit*$page - $limit;
        if ($start<0) $start = 0;

        $query = "SELECT ep.expeditie, ep.data_expeditie, ep.tip_exp,
            agp.nume_ag as curier_preluare, agl.nume_ag as curier_livrare,
            cle.nume as expeditor, cld.nume as destinatar, lce.nume_lc as expeditor_localitate, lcd.nume_lc as destinatar_localitate,
            ep.valoare_totala_expeditie, ep.tva, ep.greutate, ep.km_preluare, ep.km_livrare, ep.valoare_asigurata,
            IF(cle.zona_id > 0 and clec.id > 0, clec.label, cee.label) as expeditor_centru_cod, 
            IF(cld.zona_id > 0 and cldc.id > 0, cldc.label, ced.label) as destinatar_centru_cod,
            IF(clp.zona_id > 0 and clpc.id > 0, clpc.label, cep.label) as platitor_centru_cod,
            IF(lce.cod_centru = {$centru_id} AND ep.expeditor_id = ep.platitor_id, 1 ,2) as operatiune,
            CASE WHEN rbs.expeditie       IS NOT NULL THEN rbs.expeditie      ELSE rtn.expeditie        END AS rbs_expeditie ,
            CASE WHEN rbs.data_expeditie  IS NOT NULL THEN rbs.data_expeditie ELSE rtn.data_expeditie   END AS rbs_data_expeditie ,
            CASE WHEN rbs.tip_exp         IS NOT NULL THEN rbs.tip_exp        ELSE rtn.tip_exp          END AS rbs_tip_exp
			FROM {$this->tables['exp_prelucrate']} ep  {$o_index}
            left join agenti agp ON agp.cod_ag = ep.curier_preluare_id
            left join agenti agl ON agl.cod_ag = ep.curier_livrare_id
            left join clienti cle on cle.cod_cl = ep.expeditor_id
            left join clienti cld on cld.cod_cl = ep.destinatar_id
            left join clienti clp on clp.cod_cl = ep.platitor_id
            LEFT JOIN zones clez ON clez.id = cle.zona_id
        	LEFT JOIN centre clec on clec.id = clez.centru_id
			LEFT JOIN zones cldz ON cldz.id = cld.zona_id
        	LEFT JOIN centre cldc on cldc.id = cldz.centru_id
			LEFT JOIN zones clpz ON clpz.id = clp.zona_id
        	LEFT JOIN centre clpc on clpc.id = clpz.centru_id
            left join localitati lce ON lce.cod_lc = cle.cod_lc
            left join localitati lcd ON lcd.cod_lc = cld.cod_lc
            left join localitati lcp ON lcp.cod_lc = clp.cod_lc
            left join centre cee ON cee.id = lce.cod_centru
            left join centre ced ON ced.id = lcd.cod_centru
			left join centre cep ON cep.id = lcp.cod_centru
			LEFT JOIN {$this->tables['exp_prelucrate']} rbs
            ON rbs.referire = ep.expeditie  AND rbs.tip_exp = 3 and rbs.anulata = 0
            LEFT JOIN {$this->tables['exp_prelucrate']} rtn
            ON rtn.referire = ep.expeditie AND rtn.tip_exp = 5 and rtn.anulata = 0
			WHERE ep.data_expeditie >= (CURDATE() - INTERVAL 1 YEAR)
			and IF(clp.zona_id > 0 and clpc.id > 0, clpc.id, cep.id) = {$centru_id}
			and ep.tip_exp in (0,5) and ep.mod_plata = 0 and ep.idfact = 0 and ep.anulata = 0 and ep.valoare_totala_expeditie > 0
			and {$filtruAll}
			AND {$cond}
			ORDER BY ". $sidx . " " . $sord . " LIMIT " . $start . " , " . $limit;

        // error_log($query);
        $sql = $this->db->QFetchRowArray($query);

        $this->restante_colectari_livrari = [];
        if (!empty($sql)) {
            foreach ($sql as $key => $row) {
                $new_row = array(
                    'operatiune'        => $row['operatiune'],
                    'data_expeditie'    => $row['data_expeditie'],
                    'expeditie'         => $row['expeditie'],
                    'curier_livrare'    => $row['curier_livrare'],
                    'expeditor'         => $row['expeditor'].'('.$row['expeditor_localitate'].')',
                    'expeditor_localitate' => $row['expeditor_localitate'],
                    'destinatar'        => $row['destinatar'].'('.$row['destinatar_localitate'].')',
                    'destinatar_localitate' => $row['destinatar_localitate'],
                    'incasata'          => $row['valoare_totala_expeditie']+$row['tva'],
                    'categorie'         => '',
                    'greutate'          => $row['greutate'],
                    'km_preluare'       => $row['km_preluare'],
                    'km_livrare'        => $row['km_livrare'],
                    'valoare_asigurata' => $row['valoare_asigurata'],
                    'expeditor_centru'  => $row['expeditor_centru_cod'],
                    'destinatar_centru' => $row['destinatar_centru_cod'],
                    'curier'            => $row['curier_preluare'],
                    'platitor_centru'   => $row['platitor_centru_cod'],
                    'valoare_totala_expeditie' => $row['valoare_totala_expeditie'],
                    'tva'               => $row['tva'],
                    'tip_exp'           => $row['tip_exp'],
                    'nr_scanari'        => $this->getExpScan($row['expeditie'])
                );
                $this->restante_colectari_livrari[] = $new_row;
            }
        }

        $total = [];
        if (count($this->restante_colectari_livrari)) {
            foreach ($this->restante_colectari_livrari as $key => $row) {
                if(empty($total[$row['operatiune']]))
                    $total[$row['operatiune']] = 0;

                $total[$row['operatiune']] += $row['valoare_totala_expeditie'];
                $responce->rows[$key]['id'] = $row['expeditie'];
                $responce->rows[$key]['cell'] = array($row['operatiune'],$row['data_expeditie'],$row['expeditie'],$row['curier_livrare'],strtoupper($row['destinatar']),strtoupper($row['destinatar_centru']),strtoupper($row['expeditor']),strtoupper($row['expeditor_centru']),$row['valoare_totala_expeditie'],$row['tip_exp'],$row['nr_scanari']);
            }
        }

        $responce->page = $page;
        $responce->total = $total_pages;
        $responce->records = $count;

        $responce->userdata['destinatar'] = $count_colectare.' Col. in valoare de '.$total_colectare_val_exp;
        $responce->userdata['expeditor'] = $count_livrare.' Liv. in valoare de '.$total_livrare_val_exp;
        $responce->userdata['valoare_totala_expeditie'] = number_format($total_val_exp, 2, '.', ' ');
        $responce->userdata['count_colectare'] = $count_colectare;
        $responce->userdata['total_colectare_val_exp'] = number_format($total_colectare_val_exp, 2, '.', ' ');
        $responce->userdata['count_livrare'] = $count_livrare;
        $responce->userdata['total_livrare_val_exp'] = number_format($total_livrare_val_exp, 2, '.', ' ');
        $responce->userdata['restante_colectari_livrari_date_range'] = date('d.m.Y', strtotime($data_start))." - ".date('d.m.Y', strtotime($data_final));


        $responce->userdata['total'] = $total;


        return json_encode($responce);

    }

    function ExportXLSRestanteColectariLivrari($centru_id = 0){
        $centru_id = $centru_id > 0 ? $centru_id : $this->user_centru_id;

        $filename = "restante_colectari_livrari";

        $_REQUEST['_search'] = '';
        $_REQUEST['page'] = 1;
        $_REQUEST['rows'] = 9999999999;

        $this->JSON_RestanteColectariLivrari($centru_id);

        $rows_keys = array(
            'operatiune',
            'data_expeditie',
            'expeditie',
            'curier_livrare',
            'expeditor',
            'expeditor_localitate',
            'destinatar',
            'destinatar_localitate',
            'incasata' ,
            'categorie',
            'greutate' ,
            'km_preluare' ,
            'km_livrare',
            'valoare_asigurata' ,
            'expeditor_centru',
            'destinatar_centru',
            'curier' ,
            'platitor_centru',
            'valoare_totala_expeditie',
            'tva',
            'tip_exp',
            'nr_scanari'
        );

        $tip_exp = array(
            0 => 'Initiala',
            5 => 'Returnare'
        );
        $ops = array(
            1 => 'Colectare',
            2 => 'Livrare'
        );

        foreach ($this->restante_colectari_livrari as $key=>$row){
            foreach ($rows_keys as $r){
                if ($r == 'tip_exp'){
                    $this->restante_colectari_livrari[$key][$r] = $tip_exp[intval($row[$r])];
                }
                else if($r == 'operatiune'){
                    $this->restante_colectari_livrari[$key][$r] = $ops[intval($row[$r])];
                }
                else if($r == 'data_expeditie'){
                    $this->restante_colectari_livrari[$key][$r] = $this->CreateDate($row[$r], 'd/m/Y');
                }
            }
        }
        $this->sqlToXls($filename, $this->restante_colectari_livrari, $rows_keys);
    }

    function ExportXLSRambursuri($centru_id, $type){
        $_REQUEST['_search'] = "";
        $_REQUEST['sord'] = "";
        $_REQUEST['rows'] = 99999999999;
        $centru_id = $centru_id > 0 ? $centru_id : $this->user_centru_id;

        require_once 'rambursuri.php';
        $rambursuri = new  ModulRambursuri($this->config, 0, $this->db);

        if(empty($_REQUEST['data_start'])){
            $_REQUEST['data_start'] = date('d.m.Y', strtotime($this->data_start));
        }
        if(empty($_REQUEST['data_final'])){
            $_REQUEST['data_final'] = date('d.m.Y',$this->working_days_ago(3));
        }

        $_REQUEST['tip_plata'] = 4;
        $_REQUEST['dashboard'] = 1;
        $_REQUEST['dashboard_type'] = $type;
        $_REQUEST['date_range_for'] = 1;
        $_REQUEST['centru_id'] = $centru_id;
        
        return $rambursuri->ExportUrmarire();
    }


    function ExportCSVRestanteSiRambursuri(){
        $cond = "";
        if(!empty($_REQUEST['data_start'])){
            $data_start = $this->TransformDate($_REQUEST['data_start']);
        } else {
            $cond .= "1=2 AND";
        }
        if(!empty($_REQUEST['data_final'])){
            $data_time_final_rbs = $data_time_final_rest = strtotime($this->TransformDate($_REQUEST['data_final']));
        } else {
            $data_time_final_rbs = $this->working_days_ago(3);
            $data_time_final_rest = time();
        }

        $data_stop_rest = date('Y-m-d', $data_time_final_rest);
        $data_stop_rbs = date('Y-m-d', $data_time_final_rbs);

        $cond .= "e.data_expeditie between '{$data_start}' AND  '{$data_stop_rest}'";

        $query = "
        select t.id as centru_id, t.label as centru_cod, t.nume as centru_nume, restante.nr as restante_count, restante.total as restante_val,
        rmb_net.nr as rambursuri_netrimise_count, rmb_net.total as rambursuri_netrimise_val,
        rmb_neliv.nr as rambursuri_nelivrate_count, rmb_neliv.total as rambursuri_nelivrate_val
	    from centre t
        left join (
            SELECT IF(clp.zona_id > 0 and clpc.id > 0, clpc.id, lcp.cod_centru) as platitor_centru_id, 
                COUNT(erst.cod_expeditie) as nr, sum(erst.valoare_totala_expeditie) + sum(erst.tva) as total
                FROM {$this->tables['exp_prelucrate']} erst
                left join clienti cle on cle.cod_cl = erst.expeditor_id
			    left join clienti cld on cld.cod_cl = erst.destinatar_id
                left join clienti clp on clp.cod_cl = erst.platitor_id
                LEFT JOIN zones clez ON clez.id = cle.zona_id
                LEFT JOIN centre clec on clec.id = clez.centru_id
                LEFT JOIN zones cldz ON cldz.id = cld.zona_id
                LEFT JOIN centre cldc on cldc.id = cldz.centru_id
                LEFT JOIN zones clpz ON clpz.id = clp.zona_id
                LEFT JOIN centre clpc on clpc.id = clpz.centru_id
                left join localitati lce ON lce.cod_lc = cle.cod_lc
			    left join localitati lcd ON lcd.cod_lc = cld.cod_lc
                left join localitati lcp ON lcp.cod_lc = clp.cod_lc
                WHERE erst.data_expeditie between '{$data_start}' AND  '{$data_stop_rest}'
                and erst.tip_exp in (0,5) and erst.mod_plata=0 and erst.idfact = 0 and erst.anulata = 0
                and (
                    (IF(cle.zona_id > 0 and clec.id > 0, clec.id, lce.cod_centru) = IF(clp.zona_id > 0 and clpc.id > 0, clpc.id, lcp.cod_centru) and erst.expeditor_id = erst.platitor_id) 
                        OR 
                    (IF(cld.zona_id > 0 and cldc.id > 0, cldc.id, lcd.cod_centru) = IF(clp.zona_id > 0 and clpc.id > 0, clpc.id, lcp.cod_centru) and erst.destinatar_id = erst.platitor_id))
                group by lcp.cod_centru
        ) as restante on restante.platitor_centru_id = t.id
        left join (
            SELECT IF(cle.zona_id > 0 and clec.id > 0, clec.id, lce.cod_centru) as expeditor_centru_id, COUNT(erl.cod_expeditie) as nr , sum(erl.ramburs) total
                FROM {$this->tables['exp_prelucrate']} erl
                left join clienti cle on cle.cod_cl = erl.expeditor_id
                LEFT JOIN zones clez ON clez.id = cle.zona_id
                LEFT JOIN centre clec on clec.id = clez.centru_id
                left join localitati lce ON lce.cod_lc = cle.cod_lc
                WHERE erl.data_expeditie between '{$data_start}' AND  '{$data_stop_rbs}'
                and erl.tip_exp in (0,5) and erl.ramburs > 0 AND erl.status_ramburs in (4,30) and erl.tip_plata in (0,3) and erl.anulata = 0
                group by IF(cle.zona_id > 0 and clec.id > 0, clec.id, lce.cod_centru)
        ) as rmb_neliv on rmb_neliv.expeditor_centru_id = t.id
        left join (
            SELECT IF(cld.zona_id > 0 and cldc.id > 0, cldc.id, lcd.cod_centru) as  destinatar_centru_id, COUNT(er.cod_expeditie) as nr , sum(er.ramburs) total
                FROM {$this->tables['exp_prelucrate']} er
                left join clienti cld on cld.cod_cl = er.destinatar_id
                LEFT JOIN zones cldz ON cldz.id = cld.zona_id
                LEFT JOIN centre cldc on cldc.id = cldz.centru_id
                left join localitati lcd ON lcd.cod_lc = cld.cod_lc
                WHERE er.data_expeditie between '{$data_start}' AND  '{$data_stop_rbs}'
                and er.tip_exp in (0,5) and er.ramburs > 0 AND er.status_ramburs in (0,23) and er.tip_plata in (0,3) and er.anulata = 0
                group by IF(cld.zona_id > 0 and cldc.id > 0, cldc.id, lcd.cod_centru)
        ) as rmb_net on rmb_net.destinatar_centru_id = t.id
        where t.deleted = 0
        order by t.nume asc
        ";
        $arr = [];

        $arr[0] = array('COD','Centru','Res. col/liv','Res. col/liv total','RBS netrimise','RBS netrimise val','RBS nelivrate','RBS nelivrate val');

        $result = $this->db->QFetchRowArray($query);
        if(!empty($result)){
            foreach ($result as $i => $row ){
                $arr[] = array(
                    // $row['id'],
                    $row['centru_cod'],
                    $row['centru_nume'],
                    intval($row['restante_count']),
                    number_format(floatval($row['restante_val']),2),
                    intval($row['rambursuri_netrimise_count']),
                    number_format(floatval($row['rambursuri_netrimise_val']),2),
                    intval($row['rambursuri_nelivrate_count']),
                    number_format(floatval($row['rambursuri_nelivrate_val']),2)
                );
            }
        }


        if(count($result)){
            $this->download_send_headers("data_export_" . date("Y-m-d") . ".csv");
            echo $this->array2csv($arr, "4096M");die;
        }

    }

    function JSON_RestanteSiRbs(){

        $responce = new StdClass();
        $data_start = $this->data_start;
        // $data_start = '2017-04-20';

        $cond = "";
        if(!empty($_REQUEST['data_start'])){
            $data_start = $this->TransformDate($_REQUEST['data_start']);
        } else {
            $cond .= "1=2 AND";
        }
        if(!empty($_REQUEST['data_final'])){
            $data_time_final_rbs = $data_time_final_rest = strtotime($this->TransformDate($_REQUEST['data_final']));
        } else {
            $data_time_final_rbs = $this->working_days_ago(3);
            $data_time_final_rest = time();
        }

        $data_stop_rest = date('Y-m-d', $data_time_final_rest);
        $data_stop_rbs = date('Y-m-d', $data_time_final_rbs);

        $cond .= "e.data_expeditie between '{$data_start}' AND  '{$data_stop_rest}'";

        $query = "
            select t.id as centru_id, t.label as centru_cod, t.nume as centru_nume, restante.nr as restante_count, restante.total as restante_val,
                rmb_net.nr as rambursuri_netrimise_count, rmb_net.total as rambursuri_netrimise_val,
                rmb_neliv.nr as rambursuri_nelivrate_count, rmb_neliv.total as rambursuri_nelivrate_val
            from centre t
            left join (
                SELECT IF(clp.zona_id > 0 and clpc.id > 0, clpc.id, lcp.cod_centru) as platitor_centru_id, COUNT(erst.cod_expeditie) as nr , sum(erst.valoare_totala_expeditie) + sum(erst.tva) as total
                    FROM {$this->tables['exp_prelucrate']} erst
                    left join clienti cle on cle.cod_cl = erst.expeditor_id
                    left join clienti cld on cld.cod_cl = erst.destinatar_id
                    left join clienti clp on clp.cod_cl = erst.platitor_id
                    LEFT JOIN zones clez ON clez.id = cle.zona_id
                    LEFT JOIN centre clec on clec.id = clez.centru_id
                    LEFT JOIN zones cldz ON cldz.id = cld.zona_id
                    LEFT JOIN centre cldc on cldc.id = cldz.centru_id
                    LEFT JOIN zones clpz ON clpz.id = clp.zona_id
                    LEFT JOIN centre clpc on clpc.id = clpz.centru_id
                    left join localitati lce ON lce.cod_lc = cle.cod_lc
                    left join localitati lcd ON lcd.cod_lc = cld.cod_lc
                    left join localitati lcp ON lcp.cod_lc = clp.cod_lc
                    WHERE erst.data_expeditie between '{$data_start}' AND  '{$data_stop_rest}'
                    and erst.tip_exp in (0,5) and erst.mod_plata = 0 and erst.idfact = 0 and erst.anulata = 0
                    and (
                        (IF(cle.zona_id > 0 and clec.id > 0, clec.id, lce.cod_centru) = IF(clp.zona_id > 0 and clpc.id > 0, clpc.id, lcp.cod_centru) and erst.expeditor_id = erst.platitor_id)
                            OR 
                        (IF(cld.zona_id > 0 and cldc.id > 0, cldc.id, lcd.cod_centru) = IF(clp.zona_id > 0 and clpc.id > 0, clpc.id, lcp.cod_centru) and erst.destinatar_id = erst.platitor_id))
                    group by platitor_centru_id
            ) as restante on restante.platitor_centru_id = t.id
            left join (
                SELECT IF(cle.zona_id > 0 and clec.id > 0, clec.id, lce.cod_centru) as expeditor_centru_id, COUNT(erl.cod_expeditie) as nr , sum(erl.ramburs) total
                    FROM {$this->tables['exp_prelucrate']} erl
                    left join clienti cle on cle.cod_cl = erl.expeditor_id
                    LEFT JOIN zones clez ON clez.id = cle.zona_id
                    LEFT JOIN centre clec on clec.id = clez.centru_id
                    left join localitati lce ON lce.cod_lc = cle.cod_lc
                    WHERE erl.data_expeditie between '{$data_start}' AND  '{$data_stop_rbs}'
                    and erl.tip_exp in (0,5) and erl.ramburs > 0 AND erl.status_ramburs in (4,30) and erl.tip_plata in (0,3) and erl.anulata = 0
                    group by IF(cle.zona_id > 0 and clec.id > 0, clec.id, lce.cod_centru)
            ) as rmb_neliv on rmb_neliv.expeditor_centru_id = t.id
            left join (
            SELECT IF(cld.zona_id > 0 and cldc.id > 0, cldc.id, lcd.cod_centru) as destinatar_centru_id, COUNT(e.cod_expeditie) as nr , sum(e.ramburs) total
                    FROM {$this->tables['exp_prelucrate']} e
                    left join clienti cld on cld.cod_cl = e.destinatar_id
                    LEFT JOIN zones cldz ON cldz.id = cld.zona_id
                    LEFT JOIN centre cldc on cldc.id = cldz.centru_id
                    left join localitati lcd ON lcd.cod_lc = cld.cod_lc
                    WHERE e.data_expeditie between '{$data_start}' AND  '{$data_stop_rbs}'
                    and e.tip_exp in (0,5) and e.ramburs > 0 AND e.status_ramburs in (0,23) and e.tip_plata in (0,3) and e.anulata = 0
                    group by IF(cld.zona_id > 0 and cldc.id > 0, cldc.id, lcd.cod_centru)
            ) as rmb_net on rmb_net.destinatar_centru_id = t.id
            where t.deleted = 0
            order by t.nume asc
        ";

        $result = $this->db->QFetchRowArray($query);

        $records = count($result);
        if($records){
            foreach ($result as $i => $row ){
                $responce->rows[$i]['id'] = $row['centru_id'];
                $responce->rows[$i]['cell'] = array(
                    // $row['id'],
                    $row['centru_cod'],
                    $row['centru_nume'],
                    intval($row['restante_count']),
                    number_format(floatval($row['restante_val']),2),
                    intval($row['rambursuri_netrimise_count']),
                    number_format(floatval($row['rambursuri_netrimise_val']),2),
                    intval($row['rambursuri_nelivrate_count']),
                    number_format(floatval($row['rambursuri_nelivrate_val']),2),
                );
            }
        }
        $responce->page = 1;
        $responce->total = 1;
        $responce->records = $records;
        echo json_encode($responce);

    }
}