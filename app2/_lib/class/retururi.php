<?php

/**
 * W o r k s p a c e
 *
 */
class ModulRetururiDocumente extends BackEnd {

    public $final_result;
    public $action_module;
    public $page_prefix;
    public $table;

    /**
     * The constructor for the 'Workspace' class
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

        $this->vars['title_page'] = 'Expeditii cu retur Doc/NT';
        $this->page_prefix = 'retururi_';

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
    function Actions() {
        $this->final_result = '';

        // A D M I N
        if (isset($_GET['logout']))
            $this->Logout();

        else if (!empty($this->user_profile))
            $this->ActionsNivelAcces();
        else
            $this->final_result = $this->PageNotFound();
        // R E S U L T
        return $this->final_result;
    }


    function ActionsNivelAcces() {
		$this->user_rights = $this->GetDrepturiUtilizator($this->user_profile);
        $arr = $this->GenerateArr();
        //nivel acces 10
		$flag = 0;
		if (in_array("vizualizare_rambursuri", $this->user_rights) || $this->user_profile == 10){

			if (isset($arr[1]) && $arr[1] == 'vizualizare')
	            $this->final_result = $this->Vizualizare();
			else if(isset($arr[1]) && $arr[1] == 'json' && isset($arr[2]) && $arr[2]=='vizualizare')
				echo $this->JSON_Vizualizare();
			else if (isset($arr[1]) && $arr[1] == 'urmarire')
				$this->final_result = $this->Urmarire();
			else if(isset($arr[1]) && $arr[1] == 'json'  && isset($arr[2]) && $arr[2]=='urmarire')
				echo $this->JSON_Urmarire();

	        else if (isset($arr[1]) && $arr[1] == 'detalii_expeditie')
	            echo $this->DetaliiExpeditie();
			else if (isset($arr[1]) && $arr[1] == 'export_urmarire')
	            echo $this->ExportUrmarire();
			$flag=1;
		}
		if (in_array("urmarire_rambursuri", $this->user_rights) || $this->user_profile == 10){

			if (isset($arr[1]) && $arr[1] == 'modificare_expeditie')
	            echo $this->ModificareStatusRetururiExpeditie(isset($arr[2])?$arr[2]:0,isset($arr[3])?$arr[3]:0);

			$flag=1;
		}
        if(empty($flag))
            $this->final_result = $this->PageNotFound();
    }

 //-------------------------------- functii ----------------------------------------





/*/////////////////////////////////////////////////////////////
				 URMARIRE RAMBURSURI
/////////////////////////////////////////////////////////////*/


