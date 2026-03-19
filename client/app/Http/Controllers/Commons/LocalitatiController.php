<?php

namespace App\Http\Controllers\Commons;

use App\Http\Controllers\Controller;
use App\Services\Helpers\LocalitatiService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class LocalitatiController extends Controller
{
    public function __construct(
        private readonly LocalitatiService $localitatiService
    ) {
    }

    /**
     * Get list of localitati for autocomplete
     */
    public function autocomplete(Request $request): JsonResponse
    {
        $search = $request->input('q', '');
        $limit = (int) $request->input('limit', 30);
        $user = $request->user();

        if($user === null) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized'
            ], 401);
        }

        $localitati = $this->localitatiService->getAutocomplete($search, $limit);

        return response()->json([
            'success' => true,
            'data' => $localitati
        ]);
    }
}
