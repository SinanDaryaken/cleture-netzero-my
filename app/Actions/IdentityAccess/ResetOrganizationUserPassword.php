<?php

namespace App\Actions\IdentityAccess;

use App\Models\OrganizationUser;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Contracts\Auth\CanResetPassword;
use Illuminate\Support\Facades\Password;

class ResetOrganizationUserPassword
{
    public function handle(string $email, string $password, string $token): string
    {
        return Password::broker('organization_users')->reset(
            [
                'email' => OrganizationUser::normalizeEmail($email),
                'password' => $password,
                'token' => $token,
            ],
            function (CanResetPassword $user, string $newPassword): void {
                if (! $user instanceof OrganizationUser) {
                    return;
                }

                $user->forceFill([
                    'password' => $newPassword,
                    'auth_version' => $user->auth_version + 1,
                ])->save();

                event(new PasswordReset($user));
            },
        );
    }
}
