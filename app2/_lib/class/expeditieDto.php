<?php
require_once "Cnp.php";

class ExpeditieDto {
    const KM_LIMIT = 15;
    const TIP_EXP = [ 0=>'Initiala', 1=>'Retur NT', 2=>'Retur Doc', 3=>'Ramburs',4=>'Interna',5=>'Returnare',6=>'Retur ambalaj', 7=>'Retur colet', 33=>'Borderou RBS cash'];
    const TIP_OBJ = [1 => 'PLIC', 2 => 'COLET', 3 => 'PALET'];
    const MOD_PLATA = [ 0=>'Per NT', 1=>'Factura periodica', 3=>'barter'];
    const RBS_TIP_PLATA = [ 0=>'cash', 1=>'bo', 2=>'cec', 3=>'cont'];
    const CONTRACT = [0 => 'Nu', 1 => 'T. negociat', 2 => 'T. lista'];
    const TIP_TARIF_DET = [0 => 'Local', 1 => 'National'];
    const MONEDA = [1=>'LEI' ,2=>'EUR' ,3=>'USD'];
    const MONEDA_REV = ['LEI' => 1 ,'EUR' => 2 , 'USD' => 3];
    const DEFAULT_KG_RET_AMB = 3.00;
    const DEFAULT_TARIF_OPEN = 20.00;
    const PROC_TVA = 19;

    public static function sqlExpToUi($sqlExp, $profile, $user_rights = []){
		if($sqlExp === false || !is_array($sqlExp) || count($sqlExp) == 0) return [];
        $ret=[];

        $ret['NR_EXP'] = $sqlExp['expeditie'];
        $ret['EXPEDITOR'] = $sqlExp['expeditor_id'];
        $ret['EXPEDITOR_NUME'] = htmlspecialchars($sqlExp['expeditor_nume'], ENT_QUOTES);
        $ret['EXPEDITOR_LOCALITATE'] = $sqlExp['expeditor_localitate_id'];
        $ret['EXPEDITOR_LOCALITATE_NUME'] = "{$sqlExp['expeditor_localitate']} ({$sqlExp['expeditor_centru']})";
        $ret['EXPEDITOR_LOCALITATE_KM'] = intval(round($sqlExp['expeditor_localitate_km']));
        $ret['EXPEDITOR_CC'] = $sqlExp['expeditor_cc'];
        $ret['EXPEDITOR_CONTRACT'] = $sqlExp['expeditor_contract'];
        $ret['EXPEDITOR_MOD_PLATA'] = $sqlExp['expeditor_mod_plata'];
	    if($sqlExp['tip_plata'] == 3 && $sqlExp['tip_exp'] == 3)
        	$ret['EXPEDITOR_CC'] = $sqlExp['destinatar_cc'];

        $ret['EXPEDITOR_ADRESA'] = $ret['EXPEDITOR_TELEFON'] = $ret['EXPEDITOR_CONTACT'] = ' ';
        if(!empty($sqlExp['expeditor_adresa']))
            $ret['EXPEDITOR_ADRESA'] = $sqlExp['expeditor_adresa'];
        if(!empty($sqlExp['expeditor_contact']))
            $ret['EXPEDITOR_CONTACT'] = htmlspecialchars($sqlExp['expeditor_contact'], ENT_QUOTES);
        if(!empty($sqlExp['expeditor_telefon']))
            $ret['EXPEDITOR_TELEFON'] = $sqlExp['expeditor_telefon'];

        $ret['DESTINATAR'] = $sqlExp['destinatar_id'];
        $ret['DESTINATAR_NUME'] = htmlspecialchars($sqlExp['destinatar_nume'], ENT_QUOTES);
        $ret['DESTINATAR_LOCALITATE'] = $sqlExp['destinatar_localitate_id'];
        $ret['DESTINATAR_LOCALITATE_NUME'] = $sqlExp['destinatar_localitate'] . " (" . $sqlExp['destinatar_centru'] . ")";
        $ret['DESTINATAR_LOCALITATE_KM'] = intval(round($sqlExp['destinatar_localitate_km']));
        $ret['DESTINATAR_CC'] = $sqlExp['destinatar_cc'];
        $ret['DESTINATAR_CONTRACT'] = $sqlExp['destinatar_contract'];
        $ret['DESTINATAR_MOD_PLATA'] = $sqlExp['destinatar_mod_plata'];
        $ret['DESTINATAR_CENTRU'] = $sqlExp['destinatar_centru'];

        $ret['DESTINATAR_TELEFON'] = $ret['DESTINATAR_ADRESA'] = $ret['DESTINATAR_CONTACT'] = '';
        if(!empty($sqlExp['destinatar_adresa']))
            $ret['DESTINATAR_ADRESA'] = $sqlExp['destinatar_adresa'];
        if(!empty($sqlExp['destinatar_contact']))
            $ret['DESTINATAR_CONTACT'] = htmlspecialchars($sqlExp['destinatar_contact'], ENT_QUOTES);
        if(!empty($sqlExp['destinatar_telefon']))
            $ret['DESTINATAR_TELEFON'] = $sqlExp['destinatar_telefon'];

        $ret['PLATITOR'] = $sqlExp['platitor_id'];
        $ret['PLATITOR_NUME'] = htmlspecialchars($sqlExp['platitor_nume'], ENT_QUOTES);
        $ret['PLATITOR_LOCALITATE'] = $sqlExp['platitor_localitate'];
        $ret['PLATITOR_CENTRU'] = $sqlExp['platitor_centru'];
        $ret['PLATITOR_CC'] = $sqlExp['platitor_cc'];
        $ret['PLATITOR_MOD_PLATA'] = $sqlExp['platitor_mod_plata'];
        $ret['PLATESTE'] = $ret['PLATESTE1'] = $ret['PLATESTE2'] = $ret['PLATESTE3'] = '';
        if($sqlExp['platitor_id'] == $sqlExp['expeditor_id']) {
            $ret['PLATESTE1'] = 'checked';
            $ret['PLATESTE'] = 1;
        }
        else if($sqlExp['platitor_id'] == $sqlExp['destinatar_id']) {
            $ret['PLATESTE2'] = 'checked';
            $ret['PLATESTE'] = 2;
        }
        else {
            $ret['PLATESTE3'] = 'checked';
            $ret['PLATESTE'] = 3;
        }
        $ret['MOD_PLATA_'.$sqlExp['platitor_mod_plata']] = 'selected';

        $ret['PRIMITOR'] = $sqlExp['primitor'];

        $ret['TIP_OBJ_1'] = ($sqlExp['tip_obj'] == 1) ? 'checked' : '';
        $ret['TIP_OBJ_2'] = ($sqlExp['tip_obj'] == 2) ? intval($sqlExp['piese']) : 0;
        $ret['TIP_OBJ_3'] = ($sqlExp['tip_obj'] == 3) ? 'checked' : '';

        $ret['GREUTATE'] = round($sqlExp['greutate'], 3);
        $ret['GREUTATE_VOL'] = 0.000;
        $ret['VOLUM'] = empty($sqlExp['volum']) ? '' : $sqlExp['volum'];
        if(!empty($ret['VOLUM'])) {
            $volumes = explode("x",$ret['VOLUM']);
            if(count($volumes) == 3) {
                list($ret['VOLUM1'], $ret['VOLUM2'], $ret['VOLUM3']) = $volumes;
                if(intval($ret['VOLUM1']) > 0 && intval($ret['VOLUM2']) > 0 && intval($ret['VOLUM3']) > 0) {
                    $ret['GREUTATE_VOL'] = self::getGreutateVolumetrica($ret['VOLUM1'], $ret['VOLUM2'], $ret['VOLUM3']);
                }
            }
        }

        if($profile != 10 && $sqlExp['mod_plata'] > 0 && (!is_array($user_rights) || !in_array('preturi', $user_rights)))  {
            $ret['TEXP'] = $ret['TKG'] = $ret['TKM'] = $ret['TASIG'] = $ret['TRAMB'] = $ret['HT'] = $ret['TVA'] = $ret['TTC'] = $ret['MONEDA'] = 'NaN';
        }
        else {
            $ret['TEXP'] = number_format(round($sqlExp['valoare_expeditie'],2), 2, '.', '');
            $ret['TKG'] = number_format(round($sqlExp['val_greutate'],2), 2, '.', '');
            $ret['TKM'] = number_format(round($sqlExp['val_km'],2), 2, '.', '');
            $ret['TASIG'] = number_format(round($sqlExp['val_asig'],2), 2, '.', '');
            $ret['TRAMB'] = number_format(0.00, 2, '.', '');
            $ret['HT'] = round(round($sqlExp['valoare_expeditie'],2) + round($sqlExp['val_km'],2) + round($sqlExp['val_greutate'],2) + round($sqlExp['val_asig'],2), 2);
            $ret['TVA'] = round(((empty($sqlExp['tva'])) ? $ret['HT'] * $sqlExp['procTva']/100 : $sqlExp['tva']),2);
        	$ret['TTC'] = number_format(round(($ret['HT'] + $ret['TVA']), 2), 2, '.', '');
            $ret['HT'] = number_format($ret['HT'], 2, '.', '');
            $ret['TVA'] = number_format($ret['TVA'], 2);
        	$ret['MONEDA'] = "LEI";
        }

        $ret['RAMBURS'] = number_format(round($sqlExp['ramburs'], 2), 2, '.', '');
        $ret['TIP_PLATA'] = ($sqlExp['ramburs'] > 0 || ($sqlExp['valoare_asigurata'] > 0 && $sqlExp['tip_exp'] == 3)) ? self::RBS_TIP_PLATA[$sqlExp['tip_plata']] ?? 0 : '';
        $ret['cash'] = ($sqlExp['tip_plata'] == 0) ? " selected" : "";
        $ret['bo'] = ($sqlExp['tip_plata'] == 1) ? " selected" : "";
        $ret['cec'] = ($sqlExp['tip_plata'] == 2) ? " selected" : "";
        $ret['cont'] = ($sqlExp['tip_plata'] == 3) ? " selected" : "";

        $ret['KM_EXT_PREL'] = ceil($sqlExp['expeditor_localitate_km']);
        $ret['KM_EXT_LIV'] = ceil($sqlExp['destinatar_localitate_km']);

		$ret['tip_tarif'] = ($sqlExp['expeditor_localitate_id'] == $sqlExp['destinatar_localitate_id']) ? 0 : 1 ;

		$ret['PROC_ASIG'] = $sqlExp['procent_asigurare'];
        $ret['ASIGURARE'] = number_format(round($sqlExp['valoare_asigurata'], 2), 2, '.', '');

        $ret['MONEDA_KM'] = "LEI";
        $ret['AG_PREL'] = $sqlExp['curier_preluare_id'];
        $ret['AG_PREL_NUME'] = $sqlExp['curier_preluare'];

        $ret['AG_LIV'] = $sqlExp['curier_livrare_id'];
        $ret['AG_LIV_NUME'] = $sqlExp['curier_livrare'];

		$ret['DATA_PREL'] = self::sqlDateToUi($sqlExp['data_expeditie']);
        $ret['DATA_LIV']  = self::sqlDateToUi($sqlExp['data_op']);

        if($sqlExp['operatiune'] == 'Livrat'){
            $ret['LIVRAT'] = 'checked';
        }

        $ret['detalii_doc'] = htmlspecialchars($sqlExp['detalii_doc'], ENT_QUOTES);
        $ret['OBSERVATII'] = htmlspecialchars($sqlExp['observatii'], ENT_QUOTES);
        $ret['MOD_PLATA'] = $sqlExp['mod_plata'];

        $factura_decont = empty($sqlExp['factura_id']) ? "" : $sqlExp['serie'];
        $factura_wme = $sqlExp['idfact'] > 0 ? $sqlExp['invoice'] : "";

        $factura_decont_suma = empty($sqlExp['factura_id']) ? "" : $sqlExp['factura_suma'];
        $factura_wme_suma = $sqlExp['idfact'] > 0 ? $sqlExp['sumamnt'] : "";

        $ret['INVOICE'] = $sqlExp['idfact'] > 0 ? "facturata cu : {$factura_wme}" : (!empty($factura_decont) ? (empty($sqlExp['decontata']) ? "decontata cu : {$factura_decont}" : "decontata cu : {$factura_decont}") : "");
        $ret['SUMAMNT'] = $sqlExp['idfact'] > 0 ? $factura_wme_suma : $factura_decont_suma;


        $ret['RET_NT'] = (!empty($sqlExp['ret_nt'])) ? 'checked' : '';
        $ret['RET_DOC'] = (!empty($sqlExp['ret_doc'])) ? 'checked' : '';
        $ret['LIV_S'] = (!empty($sqlExp['liv_samb'])) ? 'checked' : '';
        $ret['RET_AMB'] = (!empty($sqlExp['ret_amb'])) ? 'checked' : '';
        $ret['RET_COLET'] = (!empty($sqlExp['ret_colet'])) ? 'checked' : '';
        $ret['LIV_SEDIU'] = (!empty($sqlExp['liv_sed'])) ? 'checked' : '';
        $ret['SMS'] = (!empty($sqlExp['sms'])) ? 'checked' : '';
        $ret['COPEN'] = (!empty($sqlExp['copen'])) ? 'checked' : '';
        $ret['TIP_EXP_'.$sqlExp['tip_exp']] = 'selected';

        //error_log('s'.$sqlExp['pret_impus'].'t'.round($sqlExp['valoare_totala_expeditie'], 2));
        $ret['PRET_IMPUS'] = ($sqlExp['pret_impus'] > 0 && in_array($sqlExp['tip_exp'], [0,5])) ? number_format(round($sqlExp['valoare_totala_expeditie'], 2), 2, '.', '') : '';

        $ret['REFERINTA_EXPEDITIE'] = empty($sqlExp['referire']) ? '' : $sqlExp['referire'];

        $ret['CODURI'] = implode(' ', self::getPuisoriForAwb($sqlExp['expeditie'], $sqlExp['piese']));

        //var_dump($ret);
        return $ret;
    }

