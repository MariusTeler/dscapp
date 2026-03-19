<?php
require_once "expeditieDto.php";
require_once "SendEmailMailGun.php";

class ModulScanport extends BackEnd
{
    public $final_result;
    public $action_module;
    public $page_prefix;
    public $site_prefix;
    public $table;
    public $scanare;
    public $eroare;

    public $user_nume;


    /**
     * The constructor for the 'ModulScanport' class
     * Calls BackEnd constructor
     * Cals Actions function
     *
     * @param array $config
     * @param integer $act  (0/1) Specifies if actions are alowed or not
     * @access public
     * @see Actions()
     */
    function __construct($config = 0, $act = 1, $db = 0) {
        parent :: __construct($config, $db);

        $this->vars['title_page'] = 'Scan Port';
        $this->page_prefix = 'scanport_';

        $this->user_nume = $_SESSION["user"]["nume"] ?? "";

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
            $this->ActionsNivelAcces($this->user_profile);
        else
            $this->final_result = $this->PageNotFound();
        // R E S U L T
        return $this->final_result;
    }

    function ActionsNivelAcces($nivel_acces) {

        $arr = $this->GenerateArr();

        $levelels = $this->config['scanare']['allowLevels'];

        if (in_array($nivel_acces,$levelels )){

            if (isset($arr[1]) && $arr[1] == 'ajax')
                $this->final_result = $this->Ajax();
            else
                $this->final_result = $this->Land();

        }else {
            $this->final_result = $this->PageNotFound();
        }


    }

    //-------------------------------- functii ----------------------------------------

