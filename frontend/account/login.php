
<?php
require_once __DIR__ . '/../../backend/middleware/auth.php';
require_once __DIR__ . '/../../backend/services/mailer.php';

$loginError = '';
$loginSuccess = '';
$forgotStatus = '';
$showForgotDialog = false;
$showResetDialog = false;
$resetToken = '';
$resetTokenValid = false;
$flash = auth_take_flash();
if (($_GET['status'] ?? '') === 'session-expired') {
    $loginError = 'Your session is no longer active. Please sign in again.';
}

if ($flash) {
    if ($flash['type'] === 'login-error') {
        $loginError = $flash['message'];
    } elseif ($flash['type'] === 'forgot-status') {
        $forgotStatus = $flash['message'];
        $showForgotDialog = true;
    } elseif ($flash['type'] === 'reset-success') {
        $loginSuccess = $flash['message'];
    }
}

if (isset($_GET['reset'])) {
    $resetToken = is_string($_GET['reset']) ? $_GET['reset'] : '';
    if (!preg_match('/\A[a-f0-9]{64}\z/', $resetToken)) {
        $loginError = 'This password reset link is invalid or has expired. Request a new one.';
    } else {
        $tokenCheck = $pdo->prepare(
            'SELECT reset_id FROM password_reset_tokens
             WHERE token_hash = :token_hash AND used_at IS NULL
               AND expires_at > UTC_TIMESTAMP() LIMIT 1'
        );
        $tokenCheck->execute(['token_hash' => hash('sha256', $resetToken)]);
        $resetTokenValid = (bool) $tokenCheck->fetchColumn();
        $showResetDialog = $resetTokenValid;
        if (!$resetTokenValid) {
            $loginError = 'This password reset link is invalid or has expired. Request a new one.';
        }
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!auth_valid_csrf()) {
        $loginError = 'Your session expired. Please try again.';
    } else {
        $action = is_string($_POST['action'] ?? null) ? $_POST['action'] : 'login';
        $email = strtolower(trim(is_string($_POST['email'] ?? null) ? $_POST['email'] : ''));

        if ($action === 'forgot_password') {
            $showForgotDialog = true;
            $retryAfter = max(
                auth_rate_limit('password-reset', $email, 3, 3600),
                auth_rate_limit('password-reset-ip', 'global', 10, 3600)
            );

            if ($retryAfter > 0) {
                $forgotStatus = 'Too many requests. Please try again later.';
            } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $forgotStatus = 'If an eligible account matches this email, reset instructions will be sent when email delivery is available.';
            } else {
                $genericResetMessage = 'If an eligible account matches this email, reset instructions will be sent when email delivery is available.';
                try {
                    $stmt = $pdo->prepare(
                        'SELECT user_id FROM users
                         WHERE email = :email AND email_verified_at IS NOT NULL LIMIT 1'
                    );
                    $stmt->execute(['email' => $email]);
                    $userId = $stmt->fetchColumn();

                    if ($userId && system_email_configured()) {
                        $token = bin2hex(random_bytes(32));
                        $pdo->beginTransaction();
                        $pdo->prepare('DELETE FROM password_reset_tokens WHERE user_id = ?')
                            ->execute([(int) $userId]);
                        $pdo->prepare(
                            'INSERT INTO password_reset_tokens (user_id, token_hash, expires_at)
                             VALUES (?, ?, DATE_ADD(UTC_TIMESTAMP(), INTERVAL 1 HOUR))'
                        )->execute([(int) $userId, hash('sha256', $token)]);
                        $mailSettings = require __DIR__ . '/../../backend/config/mail.php';
                        queue_system_email(
                            $email,
                            'Password reset request',
                            "Use this link within one hour to reset your HIMS password:\n\n" .
                                $mailSettings['app_url'] . '/account/login.php?reset=' . $token .
                                "\n\nIf you did not request a reset, you can ignore this email."
                        );
                        $pdo->commit();
                    }
                    $forgotStatus = $genericResetMessage;
                } catch (PDOException | RuntimeException $exception) {
                    if ($pdo->inTransaction()) {
                        $pdo->rollBack();
                    }
                    error_log('HIMS password reset request could not be completed.');
                    $forgotStatus = $genericResetMessage;
                }
            }
        } elseif ($action === 'reset_password') {
            $postedToken = is_string($_POST['reset_token'] ?? null) ? $_POST['reset_token'] : '';
            $password = is_string($_POST['password'] ?? null) ? $_POST['password'] : '';
            $confirmPassword = is_string($_POST['confirm_password'] ?? null) ? $_POST['confirm_password'] : '';
            $retryAfter = max(
                auth_rate_limit('password-reset-verify', $postedToken, 10, 3600),
                auth_rate_limit('password-reset-verify-ip', 'global', 30, 3600)
            );

            if (!preg_match('/\A[a-f0-9]{64}\z/', $postedToken) || $retryAfter > 0) {
                $loginError = 'This password reset link is invalid or has expired. Request a new one.';
            } elseif (strlen($password) < 8 || strlen($password) > 72 || $password !== $confirmPassword) {
                $loginError = 'Enter a matching password between 8 and 72 characters.';
                $resetToken = $postedToken;
                $resetTokenValid = true;
                $showResetDialog = true;
            } else {
                try {
                    $pdo->beginTransaction();
                    $stmt = $pdo->prepare(
                        'SELECT reset_id, user_id FROM password_reset_tokens
                         WHERE token_hash = ? AND used_at IS NULL
                           AND expires_at > UTC_TIMESTAMP()
                         LIMIT 1 FOR UPDATE'
                    );
                    $stmt->execute([hash('sha256', $postedToken)]);
                    $reset = $stmt->fetch();
                    if (!$reset) {
                        $pdo->rollBack();
                        $loginError = 'This password reset link is invalid or has expired. Request a new one.';
                    } else {
                        $pdo->prepare('UPDATE users SET password_hash = ? WHERE user_id = ?')
                            ->execute([password_hash($password, PASSWORD_DEFAULT), (int) $reset['user_id']]);
                        $pdo->prepare(
                            'UPDATE password_reset_tokens SET used_at = UTC_TIMESTAMP()
                             WHERE reset_id = ? AND used_at IS NULL'
                        )->execute([(int) $reset['reset_id']]);
                        $pdo->commit();
                        auth_flash('reset-success', 'Your password has been updated. You can now sign in.');
                        header('Location: login.php');
                        exit;
                    }
                } catch (PDOException | RuntimeException $exception) {
                    if ($pdo->inTransaction()) {
                        $pdo->rollBack();
                    }
                    error_log('HIMS password reset could not be completed.');
                    $loginError = 'Unable to reset your password. Please try again.';
                }
            }
        } else {
            $password = is_string($_POST['password'] ?? null) ? $_POST['password'] : '';
            $retryAfter = max(
                auth_rate_limit('login', $email, 10, 900),
                auth_rate_limit('login-ip', 'global', 30, 900)
            );

            if ($retryAfter > 0) {
                $loginError = 'Too many sign-in attempts. Please try again later.';
            } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($email) > 254 ||
                $password === '' || strlen($password) > 72) {
                $loginError = 'Invalid email or password.';
            } else {
                $stmt = $pdo->prepare(
                    'SELECT u.user_id, u.first_name, u.last_name, u.email, u.password_hash,
                            u.role_id, r.role_name,
                            u.approval_status, u.account_status, u.email_verified_at
                     FROM users u
                     LEFT JOIN roles r ON r.role_id = u.role_id
                     WHERE u.email = :email LIMIT 1'
                );
                $stmt->execute(['email' => $email]);
                $user = $stmt->fetch();

                if (!$user || !password_verify($password, $user['password_hash'])) {
                    $loginError = 'Invalid email or password.';
                } elseif ($user['email_verified_at'] === null) {
                    $loginError = 'Please verify your email before signing in.';
                } elseif ($user['approval_status'] === 'Pending') {
                    $loginError = 'Your account is waiting for Admin approval.';
                } elseif ($user['approval_status'] === 'Rejected') {
                    $loginError = 'Your account registration was rejected.';
                } elseif ($user['account_status'] !== 'Enabled') {
                    $loginError = 'Your account is disabled. Please contact the Admin.';
                } elseif (!in_array($user['role_name'], HIMS_ALLOWED_ROLES, true)) {
                    $loginError = 'Your account is awaiting role assignment by an Admin.';
                } else {
                    if (password_needs_rehash($user['password_hash'], PASSWORD_DEFAULT)) {
                        $pdo->prepare('UPDATE users SET password_hash = ? WHERE user_id = ?')
                            ->execute([password_hash($password, PASSWORD_DEFAULT), (int) $user['user_id']]);
                    }
                    session_regenerate_id(true);
                    $_SESSION['user_id'] = (int) $user['user_id'];
                    $_SESSION['first_name'] = $user['first_name'];
                    $_SESSION['last_name'] = $user['last_name'];
                    $_SESSION['email'] = $user['email'];
                    $_SESSION['role_id'] = $user['role_id'] !== null ? (int) $user['role_id'] : null;
                    $_SESSION['role'] = $user['role_name'];
                    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
                    header('Location: ../pages/dashboard.php');
                    exit;
                }
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login | Hardware Inventory Management System</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/auth.css">
</head>
<body class="login-page">

    <main class="auth-shell">

        <aside class="auth-welcome">
            <div class="auth-mark" aria-hidden="true">H</div>
            <h1>Hardware Inventory Management System</h1>
            <p>Sign in to manage your organization's hardware assets.</p>
            <a href="register.php">Create account</a>
        </aside>

        <section class="login-container" aria-labelledby="login-heading">

            <h2 id="login-heading">Welcome back</h2>
            <p class="login-subtitle">
                Enter your details to sign in to your account.
            </p>

            <?php if ($loginError !== ''): ?>
                <div class="auth-message" role="alert">
                    <?= htmlspecialchars($loginError, ENT_QUOTES, 'UTF-8') ?>
                </div>
            <?php endif; ?>
            <?php if ($loginSuccess !== ''): ?>
                <div class="auth-message success" role="status">
                    <?= htmlspecialchars($loginSuccess, ENT_QUOTES, 'UTF-8') ?>
                </div>
            <?php endif; ?>

            <form action="login.php" method="POST" data-loading-form>
                <?= auth_csrf_field() ?>
                <input type="hidden" name="action" value="login">

                <div class="form-group">
                    <label for="email">Email Address</label>
                    <input
                        type="email"
                        id="email"
                        name="email"
                        placeholder="Email address"
                        autocomplete="email"
                        required
                    >
                </div>

                <div class="form-group">
                    <label for="password">Password</label>

                    <div class="password-field">
                        <input
                            type="password"
                            id="password"
                            name="password"
                            placeholder="Password"
                            autocomplete="current-password"
                            required
                        >

                        <button
                            class="password-toggle"
                            type="button"
                            aria-controls="password"
                            aria-label="Show password"
                            data-password-toggle
                        >
                            Show
                        </button>
                    </div>
                </div>

                <div class="login-options">
                    <label class="remember-option" for="remember-me">
                        <input type="checkbox" id="remember-me" name="remember_me" value="1">
                        <span>Remember Me</span>
                    </label>
                    <a href="#" data-dialog-open="forgot-password-dialog">
                        Forgot password?
                    </a>
                </div>

                <button type="submit" class="login-button" data-loading-text="Signing in...">
                    Login
                </button>

            </form>

            <p class="register-link">
                Don't have an account?
                <a href="register.php">Create account</a>
            </p>

        </section>
    </main>

    <dialog
        class="auth-dialog"
        id="forgot-password-dialog"
        aria-labelledby="forgot-password-heading"
    >

        <button
            class="auth-dialog-close"
            type="button"
            aria-label="Close dialog"
            data-dialog-close
        >
            &times;
        </button>

        <h2 id="forgot-password-heading">Forgot your password?</h2>

        <p>
            Enter your account email to request password reset instructions.
        </p>

        <form action="login.php" method="POST" data-forgot-password-form data-loading-form>
            <?= auth_csrf_field() ?>
            <input type="hidden" name="action" value="forgot_password">
            <div class="form-group">
                <label for="reset-email">Email Address</label>
                <input
                    type="email"
                    id="reset-email"
                    name="email"
                    placeholder="Email address"
                    autocomplete="email"
                    required
                >
            </div>

            <button type="submit" class="login-button" data-loading-text="Sending request...">
                Send reset request
            </button>

            <?php if ($forgotStatus !== ''): ?>
                <p class="auth-dialog-status" role="status" aria-live="polite">
                    <?= htmlspecialchars($forgotStatus, ENT_QUOTES, 'UTF-8') ?>
                </p>
            <?php else: ?>
                <p class="auth-dialog-status" role="status" aria-live="polite" hidden></p>
            <?php endif; ?>
        </form>

        <p class="auth-dialog-note">
            Reset links expire after one hour and can only be used once.
        </p>

    </dialog>

    <dialog
        class="auth-dialog"
        id="reset-password-dialog"
        aria-labelledby="reset-password-heading"
    >
        <button class="auth-dialog-close" type="button" aria-label="Close dialog" data-dialog-close>&times;</button>
        <h2 id="reset-password-heading">Set a new password</h2>
        <p>Choose a password between 8 and 72 characters.</p>
        <form action="login.php" method="POST" data-loading-form>
            <?= auth_csrf_field() ?>
            <input type="hidden" name="action" value="reset_password">
            <input type="hidden" name="reset_token" value="<?= htmlspecialchars($resetToken, ENT_QUOTES, 'UTF-8') ?>">
            <div class="form-group">
                <label for="reset-password">New Password</label>
                <div class="password-field">
                    <input type="password" id="reset-password" name="password" minlength="8" maxlength="72" autocomplete="new-password" required>
                    <button class="password-toggle" type="button" aria-controls="reset-password" aria-label="Show password" data-password-toggle>Show</button>
                </div>
            </div>
            <div class="form-group">
                <label for="reset-confirm-password">Confirm New Password</label>
                <input type="password" id="reset-confirm-password" name="confirm_password" minlength="8" maxlength="72" autocomplete="new-password" required>
            </div>
            <button type="submit" class="login-button" data-loading-text="Updating password...">Update Password</button>
        </form>
    </dialog>

    <script src="../assets/js/auth.js"></script>
    <script>
        <?php if ($showForgotDialog): ?>document.getElementById('forgot-password-dialog').showModal();<?php endif; ?>
        <?php if ($showResetDialog): ?>document.getElementById('reset-password-dialog').showModal();<?php endif; ?>
    </script>

</body>
</html>
