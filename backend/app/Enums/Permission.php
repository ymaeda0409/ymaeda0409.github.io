<?php

namespace App\Enums;

/**
 * Fine-grained capabilities. Code checks permissions, never role names,
 * so the role → permission mapping can evolve without touching call sites.
 */
enum Permission: string
{
    case ORGANIZATIONS_MANAGE = 'organizations.manage';
    case FRANCHISES_VIEW = 'franchises.view';
    case FRANCHISES_MANAGE = 'franchises.manage';
    case STORES_VIEW = 'stores.view';
    case STORES_CREATE = 'stores.create';
    case STORES_UPDATE = 'stores.update';
    case KITCHENS_VIEW = 'kitchens.view';
    case KITCHENS_MANAGE = 'kitchens.manage';
    case DELIVERY_ZONES_VIEW = 'delivery_zones.view';
    case DELIVERY_ZONES_MANAGE = 'delivery_zones.manage';
    case CATALOG_VIEW = 'catalog.view';
    case CATALOG_MANAGE = 'catalog.manage';
    case STORE_PRODUCTS_MANAGE = 'store_products.manage';
    case LANGUAGES_MANAGE = 'languages.manage';
    case TRANSLATIONS_MANAGE = 'translations.manage';
    case AUDIT_LOGS_VIEW = 'audit_logs.view';
    case ORDERS_VIEW = 'orders.view';
    case KITCHEN_OPERATE = 'kitchen.operate';
    case DRIVERS_MANAGE = 'drivers.manage';
    case SALES_VIEW = 'sales.view';
    case DELIVERY_OPERATE = 'delivery.operate';
    case CUSTOMER_ORDER = 'customer.order';
}
