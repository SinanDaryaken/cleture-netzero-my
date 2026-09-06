<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Stancl\Tenancy\Database\Concerns\CentralConnection;

class OrganizationProductPurchase extends Model
{
    use CentralConnection;

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
            'price_amount' => 'decimal:4',
            'purchased_at' => 'immutable_datetime',
            'starts_at' => 'immutable_datetime',
            'ends_at' => 'immutable_datetime',
        ];
    }
}
