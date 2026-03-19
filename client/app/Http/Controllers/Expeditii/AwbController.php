<?php

namespace App\Http\Controllers\Expeditii;

use App\Http\Controllers\Controller;
use App\Http\Requests\Expeditii\AwbCreateRequest;
use App\Http\Requests\Expeditii\AwbUpdateRequest;
use App\Http\Requests\Expeditii\AwbPriceRequest;
use App\Http\Requests\Import\AwbUploadRequest;
use App\Services\AwbService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;
use Illuminate\Http\JsonResponse;
use App\Services\ExpeditiiService;
use App\Services\Print\PrintService;
use App\Services\Import\CsvService;
use App\Services\Import\XlsService;
use App\Data\AwbData;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;
use App\Models\ImportSession;

class AwbController extends Controller
{
    /**
     * Create a new controller instance.
     */
    public function __construct(
        private readonly AwbService $awbService,
        private readonly ExpeditiiService $expeditiiService,
        private readonly PrintService $printService
    ) {
    }
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): Response
    {
        return Inertia::render('awb/awb', [
            'pcs' => session('pcs', [])
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(Request $request): Response
    {
        return Inertia::render('awb/awb', [
            'pcs' => session('pcs', [])
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(AwbCreateRequest $request): JsonResponse
    {
        $user = Auth::user();
        if($user === null || ($user->expeditor_id ?? 0) == 0) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized'
            ], 401);
        }
        Log::debug("store : AwbController::store called with data : " . json_encode($request->all()));
        $validated = $request->validated();

        try {
            $inserted = $this->awbService->create($user, $validated);

            if(is_array($inserted) && count($inserted) > 0 && ($inserted['id'] ?? 0) > 0){
                if($user->preturi != 1) {
                    $inserted['tValoareFaraTva'] = 'N/A';
                    $inserted['tValoareTva'] = 'N/A';
                }
                $inserted['can_update'] = true;
                return response()->json([
                    'success' => true,
                    'message' => 'AWB created successfully.',
                    'data' => $inserted
                ], 201);
            }

            return response()->json([
                'success' => false,
                'message' => 'Eroare creare AWB : id is null.',
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Eroare creare AWB'.$e,
            ], 404);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(int $id): JsonResponse
    {
        $user = Auth::user();
        if($user === null || ($user->expeditor_id ?? 0) == 0) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized'
            ], 401);
        }

        try {
            $awbs = $this->expeditiiService->getValues($user, $id, [], 0);
            if(!is_array($awbs) || count($awbs) != 1) {
                return response()->json([
                    'success' => false,
                    'message' => 'AWB not found.'
                ], 404);
            }
            $awb = $this->awbService->getById(AwbData::fromSqlArray((array)$awbs[0]));

            return response()->json([
                'success' => true,
                'data' => $awb,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error fetching AWB.',
            ], 500);
        }
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(int $id): JsonResponse
    {
        $user = Auth::user();
        if($user === null || ($user->expeditor_id ?? 0) == 0) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized'
            ], 401);
        }

        $can_update_after_print = session('can_update_after_print', 0) == 1 ? true : false;

        try {
            $awbs = $this->expeditiiService->getValues($user, $id, [], 0);
            if(!is_array($awbs) || !is_array($awbs) || count($awbs) != 1) {
                return response()->json([
                    'success' => false,
                    'message' => 'AWB not found.'
                ], 404);
            }
            $found = $awbs[0];
            if(($found['can_update'] ?? 0) == 0 
                || 
                ($found['printed_by'] ?? 0) > 0 && !$can_update_after_print) {
                return response()->json([
                    'success' => false,
                    'message' => 'AWB nu poate fi editat deoarece a fost predat sau printat.'
                ], 400);
            }
            Log::debug("edit : AwbController::edit called for AWB ID : " . json_encode($found));

            $awb = $this->awbService->getForEdit(AwbData::fromSqlArray((array)$found));
            Log::debug("edit : AwbController::edit fetched AWB data for edit : " . json_encode($awb));
            return response()->json([
                'success' => true,
                'data' => $awb,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error fetching AWB.'. $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(int $id, AwbUpdateRequest $request): JsonResponse
    {
        Log::debug("update : AwbController::update called with data : " . json_encode($request->all()));
        $user = Auth::user();
        if($user === null || ($user->expeditor_id ?? 0) == 0) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized'
            ], 401);
        }
        $validated = $request->validated();
        $can_update_after_print = session('can_update_after_print', 0) == 1 ? true : false;

        try {
            if($id <= 0) {
                return response()->json([
                    'success' => false,
                    'message' => 'AWB invalid.'
                ], 400);
            }
            $awbs = $this->expeditiiService->getValues($user, $id, [], 0);
            if(!is_array($awbs) || count($awbs) != 1) {
                return response()->json([
                    'success' => false,
                    'message' => 'AWB not found.'
                ], 404);
            }
            $oldAwbData = (array)$awbs[0];
            if(($oldAwbData['can_update'] ?? 0) == 0 
                || 
                ($oldAwbData['printed_by'] ?? 0) > 0 && !$can_update_after_print) {
                return response()->json([
                    'success' => false,
                    'message' => 'AWB nu poate fi editat deoarece a fost predat sau printat.'
                ], 400);
            }
            $updated = $this->awbService->update($user, $id, $validated);

            if(is_array($updated) && count($updated) > 0 && ($updated['id'] ?? 0) > 0){
                if($user->preturi != 1) {
                    $updated['valoare_fara_tva'] = 'N/A';
                    $updated['valoare_tva'] = 'N/A';
                }
                $updated['can_update'] = true;
                return response()->json([
                    'success' => true,
                    'message' => 'AWB editat cu succes.',
                    'data' => $updated
                ]);
            }
            return response()->json([
                'success' => false,
                'message' => 'Eroare actualizare AWB.'.$updated['affected'],
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Eroare actualizare AWB.'.$e,
            ], 404);
        }
    }

    /**
     * Estimate shipping cost based on form data.
     */
    public function price(AwbPriceRequest $request): JsonResponse
    {
        Log::debug("price : AwbController::estimateCost called");
        $validated = $request->validated();
        $user = Auth::user();
        if($user === null || ($user->expeditor_id ?? 0) == 0) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized'
            ], 401);
        }

        if($user->preturi != 1) {
            return response()->json([
                'success' => false,
                'message' => 'Estimarea costului nu este disponibila pentru contul dumneavoastra.'
            ], 403);
        }
        $result = $this->awbService->price($user, $validated);
        Log::debug("price : AwbController::price result : " . json_encode($result));
        return response()->json(array_merge(['success' => true], $result));
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
            if($id <= 0) {
                return response()->json([
                    'success' => false,
                    'message' => 'AWB invalid.'
                ], 400);
            }
            $awbs = $this->expeditiiService->getValues($user, $id, [], 0);
            if(!is_array($awbs) || count($awbs) != 1) {
                return response()->json([
                    'success' => false,
                    'message' => 'AWB not found.'
                ], 404);
            }

            $this->awbService->delete($id, $user->id);

            return response()->json([
                'success' => true,
                'message' => 'AWB deleted successfully.'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 404);
        }
    }

    /**
     * Validate CSV file for AWB import.
     */
    public function validateImport(AwbUploadRequest $request): JsonResponse
    {
        try {
            $user = $request->user();
            if($user === null || ($user->expeditor_id ?? 0) == 0) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized'
                ], 401);
            }

            if(in_array($user->expeditor_id, config('awb.can_import_xls', []))) {
                return $this->validateImportXls($request);
            }

            if(!$user->importcsv) {
                return response()->json([
                    'success' => false,
                    'message' => 'Contul dumneavoastra nu are permisiunea de a importa fisiere CSV.'
                ], 403);
            }

            $file = $request->file('awbsFile');
            $filename = $user->id . '_' . Str::uuid() . '.' . $file->extension();
            if($file->extension() != 'csv') {
                return response()->json([
                    'success' => false,
                    'message' => 'Fisierul incarcat nu este de tip CSV.'
                ], 400);
            }
            $disk = Storage::disk(config('filesystems.default'));
            $path = $disk->putFileAs('', $file, $filename);
            $fullPath = $disk->path($path);

            $validatedRows = CsvService::validateImportAwb($fullPath);

            $import = ImportSession::create([
                'uuid' => Str::uuid(),
                'user_id' => $user->id,
                'disk' => config('filesystems.default'),
                'path' => $path,
                'status' => $validatedRows['total_errors'] == 0 ? 'validated' : 'failed',
                'metadata' => $validatedRows,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Validare completata',
                'data' => array_merge(['import_id' => $import->uuid], $validatedRows),
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Eroare la procesarea fisierului: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Run import for AWB data.
     */
    public function runImport(Request $request): JsonResponse
    {
        try {
            $user = $request->user();
            if($user === null || ($user->expeditor_id ?? 0) == 0) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized'
                ], 401);
            }

            if(in_array($user->expeditor_id, config('awb.can_import_xls', []))) {
                return $this->runImportXls($request);
            }

            $importId = $request->input('import_id', null);
            if($importId === null) {
                return response()->json([
                    'success' => false,
                    'message' => 'Import ID is required.'
                ], 400);
            }
            $user = $request->user();
            if($user === null || ($user->expeditor_id ?? 0) == 0) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized'
                ], 401);
            }

            $importRow = ImportSession::where('uuid', $importId)
                ->where('user_id', $user->id)
                ->first();
            if($importRow === null || $importRow->metadata['valid_rows'] === null || count($importRow->metadata['valid_rows']) == 0) {
                return response()->json([
                    'success' => false,
                    'message' => 'Import aborted.'
                ], 404);
            }

            $errorsRows = CsvService::runImportAwb($importRow->metadata['valid_rows'], $user);

            $importRow->update(['status' => 'completed', 'errors' => $errorsRows]);

            return response()->json([
                'success' => true,
                'message' => 'Import completat.',
                'data' => $errorsRows,
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Eroare la import.',
            ], 500);
        }
    }

    /**
     * Validate XLS file for AWB import maravet/biovet.
     */
    public function validateImportXls(AwbUploadRequest $request): JsonResponse
    {
        try {
            $user = $request->user();
            if($user === null || ($user->expeditor_id ?? 0) == 0) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized'
                ], 401);
            }

            if(!in_array($user->expeditor_id, config('awb.can_import_xls', []))) {
                return response()->json([
                    'success' => false,
                    'message' => 'Contul dumneavoastra nu are permisiunea de a importa fisiere XLS.'
                ], 403);
            }

            $file = $request->file('awbsFile');
            $filename = $user->id . '_' . Str::uuid() . '.' . $file->extension();
            if(!in_array($file->extension(), ['xls', 'xlsx'])) {
                return response()->json([
                    'success' => false,
                    'message' => 'Fisierul incarcat nu este de tip XLS.'
                ], 400);
            }
            $disk = Storage::disk(config('filesystems.default'));
            $path = $disk->putFileAs('', $file, $filename);
            $fullPath = $disk->path($path);

            $validatedRows = XlsService::validateImportMaravet($fullPath);

            $import = ImportSession::create([
                'uuid' => Str::uuid(),
                'user_id' => $user->id,
                'disk' => config('filesystems.default'),
                'path' => $path,
                'status' => $validatedRows['total_errors'] == 0 ? 'validated' : 'failed',
                'metadata' => $validatedRows,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Validare completata',
                'data' => array_merge(['import_id' => $import->uuid], $validatedRows),
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Eroare la procesarea fisierului: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Run import for AWB data.
     */
    public function runImportXls(Request $request): JsonResponse
    {
        try {
            $user = $request->user();
            if($user === null || ($user->expeditor_id ?? 0) == 0) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized'
                ], 401);
            }

            if(!in_array($user->expeditor_id, config('awb.can_import_xls', []))) {
                return response()->json([
                    'success' => false,
                    'message' => 'Contul dumneavoastra nu are permisiunea de a importa fisiere XLS.'
                ], 403);
            }

            $importId = $request->input('import_id', null);
            if($importId === null) {
                return response()->json([
                    'success' => false,
                    'message' => 'Import ID is required.'
                ], 400);
            }
            $user = $request->user();
            if($user === null || ($user->expeditor_id ?? 0) == 0) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized'
                ], 401);
            }

            $importRow = ImportSession::where('uuid', $importId)
                ->where('user_id', $user->id)
                ->first();
            if($importRow === null || $importRow->metadata['valid_rows'] === null || count($importRow->metadata['valid_rows']) == 0) {
                return response()->json([
                    'success' => false,
                    'message' => 'Import aborted.'
                ], 404);
            }

            $errorsRows = XlsService::runImportMaravet($importRow->metadata['valid_rows'], $user);

            $importRow->update(['status' => 'completed', 'errors' => $errorsRows]);

            return response()->json([
                'success' => true,
                'message' => 'Import completat '. (count($errorsRows) > 0 ? 'cu erori.' : 'fara erori.'),
                'data' => $errorsRows,
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Eroare la import : ' . $e->getMessage(),
            ], 500);
        }
    }

     /**
     * Generate PDF for all AWBs in borderou
     */
    public function awbPdf(Request $request)
    {//print awbs from borderouId in PDF
        try {
            $user = $request->user();
            if($user === null || ($user->expeditor_id ?? 0) == 0) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized'
                ], 401);
            }
            $ids = $request->query('ids', null);
            if($ids === null || !is_array($ids) || count($ids) == 0) {
                return response()->json([
                    'success' => false,
                    'message' => 'IDs are required and should be an array.'
                ], 400);
            }
            
            $awb_ids = collect($ids)
                    ->filter()
                    ->map(fn (string $id): int => (int) $id)
                    ->filter(fn (int $id): bool => $id > 0)
                    ->values()
                    ->all();
            //Log::debug('awbPdf : Received AWB IDs for PDF generation', ['ids' => $awb_ids]);
            $awbs = $this->expeditiiService->getValues($user, 0, $awb_ids, 0, true);
            if($awbs === false) {
                return response()->json([
                    'success' => false,
                    'message' => 'No AWBs found'
                ], 404);
            }
            $fileName = 'NTs.pdf';
            $pdfContent = $this->printService->awbsPdf($fileName, $awbs, $user->print_awb ?? 1);

            $printedAwbs = [];
            //update printed_by for awbs
            foreach($awbs as $awb) {
                if($awb['printed_by'] == 0)
                    $printedAwbs[] = $awb['id'];
            }

            //Log::debug('awbPdf : Marking AWBs as printed', ['ids' => $printedAwbs, 'userId' => $user->id]);
            if($this->expeditiiService->markAwbsAsPrinted($printedAwbs, $user->id) === false) {
                Log::debug('awbPdf : Error marking AWBs as printed', ['ids' => $printedAwbs, 'userId' => $user->id]);
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

    public function awbMasterPdf(Request $request)
    {
        try {
            $user = $request->user();
            if($user === null || ($user->expeditor_id ?? 0) == 0) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized'
                ], 401);
            }
            $ids = $request->query('ids', null);
            if($ids === null || !is_array($ids) || count($ids) == 0) {
                return response()->json([
                    'success' => false,
                    'message' => 'IDs are required and should be an array.'
                ], 400);
            }

            $awb_ids = collect($ids)
                    ->filter()
                    ->map(fn (string $id): int => (int) $id)
                    ->filter(fn (int $id): bool => $id > 0)
                    ->values()
                    ->all();


            $awbs = $this->expeditiiService->getValues($user, 0, $awb_ids, 0, true);
            if($awbs === false) {
                return response()->json([
                    'success' => false,
                    'message' => 'No AWBs found'
                ], 404);
            }

            $fileName = 'Master-NTs.pdf';
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

    public function awbPuisoriPdf(Request $request)
    {
        try {
            $user = $request->user();
            if($user === null || ($user->expeditor_id ?? 0) == 0) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized'
                ], 401);
            }
            $ids = $request->query('ids', null);
            if($ids === null || !is_array($ids) || count($ids) == 0) {
                return response()->json([
                    'success' => false,
                    'message' => 'IDs are required and should be an array.'
                ], 400);
            }

            $awb_ids = collect($ids)
                    ->filter()
                    ->map(fn (string $id): int => (int) $id)
                    ->filter(fn (int $id): bool => $id > 0)
                    ->values()
                    ->all();


            $awbs = $this->expeditiiService->getValues($user, 0, $awb_ids, 0, true);
            if($awbs === false) {
                return response()->json([
                    'success' => false,
                    'message' => 'No AWBs found'
                ], 404);
            }

            $fileName = 'Puisori-NTs.pdf';
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
     * Download CSV template for AWB import.
     */
    public function downloadTemplateCsv()
    {
        $filename = 'template_import_awb_' . date('Y-m-d') . '.csv';

        $callback = function() {
            $file = fopen('php://output', 'w');

            // Write headers
            fputcsv($file, array_merge(config('awb.import_headers.csv.required'), config('awb.import_headers.csv.optional')));

            // Write example data
            fputcsv($file, [
                'John Doe',
                'Cluj',
                'Cluj-Napoca',
                'Str. Exemplu, Nr. 1',
                'colet',
                '1',
                '2.5',
                'expeditor',
                'John Doe',
                '0712345678',
                'john.doe@gmail.com',
                '',
                '50.00',
                'cash',
                'nu',
                'da',
                'nu',
                'nu',
                'da',
                'nu',
                'nu',
                'nu',
                'observatii exemplu',
                'detalii documente exemplu',
            ]);

            fclose($file);
        };

        return response()->stream($callback, 200, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ]);
    }

    public function downloadTemplateXls()
    {
        $filename = 'template_import_awb_' . date('Y-m-d') . '.xls';
        $callback = function() {
            $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
            $sheet = $spreadsheet->getActiveSheet();

            // Write headers
            $headers = array_merge(config('awb.import_headers.xls.maravet.required'), config('awb.import_headers.xls.maravet.optional'));
            $sheet->fromArray($headers, null, 'A1');

            //'codbara','plic','colet','palet','greutate','clientdest','adresadest','orasdest','judetdest','centru','perscontactdest','telefondest','observatii',
            //'serieclient','rambursnumerar','ramburscontcolector','rambursalttip','platitorexpeditie','livraresambata','email','frig','continut',
            //'valoaredeclarata','extrainfo','largeinfo'
            //'codpostaldest','intervallivrare','deschiderecolet','taradest','emaildest','disclaimer','refexp1','refdest1','refdest2','referintafacturare'
            // Write example data
            $exampleData = [
                'codbara' => '290601840',
                'plic' => 1,
                'colet' => 2,
                'palet' => '',
                'greutate' => 5,
                'clientdest' => 'John Doe S.R.L.',
                'adresadest' => 'Str. Exemplu, Nr. 1',
                'orasdest' => 'Cluj-Napoca',
                'judetdest' => 'Cluj',
                'centru' => '',
                'perscontactdest' => 'John Doe',
                'telefondest' => '0712345678',
                'observatii' => 'observatii exemplu',
                'serieclient' => '',
                'rambursnumerar' => '',
                'ramburscontcolector' => '',
                'rambursalttip' => '',
                'platitorexpeditie' => '',
                'livraresambata' => 'nu',
                'email' => '',
                'frig' => '',
                'continut' => 'continut exemplu',
                'valoaredeclarata' => '',
                'extrainfo' => 'extrainfo exemplu',
                'largeinfo' => 'largeinfo exemplu',
                'codpostaldest' => '',
                'intervallivrare' => '',
                'deschiderecolet' => 'nu',
                'taradest' => '',
                'emaildest' => 'john.doe@exemplu.com',
                'disclaimer' => '',
                'refexp1' => '',
                'refdest1' => '',
                'refdest2' => '',
                'referintafacturare' => '',
            ];
            $sheet->fromArray($exampleData, null, 'A2');

            $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xls($spreadsheet);
            $writer->save('php://output');
        };
        return response()->stream($callback, 200, [
            'Content-Type' => 'application/vnd.ms-excel; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ]);
    }
}
