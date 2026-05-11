<?php
require_once '../database/config.php';
header('Content-Type: application/json');
$pdo = getDB();

$member_id = (int)($_GET['member_id'] ?? 0);
$period_id = (int)($_GET['period_id'] ?? 0);
$end_date  = $_GET['end_date'] ?? date('Y-m-d');

if (!$member_id || !$period_id) { echo json_encode(['error' => 'Missing params']); exit; }

try {
    $charges = [
        'Water Bill' => [],
        'Installments' => [],
        'Recurring Fees' => [],
        'One-Time Fees' => []
    ];

    // --- 1. GET WATER BILL (Only if is_billed = 1) ---
    $stmtWater = $pdo->prepare("
        SELECT br.consumption, m.rc_id, m.pkey
        FROM bill_readings br
        JOIN members m ON br.member_id = m.pkey
        WHERE br.member_id = ? AND br.period_id = ? AND br.is_billed = 1
    ");
    $stmtWater->execute([$member_id, $period_id]);
    
    if ($water = $stmtWater->fetch(PDO::FETCH_ASSOC)) {
        $totalCons = (float)$water['consumption'];
        
        // A. Get Discount Rules from the new enrollment table
        $stmtDisc = $pdo->prepare("
            SELECT d.wmd_name, d.free_water_m3, d.percent_discount, d.fixed_discount, d.active_discount, d.max_m3_for_discount
            FROM water_meter_discounted_members wmdm
            JOIN water_meter_discounts d ON wmdm.wmdiscount_id = d.wmdiscount_id
            WHERE wmdm.member_id = ?
        ");
        $stmtDisc->execute([$member_id]);
        $disc = $stmtDisc->fetch(PDO::FETCH_ASSOC);

        $freeM3 = $disc ? (float)$disc['free_water_m3'] : 0;
        $billable = max(0, $totalCons - $freeM3);

        // B. Get Tiered Rates
        $stmtRates = $pdo->prepare("SELECT from_cb, to_cb, amount, bill_type FROM water_meter_members WHERE rc_id = ? ORDER BY from_cb ASC");
        $stmtRates->execute([$water['rc_id']]);
        $rates = $stmtRates->fetchAll(PDO::FETCH_ASSOC);

        // C. Compute Base Charge
        $baseCharge = 0;
        $remaining = $billable;
        foreach ($rates as $r) {
            if ($remaining <= 0) break;
            $from = (int)$r['from_cb'];
            $to = $r['to_cb'] !== null ? (int)$r['to_cb'] : null;
            $amt = (float)$r['amount'];
            
            // If `to_cb` is null, it's infinity
            $capacity = ($to !== null) ? ($to - $from + 1) : INF;
            $tierCons = min($remaining, $capacity);
            
            if ($r['bill_type'] === 'FIXED') {
                $baseCharge += $amt;
            } else {
                $baseCharge += ($tierCons * $amt);
            }
            $remaining -= $tierCons;
        }

        // D. Compute Extra Cash Discount
        $discAmt = 0;
        if ($disc && $disc['active_discount'] !== 'none') {
            $maxLimit = $disc['max_m3_for_discount'] !== null ? (float)$disc['max_m3_for_discount'] : INF;
            if ($totalCons <= $maxLimit) {
                if ($disc['active_discount'] === 'percent') {
                    $discAmt = $baseCharge * ((float)$disc['percent_discount'] / 100);
                } else if ($disc['active_discount'] === 'fixed') {
                    $discAmt = (float)$disc['fixed_discount'];
                }
            }
        }

        $amtDue = max(0, $baseCharge - $discAmt);

        // Build Description
        $desc = "Water Consumption ({$totalCons} m³)";
        if ($freeM3 > 0) {
            $desc = "Water Consumption ({$totalCons} m³) — Includes {$freeM3}m³ Free Allowance";
        }

        $charges['Water Bill'][] = [
            'description' => $desc,
            'amount' => $amtDue
        ];
    }

    // --- 2. GET INSTALLMENTS ---
    // FIXED: Changed c.code_name to c.code AS code_name
    $stmtInst = $pdo->prepare("
        SELECT c.code AS code_name, s.amount 
        FROM installment_schedules s 
        JOIN installment_bills b ON s.installment_id = b.installment_id 
        JOIN bill_codes c ON b.bill_code_id = c.code_id
        WHERE b.member_id = ? AND s.status = 'pending' AND s.due_date <= ?
    ");
    $stmtInst->execute([$member_id, $end_date]);
    foreach ($stmtInst->fetchAll(PDO::FETCH_ASSOC) as $row) {
        $charges['Installments'][] = ['description' => $row['code_name'] . ' (Amortization)', 'amount' => $row['amount']];
    }

    // --- 3. GET RECURRING FEES ---
    // FIXED: Changed c.code_name to c.code AS code_name
    $stmtRecur = $pdo->prepare("
        SELECT c.code AS code_name, p.amount 
        FROM recurring_subscriptions s 
        JOIN recurring_plans p ON s.plan_id = p.plan_id 
        JOIN bill_codes c ON p.bill_code_id = c.code_id
        WHERE s.member_id = ? AND s.status = 'active'
    ");
    $stmtRecur->execute([$member_id]);
    foreach ($stmtRecur->fetchAll(PDO::FETCH_ASSOC) as $row) {
        $charges['Recurring Fees'][] = ['description' => $row['code_name'], 'amount' => $row['amount']];
    }

    // --- 4. GET ONE-TIME FEES ---
    // FIXED: Changed c.code_name to c.code AS code_name
    $stmtOne = $pdo->prepare("
        SELECT c.code AS code_name, (b.total_amount - b.amount_paid) as balance 
        FROM one_time_bills b 
        JOIN bill_codes c ON b.bill_code_id = c.code_id 
        WHERE b.member_id = ? AND b.status = 'unpaid' AND b.due_date <= ?
    ");
    $stmtOne->execute([$member_id, $end_date]);
    foreach ($stmtOne->fetchAll(PDO::FETCH_ASSOC) as $row) {
        $charges['One-Time Fees'][] = ['description' => $row['code_name'], 'amount' => $row['balance']];
    }

    echo json_encode(['success' => true, 'charges' => $charges]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}