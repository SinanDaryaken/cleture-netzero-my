<?php

namespace App\Models;

use Database\Factories\OrganizationFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Stancl\Tenancy\Database\Concerns\CentralConnection;

class Organization extends Model
{
    /** @use HasFactory<OrganizationFactory> */
    use CentralConnection, HasFactory, HasUuids;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'tax_number',
    ];

    public function tenant(): HasOne
    {
        return $this->hasOne(Tenant::class);
    }

    public function organizationUser(): BelongsTo
    {
        return $this->belongsTo(OrganizationUser::class);
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'netzero_requested' => 'boolean',
        ];
    }
}
