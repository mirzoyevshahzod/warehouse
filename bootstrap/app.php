<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use App\Http\Middleware\LogUpload;
use App\Http\Middleware\TokenCheck;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        //
    })
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'token.check' => TokenCheck::class,
            'log.upload' => LogUpload::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        
    })->create();
