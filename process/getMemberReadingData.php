<?php
/**
 * process/getMemberReadingData.php
 * AJAX — member info + full address + previous reading + existing current-period reading.
 */

require_once '../database/config.php';
// requireLogin(); 

header('Content-Type: application/json');
$pdo = getDB();

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

$memberId = (int)($_GET['member_id'] ?? 0);
$periodId = (int)($_GET['period_id'] ?? 0);

if (!$memberId || !$periodId) {
    http_response_code(400);
    echo json_encode(['error' => 'member_id and period_id are required']);
    exit;
}

try {
    // 1. Get Member Profile, Address, and Discount Rules (UPDATED TO USE NEW TABLE)
    $stmt = $pdo->prepare("
        SELECT m.pkey, m.firstname, m.lastname, m.rc_id, m.zone,
               a.housebldg, a.street,
               d.wmd_name, d.free_water_m3, d.percent_discount, d.fixed_discount, d.active_discount, d.max_m3_for_discount
        FROM members m
        LEFT JOIN memberaddress ma ON m.pkey = ma.member_key AND ma.address_type_key = 1 AND ma.status = 'A'
        LEFT JOIN addresses a ON ma.address_key = a.pkey
        LEFT JOIN water_meter_discounted_members wmdm ON m.pkey = wmdm.member_id
        LEFT JOIN water_meter_discounts d ON wmdm.wmdiscount_id = d.wmdiscount_id
        WHERE m.pkey = :mid
    ");
    $stmt->execute([':mid' => $memberId]);
    $member = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$member) {
        http_response_code(404);
        echo json_encode(['error' => 'Member not found.']);
        exit;
    }

    // 2. Fetch Previous Reading
    $prev_reading = 0.0;
    $stmtPrev = $pdo->prepare("SELECT pres_reading FROM bill_readings WHERE member_id = :mid AND period_id < :pid ORDER BY period_id DESC LIMIT 1");
    $stmtPrev->execute([':mid' => $memberId, ':pid' => $periodId]);
    if ($prev = $stmtPrev->fetch(PDO::FETCH_ASSOC)) {
        $prev_reading = (float)$prev['pres_reading'];
    }

    // 3. Fetch Current Reading
    $pres_reading = null;
    $stmtPres = $pdo->prepare("SELECT pres_reading FROM bill_readings WHERE member_id = :mid AND period_id = :pid LIMIT 1");
    $stmtPres->execute([':mid' => $memberId, ':pid' => $periodId]);
    if ($pres = $stmtPres->fetch(PDO::FETCH_ASSOC)) {
        $pres_reading = (float)$pres['pres_reading'];
    }

    // Combine address parts
    $full_address = trim(($member['housebldg'] ?? '') . ' ' . ($member['street'] ?? ''));
    if (empty($full_address)) $full_address = 'Address not specified';

    // 4. Format Payload
    $response = [
        'pkey'         => $member['pkey'],
        'firstname'    => trim($member['firstname'] ?? ''),
        'lastname'     => trim($member['lastname'] ?? ''),
        'full_address' => $full_address,
        'zone'         => $member['zone'] ?? 'N/A',
        'rc_id'        => $member['rc_id'],
        'prev_reading' => $prev_reading,
        'pres_reading' => $pres_reading,
        'discount'     => $member['wmd_name'] ? [
            'wmd_name'            => $member['wmd_name'],
            'free_water_m3'       => (int)$member['free_water_m3'],
            'active_discount'     => $member['active_discount'],
            'percent_discount'    => (float)$member['percent_discount'],
            'fixed_discount'      => (float)$member['fixed_discount'],
            'max_m3_for_discount' => $member['max_m3_for_discount'] !== null ? (int)$member['max_m3_for_discount'] : null
        ] : null
    ];

    echo json_encode($response);

} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Server error: ' . $e->getMessage()]);
}