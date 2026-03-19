<?php

/**
 * W o r k s p a c e
 *
 */
class ModulCheckpoints extends BackEnd {

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

        $this->vars['title_page'] = 'Checkpoints';
        $this->page_prefix = 'checkpoints_';

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
        if (in_array("checkpoints", $this->user_rights) || $this->user_profile == 10){
            if (isset($arr[1]) && $arr[1] == 'listare')
                    $this->final_result = $this->Checkpoints();
            else if (isset($arr[1]) && $arr[1] == 'listare_json')
                    echo $this->JSON_Checkpoints();
            else if (isset($arr[1]) && $arr[1] == 'editare')
                    echo $this->EditareCheckpoint();
        }
        else
            $this->final_result = $this->PageNotFound();
    }
    
 //-------------------------------- functii ----------------------------------------
    
    
    function Checkpoints() {
        $this->vars['title_page'] = 'Checkpoints';
        return $this->Parse($this->page_prefix . 'listare.html');
    }

    function JSON_Checkpoints() {
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
            FROM {$this->tables['checkpoints']}
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

        $query="SELECT *
                FROM {$this->tables['checkpoints']}
                WHERE {$cond} 
                ORDER BY " . $sidx . " " . $sord . " LIMIT " . $start . " , " . $limit;
        //print_r($query);die;
        $sql = $this->db->QFetchRowArray($query);
        $responce->page = $page; 
        $responce->total = $total_pages; 
        $responce->records = $count; 
        
        if (!empty($sql)) {
            foreach ($sql as $key => $row) {  
                $responce->rows[$key]['id']=$row['id'];
                $responce->rows[$key]['cell'] = array(
                                                        $row['denumire'], 
                                                        $row['abbr'], 
                                                        $row['activ'],
                                                        $row['is_exceptie'],
                                                        $row['is_public']
                                                    );
            }
        }
        return json_encode($responce);
    }

    function EditareCheckpoint(){       
        $vars = $_REQUEST;   
        if($vars['oper'] == 'edit'){//editare
            $this->PrepareForInsert ($vars,'denumire');
            $vu=[];
            if(!empty($vars['denumire']) && !empty($vars['id'])){
                $vu['denumire'] = $vars['denumire'];
                $vu['activ'] = intval($vars['activ'] ?? 0);
                $vu['abbr'] = $vars['abbr'];
                $vu['is_public'] = intval($vars['is_public'] ?? 0);
                $vu['is_exceptie'] = intval($vars['is_exceptie'] ?? 0);
                $vu['operator'] = $this->user_id;
                $vu['updated_at'] = date('Y-m-d H:i:s');
                $vu['updated_by'] = $this->user_id;
                $this->db->QueryUpdate($this->tables['checkpoints'], $vu, 'id=' . $vars['id']);
                if($vu['activ'] == 1 && $vu['is_public'] == 1 && $vu['is_exceptie'] == 1){
                    if(!$this->sendUpdateToAllAndroid($vars['id'], 7, false))
                        error_log("failed update : sendUpdateToAndroid ckps : {$vars['id']}");
                }
                else if($vu['activ'] == 0 && !$this->sendUpdateToAllAndroid($vars['id'], 7, true))
                    error_log("failed delete : sendUpdateToAndroid ckps : {$vars['id']}");
            }
        }else if($vars['oper'] == 'add'){//adaugare
            $this->PrepareForInsert ($vars,'denumire');
            $vi=[];
            if(!empty($vars['denumire'])){
                $vi['denumire'] = $vars['denumire'];
                $vi['operator'] = $this->user_id;
                $vi['activ'] = intval($vars['activ'] ?? 0);   
                $vi['abbr'] = $vars['abbr'];
                $vu['is_public'] = intval($vars['is_public'] ?? 0);
                $vu['is_exceptie'] = intval($vars['is_exceptie'] ?? 0);
                //centru
                $vu['created_at'] = date('Y-m-d H:i:s');
                $vu['created_by'] = $this->user_id;
                $ckId = $this->db->QueryInsert($this->tables['checkpoints'], $vi);
                if($vu['is_public'] == 1 && $vu['is_exceptie'] == 1 && $vu['activ'] == 1 && !$this->sendUpdateToAllAndroid($ckId, 7, false))
                    error_log("failed insert : sendUpdateToAndroid ckps : {$ckId}");
            }
        } else if($vars['oper'] == 'del'){//stergere
            $this->db->QueryUpdate($this->tables['checkpoints'], ['activ' => 0], 'id=' . $vars['id']);     
            if(!$this->sendUpdateToAllAndroid($vars['id'], 7, false))
                error_log("failed delete : sendUpdateToAndroid ckps : {$vars['id']}");
        }
    }

}
