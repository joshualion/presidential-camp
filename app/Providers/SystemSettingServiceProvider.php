<?php

namespace App\Providers;

use Illuminate\Support\Facades\Schema;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\View;
use App\Models\SystemSetting;
use Illuminate\Support\Facades\Route;
use App\Support\SafeDatabase;

class SystemSettingServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        // View::composer('*', function ($view) {
        //     $SystemSetting = SystemSetting::first();
        //     $view->with('SystemSetting', $SystemSetting);
        // });

        View::composer('*', function ($view) {
            if (SafeDatabase::hasTable('system_settings')) {
                // Only try to fetch the first record if the table exists
                $SystemSetting = SystemSetting::first();
                
                //send settings instance to views
                $view->with('SystemSetting', $SystemSetting);
            } else {
                // Optionally, provide a fallback or leave it as null
                $view->with('SystemSetting', null);
            }
        });


    }
}
