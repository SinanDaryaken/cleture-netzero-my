<?php

namespace App\Http\Controllers\Products;

use App\Actions\Products\PurchaseProduct;
use App\Http\Controllers\Controller;
use App\Http\Requests\Products\PurchaseProductRequest;
use App\Models\OrganizationUser;
use App\Models\Product;

class ProductPurchaseController extends Controller
{
    public function store(
        PurchaseProductRequest $request,
        Product $product,
        PurchaseProduct $purchaseProduct,
    ): never {
        /** @var OrganizationUser $user */
        $user = $request->user();

        $purchaseProduct->handle($user->organization()->firstOrFail(), $product);

    }
}
