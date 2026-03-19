<?php

namespace App\Http\Controllers\Borderouri;

use App\Http\Controllers\Controller;
use App\Services\BorderouService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Inertia\Inertia;
use Inertia\Response;
use Illuminate\Support\Facades\Auth;
use App\Services\Print\BorderouPdfService;
use App\Services\ExpeditiiService;
use App\Services\Print\PrintService;
use App\Services\Export\CsvService as ExportCsvService;
use Illuminate\Support\Facades\Log;

class BorderouController extends Controller
{
    public function __construct(
        private readonly BorderouService $borderouService,
        private readonly BorderouPdfService $borderouPdfService,
        private readonly ExpeditiiService $expeditiiService,
        private readonly PrintService $printService,
        private readonly ExportCsvService $exportCsvService
    ) {
    }
    /**
     * Display a listing of the resource.
     */
    public function index(): Response
    {
        return Inertia::render('borderouri/index', [
            'pcs' => session('pcs', [])
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(): Response
    {
        return Inertia::render('borderouri/index', [
            'pcs' => session('pcs', [])
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'awb_ids' => 'required|array|min:1|max:300',
            'awb_ids.*' => 'required|integer',//|exists:awbs,id
        ]);

        $user = Auth::user();
        if($user === null || ($user->expeditor_id ?? 0) == 0) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized'
            ], 401);
        }

        try {
            $borderou_data = $this->borderouService->create($user, $validated);

            if(is_array($borderou_data) && isset($borderou_data['id']) && $borderou_data['id'] > 0)
                return response()->json([
                    'success' => true,
                    'message' => 'Borderou created successfully.',
                    'data' => $borderou_data,
                ], 201);
            return response()->json([
                'success' => false,
                'message' => 'Eroare creare borderou.',
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error: ' . $e->getMessage()
            ], 404);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(int $id): JsonResponse
    {
        $borderou = $this->borderouService->getById($id);

        return response()->json([
            'success' => true,
            'message' => 'Borderou retrieved successfully.',
            'data' => $borderou,
        ]);
    }

    /**
     * Get AWBs for a specific borderou
     */
    public function awbs(int $id, Request $request): JsonResponse
    {
        try {
            $user = $request->user();
            if($user === null || ($user->expeditor_id ?? 0) == 0) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized'
                ], 401);
            }

            $awbs = $this->borderouService->getAwbsForBorderouId($user, $id);

            return response()->json([
                'success' => true,
                'data' => $awbs
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error loading expeditii: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(int $id): JsonResponse
    {
        $user = Auth::user();
        if($user === null || ($user->expeditor_id ?? 0) == 0) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized'
            ], 401);
        }

        try {
            $this->borderouService->delete($user, $id);

            return response()->json([
                'success' => true,
                'message' => 'Borderou deleted successfully: ' . $id
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error: ' . $e->getMessage()
            ], 404);
        }
    }

