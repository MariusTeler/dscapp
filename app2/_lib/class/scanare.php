<?php
require_once "expeditieDto.php";

use OpenSpout\Writer\CSV\Writer;
use OpenSpout\Writer\CSV\Options;
use OpenSpout\Common\Entity\Row;
use OpenSpout\Common\Entity\Cell;
/**
 * Scanare
 *
 */
class ModulScanare extends BackEnd {

    public $final_result;
    public $page_prefix;

    public $ascunde_scanarile_clientilor_blocati = true;

    function __construct($config = 0, $act = 1, $db = 0) {
        parent :: __construct($config, $db);

        $this->vars['title_page'] = 'Scanare';
        $this->page_prefix = 'scanare_';

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

			if (in_array("afiseaza_scanarile_clientilor_blocati", $this->user_rights)) {
				$this->ascunde_scanarile_clientilor_blocati = false;
			}

			if ($this->user_profile==10 || 1==1){

				if (isset($arr[1]) && $arr[1] == 'istoric-codbare')
						$this->final_result = $this->IstoricCodBare();
				else if (isset($arr[1]) && $arr[1] == 'json' && isset($arr[2]) && $arr[2] == 'istoric_codbare')
						echo $this->JSON_IstoricCodBare();

				else if (isset($arr[1]) && $arr[1] == 'istoric_scanari')
						$this->final_result = $this->IstoricScanari();
				else if (isset($arr[1]) && $arr[1] == 'json' && isset($arr[2]) && $arr[2] == 'istoric_scanari')
						echo $this->JSON_IstoricScanari();
				else if (isset($arr[1]) && $arr[1] == 'print_expeditii_istoric_scanare')
						echo $this->Print_ListeExpeditiiIstoricScanare();
				else if (isset($arr[1]) && $arr[1] == 'export_expeditii_istoric_scanare')
						echo $this->ExportExpeditiiIstoricScanareCsv();

				else if (isset($arr[1]) && $arr[1] == 'fara_key')
						$this->final_result = $this->FaraKey();
				else if (isset($arr[1]) && $arr[1] == 'json' && isset($arr[2]) && $arr[2] == 'fara_key')
						echo $this->JSON_FaraKey();
				else if (isset($arr[1]) && $arr[1] == 'export_expeditii_fara_key')
						echo $this->ExportExpeditiiFaraKeyCsv();

				else if (isset($arr[1]) && $arr[1] == 'diferente_rute')
						$this->final_result = $this->DiferenteRute();
				else if (isset($arr[1]) && $arr[1] == 'json' && isset($arr[2]) && $arr[2] == 'diferente_rute')
						echo $this->JSON_DiferenteRute();
				else if (isset($arr[1]) && $arr[1] == 'export' && isset($arr[2]) && $arr[2] == 'diferente_rute')
						echo $this->ExportDiferenteRute();

				else if (isset($arr[1]) && $arr[1] == 'diferente_centru')
						$this->final_result = $this->DiferenteCentru();
				else if (isset($arr[1]) && $arr[1] == 'json' && isset($arr[2]) && $arr[2] == 'diferente_centru')
						echo $this->JSON_DiferenteCentru();
				else if (isset($arr[1]) && $arr[1] == 'export' && isset($arr[2]) && $arr[2] == 'diferente_centru')
						echo $this->ExportDiferenteCentru();

				else if (isset($arr[1]) && $arr[1] == 'diferente_agent')
						$this->final_result = $this->DiferenteAgent();
				else if (isset($arr[1]) && $arr[1] == 'json' && isset($arr[2]) && $arr[2] == 'diferente_agent')
						echo $this->JSON_DiferenteAgent();
				else if (isset($arr[1]) && $arr[1] == 'export' && isset($arr[2]) && $arr[2] == 'diferente_agent')
						echo $this->ExportDiferenteAgent();

				else if (isset($arr[1]) && $arr[1] == 'bo_livrare_curieri')
						$this->final_result = $this->BoLivrareCurieri();
				else if (isset($arr[1]) && $arr[1] == 'json' && isset($arr[2]) && $arr[2] == 'bo_livrare_curieri')
				{
					if(!empty($arr[3]))
						echo $this->JSON_BoLivrareCurieriExpeditii(intval($arr[3]));
					else
						echo $this->JSON_BoLivrareCurieri();
				}
				else if (isset($arr[1]) && $arr[1] == 'print' && isset($arr[2]) && $arr[2] == 'bo_livrare_curieri')
				{
					echo $this->Print_BorderouriExpeditiiScanare_TcpdfCurier();
				}
				else
					$this->final_result = $this->PageNotFound();
			}
			else
				$this->final_result = $this->PageNotFound();
    }

 //-------------------------------- functii ----------------------------------------


	//scanare istoric codbare
	function IstoricCodBare() {
        $this->vars['title_page'] = 'Istoric CodBare';
        $vars = [];

        return $this->Parse($this->page_prefix . 'istoric_codbare.html', $vars);
    }

    function JSON_IstoricCodBare() {
		$codBare = '';
		$responce = new StdClass();
		$responce->page = 1;
       	$responce->total = 1;
        $responce->records = 0;

		$expeditie = $this->sanitize($_GET['codbare'] ?? 0);
		if(!ExpeditieDto::isValidCod($expeditie))
			return json_encode($responce);

		if(ExpeditieDto::isPuisor($expeditie)) {
			$expeditie = ExpeditieDto::getAwbFromPuisor($expeditie);
		}
		else {
			$expeditie = intval($expeditie);
		}

		if($expeditie == 0) return json_encode($responce);

		$sidx = trim($this->sanitize($_REQUEST['sidx'] ?? 'sc.data'));
		$sord = trim($this->sanitize($_REQUEST['sord'] ?? 'desc'));
		$order_by = "{$sidx} {$sord}";

		if($sidx == 'sc.data') $order_by = "sc.cod, sc.data {$sord}";
		else if($sidx == 'sc.cod') $order_by = "sc.cod {$sord}, sc.data desc";
		else if($sidx == 'ce.nume') $order_by = "ce.nume {$sord}, sc.cod, sc.data desc";
		else if($sidx == 'ru.denumire') $order_by = "ru.denumire {$sord}, sc.cod";
		else if($sidx == 'sc.tip') $order_by = "sc.tip {$sord}, sc.cod";

		$query = "SELECT sc.id, sc.cod, sc.tip, sc.data, ce.nume as centru,
			ag.nume_ag as curier, ru.denumire, ck.denumire as ckp, u.nume as user,
			IF(clp.OBS_BL = 1 OR clm.OBS_BL = 1,1,0) as OBS_BL
			FROM scanari_coduri as sc
			LEFT JOIN exp_prelucrate as ep ON (sc.expeditie = ep.expeditie and ep.anulata = 0)
			LEFT JOIN clienti clp ON clp.cod_cl = ep.platitor_id
			LEFT JOIN clienti clm ON clm.cod_cl = clp.master
			LEFT JOIN centre as ce ON sc.centru = ce.id
			LEFT JOIN agenti as ag ON sc.curier = ag.cod_ag
			LEFT JOIN rute as ru ON sc.ruta = ru.id
			left join checkpoints as ck ON ck.id = sc.tip
			left join users as u on sc.user = u.id
			WHERE sc.expeditie = {$expeditie}
			ORDER BY {$order_by}";
		$sql = $this->db->QFetchRowArray($query);

		$i = 0;
		if($sql){
			foreach ($sql as $k => $row) {
				$responce->rows[$i]['id'] = $row['id'];
				if($row['OBS_BL'] == 1 ){
					$row['cod'] = '<span style="color:red; font-weight: bold">'.$row['cod'].'</span>';
				}
				if($row['OBS_BL'] == 1  && $this->ascunde_scanarile_clientilor_blocati){
					$responce->rows[$i]['cell'] = array('<span style="color:red; font-weight: bold">Client blocat</span>',$row['expeditie'],"-","-","-","-","-","-","-","-","-","-","-","-","-","-","-","-","-","-","-","-","-");
				} else {
					$responce->rows[$i]['cell'] = array($row['cod'],strtoupper($row['centru']),strtoupper($row['denumire']),strtoupper((empty($row['curier']) ? $row['user'] : $row['curier'])),$row['ckp'],$row['data']);
				}
				$i++;
			}
		}
		$responce->page = 1;
		$responce->total = 1;
		$responce->records = $i;
		return json_encode($responce);
    }

	//istoric scanari
	function IstoricScanari() {
        $this->vars['title_page'] = 'Istoric Scanari';
        $vars = [];

		$vars['checkpoints'] = $this->ComboCheckPoints();
		$vars['data_start'] = date('d.m.Y').' 00:00';
		$vars['data_final'] = date('d.m.Y').' 23:59';

        return $this->Parse($this->page_prefix . 'istoric_scanari.html', $vars);
    }

