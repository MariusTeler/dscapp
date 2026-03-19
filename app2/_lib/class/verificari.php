<?php
/**
 * W o r k s p a c e
 *
 */
class ModulVerificari extends BackEnd {

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
        $this->vars['title_page'] = 'Verificari';
        $this->page_prefix = 'verificari_';

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

		if (in_array("confirmare", $this->user_rights) || $this->user_profile == 10){
	        if ( isset($arr[1]) && $arr[1] == 'expeditie'){
	        	$flag=1;
				$this->final_result = $this->VerificareExpeditie();
	        }
            else if ( isset($arr[1]) && $arr[1] == 'istoric_expeditie' ){
                echo $this->VerificareIstoricExpeditie();
                $flag=1;
            } else if ( isset($arr[1]) && $arr[1] == 'istoric_recantariri' ){
                echo $this->VerificareRecantaririExpeditie();
                $flag=1;
            } else if ( isset($arr[1]) && $arr[1] == 'detalii_expeditie' && !empty($arr[2]) ){
				echo $this->VerificareDetaliiExpeditie($arr[2]);
				$flag=1;
			}
		}
         if(empty($flag))
            	$this->final_result = $this->PageNotFound();
    }

 //-------------------------------- functii ----------------------------------------


	function VerificareExpeditie() {
        $this->vars['title_page'] = 'Istoric Expeditie';
        $vars = [];
        return $this->Parse($this->page_prefix . 'expeditie.html', $vars);
	}

	function VerificareIstoricExpeditie() {

	    $responce = new StdClass();
        if(empty($_REQUEST['expeditie']) || intval($_REQUEST['expeditie']) == 0){
            $responce->page = $_REQUEST['page'];
            $responce->total = 0;
            $responce->records = 0;
            return json_encode($responce);
        }
        $conf_exp = $expeditie = intval($_REQUEST['expeditie']);
		$query = "SELECT cod_expeditie FROM {$this->tables['exp_prelucrate']} WHERE expeditie={$expeditie} and anulata = 0";
        $sql = $this->db->QFetchArray($query);
		if(!empty($sql)){
			$expeditie=$sql['cod_expeditie'];
		}
		else{
            $responce->page = $_REQUEST['page'];
            $responce->total = 0;
            $responce->records = 0;
            $responce->userdata['afisare_confirmare_buton'] = "";
            return json_encode($responce);
        }

		//echo $expeditie;die;
        $cond = " a.cod_exp=".$expeditie;
        $page = intval($_REQUEST['page'] ?? 1);
		$limit = intval($_REQUEST['rows'] ?? 20);
		$sidx = trim($this->sanitize($_REQUEST['sidx'] ?? 1));
		$sord = trim($this->sanitize($_REQUEST['sord'] ?? 'asc'));

        //start generare conditie
        $searchOn = $this->Strip($_REQUEST['_search']);
        if ($searchOn == 'true') {
            $searchstr = $this->Strip($_REQUEST['filters']);
            $cond .= $this->constructWhere($searchstr);
        }

        $totalrows = isset($_REQUEST['totalrows']) ? $_REQUEST['totalrows']: false;
        if($totalrows){ $limit = $totalrows; }

        $query = "SELECT COUNT(a.cod_ist) as nr
            FROM {$this->tables['ist_exp']} a
            INNER JOIN {$this->tables['users']} b ON a.operator=b.id
            LEFT JOIN {$this->tables['op']} c ON a.operatiune=c.cod_op
            INNER JOIN {$this->tables['exp_prelucrate']} d ON (a.cod_exp = d.cod_expeditie and d.anulata = 0)
            WHERE {$cond}";
			//echo $query;die;
        $result = $this->db->QFetchArray($query);
        $count = !empty($result['nr']) ? $result['nr'] : 0;

        if( $count >0 ) {
            $total_pages = ceil($count/$limit); }
        else { $total_pages = 0; }
        if ($page > $total_pages) $page=$total_pages;
        if ($limit<0) $limit = 0;
        $start = $limit*$page - $limit; // do not put $limit*($page - 1)
        if ($start<0) $start = 0;

        $query = "SELECT a.cod_ist,a.data,a.data_op,c.op_ro,b.user, d.primitor, ec.folder
            FROM {$this->tables['ist_exp']} a
            INNER JOIN {$this->tables['users']} b ON a.operator=b.id
            LEFT JOIN {$this->tables['op']} c ON a.operatiune=c.cod_op
            INNER JOIN {$this->tables['exp_prelucrate']} d ON (a.cod_exp=d.cod_expeditie and d.anulata = 0)
            LEFT JOIN exp_confirmari ec on d.expeditie=ec.expeditie
            WHERE {$cond}
            ORDER BY " . $sidx . " " . $sord . " LIMIT " . $start . " , " . $limit . ";";
        $sql = $this->db->QFetchRowArray($query);
        //compun raspunsul
        $responce->page = $page;
        $responce->total = $total_pages;
        $responce->records = $count;
        $responce->userdata['afisare_confirmare_buton'] = "";
        if (!empty($sql)) {
            foreach ($sql as $key => $row) {
                $responce->rows[$key]['id'] = $row['cod_ist'];
                if(strtoupper($row['op_ro']) != 'LIVRAT') $row['primitor']='';
                if(!empty($row['primitor']) && !empty($row['folder'])) $row['primitor'] = '<a href="javascript:;" onclick="opDownloadConfirmare('.intval($conf_exp).');">'.$row['primitor'].'</a>';
                $responce->rows[$key]['cell'] = array($row['op_ro'], $row['data'], $row['user'],$row['data_op'],$row['primitor']);
            }
        }
        return json_encode($responce);
    }


    function VerificareRecantaririExpeditie() {
        $this->user_rights = $this->GetDrepturiUtilizator($this->user_profile);

        $responce = new StdClass();
        $expeditie = intval($_REQUEST['expeditie']);
        if($expeditie == 0){
            $responce->page = $_REQUEST['page'];
            $responce->total = 0;
            $responce->records = 0;
            return json_encode($responce);
        }

        $query = "SELECT er.*, u.user
                FROM exp_recantarite er
                LEFT join users u ON u.id = er.user_id
                WHERE er.expeditie={$expeditie} and er.vKg = 1";
        $sql = $this->db->QFetchRowArray($query);
        //empty($sql) || !($this->user_profile == 23 || $this->user_profile == 10)
        if( empty($sql) || !(in_array("vizualizare_recantarire",$this->user_rights))){
            $responce->page = 0;
            $responce->total = 0;
            $responce->records = 0;
            $responce->userdata['afisare_racantarire'] = false;
            return json_encode($responce);
        }

        //compun raspunsul
        $responce->page = 1;
        $responce->total = 1;
        $responce->records = count($sql);
        $responce->userdata['afisare_racantarire'] = true;
        if (!empty($sql)) {
            foreach ($sql as $key => $row) {
                $responce->rows[$key]['id'] = $row['id'];
                $responce->rows[$key]['cell'] = array($row['data'], $row['user'], $row['oldKg'], $row['kg'],$row['lungime'],$row['latime'],$row['inaltime'], intval(!empty($row['folder'])));
            }
        }
        return json_encode($responce);

    }


	function VerificareDetaliiExpeditie($expeditie) {
        $expeditie = intval($expeditie);

        if($expeditie == 0) return $this->Parse($this->page_prefix . 'expeditie_detalii.html', []);
        if(false === ($sql = $this->GetValues(intval($expeditie)))) return $this->Parse($this->page_prefix . 'expeditie_detalii.html', []);

        $sql['tip_exp'] = ExpeditieDto::TIP_EXP[$sql['tip_exp']] ?? "undefined";

        $tip_obj = "undefined";
		if(!empty($sql['plicuri'])) $tip_obj = '1 plic';
		if(!empty($sql['colete'])) $tip_obj = $sql['colete'].' colete';
		if(!empty($sql['paleti'])) $tip_obj = '1 palet';
		$sql['tip_obj'] = $tip_obj;

        if(!empty($sql['ret_nt'])) $sql['ret_nt'] = 'Retur NT.';
        else $sql['ret_nt'] = '';
        if(!empty($sql['ret_doc'])) $sql['ret_doc'] = 'Retur DOC.';
        else $sql['ret_doc'] = '';
        if(!empty($sql['liv_samb'])) $sql['liv_samb'] = 'Liv. Samb.';
        else $sql['liv_samb'] = '';
        if(!empty($sql['liv_sed'])) $sql['liv_sed'] = 'Liv. Sed.';
        else $sql['liv_sed'] = '';

        if(empty($sql['referire'])) $sql['referire'] = '';

        if(!empty($sql['expeditie'])){
            //retur nt
            $query = "SELECT expeditie FROM {$this->tables['exp_prelucrate']} WHERE referire={$expeditie} and anulata = 0";
            $s = $this->db->QFetchRowArray($query);
            $retururi = [];
            if(!empty($s)){
                foreach ($s as $key => $row) {
                    $retururi[] = $row['expeditie'];
                }
                $sql['retururi'] = implode(',',$retururi);
            }
        }
        if($sql['mod_plata'] > 0 && (!is_array($this->user_rights) || !in_array('preturi', $this->user_rights))) {
            $sql['valoare_expeditie'] = $sql['val_greutate'] = $sql['val_km'] = $sql['val_asig'] = $sql['valoare_totala_expeditie'] = $sql['tva'] = 'NaN';
        }

		return $this->Parse($this->page_prefix . 'expeditie_detalii.html', $sql);
    }

}
