<?php
/**
 * cron
 */

require __DIR__."/../../vendor/autoload.php";
require_once 'backend.php';
require_once "mysqlPDO.php";
require_once 'winmentorNewApi.php';
require_once 'facturare.php';
require_once 'DscReporting.php';
require_once "SendEmailMailGun.php";
require_once("tarif.php");
require_once("tarifDet.php");
require_once("tarifG.php");
require_once("expeditieDto.php");

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;

class DscCron extends BackEnd {

    public $config, $db, $livrate, $last, $user;
    public $inrange, $custom_day, $custom_day_of_week;
    public $start_microtime;

    public $cronuri_zilnice = array(
        'VERIFICARE_EXPEDITII' => array(
            'nume'      => 'VERIFICARE_EXPEDITII',
            'run_after' => '08:00:00'
        )
    );

    const release_date = '2016-09-01';
    const CONFIG_JSON_FILE = '/etc/credentials/dsc_app_cron.json';

    const limit = 5000;

    const FACTURARE_LUNARA = 1;
    const FACTURARE_BILUNARA = 2;
    const FACTURARE_SAPTAMANALA = 3;
    const FACTURARE_MANUALA = 4;
    const FACTURARE_LUNARA_LA_DATA_SPECIFICA = 5;


    const FACTURA_PREGATITA     = 1;
    const FACTURA_FACTURATA     = 2;
    const FACTURA_FINALIZATA    = 3;
    const FACTURA_TRIMISA       = 4;
    const FACTURA_EMAIL_DESCHIS = 5;
    const FACTURA_VIZUALIZATA   = 6;

    const EXPEDITIE_IN_CURS_DE_FACTURARE = 32;

    const DELAY_FACTURARE_SAPTAMANALA = 1;
    const DELAY_FACTURARE_LUNARA = 3;
    const DELAY_FACTURARE_BILUNARA = 2;

    const OP_COLECTATA = 1;
    const OP_LIVRATA = 3;
    const OP_STATUS_RAMBURS = 24;

    const CKP_COK = 5;

    const STATUS_RAMBURS_IN_DERULARE = 0;
    const STATUS_RAMBURS_INCHIS = 1;
    const STATUS_RAMBURS_VALIDAT = 2;
    const STATUS_RAMBURS_REFUZAT = 3;
    const STATUS_RAMBURS_SPRE_CLIENT = 4;
    const STATUS_RAMBURS_ANULAT = 5;
    const STATUS_RAMBURS_DECONTAT = 23;

    const TIP_EXP_INITIALA = 0;
    const TIP_EXP_RBS = 3;
    const TIP_EXP_RETURNARE = 5;

    public $skiplock = false;

    public $sent_log_email = false;

    public $MentenanceMode = false;

    public $vLock = '';
    public $logFilePath = "/var/www/logs/app/_tmp/cron.log";

    public function __construct($argv, $vLock = '')
    {
        require_once __DIR__."/../../config/config.php";
        $this->config = $config;
        $this->last = 20;
        $this->vLock = $vLock;
        $this->logFilePath = "/var/www/logs/app/_tmp/cron{$vLock}.log";
        if(file_exists("cron{$this->vLock}.lock")){
            exit;
        }

        if(!is_file(self::CONFIG_JSON_FILE)) {
            echo "NO FILE CONFIG FILE !!!".self::CONFIG_JSON_FILE."\n";
            exit;
        }

        $config = json_decode(file_get_contents(self::CONFIG_JSON_FILE),true);
        $this->config["crons"] = $config["crons"];
        $this->config["email_list"] = $config["email_list"];
        $this->config["procent_tva"] = $config["procent_tva"];
        
        putenv("APP_HOST=".$config["mysql_connection"]["server"]);
        putenv("APP_USER=".$config["mysql_connection"]["user"]);
        putenv("APP_PASS=".$config["mysql_connection"]["password"]);
        putenv("APP_DB=".  $config["mysql_connection"]["database"]);
        putenv("WME_USER=".  $config["wme"]["user"]);
        putenv("WME_PASS=".  $config["wme"]["password"]);
        putenv("MAILGUN_API_KEY=".  $config["mailgun_api_key"]);

        $this->db = new MysqlPDO();
        // setare TVA
        $this->procTva = $this->getProcentTVA(date("Y-m-d"));

        $this->start_microtime = microtime(true);

        $this->skiplock = in_array("skiplock",$argv);

        if(!$this->skiplock)
            file_put_contents("cron{$this->vLock}.lock", time());

        file_put_contents($this->logFilePath, "");

        $this->log("=== Cron start ===", $this->logFilePath);

        $this->livrate = 0;
        $this->user_id = 3380;
    }

    public function __destruct()
    {
        $this->log("=== Cron finish ===", $this->logFilePath);
        if($this->sent_log_email){
            $body = nl2br(file_get_contents($this->logFilePath));
            SendEmailMailGun::send(
                $emailFrom = 'notificari@info.curierdragonstar.ro',
                $emailConfirmTo = 'notificari@info.curierdragonstar.ro',
                $emailReplayTo = 'notificari@info.curierdragonstar.ro',
                $emailsToSent = ['tc00mi@gmail.com','noc@curierdragonstar.ro','marius.teler@curierdragonstar.ro'],
                [], [], [],
                $emailSubject = 'Cron finish',
                $emailBody = $body
            );
        }

        if(file_exists("cron{$this->vLock}.lock") && !$this->skiplock)
            unlink("cron{$this->vLock}.lock");

    }

    // get method name
    function callerId(){
        $trace = debug_backtrace();
        return $trace[2]['function'];
    }

    public function checkForRun(){

        if(isset($this->config['crons'][$this->callerId()])){
            $time_ranges = explode(",",$this->config['crons'][$this->callerId()]);
            foreach ($time_ranges as $time_range){
                $times = explode("-",$time_range);
                $start = intval(str_replace(":","",$times[0]));
                $end = intval(str_replace(":","",$times[1]));
                $now = date("Hi");
                if($now >= $start && $now < $end){
                    return true;
                }
            }
        }
        return false;
    }