    public static function sqlExpClientToUi($sqlExp, $expeditor_id, $mod_plata, $ret_amb, $ret_colet, $tarif_sms, $print_awb){
		if($sqlExp === false || !is_array($sqlExp) || count($sqlExp) == 0) return [];

        if(!empty($sqlExp['updated_by']))
			$sqlExp['updated_at'] = self::sqlDateToUi($sqlExp['updated_at'], true, false);
		else $sqlExp['updated_at'] = '';
		if(!empty($sqlExp['printed_by']))
			$sqlExp['printed_at'] = self::sqlDateToUi($sqlExp['updated_at'], true, false);
		else $sqlExp['printed_at'] = '';

        //$sqlExp['destinatar_nume'] = htmlspecialchars($sqlExp['destinatar_nume'], ENT_QUOTES);
        //$sqlExp['destinatar_contact'] = htmlspecialchars($sqlExp['destinatar_contact'], ENT_QUOTES);

	  	$sqlExp['TIP_OBJ_1'] = 0;
		$sqlExp['TIP_OBJ_2'] = 0;
		$sqlExp['TIP_OBJ_3'] = 0;
	  	$sqlExp['TIP_OBJ_'.$sqlExp['tip_obj']] = $sqlExp['piese'];
		$sqlExp['greutate'] = number_format($sqlExp['greutate'], 3, '.', '').' Kg';
        $volumes = explode("x",$sqlExp['volum']);
        if(count($volumes) == 3) {
            list($ret['volum1'], $ret['volum2'], $ret['volum3']) = $volumes;
        }

        $sqlExp['liv_sambata'] = !empty($sqlExp['liv_sambata']) ? 'DA' : 'NU';

        $sqlExp['RETUR_AMB'] = !empty($sqlExp['ret_amb']) ? 'DA' : 'NU';
        $sqlExp['RETUR_COLET'] = !empty($sqlExp['ret_colet']) ? 'DA' : 'NU';
        $sqlExp['RETUR_NC'] = !empty($sqlExp['extrainfo']) ? 'DA' : 'NU';
        $sqlExp['liv_sediu'] = !empty($sqlExp['liv_sediu']) ? 'DA' : 'NU';
        $sqlExp['RETUR_NT'] = !empty($sqlExp['ret_nt']) ? 'DA' : 'NU';
        $sqlExp['RETUR_DOC'] = !empty($sqlExp['ret_doc']) ? 'DA' : 'NU';
        $sqlExp['sms'] = !empty($sqlExp['sms']) ? 'DA' : 'NU';
        $sqlExp['copen'] = !empty($sqlExp['copen']) ? 'DA' : 'NU';

        if(!empty($sqlExp['ret_amb'])) $sqlExp['RETUR_AMB'] = 'DA';
		else $sqlExp['RETUR_AMB'] = 'NU';
        if(!empty($sqlExp['ret_colet'])) $sqlExp['RETUR_COLET'] = 'DA';
		else $sqlExp['RETUR_COLET'] = 'NU';
		if(!empty($sqlExp['extrainfo'])) $sqlExp['RETUR_NC'] = 'DA';
		else $sqlExp['RETUR_NC'] = 'NU';
        if(!empty($sqlExp['liv_sediu'])) $sqlExp['liv_sediu'] = 'DA';
		else $sqlExp['liv_sediu'] = 'NU';
		if(!empty($sqlExp['ret_nt'])) $sqlExp['RETUR_NT'] = 'DA';
		else $sqlExp['RETUR_NT'] = 'NU';
	 	if(!empty($sqlExp['ret_doc'])) $sqlExp['RETUR_DOC'] = 'DA';
		else $sqlExp['RETUR_DOC'] = 'NU';
		if(!empty($sql['sms'])) $sqlExp['sms'] = 'DA';
		else $sqlExp['sms'] = 'NU';
		if(!empty($sqlExp['copen'])) $sqlExp['copen'] = 'DA';
		else $sqlExp['copen'] = 'NU';

		$sqlExp['RET_NC_SHOW'] = $sqlExp['RET_AMB_SHOW'] = 'ascuns';
		if($expeditor_id == 171350) //maravet
			$sqlExp['RET_NC_SHOW'] = '';
		if(!empty($ret_amb))
			$sqlExp['RET_AMB_SHOW'] = '';

		if($sqlExp['ramburs'] > 0)
		{
			if($sqlExp['tip_plata'] == 0) $sqlExp['tip_plata'] = 'cash';
			else if($sqlExp['tip_plata'] == 1) $sqlExp['tip_plata'] = 'bo';
			else if($sqlExp['tip_plata'] == 2) $sqlExp['tip_plata'] = 'cec';
			else if($sqlExp['tip_plata'] == 3) $sqlExp['tip_plata'] = 'cont';
		}
		else $sqlExp['tip_plata'] = '';

		if($sqlExp['platitor'] == 1) $sqlExp['platitor'] = 'Expeditor';
		else $sqlExp['platitor'] = 'Destinatar';

		if($sqlExp['borderou_id'] > 0) $sqlExp['class_edit'] = 'style="display: none;"';

		$sqlExp['user_id'] = $print_awb;
		$sqlExp['print'] = ($print_awb == 2) ? "Print" : "PDF";
        //var_dump($ret);
        return $sqlExp;
    }

