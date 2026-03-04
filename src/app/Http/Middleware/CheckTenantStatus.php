<?php

namespace App\Http\Middleware;

use Closure;
use App\Models\Domain;
use Illuminate\Http\Request;

class CheckTenantStatus
{
    public function handle(Request $request, Closure $next)
    {
        if (!tenant()) {
            return $next($request);
        }

        $domain = Domain::where('domain', $request->getHost())->first();

        if ($domain && $domain->status === 'suspended') {
            auth()->logout();
            $request->session()->invalidate();
            return response()->view('errors.suspended', [], 403);
        }

        return $next($request);
    }
}
