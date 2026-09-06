<?php

namespace Tests\Feature\Products;

use App\Actions\IdentityAccess\AuthenticateOrganizationUser;
use App\Localization\LocaleManager;
use App\Models\Organization;
use App\Models\OrganizationUser;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Inertia\Testing\AssertableInertia;
use Tests\Concerns\InteractsWithLanguages;
use Tests\Concerns\InteractsWithProducts;
use Tests\TestCase;

class ProductCatalogTest extends TestCase
{
    use DatabaseTransactions, InteractsWithLanguages, InteractsWithProducts;

    public function test_unauthenticated_user_is_redirected_to_login(): void
    {
        $this->get(route('products.index'))->assertRedirect(route('login.create'));
    }

    public function test_unverified_user_is_redirected_to_verification_notice(): void
    {
        $user = OrganizationUser::factory()->unverified()->create();

        $this->actingAsOrganizationUser($user)
            ->get(route('products.index'))
            ->assertRedirect(route('verification.notice'));
    }

    public function test_user_without_an_organization_is_redirected_to_organization_setup(): void
    {
        $user = OrganizationUser::factory()->create();

        $this->actingAsOrganizationUser($user)
            ->get(route('products.index'))
            ->assertRedirect(route('organization.show'))
            ->assertSessionHas('error', trans('ui.products.organizationRequired'));
    }

    public function test_catalog_separates_owned_and_available_products_in_display_order(): void
    {
        $this->ensureLanguage('tr', 'Türkçe', active: true, main: true);
        $this->ensureLanguage('en', 'English', active: true);
        $user = OrganizationUser::factory()->create();
        $organization = Organization::factory()->for($user)->create(['name' => 'Cleture Teknoloji']);

        $defaultProductId = $this->createProduct(
            key: 'netzero-carbon',
            sortOrder: 1,
            purchasable: false,
            translations: [
                'tr' => ['NetZero Karbon', 'Karbon emisyonlarınızı yönetin.'],
            ],
        );
        $this->createEntitlement($organization, $defaultProductId, source: 'default');
        $suspendedProductId = $this->createProduct(
            key: 'suspended-addon',
            type: 'addon',
            sortOrder: 2,
        );
        $this->createEntitlement(
            $organization,
            $suspendedProductId,
            status: 'suspended',
            source: 'purchase',
        );
        $applicationId = $this->createProduct(
            key: 'supplier-portal',
            type: 'application',
            sortOrder: 10,
            translations: [
                'tr' => ['Tedarikçi Portalı', 'Tedarikçi verilerini yönetin.'],
            ],
        );
        $this->createProduct(
            key: 'reporting-addon',
            type: 'addon',
            sortOrder: 20,
            translations: [
                'en' => ['Reporting Add-on', null],
            ],
        );
        $this->createProduct('inactive-product', active: false);
        $this->createProduct('not-purchasable', purchasable: false);
        $this->createProduct('unpriced-product', priceAmount: null, currencyCode: null);
        $this->createProduct(
            'inactive-currency-product',
            currencyCode: 'ZZZ',
            currencySymbol: '¤',
            currencyActive: false,
        );

        $response = $this
            ->actingAsOrganizationUser($user)
            ->withSession([LocaleManager::SESSION_KEY => 'tr'])
            ->get(route('products.index'));

        $response->assertInertia(fn (AssertableInertia $page) => $page
            ->component('products/Index', false)
            ->where('organization.name', 'Cleture Teknoloji')
            ->has('ownedProducts', 2)
            ->where('ownedProducts.0.id', $defaultProductId)
            ->where('ownedProducts.0.name', 'NetZero Karbon')
            ->where('ownedProducts.0.entitlement.status', 'active')
            ->where('ownedProducts.0.entitlement.source', 'default')
            ->where('ownedProducts.1.key', 'suspended-addon')
            ->where('ownedProducts.1.entitlement.status', 'suspended')
            ->where('ownedProducts.1.entitlement.source', 'purchase')
            ->has('availableProducts', 2)
            ->where('availableProducts.0.id', $applicationId)
            ->where('availableProducts.0.key', 'supplier-portal')
            ->where('availableProducts.0.type', 'application')
            ->where('availableProducts.0.name', 'Tedarikçi Portalı')
            ->where('availableProducts.0.description', 'Tedarikçi verilerini yönetin.')
            ->where('availableProducts.0.price.amount', '100.0000')
            ->where('availableProducts.0.price.currencyCode', 'TRY')
            ->where('availableProducts.0.price.currencySymbol', '₺')
            ->where(
                'availableProducts.0.purchaseUrl',
                route('products.purchase', ['product' => $applicationId]),
            )
            ->where('availableProducts.1.key', 'reporting-addon')
            ->where('availableProducts.1.name', 'Reporting Add-on')
            ->where('availableProducts.1.description', null));
    }

