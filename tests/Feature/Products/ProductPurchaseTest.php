<?php

namespace Tests\Feature\Products;

use App\Actions\IdentityAccess\AuthenticateOrganizationUser;
use App\Models\Organization;
use App\Models\OrganizationEntitlement;
use App\Models\OrganizationUser;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\Concerns\InteractsWithProducts;
use Tests\TestCase;

class ProductPurchaseTest extends TestCase
{
    use DatabaseTransactions, InteractsWithProducts;

    public function test_unauthenticated_user_is_redirected_to_login(): void
    {
        $productId = $this->createProduct('supplier-portal');

        $this->post(route('products.purchase', ['product' => $productId]))
            ->assertRedirect(route('login.create'));

        $this->assertDatabaseCount('organization_entitlements', 0);
    }

    public function test_unverified_user_is_redirected_to_verification_notice(): void
    {
        $user = OrganizationUser::factory()->unverified()->create();
        Organization::factory()->for($user)->create();
        $productId = $this->createProduct('supplier-portal');

        $this->actingAsOrganizationUser($user)
            ->post(route('products.purchase', ['product' => $productId]))
            ->assertRedirect(route('verification.notice'));

        $this->assertDatabaseCount('organization_entitlements', 0);
    }

    public function test_user_without_an_organization_is_forbidden(): void
    {
        $user = OrganizationUser::factory()->create();
        $productId = $this->createProduct('supplier-portal');

        $this->actingAsOrganizationUser($user)
            ->post(route('products.purchase', ['product' => $productId]))
            ->assertForbidden();

        $this->assertDatabaseCount('organization_entitlements', 0);
    }

    /** @param numeric-string $priceAmount */
    #[DataProvider('purchasePrices')]
    public function test_purchase_does_not_grant_a_perpetual_entitlement_during_transition(string $priceAmount): void
    {
        $purchaseTime = CarbonImmutable::parse('2026-09-04T12:00:00+03:00');
        $this->travelTo($purchaseTime);
        $user = OrganizationUser::factory()->create();
        $organization = Organization::factory()->for($user)->create();
        $productId = $this->createProduct(
            'supplier-portal',
            type: 'application',
            priceAmount: $priceAmount,
            currencyCode: 'TRY',
        );

        $response = $this->actingAsOrganizationUser($user)
            ->from(route('products.index'))
            ->post(route('products.purchase', ['product' => $productId]));

        $response->assertRedirect(route('products.index'));
        $response->assertSessionHasErrors(['product' => trans('ui.products.purchaseUnavailable')]);
        $this->assertDatabaseMissing('organization_entitlements', ['organization_id' => $organization->getKey()]);
    }

    /** @param array<string, bool> $productState */
    #[DataProvider('unavailableProductStates')]
    public function test_unavailable_product_cannot_be_purchased(array $productState): void
    {
        $user = OrganizationUser::factory()->create();
        $organization = Organization::factory()->for($user)->create();
        $hasPrice = $productState['hasPrice'] ?? true;
        $currencyActive = $productState['currencyActive'] ?? true;
        $currencyDeleted = $productState['currencyDeleted'] ?? false;
        $productId = $this->createProduct(
            'unavailable-product',
            active: $productState['active'] ?? true,
            purchasable: $productState['purchasable'] ?? true,
            priceAmount: $hasPrice ? '100.00' : null,
            currencyCode: $hasPrice ? 'TRY' : null,
            currencyActive: $currencyActive,
            currencyDeleted: $currencyDeleted,
        );

        $this->actingAsOrganizationUser($user)
            ->post(route('products.purchase', ['product' => $productId]))
            ->assertNotFound();

        $this->assertDatabaseMissing('organization_entitlements', [
            'organization_id' => $organization->getKey(),
            'product_id' => $productId,
        ]);
    }

    public function test_product_with_an_existing_entitlement_cannot_be_purchased_again(): void
    {
        $user = OrganizationUser::factory()->create();
        $organization = Organization::factory()->for($user)->create();
        $productId = $this->createProduct('supplier-portal');
        $this->createEntitlement(
            $organization,
            $productId,
            status: 'suspended',
            source: 'purchase',
        );

        $this->actingAsOrganizationUser($user)
            ->post(route('products.purchase', ['product' => $productId]))
            ->assertNotFound();

        $this->assertSame(
            1,
            OrganizationEntitlement::query()
                ->whereBelongsTo($organization)
                ->where('product_id', $productId)
                ->count(),
        );
    }

    public function test_purchase_cannot_target_another_organization_during_transition(): void
    {
        $otherOrganization = Organization::factory()->create();
        $user = OrganizationUser::factory()->create();
        $organization = Organization::factory()->for($user)->create();
        $productId = $this->createProduct('supplier-portal');
        $this->createEntitlement($otherOrganization, $productId, source: 'purchase');

        $this->actingAsOrganizationUser($user)
            ->from(route('products.index'))
            ->post(route('products.purchase', ['product' => $productId]), [
                'organization_id' => $otherOrganization->getKey(),
                'price_amount' => '0',
                'ends_at' => '2099-01-01',
            ])
            ->assertSessionHasErrors(['product' => trans('ui.products.purchaseUnavailable')]);

        $this->assertDatabaseMissing('organization_entitlements', [
            'organization_id' => $organization->getKey(),
            'product_id' => $productId,
            'status' => OrganizationEntitlement::STATUS_ACTIVE,
            'source' => OrganizationEntitlement::SOURCE_PURCHASE,
        ]);
        $this->assertDatabaseHas('organization_entitlements', [
            'organization_id' => $otherOrganization->getKey(),
            'product_id' => $productId,
            'ends_at' => null,
        ]);
        $this->assertDatabaseCount('organization_entitlements', 1);
    }

    /** @return array<string, array{0: array<string, bool>}> */
    public static function unavailableProductStates(): array
    {
        return [
            'inactive product' => [['active' => false]],
            'not purchasable product' => [['purchasable' => false]],
            'product without price' => [['hasPrice' => false]],
            'product with inactive currency' => [['currencyActive' => false]],
            'product with deleted currency' => [['currencyDeleted' => true]],
        ];
    }

    /** @return array<string, array{numeric-string}> */
    public static function purchasePrices(): array
    {
        return ['free' => ['0.0000'], 'paid' => ['1499.9000']];
    }

    private function actingAsOrganizationUser(OrganizationUser $user): static
    {
        $this->actingAs($user)->withSession([
            AuthenticateOrganizationUser::SESSION_AUTH_VERSION => $user->auth_version,
        ]);

        return $this;
    }
}
