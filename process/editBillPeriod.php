<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }
require_once '../database/config.php';

$pdo = getDB();


if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $period_id  = $_POST['period_id'];
    $bp_code    = trim($_POST['period_code']);
    $start_date = $_POST['date_from'];
    $end_date   = $_POST['date_to'];
    $status     = $_POST['status'] ?? 'open'; 
    $updated_at = date('Y-m-d H:i:s');

    $sql = "UPDATE bill_periods 
            SET bp_code = :bp_code, start_date = :start_date, end_date = :end_date, updated_at = :updated_at";
    
    $params = [
        ':bp_code'    => $bp_code,
        ':start_date' => $start_date,
        ':end_date'   => $end_date,
        ':updated_at' => $updated_at,
        ':period_id'  => $period_id
    ];

    // If the action was to close the period, append those fields
    if ($status === 'closed') {
        $sql .= ", status = 'closed', closed_by = :closed_by, closed_at = :closed_at";
        $params[':closed_by'] = $_SESSION['user_id'] ?? 1;
        $params[':closed_at'] = $updated_at;
    }

    $sql .= " WHERE period_id = :period_id";

    try {
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $_SESSION['success_msg'] = "Billing period updated successfully.";
    } catch (PDOException $e) {
        $_SESSION['error_msg'] = "Error updating period: " . $e->getMessage();
    }
    
    header("Location: ../views/bill_period.php");
    exit;
}