	function ConditieRetururi($type=0){
		$status = intval($_REQUEST['status_ret'] ?? 1);

        if(isset($_REQUEST['data_start']) && isset($_REQUEST['data_final'])){
            $data_start = $this->TransformDate($_REQUEST['data_start']);
            $data_final = $this->TransformDate($_REQUEST['data_final']);
        }
        else
        	$data_start = $data_final = date("Y-m-d");

        $interval_data = "a.data_expeditie between '".$data_start."' AND '".$data_final."'";

		//NT+DOC+AMB - toate
		if($status==1){
			$cond = " FROM {$this->tables['exp_prelucrate']} a
			LEFT JOIN {$this->tables['exp_prelucrate']} b on b.referire = a.expeditie and b.tip_exp in (1,2,6)
			left join clienti cle on cle.cod_cl = a.expeditor_id
			left join clienti cld on cld.cod_cl = a.destinatar_id
			LEFT JOIN zones clez ON clez.id = cle.zona_id
        	LEFT JOIN centre clec on clec.id = clez.centru_id
			LEFT JOIN zones cldz ON cldz.id = cld.zona_id
        	LEFT JOIN centre cldc on cldc.id = cldz.centru_id
			left join localitati lce ON lce.cod_lc = cle.cod_lc
			left join localitati lcd ON lcd.cod_lc = cld.cod_lc
			left join centre cee on cee.id = lce.cod_centru
			left join centre ced on ced.id = lcd.cod_centru
			WHERE {$interval_data} and (a.ret_nt=1 OR a.ret_doc=1 OR a.ret_amb=1)";
		}

		//NT - in derulare
		else if($status==2){
			$cond = " FROM {$this->tables['exp_prelucrate']} a
			LEFT JOIN {$this->tables['exp_prelucrate']} b on b.referire = a.expeditie and b.tip_exp = 1
			left join clienti cle on cle.cod_cl = a.expeditor_id
			left join clienti cld on cld.cod_cl = a.destinatar_id
			LEFT JOIN zones clez ON clez.id = cle.zona_id
        	LEFT JOIN centre clec on clec.id = clez.centru_id
			LEFT JOIN zones cldz ON cldz.id = cld.zona_id
        	LEFT JOIN centre cldc on cldc.id = cldz.centru_id
			left join localitati lce ON lce.cod_lc = cle.cod_lc
			left join localitati lcd ON lcd.cod_lc = cld.cod_lc
			left join centre cee on cee.id = lce.cod_centru
			left join centre ced on ced.id = lcd.cod_centru
			WHERE {$interval_data} AND a.ret_nt=1 AND a.status_retururi=0";
		}

		//NT - cu retur
		else if($status==3){
			$cond = " FROM {$this->tables['exp_prelucrate']} a
			INNER JOIN {$this->tables['exp_prelucrate']} b on b.referire = a.expeditie and b.tip_exp = 1
			left join clienti cle on cle.cod_cl = a.expeditor_id
			left join clienti cld on cld.cod_cl = a.destinatar_id
			LEFT JOIN zones clez ON clez.id = cle.zona_id
        	LEFT JOIN centre clec on clec.id = clez.centru_id
			LEFT JOIN zones cldz ON cldz.id = cld.zona_id
        	LEFT JOIN centre cldc on cldc.id = cldz.centru_id
			left join localitati lce ON lce.cod_lc = cle.cod_lc
			left join localitati lcd ON lcd.cod_lc = cld.cod_lc
			left join centre cee on cee.id = lce.cod_centru
			left join centre ced on ced.id = lcd.cod_centru
			WHERE {$interval_data} AND b.data_expeditie >= '".$data_start."' AND a.ret_nt=1 AND a.status_retururi=0";
		}

		//NT - cu retur livrat
		if($status==4){
			$cond = " FROM {$this->tables['exp_prelucrate']} a
			INNER JOIN {$this->tables['exp_prelucrate']} b on b.referire = a.expeditie and b.tip_exp = 1
			left join clienti cle on cle.cod_cl = a.expeditor_id
			left join clienti cld on cld.cod_cl = a.destinatar_id
			LEFT JOIN zones clez ON clez.id = cle.zona_id
        	LEFT JOIN centre clec on clec.id = clez.centru_id
			LEFT JOIN zones cldz ON cldz.id = cld.zona_id
        	LEFT JOIN centre cldc on cldc.id = cldz.centru_id
			left join localitati lce ON lce.cod_lc = cle.cod_lc
			left join localitati lcd ON lcd.cod_lc = cld.cod_lc
			left join centre cee on cee.id = lce.cod_centru
			left join centre ced on ced.id = lcd.cod_centru
			WHERE {$interval_data} AND b.data_expeditie >= '".$data_start."' AND b.operatiune like 'Livrat' AND b.tip_exp = 1 AND a.ret_nt = 1 AND a.status_retururi=0";
		}

		//NT - fara retur
		if($status==5){
			$cond = " FROM {$this->tables['exp_prelucrate']} a
			LEFT JOIN {$this->tables['exp_prelucrate']} b on b.referire = a.expeditie and b.tip_exp = 1
			left join clienti cle on cle.cod_cl = a.expeditor_id
			left join clienti cld on cld.cod_cl = a.destinatar_id
			LEFT JOIN zones clez ON clez.id = cle.zona_id
        	LEFT JOIN centre clec on clec.id = clez.centru_id
			LEFT JOIN zones cldz ON cldz.id = cld.zona_id
        	LEFT JOIN centre cldc on cldc.id = cldz.centru_id
			left join localitati lce ON lce.cod_lc = cle.cod_lc
			left join localitati lcd ON lcd.cod_lc = cld.cod_lc
			left join centre cee on cee.id = lce.cod_centru
			left join centre ced on ced.id = lcd.cod_centru
			WHERE {$interval_data} AND b.referire IS NULL AND a.ret_nt=1 AND a.status_retururi=0";
		}

		//NT - inchise
		if($status==6){
			$cond = " FROM {$this->tables['exp_prelucrate']} a
			INNER JOIN {$this->tables['exp_prelucrate']} b on b.referire = a.expeditie and b.tip_exp = 1
			left join clienti cle on cle.cod_cl = a.expeditor_id
			left join clienti cld on cld.cod_cl = a.destinatar_id
			LEFT JOIN zones clez ON clez.id = cle.zona_id
        	LEFT JOIN centre clec on clec.id = clez.centru_id
			LEFT JOIN zones cldz ON cldz.id = cld.zona_id
        	LEFT JOIN centre cldc on cldc.id = cldz.centru_id
			left join localitati lce ON lce.cod_lc = cle.cod_lc
			left join localitati lcd ON lcd.cod_lc = cld.cod_lc
			left join centre cee on cee.id = lce.cod_centru
			left join centre ced on ced.id = lcd.cod_centru
			WHERE {$interval_data} AND b.data_expeditie >= '".$data_start."' AND b.operatiune like 'Livrat' AND a.ret_nt=1 AND a.status_retururi in (1,3)";
		}

		//DOC - in derulare
		if($status==7){
			$cond = " FROM {$this->tables['exp_prelucrate']} a
			LEFT JOIN {$this->tables['exp_prelucrate']} b on b.referire = a.expeditie and b.tip_exp = 2
			left join clienti cle on cle.cod_cl = a.expeditor_id
			left join clienti cld on cld.cod_cl = a.destinatar_id
			LEFT JOIN zones clez ON clez.id = cle.zona_id
        	LEFT JOIN centre clec on clec.id = clez.centru_id
			LEFT JOIN zones cldz ON cldz.id = cld.zona_id
        	LEFT JOIN centre cldc on cldc.id = cldz.centru_id
			left join localitati lce ON lce.cod_lc = cle.cod_lc
			left join localitati lcd ON lcd.cod_lc = cld.cod_lc
			left join centre cee on cee.id = lce.cod_centru
			left join centre ced on ced.id = lcd.cod_centru
			WHERE {$interval_data}
			AND a.ret_doc=1 AND a.status_retururi=0";
		}

		//DOC - cu retur
		if($status==8){
			$cond = " FROM {$this->tables['exp_prelucrate']} a
			INNER JOIN {$this->tables['exp_prelucrate']} b on b.referire = a.expeditie and b.tip_exp = 2
			left join clienti cle on cle.cod_cl = a.expeditor_id
			left join clienti cld on cld.cod_cl = a.destinatar_id
			LEFT JOIN zones clez ON clez.id = cle.zona_id
        	LEFT JOIN centre clec on clec.id = clez.centru_id
			LEFT JOIN zones cldz ON cldz.id = cld.zona_id
        	LEFT JOIN centre cldc on cldc.id = cldz.centru_id
			left join localitati lce ON lce.cod_lc = cle.cod_lc
			left join localitati lcd ON lcd.cod_lc = cld.cod_lc
			left join centre cee on cee.id = lce.cod_centru
			left join centre ced on ced.id = lcd.cod_centru
			WHERE {$interval_data} AND b.data_expeditie >= '".$data_start."' AND a.ret_doc=1 AND a.status_retururi=0";
		}

		//DOC - cu retur livrat
		if($status==9){
			$cond = " FROM {$this->tables['exp_prelucrate']} a
			INNER JOIN {$this->tables['exp_prelucrate']} b on b.referire = a.expeditie and b.tip_exp = 2
			left join clienti cle on cle.cod_cl = a.expeditor_id
			left join clienti cld on cld.cod_cl = a.destinatar_id
			LEFT JOIN zones clez ON clez.id = cle.zona_id
        	LEFT JOIN centre clec on clec.id = clez.centru_id
			LEFT JOIN zones cldz ON cldz.id = cld.zona_id
        	LEFT JOIN centre cldc on cldc.id = cldz.centru_id
			left join localitati lce ON lce.cod_lc = cle.cod_lc
			left join localitati lcd ON lcd.cod_lc = cld.cod_lc
			left join centre cee on cee.id = lce.cod_centru
			left join centre ced on ced.id = lcd.cod_centru
			WHERE {$interval_data} AND b.data_expeditie >= '".$data_start."' AND b.operatiune like 'Livrat' AND a.ret_doc=1 AND a.status_retururi=0";
		}

		//DOC - fara retur
		if($status==10){
			$cond = " FROM {$this->tables['exp_prelucrate']} a
			LEFT JOIN {$this->tables['exp_prelucrate']} b on b.referire = a.expeditie and b.tip_exp = 2
			left join clienti cle on cle.cod_cl = a.expeditor_id
			left join clienti cld on cld.cod_cl = a.destinatar_id
			LEFT JOIN zones clez ON clez.id = cle.zona_id
        	LEFT JOIN centre clec on clec.id = clez.centru_id
			LEFT JOIN zones cldz ON cldz.id = cld.zona_id
        	LEFT JOIN centre cldc on cldc.id = cldz.centru_id
			left join localitati lce ON lce.cod_lc = cle.cod_lc
			left join localitati lcd ON lcd.cod_lc = cld.cod_lc
			left join centre cee on cee.id = lce.cod_centru
			left join centre ced on ced.id = lcd.cod_centru
			WHERE {$interval_data} AND b.referire IS NULL AND a.ret_doc=1 AND a.status_retururi=0";
		}

		//DOC - inchise
		if($status==11){
			$cond = " FROM {$this->tables['exp_prelucrate']} a
			INNER JOIN {$this->tables['exp_prelucrate']} b on b.referire = a.expeditie and b.tip_exp = 2
			left join clienti cle on cle.cod_cl = a.expeditor_id
			left join clienti cld on cld.cod_cl = a.destinatar_id
			LEFT JOIN zones clez ON clez.id = cle.zona_id
        	LEFT JOIN centre clec on clec.id = clez.centru_id
			LEFT JOIN zones cldz ON cldz.id = cld.zona_id
        	LEFT JOIN centre cldc on cldc.id = cldz.centru_id
			left join localitati lce ON lce.cod_lc = cle.cod_lc
			left join localitati lcd ON lcd.cod_lc = cld.cod_lc
			left join centre cee on cee.id = lce.cod_centru
			left join centre ced on ced.id = lcd.cod_centru
			WHERE {$interval_data} AND b.data_expeditie >= '".$data_start."' AND b.operatiune like 'Livrat' AND a.ret_doc=1 AND a.status_retururi in (2,3)";
		}

		//NT - returnate
		if($status==12){
			$cond = " FROM {$this->tables['exp_prelucrate']} a
			INNER JOIN {$this->tables['exp_prelucrate']} b on b.referire = a.expeditie and b.tip_exp = 5
			left join clienti cle on cle.cod_cl = a.expeditor_id
			left join clienti cld on cld.cod_cl = a.destinatar_id
			LEFT JOIN zones clez ON clez.id = cle.zona_id
        	LEFT JOIN centre clec on clec.id = clez.centru_id
			LEFT JOIN zones cldz ON cldz.id = cld.zona_id
        	LEFT JOIN centre cldc on cldc.id = cldz.centru_id
			left join localitati lce ON lce.cod_lc = cle.cod_lc
			left join localitati lcd ON lcd.cod_lc = cld.cod_lc
			left join centre cee on cee.id = lce.cod_centru
			left join centre ced on ced.id = lcd.cod_centru
			WHERE {$interval_data} AND b.data_expeditie >= '".$data_start."' AND a.ret_nt=1";
		}

		//DOC - returnate
		if($status==13){
			$cond = " FROM {$this->tables['exp_prelucrate']} a
			INNER JOIN {$this->tables['exp_prelucrate']} b on b.referire = a.expeditie and b.tip_exp = 5
			left join clienti cle on cle.cod_cl = a.expeditor_id
			left join clienti cld on cld.cod_cl = a.destinatar_id
			LEFT JOIN zones clez ON clez.id = cle.zona_id
        	LEFT JOIN centre clec on clec.id = clez.centru_id
			LEFT JOIN zones cldz ON cldz.id = cld.zona_id
        	LEFT JOIN centre cldc on cldc.id = cldz.centru_id
			left join localitati lce ON lce.cod_lc = cle.cod_lc
			left join localitati lcd ON lcd.cod_lc = cld.cod_lc
			left join centre cee on cee.id = lce.cod_centru
			left join centre ced on ced.id = lcd.cod_centru
			WHERE {$interval_data} AND b.data_expeditie >= '".$data_start."' AND a.ret_doc=1";
		}

		//AMB - toate
		else if(intval($status ?? 0)==15){
			$cond = " FROM {$this->tables['exp_prelucrate']} a
			LEFT JOIN {$this->tables['exp_prelucrate']} b on b.referire = a.expeditie and b.tip_exp = 6
			left join clienti cle on cle.cod_cl = a.expeditor_id
			left join clienti cld on cld.cod_cl = a.destinatar_id
			LEFT JOIN zones clez ON clez.id = cle.zona_id
        	LEFT JOIN centre clec on clec.id = clez.centru_id
			LEFT JOIN zones cldz ON cldz.id = cld.zona_id
        	LEFT JOIN centre cldc on cldc.id = cldz.centru_id
			left join localitati lce ON lce.cod_lc = cle.cod_lc
			left join localitati lcd ON lcd.cod_lc = cld.cod_lc
			left join centre cee on cee.id = lce.cod_centru
			left join centre ced on ced.id = lcd.cod_centru
			WHERE {$interval_data} AND a.ret_amb=1";
		}

		//AMB - cu retur
		else if(intval($status ?? 0)==16){
			$cond = " FROM {$this->tables['exp_prelucrate']} a
			INNER JOIN {$this->tables['exp_prelucrate']} b on b.referire = a.expeditie and b.tip_exp = 6
			left join clienti cle on cle.cod_cl = a.expeditor_id
			left join clienti cld on cld.cod_cl = a.destinatar_id
			LEFT JOIN zones clez ON clez.id = cle.zona_id
        	LEFT JOIN centre clec on clec.id = clez.centru_id
			LEFT JOIN zones cldz ON cldz.id = cld.zona_id
        	LEFT JOIN centre cldc on cldc.id = cldz.centru_id
			left join localitati lce ON lce.cod_lc = cle.cod_lc
			left join localitati lcd ON lcd.cod_lc = cld.cod_lc
			left join centre cee on cee.id = lce.cod_centru
			left join centre ced on ced.id = lcd.cod_centru
			WHERE {$interval_data} AND b.data_expeditie >= '".$data_start."' AND a.ret_amb=1";
		}

		//AMB - cu retur livrat
		if(intval($status ?? 0)==17){
			$cond = " FROM {$this->tables['exp_prelucrate']} a
			INNER JOIN {$this->tables['exp_prelucrate']} b on b.referire = a.expeditie and b.tip_exp = 6
			left join clienti cle on cle.cod_cl = a.expeditor_id
			left join clienti cld on cld.cod_cl = a.destinatar_id
			LEFT JOIN zones clez ON clez.id = cle.zona_id
        	LEFT JOIN centre clec on clec.id = clez.centru_id
			LEFT JOIN zones cldz ON cldz.id = cld.zona_id
        	LEFT JOIN centre cldc on cldc.id = cldz.centru_id
			left join localitati lce ON lce.cod_lc = cle.cod_lc
			left join localitati lcd ON lcd.cod_lc = cld.cod_lc
			left join centre cee on cee.id = lce.cod_centru
			left join centre ced on ced.id = lcd.cod_centru
			WHERE {$interval_data} AND b.data_expeditie >= '".$data_start."' b.operatiune like 'Livrat' AND a.ret_amb = 1";
		}

		//AMB - fara retur
		if(intval($status ?? 0)==18){
			$cond = " FROM {$this->tables['exp_prelucrate']} a
			LEFT JOIN {$this->tables['exp_prelucrate']} b on b.referire = a.expeditie and b.tip_exp = 6
			left join clienti cle on cle.cod_cl = a.expeditor_id
			left join clienti cld on cld.cod_cl = a.destinatar_id
			LEFT JOIN zones clez ON clez.id = cle.zona_id
        	LEFT JOIN centre clec on clec.id = clez.centru_id
			LEFT JOIN zones cldz ON cldz.id = cld.zona_id
        	LEFT JOIN centre cldc on cldc.id = cldz.centru_id
			left join localitati lce ON lce.cod_lc = cle.cod_lc
			left join localitati lcd ON lcd.cod_lc = cld.cod_lc
			left join centre cee on cee.id = lce.cod_centru
			left join centre ced on ced.id = lcd.cod_centru
			WHERE {$interval_data} AND b.referire is NULL AND a.ret_amb=1";
		}

		//AMB - returnate
		if(intval($status ?? 0)==19){
			$cond = " FROM {$this->tables['exp_prelucrate']} a
			INNER JOIN {$this->tables['exp_prelucrate']} b on b.referire = a.expeditie and b.tip_exp = 5
			left join clienti cle on cle.cod_cl = a.expeditor_id
			left join clienti cld on cld.cod_cl = a.destinatar_id
			LEFT JOIN zones clez ON clez.id = cle.zona_id
        	LEFT JOIN centre clec on clec.id = clez.centru_id
			LEFT JOIN zones cldz ON cldz.id = cld.zona_id
        	LEFT JOIN centre cldc on cldc.id = cldz.centru_id
			left join localitati lce ON lce.cod_lc = cle.cod_lc
			left join localitati lcd ON lcd.cod_lc = cld.cod_lc
			left join centre cee on cee.id = lce.cod_centru
			left join centre ced on ced.id = lcd.cod_centru
			WHERE {$interval_data} AND b.data_expeditie >= '".$data_start."' AND a.ret_amb=1";
		}

		$cond .= " and a.anulata = 0";

		return $cond;
	}


