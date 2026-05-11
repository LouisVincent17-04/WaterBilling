<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }
require_once '../database/config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $pdo = getDB();
    $installment_id = filter_input(INPUT_POST, 'installment_id', FILTER_VALIDATE_INT);
    
    if ($installment_id) {
        try {
            // Note: Cascade deletion automatically handles the installment_schedules table
            $stmt = $pdo->prepare("DELETE FROM installment_bills WHERE installment_id = :installment_id");
            $stmt->execute([':installment_id' => $installment_id]);
            $_SESSION['success_msg'] = "Installment bill and all schedules successfully deleted.";
        } catch (PDOException $e) {
            $_SESSION['error_msg'] = "Failed to delete installment. Database error.";
            error_log("Delete Installment Error: " . $e->getMessage());
        }
    }
    header("Location: ../views/installment_bill.php");
    exit();
}