<?php

/**
 * W o r k s p a c e
 *
 */
class ModulRute extends BackEnd {

    public $final_result;
    public $action_module;
    public $page_prefix;
    public $table;

    /**
     * The constructor for the 'Workspace' class
     * Calls BackEnd constructor
     * Cals Actions function
     *
     * @param integer $act  (0/1) Specifies if actions are alowed or not
     * @access public
     * @see Actions()
     */
    function __construct($config = 0, $act = 1, $db = 0) {
        parent :: __construct($config, $db);

        $this->vars['title_page'] = 'Rute';
        $this->page_prefix = 'rute_';

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
  
        if (!empty($this->user_profile))
            $this->ActionsNivelAcces();    
        else
            $this->final_result = $this->PageNotFound();
    }

   
    function ActionsNivelAcces() {
        $this->user_rights = $this->GetDrepturiUtilizator($this->user_profile);
        $arr = $this->GenerateArr();
        //nivel acces 10        
        if(isset($arr[1]) && $arr[1] == 'json' && isset($arr[2]) && $arr[2]=='liste_rute_combo'){
			echo $this->JSON_ListeRuteCombo();
            return;        
        }
        if (in_array("rute", $this->user_rights) || $this->user_profile==10){
            if (isset($arr[1]) && $arr[1] == 'ruta_noua')
                $this->final_result = $this->RutaNoua();
            else if (isset($arr[1]) && $arr[1] == 'ruta_editare')
                $this->final_result = $this->RutaEditare(isset($arr[2])?$arr[2]:"");
            else if (isset($arr[1]) && $arr[1] == 'ruta_stergere')
                $this->final_result = $this->RutaStergere(isset($arr[2])?$arr[2]:"");
            else if (isset($arr[1]) && $arr[1] == 'lista_rute')
                $this->final_result = $this->ListaRute();
            else if (isset($arr[1]) && $arr[1] == 'json' && isset($arr[2]) && $arr[2] == 'liste_rute')
                echo $this->JSON_ListeRute();
        }
        else
            $this->final_result = $this->PageNotFound();
    }
    
 //-------------------------------- functii ----------------------------------------
    
    function JSON_ListeRuteCombo(){
        $responce = new StdClass();
        $responce->total = 0;
        $responce->rezultat=[];
        $limit = 10;
        $limit= intval($_GET['maxRows'] ?? $limit);
        $filter = strtoupper($this->sanitize(($_GET['name_startsWith'] ?? '')));
        if($limit > 20) $limit = 20;
        if(empty($filter))
            return json_encode($responce);

        $cond = " AND ru.denumire LIKE :name_startsWith";

        $query = "SELECT ru.id, ru.denumire
                FROM rute as ru
                WHERE ru.activ = 1 {$cond}
                ORDER BY ru.denumire LIMIT {$limit}";
        $sql = $this->db->QFetchRowArray($query, ['name_startsWith'=>"%".$filter."%"]);
        if (!empty($sql)) {
            $responce->total = count($sql);
            foreach ($sql as $key => $row) {
                $responce->rezultat[$key]['cod'] = $row['id'];
                $responce->rezultat[$key]['nume'] = strtoupper($row['denumire']);
            }
        }
        return json_encode($responce);
    }

    function RutaNoua($message=''){
        $this->vars['title_page'] = 'Ruta Noua';
        $vars = [];
        if ((empty($_POST['step'])) || ($_POST['step'] == 1)) {
            $this->vars['title_info'] = $message;
            $vars['cod'] = $this->GetCod();
            $vars['data_start'] = date('d.m.Y');
            $vars['data_final'] = date('d.m.Y');

            $vars['centru'] = 47;
            $vars['centru_nume'] = 'OTOPENI';

            $vars['centre_destinatie'] = $this->GenerareListaCentre([],'centre_destinatie');


            return $this->Parse($this->page_prefix .'ruta_noua.html', $vars);
        }
        if (!empty($_POST['step']) && $_POST['step'] == 2) {
            $vars = $_POST;
            $_POST['step'] = 1;
            //validaree
            if ($_POST['cod'] != '') {

                if(!empty($vars['centre_destinatie']))
                    $centre_destinatie = implode(',', $vars['centre_destinatie']);

                $vi=[];
                $vi['operator'] = $this->user_id ;
                $vi['data'] = date('Y-m-d H:i:s');
                $vi['centru'] = $vars['centru'];
                $vi['denumire'] = $vars['denumire'];
                $vi['centre_destinatie'] = $centre_destinatie;
                $vi['observatii'] = $vars['observatii'];
                $vi['activ'] = 1;

                $id = $this->db->QueryInsert($this->tables['rute'], $vi);

                return $this->RutaNoua($this->ErrorSmall('Ruta '.$vars['denumire'].' a fost generata!', 1));
            }
            return $this->RutaNoua($this->ErrorSmall('Nu este acceptat refesh-ul'));
        }
        return $this->RutaNoua($this->ErrorSmall('Nu este acceptat refesh-ul'));
    }