    public function ConfirmareCOK(){
        //confimare livrare la toate expeditiile (nelivrate : operatiune) care au primit COK la scanare

        if(!$this->checkForRun()){
            return;
        }

        $this->log("=== ConfirmareCOK ===", $this->logFilePath);

        $query = "SELECT sc.curier, sc.data, a.nume_ag as curier_nume,
                    ie.operatiune, e.expeditie, e.cod_expeditie, sc.user
                FROM scanari_coduri sc
                LEFT JOIN agenti a
                    ON sc.curier = a.cod_ag
                INNER JOIN exp_prelucrate e
                    ON (e.expeditie = sc.cod and e.anulata = 0)
                LEFT JOIN ist_exp ie
                    ON ie.cod_exp = e.cod_expeditie AND ie.operatiune = " . self::OP_LIVRATA ."
                WHERE sc.tip = " . self::CKP_COK . "
                AND sc.data >= DATE_SUB(NOW(), INTERVAL 7 DAY)
                AND ie.operatiune IS NULL
                GROUP BY e.cod_expeditie
                ";

        $sql = $this->db->QFetchRowArray($query);

        if(empty($sql)) {
            $this->log("Nici un COK scanat", $this->logFilePath);
            return;
        }

        // $total = count($sql);
        // $i = 0;
        foreach ($sql as $key => $row) {
            // $this->logProgress($i, $total, "ConfirmareCOK %", $this->logFilePath);
            $data_scan = new \DateTime(date('Y-m-d', strtotime($row['data'])));
            $data_now = new \DateTime("now");
            $data_finala = $row['data'];

            $data['cod_exp'] = $row['cod_expeditie'];
            $data['data_operatiei'] = $data_finala;
            $data['curier'] = $row['curier'];
            $data['curier_nume'] = $row['curier_nume'];
            $data['operator_id'] = $row['user'];

            if($this->SetLivrata($data)){
                // $this->log($row['cod']." Livrata", $this->logFilePath);
            }

        }
        $this->log($this->livrate." expeditii livrate", $this->logFilePath);
    }

    public function SetLivrata($data){
        $primitor = "CKPT Scan";

        $data_op = new \DateTime($data['data_operatiei']);
        if($data_op === false) $data_op = new DateTime("now");
        //istoric expeditii
        $this->insertIstExp($data['cod_exp'], self::OP_LIVRATA, $data_op, $data['operator_id']);

        $vu=[];
        $vu['operatiune'] = 'Livrat';
        $vu['primitor'] = $primitor;
        $vu['data_op'] = $data_op->format('Y-m-d');
        $vu['operator_id'] = $data['operator_id'];
        $vu['curier_livrare_id'] = $data['curier'];
        $vu['curier_livrare'] = $data['curier_nume'];
        $this->db->QueryUpdate('exp_prelucrate', $vu, "cod_expeditie=" . $data['cod_exp']);
        $this->livrate++;
        return true;

    }

    public function InchideRambursuriLivrate(){
        //inchide status_ramburs la initiale cu ramburs, daca expeditia de ramburs (tip_plata != cont) sau expeditia de returnare, a fost livrata : status_ramburs
        $this->log("=== InchideRambursuriLivrate ===", $this->logFilePath);

        if(!$this->checkForRun()){
            return;
        }
        $rambursuri_inchise = 0;

        $date = new \DateTime();
        $date->modify("-{$this->last} day");

        $perioada = "AND ie.DATA_OP  >= '" . $date->format("Y-m-d") . " 00:00:00'";

        //epin = initiala
        //epr = ramburs
        $query = "SELECT epin.expeditie, epin.cod_expeditie, epin.status_ramburs, epin.tip_plata
            FROM exp_prelucrate epr
            INNER JOIN exp_prelucrate epin
                ON (epin.expeditie = epr.referire and epin.anulata = 0)
            LEFT JOIN ist_exp ie
                ON epr.cod_expeditie = ie.cod_exp
            WHERE epin.status_ramburs IN (" . self::STATUS_RAMBURS_IN_DERULARE . "," . self::STATUS_RAMBURS_SPRE_CLIENT ."," . self::STATUS_RAMBURS_DECONTAT . ")
                AND epin.ramburs > 0
                AND ie.OPERATIUNE = " . self::OP_LIVRATA ."
                AND ((epin.tip_plata <> 3 AND epr.tip_exp = " . self::TIP_EXP_RBS .") OR epr.tip_exp = " . self::TIP_EXP_RETURNARE .")
                AND epr.anulata = 0
                {$perioada}
            GROUP BY epin.expeditie";

        //$this->log($query, $this->logFilePath);

        $sql = $this->db->QFetchRowArray($query);
        if (!empty($sql)) {
            $this->log($date->format("Y-m-d") . " -- ".count($sql)."  Rambursuri de inchis", $this->logFilePath);
            foreach ($sql as $key => $row) {
                $this->setStatusRamburs($row['cod_expeditie'], $row['status_ramburs'], self::STATUS_RAMBURS_INCHIS, $row['tip_plata']);
                $rambursuri_inchise++;
            }
        }
        $this->log($date->format("Y-m-d") . " -- {$rambursuri_inchise}  Rambursuri inchise", $this->logFilePath);
    }

