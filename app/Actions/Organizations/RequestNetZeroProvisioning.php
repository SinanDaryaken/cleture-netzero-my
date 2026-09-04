<?php

namespace App\Actions\Organizations;

use App\Models\Organization;
use App\Models\ProcessingTask;
use App\Models\Tenant;
use App\TenantProvisioningStatus;

class RequestNetZeroProvisioning
{
    public function handle(Organization $organization): Tenant
    {
        return $organization->getConnection()->transaction(function () use ($organization): Tenant {
            $lockedOrganization = Organization::query()
                ->whereKey($organization->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            $tenant = $lockedOrganization->tenant()->first();

            if ($tenant === null) {
                $tenant = $lockedOrganization->tenant()->create([
                    'provisioning_status' => TenantProvisioningStatus::Pending,
                    'active' => false,
                    'schema_version' => null,
                ]);
            }

            $lockedOrganization->forceFill(['netzero_requested' => true])->save();

            if ($tenant->provisioning_status === TenantProvisioningStatus::Pending) {
                ProcessingTask::query()->firstOrCreate(
                    ['dedupe_key' => "tenant:{$tenant->getKey()}:provision"],
                    [
                        'type' => ProcessingTask::TYPE_TENANT_PROVISION,
                        'payload_version' => ProcessingTask::TENANT_PROVISION_PAYLOAD_VERSION,
                        'tenant_id' => $tenant->getKey(),
                        'payload' => (object) [],
                        'status' => 'pending',
                        'attempts' => 0,
                        'available_at' => now(),
                    ],
                );
            }

            return $tenant;
        });
    }
}
