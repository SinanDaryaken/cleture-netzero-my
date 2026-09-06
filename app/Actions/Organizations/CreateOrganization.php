<?php

namespace App\Actions\Organizations;

use App\Models\Organization;
use App\Models\OrganizationUser;

class CreateOrganization
{
    /**
     * @param  array{name: string, tax_number: string}  $attributes
     */
    public function handle(OrganizationUser $user, array $attributes): Organization
    {
        return $user->organization()->create($attributes);
    }
}
