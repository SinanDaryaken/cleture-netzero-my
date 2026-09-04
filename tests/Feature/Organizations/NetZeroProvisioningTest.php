<?php

namespace Tests\Feature\Organizations;

use App\Actions\IdentityAccess\AuthenticateOrganizationUser;
use App\Actions\Organizations\RequestNetZeroProvisioning;
use App\Models\Organization;
use App\Models\OrganizationUser;
use App\Models\ProcessingTask;
use App\Models\Tenant;
use App\TenantProvisioningStatus;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Inertia\Testing\AssertableInertia;
use RuntimeException;
use Tests\TestCase;

class NetZeroProvisioningTest extends TestCase
{
    use DatabaseTransactions;

    public function test_unauthenticated_user_is_redirected_to_login(): void
    {
        $this->post(route('organization.netzero-provisioning.store'))
            ->assertRedirect(route('login.create'));
    }

    public function test_user_without_an_organization_cannot_request_netzero(): void
    {
        $user = OrganizationUser::factory()->create();

        $this->actingAsOrganizationUser($user)
            ->post(route('organization.netzero-provisioning.store'))
            ->assertForbidden();

        $this->assertDatabaseCount('tenants', 0);
        $this->assertDatabaseMissing('processing_tasks', [
            'type' => ProcessingTask::TYPE_TENANT_PROVISION,
        ]);
    }

    public function test_verified_user_can_request_netzero_for_their_organization(): void
    {
        $user = OrganizationUser::factory()->create();
        $organization = Organization::factory()->for($user)->create([
            'netzero_requested' => false,
        ]);

        $response = $this->actingAsOrganizationUser($user)
            ->post(route('organization.netzero-provisioning.store'));

        $tenant = Tenant::query()->whereBelongsTo($organization)->sole();
        $task = ProcessingTask::query()
            ->where('type', ProcessingTask::TYPE_TENANT_PROVISION)
            ->sole();

        $response->assertRedirect(route('organization.show'));
        $response->assertSessionHas('status', trans('ui.organization.netZeroRequested'));
        $this->assertTrue($organization->fresh()->netzero_requested);
        $this->assertSame(TenantProvisioningStatus::Pending, $tenant->provisioning_status);
        $this->assertFalse($tenant->active);
        $this->assertNull($tenant->schema_version);
        $this->assertSame($tenant->getKey(), $task->tenant_id);
        $this->assertSame(ProcessingTask::TENANT_PROVISION_PAYLOAD_VERSION, $task->payload_version);
        $this->assertSame([], $task->payload);
        $this->assertSame("tenant:{$tenant->getKey()}:provision", $task->dedupe_key);
        $this->assertSame('pending', $task->status);
    }

    public function test_repeated_request_does_not_duplicate_tenant_or_task(): void
    {
        $user = OrganizationUser::factory()->create();
        Organization::factory()->for($user)->create([
            'netzero_requested' => false,
        ]);

        $this->actingAsOrganizationUser($user)
            ->post(route('organization.netzero-provisioning.store'));
        $this->actingAsOrganizationUser($user)
            ->post(route('organization.netzero-provisioning.store'));

        $this->assertDatabaseCount('tenants', 1);
        $this->assertSame(
            1,
            ProcessingTask::query()
                ->where('type', ProcessingTask::TYPE_TENANT_PROVISION)
                ->count(),
        );
    }

    public function test_tenant_and_flag_are_rolled_back_when_task_creation_fails(): void
    {
        $organization = Organization::factory()->create([
            'netzero_requested' => false,
        ]);
        ProcessingTask::creating(static function (): never {
            throw new RuntimeException('Task storage failed.');
        });

        try {
            app(RequestNetZeroProvisioning::class)->handle($organization);
            $this->fail('The simulated task failure was not raised.');
        } catch (RuntimeException $exception) {
            $this->assertSame('Task storage failed.', $exception->getMessage());
        } finally {
            ProcessingTask::flushEventListeners();
        }

        $this->assertFalse($organization->fresh()->netzero_requested);
        $this->assertDatabaseCount('tenants', 0);
    }

    public function test_ready_tenant_is_exposed_as_available_without_creating_another_task(): void
    {
        $user = OrganizationUser::factory()->create();
        $organization = Organization::factory()->for($user)->create([
            'netzero_requested' => true,
        ]);
        Tenant::factory()->ready()->for($organization)->create();

        $response = $this->actingAsOrganizationUser($user)
            ->get(route('organization.show'));

        $response->assertInertia(fn (AssertableInertia $page) => $page
            ->component('organizations/Organization', false)
            ->where('organization.netZeroRequested', true)
            ->where('organization.tenant.provisioningStatus', TenantProvisioningStatus::Ready->value)
            ->where('organization.tenant.active', true)
            ->where('organization.tenant.available', true));
        $this->assertDatabaseMissing('processing_tasks', [
            'type' => ProcessingTask::TYPE_TENANT_PROVISION,
        ]);
    }

    private function actingAsOrganizationUser(OrganizationUser $user): static
    {
        $this->actingAs($user)->withSession([
            AuthenticateOrganizationUser::SESSION_AUTH_VERSION => $user->auth_version,
        ]);

        return $this;
    }
}
