<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }

// Include your database connection
require_once '../database/config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $pdo = getDB();
    
    $schedule_id = filter_input(INPUT_POST, 'schedule_id', FILTER_VALIDATE_INT);
    $action      = filter_input(INPUT_POST, 'action', FILTER_SANITIZE_STRING); // 'paid' or 'pending'

    if ($schedule_id && in_array($action, ['paid', 'pending'])) {
        try {
            // Get the current user's ID (Fallback to 1 if session isn't fully configured yet)
            $user_id = $_SESSION['user_id'] ?? 1;

            if ($action === 'paid') {
                $stmt = $pdo->prepare("
                    UPDATE installment_schedules 
                    SET status = 'paid', 
                        paid_at = NOW(), 
                        marked_by = :user_id 
                    WHERE schedule_id = :schedule_id
                ");
                $stmt->execute([
                    ':user_id'     => $user_id,
                    ':schedule_id' => $schedule_id
                ]);
                $_SESSION['success_msg'] = "Schedule payment successfully marked as PAID!";
            } else {
                // If undoing, revert status and clear the audit trail
                $stmt = $pdo->prepare("
                    UPDATE installment_schedules 
                    SET status = 'pending', 
                        paid_at = NULL, 
                        marked_by = NULL 
                    WHERE schedule_id = :schedule_id
                ");
                $stmt->execute([
                    ':schedule_id' => $schedule_id
                ]);
                $_SESSION['success_msg'] = "Payment successfully UNDONE and reverted to pending.";
            }

        } catch (PDOException $e) {
            $_SESSION['error_msg'] = "Database error. Failed to update schedule status.";
            error_log("Toggle Schedule Status Error: " . $e->getMessage());
        }
    } else {
        $_SESSION['error_msg'] = "Invalid schedule data provided.";
    }
    
    header("Location: ../views/installment_bill.php");
    exit();
} else {
    header("Location: ../views/installment_bill.php");
    exit();
}