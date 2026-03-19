<?php

namespace App\Services\Helpers;

use Illuminate\Support\Facades\DB;
use App\Services\Helpers\ToolsService;
use Illuminate\Support\Facades\Log;

class LocalitatiService
{
    /**
     * Get autocomplete suggestions for localitati
     */
    public static function getAutocomplete(string $search = '', int $limit = 20): array
    {
        if($limit > 30) $limit = 30;
        if(empty($search)) {
            return [];
        }

        return DB::table('localitati')
                    ->select('cod_lc', 'nume_lc', 'cod_jd', 'dist_km')
                    ->where('nume_lc', 'like', "{$search}%")
                    ->where('deleted', 0)
                    ->orderBy('nume_lc', 'asc')
                    ->limit($limit)
                    ->get()
                    ->map(function ($item) {
                        return [
                            'value' => $item->cod_lc,
                            'text' => strtoupper($item->nume_lc),
                            'option' => strtoupper($item->nume_lc) . " (Jud. " . strtoupper($item->cod_jd) . ") - " . ($item->dist_km <= 15 ? 0 : $item->dist_km) . " km",
                            'judet' => strtoupper($item->cod_jd),
                            'km' => ($item->dist_km <= 15 ? 0 : $item->dist_km),
                        ];
                    })
                    ->toArray();
    }

    public static function getByLocalitateJudet(string $localitate, string $judet): array
    {
        $localitate = ToolsService::sSanitizeCleanEdges($localitate);
        if(ToolsService::testLocalitateBucuresti($localitate)) {
            $localitate = 'Bucuresti';
            $judet = 'Bucuresti';
        }

        Log::debug("Searching for localitate: '$localitate' in judet: '$judet'");

        $row = DB::table('localitati as lc')
            ->select('lc.cod_lc', 'lc.dist_km')
            ->join('judete as jd', 'lc.cod_jd', '=', 'jd.cod_jd')
            ->whereRaw("replace(replace(nume_lc,' ',''),'-','') like replace(replace(?,' ',''),'-','')", [$localitate])
            ->whereRaw("replace(replace(nume_jd,' ',''),'-','') like replace(replace(?,' ',''),'-','')", [$judet])
            ->first();

        return $row ? (array)$row : [];
    }
}
