<?php // winmentorNewApi.php
require_once "facturare.php";
require_once "backend.php";
require_once "winmentorRestApi.php";

class WinMentorNewApi extends BackEnd
{
    public $db, $config;
    private $logFilePath;

    const SERVICIU_CURIERAT = 'Prestari servicii curierat';

    const FACTURA_TRIMISA = 1;
    const FACTURA_SI_CHITANTA_TRIMISA = 2;
    const FACTURA_CU_EROARE = 3;

	const FISIER_MENTENANTA = '/var/www/logs/app/_tmp/mentenance_wme_rest.lock';

    public $data;

    public function __construct($config = [], $logFilePath = false) {
        $this->db = new MysqlPDO();
		$this->config = $config;
        $this->procTva = $this->getProcentTVA(date("Y-m-d"));
        $this->logFilePath = $logFilePath;
    }

    function MentenanceMode(){

    	$ora = intval(date('H'));
    	if($ora <= 4){
            return true;
		}

		if(file_exists(self::FISIER_MENTENANTA)){
            $mentenance_time_wme = intval(file_get_contents(self::FISIER_MENTENANTA));
            if($mentenance_time_wme > 0 && (time() - $mentenance_time_wme ) < 7200){
            	return 7200 - (time() - $mentenance_time_wme );
			} else {
            	unlink(self::FISIER_MENTENANTA);
			}
		}
    	return false;
	}

	function SetMentenanceMode($active = true){
    	if($active){
            file_put_contents(self::FISIER_MENTENANTA, time());
		} else {
            unlink(self::FISIER_MENTENANTA);
		}

	}

