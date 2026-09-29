<?php

namespace App\Enums;

enum UserRole: string
{
    case SUPER_ADMIN = 'SUPER_ADMIN';
    case FRANCHISE_ADMIN = 'FRANCHISE_ADMIN';
    case STORE_MANAGER = 'STORE_MANAGER';
    case KITCHEN_STAFF = 'KITCHEN_STAFF';
    case DRIVER = 'DRIVER';
    case CUSTOMER = 'CUSTOMER';

    /**
     * @return list<Permission>
     */
    public function permissions(): array
    {
        return match ($this) {
            self::SUPER_ADMIN => Permission::cases(),
            self::FRANCHISE_ADMIN => [
                Permission::FRANCHISES_VIEW,
                Permission::STORES_VIEW,
                Permission::STORES_UPDATE,
                Permission::KITCHENS_VIEW,
                Permission::KITCHENS_MANAGE,
                Permission::DELIVERY_ZONES_VIEW,
                Permission::DELIVERY_ZONES_MANAGE,
                Permission::CATALOG_VIEW,
                Permission::STORE_PRODUCTS_MANAGE,
                Permission::AUDIT_LOGS_VIEW,
                Permission::ORDERS_VIEW,
                Permission::KITCHEN_OPERATE,
                Permission::DRIVERS_MANAGE,
                Permission::SALES_VIEW,
                Permission::STAFF_MANAGE,
                Permission::CUSTOMERS_VIEW,
                Permission::SETTINGS_MANAGE,
            ],
            self::STORE_MANAGER => [
                Permission::STORES_VIEW,
                Permission::STORES_UPDATE,
                Permission::KITCHENS_VIEW,
                Permission::DELIVERY_ZONES_VIEW,
                Permission::DELIVERY_ZONES_MANAGE,
                Permission::CATALOG_VIEW,
                Permission::STORE_PRODUCTS_MANAGE,
                Permission::ORDERS_VIEW,
                Permission::KITCHEN_OPERATE,
                Permission::DRIVERS_MANAGE,
                Permission::SALES_VIEW,
                Permission::STAFF_MANAGE,
                Permission::CUSTOMERS_VIEW,
            ],
            self::KITCHEN_STAFF => [
                Permission::STORES_VIEW,
                Permission::KITCHENS_VIEW,
                Permission::CATALOG_VIEW,
                Permission::ORDERS_VIEW,
                Permission::KITCHEN_OPERATE,
            ],
            self::DRIVER => [Permission::DELIVERY_OPERATE],
            self::CUSTOMER => [Permission::CUSTOMER_ORDER],
        };
    }

    public function hasPermission(Permission $permission): bool
    {
        return in_array($permission, $this->permissions(), true);
    }

    /**
     * The tenant level a staff role is pinned to. Null means unrestricted (platform level)
     * or not applicable (customer / driver data is scoped by ownership instead).
     */
    public function tenantLevel(): ?TenantLevel
    {
        return match ($this) {
            self::SUPER_ADMIN => null,
            self::FRANCHISE_ADMIN => TenantLevel::FRANCHISE,
            self::STORE_MANAGER, self::KITCHEN_STAFF => TenantLevel::STORE,
            self::DRIVER, self::CUSTOMER => TenantLevel::NONE,
        };
    }

    /**
     * Staff roles this role may create and manage (always inside its own tenant scope).
     *
     * @return list<self>
     */
    public function manageableRoles(): array
    {
        return match ($this) {
            self::SUPER_ADMIN => [self::SUPER_ADMIN, self::FRANCHISE_ADMIN, self::STORE_MANAGER, self::KITCHEN_STAFF],
            self::FRANCHISE_ADMIN => [self::STORE_MANAGER, self::KITCHEN_STAFF],
            self::STORE_MANAGER => [self::KITCHEN_STAFF],
            default => [],
        };
    }

    public function isStaff(): bool
    {
        return in_array($this, [self::SUPER_ADMIN, self::FRANCHISE_ADMIN, self::STORE_MANAGER, self::KITCHEN_STAFF], true);
    }
}