    public function test_products_collection_has_no_direct_write_endpoint(): void
    {
        $user = OrganizationUser::factory()->create();
        Organization::factory()->for($user)->create();

        $this->actingAsOrganizationUser($user)
            ->post('/products')
            ->assertStatus(405);
    }

    public function test_owned_products_show_recorded_dates_without_inventing_a_legacy_end_date(): void
    {
        $user = OrganizationUser::factory()->create();
        $organization = Organization::factory()->for($user)->create();
        $productId = $this->createProduct('annual-product', sortOrder: 1);
        $legacyProductId = $this->createProduct('legacy-product', sortOrder: 2);
        $this->createEntitlement($organization, $productId);
        $this->createEntitlement($organization, $legacyProductId, source: 'default');
        DB::table('organization_entitlements')->where('product_id', $productId)->update([
            'starts_at' => '2026-09-05 12:00:00+00',
            'ends_at' => '2027-09-05 12:00:00+00',
        ]);

        $this->actingAsOrganizationUser($user)->get(route('products.index'))
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->where('ownedProducts.0.entitlement.startsAt', '2026-09-05T12:00:00.000000Z')
                ->where('ownedProducts.0.entitlement.endsAt', '2027-09-05T12:00:00.000000Z')
                ->where('ownedProducts.1.entitlement.endsAt', null)
                ->has('purchaseHistory.data', 0));
    }

    public function test_purchase_history_is_paginated_scoped_to_the_user_and_uses_price_snapshots(): void
    {
        $user = OrganizationUser::factory()->create();
        $organization = Organization::factory()->for($user)->create();
        $otherOrganization = Organization::factory()->create();
        $productId = $this->createProduct('retired-product', active: false, priceAmount: '999.00');
        $currencyId = $this->ensureCurrency('EUR', '€', active: false, deleted: true);
        for ($day = 1; $day <= 12; $day++) {
            $date = sprintf('2026-01-%02d 12:00:00+00', $day);
            DB::table('organization_product_purchases')->insert([
                'id' => (string) Str::uuid7(),
                'organization_id' => $organization->getKey(),
                'product_id' => $productId,
                'idempotency_key' => (string) Str::uuid7(),
                'price_amount' => '12.3400',
                'currency_id' => $currencyId,
                'currency_code' => 'EUR',
                'purchased_at' => $date,
                'starts_at' => $date,
                'ends_at' => sprintf('2027-01-%02d 12:00:00+00', $day),
            ]);
        }
        DB::table('organization_product_purchases')->insert([
            'id' => (string) Str::uuid7(),
            'organization_id' => $otherOrganization->getKey(),
            'product_id' => $productId,
            'idempotency_key' => (string) Str::uuid7(),
            'price_amount' => '500.0000',
            'currency_id' => $currencyId,
            'currency_code' => 'EUR',
            'purchased_at' => '2026-02-01 12:00:00+00',
            'starts_at' => '2026-02-01 12:00:00+00',
            'ends_at' => '2027-02-01 12:00:00+00',
        ]);

        $this->actingAsOrganizationUser($user)
            ->get(route('products.index', ['organization_id' => $otherOrganization->getKey()]))
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->has('ownedProducts', 0)
                ->has('purchaseHistory.data', 10)
                ->where('purchaseHistory.data.0.productName', 'retired-product')
                ->where('purchaseHistory.data.0.amount', '12.3400')
                ->where('purchaseHistory.data.0.currencyCode', 'EUR')
                ->where('purchaseHistory.data.0.purchasedAt', '2026-01-12T12:00:00.000000Z')
                ->where('purchaseHistory.data.0.startsAt', '2026-01-12T12:00:00.000000Z')
                ->where('purchaseHistory.data.0.endsAt', '2027-01-12T12:00:00.000000Z')
                ->where('purchaseHistory.prev_page_url', null));

        $this->get(route('products.index', ['historyPage' => 2]))
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->has('purchaseHistory.data', 2)
                ->where('purchaseHistory.data.0.purchasedAt', '2026-01-02T12:00:00.000000Z')
                ->where('purchaseHistory.next_page_url', null));
    }

    private function actingAsOrganizationUser(OrganizationUser $user): static
    {
        $this->actingAs($user)->withSession([
            AuthenticateOrganizationUser::SESSION_AUTH_VERSION => $user->auth_version,
        ]);

        return $this;
    }
}
