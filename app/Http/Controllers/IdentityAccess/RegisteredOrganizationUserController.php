<?php

namespace App\Http\Controllers\IdentityAccess;

use App\Actions\IdentityAccess\AuthenticateOrganizationUser;
use App\Actions\IdentityAccess\RegisterOrganizationUser;
use App\Http\Controllers\Controller;
use App\Http\Requests\IdentityAccess\RegisterRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class RegisteredOrganizationUserController extends Controller
{
    public function create(): Response
    {
        return Inertia::render('identity-access/Register');
    }

    public function store(
        RegisterRequest $request,
        RegisterOrganizationUser $register,
    ): RedirectResponse {
        $user = $register->handle($request->validated(), App::currentLocale());

        Auth::guard('web')->login($user);
        $request->session()->regenerate();
        $request->session()->put(
            AuthenticateOrganizationUser::SESSION_AUTH_VERSION,
            $user->auth_version,
        );

        return redirect()->route('verification.notice');
    }
}
