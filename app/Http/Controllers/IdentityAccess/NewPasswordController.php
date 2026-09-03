<?php

namespace App\Http\Controllers\IdentityAccess;

use App\Actions\IdentityAccess\ResetOrganizationUserPassword;
use App\Http\Controllers\Controller;
use App\Http\Requests\IdentityAccess\ResetPasswordRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Password;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class NewPasswordController extends Controller
{
    public function create(Request $request, string $token): Response
    {
        return Inertia::render('identity-access/ResetPassword', [
            'email' => $request->string('email')->toString(),
            'token' => $token,
        ]);
    }

    public function store(
        ResetPasswordRequest $request,
        ResetOrganizationUserPassword $resetPassword,
    ): RedirectResponse {
        $status = $resetPassword->handle(
            email: $request->string('email')->toString(),
            password: $request->string('password')->toString(),
            token: $request->string('token')->toString(),
            locale: App::currentLocale(),
        );

        if ($status !== Password::PasswordReset) {
            throw ValidationException::withMessages([
                'email' => __('ui.resetPassword.invalidLink'),
            ]);
        }

        return redirect()->route('login.create')->with('status', __($status));
    }
}
