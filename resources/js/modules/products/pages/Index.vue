<script setup lang="ts">
import { Head, Link, useForm, usePage } from '@inertiajs/vue3';
import Button from 'primevue/button';
import Dialog from 'primevue/dialog';
import { computed, ref } from 'vue';

import AppLayout from '../../../layouts/AppLayout.vue';
import type { SharedPageProps } from '../../identity-access/types';

type ProductType = 'module' | 'addon' | 'application';

type Product = {
    id: string;
    key: string;
    type: ProductType;
    name: string;
    description: string | null;
};

type AvailableProduct = Product & {
    price: {
        amount: string;
        currencyCode: string;
        currencySymbol: string;
        formatted: string;
    };
    purchaseUrl: string;
};

type EntitlementStatus = 'active' | 'suspended' | 'expired';

type OwnedProduct = Product & {
    entitlement: {
        status: EntitlementStatus;
        source: string;
        startsAt: string | null;
        endsAt: string | null;
    };
};

defineProps<{
    organization: {
        name: string;
    };
    ownedProducts: OwnedProduct[];
    availableProducts: AvailableProduct[];
    purchaseHistory: {
        data: {
            id: string;
            productName: string;
            purchasedAt: string;
            startsAt: string;
            endsAt: string;
            amount: string;
            currencyCode: string;
            formattedAmount: string;
        }[];
        prev_page_url: string | null;
        next_page_url: string | null;
    };
}>();

const page = usePage<SharedPageProps>();
const translations = computed(() => page.props.localization.translations.products);
const selectedProduct = ref<AvailableProduct | null>(null);
const dialogVisible = ref(false);
const purchaseForm = useForm({});

function formatDate(value: string | null): string {
    if (!value) {
        return translations.value.dateNotSet;
    }

    return new Intl.DateTimeFormat(page.props.localization.locale, {
        dateStyle: 'medium',
        timeStyle: 'short',
        timeZone: 'UTC',
    }).format(new Date(value));
}

function openPurchase(product: AvailableProduct): void {
    purchaseForm.clearErrors();
    selectedProduct.value = product;
    dialogVisible.value = true;
}

function purchase(): void {
    if (!selectedProduct.value || purchaseForm.processing) {
        return;
    }

    purchaseForm.post(selectedProduct.value.purchaseUrl, {
        preserveScroll: true,
    });
}

function productIcon(type: ProductType): string {
    return {
        module: 'pi pi-th-large',
        addon: 'pi pi-plus-circle',
        application: 'pi pi-desktop',
    }[type];
}
</script>

