<?php
declare(strict_types=1);

final class Auth
{
    public static function bootSession(): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) return;
        session_name('money_changer_session');
        session_set_cookie_params([
            'lifetime' => 0,
            'path' => '/',
            'secure' => (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off'),
            'httponly' => true,
            'samesite' => 'Lax',
        ]);
        session_start();
    }

    public static function login(string $username, string $password): bool
    {
        $username = strtolower(trim($username));
        if ($username === '' || $password === '') return false;
        $stmt = Database::connection()->prepare('SELECT id, password_hash, is_active FROM users WHERE username = ? LIMIT 1');
        $stmt->execute([$username]);
        $row = $stmt->fetch();
        if (!$row || (int) $row['is_active'] !== 1 || !password_verify($password, (string) $row['password_hash'])) {
            return false;
        }
        session_regenerate_id(true);
        $_SESSION['mc_user_id'] = (int) $row['id'];
        $_SESSION['mc_login_at'] = time();
        $update = Database::connection()->prepare('UPDATE users SET last_login = NOW() WHERE id = ?');
        $update->execute([(int) $row['id']]);
        mc_audit('login', 'users', (int) $row['id']);
        return true;
    }

    public static function user(): ?array
    {
        $id = (int) ($_SESSION['mc_user_id'] ?? 0);
        if ($id <= 0) return null;
        $stmt = Database::connection()->prepare('SELECT id, full_name, username, role, is_active, must_change_password, last_login, created_at, profile_photo FROM users WHERE id = ? LIMIT 1');
        $stmt->execute([$id]);
        $user = $stmt->fetch();
        if (!$user || (int) $user['is_active'] !== 1) {
            self::logout(false);
            return null;
        }
        return $user;
    }

    public static function requireLogin(): array
    {
        $user = self::user();
        if (!$user) mc_redirect('login');
        return $user;
    }

    public static function logout(bool $recordAudit = true): void
    {
        $id = (int) ($_SESSION['mc_user_id'] ?? 0);
        if ($recordAudit && $id > 0) mc_audit('logout', 'users', $id);
        $_SESSION = [];
        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'] ?? '', (bool) $params['secure'], (bool) $params['httponly']);
        }
        if (session_status() === PHP_SESSION_ACTIVE) session_destroy();
    }
}
