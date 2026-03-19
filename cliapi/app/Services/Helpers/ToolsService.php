<?
namespace App\Services\Helpers;

use App\Dto\ExpeditieDto;

class ToolsService
{
    public static function getProcentTVA(string|null $data = null): int
    {
        if(!$data){
            $data = date("Y-m-d");
        }
        $procent = ExpeditieDto::PROC_TVA; // default value
        if(config('tva.proc_tva') && is_array(config('tva.proc_tva')) && count(config('tva.proc_tva')) > 0){
            foreach (config('tva.proc_tva') as $data_tva => $procTva){
                if(strtotime($data) >= strtotime($data_tva)){
                    $procent = $procTva;
                }
            }
        }
        else {
            error_log("getProcentTVA : Config procent_tva is not an array or is empty. Using default value: " . ExpeditieDto::PROC_TVA);
        }
        return $procent;
    }

    public static function sTrim_all(string|null $str , $with = ' ' ): string
    {
        return trim(preg_replace( "/[[:cntrl:][:space:]`~\\\<>^]+/" , $with , $str ?? ''));
    }

    public static function sSanitize(string|null $in): string 
    {
        return self::sTrim_all(self::sEncodeToUtf8($in ?? ''));
    }

    public static function sEncodeToUtf8(string|null $string): string 
    {
        return mb_convert_encoding($string ?? '', "UTF-8", mb_detect_encoding($string ?? '', "UTF-8, ISO-8859-1, ISO-8859-15", true));
    }

    public static function sSanitizeCleanEdges(string|null $in): string 
    {
        return self::cleanEdges(self::sSanitize($in ?? ''));
    }

    public static function sSanitizeCleanEdgesIconvTranslate($in) {
        return self::cleanEdges(self::sSanitize(self::iconvTranslate($in ?? '')));
    }
    
    public static function cleanEdges(string|null $str): string 
    {
        return preg_replace('/^[^a-zA-Z0-9]+/u', '', $str ?? '');
    }

