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

class OrganizationalUnitManagementTest extends TestCase
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

    public function test_ready_tenant_units_are_rendered_in_a_stable_order(): void
    {
        [$user, $tenant] = $this->readyOrganizationUserWithTenant();
        $this->createTenantDatabase($tenant);
        $tenant->run(function (): void {
            OrganizationalUnit::query()->create([
                'name' => 'Operations',
                'parent_id' => null,
                'mark_as_company' => false,
                'mark_as_facility' => false,
                'sort_order' => 20,
            ]);
            OrganizationalUnit::query()->create([
                'name' => 'Head Office',
                'parent_id' => null,
                'mark_as_company' => true,
                'mark_as_facility' => false,
                'sort_order' => 10,
            ]);
        });

        $response = $this->actingAsOrganizationUser($user)
            ->get(route('tenant.organizational-units.index'));

        $response->assertInertia(fn (AssertableInertia $page) => $page
            ->component('tenant-organizational-units/Index', false)
            ->where('units.0.name', 'Head Office')
            ->where('units.0.markAsCompany', true)
            ->where('units.1.name', 'Operations'));
    }

    public function test_valid_payload_creates_an_organizational_unit(): void
    {
        [$user, $tenant] = $this->readyOrganizationUserWithTenant();
        $this->createTenantDatabase($tenant);

        $response = $this->actingAsOrganizationUser($user)
            ->post(route('tenant.organizational-units.store'), [
                'name' => '  İzmir Tesisi  ',
                'parent_id' => null,
                'mark_as_company' => false,
                'mark_as_facility' => true,
                'sort_order' => 10,
            ]);

        $response->assertRedirect(route('tenant.organizational-units.index'));
        $response->assertSessionHas('status', trans('ui.tenantOrganizationalUnits.created'));
        $tenant->run(function (): void {
            $unit = OrganizationalUnit::query()->sole();

            $this->assertSame('İzmir Tesisi', $unit->name);
            $this->assertTrue($unit->mark_as_facility);
            $this->assertFalse($unit->mark_as_company);
            $this->assertSame(10, $unit->sort_order);
        });
    }

    public function test_unit_type_can_be_assigned_independently_from_structural_role(): void
    {
        [$user, $tenant] = $this->readyOrganizationUserWithTenant();
        $this->createTenantDatabase($tenant);
        $type = $tenant->run(fn () => OrganizationUnitType::query()->create([
            'name' => 'Şube',
            'active' => true,
            'sort_order' => 0,
        ]));

        $response = $this->actingAsOrganizationUser($user)
            ->post(route('tenant.organizational-units.store'), [
                'name' => 'İzmir Şubesi',
                'parent_id' => null,
                'organization_unit_type_id' => $type->getKey(),
                'mark_as_company' => false,
                'mark_as_facility' => true,
                'sort_order' => 0,
            ]);

        $response->assertRedirect(route('tenant.organizational-units.index'));
        $tenant->run(function () use ($type): void {
            $unit = OrganizationalUnit::query()->sole();

            $this->assertSame($type->getKey(), $unit->organization_unit_type_id);
            $this->assertTrue($unit->mark_as_facility);
        });
    }

    public function test_inactive_unit_type_cannot_be_assigned_to_a_new_unit(): void
    {
        [$user, $tenant] = $this->readyOrganizationUserWithTenant();
        $this->createTenantDatabase($tenant);
        $type = $tenant->run(fn () => OrganizationUnitType::query()->create([
            'name' => 'Eski Tür',
            'active' => false,
            'sort_order' => 0,
        ]));

        $response = $this->actingAsOrganizationUser($user)
            ->post(route('tenant.organizational-units.store'), [
                'name' => 'Yeni Birim',
                'parent_id' => null,
                'organization_unit_type_id' => $type->getKey(),
                'mark_as_company' => false,
                'mark_as_facility' => false,
                'sort_order' => 0,
            ]);

        $response->assertSessionHasErrors('organization_unit_type_id');
        $tenant->run(fn () => $this->assertSame(0, OrganizationalUnit::query()->count()));
    }

    public function test_unit_cannot_be_marked_as_company_and_facility(): void
    {
        [$user, $tenant] = $this->readyOrganizationUserWithTenant();
        $this->createTenantDatabase($tenant);

        $response = $this->actingAsOrganizationUser($user)
            ->post(route('tenant.organizational-units.store'), [
                'name' => 'Invalid Unit',
                'parent_id' => null,
                'mark_as_company' => true,
                'mark_as_facility' => true,
                'sort_order' => 0,
            ]);

        $response->assertSessionHasErrors([
            'mark_as_company' => trans('ui.tenantOrganizationalUnits.classificationConflict'),
        ]);
        $tenant->run(fn () => $this->assertSame(0, OrganizationalUnit::query()->count()));
    }

    public function test_unit_cannot_be_moved_below_its_descendant(): void
    {
        [$user, $tenant] = $this->readyOrganizationUserWithTenant();
        $this->createTenantDatabase($tenant);
        [$parent, $child] = $tenant->run(function (): array {
            $parent = OrganizationalUnit::query()->create([
                'name' => 'Parent',
                'parent_id' => null,
                'mark_as_company' => false,
                'mark_as_facility' => false,
                'sort_order' => 0,
            ]);
            $child = OrganizationalUnit::query()->create([
                'name' => 'Child',
                'parent_id' => $parent->getKey(),
                'mark_as_company' => false,
                'mark_as_facility' => false,
                'sort_order' => 0,
            ]);

            return [$parent, $child];
        });

        $response = $this->actingAsOrganizationUser($user)
            ->patch(route('tenant.organizational-units.update', $parent->getKey()), [
                'name' => 'Parent',
                'parent_id' => $child->getKey(),
                'mark_as_company' => false,
                'mark_as_facility' => false,
                'sort_order' => 0,
            ]);

        $response->assertSessionHasErrors([
            'parent_id' => trans('ui.tenantOrganizationalUnits.parentCycle'),
        ]);
        $tenant->run(fn () => $this->assertNull($parent->fresh()->parent_id));
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
