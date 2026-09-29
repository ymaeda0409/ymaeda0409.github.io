/**
 * CRUD screens described as data. Labels are never written here: a field `name` is shown
 * with t(`admin.fields.<name>`), enum values with t(`admin.enums.<enum>.<value>`).
 *
 * Field types: text, email, tel, password, number, decimal, money, boolean, select,
 * relation (id picked from another endpoint), hours (weekly opening hours), translations,
 * option_groups (product options editor).
 */
export const RESOURCES = {
    franchises: {
        endpoint: '/admin/franchises',
        view: 'franchises.view',
        manage: 'franchises.manage',
        columns: ['code', 'name', 'region', 'status', 'commission_rate'],
        fields: [
            { name: 'code', type: 'text', required: true },
            { name: 'name', type: 'text', required: true },
            { name: 'region', type: 'text', required: true },
            { name: 'owner_name', type: 'text' },
            { name: 'phone', type: 'tel' },
            { name: 'email', type: 'email' },
            { name: 'status', type: 'select', enum: 'franchise_status', options: ['PENDING', 'ACTIVE', 'SUSPENDED'] },
            { name: 'commission_rate', type: 'decimal', step: '0.01' },
        ],
        defaults: { status: 'ACTIVE', commission_rate: 0 },
    },
    stores: {
        endpoint: '/admin/stores',
        view: 'stores.view',
        manage: 'stores.update',
        create: 'stores.create',
        columns: ['code', 'name', 'city', 'is_open', 'is_accepting_orders', 'is_active'],
        fields: [
            { name: 'franchise_id', type: 'relation', source: 'franchises', required: true, createOnly: true, permission: 'stores.create' },
            { name: 'code', type: 'text', required: true },
            { name: 'name', type: 'text', required: true },
            { name: 'city', type: 'text', required: true },
            { name: 'address', type: 'text' },
            { name: 'phone', type: 'tel' },
            { name: 'email', type: 'email' },
            { name: 'latitude', type: 'decimal', step: 'any', required: true },
            { name: 'longitude', type: 'decimal', step: 'any', required: true },
            { name: 'timezone', type: 'text' },
            { name: 'is_accepting_orders', type: 'boolean' },
            { name: 'is_active', type: 'boolean' },
            { name: 'opening_hours', type: 'hours' },
            { name: 'translations', type: 'translations', attributes: ['description', 'announcement'], multiline: true },
        ],
        defaults: { is_active: true, is_accepting_orders: true, timezone: 'Africa/Blantyre' },
    },
    kitchens: {
        endpoint: '/admin/kitchens',
        view: 'kitchens.view',
        manage: 'kitchens.manage',
        columns: ['name', 'store_id', 'is_active'],
        fields: [
            { name: 'store_id', type: 'relation', source: 'stores', required: true, createOnly: true },
            { name: 'name', type: 'text', required: true },
            { name: 'latitude', type: 'decimal', step: 'any' },
            { name: 'longitude', type: 'decimal', step: 'any' },
            { name: 'is_active', type: 'boolean' },
        ],
        defaults: { is_active: true },
    },
    delivery_zones: {
        endpoint: '/admin/delivery-zones',
        view: 'delivery_zones.view',
        manage: 'delivery_zones.manage',
        columns: ['name', 'store_id', 'base_fee', 'max_delivery_distance_km', 'is_active'],
        fields: [
            { name: 'store_id', type: 'relation', source: 'stores', required: true, createOnly: true },
            { name: 'kitchen_id', type: 'relation', source: 'kitchens' },
            { name: 'name', type: 'text', required: true },
            { name: 'base_fee', type: 'money', required: true },
            { name: 'base_distance_km', type: 'decimal', step: '0.1', required: true },
            { name: 'additional_fee_per_km', type: 'money', required: true },
            { name: 'max_delivery_distance_km', type: 'decimal', step: '0.1', required: true },
            { name: 'is_active', type: 'boolean' },
        ],
        defaults: { is_active: true, base_fee: 0, base_distance_km: 3, additional_fee_per_km: 0, max_delivery_distance_km: 10 },
    },
    categories: {
        endpoint: '/admin/categories',
        view: 'catalog.view',
        manage: 'catalog.manage',
        columns: ['translations', 'code', 'sort_order', 'is_active'],
        titleField: 'translations',
        fields: [
            { name: 'code', type: 'text', required: true },
            { name: 'translations', type: 'translations', attributes: ['name'], required: ['name'] },
            { name: 'image_url', type: 'text' },
            { name: 'sort_order', type: 'number' },
            { name: 'is_active', type: 'boolean' },
        ],
        defaults: { is_active: true, sort_order: 0 },
    },
    products: {
        endpoint: '/admin/products',
        view: 'catalog.view',
        manage: 'catalog.manage',
        columns: ['translations', 'sku', 'category_id', 'price', 'is_featured', 'is_active'],
        titleField: 'translations',
        fields: [
            { name: 'sku', type: 'text', required: true },
            { name: 'category_id', type: 'relation', source: 'categories', required: true },
            { name: 'translations', type: 'translations', attributes: ['name', 'description'], required: ['name'], multiline: true },
            { name: 'price', type: 'money', required: true },
            { name: 'preparation_minutes', type: 'number' },
            { name: 'image_url', type: 'text' },
            { name: 'sort_order', type: 'number' },
            { name: 'is_featured', type: 'boolean' },
            { name: 'is_active', type: 'boolean' },
            { name: 'option_groups', type: 'option_groups' },
        ],
        defaults: { is_active: true, is_featured: false, preparation_minutes: 15, sort_order: 0, price: 0, option_groups: [] },
    },
    drivers: {
        endpoint: '/admin/drivers',
        view: 'drivers.manage',
        manage: 'drivers.manage',
        columns: ['name', 'phone', 'vehicle_type', 'is_online', 'status'],
        fields: [
            { name: 'phone', type: 'tel', required: true, createOnly: true },
            { name: 'name', type: 'text', required: true },
            { name: 'franchise_id', type: 'relation', source: 'franchises', required: true, createOnly: true, platformOnly: true },
            { name: 'store_id', type: 'relation', source: 'stores' },
            { name: 'preferred_language', type: 'language' },
            { name: 'vehicle_type', type: 'select', enum: 'vehicle_type', options: ['MOTORBIKE', 'BICYCLE', 'CAR'], required: true },
            { name: 'vehicle_number', type: 'text' },
            { name: 'status', type: 'select', enum: 'driver_status', options: ['ACTIVE', 'SUSPENDED'] },
        ],
        defaults: { vehicle_type: 'MOTORBIKE', preferred_language: 'en', status: 'ACTIVE' },
        noDelete: true,
    },
    staff: {
        endpoint: '/admin/staff',
        view: 'staff.manage',
        manage: 'staff.manage',
        columns: ['name', 'email', 'role', 'store_id', 'is_active'],
        fields: [
            { name: 'name', type: 'text', required: true },
            { name: 'email', type: 'email', required: true },
            { name: 'password', type: 'password', requiredOnCreate: true },
            { name: 'role', type: 'role', enum: 'role', required: true },
            { name: 'franchise_id', type: 'relation', source: 'franchises', showIf: (v) => v.role === 'FRANCHISE_ADMIN' },
            { name: 'store_id', type: 'relation', source: 'stores', showIf: (v) => ['STORE_MANAGER', 'KITCHEN_STAFF'].includes(v.role) },
            { name: 'preferred_language', type: 'language' },
            { name: 'is_active', type: 'boolean' },
        ],
        defaults: { role: 'KITCHEN_STAFF', preferred_language: 'en', is_active: true },
        noDelete: true,
    },
    languages: {
        endpoint: '/admin/languages',
        view: 'languages.manage',
        manage: 'languages.manage',
        columns: ['code', 'name', 'native_name', 'is_default', 'is_active'],
        fields: [
            { name: 'code', type: 'text', required: true, createOnly: true },
            { name: 'name', type: 'text', required: true },
            { name: 'native_name', type: 'text', required: true },
            { name: 'direction', type: 'select', enum: 'direction', options: ['ltr', 'rtl'] },
            { name: 'sort_order', type: 'number' },
            { name: 'is_default', type: 'boolean' },
            { name: 'is_active', type: 'boolean' },
        ],
        defaults: { direction: 'ltr', is_active: true, is_default: false, sort_order: 0 },
        noDelete: true,
    },
};

