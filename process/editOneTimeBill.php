<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }

// TODO: Include your actual database connection
require_once '../database/config.php';

$pdo = getDB();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $bill_id      = filter_input(INPUT_POST, 'bill_id', FILTER_VALIDATE_INT);
    $total_amount = filter_input(INPUT_POST, 'total_amount', FILTER_VALIDATE_FLOAT);
    $due_date     = filter_input(INPUT_POST, 'due_date', FILTER_SANITIZE_STRING);
    $status       = filter_input(INPUT_POST, 'status', FILTER_SANITIZE_STRING);

    if (!$bill_id || $total_amount === false || !$due_date || !in_array($status, ['unpaid', 'paid', 'cancelled'])) {
        $_SESSION['error_msg'] = "Invalid input data.";
        header("Location: ../views/one_time_billing.php");
        exit();
    }

    try {
        // Fetch original bill_date to recalculate term_days
        $stmt_date = $pdo->prepare("SELECT bill_date FROM one_time_bills WHERE bill_id = :bill_id");
        $stmt_date->execute([':bill_id' => $bill_id]);
        $bill_date = $stmt_date->fetchColumn();

        $term_days = (new DateTime($bill_date))->diff(new DateTime($due_date))->days;

        $stmt = $pdo->prepare("
            UPDATE one_time_bills 
            SET total_amount = :total_amount, 
                due_date = :due_date, 
                term_days = :term_days, 
                status = :status, 
                updated_at = NOW() 
            WHERE bill_id = :bill_id
        ");
        
        $stmt->execute([
            ':total_amount' => $total_amount,
            ':due_date'     => $due_date,
            ':term_days'    => $term_days,
            ':status'       => $status,
            ':bill_id'      => $bill_id
        ]);

        $_SESSION['success_msg'] = "Bill successfully updated!";
        
    } catch (PDOException $e) {
        $_SESSION['error_msg'] = "Database error. Failed to update bill.";
        error_log("Edit One Time Bill Error: " . $e->getMessage());
    }

    header("Location: ../views/one_time_billing.php");
    exit();
}