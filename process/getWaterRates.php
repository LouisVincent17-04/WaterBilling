<?php
/**
 * process/getWaterRates.php
 * AJAX — Returns all rate brackets from water_meter_members, ordered per rc_id and from_cb.
 * Used by reading_entry.php (JS) to perform live bill preview computation.
 *
 * Returns JSON array:
 * [
 *   { wmm_id, rc_id, from_cb, to_cb, amount, bill_type },
 *   ...
 * ]
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

try {
    $stmt = $pdo->query("
        SELECT wmm_id, rc_id, from_cb, to_cb, amount, bill_type
        FROM water_meter_members
        ORDER BY rc_id ASC, from_cb ASC
    ");
    $rates = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Cast types so JS receives proper numbers, not strings
    $out = array_map(function($r) {
        return [
            'wmm_id'    => (int)    $r['wmm_id'],
            'rc_id'     => (int)    $r['rc_id'],
            'from_cb'   => (int)    $r['from_cb'],
            'to_cb'     => $r['to_cb'] !== null ? (int)$r['to_cb'] : null,
            'amount'    => (float)  $r['amount'],
            'bill_type' => $r['bill_type'],   // 'FIXED' | 'VARIABLE'
        ];
    }, $rates);

    echo json_encode($out);

} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Server error: ' . $e->getMessage()]);
}