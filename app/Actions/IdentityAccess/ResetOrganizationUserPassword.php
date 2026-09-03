<?php

namespace App\Actions\IdentityAccess;

use App\Models\OrganizationUser;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Contracts\Auth\CanResetPassword;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Password;

class ResetOrganizationUserPassword
{
    public function __construct(private readonly EnqueueOrganizationUserMail $mailTasks) {}

    public function handle(string $email, string $password, string $token, string $locale): string
    {
        return Password::broker('organization_users')->reset(
            [
                'email' => OrganizationUser::normalizeEmail($email),
                'password' => $password,
                'token' => $token,
            ],
            function (CanResetPassword $user, string $newPassword) use ($locale): void {
                if (! $user instanceof OrganizationUser) {
                    return;
                }

                DB::transaction(function () use ($user, $newPassword, $locale): void {
                    $user->forceFill([
                        'password' => $newPassword,
                        'auth_version' => $user->auth_version + 1,
                    ])->save();

                    $this->mailTasks->passwordChanged($user, $locale);

                    event(new PasswordReset($user));
                });
            },
        );
    }
}