    public static function sqlExpToPdf($sqlExp = []){
        if($sqlExp === false || !is_array($sqlExp) || count($sqlExp) == 0) return [];

        $ret = $sqlExp;

		if(strtoupper(trim($ret['expeditor_contact'])) == 'PERSOANA')
			$ret['expeditor_contact']='';
		if(strtoupper(trim($ret['expeditor_telefon']))=='TELEFON')
			$ret['expeditor_telefon']='';

        if(strtoupper(trim($ret['destinatar_contact']))=='PERSOANA')
			$ret['destinatar_contact']='';
		if(strtoupper(trim($ret['destinatar_telefon']))=='TELEFON')
			$ret['destinatar_telefon']='';

		$ret['tip_obj'] = match($sqlExp['tip_obj']){
            1 => 'PLIC',
            2 => 'COLET',
            3 => 'PALET',
            default => ''
        };
		$ret['piese'] = $sqlExp['piese'] ?? 0;
		$ret['greutate'] = round($sqlExp['greutate'],3);
		$ret['greutate_vol'] = round($sqlExp['greutate_vol'],3);

		$ret['liv_sambata'] = $sqlExp['liv_samb'];
 		$ret['liv_sediu'] = $sqlExp['liv_sed'];

 		if($sqlExp['tip_exp'] == 3 && $sqlExp['tip_plata'] == 3) $ret['observatii'] .= ' CONT COLECTOR';

        $ret['asigurare'] = $ret['ramburs'] = 0.00;
 		if($sqlExp['valoare_asigurata'] > 0){
			$ret['asigurare'] = number_format(round($sqlExp['valoare_asigurata'], 2), 2, '.', '');
		}

        if($sqlExp['ramburs'] > 0){
        	$ret['ramburs'] = number_format(round($sqlExp['ramburs'], 2), 2, '.', '');
        }

        $ret['platitor'] = 0;
		if($sqlExp['platitor_id'] == $sqlExp['expeditor_id']) $ret['platitor'] = 1;
		else if($sqlExp['platitor_id'] == $sqlExp['destinatar_id']) $ret['platitor'] = 2;
		else{
            $ret['platitor'] = 3;
            $ret['cont_tert'] = $sqlExp['platitor_id'];
            $ret['nume_tert'] = $sqlExp['platitor_nume'];
        }

        $ret['valoare_totala'] = round($sqlExp['valoare_totala_expeditie'], 2);
        $ret['valoare_tva'] = round($sqlExp['tva'], 2);
        $ret['moneda'] = self::MONEDA[$sqlExp['moneda']] ?? $sqlExp['moneda'];

		$ret['curier'] = (!empty($sqlExp['curier_preluare'])) ? $sqlExp['curier_preluare'] : '';

        if(!self::isValidCui($sqlExp['expeditor_cui'])) {
            $ret['expeditor_cui'] = $ret['expeditor_j'] = "";
        }

        if($ret['tip_exp'] == Backend::TIP_EXP_BO_RBS_CASH)
            $ret['awb_rbs'] = str_replace(',', ', ', $ret['awb_rbs']);

        return $ret;
    }

    public static function sqlExpClientToPdf($sqlExp = []){
        if($sqlExp === false || !is_array($sqlExp) || count($sqlExp) == 0) return [];

        $ret = $sqlExp;

		$ret['tip_obj'] = match($sqlExp['tip_obj']){
            1 => 'PLIC',
            2 => 'COLET',
            3 => 'PALET',
            default => ''
        };

		$ret['greutate'] = round($sqlExp['greutate'], 3);
        $ret['greutate_vol'] = round($sqlExp['greutate_vol'],3);

 		if($sqlExp['asigurare'] > 0){
			$ret['asigurare'] = round($sqlExp['asigurare'], 2);
		}

        if($sqlExp['ramburs'] > 0){
        	$ret['ramburs'] = round($sqlExp['ramburs'], 2);
        }

        $ret['valoare_totala'] = round($sqlExp['valoare_totala'], 2);
        $ret['valoare_tva'] = round($sqlExp['valoare_tva'], 2);

        if(empty($sqlExp['moneda'])) $sqlExp['moneda'] = 1;
		$ret['moneda'] = self::MONEDA[$sqlExp['moneda']] ?? "LEI";

        if(!self::isValidCui($sqlExp['expeditor_cui'])) {
            $ret['expeditor_cui'] = $ret['expeditor_j'] = "";
        }

        return $ret;
    }

