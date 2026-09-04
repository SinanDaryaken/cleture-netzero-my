<?php

namespace Database\Factories;

use App\Models\Organization;
use App\Models\Tenant;
use App\TenantProvisioningStatus;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Tenant>
 */
class TenantFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'organization_id' => Organization::factory(),
            'provisioning_status' => TenantProvisioningStatus::Pending,
            'active' => false,
            'schema_version' => null,
        ];
    }

    public function ready(): static
    {
        return $this->state(fn (): array => [
            'provisioning_status' => TenantProvisioningStatus::Ready,
            'active' => true,
            'schema_version' => 'test-schema-version',
        ]);
    }
}
