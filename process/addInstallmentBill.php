<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }
require_once '../database/config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $pdo = getDB();

    $member_id         = filter_input(INPUT_POST, 'member_id', FILTER_VALIDATE_INT);
    $bill_code_id      = filter_input(INPUT_POST, 'bill_code_id', FILTER_VALIDATE_INT);
    $total_amount      = filter_input(INPUT_POST, 'total_amount', FILTER_VALIDATE_FLOAT);
    $term              = filter_input(INPUT_POST, 'term', FILTER_VALIDATE_INT);
    $payment_mode      = filter_input(INPUT_POST, 'payment_mode', FILTER_SANITIZE_STRING); 
    $amortization_type = filter_input(INPUT_POST, 'amortization_type', FILTER_SANITIZE_STRING); 
    $start_date        = filter_input(INPUT_POST, 'start_date', FILTER_SANITIZE_STRING);

    $schedule_dates    = $_POST['schedule_dates'] ?? [];
    $schedule_amounts  = $_POST['schedule_amounts'] ?? [];

    // Basic Validation
    if (!$member_id || !$bill_code_id || !$total_amount || !$term || empty($schedule_dates) || empty($schedule_amounts)) {
        $_SESSION['error_msg'] = "Missing required fields or the schedule was not generated.";
        header("Location: ../views/installment_bills.php");
        exit();
    }

    // =========================================================
    // STRICT BACKEND VALIDATION: Check if schedule sum matches total
    // =========================================================
    $sum_of_schedules = array_sum($schedule_amounts);
    
    // We check absolute difference to handle floating point precision (e.g. 0.001)
    if (abs($sum_of_schedules - $total_amount) > 0.01) {
        $_SESSION['error_msg'] = "Validation Error: The sum of the scheduled amortizations (₱ " . number_format($sum_of_schedules, 2) . ") does not match the Total Amount (₱ " . number_format($total_amount, 2) . ").";
        header("Location: ../views/installment_bills.php");
        exit();
    }

    try {
        $pdo->beginTransaction();

        $stmt = $pdo->prepare("
            INSERT INTO installment_bills (member_id, bill_code_id, payment_mode, total_amount, term, amortization_type, start_date, status, created_at, updated_at) 
            VALUES (:member_id, :bill_code_id, :payment_mode, :total_amount, :term, :amortization_type, :start_date, 'active', NOW(), NOW())
        ");
        
        $stmt->execute([
            ':member_id'         => $member_id,
            ':bill_code_id'      => $bill_code_id,
            ':payment_mode'      => $payment_mode,
            ':total_amount'      => $total_amount,
            ':term'              => $term,
            ':amortization_type' => $amortization_type,
            ':start_date'        => $start_date
        ]);

        $installment_id = $pdo->lastInsertId();

        $sched_stmt = $pdo->prepare("
            INSERT INTO installment_schedules (installment_id, due_date, amount, status) 
            VALUES (:installment_id, :due_date, :amount, 'pending')
        ");

        for ($i = 0; $i < count($schedule_dates); $i++) {
            $sched_stmt->execute([
                ':installment_id' => $installment_id,
                ':due_date'       => $schedule_dates[$i],
                ':amount'         => (float)$schedule_amounts[$i]
            ]);
        }

        $pdo->commit();
        $_SESSION['success_msg'] = "Installment Bill and Amortization Schedule successfully created!";
        
    } catch (PDOException $e) {
        $pdo->rollBack();
        $_SESSION['error_msg'] = "Database error. Failed to create installment.";
        error_log("Add Installment Error: " . $e->getMessage());
    }

    header("Location: ../views/installment_bill.php");
    exit();
}