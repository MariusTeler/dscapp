<?php
namespace App\Dto;

use App\Services\Helpers\ToolsService;

class ExpeditieDto {
    const KM_LIMIT = 15;
    const TIP_EXPEDITIE = [ 0=>'Initiala', 1=>'Retur NT', 2=>'Retur Doc', 3=>'Ramburs',4=>'Interna',5=>'Returnare',6=>'Retur ambalaj', 7=>'Retur colet', 33=>'Borderou RBS cash'];
    const TIP_OBJ = [1=>'PLIC', 2=>'COLET', 3=>'PALET'];
    const MOD_PLATA = [ 0=>'Per NT', 1=>'Factura periodica', 3=>'barter'];
    const RBS_TIP_PLATA = [ 0=>'cash', 1=>'bo', 2=>'cec', 3=>'cont'];
    const CONTRACT = [0 => 'Nu', 1 => 'T. negociat', 2 => 'T. lista'];
    const TIP_TARIF_DET = [0 => 'Local', 1 => 'National'];
    const MONEDA = [1=>'LEI' ,2=>'EUR' ,3=>'USD'];
    const MONEDA_REV = ['LEI' => 1 ,'EUR' => 2 , 'USD' => 3];
    const DEFAULT_KG_RET_AMB = 3.00;
    const DEFAULT_TARIF_OPEN = 20.00;
    const PROC_TVA = 19;

    const MIN_COLETE = 1;
    const MAX_COLETE = 99;
    const MIN_KG_COLET = 1;
    const MIN_KG_PALET = 10;
    const MAX_KG_COLET = 10000;
    const MAX_KG_PALET = 10000;
    const MAX_ASIGURARE = 15000;
    const MAX_RAMBURS = 15000;

    const MIN_BO_RBS_CASH = 3; //minim de expeditii pentru a crea o consolidare ramburs cash
    const TIP_EXP_BO_RBS_CASH = 33; //tipul de expeditie pentru borderoun ramburs cash

