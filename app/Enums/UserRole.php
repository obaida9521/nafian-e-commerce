<?php

namespace App\Enums;

enum UserRole: string
{
    case SuperAdmin = 'superadmin';
    case Admin = 'admin';
    case Manager = 'manager';
    case Viewer = 'viewer';

    public function label(): string
    {
        return match ($this) {
            self::SuperAdmin => 'Super Admin',
            self::Admin => 'Admin',
            self::Manager => 'Manager',
            self::Viewer => 'Viewer',
        };
    }

    /**
     * Whether this role may perform write actions in a given admin area.
     * Read (GET) access to every area is granted to all roles separately.
     */
    public function canManage(string $area): bool
    {
        // Normalise nested/shallow areas to their parent.
        $area = $area === 'variants' ? 'products' : $area;

        return match ($this) {
            self::SuperAdmin, self::Admin => true,
            self::Manager => in_array($area, ['orders', 'inventory', 'customers', 'pos'], true),
            self::Viewer => false,
        };
    }
}