    function sendPartenerInWme($id, $cash){
        require_once("holders/winmentor/parteneri.php");
        require_once("holders/winmentor/sedii.php");
        require_once("holders/winmentor/conturiBancare.php");

        $id = intval($id);
        if($id == 0) return "Factura nu are client definit";

        $queryClient = "SELECT cl.cod_cl as id, cl.master, cl.facturare_separata as facturareSeparata,
            cl.nume, cl.adresa, cl.nume_societate as numeSocietate, cl.adresa_sediu_social as adresaSediuSocial,
            cl.cod_fiscal as cui, cl.reg_com as regCom, cl.termen_plata as termenPlata, cl.cont_fa as contBancar,
            j.cod_jd as judet, lc.nume_lc as localitate, cl.ag_vanzari_id as agentVanzari
            FROM clienti cl
            LEFT JOIN localitati lc ON cl.cod_lc = lc.cod_lc
            LEFT JOIN judete j ON j.cod_jd = lc.cod_jd
            WHERE cl.cod_cl = {$id}";

        $client = $this->db->QFetchRowAssoc($queryClient);
        if(empty($client)) return ['error' => -1, 'message' => "APP : Client lipsa : {$id}"];
        //se trimit numai partenerii cu cui valid
        $cl_cui = ExpeditieDto::sanitizeCuiRO($client['cui'] ?? '');
        if($cash && empty($cl_cui)) {
            return ['error' => -1, 'message' => "APP : Client {$id} nu are CUI : {$client['cui']}"];
        }
        //check cui valid
        if($cash && ExpeditieDto::isValidCui($cl_cui) == false) {
            return ['error' => -1, 'message' => "APP : Client {$id} nu are CUI valid : {$client['cui']}"];
        }
        //$this->logProgress(111, 1, "sendPartenerInWme WME NEW API %  client ".print_r($client, true), $this->logFilePath);

        $pattern = ["\\", "/", "\"", "\n", "\r", "\t", "\x08", "\x0c"];
        $replacements = ["", "", "", "", "", "", "", ""];
        $pattern_ = ["\\", "/", "\"", "\n", "\r", "\t", "\x08", "\x0c", " "];
        $replacements_ = ["", "", "", "", "", "", "", "", ""];
        $patternSlash = ["\\", "\"", "\n", "\r", "\t", "\x08", "\x0c", " "];
        $replacementsSlash = ["", "", "", "", "", "", "", ""];
        //sedii
        $sedii = [];
        $sediu = new Sedii(
            $client['id'],
            strtoupper(str_replace($pattern_, $replacements_, $cl_cui)),
            strtoupper(str_replace($pattern, $replacements, empty($client['numeSocietate']) ? $client['nume'] : $client['numeSocietate'])),
            strtoupper(str_replace($pattern, $replacements, $client['judet'])),
            strtoupper(str_replace($pattern, $replacements, $client['localitate'])),
            strtoupper(str_replace($pattern, $replacements, empty($client['adresaSediuSocial']) ? $client['adresa'] : $client['adresaSediuSocial'])),
            "SFL",
            ($client['agentVanzari'] ?? ""),
            "RO"
        );
        $sedii[] = $sediu;
        $conturiBancare = [];
        if(!empty($client['contBancar']))
            $conturiBancare[] = new ConturiBancare(strtoupper(str_replace($pattern_, $replacements_, $client['contBancar'])));


        if($cash == false && $client['facturareSeparata'] == 1 && !($client['master'] == 0 || $client['master'] == $client['id'])) {
            $sediu->setTipSediu('FL');
            $queryMaster = "SELECT cl.cod_cl as id, cl.master, cl.facturare_separata as facturareSeparata,
                cl.nume, cl.adresa, cl.nume_societate as numeSocietate, cl.adresa_sediu_social as adresaSediuSocial,
                cl.cod_fiscal as cui, cl.reg_com as regCom, cl.termen_plata as termenPlata, cl.cont_fa as contBancar,
                j.cod_jd as judet, lc.nume_lc as localitate, cl.ag_vanzari_id as agentVanzari
                FROM clienti cl
                LEFT JOIN localitati lc ON cl.cod_lc = lc.cod_lc
                LEFT JOIN judete j ON j.cod_jd = lc.cod_jd
                WHERE cl.cod_cl = {$client['master']}";
            $master = $this->db->QFetchRowAssoc($queryMaster);
            //sediu master
            $sediuMasterForPc = new Sedii(
                $master['id'],
                strtoupper(str_replace($pattern_, $replacements_, ExpeditieDto::sanitizeCuiRO($master['cui'] ?? ''))),
                strtoupper(str_replace($pattern, $replacements, empty($client['numeSocietate']) ? $client['nume'] : $client['numeSocietate'])),
                strtoupper(str_replace($pattern, $replacements, $master['judet'])),
                strtoupper(str_replace($pattern, $replacements, $master['localitate'])),
                strtoupper(str_replace($pattern, $replacements, empty($client['adresaSediuSocial']) ? $client['adresa'] : $client['adresaSediuSocial'])),
                "SFL",
                ($master['agentVanzari'] ?? ""),
                "RO"
            );
            $sedii[] = $sediuMasterForPc;
        }

        $partener = new Parteneri(
            strtoupper(str_replace($pattern_, $replacements_, ExpeditieDto::sanitizeCuiRO($client['cui'] ?? ''))),
            $id,
            strtoupper(str_replace($patternSlash, $replacementsSlash, $client['regCom'])),
            strtoupper(str_replace($pattern, $replacements, empty($client['numeSocietate']) ? $client['nume'] : $client['numeSocietate'])),
            intval($client['termenPlata']),
            $id,
            $sedii,
            $conturiBancare,
            []
        );

        //TODO insert or update
        $api = new WinmentorRestApi(getenv('WME_USER'), getenv('WME_PASS'));
        $payload = "";
        $ret['error'] = 0;
        $ret['message'] = "";
        try {
            $payload = json_encode($partener);
            //$this->logProgress(111, 1, "sendPartener WME NEW API DEBUG payload : {$payload}", $this->logFilePath);
            $response = $api->sendPartener($payload);
        }
        catch(\Exception $ex) {
            file_put_contents('/var/www/logs/app/wmeRest.log', print_r(['data' => "[ ".date('d-m-Y H:i:s')." ]", 'errorWmeRestApi' => 1, 'error' => $ex->getMessage(), 'payload' => $payload], true) , FILE_APPEND | LOCK_EX);
            $ret['error'] += 1;
            $ret['message'] = "APP : Partener EXCEPTION : " . $ex->getMessage();
            return $ret;
        }
        //$this->logProgress(112, 1, "sendPartener WME NEW API DEBUG response : " . print_r($response, true), $this->logFilePath);
        if(strtolower($response['Error'] ?? 'Error') != 'ok'){
            //agentul exista
            if(str_starts_with(strtolower($response['Error']), "exista deja un partener"))
                return $ret;
            $ret['error'] += 1;
            $ret['message'] = "WME : Partener ERROR : " . ($response['Error'] ?? 'unknownError');
        }
        return $ret;
	}

