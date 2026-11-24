<?php

header('Content-Type: application/json');

require_once __DIR__ . '/../middleware/auth.php';
require_once __DIR__ . '/../helpers/active_employees.php';
auth_json_require_role([HIMS_ROLE_ADMIN, HIMS_ROLE_INVENTORY_MANAGER]);

function valid_date(string $value): bool
{
    $date = DateTime::createFromFormat('Y-m-d', $value);
    return $date && $date->format('Y-m-d') === $value;
}

try {
    $tag = trim($_POST['tag'] ?? '');
    $employeeId = (int) ($_POST['employee_id'] ?? 0);
    $assigned = trim($_POST['assigned'] ?? '');
    $expected = trim($_POST['expected'] ?? '');

    if ($tag === '') {
        throw new Exception('Please select a hardware item.');
    }
    if ($employeeId <= 0) {
        throw new Exception('Please select who the hardware is assigned to.');
    }
    if (!valid_date($assigned) || !valid_date($expected)) {
        throw new Exception('Please enter valid dates.');
    }
    if ($expected < $assigned) {
        throw new Exception("Expected return can't be before the assigned date.");
    }

    $pdo->beginTransaction();

    $stmt = $pdo->prepare('SELECT hardware_id, status FROM hardware WHERE asset_tag = ? FOR UPDATE');
    $stmt->execute([$tag]);
    $hardware = $stmt->fetch();
    if (!$hardware) {
        throw new Exception('Hardware not found.');
    }
    if ($hardware['status'] !== 'Available') {
        throw new Exception('This hardware is not available (current status: ' . $hardware['status'] . ').');
    }

    $employee = active_employee_for_update($pdo, $employeeId);
    if (!$employee) {
        throw new Exception('Selected employee is not active or has no active account.');
    }

    $stmt = $pdo->prepare(
        'INSERT INTO assignments (hardware_id, employee_id, assigned_date, expected_return) VALUES (?, ?, ?, ?)'
    );
    $stmt->execute([(int) $hardware['hardware_id'], $employeeId, $assigned, $expected]);

    $pdo->prepare("UPDATE hardware SET status = 'Assigned' WHERE hardware_id = ?")
        ->execute([(int) $hardware['hardware_id']]);

    $pdo->commit();

    echo json_encode(['success' => true, 'message' => 'Assignment successfully created!']);
} catch (PDOException $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database error. Please try again.']);
} catch (Exception $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
