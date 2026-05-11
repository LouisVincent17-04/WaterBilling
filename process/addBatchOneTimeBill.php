<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }

require_once '../database/config.php';

$pdo = getDB();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $member_id    = filter_input(INPUT_POST, 'member_id',    FILTER_VALIDATE_INT);
    $bill_code_id = filter_input(INPUT_POST, 'bill_code_id', FILTER_VALIDATE_INT);
    $total_amount = filter_input(INPUT_POST, 'total_amount', FILTER_VALIDATE_FLOAT);

    // BUG FIX: FILTER_SANITIZE_STRING is deprecated in PHP 8.1+. Use FILTER_DEFAULT then strip manually.
    $bill_date = trim(strip_tags(filter_input(INPUT_POST, 'bill_date', FILTER_DEFAULT) ?? ''));
    $due_date  = trim(strip_tags(filter_input(INPUT_POST, 'due_date',  FILTER_DEFAULT) ?? ''));
    $created_by = (int)($_SESSION['user_id'] ?? 1);

    // Basic presence check
    if (!$member_id || !$bill_code_id || $total_amount === false || $total_amount === null
        || empty($bill_date) || empty($due_date)) {
        $_SESSION['error_msg'] = "Please fill in all required fields correctly.";
        header("Location: ../views/one_time_billing.php");
        exit();
    }

    // BUG FIX: Validate date formats before passing to DateTime to prevent exceptions.
    $d1 = DateTime::createFromFormat('Y-m-d', $bill_date);
    $d2 = DateTime::createFromFormat('Y-m-d', $due_date);

    if (!$d1 || $d1->format('Y-m-d') !== $bill_date
     || !$d2 || $d2->format('Y-m-d') !== $due_date) {
        $_SESSION['error_msg'] = "Invalid date format. Please use YYYY-MM-DD.";
        header("Location: ../views/one_time_billing.php");
        exit();
    }

    // Calculate term days (negative if due date is before bill date)
    $term_days = (int)$d1->diff($d2)->days;
    if ($d1 > $d2) {
        $term_days = -$term_days;
    }

    try {
        $stmt = $pdo->prepare("
            INSERT INTO one_time_bills
                (member_id, bill_code_id, bill_date, due_date, term_days, total_amount, status, created_by, created_at, updated_at)
            VALUES
                (:member_id, :bill_code_id, :bill_date, :due_date, :term_days, :total_amount, 'unpaid', :created_by, NOW(), NOW())
        ");

        $stmt->execute([
            ':member_id'    => $member_id,
            ':bill_code_id' => $bill_code_id,
            ':bill_date'    => $bill_date,
            ':due_date'     => $due_date,
            ':term_days'    => $term_days,
            ':total_amount' => $total_amount,
            ':created_by'   => $created_by,
        ]);

        $_SESSION['success_msg'] = "One-time bill successfully issued!";

    } catch (PDOException $e) {
        $_SESSION['error_msg'] = "Database error. Failed to issue bill.";
        error_log("Add One Time Bill Error: " . $e->getMessage());
    }

    header("Location: ../views/one_time_billing.php");
    exit();
}