    function Ajax(){
        $dataScan = date('Y-m-d H:i:s', time()); // $borderou['time']

        //authenticate
        if(isset($_POST['cnp'])){
            $user = $this->authenticateLogin($_POST['cnp']);
            if($user === false) {
                echo json_encode(array('success' => 0));
                return;
            }

            $user['agenti'] = $this->getDbAgentiCentru($user['centru']);
            $user['checkpoints'] = $this->getDbCheckpoints();
            $user['rute'] = $this->getDbRuteCentru($user['centru']);

            echo json_encode($user);
            return;
        }

        if(isset($_POST['reweight_cod'])){
            $expeditie = intval($_POST['reweight_cod'] ?? 0);
            $new_kg = ceil(floatval($_POST['reweight_kg'] ?? 0));
            $centru_id = intval($_POST['centruId'] ?? 0) == 0;
            $centru_id = $centru_id == 0 ? $this->user_centru_id : 0;
            $lungime = intval($_POST['reweight_lungime'] ?? 0);
            $latime = intval($_POST['reweight_latime'] ?? 0);
            $inaltime = intval($_POST['reweight_inaltime'] ?? 0);

            $greutate_vol = ExpeditieDto::getGreutateVolumetrica($lungime, $latime, $inaltime);
            $new_kg = ceil(max($new_kg, $greutate_vol));

            if($expeditie == 0 || !ExpeditieDto::isAwb($expeditie)) {
                echo json_encode(['success'=> false, 'checkpoint' => ['response' => "RW" , 'error'=> "-1"]]);
                return;
            }
            $this->db->QueryUpdate('exp_prelucrate', ['anulata'=>0, 'deleted_at' => null, 'deleted_by' => 0], "expeditie = {$expeditie}");

            $qexist = "SELECT id
                FROM exp_recantarite
                WHERE expeditie = {$expeditie} and centru_id = {$centru_id} and kg = {$new_kg} and lungime = {$lungime} and latime = {$latime} and inaltime = {$inaltime} limit 1";
		    $found = $this->db->QFetchArray($qexist);

            if(!empty($found) && !empty($found['id'])) {
                //error_log("exp_recantarite id : ".$found['id']);
                echo json_encode(
                    array(
                        'success'=> true,
                        'checkpoint' => ['response' => "RW" , 'error'=> "-3" ]
                    )
                );
                return;
            }

            $qinit = "SELECT greutate, greutate_vol
                FROM exp_prelucrate
                WHERE expeditie = {$expeditie} and tip_exp = 0 and idfact = 0 order by anulata asc limit 1";
		    $init = $this->db->QFetchArray($qinit);

            //insert exp_recantarite
			$insId = $this->insertRecantarite($centru_id, $expeditie, $new_kg, $lungime, $latime, $inaltime, 0, ($init['greutate'] ?? 0), 3);

            $sendData = array(
                'ruta'      => 0,
                'centru'    => $centru_id,
                'curier'    => $this->config['scanare']['CurierMagazie'],  // Magazie
                'scanner'   => "Statie scanare",
                'tip'       => 7,
                'coduri'    => array($expeditie),
                'status'    => $this->config['scanare']['StatusRecantarire'],  // RW - Recantarire
                'user'      => $_POST['userId'],
                'data'      => $dataScan
            );
            $this->sendDbBorderou($sendData);

            if(!empty($init) && floor(max($init['greutate'], $init['greutate_vol'])) == $new_kg) {
				//validare only
				$this->db->QueryUpdate('exp_recantarite', ['vKg' => 1, 'vMotiv' => 'StandBy'], "id = " . $insId);
				echo json_encode(['success'=> false, 'checkpoint' => ['response' => "RW" , 'error'=> "-2"]]);
                return;
			}

            echo json_encode(
                array(
                    'success'=> true,
                    'checkpoint' => ['response' => "RW" , 'error'=> "-3" ]
                )
            );
        }


        if(isset($_POST['borderouri'])){

            if (is_array($_POST['borderouri'])){
                $borderouri = $_POST['borderouri'];
            } else {
                $borderouri = json_decode($_POST['borderouri'], true);
            }

            $success = [];
            if(count($borderouri) == 0){
                $this->sendError($_POST['borderouri']);
                error_log("BORDEROU SCANARE GOL:".$_POST['borderouri']);
                $success[]['Eroare parsare']['error'] = "Eroare salvare borderou!";
                echo json_encode($success);
                exit();
            }

            foreach ($borderouri as $k=>$borderou){

                $success[$k] = [];
                $dataToSend = [];
                $dataToSend['coduri'] = [];
                $coduri_deteriorate = [];
                $coduri_retur_la_magazie = [];
                $savedSelections = [];

                $dataToSend['userId'] = empty($borderou['userId'])? $this->user_id : intval($borderou['userId']);
                $dataToSend['centruId'] = empty($borderou['centruId'])? $this->user_centru_id : intval($borderou['centruId']);
                $dataToSend['userName'] = empty($borderou['userName'])? $this->user_nume : intval($borderou['userName']);

                $ruta = 0;
                $curier = 0;
                $tip = 0;
                $status = 0;

                if(!empty($borderou['savedSelections'])){
                    foreach ($borderou['savedSelections'] as $savedSelection){
                        $savedSelections[$savedSelection['id']] = $savedSelection["value"];
                    }
                }


                $removeCurier = false;

                switch ($borderou['menu_path']):

                    case '1_1':   // Curier - intrare

                        $ruta       = 3;
                        $status     = $ruta;
                        $tip        = 5;
                        $curier =  (isset($savedSelections['select_1_1_1']))?$savedSelections['select_1_1_1']:0;

                        break;

                    case '1_2':   // Curier - Iesire

                        $ruta       = 4;
                        $status     = $ruta;
                        $tip        = 5;
                        $curier = (isset($savedSelections['select_1_2_4']))?$savedSelections['select_1_2_4']:0;

                        break;

                    case '2_3':   // Centru - intrare TRK

                        $ruta       = (isset($savedSelections['select_2_3_7']))?$savedSelections['select_2_3_7']:0;
                        $status     = null;
                        $tip        = 1;
                        $curier =  $dataToSend['userId'];
                        $removeCurier = true;
                        break;

                    case '2_4': // Centru Iesire TRK

                        $ruta       = (isset($savedSelections['select_2_4_9']))?$savedSelections['select_2_4_9']:0;
                        $status     = null;
                        $tip        = 2;
                        $curier     =  $dataToSend['userId'];

                        break;

                    case '2_5': // Centru Inventar
                        $ruta       = 0;
                        $status     = $this->config['scanare']['StatusInventar'];
                        $tip        = 7;
                        $curier     = $this->config['scanare']['CurierMagazie'];
                        break;

                    case '2_6': // Centru Ruta gresita
                        $ruta       = 0;
                        $status     = $this->config['scanare']['StatusRutaGresita'];
                        $tip        = 7;
                        $curier     = $this->config['scanare']['CurierMagazie'];
                        break;

                    case '2_7': // Centru Deteriorate
                        $ruta       = 0;
                        $status     = $this->config['scanare']['StatusDeteriorata'];;
                        $tip        = 7;
                        $curier     = $this->config['scanare']['CurierMagazie'];
                        break;

                    case '3' : // Confirmari
                        $ruta       = 0;
                        $status     = (isset($savedSelections['select_3_9_15']))?$savedSelections['select_3_9_15']:'';
                        $tip        = 7;
                        $curier     = (isset($savedSelections['select_3_9_14']))?$savedSelections['select_3_9_14']:'';
                        break;

                    default:

                endswitch;

                $coduri = [];

                $separator = $this->config['scanare']['barcodeAndTimeSeparator'];

                foreach ($borderou['coduri'] as $cod){
                    if(!isset($cod['barcode']) || !ExpeditieDto::isValidCod($cod['barcode'])){
                        continue;
                    }
                    if($cod['det'] == 'true'){
                        $coduri_deteriorate[] = $cod['barcode'].$separator.$cod['time'];
                    }
                    if($cod['rem'] == 'true'){
                        $coduri_retur_la_magazie[] = $cod['barcode'].$separator.$cod['time'];
                    }
                    $coduri[] = $cod['barcode'].$separator.$cod['time'];
                }

                $scanner    = "Statie scanare";
                $centru     = $dataToSend['centruId'];
                $user       = $dataToSend['userId'];

                $sendData = array(
                    'ruta'      => $ruta,
                    'centru'    => $centru,
                    'curier'    => $curier,
                    'scanner'   => $scanner,
                    'tip'       => $tip,
                    'coduri'    => $coduri,
                    'status'    => $status,
                    'user'      => $user,
                    'data'      => $dataScan
                );

                $success[$k]['Coduri scanate'] = $this->sendDbBorderou($sendData);

                if(count($coduri_deteriorate) > 0){
                    $tip        = 7;
                    $sendData = array(
                        'ruta'      => $ruta,
                        'centru'    => $centru,
                        'curier'    => ($removeCurier)?0:$this->config['scanare']['CurierMagazie'],
                        'scanner'   => $scanner,
                        'tip'       => $tip,
                        'coduri'    => $coduri_deteriorate,
                        'status'    => $this->config['scanare']['StatusDeteriorata'],
                        'user'      => $user,
                        'data'      => $dataScan
                    );

                    $success[$k]['Coduri deteriorate'] = $this->sendDbBorderou($sendData);
                }

                if(count($coduri_retur_la_magazie) > 0){
                    $tip        = 7;
                    $sendData = array(
                        'ruta'      => $ruta,
                        'centru'    => $centru,
                        'curier'    => ($curier >0)?$curier:$this->config['scanare']['CurierMagazie'],
                        'scanner'   => $scanner,
                        'tip'       => $tip,
                        'coduri'    => $coduri_retur_la_magazie,
                        'status'    => $this->config['scanare']['StatusReturLaMagazie'],
                        'user'      => $user,
                        'data'      => $dataScan
                    );

                    $success[$k]['Coduri retur la magazie'] = $this->sendDbBorderou($sendData);
                }
            }
            echo json_encode($success);
        }

        exit();
    }

