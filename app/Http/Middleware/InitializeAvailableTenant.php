<?php

namespace App\Http\Middleware;

use App\Models\OrganizationUser;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class InitializeAvailableTenant
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        $tenant = $user instanceof OrganizationUser
            ? $user->organization()->with('tenant')->first()?->tenant
            : null;

        abort_unless($tenant?->isAvailable(), Response::HTTP_FORBIDDEN);

        tenancy()->initialize($tenant);

        try {
            return $next($request);
        } finally {
            tenancy()->end();
        }
    }
}
