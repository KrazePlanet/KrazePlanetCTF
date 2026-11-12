<?php

define('VAULTTECH_ADMIN_USER', 'vt_admin');
define('VAULTTECH_ADMIN_PASS', 'V@ultT3ch!Ops2026');
define('VAULTTECH_COOKIE_KEY', 'vt_internal_hmac_key_2026_prod');

function vt_sign_session(string $username, string $panel): string
{
    $payload = base64_encode(json_encode([
        'u' => $username,
        'p' => $panel,
        't' => time(),
    ]));
    $sig = hash_hmac('sha256', $payload, VAULTTECH_COOKIE_KEY);
    return $payload . '.' . $sig;
}

function vt_verify_session(string $cookieVal, string $panel): ?string
{
    $parts = explode('.', $cookieVal);
    if (count($parts) !== 2) {
        return null;
    }
    [$payload, $sig] = $parts;
    $expected = hash_hmac('sha256', $payload, VAULTTECH_COOKIE_KEY);
    if (!hash_equals($expected, $sig)) {
        return null;
    }
    $data = json_decode(base64_decode($payload, true) ?: '', true);
    if (!is_array($data) || ($data['p'] ?? '') !== $panel || empty($data['u'])) {
        return null;
    }
    return (string) $data['u'];
}

function vt_handle_panel_auth(string $panel): array
{
    $cookieName = 'vt_sess_' . $panel;

    if (isset($_GET['logout']) && $_GET['logout'] === '1') {
        setcookie($cookieName, '', time() - 3600, '/', '', false, true);
        return ['authenticated' => false, 'user' => null, 'error' => ''];
    }

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $u = trim((string) ($_POST['username'] ?? ''));
        $p = (string) ($_POST['password'] ?? '');
        if ($u === VAULTTECH_ADMIN_USER && hash_equals(VAULTTECH_ADMIN_PASS, $p)) {
            $token = vt_sign_session($u, $panel);
            setcookie($cookieName, $token, 0, '/', '', false, true);
            return ['authenticated' => true, 'user' => $u, 'error' => ''];
        }
        return ['authenticated' => false, 'user' => null, 'error' => 'Invalid username or password.'];
    }

    if (!empty($_COOKIE[$cookieName]) && is_string($_COOKIE[$cookieName])) {
        $user = vt_verify_session($_COOKIE[$cookieName], $panel);
        if ($user !== null) {
            return ['authenticated' => true, 'user' => $user, 'error' => ''];
        }
    }

    return ['authenticated' => false, 'user' => null, 'error' => ''];
}

