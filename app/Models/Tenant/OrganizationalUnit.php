<?php

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class OrganizationalUnit extends Model
{
    use HasUuids;

    protected $connection = 'tenant';

    /** @var list<string> */
    protected $fillable = [
        'parent_id',
        'organization_unit_type_id',
        'name',
        'mark_as_company',
        'mark_as_facility',
        'sort_order',
    ];

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id');
    }

    public function organizationUnitType(): BelongsTo
    {
        return $this->belongsTo(OrganizationUnitType::class, 'organization_unit_type_id');
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'mark_as_company' => 'boolean',
            'mark_as_facility' => 'boolean',
            'sort_order' => 'integer',
        ];
    }
}
