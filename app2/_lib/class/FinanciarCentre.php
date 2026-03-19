<?php

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Color;

class ModulFinanciarCentre extends BackEnd
{

    public $final_result;
    public $page_prefix;

    private $centru_nume, $centru_cod, $centru_id, $financiar;
    private $mst_financiar_nume, $mst_financiar_id, $mst_financiar;
    private $children_centru_ids = [];
    private $children_centru_nume = [];
    private $children_centru_cod = [];
    private $data_start, $data_final, $today;
    private $user_nume, $user_telefon;

    const MAX_TREZORERIE = 5000.00; // limita maxima pentru incarcare trezorerie

    function __construct($config = 0, $act = 1, $db = 0)
    {
        parent:: __construct($config, $db);

        $this->vars['title_page'] = 'Financiar';
        $this->today = $this->data_start = $this->data_final = date('Y-m-d');

        $this->user_nume = $_SESSION["user"]["nume"] ?? "";
		$this->user_telefon = $_SESSION["user"]["telefon"] ?? "";

        $this->centru_id = 0;
        $this->mst_financiar_id = 0;
        $this->centru_nume = "unknown";
        $this->mst_financiar_nume = "unknown";
        $this->financiar = false;
        $this->mst_financiar = false;
        $query = "SELECT ce.id, ce.nume, ce.label, ce.financiar, 
            cem.id as mst_financiar_id, cem.nume as mst_financiar_nume, cem.financiar as mst_financiar,
            group_concat(cec.id) as children_centru_ids, 
            group_concat(concat(cec.id, '#', cec.nume)) as children_centru_nume,
            group_concat(concat(cec.id, '#', cec.label)) as children_centru_cod
            FROM centre ce
            LEFT JOIN centre cem on cem.id = ce.mst_financiar_id
            LEFT JOIN centre cec on cec.mst_financiar_id = ce.id and cec.financiar = 0 and cec.id != ce.id
            WHERE ce.id = {$this->user_centru_id}";
        $sql = $this->db->QFetchArray($query);
        if(!empty($sql)) {
            $this->centru_id = $sql['id'];
            $this->mst_financiar_id = $sql['mst_financiar_id'] ?? 0;
            //error_log("d:{$this->mst_financiar_id}:d");
            $this->centru_nume = $sql['nume'];
            $this->centru_cod = $sql['label'];
            $this->mst_financiar_nume = $sql['mst_financiar_nume'] ?? "unknown";
            $this->financiar = $sql['financiar'] == 1;
            $this->mst_financiar = ($sql['mst_financiar'] ?? 0) == 1;
            $this->children_centru_ids = $this->financiar && !empty($sql['children_centru_ids']) ? explode(',', $sql['children_centru_ids']) : [];
            $this->children_centru_ids = array_keys(array_flip($this->children_centru_ids)); // remove duplicates
            if(count($this->children_centru_ids) > 0) {
                $children_centru_nume_tmp = !empty($sql['children_centru_nume']) ? explode(',', $sql['children_centru_nume']) : [];
                foreach($children_centru_nume_tmp as $child_centru){
                    $parts = explode('#', $child_centru);
                    if(count($parts) == 2){
                        $this->children_centru_nume[$parts[0]] = $parts[1];
                    }
                }
                $children_centru_cod_tmp = !empty($sql['children_centru_cod']) ? explode(',', $sql['children_centru_cod']) : [];
                foreach($children_centru_cod_tmp as $child_centru){
                    $parts = explode('#', $child_centru);
                    if(count($parts) == 2){
                        $this->children_centru_cod[$parts[0]] = $parts[1];
                    }
                }
            }

        }

        if(!empty($_REQUEST['show_data']))
            $this->data_start = $this->data_final = $this->TransformDate($_REQUEST['show_data']);

            $this->page_prefix = 'financiar_';
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

        if (($arr[1] ?? '') == 'centru' && (in_array("new_decont", $this->user_rights) || in_array($this->user_id, parent::CAN_MODIFY_GEOCODE_FINANCIAR))){
            if (!isset($arr[2])){
                $this->final_result = $this->DashboardFinanciarCentru();
                $flag = 1;
            } else if (($arr[2] ?? '') == 'dash_rbs_cash_cont' && !isset($arr[3])){
                echo $this->JSON_FinanciarCentru_DashRbsCashCont();
                $flag = 1;
            } else if (($arr[2] ?? '') == 'dash_fa_ch' && !isset($arr[3])){
                echo $this->JSON_FinanciarCentru_DashFaCh();
                $flag = 1;
            } else if (($arr[2] ?? '') == 'rbs_cash_cont_decontate' && !isset($arr[3])){
                echo $this->JSON_FinanciarCentru_RbsCashContDecontate();
                $flag = 1;
            } else if (($arr[2] ?? '') == 'cash_nedecontate' && !isset($arr[3])){
                echo $this->JSON_FinanciarCentru_CashNeDecontate();
                $flag = 1;
            } else if (($arr[2] ?? '') == 'facturi_cash_decontate' && !isset($arr[3])){
                echo $this->JSON_FinanciarCentru_FacturiCashDecontate();
                $flag = 1;
            } else if (($arr[2] ?? '') == 'client_ctr_cash_decontate' && !isset($arr[3])){
                echo $this->JSON_FinanciarCentru_ClientCtrCashDecontate();
                $flag = 1;
            } else if (($arr[2] ?? '') == 'incasari_card' && !isset($arr[3])){
                echo $this->JSON_FinanciarCentru_IncasariCard();
                $flag = 1;
            } else if (($arr[2] ?? '') == 'rbs_cash_aprobate' && !isset($arr[3])){
                echo $this->JSON_FinanciarCentru_RbsCashAprobate();
                $flag = 1;
            } else if (($arr[2] ?? '') == 'creare_rbs_cash' && !isset($arr[3])){
                echo $this->FinanciarCentru_CreareRbsCash();
                $flag = 1;
            } else if (($arr[2] ?? '') == 'printare_rbs_cash' && !isset($arr[3])){
                echo $this->FinanciarCentru_PrintareRbsCash();
                $flag = 1;
            } else if (($arr[2] ?? '') == 'transfer_casa' && !isset($arr[3])){
                echo $this->FinanciarCentru_TransferCasa();
                $flag = 1;
            } else if (($arr[2] ?? '') == 'incarcare_casa' && !isset($arr[3])){
                echo $this->FinanciarCentru_IncarcareCasa();
                $flag = 1;
            } else if (($arr[2] ?? '') == 'incarcare_trezorerie' && !isset($arr[3])){
                echo $this->FinanciarCentru_IncarcareTrezorerie();
                $flag = 1;
            } else if (($arr[2] ?? '') == 'confirma_salarii' && !isset($arr[3])){
                echo $this->FinanciarCentru_ConfirmaSalarii();
                $flag = 1;
            } else if (($arr[2] ?? '') == 'istoric' && !isset($arr[3])){
                echo $this->JSON_FinanciarCentru_Istoric();
                $flag = 1;
            }
        }

        if (($arr[1] ?? '') == 'centre' && (in_array("financiar_centre", $this->user_rights) || in_array($this->user_id, parent::CAN_FINANCIAR_CENTRE))){
            if (!isset($arr[2])){
                $this->final_result = $this->DashboardFinanciarCentre();
                $flag = 1;
            } else if (($arr[2] ?? '') == 'dashboard' && !isset($arr[3])){
                echo $this->JSON_DashboardFinanciarCentre();
                $flag = 1;
            } else if (($arr[2] ?? '') == 'dashboard' && ($arr[3] ?? '') == 'centru' && !isset($arr[4])){
                echo $this->JSON_DashboardFinanciarCentre_Centru($arr[4] ?? 0);
                $flag = 1;
            } else if (($arr[2] ?? '') == 'export' && !isset($arr[3])){
                echo $this->ExportDashboardFinanciarCentre();
                $flag = 1;
            } else if (($arr[2] ?? '') == 'transfer' && !isset($arr[3])){
                echo $this->JSON_FinanciarCentre_Transfer();
                $flag = 1;
            }
        }
        
        if (($arr[1] ?? '') == 'salarii' && (in_array("financiar_salarii", $this->user_rights) || in_array($this->user_id, parent::CAN_MODIFY_SALARII_FINANCIAR))){
            if (!isset($arr[2])){
                $this->final_result = $this->SalariiFinanciarCentre();
                $flag = 1;
            } else if (($arr[2] ?? '') == 'centre' && !isset($arr[3])){
                echo $this->JSON_SalariiFinanciarCentre();
                $flag = 1;
            } else if (($arr[2] ?? '') == 'edit' && !isset($arr[3])){
                echo $this->SalariiFinanciarEdit();
                $flag = 1;
            }
        }
        
        if(empty($flag))
            $this->final_result = $this->PageNotFound();
    }
 
