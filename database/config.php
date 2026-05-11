<?php
// ==============================================
//  COWASCO Waters — Database Configuration
// ==============================================

define('DB_HOST',     'localhost');
define('DB_PORT',     '3306');
define('DB_NAME',     'mnssmc');
define('DB_USER',     'root');        // ← change in production
define('DB_PASS',     'v1i1n1x1');            // ← change in production
define('DB_CHARSET',  'utf8mb4');

define('APP_NAME',    'COWASCO Waters');
define('APP_URL',     'http://localhost');  // ← change in production

// Session settings
define('SESSION_LIFETIME',    86400);   // 24 hours
define('REMEMBER_ME_DAYS',    30);      // remember-me cookie duration
define('PASSWORD_RESET_TTL',  3600);    // 1 hour token validity

// ==============================================
//  PDO Connection — singleton-style
// ==============================================

function getDB(): PDO
{
    static $pdo = null;

    if ($pdo === null) {
        $dsn = sprintf(
            'mysql:host=%s;port=%s;dbname=%s;charset=%s',
            DB_HOST, DB_PORT, DB_NAME, DB_CHARSET
        );

        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ];

        try {
            $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
        } catch (PDOException $e) {
            // In production, log the error; never expose it to the user
            error_log('[COWASCO Waters] DB connection failed: ' . $e->getMessage());
            http_response_code(503);
            exit(json_encode(['error' => 'Service temporarily unavailable.']));
        }
    }

    return $pdo;
}

// ==============================================
//  Session bootstrap
// ==============================================

if (session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params([
        'lifetime' => SESSION_LIFETIME,
        'path'     => '/',
        'secure'   => isset($_SERVER['HTTPS']),
        'httponly' => true,
        'samesite' => 'Strict',
    ]);
    session_start();
}

// ==============================================
//  Helpers
// ==============================================

function isLoggedIn(): bool
{
    return !empty($_SESSION['user_id']);
}

function requireLogin(): void
{
    if (!isLoggedIn()) {
        header('Location: login.php');
        exit;
    }
}

function requireGuest(): void
{
    if (isLoggedIn()) {
        header('Location: dashboard.php');
        exit;
    }
}

function redirect(string $url, string $message = '', string $type = 'info'): void
{
    if ($message !== '') {
        $_SESSION['flash'] = ['msg' => $message, 'type' => $type];
    }
    header("Location: $url");
    exit;
}

function flash(): array
{
    $f = $_SESSION['flash'] ?? [];
    unset($_SESSION['flash']);
    return $f;
}

function e(string $val): string
{
    return htmlspecialchars($val, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function csrfToken(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function verifyCsrf(string $token): bool
{
    return hash_equals($_SESSION['csrf_token'] ?? '', $token);
}