<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use App\Services\AwbService;
use App\Services\ComandaService;
use App\Services\BorderouService;
use App\Services\Helpers\ToolsService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Services\Validators\ValidateJsonPriceService;
use App\Services\Validators\ValidateJsonAwbService;
use App\Services\Validators\ValidateJsonOrderService;
use App\Services\Helpers\ClientService;

class PostController extends Controller
{
    private $payload;

    public function __construct(
        private readonly AwbService $awbService,
        private readonly ComandaService $comandaService,
        private readonly BorderouService $borderouService,
    )
    {
        $this->payload = new \StdClass();
    }

    /**
     * Calculate AWB cost.
     */
    public function getCostAWB(Request $request): JsonResponse
    {
        $user = $request->user;
        if(empty($user) || ($user['expeditor_id'] ?? 0) == 0) {
            return response()->json(['message' => 'Access denied'], 401)
                             ->header('WWW-Authenticate', 'Basic realm="API"');
        }
        $this->insertLog($request->uri()->path(), $request->getContent(), $user['id'], $request->ip());

        // Manual validation from json request, using rules from AwbPriceRequest
        $validated = ValidateJsonPriceService::validate($request->getContent(), []);
        if($validated['success'] === false) {
            $this->payload->message = $validated['message'];
            return $this->echoResponse(400); //422 Unprocessable Entity
        }

        try {
            if($user['preturi'] != 1) {
                return response()->json([
                    'success' => false,
                    'message' => 'Estimarea costului nu este disponibila pentru contul dumneavoastra.'
                ], 403);
            }
            $this->payload->message = $this->awbService->price($user, $validated['data']);
            return $this->echoResponse(200);
        } catch (\Exception $e) {
            Log::error("Error awb cost : " . $e->getMessage());
            $this->payload->message = "Error database";
            return $this->echoResponse(500);
        }
    }

    /**
     * Calculate AWB cost for PC.
     */
    public function getCostAwbPc(Request $request): JsonResponse
    {
        $user = $request->user;
        if(empty($user) || ($user['expeditor_id'] ?? 0) == 0) {
            return response()->json(['message' => 'Access denied'], 401)
                             ->header('WWW-Authenticate', 'Basic realm="API"');
        }
        $this->insertLog($request->uri()->path(), $request->getContent(), $user['id'], $request->ip());

        $pcIds = $user['pcIds'] ?? '';
        //Log::info("User PC IDs: " . $pcIds);
        $pcIdsArray = array_map('intval', explode(',', $pcIds));
        // Manual validation from json request, using rules from AwbPriceRequest
        $validated = ValidateJsonPriceService::validate($request->getContent(), $pcIdsArray, true);
        if($validated['success'] === false) {
            $this->payload->message = $validated['message'];
            return $this->echoResponse(400); //422 Unprocessable Entity
        }

        try {
            if($user['preturi'] != 1) {
                return response()->json([
                    'success' => false,
                    'message' => 'Estimarea costului nu este disponibila pentru contul dumneavoastra.'
                ], 403);
            }

            $expeditor_localitate_obj = ClientService::getLocalitateInfos($validated['data']['pcId']);
            if(count($expeditor_localitate_obj) == 0 || !isset($expeditor_localitate_obj['dist_km'])) {
                return response()->json([
                    'success' => false,
                    'message' => 'Localitatea pcId nu a fost gasita in baza de date.'
                ], 403);
            }

            $user['expeditor_id'] = $validated['data']['pcId'];
            $user['expeditor_localitate_km'] = $expeditor_localitate_obj['dist_km'] ?? 0;
            $user['expeditor_localitate_id'] = $expeditor_localitate_obj['cod_lc'] ?? 0;
            $this->payload->message = $this->awbService->price($user, $validated['data']);
            return $this->echoResponse(200);
        } catch (\Exception $e) {
            Log::error("Error awb costForPc : " . $e->getMessage());
            $this->payload->message = "Error database";
            return $this->echoResponse(500);
        }
    }

