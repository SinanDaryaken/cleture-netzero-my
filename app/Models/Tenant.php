<?php

namespace App\Models;

use App\TenantProvisioningStatus;
use Database\Factories\TenantFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Stancl\Tenancy\Contracts\TenantWithDatabase;
use Stancl\Tenancy\Database\Concerns\CentralConnection;
use Stancl\Tenancy\Database\Concerns\HasDatabase;
use Stancl\Tenancy\Database\Concerns\HasInternalKeys;
use Stancl\Tenancy\Database\Concerns\TenantRun;

class Tenant extends Model implements TenantWithDatabase
{
    /** @use HasFactory<TenantFactory> */
    use CentralConnection, HasDatabase, HasFactory, HasInternalKeys, HasUuids, TenantRun;

    /** @var list<string> */
    protected $fillable = [
        'provisioning_status',
        'active',
        'schema_version',
    ];

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function getTenantKeyName(): string
    {
        return $this->getKeyName();
    }

    public function getTenantKey(): mixed
    {
        return $this->getKey();
    }

    public function isAvailable(): bool
    {
        return $this->provisioning_status === TenantProvisioningStatus::Ready
            && $this->active;
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'provisioning_status' => TenantProvisioningStatus::class,
            'active' => 'boolean',
        ];
    }
}