    function JSON_IstoricScanari() {
 		$responce = new StdClass();
		$categorie=0;
		if(isset($_REQUEST['categorie'])) $categorie = $_REQUEST['categorie'];
		if($categorie==1) {$cond = '1=1';}
		else if($categorie==2) $cond = ' ep.plicuri > 0';
		else if($categorie==3) $cond = ' (ep.colete > 0 or ep.paleti > 0)';
		//else if($categorie==4) $cond = 'ep.paleti>0'.$cond;
		else return json_encode($responce);

		$today = date('Y-m-d');
		$data_start = $today.' 00:00:00';
	    $data_final = $today.' 23:59:59';

		if(isset($_REQUEST['data_start']) && isset($_REQUEST['data_final'])){
			try {
					$data_start = DateTimeImmutable::createFromFormat('d.m.Y H:i', $_REQUEST['data_start']);
					$data_final = DateTimeImmutable::createFromFormat('d.m.Y H:i', $_REQUEST['data_final']);
					if($data_start && $data_final) {
						if(intval($data_start->diff($data_final, true)->format('%a')) > 7){
							$data_start = $data_final;
							$data_final = $data_final->format('Y-m-d H:i:s');
							$data_start = $data_start->sub(new DateInterval('P7D'));
							$data_start = $data_start->format('Y-m-d H:i:s');
						}
						else {
							$data_start = $data_start->format('Y-m-d H:i:s');
	    					$data_final = $data_final->format('Y-m-d H:i:s');
						}

					}
					else {
						$data_start = $today.' 00:00:00';
	    				$data_final = $today.' 23:59:59';
					}
			}
			catch (Exception $e){
					$data_start = $today.' 00:00:00';
	    			$data_final = $today.' 23:59:59';
			}
	    } else {
			$data_start = $today.' 00:00:00';
	    	$data_final = $today.' 23:59:59';
		}

		$cond .= " AND sc.data >= '{$data_start}' AND sc.data <= '{$data_final}'";

		$borderou_id = intval($this->sanitize($_REQUEST['borderou'] ?? 0));
		if($borderou_id > 0){
			$cond .= " AND sc.borderou = {$borderou_id}";
		}
		$centru_id = intval($this->sanitize($_REQUEST['centru'] ?? 0));
		if($centru_id > 0){
			$cond .= " AND sc.centru = {$centru_id}";
		}
		$agent_id = intval($this->sanitize($_REQUEST['agent'] ?? 0));
		if($agent_id > 0){
			$cond .= " AND sc.curier = {$agent_id}";
		}
		$ruta_id = intval($this->sanitize($_REQUEST['ruta'] ?? 0));
		if($ruta_id > 0){
			$cond .= " AND sc.ruta = {$ruta_id}";
		}
		$tip_scanare = intval($this->sanitize($_REQUEST['tip_scanare'] ?? 0));
		if($tip_scanare > 0){
			$cond .= " AND sc.tip = {$tip_scanare}";
		}
		$puisori = intval($this->sanitize($_REQUEST['puisori'] ?? 0));
		if($puisori > 0){
			$cond .= " AND sc.is_awb = 1";
		}

		$searchOn = false;
		if(isset($_REQUEST['_search'])) $searchOn = $this->Strip($_REQUEST['_search']);
        if ($searchOn == 'true') {
            $searchstr = $this->Strip($_REQUEST['filters']);
            $cond .= $this->constructWhere($searchstr);
        }

		$cond = preg_replace("/tip_obj  = '1'/i", "ep.plicuri > 0", $cond);
		$cond = preg_replace("/tip_obj  = '2'/i", "ep.colete > 0", $cond);
		$cond = preg_replace("/tip_obj  = '3'/i", "ep.paleti > 0", $cond);
		$cond = preg_replace("/tip_obj  = '4'/i", "(ep.colete > 0 or ep.paleti > 0)", $cond);
		
		$cond = preg_replace("/expeditor_centru_cod/i", "IF(cle.zona_id > 0 and clec.id > 0, clec.label, cee.label)", $cond);
		$cond = preg_replace("/expeditor_centru/i", "IF(cle.zona_id > 0 and clec.id > 0, clec.nume, cee.nume)", $cond);
		$cond = preg_replace("/destinatar_centru_cod/i", "IF(cld.zona_id > 0 and cldc.id > 0, cldc.label, ced.label)", $cond);
		$cond = preg_replace("/destinatar_centru/i", "IF(cld.zona_id > 0 and cldc.id > 0, cldc.nume, ced.nume)", $cond);
		
		$page = intval($_REQUEST['page'] ?? 1);
		$limit = intval($_REQUEST['rows'] ?? 20);
		$sidx = trim($this->sanitize($_REQUEST['sidx'] ?? 1));
		$sord = trim($this->sanitize($_REQUEST['sord'] ?? 'asc'));

		$query = "SELECT COUNT(sc.cod) as nr
			FROM  scanari_coduri as sc use index (data)
			LEFT JOIN rute as ru ON sc.ruta = ru.id
			LEFT JOIN agenti as ag ON sc.curier = ag.cod_ag
			LEFT JOIN exp_prelucrate as ep ON (sc.expeditie = ep.expeditie and ep.anulata = 0)
			left join clienti cle on cle.cod_cl = ep.expeditor_id
			left join clienti cld on cld.cod_cl = ep.destinatar_id
			left join localitati lce ON lce.cod_lc = cle.cod_lc
			left join localitati lcd ON lcd.cod_lc = cld.cod_lc
			left join centre cee ON cee.id = lce.cod_centru
			left join centre ced ON ced.id = lcd.cod_centru
			LEFT JOIN clienti clp ON clp.cod_cl = ep.platitor_id
            LEFT JOIN clienti clm ON clm.cod_cl = clp.master
			LEFT JOIN checkpoints as ck ON sc.tip = ck.id
			LEFT JOIN centre as ce ON sc.centru = ce.id
			LEFT JOIN users as u ON sc.user = u.id
			LEFT JOIN zones clez ON clez.id = cle.zona_id
        	LEFT JOIN centre clec on clec.id = clez.centru_id
			LEFT JOIN zones cldz ON cldz.id = cld.zona_id
        	LEFT JOIN centre cldc on cldc.id = cldz.centru_id
			WHERE {$cond}";
		//error_log($query);
        $result = $this->db->QFetchArray($query);
        $count = !empty($result['nr']) ? $result['nr'] : 0;

        if( $count >0 ) {
            $total_pages = ceil($count/$limit); }
        else { $total_pages = 0; }
        if ($page > $total_pages) $page=$total_pages;
        if ($limit<0) $limit = 0;
        $start = $limit*$page - $limit; // do not put $limit*($page - 1)
        if ($start<0) $start = 0;

		$query = "SELECT sc.cod,sc.borderou,sc.data as data_scanare,
			ep.expeditie,ep.data_expeditie,ep.tip_exp,ep.plicuri,ep.colete,ep.paleti,ep.greutate,
			cle.nume as expeditor,lce.nume_lc as expeditor_localitate,
			cld.nume as destinatar,lcd.nume_lc as destinatar_localitate,
			IF(cle.zona_id > 0 and clec.id > 0, clec.nume, cee.nume) as expeditor_centru,
			IF(cld.zona_id > 0 and cldc.id > 0, cldc.nume, ced.nume) as destinatar_centru,
      		IF(cle.zona_id > 0 and clec.id > 0, clec.label, cee.label) as expeditor_centru_cod,
			IF(cld.zona_id > 0 and cldc.id > 0, cldc.label, ced.label) as destinatar_centru_cod,
			ru.denumire as ruta, ag.nume_ag as curier, ck.denumire as tip_scanare, ce.nume as centru, u.user as user,
			ep.valoare_asigurata, ep.ramburs, IF(ep.mod_plata > 0, 0, ep.valoare_totala_expeditie + ep.tva) as cash, ep.mod_plata, ep.operatiune,
			IF(clp.OBS_BL = 1 OR clm.OBS_BL = 1,1,0) as OBS_BL
			FROM  scanari_coduri as sc use index (data)
			LEFT JOIN rute as ru ON sc.ruta = ru.id
			LEFT JOIN agenti as ag ON sc.curier = ag.cod_ag
			LEFT JOIN exp_prelucrate as ep ON (sc.expeditie = ep.expeditie and ep.anulata = 0)
			left join clienti cle on cle.cod_cl = ep.expeditor_id
			left join clienti cld on cld.cod_cl = ep.destinatar_id
			left join localitati lce ON lce.cod_lc = cle.cod_lc
			left join localitati lcd ON lcd.cod_lc = cld.cod_lc
			left join centre cee ON cee.id = lce.cod_centru
			left join centre ced ON ced.id = lcd.cod_centru
			LEFT JOIN clienti clp ON clp.cod_cl = ep.platitor_id
            LEFT JOIN clienti clm ON clm.cod_cl = clp.master
			LEFT JOIN checkpoints as ck ON sc.tip = ck.id
			LEFT JOIN centre as ce ON sc.centru = ce.id
			LEFT JOIN users as u ON sc.user = u.id
			LEFT JOIN zones clez ON clez.id = cle.zona_id
        	LEFT JOIN centre clec on clec.id = clez.centru_id
			LEFT JOIN zones cldz ON cldz.id = cld.zona_id
        	LEFT JOIN centre cldc on cldc.id = cldz.centru_id
			WHERE {$cond}
			ORDER BY " . $sidx . " " . $sord . " LIMIT " . $start . " , " . $limit;
		if($this->user_id == parent::MARIAN) 
			$this->log($query, self::APP_LOG_FILE);
        $sql = $this->db->QFetchRowArray($query);
        $total_ramburs = 0.00;
        if (!empty($sql)) {
            foreach ($sql as $key => $row) {
				if($row['tip_exp'] == 3) $row['ramburs'] += $row['valoare_asigurata'];
				$row['tip_obj'] = $row['piese'] = 0;
				if(!empty($row['plicuri'])) {$row['tip_obj'] = 1;$row['piese'] = 1;}
				if(!empty($row['colete'])) {$row['tip_obj'] = 2;$row['piese'] = $row['colete'];}
				if(!empty($row['paleti'])) {$row['tip_obj'] = 3;$row['piese'] = $row['paleti'];}

                //$responce->rows[$key]['id'] = $row['expeditie'];
                if(empty($row['expeditie'])) $row['expeditie'] = 'fara expeditie';

				$row['cash'] = number_format(round($row['cash'], 2), 2, '.', '');
				$row['ramburs'] = number_format(round($row['ramburs'], 2), 2, '.', '');

                $responce->rows[$key]['id'] = $row['cod'];
                if($row['OBS_BL'] == 1 ){
                    $row['cod'] = '<span style="color:red; font-weight: bold">'.$row['cod'].'</span>';
                }
                if($row['OBS_BL'] == 1 && $this->ascunde_scanarile_clientilor_blocati){
                    $responce->rows[$key]['cell'] = array('<span style="color:red; font-weight: bold">Client blocat</span>',$row['expeditie'],"-","-","-","-","-","-","-","-","-","-","-","-","-","-","-","-","-","-","-","-","-");
                } else {
                    $responce->rows[$key]['cell'] = array($row['cod'],$row['expeditie'],$row['borderou'],$row['tip_obj'],$row['piese'],$row['greutate'],$row['tip_scanare'],$row['operatiune'],$row['ruta'],$row['centru'],$row['destinatar_centru'],$row['curier'],strtoupper($row['expeditor']),strtoupper($row['expeditor_localitate']),strtoupper($row['destinatar']),strtoupper($row['destinatar_localitate']),$row['cash'],$row['ramburs'],$row['data_expeditie'],$row['data_scanare'],$row['user'],$row['expeditor_centru_cod'],$row['destinatar_centru_cod']);
                }
                $total_ramburs += $row['ramburs'];
                //print_r($row);
            }
        }
		$responce->userdata['codbare'] = 'Total:';
		$responce->userdata['expeditie'] =  $count.' Scanari';
		$responce->userdata['ramburs'] =  $total_ramburs;

        $responce->page = $page;
        $responce->total = $total_pages;
        $responce->records = $count;
        return json_encode($responce);
	}

	private function getUCentru() {
		$query="select centru from users where id = ".$this->user_id ;
        $sql = $this->db->QFetchRowAssoc($query);
        if (!empty($sql)) {
          return $sql['centru'];
        }
        return 0;
	}

	//istoric scanari
	function BoLivrareCurieri() {
        $this->vars['title_page'] = 'Borderou livrare curieri';
        $vars = [];

		//$vars['checkpoints'] = $this->ComboCheckPoints();
		$vars['data_start'] = date('d.m.Y');
		$vars['centre'] = $this->ComboCentre($this->getUCentru());
        return $this->Parse($this->page_prefix . 'bo_livrare_curieri.html', $vars);
    }

    function JSON_BoLivrareCurieri() {
 		$responce = new StdClass();

		$today = date('Y-m-d');
		$data_start = $today.' 00:00:00';
		$data_final = $today.' 23:59:59';

		if(isset($_REQUEST['data_start'])){
			$ddata_start = DateTime::createFromFormat('d.m.Y', $_REQUEST['data_start']);

			if($ddata_start !== false){
				$data_start = ($ddata_start->format('Y-m-d')).' 00:00:00';
				$data_final = ($ddata_start->format('Y-m-d')).' 23:59:59';
			}
	    }

	    $cond = " sc.data >= '".$data_start."' AND sc.data <= '".$data_final."'";

		if(!empty($_REQUEST['centru'])){
			$cond .= ' AND ag.cod_centru ='.intval($_REQUEST['centru']);
		}

		if(!empty($_REQUEST['agent'])){
			$cond .= ' AND sc.curier ='.intval($_REQUEST['agent']);
		}

		$cond .= ' AND sc.tip = 4';

		$searchOn = false;
		if(isset($_REQUEST['_search'])) $searchOn = $this->Strip($_REQUEST['_search']);
        if ($searchOn == 'true') {
            $searchstr = $this->Strip($_REQUEST['filters']);
            $cond .= $this->constructWhere($searchstr);
        }

		$page = intval($_REQUEST['page'] ?? 1);
		$limit = intval($_REQUEST['rows'] ?? 20);
		$sidx = trim($this->sanitize($_REQUEST['sidx'] ?? 1));
		$sord = trim($this->sanitize($_REQUEST['sord'] ?? 'asc'));

		$query = "SELECT COUNT(sc.id) as nr
			FROM scanari_coduri as sc use index (data)
			JOIN scanari_borderouri as scb on scb.id = sc.borderou
			LEFT JOIN agenti as ag on scb.curier = ag.cod_ag
			WHERE {$cond} and sc.is_awb = 1
			group by sc.borderou";
		//error_log($query);
        $result = $this->db->QFetchArray($query);
		$count = !empty($result['nr']) ? $result['nr'] : 0;

        if( $count > 0 ) {
            $total_pages = ceil($count/$limit); }
        else { $total_pages = 0; }
        if ($page > $total_pages) $page=$total_pages;
        if ($limit < 0) $limit = 0;
        $start = $limit*$page - $limit; // do not put $limit*($page - 1)
        if ($start < 0) $start = 0;

		$query = "SELECT sc.borderou as borderou_id, sc.data as data_scanare,
			(count(distinct ep.expeditie) + sum(IF(CHAR_LENGTH(sc.cod) in (7,8,9,10) AND INSTR( sc.cod , '-' ) = 0 AND ep.expeditie IS NULL,1,0))) as nr_expeditii,
			count(sc.cod) as piese, sum(IF(ep.expeditie IS NOT NULL, ep.greutate, 0)) as kg,
			ag.nume_ag as agent, scb.ack as ack
			FROM scanari_coduri as sc use index (data)
			JOIN scanari_borderouri as scb on scb.id = sc.borderou
			LEFT JOIN exp_prelucrate as ep ON (ep.expeditie = sc.expeditie and ep.anulata = 0)
			LEFT JOIN agenti as ag on scb.curier = ag.cod_ag
			WHERE {$cond} and sc.is_awb = 1
			group by sc.borderou
			ORDER BY " . $sidx . " " . $sord . " LIMIT " . $start . " , " . $limit . ";";
		// print_r($query);die;
		// error_log($query);
        $sql = $this->db->QFetchRowArray($query);
		$total_nr_exp = 0;
		$total_piese = 0;
		$total_kg = 0;
        if (!empty($sql)) {
            foreach ($sql as $key => $row) {
				$responce->rows[$key]['id'] = $row['borderou_id'];
				$total_nr_exp+=$row['nr_expeditii'];
				$total_piese+=$row['piese'];
				$total_kg+=$row['kg'];
                $responce->rows[$key]['cell'] = array($row['borderou_id'],$row['data_scanare'],$row['nr_expeditii'],$row['piese'],$row['kg'],$row['agent'],$row['ack']);
            }
        }
		$responce->userdata['borderou_id'] = 'Total:';
		$responce->userdata['nr_expeditii'] =  $total_nr_exp.' Expeditii';
		$responce->userdata['piese'] =  $total_piese.' piese';
		$responce->userdata['kg'] =  $total_kg.' kg';
        $responce->page = $page;
        $responce->total = $total_pages;
        return json_encode($responce);
	}

