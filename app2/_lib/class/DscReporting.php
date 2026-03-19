<?php
require_once 'SendEmailMailGun.php';


class DscReporting {
  private $db;

  const TMP_FOLDER = "/var/www/logs/app/_tmp/";

  function __construct($db) {
    $this->db = $db;
  }

  public function sendReportExpBuc() {
    $header = ['nrCodBare', 'nume_lc', 'nume_jd', 'adresa'];
    $today = new DateTime("now");
    $today->modify('-1 month');
    $todayMinusOneMonth = $today->format("Y-m-d");

    $query = "
        select ep.expeditie as nrCodBare, lcd.nume_lc, jd.nume_jd, cld.adresa
        from exp_prelucrate ep
        left join clienti cld on cld.cod_cl = ep.destinatar_id
        left join localitati lcd on lcd.cod_lc = cld.cod_lc
        LEFT JOIN zones cldz ON cldz.id = cld.zona_id
        LEFT JOIN centre cldc on cldc.id = cldz.centru_id
        left join judete jd on jd.cod_jd = lcd.cod_jd
        where IF(cld.zona_id > 0 and cldc.id > 0, cldc.id, lcd.cod_centru) in (47,91) and ep.data_expeditie >= '$todayMinusOneMonth'
    ";
    $fileName = "exp-buc.csv";
    $this->generateCSVReport($query, $header, $fileName);

    $emailFrom = 'notificari@info.curierdragonstar.ro';
    $emailConfirmTo = 'notificari@info.curierdragonstar.ro';
    $emailReplayTo = 'notificari@info.curierdragonstar.ro';
    $emailsToSent = ['marius.teler@gmail.com','postmaster@info.curierdragonstar.ro', 'marius.teler@curierdragonstar.ro'];
    $emailsCC = [];
    $emailsBCC = [];
    $filesAttachements = [
        'exp-buc.csv' => self::TMP_FOLDER . $fileName,
    ];
    $emailSubject = 'Expeditii Bucuresti - Daily';
    $emailBody = 'Raportul este atasat in atasamente.';

    return SendEmailMailGun::send(
        $emailFrom,
        $emailConfirmTo,
        $emailReplayTo,
        $emailsToSent,
        $emailsCC,
        $emailsBCC,
        $filesAttachements,
        $emailSubject,
        $emailBody
    );
  }

