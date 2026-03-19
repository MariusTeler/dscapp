<?php
namespace App\Services\Helpers;

use Geocoder\Query\GeocodeQuery;
use GuzzleHttp\Client as GuzzleAdapter;
use Geocoder\StatefulGeocoder;
use Geocoder\Provider\GoogleMaps\GoogleMaps;
use Geocoder\Provider\GoogleMaps\Model\GoogleAddress;
use Geocoder\Provider\GraphHopper\GraphHopper;
use Geocoder\Exception\Exception;
use Geocoder\Exception\QuotaExceeded;
use Geocoder\Exception\UnsupportedOperation;
use Geocoder\Exception\InvalidCredentials;
use Geocoder\Exception\InvalidServerResponse;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Exception\RequestException;
use Location\Coordinate;
use Location\Polygon;
use Location\Formatter\Coordinate\DecimalDegrees;
use Illuminate\Support\Facades\DB;
use \StdClass;

class CdsGeocoderService  {

    public static function geocode(int $clientId, bool $resetZonaId = false): array|bool 
    {
        $debug = false;
        if(empty($clientId)) return false;
        //check client  

        $client = DB::table('clienti as cl')
            ->select('cl.adresa', 'cl.cod_lc', 'lc.nume_lc', 'cl.zona_id', 'ce.geocode', 'ce.id as centruId', 'jd.nume_jd')
            ->where('cl.cod_cl', $clientId)
            ->join('localitati as lc', 'lc.cod_lc', '=', 'cl.cod_lc')
            ->join('centre as ce', 'ce.id', '=', 'lc.cod_centru')
            ->leftJoin('judete as jd', 'jd.cod_jd', '=', 'lc.cod_jd')
            ->first();

        //client not found
        if(!$client) {
            error_log("APP Geocode : No client found for this ID: {$clientId}" . PHP_EOL);
            return false;
        }
        if($debug)
            error_log("APP Geocode : Client found: {$clientId} - {$client->adresa}" . PHP_EOL);

        //reset zona_id if needed
        if($resetZonaId) $client->zona_id = 0;
        //client already has zona_id
        if($client->zona_id > 0) {
            if($debug)
                error_log("APP Geocode : Client already has zona_id: {$clientId} - {$client->zona_id}" . PHP_EOL);
            return true;
        }
        if($debug)
            error_log("APP Geocode : Client doesn't have zona_id: {$clientId} - {$client->zona_id}" . PHP_EOL);
        //centru client nu are geocodare activata
        if($client->geocode == 0) {
            if($debug)
                error_log("APP Geocode : No geocode for this client: {$clientId}" . PHP_EOL);
            return false;
        }
        if($debug)
            error_log("APP Geocode : Client has geocode: {$clientId} - {$client['geocode']}" . PHP_EOL);

        if($debug)
            error_log("APP Geocode : Address: {$client['adresa']} - {$client['cod_lc']} - {$client['nume_lc']}" . PHP_EOL);

        $row = DB::table('address_geocode')
            ->where('adresa', $client['adresa'])
            ->where('localitate_id', $client['cod_lc'])
            ->where('zona_id', '>', 0)
            ->first();

        if (!empty($row) && $row->zona_id > 0) {
            if($debug)
                error_log("APP Geocode : Address found in cache: {$client->adresa} - {$client->cod_lc} - {$row->zona_id}" . PHP_EOL);
            DB::table('clienti')->where('cod_cl', $clientId)->update(['zona_id' => $row->zona_id]);
            return true;
        }

        if($debug)
            error_log("APP Geocode : No cached address found: {$client->adresa} - {$client->cod_lc} - {$client->nume_lc}" . PHP_EOL);
        //get polygons for this centru
        $polygonZones = self::getPolygonsZones($client->centruId ?? 0);
        if(count($polygonZones) == 0) {
            if($debug)
                error_log("APP Geocode : No polygons found for this centru: {$client->centruId}" . PHP_EOL);
            return false;
        }
        if($debug)
            error_log("APP Geocode : Polygons found: " . count($polygonZones) . PHP_EOL);

        //normalize address
        $normalizedAddress = self::normalizeAddress($client->adresa);
        if($debug)
            error_log("APP Geocode : Normalized address: {$normalizedAddress}" . PHP_EOL);
        //ask google for coordinates
        $googleResult = self::geocodeWithGoogle($normalizedAddress, $client->nume_lc, $client->nume_jd, $debug);
        if($debug)
            error_log("APP Geocode : Google result: " . print_r($googleResult ?? (object)[], true) . PHP_EOL);
        //check localitate == bucuresti
        //daca nu pune zona_id 1 sau 2 in functie de centrul localitatii
        if ($googleResult && !empty($googleResult->localitate) 
            && strtoupper(preg_replace('/[^a-zA-Z0-9]/', '', $googleResult->localitate)) != strtoupper(preg_replace('/[^a-zA-Z0-9]/', '', $client->nume_lc))) {
            if(strtoupper($client->nume_lc) == 'BUCURESTI' || strtoupper($client->nume_lc) == 'BUCUREȘTI') {
                if($debug)
                    error_log("APP Geocode : Localitate is Bucuresti: {$googleResult->localitate}" . PHP_EOL);
                //cache la otopeni : zona Z0S0
                return self::cacheResultS0Z0($googleResult, $client->adresa, $client->cod_lc, $clientId, 1, $debug);
            }
            else
                //cache : zona Z0S0 sau Z0S1
                return self::cacheResultS0Z0($googleResult, $client->adresa, $client->cod_lc, $clientId, ($client->centruId == 47 ? 1 : 2), $debug);
        }
        if ($googleResult && in_array($googleResult->location_type, ['ROOFTOP', 'RANGE_INTERPOLATED'])) {
            if($debug)
                error_log ("Geocode : Google result accepted: {$googleResult->formatted}" . PHP_EOL);
            //update old address
            return self::cacheResult($googleResult, $polygonZones, $client->adresa, $client->cod_lc, $clientId, $debug);
        }

        //ask graphhopper for coordinates
        $graphHopperResult = self::geocodeWithGraphHopper($normalizedAddress, $client->nume_lc, $client->nume_jd, $debug);
        if($debug)
            error_log("APP Geocode : GraphHopper result: " . print_r($graphHopperResult ?? (object)[], true) . PHP_EOL);
        if ($graphHopperResult && in_array($graphHopperResult->location_type, ['ROOFTOP', 'RANGE_INTERPOLATED'])) {
            if($debug)
                error_log ("Geocode : GraphHopper result accepted: {$graphHopperResult->formatted}" . PHP_EOL);
            //update old address
            return self::cacheResult($graphHopperResult, $polygonZones, $client->adresa, $client->cod_lc, $clientId, $debug);
        }

        //cache : zona Z0S0 sau Z0S1
        $result = (object) [
            'formatted' => self::normalizeAddress($client->adresa),
            'lat' => 0,
            'lng' => 0,
            'provider' => 'none',
        ];
        return self::cacheResultS0Z0($result, $client->adresa, $client->cod_lc, $clientId, ($client->centruId == 47 ? 1 : 2), $debug);
    }

