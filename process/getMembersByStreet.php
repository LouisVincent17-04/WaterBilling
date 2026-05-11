<?php
/**
 * getMembersByStreet.php
 * Fetches members dynamically based on the street selected.
 */
require_once '../database/config.php';
if (session_status() === PHP_SESSION_NONE) { session_start(); }
header('Content-Type: application/json');

$pdo = getDB();
$street = trim($_GET['street'] ?? '');

try {
    // Base Query: Get Active Members and their Max Discount
    $sql = "
        SELECT 
            m.pkey, 
            CONCAT(m.lastname, ', ', m.firstname) AS full_name,
            MAX(COALESCE(d.discount_rate, 0)) AS discount_rate
        FROM members m
        LEFT JOIN discounted_members dm ON m.pkey = dm.member_id
        LEFT JOIN discounts d           ON dm.discount_id = d.discount_id
    ";

    $filters = ["m.status = 'A'"];
    $params = [];

    // Check if the user is filtering by a SPECIFIC street (not "all")
    $isStreetSpecific = ($street !== '' && strtolower($street) !== 'all');

    // If a specific street is selected, we must JOIN the address tables
    if ($isStreetSpecific) {
        $sql .= "
            JOIN memberaddress ma ON ma.member_key = m.pkey AND ma.status = 'A'
            JOIN addresses a      ON a.pkey = ma.address_key AND a.status = 'A'
        ";
        
        $filters[] = "LOWER(TRIM(a.street)) = LOWER(TRIM(:street))";
        $params[':street'] = $street;
    }

    // Append filters
    $sql .= " WHERE " . implode(" AND ", $filters);
    
    // Group and Order
    $sql .= " GROUP BY m.pkey, m.lastname, m.firstname ORDER BY m.lastname ASC";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);

    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo json_encode($results);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}