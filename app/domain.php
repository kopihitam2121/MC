<?php
declare(strict_types=1);

function mc_calculate_spread_rate(float $buyRate, float $sellRate): float
{
    if (!is_finite($buyRate) || !is_finite($sellRate)) return 0.0;
    return round($sellRate - $buyRate, 4);
}

function mc_calculate_profit(float $amount, float $buyRate, float $sellRate): float
{
    if (!is_finite($amount) || !is_finite($buyRate) || !is_finite($sellRate) || $amount < 0) return 0.0;
    return round(($sellRate - $buyRate) * $amount, 2);
}

function mc_valid_datetime_local(string $value): bool
{
    foreach (['!Y-m-d\TH:i', '!Y-m-d\TH:i:s', '!Y-m-d H:i:s', '!Y-m-d H:i'] as $format) {
        $date = DateTime::createFromFormat($format, $value);
        $errors = DateTime::getLastErrors();
        if ($date instanceof DateTime && ($errors === false || ($errors['warning_count'] === 0 && $errors['error_count'] === 0))) {
            $expected = str_starts_with($format, '!Y-m-d\TH:i:s') ? $date->format('Y-m-d\TH:i:s')
                : (str_starts_with($format, '!Y-m-d\TH:i') ? $date->format('Y-m-d\TH:i')
                : (str_ends_with($format, 'H:i:s') ? $date->format('Y-m-d H:i:s') : $date->format('Y-m-d H:i')));
            if ($expected === $value) return true;
        }
    }
    return false;
}

function mc_normalize_datetime_local(mixed $value): string
{
    $value = trim((string) $value);
    if ($value === '') return '';
    foreach (['!Y-m-d\TH:i', '!Y-m-d\TH:i:s', '!Y-m-d H:i:s', '!Y-m-d H:i'] as $format) {
        $date = DateTime::createFromFormat($format, $value);
        $errors = DateTime::getLastErrors();
        if ($date instanceof DateTime && ($errors === false || ($errors['warning_count'] === 0 && $errors['error_count'] === 0))) {
            return $date->format('Y-m-d H:i:s');
        }
    }
    if (mc_valid_date($value)) return $value . ' 00:00:00';
    return '';
}

function mc_valid_nonnegative_decimal(mixed $value): bool
{
    if (!is_numeric($value)) return false;
    $number = (float) $value;
    return is_finite($number) && $number >= 0;
}

function mc_calculate_idr(float $amount, float $rate): float
{
    if (!is_finite($amount) || !is_finite($rate) || $amount < 0 || $rate < 0) {
        return 0.0;
    }
    return round($amount * $rate, 2);
}

function mc_valid_positive_decimal(mixed $value): bool
{
    if (!is_numeric($value)) {
        return false;
    }
    $number = (float) $value;
    return is_finite($number) && $number > 0;
}

function mc_spreadsheet_safe(string $value): string
{
    $trimmed = ltrim($value);
    if ($trimmed !== '' && preg_match('/^[=+\-@\t\r]/', $trimmed) === 1) {
        return "'" . $value;
    }
    return $value;
}

function mc_valid_date(string $value): bool
{
    $date = DateTime::createFromFormat('!Y-m-d', $value);
    return $date instanceof DateTime && $date->format('Y-m-d') === $value;
}

function mc_rbac_permission_decision(bool $enabled, string $role, string $permission, bool $allowed, array $sensitivePermissions): bool
{
    $role = strtolower(trim($role));
    if ($role === 'admin') {
        return true;
    }
    if (!$enabled) {
        return !in_array($permission, $sensitivePermissions, true);
    }
    return $allowed;
}


function mc_notification_role_matches(string $recipientRole, string $userRole): bool
{
    $recipientRole = strtolower(trim($recipientRole));
    $userRole = strtolower(trim($userRole));
    return $recipientRole === 'all' || ($recipientRole !== '' && $recipientRole === $userRole);
}

function mc_is_administrator(?array $user): bool
{
    return strtolower(trim((string) ($user['role'] ?? ''))) === 'admin';
}

function mc_can_manage_notifications(?array $user): bool
{
    return in_array(strtolower(trim((string) ($user['role'] ?? ''))), ['admin', 'pic'], true);
}
