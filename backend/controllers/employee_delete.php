<?php

header('Content-Type: application/json');

require_once __DIR__ . '/../middleware/auth.php';
auth_json_require_role([HIMS_ROLE_ADMIN, HIMS_ROLE_INVENTORY_MANAGER]);

try {
    $id = (int) ($_POST['id'] ?? 0);

    if ($id <= 0) {
        throw new Exception('Employee ID is required.');
    }

    $stmt = $pdo->prepare('DELETE FROM employees WHERE employee_id = ?');
    $stmt->execute([$id]);

    if (!$stmt->rowCount()) {
        throw new Exception('Employee not found.');
    }

    echo json_encode(['success' => true, 'message' => 'Employee successfully deleted!']);
} catch (PDOException $e) {
    if ((int) ($e->errorInfo[1] ?? 0) === 1451) {
        http_response_code(409);
        echo json_encode(['success' => false, 'message' => 'This employee has related records and cannot be deleted.']);
    } else {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Database error. Please try again.']);
    }
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
