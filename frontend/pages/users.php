<?php
require_once __DIR__ . '/../../backend/middleware/auth.php';
require_once __DIR__ . '/../../backend/services/mailer.php';
auth_require_admin();

$pageTitle = "User Management";
$pageDescription = "Manage system accounts and staff approvals";
$pageStyles = ["users"];

// Escape output before displaying database values.
function e($value) {
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

$csrfToken = auth_csrf_token();

$roleStmt = $pdo->query(
    "SELECT role_id, role_name
     FROM roles
     ORDER BY FIELD(role_name, 'Admin', 'Inventory Manager', 'Technician', 'Staff')"
);
$roles = [];
foreach ($roleStmt->fetchAll(PDO::FETCH_ASSOC) as $role) {
    $roles[(int) $role['role_id']] = $role['role_name'];
}

// Handle account actions.
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    if (!auth_valid_csrf()) {
        $_SESSION['user_management_message'] = [
            'type' => 'error',
            'text' => 'Invalid request. Please try again.'
        ];

        header('Location: users.php');
        exit;
    }

    $action = $_POST['action'] ?? '';
    $targetId = filter_input(INPUT_POST, 'user_id', FILTER_VALIDATE_INT);

    try {
        if (!$targetId || $targetId < 1) {
            throw new Exception('Invalid user account.');
        }

        // Get the account being modified.
        $stmt = $pdo->prepare(
            "SELECT u.user_id, u.first_name, u.email, u.email_verified_at,
                    u.role_id, r.role_name, u.approval_status, u.account_status
             FROM users u
             LEFT JOIN roles r ON r.role_id = u.role_id
             WHERE u.user_id = :user_id
             LIMIT 1"
        );

        $stmt->execute(['user_id' => $targetId]);
        $targetUser = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$targetUser) {
            throw new Exception('User account not found.');
        }

        // Assign the Admin-selected role while approving a verified registration.
        if ($action === 'approve') {
            $roleId = filter_input(INPUT_POST, 'role_id', FILTER_VALIDATE_INT);
            if ($targetUser['approval_status'] !== 'Pending' ||
                $targetUser['email_verified_at'] === null ||
                $roleId === false || $roleId === null || $roleId < 1 ||
                !isset($roles[$roleId])) {
                throw new Exception('Select a role for a verified registration awaiting approval.');
            }

            $roleName = $roles[$roleId];

            $pdo->beginTransaction();
            $stmt = $pdo->prepare(
                "UPDATE users
                 SET role_id = :role_id, approval_status = 'Approved', account_status = 'Enabled'
                 WHERE user_id = :user_id
                   AND email_verified_at IS NOT NULL
                   AND approval_status = 'Pending'"
            );
            $stmt->execute(['role_id' => $roleId, 'user_id' => $targetId]);
            if ($stmt->rowCount() !== 1) {
                throw new Exception('The account could not be approved.');
            }
            $pdo->prepare(
                "INSERT INTO user_account_events (user_id, admin_user_id, action)
                 VALUES (?, ?, 'approved')"
            )->execute([$targetId, (int) $_SESSION['user_id']]);
            queue_system_email(
                $targetUser['email'],
                'Your HIMS account has been approved',
                "Hello {$targetUser['first_name']},\n\nYour Hardware Inventory Management System account has been approved with the {$roleName} role. You may now sign in."
            );
            $pdo->commit();

            $message = 'Account approved and notification email queued.';

            $_SESSION['user_management_message'] = [
                'type' => 'success',
                'text' => $message
            ];
        }

        // Reject a verified registration awaiting approval.
        elseif ($action === 'reject') {
            $reason = trim(is_string($_POST['reason'] ?? null) ? $_POST['reason'] : '');
            if ($targetUser['approval_status'] !== 'Pending' ||
                $targetUser['email_verified_at'] === null) {
                throw new Exception('This account is not awaiting approval.');
            }
            if ($reason === '' || mb_strlen($reason, 'UTF-8') > 2000) {
                throw new Exception('Provide a rejection reason of 1 to 2000 characters.');
            }

            $pdo->beginTransaction();
            $stmt = $pdo->prepare(
                "UPDATE users
                 SET approval_status = 'Rejected', account_status = NULL
                 WHERE user_id = :user_id
                   AND email_verified_at IS NOT NULL
                   AND approval_status = 'Pending'"
            );

            $stmt->execute(['user_id' => $targetId]);

            if ($stmt->rowCount() !== 1) {
                throw new Exception('The account could not be rejected.');
            }
            $pdo->prepare(
                "INSERT INTO user_account_events (user_id, admin_user_id, action, reason)
                 VALUES (?, ?, 'rejected', ?)"
            )->execute([$targetId, (int) $_SESSION['user_id'], $reason]);
            queue_system_email(
                $targetUser['email'],
                'HIMS account registration update',
                "Hello {$targetUser['first_name']},\n\nYour Hardware Inventory Management System registration was not approved.\n\n{{REASON}}\n\nPlease contact the system administrator if you have questions.",
                $reason
            );
            $pdo->commit();

            $message = 'Registration rejected and notification email queued.';

            $_SESSION['user_management_message'] = [
                'type' => 'success',
                'text' => $message
            ];
        }

        // ENABLE or DISABLE an approved account.
        elseif ($action === 'toggle_account') {

            if ($targetUser['approval_status'] !== 'Approved') {
                throw new Exception(
                    'Only approved accounts can be enabled or disabled.'
                );
            }

            // Prevent an Admin from disabling their own account.
            if ((int) $targetUser['user_id'] === (int) $_SESSION['user_id']) {
                throw new Exception('You cannot disable your own account.');
            }

            $currentStatus = $targetUser['account_status'];
            if (!in_array($currentStatus, ['Enabled', 'Disabled'], true)) {
                throw new Exception('The account status is invalid. Ask an administrator to review this account.');
            }
            $newStatus = $currentStatus === 'Enabled' ? 'Disabled' : 'Enabled';
            $reason = trim(is_string($_POST['reason'] ?? null) ? $_POST['reason'] : '');
            if ($newStatus === 'Disabled' && ($reason === '' || mb_strlen($reason, 'UTF-8') > 2000)) {
                throw new Exception('Provide a disabling reason of 1 to 2000 characters.');
            }

            // Do not allow disabling the last enabled Admin.
            if (
                $targetUser['role_name'] === HIMS_ROLE_ADMIN &&
                $newStatus === 'Disabled'
            ) {
                $stmt = $pdo->prepare(
                    "SELECT COUNT(*)
                     FROM users u
                     INNER JOIN roles r ON r.role_id = u.role_id
                     WHERE r.role_name = :role_name
                     AND u.approval_status = 'Approved'
                     AND u.account_status = 'Enabled'
                     AND u.user_id != :user_id"
                );

                $stmt->execute([
                    'role_name' => HIMS_ROLE_ADMIN,
                    'user_id' => $targetId
                ]);
                $otherEnabledAdmins = (int) $stmt->fetchColumn();

                if ($otherEnabledAdmins < 1) {
                    throw new Exception(
                        'You cannot disable the last enabled Admin account.'
                    );
                }
            }

            $stmt = $pdo->prepare(
                "UPDATE users
                 SET account_status = :account_status
                 WHERE user_id = :user_id
                 AND approval_status = 'Approved'
                 AND account_status = :current_status"
            );

            $pdo->beginTransaction();
            $stmt->execute([
                'account_status' => $newStatus,
                'user_id' => $targetId,
                'current_status' => $currentStatus
            ]);
            if ($stmt->rowCount() !== 1) {
                throw new Exception('The account status changed before this request completed. Refresh and try again.');
            }
            $pdo->prepare(
                'INSERT INTO user_account_events (user_id, admin_user_id, action, reason)
                 VALUES (?, ?, ?, ?)'
            )->execute([
                $targetId,
                (int) $_SESSION['user_id'],
                strtolower($newStatus),
                $newStatus === 'Disabled' ? $reason : null
            ]);
            $subject = $newStatus === 'Disabled'
                ? 'Your HIMS account has been disabled'
                : 'Your HIMS account access has been restored';
            $body = $newStatus === 'Disabled'
                ? "Hello {$targetUser['first_name']},\n\nYour Hardware Inventory Management System account has been disabled.\n\n{{REASON}}\n\nPlease contact the system administrator if you have questions."
                : "Hello {$targetUser['first_name']},\n\nYour Hardware Inventory Management System account has been re-enabled. You may sign in again.";
            queue_system_email(
                $targetUser['email'],
                $subject,
                $body,
                $newStatus === 'Disabled' ? $reason : null
            );  
            $pdo->commit();

            $message = 'Account status changed to ' . $newStatus . ' and notification email queued.';
            $_SESSION['user_management_message'] = ['type' => 'success', 'text' => $message];
        }

        else {
            throw new Exception('Unknown account action.');
        }

    } catch (PDOException $exception) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        error_log('HIMS user-management database action failed: ' . $exception->getMessage());

        // Show only the SQLSTATE code (never the raw message) plus a hint for the
        // two most common setup problems, so an Admin can tell what to fix.
        $sqlState = (string) $exception->getCode();
        $hint = '';
        if ($sqlState === '42S02') {
            $hint = ' A database table is missing. Run config/account_status_audit_migration.sql, config/email_outbox_migration.sql, and config/role_normalization_migration.sql.';
        } elseif ($sqlState === '23000') {
            $hint = ' A database constraint rejected the change. Run config/account_status_audit_migration.sql and config/role_normalization_migration.sql.';
        }
        $_SESSION['user_management_message'] = [
            'type' => 'error',
            'text' => 'Unable to update the account (database error ' . $sqlState . ').' . $hint
                . ' Details are in the PHP error log.'
        ];
    } catch (RuntimeException $exception) {
        // Thrown by queue_system_email() when SMTP/app URL settings are missing.
        // The whole action is rolled back so the user is never changed without a notice.
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        error_log('HIMS user-management email queue failed: ' . $exception->getMessage());
        $_SESSION['user_management_message'] = [
            'type' => 'error',
            'text' => 'Nothing was changed because the notification email could not be queued. '
                . 'Check the HIMS_SMTP_* and HIMS_APP_URL settings.'
        ];
    } catch (Exception $exception) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }

        $_SESSION['user_management_message'] = [
            'type' => 'error',
            'text' => $exception->getMessage()
        ];
    }

    header('Location: users.php');
    exit;
}

