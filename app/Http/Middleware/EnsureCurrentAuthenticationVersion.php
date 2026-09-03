<?php

namespace App\Http\Middleware;

use App\Actions\IdentityAccess\AuthenticateOrganizationUser;
use App\Models\OrganizationUser;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsureCurrentAuthenticationVersion
{
    /**
     * @param  Closure(Request): Response  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        $sessionVersion = $request->session()->get(
            AuthenticateOrganizationUser::SESSION_AUTH_VERSION,
        );

        if (! $user instanceof OrganizationUser || $sessionVersion !== $user->auth_version) {
            Auth::guard('web')->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('login.create')->with(
                'status',
                'Güvenliğiniz için yeniden giriş yapmanız gerekiyor.',
            );
        }

        return $next($request);
    }
}
