<?php
// process/deleteRateCode.php
if (session_status() === PHP_SESSION_NONE) { session_start(); }
require_once '../database/config.php';

// CSRF check
if (!verifyCsrf($_POST['csrf_token'] ?? '')) {
    $_SESSION['flash'] = ['msg' => 'Invalid CSRF token. Please try again.', 'type' => 'error'];
    header('Location: ../views/rate_codes.php');
    exit;
}

$rc_id = (int)($_POST['rc_id'] ?? 0);

if ($rc_id <= 0) {
    $_SESSION['flash'] = ['msg' => 'Invalid rate code ID.', 'type' => 'error'];
    header('Location: ../views/rate_codes.php');
    exit;
}

try {
    $pdo = getDB();

    // Check for dependent records (e.g. members assigned to this rate code)
    // Adjust the table/column name to match your actual schema
    $check = $pdo->prepare("SELECT COUNT(*) FROM members WHERE rate_code = (SELECT rc_code FROM rate_code WHERE rc_id = :rc_id)");
    $check->execute([':rc_id' => $rc_id]);
    $inUse = (int)$check->fetchColumn();

    if ($inUse > 0) {
        $_SESSION['flash'] = [
            'msg'  => "Cannot delete: {$inUse} member(s) are currently assigned to this rate code.",
            'type' => 'error',
        ];
        header('Location: ../views/rate_codes.php');
        exit;
    }

    $stmt = $pdo->prepare("DELETE FROM rate_code WHERE rc_id = :rc_id");
    $stmt->execute([':rc_id' => $rc_id]);

    $_SESSION['flash'] = ['msg' => 'Rate code deleted successfully.', 'type' => 'success'];
} catch (PDOException $e) {
    error_log('[deleteRateCode] ' . $e->getMessage());
    $_SESSION['flash'] = ['msg' => 'A database error occurred. Please try again.', 'type' => 'error'];
}

header('Location: ../views/rate_codes.php');
exit;