    /**
     * Send AWB.
     */
    public function sendAWB(Request $request): JsonResponse
    {
        $user = $request->user;
        if(empty($user) || ($user['expeditor_id'] ?? 0) == 0) {
            return response()->json(['message' => 'Access denied'], 401)
                             ->header('WWW-Authenticate', 'Basic realm="API"');
        }
        $this->insertLog($request->uri()->path(), $request->getContent(), $user['id'], $request->ip());

        // Manual validation from json request, using rules from AwbPriceRequest
        $validated = ValidateJsonAwbService::validate($request->getContent(), []);
        if($validated['success'] === false) {
            $this->payload->message = $validated['message'];
            return $this->echoResponse(400); //422 Unprocessable Entity
        }

        try {
            $awb = $this->awbService->create($user, $validated['data']);
            if(empty($awb['awb']) || !isset($awb['totalNet'])) {
                $this->payload->message = "Error creating AWB";
                return $this->echoResponse(500);
            }
            $this->payload->awb = $awb['awb'];
            $this->payload->totalNet = $awb['totalNet'];
            return $this->echoResponse(201);
        } catch (\Exception $e) {
            Log::error("Error awb send : " . $e->getMessage());
            $this->payload->message = "Error database";
            return $this->echoResponse(500);
        }
    }

    /**
     * Send AWB for PC.
     */
    public function sendAwbPc(Request $request): JsonResponse
    {
        $user = $request->user;
        if(empty($user) || ($user['expeditor_id'] ?? 0) == 0) {
            return response()->json(['message' => 'Access denied'], 401)
                             ->header('WWW-Authenticate', 'Basic realm="API"');
        }
        $this->insertLog($request->uri()->path(), $request->getContent(), $user['id'], $request->ip());

        $pcIds = $user['pcIds'] ?? '';
        //Log::info("User PC IDs: " . $pcIds);
        $pcIdsArray = array_map('intval', explode(',', $pcIds));
        // Manual validation from json request, using rules from AwbPriceRequest
        $validated = ValidateJsonAwbService::validate($request->getContent(), $pcIdsArray, true);
        if($validated['success'] === false) {
            $this->payload->message = $validated['message'];
            return $this->echoResponse(400); //422 Unprocessable Entity
        }

        try {
            $awb = $this->awbService->create($user, $validated['data']);
            if(empty($awb['awb']) || !isset($awb['totalNet'])) {
                $this->payload->message = "Error creating AWB";
                return $this->echoResponse(500);
            }
            $this->payload->awb = $awb['awb'];
            $this->payload->totalNet = $awb['totalNet'];
            return $this->echoResponse(201);
        } catch (\Exception $e) {
            Log::error("Error awb sendForPc : " . $e->getMessage());
            $this->payload->message = "Error database";
            return $this->echoResponse(500);
        }
    }

    /**
     * Make borderou.
     */
    public function makeBorderou(Request $request): JsonResponse
    {
        $user = $request->user;
        if(empty($user) || ($user['expeditor_id'] ?? 0) == 0) {
            return response()->json(['message' => 'Access denied'], 401)
                             ->header('WWW-Authenticate', 'Basic realm="API"');
        }
        $this->insertLog($request->uri()->path(), $request->getContent(), $user['id'], $request->ip());

        $pcIds = $user['pcIds'] ?? '';
        $pcIdsArray = array_map('intval', explode(',', $pcIds));
        if(count($pcIdsArray) == 0) {
            $pcIdsArray[] = $user['expeditor_id'];
        }

        try {
            $bo = $this->borderouService->create($user, $pcIdsArray);
            if(empty($bo['nrExpeditii']) || empty($bo['borderou'])) {
                $this->payload->message = "Nici o expeditie fara borderou : nothing to do";
                return $this->echoResponse(200);
            }
            $this->payload->borderou = $bo['borderou'];
            $this->payload->nrExpeditii = $bo['nrExpeditii'];
            return $this->echoResponse(201);
        } catch (\Exception $e) {
            Log::error("Error borderou create : " . $e->getMessage());
            $this->payload->message = "Error database";
            return $this->echoResponse(500);
        }
    }