    public function sendFacturaChitantaAndroidInWme($arrFactura){
        require_once("holders/winmentor/facturi.php");
        require_once("holders/winmentor/faDocumente.php");
        require_once("holders/winmentor/items.php");

        require_once("holders/winmentor/chitante.php");
        require_once("holders/winmentor/chDocumente.php");
        require_once("holders/winmentor/tranzactii.php");
        require_once("holders/winmentor/distribuireValoare.php");

        $procTva = empty($arrFactura['procTva']) ? $this->procTva : $arrFactura['procTva'];
        $ret = [];
        $ret['error'] = 0;
        $ret['message'] = false;

        $anLucru = date('Y', strtotime($arrFactura['trndate']));
        $lunaLucru = intval(date('m', strtotime($arrFactura['trndate'])));

		$dataFactura = date("d.m.Y", strtotime($arrFactura['trndate']));
		$dataScadenta = date("d.m.Y", strtotime("{$arrFactura['trndate']} + {$arrFactura['termenPlata']} days"));

        $invoiceTokens = explode('-',$arrFactura['invoice']);
        if(count($invoiceTokens) > 1 || strlen($arrFactura['invoice']) != 12) 
            return ['error' => 1, 'message' => "APP : Factura ERROR : Nu este factura de android : {$arrFactura['invoice']}"];

        $simbolCarnet = substr($arrFactura['invoice'], 0, 5);
        $nrDoc = $arrFactura['invoice'];
        $denumireServiciu = self::SERVICIU_CURIERAT;
        $arrFactura['sumamnt'] = $arrFactura['isStorno'] ? (-1 * abs($arrFactura['sumamnt'])) : abs($arrFactura['sumamnt']);

        if(!empty($arrFactura['expeditii']))
            $denumireServiciu .= " Expeditii facturate: ".$arrFactura['expeditii'];

        $observatii = $arrFactura['isStorno'] && !empty($arrFactura['stornoLaInvoice']) ? "Storno la factura {$arrFactura['stornoLaInvoice']} din data ".date("d.m.Y", strtotime($arrFactura['stornoLaInvoiceTrndate'])) : "";

        //factura
        $items = [new Items("SC{$procTva}", 1, round($arrFactura['sumamnt'], 2), $arrFactura['centruCost'], $denumireServiciu)];
        $faDocumente = [new FaDocumente($simbolCarnet, $nrDoc, $arrFactura['cod_cl'], $dataFactura, $arrFactura['tipTva'], $arrFactura['tipTranzactie'], $arrFactura['tvaLaIncasare'], 'RON', intval($arrFactura['isStorno']), $observatii, true, $dataScadenta, $items)];
        $factura = new Facturi($anLucru, $lunaLucru, $faDocumente);

        //chitanta
        $distribuireValoare = [new DistribuireValoare($simbolCarnet, $nrDoc, round($arrFactura['sumamnt'], 2))];
        $tranzactii = [new Tranzactii($simbolCarnet, $nrDoc, $dataFactura, $arrFactura['cod_cl'], round($arrFactura['sumamnt'], 2), $arrFactura['centruCost'], $distribuireValoare)];
        $chDocumente = [new ChDocumente($arrFactura['centruCost'], $dataFactura, "RON", $tranzactii)];
        $chitanta = new Chitante($anLucru, $lunaLucru, $chDocumente);

        //TODO insert or update
        $api = new WinmentorRestApi(getenv('WME_USER'), getenv('WME_PASS'));
        $payload = "";

        try {
            $payload = json_encode($factura);
            //$this->logProgress(113, 1, "sendFacturaAndroidInWme WME NEW API % : {$payload} ", $this->logFilePath);
            $response = $api->sendFactura($payload);
        }
        catch(\Exception $ex) {
            $ret['error'] += 1;
            $ret['message'] = "APP : Factura CASH EXCEPTION : " . $ex->getMessage();
            file_put_contents('/var/www/logs/app/wmeRest.log', print_r(['data' => "[ ".date('d-m-Y H:i:s')." ]", 'errorWmeRestApi' => $ret['error'], 'errorMsg' => $ret['message'], 'payload' => $payload], true) , FILE_APPEND | LOCK_EX);
        }

        //daca factura exista, rest wme intoarce ok cu ErrorList = []
        if(strtolower($response['result'] ?? 'error') != 'ok'){
            $ret['error'] += 1;
            $errorMsg = "WME : Factura CASH ERROR : " . json_encode($response['ErrorList'] ?? ['unknownError']);
            $ret['message'] = $ret['message'] === false ? $errorMsg : "{$ret['message']} | {$errorMsg}";
            file_put_contents('/var/www/logs/app/wmeRest.log', print_r(['data' => "[ ".date('d-m-Y H:i:s')." ]", 'errorWmeRestApi' => $ret['error'], 'errorMsg' => $ret['message'], 'payload' => $payload], true) , FILE_APPEND | LOCK_EX);
        }

        try {
            $payload = json_encode($chitanta);
            //$this->logProgress(113, 1, "sendChitantaAndroidInWme WME NEW API % : {$payload} ", $this->logFilePath);
            $response = $api->sendChitanta($payload);
        }
        catch(\Exception $ex) {
            $ret['error'] += 1;
            $errorMsg = "APP : Chitanta EXCEPTION : " . $ex->getMessage();
            $ret['message'] = $ret['message'] === false ? $errorMsg : "{$ret['message']} | {$errorMsg}";
            file_put_contents('/var/www/logs/app/wmeRest.log', print_r(['data' => "[ ".date('d-m-Y H:i:s')." ]", 'errorWmeRestApi' => $ret['error'], 'errorMsg' => $ret['message'], 'payload' => $payload], true) , FILE_APPEND | LOCK_EX);
            return $ret;
        }

        if(strtolower($response['result'] ?? 'error') != 'ok'){
            $ret['error'] += 1;
            $errorMsg = "WME : Chitanta ERROR : " . json_encode($response['ErrorList'] ?? ['unknownError']);
            $ret['message'] = $ret['message'] === false ? $errorMsg : "{$ret['message']} | {$errorMsg}";
            file_put_contents('/var/www/logs/app/wmeRest.log', print_r(['data' => "[ ".date('d-m-Y H:i:s')." ]", 'errorWmeRestApi' => $ret['error'], 'errorMsg' => $ret['message'], 'payload' => $payload], true) , FILE_APPEND | LOCK_EX);
        }
        return $ret;
	}