    public static function uiExpClientToSql($uiExp = [], $expeditor_cc){
        $result = [];

        $result['swapped'] = intval(Backend::sSanitize($uiExp['SWAPPED'] ?? 0));
        $result['selected_id'] = intval(Backend::sSanitize($uiExp['SELECTED_ID'] ?? 0));

        $result['destinatar_nume'] = (!empty(Backend::sSanitizeCleanEdges($uiExp['DESTINATAR_NUME'] ?? "")) ? strtoupper(Backend::sSanitizeCleanEdges($uiExp['DESTINATAR_NUME'])):"");
		$result['destinatar_id'] = intval(Backend::sSanitize($uiExp['DESTINATAR_ID'] ?? 0));
		$result['destinatar_localitate_id'] = intval(Backend::sSanitize($uiExp['DESTINATAR_LOCALITATE_ID'] ?? 0));
		$result['destinatar_localitate'] = (!empty(Backend::sSanitizeCleanEdges($uiExp['DESTINATAR_LOCALITATE'] ?? "")) ? strtoupper(Backend::sSanitizeCleanEdges($uiExp['DESTINATAR_LOCALITATE'])):"");
		$result['destinatar_contact'] = (!empty(Backend::sSanitizeCleanEdges($uiExp['DESTINATAR_CONTACT'] ?? "")) ? strtoupper(Backend::sSanitizeCleanEdges($uiExp['DESTINATAR_CONTACT'])):"");
		$result['destinatar_telefon'] = ((!empty(Backend::sSanitize($uiExp['DESTINATAR_TELEFON'] ?? "")) && strtoupper(Backend::sSanitize($uiExp['DESTINATAR_TELEFON']))!='TELEFON')?strtoupper(Backend::sSanitize($uiExp['DESTINATAR_TELEFON'])):'');
		$result['destinatar_adresa'] = (!empty(Backend::sSanitizeCleanEdges($uiExp['DESTINATAR_ADRESA'] ?? "")) ? strtoupper(Backend::sSanitizeCleanEdges($uiExp['DESTINATAR_ADRESA'])):"");
        $result['km_ext_livr'] = intval(Backend::sSanitize($uiExp['DESTINATAR_LOCALITATE_KM'] ?? 0));

        $result['expeditor_nume'] = (!empty(Backend::sSanitize($uiExp['EXPEDITOR_NUME'] ?? "")) ? strtoupper(Backend::sSanitize($uiExp['EXPEDITOR_NUME'])):"");
		$result['expeditor_id'] = intval(Backend::sSanitize($uiExp['EXPEDITOR_ID'] ?? 0));
		$result['expeditor_localitate_id'] = intval(Backend::sSanitize($uiExp['EXPEDITOR_LOCALITATE_ID'] ?? 0));
		$result['expeditor_localitate'] = (!empty(Backend::sSanitizeCleanEdges($uiExp['EXPEDITOR_LOCALITATE'] ?? "")) ? strtoupper(Backend::sSanitizeCleanEdges($uiExp['EXPEDITOR_LOCALITATE'])):"");
		$result['expeditor_contact'] = (!empty(Backend::sSanitizeCleanEdges($uiExp['EXPEDITOR_CONTACT'] ?? "")) ? strtoupper(Backend::sSanitizeCleanEdges($uiExp['EXPEDITOR_CONTACT'])):"");
		$result['expeditor_telefon'] = ((!empty(Backend::sSanitize($uiExp['EXPEDITOR_TELEFON'] ?? "")) && strtoupper(Backend::sSanitize($uiExp['EXPEDITOR_TELEFON']))!='TELEFON')?strtoupper(Backend::sSanitize($uiExp['EXPEDITOR_TELEFON'])):'');
		$result['expeditor_adresa'] = (!empty(Backend::sSanitizeCleanEdges($uiExp['EXPEDITOR_ADRESA'] ?? "")) ? strtoupper(Backend::sSanitizeCleanEdges($uiExp['EXPEDITOR_ADRESA'])):"");
        $result['km_ext_prel'] = intval(Backend::sSanitize($uiExp['EXPEDITOR_LOCALITATE_KM'] ?? 0));

        $result['platitor'] = intval(Backend::sSanitize($uiExp['platitor'] ?? 1));

        //tip expeditie
        $result['tip_obj'] = 2;
		$result['greutate'] = round(floatval(Backend::sSanitize($uiExp['GREUTATE'] ?? 1)), 3);
		$result['piese'] = intval(Backend::sSanitize($uiExp['TIP_OBJ_2'] ?? 1));

        $result['greutate_vol'] = 0.000;
        $result['volum'] = '';
        $result['volum1'] = intval(Backend::sSanitize($uiExp['VOLUM1'] ?? 0));
        $result['volum2'] = intval(Backend::sSanitize($uiExp['VOLUM2'] ?? 0));
        $result['volum3'] = intval(Backend::sSanitize($uiExp['VOLUM3'] ?? 0));
        if($result['volum1'] > 0 && $result['volum2'] > 0 && $result['volum3'] > 0){
			$result['volum'] = $result['volum1'].'x'.$result['volum2'].'x'.$result['volum3'];
            $result['greutate_vol'] = self::getGreutateVolumetrica($result['volum1'], $result['volum2'], $result['volum3']);
        }

        $uiPlicuri = intval(Backend::sSanitize($uiExp['TIP_OBJ_1'] ?? 0));
        $uiPaleti = intval(Backend::sSanitize($uiExp['TIP_OBJ_3'] ?? 0));

        if($uiPaleti > 0){
            $result['tip_obj'] = 3;
			$result['piese'] = 1;
        }
        else if($uiPlicuri > 0){
            $result['tip_obj'] = 1;
            $result['greutate'] = 0.500;
			$result['piese'] = 1;
            $result['volum'] = "";
            $result['greutate_vol'] = 0.000;
        }

        //if($result['tip_obj'] == 2 && $result['greutate'] < Backend::MIN_KG_COLET) $result['greutate'] = Backend::MIN_KG_COLET;

        $result['ret_nt'] = $result['ret_doc'] = $result['ret_amb'] = $result['ret_colet'] = $result['ret_nc'] = $result['liv_sambata'] = $result['liv_sediu'] = $result['sms'] = $result['copen'] = 0;

        if(!empty(Backend::sSanitize($uiExp['RET_NT'] ?? 0)))
            $result['ret_nt']=1;

        if(!empty(Backend::sSanitize($uiExp['RET_DOC'] ?? 0)))
            $result['ret_doc']=1;

        if(!empty(Backend::sSanitize($uiExp['RET_AMB'] ?? 0)))
            $result['ret_amb']=1;

        if(!empty(Backend::sSanitize($uiExp['RET_COLET'] ?? 0)))
            $result['ret_colet']=1;

        if(!empty(Backend::sSanitize($uiExp['RET_NC'] ?? 0)))
            $result['ret_nc']=1;

        if(!empty(Backend::sSanitize($uiExp['LIV_S'] ?? 0)))
            $result['liv_sambata']=1;

        if(!empty(Backend::sSanitize($uiExp['LIV_SEDIU'] ?? 0)))
            $result['liv_sediu']=1;

        if(!empty(Backend::sSanitize($uiExp['SMS'] ?? 0)))
            $result['sms'] = -1;

        if(!empty(Backend::sSanitize($uiExp['COPEN'] ?? 0)))
            $result['copen']=1;

        $result['asigurare'] = round(floatval(Backend::sSanitize($uiExp['asigurare'] ?? 0.00)), 2);
        $result['ramburs'] = round(floatval(Backend::sSanitize($uiExp['ramburs'] ?? 0.00)), 2);
        $result['tip_plata'] = intval(Backend::sSanitize($uiExp['tip_plata'] ?? 0));
        
        $result['detalii_doc'] = Backend::sSanitizeCleanEdges($uiExp['detalii_doc'] ?? '');
		$result['observatii'] = Backend::sSanitizeCleanEdges($uiExp['observatii'] ?? '');

        //tip tarif
        $result['tip_tarif'] = intval($result['expeditor_localitate_id'] != $result['destinatar_localitate_id']);
        //tip plata ramburs
        if(!empty($expeditor_cc) && $result['tip_plata'] == 0)
            $result['tip_plata'] = 3;
        //var_dump($result);

        return $result;
    }

