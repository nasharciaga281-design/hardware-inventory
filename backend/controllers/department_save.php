<?php

header('Content-Type: application/json');

require_once __DIR__ . '/../middleware/auth.php';
auth_json_require_role([HIMS_ROLE_ADMIN, HIMS_ROLE_INVENTORY_MANAGER]);

try {
    $name = trim($_POST['name'] ?? '');
    $description = trim($_POST['description'] ?? '');

    if ($name === '') {
        throw new Exception('Department name is required.');
    }
    if (mb_strlen($name) > 100) {
        throw new Exception('Department name must be 100 characters or less.');
    }

    $stmt = $pdo->prepare('INSERT INTO departments (department_name, description) VALUES (?, ?)');
    $stmt->execute([$name, $description ?: null]);

    echo json_encode(['success' => true, 'message' => 'Department successfully saved!', 'id' => (int) $pdo->lastInsertId()]);
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
