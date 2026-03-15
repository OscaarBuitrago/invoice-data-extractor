<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Enums\UserRole;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RequiresCompanyContext
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user?->role === UserRole::SuperAdmin) {
            return $next($request);
        }

        if (! session()->has('active_company_id')) {
            return redirect()->route('context.select');
        }

        return $next($request);
    }
}
