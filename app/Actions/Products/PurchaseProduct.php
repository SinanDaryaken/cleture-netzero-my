<?php

namespace App\Actions\Products;

use App\Models\Organization;
use App\Models\Product;
use Illuminate\Validation\ValidationException;

class PurchaseProduct
{
    public function handle(
        Organization $organization,
        Product $product,
    ): never {
        Product::query()
            ->availableFor($organization)
            ->whereKey($product->getKey())
            ->firstOrFail();

        throw ValidationException::withMessages([
            'product' => trans('ui.products.purchaseUnavailable'),
        ]);
    }
}
