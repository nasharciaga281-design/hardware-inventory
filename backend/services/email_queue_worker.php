<?php

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/mailer.php';

$lock = $pdo->query("SELECT GET_LOCK('hims-email-outbox', 0)")->fetchColumn();
if ((int) $lock !== 1) {
    exit(0);
}

try {
    for ($processed = 0; $processed < 25; $processed++) {
        $pdo->beginTransaction();
        $stmt = $pdo->query(
            "SELECT email_id, recipient, subject, body, highlighted_text, attempts
             FROM system_email_outbox
             WHERE (status = 'queued' AND available_at <= UTC_TIMESTAMP())
                OR (status = 'sending' AND locked_at < DATE_SUB(UTC_TIMESTAMP(), INTERVAL 10 MINUTE))
             ORDER BY email_id
             LIMIT 1
             FOR UPDATE"
        );
        $email = $stmt->fetch();
        if (!$email) {
            $pdo->commit();
            break;
        }

        $attempts = (int) $email['attempts'] + 1;
        $pdo->prepare(
            "UPDATE system_email_outbox
             SET status = 'sending', attempts = ?, locked_at = UTC_TIMESTAMP()
             WHERE email_id = ?"
        )->execute([$attempts, (int) $email['email_id']]);
        $pdo->commit();

        try {
            deliver_system_email(
                $email['recipient'],
                $email['subject'],
                $email['body'],
                $email['highlighted_text']
            );
            $pdo->prepare('DELETE FROM system_email_outbox WHERE email_id = ?')
                ->execute([(int) $email['email_id']]);
        } catch (RuntimeException $exception) {
            error_log('HIMS queued email delivery failed for queue item ' . (int) $email['email_id'] . '.');
            $delay = min(300, 60 * (2 ** min($attempts - 1, 3)));
            if ($attempts >= 8) {
                $pdo->prepare(
                    "UPDATE system_email_outbox
                     SET status = 'failed', locked_at = NULL, last_error = 'SMTP delivery failed',
                         body = '', highlighted_text = NULL
                     WHERE email_id = ?"
                )->execute([(int) $email['email_id']]);
            } else {
                $pdo->prepare(
                    "UPDATE system_email_outbox
                     SET status = 'queued', locked_at = NULL,
                         available_at = TIMESTAMPADD(SECOND, ?, UTC_TIMESTAMP()),
                         last_error = 'SMTP delivery failed'
                     WHERE email_id = ?"
                )->execute([$delay, (int) $email['email_id']]);
            }
        }
    }
} finally {
    $pdo->query("SELECT RELEASE_LOCK('hims-email-outbox')");
}
