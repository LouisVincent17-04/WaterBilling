<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }

// Include your database connection
require_once '../database/config.php';
$pdo = getDB();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $bill_id = filter_input(INPUT_POST, 'bill_id', FILTER_VALIDATE_INT);
    
    if ($bill_id) {
        try {
            $stmt = $pdo->prepare("UPDATE one_time_bills SET status = 'paid', updated_at = NOW() WHERE bill_id = :bill_id");
            $stmt->execute([':bill_id' => $bill_id]);
            $_SESSION['success_msg'] = "Bill successfully marked as PAID!";
        } catch (PDOException $e) {
            $_SESSION['error_msg'] = "Failed to update bill status. Database error.";
            error_log("Mark Paid One Time Bill Error: " . $e->getMessage());
        }
    } else {
        $_SESSION['error_msg'] = "Invalid Bill ID.";
    }
    
    header("Location: ../views/one_time_billing.php");
    exit();
} else {
    header("Location: ../views/one_time_billing.php");
    exit();
}