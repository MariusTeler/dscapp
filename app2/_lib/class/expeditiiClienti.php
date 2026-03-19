<?php
/**
 * W o r k s p a c e
 *
 */

require_once 'expeditii.php';

class ModulExpeditiiClienti extends BackEnd {

    public $final_result;
    public $action_module;
    public $page_prefix;
    public $site_prefix;
    public $table;

    /**
     * The constructor for the 'ModulExpeditiiClienti' class
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

        $this->vars['title_page'] = 'Expeditii Clienti';
        $this->page_prefix = 'expeditiiclienti_';

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

		else if (!empty($this->user_profile)) {
			$this->ActionsNivelAcces();

		} else
			$this->final_result = $this->PageNotFound();
		// R E S U L T
		return $this->final_result;
	}


	function ActionsNivelAcces() {
		$this->user_rights = $this->GetDrepturiUtilizator($this->user_profile);
		$arr = $this->GenerateArr();
		$flag=0;

		//  actiuni in functie de permisiune
		if (in_array("expeditii_clienti", $this->user_rights) || $this->user_profile == 10){
			if (isset($arr[1]) && $arr[1] == 'json' && $arr[2] == 'expeditii_clienti') {
				echo $this->JSON_ExpeditiiClienti(intval($arr[3]));
				$flag = 1;
			} else if (isset($arr[1]) && $arr[1] == 'json' && $arr[2] == 'liste_expeditii_clienti') {
				echo $this->JSON_ListeExpeditiiClienti(intval($arr[3]));
				$flag = 1;
			} else if (isset($arr[1]) && $arr[1] == 'modifica_expeditii') {
				echo $this->ModificaExpeditii($arr[2]);
				$flag = 1;
			} else if (isset($arr[1]) && $arr[1] == 'detalii_expeditie_client') {
				echo $this->DetaliiExpeditieClient($arr[2]);
				$flag = 1;
			} else {
				$this->final_result = $this->ExpeditiiClienti();
				$flag = 1;
			}
		}

		if(empty($flag))
			$this->final_result = $this->PageNotFound();

	}


	function ModificaExpeditii($type){
		$expeditii = json_decode($_POST['data'], true);

		if(count($expeditii) == 0){
			return '0|||Nici o expeditie selectata';
		}

		$codition = ' expeditie IN ('.implode(",",$expeditii).') ';

		switch ($type):
			case 'sterge':
				$this->db->QueryUpdate($this->tables['client_expeditii'], ['deleted_by' => $this->user_id, 'deleted_at' => date('Y-m-d H:i:s'), 'anulata' => 1, 'stearsa' => 1], $codition);
				$this->StergeExpeditii($expeditii);
				return 1;
				break;
			case 'desterge':
				$this->db->QueryUpdate($this->tables['client_expeditii'], ['deleted_by' => 0, 'deleted_at' => null, 'anulata' => 0, 'stearsa' => 0], $codition);
				$this->DestergeExpeditii($expeditii);
				return 1;
				break;
            case 'creeaza':
                $this->DestergeExpeditii($expeditii,true);
                return 1;
                break;
			case 'anulare':
				$this->db->QueryUpdate($this->tables['client_expeditii'], ['deleted_by' => $this->user_id, 'deleted_at' => date('Y-m-d H:i:s'), 'anulata' => 1], $codition);
				return 1;
				break;
			case 'dezanulare':
				$this->db->QueryUpdate($this->tables['client_expeditii'], ['deleted_by' => 0, 'deleted_at' => null, 'anulata' => 0], $codition);
				return 1;
				break;
			case 'genereaza':
				return $this->GenerareBorderou();
				break;

		endswitch;
		return '0|||Eroare';
	}

	function DestergeExpeditii($expeditii, $creeaza = false){
		foreach ($expeditii as $expeditie){
			$this->db->QueryUpdate($this->tables['exp_prelucrate'], ['deleted_by' => 0, 'deleted_at' => null, 'anulata' => 0], "expeditie = {$expeditie}");
			$this->db->QueryUpdate($this->tables['client_expeditii'], ['deleted_by' => 0, 'deleted_at' => null, 'anulata' => 0, 'stearsa' => 0], "expeditie = {$expeditie}");
		}

	}

	function StergeExpeditii($expeditii){
		foreach ($expeditii as $expeditie){
			$query = "SELECT cod_expeditie, idfact FROM {$this->tables['exp_prelucrate']} WHERE expeditie={$expeditie} and anulata = 0";
			$sql = $this->db->QFetchArray($query);
			if(!empty($sql)){
				if($sql['idfact'] > 0){
                    $mesaj = 'Eroare la stergerea expeditiei<br>Expeditia este prinsa pe factura!';
                    continue;
                }

				$this->db->QueryUpdate($this->tables['exp_prelucrate'], ['deleted_by' => 0, 'deleted_at' => null, 'anulata' => 1], "expeditie = {$expeditie}");
				$this->db->QueryUpdate($this->tables['client_expeditii'], ['deleted_by' => 0, 'deleted_at' => null, 'anulata' => 1, 'stearsa' => 1], " expeditie = {$expeditie}");

				$this->insertIstExp($sql['cod_expeditie'], 31);
				$this->sendUpdateToAndroid($expeditie, true);
			}
		}
	}

	function GenerareBorderou(){
		$exps = $_POST['data'];
		if(empty($exps)) return 1;

		$exps = json_decode($exps,true);
		if(count($exps) == 0) return 1;

		$exps = implode(",", $exps);
		$exps = "(".$exps.")";

		$query = "SELECT COUNT(id) as nr , expeditor, user_id FROM {$this->tables['client_expeditii']} WHERE borderou_id = 0 AND anulata = 0 AND stearsa = 0 AND expeditie in ".$exps;
		$result = $this->db->QFetchArray($query);
		if(empty($result['nr']))
			return '0|||Eroare creare borderou';
		$query1 = "SELECT MAX(borderou_id) as borderou_id FROM {$this->tables['client_borderouri']} WHERE user_id={$result['user_id']}";
		$max = $this->db->QFetchArray($query1);

		$vi = [];
		$vi['data'] = date('Y-m-d');
		$vi['status'] = 'Nereceptionat';
		$vi['user_id'] = $result['user_id'];// $this->user_id;
		$vi['expeditii'] = $result['nr'];
		$vi['client_id'] = $result['expeditor'];
		$vi['borderou_id'] = $max['borderou_id']+1;

		// error_log("======= ----- =========");
		// error_log(json_encode($vi));
		// error_log("====== / ---- / ==========");
		$borderou = $this->db->QueryInsert($this->tables['client_borderouri'], $vi);

		$this->db->QueryUpdate($this->tables['client_expeditii'], ['borderou_id' => $borderou], " borderou_id = 0 AND anulata = 0 AND stearsa = 0 AND expeditie in ".$exps);
		$_POST['curier_nume'] = "SOFT CLIENT (BUCURESTI)";
		$_POST['curier'] = 415;
		$exp = new ModulExpeditii($this->config, 1, $this->db);
		$exp->BorderouriReceptie($borderou, 7);

		return 1;
	}

	function ExpeditiiClienti($message = ''){
		$vars = [];
		$vars['error'] = $message;
		$this->vars['site_title'] = 'Scanare Coduri';
		return $this->Parse($this->page_prefix . 'index.html', $vars);
	}

	function JSON_ExpeditiiClienti($anulata) {
		$responce = new StdClass();
		$cond = " ep.borderou_id = 0 AND ep.stearsa = 0";

		if($anulata == 1){
			$cond .= " AND ep.anulata = 0 ";
		} else if ($anulata == 2){
			$cond .= " AND ep.anulata = 1 ";
		}

		$flag=0;

		//start generare conditie
		$searchOn = $this->Strip($_REQUEST['_search']);
		if ($searchOn == 'true') {
			$searchstr = $this->Strip($_REQUEST['filters']);
			$cond .= $this->constructWhere($searchstr);
		}

		$page = intval($_REQUEST['page'] ?? 1);
		$limit = intval($_REQUEST['rows'] ?? 20);
		$sidx = trim($this->sanitize($_REQUEST['sidx'] ?? 1));
		$sord = trim($this->sanitize($_REQUEST['sord'] ?? 'asc'));

		$query = "
			select count(expeditor_id) as nr, sum(expeditii) as nr_expeditii from (
				SELECT ep.expeditor as expeditor_id, COUNT(ep.id) as expeditii
				FROM client_expeditii ep
				INNER JOIN clienti cl ON ep.expeditor = cl.cod_cl
				LEFT JOIN localitati lc on cl.cod_lc = lc.cod_lc
				WHERE {$cond}
				GROUP BY ep.expeditor
				) as temp_table
		";


		//error_log($query);
		$result = $this->db->QFetchArray($query);
		$count = !empty($result['nr']) ? $result['nr'] : 0;

		if( $count >0 ) {$total_pages = ceil($count/$limit); }
		else { $total_pages = 0; }
		if ($page > $total_pages) $page=$total_pages;
		if ($limit<0) $limit = 0;
		$start = $limit*$page - $limit; // do not put $limit*($page - 1)
		if ($start<0) $start = 0;
		$query = "SELECT ep.data_expeditie, cl.cod_cl as expeditor_id, cl.nume as expeditor_nume, lc.nume_lc as expeditor_localitate, count(id) as expeditii
            FROM {$this->tables['client_expeditii']} ep
			INNER JOIN {$this->tables['clienti']} cl ON ep.expeditor = cl.cod_cl
			LEFT JOIN localitati lc on cl.cod_lc = lc.cod_lc
            WHERE {$cond}
            GROUP BY ep.expeditor
            ORDER BY " . $sidx . " " . $sord . " LIMIT " . $start . " , " . $limit;

		//error_log($query);

		$sql = $this->db->QFetchRowArray($query);
		if (!empty($sql)) {
			foreach ($sql as $key => $row) {

				$row['optiuni'] = '<select id="select_acction_'.$row['expeditor_id'].'"><option>Alege</option><option value="genereaza">Genereaza Borderou</option><option value="anulare">Anulare</option><option value="dezanulare">Dezanulare</option><option value="sterge">Sterge</option></select>';
				$row['optiuni'] .= ' <a href="javascript:;" onclick="ModificaClientExpeditii('.$row['expeditor_id'].');" style="text-decoration:none;">Executa</a>&nbsp;&nbsp;';
				$row['curier_nume'] = '';
				$row['operator_nume'] = '';
				$row['status'] = '';


				$responce->rows[$key]['id'] = $row['expeditor_id'];
				$responce->rows[$key]['cell'] = array($row['expeditor_nume'],$row['expeditor_localitate'],$row['expeditii'],$row['data_expeditie'],'','','',$row['optiuni']);
			}
		}
		$responce->page = $page;
		$responce->total = $total_pages;
		$responce->records = $count;
		return json_encode($responce);
	}


	function JSON_ListeExpeditiiClienti($nr) {
		$responce = new StdClass();
		if($nr == 0) return json_encode($responce);

		$cond = " ep.expeditor = ".$nr." AND ep.borderou_id = 0 AND ep.stearsa = 0";

		$page = intval($_REQUEST['page'] ?? 1);
		$limit = intval($_REQUEST['rows'] ?? 20);
		$sidx = trim($this->sanitize($_REQUEST['sidx'] ?? 1));
		$sord = trim($this->sanitize($_REQUEST['sord'] ?? 'asc'));

		$query = "SELECT COUNT(ep.id) as nr
			FROM {$this->tables['client_expeditii']} ep
			LEFT JOIN {$this->tables['users']} u ON ep.printed_by = u.id
			LEFT JOIN clienti cl on ep.destinatar_cod_cl = cl.cod_cl
			LEFT JOIN localitati lc on lc.cod_lc = cl.cod_lc
			WHERE {$cond}";
		//echo $query;die;
		$result = $this->db->QFetchArray($query);
		//error_log($query);

		$count = !empty($result['nr']) ? $result['nr'] : 0;

		if( $count >0 ) {$total_pages = ceil($count/$limit); }
		else { $total_pages = 0; }
		if ($page > $total_pages) $page=$total_pages;
		if ($limit<0) $limit = 0;
		$start = $limit*$page - $limit; // do not put $limit*($page - 1)
		if ($start<0) $start = 0;


		$query = "SELECT ep.expeditie, ep.data_expeditie, ep.anulata, ep.printed_at, ep.valoare_totala,
			u.user as printed_by, cl.nume as destinatar_nume, lc.nume_lc as destinatar_localitate
            FROM {$this->tables['client_expeditii']} ep
            LEFT JOIN {$this->tables['users']} u ON ep.printed_by = u.id
			LEFT JOIN clienti cl on ep.destinatar_cod_cl = cl.cod_cl
			LEFT JOIN localitati lc on lc.cod_lc = cl.cod_lc
            WHERE {$cond}
            GROUP BY ep.id ORDER BY " . $sidx . " " . $sord . " LIMIT " . $start . " , " . $limit;
		//echo $query;die;
		//error_log($query);
		$sql = $this->db->QFetchRowArray($query);
		if (!empty($sql)) {
			foreach ($sql as $key => $row) {
				$responce->rows[$key]['id'] = $row['expeditie'];
				$responce->rows[$key]['cell'] = array($row['expeditie'],$row['anulata'],$row['printed_at'],$row['printed_by'],strtoupper($row['destinatar_nume']),strtoupper($row['destinatar_localitate']),$row['data_expeditie'],$row['valoare_totala']);
			}
		}
		$responce->page = $page;
		$responce->total = $total_pages;
		$responce->records = $count;
		return json_encode($responce);
	}

	function AnuleazaComanda(){
		$id = 0;
		if(isset($_POST['id'])) $id = intval($_POST['id']);
		if(!empty($id))
		{
			$vv=[];
			$vv['status'] = 7; //anulata
			$vv['created_by'] = $this->user_id;
			$this->db->QueryInsert('comenzi_history', $vv);
			return 1;
		}
		return 0;
	}

	function DetaliiExpeditieClient($expeditie) {
		$expeditie = intval($expeditie);
		if($expeditie == 0) return $this->Parse($this->page_prefix . 'expeditie_detalii.html', []);

		$sqlClient = $this->GetValuesClient($expeditie, 0, null, true);
		$sqlOp = $this->GetValues($expeditie, true);

		if(false !== $sqlClient){
			if($sqlClient['tip_obj'] == 1) $tip_obj = '1 plic';
			else if($sqlClient['tip_obj'] == 3) $tip_obj = '1 palet';

			else if($sqlClient['tip_obj'] == 2){
				if($sqlClient['piese'] > 1){
					$sqlClient['tip_obj'] = $sqlClient['piese'] . " colete";
				} else {
					$sqlClient['tip_obj'] = $sqlClient['piese'] . " colet";
				}
			}
			else $sqlClient['tip_obj'] = 'unknown';
			$sqlClient['tip_exp'] = 'Initiala Client';

			$sqlClient['valoare_expeditie'] = $sqlClient['valoare_exp'];
			$sqlClient['val_greutate'] = $sqlClient['valoare_g'];
			$sqlClient['val_km'] = $sqlClient['valoare_km'];
			$sqlClient['val_asig'] = $sqlClient['valoare_asig'];
			$sqlClient['valoare_totala_expeditie'] = $sqlClient['valoare_totala'];
			$sqlClient['tva'] = $sqlClient['valoare_tva'];
			$sqlClient['km_preluare'] = $sqlClient['expeditor_localitate_km'];
			$sqlClient['km_livrare'] = $sqlClient['destinatar_localitate_km'];
			$sqlClient['liv_samb'] = $sqlClient['liv_sambata'];
			$sqlClient['liv_sed'] = $sqlClient['liv_sediu'];
			$sqlClient['referire'] = 0;

			$sqlClient['curier_preluare'] = $sqlClient['curier_livrare'] = '-';

			$sqlClient['platitor_nume'] = "";
			if($sqlClient['platitor'] == 1){
				$sqlClient['platitor_nume'] = $sqlClient['expeditor_nume'];
			} else if ($sqlClient['platitor'] == 2){
				$sqlClient['platitor_nume'] = $sqlClient['destinatar_nume'];
			}

			if(!empty($sqlClient['ret_nt'])) $sqlClient['ret_nt'] = 'Retur NT.';
			else $sqlClient['ret_nt'] = '';
			if(!empty($sqlClient['ret_doc'])) $sqlClient['ret_doc'] = 'Retur DOC.';
			else $sqlClient['ret_doc'] = '';
			if(!empty($sqlClient['liv_samb'])) $sqlClient['liv_samb'] = 'Liv. Samb.';
			else $sqlClient['liv_samb'] = '';
			if(!empty($sqlClient['liv_sed'])) $sqlClient['liv_sed'] = 'Liv. Sed.';
			else $sqlClient['liv_sed'] = '';

			if(!empty($sqlClient['volum'])) {
				$volumes = explode("x",$sqlClient['volum']);
				if(count($volumes) == 3) {
					list($sqlClient['VOLUM1'], $sqlClient['VOLUM2'], $sqlClient['VOLUM3']) = $volumes;
				}
			}
			$vars['greutate_vol'] = round($sqlExp['greutate_vol'] ?? 0.000, 3);

			if($sqlClient['anulata'] == 1){
				$sqlClient['buton_actiune_anulare'] = '<input type="button" onclick="ModificaExpeditieClient($(\'#expeditie\').val(),\'dezanulare\');" style="margin-left: 10px;" title="Dezanulare" value="Dezanulare" class="button ui-button ui-widget ui-state-default ui-corner-all" id="desterge" name="desterge" role="button" aria-disabled="false">';
			} else {
				$sqlClient['buton_actiune_anulare'] = '<input type="button" onclick="ModificaExpeditieClient($(\'#expeditie\').val(),\'anulare\');" style="margin-left: 10px;" title="Anulare" value="Anulare" class="button ui-button ui-widget ui-state-default ui-corner-all" id="sterge" name="sterge" role="button" aria-disabled="false">';
			}
			if($sqlClient['stearsa'] == 1 || !empty($sqlOp['anulata']) && $sqlOp['anulata'] > 0){
				$sqlClient['buton_actiune_stergere'] = '<input type="button" onclick="ModificaExpeditieClient($(\'#expeditie\').val(),\'desterge\');" style="margin-left: 10px;" title="Recupereaza" value="Recupereaza" class="button ui-button ui-widget ui-state-default ui-corner-all" id="desterge" name="desterge" role="button" aria-disabled="false">';
			} else {
				$sqlClient['buton_actiune_stergere'] = '<input type="button" onclick="ModificaExpeditieClient($(\'#expeditie\').val(),\'sterge\');" style="margin-left: 10px;" title="Sterge" value="Sterge" class="button ui-button ui-widget ui-state-default ui-corner-all" id="sterge" name="sterge" role="button" aria-disabled="false">';
			}

			if($sqlClient['borderou_id'] == 0){
                $sqlClient['buton_actiune_stergere'] .= '<span style="color: red">Expeditie fara borderou !</span>';
            }
			return $this->Parse($this->page_prefix . 'expeditie_detalii.html', $sqlClient);
		}

		if (false !== $sqlOp){
			$tip_obj = '1 plic';
			if($sqlOp['tip_obj'] == 2) $tip_obj = $sqlOp['piese'].' colete';
			else if($sqlOp['tip_obj'] == 3) $tip_obj = '1 palet';
			$sqlOp['tip_obj'] = $tip_obj;

			$sqlOp['tip_exp'] = ExpeditieDto::TIP_EXP[$sqlOp['tip_exp'] ?? 0] ?? "unknown";
			$sqlOp['asigurare'] = $sqlOp['valoare_asigurata'];

			if(!empty($sqlOp['ret_nt'])) $sqlOp['ret_nt'] = 'Retur NT.';
			else $sqlOp['ret_nt'] = '';
			if(!empty($sqlOp['ret_doc'])) $sqlOp['ret_doc'] = 'Retur DOC.';
			else $sqlOp['ret_doc'] = '';
			if(!empty($sqlOp['liv_samb'])) $sqlOp['liv_samb'] = 'Liv. Samb.';
			else $sqlOp['liv_samb'] = '';
			if(!empty($sqlOp['liv_sed'])) $sqlOp['liv_sed'] = 'Liv. Sed.';
			else $sqlOp['liv_sed'] = '';
			if(!empty($sqlOp['ret_amb'])) $sqlOp['ret_amb'] = 'Ret. Amb.';
			else $sqlOp['ret_amb'] = '';
			if(!empty($sqlOp['ret_colet'])) $sqlOp['ret_colet'] = 'Ret. Colet';
			else $sqlOp['ret_colet'] = '';

			if(empty($sqlOp['referire'])) $sqlOp['referire'] = '';
			$sqlOp['retururi'] = "";

			if($sqlOp['tip_exp'] == 0){
				$query = "SELECT group_concat(expeditie) as retururi FROM {$this->tables['exp_prelucrate']} WHERE referire = {$expeditie} and anulata = 0 group by referire";
				$s = $this->db->QFetchArray($query);
				if(!empty($s)){
					$sqlOp['retururi'] = $s['retururi'];
				}
			}

			if($sqlOp['anulata'] > 0)
				$sqlOp['buton_actiune_stergere'] = '<input type="button" onclick="ModificaExpeditieClient($(\'#expeditie\').val(),\'desterge\');" style="margin-left: 10px;" title="Desterge" value="Desterge" class="button ui-button ui-widget ui-state-default ui-corner-all" id="sterge" name="sterge" role="button" aria-disabled="false">';
			else
				$sqlOp['buton_actiune_stergere'] = '<input type="button" onclick="ModificaExpeditieClient($(\'#expeditie\').val(),\'sterge\');" style="margin-left: 10px;" title="Sterge" value="Sterge" class="button ui-button ui-widget ui-state-default ui-corner-all" id="sterge" name="sterge" role="button" aria-disabled="false">';


			return $this->Parse($this->page_prefix . 'expeditie_detalii.html', $sqlOp);
		}

		return $this->Parse($this->page_prefix . 'expeditie_detalii.html', []);
	}

}//end class
