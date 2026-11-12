<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

final class EnsureVendor
{
    public function handle(Request $request, Closure $next, ?string $requirement = null): Response
    {
        $vendor = $request->user()?->vendor;

        if ($vendor === null) {
            return redirect()->route('vendor.apply');
        }

        if ($requirement === 'approved' && ! $vendor->isApproved()) {
            return redirect()
                ->route('vendor.dashboard')
                ->with('error', 'Dein Vendor-Account ist noch nicht freigeschaltet.');
        }

        return $next($request);
    }
}
