<?php

/**
 * W o r k s p a c e
 *
 */
class ModulAgentiVanzari extends BackEnd {

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

        $this->vars['title_page'] = 'Agenti';
        $this->page_prefix = 'ag_vanzari_';

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
        if (in_array("agenti", $this->user_rights) || $this->user_profile == 10){
            if (isset($arr[1]) && $arr[1] == 'listare')
                    $this->final_result = $this->ListareAgenti();
            else if (isset($arr[1]) && $arr[1] == 'listare_json')
                    echo $this->ListareAgenti_JSON();
            else if (isset($arr[1]) && $arr[1] == 'editare')
                    echo $this->EditareAgent();
            else if (isset($arr[1]) && $arr[1] == 'centre_json')
                    echo $this->JSON_Centre();
        }else
            $this->final_result = $this->PageNotFound();
    }
    
 //-------------------------------- functii ----------------------------------------
    
    
    function ListareAgenti() {
        $this->vars['title_page'] = 'Agenti vanzari';
        return $this->Parse($this->page_prefix . 'listare.html');
    }

    function ListareAgenti_JSON() {
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
        
        $query="SELECT COUNT(a.id) as nr            
            FROM ag_vanzari a
            LEFT JOIN {$this->tables['centre']} b ON a.centru_id=b.id
            WHERE {$cond}";
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

        $query="SELECT a.id, a.nume, a.activ, a.telefon, a.email, b.nume as centru
                FROM ag_vanzari a
                LEFT JOIN {$this->tables['centre']} b ON a.centru_id=b.id
                WHERE {$cond}
                ORDER BY " . $sidx . " " . $sord . " LIMIT " . $start . " , " . $limit;
        
        $sql = $this->db->QFetchRowArray($query);
        $responce->page = $page; 
        $responce->total = $total_pages; 
        $responce->records = $count; 
        
        if (!empty($sql)) {
            foreach ($sql as $key => $row) {  
                if(!empty($row['activ'])) $row['activ'] = 'Activ';
                else $row['activ'] = 'Inactiv';
                $responce->rows[$key]['id']=$row['id'];
                $responce->rows[$key]['cell'] = array(
                    $row['id'],
                    $row['nume'],
                    $row['telefon'],
                    $row['email'],
                    $row['centru'],
                    $row['activ']
                );
            }
        }
        //error_log(json_encode($responce));
        return json_encode($responce);
    }
    
    function EditareAgent(){  
    	$responce = new StdClass();
    	$responce->status = true; 
        $responce->message = '';
           
        $vars = $_REQUEST; 
        //error_log(json_encode($vars));  
        if($vars['oper'] == 'edit'){//editare
            $this->PrepareForInsert ($vars,'nume');
            $vu=[];
            if(!empty($vars['nume']) && !empty($vars['id'])){
                $vu['nume'] = $vars['nume'];
                $vu['telefon'] = $vars['telefon'];
                $vu['email'] = $vars['email'];
                $vu['activ'] = $vars['activ'];   
                //centru
                $vu['centru_id'] = $vars['centru'];
                $this->db->QueryUpdate('ag_vanzari', $vu, 'id=' . $vars['id']);
                $responce->message = 'Id not found'; 
        		return json_encode($responce);
            }
        }else if($vars['oper'] == 'add'){//adaugare
            $this->PrepareForInsert ($vars,'nume');
            $vi=[];
            if(!empty($vars['nume'])){
                $vi['nume'] = $vars['nume'];
                $vi['telefon'] = $vars['telefon'];
                $vi['email'] = $vars['email'];
                $vi['activ'] = $vars['activ'];   
                //centru
                $vi['centru_id'] = $vars['centru'];
                $this->db->QueryInsert('ag_vanzari', $vi);
            }
        }else if($vars['oper'] == 'del'){//stergere
            $this->db->QueryUpdate('ag_vanzari', array('activ' => 0), 'id=' . $vars['id']);               
        }
        return json_encode($responce);
    }
    
    function JSON_Centre() {         
    	$responce = new StdClass();      
        $query="select id, nume from {$this->tables['centre']} where deleted = 0 order by nume";
        $sql = $this->db->QFetchRowArray($query);
        $responce=[];
        if (!empty($sql)) {
            foreach ($sql as $row) {                
                $responce[$row['id']] = $row['nume']; 
            }
        }  
        //error_log(json_encode($responce));    
        return json_encode($responce);
    } 

}