  public function sendReportExpOperational() {
    $header = ['Nr. Crt', 'Nr. NT', 'Data colectare', 'Tip Expediere', 'Tipul expeditiei', 'Piese', 'Greutate', 'Expeditor', 'Centru expeditor', 'Destinatar', 'Centru destinatar', 'User curent', 'Status', 'Last Ckp', 'Data Ckp', 'Scanari', 'Valoare', 'Tip plata', 'Valoare asigurata'];
    $query = "
        SELECT
        nt.cod_expeditie as 'Nr. Crt',
        nt.expeditie as 'Nr. NT',
        nt.data_expeditie as 'Data colectare',
        CASE
          WHEN nt.tip_exp = 0 THEN 'Initiala'
          WHEN nt.tip_exp = 1 THEN 'Retur NT'
          WHEN nt.tip_exp = 2 THEN 'Retur Doc'
          WHEN nt.tip_exp = 3 THEN 'Ramburs'
          WHEN nt.tip_exp = 4 THEN 'Interna'
          WHEN nt.tip_exp = 5 THEN 'Returnare'
          WHEN nt.tip_exp = 6 THEN 'Retur ambalaj'
          WHEN nt.tip_exp = 7 THEN 'Retur colet'
          ELSE 'Neidentificat'
        END AS 'Tip Expediere',
        CASE
          WHEN nt.plicuri > 0 THEN 'Plic'
          WHEN nt.colete > 0 THEN 'Colet'
          WHEN nt.paleti > 0 THEN 'Palet'
          ELSE 'Nespecificat'
        END AS 'Tipul expeditiei',
        nt.colete as 'Piese',
        nt.greutate as 'Greutate',
        cle.nume as 'Expeditor',
        IF(cle.zona_id > 0 and clec.id > 0, clec.nume, cee.nume) as 'Centru expeditor',
        cld.nume as 'Destinatar',
        IF(cld.zona_id > 0 and cldc.id > 0, cldc.nume, ced.nume) as 'Centru destinatar',
        u.nume as 'User curent',
        nt.operatiune as 'Status',
        CASE
          WHEN scckp.id IS NOT NULL THEN ckp.denumire
          WHEN scckp.id IS NULL THEN 'Nu exista scanare'
          ELSE 'Status invalid'
        END AS 'Last Ckp',
        CASE
          WHEN scckp.id IS NOT NULL THEN scckp.last_ckp_data
          WHEN scckp.id IS NULL THEN 'Nu exista scanare'
          ELSE 'Status invalid'
        END AS 'Data Ckp',
        CASE
          WHEN scckp.id IS NOT NULL THEN 'Da'
          WHEN scckp.id IS NULL THEN 'Nu'
          ELSE 'Nu'
        END AS 'Scanari',
        nt.ramburs as 'Valoare',
        CASE
          WHEN nt.tip_plata = 0 THEN 'Cash'
          WHEN nt.tip_plata = 1 THEN 'BO'
          WHEN nt.tip_plata = 2 THEN 'CEC'
          WHEN nt.tip_plata = 3 THEN 'Cont'
          ELSE 'Neidentificat'
        END AS 'Tip plata',
        nt.val_asig as 'Valoare asigurata'
        FROM `exp_prelucrate` nt
        LEFT JOIN `clienti` cle ON nt.expeditor_id = cle.cod_cl
        LEFT JOIN `localitati` lce ON cle.cod_lc = lce.cod_lc
        LEFT JOIN `centre` cee on cee.id = lce.cod_centru
        LEFT JOIN `clienti` cld ON nt.destinatar_id = cld.cod_cl
        LEFT JOIN `localitati` lcd ON cld.cod_lc = lcd.cod_lc
        LEFT JOIN `centre` ced on ced.id = lcd.cod_centru
        LEFT JOIN zones clez ON clez.id = cle.zona_id
        LEFT JOIN centre clec on clec.id = clez.centru_id
        LEFT JOIN zones cldz ON cldz.id = cld.zona_id
        LEFT JOIN centre cldc on cldc.id = cldz.centru_id
        LEFT JOIN users u ON u.id = nt.operator_id
        LEFT JOIN ( select sc.expeditie, sc.id, sc.data as last_ckp_data, sc.tip
          from scanari_coduri sc
          where sc.is_awb = 1 and sc.data = 
          (select MAX(scc.data) from scanari_coduri scc where scc.expeditie = sc.expeditie and scc.is_awb = 1)
        ) as scckp on scckp.expeditie = nt.expeditie
        left join checkpoints ckp on ckp.id = scckp.tip
        WHERE nt.operatiune NOT LIKE '%Livrat%' AND nt.operatiune NOT LIKE '%returnat%' AND nt.data_expeditie > DATE_SUB(NOW(), INTERVAL 90 DAY)
        group by nt.cod_expeditie;
    ";
    $fileName = "exp-op.csv";
    $this->generateCSVReport($query, $header, $fileName);

    $emailFrom = 'notificari@info.curierdragonstar.ro';
    $emailConfirmTo = 'notificari@info.curierdragonstar.ro';
    $emailReplayTo = 'notificari@info.curierdragonstar.ro';
    $emailsToSent = ['marius.teler@gmail.com','postmaster@info.curierdragonstar.ro', 'marius.teler@curierdragonstar.ro'];
    $emailsCC = [];
    $emailsBCC = [];
    $filesAttachements = [
        'exp-op.csv' => self::TMP_FOLDER . $fileName,
    ];
    $emailSubject = 'Expeditii Operational - Daily';
    $emailBody = 'Raportul este atasat in atasamente.';

    return SendEmailMailGun::send(
      $emailFrom,
      $emailConfirmTo,
      $emailReplayTo,
      $emailsToSent,
      $emailsCC,
      $emailsBCC,
      $filesAttachements,
      $emailSubject,
      $emailBody
    );
  }

