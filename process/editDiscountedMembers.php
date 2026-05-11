<?php
/**
 * editDiscountedMembers.php
 * Process file — Update a member's discount assignment.
 */

require_once '../database/config.php';

requireLogin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../views/discount_management.php');
    exit;
}

$pdo = getDB();

$dmId       = (int)($_POST['dm_id'] ?? 0);
$discountId = (int)($_POST['discount_id'] ?? 0);

if ($dmId <= 0 || $discountId <= 0) {
    flash('error', 'Invalid assignment or discount selection.');
    header('Location: ../views/discount_management.php');
    exit;
}

try {
    // 1. Get the current assignment to find the member_id
    $currStmt = $pdo->prepare("SELECT member_id FROM discounted_members WHERE dm_id = :dmid");
    $currStmt->execute([':dmid' => $dmId]);
    $current = $currStmt->fetch(PDO::FETCH_ASSOC);

    if (!$current) {
        flash('error', 'Discount assignment not found.');
        header('Location: ../views/discount_management.php');
        exit;
    }

    $memberId = $current['member_id'];

    // 2. Prevent assigning a discount the member already has (in another record)
    $dupStmt = $pdo->prepare("
        SELECT dm_id FROM discounted_members 
        WHERE member_id = :mid AND discount_id = :did AND dm_id != :dmid
    ");
    $dupStmt->execute([
        ':mid'  => $memberId,
        ':did'  => $discountId,
        ':dmid' => $dmId
    ]);

    if ($dupStmt->fetch()) {
        flash('error', 'This member is already assigned to this discount rule.');
        header('Location: ../views/discount_management.php');
        exit;
    }

    // 3. Update the assignment
    $updStmt = $pdo->prepare("UPDATE discounted_members SET discount_id = :did WHERE dm_id = :dmid");
    $updStmt->execute([
        ':did'  => $discountId,
        ':dmid' => $dmId
    ]);

    flash('success', 'Member discount assignment updated successfully.');

} catch (PDOException $e) {
    flash('error', 'Database error while updating assignment.');
}

header('Location: ../views/discount_management.php');
exit;