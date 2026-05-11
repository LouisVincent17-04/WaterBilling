<?php
session_start();
require_once '../database/config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $pdo = getDB();
    
    $rc_id      = $_POST['rc_id'];
    $from_cb    = $_POST['from_cb'];
    $to_cb      = !empty($_POST['to_cb']) ? $_POST['to_cb'] : null;
    $amount     = $_POST['amount'];
    $bill_type  = $_POST['bill_type'];
    $created_by = $_SESSION['username'] ?? 'admin';

    try {
        $stmt = $pdo->prepare("INSERT INTO water_meter_members (rc_id, from_cb, to_cb, amount, bill_type, created_by, created_at, updated_at) VALUES (:rc, :from, :to, :amt, :bill, :usr, NOW(), NOW())");
        $stmt->execute([':rc'=>$rc_id, ':from'=>$from_cb, ':to'=>$to_cb, ':amt'=>$amount, ':bill'=>$bill_type, ':usr'=>$created_by]);
        $_SESSION['flash']['success'] = "Rate tier added.";
    } catch (PDOException $e) {
        $_SESSION['flash']['error'] = $e->getMessage();
    }
}
header('Location: ../views/water_meter.php');
exit; // Senior Citizen with Discount of 5% on consumption between 0 - 30 cu.m