    public function ModificareStatusRamburs(){
        // update status_ramburs = spre_client la toate expeditiile initiale
        // returnate sau care au ramburs de tip cash
        // si care au status_ramburs in_derulare sau decontat
        // si care au centru expeditor != centru scanare
        // in ultimele patru zile


        if(!$this->checkForRun()){
            return;
        }

        $this->log("=== ModificareStatusRamburs ===", $this->logFilePath);

        $fromDate = date('Y-m-d 00:00:00', strtotime('-4 days'));
        $this->log("Caut scanari din {$fromDate}", $this->logFilePath);

        //epin = initiala
        //epr = ramburs sau returnare
        $query = "SELECT epin.expeditie, epin.cod_expeditie, epin.status_ramburs, epin.tip_plata
            FROM scanari_coduri sc
            INNER JOIN exp_prelucrate epr
                ON (epr.expeditie = sc.expeditie and epr.anulata = 0)
            INNER JOIN exp_prelucrate epin
                ON (epin.expeditie = epr.referire and epin.anulata = 0)
            INNER JOIN clienti cl on cl.cod_cl = epr.expeditor_id
            INNER JOIN localitati lc on cl.cod_lc = lc.cod_lc
            WHERE 1
                AND epin.status_ramburs in (" . self::STATUS_RAMBURS_IN_DERULARE . "," . self::STATUS_RAMBURS_DECONTAT .")
                AND (epr.tip_exp = " . self::TIP_EXP_RETURNARE ." OR (epin.tip_plata <> 3 AND epr.tip_exp = " . self::TIP_EXP_RBS ."))
                AND lc.cod_centru <> sc.centru
                AND sc.data >= DATE_SUB(NOW(), INTERVAL 4 DAY)
                AND sc.is_awb = 1
            GROUP by epin.expeditie
        ";

        //error_log($query);
        $sql = $this->db->QFetchRowArray($query);
        if (!empty($sql)) {
            $this->log(count($sql). " expeditii gasite.", $this->logFilePath);
            foreach ($sql as $key => $row) {
                $this->setStatusRamburs($row['cod_expeditie'], $row['status_ramburs'], self::STATUS_RAMBURS_SPRE_CLIENT, $row['tip_plata']);
            }
        } else {
            $this->log("0 expeditii gasite", $this->logFilePath);
        }
    }

    function GenereazaCron() {

        $this->log("=== GenereazaCron ===", $this->logFilePath);
        $day = date('d');

        if ($this->custom_day > 0)
            $day = $this->custom_day;

        if (intval($day) > 0) {
            foreach ( $this->cronuri_zilnice as $key=>$val){
                $check_for_day = $this->db->QFetchRowArray("SELECT * FROM cron WHERE name = '".$key."' AND create_date >= '" . date('Y-m-d') . "' ");
                if (empty($check_for_day) && time() >= strtotime(date('Y-m-d '.$val['run_after']))) {
                    $cronuri[] = $key;
                    $this->log('SETEZ CRON '.$key, $this->logFilePath);
                }
            }
        }

        if(!empty($cronuri)){
            foreach ($cronuri as $tip){
                $this->db->QueryInsert('cron',
                    array(
                        'name' => $tip,
                        'create_date' => date('Y-m-d H:i:s'),
                    )
                );
            }
        }

    }

    function GenereazaFacturiCron(){

        $this->log("=== GenereazaFacturiCron ===", $this->logFilePath);

        $day = date('d');
        $day_of_week = date('N'); // 1 (for Monday) through 7 (for Sunday)

        if($this->custom_day > 0)
            $day = $this->custom_day;
        if($this->custom_day_of_week > 0)
            $day_of_week = $this->custom_day_of_week;

        if(intval($day) > 0){
            $check_for_lunara_data_specifica = $this->db->QFetchRowArray("SELECT * FROM cron WHERE name = 'FACTURARE_LUNARA_LA_DATA_SPECIFICA' AND create_date >= '".date('Y-m-d')."' ");
            if(empty($check_for_lunara_data_specifica)){
                $this->log('SETEZ CRON FACTURARE_LUNARA_LA_DATA_SPECIFICA', $this->logFilePath);
                $tip_facturare[] = "FACTURARE_LUNARA_LA_DATA_SPECIFICA";
            }
        }

        if($day >= (1 + self::DELAY_FACTURARE_LUNARA) && $day <= 15){
            $check_for_lunara = $this->db->QFetchRowArray("SELECT * FROM cron WHERE name = 'FACTURARE_LUNARA' AND create_date >= '".date('Y-m-01')."' AND create_date <= '".date('Y-m-15')."' ");
            if(empty($check_for_lunara)){
                $this->log('SETEZ CRON FACTURARE_LUNARA', $this->logFilePath);
                $tip_facturare[] = "FACTURARE_LUNARA";
            }
        }

        if($day >= (1 + self::DELAY_FACTURARE_BILUNARA) && $day <= 15){
            $check_for_bilunara = $this->db->QFetchRowArray("SELECT * FROM cron WHERE name = 'FACTURARE_BILUNARA' AND create_date >= '".date('Y-m-01')."' AND create_date <= '".date('Y-m-15')."' ");
            if(empty($check_for_bilunara)){
                $this->log('SETEZ CRON FACTURARE_BILUNARA', $this->logFilePath);
                $tip_facturare[] = "FACTURARE_BILUNARA";
            }
        }

        $zi_fact_bi = 16 + self::DELAY_FACTURARE_BILUNARA;
        if($day >= $zi_fact_bi){
            $check_for_bilunara = $this->db->QFetchRowArray("SELECT * FROM cron WHERE name = 'FACTURARE_BILUNARA' AND create_date >= '".date('Y-m-15')."' ");
            if(empty($check_for_bilunara)){
                $this->log('SETEZ CRON FACTURARE_BILUNARA', $this->logFilePath);
                $tip_facturare[] = "FACTURARE_BILUNARA";
            }
        }

        // == saptamanale == //

        if($day_of_week >= 1 + self::DELAY_FACTURARE_SAPTAMANALA){
            $date_start = date("Y-m-d", strtotime("last monday"));
            $date_stop = date("Y-m-d", strtotime("first sunday"));
            $check_for_saptamanala = $this->db->QFetchRowArray("SELECT * FROM cron WHERE name = 'FACTURARE_SAPTAMANALA' AND create_date >= '{$date_start}' AND create_date <= '{$date_stop}'");
            if(empty($check_for_saptamanala)){
                $this->log('SETEZ CRON FACTURARE_SAPTAMANALA', $this->logFilePath);
                $tip_facturare[] = "FACTURARE_SAPTAMANALA";
            }
        }

        if(!empty($tip_facturare)){
            foreach ($tip_facturare as $tip){
                $this->db->QueryInsert('cron',
                    array(
                        'name' => $tip,
                        'create_date' => date('Y-m-d H:i:s'),
                    )
                );
            }
        }
    }

