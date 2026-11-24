<?php

header('Content-Type: application/json');

require_once __DIR__ . '/../middleware/auth.php';
require_once __DIR__ . '/../helpers/maintenance_sync.php';
auth_json_require_role([HIMS_ROLE_ADMIN, HIMS_ROLE_INVENTORY_MANAGER, HIMS_ROLE_TECHNICIAN]);

try {
    $tag = trim($_POST['tag'] ?? '');
    [$date, $cost, $status, $issue, $action] = maintenance_read_input();

    if ($tag === '') {
        throw new Exception('Please select a hardware item.');
    }

    $stmt = $pdo->prepare('SELECT hardware_id FROM hardware WHERE asset_tag = ?');
    $stmt->execute([$tag]);
    $hardwareId = (int) $stmt->fetchColumn();
    if (!$hardwareId) {
        throw new Exception('Hardware not found.');
    }

    $pdo->beginTransaction();

    if ($status === 'Ongoing') {
        $stmt = $pdo->prepare("SELECT 1 FROM maintenance WHERE hardware_id = ? AND status = 'Ongoing' FOR UPDATE");
        $stmt->execute([$hardwareId]);
        if ($stmt->fetchColumn()) {
            throw new Exception('This hardware already has an ongoing repair.');
        }
    }

    $stmt = $pdo->prepare(
        'INSERT INTO maintenance (hardware_id, issue_description, action_taken, maintenance_date, repair_cost, status)
         VALUES (?, ?, ?, ?, ?, ?)'
    );
    $stmt->execute([$hardwareId, $issue, $action ?: null, $date, $cost, $status]);

    maintenance_sync_hardware_status($pdo, $hardwareId);
    $pdo->commit();

    echo json_encode(['success' => true, 'message' => 'Maintenance record successfully saved!']);
} catch (PDOException $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database error. Please try again.']);
} catch (Exception $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
