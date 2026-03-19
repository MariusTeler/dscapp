<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use App\Services\Helpers\ToolsService;
use App\Services\ExpeditiiService;
use App\Services\Print\PrintService;
use App\Services\AwbService;
use App\Services\BorderouService;
use App\Services\Print\BorderouPdfService;

class GetController extends Controller
{
    private $payload;

    public function __construct(
        private readonly AwbService $awbService,
        private readonly ExpeditiiService $expeditiiService,
        private readonly BorderouService $borderouService,
        private readonly PrintService $printService,
        private readonly BorderouPdfService $borderouPdfService,
    )
    {
        $this->payload = new \StdClass();
    }
    /**
     * Get list of counties (judete).
     */
    public function getJudete(Request $request): JsonResponse
    {
        $user = $request->user;

        if(empty($user) || ($user['expeditor_id'] ?? 0) == 0) {
            return response()->json(['message' => 'Access denied'], 401)
                             ->header('WWW-Authenticate', 'Basic realm="API"');
        }
        
        $this->payload->rezultate = 0;
        $this->payload->judete = [];
        try {
            DB::table('judete')->select('cod_jd', 'nume_jd')->orderBy('cod_jd')->get()->each(function($item) {
                $this->payload->judete[$item->cod_jd] = $item->nume_jd;
            });
            $this->payload->rezultate = count($this->payload->judete);
        }
        catch (\Exception $e) {
            Log::error("Error fetching counties: " . $e->getMessage());
            $this->payload->message = "Error database";
            return $this->echoResponse(500);   
        }

        return $this->echoResponse(200);
    }

    /**
     * Get localities for a specific county.
     */
    public function getLocalitati(Request $request, string $codJudet): JsonResponse
    {
        $user = $request->user;
        if(empty($user) || ($user['expeditor_id'] ?? 0) == 0) {
            return response()->json(['message' => 'Access denied'], 401)
                             ->header('WWW-Authenticate', 'Basic realm="API"');
        }
        $codJudet = ToolsService::sSanitizeCleanEdgesIconvTranslate($codJudet);
        if(empty($codJudet)) {
            $this->payload->message = "Cod judet invalid : {$codJudet}";
 			return $this->echoResponse(400);
        }
        $this->payload->rezultate = 0;
        $this->payload->localitati = [];
        try {
            DB::table('localitati')->select('nume_lc')->orderBy('nume_lc')->where('cod_jd', $codJudet)->get()->each(function($item) {
                $this->payload->localitati[] = $item->nume_lc;
            });
            $this->payload->rezultate = count($this->payload->localitati);
        }
        catch (\Exception $e) {
            Log::error("Error fetching localities for county code {$codJudet}: " . $e->getMessage());
            $this->payload->message = "Error database";
            return $this->echoResponse(500);   
        }

        return $this->echoResponse(200);
    }

    /**
     * Get localities with km for a specific county.
     */
    public function getLocalitatiKm(Request $request, string $codJudet): JsonResponse
    {
        $user = $request->user;
        if(empty($user) || ($user['expeditor_id'] ?? 0) == 0) {
            return response()->json(['message' => 'Access denied'], 401)
                             ->header('WWW-Authenticate', 'Basic realm="API"');
        }
        $codJudet = ToolsService::sSanitizeCleanEdgesIconvTranslate($codJudet);
        if(empty($codJudet)) {
            $this->payload->message = "Cod judet invalid : {$codJudet}";
 			return $this->echoResponse(400);
        }
        $this->payload->rezultate = 0;
        $this->payload->localitati = [];

        try {
            DB::table('localitati')->select(DB::raw("CONCAT(nume_lc,':',FORMAT(dist_km,0)) as localitate"))->orderBy('nume_lc')->where('cod_jd', $codJudet)->get()->each(function($item) {
                $this->payload->localitati[] = $item->localitate;
            });
            $this->payload->rezultate = count($this->payload->localitati);
        }
        catch (\Exception $e) {
            Log::error("Error fetching localities with km for county code {$codJudet}: " . $e->getMessage());
            $this->payload->message = "Error database";
            return $this->echoResponse(500);
        }

        return $this->echoResponse(200);
    }

    /**
     * Track AWB status.
     */
    public function trackAWB(Request $request, int $awb): JsonResponse
    {
        $user = $request->user;
        if(empty($user) || ($user['expeditor_id'] ?? 0) == 0) {
            return response()->json(['message' => 'Access denied'], 401)
                             ->header('WWW-Authenticate', 'Basic realm="API"');
        }

        $this->insertLog($request->uri()->path(), $awb, $user['id'], $request->ip());    
        
        if(!ToolsService::isAppAwb($awb)) {
            $this->payload->message = "Numar de awb eronat : {$awb}";
 			return $this->echoResponse(400);
        }

        try {
            $status = $this->awbService->trackAwb($user, $awb);
            if(count($status) == 0) {
                $this->payload->message = "Expeditie inexistenta : {$awb}";
 			    return $this->echoResponse(404);
            }
            $this->payload = $status;
            return $this->echoResponse(200);
        } catch (\Exception $e) {
            $this->payload->message = "Error database";
            return $this->echoResponse(500);   
        }
    }

