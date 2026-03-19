<?php

/**
 * W o r k s p a c e
 *
 */
class ModulBanci extends BackEnd {

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

        $this->vars['title_page'] = 'Banci';
        $this->page_prefix = 'banci_';

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
        if (isset($arr[1]) && $arr[1] == 'listare')
            $this->final_result = $this->ListareBanci();
        else if (isset($arr[1]) && $arr[1] == 'listare_json')
            echo $this->ListareBanci_JSON();
        else if (in_array("banci", $this->user_rights) || $this->user_profile == 10){
            if (isset($arr[1]) && $arr[1] == 'editare')
                echo $this->EditareBanca();
            else if (isset($arr[1]) && $arr[1] == 'export_csv')
                echo $this->exportCSV();
        }else
            $this->final_result = $this->PageNotFound();
    }
    
 //-------------------------------- functii ----------------------------------------
    
    function ListareBanci() {
        $this->vars['title_page'] = 'Banci';
        return $this->Parse($this->page_prefix . 'listare.html');
    }


    function exportCSV(){
        $query = "select nume, bic, label from banci
        where deleted = 0 
        ORDER by nume";
        $result = $this->db->QFetchRowArray($query);
        $arr[0] = array(
            'nume',
            'bic',
            'label'
        );

        if(!empty($result)){
            foreach ($result as $i => $row ){
                $arr[] = array(
                    $row['nume'],
                    $row['bic'],
                    $row['label']
                );
            }
        }

        if(count($result)){
            $this->download_send_headers("banci_export_" . date("Y-m-d") .".csv");
            echo $this->array2csv($arr);die;
        }

    }

    function ListareBanci_JSON() {
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
        
        $query="SELECT COUNT(id) as nr
            FROM banci
            WHERE {$cond} and deleted = 0";
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

        $query="SELECT id, nume, bic, label
                FROM banci
                WHERE {$cond}  and deleted = 0
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
                                                        $row['bic'],                                     
                                                        $row['label']
                                                    );
            }
        }
        return json_encode($responce);
    }
    
    function EditareBanca(){       
        $vars = $_REQUEST;   
        if($vars['oper'] == 'edit'){//editare
            $this->PrepareForInsert($vars, 'nume, bic, label');
            if(!empty($vars['nume']) && !empty($vars['id'])){
                $this->db->QueryUpdate('banci', ['nume'=>strtoupper($vars['nume']), 'bic'=>strtoupper($vars['bic']), 'label'=>strtoupper($vars['label']), 'user_id'=>$this->user_id], 'id=' . $vars['id']);
            }
        }else if($vars['oper'] == 'add'){//adaugare
            $this->PrepareForInsert($vars, 'nume, bic, label');
            if(!empty($vars['nume'])){  
                $this->db->QueryInsert('banci', ['nume'=>strtoupper($vars['nume']), 'bic'=>strtoupper($vars['bic']), 'label'=>strtoupper($vars['label']), 'user_id'=>$this->user_id]);
            }
        }else if($vars['oper'] == 'del'){//stergere
        	$this->db->QueryUpdate('banci', array('deleted'=>1, 'user_id'=>$this->user_id), 'id=' . $vars['id']);
        }
    }
}
//end class
?>