    function RuleazaCron(){

        $this->log("=== RuleazaCron ===", $this->logFilePath);
        $query = $this->db->QFetchRowArray("SELECT * FROM cron WHERE status = 0 and name NOT LIKE 'FACTURARE%'");
        $cron_rez = [];
        if(!empty($query)){
            foreach ($query as $row){
                $this->db->QueryUpdate('cron', array('start_date'=> date("Y-m-d H:i:s")), " id = ".$row['id']);
                if($row['name'] == 'VERIFICARE_EXPEDITII')
                    $cron_rez[] = $this->VerificareExpeditii();
                // temp
                $this->db->QueryUpdate('cron', array('stop_date'=> date("Y-m-d H:i:s"), 'status'=>1, 'return_text'=> json_encode($cron_rez)), " id = ".$row['id']);
            }
        }
    }

    function GenereazaSpecificatiiCron(){
        if(!$this->checkForRun()){
            return;
        }
        $this->log("=== GenereazaSpecificatiiCron ===", $this->logFilePath);
        $query = $this->db->QFetchRowArray("SELECT * FROM cron WHERE status = 0 and name LIKE 'FACTURARE%'");
        $specificatii_rez = [];
        if(!empty($query)){
            foreach ($query as $row){
                $this->db->QueryUpdate('cron', array('start_date'=> date("Y-m-d H:i:s")), " id = ".$row['id']);
                $specificatii_rez[]= $this->GenereazaSpecificatii($row['name']);
                $this->db->QueryUpdate('cron', array('stop_date'=> date("Y-m-d H:i:s"), 'status'=>1), " id = ".$row['id']);
            }
        }
        foreach ($specificatii_rez as $rez){
            foreach ($rez  as $tip=> $nr){
                 $this->sent_log_email = true;
            }
        }
    }

    function GenereazaSpecificatii($tip_facturare = "ALL"){
        if(!$this->checkForRun()){
            return;
        }
        $this->log("=== GenereazaSpecificatii ===", $this->logFilePath);

        $day = date('d');
        $day_of_week = date('N'); // 1 (for Monday) through 7 (for Sunday)


        if($this->custom_day > 0) {
            $day = $this->custom_day;
        }

        if($this->custom_day_of_week > 0){
            $day_of_week = $this->custom_day_of_week;
        }

        // $day = 16;
        // $day_of_week = 1;

        $this->log("START FACTURARE Day:{$day} Day of week: {$day_of_week} {$tip_facturare}", $this->logFilePath);
        $specificatii = [];
        $specificatii['lunare'] = 0;
        $specificatii['bilunare'] = 0;
        $specificatii['saptamanale'] = 0;


        if($tip_facturare == "ALL" || $tip_facturare == "FACTURARE_LUNARA_LA_DATA_SPECIFICA"){
            $date_start = date("Y-m-d", strtotime( '-1 month', time()));
            $date_stop = date("Y-m-d", strtotime('-1 day', time()));
            $specificatii['lunare'] += $this->genereazaSpec($date_start, $date_stop, self::FACTURARE_LUNARA_LA_DATA_SPECIFICA);
        }

        if($day >= 1 && $day <= 15){
            if($tip_facturare == "ALL" || $tip_facturare == "FACTURARE_LUNARA"){
                $date_start = date("Y-m-d", strtotime("first day of previous month"));
                $date_stop = date("Y-m-d", strtotime('last day of previous month'));
                $this->log("START FACTURARE_LUNARA $date_start - $date_stop", $this->logFilePath);
                $specificatii['lunare'] += $this->genereazaSpec($date_start, $date_stop, self::FACTURARE_LUNARA);
            }

            if($tip_facturare == "ALL" || $tip_facturare == "FACTURARE_BILUNARA") {
                $date_start = date("Y-m-16", strtotime("first day of previous month"));
                $date_stop = date("Y-m-d", strtotime('last day of previous month'));
                $this->log("START FACTURARE_BILUNARA $date_start - $date_stop", $this->logFilePath);
                $specificatii['bilunare'] += $this->genereazaSpec($date_start, $date_stop, self::FACTURARE_BILUNARA);
            }
        }

        if($day >= 16){
            if($tip_facturare == "ALL" || $tip_facturare == "FACTURARE_BILUNARA") {
                $date_start = date("Y-m-01");
                $date_stop = date("Y-m-15");
                $this->log("START FACTURARE_BILUNARA $date_start - $date_stop", $this->logFilePath);
                $specificatii['bilunare'] += $this->genereazaSpec($date_start, $date_stop, self::FACTURARE_BILUNARA);
            }
        }

        if($day_of_week == 1 || true){
            if($tip_facturare == "ALL" || $tip_facturare == "FACTURARE_SAPTAMANALA") {
                $date_start = date("Y-m-d", strtotime("last week monday"));
                $date_stop = date("Y-m-d", strtotime("last week sunday"));
                $this->log("START FACTURARE_SAPTAMANALA $date_start - $date_stop", $this->logFilePath);
                $specificatii['saptamanale'] += $this->genereazaSpec($date_start, $date_stop, self::FACTURARE_SAPTAMANALA);
            }
        }
        return $specificatii;

    }

    function genereazaSpec($date_start, $date_stop, $tip_facturare){

        $modul = new ModulFacturare($this->config, 0, $this->db);
        $modul->mod_generare = 1;
        return $modul->generareFacturaProforma(0,$date_start, $date_stop,0, [], 0, $tip_facturare, [], false, 0, false, $this->logFilePath);
    }

    function StergeFacturi(){
        $this->log("=== StergeFacturiProforma ===", $this->logFilePath);

        $modul = new ModulFacturare($this->config, 0, $this->db);
        $nr = $modul->stergeFacturi([], true, [], $this->logFilePath);
        if($nr == 0) return;
        $this->log("=== {$nr} Facturi sterse ===", $this->logFilePath);
    }