    /**
     * Get AWB history.
     */
    public function historyAWB(Request $request, int $awb): JsonResponse
    {
        $user = $request->user;
        if(empty($user) || ($user['expeditor_id'] ?? 0) == 0) {
            return response()->json(['message' => 'Access denied'], 401)
                             ->header('WWW-Authenticate', 'Basic realm="API"');
        }

        $this->insertLog($request->uri()->path(), $awb, $user['id'], $request->ip());    
        
        if(!ToolsService::isAppAwb($awb)) {
            $this->payload->message = "Expeditie inexistenta : {$awb}";
 			return $this->echoResponse(400);
        }

        try {
            $status = $this->awbService->historyAwb($user, $awb);
            if(count($status) == 0) {
                $this->payload->message = "Expeditie inexistenta : {$awb}";
 			    return $this->echoResponse(404);
            }
            $this->payload = $status;
            return $this->echoResponse(200);
        } catch (\Exception $e) {
            $this->payload->message = "Error database";
            return $this->echoResponse(500);   
        }
    }

    /**
     * Track AWB return status.
     */
    public function trackAWBRetur(Request $request, int $awb): JsonResponse
    {
        $user = $request->user;
        if(empty($user) || ($user['expeditor_id'] ?? 0) == 0) {
            return response()->json(['message' => 'Access denied'], 401)
                             ->header('WWW-Authenticate', 'Basic realm="API"');
        }

        $this->insertLog($request->uri()->path(), $awb, $user['id'], $request->ip());    
        
        if(!ToolsService::isAppAwb($awb)) {
            $this->payload->message = "Expeditie inexistenta : {$awb}";
 			return $this->echoResponse(400);
        }

        try {
            $status = $this->awbService->trackAwbRetur($user, $awb);
            if(count($status) == 0) {
                $this->payload->message = "Expeditie inexistenta : {$awb}";
 			    return $this->echoResponse(404);
            }
            $this->payload = $status;
            return $this->echoResponse(200);
        } catch (\Exception $e) {
            $this->payload->message = "Error database";
            return $this->echoResponse(500);   
        }
    }

    /**
     * Print AWB.
     */
    public function printAWB(Request $request, int $awb, string $tip = ''): Response | JsonResponse
    {
        $user = $request->user;
        if(empty($user) || ($user['expeditor_id'] ?? 0) == 0) {
            return response()->json(['message' => 'Access denied'], 401)
                             ->header('WWW-Authenticate', 'Basic realm="API"');
        }

        $this->insertLog($request->uri()->path(), $awb, $user['id'], $request->ip());    
        
        if(!ToolsService::isAppAwb($awb)) {
            $this->payload->message = "Expeditie inexistenta : {$awb}";
 			return $this->echoResponse(400);
        }

        $format = ToolsService::sSanitizeCleanEdgesIconvTranslate(strtoupper($tip));
        $print_awb = intval($user['print_awb'] ?? 1);
        if($format == 'A4') {
            $print_awb = 1;
        }
        else if($format == 'A6') {
            $print_awb = 5;
        }
        
        try {
            DB::beginTransaction();
            $awb_obj = $this->awbService->select($user, $awb);
            if(count($awb_obj) === 0) {
                $this->payload->message = "AWB cu numarul : {$awb} inexistent";
 			    return $this->echoResponse(404);
            }
            $fileName = 'NT-' . $awb . '.pdf';
            $pdfContent = $this->printService->awbsPdf($fileName, [$awb_obj], $print_awb);

            //Log::debug('awbPdf : Marking AWBs as printed', ['ids' => $printedAwbs, 'userId' => $user['id']]);
            if($this->expeditiiService->markAwbsAsPrinted([$awb_obj['id']], $user['id']) === false) {
                Log::debug('awbPdf : Error marking AWBs as printed', ['ids' => [$awb_obj['id']], 'userId' => $user['id']]);
                throw new \Exception("Error marking AWBs as printed");
            }

            $this->payload->pdf = $pdfContent;
            DB::commit();
            return $this->echoPdf();
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Error generating AWB PDF for AWB {$awb}: " . $e->getMessage());
            $this->payload->message = "Error database";
            return $this->echoResponse(500);   
        }
    }

