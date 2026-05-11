<?php
// process/applyDiscountToRateCode.php
// Handles toggling which discount type (percent / value / none) is active for a rate code.
if (session_status() === PHP_SESSION_NONE) { session_start(); }
require_once '../database/config.php';

// CSRF check
if (!verifyCsrf($_POST['csrf_token'] ?? '')) {
    $_SESSION['flash'] = ['msg' => 'Invalid CSRF token. Please try again.', 'type' => 'error'];
    header('Location: ../views/rate_codes.php');
    exit;
}

$rc_id           = (int)($_POST['rc_id']           ?? 0);
$active_discount = $_POST['active_discount']        ?? 'none';

if ($rc_id <= 0) {
    $_SESSION['flash'] = ['msg' => 'Invalid rate code ID.', 'type' => 'error'];
    header('Location: ../views/rate_codes.php');
    exit;
}

if (!in_array($active_discount, ['percent', 'value', 'none'], true)) {
    $_SESSION['flash'] = ['msg' => 'Invalid discount type selection.', 'type' => 'error'];
    header('Location: ../views/rate_codes.php');
    exit;
}

try {
    $pdo  = getDB();

    // Safety: if activating percent, ensure percent is > 0
    if ($active_discount === 'percent') {
        $row = $pdo->prepare("SELECT discount_percent FROM rate_code WHERE rc_id = :rc_id");
        $row->execute([':rc_id' => $rc_id]);
        $data = $row->fetch();
        if (!$data || (float)$data['discount_percent'] <= 0) {
            $_SESSION['flash'] = ['msg' => 'Cannot activate percent discount — the percent value is 0. Please edit the rate code first.', 'type' => 'error'];
            header('Location: ../views/rate_codes.php');
            exit;
        }
    }

    // Safety: if activating value, ensure value is > 0
    if ($active_discount === 'value') {
        $row = $pdo->prepare("SELECT discount_value FROM rate_code WHERE rc_id = :rc_id");
        $row->execute([':rc_id' => $rc_id]);
        $data = $row->fetch();
        if (!$data || (float)$data['discount_value'] <= 0) {
            $_SESSION['flash'] = ['msg' => 'Cannot activate value discount — the fixed value is 0. Please edit the rate code first.', 'type' => 'error'];
            header('Location: ../views/rate_codes.php');
            exit;
        }
    }

    $stmt = $pdo->prepare("UPDATE rate_code SET active_discount = :active_discount WHERE rc_id = :rc_id");
    $stmt->execute([
        ':active_discount' => $active_discount,
        ':rc_id'           => $rc_id,
    ]);

    $label = match($active_discount) {
        'percent' => 'Percentage discount activated.',
        'value'   => 'Fixed-value discount activated.',
        default   => 'Discount deactivated (no discount applied).',
    };

    $_SESSION['flash'] = ['msg' => $label, 'type' => 'success'];
} catch (PDOException $e) {
    error_log('[applyDiscountToRateCode] ' . $e->getMessage());
    $_SESSION['flash'] = ['msg' => 'A database error occurred. Please try again.', 'type' => 'error'];
}

header('Location: ../views/rate_codes.php');
exit;