	function JSON_BoLivrareCurieriExpeditii($bo) {
		$responce = new StdClass();
		$responce->page = 0;
		$responce->total = 0;
		$responce->records = 0;
		if($bo == 0) return json_encode($responce);

		$cond = ' sc.borderou='.$bo;

		$searchOn = false;
		if(isset($_REQUEST['_search'])) $searchOn = $this->Strip($_REQUEST['_search']);
		if ($searchOn == 'true') {
			$searchstr = $this->Strip($_REQUEST['filters']);
			$cond .= $this->constructWhere($searchstr);
		}

		$cond = preg_replace("/expeditor_centru_cod/i", "IF(cle.zona_id > 0 and clec.id > 0, clec.label, cee.label)", $cond);
		$cond = preg_replace("/expeditor_centru/i", "IF(cle.zona_id > 0 and clec.id > 0, clec.nume, cee.nume)", $cond);
		$cond = preg_replace("/destinatar_centru_cod/i", "IF(cld.zona_id > 0 and cldc.id > 0, cldc.label, ced.label)", $cond);
		$cond = preg_replace("/destinatar_centru/i", "IF(cld.zona_id > 0 and cldc.id > 0, cldc.nume, ced.nume)", $cond);
		
	   	$page = intval($_REQUEST['page'] ?? 1);
		$limit = intval($_REQUEST['rows'] ?? 20);
		$sidx = trim($this->sanitize($_REQUEST['sidx'] ?? 1));
		$sord = trim($this->sanitize($_REQUEST['sord'] ?? 'asc'));

	   $query = "SELECT COUNT(sc.id) as nr
			FROM  scanari_coduri as sc use index (borderou)
			LEFT JOIN exp_prelucrate as ep ON (sc.expeditie = ep.expeditie and ep.anulata = 0)
			left join clienti cle on cle.cod_cl = ep.expeditor_id
			left join clienti cld on cld.cod_cl = ep.destinatar_id
			LEFT JOIN zones clez ON clez.id = cle.zona_id
			LEFT JOIN centre clec on clec.id = clez.centru_id
			LEFT JOIN zones cldz ON cldz.id = cld.zona_id
			LEFT JOIN centre cldc on cldc.id = cldz.centru_id
			left join localitati lce ON lce.cod_lc = cle.cod_lc
			left join localitati lcd ON lcd.cod_lc = cld.cod_lc
			left join centre cee ON cee.id = lce.cod_centru
			left join centre ced ON ced.id = lcd.cod_centru
		   WHERE {$cond}";
	   $result = $this->db->QFetchArray($query);
	   $count = !empty($result['nr']) ? $result['nr'] : 0;

	   if( $count > 0 ) {
		   $total_pages = ceil($count/$limit); }
	   else { $total_pages = 0; }
	   if ($page > $total_pages) $page=$total_pages;
	   if ($limit < 0) $limit = 0;
	   $start = $limit*$page - $limit; // do not put $limit*($page - 1)
	   if ($start < 0) $start = 0;

	   $query = "SELECT sc.cod, sc.borderou, sc.data as data_scanare,
			ep.expeditie, ep.data_expeditie, ep.plicuri, ep.colete, ep.paleti, ep.tip_exp,
			cle.nume as expeditor,lce.nume_lc as expeditor_localitate,
			cld.nume as destinatar,lcd.nume_lc as destinatar_localitate,
			IF(cle.zona_id > 0 and clec.id > 0, clec.nume, cee.nume) as expeditor_centru,
			IF(cld.zona_id > 0 and cldc.id > 0, cldc.nume, ced.nume) as destinatar_centru,
      		IF(cle.zona_id > 0 and clec.id > 0, clec.label, cee.label) as expeditor_centru_cod,
			IF(cld.zona_id > 0 and cldc.id > 0, cldc.label, ced.label) as destinatar_centru_cod,
			ep.greutate, ep.valoare_asigurata, ep.ramburs,ep.valoare_totala_expeditie as cash, ep.tva, ep.mod_plata, ep.operatiune,
			ru.denumire as ruta, ag.nume_ag as curier, ck.denumire as tip_scanare, ce.nume as centru, u.user,
			IF(clp.OBS_BL = 1 OR clm.OBS_BL = 1,1,0) as OBS_BL
			FROM  scanari_coduri as sc use index (borderou)
			LEFT JOIN rute as ru ON sc.ruta = ru.id
			LEFT JOIN agenti as ag ON sc.curier = ag.cod_ag
			LEFT JOIN exp_prelucrate as ep ON (sc.expeditie = ep.expeditie and ep.anulata = 0)
			left join clienti cle on cle.cod_cl = ep.expeditor_id
			left join clienti cld on cld.cod_cl = ep.destinatar_id
			LEFT JOIN zones clez ON clez.id = cle.zona_id
        	LEFT JOIN centre clec on clec.id = clez.centru_id
			LEFT JOIN zones cldz ON cldz.id = cld.zona_id
        	LEFT JOIN centre cldc on cldc.id = cldz.centru_id
			left join localitati lce ON lce.cod_lc = cle.cod_lc
			left join localitati lcd ON lcd.cod_lc = cld.cod_lc
			left join centre cee ON cee.id = lce.cod_centru
			left join centre ced ON ced.id = lcd.cod_centru
			LEFT JOIN clienti clp ON clp.cod_cl = ep.platitor_id
            LEFT JOIN clienti clm ON clm.cod_cl = clp.master
			LEFT JOIN checkpoints as ck ON sc.tip = ck.id
			LEFT JOIN centre as ce ON sc.centru = ce.id
			LEFT JOIN users as u ON sc.user = u.id
			WHERE {$cond}
			ORDER BY " . $sidx . " " . $sord . " LIMIT " . $start . " , " . $limit . ";";
		// print_r($query);die;
		// error_log($query);
        $sql = $this->db->QFetchRowArray($query);
        $total_ramburs = 0.00;
        if (!empty($sql)) {
            foreach ($sql as $key => $row) {

				if($row['tip_exp'] == 3) $row['ramburs'] += $row['valoare_asigurata'];
				$row['tip_obj'] = 'NA';
				$row['piese'] = 0;
				if(!empty($row['plicuri'])) {$row['tip_obj'] = 'Plic';$row['piese'] = 1;}
				if(!empty($row['colete'])) {$row['tip_obj'] = 'Colete';$row['piese'] = $row['colete'];}
				if(!empty($row['paleti'])) {$row['tip_obj'] = 'Paleti';$row['piese'] = $row['paleti'];}

                //$responce->rows[$key]['id'] = $row['expeditie'];
                if(empty($row['expeditie'])) $row['expeditie'] = 'fara expeditie';

                $row['cash'] = floatval($row['cash'])+floatval($row['tva']);
                if($row['mod_plata']!=0)
                $row['cash'] = 0;

                $responce->rows[$key]['id'] = $row['cod'];
                if($row['OBS_BL'] == 1 ){
                    $row['cod'] = '<span style="color:red; font-weight: bold">'.$row['cod'].'</span>';
                }
                if($row['OBS_BL'] == 1 && $this->ascunde_scanarile_clientilor_blocati){
                    $responce->rows[$key]['cell'] = array('<span style="color:red; font-weight: bold">Client blocat</span>',$row['expeditie'],"-","-","-","-","-","-","-","-","-","-","-","-","-","-","-","-","-","-","-","-","-");
                } else {
                    $responce->rows[$key]['cell'] = array($row['cod'],$row['expeditie'],$row['borderou'],$row['tip_obj'],$row['piese'],$row['greutate'],$row['tip_scanare'],$row['operatiune'],$row['ruta'],$row['centru'],$row['destinatar_centru'],$row['curier'],strtoupper($row['expeditor']),strtoupper($row['expeditor_localitate']),strtoupper($row['destinatar']),strtoupper($row['destinatar_localitate']),$row['cash'],$row['ramburs'],$row['data_expeditie'],$row['data_scanare'],$row['user'],$row['expeditor_centru_cod'],$row['destinatar_centru_cod']);
                }
                $total_ramburs += $row['ramburs'];
                //print_r($row);
            }
        }
		$responce->userdata['codbare'] = 'Total:';
		$responce->userdata['expeditie'] =  $count.' Scanari';
		$responce->userdata['ramburs'] =  $total_ramburs;

	   $responce->page = $page;
	   $responce->total = $total_pages;
	   $responce->records = $count;
	   return json_encode($responce);
   }

   function ExportExpeditiiIstoricScanareCsv() {
		$filtru = '';
		$categorie = $_REQUEST['categorie'];
		if($categorie==1) {$cond = '1=1';}
		else if($categorie==2) $cond = 'ep.plicuri > 0';
		else if($categorie==3) $cond = '(ep.colete > 0 or ep.paleti > 0)';
		//else if($categorie==4) $cond = 'ep.paleti > 0'.$cond;
		else $cond = '1=2';

		$today = date('Y-m-d');
		if(isset($_REQUEST['data_start']) && isset($_REQUEST['data_final'])){
			try {
					$data_start = DateTimeImmutable::createFromFormat('d.m.Y H:i', $_REQUEST['data_start']);
					$data_final = DateTimeImmutable::createFromFormat('d.m.Y H:i', $_REQUEST['data_final']);
					if($data_start && $data_final) {
						if(intval($data_start->diff($data_final, true)->format('%a')) > 7){
							$data_start = $data_final;
							$data_final = $data_final->format('Y-m-d H:i:s');
							$data_start = $data_start->sub(new DateInterval('P7D'));
							$data_start = $data_start->format('Y-m-d H:i:s');
						}
						else {
							$data_start = $data_start->format('Y-m-d H:i:s');
	    					$data_final = $data_final->format('Y-m-d H:i:s');
						}

					}
					else {
						$data_start = $today.' 00:00:00';
	    				$data_final = $today.' 23:59:59';
					}
			}
			catch (Exception $e){
					$data_start = $today.' 00:00:00';
	    			$data_final = $today.' 23:59:59';
			}
	    }
		else {
			$data_start = $today.' 00:00:00';
	    	$data_final = $today.' 23:59:59';
		}

		$cond .= " AND sc.data>='".$data_start."' AND sc.data<='".$data_final."'";

		if(!empty($_REQUEST['borderou'])){
			$cond .= ' AND sc.borderou='.$_REQUEST['borderou'];
		}
		if(!empty($_REQUEST['centru'])){
			$cond .= ' AND sc.centru='.$_REQUEST['centru'];
		}
		if(!empty($_REQUEST['agent'])){
			$cond .= ' AND sc.curier='.$_REQUEST['agent'];
		}
		if(!empty($_REQUEST['ruta'])){
			$cond .= ' AND sc.ruta='.$_REQUEST['ruta'];
		}
		if(!empty($_REQUEST['tip_scanare'])){
			$cond .= ' AND sc.tip='.$_REQUEST['tip_scanare'];
		}

		if(!empty($_REQUEST['puisori']) && $_REQUEST['puisori'] == 1){
			$cond .= ' AND sc.is_awb = 1';
		}

		$searchOn = false;
		if(isset($_REQUEST['_search'])) $searchOn = $this->Strip($_REQUEST['_search']);
		if ($searchOn == 'true') {
			$searchstr = $this->Strip($_REQUEST['filters']);
			$cond .= $this->constructWhere($searchstr);
		}

		if(!empty($_REQUEST['filters'])){
			require_once 'jqGridService.php';
			$cond .= jqGridService::getJqGridFiltersCondition($_REQUEST['filters']);
			$flag=1;
		}

		$cond = preg_replace("/tip_obj  = '1'/i", "ep.plicuri > 0", $cond);
		$cond = preg_replace("/tip_obj  = '2'/i", "ep.colete > 0", $cond);
		$cond = preg_replace("/tip_obj  = '3'/i", "ep.paleti > 0", $cond);
		$cond = preg_replace("/tip_obj  = '4'/i", "(ep.colete > 0 or ep.paleti > 0)", $cond);

		$cond = preg_replace("/expeditor_centru_cod/i", "IF(cle.zona_id > 0 and clec.id > 0, clec.label, cee.label)", $cond);
		$cond = preg_replace("/expeditor_centru/i", "IF(cle.zona_id > 0 and clec.id > 0, clec.nume, cee.nume)", $cond);
		$cond = preg_replace("/destinatar_centru_cod/i", "IF(cld.zona_id > 0 and cldc.id > 0, cldc.label, ced.label)", $cond);
		$cond = preg_replace("/destinatar_centru/i", "IF(cld.zona_id > 0 and cldc.id > 0, cldc.nume, ced.nume)", $cond);
		
		$query = "SELECT sc.cod as 'CodBare', ep.expeditie as 'Nr NT', ep.tip_exp AS 'Tip Exp.', sc.borderou as 'Borderou',
			CASE  WHEN ep.plicuri > 0 THEN 'Plic' WHEN ep.colete > 0 THEN 'Colete'  WHEN ep.paleti > 0 THEN 'Paleti' ELSE '' END as 'Categorie',
			(ep.plicuri+ep.colete+ep.paleti) as piese, ep.greutate as 'Greutate', ck.denumire as 'Tip Scanare',
			ep.operatiune as 'Status', ru.denumire as 'Ruta',ce.nume as 'Centru', ag.nume_ag as 'Agent',
			cle.nume as 'Expeditor', lce.cod_jd as 'Judet exp.', lce.nume_lc as 'Localitate exp.', cle.adresa as 'Adresa exp.',
			cld.nume as 'Destinatar', lcd.cod_jd as 'Judet dest.', lcd.nume_lc as 'Localitate dest.', cld.adresa as 'Adresa dest.',
			ep.km_livrare as 'Km la livrare', ep.km_preluare as km_preluare, ep.val_km as 'Tarif km',
			IF(ep.mod_plata > 0 , 0 , (ep.valoare_totala_expeditie + ep.tva)) as 'Cash',
			IF(ep.tip_exp = 3, ep.valoare_asigurata + ep.ramburs, ep.ramburs) as 'Asig/Ramb',
			ep.data_expeditie as 'Colectare', sc.data as 'Scanare', u.USER as 'User', IF(ep.referire > 0, ep.referire, '') as 'Exp. Initiala',
			IF(ep.referire > 0, CASE bref.tip_plata WHEN 0 THEN 'cash'  WHEN 1 THEN 'bo' WHEN 2 THEN 'cec' WHEN 3 THEN 'cont' ELSE '' END , '') as 'Tip ramburs',
			IF(cld.zona_id > 0 and cldc.id > 0, cldc.label, ced.label) as 'Centru dest',
			IF(cle.zona_id > 0 and clec.id > 0, clec.label, cee.label) as 'Centru exp'
			FROM  scanari_coduri as sc use index (data)
			LEFT JOIN rute as ru ON sc.ruta = ru.id
			LEFT JOIN agenti as ag ON sc.curier = ag.cod_ag
			LEFT JOIN exp_prelucrate as ep ON (sc.expeditie = ep.expeditie and ep.anulata = 0)
			LEFT JOIN clienti as cle ON cle.cod_cl = ep.expeditor_id
			LEFT JOIN clienti as cld ON cld.cod_cl = ep.destinatar_id
			LEFT JOIN zones clez ON clez.id = cle.zona_id
        	LEFT JOIN centre clec on clec.id = clez.centru_id
			LEFT JOIN zones cldz ON cldz.id = cld.zona_id
        	LEFT JOIN centre cldc on cldc.id = cldz.centru_id
			LEFT JOIN localitati as lce ON cle.cod_lc = lce.cod_lc
			LEFT JOIN localitati as lcd ON cld.cod_lc = lcd.cod_lc
			left join centre cee ON cee.id = lce.cod_centru
			left join centre ced ON ced.id = lcd.cod_centru
			LEFT JOIN exp_prelucrate as bref ON (ep.referire = bref.expeditie and bref.anulata = 0)
			LEFT JOIN checkpoints as ck ON sc.tip = ck.id
			LEFT JOIN centre as ce ON sc.centru = ce.id
			LEFT JOIN users as u ON sc.user = u.id
			WHERE {$cond}";
		//print_r($query);die;
		$sql = $this->db->Query($query, [], false);
		$options = new Options(
    		SHOULD_ADD_BOM: false,
		);
		$writer = new Writer($options);
		$writer->openToBrowser("raport_export_" . date("Y-m-d") . ".csv");
		$i = 0;
		ob_start();
		if($sql) {
			if($row = $sql->fetch(PDO::FETCH_ASSOC)) {
				$row_header = Row::fromValues(array_keys($row));
				$writer->addRow($row_header);
				$row['Tip Exp.'] = ExpeditieDto::TIP_EXP[$row['Tip Exp.']] ?? "unknown";
				$row_values = Row::fromValues(array_values($row));
				$writer->addRow($row_values);
			}
			while($row = $sql->fetch(PDO::FETCH_ASSOC)) {
				$row['Tip Exp.'] = ExpeditieDto::TIP_EXP[$row['Tip Exp.']] ?? "unknown";
				$row_in = Row::fromValues(array_values($row));
				$writer->addRow($row_in);
				$i++;
				if($i % 1000 === 0) {
					ob_flush();
				}
			}
		}
		ob_end_flush();
		$writer->close();
		exit();
	}


