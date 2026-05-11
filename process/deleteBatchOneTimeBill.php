<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }

require_once '../database/config.php';

$pdo = getDB();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // 1. Extract and sanitize selected bill IDs
    $bill_ids_raw = $_POST['bill_ids'] ?? [];

    if (empty($bill_ids_raw) || !is_array($bill_ids_raw)) {
        $_SESSION['error_msg'] = "No bills selected for deletion.";
        header("Location: ../views/one_time_billing.php");
        exit();
    }

    // Sanitize: integers only, positive values only
    $bill_ids = array_values(
        array_filter(array_map('intval', $bill_ids_raw), fn($id) => $id > 0)
    );

    if (empty($bill_ids)) {
        $_SESSION['error_msg'] = "Invalid bill IDs provided.";
        header("Location: ../views/one_time_billing.php");
        exit();
    }

    // 2. Delete in a single transaction
    try {
        $pdo->beginTransaction();

        $placeholders = implode(',', array_fill(0, count($bill_ids), '?'));
        $stmt         = $pdo->prepare("DELETE FROM one_time_bills WHERE bill_id IN ({$placeholders})");
        $stmt->execute($bill_ids);
        $affected = $stmt->rowCount();

        $pdo->commit();

        $_SESSION['success_msg'] = "{$affected} bill(s) successfully deleted.";

    } catch (PDOException $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        $_SESSION['error_msg'] = "Database error. Failed to delete bills.";
        error_log("Batch Delete One Time Bill Error: " . $e->getMessage());
    }

    header("Location: ../views/one_time_billing.php");
    exit();
}