<?php

use App\Exceptions\InsufficientStockException;
use App\Http\Middleware\AdminMiddleware;
use App\Http\Middleware\EnsureAdminPermission;
use App\Jobs\ExpireStockReservations;
use Illuminate\Console\Scheduling\Schedule;
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
        $middleware->alias([
            'admin.auth' => AdminMiddleware::class,
            'admin.perm' => EnsureAdminPermission::class,
        ]);

        // Set by the Meta/GA/TikTok browser scripts and read back for server-side events.
        $middleware->encryptCookies(except: ['_fbp', '_fbc', '_ga', '_ttp']);
    })
    ->withSchedule(function (Schedule $schedule): void {
        // Release stock held by carts that never completed checkout.
        $schedule->job(new ExpireStockReservations)->everyMinute()->withoutOverlapping();
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        // Stock conflicts (inventory adjust, POS, order moves) go back to the form as an error snackbar, not a 500.
        $exceptions->render(function (InsufficientStockException $e, Request $request) {
            if ($request->expectsJson()) {
                return response()->json(['message' => $e->getMessage()], 422);
            }

            return back()->withInput()->with('error', $e->getMessage());
        });
    })->create();