    function sendError($data){

        $HTTP_USER_AGENT = $_SERVER['HTTP_USER_AGENT'];
        $REMOTE_ADDR = $this->kh_getUserIP();
        $file_control = '_tmp/scan-'.md5($REMOTE_ADDR.$HTTP_USER_AGENT.$data);
        $to      =  "noc@curierdragonstar.ro";
        $subject = 'Eroare scanare '. $REMOTE_ADDR;
        $message = "IP:".$REMOTE_ADDR."<br>Agent:".$HTTP_USER_AGENT."<br>".$data;

        if(is_file($file_control)){
            return;
        }
        if(SendEmailMailGun::send(
            $emailFrom = 'notificari@info.curierdragonstar.ro',
            $emailConfirmTo = 'notificari@info.curierdragonstar.ro',
            $emailReplayTo = 'notificari@info.curierdragonstar.ro',
            $emailsToSent = $to,
            [],
            [],
            [],
            $emailSubject = $subject,
            $emailBody = $message,
        ))
        {
        // if($this->sendEmail($to, $subject, $message, "noc@curierdragonstar.ro")){
            file_put_contents($file_control,$REMOTE_ADDR."\n".$HTTP_USER_AGENT."\n".$data);
        }

    }

    function kh_getUserIP(){
        $client  = @$_SERVER['HTTP_CLIENT_IP'];
        $forward = @$_SERVER['HTTP_X_FORWARDED_FOR'];
        $remote  = $_SERVER['REMOTE_ADDR'];

        if(filter_var($client, FILTER_VALIDATE_IP)){
            $ip = $client;
        }elseif(filter_var($forward, FILTER_VALIDATE_IP)){
            $ip = $forward;
        }else{
            $ip = $remote;
        }
        return $ip;
    }

    function Land($message = ''){
        $vars = [];
        $vars['auto_login'] = (isset($_COOKIE['auto_login_cookie'])?$_COOKIE['auto_login_cookie']:"");
        setcookie("auto_login_cookie", "", [
            'expires' => time()-3600,
            'path' => '/',
            'domain' => $this->config["domain"],
            'secure' => true,
            'httponly' => true,
            'samesite' => 'Strict',
        ]);


        /*
        if($this->config['scanare']['enable_agent_scan'] == -1 || $this->config['scanare']['enable_agent_scan'] == $this->user_centru_id) {
            $vars['scan_agent'] = 'true';
        } else {
            $vars['scan_agent'] = 'false';
        }
        */
        $vars['scan_agent'] = 'false';
        $vars['error'] = $message;
        $vars['elements'] = $this->getAppElements();
	    $vars['delogare_time'] = $this->config['scanare']['logoutAfter'];
        $vars['js_version'] = time();
        $vars['css_version'] = time();
        $this->vars['site_title'] = 'Scanare Coduri';
        return $this->Parse($this->page_prefix . 'app.html', $vars);
    }


