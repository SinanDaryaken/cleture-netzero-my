import { mount } from '@vue/test-utils';
import { describe, expect, it } from 'vitest';

import FormField from './FormField.vue';

describe('FormField', () => {
    it('connects the validation message to the invalid input', async () => {
        const wrapper = mount(FormField, {
            props: {
                id: 'email',
                label: 'E-posta adresi',
                modelValue: '',
                error: 'Geçerli bir e-posta adresi girin.',
                'onUpdate:modelValue': () => undefined,
            },
        });

        const input = wrapper.get('input');

        expect(input.attributes('aria-invalid')).toBe('true');
        expect(input.attributes('aria-describedby')).toBe('email-description');
        expect(wrapper.get('#email-description').text()).toContain('Geçerli bir e-posta');
    });

    it('emits the updated value', async () => {
        const wrapper = mount(FormField, {
            props: {
                id: 'name',
                label: 'Ad soyad',
                modelValue: '',
                'onUpdate:modelValue': () => undefined,
            },
        });

        await wrapper.get('input').setValue('Sinan Daryaken');

        expect(wrapper.emitted('update:modelValue')).toEqual([['Sinan Daryaken']]);
    });
});
