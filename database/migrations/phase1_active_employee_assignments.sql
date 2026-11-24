USE `hardware_inventory`;

SELECT a.assignment_id, a.hardware_id, a.user_id
FROM assignments a
LEFT JOIN employees e ON e.user_id = a.user_id
WHERE e.employee_id IS NULL;

DROP PROCEDURE IF EXISTS hims_phase1_precheck;

DELIMITER //
CREATE PROCEDURE hims_phase1_precheck()
BEGIN
    IF EXISTS (
        SELECT 1
        FROM assignments a
        LEFT JOIN employees e ON e.user_id = a.user_id
        WHERE e.employee_id IS NULL
    ) THEN
        SIGNAL SQLSTATE '45000'
            SET MESSAGE_TEXT = 'Cannot migrate assignments: an assignment cannot be mapped to an employee.';
    END IF;
END//
DELIMITER ;

CALL hims_phase1_precheck();

DROP PROCEDURE hims_phase1_precheck;

ALTER TABLE assignments
    ADD COLUMN employee_id BIGINT UNSIGNED NULL AFTER hardware_id;

UPDATE assignments a
INNER JOIN employees e ON e.user_id = a.user_id
SET a.employee_id = e.employee_id;

DROP PROCEDURE IF EXISTS hims_phase1_backfill_check;

DELIMITER //
CREATE PROCEDURE hims_phase1_backfill_check()
BEGIN
    IF EXISTS (SELECT 1 FROM assignments WHERE employee_id IS NULL) THEN
        SIGNAL SQLSTATE '45000'
            SET MESSAGE_TEXT = 'Cannot migrate assignments: employee_id backfill left NULL values.';
    END IF;
END//
DELIMITER ;

CALL hims_phase1_backfill_check();

DROP PROCEDURE hims_phase1_backfill_check;

ALTER TABLE assignments
    ADD KEY idx_assignments_employee_id (employee_id);

ALTER TABLE assignments
    ADD CONSTRAINT fk_assignments_employee
    FOREIGN KEY (employee_id) REFERENCES employees (employee_id)
    ON DELETE RESTRICT ON UPDATE CASCADE;

ALTER TABLE assignments
    DROP FOREIGN KEY fk_assignments_user;

ALTER TABLE assignments
    DROP COLUMN user_id;

ALTER TABLE assignments
    MODIFY employee_id BIGINT UNSIGNED NOT NULL;

SELECT a.assignment_id, a.hardware_id, a.employee_id
FROM assignments a
LEFT JOIN employees e ON e.employee_id = a.employee_id
WHERE e.employee_id IS NULL;
