<?php

namespace App\Http\Controllers\Destinatari;

use App\Http\Controllers\Controller;
use App\Http\Requests\Destinatari\DestinatarCreateRequest;
use App\Http\Requests\Destinatari\DestinatarUpdateRequest;
use App\Http\Requests\Import\DestinatariUploadRequest;
use App\Models\ImportSession;
use App\Services\DestinatarService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;
use App\Services\Import\CsvService;

use Illuminate\Support\Facades\Log;

class DestinatarController extends Controller
{
    public function __construct(
        private readonly DestinatarService $destinatarService
    ) {
    }
    /**
     * Display a listing of the resource for autocomplete.
     */
    public function autocomplete(Request $request): JsonResponse
    {
        $user = $request->user();
        if($user === null || ($user->expeditor_id ?? 0) == 0) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized'
            ], 401);
        }

        $search = $request->input('q', '');
        $limit = (int) $request->input('limit', 100);
        $user = $request->user();

        $master_id = session('master_id', $user->expeditor_id);
        $master_id = $user->show_master_clienti == 1 && $master_id != $user->expeditor_id ? $master_id : $user->expeditor_id;

        $destinatari = $this->destinatarService->getAutocomplete($user->expeditor_id, $master_id, $search, $limit);

        return response()->json([
            'success' => true,
            'data' => $destinatari
        ]);
    }

    /**
     * Display a listing of the resource.
     */
    public function index(): Response
    {
        return Inertia::render('destinatari/index');
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(): Response
    {
        return Inertia::render('destinatari/index');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(DestinatarCreateRequest $request): JsonResponse
    {
        $user = $request->user();
        if($user === null || ($user->expeditor_id ?? 0) == 0) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized'
            ], 401);
        }
        $master_id = session('master_id', $user->expeditor_id);
        $user->master_id = $user->show_master_clienti == 1 && $master_id != $user->expeditor_id ? $master_id : $user->expeditor_id;

        try {
            $destinatar_id = $this->destinatarService->create($user, $request->validated());

            if($destinatar_id > 0)
                return response()->json([
                    'success' => true,
                    'message' => 'Destinatar created successfully.',
                    'data' => $destinatar_id
                ], 201);
            return response()->json([
                'success' => false,
                'message' => 'Eroare creare destinatar',
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Eroare creare destinatar: ' . $e->getMessage()
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
        $master_id = session('master_id', $user->expeditor_id);
        $master_id = $user->show_master_clienti == 1 && $master_id != $user->expeditor_id ? $master_id : $user->expeditor_id;

        $destinatar = $this->destinatarService->getById($user->expeditor_id, $master_id, $id);

        return response()->json([
            'success' => true,
            'message' => 'Destinatar retrieved successfully.',
            'data' => $destinatar
        ]);
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
        $master_id = session('master_id', $user->expeditor_id);
        $master_id = $user->show_master_clienti == 1 && $master_id != $user->expeditor_id ? $master_id : $user->expeditor_id;

        $destinatar = $this->destinatarService->getForEdit($user->expeditor_id, $master_id, $id);

        if($destinatar === null || count($destinatar) == 0)
            return response()->json([
                'success' => false,
                'message' => 'Destinatar not found.',
            ]);

        return response()->json([
            'success' => true,
            'message' => 'Destinatar retrieved successfully.',
            'data' => $destinatar
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(int $id, DestinatarUpdateRequest $request): JsonResponse
    {
        $user = $request->user();
        if($user === null || ($user->expeditor_id ?? 0) == 0) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized'
            ], 401);
        }
        $master_id = session('master_id', $user->expeditor_id);
        $user->master_id = $user->show_master_clienti == 1 && $master_id != $user->expeditor_id ? $master_id : $user->expeditor_id;

        try {
            $this->destinatarService->update($user, $id, $request->validated());
            return response()->json([
                'success' => true,
                'message' => 'Destinatar updated successfully.',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Destinatar not found: ' . $e->getMessage()
            ], 404);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(int $id, Request $request): JsonResponse
    {
        $user = $request->user();
        if($user === null || ($user->expeditor_id ?? 0) == 0) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized'
            ], 401);
        }
        $master_id = session('master_id', $user->expeditor_id);
        $master_id = $user->show_master_clienti == 1 && $master_id != $user->expeditor_id ? $master_id : $user->expeditor_id;

        try {
            $this->destinatarService->delete($user->expeditor_id, $master_id, $id);

            return response()->json([
                'success' => true,
                'message' => 'Destinatar deleted successfully.'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Destinatar not found: ' . $e->getMessage()
            ], 404);
        }
    }

    /**
     * Return paginated destinatari data for Tabulator
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
            $master_id = session('master_id', $user->expeditor_id);
            $master_id = $user->show_master_clienti == 1 && $master_id != $user->expeditor_id ? $master_id : $user->expeditor_id;

            $page = (int) $request->input('page', 1);
            $rows = (int) $request->input('rows', 100);
            $sortField = $request->input('sortField', 'nume');
            $sortOrder = $request->input('sortOrder', 'asc');

            $destinatari = $this->destinatarService->getPaginated($user->expeditor_id, $master_id, $page, $rows, $sortField, $sortOrder, $request);

            return response()->json([
                'success' => true,
                'data' => $destinatari
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error loading destinatari: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Validate CSV file for client_destinatari import.
     */
    public function validateImport(DestinatariUploadRequest $request): JsonResponse
    {
        try {
            $user = $request->user();
            if($user === null || ($user->expeditor_id ?? 0) == 0) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized'
                ], 401);
            }

            $master_id = session('master_id', $user->expeditor_id);
            $is_master = ($master_id == 0 || $master_id == $user->expeditor_id);

            if(!$is_master) {
                return response()->json([
                    'success' => false,
                    'message' => 'Contul dumneavoastra nu are permisiunea de a importa destinatari.'
                ], 403);
            }

            $file = $request->file('destFile');
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

            //Log::debug('DestinatarController@validateImport - File uploaded', ['user_id' => $user->id, 'filename' => $filename, 'path' => $fullPath]);
            $validatedRows = CsvService::validateImportDestinatari($fullPath);

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

            $errorsRows = CsvService::runImportDestinatari($importRow->metadata['valid_rows'], $user);

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
     * Download CSV template for destinatari import.
     */
    public function downloadTemplate()
    {
        $filename = 'template_import_destinatari_' . date('Y-m-d') . '.csv';

        $callback = function() {
            $file = fopen('php://output', 'w');

            // Write headers
            fputcsv($file, array_merge(config('awb.import_destinatari_headers.csv.required'), config('awb.import_destinatari_headers.csv.optional')));

            // Write example data
            fputcsv($file, [
                'John Doe',
                'Cluj',
                'Cluj-Napoca',
                'Str. Exemplu, Nr. 1',
                'John Doe',
                '0712345678',
                'john.doe@gmail.com',
            ]);

            fclose($file);
        };

        return response()->stream($callback, 200, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ]);
    }
}
