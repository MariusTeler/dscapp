<?php

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class ModulComenzi extends BackEnd {

    public $final_result;
    public $action_module;
    public $page_prefix;
    public $site_prefix;
    public $table;
	//1:initiala;2:transmisa;5:refuzata;3:distribuita;4:acceptata;6:colectata;7:anulata;
	const STATUS_COMANDA = [1=>'Initiala', 2=>'Transmisa', 3=>'Distribuita', 4=>'Acceptata', 5=>'Refuzata', 6=>'Colectata', 7=>'Anulata'];

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

        $this->vars['title_page'] = 'Comenzi';
        $this->page_prefix = 'comenzi_';

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
		$flag = 0;

		//if($this->user_id == parent::MARIAN)
			//error_log("arr: ".print_r($arr, true));

        if (in_array("comenzi_preluare", $this->user_rights) || $this->user_profile == 10){
			//MENIU Comenzi
				//preluare
			if (isset($arr[1]) && $arr[1] == 'preluare')
	            $this->final_result = $this->ComenziPreluare();
	        else if(isset($arr[1]) && $arr[1]=='adaugare_comanda')
	            echo $this->AdaugareComanda();
			else if(isset($arr[1]) && $arr[1] == 'json' && isset($arr[2]) && $arr[2]=='listare_clienti')
	            echo $this->JSON_ListareClienti();
	        else if (isset($arr[1]) && $arr[1] == 'prmvclient'){
	            echo $this->FillInfoClient();
			}

			else if(isset($arr[1]) && $arr[1] == 'json' && isset($arr[2]) && $arr[2]=='localitati_only')
	            echo $this->JSON_LocalitatiOnly();
			$flag=1;
        }

		if (in_array("comenzi_distribuire", $this->user_rights) || $this->user_profile == 10){
			//distribuire
			if (isset($arr[1]) && $arr[1] == 'distribuire')
	            $this->final_result = $this->ComenziDistribuire();
	        if (isset($arr[1]) && $arr[1] == 'istoric')
	            $this->final_result = $this->ComenziIstoric();
	  		else if(isset($arr[1]) && $arr[1] == 'json' && isset($arr[2]) && $arr[2]=='agenti_distribuire')
	            echo $this->JSON_AgentiDistribuire();
	        else if(isset($arr[1]) && $arr[1] == 'json' && isset($arr[2]) && $arr[2]=='lista_distribuire')
	            echo $this->JSON_ListareComenziDistribuire();
	        else if(isset($arr[1]) && $arr[1] == 'json' && isset($arr[2]) && $arr[2]=='lista_istoric')
	            echo $this->JSON_ListareComenziIstoric();
	        else if(isset($arr[1]) && $arr[1] == 'json' && isset($arr[2]) && $arr[2]=='istoric' && !empty($arr[3]) && intval($arr[3])>0)
	            echo $this->JSON_IstoricComanda(intval($arr[3]));
			else if(isset($arr[1]) && $arr[1] == 'distribuire_comanda')
				echo $this->DistribuireComanda();
			else if(isset($arr[1]) && $arr[1] == 'redistribuire_comanda')
	            echo $this->ReDistribuireComanda();
	        else if(isset($arr[1]) && $arr[1]=='anulare_comanda')
	            echo $this->AnulareComanda();
	        else if(isset($arr[1]) && $arr[1]=='colectare_comanda')
	            echo $this->ColectareComanda();
	        else if(isset($arr[1]) && $arr[1]=='modifica_comanda_dc')
	        	echo $this->ModificareDcComanda();
			else if(isset($arr[1]) && $arr[1] == 'detalii_comanda' && !empty($arr[2]) && intval($arr[2])>0)
	            echo $this->DetaliiComanda(intval($arr[2]));
	        else if(isset($arr[1]) && $arr[1]=='export_istoric')
	        	echo $this->ExportIstoricComenzi();

			$flag=1;
		}

        if(empty($flag))
        	$this->final_result = $this->PageNotFound();
    }

 //-------------------------------- functii ----------------------------------------

 /*/////////////////////////////////////////////////////////////
				 start COMENZI
/////////////////////////////////////////////////////////////*/
	private function getComboDispecerate($selected = false) {
		$combo='';
		$query="select id, nume from dispecerate order by nume";
        $sql = $this->db->QFetchRowArray($query);
        if (!empty($sql)) {
            foreach ($sql as $key => $row) {
               $combo.='<option value="'.$row['id'].'" ';
               if($selected && $selected == $row['id']) $combo.='selected';
               $combo.='>'.$row['nume'].'</option>';
            }
        }
        return $combo;
	}

	function ComenziPreluare($message=''){
		$this->vars['title_page'] = 'Comanda noua';
		$centru = $this->user_centru_id;
		$today = new DateTime("now");
		$hNow = (int)$today->format('H');
		$hStartSelected = 9;
		$hEndSelected = 17;

		if($hNow > 17) {
			$today->add(new DateInterval('P1D'));
		}
		else {
			if($hStartSelected < $hNow) $hStartSelected = $hNow;
			if($hStartSelected >= $hEndSelected) {
				$hEndSelected = $hStartSelected + 1;
			}
		}

		$query="select id from dispecerate where id = (select dispecerat_id from centre where id = {$centru})";
        $sql = $this->db->QFetchArray($query);
        $selected = false;
        if (!empty($sql)) $selected = $sql['id'];
        $this->vars['dispecerate'] = $this->getComboDispecerate($selected);
        $this->vars['user_dispecerat_id'] = $selected;
		$this->vars['COLLECT_AT'] = $today->format('Y-m-d');
		$this->vars['H_START'] = $this->OptionsInterval($hStartSelected);
		$this->vars['H_END'] = $this->OptionsInterval($hEndSelected);
        return $this->Parse('comenzi_preluare.html', $this->vars);
	}

	function AdaugareComanda(){
		$today = new DateTime("now");
		$today->setTime(0, 0, 0);
		$hNow = (int)$today->format('H');

    	$post = [];
		if(!empty($_POST['data']) && count($_POST['data']) > 0)
		{
			foreach($_POST['data'] as $key=>$field){
				$post[$field['name']] = $this->sanitize($field['value']);
			}
		}
		$mesaj = '';

		$hStartSelected = $post['h_start'] = intval($post['h_start'] ?? 0);
		$hEndSelected = $post['h_end'] = intval($post['h_end'] ?? 0);
		//validare
		if(empty($post['collect_at']))
			$mesaj = 'Data colectarii obligatorie!';
		if(empty($post['client_nume']))
			$mesaj = 'Client inexistent!';
		else if(empty($post['localitate_id']))
			$mesaj = 'Selectati localitatea din lista!';
		else if(empty($post['adresa']))
			$mesaj = 'Introduceti adresa!';
		else if(!isset($post['nr_obj_colet']))
			$mesaj = 'Introduceti numarul de colete!';
		else if(!isset($post['nr_obj_palet']))
			$mesaj = 'Introduceti numarul de palete!';
		else if(!isset($post['kg_obj']))
			$mesaj = 'Introduceti greutatea!';
		else if($post['h_start'] == 0 || $post['h_end'] == 0)
			$mesaj = 'Introduceti interval ridicare!';
		else if($post['h_start'] > $post['h_end'])
			$mesaj = 'Interval ridicare gresit!';

		if(!empty($mesaj)) return $mesaj;

		$vi=[];
		$vv=[];

		$collect_at = trim($post['collect_at']);
		$collect_at = DateTime::createFromFormat( 'd.m.Y' , $collect_at);
		if(false === $collect_at)
			$collect_at = new DateTime("now");
		$collect_at->setTime(0, 0, 0);
		if($collect_at < $today)
			$collect_at = $today;

		$interval = $collect_at->diff($today);
		$intervalDays = intval($interval->format("%a"));
		if($intervalDays == 0) {
			if($hStartSelected < $hNow) $hStartSelected = $hNow;
			if($hStartSelected >= $hEndSelected) {
				$hEndSelected = $hStartSelected + 1;
			}
		}

		//hStartSelected after 17 hour -> move to next day
		if($hStartSelected >= 17) {
			$collect_at->add(new DateInterval('P1D'));
			$hStartSelected = 9;
			$hEndSelected = 17;
			$collect_at->setTime($hStartSelected, 0, 0);
		}

		$vi['collect_at'] = $collect_at->format('Y-m-d');

		$vi['created_by'] = $vv['created_by'] = $this->user_id;
	    $vi['h_start'] = $hStartSelected;
		$vi['h_end'] = $hEndSelected;
		if(isset($post['client_id']))
			$vi['client_id'] = trim($post['client_id']);
		$vi['client'] = trim($post['client_nume']);
		if(!empty(Backend::sSanitizeCleanEdges($post['ridica_de_la'])))
			$vi['client'] = Backend::sSanitizeCleanEdges($post['ridica_de_la']);
		$vi['localitate_id'] = intval($post['localitate_id']);
		$vi['adresa'] = trim($post['adresa']);
		if(isset($post['contact']))
			$vi['contact'] = trim($post['contact']);
		if(isset($post['telefon']))
			$vi['telefon'] = trim($post['telefon']);
		$vi['nr_obj_colet'] = intval($post['nr_obj_colet']);
		$vi['nr_obj_palet'] = intval($post['nr_obj_palet']);
		$vi['kg_obj'] = intval($post['kg_obj']);
		if(isset($post['vol_obj']))
			$vi['vol_obj'] = intval($post['vol_obj']);
		if(isset($post['observatii']))
			$vi['observatii'] = trim($post['observatii']);

		$id = $this->db->QueryInsert('comenzi', $vi);
		$vv['status'] = 1; //initiala
		$vv['comanda_id'] = $id;
		$this->db->QueryInsert('comenzi_history', $vv);
		return $id;
	}


	function AnulareComanda(){
		$mesaj = '';
		$comanda_id = intval(trim($_POST['comanda_id'] ?? 0));
		//validare
		if($comanda_id == 0)
			$mesaj = 'Comanda inexistenta!';

		if(!empty($mesaj)) return "0|||".$mesaj;

		//detalii comanda


		$query = "SELECT c.id, ch.agent_id as agent_id
			FROM comenzi c inner join (
					select i.comanda_id, i.created_at , i.agent_id
					from comenzi_history i
					where ( i.created_at, i.status) = (select j.created_at, j.status from comenzi_history j where j.comanda_id=i.comanda_id order by j.created_at desc, j.status desc limit 1)
 				)  ch on c.id = ch.comanda_id
			left join agenti ag on ch.agent_id = ag.cod_ag
			where c.id = {$comanda_id}";
		$sql = $this->db->QFetchArray($query);

		$vv=[];

		$vv['created_by'] = $this->user_id;
		$vv['comanda_id'] = $comanda_id;
		if(isset($_POST['mesaj']))
			$vv['mesaj'] = trim($_POST['mesaj']);

		$vv['status'] = 7; //anulata
		$this->db->QueryInsert('comenzi_history', $vv);

		if(!empty($sql) && !empty($sql['agent_id'])) {
			$query_a = "SELECT fcm_token from agenti where cod_ag = ".$sql['agent_id'];
			$sql_a = $this->db->QFetchArray($query_a);
			if(!empty($sql_a) && !empty($sql_a['fcm_token'])){

				$sDeviceToken = $sql_a['fcm_token'];
				$aPayload = array(
					'data' => array(
						"messageType" => 3,
						"id" => $vv['comanda_id'],
						"delete" => "true"
					)
				);
				$aOptions = array(
					'priority' => 'high',
					'ttl' => 3600
				);

				if(false === $this->fcmSend("anulareComanda", $sDeviceToken, $aPayload, $aOptions, false, $sql['agent_id']))
					return "0|||Eroare trimitere anulare comanda spre android";
			}
		}
		return 1;
	}

	function ColectareComanda(){
		//validare
		if(empty($_POST['comanda_id']))
			$mesaj = 'Comanda inexistenta!';

		if(!empty($mesaj)) return "0|||".$mesaj;


		$vv=[];
		if(!empty($_POST['agent_id']))
			$vv['agent_id'] = intval(trim($_POST['agent_id']));
		$vv['created_by'] = $this->user_id;
		$vv['comanda_id'] = intval(trim($_POST['comanda_id']));

		$vv['status'] = 6; //colectata
		$this->db->QueryInsert('comenzi_history', $vv);
		return 1;
	}

	function ModificareDcComanda(){
		$today = new DateTime("now");
		$today->setTime(0, 0, 0);
		$mesaj = '';

		//validare
		if(empty($_POST['comanda_id']))
			$mesaj = 'Comanda inexistenta!';
		else if(empty($_POST['collect_at']))
			$mesaj = 'Data colectarii obligatorie!';
		else if(!isset($_POST['h_start']) || !isset($_POST['h_end']))
			$mesaj = 'Introduceti interval ridicare!';
		else if(isset($_POST['h_start']) && isset($_POST['h_end']) && $_POST['h_start'] > $_POST['h_end'])
			$mesaj = 'Interval ridicare gresit!';

		if(!empty($mesaj)) return '0!!!'.$mesaj;

		//detalii comanda
		$comanda_id = intval(trim($_POST['comanda_id']));

		$query = "SELECT c.id, ch.agent_id as agent_id
			FROM comenzi c inner join (
					select i.comanda_id, i.created_at , i.agent_id
					from comenzi_history i
					where ( i.created_at, i.status) = (select j.created_at, j.status from comenzi_history j where j.comanda_id=i.comanda_id order by j.created_at desc, j.status desc limit 1)
 				)  ch on c.id = ch.comanda_id
			left join agenti ag on ch.agent_id = ag.cod_ag
			where c.id = ".intval($comanda_id);
		$sql = $this->db->QFetchArray($query);

		$vi=[];
		$vv=[];

		$collect_at = trim($_POST['collect_at']);
		$collect_at = DateTime::createFromFormat( 'd.m.Y' , $collect_at);
		if(false === $collect_at)
			$collect_at = new DateTime("now");
		$collect_at->setTime(0, 0, 0);
		if($collect_at < $today)
			$collect_at = $today;

		$vi['collect_at'] = $collect_at->format('Y-m-d');
		$vi['h_start'] = intval(trim($_POST['h_start']));
		$vi['h_end'] = intval(trim($_POST['h_end']));
		if(isset($_POST['mesaj']))
			$vv['mesaj'] = trim($_POST['mesaj']);

		$this->db->QueryUpdate('comenzi', $vi, "id=".$comanda_id);

		$vv['status'] = 1; //back to initiala
		$vv['created_by'] = $this->user_id;
		$vv['comanda_id'] = $comanda_id;
		$this->db->QueryInsert('comenzi_history', $vv);

		return 1;
	}

	function JSON_ListareClienti() {
		$responce = new StdClass();
		$page = intval($_REQUEST['page'] ?? 1);
		$limit = intval($_REQUEST['rows'] ?? 20);
		if ($limit > 100) $limit = 100; //max 100 rows per page
		$sidx = trim($this->sanitize($_REQUEST['sidx'] ?? 'cl.mod_plata'));
		$sord = trim($this->sanitize($_REQUEST['sord'] ?? 'desc'));

        $cond = "1=1";
        //start generare conditie
        if(isset($_REQUEST['_search']))
        {
        	$searchOn = $this->Strip($_REQUEST['_search']);
        	if ($searchOn == 'true' && isset($_REQUEST['filters'])) {
            	$searchstr = $this->Strip($_REQUEST['filters']);
            	$cond .= $this->constructWhere($searchstr);
        	}
		}

		$dispecerat = intval($_GET['dispecerat'] ?? 0);

        $query="SELECT COUNT(cl.cod_cl) as nr
			FROM {$this->tables['clienti']} cl
			INNER JOIN localitati lc on lc.cod_lc = cl.cod_lc
			INNER JOIN centre ce on lc.cod_centru = ce.id
			WHERE {$cond} and cl.activ = 1 and cl.sters = 0";
        if($dispecerat > 0)
        	$query.=" and ce.dispecerat_id = {$dispecerat}";

        $result = $this->db->QFetchArray($query);
        $count = !empty($result['nr']) ? $result['nr'] : 0;

        if( $count >0 ) {
            $total_pages = ceil($count/$limit);
        } else {
            $total_pages = 0;
        }

        if ($page > $total_pages) $page=$total_pages;
        $start = $limit*$page - $limit; // do not put $limit*($page - 1)
        if ($start<0) $start = 0;

        $query="SELECT cl.cod_cl, cl.nume, lc.nume_lc as localitate,adresa, tarif, mod_plata
                FROM {$this->tables['clienti']} cl
				INNER JOIN localitati lc on lc.cod_lc = cl.cod_lc
				INNER JOIN centre ce on lc.cod_centru = ce.id
				WHERE {$cond} and cl.activ = 1 and cl.sters = 0";
        if($dispecerat > 0)
			$query.=" and ce.dispecerat_id = {$dispecerat}";
        $query.=" ORDER BY " . $sidx . " " . $sord . ", cl.nume asc  LIMIT " . $start . " , " . $limit;
        //error_log($query);
        $sql = $this->db->QFetchRowArray($query);
        $responce->page = $page;
        $responce->total = $total_pages;
        $responce->records = $count;

        if (!empty($sql)) {
            foreach ($sql as $key => $row) {
                $responce->rows[$key]['id']=$row['cod_cl'];
                $responce->rows[$key]['cell'] = array(
                                                        $row['nume'],
                                                        $row['localitate'],
														$row['adresa'],
                                                        ExpeditieDto::CONTRACT[$row['tarif']],
                                                        ExpeditieDto::MOD_PLATA[$row['mod_plata']]
                                                    );
            }
        }
        return json_encode($responce);
    }

    function  FillInfoClient(){
		$client_id = intval($_POST['client_id']);
		//if($this->user_id == parent::MARIAN)
			//error_log("FillInfoClient: ".$client_id);
		if($client_id == 0) return '||| ||| ||| |||';

		$query="SELECT cl.nume, cl.adresa, cl.contact, cl.telefon, cl.cod_lc, lc.nume_lc
                FROM {$this->tables['clienti']} as cl
                LEFT JOIN {$this->tables['localitati']} as lc ON cl.cod_lc = lc.cod_lc
                WHERE cl.cod_cl = {$client_id} AND cl.activ = 1 and cl.sters = 0";
		//if($this->user_id == parent::MARIAN)
        	//error_log($query);
        $sql = $this->db->QFetchArray($query);

        if(!empty($sql))
			return $sql['cod_lc'].'|||'.$sql['nume_lc'].'|||'.$sql['adresa'].'|||'.$sql['contact'].'|||'.$sql['telefon'];
		return '||| ||| ||| |||';
	}


	function ComenziDistribuire(){
		$this->vars['title_page'] = 'Lista comenzi';
		$vars = [];
		$centru = $this->user_centru_id;
		$query="select id from dispecerate where id in (select dispecerat_id from centre where id = {$centru}) limit 1";
        $sql = $this->db->QFetchArray($query);
        $selected = false;
        if (!empty($sql)) $selected = $sql['id'];
        $vars['dis'] = 'disabled="disabled"';
        if($this->user_id == parent::DOINA || $this->user_id == parent::COLAREZ || $this->user_id == parent::MARIAN || $this->user_id == parent::MADALIN) $vars['dis'] = '';
        $vars['dispecerate'] = $this->getComboDispecerate($selected);
        $vars['user_dispecerat_id'] = $selected;
    	return $this->Parse('comenzi_distribuire.html', $vars);
	}

	function ComenziIstoric(){
		$this->vars['title_page'] = 'Istoric comenzi';
		$vars = [];
		$centru = $this->user_centru_id;

		$query="select id from dispecerate where id in (select dispecerat_id from centre where id = {$centru}) limit 1";
        $sql = $this->db->QFetchArray($query);
        $selected = false;
        if (!empty($sql)) $selected = $sql['id'];
        $vars['dispecerate'] = $this->getComboDispecerate($selected);
        $vars['user_dispecerat_id'] = $selected;

		$vars['data_start'] = date('d.m.Y').' 00:00';
		$vars['data_final'] = date('d.m.Y').' 23:59';

    	return $this->Parse('comenzi_history.html', $vars);
	}

	function JSON_ListareComenziDistribuire() {
		$responce = new stdClass();
		$today = new DateTime("now");
		$cond ='1=1';
		//start generare conditie
        $searchOn = $this->Strip($_GET['_search']);
        if ($searchOn == 'true') {
            $searchstr = $this->Strip($_GET['filters']);
            $cond .= $this->constructWhere($searchstr);
        }

        $page = intval($_REQUEST['page'] ?? 1);
		$limit = intval($_REQUEST['rows'] ?? 20);

        $dispecerat = false;
		if(isset($_GET['dispecerat'])){
			$dispecerat = intval($_GET['dispecerat']);
		}

	    $cond .=" and c.collect_at like '".$today->format('Y-m-d')."' ";

		$query = "SELECT COUNT(c.id) as nr
				  FROM comenzi c inner join (
					select i.comanda_id, i.created_at , i.status, i.created_by, i.agent_id , i.motiv_id, i.comunicare
					from comenzi_history i
					where ( i.created_at, i.status) = (select j.created_at, j.status from comenzi_history j where j.comanda_id=i.comanda_id order by j.created_at desc, j.status desc limit 1)
 					)  ch on c.id = ch.comanda_id
 					join localitati l on c.localitate_id = l.cod_lc
 					left join centre cc on l.cod_centru = cc.id
					left join agenti ag on ch.agent_id = ag.cod_ag
				  WHERE {$cond} ";
		if($dispecerat)
        	$query.=" and cc.dispecerat_id =".$dispecerat;

		//error_log($query);
        $result = $this->db->QFetchArray($query);
        $count = !empty($result['nr']) ? $result['nr'] : 0;

        if( $count >0 ) {$total_pages = ceil($count/$limit); }
        else { $total_pages = 0; }
        if ($page > $total_pages) $page=$total_pages;
        if ($limit<0) $limit = 0;
        $start = $limit*$page - $limit; // do not put $limit*($page - 1)
        if ($start<0) $start = 0;

        $query = "SELECT c.*, ch.status, ch.created_by as ch_created_by, cm.motiv as motiv, ch.created_at as ch_created_at, ch.comunicare as comunicare, ag.nume_ag as agent, ag.telefon as agent_telefon, CONCAT(l.nume_lc,' (',l.cod_jd,')') as localitate, cc.nume as centru, agh.created_at,
        	case
        		when ch.status = 1 then 1
        		when ch.status = 2 and DATE_ADD(ch.created_at,INTERVAL 15 MINUTE) <= NOW()  then 0
        		else 0
        	end as rosu
			FROM comenzi c
			inner join localitati l on c.localitate_id = l.cod_lc
			left join centre cc on l.cod_centru=cc.id
            left join (
					select i.comanda_id, i.created_at , i.status, i.created_by, i.agent_id , i.motiv_id, i.comunicare
					from comenzi_history i
					where ( i.created_at, i.status) = (select j.created_at, j.status from comenzi_history j where j.comanda_id=i.comanda_id order by j.created_at desc, j.status desc limit 1)
 			)  ch on c.id = ch.comanda_id
            left join comenzi_motive cm on ch.motiv_id = cm.id
            left join agenti ag on ch.agent_id = ag.cod_ag
			left join (
				select agent_id, MAX(created_at) as created_at
				from ag_login_history
				group by agent_id
			)  agh on ag.cod_ag = agh.agent_id
            WHERE {$cond}";
            if($dispecerat)
        		$query.=" and cc.dispecerat_id =".$dispecerat;
        	//1:initiala;2:transmisa;5:refuzata;3:distribuita;4:acceptata;6:colectata;7:anulata;
            $query.=" ORDER BY
            case
        		when ch.status = 5 then 3
        		when ch.status = 3 then 4
        		when ch.status = 4 then 5
        		else ch.status
        	end asc, ch.created_at asc LIMIT " . $start . " , " . $limit;
        //error_log($query);
        $sql = $this->db->QFetchRowArray($query);
        if (!empty($sql)) {
            foreach ($sql as $key => $row) {
				$row['status_time_sec'] = strtotime($row['ch_created_at']);
				$row['status_time'] =  $this->getTimeDiff(strtotime($row['ch_created_at']), time());
				$responce->rows[$key]['id']=$row['id'];
                $responce->rows[$key]['cell'] = array($row['id'], $row['collect_at'],$row['ch_created_at'], $row['status'], $row['status_time'], $row['status_time_sec'],$row['motiv'],$row['comunicare'],strtoupper($row['centru']),strtoupper($row['client']),strtoupper($row['localitate']),$row['adresa'],$row['nr_obj_colet'],$row['nr_obj_palet'],$row['kg_obj'],strtoupper($row['agent']),$row['agent_telefon'],$row['created_at'],$row['rosu'],'<a href="#" onclick="AfisareDetaliiComanda('.$row['id'].');return false;">Detalii</a>');
            }
        }
        $responce->page = $page;
        $responce->total = $total_pages;
        $responce->records = $count;
        return json_encode($responce);
    }

    function JSON_ListareComenziIstoric() {
		$responce = new stdClass();
		$today = new DateTime("now");
		$cond ='1=1';
		//start generare conditie
        $searchOn = $this->Strip($_GET['_search']);
        if ($searchOn == 'true') {
            $searchstr = $this->Strip($_GET['filters']);
            $cond .= $this->constructWhere($searchstr);
        }
		//error_log($cond);
        $dispecerat = false;
		if(isset($_GET['dispecerat'])){
			$dispecerat = intval($_GET['dispecerat']);
		}

		$data_start = $today->format('Y-m-d').' 00:00:00';
	    $data_final = $today->format('Y-m-d').' 23:59:59';

		if(isset($_GET['data_start']) && isset($_GET['data_final'])){
			$ddata_start = DateTime::createFromFormat('d.m.Y H:i', $_GET['data_start']);
			$ddata_final = DateTime::createFromFormat('d.m.Y H:i', $_GET['data_final']);

			if($ddata_start !== false)
				$data_start = $ddata_start->format('Y-m-d H:i:s');

			if($ddata_final !== false)
				$data_final = $ddata_final->format('Y-m-d H:i:s');
	    }

	    $cond .=" and c.created_at between '".$data_start."' and '".$data_final."' ";

        $page = intval($_REQUEST['page'] ?? 1);
		$limit = intval($_REQUEST['rows'] ?? 20);
		$sidx = trim($this->sanitize($_REQUEST['sidx'] ?? 1));
		$sord = trim($this->sanitize($_REQUEST['sord'] ?? 'asc'));

		$query = "SELECT COUNT(c.id) as nr
				  FROM comenzi c inner join (
					select i.comanda_id, i.created_at , i.status, i.created_by, i.agent_id , i.motiv_id, i.comunicare
					from comenzi_history i
					where ( i.created_at, i.status) = (select j.created_at, j.status from comenzi_history j where j.comanda_id=i.comanda_id order by j.created_at desc, j.status desc limit 1)
 					)  ch on c.id = ch.comanda_id
 					join localitati l on c.localitate_id = l.cod_lc
 					left join centre cc on l.cod_centru=cc.id
 					left join dispecerate dc on cc.dispecerat_id=dc.id
					left join agenti ag on ch.agent_id = ag.cod_ag
				  WHERE {$cond} ";
		if($dispecerat)
        	$query.=" and cc.dispecerat_id =".$dispecerat;

        $result = $this->db->QFetchArray($query);
        $count = !empty($result['nr']) ? $result['nr'] : 0;

        if( $count >0 ) {$total_pages = ceil($count/$limit); }
        else { $total_pages = 0; }
        if ($page > $total_pages) $page=$total_pages;
        if ($limit<0) $limit = 0;
        $start = $limit*$page - $limit; // do not put $limit*($page - 1)
        if ($start<0) $start = 0;

        $query = "SELECT c.*, ch.status, ch.created_by as ch_created_by, cm.motiv as motiv, ch.created_at as ch_created_at, ch.comunicare as comunicare, ag.nume_ag as agent, ag.telefon as agent_telefon, CONCAT(l.nume_lc,' (',l.cod_jd,')') as localitate, dc.nume as dispecerat, cc.nume as centru,
        	case
        		when ch.status = 1 then 1
        		when ch.status = 2 and DATE_ADD(ch.created_at,INTERVAL 15 MINUTE) <= NOW()  then 0
        		else 0
        	end as rosu
			FROM comenzi c inner join (
					select i.comanda_id, i.created_at , i.status, i.created_by, i.agent_id , i.motiv_id, i.comunicare
					from comenzi_history i
					where ( i.created_at, i.status) = (select j.created_at, j.status from comenzi_history j where j.comanda_id=i.comanda_id order by j.created_at desc, j.status desc limit 1)
 				)  ch on c.id = ch.comanda_id
			left join comenzi_motive cm on ch.motiv_id = cm.id
			join localitati l on c.localitate_id = l.cod_lc
			left join centre cc on l.cod_centru=cc.id
			left join dispecerate dc on cc.dispecerat_id=dc.id
			left join agenti ag on ch.agent_id = ag.cod_ag
            WHERE {$cond}";
            if($dispecerat)
        		$query.=" and cc.dispecerat_id =".$dispecerat;
        	$query.=" ORDER BY " . $sidx . " " . $sord . " LIMIT " . $start . " , " . $limit;
        //error_log($query);
        $sql = $this->db->QFetchRowArray($query);
        if (!empty($sql)) {
            foreach ($sql as $key => $row) {
				$responce->rows[$key]['id']=$row['id'];
                $responce->rows[$key]['cell'] = array($row['id'], $row['collect_at'],$row['ch_created_at'], $row['status'],$row['motiv'],$row['comunicare'],strtoupper($row['dispecerat']),strtoupper($row['centru']),strtoupper($row['client']),strtoupper($row['localitate']),$row['adresa'],$row['nr_obj_colet'],$row['nr_obj_palet'],$row['kg_obj'],strtoupper($row['agent']),$row['agent_telefon'],$row['rosu']);
            }
        }
		$responce->page = $page;
        $responce->total = $total_pages;
        $responce->records = $count;
        return json_encode($responce);
    }

    function JSON_IstoricComanda($comanda_id) {
		$responce = new stdClass();
	    $cond =" ch.comanda_id = ".$comanda_id;

        $query = "select ch.id, ch.created_at , ch.status, ch.mesaj, cm.motiv,
        		case ch.status
        			when 1 then u.user
        			when 2 then u.user
        			when 7 then u.user
        			else ag.nume_ag
        		end as u_created_by
					from comenzi_history ch
					left join agenti ag on ch.agent_id = ag.cod_ag
					left join comenzi_motive cm on cm.id = ch.motiv_id
					left join users u on u.id = ch.created_by
				  	where {$cond} order by ch.created_at desc, ch.status desc";

        $sql = $this->db->QFetchRowArray($query);
        if (!empty($sql)) {
            foreach ($sql as $key => $row) {
				$responce->rows[$key]['id']=$row['id'];
                $responce->rows[$key]['cell'] = array($row['status'], $row['created_at'], strtoupper($row['u_created_by']),$row['motiv'],$row['mesaj']);
            }
        }
        return json_encode($responce);
    }

    function JSON_LocalitatiOnly() {
		$responce = new StdClass();
        $limit = 15;
        $cond = '';
        if(!empty($_GET['maxRows'])) $limit=$_GET['maxRows'];
        if(empty($_GET['name_startsWith'])) {
			$responce->total = 0;
            $responce->rezultat=[];
        	return json_encode($responce);
		}
		$cond= "AND lc.nume_lc LIKE :name_startsWith";

        $query="SELECT lc.cod_lc, lc.nume_lc, ce.nume
                FROM {$this->tables['localitati']} lc
                LEFT JOIN {$this->tables['centre']} ce ON lc.cod_centru = ce.id
                WHERE 1=1 {$cond}
                ORDER BY lc.nume_lc LIMIT {$limit}";
        $sql = $this->db->QFetchRowArray($query, ['name_startsWith'=> $this->sanitize($_GET['name_startsWith'])."%"]);
        if (!empty($sql)) {
            $responce->total = count($sql);
            foreach ($sql as $key => $row) {
                $responce->rezultat[$key]['cod'] = $row['cod_lc'];
                $responce->rezultat[$key]['nume'] = strtoupper($row['nume_lc']).' ('.strtoupper($row['nume']).')';
				$responce->rezultat[$key]['localitate'] = strtoupper($row['nume_lc']);
            }
        }else{
            $responce->total = 0;
            $responce->rezultat=[];
        }
        return json_encode($responce);
    }

    function JSON_AgentiDistribuire(){
    	$responce = new StdClass();
		$responce->total = 0;
        $responce->rezultat=[];
        $limit = 8;
        $cond = '';
		$name_startsWith = [];

        $localitate_id = intval($_POST['localitate_id'] ?? 0);
		if($localitate_id == 0)
			return json_encode($responce);
		$query_loc = "SELECT lc.cod_centru as centruId FROM localitati lc WHERE lc.cod_lc = {$localitate_id}";
		$sql_loc = $this->db->QFetchArray($query_loc);
		if(empty($sql_loc) || empty($sql_loc['centruId'])) return json_encode($responce);
		$centre = in_array($sql_loc['centruId'], [47,91]) ? "47, 91" : $sql_loc['centruId'];

        if(!empty($_POST['name_startsWith'])) {
			$cond= " AND ag.nume_ag LIKE :name_startsWith";
			$name_startsWith = ['name_startsWith'=> $this->sanitize($_POST['name_startsWith'])."%"];
		}

        $query="SELECT ag.cod_ag, ag.nume_ag, ag.telefon, ce.nume as centru
                FROM {$this->tables['agenti']} ag
                left join {$this->tables['centre']} ce on ag.cod_centru = ce.id
                WHERE ce.id in ({$centre}) and ag.activ = 1 {$cond} order by ag.nume_ag LIMIT {$limit}";
        $sql = $this->db->QFetchRowArray($query, $name_startsWith);
        if (!empty($sql)) {
            $responce->total = count($sql);
            foreach ($sql as $key => $row) {
                $responce->rezultat[$key]['cod'] = $row['cod_ag'];
                $responce->rezultat[$key]['nume'] = $row['nume_ag'];
                $responce->rezultat[$key]['centru'] = $row['centru'];
                $responce->rezultat[$key]['telefon'] = $row['telefon'];
            }
        }else{
            $responce->total = 0;
            $responce->rezultat=[];
        }
        return json_encode($responce);
    }


	function DistribuireComanda(){
		$mesaj = '';
		//validare
		if(empty($_POST['comanda_id']))
			$mesaj = 'Comanda inexistenta!';
		else if(empty($_POST['agent_id']))
			$mesaj = 'Curier inexistent!';

		if(!empty($mesaj)) return "0|||".$mesaj;

		$vv=[];

		$vv['created_by'] = $this->user_id;
		$vv['agent_id'] = intval($_POST['agent_id']);
		$vv['comanda_id'] = intval($_POST['comanda_id']);
		$vv['mesaj'] = "";
		if(isset($_POST['mesaj']))
			$vv['mesaj'] = $this->sanitize($_POST['mesaj']);
		$vv['comunicare'] = 1;
		if(isset($_POST['comunicare']))
			$vv['comunicare'] = intval($_POST['comunicare']);

		$vv['status'] = 2; //transmisa
		$this->db->QueryInsert('comenzi_history', $vv);

		//fcm transmite
		if(false === $this->fcmDistribuireComanda($vv['comanda_id'], $vv['agent_id'], $vv['mesaj']))
			return "0|||Android : Eroare distribuire comanda!";

		if($vv['comunicare'] == 2) {
			$ora_data = new DateTime("now");
			$ora_data->add(new DateInterval('PT3S'));
			$vv['created_at'] = $ora_data->format('Y-m-d H:i:s');
			$vv['created_by'] = $vv['agent_id'];
			$vv['status'] = 3; //distribuita
			$this->db->QueryInsert('comenzi_history', $vv);
			$vv['status'] = 4; //acceptata
			$ora_data->add(new DateInterval('PT4S'));
			$vv['created_at'] = $ora_data->format('Y-m-d H:i:s');
			$this->db->QueryInsert('comenzi_history', $vv);
		}
		return 1;
	}

	function ReDistribuireComanda(){
		$mesaj = '';
		//validare
		if(empty($_POST['comanda_id']))
			$mesaj = 'Comanda inexistenta!';
		else if(empty($_POST['agent_id']))
			$mesaj = 'Curier inexistent!';

		if(!empty($mesaj)) return "0|||".$mesaj;

		$agent_id = intval($_POST['agent_id']);
		$comanda_id = intval($_POST['comanda_id']);

		//fcm transmite
		if(false === $this->fcmDistribuireComanda($comanda_id, $agent_id, ""))
			return "0|||Android : Eroare redistribuire comanda!";
		return 1;
	}

	function fcmDistribuireComanda($comanda_id, $agent_id, $mesaj){
		$loginData = new DateTime("now");
		$loginData->sub(new DateInterval('P1M')); //1 luna
		$loginData = $loginData->format('Y-m-d H:i:s');
		//get agent
		$query_a = "SELECT fcmToken from ag_login_history where agent_id = {$agent_id} and created_at > '{$loginData}' order by created_at desc limit 1";
		$sql_a = $this->db->QFetchArray($query_a);
		if(!empty($sql_a) && !empty($sql_a['fcmToken'])){
			//get commanda
			$query = "SELECT c.*, CONCAT(l.nume_lc,' (',l.cod_jd,')') as localitate, cl.mod_plata as mod_plata
			FROM comenzi c
			join localitati l on c.localitate_id = l.cod_lc
			left join clienti cl on cl.cod_cl = c.client_id
			where c.id = ".$comanda_id;
			$sql = $this->db->QFetchArray($query);

			if(empty($sql)) return false;

			$sDeviceToken = $sql_a['fcmToken'];
			$aPayload = array(
				'data' => array(
					"messageType" => 2,
					"id" => $comanda_id,
					"client" => $sql['client'],
					"client_id" => $sql['client_id'],
					"localitate" => $sql['localitate'],
					"adresa" => $sql['adresa'],
					"contact" => $sql['contact'],
					"telefon" => $sql['telefon'],
					"colete" => $sql['nr_obj_colet'],
					"paleti" => $sql['nr_obj_palet'],
					"kg" => $sql['kg_obj'],
					"volum" => $sql['vol_obj'],
					"data_colectare" => $sql['collect_at']."T00:00:00",
					"h_start" => $sql['h_start'],
					"h_end" => $sql['h_end'],
					"mod_plata" => $sql['mod_plata'],
					"observatii" => $sql['observatii'],
					"mesaj" => $mesaj
				)
			);
			$aOptions = array('priority' => 'high', 'ttl' => 10800);

			return $this->fcmSend("fcmDistribuireComanda", $sDeviceToken, $aPayload, $aOptions, false, $agent_id);
		}
		return false;
	}

	function DetaliiComanda($id){
		if(intval($id) == 0) return 0;
        $query = "SELECT c.*, ch.status, ch.created_by as ch_created_by, cm.motiv as motiv, ch.created_at as ch_created_at, ch.agent_id as agent_id, ag.nume_ag as agent, ag.telefon as agent_telefon, ch.comunicare, ch.mesaj ,  CONCAT(l.nume_lc,' (',l.cod_jd,')') as localitate
			FROM comenzi c inner join (
					select i.comanda_id, i.created_at , i.status, i.created_by, i.agent_id , i.motiv_id, i.comunicare, i.mesaj
					from comenzi_history i
					where ( i.created_at, i.status) = (select j.created_at, j.status from comenzi_history j where j.comanda_id=i.comanda_id order by j.created_at desc, j.status desc limit 1)
 				)  ch on c.id = ch.comanda_id
			left join comenzi_motive cm on ch.motiv_id = cm.id
			join localitati l on c.localitate_id = l.cod_lc
			left join agenti ag on ch.agent_id = ag.cod_ag
			where c.id = ".intval($id);
        $sql = $this->db->QFetchArray($query);
        if(empty($sql)) return 0;

		$states = array(0=>'.', 1=>'initiala',2=>'transmisa', 3=>'distribuita',4=>'acceptata',5=>'refuzata',6=>'colectata',7=>'anulata');
		$sql['collect_at'] = new DateTime($sql['collect_at']);
		$sql['collect_at'] = $sql['collect_at']->format('d.m.Y');
		$sql['ch_created_at'] = new DateTime($sql['ch_created_at']);
		$sql['ch_created_at'] = $sql['ch_created_at']->format('d.m.Y H:i:s');
        return '1|||'.$sql['id'].'|||'.$sql['collect_at'].'|||'.$sql['client'].'|||'.$sql['localitate'].'|||'.$sql['localitate_id'].'|||'.$sql['adresa'].'|||'.$sql['contact'].'|||'
        		.$sql['telefon'].'|||'.$sql['nr_obj_colet'].'|||'.$sql['nr_obj_palet'].'|||'.$sql['kg_obj'].'|||'.$sql['vol_obj'].'|||'
        		.$sql['h_start'].'|||'.$sql['h_end'].'|||'.$sql['observatii'].'|||'.$sql['ch_created_at'].'|||'.$states[$sql['status']].'|||'
        		.$sql['motiv'].'|||'.$sql['agent_id'].'|||'.$sql['agent'].'|||'.$sql['agent_telefon'].'|||'.$sql['comunicare'].'|||'.$sql['mesaj'];

    }

    function ExportIstoricComenzi() {
		$today = new DateTime("now");
 		$societate = 'Dragon Star Curier';
        $document = 'Istoric comenzi';

        $spreadsheet = new Spreadsheet();
        $spreadsheet->getProperties()->setCreator($societate)
            ->setLastModifiedBy($societate)
            ->setTitle($document)
            ->setSubject($document)
            ->setDescription($document)
            ->setKeywords($document)
            ->setCategory($document);
        $spreadsheet->getDefaultStyle()->getFont()->setName('Arial');

		$worksheet = $spreadsheet->getActiveSheet();
		$worksheet->getStyle('A:Q')->getFont()->setSize(13);
		$worksheet->getStyle('A1:Q1')->getFont()->setBold(true);

		foreach(range('A','Q') as $v){
			$worksheet->getColumnDimension($v)->setAutoSize(true);
		}

		$worksheet->setCellValue('A1','Nr. comanda');
		$worksheet->setCellValue('B1','Data SI');
		$worksheet->setCellValue('C1','Data colectarii');
        $worksheet->setCellValue('D1','Data status');
		$worksheet->setCellValue('E1','Status');
        $worksheet->setCellValue('F1','Motiv');
        $worksheet->setCellValue('G1','Comunicare');
        $worksheet->setCellValue('H1','Dispecerat');
        $worksheet->setCellValue('I1','Centru');
        $worksheet->setCellValue('J1','Client');
        $worksheet->setCellValue('K1','Localitate');
		$worksheet->setCellValue('M1','Adresa');
		$worksheet->setCellValue('M1','Colete');
		$worksheet->setCellValue('N1','Paleti');
		$worksheet->setCellValue('O1','Greutate');
		$worksheet->setCellValue('P1','Curier');
		$worksheet->setCellValue('Q1','Tel. curier');

		$cond ='1=1';
		//start generare conditie
		if(isset($_POST['_search'])) {
			$searchOn = $this->Strip($_POST['_search']);
			if ($searchOn == 'true') {
				$searchstr = $this->Strip($_POST['filters']);
				$cond .= $this->constructWhere($searchstr);
			}
		}


        $sidx = !empty($_POST['sidx']) ? urldecode($_POST['sidx']):'c.id'; // get index row - i.e. user click to sort
        $sord = !empty($_POST['sord']) ? $_POST['sord']:'asc'; // get the direction
        if (!$sidx) $sidx = 1;

        $dispecerat = false;
		if(isset($_POST['dispecerat'])){
			$dispecerat = intval($_POST['dispecerat']);
		}

		$data_start = $today->format('Y-m-d').' 00:00:00';
	    $data_final = $today->format('Y-m-d').' 23:59:59';

		if(isset($_POST['data_start']) && isset($_POST['data_final'])){
			$ddata_start = DateTime::createFromFormat('d.m.Y H:i', $_POST['data_start']);
			$ddata_final = DateTime::createFromFormat('d.m.Y H:i', $_POST['data_final']);

			if($ddata_start !== false)
				$data_start = $ddata_start->format('Y-m-d H:i:s');

			if($ddata_final !== false)
				$data_final = $ddata_final->format('Y-m-d H:i:s');
	    }

	    $cond .=" and c.created_at between '".$data_start."' and '".$data_final."' ";
	    $query = "SELECT c.*, ch.status, ch.created_by as ch_created_by, cm.motiv as motiv, ch.created_at as ch_created_at, ch.comunicare as comunicare, ag.nume_ag as agent, ag.telefon as agent_telefon, CONCAT(l.nume_lc,' (',l.cod_jd,')') as localitate, dc.nume as dispecerat, cc.nume as centru
			FROM comenzi c inner join (
					select i.comanda_id, i.created_at , i.status, i.created_by, i.agent_id , i.motiv_id, i.comunicare
					from comenzi_history i
					where ( i.created_at, i.status) = (select j.created_at, j.status from comenzi_history j where j.comanda_id=i.comanda_id order by j.created_at desc, j.status desc limit 1)
 				)  ch on c.id = ch.comanda_id
			left join comenzi_motive cm on ch.motiv_id = cm.id
			join localitati l on c.localitate_id = l.cod_lc
			left join centre cc on l.cod_centru = cc.id
			left join dispecerate dc on cc.dispecerat_id=dc.id
			left join agenti ag on ch.agent_id = ag.cod_ag
            WHERE {$cond}";
            if($dispecerat)
        		$query.=" and cc.dispecerat_id =".$dispecerat;
        	$query.=" ORDER BY " . $sidx . " " . $sord;
        //error_log($query);
        $sql = $this->db->QFetchRowArray($query);
        if (!empty($sql)) {
        	$rand = 2;
        	$states = array(0=>'.', 1=>'initiala',2=>'transmisa', 3=>'distribuita',4=>'acceptata',5=>'refuzata',6=>'colectata',7=>'anulata');
        	$comunicari = array(1=>'android',2=>'telefonic');
            foreach ($sql as $key => $row) {
            	$data_si = new DateTime($row['created_at']);
        		if($data_si !== false)
					$data_si = $data_si->format('d.m.Y H:i:s');
        		$data_coll = new DateTime($row['collect_at']);
        		if($data_coll !== false)
					$data_coll = $data_coll->format('d.m.Y');
        		$data_st = new DateTime($row['ch_created_at']);
        		if($data_st !== false)
					$data_st = $data_st->format('d.m.Y H:i:s');
                $worksheet->setCellValue('A'.($rand+$key),$row['id']);
				$worksheet->setCellValue('B'.($rand+$key),$data_si);
				$worksheet->setCellValue('C'.($rand+$key),$data_coll);
        		$worksheet->setCellValue('D'.($rand+$key),$data_st);
				$worksheet->setCellValue('E'.($rand+$key),$states[$row['status']]);
        		$worksheet->setCellValueExplicit('F'.($rand+$key), $row['motiv'], \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
        		$worksheet->setCellValue('G'.($rand+$key),$comunicari[$row['comunicare']]);
        		$worksheet->setCellValue('H'.($rand+$key),strtoupper($row['dispecerat']));
        		$worksheet->setCellValue('I'.($rand+$key),strtoupper($row['centru']));
        		$worksheet->setCellValueExplicit('J'.($rand+$key),strtoupper($row['client']), \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
        		$worksheet->setCellValue('K'.($rand+$key),strtoupper($row['localitate']));
				$worksheet->setCellValueExplicit('L'.($rand+$key), $row['adresa'], \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
				$worksheet->setCellValue('M'.($rand+$key),$row['nr_obj_colet']);
				$worksheet->setCellValue('N'.($rand+$key),$row['nr_obj_palet']);
				$worksheet->setCellValue('O'.($rand+$key),$row['kg_obj']);
				$worksheet->setCellValueExplicit('P'.($rand+$key), strtoupper($row['agent']), \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
				$worksheet->setCellValueExplicit('Q'.($rand+$key), $row['agent_telefon'], \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
            }
        }
		$filename = 'Export-istoric-comenzi-'.$today->format('d-m-Y').'.xlsx';
        $this->download_send_headers_xls($filename);
        $writer = new Xlsx($spreadsheet);
        $writer->save('php://output');
        die;
	}

/*/////////////////////////////////////////////////////////////
				 end COMENZI
/////////////////////////////////////////////////////////////*/
}
//end class
?>