    /**
     * Print ZPL label.
     */
    public function printZPL(Request $request, int $awb, int $nb): Response | JsonResponse
    {
        $user = $request->user;
        if(empty($user) || ($user['expeditor_id'] ?? 0) == 0) {
            return response()->json(['message' => 'Access denied'], 401)
                             ->header('WWW-Authenticate', 'Basic realm="API"');
        }

        $this->insertLog($request->uri()->path(), $awb, $user['id'], $request->ip());    
        
        if(!ToolsService::isAppAwb($awb)) {
            $this->payload->message = "Numar de awb eronat : {$awb}";
 			return $this->echoResponse(400);
        }

        $nb = intval(ToolsService::sSanitizeCleanEdges($nb));
        if($nb == 0)
    	{
 			$this->payload->message = "Nb eronat : {$nb}";
 			return $this->echoResponse(400);

    	}
        
        try {
            DB::beginTransaction();
            $awb_obj = $this->awbService->select($user, $awb);
            if(count($awb_obj) === 0) {
                $this->payload->message = "AWB cu numarul : {$awb} inexistent";
 			    return $this->echoResponse(404);
            }
            $fileName = 'NT-' . $awb . '.zpl';
            $zplContent = $this->printService->awbZpl($awb_obj, $nb);

            if($this->expeditiiService->markAwbsAsPrinted([$awb_obj['id']], $user['id']) === false) {
                Log::debug('awbZpl : Error marking AWB as printed', ['id' => $awb_obj['id'], 'userId' => $user['id']]);
                throw new \Exception("Error marking AWB as printed");
            }

            $this->payload->zpl = $zplContent;
            DB::commit();
            return $this->echoZpl();
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Error generating AWB ZPL for AWB {$awb}: " . $e->getMessage());
            $this->payload->message = "Error database";
            return $this->echoResponse(500);   
        }
    }

    /**
     * Print borderou.
     */
    public function printBorderou(Request $request, int $borderou): Response | JsonResponse
    {
        $user = $request->user;
        if(empty($user) || ($user['expeditor_id'] ?? 0) == 0) {
            return response()->json(['message' => 'Access denied'], 401)
                             ->header('WWW-Authenticate', 'Basic realm="API"');
        }

        $this->insertLog($request->uri()->path(), $borderou, $user['id'], $request->ip());
        $borderou_id = intval($borderou);
        if($borderou_id == 0) {
            $this->payload->message = "Numar de borderou eronat : {$borderou}";
            return $this->echoResponse(400);
        }

        $pcIds = $user['pcIds'] ?? '';
        $pcIdsArray = array_map('intval', explode(',', $pcIds));
        if(count($pcIdsArray) == 0) {
            $pcIdsArray[] = $user['expeditor_id'];
        }

        try {
            DB::beginTransaction();
            $bo_obj = $this->borderouService->select($borderou_id, $pcIdsArray);
            if(count($bo_obj) === 0) {
                $this->payload->message = "Borderou cu numarul : {$borderou} inexistent";
 			    return $this->echoResponse(404);
            }
            $awbs = $this->borderouService->getAwbsForBorderouInPdf($user, $bo_obj['id']);
            if(count($awbs) === 0) {
                $this->payload->message = "Borderou cu numarul : {$borderou} nu are expeditii aferente";
 			    return $this->echoResponse(404);
            }

            $borderou_arr = [];
            $borderou_arr['expeditor_id'] = $user['expeditor_id'] ?? 0;
            $borderou_arr['expeditor'] = $user['expeditor_nume'] ?? '';
            $borderou_arr['expeditor_localitate'] = $user['expeditor_localitate'] ?? '';
            $borderou_arr['expeditor_adresa'] = $user['expeditor_adresa'] ?? '';
            $borderou_arr['expeditor_contact'] = $user['expeditor_contact'] ?? '';
            $borderou_arr['expeditor_telefon'] = $user['expeditor_telefon'] ?? '';
            $borderou_arr['borderou_id'] = $bo_obj['borderou_id'];

            $total_piese = 0;
            $total_plicuri = 0;
            $total_greutate = 0;
            $total_ramburs = 0;
            $total_expeditii = 0;
            
            // add a page
            $this->borderouPdfService->setItem($borderou_arr);
            $this->borderouPdfService->AddPage();
            $this->borderouPdfService->makeTH();
            foreach($awbs as $key=>$row)
            {
                $tip_plata='';
                if($row['ramburs'] > 0)
                {
                    $tip_plata = 'cash plic';
                    if($row['tip_plata'] == 1) $tip_plata = 'bo';
                    else if($row['tip_plata'] == 2) $tip_plata = 'cec';
                    else if($row['tip_plata'] == 3) $tip_plata = 'cash CC';
                }
                $row['destinatar'] = strtoupper(htmlspecialchars_decode(strtolower($row['destinatar']), ENT_QUOTES));

                $this->borderouPdfService->makeTR($key+1, $row['destinatar'], $row['destinatar_localitate'], $row['awb'], $row['piese'], $row['greutate'], round($row['ramburs'], 2), $tip_plata, (($row['platitor_id'] == $row['expeditor_id'])?'Exp':'Dest'), $row['detalii_doc']);
                $total_piese += intval($row['piese']);
                $total_greutate += $row['greutate'];
                $total_ramburs += $row['ramburs'];
                if($row['tip_obj'] == 1) $total_plicuri+=intval($row['piese']);
                $total_expeditii++;
            }

            $this->borderouPdfService->setPage(1);
            $this->borderouPdfService->makeHeader($bo_obj['id'] ?? '', $total_expeditii, $total_piese, $total_plicuri, round($total_greutate,2), round($total_ramburs,2), $bo_obj['created_at'] ?? '');
            // move pointer to last page
            $this->borderouPdfService->lastPage();
            
            // Output PDF
            $fileName = 'Borderou-' . $borderou_id . '.pdf';
            $this->payload->pdf = $this->borderouPdfService->Output($fileName, 'S');

            $printedAwbs = [];
            //update printed_by for awbs
            //Log::debug("debug AWBs in borderou: ".print_r($awbs, true));
            foreach($awbs as $awb) {
                $printedAwbs[] = $awb['id'];
            }
            
            if($this->expeditiiService->markAwbsAsPrinted($printedAwbs, $user['id']) === false) {
                throw new \Exception('Error marking AWBs as printed');
            }
            DB::commit();
            return $this->echoPdf();
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Error generating Borderou PDF for Borderou {$borderou}: " . $e->getMessage());
            $this->payload->message = "Error database";
            return $this->echoResponse(500);   
        }
    }

