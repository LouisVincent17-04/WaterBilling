<?php
// This script is designed to be run via Cron Job (Linux) or Task Scheduler (Windows)
// But it can also be triggered manually or upon admin login.
require_once '../database/config.php';
$pdo = getDB();

try {
    // 1. Fetch all ACTIVE subscriptions where the next billing date is TODAY or EARLIER
    $stmt = $pdo->query("
        SELECT s.*, p.bill_code_id, p.amount AS base_amount, p.frequency 
        FROM recurring_subscriptions s
        JOIN recurring_plans p ON s.plan_id = p.plan_id
        WHERE s.status = 'active' 
          AND s.next_billing_date <= CURDATE()
    ");
    
    $due_subscriptions = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (empty($due_subscriptions)) {
        echo "No recurring bills due for generation today.\n";
        exit();
    }

    $pdo->beginTransaction();

    // Prepare the insert statement for the actual bill (Sending it to one_time_bills ledger)
    $insert_bill_stmt = $pdo->prepare("
        INSERT INTO one_time_bills (member_id, bill_code_id, bill_date, due_date, term_days, total_amount, status, created_by, created_at, updated_at) 
        VALUES (:member_id, :bill_code_id, :bill_date, :due_date, :term_days, :total_amount, 'unpaid', 1, NOW(), NOW())
    ");

    // Prepare the update statement to push the subscription's next_billing_date forward
    $update_sub_stmt = $pdo->prepare("
        UPDATE recurring_subscriptions 
        SET next_billing_date = :new_next_date, updated_at = NOW() 
        WHERE subscription_id = :subscription_id
    ");

    $bills_created = 0;

    foreach ($due_subscriptions as $sub) {
        // Use override amount if it exists, otherwise use the plan's base amount
        $amount_to_bill = $sub['override_amount'] !== null ? $sub['override_amount'] : $sub['base_amount'];
        $current_bill_date = $sub['next_billing_date'];

        // Calculate the Due Date for this specific bill (e.g., they have 15 days to pay the newly generated bill)
        $term_days = 15; // You can adjust this standard term
        $due_date = date('Y-m-d', strtotime($current_bill_date . " + $term_days days"));

        // 2. Generate the actual bill in the ledger
        $insert_bill_stmt->execute([
            ':member_id'    => $sub['member_id'],
            ':bill_code_id' => $sub['bill_code_id'],
            ':bill_date'    => $current_bill_date,
            ':due_date'     => $due_date,
            ':term_days'    => $term_days,
            ':total_amount' => $amount_to_bill
        ]);

        // 3. Calculate the NEXT billing date based on the Frequency
        $next_date_str = $current_bill_date;
        switch ($sub['frequency']) {
            case 'Monthly':
                $next_date_str = date('Y-m-d', strtotime($current_bill_date . ' + 1 month'));
                break;
            case 'Quarterly':
                $next_date_str = date('Y-m-d', strtotime($current_bill_date . ' + 3 months'));
                break;
            case 'Semi-Annually':
                $next_date_str = date('Y-m-d', strtotime($current_bill_date . ' + 6 months'));
                break;
            case 'Annually':
                $next_date_str = date('Y-m-d', strtotime($current_bill_date . ' + 1 year'));
                break;
        }

        // 4. Update the subscription with the new forward date
        $update_sub_stmt->execute([
            ':new_next_date'   => $next_date_str,
            ':subscription_id' => $sub['subscription_id']
        ]);

        $bills_created++;
    }

    $pdo->commit();
    echo "Success: $bills_created recurring bills were generated and scheduled for their next cycle.\n";

} catch (PDOException $e) {
    $pdo->rollBack();
    error_log("Recurring Billing Engine Error: " . $e->getMessage());
    echo "Error processing recurring bills.\n";
}
?>