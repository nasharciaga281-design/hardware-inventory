<?php

if (session_status() !== PHP_SESSION_ACTIVE) {
    ini_set('session.use_strict_mode', '1');
    ini_set('session.use_only_cookies', '1');
    session_set_cookie_params([
        'lifetime' => 0,
        'path' => '/',
        'secure' => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
        'httponly' => true,
        'samesite' => 'Lax'
    ]);
    session_start();
}

require_once __DIR__ . '/../config/database.php';

if (!defined('HIMS_ROLE_ADMIN')) {
    define('HIMS_ROLE_ADMIN', 'Admin');
}

if (!defined('HIMS_ROLE_INVENTORY_MANAGER')) {
    define('HIMS_ROLE_INVENTORY_MANAGER', 'Inventory Manager');
}

if (!defined('HIMS_ROLE_TECHNICIAN')) {
    define('HIMS_ROLE_TECHNICIAN', 'Technician');
}

if (!defined('HIMS_ROLE_STAFF')) {
    define('HIMS_ROLE_STAFF', 'Staff');
}

if (!defined('HIMS_ALLOWED_ROLES')) {
    define('HIMS_ALLOWED_ROLES', [
        HIMS_ROLE_ADMIN,
        HIMS_ROLE_INVENTORY_MANAGER,
        HIMS_ROLE_TECHNICIAN,
        HIMS_ROLE_STAFF
    ]);
}

function auth_csrf_token(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }

    return $_SESSION['csrf_token'];
}

function auth_valid_csrf(): bool
{
    $submitted = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? ($_POST['csrf_token'] ?? '');
    return is_string($submitted) && hash_equals(auth_csrf_token(), $submitted);
}

function auth_csrf_field(): string
{
    return '<input type="hidden" name="csrf_token" value="' .
        htmlspecialchars(auth_csrf_token(), ENT_QUOTES, 'UTF-8') . '">';
}

function auth_flash(string $type, string $message): void
{
    $_SESSION['auth_flash'] = ['type' => $type, 'message' => $message];
}

function auth_take_flash(): ?array
{
    $flash = $_SESSION['auth_flash'] ?? null;
    unset($_SESSION['auth_flash']);
    return is_array($flash) ? $flash : null;
}

function auth_rate_limit(string $scope, string $identity, int $limit, int $window): int
{
    $ip = (string) ($_SERVER['REMOTE_ADDR'] ?? 'unknown');
    $key = hash('sha256', $scope . "\0" . strtolower($identity) . "\0" . $ip);
    $directory = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'hims-auth-limits';

    if (!is_dir($directory) && !mkdir($directory, 0700, true) && !is_dir($directory)) {
        error_log('HIMS auth rate-limit storage could not be created.');
        return $window;
    }

    $file = fopen($directory . DIRECTORY_SEPARATOR . 'limits.json', 'c+');
    if ($file === false || !flock($file, LOCK_EX)) {
        if (is_resource($file)) {
            fclose($file);
        }
        error_log('HIMS auth rate-limit storage could not be locked.');
        return $window;
    }
    @chmod($directory . DIRECTORY_SEPARATOR . 'limits.json', 0600);

    rewind($file);
    $stored = json_decode(stream_get_contents($file) ?: '{}', true);
    $now = time();
    $limits = is_array($stored) ? $stored : [];
    foreach ($limits as $storedKey => $attempts) {
        $attempts = is_array($attempts) ? $attempts : [];
        $limits[$storedKey] = array_values(array_filter(
            $attempts,
            static fn($time) => is_int($time) && $time > $now - 3600
        ));
        if (!$limits[$storedKey]) {
            unset($limits[$storedKey]);
        }
    }
    if (count($limits) > 5000) {
        $limits = array_slice($limits, -5000, null, true);
    }

    $attempts = array_values(array_filter(
        $limits[$key] ?? [],
        static fn($time) => $time > $now - $window
    ));

    $retryAfter = 0;
    if (count($attempts) >= $limit) {
        $retryAfter = max(1, $window - ($now - min($attempts)));
    } else {
        $attempts[] = $now;
    }

    $limits[$key] = $attempts;
    ftruncate($file, 0);
    rewind($file);
    $encoded = json_encode($limits);
    if ($encoded === false || fwrite($file, $encoded) !== strlen($encoded)) {
        error_log('HIMS auth rate-limit storage could not be written.');
        $retryAfter = $window;
    }
    fflush($file);
    flock($file, LOCK_UN);
    fclose($file);

    return $retryAfter;
}