    function Urmarire() {
        $this->vars['title_page'] = 'Urmarire Expeditii cu retur Doc/NT';
        $vars = [];
        $vars['data_start'] = date('d.m.Y');
        $vars['data_final'] = date('d.m.Y');

        return $this->Parse($this->page_prefix . 'urmarire.html', $vars);
    }

	function JSON_Urmarire() {
		$responce = new StdClass();
		$cond = $this->ConditieRetururi();

		//start generare conditie
        $searchOn = $this->Strip($_REQUEST['_search']);
        if ($searchOn == 'true') {
            $searchstr = $this->Strip($_REQUEST['filters']);
            $cond .= $this->r_constructWhere($searchstr);
        }

        $page = intval($_REQUEST['page'] ?? 1);
		$limit = intval($_REQUEST['rows'] ?? 20);
		$sidx = trim($this->sanitize($_REQUEST['sidx'] ?? 1));
		$sord = trim($this->sanitize($_REQUEST['sord'] ?? 'asc'));

		$query = "SELECT COUNT(a.cod_expeditie) as nr {$cond}";
		//echo $query;die;
        $result = $this->db->QFetchArray($query);
        $count = !empty($result['nr']) ? $result['nr'] : 0;

        if( $count >0 ) {$total_pages = ceil($count/$limit); }
        else { $total_pages = 0; }
        if ($page > $total_pages) $page=$total_pages;
        if ($limit<0) $limit = 0;
        $start = $limit*$page - $limit; // do not put $limit*($page - 1)
        if ($start<0) $start = 0;
        $query = "SELECT a.expeditie, a.data_expeditie, a.tip_exp, a.ret_nt, a.ret_doc, a.ret_amb, a.status_retururi,
			cle.nume as expeditor, cld.nume as destinatar,
			IF(cle.zona_id > 0 and clec.id > 0, clec.label, cee.label) as expeditor_centru_cod, 
			IF(cld.zona_id > 0 and cldc.id > 0, cldc.label, ced.label) as destinatar_centru_cod,
			group_concat(b.expeditie) as bref, group_concat(b.data_expeditie) as bdata_expeditie
			{$cond}
			GROUP BY a.expeditie
            ORDER BY " . $sidx . " " . $sord . " LIMIT " . $start . " , " . $limit;
        //error_log($query);
        $sql = $this->db->QFetchRowArray($query);
        if (!empty($sql)) {
			//$total_status=[];
            foreach ($sql as $key => $row) {
                $responce->rows[$key]['id'] = $row['expeditie'];
				//$total_status[$row['status_retururi']]++;
				$arr_servicii = [];
				if(!empty($row['ret_nt'])) $arr_servicii[] = 'NT';
				if(!empty($row['ret_doc'])) $arr_servicii[] = 'DOC';
				if(!empty($row['ret_amb'])) $arr_servicii[] = 'AMB';
                $responce->rows[$key]['cell'] = array($row['destinatar_centru_cod'], strtoupper($row['expeditor_centru_cod']),
					strtoupper($row['expeditie']),strtoupper($row['data_expeditie']),strtoupper($row['bref']),
					strtoupper($row['bdata_expeditie']),implode(',',$arr_servicii),$row['status_retururi'],
					strtoupper($row['expeditor']),strtoupper($row['destinatar']) );
            }
        }
		$responce->page = $page;
        $responce->total = $total_pages;
        $responce->records = $count;

        return json_encode($responce);
    }



