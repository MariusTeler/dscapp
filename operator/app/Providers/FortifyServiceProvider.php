<?php

namespace App\Providers;

use App\Models\User;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Laravel\Fortify\Fortify;

class FortifyServiceProvider extends ServiceProvider
{
    public function register(): void {}

    public function boot(): void
    {
        $this->configureViews();
        $this->configureRateLimiting();

        Fortify::username('user');

        Fortify::authenticateUsing(function (Request $request) {
            $user = User::where('user', $request->user)
                ->where('nivel_acces', '>=', 5)
                ->where('activ', 1)
                ->first();

            if ($user && Hash::check($request->password, $user->hashParola)) {
                return $user;
            }

            return null;
        });
    }

    private function configureViews(): void
    {
        Fortify::loginView(fn () => Inertia::render('auth/Login'));
    }

    private function configureRateLimiting(): void
    {
        RateLimiter::for('login', function (Request $request) {
            $key = Str::lower($request->input(Fortify::username())).'|'.$request->ip();
            return Limit::perMinute(5)->by($key);
        });
    }
}
