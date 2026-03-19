<?php

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class ModulClienti extends BackEnd {

    public $final_result;
    public $action_module;
    public $page_prefix;
    public $site_prefix;
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

        $this->vars['title_page'] = 'Clienti';
        $this->page_prefix = 'clienti_';

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
    function Actions($msg = '') {
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
        if (isset($arr[1]) && $arr[1] == 'adauga_client_pf')
            echo $this->AdaugareClientPFJ();
        else if (isset($arr[1]) && $arr[1] == 'tip_contract' && !empty($arr[2]))
            echo $this->TipContract($arr[2]);
        else if (isset($arr[1]) && $arr[1] == 'km_suplimentari' && !empty($arr[2]))
            echo $this->SelectKmSuplimentari($arr[2]);
        else if (in_array("clienti", $this->user_rights) || $this->user_profile == 10){

            if (isset($arr[1]) && $arr[1] == 'listare')
                $this->final_result = $this->ListareClienti();
            else if (isset($arr[1]) && $arr[1] == 'detalii_client' && !empty($arr[2]))
                    echo $this->DetaliiClient($arr[2]);
            else if (isset($arr[1]) && $arr[1] == 'cont_client' && !empty($arr[2]))
                echo $this->ContClient($arr[2]);
            else if (isset($arr[1]) && $arr[1] == 'detalii_contract' && !empty($arr[2]))
                    echo $this->DetaliiContract($arr[2]);
            else if (isset($arr[1]) && $arr[1] == 'contacte_client' && !empty($arr[2]))
                    echo $this->DetaliiContacteClient($arr[2]);

            else if (isset($arr[1]) && $arr[1] == 'listare_json')
                    echo $this->ListareClienti_JSON();
            else if (isset($arr[1]) && $arr[1] == 'contacte_json')
                echo $this->ListareContacteClienti_JSON();
            else if (isset($arr[1]) && $arr[1] == 'conturi_json')
                echo $this->ListareConturiClienti_JSON();
            else if (isset($arr[1]) && $arr[1] == 'editare_client')
                    echo $this->EditareClient();
            else if (isset($arr[1]) && $arr[1] == 'editare_client_contacte')
                    echo $this->EditareContacte();

            else if (isset($arr[1]) && $arr[1] == 'tarif_lista')
                    $this->final_result = $this->TarifLista(0);
            else if (isset($arr[1]) && $arr[1] == 'detalii_contract_client')
            {
                    if(empty($arr[2])) $arr[2]=0;
                    echo $this->TarifLista($arr[2]);
            }
            else if (isset($arr[1]) && $arr[1] == 'editare_tarif_lista')
                    echo $this->EditareTarifLista();
            else if (isset($arr[1]) && $arr[1] == 'editare_tarif_lista_2')
                    echo $this->EditareTarifLista_2();


            else if (isset($arr[1]) && $arr[1] == 'listare_greutate_json')
                    echo $this->ListareGreutateJson();
            else if (isset($arr[1]) && $arr[1] == 'detalii_row_greutate' && !empty($arr[2]))
                    echo $this->DetaliiRowGreutate($arr[2]);
            else if (isset($arr[1]) && $arr[1] == 'editare_row_greutate')
                    echo $this->EditareRowGreutate();

            else if (isset($arr[1]) && $arr[1] == 'alocare')
                    $this->final_result = $this->AlocareClienti();
            else if (isset($arr[1]) && $arr[1] == 'alocare_json' )
                    echo $this->AlocareClienti_JSON();
            else if (isset($arr[1]) && $arr[1] == 'alocare-clienti')
                    echo $this->AlocareClientiProces();
            else if (isset($arr[1]) && $arr[1] == 'json' && $arr[2] == 'ag_vanzari')
                echo $this->JSON_AgentiVanzari();
            else if (isset($arr[1]) && $arr[1] == 'export' && $arr[2] == 'destinatari')
                echo $this->ExportDestinatari($arr[3]);
        }
        else
            $this->final_result = $this->PageNotFound();
    }

 //-------------------------------- functii ----------------------------------------

 	function JSON_AgentiVanzari() {
		$responce = new StdClass();
        $limit = 10;
        $cond = '';
        if(!empty($_GET['maxRows'])) $limit=$_GET['maxRows'];
        if(!empty($_GET['name_startsWith'])) $cond= "and a.nume LIKE :name_startsWith";

        $query="SELECT a.id, a.nume as ag_vanzari_nume, b.nume as centru
                FROM ag_vanzari a
                LEFT JOIN {$this->tables['centre']} b ON a.centru_id=b.id
                WHERE 1=1 {$cond} and a.activ=1
                ORDER BY a.nume LIMIT {$limit}";
        //error_log($query);
        $sql = $this->db->QFetchRowArray($query, ['name_startsWith'=>Backend::sSanitizeCleanEdges($_GET['name_startsWith'] ?? '')."%"]);
        if (!empty($sql)) {
            $responce->total = count($sql);
            foreach ($sql as $key => $row) {
                $responce->rezultat[$key]['id'] = $row['id'];
                $responce->rezultat[$key]['label'] = strtoupper($row['ag_vanzari_nume']).' ('.strtoupper($row['centru']).')';
				$responce->rezultat[$key]['value'] = strtoupper($row['ag_vanzari_nume']);
            }
        }else{
            $responce->total = 0;
            $responce->rezultat=[];
        }
        //error_log(json_encode($responce));
        return json_encode($responce);
    }


    function ListareClienti() {

		if(!($this->user_profile==106 || $this->user_profile==24 || $this->user_profile==10)) {
			echo 'Eroare'; die;
		}
        $this->vars['title_page'] = 'Clienti : atentie -> cautarea in lista are loc numai dupa apasarea tastei Enter';
		if(!empty($_SESSION['mesaj_eroare']))
        	$this->vars['title_info'] = $_SESSION['mesaj_eroare'];
        unset($_SESSION['mesaj_eroare']);
        $vars = [];
		$vars['btn_reset'] = 'display: none;';
		$vars['detalii_client']= $this->Parse($this->page_prefix . 'detalii.html', $vars);

		$vars['detalii_contract']= $this->Parse($this->page_prefix . 'contract.html', $vars);

        return $this->Parse($this->page_prefix . 'listare.html', $vars);
    }

    function ListareClienti_JSON() {
    	$responce = new StdClass();
        unset($_SESSION['client']);
        $cond = '1=1 ';
        $vars=[];
        //$vars=$_GET;
        $post=$_REQUEST;
        //print_R($vars);
        $page = intval($_REQUEST['page'] ?? 1);
		$limit = intval($_REQUEST['rows'] ?? 20);
		$sidx = trim($this->sanitize($_REQUEST['sidx'] ?? 1));
		$sord = trim($this->sanitize($_REQUEST['sord'] ?? 'asc'));

        //start generare conditie
        $searchOn = $this->Strip($post['_search']);
        if ($searchOn == 'true') {
            $searchstr = $this->Strip($post['filters']);
            $cond .= $this->constructWhere($searchstr);
        }else {
            $cond = "1=2";
            $_SESSION['conditie_clienti'] = '';
        }
        if ($cond == '1=1 ' && !empty($_SESSION['conditie_clienti']))
            $cond = $_SESSION['conditie_clienti'];
        $_SESSION['conditie_clienti'] = $cond;
        //end generare conditie

        $query="SELECT COUNT(c.cod_cl) as nr
            FROM {$this->tables['clienti']} c
            left join localitati lc on lc.cod_lc = c.cod_lc
            left join ag_vanzari ag on ag.id = c.ag_vanzari_id
            WHERE {$cond} AND c.sters = 0";
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

        $query="SELECT c.cod_cl, c.master, c.nume, lc.nume_lc as localitate, c.tarif, c.mod_plata, c.tip_plata,
                c.activ, c.cod_fiscal, c.nume_societate, c.email_factura, c.tarif_manual,
                c.tarif_individual, c.tip_facturare, c.obs_sc, c.obs_bl, c.ff_discount, ag.nume as ag_vanzari_nume
                FROM {$this->tables['clienti']} c
                left join localitati lc on lc.cod_lc = c.cod_lc
                left join ag_vanzari ag on ag.id = c.ag_vanzari_id
                WHERE {$cond}  AND c.sters = 0
                ORDER BY " . $sidx . " " . $sord . " LIMIT " . $start . " , " . $limit ;
        $sql = $this->db->QFetchRowArray($query);

        $responce->page = $page;
        $responce->total = $total_pages;
        $responce->records = $count;

        if (!empty($sql)) {
            $tip_plata = array(0 => 'Cash', 1 => 'Virament');
            $activ = array(0 => 'Inactiv', 1 => 'Activ');
            foreach ($sql as $key => $row) {

                if(empty($row['tarif'])) $row['tarif']=0;
                if(empty($row['mod_plata'])) $row['mod_plata']=0;
                if(empty($row['tip_plata'])) $row['tip_plata']=0;

                $edit='EditareClient('.$row['cod_cl'].');';
                $delete='StergereClient('.$row['cod_cl'].');';
                $actiuni = '<a href="#editare_client" title="Editeaza" class="ui-icon ui-icon-pencil actiuni" onclick="'.$edit.'"></a><a href="#stergere_client" title="Sterge" class="ui-icon ui-icon-trash actiuni" onclick="'.$delete.'"></a>';

                $responce->rows[$key]['id']=$row['cod_cl'];
                $responce->rows[$key]['cell'] = array(
                    $row['cod_cl'],
                    $row['master'],
                    $row['nume'],
                    $row['localitate'],
                    ExpeditieDto::CONTRACT[$row['tarif']],
                    ExpeditieDto::MOD_PLATA[$row['mod_plata']],
                    $tip_plata[$row['tip_plata']],
                    $activ[$row['activ']],
                    $row['cod_fiscal'],
                    $row['nume_societate'],
                    $row['email_factura'],
                    $row['tarif_manual'],
                    $row['tarif_individual'],
                    $row['tip_facturare'],
                    $row['obs_sc'],
                    $row['obs_bl'],
                    $row['ff_discount'],
                    $row['ag_vanzari_nume']
                );
            }
        }
        return json_encode($responce);
    }


    function DetaliiClient($id){
        $id = intval($id);
        if ($id == 0)
            return $this->Error('Invalid ID');

        $query="SELECT cl.*, lc.nume_lc as client_localitate_nume, ag.nume as ag_vanzari_nume, clm.nume as master_nume
            FROM {$this->tables['clienti']} cl
            LEFT JOIN localitati lc on lc.cod_lc = cl.cod_lc
            LEFT JOIN ag_vanzari ag on cl.ag_vanzari_id = ag.id
            LEFT JOIN {$this->tables['clienti']} clm on cl.master = clm.cod_cl
            WHERE cl.cod_cl={$id}";
        $sql = $this->db->QFetchArray($query);
        if(empty($sql)) return $this->Error('Invalid ID');

        $vars = [];
        $vars['MASTER'] = $this->sanitize($sql['master']);
        $vars['MASTER_NUME'] = Backend::sSanitizeCleanEdges($sql['master_nume'] ?? '');
		$vars['CONTRACT'] = $this->sanitize($sql['contract']);
        $vars['DATA_CONTRACT'] = $this->CreateDate($this->sanitize($sql['data_contract'] ?? date('Y-m-d')));
        $vars['NUME'] = $this->sanitize($sql['nume']);
        $vars['NUME_SOCIETATE'] = $this->sanitize($sql['nume_societate']);
        $vars['LOCALITATE_NUME'] = $this->sanitize($sql['client_localitate_nume']);
        $vars['LOCALITATE'] = $sql['cod_lc'];
        $vars['LOCALITATE_SEDIU_SOCIAL_NUME'] = $this->sanitize($sql['localitate_sediu_social']);
        $vars['LOCALITATE_SEDIU_SOCIAL'] = $sql['cod_lc_sediu_social'];
        $vars['ADRESA_SEDIU_SOCIAL'] = $sql['adresa_sediu_social'];
        $vars['EMAIL_FACTURA'] = $sql['email_factura'];

        $vars['KM_EXT'] = $sql['km_ext'];
        $vars['ADRESA'] = $this->sanitize($sql['adresa']);
        $vars['REG_COM'] = $this->sanitize($sql['reg_com']);
        $vars['COD_FISCAL'] = $this->sanitize($sql['cod_fiscal']);
        $vars['CONT'] = $this->sanitize($sql['cont_fa']);
        $vars['BANCA'] = $this->ComboBanci($sql['banca_fa_id'], 'w240','BANCA');
        $vars['CONT_RBS'] = $this->sanitize($sql['cont_rbs']);
        $vars['BANCA_RBS'] = $this->ComboBanci($sql['banca_rbs_id'], 'w190','BANCA_RBS');
        $vars['TARIF_'.$sql['tarif']] = 'selected';
        $vars['MOD_PLATA_'.$sql['mod_plata']] = 'selected';
        $vars['TIP_PLATA_'.$sql['tip_plata']] = 'selected';
        $vars['CONTACT'] = $this->sanitize($sql['contact']);
        $vars['COD_CL'] = $sql['cod_cl'];
        if(!empty($sql['ag_vanzari_id']))
        {
			$vars['AG_VANZARI'] = $sql['ag_vanzari_id'];
			$vars['AG_VANZARI_NUME'] = $this->sanitize($sql['ag_vanzari_nume']);
		}
        if(!empty($sql['termen_plata']))
            $vars['TERMEN_PLATA_'.$sql['termen_plata']] = ' selected ';
        if(!empty($sql['tip_tva']))
            $vars['TIP_TVA_'.$sql['tip_tva']] = ' selected ';
        if(!empty($sql['facturare_tip_tranzactie']))
            $vars['FACTURARE_TIP_TRANZACTIE_'.$sql['facturare_tip_tranzactie']] = ' selected ';
        if(!empty($sql['tva_incasare']))
            $vars['TVA_INCASARE_'.$sql['tva_incasare']] = ' selected ';
		if(!empty($sql['tip_facturare']))
			$vars['TIP_FACTURARE_'.$sql['tip_facturare']] = ' selected ';
		if(!empty($sql['cc']))
			$vars['CC'] = 'checked';
        if(!empty($sql['icc']))
			$vars['ICC'] = 'checked';
        if(!empty($sql['ret_rbs']))
			$vars['RET_RBS'] = 'checked';
        if(!empty($sql['facturare_fara_tva']))
            $vars['FACTURARE_FARA_TVA'] = 'checked';
        if(!empty($sql['tarif_individual']))
            $vars['TARIF_INDIVIDUAL'] = 'checked';
        if(!empty($sql['facturare_separata']))
            $vars['FACTURARE_SEPARATA'] = 'checked';
        if(!empty($sql['fara_factura']))
            $vars['FARA_FACTURA'] = 'checked';
        if(!empty($sql['borderou_pdf']))
            $vars['BORDEROU_PDF'] = 'checked';
        if(!empty($sql['ff_ultima_zi']))
            $vars['FF_ULTIMA_ZI'] = 'checked';
        if(!empty($sql['tarif_manual']))
            $vars['TARIF_MANUAL'] = 'checked';
        if(!empty($sql['valoare_maxima_factura']))
            $vars['VALOARE_MAXIMA_FACTURA'] =  $sql['valoare_maxima_factura'];
        if(!empty($sql['data_facturare']))
            $vars['DATA_FACTURARE'] =  $sql['data_facturare'];
        if(!empty($sql['ff_discount']))
            $vars['PROC_MAJ'] =  $sql['ff_discount'];

        if(!empty($sql['rbs_days'])) {
            $rbs_days = explode(",",$sql['rbs_days']);
            if(is_array($rbs_days) && count($rbs_days) > 0) {
                foreach($rbs_days as $rbs_day) {
                    $ck_day = "RBS_DAY_".strtoupper($rbs_day);
                    $vars[$ck_day] = 'checked';
                }
            }
        }

        if(!empty($sql['persoana_fizica'])) $vars['persoana_fizica'] = 'checked';
        if(!empty($sql['activ'])) $vars['ACTIV'] = 'checked';
        if(!empty($sql['obs_rp'])) $vars['OBS_RP'] = 'checked';
        if(!empty($sql['obs_oc'])) $vars['OBS_OC'] = 'checked';
        if(!empty($sql['obs_sc'])) $vars['OBS_SC'] = 'checked';
        if(!empty($sql['obs_bl'])) $vars['OBS_BL'] = 'checked';
        $_SESSION['client'] = $id;

		$query="SELECT * FROM {$this->tables['users']} WHERE expeditor_id={$id}";
        $sql = $this->db->QFetchArray($query);
        if(empty($sql)){
        	$vars['btn_edit'] = 'display: none;';
        }else{
        	$vars['btn_cont'] = 'display: none;';

			$vars['CONT_NUME'] = $this->sanitize($sql['nume']);
			$vars['CONT_EMAIL'] = $this->sanitize($sql['mail']);
			$vars['CONT_TELEFON'] = $this->sanitize($sql['telefon']);
			$vars['AFISARE_PRETURI'.$sql['preturi']] = 'checked="checked"';
            $vars['IMPORT_CSV'.$sql['importcsv']] = 'checked="checked"';
            $vars['RECANTARITE'.$sql['recantarite']] = 'checked="checked"';
            if($sql['selectie_puncte_de_lucru'] == 0) $sql['selectie_puncte_de_lucru'] = 2;
            $vars['SELECTIE_PUNCTE_DE_LUCRU'.$sql['selectie_puncte_de_lucru']] = 'checked="checked"';
            if($sql['show_master_clienti'] == 0) $sql['show_master_clienti'] = 2;
            $vars['SHOW_MASTER_CLIENTI'.$sql['show_master_clienti']] = 'checked="checked"';
			$vars['DE_LA'] = $sql['awb_de_la'];
			$vars['PANA_LA'] = $sql['awb_pana_la'];

			if(!empty($sql['activ']))
				$vars['btn_act'] = 'display: none;';
			else
				$vars['btn_dez'] = 'display: none;';
        }

        if(isset($_GET['active_tab']))
            $vars['ACTIVE_TAB'] = "$('a[href=\"#".htmlspecialchars($_GET['active_tab'])."\"]').click();";

        return $this->Parse($this->page_prefix . 'detalii.html', $vars);
    }

    function ContClient($id){
        $id = intval($id);
        $master = intval($this->sanitize($_REQUEST['master'] ?? 0));
        if (empty($id))
            return $this->Error('Invalid ID');

        $cond = "1=2";
        if($master > 0){
            $cond = "expeditor_id = {$master} AND id = {$id}";
        }

        $query="SELECT id, user, nume, telefon, preturi, importcsv, recantarite, selectie_puncte_de_lucru, show_master_clienti, activ FROM users WHERE {$cond}";
        $row = $this->db->QFetchArray($query);
        if (!empty($row)) {
            if($row['selectie_puncte_de_lucru'] == 0)
                $row['selectie_puncte_de_lucru'] = 2;
            if($row['show_master_clienti'] == 0)
                $row['show_master_clienti'] = 2;
        }
        return json_encode($row);

    }

    function ListareConturiClienti_JSON() {
        $responce = new StdClass();
        $master = (isset($_REQUEST['master']))?$_REQUEST['master']:0;

        if($master > 0){
            $cond = " expeditor_id=".$master;
        } else {
            $cond = " 1=2";
        }
        $query="SELECT COUNT(nume) as nr FROM users WHERE {$cond} ;";

        $result = $this->db->QFetchArray($query);
        $count = !empty($result['nr']) ? $result['nr'] : 0;

        $query="SELECT id, user, nume, activ FROM users WHERE {$cond}";
        $sql = $this->db->QFetchRowArray($query);

        if (!empty($sql)) {
            foreach ($sql as $key => $row) {
                $responce->rows[$key]['id']=$row['id'];
                $responce->rows[$key]['cell'] = array(
                    $row['nume'],
                    $row['user'],
                    $row['activ'],
                );
            }
        }
        $responce->records = $count;
        return json_encode($responce);
    }

    function ListareContacteClienti_JSON() {
    	$responce = new StdClass();

        if(!empty($_SESSION['client']))
            $cond = 'cod_cl='.$_SESSION['client'].' and activ=1';
        else $cond = '1=2 ';

        $query="SELECT COUNT(cod_cont_cl) as nr FROM {$this->tables['cont_cl']} WHERE {$cond}";
        $result = $this->db->QFetchArray($query);
        $count = !empty($result['nr']) ? $result['nr'] : 0;

        $query="SELECT cod_cont_cl, nume_pers, contact
                FROM {$this->tables['cont_cl']}
                WHERE {$cond}
                ORDER BY nume_pers";
        $sql = $this->db->QFetchRowArray($query);

        if (!empty($sql)) {
            foreach ($sql as $key => $row) {
                $responce->rows[$key]['id']=$row['cod_cont_cl'];
                $responce->rows[$key]['cell'] = array($row['nume_pers'], $row['contact']);
            }
        }
        $responce->records = $count;
        return json_encode($responce);
    }

    function DetaliiContacteClient($id){
        if (empty($id) && !is_numeric($id))
            return $this->Error('Invalid ID');

        $query="SELECT * FROM {$this->tables['cont_cl']} WHERE cod_cont_cl='{$id}'";
        $sql = $this->db->QFetchArray($query);
        if(empty($sql)) return $this->Error('Invalid ID2');

        $vars = [];
        $vars['NUME_PERS'] = $sql['nume_pers'];
        $vars['CONTACT_PERS'] = $sql['contact'];
        $vars['FUNCTIE'] = $sql['functie'];
        $vars['OBS'] = $sql['obs'];
        $vars['ROL'] = $sql['rol'];
        $vars['ROL2'] = $sql['rol2'];
        if(!empty($sql['activ'])) $vars['ACTIV1'] = 'checked';

        return $this->Parse($this->page_prefix . 'contacte.html', array_map('htmlspecialchars', $vars));
    }

    function valid_CNP ($input) // functie de validare
    {
        for ($i = 0; $i <=12; $i++) // imparte fiecare cifra a cnp-ului intr-un vector
        {
            $cnp[] = intval($input[$i]);
        }

        $suma = $cnp[0] * 2 + $cnp[1] * 7 + $cnp[2] * 9 + $cnp[3] * 1 + $cnp[4] * 4 + $cnp[5] * 6 + $cnp[6] * 3 + $cnp[7] * 5 + $cnp[8] * 8 + $cnp[9] * 2 + $cnp[10] * 7 + $cnp[11] * 9; //caluleaza o suma (face parte din algoritm)

        $rest = $suma % 11; // scoate restul din suma

        if (($rest < 10 && $rest == $cnp[12]) || ($rest == 10 && $cnp[12]==1)) // valideaza
            $validare = true;
        else
            $validare = false;
        return $validare;
    }

    function EditareClient() {
        if(!($this->user_profile==106 || $this->user_profile==24 || $this->user_profile==10)) {
			echo 'Eroare'; die;
		}

        $var = [];
        $post = $_POST;
        $post['COD_CL'] = intval($this->sanitize($_POST['COD_CL'] ?? 0));

        if(empty($post['COD_CL'])) return '0|||Nr. de cont client eronat!';

        $var['master'] = intval($_POST['MASTER'] ?? 0);

        if(isset($_POST['CONTRACT'])) $var['contract'] = $this->sanitize($post['CONTRACT']);
        if(isset($_POST['DATA_CONTRACT'])) $var['data_contract'] = $this->TransformDate($this->sanitize($post['DATA_CONTRACT']));
        if(isset($_POST['NUME'])) $var['nume'] = (!empty(Backend::sSanitizeCleanEdges($post['NUME'] ?? "")) ? strtoupper(Backend::sSanitizeCleanEdges($post['NUME'])):"");
        if(isset($_POST['COD_LC'])) $var['cod_lc'] = intval($post['COD_LC']);

        if(isset($_POST['LOCALITATE'])) $var['localitate'] = (!empty(Backend::sSanitizeCleanEdges($post['LOCALITATE'] ?? "")) ? strtoupper(Backend::sSanitizeCleanEdges($post['LOCALITATE'])):"");
        if(isset($_POST['ADRESA'])) $var['adresa'] = (!empty(Backend::sSanitizeCleanEdges($post['ADRESA'] ?? "")) ? strtoupper(Backend::sSanitizeCleanEdges($post['ADRESA'])):"");
        if(isset($_POST['KM_EXT'])) $var['km_ext'] = $post['KM_EXT'];
        if(isset($_POST['REG_COM'])) $var['reg_com'] = str_replace(" ","",$this->sanitize($post['REG_COM']));
        if(isset($_POST['COD_FISCAL'])) $var['cod_fiscal'] = str_replace(" ","",$this->sanitize($post['COD_FISCAL']));
        if(isset($_POST['CONT'])) $var['cont_fa'] = $this->sanitize($post['CONT']);
        if(isset($_POST['BANCA'])) $var['banca_fa_id'] = $this->sanitize($post['BANCA']);
        if(isset($_POST['CONT_RBS'])) $var['cont_rbs'] = $this->sanitize($post['CONT_RBS']);
        if(isset($_POST['BANCA_RBS'])) $var['banca_rbs_id'] = intval($post['BANCA_RBS']);
        if(isset($_POST['TARIF'])) $var['tarif'] = intval($post['TARIF']);

        if($var['tarif'] == 1 && empty($_POST['COD_FISCAL'])) return '0|||Eroare Cod Fiscal/CNP !';

        if(isset($_POST['MOD_PLATA'])) $var['mod_plata'] = intval($post['MOD_PLATA']);
        if(isset($_POST['TIP_PLATA'])) $var['tip_plata'] = intval($post['TIP_PLATA']);
        if(isset($_POST['CONTACT'])) $var['contact'] = (!empty(Backend::sSanitizeCleanEdges($post['CONTACT'] ?? "")) ? strtoupper(Backend::sSanitizeCleanEdges($post['CONTACT'])):"");
        if(isset($_POST['persoana_fizica'])) $var['persoana_fizica'] = intval($post['persoana_fizica']);
        if(isset($_POST['ACTIV'])) $var['activ'] = intval($post['ACTIV']);
        if(isset($_POST['CC'])) $var['cc'] = intval($post['CC']);
        if(isset($_POST['ICC'])) $var['icc'] = intval($post['ICC']);
        if(isset($_POST['RET_RBS'])) $var['ret_rbs'] = intval($post['RET_RBS']);
        if(isset($_POST['RBS_DAYS'])) $var['rbs_days'] = $this->sanitize($post['RBS_DAYS']);
        if(isset($_POST['AG_VANZARI'])) $var['ag_vanzari_id'] = intval($post['AG_VANZARI']);
        if(isset($_POST['TERMEN_PLATA'])) $var['termen_plata'] = intval($post['TERMEN_PLATA']);
        if(isset($_POST['TIP_TVA'])) $var['tip_tva'] = intval($post['TIP_TVA']);
        if(isset($_POST['FACTURARE_TIP_TRANZACTIE'])) $var['facturare_tip_tranzactie'] = intval($post['FACTURARE_TIP_TRANZACTIE']);
        if(isset($_POST['TVA_INCASARE'])) $var['tva_incasare'] = intval($post['TVA_INCASARE']);
        if(isset($_POST['TIP_FACTURARE'])) $var['tip_facturare'] = intval($post['TIP_FACTURARE']);
        if(isset($_POST['VALOARE_MAXIMA_FACTURA'])) $var['valoare_maxima_factura'] = intval($post['VALOARE_MAXIMA_FACTURA']);
        if(isset($_POST['DATA_FACTURARE'])) $var['data_facturare'] = intval($post['DATA_FACTURARE']);
        if(isset($_POST['PROC_MAJ'])) $var['ff_discount'] = intval($post['PROC_MAJ']);

        if(isset($_POST['NUME_SOCIETATE'])) $var['nume_societate'] = (!empty(Backend::sSanitizeCleanEdges($post['NUME_SOCIETATE'] ?? "")) ? strtoupper(Backend::sSanitizeCleanEdges($post['NUME_SOCIETATE'])):"");
        if(isset($_POST['COD_LC_SEDIU_SOCIAL'])) $var['cod_lc_sediu_social'] = intval($post['COD_LC_SEDIU_SOCIAL']);
        if(isset($_POST['LOCALITATE_SEDIU_SOCIAL'])) $var['localitate_sediu_social'] = (!empty(Backend::sSanitizeCleanEdges($post['LOCALITATE_SEDIU_SOCIAL'] ?? "")) ? strtoupper(Backend::sSanitizeCleanEdges($post['LOCALITATE_SEDIU_SOCIAL'])):"");
        if(isset($_POST['ADRESA_SEDIU_SOCIAL'])) $var['adresa_sediu_social'] = (!empty(Backend::sSanitizeCleanEdges($post['ADRESA_SEDIU_SOCIAL'] ?? "")) ? strtoupper(Backend::sSanitizeCleanEdges($post['ADRESA_SEDIU_SOCIAL'])):"");
        if(isset($_POST['EMAIL_FACTURA'])) $var['email_factura'] = $this->sanitize($post['EMAIL_FACTURA']);


        $var['facturare_separata'] = (isset($_POST['FACTURARE_SEPARATA']) && $_POST['FACTURARE_SEPARATA'] == 'true')?1:0;


        if(!empty($var['cod_fiscal'])) {
            $query = "SELECT * FROM clienti WHERE cod_fiscal='".$var['cod_fiscal']."' AND master <> ".intval($var['master'])." AND tarif = 1 AND activ = 1 AND facturare_separata = 0 AND cod_cl <> ".$post['COD_CL'];
            // error_log($query);
            $clienti = $this->db->QFetchRowArray($query);

            if(!empty($clienti) && $var['facturare_separata'] == 0  && $var['activ'] == 1) {
                $rez = "";
                foreach ($clienti as $cl)
                    $rez .= $cl['cod_cl'].":".$cl['nume']."<br>";

                return '0|||Cod Fiscal/CNP asociat unui alt client !<br>'.$rez;
            }
        }


        $var['borderou_pdf'] = (isset($_POST['BORDEROU_PDF']) && $_POST['BORDEROU_PDF'] == 'true')?1:0;
        $var['ff_ultima_zi'] = (isset($_POST['FF_ULTIMA_ZI']) && $_POST['FF_ULTIMA_ZI'] == 'true')?1:0;

        $var['tarif_manual'] = (isset($_POST['TARIF_MANUAL']) && $_POST['TARIF_MANUAL'] == 'true')?1:0;
        $var['tarif_individual'] = (isset($_POST['TARIF_INDIVIDUAL']) && $_POST['TARIF_INDIVIDUAL'] == 'true')?1:0;
        $var['facturare_fara_tva'] = (isset($_POST['FACTURARE_FARA_TVA']) && $_POST['FACTURARE_FARA_TVA'] == 'true')?1:0;
        $var['fara_factura'] = (isset($_POST['FARA_FACTURA']) && $_POST['FARA_FACTURA'] == 'true')?1:0;

        if(isset($_POST['OBS_RP'])) $var['obs_rp'] = $this->sanitize($post['OBS_RP']);
        if(isset($_POST['OBS_OC'])) $var['obs_oc'] = $this->sanitize($post['OBS_OC']);
        if(isset($_POST['OBS_SC'])) $var['obs_sc'] = $this->sanitize($post['OBS_SC']);
        if(isset($_POST['OBS_BL'])) $var['obs_bl'] = $this->sanitize($post['OBS_BL']);

        $var['operator'] = $this->user_id;

		if(empty($var['nume']) || empty($var['adresa']) || empty($var['cod_lc']) )
			return '0|||Trebuie completate campurile Nume, Localitate si Adresa';

        $query = "SELECT dist_km,nume_lc,cod_centru FROM {$this->tables['localitati']} WHERE cod_lc={$var['cod_lc']}";
        $loc = $this->db->QFetchArray($query);
        if(empty($loc)) return '0|||Localitate invalida!';

        $var['km_ext'] = $loc['dist_km'];
        $var['localitate'] = $loc['nume_lc'];
        $var['cod_centru'] = $loc['cod_centru'];

        if ($post['metoda'] == 'edit') {//editare
            if(empty($post['COD_CL'])){
                return '0|||Selectati va rog clientul!';
            }
			$q_u = "SELECT id FROM {$this->tables['users']} WHERE expeditor_id={$post['COD_CL']} limit 1";
        	$s_u = $this->db->QFetchArray($q_u);
        	if(!empty($s_u)){
        		$v_u =[];

				$v_u['expeditor'] = $var['nume'];
				$v_u['expeditor_localitate'] = $var['localitate'];
				$v_u['expeditor_localitate_id'] = $var['cod_lc'];
				$v_u['expeditor_adresa'] = $var['adresa'];
        		$this->db->QueryUpdate($this->tables['users'], $v_u, 'expeditor_id = ' . $post['COD_CL']);
			}
			// setare wme -1 pentru update in wme

            if($var['tarif'] == 1)
			    $var['wme'] = -1;
            $var['updated_at'] = date('Y-m-d H:i:s');
            $var['updated_by'] = $this->user_id;
            $this->db->QueryUpdate($this->tables['clienti'], $var, 'cod_cl=' . $post['COD_CL']);
            CdsGeocoder::geocode($this->db, $post['COD_CL'], true);

            return '1|||Modificarile au fost salvate!';
        } else if ($post['metoda'] == 'add') {//adaugare
            if(!empty($var['nume']) && !empty($var['cod_lc'])) {
                $query_is = "SELECT * FROM clienti WHERE nume = :nume AND cod_lc = {$var['cod_lc']} AND tarif = 1 AND activ = 1";
                // error_log($query);
                $client_is = $this->db->QFetchArray($query_is, ['nume' => $var['nume']]);
                if(!empty($client_is)) {
                    return "0|||Un client cu acelasi nume si din aceeasi localitate exista deja : <br>Nr. cont {$client_is['cod_cl']}!";
                }
            }
            $var['created_at'] = date('Y-m-d H:i:s');
            $var['created_by'] = $this->user_id;
            $id = $this->db->QueryInsert($this->tables['clienti'], $var);
            CdsGeocoder::geocode($this->db, $id);
            if($var['master'] == 0)
            	$this->db->QueryUpdate($this->tables['clienti'], array('master'=>$id), 'cod_cl=' . $id);
            return '1|||Clientul a fost adaugat!';
		} else if ($post['metoda'] == 'cont-add') {//adaugare
			if(empty($post['COD_CL'])){
                return '0|||Selectati va rog clientul!';
            }
			if( empty($post['CONT_EMAIL']) || empty($post['PAROLA']) )
			    return '0|||Trebuie completate toate campurile de la tabul cont!';


        	$query="SELECT * FROM {$this->tables['users']} WHERE mail=:mail OR user=:user";
        	$sql = $this->db->QFetchArray($query, ['mail'=>$post['CONT_EMAIL'], 'user'=>$post['CONT_EMAIL']]);
        	if(!empty($sql)) return '0|||Adresa de email este deja folosita!';

			$vi=[];
			$vi['user'] = $post['CONT_EMAIL'];
			$vi['mail'] = $post['CONT_EMAIL'];
			$vi['nume'] = $post['CONT_NUME'];
			$vi['hashParola'] = password_hash($post['PAROLA'], PASSWORD_DEFAULT);
			$vi['functie'] = 'Client';
			$vi['nivel_acces'] = 9;
			$vi['operator'] = $this->user_id;
			$vi['data_op'] = date('Y-m-d');
			$vi['telefon'] = $post['CONT_TELEFON'];
			$vi['centru'] = $var['cod_centru'];
			$vi['expeditor'] = $var['nume'];
			$vi['expeditor_id'] = $post['COD_CL'];
			$vi['expeditor_localitate'] = $var['localitate'];
			$vi['expeditor_localitate_id'] = $var['cod_lc'];
			$vi['expeditor_adresa'] = $var['adresa'];
			$vi['activ'] = 1;
			$vi['preturi'] = isset($post['AFISARE_PRETURI'])?$post['AFISARE_PRETURI']:2;
            $vi['importcsv'] = isset($post['IMPORT_CSV'])?$post['IMPORT_CSV']:2;
            $vi['recantarite'] = isset($post['RECANTARITE'])?$post['RECANTARITE']:0;
            if($var['master'] == $post['COD_CL']){
                $vi['selectie_puncte_de_lucru'] =  (isset($post['SELECTIE_PUNCTE_DE_LUCRU']) && intval($post['SELECTIE_PUNCTE_DE_LUCRU']) == 1) ? 1 : 2;
            }
            else {
                $vi['show_master_clienti'] =  (isset($post['SHOW_MASTER_CLIENTI']) && intval($post['SHOW_MASTER_CLIENTI']) == 1) ? 1 : 2;
            }
            $this->db->QueryInsert($this->tables['users'], $vi);

            return '1|||Clientul a fost adaugat!';

		} else if ($post['metoda'] == 'cont-edit') {//editare
			if(empty($post['COD_CL'])){
                return '0|||Selectati va rog clientul!';
            }
            if(empty($post['USER_ID'])){
                return '0|||Selectati va rog utilizatorul!';
            }
			if(empty($post['CONT_EMAIL']))
                return '0|||Trebuie completate toate campurile de la tabul cont!';

			$vi=[];
            $vi['user'] = $post['CONT_EMAIL'];
            $vi['activ'] = intval($post['CONT_ACTIV']);
			$vi['mail'] = $post['CONT_EMAIL'];
			$vi['nume'] = $post['CONT_NUME'];
			$vi['telefon'] = $post['CONT_TELEFON'];
            if(!empty($post['PAROLA']))
			    $vi['hashParola'] = password_hash($post['PAROLA'], PASSWORD_DEFAULT);
			$vi['operator'] = $this->user_id;
			$vi['data_op'] = date('Y-m-d');
			$vi['preturi'] = intval($post['AFISARE_PRETURI']);
            $vi['importcsv'] = intval($post['IMPORT_CSV']);
            $vi['recantarite'] = intval($post['RECANTARITE']);
            if($var['master'] == $post['COD_CL']){
                $vi['selectie_puncte_de_lucru'] =  (isset($post['SELECTIE_PUNCTE_DE_LUCRU']) && intval($post['SELECTIE_PUNCTE_DE_LUCRU']) == 1) ? 1 : 2;
            }
            else {
                $vi['show_master_clienti'] =  (isset($post['SHOW_MASTER_CLIENTI']) && intval($post['SHOW_MASTER_CLIENTI']) == 1) ? 1 : 2;
            }
            $vi['centru'] = $var['cod_centru'];
            $vi['expeditor'] = $var['nume'];
            $vi['expeditor_id'] = $post['COD_CL'];
            $vi['expeditor_localitate'] = $var['localitate'];
            $vi['expeditor_localitate_id'] = $var['cod_lc'];
            $vi['expeditor_adresa'] = $var['adresa'];

            $this->db->QueryUpdate($this->tables['users'], $vi,'expeditor_id='.$post['COD_CL']. ' AND id='.$post['USER_ID']);

            return '1|||Contul clientul a fost editat!';

		} else if ($post['metoda'] == 'cont-dez') {//dezactivare
			if(empty($post['COD_CL'])){
                return '0|||Selectati va rog clientul!';
            }
            $this->db->QueryUpdate($this->tables['users'], ['activ' => 0],'expeditor_id='.$post['COD_CL']. ' AND id='.$post['USER_ID']);
            return '1|||Contul clientul a fost dezactivat!';

        } else if ($post['metoda'] == 'cont-act') {//activare
        	if(empty($post['COD_CL'])){
                return '0|||Selectati va rog clientul!';
            }
            $this->db->QueryUpdate($this->tables['users'], ['activ' => 1],'expeditor_id='.$post['COD_CL']. ' AND id='.$post['USER_ID']);
            return '1|||Contul clientul a fost activat!';

		} else if ($post['metoda'] == 'numere-awb') {//activare
        	if(empty($post['COD_CL'])){
                return '0|||Selectati va rog clientul!';
            }
			if(!is_numeric($post['DE_LA']) || !is_numeric($post['PANA_LA']))
				return '0|||Numerele de AWB trebuie sa fie numerice!';


			$error = $this->PraguriAwburi($post['DE_LA'],$post['PANA_LA']);
        	if(!empty($error)) return '0|||'.$error;

			$vu=[];
			$vu['awb_de_la'] = $post['DE_LA'];
			$vu['awb_pana_la'] = $post['PANA_LA'];
            $this->db->QueryUpdate($this->tables['users'], $vu,'expeditor_id='.$post['COD_CL']);
            return '1|||Numerele de AWB au fost salvate!';

        }
    }

	function PraguriAwburi($de_la,$pana_la){
		$error=0;
		$query="SELECT awb_de_la,awb_pana_la FROM {$this->tables['users']} WHERE nivel_acces=9";
    	$sql_awb = $this->db->QFetchRowArray($query);
		foreach($sql_awb as $row){
			if($de_la>=$row['awb_de_la'] && $de_la<=$row['awb_pana_la'])
				$error ='Numarul minim este folosit!';
			if($pana_la>=$row['awb_pana_la'] && $de_la<=$row['awb_pana_la'])
				$error ='Numarul minim este folosit!';
		}
		return $error;
	}


    function EditareContacte(){
    	//print_R($_POST);die;
    	$post = $_POST;
    	if(empty($post['NUME_PERS'])) return '0|||Numele este obligatoriu!';
    	$this->PrepareForInsert($vars, 'NUME_PERS,CONTACT_PERS,ROL,ROL2,FUNCTIE');

    	if ($post['metoda'] == 'edit') {//editare
            if(empty($post['COD_CONT_CL'])){
                return '0|||Selectati va rog perosana!';
            }

	        $vu = [];
	        $vu['nume_pers'] = (!empty(Backend::sSanitizeCleanEdges($post['NUME_PERS'] ?? "")) ? strtoupper(Backend::sSanitizeCleanEdges($post['NUME_PERS'])):"");
	        $vu['cod_cl'] = intval($post['COD_CL']);
	        $vu['functie'] = $this->sanitize($post['FUNCTIE']);
	        $vu['contact'] = (!empty(Backend::sSanitizeCleanEdges($post['CONTACT_PERS'] ?? "")) ? strtoupper(Backend::sSanitizeCleanEdges($post['CONTACT_PERS'])):"");
	        $vu['rol'] = $this->sanitize($post['ROL']);
	        $vu['rol2'] = $this->sanitize($post['ROL2']);
	        $vu['obs'] = $this->sanitize($post['OBS']);
	        $vu['operator'] = $this->user_id;
	        $vu['activ'] = $post['ACTIV'];
            $this->db->QueryUpdate($this->tables['cont_cl'], $vu, 'cod_cont_cl=' . $post['COD_CONT_CL']);

            return '1|||Modificarile au fost salvate!';
         } else if ($post['metoda'] == 'add') {

         	$vi = [];
	        $vi['nume_pers'] = (!empty(Backend::sSanitizeCleanEdges($post['NUME_PERS'] ?? "")) ? strtoupper(Backend::sSanitizeCleanEdges($post['NUME_PERS'])):"");
	        $vi['cod_cl'] = intval($post['COD_CL']);
	        $vi['functie'] = $this->sanitize($post['FUNCTIE']);
	        $vi['contact'] = (!empty(Backend::sSanitizeCleanEdges($post['CONTACT_PERS'] ?? "")) ? strtoupper(Backend::sSanitizeCleanEdges($post['CONTACT_PERS'])):"");
	        $vi['rol'] = $this->sanitize($post['ROL']);
	        $vi['rol2'] = $this->sanitize($post['ROL2']);
	        $vi['obs'] = $this->sanitize($post['OBS']);
	        $vi['operator'] = $this->user_id;
	        $vi['activ'] = 1;
         	$this->db->QueryInsert($this->tables['cont_cl'], $vi);

       	 	return '1|||Contactul a fost adaugat!';
         }
    }

    function AdaugareClientPFJ() {

        $post = $_POST;
        $this->PrepareForInsert($post, 'PJ,NUME,CUI,COD_LOC,LOCALITATE,ADRESA,CONTACT,TELEFON');

        $vi = [];

        $vi['persoana_fizica'] = filter_var($post['PJ'] ?? 0, FILTER_VALIDATE_BOOLEAN) == false;
        $vi['nume'] = (!empty(Backend::sSanitizeCleanEdges($post['NUME'] ?? "")) ? strtoupper(Backend::sSanitizeCleanEdges($post['NUME'])):"");
        $vi['cod_fiscal'] = $this->sanitize($post['CUI'] ?? "");
        $vi['cod_lc'] = intval($post['COD_LOC']);
        $vi['localitate'] = (!empty(Backend::sSanitizeCleanEdges($post['LOCALITATE'] ?? "")) ? strtoupper(Backend::sSanitizeCleanEdges($post['LOCALITATE'])):"");
        $vi['adresa'] = (!empty(Backend::sSanitizeCleanEdges($post['ADRESA'] ?? "")) ? strtoupper(Backend::sSanitizeCleanEdges($post['ADRESA'])):"");
        $vi['telefon'] = (!empty(Backend::sSanitizeCleanEdges($post['TELEFON'] ?? "")) ? strtoupper(Backend::sSanitizeCleanEdges($post['TELEFON'])):"");
        $vi['operator'] = $this->user_id;
        $vi['activ'] = 1;
        $vi['tarif'] = 0;
        $vi['mod_plata'] = 0;
        $vi['tip_plata'] = 0;

        if(empty($post['NUME'])) return 0;
        if(empty($post['ADRESA']) || strlen($post['ADRESA']) < 4) return 0;

        //km localitate
        $query = "SELECT dist_km,nume_lc,cod_centru FROM {$this->tables['localitati']} WHERE cod_lc={$post['COD_LOC']}";
        $loc = $this->db->QFetchArray($query);
        if(empty($loc)) return 0;

        $vi['km_ext'] = $loc['dist_km'];
        $vi['localitate'] = $loc['nume_lc'];
        $vi['cod_centru'] = $loc['cod_centru'];

        $vi['created_at'] = date('Y-m-d H:i:s');
        $vi['created_by'] = $this->user_id;
        $id = $this->db->QueryInsert($this->tables['clienti'], $vi);
        CdsGeocoder::geocode($this->db, $id);
        return $id;
    }

	function DetaliiContract($id = 0){
        $id = intval($id);
        if (empty($id) && !is_numeric($id))
            return $this->Error('Invalid ID');

        $query="SELECT * FROM {$this->tables['clienti']} WHERE COD_CL={$id}";
        $sql = $this->db->QFetchArray($query);
        if(empty($sql)) return $this->Error('Invalid ID');

        $vars = [];
        $vars['NUME'] = $sql['nume'];
        $vars['NUME_SOCIETATE'] = $sql['nume_societate'];
        $vars['LOCALITATE_NUME'] = $sql['localitate'];
        $vars['LOCALITATE_SEDIU_SOCIAL_NUME'] = $sql['localitate_sediu_social'];
        $vars['LOCALITATE_SEDIU_SOCIAL'] = $sql['cod_lc_sediu_social'];
        $vars['ADRESA_SEDIU_SOCIAL'] = $sql['adresa_sediu_social'];
        $vars['EMAIL_FACTURA'] = $sql['email_factura'];

        $vars['KM_EXT'] = $sql['km_ext'];
        $vars['ADRESA'] = $sql['adresa'];
        $vars['REG_COM'] = $sql['reg_com'];
        $vars['COD_FISCAL'] = $sql['cod_fiscal'];
        $vars['CONT'] = $sql['cont_fa'];
        $vars['BANCA'] = $this->ComboBanci($sql['banca_fa_id'], 'w240', 'BANCA');
        $vars['CONT_RBS'] = $sql['cont_rbs'];
        $vars['BANCA_RBS'] = $this->ComboBanci($sql['banca_rbs_id'], 'w190', 'BANCA_RBS');
        $vars['TARIF_'.$sql['tarif']] = 'selected';
        $vars['MOD_PLATA_'.$sql['mod_plata']] = 'selected';
        $vars['TIP_PLATA_'.$sql['tip_plata']] = 'selected';
        $vars['CONTACT'] = $sql['contact'];
        $vars['COD_LC'] = $sql['cod_lc'];
		$vars['LOCALITATE'] = $sql['localitate'];
        if(!empty($sql['persoana_fizica'])) $vars['persoana_fizica'] = 'checked';
        if(!empty($sql['activ'])) $vars['activ'] = 'checked';
        if(!empty($sql['obs_rp'])) $vars['obs_rp'] = 'checked'; //rau platnic
        if(!empty($sql['obs_oc'])) $vars['obs_oc'] = 'checked'; //lucreaza cu alta firma
        if(!empty($sql['obs_sc'])) $vars['obs_sc'] = 'checked'; //stop credit
        if(!empty($sql['obs_bl'])) $vars['obs_bl'] = 'checked'; //blocat
        if(!empty($sql['facturare_separata'])) $vars['FACTURARE_SEPARATA'] = 'checked';
        if(!empty($sql['facturare_fara_tva'])) $vars['FACTURARE_FARA_TVA'] = 'checked';
        if(!empty($sql['fara_factura'])) $vars['FARA_FACTURA'] = 'checked';
        if(!empty($sql['tarif_manual'])) $vars['TARIF_MANUAL'] = 'checked';
        if(!empty($sql['tarif_individual'])) $vars['TARIF_INDIVIDUAL'] = 'checked';
        if(!empty($sql['borderou_pdf'])) $vars['BORDEROU_PDF'] = 'checked';
        if(!empty($sql['ff_ultima_zi'])) $vars['FF_ULTIMA_ZI'] = 'checked';

        $_SESSION['client'] = $id;
		$vars['observatii'] = 'test';
		$vars['contract'] = 'test1';

        return $this->Parse($this->page_prefix . 'contract.html', array_map('htmlspecialchars', $vars));
    }

	function TarifLista($client_id = 0) {

        $this->vars['title_page'] = 'Tarif de lista';
		if(!empty($_SESSION['mesaj_eroare']))
        	$this->vars['title_info'] = $_SESSION['mesaj_eroare'];
        unset($_SESSION['mesaj_eroare']);
        $vars = [];
		//tarig de lista
		$query="SELECT * FROM {$this->tables['tarife']} WHERE id_cl = {$client_id} limit 1";
        $sql = $this->db->QFetchArray($query);
        if(empty($sql)){
            $vi = [];
			$query = "SELECT * FROM {$this->tables['tarife']} WHERE id_cl = 0";
        	$vi = $this->db->QFetchArray($query);
			$vi['id_cl'] = $client_id;
        	$vi['operator'] = $this->user_id;
			unset($vi['id']);
			//print_r($vi);die;
	    	$id=$this->db->QueryInsert($this->tables['tarife'], $vi);//creez contract pentru acest client pentru a putea edita ulterior

			$this->db->QueryUpdate($this->tables['clienti'], ['tarif' => 1], 'cod_cl='.$client_id);

            $vi = [];
			$query="SELECT * FROM {$this->tables['tarife_det']} WHERE id = 1";
    		$vi = $this->db->QFetchArray($query);
			$vi['id_tarife'] = $id;
			$vi['operator'] = $this->user_id;
			unset($vi['id']);
			$this->db->QueryInsert($this->tables['tarife_det'], $vi);

            $vi = [];
			$query="SELECT * FROM {$this->tables['tarife_det']} WHERE id = 2";
    		$vi = $this->db->QFetchArray($query);
			$vi['id_tarife'] = $id;
			$vi['operator'] = $this->user_id;
    		unset($vi['id']);
			$this->db->QueryInsert($this->tables['tarife_det'], $vi);

        	$query="SELECT * FROM {$this->tables['tarife']} WHERE id_cl = {$client_id}";
        	$sql = $this->db->QFetchArray($query);
        }

		$vars['MONEDA'] = $sql['moneda'];
		$vars['MONEDA_'.$sql['moneda']] = 'selected="selected"';
        $vars['TARIF_PREL_TIP'] = $sql['tarif_prel_tip'];
		$vars['TARIF_PREL_TIP_'.$sql['tarif_prel_tip']] = 'selected="selected"';
		$vars['TARIF_LIVR_TIP'] = $sql['tarif_livr_tip'];
		$vars['TARIF_LIVR_TIP_'.$sql['tarif_livr_tip']] = 'selected="selected"';

        $vars['PLATA_RETUR'] = $sql['plata_retur'];
		if(!empty($sql['plata_retur']))
			$vars['PLATA_RETUR_VALUE'] = 'checked="checked';
		$vars['TAXA_RAMBURS'] = $sql['taxa_ramburs'];
		if(!empty($sql['taxa_ramburs']))
			$vars['TAXA_RAMBURS_VALUE'] = 'checked="checked';

		$vars['TAXA_EXPEDIERE'] = $sql['taxa_expediere'];
		if(!empty($sql['taxa_expediere']))
			$vars['TAXA_EXPEDIERE_VALUE'] = 'checked="checked';

		$vars['TAXA_DESTINATIE'] = $sql['taxa_destinatie'];
		if(!empty($sql['taxa_destinatie']))
			$vars['TAXA_DESTINATIE_VALUE'] = 'checked="checked';

		$vars['TARIF_RETURNARE'] = $sql['tarif_returnare'];
		if(!empty($sql['tarif_returnare']))
			$vars['TARIF_RETURNARE_VALUE'] = 'checked="checked';
		$vars['RET_AMB'] = $sql['ret_amb'];
		if(!empty($sql['ret_amb']))
			$vars['RET_AMB_VALUE'] = 'checked="checked';
        $vars['TARIF_PREL'] = $sql['tarif_prel'];
		$vars['TARIF_LIVR'] = $sql['tarif_livr'];
		$vars['KM_LIMIT_PREL'] = $sql['km_limit_prel'];
		$vars['KM_LIMIT_LIVR'] = $sql['km_limit_livr'];
        $vars['OBS'] = $sql['obs'];
        $vars['KG_RET_AMB'] = $sql['kg_ret_amb'];
        $vars['TARIF_SMS'] = $sql['tarif_sms'];
        $vars['TARIF_OPEN'] = $sql['tarif_open'];
        $vars['TARIF_PROC_INDEXC'] = $sql['tarif_proc_indexc'];

		$query_national="SELECT * FROM {$this->tables['tarife_det']} WHERE id_tarife=".$sql['id']." AND tip_tarif=1";
        $national = $this->db->QFetchArray($query_national);
        if(!empty($national)) {
            $vars['NATIONAL_PLIC'] = $national['plic'];
            $vars['NATIONAL_RETUR_NT'] = $national['retur_nt'];
            $vars['NATIONAL_LIV_SAMBATA'] = $national['liv_sambata'];
            $vars['NATIONAL_LIV_SEDIU'] = $national['liv_sediu'];
            $vars['NATIONAL_COLET'] = $national['colet'];
            $vars['NATIONAL_PALET'] = $national['palet'];
            $vars['NATIONAL_RETUR_DOC'] = $national['retur_doc'];
            $vars['NATIONAL_RETURNARE'] = $national['returnare'];
            $vars['NATIONAL_RET_AMB'] = $national['tspecial'];
            $vars['ID_TARIFE_DET_NATIONAL'] = $national['id'];
            $vars['NATIONAL_PROC_ASIG'] = $national['proc_asig'];
            $vars['NATIONAL_ASIG_RAMB'] = $national['asig_ramb'];
            $vars['NATIONAL_TAXA_RAMB'] = $national['taxa_ramb'];

            $vars['NATIONAL_ASIG_EXPEDIERE'] = $national['asig_expediere'];
            if(!empty($national['asig_expediere']))
                $vars['NATIONAL_ASIG_EXPEDIERE_VALUE'] = 'checked="checked';

            $vars['NATIONAL_ASIG_RAMBURS'] = $national['asig_ramburs'];
            if(!empty($national['asig_ramburs']))
                $vars['NATIONAL_ASIG_RAMBURS_VALUE'] = 'checked="checked';
        }

		$query_loco="SELECT * FROM {$this->tables['tarife_det']} WHERE id_tarife=".$sql['id']." AND tip_tarif=0";
        $loco = $this->db->QFetchArray($query_loco);
        if(!empty($loco)) {
            $vars['LOCO_PLIC'] = $loco['plic'];
            $vars['LOCO_RETUR_NT'] = $loco['retur_nt'];
            $vars['LOCO_LIV_SAMBATA'] = $loco['liv_sambata'];
            $vars['LOCO_LIV_SEDIU'] = $loco['liv_sediu'];
            $vars['LOCO_COLET'] = $loco['colet'];
            $vars['LOCO_PALET'] = $loco['palet'];
            $vars['LOCO_RETUR_DOC'] = $loco['retur_doc'];
            $vars['LOCO_RETURNARE'] = $loco['returnare'];
            $vars['LOCO_RET_AMB'] = $loco['tspecial'];
            $vars['ID_TARIFE_DET_LOCO'] = $loco['id'];

            $vars['LOCO_PROC_ASIG'] = $loco['proc_asig'];
            $vars['LOCO_ASIG_RAMB'] = $loco['asig_ramb'];
            $vars['LOCO_TAXA_RAMB'] = $loco['taxa_ramb'];
            $vars['LOCO_ASIG_EXPEDIERE'] = $loco['asig_expediere'];
            if(!empty($loco['asig_expediere']))
                $vars['LOCO_ASIG_EXPEDIERE_VALUE'] = 'checked="checked';

            $vars['LOCO_ASIG_RAMBURS'] = $loco['asig_ramburs'];
            if(!empty($loco['asig_ramburs']))
                $vars['LOCO_ASIG_RAMBURS_VALUE'] = 'checked="checked';
        }
		return $this->Parse($this->page_prefix . 'contract.html', array_map('htmlspecialchars', $vars));

    }

	function EditareTarifLista(){
    	if(!($this->user_profile==106 || $this->user_profile==24 || $this->user_profile==10)) {
			echo 'Eroare'; die;
		}

    	$post = $_POST;
        
		$this->PrepareForInsert($post, 'OBS');
        $vu = [];
        if(!empty($post['MONEDA']))
        	$vu['moneda'] = $post['MONEDA'];
        if(isset($post['TARIF_PREL_TIP']))
        	$vu['tarif_prel_tip'] = intval($post['TARIF_PREL_TIP']);
        if(isset($post['TARIF_LIVR_TIP']))
        	$vu['tarif_livr_tip'] = intval($post['TARIF_LIVR_TIP']);
        if(isset($post['TARIF_PREL']))
        	$vu['tarif_prel'] = doubleval($this->sanitize($post['TARIF_PREL']));
        if(isset($post['TARIF_LIVR']))
			$vu['tarif_livr'] = doubleval($this->sanitize($post['TARIF_LIVR']));
		if(isset($post['KM_LIMIT_PREL']))
			$vu['km_limit_prel'] = intval($post['KM_LIMIT_PREL']);
		if(isset($post['KM_LIMIT_LIVR']))
			$vu['km_limit_livr'] = intval($post['KM_LIMIT_LIVR']);

        if(isset($post['KG_RET_AMB']))
			$vu['kg_ret_amb'] = ceil(round(floatval($post['KG_RET_AMB']), 3));
        if(isset($post['TARIF_SMS']))
			$vu['tarif_sms'] = round(floatval($post['TARIF_SMS']), 2);
        if(isset($post['TARIF_OPEN']))
			$vu['tarif_open'] = round(floatval($post['TARIF_OPEN']), 2);
        if(isset($post['TARIF_PROC_INDEXC']))
			$vu['tarif_proc_indexc'] = intval($post['TARIF_PROC_INDEXC']);

		if(!empty($post['TAXA_RAMBURS'])) $post['TAXA_RAMBURS']=1;
		else $post['TAXA_RAMBURS']=0;
		if(!empty($post['TAXA_EXPEDIERE'])) $post['TAXA_EXPEDIERE']=1;
		else $post['TAXA_EXPEDIERE']=0;
		if(!empty($post['TAXA_DESTINATIE'])) $post['TAXA_DESTINATIE']=1;
		else $post['TAXA_DESTINATIE']=0;
		if(!empty($post['TARIF_RETURNARE'])) $post['TARIF_RETURNARE']=1;
		else $post['TARIF_RETURNARE']=0;
		if(!empty($post['PLATA_RETUR'])) $post['PLATA_RETUR']=1;
		else $post['PLATA_RETUR']=0;
		if(!empty($post['RET_AMB'])) $post['RET_AMB']=1;
		else $post['RET_AMB']=0;

		$vu['taxa_ramburs'] = $post['TAXA_RAMBURS'];
		$vu['taxa_expediere'] = $post['TAXA_EXPEDIERE'];
		$vu['taxa_destinatie'] = $post['TAXA_DESTINATIE'];
		$vu['tarif_returnare'] = $post['TARIF_RETURNARE'];
		$vu['plata_retur'] = $post['PLATA_RETUR'];
		$vu['ret_amb'] = $post['RET_AMB'];
        $vu['obs'] = $post['OBS'] ?? '';
        $vu['operator'] = $this->user_id;

		if(empty($post['COD_CLIENT'])) $post['COD_CLIENT'] = 0;
		$this->db->QueryUpdate($this->tables['tarife'], $vu, 'id_cl='.$post['COD_CLIENT']);

        return '1|||Modificarile au fost salvate!';

    }


	function EditareTarifLista_2(){
    	if(!($this->user_profile==106 || $this->user_profile==24 || $this->user_profile==10)) {
			echo 'Eroare'; die;
		}
    	$post=$_POST;
        $vu = [];
        $vu['plic'] = $post['PLIC'];
        $vu['retur_nt'] = $post['RETUR_NT'];
        $vu['colet'] = $post['COLET'];
		$vu['palet'] = $post['PALET'];
		$vu['retur_doc'] = $post['RETUR_DOC'];
		$vu['returnare'] = $post['RETURNARE'];
		if(!empty($post['ASIG_EXPEDIERE'])) $post['ASIG_EXPEDIERE']=1;
		else $post['ASIG_EXPEDIERE']=0;
		if(!empty($post['ASIG_RAMBURS'])) $post['ASIG_RAMBURS']=1;
		else $post['ASIG_RAMBURS']=0;
		$vu['asig_expediere'] = $post['ASIG_EXPEDIERE'];
		$vu['asig_ramburs'] = $post['ASIG_RAMBURS'];
		$vu['liv_sediu'] = $post['LIV_SEDIU'];
		$vu['liv_sambata'] = $post['LIV_SAMBATA'];

        $vu['proc_asig'] = $post['PROC_ASIG'];
		$vu['asig_ramb'] = $post['ASIG_RAMB'];
		$vu['taxa_ramb'] = $post['TAXA_RAMB'];
        $vu['operator'] = $this->user_id;
        $this->db->QueryUpdate($this->tables['tarife_det'], $vu, 'id='.$post['ID_TARIFE']);

        return '1|||Modificarile au fost salvate!';

    }

	function ListareGreutateJson() {
		$responce = new StdClass();
        $cond = "1=1 ";

        $cod_cl = intval($_GET['cod_client'] ?? 0);
		$tip = intval($_GET['tip'] ?? 1);

		$palet=0;
		if($tip > 1){
			$tip = $tip - 2;
			$palet = 1;
		}

		$query="SELECT b.id
            FROM {$this->tables['tarife']} a
            LEFT JOIN {$this->tables['tarife_det']} b ON a.id = b.id_tarife
            WHERE a.id_cl = {$cod_cl} AND b.tip_tarif = {$tip}";
        $tarif = $this->db->QFetchArray($query);
		if(empty($tarif)) return 'Invalid ID';

		$cond = "id_tarife_det = {$tarif['id']} AND tip = {$palet}";
        $query="SELECT COUNT(id) as nr FROM {$this->tables['tarife_g']} WHERE {$cond}";
        $result = $this->db->QFetchArray($query);
        $count = !empty($result['nr']) ? $result['nr'] : 0;

        $query="SELECT *
                FROM {$this->tables['tarife_g']}
                WHERE {$cond}
                ORDER BY g_init,g_fin,km_init,km_fin";
        $sql = $this->db->QFetchRowArray($query);
		//print_R($query);

        if (!empty($sql)) {
            foreach ($sql as $key => $row) {

                $responce->rows[$key]['id']=$row['id'];
                if($palet==1)
                	$responce->rows[$key]['cell'] = array(
                                                        round($row['g_init'],2),
                                                        round($row['g_fin'],2),
                                                        $row['km_init'],
                                                        $row['km_fin'],
                                                        round($row['val_init'],2),
                                                        round($row['inc_val'],2),
                                                        round($row['inc_greut'],2)
                                                	);
                else
                	$responce->rows[$key]['cell'] = array(
                                                        round($row['g_init'],2),
                                                        round($row['g_fin'],2),
                                                        round($row['val_init'],2),
                                                        round($row['inc_val'],2),
                                                        round($row['inc_greut'],2)
                                                	);
            }
        }
        $responce->page = 1;
        $responce->total = 1;
        $responce->records = count($sql ?? 0);
        return json_encode($responce);
    }


	function DetaliiRowGreutate($id){
		if (empty($id) && !is_numeric($id))
            return '0|||Invalid ID';

        $query="SELECT * FROM {$this->tables['tarife_g']} WHERE id = {$id}";
        $sql = $this->db->QFetchArray($query);
        if(empty($sql)) return '0|||Invalid ID';

        $vars = [];
        $vars['G_INIT'] = round($sql['g_init'],2);
		$vars['G_FIN'] = round($sql['g_fin'],2);
		$vars['KM_INIT'] = $sql['km_init'];
        $vars['KM_FIN'] = $sql['km_fin'];
        $vars['VAL_INIT'] = round($sql['val_init'],2);
		$vars['INC_VAL'] = round($sql['inc_val'],2);
		$vars['INC_GREUT'] = round($sql['inc_greut'],2);
		$vars['ID_TARIFE_DET'] = $sql['id_tarife_det'];

		return '1|||'.$vars['G_INIT'].'|||'.$vars['G_FIN'].'|||'.$vars['INC_VAL'].'|||'.$vars['INC_GREUT'].'|||'.$vars['ID_TARIFE_DET'].'|||'.$vars['KM_INIT'].'|||'.$vars['KM_FIN'].'|||'.$vars['VAL_INIT'];
	}

	function EditareRowGreutate(){
		//return var_dump($_POST);
    	$post = $_POST;
		if($post['TYPE'] == 1){
			$vi = [];
			$vi['id_tarife_det'] = $post['ID_TARIFE_DET'];
	        $vi['g_init'] = $post['G_INIT'];
	        $vi['g_fin'] = $post['G_FIN'];
	        if($post['TIP_TARIF'] == 2 || $post['TIP_TARIF'] == 3)
	        {
	        	if(!empty($post['KM_INIT'])) $vi['km_init'] = $post['KM_INIT'];
	        	if(!empty($post['KM_FIN'])) $vi['km_fin'] = $post['KM_FIN'];
            }
            $vi['val_init'] = empty($post['VAL_INIT']) ? 0.00 : $post['VAL_INIT'];
	        $vi['inc_val'] = $post['INC_VAL'];
			$vi['inc_greut'] = $post['INC_GREUT'];
	        $vi['operator'] = $this->user_id;

			$vi['tip'] = 0;
			if($post['TIP_TARIF'] > 1) $vi['tip'] = 1;

	        $this->db->QueryInsert($this->tables['tarife_g'], $vi);
		}
		if($post['TYPE'] == 2 && !empty($post['ID'])){
			$vu = [];
	        $vu['g_init'] = $post['G_INIT'];
	        $vu['g_fin'] = $post['G_FIN'];
	        if($post['TIP_TARIF'] == 2 || $post['TIP_TARIF'] == 3)
	        {
	        	$vu['km_init'] = $post['KM_INIT'];
	        	$vu['km_fin'] = $post['KM_FIN'];
            }
            $vu['val_init'] = empty($post['VAL_INIT']) ? 0.00 : $post['VAL_INIT'];
	        $vu['inc_val'] = $post['INC_VAL'];
			$vu['inc_greut'] = $post['INC_GREUT'];
	        $vu['operator'] = $this->user_id;
	        $this->db->QueryUpdate($this->tables['tarife_g'], $vu, 'ID='.$post['ID']);
		}
        if($post['TYPE'] == 3 && !empty($post['ID'])){
			$this->db->Query("DELETE FROM {$this->tables['tarife_g']} WHERE ID='{$post['ID']}';");
		}

        return '1|||Modificarile au fost salvate!';
	}

	function TipContract($client_id){
		if (empty($client_id) && !is_numeric($client_id))
            return '0|||Invalid ID';

        $query="SELECT tarif, mod_plata, km_ext FROM {$this->tables['clienti']} WHERE cod_cl={$client_id}";
        $sql = $this->db->QFetchArray($query);

		return json_encode($sql);
	}

	function SelectKmSuplimentari($client_id){
		if (empty($client_id) && !is_numeric($client_id))
            return '0|||Invalid ID';

        $query="SELECT km_ext FROM {$this->tables['clienti']} WHERE cod_cl = {$client_id}";
        $sql = $this->db->QFetchArray($query);
		return '1|||'.$sql['km_ext'];
	}

   function AlocareClienti() {

		if($this->user_profile!=10){
			echo 'Eroare';die;
		}
        return $this->Parse($this->page_prefix . 'alocare.html', array('title_page'=>'Alocare Clienti'));
    }

    function AlocareClienti_JSON() {
        if($this->user_profile!=10){
			echo 'Eroare';die;
		}
    	$responce = new StdClass();
        unset($_SESSION['client']);
        $cond = '1=1 ';
        $vars=[];
        //$vars=$_GET;
        $post=$_REQUEST;
        //print_R($vars);
        $page = intval($_REQUEST['page'] ?? 1);
		$limit = intval($_REQUEST['rows'] ?? 20);
		$sidx = trim($this->sanitize($_REQUEST['sidx'] ?? 1));
		$sord = trim($this->sanitize($_REQUEST['sord'] ?? 'asc'));

        //start generare conditie
        $searchOn = $this->Strip($post['_search']);
        if ($searchOn == 'true') {
            $searchstr = $this->Strip($post['filters']);
            $cond .= $this->constructWhere($searchstr);
        }else {
            $_SESSION['conditie_clienti'] = '';
        }
        if ($cond == '1=1 ' && !empty($_SESSION['conditie_clienti']))
            $cond = $_SESSION['conditie_clienti'];
        $_SESSION['conditie_clienti'] = $cond;
        //end generare conditie

        $query="SELECT COUNT(cod_cl) as nr FROM {$this->tables['clienti']} WHERE {$cond}";
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

        $query="SELECT cod_cl,nume,localitate,tarif,mod_plata,activ
                FROM {$this->tables['clienti']}
                WHERE {$cond}
                ORDER BY " . $sidx . " " . $sord . " LIMIT " . $start . " , " . $limit;
        $sql = $this->db->QFetchRowArray($query);

        $responce->page = $page;
        $responce->total = $total_pages;
        $responce->records = $count;

        if (!empty($sql)) {
            foreach ($sql as $key => $row) {

                $edit='EditareClient('.$row['cod_cl'].');';
                $delete='StergereClient('.$row['cod_cl'].');';
                $actiuni = '<a href="#editare_client" title="Editeaza" class="ui-icon ui-icon-pencil actiuni" onclick="'.$edit.'"></a><a href="#stergere_client" title="Sterge" class="ui-icon ui-icon-trash actiuni" onclick="'.$delete.'"></a>';

                $activ = array(0 => 'Inactiv', 1 => 'Activ');

                $responce->rows[$key]['id']=$row['cod_cl'];
                $responce->rows[$key]['cell'] = array(
                                                        $row['nume'],
                                                        $row['localitate'],
                                                        ExpeditieDto::CONTRACT[$row['tarif']],
                                                        ExpeditieDto::MOD_PLATA[$row['mod_plata']],
                                                        $activ[$row['activ']]
                                                    );
            }
        }
        return json_encode($responce);
    }



    function AlocareClientiProces() {

		if($this->user_profile!=10){
			echo 'Eroare';die;
		}
		$clienti = $_POST['clienti'];
		$client_id = $_POST['client'];
		$sterge = $_POST['sterge'];

		$query="SELECT a.cod_cl,a.cod_lc,a.cod_centru,a.nume,a.localitate,b.nume as centru
                FROM {$this->tables['clienti']} as a
				LEFT JOIN {$this->tables['centre']} as b ON a.cod_centru=b.id
                WHERE a.cod_cl={$client_id}";
        $sql = $this->db->QFetchArray($query);
		if(empty($sql)) return 'Nu exista clientul!';

		$clienti = explode(',', $clienti);

		foreach ($clienti as $value) {

			$this->db->QueryUpdate($this->tables['exp_prelucrate'], ['expeditor_id' => $sql['cod_cl']], 'expeditor_id='.$value);
			$this->db->QueryUpdate($this->tables['exp_prelucrate'], ['destinatar_id' => $sql['cod_cl']], 'destinatar_id='.$value);

			$this->db->QueryUpdate($this->tables['exp_prelucrate'], ['platitor_id' => $sql['cod_cl']], 'platitor_id='.$value);

			if(!empty($sterge)){
				$this->db->QueryUpdate($this->tables['clienti'], ['sters' => 1, 'activ' => 0], 'cod_cl='.$value);
			}
			$this->db->QueryUpdate($this->tables['client_destinatari'], ['cod_cl' => $sql['cod_cl']], 'cod_cl='.$value);

			$this->db->QueryUpdate($this->tables['cont_cl'], ['cod_cl' => $sql['cod_cl']], 'cod_cl='.$value);
			$var=[];
	        $var['operator'] = $this->user_id;
	        $var['data'] = date("Y-m-d");
	        $var['client_vechi'] = $value;
			$var['client_nou'] = $sql['cod_cl'];
	        $this->db->QueryInsert($this->tables['alocari_clienti'], $var);
		}
    }

    function ExportDestinatari($client_id){
            $client_id=intval($client_id);
            if(empty($client_id)) return '0|||Nr. de cont client eronat!';

            $data = date('d/m/Y');
            $societate = 'Dragon Star Curier';
            $document = 'Lista Expeditii';

            $spreadsheet = new Spreadsheet();
            $spreadsheet->getProperties()->setCreator($societate)
                ->setLastModifiedBy($societate)
                ->setTitle($document)
                ->setSubject($document)
                ->setDescription($document)
                ->setKeywords($document)
                ->setCategory($document);
            $spreadsheet->getDefaultStyle()->getFont()->setName('Arial');
            $spreadsheet->getDefaultStyle()->getFont()->setSize(11);

            $worksheet = $spreadsheet->getActiveSheet();

            $worksheet->setCellValue('A1','nume');
            $worksheet->setCellValue('B1','contact');
            $worksheet->setCellValue('C1','adresa');
            $worksheet->setCellValue('D1','localitate');
            $worksheet->setCellValue('E1','judet');
            $worksheet->setCellValue('F1','telefon');

            ini_set('memory_limit', '1228M');
            set_time_limit(0);

            $query="SELECT a.id,a.nume,id_loc,b.nume_lc as nume_lc,a.adresa,a.contact as contact,a.telefon as telefon, jud.nume_jd as nume_jd
                FROM {$this->tables['client_destinatari']} a
				LEFT JOIN {$this->tables['localitati']} b ON a.id_loc=b.cod_lc
				LEFT JOIN {$this->tables['judete']} jud ON jud.cod_jd = b.cod_jd
                WHERE a.id_exp={$client_id} AND a.activ=1";

            $sql = $this->db->QFetchRowArray($query);

            //compun raspunsul
            if (!empty($sql)) {
                $rand = 2;
                foreach ($sql as $key => $row) {;
                    $worksheet->setCellValueExplicit('A'.($rand+$key),$row['nume'], \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
                    $worksheet->setCellValueExplicit('B'.($rand+$key),$row['contact'], \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
                    $worksheet->setCellValueExplicit('C'.($rand+$key),$row['adresa'], \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
                    $worksheet->setCellValue('D'.($rand+$key),$row['nume_lc']);
                    $worksheet->setCellValue('E'.($rand+$key),$row['nume_jd']);
                    $worksheet->setCellValueExplicit('F'.($rand+$key),$row['telefon'], \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
                }
            }

            $data = date('d_m_Y');
            $filename = 'destinatari_'.$data.'.xlsx';

            $this->download_send_headers_xls($filename);
            $writer = new Xlsx($spreadsheet);
            $writer->save('php://output');
            die;
    }

    function ComboBanci($sel, $extra='', $name='BANCA') {
	    $result = '<option value="0" selected ></option>';
	    $sql = $this->db->QFetchRowArray("SELECT id, label, nume FROM banci order by label");
	    if (!empty($sql))
	        foreach ($sql as $val) {
	            if ($sel == $val['id'])
	                $result .= '<option value="' . $val['id'] . '" selected>' . $val['label'] . ' (' . $val['nume'] .')' . '</option>';
	            else
	                $result .= '<option value="' . $val['id'] . '">'  . $val['label'] . ' (' . $val['nume'] .')' . '</option>';
	        }
	    return '<select class="field_combobox ' .$extra. '" name="' . $name . '" id="' . $name . '">' . $result . '</select>';
	}

    //generate a function that return a random password with 12 characters, 3 uppercase letter, 1 lowercase letter, 3 number and 1 special character
    function generatePassword($length = 12) {
        $uppercase = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $lowercase = 'abcdefghijklmnopqrstuvwxyz';
        $numbers = '0123456789';
        $specialChars = '!@#$%^&*()_+-=[]{}|;:,.<>?';
        $password = '';

        // Add 3 uppercase letters
        for ($i = 0; $i < 3; $i++) {
            $password .= $uppercase[rand(0, strlen($uppercase) - 1)];
        }

        // Add 1 lowercase letter
        $password .= $lowercase[rand(0, strlen($lowercase) - 1)];

        // Add 3 numbers
        for ($i = 0; $i < 3; $i++) {
            $password .= $numbers[rand(0, strlen($numbers) - 1)];
        }

        // Add 1 special character
        $password .= $specialChars[rand(0, strlen($specialChars) - 1)];

        // Fill the rest of the password with random characters
        for ($i = strlen($password); $i < $length; $i++) {
            $password .= str_shuffle($uppercase . $lowercase . $numbers . $specialChars)[rand(0, strlen($uppercase . $lowercase . $numbers . $specialChars) - 1)];
        }

        return str_shuffle($password);
    }
}
