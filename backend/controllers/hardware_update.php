<?php

header('Content-Type: application/json');

require_once __DIR__ . '/../middleware/auth.php';
auth_json_require_role([HIMS_ROLE_ADMIN, HIMS_ROLE_INVENTORY_MANAGER]);

try {
    $tag = trim($_POST['tag'] ?? '');
    $name = trim($_POST['name'] ?? '');
    $category = trim($_POST['category'] ?? '');
    $location = trim($_POST['location'] ?? '');
    $brand = trim($_POST['brand'] ?? '');
    $model = trim($_POST['model'] ?? '');
    $serial = trim($_POST['serial'] ?? '');
    $purchaseDate = $_POST['purchaseDate'] ?: null;
    $purchaseCost = $_POST['purchaseCost'] ?: null;
    $condition = $_POST['condition'] ?? 'Good';
    $status = $_POST['status'] ?? 'Available';
    $description = trim($_POST['description'] ?? '');

    if ($tag === '' || $name === '' || $category === '' || $location === '') {
        throw new Exception('Please complete all required fields.');
    }

    $stmt = $pdo->prepare('SELECT category_id FROM categories WHERE category_name = ?');
    $stmt->execute([$category]);
    $categoryId = $stmt->fetchColumn();

    $stmt = $pdo->prepare('SELECT location_id FROM locations WHERE location_name = ?');
    $stmt->execute([$location]);
    $locationId = $stmt->fetchColumn();

    if (!$categoryId || !$locationId) {
        throw new Exception('Invalid category or location.');
    }

    $stmt = $pdo->prepare('UPDATE hardware SET hardware_name = ?, category_id = ?, location_id = ?, brand = ?, model = ?, serial_number = ?, purchase_date = ?, purchase_cost = ?, condition_status = ?, status = ?, description = ? WHERE asset_tag = ?');
    $stmt->execute([$name, $categoryId, $locationId, $brand ?: null, $model ?: null, $serial ?: null, $purchaseDate, $purchaseCost, $condition, $status, $description ?: null, $tag]);

    echo json_encode(['success' => true, 'message' => 'Hardware successfully updated!']);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database error. Please try again.']);
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
