<?php

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Color;

class ModulDecontCard extends BackEnd
{

    public $final_result;
    public $page_prefix;

    const RETUR_LIPSA_BANI = 3;

    const RBS_TIP_PLATA_CASH    = 0;
    const RBS_TIP_PLATA_BO      = 1;
    const RBS_TIP_PLATA_CEC     = 2;
    const RBS_TIP_PLATA_CONT    = 3;

    public $user_nume, $user_telefon, $data_start, $data_final, $exporta_centre_multiple, $today, $tip_cheltuieli_rbs_fa_ch;

    const TIP_CHELTUIELI_CASA_FA_CH = array(
        1 => 'Cheltuiala',
        2 => 'Lipsa bani',
        3 => 'Retur lipsa bani',
        6 => 'Compensare',
        7 => 'Livrare gresita',
        8 => 'Preluare gresita',
        10 => 'Achizitie paleti',
        11 => 'Avans salariu',
        12 => 'Chirie Motostivuitor',
        13 => 'Chirie SBK',
        14 => 'Consumabile auto',
        15 => 'ITP',
        16 => 'Mententanta depozit',
        17 => 'Ore suplimentare',
        18 => 'Piese auto',
        19 => 'Service auto',
        20 => 'Servicii spalatorie',
        21 => 'Taxa parcare',
        22 => 'Taxa port',
        23 => 'Tractari auto',
        24 => 'Vulcanizare',
    );
    const TIP_CHELTUIELI_CASA_RBS = array(
        //casa RBS
        30 => 'CASA RBS - Salarii',
        31 => 'CASA RBS - Auto',
        32 => 'CASA RBS - Plata SBK',
        33 => 'CASA RBS - Diverse',
    );

    function __construct($config = 0, $act = 1, $db = 0)
    {
        parent:: __construct($config, $db);

        $this->vars['title_page'] = 'Decont CARD';
        $this->today = $this->data_start = $this->data_final = date('Y-m-d');

        $this->user_nume = $_SESSION["user"]["nume"] ?? "";
		$this->user_telefon = $_SESSION["user"]["telefon"] ?? "";

        $this->tip_cheltuieli_rbs_fa_ch = self::TIP_CHELTUIELI_CASA_FA_CH;
        if(in_array($this->user_centru_id, [10, 47, 91])){ //cheltuiala din RBS la Otopeni
            foreach(self::TIP_CHELTUIELI_CASA_RBS as $id=>$tip){
                $this->tip_cheltuieli_rbs_fa_ch[$id] = $tip;
            }
        }

        if(!empty($_REQUEST['show_data']))
            $this->data_start = $this->data_final = $this->TransformDate($_REQUEST['show_data']);

            $this->page_prefix = 'decont_card_';
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
        if (in_array("exporta_centre_multiple", $this->user_rights)) {
            $this->exporta_centre_multiple = true;
        }

        if (in_array("new_decont", $this->user_rights) || $this->user_profile == 10){
            if (isset($arr[1]) && $arr[1] == 'afisare') {
                $this->final_result = $this->Afisare();
                $flag = 1;
            } else if (isset($arr[1]) && $arr[1] == 'add_cheltuiala') {
                echo $this->AddCheltuiala();
                $flag = 1;
            } else if (isset($arr[1]) && $arr[1] == 'edit_cheltuiala') {
                echo $this->EditCheltuiala();
                $flag = 1;
            } else if (isset($arr[1]) && $arr[1] == 'export_xls') {
                echo $this->exportDecontXLS($arr[2],$arr[3],filter_var($arr[4], FILTER_VALIDATE_BOOLEAN));
                $flag = 1;
            } else if (isset($arr[1]) && $arr[1] == 'total') {
                echo $this->getTotal();
                $flag = 1;
            } else if (isset($arr[1]) && $arr[1] == 'get_decont_dashboard') {
                echo $this->getDecontDashboard();
                $flag = 1;
            } else if (isset($arr[1]) && $arr[1] == 'inchide_decont') {
                echo $this->inchideDecontAgent();
                $flag = 1;
            } else if (isset($arr[1]) && $arr[1] == 'print') {
                echo $this->printDecontAgent();
                $flag = 1;
            }
            else if (isset($arr[1]) && $arr[1] == 'json' && isset($arr[2]) && $arr[2] == 'tabel_incasate_cash'){
                echo $this->JSON_IncasateCash(intval( $arr[3] ?? 0 ));
                $flag = 1;
            } 
            else if (isset($arr[1]) && $arr[1] == 'json' && isset($arr[2]) && $arr[2] == 'tabel_incasate_card'){
                echo $this->JSON_IncasateCard($arr[3]);
                $flag = 1;
            } else if (isset($arr[1]) && $arr[1] == 'json' && isset($arr[2]) && $arr[2] == 'tabel_neincasate'){
                echo $this->JSON_Neincasate($arr[3]);
                $flag = 1;
            } else if (isset($arr[1]) && $arr[1] == 'json' && isset($arr[2]) && $arr[2] == 'tabel_nelivrate'){
                echo $this->JSON_Nelivrate($arr[3]);
                $flag = 1;
            } else if (isset($arr[1]) && $arr[1] == 'json' && isset($arr[2]) && $arr[2] == 'tabel_cheltuieli'){
                echo $this->JSON_Cheltuieli($arr[3]);
                $flag = 1;
            } else if (isset($arr[1]) && $arr[1] == 'json' && isset($arr[2]) && $arr[2] == 'tabel_agenti_de_decontat'){
                echo $this->JSON_Agenti_De_Decontat();
                $flag = 1;
            }
            else if (isset($arr[1]) && $arr[1] == 'json' && isset($arr[2]) && $arr[2] == 'subgrid_agenti_de_decontat'){
                echo $this->JSON_IncasateCash(intval( $arr[3] ?? 0 ));
                $flag = 1;
            }
            else if (isset($arr[1]) && $arr[1] == 'scan_validare') {
                echo $this->ScanValidare();
                $flag = 1;
            }
        }
        
        if(empty($flag))
            $this->final_result = $this->PageNotFound();
    }

    function Afisare(){
        $vars = [];
        $vars['data_start'] = date('d.m.Y',strtotime($this->data_start));
        $vars['data_final'] = date('d.m.Y',strtotime($this->data_final));
        $agenti_arr = $this->getListaAgenti();
        $vars['agenti_decont'] = implode(PHP_EOL, $agenti_arr['agenti']);
        $centru_nume = $agenti_arr['centru_nume'];
        $vars['onload_js_version'] = $this->config['version']['onload_js_version'];

        $this->vars['title_page'] = "Decont CARD {$centru_nume}";

        $vars['TIP_CHELTUIALA_JS'] = $vars['TIP_CHELTUIALA_CASA_FA_CH'] = $vars['TIP_CHELTUIALA_CASA_RBS'] = "";
        $vars['TIP_CHELTUIALA_CASA_FA_CH'] .= '<option disabled>─────CASA Fa/Ch─────</option>';
        foreach (self::TIP_CHELTUIELI_CASA_FA_CH as $id=>$tip){
            $vars['TIP_CHELTUIALA_CASA_FA_CH'] .= '<option value="'.$id.'">'.$tip.'</option>';
            $vars['TIP_CHELTUIALA_JS'] .= $id.':'.$tip.';';
        }
        if(in_array($this->user_centru_id, [10, 47, 91])){ //cheltuiala din RBS la Otopeni, Bragadiru, Brasov
            $vars['TIP_CHELTUIALA_CASA_RBS'] .= '<option disabled>─────CASA RBS─────</option>';
            foreach (self::TIP_CHELTUIELI_CASA_RBS as $id=>$tip){
                $vars['TIP_CHELTUIALA_CASA_RBS'] .= '<option value="'.$id.'">'.$tip.'</option>';
                $vars['TIP_CHELTUIALA_JS'] .= $id.':'.$tip.';';
            }
        }
        if(substr($vars['TIP_CHELTUIALA_JS'], -1) == ";") $vars['TIP_CHELTUIALA_JS'] = substr($vars['TIP_CHELTUIALA_JS'], 0, -1);

        if(!empty($_REQUEST['show_data'])){
            $vars['data_set'] = '?show_data='.$_REQUEST['show_data'];
            $vars['custom_data_set'] = $_REQUEST['show_data'];
            $vars['data_set_title'] = 'Decont '. date('d.m.Y',strtotime($_REQUEST['show_data']));
        }

        if($this->exporta_centre_multiple){
            $vars['exporta_toate_centrele'] = '<div>
                <label for="toate_centrele">Exporta toate centrele</label>
                <input type="checkbox" id="toate_centrele">
            </div>';
        }
        return $this->Parse($this->page_prefix . 'index.html', $vars);
    }

    private function getAgentiForCentru($centru_id, $cond = "", $filter = "", $inactive_only = false){
        if($inactive_only === true){
            $cond .= " and ag.activ = 0 ";
        }
        $query_df = "
            select sum(df.suma) as transport, 0 as ramburs, 0 as chitante, df.agent_id as agent_id
            FROM decont_facturi df
            inner join agenti ag on ag.cod_ag = df.agent_id
            where df.decont_id = 0 and df.anulata = 0
            and df.transaction_id = 0 and ag.cod_centru = {$this->user_centru_id}
            group by df.agent_id
        ";
        $query_dr = "
            select 0 as transport, sum(dr.ramburs) as ramburs, 0 as chitante, dr.agent_id as agent_id
            FROM decont_rbs dr
            inner join agenti ag on ag.cod_ag = dr.agent_id
            where dr.decont_id = 0 and dr.anulata = 0
            and dr.transaction_id = 0 and ag.cod_centru = {$this->user_centru_id}
            group by dr.agent_id
        ";
        $query_cf = "
            select 0 as transport, 0 as ramburs, sum(cf.suma) as chitante, cf.agent_id as agent_id
            FROM decont_chitante cf
            inner join agenti ag on ag.cod_ag = cf.agent_id
            where cf.decont_id = 0 and cf.anulata = 0
            and cf.transaction_id = 0 and ag.cod_centru = {$this->user_centru_id}
            group by cf.agent_id
        ";

        $query_agenti = "select ag.cod_ag, ag.nume_ag, ag.telefon, ce.nume as centru_nume, ce.label as centru_label,
            COALESCE(nd.transport, 0) as transport, COALESCE(nd.ramburs, 0) as ramburs, COALESCE(nd.chitante, 0) as chitante,
            sum(COALESCE(da.total, 0)) as total_decontat, group_concat(da.id) as decont_ids
            from agenti ag
            inner join centre ce on ag.cod_centru = ce.id
            left join (
                select ndc.agent_id,
                sum(COALESCE(ndc.transport, 0)) as transport, sum(COALESCE(ndc.ramburs, 0)) as ramburs, 
                sum(COALESCE(ndc.chitante, 0)) as chitante
                from (
                    ({$query_df})
                    UNION
                    ({$query_dr})
                    UNION
                    ({$query_cf})
                ) as ndc
                group by ndc.agent_id
            ) as nd on ag.cod_ag = nd.agent_id
            left join decont_agent da on da.agent_id = ag.cod_ag and DATE(da.data) = '{$this->data_start}'
            where ag.cod_centru = {$this->user_centru_id} {$cond}
            group by ag.cod_ag
            HAVING ((transport + ramburs + chitante) > 0 or decont_ids is not null) {$filter}
            order by 2
        ";

        //error_log($query_agenti);
        return $this->db->QFetchRowArray($query_agenti);
    }

    private function getListaAgenti(){
        $agenti = [];
        //toti agentii activi + agenti inactivi cu nedecontate
        $query_agenti_activi_sql = "select ag.cod_ag, ag.nume_ag, ce.label as centru_label
            from agenti ag
            inner join centre ce on ag.cod_centru = ce.id
            where ag.cod_centru = {$this->user_centru_id} and ag.activ = 1
        ";
        $agenti_activi = $this->db->QFetchRowArray($query_agenti_activi_sql);
        if(!empty($agenti_activi)) {
            foreach ($agenti_activi as $agent) {
                $agenti[] = [
                    'cod_ag' => $agent['cod_ag'],
                    'nume_ag' => $agent['nume_ag'],
                    'centru_label' => $agent['centru_label']
                ];
            }
        }
        $agenti_inactivi = $this->getAgentiForCentru($this->user_centru_id, "", "", true);
        if(!empty($agenti_inactivi)){
            foreach ($agenti_inactivi as $agent){
                if(!in_array($agent['cod_ag'], array_column($agenti, 'cod_ag'))){
                    $agenti[] = [
                        'cod_ag' => $agent['cod_ag'],
                        'nume_ag' => $agent['nume_ag'],
                        'centru_label' => $agent['centru_label']
                    ];
                }
            }
        }
        $options = [];
        //sort agenti by nume_ag
        if(count($agenti) > 0){
            usort($agenti, function($a, $b) {
                return strcmp($a['nume_ag'], $b['nume_ag']);
            });
        
            foreach ($agenti as $cod_ag => $agent){
                $options[] = '<option value="'.$agent['cod_ag'].'" data-tokens="'.$agent['nume_ag']. '">'.$agent['nume_ag']. ' (' .$agent['centru_label'].')</option>';
            }
        }
        return ['centru_nume' => ($sql[0]['nume'] ?? ''), 'agenti' => $options];
    }