    //////////////--FINANCIAR CENTRU--
    function DashboardFinanciarCentru($m_centru_id = 0) {
        $centru_id = ($this->user_profile == 10 && $m_centru_id > 0) ? $m_centru_id : $this->centru_id;
        $centru_nume = ($this->user_profile == 10 && $m_centru_id > 0) ? "" : $this->centru_nume;
        $centru_cod = ($this->user_profile == 10 && $m_centru_id > 0) ? "" : $this->centru_cod;

        $this->vars['title_page'] = "Situatie financiara centru {$centru_nume} la data de ".date('d.m.Y');
        $vars = [];
        $vars['today'] = date('d.m.Y');
        $vars['onload_js_version'] = $this->config['version']['onload_js_version'];
        $vars['CENTRU_ID'] = $centru_id;
        $vars['TOTAL_SALARII_CONFIRMATE'] = 0.00;
        $vars['TOTAL_SALARII_APROBATE'] = 0.00;
        $vars['CASA_RBS_CASH_CONT'] = 0.00;
        $vars['CASA_FA_CH_CASH_CONT'] = 0.00;
        $vars['BUTON_INCARCARE_CASA_RBS'] = '';
        $vars['BUTON_INCARCARE_CASA_FA_CH'] = '';
        $vars['BUTON_INCARCARE_TREZORERIE_RBS'] = '';
        $vars['BUTON_INCARCARE_TREZORERIE_FA_CH'] = '';

        
        $vars['BUTON_INCARCARE_TREZORERIE_RBS'] = '<input type="button" value="Trezorerie"  onclick="IncarcareTrezorerie(3);"/>';
        $vars['BUTON_INCARCARE_TREZORERIE_FA_CH'] = '<input type="button" value="Trezorerie"  onclick="IncarcareTrezorerie(4);"/>';
        if($this->financiar === true && $this->centru_id == 47){
            $vars['BUTON_INCARCARE_CASA_RBS'] = '<input type="button" value="Incarcare casa"  onclick="IncarcareCasa(1);"/>';
            $vars['BUTON_INCARCARE_CASA_FA_CH'] = '<input type="button" value="Incarcare casa"  onclick="IncarcareCasa(2);"/>';
        }

        $decont_casa_sql = $this->db->QFetchArray("SELECT id, casa_rbs, casa_fa_ch
                FROM decont_casa 
                WHERE centru_id = {$this->centru_id} AND DATE(created_at) = '{$this->today}'");
        if(!empty($decont_casa_sql)){
            $vars['CASA_RBS_CASH_CONT'] = $decont_casa_sql['casa_rbs'] ?? 0.00;
            $vars['CASA_FA_CH_CASH_CONT'] = $decont_casa_sql['casa_fa_ch'] ?? 0.00;
        }

        $query_salarii = "SELECT ces.id, ces.amount as salarii_aprobate, sum(COALESCE(cesl.amount, 0)) as salarii_confirmate
            FROM centre_salarii ces
            LEFT JOIN centre_salarii_linii cesl on cesl.centre_salarii_id = ces.id
            where ces.centru_id = {$centru_id} 
            and ces.created_at = (select max(created_at) FROM centre_salarii where centru_id = {$centru_id})
            group by ces.id";
        $result_salarii = $this->db->QFetchArray($query_salarii);
        //error_log("query_salarii: ".$query_salarii);
        if(!empty($result_salarii)) {
            $vars['TOTAL_SALARII_APROBATE'] = $result_salarii['salarii_aprobate'] ?? 0.00;
            $vars['TOTAL_SALARII_CONFIRMATE'] = $result_salarii['salarii_confirmate'] ?? 0.00;
        }
        
        $vars['TOTAL_SALARII_APROBATE'] = number_format($vars['TOTAL_SALARII_APROBATE'], 2, '.', '');
        $vars['TOTAL_SALARII_CONFIRMATE'] = number_format($vars['TOTAL_SALARII_CONFIRMATE'], 2, '.', '');
        $vars['CASA_RBS_CASH_CONT'] = number_format($vars['CASA_RBS_CASH_CONT'], 2, '.', '');
        $vars['CASA_FA_CH_CASH_CONT'] = number_format($vars['CASA_FA_CH_CASH_CONT'], 2, '.', '');

        return $this->Parse('financiar_centru.html', $vars);
    }

    function JSON_FinanciarCentru_DashRbsCashCont() {
        $responce = new StdClass();
        $responce->records = 0;
        $today = date('Y-m-d');
        
        $centre = [];
        $centre[$this->centru_id] = [
            'centru_nume' => $this->centru_nume,
            'centru_cod' => $this->centru_cod
        ];
        foreach($this->children_centru_ids as $child_centru_id) {
            $centre[$child_centru_id] = [
                'centru_nume' => $this->children_centru_nume[$child_centru_id] ?? '',
                'centru_cod' => $this->children_centru_cod[$child_centru_id] ?? ''
            ];
        }
        $centre_ids = "(".implode(',', array_keys($centre)).")";

        $query_nedecontate="SELECT COUNT(dr.id) as nr, sum(dr.ramburs) as rbs_nedecontate, ag.cod_centru
            FROM decont_rbs dr
            inner join agenti ag on ag.cod_ag = dr.agent_id
            where dr.anulata = 0 and dr.transaction_id = 0 
            and dr.decont_id = 0 and ag.cod_centru in {$centre_ids}
            GROUP BY ag.cod_centru
            ";
        $result_nedecontate = $this->db->QFetchRowArray($query_nedecontate);
        if(!empty($result_nedecontate)) {
            foreach ($result_nedecontate as $row) {
                if(isset($centre[$row['cod_centru']])){
                    $centre[$row['cod_centru']]['rbs_nedecontate'] = $row['rbs_nedecontate'] ?? 0.00;
                    $centre[$row['cod_centru']]['nr_rbs_nedecontate'] = $row['nr'] ?? 0;
                }
            }
        }

        $query_decont_casa_sql = $this->db->QFetchRowArray("SELECT centru_id, nr_rbs_decontate, rbs_decontate, 
                facturi_decontate, chitante_decontate, cheltuieli_rbs, salarii_confirmate, 
                nr_rbs_generate, rbs_generate, casa_rbs
                FROM decont_casa 
                WHERE centru_id in {$centre_ids} AND DATE(created_at) = '{$this->today}'");
        if(!empty($query_decont_casa_sql)){
            foreach ($query_decont_casa_sql as $row) {
                if(isset($centre[$row['centru_id']])){
                    $centre[$row['centru_id']]['rbs_decontate'] = $row['rbs_decontate'] ?? 0.00;
                    $centre[$row['centru_id']]['nr_rbs_decontate'] = $row['nr_rbs_decontate'] ?? 0;
                    $centre[$row['centru_id']]['rbs_generate'] = $row['rbs_generate'] ?? 0.00;
                    $centre[$row['centru_id']]['nr_rbs_generate'] = $row['nr_rbs_generate'] ?? 0;
                    $centre[$row['centru_id']]['casa_rbs'] = $row['casa_rbs'] ?? 0.00;
                }
            }
        }

        $query_aprobate="SELECT IF(cle.zona_id > 0 and clez.centru_id > 0, clez.centru_id, lce.cod_centru) as centru_id,
            COUNT(ep.cod_expeditie) as nr_rbs_aprobate, sum(COALESCE(ep.ramburs, 0)) as rbs_aprobate          
            FROM exp_prelucrate ep
            LEFT JOIN clienti cle on cle.cod_cl = ep.expeditor_id
            LEFT JOIN zones clez ON clez.id = cle.zona_id
            LEFT JOIN localitati lce ON lce.cod_lc = cle.cod_lc
            LEFT JOIN exp_prelucrate rbs
                ON rbs.referire = ep.expeditie AND rbs.tip_exp = 3 and rbs.anulata = 0
            LEFT JOIN exp_prelucrate rtn
                ON rtn.referire = ep.expeditie AND rtn.tip_exp = 5 and rtn.anulata = 0
            WHERE ep.tip_exp = 0 and ep.anulata = 0 and ep.ramburs > 0 and ep.tip_plata = 0 and ep.status_ramburs = 30
            and rbs.cod_expeditie IS NULL and rtn.cod_expeditie IS NULL
            and IF(cle.zona_id > 0 and clez.centru_id > 0, clez.centru_id, lce.cod_centru) in {$centre_ids}
            GROUP BY IF(cle.zona_id > 0 and clez.centru_id > 0, clez.centru_id, lce.cod_centru)";

        $result_aprobate = $this->db->QFetchRowArray($query_aprobate);
        if(!empty($result_aprobate)) {
            foreach( $result_aprobate as $row) {
                if(isset($centre[$row['centru_id']])){
                    $centre[$row['centru_id']]['rbs_aprobate'] = $row['rbs_aprobate'] ?? 0.00;
                    $centre[$row['centru_id']]['nr_rbs_aprobate'] = $row['nr_rbs_aprobate'] ?? 0;
                }
            }
        }

        foreach($centre as $centru_id => $centru) {
            $responce->rows[] = [
                'id' => $centru_id,
                'cell' => [
                    $centru['centru_cod'] ?? '',
                    number_format($centru['rbs_nedecontate'] ?? 0.00, 2, '.', ''),
                    number_format($centru['rbs_decontate'] ?? 0.00, 2, '.', ''),
                    number_format($centru['rbs_aprobate'] ?? 0.00, 2, '.', ''),
                    number_format($centru['rbs_generate'] ?? 0.00, 2, '.', ''),
                    $this->financiar ? $centru_id : '',
                    $this->financiar ? $centru_id : '',
                    number_format($centru['casa_rbs'] ?? 0.00, 2, '.', ''),
                    $this->centru_id,
                ]
            ];
        }

        $responce->page = 1; 
        $responce->total = 1; 
        $responce->records = count($centre);
        $responce->userdata['rbs_nedecontate'] = number_format(
            array_sum(array_column($centre, 'rbs_nedecontate')), 2, '.', '');
        $responce->userdata['rbs_decontate'] = number_format(
            array_sum(array_column($centre, 'rbs_decontate')), 2, '.', '');
        $responce->userdata['rbs_aprobate'] = number_format(
            array_sum(array_column($centre, 'rbs_aprobate')), 2, '.', '');
        $responce->userdata['rbs_generate'] = number_format(
            array_sum(array_column($centre, 'rbs_generate'))
            , 2, '.', '');
        $responce->userdata['casa_rbs'] = number_format(
            array_sum(array_column($centre, 'casa_rbs'))
            , 2, '.', '');
        
        return json_encode($responce);
    }

    function JSON_FinanciarCentru_DashFaCh() {
        $responce = new StdClass();
        $responce->records = 0;
        $today = date('Y-m-d');
        
        $centre = [];
        $centre[$this->centru_id] = [
            'centru_nume' => $this->centru_nume,
            'centru_cod' => $this->centru_cod
        ];
        foreach($this->children_centru_ids as $child_centru_id) {
            $centre[$child_centru_id] = [
                'centru_nume' => $this->children_centru_nume[$child_centru_id] ?? '',
                'centru_cod' => $this->children_centru_cod[$child_centru_id] ?? ''
            ];
        }
        $centre_ids = "(".implode(',', array_keys($centre)).")";

        $query_df_nedecontate = "
            select ag.cod_centru as centru_id,
            count(df.id) as nr_transport, sum(df.suma) as transport,
            0 as nr_chitante, 0 as chitante
            FROM decont_facturi df
            left join agenti ag on ag.cod_ag = df.agent_id
            where df.decont_id = 0 and df.anulata = 0 and df.transaction_id = 0
            and ag.cod_centru in {$centre_ids}
            group by ag.cod_centru
        ";
        $query_cf_nedecontate = "
            select ag.cod_centru as centru_id,
            0 as nr_transport, 0 as transport,
            count(cf.id) as nr_chitante, sum(cf.suma) as chitante 
            FROM decont_chitante cf
            left join agenti ag on ag.cod_ag = cf.agent_id
            where cf.decont_id = 0 and cf.anulata = 0 and cf.transaction_id = 0
            and ag.cod_centru in {$centre_ids}
            group by ag.cod_centru
        ";

        $query_nedecontate = "
            select ndc.centru_id, 
            sum(COALESCE(ndc.nr_transport, 0)) as nr_transport_nedecontate, sum(COALESCE(ndc.transport, 0)) as transport_nedecontate, 
            sum(COALESCE(ndc.nr_chitante, 0)) as nr_chitante_nedecontate, sum(COALESCE(ndc.chitante, 0)) as chitante_nedecontate
            from (
                ({$query_df_nedecontate})
                UNION
                ({$query_cf_nedecontate})
            ) as ndc
            group by ndc.centru_id
            ";

        $result_nedecontate = $this->db->QFetchRowArray($query_nedecontate);
        if(!empty($result_nedecontate)) {
            foreach ($result_nedecontate as $row) {
                if(isset($centre[$row['centru_id']])){
                    $centre[$row['centru_id']]['transport_nedecontate'] = $row['transport_nedecontate'] ?? 0.00;
                    $centre[$row['centru_id']]['nr_transport_nedecontate'] = $row['nr_transport_nedecontate'] ?? 0;
                    $centre[$row['centru_id']]['chitante_nedecontate'] = $row['chitante_nedecontate'] ?? 0.00;
                    $centre[$row['centru_id']]['nr_chitante_nedecontate'] = $row['nr_chitante_nedecontate'] ?? 0;
                }
            }
        }

        $query_decont_casa_sql = $this->db->QFetchRowArray("SELECT centru_id,
                facturi_decontate, chitante_decontate, cheltuieli_fa_ch, salarii_confirmate, 
                casa_fa_ch
                FROM decont_casa 
                WHERE centru_id in {$centre_ids} AND DATE(created_at) = '{$this->today}'");
        if(!empty($query_decont_casa_sql)){
            foreach ($query_decont_casa_sql as $row) {
                if(isset($centre[$row['centru_id']])){
                    $centre[$row['centru_id']]['facturi_decontate'] = $row['facturi_decontate'] ?? 0.00;
                    $centre[$row['centru_id']]['chitante_decontate'] = $row['chitante_decontate'] ?? 0.00;
                    $centre[$row['centru_id']]['cheltuieli_fa_ch'] = $row['cheltuieli_fa_ch'] ?? 0.00;
                    $centre[$row['centru_id']]['casa_fa_ch'] = $row['casa_fa_ch'] ?? 0.00;
                }
            }
        }

        foreach($centre as $centru_id => $centru) {
            $responce->rows[] = [
                'id' => $centru_id,
                'cell' => [
                    $centru['centru_cod'] ?? '',
                    number_format($centru['transport_nedecontate'] ?? 0.00, 2, '.', ''),
                    number_format($centru['facturi_decontate'] ?? 0.00, 2, '.', ''),
                    number_format($centru['chitante_nedecontate'] ?? 0.00, 2, '.', ''),
                    number_format($centru['chitante_decontate'] ?? 0.00, 2, '.', ''),
                    //number_format($centru['cheltuieli_fa_ch'] ?? 0.00, 2, '.', ''),
                    number_format($centru['casa_fa_ch'] ?? 0.00, 2, '.', ''),
                    $this->centru_id,
                ]
            ];
        }

        $responce->page = 1; 
        $responce->total = 1; 
        $responce->records = count($centre);
        $responce->userdata['transport_nedecontate'] = number_format(
            array_sum(array_column($centre, 'transport_nedecontate')), 2, '.', '');
        $responce->userdata['facturi_decontate'] = number_format(
            array_sum(array_column($centre, 'facturi_decontate')), 2, '.', '');
        $responce->userdata['chitante_nedecontate'] = number_format(
            array_sum(array_column($centre, 'chitante_nedecontate')), 2, '.', '');
        $responce->userdata['chitante_decontate'] = number_format(
            array_sum(array_column($centre, 'chitante_decontate'))
            , 2, '.', '');
            /*
        $responce->userdata['cheltuieli_fa_ch'] = number_format(
            array_sum(array_column($centre, 'cheltuieli_fa_ch'))
            , 2, '.', '');
        */
        $responce->userdata['casa_fa_ch'] = number_format(
            array_sum(array_column($centre, 'casa_fa_ch'))
            , 2, '.', '');
        
        return json_encode($responce);
    }

    function JSON_FinanciarCentru_RbsCashContDecontate($m_centru_id = 0) {
        $responce = new StdClass();
        $responce->records = 0;
        $today = date('Y-m-d');
        $centru_id = ($this->user_profile == 10 && $m_centru_id > 0) ? $m_centru_id : $this->centru_id;

        $page = intval($_GET['page'] ?? 1);
		$limit = intval($_GET['rows'] ?? 50);
		$sidx = trim($this->sanitize(empty($_GET['sidx']) ? 1 : $_GET['sidx']));
		$sord = trim($this->sanitize($_GET['sord'] ?? 'asc'));

        $cond = '1=1 ';
        $searchOn = $this->Strip($_GET['_search'] ?? '');        
        if ($searchOn == 'true') {
            $searchstr = $this->Strip($_GET['filters'] ?? '');
            $cond .= $this->constructWhere($searchstr);
        }

        $query_count="SELECT COUNT(dr.id) as nr, sum(dr.ramburs) as total_valoare          
            FROM decont_rbs dr
            inner join decont_expeditii de on dr.id = de.ramburs_id
            inner join decont_agent da on dr.decont_id = da.id
            LEFT JOIN exp_prelucrate ep on ep.expeditie = de.expeditie
            LEFT JOIN clienti cle on cle.cod_cl = ep.expeditor_id
            LEFT JOIN agenti ag on ag.cod_ag = dr.agent_id
            where {$cond} and DATE(da.data) = '{$this->today}'
            and da.centru_id = {$centru_id}
            and dr.anulata = 0 and dr.transaction_id = 0";
        $result_count = $this->db->QFetchArray($query_count);
        $count = !empty($result_count['nr']) ? $result_count['nr'] : 0;

        $total_pages = $count > 0 ? ceil($count/$limit) : 0;
        if ($page > $total_pages) $page = $total_pages;
        $start = $limit * $page - $limit;
        if ($start < 0) $start = 0;

        if($count == 0) {
            $responce->page = $page; 
            $responce->total = $total_pages; 
            $responce->records = $count;
            $responce->userdata['awb'] = 0;
            $responce->userdata['valoare'] = number_format(0, 2, '.', '');
            return json_encode($responce);
        }

        $query = "SELECT dr.id, dr.dataInc, dr.ch_ramburs, dr.ramburs as valoare, de.expeditie, ep.tip_plata, cle.nume as expeditor, ag.nume_ag as agent
            FROM decont_rbs dr
            inner join decont_expeditii de on dr.id = de.ramburs_id
            inner join decont_agent da on dr.decont_id = da.id
            LEFT JOIN exp_prelucrate ep on ep.expeditie = de.expeditie
            LEFT JOIN clienti cle on cle.cod_cl = ep.expeditor_id
            LEFT JOIN agenti ag on ag.cod_ag = dr.agent_id
            where {$cond} and DATE(da.data) = '{$this->today}'
            and da.centru_id = {$centru_id}
            and dr.anulata = 0 and dr.transaction_id = 0
            ORDER BY {$sidx} {$sord} LIMIT {$start},{$limit}";

        //error_log($query);
        
        $sql = $this->db->QFetchRowArray($query);
        if (!empty($sql)) {
            foreach ($sql as $key => $row) {
                $responce->rows[$key]['id'] = $row['id'];
                $responce->rows[$key]['cell'] = array(
                    $row['expeditie'],
                    $row['ch_ramburs'],
                    number_format($row['valoare'], 2, '.', ''),
                    $row['tip_plata'],
                    $row['dataInc'],
                    $row['expeditor'],
                    $row['agent'],
                );
            }
        }

        $responce->page = $page; 
        $responce->total = $total_pages; 
        $responce->records = $count;
        $responce->userdata['awb'] = $count;
        $responce->userdata['valoare'] = number_format($result_count['total_valoare'], 2, '.', '');
        
        return json_encode($responce);
    }

    function JSON_FinanciarCentru_CashNeDecontate($m_centru_id = 0) {
        $responce = new StdClass();
        $responce->records = 0;
        $today = date('Y-m-d');
        $centru_id = ($this->user_profile == 10 && $m_centru_id > 0) ? $m_centru_id : $this->centru_id;

        $sidx = trim($this->sanitize(empty($_GET['sidx']) ? 2 : $_GET['sidx']));
        $sord = trim($this->sanitize($_GET['sord'] ?? 'desc'));

        $having = "HAVING 1 = 1";
        //start generare conditie
        $searchOn = $this->Strip($_GET['_search'] ?? '');
        if ($searchOn == 'true') {
            $searchstr = $this->Strip($_GET['filters'] ?? '');
            $having .= $this->constructWhere($searchstr);
        }

        $query_df = "
            select df.id, df.data as dataInc, df.serie as fctChit, df.suma as valoare, 
            group_concat(de.expeditie) as awbs, df.operatiune, 1 as tip, ag.nume_ag as agent
            FROM decont_facturi df
            inner join agenti ag on ag.cod_ag = df.agent_id
            LEFT JOIN decont_expeditii de on df.id = de.factura_id
            where df.decont_id = 0 and df.anulata = 0 and ag.cod_centru = {$centru_id}
            and df.transaction_id = 0
            group by df.id
            {$having}
        ";
        $query_dr = "
            select dr.id, dr.dataInc, dr.ch_ramburs as fctChit, dr.ramburs as valoare, 
            group_concat(de.expeditie) as awbs, 2 as operatiune, 2 as tip, ag.nume_ag as agent
            FROM decont_rbs dr
            inner join agenti ag on ag.cod_ag = dr.agent_id
            LEFT JOIN decont_expeditii de on dr.id = de.ramburs_id
            where dr.decont_id = 0 and dr.anulata = 0 and ag.cod_centru = {$centru_id}
            and dr.transaction_id = 0
            group by dr.id
            {$having}
        ";
        $query_cf = "
            select cf.id, cf.dataInc, cf.ch_bon as fctChit, cf.suma as valoare, 
            cf.descriere as awbs, 3 as operatiune, 3 as tip, ag.nume_ag as agent
            FROM decont_chitante cf
            inner join agenti ag on ag.cod_ag = cf.agent_id
            where cf.decont_id = 0 and cf.anulata = 0 and ag.cod_centru = {$centru_id}
            and cf.transaction_id = 0
            {$having}
        ";

        $query = "
            ({$query_df})
            UNION
            ({$query_dr})
            UNION
            ({$query_cf})
            ORDER BY {$sidx} {$sord}
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
                    $row['awbs'],
                    $row['tip'],
                    $row['operatiune'],
                    $row['dataInc'],
                    $row['agent'],
                );
            }
        }

        $responce->records = $count;
        $responce->userdata['fctChit'] = $count;
        $responce->userdata['valoare'] = number_format($total_valoare, 2, '.', '');
        
        return json_encode($responce);
    }

    function JSON_FinanciarCentru_FacturiCashDecontate($m_centru_id = 0) {
        $responce = new StdClass();
        $responce->records = 0;
        $today = date('Y-m-d');
        $centru_id = ($this->user_profile == 10 && $m_centru_id > 0) ? $m_centru_id : $this->centru_id;

        $page = intval($_GET['page'] ?? 1);
		$limit = intval($_GET['rows'] ?? 50);
		$sidx = trim($this->sanitize(empty($_GET['sidx']) ? 1 : $_GET['sidx']));
		$sord = trim($this->sanitize($_GET['sord'] ?? 'asc'));

        $cond = "1 = 1";
        //start generare conditie
        $searchOn = $this->Strip($_GET['_search'] ?? '');
        if ($searchOn == 'true') {
            $searchstr = $this->Strip($_GET['filters'] ?? '');
            $cond .= $this->constructWhere($searchstr);
        }

        $query_count="SELECT COUNT(df.id) as nr, sum(df.suma) as total_valoare          
            FROM decont_facturi df
            inner join decont_agent da on df.decont_id = da.id
            LEFT JOIN agenti ag on ag.cod_ag = df.agent_id
            where {$cond} and DATE(da.data) = '{$this->today}'
            and da.centru_id = {$centru_id}
            and df.anulata = 0 and df.transaction_id = 0";
            
        $result_count = $this->db->QFetchArray($query_count);
        $count = !empty($result_count['nr']) ? $result_count['nr'] : 0;

        $total_pages = $count > 0 ? ceil($count/$limit) : 0;
        if ($page > $total_pages) $page = $total_pages;
        $start = $limit * $page - $limit;
        if ($start < 0) $start = 0;

        if($count == 0) {
            $responce->page = $page; 
            $responce->total = $total_pages; 
            $responce->records = $count;
            $responce->userdata['fct'] = 0;
            $responce->userdata['valoare'] = number_format(0, 2, '.', '');
            return json_encode($responce);
        }

        $query = "
            select df.id, df.data as dataInc, df.serie, df.suma,
            group_concat(de.expeditie) as awbs, df.operatiune, ag.nume_ag
            FROM decont_facturi df
            inner join decont_agent da on df.decont_id = da.id
            LEFT JOIN decont_expeditii de on df.id = de.factura_id
            LEFT JOIN agenti ag on ag.cod_ag = df.agent_id
            where {$cond} and DATE(da.data) = '{$this->today}'
            and da.centru_id = {$centru_id}
            and df.anulata = 0 and df.transaction_id = 0
            group by df.id
            ORDER BY {$sidx} {$sord} LIMIT {$start},{$limit}
        ";

        //$this->log("debug", BackEnd::APP_LOG_FILE);
        $sql = $this->db->QFetchRowArray($query);
        if (!empty($sql)) {
            $count = count($sql);
            foreach ($sql as $key => $row) {
                $responce->rows[$key]['id'] = $row['id'];
                $responce->rows[$key]['cell'] = array(
                    $row['serie'],
                    number_format($row['suma'], 2, '.', ''),
                    $row['awbs'],
                    $row['operatiune'],
                    $row['dataInc'],
                    $row['nume_ag'],
                );
            }
        }

        $responce->page = $page; 
        $responce->total = $total_pages; 
        $responce->records = $count;
        $responce->userdata['serie'] = $count;
        $responce->userdata['valoare'] = number_format($result_count['total_valoare'], 2, '.', '');
        
        return json_encode($responce);
    }

