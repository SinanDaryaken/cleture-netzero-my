<?php

namespace App\Actions\IdentityAccess;

use App\Models\OrganizationUser;
use Illuminate\Auth\Events\Verified;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class VerifyOrganizationUserEmail
{
    public function handle(OrganizationUser $user, string $plainToken): bool
    {
        return DB::transaction(function () use ($user, $plainToken): bool {
            $lockedUser = OrganizationUser::query()->lockForUpdate()->findOrFail($user->getKey());

            if ($lockedUser->hasVerifiedEmail()) {
                return true;
            }

            $token = DB::table('organization_user_email_verification_tokens')
                ->where('organization_user_id', $lockedUser->getKey())
                ->lockForUpdate()
                ->first();

            if (
                $token === null
                || now()->isAfter(Carbon::parse($token->expires_at))
                || ! Hash::check($plainToken, $token->token)
            ) {
                return false;
            }

            if (! $lockedUser->markEmailAsVerified()) {
                return false;
            }

            DB::table('organization_user_email_verification_tokens')
                ->where('organization_user_id', $lockedUser->getKey())
                ->delete();

            event(new Verified($lockedUser));

            return true;
        });
    }
}
