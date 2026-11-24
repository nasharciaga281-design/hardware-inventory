<?php

header('Content-Type: application/json');

require_once __DIR__ . '/../middleware/auth.php';
auth_json_require_role([HIMS_ROLE_ADMIN, HIMS_ROLE_INVENTORY_MANAGER]);

try {
    $tag = trim($_POST['tag'] ?? '');

    if ($tag === '') {
        throw new Exception('Asset tag is required.');
    }

    $stmt = $pdo->prepare('DELETE FROM hardware WHERE asset_tag = ?');
    $stmt->execute([$tag]);

    if (!$stmt->rowCount()) {
        throw new Exception('Hardware not found.');
    }

    echo json_encode(['success' => true, 'message' => 'Hardware successfully deleted!']);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database error. Please try again.']);
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
