<?php

/**
 * W o r k s p a c e
 *
 */
class ModulOperatori extends BackEnd {

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

        $this->vars['title_page'] = 'Operatori';
        $this->page_prefix = 'operatori_';

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
        if (isset($arr[1]) && $arr[1] == 'date_personale')
            $this->final_result =  $this->DatePersonale();
        //nivel acces 10        
        else if (in_array("operatori", $this->user_rights) || $this->user_profile == 10){

            if (isset($arr[1]) && $arr[1] == 'listare')
                $this->final_result = $this->ListareOperatori();
            else if (isset($arr[1]) && $arr[1] == 'listare_json')
                    echo $this->ListareOperatori_JSON();
            else if (isset($arr[1]) && $arr[1] == 'editare')
                    echo $this->EditareOperator();
            else if (isset($arr[1]) && $arr[1] == 'centre_json')
                    echo $this->JSON_Centre();
            else if (isset($arr[1]) && $arr[1] == 'drepturi_acces')
                    $this->final_result = $this->DrepturiAcces();
            else if (isset($arr[1]) && $arr[1] == 'drept_acces')
                    echo $this->DreptAcces($arr[2]);
            else if (isset($arr[1]) && $arr[1] == 'editare_drept_acces')
                    echo $this->EditareDreptAcces();
            else if (isset($arr[1]) && $arr[1] == 'print' && !empty($arr[2]))
                    echo $this->PrintCnpTCPDF($arr[2],$arr[3]);
        }else
            $this->final_result = $this->PageNotFound();
    }
    
 //-------------------------------- functii ----------------------------------------
    
    
    function ListareOperatori() {
        $this->vars['title_page'] = 'Operatori';
        $vars = [];
        return $this->Parse($this->page_prefix . 'listare.html', $vars);
    }

	function JSON_Centre() {               
        $query="SELECT id, nume FROM {$this->tables['centre']} WHERE deleted=0 ORDER BY nume ASC";
        $sql = $this->db->QFetchRowArray($query);
        $responce=[];
        if (!empty($sql)) {
            foreach ($sql as $row) {                
                $responce[$row['id']] = $row['nume']; 
            }
        }
        return json_encode($responce);
    } 

    function ListareOperatori_JSON() {
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
        //end generare conditie
        
        $query="SELECT COUNT(u.id) as nr            
            FROM {$this->tables['users']} u
            INNER JOIN {$this->tables['centre']} c ON u.centru=c.id 
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

        $query="SELECT u.id as f_id,u.user as f_user,u.nume as f_nume,u.mail as f_mail,u.functie as f_functie,
                c.nume as f_centru,u.nivel_acces as f_nivel_acces,u.activ as f_activ,u.autologin as f_autologin,
                lh.f_last_login, lh.f_last_ip
                FROM users u
                LEFT JOIN centre c ON u.centru = c.id
                LEFT JOIN 
                (
                    select lh1.user_id, lh1.date as f_last_login, lh1.ip as f_last_ip
                    from login_history lh1
                    where lh1.date = (
                            select MAX(lh2.date) from login_history lh2 where lh1.user_id = lh2.user_id group by lh2.user_id
                        )
                ) as lh on lh.user_id = u.id
                WHERE {$cond} and u.nivel_acces <> 10 
                ORDER BY " . $sidx . " " . $sord . " LIMIT " . $start . " , " . $limit;
        
        $sql = $this->db->QFetchRowArray($query);
        $responce->page = $page; 
        $responce->total = $total_pages; 
        $responce->records = $count; 
        
        if (!empty($sql)) {
            foreach ($sql as $key => $row) {

                $autologin = (strlen($row['f_autologin']) > 0)?' <a href="javascript:;" onclick="cnpToPDF('.$row['f_id'].',1);" style="text-decoration:none;">Autologin</a>':'';

                $responce->rows[$key]['id']=$row['f_id'];
                $responce->rows[$key]['cell'] = array(
                                                        $row['f_id'],
                                                        $row['f_user'],
                                                        $row['f_nume'],
                                                        $row['f_mail'],
                                                        $row['f_functie'],
                                                        $row['f_centru'],
                                                        $row['f_nivel_acces'],
                                                        null,
                                                        null,
                                                        $row['f_activ'],
                                                        '<a href="javascript:;" onclick="cnpToPDF('.$row['f_id'].',0);" style="text-decoration:none;">CNP</a> '.$autologin,
                                                        $row['f_autologin'],
                                                        $row['f_last_login'],
                                                        $row['f_last_ip']
                                                    );
            }
        }
        return json_encode($responce);
    }
  
    function EditareOperator(){       
        $vars = $_REQUEST; 
        $vars['id'] = intval($vars['id']);
        if($vars['oper'] == 'edit'){//editare
            if(empty($vars['user'])) {
                header('HTTP/1.1 500 Username este obligatoriu');
                    return "Username este obligatoriu";
            }
            $this->PrepareForInsert ($vars,'nume,mail,functie,centru,user');
            $vu=[];
            if(!empty($vars['user']) && !empty($vars['id'])){

                $query="SELECT * FROM users WHERE id=".intval($vars['id']);
                $userData = $this->db->QFetchArray($query);
                if(empty($userData)) {
                    header('HTTP/1.1 500 Utilizator inexistent');
                    return "Utilizator inexistent";
                }

                if($vars['nivel_acces'] == 10) {
                    header('HTTP/1.1 500 Acest nivel de acces nu este disponibil');
                    return "Acest nivel de acces nu este disponibil";
                }

                if(!empty($vars['parola'])) {
                    $vu['data_parola'] = date('Y-m-d H:i:s');
                    $vu['hashParola'] = password_hash($vars['parola'], PASSWORD_DEFAULT);   
                }

                if(!empty($vars['cnp']) && $userData['cnp']!=$vars['cnp'])
                    $vu['cnp'] = $vars['cnp'];

                $vu['user'] = $vars['user'];
				$vu['nume'] = $vars['nume'];
				$vu['mail'] = $vars['mail'];
				$vu['functie'] = $vars['functie'];
                $vu['centru'] = intval($vars['centru']);
				$vu['nivel_acces'] = intval($vars['nivel_acces']); 
                $vu['operator'] = $this->user_id;
                $vu['activ'] = $vars['activ'];
                if(strlen($vars['autologin']))
				    $vu['autologin'] = $vars['autologin'];
                $vu['data_op'] = time();
                $this->db->QueryUpdate($this->tables['users'], $vu, "id={$vars['id']}");
            }
        }else if($vars['oper'] == 'add'){//adaugare
            if(empty($vars['user'])) {
                header('HTTP/1.1 500 Username este obligatoriu');
                    return "Username este obligatoriu";
            }
            $this->PrepareForInsert ($vars,'nume,mail,functie,centru,user');
            $vars['centru'] = intval($vars['centru']);
            $vars['nivel_acces'] = intval($vars['nivel_acces']);
            
            if($vars['nivel_acces'] == 10) {
                header('HTTP/1.1 500 Acest nivel de acces nu este disponibil');
                return "Acest nivel de acces nu este disponibil";
            }
            if(empty($vars['parola'])) {
                header('HTTP/1.1 500 Parola este obligatorie');
                    return "Parola este obligatorie";
            }

            $vi=[];
            $vi['data_parola'] = date('Y-m-d H:i:s');
            $vi['hashParola'] = password_hash($vars['parola'], PASSWORD_DEFAULT);  

            if(!empty($vars['cnp']))
                $vi['cnp'] = $vars['cnp'];

            $vi['user'] = $vars['user'];
            $vi['nume'] = $vars['nume'];
            $vi['mail'] = $vars['mail'];
            $vi['functie'] = $vars['functie'];
            $vi['centru'] = intval($vars['centru']);  
            $vi['nivel_acces'] = intval($vars['nivel_acces']); 
            $vi['operator'] = $this->user_id;
            $vi['activ'] = $vars['activ']; 
            if(strlen($vars['autologin']))
                $vi['autologin'] = $vars['autologin'];
            
            $vi['data_op'] = time();            
            $this->db->QueryInsert($this->tables['users'], $vi);
        }else if($vars['oper'] == 'del'){//stergere
            $this->db->QueryUpdate($this->tables['users'], ['activ' => 0], "id={$vars['id']}");                
        }
		return true;
    }
      
  
  	function DrepturiAcces(){
		$this -> vars['title_page'] = 'Drepturi Acces';
			
		return $this -> Parse($this -> page_prefix . 'drepturi_acces.html', []);
	} 
  
  	function DreptAcces($profile){
  		$vars=[];
		$vars['id'] = $profile;
		
		$query="SELECT * FROM {$this->tables['fields']} WHERE id={$profile}";
        $sql = $this->db->QFetchArray($query);

        $drepturi = explode(',',$sql['value']);
        foreach ($drepturi as $key=>$value) {
            $vars[$value] = ' checked="checked" ';
        }
		return $this -> Parse($this -> page_prefix . 'drept_acces.html', $vars);
	} 
  
  function EditareDreptAcces(){       
		$post = $this->_unserializeJQuery($_POST['data']);

		$drepturi='';
		foreach ($post as $key => $value) {
			if($key!='id'){
				$drepturi .= $key.',';
			}
		}

		if(!empty($drepturi)){
	        $this->db->QueryUpdate($this->tables['fields'], ['value' => substr($drepturi, 0,-1)], "id=" . $post['acces_id']);	
			return 'Modificarile au fost salvate!';
		}
		return 'Eroare!';
    }
      
	  
	
	 function DatePersonale($error='') {
        $this->vars['title_page'] = 'Date Personale';
        $sql = $this->db->QFetchArray("SELECT nume, mail, print_awb, telefon, hashParola FROM {$this->tables['users']} WHERE id={$this->user_id}");
        if (empty($sql))
            return $this->Error("Inexistent ID");

        if (empty($_POST['step']) || $_POST['step'] == 1) {
            $vars = $sql;
            if ($error != '')
                $this->vars['error'] = $error;
            $vars['cod'] = $this->GetCod();
            unset($vars['hashParola']);
		  	$vars['print_awb_'.$vars['print_awb']] = 'checked="checked"';
		  
            return $this->Parse($this->page_prefix . 'date_personale.html', $vars);
        }else if (!empty($_POST['step']) && $_POST['step'] == 2) {
            $vars = $_POST;
            $_POST['step'] = 1;
            $vars = $this->PrepareForInsert($vars, 'title');
            if (!empty($_POST['cod'])) {

                if (isset($vars['but_submit'])) {
					if (!$this->ValidateFields($vars, 'nume,telefon,mail'))
						return $this->DatePersonale($this->Error('Completati Persoana, Emailul si Telefonul de contact!'));
                    // insert in MySQL                
                    $vu = [];
                    $vu['nume'] = $vars['nume'];
					$vu['mail'] = $vars['mail'];
					$vu['print_awb'] = $vars['print_awb'];
                    $vu['telefon'] = $vars['telefon'];
                    $vu['data_op'] = date("Y-m-d H:i:s");
                    $vu['operator'] = $this->user_id;
                    //preprocessing
                    $this->db->QueryUpdate($this->tables['users'], $vu, "id = {$this->user_id}");
                }else if (isset($vars['but_submit2'])) {
                    if (!$this->ValidateFields($vars, 'pass')) {
                        return $this->DatePersonale($this->Error('Va rugam sa introduceti parola curenta!'));
                    }
					if (password_verify($vars['pass'], $sql['hashParola']) == false) {
                        return $this->DatePersonale($this->Error('Parola curenta este incorecta!'));
                    }
					
                    if (!$this->ValidateFields($vars, 'pass1,pass2'))
                        return $this->DatePersonale($this->Error('Completati ambele parole'));
                    //the two new passwords
                    if ($vars['pass1'] != $vars['pass2']) {
                        return $this->DatePersonale($this->Error('Ambele parole trebuie sa fie identice.'));
                    }
                    //insert the password
                    $this->db->QueryUpdate($this->tables['users'], ['hashParola' => password_hash($vars['pass1'], PASSWORD_DEFAULT), 'data_parola' => date('Y-m-d H:i:s')], "id = {$this->user_id}");
                }
                return $this->DatePersonale($this->Error('Modificarile au fost salvate cu succes!', 1));
            }else
                return $this->DatePersonale($this->Error('Browser refresh!'));
        }
    }
    
    function PrintCnpTCPDF($id, $autologin = 0){
    	require_once "CnpPdf.php";
    	
        if(empty($id)) return $this->Error('User invalid!');
        
		$query = "SELECT user, nume, cnp, autologin FROM {$this->tables['users']} WHERE id=".$id;
        $sql = $this->db->QFetchArray($query);
		if(!is_array($sql)) return $this->Error('User invalid!');
		if(empty($sql['cnp'])) return $this->Error('Userul nu are cnp!');
		
		$vars = [];
		$filename = $sql['user'].'.pdf';
		
		$vars['cnp'] = ($autologin ==1)? $sql['autologin']:$sql['cnp'];
		$vars['nume'] = $sql['nume'];
		
		$pdf = new CnpPdf($vars);
		$pdf->AddPage();
		$pdf->makeCnp();
		// move pointer to last page
		$pdf->lastPage();
		
		//I: send the file inline to the browser. The plug-in is used if available. The name given by filename is used when one selects the "Save as" option on the link generating the PDF.
		//D: send to the browser and force a file download with the name given by filename.		
		$pdf->Output($filename,'D');
	    exit;

    }
	
	  
}
//end class
?>