    function TrimiteFacturiEmail(){
        $this->log("=== TrimiteFacturiEmail catre clienti ===", $this->logFilePath);

        $query = "SELECT f.*
            FROM exp_facturi f
            LEFT JOIN clienti c ON c.cod_cl  = f.cod_cl
            WHERE f.status >= ".ModulFacturare::FACTURA_FINALIZATA." AND f.trimisa = 0 AND c.fara_factura = 0 AND c.email_factura like '%@%'";

        $sql= $this->db->QFetchRowArray($query);

        if(empty($sql)) return;
        $this->log(count($sql)." email-uri de trimis", $this->logFilePath);
        $modul = new ModulFacturare($this->config, 0, $this->db);
        $nr = $modul->trimiteFacturiNetrimise($this->logFilePath);
        $this->log("{$nr} facturi trimise catre clienti", $this->logFilePath);
    }

    function TrimiteNotificareNewBank($test){
        $this->log("=== TrimiteNotificareNewBank catre clienti ===", $this->logFilePath);

        $modul = new ModulFacturare($this->config, 0, $this->db);

        $query = "SELECT cod_cl, email_factura FROM clienti WHERE (master = cod_cl or master = 0) and tarif = 1 and activ = 1 and sters = 0 and email_factura LIKE '%@%'";
        $sql= $this->db->QFetchRowArray($query);
        if(empty($sql)) return;
        $total = count($sql);
        $this->log($total." email-uri de trimis", $this->logFilePath);
        $trimise = 0;
        if($test){
            $modul->sendEmailNewBank("noc@curierdragonstar.ro", $this->logFilePath);
            return;
        }
        foreach ($sql as $client){
            $trimise++;
            $rez = "";
            if($modul->sendEmailNewBank($client['EMAIL_FACTURA'], $this->logFilePath)){
                $rez = "OK";
            }
            $proc = number_format(round((($trimise / $total) * 100) , 2), 2);
            $this->log("Trimit schimbare cont bancar ".$proc . "%  ---- {$trimise}/{$total} {$rez}", $this->logFilePath);
            sleep(0.25);
        }
        $this->log("{$trimise} emailuri schimbare cont bancar -> catre clienti", $this->logFilePath);
    }

    function TrimiteNotificare($test){
        $this->log("=== TrimiteNotificare catre clienti ===", $this->logFilePath);

        $modul = new ModulFacturare($this->config, 0, $this->db);

        $query = "
            SELECT cl.cod_cl, cl.EMAIL_FACTURA, ag.email
            FROM clienti cl
            LEFT JOIN clienti clm on clm.cod_cl = cl.master
            LEFT JOIN ag_vanzari ag on ag.id = cl.ag_vanzari_id
            WHERE cl.tarif = 1 and cl.activ = 1 and cl.sters = 0 and cl.ff_ok not in (1,2)
            and (clm.cod_cl is NULL or cl.master = cl.cod_cl)
            ORDER BY cl.cod_cl LIMIT 100";
        $sql= $this->db->QFetchRowArray($query);
        if(empty($sql)) return;
        $total = count($sql);
        $this->log($total." email-uri de trimis", $this->logFilePath);
        $trimise = 0;
        foreach ($sql as $client){
            if(empty($client['EMAIL_FACTURA']) || stripos($client['EMAIL_FACTURA'], '@') == false) {
                $this->log($client['cod_cl'] . " : empty email factura", $this->logFilePath);
                $this->db->QueryUpdate('clienti', ['ff_ok' => 2], " cod_cl = ".$client['cod_cl']);
                continue;
            }
            $trimise++;
            $rez = "";
            $this->log("Trimit catre : " . $client['cod_cl'], $this->logFilePath);
            if($modul->sendEmailNotificare($client['cod_cl'], $client['EMAIL_FACTURA'], "", $test, $this->logFilePath)){
                $rez = "OK";
                $this->log($client['cod_cl'] . " : OK", $this->logFilePath);
            }
            else {
                $this->db->QueryUpdate('clienti', ['ff_ok' => 2], " cod_cl = ".$client['cod_cl']);
            }
            $proc = number_format(round((($trimise / $total) * 100) , 2), 2);
            $this->log("Trimit notificari ".$proc . "%  ---- {$trimise}/{$total} {$rez}", $this->logFilePath);
            sleep(0.10);
            if($test) break;
        }
        $this->log("{$trimise} emailuri notificari -> catre clienti", $this->logFilePath);
    }

    function VerificareExpeditii(){
        $data_verificare = date('Y-m-d', strtotime(date('Y-m-d') . "-1 days"));

        $this->log("==== VerificareExpeditii {$data_verificare}====", $this->logFilePath);

        $query = "select c.nume as nume_client, e.expeditie, ef.invoice, ec.greutate as greutate_client, e.data_expeditie, e.greutate, e.valoare_totala_expeditie,
            u.nume as operator_curier, u.user as user_curier, uc.nume as operator_client, uc.user as user_client
            FROM exp_prelucrate e
            left join exp_facturi ef on ef.id = e.idfact
            left join clienti c on c.cod_cl = e.platitor_id
            left join users u on u.id = e.operator_id
            left join client_expeditii ec on ec.expeditie = e.expeditie
            left join users uc on uc.id = ec.user_id
            where e.data_expeditie BETWEEN '{$data_verificare} 00:00:00' and '{$data_verificare} 23:59:59'
            and e.colete > 0
            and e.greutate = 0
            and e.anulata = 0";
        $expeditii_cu_greutate_zero = $this->db->QFetchRowArray($query);
        if(!empty($expeditii_cu_greutate_zero))
            $this->sendOneEmail($this->config['email_list'], 'Expeditii cu greutate 0  ' . date('d.m.Y H'), '<style>table {border-collapse: collapse;}table, th, td {border: 1px solid black;}</style><h3>Expeditii cu greutate 0</h3>'.$this->array2table($expeditii_cu_greutate_zero), $this->logFilePath);
    }

