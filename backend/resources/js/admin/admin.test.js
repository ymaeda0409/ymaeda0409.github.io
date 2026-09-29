import { describe, expect, it, vi } from 'vitest';
import { flushPromises, mount } from '@vue/test-utils';
import { ref } from 'vue';
import { makeI18n } from '../shared/i18n';
import { formatMoney, toMinor } from '../shared/format';
import { adminMessages, adminOnly } from './messages';
import { GROUPS, PAGES, parseHash, visiblePages } from './navigation';
import { MANAGEABLE_ROLES, RESOURCES, toPayload, translated } from './resources';
import { clearRelationCache } from './useAdmin';
import ResourceView from './views/ResourceView.vue';
import SalesView from './views/SalesView.vue';
import TranslationFields from './components/TranslationFields.vue';
import SettingsView from './views/SettingsView.vue';

const LANGUAGES = [
    { code: 'en', native_name: 'English', is_default: true },
    { code: 'ny', native_name: 'Chichewa', is_default: false },
    { code: 'ja', native_name: '日本語', is_default: false },
];
const SUPER_ADMIN = {
    id: 1,
    name: 'Admin',
    role: 'SUPER_ADMIN',
    franchise_id: null,
    store_id: null,
    permissions: [...new Set(PAGES.map((p) => p.permission).concat(['catalog.manage', 'stores.create', 'stores.update', 'franchises.manage', 'kitchen.operate']))],
};

function flatten(obj, prefix = '') {
    return Object.entries(obj).flatMap(([k, v]) => (v && typeof v === 'object' ? flatten(v, `${prefix}${k}.`) : [[`${prefix}${k}`, v]]));
}

function placeholders(text) {
    return [...String(text).matchAll(/\{(\w+)\}/g)].map((m) => m[1]).sort();
}

function mountView(component, { props = {}, api, user = SUPER_ADMIN, locale = 'en', go = vi.fn() } = {}) {
    clearRelationCache();
    return mount(component, {
        props,
        global: {
            plugins: [makeI18n(locale, adminMessages)],
            provide: {
                admin: {
                    api,
                    user: ref(user),
                    languages: ref(LANGUAGES),
                    can: (p) => !p || user.permissions.includes(p),
                    go,
                    route: { page: '', id: null, query: {} },
                },
            },
        },
    });
}

describe('admin locale files', () => {
    const en = Object.fromEntries(flatten(adminOnly.en));
    for (const code of Object.keys(adminOnly)) {
        it(`${code} has exactly the English keys and placeholders`, () => {
            const other = Object.fromEntries(flatten(adminOnly[code]));
            expect(Object.keys(other).sort()).toEqual(Object.keys(en).sort());
            for (const [key, text] of Object.entries(en)) {
                expect(placeholders(other[key]), `${code}:${key}`).toEqual(placeholders(text));
            }
        });
    }

    it('every page, field, enum value and group used by the app has a label', () => {
        const keys = new Set(flatten(adminOnly.en).map(([k]) => k));
        const need = [
            ...PAGES.map((p) => `admin.nav.${p.id}`),
            ...GROUPS.map((g) => `admin.nav_groups.${g}`),
            ...Object.values(RESOURCES).flatMap((r) => [
                ...r.columns.map((c) => `admin.fields.${c}`),
                ...r.fields.map((f) => `admin.fields.${f.name}`),
                ...r.fields.filter((f) => f.type === 'translations').flatMap((f) => f.attributes.map((a) => `admin.fields.${a}`)),
                ...r.fields.filter((f) => f.enum && f.options).flatMap((f) => f.options.map((o) => `admin.enums.${f.enum}.${o}`)),
            ]),
            ...MANAGEABLE_ROLES.SUPER_ADMIN.map((r) => `admin.enums.role.${r}`),
        ];
        expect(need.filter((k) => !keys.has(k))).toEqual([]);
    });
});

describe('navigation', () => {
    it('shows only pages the role is allowed to use', () => {
        const kitchen = { permissions: ['stores.view', 'kitchens.view', 'catalog.view', 'orders.view', 'kitchen.operate'] };
        expect(visiblePages(kitchen).map((p) => p.id)).toEqual(['dashboard', 'orders', 'products', 'categories', 'stores', 'kitchens']);
        expect(visiblePages({ permissions: [] })).toEqual([]);
        expect(visiblePages(SUPER_ADMIN).length).toBe(PAGES.length);
    });

    it('parses hash routes with ids and queries', () => {
        expect(parseHash('#/products/12')).toEqual({ page: 'products', id: '12', query: {} });
        expect(parseHash('#/orders?status=NEW')).toEqual({ page: 'orders', id: null, query: { status: 'NEW' } });
        expect(parseHash('')).toEqual({ page: '', id: null, query: {} });
    });
});

