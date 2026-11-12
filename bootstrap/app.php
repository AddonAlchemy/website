<?php

declare(strict_types=1);

use App\Exceptions\InvalidReleaseArchive;
use App\Exceptions\MarketplaceException;
use App\Http\Middleware\EnforceMaintenanceMode;
use App\Http\Middleware\EnsureRole;
use App\Http\Middleware\EnsureVendor;
use App\Http\Middleware\ForceJsonResponse;
use App\Http\Middleware\SetSecurityHeaders;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Laravel\Sanctum\Http\Middleware\CheckAbilities;
use Laravel\Sanctum\Http\Middleware\CheckForAnyAbility;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->web(append: [
            SetSecurityHeaders::class,
            EnforceMaintenanceMode::class,
        ]);

        $middleware->api(prepend: [
            ForceJsonResponse::class,
        ]);

        $middleware->alias([
            'role' => EnsureRole::class,
            'vendor' => EnsureVendor::class,
            'ability' => CheckForAnyAbility::class,
            'abilities' => CheckAbilities::class,
        ]);

        $middleware->redirectGuestsTo(static fn (): string => route('login'));
        $middleware->redirectUsersTo(static fn (): string => route('dashboard'));
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $domainFailure = static function (Throwable $exception, Request $request) {
            if ($request->expectsJson()) {
                return response()->json(['message' => $exception->getMessage()], 422);
            }

            return back()->withInput()->with('error', $exception->getMessage());
        };

        $exceptions->render(static fn (MarketplaceException $e, Request $request) => $domainFailure($e, $request));
        $exceptions->render(static fn (InvalidReleaseArchive $e, Request $request) => $domainFailure($e, $request));
        $exceptions->dontReport([MarketplaceException::class, InvalidReleaseArchive::class]);
    })->create();
