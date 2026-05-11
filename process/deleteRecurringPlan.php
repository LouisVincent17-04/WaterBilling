<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }

require_once '../database/config.php';
$pdo = getDB();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $plan_id = filter_input(INPUT_POST, 'plan_id', FILTER_VALIDATE_INT);
    
    if ($plan_id) {
        try {
            $stmt = $pdo->prepare("DELETE FROM recurring_plans WHERE plan_id = :plan_id");
            $stmt->execute([':plan_id' => $plan_id]);
            
            $_SESSION['success_msg'] = "Plan and all associated subscriptions deleted.";
        } catch (PDOException $e) {
            $_SESSION['error_msg'] = "Failed to delete plan. Database error.";
            error_log("Delete Recurring Plan Error: " . $e->getMessage());
        }
    }
    
    header("Location: ../views/recurring_bill.php");
    exit();
}