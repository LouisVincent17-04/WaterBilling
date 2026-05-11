<?php
session_start();
require_once '../database/config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $pdo = getDB();
    
    $name    = $_POST['wmd_name'];
    $free    = $_POST['free_water_m3'] ?? 0;
    $active  = $_POST['active_discount'] ?? 'none';
    $percent = $_POST['percent_discount'] ?? 0;
    $fixed   = $_POST['fixed_discount'] ?? 0;
    $max     = !empty($_POST['max_m3_for_discount']) ? $_POST['max_m3_for_discount'] : null;
    $user    = $_SESSION['username'] ?? 'admin';

    try {
        $stmt = $pdo->prepare("INSERT INTO water_meter_discounts (wmd_name, free_water_m3, percent_discount, fixed_discount, active_discount, max_m3_for_discount, created_by, created_at, updated_at) VALUES (:nm, :free, :pct, :fix, :act, :max, :usr, NOW(), NOW())");
        $stmt->execute([':nm'=>$name, ':free'=>$free, ':pct'=>$percent, ':fix'=>$fixed, ':act'=>$active, ':max'=>$max, ':usr'=>$user]);
        $_SESSION['flash']['success'] = "Discount configuration saved.";
    } catch (PDOException $e) {
        $_SESSION['flash']['error'] = "Failed to save discount configuration.";
    }
}
header('Location: ../views/water_meter.php');
exit;