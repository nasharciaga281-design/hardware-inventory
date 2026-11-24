-- HIMS Phase 1: normalized role lookup.
-- Apply manually after backing up the database. MySQL DDL auto-commits.
-- Stop if any preflight or postflight verification query returns an unexpected value.
-- The destructive legacy-column drop at the end is intentionally commented out.

-- Preflight: inspect existing values. "unmapped_legacy_roles" must be 0.
SELECT role, COUNT(*) AS total
FROM users
GROUP BY role
ORDER BY role;

SELECT COUNT(*) AS unmapped_legacy_roles
FROM users
WHERE role IS NOT NULL
  AND role NOT IN ('Admin', 'Staff');

-- Create the role lookup table and seed all four roles without duplicates.
CREATE TABLE IF NOT EXISTS roles (
  role_id TINYINT UNSIGNED NOT NULL AUTO_INCREMENT,
  role_name VARCHAR(50) NOT NULL,
  PRIMARY KEY (role_id),
  UNIQUE KEY uq_roles_role_name (role_name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT IGNORE INTO roles (role_name) VALUES
  ('Admin'),
  ('Inventory Manager'),
  ('Technician'),
  ('Staff');

-- Add users.role_id only when it does not already exist.
SET @hims_role_id_exists = (
  SELECT COUNT(*)
  FROM information_schema.COLUMNS
  WHERE TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME = 'users'
    AND COLUMN_NAME = 'role_id'
);

SET @hims_sql = IF(
  @hims_role_id_exists = 0,
  'ALTER TABLE users ADD COLUMN role_id TINYINT UNSIGNED NULL',
  'SELECT 1'
);
PREPARE hims_add_role_id FROM @hims_sql;
EXECUTE hims_add_role_id;
DEALLOCATE PREPARE hims_add_role_id;

-- Map existing roles by name. NULL stays NULL.
UPDATE users u
INNER JOIN roles r ON r.role_name = u.role
SET u.role_id = r.role_id
WHERE u.role_id IS NULL
  AND u.role IN ('Admin', 'Staff');

-- Add a dedicated index for role lookups when missing.
SET @hims_role_index_exists = (
  SELECT COUNT(*)
  FROM information_schema.STATISTICS
  WHERE TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME = 'users'
    AND INDEX_NAME = 'idx_users_role_id'
);

SET @hims_sql = IF(
  @hims_role_index_exists = 0,
  'ALTER TABLE users ADD INDEX idx_users_role_id (role_id)',
  'SELECT 1'
);
PREPARE hims_add_role_index FROM @hims_sql;
EXECUTE hims_add_role_index;
DEALLOCATE PREPARE hims_add_role_index;

-- Add the foreign key when missing. RESTRICT prevents deleting a role still in use.
SET @hims_role_fk_exists = (
  SELECT COUNT(*)
  FROM information_schema.TABLE_CONSTRAINTS
  WHERE CONSTRAINT_SCHEMA = DATABASE()
    AND TABLE_NAME = 'users'
    AND CONSTRAINT_NAME = 'fk_users_role'
    AND CONSTRAINT_TYPE = 'FOREIGN KEY'
);

SET @hims_sql = IF(
  @hims_role_fk_exists = 0,
  'ALTER TABLE users ADD CONSTRAINT fk_users_role FOREIGN KEY (role_id) REFERENCES roles(role_id) ON DELETE RESTRICT ON UPDATE CASCADE',
  'SELECT 1'
);
PREPARE hims_add_role_fk FROM @hims_sql;
EXECUTE hims_add_role_fk;
DEALLOCATE PREPARE hims_add_role_fk;

-- Postflight: review the mapping.
SELECT u.role, r.role_name AS mapped_role, COUNT(*) AS total
FROM users u
LEFT JOIN roles r ON r.role_id = u.role_id
GROUP BY u.role, r.role_name
ORDER BY u.role;

-- Both counts below must be 0 before deploying the PHP changes.
SELECT COUNT(*) AS unmapped_rows
FROM users
WHERE role IS NOT NULL
  AND role_id IS NULL;

SELECT COUNT(*) AS unexpected_role_assignments
FROM users
WHERE role IS NULL
  AND role_id IS NOT NULL;

-- Manual final step after the application has been verified:
-- ALTER TABLE users DROP COLUMN role;
