<?php
/**
 * process/getPeriodReadings.php
 * Returns all bill_readings for a given period, joined with member names.
 * Used by reading_entry.php's "Recorded Readings" table at the bottom of Step 3.
 *
 * GET params:
 *   period_id  (int, required)
 *
 * Response JSON:
 *   { readings: [ { reading_id, member_id, full_name, account_number,
 *                   prev_reading, pres_reading, consumption,
 *                   is_billed, entry_mode, encoded_by, created_at } ] }
 */

require_once '../database/config.php';
header('Content-Type: application/json');

$pdo = getDB();

$period_id = isset($_GET['period_id']) ? (int)$_GET['period_id'] : 0;
if (!$period_id) {
    echo json_encode(['error' => 'Missing period_id']);
    exit;
}

try {
    $stmt = $pdo->prepare("
        SELECT
            br.reading_id,
            br.member_id,
            CONCAT(m.lastname, ', ', m.firstname) AS full_name,
            m.account_number,
            br.prev_reading,
            br.pres_reading,
            br.consumption,
            br.is_billed,
            br.entry_mode,
            br.encoded_by,
            br.created_at
        FROM   bill_readings br
        JOIN   members       m  ON m.pkey = br.member_id
        WHERE  br.period_id = :period_id
        ORDER  BY br.created_at DESC
    ");
    $stmt->execute([':period_id' => $period_id]);
    $readings = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode(['readings' => $readings]);
} catch (Exception $e) {
    echo json_encode(['error' => $e->getMessage()]);
}