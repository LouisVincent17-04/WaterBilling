<?php
/**
 * deleteDiscountedMembers.php
 * Process file — Remove a discount assignment from a member.
 * No HTML output. Redirects back to discounted_members.php on completion.
 */

require_once '../database/config.php';

requireLogin();

// Accept POST only
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../views/discount_management.php');
    exit;
}

$pdo = getDB();

$dmId = (int)($_POST['dm_id'] ?? 0);

// --- Validation ---
if ($dmId <= 0) {
    flash('error', 'Invalid discount assignment record.');
    header('Location: ../views/discount_management.php');
    exit;
}

// --- Verify the record exists and fetch labels for the success message ---
try {
    $existStmt = $pdo->prepare(
        "SELECT dm.dm_id,
                CONCAT(m.lastname, ', ', m.firstname) AS member_name,
                d.discount_type
         FROM discounted_members dm
         JOIN members   m ON m.pkey        = dm.member_id
         JOIN discounts d ON d.discount_id = dm.discount_id
         WHERE dm.dm_id = :id
         LIMIT 1"
    );
    $existStmt->bindValue(':id', $dmId, PDO::PARAM_INT);
    $existStmt->execute();
    $record = $existStmt->fetch(PDO::FETCH_ASSOC);

    if (!$record) {
        flash('error', 'Discount assignment record not found.');
header('Location: ../views/discount_management.php');
        exit;
    }
} catch (PDOException $e) {
    flash('error', 'Database error. Please try again.');
header('Location: ../views/discount_management.php');
    exit;
}

// --- Delete ---
try {
    $delStmt = $pdo->prepare("DELETE FROM discounted_members WHERE dm_id = :id");
    $delStmt->bindValue(':id', $dmId, PDO::PARAM_INT);
    $delStmt->execute();

    $memberName  = htmlspecialchars($record['member_name']);
    $discountLbl = htmlspecialchars($record['discount_type']);

    flash('success', '"' . $discountLbl . '" discount removed from ' . $memberName . '.');
} catch (PDOException $e) {
    flash('error', 'Failed to remove discount assignment. Please try again.');
}

header('Location: ../views/discount_management.php');
exit;