    private static function cacheResult(StdClass $result, array $polygonZones, string $adresa, int $localitateId, int $clientId, bool $debug = false): bool 
    {
        $formattedAddress = $result->formatted;
        $latitude = $result->lat;
        $longitude = $result->lng;
        $provider = $result->provider;
        $locationType = $result->location_type;
        $adresaPoint = new Coordinate($latitude, $longitude);

        //check if the coordinates are inside the polygons
        foreach($polygonZones as $zoneId => $polygon) {
            if($polygon->contains($adresaPoint)) {
                if($debug)
                    error_log("APP Geocode : Address is inside polygon: {$zoneId}" . PHP_EOL);
                self::insertAddress($adresa, $formattedAddress, $localitateId, $latitude, $longitude, $zoneId, $provider, $locationType);
                try {
                    $updated = DB::table('clienti')->where('cod_cl', $clientId)->update(['zona_id' => $zoneId]);
                } catch (\Exception $e) {
                    return false;
                }
                return $updated > 0;
            }
        }
        if($debug)
            error_log("APP Geocode : Address " .$adresaPoint->format(new DecimalDegrees(', ')). " is not inside any polygon" . PHP_EOL);
        return false;
    }

    private static function cacheResultS0Z0(StdClass $result, string $adresa, int $localitateId, int $clientId, int $zoneId, bool $debug = false): bool 
    {
        self::insertAddress($adresa, $result->formatted, $localitateId, $result->lat, $result->lng, $zoneId, $result->provider, 'ERROR');
        try {
            $updated = DB::table('clienti')->where('cod_cl', $clientId)->update(['zona_id' => $zoneId]);
        } catch (\Exception $e) {
            return false;
        }
        return $updated > 0;
    }

    private static function insertAddress(string $adresa, string $formattedAddress, int $localitateId, float $lat, float $lng, int $zoneId = 0, string $provider, string $locationType = ''): int 
    {
        if(empty($adresa) || $zoneId == 0 || $localitateId == 0) return false;
        $addressId = DB::table('address_geocode')->insertGetId([
            'adresa' => $adresa,
            'localitate_id' => $localitateId,
            'formatted_adresa' => $formattedAddress,
            'lat' => $lat,
            'lng' => $lng,
            'provider' => $provider,
            'neatness' => $locationType,
            'zona_id' => $zoneId
        ]);
        return intval($addressId);
    }

