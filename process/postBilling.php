<?php
require_once '../database/config.php';
header('Content-Type: application/json');
$pdo = getDB();

$input = json_decode(file_get_contents('php://input'), true);
$action = $_GET['action'] ?? $input['action'] ?? '';
$period_id = (int)($_GET['period_id'] ?? $input['period_id'] ?? 0);

if (!$period_id) { echo json_encode(['error' => 'No period ID']); exit; }

try {
    if ($action === 'summary') {
        // 1. Get counts for the dashboard
        $stmt = $pdo->prepare("
            SELECT 
                COUNT(*) as total,
                SUM(IF(is_billed = 0, 1, 0)) as draft,
                SUM(IF(is_billed = 1, 1, 0)) as billed
            FROM bill_readings 
            WHERE period_id = ?
        ");
        $stmt->execute([$period_id]);
        $res = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // 2. Fetch the actual list of Drafts (is_billed = 0)
        $stmtList = $pdo->prepare("
            SELECT br.reading_id, m.pkey as member_id, m.account_number, m.firstname, m.lastname, 
                   br.prev_reading, br.pres_reading, br.consumption 
            FROM bill_readings br
            JOIN members m ON br.member_id = m.pkey
            WHERE br.period_id = ? AND br.is_billed = 0
            ORDER BY m.lastname, m.firstname
        ");
        $stmtList->execute([$period_id]);
        $draftList = $stmtList->fetchAll(PDO::FETCH_ASSOC);

        echo json_encode([
            'total' => (int)$res['total'],
            'draft' => (int)$res['draft'],
            'billed' => (int)$res['billed'],
            'draft_list' => $draftList
        ]);
        exit;
    }

    if ($action === 'post') {
        // The Official LOCKING logic
        // Only updates readings that are currently drafts (0)
        $stmt = $pdo->prepare("UPDATE bill_readings SET is_billed = 1 WHERE period_id = ? AND is_billed = 0");
        $stmt->execute([$period_id]);
        
        echo json_encode(['success' => true, 'posted_count' => $stmt->rowCount()]);
        exit;
    }

} catch (Exception $e) {
    echo json_encode(['error' => $e->getMessage()]);
}