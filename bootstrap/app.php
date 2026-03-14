<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Illuminate\Auth\AuthenticationException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        api: __DIR__ . '/../routes/api.php',
        web: __DIR__ . '/../routes/web.php',
        commands: __DIR__ . '/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        //
    })
    ->withExceptions(function (Exceptions $exceptions) {

        // 1. التعامل مع 401 (Unauthorized)
        $exceptions->render(function (AuthenticationException $e, Request $request) {
            if ($request->is('api/*')) {
                return response()->json([
                    "success" => false,
                    "message" => "Non authentifié."
                ], 401);
            }
        });

        // 2. التعامل مع 403 (Forbidden)
        $exceptions->render(function (AccessDeniedHttpException $e, Request $request) {
            if ($request->is('api/*')) {
                return response()->json([
                    "success" => false,
                    "message" => "Vous n'êtes pas autorisé à effectuer cette action."
                ], 403);
            }
        });

        // 3. التعامل مع 404 (Not Found)
        $exceptions->render(function (NotFoundHttpException $e, Request $request) {
            if ($request->is('api/*')) {
                return response()->json([
                    "success" => false,
                    "message" => "Ressource introuvable."
                ], 404);
            }
        });

        // 4. التعامل مع 500 (Internal Server Error)
        $exceptions->render(function (Throwable $e, Request $request) {
            if ($request->is('api/*')) {
                // ملاحظة: هاد الخطأ كيشمل أي حاجة تفركعات في السيستم
                return response()->json([
                    "success" => false,
                    "message" => "Une erreur interne est survenue. Veuillez réessayer."
                ], 500);
            }
        });
    })->create();