describe('resource helpers', () => {
    it('picks translations in the UI language with English fallback', () => {
        const t = { en: { name: 'Chicken Bento' }, ja: { name: 'チキン弁当' } };
        expect(translated(t, 'name', 'ja')).toBe('チキン弁当');
        expect(translated(t, 'name', 'ny')).toBe('Chicken Bento');
        expect(translated({ ja: { name: 'のみ' } }, 'name', 'ny')).toBe('のみ');
    });

    it('builds payloads: numbers typed, create-only and hidden fields dropped, empty password skipped', () => {
        const values = { name: 'Grace', email: 'g@x.test', password: '', role: 'KITCHEN_STAFF', store_id: '3', franchise_id: 9, is_active: true };
        expect(toPayload(RESOURCES.staff, values, { creating: false })).toEqual({
            name: 'Grace', email: 'g@x.test', role: 'KITCHEN_STAFF', store_id: 3, is_active: true,
        });
        const zone = toPayload(RESOURCES.delivery_zones, { store_id: '1', name: 'A', base_fee: 150000 }, { creating: false });
        expect(zone).toEqual({ name: 'A', base_fee: 150000 });
        expect(toMinor('1500', 'MWK')).toBe(150000);
    });

    it('formats money from minor units per locale', () => {
        expect(formatMoney(150000, 'MWK', 'en')).toMatch(/MWK\s?1,500/);
        // Chichewa has no CLDR data in some runtimes → English formatting rules (generic fallback).
        expect(formatMoney(150000, 'MWK', 'ny')).toMatch(/1,500/);
    });
});

describe('ResourceView', () => {
    function productApi() {
        return {
            get: vi.fn(async (path) => {
                if (path === '/admin/categories') return { data: [{ id: 5, code: 'bento', translations: { en: { name: 'Bento' }, ja: { name: '弁当' } } }] };
                return {
                    data: [{ id: 1, sku: 'CHK', category_id: 5, price: 250000, is_featured: true, is_active: true, translations: { en: { name: 'Chicken Bento' }, ja: { name: 'チキン弁当' } } }],
                    meta: { pagination: { current_page: 1, last_page: 1 } },
                };
            }),
            post: vi.fn(async (path, body) => ({ data: { id: 2, ...body } })),
            put: vi.fn(),
            delete: vi.fn(),
        };
    }

    it('lists records with names, relations and money in the UI language', async () => {
        const wrapper = mountView(ResourceView, { props: { name: 'products' }, api: productApi(), locale: 'ja' });
        await flushPromises();
        const row = wrapper.find('[data-test="row"]').text();
        expect(row).toContain('チキン弁当');
        expect(row).toContain('弁当');
        expect(row).toContain('2,500');
        expect(wrapper.text()).toContain('商品');
    });

    it('creates a record with translations per language and minor-unit prices', async () => {
        const api = productApi();
        const go = vi.fn();
        const wrapper = mountView(ResourceView, { props: { name: 'categories', recordId: 'new' }, api, go });
        await flushPromises();

        await wrapper.find('input[id^="f-code"]').setValue('drinks');
        await wrapper.find('[data-test="input-en-name"]').setValue('Drinks');
        await wrapper.find('[data-test="tab-ny"]').trigger('click');
        await wrapper.find('[data-test="input-ny-name"]').setValue('Zakumwa');
        await wrapper.find('form').trigger('submit');
        await flushPromises();

        expect(api.post).toHaveBeenCalledWith('/admin/categories', expect.objectContaining({
            code: 'drinks',
            translations: { en: { name: 'Drinks' }, ny: { name: 'Zakumwa' } },
            is_active: true,
        }));
        expect(go).toHaveBeenCalledWith('categories', 2);
    });

    it('shows field errors and the translated error message', async () => {
        const api = productApi();
        const { ApiError } = await import('../shared/api');
        api.post = vi.fn(async () => {
            throw new ApiError('VALIDATION_FAILED', { status: 422, fields: { code: ['The code has already been taken.'] } });
        });
        const wrapper = mountView(ResourceView, { props: { name: 'categories', recordId: 'new' }, api, locale: 'ny' });
        await flushPromises();
        await wrapper.find('form').trigger('submit');
        await flushPromises();

        expect(wrapper.find('[data-test="form-error"]').text()).toBe('Chonde onaninso zomwe mwalemba.');
        expect(wrapper.text()).toContain('The code has already been taken.');
    });

    it('hides create and save for read-only roles', async () => {
        const viewer = { ...SUPER_ADMIN, role: 'KITCHEN_STAFF', permissions: ['catalog.view'] };
        const wrapper = mountView(ResourceView, { props: { name: 'products' }, api: productApi(), user: viewer });
        await flushPromises();
        expect(wrapper.find('[data-test="create"]').exists()).toBe(false);
    });
});