    public function ValideazaRecantariri() {
        $this->log("=== ValidareRecantariri ===", $this->logFilePath);

        $query_recantarite = "select er.id as er_id, er.kg as er_kg, er.lungime as er_vol1, er.latime as er_vol2, er.inaltime as er_vol3, er.centru_id, er.user_id,
            ep.expeditie, ep.greutate, ep.greutate_vol, ep.idfact, ep.tip_exp, ep.referire, ep.mod_plata, group_concat(dfep.serie) as serie, ep.anulata,
            epr.expeditie as r_expeditie, epr.idfact as r_idfact, epr.tip_exp as r_tip_exp, epr.mod_plata as r_mod_plata, group_concat(dfepr.serie) as r_serie,
            epi.expeditie as i_expeditie, epi.idfact as i_idfact, epi.tip_exp as i_tip_exp, epi.mod_plata as i_mod_plata, group_concat(dfepi.serie) as i_serie
			from exp_recantarite er
			INNER JOIN exp_prelucrate ep on er.expeditie = ep.expeditie
			LEFT JOIN exp_prelucrate epr on (ep.expeditie = epr.referire and epr.tip_exp = 5 and epr.anulata = 0)
			LEFT JOIN exp_prelucrate epi on (ep.referire = epi.expeditie and epi.anulata = 0)
            LEFT JOIN decont_expeditii dep on (ep.expeditie = dep.expeditie and dep.anulata = 0)
		    LEFT JOIN decont_facturi dfep on (dep.factura_id = dfep.id and dfep.anulata = 0)
            LEFT JOIN decont_expeditii depr on (epr.expeditie = depr.expeditie and depr.anulata = 0)
		    LEFT JOIN decont_facturi dfepr on (depr.factura_id = dfepr.id and dfepr.anulata = 0)
            LEFT JOIN decont_expeditii depi on (epi.expeditie = depi.expeditie and depi.anulata = 0)
		    LEFT JOIN decont_facturi dfepi on (depi.factura_id = dfepi.id and dfepi.anulata = 0)
			where er.vKg = 0
            GROUP BY er.id";

        $recantarite = $this->db->QFetchRowArray($query_recantarite);
        if(empty($recantarite)){
			return 0;
		}
        $this->log("RW : count : ".count($recantarite), $this->logFilePath);
        foreach ($recantarite as $key => $expRow) {
            if(empty($expRow['er_id'])) continue;
            $greutate = $expRow['er_kg'];
			$greutate_vol = ExpeditieDto::getGreutateVolumetrica($expRow['er_vol1'], $expRow['er_vol2'], $expRow['er_vol3']);
            $greutate = ceil(max($greutate, $greutate_vol));
            $greutate_initiala = floor(max($expRow['greutate'], $expRow['greutate_vol']));
            if($expRow['anulata'] > 0)
                $this->db->QueryUpdate('exp_prelucrate', ['anulata' => 0, 'deleted_at' => null, 'deleted_by' => 0], "expeditie = ".$expRow['expeditie']);
			if($greutate_initiala == $greutate) {
				//validare only
				$this->db->QueryUpdate('exp_recantarite', ['vKg' => 1], "id = " . $expRow['er_id']);
                $this->log("RW standBy : {$expRow['expeditie']} : from {$greutate_initiala} to {$greutate}", $this->logFilePath);
				continue;
			}

			if(!in_array($expRow['tip_exp'], [0, 5, 7])) {
				$this->db->QueryUpdate('exp_recantarite', ['vKg' => 2, 'vMotiv' => "nu este initiala, retur colet sau returnare"], "id = " . $expRow['er_id']);
                $this->log("RW anulare : tip_exp : {$expRow['expeditie']} : {$expRow['tip_exp']}", $this->logFilePath);
				continue;
			}

            //recantareste : initiala, retur colet sau returnare
            if(empty($expRow['idfact']) && empty($expRow['serie'])){
                if(in_array($expRow['tip_exp'], [5, 7]))
                    $this->log("RW expeditie : {$expRow['expeditie']} : from {$greutate_initiala} to {$greutate}", $this->logFilePath);
                $ret = $this->reweightExpeditie($expRow['expeditie'], $greutate, $expRow['er_id'], $expRow['er_vol1'], $expRow['er_vol2'], $expRow['er_vol3']);
                if(true === $ret[0]) {
                    try {
                        //send FCM
                        if($expRow['mod_plata'] == 0)
                            $this->sendUpdateToAndroid($expRow['expeditie']);
                    }
                    catch(\PDOException $e) {
                        $this->log("ERROR RW : {$expRow['expeditie']} : sendUpdateToAndroid : ".$e, $this->logFilePath);
                    }
                }
                else {
                    $this->log("ERROR RW : {$expRow['expeditie']} : {$ret[1]}", $this->logFilePath);
                    $this->db->QueryUpdate('exp_recantarite', ['vKg' => 2, 'vMotiv' => $ret[1]], "id = " . $expRow['er_id']);
                }
            }
            else {
                $serie = empty($expRow['serie']) ? "wme : {$expRow['idfact']}" : "decont : {$expRow['serie']}";
                $this->db->QueryUpdate('exp_recantarite', ['vKg' => 2, 'vMotiv' => "facturata {$serie}"], "id = " . $expRow['er_id']);
                $this->log("RW facturata : {$expRow['expeditie']} : {$serie}", $this->logFilePath);
            }

			//returnare la initiala : reweight
			if($expRow['tip_exp'] == 0 && !empty($expRow['r_expeditie'])) {
                if(empty($expRow['r_idfact']) && empty($expRow['r_serie'])){
                    $ret = $this->reweightExpeditie($expRow['r_expeditie'], $greutate, 0, $expRow['er_vol1'], $expRow['er_vol2'], $expRow['er_vol3']);
                    if(true === $ret[0]) {
                        //$this->log("RW r_expeditie : {$expRow['r_expeditie']} : from {$greutate_initiala} to {$greutate}", $this->logFilePath);
                        try {
                            //send FCM
                            if($expRow['r_mod_plata'] == 0)
                                $this->sendUpdateToAndroid($expRow['r_expeditie']);
                        }
                        catch(\PDOException $e) {
                            $this->log("ERROR RW retur colet / returnare la initiala : {$expRow['r_expeditie']} : sendUpdateToAndroid : ".$e, $this->logFilePath);
                        }
                    }
                    else {
                        $this->log("ERROR RW retur colet / returnare la initiala : {$expRow['r_expeditie']} : {$ret[1]}", $this->logFilePath);
                    }
                }
                else {
                    $r_serie = empty($expRow['r_serie']) ? "wme : {$expRow['r_idfact']}" : "decont : {$expRow['r_serie']}";
                    $this->log("ERROR RW retur colet / returnare la initiala : {$expRow['r_expeditie']} : facturata : {$r_serie}", $this->logFilePath);
                }
            }
			//initiala la returnare : reweight initiala
			else if($expRow['tip_exp'] == 5 && !empty($expRow['i_expeditie']) && $expRow['i_tip_exp'] == 0) {
                if(empty($expRow['i_idfact']) && empty($expRow['i_serie'])){
                    $ret = $this->reweightExpeditie($expRow['i_expeditie'], $greutate, 0, $expRow['er_vol1'], $expRow['er_vol2'], $expRow['er_vol3']);
                    if(true === $ret[0]) {
                        //$this->log("RW initiala la returnare : {$expRow['i_expeditie']} : from {$greutate_initiala} to {$greutate}", $this->logFilePath);
                        try {
                            //send FCM
                            if($expRow['i_mod_plata'] == 0)
                                $this->sendUpdateToAndroid($expRow['r_expeditie']);
                        }
                        catch(\PDOException $e) {
                            $this->log("ERROR RW initiala la returnare : {$expRow['i_expeditie']} : sendUpdateToAndroid : ".$e, $this->logFilePath);
                        }
                    }
                    else {
                        $this->log("ERROR RW initiala la returnare : {$expRow['i_expeditie']} : {$ret[1]}", $this->logFilePath);
                    }
                }
                else {
                    $i_serie = empty($expRow['i_serie']) ? "wme : {$expRow['i_idfact']}" : "decont : {$expRow['i_serie']}";
                    $this->log("ERROR RW initiala la returnare : {$expRow['i_expeditie']} : facturata : {$i_serie}", $this->logFilePath);
                }
            }
        }
    }