    private static function getPolygonsZones(int $centruId = 0): array 
    {
        $polygonZones = [];
        $zones = DB::table('zones')
            ->where('centru_master_id', $centruId)
            ->orWhere('centru_id', $centruId)
            ->select('id', 'coordinates')
            ->get();
        if ($zones->count()) {
            foreach ($zones as $row) {
                $coordinates = json_decode($row->coordinates);
                if (is_array($coordinates) && count($coordinates) > 0) {
                    $polygonZones[$row->id] = new Polygon();
                    foreach($coordinates as $coordinate) {
                        if(count($coordinate) != 2) {
                            error_log("APP Geocode : Invalid coordinates: " . print_r($coordinate, true) . PHP_EOL);
                            continue;
                        }
                        $polygonZones[$row->id]->addPoint(new Coordinate($coordinate[0],$coordinate[1]));
                    }
                }
            }
        }
        return $polygonZones;
	}

    private static function geocodeWithGoogle(string $adresa, string $localitate, string $judet, bool $debug = false): StdClass|null
    {
        if(empty(config('geocoder.google_api_key'))) {
            error_log("APP Geocode : Google API key is not set." . PHP_EOL);
            return null;
        }
        try {
            $httpClient = new GuzzleAdapter();
            $provider = new GoogleMaps($httpClient, 'RO', config('geocoder.google_api_key'));
            $geocoder = new StatefulGeocoder($provider, 'ro');

            $results = $geocoder->geocodeQuery(GeocodeQuery::create("{$adresa}, {$localitate}, {$judet}, RO"));

            if($debug) {
                error_log("APP Geocode : Google result: {$adresa} - {$localitate} - " . count($results) . PHP_EOL);
            }
            
            foreach ($results as $result) {
                /** @var GoogleAddress $googleAddress */
                $googleAddress = $result;
                return (object) [
                    'adresa' => $adresa,
                    'formatted' => $googleAddress->getFormattedAddress() ?? $adresa,
                    'localitate' => iconv('UTF-8','ASCII//TRANSLIT', $googleAddress->getLocality() ?? ''),
                    'lat' => $googleAddress->getCoordinates()->getLatitude(),
                    'lng' => $googleAddress->getCoordinates()->getLongitude(),
                    'provider' => 'google',
                    'location_type' => $googleAddress->getLocationType() ?? null,
                ];
            }
        } catch (QuotaExceeded $e) {
            error_log("APP Geocode exception : You have exceeded your quota : {$adresa}");
        } catch (UnsupportedOperation $e) {
            error_log("APP Geocode exception : This operation is not supported : {$adresa}");
        } catch (InvalidCredentials $e) {
            error_log("APP Geocode exception : Your credentials are invalid : {$adresa}");
        } catch (InvalidServerResponse $e) {
            error_log("APP Geocode exception : The server response is invalid : {$adresa}");
        } catch (Exception $e) {
            error_log("APP Geocode exception : {$adresa} : ". $e->getMessage());
        } catch (ConnectException $e) {
            error_log("APP GuzzleHttp exception : ". $e->getMessage());
        } catch (RequestException $e) {
            error_log("APP GuzzleHttp exception : ". $e->getMessage());
        } catch (\Exception $e) {
            error_log("APP unknown exception : ". $e->getMessage());
        }

        return null;
    }