	function Print_ListeExpeditiiIstoricScanare(){
		$vars=[];

		$cond1 = $cond2 = '1=1';
		$today = date('Y-m-d');
		if(isset($_REQUEST['data_start']) && isset($_REQUEST['data_final'])){
			try {
					$data_start = DateTime::createFromFormat('d.m.Y H:i', $_REQUEST['data_start']);
					$data_final = DateTime::createFromFormat('d.m.Y H:i', $_REQUEST['data_final']);
					$data_start = $data_start->format('Y-m-d H:i:s');
					$data_final = $data_final->format('Y-m-d H:i:s');
			}
			catch (Exception $e){
					$data_start = $today.' 00:00:00';
	    			$data_final = $today.' 23:59:59';
			}
	    }
	    else
	    {
	    	$data_start = $today.' 00:00:00';
	    	$data_final = $today.' 23:59:59';
	    }
	    $cond1 .= " AND sc.data >= '".$data_start."' AND sc.data <= '".$data_final."'";
		$cond2 .= " AND sc.data >= '".$data_start."' AND sc.data <= '".$data_final."'";


		if(!empty($_REQUEST['puisori']) && $_REQUEST['puisori'] == 1 && empty($_REQUEST['agent'])){
			$cond1 .= ' AND sc.is_awb = 1';
			$cond2 .= ' AND sc.is_awb = 1';
		}

		$vars['borderou'] = '';
		if(!empty($_REQUEST['borderou'])) {
			$b = intval($_REQUEST['borderou']);
			$cond1 .= " AND sc.borderou=".$b;
			$cond2 .= " AND sc.borderou=".$b;
			$vars['borderou'] = $b;
		}

		$vars['curier'] = '';
		$vars['centru'] = '';
		$vars['ruta'] = '';

		if(!empty($_REQUEST['tip_scanare'])){
			$cond1 .= ' AND sc.tip = '.intval($_REQUEST['tip_scanare']);
			$cond2 .= ' AND sc.tip = '.intval($_REQUEST['tip_scanare']);
		}

		if(!empty($_REQUEST['categorie'])){
			$categ = intval($_REQUEST['categorie']);
			switch($categ ) {
				case 2 : $cond1 .= " AND ep.plicuri > 0";
					break;
				case 3 : $cond1 .= " AND (ep.colete > 0 OR ep.paleti > 0)";
					break;
			}
		}

		if(!empty($_REQUEST['centru'])){
			$centru = intval($_REQUEST['centru']);
			$cond1 .= " AND sc.centru = ".$centru;
			$cond2 .= " AND sc.centru = ".$centru;

			$query = "SELECT nume FROM centre WHERE id={$centru} ";
        	$sql = $this->db->QFetchArray($query);
			if(!empty($sql)) $vars['centru'] = strtoupper($sql['nume']);
		}

		if(!empty($_REQUEST['agent'])){
			$curier = intval($_REQUEST['agent']);
			$cond1 .= " AND sc.curier = ".$curier;
			$cond2 .= " AND sc.curier = ".$curier;

			$query = "SELECT nume_ag FROM agenti WHERE cod_ag = {$curier} ";
        	$sql = $this->db->QFetchArray($query);
			if(!empty($sql)) {
				$vars['curier'] = strtoupper($sql['nume_ag']);
				return $this->Print_ListeExpeditiiIstoricScanare_TcpdfCurier($cond1, $cond2, $vars);
			}
		}

		$hasRuta = false;
		if(!empty($_REQUEST['ruta'])){
			$vars['ruta'] = intval($_REQUEST['ruta']);
			$cond1 .= " AND sc.ruta = ".$vars['ruta'];
			$hasRuta = true;
		}

		if(!empty($_REQUEST['filters'])){
			require_once 'jqGridService.php';
			$cond1 .= jqGridService::getJqGridFiltersCondition($_REQUEST['filters']);
			$flag=1;
		}

		return $this->Print_ListeExpeditiiIstoricScanare_HtmlCentru($cond1,$vars,$hasRuta);
	}

	function Print_ListeExpeditiiIstoricScanare_TcpdfCurier($cond1, $cond2, $vars){
		require_once 'BorderouLivrarePdf.php';

		$query = "
			(SELECT
		sc.id as idd, sc.expeditie as exp, sc.borderou as borderou, count(sc.cod) as piese_scanate,
		ep.expeditie as expeditie, ep.data_expeditie, ep.plicuri, ep.colete, ep.paleti, ep.greutate,
		ep.referire, ep.valoare_asigurata, ep.ramburs, ep.tip_plata, ep.valoare_totala_expeditie, ep.tva,
		cld.nume as destinatar, lcd.nume_lc as destinatar_localitate,
		cld.adresa as destinatar_adresa,
		ep.destinatar_id, ep.platitor_id, ep.observatii, ep.detalii_doc,
		ep.destinatar_contact, ep.destinatar_telefon,
		ep.mod_plata, ep.ret_doc, ep.ret_nt, ep.ret_colet, ep.ret_amb, cn.extrainfo, cn.largeinfo
		FROM
				scanari_coduri as sc
		LEFT JOIN
				exp_prelucrate as ep ON (sc.expeditie = ep.expeditie and ep.anulata = 0)
		LEFT JOIN
				clienti cld ON ep.destinatar_id = cld.cod_cl
		LEFT JOIN
				localitati lcd on lcd.cod_lc = cld.cod_lc
		LEFT JOIN
				exp_nc as cn on ep.expeditie = cn.expeditie
		WHERE
				{$cond1}
		GROUP BY
				sc.expeditie)
		UNION
		(SELECT
				sc.id as idd, sc.cod as exp, sc.borderou as borderou, 1 as piese_scanate,
				sc.expeditie, NULL as data_expeditie, NULL as plicuri, NULL as colete, NULL as paleti, NULL as greutate,
				NULL as referire, NULL as valoare_asigurata, NULL as ramburs, NULL as tip_plata, NULL as valoare_totala_expeditie, NULL as tva,
				NULL as destinatar, NULL as destinatar_localitate,
				NULL as destinatar_adresa,
				NULL as destinatar_id, NULL as platitor_id, NULL as observatii, NULL as detalii_doc,
				NULL as destinatar_contact, NULL as destinatar_telefon,
				NULL as mod_plata, NULL as ret_doc, NULL as ret_nt, NULL as ret_colet, NULL as ret_amb, NULL as extrainfo, NULL as largeinfo
		FROM
				scanari_coduri as sc
		WHERE
				{$cond2} AND sc.is_awb = 1
		)
		ORDER BY 17;
		";

		// error_log($query);
        $sql = $this->db->QFetchRowArray($query);
		//print_r($sql);die;
		$vars['total_piese_scanate'] = 0;
		$vars['total_greutate'] = 0.00;
		$vars['total_ramburs'] = 0.00;
		$vars['total_expeditii'] = 0;
		$total_expeditii = 0;

		$today = new DateTime("now");
		$vars['data'] = $today->format('d.m.Y / H:i');
		$filename = 'Borderou-Livrare-';
		if(isset($vars['curier'])) $filename .= $vars['curier'].'-';
		else $vars['curier'] = '';
		if(!isset($vars['centru'])) $vars['centru'] = '';
		$filename .= $today->format('d-m-Y').'.pdf';

		if (!empty($sql)) {
			foreach ($sql as $key => $row) {
            	$vars['total_piese_scanate'] += $row['piese_scanate'];
            	if(!empty($row['expeditie'])) { //expeditie cu key entry
            		$vars['total_expeditii']++;
					if(!empty($row['greutate'])) $vars['total_greutate'] += $row['greutate'];
					if($row['ramburs'] != '0.00'  && $row['ramburs'] != null) $vars['total_ramburs'] += $row['ramburs'];
				}
				else {
					//expeditii + puisori fara key entry
					if(ExpeditieDto::isAwb($row['exp']) || ExpeditieDto::isCmnAwb($row['exp'])) { //expeditie fara key entry
						$vars['total_expeditii']++;
					}
				}
			}
			$vars['total_greutate'] = round($vars['total_greutate'],2);
			$vars['total_ramburs'] = round($vars['total_ramburs'],2);
			$pdf = new BorderouLivrarePdf($vars);
			// add a page
			$pdf->AddPage();
            $vars['expeditii'] = [];
            foreach ($sql as $key => $row) {
            	if(!empty($row['expeditie'])) { //expeditie cu key entry
                    $vars['expeditii'][] = $row['expeditie'];
            		$total_expeditii++;
            		$var=[];
            		$var['expeditie'] = intval($row['exp']);
					//nr de piese
					$var['tip_obj'] = '';
					$var['piese'] = 1;
					if(!empty($row['plicuri'])) {$var['piese'] = $row['plicuri']; $var['tip_obj'] = 'Plic';}
					else if(!empty($row['colete'])) {$var['piese'] = $row['colete']; $var['tip_obj'] = 'Colet';}
					else if(!empty($row['paleti'])) {$var['piese'] = $row['paleti']; $var['tip_obj'] = 'Palet';}
					$var['piese_scanate'] = intval($row['piese_scanate']);

					$var['greutate'] = '';
					if(!empty($row['greutate'])) {
						$var['greutate'] = $row['greutate'];
					}

					//detalii expeditie
					$var['ramburs'] = $var['valoare_asigurata'] = $var['referire'] = $var['expeditie'] = $var['tip_plata'] = '';
					$var['expeditie'] = '';
					if(!empty($row['exp'])) $var['expeditie'] = $row['exp'];
					$var['referire'] = '';
					if(!empty($row['referire'])) $var['referire'] = $row['referire'];
					$var['valoare_asigurata'] = '';
					if($row['valoare_asigurata'] != '0.00' && $row['valoare_asigurata'] != null) $var['valoare_asigurata'] = $row['valoare_asigurata'];
					$var['ramburs'] = '';
					$var['tip_plata'] = '';
					if($row['ramburs'] != '0.00'  && $row['ramburs'] != null) {
						$var['ramburs'] = $row['ramburs'];
						$var['tip_plata'] = 'cash';
						if($row['tip_plata']==1) $var['tip_plata'] = 'bo';
						else if($row['tip_plata']==2) $var['tip_plata'] = 'cec';
					}
					if(!empty($row['detalii_doc'])) $var['detalii_doc'] = $row['detalii_doc'];
					if(!empty($row['observatii'])) $var['observatii'] = $row['observatii'];

					//destinatar
					if($row['destinatar_contact'] == 'Persoana' || empty($row['destinatar_contact'])) $row['destinatar_contact'] = 'NA';
					$var['client'] = '';
					if(!empty($row['destinatar']))
						$var['client'] = $row['destinatar'];
					$var['adresa'] = '';
					if(!empty($row['destinatar_localitate']) && !empty($row['destinatar_adresa']))
						$var['adresa'] = $row['destinatar_localitate'].', '.$row['destinatar_adresa'];
					else if(!empty($row['destinatar_localitate']))
						$var['adresa'] = $row['destinatar_localitate'];
					else if(!empty($row['destinatar_adresa']))
						$var['adresa'] = $row['destinatar_adresa'];
					$var['contact'] = '';
					if(!empty($row['destinatar_contact']) && !empty($row['destinatar_telefon']))
						$var['contact'] = $row['destinatar_contact'].', '.$row['destinatar_telefon'];
					else if(!empty($row['destinatar_contact']))
						$var['contact'] = $row['destinatar_contact'];
					else if(!empty($row['destinatar_telefon']))
						$var['contact'] = $row['destinatar_telefon'];

					$var['ret_nt'] = '';
					if(!empty($row['ret_nt']) || !empty($row['extrainfo'])) $var['ret_nt'] = 'RN';
					$var['ret_doc'] = '';
					if(!empty($row['ret_doc'])) $var['ret_doc'] = 'RD';
					$var['ret_amb'] = '';
					if(!empty($row['ret_amb'])) $var['ret_amb'] = 'RA';
					$var['ret_colet'] = '';
					if(!empty($row['ret_colet'])) $var['ret_colet'] = 'RC';
					$var['numerar'] = '';
					if($row['destinatar_id'] == $row['platitor_id'] && empty($row['mod_plata']) && $row['valoare_totala_expeditie'] != '0.00' && $row['valoare_totala_expeditie'] != null) $var['numerar'] = round($row['valoare_totala_expeditie']+$row['tva'],2);
					$pdf->makeTR($var);
					if($total_expeditii%10==0)
					{ $pdf->SetTopMargin(30);	$pdf->AddPage();}
				}
				else {
					//expeditii + puisori fara key entry
					if(ExpeditieDto::isAwb($row['exp']) || ExpeditieDto::isCmnAwb($row['exp'])) { //expeditie fara key entry
						$total_expeditii++;
						$var=[];
            			$var['expeditie'] = intval($row['exp']);
                        $vars['expeditii'][] = $var['expeditie'];
            			$var['piese'] = 0;
            			$var['piese_scanate'] = 1;
            			$var['tip_obj'] = '';
						$var['greutate'] = '';
						$var['ramburs'] = '';
						$var['ramburs'] = $var['valoare_asigurata'] = $var['referire'] = $var['expeditie'] = $var['tip_plata'] = '';
						$var['expeditie'] = '';
						if(!empty($row['exp'])) $var['expeditie'] = $row['exp'];
						$var['client'] = '';
						$var['adresa'] = '';
						$var['contact'] = 'NA';
						$var['ret_nt'] = $var['ret_doc'] = $var['numerar'] = $var['ret_amb'] = $var['ret_colet'] = '';

						$pdf->makeTR($var);
						if($total_expeditii%12==0)
						{ $pdf->SetTopMargin(30);	$pdf->AddPage();}
					}
				}
            }
			$pdf->setPrintHeader(false);
			$pdf->addPage();
			$pdf->addProcessVerbal($vars);

			$pdf->lastPage();

			//I: send the file inline to the browser.
			$pdf->Output($filename,'I');
        }
	    exit;
	}

