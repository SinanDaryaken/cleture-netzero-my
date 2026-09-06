<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Stancl\Tenancy\Database\Concerns\CentralConnection;

class OrganizationEntitlement extends Model
{
    use CentralConnection, HasUuids;

    public const string STATUS_ACTIVE = 'active';

    public const string SOURCE_PURCHASE = 'purchase';

    /** @var list<string> */
    protected $fillable = [
        'product_id',
        'status',
        'source',
        'source_reference',
        'starts_at',
        'ends_at',
    ];

    /** @return BelongsTo<Organization, $this> */
    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    /** @return BelongsTo<Product, $this> */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'starts_at' => 'immutable_datetime',
            'ends_at' => 'immutable_datetime',
        ];
    }
}