    function RutaEditare($id,$message=''){

        $this->vars['title_page'] = 'Ruta Noua';
        $vars = [];
        if ((empty($_POST['step'])) || ($_POST['step'] == 1)) {
            $this->vars['title_info'] = $message;

            $vars['cod'] = $this->GetCod();

            $query = "SELECT * FROM rute WHERE id={$id}";
            $sql = $this->db->QFetchArray($query);
            if(empty($sql)) return 'Ruta nu exista!';
            $vars['centru'] = $sql['centru'];
            $vars['kg_max'] = $sql['kg_max'];

            $centre = $this->GetCentre();
            $vars['centru_NUME'] = $centre[$sql['centru']];

            $vars['denumire'] = $sql['denumire'];
            $vars['observatii'] = $sql['observatii'];

            $centre = [];
            $centre = explode(',',$sql['centre_destinatie']);
            $vars['centre_destinatie'] = $this->GenerareListaCentre($centre,'centre_destinatie');

            return $this->Parse($this->page_prefix .'ruta_noua.html', $vars);

        }
        if (!empty($_POST['step']) && $_POST['step'] == 2) {
            $vars = $_POST;
            if ($_POST['cod'] != '') {
                unset($_POST);
                $_POST['step'] = 1;

                if(!empty($vars['centre_destinatie']))
                $centre_destinatie = implode(',', $vars['centre_destinatie']);

                $vi=[];
                $vi['operator'] = $this->user_id ;
                $vi['data'] = date('Y-m-d H:i:s');
                $vi['centru'] = $vars['centru'];
                $vi['denumire'] = $vars['denumire'];
                $vi['centre_destinatie'] = $centre_destinatie;
                $vi['kg_max'] = intval($vars['kg_max']);
                $vi['observatii'] = $vars['observatii'];
                $vi['activ'] = 1;

                $this->db->QueryUpdate($this->tables['rute'], $vi,'id='.$id);
                return $this->RutaEditare($id,$this->ErrorSmall('Ruta '.$vars['denumire'].' a fost editata!', 1));
            }
            return $this->RutaEditare($id,$this->ErrorSmall('Nu este acceptat refesh-ul'));
        }
        return $this->RutaEditare($id,$this->ErrorSmall('Nu este acceptat refesh-ul'));
    }

    function RutaStergere($id){
        $this->db->Query("update rute set activ = 0 WHERE id={$id}");
        return 'true';
    }

    function ListaRute(){
        $this->vars['title_page'] = 'Lista Rute';

        $vars = [];
        $vars['data_start'] = date('d.m.Y');
        $vars['data_final'] = date('d.m.Y');

        $vars['centru'] = 47;
        $vars['centru_nume'] = 'OTOPENI';

        return $this->Parse($this->page_prefix .'lista_rute.html', $vars);
    }

    function JSON_ListeRute() {
        $cond = '1=1';
        $responce = new StdClass();
        $page = intval($_REQUEST['page'] ?? 1);
        $limit = intval($_REQUEST['rows'] ?? 20);
        $sidx = trim($this->sanitize($_REQUEST['sidx'] ?? 1));
        $sord = trim($this->sanitize($_REQUEST['sord'] ?? 'asc'));

        $searchOn = false;
        if(isset($_REQUEST['_search'])) $searchOn = $this->Strip($_REQUEST['_search']);
        if ($searchOn == 'true') {
            $searchstr = $this->Strip($_REQUEST['filters']);
            $cond .= $this->constructWhere($searchstr);
        }

        $query = "SELECT count(ru.id) as nr
            FROM rute as ru
            LEFT JOIN centre as ce on ru.centru = ce.id
            WHERE ru.activ=1 and {$cond}";
            //print_r($query);die;
        $result = $this->db->QFetchArray($query);
        $count = !empty($result['nr']) ? $result['nr'] : 0;

        $total_pages = $count > 0 ? ceil($count/$limit) : 0;
        if ($page > $total_pages) $page = $total_pages;
        if ($limit<0) $limit = 0; $start = $limit*$page - $limit;
        if ($start<0) $start = 0;

        $query = "SELECT ru.*, ce.nume as centru_expediere
            FROM rute as ru
            LEFT JOIN centre as ce on ru.centru = ce.id
            WHERE ru.activ = 1 and {$cond}
            ORDER BY " . $sidx . " " . $sord . " LIMIT " . $start . " , " . $limit . ";";
            //print_r($query);die;

        $sql = $this->db->QFetchRowArray($query);
        if (!empty($sql)) {
            $centre = $this->GetCentre();
            foreach ($sql as $key => $row) {
                $row['centru_expeditie'] = $centre[$row['centru']];
                $arr_centre_destinatie = explode(',',$row['centre_destinatie']);
                $row['centre_destinatie'] ='';
                foreach($arr_centre_destinatie as $centru){
                    if(isset($centre[$centru])) {
                        if(empty($row['centre_destinatie'])) $row['centre_destinatie'] = $centre[$centru];
                        else $row['centre_destinatie'] .= ', '.$centre[$centru];
                    }
                }

                $row['option'] = '<a title="Editare Ruta" href="'.$this->config['http'].'rute/ruta_editare/' . $row['id'] . '" style="text-decoration:none;">Editare</a>';
                $row['option'] .= ' - <a title="Sterge Ruta" href="javascript:;" onclick="SergereRuta('.$row['id'].');" style="text-decoration:none;">Sterge</a>';


                $responce->rows[$key]['id'] = $row['id'];
                $responce->rows[$key]['cell'] = array($row['id'],$row['denumire'],$row['centru_expeditie'], $row['centre_destinatie'], $row['observatii'], $row['data'],$row['kg_max'],$row['option']);
            }
        }

        $responce->page = $page;
        $responce->total = $total_pages;
        $responce->records = $count;
        return json_encode($responce);
    }
}