	function Print_ListeExpeditiiIstoricScanare_HtmlCentru($cond,$vars,$hasRuta = false){
		$vars['data'] = date('d/m/Y');
		$coduri_exp = [];//folosesc pentru a determina ce expeditii am afisat

		$query = "SELECT sc.cod,
			ep.expeditie, ep.tip_exp, ep.data_expeditie, ep.plicuri, ep.colete, ep.paleti, ep.greutate,
			cle.nume as expeditor,lce.nume_lc as expeditor_localitate, cld.nume as destinatar,lcd.nume_lc as destinatar_localitate,
			cld.adresa as destinatar_adresa, ep.referire, ep.valoare_asigurata, ep.ramburs,
			ep.tip_plata, ep.valoare_totala_expeditie, ep.tva, ep.moneda, ep.cod_expeditie, ep.detalii_doc,
			ep.destinatar_contact, ep.destinatar_telefon, ep.observatii,
			ep.mod_plata, ep.ret_doc, ep.ret_nt, ep.ret_amb, ep.ret_colet, ru.kg_max
			FROM  scanari_coduri as sc
			LEFT JOIN exp_prelucrate as ep ON (sc.expeditie = ep.expeditie and ep.anulata = 0)
			LEFT JOIN clienti cle ON ep.expeditor_id = cle.cod_cl
			LEFT JOIN localitati lce on lce.cod_lc = cle.cod_lc
			LEFT JOIN clienti cld ON ep.destinatar_id = cld.cod_cl
			LEFT JOIN localitati lcd on lcd.cod_lc = cld.cod_lc
			LEFT JOIN rute ru on sc.ruta = ru.id
			WHERE {$cond}
			GROUP BY
			  CASE
			    WHEN ep.expeditie IS NOT NULL THEN ep.expeditie
			    ELSE sc.expeditie
			  END
			ORDER BY sc.id ASC";

		//print_r($query);die;
        $sql = $this->db->QFetchRowArray($query);

        $exp=0;
        $total = 0;
		$rest = 0;
        $total_piese = 0;
		$total_greutate = 0;
		$ruta_kg_max = 0;
		$exp_pag = 46;
		$pag=0;
		$items='';
		$total_valoare_asigurata = 0.00;

        if (!empty($sql)) {
			$total = round(count($sql)/$exp_pag,0);
			$rest = round(count($sql)/$exp_pag,2);
			if($rest/100>0) $total++;

            foreach ($sql as $key => $row) {
				if(!empty($row['kg_max'])) $ruta_kg_max = $row['kg_max'];
            	$row['piese'] = 0;
            	if(!empty($row['plicuri'])){
					$row['categorie'] = 'Plicuri';
					$row['piese'] = $row['plicuri'];
				}
				if(!empty($row['colete'])){
					$row['categorie'] = 'Colete';
					$row['piese'] = $row['colete'];
				}
				if(!empty($row['paleti'])){
					$row['categorie'] = 'Paleti';
					$row['piese'] = $row['paleti'];
				}

				$total_piese += $row['piese'];
				$total_greutate += $row['greutate'];
				if($row['tip_exp'] == 3 && !empty($row['valoare_asigurata']))
					$total_valoare_asigurata += $row['valoare_asigurata'];

				$row['borderou'] = $vars['borderou'];
				$row['ruta'] = $vars['ruta'];
				$row['data'] = date('d/m/Y');

				if(strlen($row['expeditor'])>15) $row['expeditor'] = substr($row['expeditor'], 0, 15);
				if(strlen($row['destinatar'])>15) $row['destinatar'] = substr($row['destinatar'], 0, 15);

				if(!empty($row['expeditie'])) $row['cod'] = $row['expeditie'];

				$exp++;
				$var['no'] = $exp;
				$items .= $this->Parse($this->page_prefix . 'pdf_expeditii_disponibile_iesire_row.html', $row);

				if($exp%$exp_pag==0) {
					$pag++;
					$items .= '<div style="text-align:right; height: 10mm; font-size: 13pt; line-height: 10mm;">Pagina <strong>'.$pag.'</strong> din <strong>'.$total.'</strong></div><div class="page-break"></div>';//pagina noua
					$items .= $this->Parse($this->page_prefix . 'pdf_expeditii_disponibile_iesire_row_top.html', $row);
				}

            }
			$vars['total_expeditii']=$key+1;
        }

		$vars['data'] = date('d/m/Y');
        $vars['items'] = $items;
		$vars['total_piese']=$total_piese;
		$vars['total_expeditii']=$exp;
		$vars['total_greutate'] = $total_greutate;
		if($hasRuta && $ruta_kg_max > 100 && $ruta_kg_max < $total_greutate)
			$vars['total_greutate'] = $ruta_kg_max - 100 + rand(3, 96);
		$vars['total_valoare_asigurata']=number_format((float)$total_valoare_asigurata, 2, '.', '');
		$vars['total'] = $total;
		$vars['ruta'] = $vars['ruta'];

        $html = $this->Parse($this->page_prefix . 'pdf_expeditii_disponibile_iesire.html', $vars);


		print_r($html);die;
	}


//istoric scanari
	function FaraKey() {
        $this->vars['title_page'] = 'Fara key entry';
        $vars = [];
		$vars['data_start'] = date('d.m.Y').' 00:00';
		$vars['data_final'] = date('d.m.Y').' 23:59';

        return $this->Parse($this->page_prefix . 'fara_key.html', $vars);
    }

    function JSON_FaraKey() {
 		$responce = new StdClass();
		 $today = date('Y-m-d');
		 $data_start = $today.' 00:00:00';
		 $data_final = $today.' 23:59:59';

		 if(isset($_REQUEST['data_start']) && isset($_REQUEST['data_final'])){
			try {
					$data_start = DateTime::createFromFormat('d.m.Y H:i', $_REQUEST['data_start']);
					$data_final = DateTime::createFromFormat('d.m.Y H:i', $_REQUEST['data_final']);
					$data_start = $data_start->format('Y-m-d H:i:s');
					$data_final = $data_final->format('Y-m-d H:i:s');
			}
			catch (Exception $e){
					$data_start = $today.' 00:00:00';
					$data_final = $today.' 23:59:59';
			}
		 }
		 else
		 {
			 $data_start = $today.' 00:00:00';
			 $data_final = $today.' 23:59:59';
		 }

	    $cond = " sc.data >= '".$data_start."' AND sc.data <= '".$data_final."'";

		$searchOn = false;
		if(isset($_REQUEST['_search'])) $searchOn = $this->Strip($_REQUEST['_search']);
        if ($searchOn == 'true') {
            $searchstr = $this->Strip($_REQUEST['filters']);
            $cond .= $this->constructWhere($searchstr);
        }

		$page = intval($_REQUEST['page'] ?? 1);
		$limit = intval($_REQUEST['rows'] ?? 500);
		$sidx = trim($this->sanitize($_REQUEST['sidx'] ?? 1));
		$sord = trim($this->sanitize($_REQUEST['sord'] ?? 'asc'));

    	$query = "SELECT COUNT(DISTINCT(sc.cod)) as nr
			FROM  scanari_coduri as sc
			LEFT JOIN exp_prelucrate as ep ON ep.expeditie = sc.cod
			LEFT JOIN centre as ce ON ce.id = sc.centru
			LEFT JOIN users as u ON u.id = sc.user
			left join checkpoints as ck ON ck.id = sc.tip
			left join client_expeditii as cep on cep.expeditie = sc.cod
			left join clienti cle on cep.expeditor = cle.cod_cl
			left join localitati lce on lce.cod_lc = cle.cod_lc
			left join centre cea on cea.id = lce.cod_centru
			WHERE {$cond} AND (ep.expeditie IS NULL or (ep.expeditie is not null and (IFNULL(ep.expeditor_id, 0) = 0 or IFNULL(ep.destinatar_id,0) = 0)))
			and INSTR(sc.cod, '-') = 0
			and CHAR_LENGTH(sc.cod) in (7,8,9,10)";
        $result = $this->db->QFetchArray($query);
        $count = !empty($result['nr']) ? $result['nr'] : 0;

        if( $count >0 ) {
            $total_pages = ceil($count/$limit); }
        else { $total_pages = 0; }
        if ($page > $total_pages) $page=$total_pages;
        if ($limit<0) $limit = 0;
        $start = $limit*$page - $limit; // do not put $limit*($page - 1)
        if ($start<0) $start = 0;

		//and sc.cod regexp '^([89][0-9]{7}|(290|291)[0-9]{6})$'
		$query = "SELECT GROUP_CONCAT(ce.nume order by sc.data asc SEPARATOR ', ') as centre,
			GROUP_CONCAT(u.user order by sc.data asc SEPARATOR ', ') as utilizatori,
			GROUP_CONCAT(ck.denumire order by sc.data asc SEPARATOR ', ') as checkpointuri,
			GROUP_CONCAT(sc.data order by sc.data asc SEPARATOR ', ') as data_scanari,
			sc.cod as cod, cle.nume as expeditor, cea.nume as centru_awb, eco.folder as folder, count(sc.cod) as scan

			FROM  scanari_coduri as sc
			LEFT JOIN exp_prelucrate as ep ON ep.expeditie=sc.cod
			LEFT JOIN centre as ce ON ce.id = sc.centru
			LEFT JOIN users as u ON u.id = sc.user
			left join checkpoints as ck ON ck.id = sc.tip
			left join client_expeditii as cep on cep.expeditie = sc.cod
			left join clienti cle on cep.expeditor = cle.cod_cl
			left join localitati lce on lce.cod_lc = cle.cod_lc
			left join centre cea on cea.id = lce.cod_centru
			left join exp_confirmari eco on eco.expeditie = sc.cod
			WHERE {$cond} AND (ep.expeditie IS NULL or (ep.expeditie is not null and (IFNULL(ep.expeditor_id, 0) = 0 or IFNULL(ep.destinatar_id,0) = 0)))
			and INSTR(sc.cod, '-') = 0
			and CHAR_LENGTH(sc.cod) in (7,8,9,10)
			group by sc.cod
			ORDER BY " . $sidx . " " . $sord . " LIMIT " . $start . " , " . $limit . ";";
		// error_log($query);
        $sql = $this->db->QFetchRowArray($query);
        if (!empty($sql)) {
            foreach ($sql as $key => $row) {
                $responce->rows[$key]['id'] = $row['cod'];
                if(!empty($row['folder'])) $row['cod'] = '<a href="javascript:;" onclick="opDownloadConfirmare('.intval($row['cod']).');">'.$row['cod'].'</a>';
                $responce->rows[$key]['cell'] = array($row['cod'],$row['scan'],$row['expeditor'],$row['centru_awb'],$row['data_scanari'],$row['centre'],$row['utilizatori'],$row['checkpointuri']);

                //print_r($row);
            }
        }
		$responce->userdata['centru'] =  'Total: '.$count.' Exp.';

        $responce->page = $page;
        $responce->total = $total_pages;
        $responce->records = $count;
        return json_encode($responce);
    }

    function ExportExpeditiiFaraKeyCsv() {
		$arr=[];
		$arr[0][1] = 'CodBare';
        $arr[0][2] = 'Scan';
		$arr[0][3] = 'Expeditor';
		$arr[0][4] = 'Centru alocare';
		$arr[0][5] = 'Date';
		$arr[0][6] = 'Centre';
		$arr[0][7] = 'Utilizatori';
		$arr[0][8] = 'Ckeckpoints';

		$today = date('Y-m-d');

		$data_start = $today.' 00:00:00';
		$data_final = $today.' 23:59:59';

		if(isset($_REQUEST['data_start']) && isset($_REQUEST['data_final'])){
			try {
					$data_start = DateTime::createFromFormat('d.m.Y H:i', $_REQUEST['data_start']);
					$data_final = DateTime::createFromFormat('d.m.Y H:i', $_REQUEST['data_final']);
					$data_start = $data_start->format('Y-m-d H:i:s');
					$data_final = $data_final->format('Y-m-d H:i:s');
			}
			catch (Exception $e){
					$data_start = $today.' 00:00:00';
					$data_final = $today.' 23:59:59';
			}
		}
		else
		{
			$data_start = $today.' 00:00:00';
			$data_final = $today.' 23:59:59';
		}

	    $cond = " sc.data>='".$data_start."' AND sc.data<='".$data_final."'";

		$query = "SELECT GROUP_CONCAT(ce.nume order by sc.data asc SEPARATOR ', ') as centre,
			GROUP_CONCAT(u.user order by sc.data asc SEPARATOR ', ') as utilizatori,
			GROUP_CONCAT(ck.denumire order by sc.data asc SEPARATOR ', ') as checkpointuri,
			GROUP_CONCAT(sc.data order by sc.data asc SEPARATOR ', ') as data_scanari,
			sc.cod as cod, cle.nume as expeditor, cea.nume as centru_awb, eco.folder as folder, count(sc.cod) as scan

			FROM  scanari_coduri as sc
			LEFT JOIN exp_prelucrate as ep ON ep.expeditie = sc.cod
			LEFT JOIN centre as ce ON ce.id = sc.centru
			LEFT JOIN users as u ON u.id = sc.user
			left join checkpoints as ck ON ck.id = sc.tip
			left join client_expeditii as cep on cep.expeditie = sc.cod
			left join clienti cle on cep.expeditor = cle.cod_cl
			left join localitati lce on lce.cod_lc = cle.cod_lc
			left join centre cea on cea.id = lce.cod_centru
			left join exp_confirmari eco on eco.expeditie = sc.cod
			WHERE {$cond} AND (ep.expeditie IS NULL or (ep.expeditie is not null and (IFNULL(ep.expeditor_id, 0) = 0 or IFNULL(ep.destinatar_id,0) = 0)))
			and INSTR(sc.cod, '-') = 0
			and CHAR_LENGTH(sc.cod) in (7,8,9,10)
			group by sc.cod";
		//print_r($query);die;
        $sql = $this->db->QFetchRowArray($query);
        if (!empty($sql)) {
            foreach ($sql as $key => $row) {
                $arr[($key+1)][1] = $row['cod'];
                $arr[($key+1)][2] = $row['scan'];
                $arr[($key+1)][3] = $row['expeditor'];
                $arr[($key+1)][4] = $row['centru_awb'];
				$arr[($key+1)][5] = $row['data_scanari'];
				$arr[($key+1)][6] = $row['centre'];
				$arr[($key+1)][7] = $row['utilizatori'];
				$arr[($key+1)][8] = $row['checkpointuri'];
            }
        }

    	$this->download_send_headers("fara_key_export_" . date("Y-m-d") . ".csv");
		echo $this->array2csv($arr);
		die;
    }