    private static function geocodeWithGraphHopper(string $adresa, string $localitate, string $judet, bool $debug = false): StdClass|null
    {
        if(empty(config('geocoder.graph_api_key'))) {
            error_log("APP Geocode : GraphHopper API key is not set." . PHP_EOL);
            return null;
        }
        try {
            $httpClient = new GuzzleAdapter();
            $provider = new GraphHopper($httpClient, config('geocoder.graph_api_key'));
            $geocoder = new StatefulGeocoder($provider, 'ro');

            $results = $geocoder->geocodeQuery(GeocodeQuery::create("{$adresa}, {$localitate}, {$judet}, RO"));
            if($debug)
                error_log("APP Geocode : GraphHopper result: {$adresa} - {$localitate} - " . count($results) . PHP_EOL);
            foreach ($results as $result) {
                return (object) [
                    'adresa' => $adresa,
                    'formatted' => self::getGraphHopperFormattedAddress($result),
                    'localitate' => iconv('UTF-8','ASCII//TRANSLIT', method_exists($result, 'getLocality') ? $result->getLocality() ?? '' : ''),
                    'lat' => $result->getCoordinates()->getLatitude(),
                    'lng' => $result->getCoordinates()->getLongitude(),
                    'provider' => 'graphhopper',
                    'location_type' => self::estimateGraphHopperPrecision($result)
                ];
            }
        } catch (QuotaExceeded $e) {
            error_log("APP Geocode exception : You have exceeded your quota : {$adresa}");
        } catch (UnsupportedOperation $e) {
            error_log("APP Geocode exception : This operation is not supported : {$adresa}");
        } catch (InvalidCredentials $e) {
            error_log("APP Geocode exception : Your credentials are invalid : {$adresa}");
        } catch (InvalidServerResponse $e) {
            error_log("APP Geocode exception : The server response is invalid : {$adresa}");
        } catch (Exception $e) {
            error_log("APP Geocode exception : {$adresa} : ". $e->getMessage());
        } catch (ConnectException $e) {
            error_log("APP GuzzleHttp exception : ". $e->getMessage());
        } catch (RequestException $e) {
            error_log("APP GuzzleHttp exception : ". $e->getMessage());
        } catch (\Exception $e) {
            error_log("APP unknown exception : ". $e->getMessage());
        }

        return null;
    }

    private static function estimateGraphHopperPrecision($result)
    {
        if ($result->getStreetName() && $result->getStreetNumber()) {
            return 'ROOFTOP';
        } elseif ($result->getStreetName()) {
            return 'RANGE_INTERPOLATED';
        } else {
            return 'UNKNOWN';
        }
    }

    private static function getGraphHopperFormattedAddress($result)
    {
        $formatted = '';
        if ($result->getStreetName()) {
            $formatted .= $result->getStreetName();
        }
        if ($result->getStreetNumber()) {
            $formatted .= ' ' . $result->getStreetNumber();
        }
        if ($result->getPostalCode()) {
            $formatted .= ', ' . $result->getPostalCode();
        }
        if ($result->getLocality()) {
            $formatted .= ', ' . $result->getLocality();
        }
        return trim($formatted);
    }

    private static function getGoogleFormattedAddress(GoogleAddress $result): string
    {
        $formatted = '';
        if ($result->getStreetName()) {
            $formatted .= $result->getStreetName();
        }
        if ($result->getStreetNumber()) {
            $formatted .= ' ' . $result->getStreetNumber();
        }
        if ($result->getPostalCode()) {
            $formatted .= ', ' . $result->getPostalCode();
        }
        if ($result->getLocality()) {
            $formatted .= ', ' . $result->getLocality();
        }
        return trim($formatted) ?: $result->getFormattedAddress() ?? '';
    }

    private static function normalizeAddress($adresa) {
        // Step 1: Normalize SECTOR variants
        $adresa = preg_replace('/\bSECT(?:ORUL|OR|R)?[\.\s]*?(\d+)\b/i', 'SECTOR $1 ', $adresa);
        // Step 2: Normalize NR variants
        $adresa = preg_replace('/\bNR[\.\s]*([0-9]+[a-zA-Z]*)\b/i', 'NR $1', $adresa);
        // Step 3: Normalize other common abbreviations
        $replacements = [
            '/\bSTR(?:ADA|AD|D)?[\.\s]*\b/i' => 'Strada ',
            '/\bSOS(?:EA|EAUA)?[\.\s]*\b/i' => 'Soseaua ',
            '/\bBD[\.\s]*\b/i' => 'Bulevardul ',
            '/\bBLVD[\.\s]*\b/i' => 'Bulevardul ',
            '/\bBL(?:OC|OCUL|OCU)[\.\s]*\b/i' => 'Bloc ',
            '/\bSC(?:ARA|AR)[\.\s]*\b/i' => 'Scara ',
            '/\bAP(?:T|PT)[\.\s]*\b/i' => 'Apartament ',
            '/\bCAM(?:ERA|ARA)[\.\s]*\b/i' => 'Camera ',
            '/\bJUD(?:ET|ETUL)[\.\s]*\b/i' => 'Județ',
            '/\bCP\b/i' => 'Cod poștal'
        ];

        $adresa = preg_replace(array_keys($replacements), array_values($replacements), $adresa);

        // Clean extra spaces
        $adresa = preg_replace('/\s+/', ' ', $adresa);
        $adresa = trim($adresa);

        return $adresa;
    }

