<?php

header('Content-Type: application/json');

require_once __DIR__ . '/../middleware/auth.php';
auth_json_require_role([HIMS_ROLE_ADMIN, HIMS_ROLE_INVENTORY_MANAGER]);

try {
    $id = (int) ($_POST['id'] ?? 0);

    if ($id <= 0) {
        throw new Exception('Department ID is required.');
    }

    // Employees in this department are kept; their department becomes empty (FK ON DELETE SET NULL).
    $stmt = $pdo->prepare('DELETE FROM departments WHERE department_id = ?');
    $stmt->execute([$id]);

    if (!$stmt->rowCount()) {
        throw new Exception('Department not found.');
    }

    echo json_encode(['success' => true, 'message' => 'Department successfully deleted!']);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database error. Please try again.']);
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
