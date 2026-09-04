<?php

namespace Tests\Feature\Tenant;

use App\Actions\IdentityAccess\AuthenticateOrganizationUser;
use App\Models\Organization;
use App\Models\OrganizationUser;
use App\Models\Tenant;
use App\Models\Tenant\OrganizationalUnit;
use App\Models\Tenant\OrganizationUnitType;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Inertia\Testing\AssertableInertia;
use Tests\Concerns\InteractsWithTenantDatabase;
use Tests\TestCase;

class OrganizationUnitTypeManagementTest extends TestCase
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

    public function test_ready_tenant_unit_types_are_rendered_in_a_stable_order(): void
    {
        [$user, $tenant] = $this->readyOrganizationUserWithTenant();
        $this->createTenantDatabase($tenant);
        $tenant->run(function (): void {
            OrganizationUnitType::query()->create([
                'name' => 'Şube',
                'active' => true,
                'sort_order' => 20,
            ]);
            $department = OrganizationUnitType::query()->create([
                'name' => 'Departman',
                'active' => false,
                'sort_order' => 10,
            ]);
            OrganizationalUnit::query()->create([
                'name' => 'Operasyon',
                'parent_id' => null,
                'organization_unit_type_id' => $department->getKey(),
                'mark_as_company' => false,
                'mark_as_facility' => false,
                'sort_order' => 0,
            ]);
        });

        $response = $this->actingAsOrganizationUser($user)
            ->get(route('tenant.organization-unit-types.index'));

        $response->assertInertia(fn (AssertableInertia $page) => $page
            ->component('tenant-organization-unit-types/Index', false)
            ->where('organizationUnitTypes.0.name', 'Departman')
            ->where('organizationUnitTypes.0.active', false)
            ->where('organizationUnitTypes.0.unitsCount', 1)
            ->where('organizationUnitTypes.1.name', 'Şube'));
    }

    public function test_valid_payload_creates_a_unit_type(): void
    {
        [$user, $tenant] = $this->readyOrganizationUserWithTenant();
        $this->createTenantDatabase($tenant);

        $response = $this->actingAsOrganizationUser($user)
            ->post(route('tenant.organization-unit-types.store'), [
                'name' => '  Departman  ',
                'active' => true,
                'sort_order' => 5,
            ]);

        $response->assertRedirect(route('tenant.organization-unit-types.index'));
        $response->assertSessionHas('status', trans('ui.tenantOrganizationUnitTypes.created'));
        $tenant->run(function (): void {
            $type = OrganizationUnitType::query()->sole();

            $this->assertSame('Departman', $type->name);
            $this->assertTrue($type->active);
            $this->assertSame(5, $type->sort_order);
        });
    }

    public function test_duplicate_name_is_rejected(): void
    {
        [$user, $tenant] = $this->readyOrganizationUserWithTenant();
        $this->createTenantDatabase($tenant);
        $tenant->run(fn () => OrganizationUnitType::query()->create([
            'name' => 'Departman',
            'active' => true,
            'sort_order' => 0,
        ]));

        $response = $this->actingAsOrganizationUser($user)
            ->post(route('tenant.organization-unit-types.store'), [
                'name' => 'Departman',
                'active' => true,
                'sort_order' => 1,
            ]);

        $response->assertSessionHasErrors('name');
        $tenant->run(fn () => $this->assertSame(1, OrganizationUnitType::query()->count()));
    }

    public function test_unit_type_can_be_updated(): void
    {
        [$user, $tenant] = $this->readyOrganizationUserWithTenant();
        $this->createTenantDatabase($tenant);
        $type = $tenant->run(fn () => OrganizationUnitType::query()->create([
            'name' => 'Departman',
            'active' => true,
            'sort_order' => 0,
        ]));

        $response = $this->actingAsOrganizationUser($user)
            ->patch(route('tenant.organization-unit-types.update', $type->getKey()), [
                'name' => 'Bölüm',
                'active' => false,
                'sort_order' => 8,
            ]);

        $response->assertRedirect(route('tenant.organization-unit-types.index'));
        $response->assertSessionHas('status', trans('ui.tenantOrganizationUnitTypes.updated'));
        $tenant->run(function () use ($type): void {
            $type->refresh();

            $this->assertSame('Bölüm', $type->name);
            $this->assertFalse($type->active);
            $this->assertSame(8, $type->sort_order);
        });
    }

    public function test_unused_unit_type_can_be_deleted(): void
    {
        [$user, $tenant] = $this->readyOrganizationUserWithTenant();
        $this->createTenantDatabase($tenant);
        $type = $tenant->run(fn () => OrganizationUnitType::query()->create([
            'name' => 'Bölge',
            'active' => true,
            'sort_order' => 0,
        ]));

        $response = $this->actingAsOrganizationUser($user)
            ->delete(route('tenant.organization-unit-types.destroy', $type->getKey()));

        $response->assertRedirect(route('tenant.organization-unit-types.index'));
        $response->assertSessionHas('status', trans('ui.tenantOrganizationUnitTypes.deleted'));
        $tenant->run(fn () => $this->assertSame(0, OrganizationUnitType::query()->count()));
    }

    public function test_assigned_unit_type_cannot_be_deleted(): void
    {
        [$user, $tenant] = $this->readyOrganizationUserWithTenant();
        $this->createTenantDatabase($tenant);
        $type = $tenant->run(function (): OrganizationUnitType {
            $type = OrganizationUnitType::query()->create([
                'name' => 'Şube',
                'active' => true,
                'sort_order' => 0,
            ]);
            OrganizationalUnit::query()->create([
                'name' => 'Ege Şubesi',
                'parent_id' => null,
                'organization_unit_type_id' => $type->getKey(),
                'mark_as_company' => false,
                'mark_as_facility' => false,
                'sort_order' => 0,
            ]);

            return $type;
        });

        $response = $this->actingAsOrganizationUser($user)
            ->delete(route('tenant.organization-unit-types.destroy', $type->getKey()));

        $response->assertRedirect();
        $response->assertSessionHas('error', trans('ui.tenantOrganizationUnitTypes.inUse'));
        $tenant->run(fn () => $this->assertSame(1, OrganizationUnitType::query()->count()));
    }

    public function test_unit_type_from_another_tenant_returns_404(): void
    {
        [$user, $tenant] = $this->readyOrganizationUserWithTenant();
        [, $otherTenant] = $this->readyOrganizationUserWithTenant();
        $this->createTenantDatabase($tenant);
        $this->createTenantDatabase($otherTenant);
        $otherType = $otherTenant->run(fn () => OrganizationUnitType::query()->create([
            'name' => 'Gizli Tür',
            'active' => true,
            'sort_order' => 0,
        ]));

        $response = $this->actingAsOrganizationUser($user)
            ->delete(route('tenant.organization-unit-types.destroy', $otherType->getKey()));

        $response->assertNotFound();
        $otherTenant->run(fn () => $this->assertSame(1, OrganizationUnitType::query()->count()));
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
