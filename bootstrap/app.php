<?php

use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Laravel\Sanctum\Http\Middleware\EnsureFrontendRequestsAreStateful;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->api(prepend: [
            EnsureFrontendRequestsAreStateful::class,
        ]);

        $middleware->throttleApi('60,1');
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->render(function (AuthenticationException $e, Request $request) {
            if ($request->expectsJson() || $request->is('api/*')) {
                return response()->json(['message' => 'No autenticado.'], 401);
            }
        });

        $exceptions->render(function (AccessDeniedHttpException $e, Request $request) {
            if (($request->expectsJson() || $request->is('api/*')) && config('app.debug')) {
                $user = $request->user();
                $previous = $e->getPrevious();
                $debug = [
                    'message' => 'Acción no autorizada.',
                    'user_id' => $user?->id,
                    'user_role' => $user?->role?->slug,
                    'endpoint' => $request->method() . ' ' . $request->path(),
                ];

                if ($previous instanceof AuthorizationException) {
                    $debug['policy_message'] = $previous->getMessage();
                }

                // Include route model binding details
                foreach ($request->route()?->parameters() ?? [] as $key => $value) {
                    if (is_object($value) && method_exists($value, 'getKey')) {
                        $debug["route_{$key}_id"] = $value->getKey();
                        if (method_exists($value, 'toArray')) {
                            $model = $value->toArray();
                            // Include key task fields for debugging
                            $debug["route_{$key}"] = array_intersect_key($model, array_flip([
                                'id', 'area_id', 'created_by', 'assigned_to_user_id',
                                'current_responsible_user_id', 'status',
                            ]));
                        }
                    } else {
                        $debug["route_{$key}"] = $value;
                    }
                }

                return response()->json($debug, 403);
            }
        });
    })->create();
