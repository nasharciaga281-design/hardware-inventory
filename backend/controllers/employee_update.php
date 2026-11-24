<?php

header('Content-Type: application/json');

require_once __DIR__ . '/../middleware/auth.php';
auth_json_require_role([HIMS_ROLE_ADMIN, HIMS_ROLE_INVENTORY_MANAGER]);

try {
    $id = (int) ($_POST['id'] ?? 0);
    $number = trim($_POST['employee_number'] ?? '');
    $first = trim($_POST['first_name'] ?? '');
    $middle = trim($_POST['middle_name'] ?? '');
    $last = trim($_POST['last_name'] ?? '');
    $email = trim($_POST['work_email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $position = trim($_POST['position'] ?? '');
    $status = trim($_POST['employment_status'] ?? 'Active');
    $departmentId = (int) ($_POST['department_id'] ?? 0) ?: null;
    $userId = (int) ($_POST['user_id'] ?? 0) ?: null;

    if ($id <= 0) {
        throw new Exception('Employee ID is required.');
    }
    if ($number === '' || $first === '' || $last === '') {
        throw new Exception('Employee number, first name, and last name are required.');
    }
    if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        throw new Exception('Please enter a valid work email.');
    }
    if (!in_array($status, ['Active', 'Inactive', 'On Leave'], true)) {
        throw new Exception('Invalid employment status.');
    }

    $stmt = $pdo->prepare('SELECT 1 FROM employees WHERE employee_id = ?');
    $stmt->execute([$id]);
    if (!$stmt->fetchColumn()) {
        throw new Exception('Employee not found.');
    }

    $stmt = $pdo->prepare(
        'UPDATE employees SET
            employee_number = ?, first_name = ?, middle_name = ?, last_name = ?, work_email = ?,
            phone = ?, position = ?, employment_status = ?, department_id = ?, user_id = ?
         WHERE employee_id = ?'
    );
    $stmt->execute([
        $number, $first, $middle ?: null, $last, $email ?: null,
        $phone ?: null, $position ?: null, $status, $departmentId, $userId, $id
    ]);

    echo json_encode(['success' => true, 'message' => 'Employee successfully updated!']);
} catch (PDOException $e) {
    $driverCode = (int) ($e->errorInfo[1] ?? 0);
    if ($driverCode === 1062) {
        http_response_code(409);
        $msg = str_contains($e->getMessage(), 'uq_employees_user_id')
            ? 'That user account is already linked to another employee.'
            : 'That employee number already exists.';
        echo json_encode(['success' => false, 'message' => $msg]);
    } elseif ($driverCode === 1452) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Selected department or user account does not exist.']);
    } else {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Database error. Please try again.']);
    }
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
