<?php
session_start();
require_once '../database/config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $pdo = getDB();
    
    $wmm_id    = $_POST['wmm_id'];
    $rc_id     = $_POST['rc_id'];
    $from_cb   = $_POST['from_cb'];
    $to_cb     = !empty($_POST['to_cb']) ? $_POST['to_cb'] : null;
    $amount    = $_POST['amount'];
    $bill_type = $_POST['bill_type'];
    $user      = $_SESSION['username'] ?? 'admin';

    try {
        $stmt = $pdo->prepare("UPDATE water_meter_members SET rc_id=:rc, from_cb=:from, to_cb=:to, amount=:amt, bill_type=:bill, created_by=:usr, updated_at=NOW() WHERE wmm_id=:id");
        $stmt->execute([':rc'=>$rc_id, ':from'=>$from_cb, ':to'=>$to_cb, ':amt'=>$amount, ':bill'=>$bill_type, ':usr'=>$user, ':id'=>$wmm_id]);
        $_SESSION['flash']['success'] = "Rate tier updated.";
    } catch (PDOException $e) {
        $_SESSION['flash']['error'] = "Failed to update tier.";
    }
}
header('Location: ../views/water_meter.php');
exit;