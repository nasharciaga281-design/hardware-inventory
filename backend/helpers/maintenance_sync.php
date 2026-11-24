<?php

// Keeps hardware.status in step with its repair records:
// any ongoing repair => 'Under Maintenance'; none left => back to 'Assigned' or 'Available'.
function maintenance_sync_hardware_status(PDO $pdo, int $hardwareId): void
{
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM maintenance WHERE hardware_id = ? AND status = 'Ongoing'");
    $stmt->execute([$hardwareId]);

    if ((int) $stmt->fetchColumn() > 0) {
        $pdo->prepare("UPDATE hardware SET status = 'Under Maintenance' WHERE hardware_id = ?")->execute([$hardwareId]);
    } else {
        // Repair finished: back to Assigned if still issued to someone, otherwise Available.
        $stmt = $pdo->prepare('SELECT 1 FROM assignments WHERE hardware_id = ? AND returned_date IS NULL');
        $stmt->execute([$hardwareId]);
        $backTo = $stmt->fetchColumn() ? 'Assigned' : 'Available';

        $pdo->prepare("UPDATE hardware SET status = ? WHERE hardware_id = ? AND status = 'Under Maintenance'")->execute([$backTo, $hardwareId]);
    }
}

// Shared input checks for add and edit.
function maintenance_read_input(): array
{
    $date = trim($_POST['date'] ?? '');
    $cost = trim($_POST['cost'] ?? '');
    $status = trim($_POST['status'] ?? '');
    $issue = trim($_POST['issue'] ?? '');
    $action = trim($_POST['action'] ?? '');

    $parsed = DateTime::createFromFormat('Y-m-d', $date);
    if (!$parsed || $parsed->format('Y-m-d') !== $date) {
        throw new Exception('Please enter a valid date.');
    }
    if (!is_numeric($cost) || (float) $cost < 0 || (float) $cost > 99999999.99) {
        throw new Exception('Repair cost must be a number of 0 or more.');
    }
    if (!in_array($status, ['Ongoing', 'Completed'], true)) {
        throw new Exception('Invalid status.');
    }
    if ($issue === '') {
        throw new Exception('Issue description is required.');
    }

    return [$date, (float) $cost, $status, $issue, $action];
}
