<?php

use App\Http\Middleware\KuyakEyenIno;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use App\Http\Middleware\Accesslevel;
use App\Http\Middleware\CheckUserOnboarding;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',

        commands: __DIR__.'/../routes/console.php',
        channels: __DIR__.'/../routes/channels.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->alias([
                         'access_level' => Accesslevel::class,
                         'check_onboarding' => CheckUserOnboarding::class, // Add this alias
                         'kuyak_eyen_ino' => KuyakEyenIno::class,
                        ]);

    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();