    public function sendFacturaCardAndroidInWme($arrFactura){
        require_once("holders/winmentor/facturi.php");
        require_once("holders/winmentor/faDocumente.php");
        require_once("holders/winmentor/items.php");

        $procTva = empty($arrFactura['procTva']) ? $this->procTva : $arrFactura['procTva'];
        $ret = [];
        $ret['error'] = 0;
        $ret['message'] = '';

        $anLucru = date('Y', strtotime($arrFactura['trndate']));
        $lunaLucru = intval(date('m', strtotime($arrFactura['trndate'])));

		$dataFactura = date("d.m.Y", strtotime($arrFactura['trndate']));
		$dataScadenta = date("d.m.Y", strtotime("{$arrFactura['trndate']} + {$arrFactura['termenPlata']} days"));

        $invoiceTokens = explode('-',$arrFactura['invoice']);
        if(count($invoiceTokens) > 1 || strlen($arrFactura['invoice']) != 12) 
            return ['error' => 1, 'message' => "APP : Factura ERROR : Nu este factura de android : {$arrFactura['invoice']}"];

        $simbolCarnet = substr($arrFactura['invoice'], 0, 5);
        $nrDoc = $arrFactura['invoice'];
        $denumireServiciu = self::SERVICIU_CURIERAT;
        $arrFactura['sumamnt'] = $arrFactura['isStorno'] ? (-1 * abs($arrFactura['sumamnt'])) : abs($arrFactura['sumamnt']);

        if(!empty($arrFactura['expeditii']))
            $denumireServiciu .= " Expeditii facturate: ".$arrFactura['expeditii'];

        $observatii = $arrFactura['isStorno'] && !empty($arrFactura['stornoLaInvoice']) ? "Storno la factura {$arrFactura['stornoLaInvoice']} din data ".date("d.m.Y", strtotime($arrFactura['stornoLaInvoiceTrndate'])) : "";

        //factura
        $items = [new Items("SC{$procTva}", 1, round($arrFactura['sumamnt'], 2), $arrFactura['centruCost'], $denumireServiciu)];
        $faDocumente = [new FaDocumente($simbolCarnet, $nrDoc, $arrFactura['cod_cl'], $dataFactura, $arrFactura['tipTva'], $arrFactura['tipTranzactie'], $arrFactura['tvaLaIncasare'], 'RON', intval($arrFactura['isStorno']), $observatii, true, $dataScadenta, $items)];
        $factura = new Facturi($anLucru, $lunaLucru, $faDocumente);

        //TODO insert or update
        $api = new WinmentorRestApi(getenv('WME_USER'), getenv('WME_PASS'));
        $payload = "";

        //send factura only
        try {
            $payload = json_encode($factura);
            //$this->logProgress(113, 1, "sendFacturaAndroidInWme WME NEW API % : {$payload} ", $this->logFilePath);
            $response = $api->sendFactura($payload);
        }
        catch(\Exception $ex) {
            $ret['error'] += 1;
            $ret['message'] = "APP : Factura CARD EXCEPTION : " . $ex->getMessage();
            file_put_contents('/var/www/logs/app/wmeRest.log', print_r(['data' => "[ ".date('d-m-Y H:i:s')." ]", 'errorWmeRestApi' => $ret['error'], 'errorMsg' => $ret['message'], 'payload' => $payload], true) , FILE_APPEND | LOCK_EX);
        }

        //daca factura exista, rest wme intoarce ok cu ErrorList = []
        if(strtolower($response['result'] ?? 'error') != 'ok'){
            $ret['error'] += 1;
            $ret['message'] = "WME : Factura CARD ERROR : " . json_encode($response['ErrorList'] ?? ['unknownError']);
            file_put_contents('/var/www/logs/app/wmeRest.log', print_r(['data' => "[ ".date('d-m-Y H:i:s')." ]", 'errorWmeRestApi' => $ret['error'], 'errorMsg' => $ret['message'], 'payload' => $payload], true) , FILE_APPEND | LOCK_EX);
        }
        return $ret;
	}

