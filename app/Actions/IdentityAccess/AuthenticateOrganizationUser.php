<?php

namespace App\Actions\IdentityAccess;

use App\Models\OrganizationUser;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class AuthenticateOrganizationUser
{
    public const SESSION_AUTH_VERSION = 'identity_access.auth_version';

    /**
     * @param  array{email: string, password: string}  $credentials
     */
    public function handle(Request $request, array $credentials): OrganizationUser
    {
        $key = $this->throttleKey($credentials['email'], $request->ip());

        if (RateLimiter::tooManyAttempts($key, 5)) {
            $seconds = RateLimiter::availableIn($key);

            throw ValidationException::withMessages([
                'email' => trans('auth.throttle', [
                    'seconds' => $seconds,
                    'minutes' => (int) ceil($seconds / 60),
                ]),
            ]);
        }

        if (! Auth::guard('web')->attempt($credentials)) {
            RateLimiter::hit($key, 60);

            throw ValidationException::withMessages([
                'email' => trans('auth.failed'),
            ]);
        }

        RateLimiter::clear($key);
        $request->session()->regenerate();

        $user = Auth::guard('web')->user();

        if (! $user instanceof OrganizationUser) {
            Auth::guard('web')->logout();

            throw ValidationException::withMessages([
                'email' => trans('auth.failed'),
            ]);
        }

        $request->session()->put(self::SESSION_AUTH_VERSION, $user->auth_version);

        return $user;
    }

    private function throttleKey(string $email, ?string $ip): string
    {
        return Str::transliterate(OrganizationUser::normalizeEmail($email).'|'.($ip ?? 'unknown'));
    }
}