<template>
    <Head :title="translations.headTitle" />
    <AppLayout>
        <main class="product-page-shell">
            <header class="product-page-heading">
                <div>
                    <p class="eyebrow">{{ translations.eyebrow }}</p>
                    <h1>{{ translations.title }}</h1>
                    <p>{{ translations.description }}</p>
                </div>
                <div class="product-organization-context">
                    <span>{{ translations.organizationLabel }}</span>
                    <strong>{{ organization.name }}</strong>
                </div>
            </header>

            <section class="product-catalog-section" aria-labelledby="owned-products-title">
                <header class="product-section-heading">
                    <div>
                        <h2 id="owned-products-title">{{ translations.ownedTitle }}</h2>
                        <p>{{ translations.ownedDescription }}</p>
                    </div>
                    <span class="product-section-count">{{ ownedProducts.length }}</span>
                </header>

                <div v-if="ownedProducts.length > 0" class="product-catalog-grid">
                    <article
                        v-for="product in ownedProducts"
                        :key="product.id"
                        class="product-catalog-card product-owned-card"
                    >
                        <div class="product-card-heading">
                            <span class="product-card-icon">
                                <i :class="productIcon(product.type)" aria-hidden="true"></i>
                            </span>
                            <span class="product-owned-badge">
                                <i class="pi pi-verified" aria-hidden="true"></i>
                                {{ translations.ownedBadge }}
                            </span>
                        </div>
                        <div class="product-card-copy">
                            <span class="product-type-label">{{
                                translations.types[product.type]
                            }}</span>
                            <h3>{{ product.name }}</h3>
                            <p v-if="product.description">{{ product.description }}</p>
                            <span
                                class="product-entitlement-status"
                                :class="`product-entitlement-status-${product.entitlement.status}`"
                            >
                                {{ translations.statuses[product.entitlement.status] }}
                            </span>
                        </div>
                        <dl class="grid gap-3 border-t border-slate-100 pt-4 text-sm">
                            <div>
                                <dt class="text-slate-500">{{ translations.startsAt }}</dt>
                                <dd class="mt-1 font-medium text-slate-800">
                                    <time
                                        v-if="product.entitlement.startsAt"
                                        :datetime="product.entitlement.startsAt"
                                        >{{ formatDate(product.entitlement.startsAt) }} UTC</time
                                    >
                                    <span v-else>{{ translations.dateNotSet }}</span>
                                </dd>
                            </div>
                            <div>
                                <dt class="text-slate-500">{{ translations.endsAt }}</dt>
                                <dd class="mt-1 font-medium text-slate-800">
                                    <time
                                        v-if="product.entitlement.endsAt"
                                        :datetime="product.entitlement.endsAt"
                                        >{{ formatDate(product.entitlement.endsAt) }} UTC</time
                                    >
                                    <span v-else>{{ translations.dateNotSet }}</span>
                                </dd>
                            </div>
                        </dl>
                    </article>
                </div>

                <div v-else class="product-empty-state product-empty-state-compact">
                    <i class="pi pi-box" aria-hidden="true"></i>
                    <strong>{{ translations.ownedEmpty }}</strong>
                    <p>{{ translations.ownedEmptyHint }}</p>
                </div>
            </section>

            <section class="product-catalog-section" aria-labelledby="available-products-title">
                <header class="product-section-heading">
                    <div>
                        <h2 id="available-products-title">{{ translations.availableTitle }}</h2>
                        <p>{{ translations.availableDescription }}</p>
                    </div>
                    <span class="product-section-count">{{ availableProducts.length }}</span>
                </header>

                <div v-if="availableProducts.length > 0" class="product-catalog-grid">
                    <article
                        v-for="product in availableProducts"
                        :key="product.id"
                        class="product-catalog-card"
                    >
                        <div class="product-card-heading">
                            <span class="product-card-icon">
                                <i :class="productIcon(product.type)" aria-hidden="true"></i>
                            </span>
                            <span class="product-available-badge">
                                <i class="pi pi-check-circle" aria-hidden="true"></i>
                                {{ translations.availableBadge }}
                            </span>
                        </div>
                        <div class="product-card-copy">
                            <span class="product-type-label">{{
                                translations.types[product.type]
                            }}</span>
                            <h3>{{ product.name }}</h3>
                            <p v-if="product.description">{{ product.description }}</p>
                        </div>
                        <div class="product-purchase-form">
                            <div class="product-price">
                                <span>{{ product.price.formatted }}</span>
                                <small
                                    >{{ product.price.currencyCode }} ·
                                    {{ translations.annualPrice }}</small
                                >
                            </div>
                            <button
                                type="button"
                                class="product-purchase-button"
                                @click="openPurchase(product)"
                            >
                                {{ translations.purchase }}
                            </button>
                        </div>
                    </article>
                </div>

                <div v-else class="product-empty-state product-empty-state-compact">
                    <i class="pi pi-box" aria-hidden="true"></i>
                    <strong>{{ translations.availableEmpty }}</strong>
                    <p>{{ translations.availableEmptyHint }}</p>
                </div>
            </section>
            <section class="product-catalog-section" aria-labelledby="purchase-history-title">
                <header class="product-section-heading">
                    <div>
                        <h2 id="purchase-history-title">{{ translations.historyTitle }}</h2>
                        <p>{{ translations.historyDescription }}</p>
                    </div>
                </header>
                <div v-if="purchaseHistory.data.length" class="tenant-table-scroll">
                    <table class="tenant-table">
                        <thead>
                            <tr>
                                <th scope="col">{{ translations.historyProduct }}</th>
                                <th scope="col">{{ translations.purchasedAt }}</th>
                                <th scope="col">{{ translations.historyAmount }}</th>
                                <th scope="col">{{ translations.startsAt }}</th>
                                <th scope="col">{{ translations.endsAt }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="transaction in purchaseHistory.data" :key="transaction.id">
                                <td>{{ transaction.productName }}</td>
                                <td>
                                    <time :datetime="transaction.purchasedAt"
                                        >{{ formatDate(transaction.purchasedAt) }} UTC</time
                                    >
                                </td>
                                <td>
                                    {{ transaction.formattedAmount }}
                                    <small>{{ transaction.currencyCode }}</small>
                                </td>
                                <td>
                                    <time :datetime="transaction.startsAt"
                                        >{{ formatDate(transaction.startsAt) }} UTC</time
                                    >
                                </td>
                                <td>
                                    <time :datetime="transaction.endsAt"
                                        >{{ formatDate(transaction.endsAt) }} UTC</time
                                    >
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <div v-else class="product-empty-state product-empty-state-compact">
                    <i class="pi pi-history" aria-hidden="true"></i>
                    <strong>{{ translations.historyEmpty }}</strong>
                </div>
                <nav
                    v-if="purchaseHistory.prev_page_url || purchaseHistory.next_page_url"
                    class="tenant-pagination"
                    :aria-label="translations.historyTitle"
                >
                    <Link
                        v-if="purchaseHistory.prev_page_url"
                        :href="purchaseHistory.prev_page_url"
                        class="tenant-pagination-link"
                        preserve-scroll
                        >{{ translations.previous }}</Link
                    >
                    <Link
                        v-if="purchaseHistory.next_page_url"
                        :href="purchaseHistory.next_page_url"
                        class="tenant-pagination-link"
                        preserve-scroll
                        >{{ translations.next }}</Link
                    >
                </nav>
            </section>
        </main>
        <Dialog
            v-model:visible="dialogVisible"
            modal
            :closable="!purchaseForm.processing"
            :header="translations.purchaseTitle"
            class="tenant-form-dialog"
        >
            <form
                v-if="selectedProduct"
                id="product-purchase"
                class="tenant-dialog-form"
                @submit.prevent="purchase"
            >
                <h3 class="text-lg font-semibold">{{ selectedProduct.name }}</h3>
                <dl class="grid gap-4 text-sm">
                    <div class="flex justify-between gap-4">
                        <dt>{{ translations.annualPrice }}</dt>
                        <dd class="font-semibold">
                            {{ selectedProduct.price.formatted }}
                            {{ selectedProduct.price.currencyCode }}
                        </dd>
                    </div>
                    <div class="flex justify-between gap-4">
                        <dt>{{ translations.period }}</dt>
                        <dd>{{ translations.oneYear }}</dd>
                    </div>
                </dl>
                <p class="text-sm text-slate-600">{{ translations.paymentNotice }}</p>
                <p v-if="purchaseForm.hasErrors" role="alert" class="text-sm text-red-700">
                    {{ Object.values(purchaseForm.errors)[0] }}
                </p>
            </form>
            <template #footer>
                <Button
                    severity="secondary"
                    text
                    :label="translations.cancel"
                    :disabled="purchaseForm.processing"
                    @click="dialogVisible = false"
                />
                <Button
                    type="submit"
                    form="product-purchase"
                    :label="translations.continuePayment"
                    :loading="purchaseForm.processing"
                />
            </template>
        </Dialog>
    </AppLayout>
</template>
