<?php

namespace App\Http\Controllers\IdentityAccess;

use App\Actions\IdentityAccess\EnqueueOrganizationUserMail;
use App\Http\Controllers\Controller;
use App\Http\Requests\IdentityAccess\ForgotPasswordRequest;
use App\Models\OrganizationUser;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Timebox;
use Inertia\Inertia;
use Inertia\Response;

class PasswordResetLinkController extends Controller
{
    public function create(): Response
    {
        return Inertia::render('identity-access/ForgotPassword');
    }

    public function store(
        ForgotPasswordRequest $request,
        EnqueueOrganizationUserMail $mailTasks,
        Timebox $timebox,
    ): RedirectResponse {
        return $timebox->call(function () use ($request, $mailTasks): RedirectResponse {
            $user = OrganizationUser::query()
                ->where('email', $request->string('email')->toString())
                ->first();

            if ($user instanceof OrganizationUser) {
                $mailTasks->passwordReset($user, App::currentLocale());
            }

            return back()->with('status', __('ui.forgotPassword.status'));
        }, 200_000);
    }
}