function auth_acquire_email_lock(string $email): bool
{
    global $pdo;
    $name = 'hims-email-' . substr(hash('sha256', strtolower($email)), 0, 53);
    $stmt = $pdo->prepare('SELECT GET_LOCK(?, 5)');
    $stmt->execute([$name]);
    return (int) $stmt->fetchColumn() === 1;
}

function auth_release_email_lock(string $email): void
{
    global $pdo;
    $name = 'hims-email-' . substr(hash('sha256', strtolower($email)), 0, 53);
    $stmt = $pdo->prepare('SELECT RELEASE_LOCK(?)');
    $stmt->execute([$name]);
}

function auth_logout_session(): void
{
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', [
            'expires' => time() - 42000,
            'path' => $params['path'],
            'domain' => $params['domain'],
            'secure' => $params['secure'],
            'httponly' => $params['httponly'],
            'samesite' => $params['samesite'] ?? 'Lax'
        ]);
    }
    session_destroy();
}

function auth_fetch_current_user(): ?array
{
    global $pdo;

    $roles = HIMS_ALLOWED_ROLES;
    $placeholders = [];
    foreach ($roles as $index => $role) {
        $placeholders[] = ':role_' . $index;
    }

    $stmt = $pdo->prepare(
        "SELECT u.user_id, u.first_name, u.last_name, u.email, u.role_id, r.role_name
         FROM users u
         INNER JOIN roles r ON r.role_id = u.role_id
         WHERE u.user_id = :user_id
           AND u.email_verified_at IS NOT NULL
           AND u.approval_status = 'Approved'
           AND u.account_status = 'Enabled'
           AND r.role_name IN (" . implode(', ', $placeholders) . ")
         LIMIT 1"
    );

    $params = ['user_id' => (int) $_SESSION['user_id']];
    foreach ($roles as $index => $role) {
        $params['role_' . $index] = $role;
    }

    $stmt->execute($params);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    return $user !== false ? $user : null;
}

function auth_require_user(): void
{
    if (empty($_SESSION['user_id'])) {
        header('Location: ../account/login.php');
        exit;
    }

    $user = auth_fetch_current_user();

    if (!$user) {
        auth_logout_session();
        header('Location: ../account/login.php?status=session-expired');
        exit;
    }

    $_SESSION['first_name'] = $user['first_name'];
    $_SESSION['last_name'] = $user['last_name'];
    $_SESSION['email'] = $user['email'];
    $_SESSION['role_id'] = $user['role_id'] !== null ? (int) $user['role_id'] : null;
    $_SESSION['role'] = $user['role_name'];
}

function auth_has_role(array|string $allowedRoles): bool
{
    $currentRole = $_SESSION['role'] ?? null;
    if (!is_string($currentRole) || $currentRole === '') {
        return false;
    }
    $roles = is_array($allowedRoles) ? $allowedRoles : [$allowedRoles];
    return in_array($currentRole, $roles, true);
}

function auth_require_role(array|string $allowedRoles): void
{
    auth_require_user();
    if (!auth_has_role($allowedRoles)) {
        http_response_code(403);
        exit('Access denied.');
    }
}

function auth_require_admin(): void
{
    auth_require_role(HIMS_ROLE_ADMIN);
}

function auth_json_require_role(array|string $allowedRoles): void
{
    auth_json_require_user();
    if (!auth_has_role($allowedRoles)) {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'Access denied. You do not have permission to perform this action.']);
        exit;
    }
}

function auth_require_csrf(): void
{
    if (!auth_valid_csrf()) {
        http_response_code(403);
        exit('Invalid request. Refresh the page and try again.');
    }
}

function auth_json_require_user(): void
{
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        header('Allow: POST');
        echo json_encode(['success' => false, 'message' => 'Method not allowed.']);
        exit;
    }

    if (empty($_SESSION['user_id'])) {
        http_response_code(401);
        echo json_encode(['success' => false, 'message' => 'Please sign in again.']);
        exit;
    }

    $user = auth_fetch_current_user();
    if (!$user) {
        auth_logout_session();
        http_response_code(401);
        echo json_encode(['success' => false, 'message' => 'Please sign in again.']);
        exit;
    }

    $_SESSION['first_name'] = $user['first_name'];
    $_SESSION['last_name'] = $user['last_name'];
    $_SESSION['email'] = $user['email'];
    $_SESSION['role_id'] = $user['role_id'] !== null ? (int) $user['role_id'] : null;
    $_SESSION['role'] = $user['role_name'];

    if (!auth_valid_csrf()) {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'Invalid request. Refresh the page and try again.']);
        exit;
    }
}
