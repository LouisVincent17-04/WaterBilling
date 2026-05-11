<?php
/**
 * process/getWaterBillReceivables.php
 * Fetches all official, posted water bills (is_billed = 1) for a specific member
 * and calculates the exact peso amount due dynamically.
 */

require_once '../database/config.php';
header('Content-Type: application/json');
$pdo = getDB();

$member_id = (int)($_GET['member_id'] ?? 0);
if (!$member_id) { echo json_encode([]); exit; }

try {
    // 1. Get Member details (to know their rate code)
    $stmtMem = $pdo->prepare("SELECT rc_id FROM members WHERE pkey = ?");
    $stmtMem->execute([$member_id]);
    $member = $stmtMem->fetch(PDO::FETCH_ASSOC);
    if (!$member) { echo json_encode([]); exit; }

    // 2. Get Discount Rules from enrollment table
    $stmtDisc = $pdo->prepare("
        SELECT d.wmd_name, d.free_water_m3, d.percent_discount, d.fixed_discount, d.active_discount, d.max_m3_for_discount
        FROM water_meter_discounted_members wmdm
        JOIN water_meter_discounts d ON wmdm.wmdiscount_id = d.wmdiscount_id
        WHERE wmdm.member_id = ?
    ");
    $stmtDisc->execute([$member_id]);
    $disc = $stmtDisc->fetch(PDO::FETCH_ASSOC);
    $freeM3 = $disc ? (float)$disc['free_water_m3'] : 0;

    // 3. Get Tiered Rates for this specific member's rate code
    $stmtRates = $pdo->prepare("SELECT from_cb, to_cb, amount, bill_type FROM water_meter_members WHERE rc_id = ? ORDER BY from_cb ASC");
    $stmtRates->execute([$member['rc_id']]);
    $rates = $stmtRates->fetchAll(PDO::FETCH_ASSOC);

    // 4. Fetch Official Water Readings (is_billed = 1)
    // IMPORTANT: When you build your actual Payment/OR tables, you will need to add 
    // a condition here to exclude readings that have already been paid!
    // Example: AND br.reading_id NOT IN (SELECT reading_id FROM payments)
    $stmtBills = $pdo->prepare("
        SELECT br.reading_id, br.consumption, DATE(br.created_at) as reading_date, bp.bp_code as period_code
        FROM bill_readings br
        JOIN bill_periods bp ON br.period_id = bp.period_id
        WHERE br.member_id = ? AND br.is_billed = 1
        ORDER BY bp.start_date DESC
    ");
    $stmtBills->execute([$member_id]);
    $readings = $stmtBills->fetchAll(PDO::FETCH_ASSOC);

    $results = [];

    // 5. Calculate exact amount for each unpaid reading
    foreach ($readings as $r) {
        $totalCons = (float)$r['consumption'];
        $billable = max(0, $totalCons - $freeM3);

        $baseCharge = 0;
        $remaining = $billable;
        
        // Loop through rate brackets
        foreach ($rates as $rate) {
            if ($remaining <= 0) break;
            $from = (int)$rate['from_cb'];
            $to = $rate['to_cb'] !== null ? (int)$rate['to_cb'] : null;
            $amt = (float)$rate['amount'];
            
            $capacity = ($to !== null) ? ($to - $from + 1) : INF;
            $tierCons = min($remaining, $capacity);
            
            if ($rate['bill_type'] === 'FIXED') {
                $baseCharge += $amt;
            } else {
                $baseCharge += ($tierCons * $amt);
            }
            $remaining -= $tierCons;
        }

        // Apply Monetary Discounts (if any)
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

        // Final Amount Due for this specific reading
        $amtDue = max(0, $baseCharge - $discAmt);

        $results[] = [
            'reading_id'   => $r['reading_id'],
            'period_code'  => $r['period_code'],
            'reading_date' => $r['reading_date'],
            'consumption'  => $totalCons,
            'amount_due'   => $amtDue
        ];
    }

    echo json_encode($results);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}