    /**
     * Send pickup command.
     */
    public function sendComanda(Request $request): JsonResponse
    {
        $user = $request->user;
        if(empty($user) || ($user['expeditor_id'] ?? 0) == 0) {
            return response()->json(['message' => 'Access denied'], 401)
                             ->header('WWW-Authenticate', 'Basic realm="API"');
        }
        $this->insertLog($request->uri()->path(), $request->getContent(), $user['id'], $request->ip());

        // Manual validation from json request, using rules from AwbPriceRequest
        $validated = ValidateJsonOrderService::validate($request->getContent(), []);
        if($validated['success'] === false) {
            $this->payload->message = $validated['message'];
            return $this->echoResponse(400); //422 Unprocessable Entity
        }

        try {
            $validated['data']['pcId'] = $user['expeditor_id'];
            $validated['data']['client'] = $user['expeditor_nume'] ?? '';
            $this->payload->pickupId = $this->comandaService->create($user, $validated['data']);
            return $this->echoResponse(201);
        } catch (\Exception $e) {
            Log::error("Error pickup send : " . $e->getMessage());
            $this->payload->message = "Error database";
            return $this->echoResponse(500);
        }
    }

    /**
     * Send pickup command for PC.
     */
    public function sendComandaPc(Request $request): JsonResponse
    {
        $user = $request->user;
        if(empty($user) || ($user['expeditor_id'] ?? 0) == 0) {
            return response()->json(['message' => 'Access denied'], 401)
                             ->header('WWW-Authenticate', 'Basic realm="API"');
        }
        $this->insertLog($request->uri()->path(), $request->getContent(), $user['id'], $request->ip());

        $pcIds = $user['pcIds'] ?? '';
        //Log::info("User PC IDs: " . $pcIds);
        $pcIdsArray = array_map('intval', explode(',', $pcIds));
        // Manual validation from json request, using rules from AwbPriceRequest
        $validated = ValidateJsonOrderService::validate($request->getContent(), $pcIdsArray, true);
        if($validated['success'] === false) {
            $this->payload->message = $validated['message'];
            return $this->echoResponse(400); //422 Unprocessable Entity
        }

        try {
            $pc = ($validated['data']['pcId'] ?? 0) > 0 ? ClientService::getClient($validated['data']['pcId']) : [];
            if(count($pc) == 0) {
                $this->payload->message = "pcId invalid";
                return $this->echoResponse(400);
            }
            $validated['data']['pcId'] = $validated['data']['pcId'];
            $validated['data']['client'] = $pc['nume'] ?? $user['expeditor_nume'] ?? '';
            $this->payload->pickupId = $this->comandaService->create($user, $validated['data']);
            return $this->echoResponse(201);
        } catch (\Exception $e) {
            Log::error("Error pickup sendForPc : " . $e->getMessage());
            $this->payload->message = "Error database";
            return $this->echoResponse(500);
        }
    }

    /**
     * Delete AWB.
     */
    public function deleteAWB(Request $request, int $awb): JsonResponse
    {
        $user = $request->user;
        if(empty($user) || ($user['expeditor_id'] ?? 0) == 0) {
            return response()->json(['message' => 'Access denied'], 401)
                             ->header('WWW-Authenticate', 'Basic realm="API"');
        }

        try {
            if($awb <= 0 || !ToolsService::isAppAwb($awb)) {
                $this->payload->message = "Numar de awb eronat : {$awb}";
 			    return $this->echoResponse(400);
            }
            $awb_obj = $this->awbService->select($user, $awb);
            if(count($awb_obj) == 0) {
                $this->payload->message = "Expeditie inexistenta : {$awb}";
                return $this->echoResponse(404);
            }

            if($this->awbService->delete($user, $awb_obj['id']) === false) {
                $this->payload->message = "Error deleting AWB";
                return $this->echoResponse(500);
            }

            $this->payload->message = "deleted";
            return $this->echoResponse(200); //204 No Content
        } catch (\Exception $e) {
            Log::error("Error generating AWB PDF for AWB {$awb}: " . $e->getMessage());
            $this->payload->message = "Error database";
            return $this->echoResponse(500);
        }
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
