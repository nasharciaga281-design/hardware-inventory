<?php

header('Content-Type: application/json');

require_once __DIR__ . '/../middleware/auth.php';
auth_json_require_role([HIMS_ROLE_ADMIN, HIMS_ROLE_INVENTORY_MANAGER]);

try {
    $id = (int) ($_POST['id'] ?? 0);

    if ($id <= 0) {
        throw new Exception('Category ID is required.');
    }

    $stmt = $pdo->prepare('SELECT COUNT(*) FROM hardware WHERE category_id = ?');
    $stmt->execute([$id]);
    if ((int) $stmt->fetchColumn() > 0) {
        throw new Exception('This category still has hardware items. Move them to another category first.');
    }

    $stmt = $pdo->prepare('DELETE FROM categories WHERE category_id = ?');
    $stmt->execute([$id]);

    if (!$stmt->rowCount()) {
        throw new Exception('Category not found.');
    }

    echo json_encode(['success' => true, 'message' => 'Category successfully deleted!']);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database error. Please try again.']);
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
