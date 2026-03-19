<?php

use OpenSpout\Writer\CSV\Writer;
use OpenSpout\Writer\CSV\Options;
use OpenSpout\Common\Entity\Row;
use OpenSpout\Common\Entity\Cell;

/**
 * Reports
 *
 */
class ModulReports extends BackEnd {

    public $final_result;
    public $action_module;
    public $page_prefix;
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

        $this->vars['title_page'] = 'Rapoarte';
        $this->page_prefix = 'reports_';

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
        if (!empty($this->user_profile))
        {
            $this->ActionsNivelAcces();   
        } 
        else
            $this->final_result = $this->PageNotFound();
        // R E S U L T
        return $this->final_result;
    }

   
    function ActionsNivelAcces() {
		$this->user_rights = $this->GetDrepturiUtilizator($this->user_profile);
        $arr = $this->GenerateArr();   
		$flag = 0;
		if(in_array("rapoarte_export", $this->user_rights) || $this->user_id == parent::DENISA || $this->user_profile == 10){
			if (!empty($arr[1]) && $arr[1] == 'view')
				$this->final_result = $this->HomeExport(); 
			else if (!empty($arr[1]) && $arr[1] == 'export')
				echo $this->Export();
			$flag=1;
		}
        if(empty($flag))
            $this->final_result = $this->PageNotFound();
    }
    
 //-------------------------------- functii ----------------------------------------
   
