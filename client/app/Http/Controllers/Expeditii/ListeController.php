<?php

namespace App\Http\Controllers\Expeditii;

use App\Http\Controllers\Controller;
use App\Services\ExpeditiiService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\StreamedResponse;
use App\Services\Export\CsvService as ExportCsvService;

class ListeController extends Controller
{
    public function __construct(
        private readonly ExpeditiiService $expeditiiService,
        private readonly ExportCsvService $exportCsvService
    ) {
    }

    /**
     * Display the main liste page.
     */
    public function index(Request $request): Response
    {
        return Inertia::render('shipments/index', [
            'pcs' => session('pcs', [])
        ]);
    }

    /**
     * Get AWB-uri nepredate (undelivered AWBs) with pagination.
     */
    public function nepredate(Request $request): JsonResponse
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
            $rows = min(300, max(1, (int) $request->input('rows', 300))); // Max 300 per page
            $sortField = $request->input('sortField', 'id');
            $sortOrder = $request->input('sortOrder', 'desc');
            $swapped = $request->input('swapped', '0') === '1';

            $nepredate = $this->expeditiiService->getNepredate(0, $user, $page, $rows, $sortField, $sortOrder, $swapped, $request);

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

    /**
     * Get AWB-uri predate (delivered AWBs) with pagination.
     */
    public function predate(Request $request): JsonResponse
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
            $rows = min(100, max(1, (int) $request->input('rows', 100))); // Max 300 per page
            $sortField = $request->input('sortField', 'id');
            $sortOrder = $request->input('sortOrder', 'desc');

            $predate = $this->expeditiiService->getPredate($user, $page, $rows, $sortField, $sortOrder, $request);

            return response()->json([
                'success' => true,
                'data' => $predate
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error loading expeditii: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get AWB-uri retururi (returned AWBs) with pagination.
     */
    public function retururi(Request $request): JsonResponse
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
            $rows = min(100, max(1, (int) $request->input('rows', 100))); // Max 300 per page
            $sortField = $request->input('sortField', 'id');
            $sortOrder = $request->input('sortOrder', 'desc');

            $retururi = $this->expeditiiService->getRetururi($user, $page, $rows, $sortField, $sortOrder, $request);

            return response()->json([
                'success' => true,
                'data' => $retururi
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error loading expeditii: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Export AWB-uri predate to CSV
     */
    public function exportPredate(Request $request): JsonResponse | StreamedResponse
    {
        try {
            $user = $request->user();
            if($user === null || ($user->expeditor_id ?? 0) == 0) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized'
                ], 401);
            }

            $startDate = $request->input('startDate');
            $endDate = $request->input('endDate');

            // Get all predate data without pagination
            $result = $this->expeditiiService->getPredate($user, 1, 999999, 'id', 'desc', $request);
            if(empty($result['data']) || $result['data']->isEmpty()) {
                $awbs = [];
            } else {
                $awbs = $result['data']->toArray();
            }

            $fileName = 'Predate_export_' . ($startDate ?? 'all') . '-' . ($endDate ?? 'now') . '.csv';
            return $this->exportCsvService->exportPredate($awbs, $fileName, $user->preturi ?? 0);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error generating CSV: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Export AWB-uri predate to CSV
     */
    public function exportRetururi(Request $request): JsonResponse | StreamedResponse
    {
        try {
            $user = $request->user();
            if($user === null || ($user->expeditor_id ?? 0) == 0) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized'
                ], 401);
            }

            $startDate = $request->input('startDate');
            $endDate = $request->input('endDate');

            // Get all retururi data without pagination
            $result = $this->expeditiiService->getRetururi($user, 1, 999999, 'id', 'desc', $request);
            if(empty($result['data']) || $result['data']->isEmpty()) {
                $awbs = [];
            } else {
                $awbs = $result['data']->toArray();
            }

            $fileName = 'Retururi_export_' . ($startDate ?? 'all') . '-' . ($endDate ?? 'now') . '.csv';
            return $this->exportCsvService->exportRetururi($awbs, $fileName, $user->preturi ?? 0);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error generating CSV: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get distinct status values (operatiune) for a tab.
     */
    public function statuses(Request $request, string $tab): JsonResponse
    {
        if (! in_array($tab, ['predate', 'retururi'], true)) {
            return response()->json(['success' => false, 'message' => 'Invalid tab'], 400);
        }
        try {
            $user = $request->user();
            if ($user === null || ($user->expeditor_id ?? 0) == 0) {
                return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
            }
            $statuses = $this->expeditiiService->getStatuses($user, $tab);
            return response()->json(['success' => true, 'data' => $statuses]);
        } catch (\Exception $e) {
            Log::error('Error loading statuses', ['tab' => $tab, 'error' => $e->getMessage()]);
            return response()->json(['success' => false, 'message' => 'Error loading statuses'], 500);
        }
    }

    /**
     * Get aggregated stats for a tab.
     */
    public function stats(Request $request, string $tab): JsonResponse
    {
        if (! in_array($tab, ['nepredate', 'predate', 'retururi'], true)) {
            return response()->json(['success' => false, 'message' => 'Invalid tab'], 400);
        }

        try {
            $user = $request->user();
            if ($user === null || ($user->expeditor_id ?? 0) == 0) {
                return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
            }

            $swapped = $request->input('swapped', '0') === '1';
            $stats = $this->expeditiiService->getStats($request, $user, $tab, $swapped);

            return response()->json(['success' => true, 'data' => $stats]);
        } catch (\Exception $e) {
            Log::error('Error loading stats', ['tab' => $tab, 'error' => $e->getMessage()]);
            return response()->json(['success' => false, 'message' => 'Error loading stats'], 500);
        }
    }
}
