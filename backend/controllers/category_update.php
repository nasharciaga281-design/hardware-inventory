<?php

header('Content-Type: application/json');

require_once __DIR__ . '/../middleware/auth.php';
auth_json_require_role([HIMS_ROLE_ADMIN, HIMS_ROLE_INVENTORY_MANAGER]);

try {
    $id = (int) ($_POST['id'] ?? 0);
    $name = trim($_POST['name'] ?? '');
    $description = trim($_POST['description'] ?? '');

    if ($id <= 0) {
        throw new Exception('Category ID is required.');
    }
    if ($name === '') {
        throw new Exception('Category name is required.');
    }
    if (mb_strlen($name) > 100) {
        throw new Exception('Category name must be 100 characters or less.');
    }

    $stmt = $pdo->prepare('SELECT 1 FROM categories WHERE category_id = ?');
    $stmt->execute([$id]);
    if (!$stmt->fetchColumn()) {
        throw new Exception('Category not found.');
    }

    $stmt = $pdo->prepare('SELECT 1 FROM categories WHERE category_name = ? AND category_id <> ?');
    $stmt->execute([$name, $id]);
    if ($stmt->fetchColumn()) {
        throw new Exception('This category already exists.');
    }

    $stmt = $pdo->prepare('UPDATE categories SET category_name = ?, description = ? WHERE category_id = ?');
    $stmt->execute([$name, $description ?: null, $id]);

    echo json_encode(['success' => true, 'message' => 'Category successfully updated!']);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database error. Please try again.']);
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
