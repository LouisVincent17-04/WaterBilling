<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }
require_once '../database/config.php';

$pdo = getDB();

if (isset($_GET['id'])) {
    $period_id = $_GET['id'];
    
    try {
        $stmt = $pdo->prepare("DELETE FROM bill_periods WHERE period_id = :period_id");
        $stmt->execute([':period_id' => $period_id]);
        $_SESSION['success_msg'] = "Billing period deleted successfully.";
    } catch (PDOException $e) {
        // This will catch constraint failures if the period is currently in use
        $_SESSION['error_msg'] = "Cannot delete this period because it contains active billing records.";
    }
}

header("Location: ../views/bill_period.php");
exit;