<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Stancl\Tenancy\Database\Concerns\CentralConnection;

class Product extends Model
{
    use CentralConnection, HasUuids;

    /** @return HasMany<ProductTranslation, $this> */
    public function translations(): HasMany
    {
        return $this->hasMany(ProductTranslation::class);
    }

    /** @return HasMany<OrganizationEntitlement, $this> */
    public function organizationEntitlements(): HasMany
    {
        return $this->hasMany(OrganizationEntitlement::class);
    }

    /** @return BelongsTo<Currency, $this> */
    public function currency(): BelongsTo
    {
        return $this->belongsTo(Currency::class);
    }

    /**
     * @param  Builder<Product>  $query
     * @return Builder<Product>
     */
    #[Scope]
    protected function availableFor(Builder $query, Organization $organization): Builder
    {
        return $query
            ->where('active', true)
            ->where('purchasable', true)
            ->whereNotNull('price_amount')
            ->whereNotNull('currency_id')
            ->whereHas(
                'currency',
                fn (Builder $currencies): Builder => $currencies->where('active', true),
            )
            ->whereDoesntHave(
                'organizationEntitlements',
                fn (Builder $entitlements): Builder => $entitlements->whereBelongsTo($organization),
            );
    }

    /**
     * @param  Builder<Product>  $query
     * @return Builder<Product>
     */
    #[Scope]
    protected function ownedBy(Builder $query, Organization $organization): Builder
    {
        return $query->whereHas(
            'organizationEntitlements',
            fn (Builder $entitlements): Builder => $entitlements->whereBelongsTo($organization),
        );
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'active' => 'boolean',
            'purchasable' => 'boolean',
            'price_amount' => 'decimal:4',
            'sort_order' => 'integer',
        ];
    }
}
