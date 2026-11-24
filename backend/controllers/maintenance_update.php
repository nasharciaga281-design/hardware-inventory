<?php

header('Content-Type: application/json');

require_once __DIR__ . '/../middleware/auth.php';
require_once __DIR__ . '/../helpers/maintenance_sync.php';
auth_json_require_role([HIMS_ROLE_ADMIN, HIMS_ROLE_INVENTORY_MANAGER, HIMS_ROLE_TECHNICIAN]);

try {
    $id = (int) ($_POST['id'] ?? 0);
    [$date, $cost, $status, $issue, $action] = maintenance_read_input();

    if ($id <= 0) {
        throw new Exception('Maintenance record ID is required.');
    }

    $pdo->beginTransaction();

    $stmt = $pdo->prepare('SELECT hardware_id FROM maintenance WHERE maintenance_id = ? FOR UPDATE');
    $stmt->execute([$id]);
    $hardwareId = (int) $stmt->fetchColumn();
    if (!$hardwareId) {
        throw new Exception('Maintenance record not found.');
    }

    if ($status === 'Ongoing') {
        $stmt = $pdo->prepare("SELECT 1 FROM maintenance WHERE hardware_id = ? AND status = 'Ongoing' AND maintenance_id <> ?");
        $stmt->execute([$hardwareId, $id]);
        if ($stmt->fetchColumn()) {
            throw new Exception('This hardware already has an ongoing repair.');
        }
    }

    $stmt = $pdo->prepare(
        'UPDATE maintenance
         SET issue_description = ?, action_taken = ?, maintenance_date = ?, repair_cost = ?, status = ?
         WHERE maintenance_id = ?'
    );
    $stmt->execute([$issue, $action ?: null, $date, $cost, $status, $id]);

    maintenance_sync_hardware_status($pdo, $hardwareId);
    $pdo->commit();

    echo json_encode(['success' => true, 'message' => 'Maintenance record successfully updated!']);
} catch (PDOException $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database error. Please try again.']);
} catch (Exception $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