    function getAppElements(){

        $level_0_cont = 0;
        $level_1_cont = 0;
        $level_2_cont = 0;
        $main_flow = $this->getMain();
        $separator = "|";
        $out = "";
        foreach ($main_flow['elements'] as $level0){
            $level_0_cont++;

            $out .= '<li class="path-'.$level_0_cont.' level-0">';
            $out .= '<a href="#" class="menu-acction btn widecustom path-text-'.$level_0_cont.'" rel="'.$level0['id'].'" menu_path="'.$level_0_cont.'" menu_path_text="'.$level0['title'].'" level="0">'.$level0['title'].'</a>';
             foreach ($level0['elements'] as $level1) {
                $level_1_cont++;

                 $out .= '<li class="path-'.$level_0_cont."_".$level_1_cont.' hide-menu level-1 sub-path-'.$level_0_cont.'">';
                 if($level1['type'] == 'button') {
                     $out .= '<a href="#" class="menu-acction btn widecustom path-text-'.$level_0_cont."_".$level_1_cont.'" rel="'.$level1['id'].'" menu_path="'.$level_0_cont."_".$level_1_cont.'" menu_path_text="'.$level0['title'].$separator.$level1['title'].'" level="1">'.$level1['title'].'</a>';
                 }

                foreach ($level1['elements'] as $level2) {
                    $level_2_cont++;
                    $next_scan_op = (isset($level1['next_scan']))?'next_scan':'';
                    $out .= '<li class="path-'.$level_0_cont."_".$level_1_cont."_".$level_2_cont.' hide-menu level-2 sub-path-'.$level_0_cont."_".$level_1_cont.' '.$next_scan_op.'">';
                         if($level2['type'] == 'select' && !isset($level2['hide'])) {
                            $out .= '<select id="select_'.$level_0_cont."_".$level_1_cont."_".$level_2_cont.'" class="selectpicker" data-live-search="true">';
                                    $out .= '<option>'.$level2['title'].'</option>';

                                if(isset($level2['values'])) {
                                    foreach ($level2['values'] as $level2_val) {
                                        $text = (isset($level2_val['text']))?$level2_val['text']:'';
                                        $out .= '<option value="'.$level2_val['value'].'">'.$text.'</option>';
                                    }
                                }
                             $out .= '</select>';
                          } else if ($level2['type'] == 'hidden') {
                             $out .= '<input type="hidden" name="'.$level2['name'].'" value="'.$level2['value'].'">';
                          }
                    $out .= '</li>';

                    }
                 $out .= '</li>';

                }
            $out .= '</li>';
            }

        return $out;

    }



