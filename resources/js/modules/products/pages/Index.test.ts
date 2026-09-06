import { mount } from '@vue/test-utils';
import { beforeEach, describe, expect, it, vi } from 'vitest';

const inertia = vi.hoisted(() => ({
    post: vi.fn(),
}));

vi.mock('@inertiajs/vue3', () => ({
    Head: { props: ['title'], template: '<slot />' },
    useForm: () => ({
        processing: false,
        post: inertia.post,
        clearErrors: vi.fn(),
        errors: {},
        hasErrors: false,
    }),
    usePage: () => ({
        props: {
            localization: {
                locale: 'tr',
                translations: {
                    products: {
                        headTitle: 'Ürünler',
                        eyebrow: 'Ürün kataloğu',
                        title: 'Ürünleriniz',
                        description: 'Ürünlerinizi inceleyin.',
                        organizationLabel: 'Organizasyon',
                        ownedTitle: 'Sahip olduğunuz ürünler',
                        ownedDescription: 'Tanımlı ürünler.',
                        ownedBadge: 'Sahip olduğunuz',
                        ownedEmpty: 'Tanımlı ürün yok.',
                        ownedEmptyHint: 'Tanımlı ürünler burada görünecek.',
                        availableTitle: 'Eklenebilir ürünler',
                        availableDescription: 'Ek ürünler.',
                        availableBadge: 'Eklenebilir',
                        availableEmpty: 'Yeni ürün bulunmuyor.',
                        availableEmptyHint: 'Yeni ürünler burada görünecek.',
                        purchase: 'Satın al',
                        startsAt: 'Başlangıç',
                        endsAt: 'Bitiş',
                        dateNotSet: 'Belirtilmemiş',
                        annualPrice: 'Yıllık fiyat',
                        historyTitle: 'Geçmiş işlemler',
                        historyDescription:
                            'Organizasyonunuzun kayıtlı ürün alımları ve kullanım dönemleri.',
                        historyProduct: 'Ürün',
                        purchasedAt: 'İşlem tarihi',
                        historyAmount: 'Tutar',
                        historyEmpty: 'Kayıtlı geçmiş işlem bulunmuyor.',
                        previous: 'Önceki',
                        next: 'Sonraki',
                        purchaseTitle: 'Satın alma özeti',
                        period: 'Kullanım süresi',
                        oneYear: '1 yıl',
                        paymentNotice:
                            'Ödeme adımı henüz kullanıma açılmadı. Bu aşamada tahsilat yapılmaz ve kullanım hakkı başlamaz.',
                        cancel: 'Vazgeç',
                        continuePayment: 'Ödemeye devam et',
                        purchaseUnavailable: 'Ürün edinme şu anda kullanılamıyor.',
                        purchasing: 'Satın alınıyor…',
                        purchased: 'Ürün organizasyonunuza eklendi.',
                        statuses: {
                            active: 'Aktif',
                            suspended: 'Askıya alınmış',
                            expired: 'Süresi dolmuş',
                        },
                        types: {
                            module: 'Modül',
                            addon: 'Eklenti',
                            application: 'Uygulama',
                        },
                    },
                },
            },
        },
    }),
}));

import ProductCatalog from './Index.vue';

const availableProducts = [
    {
        id: '019a0000-0000-7000-8000-000000000001',
        key: 'supplier-portal',
        type: 'application' as const,
        name: 'Tedarikçi Portalı',
        description: 'Tedarikçi verilerini yönetin.',
        price: {
            amount: '1499.9000',
            currencyCode: 'TRY',
            currencySymbol: '₺',
            formatted: '₺1.499,90',
        },
        purchaseUrl: '/products/019a0000-0000-7000-8000-000000000001/purchase',
    },
];

const ownedProducts = [
    {
        id: '019a0000-0000-7000-8000-000000000002',
        key: 'netzero-carbon',
        type: 'module' as const,
        name: 'NetZero Karbon',
        description: 'Karbon emisyonlarını yönetin.',
        entitlement: {
            status: 'active' as const,
            source: 'default',
            startsAt: '2026-09-05T12:00:00.000000Z',
            endsAt: null as string | null,
        },
    },
];

const emptyHistory = { data: [], prev_page_url: null, next_page_url: null };

function mountCatalog(
    owned = ownedProducts,
    available = availableProducts,
    history: {
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
    } = emptyHistory,
) {
    return mount(ProductCatalog, {
        props: {
            organization: { name: 'Cleture Teknoloji' },
            ownedProducts: owned,
            availableProducts: available,
            purchaseHistory: history,
        },
        global: {
            stubs: {
                AppLayout: { template: '<div><slot /></div>' },
                Dialog: {
                    props: ['visible', 'header'],
                    template:
                        '<div v-if="visible" role="dialog"><h2>{{ header }}</h2><slot /><slot name="footer" /></div>',
                },
            },
        },
    });
}

