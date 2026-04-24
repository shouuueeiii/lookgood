<?php
if (!function_exists('lg_normalize_session_scope')) {
    function lg_normalize_session_scope(string $scope): string {
        $scope = strtolower(trim($scope));
        return ($scope === 'admin') ? 'admin' : 'user';
    }
}

if (!function_exists('lg_session_name_for_scope')) {
    function lg_session_name_for_scope(string $scope): string {
        $scope = lg_normalize_session_scope($scope);
        return ($scope === 'admin') ? 'LG_ADMIN_SESSID' : 'LG_USER_SESSID';
    }
}

if (!function_exists('lg_is_https')) {
    function lg_is_https(): bool {
        return (
            (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ||
            (isset($_SERVER['SERVER_PORT']) && (int)$_SERVER['SERVER_PORT'] === 443)
        );
    }
}

if (!function_exists('lg_redirect_to_canonical_host_if_needed')) {
    function lg_redirect_to_canonical_host_if_needed(): void {
        $enforceCanonical = defined('LG_ENFORCE_CANONICAL_HOST') && constant('LG_ENFORCE_CANONICAL_HOST') === true;
        if (!$enforceCanonical) return;

        if (headers_sent()) return;
        if (PHP_SAPI === 'cli') return;

        // Optional canonical-host enforcement to avoid split-cookie sessions.
        // Disabled by default to avoid forcing host switches for existing sessions.
        $host = (string)($_SERVER['HTTP_HOST'] ?? '');
        $hostNoPort = preg_replace('/:\\d+$/', '', $host);
        if ($hostNoPort !== '127.0.0.1') return;

        $port = '';
        if (strpos($host, ':') !== false) {
            $parts = explode(':', $host, 2);
            $port = isset($parts[1]) && $parts[1] !== '' ? ':' . $parts[1] : '';
        }

        $scheme = lg_is_https() ? 'https' : 'http';
        $uri = (string)($_SERVER['REQUEST_URI'] ?? '/');
        header('Location: ' . $scheme . '://localhost' . $port . $uri, true, 302);
        exit();
    }
}

if (!function_exists('lg_start_scoped_session')) {
    function lg_start_scoped_session(string $scope = 'user'): string {
        $scope = lg_normalize_session_scope($scope);
        $targetSessionName = lg_session_name_for_scope($scope);

        if (session_status() === PHP_SESSION_ACTIVE && session_name() !== $targetSessionName) {
            session_write_close();
        }

        if (session_status() !== PHP_SESSION_ACTIVE) {
            ini_set('session.use_strict_mode', '1');
            ini_set('session.gc_maxlifetime', (string)(60 * 60 * 24 * 7));

            session_name($targetSessionName);
            session_set_cookie_params([
                'lifetime' => 60 * 60 * 24 * 7,
                'path' => '/',
                'domain' => '',
                'secure' => lg_is_https(),
                'httponly' => true,
                'samesite' => 'Lax',
            ]);

            session_start();
        }

        $_SESSION['__scope'] = $scope;
        return $scope;
    }
}

if (!function_exists('lg_switch_session_scope')) {
    function lg_switch_session_scope(string $scope): string {
        return lg_start_scoped_session($scope);
    }
}

if (!function_exists('lg_destroy_scoped_session')) {
    function lg_destroy_scoped_session(string $scope): void {
        $scope = lg_normalize_session_scope($scope);
        $targetSessionName = lg_session_name_for_scope($scope);

        if (session_status() === PHP_SESSION_ACTIVE && session_name() !== $targetSessionName) {
            session_write_close();
        }

        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_name($targetSessionName);
            session_set_cookie_params([
                'lifetime' => 60 * 60 * 24 * 7,
                'path' => '/',
                'domain' => '',
                'secure' => lg_is_https(),
                'httponly' => true,
                'samesite' => 'Lax',
            ]);
            session_start();
        }

        $_SESSION = [];

        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(
                session_name(),
                '',
                time() - 42000,
                $params['path'],
                $params['domain'],
                (bool)$params['secure'],
                (bool)$params['httponly']
            );
        }

        session_destroy();
    }
}

lg_redirect_to_canonical_host_if_needed();

$defaultScope = defined('LG_SESSION_SCOPE') ? (string)LG_SESSION_SCOPE : 'user';
lg_start_scoped_session($defaultScope);
