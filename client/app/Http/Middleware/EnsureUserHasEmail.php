<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserHasEmail
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        // Verificăm dacă utilizatorul este autentificat
        if ($user && empty($user->email)) {
            // Exclude rutele de completare email pentru a evita loop-uri infinite
            $excludedRoutes = ['email.missing', 'email.update', 'logout'];
            
            if (! $request->routeIs($excludedRoutes)) {
                return redirect()->route('email.missing');
            }
        }

        return $next($request);
    }
}
