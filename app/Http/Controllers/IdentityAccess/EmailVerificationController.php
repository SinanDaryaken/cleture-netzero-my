<?php

namespace App\Http\Controllers\IdentityAccess;

use App\Actions\IdentityAccess\VerifyOrganizationUserEmail;
use App\Http\Controllers\Controller;
use App\Models\OrganizationUser;
use Illuminate\Http\RedirectResponse;

class EmailVerificationController extends Controller
{
    public function __invoke(
        OrganizationUser $organizationUser,
        string $token,
        VerifyOrganizationUserEmail $verify,
    ): RedirectResponse {
        if (! $verify->handle($organizationUser, $token)) {
            return redirect()->route('login.create')->with(
                'status',
                'Doğrulama bağlantısının süresi dolmuş veya bağlantı geçersiz.',
            );
        }

        return redirect()->route('login.create')->with(
            'status',
            'E-posta adresiniz doğrulandı. Şimdi giriş yapabilirsiniz.',
        );
    }
}
