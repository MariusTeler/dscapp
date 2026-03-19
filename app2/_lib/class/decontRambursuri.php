<?php

/**
 * W o r k s p a c e
 *
 */
class ModulDecontRambursuri extends BackEnd {

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

        $this->vars['title_page'] = 'Decont ramburs';
        $this->page_prefix = 'decont_ramburs_';

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
        if (in_array("decont_ramburs", $this->user_rights) || $this->user_profile == 10){

            if (isset($arr[1]) && $arr[1] == 'centre')
                $this->final_result = $this->ListareCentre();
            else if(isset($arr[1]) && $arr[1] == 'json' && $arr[2]=='centre')
				echo $this->JSON_Centre();
        
        }else
            $this->final_result = $this->PageNotFound();
    }
    
 //-------------------------------- functii ----------------------------------------
    
    
    function ListareCentre() {
        $this->vars['title_page'] = 'Decont ramburs';
        $vars = [];
        $vars['onload_js_version'] = $this->config['version']['onload_js_version'];
        $vars['centre'] = $this->ComboCentre(-1);
        $vars['data_start'] = $vars['data_final'] = date('d.m.Y');
        return $this->Parse($this->page_prefix . 'centre.html', $vars);
    }

    function JSON_Centre() {
    	$responce = new StdClass();
        $cond1 = $cond2 = "";

        if(isset($_REQUEST['data_start']) && isset($_REQUEST['data_final'])){
            $data_start = $this->TransformDate($_REQUEST['data_start']);
            $data_final = $this->TransformDate($_REQUEST['data_final']);        
            $cond1 = " AND da.data_expeditie like '".$data_start."%'";          
        }
        if(isset($_REQUEST['centru']) && intval($_REQUEST['centru']) > 0){ 
            $cond1 = " AND ce.id = ".intval($_REQUEST['centru']);        
        }

        if(isset($_REQUEST['_search'])){
            $searchOn = $this->Strip($_REQUEST['_search']);
            if ($searchOn == 'true' && isset($_REQUEST['filters'])) {
                $searchstr = $this->Strip($_REQUEST['filters']);
                $cond2 .= $this->constructWhere($searchstr);
            }
        }

        $page = intval($_REQUEST['page'] ?? 1);
		$limit = intval($_REQUEST['rows'] ?? 20);
		$sidx = trim($this->sanitize($_REQUEST['sidx'] ?? 1));
		$sord = trim($this->sanitize($_REQUEST['sord'] ?? 'asc'));
        
        $query="SELECT COUNT(ce.id) as nr
                FROM centre ce
                LEFT JOIN decont_agent da on da.centru_id = ce.id
                WHERE {$cond1} and ce.deleted = 0";
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

        $query_inc = " SELECT ce.id, ce.nume, ce.label as cod, d.nume as dispecerat, SUM(IFNULL(da.total, 0)) as incasare     
        FROM centre ce
        left join dispecerate d on ce.dispecerat_id = d.id
        LEFT JOIN decont_agent da on da.centru_id = ce.id
        WHERE {$cond1} {$cond2} and ce.deleted = 0
        GROUP BY ce.id
        ORDER BY " . $sidx . " " . $sord . " LIMIT " . $start . " , " . $limit;

        $sql_inc = $this->db->QFetchRowArray($query_inc);
        $responce->page = $page; 
        $responce->total = $total_pages; 
        $responce->records = $count;
        
        if (!empty($sql_inc)) {
            foreach ($sql_inc as $key => $row) {  
                $responce->rows[$key]['id']=$row['id'];
                $responce->rows[$key]['cell'] = array(
                                                    $row['cod'], 
                                                    $row['nume'],
                                                    $row['incasare'],
                                                    0,//$row['rambursuri'],
                                                    $row['nr_expeditii'],
                                                    $row['incasare'] - 0,
                                                    '',
                                                );
            }
        }
        return json_encode($responce);
    }
}
//end class
?>
