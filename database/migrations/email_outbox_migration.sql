  CREATE TABLE system_email_outbox (
    email_id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    recipient VARCHAR(254) NOT NULL,
    subject VARCHAR(255) NOT NULL,
    body MEDIUMTEXT NOT NULL,
    highlighted_text TEXT NULL,
    status ENUM('queued','sending','failed') NOT NULL DEFAULT 'queued',
    attempts TINYINT UNSIGNED NOT NULL DEFAULT 0,
    available_at DATETIME NOT NULL,
    locked_at DATETIME NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    last_error VARCHAR(255) NULL,
    PRIMARY KEY (email_id),
    KEY idx_system_email_outbox_ready (status, available_at, email_id),
    KEY idx_system_email_outbox_locked (status, locked_at)
  ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