    public function sendFacturaPeriodicaInWme($arrFactura){
        require_once("holders/winmentor/facturi.php");
        require_once("holders/winmentor/faDocumente.php");
        require_once("holders/winmentor/items.php");

        $procTva = empty($arrFactura['procTva']) ? $this->procTva : $arrFactura['procTva'];
        $ret = [];
        $ret['error'] = 0;
        $ret['message'] = '';

        $anLucru = date('Y', strtotime($arrFactura['trndate']));
        $lunaLucru = intval(date('m', strtotime($arrFactura['trndate'])));

		$dataFactura = date("d.m.Y", strtotime($arrFactura['trndate']));
		$dataScadenta = date("d.m.Y", strtotime("{$arrFactura['trndate']} + {$arrFactura['termenPlata']} days"));

        $invoiceTokens = explode('-',$arrFactura['invoice']);
        if(count($invoiceTokens) != 2) 
            return ['error' => 1, 'message' => "APP : Factura ERROR : {$arrFactura['invoice']} nu este periodica"];

        $simbolCarnet = $invoiceTokens[0];
        $nrDoc = intval($invoiceTokens[1]);
        $denumireServiciu = self::SERVICIU_CURIERAT;

        if(!empty($arrFactura['expeditii']))
            $denumireServiciu .= " Expeditii facturate: ".$arrFactura['expeditii'];

        $observatii = $arrFactura['isStorno'] && !empty($arrFactura['stornoLaInvoice']) ? "Storno la factura {$arrFactura['stornoLaInvoice']} din data ".date("d.m.Y", strtotime($arrFactura['stornoLaInvoiceTrndate'])) : "";

        //factura linie : coloana valoare este fara tva
        // trimit in winmentor valoarea cu tva
        $queryLinii = "SELECT nume, procTva, cantitate, valoare FROM factura_linie WHERE factura_id = {$arrFactura['id']}";
		$linii = $this->db->QFetchRowArray($queryLinii);
        $items = [];
        if(!empty($linii)){
            foreach ($linii as $linie){
                if($linie['procTva'] == 0) {
                    $linie['procTva'] = $procTva; // daca nu este specificat procentul de tva, folosesc cel implicit
                }
                $items[] = new Items("SC{$linie['procTva']}", intval($linie['cantitate']), round($linie['valoare'], 2), $arrFactura['centruCost'], $linie['nume']);
            }
        }
        else {
            $arrFactura['sumamnt'] = $arrFactura['isStorno'] ? (-1 * abs($arrFactura['sumamnt'])) : abs($arrFactura['sumamnt']);
            $items = [new Items("SC{$procTva}", 1, round($arrFactura['sumamnt'], 2), $arrFactura['centruCost'], $denumireServiciu)];
        }
        $faDocumente = [new FaDocumente($simbolCarnet, $nrDoc, $arrFactura['cod_cl'], $dataFactura, $arrFactura['tipTva'], $arrFactura['tipTranzactie'], $arrFactura['tvaLaIncasare'], 'RON', intval($arrFactura['isStorno']), $observatii, false, $dataScadenta, $items)];
        $factura = new Facturi($anLucru, $lunaLucru, $faDocumente);

        $api = new WinmentorRestApi(getenv('WME_USER'), getenv('WME_PASS'));
        $payload = "";

        try {
            $payload = json_encode($factura);
            $response = $api->sendFactura($payload);
            file_put_contents('/var/www/logs/app/wmeRest.log', print_r($response, true) , FILE_APPEND | LOCK_EX);
        }
        catch(\Exception $ex) {
            $ret['error'] += 1;
            $ret['message'] = "APP : FACTURA P EXCEPTION : " . $ex->getMessage();
            file_put_contents('/var/www/logs/app/wmeRest.log', print_r(['data' => "[ ".date('d-m-Y H:i:s')." ]", 'errorWmeRestApi' => $ret['error'], 'errorMsg' => $ret['message'], 'payload' => $payload], true) , FILE_APPEND | LOCK_EX);
            return $ret;
        }

        if(strtolower($response['result'] ?? 'error') != 'ok'){
            $ret['error'] += 1;
            $ret['message'] = "WME : FACTURA P ERROR : " . json_encode($response ?? ['unknownError']);
            file_put_contents('/var/www/logs/app/wmeRest.log', print_r(['data' => "[ ".date('d-m-Y H:i:s')." ]", 'errorWmeRestApi' => $ret['error'], 'errorMsg' => $ret['message'], 'payload' => $payload], true) , FILE_APPEND | LOCK_EX);
        }
        return $ret;
	}
}