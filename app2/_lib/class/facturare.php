<?php
require_once "SendEmailMailGun.php";

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;

class ModulFacturare extends BackEnd
{
    public $final_result;
    public $action_module;
    public $page_prefix;
    public $site_prefix;
    public $table;
    public $cautare;
    private $debug;

    public $servicii = array(
        '01'=> 'Prestari servicii curierat',
        '02'=> 'Discount',
        '03'=> 'Majorare tarif conform GPI'
    );

    public $mod_generare = 2;

    public $facturi_create = 0;

    const RELEASE_DATE = '2020-01-01';

    const FACTURA_TIP_NORMALA = 1;
    const FACTURA_TIP_STORNO = 2;

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


    public $status_factura = array(
        self::FACTURA_PREGATITA     => 'Pregatita',
        self::FACTURA_FACTURATA     => 'Facturata',
        self::FACTURA_FINALIZATA    => 'Finalizata',
        self::FACTURA_TRIMISA       => 'Trimisa',
        self::FACTURA_EMAIL_DESCHIS => 'Email deschis',
        self::FACTURA_VIZUALIZATA   => 'Vizualizata',
    );


    const EXPEDITIE_IN_CURS_DE_FACTURARE = 32;
    const EXPEDITIE_FACTURATA = 33;
    const EXPEDITIE_SCOASA_DIN_FACTURARE = 34;

    public $tip_factura = self::FACTURA_TIP_NORMALA;
    public $expeditii_factura = [];
    public $cod_cl_factura = 0;

    const MAIL_SAVE_PATH = '/mnt/nfs4/app/factpdf/';
    const XLS_SAVE_PATH = '/var/www/download/facturare/';

    public $email_template = "
    Buna ziua,

Va trimitem atasat factura cu numarul {serie}{perioada}.<br>
<br>
Va multumesc.<br>
<br>
Pentru orice probleme/sesizari legate de factura, va rugam contactati: <br>
Musat Irina<br>
Departamentul Contabilitate<br>
Telefon 0760235005<br>
Email irina.musat@curierdragonstar.ro<br>
";


    /**
     * The constructor for the 'Workspace' class
     * Calls BackEnd constructor
     * Cals Actions function
     *
     * @param array $config
     * @param integer $act (0/1) Specifies if actions are alowed or not
     * @access public
     * @see Actions()
     */
    function __construct($config = 0, $act = 1, $db = 0)
    {
        parent::__construct($config, $db);
        $this->procTva = $this->getProcentTVA(date("Y-m-d"));
        $this->debug = false;

        ini_set('memory_limit', '-1');
        $this->vars['title_page'] = 'Facturare';
        $this->page_prefix = 'facturare_';

        if ($act) {
            //ACTIONS
            $this->Actions();
        }

    }

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
        if (in_array("facturare", $this->user_rights) || $this->user_profile == 10){
            //  actiuni care se pot accesa de orice permisiune
            if (isset($arr[1]) && $arr[1] == 'facturare') {
                $this->final_result = $this->afisareTaskuri();
                $flag = 1;
            } else if (isset($arr[0]) && $arr[0] == 'facturare' && isset($arr[1]) && $arr[1] == 'printare_multipla') {
                $this->final_result = $this->printareMultipla(isset($arr[2]) ? $arr[2] : '', isset($arr[3]) ? $arr[3] : '');
                $flag = 1;
            } else if (isset($arr[0]) && $arr[0] == 'facturare' && isset($arr[1]) && $arr[1] == 'acc') {
                echo $this->acc();
                $flag = 1;
            } else if (isset($arr[1]) && $arr[1] == 'json' && isset($arr[2]) && $arr[2] == 'taskuri') {
                echo $this->JSON_Taskuri((isset($arr[3]) ? $arr[3] : 1), 'taskuri');
                $flag = 1;
            } else if (isset($arr[1]) && $arr[1] == 'json' && isset($arr[2]) && $arr[2] == 'detalii_task') {
                echo $this->JSON_DetaliiTask($arr[3], false, (isset($arr[4]) ? $arr[4] : ''));
                $flag = 1;
            } else if (isset($arr[1]) && $arr[1] == 'json' && isset($arr[2]) && $arr[2] == 'detalii_task_exp_nefacturate') {
                echo $this->JSON_DetaliiTask($arr[3], true);
                $flag = 1;
            } else if (isset($arr[1]) && $arr[1] == 'json' && isset($arr[2]) && $arr[2] == 'detalii_task_exp_neasociate') {
                echo $this->JSON_DetaliiTask($arr[3], 2);
                $flag = 1;
            } else if (isset($arr[1]) && $arr[1] == 'json' && isset($arr[2]) && $arr[2] == 'cautare_expeditii') {
                echo $this->JSON_CautaExpeditii($arr[3], true);
                $flag = 1;
            } else if (isset($arr[1]) && $arr[1] == 'json' && isset($arr[2]) && $arr[2] == 'pozitii_factura') {
                echo $this->JSON_PozitiiFactura($arr[3]);
                $flag = 1;
            } else if (isset($arr[1]) && $arr[1] == 'json' && isset($arr[2]) && $arr[2] == 'facturi_cautare') {
                echo $this->JSON_Taskuri($arr[3], 'facturi_cautare');
                $flag = 1;
            } else if (isset($arr[1]) && $arr[1] == 'json' && isset($arr[2]) && $arr[2] == 'platitori') {
                echo $this->JSON_ListePlatitori();
                $flag = 1;
            } else if (isset($arr[1]) && $arr[1] == 'xls' && isset($arr[2]) && $arr[2] == 'borderou_expeditii') {
                echo $this->ExportBorderouXLS($arr[3]);
                $flag = 1;
            } else if (isset($arr[1]) && $arr[1] == 'xls' && isset($arr[2]) && $arr[2] == 'facturi') {
                echo $this->ExportFacturi();
                $flag = 1;
            } else if(empty($arr[1])) {
                $this->final_result = $this->afisareTaskuri();
                $flag = 1;
            }
            /*
            else if($arr[0] == 'facturare' && $arr[1] == 'sstt65migm') {
                set_time_limit(3600);
                $expeditii = [81341151,81349873,81357808,81360497,81422386,81444909,81435228,81453992,81453725,81453208,81453227,81453264,81453284,81453322,81453354,81453390,81453435,81453465,81453505,81453554,81453577,81453646,81452489,81452587,81423106,81423414,81423464,81424171,81429524,81452409,81452455,81452534,81452964,81453438,81447539,81447576,81447732,81448844,81448927,81447623,81454111,81457770,81457736,81449269,81455275,101252521,81457881,81457898,81446923,81451909,81451989,81452189,81452196,81452320,81453079,81447111,81447044,81446938,81446954,81446976,81446998,81447653,81445406,81447375,81434146,81446907,81446575,81446576,81446577,81446579,81446580,81446581,81449317,81449441,81449175,81449126,81446717,81446800,81446840,81446843,81448851,81448973,81449043,81448102,81448391,81448887,81448192,81448247,81447889,81448847,81448855,81455370,81449153,81455104,81455112,81454005,81449700,81449718,81449827,81449949,81449500,81450093,81451329,81453617,81449383,81448375,81446933,81447133,81447417,81447585,81447736,81447970,81448042,81453023,81448275,81454109,81454910,81469964,81465736,81465787,81465686,81465642,81465610,81463397,81465245,81465298,81465339,81465386,81465427,81465479,81465529,81465569,81468606,81461929,81448841,81468643,81469481,81465227,81463827,81470948,81470953,81470942,81466585,81460567,81460385,81460477,81460644,81460540,81470373,81448733,81449370,81460614,81458770,81461461,81466500,81470324,101197064,81470144,81463816,81464379,81458850,81462419,81462485,81463396,81463099,81459722,81463446,81459290,81459638,81462982,81461500,81461510,81461518,81459109,81462446,81466075,81460828,81465434,81459705,81459708,81459452,81459341,81459185,81453786,81458635,81458351,81458350,81458530,81458953,81461300,81461310,81461320,81461332,81460364,81460158,81460042,81460622,81458778,81467618,81468480,81467745,81467908,81467990,81468173,81463918,81466796,81461024,81461122,81466958,81457178,81458632,81463078,81429883,81466092,81466293,81466310,81459979,81460065,81460319,81460778,81461230,81461340,81461640,81463740,81463825,81472752,81473028,81475661,81475694,81475741,81475768,81479738,81480104,81480121,81458321,81458324,81458327,81458329,81471242,81479491,81471258,81471732,81480730,81476245,81469037,81475087,81476177,81479634,81480713,81480761,81474195,81476173,81472732,81473984,81475176,81474605,81475965,81476068,81480631,81473645,81473650,81474606,81474559,81473913,81472567,81472595,81472629,81472683,81472710,81472761,81473394,81473902,81472433,81474548,81474872,81475293,81475779,81471585,81471731,81475701,81475957,81461206,81472241,81472272,81460359,81471407,81473363,81472350,81473038,81473104,81473141,81471713,81473436,81473460,81467301,81478926,81473007,81475754,81476256,81476415,81478057,81477669,81471393,81471922,81473724,81481050,81481051,81481052,81493086,81493193,81476912,81481745,81494595,81495332,81483390,81484937,81493232,81493199,81493175,81485351,81491086,81491486,81495228,81495163,81480770,81481098,81493767,81495255,81495330,81486714,81481884,81481931,81486552,81489405,81487820,81483510,81483970,81484404,81484610,81485448,81485769,81487262,81487390,81486561,81486113,81482775,81483000,81482308,81482422,81481724,81481758,81481873,81482070,81481896,81481957,81481975,81482063,81481832,81483692,81485015,81485122,81485150,81484426,81483889,81482346,81484246,81483522,81483575,81483186,81483259,81486850,81486956,81500908,81500964,81501978,81500698,81500723,81500745,81500772,81500818,81500858,81504403,81499397,81500279,81502485,81502980,81504932,81499280,81498998,81498673,81498204,81482039,81483084,81496074,81497696,81503515,81498091,81493983,81484436,81505345,81505524,81507459,101252522,81495375,81495377,81495370,81495371,81495374,81483532,81483583,81483620,81483488,81423329,81498965,81499117,81499874,81500213,81499045,81502645,81497192,81497371,81496514,81496108,81496102,81497841,81498512,81498516,81497400,81495803,81495856,81495938,81495999,81496831,81497183,81498328,81498622,81504512,81505766,81496055,81498887,81500708,81500714,81500881,81505442,81505372,81499883,81518628,81516904,81509822,81509832,81516858,81518188,81510001,81508628,81515726,81515762,81515812,81515869,81515909,81515961,81515672,81515377,81515012,81515334,81508514,81498943,81519970,81519976,81520042,81513974,81509931,81510180,81515355,81515397,81515534,81519895,81517860,81517937,81519281,81511741,81511864,81513687,81508996,81508840,81509478,81495395,81508670,81511070,81510662,81508696,81502463,81508472,81508506,81508569,81508668,81510182,81509747,81509821,81509917,81509327,81510190,81510569,81510243,81510283,81508364,81510405,81510110,81517050,81516558,81517447,81516490,81516501,81511549,81512573,81495369,81521817,81526001,81526051,81526189,81526209,81526240,81526258,81526786,81526849,81526892,81526956,81527040,81527125,81527201,81527230,81518066,81531397,81528644,81522800,81528927,81528354,81530256,81529908,81511125,81521101,81521150,101197068,81528269,81526381,81532384,81527980,81530307,81524435,81523357,81523362,81523368,81520793,81521440,81521771,81525778,81526692,81523842,81520865,81521628,101252410,81520502,81520990,81509826,81519361,101194196,81520395,81520397,81520399,81520405,81513596,81513692,81513987,81514141,81513473,81513337,81512979,81521617,81522430,81522350,81522227,81522255,81523067,81522996,81521504,81521220,81521776,81513382,81513653,81521772,81528372,81523145,81523210,81523330,81523499,81523847,81524827,81526116,81526290,81526446,81526750,81527640,81527825,81527984,81528964,81528813,81520163,81520515,81522388,81522469,81522552,81522761,81522960,81505882,81505907,81505934,81505955,81505980,81506054,81507529,81523720,81526787,81523001,81522011,81520442,81522254,81523548,81539129,81539817,81539891,81539960,81540151,81540188,81540227,81540238,81540904,81541880,81539794,81539595,81539562,81535118,81536119,81539449,81539500,81539530,81543797,81542404,81536286,81536367,81536622,81521044,81528632,81534886,81536596,81540378,81535523,81535540,81536122,81544143,81539258,81541739,81544151,81533796,81537397,81537259,81536159,81536240,81536331,81536365,81536522,81536544,81536585,81536613,81536726,81536858,81536929,81536124,81533313,81536230,81536279,81536311,81532263,81533771,81535898,81532949,81537965,81533385,81532852,81532853,81532854,81532855,81532856,81532857,81532858,81534450,81534468,81534474,81534828,81534837,81534370,81534420,81541395,81541467,81536437,81537437,81537903,81537974,81535875,81535528,81535290,81533505,81533632,81533875,81534155,81534471,81534561,81535193,81536450,81533149,81539832,81537826,81538143,81533646,81533782,81533791,81533891,81535146,81535391,81538459,81538335,81544709,81557989,81557962,81557867,81547176,81548916,81554962,81558066,81546914,81557336,81538482,81547253,81552993,81550425,81555341,81558181,81546923,101189987,101173021,81550398,81551837,81551950,81552234,81545126,81545168,81546323,81546775,81551374,81549762,81549819,81545630,81545987,81546086,81546509,81546638,81548483,81549026,81547265,81546932,81547800,81548069,81548101,81547669,81544977,81544980,81544981,81544985,81547749,81547881,81547959,81545887,81545892,81546170,81548269,81548351,81548465,81545904,81546080,81546090,81550934,81550819,81550851,81550857,81550887,81548636,81544842,81549156,81549272,81555899,81547385,81536561,81554137,81546873,81554833,81555368,81546041,81546142,81546451,81546981,81547394,81548976,81549349,81549847,81550402,81551247,81551615,81552046,81552641,81553191,81558975,81570652,81563790,81558290,81558994,81559750,81558364,81572294,81565242,81545257,81546289,81559780,81561791,81561973,81558836,81558841,81560349,81560386,81560419,81560843,81558826,81558823,81558821,81558266,81558814,81563519,81567361,81569566,81570612,81569378,81560005,81560109,81561325,81561543,81561712,81561965,81562097,81562142,81562220,81562581,81562652,81562755,81563362,81563776,81561725,81560823,81561218,81561258,81548112,81548239,81559038,81547025,81547060,81547156,81548425,81561197,81560301,81560800,81561207,81560367,81560333,81560266,81558798,81558799,81560207,81560013,81560356,81560360,81565420,81565457,81568804,81559583,81559990,81560042,81560545,81565810,81565854,81565880,81565915,81565960,81566000,81566078,81566184,81566403,81566412,81566422,81565896,81567921,81546158,81567760,81559030,81559184,81560949,81561060,81561239,81561723,81562519,81564278,81564433,81566340,81559118,81565897,81560240,81562129,81566308,81560758,81576280,81584245,81571329,81578535,81578556,81581960,81581984,81582016,81582051,81582080,81582107,81582140,81582184,81582247,81582287,81582323,81582355,81582393,81582424,81579347,81582102,81578846,81577656,81576260,81575450,81575532,81575707,81563485,81574446,81574701,81574834,81577213,81580486,81561918,81561896,81564457,81577688,81578605,81582505,81574493,81575927,81577604,81575933,81585477,81585550,81585235,81585946,81585803,81575499,81575539,81575719,81575795,81575926,81577452,81577528,81577670,81577769,81578142,81577429,81577621,81577749,81578131,81561893,81577038,81580998,81562108,81580445,81566869,81566745,81561771,81560793,81573914,81573927,81573732,81560966,81561327,81563858,81546627,81546652,81546681,81546726,81556305,81573368,81575679,81546607,81546713,81577202,81573201,81573211,81572416,81572740,81574893,81574909,81574966,81575054,81575125,81575156,81575204,81582461,81579969,81580078,81580776,81580973,81581544,81580676,81573754,81575130,81575981,81576334,81576357,81577175,81577184,81579278,81579805,81573671,81582137,81581808,81595546,81597957,81598151,81594319,81592432,81592461,81592497,81592527,81592553,81592602,81592626,81592651,81592684,81592711,81592736,81592777,81592817,81592870,81595239,81593466,81598802,81595340,81595399,81595420,81595443,81595707,81587278,81587296,81587314,81593711,81587985,81588021,81588919,101252526,81592649,81588853,81591273,81590861,81591380,81591410,81591634,81592067,81591969,81591960,81594627,81579460,81590190,81575681,81587908,81587930,81586620,81586771,81586745,81587615,81587619,81587620,81586921,81587033,81587373,81586278,81586281,81586287,81586277,81577616,81578699,81585679,81585949,81586032,81586042,81586276,81589599,81589885,81587437,81588442,81589224,81580538,81581200,81582738,81580179,81578211,81577406,81573684,81577379,81588595,81588387,81579613,81587742,81587813,81596476,81597132,81596074,81591989,81591998,81589888,81589968,81591187,81593003,81593329,81593509,81593871,81588046,81586894,81586928,81589596,81589763,81589828,81590070,81590187,81590378,81590489,81586787,81586918,81591877,81577484,81570187,81573141,81609870,81602332,81604229,81608806,81606904,81605603,81604549,81604689,81605333,81609533,81610765,81610771,81589516,81589594,81607086,81606636,81606656,81606500,81606460,81606429,81600383,81606279,81606333,101146342,81605764,81605540,81600752,81601126,81602384,81600290,81600132,81600038,81604678,81601282,81603175,81604579,81587509,81585984,81602692,81602813,81602842,81602973,81599999,81602310,81602464,81602185,81602220,81603926,81603915,81600213,81599627,81601112,81599949,81599977,81609034,81602068,81602238,81605510,81606405,81606455,81606507,81600916,81602958];
                $rett = "nr exps : " . count($expeditii)."<br/>";
                foreach ($expeditii as $expeditie) {
                    $rett .= "actualizare : " . $expeditie."<br/>";
                    if($this->ActualizareExpeditieInitiala($expeditie, 0) == false)
                        $rett .= "error expeditie : " . $expeditie."<br/>";
                }
                echo $rett;
                $flag = 1;
            }
            else if($arr[0] == 'facturare' && $arr[1] == 'sstt65migm') {
                //$invoices = array(1267988,1269365,1267911,1269332,1268866,1267807,1268184,1267486,1268480,1269739,1267489,1269759,1267507,1267555,1270167,1268510,1269522,1268292,1267659,1269338,1268681,1268583,1269618,1269546,1267835,1269174,1269933,1270221,1267703,1268235,1270273,1268668,1269900,1269113,1269384,1268637,1268035,1267832,1268238,1268325,1269567,1269037,1268890,1268513,1269063,1268535,1267642,1270046,1267473);
                $invoices = [];
                foreach($invoices as $idfact) {
                    $query = "SELECT expeditie, idfact FROM exp_prelucrate WHERE idfact = {$idfact} and anulata = 0 and tip_exp = 0";
                    $sql = $this->db->QFetchRowArray($query);
                    $rett = "nr exps : " . count($sql)."<br/>";
                    foreach ($sql as $key => $row) {
                        //$rett .= "expeditie : " . $row['expeditie']."<br/>";
                        if($this->ActualizareExpeditieInitiala($row['expeditie'], $row['idfact']) == false)
                            $rett .= "error expeditie : " . $row['expeditie']."<br/>";
                    }
                    echo $idfact ." : ". $rett;
                    flush();
                    ob_flush();
                    sleep(1);
                }
                $flag = 1;
            }

            else if($arr[0] == 'facturare' && $arr[1] == 'sstt65migm') {
                echo $this->sendEmailSend('notificari@info.curierdragonstar.ro', [], [], [], [], [], [], 'marian', 'test', 'debug : ', false);
            }
            */
        }


