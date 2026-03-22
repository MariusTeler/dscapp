<?php

namespace App\Http\Controllers;

use App\Models\Expeditie;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;

class ExpeditiiController extends Controller
{
    public function cautare(Request $request): InertiaResponse
    {
        $filters = $request->only([
            'operatiune', 'search',
            'data_start', 'data_final',
            'operator',
            'livrare',
            'expeditor_localitate', 'expeditor',
            'destinatar_localitate', 'destinatar',
            'curier_preluare', 'curier_livrare',
            'expeditii',
            'sort_col', 'sort_dir',
        ]);

        // Sortare cu whitelist explicit
        $sortCol = in_array($filters['sort_col'] ?? '', Expeditie::SORT_WHITELIST)
            ? $filters['sort_col']
            : 'ep.data_expeditie';
        $sortDir = ($filters['sort_dir'] ?? 'desc') === 'asc' ? 'asc' : 'desc';

        try {
            $expeditii = Expeditie::query()
                ->withFilters($filters)
                ->orderBy($sortCol, $sortDir)
                ->paginate(100)
                ->withQueryString();
        } catch (\Illuminate\Database\QueryException $e) {
            // Tabelele DSC lipsesc local (nu există dump importat)
            $expeditii = new \Illuminate\Pagination\LengthAwarePaginator([], 0, 100);
        }

        return Inertia::render('expeditii/Cautare', [
            'expeditii' => $expeditii,
            'filters' => $filters,
        ]);
    }

    public function export(): Response
    {
        return response('Export not implemented in POC', 501);
    }
}
