<?php

header('Content-Type: application/json');

require_once __DIR__ . '/../middleware/auth.php';
auth_json_require_role([HIMS_ROLE_ADMIN, HIMS_ROLE_INVENTORY_MANAGER]);

try {
    $id = (int) ($_POST['id'] ?? 0);
    $returned = trim($_POST['returned'] ?? '');

    if ($id <= 0) {
        throw new Exception('Assignment ID is required.');
    }
    $date = DateTime::createFromFormat('Y-m-d', $returned);
    if (!$date || $date->format('Y-m-d') !== $returned) {
        throw new Exception('Please enter a valid return date.');
    }
    if ($returned > date('Y-m-d', strtotime('+1 day'))) {
        throw new Exception('Return date cannot be in the future.');
    }

    $pdo->beginTransaction();

    $stmt = $pdo->prepare('SELECT hardware_id, assigned_date, returned_date FROM assignments WHERE assignment_id = ? FOR UPDATE');
    $stmt->execute([$id]);
    $assignment = $stmt->fetch();
    if (!$assignment) {
        throw new Exception('Assignment not found.');
    }
    if ($assignment['returned_date'] !== null) {
        throw new Exception('This hardware was already returned.');
    }
    if ($returned < $assignment['assigned_date']) {
        throw new Exception("Return date can't be before the assigned date.");
    }

    $pdo->prepare('UPDATE assignments SET returned_date = ? WHERE assignment_id = ?')->execute([$returned, $id]);

    // Back to Available, unless it is currently in repair (maintenance handles that status).
    $pdo->prepare("UPDATE hardware SET status = 'Available' WHERE hardware_id = ? AND status = 'Assigned'")
        ->execute([(int) $assignment['hardware_id']]);

    $pdo->commit();

    echo json_encode(['success' => true, 'message' => 'Hardware marked as returned!']);
} catch (PDOException $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database error. Please try again.']);
} catch (Exception $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
