<?php

header('Content-Type: application/json');

require_once __DIR__ . '/../middleware/auth.php';
auth_json_require_role([HIMS_ROLE_ADMIN, HIMS_ROLE_INVENTORY_MANAGER]);

try {
    $id = (int) ($_POST['id'] ?? 0);

    if ($id <= 0) {
        throw new Exception('Location ID is required.');
    }

    $stmt = $pdo->prepare('SELECT COUNT(*) FROM hardware WHERE location_id = ?');
    $stmt->execute([$id]);
    if ((int) $stmt->fetchColumn() > 0) {
        throw new Exception('This location still holds hardware items. Move them to another location first.');
    }

    $stmt = $pdo->prepare('DELETE FROM locations WHERE location_id = ?');
    $stmt->execute([$id]);

    if (!$stmt->rowCount()) {
        throw new Exception('Location not found.');
    }

    echo json_encode(['success' => true, 'message' => 'Location successfully deleted!']);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database error. Please try again.']);
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
