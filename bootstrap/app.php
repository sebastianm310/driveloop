<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Exceptions\PostTooLargeException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__ . '/../routes/web.php',
        api: __DIR__ . '/../routes/api.php',
        commands: __DIR__ . '/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        if (env('APP_ENV') === 'local') {
            $middleware->trustProxies(at: '*');
        }

        //-------------------------------------------------------------------------------------------------------------------
        // --- PASO B: EXCEPCIÓN DE MERCADO PAGO ---
        $middleware->validateCsrfTokens(except: [
            'api/webhook', // Si tu ruta está en api.php
            'webhook',     // Si tu ruta está en web.php
            'pagos/webhook/*', // Exime todas las rutas de webhooks de pasarelas
        ]);

        // Registrar alias del middleware para verificar documentos aprobados
        $middleware->alias([
            'verified_docs' => \App\Modules\GestionUsuario\Middleware\EnsureUserIsVerified::class,
            // Spatie Laravel Permission Middleware
            'role' => \Spatie\Permission\Middleware\RoleMiddleware::class,
            'permission' => \Spatie\Permission\Middleware\PermissionMiddleware::class,
            'role_or_permission' => \Spatie\Permission\Middleware\RoleOrPermissionMiddleware::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->render(fn(PostTooLargeException $e, $request) => back());
    })->create();