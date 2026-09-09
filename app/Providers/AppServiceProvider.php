<?php

namespace App\Providers;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Blade;
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
        // @adminCan('products') ... @endadminCan — hides write controls the
        // current admin role may not use (server-side gate still enforces it).
        Blade::if('adminCan', function (string $area): bool {
            $admin = Auth::guard('admin')->user();

            return $admin !== null && $admin->role->canManage($area);
        });
    }
}
