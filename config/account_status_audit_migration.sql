ALTER TABLE users
  MODIFY account_status ENUM('Enabled','Disabled') NULL DEFAULT NULL;

UPDATE users
SET account_status = NULL
WHERE approval_status IN ('Pending','Rejected');

CREATE TABLE user_account_events (
  event_id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  user_id INT NOT NULL,
  admin_user_id INT NULL,
  action ENUM('approved','rejected','disabled','enabled') NOT NULL,
  reason TEXT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (event_id),
  KEY idx_user_account_events_user_created (user_id, created_at),
  CONSTRAINT fk_user_account_events_user
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE RESTRICT,
  CONSTRAINT fk_user_account_events_admin
    FOREIGN KEY (admin_user_id) REFERENCES users(user_id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
