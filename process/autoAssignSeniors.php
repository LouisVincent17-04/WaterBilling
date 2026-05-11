<?php
/**
 * autoAssignSeniors.php
 * Path: ../process/autoAssignSeniors.php
 * Process file — Batch assigns the Senior Citizen discount to eligible members.
 */

require_once '../database/config.php';

// If you have a login check, uncomment it:
// requireLogin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../views/discount_management.php');
    exit;
}

$pdo = getDB();

try {
    // 1. Find the Senior Citizen discount ID
    // Removes spaces and checks for variations of "senior" or "seniorcitizen"
    $srStmt = $pdo->prepare("
        SELECT discount_id 
        FROM discounts 
        WHERE LOWER(REPLACE(discount_type, ' ', '')) LIKE '%senior%' 
        LIMIT 1
    ");
    $srStmt->execute();
    $seniorDiscount = $srStmt->fetch(PDO::FETCH_ASSOC);

    if (!$seniorDiscount) {
        // Fallback message using session variables for your UI alerts
        $_SESSION['flash']['error'] = 'Senior Citizen discount rule not found. Please create a discount type with "Senior" in the name first.';
        header('Location: ../views/discount_management.php');
        exit;
    }

    $discountId = $seniorDiscount['discount_id'];

    // 2. Find eligible members:
    // Status = 'A' (Active), Age >= 60, and they don't already have ANY discount assigned
    $findStmt = $pdo->prepare("
        SELECT pkey 
        FROM members 
        WHERE status = 'A' 
          AND dateofbirth IS NOT NULL 
          AND TIMESTAMPDIFF(YEAR, dateofbirth, CURDATE()) >= 60
          AND pkey NOT IN (SELECT member_id FROM discounted_members)
    ");
    $findStmt->execute();
    $eligibleMembers = $findStmt->fetchAll(PDO::FETCH_COLUMN);

    if (empty($eligibleMembers)) {
        $_SESSION['flash']['info'] = 'No new eligible Senior Citizens found. All members 60+ are already assigned.';
        header('Location: ../views/discount_management.php');
        exit;
    }

    // 3. Batch Insert the discount assignments
    $insertStmt = $pdo->prepare("
        INSERT INTO discounted_members (member_id, discount_id, created_at) 
        VALUES (:mid, :did, NOW())
    ");
    
    $pdo->beginTransaction();
    $count = 0;
    
    foreach ($eligibleMembers as $mid) {
        $insertStmt->execute([
            ':mid' => $mid,
            ':did' => $discountId
        ]);
        $count++;
    }
    
    $pdo->commit();

    $_SESSION['flash']['success'] = "Success! Auto-assigned the Senior Citizen discount to {$count} eligible member(s).";

} catch (PDOException $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    $_SESSION['flash']['error'] = 'Database error during auto-assignment. Please try again.';
}

header('Location: ../views/discount_management.php');
exit;