    function getMain()
    {
        $config = $this->config['scanare'];
        $StatusConfirmari = [];

        foreach ($config['StatusConfirmari'] as $id=>$value){
            $StatusConfirmari[] = array(
                'id' => $id,
                'value' => $id,
                'text'  => $value
            );
        }


        $main_flow = array(
            'id' => 1,
            'title' => 'Main',
            'description' => 'Main',
            'level' => 0,
            'elements' => array(
                array(
                    'id' => 2,
                    'title' => 'Curier',
                    'description' => 'Curier land',
                    'level' => 1,
                    'elements' => array(
                        array(
                            'id' => 2,
                            'title' => 'Intrare curier',
                            'description' => 'Curier Intrare',
                            'level' => 2,
                            'type' => 'button',
                            'next_scan' => true,
                            'elements' => array(
                                array(
                                    'id' => 2,
                                    'title' => 'Alege agent',
                                    'description' => 'Alege agent',
                                    'level' => 3,
                                    'type' => 'select'
                                ),
                                array(
                                    'id' => 2,
                                    'title' => 'Alege Zona',
                                    'description' => 'Alege Zona',
                                    'level' => 3,
                                    'type' => 'select',
                                    'hide' => false,
                                    'values' => array(
                                        array(
                                            'id' => 1,
                                            'value' => 'Zona 1'
                                        ),
                                        array(
                                            'id' => 2,
                                            'value' => 'Zona 2'
                                        ),
                                    ),
                                ),
                                array(
                                    'id' => 2,
                                    'hide' => false,
                                    'title' => 'Alege Auto',
                                    'description' => 'Alege Auto',
                                    'level' => 3,
                                    'type' => 'select',
                                    'values' => array(
                                        array(
                                            'id' => 1,
                                            'value' => 'Auto 1'
                                        ),
                                        array(
                                            'id' => 1,
                                            'value' => 'Auto 2'
                                        ),
                                    ),
                                )
                            )
                        ),
                        array(
                            'id' => 2,
                            'title' => 'Iesire curier',
                            'description' => 'Curier Iesire',
                            'level' => 2,
                            'type' => 'button',
                            'next_scan' => true,
                            'elements' => array(
                                array(
                                    'id' => 2,
                                    'title' => 'Alege agent',
                                    'description' => 'Alege agent',
                                    'level' => 3,
                                    'type' => 'select',
                                ),
                                array(
                                    'id' => 2,
                                    'hide' => 1,
                                    'title' => 'Alege Zona',
                                    'description' => 'Alege Zona',
                                    'level' => 3,
                                    'type' => 'select',
                                    'values' => array(
                                        array(
                                            'id' => 1,
                                            'value' => 'Zona 1'
                                        ),
                                        array(
                                            'id' => 1,
                                            'value' => 'Zona 2'
                                        ),
                                    ),
                                ),
                                array(
                                    'id' => 2,
                                    'hide' => 1,
                                    'title' => 'Alege Auto',
                                    'description' => 'Alege Auto',
                                    'level' => 3,
                                    'type' => 'select',
                                    'values' => array(
                                        array(
                                            'id' => 1,
                                            'value' => 'Auto 1'
                                        ),
                                        array(
                                            'id' => 1,
                                            'value' => 'Auto 2'
                                        ),
                                    ),
                                )
                            )
                        )
                    )
                ),
                array(
                    'id' => 3,
                    'title' => 'Centru',
                    'description' => 'Centru land',
                    'level' => 1,
                    'elements' => array(
                        array(
                            'id' => 2,
                            'title' => 'Intrare TRK',
                            'description' => 'Intrare TRK',
                            'level' => 2,
                            'type' => 'button',
                            'next_scan' => true,
                            'elements' => array(
                                array(
                                    'id' => 2,
                                    'title' => 'Alege Ruta',
                                    'description' => 'Alege ruta',
                                    'level' => 3,
                                    'type' => 'select',
                                    'values' => array(
                                        array(
                                            'id' => 1,
                                            'value' => 'agent 1'
                                        ),
                                        array(
                                            'id' => 1,
                                            'value' => 'agent 2'
                                        ),
                                    ),
                                ),
                                array(
                                    'id' => 2,
                                    'hide' => 1,
                                    'title' => 'Alege Auto',
                                    'description' => 'Alege Auto',
                                    'level' => 3,
                                    'type' => 'select',
                                    'values' => array(
                                        array(
                                            'id' => 1,
                                            'value' => 'Auto 1'
                                        ),
                                        array(
                                            'id' => 1,
                                            'value' => 'Auto 2'
                                        ),
                                    ),
                                )
                            )
                        ),
                        array(
                            'id' => 2,
                            'title' => 'Iesire TRK',
                            'description' => 'Iesire TRK',
                            'level' => 2,
                            'type' => 'button',
                            'next_scan' => true,
                            'elements' => array(
                                array(
                                    'id' => 2,
                                    'title' => 'Alege Ruta',
                                    'description' => 'Alege ruta',
                                    'level' => 3,
                                    'type' => 'select',
                                    'values' => array(

                                    ),
                                ),
                                array(
                                    'id' => 2,
                                    'hide' => 1,
                                    'title' => 'Alege Statie/Hub',
                                    'description' => 'Alege Statie/Hub',
                                    'level' => 3,
                                    'type' => 'select',
                                    'values' => array(

                                    ),
                                ),
                                array(
                                    'id' => 2,
                                    'hide' => 1,
                                    'title' => 'Alege Auto',
                                    'description' => 'Alege Auto',
                                    'level' => 3,
                                    'type' => 'select',
                                    'values' => array(
                                        array(
                                            'id' => 1,
                                            'value' => 'Auto 1'
                                        ),
                                        array(
                                            'id' => 1,
                                            'value' => 'Auto 2'
                                        ),
                                    ),
                                )
                            )
                        ),

                        array(
                            'id' => 2,
                            'title' => 'Inventar',
                            'description' => 'Inventar',
                            'level' => 2,
                            'type' => 'button',
                            'next_scan' => true,
                            'elements' => array(

                            )
                        ),

                        array(
                            'id' => 2,
                            'title' => 'Ruta gresita',
                            'description' => 'Ruta gresita',
                            'level' => 2,
                            'type' => 'button',
                            'next_scan' => true,
                            'elements' => array(

                            )
                        ),
                        array(
                            'id' => 2,
                            'title' => 'Deteriorate',
                            'description' => 'Deteriorate',
                            'level' => 2,
                            'type' => 'button',
                            'next_scan' => true,
                            'elements' => array(
                                array(
                                    'id'    => 2,
                                    'level' => 3,
                                    'type'  => 'hidden',
                                    'name'  => 'status',
                                    'value' => 1
                                )
                            )
                        ),
                        array(
                            'id' => 2,
                            'title' => 'Recantarire',
                            'description' => 'Recantarire',
                            'level' => 2,
                            'type' => 'button',
                            'next_scan' => true,
                            'elements' => array(
                                array(
                                    'id'    => 2,
                                    'level' => 3,
                                    'type'  => 'hidden',
                                    'name'  => 'status',
                                    'value' => 1
                                )
                            )
                        )
                    )
                ),

                array(
                    'id' => 2,
                    'title' => 'Confirmari',
                    'description' => 'Confirmari',
                    'level' => 1,
                    'next_scan' => true,
                    'elements' => array(
                        array(
                            'id' => 2,
                            'title' => 'Alege status',
                            'description' => 'Alege status',
                            'level' => 3,
                            'type' => 'select',
                            'elements' => array(
                                array(
                                    'id' => 2,
                                    'title' => 'Alege curier',
                                    'description' => 'Alege status',
                                    'level' => 3,
                                    'type' => 'select',
                                    'elements' => [],
                                    'values' => []
                                ),
                                array(
                                    'id' => 2,
                                    'title' => 'ALEGE STATUS',
                                    'description' => 'Alege status',
                                    'level' => 3,
                                    'type' => 'select',
                                    'elements' => [],
                                    'values' => $StatusConfirmari
                                )
                            )
                        )
                    )
                )
            )


        );

        return $main_flow;
    }

