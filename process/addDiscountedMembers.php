<?php
/**
 * addDiscountedMembers.php
 * Process file — Assign a discount to a member.
 *
 * AUTO-DISCOUNT RULE:
 *   If the member is 60 years old or above, the system automatically
 *   overrides the selected discount and assigns the "Senior Citizen"
 *   discount instead (matched via LIKE '%Senior%').
 *
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

// --- Sanitize inputs ---
$memberId   = (int)($_POST['member_id']   ?? 0);
$discountId = (int)($_POST['discount_id'] ?? 0);

// --- Validation ---
$errors = [];

if ($memberId <= 0) {
    $errors[] = 'Please select a valid member.';
}
if ($discountId <= 0) {
    $errors[] = 'Please select a discount type.';
}

if (!empty($errors)) {
    flash('error', implode(' ', $errors));
    header('Location: ../views/discount_management.php');
    exit;
}

// --- Fetch member (validate existence + get birthdate for age check) ---
try {
    $mStmt = $pdo->prepare(
        "SELECT pkey, lastname, firstname, dateofbirth
         FROM members
         WHERE pkey = :id
         LIMIT 1"
    );
    $mStmt->bindValue(':id', $memberId, PDO::PARAM_INT);
    $mStmt->execute();
    $member = $mStmt->fetch(PDO::FETCH_ASSOC);

    if (!$member) {
        flash('error', 'Selected member does not exist.');
        header('Location: ../views/discount_management.php');
        exit;
    }
} catch (PDOException $e) {
    flash('error', 'Database error while validating member. Please try again.');
    header('Location: ../views/discount_management.php');
    exit;
}

// -------------------------------------------------------------------
// AUTO-DISCOUNT LOGIC
// If the member is 60 years or older, override with Senior Citizen
// discount regardless of what was selected in the form.
// -------------------------------------------------------------------
$autoAssigned   = false;
$autoAssignNote = '';

if (!empty($member['dateofbirth'])) {
    try {
        $birthDate = new DateTime($member['dateofbirth']);
        $today     = new DateTime();
        $age       = (int)$today->diff($birthDate)->y;

        if ($age >= 60) {
            // Look up the Senior Citizen discount
            $srStmt = $pdo->prepare(
                "SELECT discount_id, discount_type
                 FROM discounts
                 WHERE LOWER(discount_type) LIKE '%senior%'
                 LIMIT 1"
            );
            $srStmt->execute();
            $seniorDiscount = $srStmt->fetch(PDO::FETCH_ASSOC);

            if ($seniorDiscount) {
                $discountId  = (int)$seniorDiscount['discount_id'];
                $autoAssigned = true;
                $autoAssignNote = 'Member is ' . $age . ' years old. Senior Citizen discount was automatically assigned.';
            }
        }
    } catch (Exception $e) {
        // Date parse failure — continue with the selected discount
    }
}

// --- Validate discount exists ---
try {
    $dStmt = $pdo->prepare(
        "SELECT discount_id, discount_type FROM discounts WHERE discount_id = :id LIMIT 1"
    );
    $dStmt->bindValue(':id', $discountId, PDO::PARAM_INT);
    $dStmt->execute();
    $discount = $dStmt->fetch(PDO::FETCH_ASSOC);

    if (!$discount) {
        flash('error', 'Selected discount type does not exist.');
        header('Location: ../views/discount_management.php');
        exit;
    }
} catch (PDOException $e) {
    flash('error', 'Database error while validating discount. Please try again.');
    header('Location: ../views/discount_management.php');
    exit;
}

// --- Duplicate check: member cannot have the same discount twice ---
try {
    $dupStmt = $pdo->prepare(
        "SELECT dm_id FROM discounted_members
         WHERE member_id = :mid AND discount_id = :did
         LIMIT 1"
    );
    $dupStmt->bindValue(':mid', $memberId,   PDO::PARAM_INT);
    $dupStmt->bindValue(':did', $discountId, PDO::PARAM_INT);
    $dupStmt->execute();

    if ($dupStmt->fetch()) {
        $memberName = htmlspecialchars($member['lastname'] . ', ' . $member['firstname']);
        flash('error', '"' . $memberName . '" already has the "' . htmlspecialchars($discount['discount_type']) . '" discount applied.');
        header('Location: ../views/discount_management.php');
        exit;
    }
} catch (PDOException $e) {
    flash('error', 'Database error during duplicate check. Please try again.');
    header('Location: ../views/discount_management.php');
    exit;
}

// --- Insert ---
try {
    $insStmt = $pdo->prepare(
        "INSERT INTO discounted_members (member_id, discount_id, created_at)
         VALUES (:mid, :did, NOW())"
    );
    $insStmt->bindValue(':mid', $memberId,   PDO::PARAM_INT);
    $insStmt->bindValue(':did', $discountId, PDO::PARAM_INT);
    $insStmt->execute();

    $memberName  = htmlspecialchars($member['lastname'] . ', ' . $member['firstname']);
    $discountLbl = htmlspecialchars($discount['discount_type']);

    if ($autoAssigned) {
        flash('success', '"' . $memberName . '" — ' . $autoAssignNote);
    } else {
        flash('success', '"' . $discountLbl . '" discount applied to ' . $memberName . ' successfully.');
    }
} catch (PDOException $e) {
    flash('error', 'Failed to apply discount. Please try again.');
}

header('Location: ../views/discount_management.php');
exit;