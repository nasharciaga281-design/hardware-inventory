<?php

header('Content-Type: application/json');

require_once __DIR__ . '/../middleware/auth.php';
require_once __DIR__ . '/../helpers/maintenance_sync.php';
auth_json_require_role([HIMS_ROLE_ADMIN, HIMS_ROLE_INVENTORY_MANAGER, HIMS_ROLE_TECHNICIAN]);

try {
    $id = (int) ($_POST['id'] ?? 0);
    $cost = trim($_POST['cost'] ?? '');
    $action = trim($_POST['action'] ?? '');

    if ($id <= 0) {
        throw new Exception('Maintenance record ID is required.');
    }
    if (!is_numeric($cost) || (float) $cost < 0 || (float) $cost > 99999999.99) {
        throw new Exception('Repair cost must be a number of 0 or more.');
    }

    $pdo->beginTransaction();

    $stmt = $pdo->prepare('SELECT hardware_id, status FROM maintenance WHERE maintenance_id = ? FOR UPDATE');
    $stmt->execute([$id]);
    $record = $stmt->fetch();
    if (!$record) {
        throw new Exception('Maintenance record not found.');
    }
    if ($record['status'] === 'Completed') {
        throw new Exception('This repair is already completed.');
    }

    $stmt = $pdo->prepare("UPDATE maintenance SET status = 'Completed', repair_cost = ?, action_taken = ? WHERE maintenance_id = ?");
    $stmt->execute([(float) $cost, $action ?: null, $id]);

    maintenance_sync_hardware_status($pdo, (int) $record['hardware_id']);
    $pdo->commit();

    echo json_encode(['success' => true, 'message' => 'Maintenance marked as completed!']);
} catch (PDOException $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database error. Please try again.']);
} catch (Exception $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
