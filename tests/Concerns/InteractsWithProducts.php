<?php

namespace Tests\Concerns;

use App\Models\Language;
use App\Models\Organization;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use InvalidArgumentException;

trait InteractsWithProducts
{
    /**
     * @param  array<string, array{0: string, 1: ?string}>  $translations
     */
    protected function createProduct(
        string $key,
        string $type = 'module',
        int $sortOrder = 0,
        bool $active = true,
        bool $purchasable = true,
        ?string $priceAmount = '100.00',
        ?string $currencyCode = 'TRY',
        string $currencySymbol = '₺',
        bool $currencyActive = true,
        bool $currencyDeleted = false,
        array $translations = [],
    ): string {
        if (($priceAmount === null) !== ($currencyCode === null)) {
            throw new InvalidArgumentException('Product price and currency must be configured together.');
        }

        $productId = (string) Str::uuid7();
        $currencyId = $currencyCode === null
            ? null
            : $this->ensureCurrency(
                $currencyCode,
                $currencySymbol,
                $currencyActive,
                $currencyDeleted,
            );

        DB::table('products')->insert([
            'id' => $productId,
            'key' => $key,
            'type' => $type,
            'active' => $active,
            'purchasable' => $purchasable,
            'price_amount' => $priceAmount,
            'currency_id' => $currencyId,
            'sort_order' => $sortOrder,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        foreach ($translations as $languageCode => [$name, $description]) {
            DB::table('product_translations')->insert([
                'id' => (string) Str::uuid7(),
                'product_id' => $productId,
                'language_id' => Language::query()->where('code', $languageCode)->valueOrFail('id'),
                'name' => $name,
                'description' => $description,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        return $productId;
    }

    protected function ensureCurrency(
        string $code = 'TRY',
        string $symbol = '₺',
        bool $active = true,
        bool $deleted = false,
    ): string {
        $existingCurrencyId = DB::table('currencies')->where('code', $code)->value('id');

        if (is_string($existingCurrencyId)) {
            return $existingCurrencyId;
        }

        $currencyId = (string) Str::uuid7();

        DB::table('currencies')->insert([
            'id' => $currencyId,
            'code' => $code,
            'name' => $code,
            'symbol' => $symbol,
            'active' => $active,
            'created_at' => now(),
            'updated_at' => now(),
            'deleted_at' => $deleted ? now() : null,
        ]);

        return $currencyId;
    }

    protected function createEntitlement(
        Organization $organization,
        string $productId,
        string $status = 'active',
        string $source = 'manual',
    ): void {
        DB::table('organization_entitlements')->insert([
            'id' => (string) Str::uuid7(),
            'organization_id' => $organization->getKey(),
            'product_id' => $productId,
            'status' => $status,
            'source' => $source,
            'starts_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
