<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }

require_once '../database/config.php';

$pdo = getDB();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // 1. Extract and sanitize selected bill IDs
    $bill_ids_raw = $_POST['bill_ids'] ?? [];

    if (empty($bill_ids_raw) || !is_array($bill_ids_raw)) {
        $_SESSION['error_msg'] = "No bills selected for batch update.";
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

    // 2. Validate new status
    $status          = trim(filter_input(INPUT_POST, 'status', FILTER_DEFAULT) ?? '');
    $allowed_statuses = ['unpaid', 'paid', 'cancelled'];

    if (!in_array($status, $allowed_statuses, true)) {
        $_SESSION['error_msg'] = "Invalid status value. Must be one of: unpaid, paid, cancelled.";
        header("Location: ../views/one_time_billing.php");
        exit();
    }

    // 3. Optionally update due_date and term_days
    $due_date_raw  = trim(strip_tags(filter_input(INPUT_POST, 'due_date',  FILTER_DEFAULT) ?? ''));
    $term_days_raw = filter_input(INPUT_POST, 'term_days', FILTER_VALIDATE_INT);

    $update_dates = false;
    $due_date     = null;
    $term_days    = null;

    if (!empty($due_date_raw)) {
        $d = DateTime::createFromFormat('Y-m-d', $due_date_raw);
        if (!$d || $d->format('Y-m-d') !== $due_date_raw) {
            $_SESSION['error_msg'] = "Invalid due date format. Please use YYYY-MM-DD.";
            header("Location: ../views/one_time_billing.php");
            exit();
        }
        $due_date     = $due_date_raw;
        $term_days    = ($term_days_raw !== false && $term_days_raw !== null) ? (int)$term_days_raw : 0;
        $update_dates = true;
    }

    // 4. Build and execute the UPDATE
    try {
        $pdo->beginTransaction();

        $placeholders = implode(',', array_fill(0, count($bill_ids), '?'));

        if ($update_dates) {
            $sql    = "UPDATE one_time_bills
                       SET status = ?, due_date = ?, term_days = ?, updated_at = NOW()
                       WHERE bill_id IN ({$placeholders})";
            $params = array_merge([$status, $due_date, $term_days], $bill_ids);
        } else {
            $sql    = "UPDATE one_time_bills
                       SET status = ?, updated_at = NOW()
                       WHERE bill_id IN ({$placeholders})";
            $params = array_merge([$status], $bill_ids);
        }

        $stmt     = $pdo->prepare($sql);
        $stmt->execute($params);
        $affected = $stmt->rowCount();

        $pdo->commit();

        $label = ucfirst($status);
        $_SESSION['success_msg'] = "Batch update complete! {$affected} bill(s) marked as \"{$label}\".";

    } catch (PDOException $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        $_SESSION['error_msg'] = "Database error. Failed to update bills in batch.";
        error_log("Batch Edit One Time Bill Error: " . $e->getMessage());
    }

    header("Location: ../views/one_time_billing.php");
    exit();
}