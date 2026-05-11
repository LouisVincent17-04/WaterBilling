<?php
require_once '../database/config.php';
header('Content-Type: application/json');
$pdo = getDB();

$q = trim($_GET['q'] ?? '');
if (strlen($q) < 2) { echo json_encode([]); exit; }

$likeQ = '%' . $q . '%';

try {
    // 1. Search for members who specifically have UNPAID WATER BILLS
    // We enforce is_billed = 1 so draft readings are ignored.
    $stmt = $pdo->prepare("
        SELECT 
            m.pkey, 
            CONCAT(m.lastname, ', ', m.firstname) AS full_name,
            COUNT(br.reading_id) as unpaid_count
        FROM members m
        JOIN bill_readings br ON m.pkey = br.member_id
        WHERE br.is_billed = 1 
          -- AND br.payment_status = 'UNPAID' (Assuming you have a payment flag, add it here)
          AND (CAST(m.pkey AS CHAR) LIKE :q1 OR m.lastname LIKE :q2 OR m.firstname LIKE :q3)
        GROUP BY m.pkey
        ORDER BY m.lastname
        LIMIT 10
    ");
    
    // Bind the variable to each unique placeholder
    $stmt->execute([
        ':q1' => $likeQ,
        ':q2' => $likeQ,
        ':q3' => $likeQ
    ]);
    
    echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}