    public static function uiExpToSql($uiExp = []){
        $result = [];

        $result['expeditie'] = strtoupper(Backend::sSanitize($uiExp['NR_EXP'] ?? 'NEW')) == 'NEW' ? -1 : intval(Backend::sSanitize($uiExp['NR_EXP'] ?? 0));
        $result['referire'] = intval(Backend::sSanitize($uiExp['referinta_expeditie'] ?? 0));
        $result['data_expeditie'] = ExpeditieDto::uiDateToSql(null, true);
        $result['expeditor_id'] = intval(Backend::sSanitize($uiExp['EXPEDITOR'] ?? 0));
        $result['expeditor_nume'] = (!empty(Backend::sSanitizeCleanEdges($uiExp['EXPEDITOR_NUME'] ?? "")) ? strtoupper(Backend::sSanitizeCleanEdges($uiExp['EXPEDITOR_NUME'])):"");
			//$result['debug'] = true;
        $result['destinatar_id'] = intval(Backend::sSanitize($uiExp['DESTINATAR'] ?? 0));
        $result['destinatar_nume'] = (!empty(Backend::sSanitizeCleanEdges($uiExp['DESTINATAR_NUME'] ?? "")) ? strtoupper(Backend::sSanitizeCleanEdges($uiExp['DESTINATAR_NUME'])):"");

        $result['expeditor_localitate_id'] = intval(Backend::sSanitize($uiExp['EXPEDITOR_LOCALITATE'] ?? 0));
        $result['destinatar_localitate_id'] = intval(Backend::sSanitize($uiExp['DESTINATAR_LOCALITATE'] ?? 0));

        $result['expeditor_contact'] = (!empty(Backend::sSanitizeCleanEdges($uiExp['EXPEDITOR_CONTACT'] ?? "")) ? strtoupper(Backend::sSanitizeCleanEdges($uiExp['EXPEDITOR_CONTACT'])):"");
        $result['expeditor_telefon'] = strtoupper(Backend::sSanitize($uiExp['EXPEDITOR_TELEFON'] ?? ''));
        $result['destinatar_contact'] = (!empty(Backend::sSanitizeCleanEdges($uiExp['DESTINATAR_CONTACT'] ?? "")) ? strtoupper(Backend::sSanitizeCleanEdges($uiExp['DESTINATAR_CONTACT'])):"");
        $result['destinatar_telefon'] = strtoupper(Backend::sSanitize($uiExp['DESTINATAR_TELEFON'] ?? ''));

        $result['platitor_id'] = intval(Backend::sSanitize($uiExp['PLATITOR'] ?? 0));

        if($result['platitor_id'] == 0) {
            $result['platitor_id'] = $result['expeditor_id'];
            $plateste2 = intval(Backend::sSanitize($uiExp['plateste2'] ?? 0));
            if($plateste2 > 0) $result['platitor_id'] = $result['destinatar_id'];
        }
        $result['platitor_nume'] = Backend::sSanitize($uiExp['PLATITOR_NUME'] ?? '');

        $result['tip_exp'] = intval(Backend::sSanitize($uiExp['tip_exp'] ?? 0));
        $result['valoare_asigurata'] = floatval(Backend::sSanitize($uiExp['asigurare'] ?? 0.00));
        $result['ramburs'] = round(floatval(Backend::sSanitize($uiExp['ramburs'] ?? 0.00)), 2);
        $result['tip_plata'] = intval(Backend::sSanitize($uiExp['tip_plata'] ?? 0));

        $result['plicuri'] = $result['paleti'] = 0;
        $result['tip_obj'] = 2;
        $result['piese'] = $result['colete'] = intval(Backend::sSanitize($uiExp['TIP_OBJ_2'] ?? 0));
        $uiPlicuri = intval(Backend::sSanitize($uiExp['TIP_OBJ_1'] ?? 0));
        $uiPaleti = intval(Backend::sSanitize($uiExp['TIP_OBJ_3'] ?? 0));

        if($uiPaleti > 0){
            $result['tip_obj'] = 3;
            $result['piese'] = $result['paleti'] = 1;
        } else if($uiPlicuri > 0){
            $result['tip_obj'] = 1;
            $result['piese'] = $result['plicuri'] = 1;
        }

        //error_log('tip_obj: '.$result['tip_obj'].' piese: '.$result['piese']);

        $result['ret_nt'] = $result['ret_doc'] = $result['ret_amb'] = $result['ret_colet'] = $result['liv_sambata'] = $result['liv_sediu'] = $result['sms'] = $result['copen'] = 0;

        $result['greutate'] = round(floatval(Backend::sSanitize($uiExp['GREUTATE'] ?? ($result['tip_obj'] == 1 ? 0.500 : 1))), 3);
        $result['greutate_vol'] = 0.000;
        $result['volum'] = '';
        $result['volum1'] = intval(Backend::sSanitize($uiExp['VOLUM1'] ?? 0));
        $result['volum2'] = intval(Backend::sSanitize($uiExp['VOLUM2'] ?? 0));
        $result['volum3'] = intval(Backend::sSanitize($uiExp['VOLUM3'] ?? 0));
        if($result['volum1'] > 0 && $result['volum2'] > 0 && $result['volum3'] > 0){
			$result['volum'] = $result['volum1'].'x'.$result['volum2'].'x'.$result['volum3'];
            $result['greutate_vol'] = self::getGreutateVolumetrica($result['volum1'], $result['volum2'], $result['volum3']);
        }

        if($result['tip_obj'] == 1){
            $result['greutate'] = 0.500;
            $result['volum'] = '';
            $result['greutate_vol'] = 0.000;
        }
        
        $result['km_preluare'] = intval(Backend::sSanitize($uiExp['KM_EXT_PREL'] ?? 0));
        $result['km_livrare'] = intval(Backend::sSanitize($uiExp['KM_EXT_LIV'] ?? 0));

        $result['detalii_doc'] = Backend::sSanitizeCleanEdges($uiExp['detalii_doc'] ?? '');
		$result['observatii'] = Backend::sSanitizeCleanEdges($uiExp['OBSERVATII'] ?? '');
        $result['coduri'] = trim($uiExp['CODURI'] ?? '');

        $result['tip_tarif'] = $uiExp['tip_tarif'] ?? ($result['expeditor_localitate_id'] != $result['destinatar_localitate_id']) ? 1 : 0;

        $result['liv_samb'] = intval(!empty(Backend::sSanitize($uiExp['LIV_S'] ?? 0)));
        $result['liv_sed'] = intval(!empty(Backend::sSanitize($uiExp['LIV_SEDIU'] ?? 0)));
        $result['pret_impus'] = 0.00;
        switch ($result['tip_exp']) {
            case 1 :
                $result = self::uiExpToSqlRetNtDoc($result);
                break;
            case 2 :
                $result = self::uiExpToSqlRetNtDoc($result);
                break;
            case 3 :
                $result = self::uiExpToSqlRamb($result);
                break;
            case 5 :
                $result = self::uiExpToSqlReturnare($result, $uiPlicuri, $uiPaleti);
                break;
            case 6 :
                $result = self::uiExpToSqlRetAmb($result, round(floatval(Backend::sSanitize($uiExp['GREUTATE'] ?? self::DEFAULT_KG_RET_AMB)), 3));
                break;
            case 7 :
                $result = self::uiExpToSqlRetColet($result, $result['greutate']);
                break;

            default :
                if($uiPaleti > 0){
                    $result['tip_obj'] = 3;
                    $result['plicuri'] = 0;
                    $result['colete'] = 0;
                    $result['piese'] = $result['paleti'] = 1;
                }
                else if($uiPlicuri > 0){
                    $result['tip_obj'] = 1;
                    $result['piese'] = $result['plicuri'] = 1;
                    $result['colete'] = 0;
                    $result['paleti'] = 0;
                    $result['greutate'] = 0.500;
                    $result['volum'] = '';
                    $result['volum1'] = $result['volum2'] = $result['volum3'] = 0;
                }
                $result['ret_nt'] = intval(!empty(Backend::sSanitize($uiExp['RET_NT'] ?? 0)));
                $result['ret_doc'] = intval(!empty(Backend::sSanitize($uiExp['RET_DOC'] ?? 0)));
                $result['ret_amb'] = intval(!empty(Backend::sSanitize($uiExp['RET_AMB'] ?? 0)));
                $result['ret_colet'] = intval(!empty(Backend::sSanitize($uiExp['RET_COLET'] ?? 0)));
                $result['sms'] = (!empty(Backend::sSanitize($uiExp['SMS'] ?? 0))) ? -1 : 0;
                $result['copen'] = intval(!empty(Backend::sSanitize($uiExp['COPEN'] ?? 0)));
                $result['pret_impus'] = $uiExp['can_pret_impus'] && intval($uiExp['PRET_IMPUS'] ?? 0) > 0 ? round(floatval(Backend::sSanitize($uiExp['PRET_IMPUS'] ?? 0.00)), 2) : 0;
                //error_log(intval($uiExp['can_pret_impus']) . ' - ' . ($uiExp['PRET_IMPUS'] ?? 0) . ' - ' . $result['pret_impus']);
        }
        //error_log('tip_obj: '.$result['tip_obj'].' plicuri: '.$uiPlicuri.' paleti: '.$uiPaleti.' piese: '.$result['piese'].' greutate: '.$result['greutate'].' volum: '.$result['volum']);
        return $result;
    }

