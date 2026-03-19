<?php

namespace App\Http\Controllers\Comenzi;

use App\Http\Controllers\Controller;
use App\Http\Requests\Comenzi\ComandaCreateRequest;
use App\Http\Requests\Comenzi\ComandaUpdateRequest;
use App\Services\ComandaService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

class ComandaController extends Controller
{
    public function __construct(
        private readonly ComandaService $comandaService
    ) {
    }
    /**
     * Display a listing of the resource.
     */
    public function index(): Response
    {
        return Inertia::render('comenzi/index', [
            'pcs' => session('pcs', [])
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(): Response
    {
        return Inertia::render('comenzi/index', [
            'pcs' => session('pcs', [])
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(ComandaCreateRequest $request): JsonResponse
    {
        $user = Auth::user();
        if($user === null || ($user->expeditor_id ?? 0) == 0) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized'
            ], 401);
        }

        try {
            $comanda_id = $this->comandaService->create($request->validated(), $user->id);

            if($comanda_id > 0)
                return response()->json([
                    'success' => true,
                    'message' => 'Comanda created successfully.',
                    'data' => $comanda_id
                ], 201);
            return response()->json([
                'success' => false,
                'message' => 'Eroare creare comanda',
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
        $user = Auth::user();
        if($user === null || ($user->expeditor_id ?? 0) == 0) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized'
            ], 401);
        }
        $comanda = $this->comandaService->getById($user->expeditor_id, $id);

        return response()->json([
            'success' => true,
            'message' => 'Order retrieved successfully.',
            'data' => $comanda
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
        $comanda = $this->comandaService->getForEdit($user->expeditor_id, $id);

        return response()->json([
            'success' => true,
            'message' => 'Order retrieved successfully.',
            'data' => $comanda
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(ComandaUpdateRequest $request, int $id): JsonResponse
    {
        $user = Auth::user();
        if($user === null || ($user->expeditor_id ?? 0) == 0) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized'
            ], 401);
        }

        try {
            $this->comandaService->update($user, $id, $request->validated());
            return response()->json([
                'success' => true,
                'message' => 'Order updated successfully.',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error: ' . $e->getMessage()
            ], 404);
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
            $this->comandaService->delete($user, $id);

            return response()->json([
                'success' => true,
                'message' => 'Order deleted successfully.'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error: ' . $e->getMessage()
            ], 404);
        }
    }

    /**
     * Return paginated comenzi data for Tabulator
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
            $rows = (int) $request->input('rows', 50);
            $sortField = $request->input('sortField', 'id');
            $sortOrder = $request->input('sortOrder', 'desc');

            $comenzi = $this->comandaService->getPaginated($user->expeditor_id, $page, $rows, $sortField, $sortOrder, $request);

            return response()->json([
                'success' => true,
                'data' => $comenzi
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error loading comenzi: ' . $e->getMessage()
            ], 500);
        }
    }
}