  public function sendReportExpRambursuri() {

    $query = "
        SELECT init.expeditie as 'Nr. NT', init.data_expeditie as 'Data colectare',
        init.plicuri as Plicuri,init.colete as Colete,init.paleti as Paleti, init.greutate, 
        cle.nume as Expeditor, cld.nume as Destinatar, 
        IF(cle.zona_id > 0 and clec.id > 0, clec.nume, cee.nume) as 'Centru expeditor', 
        IF(cld.zona_id > 0 and cldc.id > 0, cldc.nume, ced.nume) as 'Centru destinatar',
        init.operatiune as 'Status initiala', 
        scckp.last_ckp_data as 'Data last CKP', ckp.denumire as 'Last CKP', ces.nume as 'Centru last CKP', scsc.scanari as 'Scanari',
        init.status_ramburs as 'Status ramburs',
        rbs.expeditie as 'Nr. NT rbs', rbs.data_expeditie as 'Colectare NT rbs', rbs.operatiune as 'Status rbs',
        rtn.expeditie as 'Nr. NT returnare', rtn.data_expeditie as 'Colectare NT returnare', rtn.operatiune as 'Status returnare',
        init.ramburs as Valoare, 
        CASE
                WHEN init.tip_plata=0 THEN 'cash'
                WHEN init.tip_plata=1 THEN 'bo'
                WHEN init.tip_plata=2 THEN 'cec'
                WHEN init.tip_plata=3 THEN 'cont'
                ELSE 'unknown'
        END as 'Tip plata', init.val_asig as 'Valoare asigurata',
        init.observatii as Observatii,
        if(init.operatiune like 'Livrat', init.data_op, '') as 'Data COK'
        FROM exp_prelucrate init
        left join clienti cle on cle.cod_cl = init.expeditor_id
        left join clienti cld on cld.cod_cl = init.destinatar_id
        left join localitati lce ON lce.cod_lc = cle.cod_lc
        left join localitati lcd ON lcd.cod_lc = cld.cod_lc
        left join centre cee ON cee.id = lce.cod_centru
        left join centre ced ON ced.id = lcd.cod_centru
        LEFT JOIN zones clez ON clez.id = cle.zona_id
        LEFT JOIN centre clec on clec.id = clez.centru_id
        LEFT JOIN zones cldz ON cldz.id = cld.zona_id
        LEFT JOIN centre cldc on cldc.id = cldz.centru_id
        LEFT JOIN exp_prelucrate rbs
          ON rbs.referire = init.expeditie AND rbs.tip_exp = 3 and rbs.anulata = 0
        LEFT JOIN exp_prelucrate rtn
          ON rtn.referire = init.expeditie AND rtn.tip_exp = 5 and rtn.anulata = 0
        LEFT JOIN ( select sc.expeditie, sc.data as last_ckp_data, sc.tip as last_ckp, sc.centru as last_ckp_centru
            from scanari_coduri sc
            where sc.is_awb = 1 and sc.data = (select MAX(scc.data) from scanari_coduri scc where scc.expeditie = sc.expeditie and scc.is_awb = 1)
        ) as scckp on scckp.expeditie = init.expeditie
        LEFT JOIN ( select sc.expeditie, COUNT(sc.id) as scanari
            from scanari_coduri sc
            group by sc.expeditie
        ) as scsc on scsc.expeditie = init.expeditie
        left join checkpoints ckp on ckp.id = scckp.last_ckp
        left join centre ces on ces.id = scckp.last_ckp_centru
        where init.data_expeditie >= DATE_SUB(NOW(), INTERVAL 3 MONTH) and init.tip_exp = 0 and init.anulata = 0 
          and init.ramburs > 0 and init.tip_plata in (0,3) and init.status_ramburs = 0
        group by init.expeditie;
    ";
    $fileName = "exp-rambursuri.csv";
    $this->generateCSVReport($query, [], $fileName);

    $emailFrom = 'notificari@info.curierdragonstar.ro';
    $emailConfirmTo = 'notificari@info.curierdragonstar.ro';
    $emailReplayTo = 'notificari@info.curierdragonstar.ro';
    $emailsToSent = ['marius.teler@gmail.com','marius.teler@curierdragonstar.ro'];
    $emailsCC = [];
    $emailsBCC = [];
    $filesAttachements = [
        'exp-rambursuri.csv' => self::TMP_FOLDER . $fileName,
    ];
    $emailSubject = 'Raport pentru Rambursuri';
    $emailBody = 'Raportul este atasat in atasamente.';

    return SendEmailMailGun::send(
      $emailFrom,
      $emailConfirmTo,
      $emailReplayTo,
      $emailsToSent,
      $emailsCC,
      $emailsBCC,
      $filesAttachements,
      $emailSubject,
      $emailBody
    );
  }

  private function generateCSVReport($query, $header,  $fileName) {
    $results = $this->db->query($query)->fetchAll(PDO::FETCH_ASSOC);

    if(empty($header) || !is_array($header) || count($header) == 0) {
      $header = array_keys($results[0] ?? []);
    }

    $file = fopen(self::TMP_FOLDER . $fileName, 'w');
    fputcsv($file, $header);
    foreach ($results as $result) {
        fputcsv($file, array_values($result));
    }
    fclose($file);
    return $fileName;
  }

