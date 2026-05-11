<?php
/**
 * process/getBatchMembers.php
 * Returns active members filtered by street or by zone.
 * Returns a full `discount` object per member so the front-end can compute
 * live bill previews in the second detail row of the batch table.
 */

require_once '../database/config.php';
header('Content-Type: application/json');

$pdo = getDB();

$period_id = isset($_GET['period_id']) ? (int)$_GET['period_id'] : 0;
if (!$period_id) {
    echo json_encode(['error' => 'Missing period_id']);
    exit;
}

$street = isset($_GET['street']) ? trim($_GET['street']) : '';
$zone   = isset($_GET['zone'])   ? (int)$_GET['zone']   : 0;

if (!$street && !$zone) {
    echo json_encode(['error' => 'Provide either street or zone parameter']);
    exit;
}

// Common discount columns added to both queries
$discountCols = "
    wmd.wmd_name          AS discount_type,
    wmd.free_water_m3,
    wmd.active_discount,
    wmd.percent_discount,
    wmd.fixed_discount,
    wmd.max_m3_for_discount
";

try {
    if ($street) {
        // --- STREET FILTER QUERY ---
        $sql = "
            SELECT 
                m.pkey, 
                CONCAT(m.lastname, ', ', m.firstname) AS full_name, 
                m.zone, 
                m.rc_id,
                {$discountCols},
                a.housebldg, 
                a.street,
                (
                    SELECT br2.pres_reading 
                    FROM bill_readings br2 
                    JOIN bill_periods bp2 ON bp2.period_id = br2.period_id
                    WHERE br2.member_id = m.pkey 
                      AND bp2.end_date < (SELECT end_date FROM bill_periods WHERE period_id = :period_id)
                    ORDER BY bp2.end_date DESC 
                    LIMIT 1
                ) AS prev_reading,
                br.pres_reading, 
                br.reading_id, 
                br.is_billed
            FROM members m
            JOIN memberaddress ma ON ma.member_key = m.pkey AND ma.address_type_key = 1 AND ma.status = 'A'
            JOIN addresses a ON a.pkey = ma.address_key
            LEFT JOIN water_meter_discounted_members wmdm ON m.pkey = wmdm.member_id
            LEFT JOIN water_meter_discounts wmd ON wmd.wmdiscount_id = wmdm.wmdiscount_id
            LEFT JOIN bill_readings br ON br.member_id = m.pkey AND br.period_id = :period_id2
            WHERE m.status = 'A' 
              AND a.street = :street
            ORDER BY m.lastname, m.firstname
        ";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':period_id'  => $period_id, 
            ':period_id2' => $period_id, 
            ':street'     => $street
        ]);
    } else {
        // --- ZONE FILTER QUERY ---
        $sql = "
            SELECT 
                m.pkey, 
                CONCAT(m.lastname, ', ', m.firstname) AS full_name, 
                m.zone, 
                m.rc_id,
                {$discountCols},
                '' AS housebldg, 
                '' AS street,
                (
                    SELECT br2.pres_reading 
                    FROM bill_readings br2 
                    JOIN bill_periods bp2 ON bp2.period_id = br2.period_id
                    WHERE br2.member_id = m.pkey 
                      AND bp2.end_date < (SELECT end_date FROM bill_periods WHERE period_id = :period_id)
                    ORDER BY bp2.end_date DESC 
                    LIMIT 1
                ) AS prev_reading,
                br.pres_reading, 
                br.reading_id, 
                br.is_billed
            FROM members m
            LEFT JOIN water_meter_discounted_members wmdm ON m.pkey = wmdm.member_id
            LEFT JOIN water_meter_discounts wmd ON wmd.wmdiscount_id = wmdm.wmdiscount_id
            LEFT JOIN bill_readings br ON br.member_id = m.pkey AND br.period_id = :period_id2
            WHERE m.status = 'A' 
              AND m.zone = :zone
            ORDER BY m.lastname, m.firstname
        ";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':period_id'  => $period_id, 
            ':period_id2' => $period_id, 
            ':zone'       => $zone
        ]);
    }

    $members = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Normalize and build discount object per member
    foreach ($members as &$row) {
        $row['prev_reading'] = $row['prev_reading'] !== null ? (float)$row['prev_reading'] : 0;
        $row['pres_reading'] = $row['pres_reading'] !== null ? (float)$row['pres_reading'] : null;
        $row['is_billed']    = (bool)$row['is_billed'];
        $row['reading_id']   = $row['reading_id'] ? (int)$row['reading_id'] : null;

        // Build a structured discount object (mirrors getMemberReadingData.php)
        $row['discount'] = $row['discount_type'] ? [
            'wmd_name'            => $row['discount_type'],
            'free_water_m3'       => (int)($row['free_water_m3'] ?? 0),
            'active_discount'     => $row['active_discount'] ?? 'none',
            'percent_discount'    => (float)($row['percent_discount'] ?? 0),
            'fixed_discount'      => (float)($row['fixed_discount'] ?? 0),
            'max_m3_for_discount' => $row['max_m3_for_discount'] !== null ? (int)$row['max_m3_for_discount'] : null,
        ] : null;

        // Remove the flat discount columns — front-end uses the object above
        unset(
            $row['free_water_m3'],
            $row['active_discount'],
            $row['percent_discount'],
            $row['fixed_discount'],
            $row['max_m3_for_discount']
        );
    }
    unset($row); // break reference

    echo json_encode(['members' => $members]);

} catch (Exception $e) {
    echo json_encode(['error' => 'Database Error: ' . $e->getMessage()]);
}