    ///////////////WME

    function TrimiteFacturiInWinMentorNewApi(){
        $this->log("=== TrimiteFacturiInWinMentor new API ===", $this->logFilePath);
        try {
            $this->SyncFacturiNewApi();
        }
        catch(\Exception $ex) {
            error_log($ex->getMessage());
        }
    }

    function SyncFacturiNewApi(){
        $debug = false;
        $pattern_ = ["\\", "/", "\"", "\n", "\r", "\t", "\x08", "\x0c", " "];
        $replacements_ = ["", "", "", "", "", "", "", "", ""];

        $this->log("=== SyncFacturi WME NEW API ===", $this->logFilePath);

        require_once("holders/winmentor/parteneri.php");
        $wme = new WinMentorNewApi($this->config, $this->logFilePath);
        if($wme->MentenanceMode()){
            $this->log("WME MentenanceMode ".__FUNCTION__." ".intval($wme->MentenanceMode() / 60)." min", $this->logFilePath);
            return;
        }

        // coloana ef.sumamnt contine : valoare factura cu tva daca factura de android (coloana mod_generare in (0,3)) | valoare factura fara tva daca factura periodica (coloana mod_generare not in (0,3))
        // trimit in winmentor valoarea cu tva (coloana sumamnt)
        $data_mai_veche = date('Y-m-d H:i:s',strtotime('-5 minutes') );
        $query = "SELECT ef.id, ef.tip_factura as tipFactura, ef.trndate , ef.invoice, ef.sumamnt, ef.cod_cl, ef.mod_generare, ef.procTva,
            cl.cod_fiscal as cui, cl.termen_plata as termenPlata, cl.tip_tva as tipTva, cl.tva_incasare as tvaLaIncasare, cl.facturare_tip_tranzactie as tipTranzactie,
            ce.label as centruCost, GROUP_CONCAT(ep.expeditie) as expeditii, group_concat(efs.invoice) as stornoLaInvoice, group_concat(efs.trndate) as stornoLaInvoiceTrndate
            FROM exp_facturi ef
            LEFT JOIN exp_facturi efs on efs.id = ef.factura_initiala
	        LEFT JOIN clienti cl ON cl.cod_cl = ef.cod_cl
            LEFT JOIN localitati lc ON cl.cod_lc = lc.cod_lc
            LEFT JOIN centre ce ON ce.id = lc.cod_centru
            LEFT JOIN exp_prelucrate ep on ep.idfact = ef.id
            WHERE ef.wme = 0 AND ef.anulata = 0 AND (ef.mod_generare > 0 AND ef.status > ".ModulFacturare::FACTURA_FACTURATA." OR ef.mod_generare in (0,3))
            AND (ef.data_adaugare <= '{$data_mai_veche}' OR ef.data_adaugare IS NULL)
            AND IFNULL(cl.fara_factura, 0) = 0
            GROUP BY ef.id ORDER BY ef.trndate LIMIT 500";

        $sql= $this->db->QFetchRowArray($query);

        if(empty($sql))
            return;
        $this->logProgress(10, 1, "SyncFacturiNewApi WME NEW API % : ".count($sql), $this->logFilePath);

        $total = count($sql);
        $rez = [];
        foreach ($sql as $i => $row){
            $errors = "";
            $row['cod_cl'] = intval($row['cod_cl'] ?? 0);
            $row['cui'] = strtoupper(str_replace($pattern_, $replacements_, $row['cui']));
            $isFacturaAndroidCash = $row['mod_generare'] == 0;
            $isFacturaAndroidCard = $row['mod_generare'] == 3;
            $dontSendFacturaCashCard = false;

            $this->logProgress($i + 1, $total, "SyncFacturiNewApi WME NEW API % cod_cl: {$row['cod_cl']}", $this->logFilePath);

            if($row['cod_cl'] == 0){
                $this->db->QueryUpdate('exp_facturi', ['wme' => WinMentorNewApi::FACTURA_CU_EROARE, 'wme_message' => "Factura cu cod client 0 !"], " id = {$row['id']}");
                continue;
			}

            $this->logProgress($i + 1, $total, "SyncFacturiNewApi WME NEW API % cui: ".($row['cui'] ?? ""), $this->logFilePath);

            $ret = $wme->sendPartenerInWme($row['cod_cl'], $isFacturaAndroidCash || $isFacturaAndroidCard);
            //daca eroare trimitere partener : nu se trimite factura cash only
            if($ret['error'] == -1) {
                $errors .= $ret['message'] ?? '-1 UnknownErrorSendPartenerInWme';
                $dontSendFacturaCashCard = true;
            } else if($ret['error'] > 0) {
                $errors .= $ret['message'] ?? '1 UnknownErrorSendPartenerInWme';
            }
            else
                $this->db->QueryUpdate('clienti', ['wme' => 1, 'wme_message' => ''], "cod_cl = {$row['cod_cl']}");

            $ret = [];
            $ret['error'] = 1;
            $ret['message'] = "sendFactura ERROR : unknown";
            $arrFacturaToLog = $arrFactura = ['id' => $row['id'], 'invoice' => $row['invoice'], 'trndate' => $row['trndate'], 'isStorno' => ($row['tipFactura'] == 2),
                'stornoLaInvoice' => $row['stornoLaInvoice'], 'stornoLaInvoiceTrndate' => $row['stornoLaInvoiceTrndate'], 'sumamnt' => $row['sumamnt'],
                'cod_cl' => $row['cod_cl'], 'termenPlata' => intval($row['termenPlata']), 'tipTva' => $row['tipTva'], 'tvaLaIncasare' => $row['tvaLaIncasare'],
                'tipTranzactie' => $row['tipTranzactie'], 'centruCost' => $row['centruCost'], 'expeditii' => $row['expeditii'], 'procTva' => intval($row['procTva'])];
            unset($arrFacturaToLog['expeditii']);

            //$this->logProgress($i + 1, $total, "SyncFacturi WME NEW API % {$row['invoice']} DEBUG : ".print_r($arrFacturaToLog, true), $this->logFilePath);
            if($isFacturaAndroidCash) {//factura android
                $this->logProgress($i + 1, $total, "sendFacturaChitantaAndroidInWme WME NEW API % {$row['invoice']} ", $this->logFilePath);
                if($dontSendFacturaCashCard) {
                    $this->logProgress($i + 1, $total, "SKIPPED : dontSendFacturaCashCard {$row['invoice']}", $this->logFilePath);
                    $this->db->QueryUpdate('exp_facturi', ['wme'=> WinMentorNewApi::FACTURA_TRIMISA, 'wme_message'=>"SKIPPED"], "id=".$row['id']);
                    continue;
                } 
                $ret = $wme->sendFacturaChitantaAndroidInWme($arrFactura);
            }
            else if($isFacturaAndroidCard) {//factura android card
                $this->logProgress($i + 1, $total, "sendFacturaCardAndroidInWme WME NEW API % {$row['invoice']} ", $this->logFilePath);
                if($dontSendFacturaCashCard) {
                    $this->logProgress($i + 1, $total, "SKIPPED : dontSendFacturaCashCard {$row['invoice']}", $this->logFilePath);
                    $this->db->QueryUpdate('exp_facturi', ['wme'=> WinMentorNewApi::FACTURA_TRIMISA, 'wme_message'=>"SKIPPED"], "id=".$row['id']);
                    continue;
                } 
                $ret = $wme->sendFacturaCardAndroidInWme($arrFactura);
            }
            else {//factura periodica
                $this->logProgress($i + 1, $total, "sendFacturaPeriodicaInWme WME NEW API % {$row['invoice']} ", $this->logFilePath);
                $ret = $wme->sendFacturaPeriodicaInWme($arrFactura);
            }

            if($ret['error'] > 0) {
                if(strpos($ret['message'],'Connection') !== false) {
                    $this->log("Eroare comunicare WME: " . $ret['message'], $this->logFilePath);
                    $send = $this->sendOneEmail(["tc00mi@gmail.com", "noc@curierdragonstar.ro"], "Eroare comunicare WinMentor - " . date('d.m.Y H'), $ret['message'] . "\n" . date("d.m.Y"), $this->logFilePath);
                    if ($send) {
                        $this->log("Email trimis !", $this->logFilePath);
                    }
                    continue;
                }
                $this->db->QueryUpdate('exp_facturi', ['wme'=> WinMentorNewApi::FACTURA_CU_EROARE, 'wme_message'=>"{$ret['message']} | {$errors}"], "id=".$row['id']);
                $this->logProgress($i + 1, $total, "SyncFacturi WME NEW API % {$row['invoice']} ERROR : {$ret['message']} | {$errors}", $this->logFilePath);
                continue;
            }

            $this->db->QueryUpdate('exp_facturi', ['wme'=> WinMentorNewApi::FACTURA_TRIMISA, 'wme_message'=>"{$errors}"], "id=".$row['id']);
            $this->logProgress($i + 1, $total, "SyncFacturi WME NEW API % {$row['invoice']} : {$ret['message']} | {$errors}", $this->logFilePath);
        }
    }

    public function sendReportExpBuc() {
        $reportSender = new DscReporting($this->db);
        $this->log("=== Raport Expeditii Bucuresti ===", $this->logFilePath);
        $res = $reportSender->sendReportExpBuc();
        $this->log($res[1] ?? "BIG ERROR", $this->logFilePath);
    }

    public function sendReportExpOperational() {
        $reportSender = new DscReporting($this->db);
        $this->log("=== Raport pentru Operational ===", $this->logFilePath);
        $res = $reportSender->sendReportExpOperational();
        $this->log($res[1] ?? "BIG ERROR", $this->logFilePath);
    }

    public function sendReportExpRambursuri() {
        $reportSender = new DscReporting($this->db);
        $this->log("=== Raport pentru Rambursuri ===", $this->logFilePath);
        $res = $reportSender->sendReportExpRambursuri();
        $this->log($res[1] ?? "BIG ERROR", $this->logFilePath);
    }

    public function sendReportExpNelivrate() {
        $reportSender = new DscReporting($this->db);
        $this->log("=== Raport pentru Nelivrate ===", $this->logFilePath);
        $res = $reportSender->sendReportExpNelivrate();
        $this->log($res[1] ?? "BIG ERROR", $this->logFilePath);
    }
}