<?php

/**
 * W o r k s p a c e
 *
 */
class ModulApiUsers extends BackEnd {

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

        $this->vars['title_page'] = 'Utilizatori API';
        $this->page_prefix = 'apiusers_';

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
        $arr = $this->GenerateArr();
        //nivel acces 10
        if ($this->user_id == parent::MADALIN || $this->user_id == parent::MARIAN){
            if (isset($arr[1]) && $arr[1] == 'listare')
                $this->final_result = $this->ListareApiUsers(isset($arr[2]) ? $arr[2] : "");
            else if (isset($arr[1]) && $arr[1] == 'listare_json')
                echo $this->ListareApiUsers_JSON(isset($arr[2]) ? $arr[2] : "");
            else if (isset($arr[1]) && $arr[1] == 'utilizatori_agenti_json')
                echo $this->Utilizatori_Agenti_JSON(isset($arr[2]) ? $arr[2] : "");
            else if (isset($arr[1]) && $arr[1] == 'utilizatori_agenti_data')
                echo $this->Utilizatori_Agenti_Data(isset($arr[2]) ? $arr[2] : "");
            else if (isset($arr[1]) && $arr[1] == 'verifica_utilizator')
                echo $this->VerificaUtilizator(isset($arr[2]) ? $arr[2] : "");
            else if (isset($arr[1]) && $arr[1] == 'editare')
                echo $this->EditareApiUsers();
        } else
            $this->final_result = $this->PageNotFound();
    }

 //-------------------------------- functii ----------------------------------------


    function ListareApiUsers($type = "") {
        $this->vars['title_page'] = 'Api users'.(strlen($type)?' - '.$type:'');
        $this->vars['api_type'] = $type;
        return $this->Parse($this->page_prefix . 'listare.html');
    }

    function ListareApiUsers_JSON($type='') {
    	$responce = new StdClass();
        $cond = '1=1 ';
        $vars=[];
        //$vars=$_GET;
        $vars=$_REQUEST;
        //print_R($vars);
        $page = intval($_REQUEST['page'] ?? 1);
		$limit = intval($_REQUEST['rows'] ?? 20);
		$sidx = trim($this->sanitize($_REQUEST['sidx'] ?? 1));
		$sord = trim($this->sanitize($_REQUEST['sord'] ?? 'asc'));

        //start generare conditie
        $searchOn = $this->Strip($vars['_search']);
        if ($searchOn == 'true') {
            $searchstr = $this->Strip($vars['filters']);
            $cond .= $this->constructWhere($searchstr);
        }
        //end generare conditie
        $query = "";

        switch ($type):
            case 'clienti':
                $cond .= ' AND a.is_scanner = 0 AND a.is_android = 0 ';
                $query="SELECT COUNT(a.id) as nr
                FROM apiusers a
                LEFT JOIN {$this->tables['users']} b ON a.users_id = b.id
                LEFT JOIN {$this->tables['clienti']} c ON c.cod_cl = b.expeditor_id
                WHERE {$cond}";
                break;
            case 'scanner':
            case 'android':
                $cond .= ' AND (a.is_android = 1 OR a.is_scanner = 1) ';
                $query="SELECT COUNT(a.id) as nr
                FROM apiusers a
                LEFT JOIN {$this->tables['agenti']} b ON a.users_id = b.cod_ag
                LEFT JOIN {$this->tables['centre']} c ON b.cod_centru = c.id
                WHERE {$cond} ";
                break;
        endswitch;

        if(!strlen($query))
            return;


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

        switch ($type):
            case 'clienti':
                $cond .= ' AND a.is_scanner = 0 AND a.is_android = 0 ';
                $query="SELECT  a.*, c.nume as nume_exp, b.user, a.status as statusapi
                FROM apiusers a
                LEFT JOIN {$this->tables['users']} b ON a.users_id = b.id
                LEFT JOIN {$this->tables['clienti']} c ON c.cod_cl = b.expeditor_id
                WHERE {$cond}
                ORDER BY " . $sidx . " " . $sord . " LIMIT " . $start . " , " . $limit . ";";
                break;
            case 'scanner':
            case 'android':
                $cond .= ' AND (a.is_android = 1 OR a.is_scanner = 1) ';
                $query="SELECT  a.*, b.nume_ag as user, c.nume as nume_exp, a.status as statusapi
                FROM apiusers a
                LEFT JOIN {$this->tables['agenti']} b ON a.users_id = b.cod_ag
                LEFT JOIN {$this->tables['centre']} c ON b.cod_centru = c.id
                WHERE {$cond}
                ORDER BY " . $sidx . " " . $sord . " LIMIT " . $start . " , " . $limit . ";";
                break;

        endswitch;

        $sql = $this->db->QFetchRowArray($query);
        $responce->page = $page;
        $responce->total = $total_pages;
        $responce->records = $count;

        if (!empty($sql)) {
            foreach ($sql as $key => $row) {
                if(!empty($row['status'])) $row['status'] = 'Activ';
                else $row['status'] = 'Inactiv';
                $responce->rows[$key]['id']=$row['id'];
                $responce->rows[$key]['cell'] = array(
                    $row['id'],
                    $row['name'],
                    $row['api_key'],
                    $row['created_at'],
                    (!empty($row['user']))?$row['user']:'',
                    (!empty($row['nume_exp']))?$row['nume_exp']:'',
                    ($row['is_scanner'])?'DA':'NU',
                    ($row['is_android'])?'DA':'NU',
                    $row['master'],
                    ($row['statusapi'] == 1)?'Activ':'Inactiv'
                );
            }
        }
        return json_encode($responce);
    }

    function EditareApiUsers(){
        require_once 'passHash.php';
        $vars = $_REQUEST;
        $this->PrepareForInsert($vars,'name,api_key,users_id,is_scanner,is_android,master,activ');
        if($vars['oper'] == 'edit'){//editare
            if(empty($vars['id'])) {
                header('HTTP/1.1 500 Utilizator inexistent');
                return "Utilizator inexistent";
            }
            if(empty($vars['name'])) {
                header('HTTP/1.1 500 Numele este obligatoriu');
                    return "Numele este obligatoriu";
            }
            $query="SELECT id FROM apiusers WHERE id = ".$vars['id'];
            $sql = $this->db->QFetchArray($query);
            if(empty($sql)) {
                header('HTTP/1.1 500 Utilizator inexistent');
                return "Utilizator inexistent";
            }
            $vu=[];
            if(!empty($vars['name'])){
                $vu['name'] = $vars['name'];
				$vu['api_key'] = $vars['api_key'];
				$vu['users_id'] = (!empty($vars['users_id']))?$vars['users_id']:'';
				$vu['is_scanner'] = $vars['is_scanner'];
				$vu['is_android'] = $vars['is_android'];
				$vu['master'] = $vars['master'];
                // $vu['OPERATOR'] = $this->user_id;
                $vu['status'] = $vars['activ'];
                if(!empty($vars['parola']))
                    $vu['password_hash'] = PassHash::hash($vars['parola']);
                // $vu['DATA_OP'] = time();
                $this->db->QueryUpdate('apiusers', $vu, 'id=' . $vars['id']);
            }
        }else if($vars['oper'] == 'add'){//adaugare
            if(empty($vars['name'])) {
                header('HTTP/1.1 500 Numele este obligatoriu');
                    return "Numele este obligatoriu";
            }
            if(empty($vars['parola'])) {
                header('HTTP/1.1 500 Parola este obligatorie');
                    return "Parola este obligatorie";
            }
            $vi=[];
            if(!empty($vars['name'])){
                $vi['name'] = $vars['name'];
                $vi['api_key'] = $vars['api_key'];
                $vi['users_id'] = (!empty($vars['users_id']))?$vars['users_id']:'';
                $vi['is_scanner'] = $vars['is_scanner'];
                $vi['is_android'] = $vars['is_android'];
                $vi['master'] = $vars['master'];
                // $vu['OPERATOR'] = $this->user_id;
                $vi['status'] = $vars['activ'];
                $vi['password_hash'] = PassHash::hash($vars['parola']);
                // $vi['DATA_OP'] = time();

                $this->db->QueryInsert('apiusers', $vi);
            }
        }else if($vars['oper'] == 'del'){//stergere
            $this->db->Query("DELETE FROM apiusers WHERE id={$vars['id']}");
        }
		return true;
    }



    function Utilizatori_Agenti_Data($tip){
        $query="SELECT id, user FROM {$this->tables['users']} WHERE activ = 1  ORDER BY user ASC";
        if($tip == 'android')
            $query="SELECT cod_ag as id, nume_ag as user FROM {$this->tables['agenti']} WHERE activ = 1 ORDER BY nume_ag ASC";

        $sql = $this->db->QFetchRowArray($query);
        $html = '<select><option value="0">Alege</option>';
        if (!empty($sql)) {
            foreach ($sql as $row) {
                $html .= '<option value="'.$row['id'].'">'.$row['user'].'</option>';
            }
        }
        return $html.'</select>';
    }
    function Utilizatori_Agenti_JSON($tip){
        $query="SELECT id, user FROM {$this->tables['users']} WHERE activ = 1 ORDER BY user ASC";
        if($tip == 'android')
            $query="SELECT cod_ag as id, nume_ag as user FROM {$this->tables['agenti']} WHERE activ = 1 ORDER BY nume_ag ASC";

        $sql = $this->db->QFetchRowArray($query);
        $responce = array( 0 => 'Alege');
        if (!empty($sql)) {
            foreach ($sql as $row) {
                $responce[(string)$row['id']] = $row['user'];
            }
        }
        return json_encode($responce);
    }

    function VerificaUtilizator($utilizator){
        return json_encode(array('id'=>0));
        $query="SELECT id, user FROM {$this->tables['users']} WHERE user = :utilizator";
        $sql = $this->db->QFetchArray($query, ['utilizator'=>$this->sanitize($utilizator)]);
        return json_encode($sql);
    }
}