        if(empty($flag))
            $this->final_result = $this->PageNotFound();

    }


    function recalculareFacturaDupaBorderou($idfact, $inputFileName){

        $factura = $this->getFactura($idfact);
        if(isset($factura['status']) && $factura['status'] >= self::FACTURA_FINALIZATA){
            return json_encode(array('eroare'=>1,'mesaj'=>"FACTURA_FINALIZATA / nu este permisa nici o modificare !"));
        }

        try {
            $inputFileType = IOFactory::identify($inputFileName);
			$reader = IOFactory::createReader($inputFileType);
			$reader->setReadDataOnly(true);
			$spreadsheet = $reader->load($inputFileName);
			$spreadsheet->setActiveSheetIndex(0);
			$worksheet = $spreadsheet->getActiveSheet();

            $rowIterator = $worksheet->getRowIterator();
            $highestRow = $worksheet->getHighestRow();
        } catch(Exception $e) {
            return json_encode(array('eroare'=>1,'mesaj'=>"Eroare incarcare xls ".$e));
        }

        $this->log("recalculareFacturaDupaBorderou: ".$idfact." : " . $inputFileName . " : ". $highestRow." linii");
        $modificari=0;
        $i = 0;
        foreach($rowIterator as $row) {
            $i++;
            try {
                $cellIterator = $row->getCellIterator();
                $cellIterator->setIterateOnlyExistingCells(false); // Loop all cells, even if it is not set
                if (1 == $row->getRowIndex()) continue;//skip first row
                $rowIndex = $row->getRowIndex();
                $error = '';

                $expeditie = $worksheet->getCell("B" . $rowIndex)->getValue();
                if(intval($expeditie) == 0)continue;//skip first row
                $plicuri = $worksheet->getCell("I" . $rowIndex)->getValue();
                $colete = $worksheet->getCell("J" . $rowIndex)->getValue();
                $paleti = $worksheet->getCell("K" . $rowIndex)->getValue();
                $val_gr = $worksheet->getCell("R" . $rowIndex)->getValue();
                $val_exp = $worksheet->getCell("U" . $rowIndex)->getValue();
                $val_totala = $worksheet->getCell("V" . $rowIndex)->getValue();
                if(!strlen($val_totala))
                    continue;
                $exp_data = $this->db->QFetchArray("SELECT plicuri, colete, paleti, val_greutate, valoare_expeditie, valoare_totala_expeditie
                    FROM exp_prelucrate WHERE expeditie = {$expeditie} AND idfact = {$idfact} AND anulata = 0");
                if(!empty($exp_data)){
                    //&& $exp_data['val_greutate'] != $val_gr
                    $this->db->QueryUpdate('exp_prelucrate', array(
                            'updated_at'=>date('Y-m-d H:i:s'),
                            'updated_by'=>$this->user_id,
                            'plicuri'=>$plicuri,
                            'colete'=>$colete,
                            'paleti'=>$paleti,
                            'val_greutate'=>$val_gr,
                            'valoare_expeditie'=>$val_exp,
                            'valoare_totala_expeditie'=> $val_totala
                        ),
                        "expeditie =".$expeditie);
                    $modificari++;
                }

            } catch (Exception $x){
                $mesaj = " Error parsing xls file ".$x;
            }

            //$this->logProgress($i, $highestRow, "recalculareFacturaDupaBorderou expeditii   $expeditie|$plicuri|$colete|$paleti|$val_gr|$val_exp|$val_totala %   $modificari");
        }

        if($modificari > 0 && intval($_POST['recalculare_idfact']) >0){
            $this->log("Selectez expeditii pentru regenerare");
            $query = "SELECT expeditie FROM exp_facturi_expeditii where idfact = {$idfact}";
            $sql = $this->db->QFetchRowArray($query);
            $expeditii = [];
            foreach ($sql as $exp){
                $expeditii[]  = $exp['expeditie'];
            }
            $this->log(count($expeditii). " expeditii selectate pentru regenerare");
            $this->regenerareFacturaProforma(array($idfact), $expeditii);
            $this->log("regenerareFacturaProforma executata");
        }
        return json_encode(array('eroare'=>0,'mesaj'=>$modificari." expeditii modificate"));
    }


    function acc(){

        $mesaj = "";
        $eroare = 0;
        if(isset($_POST['recalculare_idfact'])){
            $idfact = intval($_POST['recalculare_idfact']);
            if($idfact == 0){
                header('Location:'.$this->config['http']."facturare");
            }
            $uploaddir = "/var/www/upload/facturare/";
            $uploadfile = $uploaddir . basename($_FILES['recalculare_borderou_xls']['name']);
            if (!move_uploaded_file($_FILES['recalculare_borderou_xls']['tmp_name'], $uploadfile)) {
                $this->log("Erorare upload fisier !");
                echo "Erorare upload fisier !";
                return;
            }

            echo $this->recalculareFacturaDupaBorderou($idfact, $uploadfile);

        }

        if(isset($_POST['get_progress'])) {
            return $this->getJsonProgress();
        }

        if(isset($_POST['resetare_wme_com'])){
            foreach ($_POST['resetare_wme_com'] as $idfact){
                $this->db->QueryUpdate('exp_facturi', array('wme'=>0,'wme_message'=>''), "id =".intval($idfact));
            }
            return;
        }

        if(isset($_POST['factureaza'])){
            $factura = $this->getFactura($_POST['factureaza']);
            if(isset($factura['status']) && $factura['status'] >= self::FACTURA_FINALIZATA){
                $eroare = 1;
                $mesaj = 'FACTURA FINALIZATA - Nu se modifica !';
            } else {
                $this->factureazaExpeditii($_POST['factureaza']);
            }
        }

        if(isset($_POST['trimite_catre_client'])){
            $this->finalizareFacturi($_POST['trimite_catre_client']);
            if(count($_POST['trimite_catre_client']) == 1){
                foreach ($_POST['trimite_catre_client'] as $idfact){
                    $this->sendInvoice($idfact);
                }
            }
        }

        if(isset($_POST['regenerare']) && $_POST['regenerare'] > 0){
            $factura = $this->getFactura($_POST['regenerare']);
            if(isset($factura['status']) && $factura['status'] >= self::FACTURA_FINALIZATA){
                $eroare = 1;
                $mesaj = 'FACTURA FINALIZATA - Nu se modifica !';
            } else {
                $this->regenerareFacturaProforma($_POST['regenerare'], [], true);
            }

        }

        if(isset($_POST['sterge']) && $_POST['sterge'] > 0){
            $iduri = [];
            foreach ($_POST['sterge'] as $idDeSters){
                $factura = $this->getFactura($idDeSters);
                if(isset($factura['status']) && $factura['status'] >= self::FACTURA_FINALIZATA){
                    $eroare = 1;
                    $mesaj = 'FACTURA FINALIZATA - Nu se modifica !';
                    continue;
                }
                $iduri[] = $idDeSters;
            }
            if(count($iduri))
                $this->stergeFacturi($iduri);
        }


        if(isset($_POST['generare']) && intval($_POST['generare']) > 0){
            $eroare = 1;
            $this->cod_cl_factura = intval($_POST['generare']);
            $this->tip_factura = intval($_POST['task_nou_tip']);

            $grupare = 2;
            if(isset($_POST['master']) && (intval($_POST['master']) == 0 || (intval($_POST['master']) > 0 && intval($_POST['master']) != intval($_POST['generare']))))
                $grupare = 1;

            $data_final = new DateTime();
            $data_final = $data_final->format('d.m.Y');
            if(!empty($_POST['data_final'])) $data_final = $_POST['data_final'];

            if(!empty($_POST['expeditii_task_nou'])){
                $this->expeditii_factura = $this->cleanArray(explode(",",$_POST['expeditii_task_nou']));
            }
            else if($this->tip_factura == self::FACTURA_TIP_STORNO){
                $mesaj = "Introduceti minimum o expeditie pentru Storno !";
                return json_encode(array('eroare'=>$eroare,'mesaj'=>$mesaj));
            }
            else if(empty($this->cod_cl_factura))
                return json_encode(array('eroare'=>$eroare,'mesaj'=>'Client not found'));

            $exp_cond = "";
            if(count($this->expeditii_factura))
                $exp_cond = " AND  expeditie IN (".implode(",",$this->expeditii_factura).")";

            if($this->tip_factura == self::FACTURA_TIP_NORMALA && count($this->expeditii_factura)){

                $sql = $this->db->QFetchArray("SELECT GROUP_CONCAT(expeditie) as expeditie FROM exp_prelucrate WHERE idfact > 0 $exp_cond and anulata = 0");
                if(!empty($sql['expeditie'])){
                    $mesaj = $sql['expeditie']. " Sunt prinse pe factura!"."<br>0 facturi create !";
                    return json_encode(array('eroare'=>$eroare,'mesaj'=>$mesaj));
                }
            }
            //error_log($data_start.':'.$data_final);
            //exit();
            $nr = $this->generareFacturaProforma($this->cod_cl_factura, self::RELEASE_DATE, $data_final, 0, [], 0, 0, [], false, $grupare);
            if(intval($nr) > 0 && $this->tip_factura == self::FACTURA_TIP_STORNO){
                $this->db->QueryUpdate('exp_prelucrate', array('idfact' => 0), "expeditie IN (".implode(",",$this->expeditii_factura).")");
            }


            $mesaj = $nr.' facturi create !';
            return json_encode(array('eroare'=>$eroare,'mesaj'=>$mesaj));

        }

        if(isset($_POST['storneaza'])){
            $this->tip_factura = self::FACTURA_TIP_STORNO;
            if($this->debug)
                $this->log("acc : ".intval($_POST['proforma_activa'])." expeditii : " . implode(',', $_POST['storneaza']), BackEnd::APP_LOG_FILE);
            $nr = $this->stornareExpeditiiFactura(intval($_POST['proforma_activa']), $_POST['storneaza']);

            $eroare = 1;
            if(!$mesaj)
                $mesaj = $nr.' facturi create !';
        }

        if(isset($_POST['scoate'])){

            $factura = $this->getFactura($_POST['proforma_activa']);
            if(isset($factura['status']) && $factura['status'] >= self::FACTURA_FINALIZATA){
                $eroare = 1;
                $mesaj = 'FACTURA FINALIZATA - Nu se modifica !';
            } else {
                $this->scoateExpeditiiDinProforma(intval($_POST['proforma_activa']), $_POST['scoate'], true);
                $exp_array = $this->cleanArray($_POST['scoate']);
                if(count($exp_array)>0){
                    $this->db->Query("UPDATE exp_prelucrate set idfact = 0 WHERE expeditie IN (".implode(',',$exp_array).")");
                }

                $query = "SELECT expeditie FROM exp_facturi_expeditii WHERE idfact=".intval($_POST['proforma_activa']);
                $sql = $this->db->QFetchRowArray($query);
                $fact_exps = [];
                foreach ($sql as $row){
                    $fact_exps[] = $row['expeditie'];
                }

                $this->regenerareFacturaProforma(array(intval($_POST['proforma_activa'])), $fact_exps);
            }

        }

        if(isset($_POST['adauga'])){
            $factura = $this->getFactura($_POST['proforma_activa']);
            if(isset($factura['status']) && $factura['status'] >= self::FACTURA_FINALIZATA){
                $eroare = 1;
                $mesaj = 'FACTURA FINALIZATA - Nu se modifica !';
            } else {
                $this->adaugaExpeditiiInProforma($_POST['proforma_activa'], $_POST['adauga']);
            }

        }

        if(isset($_POST['modificare_data']) && isset($_POST['data_noua'])){

            $facturi = $this->cleanArray($_POST['modificare_data']);
            $data_noua = $this->checkDateFormat($_POST['data_noua']);
            $procTvaNou = $this->getProcentTva($data_noua);

            $eroare = false;
            $mesaj = '';

            foreach ($facturi as $factura_id) {
                $factura = $this->getFactura($factura_id);
                if(empty($factura)){
                    $eroare = $eroare || true;
                    $mesaj .= 'Factura cu id '.$factura_id.' nu exista !<br/>';
                    continue ;
                }
                if($factura['status'] >= self::FACTURA_FINALIZATA){
                    $eroare = $eroare || true;
                    $mesaj .= 'FACTURA FINALIZATA '.$factura['invoice'].' - Nu se modifica !<br/>';
                    continue ;
                }
                if($factura['wme'] > 0 || $factura['trimisa'] > 0){
                    $eroare = $eroare || true;
                    $mesaj .= 'FACTURA TRIMISA in WME '.$factura['invoice'].' - Nu se modifica !<br/>';
                    continue ;
                }
                $procTvaVechi = $this->getProcentTva($factura['trndate']);
                if($procTvaVechi != $procTvaNou){
                    $invoice_data_new = [
                        'operator' => $this->user_id,
                        'trndate'=> $data_noua,
                        'procTva' => $procTvaNou,
                        'tva' => round(($factura['sumamnt'] * $procTvaNou / 100), 2)
                    ];
                    $this->log("Modificare procent TVA de la {$procTvaVechi} la {$procTvaNou} pentru factura {$factura_id}");
                    $this->db->QueryUpdate('exp_facturi', $invoice_data_new, "id =".intval($factura_id));
                    //update linii factura
                    $query = "SELECT * FROM factura_linie WHERE factura_id =".intval($factura_id);
                    $sql = $this->db->QFetchRowArray($query);
                    if (!empty($sql)) {
                        foreach ($sql as $key => $row) {
                            $this->db->QueryUpdate('factura_linie', [
                                    'procTva' => $procTvaNou, 
                                    'tva' => round(($row['valoare'] * $procTvaNou / 100), 2)
                                ], "id =".intval($row['id']));
                        }
                    }
                }
                else {
                    $this->db->Query("UPDATE exp_facturi SET trndate='{$data_noua}' WHERE id =".intval($factura_id));
                }  
            }
        
            if($eroare){
                return json_encode([
                    'eroare' => 1,
                    'mesaj' => $mesaj
                ]);
            }

            return json_encode([
                'eroare' => 0,
                'mesaj' => 'Data facturilor a fost modificată cu succes la '.$data_noua.'.'
            ]);
        }

        if(isset($_POST['genereazaSpecificatii'])){
            $eroare = true;
            if($_POST['lunare'] == 'true') {

                $check = $this->db->QFetchRowArray("SELECT * FROM cron WHERE name = 'FACTURARE_LUNARA' AND status = 0");
                if(empty($check)){
                    $this->db->QueryInsert('cron',
                        array(
                            'name' => 'FACTURARE_LUNARA',
                            'create_date' => date('Y-m-d H:i:s'),
                            'operator' => $this->user_id
                        )
                    );
                    $eroare = false;
                } else {
                    $mesaj = "FACTURARE_LUNARA in asteptare !";
                }

            }

            if($_POST['bilunare'] == 'true') {
                $check = $this->db->QFetchRowArray("SELECT * FROM cron WHERE name = 'FACTURARE_BILUNARA' AND status = 0");
                if(empty($check)) {
                    $this->db->QueryInsert('cron',
                        array(
                            'name' => 'FACTURARE_BILUNARA',
                            'create_date' => date('Y-m-d H:i:s'),
                            'operator' => $this->user_id
                        )
                    );
                    $eroare = false;
                } else {
                    $mesaj = "FACTURARE_BILUNARA in asteptare !";
                }
            }

            if($_POST['saptamanale'] == 'true') {

                $check = $this->db->QFetchRowArray("SELECT * FROM cron WHERE name = 'FACTURARE_SAPTAMANALA' AND status = 0");
                if(empty($check)) {
                    $this->db->QueryInsert('cron',
                        array(
                            'name' => 'FACTURARE_SAPTAMANALA',
                            'create_date' =>  date('Y-m-d H:i:s'),
                            'operator' => $this->user_id
                        )
                    );
                    $eroare = false;
                } else {
                    $mesaj = "FACTURARE_SAPTAMANALA in asteptare !";
                }
            }

            if($eroare && $mesaj == "")
                $mesaj = "Nici un tip de facturare selectat !";
        }
        return json_encode(array('eroare'=>$eroare,'mesaj'=>$mesaj));
    }


    function getFactura($idfact){
        $select = "SELECT * FROM exp_facturi WHERE id = ".intval($idfact);
        $factura = $this->db->QFetchArray($select);
        if(empty($factura)) return [];
        return $factura;
    }

    function JSON_ListePlatitori() {
        $limit = 15;
        $responce = new StdClass();
        $responce->total = 0;
        $responce->rezultat=[];
        if(!empty($_GET['maxRows'])) $limit=$_GET['maxRows'];
        if(empty($_GET['name_startsWith'])) {
            return json_encode($responce);
        }
        $cond = " and c.nume like :name_startsWith";

        if($_REQUEST['puncte_de_lucru'] != 'true'){
            $cond .= " AND ( c.cod_cl = c.master or c.master = 0 ) ";
        }

        if($_REQUEST['clienti_activi'] == 'true'){
            $cond .= " AND c.activ = 1";
        }

        if($_REQUEST['clienti_cash'] == 'true'){
            $cond .= " AND c.tip_plata = 0";
        }

        $query = "SELECT c.nume as platitor_nume, c.cod_cl as platitor_id, c.cod_fiscal, b.cod_lc as localitate_id,
        b.nume_lc as localitate_nume, c.nume_societate, c.email_factura, c.reg_com,
        c.LOCALITATE_SEDIU_SOCIAL, c.ADRESA_SEDIU_SOCIAL,c.master
        FROM clienti c
        LEFT JOIN clienti cm ON cm.cod_cl = c.master
        LEFT JOIN localitati b on c.cod_lc=b.cod_lc
        WHERE c.mod_plata IN (1,2) {$cond} LIMIT {$limit}";
        // error_log($query);
        $sql = $this->db->QFetchRowArray($query, ['name_startsWith'=>strtoupper($this->sanitize($_GET['name_startsWith']))."%"]);

        if (!empty($sql)) {
            $responce->total = count($sql);
            foreach ($sql as $key => $row) {
                $responce->rezultat[$key]['cod_cl'] = $row['platitor_id'];
                $responce->rezultat[$key]['master'] = $row['master'];
                $responce->rezultat[$key]['localitate_id'] = $row['localitate_id'];
                $responce->rezultat[$key]['cod_fiscal'] = $row['cod_fiscal'];
                $responce->rezultat[$key]['nume_societate'] = $row['nume_societate'];
                $responce->rezultat[$key]['reg_com'] = $row['reg_com'];
                $responce->rezultat[$key]['LOCALITATE_SEDIU_SOCIAL'] = $row['LOCALITATE_SEDIU_SOCIAL'];
                $responce->rezultat[$key]['ADRESA_SEDIU_SOCIAL'] = $row['ADRESA_SEDIU_SOCIAL'];
                $responce->rezultat[$key]['email_factura'] = $row['email_factura'];
                $responce->rezultat[$key]['label'] = strtoupper($row['platitor_nume']).' ('.strtoupper($row['localitate_nume']).')';
                $responce->rezultat[$key]['value'] = strtoupper($row['platitor_nume']);
            }
        }
        return json_encode($responce);
    }

    function JSON_CautaExpeditii(){
        $responce = new StdClass();

        if(!isset($_POST['expeditii'])){
            $responce->page = 1;
            $responce->total = 1;
            $responce->records = 0;
            echo json_encode($responce);
            return;
        }
        $cond = "";

        if(isset($_REQUEST['_search'])){
            $searchOn = $this->Strip($_REQUEST['_search']);
            if ($searchOn == 'true') {
                $searchstr = $this->Strip($_REQUEST['filters']);
                $cond .= $this->constructWhere($searchstr);
            }
        }

        $expeditii = parent::ValidareExpeditiiCurata($_POST['expeditii']);

        $sql = [];

        if(!empty($expeditii)){
            $query = "SELECT ep.expeditie, ep.data_expeditie, ep.referire,
                cle.nume as expeditor, cld.nume as destinatar, lce.nume_lc as expeditor_localitate, lcd.nume_lc as destinatar_localitate,
                IF(cle.zona_id > 0 and clec.id > 0, clec.nume, cee.nume) as expeditor_centru, 
                IF(cld.zona_id > 0 and cldc.id > 0, cldc.nume, ced.nume) as destinatar_centru, 
                clp.nume as platitor, ep.platitor_id,
                ep.plicuri, ep.colete, ep.paleti, ep.tip_exp, ep.greutate, ep.km_preluare, ep.km_livrare,
                ep.val_greutate, ep.val_km, ep.val_asig, ep.valoare_expeditie, ep.valoare_totala_expeditie, ep.observatii, ep.operatiune
                FROM exp_prelucrate ep
                left join clienti cle on cle.cod_cl = ep.expeditor_id
                left join clienti cld on cld.cod_cl = ep.destinatar_id
                LEFT JOIN zones clez ON clez.id = cle.zona_id
                LEFT JOIN centre clec on clec.id = clez.centru_id
                LEFT JOIN zones cldz ON cldz.id = cld.zona_id
                LEFT JOIN centre cldc on cldc.id = cldz.centru_id
                left join clienti clp on clp.cod_cl = ep.platitor_id
                left join localitati lce ON lce.cod_lc = cle.cod_lc
                left join localitati lcd ON lcd.cod_lc = cld.cod_lc
                left join centre cee ON cee.id = lce.cod_centru
                left join centre ced ON ced.id = lcd.cod_centru
                WHERE ep.expeditie IN ({$expeditii}) {$cond} and ep.anulata = 0";
            $sql = $this->db->QFetchRowArray($query);
        }

        $records = count($sql);
        if($records){
            foreach ($sql as $i => $row ){
                $responce->rows[$i]['id'] = $row['expeditie'];
                $responce->rows[$i]['cell'] = array(
                    //   $row['platitor'],
                    $row['expeditie'],
                    $row['data_expeditie'],
                    $row['platitor'],
                    $row['expeditor'],
                    $row['expeditor_localitate'],
                    $row['destinatar'],
                    $row['destinatar_localitate'],
                    $row['destinatar_centru'],
                    $row['plicuri'],
                    $row['colete'],
                    $row['paleti'],
                    (ExpeditieDto::TIP_EXP[$row['tip_exp']] ?? "unknown"),
                    $row['greutate'],
                    $row['km_preluare'],
                    $row['km_livrare'],
                    $row['val_greutate'],
                    $row['val_km'],
                    $row['val_asig'],
                    $row['valoare_expeditie'],
                    $row['valoare_totala_expeditie'],
                    $row['observatii'],
                    $row['operatiune'],
                    $row['platitor_id']
                );
            }
        }

        $responce->page = 1;
        $responce->total = 1;
        $responce->records = $records;
        $responce->userdata['factura'] = [];
        echo json_encode($responce);
    }

    function afisareTaskuri(){
        $vars = [];
        $this->vars['site_title'] = 'Facturare';

        $this->vars['status_factura_js_array'] = "['','".implode("','",$this->status_factura)."']";
        $status_factura_jqgrid = '';
        foreach ($this->status_factura as $k=>$sf){
            $status_factura_jqgrid .= ';'.$k.':'.$sf;
        }
        $vars['status_factura_jqgrid'] = $status_factura_jqgrid;
        $vars['URL'] = $this->config['http'];
        return $this->Parse($this->page_prefix . 'taskuri.html', $vars);
    }


    function getDetaliiFiltre(){
        $cond = "";

        if(isset($_REQUEST['_search'])){
            $searchOn = $this->Strip($_REQUEST['_search']);
            if ($searchOn == 'true') {
                $searchstr = $this->Strip($_REQUEST['filters']);
                $cond .= $this->constructWhere($searchstr);
            }
        }

        if(isset($_POST['expeditii_stornare'])){
            $expeditii = $this->cleanArray(explode(",",$_POST['expeditii_stornare']));
            if(count($expeditii)) {
                $cond .= " AND ep.expeditie IN (".implode(',',$expeditii).")";
            }
        }
        return $cond;
    }

    function getExpeditiiNeasociateClientului($idfact, $limit = ""){
        //expeditii de tip retur sau returnate : clientul facturii = platitorul initialei
        $cond = $this->getDetaliiFiltre();
        $query = "select ep.expeditie, ep.data_expeditie, ep.referire,
            cle.nume as expeditor, cld.nume as destinatar, lce.nume_lc as expeditor_localitate, lcd.nume_lc as destinatar_localitate,
            IF(cle.zona_id > 0 and clec.id > 0, clec.nume, cee.nume) as expeditor_centru, 
            IF(cld.zona_id > 0 and cldc.id > 0, cldc.nume, ced.nume) as destinatar_centru,
            clp.nume as platitor, ep.platitor_id,
            ep.plicuri, ep.colete, ep.paleti, ep.tip_exp as tip_expeditie, ep.greutate, ep.km_preluare, ep.km_livrare,
            ep.val_greutate, ep.val_km, ep.val_asig, ep.valoare_expeditie, ep.valoare_totala_expeditie, ep.procTva, ep.observatii, ep.operatiune,
            clpm.cod_cl as master
            FROM exp_prelucrate ep
            left JOIN exp_prelucrate epr on (epr.expeditie = ep.referire and epr.anulata = 0)
            left join clienti cle on cle.cod_cl = ep.expeditor_id
            left join clienti cld on cld.cod_cl = ep.destinatar_id
            LEFT JOIN zones clez ON clez.id = cle.zona_id
            LEFT JOIN centre clec on clec.id = clez.centru_id
            LEFT JOIN zones cldz ON cldz.id = cld.zona_id
            LEFT JOIN centre cldc on cldc.id = cldz.centru_id
            left join clienti clp on clp.cod_cl = ep.platitor_id
            LEFT JOIN clienti clpm ON clpm.cod_cl=clp.master
            left join localitati lce ON lce.cod_lc = cle.cod_lc
            left join localitati lcd ON lcd.cod_lc = cld.cod_lc
            left join centre cee ON cee.id = lce.cod_centru
            left join centre ced ON ced.id = lcd.cod_centru
            LEFT JOIN exp_facturi f ON f.cod_cl = epr.platitor_id
            where
            ep.idfact = 0
            and ep.mod_plata = 0
            and ep.referire > 0
            and ep.anulata = 0
            and ep.data_expeditie >= '".self::RELEASE_DATE." 00:00:00'
            AND f.id={$idfact}
            AND ep.platitor_id <> epr.platitor_id
            and epr.mod_plata > 0
        {$cond} {$limit}";

        // error_log($query);

        $sql = $this->db->QFetchRowArray($query);
        return $sql;
    }

    function getExpeditiiNefacturate($idfact, $limit = ""){
        $cond = $this->getDetaliiFiltre();
        $query = "select ep.expeditie, ep.data_expeditie, ep.referire,
            cle.nume as expeditor, cld.nume as destinatar, lce.nume_lc as expeditor_localitate, lcd.nume_lc as destinatar_localitate,
            IF(cle.zona_id > 0 and clec.id > 0, clec.nume, cee.nume) as expeditor_centru, 
            IF(cld.zona_id > 0 and cldc.id > 0, cldc.nume, ced.nume) as destinatar_centru, 
            clp.nume as platitor, ep.platitor_id,
            ep.plicuri, ep.colete, ep.paleti, ep.tip_exp as tip_expeditie, ep.greutate, ep.km_preluare, ep.km_livrare,
            ep.val_greutate, ep.val_km, ep.val_asig, ep.valoare_expeditie, ep.valoare_totala_expeditie, ep.procTva, ep.observatii, ep.operatiune,
            clpm.cod_cl as master
            FROM exp_prelucrate ep
            left join clienti cle on cle.cod_cl = ep.expeditor_id
            left join clienti cld on cld.cod_cl = ep.destinatar_id
            LEFT JOIN zones clez ON clez.id = cle.zona_id
            LEFT JOIN centre clec on clec.id = clez.centru_id
            LEFT JOIN zones cldz ON cldz.id = cld.zona_id
            LEFT JOIN centre cldc on cldc.id = cldz.centru_id
            left join clienti clp on clp.cod_cl = ep.platitor_id
            LEFT JOIN clienti clpm ON clpm.cod_cl=clp.master
            left join localitati lce ON lce.cod_lc = cle.cod_lc
            left join localitati lcd ON lcd.cod_lc = cld.cod_lc
            left join centre cee ON cee.id = lce.cod_centru
            left join centre ced ON ced.id = lcd.cod_centru
            LEFT JOIN exp_facturi f ON f.cod_cl = ep.platitor_id
            WHERE f.id = {$idfact} AND ep.idfact = 0 AND ep.anulata = 0 AND ep.data_operatie >= '".self::RELEASE_DATE." 00:00:00' {$cond} {$limit}";
        // error_log($query);
        $sql = $this->db->QFetchRowArray($query);
        return $sql;
    }

    function getExpeditiiBorderouFacturare($idfact, $limit = ""){
        $cond = $this->getDetaliiFiltre();
        $query = "SELECT fe.* , ep.observatii, ep.detalii_doc, ep.operatiune , clp.nume as platitor, ep.procTva,
        IF(ep.tip_exp > 0 , ep.referire,'') as referire,
        IF(ep.tip_exp = 3, ep.valoare_asigurata,'') as ramburs,
        lce.cod_jd as expeditor_judet, lcd.cod_jd as destinatar_judet
        FROM exp_facturi_expeditii fe
        LEFT JOIN exp_prelucrate ep ON (ep.expeditie = fe.expeditie and ep.anulata = 0)
        left join clienti cle on cle.cod_cl = ep.expeditor_id
        left join clienti cld on cld.cod_cl = ep.destinatar_id
        left join clienti clp on clp.cod_cl = ep.platitor_id
        LEFT JOIN clienti clpm ON clpm.cod_cl=clp.master
        left join localitati lce ON lce.cod_lc = cle.cod_lc
        left join localitati lcd ON lcd.cod_lc = cld.cod_lc
        LEFT JOIN exp_facturi f ON f.id = ep.idfact
        WHERE fe.idfact=".$idfact." {$cond}
        ORDER BY fe.data_expeditie ASC
        {$limit}
        ";

        $sql = $this->db->QFetchRowArray($query);
        return $sql;
    }


    function stornareExpeditiiFactura($idfact, $expeditii){
        $stornare_totala = false;
        if(isset($_POST['storno_total']) && $_POST['storno_total']  == 'true')
            $stornare_totala = true;

        //error_log($idfact . ":" . print_r($expeditii, true));

        if(empty($expeditii) && $stornare_totala === false)
            return 0;

        $query = "SELECT * FROM exp_facturi WHERE id = {$idfact}";
        $factura= $this->db->QFetchArray($query);

        if($stornare_totala === true){
            $expeditii = [];
            $query = "SELECT expeditie FROM exp_facturi_expeditii WHERE idfact = {$idfact}";
            $exp_fact = $this->db->QFetchRowArray($query);
            if(empty($exp_fact)) return 0;
            foreach ($exp_fact as $k => $exp){
                $expeditii[] = $exp['expeditie'];
            }
        }
        if($this->debug)
            $this->log("stornareExpeditiiFactura : {$factura['cod_cl']} : {$factura['id']} : " . implode(',', $expeditii), BackEnd::APP_LOG_FILE);
        return $this->generareFacturaProforma($factura['cod_cl'], '', '', 0, $expeditii, $factura['id']);

    }

    function scoateExpeditiiDinProforma($idfact, $expeditii, $sterge = false){
        if(empty($expeditii))
            return;

        $expeditii = $this->cleanArray($expeditii);
        // error_log(json_encode($expeditii));
        $tipStorno = false;
        $factura = $this->db->QFetchArray("SELECT * FROM exp_facturi WHERE id = ". intval($idfact));
        if($factura['tip_factura'] == self::FACTURA_TIP_STORNO){
            $tipStorno = true;
        }

        $query = "SELECT cod_expeditie, expeditie, idfact FROM exp_prelucrate WHERE expeditie IN (" .implode(',',$expeditii).") and anulata = 0";
        $sql= $this->db->QFetchRowArray($query);
        foreach ($sql as $row){
            // $this->insertIstExp($row['cod_expeditie'], self::EXPEDITIE_SCOASA_DIN_FACTURARE);
            if($sterge){
                $idfact_nou = 0;
                if($tipStorno){
                    $factura_prev = $this->db->QFetchArray("SELECT * FROM exp_facturi_expeditii WHERE idfact <> {$idfact} AND expeditie = ". $row['expeditie']. " ORDER BY idfact DESC limit 1" );
                    if(!empty($factura_prev))
                        $idfact_nou = intval($factura_prev['idfact']);
                }
                $this->db->Query("DELETE FROM exp_facturi_expeditii WHERE idfact={$idfact} AND expeditie=".$row['expeditie']);
                $this->db->QueryUpdate('exp_prelucrate', array('idfact'=>$idfact_nou), "expeditie =".$row['expeditie']);
            }
        }
    }

    function cleanArray($expeditii){
        $new_exp = [];
        if( !is_array($expeditii) || count($expeditii) == 0){
            return [];
        }
        foreach ($expeditii as $exp){
            if(intval($exp)>0)
                $new_exp[] = intval($exp);
        }
        return $new_exp;
    }

    function adaugaExpeditiiInProforma($idfact, $expeditii)
    {
        $this->regenerareFacturaProforma(array(intval($idfact)), $expeditii);
    }

    function JSON_DetaliiTask($idfact, $nefacturate = false, $tip = ''){

        $responce = new StdClass();
        $total_pages = 0;
        $records = 0;

        $page = intval($_REQUEST['page'] ?? 0);
		$limit = intval($_REQUEST['rows'] ?? 250);

        $idfact = intval($idfact);
        if($idfact > 0){

            if($nefacturate === true){
                $expeditii = $this->getExpeditiiNefacturate($idfact);
            } else if($nefacturate == 2){
                $expeditii = $this->getExpeditiiNeasociateClientului($idfact);
            } else {
                $expeditii = $this->getExpeditiiBorderouFacturare($idfact);
            }

            $records = $count = count($expeditii);

            if( $count >0 ) {$total_pages = ceil($count/$limit); }
            else { $total_pages = 0; }
            if ($page > $total_pages) $page=$total_pages;
            if ($limit<0) $limit = 0;
            $start = $limit*$page - $limit; // do not put $limit*($page - 1)
            if ($start<0) $start = 0;


            if($nefacturate === true){
                $expeditii = $this->getExpeditiiNefacturate($idfact, " LIMIT $start,$limit");
            } else if($nefacturate == 2){
                $expeditii = $this->getExpeditiiNeasociateClientului($idfact, " LIMIT $start,$limit");
            } else {
                $expeditii = $this->getExpeditiiBorderouFacturare($idfact, " LIMIT $start,$limit");
            }

            if($records){

                foreach ($expeditii as $i => $row ){
                    $responce->rows[$i]['id'] = $row['expeditie'];
                    $responce->rows[$i]['cell'] = array(
                        $row['expeditie'],
                        $row['data_expeditie'],
                        $row['platitor'],
                        $row['expeditor'],
                        $row['expeditor_localitate'],
                        $row['destinatar'],
                        $row['destinatar_localitate'],
                        $row['destinatar_centru'],
                        $row['plicuri'],
                        $row['colete'],
                        $row['paleti'],
                        (ExpeditieDto::TIP_EXP[$row['tip_expeditie']] ?? $row['tip_expeditie']),
                        $row['greutate'],
                        $row['km_preluare'],
                        $row['km_livrare'],
                        $row['val_greutate'],
                        $row['val_km'],
                        $row['val_asig'],
                        $row['valoare_expeditie'],
                        $row['valoare_totala_expeditie'],
                        $row['procTva'],
                        $row['observatii'],
                        $row['operatiune'],
                        $row['master']
                    );
                }
            }
        }
        $responce->page = $page;
        $responce->total = $total_pages;
        $responce->records = $records;

        $factura = $this->db->QFetchArray("SELECT * FROM exp_facturi WHERE id = ".intval($idfact));

        $responce->userdata['request_type'] = $tip;
        $responce->userdata['factura'] = $factura;
        echo json_encode($responce);
    }


    function factureazaExpeditii($proformeIds){

        $this->log("Facturare proforme:".count($proformeIds));
        $total = count($proformeIds);
        $fnr = 0;
        foreach ($proformeIds as $id){
            $fnr++;
            $proc = number_format(round((($fnr/$total) * 100) , 2), 2);
            $this->log("Facturare:".$proc."%  $fnr / $total");
            $query = "SELECT f.* , c.nume, c.tip_facturare, c.fara_factura
                FROM exp_facturi f
                LEFT JOIN clienti c ON c.cod_cl = (
                    SELECT IF(master > 0, master, cod_cl) AS master
                    FROM clienti where cod_cl = f.cod_cl ORDER BY cod_fiscal DESC , MOD_PLATA DESC LIMIT 1)
                WHERE f.id={$id} ORDER BY sumamnt ASC";
            $row= $this->db->QFetchArray($query);
            //error_log($query);
            if(!empty($row)){
                if(intval($row['status']) == 1){
                    $serie = "";
                    switch ($row['tip_facturare']){
                        case self::FACTURARE_LUNARA:
                            $serie = 'DSCL';
                            break;
                        case self::FACTURARE_BILUNARA:
                            $serie = 'DSCB';
                            break;
                        case self::FACTURARE_SAPTAMANALA:
                            $serie = 'DSCS';
                            break;
                        case self::FACTURARE_MANUALA:
                            $serie = 'DSCM';
                            break;
                        default:
                            break;
                    }

                    if($row['tip_factura'] == 2){
                        $serie = 'DSCST';
                    }

                    if($row['fara_factura'] == 1){
                        if($row['tip_factura'] == 2){
                            $serie = 'DSCUST';
                        } else {
                            $serie = 'DSCU';
                        }
                    }

                    // error_log($row['status'] . " ".$row['tip_facturare']);
                    if($serie){
                        // error_log("Facturare proforma cu seria noua:".$serie);
                        $serieFactura = $this->getNumarFactura($serie);
                        $vv=[];
                        $vv['invoice'] = $serieFactura;
                        $vv['status'] = self::FACTURA_FACTURATA;
                        // $vv['tip_factura'] = self::FACTURA_TIP_NORMALA;
                        $this->db->QueryUpdate('exp_facturi', $vv, "id=".$id);

                    } else {
                        // error_log("Factura ID:{$id} fara serie tip_facturare:".$row['tip_facturare']);
                    }

                } else {
                    $this->log("Factura cu status".$id);
                }
            } else {
                $this->log("Factura ID:{$id} inexistenta");
            }
        }
    }

    function sendToCron(){

    }

    function finalizareFacturi($facturiId){
        if(!is_array($facturiId))
            return;
        foreach ($facturiId as $facturaId) {
            $facturaId = intval($facturaId);
            $this->db->QueryUpdate('exp_facturi', ['status' => self::FACTURA_FINALIZATA], " id = {$facturaId} AND status =".self::FACTURA_FACTURATA);
        }
    }

    function trimiteFacturiNetrimise($logFilePath = false){
        $trimise = 0;
        $query = "SELECT f.*
            FROM exp_facturi f
            LEFT JOIN clienti c ON c.cod_cl  = f.cod_cl
            WHERE f.status >= ".self::FACTURA_FINALIZATA." AND f.trimisa = 0 AND
            c.fara_factura = 0 AND c.email_factura like '%@%'
            ORDER BY f.id LIMIT 250";
        $facturi= $this->db->QFetchRowArray($query);
        if(empty($facturi) || !is_array($facturi) || count($facturi) == 0) return;
        $total = count($facturi);
        $this->log("{$total} nr. facturi after query", $logFilePath);
        foreach ($facturi as $factura){
            $trimise++;
            $proc = number_format(round((($trimise / $total) * 100) , 2), 2);
            if($factura['id'] > 0){
                $this->log("Trimit ".$factura['id']." ".$proc . "%  ---- {$trimise}/{$total}", $logFilePath);
                if($this->sendInvoice($factura['id'], $logFilePath)){
                    $this->log($factura['id']." trimisa", $logFilePath);
                }
                else {
                    $this->log($factura['id']." send failed", $logFilePath);
                }
            }
            sleep(0.25);
        }
        return $trimise;
    }

    function stergeFacturi($facturiId, $isCron = false, $limit_ids = [], $logFilePath = false){
        $facturiId = $this->cleanArray($facturiId);
        if(!$isCron){
            if(!is_array($facturiId))
                return;
            $this->db->Query("UPDATE exp_facturi SET status = -1 WHERE id IN (".implode(',',$facturiId).") AND trimisa = 0 AND wme = 0");
            if(count($facturiId) <= 2){
                return $this->stergeFacturi($facturiId, true, $facturiId, $logFilePath);
            }
            return;
        }
        else {
            $limit_ids = $this->cleanArray($limit_ids);
            $cod = "";
            if(count($limit_ids))
                $cod .= " AND id IN (".implode(',',$facturiId).")";

            $query = "SELECT * FROM exp_facturi WHERE status = -1 {$cod}";
            $facturi = $this->db->QFetchRowArray($query);
            if(empty($facturi))
                return 0;
        }

        $total_fact = count($facturi);
        $i = 0;
        foreach ($facturi as $sql){
            $i++;
            $facturaId = intval($sql['id']);
            // $sql= $this->db->QFetchRowArray($query);
            $status = intval($sql['status'] ?? 0 );

            if($facturaId == 0 || $status >= 4)
                continue;

            $factura  = $this->getFactura($facturaId);
            $expeditii = [];
            if(isset($factura['status']) && $factura['tip_factura'] == 2){
                $expeditii = $this->db->QFetchRowArray("SELECT efe.idfact, efe.expeditie
                    FROM exp_facturi_expeditii efe
                    INNER JOIN exp_facturi ef ON ef.id = efe.idfact
                    WHERE
                    ef.tip_factura = 1 AND
                    efe.expeditie  IN (SELECT * FROM  (SELECT expeditie FROM exp_facturi_expeditii WHERE idfact = {$facturaId} ) AS subquery)
                    GROUP BY efe.idfact, efe.expeditie ORDER BY efe.idfact DESC");
            }

            $this->log("DELETE FACTURA : {$facturaId}", $logFilePath);
            $this->db->Query("DELETE FROM exp_facturi WHERE id={$facturaId}");
            $this->db->Query("DELETE FROM factura_linie WHERE factura_id={$facturaId}");
            $this->db->Query("DELETE FROM exp_facturi_expeditii WHERE idfact={$facturaId}");
            $this->db->Query("UPDATE exp_prelucrate SET idfact = 0 WHERE idfact={$facturaId}");
            $proc = number_format(round((($i / $total_fact) * 100) , 2), 2);
            if(!empty($expeditii) && is_array($expeditii)){
                foreach ($expeditii as $exp){
                    $this->db->Query("UPDATE exp_prelucrate SET idfact = {$exp['idfact']} WHERE expeditie ={$exp['expeditie']}");
                }
            }

            $this->log("DELETE ".$proc . "%  ---- {$i}/{$total_fact}", $logFilePath);

        }
        return count($facturi);
    }



    function JSON_Taskuri($tab_nr, $tab){
        $cond = "AND f.status > 0 ";

        if(isset($_REQUEST['_search'])){
            $searchOn = $this->Strip($_REQUEST['_search']);
            if ($searchOn == 'true' && isset($_REQUEST['filters'])) {
                $searchstr = $this->Strip($_REQUEST['filters']);
                $cond .= $this->constructWhere($searchstr);
            }
        }

        $this->cautare = false;
        $sflag = false;

        if($tab_nr == 1 || $tab_nr == 2)
            $sflag = true;

        if(isset($_POST['facturi_expeditii']) || $tab_nr == 'cautare'){

            $sflag = false;
            if(!empty($_POST['client'])){
                $client_id = intval($_POST['client'] ?? 0);
                $master_id = intval($_POST['master'] ?? 0);

                if($master_id > 0 && $master_id == $client_id){
                    $cond .= " AND f.cod_cl in (select cod_cl from clienti where master = {$master_id})";
                    $sflag = true;
                }
                else if($client_id > 0){
                    $cond .= " AND f.cod_cl = {$client_id}";
                    $sflag = true;
                }
            }

            if(!empty($_POST['facturi_expeditii'])) {
                $facturi_expeditii = explode(",", $_POST['facturi_expeditii']);
                $cauta_array = [];
                $cauta_array_int = [];
                if(is_array($facturi_expeditii) && count($facturi_expeditii)){
                    foreach ($facturi_expeditii as $factura_expeditie){
                        $factura_expeditie = trim($factura_expeditie);
                        if(strlen($factura_expeditie) == 0) continue;
                        //este expeditie ?
                        if(intval($factura_expeditie) > 0 && (ExpeditieDto::isAwb($factura_expeditie) || ExpeditieDto::isCmnAwb($factura_expeditie))){
                            $cauta_array_int[] = $factura_expeditie;
                        }
                        //factura
                        else {
                            $cauta_array[] = $factura_expeditie;
                        }
                    }
                }

                if(count($cauta_array_int) || count($cauta_array)){

                    if(count($cauta_array_int)){
                        $in_cond_init = implode(',',array_unique($cauta_array_int));
                        $query = "SELECT idfact FROM exp_prelucrate WHERE expeditie IN ({$in_cond_init}) and anulata = 0 GROUP BY idfact";
                        $sql = $this->db->QFetchRowArray($query);
                        if(!empty($sql)){
                            foreach ($sql as $idfact){
                                $cauta_array[] = $idfact['idfact'];
                            }
                        }
                    }

                    if(count($cauta_array)){
                        $in_cond = "'".implode("','",array_unique($cauta_array))."'";
                        $cond .= " AND f.id IN ({$in_cond}) OR f.invoice IN ({$in_cond})";
                    }

                    $sflag = true;
                }
            }

            if(isset($_REQUEST['data_start']) && isset($_REQUEST['data_final']) && strlen($_REQUEST['data_start']) > 0){
                $data_start = $this->TransformDate($_REQUEST['data_start']);
                $data_final = $this->TransformDate($_REQUEST['data_final']);
                $cond .= " AND f.trndate>='".$data_start."' AND f.trndate<='".$data_final."'";
                $sflag = true;
            }

            $this->cautare = true;
        }
        else if ($tab_nr == 'storno'){
            $sflag = false;

            if(!empty($_POST['client'])){
                $client_id = intval($_POST['client'] ?? 0);
                $master_id = intval($_POST['master'] ?? 0);

                if($master_id > 0 && $master_id == $client_id){
                    $cond .= " AND f.status > ".self::FACTURA_FACTURATA." AND f.tip_factura <> 2 AND f.cod_cl in (select cod_cl from clienti where master = {$master_id})";
                    $sflag = true;
                }
                else if($client_id > 0){
                    $cond .= " AND f.cod_cl = {$client_id} AND f.status > ".self::FACTURA_FACTURATA." AND f.tip_factura <> 2";
                    $sflag = true;
                }
            }

            if(!empty($_POST['expeditie_stornare'])){
                $expeditie_stornare = intval($_POST['expeditie_stornare']);
                if($expeditie_stornare > 0){

                    $query = "SELECT  group_concat(idfact) as idfacts FROM exp_facturi_expeditii WHERE expeditie = {$expeditie_stornare}";
                    $sql = $this->db->QFetchArray($query);
                    if(!empty($sql)){
                        if(strlen($sql['idfacts'])){
                            $cond .= "and f.tip_factura = ".self::FACTURA_TIP_NORMALA." and f.id IN (".$sql['idfacts'].")";
                            $sflag = true;
                        }

                    }
                }
            }

            if($sflag !== true){
                $cond = ' AND 1=2';
            }
        }

        if($sflag !== true){
            $cond = ' AND 1=2';
        }

        $sort_cond ="";
        if(isset($_REQUEST['sidx']) && strlen($_REQUEST['sidx']) > 0)  {
            $sidx = $_REQUEST['sidx']; // get index row - i.e. user click to sort
            $sord = $_REQUEST['sord']; // get the direction
            $sort_cond =  " ORDER BY " . $sidx . " " . $sord;
        }
        else $sort_cond =  " ORDER BY data_adaugare desc ";

        $output_array = [];
        preg_match("/AND  nr_exp_cu_referire  = '(.*)'/", $cond, $output_array);
        $having = '';
        if(!empty($output_array[0])){
            $cond = str_replace($output_array[0],'',$cond);
            if($output_array[1] > 0){
                $having = " HAVING nr_exp_cu_referire >= '{$output_array[1]}'";
            } else {
                $having = " HAVING nr_exp_cu_referire = '{$output_array[1]}'";
            }

        }

        $output_array = [];
        preg_match("/AND  corectii  = '(.*)'/", $cond, $output_array);
        if(!empty($output_array[0])){
            $cond = str_replace($output_array[0],'',$cond);
            if(isset($output_array[1])) {
                $output_array[1] = intval($output_array[1]);
                if($output_array[1] > 0){
                    $having = " HAVING corectii > 1";
                } else {
                    $having = " HAVING corectii = 1";
                }
            }
        }

        $page = intval($_REQUEST['page'] ?? 0);
		$limit = intval($_REQUEST['rows'] ?? 250);

        $query_m = "select f.id, count(fl.id) as corectii
            FROM exp_facturi f
            left join exp_facturi s on s.factura_initiala = f.id
            LEFT JOIN clienti c ON c.cod_cl = f.cod_cl
            inner JOIN factura_linie fl ON fl.factura_id = f.id
            WHERE c.tarif_manual = 1 AND f.status IN (".self::FACTURA_PREGATITA . ", ".self::FACTURA_FACTURATA . ") {$cond}  GROUP by f.id {$having}";

        $query_n = "select f.id, count(fl.id) as corectii
            FROM exp_facturi f
            left join exp_facturi s on s.factura_initiala = f.id
            LEFT JOIN clienti c ON c.cod_cl = f.cod_cl
            inner JOIN factura_linie fl ON fl.factura_id = f.id
            WHERE c.tarif_manual = 0 AND f.status IN (".self::FACTURA_PREGATITA . ", ".self::FACTURA_FACTURATA . ") {$cond}  GROUP by f.id {$having}";

        $tarif_negociat = 0;
        $tarif_manual = 0;

        $sql_m = $this->db->QFetchRowArray($query_m);
        if(!empty($sql_m))
            $tarif_manual = count($sql_m);
        $sql_n = $this->db->QFetchRowArray($query_n);
        if(!empty($sql_n))
            $tarif_negociat = count($sql_n);

        $count = $tarif_manual + $tarif_negociat;
        $responce = new StdClass();
        $condition = "";
        if($tab_nr == 2){
            $condition .= " AND c.tarif_manual = 1 AND f.status IN (".self::FACTURA_PREGATITA . ", ".self::FACTURA_FACTURATA . ")";
            $count = $tarif_manual;
        } else if($tab_nr == 1){
            $condition .= "AND c.tarif_manual = 0 AND f.status IN (".self::FACTURA_PREGATITA . ", ".self::FACTURA_FACTURATA . ")";
            $count = $tarif_negociat;
        } else {

        }

        if( $count >0 ) {$total_pages = ceil($count/$limit); }
        else { $total_pages = 0; }
        if ($page > $total_pages) $page=$total_pages;
        if ($limit<0) $limit = 0;
        $start = $limit*$page - $limit; // do not put $limit*($page - 1)
        if ($start<0) $start = 0;

        //error_log($tab_nr . " : ".$condition);

        $query = "SELECT
            0 as nr_exp_cu_referire,
            f.* , c.nume, c.tip_facturare, c.TIP_PLATA, c.cod_fiscal, IF(length(c.nume_societate) > 0, c.nume_societate,c.nume) as nume_societate, 
            c.email_factura, c.MOD_PLATA, c.tip_tva, c.facturare_tip_tranzactie,
            group_concat(fl.procTva) as procTva, count(fl.id) as corectii, sum(fl.valoare) as fl_suma, s.stornari
            FROM exp_facturi f
            LEFT JOIN (select st.factura_initiala, group_concat(st.invoice) as stornari 
                from exp_facturi st where st.factura_initiala > 0 group by st.factura_initiala) as s on s.factura_initiala = f.id
            LEFT JOIN clienti c ON c.cod_cl = f.cod_cl
            INNER JOIN factura_linie fl ON fl.factura_id = f.id
            WHERE 1 {$condition} {$cond}  GROUP by f.id  {$having}  {$sort_cond} LIMIT $start, $limit ";
        $sql = $this->db->QFetchRowArray($query);

        //error_log($query);
        if(!empty($sql)){
            foreach ($sql as $i => $row ){
                $responce->rows[$i]['id'] = $row['id'];
                $responce->rows[$i]['cell'] = array(
                    $row['nume_societate'],
                    $row['trndate'],
                    $row['invoice'], // ($row['status'] > self::FACTURA_PREGATITA)?$row['invoice']:'',
                    $row['stornari'],
                    (int)$row['tip_facturare'],
                    (int)$row['TIP_PLATA'],
                    (int)$row['tip_factura'],
                    $row['mod_generare'],
                    $row['fl_suma'],
                    $row['procTva'],
                    $row['corectii'] - 1,
                    $row['nr_exp'],
                    $row['nr_exp_cu_referire'],
                    $row['status'],
                    $row['trimisa'],
                    $row['cod_fiscal'],
                    trim($row['nume']),
                    $row['cod_cl'],
                    $row['email_factura'],
                    $row['MOD_PLATA'],
                    $row['tip_tva'],
                    $row['wme'],
                    $row['wme_message']
                );
            }
        }

        $responce->page = $page;
        $responce->total = $total_pages;
        $responce->records =  $count;
        $responce->userdata['tarif_negociat']   = (int) $tarif_negociat;
        $responce->userdata['tarif_manual']     = (int) $tarif_manual;
        echo json_encode($responce);
    }

    function getNumarFactura($serie, $width = 7 ){
        $query = "UPDATE facturi_increments SET increment = increment + 1 WHERE  serie = :serie";
        $this->db->Query($query, ['serie'=>$serie]);
        $query = "SELECT * FROM facturi_increments WHERE serie = :serie";
        $result = $this->db->QFetchArray($query, ['serie'=>$serie]);
        $padded = str_pad((string)$result['increment'], $width, "0", STR_PAD_LEFT);
        return $serie."-".$padded;
    }


    function getClient($clientId){
        $cond = "";
        if($clientId != null){
            $cond = " AND cod_cl = {$clientId}";
        }
        $query = "SELECT cod_cl, nume, master, GROUP_CONCAT( cod_cl ) AS coduri, count( cod_cl ) AS nr, tip_facturare, valoare_maxima_factura, tip_tva, facturare_tip_tranzactie
            FROM clienti
            WHERE mod_plata
            IN ( 1, 2 ) {$cond}
            GROUP BY master, facturare_separata";

        $sql_one = $this->db->QFetchArray($query);
        if($sql_one['master'] != $sql_one['cod_cl'] && $sql_one['master'] > 0){

            $query = "SELECT
                IF(cod_cl != master, master, cod_cl) as cod_cl,
                IF(cod_cl != c.master, (SELECT c.nume FROM clienti WHERE cod_cl=c.master LIMIT 1), nume) AS nume,
                master, GROUP_CONCAT( cod_cl ) AS coduri, count( cod_cl ) AS nr, tip_facturare, valoare_maxima_factura , tip_tva, facturare_tip_tranzactie
                FROM clienti c
                WHERE mod_plata
                IN ( 1, 2 )  AND master = {$sql_one['master']}
                GROUP BY master, facturare_separata";

        // $sql = $this->db->QFetchRowArray($query);
        }
        //print_r($sql);
        //exit();
        $sql = $this->db->QFetchRowArray($query);
        if (!empty($sql)) {
            return $sql;
        }
        return [];
    }

    function regenerareFacturaProforma($facturiId , $expeditii_noi = [], $reseteaza = false){
        $expeditii_noi = $this->cleanArray($expeditii_noi);
        // error_log(json_encode($expeditii_noi));
        foreach ($facturiId as $facturaId){
            $facturaId = intval($facturaId);
            $query = "SELECT cod_cl, interval_start, interval_stop FROM exp_facturi WHERE id = {$facturaId}";
            $factura = $this->db->QFetchArray($query);
            $query = "SELECT expeditie FROM exp_facturi_expeditii WHERE idfact=".$facturaId;
            $expeditii = $this->db->QFetchRowArray($query);

            $nturi = [];
            if(!empty($expeditii)){
                foreach ($expeditii as $expeditie){
                    $nturi[] = $expeditie['expeditie'];
                }
            }
            $this->db->Query("DELETE FROM exp_facturi_expeditii WHERE idfact = {$facturaId}");
            $this->db->QueryUpdate('exp_prelucrate', ['idfact' => 0], "idfact = {$facturaId}");

            if(count($expeditii_noi)>0){
                foreach ($nturi as $exp){
                    $expeditii_noi[] = $exp;
                }
            }

            $this->generareFacturaProforma($factura['cod_cl'], self::RELEASE_DATE, $factura['interval_stop'], intval($facturaId), [], 0, 0, $expeditii_noi, $reseteaza);

        }

    }

    function checkDateFormat($data){
        $data_arr = explode('.',$data);
        if(count($data_arr) == 3){
            return $data_arr[2]."-".$data_arr[1]."-".$data_arr[0];
        }
        return $data;
    }

    function imparteFactura($grupare, $clientId, $valmax, $data_final, $exclude_cod_cl = [], $tip_facturare, $logFilePath = false){
        $data_start = self::RELEASE_DATE;
        $filtru_id = " ";
        if($grupare == 1){
            $filtru_id = " clp.cod_cl = ".$clientId;
        }
        else {
            //exclude punctele de lucru care au facturare individuala
            $filtru_id = " clpm.cod_cl = ".$clientId;
            if(count($exclude_cod_cl) > 0)
                $filtru_id .= " AND ep.platitor_id not in (".implode(",",$exclude_cod_cl).")";
        }

        $cond_ex = "";
       if(count($this->expeditii_factura)){
            $expeditii_noi = $this->cleanArray($this->expeditii_factura);
            $cond_ex = " AND ep.expeditie IN (".implode(',',$expeditii_noi).")";
       }
       $select = "SELECT ep.expeditie, ep.valoare_totala_expeditie, ep.data_expeditie
            FROM exp_prelucrate ep
            LEFT JOIN clienti clp ON clp.cod_cl = ep.platitor_id
            LEFT JOIN clienti clpm ON clpm.cod_cl = clp.master
            WHERE
            ep.data_expeditie >= '{$data_start}' AND ep.data_expeditie <= '{$data_final}'
            AND {$filtru_id} and ep.idfact = 0 and ep.anulata = 0 {$cond_ex}
            order by ep.data_expeditie";

        $query = $this->db->QFetchRowArray($select);
        if(empty($query)) {
            $this->log("Eroare imparte factura la client = ".$clientId. ", grupare = ".$grupare, $logFilePath);
            return 0;
        }

        $exp_fact = [];
        $perioade_facturate = [];
        $fact_nr = 1;
        $perioade_facturate[$fact_nr] = [];
        $exp_fact[$fact_nr] = [];
        $total_fact = 0;
        foreach ($query as $row){
            if(ceil(($total_fact+$row['valoare_totala_expeditie'])/$valmax) > 1){
                $fact_nr++;
                $total_fact = 0;
                $perioade_facturate[$fact_nr] = [];
                $exp_fact[$fact_nr] = [];
            }

            $perioade_facturate[$fact_nr][$row['data_expeditie']] = 1;
            $exp_fact[$fact_nr][] = $row['expeditie'];
            $total_fact += $row['valoare_totala_expeditie'];
        }
        $this->expeditii_factura = [];
        foreach ($exp_fact as $fact_nr => $fact){
            $date_range = array_keys($perioade_facturate[$fact_nr]);
            $date_start = $date_range[0];
            $date_stop = $date_range[count($date_range)-1];
            $this->log("Generez $fact_nr: $clientId $date_start $date_stop $grupare", $logFilePath);
            $this->generareFacturaProforma($clientId, $date_start, $date_stop, 0, [] , 0,  $tip_facturare, $fact, false, $grupare, true);
        }
    }


    function generareFacturaProforma($clientId, $data_start = "", $data_final = "", $update = 0, $expeditii = [], $factura_initiala = 0, $tip_facturare = 0, $expeditii_noi = [], $reseteaza = false, $grupare = 0, $from_imparteFactura = false, $logFilePath = false){
        ini_set('memory_limit', '2048M');

        if(count($this->expeditii_factura))
            $expeditii_noi = $this->expeditii_factura;

        $expeditii_noi = $this->cleanArray($expeditii_noi);

        //error_log($data_start.':'.$data_final);

        $data_start = $this->checkDateFormat($data_start);
        $data_final = $this->checkDateFormat($data_final);

        //error_log($data_start.':'.$data_final);
        $cond = "";
        $cond_having="";
        if($clientId > 0 && $this->tip_factura != self::FACTURA_TIP_STORNO){
            if($grupare == 1)
                { $cond_having=" HAVING clp.cod_cl = {$clientId}"; }
            else if($grupare == 2)
                { $cond_having=" HAVING clp.master = {$clientId}"; }
            else {
                $cond_having=" HAVING IF(grupare = 1, clp.cod_cl , clp.master) = {$clientId}";
            }
        }
        $perioada_facturare = date("d/m/Y",strtotime($data_start))." - ".date("d/m/Y",strtotime($data_final));

        if(strlen($data_start) && strlen($data_final)){

            $date_range = " AND ( ep.data_expeditie >= '".self::RELEASE_DATE."' AND ep.data_expeditie <= '{$data_final}') ";
            if($this->mod_generare == 2){
                $date_range = " AND ( ep.data_expeditie >= '{$data_start}' AND ep.data_expeditie <= '{$data_final}' ) ";
            }

        } else  {
            $date_range = " AND 1=2";
        }
        //error_log($date_range);
        //exit();

        if($tip_facturare > 0){
            if($cond_having == "") $cond_having = " HAVING ";
            else $cond_having .= " AND ";
            switch ($tip_facturare){
                    case self::FACTURARE_LUNARA:
                        $cond_having .= " IF(gr_tip_facturare = 1,clp.tip_facturare,clpm.tip_facturare) = {$tip_facturare} AND IF(gr_tip_facturare = 1,clp.DATA_FACTURARE,clpm.DATA_FACTURARE) < 1";
                        break;
                    case self::FACTURARE_LUNARA_LA_DATA_SPECIFICA:
                        $cond_having .= " IF(gr_tip_facturare = 1,clp.DATA_FACTURARE,clpm.DATA_FACTURARE) = ". intval(date('d'));
                        break;
                    case self::FACTURARE_BILUNARA:
                        $cond_having .= " IF(gr_tip_facturare = 1,clp.tip_facturare,clpm.tip_facturare) = {$tip_facturare}";
                        break;
                    case self::FACTURARE_SAPTAMANALA:
                        $cond_having .= " IF(gr_tip_facturare = 1,clp.tip_facturare,clpm.tip_facturare) = {$tip_facturare}";
                        break;
                    default:
                        $cond_having .= " IF(gr_tip_facturare = 1,clp.tip_facturare,clpm.tip_facturare) IN (0, {$tip_facturare} )";
                        break;
            }
        }

        $tip_strorno = false;
        $storno_join = "";
        // regula pentru stornare
        if(count($expeditii) > 0 && $factura_initiala > 0){
            if($this->debug)
                $this->log("storno_join : {$clientId} : {$factura_initiala} : " . implode(',', $expeditii), BackEnd::APP_LOG_FILE);
            $query_fa_init = "SELECT invoice, trndate, sumamnt, procTva FROM exp_facturi WHERE id = {$factura_initiala}";
            $factura_initiala_data = $this->db->QFetchArray($query_fa_init);

            if($this->debug)
                $this->log("factura_initiala_data : " . print_r($factura_initiala_data, true), BackEnd::APP_LOG_FILE);

            $expeditii = $this->cleanArray($expeditii);
            $cond .= " AND ep.idfact = {$factura_initiala} AND ep.expeditie IN (".implode(',',$expeditii).")";

            if($this->debug)
                $this->log("cond : {$cond}", BackEnd::APP_LOG_FILE);

            $date_range = "";
            $tip_strorno = true;
            $storno_join = "INNER JOIN exp_facturi_expeditii efe ON efe.expeditie = ep.expeditie AND efe.idfact=".$factura_initiala;

            if($this->debug)
                $this->log("storno_join : {$storno_join}", BackEnd::APP_LOG_FILE);

        } else if(count($this->expeditii_factura)){
            $expeditii_noi = $this->cleanArray($this->expeditii_factura);
            $cond .= " AND ep.expeditie IN (".implode(',',$expeditii_noi).")";
            $date_range = "";
        } else {
            if(is_array($expeditii_noi) && count($expeditii_noi)>0){
                $cond .= " AND ep.expeditie IN (".implode(',',$expeditii_noi).")";
                $date_range = "";
            }
            else {
                $cond .= " AND ep.idfact = 0";
            }
        }

        if($reseteaza === false && $update > 0 && count($expeditii_noi) > 0){
            $cond = " AND ep.expeditie IN (".implode(',',$expeditii_noi).")";
        }

        //trebuie sa fac update idfact = 1 la toate expeditiile inainte de RELEASE_DATE
        //search clienti cu expeditii in perioada de facturare
        $query = "SELECT clp.cod_cl as cl_cod_cl, clp.master as cl_master, clp.nume as cl_nume, clp.nume_societate as cl_nume_societate, clp.cod_fiscal as cl_cod_fiscal, clp.tip_tva as cl_tip_tva,
            clp.tarif_manual as cl_tarif_manual, clp.fara_factura as cl_fara_factura, clp.facturare_separata as cl_facturare_separata,
            clp.ff_ultima_zi as cl_ff_ultima_zi, clp.valoare_maxima_factura as cl_valoare_maxima_factura, clp.tip_facturare as cl_tip_facturare, clp.DATA_FACTURARE as cl_data_facturare,
            clpm.cod_cl as cm_cod_cl, clpm.nume as cm_nume, clpm.nume_societate as cm_nume_societate, clpm.cod_fiscal as cm_cod_fiscal, clpm.tip_tva as cm_tip_tva,
            clpm.tarif_manual as cm_tarif_manual, clpm.fara_factura as cm_fara_factura, clpm.facturare_separata as cm_facturare_separata,
            clpm.ff_ultima_zi as cm_ff_ultima_zi, clpm.valoare_maxima_factura as cm_valoare_maxima_factura, clpm.tip_facturare as cm_tip_facturare, clpm.DATA_FACTURARE as cm_data_facturare,
            SUM(ep.valoare_totala_expeditie) as total,
            COUNT(ep.expeditie) as nr_expeditii,
            CASE
                WHEN clp.master = 0 or clp.master is NULL THEN 1
                WHEN clp.master > 0 and (clpm.mod_plata = 0 or clpm.activ = 0 or clpm.sters = 1) THEN 1
                WHEN clp.master > 0 and clpm.facturare_separata = 1 THEN 1
                WHEN clp.facturare_separata = 1 THEN 1
                WHEN clp.master > 0 and clpm.facturare_separata = 0 THEN 2
                ELSE 1
            END as grupare,
            CASE
                WHEN clp.master = 0 or clp.master is NULL THEN clp.ff_discount
                WHEN clp.master > 0 and (clpm.mod_plata = 0 or clpm.activ = 0 or clpm.sters = 1) THEN clp.ff_discount
                WHEN clp.master > 0 THEN clpm.ff_discount
                ELSE 1
            END as majorare,
            IF(clp.master = 0 or (clp.master > 0 and (clpm.mod_plata = 0 or clpm.activ = 0 or clpm.sters = 1)),1,2) as gr_tip_facturare
            FROM exp_prelucrate ep
            LEFT JOIN clienti clp ON clp.cod_cl = ep.platitor_id
            LEFT JOIN clienti clpm ON clpm.cod_cl = clp.master
            {$storno_join}
            WHERE 1 {$date_range} {$cond}
            AND ep.anulata = 0
            AND ep.mod_plata IN (1,2)
            GROUP BY IF(grupare = 1, clp.cod_cl, clp.master) {$cond_having}";

        //$this->log($query, $logFilePath);
        // exit();
        $sql = $this->db->QFetchRowArray($query);
        $this->log(count($sql)." Clienti de facturat", $logFilePath);

        if(empty($sql)) {
            $this->log(count($sql)." 0 Clienti de facturat", $logFilePath);
            return 0;
        }

        $i_total = count($sql);
        foreach ($sql as $k=>$new_fact){
            //clean query result
            $exclude_cod_cl = [];
            if(empty($new_fact['grupare']) && $grupare > 0) $new_fact['grupare'] = $grupare;
            if($new_fact['grupare'] == 1){
                $new_fact['cod_cl'] = $new_fact['cl_cod_cl'];
                $new_fact['master'] = $new_fact['cl_master'];
                $new_fact['nume'] = $new_fact['cl_nume'];
                $new_fact['nume_societate'] = $new_fact['cl_nume_societate'];
                $new_fact['cod_fiscal'] = $new_fact['cl_cod_fiscal'];
                $new_fact['tip_tva'] = $new_fact['cl_tip_tva'];
                $new_fact['tarif_manual'] = $new_fact['cl_tarif_manual'];
                $new_fact['fara_factura'] = $new_fact['cl_fara_factura'];
                $new_fact['facturare_separata'] = $new_fact['cl_facturare_separata'];
                $new_fact['ff_ultima_zi'] = $new_fact['cl_ff_ultima_zi'];
                $new_fact['valoare_maxima_factura'] = $new_fact['cl_valoare_maxima_factura'];
            } else {
                $new_fact['cod_cl'] = $new_fact['cm_cod_cl'];
                $new_fact['master'] = $new_fact['cm_cod_cl'];
                $new_fact['nume'] = $new_fact['cm_nume'];
                $new_fact['nume_societate'] = $new_fact['cm_nume_societate'];
                $new_fact['cod_fiscal'] = $new_fact['cm_cod_fiscal'];
                $new_fact['tip_tva'] = $new_fact['cm_tip_tva'];
                $new_fact['tarif_manual'] = $new_fact['cm_tarif_manual'];
                $new_fact['fara_factura'] = $new_fact['cm_fara_factura'];
                $new_fact['facturare_separata'] = $new_fact['cm_facturare_separata'];
                $new_fact['ff_ultima_zi'] = $new_fact['cm_ff_ultima_zi'];
                $new_fact['valoare_maxima_factura'] = $new_fact['cm_valoare_maxima_factura'];
                //cauta puncte de lucru care au facturare individuala pentru master
                $query_exclude = "SELECT clp.cod_cl,
                    CASE
                        WHEN clp.master = 0 or clp.master is NULL THEN 1
                        WHEN clp.master > 0 and (clpm.mod_plata = 0 or clpm.activ = 0 or clpm.sters = 1) THEN 1
                        WHEN clp.master > 0 and clpm.facturare_separata = 1 THEN 1
                        WHEN clp.facturare_separata = 1 THEN 1
                        WHEN clp.master > 0 and clpm.facturare_separata = 0 THEN 2
                        ELSE 1
                    END as grupare
                    FROM clienti clp
                    left join clienti clpm on clp.master = clpm.cod_cl
                    WHERE clp.master = ".$new_fact['cod_cl']." and clp.master <> clp.cod_cl having grupare = 1";
                $sql_exclude = $this->db->QFetchRowArray($query_exclude);
                if(!empty($sql_exclude)){
                    foreach ($sql_exclude as $kex=>$val)
                        $exclude_cod_cl[] = $val['cod_cl'];
                }
            }

            $dataFactura = date('Y-m-d');
            //if($new_fact['ff_ultima_zi'] == 1 && $tip_facturare == self::FACTURARE_LUNARA){
            if($tip_facturare == self::FACTURARE_LUNARA){
                $dataFactura = date('Y-m-d', strtotime('last day of previous month'));
            }
            //if($new_fact['ff_ultima_zi'] == 1 && $tip_facturare == self::FACTURARE_BILUNARA && intval(date('d')) <= 15 ){
            if($tip_facturare == self::FACTURARE_BILUNARA && intval(date('d')) <= 15 ){
                $dataFactura = date('Y-m-d', strtotime('last day of previous month'));
            }

            $procTva = $this->getProcentTVA($dataFactura);
            if($this->debug)
                $this->log("procTva {$procTva} : dataFactura : {$dataFactura}", BackEnd::APP_LOG_FILE);
            //procTva este preluat din factura initiala daca exista
            if($tip_strorno)
                $procTva = $factura_initiala_data['procTva'] ?? $procTva;

            if($this->debug)
                $this->log("tip_strorno : ".intval($tip_strorno)." procTva {$procTva}", BackEnd::APP_LOG_FILE);

            $i = $k+1;
            $proc = number_format(round((($i / $i_total) * 100) , 2), 2);
            if($new_fact['total'] == 0){
                $this->log("{$i} din {$i_total} {$proc} % : client : {$new_fact['cod_cl']}", $logFilePath);
                $this->log("client : {$new_fact['cod_cl']} : total factura = 0 : expeditii : {$new_fact['nr_expeditii']}", $logFilePath);
                continue;
            } else if (!$from_imparteFactura && intval($new_fact['valoare_maxima_factura']) > 0 && intval($new_fact['total']) > intval($new_fact['valoare_maxima_factura'])) {
                $this->log("{$i} din {$i_total} {$proc} % : client : {$new_fact['cod_cl']}", $logFilePath);
                $this->log("imparteFactura client : {$new_fact['cod_cl']} : valMaxFactura : {$new_fact['valoare_maxima_factura']}", $logFilePath);
                $this->imparteFactura($new_fact['grupare'], $new_fact['cod_cl'], $new_fact['valoare_maxima_factura'], $data_final, $exclude_cod_cl, $tip_facturare);
                continue;
            }

            $cond_sel = "";
            if($this->tip_factura == self::FACTURA_TIP_STORNO || $tip_strorno){
                $expeditii = $this->cleanArray($expeditii);
                if(count($expeditii)){
                    $date_range = " AND ep.expeditie IN (".implode(',',$expeditii).")";
                } else {
                    $date_range = " AND ep.expeditie IN (".implode(',',$this->expeditii_factura).")";
                }

            } else {
                if(count($expeditii_noi)>0){
                    $ifdact_cond = "AND ep.idfact = 0";
                    if($this->tip_factura == self::FACTURA_TIP_STORNO){
                        $ifdact_cond = "AND ep.idfact > 0";
                    }
                    $cond_sel .= " {$ifdact_cond} AND ep.expeditie IN (".implode(',',$expeditii_noi).")";
                }
                else {
                    $cond_sel .= " AND ep.idfact = 0";
                }
            }

            //?????
            if($reseteaza === false && $update >0 && count($expeditii_noi)>0){
                $cond_sel = " AND ep.expeditie IN (".implode(',',$expeditii_noi).")";
            }

            $filtru_id = " 1=1 ";
            if($new_fact['grupare'] == 1){
                $filtru_id = "clp.cod_cl = ".$new_fact['cod_cl'];
            }
            else {
                $filtru_id = "clpm.cod_cl = ".$new_fact['cod_cl'];
                if(count($exclude_cod_cl) > 0)
                    $filtru_id .= " AND ep.platitor_id not in (".implode(",",$exclude_cod_cl).")";
            }

            $query_expeditii = "SELECT ep.cod_expeditie, ep.expeditie, ep.data_expeditie,
                ep.plicuri, ep.colete, ep.paleti, ep.tip_exp, ep.greutate, ep.km_preluare, ep.km_livrare, ep.sms,
                ep.val_greutate, ep.val_km, ep.val_asig, ep.valoare_expeditie, ep.valoare_totala_expeditie,
                cle.nume as expeditor, lce.nume_lc as expeditor_localitate,
                cld.nume as destinatar, lcd.nume_lc as destinatar_localitate, 
                IF(cld.zona_id > 0 and cldc.id > 0, cldc.nume, ced.nume) as destinatar_centru,
                clp.cod_cl as platitor_id, clp.master as platitor_master_id, clp.nume as platitor
                FROM exp_prelucrate ep
                LEFT JOIN clienti cle ON ep.expeditor_id = cle.cod_cl
                LEFT JOIN localitati lce on lce.cod_lc = cle.cod_lc
                LEFT JOIN clienti cld ON ep.destinatar_id = cld.cod_cl
                LEFT JOIN zones cldz ON cldz.id = cld.zona_id
        	    LEFT JOIN centre cldc on cldc.id = cldz.centru_id
                LEFT JOIN localitati lcd on lcd.cod_lc = cld.cod_lc
                LEFT JOIN centre ced on ced.id = lcd.cod_centru
                LEFT JOIN clienti clp ON ep.platitor_id = clp.cod_cl
                LEFT JOIN clienti clpm ON clpm.cod_cl = clp.master
                WHERE {$filtru_id}
                AND ep.mod_plata IN (1,2)
                AND ep.anulata = 0
                {$date_range} {$cond_sel}";
            if($tip_strorno){
                $query_expeditii = "SELECT ep.cod_expeditie, efe.expeditie, efe.data_expeditie, ef.procTva,
                efe.plicuri, efe.colete, efe.paleti, efe.tip_expeditie, efe.greutate, efe.km_preluare, efe.km_livrare, efe.sms,
                efe.val_greutate, efe.val_km, efe.val_asig, efe.valoare_expeditie, efe.valoare_totala_expeditie,
                efe.expeditor, efe.expeditor_localitate, efe.destinatar, efe.destinatar_localitate, efe.destinatar_centru,
                efe.platitor, clp.cod_cl as platitor_id, clp.master as platitor_master_id
                FROM exp_prelucrate ep
                INNER JOIN exp_facturi_expeditii efe ON efe.expeditie = ep.expeditie
                INNER JOIN exp_facturi ef on efe.idfact = ef.id
                LEFT JOIN clienti clp ON clp.cod_cl = ep.platitor_id
                LEFT JOIN clienti clpm ON clpm.cod_cl = clp.master
                WHERE {$filtru_id} AND ef.id = {$factura_initiala}
                AND ep.mod_plata IN (1,2)
                AND ep.anulata = 0
                {$date_range} {$cond_sel}";
            }

            if($this->debug)
                $this->log("query_expeditii : {$query_expeditii}", BackEnd::APP_LOG_FILE);

            $sql_expeditii = $this->db->QFetchRowArray($query_expeditii);
            $query_total = "SELECT
                SUM(ep.valoare_totala_expeditie) as total,
                COUNT(ep.expeditie) as nr_expeditii
                FROM exp_prelucrate ep
                LEFT JOIN clienti clp ON clp.cod_cl = ep.platitor_id
                LEFT JOIN clienti clpm ON clpm.cod_cl = clp.master
                WHERE {$filtru_id}
                AND ep.mod_plata IN (1,2)
                AND ep.anulata = 0
                {$date_range} {$cond_sel}
            ";

            if($this->debug)
                $this->log("query_total : {$query_total}", BackEnd::APP_LOG_FILE);

            $sql_total = $this->db->QFetchRowArray($query_total);
            if(empty($sql_total)){
                $this->log("EROARE FACTURARE count:0 query_total !", $logFilePath);
                $this->log("COD CL:".$new_fact['cod_cl']." ".$new_fact['nume'], $logFilePath);
                $this->log($query_total, $logFilePath);
                continue;
            }
            $liniii_factura = [];
            $update_total = 0;

            //error_log($query_total); die();
            foreach ($sql_total as $linie){
                $linie_tva = "";
                if(count($sql_total) > 1)
                    $linie_tva = " {$procTva} T.V.A.";
                if($this->debug)
                    $this->log("linie_tva : {$linie_tva}", BackEnd::APP_LOG_FILE);
                $detalii_linie = $this->servicii['01']." {$perioada_facturare} ".$linie_tva;

                $total_linie = $linie['total'];
                $tva_linie =  round($linie['total'] * $procTva / 100, 2);

                if($factura_initiala > 0){
                    if($this->debug)
                        $this->log("Stornare la procTva : {$procTva}", BackEnd::APP_LOG_FILE);
                    $detalii_linie = "Stornare la ".(!empty($factura_initiala_data) && isset($factura_initiala_data['invoice']) ? $factura_initiala_data['invoice'] : "unknown");
                    $total_linie = -1 * abs($linie['total']);
                    $tva_linie =  round($linie['total'] * $procTva / 100, 2);
                    $tva_linie = -1 * abs($tva_linie);
                }
                else if($this->tip_factura == self::FACTURA_TIP_STORNO && count($this->expeditii_factura)){
                    return 0;
                    $expeditii_noi = $this->cleanArray($this->expeditii_factura);
                    $sql_fa = false;
                    if(count($expeditii_noi)) {
                        $query_fa = "SELECT group_concat(invoice) as facturi, min(procTva) as procTva  FROM exp_facturi WHERE id in (select idfact from exp_facturi_expeditii where expeditie IN (".implode(',',$expeditii_noi)."))";
                        $sql_fa = $this->db->QFetchArray($query_fa);
                    }
                    $detalii_linie = "Stornare la " . (!empty($sql_fa) && isset($sql_fa['facturi']) ? $sql_fa['facturi'] : "unknown");
                    $total_linie = -1 * abs($linie['total']);
                    $procTva = $sql_fa['procTva'] ?? $procTva;
                    $tva_linie =  round($linie['total'] * $procTva / 100, 2);
                    $tva_linie = -1 * abs($tva_linie);
                }

                $update_total += $total_linie;


                $liniii_factura[] = array(
                    'nume' => $detalii_linie,
                    'cantitate' => 1,
                    'valoare' => $total_linie,
                    'tva' => $tva_linie,
                    'procTva' => abs($procTva),
                    'type'=> 0,
                );
                if($this->debug)
                    $this->log("liniii_factura : " . print_r($liniii_factura, true), BackEnd::APP_LOG_FILE);

            }

            if($tip_strorno || $this->tip_factura == self::FACTURA_TIP_STORNO){
                if($new_fact['fara_factura'] == 1){
                    $proforma = $this->getNumarFactura('DSCPUST');
                } else {
                    $proforma = $this->getNumarFactura('DSCPST');
                }

                if(strlen($data_start) == 0){
                    $data_start = $data_final = "1970-01-01";
                }

                if($this->debug)
                    $this->log("procTva_storno : {$procTva}", BackEnd::APP_LOG_FILE);
                $total_storno = -1 * abs($new_fact['total']);
                $procTva_storno = -1 * abs($procTva);
                $tva_storno = -1 * abs(round($new_fact['total'] * $procTva / 100, 2));
                $invoice_data = array(
                    'invoice' => $proforma,
                    'sumamnt' => $total_storno,
                    'mod_generare' => $this->mod_generare,
                    'tip_factura' => self::FACTURA_TIP_STORNO,
                    'operator' => $this->user_id,
                    'sumamnt_exp' => $new_fact['total'],
                    'cod_cl'=>$new_fact['cod_cl'],
                    'trndate'=> $dataFactura,
                    'procTva' =>  $procTva_storno,
                    'tva' =>  $tva_storno,
                    'nr_exp' => count($sql_expeditii),
                    'status'=> self::FACTURA_PREGATITA,
                    'interval_start' => $data_start,
                    'interval_stop' => $data_final,
                    'majorare' => $new_fact['majorare'],
                    'factura_initiala' => $factura_initiala
                );
                if($this->debug)
                    $this->log("invoice_data : " . print_r($invoice_data, true), BackEnd::APP_LOG_FILE);
            }
            else {

                if($new_fact['fara_factura'] == 1){
                    $proforma = $this->getNumarFactura('DSCPU');
                } else {
                    switch ($tip_facturare){
                        case self::FACTURARE_LUNARA:
                        case self::FACTURARE_LUNARA_LA_DATA_SPECIFICA:
                            $proforma = $this->getNumarFactura('DSCPL');
                            break;
                        case self::FACTURARE_BILUNARA:
                            $proforma = $this->getNumarFactura('DSCPB');
                            break;
                        case self::FACTURARE_SAPTAMANALA:
                            $proforma = $this->getNumarFactura('DSCPS');
                            break;
                        default:
                            $proforma = $this->getNumarFactura('DSCPM');
                            break;
                    }
                }

                if(strlen($data_start) == 0){
                    $data_start = $data_final = "1970-01-01";
                }


                $invoice_data = array(
                    'invoice' => $proforma,
                    'sumamnt' => $new_fact['total'],
                    'mod_generare' => $this->mod_generare,
                    'tip_factura' => self::FACTURA_TIP_NORMALA,
                    'operator' => $this->user_id,
                    'sumamnt_exp' => $new_fact['total'],
                    'cod_cl'=>$new_fact['cod_cl'],
                    'trndate'=> $dataFactura,
                    'procTva' => $procTva,
                    'tva' => round($new_fact['total'] * $procTva / 100, 2),
                    'nr_exp' => $linie['nr_expeditii'] ,
                    'status'=> self::FACTURA_PREGATITA,
                    'interval_start' => $data_start,
                    'interval_stop' => $data_final,
                    'majorare' => $new_fact['majorare'],
                    'factura_initiala' => $factura_initiala
                );
                //daca majorare insert factura_linie
                if($new_fact['majorare'] > 1) {
                    $maj = round(($new_fact['majorare'] * $new_fact['total'] / 100), 2);
                    $liniii_factura[] = array(
                        'nume' => $this->servicii['03']." {$perioada_facturare} ",
                        'cantitate' => 1,
                        'valoare' => $maj,
                        'tva' => round($procTva * $maj / 100, 2),
                        'procTva' => $procTva,
                        'type'=> 1,
                    );
                }
            }
            if($this->debug)
                $this->log("line2121 : procTva : {$procTva}", BackEnd::APP_LOG_FILE);
            if($update >0){
                $id = $update;
                //error_log("update"); die();
                $this->db->QueryUpdate('exp_facturi', $invoice_data, "id={$id}");
                $this->db->Query("DELETE FROM factura_linie WHERE factura_id={$id}");

            } else {
                $invoice_data['data_adaugare'] = date("Y-m-d H:i:s");
                //error_log(print_r($invoice_data, true)); die();
                $id = $this->db->QueryInsert('exp_facturi', $invoice_data);
            }
            if(intval($id) == 0){
                $this->log("EROARE FACTURARE id fact:{$id} count:".count($liniii_factura), $logFilePath);
                return 0;
            }

            foreach ($liniii_factura as $linie_factura){
                $linie_factura['factura_id'] = $id;
                $this->db->QueryInsert('factura_linie', $linie_factura);
            }

            if($tip_strorno || $this->tip_factura == self::FACTURA_TIP_STORNO){
                $update_total = -1 * abs($update_total);
            }

            if($this->debug)
                $this->log("update_total : {$update_total}", BackEnd::APP_LOG_FILE);
            $this->db->QueryUpdate('exp_facturi', ['sumamnt' => $update_total], "id={$id}");

            $expeditii_facturate = [];
            foreach ($sql_expeditii as $expeditie){
                $this->insertIstExp($expeditie['cod_expeditie'], self::EXPEDITIE_IN_CURS_DE_FACTURARE);
                $this->db->QueryInsert('exp_facturi_expeditii',
                    array(
                        'idfact' => $id,
                        'cod_cl' => $expeditie['platitor_id'],
                        'master' => $expeditie['platitor_master_id'],
                        'expeditie' => $expeditie['expeditie'],
                        'data_expeditie' => $expeditie['data_expeditie'],
                        'platitor' => $expeditie['platitor'],
                        'expeditor' => $expeditie['expeditor'],
                        'expeditor_localitate' => $expeditie['expeditor_localitate'],
                        'destinatar' => $expeditie['destinatar'],
                        'destinatar_localitate' => $expeditie['destinatar_localitate'],
                        'destinatar_centru' => $expeditie['destinatar_centru'],
                        'plicuri' => $expeditie['plicuri'],
                        'colete' => $expeditie['colete'],
                        'paleti' => $expeditie['paleti'],
                        'tip_expeditie' => (($tip_strorno) ? $expeditie['tip_expeditie'] : (ExpeditieDto::TIP_EXP[$expeditie['tip_exp']] ?? "unknown")),
                        'greutate' => $expeditie['greutate'],
                        'km_preluare' => $expeditie['km_preluare'],
                        'km_livrare' => $expeditie['km_livrare'],
                        'val_greutate' => $expeditie['val_greutate'],
                        'val_km' => $expeditie['val_km'],
                        'val_asig' => $expeditie['val_asig'],
                        'valoare_expeditie' => $expeditie['valoare_expeditie'],
                        'valoare_totala_expeditie' => $expeditie['valoare_totala_expeditie'],
                        'sms' => $expeditie['sms'] >= 0 ? $expeditie['sms'] : 0
                    )
                );
                if($expeditie['expeditie'] > 0)
                    $expeditii_facturate[] = intval($expeditie['expeditie']);
            }

            if(count($expeditii_facturate)){
                if($factura_initiala > 0){
                    $id = 0;
                }
                $this->db->QueryUpdate('exp_prelucrate', ['idfact' => $id], "expeditie IN (" . implode(',', $expeditii_facturate) . ")");
            }

            $this->log("{$i} din {$i_total} {$proc} % : client : {$new_fact['cod_cl']}", $logFilePath);
        }
        return $i_total;
    }

    function JSON_PozitiiFactura($facturaId){
        $facturaId = intval($facturaId ?? 0);
        if($this->debug)
            $this->log("JSON_PozitiiFactura facturaId : {$facturaId}", BackEnd::APP_LOG_FILE);

        if(isset($_POST['idfact'])){
            $facturaId = intval($_POST['idfact'] ?? 0);
            if($facturaId <= 0)
                return;
            $fact = $this->getFactura(intval($_POST['idfact']));
            if(!is_array($fact) || count($fact) == 0 || isset($fact['status']) && $fact['status'] >= self::FACTURA_FINALIZATA )
                return;
            $this->procTva = $fact['procTva'] ?? ($this->getProcentTVA($fact['trndate']) ?? $this->procTva);
            if($this->debug)
                $this->log("JSON_PozitiiFactura procTva : {$this->procTva}", BackEnd::APP_LOG_FILE);
        }

        if($this->debug)
            $this->log("JSON_PozitiiFactura procTva : {$this->procTva}", BackEnd::APP_LOG_FILE);

        if(isset($_POST['oper']) && isset($_POST['oper']) && $_POST['oper'] == 'add' && isset($_POST['valoare_fara_tva'])){
            $facturaId = intval($_POST['idfact'] ?? 0);
            if($facturaId <= 0)
                return;
            $tva = round(($_POST['valoare_fara_tva'] / 100) * $this->procTva, 2);
            $linie_factura = $this->db->QueryInsert('factura_linie',
                array(
                    'factura_id' => $facturaId,
                    'nume' => $_POST['nume'],
                    'cantitate' => $_POST['cantitate'] ?? 1,
                    'valoare' => $_POST['valoare_fara_tva'],
                    'tva' => $tva,
                    'procTva' => abs($this->procTva),
                    'type'=> 1,
                )
            );
        }

        if(isset($_POST['id'])){
            // verificare tip linie
            $query = "SELECT * FROM factura_linie WHERE id =".intval($_POST['id']);
            $result = $this->db->QFetchArray($query);
            if(!empty($result) && isset($result['type']) && intval($result['type']) == 0 && isset($_POST['oper']) && $_POST['oper'] != 'edit'){
                return;
            }
        }

        if(isset($_POST['oper']) && $_POST['oper'] == 'del'){
            $this->db->Query("DELETE FROM factura_linie WHERE id=". intval($_POST['id']));
        }

        if(isset($_POST['oper']) && isset($_POST['oper']) && $_POST['oper'] == 'edit' && isset($_POST['valoare_fara_tva'])){
            $tva = -1 * abs(round(($_POST['valoare_fara_tva'] / 100) * $this->procTva, 2));
            if(intval($result['type']) > 0){ // temp facturare
                $linie = array(
                    'nume'=> $_POST['nume'],
                    'cantitate'=> $_POST['cantitate'] ?? 1,
                    'valoare'=> $_POST['valoare_fara_tva'],
                    'tva'=> $tva,
                    'procTva' => abs($this->procTva)
                );
            } else {
                $linie = array(
                    'nume'=> $_POST['nume'],
                    'cantitate'=> $_POST['cantitate'] ?? 1, // temp facturare
                    'valoare'=> $_POST['valoare_fara_tva'], // temp facturare
                    'tva'=> $tva, // temp facturare
                    'procTva' => abs($this->procTva)
                );
            }

            $this->db->QueryUpdate('factura_linie', $linie, "id=".intval($_POST['id']));
        }

        $responce = new StdClass();
        $records = 0;
        if($facturaId > 0){
            //error_log("d3:".$facturaId);
            $query = "SELECT * FROM factura_linie WHERE factura_id = {$facturaId}";
            $result = $this->db->QFetchRowArray($query);
            if(!empty($result)) {
                $records = count($result);
                if($records){
                    foreach ($result as $i => $row ){
                        $responce->rows[$i]['id'] = $row['id'];
                        $responce->rows[$i]['cell'] = array(
                            //    $row['factura_id'],
                            $row['nume'],
                            $row['cantitate'],
                            $row['valoare'],
                            $row['tva'],
                            $facturaId
                        );
                    }
                }
            }
        }
        $responce->page = 1;
        $responce->total = 1;
        $responce->records = $records;
        // $responce->userdata['client_id'] = $expeditii;
        echo json_encode($responce);
    }


    function printareMultipla($facturiId, $borderou_pdf = false){
        $facturiId = json_decode($facturiId,true);
        $this->Pdf($facturiId, "I", 'facturi.pdf', $borderou_pdf);
        return;
    }

    function Pdf($facturiId, $acction = "I", $filename = 'facturi.pdf', $borderou_pdf = false){

        require_once "FacturaPdf.php";

        if(empty($facturiId) || !is_array($facturiId)) return $this->Error('Factura invalida!');


        $pdf = new FacturaPdf();
        foreach ($facturiId as $facturaId){

            $query = "SELECT * FROM exp_facturi WHERE id = {$facturaId}";
            $factura = $this->db->QFetchArray($query);

            if(empty($factura)) continue;

            $query = "SELECT * FROM factura_linie WHERE factura_id = {$facturaId}";
            $linii_factura = $this->db->QFetchRowArray($query);

            $query = "SELECT clp.*, b.nume as banca_fa, js.nume_jd as judet_sediu, jl.nume_jd as judet_livrare, 
            IF(clp.zona_id > 0 and clpc.id > 0, clpc.label, ce.label) as centru_livrare
            FROM clienti clp
            LEFT JOIN localitati ls ON ls.cod_lc = clp.cod_lc_sediu_social
            LEFT JOIN judete js ON js.cod_jd = ls.cod_jd

            LEFT JOIN localitati ll ON ll.cod_lc = clp.cod_lc
            LEFT JOIN judete jl ON jl.cod_jd = ll.cod_jd

            LEFT JOIN zones clpz ON clpz.id = clp.zona_id
        	LEFT JOIN centre clpc on clpc.id = clpz.centru_id

            LEFT JOIN centre ce ON ce.id = ll.cod_centru
            LEFT JOIN banci b on b.id = clp.banca_fa_id
            WHERE clp.cod_cl = {$factura['cod_cl']} ORDER BY cod_fiscal DESC , mod_plata DESC";

            $client = $this->db->QFetchArray($query);

            $centralizator_arr = $this->getCentralizator($facturaId);
            $centralizator = $centralizator_arr['centralizator'];
            $procTva = $centralizator_arr['procTva'];

            $vars = array(
                'factura'               => $factura,
                'linii'                 => $linii_factura,
                'client'                => $client,
                'centralizator'         => $centralizator
            );

            $pdf->cota_tva = !empty($factura['procTva']) ? [$factura['procTva']] : $procTva;

            $pdf->setVars($vars);

            $pdf->AddPage();
            $pdf->make($pdf);
            if($factura['tip_factura'] == self::FACTURA_TIP_NORMALA){
                $pdf->AddPage();
                $pdf->centralizator();
            }

            if($borderou_pdf || (isset($client['BORDEROU_PDF']) && intval($client['BORDEROU_PDF']) == 1)){
                $query = "SELECT * FROM exp_facturi_expeditii WHERE idfact=".$factura['id'];
                $sql = $this->db->QFetchRowArray($query);
                $vars['borderou_expeditii'] = $sql;
                $pdf->setVars($vars);
                // $pdf->AddPage();
                $pdf->borderouExpeditii();
            }

        }
        if($acction == 'F') {
            if(file_exists($filename))
                unlink($filename);
        }
        $pdf->Output($filename, $acction);
        if($acction == 'F') return;
        exit;
    }



    function getCentralizator($facturaId){

        $client = $this->db->QFetchArray("SELECT c.*
            FROM exp_facturi f
            LEFT JOIN clienti c ON c.cod_cl = f.cod_cl
            WHERE f.id={$facturaId}
        ");

        $tva_query = "(ep.valoare_totala_expeditie * ef.procTva / 100)";
        if($client['tip_tva'] == 2) {
            $tva_query = "0";
        }

        $query = "SELECT efe.*, ef.procTva, {$tva_query} as tva
            FROM exp_facturi_expeditii efe
            INNER JOIN exp_facturi ef ON ef.id = efe.idfact
            LEFT JOIN exp_prelucrate ep ON (ep.expeditie = efe.expeditie and ep.anulata = 0)
            WHERE ef.id = {$facturaId}";

        $expeditii = $this->db->QFetchRowArray($query);
        if(empty($expeditii)){
            return array('centralizator'=>[],'procTva'=>[]);
        }
        $centralizator = [];
        $centralizator['nr_exp']['label'] = "";
        $centralizator['nr_exp']['value'] = 0;

        $sum_rows = array(
            'plicuri' => 'Plicuri',
            'colete' => 'Colete',
            'paleti' => 'Paleti',
            'greutate' => 'Greutate',
            'km_preluare' => 'Km preluare',
            'km_livrare' => 'Km livrare',
            'val_greutate' => 'Val greutate',
            'val_km' => 'Val km',
            'val_asig' => 'Val asig',
            'valoare_expeditie' => 'Valoare expeditii',
            'valoare_totala_expeditie' => 'Valoare totala expeditii',
            'tva' => 'Valoare T.V.A.',
        );

        $sum_rows_tabel = array(
            'nr' => 'Expeditii<br>(buc.)',
            'piese' => 'Piese<br>(buc.)',
            'valoare_expeditie' => 'Tarif baza<br>(lei)',
            'val_km' => 'Tarif km.<br>(lei)',
            'val_greutate' => 'Tarif kg.<br>(lei)',
            'val_asig' => 'Tarif asig.<br>(lei)',
            'valoare_totala_expeditie' => 'TOTAL<br>(lei)',
            'tva' => 'T.V.A.<br>(lei)',
        );



        $centralizator['nr_exp']['label'] = 'Nr. exp';
        $centralizator['nr_exp']['value'] += count($expeditii);

        $tip_expeditii_arr = array('colete','plicuri','paleti');


        foreach ($tip_expeditii_arr as $tip) {
            foreach ($sum_rows_tabel as $key => $label) {
                $centralizator['tabel_values'][$tip][$key] = 0;
            }
        }

        $centralizator['tabel_header'] = $sum_rows_tabel;
        $procTva = [];
        foreach ($expeditii as $row){
            $procTva[$row['procTva']] = 1;
            foreach ($tip_expeditii_arr as $tip) {
                if($row[$tip] == 0){
                    continue;
                }
                foreach ($sum_rows_tabel as $key => $label) {
                    if(!isset($centralizator['tabel_values']['total'][$key])){
                        $centralizator['tabel_values']['total'][$key] = 0;
                    }
                    if($key == 'nr'){
                        $centralizator['tabel_values'][$tip][$key] += 1;
                        $centralizator['tabel_values']['total'][$key] +=1;
                    } else if ($key == 'piese') {
                        $centralizator['tabel_values'][$tip][$key] += $row[$tip];
                        $centralizator['tabel_values']['total'][$key] += $row[$tip];
                    } else if($row[$tip] > 0){
                        if(!isset($row[$key])){
                            $row[$key] = 0;
                        }
                        $centralizator['tabel_values'][$tip][$key] = $centralizator['tabel_values'][$tip][$key] + $row[$key];
                    }
                    if(isset($row[$key])){
                        if(!isset($centralizator['tabel_values']['total'][$key])){
                            $centralizator['tabel_values']['total'][$key] = 0;
                        }
                        $centralizator['tabel_values']['total'][$key] = $centralizator['tabel_values']['total'][$key] + $row[$key];
                    }
                }

            }
        }

        return array('centralizator'=>$centralizator,'procTva'=>$procTva);
    }

    function ExportFacturi(){

        $data = date('d/m/Y');
        $societate = 'Dragon Star Curier';
        $document = 'Facturi';

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

        $query = " SELECT f.* ,f.cod_cl as factura_cod_cl , clp.* ,
        IF(length(clp.nume_societate) > 0, clp.nume_societate,clp.nume) as nume_societate, clp.email_factura,
        b.nume as banca_fa, group_concat(fl.procTva) as procTva, sum(fl.valoare) as fl_suma
	    FROM exp_facturi f
        LEFT JOIN clienti clp ON clp.cod_cl = f.cod_cl
        LEFT JOIN banci b ON clp.banca_fa_id = b.id
        inner JOIN factura_linie fl ON fl.factura_id = f.id
	    WHERE f.id IN (".$_POST['data'].") GROUP by f.id";

        $sql = [];
        if(!empty($_POST['data']))
            $sql = $this->db->QFetchRowArray($query);
        // exit();

        $fields_to_export = array(
            'trndate' => 'Data',
            'invoice'=> 'Serie',
            'fl_suma'=> 'Valoare',
            'procTva'=> 'T.V.A.',
            'nr_exp'=> 'Nr.Exp.',
            'status'=> 'Status',
            'trimisa'=> 'Trimisa',
            'interval_start'=> 'Interval Start',
            'interval_stop'=> 'Interval Stop',
            'nume'=> 'Nume',
            'nume_societate'=> 'Nume Societate',
            'tarif_manual'=> 'Tarif Manual',
            'cod_fiscal'=> 'COD FISCAL',
            'reg_com'=> 'REG_COM',
            'cont_fa'=> 'CONT',
            'banca_fa'=> 'BANCA',
            'contract'=> 'CONTRACT',
            'tip_facturare' => 'TIP FACTURARE',
            'factura_cod_cl' => 'COD CL Factura',
            'cod_cl' => 'COD CL',
            'email_factura' => 'EMAIL FACTURA'
        );


        $rangeArr = range('A', 'Z') ;
        $i = 0;
        foreach ($fields_to_export as $key=>$label){
            $worksheet->setCellValue($rangeArr[$i].'1',$label);
            $i++;
        }
        $rand=2;
        $total_expeditii=0;

        if (!empty($sql)) {

            foreach ($sql as $key => $row) {
                $rangeArr = range('A', 'Z') ;
                $i = 0;
                foreach ($fields_to_export as $label_key=>$label){
                    if(substr($row[$label_key],0,1) == "=")
                        $row[$label_key] = substr($row[$label_key],1);

                    if($label_key == "trndate") {
                        $row[$label_key] = \PhpOffice\PhpSpreadsheet\Shared\Date::PHPToExcel(new DateTime($row[$label_key]));
                        $worksheet->setCellValue($rangeArr[$i].$rand,$row[$label_key]);
                        $worksheet->getStyle($rangeArr[$i].$rand)->getNumberFormat()->setFormatCode(
                            \PhpOffice\PhpSpreadsheet\Style\NumberFormat::FORMAT_DATE_DDMMYYYY
                            );
                    }
                    else {
                        if(isset($row[$label_key]))
                            $worksheet->setCellValueExplicit($rangeArr[$i].$rand, $row[$label_key], \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
                    }
                    $i++;
                }

                $rand++;
                $total_expeditii++;
            }
            $rand++;
            $worksheet->setCellValue($rangeArr[0].($rand),"Valoare totala:");
            $worksheet->setCellValue($rangeArr[1].($rand),"=SUM(C2:C".($rand - 1).")");
            $worksheet->setCellValue($rangeArr[0].($rand +1),"T.V.A:");
            $worksheet->setCellValue($rangeArr[1].($rand +1),"=SUM(D2:D".($rand - 1).")");


            $worksheet->setCellValue($rangeArr[0].($rand +2),"Total:");
            $worksheet->setCellValue($rangeArr[1].($rand +2),"=SUM(B".($rand).":B".($rand + 1).")");
            foreach(range('A','J') as $columnID) {
                $worksheet->getColumnDimension($columnID)->setAutoSize(true);
            }

        }

        $data = date('d_m_Y');
        $filename = 'Facturi_'.$data.'.xlsx';

        $this->download_send_headers_xls($filename);
        $writer = new Xlsx($spreadsheet);
        $writer->save('php://output');
		die;
    }


    function ExportBorderouXLS($idfact, $return_file_path = false ) {
        $data = date('d/m/Y');
        $societate = 'Dragon Star Curier';
        $document = 'Lista Expeditii';

        try{
            $spreadsheet = new Spreadsheet();
            $spreadsheet->setActiveSheetIndex(0);
            $worksheet = $spreadsheet->getActiveSheet();
            $spreadsheet->getProperties()->setCreator($societate)
                    ->setLastModifiedBy($societate)
                    ->setTitle($document)
                    ->setSubject($document)
                    ->setDescription($document)
                    ->setKeywords($document)
                    ->setCategory($document);
            $spreadsheet->getDefaultStyle()->getFont()->setName('Arial');
            $spreadsheet->getDefaultStyle()->getFont()->setSize(11);

            $fiels_to_export = array(
                'platitor' => 'Platitor	Expeditie',
                'expeditie'=> 'Expeditie',
                'data_expeditie'=> 'Data Colectare',
                'expeditor'=> 'Expeditor',
                'expeditor_localitate'=> 'Localitate Expeditor',
                'destinatar'=> 'Destinatar',
                'destinatar_localitate'=> 'Localitate Destinatar',
                'destinatar_centru'=> 'Centru Destinatar',
                'plicuri'=> 'Plicuri',
                'colete'=> 'Colete',
                'paleti'=> 'Paleti',
                'tip_expeditie'=> 'Tip Expeditie',
                'referire' => 'Retur la NT',
                'ramburs' =>'Valoare RBS',
                'greutate'=> 'Greutate',
                'km_preluare'=> 'Km Prel',
                'km_livrare'=> 'Km Livr',
                'val_greutate'=> 'Val Gr',
                'val_km'=> 'Val km',
                'val_asig'=> 'Val Asig',
                'valoare_expeditie'=> 'Val Exp',
                'valoare_totala_expeditie'=> 'Val Totala',
                'observatii'=> 'Observatii',
                'expeditor_judet'=> 'Judet Expeditor',
                'destinatar_judet'=> 'Judet Destinatar',
                'detalii_doc'=> 'Detalii doc.'
            );

            $rangeArr = $this->excelRange(26);
            $i = 0;
            foreach ($fiels_to_export as $key=>$label){
                $worksheet->setCellValue($rangeArr[$i].'1', $label);
                $i++;
            }

            $rand=2;
            $total_valoare = 0.00;

            $sql = $this->getExpeditiiBorderouFacturare($idfact);
            if (!empty($sql)) {
                foreach ($sql as $key => $row) {
                    $i = 0;
                    foreach ($fiels_to_export as $label_key=>$label){
                        if(substr($row[$label_key],0,1) == "=")
                            $row[$label_key] = substr($row[$label_key],1);

                        if($label_key == "data_expeditie" && !empty($row[$label_key])) {
                            try {
                                $row[$label_key] = new \DateTime($row[$label_key]);
                                $row[$label_key] = \PhpOffice\PhpSpreadsheet\Shared\Date::PHPToExcel($row[$label_key]);
                                $worksheet->setCellValue($rangeArr[$i].$rand,$row[$label_key]);
                                $worksheet->getStyle($rangeArr[$i].$rand)->getNumberFormat()->setFormatCode(
                                    \PhpOffice\PhpSpreadsheet\Style\NumberFormat::FORMAT_DATE_DDMMYYYY
                                    );
                            }
                            catch(Exception $ex) {
                                $this->log("error DateTime : expeditie : " . $row['expeditie']);
                            }
                        }
                        else {
                            $worksheet->setCellValueExplicit($rangeArr[$i].$rand, $row[$label_key], \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
                        }
                        if($label_key == "valoare_totala_expeditie") {
                            $total_valoare += $row[$label_key];
                        }
                        $i++;
                    }
                    $rand++;
                }
                $rand++;
                $worksheet->setCellValue($rangeArr[0].($rand),"Valoare totala:");
                $worksheet->setCellValue($rangeArr[1].($rand),$total_valoare);
                $worksheet->getStyle($rangeArr[0].$rand)->getFont()->setBold(true);
                $worksheet->getStyle($rangeArr[1].$rand)->getFont()->setBold(true);
                foreach($rangeArr as $v){
                    $worksheet->getStyle($v.'1')->getFont()->setBold(true);
                    $worksheet->getColumnDimension($v)->setAutoSize(true);
                }
            }
        }
        catch (Exception $ex){
            $this->log("Error ExportBorderouXLS : ".$ex);
            //error_log($ex);
        }

        $data = date('d_m_Y');
        $dateTime = date('d_m_Y_H_i_s');
        $filename = 'Expeditii_'.$idfact.'_'.$data.'.xlsx';

        if (file_exists(self::XLS_SAVE_PATH . $filename)) {
            $filename = 'Expeditii_'.$idfact.'_'.$dateTime.'.xlsx';
        }

        try{
            $writer = new Xlsx($spreadsheet);
		    $writer->save(self::XLS_SAVE_PATH . $filename);
        } catch (Exception $ex){
            $this->log($ex);
            return false;
        }

        if($return_file_path){
            return self::XLS_SAVE_PATH . $filename;
        }

        if (!file_exists(self::XLS_SAVE_PATH . $filename)) {
            error_log(self::XLS_SAVE_PATH . $filename . " :  not exists.\n");
            die();
        }

        try {
			$this->download_send_headers_xls($filename);
			$writer = new Xlsx($spreadsheet);
			$writer->save('php://output');
			die();
		}
		catch(Exception $e) {
            error_log(self::XLS_SAVE_PATH . $filename . " :  error opening file.\n");
        }
    }

    function sendInvoice($idfact, $logFilePath = false){
        $saveFolder = self::MAIL_SAVE_PATH."n".date("Ym")."/";
        if(!is_dir($saveFolder)){
            @mkdir($saveFolder, 0777);
        }

        $query = "SELECT * from exp_facturi WHERE id = {$idfact} AND trimisa = 0 AND status >= ".self::FACTURA_FINALIZATA;
        $factura = $this->db->QFetchArray($query);
        if(empty($factura) || empty($factura['cod_cl'])){
            $this->log('Factura|Client not found : factura id : '.$idfact, $logFilePath);
            return false;
        }
        $query = "SELECT * from clienti WHERE cod_cl = ".$factura['cod_cl'];
        $client = $this->db->QFetchArray($query);
        if(empty($client) || empty($client['email_factura'])){
            $this->log('sendInvoice : Email not found : client id : '.$factura['cod_cl'], $logFilePath);
            return false;
        }

        $emailsClient = $client['email_factura'];
        $emailsClient = str_replace(" ","",$emailsClient);
        $emailsClient = str_replace(";",",",$emailsClient);
        if(empty($emailsClient)){
            $this->log('sendInvoice : empty emailsClient : client id : '.$factura['cod_cl'], $logFilePath);
            return false;
        }
        $emailsClientArray = explode(",",$emailsClient);
        $emailsToSent = [];
        foreach ($emailsClientArray as $email){
            $email = trim($email);
            if(filter_var($email, FILTER_VALIDATE_EMAIL)){
                $emailsToSent[] = $email;
            }
            else {
                $this->log('sendInvoice : wrong email address : client id : '.$factura['cod_cl'], $logFilePath);
            }
        }

        //debug
        /*$this->log("debug sendInvoice", $logFilePath);
        $emailsToSent = [];
        $emailsToSent[] = "noc@curierdragonstar.ro";
        */

        if(count($emailsToSent) == 0) {
            $this->log('sendInvoice : empty emailsToSent : client id : '.$factura['cod_cl'], $logFilePath);
            return false;
        }

        if($client['fara_factura'] == 1){
            $this->log('sendInvoice : client fara factura : client id : '.$factura['cod_cl'], $logFilePath);
            return false;
        }

        $idFacturi = array($idfact);

        $perioada = " pentru perioada ".date('d/m/Y',strtotime($factura['interval_start'])). " - ".date('d/m/Y',strtotime($factura['interval_stop']));

        if($factura['tip_factura'] == self::FACTURA_TIP_STORNO){
            $perioada = "";
        }

        $sn = $factura['invoice'];

        $fileName = date("Ymd")."_".$sn;
        $pdfFilePath = $saveFolder.$fileName.".pdf";

        //$this->log("debug sendInvoice before pdf", $logFilePath);

        $this->Pdf($idFacturi, 'F', $pdfFilePath);

        //$this->log("debug sendInvoice after pdf", $logFilePath);

        if(!is_file($pdfFilePath)){
            $this->log('sendInvoice : Eroare adaugare atasament : factura id : '.$idfact, $logFilePath);
            return false;
        }

        $emailFrom = "efactura@info.curierdragonstar.ro";
        $emailConfirmTo = 'efactura@curierdragonstar.ro';
        $emailReplayTo = 'efactura@curierdragonstar.ro';

        $emailsCC = [];
        $emailsBCC = [];
        $emailsBCC[] = 'efactura@curierdragonstar.ro';
        $emailsBCC[] = 'arhiva@curierdragonstar.ro';

        //$this->log("debug sendInvoice before attachement", $logFilePath);

        $filesAttachements = [];
        $filesAttachements[$sn.'.pdf'] = $pdfFilePath;
        if($factura['tip_factura'] == self::FACTURA_TIP_NORMALA) {
            foreach ($idFacturi as $idFactura) {
                if(false === ($borderouXLS = $this->ExportBorderouXLS($idFactura, true))) {
                    $this->log("sendInvoice : error xlsx file : facture id : " . $idFactura, $logFilePath);
                    continue;
                }
                $fisierXLS = $saveFolder . $fileName . ".xlsx";
                if (!copy($borderouXLS, $fisierXLS)) {
                    $this->log("sendInvoice : failed to copy to " . $fisierXLS, $logFilePath);
                    $this->log('sendInvoice : Eroare adaugare atasament', $logFilePath);
                }
                else
                    $filesAttachements[$fileName . ".xlsx"] = $fisierXLS;
            }
        }
        //$filesAttachements["Notificare introducere indice de carburant.pdf"] = "/var/www/download/mails/Notificare introducere indice de carburant.pdf";
        //$this->log("debug sendInvoice after attachement", $logFilePath);

        $emailSubject = 'FACTURA FISCALA DSC Expres Logistic pentru '.$client["nume"].' '.$sn.' / '.date('d.m.Y',strtotime($factura['trndate']));
        $emailBody = str_replace("{serie}", $sn, $this->email_template);
        $emailBody = str_replace("{perioada}", $perioada, $emailBody);

        try {
            if(SendEmailMailGun::send($emailFrom, $emailConfirmTo, $emailReplayTo, $emailsToSent, $emailsCC, $emailsBCC, $filesAttachements, $emailSubject, $emailBody))
            {
                $this->db->QueryUpdate('exp_facturi', ['status' => ($factura['status'] < self::FACTURA_TRIMISA ? self::FACTURA_TRIMISA : $factura['status']), 'trimisa' => ($factura['trimisa'] + 1) ], " id = ".$idfact);
                $this->log('sendInvoice : client : '.$factura['cod_cl'], $logFilePath);
                return true;
            }
        }
        catch(\Exception $ex) {
            $this->log("sendInvoice : {$idfact} : Exception : ".$ex->getMessage(), $logFilePath);
            return false;
        }
        return false;
    }

    /*
    function AsociazaRetururileLaInitiala($cod_cl){
        //asociaza retururile la initiala daca are platitorul diferit : modificare platitor
        $cond = (is_array($cod_cl)) ? "and ep.platitor_id in (".implode(",",$cod_cl).")" : " and ep.platitor_id = ".$cod_cl;
        $query = "select distinct(ep.expeditie) as expeditie
            FROM exp_prelucrate ep
            inner JOIN exp_prelucrate epr on (ep.expeditie = epr.referire and epr.anulata = 0)
            where
            epr.idfact = 0
            and ep.tip_exp = 0
            and ep.mod_plata > 0
            and epr.mod_plata = 0
            and epr.data_expeditie > '".self::RELEASE_DATE."'
            and ep.anulata = 0
            {$cond}
            and epr.platitor_id <> ep.platitor_id";

            $expeditii = $this->db->QFetchRowArray($query);
        if (!empty($expeditii)) {
            foreach($expeditii as $initiala)
                $this->ActualizareRetururi($initiala['expeditie']);
        }
    }
        */

    function sendEmailNewBank($emailsToSent, $logFilePath = false){

        $fileDir = "/var/www/download/mails/";
        $fileName = "1809_DSC_notificare schimbare_cont.pdf";


        $emailsToSent = str_replace(" ","",$emailsToSent);
        $emailsToSent = str_replace(";",",",$emailsToSent);
        $emailsToSentArray = explode(",",$emailsToSent);

        $mail = SendEmailMailGun::send(
            $emailFrom = 'notificari@curierdragonstar.ro',
            $emailConfirmTo = 'notificari@curierdragonstar.ro',
            $emailReplayTo = 'notificari@curierdragonstar.ro',
            $emailsToSent = $emailsToSentArray,
            [],
            [],
            [],
            $emailSubject = 'S.C. DRAGON STAR CURIER S.R.L. : modificare cont bancar',
            $emailBody = '<b>In atentia:</b> Departament Financiar-Contabil <br/>
            <b>Subiect:</b> Schimbare conturi<br/><br/>
            <center><b>ADRESA</b></center><br/><br/>
            Prin prezenta, <b>S.C. DRAGON STAR CURIER S.R.L.</b>, cu sediul in Bucuresti, Sos. Viilor nr.<br/>
            14, corp C18, sector 5, inregistrata la Oficiul National al Registrului Comertului sub nr.<br/>
            J40/15753/2017, Cod Unic de Inregistrare RO16159887, va instiinteaza de schimbarea<br/>
            contulului bancar in care puteti efectua platile, incepand cu data de 25.09.2018.<br/><br/>
            Numarul noului cont este:<br/><br/>
            <center><b>RO13 BTRL RONC RT04 6501 4901</b></center><br/><br/>
            <b>Va rugam sa efectuati platile in noul cont.</b><br/><br/>
            Va multumim,<br/> Departamentul Contabilitate<br/>',
            $filesAttachements = [$fileDir.$fileName]
        );

        if(!$mail[0]) {
            $this->log('New Bank Mailer Error: ' . $mail[1], $logFilePath);
            return false;
        } else {
            $this->log("New Bank Mailer catre ".$emailsToSent, $logFilePath);
            sleep(0.25);
            return true;
        }

    }

    function sendEmailNotificare($client_id, $emailsClient, $emailReplayTo, $emailDebug = false, $logFilePath = false){
        /*
        $fileDir = "/var/www/download/mails/";
        $fileName = "notificare_preturi_rambursuri.pdf";
        $pdfFilePath = $fileDir.$fileName;
        if(!is_file($pdfFilePath)){
            $this->log('sendEmailNotificare : Eroare adaugare atasament : client : '.$client_id, $logFilePath);
            return false;
        }
        */
        $emailsClient = str_replace(" ","",$emailsClient);
        $emailsClient = str_replace(";",",",$emailsClient);
        if(empty($emailsClient)){
            $this->log('sendEmailNotificare : empty emailsClient : client id : '.$client_id, $logFilePath);
            return false;
        }
        $emailsClientArray = explode(",",$emailsClient);
        $emailsToSent = [];
        foreach ($emailsClientArray as $email){
            $email = trim($email);
            if(strlen($email)>2){
                $emailsToSent[] = $email;
            }
        }

        if(count($emailsToSent) == 0) {
            $this->log('sendEmailNotificare : empty emailsToSent : client id : '.$client_id, $logFilePath);
            return false;
        }

        //$emailsBCC = ["notificari@info.curierdragonstar.ro"];
        $emailsBCC = [];
        $emailsCC = [];
        $emailConfirmTo = $emailReplayTo;

        $filesAttachements = [];
        //$filesAttachements[$fileName] = $pdfFilePath;

        $emailSubject = "Informare program de lucru DSC";
        $emailBody = "
<br/>
Stimate Partener,
<br/><br/>
Dorim să vă informăm că joi, 02.05.2024, compania noastră va funcționa conform unui program normal de lucru, realizând preluări și livrări în regim obișnuit. Vă rugăm să luați în considerare acest aspect în planificarea activităților dumneavoastră.
<br/>
<br/>
De asemenea, vă prezentăm programul companiei noastre pentru perioada sărbătorilor:
<br/>
<br/>
01.05.2024, Miercuri – zi liberă<br/>
02.05.2024, Joi – zi lucrătoare (program normal)<br/>
03.05.2024, Vineri – Program de sâmbătă, livrări efectuate între orele 08:00 – 13:00<br/>
04.05.2024, Sâmbătă – zi liberă<br/>
05.05.2024, Duminică – zi liberă<br/>
06.05.2024, Luni – zi liberă<br/>
07.05.2024, Marți – zi lucrătoare (program normal)<br/>
<br/><br/>
Vă mulțumim pentru înțelegere și pentru colaborarea dumneavoastră continuă,
<br/><br/>
Cu stimă,<br/>
Echipa Dragon Star Curier
";

        $emailLog = 'sendEmailNotificare : Mailer Error: ';
        $emailFrom = $emailConfirmTo = $emailReplayTo = "notificari@curierdragonstar.ro";

        $sE = false;
        if($emailDebug) {
            $this->log('sendEmailNotificare : client : '.$client_id, $logFilePath);
            // $sE = $this->sendEmailSend($emailFrom, $emailConfirmTo, $emailReplayTo, $emailsToSent, $emailsCC, $emailsBCC, $filesAttachements, $emailSubject, $emailBody, $emailLog, $logFilePath);
            $sE = SendEmailMailGun::send($emailFrom, $emailConfirmTo, $emailReplayTo, $emailsToSent, $emailsCC, $emailsBCC, $filesAttachements, $emailSubject, $emailBody, $emailDebug);
        }
        else {
            $this->log('sendEmailNotificare : client : '.$client_id, $logFilePath);
            // $sE = $this->sendEmailSend($emailFrom, $emailConfirmTo, $emailReplayTo, $emailsToSent, $emailsCC, $emailsBCC, $filesAttachements, $emailSubject, $emailBody, $emailLog, $logFilePath);
            $sE = SendEmailMailGun::send($emailFrom, $emailConfirmTo, $emailReplayTo, $emailsToSent, $emailsCC, $emailsBCC, $filesAttachements, $emailSubject, $emailBody);
        }

        if($sE) {
            $this->log("Notificare trimisa catre ".$emailsClient, $logFilePath);
            $this->db->QueryUpdate('clienti', ['ff_ok' => 1], " cod_cl = ".$client_id);
            sleep(0.25);
            return true;
        }
        return false;
    }
}