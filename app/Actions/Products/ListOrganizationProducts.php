<?php

namespace App\Actions\Products;

use App\Models\Currency;
use App\Models\Organization;
use App\Models\OrganizationEntitlement;
use App\Models\OrganizationProductPurchase;
use App\Models\Product;
use App\Models\ProductTranslation;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Number;
use LogicException;

class ListOrganizationProducts
{
    /**
     * @return array{
     *     ownedProducts: list<array{id: string, key: string, type: string, name: string, description: ?string, entitlement: array{status: string, source: string, startsAt: ?string, endsAt: ?string}}>,
     *     availableProducts: list<array{id: string, key: string, type: string, name: string, description: ?string, price: array{amount: string, currencyCode: string, currencySymbol: string, formatted: string}, purchaseUrl: string}>,
     *     purchaseHistory: Paginator
     * }
     */
    public function handle(Organization $organization, string $locale): array
    {
        $products = Product::query()
            ->where(
                fn (Builder $catalog): Builder => $catalog
                    ->availableFor($organization)
                    ->orWhere(fn (Builder $owned): Builder => $owned->ownedBy($organization)),
            )
            ->with([
                'translations' => fn (HasMany $translations): HasMany => $translations
                    ->whereHas(
                        'language',
                        fn (Builder $languages): Builder => $languages->active(),
                    )
                    ->with('language:id,code,main'),
                'organizationEntitlements' => fn (HasMany $entitlements): HasMany => $entitlements
                    ->whereBelongsTo($organization),
                'currency:id,code,symbol',
            ])
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get(['id', 'key', 'type', 'price_amount', 'currency_id', 'sort_order']);

        $ownedProducts = [];
        $availableProducts = [];

        foreach ($products as $product) {
            $productData = $this->productData($product, $locale);
            $entitlement = $product->organizationEntitlements->first();

            if ($entitlement instanceof OrganizationEntitlement) {
                $ownedProducts[] = [
                    ...$productData,
                    'entitlement' => [
                        'status' => $entitlement->status,
                        'source' => $entitlement->source,
                        'startsAt' => $entitlement->starts_at?->toISOString(),
                        'endsAt' => $entitlement->ends_at?->toISOString(),
                    ],
                ];

                continue;
            }

            $availableProducts[] = [
                ...$productData,
                'price' => $this->priceData($product, $locale) ?? throw new LogicException(
                    'Available products must have a configured price.',
                ),
                'purchaseUrl' => route('products.purchase', ['product' => $product]),
            ];
        }

        return [
            'ownedProducts' => $ownedProducts,
            'availableProducts' => $availableProducts,
            'purchaseHistory' => $this->purchaseHistory($organization, $locale),
        ];
    }

    /** @return Paginator<int, array{id: string, productName: string, purchasedAt: string, startsAt: string, endsAt: string, amount: string, currencyCode: string, formattedAmount: string}> */
    private function purchaseHistory(Organization $organization, string $locale): Paginator
    {
        return OrganizationProductPurchase::query()
            ->whereBelongsTo($organization)
            ->with(['product.translations' => fn (HasMany $translations): HasMany => $translations
                ->whereHas('language', fn (Builder $languages): Builder => $languages->active())
                ->with('language:id,code,main')])
            ->orderByDesc('purchased_at')
            ->orderByDesc('id')
            ->simplePaginate(10, pageName: 'historyPage')
            ->through(function (OrganizationProductPurchase $purchase) use ($locale): array {
                return [
                    'id' => (string) $purchase->getKey(),
                    'productName' => $this->preferredTranslation($purchase->product, $locale)?->name ?? $purchase->product->key,
                    'purchasedAt' => $purchase->purchased_at->toISOString(),
                    'startsAt' => $purchase->starts_at->toISOString(),
                    'endsAt' => $purchase->ends_at->toISOString(),
                    'amount' => $purchase->price_amount,
                    'currencyCode' => $purchase->currency_code,
                    'formattedAmount' => Number::currency((float) $purchase->price_amount, in: $purchase->currency_code, locale: $locale, precision: 2)
                        ?: "{$purchase->price_amount} {$purchase->currency_code}",
                ];
            });
    }

    /**
     * @return array{
     *     id: string,
     *     key: string,
     *     type: string,
     *     name: string,
     *     description: ?string
     * }
     */
    private function productData(Product $product, string $locale): array
    {
        $translation = $this->preferredTranslation($product, $locale);

        return [
            'id' => (string) $product->getKey(),
            'key' => $product->key,
            'type' => $product->type,
            'name' => $translation?->name ?? $product->key,
            'description' => $translation?->description,
        ];
    }

    /** @return ?array{amount: string, currencyCode: string, currencySymbol: string, formatted: string} */
    private function priceData(Product $product, string $locale): ?array
    {
        $currency = $product->currency;
        $amount = $product->price_amount;

        if (! $currency instanceof Currency || ! is_string($amount)) {
            return null;
        }

        $formatted = Number::currency(
            (float) $amount,
            in: $currency->code,
            locale: $locale,
            precision: 2,
        );

        return [
            'amount' => $amount,
            'currencyCode' => $currency->code,
            'currencySymbol' => $currency->symbol,
            'formatted' => is_string($formatted) ? $formatted : "{$amount} {$currency->code}",
        ];
    }

    private function preferredTranslation(Product $product, string $locale): ?ProductTranslation
    {
        return $product->translations
            ->first(fn (ProductTranslation $translation): bool => $translation->language->code === $locale)
            ?? $product->translations
                ->first(fn (ProductTranslation $translation): bool => $translation->language->main)
            ?? $product->translations->first();
    }
}
