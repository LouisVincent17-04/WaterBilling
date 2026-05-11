<?php
/**
 * process/saveIndividualReading.php
 * POST handler — insert or update a single bill_reading record.
 *
 * Expects POST JSON body:
 * {
 *   period_id    : int,
 *   member_id    : int,
 *   prev_reading : float,
 *   pres_reading : float,
 *   reading_id   : int|null,   // null = new record
 *   is_override  : 0|1         // allow editing a billed record
 * }
 *
 * Returns JSON:
 * {
 *   success      : bool,
 *   message      : string,
 *   reading_id   : int,
 *   consumption  : float,
 *   water_charge : float,
 *   discount_amount : float,
 *   amount_due   : float
 * }
 */

require_once '../database/config.php';
requireLogin();
require_once '../common/readingHelpers.php';

header('Content-Type: application/json');

$pdo = getDB();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

// Parse JSON or fall back to form-encoded body
$body = json_decode(file_get_contents('php://input'), true);
if (!$body) {
    $body = $_POST;
}

$periodId    = (int)  ($body['period_id']    ?? 0);
$memberId    = (int)  ($body['member_id']    ?? 0);
$prevReading = (float)($body['prev_reading'] ?? 0);
$presReading = isset($body['pres_reading']) ? (float)$body['pres_reading'] : null;
$readingId   = isset($body['reading_id']) && $body['reading_id'] !== '' ? (int)$body['reading_id'] : null;
$isOverride  = (int)  ($body['is_override']  ?? 0);
$encodedBy   = $_SESSION['username'] ?? $_SESSION['user_name'] ?? 'system';

// --- Validation ---
if (!$periodId || !$memberId) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'period_id and member_id are required.']);
    exit;
}

if ($presReading === null || $presReading === '') {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Present reading is required.']);
    exit;
}

if ($presReading < $prevReading) {
    http_response_code(422);
    echo json_encode([
        'success' => false,
        'message' => "Present reading ({$presReading}) cannot be less than previous reading ({$prevReading}).",
    ]);
    exit;
}

try {
    // Guard: cannot edit a billed record without override flag
    if ($readingId) {
        $existing = getCurrentReading($pdo, $memberId, $periodId);
        if ($existing && $existing['is_billed'] && !$isOverride) {
            http_response_code(409);
            echo json_encode([
                'success' => false,
                'message' => 'This reading has already been billed. Enable Override to edit it.',
            ]);
            exit;
        }
    }

    $rates = getWaterRates($pdo);
    $now   = date('Y-m-d H:i:s');

    $pdo->beginTransaction();

    if ($readingId) {
        // --- UPDATE existing record ---
        $stmt = $pdo->prepare(
            "UPDATE bill_readings
             SET prev_reading        = ?,
                 pres_reading        = ?,
                 is_edited_override  = ?,
                 entry_mode          = 'INDIVIDUAL',
                 encoded_by          = ?,
                 updated_at          = ?
             WHERE reading_id = ?"
        );
        $stmt->execute([$prevReading, $presReading, $isOverride, $encodedBy, $now, $readingId]);
    } else {
        // --- INSERT new record (ON DUPLICATE handles race condition) ---
        $stmt = $pdo->prepare(
            "INSERT INTO bill_readings
               (period_id, member_id, prev_reading, pres_reading,
                is_billed, is_edited_override, entry_mode, encoded_by, created_at, updated_at)
             VALUES (?, ?, ?, ?, 0, 0, 'INDIVIDUAL', ?, ?, ?)
             ON DUPLICATE KEY UPDATE
               prev_reading       = VALUES(prev_reading),
               pres_reading       = VALUES(pres_reading),
               is_edited_override = 1,
               entry_mode         = 'INDIVIDUAL',
               encoded_by         = VALUES(encoded_by),
               updated_at         = VALUES(updated_at)"
        );
        $stmt->execute([$periodId, $memberId, $prevReading, $presReading, $encodedBy, $now, $now]);
        $readingId = (int)$pdo->lastInsertId() ?: $readingId;
    }

    $pdo->commit();

    // Compute billing preview
    $consumption    = max(0.0, $presReading - $prevReading);
    $waterCharge    = computeWaterCharge($consumption, $rates);
    $amountDue      = max(0.0, $waterCharge);

    echo json_encode([
        'success'         => true,
        'message'         => 'Reading saved successfully.',
        'reading_id'      => $readingId,
        'consumption'     => $consumption,
        'water_charge'    => $waterCharge,
        'amount_due'      => $amountDue,
    ]);

} catch (Throwable $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Server error: ' . $e->getMessage()]);
}