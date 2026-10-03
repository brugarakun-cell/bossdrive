<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use App\Http\Middleware\AdminMiddleware;
use App\Http\Middleware\StaffMiddleware;
use App\Http\Middleware\TrackUserActivity;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->trustProxies(at: '*');
        $middleware->web(append: [TrackUserActivity::class]);
        $middleware->redirectGuestsTo('/user/login');
        $middleware->redirectUsersTo('/user/dashboard');
        $middleware->validateCsrfTokens(except: [
            'admin/login',
            'staff/login',
        ]);
        $middleware->alias([
            'admin' => AdminMiddleware::class,
            'staff' => StaffMiddleware::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
