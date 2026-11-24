
<?php
$setupSecret = getenv('HIMS_ADMIN_SETUP_SECRET');
if (!is_string($setupSecret) || !preg_match('/\A[a-f0-9]{64}\z/i', $setupSecret)) {
    http_response_code(404);
    exit;
}

require_once __DIR__ . '/../backend/middleware/auth.php';

function hims_admin_exists(PDO $pdo): bool
{
    $stmt = $pdo->prepare(
        "SELECT COUNT(*)
         FROM users u
         INNER JOIN roles r ON r.role_id = u.role_id
         WHERE r.role_name = :role_name"
    );
    $stmt->execute(['role_name' => HIMS_ROLE_ADMIN]);

    return (int) $stmt->fetchColumn() > 0;
}

$message = '';
$success = false;
try {
    $adminExists = hims_admin_exists($pdo);
    if ($adminExists) {
        http_response_code(404);
        exit;
    }

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if (!auth_valid_csrf()) {
            $message = 'Your session expired. Please refresh the page and try again.';
        } elseif (auth_rate_limit('first-admin-setup', 'global', 5, 3600) > 0) {
            $message = 'Too many setup attempts. Please try again later.';
        } elseif (
            !is_string($_POST['setup_secret'] ?? null) ||
            !hash_equals($setupSecret, $_POST['setup_secret'])
        ) {
            $message = 'The setup secret is invalid.';
        } elseif ((int) $pdo->query("SELECT GET_LOCK('hims_first_admin_setup', 5)")->fetchColumn() !== 1) {
            $message = 'Admin setup is busy. Please try again.';
        } else {
            try {
                $adminExists = hims_admin_exists($pdo);

                if ($adminExists) {
                    $message = 'An Admin account already exists.';
                } else {
                    $firstName = trim(is_string($_POST['first_name'] ?? null) ? $_POST['first_name'] : '');
                    $middleName = trim(is_string($_POST['middle_name'] ?? null) ? $_POST['middle_name'] : '');
                    $lastName = trim(is_string($_POST['last_name'] ?? null) ? $_POST['last_name'] : '');
                    $email = strtolower(trim(is_string($_POST['email'] ?? null) ? $_POST['email'] : ''));
                    $password = is_string($_POST['password'] ?? null) ? $_POST['password'] : '';
                    $confirmPassword = is_string($_POST['confirm_password'] ?? null) ? $_POST['confirm_password'] : '';

                    if ($firstName === '' || $lastName === '' || $email === '' || $password === '') {
                        $message = 'Please complete all required fields.';
                    } elseif (
                        !filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($email) > 254 ||
                        strlen($firstName) > 100 || strlen($middleName) > 100 || strlen($lastName) > 100
                    ) {
                        $message = 'Please enter valid account details.';
                    } elseif (strlen($password) < 8 || strlen($password) > 72) {
                        $message = 'Password must contain between 8 and 72 characters.';
                    } elseif ($password !== $confirmPassword) {
                        $message = 'Passwords do not match.';
                    } else {
                        $roleStmt = $pdo->prepare(
                            'SELECT role_id FROM roles WHERE role_name = :role_name'
                        );
                        $roleStmt->execute(['role_name' => HIMS_ROLE_ADMIN]);
                        $adminRoleId = $roleStmt->fetchColumn();

                        if (!$adminRoleId) {
                            throw new RuntimeException('The Admin role is not configured. Apply config/role_normalization_migration.sql.');
                        }

                        $stmt = $pdo->prepare(
                            "INSERT INTO users (
                                first_name, middle_name, last_name, email, password_hash,
                                role_id, approval_status, account_status, email_verified_at
                             ) VALUES (?, ?, ?, ?, ?, ?, 'Approved', 'Enabled', UTC_TIMESTAMP())"
                        );
                        $stmt->execute([
                            $firstName,
                            $middleName !== '' ? $middleName : null,
                            $lastName,
                            $email,
                            password_hash($password, PASSWORD_DEFAULT),
                            (int) $adminRoleId
                        ]);
                        $success = true;
                        $message = 'Your first Admin account was created successfully!';
                        $adminExists = true;
                    }
                }
            } catch (PDOException | RuntimeException $exception) {
                if ($exception instanceof PDOException && $exception->getCode() === '23000') {
                    $message = 'This email address is already registered.';
                } else {
                    error_log('HIMS first Admin setup failed: ' . $exception->getMessage());
                    $message = $exception instanceof RuntimeException
                        ? $exception->getMessage()
                        : 'Unable to create the account. Please check the database connection.';
                }
            } finally {
                $pdo->query("SELECT RELEASE_LOCK('hims_first_admin_setup')");
            }
        }
    }
} catch (PDOException $exception) {
    error_log('HIMS first Admin setup failed: ' . $exception->getMessage());
    http_response_code(503);
    exit('Admin setup is temporarily unavailable.');
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create First Admin</title>
    <style>
        * {
            box-sizing: border-box;
        }

        body {
            font-family: Arial, sans-serif;
            background: #f4f6fa;
            display: grid;
            place-items: center;
            min-height: 100vh;
            margin: 0;
            padding: 20px;
        }

        .container {
            background: white;
            width: 100%;
            max-width: 430px;
            padding: 30px;
            border-radius: 12px;
            box-shadow: 0 4px 20px rgba(0,0,0,.06);
        }

        h2 {
            margin-top: 0;
        }

        p {
            color: #64748b;
            line-height: 1.5;
        }

        label {
            display: block;
            margin-top: 15px;
            font-size: 14px;
            font-weight: 600;
        }

        input {
            width: 100%;
            padding: 11px;
            margin-top: 6px;
            border: 1px solid #cbd5e1;
            border-radius: 7px;
        }

        button {
            width: 100%;
            padding: 12px;
            margin-top: 22px;
            border: none;
            border-radius: 7px;
            background: #172b4d;
            color: white;
            font-weight: bold;
            cursor: pointer;
        }

        .message {
            margin-top: 15px;
            padding: 12px;
            background: #f1f5f9;
            border-radius: 7px;
            line-height: 1.5;
        }

        .success {
            color: #166534;
        }

        .error {
            color: #b91c1c;
        }
    </style>
</head>
<body>

<div class="container">

    <h2>Create First Admin</h2>
    <p>Set up the initial administrator account for your system.</p>

    <?php if ($message): ?>
        <div class="message <?= $success ? 'success' : 'error' ?>">
            <?= htmlspecialchars($message, ENT_QUOTES, 'UTF-8') ?>
        </div>
    <?php endif; ?>

    <?php if ($success): ?>

        <p>
            Your Admin account is ready. You can now use its email
            and password to sign in.
        </p>

    <?php elseif ($adminExists): ?>

        <p>An Admin account already exists. No additional Admin can be created using this setup page.</p>

    <?php else: ?>

        <form method="POST">
            <?= auth_csrf_field() ?>

            <label for="setup-secret">One-time Setup Secret *</label>
            <input
                type="password"
                id="setup-secret"
                name="setup_secret"
                minlength="64"
                maxlength="64"
                pattern="[A-Fa-f0-9]{64}"
                autocomplete="off"
                required
            >

            <label>First Name *</label>
            <input type="text" name="first_name" required>

            <label>Middle Name</label>
            <input type="text" name="middle_name">

            <label>Last Name *</label>
            <input type="text" name="last_name" required>

            <label>Email Address *</label>
            <input type="email" name="email" required>

            <label>Password *</label>
            <input type="password" name="password" minlength="8" required>

            <label>Confirm Password *</label>
            <input type="password" name="confirm_password" minlength="8" required>

            <button type="submit">Create Admin Account</button>

        </form>

    <?php endif; ?>

</div>

</body>
</html>