    //database ops
    private function authenticateLogin($cnp) {
    	$query = "select id, user, cnp, centru from users where activ = 1 and nivel_acces = 13 order by 2";
        $sql = $this->db->QFetchRowArray($query);
        if(empty($sql)) return false;

        foreach ($sql as $key => $row) {
            if($cnp == $row['cnp']) {
                unset($row['cnp']);
                return $row;
            }
        }
        return false;
    }

    public function getDbCheckpoints(){
        $query = "select id, denumire from checkpoints where activ = 1 and id != 5 order by 2";

        $rows = $this->db->QFetchRowArray($query);
        if (!empty($rows) && is_array($rows) && count($rows) > 0) {
            return $rows;
        }

        return [];
    }


    public function getDbCentre(){
        $query = "select id, nume from centre order by nume";

        $rows = $this->db->QFetchRowArray($query);
        if (!empty($rows) && is_array($rows) && count($rows) > 0) {
            return $rows;
        }

        return [];
    }

    public function getDbAgentiCentru($centru_id){
        $query = "select cod_ag as id, nume_ag as denumire from agenti where cod_centru={$centru_id} and activ = 1 order by 2";

        $rows = $this->db->QFetchRowArray($query);
        if (!empty($rows) && is_array($rows) && count($rows) > 0) {
            return $rows;
        }

        return [];
    }

    public function getDbRuteCentru($centru_id){
        $query = "select id, denumire, centru, centre_destinatie from rute where centru = {$centru_id} and activ = 1 order by 2";

        $rows = $this->db->QFetchRowArray($query);
        if (!empty($rows) && is_array($rows) && count($rows) > 0) {
            return $rows;
        }

        return [];
    }

    public function sendDbBorderou($data){
        $inserted = $this->insertCoduri($data);
        $response = json_encode(['error'=>0, 'records'=>$inserted, 'message'=>'Au fost importate '.$inserted.' coduri']);
        return ['response' => $response , 'error'=> "" ];
    }

