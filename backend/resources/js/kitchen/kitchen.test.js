import { describe, expect, it, vi } from 'vitest';
import { flushPromises, mount } from '@vue/test-utils';
import { makeI18n, messages } from '../shared/i18n';
import OrderCard from './components/OrderCard.vue';
import KitchenBoard from './components/KitchenBoard.vue';

function flatKeys(obj, prefix = '') {
    return Object.entries(obj).flatMap(([k, v]) => (typeof v === 'object' ? flatKeys(v, `${prefix}${k}.`) : [`${prefix}${k}`]));
}

function order(overrides = {}) {
    return {
        id: 1,
        order_number: 'LLW-CENTRAL-260929-0001',
        status: 'NEW',
        payment_method: 'CASH',
        payment_status: 'PENDING',
        is_payable: true,
        ordered_at: new Date().toISOString(),
        scheduled_at: null,
        delivery_address: { delivery_note: null },
        items: [{ name: 'Bento ya Nkhuku', quantity: 2, options: [{ name: 'Wochuluka' }] }],
        ...overrides,
    };
}

const mountCard = (props, locale = 'en') =>
    mount(OrderCard, { props: { now: Date.now(), ...props }, global: { plugins: [makeI18n(locale)] } });

describe('locale files', () => {
    const reference = flatKeys(messages.en).sort();
    for (const [code, m] of Object.entries(messages)) {
        it(`${code} has exactly the English keys`, () => {
            expect(flatKeys(m).sort()).toEqual(reference);
        });
    }
});

describe('OrderCard', () => {
    it.each([
        ['NEW', 'accept', 'ACCEPT ORDER'],
        ['CONFIRMED', 'start-cooking', 'START COOKING'],
        ['COOKING', 'ready', 'READY'],
    ])('%s shows the %s button', async (status, action, label) => {
        const wrapper = mountCard({ order: order({ status }) });
        const button = wrapper.get(`[data-action="${action}"]`);
        expect(button.text()).toBe(label);
        await button.trigger('click');
        expect(wrapper.emitted('action')[0]).toEqual([action]);
    });

    it('ready orders have no kitchen action', () => {
        const wrapper = mountCard({ order: order({ status: 'READY_FOR_PICKUP' }) });
        expect(wrapper.find('[data-action="accept"]').exists()).toBe(false);
        expect(wrapper.text()).toContain('Waiting for rider');
    });

    it('unpaid mobile money orders cannot be accepted', () => {
        const wrapper = mountCard({ order: order({ payment_method: 'AIRTEL_MONEY', is_payable: false }) });
        expect(wrapper.get('[data-action="accept"]').attributes('disabled')).toBeDefined();
        expect(wrapper.text()).toContain('Waiting for payment');
    });

    it.each([
        ['ny', 'LANDIRANI ODA', 'Ndalama pofika'],
        ['ja', '注文を受け付ける', '代金引換'],
    ])('renders in %s', (locale, accept, cash) => {
        const wrapper = mountCard({ order: order() }, locale);
        expect(wrapper.get('[data-action="accept"]').text()).toBe(accept);
        expect(wrapper.text()).toContain(cash);
    });
});

describe('KitchenBoard', () => {
    it('groups orders into NEW / COOKING / READY and applies actions', async () => {
        const api = {
            get: vi.fn().mockResolvedValue({
                data: [order({ id: 1 }), order({ id: 2, status: 'CONFIRMED' }), order({ id: 3, status: 'COOKING' }), order({ id: 4, status: 'READY_FOR_PICKUP' })],
            }),
            post: vi.fn().mockResolvedValue({ data: order({ id: 1, status: 'CONFIRMED' }) }),
        };
        const wrapper = mount(KitchenBoard, { props: { api, pollMs: 60000 }, global: { plugins: [makeI18n('en')] } });
        await flushPromises();

        expect(wrapper.findAll('[data-column="new"] article')).toHaveLength(2);
        expect(wrapper.findAll('[data-column="cooking"] article')).toHaveLength(1);
        expect(wrapper.findAll('[data-column="ready"] article')).toHaveLength(1);

        await wrapper.get('[data-column="new"] [data-action="accept"]').trigger('click');
        await flushPromises();
        expect(api.post).toHaveBeenCalledWith('/admin/orders/1/accept', {});
        expect(wrapper.findAll('[data-column="new"] [data-action="start-cooking"]')).toHaveLength(2);
        wrapper.unmount();
    });

    it('shows translated error codes and reloads after a conflict', async () => {
        const { ApiError } = await import('../shared/api');
        const api = {
            get: vi.fn().mockResolvedValue({ data: [order()] }),
            post: vi.fn().mockRejectedValue(new ApiError('INVALID_STATUS_TRANSITION')),
        };
        const wrapper = mount(KitchenBoard, { props: { api, pollMs: 60000 }, global: { plugins: [makeI18n('ja')] } });
        await flushPromises();
        await wrapper.get('[data-action="accept"]').trigger('click');
        await flushPromises();

        expect(wrapper.get('[role="alert"]').text()).toBe(messages.ja.errors.INVALID_STATUS_TRANSITION);
        expect(api.get).toHaveBeenCalledTimes(2);
        wrapper.unmount();
    });
});
