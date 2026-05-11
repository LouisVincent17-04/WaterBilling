<?php
/**
 * process/searchMembers.php
 * AJAX — autocomplete search for members (Individual Reading tab).
 *
 * Method : GET
 * Params : q (string, min 2 chars), period_id (int)
 * Returns: JSON array of member objects
 */

require_once '../database/config.php';
requireLogin();
require_once '../common/readingHelpers.php';

header('Content-Type: application/json');

$pdo = getDB();

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

$q        = trim($_GET['q']         ?? '');
$periodId = (int)($_GET['period_id'] ?? 0);

if (mb_strlen($q) < 2) {
    echo json_encode([]);
    exit;
}

try {
    $members = searchMembers($pdo, $q, 12);
    $results = [];

    foreach ($members as $m) {
        $existing = ($periodId > 0) ? getCurrentReading($pdo, $m['pkey'], $periodId) : null;

        $results[] = [
            'pkey'         => $m['pkey'],
            'full_name'    => trim($m['full_name']),
            'street'       => trim($m['street']    ?? ''),
            'housebldg'    => trim($m['housebldg'] ?? ''),
            'initials'     => initials(trim($m['full_name'])),
            'has_discount' => !empty($m['dm_id']),
            'reading_done' => !empty($existing),
            'is_billed'    => !empty($existing['is_billed']),
        ];
    }

    echo json_encode($results);

} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Server error: ' . $e->getMessage()]);
}