    private function insertCoduri($data)
	{
        $arrExpsForSms = [];
		$ret=0;
		$user = $data['user'];
        $centru = $data['centru'];
        $scanner = "Statie scanare {$centru}";
		$dataScan = $data['data'];
        $objDataScan = DateTime::createFromFormat('Y-m-d H:i:s', $dataScan);
		$coduri = $data['coduri'];

        if(!is_array($coduri) || count($coduri) == 0) return 0;

		$tip=0;
		if(isset($data['tip'])) $tip = intval($data['tip']);
		$status=0;
		if(isset($data['status'])) $status = intval($data['status']);
		if($tip == 5 || $tip == 7) {
            $tip = $status;
            $status = 1;
		}

		$curier=0;
		if(isset($data['curier'])) $curier = intval($data['curier']);
		if($tip==1 || $tip==2) $curier=0;

		$ruta=0;
		if(isset($data['ruta'])) $ruta = $data['ruta'];
		if($tip==3 || $tip==4 || $tip==5 || $tip==6) $ruta=0;

		$borderou = $this->db->QueryInsert('scanari_borderouri', ['data'=>$dataScan, 'curier'=>$curier, 'tip'=>$tip]);

        //'cod', 'centru', 'scanner', 'curier', 'data', 'ruta', 'tip', 'status', 'user', 'borderou'
		$arr_cod = ['centru'=>$centru, 'scanner'=>$scanner, 'curier'=>$curier, 'ruta'=>$ruta, 'tip'=>$tip, 'status'=>$status, 'user'=>$user, 'borderou'=>$borderou];
        $expeditiiForUpdate = [];
		foreach ($coduri as $cod) {
			$parts = explode('|', $cod);
			if(count($parts) >= 2) {
				if(false !== ($objDataScan->setTimestamp($parts[1])))
                    $arr_cod['data'] = $objDataScan->format('Y-m-d H:i:s');
				else
                    $arr_cod['data'] = $dataScan;
                $arr_cod['cod'] = $parts[0];
			}
			else
			{
				$arr_cod['data'] = $dataScan;
                $arr_cod['cod'] = $cod;
			}
            
            if(ExpeditieDto::isAwb($arr_cod['cod'])){
                $expeditiiForUpdate[] = $arr_cod['expeditie'] = intval($arr_cod['cod']);
                $arr_cod['is_awb'] = 1;
                if($arr_cod['tip'] == 4) $arrExpsForSms[] = $arr_cod['expeditie'];
            }
            else if(ExpeditieDto::isPuisor($arr_cod['cod'])) {
                $expeditiiForUpdate[] = $arr_cod['expeditie'] = ExpeditieDto::getAwbFromPuisor($arr_cod['cod']);
                $arr_cod['is_awb'] = 0;
            }
            else $arr_cod['expeditie'] = 0;
            
        	$this->db->QueryInsert('scanari_coduri', $arr_cod);
            $ret++;
		}
        //dezanulare expeditii
        if(count($expeditiiForUpdate) > 0) {
            $this->db->QueryUpdate('exp_prelucrate', 
                [
                    'anulata' => 0, 
                    'deleted_at' => null, 
                    'deleted_by' => 0,
                    'last_ckp' => $tip,
                    'centru_last_ckp' => $centru,
                    'data_last_ckp' => $arr_cod['data'],
                ], "expeditie IN (".implode(',', $expeditiiForUpdate).")");
        }

		//trimite borderou fcm if tip == 4
		if($tip == 4 && $ret > 0) {
			//cauta agent
            $query = "select fcm_token, telefon from agenti where cod_ag = {$curier}";//order by date desc limit 1
            $agent = $this->db->QFetchArray($query);
            if(empty($agent) || empty($agent['fcm_token'])) return false;
            $sDeviceToken = $agent['fcm_token'];

            $aPayload = array(
				'data' => array(
                    "messageType" => 4,
                    "id" => $borderou
                )
			);
			$aOptions = array(
				'priority' => 'high',
				'ttl' => 3600
			);

            $this->fcmSend("insertCoduri", $sDeviceToken, $aPayload, $aOptions);

            //trimite SMS pentru expeditiile din borderou : log sms : increment sms la expeditii : adauga la valoare_totala_expeditii
            if(count($arrExpsForSms) > 0) {
                $strExpsForSms = implode(',', $arrExpsForSms);
                $query = "SELECT ep.cod_expeditie, ep.expeditie, ep.expeditor_id, ep.destinatar_id, ep.platitor_id,
                ep.tip_exp, ep.sms, ep.valoare_expeditie, ep.valoare_totala_expeditie, ep.procTva, ep.mod_plata, ep.pret_impus,
                ep.anulata, ep.idfact, ep.operatiune, ep.ramburs, ep.destinatar_telefon,
                cle.nume as expeditor_nume, cld.nume as destinatar_nume,
                clp.nume as platitor_nume, clp.master as platitor_master_id,
                clp.tarif as platitor_contract, clp.mod_plata as platitor_mod_plata,
				clpm.tarif as platitor_master_contract, clpm.mod_plata as platitor_master_mod_plata,
                tclp.tarif_sms as platitor_tarif_sms, tclpm.tarif_sms as platitor_master_tarif_sms
                FROM exp_prelucrate ep
                LEFT JOIN clienti cle ON ep.expeditor_id = cle.cod_cl
                LEFT JOIN clienti cld ON ep.destinatar_id = cld.cod_cl
                LEFT JOIN clienti clp ON ep.platitor_id = clp.cod_cl
                LEFT JOIN clienti clpm ON clp.master = clpm.cod_cl
                LEFT JOIN tarife tclp ON tclp.id_cl = ep.platitor_id
                LEFT JOIN tarife tclpm ON tclpm.id_cl = clpm.cod_cl
                WHERE ep.expeditie in ({$strExpsForSms}) and ep.sms <> 0
                and not exists (select * from exp_sms where exp_sms.expeditie = ep.expeditie and DATE(exp_sms.created_at) = CURDATE())
                GROUP by ep.expeditie";
                $rows = $this->db->QFetchRowArray($query);
                /*
                if(empty($rows) || !is_array($rows) || count($rows) == 0) {
                    error_log("SEND SMS : no rows after query : {$strExpsForSms}");
                    return $ret;
                }
                if(count($rows) != count($arrExpsForSms)) {
                    error_log("SEND SMS : diff count : {$strExpsForSms}");
                }
                */
                require_once "SMSPushNotification.php";
                $tarifLista = Tarif::getInstanceTarifLista($this->db);
                $tarifSms = $tarifLista->tarif_sms;
                foreach($rows as $row) {
                    if($row['anulata'] == 1) {
                        error_log("SEND SMS : {$row['expeditie']} anulata");
                        continue;
                    }
                    if($row['tip_exp'] > 0) {
                        error_log("SEND SMS : {$row['expeditie']} tip exp : {$row['tip_exp']}");
                        continue;
                    }
                    //facturata si cu factura periodica
                    if($row['idfact'] > 0 && $row['mod_plata'] > 0) {
                        //error_log("SEND SMS : {$row['expeditie']} facturata : {$row['idfact']}");
                        continue;
                    }
                    //maximum 3 sms la plata per NT
                    if($row['mod_plata'] == 0 && $row['sms'] == 3) {
                        error_log("SEND SMS : {$row['expeditie']} mod plata per NT maximum 3 SMS");
                        continue;
                    }
                    if($row['operatiune'] == 'Livrat') {
                        error_log("SEND SMS : {$row['expeditie']} livrata : {$row['operatiune']}");
                        continue;
                    }
                    if(!ExpeditieDto::isValidTelefonNumber($row['destinatar_telefon'])) {
                        error_log("SEND SMS : {$row['expeditie']} invalid telefon number : {$row['destinatar_telefon']}");
                        continue;
                    }
                    $isMaster = (empty($row['platitor_master_id']) || $row['platitor_master_id'] > 0 && $row['platitor_master_id'] == $row['platitor_id']);
                    if(($isMaster && (empty($row['platitor_contract']) || empty($row['platitor_mod_plata']) || empty($row['platitor_tarif_sms'])))
                        || (!$isMaster && (empty($row['platitor_master_contract']) || empty($row['platitor_master_mod_plata']) || empty($row['platitor_master_tarif_sms'])))) {
                        //tarif de lista
                        //error_log("SEND SMS : tarif de lista : {$row['expeditie']}");
					}
                    else {
                        $tarifSms = $isMaster ? (empty($row['platitor_tarif_sms']) ? $tarifLista->tarif_sms : $row['platitor_tarif_sms'])
                            : (empty($row['platitor_master_tarif_sms']) ? $tarifLista->tarif_sms : $row['platitor_master_tarif_sms']);
                    }
                    //send sms
                    $smsService = new SMSPushNotification(getenv('SMS_USER'), getenv('SMS_PWD'));
                    $textToSend = "DSC va livreaza azi expeditia cu nr. {$row['expeditie']}, de la {$row['expeditor_nume']}.";
                    if($row['ramburs'] > 0) $textToSend .= " Ramburs: {$row['ramburs']} LEI.";
                    if(!empty($agent['telefon'])) $textToSend .= " Nr. tel curier: {$agent['telefon']}";
                    $aPayload = [
                        "destination" => $row['destinatar_telefon'],
                        "text" => $textToSend
                    ];
                    try {
                        $aResult = $smsService->sendSMS($aPayload);
                        if(!$aResult['success']) {
                            //log only
                            $this->db->QueryInsert('exp_sms', ['expeditie' => $row['expeditie'], 'agent_id' => $curier, 'destination' => $row['destinatar_telefon'],
                                'msg_id' => 0, 'msg_status' => $aResult['success'], 'msg_log' => "{$aResult['code']} : {$aResult['text']}", 'src' => 1]);
                            continue;
                        }
                        //success
                        $this->db->QueryInsert('exp_sms', ['expeditie' => $row['expeditie'], 'agent_id' => $curier, 'destination' => $row['destinatar_telefon'],
                                'msg_id' => $aResult['msg_id'], 'msg_status' => $aResult['success'], 'msg_log' => "{$aResult['code']} : {$aResult['text']}", 'src' => 1]);
                        //tarifare
                        if($row['sms'] == -1) {
                            $row['sms'] = 0;
                        }
                        $row['sms']++;

                        if($row['mod_plata'] > 0 && $row['pret_impus'] == 0) {
                            $row['valoare_expeditie'] = round($row['valoare_expeditie'] + $tarifSms, 2);
                            $row['valoare_totala_expeditie'] = round($row['valoare_totala_expeditie'] + $tarifSms, 2);
                            $row['tva'] = round(($row['valoare_totala_expeditie'] * $row['procTva'] / 100), 2);
                            $this->db->QueryUpdate('exp_prelucrate', [
                                'valoare_expeditie' => $row['valoare_expeditie'], 'valoare_totala_expeditie' => $row['valoare_totala_expeditie'],
                                'sms' => $row['sms'], 'tva' => $row['tva']
                            ], "cod_expeditie = {$row['cod_expeditie']}");
                        }
                        else
                            $this->db->QueryUpdate('exp_prelucrate', ['sms' => $row['sms']], "cod_expeditie = {$row['cod_expeditie']}");
                    }
                    catch (Exception $ex){
                        error_log("ScanPort : error send sms : ".$ex->getMessage());
                    }
                }
            }
		}

		return $ret;
	}
//$this->api_user = '50006F0063006B0065007400500043000000';
//$this->api_password = '444556494345454D00';
}
