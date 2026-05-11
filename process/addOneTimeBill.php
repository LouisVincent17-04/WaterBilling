<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }

// TODO: Include your actual database connection

require_once '../database/config.php';  

$pdo = getDB();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $member_id    = filter_input(INPUT_POST, 'member_id', FILTER_VALIDATE_INT);
    $bill_code_id = filter_input(INPUT_POST, 'bill_code_id', FILTER_VALIDATE_INT);
    $total_amount = filter_input(INPUT_POST, 'total_amount', FILTER_VALIDATE_FLOAT);
    $bill_date    = filter_input(INPUT_POST, 'bill_date', FILTER_SANITIZE_STRING);
    $due_date     = filter_input(INPUT_POST, 'due_date', FILTER_SANITIZE_STRING);
    $created_by   = $_SESSION['user_id'] ?? 1;

    if (!$member_id || !$bill_code_id || $total_amount === false || !$bill_date || !$due_date) {
        $_SESSION['error_msg'] = "Please fill in all required fields correctly.";
        header("Location: ../views/one_time_billing.php");
        exit();
    }

    // Calculate term days
    $datetime1 = new DateTime($bill_date);
    $datetime2 = new DateTime($due_date);
    $term_days = $datetime1->diff($datetime2)->days;
    if ($datetime1 > $datetime2) {
        $term_days = -$term_days; // Negative if due date is before bill date
    }

    try {
        $stmt = $pdo->prepare("
            INSERT INTO one_time_bills (member_id, bill_code_id, bill_date, due_date, term_days, total_amount, status, created_by, created_at, updated_at) 
            VALUES (:member_id, :bill_code_id, :bill_date, :due_date, :term_days, :total_amount, 'unpaid', :created_by, NOW(), NOW())
        ");
        
        $stmt->execute([
            ':member_id'    => $member_id,
            ':bill_code_id' => $bill_code_id,
            ':bill_date'    => $bill_date,
            ':due_date'     => $due_date,
            ':term_days'    => $term_days,
            ':total_amount' => $total_amount,
            ':created_by'   => $created_by
        ]);

        $_SESSION['success_msg'] = "One-time bill successfully issued!";
        
    } catch (PDOException $e) {
        $_SESSION['error_msg'] = "Database error. Failed to issue bill.";
        error_log("Add One Time Bill Error: " . $e->getMessage());
    }

    header("Location: ../views/one_time_billing.php");
    exit();
}