    function Vizualizare() {
        $this->vars['title_page'] = 'Vizualizare Expeditii cu retur Doc/NT';
        $vars = [];
        $vars['data_start'] = date('d.m.Y');
        $vars['data_final'] = date('d.m.Y');

        return $this->Parse($this->page_prefix . 'vizualizare.html', $vars);
    }

	function JSON_Vizualizare() {
		$responce = new StdClass();
		$cond = $this->ConditieRetururi();

		//start generare conditie
        $searchOn = $this->Strip($_REQUEST['_search']);
        if ($searchOn == 'true') {
            $searchstr = $this->Strip($_REQUEST['filters']);
            $cond .= $this->r_constructWhere($searchstr);
        }

		$cond = preg_replace("/ced.nume'/i", "IF(cld.zona_id > 0 and cldc.id > 0, cldc.nume, ced.nume)", $cond);

        $page = intval($_REQUEST['page'] ?? 1);
		$limit = intval($_REQUEST['rows'] ?? 20);
		$sidx = trim($this->sanitize($_REQUEST['sidx'] ?? 1));
		$sord = trim($this->sanitize($_REQUEST['sord'] ?? 'asc'));

		$query = "SELECT COUNT(a.cod_expeditie) as nr {$cond}";
			//echo $query;die;
        $result = $this->db->QFetchArray($query);
        $count = !empty($result['nr']) ? $result['nr'] : 0;

        if( $count >0 ) {$total_pages = ceil($count/$limit); }
        else { $total_pages = 0; }
        if ($page > $total_pages) $page=$total_pages;
        if ($limit<0) $limit = 0;
        $start = $limit*$page - $limit; // do not put $limit*($page - 1)
        if ($start<0) $start = 0;
        $query = "SELECT a.expeditie, a.data_expeditie, a.tip_exp, a.ret_nt, a.ret_doc, a.ret_amb, a.status_retururi,
			cle.nume as expeditor, cld.nume as destinatar,
			IF(cle.zona_id > 0 and clec.id > 0, clec.label, cee.label) as expeditor_centru_cod, 
			IF(cld.zona_id > 0 and cldc.id > 0, cldc.label, ced.label) as destinatar_centru_cod,
			group_concat(b.expeditie) as bref, group_concat(b.data_expeditie) as bdata_expeditie 
			{$cond}
			GROUP BY a.expeditie
            ORDER BY " . $sidx . " " . $sord . " LIMIT " . $start . " , " . $limit . ";";
            //echo $query;die;
        $sql = $this->db->QFetchRowArray($query);
        if (!empty($sql)) {
            foreach ($sql as $key => $row) {
                $responce->rows[$key]['id'] = $row['expeditie'];
				$arr_servicii = [];
				if(!empty($row['ret_nt'])) $arr_servicii[] = 'NT';
				if(!empty($row['ret_doc'])) $arr_servicii[] = 'DOC';
				if(!empty($row['ret_amb'])) $arr_servicii[] = 'AMB';
                $responce->rows[$key]['cell'] = array(strtoupper($row['destinatar_centru_cod']), strtoupper($row['expeditor_centru_cod']),$row['expeditie'],$row['data_expeditie'],strtoupper($row['bref']),strtoupper($row['bdata_expeditie']),implode(',',$arr_servicii),$row['status_retururi'],strtoupper($row['expeditor']),strtoupper($row['destinatar']) );
            }
        }
		$responce->page = $page;
        $responce->total = $total_pages;
        $responce->records = $count;

        return json_encode($responce);
    }


