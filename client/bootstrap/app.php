<?php

use App\Http\Middleware\EnsureUserHasEmail;
use App\Http\Middleware\HandleAppearance;
use App\Http\Middleware\HandleInertiaRequests;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Middleware\AddLinkHeadersForPreloadedAssets;
use Inertia\Inertia;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->encryptCookies(except: ['appearance', 'sidebar_state']);

        $middleware->web(append: [
            HandleAppearance::class,
            HandleInertiaRequests::class,
            EnsureUserHasEmail::class,
            AddLinkHeadersForPreloadedAssets::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        // Handle session expired / CSRF token mismatch
        $exceptions->render(function (\Illuminate\Session\TokenMismatchException $e, $request) {
            if ($request->header('X-Inertia')) {
                return redirect()->route('login')->with('error', 'Sesiunea a expirat. Vă rugăm să reîncărcați pagina.');
            }
            
            if ($request->expectsJson()) {
                return response()->json([
                    'message' => 'Sesiunea a expirat. Vă rugăm să reîncărcați pagina.',
                    'error' => 'Session expired'
                ], 419);
            }

            return redirect()->route('login')->with('error', 'Sesiunea a expirat. Vă rugăm să vă autentificați din nou.');
        });

        // Handle unauthenticated users
        $exceptions->render(function (\Illuminate\Auth\AuthenticationException $e, $request) {
            if ($request->header('X-Inertia')) {
                return redirect()->route('login')->with('error', 'Sesiunea a expirat. Vă rugăm să reîncărcați pagina.');
            }
            
            if ($request->expectsJson()) {
                return response()->json([
                    'message' => 'Neautentificat. Vă rugăm să vă autentificați.',
                    'error' => 'Unauthenticated'
                ], 401);
            }

            return redirect()->route('login')->with('error', 'Sesiunea a expirat. Vă rugăm să reîncărcați pagina.');
        });

        // Handle database connection errors
        $exceptions->render(function (\PDOException $e, $request) {
            if ($request->header('X-Inertia')) {
                return Inertia::render('errors/database', [
                    'message' => 'Eroare de conexiune la baza de date.',
                    'error' => 'Database connection failed.',
                ]);
            }
            
            return response()->json([
                'message' => 'Eroare de conexiune la baza de date. Verificați configurația.',
                'error' => 'Database connection failed.',
            ], 500);
        });

        $exceptions->render(function (\Illuminate\Database\QueryException $e, $request) {
            if ($request->header('X-Inertia')) {
                return Inertia::render('errors/database', [
                    'message' => 'Eroare la execuția interogării bazei de date.',
                    'error' => null
                ]);
            }
            
            // Handle connection errors (2002 = Can't connect)
            if ($e->getCode() === 2002 || str_contains($e->getMessage(), 'getaddrinfo')) {
                return response()->json([
                    'message' => 'Nu se poate conecta la baza de date.',
                    'error' => 'Database host unreachable'
                ], 500);
            }

            return response()->json([
                'message' => 'Eroare la execuția interogării bazei de date.',
                'error' => 'Query execution failed'
            ], 500);
        });
    })->create();