// Retrieve the latest message, if any.
$message = $_SESSION['user_management_message'] ?? null;
unset($_SESSION['user_management_message']);

// Retrieve statistics.
$statsQuery = $pdo->prepare(
    "SELECT
        COUNT(*) AS total_users,
        COALESCE(SUM(r.role_name = :admin_role), 0) AS total_admins,
        COALESCE(SUM(u.approval_status = 'Pending'), 0) AS pending_users,
        COALESCE(
            SUM(
                u.approval_status = 'Approved'
                AND u.account_status = 'Enabled'
            ), 0
        ) AS enabled_users
     FROM users u
     LEFT JOIN roles r ON r.role_id = u.role_id"
);
$statsQuery->execute(['admin_role' => HIMS_ROLE_ADMIN]);

$stats = $statsQuery->fetch(PDO::FETCH_ASSOC);

// Retrieve all user accounts.
$usersQuery = $pdo->query(
    "SELECT
        u.user_id,
        u.first_name,
        u.middle_name,
        u.last_name,
        u.email,
        r.role_name,
        u.approval_status,
        u.account_status,
        u.created_at
     FROM users u
     LEFT JOIN roles r ON r.role_id = u.role_id
     ORDER BY
        CASE
            WHEN u.approval_status = 'Pending' THEN 0
            ELSE 1
        END,
        u.created_at DESC"
);

