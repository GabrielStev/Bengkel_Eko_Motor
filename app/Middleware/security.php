<?php
declare(strict_types=1);

function startSession(): void
{
    ini_set('session.use_strict_mode', '1');
    ini_set('session.use_only_cookies', '1');
    session_name('bengkel_session');
    session_set_cookie_params([
        'path' => '/',
        'httponly' => true,
        'secure' => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
        'samesite' => 'Lax',
    ]);
    session_start();
    header('Cache-Control: no-store');
    header('X-Content-Type-Options: nosniff');
    header("Content-Security-Policy: default-src 'self'; frame-ancestors 'none'; base-uri 'none'; form-action 'self'");
    $_SESSION['csrf'] ??= bin2hex(random_bytes(32));
}

function logout(): void
{
    $_SESSION = [];
    setcookie(session_name(), '', [
        'expires' => time() - 3600, 'path' => '/', 'httponly' => true,
        'secure' => session_get_cookie_params()['secure'], 'samesite' => 'Lax',
    ]);
    session_destroy();
}

function currentUser(): array|false
{
    if (!isset($_SESSION['user'])) {
        return false;
    }
    if (time() - $_SESSION['last'] >= 1800) {
        logout();
        redirect('login');
    }
    $user = row("SELECT id_pengguna,nama_lengkap,peran FROM pengguna WHERE id_pengguna=? AND status_aktif='Aktif'", [$_SESSION['user']]);
    if (!$user) {
        logout();
        redirect('login');
    }
    $_SESSION['last'] = time();
    return $user;
}

function verifyCsrf(): void
{
    if (!hash_equals($_SESSION['csrf'], (string) ($_POST['csrf'] ?? ''))) {
        fail(403, 'Formulir kedaluwarsa. Buka kembali halaman.');
    }
}

function authenticate(string $username, string $password): bool
{
    $key = hash('sha256', (string) ($_SERVER['REMOTE_ADDR'] ?? 'local'));
    $id = atomic(function () use ($key, $username, $password): int|false {
        query("INSERT IGNORE INTO percobaan_login VALUES (?,0,DATE_ADD(NOW(),INTERVAL 15 MINUTE))", [$key]);
        $attempt = row("SELECT gagal,kedaluwarsa<=NOW() AS lampau FROM percobaan_login WHERE kunci=? FOR UPDATE", [$key]);
        if (!$attempt['lampau'] && $attempt['gagal'] >= 8) {
            return false;
        }
        if ($attempt['lampau']) {
            query("UPDATE percobaan_login SET gagal=0,kedaluwarsa=DATE_ADD(NOW(),INTERVAL 15 MINUTE) WHERE kunci=?", [$key]);
        }
        $account = row('SELECT id_pengguna,password_hash,status_aktif FROM pengguna WHERE username=?', [$username]);
        $hash = $account['password_hash'] ?? '';
        if (!password_get_info($hash)['algo']) {
            $hash = '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2uheWG/igi.';
            $account = false;
        }
        $valid = password_verify($password, $hash);
        if (!$valid || !$account || $account['status_aktif'] !== 'Aktif') {
            query('UPDATE percobaan_login SET gagal=gagal+1 WHERE kunci=?', [$key]);
            return false;
        }
        if (password_needs_rehash($hash, PASSWORD_DEFAULT)) {
            query('UPDATE pengguna SET password_hash=? WHERE id_pengguna=? AND password_hash=?',
                [password_hash($password, PASSWORD_DEFAULT), $account['id_pengguna'], $hash]);
        }
        query('DELETE FROM percobaan_login WHERE kunci=?', [$key]);
        return (int) $account['id_pengguna'];
    });
    if (!$id) {
        return false;
    }
    session_regenerate_id(true);
    $_SESSION = ['user' => $id, 'last' => time(), 'csrf' => bin2hex(random_bytes(32))];
    return true;
}
