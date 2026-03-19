<?php

/**
 * W o r k s p a c e
 *
 */
class ModulZone extends BackEnd {

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

        $this->vars['title_page'] = 'Zone';
        $this->page_prefix = 'zone_';

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
        if (in_array("zone", $this->user_rights) || $this->user_profile == 10){
            if (isset($arr[1]) && $arr[1] == 'listare')
                    $this->final_result = $this->ListareZone();
            else if (isset($arr[1]) && $arr[1] == 'listare_json')
                    echo $this->ListareZone_JSON();
            else if (isset($arr[1]) && $arr[1] == 'editare')
                    echo $this->EditareZona();
            else if (isset($arr[1]) && $arr[1] == 'zone_json')
                    echo $this->JSON_Zone();
        }else
            $this->final_result = $this->PageNotFound();
    }
    
 //-------------------------------- functii ----------------------------------------
    
    
    function ListareZone() {
        $this->vars['title_page'] = 'Zone';
        $vars = [];
        return $this->Parse($this->page_prefix . 'listare.html', $vars);
    }

    function ListareZone_JSON() {
    	$responce = new StdClass();
        /*
        $cond = '1=1 ';
        $vars=[];
        //$vars=$_GET;
        $vars=$_REQUEST;
        //print_R($vars);
        $page = $vars['page']; // get the requested page 
        $limit = $vars['rows']; // get how many rows we want to have into the grid 
        $sidx = $vars['sidx']; // get index row - i.e. user click to sort 
        $sord = $vars['sord']; // get the direction 
        if(!$sidx){ $sidx =1; }
        
        //start generare conditie
        $searchOn = $this->Strip($vars['_search']);
        if ($searchOn == 'true') {
            $searchstr = $this->Strip($vars['filters']);
            $cond .= $this->constructWhere($searchstr);
        }
        
        $query="SELECT COUNT(r.id) as nr
                FROM regiuni r
                left join users u on u.regiune_id = r.id
                WHERE {$cond} and r.deleted = 0
                group by r.id";
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

        $query="SELECT r.id , r.nume , r.label as cod, group_concat(u.user) as sefi
                FROM regiuni r
                left join users u on u.regiune_id = r.id
                WHERE {$cond}  and r.deleted = 0
                group by r.id
                ORDER BY " . $sidx . " " . $sord . " LIMIT " . $start . " , " . $limit;

        $sql = $this->db->QFetchRowArray($query);
        $responce->page = $page; 
        $responce->total = $total_pages; 
        $responce->records = $count;
        
        if (!empty($sql)) {
            foreach ($sql as $key => $row) {  
                $responce->rows[$key]['id']=$row['id'];
                $responce->rows[$key]['cell'] = array(
                                                        $row['nume'], 
                                                        $row['cod'],
                                                        $row['sefi']                                                
                                                    );
            }
        }
        */
        return json_encode($responce);
    }

    function EditareZona(){    
        /*   
        $vars = $_REQUEST;   
        if($vars['oper'] == 'edit'){//editare
            $this->PrepareForInsert($vars,'nume, cod');
            $vars['id'] = intval($vars['id']);
            $vu=[];
            if(!empty($vars['nume']) && !empty($vars['id'])){
                $vu['nume'] = $vars['nume'];
                $vu['label'] = $vars['cod'];
                $vu['updated_by'] = $this->user_id ;
                $vu['updated_at'] = (new DateTime('now'))->format('Y-m-d H:i:s');
                $this->db->QueryUpdate('regiuni', $vu, 'id=' . $vars['id']);
            }
            else {
                header('HTTP/1.1 500 Empty id');
                return "Empty id";
            }
        }else if($vars['oper'] == 'add'){//adaugare
            $this->PrepareForInsert($vars,'nume, cod');
            $query="SELECT COUNT(id) as nr FROM regiuni WHERE nume like :nume";
            $result = $this->db->QFetchArray($query,  ['nume'=>$vars['nume']]);
            if($result['nr'] > 0) {
                header('HTTP/1.1 500 Regiune existenta');
                return "Regiune existenta";
            }
            $vi=[];
            if(!empty($vars['nume'])){
                $vi['nume'] = $vars['nume'];
                $vi['label'] = $vars['cod'];
                $vi['updated_by'] = $this->user_id ;
                $vi['updated_at'] = (new DateTime('now'))->format('Y-m-d H:i:s');
                $this->db->QueryInsert('regiuni', $vi);
            }
            else {
                header('HTTP/1.1 500 Empty name');
                return "Empty name";
            }
        }else if($vars['oper'] == 'del'){//stergere
            $vars['id'] = intval($vars['id']);
            if(!empty($vars['id'])) {
                $query="SELECT COUNT(id) as nr FROM centre WHERE id_regiune =".$vars['id'];
                $result = $this->db->QFetchArray($query);
                if($result['nr'] > 0) {
                    header('HTTP/1.1 500 Regiunea are centre atasate');
                    return "Regiunea are centre atasate";
                }
                $this->db->QueryUpdate('regiuni', array('deleted'=>1, 'updated_by'=>$this->user_id, 'updated_at'=>(new DateTime('now'))->format('Y-m-d H:i:s')), 'id=' . $vars['id']);
            }
            else {
                header('HTTP/1.1 500 Empty id');
                return "Empty id";
            }
        }
        */
    }

    function JSON_Zone() { 
    	$responce = new StdClass();              
        $query="SELECT zo.id, zo.name as 'name', zo.coordinates as coordinates, zf.code as centru, zf.name as center_name 
                FROM zones ORDER BY zo.name";
        $sql = $this->db->QFetchRowArray($query);
        $responce=[];
        if (!empty($sql)) {
        	$responce[0] = ' ';
            foreach ($sql as $row) {                
                $responce[$row['id']] = $row['name']; 
            }
        }      
        return json_encode($responce);
    }
}
//end class
?>
