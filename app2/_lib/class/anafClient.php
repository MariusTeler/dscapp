<?php
/**
 * Implementare API ANAF
 */
class AnafClient
{
    /**
     * ANAF limit one times cui's
     */
    const ANAF_CUI_LIMIT = 500;
    /**
     * @var string
     */
    protected $apiUri = 'https://webservicesp.anaf.ro/api/PlatitorTvaRest/v9/tva';
    /**
     * CUI List
     *
     * @var array
     */
    protected $cuis = [];
    /**
     * Add more or one cui to list
     *
     * @param $fiscals
     * @param null $date
     * @return $this
     */
    public function addCui($fiscals, $date = null)
    {
        // If not have set date return today
        if(is_null($date)) {
            $date = date('Y-m-d');
        }
        
        if(!is_array($fiscals)) {
            $fiscals = [$fiscals];
        }
        foreach($fiscals as $cui) {
            // Keep only numbers from CUI
            $cui = preg_replace('/\D/', '', $cui);
            // Add cui to list
            $this->cuis[] = [
                "cui" => intval($cui),
                "data" => $date
            ];
        }
        return $this;
    }
    /**
     * Get results of request
     *
     * @return array
     */
    public function getResults()
    {
        $results = $this->callApi();
        if($results === false) return false;
        foreach($results as $company) {
            $company->date_generale->adresa = $this->parseAddress($company->date_generale->adresa);
        }
        return $results;
    }
    /**
     * Get first result
     *
     * @return object
     */
    public function getOneResult()
    {
        $results = $this->callApi();
        if($results === false) return false;
        $company = $results[0];
        //error_log(print_r($company, true));
        $company->date_generale->adresa = $this->parseAddress($company->date_generale->adresa);
        return $company;
    }
    
