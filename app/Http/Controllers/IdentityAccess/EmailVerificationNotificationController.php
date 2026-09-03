<?php

namespace App\Http\Controllers\IdentityAccess;

use App\Actions\IdentityAccess\EnqueueOrganizationUserMail;
use App\Http\Controllers\Controller;
use App\Models\OrganizationUser;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class EmailVerificationNotificationController extends Controller
{
    public function __invoke(
        Request $request,
        EnqueueOrganizationUserMail $mailTasks,
    ): RedirectResponse {
        $user = $request->user();

        if (! $user instanceof OrganizationUser || $user->hasVerifiedEmail()) {
            return redirect()->route('dashboard');
        }

        $mailTasks->emailVerification($user);

        return back()->with(
            'status',
            'Doğrulama bağlantısı hazırlanıyor. Birkaç saniye içinde gelen kutunuzu kontrol edin.',
        );
    }
}