    private static function normalize_and_parse_address($input) {
        // Normalize abbreviations
        $patterns = [
            '/\bSECT(?:ORUL|OR)?(\d+)\b/i' => 'SECTOR $1',
            '/\bNR\.?(\d+)\b/i' => 'NR $1',
            '/\bSTR\.?\b/i' => 'Strada',
            '/\bBD\b/i' => 'Bulevardul',
            '/\bBLVD\b/i' => 'Bulevardul',
            '/\bBL\.?\b/i' => 'Bloc',
            '/\bSC\.?\b/i' => 'Scara',
            '/\bAP\.?\b/i' => 'Apartament',
            '/\bCAM\.?\b/i' => 'Camera',
            '/\bJUD\.?\b/i' => 'Județ',
            '/\bCP\b/i' => 'Cod poștal',
        ];

        $normalized = preg_replace(array_keys($patterns), array_values($patterns), $input);
        $normalized = preg_replace('/\s+/', ' ', $normalized);
        $normalized = trim($normalized);

        // Initialize result
        $result = [
            'street_type' => null,
            'street_name' => null,
            'number' => null,
            'sector' => null,
            'bloc' => null,
            'scara' => null,
            'apartament' => null,
            'county' => null,
            'postal_code' => null,
        ];

        // Extract values
        if (preg_match('/\b(Bulevardul|Strada|Calea|Aleea|Drumul)\s+([A-ZĂÂÎȘȚa-zăâîșț0-9\s\-]+)/u', $normalized, $m)) {
            $result['street_type'] = $m[1];
            $result['street_name'] = trim($m[2]);
        }
        if (preg_match('/\bNR\s+(\d+)/i', $normalized, $m)) {
            $result['number'] = $m[1];
        }
        if (preg_match('/\bSECTOR\s+(\d+)/i', $normalized, $m)) {
            $result['sector'] = $m[1];
        }
        if (preg_match('/\bBloc\s+(\w+)/i', $normalized, $m)) {
            $result['bloc'] = $m[1];
        }
        if (preg_match('/\bScara\s+(\w+)/i', $normalized, $m)) {
            $result['scara'] = $m[1];
        }
        if (preg_match('/\bApartament\s+(\w+)/i', $normalized, $m)) {
            $result['apartament'] = $m[1];
        }
        if (preg_match('/\bJudeț\s+([A-ZĂÂÎȘȚa-zăâîșț\-]+)/u', $normalized, $m)) {
            $result['county'] = $m[1];
        }
        if (preg_match('/\bCod poștal\s+(\d{6})/u', $normalized, $m)) {
            $result['postal_code'] = $m[1];
        }

        return $result;
    }

    private static function validate_parsed_address(array $address): array {
        $errors = [];

        // 1. Street type
        $valid_street_types = ['Strada', 'Bulevardul', 'Calea', 'Aleea', 'Drumul'];
        if (!in_array($address['street_type'], $valid_street_types)) {
            $errors[] = "Tipul străzii este invalid.";
        }

        // 2. Street name
        if (empty($address['street_name'])) {
            $errors[] = "Numele străzii lipsește.";
        }

        // 3. House number
        if (!empty($address['number']) && !ctype_digit($address['number'])) {
            $errors[] = "Numărul străzii trebuie să fie numeric.";
        }

        // 4. Sector (only if present)
        if (!empty($address['sector']) && (!ctype_digit($address['sector']) || (int)$address['sector'] < 1 || (int)$address['sector'] > 6)) {
            $errors[] = "Sectorul trebuie să fie între 1 și 6 (doar pentru București).";
        }

        // 5. County (Județ)
        $judete = [
            'Alba', 'Arad', 'Argeș', 'Bacău', 'Bihor', 'Bistrița-Năsăud', 'Botoșani', 'Brașov', 'Brăila', 'Buzău',
            'Caraș-Severin', 'Călărași', 'Cluj', 'Constanța', 'Covasna', 'Dâmbovița', 'Dolj', 'Galați', 'Giurgiu',
            'Gorj', 'Harghita', 'Hunedoara', 'Ialomița', 'Iași', 'Ilfov', 'Maramureș', 'Mehedinți', 'Mureș', 'Neamț',
            'Olt', 'Prahova', 'Satu Mare', 'Sălaj', 'Sibiu', 'Suceava', 'Teleorman', 'Timiș', 'Tulcea', 'Vaslui',
            'Vâlcea', 'Vrancea', 'București'
        ];
        if (!empty($address['county']) && !in_array(ucfirst(strtolower($address['county'])), $judete)) {
            $errors[] = "Județul este invalid.";
        }

        // 6. Postal code
        if (!empty($address['postal_code']) && !preg_match('/^\d{6}$/', $address['postal_code'])) {
            $errors[] = "Codul poștal trebuie să conțină exact 6 cifre.";
        }

        return $errors;
    }
}
