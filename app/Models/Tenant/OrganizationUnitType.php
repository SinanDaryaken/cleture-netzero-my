<?php

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class OrganizationUnitType extends Model
{
    use HasUuids;

    protected $connection = 'tenant';

    /** @var list<string> */
    protected $fillable = [
        'name',
        'active',
        'sort_order',
    ];

    public function organizationalUnits(): HasMany
    {
        return $this->hasMany(OrganizationalUnit::class, 'organization_unit_type_id');
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'active' => 'boolean',
            'sort_order' => 'integer',
        ];
    }
}