	function DetaliiExpeditie(){
		if(empty($_POST['exp']))
			return '';
		$id = $_POST['exp'];
		$query="SELECT expeditie, operatiune FROM {$this->tables['exp_prelucrate']} WHERE referire={$id} and anulata = 0 LIMIT 1";
		//error_log($query);
        $sql = $this->db->QFetchArray($query);
		if(empty($sql)) return '';

		return '<a href="javascript:;" onclick="AfisareDetaliiExpeditieCuID('.$sql['expeditie'].');">'.$sql['expeditie'].'</a> - '.$sql['operatiune'];
	}

	function ModificareStatusRetururiExpeditie($exp, $newStatusRet){
		$exp = intval($exp);
		$newStatusRet = intval($newStatusRet);
		if(empty($exp) || $newStatusRet >= 4) return false;

		$query = "SELECT cod_expeditie as initialaId, status_retururi as oldStatusRet
			FROM {$this->tables['exp_prelucrate']}
			WHERE expeditie = {$exp} and anulata = 0 and tip_exp = 0 order by cod_expeditie desc";
		$sql = $this->db->QFetchArray($query);
		if(empty($sql['initialaId'])) return false;
		return $this->setStatusRetur($sql['initialaId'], $sql['oldStatusRet'], $newStatusRet);
	}