    private static function uiExpToSqlRetNtDoc($in = []) {
        $in['valoare_asigurata'] = $in['ramburs'] = $in['procent_asigurare'] = $in['ramburs_procent'] = $in['tip_plata'] = $in['colete'] = $in['paleti'] = $in['ret_nt'] = $in['ret_doc'] = $in['ret_amb'] = $in['ret_colet'] = $in['liv_samb'] = $in['liv_sed'] = $in['sms'] = $in['copen'] = 0;
        $in['piese'] = $in['plicuri'] = 1;
        $in['tip_obj'] = 1;
        $in['greutate'] = 0.500;
        $in['greutate_vol'] = 0.000;
        $in['volum'] = '';
        $in['volum1'] = $in['volum2'] = $in['volum3'] = 0;
        $in['km_preluare'] = 0;
        return $in;
    }

    private static function uiExpToSqlRamb($in = []) {
        $in['colete'] = $in['paleti'] = $in['ret_nt'] = $in['ret_doc'] = $in['ret_amb'] = $in['ret_colet'] = $in['liv_samb'] = $in['liv_sed'] = $in['sms'] = $in['copen'] = 0;
        $in['piese'] = $in['plicuri'] = 1;
        $in['tip_obj'] = 1;
        $in['greutate'] = 0.500;
        $in['greutate_vol'] = 0.000;
        $in['volum'] = '';
        $in['volum1'] = $in['volum2'] = $in['volum3'] = 0;
        $in['km_preluare'] = 0;
        return $in;
    }

    private static function uiExpToSqlReturnare($in = [], $uiPlicuri, $uiPaleti) {
        $in['valoare_asigurata'] = $in['ramburs'] = $in['procent_asigurare'] = $in['ramburs_procent'] = $in['ret_nt'] = $in['ret_doc'] = $in['ret_colet'] = $in['ret_amb'] = $in['sms'] = $in['copen'] = 0;
        $in['km_preluare'] = 0;
        if($uiPaleti > 0){
            $in['tip_obj'] = 3;
            $in['plicuri'] = 0;
            $in['colete'] = 0;
            $in['piese'] = $in['paleti'] = 1;
        }
        else if($uiPlicuri > 0){
            $in['tip_obj'] = 1;
            $in['piese'] = $in['plicuri'] = 1;
            $in['colete'] = 0;
            $in['paleti'] = 0;
            $in['greutate'] = 0.500;
            $in['volum'] = '';
            $in['volum1'] = $in['volum2'] = $in['volum3'] = 0;
        }
        return $in;
    }

    private static function uiExpToSqlRetAmb($in = [], $greutate) {
        $in['valoare_asigurata'] = $in['ramburs'] = $in['procent_asigurare'] = $in['ramburs_procent'] = $in['tip_plata'] = $in['plicuri'] = $in['paleti'] = $in['ret_nt'] = $in['ret_doc'] = $in['ret_colet'] = $in['ret_amb'] = $in['liv_samb'] = $in['liv_sed'] = $in['sms'] = $in['copen'] = 0;
        $in['piese'] = $in['colete'] = 1;
        $in['tip_obj'] = 2;
        $in['greutate'] = $greutate;
        $in['greutate_vol'] = 0.000;
        $in['volum'] = '';
        $in['volum1'] = $in['volum2'] = $in['volum3'] = 0;
        $in['km_preluare'] = 0;
        return $in;
    }

    private static function uiExpToSqlRetColet($in = [], $greutate) {
        $in['valoare_asigurata'] = $in['ramburs'] = $in['procent_asigurare'] = $in['ramburs_procent'] = $in['tip_plata'] = $in['plicuri'] = $in['paleti'] = $in['ret_nt'] = $in['ret_doc'] = $in['ret_colet'] = $in['ret_amb'] = $in['liv_samb'] = $in['liv_sed'] = $in['sms'] = $in['copen'] = 0;
        $in['tip_obj'] = 2;
        $in['greutate'] = $greutate;
        $in['km_preluare'] = 0;
        return $in;
    }

    public static function sqlInitialaToRetur($initialaRow, $arrForVals, $val) {
        $vi = [];

        $vi['tip_exp'] = $arrForVals['tip_exp'];
        $vi['referire'] = $arrForVals['referire'] ?? $initialaRow['expeditie'];
        $vi['data_expeditie'] = $vi['data_operatie'] = date("Y-m-d");
        $vi['data_op'] = $vi['data'] = date('Y-m-d H:i:s');
        $vi['km_preluare'] = 0;
        $vi['km_livrare'] = $initialaRow['expeditor_localitate_km'];
        $vi['mod_plata'] = $initialaRow['mod_plata'];
        $vi['ret_nt'] = $vi['ret_doc'] = $vi['liv_samb'] = $vi['liv_sed'] = $vi['ret_amb'] = $vi['ret_colet'] = $vi['sms'] = $vi['copen'] = 0;
        $vi['moneda'] = self::MONEDA[$initialaRow['moneda']] ?? $initialaRow['moneda'];
        $vi['procTva'] = $initialaRow['procTva'];

        $vi['expeditor_id'] = $initialaRow['destinatar_id'];
        $vi['expeditor_contact'] = $initialaRow['destinatar_contact'];
        $vi['expeditor_telefon'] = $initialaRow['destinatar_telefon'];
        $vi['destinatar_id'] = $initialaRow['expeditor_id'];
        $vi['destinatar_contact'] = $initialaRow['expeditor_contact'];
        $vi['destinatar_telefon'] = $initialaRow['expeditor_telefon'];
        $vi['platitor_id'] = $initialaRow['platitor_id'];
        $vi['curier_preluare_id']  = $initialaRow['curier_livrare_id'];

        $vi['observatii'] = $arrForVals['observatii'] ?? '';

        switch ($arrForVals['tip_exp']) {
            case 1 :
            case 2 :
                $vi = self::sqlInitialaToSqlRetNtDoc($vi);
                break;
            case 3 :
                $vi['ramburs'] = $initialaRow['ramburs'];
                $vi['tip_plata'] = $initialaRow['tip_plata'];
                $vi['val_asig'] = $val['tRamburs'];
                $vi['ramburs_procent'] = $val['procRamburs'];
                $vi = self::sqlInitialaToSqlRamb($vi);
                break;
            case 5 :
                //plata la expeditor initiala obligatorie -> exceptie initiala cu plata la tertz
                if($initialaRow['platitor_id'] == $initialaRow['destinatar_id']){
                    $vi['platitor_id'] = $initialaRow['expeditor_id'];
                    $vi['plateste'] = 2;
                }
                $vi['plicuri'] = $initialaRow['plicuri'];
                $vi['colete'] = $initialaRow['colete'];
                $vi['paleti'] = $initialaRow['paleti'];
                $vi['tip_obj'] = $initialaRow['tip_obj'];
                $vi['piese'] = $initialaRow['piese'];
                $vi['greutate'] = $initialaRow['greutate'];
                $vi['greutate_vol'] = $initialaRow['greutate_vol'];
                $vi['volum'] = $initialaRow['volum'];
                $vi['mod_plata'] = $val['mod_plata'];
                $vi['valoare_asigurata'] = $val['valoare_asigurata'];
                $vi['procent_asigurare'] = $val['procAsigurare'];
                $vi['val_asig'] = $val['tAsigurare'];
                $vi = self::sqlInitialaToSqlReturnare($vi);
                break;
            case 6 :
                $vi['greutate'] = $val['greutate'];
                $vi = self::sqlInitialaToSqlRetAmb($vi);
                break;
            case 7 :
                $vi['greutate'] = $arrForVals['greutate'];;
                $vi['greutate_vol'] = $arrForVals['greutate_vol'];
                $vi['volum'] = $arrForVals['volum'];
                $vi['piese'] = $vi['colete'] = $arrForVals['colete'];
                //plata la expeditor initiala obligatorie -> exceptie initiala cu plata la tertz
                if($initialaRow['platitor_id'] == $initialaRow['destinatar_id']){
                    $vi['platitor_id'] = $initialaRow['expeditor_id'];
                    $vi['plateste'] = 2;
                    $vi['mod_plata'] = $val['mod_plata'];
                }
                $vi = self::sqlInitialaToSqlRetCol($vi);
                break;
            default :
                error_log('Eroare sqlInitialaToRetur : tip_exp = 0 or unknown pentru expeditia initiala '.$arrForVals['tip_exp']);
        }

        $vi['valoare_expeditie'] = $val['tExpeditie'];
        $vi['val_greutate'] = $val['tGreutate'];
        $vi['val_km'] = $val['tKm'];
        if($arrForVals['tip_exp'] != 7)
            $vi['pret_impus'] = $initialaRow['pret_impus'];
        if($initialaRow['pret_impus'] > 0 && in_array($arrForVals['tip_exp'], [1,2,3,5,6])) {
            $vi['val_asig'] = $vi['valoare_expeditie'] = $vi['val_greutate'] = $vi['val_km'] = $vi['valoare_totala_expeditie'] = $vi['tva'] = 0;
            if($arrForVals['tip_exp'] == 5){
                $vi['valoare_expeditie'] = $vi['valoare_totala_expeditie'] = round($initialaRow['valoare_totala_expeditie'], 2);
                $vi['tva'] = round($vi['valoare_totala_expeditie'] * $vi['procTva'] / 100, 2);
            }
        }
        else {
            $vi['valoare_totala_expeditie'] = round($val['tExpeditie'] + $val['tGreutate'] + $val['tKm'] + $val['tAsigurare'] + $val['tRamburs'], 2);
            $vi['tva'] = round($vi['valoare_totala_expeditie'] * $vi['procTva'] / 100, 2);
        }

        return $vi;
    }

