<?php
declare(strict_types=1);

final class Rbac
{
    public static function enabled(): bool
    {
        $rules = require dirname(__DIR__) . '/config/configrules.php';
        return mc_setting_bool('rbac_enabled', (bool) $rules['rbac_enabled_default']);
    }

    public static function can(string $permission, ?array $user = null): bool
    {
        $user = $user ?? Auth::user();
        if (!$user) return false;
        $role = strtolower(trim((string) ($user['role'] ?? '')));
        $rules = require dirname(__DIR__) . '/config/configrules.php';
        $enabled = self::enabled();
        if ($role === 'admin') return true;
        if (!$enabled) {
            return mc_rbac_permission_decision(false, $role, $permission, false, $rules['sensitive_permissions']);
        }

        $stmt = Database::connection()->prepare('SELECT allowed FROM role_permissions WHERE role = ? AND permission_key = ? LIMIT 1');
        $stmt->execute([$role, $permission]);
        $allowed = (int) ($stmt->fetchColumn() ?: 0) === 1;
        return mc_rbac_permission_decision(true, $role, $permission, $allowed, $rules['sensitive_permissions']);
    }

    public static function canEditTransaction(array $transaction, array $user): bool
    {
        if (!self::can('transactions.update', $user)) return false;
        $role = strtolower(trim((string) ($user['role'] ?? '')));
        if (in_array($role, ['admin', 'admin_money_changer', 'supervisor', 'pic'], true)) {
            return true;
        }
        return (int) ($transaction['created_by'] ?? 0) === (int) ($user['id'] ?? 0);
    }

    public static function canDeleteTransaction(array $transaction, array $user): bool
    {
        if (!self::can('transactions.delete', $user)) return false;
        $role = strtolower(trim((string) ($user['role'] ?? '')));
        return in_array($role, ['admin', 'admin_money_changer'], true);
    }
}
