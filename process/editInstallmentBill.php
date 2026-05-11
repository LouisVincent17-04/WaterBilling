<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }
require_once '../database/config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $pdo = getDB();
    $installment_id = filter_input(INPUT_POST, 'installment_id', FILTER_VALIDATE_INT);
    $status         = filter_input(INPUT_POST, 'status', FILTER_SANITIZE_STRING);

    if ($installment_id && in_array($status, ['active', 'completed', 'cancelled'])) {
        try {
            $stmt = $pdo->prepare("UPDATE installment_bills SET status = :status, updated_at = NOW() WHERE installment_id = :installment_id");
            $stmt->execute([':status' => $status, ':installment_id' => $installment_id]);
            $_SESSION['success_msg'] = "Installment status updated successfully!";
        } catch (PDOException $e) {
            $_SESSION['error_msg'] = "Database error. Failed to update status.";
            error_log("Edit Installment Error: " . $e->getMessage());
        }
    } else {
        $_SESSION['error_msg'] = "Invalid input.";
    }
    
    header("Location: ../views/installment_bill.php");
    exit();
}