    function JSON_FinanciarCentru_ClientCtrCashDecontate($m_centru_id = 0) {
        $responce = new StdClass();
        $responce->records = 0;
        $today = date('Y-m-d');
        $centru_id = ($this->user_profile == 10 && $m_centru_id > 0) ? $m_centru_id : $this->centru_id;

        $page = intval($_GET['page'] ?? 1);
		$limit = intval($_GET['rows'] ?? 50);
		$sidx = trim($this->sanitize(empty($_GET['sidx']) ? 1 : $_GET['sidx']));
		$sord = trim($this->sanitize($_GET['sord'] ?? 'asc'));

        $cond = "1 = 1";
        //start generare conditie
        $searchOn = $this->Strip($_GET['_search'] ?? '');
        if ($searchOn == 'true') {
            $searchstr = $this->Strip($_GET['filters'] ?? '');
            $cond .= $this->constructWhere($searchstr);
        }

        $query_count="SELECT COUNT(cf.id) as nr, sum(cf.suma) as total_valoare          
            FROM decont_chitante cf
            inner join decont_agent da on cf.decont_id = da.id
            LEFT JOIN agenti ag on ag.cod_ag = cf.agent_id
            where {$cond} and DATE(da.data) = '{$this->today}'
            and da.centru_id = {$centru_id}
            and cf.anulata = 0 and cf.transaction_id = 0";
            
        $result_count = $this->db->QFetchArray($query_count);
        $count = !empty($result_count['nr']) ? $result_count['nr'] : 0;

        $total_pages = $count > 0 ? ceil($count/$limit) : 0;
        if ($page > $total_pages) $page = $total_pages;
        $start = $limit * $page - $limit;
        if ($start < 0) $start = 0;

        if($count == 0) {
            $responce->page = $page; 
            $responce->total = $total_pages; 
            $responce->records = $count;
            $responce->userdata['fct'] = 0;
            $responce->userdata['valoare'] = number_format(0, 2, '.', '');
            return json_encode($responce);
        }

        $query = "select cf.id, cf.dataInc, cf.ch_bon, cf.suma, cf.descriere, ag.nume_ag
            FROM decont_chitante cf
            inner join decont_agent da on cf.decont_id = da.id
            LEFT JOIN agenti ag on ag.cod_ag = cf.agent_id
            where {$cond} and DATE(da.data) = '{$this->today}'
            and da.centru_id = {$centru_id}
            and cf.anulata = 0 and cf.transaction_id = 0
            ORDER BY {$sidx} {$sord} LIMIT {$start},{$limit}
        ";

        //$this->log("debug", BackEnd::APP_LOG_FILE);
        $sql = $this->db->QFetchRowArray($query);
        if (!empty($sql)) {
            $count = count($sql);
            foreach ($sql as $key => $row) {
                $responce->rows[$key]['id'] = $row['id'];
                $responce->rows[$key]['cell'] = array(
                    $row['ch_bon'],
                    number_format($row['suma'], 2, '.', ''),
                    $row['descriere'],
                    $row['dataInc'],
                    $row['nume_ag'],
                );
            }
        }

        $responce->page = $page; 
        $responce->total = $total_pages; 
        $responce->records = $count;
        $responce->userdata['serie'] = $count;
        $responce->userdata['valoare'] = number_format($result_count['total_valoare'], 2, '.', '');
        
        return json_encode($responce);
    }

