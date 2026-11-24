<?php

function active_employee_select_sql(): string
{
    return "SELECT e.employee_id AS id,
                   CONCAT_WS(' ', e.first_name, e.middle_name, e.last_name) AS name,
                   COALESCE(NULLIF(e.work_email, ''), u.email, '') AS email
            FROM employees e
            LEFT JOIN users u ON u.user_id = e.user_id
            WHERE e.employment_status = 'Active'
              AND (
                  e.user_id IS NULL
                  OR (
                      u.email_verified_at IS NOT NULL
                      AND u.approval_status = 'Approved'
                      AND u.account_status = 'Enabled'
                  )
              )";
}

function active_employee_options(PDO $pdo): array
{
    $rows = $pdo->query(active_employee_select_sql() . ' ORDER BY e.first_name, e.last_name, e.employee_id')
        ->fetchAll(PDO::FETCH_ASSOC);

    return array_map(static function (array $row): array {
        $row['id'] = (int) $row['id'];
        return $row;
    }, $rows);
}

function active_employee_for_update(PDO $pdo, int $employeeId): ?array
{
    $stmt = $pdo->prepare(
        "SELECT e.employee_id, e.employment_status, e.user_id,
                u.email_verified_at, u.approval_status, u.account_status
         FROM employees e
         LEFT JOIN users u ON u.user_id = e.user_id
         WHERE e.employee_id = ?
         FOR UPDATE"
    );
    $stmt->execute([$employeeId]);
    $employee = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$employee || $employee['employment_status'] !== 'Active') {
        return null;
    }
    if (
        $employee['user_id'] !== null
        && (
            $employee['email_verified_at'] === null
            || $employee['approval_status'] !== 'Approved'
            || $employee['account_status'] !== 'Enabled'
        )
    ) {
        return null;
    }

    return $employee;
}