	//istoric scanari
	function DiferenteRute() {
        $this->vars['title_page'] = 'Diferente Rute';
        $vars = [];
		$vars['data_start'] = date('d.m.Y');
		$vars['data_final'] = date('d.m.Y');
        return $this->Parse($this->page_prefix . 'diferente_rute.html', $vars);
    }

	function Expeditii_DiferenteRute($tip=1) {
		$cond1 = '1=1';
		$cond2 = '1=1';
		$cond3 = '1=1';

		$centru = intval($_REQUEST['centru'] ?? 0);

		if($centru == 0 || empty($_REQUEST['data_final']))
			return [];

		$data1 = $this->TransformDate($_REQUEST['data_final'])." 00:00:00";
		$data2 = $this->TransformDate($_REQUEST['data_final']);

		$cond1 .= " AND sc.data > '".$data1."'";
		$cond2 .= " AND ep.data_expeditie='".$data2."'";
		$cond3 .= " AND sc.data > '".$data1."'";

		$cond1 .= ' AND sc.centru = '.$centru;
		$cond2 .= ' AND IF(cld.zona_id > 0 and cldc.id > 0, cldc.id, lcd.cod_centru) = '.$centru;



		$categorie = $_REQUEST['categorie'];
		if($categorie==1) {$cond2 .= ' AND 1=1';}
		else if($categorie==2) $cond2 .= ' AND ep.plicuri > 0';
		else if($categorie==3) $cond2 .= ' AND (ep.colete > 0 or ep.paleti > 0)';

		$searchOn = false;
		if(isset($_REQUEST['_search'])) $searchOn = $this->Strip($_REQUEST['_search']);
        if ($searchOn == 'true') {
            $searchstr = $this->Strip($_REQUEST['filters']);
			$jsona = json_decode($searchstr, true);
			$rules = $jsona['rules'];
			foreach ($rules as $key => $val) {
			    $field = $val['field'];
			    $val = $val['data'];
			    if (isset($val)) {
			   		switch ($field) {
			        	case 'tip':
			                break;
			            default :
							$cond2 .= " AND ".$field." LIKE '" . strtoupper(addslashes($val)) . "%'";
							break;
					}
				}
			}
        }

		$exp = [];
		$query = "
			SELECT
			ep.cod_expeditie, ep.expeditie, ep.data_expeditie, ep.tip_exp, ep.tip_plata, ep.mod_plata,
			ep.plicuri, ep.colete, ep.paleti,
			cle.nume as expeditor, cld.nume as destinatar,
			lce.nume_lc as expeditor_localitate, lcd.nume_lc as destinatar_localitate,
			IF(cle.zona_id > 0 and clec.id > 0, clec.nume, cee.nume) as expeditor_centru, 
			IF(cld.zona_id > 0 and cldc.id > 0, cldc.nume, ced.nume) as destinatar_centru,
			sc.cod as codbare
			FROM exp_prelucrate as ep
			left join scanari_coduri sc on sc.expeditie = ep.expeditie
			left join clienti cle on cle.cod_cl = ep.expeditor_id
			left join clienti cld on cld.cod_cl = ep.destinatar_id
			LEFT JOIN zones clez ON clez.id = cle.zona_id
        	LEFT JOIN centre clec on clec.id = clez.centru_id
			LEFT JOIN zones cldz ON cldz.id = cld.zona_id
        	LEFT JOIN centre cldc on cldc.id = cldz.centru_id
			left join localitati lce ON lce.cod_lc = cle.cod_lc
			left join localitati lcd ON lcd.cod_lc = cld.cod_lc
			left join centre cee ON cee.id = lce.cod_centru
			left join centre ced ON ced.id = lcd.cod_centru
			WHERE
			".$cond2."
			and ep.anulata = 0
			GROUP BY
			sc.cod
			";
        $exp = $this->db->QFetchRowArray($query);

		$scanari = [];
		$expeditii = [];
		if (!empty($exp)) {
            foreach ($exp as $key => $row) {
				$query = "
					SELECT sc.*
					FROM scanari_coduri sc
					WHERE ".$cond1." and sc.cod = '".$row['codbare']."'
					LIMIT 1
					";
				$cod = [];
        		$cod = $this->db->QFetchArray($query);

				if(empty($cod)){
					$cod = [];
					$query = "
						SELECT
						sc.centru,sc.data as data_scanare, sc.ruta as ruta_id, ru.denumire as ruta,
						ag.nume_ag as curier, sc.curier as curier_id, ck.abbr as tip, sc.tip as tip_id, sc.borderou
						FROM
						scanari_coduri as sc
						LEFT JOIN rute as ru ON sc.ruta = ru.id
						LEFT JOIN agenti as ag ON sc.curier = ag.cod_ag
						LEFT JOIN checkpoints as ck ON sc.tip = ck.id

						WHERE
						".$cond3." and sc.cod='".$row['codbare']."'

						ORDER BY sc.id desc LIMIT 1
						";
					$cod = [];
			    	$cod = $this->db->QFetchArray($query);
			    	$scanari[$row['codbare']] = $cod;
            		$expeditii[] = $row;
            	}
			}
		}
		if($tip==2)
			return $scanari;
		else
			return $expeditii;
	}

    function JSON_DiferenteRute() {
    	$responce = new StdClass();
		$expeditii = $this->Expeditii_DiferenteRute(1);
		$scanari = $this->Expeditii_DiferenteRute(2);
		$total_piese = 0;
        if (!empty($expeditii)) {
            foreach ($expeditii as $key => $row) {
				$row['piese'] = 0;
				$row['ambalaj'] = '';
				if(!empty($row['plicuri'])) {$row['ambalaj'] = 'Plic';$row['piese'] = $row['plicuri'];}
				if(!empty($row['colete'])) {$row['ambalaj'] = 'Colete';$row['piese'] = $row['colete'];}
				if(!empty($row['paleti'])) {$row['ambalaj'] = 'Paleti';$row['piese'] = $row['paleti'];}

				//$ret = $row['tip_exp'];

                $responce->rows[$key]['id'] = $row['expeditie'];
                $responce->rows[$key]['cell'] = array(
                									$row['codbare'],
                									$row['expeditie'],
                									isset($scanari[$row['codbare']]['tip'])?$scanari[$row['codbare']]['tip']:"",
                									isset($scanari[$row['codbare']]['ruta'])?$scanari[$row['codbare']]['ruta']:"",
                									isset($scanari[$row['codbare']]['curier'])?$scanari[$row['codbare']]['curier']:"",
													isset($scanari[$row['codbare']]['borderou'])?$scanari[$row['codbare']]['borderou']:"",
													$row['tip_exp'],
													$row['tip_plata'],
                									$row['ambalaj'],
                									$row['piese'],
                									strtoupper($row['destinatar']),
                									strtoupper($row['expeditor']),
                									strtoupper($row['destinatar_centru']),
                									strtoupper($row['destinatar_localitate']),
                									strtoupper($row['expeditor_centru']),
                									strtoupper($row['expeditor_localitate']),
                									isset($scanari[$row['codbare']]['data_scanare'])?$scanari[$row['codbare']]['data_scanare']:""
												);
                $total_piese++;
                //print_r($row);
            }
        }
		$responce->userdata['expeditie'] =  '';
		$responce->userdata['tip'] = '';
		$responce->userdata['expeditor'] =  'Total: '.$total_piese.' AWB-uri';

        $responce->page = 1;
        $responce->total = 1;
        $responce->records = $total_piese;
        return json_encode($responce);
    }


	function ExportDiferenteRute(){
		$expeditii = $this->Expeditii_DiferenteRute(1);
		$scanari = $this->Expeditii_DiferenteRute(2);

		$arr=[];
		$arr[0][1] = 'CodBare';
		$arr[0][2] = 'Nr NT';
		$arr[0][3] = 'Scanare';
		$arr[0][4] = 'Ruta';
		$arr[0][5] = 'Curier';
		$arr[0][6] = 'Borderou';
		$arr[0][7] = 'Tip exp.';
		$arr[0][8] = 'Tip plata';
		$arr[0][9] = 'Tip';
		$arr[0][10] = 'Piese';
		$arr[0][11] = 'Destinatar';
		$arr[0][12] = 'Expeditor';
		$arr[0][13] = 'Centru Livrare';
		$arr[0][14] = 'Localitate Livrare';
		$arr[0][15] = 'Centru Colectare';
		$arr[0][16] = 'Localitate Colectare';
		$arr[0][17] = 'Data Scanare';

        if (!empty($expeditii)) {
            foreach ($expeditii as $key => $row) {
				$row['piese'] = 0;
				$row['ambalaj'] = '';
				if(!empty($row['plicuri'])) {$row['ambalaj'] = 'Plic';$row['piese'] = $row['plicuri'];}
				if(!empty($row['colete'])) {$row['ambalaj'] = 'Colete';$row['piese'] = $row['colete'];}
				if(!empty($row['paleti'])) {$row['ambalaj'] = 'Paleti';$row['piese'] = $row['paleti'];}

				$arr[($key+1)][1] = $row['codbare'];
				$arr[($key+1)][2] = $row['expeditie'];
				$arr[($key+1)][3] = isset($scanari[$row['codbare']]['tip'])?$scanari[$row['codbare']]['tip']:"";
				$arr[($key+1)][4] = isset($scanari[$row['codbare']]['ruta'])?$scanari[$row['codbare']]['ruta']:"";
				$arr[($key+1)][5] = isset($scanari[$row['codbare']]['curier'])?$scanari[$row['codbare']]['curier']:"";
				$arr[($key+1)][6] = isset($scanari[$row['codbare']]['borderou'])?$scanari[$row['codbare']]['borderou']:"";
				$arr[($key+1)][7] = ExpeditieDto::TIP_EXP[$row['tip_exp']] ?? "";
				$arr[($key+1)][8] = ExpeditieDto::RBS_TIP_PLATA[$row['tip_plata']] ?? "";
				$arr[($key+1)][9] = $row['ambalaj'];
				$arr[($key+1)][10] = $row['piese'];
				$arr[($key+1)][11] = strtoupper($row['destinatar']);
				$arr[($key+1)][12] = strtoupper($row['expeditor']);
				$arr[($key+1)][13] = strtoupper($row['destinatar_centru']);
				$arr[($key+1)][14] = strtoupper($row['destinatar_localitate']);
				$arr[($key+1)][15] = strtoupper($row['expeditor_centru']);
				$arr[($key+1)][16] = strtoupper($row['expeditor_localitate']);
				$arr[($key+1)][17] = isset($scanari[$row['codbare']]['data_scanare'])?$scanari[$row['codbare']]['data_scanare']:"";
            }
        }
    	$this->download_send_headers("diferente_ruta_" . date("Y-m-d") . ".csv");
		echo $this->array2csv($arr);die;
	}

	//diferente centru
	function DiferenteCentru() {
        $this->vars['title_page'] = 'Diferente Centru';
        $vars = [];
		$vars['data_start'] = date('d.m.Y H:i:s');
		$vars['data_final'] = date('d.m.Y H:i:s');
        return $this->Parse($this->page_prefix . 'diferente_centru.html', $vars);
    }

