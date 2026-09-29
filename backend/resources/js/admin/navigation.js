import { reactive } from 'vue';
import { RESOURCES } from './resources';

/**
 * Sidebar sections. A page is shown only when the signed-in user has its permission, so
 * the same app serves platform, franchise and store staff (the API enforces it again).
 * Labels: t(`admin.nav.<id>`) and t(`admin.nav_groups.<group>`).
 */
export const PAGES = [
    { id: 'dashboard', group: 'overview', permission: 'orders.view' },
    { id: 'orders', group: 'overview', permission: 'orders.view' },
    { id: 'sales', group: 'overview', permission: 'sales.view' },
    { id: 'products', group: 'catalog', permission: RESOURCES.products.view, resource: 'products' },
    { id: 'categories', group: 'catalog', permission: RESOURCES.categories.view, resource: 'categories' },
    { id: 'store_products', group: 'catalog', permission: 'store_products.manage' },
    { id: 'stores', group: 'operations', permission: RESOURCES.stores.view, resource: 'stores' },
    { id: 'kitchens', group: 'operations', permission: RESOURCES.kitchens.view, resource: 'kitchens' },
    { id: 'delivery_zones', group: 'operations', permission: RESOURCES.delivery_zones.view, resource: 'delivery_zones' },
    { id: 'drivers', group: 'operations', permission: RESOURCES.drivers.view, resource: 'drivers' },
    { id: 'franchises', group: 'business', permission: RESOURCES.franchises.view, resource: 'franchises' },
    { id: 'staff', group: 'business', permission: RESOURCES.staff.view, resource: 'staff' },
    { id: 'customers', group: 'business', permission: 'customers.view' },
    { id: 'translations', group: 'system', permission: 'translations.manage' },
    { id: 'languages', group: 'system', permission: RESOURCES.languages.view, resource: 'languages' },
    { id: 'settings', group: 'system', permission: 'settings.manage' },
    { id: 'audit_logs', group: 'system', permission: 'audit_logs.view' },
];

export const GROUPS = ['overview', 'catalog', 'operations', 'business', 'system'];

export function can(user, permission) {
    return !permission || (user?.permissions ?? []).includes(permission);
}

export function visiblePages(user) {
    return PAGES.filter((p) => can(user, p.permission));
}

/**
 * "#/products/12" → { page: 'products', id: '12', query: {} }
 * "#/orders?status=NEW" → { page: 'orders', id: null, query: { status: 'NEW' } }
 */
export function parseHash(hash) {
    const [path, search = ''] = (hash ?? '').replace(/^#\/?/, '').split('?');
    const [page = '', id = null] = path.split('/').filter(Boolean);
    return { page, id, query: Object.fromEntries(new URLSearchParams(search)) };
}

export function href(page, id = null) {
    return id === null || id === undefined ? `#/${page}` : `#/${page}/${id}`;
}

/** Reactive current route, updated on hashchange. */
export function createRouter(win = window) {
    const route = reactive(parseHash(win.location.hash));
    win.addEventListener('hashchange', () => Object.assign(route, parseHash(win.location.hash)));
    return {
        route,
        go(page, id = null) {
            win.location.hash = href(page, id);
        },
    };
}
