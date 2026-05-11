<?php
session_start();
require_once '../database/config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $pdo = getDB();
    
    $id      = $_POST['wmdiscount_id'];
    $name    = $_POST['wmd_name'];
    $free    = $_POST['free_water_m3'] ?? 0;
    $active  = $_POST['active_discount'] ?? 'none';
    $percent = $_POST['percent_discount'] ?? 0;
    $fixed   = $_POST['fixed_discount'] ?? 0;
    $max     = !empty($_POST['max_m3_for_discount']) ? $_POST['max_m3_for_discount'] : null;
    $user    = $_SESSION['username'] ?? 'admin';

    try {
        $stmt = $pdo->prepare("UPDATE water_meter_discounts SET wmd_name=:nm, free_water_m3=:free, percent_discount=:pct, fixed_discount=:fix, active_discount=:act, max_m3_for_discount=:max, created_by=:usr, updated_at=NOW() WHERE wmdiscount_id=:id");
        $stmt->execute([':nm'=>$name, ':free'=>$free, ':pct'=>$percent, ':fix'=>$fixed, ':act'=>$active, ':max'=>$max, ':usr'=>$user, ':id'=>$id]);
        $_SESSION['flash']['success'] = "Discount configuration updated.";
    } catch (PDOException $e) {
        $_SESSION['flash']['error'] = "Failed to update discount.";
    }
}
header('Location: ../views/water_meter.php');
exit;