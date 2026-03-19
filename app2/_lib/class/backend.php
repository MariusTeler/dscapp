<?php
require_once "expeditieDto.php";
require_once "CdsGeocoder.php";
require_once "FCMPushNotification.php";
require_once "SendEmailMailGun.php";

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class BackEnd {

    protected $pass_limit = 10;

    /**
     * User logged in or not
     *
     * @var string
     * @access public
     * @see Authenticate()
     */
    protected $user_id = 0;

    /**
     * User's PROFILE:
     * 10 = Administrator
     * 1 = User
     * 0 = No User Logged In
     *
     * @var string
     * @access public
     * @see Authenticate()
     */
    protected $user_profile = 0;

    /**
     * User's RIGHTS:
     **/
    protected $user_rights = [];

    /**
     * User Centru:
     * 47 = Bucuresti
     * @var integer
     * @access protected
     * @see Authenticate()
     */
    protected $user_centru_id = 0;

    /**
     * Is Master:
     * @var boolean
     * @access protected
     * @see Authenticate()
     */
    protected $is_master = false;


    /**
     * Global array used to store variables to be parsed into a file or for a MySQL Query
     * Used as:  $this->vars["variabile"] = "value";
     *
     * @var array
     * @access public
     * @see SetVars()
     */
    protected $vars = [];

    /**
     * Pointer to the opened database connection
     *
     * @var pointer
     * @access public
     * @see BackEnd()
     */
    protected $db = 0;

    /**
     * Array that keeps the real names of the tables used
     * Usage:  ... $this -> tables["table_name"] ...
     *
     * @var array
     * @access public
     * @see BackEnd()
     */
    protected $tables = 0;

    /**
     * Array that keeps the real path to where the layouts are stored
     * Usage:  ... $this -> paths["name"] ...
     *
     * @var array
     * @access public
     * @see BackEnd()
     */
    protected $paths = 0;

    /**
     * Array that keeps all configuration vars pased to the constructor. Used to initiate other stand alone classes.
     *
     * @var array
     * @access public
     * @see BackEnd()
     */
    protected $config = 0;

    /**
     * Flag, enables ON / OFF for debbuging mode
     * 1 - ON   /   0 - Off
     *
     * @var integer-boolean
     * @access public
     * @see BackEnd()
     */
    protected $DEBUG = 0;

    protected $parola_expirata = false;

    protected $coduri_generate = 0;

    /**
     * Sources
     *
     * @var string
     * @access public
     */
    protected $source = 'site_index.html';
    protected $source_user = 'user_index.html';
	protected $source_client = 'client_index.html';

    /**
     * TVA
     *
     * @var double
     * @access public
     */
    public $procTva;
    const TVA_PROCENT = 19;

    const APP_LOG_FILE = "/var/www/html/app/logs/app.log";

    const TIP_SCANARE_INC = 1; //intrare centru
    const TIP_SCANARE_OUC = 2; //iesire centru
    const TIP_SCANARE_INA = 3; //intrare agent
    const TIP_SCANARE_OUA = 4; //iesire agent
    const TIP_SCANARE_COK = 5; //expeditie livrata
    const TIP_SCANARE_PLP = 6;
    const TIP_SCANARE_ESN = 7;
    const TIP_SCANARE_EMA = 8;
    const TIP_SCANARE_CRT = 9; //expeditie returnata la expeditor
    const TIP_SCANARE_ECG = 10;
    const TIP_SCANARE_EAI = 11;
    const TIP_SCANARE_ESF = 12;
    const TIP_SCANARE_ENL = 13;
    const TIP_SCANARE_EPR = 14;
    const TIP_SCANARE_EEA = 15;
    const TIP_SCANARE_CLD = 16;
    const TIP_SCANARE_ESJ = 17;
    const TIP_SCANARE_SCD = 18;
    const TIP_SCANARE_ECS = 20;
    const TIP_SCANARE_EIA = 21;
    const TIP_SCANARE_PCC = 22;
    const TIP_SCANARE_PLS = 23;
    const TIP_SCANARE_REM = 24; //returnare expeditie catre magazie
    const TIP_SCANARE_NOC = 25;
    const TIP_SCANARE_ESG = 26;
    const TIP_SCANARE_ECO = 27;
    const TIP_SCANARE_EDT = 28; //deteriorata
    const TIP_SCANARE_RW  = 29;
    const TIP_SCANARE_ESI = 30;


    const CRISTINA_IANCU = 31;
    const OVIDIU_LEPADATU = 45;
    const CRINA_TORDAI = 129;
    const MIRELA_LACO = 3321;
    const BOGDAN_MNEITA = 223;
    const MIHALCEA = 729;
    const MARIAN = 929;
    const MADALIN = 1420;
    const MADALINP = 2192;
    const VALIDINICA = 6818;
    const KAUFMES = 9716;
    const MARIUS_TELER = 17369;
    const DOINA = 148;
    const COLAREZ = 282;
    const DENISA = 6942;
    const OANA_MANOLACHE = 17454;
    const CAMELIA_TELER = 19433;

    const CAN_MODIFY_RBS = [self::MARIAN, self::BOGDAN_MNEITA, self::DOINA, self::MARIUS_TELER, self::MIRELA_LACO, self::CRISTINA_IANCU, self::DENISA, self::OVIDIU_LEPADATU, self::CRINA_TORDAI];
    const CAN_MODIFY_STATUS_RBS_VALIDAT = [self::VALIDINICA, self::MARIUS_TELER, self::DENISA, self::MARIAN];
    const CAN_FINANCIAR_CENTRE = [self::VALIDINICA, self::MARIUS_TELER, self::DENISA, self::MARIAN, self::OANA_MANOLACHE, self::MADALIN];
    const CAN_MODIFY_STATUS_RBS_INCHIS = [self::MARIUS_TELER, self::DENISA];
    const CAN_MODIFY_STATUS_RBS_APROBAT = [self::MARIUS_TELER, self::DENISA, self::MARIAN];
    const CAN_MODIFY_GEOCODE_FINANCIAR = [self::MARIUS_TELER, self::MARIAN];
    const CAN_MODIFY_SALARII_FINANCIAR = [self::VALIDINICA, self::MARIUS_TELER, self::MARIAN];

    const MIN_COLETE = 1;
    const MAX_COLETE = 99;
    const MIN_KG_COLET = 1;
    const MIN_KG_PALET = 10;
    const MAX_KG_COLET = 10000;
    const MAX_KG_PALET = 10000;
    const MAX_ASIGURARE = 15000;


    const MIN_BO_RBS_CASH = 3; //minim de expeditii pentru a crea o consolidare ramburs cash
    const TIP_EXP_BO_RBS_CASH = 33; //tipul de expeditie pentru borderoun ramburs cash

    const ALLOWED_TIP_EXP = [0, 1, 2, 3, 5, 6, 7]; //tipuri de expeditii care pot fi create, modificate

    const STATUS_RAMBURS = [
    	0 => 'In derulare', //la primul scan
		1 => 'Inchis', // CRON s-a livrat expeditia de ramburs (cash) | MANUAL s-a platit rambursul CC | MANUAL s-a livrat expeditia de returnare
		2 => 'Validat', // MANUAL : banii au ajuns in Bucuresti (CASH SAU CC)
		4 => 'Spre client', // CASH : CRON : orice scan pe care il ia expeditia in afara centrului de origine al expeditiei de tip ramburs sau returnare
		5 => 'Anulat',
		6 => 'Litigiu',
		7 => 'Pierdut',
		10 => 'Nepreluat', // initiala nepreluata (niciun scan)
		11 => 'Spre Compensare',
		21 => 'On Hold',
		23 => 'Decontat', // AUTOMAT IN DECONT : DCL : decontul la sfarsitul zilei (CASH SAU CC)
		24 => 'LaPlata',
		25 => 'Nesosit',
		26 => 'Abandonat',
		27 => 'Compensat',
		30 => 'Aprobat', // MANUAL : aprobat pentru rambursare
		//31 => 'Returnat' // initiala returnata
    ];

//----------------------- c o n s t r u c t o r ----------------------------------------------------------

    /**
     * The constructor for the 'Backend' class sets the default values for some basic vars,
     * initiates MySQL connection
     *
     * @param array $config  an array with the configuration params
     * @access public
     * @see $config
     * @see $tables
     * @see $db
     * @see $DEBUG
     * @see SetVars()
     */
    function __construct($config = 0) {
        date_default_timezone_set('Europe/Bucharest');
        $this->config = $config;
        if ($config) {
            $this->tables = $config["tables"];
            $this->paths = $config["paths"];
            $this->db = new MysqlPDO();
            $this->DEBUG = $config["DEBUG"];
        }

        $this->procTva = $this->getProcentTVA(date("Y-m-d"));

        if($this->Authenticate() === false) {
            $this->Logout(false);
            $isModal = intval($_POST["fromModal"] ?? 0);
            if($isModal == 1)
                header("HTTP/1.0 401 Unauthorized");
        }
        $this->SetVars();
        if ((is_array($_POST)) && (sizeof($_POST) > 0))
        	$_POST = $this->CorectFields($_POST);
    }

//-------------------- a u t h e n t i c a t e ---------------------------------------------

    /**
     * Authenticates User
     *
     * @access public
     */
    function Authenticate(){
        if( (isset($_SESSION["user"])) && is_array($_SESSION["user"]) && (!empty($_SESSION["user"]["id"])) && (!empty($_SESSION["user"]["profile"])) ) {
            //ALREADY LOGED IN
            $this->user_id = $_SESSION["user"]["id"];
            $this->user_profile = $_SESSION["user"]["profile"];
            $this->user_centru_id = $_SESSION["user"]["centru_id"] ?? 0;
            $this->is_master = $_SESSION["user"]["is_master"] ?? false;
            return true;
        }
        else if(isset($_POST["login_user"]) && isset($_POST["login_pass"])){
                //USER&PASS SUBMITTED
            $auth = $this->ValidateAdmin_MySQL($this->sanitize($_POST["login_user"]) , $this->sanitize($_POST["login_pass"]));

            if ($auth === false && $this->parola_expirata === true) {
                $user_login = $this->sanitize($_POST["login_user"] ?? "");
                $new_pass = $this->sanitize($_POST["login_pass_new"] ?? "");
                $new_pass_verify = $this->sanitize($_POST["login_pass_new_verify"] ?? "");

                // Schimbare parola expirata
                if (empty($new_pass) || empty($new_pass_verify)){
                    $this->vars["login_error"] = $this->Error('Parola expirata.<br/> Completeaza toate campurile de mai jos', 2);
                    return false;
                }

                if ($new_pass != $new_pass_verify){
                    $this->vars["login_error"] = $this->Error('Parola expirata.<br/> Parola noua diferita. Completeaza toate campurile de mai jos', 2);
                    return false;
                }

                if (strlen($new_pass_verify) < $this->pass_limit){
                    $this->vars["login_error"] = $this->Error('Parola expirata.<br/> Parola noua trebuie sa aibe minim '.$this->pass_limit.' caractere', 2);
                    return false;
                }

                if(str_contains(strtolower($new_pass_verify), strtolower($user_login))){
                    $this->vars["login_error"] = $this->Error('Parola expirata.<br/> Parola noua nu poate contine numele utilizatorului', 2);
                    return false;
                }

                if($new_pass == $_POST['login_pass']){
                    $this->vars["login_error"] = $this->Error('Parola expirata.<br/> Parola noua trebuie sa fie diferita de cea veche', 2);
                    return false;
                }

                $this->db->QueryUpdate('users',
                    [
                        'hashParola' => password_hash($new_pass_verify, PASSWORD_DEFAULT),
                        'data_parola' => date('Y-m-d H-i-s')
                    ],
                    " user = '" . $user_login."'");

                return $this->ValidateAdmin_MySQL($user_login , $new_pass_verify);
            }

            if($auth === false) {
                $cnp = $this->ValidateUserScan_MySQL($this->sanitize($_POST["login_user"]));
                if($cnp){
                    setcookie("auto_login_cookie", $cnp, [
                        'path' => '/',
                        'domain' => $this->config["domain"],
                        'secure' => true,
                        'httponly' => true,
                        'samesite' => 'Strict',
                    ]);
                    header("Location: ".$this->config['http'].'scanport');
                    exit();
                }
            }

            if($auth === false) {
                //error message
                if($this->parola_expirata){
                    $this->vars["login_error"] = $this->Error('Parola expirata', 2);
                } else {
                    $this->vars["login_error"] = $this->Error('Date de autentificare incorecte...');
                }
                return false;
            }
        }
    }


    /**
     * Validates a user & pass against MySQL records
     *
     * @param string $user User
     * @access public
     */
    function ValidateUserScan_MySQL($user) {
        if(empty($user)) return false;

        $vars = [];
        $vars['user'] = $user;
        $vars['user_agent'] = $_SERVER['HTTP_USER_AGENT'];
        $vars['ip'] = $this->getClientIp();
        $vars['successfull'] = 0;
        $vars['date'] = date("Y-m-d H:i:s");
        //insert in DB
        $this->db->QueryInsert('login_history', $vars);
        $id = $this->db->InsertId();

        $sql_login = $this->db->QFetchArray("SELECT id, nivel_acces, centru, cnp 
            FROM users 
            WHERE autologin = :autologin AND activ = 1 AND nivel_acces = 13 limit 1", ['autologin' => $user]);
        if(empty($sql_login)) return false;
        $cnp = $sql_login['cnp'];
        if (strlen($cnp)) {
            $this->db->QueryUpdate('login_history', ['user_id' => ($sql_login['id'] ?? 0), 'successfull' => 1], "id = " . $id);
            $_SESSION["user"] = [];
            // V A L I D  L O G I N
            $this->user_id = $_SESSION["user"]["id"] = $sql_login['id'];
            $this->user_profile = $_SESSION["user"]["profile"] = $sql_login['nivel_acces'];
            $this->user_centru_id = $_SESSION["user"]["centru_id"] = $sql_login['centru'];
            $this->is_master = $_SESSION["user"]["is_master"] = false;
            return $cnp;
        }
        return false;
    }


    /**
     * Validates a user & pass against MySQL records
     *
     * @param string $user User
     * @param string $pass Password
     * @access public
     */
    function ValidateAdmin_MySQL($user, $pass) {
        if(empty($user) || empty($pass)) return false;
        //verifying if repeatedly tried to login using this username
        //loging in login_history; default is insuccessfull
        $vars = [];
        $vars['user'] = $user;
        $vars['user_agent'] = $_SERVER['HTTP_USER_AGENT'];
        $vars['ip'] = $this->getClientIp();
        $vars['successfull'] = 0;
        $vars['date'] = date("Y-m-d H:i:s");
        //insert in DB
        $this->db->QueryInsert('login_history', $vars);
        $id = $this->db->InsertId();

        $sql_login = $this->db->QFetchArray("
            SELECT id, nume, centru, telefon, nivel_acces, hashParola, expeditor_id, recantarite, selectie_puncte_de_lucru,
            print_add, print_awb, importcsv, preturi as preturi, show_master_clienti, def_obsv, def_sms,
            data_parola, datediff(now(), data_parola) as parola_veche
            FROM users
            WHERE (user like :user) AND activ = 1 limit 1", ['user' => $user]);
        if(empty($sql_login)) return false;
        if(password_verify($pass, $sql_login['hashParola']) == false) return false;
        if($sql_login['parola_veche'] >= 30 && $sql_login['nivel_acces'] != 13 && $sql_login['nivel_acces'] != 9){
            header("HTTP/1.0 409 Conflict");
            $this->parola_expirata = true;
            return false;
        }

        /*
        //parola == user
        if(str_contains(strtolower($pass), strtolower($user))){
            header("HTTP/1.0 409 Conflict");
            $this->parola_expirata = true;
            return false;
        }
        */

        if($sql_login['nivel_acces'] == 9 && $sql_login['expeditor_id'] == 0)
            return false;

        $this->db->QueryUpdate('login_history', ['user_id' => ($sql_login['id'] ?? 0), 'successfull' => 1], "id = " . $id);
        //new login : regenerate session id
        session_regenerate_id(true);
        $_SESSION['last_regeneration'] = time();

        $_SESSION["user"] = [];
        $_SESSION["expeditor"] = [];
        $_SESSION["master"] = [];
        // V A L I D  L O G I N
        $this->user_id = $_SESSION["user"]["id"] = $sql_login['id'];
        $this->user_profile = $_SESSION["user"]["profile"] = $sql_login['nivel_acces'];
        $this->user_centru_id = $_SESSION["user"]["centru_id"] = $sql_login['centru'];
        $this->is_master = $_SESSION["user"]["is_master"] = false;

        $_SESSION["user"]["nume"] = $sql_login['nume'];
        $_SESSION["user"]["telefon"] = $sql_login['telefon'];
        $_SESSION["user"]["print_awb"] = $sql_login['print_awb'];
        $_SESSION["user"]["print_add"] = $sql_login['print_add'];
        $_SESSION["user"]["preturi"] = $sql_login['preturi'];

        if($this->user_profile == 9) {
            $sql_client = $this->db->QFetchArray("
                SELECT lce.nume_lc as expeditor_localitate, lce.cod_jd as expeditor_judet, lce.dist_km as expeditor_localitate_km,
                cle.cod_cl as expeditor_id, cle.master as expeditor_master_id, cle.cod_lc as expeditor_localitate_id, 
                IF(cle.zona_id > 0 and clec.id > 0, clec.id, lce.cod_centru) as expeditor_centru_id,
                cle.nume as expeditor, cle.adresa as expeditor_adresa, cle.tarif as expeditor_contract, cle.mod_plata as expeditor_mod_plata,
                cle.cc as expeditor_cc, cle.icc as expeditor_icc, cle.cod_fiscal as expeditor_cui, cle.reg_com as expeditor_j, cle.not_print_phone as expeditor_not_print_phone,
                IF(cle.zona_id > 0 and clec.id > 0, clec.nume, cee.nume) as expeditor_centru,
                IF(cle.zona_id > 0 and clec.id > 0, clec.label, cee.label) as expeditor_centru_cod,
                cle.tarif_individual as expeditor_tarif_individual, cle.activ as expeditor_activ,
                clem.nume as expeditor_master, clem.tarif as expeditor_master_contract, clem.mod_plata as expeditor_master_mod_plata,
                clem.cc as expeditor_master_cc, clem.activ as expeditor_master_activ, clem.cod_fiscal as expeditor_master_cui, clem.reg_com as expeditor_master_j, clem.not_print_phone as expeditor_master_not_print_phone,
                clet.taxa_destinatie as expeditor_taxa_destinatie, clet.taxa_expediere as expeditor_taxa_expediere, clet.ret_amb as expeditor_ret_amb, clet.kg_ret_amb expeditor_kg_ret_amb,
                clet.tarif_sms as expeditor_tarif_sms, cmet.taxa_destinatie as expeditor_master_taxa_destinatie, cmet.taxa_expediere as expeditor_master_taxa_expediere, cmet.ret_amb as expeditor_master_ret_amb,
                cmet.kg_ret_amb as expeditor_master_kg_ret_amb, cmet.tarif_sms as expeditor_master_tarif_sms, cle.zona_id as expeditor_zona_id, cee.geocode as expeditor_geocode,
                (SELECT COUNT(tcle.id)
                    	from tarife tcle
                        LEFT JOIN tarife_det tdcle ON tdcle.id_tarife = tcle.id
                        LEFT JOIN tarife_g tgcle ON tgcle.id_tarife_det = tdcle.id
                        WHERE tgcle.tip = 1  and tcle.id_cl = cle.cod_cl) as expeditor_tarif_palet,
                (SELECT COUNT(tclem.id)
                    	from tarife tclem
                        LEFT JOIN tarife_det tdclem ON tdclem.id_tarife = tclem.id
                        LEFT JOIN tarife_g tgclem ON tgclem.id_tarife_det = tdclem.id
                        WHERE tgclem.tip = 1  and tclem.id_cl = clem.cod_cl) as expeditor_master_tarif_palet
                FROM clienti cle
                LEFT JOIN zones clez ON clez.id = cle.zona_id
        	    LEFT JOIN centre clec on clec.id = clez.centru_id
                LEFT JOIN localitati lce on cle.cod_lc = lce.cod_lc
                LEFT JOIN centre cee on lce.cod_centru = cee.id
                LEFT JOIN clienti clem on clem.cod_cl = cle.master
                LEFT JOIN tarife clet on clet.id_cl = cle.cod_cl
                LEFT JOIN tarife cmet on cmet.id_cl = clem.cod_cl
                WHERE (cle.cod_cl = :expeditor_id) and cle.activ = 1", ['expeditor_id' => $sql_login['expeditor_id']]);


            if(empty($sql_client) || empty($sql_client['expeditor_id'])) return false;

            $pcs = [];
            $this->is_master = $_SESSION["user"]["is_master"]
                            = ($sql_client['expeditor_master_id'] == 0 || $sql_client['expeditor_master_id'] == $sql_client['expeditor_id']);
            if($this->is_master)
                $pcs = $this->getIdsPuncteDeLucru($sql_client['expeditor_id']);

            //error_log($sql_client['expeditor_id']);
            //error_log($sql_client['expeditor_master_id']);

            $is_pc = $this->isPunctDeLucru($sql_client['expeditor_id'], $sql_client['expeditor_master_id']);

            //daca is pc and masterul nu e activ auth failure
            if($is_pc && empty($sql_client['expeditor_master_activ']))
                return false;

            $_SESSION["user"]["recantarite"] = $sql_login['recantarite'];
            $_SESSION["user"]["selectie_puncte_de_lucru"] = $sql_login['selectie_puncte_de_lucru'];
            $_SESSION["user"]["importcsv"] = $sql_login['importcsv'];
            $_SESSION["user"]["show_master_clienti"] = $sql_login['show_master_clienti'];
            $_SESSION["user"]["expeditor_id"] = $sql_login['expeditor_id'];
            $_SESSION["user"]["def_obsv"] = $sql_login['def_obsv'];
            $_SESSION["user"]["def_sms"] = $sql_login['def_sms'];

            $_SESSION["expeditor"]["id"] = $sql_client['expeditor_id'];
            $_SESSION["expeditor"]["master_id"] = $this->is_master ? $sql_client['expeditor_id'] : $sql_client['expeditor_master_id'];
            $_SESSION["expeditor"]["is_pc"] = $is_pc;

            $_SESSION["expeditor"]["localitate_id"] = $sql_client['expeditor_localitate_id'];
            $_SESSION["expeditor"]["localitate"] = $sql_client['expeditor_localitate'];
            $_SESSION["expeditor"]["localitate_km"] = $sql_client['expeditor_localitate_km'];
            $_SESSION["expeditor"]["judet"] = $sql_client['expeditor_judet'];
            $_SESSION["expeditor"]["centru_id"] = $sql_client['expeditor_centru_id'];
            $_SESSION["expeditor"]["nume"] = $sql_client['expeditor'];
            $_SESSION["expeditor"]["adresa"] = $sql_client['expeditor_adresa'];
            $_SESSION["expeditor"]["contract"] = $sql_client['expeditor_contract'];
            $_SESSION["expeditor"]["tarif_individual"] = $sql_client['expeditor_tarif_individual'];
            $_SESSION["expeditor"]["mod_plata"] = $sql_client['expeditor_mod_plata'];
            $_SESSION["expeditor"]["cc"] = $sql_client['expeditor_cc'];
            $_SESSION["expeditor"]["icc"] = $sql_client['expeditor_icc'];
            $_SESSION["expeditor"]["centru"] = $sql_client['expeditor_centru'];
            $_SESSION["expeditor"]["centru_cod"] = $sql_client['expeditor_centru_cod'];
            $_SESSION["expeditor"]["taxa_expediere"] = $sql_client['expeditor_taxa_expediere'];
            $_SESSION["expeditor"]["taxa_destinatie"] = $sql_client['expeditor_taxa_destinatie'];
            $_SESSION["expeditor"]["ret_amb"] = $sql_client['expeditor_ret_amb'];
            $_SESSION["expeditor"]["kg_ret_amb"] = $sql_client['expeditor_kg_ret_amb'];
            $_SESSION["expeditor"]["tarif_sms"] = $sql_client['expeditor_tarif_sms'];
            $_SESSION["expeditor"]["tarif_palet"] = $sql_client['expeditor_tarif_palet'];
            $_SESSION["expeditor"]["cui"] = $sql_client['expeditor_cui'];
            $_SESSION["expeditor"]["j"] = $sql_client['expeditor_j'];
            $_SESSION["expeditor"]["not_print_phone"] = $sql_client['expeditor_not_print_phone'];

            $_SESSION["master"]["nume"] = $sql_client['expeditor_master'];
            $_SESSION["master"]["contract"] = $sql_client['expeditor_master_contract'];
            $_SESSION["master"]["mod_plata"] = $sql_client['expeditor_master_mod_plata'];
            $_SESSION["master"]["cc"] = $sql_client['expeditor_master_cc'];
            $_SESSION["master"]["taxa_expediere"] = $sql_client['expeditor_master_taxa_expediere'];
            $_SESSION["master"]["taxa_destinatie"] = $sql_client['expeditor_master_taxa_destinatie'];
            $_SESSION["master"]["ret_amb"] = $sql_client['expeditor_master_ret_amb'];
            $_SESSION["master"]["kg_ret_amb"] = $sql_client['expeditor_master_kg_ret_amb'];
            $_SESSION["master"]["tarif_sms"] = $sql_client['expeditor_master_tarif_sms'];
            $_SESSION["master"]["tarif_palet"] = $sql_client['expeditor_master_tarif_palet'];
            $_SESSION["master"]["cui"] = $sql_client['expeditor_master_cui'];
            $_SESSION["master"]["j"] = $sql_client['expeditor_master_j'];
            $_SESSION["master"]["not_print_phone"] = $sql_client['expeditor_master_not_print_phone'];
            $_SESSION["master"]["pcs"] = $pcs;

            if(empty($sql_client['expeditor_zona_id']) && !empty($sql_client['expeditor_geocode']))
                CdsGeocoder::geocode($this->db, $sql_client['expeditor_id']);
        }
        return true;
    }


    /**
     * L o g o u t
     *
     * @param integer $act  Redirect or just destroy session
     * @access public
     */
    function Logout($act = true) {
        $this->user_id = 0;
        $this->user_rights = [];
        $this->user_profile = 0;
        $this->user_centru_id = 0;
        //destroy session
        $_SESSION = [];
        // delete the session cookie by setting its expiration in the past
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $params["path"], $params["domain"],
            $params["secure"], $params["httponly"]
        );
        //destroy the session
        session_destroy();
        if($act === true)
		    $this->Go($this->config['http']);
    }

//-------------------- l a y o u t -------------------------------------------------------------

    /**
     * Redirects to the specified $url
     *
     * @param string $url Web address
     * @access public
     */
    function Go($url) {
        echo '<script> document.location="' . $url . '"; </script>';
    }

    /**
     * displays the web page
     *
     * @param string $title (optional) TITLE
     * @param string $decription (optional) META Description
     * @param string $keywords (optional) META Keywords
     * @param string $source (optional) If general layout other than 'default' or 'user'
     * @access public
     */
    function Layout($source = 0, $title = '', $description = '', $keywords = '') {
        if (!$source) {
            //login
            $source = $this->source;
            //inside
            $this->vars['onload_js_version'] = $this->config['version']['onload_js_version'];
            $this->vars['client_js_version'] = $this->config['version']['client_js_version'];
            $this->vars['retururi_js_version'] = $this->config['version']['retururi_js_version'];
            $this->vars['facturare_js_version'] = $this->config['version']['facturare_js_version'];
            $this->vars['footer_text'] = $this->config['footer']['text'];
            if ($this->user_id)
                $source = $this->source_user;

            if($this->user_profile == 9)
            	$source = $this->source_client;

        }

        //TODO update last_action datetime
        $html = $this->FileToString( __DIR__ . '/../../_tpl/'. $source);
        $html = $this->InsertVars($html, $this->vars);
        echo $html; //display page
    }

//-------------------- p a r s e r --------------------------------------------------------
    /*
     * 	{VALUE}
     */

    /**
     * Return Parsed file with corect paths into a string
     *
     * @param string $file  File to be read (container)
     * @return String
     * @access public
     */
    function Container($file, $p = 1) {
        return $this->FileToString( __DIR__ . '/../../_tpl/' . $file);
    }

    /**
     * Replaces  $vars  into  $file
     *
     * @param string $file  File to be parsed (container)
     * @param array $file  Vars that need to be inserted into file
     * @return String
     * @access public
     */
    function Parse($file, $vars = 0, $p = 1) {
        if (is_array($vars)){
            $vars = array_merge($this->vars, $vars);
        }
        else
            $vars = $this->vars;
        $vars["PHP_SELF"] = $_SERVER["PHP_SELF"];

        $html = $this->FileToString( __DIR__ . '/../../_tpl/' . $file);
        return $this->InsertVars($html, $vars);
    }

    /**
     * Replaces  {VARIABLE}  from the $html string with  $vars["variable"]
     *
     *
     * @param string $html  Source string to be parsed
     * @param array $vars  Key/Values to be parsed: ($vars[$key] = $value), where value can be a 'value' or an 'array'
     * @return string
     * @access public
     */
    function InsertVars($html, $vars) {
        //replace values
        $patterns = [];
        $replacements = [];
        if (is_array($vars)) {
            foreach ($vars as $key => $val) {
                $key = strtoupper($key);
                $patterns[] = "/{" . $key . "}/";
                $replacements[] = str_replace('$', '\$', $val);
            }
        }
        $result = @preg_replace($patterns, $replacements, $html);

        //clear all not replaced values
        $result = @preg_replace_callback('/\{(\w+)\}/',
            function ($m) {
                return "";
            }, $result);

        //return parsed result
        return $result;
    }

//-------------------- g e n e r a l --------------------------------------------------------

    /**
     * Reads a full file (with the path from $file_name) and returns its content into a string
     *
     * @param string $file_name  the path fpr the file be read
     * @return Boolean
     * @access public
     */
    function FileToString($file_name) {
        if (!file_exists($file_name))
            return "<i>Error: file '$file_name' not found ...</i>";
        return file_get_contents($file_name);
    }

    /**
     * Writes a string into a file
     *
     * @param string $file_name  File to be created-written
     * @param string $string  String to write
     * @access public
     */
    function StringToFile($file_name, $string) {
        if (!function_exists('file_put_contents') && !defined('FILE_APPEND')) {
            $f = @fopen($file_name, "w");
            if (!$f) {
                return false;
            } else {
                fwrite($f, $string);
                fclose($f);
                return true;
            }
        }
        else
            file_put_contents($file_name, $string);
    }

    function CreatePhpDate($sqlStr) {
        if(empty($sqlStr) || $sqlStr == '0000-00-00 00:00:00' || $sqlStr == '0000-00-00')
            return null;
        $result = DateTimeImmutable::createFromFormat('Y-m-d H:i:s', $sqlStr);
        if($result === false) {
        	$result = DateTimeImmutable::createFromFormat('Y-m-d', $sqlStr);
        	if($result === false)
        		return null;
        	return $result;
        }
        return $result;
    }

    /**
     * Creates a PHP Date(Time) Object from a given SQL Date(Time) String
     *
     * @return string
     * @param string $str  SQL Date(Time) string
     * @access public
     * @see TimeNos()
     */
    function CreateDate($str , $format = 'd.m.Y') {
        if($str == '0000-00-00 00:00:00' || $str == '0000-00-00')
            return '00.00.0000';
        $result = DateTime::createFromFormat('Y-m-d H:i:s', $str);
        if($result === false)
        {
        	$result = DateTime::createFromFormat('Y-m-d', $str);
        	if($result === false)
        		return date($format);
        	return $result->format($format);
        }
        return $result->format($format);
    }

    /**
     *
     * @access public
     * @see $vars
     * @see $nt4
     */
    function SetVars() {
        $this->vars["HTTP"] = $this->config['http'];
        $this->vars["APP_VERSION"] = $this->config['app_version'];
        $this->vars["APP_MESSAGE"] = 0;
        $this->vars["CHECK_SESSION_EXPIRED"] = $this->config['check_session_secunde'];
        $this->vars["PHP_SELF"] = $_SERVER["PHP_SELF"];
        $this->vars["GET"] = $this->GET();
        $this->vars['title'] = $_SESSION["user"]["nume"] ?? "";
        $this->vars["PROCENT_TVA"] = $this->getProcentTVA();
        $this->vars["ALERTA_NR_ZILE"] = intval(isset($this->config['ALERTA_NR_ZILE'])?$this->config['ALERTA_NR_ZILE']:0);
    }

     /**
     * Get the client's IP addres
     *
     * @param  boolean $checkProxy
     * @return string
     */
    private function getClientIp($checkProxy = true)
    {
        $ip = '';
	if ($checkProxy && $this->getServer('HTTP_CLIENT_IP') != null) {
            $ip = $this->getServer('HTTP_CLIENT_IP');
        }
        else if ($checkProxy && $this->getServer('HTTP_X_FORWARDED_FOR') != null) {
        	// check if multiple ips exist in var
        	$ips = $this->getServer('HTTP_X_FORWARDED_FOR');
			if (strpos($ips, ',') !== false) {
				$iplist = explode(',', $ips);
				foreach ($iplist as $ip) {
					if ($this->validateIp($ip))
						return $ip;
				}
			}
        }
        else {
            $ip = $this->getServer('REMOTE_ADDR');
        }

        if ($this->validateIp($ip)) {
            return $ip;
        }

        return isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : false;
    }

    /**
 	* Ensures an ip address is both a valid IP and does not fall within
 	* a private network range.
 	*/
	private function validateIp($ip)
	{
    	if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4 | FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) === false) {
        	return false;
    	}
    	return true;
	}

        /**
     * Retrieve a member of the $_SERVER superglobal
     *
     * If no $key is passed, returns the entire $_SERVER array.
     *
     * @param string $key
     * @param mixed $default Default value to use if key not found
     * @return mixed Returns null if key does not exist
     */
    private function getServer($key = null, $default = null)
    {
        if (null === $key) {
            return $_SERVER;
        }
        return (isset($_SERVER[$key])) ? $_SERVER[$key] : $default;
    }


    /**
     * Return a code based on curent time
     *
     * @return integer
     * @access public
     */
    function GetCod() {
        //return date("His");
        $time = microtime();
        $time = explode(" ", $time);
        return str_replace('.', '', $time[1] + $time[0]) . rand(100000, 999999);
    }

    /**
     * V a l i d a t e F i e l d s
     * Validates fields from an array if they are valid form fields
     *
     * @return integer-boolean
     * @param array $vars  $_POST from the form with fields to be verified
     * @param string $fields  String with fields (separated by comma) to be verified
     * @access private
     */
    function ValidateFields($vars, $fields) {
        if ($fields != '')
            $fields = explode(',', $fields);
        if ((is_array($fields)) && (is_array($vars))) {
            $vars = array_map('trim', $vars);
            foreach ($fields as $key => $field) {
                if (!isset($vars[$field])) {
                    //if($this -> DEBUG) echo ' Invalid field: '.$field.' ';
                    return 0;
                } else if ($vars[$field] == '') {
                    //if($this -> DEBUG) echo ' Invalid field: '.$field.' ';
                    return 0;
                }
            }
        }
        return 1;
    }

    /**
     * Return a $_GET var with a givven order index
     *
     * @return string
     * @param integer $k (optional) Index
     * @access private
     */
    function ACT($k = 0) {
        $i = 0;
        foreach ($_GET as $key => $val) {
            if ($i == $k)
                return $key;
            $i++;
        }
        return '';
    }

    /**
     * Return the $_GET string except certain actions
     *
     * @return string
     * @param integer $k (optional) Index
     * @access private
     */
    function GET($excluded = 'pag') {
        $act = '';
        if (is_array($_GET))
            for ($i = 0; $i < sizeof($_GET); $i++)
                if (($this->ACT($i) != '') && (!in_array($this->ACT($i), explode(',', $excluded . ',')) )) {
                    $act .= $this->ACT($i);
                    if (!empty($_GET[$this->ACT($i)]) && !is_array($_GET[$this->ACT($i)]))
                        $act .= '=' . $_GET[$this->ACT($i)];
                    if ($i < sizeof($_GET) - 1)
                        $act .= '&';
                }
        $act = str_replace('&&', '&', $act);
        if (substr($act, strlen($act) - 1, 1) == '&')
            $act = substr($act, 0, strlen($act) - 1);
        return $act;
    }

    /**
     * C o r e c t F i e l d s
     * Writes $_POST as hidden fields except those in the givven optional parametter
     *
     * @return string
     * @param array $vars Change content for vars of this array
     * @param string $fields  Fields not altered
     * @access public
     */
    function CorectFields($vars, $original = '') {
        $original = explode(',', $original);
        if (is_array($vars))
            foreach ($vars as $key => $val) {
                if (!in_array($key, $original)) {
                    $vars[$key] = $val;
                }
            }
        return $vars;
    }

    /**
     * E r r o r
     * Error message
     *
     * @return string
     * @param string $error  Error Message
     * @param array $vars  Vars array
     * @access private
     */
    function Error($message = '', $type = 0) {
        $vars = [];
        $vars['message'] = $message;
        $vars['type'] = $type;
        if ($type == 1)
            $vars['class'] = 'confirm';
        else if ($type == 2)
            $vars['class'] = 'warning';
        else
            $vars['class'] = 'error';
        if ($message == '')
            $vars['class'] = '';
        $vars['image_src'] = '/assets/images/admin/symbol_close_mini.png';
        return $this->Parse('error.html', $vars);
    }

    /**
     * E r r o r S m a l l
     * text error message
     *
     * @param unknown_type $message
     * @param unknown_type $type
     * @return unknown
     */
    function ErrorSmall($message = '', $type = 0) {
        //type = 1 confirm message
        //type = 0 error message
        //type = 2 login error message
        if ($type == 1) {
            return '<span style="font-weight: bold; color: green;">' . $message . '</span>';
        } else if ($type == 0) {
            return '<span style="font-weight: bold; color: red;">' . $message . '</span>';
        }
        else
            return '<span style="font-weight: bold; color: #FF0000;">' . $message . '</span>';
    }

    function getIdsPuncteDeLucru($cod_cl = 0) {
        $pcs = [];
        if($cod_cl == 0) return [];
        $sqlPcs = $this->db->QFetchRowArray("select cl.cod_cl as pc_id, cl.nume as pc_nume, cl.adresa as pc_adresa,
			lc.nume_lc as pc_localitate, cl.cod_lc as pc_localitate_id, lc.dist_km as pc_localitate_km, j.nume_jd as pc_judet
			from clienti cl
			inner join localitati lc ON lc.cod_lc = cl.cod_lc
			inner join judete j ON j.cod_jd = lc.cod_jd
			where cl.master = {$cod_cl} and cl.activ = 1 ORDER BY cl.nume");
        if(!empty($sqlPcs) && count($sqlPcs) > 0)
            foreach ($sqlPcs as $key => $row) {
                $pcs[$row['pc_id']] = [
                    'id' => $row['pc_id'],
                    'nume' => htmlspecialchars($row['pc_nume'], ENT_QUOTES),
                    'adresa' => htmlspecialchars($row['pc_adresa'], ENT_QUOTES),
                    'localitate' => htmlspecialchars($row['pc_localitate'], ENT_QUOTES),
                    'localitate_id' => $row['pc_localitate_id'],
                    'judet' => $row['pc_judet'],
                    'localitate_km' => $row['pc_localitate_km']
                ];
            }
        return $pcs;
    }

    function isPunctDeLucru($cod_cl, $master_id) {
        return !($master_id == 0 || $master_id == $cod_cl);
    }

    function ClientInfos($cod_cl = 0) {
        if($cod_cl == 0) return ['cod_cl' => 0, 'contract' => 0, 'mod_plata' => 0, 'taxa_expediere' => 1, 'taxa_destinatie' => 0, 'activ' => 0];
        $client_infos = $this->db->QFetchArray("
                SELECT cl.cod_cl, cl.activ as client_activ, cl.tarif as client_contract, cl.mod_plata as client_mod_plata,
                cl.cc as client_cc, cl.icc as client_icc,
                cl.tarif_individual as client_tarif_individual,
                clt.taxa_expediere as client_taxa_expediere, clt.taxa_destinatie as client_taxa_destinatie,
                clt.ret_amb as client_ret_amb, clt.kg_ret_amb client_kg_ret_amb, clt.tarif_sms as client_tarif_sms,
                cl.master, clm.activ as client_master_activ, clm.tarif as client_master_contract, clm.mod_plata as client_master_mod_plata,
                clm.cc as client_master_cc, clm.icc as client_master_icc,
                clmt.taxa_expediere as client_master_taxa_expediere, clmt.taxa_destinatie as client_master_taxa_destinatie,
                clmt.ret_amb as client_master_ret_amb, clmt.kg_ret_amb as client_master_kg_ret_amb,
                clmt.tarif_sms as client_master_tarif_sms
                FROM clienti cl
                LEFT JOIN clienti clm on clm.cod_cl = cl.master
                LEFT JOIN tarife clt on clt.id_cl = cl.cod_cl
                LEFT JOIN tarife clmt on clmt.id_cl = clm.cod_cl
                WHERE (cl.cod_cl = :cod_cl) and cl.activ = 1", ['cod_cl' => $cod_cl]);
        if(empty($client_infos)) return ['cod_cl' => 0, 'contract' => 0, 'mod_plata' => 0, 'taxa_destinatie' => 0, 'activ' => 0];
        $ret_client_infos = [];
        $ret_client_infos['activ'] = 0;
        //return master infos if not ...
        if($client_infos['client_tarif_individual'] == 1 || $client_infos['master'] == 0
            || $client_infos['master'] == $client_infos['cod_cl'])
        {
            $ret_client_infos['cod_cl'] = $client_infos['cod_cl'];
            $ret_client_infos['activ'] = $client_infos['client_activ'] ?? 0;
            $ret_client_infos['contract'] = $client_infos['client_contract'] ?? 0;
            $ret_client_infos['mod_plata'] = $client_infos['client_mod_plata'] ?? 0;
            $ret_client_infos['cc'] = $client_infos['client_cc'] ?? 0;
            $ret_client_infos['icc'] = $client_infos['client_icc'] ?? 0;
            $ret_client_infos['taxa_expediere'] = $client_infos['client_taxa_expediere'] ?? 0;
            $ret_client_infos['taxa_destinatie'] = $client_infos['client_taxa_destinatie'] ?? 0;
            $ret_client_infos['ret_amb'] = $client_infos['client_ret_amb'] ?? 0;
            $ret_client_infos['kg_ret_amb'] = $client_infos['client_kg_ret_amb'] ?? 0;
            $ret_client_infos['tarif_sms'] = $client_infos['client_tarif_sms'] ?? 0;
        }
        else
        {
            $ret_client_infos['cod_cl'] = $client_infos['master'];
            $ret_client_infos['activ'] = $client_infos['client_master_activ'] ?? 0;
            $ret_client_infos['contract'] = $client_infos['client_master_contract'] ?? 0;
            $ret_client_infos['mod_plata'] = $client_infos['client_master_mod_plata'] ?? 0;
            $ret_client_infos['cc'] = $client_infos['client_master_cc'] ?? 0;
            $ret_client_infos['icc'] = $client_infos['client_master_icc'] ?? 0;
            $ret_client_infos['taxa_expediere'] = $client_infos['client_master_taxa_expediere'] ?? 0;
            $ret_client_infos['taxa_destinatie'] = $client_infos['client_master_taxa_destinatie'] ?? 0;
            $ret_client_infos['ret_amb'] = $client_infos['client_master_ret_amb'] ?? 0;
            $ret_client_infos['kg_ret_amb'] = $client_infos['client_master_kg_ret_amb'] ?? 0;
            $ret_client_infos['tarif_sms'] = $client_infos['client_master_tarif_sms'] ?? 0;
        }
        return $ret_client_infos['activ'] > 0 ? $ret_client_infos : ['cod_cl' => 0, 'contract' => 0, 'mod_plata' => 0, 'taxa_destinatie' => 0, 'activ' => 0];
    }

/**
     * e s c a p e _ s t r i n g
     *
     * @return int
     * @param string $string
     * @access private
     */
    function escape_string($string) {
        return $this->sanitize($string);
    }

    function clean($vect = 0, $exclude = []) {
        if (!is_array($vect))
            return $this->escape_string($vect);
        else
            foreach ($vect as $key => $val)
                if ((!is_array($exclude)) || ( (is_array($exclude)) && (!in_array($key, $exclude)) ))
                    $vect[$key] = $this->clean($val);
        return $vect;
    }

    /**
     * validate email address
     * @access public
     * @return string
     */
    function is_valid_email_address($email) {

        $qtext = '[^\\x0d\\x22\\x5c\\x80-\\xff]';

        $dtext = '[^\\x0d\\x5b-\\x5d\\x80-\\xff]';

        $atom = '[^\\x00-\\x20\\x22\\x28\\x29\\x2c\\x2e\\x3a-\\x3c' .
                '\\x3e\\x40\\x5b-\\x5d\\x7f-\\xff]+';

        $quoted_pair = '\\x5c[\\x00-\\x7f]';

        $domain_literal = "\\x5b($dtext|$quoted_pair)*\\x5d";

        $quoted_string = "\\x22($qtext|$quoted_pair)*\\x22";

        $domain_ref = $atom;

        $sub_domain = "($domain_ref|$domain_literal)";

        $word = "($atom|$quoted_string)";

        $domain = "$sub_domain(\\x2e$sub_domain)*";

        $local_part = "$word(\\x2e$word)*";

        $addr_spec = "$local_part\\x40$domain";

        return preg_match("!^$addr_spec$!", $email) ? 1 : 0;
    }

    function bytesToMb($size) {
        $size = (int) ($size / 1024);
        if ($size > 1000) {
            $size = round(floatval($size / 1024), 1) . 'MB';
        }else
            $size .= 'kB';
        return $size;
    }

    function GenerateArr($type=1) {
        $act = '';
        $arr = [];

        if ($type == 1) {
            if (isset($_GET['act']))
                $act = strtolower($_GET['act']);
            if (($act == '/') || ($act == ''))
                return $arr;
            $arr = explode('/', $act);
            return $arr;
        }else {
            if (!empty($_GET)) {
                foreach ($_GET as $key => $val) {
                    $arr[] = $key;
                    if ($val)
                        $arr[] = $val;
                }
                return $arr;
            }
        }
    }

    function SessionExpired() {
        header("HTTP/1.0 401 Unauthorized");
        echo '<html>
                <head><title>401 Unauthorized</title><meta http-equiv="refresh" content="2;url='.$this->config['http'].'" /></head>
                <body bgcolor="white">
                    <center><h1>401 Unauthorized</h1></center>
                </body>
            </html>';
        die;
    }

    function PageNotFound() {
        $this->vars['title'] = '404 Not Found';
        header("HTTP/1.0 404 Not Found");
        $this->vars['robots'] = 'noindex, nofollow, noarchive, noodp, noydir';
        $redirect = '';
        if(!$this->user_id){
            $redirect = '<meta http-equiv="refresh" content="2;url='.$this->config['http'].'" />';
        }
        echo '<html>
                <head><title>404 Not Found</title>'.$redirect.'</head>
                <body bgcolor="white">
                    <center><h1>404 Not Found</h1></center>
                </body>
            </html>';
        die;
    }

    function PageForbidden() {
        $this->vars['title'] = '403 Forbidden';
        header("HTTP/1.0 403 Forbidden");
        echo '<html>
                <head><title>403 Forbidden</title></head>
                <body bgcolor="white">
                    <center><h1>403 Forbidden</h1></center>
                </body>
            </html>';
        die;
    }


/**
     * P r e p a r e F o r I n s e r t
     * apply the following functions: trim(), strip_tags() and addslashes() to the values of fields
     * $fields(comma separated), given as parameter, in the $vars array.
     *
     * @return array $vars modified
     * @param array $vars  array
     * @param string $fields fields from $vars, comma separated, for being striped of html tags and trimmed
     * @access public
     */
    function PrepareForInsert(&$vars, $fields) {
        $arr_fields = explode(',', $fields);
        foreach ($arr_fields as $key => $val)
            if (!empty($vars[$val])) {
                $vars[$val] = $this->sanitize($vars[$val]);
            }

        return $vars;
    }

//for create condition with jqgrid
    function Strip($value) {
	    return $value;
    }

    function constructWhere($s) {
	     $qwery = "";
	      //['eq','ne','lt','le','gt','ge','bw','bn','in','ni','ew','en','cn','nc']
	       $qopers = array(
	          'eq' => " = ",
	          'ne' => " <> ",
	          'lt' => " < ",
	          'le' => " <= ",
	          'gt' => " > ",
	          'ge' => " >= ",
	          'bw' => " LIKE ",
	          'bn' => " NOT LIKE ",
	          'in' => " IN ",
	          'ni' => " NOT IN ",
	          'ew' => " LIKE ",
	          'en' => " NOT LIKE ",
	          'cn' => " LIKE ",
	          'nc' => " NOT LIKE ");
	      if ($s) {
	         $jsona = json_decode($s, true);
	          if (is_array($jsona)) {
                    $gopr = $jsona['groupOp'];
                    $rules = [];
                    if(isset($jsona['rules']))
		                $rules = $jsona['rules'];
                    $i = 0;
		            foreach ($rules as $key => $val) {
		                $field = $this->sanitize($val['field']);
		                $op = $this->sanitize($val['op']);
		                $v = $val['data'];
		                if (isset($v) && $op) {
  			               $i++;
			                // ToSql in this case is absolutley needed
			                $v = $this->ToSql($field, $op, $v);
			                if ($i == 1)
			                   $qwery = " AND ";
			                else
			                   $qwery .= " " . $gopr . " ";
			                switch ($op) {
			                   // in need other thing
			                  case 'in' :
			                  case 'ni' :
				                    $qwery .= ' '. $field.' '. $qopers[$op] . " (" . $v . ")";
				                    break;
			                  default:
				                    $qwery .= ' '. $field.' '. $qopers[$op] . $v;
			                }
		              }
  		        }
	        }
    	  }
	      return $qwery;
    }

    function constructWhereIgnore($s, $ignore = []) {
		$qwery = "";
		 //['eq','ne','lt','le','gt','ge','bw','bn','in','ni','ew','en','cn','nc']
		  $qopers = array(
			 'eq' => " = ",
			 'ne' => " <> ",
			 'lt' => " < ",
			 'le' => " <= ",
			 'gt' => " > ",
			 'ge' => " >= ",
			 'bw' => " LIKE ",
			 'bn' => " NOT LIKE ",
			 'in' => " IN ",
			 'ni' => " NOT IN ",
			 'ew' => " LIKE ",
			 'en' => " NOT LIKE ",
			 'cn' => " LIKE ",
			 'nc' => " NOT LIKE ");
		 if ($s) {
			$jsona = json_decode($s, true);
			 if (is_array($jsona)) {
				   $gopr = $jsona['groupOp'];
				   $rules = [];
				   if(isset($jsona['rules']))
					   $rules = $jsona['rules'];
				   $i = 0;
				   foreach ($rules as $key => $val) {
					   $field = $this->sanitize($val['field']);
					   if(in_array($field, $ignore)) continue;
					   $op = $this->sanitize($val['op']);
					   $v = $val['data'];
					   if (isset($v) && $op) {
							$i++;
						   // ToSql in this case is absolutley needed
						   $v = $this->ToSql($field, $op, $v);
						   if ($i == 1)
							  $qwery = " AND ";
						   else
							  $qwery .= " " . $gopr . " ";
						   switch ($op) {
							  // in need other thing
							 case 'in' :
							 case 'ni' :
								   $qwery .= ' '. $field.' '. $qopers[$op] . " (" . $v . ")";
								   break;
							 default:
								   $qwery .= ' '. $field.' '. $qopers[$op] . $v;
						   }
					 }
				 }
		   }
		 }
		 return $qwery;
   }

    function ToSql($field, $oper, $val) {
        // we need here more advanced checking using the type of the field – i.e. integer, string, float
        switch ($field) {
            case 'a.DATA_COL':
                $val = $this->TransformDate($this->sanitize($val),'/');
                return "'".$val."'";
                break;
            default :
                if($oper=='bw' || $oper=='bn') return $this->db->escapeString($this->sanitize($val) ."%" );
                if($oper=='ew' || $oper=='en') return $this->db->escapeString($this->sanitize($val));
                if($oper=='cn' || $oper=='nc') return $this->db->escapeString($this->sanitize($val) . "%");
                return $this->db->escapeString($this->sanitize($val));
        }
    }

    function GetFieldValue($table, $field, $cond) {
        $result = $this->db->QFetchArray("SELECT {$field} FROM " . $this->tables[$table] . " WHERE {$cond} LIMIT 1");
        return $result[$field] ?? '';
    }

    function TransformDate($str , $sep='.') {
    	$old_format = 'd.m.Y';
    	if($sep == '/') $old_format = 'd/m/Y';
        $result = DateTime::createFromFormat($old_format, $str);
        if($result === false) {
        	return date('Y-m-d');
        }
        return $result->format('Y-m-d');
    }

    function TransformDateHours($str , $sep = '.') {
        $old_format = 'd.m.Y H:i:s';
    	if($sep == '/') $old_format = 'd/m/Y H:i:s';
        $result = DateTime::createFromFormat($old_format, $str);
        if($result === false)
        {
        	$old_format = 'd.m.Y';
    		if($sep == '/') $old_format = 'd/m/Y';
        	$result = DateTime::createFromFormat($old_format, $str);
        	if($result === false)
        		return date('Y-m-d H:i:s');
        	return $result->format('Y-m-d H:i:s');
        }
        return $result->format('Y-m-d H:i:s');
    }

   function GetCentre(){
     	$centre = [];
		$query = "SELECT id, nume FROM centre ORDER BY nume";
		$sql = $this->db->QFetchRowArray($query);
		if(!empty($sql) && is_array($sql))
      	{
  		  foreach ($sql as $key => $row) {
  			    $centre[$row['id']] = $row['nume'];
  		  }
      	}
		return $centre;
    }

   function GetOperatori(){
     	$operatori = [];
		$query = "SELECT id, user FROM users WHERE ACTIV=1";
		$sql = $this->db->QFetchRowArray($query);
		if(!empty($sql) && is_array($sql))
      	{
			foreach ($sql as $key => $row) {
				$operatori[$row['id']] = $row['user'];
			}
		}
		return $operatori;
    }

   function DrepturiUtilizatori(){
		$ascuns = 'style="display:none;"';

		for($i=1;$i<20;$i++){
			$this->vars['MENIU_'.$i] = $ascuns;
			for($j=1;$j<=20;$j++){
				$this->vars['MENIU_'.$i.'_'.$j] = $ascuns;
			}
		}

		$this->user_rights = $this->GetDrepturiUtilizator($this->user_profile);

		//meniu in functie de drepturi
		for($i=1;$i<8;$i++){
			$flag[$i] = 0;
		}

		//Expeditii
		if( in_array('cautare',$this->user_rights)){
			$this->vars['MENIU_1_1'] = '';//cautare
			$flag[1] = 1;
		}
		if( in_array('introducere',$this->user_rights)){
			$this->vars['MENIU_1_2'] = '';//introducere
			$flag[1] = 1;
		}
		if( in_array('editare',$this->user_rights)){
			$this->vars['MENIU_1_10'] = '';//editare
			$flag[1] = 1;
		}
		if( in_array('stergere',$this->user_rights)){
			$this->vars['MENIU_1_12'] = '';//stergere
			$flag[1] = 1;
		}
		if( in_array('confirmare',$this->user_rights)){
			$this->vars['MENIU_1_3'] = '';//urmarire
			$this->vars['MENIU_1_9'] = '';//confirmare
			$flag[1] = 1;
        }
        if( in_array('istoric_scanare',$this->user_rights)){
			$this->vars['MENIU_1_18'] = '';//istoric livrari
			$flag[1] = 1;
        }
        if( in_array('recantarire',$this->user_rights)){
            $this->vars['MENIU_15'] = '';
            $this->vars['MENIU_15_1'] = '';//recantarire
            if($this->user_id == self::MIHALCEA || $this->user_id == self::DOINA || $this->user_id == self::MARIAN)
                $this->vars['MENIU_15_2'] = '';
            if($this->user_id == self::DOINA || $this->user_id == self::MARIAN)
                $this->vars['MENIU_15_3'] = '';
            $flag[15] = 1;
		}
        if( in_array('vrecantarire',$this->user_rights)){
            $this->vars['MENIU_15'] = '';
            $this->vars['MENIU_15_3'] = '';
            $flag[15] = 1;
		}
		if( in_array('liste_expeditii',$this->user_rights)){
			$this->vars['MENIU_1_4'] = '';//liste expeditii
			$flag[1] = 1;
		}
		if( in_array('rapoarte_traseu',$this->user_rights)){
			$this->vars['MENIU_1_5'] = '';//rapoarte traseu
			$flag[1] = 1;
		}

        if( in_array('expeditii_clienti',$this->user_rights)){
            $this->vars['MENIU_1_17'] = '';// modificare expeditii clienti , sterge si recupereaza expeditii
            $flag[1] = 1;
        }
		if( in_array('urmarire_rambursuri',$this->user_rights)){
			$this->vars['MENIU_1_8'] = '';//urmarire retururi
			$flag[1] = 1;
			$this->vars['MENIU_7_2'] = '';//urmarire_rambursuri
			$flag[7] = 1;
		}

        if( in_array('creare_printare_note_rambursuri',$this->user_rights)){
            $this->vars['MENIU_7_7'] = '';// creare tiparire
            $flag[7] = 1;
        }
		if( in_array('print_ch_ramburs',$this->user_rights)){
			$this->vars['MENIU_7_4'] = '';//printare chitante RBS
			$flag[7] = 1;
		}
        if( in_array('rbs_validate',$this->user_rights)){
			$this->vars['MENIU_7_5'] = '';//RBS validate
			$flag[7] = 1;
		}

		if( in_array('vizualizare_rambursuri',$this->user_rights)){
			$this->vars['MENIU_7_1'] = '';//vizualizare_rambursuri
			$this->vars['MENIU_1_11'] = '';//vizualizare retururi
			$flag[7] = 1;
            $flag[1] = 1;
		}

		if( in_array('importuri',$this->user_rights)){
			$this->vars['MENIU_1_13'] = '';//Import Expedii
			$flag[1] = 1;
		}
		if( in_array('importuri',$this->user_rights)){
			$this->vars['MENIU_1_14'] = '';//Import Destinatari
			$flag[1] = 1;
		}


		//Comenzi
		if( in_array('comenzi_preluare',$this->user_rights)){
			$this->vars['MENIU_2_1'] = '';//preluare
			$flag[2] = 1;
		}
		if( in_array('comenzi_distribuire',$this->user_rights)){
			$this->vars['MENIU_2_2'] = '';//distribuire
			$flag[2] = 1;
		}
		if( in_array('comenzi_borderouri',$this->user_rights)){
			$this->vars['MENIU_2_3'] = '';//borderouri
			$flag[2] = 1;
		}

        //Editare
        if( in_array('banci',$this->user_rights)){
			$this->vars['MENIU_3_10'] = '';//banci
			$flag[3] = 1;
		}
		if( in_array('clienti',$this->user_rights)){
			$this->vars['MENIU_3_1'] = '';//clienti
            if($this->user_profile == 10)
                $this->vars['MENIU_3_13'] = '';//alocare clienti
			$this->vars['MENIU_3_5'] = '';//tarif de lista
			$flag[3] = 1;
		}
		if( in_array('localitati',$this->user_rights)){
			$this->vars['MENIU_3_2'] = '';//localitati
			$flag[3] = 1;
		}
		if( in_array('centre',$this->user_rights)){
			$this->vars['MENIU_3_3'] = '';//centre
			$flag[3] = 1;
        }
        if( in_array('checkpoints',$this->user_rights)){
			$this->vars['MENIU_3_12'] = '';//checkpoints
			$flag[3] = 1;
		}
        if( in_array('rute',$this->user_rights)){
			$this->vars['MENIU_3_14'] = '';//rute
			$flag[3] = 1;
		}
		if( in_array('agenti',$this->user_rights)){
			$this->vars['MENIU_3_4'] = '';//agenti
			$this->vars['MENIU_3_9'] = '';//agenti vanzare
			$flag[3] = 1;
		}
		if( in_array('operatori',$this->user_rights)){
			$this->vars['MENIU_3_6'] = '';//operatori
			$flag[3] = 1;
		}
        if(in_array('geocoder_address', $this->user_rights) || in_array($this->user_id, self::CAN_MODIFY_GEOCODE_FINANCIAR)){
            $this->vars['MENIU_8_11'] = '';
            $flag[8] = 1;
        }

		//Centralizari
		if( in_array('activitate_centre',$this->user_rights)){
			$this->vars['MENIU_4_1'] = '';//activitate_centre
			$flag[4] = 1;
		}
		if( in_array('colectari',$this->user_rights)){
			$this->vars['MENIU_4_2'] = '';//colectari
			$flag[4] = 1;
		}
		if( in_array('livrari',$this->user_rights)){
			$this->vars['MENIU_4_3'] = '';//livrari
			$flag[4] = 1;
		}
		if( in_array('rulaj_clienti',$this->user_rights)){
			$this->vars['MENIU_4_4'] = '';//rulaj_clienti
			$flag[4] = 1;
		}
		if( in_array('rapoarte_centre',$this->user_rights)){
			$this->vars['MENIU_4_6'] = '';//rulaj_clienti
			$flag[4] = 1;
		}
        if($this->user_id == self::DENISA || in_array('rapoarte_export',$this->user_rights)){
			$this->vars['MENIU_4_8'] = '';//rapoarte
			$flag[4] = 1;
		}
		//Facturi
		if( in_array('incasari_incasari',$this->user_rights)){
			$this->vars['MENIU_5_1'] = '';//verificari
			$flag[5] = 1;
		}
		if( in_array('incasari_restante',$this->user_rights)){
			$this->vars['MENIU_5_2'] = '';//restante
			$flag[5] = 1;
		}
		if( in_array('facturare',$this->user_rights)){
			$this->vars['MENIU_5_3'] = '';//facturare
			$flag[5] = 1;
		}
		if( in_array('facturi_restante',$this->user_rights)){
			$this->vars['MENIU_5_4'] = '';//facturi_restante
			$flag[5] = 1;
		}

        if( in_array('facturare',$this->user_rights)){
            $this->vars['MENIU_5_5'] = '';//facturare noua
            $flag[5] = 1;
        }

        if( in_array('print_facturi_android',$this->user_rights)){
                $this->vars['MENIU_5_6'] = '';//facturare noua
                $flag[5] = 1;
            }

        if( in_array('decont_cheltuieli',$this->user_rights)){
            $this->vars['MENIU_5_7'] = '';//facturare noua
            $flag[5] = 1;
        }

        if( in_array('loguri',$this->user_rights)){
            $this->vars['MENIU_11'] = '';// procesare scanari
            $flag[1] = 1;
        }

        if( in_array('dashboard_urmarire', $this->user_rights)){
            $this->vars['MENIU_12'] = '';
            $flag[1] = 1;
        }

        if( in_array('contracte', $this->user_rights)){
            $this->vars['MENIU_14'] = '';
            $flag[1] = 1;
        }

        if( in_array('new_decont', $this->user_rights) || in_array($this->user_id, self::CAN_MODIFY_GEOCODE_FINANCIAR)){
            $this->vars['MENIU_8_1'] = '';
            $this->vars['MENIU_8_2'] = '';
            $flag[8] = 1;
        }
        if(in_array('financiar_centre', $this->user_rights) || in_array($this->user_id, self::CAN_FINANCIAR_CENTRE)){
            $this->vars['MENIU_8_3'] = '';
            $flag[8] = 1;
        }
        if(in_array('financiar_salarii', $this->user_rights) || in_array($this->user_id, self::CAN_MODIFY_SALARII_FINANCIAR)){
            $this->vars['MENIU_8_4'] = '';
            $flag[8] = 1;
        }
        if($this->user_id == self::MARIAN){
            $this->vars['MENIU_8_5'] = '';
            $flag[8] = 1;
        }

		//Scanare
		if( in_array('scanare',$this->user_rights)){
			$this->vars['MENIU_6'] = '';//scanare
			$flag[6] = 1;
		}

        if( in_array('pontaj',$this->user_rights)  || $this->user_profile == 10){
            $this->vars['MENIU_8_6'] = '';//pontaj
            $flag[8] = 1;
        }

		$this->vars['MENIU_3'] = '';//editare
		$this->vars['MENIU_3_8'] = '';//drepturi acces

		for($x=1;$x<10;$x++){
			if(!empty($flag[$x]))
				$this->vars['MENIU_'.$x] = '';
		}

		if($this->user_profile == 10){
			for($i=1;$i<20;$i++){
				$this->vars['MENIU_'.$i] = '';
				for($j=1;$j<=20;$j++){
					$this->vars['MENIU_'.$i.'_'.$j] = '';
				}
			}
		}


       if ($this->user_id == self::MADALIN || $this->user_id == self::MARIAN){
           $this->vars['MENIU_18_1'] = '';
       }

       $this->vars['MENIU_19'] = $ascuns;
       $this->vars['MENIU_20'] = $ascuns;
       //Statie Scanare
       if( $this->user_profile==13){
           $this->vars['MENIU_19'] = '';
       }

       if(in_array($this->user_profile, $this->config['scanare']['allowLevels'])){
           $this->vars['MENIU_20'] = '';
       }
       //error_log(print_r($this->vars,true));
   }

   function GenerareListaCentre($ids=[],$lista='centre_expeditie'){
		$items = '<ul class="lista_centre" id = "lista_'.$lista.'">';
		$query="SELECT id, nume FROM centre WHERE deleted = 0 ORDER BY nume";
        $sql = $this->db->QFetchRowArray($query);
        if (!empty($sql)) {
            foreach ($sql as $key => $row) {
            	$checked = '';
            	if(in_array($row['id'], $ids)) $checked = 'checked="checked"';
				$items .= '<li><input type="checkbox" class="'.$lista.'" name="'.$lista.'[]" id="'.$lista.$row['id'].'" value="'.$row['id'].'" '.$checked.' /><label for="'.$lista.$row['id'].'" id="label_'.$lista.$row['id'].'">'.ucwords($row['nume']).'</label></li>';
            }
		}
		$items .= '</ul>';
		return $items;
	}

    function GetValuesClient($exp, $borderou_id = 0, $hasCond = null, $all = false) {
        $borderou_id = intval($borderou_id);

        $cond = "ep.expeditie = 0";
        if($hasCond != null)
            $cond = $hasCond;
        else if($borderou_id > 0) {
            $cond = "ep.borderou_id = {$borderou_id}";
        }
        else if(is_array($exp)) {
            if(count($exp) == 0) return false;
            $expeditii = implode(",", $exp);
            $cond = "ep.expeditie in ({$expeditii})";
        }
        else {
            $exp = intval($exp);
            if($exp == 0) return false;
            $cond = "ep.expeditie = {$exp}";
        }

        $anulate = "and ep.anulata = 0 and ep.stearsa = 0";
        if(true === $all) $anulate = "";
        $query ="SELECT ep.id, ep.created_at, ep.updated_at, ep.updated_by, ep.printed_at, ep.printed_by, ep.deleted_at, ep.deleted_by, ep.user_id,
            ep.expeditie, ep.data_expeditie, ep.platitor, IF(ep.platitor = 1, ep.expeditor, ep.destinatar_cod_cl) as platitor_id,
            ep.expeditor as expeditor_id, ep.destinatar_cod_cl as destinatar_id, ep.destinatar_id as client_destinatar_id,
            ep.expeditor_contact, ep.expeditor_telefon, ep.destinatar_contact, ep.destinatar_telefon,
            lce.cod_lc as expeditor_localitate_id, lcd.cod_lc as destinatar_localitate_id, IF(ep.platitor = 1, lce.cod_lc, lcd.cod_lc) as platitor_localitate_id,
            IF(cle.zona_id > 0 and clec.id > 0, clec.id, cee.id) as expeditor_centru_id, 
            IF(cld.zona_id > 0 and cldc.id > 0, cldc.id, ced.id) as destinatar_centru_id, 
            IF(ep.platitor = 1, IF(cle.zona_id > 0 and clec.id > 0, clec.id, cee.id), IF(cld.zona_id > 0 and cldc.id > 0, cldc.id, ced.id)) as platitor_centru_id,
            je.cod_jd as expeditor_judet_id, jd.cod_jd as destinatar_judet_id,
            ep.tip_obj, ep.piese, ep.greutate, ep.greutate_vol, ep.volum, ep.ret_nt, ep.ret_doc, ep.ret_colet, ep.ret_amb, ep.liv_sediu, ep.liv_sambata,
            ep.copen, ep.sms, ep.km_ext_prel, ep.km_ext_livr, ep.ramburs, ep.tip_plata, ep.asigurare,
            ep.valoare_g, ep.valoare_km, ep.valoare_asig, ep.valoare_exp, ep.valoare_tva, ep.valoare_totala, ep.procTva, ep.moneda, ep.mod_plata, ep.borderou_id,
            ep.observatii, ep.detalii_doc, ep.swapped, ep.anulata, ep.stearsa,
            IF(lce.cod_lc = lcd.cod_lc, 0 , 1) as tip_tarif,
            cle.nume as expeditor_nume, cle.adresa as expeditor_adresa,
            lce.nume_lc as expeditor_localitate, lce.dist_km as expeditor_localitate_km, je.nume_jd as expeditor_judet,
            IF(cle.zona_id > 0 and clec.id > 0, clec.nume, cee.nume) as expeditor_centru, 
            IF(cle.zona_id > 0 and clec.id > 0, clec.label, cee.label) as expeditor_centru_cod,
            cld.nume as destinatar_nume, cld.adresa as destinatar_adresa,
            lcd.nume_lc as destinatar_localitate, lcd.dist_km as destinatar_localitate_km, jd.nume_jd as destinatar_judet,
            IF(cld.zona_id > 0 and cldc.id > 0, cldc.nume, ced.nume) as destinatar_centru, 
            IF(cld.zona_id > 0 and cldc.id > 0, cldc.label, ced.label) as destinatar_centru_cod,
            IF(cld.zona_id > 0 and cldc.id > 0, concat(' - ', cldz.name), '') as destinatar_centru_zona,
            IF(cld.zona_id > 0 and cldc.id > 0, cldc.rut_bvh, ced.rut_bvh) as rut_bvh,
            IF(cld.zona_id > 0 and cldc.id > 0, cldc.rut_buh, ced.rut_buh) as rut_buh,
            IF(cld.zona_id > 0 and cldc.id > 0, cldc.rut_buc, ced.rut_buc) as rut_buc,
            IF(ep.platitor = 1, cle.nume, cld.nume) as platitor_nume,
            IF(ep.platitor = 1, IF(cle.zona_id > 0 and clec.id > 0, clec.nume, cee.nume), IF(cld.zona_id > 0 and cldc.id > 0, cldc.nume, ced.nume)) as platitor_centru,
            IF(ep.platitor = 1, IF(cle.zona_id > 0 and clec.id > 0, clec.label, cee.label), IF(cld.zona_id > 0 and cldc.id > 0, cldc.label, ced.label)) as platitor_centru_cod,
            IF(ep.platitor = 1, lce.nume_lc, lcd.nume_lc) as platitor_localitate,
            IF(clem.cod_fiscal is NULL, cle.cod_fiscal, clem.cod_fiscal) as expeditor_cui,
            IF(clem.reg_com is NULL, cle.reg_com, clem.reg_com) as expeditor_j,
            enc.id as nc_id, enc.extrainfo, enc.largeinfo, ecf.folder,
            mu.user as updated_by_user, mp.user as printed_by_user
            FROM client_expeditii ep
            LEFT JOIN clienti cle ON ep.expeditor = cle.cod_cl
            LEFT JOIN zones clez ON clez.id = cle.zona_id
            LEFT JOIN centre clec on clec.id = clez.centru_id
            LEFT JOIN clienti clem ON clem.cod_cl = cle.master
            LEFT JOIN localitati lce on lce.cod_lc = cle.cod_lc
            LEFT JOIN centre cee on cee.id = lce.cod_centru
            LEFT JOIN judete je on je.cod_jd = lce.cod_jd
            LEFT JOIN clienti cld ON ep.destinatar_cod_cl = cld.cod_cl
            LEFT JOIN zones cldz ON cldz.id = cld.zona_id
            LEFT JOIN centre cldc on cldc.id = cldz.centru_id
            LEFT JOIN localitati lcd on lcd.cod_lc = cld.cod_lc
            LEFT JOIN centre ced on ced.id = lcd.cod_centru
            LEFT JOIN judete jd on jd.cod_jd = lcd.cod_jd
            LEFT JOIN clienti clp ON IF(ep.platitor = 1, ep.expeditor, ep.destinatar_cod_cl) = clp.cod_cl
            left join exp_nc enc on ep.expeditie = enc.expeditie
            left join exp_confirmari ecf on ep.expeditie = ecf.expeditie
            left join users u on u.id = ep.user_id
            left join users mu on mu.id = ep.updated_by
            left join users mp on mp.id = ep.printed_by
            WHERE {$cond} {$anulate}
            GROUP by ep.id
            ORDER BY ep.expeditie ASC";

        //$this->log($query, self::APP_LOG_FILE);

        if($borderou_id > 0 || is_array($exp) && count($exp) > 0)
            $sql = $this->db->QFetchRowArray($query);
        else
            $sql = $this->db->QFetchArray($query);
        if(empty($sql)) return false;
        return $sql;
    }

    function GetValues($exp, $all = false) {
        $exp = intval($exp);
        if($exp == 0) return false;

        $anulate = "and ep.anulata = 0";
        if(true === $all) $anulate = "";

        $query ="SELECT ep.cod_expeditie, ep.expeditie, ep.referire, ep.data_expeditie,
        group_concat(epbo.expeditie ORDER BY epbo.expeditie ASC) as awb_rbs, ep.ref_bo,
        ep.expeditor_id, ep.expeditor_contact, ep.expeditor_telefon,
        ep.destinatar_id, ep.destinatar_contact, ep.destinatar_telefon, ep.platitor_id, ep.idfact,
        lce.cod_lc as expeditor_localitate_id, lcd.cod_lc as destinatar_localitate_id, lcp.cod_lc as platitor_localitate_id,
        IF(cle.zona_id > 0 and clec.id > 0, clec.id, cee.id) as expeditor_centru_id, 
        IF(cld.zona_id > 0 and cldc.id > 0, cldc.id, ced.id) as destinatar_centru_id, 
        IF(clp.zona_id > 0 and clpc.id > 0, clpc.id, cep.id) as platitor_centru_id,
        je.cod_jd as expeditor_judet_id, jd.cod_jd as destinatar_judet_id,
        ep.tip_exp, ep.tip_obj, ep.piese, ep.plicuri, ep.colete, ep.paleti,
        ep.greutate, ep.volum, ep.greutate_vol, ep.ret_nt, ep.ret_doc, ep.ret_colet, ep.ret_amb, ep.liv_sed, ep.liv_samb,
        IF(lce.cod_lc = lcd.cod_lc, 0 , 1) as tip_tarif,
        IF(clem.cod_fiscal is NULL, cle.cod_fiscal, clem.cod_fiscal) as expeditor_cui,
        IF(clem.reg_com is NULL, cle.reg_com, clem.reg_com) as expeditor_j,
        ep.copen, ep.sms, ep.km_preluare, ep.km_livrare,
        ep.ramburs, ep.status_ramburs, ep.tip_plata, ep.ramburs_procent, ep.valoare_asigurata, ep.procent_asigurare,
        ep.val_greutate, ep.val_km, ep.val_asig, ep.valoare_expeditie, ep.valoare_totala_expeditie, ep.pret_impus, ep.tva, ep.procTva, ep.mod_plata, ep.moneda, ep.restanta,
        ep.curier_preluare_id, ep.curier_livrare_id, ep.primitor, ep.operatiune, ep.data_op, iep.data as data_livrare,
        ep.observatii, ep.detalii_doc, ep.anulata,
		cle.nume as expeditor_nume, cle.cc as expeditor_cc, cle.mod_plata as expeditor_mod_plata, cle.tarif as expeditor_contract, cle.adresa as expeditor_adresa,
        lce.nume_lc as expeditor_localitate, lce.dist_km as expeditor_localitate_km, je.nume_jd as expeditor_judet,
        IF(cle.zona_id > 0 and clec.id > 0, clec.nume, cee.nume) as expeditor_centru, 
        IF(cle.zona_id > 0 and clec.id > 0, clec.label, cee.label) as expeditor_centru_cod,
		cld.nume as destinatar_nume, cld.cc as destinatar_cc, cld.mod_plata as destinatar_mod_plata, cld.tarif as destinatar_contract, cld.adresa as destinatar_adresa,
        lcd.nume_lc as destinatar_localitate, lcd.dist_km as destinatar_localitate_km, jd.nume_jd as destinatar_judet,
        IF(cld.zona_id > 0 and cldc.id > 0, cldc.nume, ced.nume) as destinatar_centru, 
        IF(cld.zona_id > 0 and cldc.id > 0, cldc.label, ced.label) as destinatar_centru_cod,
        IF(cld.zona_id > 0 and cldc.id > 0, concat(' - ', cldz.name), '') as destinatar_centru_zona,
        IF(cld.zona_id > 0 and cldc.id > 0, cldc.rut_bvh, ced.rut_bvh) as rut_bvh,
        IF(cld.zona_id > 0 and cldc.id > 0, cldc.rut_buh, ced.rut_buh) as rut_buh,
        IF(cld.zona_id > 0 and cldc.id > 0, cldc.rut_buc, ced.rut_buc) as rut_buc,
		clp.nume as platitor_nume, clp.cc as platitor_cc, clp.mod_plata as platitor_mod_plata, clp.tarif as platitor_contract,
        IF(clp.zona_id > 0 and clpc.id > 0, clpc.nume, cep.nume) as platitor_centru, 
		IF(clp.zona_id > 0 and clpc.id > 0, clpc.label, cep.label) as platitor_centru_cod,
        lcp.nume_lc as platitor_localitate,
        agp.nume_ag as curier_preluare, agl.nume_ag as curier_livrare,
		efa.invoice as invoice, efa.trndate, IF(clp.mod_plata = 0, efa.sumamnt,'') as sumamnt,
        ddd.factura_id, ddd.serie as serie, ddd.decontata, ddd.factura_suma, ddd.factura_op,
        enc.id as nc_id, enc.extrainfo, enc.largeinfo, ecf.folder
		from exp_prelucrate ep
		LEFT JOIN clienti cle ON ep.expeditor_id = cle.cod_cl
        LEFT JOIN zones clez ON clez.id = cle.zona_id
        LEFT JOIN centre clec on clec.id = clez.centru_id
        LEFT JOIN clienti clem ON clem.cod_cl = cle.master
		LEFT JOIN localitati lce on lce.cod_lc = cle.cod_lc
		LEFT JOIN centre cee on cee.id = lce.cod_centru
		LEFT JOIN judete je on je.cod_jd = lce.cod_jd
		LEFT JOIN clienti cld ON ep.destinatar_id = cld.cod_cl
        LEFT JOIN zones cldz ON cldz.id = cld.zona_id
        LEFT JOIN centre cldc on cldc.id = cldz.centru_id
		LEFT JOIN localitati lcd on lcd.cod_lc = cld.cod_lc
		LEFT JOIN centre ced on ced.id = lcd.cod_centru
		LEFT JOIN judete jd on jd.cod_jd = lcd.cod_jd
		LEFT JOIN clienti clp ON ep.platitor_id = clp.cod_cl
        LEFT JOIN zones clpz ON clpz.id = clp.zona_id
        LEFT JOIN centre clpc on clpc.id = clpz.centru_id
		LEFT JOIN localitati lcp on lcp.cod_lc = clp.cod_lc
		LEFT JOIN centre cep on cep.id = lcp.cod_centru
		LEFT JOIN agenti agp ON ep.curier_preluare_id = agp.cod_ag
		LEFT JOIN agenti agl ON ep.curier_livrare_id = agl.cod_ag
		left join exp_facturi efa on efa.id = ep.idfact
        left join ( select dee.expeditie as expeditie, group_concat(dfa.id) as factura_id, group_concat(dfa.serie) as serie, group_concat(dfa.vFF) as decontata,
                group_concat(dfa.suma) as factura_suma, group_concat(dfa.operatiune) as factura_op
                from decont_expeditii dee
		        left join decont_facturi dfa on (dee.factura_id = dfa.id and dfa.anulata = 0)
                where dee.expeditie = {$exp} and dee.anulata = 0
                group by dee.expeditie
        ) as ddd on ep.expeditie = ddd.expeditie
        left join exp_nc enc on ep.expeditie = enc.expeditie
        left join exp_confirmari ecf on ep.expeditie = ecf.expeditie
        left join ist_exp iep on iep.cod_exp = ep.cod_expeditie and iep.operatiune = 3
        left join exp_prelucrate epbo on epbo.ref_bo = ep.expeditie and epbo.anulata = 0 and epbo.tip_exp = 3
		WHERE ep.expeditie = {$exp} {$anulate}
        GROUP by ep.cod_expeditie
        ORDER by ep.anulata ASC
		LIMIT 1";

        $sql = $this->db->QFetchArray($query);
        if(empty($sql)) return false;
        return $sql;
    }

    function Get_ValoareExpeditieClient($post = []){
		$result=[];
        $result['tExpeditie'] = $result['tKm'] = $result['tGreutate'] = $result['tAsigurare'] = $result['tRamburs'] = 0.00;
        $result['moneda'] = "LEI";
        $swapped = (isset($post['swapped']) && $post['swapped'] == 1) ? true : false;

        //error_log(intval($swapped));

        if(!$swapped){
            $expeditor_id = empty($post['expeditor_id']) ? $this->expeditor_id : intval($post['expeditor_id']);
            $expeditor_localitate_id = empty($post['expeditor_localitate_id']) ? $this->expeditor_localitate_id : intval($post['expeditor_localitate_id']);
            $kmPreluare = intval($post['km_ext_prel'] ?? $this->expeditor_localitate_km);
            $destinatar_id = $post['destinatar_id'];
            $destinatar_localitate_id = $post['destinatar_localitate_id'];
            $kmLivrare = $post['km_ext_livr'];
        }
        else {
            $expeditor_id = $post['expeditor_id'];
            $expeditor_localitate_id = $post['expeditor_localitate_id'];
            $kmPreluare = $post['km_ext_prel'];
            $destinatar_id = empty($post['destinatar_id']) ? $this->expeditor_id : intval($post['destinatar_id']);
            $destinatar_localitate_id = empty($post['destinatar_localitate_id']) ? $this->expeditor_localitate_id : intval($post['destinatar_localitate_id']);
            $kmLivrare = intval($post['km_ext_livr'] ?? $this->expeditor_localitate_km);
        }

        $platitor_infos = $this->ClientInfos(!$swapped ? $expeditor_id : $destinatar_id);
        $mod_plata = ($post['platitor'] == 1 && !$swapped) || ($post['platitor'] == 2 && $swapped) ? $this->mod_plata : 0;

        //incarcam tariful
		$tarif = $tarifLista = Tarif::getInstanceTarifLista($this->db);

        if((!$swapped && ($post['platitor'] == 1 && $platitor_infos['contract'] == 1 || $post['platitor'] == 2 && $platitor_infos['taxa_destinatie'] == 1))
            || ($swapped && ($post['platitor'] == 2 && $platitor_infos['contract'] == 1 || $post['platitor'] == 1 && $platitor_infos['taxa_destinatie'] == 1))) {
            
                $tarif = new Tarif($this->db, $platitor_infos['cod_cl']);
        }

		//tip tarif	det
		$tip_tarif = TarifDet::TARIF_LOCO;
		if($expeditor_localitate_id != $destinatar_localitate_id) $tip_tarif = TarifDet::TARIF_NATIONAL;

		$tarif_det = new TarifDet($this->db, $tarif->id, $tip_tarif);
        $tarif_g = new TarifG($this->db, $tarif_det->id, $tip_tarif);

		//tip tarif	g
		$result['tExpeditie'] = $tarif_det->colet;
		//facturare pe km si greutate la paleti
		if($post['tip_obj'] == 3)
		{
            $result['tExpeditie'] = $tarif_det->palet;
            $km_dist = 1;
			$id_centru_exp = 0;
			$id_centru_dest = 0;
			//imi trebuie id centrul exp si id centrul dest ca sa aflu km in tabla centre_km
			$query_exp="select cod_centru from localitati where cod_lc=".$expeditor_localitate_id;
			$sql_exp = $this->db->QFetchArray($query_exp);
			if(!empty($sql_exp))
			{
				$id_centru_exp = $sql_exp['cod_centru'];
				$query_dest="select cod_centru from localitati where cod_lc=".$destinatar_localitate_id;
				$sql_dest = $this->db->QFetchArray($query_dest);
				if(!empty($sql_dest))
				{
					$id_centru_dest = $sql_dest['cod_centru'];
					$query_km="select km from centre_km where (id_centru_exp=".$id_centru_exp." and id_centru_dest=".$id_centru_dest.") or (id_centru_exp=".$id_centru_dest." and id_centru_dest=".$id_centru_exp.") limit 1";
    				$sql_km = $this->db->QFetchRowAssoc($query_km);
					if(!empty($sql_km))
						$km_dist = $sql_km['km'];
				}
			}
            $tarif_g = new TarifG($this->db, $tarif_det->id, $tip_tarif, TarifG::PALET, $km_dist);

            //daca palet si tarif colet
		    if($tarif_g->tarifColetReplacePalet == 1)
                $result['tExpeditie'] = $tarif_det->colet;
		}
        else if($post['tip_obj'] == 1) {
            $result['tExpeditie'] = $tarif_det->plic;
        }


        if($tarif->tarif_proc_indexc > 0)
            $result['tExpeditie'] += ($result['tExpeditie'] * $tarif->tarif_proc_indexc) / 100;

		//greutate
		if($post['tip_obj'] == 2 || $post['tip_obj'] == 3) $result['tGreutate'] = $tarif_g->getValoareGreutate($post['greutate'], ($post['greutate_vol'] ?? 0), $tarif->tarif_proc_indexc);

		//valoare asigurare
		if($post['asigurare'] > 0)
            $result['tAsigurare'] = ($post['asigurare'] * $tarif_det->proc_asig)/100;

		//livrare sediu
		if(!empty($post['liv_sediu']))
            $result['tExpeditie'] += $tarif_det->liv_sediu;

		//livrare sambata
		if(!empty($post['liv_sambata']))
            $result['tExpeditie'] += $tarif_det->liv_sambata;

		//sms la livrare
        //mod_plata == 0 se plateste un singur sms si se pot trimite maximum 3
        //mod_plata == 1 se plateste per sms la scanare iesire curier
        if($mod_plata == 0)
            $result['tExpeditie'] += !empty($post['sms']) ? ($tarif->tarif_sms > 0 ? $tarif->tarif_sms : $tarifLista->tarif_sms) : 0.00;

		//deschidere colet
		if(!empty($post['copen']))
            $result['tExpeditie'] += $tarif->tarif_open > 0 ? $tarif->tarif_open : $tarifLista->tarif_open;

        //km exteriori
		$result['tKm']= $tarif->getValoareKM($kmPreluare, $kmLivrare);

        //valoare retururi
		//OPTIUNE taxa unica la retururi obligatorie pentru toata lumea
		$tRetururi=0;
        $plataRetururiLaInitiala = !$swapped ? ($mod_plata == 0 || $post['platitor'] == 2 || $platitor_infos['contract'] == 2 || ($platitor_infos['contract'] == 1 && !empty($tarif->taxa_expediere))) : true;
		//OPTIUNE taxa retururi la expediere : daca are aceasta optiune, retururile se platesc la initiala
		if($plataRetururiLaInitiala)
		{
            if(!empty($post['extrainfo'])) $post['ret_nt'] = 1;

			if(!empty($post['ret_nt']) && empty($post['ret_doc']))
				$tRetururi += $tarif_det->retur_nt;
			else if(empty($post['ret_nt']) && !empty($post['ret_doc']))
				$tRetururi += $tarif_det->retur_doc;
			else if(!empty($post['ret_nt']) && !empty($post['ret_doc']))
				$tRetururi += max($tarif_det->retur_nt,$tarif_det->retur_doc);

            //ramburs
            if($post['ramburs'] > 0)
            {
                //tip plata ramburs
                if(isset($post['tip_plata']) && ($post['tip_plata'] == 1 || $post['tip_plata'] == 2)) {
                    $tarif_det->taxa_ramb = max($tarif_det->retur_nt,$tarif_det->retur_doc);
                    $tarif_det->asig_ramb = 0;
                }
                //OPTIUNE taxa de ramburs include retururile : plateste la expeditie maximum din trei taxe
                if(!empty($tarif->taxa_ramburs))
                {
                    $tRetururi = 0;
                    //maravet
                    if(!empty($post['extrainfo'])) $post['ret_nt'] = 1;

                    if(!empty($post['ret_nt']) && empty($post['ret_doc']))
                        $tRetururi += max($tarif_det->retur_nt,$tarif_det->taxa_ramb);
                    else if(empty($post['ret_nt']) && !empty($post['ret_doc']))
                        $tRetururi += max($tarif_det->retur_doc,$tarif_det->taxa_ramb);
                    else if(!empty($post['ret_nt']) && !empty($post['ret_doc']))
                        $tRetururi += max($tarif_det->retur_nt,$tarif_det->retur_doc,$tarif_det->taxa_ramb);
                    else
                        $tRetururi += $tarif_det->taxa_ramb;
                }
                else
                    $tRetururi += $tarif_det->taxa_ramb;
                $result['tAsigurare'] += ($post['ramburs'] * $tarif_det->asig_ramb)/100;
            }

            //index combustibil
            if($tarif->tarif_proc_indexc > 0)
                $tRetururi += ($tRetururi * $tarif->tarif_proc_indexc) / 100;

            if(!empty($post['ret_amb']))
            {
                $tRetururi += $tarif_det->colet;
                $kgRetAmb = $this->kg_ret_amb > 0 ? $this->kg_ret_amb : $tarifLista->kg_ret_amb;
                $tRetururi += $tarif_g->getValoareGreutate($kgRetAmb, 0, $tarif->tarif_proc_indexc);
            }

            //daca are retur nt, retur doc, ramburs, retur amb
            if(!empty($post['ret_nt']) || !empty($post['ret_doc']) || $post['ramburs'] > 0 || !empty($post['ret_amb']))
                $result['tKm'] += $tarif->getValoareKM(0, $kmPreluare);
        }

		//valoare expeditie
		$result['tExpeditie'] += $tRetururi;

		$result['tExpeditie'] = round($result['tExpeditie'],2);
		$result['tKm'] = round($result['tKm'],2);
		$result['tGreutate'] = round($result['tGreutate'],2);
		$result['tAsigurare'] = round($result['tAsigurare'], 2);
		$result['moneda'] = $tarif->moneda;
		$result['mod_plata'] = $mod_plata;
		return $result;
	}

    function Get_ValoareExpeditie($post, $initialaRow = []){
        $tip_exp = $post['tip_exp'] ?? 0;
        switch ($tip_exp) {
            case 1 :
            case 2 :
            case 3 :
                return empty($initialaRow) ? false : $this->valoareReturNtDocRbs($initialaRow, $tip_exp);
            case 5 :
                return empty($initialaRow) ? false : $this->valoareReturnare($initialaRow);
            case 6 :
                return empty($initialaRow) ? false : $this->valoareReturAmb($initialaRow);
            case 7 :
                return empty($initialaRow) ? false : $this->valoareReturCol($initialaRow, $post);
            default :
                return $this->valoareInitiala($post);
        }
        return false;
	}

    private function valoareReturnare($initialaRow){
        $expeditor_id = intval($initialaRow['expeditor_id'] ?? 0);
        $destinatar_id = intval($initialaRow['destinatar_id'] ?? 0);
        $platitor_id = intval($initialaRow['platitor_id'] ?? 0);

		if(empty($expeditor_id) || empty($destinatar_id) || empty($platitor_id)) {
            error_log("debug valoareReturnare : {$expeditor_id}  : {$destinatar_id} : {$platitor_id}");
            return false;
        }

        $initialaRow['liv_samb'] = $initialaRow['liv_sediu'] = $initialaRow['sms'] = $initialaRow['copen'] =
        $initialaRow['ret_nt'] = $initialaRow['ret_doc'] = $initialaRow['ret_amb'] = 
        $initialaRow['ramburs'] = $initialaRow['tip_plata'] = 0;

        $result = $this->valoareInitiala($initialaRow);
        /*
        $result['tBaza']
        $result['tRetururi']
        $result['tAmb']
        $result['tOpt']
        $result['tExpeditie'] = round($result['tBaza'] + $result['tRetururi'] + $result['tAmb'] + $result['tOpt'], 2);
		$result['tKm']
		$result['tGreutate']
        $result['tAsigurare']
        $result['tRamburs']
        */

        //se ia asigurare si pe returnare
        $result['tExpeditie'] = round($result['tBaza'], 2);
        $result['tKm'] = $result['tKmLivrare'] = $result['tKmPreluare'];
        $result['tKmPreluare'] = 0.00;

        $result['tRetururi'] = $result['tRamburs'] = $result['tAmb'] = $result['tOpt']
            = $result['ramburs'] = $result['procRamburs']
            = 0.00;

        return $result;
	}

    //TODO
    private function valoareReturCol($initialaRow, $post = []){
        $expeditor_id = intval($initialaRow['expeditor_id'] ?? 0);
        $destinatar_id = intval($initialaRow['destinatar_id'] ?? 0);
        $platitor_id = intval($initialaRow['platitor_id'] ?? 0);

		if(empty($expeditor_id) || empty($destinatar_id) || empty($platitor_id)) {
            error_log("debug valoareReturnare : {$expeditor_id}  : {$destinatar_id} : {$platitor_id}");
            return false;
        }
        //plata la expeditor initiala -> exceptie initiala cu plata la tertz
        $initialaRow['platitor_id'] = intval($initialaRow['platitor_id'] == $initialaRow['destinatar_id'] ? ($initialaRow['expeditor_id'] ?? 0) : ($initialaRow['platitor_id'] ?? 0));

        $initialaRow['greutate'] = $post['greutate'] ?? 0.000;
		$initialaRow['greutate_vol'] = $post['greutate_vol'] ?? 0.000;
        $initialaRow['tip_obj'] = 2; //colet
        $initialaRow['ramburs'] = $initialaRow['tip_plata'] = $initialaRow['valoare_asigurata'] = 0;
        $initialaRow['liv_samb'] = $initialaRow['liv_sediu'] = $initialaRow['sms'] = $initialaRow['copen'] = 0;
        $initialaRow['ret_nt'] = $initialaRow['ret_doc'] = $initialaRow['ret_amb'] = 0;
        $result = $this->valoareInitiala($initialaRow);
        /*
        $result['tBaza']
        $result['tRetururi']
        $result['tAmb']
        $result['tOpt']
        $result['tExpeditie'] = round($result['tBaza'] + $result['tRetururi'] + $result['tAmb'] + $result['tOpt'], 2);
		$result['tKm']
		$result['tGreutate']
        $result['tAsigurare']
        $result['tRamburs']
        */

        //se ia asigurare si pe returnare
        $result['tExpeditie'] = round($result['tBaza'], 2);
        $result['tKm'] = $result['tKmLivrare'] = $result['tKmPreluare'];
        $result['tKmPreluare'] = 0.00;

        $result['tRetururi'] = $result['tRamburs'] = $result['tAmb'] = $result['tOpt'] = 0;
        $result['ramburs'] = $result['procRamburs'] = $result['valoare_asigurata'] = $result['tAsigurare'] = 0;

        return $result;
	}

    private function valoareReturAmb($initialaRow){
        $result = [];
        $result['tExpeditie'] = $result['tGreutate'] = $result['tKm'] = $result['tBaza'] = $result['tRetururi'] = $result['tAmb'] = $result['tOpt'] = $result['tAsigurare'] = $result['tRamburs'] = $result['valoare_asigurata'] = $result['procAsigurare'] = $result['ramburs'] = $result['procRamburs'] = $result['tKmPreluare'] = $result['tKmLivrare'] =  0.00;
		$result['moneda'] = $initialaRow['moneda'] ?? "LEI";
        $result['mod_plata'] = $initialaRow['mod_plata'] ?? 0;

        //km
        $kmPreluare = $initialaRow['expeditor_localitate_km'] ?? 0;

        $expeditor_id = intval($initialaRow['expeditor_id'] ?? 0);
        $destinatar_id = intval($initialaRow['destinatar_id'] ?? 0);
        $platitor_id = intval($initialaRow['platitor_id'] ?? 0);

        if(empty($destinatar_id) || empty($platitor_id)) {
            error_log("debug valoareReturAmb : {$expeditor_id} : {$destinatar_id} : {$platitor_id}");
        }

        //incarc tariful de lista
        $tarifLista = Tarif::getInstanceTarifLista($this->db);
        //greutate
        $result['greutate'] = $tarifLista->kg_ret_amb;
        // cod_cl, activ, contract, mod_plata, cc, icc, taxa_destinatie, ret_amb, kg_ret_amb, tarif_sms
        $platitor_infos = $this->ClientInfos($platitor_id);

        $result['greutate'] = ($platitor_infos['contract'] == 1 && $platitor_infos['kg_ret_amb'] > 0) ? $platitor_infos['kg_ret_amb'] : $tarifLista->kg_ret_amb;

        //daca mod plata per NT sau platitorul este destinatarul : s-a platit la initiala
        //daca platitorul are tarif de lista sau optiunea taxa retururi la initiala : s-a platit la initiala
        if($initialaRow['mod_plata'] == 0 || $platitor_id == $destinatar_id || $platitor_infos['contract'] == 2 || ($platitor_infos['contract'] == 1 && !empty($platitor_infos['taxa_expediere'])))
            return $result;

        $tip_tarif = $initialaRow['tip_tarif'] ?? (($initialaRow['expeditor_localitate_id'] != $initialaRow['destinatar_localitate_id']) ? TarifDet::TARIF_NATIONAL : TarifDet::TARIF_LOCO);
        $tip_obj = 2; //COLET

        //incarc tariful platitor
        $tarif = new Tarif($this->db, $platitor_infos['cod_cl']);
        //incarc tariful plic, colet, palet
        $tarif_det = new TarifDet($this->db, $tarif->id, $tip_tarif);

        //error_log("debug valoareReturAmb : {$platitor_infos['contract']} : {$result['mod_plata']} : {$tarif->taxa_expediere} : {$initialaRow['tip_plata']} : {$tarif_det->retur_doc}");

        //km la livrare retur = km la preluare initiala
        $result['tKmLivrare'] = $tarif->GetValoareKM(0, $kmPreluare);
        //plata la tertz : kg retur ambalaj : se iau tot timpul din contractul expeditorului
        if($platitor_id != $expeditor_id) {
            $expeditor_infos = $this->ClientInfos($expeditor_id);
            //daca expeditorul are contract negociat si nu este puisor al tertzului
            if($expeditor_infos['contract'] == 1 && $expeditor_infos['cod_cl'] != $platitor_infos['cod_cl'])
                $result['greutate'] = $expeditor_infos['kg_ret_amb'] > 0 ? $expeditor_infos['kg_ret_amb'] : $result['greutate'];
        }

        $tarif_det = new TarifDet($this->db, $tarif->id, $tip_tarif);
        $tarif_g = new TarifG($this->db, $tarif_det->id, $tip_tarif);

        $result['tBaza'] = $tarif_det->colet;
		if($tarif->tarif_proc_indexc > 0)
            $result['tBaza'] += ($result['tBaza'] * $tarif->tarif_proc_indexc) / 100;

        $result['tGreutate'] = $tarif_g->getValoareGreutate($result['greutate'], 0, $tarif->tarif_proc_indexc);

		//rezultate
		$result['tExpeditie'] = $result['tBaza'] = round($result['tBaza'], 2);
		$result['tKm'] = round($result['tKmLivrare'], 2);
		$result['tGreutate'] = round($result['tGreutate'], 2);
		$result['moneda'] = $tarif->moneda;

		if(!empty($initialaRow['expeditie']))  $result['referire'] = $initialaRow['expeditie'];

		return $result;
	}

    private function valoareReturNtDocRbs($initialaRow, $tip_exp){
        $result = [];
        $result['tExpeditie'] = $result['tGreutate'] = $result['tKm'] = $result['tBaza'] = $result['tRetururi'] = $result['tAmb'] = $result['tOpt'] = $result['tAsigurare'] = $result['tRamburs'] = $result['valoare_asigurata'] = $result['procAsigurare'] = $result['ramburs'] = $result['procRamburs'] = $result['tKmPreluare'] = $result['tKmLivrare'] =  0.00;
		$result['moneda'] = $initialaRow['moneda'] ?? "LEI";
        $result['mod_plata'] = $initialaRow['mod_plata'] ?? 0;

        //greutate
        $greutate = 0.500;
        //asigurare, ramburs
        $result['ramburs'] = round($initialaRow['ramburs'] ?? 0, 2);
        $result['valoare_asigurata'] = round($initialaRow['valoare_asigurata'] ?? 0, 2);
        //km
        $kmPreluare = $initialaRow['expeditor_localitate_km'] ?? 0;

        $destinatar_id = intval($initialaRow['destinatar_id'] ?? 0);
        $platitor_id = intval($initialaRow['platitor_id'] ?? 0);

        if(empty($destinatar_id) || empty($platitor_id)) {
            error_log("debug valoareReturNtDocRbs : {$destinatar_id} : {$platitor_id} : NT : {$initialaRow['expeditie']}");
        }

        //daca mod plata per NT sau platitorul este destinatarul : s-a platit la initiala
        if($initialaRow['mod_plata'] == 0 || $platitor_id == $destinatar_id)
            return $result;

        // cod_cl, activ, contract, mod_plata, cc, icc, taxa_destinatie, ret_amb, kg_ret_amb, tarif_sms
        $platitor_infos = $this->ClientInfos($platitor_id);
        
        //daca platitorul are tarif de lista sau optiunea taxa retururi la initiala : s-a platit la initiala
        if($platitor_infos['contract'] == 2 || ($platitor_infos['contract'] == 1 && !empty($platitor_infos['taxa_expediere'])))
            return $result;

        $tip_tarif = $initialaRow['tip_tarif'] ?? (($initialaRow['expeditor_localitate_id'] != $initialaRow['destinatar_localitate_id']) ? TarifDet::TARIF_NATIONAL : TarifDet::TARIF_LOCO);
        $tip_obj = 1; //plic
        //error_log("tip_tarif : {$tip_tarif}");
        //incarc tariful
        $tarif = new Tarif($this->db, $platitor_infos['cod_cl']);
        //km la livrare retur = km la preluare initiala
        $result['tKmLivrare'] = $tarif->GetValoareKM(0, $kmPreluare);

        //incarc tariful plic, colet, palet
        //error_log("{$tarif->id} : {$platitor_infos['cod_cl']}");
        $tarif_det = new TarifDet($this->db, $tarif->id, $tip_tarif);
        //error_log("{$tarif->id} : {$tarif_det->id} : {$platitor_infos['cod_cl']}");

        //error_log("debug valoareReturNtDocRbs : {$platitor_infos['contract']} : {$result['mod_plata']} : {$tarif->taxa_expediere} : {$initialaRow['tip_plata']} : {$tarif_det->retur_doc} : {$result['ramburs']} ");

        //ramburs
        if($tip_exp == 3)
        {
            $tip_plata_rbs = $initialaRow['tip_plata'] ?? 0;
            if($tip_plata_rbs == 1 || $tip_plata_rbs == 2){
                $tarif_det->taxa_ramb = max($tarif_det->retur_nt,$tarif_det->retur_doc);
                $tarif_det->asig_ramb = 0;
            }

            //OPTIUNE taxa de ramburs include retururile : plateste la expeditie maximum din trei taxe
            if(!empty($tarif->taxa_ramburs))
            {
                //maravet
                if(!empty($initialaRow['extrainfo'])) $initialaRow['ret_nt'] = 1;

                if(!empty($initialaRow['ret_nt']) && empty($initialaRow['ret_doc']))
                    $result['tBaza'] += max($tarif_det->retur_nt,$tarif_det->taxa_ramb);
				else if(empty($initialaRow['ret_nt']) && !empty($initialaRow['ret_doc']))
                    $result['tBaza'] += max($tarif_det->retur_doc,$tarif_det->taxa_ramb);
				else if(!empty($initialaRow['ret_nt']) && !empty($initialaRow['ret_doc']))
                    $result['tBaza'] += max($tarif_det->retur_nt,$tarif_det->retur_doc,$tarif_det->taxa_ramb);
				else
                    $result['tBaza'] += $tarif_det->taxa_ramb;
            }
            else
                $result['tBaza'] += $tarif_det->taxa_ramb;
            $result['tRamburs'] += ($result['ramburs'] * $tarif_det->asig_ramb)/100;

            //if($tip_plata_rbs == 3) //cont colector
            // $result['tKmLivrare'] = 0;

            if($tarif->tarif_proc_indexc > 0 && $initialaRow['tip_plata'] != 3)
                $result['tBaza'] += ($result['tBaza'] * $tarif->tarif_proc_indexc) / 100;
        }
        //ret_doc sau ret_nt
        else
        {
            //maravet
            if(!empty($initialaRow['extrainfo'])) $initialaRow['ret_nt'] = 1;

            if(!empty($initialaRow['ret_nt']) || !empty($initialaRow['ret_doc']))
                $result['tBaza'] += max($tarif_det->retur_nt,$tarif_det->retur_doc);

            if($tarif->tarif_proc_indexc > 0)
                $result['tBaza'] += ($result['tBaza'] * $tarif->tarif_proc_indexc) / 100;
            //error_log($initialaRow['expeditie'].":".$initialaRow['platitor_id'].":".$initialaRow['ret_nt'].":".$tRetururi);
        }

        /////////////////////////////////
		//rezultate
		$result['tExpeditie'] = $result['tBaza'] = round($result['tBaza'], 2);
		$result['tKm'] = round($result['tKmLivrare'], 2);
		$result['tGreutate'] = round($result['tGreutate'], 2);
        $result['tAsigurare'] = round($result['tAsigurare'], 2);
        $result['tRamburs'] = round($result['tRamburs'], 2);
        if($result['ramburs'] > 1)
		    $result['procRamburs'] = round($tarif_det->asig_ramb, 2);
		$result['moneda'] = $tarif->moneda;
        $result['greutate'] = round($greutate, 3);

        if(!empty($initialaRow['debug'])) error_log("debug valoareReturNtDocRbs : mod_plata2 " . $result['mod_plata']);

		if(!empty($initialaRow['expeditie']))  $result['referire'] = $initialaRow['expeditie'];
		return $result;
	}

    private function valoareInitiala($initialaRow){
        $result = [];
        $result['tExpeditie'] = $result['tGreutate'] = $result['tKm'] = $result['tBaza'] = $result['tRetururi'] = $result['tAmb'] = $result['tOpt'] = $result['tAsigurare'] = $result['tRamburs'] = $result['valoare_asigurata'] = $result['procAsigurare'] = $result['ramburs'] = $result['procRamburs'] = $result['tKmPreluare'] = $result['tKmLivrare'] =  0.00;
		$result['moneda'] = $initialaRow['moneda'] ?? "LEI";
		$result['mod_plata'] = 0;

        //greutate
        //$greutate = ceil($initialaRow['greutate'] ?? 0);
		$greutate = round($initialaRow['greutate'] ?? 0, 2);
        //asigurare, ramburs
        $result['valoare_asigurata'] = round($initialaRow['valoare_asigurata'] ?? 0, 2);
		$result['ramburs'] = round($initialaRow['ramburs'] ?? 0, 2);
        //km
		$kmPreluare = $initialaRow['km_preluare'] ?? 0;
		$kmLivrare = $initialaRow['km_livrare'] ?? 0;

        $expeditor_id = intval($initialaRow['expeditor_id'] ?? 0);
        $destinatar_id = intval($initialaRow['destinatar_id'] ?? 0);
        $platitor_id = intval($initialaRow['platitor_id'] ?? 0);

		if(empty($expeditor_id) || empty($destinatar_id) || empty($platitor_id)) {
            error_log("debug valoareInitiala : {$expeditor_id}  : {$destinatar_id} : {$platitor_id}");
        }

        // cod_cl, activ, tarif, mod_plata, cc, icc, taxa_destinatie, ret_amb, kg_ret_amb, tarif_sms
        $platitor_infos = $this->ClientInfos($platitor_id);
        $tip_tarif = $initialaRow['tip_tarif'] ?? (($initialaRow['expeditor_localitate_id'] ?? 0) != ($initialaRow['destinatar_localitate_id'] ?? 1) ? TarifDet::TARIF_NATIONAL : TarifDet::TARIF_LOCO);
        $tip_obj = $initialaRow['tip_obj'] > 0 ? $initialaRow['tip_obj'] : (($greutate >= 1) ? 2 : 1); //1 plic, 2 colet, 3 palet
        $mod_plata = $platitor_infos['mod_plata'] ?? 0;

        //incarc tarife
        $tarif = $tarifLista = Tarif::getInstanceTarifLista($this->db);
        //daca platitorul are tarif de lista sau mod plata per NT : plata retururi se face la initiala
        $plataRetururiLaInitiala = ($mod_plata == 0 || $platitor_infos['contract'] == 2 || $platitor_id == $destinatar_id);
        $kgRetAmb = ($platitor_infos['contract'] == 1 && $platitor_infos['kg_ret_amb'] > 0) ? $platitor_infos['kg_ret_amb'] :  $tarifLista->kg_ret_amb;
        if($platitor_id == $destinatar_id) {
            $expeditor_infos = $this->ClientInfos($expeditor_id);
            if($expeditor_infos['contract'] == 1 && ($expeditor_infos['taxa_destinatie'] ?? 0) > 0){
                $tarif = new Tarif($this->db, $expeditor_infos['cod_cl']);
            }
            else if($platitor_infos['contract'] == 1) {
                $tarif = new Tarif($this->db, $platitor_infos['cod_cl']);
            }
            //kg retur ambalaj : se iau tot timpul din contractul expeditorului
            if(!empty($initialaRow['ret_amb']))
                $kgRetAmb = $expeditor_infos['contract'] == 1 && $expeditor_infos['kg_ret_amb'] > 0 ? $expeditor_infos['kg_ret_amb'] : $kgRetAmb;
        }
        else{
            if($platitor_infos['contract'] == 1){
                //error_log("platitor : " . $platitor_infos['cod_cl']);
                $tarif = new Tarif($this->db, $platitor_infos['cod_cl']);
                //Taxa retururi la initiala
                if($mod_plata > 0)
                    $plataRetururiLaInitiala = !empty($tarif->taxa_expediere);
            }
            //plata la tertz : kg retur ambalaj : se iau tot timpul din contractul expeditorului
            if(!empty($initialaRow['ret_amb']) && $platitor_id != $expeditor_id) {
                $expeditor_infos = $this->ClientInfos($expeditor_id);
                //daca expeditorul are contract negociat si nu este puisor al tertzului
                if($expeditor_infos['contract'] == 1 && $expeditor_infos['cod_cl'] != $platitor_infos['cod_cl'])
                    $kgRetAmb = $expeditor_infos['kg_ret_amb'] > 0 ? $expeditor_infos['kg_ret_amb'] : $kgRetAmb;
            }
        }

        $tarif_det = new TarifDet($this->db, $tarif->id, $tip_tarif);
        $tarif_g = new TarifG($this->db, $tarif_det->id, $tip_tarif);

        //error_log("{$tarif->id} : {$platitor_infos['cod_cl']} : {$tarif_det->id}");

        if(!empty($initialaRow['debug'])) 
            error_log("debug valoareInitiala : tarif : " . ($platitor_id == $destinatar_id && $expeditor_infos['contract'] == 1 && ($expeditor_infos['taxa_destinatie'] ?? 0) > 0) ? $expeditor_id : $platitor_id);

        $result['tBaza'] = $tarif_det->colet;
        $greutate_vol = !empty($initialaRow['greutate_vol']) ? $initialaRow['greutate_vol'] : 0;


        //error_log("{$tip_tarif} : {$result['tBaza']} : {$tarif_det->plic} : {$tarif_det->colet} : {$tarif_det->palet} : ");

        switch($tip_obj) {
            case 1 : //PLIC
                $result['tBaza'] = $tarif_det->plic;
                break;
            case 3 : //PALET
                $result['tBaza'] = $tarif_det->palet;
                $km_dist = 1;
                $id_centru_exp = 0;
                $id_centru_dest = 0;
                //imi trebuie id centru expeditie si id centrul dest ca sa aflu km in tabla centre_km
                $query_exp="select cod_centru from localitati where cod_lc=".$initialaRow['expeditor_localitate_id'];
                $sql_exp = $this->db->QFetchArray($query_exp);
                if(!empty($sql_exp))
                {
                    $id_centru_exp = $sql_exp['cod_centru'];
                    $query_dest="select cod_centru from localitati where cod_lc=".$initialaRow['destinatar_localitate_id'];
                    $sql_dest = $this->db->QFetchArray($query_dest);
                    if(!empty($sql_dest))
                    {
                        $id_centru_dest = $sql_dest['cod_centru'];
                        $query_km="select km from centre_km where (id_centru_exp = ".$id_centru_exp." and id_centru_dest = ".$id_centru_dest.") or (id_centru_exp = ".$id_centru_dest." and id_centru_dest = ".$id_centru_exp.") limit 1";
                        $sql_km = $this->db->QFetchRowAssoc($query_km);
                        if(!empty($sql_km))
                             $km_dist = $sql_km['km'];
                    }
                }
                $tarif_g = new TarifG($this->db, $tarif_det->id, $tip_tarif, TarifG::PALET, $km_dist);
                $result['tGreutate'] = $tarif_g->getValoareGreutate($greutate, $greutate_vol, $tarif->tarif_proc_indexc);
                if($tarif_g->tarifColetReplacePalet == 1)
                    $result['tBaza'] = $tarif_det->colet;
                break;
            default :
                $result['tBaza'] = $tarif_det->colet;
                $result['tGreutate'] = $tarif_g->getValoareGreutate($greutate, $greutate_vol, $tarif->tarif_proc_indexc);
        }

        if($tarif->tarif_proc_indexc > 0)
            $result['tBaza'] += ($result['tBaza'] * $tarif->tarif_proc_indexc) / 100;

        //valoare asigurare : se ia la cea mai mare dintre ramburs si asigurare
        if($result['valoare_asigurata'] > 0)
            $result['tAsigurare']= ($result['valoare_asigurata'] * $tarif_det->proc_asig)/100;

        //livrare sediu : poate fi negativ sau procent din (tbaza + tkg)
        if(!empty($initialaRow['liv_sed'])){
            $result['tOpt'] += $tarif_det->liv_sediu;
            //la livrare sediu : kmLivrare se anuleaza, interdictie la livrare din android
            //$kmLivrare = 0;
        }

        //livrare sambata
        if(!empty($initialaRow['liv_samb']))
            $result['tOpt'] += $tarif_det->liv_sambata;

        //sms la livrare
        //mod_plata == 0 se plateste un singur sms si se pot trimite maximum 3
        //mod_plata == 1 se plateste per sms
        if($mod_plata == 0)
            $result['tOpt'] += !empty($initialaRow['sms']) ? ($tarif->tarif_sms > 0 ? $tarif->tarif_sms : $tarifLista->tarif_sms) : 0.00;
        else if(isset($initialaRow['sms']) && intval($initialaRow['sms']) > 0)
            $result['tOpt'] += intval($initialaRow['sms']) * ($tarif->tarif_sms > 0 ? $tarif->tarif_sms : $tarifLista->tarif_sms);

        //deschidere colet
        if(!empty($initialaRow['copen']))
            $result['tOpt'] += $tarif->tarif_open > 0 ? $tarif->tarif_open : $tarifLista->tarif_open;

        //km
        //if($this->user_id == self::MARIAN)
        //    error_log(print_r($tarif, true));
        $result['tKmPreluare'] = $tarif->getValoareKM($kmPreluare, 0);
        $result['tKmLivrare'] = $tarif->getValoareKM(0, $kmLivrare);
        $result['tKm'] = $result['tKmPreluare'] + $result['tKmLivrare'];

        //OPTIUNE taxa unica la retururi obligatorie pentru toata lumea
        //OPTIUNE taxa retururi la expediere : daca are aceasta optiune, retururile se platesc la initiala
        if($plataRetururiLaInitiala)
        {
            //maravet
            if(!empty($initialaRow['extrainfo'])) $initialaRow['ret_nt'] = 1;
            //return nt, retur doc
            if(!empty($initialaRow['ret_nt']) || !empty($initialaRow['ret_doc'])){
                if(!empty($initialaRow['ret_nt']) && empty($initialaRow['ret_doc']))
                    $result['tRetururi'] += $tarif_det->retur_nt;
				else if(empty($initialaRow['ret_nt']) && !empty($initialaRow['ret_doc']))
                    $result['tRetururi'] += $tarif_det->retur_doc;
				else if(!empty($initialaRow['ret_nt']) && !empty($initialaRow['ret_doc']))
                    $result['tRetururi'] += max($tarif_det->retur_nt,$tarif_det->retur_doc);
                //index combustibil
                if($tarif->tarif_proc_indexc > 0)
                    $result['tRetururi'] += ($result['tRetururi'] * $tarif->tarif_proc_indexc) / 100;
            }

			//ramburs
            $tRetururiRamburs = 0.00;
			if($result['ramburs'] > 0)
			{
				//tip plata ramburs : de anulat si modificat raport Scanare->Istoric scanari : coloana Asig/Ramb
				$tip_plata_rbs = $initialaRow['tip_plata'] ?? 0;
                if($tip_plata_rbs == 1 || $tip_plata_rbs == 2){
                    $tarif_det->taxa_ramb = max($tarif_det->retur_nt,$tarif_det->retur_doc);
                    $tarif_det->asig_ramb = 0;
                }

				//OPTIUNE taxa de ramburs include retururile : plateste la expeditie maximum din trei taxe
				if(!empty($tarif->taxa_ramburs))
				{
                    $result['tRetururi'] = 0;
                    //maravet
                    if(!empty($initialaRow['extrainfo'])) $initialaRow['ret_nt'] = 1;

                    if(!empty($initialaRow['ret_nt']) && empty($poinitialaRowst['ret_doc']))
						$tRetururiRamburs += max($tarif_det->retur_nt,$tarif_det->taxa_ramb);
					else if(empty($initialaRow['ret_nt']) && !empty($initialaRow['ret_doc']))
						$tRetururiRamburs += max($tarif_det->retur_doc,$tarif_det->taxa_ramb);
					else if(!empty($initialaRow['ret_nt']) && !empty($initialaRow['ret_doc']))
						$tRetururiRamburs += max($tarif_det->retur_nt,$tarif_det->retur_doc,$tarif_det->taxa_ramb);
					else
						$tRetururiRamburs += $tarif_det->taxa_ramb;
				}
				else
                    $tRetururiRamburs += $tarif_det->taxa_ramb;
				$result['tRamburs'] += ($result['ramburs'] * $tarif_det->asig_ramb)/100;

                //index combustibil
                if($tarif->tarif_proc_indexc > 0 && $tip_plata_rbs != 3)
                    $tRetururiRamburs += ($tRetururiRamburs * $tarif->tarif_proc_indexc) / 100;
			}

            $result['tRetururi'] += $tRetururiRamburs;

            if(!empty($initialaRow['ret_amb']))
            {
                $result['tAmb'] += $tarif_det->colet;
                $result['tAmb'] += $tarif_g->getValoareGreutate($kgRetAmb, 0, $tarif->tarif_proc_indexc);
                //index combustibil
                if($tarif->tarif_proc_indexc > 0)
                    $result['tAmb'] += ($result['tAmb'] * $tarif->tarif_proc_indexc) / 100;
            }

            //daca are retur nt, retur doc, retur amb, ramburs : km la livrare
            if(!empty($initialaRow['ret_nt']) || !empty($initialaRow['ret_doc']) || $result['ramburs'] > 0 || !empty($initialaRow['ret_amb']))
                $result['tKm'] += $tarif->getValoareKM(0, $kmPreluare);
        }

        /////////////////////////////////
        //if($this->user_id == self::MARIAN)
        //error_log("{$tip_tarif} : {$tip_obj} : {$result['tBaza']} : {$result['tRetururi']} : {$result['tAmb']} : {$result['tOpt']}");
		//rezultate
		$result['tBaza'] = round($result['tBaza'], 2);
        $result['tRetururi'] = round($result['tRetururi'], 2);
        $result['tAmb'] = round($result['tAmb'], 2);
        $result['tOpt'] = round($result['tOpt'], 2);
        $result['tExpeditie'] = round($result['tBaza'] + $result['tRetururi'] + $result['tAmb'] + $result['tOpt'], 2);
		$result['tKm'] = round($result['tKm'], 2);
		$result['tGreutate'] = round($result['tGreutate'], 2);
        $result['tAsigurare'] = round($result['tAsigurare'], 2);
        $result['tRamburs'] = round($result['tRamburs'], 2);
		$result['valoare_asigurata'] = round($result['valoare_asigurata'], 2);
        if($result['valoare_asigurata'] >= 1)
		    $result['procAsigurare'] = round($tarif_det->proc_asig, 2);
		$result['ramburs'] = round($result['ramburs'], 2);
        if($result['ramburs'] >= 1)
		    $result['procRamburs'] = round($tarif_det->asig_ramb, 2);
        //error_log("{$result['ramburs']} : {$tarif_det->asig_ramb}");
		$result['moneda'] = $tarif->moneda;
		$result['mod_plata'] = $mod_plata;
        $result['greutate'] = round($greutate, 3);

        if(!empty($initialaRow['debug'])) error_log("debug valoareInitiala : mod_plata2 " . $mod_plata);

		if(!empty($initialaRow['expeditie']))  $result['expeditie'] = $initialaRow['expeditie'];
		return $result;
	}

	function checkLocalitate($localitate, $judet){
        if(strtoupper($judet) == 'BUCURESTI' && strtoupper(substr($localitate, 0, 6)) == 'SECTOR') return true;
		if(strtoupper($localitate) == 'BUCURESTI' && (strtoupper($judet) == 'BUCURESTI' || strtoupper($judet) == 'ILFOV')) return true;

		$query="select lc.cod_lc, lc.nume_lc, jd.nume_jd from localitati lc
				left join judete jd on lc.cod_jd = jd.cod_jd
				where replace(replace(lc.nume_lc,' ',''),'-','') like replace(replace(:localitate,' ',''),'-','')
				and (replace(replace(jd.nume_jd,' ',''),'-','') like replace(replace(:judet,' ',''),'-','') or jd.cod_jd like '".$judet."')";

    	$sql = $this->db->QFetchRowAssoc($query, ['localitate'=>$localitate,'judet'=>$judet]);

		if(empty($sql)) {
			return 'localitate lipsa : '.$localitate. ' in judetul '.$judet;
		}
		return true;
	}

    function getKmLocalitate($localitate_id) {
        $km = $this->db->QFetchRowColumn("select dist_km from localitati where cod_lc={$localitate_id}");
        //if($this->user_id == self::MARIAN)
            //error_log($km !== false ? round($km, 2) : 0.00);
        return $km !== false ? round($km, 2) : 0.00;
    }

	function _unserializeJQuery($rubble = NULL) {
	    $bricks = explode('&', $rubble);
	    foreach ($bricks as $key => $value) {
	        $walls = preg_split('/=/', $value);
	        $built[urldecode($walls[0])] = urldecode($walls[1]);
	    }
	    return $built;
	}

	function ComboOperatori($sel = 0, $extra='', $name='operatori') {
	    $result = '<option value="0" selected >Select Operator</option>';
	    $sql = $this->db->QFetchRowArray("SELECT a.id, a.user, b.nume FROM users a, centre b WHERE a.activ = 1 AND a.centru = b.id");
	    if (!empty($sql))
	        foreach ($sql as $val) {
	            if ($sel == $val['id'])
	                $result .= '<option value="' . $val['id'] . '" selected>' . ucfirst($val['user']) . '   (' . $val['nume'] . ')</option>';
	            else
	                $result .= '<option value="' . $val['id'] . '">' . ucfirst($val['user']) . '   (' . $val['nume'] . ')</option>';
	        }
	    return '<select class="field_combobox w300" name="' . $name . '" id="' . $name . '" ' . $extra . '>' . $result . '</select>';
	}

	function ComboCentre($sel, $extra='', $name='centru') {
	    $result = '<option value="0" >Toate</option>';
	    $sql = $this->db->QFetchRowArray("SELECT id, nume FROM centre WHERE deleted=0 ORDER BY nume ASC");
	    if (!empty($sql))
	        foreach ($sql as $val) {
	            if ($sel == $val['id'])
	                $result .= '<option value="' . $val['id'] . '" selected>' . $val['nume'] . '</option>';
	            else
	                $result .= '<option value="' . $val['id'] . '">' . $val['nume'] . '</option>';
	        }
	    return '<select class="field_combobox w180" name="' . $name . '" id="' . $name . '" ' . $extra . '>' . $result . '</select>';
	}

    function ComboCheckPoints($sel=0, $extra='', $name='operatiune') {
	    $result = '<option value="0" selected >Toate</option>';

		$result .= '<option value="1" >Intrare Centru</option>';
		$result .= '<option value="2" >Iesire Centru</option>';
		$result .= '<option value="3" >Intrare Curier</option>';
		$result .= '<option value="4" >Iesire Curier</option>';

	    $sql = $this->db->QFetchRowArray("SELECT id, abbr FROM checkpoints");
	    if (!empty($sql))
	        foreach ($sql as $val) {
	            if ($sel == $val['id'])
	                $result .= '<option value="' . $val['id'] . '" selected>' . $val['abbr'] . '</option>';
	            else
	                $result .= '<option value="' . $val['id'] . '">' . $val['abbr'] . '</option>';
	        }
		return $result;
	}

    function OptionsInterval($enabled = 9, $start=9, $end=17) {
	    $result = '';
        for($i=$start;$i<=$end;$i++) {
            if ($enabled == $i)
                $result .= '<option value="' . $i . '" selected>' . str_pad($i, 2, '0', STR_PAD_LEFT) . '</option>';
            else
                $result .= '<option value="' . $i . '">' . str_pad($i, 2, '0', STR_PAD_LEFT) . '</option>';
        }
		return $result;
	}

	function GetDrepturiUtilizator($profile){
        $result = $this->db->QFetchArray("SELECT * FROM fields WHERE id = {$profile}");
        if (isset($result['value'])){
            $profile = explode(',', $result['value']);
        }
        return is_array($profile) ? $profile : [];
	}

	function GenerareNrExpeditie(){
        $this->db->Query('INSERT INTO exp_alocare VALUES()');
        return $this->db->InsertId();
	}

	function trim_all( $str , $with = ' ' )
    {
        return trim(preg_replace( "/[[:cntrl:][:space:]`~\\\<>^]+/" , $with , $str ?? ''));
    }

    public static function sTrim_all( $str , $with = ' ' )
    {
        return trim(preg_replace( "/[[:cntrl:][:space:]`~\\\<>^]+/" , $with , $str ?? ''));
    }

    function sanitize($in) {
 		return $this->trim_all($this->encodeToUtf8($in ?? ''));
    }

    public static function sSanitize($in) {
        return self::sTrim_all(self::sEncodeToUtf8($in ?? ''));
    }

	function encodeToUtf8($string) {
     	return mb_convert_encoding($string ?? '', "UTF-8", mb_detect_encoding($string ?? '', "UTF-8, ISO-8859-1, ISO-8859-15", true));
    }

    public static function sEncodeToUtf8($string) {
        return mb_convert_encoding($string ?? '', "UTF-8", mb_detect_encoding($string ?? '', "UTF-8, ISO-8859-1, ISO-8859-15", true));
    }

    public static function sSanitizeCleanEdges($in) {
        return self::cleanEdges(self::sSanitize($in ?? ''));
    }
    
    public static function cleanEdges($str) {
        return preg_replace('/^[^a-zA-Z0-9]+/u', '', $str ?? '');
    }

    public static function iconvTranslate($string) {
        $string = self::sSanitize($string);
        setlocale(LC_CTYPE, 'en_US.UTF8');
        return iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $string);
    }

    public static function sSanitizeCleanEdgesToUpper($in) {
        return mb_strtoupper(self::sSanitizeCleanEdges($in ?? ''));
    }

	function isfloat($num) {
    	return is_float($num) || is_numeric($num) && ((float) $num != (int) $num);
	}

    function getTimeDiff($firstTime,$lastTime){
        $difference = $lastTime - $firstTime;
        $years = abs(floor($difference / 31536000));
        $days = abs(floor(($difference-($years * 31536000))/86400));
        $hours = abs(floor(($difference-($years * 31536000)-($days * 86400))/3600));
        $mins = abs(floor(($difference-($years * 31536000)-($days * 86400)-($hours * 3600))/60));#floor($difference / 60);

        $return = "";
        if($years > 0)
            $return .= "$years y ";
        if($days > 0)
            $return .= "$days d ";
        if($hours > 0)
            $return .= "$hours h ";
        if($mins > 0)
            $return .= "$mins m";


        return $return;
    }

    function getProcentTVA($data = null){
        if(!$data){
            $data = date("Y-m-d");
        }
        $procent = self::TVA_PROCENT;
        if(isset($this->config['procent_tva']) && is_array($this->config['procent_tva'])){
            foreach ($this->config['procent_tva'] as $data_tva => $val){
                if(strtotime($data) >= strtotime($data_tva)){
                    $procent = $val;
                }
            }
        }
        else {
            error_log("getProcentTVA : Config procent_tva is not an array or is empty. Using default value: " . self::TVA_PROCENT);
        }
        return $procent;
    }

    public function sendEmail($to, $emailSubject, $emailBody, $from = false, $logFilePath = false){
        $emailsToSent = str_replace(" ","",$to);
        $emailsToSent = str_replace(";",",",$emailsToSent);
        $emailsToSent = explode(",",$emailsToSent);
        $emailFrom = $from ? $from : "notificari@info.curierdragonstar.ro";

        $sE = SendEmailMailGun::send($emailFrom, null, null, $emailsToSent, [], [], [], $emailSubject, $emailBody);

        if(!$sE[0]) {
            $this->log('sendEmail Mailer Error: ' . $sE[1], $logFilePath);
            return false;
        }

        return true;
    }

    public function sendOneEmail($emailsToSent, $emailSubject, $emailBody, $logFilePath = false){
        $sE = SendEmailMailGun::send('notificari@info.curierdragonstar.ro', null, null, $emailsToSent, [], [], [], $emailSubject, $emailBody);

        if(!$sE[0]) {
            $this->log('sendOneEmail Mailer Error: ' . $sE[1], $logFilePath);
            return false;
        }

        return true;
    }

    public function sendEmailSend($emailFrom, $emailConfirmTo = null, $emailReplayTo = null, $emailsToSent = [], $emailsCC = [], $emailsBCC = [], $filesAttachements = [], $emailSubject, $emailBody, $emailLog, $logFilePath = false) {
        $sE = SendEmailMailGun::send($emailFrom, $emailConfirmTo, $emailReplayTo, $emailsToSent, $emailsCC, $emailsBCC, $filesAttachements, $emailSubject, $emailBody);

        if(!$sE[0]) {
            $this->log('sendEmailSend Mailer Error: ' . $sE[1], $logFilePath);
            return false;
        }

        return true;
    }

    public function getIstoricVals($ist_id){
        $prefixuri = array('int', 'double', 'text', 'varchar');
        $ist_id = intval($ist_id);

        $result = [];
        $i = 0;
        foreach ($prefixuri as $prefix){
            $sql = $this->db->QFetchRowArray("SELECT * FROM ist_exp_value_".$prefix." WHERE cod_ist = {$ist_id}");
            if(empty($sql))
                continue;
            foreach ($sql as $key => $row){
                $result[$i]['nume'] = $row['attribute'];

                if($row['attribute'] == 'status_ramburs')
                {
                    if(empty($row['value'])) $row['value'] = 0;
                    if(empty($row['old_value'])) $row['old_value'] = 0;

                    $row['value'] = self::STATUS_RAMBURS[$row['value']];
                    $row['old_value'] = self::STATUS_RAMBURS[$row['old_value']];
                }

                $result[$i]['new'] = $row['value'];
                $result[$i]['old'] = $row['old_value'];
                $i++;
            }
        }
        return $result;
    }

    public function saveIstoricVals($ist_id, $initialaRow, $newData){
        $unset = array('cod_operatiune', 'DATA_OP','platitor_localitate_id','data_operatie','data');
        $int_vals = array('tip_obj', 'piese', 'plicuri','colete' , 'paleti' , 'tip_exp', 'referire' ,'status_retururi' , 'expeditor_id' ,
            'destinatar_id' , 'curier_preluare_id', 'curier_livrare_id' , 'operator_id' , 'platitor_id',
            'mod_plata' , 'ret_nt', 'ret_doc' ,'liv_samb', 'liv_sed','ret_amb', 'ret_colet', 'idfact' , 'tip_plata', 'copen', 'sms', 'pret_impus');
        $double_vals = array('greutate', 'greutate_vol', 'val_greutate', 'km_preluare', 'km_livrare', 'val_km', 'valoare_asigurata', 'procent_asigurare', 'val_asig',
            'valoare_expeditie', 'valoare_totala_expeditie', 'tva', 'procTva', 'ramburs', 'ramburs_procent');
        $text_vals = array('observatii', 'volum');

        foreach ($unset as $k)
            unset($newData[$k]);

        $trimite_alerta = false;

        foreach ($newData as $key => $val){
            //is int ?
            if(in_array($key, $int_vals))
                $val = intval($val);

            $prefix = "";
            if(isset($initialaRow[$key])) {
                if($initialaRow[$key] != $val && in_array($key, $int_vals)){
                    $prefix = "int";
                } else if ($initialaRow[$key] != $val && in_array($key, $double_vals)){
                    $prefix = "double";
                } else if ($initialaRow[$key] != $val && in_array($key, $text_vals)){
                    $prefix = "text";
                } else if ($initialaRow[$key] != $val){
                    $prefix = "varchar";
                }
            }

            if(strlen($prefix)){
                $this->db->QueryInsert('ist_exp_value_'.$prefix, array(
                        'cod_ist'	=> $ist_id,
                        'attribute'	=> $key,
                        'value'		=> $newData[$key],
                        'old_value'	=> $initialaRow[$key]
                    )
                );

                if($key == 'greutate' && abs($newData[$key] - $initialaRow[$key]) >= $this->config['alerte']['modificari_valoare_totala_expeditie']['diferenta'] && count($this->config['alerte']['modificari_valoare_totala_expeditie']['email'])){
                    $trimite_alerta = true;
                }
            }
        }

        $trimite_alerta = false;
        if($trimite_alerta){
            $mesaj = "";
            $query = "SELECT
                ep.expeditie,
                clp.nume as platitor,
                cle.nume as expeditor,
                cld.nume as destinatar,
                CASE
                    WHEN ep.mod_plata = 0 then 'Cash'
                    WHEN ep.mod_plata = 1 then 'Periodic'
                    ELSE -1
                END as mod_plata,
                ie.data_op,
                ievd.old_value as 'valoare greutate veche',
                ievd.value as 'valoare greutate noua',
                (ievd.old_value - ievd.value) as diff ,
                u.user,
                c.label as centru,
                (SELECT (old_value) as diff_valoare_exp from ist_exp_value_double where attribute = 'valoare_totala_expeditie' and  cod_ist = ie.cod_ist) as 'valoare expeditie veche',
                (SELECT (value) as diff_valoare_exp from ist_exp_value_double where attribute = 'valoare_totala_expeditie' and  cod_ist = ie.cod_ist) as 'valoare expeditie noua',
                (SELECT (old_value - value) as diff_valoare_exp from ist_exp_value_double where attribute = 'valoare_totala_expeditie' and  cod_ist = ie.cod_ist) as 'diferenta expeditie'
                FROM ist_exp_value_double ievd
                left join ist_exp ie on ie.cod_ist = ievd.cod_ist
                left join users u on u.id = ie.operator
                left join centre c on c.id = u.centru
                left JOIN exp_prelucrate ep on ( ep.cod_expeditie = ie.cod_exp and ep.anulata = 0)
                left join clienti cle on cle.cod_cl = ep.expeditor_id
                left join clienti cld on cld.cod_cl = ep.destinatar_id
                left join clienti clp on clp.cod_cl = ep.platitor_id
                WHERE 1
                AND ievd.attribute = 'greutate'
                AND ie.cod_ist = {$ist_id}
                HAVING diff > 0
                ";
            // error_log($query);
            $sql = $this->db->QFetchArray($query);

            if(!empty($sql))
            {
                $keys = array_keys($sql);
                $values = array_values($sql);

                $mesaj .= '<style>table {border-collapse: collapse;}table, th, td {border: 1px solid black;}</style><table><tr><td>'.implode("</td><td>",$keys).'</td></tr><tr><td>'.implode("</td><td>",$values).'</td></tr></table>';
                SendEmailMailGun::send('notificari@info.curierdragonstar.ro', null, null, $this->config['alerte']['modificari_valoare_totala_expeditie']['email'], [], [], [], 'Modificare expeditie', $mesaj);
            }
        }

    }

    public function log($mess, $logFilePath = false){
        $out = "[ ".date('d-m-Y H:i:s')." ] {$mess}\n";
        if(empty($logFilePath)) {
            //error_log($out);
            return;
        }
        if(file_put_contents($logFilePath, $out, FILE_APPEND) === false){
            error_log("Error file_put_contents : {$logFilePath}");
        }
        if (php_sapi_name() == "cli")
            echo $out;
    }

    public function getProcent($i, $total){
        return ($i / $total) * 100;
    }

    public function logProgress($i, $total, $text = "%", $logFilePath = false){
        $this->log(str_replace("%", number_format(round((($i / $total) * 100) , 2), 2)."%", $text), $logFilePath);
    }

    public function getTimeEnd($start, $n, $totale){
        $diff = microtime(true) - $start;
        $estimat = $diff / $n;
        $endat = intval($estimat * ($totale -$n));
        // $time_end = date('d-m-Y H',strtotime('+'.$endat.' seconds'));
        // echo "$n: $diff --- ---> $time_end\n";
        return strtotime('+'.$endat.' seconds');
    }


    function getJsonProgress(){
        $progress = $this->db->QFetchRowArray("SELECT * FROM cron WHERE start_date is null");
        if(empty($progress)) $progress = [];
        $facturi_de_sters = $this->db->QFetchArray("SELECT count(id) as nr FROM exp_facturi WHERE status = -1");
        $facturi_de_trimis = $this->db->QFetchArray("SELECT count(id) as nr FROM exp_facturi f LEFT JOIN clienti c ON c.cod_cl  = f.cod_cl WHERE c.fara_factura = 0 AND c.email_factura LIKE '%@%' AND f.status >= 3 AND f.trimisa = 0 ");
        $facturi_de_trimis_wme = $this->db->QFetchArray("SELECT count(id) as nr FROM exp_facturi f LEFT JOIN clienti c ON c.cod_cl  = f.cod_cl WHERE c.fara_factura = 0 AND f.status >= 3 AND f.wme = 0 ");

        if(!empty($facturi_de_sters['nr']) && $facturi_de_sters['nr'] > 0)
            $progress[] = array('name'=>$facturi_de_sters['nr']." facturi de sters");

        if(!empty($facturi_de_trimis['nr']) && $facturi_de_trimis['nr'] > 0)
            $progress[] = array('name'=>$facturi_de_trimis['nr']." facturi de trimis");

        if(!empty($facturi_de_trimis_wme['nr']) && $facturi_de_trimis_wme['nr'] > 0)
            $progress[] = array('name'=>$facturi_de_trimis_wme['nr']." facturi de trimis in wme");

        $running = (file_exists('/var/www/crons/app/cron.lock'))? 1:0;
        return json_encode(array('progress'=>$progress, 'running' => $running));
    }

    function download_send_headers($filename) {
        header("Content-Description: File Transfer");
        header("Cache-Control: no-cache");
        header("Pragma: no-cache");
        header("Expires: 0");
        header("Content-Type: text/csv");
        header("Content-Disposition: attachment; filename={$filename}");
        flush();
    }

    function download_send_headers_xls($filename) {
        header("Content-Description: File Transfer");
        header("Cache-Control: no-cache");
        header("Pragma: no-cache");
        header("Expires: 0");
		header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
		header('Content-Disposition: attachment; filename="'. $filename);
		header("Content-Transfer-Encoding: binary");
        flush();
    }

    function array2csv(&$array, $memory_limit = "1228M"){
        ini_set('memory_limit', $memory_limit);
        set_time_limit(600);
        ob_start();
        $df = fopen("php://output", 'w');
        if(is_array($array) && count($array)) {
            foreach ($array as $row) {
                fputcsv($df, $row);
            }
        }
        fclose($df);
        return ob_get_clean();
    }

    function array2table(&$array, $titlu = ""){
        if(!count($array))
            return;

        $html = "";
        if(strlen($titlu))
            $html .= '<h3>'.$titlu.'</h3>';

        $html .= '<table>';
        $html .= '<tr><td></td>';
        foreach (array_keys($array[0]) as $label){
            $html .= '<td>'.$label.'</td>';
        }
        $html .= '</tr>';
        $i = 0;
        foreach ($array as $row) {
            $i++;
            $html .= '<tr><td>'.$i.'</td><td>'.implode("</td><td>",$row).'</td></tr>';
        }
        $html .= '</table>';
        return $html;
    }

    function checkSessionExpired(){
        if(!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest' && isset($_POST['checkSessionExpired'])){
            if($this->user_id == 0){
                header('Status: 401', TRUE, 401);
            } else {
                if(!empty($_POST['appVersion']) && $this->config['app_version'] != $_POST['appVersion']){
                    header('Status: 426', TRUE, 426);
                    echo '<div style="height: 200px; display: block;"><br><br><br><br><h1 style="color: red;">Atentie !</h1><hr style="margin: 5px 0; "/><h2>Apasa <a href="#" onClick="window.location.reload(); return false;" style="color: green">aici</a> pentru reincarcare</h2></div>';
                } else {
                    header('Status: 200', TRUE, 200);
                }
            }
            exit();
        }
    }


    function getExpScan($nr_exp)
    {
        if (empty($nr_exp))
            return 0;

        $nr_scanari = $this->db->QFetchRowColumn("SELECT count(sc.id) FROM scanari_coduri sc WHERE sc.cod like '{$nr_exp}'");
        return $nr_scanari !== false ? intval($nr_scanari) : 0;

    }

    function ActualizareRetururi($initiala, $keep_returnare_platitor_id = false) {
        $updated = 0;

        if($initiala == 0)
            return false;

        $queryRetururi = "SELECT cod_expeditie, expeditie, referire, tip_exp, idfact, 
            tip_obj, piese, greutate, greutate_vol, volum, mod_plata,
            plicuri, colete, paleti, pret_impus,
            platitor_id, expeditor_id, destinatar_id, observatii, anulata
            from exp_prelucrate 
            where referire = {$initiala} and tip_exp not in (0,33) and anulata = 0 and idfact = 0";
		$sqlRetururi = $this->db->QFetchRowArray($queryRetururi);
        if(empty($sqlRetururi) || !is_array($sqlRetururi) || count($sqlRetururi) == 0)
            return $updated;

        if(false === ($initialaRow = $this->GetValues($initiala))){
            error_log("debug : ActualizareRetururi : initiala not found : {$initiala}");
            return false;
        }

        if($initialaRow['procTva'] == 0)
            $initialaRow['procTva'] = $this->procTva;

        foreach ($sqlRetururi as $retur)
        {
            $updated += $this->ActualizareRetur($retur, $initialaRow, $keep_returnare_platitor_id);
        }

        return $updated;
    }

    function ActualizareRetur($retur, $initialaRow, $keep_returnare_platitor_id = false) {
        if(empty($retur['tip_exp']) || $retur['tip_exp'] == self::TIP_EXP_BO_RBS_CASH || empty($retur['expeditie']) || $retur['anulata'] == 1) return 0; // ignore retururi de tip borderou RBS cash

        $initialaRow['km_preluare'] = $initialaRow['expeditor_localitate_km'];
		$initialaRow['km_livrare'] = $initialaRow['destinatar_localitate_km'];
        // returnare cu platitor tertz modificat
        if($keep_returnare_platitor_id && $retur['tip_exp'] == 5 && $initialaRow['platitor_id'] != $retur['platitor_id'] && $retur['destinatar_id'] != $retur['platitor_id'] && $retur['expeditor_id'] != $retur['platitor_id']) {
            $initialaRow['platitor_id'] = $retur['platitor_id'];
			$initialaRow['mod_plata'] = $retur['mod_plata'];
            $val = $this->Get_ValoareExpeditie($retur, $initialaRow);
        }// returnare cu platitor = destinatar, mut plata la expeditor si setare expeditie ca restanta
        else if($retur['tip_exp'] == 5 && $initialaRow['destinatar_id'] == $initialaRow['platitor_id']) {
            $updateInitiala = [];
            $updateInitiala['platitor_id'] = $initialaRow['platitor_id'] = $initialaRow['expeditor_id'];
            $updateInitiala['restanta'] = 1;
            $updateInitiala['restanta_data'] = date('Y-m-d');
            $valInitiala = $this->Get_ValoareExpeditie($initialaRow);
            $updateInitiala['mod_plata'] = $initialaRow['mod_plata'] = $valInitiala['mod_plata'];
            $updateInitiala['valoare_expeditie'] = $initialaRow['pret_impus'] > 0 ? round($initialaRow['valoare_totala_expeditie'], 2) : round($valInitiala['tExpeditie'], 2);
            $updateInitiala['val_greutate'] = $initialaRow['pret_impus'] > 0 ? 0 : round($valInitiala['tGreutate'], 2);
            $updateInitiala['val_km'] = $initialaRow['pret_impus'] > 0 ? 0 : round($valInitiala['tKm'], 2);
            $updateInitiala['val_asig'] = $initialaRow['pret_impus'] > 0 ? 0 : round($valInitiala['tAsigurare'] + $valInitiala['tRamburs'], 2);
            $updateInitiala['valoare_totala_expeditie'] = $initialaRow['pret_impus'] > 0 ? round($initialaRow['valoare_totala_expeditie'], 2) : round($valInitiala['tExpeditie'] + $valInitiala['tGreutate'] + $valInitiala['tKm'] + $valInitiala['tAsigurare'] + $valInitiala['tRamburs'], 2);
            $updateInitiala['procTva'] = $initialaRow['procTva'];
            $updateInitiala['tva'] = round($updateInitiala['valoare_totala_expeditie'] * $updateInitiala['procTva'] / 100, 2);
            
            $updateInitiala['updated_at'] = date('Y-m-d H:i:s');
            $updateInitiala['updated_by'] = $this->user_id;
            $this->db->QueryUpdate('exp_prelucrate', $updateInitiala, "cod_expeditie=".$initialaRow['cod_expeditie']);

            $val = $this->Get_ValoareExpeditie($retur, $initialaRow);
        } //retur colet
        else if($retur['tip_exp'] == 7){ 
            $arrForVals = [
                'tip_exp' => $retur['tip_exp'],
                'greutate' => $retur['greutate'] >= 1 ? $retur['greutate'] : 1,
                'greutate_vol' => $retur['greutate_vol'],
                'piese' => $retur['piese'] >= 1 ? $retur['piese'] : 1,
                'colete' => $retur['colete'],
                'tip_obj' => 2, // colet
                'volum' => $retur['volum'],
            ];
            $val = $this->Get_ValoareExpeditie($arrForVals, $initialaRow);
        }
        else {
            $val = $this->Get_ValoareExpeditie($retur, $initialaRow);
        }

        $vi = ExpeditieDto::sqlInitialaToRetur($initialaRow, $retur, $val);
        unset($vi['data_expeditie']);
        unset($vi['data']);
        unset($vi['curier_preluare_id']);
        unset($vi['curier_livrare_id']);
        $vi['operator_id'] = $this->user_id;

        $oldReturRow = $this->getValues($retur['expeditie']);
        //update expeditie
        $vi['updated_at'] = date('Y-m-d H:i:s');
        $vi['updated_by'] = $this->user_id;
        $this->db->QueryUpdate('exp_prelucrate', $vi, "cod_expeditie = {$retur['cod_expeditie']}");

        //istoric expeditie
        if(false !== ($ist_id = $this->insertIstExp($retur['cod_expeditie'], 25)) && !empty($oldReturRow) && is_array($oldReturRow))
            $this->saveIstoricVals($ist_id, $oldReturRow, $vi);
        
        //send FCM la retururi
        $this->sendUpdateToAndroid($retur['expeditie']);

        return 1;
    }

    function ActualizareExpeditieInitiala($expeditie, $idfact){
        $updated = 0;
        if($expeditie == 0)
            return false;

        if($idfact == 0){
            if(false === ($initialaRow = $this->GetValues($expeditie))){
                error_log("debug : ActualizareExpeditii : expeditie not found : {$expeditie}");
                return false;
            }

            if($initialaRow['tip_exp'] > 0){
                error_log("debug : ActualizareExpeditii : expeditia nu este initiala : {$expeditie}");
                return false;
            }
            //error_log(print_r($initialaRow, true));
            $initialaRow['km_preluare'] = $initialaRow['expeditor_localitate_km'];
		    $initialaRow['km_livrare'] = $initialaRow['destinatar_localitate_km'];
            $val = $this->Get_ValoareExpeditie($initialaRow);
            $vi = [];
            $vi['km_preluare'] = $initialaRow['expeditor_localitate_km'];
            $vi['km_livrare'] = $initialaRow['destinatar_localitate_km'];
            $vi['val_greutate'] = $initialaRow['pret_impus'] > 0 ? 0 : $val['tGreutate'];
            $vi['val_km'] = $initialaRow['pret_impus'] > 0 ? 0 : $val['tKm'];
            $vi['val_asig'] = $initialaRow['pret_impus'] > 0 ? 0 : $val['tAsigurare'] + $val['tRamburs'];
            $vi['procent_asigurare'] = $initialaRow['procent_asigurare'];
            $vi['ramburs_procent'] = $initialaRow['ramburs_procent'];
            $vi['valoare_expeditie'] = $initialaRow['pret_impus'] > 0 ? round($initialaRow['valoare_totala_expeditie'], 2) : $val['tExpeditie'];
            $vi['valoare_totala_expeditie'] = $initialaRow['pret_impus'] > 0 ? round($initialaRow['valoare_totala_expeditie'], 2) : round($val['tExpeditie'] + $val['tGreutate'] + $val['tKm'] + $val['tAsigurare'] + $val['tRamburs'], 2);
            if($initialaRow['procTva'] == 0)
                $initialaRow['procTva'] = $this->procTva;
            $vi['procTva'] = round($initialaRow['procTva'], 2);
            $vi['tva'] = round($vi['valoare_totala_expeditie'] * $initialaRow['procTva'] / 100, 2);

            $vi['updated_at'] = date('Y-m-d H:i:s');
            $vi['updated_by'] = $this->user_id;
            $this->db->QueryUpdate('exp_prelucrate', $vi, "cod_expeditie=".$initialaRow['cod_expeditie']);

            //istoric expeditie
            if(false !== ($ist_id = $this->insertIstExp($initialaRow['cod_expeditie'], 25)))
                $this->saveIstoricVals($ist_id, $initialaRow, $vi);
            $updated++;
            //send FCM la retururi
            $this->sendUpdateToAndroid($initialaRow['expeditie']);
        }

        //actualizare retururi
        $queryRetururi = "SELECT count(cod_expeditie) as nr_retururi from exp_prelucrate where referire = {$expeditie} and tip_exp not in (0,33) and anulata = 0 and idfact = 0";
        $sqlRetururi = $this->db->QFetchArray($queryRetururi);
        $count = !empty($sqlRetururi['nr_retururi']) ? $sqlRetururi['nr_retururi'] : 0;
        if($count > 0){
            if(false == ($retR = $this->ActualizareRetururi($expeditie)))
                error_log("ActualizareExpeditieInitiala : ActualizareRetururi : {$expeditie}");
        }
        return $updated;
    }

    function ValidareExpeditiiCurata($fs){
		$items = explode(',',$fs);
		$proces_f = [];
		foreach ($items  as $fval){
			if(ExpeditieDto::isAwb(trim($fval)) || ExpeditieDto::isOldSystemAwb(trim($fval)) || ExpeditieDto::isCmnAwb(trim($fval)))
                $proces_f[] = intval(trim($fval));
		}

		if(!count($proces_f)){
			return "";
		}

		return implode(',',array_unique($proces_f));
	}

    function excelRange($end) {
		$end = intval($end);
		if($end > 100) return 'A';
		$letters = [];
		$letter = 'A';
		for($i=1; $i <= $end; $i++){
    		$letters[] = $letter++;
		}
		return $letters;
    }

    function checkCodBaraMaravet($cod){
		$mcod=intval($cod);

        if(!(ExpeditieDto::isMaravetAwb($mcod))) return ': nr. nt eronat '.$mcod;

		$query="select expeditie from client_expeditii where expeditie=". $mcod;
		$sql = $this->db->QFetchRowAssoc($query);
		if(!empty($sql)) return ': expeditia '.$mcod.' exista deja in baza de date';

		return true;
    }

    function checkCodBaraGeneral($cod){
		$mcod=intval($cod);

        if(!(ExpeditieDto::isAwb($mcod))) return ': nr. nt eronat '.$mcod;

        $query="select expeditie from client_expeditii where expeditie=". $mcod;
		$sql = $this->db->QFetchRowAssoc($query);
        if(!empty($sql)) return ': expeditia '.$mcod.' exista deja in baza de date';

		return true;
    }

    function fcmSend($fromMethod, $to, $aPayload, $aOptions = [], $topic = false, $agent_id = 0) {
        if(!file_exists(FCMPushNotification::AUTH_JSON_FILE_PATH)) {
            error_log("ERROR FCM {$fromMethod} : file not found");
            return false;
        }

        $fcm = new FCMPushNotification();
        $fcmMethod = $topic === false ? "sendToDevice" : "sendToTopic";

        try {
            $aJsonMessage = $fcm->$fcmMethod(
                $to,
                $aPayload,
                $aOptions
            );
            //error_log("FCM {$fromMethod} : {$aJsonMessage}");
        }
        catch (Exception $ex){
            error_log("ERROR FCM {$fromMethod} : agent {$agent_id} : token {$to} : {$ex->getMessage()}");
            return false;
        }
        return true;
    }

    function sendUpdateToAndroid($id, $delete = false) {
        $id = intval($id);
        if($id == 0) return false;
        $today = new DateTime("now");
        //cauta expeditia in scanare iesire curier
        $query = "select fcm_token from agenti where cod_ag = (select curier from scanari_coduri
            where tip = 4 and borderou > 0 and DATE(data) = '" . $today->format('Y-m-d') . "'
            and cod like '".$id."' order by data desc limit 1)
            and not exists (select cod from scanari_coduri where cod like '".$id."' and tip = 5)";
        $result = $this->db->QFetchArray($query);
        if(empty($result) || empty($result['fcm_token'])) return false;

        $sDeviceToken = $result['fcm_token'];

		$aPayload = array(
			'data' => array(
				"messageType" => 5,
				"id" => $id
			)
		);
        if($delete === true) $aPayload['data']['delete'] = true;

		$aOptions = array(
			'priority' => 'high',
			'ttl' => 3600
		);

        return $this->fcmSend("sendUpdateToAndroid", $sDeviceToken, $aPayload, $aOptions);
    }

    function sendUpdateToAllAndroid($id, $messageType, $delete = false) {
        $id = intval($id);
        if($id == 0) return false;

		$aPayload = array(
			'data' => array(
				"messageType" => $messageType,
				"id" => $id
			)
		);
        if($delete === true) $aPayload['data']['delete'] = true;

        //'ttl' => default 4 month
		$aOptions = array(
			'priority' => 'high'
		);

        return $this->fcmSend("sendUpdateToAllAndroid", "cds-tools", $aPayload, $aOptions, true);
    }

    function insertIstExp($cod_expeditie, $operatiune, $data_op = null, $operator = null){
        try {
            return $this->db->QueryInsert('ist_exp',
                [
                    'cod_exp' => $cod_expeditie ?? 0,
                    'operatiune' => $operatiune,
                    'operator' => $operator ?? $this->user_id,
                    'data'     => !empty($data_op) ? $data_op->format("Y-m-d") : date("Y-m-d"),
                    'data_op' => !empty($data_op) ? $data_op->format("Y-m-d H:i:s") : date("Y-m-d H:i:s")
                ]
            );
        }
        catch (Exception $ex) {
            error_log("Exception : insertIstExp : {$ex}");
            return false;
        }
	}

    function insertCheckpointRW($expeditie, $centru_id, $user_id = 0, $scanner = 'Modul recantarire'){
        if($user_id == 0) $user_id = $this->user_id;
		$scanData = array(
			'ruta'      => 0,
			'centru'    => $centru_id,
			'curier'    => $this->config['scanare']['CurierMagazie'],  // Magazie
			'scanner'   => $scanner,
			'tip'       => $this->config['scanare']['StatusRecantarire'], // RW - Recantarire
			'borderou'	=> 0,
			'cod'    	=> $expeditie,
            'expeditie' => $expeditie,
            'is_awb'    => 1,
			'status'    => 1,
			'user'      => $user_id,
			'data'  	=> date('Y-m-d H:i:s')
		);
        //dont update last_ckp, centru_last_ckp, data_last_ckp for expeditie
		return $this->db->QueryInsert('scanari_coduri', $scanData);
	}

    function insertCkp($expeditie, $tip, $centru_id, $user_id = 0, $curier_id = null, $scanner = 'Confirmari app'){
        if($user_id == 0) $user_id = $this->user_id;
		$scanData = array(
			'ruta'      => 0,
			'centru'    => $centru_id,
			'curier'    => $curier_id ?? $this->config['scanare']['CurierMagazie'],  // Magazie
			'scanner'   => $scanner,
			'tip'       => $tip, // tip checkpoint
			'borderou'	=> 0,
			'cod'    	=> $expeditie,
            'expeditie' => $expeditie,
            'is_awb'    => 1,
			'status'    => 1,
			'user'      => $user_id,
			'data'  	=> date('Y-m-d H:i:s')
		);
        //dont update last_ckp, centru_last_ckp, data_last_ckp for expeditie
		return $this->db->QueryInsert('scanari_coduri', $scanData);
	}

    function checkCodBaraRecantarire($expeditie = 0, $greutate = 0){
		if($expeditie == 0 ) return ': cod bare eronat';
		$query="select ep.expeditie as expeditie, ep.idfact as idfact, ep.tip_exp as tip_exp, ep.referire as referire, ep.anulata,
			ep.tip_obj as tip_obj, ep.piese as piese, ep.greutate as greutate, ep.greutate_vol as greutate_vol, df.id as factura_id
			from exp_prelucrate ep
			left join decont_expeditii de on (ep.expeditie = de.expeditie and de.anulata = 0)
			left join decont_facturi df on (de.factura_id = df.id and df.anulata = 0)
			where ep.expeditie = {$expeditie} limit 1";

		$sql = $this->db->QFetchArray($query);
		if(empty($sql)) return 'nu exista in baza de date : recantarire imposibila';
		if(!empty($sql['anulata'])) return 'a fost stearsa : recantarire imposibila';
		if(!empty($sql['factura_id'])) return 'este facturata : recantarire imposibila';
		if(!empty($sql['idfact'])) return 'este facturata : recantarire imposibila';
		if(!in_array($sql['tip_exp'], [0,5,7])) return 'nu este initiala, retur colet sau returnare : modifica expeditia manual';
        if($sql['tip_exp'] == 5 && empty($sql['referire'])) return 'nu exista initiala la returnare';
		if(!empty($sql['greutate']) && !empty($greutate) && (ceil($sql['greutate']) > $greutate)) return 'greutate >= greutate noua : modifica expeditia manual';
        if(!empty($sql['greutate_vol']) && !empty($greutate) && (ceil($sql['greutate_vol']) > $greutate)) return 'greutate volumetrica >= greutate noua : modifica expeditia manual';
		if(!empty($sql['greutate']) && !empty($greutate) && (ceil($sql['greutate']) == $greutate)) return ceil($sql['greutate']);

		return true;
	}

    function reweightExpeditie($expeditie, $greutate, $er_id = 0, $vol1 = 0, $vol2 = 0, $vol3 = 0){
		$ret = [];
        $ret[0] = false;
        $ret[1] = "error unknown";

        if(false === ($rowExp = $this->GetValues($expeditie, true)))
            { $ret[1] = 'nu exista in baza de date'; return $ret;}
		if(empty($rowExp) || empty($rowExp['expeditie']))
			{ $ret[1] = 'nu exista in baza de date'; return $ret;}
        if(!in_array($rowExp['tip_exp'], [0,5,7]))
            { $ret[1] = 'nu este initiala, returnare sau retur colet : modifica expeditia manual'; return $ret;}
        if($rowExp['tip_exp'] == 5 && empty($rowExp['referire']))
            { $ret[1] = 'returnarea nu are initiala'; return $ret;}
        $oldKg = floor(max($rowExp['greutate'], $rowExp['greutate_vol']));
		if($oldKg >= $greutate)
			{ $ret[1] = 'greutatea este mai mica decat cea din baza de date'; return $ret;}



        if(($rowExp['tip_exp'] == 5 || $rowExp['tip_exp'] == 7) && false === ($rowExpInitiala = $this->GetValues($rowExp['referire'], true))){
            error_log("reweightExpeditie : nu exista initiala la returnare / retur colet : {$ret[1]} : {$rowExp['referire']}");
            if($rowExpInitiala['anulata'] > 0)
                $this->db->QueryUpdate('exp_prelucrate', ['anulata' => 0, 'deleted_at' => null, 'deleted_by' => 0], "cod_expeditie = ".$rowExpInitiala['cod_expeditie']);
        }

        $vi = [];
        $vi['greutate'] = $rowExp['greutate'] = $greutate;
        $vi['greutate_vol'] = 0;
        if($vol1 > 0 && $vol2 > 0 && $vol3 > 0){
            $vi['volum'] = $vol1.'x'.$vol2.'x'.$vol3;
            $vi['greutate_vol'] = ExpeditieDto::getGreutateVolumetrica($vol1, $vol2, $vol3);
        }
        $vi['anulata'] = $rowExp['anulata'] = 0;
        if($rowExp['tip_obj'] == 1) {
            $vi['piese'] = $rowExp['piese'] = 1;
            $vi['tip_obj'] = $rowExp['tip_obj'] = 2;
        }

        $valoare_totala_expeditie = $rowExp['valoare_totala_expeditie'];
        $pret_impus = $rowExp['pret_impus'];
        if($rowExp['tip_exp'] == 5){
		    $val = $this->Get_ValoareExpeditie(['tip_exp' => 5], $rowExpInitiala);
            $valoare_totala_expeditie = $rowExpInitiala['valoare_totala_expeditie'];
            $pret_impus = $rowExpInitiala['pret_impus'];
        }
        else if($rowExp['tip_exp'] == 7)
            $val = $this->Get_ValoareExpeditie(['tip_exp' => 7, 'greutate' => $vi['greutate'], 'greutate_vol' => $vi['greutate_vol']], $rowExpInitiala);
        else
            $val = $this->Get_ValoareExpeditie($rowExp);

        if(false === $val) {
            error_log(print_r($rowExp, true));
            $ret[1] = 'eroare recantarire ! anunta la IT';
            return $ret;
        }

		$vi['valoare_expeditie'] = $pret_impus > 0 ? round($valoare_totala_expeditie, 2) : $val['tExpeditie'];
        $vi['val_greutate'] = $pret_impus > 0 ? 0 : $val['tGreutate'];
        $vi['valoare_totala_expeditie'] = $pret_impus > 0 ? round($valoare_totala_expeditie, 2) : round($val['tExpeditie'] + $val['tGreutate'] + $val['tKm'] + $val['tAsigurare'] + $val['tRamburs'], 2);
        if($rowExp['procTva'] == 0)
            $rowExp['procTva'] = $this->procTva;
        $vi['procTva'] = round($rowExp['procTva'], 2);
        $vi['tva'] = round($vi['valoare_totala_expeditie'] * $rowExp['procTva'] / 100, 2);

        if($this->user_id != self::MIHALCEA){
            //istoric modificare
            if(false !== ($ist_id = $this->insertIstExp($rowExp['cod_expeditie'], 25)))
                foreach(["greutate","val_greutate","valoare_totala_expeditie"] as $tVal)
                    $this->db->QueryInsert('ist_exp_value_double', array(
                            'cod_ist'	=> $ist_id,
                            'attribute'	=> $tVal,
                            'value'		=> $vi[$tVal],
                            'old_value'	=> $tVal == "greutate" ? $oldKg : $rowExp[$tVal]
                        )
                    );
        }

        $vi['updated_at'] = date('Y-m-d H:i:s');
        $vi['updated_by'] = $this->user_id;
        $this->db->QueryUpdate('exp_prelucrate', $vi, "expeditie = ".$rowExp['expeditie']);
        if($this->user_id != self::MIHALCEA && $er_id > 0)
            $this->db->QueryUpdate('exp_recantarite', ['oldKg' => $oldKg, 'vKg' => 1], "id = " . $er_id);

		$ret[0] = true;
		$ret[1] = $vi['val_greutate'];
        $ret[2] = $vi['valoare_totala_expeditie'];
        $ret[3] = $vi['tva'];
		return $ret;
	}

    function insertRecantarite($centru_id, $expeditie, $greutate, $lg = 0, $lt = 0, $h = 0, $vstatus = 1, $greutateInitiala = 0, $src = 0){
        if($centru_id == 0) $centru_id = $this->user_centru_id;
		$vi = array(
			'user_id' => $this->user_id,
            'centru_id' => $centru_id,
			'expeditie' => $expeditie,
			'kg' => $greutate,
			'lungime' => $lg,
			'latime' => $lt,
			'inaltime' => $h,
            'vKg' => $vstatus,
            'src' => $src
		);
        if(is_numeric($greutateInitiala) && $greutateInitiala > 0)
            $vi['oldKg'] = $greutateInitiala;

		return $this->db->QueryInsert('exp_recantarite', $vi);
	}

    function sqlToXls($filename, $sql = [], $header = []) {

        $data = date('d-m-Y');
 		$societate = 'S.C. DSC EXPRES LOGISTIC S.R.L.';
        $document = 'export';

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

        if(empty($sql[0]) || !is_array($sql[0]) || count($sql[0]) == 0){
            $this->download_send_headers_xls($filename . '_din_data_de_' . $data . '.xlsx');
            $writer = new Xlsx($spreadsheet);
            $writer->save('php://output');
            die;
        }

        $nbCols = count($header);
        $keys = array_keys($sql[0]);

        if($nbCols == 0) {
            $nbCols = count($sql[0]);
            $header = $keys;
        }

        $cols = $this->excelRange($nbCols);
        $i = 0;
		foreach($cols as $v) {
            $worksheet->setCellValue($v.'1',$header[$i++]);
			$worksheet->getStyle($v.'1')->getFont()->setBold(true);
			$worksheet->getStyle($v.'1')->getFont()->setSize(13);
			$worksheet->getColumnDimension($v)->setAutoSize(true);
		}

        $rand = 2;
        foreach ($sql as $key => $row) {
            for($col = 0; $col < $nbCols; $col++)
                $worksheet->setCellValueExplicit($cols[$col].($rand+$key), $row[$keys[$col]], \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
        }

        $this->download_send_headers_xls($filename .'_din_data_de_' . $data . '.xlsx');
        $writer = new Xlsx($spreadsheet);
        $writer->save('php://output');
        die;
    }

    function setStatusRamburs($initialaId, $oldStatusRbs, $newStatusRbs, $tip_plata = -1){
		$initialaId = intval($initialaId);
		$oldStatusRbs = intval($oldStatusRbs);
        $newStatusRbs = intval($newStatusRbs);
        if($initialaId == 0) return 0;
		//if(!ExpeditieDto::canModifyStatusRbs($oldStatusRbs, $newStatusRbs, $tip_plata)) return -1;

        $this->db->QueryUpdate('exp_prelucrate', ['status_ramburs' => $newStatusRbs, 'anulata' => 0, 'deleted_at' => null, 'deleted_by' => 0], "cod_expeditie = {$initialaId}");

        //istoric expeditii
        if(false !== ($istId = $this->insertIstExp($initialaId, 24)))
            $this->db->QueryInsert('ist_exp_value_int', array(
                    'cod_ist'	=> $istId,
                    'attribute'	=> 'status_ramburs',
                    'value'		=> $newStatusRbs,
                    'old_value'	=> $oldStatusRbs
                )
            );
		return 1;
	}

    function setStatusRetur($initialaId, $oldStatusRet, $newStatusRet){
		$initialaId = intval($initialaId);
        $oldStatusRet = intval($oldStatusRet);
        $newStatusRet = intval($newStatusRet);
		if($initialaId == 0 || $newStatusRet >= 4) return false;

        if($newStatusRet == 1 && $oldStatusRet == 2){
            $newStatusRet = 3;
        }
        if($newStatusRet == 2 && $oldStatusRet == 1){
            $newStatusRet = 3;
        }

        $this->db->QueryUpdate('exp_prelucrate', ['status_retururi' => $newStatusRet, 'anulata' => 0, 'deleted_at' => null, 'deleted_by' => 0], "cod_expeditie = {$initialaId}");

        //istoric expeditii
        if(false !== ($istId = $this->insertIstExp($initialaId, 30)))
            $this->db->QueryInsert('ist_exp_value_int', array(
                    'cod_ist'	=> $istId,
                    'attribute'	=> 'status_retururi',
                    'value'		=> $newStatusRet,
                    'old_value'	=> $oldStatusRet
                )
            );
		return true;
	}

    function creazaNotaRamburs($initiala, $cash_only = false){
		$initiala = intval($initiala);

		if($initiala == 0 || false === ($initialaRow = $this->GetValues($initiala))) return false;
		if($initialaRow['ramburs'] == 0 || $initialaRow['tip_exp'] > 0) return false;
        if($cash_only && $initialaRow['tip_plata'] > 0) return false;

		if($initialaRow['procTva'] == 0)
			$initialaRow['procTva'] = $this->procTva;

		$initialaRow['km_preluare'] = $initialaRow['expeditor_localitate_km'];
		$initialaRow['km_livrare'] = $initialaRow['destinatar_localitate_km'];
		$val = $this->Get_ValoareExpeditie(['tip_exp' => 3], $initialaRow);
		$notaRamburs = ExpeditieDto::sqlInitialaToRetur($initialaRow, ['tip_exp' => 3], $val);
		$notaRamburs['operator_id'] = $this->user_id;
		$notaRamburs['expeditie'] = $this->GenerareNrExpeditie();
		$notaRamburs['operatiune'] = "Colectata";
        $notaRamburs['created_at'] = date('Y-m-d H:i:s');
        $notaRamburs['created_by'] = $this->user_id;
        $notaRamburs['last_ckp'] = 5;
        $notaRamburs['centru_last_ckp'] = 47; //Bucuresti
        $notaRamburs['data_last_ckp'] = date('Y-m-d H:i:s');

		if(isset($_REQUEST['data_op'])){
			$data_op = date('Y-m-d', strtotime($_REQUEST['data_op']));
            $data_ora_op = date('Y-m-d H:i:s', strtotime($_REQUEST['data_op']));
			$notaRamburs['data_expeditie'] 	= $data_op;
			$notaRamburs['data_operatie'] 	= $data_op;
			$notaRamburs['data'] 			= $data_op;
            $notaRamburs['data_last_ckp'] = $data_ora_op;
		}

		//istoric expeditii
		$id_ist = $this->insertIstExp(null, 1);
		$cod_expeditie = $this->db->QueryInsert($this->tables['exp_prelucrate'], $notaRamburs);
	
		if($id_ist !== false) {
			if(isset($_REQUEST['data_op'])){
				$data_op = date('Y-m-d',strtotime($_REQUEST['data_op']));
				$this->db->QueryUpdate($this->tables['ist_exp'], ['cod_exp' => $cod_expeditie, 'data' => $data_op], "cod_ist = ".$id_ist);
			}
			else
				$this->db->QueryUpdate($this->tables['ist_exp'], ['cod_exp' => $cod_expeditie], "cod_ist = ".$id_ist);
		}
		//la creare expeditie de ramburs, daca tip_exp = 3 si tip_plata = 3 dau cok livrare
		if($notaRamburs['tip_plata'] == 3) {
			try {
				$data_op = new \DateTime($notaRamburs['data_expeditie']);
				$data_op->modify("+1 day");
				$scanData = array(
					'ruta'      => 0,
					'centru'    => 47, //Bucuresti
					'curier'    => 855,  // RAMBURSURI OPS
					'scanner'   => 'Creare NT RBS CC BULK',
					'tip'       => 5, // COK
					'borderou'	=> 0,
					'cod'    	=> $notaRamburs['expeditie'],
                    'expeditie' => $notaRamburs['expeditie'],
                    'is_awb'    => 1,
					'status'    => 1,
					'user'      => $this->user_id,
					'data'  	=> $data_op->format('Y-m-d H:i:s')
				);
				$this->db->QueryInsert($this->tables['scanari_coduri'], $scanData);
			}
			catch(\Exception $ex) {
				error_log("error adaugare expeditie tip ramburs CC : ".$notaRamburs['expeditie']);
			}
		}

        //la creare expeditie de ramburs, daca tip_plata = 0 schimb statusul rambursului din aprobat in spre client
		if($cash_only && $initialaRow['status_ramburs'] == 30) {
            $this->setStatusRamburs($initialaRow['cod_expeditie'], 30, 4, $initialaRow['tip_plata']);
            return ['expeditie' => $notaRamburs['expeditie'], 'ramburs' => $initialaRow['ramburs']];
		}

		return $notaRamburs['expeditie'];
	}
}