$users = $usersQuery->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <?php include "../includes/head.php"; ?>

    <style>
        .user-status {
            display: inline-block;
            padding: 5px 10px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            white-space: nowrap;
        }

        .user-status.pending {
            background: #fff4d6;
            color: #946200;
        }

        .user-status.approved,
        .user-status.enabled {
            background: #e3f6e9;
            color: #24733d;
        }

        .user-status.rejected,
        .user-status.disabled {
            background: #fde8e8;
            color: #a83232;
        }

        .user-actions {
            display: flex;
            align-items: center;
            justify-content: flex-end;
            gap: 6px;
            flex-wrap: wrap;
        }

        .user-action {
            border: 1px solid #d7d7d7;
            border-radius: 6px;
            padding: 7px 10px;
            background: #fff;
            color: #333;
            font-size: 12px;
            cursor: pointer;
        }

        .user-action.approve {
            background: #e3f6e9;
            border-color: #c5e8cf;
            color: #24733d;
        }

        .user-action.reject {
            background: #fde8e8;
            border-color: #f3cccc;
            color: #a83232;
        }

        .user-role-select {
            min-height: 32px;
            border: 1px solid #d7d7d7;
            border-radius: 6px;
            padding: 5px 8px;
            background: #fff;
            color: #333;
            font-size: 12px;
        }

        .user-action:hover {
            filter: brightness(0.96);
        }

        .user-message {
            padding: 12px 16px;
            border-radius: 7px;
            margin-bottom: 18px;
            font-size: 14px;
        }

        .user-message.success {
            background: #e3f6e9;
            color: #24733d;
        }

        .user-message.error {
            background: #fde8e8;
            color: #a83232;
        }

        .user-name {
            font-weight: 600;
        }

        .user-email {
            color: #777;
            font-size: 12px;
        }

        .empty-users {
            text-align: center;
            padding: 30px;
            color: #777;
        }

        .user-reason-dialog { padding: 0; }
        .user-reason-label { display: block; margin-top: 16px; font-size: 14px; font-weight: 600; }
        .user-reason-dialog textarea {
            box-sizing: border-box;
            width: 100%;
            min-height: 120px;
            margin-top: 7px;
            padding: 10px;
            border: 1px solid #d7d7d7;
            border-radius: 6px;
            font: inherit;
            resize: vertical;
        }
    </style>