    public static function sqlBorderouRbsCash($expeditor_id, $expeditor_contact, $expeditor_telefon, $destinatar_id, $valoare_asigurata, $procTva) {
        $vi = [];

        $vi['tip_exp'] = Backend::TIP_EXP_BO_RBS_CASH; //borderou rbs cash
        $vi['referire'] = 0;
        $vi['data_expeditie'] = $vi['data_operatie'] = date("Y-m-d");
        $vi['data_op'] = $vi['data'] = date('Y-m-d H:i:s');
        $vi['km_preluare'] = 0;
        $vi['km_livrare'] = 0;
        $vi['mod_plata'] = 0;
        $vi['ret_nt'] = $vi['ret_doc'] = $vi['liv_samb'] = $vi['liv_sed'] = $vi['ret_amb'] = $vi['ret_colet'] = $vi['sms'] = $vi['copen'] = 0;
        $vi['moneda'] = self::MONEDA[1];
        $vi['procTva'] = $procTva;

        $vi['expeditor_id'] = $expeditor_id;
        $vi['expeditor_contact'] = $expeditor_contact;
        $vi['expeditor_telefon'] = $expeditor_telefon;
        $vi['destinatar_id'] = $destinatar_id;
        $vi['destinatar_contact'] = '';
        $vi['destinatar_telefon'] = '';
        $vi['platitor_id'] = $expeditor_id;

        $vi['curier_preluare_id']  = '';

        $vi['ramburs'] = 0;
        $vi['valoare_asigurata'] = $valoare_asigurata;
        $vi['tip_plata'] = 0; //cash
        $vi['val_asig'] = 0;
        $vi['ramburs_procent'] = 0;
        $vi['colete'] = $vi['paleti'] = $vi['procent_asigurare'] = 0;
        $vi['piese'] = $vi['plicuri'] = 1;
        $vi['tip_obj'] = 1;
        $vi['greutate'] = 0.500;
        $vi['greutate_vol'] = 0.000;
        $vi['volum'] = '';
        
        $vi['valoare_expeditie'] = 0;
        $vi['val_greutate'] = 0;
        $vi['val_km'] = 0;
        $vi['valoare_totala_expeditie'] = 0;
        $vi['tva'] = 0;

        return $vi;
    }

    private static function sqlInitialaToSqlRetNtDoc($in = []) {
        $in['ramburs'] = $in['tip_plata'] = $in['ramburs_procent'] = $in['valoare_asigurata'] = $in['procent_asigurare'] = $in['val_asig'] = $in['colete'] = $in['paleti'] = 0;
        $in['piese'] = $in['plicuri'] = 1;
        $in['tip_obj'] = 1;
        $in['greutate'] = 0.500;
        $in['greutate_vol'] = 0.000;
        $in['volum'] = '';
        return $in;
    }

    private static function sqlInitialaToSqlRamb($in = []) {
        $in['colete'] = $in['paleti'] = $in['procent_asigurare'] = $in['valoare_asigurata'] = 0;
        $in['piese'] = $in['plicuri'] = 1;
        $in['tip_obj'] = 1;
        $in['greutate'] = 0.500;
        $in['greutate_vol'] = 0.000;
        $in['volum'] = '';
        if($in['tip_plata'] == 0 || $in['tip_plata'] == 3) {
            $in['valoare_asigurata'] = $in['ramburs'];
            $in['ramburs'] = 0.00;
        }
        return $in;
    }

    private static function sqlInitialaToSqlReturnare($in = []) {
        $in['ramburs'] = $in['tip_plata'] = $in['ramburs_procent'] = 0;
        return $in;
    }

    private static function sqlInitialaToSqlRetAmb($in = []) {
        $in['ramburs'] = $in['tip_plata'] = $in['ramburs_procent'] = $in['valoare_asigurata'] = $in['procent_asigurare'] = $in['val_asig'] = $in['plicuri'] = $in['paleti'] = 0;
        $in['piese'] = $in['colete'] = 1;
        $in['tip_obj'] = 2;
        $in['greutate_vol'] = 0.000;
        $in['volum'] = '';
        return $in;
    }

    private static function sqlInitialaToSqlRetCol($in = []) {
        $in['ramburs'] = $in['tip_plata'] = $in['ramburs_procent'] = $in['valoare_asigurata'] = $in['procent_asigurare'] = $in['val_asig'] = $in['plicuri'] = $in['paleti'] = 0;
        $in['tip_obj'] = 2;
        return $in;
    }

    public static function sqlDateToUi($str, $formatDot = true, $dateOnly = true) {
        $format = 'd.m.Y';
        if($dateOnly === false) $format = 'd.m.Y H:i';
        if($formatDot === false) {
            $format = 'd/m/Y';
            if($dateOnly === false) $format = 'd/m/Y H:i';
        }
        if(empty($str)) return date($format);
        $result = DateTime::createFromFormat('Y-m-d H:i', $str);
        if($result === false) {
        	$result = DateTime::createFromFormat('Y-m-d', $str);
        	if($result === false)
        		return date($format);
        	return $result->format($format);
        }
        return $result->format($format);
    }

    public static function uiDateToSql($str, $dateOnly = false) {
        $format = 'Y-m-d H:i:s';
        if($dateOnly === true) $format = 'Y-m-d';
        if(empty($str)) return date($format);
        $result = DateTime::createFromFormat('d.m.Y H:i:s', $str);
        if($result === false) {
        	$result = DateTime::createFromFormat('d.m.Y H:i', $str);
            if($result === false){
                if($result === false){
                    $result = DateTime::createFromFormat('d/m/Y H:i:s', $str);
                    if($result === false) {
                        $result = DateTime::createFromFormat('d/m/Y', $str);
                        if($result === false)
                            return date($format);
                        return $result->format($format);
                    }
                    return $result->format($format);
                }
        	    return $result->format($format);
            }
            return $result->format($format);
        }
        return $result->format($format);
    }

    public static function isValidCod($cod){
        return self::isAwb($cod) || self::isPuisor($cod) || self::isCmnAwb($cod);
    }

    public static function isAwb($awb){
        return self::isAppAwb($awb) || self::isAndroidAwb($awb);
    }

    public static function isAppAwb($awb){
        return self::isSystemAwb($awb) || self::isMaravetAwb($awb);
    }

