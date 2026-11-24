
<?php
require_once __DIR__ . '/../../backend/middleware/auth.php';
require_once __DIR__ . '/../../backend/services/mailer.php';

$registrationError = '';
$registrationSuccess = false;
$verificationSuccess = false;
$flash = auth_take_flash();
if ($flash) {
    if ($flash['type'] === 'registration-success') {
        $registrationSuccess = true;
    } elseif ($flash['type'] === 'registration-verified') {
        $verificationSuccess = true;
    } elseif ($flash['type'] === 'registration-error') {
        $registrationError = $flash['message'];
    }
}

if (isset($_GET['verify'])) {
    $token = is_string($_GET['verify']) ? $_GET['verify'] : '';
    $retryAfter = max(
        auth_rate_limit('email-verification', hash('sha256', $token), 10, 3600),
        auth_rate_limit('email-verification-ip', 'global', 30, 3600)
    );

    if (!preg_match('/\A[a-f0-9]{64}\z/', $token) || $retryAfter > 0) {
        $registrationError = 'This verification link is invalid or has expired. Request a new email below.';
    } else {
        $emailStmt = $pdo->prepare(
            'SELECT email FROM pending_registrations
             WHERE verification_token_hash = ? AND expires_at > UTC_TIMESTAMP() LIMIT 1'
        );
        $emailStmt->execute([hash('sha256', $token)]);
        $verificationEmail = $emailStmt->fetchColumn();

        if (!$verificationEmail || !auth_acquire_email_lock($verificationEmail)) {
            $registrationError = 'This verification link is invalid or has expired. Request a new email below.';
        } else {
        $emailLockAcquired = true;
        try {
            $pdo->beginTransaction();
            $stmt = $pdo->prepare(
                'SELECT registration_id, first_name, middle_name, last_name, email, password_hash
                 FROM pending_registrations
                 WHERE verification_token_hash = ? AND expires_at > UTC_TIMESTAMP()
                 LIMIT 1 FOR UPDATE'
            );
            $stmt->execute([hash('sha256', $token)]);
            $pending = $stmt->fetch();

            if (!$pending) {
                $pdo->rollBack();
                auth_release_email_lock($verificationEmail);
                $emailLockAcquired = false;
                $registrationError = 'This verification link is invalid or has expired. Request a new email below.';
            } else {
                $pdo->prepare(
                    "INSERT INTO users (
                        first_name, middle_name, last_name, email, password_hash, role_id,
                        approval_status, account_status, email_verified_at
                     ) VALUES (?, ?, ?, ?, ?, NULL, 'Pending', NULL, UTC_TIMESTAMP())"
                )->execute([
                    $pending['first_name'],
                    $pending['middle_name'],
                    $pending['last_name'],
                    $pending['email'],
                    $pending['password_hash']
                ]);
                $pdo->prepare('DELETE FROM pending_registrations WHERE registration_id = ?')
                    ->execute([(int) $pending['registration_id']]);
                $pdo->commit();
                auth_release_email_lock($verificationEmail);
                $emailLockAcquired = false;
                auth_flash('registration-verified', 'Your email is verified. Your account is pending Admin approval.');
                header('Location: register.php');
                exit;
            }
        } catch (PDOException | RuntimeException $exception) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            if ($emailLockAcquired) {
                auth_release_email_lock($verificationEmail);
            }
            error_log('HIMS email verification could not be completed.');
            $registrationError = 'This verification link could not be completed. Request a new email below.';
        }
        }
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!auth_valid_csrf()) {
        $registrationError = 'Your session expired. Please refresh the page and try again.';
    } else {
        $action = is_string($_POST['action'] ?? null) ? $_POST['action'] : 'register';
        $email = strtolower(trim(is_string($_POST['email'] ?? null) ? $_POST['email'] : ''));

        if ($action === 'resend') {
            $retryAfter = max(
                auth_rate_limit('verification-resend', $email, 3, 3600),
                auth_rate_limit('verification-resend-ip', 'global', 10, 3600)
            );
            $message = 'If this address has a pending registration, a fresh verification email will be delivered when mail service is available.';

            if ($retryAfter > 0) {
                $registrationError = 'Too many requests. Please try again later.';
            } elseif (filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $emailLockAcquired = false;
                try {
                    $emailLockAcquired = auth_acquire_email_lock($email);
                    if (!$emailLockAcquired) {
                        throw new RuntimeException('Registration is busy.');
                    }
                    $pdo->beginTransaction();
                    $stmt = $pdo->prepare(
                        'SELECT registration_id FROM pending_registrations WHERE email = ? LIMIT 1'
                    );
                    $stmt->execute([$email]);
                    $registrationId = $stmt->fetchColumn();
                    $token = null;

                    if ($registrationId && system_email_configured()) {
                        $token = bin2hex(random_bytes(32));
                        $pdo->prepare(
                            'UPDATE pending_registrations
                             SET verification_token_hash = ?,
                                 expires_at = DATE_ADD(UTC_TIMESTAMP(), INTERVAL 24 HOUR)
                             WHERE registration_id = ?'
                        )->execute([hash('sha256', $token), (int) $registrationId]);
                        $mailSettings = require __DIR__ . '/../../backend/config/mail.php';
                        queue_system_email(
                            $email,
                            'Verify your HIMS account',
                            "Verify your email within 24 hours:\n\n" .
                                $mailSettings['app_url'] . '/account/register.php?verify=' . $token
                        );
                    }
                    $pdo->commit();
                    auth_release_email_lock($email);
                    $emailLockAcquired = false;
                    auth_flash('registration-success', $message);
                    header('Location: register.php');
                    exit;
                } catch (PDOException | RuntimeException $exception) {
                    if ($pdo->inTransaction()) {
                        $pdo->rollBack();
                    }
                    if ($emailLockAcquired) {
                        auth_release_email_lock($email);
                    }
                    error_log('HIMS verification email could not be resent.');
                    auth_flash('registration-success', $message);
                    header('Location: register.php');
                    exit;
                }
            } else {
                auth_flash('registration-success', $message);
                header('Location: register.php');
                exit;
            }
        } else {
            $firstName = trim(is_string($_POST['first_name'] ?? null) ? $_POST['first_name'] : '');
            $middleName = trim(is_string($_POST['middle_name'] ?? null) ? $_POST['middle_name'] : '');
            $lastName = trim(is_string($_POST['last_name'] ?? null) ? $_POST['last_name'] : '');
            $password = is_string($_POST['password'] ?? null) ? $_POST['password'] : '';
            $confirmPassword = is_string($_POST['confirm_password'] ?? null) ? $_POST['confirm_password'] : '';
            $retryAfter = max(
                auth_rate_limit('registration', $email, 5, 3600),
                auth_rate_limit('registration-ip', 'global', 10, 3600)
            );

            if ($retryAfter > 0) {
                $registrationError = 'Too many registration requests. Please try again later.';
            } elseif (
                $firstName === '' || $lastName === '' || $email === '' || $password === '' ||
                strlen($firstName) > 100 || strlen($middleName) > 100 || strlen($lastName) > 100
            ) {
                $registrationError = 'Please complete all required fields.';
            } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($email) > 254) {
                $registrationError = 'Please enter a valid email address.';
            } elseif (strlen($password) < 8 || strlen($password) > 72) {
                $registrationError = 'Password must be between 8 and 72 characters.';
            } elseif ($password !== $confirmPassword) {
                $registrationError = 'Passwords do not match.';
            } elseif (!system_email_configured()) {
                $registrationError = 'Email verification is not configured. Please contact the system administrator.';
            } else {
                $emailLockAcquired = false;
                $token = null;
                try {
                    $emailLockAcquired = auth_acquire_email_lock($email);
                    if (!$emailLockAcquired) {
                        throw new RuntimeException('Registration is busy.');
                    }
                    $pdo->beginTransaction();
                    $stmt = $pdo->prepare('SELECT user_id FROM users WHERE email = ? LIMIT 1');
                    $stmt->execute([$email]);
                    $existingUser = $stmt->fetchColumn();
                    if (!$existingUser) {
                        $stmt = $pdo->prepare('SELECT registration_id FROM pending_registrations WHERE email = ? LIMIT 1');
                        $stmt->execute([$email]);
                        $registrationId = $stmt->fetchColumn();
                        $token = bin2hex(random_bytes(32));
                        $passwordHash = password_hash($password, PASSWORD_DEFAULT);

                        if ($registrationId) {
                            $pdo->prepare(
                                'UPDATE pending_registrations
                                 SET first_name = ?, middle_name = ?, last_name = ?, password_hash = ?,
                                     verification_token_hash = ?,
                                     expires_at = DATE_ADD(UTC_TIMESTAMP(), INTERVAL 24 HOUR)
                                 WHERE registration_id = ?'
                            )->execute([
                                $firstName, $middleName !== '' ? $middleName : null, $lastName,
                                $passwordHash, hash('sha256', $token), (int) $registrationId
                            ]);
                        } else {
                            $pdo->prepare(
                                'INSERT INTO pending_registrations (
                                    first_name, middle_name, last_name, email, password_hash,
                                    verification_token_hash, expires_at
                                 ) VALUES (?, ?, ?, ?, ?, ?, DATE_ADD(UTC_TIMESTAMP(), INTERVAL 24 HOUR))'
                            )->execute([
                                $firstName, $middleName !== '' ? $middleName : null, $lastName,
                                $email, $passwordHash, hash('sha256', $token)
                            ]);
                        }

                        $mailSettings = require __DIR__ . '/../../backend/config/mail.php';
                        queue_system_email(
                            $email,
                            'Verify your HIMS account',
                            "Verify your email within 24 hours:\n\n" .
                            $mailSettings['app_url'] . '/account/register.php?verify=' . $token
                        );
                    }
                    $pdo->commit();
                    auth_release_email_lock($email);
                    $emailLockAcquired = false;

                    auth_flash(
                        'registration-success',
                        'If this address can be registered, a verification link will be delivered when email service is available.'
                    );
                    header('Location: register.php');
                    exit;
                } catch (PDOException $exception) {
                    if ($pdo->inTransaction()) {
                        $pdo->rollBack();
                    }
                    if ($emailLockAcquired) {
                        auth_release_email_lock($email);
                    }
                    error_log('HIMS registration database operation failed: ' . $exception->getMessage());
                    $registrationError = 'Unable to process this request. Please try again later.';
                } catch (RuntimeException $exception) {
                    if ($pdo->inTransaction()) {
                        $pdo->rollBack();
                    }
                    if ($emailLockAcquired) {
                        auth_release_email_lock($email);
                    }
                    error_log('HIMS verification email could not be sent.');
                    $registrationError = 'Email verification could not be completed. Please retry or contact the system administrator.';
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
    <title>Register | Hardware Inventory Management System</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/auth.css">
</head>

<body class="login-page">

    <main class="auth-shell">

        <aside class="auth-welcome">
            <div class="auth-mark" aria-hidden="true">H</div>
            <h1>Hardware Inventory Management System</h1>
            <p>Create an account to request access to your organization's hardware inventory.</p>
            <a href="login.php">Sign in</a>
        </aside>

        <section class="login-container" aria-labelledby="register-heading">

            <h2 id="register-heading">Create account</h2>
            <p class="login-subtitle">Enter your details to request an account.</p>

            <form action="register.php" method="POST" data-loading-form
                  data-auth-form="register">
                <?= auth_csrf_field() ?>
                <input type="hidden" name="action" value="register">

                <div class="auth-form-grid">

                    <div class="form-group">
                        <label for="first_name">First Name</label>
                        <input
                            type="text"
                            id="first_name"
                            name="first_name"
                            placeholder="First name"
                            autocomplete="given-name"
                            required
                        >
                    </div>

                    <div class="form-group">
                        <label for="middle_name">
                            Middle Name <span>(optional)</span>
                        </label>
                        <input
                            type="text"
                            id="middle_name"
                            name="middle_name"
                            placeholder="Middle name (optional)"
                            autocomplete="additional-name"
                        >
                    </div>

                    <div class="form-group full-width">
                        <label for="last_name">Last Name</label>
                        <input
                            type="text"
                            id="last_name"
                            name="last_name"
                            placeholder="Last name"
                            autocomplete="family-name"
                            required
                        >
                    </div>

                    <div class="form-group full-width">
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
                                autocomplete="new-password"
                                minlength="8"
                                maxlength="72"
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

                    <div class="form-group">
                        <label for="confirm_password">Confirm Password</label>

                        <div class="password-field">
                            <input
                                type="password"
                                id="confirm_password"
                                name="confirm_password"
                                placeholder="Confirm password"
                                autocomplete="new-password"
                                required
                            >

                            <button
                                class="password-toggle"
                                type="button"
                                aria-controls="confirm_password"
                                aria-label="Show password"
                                data-password-toggle
                            >
                                Show
                            </button>
                        </div>
                    </div>

                </div>

                <?php if ($registrationError !== ''): ?>
                    <div class="auth-message" role="alert" aria-live="polite">
                        <?= htmlspecialchars($registrationError, ENT_QUOTES, 'UTF-8') ?>
                    </div>
                <?php endif; ?>

                <button type="submit" class="login-button" data-loading-text="Creating account...">
                    Create Account
                </button>

            </form>

            <p class="register-link">
                Already have an account?
                <a href="login.php">Sign in</a>
            </p>

            <details class="register-link verification-resend">
                <summary>Didn't receive your verification email?</summary>
                <form action="register.php" method="POST" data-loading-form>
                    <?= auth_csrf_field() ?>
                    <input type="hidden" name="action" value="resend">
                    <div class="form-group">
                        <label for="resend-email">Email Address</label>
                        <input type="email" id="resend-email" name="email" placeholder="Email address" autocomplete="email" required>
                    </div>
                    <button type="submit" class="login-button" data-loading-text="Sending...">Resend Verification Email</button>
                </form>
            </details>

        </section>
    </main>

    <dialog
        class="auth-dialog"
        id="register-success-dialog"
        aria-labelledby="register-success-heading"
    >

        <div class="auth-dialog-icon" aria-hidden="true">&#10003;</div>

        <?php if ($verificationSuccess): ?>
            <h2 id="register-success-heading">Email verified successfully</h2>
            <p>Your registration is now pending Admin approval. You can sign in after it is approved.</p>
        <?php else: ?>
            <h2 id="register-success-heading">Check your email</h2>
            <p>If this address can be registered, a verification link will be delivered when email service is available. Verify your email before Admin review.</p>
        <?php endif; ?>

        <div class="auth-dialog-actions">
            <a class="login-button auth-dialog-link" href="login.php">
                Back to login
            </a>
        </div>

    </dialog>

    <script src="../assets/js/auth.js"></script>

    <?php if ($registrationSuccess): ?>
        <script>
            document.getElementById('register-success-dialog').showModal();
        </script>
    <?php endif; ?>
    <?php if ($verificationSuccess): ?>
        <script>
            document.getElementById('register-success-dialog').showModal();
        </script>
    <?php endif; ?>

</body>
</html>
