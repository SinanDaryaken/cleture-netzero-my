import { mount } from '@vue/test-utils';
import { beforeEach, describe, expect, it, vi } from 'vitest';
import { ref } from 'vue';

const inertia = vi.hoisted(() => ({
    start: vi.fn(),
    stop: vi.fn(),
    usePoll: vi.fn(),
}));

vi.mock('@inertiajs/vue3', () => ({
    Head: { template: '<slot />' },
    Link: { template: '<a><slot /></a>' },
    useForm: (values: Record<string, unknown>) => ({
        ...values,
        errors: {},
        processing: false,
        patch: vi.fn(),
        post: vi.fn(),
    }),
    usePage: () => ({
        props: {
            flash: { status: null },
            localization: {
                translations: {
                    organization: {
                        headTitle: 'Organizasyon',
                        backToDashboard: 'Panele dön',
                        eyebrow: 'Organizasyon',
                        title: 'Organizasyon',
                        description: 'Organizasyonunuzu yönetin.',
                        existingLabel: 'Mevcut',
                        newLabel: 'Yeni',
                        formTitle: 'Bilgiler',
                        name: 'Ad',
                        taxNumber: 'Vergi numarası',
                        taxNumberHint: 'Vergi numaranızı girin.',
                        createSubmit: 'Oluştur',
                        creating: 'Oluşturuluyor',
                        updateSubmit: 'Güncelle',
                        updating: 'Güncelleniyor',
                        netZeroTitle: 'NetZero',
                        netZeroDescription: 'NetZero kurulum durumunuz.',
                        netZeroRequestSubmit: 'Kurulumu başlat',
                        netZeroRequesting: 'Başlatılıyor',
                        netZeroStatusLabel: 'Durum',
                        netZeroStatuses: {
                            pending: 'Bekliyor',
                            provisioning: 'Hazırlanıyor',
                            ready: 'Hazır',
                            failed: 'Başarısız',
                        },
                    },
                },
            },
        },
    }),
    usePoll: inertia.usePoll,
}));

import Organization from './Organization.vue';

type ProvisioningStatus = 'pending' | 'provisioning' | 'ready' | 'failed';

function organization(provisioningStatus: ProvisioningStatus) {
    return {
        name: 'Cleture Teknoloji',
        taxNumber: '1234567890',
        netZeroRequested: true,
        tenant: {
            provisioningStatus,
            active: provisioningStatus === 'ready',
            available: provisioningStatus === 'ready',
        },
    };
}

describe('Organization NetZero provisioning status', () => {
    beforeEach(() => {
        inertia.start.mockReset();
        inertia.stop.mockReset();
        inertia.usePoll.mockReset();
        inertia.usePoll.mockReturnValue({
            start: inertia.start,
            stop: inertia.stop,
            polling: ref(false),
        });
    });

    it('polls only the organization prop while provisioning is in progress', () => {
        mount(Organization, {
            props: { organization: organization('pending') },
            global: {
                stubs: {
                    AppLayout: { template: '<div><slot /></div>' },
                    FormField: true,
                    StatusMessage: true,
                    SubmitButton: true,
                },
            },
        });

        expect(inertia.usePoll).toHaveBeenCalledWith(
            2_000,
            {
                only: ['organization'],
                showProgress: false,
            },
            {
                autoStart: true,
                mode: 'rest',
            },
        );
    });

    it('starts for an active provisioning state and stops at a terminal state', async () => {
        const wrapper = mount(Organization, {
            props: { organization: organization('ready') },
            global: {
                stubs: {
                    AppLayout: { template: '<div><slot /></div>' },
                    FormField: true,
                    StatusMessage: true,
                    SubmitButton: true,
                },
            },
        });

        await wrapper.setProps({ organization: organization('provisioning') });

        expect(inertia.start).toHaveBeenCalledOnce();

        await wrapper.setProps({ organization: organization('failed') });

        expect(inertia.stop).toHaveBeenCalledOnce();
        expect(wrapper.get('.netzero-provisioning-status').attributes()).toMatchObject({
            role: 'status',
            'aria-live': 'polite',
            'aria-atomic': 'true',
        });
    });
});
