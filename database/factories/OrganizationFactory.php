<?php

namespace Database\Factories;

use App\Models\Organization;
use App\Models\OrganizationUser;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Organization>
 */
class OrganizationFactory extends Factory
{
    protected $model = Organization::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'organization_user_id' => OrganizationUser::factory(),
            'name' => fake()->company(),
            'tax_number' => fake()->unique()->numerify('##########'),
        ];
    }
}
