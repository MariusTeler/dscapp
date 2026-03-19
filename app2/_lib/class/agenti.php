<?php

/**
 * W o r k s p a c e
 *
 */
class ModulAgenti extends BackEnd {

    public $final_result;
    public $action_module;
    public $page_prefix;
    public $table;

    private $tip_agent = [ 0=>'Curier', 1=>'Centru', 2=>'Departament', 3=>'Hub',4=>'SBK'];

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
        $this->page_prefix = 'agenti_';

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
            $this->final_result = $this->PageForbidden();
        // R E S U L T
        return $this->final_result;
    }

   
    function ActionsNivelAcces() {
        $this->user_rights = $this->GetDrepturiUtilizator($this->user_profile);
        $arr = $this->GenerateArr();
        //nivel acces 10
        if($this->user_profile == 10) {
            $this->user_rights[] = "agenti";
            $this->user_rights[] = "pontaj";
        }

        if (isset($arr[1]) && $arr[1] == 'autocomplete') {
            echo $this->JSON_Autocomplete(); 
            return;
        }
        if (in_array("agenti", $this->user_rights)) {
            if (isset($arr[1]) && $arr[1] == 'listare')  {
                $this->final_result = $this->ListareAgenti();
                return;
            }
            if (isset($arr[1]) && $arr[1] == 'listare_json')  {
                echo $this->ListareAgenti_JSON();
                return;
            }
            if (isset($arr[1]) && $arr[1] == 'editare')  {
                echo $this->EditareAgent();
                return;
            }
            if (isset($arr[1]) && $arr[1] == 'centre_json')  {
                echo $this->JSON_Centre();
                return;
            }
        }
        if (in_array("pontaj", $this->user_rights)){
            if (isset($arr[1]) && $arr[1] == 'pontaj'){
                $this->final_result = $this->PontajAgenti();
                return;
            }
            if (isset($arr[1]) && $arr[1] == 'pontaj_json'){
                echo $this->PontajAgenti_JSON();
                return;
            }
            if (isset($arr[1]) && $arr[1] == 'pontaj_xls'){
                echo $this->PontajAgenti_XLS();
                return;
            }
        }
        $this->final_result = $this->PageNotFound();
    }
    
 //-------------------------------- functii ----------------------------------------
    
    
    function ListareAgenti() {
        $this->vars['title_page'] = 'Agenti';
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
		$sidx = trim($this->sanitize(empty($_REQUEST['sidx']) ? 1 : $_REQUEST['sidx']));
		$sord = trim($this->sanitize($_REQUEST['sord'] ?? 'asc'));
        
        //start generare conditie
        $searchOn = $this->Strip($vars['_search']);        
        if ($searchOn == 'true') {
            $searchstr = $this->Strip($vars['filters']);
            $cond .= $this->constructWhere($searchstr);
        }

        $query="SELECT COUNT(a.cod_ag) as nr            
            FROM {$this->tables['agenti']} a
            LEFT JOIN {$this->tables['centre']} b ON a.cod_centru=b.id
            LEFT JOIN (
                select i.agent_id, i.created_at, i.androidAppVersion, i.dbSize, i.pozeError, i.pozeFile
                from ag_login_history i
                where i.created_at = (select MAX(j.created_at) from ag_login_history j where j.agent_id = i.agent_id group by j.agent_id)
            ) agh on a.cod_ag = agh.agent_id
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

        $query="SELECT a.cod_ag, a.nume_ag, a.tip, a.activ, a.telefon, IF(a.cnp > 0,a.cnp,'') as cnp, 
            a.user, b.nume, a.mid,
            agh.created_at, agh.androidAppVersion, agh.dbSize, agh.pozeError, agh.pozeFile
            FROM agenti a
            LEFT JOIN centre b ON a.cod_centru=b.id
            LEFT JOIN (
                select i.agent_id, i.created_at, i.androidAppVersion, i.dbSize, i.pozeError, i.pozeFile
                from ag_login_history i
                where i.created_at = (select MAX(j.created_at) from ag_login_history j where j.agent_id = i.agent_id group by j.agent_id)
            ) agh on a.cod_ag = agh.agent_id
            WHERE {$cond} 
            ORDER BY " . $sidx . " " . $sord . " LIMIT " . $start . " , " . $limit;

        //error_log($query);
        
        $sql = $this->db->QFetchRowArray($query);
        $responce->page = $page; 
        $responce->total = $total_pages; 
        $responce->records = $count; 
        
        if (!empty($sql)) {
            foreach ($sql as $key => $row) {  
                if(!empty($row['created_at']))
                    $row['created_at'] = $this->CreateDate($row['created_at'], 'd.m.Y H:i');
                $responce->rows[$key]['id']=$row['cod_ag'];
                $responce->rows[$key]['cell'] = array(
                										$row['cod_ag'],
                                                        $row['nume_ag'],
                                                        $row['tip'],
                                                        $row['telefon'],
                                                        $row['cnp'],
                                                        $row['nume'], 
                                                        $row['activ'],
                                                        $row['user'],
                                                        null,
                                                        $row['created_at'],
                                                        $row['mid'],
                                                        $row['androidAppVersion'],
                                                        number_format((float)$row['dbSize'] / 1024, 2, '.', ''),
                                                        $row['pozeError'],
                                                        $row['pozeFile']
                                                    );
            }
        }
        return json_encode($responce);
    }
    
    function EditareAgent(){  
    	require_once 'passHash.php';
           
        $vars = $_REQUEST;   
        if($vars['oper'] == 'edit'){//editare
            if(empty($vars['nume'])) {
                header('HTTP/1.1 500 Numele este obligatoriu');
                    return "Numele este obligatoriu";
            }
            $this->PrepareForInsert ($vars,'nume');
            $vu=[];
            if(empty($vars['id'])) {
                header('HTTP/1.1 500 Agent inexistent');
                return "Agent inexistent";
            }
            if(!empty($vars['nume'])){
                $vu['nume_ag'] = $vars['nume'];
                $vu['telefon'] = $vars['telefon'];
                $vu['tip'] = intval($vars['tip']) ?? 0;
                if(!empty($vars['cnp']))
                    $vu['cnp'] = $vars['cnp'];
                if(!empty($vars['mid']))
                    $vu['mid'] = $vars['mid'];
                $vu['operator'] = $this->user_id;
                $vu['activ'] = $vars['activ'];   
                $vu['data_op'] = time();
                //centru
                $vu['cod_centru'] = $vars['centru'];
                $vu['user'] = $vars['user'];
                
                if(!empty($vars['parola']))
                	$vu['password'] = PassHash::hash($vars['parola']);
                $this->db->QueryUpdate($this->tables['agenti'], $vu, 'cod_ag=' . $vars['id']);
            }
        }else if($vars['oper'] == 'add'){//adaugare
            if(empty($vars['nume'])) {
                header('HTTP/1.1 500 Numele este obligatoriu');
                    return "Numele este obligatoriu";
            }
            $this->PrepareForInsert ($vars,'nume');
            $vi=[];
            if(!empty($vars['nume'])){
                $vi['nume_ag'] = $vars['nume'];
                $vi['telefon'] = $vars['telefon'];
                $vi['tip'] = intval($vars['tip']) ?? 0;
                if(!empty($vars['cnp']))
                    $vi['cnp'] = $vars['cnp'];
                if(!empty($vars['mid']))
                    $vu['mid'] = $vars['mid'];
                $vi['operator'] = $this->user_id;
                $vi['activ'] = $vars['activ'];   
                $vi['data_op'] = time();
                //centru
                $vi['cod_centru'] = $vars['centru'];
                $vi['user'] = $vars['user'];
                if(!empty($vars['user']) && empty($vars['parola'])) {
                    header('HTTP/1.1 500 Parola este obligatorie');
                    return "Parola este obligatorie";
                }
                if(!empty($vars['parola']))
                	$vi['password'] = PassHash::hash($vars['parola']);
                $this->db->QueryInsert($this->tables['agenti'], $vi);
            }
        }else if($vars['oper'] == 'del'){//stergere
            $this->db->QueryUpdate($this->tables['agenti'], ['activ' => 0], "cod_ag={$vars['id']}");  
        }
        return true;
    }
    
    function JSON_Centre() {         
    	$responce = new StdClass();      
        $query="SELECT id, nume FROM {$this->tables['centre']} WHERE deleted = 0 ORDER BY nume";
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
        $query="SELECT cod_jd, nume_jd FROM {$this->tables['judete']} WHERE 1=1 ORDER BY nume_jd";
        $sql = $this->db->QFetchRowArray($query);
        $responce=[];
        if (!empty($sql)) {
            foreach ($sql as $row) {                
                $responce[$row['cod_jd']] = $row['nume_jd']; 
            }
        }        
        return json_encode($responce);
    } 

    function JSON_Autocomplete(){
        $limit = 10;
        if(!empty($_GET['maxRows'])) $limit=$_GET['maxRows'];
        $agent = "a";
        if(!empty($_GET['name_startsWith'])) $agent = $this->sanitize($_GET['name_startsWith']);
        $cond= " nume_ag like :agent";

        if(!empty($_GET['centru'])) {
            $cond .= " and cod_centru = ".intval($_GET['centru']);
        }

        $responce = new StdClass();

        $query = "select cod_ag, nume_ag from {$this->tables['agenti']} where {$cond} and activ=1 order by nume_ag limit {$limit}";
        //echo $query;die;
        $sql = $this->db->QFetchRowArray($query, ['agent'=>$agent."%"]);
        $responce->total = 0;
        $responce->rezultat=[];
        if (!empty($sql)) {
            $responce->total = count($sql);
            foreach ($sql as $key => $row) {
                $responce->rezultat[$key]['id'] = $row['cod_ag'];
                $responce->rezultat[$key]['label'] = $responce->rezultat[$key]['value'] = strtoupper($row['nume_ag']);                
            }
        } 
        return json_encode($responce);
    }

    function PontajAgenti() {
        $this->vars['title_page'] = 'Pontaj agenti';
        $vars['data_start'] = $vars['data_final'] = date('d.m.Y');
		$vars['centre'] = $this->ComboCentre(0);
        return $this->Parse($this->page_prefix . 'pontaj.html', $vars);
    }

    function PontajAgenti_JSON() {
    	$responce = new StdClass();
        $data_start = date('Y-m-d') . " 00:00:00";
        $data_final = date('Y-m-d') . " 23:59:59";   
        $cond_data = " between '".$data_start."' AND '".$data_final."'";
		$cond = "scfl.first_liv {$cond_data}";
        
        $vars = $_GET ?? [];
        $vars = is_array($vars) ? $vars : [];
        if(isset($vars['data_start']) && isset($vars['data_final'])){
            $data_start = $this->TransformDate($vars['data_start']) . " 00:00:00";
            $data_final = $this->TransformDate($vars['data_final']) . " 23:59:59";   
            $cond_data = " between '".$data_start."' AND '".$data_final."'";
            $cond = "scfl.first_liv {$cond_data}";     
        }
        $centru_id = isset($vars['centru']) ? intval($vars['centru']) : 0;
        if($centru_id > 0) $cond .= " and ag.cod_centru = {$centru_id}";
        //error_log($cond);

        $page = intval($vars['page'] ?? 1);
		$limit = intval($vars['rows'] ?? 100);
		$sidx = trim($this->sanitize($vars['sidx'] ?? 1));
		$sord = trim($this->sanitize($vars['sord'] ?? 'asc'));
        
        //start generare conditie
        $searchOn = $vars['_search'] ?? false;        
        if ($searchOn == 'true') {
            $searchstr = $this->Strip($vars['filters']);
            $cond .= $this->constructWhere($searchstr);
        }

        $query="SELECT COUNT(ag.cod_ag) as nr            
            FROM {$this->tables['agenti']} ag
            INNER JOIN centre ce ON ag.cod_centru = ce.id
            inner join ( select sc.curier as agent_id, MIN(sc.data) as first_liv, count(sc.cod) as nb_liv
                    from scanari_coduri sc
                    where sc.data {$cond_data} and sc.tip in (5,7,8,10,11,12,13,14,15,17,20,21,26,27,28,30,33,34,35)
                    and sc.is_awb = 1
                    group by sc.curier, DATE(sc.data)
                ) scfl on scfl.agent_id = ag.cod_ag
            WHERE {$cond}";
        $result = $this->db->QFetchArray($query);
        $count = !empty($result['nr']) ? $result['nr'] : 0;

        if( $count >0 ) {
            $total_pages = ceil($count/$limit);
        } else {
            $total_pages = 0;
        }
        if ($page > $total_pages) $page = $total_pages;
            $start = $limit*$page - $limit; // do not put $limit*($page - 1)
        if ($start < 0) $start = 0;

        $query="SELECT ag.cod_ag, ag.nume_ag, ag.tip, ag.telefon, ag.user, agh.first_login, sb.first_bo, scfl.first_liv, scfp.first_prel, scll.last_liv, sclp.last_prel, 
                ce.nume as centru, di.last_inc_zi, da.last_decont, (IFNULL(scfl.nb_liv, 0) + IFNULL(scfp.nb_prel, 0)) as nb_exps
                FROM agenti ag
                INNER JOIN centre ce ON ag.cod_centru = ce.id
                inner join ( select sc.curier as agent_id, MIN(sc.data) as first_liv, count(sc.cod) as nb_liv
                    from scanari_coduri sc
                    where sc.data {$cond_data} and sc.tip in (5,7,8,10,11,12,13,14,15,17,20,21,26,27,28,30,33,34,35)
                    and sc.is_awb = 1
                    group by sc.curier, DATE(sc.data)
                ) scfl on scfl.agent_id = ag.cod_ag
                left join ( select sc.curier as agent_id, MAX(sc.data) as last_liv
                    from scanari_coduri sc
                    where sc.data {$cond_data} and sc.tip in (5,7,8,10,11,12,13,14,15,17,20,21,26,27,28,30,33,34,35)
                    and sc.is_awb = 1
                    group by sc.curier, DATE(sc.data)
                ) scll on scll.agent_id = ag.cod_ag and  DATE(scfl.first_liv) = DATE(scll.last_liv)
                left join ( select sc.curier as agent_id, MIN(sc.data) as first_prel, count(sc.cod) as nb_prel
                    from scanari_coduri sc
                    where sc.data {$cond_data} and sc.tip = 36
                    and sc.is_awb = 1
                    group by sc.curier, DATE(sc.data)
                ) scfp on scfp.agent_id = ag.cod_ag and  DATE(scfl.first_liv) = DATE(scfp.first_prel)
                left join ( select sc.curier as agent_id, MAX(sc.data) as last_prel
                    from scanari_coduri sc
                    where sc.data {$cond_data} and sc.tip = 36
                    and sc.is_awb = 1
                    group by sc.curier, DATE(sc.data)
                ) sclp on sclp.agent_id = ag.cod_ag and  DATE(scfl.first_liv) = DATE(sclp.last_prel)
                left join (
                    select agent_id, MIN(created_at) as first_login
                    from ag_login_history
                    where created_at {$cond_data}
                    group by agent_id, DATE(created_at)
			    ) agh on ag.cod_ag = agh.agent_id and DATE(scfl.first_liv) = DATE(agh.first_login)
                left join ( select curier as agent_id, MIN(data) as first_bo
                    from scanari_borderouri
                    where data {$cond_data} and tip = 4
                    group by curier, DATE(data)
                ) sb on sb.agent_id = ag.cod_ag and  DATE(scfl.first_liv) = DATE(sb.first_bo)
                left join ( select agent_id, MAX(data) as last_decont
                    from decont_agent
                    where data {$cond_data}
                    group by agent_id, DATE(data)
                ) da on da.agent_id = ag.cod_ag and  DATE(scfl.first_liv) = DATE(da.last_decont)
                left join ( select agent_id, MAX(data) as last_inc_zi
                    from decont_inc_zi
                    where data {$cond_data}
                    group by agent_id, DATE(data)
                ) di on di.agent_id = ag.cod_ag  and  DATE(scfl.first_liv) = DATE(di.last_inc_zi)
                WHERE {$cond}
                ORDER BY " . $sidx . " " . $sord . " LIMIT " . $start . " , " . $limit;
        
        $sql = $this->db->QFetchRowArray($query);
        $responce->page = $page; 
        $responce->total = $total_pages; 
        $responce->records = $count; 
        
        if (!empty($sql)) {
            foreach ($sql as $key => $row) {  
                $first_lp = null;
                $last_lp = null;
                $first_tip = null;
                $last_tip = null;

                $first_liv = $this->CreatePhpDate($row['first_liv']);
                $first_prel = $this->CreatePhpDate($row['first_prel']);
                $last_liv = $this->CreatePhpDate($row['last_liv']);
                $last_prel = $this->CreatePhpDate($row['last_prel']);
                
                if($first_liv != null && $first_prel != null) {
                    $min_timestamp = min($first_liv->getTimestamp(), $first_prel->getTimestamp());
                    $first_lp = $first_liv->setTimestamp($min_timestamp);
                    $first_tip = $first_liv->getTimestamp() == $min_timestamp ? 'Livrare' : 'Preluare';
                }
                else if($first_liv != null){
                    $first_lp = $first_liv;
                    $first_tip = 'Livrare';
                }
                else if($first_prel != null){
                    $first_lp = $first_prel;
                    $first_tip = 'Preluare';
                }
                if($last_liv != null && $last_prel != null) {
                    $max_timestamp = max($last_liv->getTimestamp(), $last_prel->getTimestamp());
                    $last_lp = $last_liv->setTimestamp($max_timestamp);
                    $last_tip = $last_liv->getTimestamp() == $max_timestamp ? 'Livrare' : 'Preluare';
                }
                else if($last_liv != null){
                    $last_lp = $last_liv;
                    $last_tip = 'Livrare';
                }
                else if($last_prel != null){
                    $last_tip = 'Preluare';
                    $last_lp = $last_prel;
                }

                if(!empty($first_lp))
                    $first_lp = $first_lp->format('d.m.Y H:i');
                if(!empty($last_lp))
                    $last_lp = $last_lp->format('d.m.Y H:i');
                if(!empty($row['first_login']))
                    $row['first_login'] = $this->CreateDate($row['first_login'], 'd.m.Y H:i');
                if(!empty($row['first_bo']))
                    $row['first_bo'] = $this->CreateDate($row['first_bo'], 'd.m.Y H:i');
                if(!empty($row['last_inc_zi']))
                    $row['last_inc_zi'] = $this->CreateDate($row['last_inc_zi'], 'd.m.Y H:i');
                if(!empty($row['last_decont']))
                    $row['last_decont'] = $this->CreateDate($row['last_decont'], 'd.m.Y H:i');
                $responce->rows[$key]['id']=$row['cod_ag'];
                $responce->rows[$key]['cell'] = array(
                										$row['cod_ag'],
                                                        strtoupper($row['nume_ag']),
                                                        $row['centru'],
                                                        $row['telefon'],
                                                        $row['tip'],
                                                        $row['first_login'],
                                                        $row['first_bo'] ?? "",
                                                        $first_lp ?? "",
                                                        $first_tip ?? "",
                                                        $last_lp ?? "",
                                                        $last_tip ?? "",
                                                        $row['last_inc_zi'],
                                                        $row['last_decont'],
                                                        $row['nb_exps'] ?? 0
                                                    );
            }
        }
        return json_encode($responce);
    }

    function PontajAgenti_XLS(){
        $data_start = $data_final = date('Y-m-d');
        $dateTime_start = $data_start . " 00:00:00";
        $dateTime_final = $data_final . " 23:59:59";   
        $cond_data = " between '".$dateTime_start."' AND '".$dateTime_final."'";
		$cond = "scfl.first_liv {$cond_data}";
        
        $vars = $_POST ?? [];
        $vars = is_array($vars) ? $vars : [];
        if(isset($vars['data_start']) && isset($vars['data_final'])){
            $data_start = $this->TransformDate($vars['data_start']);
            $data_final = $this->TransformDate($vars['data_final']);  
            $dateTime_start = $data_start . " 00:00:00";
            $dateTime_final = $data_final . " 23:59:59";   
            $cond_data = " between '".$dateTime_start."' AND '".$dateTime_final."'";
            $cond = "scfl.first_liv {$cond_data}";     
        }
        $centru_id = isset($vars['centru']) ? intval($vars['centru']) : 0;
        if($centru_id > 0) $cond .= " and ag.cod_centru = {$centru_id}";
        
        $filename = "pontaj_agenti_{$vars['data_start']}_la_{$vars['data_final']}";

		$sidx = trim($this->sanitize($vars['sidx'] ?? 1));
		$sord = trim($this->sanitize($vars['sord'] ?? 'asc'));
        
        //start generare conditie
        $searchOn = $vars['_search'] ?? false;        
        if ($searchOn == 'true') {
            $searchstr = $this->Strip($vars['filters']);
            $cond .= $this->constructWhere($searchstr);
        }

        $header = array(
            'Cod',
            'Nume',
            'Centru',
            'Telefon',
            'Tip agent',
            'Primul login',
            'Primul borderou',
            'Prima liv/prel',
            'Tip operatie',
            'Ultima liv/prel',
            'Tip operatie',
            'Ultimul ZET',
            'Ultimul decont',
            'Nr. expeditii'
        );

        $query="SELECT ag.cod_ag, ag.nume_ag, ce.nume as centru, ag.telefon, 
                CASE ag.tip WHEN 0 THEN 'Curier' WHEN 1 THEN 'Centru' WHEN 2 THEN 'Departament' WHEN 3 THEN 'HUB' WHEN 4 THEN 'SBK' ELSE 'UNKNOWN' END as tip_agent,
                agh.first_login, sb.first_bo, 
                scfl.first_liv, scfp.first_prel, scll.last_liv, sclp.last_prel, 
                di.last_inc_zi, da.last_decont, (IFNULL(scfl.nb_liv, 0) + IFNULL(scfp.nb_prel, 0)) as nb_exps
                FROM agenti ag
                INNER JOIN centre ce ON ag.cod_centru = ce.id
                inner join ( select sc.curier as agent_id, MIN(sc.data) as first_liv, count(sc.cod) as nb_liv
                    from scanari_coduri sc
                    where sc.data {$cond_data} and sc.tip in (5,7,8,10,11,12,13,14,15,17,20,21,26,27,28,30,33,34,35)
                    and sc.is_awb = 1
                    group by sc.curier, DATE(sc.data)
                ) scfl on scfl.agent_id = ag.cod_ag
                left join ( select sc.curier as agent_id, MAX(sc.data) as last_liv
                    from scanari_coduri sc
                    where sc.data {$cond_data} and sc.tip in (5,7,8,10,11,12,13,14,15,17,20,21,26,27,28,30,33,34,35)
                    and sc.is_awb = 1
                    group by sc.curier, DATE(sc.data)
                ) scll on scll.agent_id = ag.cod_ag and  DATE(scfl.first_liv) = DATE(scll.last_liv)
                left join ( select sc.curier as agent_id, MIN(sc.data) as first_prel, count(sc.cod) as nb_prel
                    from scanari_coduri sc
                    where sc.data {$cond_data} and sc.tip = 36
                    and sc.is_awb = 1
                    group by sc.curier, DATE(sc.data)
                ) scfp on scfp.agent_id = ag.cod_ag and  DATE(scfl.first_liv) = DATE(scfp.first_prel)
                left join ( select sc.curier as agent_id, MAX(sc.data) as last_prel
                    from scanari_coduri sc
                    where sc.data {$cond_data} and sc.tip = 36
                    and sc.is_awb = 1
                    group by sc.curier, DATE(sc.data)
                ) sclp on sclp.agent_id = ag.cod_ag and  DATE(scfl.first_liv) = DATE(sclp.last_prel)
                left join (
                    select agent_id, MIN(created_at) as first_login
                    from ag_login_history
                    where created_at {$cond_data}
                    group by agent_id, DATE(created_at)
			    ) agh on ag.cod_ag = agh.agent_id and DATE(scfl.first_liv) = DATE(agh.first_login)
                left join ( select curier as agent_id, MIN(data) as first_bo
                    from scanari_borderouri
                    where data {$cond_data} and tip = 4
                    group by curier, DATE(data)
                ) sb on sb.agent_id = ag.cod_ag and  DATE(scfl.first_liv) = DATE(sb.first_bo)
                left join ( select agent_id, MAX(data) as last_decont
                    from decont_agent
                    where data {$cond_data}
                    group by agent_id, DATE(data)
                ) da on da.agent_id = ag.cod_ag and  DATE(scfl.first_liv) = DATE(da.last_decont)
                left join ( select agent_id, MAX(data) as last_inc_zi
                    from decont_inc_zi
                    where data {$cond_data}
                    group by agent_id, DATE(data)
                ) di on di.agent_id = ag.cod_ag  and  DATE(scfl.first_liv) = DATE(di.last_inc_zi)
                WHERE {$cond}
                ORDER BY " . $sidx . " " . $sord;
        
        $sql = $this->db->QFetchRowArray($query);

        $ret = [];
        if (!empty($sql)) {
            foreach ($sql as $key => $row) {  
                $ret[$key] = $row;
                
                $first_lp = null;
                $last_lp = null;
                $first_tip = null;
                $last_tip = null;

                $first_liv = $this->CreatePhpDate($row['first_liv']);
                $first_prel = $this->CreatePhpDate($row['first_prel']);
                $last_liv = $this->CreatePhpDate($row['last_liv']);
                $last_prel = $this->CreatePhpDate($row['last_prel']);

                if($first_liv != null && $first_prel != null) {
                    $min_timestamp = min($first_liv->getTimestamp(), $first_prel->getTimestamp());
                    $first_lp = $first_liv->setTimestamp($min_timestamp);
                    $first_tip = $first_liv->getTimestamp() == $min_timestamp ? 'Livrare' : 'Preluare';
                }
                else if($first_liv != null){
                    $first_lp = $first_liv;
                    $first_tip = 'Livrare';
                }
                else if($first_prel != null){
                    $first_lp = $first_prel;
                    $first_tip = 'Preluare';
                }
                if($last_liv != null && $last_prel != null) {
                    $max_timestamp = max($last_liv->getTimestamp(), $last_prel->getTimestamp());
                    $last_lp = $last_liv->setTimestamp($max_timestamp);
                    $last_tip = $last_liv->getTimestamp() == $max_timestamp ? 'Livrare' : 'Preluare';
                }
                else if($last_liv != null){
                    $last_lp = $last_liv;
                    $last_tip = 'Livrare';
                }
                else if($last_prel != null){
                    $last_tip = 'Preluare';
                    $last_lp = $last_prel;
                }

                if(!empty($first_lp))
                    $first_lp = $first_lp->format('d.m.Y H:i');
                if(!empty($last_lp))
                    $last_lp = $last_lp->format('d.m.Y H:i');
                if(!empty($row['first_login']))
                    $ret[$key]['first_login'] = $this->CreateDate($row['first_login'], 'd.m.Y H:i');
                if(!empty($row['first_bo']))
                    $ret[$key]['first_bo'] = $this->CreateDate($row['first_bo'], 'd.m.Y H:i');
                if(!empty($row['last_inc_zi']))
                    $ret[$key]['last_inc_zi'] = $this->CreateDate($row['last_inc_zi'], 'd.m.Y H:i');
                if(!empty($row['last_decont']))
                    $ret[$key]['last_decont'] = $this->CreateDate($row['last_decont'], 'd.m.Y H:i');
                    
                $ret[$key]['nb_exps'] = $row['nb_exps'] ?? 0;
                $ret[$key]['first_liv'] = $first_lp ?? "";
                $ret[$key]['first_prel'] = $first_tip ?? "";
                $ret[$key]['last_liv'] = $last_lp ?? "";
                $ret[$key]['last_prel'] = $last_tip ?? ""; 
            }
            $this->sqlToXls($filename, $ret, $header);
        }
    }
}