describe('Product catalog', () => {
    beforeEach(() => {
        inertia.post.mockReset();
    });

    it('renders owned and available products for the organization', () => {
        const wrapper = mountCatalog();

        expect(wrapper.get('.product-organization-context').text()).toContain('Cleture Teknoloji');
        expect(wrapper.get('.product-owned-card').text()).toContain('NetZero Karbon');
        expect(wrapper.get('.product-owned-card').text()).toContain('Sahip olduğunuz');
        expect(wrapper.get('.product-owned-card').text()).toContain('Aktif');
        expect(wrapper.get('.product-owned-card').find('.product-purchase-button').exists()).toBe(
            false,
        );
        expect(wrapper.get('.product-catalog-section:nth-of-type(2)').text()).toContain(
            'Tedarikçi Portalı',
        );
        expect(wrapper.get('.product-catalog-section:nth-of-type(2)').text()).toContain(
            'Eklenebilir',
        );
        expect(wrapper.get('.product-price').text()).toContain('₺1.499,90');
        expect(wrapper.get('.product-purchase-button').text()).toContain('Satın al');
    });

    it('opens a purchase summary and submits only after continuing to payment', async () => {
        const wrapper = mountCatalog();

        expect(wrapper.get('.product-purchase-button').attributes('disabled')).toBeUndefined();
        await wrapper.get('.product-purchase-button').trigger('click');
        expect(inertia.post).not.toHaveBeenCalled();
        expect(wrapper.get('[role="dialog"]').text()).toContain('Tedarikçi Portalı');
        expect(wrapper.get('[role="dialog"]').text()).toContain('₺1.499,90');
        expect(wrapper.get('[role="dialog"]').text()).toContain('1 yıl');
        expect(wrapper.get('[role="dialog"]').text()).toContain(
            'Ödeme adımı henüz kullanıma açılmadı.',
        );
        await wrapper.get('#product-purchase').trigger('submit');
        expect(inertia.post).toHaveBeenCalledWith(availableProducts[0]!.purchaseUrl, {
            preserveScroll: true,
        });
    });

    it('renders separate empty states when the organization has no catalog products', () => {
        const wrapper = mountCatalog([], []);

        expect(wrapper.find('.product-catalog-card').exists()).toBe(false);
        expect(wrapper.findAll('.product-empty-state')).toHaveLength(3);
        expect(wrapper.text()).toContain('Tanımlı ürün yok.');
        expect(wrapper.text()).toContain('Yeni ürün bulunmuyor.');
        expect(wrapper.text()).toContain('Kayıtlı geçmiş işlem bulunmuyor.');
    });

    it('shows recorded entitlement dates and a missing legacy end date', () => {
        const wrapper = mountCatalog();
        expect(wrapper.get('.product-owned-card time').attributes('datetime')).toBe(
            '2026-09-05T12:00:00.000000Z',
        );
        expect(wrapper.get('.product-owned-card time').text()).toContain('12:00 UTC');
        expect(wrapper.get('.product-owned-card').text()).toContain('Belirtilmemiş');
    });

    it('shows the finite entitlement end and historical purchase amounts and periods', () => {
        const wrapper = mountCatalog(
            [
                {
                    ...ownedProducts[0]!,
                    entitlement: {
                        ...ownedProducts[0]!.entitlement,
                        endsAt: '2027-09-05T12:00:00.000000Z',
                    },
                },
            ],
            availableProducts,
            {
                data: [
                    {
                        id: 'purchase-1',
                        productName: 'Eski ürün',
                        purchasedAt: '2025-01-01T00:00:00Z',
                        startsAt: '2025-01-01T00:00:00Z',
                        endsAt: '2026-01-01T00:00:00Z',
                        amount: '12.3400',
                        currencyCode: 'EUR',
                        formattedAmount: '€12,34',
                    },
                ],
                prev_page_url: null,
                next_page_url: null,
            },
        );
        expect(wrapper.findAll('.product-owned-card time')[1]!.attributes('datetime')).toBe(
            '2027-09-05T12:00:00.000000Z',
        );
        expect(wrapper.get('tbody').text()).toContain('Eski ürün');
        expect(wrapper.get('tbody').text()).toContain('€12,34');
        expect(wrapper.get('tbody').text()).toContain('EUR');
        expect(wrapper.findAll('tbody time').map((item) => item.attributes('datetime'))).toEqual([
            '2025-01-01T00:00:00Z',
            '2025-01-01T00:00:00Z',
            '2026-01-01T00:00:00Z',
        ]);
    });
});
