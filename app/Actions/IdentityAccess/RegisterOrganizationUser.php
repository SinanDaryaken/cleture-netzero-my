<?php

namespace App\Actions\IdentityAccess;

use App\Models\OrganizationUser;
use Illuminate\Support\Facades\DB;

class RegisterOrganizationUser
{
    public function __construct(private readonly EnqueueOrganizationUserMail $mailTasks) {}

    /**
     * @param  array{name: string, email: string, password: string}  $attributes
     */
    public function handle(array $attributes): OrganizationUser
    {
        return DB::transaction(function () use ($attributes): OrganizationUser {
            $user = OrganizationUser::query()->create($attributes);

            $this->mailTasks->emailVerification($user);

            return $user;
        });
    }
}