	function Expeditii_DiferenteCentru() {
		$cond1 = '1=1';
		$cond2 = '1=1';

		if(empty($_REQUEST['centru']) || empty($_REQUEST['data_final']))
			return [];

		if(isset($_REQUEST['data_start']) ){
	        $data1 = $this->TransformDateHours($_REQUEST['data_start']);
			$cond1 .= " AND sc.data >'".$data1."'";
			$cond2 .= " AND data >'".$data1."'";
	    }

		if(isset($_REQUEST['data_final']) ){
	        $data1 = $this->TransformDateHours($_REQUEST['data_final']);
			$cond1 .= " AND sc.data < '".$data1."'";
	    }

		if(!empty($_REQUEST['centru'])){
			$cond1 .= ' AND sc.centru = '.intval($_REQUEST['centru']);
		}

		$categorie = $_REQUEST['categorie'];
		if($categorie==1) {$cond1 .= ' AND 1=1';}
		else if($categorie==2) $cond1 .= ' AND ep.plicuri>0';
		else if($categorie==3) $cond1 .= ' AND (ep.colete>0 or ep.paleti>0)';

		$searchOn = false;
		if(isset($_REQUEST['_search'])) $searchOn = $this->Strip($_REQUEST['_search']);
        if ($searchOn == 'true') {
            $searchstr = $this->Strip($_REQUEST['filters']);
			$jsona = json_decode($searchstr, true);
			$rules = $jsona['rules'];
			foreach ($rules as $key => $val) {
			    $field = $val['field'];
			    $val = $val['data'];
			    if (isset($val)) {
			   		switch ($field) {
			        	case 'tip':
			                break;
			            default :
							$cond1 .= " AND ".$field." LIKE '" . strtoupper(addslashes($val)) . "%'";
							break;
					}
				}
			}
        }

		$query = "SELECT sc.cod, sc.centru, sc.data as data_scanare, sc.ruta as ruta_id, sc.curier as curier_id, sc.tip as tip_id, sc.borderou,
			ru.denumire as ruta,
			ag.nume_ag as curier, ck.abbr as tip,
			ep.expeditie, ep.plicuri, ep.colete, ep.paleti,
			cle.nume as expeditor, cee.nume as expeditor_centru, lce.nume_lc as expeditor_localitate,
			cld.nume as destinatar, ced.nume as destinatar_centru, lcd.nume_lc as destinatar_localitate
			FROM scanari_coduri as sc
			LEFT JOIN rute as ru ON sc.ruta = ru.id
			LEFT JOIN agenti as ag ON sc.curier = ag.cod_ag
			LEFT JOIN checkpoints as ck ON sc.tip = ck.id
			LEFT JOIN exp_prelucrate as ep ON (sc.expeditie = ep.expeditie and ep.anulata = 0)
			left join clienti cle on cle.cod_cl = ep.expeditor_id
			left join clienti cld on cld.cod_cl = ep.destinatar_id
			left join localitati lce ON lce.cod_lc = cle.cod_lc
			left join localitati lcd ON lcd.cod_lc = cld.cod_lc
			left join centre cee ON cee.id = lce.cod_centru
			left join centre ced ON ced.id = lcd.cod_centru
			WHERE ".$cond1." AND (sc.tip = 1 OR sc.tip = 3)
			GROUP BY sc.cod
			ORDER BY sc.id desc";
        $exp = $this->db->QFetchRowArray($query);
		$expeditii = [];
		if (!empty($exp)) {
			$query = "SELECT * FROM scanari_coduri
					WHERE ".$cond2." and cod like :cod AND tip != 1 AND tip != 3
					LIMIT 0,1";
            foreach ($exp as $key => $row) {
				$cod = [];
        		$cod = $this->db->QFetchArray($query, ['cod' => $row['cod']]);
				if(empty($cod)){
			    	//$flag_s++;
            		$expeditii[] = $row;
            	}
			}
		}
		return $expeditii;
	}

    function JSON_DiferenteCentru() {
    	$responce = new StdClass();
		$expeditii = $this->Expeditii_DiferenteCentru();
		$total_piese = 0;
        if (!empty($expeditii)) {
            foreach ($expeditii as $key => $row) {
            	if(empty($row['expeditie'])) $row['expeditie'] = 'unknown';
				$row['tip_obj'] = 'NA';
				$row['piese'] = 0;
				if(!empty($row['plicuri'])) {$row['tip_obj'] = 'Plic';$row['piese'] = 1;}
				if(!empty($row['colete'])) {$row['tip_obj'] = 'Colete';$row['piese'] = $row['colete'];}
				if(!empty($row['paleti'])) {$row['tip_obj'] = 'Paleti';$row['piese'] = $row['paleti'];}

                $responce->rows[$key]['id'] = $row['expeditie'];
                $responce->rows[$key]['cell'] = array(
                									$row['cod'],
                									$row['expeditie'],
                									$row['tip_obj'],
                									$row['piese'],
                									strtoupper($row['destinatar']),
                									strtoupper($row['expeditor']),
                									strtoupper($row['destinatar_centru']),
                									strtoupper($row['destinatar_localitate']),
                									strtoupper($row['expeditor_centru']),
                									strtoupper($row['expeditor_localitate']),
                									$row['data_scanare']
												);
                $total_piese++;
                //print_r($row);
            }
        }
		$responce->userdata['expeditie'] =  '';
		$responce->userdata['tip'] = '';
		$responce->userdata['expeditor'] =  'Total: '.$total_piese.' AWB-uri';

        $responce->page = 1;
        $responce->total = 1;
        $responce->records = $total_piese;
        return json_encode($responce);
    }


	function ExportDiferenteCentru(){
		$expeditii = $this->Expeditii_DiferenteCentru();

		$arr=[];
		$arr[0][1] = 'CodBare';
		$arr[0][2] = 'Nr NT';
		$arr[0][8] = 'Tip';
		$arr[0][9] = 'Piese';
		$arr[0][10] = 'Destinatar';
		$arr[0][11] = 'Expeditor';
		$arr[0][12] = 'Centru Livrare';
		$arr[0][13] = 'Localitate Livrare';
		$arr[0][14] = 'Centru Colectare';
		$arr[0][15] = 'Localitate Colectare';
		$arr[0][16] = 'Data Scanare';

        if (!empty($expeditii)) {
            foreach ($expeditii as $key => $row) {
				if(empty($row['expeditie'])) $row['expeditie'] = 'unknown';
				$row['tip_obj'] = 'NA';
				$row['piese'] = 0;
				if(!empty($row['plicuri'])) {$row['tip_obj'] = 'Plic';$row['piese'] = 1;}
				if(!empty($row['colete'])) {$row['tip_obj'] = 'Colete';$row['piese'] = $row['colete'];}
				if(!empty($row['paleti'])) {$row['tip_obj'] = 'Paleti';$row['piese'] = $row['paleti'];}

				$arr[($key+1)][1] = $row['cod'];
				$arr[($key+1)][2] = $row['expeditie'];
				$arr[($key+1)][8] = $row['tip_obj'];
				$arr[($key+1)][9] = $row['piese'];
				$arr[($key+1)][10] = strtoupper($row['destinatar']);
				$arr[($key+1)][11] = strtoupper($row['expeditor']);
				$arr[($key+1)][12] = strtoupper($row['destinatar_centru']);
				$arr[($key+1)][13] = strtoupper($row['destinatar_localitate']);
				$arr[($key+1)][14] = strtoupper($row['expeditor_centru']);
				$arr[($key+1)][15] = strtoupper($row['expeditor_localitate']);
				$arr[($key+1)][16] = $row['data_scanare'];
            }
        }
    	$this->download_send_headers("diferente_centru_" . date("Y-m-d") . ".csv");
		echo $this->array2csv($arr);die;
	}


	function DiferenteAgent() {
        $this->vars['title_page'] = 'Diferente Agent';
        $vars = [];
		$vars['DATA_START'] = date('d.m.Y');
		$vars['DATA_FINAL'] = date('d.m.Y');
        return $this->Parse($this->page_prefix . 'diferente_agent.html', $vars);
    }


	function Expeditii_DiferenteAgent() {
		$cond1 = '1=1';
		$cond2 = '1=1';

		if(empty($_REQUEST['agent']) && empty($_REQUEST['data_final']) && empty($_REQUEST['centru']))
			return [];

		if(!empty($_REQUEST['data_final'])) {
			$data1 = $this->TransformDate($_REQUEST['data_final'])." 00:00:00";
			$data2 = $this->TransformDate($_REQUEST['data_final'])." 23:59:59";

			$cond1 .= " AND sc.data >= '".$data1."' AND sc.data <= '".$data2."'";
			$cond2 .= " AND data>'".$data1."' AND data<'".$data2."'";
		}

		if(!empty($_REQUEST['agent'])) {
			$cond1 .= ' AND sc.curier = '.intval($_REQUEST['agent']);
			$cond2 .= ' AND curier = '.intval($_REQUEST['agent']);
		}

		if(!empty($_REQUEST['centru'])) {
			$cond1 .= ' AND IF(cld.zona_id > 0 and cldc.id > 0, cldc.id, lcd.cod_centru) = '.intval($_REQUEST['centru']);
			$cond2 .= ' AND centru = '.intval($_REQUEST['centru']);
		}

		$categorie = intval($_REQUEST['categorie'] ?? 0);
		if($categorie == 1) {$cond1 .= ' AND 1=1';}
		else if($categorie == 2) $cond1 .= ' AND ep.plicuri > 0';
		else if($categorie == 3) $cond1 .= ' AND (ep.colete > 0 or ep.paleti > 0)';

		$searchOn = false;
		if(isset($_REQUEST['_search'])) $searchOn = $this->Strip($_REQUEST['_search']);
        if ($searchOn == 'true') {
            $searchstr = $this->Strip($_REQUEST['filters']);
			$jsona = json_decode($searchstr, true);
			$rules = $jsona['rules'];
			foreach ($rules as $key => $val) {
			    $field = $val['field'];
			    $val = $val['data'];
			    if (isset($val)) {
			   		switch ($field) {
			        	case 'tip':
			                break;
			            default :
							$cond2 .= " AND ".$field." LIKE '" . strtoupper(addslashes($val)) . "%'";
							break;
					}
				}
			}
        }

		$exp = [];

		$query = "SELECT sc.id, sc.cod, sc.centru, sc.data as data_scanare, sc.ruta as ruta_id,
			ru.denumire as ruta,
			ag.nume_ag as curier, sc.curier as curier_id, ck.abbr as tip, sc.tip as tip_id, sc.borderou,
			ep.expeditie, ep.plicuri, ep.colete, ep.paleti, ep.tip_exp, ep.tip_obj, ep.piese,
			cle.nume as expeditor, lce.nume_lc as expeditor_localitate,
			cld.nume as destinatar, lcd.nume_lc as destinatar_localitate,
			IF(cle.zona_id > 0 and clec.id > 0, clec.nume, cee.nume) as expeditor_centru,
			IF(cld.zona_id > 0 and cldc.id > 0, cldc.nume, ced.nume) as destinatar_centru,
			IF(cle.zona_id > 0 and clec.id > 0, clec.label, cee.label) as expeditor_centru_cod,
			IF(cld.zona_id > 0 and cldc.id > 0, cldc.label, ced.label) as destinatar_centru_cod,
			case when ep.tip_plata = 0 or ep.tip_plata = 3 then ep.ramburs else 0.00 end as ramburs,
			case when ep.platitor_id = ep.destinatar_id and ep.mod_plata=0 then (ep.valoare_totala_expeditie + ep.tva) else 0.00 end as cash,
			ep.mod_plata, ep.tva
			FROM scanari_coduri as sc
			LEFT JOIN rute as ru ON sc.ruta = ru.id
			LEFT JOIN agenti as ag ON sc.curier = ag.cod_ag
			LEFT JOIN checkpoints as ck ON sc.tip = ck.id
			LEFT JOIN exp_prelucrate as ep ON (sc.expeditie = ep.expeditie and ep.anulata = 0)
			left join clienti cle on cle.cod_cl = ep.expeditor_id
			left join clienti cld on cld.cod_cl = ep.destinatar_id
			LEFT JOIN zones clez ON clez.id = cle.zona_id
        	LEFT JOIN centre clec on clec.id = clez.centru_id
			LEFT JOIN zones cldz ON cldz.id = cld.zona_id
        	LEFT JOIN centre cldc on cldc.id = cldz.centru_id
			left join localitati lce ON lce.cod_lc = cle.cod_lc
			left join localitati lcd ON lcd.cod_lc = cld.cod_lc
			left join centre cee ON cee.id = lce.cod_centru
			left join centre ced ON ced.id = lcd.cod_centru
			WHERE ".$cond1." AND (sc.tip=4) and ((ep.ramburs > 0  and ep.referire = 0 and (ep.tip_plata = 0 or ep.tip_plata = 3)) or
			(ep.mod_plata=0 and ep.platitor_id = ep.destinatar_id and ep.referire = 0))
			and sc.is_awb = 1
			GROUP BY ep.expeditie
			ORDER BY sc.id desc
		";
        $exp = $this->db->QFetchRowArray($query);

		$expeditii = [];
		if (!empty($exp)) {
            foreach ($exp as $key => $row) {
            	$row['incasat'] = 0;
				$query = "
					SELECT cod, tip
					FROM scanari_coduri
					WHERE ".$cond2." and cod = '".$row['cod']."' AND (tip = 3 OR tip > 4) and id > '".$row['id']."'
					LIMIT 1";
        		$cod = $this->db->QFetchArray($query);
				if (!empty($cod)) {
					$row['incasat'] = 1;
					if(isset($cod['tip']) && $cod['tip'] == 5)
						$row['incasat'] = 2;
				}
				$expeditii[] = $row;
			}
		}
		return $expeditii;
	}

    function JSON_DiferenteAgent() {
    	$responce = new StdClass();
		$expeditii = $this->Expeditii_DiferenteAgent();
		$total_piese = 0;
        if (!empty($expeditii)) {
            foreach ($expeditii as $key => $row) {
				$row['tip_obj'] = 'NA';
				$row['piese'] = 0;
				if(!empty($row['plicuri'])) {$row['tip_obj'] = 'Plic';$row['piese'] = 1;}
				if(!empty($row['colete'])) {$row['tip_obj'] = 'Colete';$row['piese'] = $row['colete'];}
				if(!empty($row['paleti'])) {$row['tip_obj'] = 'Paleti';$row['piese'] = $row['paleti'];}

                $responce->rows[$key]['id'] = ($key+1);
                $responce->rows[$key]['cell'] = array(
                									$row['cod'],
                									$row['expeditie'],
                									strtoupper($row['curier']),
                									$row['tip_obj'],
                									$row['piese'],
                									strtoupper($row['destinatar']),
                									strtoupper($row['expeditor']),
                									strtoupper($row['destinatar_centru']),
                									strtoupper($row['destinatar_localitate']),
                									strtoupper($row['expeditor_centru']),
                									strtoupper($row['expeditor_localitate']),
                									$row['ramburs'],
                									$row['cash'],
                									$row['data_scanare'],
                									$row['incasat'],
												);
                $total_piese++;
                //print_r($row);
            }
        }
		$responce->userdata['expeditie'] =  '';
		$responce->userdata['tip'] = '';
		$responce->userdata['expeditor'] =  'Total: '.$total_piese.' AWB-uri';

        $responce->page = 1;
        $responce->total = 1;
        $responce->records = $total_piese;
        return json_encode($responce);
    }


