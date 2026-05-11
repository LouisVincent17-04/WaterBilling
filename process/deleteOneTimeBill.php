<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }

// TODO: Include your actual database connection
require_once '../database/config.php';

$pdo = getDB();
    
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $bill_id = filter_input(INPUT_POST, 'bill_id', FILTER_VALIDATE_INT);
    
    if ($bill_id) {
        try {
            $stmt = $pdo->prepare("DELETE FROM one_time_bills WHERE bill_id = :bill_id");
            $stmt->execute([':bill_id' => $bill_id]);
            $_SESSION['success_msg'] = "Bill successfully deleted.";
        } catch (PDOException $e) {
            $_SESSION['error_msg'] = "Failed to delete bill. Database error.";
            error_log("Delete One Time Bill Error: " . $e->getMessage());
        }
    }
    header("Location: ../views/one_time_billing.php");
    exit();
}