    public static function iconvTranslate(string|null $string): string 
    {
        $string = self::sSanitize($string ?? '');
        setlocale(LC_CTYPE, 'en_US.UTF8');
        return iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $string);
    }

    public static function sqlDateToUi(string $str, bool $formatDot = true, bool $dateOnly = true): string 
    {
        $format = 'd.m.Y';
        if($dateOnly === false) $format = 'd.m.Y H:i';
        if($formatDot === false) {
            $format = 'd/m/Y';
            if($dateOnly === false) $format = 'd/m/Y H:i';
        }
        if(empty($str)) return date($format);
        $result = \DateTime::createFromFormat('Y-m-d H:i', $str);
        if($result === false) {
        	$result = \DateTime::createFromFormat('Y-m-d', $str);
        	if($result === false)
        		return date($format);
        	return $result->format($format);
        }
        return $result->format($format);
    }

    public static function uiDateToSql(string $str, bool $dateOnly = false): string 
    {
        $format = 'Y-m-d H:i:s';
        if($dateOnly === true) $format = 'Y-m-d';
        if(empty($str)) return date($format);
        $result = \DateTime::createFromFormat('d.m.Y H:i:s', $str);
        if($result === false) {
        	$result = \DateTime::createFromFormat('d.m.Y H:i', $str);
            if($result === false){
                if($result === false){
                    $result = \DateTime::createFromFormat('d/m/Y H:i:s', $str);
                    if($result === false) {
                        $result = \DateTime::createFromFormat('d/m/Y', $str);
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

    public static function isValidCod(string|int|null $cod): bool 
    {
        return self::isAwb($cod ?? '') || self::isPuisor($cod ?? '') || self::isCmnAwb($cod ?? '');
    }

    public static function isAwb(string|int|null $awb): bool 
    {
        return self::isAppAwb($awb ?? '') || self::isAndroidAwb($awb ?? '');
    }

    public static function isAppAwb(string|int|null $awb): bool 
    {
        return self::isSystemAwb($awb ?? '') || self::isMaravetAwb($awb ?? '');
    }

    public static function isSystemAwb(string|int|null $awb): bool 
    {
        /*
        expeditiile de sistem incep cu 7, 8, 9 pe 8 cifre
        */

        return preg_match(config('awb.regexp.awb.system'), $awb ?? '');
    }

    public static function isCmnAwb(string|int|null $awb): bool 
    {
        /*
        expeditiile din curiermanager ... old
        */

        return preg_match(config('awb.regexp.awb.cmn'), $awb ?? '');
    }

    public static function isOldSystemAwb(string|int|null $awb): bool 
    {
        /*
        expeditiile vechi incep cu 1 pe 7 cifre
        */

        return preg_match(config('awb.regexp.awb.old'), $awb ?? '');
    }

    public static function isAndroidAwb(string|int|null $awb): bool 
    {
        /*
        ntNoi de Android incep cu 3 pe 10 cifre
        */

        return preg_match(config('awb.regexp.awb.android'), $awb ?? '');
    }

    public static function isRosieAwb(string|int|null $awb): bool 
    {
        /*
        rosii care incep cu 3|4|5|6|7 pe 7 cifre
        rosii care incep cu 1 pe 9 cifre
        */
        return preg_match(config('awb.regexp.awb.rosie'), $awb ?? '');
    }

    public static function isMaravetAwb(string|int|null $awb): bool 
    {
        /*
        maravet incepe cu 290 | 291 pe 9 cifre
        */

        return preg_match(config('awb.regexp.awb.maravet'), $awb ?? '');
    }

    public static function isPuisor(string $cod): bool 
    {
        return self::isSystemPuisor($cod);
    }

    public static function isSystemPuisor(string|int|null $cod): bool 
    {
        /*
        puisorii system contin awb system sau android, sign minus, 3 digits
        */
        return preg_match(config('awb.regexp.awb.puisor'), $cod ?? '');
    }

    public static function getAwbFromPuisor(string $cod): int 
    {
        /*
        extrage awb din codul de puisor
        */
        if(self::isSystemPuisor($cod)){
            $parts = explode('-', $cod);
            return intval($parts[0]);
        }
        return 0;
    }

    public static function getPuisoriForAwb(string|int|null $awb, int $colete): array
    {
        /*
        returneaza un array cu puisorii pentru awb
        */
        $result = [];
        if(!self::isAwb($awb ?? '')) return $result;

        if($colete < 2 || $colete > 1000) return [$awb];
		for($i=2; $i <= $colete; $i++){
			$result[] = $awb.'-'.self::getPuisorNr($i);
		}
        return $result;
    }

    public static function getPuisorNr(int $nr): string 
    {
		if($nr < 10)
			return '00'.$nr;
		if($nr < 100)
			return '0'.$nr;
		return $nr;
	}

    public static function isValidTelefonNumber(string $telefon): bool 
    {
        /*
        07XXXXXXXX
        */
        return preg_match(config('awb.regexp.telefon'), $telefon);
    }

    public static function isValidCui(string $cui): bool 
    {
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

    public static function sanitizeCuiRO(string $input, bool $digitsOnly = false): string {
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

    public static function isValidCnp(string $cnpToValidate = ''): bool {
        //$cnp = new Cnp($cnpToValidate);
        //return $cnp->isValid();
        return true;
    }

    public static function uiToVolum(int $vol1 = 0, int $vol2 = 0, int $vol3 = 0): string 
    {
        if($vol1 > 0 && $vol2 > 0 && $vol3 > 0)
            return $vol1.'x'.$vol2.'x'.$vol3;
        return '';
    }

    public static function sqlToVolum1(string $volum = ''): int 
    {
        $volumes = explode("x",$volum);
        if(count($volumes) == 3) {
            return intval($volumes[0]);
        }
        return 0;
    }

    public static function sqlToVolum2(string $volum = ''): int 
    {
        $volumes = explode("x",$volum);
        if(count($volumes) == 3) {
            return intval($volumes[1]);
        }
        return 0;
    }

    public static function sqlToVolum3(string $volum = ''): int 
    {
        $volumes = explode("x",$volum);
        if(count($volumes) == 3) {
            return intval($volumes[2]);
        }
        return 0;
    }

    public static function getGreutateVolumetrica(int $vol1 = 0, int $vol2 = 0, int $vol3 = 0): int 
    {
        if($vol1 > 0 && $vol2 > 0 && $vol3 > 0)
            return ceil(($vol1*$vol2*$vol3)/6000);
        return 0;
    }

    public static function canModifyStatusRbs(int $oldStatusRbs, int $newStatusRbs, int $tip_plata = -1): bool 
    {
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

    public static function testLocalitateBucuresti(string $localitate):bool 
    {
        $localitate = mb_strtolower(self::sSanitizeCleanEdges($localitate));
        return 0 === strpos($localitate, 'bucuresti') || 0 === strpos($localitate, 'sector');
    }

    public static function excelRange(int $end): array
    {
		$end = intval($end);
		if($end > 100) return [];
		$letters = [];
		$letter = 'A';
		for($i=1; $i <= $end; $i++){
    		$letters[] = $letter++;
		}
		return $letters;
    }
}