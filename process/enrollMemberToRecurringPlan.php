<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }

require_once '../database/config.php';
$pdo = getDB();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $member_id  = filter_input(INPUT_POST, 'member_id', FILTER_VALIDATE_INT);
    $plan_id    = filter_input(INPUT_POST, 'plan_id',   FILTER_VALIDATE_INT);
    $start_date = filter_input(INPUT_POST, 'start_date', FILTER_SANITIZE_STRING);

    if (!$member_id || !$plan_id || !$start_date) {
        $_SESSION['error_msg'] = "Please fill in all required fields.";
        header("Location: ../views/recurring_bill.php");
        exit();
    }

    try {
        // Prevent duplicate active subscriptions for the same plan + member combo
        $check_stmt = $pdo->prepare("
            SELECT subscription_id 
            FROM recurring_subscriptions 
            WHERE member_id = :member_id 
              AND plan_id   = :plan_id
        ");
        $check_stmt->execute([':member_id' => $member_id, ':plan_id' => $plan_id]);

        if ($check_stmt->rowCount() > 0) {
            $_SESSION['error_msg'] = "This account is already subscribed to this plan.";
            header("Location: ../views/recurring_bill.php");
            exit();
        }

        // Insert the subscription.
        // override_amount is always NULL — amount overrides happen only at the collection phase.
        // next_billing_date is seeded with the enrollment start_date as the anchor.
        // :start_date and :next_billing_date hold the same value but must be
        // distinct named params — PDO disallows reusing the same placeholder
        // twice in one statement (causes SQLSTATE HY093).
        $stmt = $pdo->prepare("
            INSERT INTO recurring_subscriptions 
                (plan_id, member_id, override_amount, start_date, next_billing_date, status, created_at, updated_at) 
            VALUES 
                (:plan_id, :member_id, NULL, :start_date, :next_billing_date, 'active', NOW(), NOW())
        ");

        $stmt->execute([
            ':plan_id'           => $plan_id,
            ':member_id'         => $member_id,
            ':start_date'        => $start_date,
            ':next_billing_date' => $start_date,
        ]);

        $_SESSION['success_msg'] = "Account successfully subscribed to the plan!";

    } catch (PDOException $e) {
        $_SESSION['error_msg'] = "Database error. Failed to enroll account.";
        error_log("Enroll Member Error: " . $e->getMessage());
    }

    header("Location: ../views/recurring_bill.php");
    exit();
}