/*/////////////////////////////////////////////////////////////
				 Recantariri Xls
/////////////////////////////////////////////////////////////*/
	function HomeExport(){
		$this->vars['title_page'] = 'Rapoarte';
        $vars = [];
        $vars['data_start'] = date('d.m.Y');
        $vars['data_final'] = date('d.m.Y');
        
        return $this->Parse($this->page_prefix . 'view.html', $vars);
	}

	private function reportExpClienti($d1, $d2) {

		$query = "
			SELECT ep.expeditie as 'Nr. NT', ep.data_expeditie as 'Data colectarii', 
			cle.nume as 'Expeditor', IF(cle.zona_id > 0 and clec.id > 0, clec.nume, cee.nume) as 'Centru expeditor',
			cld.nume as 'Destinatar', IF(cld.zona_id > 0 and cldc.id > 0, cldc.nume, ced.nume) as 'Centru destinatar',
			lcd.dist_km as 'Km. livrare', IF(ep.platitor = 1, 'Expeditor', 'Destinatar') as 'Platitor',
			IF(ep.platitor = 1, IF(cle.zona_id > 0 and clec.id > 0, clec.nume, cee.nume), IF(cld.zona_id > 0 and cldc.id > 0, cldc.nume, ced.nume)) as 'Centru platitor',
			CASE ep.tip_obj
					WHEN 1 THEN 'Plic'
					WHEN 2 THEN 'Colet'
					WHEN 3 THEN 'Palet'
					ELSE 'Neidentificat'
			END as 'Tip',
			ep.piese as 'Piese', ep.greutate as 'Greutate',
			ep.borderou_id as 'Borderou ID'
			FROM client_expeditii ep
			LEFT JOIN clienti cle ON ep.expeditor = cle.cod_cl
            LEFT JOIN zones clez ON clez.id = cle.zona_id
            LEFT JOIN centre clec on clec.id = clez.centru_id
            LEFT JOIN localitati lce on lce.cod_lc = cle.cod_lc
            LEFT JOIN centre cee on cee.id = lce.cod_centru
            LEFT JOIN clienti cld ON ep.destinatar_cod_cl = cld.cod_cl
            LEFT JOIN zones cldz ON cldz.id = cld.zona_id
            LEFT JOIN centre cldc on cldc.id = cldz.centru_id
            LEFT JOIN localitati lcd on lcd.cod_lc = cld.cod_lc
            LEFT JOIN centre ced on ced.id = lcd.cod_centru
            LEFT JOIN clienti clp ON IF(ep.platitor = 1, ep.expeditor, ep.destinatar_cod_cl) = clp.cod_cl
			WHERE ep.data_expeditie BETWEEN :d1 AND :d2 and ep.anulata = 0
			GROUP by ep.id
            ORDER BY ep.expeditie ASC
		";
		$this->generateCSVReport($query, "exp-clienti.csv", $d1, $d2);
	}

	private function reportExpRambursuri($d1, $d2, $status = -1) {

		//error_log("debug {$status}");
		//status_ramburs, data_op : operatiune 24 in ist_exp
		$query = "
			SELECT init.expeditie as 'Nr. NT', init.data_expeditie as 'Data colectare',
			init.plicuri as Plicuri,init.colete as Colete,init.paleti as Paleti, init.greutate, 
			cle.nume as Expeditor, IF(cle.zona_id > 0 and clec.id > 0, clec.nume, cee.nume) as 'Centru expeditor', 
			cld.nume as Destinatar, IF(cld.zona_id > 0 and cldc.id > 0, cldc.nume, ced.nume) as 'Centru destinatar',
			init.operatiune as 'Status initiala', 
			scckp.last_ckp_data as 'Data last CKP', ckp.denumire as 'Last CKP', ces.nume as 'Centru last CKP', 
			ag.nume_ag as 'Curier last CKP', us.nume as 'User last CKP',
			scsc.scanari as 'Scanari',
			init.status_ramburs as 'Status ramburs', group_concat(ie.data_op) as 'Data status ramburs',
			rbs.expeditie as 'Nr. NT rbs', rbs.data_expeditie as 'Colectare NT rbs', rbs.operatiune as 'Status NT rbs',
			rtn.expeditie as 'Nr. NT returnare', rtn.data_expeditie as 'Colectare NT returnare', rtn.operatiune as 'Status NT returnare',
			init.ramburs as Valoare, 
			CASE
					WHEN init.tip_plata=0 THEN 'cash'
					WHEN init.tip_plata=1 THEN 'bo'
					WHEN init.tip_plata=2 THEN 'cec'
					WHEN init.tip_plata=3 THEN 'cont'
					ELSE 'unknown'
			END as 'Tip plata', init.val_asig as 'Valoare asigurata',
			init.observatii as Observatii,
			if(init.operatiune like 'Livrat', init.data_op, '') as 'Data COK',
			group_concat(dr.ch_ramburs) as 'Ch. ramburs', group_concat(IF(dr.decont_id > 0, 'DA', 'NU')) as decontat, group_concat(da.data) as 'Data decontare',
            ie2.statusi_ramburs as 'Istoric status RBS', ie2.date_status_ramburs as 'Istoric data status RBS'
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
			LEFT JOIN ( select sc.expeditie, sc.data as last_ckp_data, sc.tip as last_ckp, 
				sc.centru as last_ckp_centru, sc.curier as last_ckp_curier, sc.user as last_ckp_user
				from scanari_coduri sc
				where sc.is_awb = 1 
				and sc.data = (select MAX(scc.data) from scanari_coduri scc where scc.expeditie = sc.expeditie and scc.is_awb = 1)
			) as scckp on scckp.expeditie = init.expeditie
			LEFT JOIN ( select sc.expeditie, COUNT(sc.id) as scanari
				from scanari_coduri sc
				group by sc.expeditie
			) as scsc on scsc.expeditie = init.expeditie
			left join checkpoints ckp on ckp.id = scckp.last_ckp
			left join centre ces on ces.id = scckp.last_ckp_centru
			left join agenti as ag ON scckp.last_ckp_curier = ag.cod_ag
			left join users as us ON scckp.last_ckp_user = us.id
			left join decont_expeditii de on init.expeditie = de.expeditie and de.anulata = 0 and de.operatiune = 2
			left join decont_rbs dr on dr.id = de.ramburs_id and dr.anulata = 0
			left join decont_agent da on da.id = dr.decont_id
			left join ( select iet.cod_exp, iet.data_op, ievt.value from ist_exp iet
				inner join ist_exp_value_int ievt on ievt.cod_ist = iet.cod_ist
				where iet.operatiune = 24
			) as ie on ie.cod_exp = init.cod_expeditie and init.status_ramburs = ie.value
			left join ( select iet2.cod_exp, group_concat(iet2.data_op) as date_status_ramburs, group_concat(ievt2.value) as statusi_ramburs from ist_exp iet2
				inner join ist_exp_value_int ievt2 on ievt2.cod_ist = iet2.cod_ist
				where iet2.operatiune = 24
				group by iet2.cod_exp
			) as ie2 on ie2.cod_exp = init.cod_expeditie";
		$query .= " where init.data_expeditie between :d1 and :d2 and init.tip_exp = 0 and init.anulata = 0
			  and init.ramburs > 0 and init.tip_plata in (0,3)";
		if($status >= 0) $query .= " and init.status_ramburs = {$status}";
		$query .= " group by init.cod_expeditie";

		//$this->log($query, BackEnd::APP_LOG_FILE);
		
		$this->generateCSVReport($query, "exp-rambursuri.csv", $d1, $d2);
	  }
	
	  private function reportExpNelivrate($d1, $d2) {
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
			cle.nume as Expeditor, IF(cle.zona_id > 0 and clec.id > 0, clec.nume, cee.nume) as 'Centru expeditor', 
			cld.nume as Destinatar, IF(cld.zona_id > 0 and cldc.id > 0, cldc.nume, ced.nume) as 'Centru destinatar',
			init.operatiune as 'Status', scckp.last_ckp_data as 'Data last CKP', ckp.denumire as 'Last CKP', 
			ag.nume_ag as 'Curier last CKP', us.nume as 'User last CKP',
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
			LEFT JOIN ( select sc.expeditie, sc.data as last_ckp_data, 
				sc.tip as last_ckp, sc.centru as last_ckp_centru, 
				sc.curier as last_ckp_curier, sc.user as last_ckp_user
				from scanari_coduri sc
				where sc.is_awb = 1 
				and sc.data = (select MAX(scc.data) from scanari_coduri scc where scc.expeditie = sc.expeditie and scc.is_awb = 1)
			) as scckp on scckp.expeditie = init.expeditie
			LEFT JOIN ( select sc.expeditie, COUNT(sc.id) as scanari
				from scanari_coduri sc
				group by sc.expeditie
			) as scsc on scsc.expeditie = init.expeditie
			left join checkpoints ckp on ckp.id = scckp.last_ckp
			left join centre ces on ces.id = scckp.last_ckp_centru
			left join agenti as ag ON scckp.last_ckp_curier = ag.cod_ag
			left join users as us ON scckp.last_ckp_user = us.id
			where init.data_expeditie between :d1 and :d2 and init.anulata = 0 
			and init.operatiune NOT IN ('Livrat', 'Returnat', 'Abandonat', 'Distrus', 'Confiscat', 'Pierdut')
			group by init.cod_expeditie;
		";
		$this->generateCSVReport($query, "exp-nelivrate.csv", $d1, $d2);
	}

	private function reportExpFirstScan($d1, $d2) {
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
			cle.nume as Expeditor, IF(cle.zona_id > 0 and clec.id > 0, clec.nume, cee.nume) as 'Centru expeditor', 
			cld.nume as Destinatar, IF(cld.zona_id > 0 and cldc.id > 0, cldc.nume, ced.nume) as 'Centru destinatar',
			init.operatiune as 'Status', scckp.first_ckp_data as 'Data first CKP', ckp.denumire as 'first CKP', 
			ag.nume_ag as 'Curier first CKP', us.nume as 'User first CKP',
			ces.nume as 'Centru first CKP', scsc.scanari as 'Scanari', init.anulata as Anulata,
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
			LEFT JOIN ( select sc.expeditie, sc.data as first_ckp_data, 
				sc.tip as first_ckp, sc.centru as first_ckp_centru, 
				sc.curier as first_ckp_curier, sc.user as first_ckp_user
				from scanari_coduri sc
				where sc.is_awb = 1 
				and sc.data = (select MIN(scc.data) from scanari_coduri scc where scc.expeditie = sc.expeditie and scc.is_awb = 1)
			) as scckp on scckp.expeditie = init.expeditie
			LEFT JOIN ( select sc.expeditie, COUNT(sc.id) as scanari
				from scanari_coduri sc
				group by sc.expeditie
			) as scsc on scsc.expeditie = init.expeditie
			left join checkpoints ckp on ckp.id = scckp.first_ckp
			left join centre ces on ces.id = scckp.first_ckp_centru
			left join agenti as ag ON scckp.first_ckp_curier = ag.cod_ag
			left join users as us ON scckp.first_ckp_user = us.id
			where init.data_expeditie between :d1 and :d2
			group by init.cod_expeditie;
		";
		$this->generateCSVReport($query, "exp-firstScan.csv", $d1, $d2);
	}

	private function reportExpNedecontate($d1, $d2) {
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
			init.valoare_totala_expeditie as 'Valoare totala expeditie',
			cle.nume as Expeditor, IF(cle.zona_id > 0 and clec.id > 0, clec.nume, cee.nume) as 'Centru expeditor', 
			cld.nume as Destinatar, IF(cld.zona_id > 0 and cldc.id > 0, cldc.nume, ced.nume) as 'Centru destinatar',
			init.operatiune as 'Status', scckp.last_ckp_data as 'Data last CKP', ckp.denumire as 'Last CKP', 
			ag.nume_ag as 'Curier last CKP', us.nume as 'User last CKP',
			ces.nume as 'Centru last CKP', scsc.scanari as 'Scanari',
			init.observatii as Observatii
			FROM exp_prelucrate init
			inner join decont_expeditii de on de.expeditie = init.expeditie
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
			LEFT JOIN ( select sc.expeditie, sc.data as last_ckp_data, sc.tip as last_ckp, 
				sc.curier as last_ckp_curier, sc.user as last_ckp_user, sc.centru as last_ckp_centru
				from scanari_coduri sc
				where sc.is_awb = 1 
				and sc.data = (select MAX(scc.data) from scanari_coduri scc where scc.expeditie = sc.expeditie and scc.is_awb = 1)
			) as scckp on scckp.expeditie = init.expeditie
			LEFT JOIN ( select sc.expeditie, COUNT(sc.id) as scanari
				from scanari_coduri sc
				group by sc.expeditie
			) as scsc on scsc.expeditie = init.expeditie
			left join checkpoints ckp on ckp.id = scckp.last_ckp
			left join centre ces on ces.id = scckp.last_ckp_centru
			left join agenti as ag ON scckp.last_ckp_curier = ag.cod_ag
			left join users as us ON scckp.last_ckp_user = us.id
			where init.data_expeditie between :d1 and :d2 and init.anulata = 0 and de.decont_id = 0 and de.anulata = 0 and (de.factura_id > 0 or de.ramburs_id > 0)
			group by init.cod_expeditie;
		";
		$this->generateCSVReport($query, "exp-nedecontate.csv", $d1, $d2);
	}

	private function reportAnaf395Rbs($d1, $d2) {

		$query = "
			select ep.expeditie as 'Numar document de transport', ep.data_expeditie as 'Data document de transport', 
			ep.ramburs as 'Valoare ramburs',
			cle.nume as 'Denumire/Nume si prenume expeditor', cle.cod_fiscal as 'CIF expeditor', 
			jde.nume_jd as 'Judetul de preluare a trimiterii/oficiului',
			concat(cle.adresa, ', ', lce.nume_lc) as 'Adresa de preluare a trimiterii', '' as 'Adresa oficiu', 
			IF(ep.tip_plata = 3, 'Cont colector', 'Numerar') as 'Modalitate virare ramburs catre expeditor',
			IF(ep.tip_plata = 3, IF(cle.master > 0 and cle.master <> cle.cod_cl, clm.cont_rbs, cle.cont_rbs), '') as 'IBAN cont colector', 
			IF(ep.tip_plata = 0, epr.primitor, '') as 'Nume si prenume persoana fizica care a receptionat rambursul pentru expeditor',
			cld.nume as 'Denumire/Nume si prenume destinatar', '' as 'Rectificare/stergere'
			from exp_prelucrate ep
			inner join exp_prelucrate epr on epr.referire = ep.expeditie and epr.tip_exp = 3 and epr.anulata = 0
			inner join clienti cle on cle.cod_cl = ep.expeditor_id
			left join clienti clm on clm.cod_cl = cle.master
			inner join localitati lce on lce.cod_lc = cle.cod_lc
			inner join judete jde on lce.cod_jd = jde.cod_jd
			inner join clienti cld on cld.cod_cl = ep.destinatar_id
			where ep.tip_exp = 0 and ep.ramburs > 0 and ep.tip_plata in (0,3) and ep.anulata = 0
			and ep.data_expeditie between :d1 and :d2
			and not exists (select * from exp_prelucrate eppr where eppr.tip_exp = 5 and eppr.anulata = 0 and eppr.referire = ep.expeditie)
			GROUP BY ep.expeditie
		";
		if($this->user_profile != 10) $query = false;
		$this->generateCSVReport($query, "395-rbs-anaf.csv", $d1, $d2);
	}

	private function reportIncasariCard($d1, $d2) {

		$query_df = "
            select df.serie as fctChit, df.suma as Valoare,
			dt.transactionId as 'Identificator tranzactie banca', ROUND(dt.amount/100, 2) as 'Suma tranzactie',
			group_concat(de.expeditie) as Expeditii, 
			CASE de.operatiune WHEN 1 THEN 'colectare'  WHEN 2 THEN 'livrare' ELSE '' END as operatiune, 'Transport' as tip, 
			df.data as dataInc, ag.nume_ag as 'agent', ce.label as 'centru', de.client as Client, df.cui as CUI, df.primitor as Primitor
            from decont_facturi df
			inner join decont_transactions dt on dt.id = df.transaction_id
            left join decont_expeditii de on df.id = de.factura_id
			left join agenti ag on ag.cod_ag = df.agent_id
			left join centre ce on ag.cod_centru = ce.id
            where DATE(df.data) between :d1 and :d2
            group by df.id
        ";
        $query_dr = "
            select dr.ch_ramburs as fctChit, dr.ramburs as Valoare,
			dt.transactionId as 'Identificator tranzactie banca', ROUND(dt.amount/100, 2) as 'Suma tranzactie',
			group_concat(de.expeditie) as Expeditii, 
			'livrare' as operatiune, 'Ramburs' as tip, 
			dr.dataInc, ag.nume_ag as 'agent', ce.label as 'centru', '' as Client, '' as CUI, '' as Primitor
            from decont_rbs dr
			inner join decont_transactions dt on dt.id = dr.transaction_id
            left join decont_expeditii de on dr.id = de.ramburs_id
			left join agenti ag on ag.cod_ag = dr.agent_id
			left join centre ce on ag.cod_centru = ce.id
            where DATE(dr.dataInc) between :d1 and :d2
            group by dr.id
        ";
        $query_cf = "
            select cf.ch_bon as fctChit, cf.suma as Valoare,
			dt.transactionId as 'Identificator tranzactie banca', ROUND(dt.amount/100, 2) as 'Suma tranzactie',
			cf.descriere as Expeditii, '' as operatiune, 'Client CTR' as tip, 
			cf.dataInc, ag.nume_ag as 'agent', ce.label as 'centru', '' as Client, '' as CUI, '' as Primitor
            from decont_chitante cf
			inner join decont_transactions dt on dt.id = cf.transaction_id
			left join agenti ag on ag.cod_ag = cf.agent_id
			left join centre ce on ag.cod_centru = ce.id
            where DATE(cf.dataInc) between :d1 and :d2
        ";

        $query = "
            ({$query_df})
            UNION
            ({$query_dr})
            UNION
            ({$query_cf})
			order by agent asc, tip desc, dataInc desc
            ";

		$this->generateCSVReport($query, "incasari-card.csv", $d1, $d2);
	}

	private function reportExpPretImpus($d1, $d2) {
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
			init.valoare_totala_expeditie as 'Valoare totala expeditie',
			cle.nume as Expeditor, IF(cle.zona_id > 0 and clec.id > 0, clec.nume, cee.nume) as 'Centru expeditor', 
			cld.nume as Destinatar, IF(cld.zona_id > 0 and cldc.id > 0, cldc.nume, ced.nume) as 'Centru destinatar',
			init.operatiune as 'Status', scckp.last_ckp_data as 'Data last CKP', ckp.denumire as 'Last CKP', 
			ag.nume_ag as 'Curier last CKP', us.nume as 'User last CKP',
			ces.nume as 'Centru last CKP', scsc.scanari as 'Scanari',
			init.observatii as Observatii, init.anulata as Anulata
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
			LEFT JOIN ( select sc.expeditie, sc.data as last_ckp_data, 
				sc.tip as last_ckp, sc.centru as last_ckp_centru, 
				sc.curier as last_ckp_curier, sc.user as last_ckp_user
				from scanari_coduri sc
				where sc.is_awb = 1 
				and sc.data = (select MAX(scc.data) from scanari_coduri scc where scc.expeditie = sc.expeditie and scc.is_awb = 1)
			) as scckp on scckp.expeditie = init.expeditie
			LEFT JOIN ( select sc.expeditie, COUNT(sc.id) as scanari
				from scanari_coduri sc
				group by sc.expeditie
			) as scsc on scsc.expeditie = init.expeditie
			left join checkpoints ckp on ckp.id = scckp.last_ckp
			left join centre ces on ces.id = scckp.last_ckp_centru
			left join agenti as ag ON scckp.last_ckp_curier = ag.cod_ag
			left join users as us ON scckp.last_ckp_user = us.id
			where init.data_expeditie between :d1 and :d2 and init.pret_impus = 1
			group by init.cod_expeditie;
		";
		$this->generateCSVReport($query, "exp-pret-impus.csv", $d1, $d2);
	}

	public function Export() {
		ini_set('memory_limit', '1228M');
		set_time_limit(600);

		$tip_export = intval($_POST['tip'] ?? 0);
		$status_ramburs = intval($_POST['status_ramburs'] ?? -1);

		if(isset($_POST['data_start']) && isset($_POST['data_final'])){
            $data_start = DateTime::createFromFormat("d.m.Y", $_POST['data_start']);
            $data_final = DateTime::createFromFormat("d.m.Y", $_POST['data_final']);        
			if(!$data_start || !$data_final){
				$data_start = $data_final = new DateTime("now");
			}
			$interval = intval($data_start->diff($data_final, true)->format('%a') ?? 0);
			if($interval > 92) {
				$data_start = $data_final = new DateTime("now");
			}
		}
		else {
			$data_start = $data_final = new DateTime("now");
		}

		match ($tip_export) {
			1 => $this->reportExpRambursuri($data_start->format('Y-m-d'), $data_final->format('Y-m-d'), $status_ramburs),
			2 => $this->reportExpNelivrate($data_start->format('Y-m-d'), $data_final->format('Y-m-d')),
			3 => $this->reportAnaf395Rbs($data_start->format('Y-m-d'), $data_final->format('Y-m-d')),
			4 => $this->reportExpNedecontate($data_start->format('Y-m-d'), $data_final->format('Y-m-d')),
			5 => $this->reportIncasariCard($data_start->format('Y-m-d'), $data_final->format('Y-m-d')),
			6 => $this->reportExpFirstScan($data_start->format('Y-m-d'), $data_final->format('Y-m-d')),
			7 => $this->reportExpClienti($data_start->format('Y-m-d'), $data_final->format('Y-m-d')),
			8 => $this->reportExpPretImpus($data_start->format('Y-m-d'), $data_final->format('Y-m-d')),
			default => exit(),
		};
		exit();
	}

	private function generateCSVReport($query, $fileName, $d1, $d2) {
		//error_log($query);
	
		$options = new Options(
    		SHOULD_ADD_BOM: false,
		);
		$writer = new Writer($options);
		$writer->openToBrowser("raport_{$fileName}");
		$i = 0;
		ob_start();
		if($query === false) {
			$writer->addRow(Row::fromValues(["not allowed"]));
			ob_end_flush();
			$writer->close();
			exit();
		}
		$sql = $this->db->Query($query, ['d1' => $d1, 'd2' => $d2], false);
		if($sql) {
			if($row = $sql->fetch(PDO::FETCH_ASSOC)) {
				$row_header = Row::fromValues(array_keys($row));
				$writer->addRow($row_header);
				$row_values = Row::fromValues(array_values($row));
				$writer->addRow($row_values);
			}
			while($row = $sql->fetch(PDO::FETCH_ASSOC)) {
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
}