    public static function isSystemAwb($awb){
        /*
        expeditiile de sistem incep cu 7, 8, 9 pe 8 cifre
        */

        return preg_match('/^[789][0-9]{7}$/', $awb);
    }

    public static function isCmnAwb($awb){
        /*
        expeditiile din curiermanager ... old
        */

        return preg_match('/^1000[0-9]{5}$/', $awb);
    }

    public static function isOldSystemAwb($awb){
        /*
        expeditiile vechi incep cu 1 pe 7 cifre
        */

        return preg_match('/^[1-9][0-9]{6}$/', $awb);
    }

    public static function isAndroidAwb($awb){
        /*
        ntNoi de Android incep cu 3 pe 10 cifre
        */

        return preg_match('/^3[0-9]{9}$/', $awb);
    }

    public static function isRosieAwb($awb){
        /*
        rosii care incep cu 3|4|5|6|7 pe 7 cifre
        rosii care incep cu 1 pe 9 cifre
        */
        return preg_match('/^1[0-9]{8}$/', $awb);
    }

    public static function isMaravetAwb($awb){
        /*
        maravet incepe cu 290 | 291 pe 9 cifre
        */

        return preg_match('/^(290|291)[0-9]{6}$/', $awb);
    }

    public static function isPuisor($cod){
        return self::isSystemPuisor($cod);
    }

    public static function isSystemPuisor($cod){
        /*
        puisorii system contin awb system sau android, sign minus, 3 digits
        */
        return preg_match('/^([89][0-9]{7}|(290|291)[0-9]{6}|3[0-9]{9})-[0-9]{1,3}$/', $cod);
    }

    public static function getAwbFromPuisor($cod){
        /*
        extrage awb din codul de puisor
        */
        if(self::isSystemPuisor($cod)){
            $parts = explode('-', $cod);
            return intval($parts[0]);
        }
        return 0;
    }

    public static function getPuisoriForAwb($awb, $colete){
        /*
        returneaza un array cu puisorii pentru awb
        */
        $result = [];
        if($colete < 2 || $colete > 1000) return [$awb];
		for($i=2; $i <= $colete; $i++){
			$result[] = $awb.'-'.self::getPuisorNr($i);
		}
        return $result;
    }

    public static function getPuisorNr($nr){
		if($nr < 10)
			return '00'.$nr;
		if($nr < 100)
			return '0'.$nr;
		return $nr;
	}

    public static function isValidTelefonNumber($telefon){
        /*
        07XXXXXXXX
        */
        return preg_match('/^07[0-9]{8}$/', $telefon);
    }

    public static function isValidCui($cui) {
        $cui = self::sanitizeCuiRO($cui, true);
        if(!preg_match('/^\d{2,10}$/',$cui)) return false;

        $v = 753217532;
        $c1 = $cui % 10;
        $cui = intdiv($cui, 10);

        $t = 0;
        while($cui > 0){
            $t += ($cui % 10) * ($v % 10);
            $cui = intdiv($cui, 10);
            $v = intdiv($v, 10);
        }

        // aplica inmultirea cu 10 si afla modulo 11
        $c2 = ($t * 10) % 11;

        // daca modulo 11 este 10, atunci cifra de control este 0
        if($c2 == 10){
            $c2 = 0;
        }

        return $c1 == $c2;

    }

    public static function sanitizeCuiRO($input, $digitsOnly = false) {
        $input = strtoupper(trim($input));

        if (stripos($input, 'RORO') === 0) {
            // începe cu RORO → păstrăm prefixul și curățăm după
            $digits = substr($input, 4);
            $digits = preg_replace('/[^0-9]/', '', $digits);
            if(empty($digits)) return '';
            if($digitsOnly) return $digits;
            return 'RO' . $digits;
        }
        if (stripos($input, 'RO') === 0) {
            // începe cu RO → păstrăm prefixul și curățăm după
            $digits = substr($input, 2);
            $digits = preg_replace('/[^0-9]/', '', $digits);
            if(empty($digits)) return '';
            if($digitsOnly) return $digits;
            return 'RO' . $digits;
        }
        return preg_replace('/[^0-9]/', '', $input);
    }

    public static function isValidCnp($cnpToValidate) {
        $cnp = new Cnp($cnpToValidate);
        return $cnp->isValid();
    }

    public static function getGreutateVolumetrica($vol1, $vol2, $vol3) {
        if($vol1 > 0 && $vol2 > 0 && $vol3 > 0)
            return ceil(($vol1*$vol2*$vol3)/6000);
        return 0;
    }

    public static function canModifyStatusRbs($oldStatusRbs, $newStatusRbs, $tip_plata = -1) {
        return true;
        /*
            0 => 'In derulare', //automat : orice scan
            1 => 'Inchis', // automat : rbs cash livrat, rbs cc livrat, returnare livrata
            2 => 'Validat', // MANUAL : banii au ajuns in Bucuresti (CASH SAU CC)
            4 => 'Spre client', // CASH : CRON : orice scan pe care il ia expeditia in afara centrului de origine al expeditiei de tip ramburs sau returnare
            5 => 'Anulat',
            6 => 'Litigiu',
            7 => 'Pierdut',
            10 => 'Nepreluat',
            11 => 'Spre compensare',
            21 => 'On Hold',
            23 => 'Decontat', // AUTOMAT IN DECONT : DCL : decontul la sfarsitul zilei (CASH SAU CC)
            24 => 'LaPlata',
            25 => 'Nesosit',
            26 => 'Abandonat',
            27 => 'Compensat',
            30 => 'Aprobat',
            31 => 'Returnat',
        */

        //finale : inchis, compensat
        if(in_array($oldStatusRbs, [1, 27])) return false;
        //nepreluat -> in derulare, returnat, anulat, litigiu, pierdut, nesosit, abandonat
        if($oldStatusRbs == 10 && in_array($newStatusRbs, [0, 31, 5, 6, 7, 25, 26])) return true;
        //in derulare -> decontat, returnat, anulat, litigiu, pierdut, nesosit, abandonat
        if($oldStatusRbs == 0 && in_array($newStatusRbs, [23, 31, 5, 6, 7, 25, 26])) return true;
        //returnat -> inchis, in derulare, decontat
        if($oldStatusRbs == 31 && in_array($newStatusRbs, [1, 0, 23])) return true;
        //anulat, litigiu, pierdut, nesosit, abandonat -> decontat
        if(in_array($oldStatusRbs, [5, 6, 7, 25, 26]) && $newStatusRbs == 23) return true;
        //decontat -> validat
        if($oldStatusRbs == 23 && $newStatusRbs == 2) return true;
        //validat -> spre compensare, on hold, aprobat
        if($oldStatusRbs == 2 && in_array($newStatusRbs, [11, 21, 30])) return true;
        //on hold -> validat
        if($oldStatusRbs == 21 && $newStatusRbs == 2) return true;
        //spre compensare -> compensat, validat
        if($oldStatusRbs == 11 && in_array($newStatusRbs, [27, 2])) return true;
        
        //aprobat -> spre client, la plata
        if($oldStatusRbs == 30 && ($tip_plata == 0 && $newStatusRbs == 4 || $tip_plata == 3 && $newStatusRbs == 24)) return true;
        //spre client, la plata -> inchis
        if(($oldStatusRbs == 4 && $tip_plata == 0 || $oldStatusRbs == 24 && $tip_plata == 3 ) && $newStatusRbs == 1) return true;
        return false;
    }

    public static function getCkpFromOperatiune($status) {
        $ckp = 0;
        switch ($status) {
            case 1 :
                $ckp = 36;
                break;
            case 2: 
            case 4:
            case 5:
            case 6: 
            case 8: 
            case 9:
                $ckp = 13;
                break;
            case 3 :
                $ckp = 5;
                break;
            case 7 :
                $ckp = 9;
                break;
            case 10 :
            case 11 :
            case 12 :
            case 13 :
            case 14 :
                $ckp = 28;
                break;
            case 15 :
                $ckp = 37;
                break;
            case 16 :
            case 17 :
            case 20 :
            case 21 :
                $ckp = 20;
                break;
            case 18 :
                $ckp = 4;
                break;
            case 19 :
                $ckp = 34;
                break;
            default :
                $ckp = 20;
        }
        return $ckp;
    }
}