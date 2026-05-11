<?php
/**
 * process/saveBatchReading.php
 * POST handler — insert or update multiple bill_reading records at once (batch mode).
 *
 * Expects POST JSON body:
 * {
 *   period_id : int,
 *   street    : string,
 *   readings  : [
 *     { member_id, prev_reading, pres_reading, reading_id|null, is_override }
 *   ]
 * }
 *
 * Returns JSON:
 * {
 *   success : bool,
 *   message : string,
 *   saved   : int,
 *   skipped : int,
 *   errors  : string[],
 *   results : [{ member_id, reading_id, consumption, water_charge, discount_amount, amount_due }]
 * }
 */

require_once '../database/config.php';
requireLogin();
require_once '../common/readingHelpers.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

$body = json_decode(file_get_contents('php://input'), true);
if (!$body) {
    $body = $_POST;
    $body['readings'] = json_decode($_POST['readings'] ?? '[]', true);
}

$periodId  = (int)  ($body['period_id'] ?? 0);
$street    = trim($body['street']       ?? '');
$readings  = $body['readings']          ?? [];
$encodedBy = $_SESSION['username'] ?? $_SESSION['user_name'] ?? 'system';

if (!$periodId || !$street || empty($readings)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'period_id, street, and readings are required.']);
    exit;
}

$rates   = getWaterRates($pdo);
$saved   = 0;
$skipped = 0;
$errors  = [];
$results = [];
$now     = date('Y-m-d H:i:s');

try {
    $pdo->beginTransaction();

    foreach ($readings as $row) {
        $memberId    = (int)  ($row['member_id']    ?? 0);
        $prevReading = (float)($row['prev_reading'] ?? 0);
        $presRaw     = $row['pres_reading'] ?? '';
        $presReading = ($presRaw !== '' && $presRaw !== null) ? (float)$presRaw : null;
        $readingId   = isset($row['reading_id']) && $row['reading_id'] !== '' ? (int)$row['reading_id'] : null;
        $isOverride  = (int)($row['is_override'] ?? 0);

        // Skip rows where no present reading was entered
        if ($presReading === null) {
            $skipped++;
            continue;
        }

        // Validate: pres must not be less than prev
        if ($presReading < $prevReading) {
            $errors[] = "Member #{$memberId}: present reading ({$presReading}) is less than previous ({$prevReading}). Skipped.";
            $skipped++;
            continue;
        }

        // Guard billed rows
        if ($readingId) {
            $existing = getCurrentReading($pdo, $memberId, $periodId);
            if ($existing && $existing['is_billed'] && !$isOverride) {
                $skipped++;
                continue;
            }
        }

        if ($readingId) {
            $stmt = $pdo->prepare(
                "UPDATE bill_readings
                 SET prev_reading       = ?,
                     pres_reading       = ?,
                     is_edited_override = ?,
                     entry_mode         = 'BATCH',
                     encoded_by         = ?,
                     updated_at         = ?
                 WHERE reading_id = ?"
            );
            $stmt->execute([$prevReading, $presReading, $isOverride, $encodedBy, $now, $readingId]);
        } else {
            $stmt = $pdo->prepare(
                "INSERT INTO bill_readings
                   (period_id, member_id, prev_reading, pres_reading,
                    is_billed, is_edited_override, entry_mode, encoded_by, created_at, updated_at)
                 VALUES (?, ?, ?, ?, 0, 0, 'BATCH', ?, ?, ?)
                 ON DUPLICATE KEY UPDATE
                   prev_reading       = VALUES(prev_reading),
                   pres_reading       = VALUES(pres_reading),
                   is_edited_override = 1,
                   entry_mode         = 'BATCH',
                   encoded_by         = VALUES(encoded_by),
                   updated_at         = VALUES(updated_at)"
            );
            $stmt->execute([$periodId, $memberId, $prevReading, $presReading, $encodedBy, $now, $now]);
            if (!$readingId) $readingId = (int)$pdo->lastInsertId() ?: $readingId;
        }

        $consumption    = max(0.0, $presReading - $prevReading);
        $waterCharge    = computeWaterCharge($consumption, $rates);
        $amountDue      = max(0.0, $waterCharge);

        $results[] = [
            'member_id'       => $memberId,
            'reading_id'      => $readingId,
            'consumption'     => $consumption,
            'water_charge'    => $waterCharge,
            'amount_due'      => $amountDue,
        ];
        $saved++;
    }

    $pdo->commit();

    echo json_encode([
        'success' => true,
        'message' => "{$saved} reading(s) saved, {$skipped} skipped.",
        'saved'   => $saved,
        'skipped' => $skipped,
        'errors'  => $errors,
        'results' => $results,
    ]);

} catch (Throwable $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Server error: ' . $e->getMessage()]);
}