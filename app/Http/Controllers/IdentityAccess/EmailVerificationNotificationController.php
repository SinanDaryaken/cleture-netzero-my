<?php

namespace App\Http\Controllers\IdentityAccess;

use App\Actions\IdentityAccess\EnqueueOrganizationUserMail;
use App\Http\Controllers\Controller;
use App\Models\OrganizationUser;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;

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

        $mailTasks->emailVerification($user, App::currentLocale());

        return back()->with(
            'status',
            'Doğrulama bağlantısı hazırlanıyor. Birkaç saniye içinde gelen kutunuzu kontrol edin.',
        );
    }
}
