<?php

/**
 * W o r k s p a c e
 *
 */
class ModulCentre extends BackEnd {

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

        $this->vars['title_page'] = 'Centre';
        $this->page_prefix = 'centre_';

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
        if (in_array("centre", $this->user_rights) || $this->user_profile == 10){
            if (isset($arr[1]) && $arr[1] == 'listare')
                    $this->final_result = $this->ListareCentre();
            else if (isset($arr[1]) && $arr[1] == 'dispecerate_listare')
                    $this->final_result = $this->ListareDispecerate();
            else if (isset($arr[1]) && $arr[1] == 'listare_json')
                    echo $this->ListareCentre_JSON();
            else if (isset($arr[1]) && $arr[1] == 'editare')
                    echo $this->EditareCentru();
            else if (isset($arr[1]) && $arr[1] == 'dispecerate_listare_json')
                    echo $this->ListareDispecerate_JSON();
            else if (isset($arr[1]) && $arr[1] == 'dispecerate_editare')
                    echo $this->EditareDispecerat();
            else if (isset($arr[1]) && $arr[1] == 'dispecerate_json')
                    echo $this->JSON_Dispecerate();
            else if (isset($arr[1]) && $arr[1] == 'centre_json')
                    echo $this->JSON_FinanciarCentre();
        }else
            $this->final_result = $this->PageNotFound();
    }
    
 //-------------------------------- functii ----------------------------------------
    
    
    function ListareCentre() {
        $this->vars['title_page'] = 'Centre';
        return $this->Parse($this->page_prefix . 'listare.html');
    }

    function ListareDispecerate() {
        $this->vars['title_page'] = 'Dispecerate';
        return $this->Parse($this->page_prefix . 'dispecerate_listare.html');
    }

    function ListareCentre_JSON() {
    	$responce = new StdClass();
        $cond = '1=1 ';

        $page = intval($_GET['page'] ?? 1);
		$limit = intval($_GET['rows'] ?? 20);
		$sidx = trim($this->sanitize($_GET['sidx'] ?? 1));
		$sord = trim($this->sanitize($_GET['sord'] ?? 'asc'));
        
        //start generare conditie
        $searchOn = $this->Strip($_GET['_search'] ?? 'false');
        if ($searchOn == 'true') {
            $searchstr = $this->Strip($_GET['filters'] ?? '');
            $cond .= $this->constructWhere($searchstr);
        }
        //end generare conditie
        
        $query="SELECT COUNT(ce.id) as nr
                FROM centre ce
                left join centre mce on ce.mst_financiar_id=mce.id
                left join dispecerate d on ce.dispecerat_id = d.id
                WHERE {$cond} and ce.deleted = 0";
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

        $query="SELECT ce.id, mce.nume as mst_financiar_nume, ce.nume as centru_nume,ce.label as cod,ce.email as email, 
                ce.geocode, ce.financiar, d.nume as dispecerat, ce.rut_bvh, ce.rut_buh, ce.rut_buc
                FROM centre ce
                left join centre mce on ce.mst_financiar_id = mce.id
                left join dispecerate d on ce.dispecerat_id = d.id
                WHERE {$cond}  and ce.deleted = 0
                ORDER BY " . $sidx . " " . $sord . " LIMIT " . $start . " , " . $limit;

        $sql = $this->db->QFetchRowArray($query);
        $responce->page = $page; 
        $responce->total = $total_pages; 
        $responce->records = $count;
        
        if (!empty($sql)) {
            foreach ($sql as $key => $row) {  
                $responce->rows[$key]['id']=$row['id'];
                $responce->rows[$key]['cell'] = array(
                    $row['id'], 
                    $row['centru_nume'], 
                    $row['cod'],
                    $row['dispecerat'],
                    $row['email'],
                    $row['mst_financiar_nume'],
                    $row['financiar'],
                    $row['rut_bvh'],
                    $row['rut_buh'],
                    $row['rut_buc'],
                    $row['geocode'],                                               
                );
            }
        }
        return json_encode($responce);
    }

    function ListareDispecerate_JSON() {
    	$responce = new StdClass();
        $cond = '1=1 ';
        $page = intval($_GET['page'] ?? 1);
		$limit = intval($_GET['rows'] ?? 20);
		$sidx = trim($this->sanitize($_GET['sidx'] ?? 1));
		$sord = trim($this->sanitize($_GET['sord'] ?? 'asc'));
        
        //start generare conditie
        $searchOn = $this->Strip($_GET['_search'] ?? 'false');
        if ($searchOn == 'true') {
            $searchstr = $this->Strip($_GET['filters'] ?? '');
            $cond .= $this->constructWhere($searchstr);
        }
        
        $query="SELECT COUNT(id) as nr
                FROM dispecerate
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

        $query="SELECT id, nume, comment
                FROM dispecerate
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
                    $row['comment']                                                    
                );
            }
        }
        return json_encode($responce);
    }
    
    function EditareCentru(){
        $id = intval($_POST['id'] ?? 0);
        $mst_financiar_id = intval($_POST['master'] ?? 0);
        $dispecerat_id = intval($_POST['dispecerat'] ?? 0);
        if(($_POST['oper'] ?? 'unknown') == 'edit'){//editare
            $this->PrepareForInsert($_POST,'nume,email');
            $vu=[];
            if(!empty($_POST['nume']) && $id > 0){
                $vu['nume'] = $_POST['nume'] ?? 'unknown';
                $vu['label'] = $_POST['label'] ?? 'unknown';
                $vu['dispecerat_id'] = $dispecerat_id;
                $vu['email'] = $_POST['email'] ?? '';
                $vu['rut_bvh'] = $_POST['rut_bvh'] ?? '';
                $vu['rut_buh'] = $_POST['rut_buh'] ?? '';
                $vu['rut_buc'] = $_POST['rut_buc'] ?? '';
                
                if(in_array($this->user_id, parent::CAN_MODIFY_GEOCODE_FINANCIAR)){
                    $vu['mst_financiar_id'] = $mst_financiar_id;
                    if(isset($_POST['geocode']))
                        $vu['geocode'] =  intval($_POST['geocode']);
                    $vu['financiar'] = intval($_POST['financiar'] ?? 0);
                }
                    
                $vu['updated_at'] = (new DateTime('now'))->format('Y-m-d H:i:s');
                $vu['updated_by'] = $this->user_id;
                $this->db->QueryUpdate($this->tables['centre'], $vu, "id = {$id}");
                if(!$this->sendUpdateToAllAndroid($id, 6, false))
                    error_log("failed sendUpdateToAndroid centre : {$id}");
            }
        }else if(($_POST['oper'] ?? 'unknown') == 'add'){//adaugare
            $this->PrepareForInsert($_POST,'nume, email');
            $query="SELECT COUNT(id) as nr FROM centre WHERE nume like :nume";
            $result = $this->db->QFetchArray($query, ['nume'=> ($_POST['nume'] ?? 'OTOPENI')]);
            if($result['nr'] > 0) {
                header('HTTP/1.1 500 Centru existent');
                return "Centru existent";
            }
            $vi=[];
            if(!empty($_POST['nume'] ?? '')){
                $vi['nume'] = $_POST['nume'] ?? 'unknown';
                $vi['label'] = $_POST['label'] ?? 'unknown';
                $vi['dispecerat_id'] = $dispecerat_id;
                $vi['email'] = $_POST['email'] ?? '';
                $vi['rut_bvh'] = $_POST['rut_bvh'] ?? '';
                $vi['rut_buh'] = $_POST['rut_buh'] ?? '';
                $vi['rut_buc'] = $_POST['rut_buc'] ?? '';

                if(in_array($this->user_id, parent::CAN_MODIFY_GEOCODE_FINANCIAR)){
                    $vi['mst_financiar_id'] = $mst_financiar_id;
                    if(isset($_POST['geocode']))
                        $vi['geocode'] =  intval($_POST['geocode']);
                    $vi['financiar'] = intval($_POST['financiar'] ?? 0);
                }

                $vi['created_at'] = date('Y-m-d H:i:s');
                $vi['created_by'] = $this->user_id;
                $id = $this->db->QueryInsert($this->tables['centre'], $vi);
                if(!$this->sendUpdateToAllAndroid($id, 6, false))
                    error_log("failed sendInsertToAndroid centre : {$id}");
            }
        } 
    }

    function EditareDispecerat(){        
        $id = intval($_POST['id'] ?? 0); 
        if(($_POST['oper'] ?? 'unknown') == 'edit'){//editare
            $this->PrepareForInsert($_POST,'nume,comment');
            $vu=[];
            if(!empty($_POST['nume']) && $id > 0){
                $vu['nume'] = $_POST['nume'] ?? 'unknown';
                $vu['comment'] = $_POST['comment'] ?? '';
                $vu['updated_at'] = (new DateTime('now'))->format('Y-m-d H:i:s');
                $vu['updated_by'] = $this->user_id;
                $this->db->QueryUpdate('dispecerate', $vu, "id = {$id}");
            }
        }else if(($_POST['oper'] ?? 'unknown') == 'add'){//adaugare
            $this->PrepareForInsert($_POST,'nume,comment');
            $vi=[];
            if(!empty($_POST['nume'])){
                $vi['nume'] = $_POST['nume'] ?? 'unknown';
                $vi['comment'] = $_POST['comment'] ?? '';
                $vi['created_at'] = date('Y-m-d H:i:s');
                $vi['created_by'] = $this->user_id;
                $id = $this->db->QueryInsert('dispecerate', $vi);
            }
        }
    }
    
    function JSON_Dispecerate() { 
    	$responce = new StdClass();              
        $query="select id, nume FROM dispecerate ORDER BY nume";
        $sql = $this->db->QFetchRowArray($query);
        $responce=[];
        if (!empty($sql)) {
            foreach ($sql as $row) {                
                $responce[$row['id']] = $row['nume']; 
            }
        }      
        return json_encode($responce);
    }
    
    function JSON_FinanciarCentre() { 
    	$responce = new StdClass();              
        $query="select id, nume FROM centre WHERE deleted = 0 and financiar = 1 ORDER BY nume";
        $sql = $this->db->QFetchRowArray($query);
        $responce=[];
        $responce[0] = "";
        if (!empty($sql)) {
        	$responce[0] = ' ';
            foreach ($sql as $row) {                
                $responce[$row['id']] = $row['nume']; 
            }
        }      
        return json_encode($responce);
    }
}