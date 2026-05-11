<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }

// TODO: Include your actual database connection
// require_once '../config/database.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $code_id = filter_input(INPUT_POST, 'code_id', FILTER_VALIDATE_INT);
    
    if ($code_id) {
        try {
            $stmt = $pdo->prepare("DELETE FROM bill_codes WHERE code_id = :code_id");
            $stmt->execute([':code_id' => $code_id]);
            $_SESSION['success_msg'] = "Bill code successfully deleted.";
        } catch (PDOException $e) {
            // Error 1451 is triggered if this Bill Code is already actively used in a billing ledger (Foreign Key restriction)
            if ($e->getCode() == 23000 || $e->getCode() == 1451) {
                $_SESSION['error_msg'] = "Cannot delete this code because it is currently linked to active billing records.";
            } else {
                $_SESSION['error_msg'] = "Failed to delete code. Database error.";
            }
        }
    }
    header("Location: ../views/bill_codes.php");
    exit();
}