	function ExportDiferenteAgent(){
		$expeditii = $this->Expeditii_DiferenteAgent();

		$arr=[];
		$arr[0][1] = 'CodBare';
		$arr[0][2] = 'Nr NT';
		$arr[0][3] = 'Agent';
		$arr[0][8] = 'Tip';
		$arr[0][9] = 'Piese';
		$arr[0][10] = 'Destinatar';
		$arr[0][11] = 'Expeditor';
		$arr[0][12] = 'Centru Livrare';
		$arr[0][13] = 'Localitate Livrare';
		$arr[0][14] = 'Centru Colectare';
		$arr[0][15] = 'Localitate Colectare';
		$arr[0][16] = 'Ramburs';
		$arr[0][17] = 'Cash';
		$arr[0][18] = 'Data Scanare';
		$arr[0][19] = 'Incasat';

        if (!empty($expeditii)) {
            foreach ($expeditii as $key => $row) {
				$row['tip_obj'] = 'NA';
				$row['piese'] = 0;
				if(!empty($row['plicuri'])) {$row['tip_obj'] = 'Plic';$row['piese'] = 1;}
				if(!empty($row['colete'])) {$row['tip_obj'] = 'Colete';$row['piese'] = $row['colete'];}
				if(!empty($row['paleti'])) {$row['tip_obj'] = 'Paleti';$row['piese'] = $row['paleti'];}

				$arr[($key+1)][1] = $row['cod'];
				$arr[($key+1)][2] = $row['expeditie'];
				$arr[($key+1)][3] = strtoupper($row['curier']);
				$arr[($key+1)][8] = $row['tip_obj'];
				$arr[($key+1)][9] = $row['piese'];
				$arr[($key+1)][10] = strtoupper($row['destinatar']);
				$arr[($key+1)][11] = strtoupper($row['expeditor']);
				$arr[($key+1)][12] = strtoupper($row['destinatar_centru']);
				$arr[($key+1)][13] = strtoupper($row['destinatar_localitate']);
				$arr[($key+1)][14] = strtoupper($row['expeditor_centru']);
				$arr[($key+1)][15] = strtoupper($row['expeditor_localitate']);
				$arr[($key+1)][16] = $row['ramburs'];
                $arr[($key+1)][17] = $row['cash'];
                $arr[($key+1)][18] = $row['data_scanare'];
                $arr[($key+1)][19] = $row['incasat'];

            }
        }
    	$this->download_send_headers("diferente_centru_" . date("Y-m-d") . ".csv");
		echo $this->array2csv($arr);die;
	}

	function Print_BorderouriExpeditiiScanare_TcpdfCurier(){
		$pData = isset($_POST['pJson'])?base64_decode($_POST['pJson']):'';
		if(empty($pData)) return "0 selected";

		$pData = json_decode($pData, true);
		if(!is_array($pData)) return "0 selected";
		if(count($pData) <= 2) return "0 selected"; //pData first element = centru; second = agent

		$centru = intval(array_shift($pData));
		$query = "SELECT nume FROM centre WHERE id = {$centru}";
        $sql = $this->db->QFetchArray($query);
		if(!empty($sql)) $centru = strtoupper($sql['nume']);

		$agent = intval(array_shift($pData));
		$query = "SELECT nume_ag FROM agenti WHERE cod_ag = {$agent}";
        $sql = $this->db->QFetchArray($query);
		if(!empty($sql)) $agent = strtoupper($sql['nume_ag']);


		//array_map : filter exps integer
		$pData = array_map("intval", $pData);
		$pData = array_diff($pData, array(0));
		if(count($pData) == 0) return "0 selected";

		$bos = implode(",", $pData);

		$vars=[];
		$vars['borderou'] = $bos;
		$vars['curier'] = $agent;
		$vars['centru'] = $centru;
		require_once 'BorderouLivrarePdf.php';

		$query = "(
			SELECT
			sc.id as idd,
			sc.expeditie as exp,
			sc.borderou as borderou,
			COUNT(DISTINCT sc.cod) as piese_scanate,
			ep.expeditie as expeditie,
			ep.data_expeditie,
			ep.plicuri,
			ep.colete,
			ep.paleti,
			ep.greutate,
			ep.referire,
			ep.valoare_asigurata,
			ep.ramburs,
			ep.tip_plata,
			ep.valoare_totala_expeditie,
			ep.tva,
			cld.nume as destinatar,
			lcd.nume_lc as destinatar_localitate,
			cld.adresa as destinatar_adresa,
			ep.destinatar_id,
			ep.platitor_id,
			ep.observatii,
			ep.detalii_doc,
			ep.destinatar_contact,
			ep.destinatar_telefon,
			ep.mod_plata,
			ep.ret_doc,
			ep.ret_nt,
			ep.ret_amb,
			ep.ret_colet,
			cn.extrainfo,
			cn.largeinfo
			FROM scanari_coduri as sc USE INDEX (borderou)
			INNER JOIN exp_prelucrate as ep ON (sc.expeditie = ep.expeditie AND ep.anulata = 0)
			LEFT JOIN exp_nc as cn ON ep.expeditie = cn.expeditie
			LEFT JOIN clienti cld ON cld.cod_cl = ep.destinatar_id
			LEFT JOIN localitati lcd ON lcd.cod_lc = cld.cod_lc
			WHERE sc.borderou IN (".$bos.")
			GROUP BY sc.expeditie
		)
		UNION
		(
			SELECT
			sc.id as idd,
			sc.expeditie as exp,
			sc.borderou as borderou,
			1 as piese_scanate,
			NULL as expeditie,
			NULL as data_expeditie,
			NULL as plicuri,
			NULL as colete,
			NULL as paleti,
			NULL as greutate,
			NULL as referire,
			NULL as valoare_asigurata,
			NULL as ramburs,
			NULL as tip_plata,
			NULL as valoare_totala_expeditie,
			NULL as tva,
			NULL as destinatar,
			NULL as destinatar_localitate,
			NULL as destinatar_adresa,
			NULL as destinatar_id,
			NULL as platitor_id,
			NULL as observatii,
			NULL as detalii_doc,
			NULL as destinatar_contact,
			NULL as destinatar_telefon,
			NULL as mod_plata,
			NULL as ret_doc,
			NULL as ret_nt,
			NULL as ret_amb,
			NULL as ret_colet,
			NULL as extrainfo,
			NULL as largeinfo
			FROM scanari_coduri as sc USE INDEX (borderou)
			LEFT JOIN exp_prelucrate as ep ON (sc.expeditie = ep.expeditie AND ep.anulata = 0)
			WHERE sc.borderou IN (".$bos.") AND ep.expeditie IS NULL
		)
		ORDER BY 17;";

		// error_log($query);
        $sql = $this->db->QFetchRowArray($query);
		//print_r($sql);die;
		$vars['total_piese_scanate'] = 0;
		$vars['total_greutate'] = 0.00;
		$vars['total_ramburs'] = 0.00;
		$vars['total_expeditii'] = 0;
		$total_expeditii = 0;

		$today = new DateTime("now");
		$vars['data'] = $today->format('d.m.Y / H:i');
		$filename = 'Borderou-Livrare-'.$agent.'-';
		$filename .= $today->format('d-m-Y').'.pdf';

		if (!empty($sql)) {
			foreach ($sql as $key => $row) {
            	$vars['total_piese_scanate'] += $row['piese_scanate'];
            	if(!empty($row['expeditie'])) { //expeditie cu key entry
            		$vars['total_expeditii']++;
					if(!empty($row['greutate'])) $vars['total_greutate'] += $row['greutate'];
					if($row['ramburs'] != '0.00'  && $row['ramburs'] != null) $vars['total_ramburs'] += $row['ramburs'];
				}
				else {
					//expeditii + puisori fara key entry
					if(ExpeditieDto::isAwb($row['exp']) || ExpeditieDto::isCmnAwb($row['exp'])) { //expeditie fara key entry
						$vars['total_expeditii']++;
					}
				}
			}
			$vars['total_greutate'] = round($vars['total_greutate'],2);
			if($vars['total_greutate'] > 450)
				$vars['total_greutate'] = 400 + rand(8, 49);
			$vars['total_ramburs'] = round($vars['total_ramburs'],2);
			$pdf = new BorderouLivrarePdf($vars);
			$pdf->setPrintHeader(false);
			$pdf->addPage('P');
			$pdf->addCMR($vars);
			// add a page
			$pdf->setPrintHeader(true);
			$pdf->AddPage('L');
            $vars['expeditii'] = [];
            foreach ($sql as $key => $row) {
            	if(!empty($row['expeditie'])) { //expeditie cu key entry
                    $vars['expeditii'][] = $row['expeditie'];
            		$total_expeditii++;
            		$var=[];
            		$var['expeditie'] = intval($row['exp']);
					//nr de piese
					$var['tip_obj'] = '';
					$var['piese'] = 1;
					if(!empty($row['plicuri'])) {$var['piese'] = $row['plicuri']; $var['tip_obj'] = 'Plic';}
					else if(!empty($row['colete'])) {$var['piese'] = $row['colete']; $var['tip_obj'] = 'Colet';}
					else if(!empty($row['paleti'])) {$var['piese'] = $row['paleti']; $var['tip_obj'] = 'Palet';}
					$var['piese_scanate'] = intval($row['piese_scanate']);

					$var['greutate'] = '';
					//if(!empty($row['greutate'])) {
					//	$var['greutate'] = $row['greutate'];
					//}

					//detalii expeditie
					$var['ramburs'] = $var['valoare_asigurata'] = $var['referire'] = $var['expeditie'] = $var['tip_plata'] = '';
					$var['expeditie'] = '';
					if(!empty($row['exp'])) $var['expeditie'] = $row['exp'];
					$var['referire'] = '';
					if(!empty($row['referire'])) $var['referire'] = $row['referire'];
					$var['valoare_asigurata'] = '';
					if($row['valoare_asigurata'] != '0.00' && $row['valoare_asigurata'] != null) $var['valoare_asigurata'] = $row['valoare_asigurata'];
					$var['ramburs'] = '';
					$var['tip_plata'] = '';
					if($row['ramburs'] != '0.00'  && $row['ramburs'] != null) {
						$var['ramburs'] = $row['ramburs'];
						$var['tip_plata'] = 'cash';
						if($row['tip_plata']==1) $var['tip_plata'] = 'bo';
						else if($row['tip_plata']==2) $var['tip_plata'] = 'cec';
					}
					if(!empty($row['detalii_doc'])) $var['detalii_doc'] = $row['detalii_doc'];
					if(!empty($row['observatii'])) $var['observatii'] = $row['observatii'];

					//destinatar
					if($row['destinatar_contact'] == 'Persoana' || empty($row['destinatar_contact'])) $row['destinatar_contact'] = 'NA';
					$var['client'] = '';
					if(!empty($row['destinatar']))
						$var['client'] = $row['destinatar'];
					$var['adresa'] = '';
					if(!empty($row['destinatar_localitate']) && !empty($row['destinatar_adresa']))
						$var['adresa'] = $row['destinatar_localitate'].', '.$row['destinatar_adresa'];
					else if(!empty($row['destinatar_localitate']))
						$var['adresa'] = $row['destinatar_localitate'];
					else if(!empty($row['destinatar_adresa']))
						$var['adresa'] = $row['destinatar_adresa'];
					$var['contact'] = '';
					if(!empty($row['destinatar_contact']) && !empty($row['destinatar_telefon']))
						$var['contact'] = $row['destinatar_contact'].', '.$row['destinatar_telefon'];
					else if(!empty($row['destinatar_contact']))
						$var['contact'] = $row['destinatar_contact'];
					else if(!empty($row['destinatar_telefon']))
						$var['contact'] = $row['destinatar_telefon'];

					$var['ret_nt'] = '';
					if(!empty($row['ret_nt']) || !empty($row['extrainfo'])) $var['ret_nt'] = 'RN';
					$var['ret_doc'] = '';
					if(!empty($row['ret_doc'])) $var['ret_doc'] = 'RD';
					$var['ret_amb'] = '';
					if(!empty($row['ret_amb'])) $var['ret_amb'] = 'RA';
					$var['ret_colet'] = '';
					if(!empty($row['ret_colet'])) $var['ret_colet'] = 'RC';

					$var['numerar'] = '';
					if($row['destinatar_id'] == $row['platitor_id'] && empty($row['mod_plata']) && $row['valoare_totala_expeditie'] != '0.00' && $row['valoare_totala_expeditie'] != null) $var['numerar'] = round($row['valoare_totala_expeditie']+$row['tva'],2);
					$pdf->makeTR($var);
					if($total_expeditii%10==0)
					{ $pdf->SetTopMargin(30);	$pdf->AddPage();}
				}
				else {
					//expeditii + puisori fara key entry
					if(ExpeditieDto::isAwb($row['exp']) || ExpeditieDto::isCmnAwb($row['exp'])) { //expeditie fara key entry
						$total_expeditii++;
						$var=[];
            			$var['expeditie'] = intval($row['exp']);
                        $vars['expeditii'][] = $var['expeditie'];
            			$var['piese'] = 0;
            			$var['piese_scanate'] = 1;
            			$var['tip_obj'] = '';
						$var['greutate'] = '';
						$var['ramburs'] = '';
						$var['ramburs'] = $var['valoare_asigurata'] = $var['referire'] = $var['expeditie'] = $var['tip_plata'] = '';
						$var['expeditie'] = '';
						if(!empty($row['exp'])) $var['expeditie'] = $row['exp'];
						$var['client'] = '';
						$var['adresa'] = '';
						$var['contact'] = 'NA';
						$var['ret_nt'] = $var['ret_doc'] = $var['numerar'] = $var['ret_amb'] = $var['ret_colet'] = '';

						$pdf->makeTR($var);
						if($total_expeditii%12==0)
						{ $pdf->SetTopMargin(30);	$pdf->AddPage();}
					}
				}
            }

			$pdf->setPrintHeader(false);
			$pdf->addPage();
			$pdf->addProcessVerbal($vars);


			$pdf->lastPage();

			$pdf->Output($filename,'D');
        }
	    exit;
	}
}
//end class