    public function AddCheltuiala(){
        $sucess = 0;
        $data['data'] = date('Y-m-d H:i:s');
        $data['user_id'] = $this->user_id;
        $data['tip'] = intval(Backend::sSanitize($_POST['tip'] ?? 1));
        $data['agent_id'] = intval(Backend::sSanitize($_POST['agent_id'] ?? 0));
        $data['descriere'] = Backend::sSanitize($_POST['descriere'] ?? "");
        $data['suma'] = round(abs(floatval(Backend::sSanitize($_POST['suma'] ?? 0.00))), 2);
        $data['centru_id'] = $this->user_centru_id;

        if(!in_array($data['tip'], array_keys($this->tip_cheltuieli_rbs_fa_ch))) {
            header('HTTP/1.1 500 Internal Server Cheltuiala');
            header('Content-Type: application/json; charset=UTF-8');
            die(json_encode(['message'=>'Tip cheltuiala invalid !']));
        }

        if($data['suma'] < 1) {
            header('HTTP/1.1 500 Internal Server Cheltuiala');
            header('Content-Type: application/json; charset=UTF-8');
            die(json_encode(['message'=>'Suma invalida !']));
        }

        //decont casa
        /*
        $decont_casa_sql = $this->db->QFetchArray("SELECT id, casa_fa_ch, casa_rbs
            FROM decont_casa 
            WHERE centru_id = {$this->user_centru_id} AND created_at = '{$this->today}'");
        if(empty($decont_casa_sql)){
                header('HTTP/1.1 500 Internal Server Cheltuiala');
                header('Content-Type: application/json; charset=UTF-8');
                if(in_array($data['tip'], array_keys(self::TIP_CHELTUIELI_CASA_FA_CH))) {
                    die(json_encode(['message'=>'Casa Fa/Ch este goala !']));
                } else {
                    die(json_encode(['message'=>'Casa RBS este goala !']));
            }
        } else {
            if(in_array($data['tip'], array_keys(self::TIP_CHELTUIELI_CASA_FA_CH)) && $decont_casa_sql['casa_fa_ch'] < $data['suma']) {
                header('HTTP/1.1 500 Internal Server Cheltuiala');
                header('Content-Type: application/json; charset=UTF-8');
                die(json_encode(['message'=>'Nu sunt destui bani in Casa Fa/Ch !']));
            } else if(in_array($data['tip'], array_keys(self::TIP_CHELTUIELI_CASA_RBS)) && $decont_casa_sql['casa_rbs'] < $data['suma']) {
                header('HTTP/1.1 500 Internal Server Cheltuiala');
                header('Content-Type: application/json; charset=UTF-8');
                die(json_encode(['message'=>'Nu sunt destui bani in Casa RBS !']));
            }
        }
        */
    
        return json_encode(['success' => $this->db->QueryInsert('decont_cheltuieli', $data)]);
    }

    public function EditCheltuiala(){
        $id = intval(Backend::sSanitize($_POST['id'] ?? 0));
        $data['tip'] = intval(Backend::sSanitize($_POST['tip'] ?? 1));
        $data['descriere'] = Backend::sSanitize($_POST['descriere'] ?? "");
        $data['suma'] = round(abs(floatval(Backend::sSanitize($_POST['suma'] ?? 0.00))), 2);

        if(!in_array($data['tip'], array_keys($this->tip_cheltuieli_rbs_fa_ch))) {
            header('HTTP/1.1 500 Internal Server Cheltuiala');
            header('Content-Type: application/json; charset=UTF-8');
            die(json_encode(['message'=>'Tip cheltuiala invalid !']));
        }

        /*
        //decont casa
        $decont_casa_sql = $this->db->QFetchArray("SELECT id, casa_fa_ch, casa_rbs
            FROM decont_casa 
            WHERE centru_id = {$this->user_centru_id} AND created_at = '{$this->today}'");
        if(empty($decont_casa_sql)){
            header('HTTP/1.1 500 Internal Server Cheltuiala');
            header('Content-Type: application/json; charset=UTF-8');
            if(in_array($data['tip'], array_keys(self::TIP_CHELTUIELI_CASA_FA_CH))) {
                die(json_encode(['message'=>'Casa Fa/Ch este goala !']));
            } else {
                die(json_encode(['message'=>'Casa RBS este goala !']));
            }
        } else {
            if(in_array($data['tip'], array_keys(self::TIP_CHELTUIELI_CASA_FA_CH)) && $decont_casa_sql['casa_fa_ch'] < $data['suma']) {
                header('HTTP/1.1 500 Internal Server Cheltuiala');
                header('Content-Type: application/json; charset=UTF-8');
                die(json_encode(['message'=>'Nu sunt destui bani in Casa Fa/Ch !']));
            } else if(in_array($data['tip'], array_keys(self::TIP_CHELTUIELI_CASA_RBS)) && $decont_casa_sql['casa_rbs'] < $data['suma']) {
                header('HTTP/1.1 500 Internal Server Cheltuiala');
                header('Content-Type: application/json; charset=UTF-8');
                die(json_encode(['message'=>'Nu sunt destui bani in Casa RBS !']));
            }
        }
        */

        if($data['suma'] == 0.00) {
            $this->db->Query("DELETE FROM decont_cheltuieli where id = {$id}");
            return 1;
        }
        if($id > 0)
            return $this->db->QueryUpdate('decont_cheltuieli', $data, "id = {$id}");
        return 0;
    }