</head>

<body class="dashboard-page">

    <?php include "../includes/sidebar.php"; ?>

    <main class="main-content">

        <?php include "../includes/topbar.php"; ?>

        <section class="content-area">

            <?php if ($message): ?>
                <div class="user-message <?= e($message['type']) ?>">
                    <?= e($message['text']) ?>
                </div>
            <?php endif; ?>

            <!-- Statistics -->
            <div class="stat-grid">

                <article class="stat-card">
                    <div class="stat-top">
                        <span>Total Users</span>
                    </div>
                    <h3><?= (int) $stats['total_users'] ?></h3>
                </article>

                <article class="stat-card">
                    <div class="stat-top">
                        <span>Administrators</span>
                    </div>
                    <h3><?= (int) $stats['total_admins'] ?></h3>
                </article>

                <article class="stat-card">
                    <div class="stat-top">
                        <span>Pending Approvals</span>
                    </div>
                    <h3><?= (int) $stats['pending_users'] ?></h3>
                </article>

                <article class="stat-card">
                    <div class="stat-top">
                        <span>Enabled Accounts</span>
                    </div>
                    <h3><?= (int) $stats['enabled_users'] ?></h3>
                </article>

            </div>

            <!-- User Table -->
            <div class="table-card">

                <div class="table-toolbar">

                    <label class="search-field">
                        <i class="bi bi-search"></i>
                        <input
                            type="search"
                            id="searchInput"
                            placeholder="Search name or email..."
                        >
                    </label>

                </div>

                <div class="table-container">

                    <table class="data-table">

                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>Email</th>
                                <th>Role</th>
                                <th>Approval</th>
                                <th>Account Status</th>
                                <th>Date Registered</th>
                                <th class="align-right">Actions</th>
                            </tr>
                        </thead>

                        <tbody id="userBody">

                            <?php if (empty($users)): ?>

                                <tr>
                                    <td colspan="7" class="empty-users">
                                        No user accounts found.
                                    </td>
                                </tr>

                            <?php else: ?>

                                <?php foreach ($users as $user): ?>

                                    <?php
                                    $fullName = trim(
                                        $user['first_name'] . ' ' .
                                        ($user['middle_name'] ?? '') . ' ' .
                                        $user['last_name']
                                    );
                                    ?>

                                    <tr class="user-row">

                                        <td>
                                            <span class="user-name">
                                                <?= e($fullName) ?>
                                            </span>
                                        </td>

                                        <td>
                                            <?= e($user['email']) ?>
                                        </td>

                                        <td>
                                            <?= !empty($user['role_name']) ? e($user['role_name']) : 'Unassigned' ?>
                                        </td>

                                        <td>
                                            <span class="user-status <?= strtolower(e($user['approval_status'])) ?>">
                                                <?= e($user['approval_status']) ?>
                                            </span>
                                        </td>

                                        <td>
                                            <span class="user-status <?= $user['account_status'] !== null ? strtolower(e($user['account_status'])) : '' ?>">
                                                <?= $user['account_status'] !== null ? e($user['account_status']) : 'N/A' ?>
                                            </span>
                                        </td>

                                        <td>
                                            <?= e(date(
                                                'M d, Y',
                                                strtotime($user['created_at'])
                                            )) ?>
                                        </td>

                                        <td class="align-right">

                                            <div class="user-actions">

                                                <?php if ($user['approval_status'] === 'Pending'): ?>

                                                    <form method="POST" action="users.php" data-loading-form>
                                                        <input
                                                            type="hidden"
                                                            name="csrf_token"
                                                            value="<?= e($csrfToken) ?>"
                                                        >
                                                        <input
                                                            type="hidden"
                                                            name="user_id"
                                                            value="<?= (int) $user['user_id'] ?>"
                                                        >
                                                        <input
                                                            type="hidden"
                                                            name="action"
                                                            value="approve"
                                                        >
                                                        <select class="user-role-select" name="role_id" aria-label="Assign account role" required>
                                                            <option value="" selected disabled>Assign role</option>
                                                            <?php foreach ($roles as $roleId => $roleName): ?>
                                                                <option value="<?= (int) $roleId ?>"><?= e($roleName) ?></option>
                                                            <?php endforeach; ?>
                                                        </select>

                                                        <button
                                                            type="submit"
                                                            class="user-action approve"
                                                            data-loading-text="Approving..."
                                                        >
                                                            Approve
                                                        </button>
                                                    </form>

                                                    <button
                                                        type="button"
                                                        class="user-action reject"
                                                        data-reason-action="reject"
                                                        data-user-id="<?= (int) $user['user_id'] ?>"
                                                        data-user-name="<?= e($fullName) ?>"
                                                    >Reject</button>

                                                <?php elseif ($user['approval_status'] === 'Approved'): ?>

                                                    <?php if ((int) $user['user_id'] !== (int) $_SESSION['user_id']): ?>

                                                        <?php if ($user['account_status'] === 'Enabled'): ?>
                                                            <button
                                                                type="button"
                                                                class="user-action"
                                                                data-reason-action="disable"
                                                                data-user-id="<?= (int) $user['user_id'] ?>"
                                                                data-user-name="<?= e($fullName) ?>"
                                                            >Disable</button>
                                                        <?php else: ?>
                                                            <form method="POST" action="users.php" data-loading-form
                                                                onsubmit="return confirm('Re-enable this account? A notification email will be sent.');">
                                                                <input type="hidden" name="csrf_token" value="<?= e($csrfToken) ?>">
                                                                <input type="hidden" name="user_id" value="<?= (int) $user['user_id'] ?>">
                                                                <input type="hidden" name="action" value="toggle_account">
                                                                <button type="submit" class="user-action" data-loading-text="Enabling...">Enable</button>
                                                            </form>
                                                        <?php endif; ?>

                                                    <?php else: ?>

                                                        <span class="user-email">Current account</span>

                                                    <?php endif; ?>

                                                <?php else: ?>

                                                    <span class="user-email">No actions</span>

                                                <?php endif; ?>

                                            </div>

                                        </td>

                                    </tr>

                                <?php endforeach; ?>

                            <?php endif; ?>

                        </tbody>

                    </table>

                </div>
            </div>

        </section>

    </main>

    <dialog class="modal modal-small user-reason-dialog" id="user-reason-dialog" aria-labelledby="user-reason-heading">
        <form method="POST" action="users.php" class="modal-body" id="user-reason-form" data-loading-form>
            <h2 id="user-reason-heading">Provide a reason</h2>
            <p class="dialog-text" id="user-reason-description"></p>
            <input type="hidden" name="csrf_token" value="<?= e($csrfToken) ?>">
            <input type="hidden" name="user_id" id="reason-user-id">
            <input type="hidden" name="action" id="reason-action">
            <label class="user-reason-label" for="user-action-reason">Reason (required)</label>
            <textarea id="user-action-reason" name="reason" maxlength="2000" required></textarea>
            <div class="modal-actions">
                <button type="button" class="user-action" id="cancel-user-reason">Cancel</button>
                <button type="submit" class="user-action reject" id="submit-user-reason" data-loading-text="Processing...">Continue</button>
            </div>
        </form>
    </dialog>

    <script>
        // Search users by name or email.
        const searchInput = document.getElementById('searchInput');
        const userRows = document.querySelectorAll('.user-row');

        searchInput.addEventListener('input', function () {
            const searchValue = this.value.toLowerCase().trim();

            userRows.forEach(row => {
                const rowText = row.textContent.toLowerCase();
                row.style.display = rowText.includes(searchValue) ? '' : 'none';
            });
        });

        const reasonDialog = document.getElementById('user-reason-dialog');
        const reasonForm = document.getElementById('user-reason-form');
        const reasonField = document.getElementById('user-action-reason');
        document.querySelectorAll('[data-reason-action]').forEach(button => {
            button.addEventListener('click', () => {
                const action = button.dataset.reasonAction;
                const isRejection = action === 'reject';
                document.getElementById('reason-user-id').value = button.dataset.userId;
                document.getElementById('reason-action').value = isRejection ? 'reject' : 'toggle_account';
                document.getElementById('user-reason-heading').textContent =
                    isRejection ? 'Reject registration' : 'Disable account';
                document.getElementById('user-reason-description').textContent =
                    `${button.dataset.userName}: ${isRejection ? 'provide a reason for rejection.' : 'provide a reason for disabling this account.'}`;
                reasonField.value = '';
                reasonField.placeholder = isRejection ? 'Why is this registration being rejected?' : 'Why is this account being disabled?';
                document.getElementById('submit-user-reason').textContent =
                    isRejection ? 'Reject registration' : 'Disable account';
                reasonDialog.showModal();
                reasonField.focus();
            });
        });
        document.getElementById('cancel-user-reason').addEventListener('click', () => reasonDialog.close());
        reasonDialog.addEventListener('close', () => reasonForm.reset());
        // Restore buttons if the page is shown again from the browser's back/forward cache.
        window.addEventListener('pageshow', event => {
            if (!event.persisted) return;
            document.querySelectorAll('form[data-loading-form]').forEach(form => {
                delete form.dataset.submitting;
                const button = form.querySelector('button[type="submit"]');
                if (button) {
                    button.disabled = false;
                    button.removeAttribute('aria-busy');
                    button.classList.remove('is-loading');
                }
            });
            reasonForm.reset();
        });

        document.querySelectorAll('form[data-loading-form]').forEach(form => {
            form.addEventListener('submit', event => {
                if (event.defaultPrevented || form.dataset.submitting === 'true') {
                    event.preventDefault();
                    return;
                }
                form.dataset.submitting = 'true';
                const button = form.querySelector('button[type="submit"]');
                if (button) {
                    button.disabled = true;
                    button.setAttribute('aria-busy', 'true');
                    button.classList.add('is-loading');
                    button.textContent = button.dataset.loadingText || 'Processing...';
                }
            });
        });
    </script>

</body>
</html>