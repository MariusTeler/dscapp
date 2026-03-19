<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class ApiBasicAuth
{
    public function handle(Request $request, Closure $next)
    {
        // Preia header Authorization
        $authHeader = $request->header('Authorization');

        if (!$authHeader || !preg_match('/Basic\s+(.*)$/i', $authHeader, $matches)) {
            return response()->json(['message' => 'Unauthorized'], 401)
                             ->header('WWW-Authenticate', 'Basic realm="API"');
        }

        // Decode base64(username:password)
        $credentials = explode(':', base64_decode($matches[1]), 2);
        if (count($credentials) !== 2) {
            return response()->json(['message' => 'Access denied'], 401)
                             ->header('WWW-Authenticate', 'Basic realm="API"');
        }

        [$name, $password] = $credentials;

        //authenticate against apiusers
        try {
            $apiUser = DB::table('apiusers as api')
                ->join('users as u', 'api.users_id', '=', 'u.id')
                ->join('clienti as cl', function($join) {
                    $join->on('cl.cod_cl', '=', 'u.expeditor_id')
                        ->where('cl.activ', 1)
                        ->where('cl.sters', 0)
                        ->where(function($query) {
                            $query->where('cl.master', 0)
                                ->orWhereRaw('cl.master = cl.cod_cl');
                        });
                })
                ->leftJoin('localitati as lce', 'lce.cod_lc', '=', 'cl.cod_lc')
                ->leftJoin('clienti as clm', function($join) {
                    $join->on('clm.master', '=', 'cl.cod_cl')
                        ->where('clm.activ', 1);
                })
                ->select('api.password_hash', 'api.users_id as id', 'u.expeditor_id as expeditor_id',
                        'cl.nume as expeditor_nume', 'u.nume as expeditor_contact', 'u.telefon as expeditor_telefon', 'u.email as expeditor_email',
                        'cl.adresa as expeditor_adresa', 'lce.cod_lc as expeditor_localitate_id', 'lce.nume_lc as expeditor_localitate', 'lce.dist_km as expeditor_localitate_km',
                        'cl.cc as expeditor_cc', 'cl.mod_plata as expeditor_mod_plata',
                        'u.selectie_puncte_de_lucru', 'u.print_awb', 'u.preturi',
                        DB::raw('group_concat(distinct clm.cod_cl) as pcIds'),
                )
                ->where('api.status', 1)
                ->where('api.name', 'like', $name)
                ->where('api.is_scanner', 0)
                ->where('api.is_android', 0)
                ->groupBy('u.id')
                ->first();

            // Verifică parola
            if (!$apiUser || !Hash::check($password, $apiUser->password_hash)) {
                return response()->json(['message' => 'Unauthorized'], 401)
                                ->header('WWW-Authenticate', 'Basic realm="API"');
            }

            unset($apiUser->password_hash); // Nu păstra hash-ul parolei în request
            // Attach userId to request
            $request->merge(['user' => (array) $apiUser]);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Unauthorized'], 401)
                             ->header('WWW-Authenticate', 'Basic realm="API"');
        }
        // Dacă e ok, lasă request-ul să continue
        return $next($request);
    }
}
