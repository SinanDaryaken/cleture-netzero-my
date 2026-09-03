<?php

namespace Tests\Feature\Organizations;

use App\Actions\IdentityAccess\AuthenticateOrganizationUser;
use App\Models\Organization;
use App\Models\OrganizationUser;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Inertia\Testing\AssertableInertia;
use Tests\TestCase;

class OrganizationManagementTest extends TestCase
{
    use DatabaseTransactions;

    public function test_unauthenticated_user_is_redirected_to_login(): void
    {
        $this->get(route('organization.show'))->assertRedirect(route('login.create'));
        $this->post(route('organization.store'))->assertRedirect(route('login.create'));
        $this->patch(route('organization.update'))->assertRedirect(route('login.create'));
    }

    public function test_verified_user_can_read_their_organization(): void
    {
        $user = OrganizationUser::factory()->create();
        Organization::factory()->for($user)->create([
            'name' => 'Cleture Teknoloji',
            'tax_number' => '1234567890',
        ]);

        $response = $this->actingAsOrganizationUser($user)->get(route('organization.show'));

        $response->assertInertia(fn (AssertableInertia $page) => $page
            ->component('organizations/Organization', false)
            ->where('organization.name', 'Cleture Teknoloji')
            ->where('organization.taxNumber', '1234567890'));
    }

    public function test_verified_user_can_create_one_organization(): void
    {
        $user = OrganizationUser::factory()->create();

        $response = $this->actingAsOrganizationUser($user)->post(route('organization.store'), [
            'name' => '  Cleture Teknoloji  ',
            'tax_number' => '  1234567890  ',
        ]);

        $response->assertRedirect(route('organization.show'));
        $response->assertSessionHas('status', trans('ui.organization.created'));
        $this->assertDatabaseHas('organizations', [
            'organization_user_id' => $user->getKey(),
            'name' => 'Cleture Teknoloji',
            'tax_number' => '1234567890',
        ]);
    }

    public function test_user_cannot_create_a_second_organization(): void
    {
        $user = OrganizationUser::factory()->create();
        Organization::factory()->for($user)->create();

        $response = $this->actingAsOrganizationUser($user)->post(route('organization.store'), [
            'name' => 'İkinci Organizasyon',
            'tax_number' => '9876543210',
        ]);

        $response->assertForbidden();
        $this->assertSame(1, $user->organization()->count());
    }

    public function test_organization_requires_a_name_and_tax_number(): void
    {
        $user = OrganizationUser::factory()->create();

        $response = $this
            ->actingAsOrganizationUser($user)
            ->from(route('organization.show'))
            ->post(route('organization.store'));

        $response->assertRedirect(route('organization.show'));
        $response->assertSessionHasErrors(['name', 'tax_number']);
        $this->assertDatabaseMissing('organizations', [
            'organization_user_id' => $user->getKey(),
        ]);
    }

    public function test_tax_number_must_be_unique_across_organizations(): void
    {
        Organization::factory()->create(['tax_number' => '1234567890']);
        $user = OrganizationUser::factory()->create();

        $response = $this
            ->actingAsOrganizationUser($user)
            ->from(route('organization.show'))
            ->post(route('organization.store'), [
                'name' => 'Yeni Organizasyon',
                'tax_number' => '1234567890',
            ]);

        $response->assertRedirect(route('organization.show'));
        $response->assertSessionHasErrors('tax_number');
        $this->assertDatabaseMissing('organizations', [
            'organization_user_id' => $user->getKey(),
        ]);
    }

    public function test_verified_user_can_update_only_their_organization(): void
    {
        $user = OrganizationUser::factory()->create();
        $organization = Organization::factory()->for($user)->create();
        $otherOrganization = Organization::factory()->create([
            'name' => 'Başka Organizasyon',
            'tax_number' => '1111111111',
        ]);

        $response = $this->actingAsOrganizationUser($user)->patch(route('organization.update'), [
            'name' => 'Güncel Organizasyon',
            'tax_number' => '2222222222',
        ]);

        $response->assertRedirect(route('organization.show'));
        $response->assertSessionHas('status', trans('ui.organization.updated'));
        $this->assertSame('Güncel Organizasyon', $organization->fresh()->name);
        $this->assertSame('2222222222', $organization->fresh()->tax_number);
        $this->assertSame('Başka Organizasyon', $otherOrganization->fresh()->name);
        $this->assertSame('1111111111', $otherOrganization->fresh()->tax_number);
    }

    public function test_user_without_an_organization_cannot_update_one(): void
    {
        $user = OrganizationUser::factory()->create();

        $response = $this->actingAsOrganizationUser($user)->patch(
            route('organization.update'),
            ['name' => 'Organizasyon', 'tax_number' => '1234567890'],
        );

        $response->assertForbidden();
    }

    public function test_delete_organization_returns_405(): void
    {
        $user = OrganizationUser::factory()->create();
        Organization::factory()->for($user)->create();

        $this->actingAsOrganizationUser($user)
            ->delete('/organization')
            ->assertStatus(405);
    }

    private function actingAsOrganizationUser(OrganizationUser $user): static
    {
        $this->actingAs($user)->withSession([
            AuthenticateOrganizationUser::SESSION_AUTH_VERSION => $user->auth_version,
        ]);

        return $this;
    }
}