    /**
     * Get PC master list.
     */
    public function getPcs(Request $request): JsonResponse
    {
        $user = $request->user;
        if(empty($user) || ($user['expeditor_id'] ?? 0) == 0) {
            return response()->json(['message' => 'Access denied'], 401)
                             ->header('WWW-Authenticate', 'Basic realm="API"');
        }

        $this->payload->rezultate = 0;
        $this->payload->masterId = $user['expeditor_id'];
        $this->payload->puncte_de_lucru = [];

        try {
            DB::table('clienti as cl')
                ->select('cl.cod_cl as pcId', 'cl.nume', 'jd.nume_jd as judet', 'lc.nume_lc as localitate', 'cl.adresa')
                ->leftJoin('localitati as lc', 'lc.cod_lc', '=', 'cl.cod_lc')
                ->leftJoin('judete as jd', 'jd.cod_jd', '=', 'lc.cod_jd')
                ->where('cl.master', $user['expeditor_id'])
                ->where(function($query) use ($user) {
                    $query->where('cl.master', $user['expeditor_id'])
                        ->orWhere('cl.master', 0);
                })
                ->where('cl.activ', 1)
                ->orderBy('cl.cod_cl')
                ->get()
                ->each(function($item) {
                    $this->payload->puncte_de_lucru[] = [
                        'pcId' => $item->pcId,
                        'nume' => $item->nume,
                        'judet' => $item->judet,
                        'localitate' => $item->localitate,
                        'adresa' => $item->adresa
                    ];
                });
                $this->payload->rezultate = count($this->payload->puncte_de_lucru);
            }
            catch (\Exception $e) {
                Log::error("Error fetching PC master list for clientId {$user['expeditor_id']}: " . $e->getMessage());
                $this->payload->message = "Error database";
                return $this->echoResponse(500);   
            }

            if($this->payload->rezultate === 0) {
                $this->payload->message = "Nu exista puncte de lucru pentru acest clientId : {$user['expeditor_id']}";
                return $this->echoResponse(400);
            }

            $this->payload->masterId = $user['expeditor_id'];

        return $this->echoResponse(200);
    }

    private function insertLog($path, $req, $user_id, $ip = "") {
		try {
            DB::table('api_log')
                ->insert([
                    'path' => $path,
                    'request' => $req,
                    'user_id' => $user_id,
                    'ip' => $ip
                ]);
		}
		catch(\PDOException $e){
			error_log("insertLog : ".$e->getMessage());
		}
	}

    private function echoResponse($status_code) {
        return response()->json($this->payload, $status_code)
                ->header('Content-Type', 'application/json');
	}

	private function echoPdf() {
        return response($this->payload->pdf, 200)
                ->header('Content-Type', 'application/pdf');
	}

	private function echoZpl() {
        return response($this->payload->zpl, 200)
                ->header('Content-Type', 'application/octet-stream');
	}
}
