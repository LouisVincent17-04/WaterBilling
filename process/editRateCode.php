<?php
// process/editRateCode.php
if (session_status() === PHP_SESSION_NONE) { session_start(); }
require_once '../database/config.php';

// CSRF check
if (!verifyCsrf($_POST['csrf_token'] ?? '')) {
    $_SESSION['flash'] = ['msg' => 'Invalid CSRF token. Please try again.', 'type' => 'error'];
    header('Location: ../views/rate_codes.php');
    exit;
}

$rc_id             = (int)($_POST['rc_id']              ?? 0);
$rc_name           = strtoupper(trim($_POST['rc_name']  ?? ''));
$discount_percent  = (float)($_POST['discount_percent'] ?? 0);
$discount_value    = (float)($_POST['discount_value']   ?? 0);
$active_discount   = $_POST['active_discount']          ?? 'none';

// --- Validation ---
$errors = [];

if ($rc_id <= 0) {
    $errors[] = 'Invalid rate code ID.';
}
if ($rc_name === '') {
    $errors[] = 'Rate name is required.';
}
if ($discount_percent < 0 || $discount_percent > 100) {
    $errors[] = 'Discount percent must be between 0 and 100.';
}
if ($discount_value < 0) {
    $errors[] = 'Discount value cannot be negative.';
}
if (!in_array($active_discount, ['percent', 'value', 'none'], true)) {
    $active_discount = 'none';
}

if ($errors) {
    $_SESSION['flash'] = ['msg' => implode(' ', $errors), 'type' => 'error'];
    header('Location: ../views/rate_codes.php');
    exit;
}

try {
    $pdo  = getDB();
    $stmt = $pdo->prepare("
        UPDATE rate_code
        SET rc_name          = :rc_name,
            discount_percent = :discount_percent,
            discount_value   = :discount_value,
            active_discount  = :active_discount
        WHERE rc_id = :rc_id
    ");
    $stmt->execute([
        ':rc_name'          => $rc_name,
        ':discount_percent' => $discount_percent,
        ':discount_value'   => $discount_value,
        ':active_discount'  => $active_discount,
        ':rc_id'            => $rc_id,
    ]);

    $_SESSION['flash'] = ['msg' => 'Rate code updated successfully.', 'type' => 'success'];
} catch (PDOException $e) {
    error_log('[editRateCode] ' . $e->getMessage());
    $_SESSION['flash'] = ['msg' => 'A database error occurred. Please try again.', 'type' => 'error'];
}

header('Location: ../views/rate_codes.php');
exit;