    /**
     * Generate PDF for borderou
     */
    public function pdf(int $id, Request $request)
    {
        try {
            $user = $request->user();
            if($user === null || ($user->expeditor_id ?? 0) == 0) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized'
                ], 401);
            }
            if($id <= 0) {
                return response()->json([
                    'success' => false,
                    'message' => 'Borderou invalid'
                ], 400);
            }

            $borderou = [];
            $borderou['expeditor_id'] = $user->expeditor_id ?? 0;
            $borderou['expeditor'] = session('nume', '');
            $borderou['expeditor_localitate'] = session('localitate', '');
            $borderou['expeditor_adresa'] = session('adresa', '');
            $borderou['expeditor_contact'] = $user->nume;
            $borderou['expeditor_telefon'] = $user->telefon;
            $borderou['borderou_id'] = $id;

            $total_piese = 0;
            $total_plicuri = 0;
            $total_greutate = 0;
            $total_ramburs = 0;
            $total_expeditii = 0;

            $awbs = $this->borderouService->getAwbsForBorderouInPdf($id, $user->expeditor_id);
            $this->borderouPdfService->setItem($borderou);
            
            // add a page
            $this->borderouPdfService->AddPage();
            $this->borderouPdfService->makeTH();
            foreach($awbs as $key=>$row)
            {
                if($key == 0)
                {
                    $borderou['bo_created_at'] = $row['bo_created_at'];
                    $this->borderouPdfService->setItem($borderou);
                }
                $tip_plata='';
                if($row['ramburs'] > 0)
                {
                    $tip_plata = 'cash plic';
                    if($row['tip_plata'] == 1) $tip_plata = 'bo';
                    else if($row['tip_plata'] == 2) $tip_plata = 'cec';
                    else if($row['tip_plata'] == 3) $tip_plata = 'cash CC';
                }
                $row['destinatar'] = strtoupper(htmlspecialchars_decode(strtolower($row['destinatar']), ENT_QUOTES));

                $this->borderouPdfService->makeTR($key+1, $row['destinatar'], $row['destinatar_localitate'], $row['awb'], $row['piese'], $row['greutate'], $row['ramburs'], $tip_plata, (($row['platitor_id'] == $row['expeditor_id'])?'E':'D'), $row['detalii_doc']);
                $total_piese += intval($row['piese']);
                $total_greutate += $row['greutate'];
                $total_ramburs += $row['ramburs'];
                if($row['tip_obj'] == 1) $total_plicuri+=intval($row['piese']);
                $total_expeditii++;
            }

            $this->borderouPdfService->setPage(1);
            $this->borderouPdfService->makeHeader($borderou['borderou_id'] ?? '', $total_expeditii, $total_piese, $total_plicuri, round($total_greutate,2), round($total_ramburs,2), $borderou['bo_created_at'] ?? '');
            // move pointer to last page
            $this->borderouPdfService->lastPage();
            
            // Output PDF
            $fileName = 'Borderou-' . $id . '.pdf';
            $pdfContent = $this->borderouPdfService->Output($fileName, 'S');

            $printedAwbs = [];
            //update printed_by for awbs
            //Log::debug("debug AWBs in borderou: ".print_r($awbs, true));
            foreach($awbs as $awb) {
                if($awb['printed_by'] == 0)
                    $printedAwbs[] = $awb['id'];
            }
            
            if($this->expeditiiService->markAwbsAsPrinted($printedAwbs, $user->id) === false) {
                throw new \Exception('Error database');
            }
            
            return response($pdfContent, 200, [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => 'inline; filename="'.$fileName.'"; filename*=UTF-8'."''".$fileName,
                'Content-Transfer-Encoding' => 'binary',
                'Accept-Ranges' => 'bytes',
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error generating PDF: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Generate PDF for all AWBs in borderou
     */
    public function awbsPdf(int $id, Request $request)
    {//print awbs from borderouId in PDF
        try {
            $user = $request->user();
            if($user === null || ($user->expeditor_id ?? 0) == 0) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized'
                ], 401);
            }
            if($id <= 0) {
                return response()->json([
                    'success' => false,
                    'message' => 'Borderou invalid'
                ], 400);
            }

            $awbs = $this->expeditiiService->getValues($user, 0, [], $id, true);
            if(!is_array($awbs) || count($awbs) == 0) {
                return response()->json([
                    'success' => false,
                    'message' => 'No AWBs found in borderou'
                ], 404);
            }
            
            $fileName = 'NT-in-Borderou-' . $id . '.pdf';
            $pdfContent = $this->printService->awbsPdf($fileName, $awbs, $user->print_awb ?? 1);

            $printedAwbs = [];
            //update printed_by for awbs
            foreach($awbs as $awb) {
                if($awb['printed_by'] == 0)
                    $printedAwbs[] = $awb['id'];
            }
            error_log("debug : ".print_r($printedAwbs, true));
            if($this->expeditiiService->markAwbsAsPrinted($printedAwbs, $user->id) === false) {
                throw new \Exception('Error database');
            }
            
            return response($pdfContent, 200, [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => 'inline; filename="'.$fileName.'"; filename*=UTF-8'."''".$fileName,
                'Content-Transfer-Encoding' => 'binary',
                'Accept-Ranges' => 'bytes',
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error generating PDF: ' . $e->getMessage()
            ], 500);
        }
    }

    public function awbsMasterPdf(int $id, Request $request)
    {
        try {
            $user = $request->user();
            if($user === null || ($user->expeditor_id ?? 0) == 0) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized'
                ], 401);
            }
            if($id <= 0) {
                return response()->json([
                    'success' => false,
                    'message' => 'Borderou invalid'
                ], 400);
            }

            $awbs = $this->expeditiiService->getValues($user, 0, [], $id, true);
            if(!is_array($awbs) || count($awbs) == 0) {
                return response()->json([
                    'success' => false,
                    'message' => 'No AWBs found in borderou'
                ], 404);
            }

            $fileName = 'Master-NT-in-Borderou-' . $id . '.pdf';
            $pdfContent = $this->printService->awbsMasterPdf($fileName, $awbs, $user->print_awb ?? 1);

            $printedAwbs = [];
            //update printed_by for awbs
            foreach($awbs as $awb) {
                if($awb['printed_by'] == 0)
                    $printedAwbs[] = $awb['id'];
            }
            
            if($this->expeditiiService->markAwbsAsPrinted($printedAwbs, $user->id) === false) {
                throw new \Exception('Error database');
            }
            
            return response($pdfContent, 200, [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => 'inline; filename="'.$fileName.'"; filename*=UTF-8'."''".$fileName,
                'Content-Transfer-Encoding' => 'binary',
                'Accept-Ranges' => 'bytes',
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error generating PDF: ' . $e->getMessage()
            ], 500);
        }
    }

    public function awbsPuisoriPdf(int $id, Request $request)
    {
        try {
            $user = $request->user();
            if($user === null || ($user->expeditor_id ?? 0) == 0) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized'
                ], 401);
            }
            if($id <= 0) {
                return response()->json([
                    'success' => false,
                    'message' => 'Borderou invalid'
                ], 400);
            }

            $awbs = $this->expeditiiService->getValues($user, 0, [], $id, true);
            if(!is_array($awbs) || count($awbs) == 0) {
                return response()->json([
                    'success' => false,
                    'message' => 'No AWBs found in borderou'
                ], 404);
            }

            $fileName = 'Puisori-NT-in-Borderou-' . $id . '.pdf';
            $pdfContent = $this->printService->awbsPuisoriPdf($fileName, $awbs, $user->print_awb ?? 1);

            $printedAwbs = [];
            //update printed_by for awbs
            foreach($awbs as $awb) {
                if($awb['printed_by'] == 0)
                    $printedAwbs[] = $awb['id'];
            }
            
            if($this->expeditiiService->markAwbsAsPrinted($printedAwbs, $user->id) === false) {
                throw new \Exception('Error database');
            }
            
            return response($pdfContent, 200, [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => 'inline; filename="'.$fileName.'"; filename*=UTF-8'."''".$fileName,
                'Content-Transfer-Encoding' => 'binary',
                'Accept-Ranges' => 'bytes',
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error generating PDF: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Export borderou data as CSV
     */
    public function export(int $id, Request $request)
    {
        try {
            $user = $request->user();
            if($user === null || ($user->expeditor_id ?? 0) == 0) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized'
                ], 401);
            }
            if($id <= 0) {
                return response()->json([
                    'success' => false,
                    'message' => 'Borderou invalid'
                ], 400);
            }

            $awbs = $this->expeditiiService->getValues($user, 0, [], $id, true);
            if(!is_array($awbs) || count($awbs) == 0) {
                return response()->json([
                    'success' => false,
                    'message' => 'No AWBs found in borderou'
                ], 404);
            }

            $fileName = 'Borderou-' . $id . '.csv';
            return $this->exportCsvService->exportBorderou($awbs, $fileName, $user->preturi ?? 0);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error generating CSV: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get borderouri data for API consumption (AJAX, Tabulator, etc.)
     */
    public function data(Request $request): JsonResponse
    {
        try {
            $user = $request->user();
            if($user === null || ($user->expeditor_id ?? 0) == 0) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized'
                ], 401);
            }

            $page = (int) $request->input('page', 1);
            $rows = min(300, max(1, (int) $request->input('rows', 100))); // Max 300 per page
            $sortField = $request->input('sortField', 'id');
            $sortOrder = $request->input('sortOrder', 'desc');

            $nepredate = $this->borderouService->getPaginated($user, $user->print_awb ?? 1, $page, $rows, $sortField, $sortOrder, $request);

            return response()->json([
                'success' => true,
                'data' => $nepredate
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error loading expeditii: ' . $e->getMessage()
            ], 500);
        }
    }
}