    function ExportUrmarire() {

		$cond = $this->ConditieRetururi();
   		$vars = [];

		//start generare conditie
        $searchOn = $this->Strip($_POST['_search'] ?? false);
        if ($searchOn == 'true') {
            $searchstr = $this->Strip(base64_decode($_POST['filters']));
            $cond .= $this->r_constructWhere($searchstr);
        }

		$arr=[];
		$arr[0][1] = 'Nr. NT';
		$arr[0][2] = 'Data colectarii';
		$arr[0][3] = 'Expeditor';
		$arr[0][4] = 'Centru expeditor';
		$arr[0][5] = 'Destinatar';
		$arr[0][6] = 'Centru destinatar';
		$arr[0][7] = 'Return NT';
		$arr[0][8] = 'Retur DOC';
		$arr[0][9] = 'Retur AMB';
		$arr[0][10] = 'Status';
		$arr[0][11] = 'NT Referinta';

        $query = "SELECT a.expeditie,a.data_expeditie,a.status_retururi,a.ret_nt,a.ret_doc,a.ret_amb,a.moneda,
			group_concat(b.expeditie) as bref, group_concat(b.data_expeditie) as bdata_expeditie,
			lce.nume_lc as expeditor_localitate, IF(cle.zona_id > 0 and clec.id > 0, clec.label, cee.label) as expeditor_centru_cod,
			lcd.nume_lc as destinatar_localitate, IF(cld.zona_id > 0 and cldc.id > 0, cldc.label, ced.label) as destinatar_centru_cod,
			cle.nume as expeditor, cld.nume as destinatar
			{$cond}
			GROUP BY a.expeditie
			ORDER BY destinatar_centru_cod, a.data_expeditie, cld.nume ASC";
			//echo $query;die;
        $sql = $this->db->QFetchRowArray($query);
        //compun raspunsul
        if (!empty($sql)) {
        	$rand = 2;
            foreach ($sql as $key => $row) {

                $row['data_expeditie'] = $this->CreateDate($row['data_expeditie']);

			   	$status = array(0 => 'In derulare', 1 => 'Inchis NT', 2 => 'Inchis DOC', 3 => 'Inchis NT+DOC');

				if(!empty($row['ret_nt'])) $row['ret_nt'] = 'DA';
				else  $row['ret_nt'] = 'NU';

				if(!empty($row['ret_doc'])) $row['ret_doc'] = 'DA';
				else  $row['ret_doc'] = 'NU';

				if(!empty($row['ret_amb'])) $row['ret_amb'] = 'DA';
				else  $row['ret_amb'] = 'NU';

				$arr[($key+1)][1] = $row['expeditie'];
				$arr[($key+1)][2] = $row['data_expeditie'];
				$arr[($key+1)][3] = $row['expeditor'].'('.$row['expeditor_localitate'].')';
				$arr[($key+1)][4] = $row['expeditor_centru_cod'];
				$arr[($key+1)][5] = $row['destinatar'].'('.$row['destinatar_localitate'].')';
				$arr[($key+1)][6] = $row['destinatar_centru_cod'];
				$arr[($key+1)][7] = $row['ret_nt'];
				$arr[($key+1)][8] = $row['ret_doc'];
				$arr[($key+1)][9] = $row['ret_amb'];
				$arr[($key+1)][10] = $status[$row['status_retururi']];
				$arr[($key+1)][11] = $row['bref'];
            }
        }
    	$this->download_send_headers("data_export_" . date("Y-m-d") . ".csv");
		echo $this->array2csv($arr, "2048M");die;
  	}