    public static function uiToSql($uiExp = [], $expeditor_cc){
        $result = [];

        $result['swapped'] = intval(ToolsService::sSanitize($uiExp['SWAPPED'] ?? 0));

        $result['destinatar_nume'] = (!empty(ToolsService::sSanitizeCleanEdges($uiExp['DESTINATAR_NUME'] ?? "")) ? strtoupper(ToolsService::sSanitizeCleanEdges($uiExp['DESTINATAR_NUME'])):"");
		$result['destinatar_id'] = intval(ToolsService::sSanitize($uiExp['DESTINATAR_ID'] ?? 0));
		$result['destinatar_localitate_id'] = intval(ToolsService::sSanitize($uiExp['DESTINATAR_LOCALITATE_ID'] ?? 0));
		$result['destinatar_localitate'] = (!empty(ToolsService::sSanitizeCleanEdges($uiExp['DESTINATAR_LOCALITATE'] ?? "")) ? strtoupper(ToolsService::sSanitizeCleanEdges($uiExp['DESTINATAR_LOCALITATE'])):"");
		$result['destinatar_contact'] = (!empty(ToolsService::sSanitizeCleanEdges($uiExp['DESTINATAR_CONTACT'] ?? "")) ? strtoupper(ToolsService::sSanitizeCleanEdges($uiExp['DESTINATAR_CONTACT'])):"");
		$result['destinatar_telefon'] = ((!empty(ToolsService::sSanitize($uiExp['DESTINATAR_TELEFON'] ?? "")) && strtoupper(ToolsService::sSanitize($uiExp['DESTINATAR_TELEFON']))!='TELEFON')?strtoupper(ToolsService::sSanitize($uiExp['DESTINATAR_TELEFON'])):'');
		$result['destinatar_adresa'] = (!empty(ToolsService::sSanitizeCleanEdges($uiExp['DESTINATAR_ADRESA'] ?? "")) ? strtoupper(ToolsService::sSanitizeCleanEdges($uiExp['DESTINATAR_ADRESA'])):"");
        $result['km_livrare'] = intval(ToolsService::sSanitize($uiExp['DESTINATAR_LOCALITATE_KM'] ?? 0));
        $result['expeditor_nume'] = (!empty(ToolsService::sSanitize($uiExp['EXPEDITOR_NUME'] ?? "")) ? strtoupper(ToolsService::sSanitize($uiExp['EXPEDITOR_NUME'])):"");
		$result['expeditor_id'] = intval(ToolsService::sSanitize($uiExp['EXPEDITOR_ID'] ?? 0));
		$result['expeditor_localitate_id'] = intval(ToolsService::sSanitize($uiExp['EXPEDITOR_LOCALITATE_ID'] ?? 0));
		$result['expeditor_localitate'] = (!empty(ToolsService::sSanitizeCleanEdges($uiExp['EXPEDITOR_LOCALITATE'] ?? "")) ? strtoupper(ToolsService::sSanitizeCleanEdges($uiExp['EXPEDITOR_LOCALITATE'])):"");
		$result['expeditor_contact'] = (!empty(ToolsService::sSanitizeCleanEdges($uiExp['EXPEDITOR_CONTACT'] ?? "")) ? strtoupper(ToolsService::sSanitizeCleanEdges($uiExp['EXPEDITOR_CONTACT'])):"");
		$result['expeditor_telefon'] = ((!empty(ToolsService::sSanitize($uiExp['EXPEDITOR_TELEFON'] ?? "")) && strtoupper(ToolsService::sSanitize($uiExp['EXPEDITOR_TELEFON']))!='TELEFON')?strtoupper(ToolsService::sSanitize($uiExp['EXPEDITOR_TELEFON'])):'');
		$result['expeditor_adresa'] = (!empty(ToolsService::sSanitizeCleanEdges($uiExp['EXPEDITOR_ADRESA'] ?? "")) ? strtoupper(ToolsService::sSanitizeCleanEdges($uiExp['EXPEDITOR_ADRESA'])):"");
        $result['km_preluare'] = intval(ToolsService::sSanitize($uiExp['EXPEDITOR_LOCALITATE_KM'] ?? 0));

        $result['platitor'] = intval(ToolsService::sSanitize($uiExp['platitor'] ?? 1));

        //tip expeditie
        $result['tip_obj'] = 2;
		$result['greutate'] = round(floatval(ToolsService::sSanitize($uiExp['GREUTATE'] ?? 1)), 3);
		$result['piese'] = intval(ToolsService::sSanitize($uiExp['COLETE'] ?? 1));

        $result['greutate_vol'] = 0.000;
        $result['volum'] = '';
        $result['volum1'] = intval(ToolsService::sSanitize($uiExp['VOLUM1'] ?? 0));
        $result['volum2'] = intval(ToolsService::sSanitize($uiExp['VOLUM2'] ?? 0));
        $result['volum3'] = intval(ToolsService::sSanitize($uiExp['VOLUM3'] ?? 0));
        if($result['volum1'] > 0 && $result['volum2'] > 0 && $result['volum3'] > 0){
			$result['volum'] = ToolsService::uiToVolum($result['volum1'], $result['volum2'], $result['volum3']);
            $result['greutate_vol'] = ToolsService::getGreutateVolumetrica($result['volum1'], $result['volum2'], $result['volum3']);
        }

        if($result['tip_obj'] == 3){
			$result['piese'] = 1;
        }
        else if($result['tip_obj'] == 1){
            $result['greutate'] = 0.500;
			$result['piese'] = 1;
            $result['volum'] = "";
            $result['greutate_vol'] = 0.000;
        }

        //if($result['tip_obj'] = 'COLET' && $result['greutate'] < ToolsService::MIN_KG_COLET) $result['greutate'] = ToolsService::MIN_KG_COLET;

        $result['ret_nt'] = $result['ret_doc'] = $result['ret_amb'] = $result['ret_colet'] = $result['ret_nc'] = $result['liv_samb'] = $result['liv_sed'] = $result['sms'] = $result['copen'] = 0;

        if(!empty(ToolsService::sSanitize($uiExp['RET_NT'] ?? 0)))
            $result['ret_nt']=1;

        if(!empty(ToolsService::sSanitize($uiExp['RET_DOC'] ?? 0)))
            $result['ret_doc']=1;

        if(!empty(ToolsService::sSanitize($uiExp['RET_AMB'] ?? 0)))
            $result['ret_amb']=1;

        if(!empty(ToolsService::sSanitize($uiExp['RET_COLET'] ?? 0)))
            $result['ret_colet']=1;

        if(!empty(ToolsService::sSanitize($uiExp['RET_NC'] ?? 0)))
            $result['ret_nc']=1;

        if(!empty(ToolsService::sSanitize($uiExp['LIV_SAMB'] ?? 0)))
            $result['liv_samb']=1;

        if(!empty(ToolsService::sSanitize($uiExp['LIV_SED'] ?? 0)))
            $result['liv_sed']=1;

        if(!empty(ToolsService::sSanitize($uiExp['SMS'] ?? 0)))
            $result['sms'] = -1;

        if(!empty(ToolsService::sSanitize($uiExp['COPEN'] ?? 0)))
            $result['copen']=1;

        $result['valoare_asigurata'] = round(floatval(ToolsService::sSanitize($uiExp['asigurare'] ?? 0.00)), 2);
        $result['ramburs'] = round(floatval(ToolsService::sSanitize($uiExp['ramburs'] ?? 0.00)), 2);
        $result['tip_plata'] = intval(ToolsService::sSanitize($uiExp['tip_plata'] ?? 0));
        
        $result['detalii_doc'] = ToolsService::sSanitizeCleanEdges($uiExp['detalii_doc'] ?? '');
		$result['observatii'] = ToolsService::sSanitizeCleanEdges($uiExp['observatii'] ?? '');

        //tip tarif
        $result['tip_tarif'] = intval($result['expeditor_localitate_id'] != $result['destinatar_localitate_id']);
        //tip plata ramburs
        if(!empty($expeditor_cc) && $result['tip_plata'] == 0)
            $result['tip_plata'] = 3;
        //var_dump($result);

        return $result;
    }

    public static function sqlToPdf(array $sqlExp = []): array 
    {
        if($sqlExp === false || !is_array($sqlExp) || count($sqlExp) == 0) return [];

        $ret = $sqlExp;

		$ret['greutate'] = round($sqlExp['greutate'], 3);
        $ret['greutate_vol'] = round($sqlExp['greutate_vol'],3);

 		if($sqlExp['asigurare'] > 0){
			$ret['asigurare'] = round($sqlExp['asigurare'], 2);
		}

        if($sqlExp['ramburs'] > 0){
        	$ret['ramburs'] = round($sqlExp['ramburs'], 2);
        }

        $ret['valoare_fara_tva'] = round($sqlExp['valoare_fara_tva'], 2);
        $ret['valoare_tva'] = round($sqlExp['valoare_tva'], 2);
    
        if(empty($sqlExp['moneda'])) $sqlExp['moneda'] = 1;
		$ret['moneda'] = self::MONEDA[$sqlExp['moneda']] ?? $sqlExp['moneda'];

        if(!ToolsService::isValidCui($sqlExp['expeditor_cui'])) {
            $ret['expeditor_cui'] = $ret['expeditor_j'] = "";
        }

        return $ret;
    }
}