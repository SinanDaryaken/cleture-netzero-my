<?php

namespace Tests\Feature\Tenant;

use App\Actions\IdentityAccess\AuthenticateOrganizationUser;
use App\Models\Organization;
use App\Models\OrganizationUser;
use App\Models\Tenant;
use App\Models\Tenant\User;
use App\TenantProvisioningStatus;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Hash;
use Inertia\Testing\AssertableInertia;
use Tests\Concerns\InteractsWithTenantDatabase;
use Tests\TestCase;

class TenantUserManagementTest extends TestCase
{
    use DatabaseTransactions;
    use InteractsWithTenantDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->configureTenantDatabaseTesting();
    }

    protected function tearDown(): void
    {
        $this->cleanUpTenantDatabases();

        parent::tearDown();
    }

    public function test_unauthenticated_request_redirects_to_login(): void
    {
        $this->get(route('tenant.users.index'))
            ->assertRedirect(route('login.create'));
    }

    public function test_request_is_forbidden_when_tenant_is_not_available(): void
    {
        $user = OrganizationUser::factory()->create();
        $organization = Organization::factory()->for($user)->create();
        Tenant::factory()->for($organization)->create();

        $this->actingAsOrganizationUser($user)
            ->get(route('tenant.users.index'))
            ->assertForbidden();
    }

    public function test_request_is_forbidden_when_ready_tenant_is_inactive(): void
    {
        $user = OrganizationUser::factory()->create();
        $organization = Organization::factory()->for($user)->create();
        Tenant::factory()->for($organization)->create([
            'provisioning_status' => TenantProvisioningStatus::Ready,
            'active' => false,
        ]);

        $this->actingAsOrganizationUser($user)
            ->get(route('tenant.users.index'))
            ->assertForbidden();
    }

    public function test_ready_tenant_users_are_rendered_from_the_tenant_database(): void
    {
        [$user, $tenant] = $this->readyOrganizationUserWithTenant();
        $this->createTenantDatabase($tenant);
        $tenant->run(fn (): User => User::query()->create([
            'name' => 'Ayşe Demir',
            'email' => 'ayse@example.com',
            'password' => 'secure-password',
            'active' => true,
        ]));

        $response = $this->actingAsOrganizationUser($user)
            ->get(route('tenant.users.index'));

        $response->assertInertia(fn (AssertableInertia $page) => $page
            ->component('tenant-users/Index', false)
            ->where('users.data.0.name', 'Ayşe Demir')
            ->where('users.data.0.email', 'ayse@example.com')
            ->where('users.data.0.active', true));
    }

    public function test_valid_payload_creates_user_with_a_hashed_password(): void
    {
        [$user, $tenant] = $this->readyOrganizationUserWithTenant();
        $this->createTenantDatabase($tenant);

        $response = $this->actingAsOrganizationUser($user)
            ->post(route('tenant.users.store'), [
                'name' => '  Ayşe Demir  ',
                'email' => '  AYSE@EXAMPLE.COM ',
                'password' => 'correct-horse-battery-staple',
                'password_confirmation' => 'correct-horse-battery-staple',
                'active' => true,
            ]);

        $response->assertRedirect(route('tenant.users.index'));
        $response->assertSessionHas('status', trans('ui.tenantUsers.created'));
        $tenant->run(function (): void {
            $tenantUser = User::query()->sole();

            $this->assertSame('Ayşe Demir', $tenantUser->name);
            $this->assertSame('ayse@example.com', $tenantUser->email);
            $this->assertTrue($tenantUser->active);
            $this->assertTrue(Hash::check('correct-horse-battery-staple', $tenantUser->password));
        });
    }

    public function test_duplicate_email_is_rejected_without_creating_another_user(): void
    {
        [$user, $tenant] = $this->readyOrganizationUserWithTenant();
        $this->createTenantDatabase($tenant);
        $tenant->run(fn (): User => User::query()->create([
            'name' => 'Existing User',
            'email' => 'user@example.com',
            'password' => 'secure-password',
            'active' => true,
        ]));

        $response = $this->actingAsOrganizationUser($user)
            ->post(route('tenant.users.store'), [
                'name' => 'Other User',
                'email' => 'USER@example.com',
                'password' => 'correct-horse-battery-staple',
                'password_confirmation' => 'correct-horse-battery-staple',
                'active' => true,
            ]);

        $response->assertSessionHasErrors('email');
        $tenant->run(fn () => $this->assertSame(1, User::query()->count()));
    }

    public function test_existing_tenant_user_can_be_deleted(): void
    {
        [$user, $tenant] = $this->readyOrganizationUserWithTenant();
        $this->createTenantDatabase($tenant);
        $tenantUser = $tenant->run(fn (): User => User::query()->create([
            'name' => 'Ayşe Demir',
            'email' => 'ayse@example.com',
            'password' => 'secure-password',
            'active' => true,
        ]));

        $response = $this->actingAsOrganizationUser($user)
            ->delete(route('tenant.users.destroy', $tenantUser->getKey()));

        $response->assertRedirect(route('tenant.users.index'));
        $response->assertSessionHas('status', trans('ui.tenantUsers.deleted'));
        $tenant->run(fn () => $this->assertModelMissing($tenantUser));
    }

    public function test_user_from_another_tenant_cannot_be_deleted(): void
    {
        [$user, $tenant] = $this->readyOrganizationUserWithTenant();
        [, $otherTenant] = $this->readyOrganizationUserWithTenant();
        $this->createTenantDatabase($tenant);
        $this->createTenantDatabase($otherTenant);
        $otherUser = $otherTenant->run(fn (): User => User::query()->create([
            'name' => 'Other Tenant User',
            'email' => 'other@example.com',
            'password' => 'secure-password',
            'active' => true,
        ]));

        $this->actingAsOrganizationUser($user)
            ->delete(route('tenant.users.destroy', $otherUser->getKey()))
            ->assertNotFound();

        $otherTenant->run(fn () => $this->assertModelExists($otherUser));
    }

    public function test_user_from_another_tenant_is_not_found(): void
    {
        [$user, $tenant] = $this->readyOrganizationUserWithTenant();
        [, $otherTenant] = $this->readyOrganizationUserWithTenant();
        $this->createTenantDatabase($tenant);
        $this->createTenantDatabase($otherTenant);
        $otherUser = $otherTenant->run(fn (): User => User::query()->create([
            'name' => 'Other Tenant User',
            'email' => 'other@example.com',
            'password' => 'secure-password',
            'active' => true,
        ]));

        $this->actingAsOrganizationUser($user)
            ->patch(route('tenant.users.update', $otherUser->getKey()), [
                'name' => 'Changed Name',
                'email' => 'other@example.com',
                'password' => '',
                'password_confirmation' => '',
                'active' => false,
            ])
            ->assertNotFound();

        $otherTenant->run(function () use ($otherUser): void {
            $this->assertSame('Other Tenant User', $otherUser->fresh()->name);
            $this->assertTrue($otherUser->fresh()->active);
        });
    }

    /** @return array{OrganizationUser, Tenant} */
    private function readyOrganizationUserWithTenant(): array
    {
        $user = OrganizationUser::factory()->create();
        $organization = Organization::factory()->for($user)->create([
            'netzero_requested' => true,
        ]);
        $tenant = Tenant::factory()->ready()->for($organization)->create();

        return [$user, $tenant];
    }

    private function actingAsOrganizationUser(OrganizationUser $user): static
    {
        $this->actingAs($user)->withSession([
            AuthenticateOrganizationUser::SESSION_AUTH_VERSION => $user->auth_version,
        ]);

        return $this;
    }
}
