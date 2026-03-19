<?php

/**
 * W o r k s p a c e
 *
 */
class ModulLocalitati extends BackEnd {

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

        $this->vars['title_page'] = 'Localitati';
        $this->page_prefix = 'localitati_';

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
        if (isset($arr[1]) && $arr[1] == 'show')
            $this->final_result = $this->ShowLocalitati();
        else if (isset($arr[1]) && $arr[1] == 'listare')
            $this->final_result = $this->ListareLocalitati();
        else if (isset($arr[1]) && $arr[1] == 'listare_json')
            echo $this->ListareLocalitati_JSON();
        else if (in_array("localitati", $this->user_rights) || $this->user_profile == 10){
            if (isset($arr[1]) && $arr[1] == 'editare')
                    echo $this->EditareLocalitate();
            else if (isset($arr[1]) && $arr[1] == 'centre_json')
                    echo $this->JSON_Centre();
            else if (isset($arr[1]) && $arr[1] == 'judete_json')
                echo $this->JSON_Judete();
            else if (isset($arr[1]) && $arr[1] == 'export_csv')
                echo $this->exportCSV();
        }
        else
            $this->final_result = $this->PageNotFound();
    }
    
 //-------------------------------- functii ----------------------------------------
    
    function ShowLocalitati() {
        $this->vars['title_page'] = 'Localitati';
        $vars = [];
        return $this->Parse($this->page_prefix . 'show.html', $vars);
    }
    
    function ListareLocalitati() {
        $this->vars['title_page'] = 'Localitati';
        $vars = [];
        return $this->Parse($this->page_prefix . 'listare.html', $vars);
    }


    function exportCSV(){
        $query = "select j.nume_jd as judet, l.nume_lc as localitate, c.label as cod_centru, c.nume as centru, l.dist_km as km_ext, l.zile_liv from localitati l
left join judete j on j.cod_jd = l.cod_jd
left join centre c on c.id = l.cod_centru 
where l.deleted = 0 
ORDER by j.nume_jd, l.nume_lc";
        $result = $this->db->QFetchRowArray($query);
        $arr[0] = array(
            'judet',
            'localitate',
            'cod_centru',
            'centru',
            'km_ext',
            'zile_liv'
        );

        if(!empty($result)){
            foreach ($result as $i => $row ){
                $arr[] = array(
                    $row['judet'],
                    $row['localitate'],
                    $row['cod_centru'],
                    $row['centru'],
                    $row['km_ext'],
                    $row['zile_liv']
                );
            }
        }

        if(count($result)){
            $this->download_send_headers("localitati_export_" . date("Y-m-d") .".csv");
            echo $this->array2csv($arr);die;
        }

    }

    function ListareLocalitati_JSON() {
    	$responce = new StdClass();
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
        //end generare conditie
        
        $query="SELECT COUNT(a.cod_lc) as nr
            FROM {$this->tables['localitati']} a
            INNER JOIN {$this->tables['centre']} b ON a.cod_centru=b.id
            INNER JOIN {$this->tables['judete']} c ON a.cod_jd=c.cod_jd
            WHERE {$cond} and a.deleted = 0";
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

        $query="SELECT a.cod_lc,a.nume_lc,a.dist_km,b.nume,c.nume_jd, a.zile_liv
                FROM {$this->tables['localitati']} a
                INNER JOIN {$this->tables['centre']} b ON a.cod_centru=b.id
                INNER JOIN {$this->tables['judete']} c ON a.cod_jd=c.cod_jd
                WHERE {$cond}  and a.deleted = 0
                ORDER BY " . $sidx . " " . $sord . " LIMIT " . $start . " , " . $limit . ";";
        
        $sql = $this->db->QFetchRowArray($query);
        $responce->page = $page; 
        $responce->total = $total_pages; 
        $responce->records = $count; 
        
        if (!empty($sql)) {
            foreach ($sql as $key => $row) {  
                $responce->rows[$key]['id']=$row['cod_lc'];
                $responce->rows[$key]['cell'] = array(
                                                        $row['nume_lc'], 
                                                        $row['nume_jd'],                                     
                                                        $row['nume'], 
                                                        $row['dist_km'],
                                                        $row['zile_liv']                                                      
                                                    );
            }
        }
        return json_encode($responce);
    }
    
    function EditareLocalitate(){       
        $vars = $_REQUEST;   
        if($vars['oper'] == 'edit'){//editare
            $this->PrepareForInsert ($vars,'localitate');
            $vu=[];
            if(!empty($vars['localitate']) && !empty($vars['id'])){
                $vu['nume_lc'] = $vars['localitate'];
                $vu['operator'] = $this->user_id;
                //centru
                $vu['cod_centru'] = $vars['centru'];                
                $query="SELECT nume FROM {$this->tables['centre']} WHERE id={$vars['centru']}";
                $centru = $this->db->QFetchArray($query);
                $vu['nume_centru'] = $centru['nume'];
                //judet
                $vu['cod_jd'] = $vars['judet'];                 
                $query="SELECT nume_jd FROM {$this->tables['judete']} WHERE cod_jd='{$vars['judet']}'";
                $judet = $this->db->QFetchArray($query);
                $vu['judet'] = $judet['nume_jd'];
                $vu['dist_km'] = $vars['km'];   
                //zile livrare
                $vu['zile_liv'] = '';
                if(!empty($vars['zile_liv'])) {
                    $zile_liv = explode(",", $this->sanitize($vars['zile_liv']));
                    if(is_array($zile_liv) && count($zile_liv) > 0) {
                        array_map('intval', $zile_liv);
                        $vu['zile_liv'] = implode(",", $zile_liv);
                    }
                }
                $this->db->QueryUpdate($this->tables['localitati'], $vu, 'cod_lc=' . $vars['id']);
                //update all clienti zona_id = 0
                $this->db->QueryUpdate($this->tables['clienti'], array('zona_id' => 0, 'cod_centru' => $vu['cod_centru']), 'cod_lc=' . $vars['id']);
                if(!$this->sendUpdateToAllAndroid($vars['id'], 9, false))
                    error_log("failed update : sendUpdateToAndroid loca : {$vars['id']}");
            }
        }else if($vars['oper'] == 'add'){//adaugare
            $this->PrepareForInsert ($vars,'localitate');
            $vi=[];
            if(!empty($vars['localitate'])){
                $vi['nume_lc'] = $vars['localitate'];
                $vi['operator'] = $this->user_id;
                //centru
                $vi['cod_centru'] = $vars['centru'];                
                $query="SELECT nume FROM {$this->tables['centre']} WHERE id='{$vars['centru']}'";
                $centru = $this->db->QFetchArray($query);
                $vi['nume_centru'] = $centru['nume'];
                //judet
                $vi['cod_jd'] = $vars['judet'];                 
                $query="SELECT nume_jd FROM {$this->tables['judete']} WHERE cod_jd='{$vars['judet']}'";
                $judet = $this->db->QFetchArray($query);
                $vi['judet'] = $judet['nume_jd'];
                $vi['dist_km'] = $vars['km'];   
                //zile livrare
                $vu['zile_liv'] = '';
                if(!empty($vars['zile_liv'])) {
                    $zile_liv = explode(",", $this->sanitize($vars['zile_liv']));
                    if(is_array($zile_liv) && count($zile_liv) > 0) {
                        array_map('intval', $zile_liv);
                        $vu['zile_liv'] = implode(",", $zile_liv);
                    }
                }
                $locaId = $this->db->QueryInsert($this->tables['localitati'], $vi);
                if(!$this->sendUpdateToAllAndroid($locaId, 9, false))
                    error_log("failed insert : sendUpdateToAndroid loca : {$locaId}");
            }
        }else if($vars['oper'] == 'del'){//stergere
        	$this->db->QueryUpdate($this->tables['localitati'], array('deleted'=>1, 'operator'=>$this->user_id, 'date_op'=>time()), 'cod_lc=' . $vars['id']);
            $this->db->QueryUpdate($this->tables['clienti'], array('zona_id' => 0), 'cod_lc=' . $vars['id']);
            if(!$this->sendUpdateToAllAndroid($vars['id'], 9, true))
                error_log("failed delete : sendUpdateToAndroid loca : {$vars['id']}");
        }
    }
    
    function JSON_Centre() { 
    	$responce = new StdClass();              
        $query="SELECT id,nume FROM {$this->tables['centre']} WHERE deleted = 0 ORDER BY nume";
        $sql = $this->db->QFetchRowArray($query);
        $responce=[];
        if (!empty($sql)) {
            foreach ($sql as $row) {                
                $responce[$row['id']] = $row['nume']; 
            }
        }      
        return json_encode($responce);
    } 
    
    function JSON_Judete() {  
    	$responce = new StdClass();             
        $query="SELECT cod_jd,nume_jd FROM {$this->tables['judete']} WHERE 1=1 ORDER BY nume_jd";
        $sql = $this->db->QFetchRowArray($query);
        $responce=[];
        if (!empty($sql)) {
            foreach ($sql as $row) {                
                $responce[$row['cod_jd']] = $row['nume_jd']; 
            }
        }        
        return json_encode($responce);
    } 

}
//end class
?>