    function JSON_FinanciarCentru_IncasariCard($m_centru_id = 0) {
        $responce = new StdClass();
        $responce->records = 0;
        $today = date('Y-m-d');
        $centru_id = ($this->user_profile == 10 && $m_centru_id > 0) ? $m_centru_id : $this->centru_id;

        $sidx = trim($this->sanitize(empty($_GET['sidx']) ? 2 : $_GET['sidx']));
        $sord = trim($this->sanitize($_GET['sord'] ?? 'desc'));

        $having = "HAVING 1 = 1";
        //start generare conditie
        $searchOn = $this->Strip($_GET['_search'] ?? '');
        if ($searchOn == 'true') {
            $searchstr = $this->Strip($_GET['filters'] ?? '');
            $having .= $this->constructWhere($searchstr);
        }

        $query_df = "
            select df.id, df.data as dataInc, df.serie as fctChit, df.suma as valoare, dt.transactionId,
            group_concat(de.expeditie) as awbs, df.operatiune, 1 as tip, ag.nume_ag as agent
            FROM decont_facturi df
            inner join agenti ag on ag.cod_ag = df.agent_id
            inner join decont_transactions dt on dt.id = df.transaction_id
            LEFT JOIN decont_expeditii de on df.id = de.factura_id
            where df.anulata = 0 and ag.cod_centru = {$centru_id} and DATE(df.data) = '{$this->today}'
            group by df.id
            {$having}
        ";
        $query_dr = "
            select dr.id, dr.dataInc, dr.ch_ramburs as fctChit, dr.ramburs as valoare, dt.transactionId,
            group_concat(de.expeditie) as awbs, 2 as operatiune, 2 as tip, ag.nume_ag as agent
            FROM decont_rbs dr
            inner join agenti ag on ag.cod_ag = dr.agent_id
            inner join decont_transactions dt on dt.id = dr.transaction_id
            LEFT JOIN decont_expeditii de on dr.id = de.ramburs_id
            where dr.decont_id = 0 and dr.anulata = 0 and ag.cod_centru = {$centru_id} and DATE(dr.dataInc) = '{$this->today}'
            group by dr.id
            {$having}
        ";
        $query_cf = "
            select cf.id, cf.dataInc, cf.ch_bon as fctChit, cf.suma as valoare, dt.transactionId,
            cf.descriere as awbs, 3 as operatiune, 3 as tip, ag.nume_ag as agent
            FROM decont_chitante cf
            inner join agenti ag on ag.cod_ag = cf.agent_id
            inner join decont_transactions dt on dt.id = cf.transaction_id
            where cf.decont_id = 0 and cf.anulata = 0 and ag.cod_centru = {$centru_id} and DATE(cf.dataInc) = '{$this->today}'
            {$having}
        ";

        $query = "
            ({$query_df})
            UNION
            ({$query_dr})
            UNION
            ({$query_cf})
            ORDER BY {$sidx} {$sord}
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
                    $row['transactionId'],
                    number_format($row['valoare'], 2, '.', ''),
                    $row['awbs'],
                    $row['tip'],
                    $row['operatiune'],
                    $row['dataInc'],
                    $row['agent'],
                );
            }
        }

        $responce->records = $count;
        $responce->userdata['fctChit'] = $count;
        $responce->userdata['valoare'] = number_format($total_valoare, 2, '.', '');
        
        return json_encode($responce);
    }

    function JSON_FinanciarCentru_RbsCashAprobate() {
        $responce = new StdClass();
        $responce->records = 0;
        $today = date('Y-m-d');

        $cond_centre = " = {$this->centru_id}";
        //if has children centres include them also
        if($this->financiar && count($this->children_centru_ids) > 0){
            $centru_ids = $this->children_centru_ids;
            $centru_ids[] = $this->centru_id;
            $centru_ids = '('.implode(',', $centru_ids).')';
            $cond_centre = " IN {$centru_ids}";
        }

        $page = intval($_GET['page'] ?? 1);
		$limit = intval($_GET['rows'] ?? 50);
		$sidx = trim($this->sanitize(empty($_GET['sidx']) ? 1 : $_GET['sidx']));
		$sord = trim($this->sanitize($_GET['sord'] ?? 'asc'));

        $cond = "1 = 1";
        //start generare conditie
        $searchOn = $this->Strip($_GET['_search'] ?? '');
        if ($searchOn == 'true') {
            $searchstr = $this->Strip($_GET['filters'] ?? '');
            $cond .= $this->constructWhere($searchstr);
        }

        $query_count="SELECT COUNT(ep.cod_expeditie) as nr, sum(ep.ramburs) as total_valoare          
            FROM exp_prelucrate ep
            LEFT JOIN clienti cle on cle.cod_cl = ep.expeditor_id
            LEFT JOIN zones clez ON clez.id = cle.zona_id
            LEFT JOIN centre clec on clec.id = clez.centru_id
            LEFT JOIN localitati lce ON lce.cod_lc = cle.cod_lc
            LEFT JOIN centre cee ON cee.id = lce.cod_centru
            LEFT JOIN clienti mst on cle.master = mst.cod_cl
            LEFT JOIN exp_prelucrate rbs
                ON rbs.referire = ep.expeditie AND rbs.tip_exp = 3 and rbs.anulata = 0
            LEFT JOIN exp_prelucrate rtn
                ON rtn.referire = ep.expeditie AND rtn.tip_exp = 5 and rtn.anulata = 0
            WHERE {$cond} and ep.tip_exp = 0 and ep.anulata = 0 
            and ep.ramburs > 0 and ep.tip_plata = 0 and ep.status_ramburs = 30
            and rbs.cod_expeditie IS NULL and rtn.cod_expeditie IS NULL
            and IF(cle.zona_id > 0 and clec.id > 0, clec.id, cee.id) {$cond_centre}";
            
        //error_log($cond_centre);
        $result_count = $this->db->QFetchArray($query_count);
        $count = !empty($result_count['nr']) ? $result_count['nr'] : 0;

        $total_pages = $count > 0 ? ceil($count/$limit) : 0;
        if ($page > $total_pages) $page = $total_pages;
        $start = $limit * $page - $limit;
        if ($start < 0) $start = 0;

        if($count == 0) {
            $responce->page = $page; 
            $responce->total = $total_pages; 
            $responce->records = $count;
            $responce->userdata['awb'] = 0;
            $responce->userdata['valoare'] = number_format(0, 2, '.', '');
            return json_encode($responce);
        }

        $query = "SELECT ep.cod_expeditie, ep.expeditie, ep.data_expeditie,
            ep.expeditor_id, ep.expeditor_contact, ep.expeditor_telefon,
            ep.destinatar_id, ep.destinatar_contact, ep.destinatar_telefon, ep.platitor_id,
            ep.ramburs, ep.tip_plata, ep.ramburs_procent, ep.valoare_asigurata, ep.procent_asigurare,
            ep.val_greutate, ep.val_km, ep.val_asig, ep.valoare_expeditie, ep.valoare_totala_expeditie, 
            ep.tva, ep.procTva, ep.mod_plata, ep.moneda,
            ep.primitor, ep.operatiune, ep.data_op,
            ep.observatii, ep.detalii_doc,
            cle.nume as expeditor, cle.cc as expeditor_cc, cle.mod_plata as expeditor_mod_plata, 
            cle.tarif as expeditor_contract, cle.adresa as expeditor_adresa,
            lce.nume_lc as expeditor_localitate,
            IF(cle.zona_id > 0 and clec.id > 0, clec.id, cee.id) as expeditor_centru_id,
            IF(cle.zona_id > 0 and clec.id > 0, clec.nume, cee.nume) as expeditor_centru, 
            IF(cle.zona_id > 0 and clec.id > 0, clec.label, cee.label) as expeditor_centru_cod,
            cle.icc as rbs_individual,
            if(mst.nume is NULL , cle.nume, mst.nume) as mst_financiar_nume, mst.nume_societate,
            if(cle.icc > 0 or mst.rbs_days is NULL , cle.rbs_days, mst.rbs_days) as master_rbs_days,
            if(cle.icc > 0 , cle.cod_fiscal, mst.cod_fiscal) as cod_fiscal,
            if(cle.icc > 0 , cle.cont_rbs, mst.cont_rbs) as cont_rbs,
            if(ie.cod_ist > 0,1,0) as livrat
            FROM exp_prelucrate ep
            LEFT JOIN clienti cle on cle.cod_cl = ep.expeditor_id
            LEFT JOIN zones clez ON clez.id = cle.zona_id
            LEFT JOIN centre clec on clec.id = clez.centru_id
            LEFT JOIN localitati lce ON lce.cod_lc = cle.cod_lc
            LEFT JOIN centre cee ON cee.id = lce.cod_centru
            LEFT JOIN clienti mst on cle.master = mst.cod_cl
            LEFT JOIN ist_exp ie ON ie.cod_ist = (
                SELECT
                    MAX(i.cod_ist) as cod_ist
                    FROM ist_exp i
                    WHERE i.cod_exp = ep.cod_expeditie AND i.OPERATIUNE = 3
            )
            LEFT JOIN exp_prelucrate rbs
                ON rbs.referire = ep.expeditie AND rbs.tip_exp = 3 and rbs.anulata = 0
            LEFT JOIN exp_prelucrate rtn
                ON rtn.referire = ep.expeditie AND rtn.tip_exp = 5 and rtn.anulata = 0
            WHERE {$cond} and ep.tip_exp = 0 and ep.anulata = 0 and 
            ep.ramburs > 0 and ep.tip_plata = 0 and ep.status_ramburs = 30
            and rbs.cod_expeditie IS NULL and rtn.cod_expeditie IS NULL
            and IF(cle.zona_id > 0 and clec.id > 0, clec.id, cee.id) {$cond_centre}
            GROUP BY ep.cod_expeditie
            ORDER BY {$sidx} {$sord} LIMIT {$start},{$limit}
        ";

        $sql = $this->db->QFetchRowArray($query);
        if (!empty($sql)) {
            foreach ($sql as $key => $row) {
                $responce->rows[$key]['id'] = $row['cod_expeditie'];
                $responce->rows[$key]['cell'] = array(
                    $row['expeditie'],
                    $row['data_expeditie'],
                    $row['expeditor'],
                    $row['cod_fiscal'],
                    $row['expeditor_localitate'],
                    number_format($row['ramburs'], 2, '.', ''),
                    $row['tip_plata'],
                );
            }
        }

        $responce->page = $page; 
        $responce->total = $total_pages; 
        $responce->records = $count;
        $responce->userdata['awb'] = $count;
        $responce->userdata['valoare'] = number_format($result_count['total_valoare'], 2, '.', '');

        return json_encode($responce);
    }

    function FinanciarCentru_CreareRbsCash() {
        $m_centru_id = intval($_POST['centru_id'] ?? 0);
        $isChildCentru = $this->financiar && $m_centru_id > 0 && in_array($m_centru_id, $this->children_centru_ids);
        $centru_id = $isChildCentru ? $m_centru_id : $this->centru_id;
        $centru_nume = $this->children_centru_nume[$centru_id] ?? $this->centru_nume;

        if(false === $this->financiar && false === $this->mst_financiar) {
            return json_encode([
                'status' => 'error',
                'message' => "Generare RBS cash pentru centrul {$centru_nume} BLOCATA !"
            ]);
        }

        if(false === $this->financiar && true === $this->mst_financiar) {
            return json_encode([
                'status' => 'error',
                'message' => "Generare RBS cash pentru centrul {$centru_nume} se face in centrul {$this->mst_financiar_nume}!"
            ]);
        }

        //verifica casa master
        $decont_casa_master_sql = $this->db->QFetchArray("SELECT id, nr_rbs_generate, rbs_generate, casa_rbs
                FROM decont_casa 
                WHERE centru_id = {$this->centru_id} AND DATE(created_at) = '{$this->today}'");
        if(empty($decont_casa_master_sql) || $decont_casa_master_sql['casa_rbs'] <= 1) {
            return json_encode([
                'status' => 'error',
                'message' => "Casa RBS la centrul {$this->centru_nume} este goala! <br/>Nu poti crea AWB RBS pentru centrul {$centru_nume}!"
            ]);
        }

        //cauta aprobate pentru centru : copil or master
        $query_aprobate="SELECT cle.cod_cl as expeditor_id, cle.contact as expeditor_contact, cle.telefon as expeditor_telefon,
            group_concat(ep.expeditie) as initiale, count(ep.expeditie) as nr_rbs_aprobate, sum(ep.ramburs) as total_rbs_aprobate
            FROM exp_prelucrate ep
            LEFT JOIN clienti cle on cle.cod_cl = ep.expeditor_id
            LEFT JOIN zones clez ON clez.id = cle.zona_id
            LEFT JOIN localitati lce ON lce.cod_lc = cle.cod_lc
            LEFT JOIN exp_prelucrate rbs
                ON rbs.referire = ep.expeditie AND rbs.tip_exp = 3 and rbs.anulata = 0
            LEFT JOIN exp_prelucrate rtn
                ON rtn.referire = ep.expeditie AND rtn.tip_exp = 5 and rtn.anulata = 0
            WHERE ep.tip_exp = 0 and ep.anulata = 0 and ep.ramburs > 0 and ep.tip_plata = 0 and ep.status_ramburs = 30
            and rbs.cod_expeditie IS NULL and rtn.cod_expeditie IS NULL
            and IF(cle.zona_id > 0 and clez.centru_id > 0, clez.centru_id, lce.cod_centru) = {$centru_id}
            group by cle.cod_cl order by nr_rbs_aprobate desc";

        $result_aprobate = $this->db->QFetchRowArray($query_aprobate);
        if(empty($result_aprobate) || !is_array($result_aprobate) || count($result_aprobate) <= 0) {
            return json_encode([
                'status' => 'error',
                'message' => "Nu e nimic aprobat pentru centrul {$centru_nume}! Nu poti crea AWB RBS !"
            ]);
        }

        //generate rbs allways from casa master
        $generate = [];
        $errors = [];
        $total_rbs_generate = 0.00;
        if($isChildCentru)
            $total_rbs_generate = $this->creareRbsCashCopil($centru_id, $decont_casa_master_sql['casa_rbs'], $result_aprobate, $generate, $errors);
        else
            $total_rbs_generate = $this->creareRbsCashMaster($decont_casa_master_sql['id'], $decont_casa_master_sql['casa_rbs'], $decont_casa_master_sql['nr_rbs_generate'], $decont_casa_master_sql['rbs_generate'], $result_aprobate, $generate, $errors);
        
        if(count($generate) == 0) {
            return json_encode([
                'status' => 'error',
                'message' => "Suma din CASA centrului {$this->centru_nume} e prea mica!<br/>"
                    ."Nu s-au putut crea AWB RBS cash pentru centrul {$centru_nume} !"
            ]);
        }
        
        //update casa master
        $this->db->QueryUpdate('decont_casa', [
            'casa_rbs' => round($decont_casa_master_sql['casa_rbs'], 2),
        ], "id = {$decont_casa_master_sql['id']}");
        

        if(count($errors) > 0) {
            error_log("FinanciarCentru_CreareRbsCash : Expeditii initiale cu erori: ".implode(',', $errors));
        }
       
        return json_encode([
            'status' => 'success',
            'message' => count($generate) . " AWB RBS create cu succes pentru centrul {$centru_nume}!</br>pentru un total de " . number_format($total_rbs_generate, 2, '.', '') . " lei.<br/>"
                . (count($errors) > 0 ? count($errors).' <span class="ui-state-error">AWB RBS nu au putut fi create pentru expeditiile : '.implode(', ', $errors)."</span>" : ''),
            'casa_rbs' => number_format(round($decont_casa_master_sql['casa_rbs'], 2), 2, '.', ''),
        ]);
    }

    private function creareRbsCashMaster($casa_master_id, &$casa_master_rbs, $nr_rbs_generate, $total_rbs_generate, &$aprobate, &$generate, &$errors) {

        $total_master_rbs_generate = $this->creareRbsCash($casa_master_rbs, $aprobate, $generate, $errors);
        $total_rbs_generate += $total_master_rbs_generate;
        $nr_rbs_generate += count($generate);

        //update casa master
        $this->db->QueryUpdate('decont_casa', [
            'nr_rbs_generate' => $nr_rbs_generate,
            'rbs_generate' => round($total_rbs_generate, 2),
        ], "id = {$casa_master_id}");

        return $total_master_rbs_generate;
    }

    private function creareRbsCashCopil($centru_id, &$casa_master_rbs, &$aprobate, &$generate, &$errors) {
        //verifica casa copil
        //daca nu exista creeaza casa copil
        $decont_casa_copil_sql = $this->db->QFetchArray("SELECT id, nr_rbs_generate, rbs_generate
            FROM decont_casa 
            WHERE centru_id = {$centru_id} AND DATE(created_at) = '{$this->today}'");
        if(empty($decont_casa_copil_sql)) {
            $decont_casa_copil_id = $this->db->QueryInsert('decont_casa', [
                'created_at' => $this->today,
                'centru_id' => $centru_id,
                'user_id' => $this->user_id,
            ]);
            $trezorerie = self::transferTrezorerie($this->db, $centru_id, $this->user_id, $decont_casa_copil_id, 0, 0, $this->today);
            $decont_casa_copil_sql = [
                'id' => $decont_casa_copil_id, 
                'nr_rbs_generate' => 0, 
                'rbs_generate' => 0,
            ];
        }
        $nr_rbs_generate = $decont_casa_copil_sql['nr_rbs_generate'];
        $total_rbs_generate = $decont_casa_copil_sql['rbs_generate'];
        $casa_copil_id = $decont_casa_copil_sql['id'];

        $total_copil_rbs_generate = $this->creareRbsCash($casa_master_rbs, $aprobate, $generate, $errors);
        $total_rbs_generate += $total_copil_rbs_generate;
        $nr_rbs_generate += count($generate);

        $this->db->QueryUpdate('decont_casa', [
            'nr_rbs_generate' => $nr_rbs_generate,
            'rbs_generate' => round($total_rbs_generate, 2),
        ], "id = {$casa_copil_id}");

        return $total_copil_rbs_generate;
    }

    private function creareRbsCash(&$casa_master_rbs, &$aprobate, &$awb_rbs_generate, &$awb_initiale_error) {
        $total_rbs_generate = 0.00;
        
        //foreach client rbs aprobate
        foreach($aprobate as $row) {
            $expeditor_id_row = $row['expeditor_id'] ?? 0;
            $total_rbs_aprobate_row = $row['total_rbs_aprobate'] ?? 0.00;
            $awb_initiale_rbs_aprobate_row = $row['initiale'] ?? 0;
            $awb_rbs_generate_row = [];
            $total_rbs_generate_row = 0.00;
            //TODO: check if total_ramburs is greater than casa
            if($total_rbs_aprobate_row > $casa_master_rbs) {
                error_log("FinanciarCentru_CreareRbsCash : Total ramburs > casa : {$total_rbs_aprobate_row} - Casa: {$casa_master_rbs}");
                continue;
            }
            //explode expeditii
            $awb_initiale_rbs_aprobate_row = explode(',', $awb_initiale_rbs_aprobate_row);
            //to int
            $awb_initiale_rbs_aprobate_row = array_map('intval', $awb_initiale_rbs_aprobate_row);
            if(count($awb_initiale_rbs_aprobate_row) > 1) {
                //unique and sort
                $awb_initiale_rbs_aprobate_row = array_unique($awb_initiale_rbs_aprobate_row);
                sort($awb_initiale_rbs_aprobate_row);
            }
            //create awb ramburs
            foreach($awb_initiale_rbs_aprobate_row as $initiala){
                $awb_rbs = $this->creazaNotaRamburs($initiala, true);
                //create expeditie rbs
                if(false !== $awb_rbs && is_array($awb_rbs) && isset($awb_rbs['expeditie']) && isset($awb_rbs['ramburs'])) {
                    $awb_rbs_generate_row[] = $awb_rbs['expeditie'];
                    //update counters
                    $total_rbs_generate_row += $awb_rbs['ramburs'];
                    $casa_master_rbs -= $awb_rbs['ramburs'];
                }
                else {
                    $awb_initiale_error[] = $initiala;
                }
            }
            $total_rbs_generate += $total_rbs_generate_row;
            $awb_rbs_generate = array_merge($awb_rbs_generate, $awb_rbs_generate_row);
            //borderou rbs cash
            if(count($awb_rbs_generate_row) >= parent::MIN_BO_RBS_CASH) {
                //consolidare
                $this->creazaAwbBorderouRbsCash($expeditor_id_row, $awb_rbs_generate_row, $total_rbs_generate_row);
            }
            if(abs($total_rbs_aprobate_row - $total_rbs_generate_row) > 0.5) {
                error_log("FinanciarCentru_CreareRbsCash : Diferenta ramburs client {$expeditor_id_row} : Total aprobate {$total_rbs_aprobate_row} - Total create {$total_rbs_generate_row}");
                error_log("FinanciarCentru_CreareRbsCash : Expeditii error : ".print_r($awb_initiale_error, true));
            }
        }
        return $total_rbs_generate;
    }

    private function creazaAwbBorderouRbsCash($destinatar_id, $awb_rbs_generate_row, $valoare_asigurata){
        $expeditor_id = 84742; //DSC Express Logistics
        $procTva = $this->procTva;

		$exp_bo_rbs_cash = ExpeditieDto::sqlBorderouRbsCash($expeditor_id, $this->user_nume, $this->user_telefon, $destinatar_id, $valoare_asigurata, $procTva);
		$exp_bo_rbs_cash['operator_id'] = $this->user_id;
		$exp_bo_rbs_cash['operatiune'] = "Colectata";
        $exp_bo_rbs_cash['created_at'] = date('Y-m-d H:i:s');
        $exp_bo_rbs_cash['created_by'] = $this->user_id;

        try {
            $this->db->BeginTransaction();
            $exp_bo_rbs_cash['expeditie'] = $this->GenerareNrExpeditie();
		    $cod_expeditie = $this->db->QueryInsert($this->tables['exp_prelucrate'], $exp_bo_rbs_cash);

            //update ref_bo la rbs create
            $this->db->QueryUpdate('exp_prelucrate', 
                ['ref_bo' => $exp_bo_rbs_cash['expeditie']], 
                "expeditie in (".implode(',', $awb_rbs_generate_row).")"
            );

            //istoric expeditii
		    $id_ist = $this->insertIstExp($cod_expeditie, 1);
            $this->db->commitTransaction();
        } catch (Exception $e) {
            $this->db->rollbackTransaction();
            error_log("FinanciarCentru : creazaAwbBorderouRbsCash : Eroare : ".$e->getMessage());
            return false;
        }
		
		return $exp_bo_rbs_cash['expeditie'];
	}

    function FinanciarCentru_PrintareRbsCash() {
        $m_centru_id = intval($_POST['centru_id'] ?? 0);
        $isChildCentru = $this->financiar && $m_centru_id > 0 && in_array($m_centru_id, $this->children_centru_ids);
        $centru_id = $isChildCentru ? $m_centru_id : $this->centru_id;
        $centru_nume = $this->children_centru_nume[$centru_id] ?? $this->centru_nume;
        $centru_cod = $this->children_centru_cod[$centru_id] ?? $this->centru_cod;

        $data = DateTime::createFromFormat('Y-m-d',($_POST['created_at'] ?? ''));
        if($data instanceof DateTime) {
            $data = $data->format('Y-m-d');
        } else {
            $data = $this->today;
        }

        //if old data and is master and has children -> print for children also
        $cond_centre = " = {$centru_id}";
        if($centru_id == $this->centru_id && $this->financiar && count($this->children_centru_ids) > 0 
            && isset($_POST['created_at'])){
            $centru_ids = $this->children_centru_ids;
            $centru_ids[] = $this->centru_id;
            $centru_ids = '('.implode(',', $centru_ids).')';
            $cond_centre = " IN {$centru_ids}";
            $centru_cod = array_merge([$this->centru_cod], $this->children_centru_cod);
            $centru_cod = implode("_", $centru_cod);
            //error_log($centru_cod);
        }

        require_once "NewAwbPdf.php";
        $filename = "AWB-RBS-CASH_{$centru_cod}_{$data}.pdf";
        $pdf = new NewAwbPdf();
        $pdf->SetTitle('NT-printare_multipla');
        $pdf->SetSubject('NT-printare_multipla');

        $query_generate="SELECT rbs.expeditie, rbs.ref_bo
            FROM exp_prelucrate rbs
            inner join exp_prelucrate ep on ep.expeditie = rbs.referire
            LEFT JOIN clienti cld on cld.cod_cl = rbs.destinatar_id
            LEFT JOIN zones cldz ON cldz.id = cld.zona_id
            LEFT JOIN localitati lcd ON lcd.cod_lc = cld.cod_lc
            WHERE rbs.tip_exp = 3 and rbs.anulata = 0 and rbs.valoare_asigurata > 0 and rbs.tip_plata = 0 
            and ep.tip_exp = 0 and ep.anulata = 0 and ep.status_ramburs in (1,4)
            and IF(cld.zona_id > 0 and cldz.centru_id > 0, cldz.centru_id, lcd.cod_centru) {$cond_centre}
            and rbs.data_expeditie = '{$data}'
            order by cld.nume asc, rbs.expeditie asc";
        
		$result_generate = $this->db->QFetchRowArray($query_generate);
        if(empty($result_generate) || !is_array($result_generate) || count($result_generate) <= 0) {
            $pdf->AddPage();
            $pdf->Output($filename,'D');
            exit;
        }
        
        $bo_rbs_cash = [];
        foreach($result_generate as $key=>$row) {
            if($row['ref_bo'] > 0) {
                $bo_rbs_cash[] = $row['ref_bo'];
                continue; //skip if ref_bo is set
            } else {
                //printare ramburs
                $awb = $row['expeditie'];
            }
            if(false === ($exp = $this->GetValues($awb))) continue;
            $vars = ExpeditieDto::sqlExpToPdf($exp);
            $vars['destinatar_nume'] = strtoupper(htmlspecialchars_decode(strtolower($vars['destinatar_nume']), ENT_QUOTES));
            $pdf->setVars($vars);

            $pdf->AddPage();
            $pdf->makeHalfFirstPage(0, 0);
            $pdf->makeDashedLine('H');
            $pdf->makeHalfFirstPage(0, 148);

            $pdf->setVars(null);
        }

        $bo_rbs_cash = array_unique($bo_rbs_cash);
        //error_log(print_r($bo_rbs_cash, true));
        if(count($bo_rbs_cash) > 0) {
            $query_rbs_cash="SELECT rbs.expeditie, rbs.referire as initiala,
            rbs.valoare_asigurata, rbs.tip_plata,
            cle.nume as expeditor_nume, lce.nume_lc as expeditor_localitate,
            lce.cod_jd as expeditor_judet
            FROM exp_prelucrate rbs
            LEFT JOIN clienti cle on cle.cod_cl = rbs.expeditor_id
            LEFT JOIN localitati lce ON lce.cod_lc = cle.cod_lc
            WHERE rbs.tip_exp = 3 and rbs.anulata = 0 and rbs.valoare_asigurata > 0 and rbs.tip_plata = 0
            and rbs.ref_bo = :ref_bo
            order by rbs.expeditie asc";

            foreach($bo_rbs_cash as $key=>$ref_bo) {
                if(false === ($exp = $this->GetValues($ref_bo))) continue;
                $vars = ExpeditieDto::sqlExpToPdf($exp);
                $vars['destinatar_nume'] = strtoupper(htmlspecialchars_decode(strtolower($vars['destinatar_nume']), ENT_QUOTES));
                
                $pdf->setVars($vars);

                $pdf->AddPage();
                $pdf->makeHalfFirstPage(0, 0);
                $pdf->makeDashedLine('H');
                $pdf->makeHalfFirstPage(0, 148);

                $pdf->toogleHeaderFooterAutoBrake(true);
                $result_rbs_cash = $this->db->QFetchRowArray($query_rbs_cash, ['ref_bo' => $ref_bo]);
                if(empty($result_rbs_cash) || !is_array($result_rbs_cash) || count($result_rbs_cash) <= 0) {
                    //error_log("FinanciarCentru_PrintareRbsCash : Nu e nimic generat pentru ref_bo: {$ref_bo}");
                    continue;
                }
                $vars['nr_awb_rbs_cash'] = count($result_rbs_cash);
                $pdf->setVars($vars);
                //print borderou rbs cash
                $pdf->AddPage();
                $pdf->makeBoRbsCashTh();
                foreach($result_rbs_cash as $key=>$rbs) {
                    $pdf->makeBoRbsCashTr($rbs['expeditie'], $rbs['initiala'], $rbs['valoare_asigurata'], $rbs['tip_plata'],
                        $rbs['expeditor_nume'], $rbs['expeditor_localitate'], $rbs['expeditor_judet']);
                }                
                $pdf->toogleHeaderFooterAutoBrake(false);
                $pdf->setVars(null);
            }
        }
        //$pdf->lastPage();
        $pdf->Output($filename,'D');
        exit;
    }

    function FinanciarCentru_TransferCasa() {
        //tip 1-transfer rbs, 2-transfer fa_ch
        $responce = new StdClass();
        $responce->status = 'error';
        $responce->message = 'Eroare la transferul de casa !';
        $centru_id = intval($_POST['centru_id'] ?? 0);
        $tip = intval($_POST['tip'] ?? 0); //1-transfer rbs, 2-transfer fa_ch
        $isChildCentru = $this->financiar && $centru_id > 0 && in_array($centru_id, $this->children_centru_ids);

        if(!in_array($tip, [1, 2])) {
            $responce->message = 'Casa gresita !'; 
            return json_encode($responce);
        }

        if($isChildCentru === false) {
            $responce->message = 'Nu ai dreptul sa transferi casa din acest centru !'; 
            return json_encode($responce);
        }

        if($centru_id <= 0 || $centru_id == $this->centru_id) {
            $responce->message = 'Alege un centru valid !'; 
            return json_encode($responce);
        }
        
        $centru_nume = $this->children_centru_nume[$centru_id] ?? "unknown";
        $centru_cod = $this->children_centru_cod[$centru_id] ?? "unknown";

        $this->db->BeginTransaction();
        try {

            $decont_casa_child_sql = $this->db->QFetchArray("SELECT id, casa_rbs, casa_fa_ch
                    FROM decont_casa 
                    WHERE centru_id = {$centru_id} AND DATE(created_at) = '{$this->today}'");
            if(empty($decont_casa_child_sql)) {
                $this->db->rollbackTransaction();
                $responce->message = "Nimic de transferat de la centrul {$centru_nume}!";
                return json_encode($responce);
            }
            if($tip == 1 && $decont_casa_child_sql['casa_rbs'] <= 1) {
                $this->db->rollbackTransaction();
                $responce->message = "Casa RBS este goala la centrul {$centru_nume}!";
                return json_encode($responce);
            }
            if($tip == 2 && $decont_casa_child_sql['casa_fa_ch'] <= 1) {
                $this->db->rollbackTransaction();
                $responce->message = "Casa Facturi/Chitante este goala la centrul {$centru_nume}!";
                return json_encode($responce);
            }
            $decont_casa_child_id = $decont_casa_child_sql['id'];

            $decont_casa_master_sql = $this->db->QFetchArray("SELECT id, casa_rbs, casa_fa_ch
                    FROM decont_casa 
                    WHERE centru_id = {$this->centru_id} AND DATE(created_at) = '{$this->today}'");
            if(empty($decont_casa_master_sql)) {
                $decont_casa_master_id = $this->db->QueryInsert('decont_casa', [
                    'created_at' => $this->today,
                    'centru_id' => $this->centru_id,
                    'user_id' => $this->user_id,
                ]);
                $trezorerie = self::transferTrezorerie($this->db, $this->centru_id, $this->user_id, $decont_casa_master_id, 0, 0, $this->today);
                $decont_casa_master_sql = ['id' => $decont_casa_master_id, 'casa_rbs' => $trezorerie['rbs'], 'casa_fa_ch' => $trezorerie['fa_ch']];
            }

            //update child
            $this->db->QueryUpdate('decont_casa', [
                ($tip == 1 ? 'casa_rbs' : 'casa_fa_ch') => 0.00,
            ], "id = {$decont_casa_child_id}");

            //update casa master
            $casa_rbs_master = $decont_casa_master_sql['casa_rbs'] + ($tip == 1 ? $decont_casa_child_sql['casa_rbs'] : 0);
            $casa_fa_ch_master = $decont_casa_master_sql['casa_fa_ch'] + ($tip == 1 ? 0 : $decont_casa_child_sql['casa_fa_ch']);
            $this->db->QueryUpdate('decont_casa', 
                $tip == 1 ?
                ['casa_rbs' => round($casa_rbs_master, 2)] :
                ['casa_fa_ch' => round($casa_fa_ch_master, 2)],
                "id = {$decont_casa_master_sql['id']}");

            //insert decont_casa_transfer
            $this->db->QueryInsert('decont_casa_transfer', [
                'from_decont_casa_id' => $decont_casa_child_id,
                'to_decont_casa_id' => $decont_casa_master_sql['id'],
                'amount' => ($tip == 1 ? $decont_casa_child_sql['casa_rbs'] : $decont_casa_child_sql['casa_fa_ch']),
                'tip' => $tip, //transfer casa
                'user_id' => $this->user_id,
            ]);
        }
        catch (Exception $e) {
            $this->db->rollbackTransaction();
            error_log("FinanciarCentru_TransferCasa : Eroare GRAVA : <br/>".$e->getMessage());
            $responce->message = 'Eroare GRAVA la transferul de casa !';
            return json_encode($responce);
        }
        $this->db->commitTransaction();
        $responce->status = 'success';
        $responce->message = "A fost transferata suma de ".number_format(($tip == 1 ? $decont_casa_child_sql['casa_rbs'] : $decont_casa_child_sql['casa_fa_ch']), 2, '.', '')." 
            de la centrul {$centru_nume} !";
        $responce->centru_id = $centru_id;
        $responce->casa_rbs = number_format($casa_rbs_master, 2, '.', '');
        $responce->casa_fa_ch = number_format($casa_fa_ch_master, 2, '.', '');
        return json_encode($responce);
    }

    function FinanciarCentru_IncarcareCasa() {
        //table decont_cheltuieli
        //tip 1-incarcare casa_rbs, 2-incarcare casa_fa_ch
        $responce = new StdClass();
        $responce->status = 'error';
        $responce->message = 'Eroare incarcare casa !';
        $tip = intval($_POST['tip'] ?? 0);
        $amount = round(floatval($_POST['amount'] ?? 0), 2);
        $descriere = Backend::sSanitizeCleanEdges($_POST['descriere'] ?? '');

        if(!in_array($tip, [1, 2])) {
            $responce->message = 'Casa gresita !'; 
            return json_encode($responce);
        }
        $tip_casa = $tip == 1 ? 'RBS' : 'FA/CH';
        //TODO: doar centru 47 poate incarca casa
        if(!$this->financiar || $this->centru_id != 47) {
            $responce->message = "Nu ai dreptul sa incarci casa {$tip_casa}!"; 
            return json_encode($responce);
        }

        if($amount < 1) {
            $responce->message = 'Suma incarcata trebuie sa fie mai mare decat 0 !'; 
            return json_encode($responce);
        }

        if($descriere == '') {
            $responce->message = 'Descrierea este obligatorie !'; 
            return json_encode($responce);
        }

        try {
            $this->db->BeginTransaction();
            //get decont_casa
            $decont_casa_sql = $this->db->QFetchArray("SELECT id, casa_rbs, casa_fa_ch
                FROM decont_casa 
                WHERE centru_id = {$this->centru_id} AND DATE(created_at) = '{$this->today}'");
            if(empty($decont_casa_sql)) {
                $decont_casa_id = $this->db->QueryInsert('decont_casa', [
                    'created_at' => $this->today,
                    'centru_id' => $this->centru_id,
                    'user_id' => $this->user_id,
                ]);
                $trezorerie = self::transferTrezorerie($this->db, $this->centru_id, $this->user_id, $decont_casa_id, 0, 0, $this->today);
                $decont_casa_sql = ['id' => $decont_casa_id, 'casa_rbs' => $trezorerie['rbs'], 'casa_fa_ch' => $trezorerie['fa_ch']];
            }

            $responce->casa_rbs = 0.00;
            $responce->casa_fa_ch = 0.00;

            switch($tip) {
                case 1 : $this->db->QueryUpdate('decont_casa', [
                        'casa_rbs' => round(($decont_casa_sql['casa_rbs'] + $amount), 2),
                    ], "id = {$decont_casa_sql['id']}");
                    $responce->casa_fa_ch = round($decont_casa_sql['casa_fa_ch'], 2);
                    $responce->casa_rbs = round(($decont_casa_sql['casa_rbs'] + $amount), 2);
                    break;
                case 2 : $this->db->QueryUpdate('decont_casa', [
                        'casa_fa_ch' => round(($decont_casa_sql['casa_fa_ch'] + $amount), 2)
                    ], "id = {$decont_casa_sql['id']}");
                    $responce->casa_rbs = round($decont_casa_sql['casa_rbs'], 2);
                    $responce->casa_fa_ch = round(($decont_casa_sql['casa_fa_ch'] + $amount), 2);
                    break;
                default : 
                    $responce->casa_rbs = round($decont_casa_sql['casa_rbs'], 2);
                    $responce->casa_fa_ch = round($decont_casa_sql['casa_fa_ch'], 2);
                    $this->db->rollbackTransaction();
                    $responce->message = 'Eroare tip casa !';
                    return json_encode($responce);
            };
            //insert incarcare
            $this->db->QueryInsert('decont_cheltuieli', [
                'data' => date('Y-m-d H:i:s'),
                'tip' => $tip == 1 ? 35 : 34, //34-incarcare casa fa_ch, 35-incarcare casa rbs
                'suma' => $amount,
                'descriere' => $descriere,
                'user_id' => $this->user_id,
                'centru_id' => $this->centru_id,
                'agent_id' => 0,
                'decont_id' => -1,
            ]);
            $this->db->commitTransaction();
        }
        catch (Exception $e) {
            $this->db->rollbackTransaction();
            error_log("FinanciarCentru_IncarcareCasa : Eroare GRAVA {$tip_casa}: <br/>".$e->getMessage());
            $responce->message = "Eroare GRAVA la incarcare casa {$tip_casa}!";
            return json_encode($responce);
        }
        $responce->status = 'success';
        $responce->message = "A fost incarcata suma de ".number_format($amount, 2, '.', '')." in CASA {$tip_casa} !";

        return json_encode($responce);
    }

    function FinanciarCentru_IncarcareTrezorerie() {
        //field trezorerie_rbs, trezorerie_fa_ch in table decont_casa
        //tip 1-incarcare casa_rbs, 2-incarcare casa_fa_ch
        //update aici
        //transfera trezoreria la insert decont_casa
        //tip 1-incarcare casa_rbs, 2-incarcare casa_fa_ch
        $responce = new StdClass();
        $responce->status = 'error';
        $responce->message = 'Eroare incarcare trezorerie !';
        $tip = intval($_POST['tip'] ?? 0);
        $amount = round(floatval($_POST['amount'] ?? 0), 2);
        $descriere = Backend::sSanitizeCleanEdges($_POST['descriere'] ?? '');

        if(!in_array($tip, [3, 4])) {
            $responce->message = 'Casa gresita !'; 
            return json_encode($responce);
        }
        $tip_trezorerie = $tip == 3 ? 'RBS' : 'FA/CH';

        /*
        if(!$this->financiar) {
            $responce->message = "Nu ai dreptul sa incarci trezoreria {$tip_trezorerie}!"; 
            return json_encode($responce);
        }
        */

        if($amount < 1 || $amount > self::MAX_TREZORERIE) {
            $responce->message = 'Suma nu poate depasi '.self::MAX_TREZORERIE.' RON !'; 
            return json_encode($responce);
        }

        if(empty($descriere)) {
            $responce->message = 'Descrierea este obligatorie !'; 
            return json_encode($responce);
        }

        try {
            $this->db->BeginTransaction();
            //get decont_casa
            $decont_casa_sql = $this->db->QFetchArray("SELECT id, casa_rbs, casa_fa_ch, trezorerie_rbs, trezorerie_fa_ch
                FROM decont_casa 
                WHERE centru_id = {$this->centru_id} AND DATE(created_at) = '{$this->today}'");
            if(empty($decont_casa_sql)) {
                $responce->message = "Casa {$tip_trezorerie} este goala !"; 
                return json_encode($responce);
            }

            $responce->casa_rbs = 0.00;
            $responce->casa_fa_ch = 0.00;
            $responce->trezorerie_rbs = 0.00;
            $responce->trezorerie_fa_ch = 0.00;

            switch($tip) {
                case 3 : 
                    if(round(($decont_casa_sql['trezorerie_rbs'] + $amount), 2) > self::MAX_TREZORERIE) {
                        $this->db->rollbackTransaction();
                        $responce->message = 'Suma maxima trezorerie RBS nu poate depasi '.self::MAX_TREZORERIE.' RON !'; 
                        return json_encode($responce);
                    }
                    if(round(($decont_casa_sql['casa_rbs'] - $amount), 2) < 0) {
                        $this->db->rollbackTransaction();
                        $responce->message = 'Suma ceruta depaseste existentul in CASA RBS!'; 
                        return json_encode($responce);
                    }
                    $this->db->QueryUpdate('decont_casa', [
                        'casa_rbs' => round(($decont_casa_sql['casa_rbs'] - $amount), 2),
                        'trezorerie_rbs' => round(($decont_casa_sql['trezorerie_rbs'] + $amount), 2),
                    ], "id = {$decont_casa_sql['id']}");
                    $responce->casa_rbs = round(($decont_casa_sql['casa_rbs'] - $amount), 2);
                    $responce->trezorerie_rbs = round(($decont_casa_sql['trezorerie_rbs'] + $amount), 2);
                    $responce->casa_fa_ch = round(($decont_casa_sql['casa_fa_ch']), 2);
                    $responce->trezorerie_fa_ch = round(($decont_casa_sql['trezorerie_fa_ch']), 2);
                    break;
                case 4 : 
                    if(round(($decont_casa_sql['trezorerie_fa_ch'] + $amount), 2) > self::MAX_TREZORERIE) {
                        $this->db->rollbackTransaction();
                        $responce->message = 'Suma maxima trezorerie FA/CH nu poate depasi '.self::MAX_TREZORERIE.' RON !'; 
                        return json_encode($responce);
                    }
                    if(round(($decont_casa_sql['casa_fa_ch'] - $amount), 2) < 0) {
                        $this->db->rollbackTransaction();
                        $responce->message = 'Suma ceruta depaseste existentul in CASA FA/CH!'; 
                        return json_encode($responce);
                    }
                    $this->db->QueryUpdate('decont_casa', [
                        'casa_fa_ch' => round(($decont_casa_sql['casa_fa_ch'] - $amount), 2),
                        'trezorerie_fa_ch' => round(($decont_casa_sql['trezorerie_fa_ch'] + $amount), 2)
                    ], "id = {$decont_casa_sql['id']}");
                    $responce->casa_rbs = round(($decont_casa_sql['casa_rbs']), 2);
                    $responce->trezorerie_rbs = round(($decont_casa_sql['trezorerie_rbs']), 2);
                    $responce->casa_fa_ch = round(($decont_casa_sql['casa_fa_ch'] - $amount), 2);
                    $responce->trezorerie_fa_ch = round(($decont_casa_sql['trezorerie_fa_ch'] + $amount), 2);
                    break;
                default : 
                    $this->db->rollbackTransaction();
                    return json_encode($responce);
            };
            //insert incarcare
            $this->db->QueryInsert('decont_cheltuieli', [
                'data' => date('Y-m-d H:i:s'),
                'tip' => $tip == 3 ? 37 : 36, //36-incarcare trezorerie fa/ch, 37-incarcare trezorerie rbs
                'suma' => $amount,
                'descriere' => $descriere,
                'user_id' => $this->user_id,
                'centru_id' => $this->centru_id,
                'agent_id' => 0,
                'decont_id' => -1,
            ]);
            $this->db->commitTransaction();
        }
        catch (Exception $e) {
            $this->db->rollbackTransaction();
            error_log("FinanciarCentru_IncarcareTrezorerie : Eroare GRAVA {$tip_trezorerie}: <br/>".$e->getMessage());
            $responce->message = "Eroare GRAVA la incarcare trezorerie {$tip_trezorerie}!";
            return json_encode($responce);
        }
        $responce->status = 'success';
        $responce->message = "A fost incarcata suma de ".number_format($amount, 2, '.', '')." in Trezoreria {$tip_trezorerie} !";

        return json_encode($responce);
    }

    function FinanciarCentru_ConfirmaSalarii() {
        $salarii_confirmate = 0.00;
        $casa_id = 0;
        $casa = 0.00;

        $decont_casa_sql = $this->db->QFetchArray("SELECT id, rbs_decontate, rbs_generate, 
                casa_rbs, salarii_confirmate
                FROM decont_casa 
                WHERE centru_id = {$this->centru_id} AND DATE(created_at) = '{$this->today}'");
        if(empty($decont_casa_sql) || $decont_casa_sql['casa_rbs'] <= 1) {
            return json_encode([
                'status' => 'error',
                'message' => 'Casa este goala! Nu poti confirma salariile !'
            ]);
        }
        $casa = $decont_casa_sql['casa_rbs'] ?? 0.00;
        $casa_id = $decont_casa_sql['id'] ?? 0;

        $query_salarii = "SELECT ces.id, ces.amount as total_aprobate, sum(COALESCE(cesl.amount, 0)) as total_confirmate 
            FROM centre_salarii ces
            LEFT JOIN centre_salarii_linii cesl on cesl.centre_salarii_id = ces.id
            where ces.centru_id = {$this->centru_id} and 
            ces.created_at = (select max(created_at) FROM centre_salarii where centru_id = {$this->centru_id})
            group by ces.id";
        $result_salarii = $this->db->QFetchArray($query_salarii);
        if(empty($result_salarii) || $result_salarii['total_aprobate'] - $result_salarii['total_confirmate'] <= 0) {
            return json_encode([
                'status' => 'error',
                'message' => 'Nu exista salarii de confirmat!'
            ]);
        }

        $id = $result_salarii['id'] ?? 0;
        $salarii_confirmate = $salarii_de_confirmat = $result_salarii['total_aprobate'] - $result_salarii['total_confirmate'];

        $casa_int = floor($casa);
        $sold_bank = $casa_int - $salarii_de_confirmat;
        $new_bank = $casa - $salarii_de_confirmat;
        if($sold_bank < 0) {
            $salarii_confirmate = $casa_int; //iau tot din casa
            $new_bank = $casa - $casa_int;
        }

        $new_bank = round($new_bank, 2);
        //insert centre_salarii_linii
        $this->db->QueryInsert('centre_salarii_linii', [
            'centre_salarii_id' => $id,
            'amount' => $salarii_confirmate
        ]);
        //update decont_casa
        $salarii_confirmate_decont = $decont_casa_sql['salarii_confirmate'] + $salarii_confirmate;
        $this->db->QueryUpdate('decont_casa', [
            'casa_rbs' => $new_bank,
            'salarii_confirmate' =>  $salarii_confirmate_decont,
        ], "id = {$casa_id}");

        $salarii_confirmate_dashboard = $result_salarii['total_confirmate'] + $salarii_confirmate;
        return json_encode([
                'status' => 'success',
                'casa_rbs' => $new_bank,
                'salarii' =>  "{$result_salarii['total_aprobate']}/{$salarii_confirmate_dashboard}",
            ]);
    }

    function JSON_FinanciarCentru_Istoric($m_centru_id = 0) {
        $responce = new StdClass();
        $responce->records = 0;
        $centru_id = ($this->user_profile == 10 && $m_centru_id > 0) ? $m_centru_id : $this->centru_id;

        $page = intval($_GET['page'] ?? 1);
		$limit = intval($_GET['rows'] ?? 50);
		$sidx = trim($this->sanitize(empty($_GET['sidx']) ? 2 : $_GET['sidx']));
		$sord = trim($this->sanitize($_GET['sord'] ?? 'desc'));

        $cond = "1 = 1";
        //start generare conditie
        $searchOn = $this->Strip($_GET['_search'] ?? '');
        if ($searchOn == 'true') {
            $searchstr = $this->Strip($_GET['filters'] ?? '');
            $cond .= $this->constructWhere($searchstr);
        }

        $today = new DateTime("now");
        $data_final = $today->format('Y-m-d');
        //3 months limit
        $data_start = $today->modify('-3 months')->format('Y-m-d');
        //ramburs generate & ramburs decontate & salarii confirmate & depunere banca

        $query_count="SELECT COUNT(*) as nr         
            FROM decont_casa 
            WHERE centru_id = :centru_id 
            and created_at between '{$data_start}' and '{$data_final}'
        ";
    
        $result_count = $this->db->QFetchRowColumn($query_count, ['centru_id' => $centru_id]);
        $count = !empty($result_count) ? $result_count : 0;

        $total_pages = $count > 0 ? ceil($count/$limit) : 0;
        if ($page > $total_pages) $page = $total_pages;
        $start = $limit * $page - $limit;
        if ($start < 0) $start = 0;

        if($count == 0) {
            $responce->page = $page; 
            $responce->total = $total_pages; 
            $responce->records = $count;
            return json_encode($responce);
        }
        
        $query_decont_casa = "SELECT id, created_at, nr_rbs_decontate, rbs_decontate, facturi_decontate, 
            chitante_decontate, cheltuieli_rbs, cheltuieli_fa_ch, nr_rbs_generate, rbs_generate, 
            salarii_confirmate, casa_rbs, casa_fa_ch, trezorerie_rbs, trezorerie_fa_ch
            FROM decont_casa 
            WHERE centru_id = :centru_id and created_at between '{$data_start}' and '{$data_final}'
            order by {$sidx} {$sord} LIMIT {$start},{$limit}
        ";
        //error_log($query_decont_casa);
        $result_decont_casa = $this->db->QFetchRowArray($query_decont_casa, ['centru_id' => $centru_id]);
        if(!empty($result_decont_casa) && is_array($result_decont_casa) && count($result_decont_casa) > 0) {
            foreach($result_decont_casa as $key => $row) {
                $responce->rows[$key]['id'] = $row['id'] ?? 0;
                $responce->rows[$key]['cell'] = [
                    $row['created_at'] ?? '',
                    number_format($row['facturi_decontate'] ?? 0.00, 2, '.', ''),
                    number_format($row['chitante_decontate'] ?? 0.00, 2, '.', ''),
                    number_format($row['rbs_decontate'] ?? 0.00, 2, '.', ''),
                    number_format($row['cheltuieli_rbs'] ?? 0.00, 2, '.', ''),
                    number_format($row['cheltuieli_fa_ch'] ?? 0.00, 2, '.', ''),
                    $row['nr_rbs_generate'] ?? 0,
                    number_format($row['rbs_generate'] ?? 0.00, 2, '.', ''),
                    number_format($row['salarii_confirmate'] ?? 0.00, 2, '.', ''),
                    number_format($row['casa_rbs'] ?? 0.00, 2, '.', ''),
                    number_format($row['casa_fa_ch'] ?? 0.00, 2, '.', ''),
                    number_format($row['trezorerie_rbs'] ?? 0.00, 2, '.', ''),
                    number_format($row['trezorerie_fa_ch'] ?? 0.00, 2, '.', ''),
                    ['centru_id' => $centru_id, 'created_at' => $row['created_at']],
                ];
            }
        }
        $responce->page = $page; 
        $responce->total = $total_pages; 
        $responce->records = $count;
        //$responce->userdata[''] = $count;
        //$responce->userdata[''] = number_format(0, 2, '.', '');
        
        return json_encode($responce);
    }

//////////////--FINANCIAR CENTRE--
    function DashboardFinanciarCentre() {
        $this->vars['title_page'] = "Situatie financiara centre";
        $vars = [];
        $today = new DateTime("now");
        $vars['onload_js_version'] = $this->config['version']['onload_js_version'];
        $vars['TOTAL_FACTURI_INCASATE'] = 0.00;
        $vars['NR_FACTURI_INCASATE'] = 0;
        $vars['TOTAL_CHITANTE_INCASATE'] = 0.00;
        $vars['NR_CHITANTE_INCASATE'] = 0;
        $vars['TOTAL_RBS_CASH_CONT_INCASATE'] = 0.00;
        $vars['NR_RBS_CASH_CONT_INCASATE'] = 0;

        $vars['TOTAL_FACTURI_DECONTATE'] = 0.00;
        $vars['TOTAL_CHITANTE_DECONTATE'] = 0.00;
        $vars['TOTAL_CHELTUIELI_RBS'] = 0.00;
        $vars['TOTAL_CHELTUIELI_FA_CH'] = 0.00;
        $vars['TOTAL_RBS_CASH_CONT_DECONTATE'] = 0.00;
        $vars['NR_RBS_CASH_CONT_DECONTATE'] = 0;

        $vars['TOTAL_RBS_CASH_APROBATE'] = 0.00;
        $vars['NR_RBS_CASH_APROBATE'] = 0;
        $vars['TOTAL_RBS_CASH_GENERATE'] = 0.00;
        $vars['NR_RBS_CASH_GENERATE'] = 0;

        $vars['TOTAL_SALARII_CONFIRMATE'] = 0.00;
        $vars['TOTAL_SALARII_APROBATE'] = 0.00;

        $vars['CASA_RBS_CASH_CONT'] = 0.00;
        $vars['CASA_FA_CH_CASH_CONT'] = 0.00;

        $data_start = $data_final = $today;
        if(isset($_GET['data_start']) && isset($_GET['data_final'])){
			try {
                $data_start = DateTime::createFromFormat('d.m.Y', $_GET['data_start']);
                $data_final = DateTime::createFromFormat('d.m.Y', $_GET['data_final']);
                if($data_start && $data_final) {
                    if(intval($data_start->diff($data_final, true)->format('%a')) > 93){
                        $data_start = $data_final = $today;
                    }
                }
			}
			catch (Exception $e){
                $data_start = $data_final = $today;
            }
	    }
        $vars['DATA_START'] = $data_start->format('d.m.Y');
        $vars['DATA_FINAL'] = $data_final->format('d.m.Y');

        //facturi incasate
        $query_facturi_incasate = "SELECT COUNT(df.id) as nr, sum(df.suma) as total_valoare
            FROM decont_facturi df
            where df.anulata = 0 and df.transaction_id = 0
            and DATE(df.data) between '{$data_start->format('Y-m-d')}' and '{$data_final->format('Y-m-d')}'
        ";
        $result_facturi_incasate = $this->db->QFetchArray($query_facturi_incasate);
        if(!empty($result_facturi_incasate) && is_array($result_facturi_incasate) && count($result_facturi_incasate) > 0) {
            $vars['TOTAL_FACTURI_INCASATE'] = number_format($result_facturi_incasate['total_valoare'] ?? 0.00, 2, '.', '');
            $vars['NR_FACTURI_INCASATE'] = $result_facturi_incasate['nr'] ?? 0;
        }
        
        //chitante incasate
        $query_chitante = "SELECT COUNT(cf.id) as nr, sum(cf.suma) as total_valoare
            FROM decont_chitante cf
            where cf.anulata = 0 and cf.transaction_id = 0
            and DATE(cf.dataInc) between '{$data_start->format('Y-m-d')}' and '{$data_final->format('Y-m-d')}'
        ";
        $result_chitante = $this->db->QFetchArray($query_chitante);
        if(!empty($result_chitante) && is_array($result_chitante) && count($result_chitante) > 0) {
            $vars['TOTAL_CHITANTE_INCASATE'] = number_format($result_chitante['total_valoare'] ?? 0.00, 2, '.', '');
            $vars['NR_CHITANTE_INCASATE'] = $result_chitante['nr'] ?? 0;
        }

        //ramburs incasate
        $query_rbs_cash_cont_incasate="SELECT COUNT(dr.id) as nr, sum(dr.ramburs) as total_valoare
            FROM decont_rbs dr
            where dr.anulata = 0 and dr.transaction_id = 0 and DATE(dr.dataInc) between '{$data_start->format('Y-m-d')}' and '{$data_final->format('Y-m-d')}'
            ";
        $result_rbs_cash_cont_incasate = $this->db->QFetchArray($query_rbs_cash_cont_incasate);
        if(!empty($result_rbs_cash_cont_incasate) && is_array($result_rbs_cash_cont_incasate) && count($result_rbs_cash_cont_incasate) > 0) {
            $vars['TOTAL_RBS_CASH_CONT_INCASATE'] = number_format($result_rbs_cash_cont_incasate['total_valoare'] ?? 0.00, 2, '.', '');
            $vars['NR_RBS_CASH_CONT_INCASATE'] = $result_rbs_cash_cont_incasate['nr'] ?? 0;
        }

        //salarii aprobate
        $query_salarii_aprobate = "SELECT sum(amount) as total_salarii_aprobate
            FROM centre_salarii
            where DATE(created_at) between '{$data_start->format('Y-m-d')}' and '{$data_final->format('Y-m-d')}'
            ";
        $result_salarii_aprobate = $this->db->QFetchArray($query_salarii_aprobate);
        if(!empty($result_salarii_aprobate) && is_array($result_salarii_aprobate) && count($result_salarii_aprobate) > 0) {
            $vars['TOTAL_SALARII_APROBATE'] = number_format($result_salarii_aprobate['total_salarii_aprobate'] ?? 0.00, 2, '.', '');
        }

        //ramburs aprobate
        $query_rbs_cash_aprobate = "SELECT COUNT(ep.cod_expeditie) as nr, sum(COALESCE(ep.ramburs, 0)) as total_valoare          
            FROM exp_prelucrate ep
            WHERE ep.tip_exp = 0 and ep.anulata = 0 and ep.ramburs > 0 and ep.tip_plata = 0 and ep.status_ramburs = 30
            ";
        $result_rbs_cash_aprobate = $this->db->QFetchArray($query_rbs_cash_aprobate);
        if(!empty($result_rbs_cash_aprobate) && is_array($result_rbs_cash_aprobate) && count($result_rbs_cash_aprobate) > 0) {
            $vars['TOTAL_RBS_CASH_APROBATE'] = number_format($result_rbs_cash_aprobate['total_valoare'] ?? 0.00, 2, '.', '');
            $vars['NR_RBS_CASH_APROBATE'] = $result_rbs_cash_aprobate['nr'] ?? 0;
        }

        //ramburs generate & ramburs decontate & salarii confirmate & casa rbs & casa fa_ch
        $query_decont_casa = "SELECT sum(nr_rbs_decontate) as nr_rbs_decontate,     
                sum(rbs_decontate) as rbs_decontate, sum(facturi_decontate) as facturi_decontate, 
                sum(chitante_decontate) as chitante_decontate, sum(cheltuieli_rbs) as cheltuieli_rbs, sum(cheltuieli_fa_ch) as cheltuieli_fa_ch,
                sum(nr_rbs_generate) as nr_rbs_generate, sum(rbs_generate) as rbs_generate, 
                sum(salarii_confirmate) as salarii_confirmate, 
                sum(casa_rbs) as casa_rbs, sum(casa_fa_ch) as casa_fa_ch, 
                sum(trezorerie_rbs) as trezorerie_rbs, sum(trezorerie_fa_ch) as trezorerie_fa_ch
                FROM decont_casa 
                WHERE DATE(created_at) between '{$data_start->format('Y-m-d')}' and '{$data_final->format('Y-m-d')}'
            ";
        $result_decont_casa = $this->db->QFetchArray($query_decont_casa);
        if(!empty($result_decont_casa) && is_array($result_decont_casa) && count($result_decont_casa) > 0) {
            $vars['TOTAL_FACTURI_DECONTATE'] = number_format($result_decont_casa['facturi_decontate'] ?? 0.00, 2, '.', '');
            $vars['TOTAL_CHITANTE_DECONTATE'] = number_format($result_decont_casa['chitante_decontate'] ?? 0.00, 2, '.', '');
            $vars['TOTAL_CHELTUIELI_RBS'] = number_format($result_decont_casa['cheltuieli_rbs'] ?? 0.00, 2, '.', '');
            $vars['TOTAL_CHELTUIELI_FA_CH'] = number_format($result_decont_casa['cheltuieli_fa_ch'] ?? 0.00, 2, '.', '');
            $vars['NR_RBS_CASH_CONT_DECONTATE'] = $result_decont_casa['nr_rbs_decontate'] ?? 0;
            $vars['TOTAL_RBS_CASH_CONT_DECONTATE'] = number_format($result_decont_casa['rbs_decontate'] ?? 0.00, 2, '.', '');
            $vars['NR_RBS_CASH_GENERATE'] = $result_decont_casa['nr_rbs_generate'] ?? 0;
            $vars['TOTAL_RBS_CASH_GENERATE'] = number_format($result_decont_casa['rbs_generate'] ?? 0.00, 2, '.', '');
            $vars['TOTAL_SALARII_CONFIRMATE'] = number_format($result_decont_casa['salarii_confirmate'] ?? 0.00, 2, '.', '');
            $vars['CASA_RBS_CASH_CONT'] = number_format($result_decont_casa['casa_rbs'] ?? 0.00, 2, '.', '');
            $vars['CASA_FA_CH_CASH_CONT'] = number_format($resul_decont_casa['casa_fa_ch'] ?? 0.00, 2, '.', '');
            $vars['TREZORERIE_RBS'] = number_format($result_decont_casa['trezorerie_rbs'] ?? 0.00, 2, '.', '');
            $vars['TREZORERIE_FA_CH'] = number_format($result_decont_casa['trezorerie_fa_ch'] ?? 0.00, 2, '.', '');
        }

        return $this->Parse('financiar_centre.html', $vars);
    }

    function JSON_DashboardFinanciarCentre() {
        $today = new DateTime("now");
        $responce = new StdClass();
        $responce->records = 0;

        $data_start = $data_final = $today;
        if(isset($_GET['data_start']) && isset($_GET['data_final'])){
			try {
                $data_start = DateTime::createFromFormat('d.m.Y', $_GET['data_start']);
                $data_final = DateTime::createFromFormat('d.m.Y', $_GET['data_final']);
                if($data_start && $data_final) {
                    if(intval($data_start->diff($data_final, true)->format('%a')) > 93){
                        $data_start = $data_final = $today;
                    }
                }
			}
			catch (Exception $e){
                $data_start = $data_final = $today;
            }
	    }

        //facturi incasate
        $query_facturi_incasate = "SELECT ag.cod_centru as centru_id, COUNT(df.id) as nr, sum(df.suma) as total_valoare
            FROM decont_facturi df
            LEFT join agenti ag on ag.cod_ag = df.agent_id
            where df.anulata = 0 and df.transaction_id = 0
            and DATE(df.data) between '{$data_start->format('Y-m-d')}' and '{$data_final->format('Y-m-d')}'
            group by ag.cod_centru
        ";
        $result_facturi_incasate = $this->db->QFetchRowArray($query_facturi_incasate);
        $result_facturi_incasate_proc = [];
        if(!empty($result_facturi_incasate) && is_array($result_facturi_incasate) && count($result_facturi_incasate) > 0) {
            foreach ($result_facturi_incasate as $key => $row) {
                $result_facturi_incasate_proc[$row['centru_id']] = $row;
            }
        }
        
        //chitante incasate
        $query_chitante = "SELECT ag.cod_centru as centru_id, COUNT(cf.id) as nr, sum(cf.suma) as total_valoare
            FROM decont_chitante cf
            LEFT join agenti ag on ag.cod_ag = cf.agent_id
            where cf.anulata = 0 and cf.transaction_id = 0
            and DATE(cf.dataInc) between '{$data_start->format('Y-m-d')}' and '{$data_final->format('Y-m-d')}'
            group by ag.cod_centru
        ";
        $result_chitante = $this->db->QFetchRowArray($query_chitante);
        $result_chitante_proc = [];
        if(!empty($result_chitante) && is_array($result_chitante) && count($result_chitante) > 0) {
            foreach ($result_chitante as $key => $row) {
                $result_chitante_proc[$row['centru_id']] = $row;
            }
        }

        //ramburs incasate
        $query_rbs_cash_cont_incasate="SELECT ag.cod_centru as centru_id, COUNT(dr.id) as nr, sum(dr.ramburs) as total_valoare
            FROM decont_rbs dr
            LEFT join agenti ag on ag.cod_ag = dr.agent_id
            where dr.anulata = 0 and dr.transaction_id = 0 and DATE(dr.dataInc) between '{$data_start->format('Y-m-d')}' and '{$data_final->format('Y-m-d')}'
            group by ag.cod_centru
            ";
        $result_rbs_cash_cont_incasate = $this->db->QFetchRowArray($query_rbs_cash_cont_incasate);
        $result_rbs_cash_cont_incasate_proc = [];
        if(!empty($result_rbs_cash_cont_incasate) && is_array($result_rbs_cash_cont_incasate) && count($result_rbs_cash_cont_incasate) > 0) {
            foreach ($result_rbs_cash_cont_incasate as $key => $row) {
                $result_rbs_cash_cont_incasate_proc[$row['centru_id']] = $row;
            }
        }

        //salarii aprobate
        $query_salarii_aprobate = "SELECT centru_id, sum(amount) as centru_salarii_aprobate
            FROM centre_salarii
            where DATE(created_at) between '{$data_start->format('Y-m-d')}' and '{$data_final->format('Y-m-d')}'
            group by centru_id
            ";
        $result_salarii_aprobate = $this->db->QFetchRowArray($query_salarii_aprobate);
        $result_salarii_aprobate_proc = [];
        if(!empty($result_salarii_aprobate) && is_array($result_salarii_aprobate) && count($result_salarii_aprobate) > 0) {
            foreach ($result_salarii_aprobate as $key => $row) {
                $result_salarii_aprobate_proc[$row['centru_id']] = $row;
            }
        }

        //ramburs aprobate
        $query_rbs_cash_aprobate = "SELECT IF(cle.zona_id > 0 and clez.centru_id > 0, clez.centru_id, lce.cod_centru) as centru_id,
            COUNT(ep.cod_expeditie) as nr, sum(COALESCE(ep.ramburs, 0)) as total_valoare          
            FROM exp_prelucrate ep
            LEFT JOIN clienti cle on cle.cod_cl = ep.expeditor_id
            LEFT JOIN zones clez ON clez.id = cle.zona_id
            LEFT JOIN localitati lce ON lce.cod_lc = cle.cod_lc
            WHERE ep.tip_exp = 0 and ep.anulata = 0 and ep.ramburs > 0 and ep.tip_plata = 0 and ep.status_ramburs = 30
            group by IF(cle.zona_id > 0 and clez.centru_id > 0, clez.centru_id, lce.cod_centru)
            ";
        $result_rbs_cash_aprobate = $this->db->QFetchRowArray($query_rbs_cash_aprobate);
        $result_rbs_cash_aprobate_proc = [];
        if(!empty($result_rbs_cash_aprobate) && is_array($result_rbs_cash_aprobate) && count($result_rbs_cash_aprobate) > 0) {
            foreach ($result_rbs_cash_aprobate as $key => $row) {
                $result_rbs_cash_aprobate_proc[$row['centru_id']] = $row;
            }
        }

        //ramburs generate & ramburs decontate & salarii confirmate & depunere banca
        $query_decont_casa = "SELECT centru_id, sum(nr_rbs_decontate) as nr_rbs_decontate,     
            sum(rbs_decontate) as rbs_decontate, sum(facturi_decontate) as facturi_decontate, 
            sum(chitante_decontate) as chitante_decontate, sum(cheltuieli_rbs) as cheltuieli_rbs,sum(cheltuieli_fa_ch) as cheltuieli_fa_ch,
            sum(nr_rbs_generate) as nr_rbs_generate, sum(rbs_generate) as rbs_generate, 
            sum(salarii_confirmate) as salarii_confirmate, 
            sum(casa_rbs) as casa_rbs, sum(casa_fa_ch) as casa_fa_ch,
            sum(trezorerie_rbs) as trezorerie_rbs, sum(trezorerie_fa_ch) as trezorerie_fa_ch
            FROM decont_casa 
            WHERE DATE(created_at) between '{$data_start->format('Y-m-d')}' and '{$data_final->format('Y-m-d')}'
            group by centru_id
        ";
        $result_decont_casa = $this->db->QFetchRowArray($query_decont_casa);
        $result_decont_casa_proc = [];
        if(!empty($result_decont_casa) && is_array($result_decont_casa) && count($result_decont_casa) > 0) {
            foreach ($result_decont_casa as $key => $row) {
                $result_decont_casa_proc[$row['centru_id']] = $row;
            }
        }

        $query_centre="SELECT ce.id, mce.nume as mst_financiar_nume, ce.nume as centru_nume, 
                ce.label as centru_cod, ce.email as centru_email, 
                ce.financiar, dc.nume as dispecerat
                FROM centre ce
                left join centre mce on ce.mst_financiar_id = mce.id
                left join dispecerate dc on ce.dispecerat_id = dc.id
                WHERE ce.deleted = 0 order by ce.nume asc";
        $result_centre = $this->db->QFetchRowArray($query_centre);
        $count = 0;
        if(empty($result_centre) || !is_array($result_centre) || count($result_centre) <= 0) {
            return json_encode([
                'status' => 'error',
                'message' => 'Nu gasesc centre!'
            ]);
        }
        $count = count($result_centre);
        foreach($result_centre as $key => $row) {
            $responce->rows[$key]['id'] = $centru_id = $row['id'] ?? 0;
            $responce->rows[$key]['cell'] = [
                $row['centru_nume'] ?? '',
                $row['centru_cod'] ?? '',
                $row['mst_financiar_nume'] ?? '',
                $row['dispecerat'] ?? '',
            ];
            //incasate
            $responce->rows[$key]['cell'][] = $result_facturi_incasate_proc[$centru_id]['nr'] ?? 0;
            $responce->rows[$key]['cell'][] = number_format($result_facturi_incasate_proc[$centru_id]['total_valoare'] ?? 0.00, 2, '.', '');
            $responce->rows[$key]['cell'][] = $result_chitante_proc[$centru_id]['nr'] ?? 0;
            $responce->rows[$key]['cell'][] = number_format($result_chitante_proc[$centru_id]['total_valoare'] ?? 0.00, 2, '.', '');
            $responce->rows[$key]['cell'][] = $result_rbs_cash_cont_incasate_proc[$centru_id]['nr'] ?? 0;
            $responce->rows[$key]['cell'][] = number_format($result_rbs_cash_cont_incasate_proc[$centru_id]['total_valoare'] ?? 0.00, 2, '.', '');

            //decontate
            $responce->rows[$key]['cell'][] = number_format($result_decont_casa_proc[$centru_id]['facturi_decontate'] ?? 0.00, 2, '.', '');
            $responce->rows[$key]['cell'][] = number_format($result_decont_casa_proc[$centru_id]['chitante_decontate'] ?? 0.00, 2, '.', '');
            $responce->rows[$key]['cell'][] = number_format($result_decont_casa_proc[$centru_id]['rbs_decontate'] ?? 0.00, 2, '.', '');
            $responce->rows[$key]['cell'][] = number_format($result_decont_casa_proc[$centru_id]['cheltuieli_rbs'] ?? 0.00, 2, '.', '');
            $responce->rows[$key]['cell'][] = number_format($result_decont_casa_proc[$centru_id]['cheltuieli_fa_ch'] ?? 0.00, 2, '.', '');

            //aprobate
            $responce->rows[$key]['cell'][] = $result_rbs_cash_aprobate_proc[$centru_id]['nr'] ?? 0;
            $responce->rows[$key]['cell'][] = number_format($result_rbs_cash_aprobate_proc[$centru_id]['total_valoare'] ?? 0.00, 2, '.', '');
            $responce->rows[$key]['cell'][] = $result_decont_casa_proc[$centru_id]['nr_rbs_generate'] ?? 0;
            $responce->rows[$key]['cell'][] = number_format($result_decont_casa_proc[$centru_id]['rbs_generate'] ?? 0.00, 2, '.', '');
            $responce->rows[$key]['cell'][] = number_format($result_salarii_aprobate_proc[$centru_id]['centru_salarii_aprobate'] ?? 0.00, 2, '.', '');
            $responce->rows[$key]['cell'][] = number_format($result_decont_casa_proc[$centru_id]['salarii_confirmate'] ?? 0.00, 2, '.', '');
            
            //depunere banca  
            $responce->rows[$key]['cell'][] = number_format($result_decont_casa_proc[$centru_id]['casa_rbs'] ?? 0.00, 2, '.', '');
            $responce->rows[$key]['cell'][] = number_format($result_decont_casa_proc[$centru_id]['casa_fa_ch'] ?? 0.00, 2, '.', '');
            $responce->rows[$key]['cell'][] = number_format($result_decont_casa_proc[$centru_id]['trezorerie_rbs'] ?? 0.00, 2, '.', '');
            $responce->rows[$key]['cell'][] = number_format($result_decont_casa_proc[$centru_id]['trezorerie_fa_ch'] ?? 0.00, 2, '.', '');
        }

        $responce->records = $count;
        //$responce->userdata[''] = $count;
        //$responce->userdata[''] = number_format(0, 2, '.', '');
        
        return json_encode($responce);
    }

    function ExportDashboardFinanciarCentre() {
        ini_set('memory_limit', '1228M');
		set_time_limit(600);

        $data = date('d/m/Y');
 		$societate = 'Dragon Star Curier';
        $document = 'Financiar centre';

        $spreadsheet = new Spreadsheet();
        $spreadsheet->getProperties()->setCreator($societate)
            ->setLastModifiedBy($societate)
            ->setTitle($document)
            ->setSubject($document)
            ->setDescription($document)
            ->setKeywords($document)
            ->setCategory($document);
        $spreadsheet->getDefaultStyle()->getFont()->setName('Arial');
        $spreadsheet->getDefaultStyle()->getFont()->setSize(11);
		$worksheet = $spreadsheet->getActiveSheet();

		foreach(range('A','Y') as $v) {
			$worksheet->getStyle($v.'1')->getFont()->setBold(true);
			$worksheet->getStyle($v.'1')->getFont()->setSize(13);
			$worksheet->getColumnDimension($v)->setWidth(30);
		}

        $headers = ['Centru', 'Cod', 'Financiar centru', 'Dispecerat', 'Nr. fact. inc.', 'Total fact. inc.', 'Nr. chit. inc.', 'Total chit. inc.', 
				'Nr. RBS cash/cont inc.', 'Total RBS cash/cont inc.', 'Total facturi decontate', 'Total chitante decontate', 
				'Total RBS cash/cont decontate', 'Total cheltuieli RBS', 'Total cheltuieli Fa/Ch',
				'Nr. RBS cash aprobate', 'Total RBS cash aprobate', 'Nr. RBS cash generate', 'Total RBS cash generate',
				'Total Salarii aprobate', 'Total Salarii confirmate', 'Total Casa RBS', 'Total Casa FA/CH', 'Total Trezorerie RBS', 'Total Trezorerie FA/CH'];
        $worksheet->fromArray([$headers], NULL, 'A1');

        $today = new DateTime("now");
        $data_start = $data_final = $today;
        if(isset($_POST['data_start']) && isset($_POST['data_final'])){
			try {
                $data_start = DateTime::createFromFormat('d.m.Y', $_POST['data_start']);
                $data_final = DateTime::createFromFormat('d.m.Y', $_POST['data_final']);
                if($data_start && $data_final) {
                    if(intval($data_start->diff($data_final, true)->format('%a')) > 93){
                        $data_start = $data_final = $today;
                    }
                }
			}
			catch (Exception $e){
                $data_start = $data_final = $today;
            }
	    }

        $filename = "financiar_centre_{$data_start->format('Y-m-d')}_{$data_final->format('Y-m-d')}.xlsx";
    
        //facturi incasate
        $query_facturi_incasate = "SELECT ag.cod_centru as centru_id, COUNT(df.id) as nr, sum(df.suma) as total_valoare
            FROM decont_facturi df
            LEFT join agenti ag on ag.cod_ag = df.agent_id
            where df.anulata = 0 and df.transaction_id = 0
            and DATE(df.data) between '{$data_start->format('Y-m-d')}' and '{$data_final->format('Y-m-d')}'
            group by ag.cod_centru
        ";
        $result_facturi_incasate = $this->db->QFetchRowArray($query_facturi_incasate);
        $result_facturi_incasate_proc = [];
        if(!empty($result_facturi_incasate) && is_array($result_facturi_incasate) && count($result_facturi_incasate) > 0) {
            foreach ($result_facturi_incasate as $key => $row) {
                $result_facturi_incasate_proc[$row['centru_id']] = $row;
            }
        }
        
        //chitante incasate
        $query_chitante = "SELECT ag.cod_centru as centru_id, COUNT(cf.id) as nr, sum(cf.suma) as total_valoare
            FROM decont_chitante cf
            LEFT join agenti ag on ag.cod_ag = cf.agent_id
            where cf.anulata = 0 and cf.transaction_id = 0
            and DATE(cf.dataInc) between '{$data_start->format('Y-m-d')}' and '{$data_final->format('Y-m-d')}'
            group by ag.cod_centru
        ";
        $result_chitante = $this->db->QFetchRowArray($query_chitante);
        $result_chitante_proc = [];
        if(!empty($result_chitante) && is_array($result_chitante) && count($result_chitante) > 0) {
            foreach ($result_chitante as $key => $row) {
                $result_chitante_proc[$row['centru_id']] = $row;
            }
        }

        //ramburs incasate
        $query_rbs_cash_cont_incasate="SELECT ag.cod_centru as centru_id, COUNT(dr.id) as nr, sum(dr.ramburs) as total_valoare
            FROM decont_rbs dr
            LEFT join agenti ag on ag.cod_ag = dr.agent_id
            where dr.anulata = 0 and dr.transaction_id = 0 and DATE(dr.dataInc) between '{$data_start->format('Y-m-d')}' and '{$data_final->format('Y-m-d')}'
            group by ag.cod_centru
            ";
        $result_rbs_cash_cont_incasate = $this->db->QFetchRowArray($query_rbs_cash_cont_incasate);
        $result_rbs_cash_cont_incasate_proc = [];
        if(!empty($result_rbs_cash_cont_incasate) && is_array($result_rbs_cash_cont_incasate) && count($result_rbs_cash_cont_incasate) > 0) {
            foreach ($result_rbs_cash_cont_incasate as $key => $row) {
                $result_rbs_cash_cont_incasate_proc[$row['centru_id']] = $row;
            }
        }

        //salarii aprobate
        $query_salarii_aprobate = "SELECT centru_id, sum(amount) as centru_salarii_aprobate
            FROM centre_salarii
            where DATE(created_at) between '{$data_start->format('Y-m-d')}' and '{$data_final->format('Y-m-d')}'
            group by centru_id
            ";
        $result_salarii_aprobate = $this->db->QFetchRowArray($query_salarii_aprobate);
        $result_salarii_aprobate_proc = [];
        if(!empty($result_salarii_aprobate) && is_array($result_salarii_aprobate) && count($result_salarii_aprobate) > 0) {
            foreach ($result_salarii_aprobate as $key => $row) {
                $result_salarii_aprobate_proc[$row['centru_id']] = $row;
            }
        }

        //ramburs aprobate
        $query_rbs_cash_aprobate = "SELECT IF(cle.zona_id > 0 and clez.centru_id > 0, clez.centru_id, lce.cod_centru) as centru_id,
            COUNT(ep.cod_expeditie) as nr, sum(COALESCE(ep.ramburs, 0)) as total_valoare          
            FROM exp_prelucrate ep
            LEFT JOIN clienti cle on cle.cod_cl = ep.expeditor_id
            LEFT JOIN zones clez ON clez.id = cle.zona_id
            LEFT JOIN localitati lce ON lce.cod_lc = cle.cod_lc
            WHERE ep.tip_exp = 0 and ep.anulata = 0 and ep.ramburs > 0 and ep.tip_plata = 0 and ep.status_ramburs = 30
            group by IF(cle.zona_id > 0 and clez.centru_id > 0, clez.centru_id, lce.cod_centru)
            ";
        $result_rbs_cash_aprobate = $this->db->QFetchRowArray($query_rbs_cash_aprobate);
        $result_rbs_cash_aprobate_proc = [];
        if(!empty($result_rbs_cash_aprobate) && is_array($result_rbs_cash_aprobate) && count($result_rbs_cash_aprobate) > 0) {
            foreach ($result_rbs_cash_aprobate as $key => $row) {
                $result_rbs_cash_aprobate_proc[$row['centru_id']] = $row;
            }
        }

        //ramburs generate & ramburs decontate & salarii confirmate & depunere banca
        $query_decont_casa = "SELECT centru_id, sum(nr_rbs_decontate) as nr_rbs_decontate,     
            sum(rbs_decontate) as rbs_decontate, sum(facturi_decontate) as facturi_decontate, 
            sum(chitante_decontate) as chitante_decontate, sum(cheltuieli_rbs) as cheltuieli_rbs, sum(cheltuieli_fa_ch) as cheltuieli_fa_ch,
            sum(nr_rbs_generate) as nr_rbs_generate, sum(rbs_generate) as rbs_generate, 
            sum(salarii_confirmate) as salarii_confirmate, 
            sum(casa_rbs) as casa_rbs, sum(casa_fa_ch) as casa_fa_ch,
            sum(trezorerie_rbs) as trezorerie_rbs, sum(trezorerie_fa_ch) as trezorerie_fa_ch
            FROM decont_casa 
            WHERE DATE(created_at) between '{$data_start->format('Y-m-d')}' and '{$data_final->format('Y-m-d')}'
            group by centru_id
        ";
        $result_decont_casa = $this->db->QFetchRowArray($query_decont_casa);
        $result_decont_casa_proc = [];
        if(!empty($result_decont_casa) && is_array($result_decont_casa) && count($result_decont_casa) > 0) {
            foreach ($result_decont_casa as $key => $row) {
                $result_decont_casa_proc[$row['centru_id']] = $row;
            }
        }

        $query_centre="SELECT ce.id, mce.nume as mst_financiar_nume, ce.nume as centru_nume, 
                ce.label as centru_cod, ce.email as centru_email, 
                ce.financiar, dc.nume as dispecerat
                FROM centre ce
                left join centre mce on ce.mst_financiar_id = mce.id
                left join dispecerate dc on ce.dispecerat_id = dc.id
                WHERE ce.deleted = 0 order by ce.nume asc";
        $result_centre = $this->db->QFetchRowArray($query_centre);
        if(empty($result_centre) || !is_array($result_centre) || count($result_centre) <= 0) {
            $result_centre = [];
        }

        $rand = 2;
        foreach($result_centre as $key => $row) {
            $centru_id = $row['id'] ?? 0;
            $worksheet->setCellValue('A'.($rand+$key),$row['centru_nume'] ?? '');
            $worksheet->setCellValue('B'.($rand+$key),$row['centru_cod'] ?? '');
            $worksheet->setCellValue('C'.($rand+$key),$row['mst_financiar_nume'] ?? '');
            $worksheet->setCellValue('D'.($rand+$key),$row['dispecerat'] ?? '');
            //incasate
            $worksheet->setCellValue('E'.($rand+$key), $result_facturi_incasate_proc[$centru_id]['nr'] ?? 0);
            $worksheet->setCellValue('F'.($rand+$key), number_format($result_facturi_incasate_proc[$centru_id]['total_valoare'] ?? 0.00, 2, '.', ''));
            $worksheet->setCellValue('G'.($rand+$key),$result_chitante_proc[$centru_id]['nr'] ?? 0);
            $worksheet->setCellValue('H'.($rand+$key),number_format($result_chitante_proc[$centru_id]['total_valoare'] ?? 0.00, 2, '.', ''));
            $worksheet->setCellValue('I'.($rand+$key),$result_rbs_cash_cont_incasate_proc[$centru_id]['nr'] ?? 0);
            $worksheet->setCellValue('J'.($rand+$key),number_format($result_rbs_cash_cont_incasate_proc[$centru_id]['total_valoare'] ?? 0.00, 2, '.', ''));

            //decontate
            $worksheet->setCellValue('K'.($rand+$key),number_format($result_decont_casa_proc[$centru_id]['facturi_decontate'] ?? 0.00, 2, '.', ''));
            $worksheet->setCellValue('L'.($rand+$key),number_format($result_decont_casa_proc[$centru_id]['chitante_decontate'] ?? 0.00, 2, '.', ''));
            $worksheet->setCellValue('M'.($rand+$key),number_format($result_decont_casa_proc[$centru_id]['rbs_decontate'] ?? 0.00, 2, '.', ''));
            $worksheet->setCellValue('N'.($rand+$key),number_format($result_decont_casa_proc[$centru_id]['cheltuieli_rbs'] ?? 0.00, 2, '.', ''));
            $worksheet->setCellValue('O'.($rand+$key),number_format($result_decont_casa_proc[$centru_id]['cheltuieli_fa_ch'] ?? 0.00, 2, '.', ''));

            //aprobate
            $worksheet->setCellValue('P'.($rand+$key),$result_rbs_cash_aprobate_proc[$centru_id]['nr'] ?? 0);
            $worksheet->setCellValue('Q'.($rand+$key),number_format($result_rbs_cash_aprobate_proc[$centru_id]['total_valoare'] ?? 0.00, 2, '.', ''));
            $worksheet->setCellValue('R'.($rand+$key),$result_decont_casa_proc[$centru_id]['nr_rbs_generate'] ?? 0);
            $worksheet->setCellValue('S'.($rand+$key),number_format($result_decont_casa_proc[$centru_id]['rbs_generate'] ?? 0.00, 2, '.', ''));
            $worksheet->setCellValue('T'.($rand+$key),number_format($result_salarii_aprobate_proc[$centru_id]['centru_salarii_aprobate'] ?? 0.00, 2, '.', ''));
            $worksheet->setCellValue('U'.($rand+$key),number_format($result_decont_casa_proc[$centru_id]['salarii_confirmate'] ?? 0.00, 2, '.', ''));
            
            //depunere banca  
            $worksheet->setCellValue('V'.($rand+$key),number_format($result_decont_casa_proc[$centru_id]['casa_rbs'] ?? 0.00, 2, '.', ''));
            $worksheet->setCellValue('W'.($rand+$key),number_format($result_decont_casa_proc[$centru_id]['casa_fa_ch'] ?? 0.00, 2, '.', ''));
            $worksheet->setCellValue('X'.($rand+$key),number_format($result_decont_casa_proc[$centru_id]['trezorerie_rbs'] ?? 0.00, 2, '.', ''));
            $worksheet->setCellValue('Y'.($rand+$key),number_format($result_decont_casa_proc[$centru_id]['trezorerie_fa_ch'] ?? 0.00, 2, '.', ''));
        }

        $this->download_send_headers_xls($filename);
        $writer = new Xlsx($spreadsheet);
        $writer->save('php://output');
		die;
    }

    function JSON_DashboardFinanciarCentre_Centru() {
        $centru_id = intval($_GET['centru_id'] ?? 0);
        $responce = new StdClass();
        $responce->records = 0;
        if($centru_id <= 0) {
            return json_encode($responce);
        }
        $today = new DateTime("now");

        $data_start = $data_final = $today;
        if(isset($_GET['data_start']) && isset($_GET['data_final'])){
			try {
                $data_start = DateTime::createFromFormat('d.m.Y', $_GET['data_start']);
                $data_final = DateTime::createFromFormat('d.m.Y', $_GET['data_final']);
                if($data_start && $data_final) {
                    if(intval($data_start->diff($data_final, true)->format('%a')) > 93){
                        $data_start = $data_final = $today;
                    }
                }
			}
			catch (Exception $e){
                $data_start = $data_final = $today;
            }
	    }
        //ramburs generate & ramburs decontate & salarii confirmate & depunere banca
        $query_decont_casa = "SELECT id, created_at, nr_rbs_decontate, rbs_decontate, facturi_decontate, 
            chitante_decontate, cheltuieli_rbs, cheltuieli_fa_ch, nr_rbs_generate, rbs_generate, 
            salarii_confirmate, casa_rbs, casa_fa_ch, trezorerie_rbs, trezorerie_fa_ch
            FROM decont_casa 
            WHERE centru_id = :centru_id and created_at between '{$data_start->format('Y-m-d')}' and '{$data_final->format('Y-m-d')}'
            order by created_at desc
        ";
        $result_decont_casa = $this->db->QFetchRowArray($query_decont_casa, ['centru_id' => $centru_id]);
        $count = 0;
        if(!empty($result_decont_casa) && is_array($result_decont_casa) && count($result_decont_casa) > 0) {
            $count = count($result_decont_casa);
            foreach($result_decont_casa as $key => $row) {
                $responce->rows[$key]['id'] = $row['id'] ?? 0;
                $responce->rows[$key]['cell'] = [
                    $row['created_at'] ?? '',
                    number_format($row['facturi_decontate'] ?? 0.00, 2, '.', ''),
                    number_format($row['chitante_decontate'] ?? 0.00, 2, '.', ''),
                    number_format($row['rbs_decontate'] ?? 0.00, 2, '.', ''),
                    number_format($row['cheltuieli_rbs'] ?? 0.00, 2, '.', ''),
                    number_format($row['cheltuieli_fa_ch'] ?? 0.00, 2, '.', ''),
                    $row['nr_rbs_generate'] ?? 0,
                    number_format($row['rbs_generate'] ?? 0.00, 2, '.', ''),
                    number_format($row['salarii_confirmate'] ?? 0.00, 2, '.', ''),
                    number_format($row['casa_rbs'] ?? 0.00, 2, '.', ''),
                    number_format($row['casa_fa_ch'] ?? 0.00, 2, '.', ''),
                    number_format($row['trezorerie_rbs'] ?? 0.00, 2, '.', ''),
                    number_format($row['trezorerie_fa_ch'] ?? 0.00, 2, '.', ''),
                ];
            }
        }
        $responce->records = $count;
        //$responce->userdata[''] = $count;
        //$responce->userdata[''] = number_format(0, 2, '.', '');
        
        return json_encode($responce);
    }

    function SalariiFinanciarCentre() {
        $this->vars['title_page'] = 'Salarii centre';
        $vars = ['onload_js_version' => $this->config['version']['onload_js_version']];
        return $this->Parse('financiar_salarii.html', $vars);
    }

    function JSON_SalariiFinanciarCentre() {
        $today = date('Y-m-d');
        $responce = new StdClass();
        $responce->records = 0;

        $page = intval($_GET['page'] ?? 1);
		$limit = intval($_GET['rows'] ?? 50);
		$sidx = trim($this->sanitize(empty($_GET['sidx']) ? 1 : $_GET['sidx']));
		$sord = trim($this->sanitize($_GET['sord'] ?? 'asc'));

        $cond = "1 = 1";
        //start generare conditie
        $searchOn = $this->Strip($_GET['_search'] ?? '');
        if ($searchOn == 'true') {
            $searchstr = $this->Strip($_GET['filters'] ?? '');
            $cond .= $this->constructWhere($searchstr);
        }

        $query_count = "SELECT COUNT(ce.id) as nr  
            FROM centre ce
            left join centre mce on ce.mst_financiar_id = mce.id
            WHERE {$cond} and ce.deleted = 0";
            
        $result_count = $this->db->QFetchArray($query_count);
        $count = !empty($result_count['nr']) ? $result_count['nr'] : 0;

        $total_pages = $count > 0 ? ceil($count/$limit) : 0;
        if ($page > $total_pages) $page = $total_pages;
        $start = $limit * $page - $limit;
        if ($start < 0) $start = 0;

        if($count == 0) {
            $responce->page = $page; 
            $responce->total = $total_pages; 
            $responce->records = $count;
            return json_encode($responce);
        }

        $query_centre_agenti="SELECT ce.id, count(ag.cod_ag) as nr_agenti
                FROM centre ce
                left join centre mce on ce.mst_financiar_id = mce.id
                left join agenti ag on ag.cod_centru = ce.id and ag.activ = 1
                group by ce.id";
        $result_centre_agenti_tmp = $this->db->QFetchRowArray($query_centre_agenti);
        $result_centre_agenti = [];
        foreach ($result_centre_agenti_tmp as $key => $row) {
            $result_centre_agenti[$row['id']] = $row['nr_agenti'] ?? 0;
        }

        $query_centre_salarii="SELECT ce.id, ce.nume as centru_nume,
                ce.label as centru_cod, mce.nume as mst_financiar_nume, ce.financiar,
                COALESCE(ces.amount, 0) as salarii_aprobate, DATE(ces.created_at) as data_salarii_aprobate,
                sum(COALESCE(cesl.amount, 0)) as salarii_confirmate
                FROM centre ce
                left join centre mce on ce.mst_financiar_id = mce.id
                left join centre_salarii ces 
                    on ces.centru_id = ce.id and ces.created_at = (
                        select max(created_at) from centre_salarii 
                        where centru_id = ce.id
                    )
                left join centre_salarii_linii cesl on cesl.centre_salarii_id = ces.id
                WHERE {$cond} and ce.deleted = 0
                group by ce.id
                ORDER BY {$sidx} {$sord} LIMIT {$start} , {$limit}";

        $result_centre_salarii = $this->db->QFetchRowArray($query_centre_salarii);
        if (!empty($result_centre_salarii)) {
            foreach ($result_centre_salarii as $key => $row) {
                $responce->rows[$key]['id'] = $row['id'];
                $responce->rows[$key]['cell'] = array(
                    $row['id'],
                    $row['centru_nume'],
                    $row['centru_cod'],
                    $row['mst_financiar_nume'],
                    $row['financiar'],
                    $result_centre_agenti[$row['id']] ?? 0,
                    number_format($row['salarii_aprobate'], 2, '.', ''),
                    $row['data_salarii_aprobate'],
                    number_format($row['salarii_confirmate'], 2, '.', ''),
                );
            }
            $responce->userdata['salarii_aprobate'] = number_format(
                array_sum(array_column($result_centre_salarii, 'salarii_aprobate')), 2, '.', '');
            $responce->userdata['salarii_confirmate'] = number_format(
                array_sum(array_column($result_centre_salarii, 'salarii_confirmate')), 2, '.', '');
        }

        $responce->page = $page; 
        $responce->total = $total_pages; 
        $responce->records = $count;
        
        return json_encode($responce);
    }

    function SalariiFinanciarEdit() {
        $centru_id = intval($_POST['id'] ?? 0);
        $new_salary = intval($_POST['new_salary'] ?? 0);
        $responce = new StdClass();
		$responce->success = 1;
		if($centru_id == 0)
		{
			$responce->success = 0;
			$responce->error = 'Eroare : id-ul centru nu este valid : '.$centru_id;
			return json_encode($responce);
		}

		if($new_salary == 0)
        {
            $responce->success = 0;
            $responce->error = 'Eroare : valoarea introdusa nu este valida : '.$new_salary;
            return json_encode($responce);
        }
        //upsert if created_date < 3 jours and not exists centre_salarii_linii
        $query_centru_salarii="SELECT ces.id, ces.amount as salarii_aprobate, DATE(ces.created_at) as data_salarii_aprobate,
                sum(cesl.amount) as salarii_confirmate
                from centre_salarii ces
                left join centre_salarii_linii cesl on cesl.centre_salarii_id = ces.id
                WHERE ces.centru_id = :centru_id and ces.created_at = (
                        select max(created_at) from centre_salarii 
                        where centru_id = :centru_id
                    )
                group by ces.id";

        $result_centru_salarii = $this->db->QFetchArray($query_centru_salarii, ['centru_id' => $centru_id]);
        //error_log("result_centru_salarii: ".print_r($result_centru_salarii, true));
        if(!empty($result_centru_salarii) && is_array($result_centru_salarii) && count($result_centru_salarii) > 0) {
            $created_at = DateTime::createFromFormat('Y-m-d', $result_centru_salarii['data_salarii_aprobate']);
            //error_log("created_at: ".$created_at->format('Y-m-d'));
            $diff = $created_at->diff(new DateTime("now"), true);
            $days_diff = intval($diff->format('%a'));
            $result_centru_salarii['salarii_confirmate'] = empty($result_centru_salarii['salarii_confirmate']) ? 0 : $result_centru_salarii['salarii_confirmate'];
            //error_log("days_diff: {$days_diff}, salarii_confirmate: {$result_centru_salarii['salarii_confirmate']}");
            if($days_diff <= 3 && $result_centru_salarii['salarii_confirmate'] == 0) {
                //update
                $this->db->QueryUpdate('centre_salarii', ['amount' => $new_salary], "id = {$result_centru_salarii['id']}");
                return json_encode($responce);
            }
            else if($result_centru_salarii['salarii_confirmate'] != $result_centru_salarii['salarii_aprobate']) {
                $responce->success = 0;
                $responce->error = 'Eroare : nu se poate adauga/modifica salariile deoarece exista salarii neconfirmate.';
                return json_encode($responce);
            }
        }

        $this->db->QueryInsert('centre_salarii', ['amount' => $new_salary, 'centru_id' => $centru_id]);

		return json_encode($responce);
    }

    public static function transferTrezorerie($db, $centru_id, $user_id, $decont_casa_id, $casa_rbs, $casa_fa_ch, $today){
        //recupereaza ultima trezorerie
        $trezorerie_rbs = 0.00;
        $trezorerie_fa_ch = 0.00;
        $decont_casa_trezorerie_id = 0;

        $decont_casa_trezorerie = $db->QFetchArray("SELECT id, trezorerie_rbs, trezorerie_fa_ch 
            FROM decont_casa
            WHERE centru_id = {$centru_id} and DATE(created_at) < '{$today}'
            ORDER BY created_at DESC LIMIT 1");
        if(!empty($decont_casa_trezorerie)) {
            $trezorerie_rbs = round($decont_casa_trezorerie['trezorerie_rbs'], 2);
            $trezorerie_fa_ch = round($decont_casa_trezorerie['trezorerie_fa_ch'], 2);
            $decont_casa_trezorerie_id = $decont_casa_trezorerie['id'];
        }

        if($trezorerie_rbs > 0){
            $db->QueryInsert('decont_casa_transfer', [
                'from_decont_casa_id' => $decont_casa_trezorerie_id,
                'to_decont_casa_id' => $decont_casa_id,
                'amount' => $trezorerie_rbs,
                'tip' => 5, //transfer trezorerie rbs
                'user_id' => $user_id,
            ]);
        }
        if($trezorerie_fa_ch > 0){
            $db->QueryInsert('decont_casa_transfer', [
                'from_decont_casa_id' => $decont_casa_trezorerie_id,
                'to_decont_casa_id' => $decont_casa_id,
                'amount' => $trezorerie_fa_ch,
                'tip' => 6, //transfer trezorerie fa+ch
                'user_id' => $user_id,
            ]);
        }

        if($trezorerie_rbs > 0 || $trezorerie_fa_ch > 0){
            //actualizeaza trezoreria in casa curenta
            $db->QueryUpdate('decont_casa', [
                'casa_rbs' => round($trezorerie_rbs + $casa_rbs),
                'casa_fa_ch' => round($trezorerie_fa_ch + $casa_fa_ch),
            ], " id = {$decont_casa_id} ");
        }
        return ['rbs' => $trezorerie_rbs, 'fa_ch' => $trezorerie_fa_ch]; 
    }

    function JSON_FinanciarCentre_Transfer() {
        $today = new DateTime("now");
        $responce = new StdClass();
        $responce->records = 0;

        $data_start = $data_final = $today;
        if(isset($_GET['data_start']) && isset($_GET['data_final'])){
			try {
                $data_start = DateTime::createFromFormat('d.m.Y', $_GET['data_start']);
                $data_final = DateTime::createFromFormat('d.m.Y', $_GET['data_final']);
                if($data_start && $data_final) {
                    if(intval($data_start->diff($data_final, true)->format('%a')) > 93){
                        $data_start = $data_final = $today;
                    }
                }
			}
			catch (Exception $e){
                $data_start = $data_final = $today;
            }
	    }

        $page = intval($_GET['page'] ?? 1);
		$limit = intval($_GET['rows'] ?? 50);
		$sidx = trim($this->sanitize(empty($_GET['sidx']) ? 2 : $_GET['sidx']));
		$sord = trim($this->sanitize($_GET['sord'] ?? 'desc'));

        $cond = '1=1 ';
        $searchOn = $this->Strip($_GET['_search'] ?? '');        
        if ($searchOn == 'true') {
            $searchstr = $this->Strip($_GET['filters'] ?? '');
            $cond .= $this->constructWhere($searchstr);
        }

        $query_count="SELECT COUNT(dct.id) as nr, sum(dct.amount) as total_amount         
            FROM decont_casa_transfer dct
            inner join decont_casa dcfrom on dcfrom.id = dct.from_decont_casa_id
            inner join centre cef on cef.id = dcfrom.centru_id
            inner join decont_casa dcto on dcto.id = dct.to_decont_casa_id
            inner join centre cet on cet.id = dcto.centru_id
            left join users u on u.id = dct.user_id
            where {$cond} 
            and dct.created_at between '{$data_start->format('Y-m-d')}' and '{$data_final->format('Y-m-d')}'
        ";
        $result_count = $this->db->QFetchArray($query_count);
        $count = !empty($result_count['nr']) ? $result_count['nr'] : 0;

        $total_pages = $count > 0 ? ceil($count/$limit) : 0;
        if ($page > $total_pages) $page = $total_pages;
        $start = $limit * $page - $limit;
        if ($start < 0) $start = 0;

        if($count == 0) {
            $responce->page = $page; 
            $responce->total = $total_pages; 
            $responce->records = $count;
            $responce->userdata['amount'] = number_format(0, 2, '.', '');
            return json_encode($responce);
        }

        $query = "SELECT dct.id, dct.created_at, cef.label as from_centru, cet.label as to_centru,
            dct.amount, dct.tip, u.user
            FROM decont_casa_transfer dct
            inner join decont_casa dcfrom on dcfrom.id = dct.from_decont_casa_id
            inner join centre cef on cef.id = dcfrom.centru_id
            inner join decont_casa dcto on dcto.id = dct.to_decont_casa_id
            inner join centre cet on cet.id = dcto.centru_id
            left join users u on u.id = dct.user_id
            where {$cond} 
            and dct.created_at between '{$data_start->format('Y-m-d')}' and '{$data_final->format('Y-m-d')}'
            ORDER BY {$sidx} {$sord} LIMIT {$start},{$limit}";

        //error_log($query);
        
        $sql = $this->db->QFetchRowArray($query);
        if (!empty($sql)) {
            foreach ($sql as $key => $row) {
                $responce->rows[$key]['id'] = $row['id'];
                $responce->rows[$key]['cell'] = array(
                    $row['tip'],
                    $row['created_at'],
                    $row['from_centru'],
                    $row['to_centru'],
                    number_format($row['amount'], 2, '.', ''),
                    $row['user'],
                );
            }
        }

        $responce->page = $page; 
        $responce->total = $total_pages; 
        $responce->records = $count;
        $responce->userdata['amount'] = number_format($result_count['total_amount'], 2, '.', '');

        return json_encode($responce);
    }
}
