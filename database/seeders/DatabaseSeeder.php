<?php

namespace Database\Seeders;

use App\Models\Organization;
use App\Models\OrganizationUser;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $user = OrganizationUser::factory()->create([
            'name' => 'Yerel Test Kullanıcısı',
            'email' => 'test@cleture.test',
        ]);

        Organization::factory()->for($user)->create([
            'name' => 'Cleture Yerel Organizasyon',
            'tax_number' => '1234567890',
        ]);
    }
}
