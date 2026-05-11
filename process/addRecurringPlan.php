<?php
require_once '../database/config.php';

if (session_status() === PHP_SESSION_NONE) { session_start(); }

$pdo = getDB();

$bill_code_id = $_POST['bill_code_id'] ?? null;
$amount       = $_POST['amount']       ?? null;
$frequency    = $_POST['frequency']    ?? null;
$start_date   = $_POST['start_date']   ?? null;

if (!$bill_code_id || !$amount || !$frequency || !$start_date) {
    $_SESSION['error_msg'] = "All fields are required.";
    header("Location: ../views/recurring_bill.php");
    exit;
}

$interval_map = [
    'Monthly'       => 'INTERVAL 1 MONTH',
    'Quarterly'     => 'INTERVAL 3 MONTH',
    'Semi-Annually' => 'INTERVAL 6 MONTH',
    'Annually'      => 'INTERVAL 1 YEAR',
];

if (!isset($interval_map[$frequency])) {
    $_SESSION['error_msg'] = "Invalid frequency selected.";
    header("Location: ../views/recurring_bill.php");
    exit;
}

$interval = $interval_map[$frequency];

try {
    // Row 1: the plan itself (current billing date)
    $stmt = $pdo->prepare("
        INSERT INTO recurring_plans 
            (bill_code_id, amount, frequency, start_date, status, created_by, created_at, updated_at)
        VALUES 
            (:bill_code_id, :amount, :frequency, :start_date, 'active', :created_by, NOW(), NOW())
    ");
    $stmt->execute([
        ':bill_code_id' => $bill_code_id,
        ':amount'       => $amount,
        ':frequency'    => $frequency,
        ':start_date'   => $start_date,
        ':created_by'   => $_SESSION['user_id'] ?? 1,
    ]);

    // Row 2: next billing date (auto-generated)
    $stmt2 = $pdo->prepare("
        INSERT INTO recurring_plans 
            (bill_code_id, amount, frequency, start_date, status, created_by, created_at, updated_at)
        VALUES 
            (:bill_code_id, :amount, :frequency, DATE_ADD(:start_date, $interval), 'active', :created_by, NOW(), NOW())
    ");
    $stmt2->execute([
        ':bill_code_id' => $bill_code_id,
        ':amount'       => $amount,
        ':frequency'    => $frequency,
        ':start_date'   => $start_date,
        ':created_by'   => $_SESSION['user_id'] ?? 1,
    ]);

    $_SESSION['success_msg'] = "Recurring plan created successfully.";
    header("Location: ../views/recurring_bill.php");
    exit;

} catch (PDOException $e) {
    error_log("Add Plan Error: " . $e->getMessage());
    $_SESSION['error_msg'] = "Failed to create plan.";
    header("Location: ../views/recurring_bill.php");
    exit;
}