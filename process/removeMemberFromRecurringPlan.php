<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }

require_once '../database/config.php';
$pdo = getDB();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $subscription_id = filter_input(INPUT_POST, 'subscription_id', FILTER_VALIDATE_INT);
    $status          = filter_input(INPUT_POST, 'status', FILTER_SANITIZE_STRING); // Expects 'active' or 'inactive'

    if ($subscription_id && in_array($status, ['active', 'inactive'])) {
        try {
            $stmt = $pdo->prepare("UPDATE recurring_subscriptions SET status = :status, updated_at = NOW() WHERE subscription_id = :subscription_id");
            $stmt->execute([':status' => $status, ':subscription_id' => $subscription_id]);
            
            if ($status === 'inactive') {
                $_SESSION['success_msg'] = "Subscription cancelled. No further bills will be generated.";
            } else {
                $_SESSION['success_msg'] = "Subscription reactivated successfully.";
            }
            
        } catch (PDOException $e) {
            $_SESSION['error_msg'] = "Failed to update subscription status. Database error.";
            error_log("Remove Member Error: " . $e->getMessage());
        }
    } else {
        $_SESSION['error_msg'] = "Invalid input data.";
    }
    
    header("Location: ../views/recurring_bill.php");
    exit();
}