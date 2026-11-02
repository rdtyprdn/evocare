<?php

use App\Http\Middleware\EnsureRole;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Exceptions\PostTooLargeException;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias(['role' => EnsureRole::class]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        // Tamu yang mencoba mendaftar kegiatan (POST) dikembalikan ke detail kegiatan setelah login.
        $exceptions->render(function (AuthenticationException $e, Request $request) {
            if ($request->routeIs('registrations.store') && $request->route('event') && ! $request->expectsJson()) {
                $request->session()->put('url.intended', route('events.show', $request->route('event')));

                return redirect()->route('login')->with('status', 'Silakan masuk terlebih dahulu untuk mendaftar kegiatan ini.');
            }
        });
        // Upload melebihi post_max_size PHP: kembali ke halaman asal dengan pesan ramah.
        // (Dilempar sebelum session dimulai, jadi pesan dikirim lewat query string.)
        $exceptions->render(function (PostTooLargeException $e, Request $request) {
            $message = 'Ukuran file terlalu besar. Maksimal 10 MB per foto atau 30 MB per video.';
            if ($request->expectsJson()) {
                return response()->json(['message' => $message], 413);
            }
            $referer = (string) $request->headers->get('referer');
            $target = $referer !== '' && str_starts_with($referer, $request->getSchemeAndHttpHost())
                ? strtok($referer, '?') : route('profile.edit');

            return redirect()->to($target.'?upload_error=too_large');
        });
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );
    })->create();
