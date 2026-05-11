<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }

require_once '../database/config.php';
$pdo = getDB();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $plan_id = filter_input(INPUT_POST, 'plan_id', FILTER_VALIDATE_INT);
    $status  = filter_input(INPUT_POST, 'status', FILTER_SANITIZE_STRING); // active or inactive

    // Optional: Allow editing the amount. For safety, many systems only allow editing the status.
    // If you want to update the amount, add it here. For now, we'll just toggle status.
    
    if ($plan_id && in_array($status, ['active', 'inactive'])) {
        try {
            $stmt = $pdo->prepare("UPDATE recurring_plans SET status = :status, updated_at = NOW() WHERE plan_id = :plan_id");
            $stmt->execute([':status' => $status, ':plan_id' => $plan_id]);
            
            $_SESSION['success_msg'] = "Plan status updated successfully!";
        } catch (PDOException $e) {
            $_SESSION['error_msg'] = "Database error. Failed to update plan.";
            error_log("Edit Recurring Plan Error: " . $e->getMessage());
        }
    } else {
        $_SESSION['error_msg'] = "Invalid input.";
    }

    header("Location: ../views/recurring_bill.php");
    exit();
}