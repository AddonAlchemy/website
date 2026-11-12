<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Services\Settings;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

final class EnforceMaintenanceMode
{
    private const ALWAYS_REACHABLE = ['login', 'login.store', 'logout'];

    public function __construct(private readonly Settings $settings)
    {
    }

    public function handle(Request $request, Closure $next): Response
    {
        if (! $this->settings->get('maintenance')
            || $request->user()?->isAdmin()
            || $request->routeIs(...self::ALWAYS_REACHABLE)) {
            return $next($request);
        }

        return response()->view('errors.maintenance', [
            'message' => $this->settings->get('maintenance_message'),
        ], Response::HTTP_SERVICE_UNAVAILABLE);
    }
}
