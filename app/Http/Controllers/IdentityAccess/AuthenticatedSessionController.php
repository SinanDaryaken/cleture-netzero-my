<?php

namespace App\Http\Controllers\IdentityAccess;

use App\Actions\IdentityAccess\AuthenticateOrganizationUser;
use App\Actions\IdentityAccess\LogoutOrganizationUser;
use App\Http\Controllers\Controller;
use App\Http\Requests\IdentityAccess\LoginRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class AuthenticatedSessionController extends Controller
{
    public function create(): Response
    {
        return Inertia::render('identity-access/Login');
    }

    public function store(
        LoginRequest $request,
        AuthenticateOrganizationUser $authenticate,
    ): RedirectResponse {
        $user = $authenticate->handle($request, $request->validated());

        return redirect()->intended(
            $user->hasVerifiedEmail()
                ? route('dashboard')
                : route('verification.notice'),
        );
    }

    public function destroy(
        Request $request,
        LogoutOrganizationUser $logout,
    ): RedirectResponse {
        $logout->handle($request);

        return redirect()->route('login.create');
    }
}
