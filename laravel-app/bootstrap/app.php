<?php

use App\Http\Middleware\AdminMiddleware;
use App\Http\Middleware\CheckEnabled;
use App\Http\Middleware\UnicodeCorrection;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__ . '/../routes/web.php',
        api: __DIR__ . '/../routes/api.php',
        commands: __DIR__ . '/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware
            ->append([
                UnicodeCorrection::class
            ])
            ->alias([
                'admin' => AdminMiddleware::class,
                'enabled' => CheckEnabled::class,
            ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
