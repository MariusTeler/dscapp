<?php

/**
 * W o r k s p a c e
 *
 */
class ModulGeocoderAddress extends BackEnd {

    public $final_result;
    public $page_prefix;

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

        $this->vars['title_page'] = 'Adrese Geocoder';
        $this->page_prefix = 'geocoder_address_';

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
        if (in_array("geocoder_address", $this->user_rights) || in_array($this->user_id, parent::CAN_MODIFY_GEOCODE_FINANCIAR)) {
            if (isset($arr[1]) && $arr[1] == 'listare')
                    $this->final_result = $this->ListareGeocoderAddress();
            else if (isset($arr[1]) && $arr[1] == 'listare_json')
                    echo $this->ListareGeocoderAddress_JSON();
            else if (isset($arr[1]) && $arr[1] == 'editare')
                    echo $this->EditareGeocoderAddress();
            else if (isset($arr[1]) && $arr[1] == 'zone_json')
                    echo $this->JSON_Zone();
        }else
            $this->final_result = $this->PageNotFound();
    }
    
 //-------------------------------- functii ----------------------------------------
    
    
    function ListareGeocoderAddress() {
        $this->vars['title_page'] = 'Adrese Geocoder';
        return $this->Parse($this->page_prefix . 'listare.html');
    }

    function ListareGeocoderAddress_JSON() {
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
        
        $query="SELECT COUNT(agd.id) as nr
                FROM address_geocode agd
                left join localitati lc on lc.cod_lc = agd.localitate_id
                left join zones zo on agd.zona_id = zo.id
                left join centre ce on ce.id = zo.centru_id
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

        $query="SELECT agd.id, agd.adresa, agd.formatted_adresa, agd.lat, agd.lng, agd.provider, agd.neatness,
                lc.nume_lc as localitate, 
                zo.name as zona, ce.nume as centru, ce.label as centru_cod
                FROM address_geocode agd
                left join localitati lc on lc.cod_lc = agd.localitate_id
                left join zones zo on agd.zona_id = zo.id
                left join centre ce on ce.id = zo.centru_id
                WHERE {$cond}
                ORDER BY " . $sidx . " " . $sord . " LIMIT " . $start . " , " . $limit;

        $sql = $this->db->QFetchRowArray($query);
        $responce->page = $page; 
        $responce->total = $total_pages; 
        $responce->records = $count;
        
        if (!empty($sql)) {
            foreach ($sql as $key => $row) {  
                $responce->rows[$key]['id']=$row['id'];
                $responce->rows[$key]['cell'] = array(
                    $row['adresa'], 
                    $row['localitate'],
                    $row['zona'],
                    $row['centru'],
                    $row['provider'],
                    $row['neatness'],
                    $row['formatted_adresa'],
                    $row['lat'],
                    $row['lng'],                                                   
                );
            }
        }
        return json_encode($responce);
    }
    
    function EditareGeocoderAddress(){
        $id = intval($_POST['id'] ?? 0);
        $zona_id = intval($_POST['zona'] ?? 0);
        $responce = new StdClass();
		$responce->success = 1;
		if($id == 0)
		{
			$responce->success = 0;
			$responce->error = 'Eroare : id-ul nu este valid : '.$id;
			return json_encode($responce);
		}

		if($zona_id == 0)
        {
            $responce->success = 0;
            $responce->error = 'Eroare : zona nu este valida : '.$zona_id;
            return json_encode($responce);
        }
        $this->db->QueryUpdate('address_geocode', ['zona_id' => $zona_id, 'neatness' => 'ROOFTOP', 'provider' => 'DSC'], "id = {$id}");

		return json_encode($responce);
	}
    
    function JSON_Zone() {           
        $query="select id, name FROM zones ORDER BY name";
        $sql = $this->db->QFetchRowArray($query);
        $responce = [];
        if (!empty($sql)) {
        	$responce[] = "0:   ";
            foreach ($sql as $row) {                
                $responce[] = "{$row['id']}:{$row['name']}";
            }
        }      
        return implode(';', $responce);
    }
}