/** Roles a signed-in role may assign (mirrors UserRole::manageableRoles on the server). */
export const MANAGEABLE_ROLES = {
    SUPER_ADMIN: ['SUPER_ADMIN', 'FRANCHISE_ADMIN', 'STORE_MANAGER', 'KITCHEN_STAFF'],
    FRANCHISE_ADMIN: ['STORE_MANAGER', 'KITCHEN_STAFF'],
    STORE_MANAGER: ['KITCHEN_STAFF'],
};

/**
 * Text of a translations object in the UI language, else English, else any language.
 */
export function translated(translations, attribute, locale, fallback = 'en') {
    if (!translations) return '';
    const pick = (code) => translations[code]?.[attribute];
    return pick(locale) || pick(fallback) || Object.values(translations).map((t) => t?.[attribute]).find(Boolean) || '';
}

/** Payload for create/update: only declared fields, money already in minor units. */
export function toPayload(resource, values, { creating }) {
    const payload = {};
    for (const field of resource.fields) {
        if (!creating && field.createOnly) continue;
        if (field.showIf && !field.showIf(values)) continue;
        let value = values[field.name];
        if (value === undefined) continue;
        if (field.type === 'password' && !value) continue;
        if (['number', 'decimal', 'money', 'relation'].includes(field.type)) {
            value = value === '' || value === null ? null : Number(value);
        }
        if (['text', 'tel', 'email'].includes(field.type) && value === '') value = null;
        payload[field.name] = value;
    }
    return payload;
}
