<?php

header('Content-Type: application/json');

require_once __DIR__ . '/../middleware/auth.php';
auth_json_require_role([HIMS_ROLE_ADMIN, HIMS_ROLE_INVENTORY_MANAGER]);

try {
    $id = (int) ($_POST['id'] ?? 0);
    $name = trim($_POST['name'] ?? '');
    $description = trim($_POST['description'] ?? '');

    if ($id <= 0) {
        throw new Exception('Department ID is required.');
    }
    if ($name === '') {
        throw new Exception('Department name is required.');
    }
    if (mb_strlen($name) > 100) {
        throw new Exception('Department name must be 100 characters or less.');
    }

    $stmt = $pdo->prepare('SELECT 1 FROM departments WHERE department_id = ?');
    $stmt->execute([$id]);
    if (!$stmt->fetchColumn()) {
        throw new Exception('Department not found.');
    }

    $stmt = $pdo->prepare('UPDATE departments SET department_name = ?, description = ? WHERE department_id = ?');
    $stmt->execute([$name, $description ?: null, $id]);

    echo json_encode(['success' => true, 'message' => 'Department successfully updated!']);
} catch (PDOException $e) {
    if ($e->getCode() == '23000') {
        http_response_code(409);
        echo json_encode(['success' => false, 'message' => 'That department already exists.']);
    } else {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Database error. Please try again.']);
    }
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