    /**
     * Call ANAF API
     *
     * @return array
     */
    private function callApi()
    {
        // Limit maxim numbers of cuis
        if(count($this->cuis) >= self::ANAF_CUI_LIMIT) {
            error_log('AnafClient : Poti verifica simultam pana la 500 de CUI-uri.');
            return false;
        }
        // Make request
        $curl = curl_init();
        $jsonPost = json_encode($this->cuis);
        curl_setopt_array($curl, array(
            CURLOPT_URL => $this->apiUri,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 10,
            CURLOPT_CUSTOMREQUEST => "POST",
            CURLOPT_POSTFIELDS => $jsonPost,
            CURLOPT_HTTPHEADER => array(
                "Content-Type: application/json",
                "Connection: keep-alive",
                "Cache-Control: no-cache"
            )
        ));
        $response = curl_exec($curl);
        $info = curl_getinfo($curl);
        curl_close($curl);
        // Check http code
        if (!isset($info['http_code']) || $info['http_code'] !== 200) {
            error_log("AnafClient : CURLOPT_URL: {$this->apiUri} | CURLOPT_POSTFIELDS : {$jsonPost}");
            return false;
        }
        // Get items
        $items = json_decode($response);
        // Check if have json because ANAF return errors in plain text
        if(json_last_error() !== JSON_ERROR_NONE) {
            error_log("AnafClient : Json parse error | Response body: {$response}");
            return false;
        }
        // Check success stats
        if (!is_array($items->found ?? []) || count($items->found) == 0) {
            error_log("AnafClient : Response body: {$response}");
            return false;
        }
        return $items->found;
    }
    /**
     * Parse company address
     *
     * @return object
     */
    private function parseAddress($raw)
    {
        // Check if raw is empty
        if(empty($raw)) {
            return $raw;
        }
        // Normal case from all uppercase
        $rawText = mb_convert_case($raw, MB_CASE_TITLE, 'UTF-8');
        setlocale(LC_CTYPE, 'en_US.UTF8');
        $rawText = iconv('UTF-8','ASCII//TRANSLIT//IGNORE',$rawText);
        //error_log($rawText);
        // Parse address
        $list = array_map('trim', explode(",", $rawText, 5));
        list($judet, $localitate, $strada, $numar, $altele) = array_pad($list, 5, '');
        // Parse county
        $judet = trim(str_replace(['Jud. ', 'Judet ', 'Judetul ', 'Municipiul ', 'Mun. '], '', $judet));
        // Parse city
        // parse sat x comuna y
        if(stripos($localitate, "Sat ") !== false && (stripos($localitate, "Comuna ") != false  || stripos($localitate, "Com. ") != false)) {
            if(stripos($localitate, "Com. ") < stripos($localitate, "Sat ") || stripos($localitate, "Comuna ") < stripos($localitate, "Sat ")) {
                $parts_localitate = explode("Sat ", $localitate);
                $localitate = trim(str_replace(['Com. ', 'Comuna '], '', $parts_localitate[0]));
            }
            else if(stripos($localitate, "Sat ") < stripos($localitate, "Com. ")) {
                $parts_localitate = explode("Com. ", $localitate);
                $localitate = trim($parts_localitate[1]);
            }
            else if(stripos($localitate, "Sat ") < stripos($localitate, "Comuna ")) {
                $parts_localitate = explode("Comuna ", $localitate);
                $localitate = trim($parts_localitate[1]);
            }
            else {
                error_log("debug Comuna Sat: ".$rawText);
            } 
        }  
        else if(stripos($localitate, "Loc. ") !== false && (stripos($localitate, "Ors. ") != false  || stripos($localitate, "Oras ") != false)) {
            if(stripos($localitate, "Loc. ") > stripos($localitate, "Ors. ") || stripos($localitate, "Loc. ") > stripos($localitate, "Oras ")) {
                $parts_localitate = explode("Loc. ", $localitate);
                $localitate = trim(str_replace(['Ors. ', 'Oras '], '', $parts_localitate[0]));
            }
            else if(stripos($localitate, "Loc. ") < stripos($localitate, "Ors. ")) {
                $parts_localitate = explode("Ors. ", $localitate);
                $localitate = trim($parts_localitate[1]);
            }
            else if(stripos($localitate, "Loc. ") < stripos($localitate, "Oras ")) {
                $parts_localitate = explode("Oras ", $localitate);
                $localitate = trim($parts_localitate[1]);
            }
            else {
                error_log("debug Ors. Loc.: ".$rawText);
            } 
        } 
        else if(stripos($localitate, "Loc. ") !== false && (stripos($localitate, "Mun. ") != false  || stripos($localitate, "Municipiul ") != false)) {
            if(stripos($localitate, "Loc. ") > stripos($localitate, "Mun. ") || stripos($localitate, "Loc. ") > stripos($localitate, "Municipiul ")) {
                $parts_localitate = explode("Loc. ", $localitate);
                $localitate = trim(str_replace(['Mun. ', 'Municipiul '], '', $parts_localitate[0]));
            }
            else if(stripos($localitate, "Loc. ") < stripos($localitate, "Mun. ")) {
                $parts_localitate = explode("Mun. ", $localitate);
                $localitate = trim($parts_localitate[1]);
            }
            else if(stripos($localitate, "Loc. ") < stripos($localitate, "Municipiul ")) {
                $parts_localitate = explode("Municipiul ", $localitate);
                $localitate = trim($parts_localitate[1]);
            }
            else {
                error_log("debug Mun. Loc.: ".$rawText);
            } 
        }   
        else if(stripos($localitate, "Sat ") !== false && (stripos($localitate, "Mun. ") != false  || stripos($localitate, "Municipiul ") != false)) {
            if(stripos($localitate, "Sat ") > stripos($localitate, "Mun. ") || stripos($localitate, "Sat ") > stripos($localitate, "Municipiul ")) {
                $parts_localitate = explode("Sat ", $localitate);
                $localitate = trim(str_replace(['Mun. ', 'Municipiul '], '', $parts_localitate[0]));
            }
            else if(stripos($localitate, "Sat ") < stripos($localitate, "Mun. ")) {
                $parts_localitate = explode("Mun. ", $localitate);
                $localitate = trim($parts_localitate[1]);
            }
            else if(stripos($localitate, "Sat ") < stripos($localitate, "Municipiul ")) {
                $parts_localitate = explode("Municipiul ", $localitate);
                $localitate = trim($parts_localitate[1]);
            }
            else {
                error_log("debug Mun. Sat: ".$rawText);
            } 
        }   
        else if(stripos($localitate, "Sat ") !== false && (stripos($localitate, "Ors. ") != false  || stripos($localitate, "Oras ") != false)) {
            if(stripos($localitate, "Sat ") > stripos($localitate, "Ors. ") || stripos($localitate, "Sat ") > stripos($localitate, "Oras ")) {
                $parts_localitate = explode("Sat ", $localitate);
                $localitate = trim(str_replace(['Ors. ', 'Oras '], '', $parts_localitate[0]));
            }
            else if(stripos($localitate, "Sat ") < stripos($localitate, "Ors. ")) {
                $parts_localitate = explode("Ors. ", $localitate);
                $localitate = trim($parts_localitate[1]);
            }
            else if(stripos($localitate, "Sat ") < stripos($localitate, "Oras ")) {
                $parts_localitate = explode("Oras ", $localitate);
                $localitate = trim($parts_localitate[1]);
            }
            else {
                error_log("debug Ors. Sat: ".$rawText);
            } 
        }
        
        $localitate = trim(str_replace(['Mun. ', 'Municipiul ', 'Ors. ', 'Oras ', 'Loc. ', 'Com. ', 'Comuna ', 'Sat '], '', $localitate));
        $localitate = trim(str_replace(['Sector 1', 'Sector 2', 'Sector 3', 'Sector 4', 'Sector 5', 'Sector 6'], 'Bucuresti', $localitate));
        
        // New object for address
        $address = new \stdClass;
        $address->raw = $rawText;
        $address->judet = $judet;
        $address->localitate = $localitate;
        $address->strada = trim($strada);
        $address->numar = trim($numar);
        $address->altele = trim($altele);
        return $address;
    }
}