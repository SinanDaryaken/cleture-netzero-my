import { mount } from '@vue/test-utils';
import { beforeEach, describe, expect, it, vi } from 'vitest';

const inertia = vi.hoisted(() => ({
    tenantAvailable: false,
    flashStatus: null as string | null,
    flashError: null as string | null,
    post: vi.fn(),
    on: vi.fn(() => vi.fn()),
    toastAdd: vi.fn(),
}));

vi.mock('@inertiajs/vue3', () => ({
    Link: {
        props: ['href'],
        template: '<a :href="href"><slot /></a>',
    },
    router: {
        post: inertia.post,
        on: inertia.on,
    },
    usePage: () => ({
        url: '/dashboard',
        props: {
            auth: {
                user: {
                    name: 'Sinan Daryaken',
                    email: 'sinan@example.com',
                },
                tenant: {
                    provisioningStatus: inertia.tenantAvailable ? 'ready' : 'pending',
                    available: inertia.tenantAvailable,
                },
            },
            localization: {
                locale: 'tr',
                languages: [{ code: 'tr', nativeName: 'Türkçe' }],
                translations: {
                    appLayout: {
                        dashboardLabel: 'Genel bakışa git',
                        navigationLabel: 'Ana gezinme',
                        overview: 'Genel Bakış',
                        organization: 'Organizasyon',
                        tenantManagement: 'Tenant yönetimi',
                        tenantUsers: 'Kullanıcılar',
                        organizationalUnits: 'Organizasyon Birimleri',
                        organizationUnitTypes: 'Organizasyon Türleri',
                        collapseSidebar: 'Yan menüyü daralt',
                        expandSidebar: 'Yan menüyü genişlet',
                        logout: 'Çıkış',
                    },
                    authLayout: {
                        selectLanguage: 'Dil seçin',
                    },
                },
            },
            flash: {
                status: inertia.flashStatus,
                error: inertia.flashError,
            },
        },
    }),
}));

vi.mock('primevue/usetoast', () => ({
    useToast: () => ({ add: inertia.toastAdd }),
}));

import AppLayout from './AppLayout.vue';

function mountLayout() {
    return mount(AppLayout, {
        global: {
            stubs: {
                BrandMark: true,
                Button: { template: '<button type="button"></button>' },
                ConfirmDialog: true,
                Select: true,
                Toast: true,
            },
        },
    });
}

describe('AppLayout tenant navigation', () => {
    beforeEach(() => {
        inertia.tenantAvailable = false;
        inertia.flashStatus = null;
        inertia.flashError = null;
        inertia.post.mockReset();
        inertia.on.mockClear();
        inertia.toastAdd.mockReset();
        window.localStorage.clear();
    });

    it('hides tenant modules while the tenant is unavailable', () => {
        const wrapper = mountLayout();

        expect(wrapper.find('a[href="/tenant/users"]').exists()).toBe(false);
        expect(wrapper.find('a[href="/tenant/organizational-units"]').exists()).toBe(false);
        expect(wrapper.find('a[href="/tenant/organization-unit-types"]').exists()).toBe(false);
    });

    it('shows tenant modules when the tenant is ready and active', () => {
        inertia.tenantAvailable = true;

        const wrapper = mountLayout();

        expect(wrapper.get('a[href="/tenant/users"]').text()).toContain('Kullanıcılar');
        expect(wrapper.get('a[href="/tenant/organizational-units"]').text()).toContain(
            'Organizasyon Birimleri',
        );
        expect(wrapper.get('a[href="/tenant/organization-unit-types"]').text()).toContain(
            'Organizasyon Türleri',
        );
    });

    it('shows flash status as a toast', () => {
        inertia.flashStatus = 'Tenant kullanıcısı güncellendi.';

        mountLayout();

        expect(inertia.toastAdd).toHaveBeenCalledWith({
            severity: 'success',
            detail: 'Tenant kullanıcısı güncellendi.',
            life: 4500,
        });
    });

    it('shows flash errors as an error toast', () => {
        inertia.flashError = 'Bu organizasyon türü kullanımda.';

        mountLayout();

        expect(inertia.toastAdd).toHaveBeenCalledWith({
            severity: 'error',
            detail: 'Bu organizasyon türü kullanımda.',
            life: 4500,
        });
    });
});