describe('TranslationFields', () => {
    it('offers one tab per active language and keeps other languages when editing', async () => {
        const wrapper = mountView(TranslationFields, {
            props: { modelValue: { en: { name: 'Bento' } }, attributes: ['name'], required: ['name'] },
            api: {},
        });
        expect(wrapper.findAll('[role="tab"]').map((t) => t.text())).toEqual([
            expect.stringContaining('English'), expect.stringContaining('Chichewa'), expect.stringContaining('日本語'),
        ]);
        await wrapper.find('[data-test="tab-ja"]').trigger('click');
        await wrapper.find('[data-test="input-ja-name"]').setValue('弁当');
        expect(wrapper.emitted('update:modelValue').at(-1)[0]).toEqual({ en: { name: 'Bento' }, ja: { name: '弁当' } });
    });
});

describe('SalesView', () => {
    const report = (groupBy) => ({
        data: {
            from: '2026-03-04', to: '2026-03-10', timezone: 'Africa/Blantyre', currency: 'MWK', group_by: groupBy,
            summary: { orders: 3, gross_sales: 750000, average_order_value: 250000, delivery_fees: 45000, cancelled: 1 },
            rows: groupBy === 'product'
                ? [{ key: 1, label: 'チキン弁当', quantity: 4, orders: 3, sales: 600000 }]
                : [{ key: '2026-03-10', label: '2026-03-10', orders: 3, subtotal: 700000, delivery_fees: 45000, gross_sales: 750000 }],
        },
    });

    it('shows totals and switches grouping', async () => {
        const api = { get: vi.fn(async (path, params) => (path === '/admin/sales' ? report(params.group_by) : { data: [] })) };
        const wrapper = mountView(SalesView, { api, locale: 'ja' });
        await flushPromises();

        expect(wrapper.find('[data-test="summary-orders"]').text()).toBe('3');
        expect(wrapper.find('[data-test="summary-gross"]').text()).toContain('7,500');
        expect(wrapper.findAll('[data-test="sales-row"]')).toHaveLength(1);

        await wrapper.find('[data-test="group-by"]').setValue('product');
        await flushPromises();
        expect(api.get).toHaveBeenLastCalledWith('/admin/sales', expect.objectContaining({ group_by: 'product' }));
        expect(wrapper.find('[data-test="sales-row"]').text()).toContain('チキン弁当');
    });

    it('reloads server-translated names when the language changes', async () => {
        const api = { get: vi.fn(async (path, params) => (path === '/admin/sales' ? report(params.group_by) : { data: [] })) };
        const wrapper = mountView(SalesView, { api, locale: 'en' });
        await flushPromises();
        const calls = api.get.mock.calls.filter(([p]) => p === '/admin/sales').length;

        wrapper.vm.$i18n.locale = 'ny';
        await flushPromises();
        expect(api.get.mock.calls.filter(([p]) => p === '/admin/sales').length).toBe(calls + 1);
    });
});

describe('SettingsView', () => {
    it('saves overrides in minor units and clears empty values', async () => {
        const settings = [
            { key: 'service_fee', type: 'money', value: null, effective: 0, source: 'DEFAULT' },
            { key: 'delivery_offer_ttl_seconds', type: 'integer', value: 90, effective: 90, source: 'GLOBAL' },
        ];
        const api = {
            get: vi.fn(async () => ({ data: { settings } })),
            put: vi.fn(async (path, body) => ({ data: { settings } })),
        };
        const wrapper = mountView(SettingsView, { api });
        await flushPromises();

        const inputs = wrapper.findAll('[data-test="setting"] input');
        await inputs[0].setValue('50');
        await inputs[1].setValue('');
        await wrapper.find('form').trigger('submit');
        await flushPromises();

        expect(api.put).toHaveBeenCalledWith('/admin/settings', {
            scope_type: 'GLOBAL', scope_id: null, values: { service_fee: 5000, delivery_offer_ttl_seconds: null },
        });
    });
});