	function r_constructWhere($s) {
	     $qwery = "";
	      //['eq','ne','lt','le','gt','ge','bw','bn','in','ni','ew','en','cn','nc']
	       $qopers = array(
	          'eq' => " = ",
	          'ne' => " <> ",
	          'lt' => " < ",
	          'le' => " <= ",
	          'gt' => " > ",
	          'ge' => " >= ",
	          'bw' => " LIKE ",
	          'bn' => " NOT LIKE ",
	          'in' => " IN ",
	          'ni' => " NOT IN ",
	          'ew' => " LIKE ",
	          'en' => " NOT LIKE ",
	          'cn' => " LIKE ",
	          'nc' => " NOT LIKE ");
	      if ($s) {
	         $jsona = json_decode($s, true);
	          if (is_array($jsona)) {
		            $gopr = $jsona['groupOp'];
		            $rules = $jsona['rules'];
		            $i = 0;
		            foreach ($rules as $key => $val) {
		                $field = $this->sanitize($val['field']);
		                $op = $this->sanitize($val['op']);
		                $v = $val['data'];
		                if (isset($v) && $op) {
  			               $i++;
  			               	if($field == 'a.tip_exp' && $v == 1) {$field = 'a.ret_nt';}
  			               	else if($field == 'a.tip_exp' && $v == 2) {$field = 'a.ret_doc'; $v = 1;}
							else if($field == 'a.tip_exp' && $v == 6) {$field = 'a.ret_amb'; $v = 1;}
  			               	else if($field == 'a.tip_exp' && $v == 3) {$field = 'a.ret_nt=1 and a.ret_doc'; $v = 1;}
			                // ToSql in this case is absolutley needed
			                $v = $this->ToSql($field, $op, $v);
			                if ($i == 1)
			                   $qwery = " AND ";
			                else
			                   $qwery .= " " . $gopr . " ";
			                switch ($op) {
			                   // in need other thing
			                  case 'in' :
			                  case 'ni' :
				                    $qwery .= ' '. $field.' ' . $qopers[$op] . " (" . $v . ")";
				                    break;
			                  default:
				                    $qwery .= ' '. $field.' '. $qopers[$op] . $v;
			                }
		              }
  		        }
	        }
    	  }
	      return $qwery;
    }
}
