<?php

namespace App\Actions\IdentityAccess;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class LogoutOrganizationUser
{
    public function handle(Request $request): void
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();
    }
}