    public function getTotal(){
        $sucess = 0;
        $agent_id = intval(Backend::sSanitize($_POST['agent_id'] ?? 0));

        $total_de_decontat = 0.00;
        $total_ramburs = $this->db->QFetchArray("select sum(ramburs) as total_ramburs, sum(if(ramburs > 0, 1, 0)) as total_ramburs_count 
            FROM decont_rbs where agent_id = {$agent_id} and decont_id = 0 and vCR = 1 and anulata = 0
            and transaction_id = 0");
        $total_transport = $this->db->QFetchArray("select sum(suma) as total_transport, sum(if(suma > 0, 1, 0)) as total_transport_count 
            FROM decont_facturi where agent_id = {$agent_id} and decont_id = 0 and vFF = 1 and anulata = 0
            and transaction_id = 0");
        $total_chitante = $this->db->QFetchArray("select sum(suma) as total_chitante, sum(if(suma > 0, 1, 0)) as total_chitante_count 
            FROM decont_chitante where agent_id = {$agent_id} AND decont_id = 0 and vCF = 1 and anulata = 0
            and transaction_id = 0");
        $total_cheltuieli_sql = $this->db->QFetchRowArray("select  sum(if(suma > 0, 1, 0)) as total_cheltuieli_count, sum(suma) as total_cheltuieli, tip
            FROM decont_cheltuieli 
            where agent_id = {$agent_id} AND decont_id = 0 and anulata = 0 group by tip");
        
        $total_cheltuieli = [];
        $total_de_decontat += empty($total_ramburs) ? 0 : $total_ramburs['total_ramburs'];
        $total_de_decontat += empty($total_transport) ? 0 : $total_transport['total_transport'];
        $total_de_decontat += empty($total_chitante) ? 0 : $total_chitante['total_chitante'];

        if(!empty($total_cheltuieli_sql)){
            foreach ($total_cheltuieli_sql as $row){
                $total_cheltuieli[$row['tip']]['count'] = $row['total_cheltuieli_count'];
                $total_cheltuieli[$row['tip']]['total'] = $row['total_cheltuieli'];
                if($row['tip'] == self::RETUR_LIPSA_BANI){
                    $total_de_decontat += $row['total_cheltuieli'];
                } else {
                    $total_de_decontat -= $row['total_cheltuieli'];
                }
            }
        }

        $html = $html_total = "";

        if(empty($total_transport['total_transport']))
            $total_transport['total_transport'] = '0.00';
        if(empty($total_transport['total_transport_count']))
            $total_transport['total_transport_count'] = 0;
        if(empty($total_ramburs['total_ramburs']))
            $total_ramburs['total_ramburs'] = '0.00';
        if(empty($total_ramburs['total_ramburs_count']))
            $total_ramburs['total_ramburs_count'] = 0;
        if(empty($total_chitante['total_chitante']))
            $total_chitante['total_chitante'] = '0.00';
        if(empty($total_chitante['total_chitante_count']))
            $total_chitante['total_chitante_count'] = 0;

        $html_total .= '<li><span class="label-ag">Contravaloare transport</span><span class="count-nr">'.$total_transport['total_transport_count'].'</span><span class="decount-val">'.number_format($total_transport['total_transport'], 2, '.', '') .'</span></li>';
        $html_total .= '<li><span class="label-ag">Ramburs</span><span class="count-nr">'.$total_ramburs['total_ramburs_count'].'</span><span class="decount-val">'.number_format($total_ramburs['total_ramburs'], 2, '.', '').'</span></li>';
        $html_total .= '<li><span class="label-ag">Chitante client CTR</span><span class="count-nr">'.$total_chitante['total_chitante_count'].'</span><span class="decount-val">'.number_format($total_chitante['total_chitante'], 2, '.', '').'</span></li>';
        
        foreach ($total_cheltuieli as $tip => $cheltuiala){
            $html_total .= '<li><span class="label-ag">'.($this->tip_cheltuieli_rbs_fa_ch[$tip] ?? 'unknown').'</span><span class="count-nr">'.$cheltuiala['count'].'</span><span class="decount-val">'.(($tip != self::RETUR_LIPSA_BANI && $cheltuiala['total'] > 0 ? "-" : "").number_format(abs($cheltuiala['total']), 2, '.', '')).'</span></li>';
        }

        $html_total .= '<li><hr style="border-top: 1px solid red; margin-top:2px; margin-bottom:2px"/></li>';
        $html_total .= '<li class="total-ag-incasat" style="margin-top:5px"><span class="label-ag">Total de decontat</span><span class="count-nr"></span><span class="decount-val">'.number_format($total_de_decontat, 2, '.', '').'</span></li>';

        $html = '<ul>'.$html_total.'</ul>';

        return json_encode(
            array(
                'html'=> $html,
                'success' => $sucess
            )
        );
    }

    public function inchideDecontAgent(){
        $agent_id = intval(Backend::sSanitize($_POST['agent_id'] ?? 0));
        if($agent_id == 0){
            header('HTTP/1.1 500 Internal Server No Agent');
            header('Content-Type: application/json; charset=UTF-8');
            die(json_encode(['message'=>'unknown agent']));
        }

        //de decontat
        $total_ramburs = $this->db->QFetchArray("select count(id) as nr_ramburs, sum(ramburs) as total_ramburs
            FROM decont_rbs where agent_id = {$agent_id} and decont_id = 0 and vCR = 1 and anulata = 0
            and transaction_id = 0");
        $total_transport = $this->db->QFetchArray("select count(id) as nr_transport, sum(suma) as total_transport
            FROM decont_facturi where agent_id = {$agent_id} and decont_id = 0 and vFF = 1 and anulata = 0
            and transaction_id = 0");
        $total_chitante = $this->db->QFetchArray("select count(id) as nr_chitante, sum(suma) as total_chitante
            FROM decont_chitante where agent_id = {$agent_id} AND decont_id = 0 and vCF = 1 and anulata = 0
            and transaction_id = 0");
        $total_cheltuieli = $this->db->QFetchRowArray("select sum(suma) as total_cheltuieli, tip 
            FROM decont_cheltuieli 
            where agent_id = {$agent_id} AND decont_id = 0 and anulata = 0 group by tip");

        //de decontat
        $total_de_decontat = 0.00;
        //decont casa
        $data_decont_casa = [];
        $data_decont_casa['nr_rbs_decontate'] = 0;
        $data_decont_casa['rbs_decontate'] = 0.00;
        $data_decont_casa['facturi_decontate'] = 0.00;
        $data_decont_casa['chitante_decontate'] = 0.00;
        $data_decont_casa['cheltuieli_fa_ch'] = 0.00;
        $data_decont_casa['cheltuieli_rbs'] = 0.00;

        if(!empty($total_ramburs) && is_array($total_ramburs)){
            $data_decont_casa['nr_rbs_decontate'] = $total_ramburs['nr_ramburs'];
            $data_decont_casa['rbs_decontate'] = round($total_ramburs['total_ramburs'], 2);
            $total_de_decontat += $data_decont_casa['rbs_decontate'];
        }
        if(!empty($total_transport) && is_array($total_transport)){
            $data_decont_casa['facturi_decontate'] = round($total_transport['total_transport'], 2);
            $total_de_decontat += $data_decont_casa['facturi_decontate'];
        }
        if(!empty($total_chitante) && is_array($total_chitante)){
            $data_decont_casa['chitante_decontate'] = round($total_chitante['total_chitante'], 2);
            $total_de_decontat += $data_decont_casa['chitante_decontate'];
        }

        if(!empty($total_cheltuieli)){
            foreach ($total_cheltuieli as $row){
                if($row['tip'] == self::RETUR_LIPSA_BANI){
                    $total_de_decontat += abs($row['total_cheltuieli']);
                    $data_decont_casa['cheltuieli_fa_ch'] += abs($row['total_cheltuieli']);
                } else {
                    $total_de_decontat -= abs($row['total_cheltuieli']);
                    if(in_array($row['tip'], array_keys(self::TIP_CHELTUIELI_CASA_RBS))){
                        $data_decont_casa['cheltuieli_rbs'] -= abs($row['total_cheltuieli']);
                    }
                    else {
                        //cheltuieli fa/ch
                        $data_decont_casa['cheltuieli_fa_ch'] -= abs($row['total_cheltuieli']);
                    }
                }
            }
        }

        if($total_de_decontat == 0.00){
            header('HTTP/1.1 500 Internal Server Total incasat');
            header('Content-Type: application/json; charset=UTF-8');
            die(json_encode(['message'=>'nothing to do : total de incasat = 0.00']));
        }

        try {
            $this->db->BeginTransaction();
            $count1 = 0;
            $query1 = "SELECT count(*) as nr, group_concat(id) as ids 
                FROM decont_rbs 
                where decont_id = 0 and (vCR = 1 or transaction_id > 0) 
                and anulata = 0 AND agent_id = {$agent_id}";   
            $result1 = $this->db->QFetchArray($query1);
            if(!empty($result1['nr']))
                $count1 = $result1['nr'];

            $count2 = 0;
            $query2 = "SELECT count(*) as nr, group_concat(id) as ids FROM decont_chitante where decont_id = 0 and (vCF = 1 or transaction_id > 0) and anulata = 0 AND agent_id = {$agent_id}";   
            $result2 = $this->db->QFetchArray($query2);
            if(!empty($result2['nr']))
                $count2 = $result2['nr'];

            $count3 = 0;
            $query3 = "SELECT count(*) as nr, group_concat(id) as ids FROM decont_facturi where decont_id = 0 and (vFF = 1 or transaction_id > 0) and anulata = 0 AND agent_id = {$agent_id}";   
            $result3 = $this->db->QFetchArray($query3);
            if(!empty($result3['nr']))
                $count3 = $result3['nr'];

            $count4 = 0;
            $query4 = "SELECT count(*) as nr, group_concat(id) as ids FROM decont_cheltuieli where decont_id = 0 and anulata = 0 AND agent_id = {$agent_id}";
            $result4 = $this->db->QFetchArray($query4);
            if(!empty($result4['nr']))
                $count4 = $result4['nr'];
            
            $count = $count1 + $count2 + $count3 + $count4;
            $decont_id = 0;
            if($count > 0) {
                $data = [];
                $data['data'] = date('Y-m-d H:i:s');
                $data['centru_id'] = $this->user_centru_id;
                $data['user_id'] = $this->user_id;
                $data['total'] = $total_de_decontat;
                $data['agent_id'] = $agent_id;

                $decont_id = $this->db->QueryInsert('decont_agent', $data);
                $this->db->QueryUpdate('decont_rbs', array('decont_id'=> $decont_id, 'vCR'=> 1), " decont_id = 0 and (vCR = 1 or transaction_id > 0) and anulata = 0 AND agent_id = {$agent_id}");
                $this->db->QueryUpdate('decont_chitante', array('decont_id'=> $decont_id, 'vCF'=> 1), " decont_id = 0 and (vCF = 1 or transaction_id > 0) and anulata = 0 AND agent_id = {$agent_id}");
                $this->db->QueryUpdate('decont_facturi', array('decont_id'=> $decont_id, 'vFF'=> 1), " decont_id = 0 and (vFF = 1 or transaction_id > 0) and anulata = 0 AND agent_id = {$agent_id}");
                $this->db->QueryUpdate('decont_cheltuieli', array('decont_id'=> $decont_id), " decont_id = 0 and anulata = 0 AND agent_id = {$agent_id}");
                

                if($count3 > 0 && $count1 > 0)
                    $this->db->QueryUpdate('decont_expeditii', array('decont_id'=> $decont_id), " decont_id = 0 and anulata = 0 AND agent_id = {$agent_id} 
                        AND (factura_id in ({$result3['ids']}) OR ramburs_id in ({$result1['ids']}))");
                else if($count3 > 0)
                    $this->db->QueryUpdate('decont_expeditii', array('decont_id'=> $decont_id), " decont_id = 0 and anulata = 0 AND agent_id = {$agent_id} 
                        AND factura_id in ({$result3['ids']})");
                else if($count1 > 0)
                    $this->db->QueryUpdate('decont_expeditii', array('decont_id'=> $decont_id), " decont_id = 0 and anulata = 0 AND agent_id = {$agent_id} 
                        AND ramburs_id in ({$result1['ids']})");
                //update status ramburs = decontat la expeditie daca status_ramburs == 0
                if($count1 > 0){
                    $query_exp = "SELECT ep.cod_expeditie, ep.status_ramburs, ep.tip_plata
                        FROM exp_prelucrate ep
                        where ep.expeditie in (select de.expeditie FROM decont_expeditii de where de.ramburs_id in ({$result1['ids']}))
                        and ep.anulata = 0";
                    $sql_exp = $this->db->QFetchRowArray($query_exp);
                    if (!empty($sql_exp)) {
                        foreach ($sql_exp as $key => $row) {
                            if(!empty($row['cod_expeditie']) && $row['status_ramburs'] == 0)
                                $this->setStatusRamburs($row['cod_expeditie'], $row['status_ramburs'], 23, $row['tip_plata']);
                        }
                    }
                }
            }
            $this->setCheckpoints($decont_id);
            //decont casa
            $data_decont_casa['cheltuieli_fa_ch'] = round($data_decont_casa['cheltuieli_fa_ch'], 2);
            $data_decont_casa['cheltuieli_rbs'] = round($data_decont_casa['cheltuieli_rbs'], 2);
            $data_decont_casa['casa_rbs'] = round($data_decont_casa['rbs_decontate'] + $data_decont_casa['cheltuieli_rbs'], 2);
            $data_decont_casa['casa_fa_ch'] = round($data_decont_casa['facturi_decontate'] + $data_decont_casa['chitante_decontate'] + $data_decont_casa['cheltuieli_fa_ch'], 2);

            //upsert decont casa
            $decont_casa_sql = $this->db->QFetchArray("SELECT id, nr_rbs_decontate, rbs_decontate, facturi_decontate, 
                chitante_decontate, cheltuieli_fa_ch, cheltuieli_rbs, salarii_confirmate, 
                nr_rbs_generate, rbs_generate, casa_fa_ch, casa_rbs
                FROM decont_casa 
                WHERE centru_id = {$this->user_centru_id} AND created_at = '{$this->today}'");
            if(empty($decont_casa_sql)){
                //insert
                $data_decont_casa['created_at'] = $this->today;
                $data_decont_casa['centru_id'] = $this->user_centru_id;
                $data_decont_casa['user_id'] = $this->user_id;
                $decont_casa_id = $this->db->QueryInsert('decont_casa', $data_decont_casa);
                //transfer trezorerie
                require_once 'FinanciarCentre.php';
                ModulFinanciarCentre::transferTrezorerie($this->db, $this->user_centru_id, $this->user_id, $decont_casa_id, $data_decont_casa['casa_rbs'], $data_decont_casa['casa_fa_ch'], $this->today = date('Y-m-d'));

            } else {
                //update
                $decont_casa_id = $decont_casa_sql['id'];
                $data_decont_casa['casa_rbs'] = round($data_decont_casa['casa_rbs'] + $decont_casa_sql['casa_rbs'], 2);
                $data_decont_casa['nr_rbs_decontate'] += $decont_casa_sql['nr_rbs_decontate'];
                $data_decont_casa['rbs_decontate'] = round($data_decont_casa['rbs_decontate'] + $decont_casa_sql['rbs_decontate'], 2);
                $data_decont_casa['facturi_decontate'] = round($data_decont_casa['facturi_decontate'] + $decont_casa_sql['facturi_decontate'], 2);
                $data_decont_casa['chitante_decontate'] = round($data_decont_casa['chitante_decontate'] + $decont_casa_sql['chitante_decontate'], 2);
                $data_decont_casa['cheltuieli_fa_ch'] = round($data_decont_casa['cheltuieli_fa_ch'] + $decont_casa_sql['cheltuieli_fa_ch'], 2);
                $data_decont_casa['cheltuieli_rbs'] = round($data_decont_casa['cheltuieli_rbs'] + $decont_casa_sql['cheltuieli_rbs'], 2);
                $data_decont_casa['casa_fa_ch'] = round($data_decont_casa['casa_fa_ch'] + $decont_casa_sql['casa_fa_ch'], 2);
                $this->db->QueryUpdate('decont_casa', $data_decont_casa, "id = {$decont_casa_id}");
            }

            $this->db->CommitTransaction();
            return json_encode(['decont_id' => $decont_id]); 
        }
        catch (PDOException $e) {
            $this->db->RollbackTransaction();
            error_log("inchideDecontAgent : ".$e->getMessage());
            header('HTTP/1.1 500 Internal Server');
            header('Content-Type: application/json; charset=UTF-8');
            die(json_encode(['message'=>'eroare server']));
        }
        return json_encode(['decont_id' => 0]);
    }

    /*
        se dau checkpointuri DCC la expeditii colectare (operatiune = 1)
        se dau checkpointuri DCL la expeditii livrare (operatiune = 2)
        se dau checkpointuri COK la expeditii livrare (operatiune = 2) care nu au COK = 5 in scanari_coduri
        COK in scanari_coduri : tip = 5
        operatiune in decont_expeditii : =1 la colectare, =2 la livrare
        1. operatiune = 1 : se da checkpoint DCC = 31
        2. operatiune = 2 : se da checkpoint DCL = 32 + checkpoint COK = 5
        cronul mulineaza expeditiile din scanari_coduri cu COK (tip = 5) si le da status livrata
    */
    private function setCheckpoints($decont_id = 0){
        $decont_id = intval($decont_id);
        if($decont_id == 0) return;

        //caut expeditiile care nu au cok
        $query = "
        (
            select group_concat(sc.tip) as ckps, de.expeditie, de.operatiune, de.agent_id
            FROM decont_expeditii de
            LEFT JOIN scanari_coduri sc on sc.expeditie = de.expeditie and sc.is_awb = 1
            where de.operatiune = 2
            and de.decont_id = {$decont_id}
            group by de.expeditie
        )
        UNION
        (
            select de.id as ckps, de.expeditie, de.operatiune, de.agent_id
            FROM decont_expeditii de
            where de.operatiune = 1
            and de.decont_id = {$decont_id}
        )
        ";
        $today = date('Y-m-d H:i:s');
        //error_log($query);
        $sql = $this->db->QFetchRowArray($query);
        if (!empty($sql)) {
            //borderou scanare
            $expeditii = [];
            $expeditii_cok = [];
		    $bo_sc = $this->db->QueryInsert($this->tables['scanari_borderouri'], ['data' => $today]);
            foreach ($sql as $key => $row) {                
                $vi=[];		
		        $vi['cod'] = $vi['expeditie'] = intval($row['expeditie']);
                $vi['is_awb'] = 1;
                $vi['centru'] = $this->user_centru_id;
                $vi['scanner'] = 'MODUL DECONT';
                $vi['curier'] = $row['agent_id'];
                $vi['data'] = $today;
                $vi['ruta'] = 0;
                $vi['borderou'] = $bo_sc;
                $vi['status'] = 1;
                $vi['user'] = $this->user_id;

                if(isset($row['operatiune']) && $row['operatiune'] == 1){
                    //operatiune colectare se da checkpoint DCC = 31
                    $vi['tip'] = 31;
                    $this->db->QueryInsert($this->tables['scanari_coduri'], $vi);
                    $expeditii[] = $row['expeditie'];
                }
                else if(isset($row['operatiune']) && $row['operatiune'] == 2){ 
                    //operatiune livrare se da checkpoint DCL = 32
                    $vi['tip'] = 32;
                    $this->db->QueryInsert($this->tables['scanari_coduri'], $vi);
                    //se da checkpoint COK = 5 numai daca nu are COK
                    $ckpts = array_map('intval', explode(',', $row['ckps']));
                    if(!is_array($ckpts)) $ckpts = [];
                    if(!in_array(5, $ckpts)){
                        $vi['tip'] = 5;
                        $this->db->QueryInsert($this->tables['scanari_coduri'], $vi);
                        $expeditii_cok[] = $row['expeditie'];
                    }
                    else {
                        $expeditii[] = $row['expeditie'];
                    }
                }			    
            }
            //dezanulare expeditii
            if(count($expeditii) > 0){
                $this->db->Query("UPDATE exp_prelucrate SET anulata = 0, deleted_by = 0, deleted_at = null where cod_expeditie in (".implode(',', $expeditii).")");
            }
            if(count($expeditii_cok) > 0){
                $this->db->Query("UPDATE exp_prelucrate SET anulata = 0, deleted_by = 0, deleted_at = null, last_ckp = 5, centru_last_ckp = {$this->user_centru_id}, data_last_ckp = NOW() where cod_expeditie in (".implode(',', $expeditii_cok).") and anulata = 0");
            }
        }
        return json_encode(['decont_id' => $decont_id]);
    }

    function printDecontAgent(){
        $decont_id = intval(Backend::sSanitize($_POST['decont_id'] ?? 0));
        if($decont_id == 0){
            echo 'Decont not found : unknown'; exit();
        }

        $vars = [];
        $vars['decont_id'] = $decont_id;

        //search agent for decont_id
        $decont_agent = $this->db->QFetchRowAssoc("
            select da.data, da.total, ag.nume_ag as agent_nume, ce.nume as centru_nume
            FROM decont_agent da
            LEFT JOIN agenti ag on ag.cod_ag = da.agent_id
            LEFT JOIN centre ce on ag.cod_centru = ce.id 
            where da.id = {$decont_id}");
        
        if(empty($decont_agent)){ echo 'Decont not found : '.$decont_id.' for user : '.$this->user_id; exit(); }

        $vars['agent_nume'] = $decont_agent['agent_nume'];
        $vars['show_data'] = $decont_agent['data'];
        $vars['centru_nume'] = $decont_agent['centru_nume'];
        $vars['totaluri'] = [];
        $vars['totaluri']['total'] = $decont_agent['total'];
        $vars['totaluri']['transport'] = 0;
        $vars['totaluri']['ramburs'] = 0;
        $vars['totaluri']['chitante'] = 0;
        $vars['totaluri']['cheltuieli'] = 0;
        $vars['expeditii'] = [];
        $vars['chitante'] = [];
        $vars['cheltuieli'] = [];

        //search total
        $t_ramburs = $this->db->QFetchRowAssoc("
            select sum(ramburs) as total_ramburs 
            FROM decont_rbs
            where anulata = 0 and decont_id = {$decont_id} and transaction_id = 0");

        if(!empty($t_ramburs)){
            $vars['totaluri']['ramburs'] = $t_ramburs['total_ramburs'];
        }

        $t_transport = $this->db->QFetchRowAssoc("
            select sum(suma) as total_transport
            FROM decont_facturi
            where anulata = 0 and decont_id = {$decont_id} and transaction_id = 0");

        if(!empty($t_transport)){
            $vars['totaluri']['transport'] = $t_transport['total_transport'];
        }

        $t_chitante = $this->db->QFetchRowAssoc("
            select sum(suma) as total_chitante
            FROM decont_chitante
            where anulata = 0 and decont_id = {$decont_id} and transaction_id = 0");
        
        if(!empty($t_chitante)){
            $vars['totaluri']['chitante'] = $t_chitante['total_chitante'];
        }
        
        $temp_expeditii_rbs = [];
        //search expeditii cu ramburs la livrare for decont_id
        $expeditii_rbs = $this->db->QFetchRowArray("
            select de.expeditie, de.transport, df.serie as factura, de.ramburs, dr.ch_ramburs as ch_ramburs
            FROM decont_expeditii de
            inner join decont_rbs dr on (de.ramburs_id = dr.id and dr.anulata = 0)
            LEFT JOIN decont_facturi df on (de.factura_id = df.id and df.anulata = 0)
            WHERE dr.decont_id = {$decont_id} and dr.transaction_id = 0 and de.operatiune = 2");
        if(!empty($expeditii_rbs)){
            foreach ($expeditii_rbs as $key => $row){
                $temp_expeditii_rbs[] = $row['expeditie'];
                $vars['expeditii'][] = ['expeditie'=>$row['expeditie'],'transport'=>$row['transport'],'factura'=>(empty($row['factura'])?'':$row['factura']),'ramburs'=>$row['ramburs'], 'chitanta'=>$row['ch_ramburs']];
            }
        }

        //search expeditii cu factura la livrare sau la colectare for decont_id
        $expeditii_ff = $this->db->QFetchRowArray("
            select de.expeditie, de.transport, df.serie as factura, de.ramburs, dr.ch_ramburs as ch_ramburs
            FROM decont_expeditii de
            inner join decont_facturi df on (de.factura_id = df.id and df.anulata = 0)
            LEFT JOIN decont_rbs dr on (de.ramburs_id = dr.id and dr.anulata = 0)
            WHERE df.decont_id = {$decont_id} and df.transaction_id = 0 and de.operatiune in (1,2)");
        if(!empty($expeditii_ff)){
            foreach ($expeditii_ff as $key => $row){
                if(!in_array($row['expeditie'], $temp_expeditii_rbs))
                    $vars['expeditii'][] = ['expeditie'=>$row['expeditie'],'transport'=>$row['transport'],'factura'=>(empty($row['factura'])?'':$row['factura']),'ramburs'=>$row['ramburs'], 'chitanta'=>$row['ch_ramburs']];
            }
        }

        //search chitante client CTR
        $chitante_ctr = $this->db->QFetchRowArray("
            select descriere, ch_bon as chitanta, suma
            FROM decont_chitante use index (decont_id)
            where decont_id = {$decont_id} and transaction_id = 0 and anulata = 0");
        if(!empty($chitante_ctr)){
            foreach ($chitante_ctr as $key => $row){
                $vars['chitante'][] = ['descriere'=>$row['descriere'],'chitanta'=>$row['chitanta'],'suma'=>$row['suma']];
            }
        }

        //search cheltuieli for decont_id
        $cheltuieli = $this->db->QFetchRowArray("
            select tip, descriere, suma
            FROM decont_cheltuieli use index (decont_id)
            where decont_id = {$decont_id} and anulata = 0");
        if(!empty($cheltuieli)){
            foreach ($cheltuieli as $key => $row){
                $vars['cheltuieli'][] = [
                    'tip'=>$this->tip_cheltuieli_rbs_fa_ch[$row['tip']] ?? ($row['tip'] == 5 ? 'Descarcare casa' : ($row['tip'] == 4 ? 'Incarcare casa' : 'Cheltuiala')),
                    'desc'=>$row['descriere'],
                    'suma'=>($row['tip'] == self::RETUR_LIPSA_BANI ? abs($row['suma']) : -abs($row['suma']))
                ];
                if($row['tip'] == self::RETUR_LIPSA_BANI){
                    $vars['totaluri']['cheltuieli'] += abs($row['suma']);
                }
                else {
                    $vars['totaluri']['cheltuieli'] -= abs($row['suma']);
                }
            }
        }
        
        require_once 'PvAgentPdf.php';
        $filename = 'PV-'.(new DateTime($vars['show_data']))->format('d-m-Y').'-'.$vars['agent_nume'].'.pdf';
        $vars['y'] = 85;
        $pdf = new PvAgentPdf($vars);	
		// add a page
        $pdf->AddPage();
        if(count($vars['expeditii']) > 0){
            $pdf->makeThExpeditii();
            foreach($vars['expeditii'] as $exp)
                $pdf->makeTrExpeditii($exp);
        }
        if(count($vars['chitante']) > 0){
            $pdf->makeThChitanteFiscale();
            foreach($vars['chitante'] as $chitanta)
                $pdf->makeTrChitanteFiscale($chitanta);
        }
        if(count($vars['cheltuieli']) > 0){
            $pdf->makeThCheltuieli();
            foreach($vars['cheltuieli'] as $cheltuiala)
                $pdf->makeTrCheltuieli($cheltuiala);
        }
        $pdf->makeTotal();
        $pdf->lastPage();
        $pdf->Output($filename,'D');
        exit;
    }

    function getDecontDashboard(){
        $agenti_de_decontat = [];
        $agenti_de_decontat['nr_agenti'] = 0;
        $agenti_de_decontat['nr_transport'] = 0;
        $agenti_de_decontat['nr_ramburs'] = 0;
        $agenti_de_decontat['nr_chitante'] = 0;
        $agenti_de_decontat['total_transport'] = 0.00;
        $agenti_de_decontat['total_ramburs'] = 0.00;
        $agenti_de_decontat['total_chitante'] = 0.00;
        $agenti_de_decontat['total_de_decontat'] = 0.00;

        $agenti_decontati = [];
        $agenti_decontati['nr_agenti'] = 0;
        $agenti_decontati['nr_transport'] = 0;
        $agenti_decontati['nr_ramburs'] = 0;
        $agenti_decontati['nr_chitante'] = 0;
        $agenti_decontati['nr_cheltuieli'] = 0;
        $agenti_decontati['total_transport'] = 0.00;
        $agenti_decontati['total_ramburs'] = 0.00;
        $agenti_decontati['total_chitante'] = 0.00;
        $agenti_decontati['total_cheltuieli'] = 0.00;
        $agenti_decontati['total_decontat'] = 0.00;

        $query_df_de_decontat = "
            select df.agent_id as agent_id,
            count(df.id) as nr_transport, sum(df.suma) as transport, 
            0 as nr_ramburs, 0 as ramburs, 
            0 as nr_chitante, 0 as chitante
            FROM decont_facturi df
            inner join agenti ag on ag.cod_ag = df.agent_id
            where df.decont_id = 0 and df.anulata = 0 and df.transaction_id = 0
            and ag.cod_centru = {$this->user_centru_id}
            group by df.agent_id
        ";
        $query_dr_de_decontat = "
            select dr.agent_id as agent_id, 
            0 as nr_transport, 0 as transport, 
            count(dr.id) as nr_ramburs, sum(dr.ramburs) as ramburs, 
            0 as nr_chitante, 0 as chitante
            FROM decont_rbs dr
            inner join agenti ag on ag.cod_ag = dr.agent_id
            where dr.decont_id = 0 and dr.anulata = 0 and dr.transaction_id = 0
            and ag.cod_centru = {$this->user_centru_id}
            group by dr.agent_id
        ";
        $query_cf_de_decontat = "
            select cf.agent_id as agent_id,
            0 as nr_transport, 0 as transport, 
            0 as nr_ramburs, 0 as ramburs, 
            count(cf.id) as nr_chitante, sum(cf.suma) as chitante 
            FROM decont_chitante cf
            inner join agenti ag on ag.cod_ag = cf.agent_id
            where cf.decont_id = 0 and cf.anulata = 0 and cf.transaction_id = 0
            and ag.cod_centru = {$this->user_centru_id}
            group by cf.agent_id
        ";

        $query_de_decontat = "
            select count(distinct ndc.agent_id) as nr_agenti, 
            sum(COALESCE(ndc.nr_transport, 0)) as nr_transport, sum(COALESCE(ndc.nr_ramburs, 0)) as nr_ramburs,
            sum(COALESCE(ndc.nr_chitante, 0)) as nr_chitante,
            sum(COALESCE(ndc.transport, 0)) as transport, sum(COALESCE(ndc.ramburs, 0)) as ramburs, 
            sum(COALESCE(ndc.chitante, 0)) as chitante
            from (
                ({$query_df_de_decontat})
                UNION
                ({$query_dr_de_decontat})
                UNION
                ({$query_cf_de_decontat})
            ) as ndc
        ";

        $query_de_decontat_sql = $this->db->QFetchArray($query_de_decontat);
        if(!empty($query_de_decontat_sql)) {
            $agenti_de_decontat['nr_transport'] = $query_de_decontat_sql['nr_transport'];
            $agenti_de_decontat['nr_ramburs'] = $query_de_decontat_sql['nr_ramburs'];
            $agenti_de_decontat['nr_chitante'] = $query_de_decontat_sql['nr_chitante'];
            $agenti_de_decontat['total_transport'] = $query_de_decontat_sql['transport'];
            $agenti_de_decontat['total_ramburs'] = $query_de_decontat_sql['ramburs'];
            $agenti_de_decontat['total_chitante'] = $query_de_decontat_sql['chitante'];
            $agenti_de_decontat['nr_agenti'] = $query_de_decontat_sql['nr_agenti'];
        }
        //total decontat = total transport + total ramburs + total chitante + total cheltuieli
        $agenti_de_decontat['total_de_decontat'] = $agenti_de_decontat['total_transport'] + $agenti_de_decontat['total_ramburs'] + $agenti_de_decontat['total_chitante'];

        $query_df_decontat = "
            select df.agent_id as agent_id,
            count(df.id) as nr_transport, sum(df.suma) as transport, 
            0 as nr_ramburs, 0 as ramburs, 
            0 as nr_chitante, 0 as chitante
            FROM decont_facturi df
            inner join decont_agent da on da.id = df.decont_id
            where df.anulata = 0 and df.transaction_id = 0 and DATE(da.data) = '{$this->data_start}'
            and da.centru_id = {$this->user_centru_id}
            group by df.agent_id
        ";
        $query_dr_decontat = "
            select dr.agent_id as agent_id, 
            0 as nr_transport, 0 as transport, 
            count(dr.id) as nr_ramburs, sum(dr.ramburs) as ramburs, 
            0 as nr_chitante, 0 as chitante
            FROM decont_rbs dr
            inner join decont_agent da on da.id = dr.decont_id
            where dr.anulata = 0 and dr.transaction_id = 0 and DATE(da.data) = '{$this->data_start}'
            and da.centru_id = {$this->user_centru_id}
            group by dr.agent_id
        ";
        $query_cf_decontat = "
            select cf.agent_id as agent_id,
            0 as nr_transport, 0 as transport, 
            0 as nr_ramburs, 0 as ramburs, 
            count(cf.id) as nr_chitante, sum(cf.suma) as chitante
            FROM decont_chitante cf
            inner join decont_agent da on da.id = cf.decont_id
            where cf.anulata = 0 and cf.transaction_id = 0 and DATE(da.data) = '{$this->data_start}'
            and da.centru_id = {$this->user_centru_id}
            group by cf.agent_id
        ";

        $query_decontat = "
            select count(distinct ndc.agent_id) as nr_agenti, 
            sum(COALESCE(ndc.nr_transport, 0)) as nr_transport, sum(COALESCE(ndc.nr_ramburs, 0)) as nr_ramburs,
            sum(COALESCE(ndc.nr_chitante, 0)) as nr_chitante,
            sum(COALESCE(ndc.transport, 0)) as transport, sum(COALESCE(ndc.ramburs, 0)) as ramburs, 
            sum(COALESCE(ndc.chitante, 0)) as chitante
            from (
                ({$query_df_decontat})
                UNION
                ({$query_dr_decontat})
                UNION
                ({$query_cf_decontat})
            ) as ndc
        ";

        $query_decontat_sql = $this->db->QFetchArray($query_decontat);
        if(!empty($query_decontat_sql)) {
            $agenti_decontati['nr_transport'] = $query_decontat_sql['nr_transport'] ?? 0;
            $agenti_decontati['nr_ramburs'] = $query_decontat_sql['nr_ramburs'] ?? 0;
            $agenti_decontati['nr_chitante'] = $query_decontat_sql['nr_chitante'] ?? 0;
            $agenti_decontati['total_transport'] = $query_decontat_sql['transport'] ?? 0.00;
            $agenti_decontati['total_ramburs'] = $query_decontat_sql['ramburs'] ?? 0.00;
            $agenti_decontati['total_chitante'] = $query_decontat_sql['chitante'] ?? 0.00;
            $agenti_decontati['nr_agenti'] = $query_decontat_sql['nr_agenti'] ?? 0;
        }
        //total decontat = total transport + total ramburs + total chitante + total cheltuieli
        $agenti_decontati['total_decontat'] = $agenti_decontati['total_transport'] + $agenti_decontati['total_ramburs'] + $agenti_decontati['total_chitante'];

        //cheltuieli
        $query_cheltuieli = "select sum(COALESCE(dicd.suma, 0)) as total, dicd.tip
            FROM decont_cheltuieli as dicd
            join decont_agent da on da.id = dicd.decont_id
            where DATE(da.data) = '{$this->data_start}'
            and dicd.anulata = 0
            and da.centru_id = {$this->user_centru_id}
            group by dicd.tip";
        $cheltuieli_sql = $this->db->QFetchRowArray($query_cheltuieli);
        if(!empty($cheltuieli_sql)){
            foreach ($cheltuieli_sql as $row){
                if(empty($row['tip'])) continue;
                if($row['tip'] == self::RETUR_LIPSA_BANI){
                    $agenti_decontati['total_cheltuieli'] += $row['total'];
                    $agenti_decontati['nr_cheltuieli']++;
                }
                else {
                    $agenti_decontati['total_cheltuieli'] -= $row['total'];
                    $agenti_decontati['nr_cheltuieli']++;
                }
            }
        }
    
        $total_casa = $agenti_decontati['total_decontat'] + $agenti_decontati['total_cheltuieli'];

        $agenti_de_decontat['total_de_decontat'] = number_format($agenti_de_decontat['total_de_decontat'],2);
        $agenti_de_decontat['total_transport'] = number_format($agenti_de_decontat['total_transport'],2);
        $agenti_de_decontat['total_ramburs'] = number_format($agenti_de_decontat['total_ramburs'],2);
        $agenti_de_decontat['total_chitante'] = number_format($agenti_de_decontat['total_chitante'],2);
        $agenti_de_decontat['nr_chitante'] = intval($agenti_de_decontat['nr_chitante']);

        $agenti_decontati['total_decontat'] = number_format($agenti_decontati['total_decontat'],2);
        $agenti_decontati['total_transport'] = number_format($agenti_decontati['total_transport'],2);
        $agenti_decontati['total_ramburs'] = number_format($agenti_decontati['total_ramburs'],2);
        $agenti_decontati['total_chitante'] = number_format($agenti_decontati['total_chitante'],2);
        $agenti_decontati['nr_chitante'] = intval($agenti_decontati['nr_chitante']);

        $agenti_decontati['total_cheltuieli'] = number_format($agenti_decontati['total_cheltuieli'],2);
        $agenti_decontati['total_casa'] = number_format($total_casa, 2);

        // error_log(json_encode($agenti_decontati));

        return json_encode(
            array(
                'agenti_de_decontat' => $agenti_de_decontat,
                'agenti_decontati' => $agenti_decontati,
                'success' => 1
            )
        );
    }

    function exportDecontXLS($data_start, $data_stop = "", $centre_multiple = false){
        
        require_once 'scanare.php';

        $data_start = $data_decont = $this->TransformDate($data_start);
        
        $filtru_centru = "AND da.centru_id = ".$this->user_centru_id;

        if($this->exporta_centre_multiple && ($centre_multiple))
            $filtru_centru = '';

        //error_log($filtru_centru);
        
        $sql = "SELECT de.agent_id, ag.nume_ag as curier, de.motiv_transport, sum(dr.ramburs) as ramburs,
            IF(dr.id > 0, group_concat(dr.ch_ramburs), dr.ch_ramburs) as ch_ramburs,
            IF(de.id > 0, group_concat(de.expeditie), de.expeditie) as expeditie,
            IF(df.id > 0, df.suma, de.transport) as transport,
            IF(df.id > 0, df.id , de.expeditie) as grupare,
            cle.nume as expeditor, cld.nume as destinatar, lce.nume_lc as expeditor_localitate, lcd.nume_lc as destinatar_localitate,
            IF(cle.zona_id > 0 and clec.id > 0, clec.label, cee.label) as expeditor_centru_cod, 
            IF(cld.zona_id > 0 and cldc.id > 0, cldc.label, ced.label) as destinatar_centru_cod,
            ep.plicuri,
            ep.colete,
            ep.paleti,
            ep.tip_exp,
            ep.tip_obj,
            ep.piese,
            CASE 
                WHEN ep.tip_plata=".self::RBS_TIP_PLATA_CASH." THEN 'cash'
                WHEN ep.tip_plata=".self::RBS_TIP_PLATA_CONT." THEN 'cont'
                WHEN ep.tip_plata=".self::RBS_TIP_PLATA_CEC." THEN 'cec'
                WHEN ep.tip_plata=".self::RBS_TIP_PLATA_BO." THEN 'bo'
                ELSE 'unknown'
            END as tip_plata_rbs,
            de.operatiune,
            sum(IF (ep.mod_plata = 0 AND ep.platitor_id = ep.destinatar_id, ep.valoare_totala_expeditie + ep.tva , 0 )) as cash,
            sum(IF (ep.tip_plata IN (".self::RBS_TIP_PLATA_CASH.",".self::RBS_TIP_PLATA_CONT.") AND de.operatiune <> 1, ep.ramburs, 0)) as ramburs_de_incasat,
            df.serie as factura,
            df.decont_id as decont_id,
            de.data,
            da.data as data_decont,
            ce.label as centru_label
            FROM decont_expeditii de 
            INNER JOIN decont_agent da on de.decont_id = da.id
            LEFT JOIN agenti ag ON ag.cod_ag = de.agent_id 
            LEFT JOIN centre ce ON ce.id = da.centru_id
            LEFT JOIN {$this->tables['exp_prelucrate']} as ep ON (ep.expeditie = de.expeditie and ep.anulata = 0)
            LEFT JOIN clienti cle on cle.cod_cl = ep.expeditor_id
            LEFT JOIN clienti cld on cld.cod_cl = ep.destinatar_id
            LEFT JOIN zones clez ON clez.id = cle.zona_id
        	LEFT JOIN centre clec on clec.id = clez.centru_id
			LEFT JOIN zones cldz ON cldz.id = cld.zona_id
        	LEFT JOIN centre cldc on cldc.id = cldz.centru_id
            LEFT JOIN localitati lce ON lce.cod_lc = cle.cod_lc
            LEFT JOIN localitati lcd ON lcd.cod_lc = cld.cod_lc
            LEFT JOIN centre cee ON cee.id = lce.cod_centru
            LEFT JOIN centre ced ON ced.id = lcd.cod_centru
            LEFT JOIN decont_facturi df ON (df.id = de.factura_id and df.anulata = 0 and df.transaction_id = 0)
            LEFT JOIN decont_rbs dr ON (dr.id = de.ramburs_id and dr.anulata = 0 and dr.transaction_id = 0)
            WHERE da.data BETWEEN '{$data_start} 00:00:00' AND  '{$data_decont} 23:59:59' 
            {$filtru_centru}
            AND (dr.decont_id > 0 or df.decont_id > 0)
            GROUP BY grupare, ag.cod_ag order by ag.nume_ag";
        //error_log($sql);
        //

        $decont_expeditii_sql = $this->db->QFetchRowArray($sql);

        $incasari = [];
        $centralizator = [];
        if(!empty($decont_expeditii_sql)){
            foreach ($decont_expeditii_sql as $row){
                if(empty($row['operatiune'])){
                    $row['operatiune'] = 0;
                }
                switch ($row['operatiune']):
                    case 1:
                        $row['operatiune'] = 'colectare';
                        break;
                    case 2:
                        $row['operatiune'] = 'livrare';
                        break;
                    default:
                        $row['operatiune'] = '';
                endswitch;

                //if(!empty($incasari[$row['expeditie']]))
                    //$row['expeditie'] = $row['expeditie']."-".$row['curier'];

                $incasari[$row['expeditie']] = array(
                    'curier' => $row['curier'],
                    'expeditie' => $row['expeditie'],
                    'cash' => $row['cash'],
                    'cash_incasat' =>$row['transport'],
                    'cash_motiv' => $row['motiv_transport'],
                    'ramburs' => $row['ramburs_de_incasat'],
                    'rbs_incasat' => $row['ramburs'],
                    'agent_id' => $row['agent_id'],
                    'expeditor' => $row['expeditor'],
                    'expeditor_localitate' => $row['expeditor_localitate'],
                    'expeditor_centru_cod' => $row['expeditor_centru_cod'],
                    'destinatar' => $row['destinatar'],
                    'destinatar_localitate' => $row['destinatar_localitate'],
                    'destinatar_centru_cod' => $row['destinatar_centru_cod'],
                    'plicuri' => $row['plicuri'],
                    'colete' => $row['colete'],
                    'paleti' => $row['paleti'],
                    'tip_exp' => (ExpeditieDto::TIP_EXP[$row['tip_exp']] ?? "unknown"),
                    'tip_obj' => $row['tip_obj'],
                    'piese' => $row['piese'],
                    'decontat'  => 1,
                    'factura' => $row['factura'],
                    'ch_ramburs' => $row['ch_ramburs'],
                    'data' => $row['data'],
                    'operatiune' => $row['operatiune'],
                    'centru_label' => $row['centru_label'],
                    'tip_plata_rbs' => (($row['ramburs_de_incasat'] > 0) ? $row['tip_plata_rbs']:''),
                    'data_decont' => $row['data_decont']
                );

                if(!empty($row['factura']) && !empty($row['decont_id'])){
                    $centralizator[$row['factura']] = array (
                        'curier' => $row['curier'],
                        'factura' => $row['factura'],
                        'suma' =>$row['transport'],
                        'expeditie' => $row['expeditie'],
                        'operatiune' => $row['operatiune'],
                        'centru_label' => $row['centru_label'],
                        'data' => $row['data']
                    );
                }
            }
        }


        $_REQUEST['centru'] = $this->user_centru_id;
        $_REQUEST['data_final'] = date('d.m.Y',strtotime($data_decont));
        $_REQUEST['categorie'] = 1;

        $fields_to_export = array(
            'expeditie' => 'Nr NT',
            'curier' => 'Agent',
            'tip_obj' => 'Tip',
            'piese' => 'Piese',
            'destinatar' => 'Destinatar',
            'expeditor' => 'Expeditor',
            'destinatar_centru_cod' => 'Centru Livrare',
            'destinatar_localitate' => 'Localitate Livrare',
            'expeditor_centru_cod' => 'Centru Colectare',
            'expeditor_localitate' => 'Localitate Colectare',
            'incasat' => 'Incasat',
            'ramburs' => 'Ramburs',
            'cash' => 'Cash',
            'rbs_incasat' => 'RBS Incasat',
            'cash_incasat' => 'Cash Incasat',
            'cash_motiv' => 'Cash Motiv',
            'decontat' => 'Decontat',
            'factura'  => 'Factura',
            'ch_ramburs'  => 'CH Ramburs',
            'data'  => 'Data',
            'operatiune'  => 'Operatiune',
            'centru_label'  => 'Centru',
            'tip_plata_rbs'  => 'Tip RBS',
            'data_decont' => 'Data decont'
        );


        $societate = 'Dragon Star Curier';
        $document = 'Lista Expeditii';

        $spreadsheet = new Spreadsheet();    
	    $spreadsheet->getProperties()->setCreator($societate)
            ->setLastModifiedBy($societate)
            ->setTitle($document)
            ->setSubject($document)
            ->setDescription($document)
            ->setKeywords($document)
            ->setCategory($document);
        $spreadsheet->getDefaultStyle()->getFont()->setName('Arial');
        $spreadsheet->getDefaultStyle()->getFont()->setSize(12);


        $CheltuieliWorkSheet = $spreadsheet->getActiveSheet();
        $CheltuieliWorkSheet->setTitle("Cheltuieli");

        $ChitanteClientCTRWorkSheet = $spreadsheet->createSheet(1);
        $ChitanteClientCTRWorkSheet->setTitle("Chitante client CTR");

        $DecontWorkSheet = $spreadsheet->createSheet(2);
        $DecontWorkSheet->setTitle("Decont");

        $CentralizatorWorkSheet = $spreadsheet->createSheet(3);
        $CentralizatorWorkSheet->setTitle("Facturi");

        $CentralizatorRbsWorkSheet = $spreadsheet->createSheet(4);
        $CentralizatorRbsWorkSheet->setTitle("Rambursuri");

        $cheltuieli_to_export = array(
            'curier' => 'Agent',
            'descriere' => 'Descriere',
            'tip' => 'Tip',
            'suma' => 'Suma',
            'centru_nume'  => 'Centru',
            'data' => 'Data',
            'data_decont' => 'Data decont'
        );

        $rangeArr = range('A', 'Z') ;

        $ic = 0;
        foreach ($cheltuieli_to_export as $key=>$label){
            $CheltuieliWorkSheet->setCellValue($rangeArr[$ic].'1',$label);
            $CheltuieliWorkSheet->getStyle($rangeArr[$ic].'1')->applyFromArray(
                [
                    'fill' => [
                        'fillType' => Fill::FILL_SOLID,
                        'startColor' => ['argb' => Color::COLOR_BLACK]
                    ],
                    'font'  => [
                        'color' => ['argb' => Color::COLOR_WHITE]
                    ]
                ]
            );
            $ic++;
        }

        $chitanteClientCTR_to_export = array(
            'curier' => 'Agent',
            'ch_bon' => 'Chitanta',
            'descriere' => 'Descriere',
            'suma' => 'Suma',
            'centru_nume'  => 'Centru',
            'dataInc' => 'Data',
            'data_decont' => 'Data decont'
        );

        $rangeArr = range('A', 'Z') ;

        $ic = 0;
        foreach ($chitanteClientCTR_to_export as $key=>$label){
            $ChitanteClientCTRWorkSheet->setCellValue($rangeArr[$ic].'1',$label);
            $ChitanteClientCTRWorkSheet->getStyle($rangeArr[$ic].'1')->applyFromArray(
                [
                    'fill' => [
                        'fillType' => Fill::FILL_SOLID,
                        'startColor' => ['argb' => Color::COLOR_BLACK]
                    ],
                    'font'  => [
                        'color' => ['argb' => Color::COLOR_WHITE]
                    ]
                ]
            );
            $ic++;
        }

        $randc = 2;
        $ic = 0;
        //cheltuieli
        $sql_cheltuieli = "SELECT dicd.*, ag.nume_ag as curier, ce.nume as centru_nume, da.data as data_decont
            FROM  decont_cheltuieli dicd
            INNER JOIN decont_agent da on dicd.decont_id = da.id
            LEFT JOIN agenti ag ON ag.cod_ag = dicd.agent_id 
            LEFT JOIN centre ce ON ce.id = da.centru_id
            WHERE da.data BETWEEN '{$data_start} 00:00:00' AND  '{$data_decont} 23:59:59' 
            and dicd.anulata = 0
            ".$filtru_centru;
        $decont_cheltuieli_sql = $this->db->QFetchRowArray($sql_cheltuieli);

        if(!empty($decont_cheltuieli_sql)){
            foreach ($decont_cheltuieli_sql as $row) {
                $tip = $row['tip'];
                $row['tip'] = $this->tip_cheltuieli_rbs_fa_ch[$row['tip'] ?? 1] ?? ($tip == 5 ? 'Descarcare casa' : ($tip == 4 ? 'Incarcare casa' : 'Cheltuiala'));
                if($tip != self::RETUR_LIPSA_BANI){
                    $row['suma'] = abs($row['suma']) * -1 ;
                }
                
                $ic = 0;
                foreach ($cheltuieli_to_export as $key=>$label){
                    if($key == 'suma')
                        $CheltuieliWorkSheet->setCellValueExplicit($rangeArr[$ic++].$randc, $row[$key], \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_NUMERIC);
                    else 
                        $CheltuieliWorkSheet->setCellValueExplicit($rangeArr[$ic++].$randc, $row[$key], \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
                }
                $randc++;
            }
        }

        $randc++;
        $CheltuieliWorkSheet->getStyle('F'.$randc)->getFont()->setBold(true);
        $CheltuieliWorkSheet->setCellValue('F'.$randc,"=SUM(F2:F".($randc - 1).")");
        $CheltuieliWorkSheet->getColumnDimension("A")->setAutoSize(true);
        $CheltuieliWorkSheet->getColumnDimension("D")->setAutoSize(true);
        $CheltuieliWorkSheet->getColumnDimension("F")->setAutoSize(true);

        //chitante
        $randc = 2;
        $ic = 0;
        $sql_cf = "SELECT dfc.*, ag.nume_ag as curier, ce.nume as centru_nume, da.data as data_decont
            FROM  decont_chitante dfc
            INNER JOIN decont_agent da on dfc.decont_id = da.id
            LEFT JOIN agenti ag ON ag.cod_ag = dfc.agent_id 
            LEFT JOIN centre ce ON ce.id = da.centru_id
            WHERE da.data BETWEEN '{$data_start} 00:00:00' AND  '{$data_decont} 23:59:59' 
            and dfc.anulata = 0 and dfc.transaction_id = 0
            ".$filtru_centru;
        $decont_cf_sql = $this->db->QFetchRowArray($sql_cf);

        if(!empty($decont_cf_sql)){
            foreach ($decont_cf_sql as $row) {
                $ic = 0;
                foreach ($chitanteClientCTR_to_export as $key=>$label){
                    if($key == 'suma')
                        $ChitanteClientCTRWorkSheet->setCellValueExplicit($rangeArr[$ic++].$randc, $row[$key], \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_NUMERIC);
                    else
                        $ChitanteClientCTRWorkSheet->setCellValue($rangeArr[$ic++].$randc, $row[$key]);
                }
                $randc++;
            }
        }

        $randc++;
        $ChitanteClientCTRWorkSheet->getStyle('D'.$randc)->getFont()->setBold(true);
        $ChitanteClientCTRWorkSheet->setCellValue('D'.$randc,"=SUM(D2:D".($randc - 1).")");
        $ChitanteClientCTRWorkSheet->getColumnDimension("B")->setAutoSize(true);
        $ChitanteClientCTRWorkSheet->getColumnDimension("C")->setAutoSize(true);
        $ChitanteClientCTRWorkSheet->getColumnDimension("D")->setAutoSize(true);

        $i = 0;
        foreach ($fields_to_export as $key=>$label){
            $DecontWorkSheet->setCellValue($rangeArr[$i].'1',$label);
            $DecontWorkSheet->getStyle($rangeArr[$i].'1')->applyFromArray(
                [
                    'fill' => [
                        'fillType' => Fill::FILL_SOLID,
                        'startColor' => ['argb' => Color::COLOR_BLACK]
                    ],
                    'font'  => [
                        'color' => ['argb' => Color::COLOR_WHITE]
                    ]
                ]
            );
            $i++;
        }

        $rand=2;
        $modulScanare = new ModulScanare($this->config, 0, $this->db);
        $_REQUEST['centru'] = $this->user_centru_id;
        $_REQUEST['data_final'] = date('d.m.Y',strtotime($data_decont));
        $_REQUEST['categorie'] = 1;

        $expeditii = $modulScanare->Expeditii_DiferenteAgent();

        $incasari_exp = array_keys($incasari);

        $introduse = [];
        foreach ($expeditii as $expeditie){
            if(in_array($expeditie['expeditie'], $incasari_exp )){
                $incasari[$expeditie['expeditie']]['incasat'] = intval($expeditie['incasat']);
            } else {
                if(!in_array($expeditie['expeditie'], $introduse)) {
                    $incasari[$expeditie['expeditie']] = $expeditie;
                    $incasari[$expeditie['expeditie']]['cash_motiv'] = 0;
                    $incasari[$expeditie['expeditie']]['cash_incasat'] = 0;
                    $incasari[$expeditie['expeditie']]['rbs_incasat'] = 0;
                    $introduse[] = $expeditie['expeditie'];
                } else {
                    $incasari[$expeditie['expeditie']] = $expeditie;
                }
            }
        }

        if (!empty($incasari)) {
            foreach ($incasari as $exp => $row) {
                $incasare_data = (!empty($incasari[$row['expeditie']]))?$incasari[$row['expeditie']]:[];
                $row['cash_motiv'] = (!empty($row['cash_motiv'])) ? 'Client cu contract' : '';
                if(empty($row['operatiune'])){
                    $row['operatiune'] = 0;
                }

                $row['piese'] = in_array($row['tip_obj'], [1,3]) ? 1 : $row['piese'];
                $row['tip_obj'] = ExpeditieDto::TIP_OBJ[$row['tip_obj']] ?? 'Necunoscut'; 

                $rangeArr = range('A', 'Z') ;
                $i = 0;

                $decontat = false;
                if(in_array($row['expeditie'], $incasari_exp )){
                    $decontat = true;
                }

                foreach ($fields_to_export as $key=>$label){

                    if($decontat){
                        if(!empty($incasare_data) &&  $key == 'curier'){
                            $DecontWorkSheet->getStyle($rangeArr[$i].$rand)->applyFromArray(
                                [
                                    'fill' => [
                                        'fillType' => Fill::FILL_SOLID,
                                        'startColor' => ['argb' => Color::COLOR_YELLOW]
                                    ]
                                ]
                            );
                        }

                        if(!empty($incasare_data) &&  $key == 'cash_incasat'){
                            $DecontWorkSheet->getStyle($rangeArr[$i].$rand)->applyFromArray(
                                [
                                    'fill' => [
                                        'fillType' => Fill::FILL_SOLID,
                                        'startColor' => ['argb' => Color::COLOR_YELLOW]
                                    ]
                                ]
                            );
                        }

                        if(!empty($incasare_data) &&  $key == 'rbs_incasat'){
                            $DecontWorkSheet->getStyle($rangeArr[$i].$rand)->applyFromArray(
                                [
                                    'fill' => [
                                        'fillType' => Fill::FILL_SOLID,
                                        'startColor' => ['argb' => Color::COLOR_YELLOW]
                                    ]
                                ]
                            );
                        }
                    }

                    if(in_array($key, array('incasat','factura','ch_ramburs','data','centru_label','tip_plata_rbs','data_decont') ) && empty($row[$key]))
                        $row[$key] = "";

                    if($key == 'decontat')
                        $row[$key] = ($decontat)?1:0;

                    $DecontWorkSheet->setCellValue($rangeArr[$i++].$rand, $row[$key]);
                }
                $rand++;
            }

            $rand++;

            $show_total = array('L','M','N','O','R');
            foreach ($show_total as $col){
                $DecontWorkSheet->getStyle($col.$rand)->getFont()->setBold(true);
                $DecontWorkSheet->setCellValue($col.$rand,"=SUM({$col}2:{$col}".($rand - 1).")");
            }

        }

        $autosize = array('B'); // range('A', 'Z') ;
        foreach ($autosize as $id)
            $DecontWorkSheet->getColumnDimension($id)->setAutoSize(true);

        //centralizator

        $centralizator_to_export = array(
            'curier' => 'Agent',
            'factura' => 'Factura',
            'cash_inc' => 'Cash Incasat',
            'cash' => 'Cash',
            'motive' => 'Cash Motive',
            'expeditii' => 'Expeditii',
            'operatiune' => 'Operatiune',
            'centru_nume'  => 'Centru',
            'data' => 'Data',
            'data_decont' => 'Data decont'
        );

        $rangeArr = range('A', 'Z') ;

        $ic = 0;
        if(!empty($centralizator_to_export)){
            foreach ($centralizator_to_export as $key=>$label){
                $CentralizatorWorkSheet->setCellValue($rangeArr[$ic].'1',$label);
                $CentralizatorWorkSheet->getStyle($rangeArr[$ic].'1')->applyFromArray(
                    [
                        'fill' => [
                            'fillType' => Fill::FILL_SOLID,
                            'startColor' => ['argb' => Color::COLOR_BLACK]
                        ],
                        'font'  => [
                            'color' => ['argb' => Color::COLOR_WHITE]
                        ]
                    ]
                );
                $ic++;
            }
        }

        $sql_c = "SELECT df.serie as factura, ag.nume_ag as curier, 
            IF(df.id > 0, df.suma, de.transport) as cash_inc,
            IF(df.id > 0, df.id , de.expeditie) as grupare,
            CASE df.operatiune WHEN 1 THEN 'colectare'  WHEN 2 THEN 'livrare' ELSE '' END as operatiune,
            group_concat(de.expeditie) as expeditii,
            group_concat(de.motiv_transport) as motive,
            ce.nume as centru_nume, df.data, da.data as data_decont,
            sum(ep.valoare_totala_expeditie + ep.tva) as cash
            FROM decont_facturi df
            INNER JOIN decont_agent da on df.decont_id = da.id
            LEFT JOIN agenti ag ON ag.cod_ag = df.agent_id 
            LEFT JOIN centre ce ON ce.id = da.centru_id
            INNER JOIN decont_expeditii de ON df.id = de.factura_id
            LEFT JOIN {$this->tables['exp_prelucrate']} as ep ON ep.expeditie = de.expeditie
            WHERE da.data BETWEEN '{$data_start} 00:00:00' AND  '{$data_decont} 23:59:59'
            and df.anulata = 0 and df.transaction_id = 0
            {$filtru_centru}
            GROUP BY grupare";

        $c_sql = $this->db->QFetchRowArray($sql_c);

        if (!empty($c_sql)) {
            $rand=2;
            foreach ($c_sql as $f => $row) {
                $rangeArr = range('A', 'Z') ;
                $i = 0;
                foreach ($centralizator_to_export as $key=>$label){
                    if($key == "motive" && !empty($row[$key])) {
                        $motive_arr_text = [];
                        $motive_arr_int = explode(",", $row[$key]);
                        if(is_array($motive_arr_int))
                            foreach($motive_arr_int as $motiv_int)
                                if($motiv_int > 0)
                                    $motive_arr_text[] = (!empty($motiv_int))?'Client cu contract':'';
                        $row[$key] = implode(",", $motive_arr_text);

                    }
                    if($key == 'cash' || $key == 'cash_inc')
                        $CentralizatorWorkSheet->setCellValueExplicit($rangeArr[$i++].$rand, $row[$key], \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_NUMERIC);
                    else
                        $CentralizatorWorkSheet->setCellValue($rangeArr[$i++].$rand, $row[$key]);
                }
                $rand++;
            }
            $rand++;

            $CentralizatorWorkSheet->getColumnDimension("B")->setAutoSize(true);
            $CentralizatorWorkSheet->getColumnDimension("C")->setAutoSize(true);
            $CentralizatorWorkSheet->getColumnDimension("D")->setAutoSize(true);
            $CentralizatorWorkSheet->getColumnDimension("E")->setAutoSize(true);
            $CentralizatorWorkSheet->getColumnDimension("F")->setAutoSize(true);
            $CentralizatorWorkSheet->getColumnDimension("G")->setAutoSize(true);
            $CentralizatorWorkSheet->getColumnDimension("H")->setAutoSize(true);
            $CentralizatorWorkSheet->getColumnDimension("I")->setAutoSize(true);
            $CentralizatorWorkSheet->getStyle('C'.$rand)->getFont()->setBold(true);
            $CentralizatorWorkSheet->setCellValue('C'.$rand,"=SUM(C2:C".($rand - 1).")");
            $CentralizatorWorkSheet->getStyle('D'.$rand)->getFont()->setBold(true);
            $CentralizatorWorkSheet->setCellValue('D'.$rand,"=SUM(D2:D".($rand - 1).")");

        }

        //centralizator RBS

        $centralizator_rbs_to_export = array(
            'curier' => 'Agent',
            'expeditie' => 'Expeditie',
            'ch_rbs' => 'Chitanta RBS',
            'suma_rbs' => 'RBS',
            'operatiune' => 'Operatiune',
            'centru_nume'  => 'Centru',
            'data' => 'Data',
            'data_decont' => 'Data decont'
        );

        $rangeArr = range('A', 'Z') ;

        $ic = 0;
        if(!empty($centralizator_rbs_to_export)){
            foreach ($centralizator_rbs_to_export as $key=>$label){
                $CentralizatorRbsWorkSheet->setCellValue($rangeArr[$ic].'1', $label);
                $CentralizatorRbsWorkSheet->getStyle($rangeArr[$ic].'1')->applyFromArray(
                    [
                        'fill' => [
                            'fillType' => Fill::FILL_SOLID,
                            'startColor' => ['argb' => Color::COLOR_BLACK]
                        ],
                        'font'  => [
                            'color' => ['argb' => Color::COLOR_WHITE]
                        ]
                    ]
                );
                $ic++;
            }
        }

        $sql_crbs = "SELECT de.expeditie, dr.ch_ramburs as ch_rbs, ag.nume_ag as curier, dr.ramburs as suma_rbs, 
            CASE de.operatiune WHEN 1 THEN 'colectare'  WHEN 2 THEN 'livrare' ELSE '' END as operatiune, de.data,
            ce.nume as centru_nume, da.data as data_decont
            FROM decont_rbs dr
            INNER JOIN  decont_expeditii de on de.ramburs_id = dr.id
            INNER JOIN decont_agent da on dr.decont_id = da.id
            LEFT JOIN agenti ag ON ag.cod_ag = dr.agent_id 
            LEFT JOIN centre ce ON ce.id = da.centru_id
            WHERE da.data BETWEEN '{$data_start} 00:00:00' AND  '{$data_decont} 23:59:59'
            and dr.anulata = 0 and dr.transaction_id = 0 and dr.ramburs > 0 {$filtru_centru}";

        $crbs_sql = $this->db->QFetchRowArray($sql_crbs);

        if (!empty($crbs_sql)) {
            $rand=2;
            foreach ($crbs_sql as $erbs => $row) {
                $rangeArr = range('A', 'Z') ;
                $i = 0;
                foreach ($centralizator_rbs_to_export as $key=>$label){
                    if($key == 'suma_rbs')
                        $CentralizatorRbsWorkSheet->setCellValueExplicit($rangeArr[$i++].$rand, $row[$key], \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_NUMERIC);
                    else
                        $CentralizatorRbsWorkSheet->setCellValue($rangeArr[$i++].$rand, $row[$key]);
                }
                $rand++;
            }
            $rand++;

            $CentralizatorRbsWorkSheet->getColumnDimension("A")->setAutoSize(true);
            $CentralizatorRbsWorkSheet->getColumnDimension("B")->setAutoSize(true);
            $CentralizatorRbsWorkSheet->getColumnDimension("C")->setAutoSize(true);
            $CentralizatorRbsWorkSheet->getColumnDimension("D")->setAutoSize(true);
            $CentralizatorRbsWorkSheet->getColumnDimension("E")->setAutoSize(true);
            $CentralizatorRbsWorkSheet->getStyle('D'.$rand)->getFont()->setBold(true);
            $CentralizatorRbsWorkSheet->setCellValue('D'.$rand,"=SUM(D2:D".($rand - 1).")");

        }

        $spreadsheet->setActiveSheetIndex(2);
        $filename = 'decont_'.$_REQUEST['data_final'].'.xlsx';
        $this->download_send_headers_xls($filename);
        $writer = new Xlsx($spreadsheet);
        $writer->save('php://output');
        die;
    }

    function JSON_Agenti_De_Decontat(){
        //total incasate pe android : transport + ramburs
        //total chitante client CTR
        
        $cond = "";
        $filter = "";
        $searchOn = $this->Strip($_REQUEST['_search']);
        if ($searchOn == 'true') {
            $searchstr = $this->Strip($_REQUEST['filters']);
            $cond .= $this->constructWhere($searchstr);

            if(strpos($cond, "AND  decontat  = '0'") !== false){
                $cond = str_replace("AND  decontat  = '0'","",$cond);
                $filter = " and total_decontat = 0 ";
            }
            if(strpos($cond, "AND  decontat  = '1'") !== false){
                $cond = str_replace("AND  decontat  = '1'","",$cond);
                $filter = " and total_decontat != 0 ";
            }
        }

        $responce = new StdClass();
        $agenti = $this->getAgentiForCentru($this->user_centru_id, $cond, $filter);

        foreach ($agenti as $key => $agent) {
            $responce->rows[$key]['id'] = $agent['cod_ag'];
            $decontIds = [];
            if(!empty($agent['decont_ids'])) {
                $arr_decontId = explode(',', $agent['decont_ids']);
                foreach($arr_decontId as $decontId) {
                    $decontId = intval($decontId);
                    if($decontId > 0) {
                        $decontIds[] = '<a href="javascript:;" onclick="printPVAgent('.$decontId.');" style="text-decoration:none; color:green">'.$decontId.'</a>';
                    }
                }
            }
            $decontIds = implode(',', $decontIds);
            $responce->rows[$key++]['cell'] = array($agent['nume_ag'], $agent['telefon'], number_format($agent['transport'], 2, '.', ''),number_format($agent['ramburs'],2, '.', ''), number_format($agent['chitante'],2, '.', ''), ($agent['total_decontat'] > 0 ? 1 : 0), $decontIds, number_format($agent['total_decontat'],2, '.', ''));
        }

        $responce->page = 1;
        $responce->total = 1;
        $responce->records = $key ?? 0;
        return json_encode($responce);
    }

    function JSON_Agenti_De_Decontat_Row(){
        //total scanate iesire
        $agentId = intval($_REQUEST['id'] ?? 0);

        $responce = new StdClass();
        $responce->page = 1;
        $responce->total = 0;
        $responce->records = 0;

        $cond = "1=1";

        if($agentId > 0){ //show subgrid
            $cond .= " AND scb.curier = {$agentId}";
        } else {
            return json_encode($responce);
        }

        $searchOn = $this->Strip($_REQUEST['_search']);
        if ($searchOn == 'true') {
            $searchstr = $this->Strip($_REQUEST['filters']);
            $cond .= $this->constructWhere($searchstr);
        }

        $query = "SELECT ep.expeditie, cle.nume as expeditor, cld.nume as destinatar,
            CASE WHEN (ep.mod_plata = 0 OR ep.mod_plata is null ) AND ep.platitor_id = ep.destinatar_id THEN ep.valoare_totala_expeditie + ep.tva ELSE 0 END as transport,
            IF(ep.tip_plata IN (0,3) and ep.ramburs > 0, ep.ramburs, 0) as ramburs
            FROM scanari_borderouri as scb
            JOIN scanari_coduri as sc on sc.borderou = scb.id
            JOIN exp_prelucrate as ep ON ep.expeditie = sc.expeditie
            LEFT JOIN clienti cle on cle.cod_cl = ep.expeditor_id
            LEFT JOIN clienti cld on cld.cod_cl = ep.destinatar_id
            WHERE {$cond} AND scb.data between '{$this->data_start} 00:00:00' AND '{$this->data_start} 23:59:00' 
            AND scb.tip = ".self::TIP_SCANARE_OUA."
            and sc.is_awb = 1
            GROUP BY ep.expeditie
            HAVING (transport + ramburs) > 0  ORDER by 2";
        $sql = $this->db->QFetchRowArray($query);
        if(empty($sql)) {
            $query_livrate = "SELECT de.expeditie, de.transport, de.ramburs, cle.nume as expeditor, cld.nume as destinatar, (de.transport + de.ramburs) as incasat
                FROM decont_expeditii de 
                LEFT JOIN exp_prelucrate as ep ON ep.expeditie = de.expeditie
                LEFT JOIN clienti cle on cle.cod_cl = ep.expeditor_id
                LEFT JOIN clienti cld on cld.cod_cl = ep.destinatar_id
                where de.agent_id = {$agentId}  
                and de.decont_id = 0
                GROUP BY de.expeditie
                HAVING incasat > 0";
            $sql = $this->db->QFetchRowArray($query_livrate);
        }
        if (!empty($sql)) {
            foreach ($sql as $key => $row) {
                $responce->rows[$key]['id'] = $row['expeditie'];
                $responce->rows[$key]['cell'] = array($row['expeditie'], $row['expeditor'], $row['destinatar'], number_format($row['transport'],2, '.', ''), number_format($row['ramburs'],2, '.', ''), number_format($row['transport'] + $row['ramburs'],2, '.', ''));
            }
        }

        $responce->page = 1;
        $responce->total = 1;
        $responce->records = count($sql);
        return json_encode($responce);
    }

    function JSON_IncasateCash($agent_id = 0){
        $responce = new StdClass();
        $responce->records = 0;
        if($agent_id == 0) return json_encode($responce);

        $having = "HAVING 1 = 1";
        //start generare conditie
        $searchOn = $this->Strip($_REQUEST['_search']);
        if ($searchOn == 'true') {
            $searchstr = $this->Strip($_REQUEST['filters']);
            $having .= $this->constructWhere($searchstr);
        }

        $query_df = "
            select df.id, df.data as dataInc, df.serie as fctChit, df.suma as valoare, df.vFF as vt, group_concat(de.expeditie) as ntsClient, de.operatiune, 1 as tip
            FROM decont_facturi df
            LEFT JOIN decont_expeditii de on df.id = de.factura_id
            where df.decont_id = 0 and df.anulata = 0 and df.agent_id = {$agent_id}
            and df.transaction_id = 0
            group by df.id
            {$having}
        ";
        $query_dr = "
            select dr.id, dr.dataInc, dr.ch_ramburs as fctChit, dr.ramburs as valoare, dr.vCR as vt, group_concat(de.expeditie) as ntsClient, de.operatiune, 2 as tip
            FROM decont_rbs dr
            LEFT JOIN decont_expeditii de on dr.id = de.ramburs_id
            where dr.decont_id = 0 and dr.anulata = 0 and dr.agent_id = {$agent_id}
            and dr.transaction_id = 0
            group by dr.id
            {$having}
        ";
        $query_cf = "
            select cf.id, cf.dataInc, cf.ch_bon as fctChit, cf.suma as valoare, cf.vCF as vt, cf.descriere as ntsClient, 3 as operatiune, 3 as tip
            FROM decont_chitante cf
            where cf.decont_id = 0 and cf.anulata = 0 and cf.agent_id = {$agent_id}
            and cf.transaction_id = 0
            {$having}
        ";

        $query = "
            ({$query_df})
            UNION
            ({$query_dr})
            UNION
            ({$query_cf})
            order by tip, dataInc desc
            ";
        //$this->log("debug", BackEnd::APP_LOG_FILE);
        $sql = $this->db->QFetchRowArray($query);
        $count = 0;
        $total_valoare = 0.00;
        if (!empty($sql)) {
            $count = count($sql);
            foreach ($sql as $key => $row) {
                $responce->rows[$key]['id'] = $row['fctChit'];
                $total_valoare += $row['valoare'];
                $responce->rows[$key]['cell'] = array(
                    $row['fctChit'],
                    number_format($row['valoare'], 2, '.', ''),
                    $row['ntsClient'],
                    $row['tip'],
                    $row['operatiune'],
                    $row['dataInc'],
                    $row['vt'],
                );
            }
        }

        $responce->records = $count;
        $responce->userdata['fctChit'] = "Total:";
        $responce->userdata['valoare'] = number_format($total_valoare, 2, '.', '');
        
        return json_encode($responce);
    }

    function JSON_IncasateCard($agent_id = 0){
        $responce = new StdClass();
        $responce->records = 0;
        if($agent_id == 0) return json_encode($responce);

        $having = "HAVING 1 = 1";
        //start generare conditie
        $searchOn = $this->Strip($_REQUEST['_search']);
        if ($searchOn == 'true') {
            $searchstr = $this->Strip($_REQUEST['filters']);
            $having .= $this->constructWhere($searchstr);
        }

        $query_df = "
            select df.id, df.data as dataInc, df.serie as fctChit, df.suma as valoare, group_concat(de.expeditie) as ntsClient, de.operatiune, 1 as tip
            FROM decont_facturi df
            LEFT JOIN decont_expeditii de on df.id = de.factura_id
            where df.decont_id = 0 and df.anulata = 0 and df.agent_id = {$agent_id}
            and exists (select 1 FROM decont_transactions dt where dt.id = df.transaction_id)
            group by df.id
            {$having}
        ";
        $query_dr = "
            select dr.id, dr.dataInc, dr.ch_ramburs as fctChit, dr.ramburs as valoare, group_concat(de.expeditie) as ntsClient, de.operatiune, 2 as tip
            FROM decont_rbs dr
            LEFT JOIN decont_expeditii de on dr.id = de.ramburs_id
            where dr.decont_id = 0 and dr.anulata = 0 and dr.agent_id = {$agent_id}
            and exists (select 1 FROM decont_transactions dt where dt.id = dr.transaction_id)
            group by dr.id
            {$having}
        ";
        $query_cf = "
            select cf.id, cf.dataInc, cf.ch_bon as fctChit, cf.suma as valoare, cf.descriere as ntsClient, 3 as operatiune, 3 as tip
            FROM decont_chitante cf
            where cf.decont_id = 0 and cf.anulata = 0 and cf.agent_id = {$agent_id}
            and exists (select 1 FROM decont_transactions dt where dt.id = cf.transaction_id)
            {$having}
        ";

        $query = "
            ({$query_df})
            UNION
            ({$query_dr})
            UNION
            ({$query_cf})
            order by tip, dataInc desc
            ";
        //$this->log($query, BackEnd::APP_LOG_FILE);
        $sql = $this->db->QFetchRowArray($query);
        $count = 0;
        $total_valoare = 0.00;
        if (!empty($sql)) {
            $count = count($sql);
            foreach ($sql as $key => $row) {
                $responce->rows[$key]['id'] = $row['fctChit'];
                $total_valoare += $row['valoare'];
                $responce->rows[$key]['cell'] = array(
                    $row['fctChit'],
                    number_format($row['valoare'], 2, '.', ''),
                    $row['ntsClient'],
                    $row['tip'],
                    $row['operatiune'],
                    $row['dataInc'],
                );
            }
        }

        $responce->records = $count;
        $responce->userdata['fctChit'] = "Total:";
        $responce->userdata['valoare'] = number_format($total_valoare, 2, '.', '');
        
        return json_encode($responce);
    }

    function JSON_Nelivrate($agent_id = 0){
        //ultimul checkpoint este exceptie si pentru siguranta nu este in decont_expeditii
        $responce = new StdClass();
        $responce->records = 0;
        $agent_id = intval($agent_id);

        if($agent_id == 0) return json_encode($responce);
        
        $cond = "i.curier = ".$agent_id;
        //start generare conditie
        $searchOn = $this->Strip($_REQUEST['_search']);
        if ($searchOn == 'true') {
            $searchstr = $this->Strip($_REQUEST['filters']);
            $cond .= $this->constructWhere($searchstr);
        }

        $sidx = (empty($_REQUEST['sidx'])) ? "cle.nume" : $_REQUEST['sidx'];
        $sord = (empty($_REQUEST['sord'])) ? "ASC" : $_REQUEST['sord'];

        $query = "SELECT ep.expeditie, cle.nume as expeditor, cld.nume as destinatar,
        CASE WHEN ( ep.mod_plata = 0 OR ep.mod_plata is null ) AND ep.platitor_id = ep.destinatar_id THEN ep.valoare_totala_expeditie + ep.tva ELSE 0 END as transport,
        IF(ep.tip_plata IN (0,3) and ep.ramburs > 0, ep.ramburs, 0) as ramburs,
        ck.abbr as ckp
        FROM (
				select i.expeditie, i.tip
				FROM scanari_coduri as i   
				where {$cond} and i.is_awb = 1 AND i.tip in (select id FROM checkpoints where is_exceptie = 1) and i.data between '{$this->data_start} 00:00:00' AND '{$this->data_start} 23:59:00' and
                i.data = 
                    (select t.data FROM scanari_coduri t where t.expeditie = i.expeditie and t.is_awb = 1 and t.curier = {$agent_id} order by t.data desc limit 1)
 				) as a
        INNER JOIN exp_prelucrate as ep ON (ep.expeditie = a.expeditie and ep.anulata = 0)
        LEFT JOIN clienti cle on cle.cod_cl = ep.expeditor_id
        LEFT JOIN clienti cld on cld.cod_cl = ep.destinatar_id
        JOIN checkpoints ck on a.tip = ck.id
        WHERE NOT EXISTS (SELECT 1 FROM decont_expeditii de WHERE de.expeditie = ep.expeditie and de.data BETWEEN '{$this->data_start} 00:00:00' AND '{$this->data_start} 23:59:00')
        GROUP BY a.expeditie
        HAVING (transport + ramburs) > 0  ORDER BY " . $sidx . " " . $sord;
        // error_log($query);
        $sql = $this->db->QFetchRowArray($query);
        $count = 0;
        $t_exp = 0.00;
        $r_exp = 0.00;
        if (!empty($sql)) {
            $count = count($sql);
            foreach ($sql as $key => $row) {
                $responce->rows[$key]['id'] = $row['expeditie'];
                $responce->rows[$key]['cell'] = array(
                    $row['expeditie'],
                    $row['expeditor'],
                    $row['destinatar'],
                    number_format($row['transport'], 2, '.', ''),
                    number_format($row['ramburs'], 2, '.', ''),
                    number_format($row['transport'] + $row['ramburs'], 2, '.', ''),
                    $row['ckp']
                );
                $t_exp += $row['transport'];
                $r_exp += $row['ramburs'];
            }
        }

        $responce->records = $count;
        $responce->userdata['valoare_factura'] = "Total:";
        $responce->userdata['expeditie'] = $count;
        $responce->userdata['transport'] = number_format($t_exp, 2, '.', '');
        $responce->userdata['ramburs'] = number_format($r_exp, 2, '.', '');
        $responce->userdata['total'] = number_format($t_exp + $r_exp, 2, '.', '');
        
        return json_encode($responce);
    }

    function JSON_Neincasate($agent_id = 0){
        //expeditii livrate, client cu CTR sau plata CARD
        $responce = new StdClass();
        $responce->records = 0;
        if($agent_id == 0) return json_encode($responce);

        $rbs_tip_plata = self::RBS_TIP_PLATA_CASH.",".self::RBS_TIP_PLATA_CONT;
        $cond = "de.agent_id = ".intval($agent_id);
        //start generare conditie
        $searchOn = $this->Strip($_REQUEST['_search']);
        if ($searchOn == 'true') {
            $searchstr = $this->Strip($_REQUEST['filters']);
            $cond .= $this->constructWhere($searchstr);
        }

        $query = "
            SELECT de.expeditie, cle.nume as expeditor, cld.nume as destinatar, clp.nume as platitor, ep.mod_plata,
            IF(ep.mod_plata = 0 and (
                (ep.destinatar_id is not null and ep.destinatar_id > 0 and ep.platitor_id = ep.destinatar_id AND de.operatiune = 2)
                or
                (ep.expeditor_id is not null and ep.expeditor_id > 0 and ep.platitor_id = ep.expeditor_id AND de.operatiune = 1)
                ), 
                (ep.valoare_totala_expeditie + ep.tva ) , 0) as transport,
            'COK' as ckp
            FROM decont_expeditii de
            JOIN agenti ag ON ag.cod_ag = de.agent_id
            LEFT JOIN {$this->tables['exp_prelucrate']} ep ON (ep.expeditie = de.expeditie and ep.anulata = 0)
            LEFT JOIN clienti cle on cle.cod_cl = ep.expeditor_id
            LEFT JOIN clienti cld on cld.cod_cl = ep.destinatar_id
            LEFT JOIN clienti clp on clp.cod_cl = ep.platitor_id
            WHERE {$cond} AND de.decont_id = 0 and de.factura_id = 0
            GROUP BY de.id 
            HAVING transport > 0";
        
        //error_log($query);
        $sql = $this->db->QFetchRowArray($query);
        $count = 0;
        $t_inc = 0.00;
        if (!empty($sql)) {
            $count = count($sql);
            foreach ($sql as $key => $row) {
                $responce->rows[$key]['id'] = $row['expeditie'];
                $responce->rows[$key]['cell'] = array(
                    $row['expeditie'],
                    $row['expeditor'],
                    $row['destinatar'],
                    $row['platitor'],
                    $row['mod_plata'],
                    number_format($row['transport'], 2, '.', ''),
                    $row['ckp']
                );

                $t_inc += $row['transport'];
            }
        }

        $responce->records = $count;
        $responce->userdata['expeditie'] = $count;
        $responce->userdata['transport'] = number_format($t_inc, 2, '.', '');
        return json_encode($responce);
    }

    function JSON_Cheltuieli($agent_id = 0){

        $responce = new StdClass();
        $cond = "1";
        //start generare conditie
        $searchOn = $this->Strip($_REQUEST['_search']);
        if ($searchOn == 'true') {
            $searchstr = $this->Strip($_REQUEST['filters']);
            $cond .= $this->constructWhere($searchstr);
        }

        $cond .= " AND dicd.decont_id = 0 and dicd.anulata = 0 AND dicd.agent_id = {$agent_id}";

        $query = "SELECT dicd.id, dicd.tip, dicd.suma, dicd.descriere
        FROM decont_cheltuieli dicd
        WHERE {$cond}";
        // error_log($query);
        $sql = $this->db->QFetchRowArray($query);
        $count = 0;
        $suma = 0;
        if (!empty($sql)) {
            $count = count($sql);
            foreach ($sql as $key => $row) {
                $responce->rows[$key]['id'] = $row['id'];
                $responce->rows[$key]['cell'] = array(
                    $row['tip'],
                    number_format($row['suma'], 2, '.', ''),
                    $row['descriere'],
                );
                $suma += $row['suma'];
            }
        }
        $responce->userdata['suma'] = number_format($suma, 2, '.', '');
        $responce->records = $count;
        return json_encode($responce);

    }

    //TODO : validare facturi -> inchidere zi
    function ScanValidare() {
        $responce = new StdClass();
        $responce->errorCode = 0;
        $responce->errorMsg = "ok";
        $responce->records = "";
        $post_coduri = trim($_POST['coduri'] ?? "");
        $error_coduri_notExists = [];
        $error_coduri_decontate = [];
        $error_coduri_dublate = [];
        $error_coduri_card = [];

        if(empty($post_coduri)) {
            $responce->errorCode = 1;
            $responce->errorMsg = "Lista de coduri pentru validare este goala !";
            return json_encode($responce);
        }
        
        $agent_id = intval(Backend::sSanitize($_POST['agent_id'] ?? 0));
        if($agent_id == 0) {
            $responce->errorCode = 1;
            $responce->errorMsg = "Nu gasesc agentul pentru care se cere validarea !";
            return json_encode($responce);
        }
        
        $coduri = preg_split( '/\r\n|\r|\n/', $post_coduri);
        $coduri = array_keys(array_flip($coduri));
        foreach($coduri as $cod){
            $cod = $this->sanitize($cod);
            if(empty($cod)) continue;
            
            $cod = strtoupper($cod);
            if($this->isCR($cod)){
                //deja decontata ?
                $query_cr = "SELECT dr.id, dr.vCR, dr.decont_id, df.id as factura_id, de.expeditie, dr.transaction_id
                    FROM decont_rbs dr 
                    inner join decont_expeditii de on de.ramburs_id = dr.id
                    left join decont_facturi df 
                        on df.id = de.factura_id and df.anulata = 0 and df.operatiune = 2 and df.transaction_id = 0
                    where dr.ch_ramburs like :ch_ramburs and dr.agent_id = :agent_id and dr.anulata = 0";
                $sql_cr = $this->db->QFetchRowArray($query_cr, ['ch_ramburs'=>$cod, 'agent_id'=>$agent_id]);
                if(empty($sql_cr)){
                    $error_coduri_notExists[] = $cod;
                    continue;
                }

                if(count($sql_cr) > 1){//cod dublat
                    $error_coduri_dublate[] = $cod;
                    continue;
                }
                
                if(!empty($sql_cr[0]['decont_id'])){
                    $error_coduri_decontate[] = $cod;
                    continue;
                }

                if(!empty($sql_cr[0]['transaction_id'])){
                    $error_coduri_card[] = $cod;
                    continue;
                }
                //valideaza
                $this->db->QueryUpdate('decont_rbs', ['vCR' => 1], " id = {$sql_cr[0]['id']}");

                //valideaza si factura la livrare
                if(!empty($sql_cr[0]['factura_id']))
                    $this->db->QueryUpdate('decont_facturi', ['vFF' => 1], " id = {$sql_cr[0]['factura_id']}");
            }
            else if($this->isCF($cod)){
                //deja decontata ?
                $query_cf = "SELECT id, vCF, decont_id, transaction_id FROM decont_chitante 
                    where ch_bon like :ch_bon and agent_id = :agent_id and anulata = 0";
                $sql_cf = $this->db->QFetchRowArray($query_cf, ['ch_bon'=>$cod, 'agent_id'=>$agent_id]);
                if(empty($sql_cf)){
                    $error_coduri_notExists[] = $cod;
                    continue;
                }
                if(count($sql_cf) > 1){//cod dublat
                    $error_coduri_dublate[] = $cod;
                    continue;
                }
                if(!empty($sql_cf[0]['decont_id'])){
                    $error_coduri_decontate[] = $cod;
                    continue;
                }

                if(!empty($sql_cf[0]['transaction_id'])){
                    $error_coduri_card[] = $cod;
                    continue;
                }

                $this->db->QueryUpdate('decont_chitante', ['vCF' => 1], " id = {$sql_cf[0]['id']}");
            }
            else if($this->isFF($cod)){
                //deja decontata ?
                $query_ff = "SELECT df.id, df.vFF, df.decont_id, df.transaction_id, df.operatiune,
                    group_concat(de.id) as expIds
                    FROM decont_facturi df 
                    inner join decont_expeditii de on de.factura_id = df.id
                    where df.serie like :serie and df.agent_id = :agent_id and df.anulata = 0
                    group by df.id";
                $sql_ff = $this->db->QFetchRowArray($query_ff, ['serie'=>$cod, 'agent_id'=>$agent_id]);
                if(empty($sql_ff)){
                    $error_coduri_notExists[] = $cod;
                    continue;
                }
                if(count($sql_ff) > 1){//cod dublat
                    $error_coduri_dublate[] = $cod;
                    continue;
                }
                if(!empty($sql_ff[0]['decont_id'])){
                    $error_coduri_decontate[] = $cod;
                    continue;
                }
                if(!empty($sql_ff[0]['transaction_id'])){
                    $error_coduri_card[] = $cod;
                    continue;
                }
                $this->db->QueryUpdate('decont_facturi', ['vFF' => 1], " id = {$sql_ff[0]['id']}");
                //valideaza si RBS daca factura la livrare
                if(!empty($sql_ff[0]['expIds']) && $sql_ff[0]['operatiune'] == 2){
                    //search rbs for expIds
                    $query_rbs = "SELECT group_concat(dr.id) as rbsIds
                        FROM decont_rbs dr 
                        inner join decont_expeditii de on de.ramburs_id = dr.id
                        where de.id in ({$sql_ff[0]['expIds']}) and dr.agent_id = {$agent_id}
                        and dr.transaction_id = 0 and dr.decont_id = 0 and dr.anulata = 0
                        group by dr.agent_id";
                    $sql_rbs = $this->db->QFetchArray($query_rbs);
                    if(empty($sql_rbs)) continue;
                    if(empty($sql_rbs['rbsIds'])) continue;

                    $arr_rbsIds = explode(',', $sql_rbs['rbsIds']);
                    $arr_rbsIds = array_keys(array_flip($arr_rbsIds));
                    $arr_rbsIdsNew = [];
                    foreach($arr_rbsIds as $rbsId){
                        if(!empty($rbsId)) $arr_rbsIdsNew[] = intval($rbsId);
                    }
                    if(count($arr_rbsIdsNew) > 0){
                        $rbsIds = implode(',', $arr_rbsIdsNew);
                        $this->db->QueryUpdate('decont_rbs', ['vCR' => 1], " id in ({$rbsIds})");
                    }
                }
            }  
        }

        if(count($error_coduri_notExists) > 0 || count($error_coduri_decontate) > 0  || count($error_coduri_dublate) > 0 || count($error_coduri_card) > 0) {
            $responce->errorCode = 1;
            $responce->errorMsg = "Codurile urmatoare : au fost deja DECONTATE, incasate cu CARD, nu exista sau sunt dublate !";
            $responce->records = implode(", ", array_merge($error_coduri_notExists, $error_coduri_decontate, $error_coduri_dublate, $error_coduri_card));
            return json_encode($responce);
        }

        return json_encode($responce);
    }

    private function isFF($cod){
        return strlen($cod) == 12 && substr($cod, 0, 1) !== "R";
    }

    private function isCR($cod){
        return strlen($cod) == 17 && substr($cod, 0, 1) == "R";
    }

    private function isCF($cod){
        return strlen($cod) == 11  && substr($cod, 0, 1) !== "R";
    }
}
