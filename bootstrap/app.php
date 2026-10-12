<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias(['role' => \App\Http\Middleware\EnsureRole::class]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        // Upload melebihi post_max_size PHP: kembali ke halaman asal dengan pesan ramah.
        // (Dilempar sebelum session dimulai, jadi pesan dikirim lewat query string.)
        $exceptions->render(function (\Illuminate\Http\Exceptions\PostTooLargeException $e, Request $request) {
            $message = 'Ukuran file terlalu besar. Maksimal 2 MB.';
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