  public function sendReportExpNelivrate() {
    $query = "
        SELECT init.expeditie as 'Nr. NT', init.data_expeditie as 'Data colectare',
        CASE
                WHEN init.tip_exp=0 THEN 'Initiala'
                WHEN init.tip_exp=1 THEN 'Retur NT'
                WHEN init.tip_exp=2 THEN 'Retur Doc'
                WHEN init.tip_exp=3 THEN 'Ramburs'
                WHEN init.tip_exp=4 THEN 'Interna'
                WHEN init.tip_exp=5 THEN 'Returnare'
                WHEN init.tip_exp=6 THEN 'Retur ambalaj'
                WHEN init.tip_exp=7 THEN 'Retur colet'
                ELSE 'Neidentificat'
        END AS 'Tip Expediere',
        init.plicuri as Plicuri,init.colete as Colete,init.paleti as Paleti, init.greutate,
        init.ramburs as 'Valoare ramburs', 
        CASE
                WHEN init.tip_plata=0 THEN 'cash'
                WHEN init.tip_plata=1 THEN 'bo'
                WHEN init.tip_plata=2 THEN 'cec'
                WHEN init.tip_plata=3 THEN 'cont'
                ELSE 'unknown'
        END as 'Tip plata',
        init.val_asig as 'Valoare asigurata',
        cle.nume as Expeditor, cld.nume as Destinatar,
        IF(cle.zona_id > 0 and clec.id > 0, clec.nume, cee.nume) as 'Centru expeditor', 
        IF(cld.zona_id > 0 and cldc.id > 0, cldc.nume, ced.nume) as 'Centru destinatar',
        init.operatiune as 'Status', scckp.last_ckp_data as 'Data last CKP', ckp.denumire as 'Last CKP', 
        ces.nume as 'Centru last CKP', scsc.scanari as 'Scanari',
        init.observatii as Observatii
        FROM exp_prelucrate init
        left join clienti cle on cle.cod_cl = init.expeditor_id
        left join clienti cld on cld.cod_cl = init.destinatar_id
        left join localitati lce ON lce.cod_lc = cle.cod_lc
        left join localitati lcd ON lcd.cod_lc = cld.cod_lc
        left join centre cee ON cee.id = lce.cod_centru
        left join centre ced ON ced.id = lcd.cod_centru
        LEFT JOIN zones clez ON clez.id = cle.zona_id
        LEFT JOIN centre clec on clec.id = clez.centru_id
        LEFT JOIN zones cldz ON cldz.id = cld.zona_id
        LEFT JOIN centre cldc on cldc.id = cldz.centru_id
        LEFT JOIN ( select sc.expeditie, sc.data as last_ckp_data, sc.tip as last_ckp, sc.centru as last_ckp_centru
            from scanari_coduri sc
            where sc.is_awb = 1 and sc.data = (select MAX(scc.data) from scanari_coduri scc where scc.expeditie = sc.expeditie and scc.is_awb = 1)
        ) as scckp on scckp.expeditie = init.expeditie
        LEFT JOIN ( select sc.expeditie, COUNT(sc.id) as scanari
            from scanari_coduri sc
            group by sc.expeditie
        ) as scsc on scsc.expeditie = init.expeditie
        left join checkpoints ckp on ckp.id = scckp.last_ckp
        left join centre ces on ces.id = scckp.last_ckp_centru
        where init.data_expeditie >= DATE_SUB(NOW(), INTERVAL 3 MONTH) and init.anulata = 0 
        and init.operatiune NOT IN ('Livrat', 'Returnat', 'Abandonat', 'Distrus', 'Confiscat', 'Pierdut')
        group by init.expeditie;
    ";
    $fileName = "exp-nelivrate.csv";
    $this->generateCSVReport($query, [], $fileName);

    $emailFrom = 'notificari@info.curierdragonstar.ro';
    $emailConfirmTo = 'notificari@info.curierdragonstar.ro';
    $emailReplayTo = 'notificari@info.curierdragonstar.ro';
    $emailsToSent = ['marius.teler@gmail.com','marius.teler@curierdragonstar.ro'];
    $emailsCC = [];
    $emailsBCC = [];
    $filesAttachements = [
        'exp-nelivrate.csv' => self::TMP_FOLDER . $fileName,
    ];
    $emailSubject = 'Raport pentru Nelivrate';
    $emailBody = 'Raportul este atasat in atasamente.';

    return SendEmailMailGun::send(
      $emailFrom,
      $emailConfirmTo,
      $emailReplayTo,
      $emailsToSent,
      $emailsCC,
      $emailsBCC,
      $filesAttachements,
      $emailSubject,
      $emailBody
    );
  }
}