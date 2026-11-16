<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Batasi pembuatan akun dan permintaan reset kata sandi per IP agar tidak disalahgunakan.
        foreach (['register', 'forgot-password'] as $name) {
            RateLimiter::for($name, fn (Request $request) => Limit::perMinute(5)
                ->by($name.'|'.$request->ip())
                ->response(function (Request $request, array $headers) {
                    $seconds = (int) ($headers['Retry-After'] ?? 60);
                    $message = "Terlalu banyak percobaan. Silakan coba lagi dalam {$seconds} detik.";

                    if ($request->expectsJson()) {
                        return response()->json(['message' => $message], 429, $headers);
                    }

                    return back()
                        ->withInput($request->except('password', 'password_confirmation'))
                        ->withErrors(['email' => $message]);
                }));
        }
    }
}
