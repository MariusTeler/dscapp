<?php

namespace App\Http\Middleware;

use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that's loaded on the first page visit.
     *
     * @see https://inertiajs.com/server-side-setup#root-template
     *
     * @var string
     */
    protected $rootView = 'app';


    /**
     * Determines the current asset version.
     *
     * @see https://inertiajs.com/asset-versioning
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @see https://inertiajs.com/shared-data
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        //Log::debug("HandleInertiaRequests::share called for url: " . $request->url());
        return array_merge(parent::share($request), [
            'appName' => config('app.name'),
            // Lazily...
            'auth.user' => fn () => $request->user()
                ? $request->user()->only('id', 'user', 'email', 'nume', 'telefon', 'expeditor_id')
                : null,
            'prefs' => fn () => $request->user()
                ? [
                    'master_id' => session('master_id', $request->user()->expeditor_id),
                    'print' => $request->user()->print_awb, //print AWB
                    'cc' => session('cc', false), //cont colector
                    'def_sms' => $request->user()->def_sms, //default notificari SMS
                    'def_obsv' => $request->user()->def_obsv, //default observatii AWB
                    'selectie_puncte_de_lucru' => $request->user()->selectie_puncte_de_lucru,
                    'importcsv' => $request->user()->importcsv,
                    'preturi'   => $request->user()->preturi,
                    'recantarite' => $request->user()->recantarite,
                    'show_master_clienti' => $request->user()->show_master_clienti,
                    'def_retur_nc' => in_array($request->user()->expeditor_id, config('awb.can_import_xls')),
                    'importxls' => in_array($request->user()->expeditor_id, config('awb.can_import_xls')),
                ]
                : null,
            'sidebarOpen' => ! $request->hasCookie('sidebar_state') || $request->cookie('sidebar_state') === 'true',
            'flash' => [
                'message' => fn () => $request->session()->get('message')
            ],
        ]);
    }
}
